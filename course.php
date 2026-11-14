<?php
/* =====================================================================
 *  course.php?id=N — one bookable course: everything about it, and
 *  "Register and pay". HIDDEN until the bookings switch is on (booking_lib.php).
 *  The meeting link of an online course shows only to registered students.
 *  Tables: courses, institutes, course_registrations, booking_payments
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/booking_lib.php';
$preview = owBookingGate();
owBookingCleanup();

$c = owCourseGet((int) ($_GET['id'] ?? 0));
$me = isLoggedIn() ? currentUser() : null;
$isStaff = owIsAdmin();
$isOwner = $c && $me && (int) $c['inst_user'] === (int) $me['id'];
if (!$c || (int) $c['bookable'] !== 1 || (!owCoursePublic($c) && !$isStaff && !$isOwner)) { http_response_code(404); require __DIR__ . '/404.php'; exit; }

$myReg = null;
if ($me) { $st = getDB()->prepare('SELECT * FROM course_registrations WHERE course_id = :c AND user_id = :u'); $st->execute([':c' => (int) $c['id'], ':u' => (int) $me['id']]); $myReg = $st->fetch() ?: null; }
$err = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!$me) { header('Location: login.php?next=' . rawurlencode('course.php?id=' . (int) $c['id'])); exit; }
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) $err = ['The page was open too long. Please try again.', 'انتهت صلاحية الصفحة. حاول مرة أخرى.'];
    elseif (($me['role'] ?? '') !== 'collector') $err = ['Please sign in with a collector account to register.', 'يرجى الدخول بحساب مقتنٍ للتسجيل.'];
    elseif (empty($_POST['agree'])) $err = ['Please tick that you read the booking rules.', 'يرجى التأشير على أنك قرأت قواعد الحجز.'];
    else {
        $name = mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 150);
        $phone = trim((string) ($_POST['phone'] ?? ''));
        if (owNameProblem($name, true) !== '') $err = ['Please write the student’s name in letters.', 'يرجى كتابة اسم الطالب بالحروف.'];
        elseif (!preg_match('/^[+0-9][0-9 \-()]{6,29}$/', $phone)) $err = ['Please enter a valid mobile number.', 'يرجى إدخال رقم جوال صالح.'];
        else {
            [$rid, $err] = owCourseHold((int) $c['id'], $me, $name, (string) $me['email'], $phone);
            if (!$err) {
                if ((float) $c['price'] <= 0) { owCourseConfirmFree($rid); header('Location: my_bookings.php?done=1'); exit; }
                require_once __DIR__ . '/pay_lib.php';
                $pay = owPayNew('course', $rid, (int) $me['id'], (float) $c['price']);
                header('Location: pay.php?t=' . $pay['token']); exit;
            }
        }
    }
}

$tEn = $c['title_en']; $tAr = $c['title_ar'];
[$dEn, $dAr] = owDateRangeBi($c['start_date'], $c['end_date']);
[$aEn, $aAr] = owCourseAgeBi($c);
$type = OW_COURSE_TYPES[$c['course_type']] ?? OW_COURSE_TYPES['in_person'];
$lvl = OW_COURSE_LEVELS[$c['level']] ?? OW_COURSE_LEVELS['beginner'];
$lng = OW_COURSE_LANGS[$c['course_lang']] ?? OW_COURSE_LANGS['ar'];
$registered = $myReg && $myReg['status'] === 'confirmed';
$price = (float) $c['price'];
$hours = $c['total_hours'] !== null ? rtrim(rtrim(number_format((float) $c['total_hours'], 1), '0'), '.') : '';

owBookTop($tEn, $tAr, 'course.php?id=' . (int) $c['id'], $preview, '
.c-head{display:flex;gap:var(--space-sm);align-items:center;}
.c-head img{width:56px;height:56px;object-fit:contain;border-radius:12px;background:#fff;border:1px solid var(--color-border);}
.c-price{font-size:28px;font-weight:800;color:var(--color-dark);}
.c-learn{white-space:pre-line;}
', !owBookingsOn() || !owCoursePublic($c));
?>
<?php if (!owCoursePublic($c)): ?><div class="alert alert-error" role="status"><?= owBi('Only you and the Oweili team see this page: the course is ' . strtolower(OW_COURSE_STATUS[$c['status']][0] ?? $c['status']) . '.', 'هذه الصفحة تظهر لك ولفريق أويلي فقط: الدورة ' . (OW_COURSE_STATUS[$c['status']][1] ?? $c['status']) . '.') ?></div><?php endif; ?>
<div class="card">
  <div class="c-head">
    <?php if ($c['inst_logo'] !== '' && owSafeLogo((string) $c['inst_logo'])): ?><img src="<?= e($c['inst_logo']) ?>" alt="<?= e('Logo of ' . $c['inst_en'] . ' · شعار ' . $c['inst_ar']) ?>"><?php endif; ?>
    <div><p class="mini" style="margin:0"><?= owBi($c['inst_en'], $c['inst_ar']) ?> · <?= owBi($c['inst_city_en'], $c['inst_city_ar']) ?></p>
      <h1><?= owBi($tEn, $tAr) ?></h1></div>
  </div>
  <p><?= owBi((string) $c['description_en'], (string) $c['description_ar']) ?></p>
  <dl class="kv">
    <dt><?= owBi('Type', 'النوع') ?></dt><dd><?= owBi($type[0], $type[1]) ?></dd>
    <dt><?= owBi('Level', 'المستوى') ?></dt><dd><?= owBi($lvl[0], $lvl[1]) ?></dd>
    <dt><?= owBi('Dates', 'التواريخ') ?></dt><dd><?= owBi($dEn, $dAr) ?></dd>
    <dt><?= owBi('Days and times', 'الأيام والأوقات') ?></dt><dd><?= owBi((string) $c['schedule_en'], (string) $c['schedule_ar']) ?></dd>
    <?php if ($hours !== ''): ?><dt><?= owBi('Total hours', 'إجمالي الساعات') ?></dt><dd><?= owBi($hours . ' hours', $hours . ' ساعة') ?></dd><?php endif; ?>
    <dt><?= owBi('Language', 'اللغة') ?></dt><dd><?= owBi($lng[0], $lng[1]) ?></dd>
    <dt><?= owBi('Suitable for', 'مناسبة لـ') ?></dt><dd><?= owBi($aEn, $aAr) ?></dd>
    <dt><?= owBi('Materials', 'المواد') ?></dt><dd><?= (int) $c['materials_included'] ? owBi('Included in the price', 'مشمولة في السعر') : owBi('Not included: bring your own', 'غير مشمولة: أحضر موادك') ?></dd>
    <?php if ($c['course_type'] === 'in_person'): ?><dt><?= owBi('Place', 'المكان') ?></dt><dd><?= owBi((string) $c['location_en'], (string) $c['location_ar']) ?></dd>
    <?php else: ?><dt><?= owBi('Meeting link', 'رابط الاجتماع') ?></dt><dd><?= $registered || $isStaff || $isOwner ? '<a href="' . e($c['meeting_link']) . '" target="_blank" rel="noopener" dir="ltr">' . e($c['meeting_link']) . '</a>' : owBi('Shown here and in your email once you are registered.', 'يظهر هنا وفي بريدك بعد تسجيلك.') ?></dd><?php endif; ?>
    <dt><?= owBi('Seats', 'المقاعد') ?></dt><dd><?= (int) $c['seats_remaining'] > 0 ? owBi((int) $c['seats_remaining'] . ' of ' . (int) $c['seats_total'] . ' left', 'متبقٍ ' . (int) $c['seats_remaining'] . ' من ' . (int) $c['seats_total']) : owBi('Full', 'اكتمل العدد') ?></dd>
  </dl>
</div>
<div class="card"><h2><?= owBi('What you will learn', 'ماذا ستتعلم') ?></h2><p class="c-learn"><?= owBi((string) $c['learn_en'], (string) $c['learn_ar']) ?></p></div>

<div class="card" id="register">
  <div class="c-price"><?= $price > 0 ? owSar($price) : owBi('Free', 'مجاني') ?></div>
  <?php if ($price > 0): ?><p class="mini"><?= owBi('Paid on Oweili. You get a confirmation email with your booking number.', 'الدفع عبر أويلي. تصلك رسالة تأكيد برقم حجزك.') ?></p><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-error" role="alert"><?= owBi($err[0], $err[1]) ?></div><?php endif; ?>
  <?php if ($registered): ?>
    <div class="alert alert-ok" role="status"><?= owBi('You are registered. Booking number ' . $myReg['ref_no'] . '.', 'أنت مسجل. رقم الحجز ' . $myReg['ref_no'] . '.') ?></div>
    <a class="btn btn-secondary" href="my_bookings.php"><?= owBi('My bookings', 'حجوزاتي') ?></a>
  <?php elseif (!owCourseOpen($c)): ?>
    <p><?= (int) $c['seats_remaining'] < 1 ? owBi('This course is full.', 'اكتمل العدد في هذه الدورة.') : owBi('Registration is closed.', 'التسجيل مغلق.') ?></p>
  <?php elseif (!$me): ?>
    <a class="btn btn-primary" href="login.php?next=<?= e(rawurlencode('course.php?id=' . (int) $c['id'] . '#register')) ?>"><?= owBi('Sign in to register', 'سجّل الدخول للتسجيل') ?></a>
    <p class="mini"><?= owBi('New here?', 'جديد هنا؟') ?> <a href="register_collector.php"><?= owBi('Create a free account', 'أنشئ حساباً مجانياً') ?></a></p>
  <?php else: ?>
    <form method="post" action="course.php?id=<?= (int) $c['id'] ?>#register"><?= csrfField() ?>
      <div class="g2">
        <div class="field"><label for="st-name"><?= owBi('Student’s full name', 'اسم الطالب كاملاً') ?></label><input id="st-name" name="name" required maxlength="150" dir="auto" value="<?= e($_POST['name'] ?? owRealName($me['full_name_en'] ?? '')) ?>"></div>
        <div class="field"><label for="st-phone"><?= owBi('Mobile number', 'رقم الجوال') ?></label><input id="st-phone" name="phone" type="tel" required maxlength="30" dir="ltr" value="<?= e($_POST['phone'] ?? ($me['phone'] ?? '')) ?>"></div>
      </div>
      <p class="mini"><?= owBi('The confirmation goes to ' . $me['email'] . '.', 'يصل التأكيد إلى ' . $me['email'] . '.') ?></p>
      <details style="margin:var(--space-xs) 0"><summary><?= owBi('Booking and refund rules', 'قواعد الحجز والاسترداد') ?></summary><?= owBookingTermsHtml() ?></details>
      <label class="chk" style="display:flex;gap:6px;align-items:flex-start;margin:var(--space-sm) 0"><input type="checkbox" name="agree" value="1" required> <span><?= owBi('I read the booking and refund rules.', 'قرأت قواعد الحجز والاسترداد.') ?></span></label>
      <button class="btn btn-primary" type="submit"><?= $price > 0 ? owBi('Register and pay', 'سجّل وادفع') : owBi('Register', 'سجّل') ?></button>
    </form>
  <?php endif; ?>
</div>
<?php owBookBottom();
