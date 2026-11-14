<?php
/* =====================================================================
 *  pay_return.php?t=TOKEN — back from the payment: asks the provider
 *  (never trusts the address alone) and shows the result.
 *  Tables: booking_payments, course_registrations, museum_tickets
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/pay_lib.php';
$preview = owBookingGate();
$pay = owPayByToken((string) ($_GET['t'] ?? ''));
if (!$pay) { http_response_code(404); require __DIR__ . '/404.php'; exit; }
$x = (string) ($_GET['x'] ?? '');
if ($x === 'notify') { owPayCheck($pay); http_response_code(200); exit; }   // the provider's own call
if (!isLoggedIn()) { header('Location: login.php?next=' . rawurlencode('pay_return.php?t=' . $pay['token'])); exit; }
$me = currentUser();
if (!$me || (int) $pay['user_id'] !== (int) $me['id']) { http_response_code(404); require __DIR__ . '/404.php'; exit; }
$state = $pay['status'] === 'paid' ? 'paid' : ($pay['status'] === 'created' && $pay['gateway'] !== 'test' && !in_array($x, ['cancel', 'fail'], true) ? owPayCheck($pay) : ($pay['status'] === 'created' ? 'waiting' : 'failed'));
if (in_array($x, ['cancel', 'fail'], true) && $state !== 'paid') { owPayFail($pay, $pay['gateway'] . ': ' . $x); $state = 'failed'; }
$pay = owPayByToken((string) $pay['token']);
$what = owPayWhat($pay);
owBookTop('Payment', 'الدفع', 'pay_return.php', $preview);
?>
<div class="card">
<?php if ($state === 'paid' && $what): $r = $what['row']; ?>
  <div class="alert alert-ok" role="status"><?= owBi('Payment received. Thank you!', 'وصلنا الدفع. شكراً لك!') ?></div>
  <h1><?= $pay['kind'] === 'course' ? owBi('You are registered', 'تم تسجيلك') : owBi('Your ticket is ready', 'تذكرتك جاهزة') ?></h1>
  <dl class="kv"><dt><?= owBi('For', 'مقابل') ?></dt><dd><?= owBi($what['title_en'], $what['title_ar']) ?></dd>
    <dt><?= owBi('Booking number', 'رقم الحجز') ?></dt><dd dir="ltr"><?= e($r['ref_no']) ?></dd>
    <dt><?= owBi('Paid', 'المدفوع') ?></dt><dd><?= owSar((float) $pay['amount']) ?></dd></dl>
  <p><?= owBi('We sent the details to ' . $what['email'] . '.', 'أرسلنا التفاصيل إلى ' . $what['email'] . '.') ?></p>
  <div class="acts">
    <?php if ($pay['kind'] === 'ticket'): ?><a class="btn btn-primary" href="ticket.php?k=<?= e($r['qr_token']) ?>"><?= owBi('Show my ticket', 'اعرض تذكرتي') ?></a><?php endif; ?>
    <a class="btn btn-secondary" href="my_bookings.php"><?= owBi('My bookings', 'حجوزاتي') ?></a></div>
<?php elseif ($state === 'waiting'): ?>
  <h1><?= owBi('Checking your payment', 'نتحقق من دفعتك') ?></h1>
  <p><?= owBi('The payment is not confirmed yet. Please wait a moment and refresh this page.', 'لم يتأكد الدفع بعد. انتظر قليلاً ثم حدّث الصفحة.') ?></p>
  <a class="btn btn-secondary" href="pay_return.php?t=<?= e($pay['token']) ?>"><?= owBi('Refresh', 'تحديث') ?></a>
<?php else: ?>
  <div class="alert alert-error" role="alert"><?= owBi('The payment did not go through. Nothing was charged.', 'لم يتم الدفع. لم يُخصم أي مبلغ.') ?></div>
  <?php if ($pay['status'] === 'paid'): ?><p><?= owBi('If money left your account, it will be refunded. Please write to us.', 'إن خُصم مبلغ من حسابك فسيُعاد إليك. يرجى مراسلتنا.') ?></p><?php endif; ?>
  <div class="acts"><a class="btn btn-primary" href="<?= e($what['back'] ?? 'art_map.php') ?>"><?= owBi('Try again', 'حاول مرة أخرى') ?></a></div>
<?php endif; ?>
</div>
<?php owBookBottom();
