<?php
/* =====================================================================
 *  pay_test.php?t=TOKEN — Oweili's own TEST checkout (no real money).
 *  "Pay (test)" marks the booking paid; "Payment fails (test)" does not.
 *  Only for the person who made the booking, only for test payments.
 *  Tables: booking_payments
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/pay_lib.php';
$preview = owBookingGate();
if (!isLoggedIn()) { header('Location: login.php'); exit; }
$me = currentUser();
$pay = owPayByToken((string) ($_GET['t'] ?? ''));
if (!$pay || !$me || (int) $pay['user_id'] !== (int) $me['id'] || $pay['gateway'] !== 'test') { http_response_code(404); require __DIR__ . '/404.php'; exit; }
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && verifyCsrf($_POST['csrf_token'] ?? null) && $pay['status'] === 'created') {
    if (($_POST['do'] ?? '') === 'pay') owPayTestApprove($pay); else owPayFail($pay, 'test: payment refused');
    header('Location: pay_return.php?t=' . $pay['token'] . '&g=test'); exit;
}
owBookTop('Test payment', 'دفع تجريبي', 'pay_test.php', $preview, '.test-card{max-width:420px;margin:0 auto;text-align:center;}.test-card .acts{justify-content:center;}');
?>
<div class="card test-card">
  <h1><?= owBi('Test payment', 'دفع تجريبي') ?></h1>
  <p><?= owBi('This page is for testing. No card is asked for and no money moves.', 'هذه الصفحة للاختبار. لا تُطلب بطاقة ولا ينتقل أي مال.') ?></p>
  <p style="font-size:24px;font-weight:800"><?= owSar((float) $pay['amount']) ?></p>
  <?php if ($pay['status'] === 'created'): ?>
  <div class="acts">
    <form method="post"><?= csrfField() ?><input type="hidden" name="do" value="pay"><button class="btn btn-primary"><?= owBi('Pay (test)', 'ادفع (تجريبي)') ?></button></form>
    <form method="post"><?= csrfField() ?><input type="hidden" name="do" value="fail"><button class="btn btn-secondary"><?= owBi('Payment fails (test)', 'فشل الدفع (تجريبي)') ?></button></form>
  </div>
  <?php else: ?><a class="btn btn-secondary" href="pay_return.php?t=<?= e($pay['token']) ?>"><?= owBi('Continue', 'متابعة') ?></a><?php endif; ?>
</div>
<?php owBookBottom();
