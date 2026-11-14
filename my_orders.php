<?php
/* =====================================================================
 *  my_orders.php — a signed-in buyer's orders, with "I received the artwork" (1 to 3 private
 *  photos + "arrived in good condition"; sends the artist's payout to the owner for approval)
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
$uid = (int) $me['id']; $msg = null; $msgBad = false;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && verifyCsrf($_POST['csrf_token'] ?? null)) {
    $id = (int) ($_POST['id'] ?? 0);
    if (($_POST['do'] ?? '') === 'confirm') {   // "I received the artwork": 1 to 3 private photos + "arrived in good condition" (owner, 14 Nov 2026)
        $err = owOrderConfirm($id, $uid, $_FILES['photos'] ?? [], !empty($_POST['good']), !empty($_POST['share']));
        $msg = match ($err) {
            ''           => ['Thank you for confirming. The Oweili team will now pay the artist.', 'شكراً لتأكيدك. سيدفع فريق أويلي للفنان الآن.'],
            'condition'  => ['Please tick that the artwork arrived in good condition. If it did not, use "Something is wrong".', 'يرجى التأشير على أن العمل وصل بحالة سليمة. وإن لم يصل كذلك، استخدم «هناك مشكلة».'],
            'photo_none' => ['Please add at least one photo of the artwork.', 'يرجى إضافة صورة واحدة للعمل على الأقل.'],
            'photo_many' => ['Please add at most 3 photos.', 'يرجى إضافة 3 صور كحد أقصى.'],
            'photo_type' => ['Please use JPG, PNG or WebP photos.', 'يرجى استخدام صور JPG أو PNG أو WebP.'],
            'photo_size' => ['Each photo can be up to 10 MB.', 'حجم كل صورة حتى 10 ميغابايت.'],
            default      => ['Something went wrong. Please try again.', 'حدث خطأ. يرجى المحاولة مرة أخرى.'],
        };
        $msgBad = $err !== '';
    }
    if (($_POST['do'] ?? '') === 'complain') $msg = owOrderComplain($id, $uid, (string) ($_POST['reason'] ?? '')) ? ['Sent. The Oweili team will contact you, and the money stays held until it is solved.', 'تم الإرسال. سيتواصل معك فريق أويلي، ويبقى المبلغ محفوظاً حتى تُحل المشكلة.'] : ['Please describe the problem.', 'يرجى وصف المشكلة.'];
}
$st = getDB()->prepare('SELECT * FROM orders WHERE buyer_user_id = :u ORDER BY id DESC LIMIT 100'); $st->execute([':u' => $uid]); $orders = $st->fetchAll();
?>
<!DOCTYPE html>
<html <?php require_once __DIR__ . '/lang_lib.php'; owHtmlAttrs(); ?>><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="robots" content="noindex">
<title>My orders · طلباتي · Oweili</title>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>.page{position:relative;z-index:10;}.o-card{padding:var(--space-md);margin-top:var(--space-sm);}.o-card h2{font-size:var(--fs-body);margin:0 0 4px;}.acts{display:flex;gap:var(--space-xs);flex-wrap:wrap;margin-top:var(--space-sm);}.acts form{margin:0;}.chk{display:flex;gap:6px;align-items:flex-start;margin:0 0 var(--space-xs);font-size:15px;}details.rcv form{margin-top:var(--space-sm);}.pillx{font-size:var(--fs-min);font-weight:700;padding:2px 10px;border-radius:var(--radius-pill);border:1px solid var(--color-border-strong);}</style>
</head><body>
<div class="bg-wrap"></div><div class="overlay"></div>
<?php include __DIR__ . '/nav.php'; ?>
<main class="page dash role-collector dash-wrap">
  <div class="card o-card"><h1 style="font-size:var(--fs-section);margin:0"><?= owBi('My orders', 'طلباتي') ?></h1>
    <p class="mini"><?= owBi('Your money is held safely by Oweili until you receive your order.', 'يحتفظ أويلي بمالك بأمان حتى تستلم طلبك.') ?></p></div>
  <?php if ($msg): ?><div class="alert <?= $msgBad ? 'alert-error' : 'alert-ok' ?>" role="status" style="margin-top:var(--space-sm)"><?= owBi($msg[0], $msg[1]) ?></div><?php endif; ?>
  <?php if (!$orders): ?><div class="card o-card"><p><?= owBi('No orders yet.', 'لا طلبات بعد.') ?></p><a class="btn btn-primary" href="marketplace.php"><?= owBi('Explore Gallery', 'استكشف المعرض') ?></a></div><?php endif; ?>
  <?php foreach ($orders as $o): ?>
  <div class="card o-card"><h2><?= e($o['title']) ?> <?= owOrderPill((string) $o['status']) ?></h2>
    <p class="mini"><?= e($o['order_no']) ?> · <?= number_format((float) $o['amount'], 2) ?> <?= owBi('SAR', 'ر.س') ?></p>
    <?php if ($o['status'] === 'delivered' && $o['confirmed_at'] !== null): ?><p class="mini"><?= owBi('Thank you, you confirmed receipt.', 'شكراً، أكدت الاستلام.') ?></p><?php endif; ?>
    <?php if ($o['status'] === 'delivered' && $o['confirmed_at'] === null): ?>
    <div class="acts">
      <details class="rcv"<?= (int) ($_GET['o'] ?? 0) === (int) $o['id'] || ($msgBad && (int) ($_POST['id'] ?? 0) === (int) $o['id']) ? ' open' : '' ?>><summary class="btn btn-primary btn-sm"><?= owBi('I received the artwork', 'استلمت العمل') ?></summary>
        <form method="post" action="my_orders.php" enctype="multipart/form-data"><?= csrfField() ?><input type="hidden" name="do" value="confirm"><input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
          <div class="field"><label for="ph<?= (int) $o['id'] ?>"><?= owBi('Photos of the artwork as you received it (1 to 3)', 'صور العمل كما استلمته (من 1 إلى 3)') ?></label>
            <input id="ph<?= (int) $o['id'] ?>" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required data-max="3"></div>
          <p class="mini"><?= owBi('Photograph the artwork only, please, with no people and no personal details.', 'صوّر العمل فقط من فضلك، بدون أشخاص أو تفاصيل شخصية.') ?> <?= owBi('Only the Oweili team and the artist see these photos.', 'لا يرى هذه الصور إلا فريق أويلي والفنان.') ?></p>
          <label class="chk"><input type="checkbox" name="good" value="1" required> <?= owBi('The artwork arrived in good condition', 'وصل العمل بحالة سليمة') ?></label>
          <label class="chk"><input type="checkbox" name="share" value="1"> <?= owBi('Oweili may share my photo of the artwork', 'أوافق أن تنشر أويلي صورة العمل') ?></label>
          <button class="btn btn-primary btn-sm"><?= owBi('Send', 'أرسل') ?></button></form></details>
      <details><summary class="btn btn-danger btn-sm"><?= owBi('Something is wrong', 'هناك مشكلة') ?></summary>
        <form method="post" action="my_orders.php"><?= csrfField() ?><input type="hidden" name="do" value="complain"><input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
          <div class="field"><label for="r<?= (int) $o['id'] ?>"><?= owBi('What happened?', 'ماذا حدث؟') ?></label><textarea id="r<?= (int) $o['id'] ?>" name="reason" rows="3" required maxlength="3000" dir="auto"></textarea></div>
          <button class="btn btn-danger btn-sm"><?= owBi('Send complaint', 'أرسل الشكوى') ?></button></form></details>
    </div>
    <p class="mini"><?= owBi('Please press "I received the artwork" once it arrives safely. If something is wrong, tell us: the payment stays held until it is solved.', 'يرجى الضغط على «استلمت العمل» حين يصلك بسلام. وإن كانت هناك مشكلة فأخبرنا، ويبقى المبلغ محفوظاً حتى تُحل.') ?></p>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</main>
<?php include __DIR__ . '/footer.php'; ?>
<script>document.querySelectorAll('input[type=file][data-max]').forEach(function(i){ i.addEventListener('change', function(){ var ar = document.documentElement.dir === 'rtl';
  i.setCustomValidity(i.files.length > +i.dataset.max ? (ar ? 'يرجى اختيار 3 صور كحد أقصى.' : 'Please choose at most 3 photos.') : ''); i.reportValidity(); }); });</script>
</body></html>
