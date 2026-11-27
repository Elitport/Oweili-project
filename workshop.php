<?php
/* =====================================================================
 *  workshop.php?id=N — one artist workshop and "Book your seat"
 *  (owner, 25 Nov 2026; workshop_lib.php). Hidden behind the switch
 *  "Artist workshops": visitors get 404 while it is off, a signed-in admin
 *  a preview. Only approved workshops of approved artists, not started yet.
 *  The booking needs no account: name, mobile, email and the rules tick;
 *  security token, hidden robot trap, 5 per visitor per hour, one booking per
 *  email per workshop. Oweili handles contact and payment.
 *  Tables: artist_workshops, workshop_bookings, users, activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/workshop_lib.php';
require_once __DIR__ . '/artwork_lib.php';
require_once __DIR__ . '/article_lib.php';
$preview = owHiddenPageGate('artist_workshops');
header('Cache-Control: no-store');
$w = owWsPublicGet((int) ($_GET['id'] ?? 0));
if (!$w) { http_response_code(404); require __DIR__ . '/404.php'; exit; }

$errors = []; $booked = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) $errors[] = ['This page was open for a long time. Please try again.', 'كانت هذه الصفحة مفتوحة لوقت طويل. حاول مرة أخرى.'];
    else { $ref = owWsBook($w, $errors); if ($ref !== '') { $_SESSION['ow_ws_booked'] = [(int) $w['id'], $ref]; header('Location: workshop.php?id=' . (int) $w['id'] . '&booked=1#book'); exit; } }
}
if (isset($_GET['booked'])) { $b = $_SESSION['ow_ws_booked'] ?? null; $booked = is_array($b) && (int) $b[0] === (int) $w['id'] ? (string) $b[1] : ''; }
$left = owWsSeatsLeft($w);
$price = (float) $w['price'];
[$dEn, $dAr] = owDateRangeBi($w['start_date'], $w['end_date']);
$lvl = OW_WS_LEVELS[$w['level']] ?? OW_WS_LEVELS['beginner'];
$hours = rtrim(rtrim(number_format((float) $w['hours'], 1), '0'), '.');
owBookTop($w['title_en'], $w['title_ar'], 'workshop.php?id=' . (int) $w['id'], $preview, '.c-price{font-size:28px;font-weight:800;color:var(--color-dark);}.c-learn{white-space:pre-line;}', true, 'role-artist');
?>
<div class="card">
  <p class="mini" style="margin:0"><?= owBi('A workshop by', 'ورشة يقدمها') ?> <a href="artist_dashboard.php?id=<?= (int) $w['artist_id'] ?>"><?= owBiName((string) $w['artist_en'], (string) $w['artist_ar']) ?></a></p>
  <h1><?= owBi((string) $w['title_en'], (string) $w['title_ar']) ?></h1>
  <dl class="kv">
    <dt><?= owBi('Where', 'أين') ?></dt><dd><?= $w['mode'] === 'online' ? owBi('Online', 'عن بعد') : owBi('In person, ', 'حضوري، ') . e((string) $w['city']) ?></dd>
    <dt><?= owBi('Level', 'المستوى') ?></dt><dd><?= owBi($lvl[0], $lvl[1]) ?></dd>
    <dt><?= owBi('Dates', 'التواريخ') ?></dt><dd><?= owBi($dEn, $dAr) ?></dd>
    <?php if ($w['schedule_en'] !== '' || $w['schedule_ar'] !== ''): ?><dt><?= owBi('Days and times', 'الأيام والأوقات') ?></dt><dd><?= owBi((string) $w['schedule_en'], (string) $w['schedule_ar']) ?></dd><?php endif; ?>
    <dt><?= owBi('Total hours', 'إجمالي الساعات') ?></dt><dd><?= owBi($hours . ' hours', $hours . ' ساعة') ?></dd>
    <dt><?= owBi('Materials', 'المواد') ?></dt><dd><?= (int) $w['materials'] ? owBi('Included in the price', 'مشمولة في السعر') : owBi('Not included: bring your own', 'غير مشمولة: أحضر موادك') ?></dd>
    <dt><?= owBi('Seats', 'المقاعد') ?></dt><dd><?= $left > 0 ? owBi($left . ' of ' . (int) $w['seats'] . ' left', 'متبق ' . $left . ' من ' . (int) $w['seats']) : owBi('Full', 'اكتمل العدد') ?></dd>
  </dl>
</div>
<div class="card"><h2><?= owBi('What you will learn', 'ماذا ستتعلم') ?></h2><p class="c-learn"><?= owBi((string) $w['desc_en'], (string) $w['desc_ar']) ?></p></div>

<div class="card" id="book">
  <?php require_once __DIR__ . '/vat_lib.php'; ?>
  <div class="c-price"><?= $price > 0 ? owSar(owGross($price)) . owVatTag() : owBi('Free', 'مجانية') ?></div>
  <?php if ($price > 0): ?><p class="mini"><?= owBi('Book your seat now. The Oweili team contacts you to arrange the payment, and your place is confirmed once it is paid', 'احجز مقعدك الآن. يتواصل معك فريق أويلي لترتيب الدفع، ويتأكد مكانك بعد الدفع') ?></p><?php endif; ?>
  <?php foreach ($errors as $er): ?><div class="alert alert-error" role="alert"><?= owBi($er[0], $er[1]) ?></div><?php endforeach; ?>
  <?php if ($booked !== null): ?>
    <div class="alert alert-ok" role="status"><?= $booked !== '' ? ($price > 0
        ? owBi('Your seat is booked. Booking number ' . $booked . '. We sent the details to your email, and the Oweili team will contact you to arrange the payment', 'تم حجز مقعدك. رقم الحجز ' . $booked . '. أرسلنا التفاصيل إلى بريدك، وسيتواصل معك فريق أويلي لترتيب الدفع')
        : owBi('You are registered. Booking number ' . $booked . '. We sent the details to your email', 'تم تسجيلك. رقم الحجز ' . $booked . '. أرسلنا التفاصيل إلى بريدك'))
        : owBi('Thank you. We received your booking', 'شكرا. وصلنا حجزك') ?></div>
    <a class="btn btn-secondary" href="workshops.php"><?= owBi('See more workshops', 'تصفّح ورشا أخرى') ?></a>
  <?php elseif ($left < 1): ?>
    <p><?= owBi('This workshop is full', 'اكتملت مقاعد هذه الورشة') ?></p>
  <?php else: ?>
    <form method="post" action="workshop.php?id=<?= (int) $w['id'] ?>#book" novalidate><?= csrfField() ?>
      <div class="g2">
        <div class="field"><label for="b-name"><?= owBi('Full name', 'الاسم كاملا') ?></label><input id="b-name" name="buyer_name" required maxlength="150" autocomplete="name" dir="auto" value="<?= e((string) ($_POST['buyer_name'] ?? '')) ?>"></div>
        <div class="field"><label for="b-phone"><?= owBi('Mobile number', 'رقم الجوال') ?></label><input id="b-phone" name="buyer_phone" type="tel" required maxlength="30" autocomplete="tel" dir="ltr" placeholder="+966 5X XXX XXXX" value="<?= e((string) ($_POST['buyer_phone'] ?? '')) ?>"></div>
      </div>
      <div class="field"><label for="b-email"><?= owBi('Email', 'البريد الإلكتروني') ?></label><input id="b-email" name="buyer_email" type="email" required maxlength="190" autocomplete="email" dir="ltr" value="<?= e((string) ($_POST['buyer_email'] ?? '')) ?>"></div>
      <div style="position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%);white-space:nowrap" aria-hidden="true"><label for="b-web">Website</label><input id="b-web" name="website" tabindex="-1" autocomplete="off"></div>
      <details style="margin:var(--space-xs) 0"><summary><?= owBi('Booking and refund rules', 'قواعد الحجز والاسترداد') ?></summary><ol class="bk-rules"><?php foreach (owBookingRules(true, false) as $r): ?><li><?= owBi($r[0], $r[1]) ?></li><?php endforeach; ?></ol></details>
      <label class="chk" style="display:flex;gap:6px;align-items:flex-start;margin:var(--space-sm) 0"><input type="checkbox" name="rules_ok" value="1" required> <span><?= owBi('I read the booking and refund rules', 'قرأت قواعد الحجز والاسترداد') ?></span></label>
      <p class="mini"><?= owBi('We use these details only for this booking. The artist sees only your first name. See our', 'نستخدم هذه البيانات لهذا الحجز فقط. لا يرى الفنان إلا اسمك الأول. اطّلع على') ?> <a href="privacy.php"><?= owBi('Privacy Policy', 'سياسة الخصوصية') ?></a></p>
      <button class="btn btn-primary" type="submit"><?= owBi('Book your seat', 'احجز مقعدك') ?></button>
    </form>
  <?php endif; ?>
</div>
<?php owBookBottom();
