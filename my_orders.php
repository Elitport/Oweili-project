<?php
/* =====================================================================
 *  my_orders.php — a signed-in buyer's orders, with "I received it"
 *  (releases the money to the artist) and "Something is wrong" (a
 *  complaint holds the money until the admin decides).
 *  SWITCHED OFF: while payments are off (order_lib.php owPaymentsOn) this
 *  page answers 404 and nothing links to it.
 *  Tables: orders, order_complaints, order_events
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/order_lib.php';
if (!owPaymentsOn()) { http_response_code(404); include __DIR__ . '/404.php'; exit; }
if (!isLoggedIn()) { header('Location: login.php?next=my_orders.php'); exit; }
$me = currentUser();
if (!$me || ($me['role'] ?? '') !== 'collector') { header('Location: ' . dashboardUrl($me['role'] ?? '')); exit; }
$uid = (int) $me['id']; $msg = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && verifyCsrf($_POST['csrf_token'] ?? null)) {
    $id = (int) ($_POST['id'] ?? 0);
    if (($_POST['do'] ?? '') === 'confirm') $msg = owOrderConfirm($id, $uid) ? ['Thank you. The artist will now be paid.', 'شكراً لك. سيُدفع للفنان الآن.'] : null;
    if (($_POST['do'] ?? '') === 'complain') $msg = owOrderComplain($id, $uid, (string) ($_POST['reason'] ?? '')) ? ['Sent. The Oweili team will contact you, and the money stays held until it is solved.', 'تم الإرسال. سيتواصل معك فريق أويلي، ويبقى المبلغ محفوظاً حتى تُحل المشكلة.'] : ['Please describe the problem.', 'يرجى وصف المشكلة.'];
}
$st = getDB()->prepare('SELECT * FROM orders WHERE buyer_user_id = :u ORDER BY id DESC LIMIT 100'); $st->execute([':u' => $uid]); $orders = $st->fetchAll();
?>
<!DOCTYPE html>
<html <?php require_once __DIR__ . '/lang_lib.php'; owHtmlAttrs(); ?>><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="robots" content="noindex">
<title>My orders · طلباتي · Oweili</title>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>.page{position:relative;z-index:10;}.o-card{padding:var(--space-md);margin-top:var(--space-sm);}.o-card h2{font-size:var(--fs-body);margin:0 0 4px;}.acts{display:flex;gap:var(--space-xs);flex-wrap:wrap;margin-top:var(--space-sm);}.acts form{margin:0;}.pillx{font-size:var(--fs-min);font-weight:700;padding:2px 10px;border-radius:var(--radius-pill);border:1px solid var(--color-border-strong);}</style>
</head><body>
<div class="bg-wrap"></div><div class="overlay"></div>
<?php include __DIR__ . '/nav.php'; ?>
<main class="page dash role-collector dash-wrap">
  <div class="card o-card"><h1 style="font-size:var(--fs-section);margin:0"><?= owBi('My orders', 'طلباتي') ?></h1>
    <p class="mini"><?= owBi('Your money is held safely by Oweili until you receive your order.', 'يحتفظ أويلي بمالك بأمان حتى تستلم طلبك.') ?></p></div>
  <?php if ($msg): ?><div class="alert alert-ok" role="status" style="margin-top:var(--space-sm)"><?= owBi($msg[0], $msg[1]) ?></div><?php endif; ?>
  <?php if (!$orders): ?><div class="card o-card"><p><?= owBi('No orders yet.', 'لا طلبات بعد.') ?></p><a class="btn btn-primary" href="marketplace.php"><?= owBi('Explore Gallery', 'استكشف المعرض') ?></a></div><?php endif; ?>
  <?php foreach ($orders as $o): ?>
  <div class="card o-card"><h2><?= e($o['title']) ?> <?= owOrderPill((string) $o['status']) ?></h2>
    <p class="mini"><?= e($o['order_no']) ?> · <?= number_format((float) $o['amount'], 2) ?> <?= owBi('SAR', 'ر.س') ?></p>
    <?php if ($o['status'] === 'delivered'): ?>
    <div class="acts">
      <form method="post" action="my_orders.php"><?= csrfField() ?><input type="hidden" name="do" value="confirm"><input type="hidden" name="id" value="<?= (int) $o['id'] ?>"><button class="btn btn-primary btn-sm"><?= owBi('I received it', 'استلمته') ?></button></form>
      <details><summary class="btn btn-danger btn-sm"><?= owBi('Something is wrong', 'هناك مشكلة') ?></summary>
        <form method="post" action="my_orders.php"><?= csrfField() ?><input type="hidden" name="do" value="complain"><input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
          <div class="field"><label for="r<?= (int) $o['id'] ?>"><?= owBi('What happened?', 'ماذا حدث؟') ?></label><textarea id="r<?= (int) $o['id'] ?>" name="reason" rows="3" required maxlength="3000" dir="auto"></textarea></div>
          <button class="btn btn-danger btn-sm"><?= owBi('Send complaint', 'أرسل الشكوى') ?></button></form></details>
    </div>
    <p class="mini"><?= owBi('If we hear nothing within ' . OW_ESCROW_DAYS . ' days of delivery, the order is completed and the artist is paid.', 'إن لم يصلنا شيء خلال ' . OW_ESCROW_DAYS . ' أيام من التسليم، يكتمل الطلب ويُدفع للفنان.') ?></p>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</main>
<?php include __DIR__ . '/footer.php'; ?>
</body></html>
