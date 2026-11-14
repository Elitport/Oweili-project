<?php
/* =====================================================================
 *  ticket_check.php?k=TOKEN — what the QR code opens. Museum staff (the
 *  museum's own account) and the Oweili admin see whether the ticket is
 *  valid and can mark it used, once. Anyone else is asked to show the code
 *  at the entrance; nothing about the buyer is shown to them.
 *  Tables: museum_tickets, art_places, activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/booking_lib.php';
$preview = owBookingGate();
$k = (string) ($_GET['k'] ?? ($_POST['k'] ?? ''));
$code = strtoupper(trim((string) ($_GET['code'] ?? '')));
$me = isLoggedIn() ? currentUser() : null;
$myPlace = $me && ($me['role'] ?? '') === 'museum' ? owMyMuseum((int) $me['id']) : null;
$admin = owIsAdmin();
$t = null;
if (preg_match('/^[a-f0-9]{40}$/', $k)) { $st = getDB()->prepare('SELECT * FROM museum_tickets WHERE qr_token = :k'); $st->execute([':k' => $k]); $t = $st->fetch() ?: null; }
elseif ($code !== '' && ($myPlace || $admin) && preg_match('/^T-\d{2}-[A-Z0-9]{6}$/', $code)) { $st = getDB()->prepare('SELECT * FROM museum_tickets WHERE ref_no = :r'); $st->execute([':r' => $code]); $t = $st->fetch() ?: null; }
$canCheck = $t && ($admin || ($myPlace && (int) $myPlace['id'] === (int) $t['place_id']));
$msg = null;
if ($canCheck && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && verifyCsrf($_POST['csrf_token'] ?? null) && ($_POST['do'] ?? '') === 'use') {
    $u = getDB()->prepare("UPDATE museum_tickets SET status = 'used', used_at = NOW(), used_by = :by WHERE id = :id AND status = 'paid'");
    $u->execute([':by' => $me['id'] ?? null, ':id' => (int) $t['id']]);
    if ($u->rowCount()) { owLog('ticket_used', 'museum_ticket', (int) $t['id'], (string) $t['ref_no'], $me ? ['type' => owActorType($me['role']), 'id' => (int) $me['id']] : ['type' => 'admin', 'id' => null]); $msg = ['Marked as used. Welcome them in!', 'تم التأشير بالاستخدام. أهلاً بهم!']; }
    $st = getDB()->prepare('SELECT * FROM museum_tickets WHERE id = :id'); $st->execute([':id' => (int) $t['id']]); $t = $st->fetch();
}
header('X-Robots-Tag: noindex');
owBookTop('Ticket check', 'فحص التذكرة', 'ticket_check.php', $preview, '.ck{max-width:520px;margin:0 auto;}.ck .big{font-size:22px;font-weight:800;}', true, 'role-institute');
?>
<div class="card ck">
<?php if (!$t): ?>
  <h1><?= owBi('Ticket not found', 'التذكرة غير موجودة') ?></h1>
  <p><?= owBi('Please check the code and try again.', 'يرجى مراجعة الرمز والمحاولة مرة أخرى.') ?></p>
<?php elseif (!$canCheck): ?>
  <h1><?= owBi('Oweili ticket', 'تذكرة أويلي') ?></h1>
  <p><?= owBi('Please show this code at the museum entrance. The museum team will scan it.', 'يرجى عرض هذا الرمز عند مدخل المتحف. سيمسحه فريق المتحف.') ?></p>
  <?php if (!$me): ?><p class="mini"><?= owBi('Museum team?', 'فريق المتحف؟') ?> <a href="login.php?next=<?= e(rawurlencode('ticket_check.php?k=' . $k)) ?>"><?= owBi('Sign in to check tickets', 'سجّل الدخول لفحص التذاكر') ?></a></p><?php endif; ?>
<?php else: $p = owMuseumGet((int) $t['place_id']); [$lEn, $lAr] = owTicketLinesBi($t); [$dEn, $dAr] = owDateRangeBi($t['visit_date'], null);
  $today = $t['visit_date'] === date('Y-m-d'); ?>
  <?php if ($msg): ?><div class="alert alert-ok" role="status"><?= owBi($msg[0], $msg[1]) ?></div><?php endif; ?>
  <h1><?= owBi($p['name_en'] ?? '', $p['name_ar'] ?? '') ?></h1>
  <p class="big"><?php
    if ($t['status'] === 'paid') echo $today ? owBi('✓ Valid for today', '✓ صالحة لليوم') : owBi('Valid, but for another date', 'صالحة، لكن لتاريخ آخر');
    elseif ($t['status'] === 'used') echo owBi('Already used', 'استُخدمت من قبل');
    else echo owBi('Not valid', 'غير صالحة'); ?></p>
  <dl class="kv">
    <dt><?= owBi('Visit date', 'تاريخ الزيارة') ?></dt><dd><?= owBi($dEn, $dAr) ?></dd>
    <dt><?= owBi('Tickets', 'التذاكر') ?></dt><dd><?= owBi($lEn, $lAr) ?> (<?= owBi((int) $t['people'] . ' people', (int) $t['people'] . ' أشخاص') ?>)</dd>
    <dt><?= owBi('Name', 'الاسم') ?></dt><dd><?= e($t['buyer_name']) ?></dd>
    <dt><?= owBi('Ticket number', 'رقم التذكرة') ?></dt><dd dir="ltr"><?= e($t['ref_no']) ?></dd>
    <?php if ($t['status'] === 'used'): ?><dt><?= owBi('Used at', 'وقت الاستخدام') ?></dt><dd dir="ltr"><?= e(substr((string) $t['used_at'], 0, 16)) ?></dd><?php endif; ?>
  </dl>
  <?php if ($t['status'] === 'paid'): ?>
  <form method="post" action="ticket_check.php" class="acts"><?= csrfField() ?><input type="hidden" name="k" value="<?= e($t['qr_token']) ?>"><input type="hidden" name="do" value="use">
    <button class="btn btn-primary"><?= owBi('Mark as used', 'أشّر بالاستخدام') ?></button></form>
  <?php endif; ?>
  <?php if ($myPlace): ?><p class="mini" style="margin-top:var(--space-sm)"><a href="museum_dashboard.php?tab=scan"><?= owBi('Scan the next ticket', 'امسح التذكرة التالية') ?></a></p><?php endif; ?>
<?php endif; ?>
</div>
<?php owBookBottom();
