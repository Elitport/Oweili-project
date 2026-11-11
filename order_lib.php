<?php
/* =====================================================================
 *  order_lib.php — orders and escrow: READY BUT SWITCHED OFF (2 Nov 2026).
 *  No payment provider is connected. Nothing here can take money.
 *
 *  Switched on ONLY when all three are true (owPaymentsOn):
 *    1. config.php on the server has define('OWEILI_PAYMENT_GATEWAY', 'moyasar' | 'tap' | 'hyperpay')
 *       and that provider's keys (not built yet: a gateway file must be written for it)
 *    2. the commercial registration number is set (OW_COMPANY_CR / OWEILI_COMPANY_CR)
 *    3. the admin setting site_settings 'payments_enabled' = '1'
 *  Until then every page that would show orders answers 404, and no buy
 *  button exists anywhere on the site.
 *
 *  How the money moves once it is on (escrow):
 *    created → paid (the provider confirms) → shipped → delivered
 *    → released: the buyer presses "I received it", or 7 days after
 *      delivery with no open complaint (owOrderAutoRelease, once a day)
 *    → a payout row for the artist: amount minus Oweili's commission
 *      (site_settings 'commission_rate', 15 by default, before VAT)
 *    delivered → complaint (the buyer's complaint button, before release)
 *      → the admin decides: released (pay the artist) or refunded
 *    created → cancelled; paid → refunded (the admin)
 *  Orders can be for: artwork, gift, commission, course, workshop.
 *  Every change is an order_events row (who, when, from, to, note).
 *  Tables: orders, order_events, order_complaints, payouts, site_settings
 * ===================================================================== */
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/gallery_lib.php';   // owSetting

const OW_ORDER_KINDS = ['artwork' => ['Artwork', 'عمل فني'], 'gift' => ['Gift', 'هدية'], 'commission' => ['Private commission', 'عمل خاص'],
                        'course' => ['Course ticket', 'تذكرة دورة'], 'workshop' => ['Workshop ticket', 'تذكرة ورشة']];
const OW_ORDER_STATUS = ['created' => ['Waiting for payment', 'بانتظار الدفع'], 'paid' => ['Paid, held by Oweili', 'مدفوع، محفوظ لدى أويلي'],
                         'shipped' => ['On its way', 'في الطريق'], 'delivered' => ['Delivered', 'تم التسليم'], 'complaint' => ['Complaint open', 'شكوى مفتوحة'],
                         'released' => ['Completed, artist paid', 'مكتمل، دُفع للفنان'], 'refunded' => ['Refunded', 'مُسترد'], 'cancelled' => ['Cancelled', 'ملغى']];
/* Which status may follow which (anything else is refused) */
const OW_ORDER_FLOW = ['created' => ['paid', 'cancelled'], 'paid' => ['shipped', 'delivered', 'refunded'], 'shipped' => ['delivered', 'refunded'],
                       'delivered' => ['released', 'complaint'], 'complaint' => ['released', 'refunded']];
const OW_ESCROW_DAYS = 7;
const OW_GATEWAYS = ['moyasar' => 'Moyasar', 'tap' => 'Tap Payments', 'hyperpay' => 'HyperPay'];

function owOrdersReady(): bool { return owHasTable('orders') && owHasTable('order_events') && owHasTable('order_complaints'); }
function owGatewayName(): string { $g = defined('OWEILI_PAYMENT_GATEWAY') ? (string) OWEILI_PAYMENT_GATEWAY : ''; return isset(OW_GATEWAYS[$g]) ? $g : ''; }
/* What is still missing before payments can be switched on: [[en, ar], ...] */
function owPaymentsMissing(): array {
    $m = [];
    if (!owOrdersReady()) $m[] = ['The orders tables (night_update.sql)', 'جداول الطلبات (night_update.sql)'];
    if (owGatewayName() === '') $m[] = ['A payment provider account (Moyasar, Tap or HyperPay), its keys in config.php, and its connection file written and tested', 'حساب لدى مزوّد دفع (Moyasar أو Tap أو HyperPay)، ومفاتيحه في config.php، وكتابة ملف الربط واختباره'];
    if (function_exists('owCompanyCr') ? owCompanyCr() === '' : true) $m[] = ['The commercial registration number (OWEILI_COMPANY_CR in config.php)', 'رقم السجل التجاري (OWEILI_COMPANY_CR في config.php)'];
    if (owSetting('payments_enabled', '0') !== '1') $m[] = ['The owner’s final "switch on" (setting payments_enabled)', 'موافقة المالك النهائية بالتشغيل (الإعداد payments_enabled)'];
    return $m;
}
function owPaymentsOn(): bool { return owPaymentsMissing() === []; }
function owCommissionRate(): float { return owCommissionPct(); }   // one number, Admin → Settings (platform_lib.php)
/* Oweili's share and the artist's share of an amount (before VAT), in SAR with 2 decimals */
function owSplitAmount(float $amount, ?float $rate = null): array {
    $rate = $rate ?? owCommissionRate();
    $fee = round($amount * $rate / 100, 2);
    return ['rate' => $rate, 'commission' => $fee, 'artist' => round($amount - $fee, 2)];
}
function owOrderNewNo(): string {
    $abc = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    for ($i = 0; $i < 20; $i++) {
        $n = 'O-' . date('Y') . '-'; for ($j = 0; $j < 6; $j++) $n .= $abc[random_int(0, 31)];
        $st = getDB()->prepare('SELECT 1 FROM orders WHERE order_no = :n'); $st->execute([':n' => $n]);
        if (!$st->fetchColumn()) return $n;
    }
    throw new RuntimeException('no free order number');
}
/* Makes an order (status created). Refused (null) while payments are off. */
function owOrderCreate(string $kind, ?int $refId, array $buyer, float $amount, ?int $artistId, string $title, float $vat = 0.0): ?int {
    if (!owPaymentsOn() || !isset(OW_ORDER_KINDS[$kind]) || $amount <= 0) return null;
    $split = owSplitAmount($amount);
    $no = owOrderNewNo();
    getDB()->prepare('INSERT INTO orders (order_no, kind, ref_id, buyer_user_id, buyer_name, buyer_email, buyer_phone, artist_id, title, amount, vat_amount, commission_rate, commission_amount, artist_amount, gateway)
                      VALUES (:no, :k, :r, :bu, :bn, :be, :bp, :a, :t, :am, :v, :cr, :ca, :aa, :g)')
           ->execute([':no' => $no, ':k' => $kind, ':r' => $refId, ':bu' => $buyer['user_id'] ?? null, ':bn' => mb_substr((string) ($buyer['name'] ?? ''), 0, 150),
                      ':be' => mb_substr((string) ($buyer['email'] ?? ''), 0, 190), ':bp' => mb_substr((string) ($buyer['phone'] ?? ''), 0, 30), ':a' => $artistId,
                      ':t' => mb_substr($title, 0, 255), ':am' => round($amount, 2), ':v' => round($vat, 2), ':cr' => $split['rate'], ':ca' => $split['commission'],
                      ':aa' => $kind === 'course' || $kind === 'workshop' ? 0 : $split['artist'], ':g' => owGatewayName()]);
    $id = (int) getDB()->lastInsertId();
    owOrderEvent($id, '', 'created', null, 'order made');
    return $id;
}
function owOrderEvent(int $orderId, string $from, string $to, ?array $actor, string $note = ''): void {
    $actor = $actor ?? ['type' => 'system', 'id' => null];
    getDB()->prepare('INSERT INTO order_events (order_id, from_status, to_status, actor_type, actor_id, note) VALUES (:o, :f, :t, :at, :ai, :n)')
           ->execute([':o' => $orderId, ':f' => $from, ':t' => $to, ':at' => $actor['type'], ':ai' => $actor['id'], ':n' => mb_substr($note, 0, 500)]);
    owLog('order_' . $to, 'order', $orderId, $note, $actor);
}
function owOrderGet(int $id): ?array { $st = getDB()->prepare('SELECT * FROM orders WHERE id = :id'); $st->execute([':id' => $id]); return $st->fetch() ?: null; }
/* Moves an order one step, only along OW_ORDER_FLOW. Release also makes the artist's payout. */
function owOrderMove(int $id, string $to, ?array $actor, string $note = ''): bool {
    $db = getDB();
    $db->beginTransaction();
    try {
        $st = $db->prepare('SELECT * FROM orders WHERE id = :id FOR UPDATE'); $st->execute([':id' => $id]);
        $o = $st->fetch();
        if (!$o || !in_array($to, OW_ORDER_FLOW[$o['status']] ?? [], true)) { $db->rollBack(); return false; }
        $set = ['status = :s']; $args = [':s' => $to, ':id' => $id];
        if ($to === 'paid')      $set[] = 'paid_at = NOW()';
        if ($to === 'shipped')   $set[] = 'shipped_at = NOW()';
        if ($to === 'delivered') { $set[] = 'delivered_at = NOW()'; $set[] = 'release_due_at = NOW() + INTERVAL ' . OW_ESCROW_DAYS . ' DAY'; }
        if ($to === 'released')  $set[] = 'released_at = NOW()';
        $db->prepare('UPDATE orders SET ' . implode(', ', $set) . ' WHERE id = :id')->execute($args);
        if ($to === 'released' && $o['artist_id'] && (float) $o['artist_amount'] > 0 && owHasColumn('payouts', 'order_id')) {
            $ex = $db->prepare('SELECT 1 FROM payouts WHERE order_id = :o'); $ex->execute([':o' => $id]);
            if (!$ex->fetchColumn()) $db->prepare("INSERT INTO payouts (artist_id, amount, currency, status, order_id, note) VALUES (:a, :m, :c, 'pending', :o, :n)")
                                       ->execute([':a' => (int) $o['artist_id'], ':m' => $o['artist_amount'], ':c' => $o['currency'], ':o' => $id, ':n' => 'Order ' . $o['order_no']]);
        }
        if (in_array($to, ['released', 'refunded'], true)) $db->prepare("UPDATE order_complaints SET status = :s, resolved_at = NOW(), resolution_note = :n WHERE order_id = :o AND status = 'open'")
                                                           ->execute([':s' => $to, ':n' => mb_substr($note, 0, 1000), ':o' => $id]);
        $db->commit();
    } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); error_log('owOrderMove: ' . $e->getMessage()); return false; }
    owOrderEvent($id, (string) $o['status'], $to, $actor, $note);
    return true;
}
/* The buyer: "I received it" (releases the money to the artist) */
function owOrderConfirm(int $id, int $buyerId): bool {
    $o = owOrderGet($id);
    if (!$o || (int) $o['buyer_user_id'] !== $buyerId || $o['status'] !== 'delivered') return false;
    getDB()->prepare('UPDATE orders SET confirmed_at = NOW() WHERE id = :id')->execute([':id' => $id]);
    return owOrderMove($id, 'released', ['type' => 'buyer', 'id' => $buyerId], 'the buyer confirmed receipt');
}
/* The buyer: "Something is wrong" (holds the money until the admin decides) */
function owOrderComplain(int $id, int $buyerId, string $reason): bool {
    $o = owOrderGet($id);
    $reason = mb_substr(trim($reason), 0, 3000);
    if (!$o || (int) $o['buyer_user_id'] !== $buyerId || $o['status'] !== 'delivered' || mb_strlen($reason) < 5) return false;
    if (!owOrderMove($id, 'complaint', ['type' => 'buyer', 'id' => $buyerId], 'complaint')) return false;
    getDB()->prepare('INSERT INTO order_complaints (order_id, buyer_user_id, reason) VALUES (:o, :b, :r)')->execute([':o' => $id, ':b' => $buyerId, ':r' => $reason]);
    try { require_once __DIR__ . '/alert_lib.php'; owAdminAlert('order', 'Complaint on order ' . $o['order_no'], 'شكوى على الطلب ' . $o['order_no'], 'admin_orders.php?id=' . $id); } catch (Throwable $e) {}
    return true;
}
/* 7 days after delivery with no open complaint: the money is released */
function owOrderAutoRelease(): int {
    if (!owPaymentsOn()) return 0;
    $n = 0;
    $rows = getDB()->query("SELECT id FROM orders WHERE status = 'delivered' AND release_due_at IS NOT NULL AND release_due_at <= NOW() LIMIT 100")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($rows as $id) if (owOrderMove((int) $id, 'released', ['type' => 'system', 'id' => null], OW_ESCROW_DAYS . ' days after delivery, no complaint')) $n++;
    return $n;
}
function owOrderPill(string $s): string {
    $w = OW_ORDER_STATUS[$s] ?? [$s, $s];
    return '<span class="pillx' . (in_array($s, ['complaint', 'created'], true) ? ' warn' : (in_array($s, ['released'], true) ? ' on' : '')) . '">' . owBi($w[0], $w[1]) . '</span>';
}
