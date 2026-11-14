<?php
/* =====================================================================
 *  register_institute.php — an art institute asks to join Oweili.
 *  The account waits until the owner approves it (Admin → Courses and tickets).
 *  HIDDEN until the bookings switch is on (booking_lib.php).
 *  Tables: users (role institute), institutes (via_signup = 1), activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/booking_lib.php';
$preview = owBookingGate();
$err = null; $ok = false;
$v = ['name_en' => '', 'name_ar' => '', 'city_en' => '', 'city_ar' => '', 'person' => '', 'email' => '', 'phone' => '', 'website' => '', 'desc_en' => '', 'desc_ar' => ''];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    foreach ($v as $k => $_) $v[$k] = mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) ($_POST[$k] ?? ''))), 0, in_array($k, ['desc_en', 'desc_ar'], true) ? 1500 : 200);
    $v['email'] = strtolower($v['email']);
    $pass = (string) ($_POST['password'] ?? ''); $confirm = (string) ($_POST['confirm'] ?? '');
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) $err = ['Security check failed. Please try again.', 'فشل التحقق الأمني. حاول مرة أخرى.'];
    elseif (($_POST['website_hp'] ?? '') !== '') $err = ['Please try again.', 'حاول مرة أخرى.'];
    elseif (owSignupLimited()) $err = ['Too many new accounts from here. Please try again in an hour.', 'حسابات جديدة كثيرة من هنا. حاول مرة أخرى بعد ساعة.'];
    elseif ($v['name_en'] === '' || $v['name_ar'] === '' || $v['city_en'] === '' || $v['city_ar'] === '' || $v['person'] === '') $err = ['Please fill in the required fields.', 'يرجى تعبئة الحقول المطلوبة.'];
    elseif (owNameProblem($v['person'], true) !== '') $err = ['Please write the contact person’s name in letters.', 'يرجى كتابة اسم المسؤول بالحروف.'];
    elseif (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $err = ['Please enter a valid email address.', 'يرجى إدخال بريد إلكتروني صالح.'];
    elseif (!preg_match('/^[+0-9][0-9 \-()]{6,29}$/', $v['phone'])) $err = ['Please enter a valid phone number.', 'يرجى إدخال رقم هاتف صالح.'];
    elseif ($v['website'] !== '' && !owSafeLink($v['website'], 'web')[1]) $err = ['Please check the website address.', 'يرجى مراجعة عنوان الموقع.'];
    elseif (!owPasswordStrong($pass)) $err = ['The password needs at least 8 characters, with a capital letter, a number and a symbol.', 'كلمة المرور تحتاج 8 أحرف على الأقل، فيها حرف كبير ورقم ورمز.'];
    elseif ($pass !== $confirm) $err = ['Passwords do not match.', 'كلمتا المرور غير متطابقتين.'];
    else {
        $st = getDB()->prepare('SELECT 1 FROM users WHERE LOWER(TRIM(email)) = :e LIMIT 1'); $st->execute([':e' => $v['email']]);
        if ($st->fetchColumn()) $err = ['An account with this email already exists.', 'يوجد حساب بهذا البريد الإلكتروني بالفعل.'];
        else {
            $db = getDB();
            $token = bin2hex(random_bytes(32));
            $lang = owIsAr() ? 'ar' : 'en';
            $withLang = owHasColumn('users', 'preferred_lang');
            $db->beginTransaction();
            try {
                $db->prepare('INSERT INTO users (email, password, full_name_en, full_name_ar, phone, role, is_verified, is_approved, verification_token, verification_expires' . ($withLang ? ', preferred_lang' : '') . ')
                              VALUES (:e, :p, :n, :na, :ph, "institute", 0, 0, :t, :x' . ($withLang ? ', :l' : '') . ')')
                   ->execute([':e' => $v['email'], ':p' => hashPassword($pass), ':n' => $v['person'], ':na' => '', ':ph' => $v['phone'], ':t' => $token,
                              ':x' => date('Y-m-d H:i:s', time() + 48 * 3600)] + ($withLang ? [':l' => $lang] : []));
                $uid = (int) $db->lastInsertId();
                $db->prepare('INSERT INTO institutes (user_id, name_en, name_ar, city_en, city_ar, description_en, description_ar, contact_email, contact_phone, website_url, is_active, via_signup)
                              VALUES (:u, :ne, :na, :ce, :ca, :de, :da, :e, :ph, :w, 0, 1)')
                   ->execute([':u' => $uid, ':ne' => $v['name_en'], ':na' => $v['name_ar'], ':ce' => $v['city_en'], ':ca' => $v['city_ar'], ':de' => $v['desc_en'], ':da' => $v['desc_ar'],
                              ':e' => $v['email'], ':ph' => $v['phone'], ':w' => $v['website'] !== '' ? owSafeLink($v['website'], 'web')[0] : '']);
                $db->commit();
            } catch (Throwable $e) { $db->rollBack(); error_log('register_institute: ' . $e->getMessage()); $err = ['Something went wrong. Please try again.', 'حدث خطأ. حاول مرة أخرى.']; }
            if (!$err) {
                owSignupCounted($uid, 'institute');
                require_once __DIR__ . '/alert_lib.php';
                owAdminAlert('booking', 'New institute sign-up: ' . $v['name_en'], 'معهد جديد: ' . $v['name_ar'], 'admin_bookings.php#institutes');
                owSendMail($v['email'], 'Confirm your email · تأكيد بريدك الإلكتروني', owVerifyEmailBody(owSiteBase() . '/verify.php?token=' . $token));
                $ok = true;
            }
        }
    }
}
owBookTop('Join as an art institute', 'انضم كمعهد فني', 'register_institute.php', $preview, '', !owBookingsOn(), 'role-institute');
?>
<div class="card">
  <h1><?= owBi('Join as an art institute', 'انضم كمعهد فني') ?></h1>
  <p><?= owBi('List your courses on Oweili. Students register and pay here, and you follow everything from your own dashboard. Oweili keeps ' . rtrim(rtrim(number_format(owCourseCommission(), 2), '0'), '.') . '% of each registration, before VAT. You are paid once the course has started.',
              'اعرض دوراتك على أويلي. يسجّل الطلاب ويدفعون هنا، وتتابع كل شيء من لوحتك الخاصة. يحتفظ أويلي بـ ' . rtrim(rtrim(number_format(owCourseCommission(), 2), '0'), '.') . '٪ من كل تسجيل، قبل الضريبة. وتُدفع مستحقاتك بعد بداية الدورة.') ?></p>
  <p class="mini"><?= owBi('Every institute is reviewed by the Oweili team before it shows.', 'يراجع فريق أويلي كل معهد قبل ظهوره.') ?></p>
</div>
<?php if ($ok): ?>
  <div class="card"><div class="alert alert-ok" role="status"><?= owBi('Thank you! Please confirm your email. We will review your institute and let you know.', 'شكراً لك! يرجى تأكيد بريدك. سنراجع معهدك ونبلغك.') ?></div></div>
<?php else: ?>
<?php if ($err): ?><div class="alert alert-error" role="alert"><?= owBi($err[0], $err[1]) ?></div><?php endif; ?>
<form method="post" action="register_institute.php" class="card"><?= csrfField() ?>
  <div style="position:absolute;left:-9999px" aria-hidden="true"><input name="website_hp" tabindex="-1" autocomplete="off"></div>
  <div class="g2">
    <div class="field"><label for="ri-ne"><?= owBi('Institute name (English)', 'اسم المعهد (بالإنجليزية)') ?></label><input id="ri-ne" name="name_en" required maxlength="200" dir="ltr" value="<?= e($v['name_en']) ?>"></div>
    <div class="field"><label for="ri-na"><?= owBi('Institute name (Arabic)', 'اسم المعهد (بالعربية)') ?></label><input id="ri-na" name="name_ar" required maxlength="200" dir="rtl" value="<?= e($v['name_ar']) ?>"></div>
    <div class="field"><label for="ri-ce"><?= owBi('City (English)', 'المدينة (بالإنجليزية)') ?></label><input id="ri-ce" name="city_en" required maxlength="120" dir="ltr" value="<?= e($v['city_en']) ?>"></div>
    <div class="field"><label for="ri-ca"><?= owBi('City (Arabic)', 'المدينة (بالعربية)') ?></label><input id="ri-ca" name="city_ar" required maxlength="120" dir="rtl" value="<?= e($v['city_ar']) ?>"></div>
    <div class="field"><label for="ri-de"><?= owBi('Short description (English, optional)', 'وصف قصير (بالإنجليزية، اختياري)') ?></label><textarea id="ri-de" name="desc_en" rows="3" maxlength="1500" dir="ltr"><?= e($v['desc_en']) ?></textarea></div>
    <div class="field"><label for="ri-da"><?= owBi('Short description (Arabic, optional)', 'وصف قصير (بالعربية، اختياري)') ?></label><textarea id="ri-da" name="desc_ar" rows="3" maxlength="1500" dir="rtl"><?= e($v['desc_ar']) ?></textarea></div>
    <div class="field"><label for="ri-p"><?= owBi('Contact person', 'اسم المسؤول') ?></label><input id="ri-p" name="person" required maxlength="150" dir="auto" value="<?= e($v['person']) ?>"></div>
    <div class="field"><label for="ri-w"><?= owBi('Website (optional)', 'الموقع (اختياري)') ?></label><input id="ri-w" name="website" maxlength="200" dir="ltr" value="<?= e($v['website']) ?>"></div>
    <div class="field"><label for="ri-e"><?= owBi('Email (for signing in)', 'البريد الإلكتروني (لتسجيل الدخول)') ?></label><input id="ri-e" name="email" type="email" required maxlength="190" dir="ltr" value="<?= e($v['email']) ?>"></div>
    <div class="field"><label for="ri-ph"><?= owBi('Phone', 'الهاتف') ?></label><input id="ri-ph" name="phone" type="tel" required maxlength="30" dir="ltr" value="<?= e($v['phone']) ?>"></div>
    <div class="field"><label for="ri-pw"><?= owBi('Password', 'كلمة المرور') ?></label><input id="ri-pw" name="password" type="password" required minlength="8" autocomplete="new-password"></div>
    <div class="field"><label for="ri-c"><?= owBi('Confirm password', 'تأكيد كلمة المرور') ?></label><input id="ri-c" name="confirm" type="password" required minlength="8" autocomplete="new-password"></div>
  </div>
  <p class="mini"><?= owBi('By joining you agree to the', 'بالانضمام توافق على') ?> <a href="terms.php"><?= owBi('Terms of Use', 'شروط الاستخدام') ?></a> <?= owBi('and the', 'و') ?> <a href="privacy.php"><?= owBi('Privacy Policy', 'سياسة الخصوصية') ?></a>.</p>
  <button class="btn btn-primary" type="submit"><?= owBi('Send for review', 'أرسل للمراجعة') ?></button>
</form>
<?php endif; ?>
<?php owBookBottom();
