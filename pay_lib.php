<?php
/* =====================================================================
 *  pay_lib.php — payments for course bookings and museum tickets
 *  (owner, 14 Nov 2026). TEST MODE ONLY. No real money can move here.
 *
 *  Providers:
 *    test     our own test page (pay_test.php): always there in test mode
 *    moyasar  mada, cards, Apple Pay (Moyasar hosted invoice page)
 *    tabby    Tabby, pay in 4
 *    tamara   Tamara, pay in instalments
 *  A provider shows only when its TEST keys are in config.php on the server
 *  (never in GitHub, never in chat):
 *    define('OWEILI_MOYASAR_SK', 'sk_test_…');       // Moyasar secret key, test
 *    define('OWEILI_TABBY_PK', 'pk_test_…');         // Tabby public key, test
 *    define('OWEILI_TABBY_SK', 'sk_test_…');         // Tabby secret key, test
 *    define('OWEILI_TABBY_MERCHANT', '…');           // Tabby merchant code
 *    define('OWEILI_TAMARA_TOKEN', '…');             // Tamara sandbox API token
 *  Live keys are refused (a Moyasar or Tabby key that does not say "test"),
 *  and Tamara always talks to its sandbox.
 *  The amount is always checked again with the provider before a booking
 *  is marked paid. Nothing here has been tried against the providers yet:
 *  that needs their test keys.
 *  Tables: booking_payments
 * ===================================================================== */
require_once __DIR__ . '/booking_lib.php';

const OW_PAY_NAMES = [
    'test'    => ['Test payment (no real money)', 'دفع تجريبي (بلا مال حقيقي)'],
    'moyasar' => ['mada, card or Apple Pay (Moyasar)', 'مدى أو البطاقة أو Apple Pay (ميسر)'],
    'tabby'   => ['Tabby: split in 4', 'تابي: قسّمها على 4'],
    'tamara'  => ['Tamara: pay in instalments', 'تمارا: ادفع على دفعات'],
];
const OW_TAMARA_HOST = 'https://api-sandbox.tamara.co';
const OW_TABBY_HOST  = 'https://api.tabby.sa';
const OW_MOYASAR_HOST = 'https://api.moyasar.com/v1';

function owKey(string $name): string { return defined($name) ? trim((string) constant($name)) : ''; }
function owPayTestKey(string $k): bool { return $k !== '' && stripos($k, 'test') !== false; }
/* Which providers can be offered right now */
function owPayGateways(): array {
    $g = owIsAdmin() ? ['test'] : [];   // Oweili's own test payment: only for the signed-in admin (owner, 14 Nov 2026), never for visitors
    if (owPayTestKey(owKey('OWEILI_MOYASAR_SK'))) $g[] = 'moyasar';
    if (owPayTestKey(owKey('OWEILI_TABBY_PK')) && owPayTestKey(owKey('OWEILI_TABBY_SK')) && owKey('OWEILI_TABBY_MERCHANT') !== '') $g[] = 'tabby';
    if (owKey('OWEILI_TAMARA_TOKEN') !== '') $g[] = 'tamara';
    return $g;
}
/* What each provider still needs (for Admin) */
function owPayKeysStatus(): array {
    return [
        'moyasar' => owPayTestKey(owKey('OWEILI_MOYASAR_SK')) ? '' : (owKey('OWEILI_MOYASAR_SK') !== '' ? 'live key refused' : 'OWEILI_MOYASAR_SK'),
        'tabby'   => (owPayTestKey(owKey('OWEILI_TABBY_PK')) && owPayTestKey(owKey('OWEILI_TABBY_SK')) && owKey('OWEILI_TABBY_MERCHANT') !== '') ? '' : 'OWEILI_TABBY_PK, OWEILI_TABBY_SK, OWEILI_TABBY_MERCHANT',
        'tamara'  => owKey('OWEILI_TAMARA_TOKEN') !== '' ? '' : 'OWEILI_TAMARA_TOKEN',
    ];
}

/* A payment row for one booking. Reuses the open one if there is one. */
function owPayNew(string $kind, int $refId, int $userId, float $amount): array {
    $db = getDB();
    $st = $db->prepare("SELECT * FROM booking_payments WHERE kind = :k AND ref_id = :r AND status = 'created' ORDER BY id DESC LIMIT 1");
    $st->execute([':k' => $kind, ':r' => $refId]);
    if ($p = $st->fetch()) {
        if ((float) $p['amount'] === round($amount, 2)) return $p;
        $db->prepare("UPDATE booking_payments SET status = 'cancelled' WHERE id = :id")->execute([':id' => $p['id']]);
    }
    $tok = bin2hex(random_bytes(16));
    $db->prepare('INSERT INTO booking_payments (token, kind, ref_id, user_id, amount, test_mode) VALUES (:t, :k, :r, :u, :a, 1)')
       ->execute([':t' => $tok, ':k' => $kind, ':r' => $refId, ':u' => $userId, ':a' => round($amount, 2)]);
    return owPayByToken($tok);
}
function owPayByToken(string $tok): ?array {
    if (!preg_match('/^[a-f0-9]{32}$/', $tok)) return null;
    $st = getDB()->prepare('SELECT * FROM booking_payments WHERE token = :t'); $st->execute([':t' => $tok]);
    return $st->fetch() ?: null;
}
/* What the payment is for: title, buyer, the booking row */
function owPayWhat(array $pay): ?array {
    if ($pay['kind'] === 'course') {
        $st = getDB()->prepare('SELECT * FROM course_registrations WHERE id = :id'); $st->execute([':id' => (int) $pay['ref_id']]); $r = $st->fetch();
        if (!$r) return null;
        $c = owCourseGet((int) $r['course_id']);
        return ['row' => $r, 'title_en' => (string) ($c['title_en'] ?? ''), 'title_ar' => (string) ($c['title_ar'] ?? ''), 'name' => $r['student_name'], 'email' => $r['student_email'], 'phone' => $r['student_phone'],
                'back' => 'course.php?id=' . (int) $r['course_id'], 'ok' => in_array($r['status'], ['pending', 'confirmed'], true)];
    }
    $st = getDB()->prepare('SELECT * FROM museum_tickets WHERE id = :id'); $st->execute([':id' => (int) $pay['ref_id']]); $t = $st->fetch();
    if (!$t) return null;
    $p = owMuseumGet((int) $t['place_id']);
    return ['row' => $t, 'title_en' => 'Tickets: ' . ($p['name_en'] ?? ''), 'title_ar' => 'تذاكر: ' . ($p['name_ar'] ?? ''), 'name' => $t['buyer_name'], 'email' => $t['buyer_email'], 'phone' => $t['buyer_phone'],
            'back' => 'ticket_buy.php?p=' . (int) $t['place_id'], 'ok' => in_array($t['status'], ['pending', 'paid'], true)];
}
function owPayReturnUrl(array $pay, string $g): string { return owSiteBase() . '/pay_return.php?t=' . $pay['token'] . '&g=' . $g; }

/* ---------------- calling a provider ---------------- */
function owPayHttp(string $method, string $url, array $headers, ?array $body = null, ?string $basicUser = null): array {
    if (!function_exists('curl_init')) return [0, null];
    $ch = curl_init($url);
    $h = array_merge(['Accept: application/json'], $headers);
    if ($body !== null) { $h[] = 'Content-Type: application/json'; curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE)); }
    curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $h, CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 8]);
    if ($basicUser !== null) curl_setopt($ch, CURLOPT_USERPWD, $basicUser . ':');
    $raw = curl_exec($ch); $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE); curl_close($ch);
    return [$code, is_string($raw) ? json_decode($raw, true) : null];
}
function owPayNote(array $pay, string $note): void {
    getDB()->prepare('UPDATE booking_payments SET note = :n WHERE id = :id')->execute([':n' => mb_substr($note, 0, 500), ':id' => (int) $pay['id']]);
}

/* Starts the payment: returns the address to send the visitor to, or null (with a note saved) */
function owPayStart(array $pay, string $g): ?string {
    if (!in_array($g, owPayGateways(), true) || $pay['status'] !== 'created') return null;
    $what = owPayWhat($pay); if (!$what || !$what['ok']) return null;
    getDB()->prepare('UPDATE booking_payments SET gateway = :g WHERE id = :id')->execute([':g' => $g, ':id' => (int) $pay['id']]);
    $halalas = (int) round((float) $pay['amount'] * 100);
    $ret = owPayReturnUrl($pay, $g);
    if ($g === 'test') return owBase() . 'pay_test.php?t=' . $pay['token'];
    if ($g === 'moyasar') {
        [$code, $j] = owPayHttp('POST', OW_MOYASAR_HOST . '/invoices', [], ['amount' => $halalas, 'currency' => 'SAR', 'description' => mb_substr($what['title_en'] . ' (' . $what['row']['ref_no'] . ')', 0, 250),
                                    'callback_url' => $ret, 'success_url' => $ret, 'back_url' => owSiteBase() . '/' . $what['back'], 'metadata' => ['booking' => $pay['token']]], owKey('OWEILI_MOYASAR_SK'));
        if ($code >= 200 && $code < 300 && !empty($j['url']) && !empty($j['id'])) { owPaySetRef($pay, (string) $j['id']); return (string) $j['url']; }
        owPayNote($pay, 'moyasar start failed: HTTP ' . $code); return null;
    }
    $first = trim(strtok((string) $what['name'], ' ') ?: 'Customer'); $last = trim(substr((string) $what['name'], strlen($first))) ?: $first;
    $item = ['title' => mb_substr($what['title_en'], 0, 120), 'quantity' => 1, 'unit_price' => number_format((float) $pay['amount'], 2, '.', ''), 'reference_id' => $what['row']['ref_no'], 'category' => $pay['kind'] === 'course' ? 'Education' : 'Tickets'];
    if ($g === 'tabby') {
        [$code, $j] = owPayHttp('POST', OW_TABBY_HOST . '/api/v2/checkout', ['Authorization: Bearer ' . owKey('OWEILI_TABBY_PK')], [
            'payment' => ['amount' => number_format((float) $pay['amount'], 2, '.', ''), 'currency' => 'SAR', 'description' => $item['title'],
                          'buyer' => ['name' => $what['name'], 'email' => $what['email'], 'phone' => $what['phone']],
                          'order' => ['reference_id' => $what['row']['ref_no'], 'items' => [$item]],
                          'shipping_address' => ['city' => 'Jeddah', 'address' => 'Digital service, no shipping', 'zip' => '00000']],
            'lang' => owIsAr() ? 'ar' : 'en', 'merchant_code' => owKey('OWEILI_TABBY_MERCHANT'),
            'merchant_urls' => ['success' => $ret, 'cancel' => $ret . '&x=cancel', 'failure' => $ret . '&x=fail']]);
        $url = $j['configuration']['available_products']['installments'][0]['web_url'] ?? '';
        if ($code >= 200 && $code < 300 && $url !== '' && ($j['status'] ?? '') !== 'rejected' && !empty($j['payment']['id'])) { owPaySetRef($pay, (string) $j['payment']['id']); return (string) $url; }
        owPayNote($pay, 'tabby start failed: HTTP ' . $code . ' ' . (string) ($j['status'] ?? '')); return null;
    }
    if ($g === 'tamara') {
        $addr = ['first_name' => $first, 'last_name' => $last, 'line1' => 'Digital service, no shipping', 'city' => 'Jeddah', 'country_code' => 'SA', 'phone_number' => $what['phone']];
        $money = fn(float $v) => ['amount' => round($v, 2), 'currency' => 'SAR'];
        [$code, $j] = owPayHttp('POST', OW_TAMARA_HOST . '/checkout', ['Authorization: Bearer ' . owKey('OWEILI_TAMARA_TOKEN')], [
            'order_reference_id' => $what['row']['ref_no'], 'total_amount' => $money((float) $pay['amount']), 'description' => $item['title'], 'country_code' => 'SA',
            'payment_type' => 'PAY_BY_INSTALMENTS', 'instalments' => 3, 'locale' => owIsAr() ? 'ar_SA' : 'en_US',
            'items' => [['name' => $item['title'], 'type' => 'Digital', 'reference_id' => $what['row']['ref_no'], 'sku' => $what['row']['ref_no'], 'quantity' => 1,
                         'unit_price' => $money((float) $pay['amount']), 'total_amount' => $money((float) $pay['amount'])]],
            'consumer' => ['first_name' => $first, 'last_name' => $last, 'phone_number' => $what['phone'], 'email' => $what['email']],
            'shipping_address' => $addr, 'billing_address' => $addr, 'tax_amount' => $money(0), 'shipping_amount' => $money(0),
            'merchant_url' => ['success' => $ret, 'failure' => $ret . '&x=fail', 'cancel' => $ret . '&x=cancel', 'notification' => $ret . '&x=notify'], 'is_mobile' => false]);
        if ($code >= 200 && $code < 300 && !empty($j['checkout_url']) && !empty($j['order_id'])) { owPaySetRef($pay, (string) $j['order_id']); return (string) $j['checkout_url']; }
        owPayNote($pay, 'tamara start failed: HTTP ' . $code); return null;
    }
    return null;
}
function owPaySetRef(array $pay, string $ref): void {
    getDB()->prepare('UPDATE booking_payments SET gateway_ref = :r WHERE id = :id')->execute([':r' => mb_substr($ref, 0, 120), ':id' => (int) $pay['id']]);
}

/* Back from the provider: asks the provider itself whether the full amount was paid. Returns 'paid', 'failed' or 'waiting'. */
function owPayCheck(array $pay): string {
    $pay = owPayByToken((string) $pay['token']) ?? $pay;
    if ($pay['status'] === 'paid') return 'paid';
    if ($pay['status'] !== 'created') return 'failed';
    $g = (string) $pay['gateway']; $ref = (string) $pay['gateway_ref'];
    $halalas = (int) round((float) $pay['amount'] * 100); $amt = round((float) $pay['amount'], 2);
    $ok = false; $fail = false;
    if ($g === 'moyasar' && $ref !== '') {
        [$code, $j] = owPayHttp('GET', OW_MOYASAR_HOST . '/invoices/' . rawurlencode($ref), [], null, owKey('OWEILI_MOYASAR_SK'));
        $ok = $code === 200 && ($j['status'] ?? '') === 'paid' && (int) ($j['amount'] ?? 0) === $halalas && ($j['currency'] ?? '') === 'SAR';
        $fail = $code === 200 && in_array($j['status'] ?? '', ['failed', 'canceled', 'expired'], true);
    } elseif ($g === 'tabby' && $ref !== '') {
        [$code, $j] = owPayHttp('GET', OW_TABBY_HOST . '/api/v2/payments/' . rawurlencode($ref), ['Authorization: Bearer ' . owKey('OWEILI_TABBY_SK')]);
        $st = strtoupper((string) ($j['status'] ?? ''));
        if ($code === 200 && round((float) ($j['amount'] ?? 0), 2) === $amt && ($j['currency'] ?? '') === 'SAR') {
            if ($st === 'AUTHORIZED') {   // capture the full amount, as Tabby asks
                [$cc] = owPayHttp('POST', OW_TABBY_HOST . '/api/v2/payments/' . rawurlencode($ref) . '/captures', ['Authorization: Bearer ' . owKey('OWEILI_TABBY_SK')], ['amount' => number_format($amt, 2, '.', '')]);
                $ok = $cc >= 200 && $cc < 300;
            } elseif ($st === 'CLOSED') $ok = true;
        }
        $fail = in_array($st, ['REJECTED', 'EXPIRED'], true);
    } elseif ($g === 'tamara' && $ref !== '') {
        $auth = ['Authorization: Bearer ' . owKey('OWEILI_TAMARA_TOKEN')];
        [$code, $j] = owPayHttp('GET', OW_TAMARA_HOST . '/orders/' . rawurlencode($ref), $auth);
        $st = strtolower((string) ($j['status'] ?? ''));
        if ($code === 200 && round((float) ($j['total_amount']['amount'] ?? 0), 2) === $amt) {
            if ($st === 'approved') { [$ac] = owPayHttp('POST', OW_TAMARA_HOST . '/orders/' . rawurlencode($ref) . '/authorise', $auth, []); $st = $ac >= 200 && $ac < 300 ? 'authorised' : $st; }
            if ($st === 'authorised') {
                [$cc] = owPayHttp('POST', OW_TAMARA_HOST . '/payments/capture', $auth, ['order_id' => $ref, 'total_amount' => ['amount' => $amt, 'currency' => 'SAR'], 'shipping_info' => ['shipped_at' => gmdate('c'), 'shipping_company' => 'None (digital)']]);
                $ok = $cc >= 200 && $cc < 300;
            } elseif ($st === 'fully_captured') $ok = true;
        }
        $fail = in_array($st, ['declined', 'expired', 'canceled'], true);
    }
    if ($ok) return owBookingPaid($pay) ? 'paid' : 'failed';
    if ($fail) { owPayFail($pay, $g . ' said: not paid'); return 'failed'; }
    return 'waiting';
}
/* The test page: "Pay (test)" */
function owPayTestApprove(array $pay): bool {
    if ($pay['gateway'] !== 'test' || $pay['status'] !== 'created' || !owIsAdmin()) return false;   // admin only
    owPaySetRef($pay, 'TEST-' . strtoupper(bin2hex(random_bytes(4))));
    return owBookingPaid($pay);
}
function owPayFail(array $pay, string $note): void {
    getDB()->prepare("UPDATE booking_payments SET status = 'failed', note = :n WHERE id = :id AND status = 'created'")->execute([':n' => mb_substr($note, 0, 500), ':id' => (int) $pay['id']]);
}
/* Refund the whole amount. Test payments: nothing to call. */
function owPayRefund(array $pay): bool {
    if ($pay['status'] === 'refunded') return true;
    if ($pay['status'] !== 'paid') return false;
    $g = (string) $pay['gateway']; $ref = (string) $pay['gateway_ref']; $amt = round((float) $pay['amount'], 2); $ok = false;
    if ($g === 'test') $ok = true;
    elseif ($g === 'moyasar') {
        [$code, $inv] = owPayHttp('GET', OW_MOYASAR_HOST . '/invoices/' . rawurlencode($ref), [], null, owKey('OWEILI_MOYASAR_SK'));
        $pid = '';
        foreach (($inv['payments'] ?? []) as $p) if (($p['status'] ?? '') === 'paid') $pid = (string) $p['id'];
        if ($pid !== '') { [$rc] = owPayHttp('POST', OW_MOYASAR_HOST . '/payments/' . rawurlencode($pid) . '/refund', [], ['amount' => (int) round($amt * 100)], owKey('OWEILI_MOYASAR_SK')); $ok = $rc >= 200 && $rc < 300; }
    } elseif ($g === 'tabby') {
        [$rc] = owPayHttp('POST', OW_TABBY_HOST . '/api/v2/payments/' . rawurlencode($ref) . '/refunds', ['Authorization: Bearer ' . owKey('OWEILI_TABBY_SK')], ['amount' => number_format($amt, 2, '.', '')]);
        $ok = $rc >= 200 && $rc < 300;
    } elseif ($g === 'tamara') {
        [$rc] = owPayHttp('POST', OW_TAMARA_HOST . '/payments/simplified-refund/' . rawurlencode($ref), ['Authorization: Bearer ' . owKey('OWEILI_TAMARA_TOKEN')], ['total_amount' => ['amount' => $amt, 'currency' => 'SAR'], 'comment' => 'Booking cancelled']);
        $ok = $rc >= 200 && $rc < 300;
    }
    if ($ok) getDB()->prepare("UPDATE booking_payments SET status = 'refunded', refunded_at = NOW() WHERE id = :id")->execute([':id' => (int) $pay['id']]);
    else owPayNote($pay, $g . ' refund failed');
    return $ok;
}
