<?php
/* =====================================================================
 *  pay.php?t=TOKEN — choose how to pay for a course seat or museum tickets,
 *  then go to the provider. TEST MODE ONLY (pay_lib.php).
 *  Only the person who made the booking can open it.
 *  Tables: booking_payments, course_registrations, museum_tickets
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/pay_lib.php';
$preview = owBookingGate();
owBookingCleanup();
if (!isLoggedIn()) { header('Location: login.php'); exit; }
$me = currentUser();
$pay = owPayByToken((string) ($_GET['t'] ?? ''));
if (!$pay || !$me || (int) $pay['user_id'] !== (int) $me['id']) { http_response_code(404); require __DIR__ . '/404.php'; exit; }
if ($pay['status'] === 'paid') { header('Location: pay_return.php?t=' . $pay['token']); exit; }
$what = owPayWhat($pay);
$err = null;
if (!$what || !$what['ok'] || $pay['status'] !== 'created') $err = ['This booking is no longer waiting for payment. Please start again.', 'لم يعد هذا الحجز بانتظار الدفع. يرجى البدء من جديد.'];
elseif (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) $err = ['The page was open too long. Please try again.', 'انتهت صلاحية الصفحة. حاول مرة أخرى.'];
    else {
        $url = owPayStart($pay, (string) ($_POST['g'] ?? ''));
        if ($url) { header('Location: ' . $url); exit; }
        $err = ['This payment way did not answer. Please choose another one or try again.', 'لم تستجب وسيلة الدفع هذه. اختر وسيلة أخرى أو حاول مرة أخرى.'];
    }
}
$hold = $what['row']['hold_until'] ?? null;
owBookTop('Payment', 'الدفع', 'pay.php', $preview, '
.g-list{display:grid;gap:var(--space-xs);margin:var(--space-sm) 0;}
.g-opt{display:flex;gap:10px;align-items:center;padding:12px 14px;border:1px solid var(--color-border-strong);border-radius:12px;cursor:pointer;font-weight:600;}
.g-opt:has(input:checked){border-color:var(--color-accent);box-shadow:0 0 0 2px rgba(var(--rgb-accent),.18);}
.test-tag{display:inline-block;font-size:var(--fs-min);font-weight:800;letter-spacing:.06em;padding:2px 8px;border-radius:6px;background:rgba(var(--rgb-primary),.2);}
');
?>
<div class="card">
  <span class="test-tag"><?= owBi('TEST MODE: no real money', 'وضع تجريبي: بلا مال حقيقي') ?></span>
  <h1><?= owBi('Payment', 'الدفع') ?></h1>
  <?php if ($what): ?>
  <dl class="kv">
    <dt><?= owBi('For', 'مقابل') ?></dt><dd><?= owBi($what['title_en'], $what['title_ar']) ?></dd>
    <dt><?= owBi('Booking number', 'رقم الحجز') ?></dt><dd dir="ltr"><?= e($what['row']['ref_no']) ?></dd>
    <dt><?= owBi('Total', 'الإجمالي') ?></dt><dd><b><?= owSar((float) $pay['amount']) ?></b></dd>
  </dl>
  <?php endif; ?>
  <?php if ($err): ?><div class="alert alert-error" role="alert" style="margin-top:var(--space-sm)"><?= owBi($err[0], $err[1]) ?></div><?php endif; ?>
  <?php if ($what && $what['ok'] && $pay['status'] === 'created'): ?>
  <?php if ($hold): ?><p class="mini"><?= owBi('Your place is held for ' . OW_BOOK_HOLD_MIN . ' minutes while you pay.', 'نحجز مكانك ' . OW_BOOK_HOLD_MIN . ' دقيقة أثناء الدفع.') ?></p><?php endif; ?>
  <?php if (!owPayGateways()): /* no way to pay yet: the test payment is for the admin only (owner, 14 Nov 2026) */ ?>
  <p><?= owBi('Online payment is not open yet. Please try again later.', 'الدفع الإلكتروني غير متاح بعد. يرجى المحاولة لاحقاً.') ?></p>
  <?php else: ?>
  <form method="post" action="pay.php?t=<?= e($pay['token']) ?>"><?= csrfField() ?>
    <div class="g-list" role="radiogroup">
      <?php foreach (owPayGateways() as $i => $g): ?>
      <label class="g-opt"><input type="radio" name="g" value="<?= e($g) ?>"<?= $i === 0 ? ' checked' : '' ?>> <?= owBi(OW_PAY_NAMES[$g][0], OW_PAY_NAMES[$g][1]) ?></label>
      <?php endforeach; ?>
    </div>
    <button class="btn btn-primary" type="submit"><?= owBi('Pay ' . number_format((float) $pay['amount'], 2) . ' SAR', 'ادفع ' . number_format((float) $pay['amount'], 2) . ' ر.س') ?></button>
  </form>
  <?php endif; ?>
  <?php else: ?><a class="btn btn-secondary" href="<?= e($what['back'] ?? 'art_map.php') ?>"><?= owBi('Back', 'رجوع') ?></a><?php endif; ?>
</div>
<?php owBookBottom();
