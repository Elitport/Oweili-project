<?php
/* =====================================================================
 *  ticket.php?k=TOKEN — the visitor's museum ticket with its QR code.
 *  The long secret in the address is the ticket itself (sent only to the
 *  buyer). The QR code opens ticket_check.php, which only museum staff can use.
 *  Tables: museum_tickets, art_places
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/booking_lib.php';
require_once __DIR__ . '/qr_lib.php';
$preview = owBookingGate();
$k = (string) ($_GET['k'] ?? '');
$t = null;
if (preg_match('/^[a-f0-9]{40}$/', $k)) { $st = getDB()->prepare('SELECT * FROM museum_tickets WHERE qr_token = :k'); $st->execute([':k' => $k]); $t = $st->fetch() ?: null; }
if (!$t || !in_array($t['status'], ['paid', 'used', 'cancelled'], true)) { http_response_code(404); require __DIR__ . '/404.php'; exit; }
header('X-Robots-Tag: noindex');
$p = owMuseumGet((int) $t['place_id']);
[$lEn, $lAr] = owTicketLinesBi($t);
[$dEn, $dAr] = owDateRangeBi($t['visit_date'], null);
owBookTop('Your ticket', 'تذكرتك', 'ticket.php', $preview, '
.tk{max-width:460px;margin:0 auto;text-align:center;}
.tk .qr{display:inline-block;background:#fff;padding:12px;border-radius:12px;border:1px solid var(--color-border);margin:var(--space-sm) 0;}
.tk .kv{text-align:start;}
');
?>
<div class="card tk">
  <h1><?= owBi($p['name_en'] ?? '', $p['name_ar'] ?? '') ?></h1>
  <p class="mini"><?= owBi($p['city_en'] ?? '', $p['city_ar'] ?? '') ?></p>
  <p><?= owPill(OW_TICKET_STATUS, (string) $t['status']) ?></p>
  <?php if ($t['status'] === 'paid'): ?><div class="qr"><?= owQrSvg(owTicketCheckUrl($t), 240, 'Ticket QR code · رمز التذكرة') ?></div>
  <p class="mini"><?= owBi('Show this code at the entrance. It works once.', 'اعرض هذا الرمز عند المدخل. يعمل مرة واحدة.') ?></p><?php endif; ?>
  <dl class="kv">
    <dt><?= owBi('Visit date', 'تاريخ الزيارة') ?></dt><dd><?= owBi($dEn, $dAr) ?></dd>
    <dt><?= owBi('Tickets', 'التذاكر') ?></dt><dd><?= owBi($lEn, $lAr) ?></dd>
    <dt><?= owBi('Ticket number', 'رقم التذكرة') ?></dt><dd dir="ltr"><?= e($t['ref_no']) ?></dd>
    <dt><?= owBi('Name', 'الاسم') ?></dt><dd><?= e($t['buyer_name']) ?></dd>
  </dl>
  <?php if ($t['status'] === 'used'): ?><p class="mini"><?= owBi('Used on ' . substr((string) $t['used_at'], 0, 16), 'استُخدمت في ' . substr((string) $t['used_at'], 0, 16)) ?></p><?php endif; ?>
</div>
<?php owBookBottom();
