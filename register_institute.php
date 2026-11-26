<?php
/* =====================================================================
 *  register_institute.php — an art institute registers ITSELF on Oweili
 *  (owner, 25 Nov 2026: institutes only join through this page; the owner
 *  never creates institute accounts). The account waits until the owner
 *  approves it (Admin → Waiting for approval, with the documents).
 *  Asks for: public names (English and Arabic), legal name, city (Saudi
 *  Arabia only for now), contact person, email, phone, password; the
 *  registration (Commercial Register or Freelance document: number and
 *  upload); the training licence (Ministry of Culture "Ebdaa" or TVTC:
 *  number and upload; optional now, needed before courses go live); logo
 *  (optional, public); a short description; a website or social link (only
 *  for the owner's review, never shown to visitors); the tick "I confirm
 *  these documents are valid and mine".
 *  Documents are private (.private/institute-docs/, institute_doc.php).
 *  HIDDEN while the courses switch is off (booking_lib.php owCoursesGate).
 *  Tables: users (role institute), institutes (via_signup = 1), activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/booking_lib.php';
require_once __DIR__ . '/inst_lib.php';
$preview = owCoursesGate();
$err = null; $ok = false; $bad = [];
$v = ['name_en' => '', 'name_ar' => '', 'legal_name' => '', 'city' => '', 'person' => '', 'email' => '', 'phone' => '', 'link' => '', 'desc_en' => '', 'desc_ar' => '',
      'reg_type' => '', 'reg_number' => '', 'licence_from' => '', 'licence_number' => ''];
$docsOn = owInstDocsReady();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    foreach ($v as $k => $_) $v[$k] = mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) ($_POST[$k] ?? ''))), 0, in_array($k, ['desc_en', 'desc_ar'], true) ? 1500 : ($k === 'link' ? 300 : 200));
    $v['email'] = strtolower($v['email']);
    $v['reg_number'] = strtoupper(preg_replace('/\s+/', '', strtr($v['reg_number'], ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'])));
    $v['licence_number'] = strtoupper(preg_replace('/\s+/', '', strtr($v['licence_number'], ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'])));
    $pass = (string) ($_POST['password'] ?? ''); $confirm = (string) ($_POST['confirm'] ?? '');
    $fail = function (array $msg, string ...$fields) use (&$err, &$bad) { if (!$err) { $err = $msg; $bad = $fields; } };
    $postTooBig = empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
    if ($postTooBig) $fail(['The files are too large together. Each document can be up to 10 MB.', 'الملفات كبيرة جدا معا. حجم كل مستند حتى 10 ميغابايت.']);
    elseif (!verifyCsrf($_POST['csrf_token'] ?? null)) $fail(['Security check failed. Please try again.', 'فشل التحقق الأمني. حاول مرة أخرى.']);
    elseif (($_POST['website_hp'] ?? '') !== '') $fail(['Please try again', 'حاول مرة أخرى']);
    elseif (owSignupLimited()) $fail(['Too many new accounts from here. Please try again in an hour.', 'حسابات جديدة كثيرة من هنا. حاول مرة أخرى بعد ساعة.']);
    if (!$err) {
        if ($v['name_en'] === '' || $v['name_ar'] === '') $fail(['Please write the institute’s name in English and Arabic', 'يرجى كتابة اسم المعهد بالإنجليزية والعربية'], 'name_en', 'name_ar');
        elseif ($v['legal_name'] === '') $fail(['Please write the legal name, as in your registration', 'يرجى كتابة الاسم النظامي كما في التسجيل'], 'legal_name');
        elseif (!isset(OW_SA_CITIES[$v['city']])) $fail(['Please choose your city', 'يرجى اختيار مدينتك'], 'city');
        elseif ($v['person'] === '' || owNameProblem($v['person'], true) !== '') $fail(['Please write the contact person’s name in letters', 'يرجى كتابة اسم المسؤول بالحروف'], 'person');
        elseif (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $fail(['Please enter a valid email address', 'يرجى إدخال بريد إلكتروني صالح'], 'email');
        elseif (!preg_match('/^[+0-9][0-9 \-()]{6,29}$/', $v['phone'])) $fail(['Please enter a valid phone number', 'يرجى إدخال رقم هاتف صالح'], 'phone');
        elseif (!isset(OW_INST_REG_TYPES[$v['reg_type']])) $fail(['Please choose the type of registration', 'يرجى اختيار نوع التسجيل'], 'reg_type');
        elseif ($v['reg_type'] === 'cr' && !preg_match('/^[0-9]{10}$/', $v['reg_number'])) $fail(['The Commercial Register number has 10 digits', 'رقم السجل التجاري من 10 أرقام'], 'reg_number');
        elseif ($v['reg_type'] === 'freelance' && !preg_match('/^[A-Z0-9\-]{5,30}$/', $v['reg_number'])) $fail(['Please write the Freelance document number', 'يرجى كتابة رقم وثيقة العمل الحر'], 'reg_number');
        elseif ($v['licence_from'] !== '' && !isset(OW_INST_LICENCES[$v['licence_from']])) $fail(['Please choose who gave the training licence', 'يرجى اختيار جهة ترخيص التدريب'], 'licence_from');
        elseif ($v['licence_from'] !== '' && !preg_match('/^[A-Z0-9\-\/]{3,30}$/', $v['licence_number'])) $fail(['Please write the training licence number', 'يرجى كتابة رقم ترخيص التدريب'], 'licence_number');
        elseif ($v['licence_from'] === '' && ($v['licence_number'] !== '' || !empty($_FILES['licence_file']['name']))) $fail(['Please choose who gave the training licence', 'يرجى اختيار جهة ترخيص التدريب'], 'licence_from');
        elseif ($v['desc_en'] === '' && $v['desc_ar'] === '') $fail(['Please write a short description (Arabic or English)', 'يرجى كتابة وصف قصير (بالعربية أو الإنجليزية)'], 'desc_en', 'desc_ar');
        elseif (!owSafeLink($v['link'], 'web')[1]) $fail(['Please add your website or a social link (a full web address)', 'يرجى إضافة موقعك أو رابط حساب تواصل (عنوان كامل)'], 'link');
        elseif (!isset($_POST['docs_ok'])) $fail(['Please confirm the documents are valid and yours', 'يرجى تأكيد أن المستندات سارية وتخصك'], 'docs_ok');
        elseif (!owPasswordStrong($pass)) $fail(['The password needs at least 8 characters, with a capital letter, a number and a symbol', 'كلمة المرور تحتاج 8 أحرف على الأقل، فيها حرف كبير ورقم ورمز'], 'password');
        elseif ($pass !== $confirm) $fail(['Passwords do not match', 'كلمتا المرور غير متطابقتين'], 'confirm');
        elseif (empty($_FILES['reg_file']['name'])) $fail(['Please upload the registration document (PDF or photo)', 'يرجى رفع مستند التسجيل (PDF أو صورة)'], 'reg_file');
        elseif ($v['licence_from'] !== '' && empty($_FILES['licence_file']['name'])) $fail(['Please upload the training licence (PDF or photo), or leave the licence empty for now', 'يرجى رفع ترخيص التدريب (PDF أو صورة)، أو اتركه فارغا الآن'], 'licence_file');
    }
    if (!$err) {
        $st = getDB()->prepare('SELECT 1 FROM users WHERE LOWER(TRIM(email)) = :e LIMIT 1'); $st->execute([':e' => $v['email']]);
        if ($st->fetchColumn()) $fail(['An account with this email already exists', 'يوجد حساب بهذا البريد الإلكتروني بالفعل'], 'email');
    }
    /* the files, only once everything else is right */
    $saved = [];
    if (!$err) {
        $r = owInstDocStore($_FILES['reg_file']);
        if (!$r['ok']) $fail($r['err'] ?? ['Please upload the registration document (PDF or photo)', 'يرجى رفع مستند التسجيل (PDF أو صورة)'], 'reg_file'); else $saved['reg_file'] = $r['name'];
    }
    if (!$err && $v['licence_from'] !== '') {
        $r = owInstDocStore($_FILES['licence_file']);
        if (!$r['ok']) $fail($r['err'] ?? ['Please upload the training licence (PDF or photo)', 'يرجى رفع ترخيص التدريب (PDF أو صورة)'], 'licence_file'); else $saved['licence_file'] = $r['name'];
    }
    $logo = '';
    if (!$err && !empty($_FILES['logo']['name'])) {
        $r = owStoreImage($_FILES['logo'], 'uploads/institutes/', true);
        if (!$r['ok']) $fail(['The logo must be a JPG, PNG or WebP picture up to 10 MB', 'يجب أن يكون الشعار صورة JPG أو PNG أو WebP حتى 10 ميغابايت'], 'logo'); else $logo = $r['path'];
    }
    if ($err) { foreach ($saved as $f) owInstDocDelete($f); if ($logo !== '') @unlink(__DIR__ . '/' . $logo); }
    else {
        $db = getDB();
        $token = bin2hex(random_bytes(32));
        $lang = owIsAr() ? 'ar' : 'en';
        $withLang = owHasColumn('users', 'preferred_lang');
        $descEn = $v['desc_en'] !== '' ? $v['desc_en'] : $v['desc_ar']; $descAr = $v['desc_ar'] !== '' ? $v['desc_ar'] : $v['desc_en'];
        $db->beginTransaction();
        try {
            $db->prepare('INSERT INTO users (email, password, full_name_en, full_name_ar, phone, role, is_verified, is_approved, verification_token, verification_expires' . ($withLang ? ', preferred_lang' : '') . ')
                          VALUES (:e, :p, :n, :na, :ph, "institute", 0, 0, :t, :x' . ($withLang ? ', :l' : '') . ')')
               ->execute([':e' => $v['email'], ':p' => hashPassword($pass), ':n' => $v['person'], ':na' => '', ':ph' => $v['phone'], ':t' => $token,
                          ':x' => date('Y-m-d H:i:s', time() + 48 * 3600)] + ($withLang ? [':l' => $lang] : []));
            $uid = (int) $db->lastInsertId();
            $cols = ['user_id' => $uid, 'name_en' => $v['name_en'], 'name_ar' => $v['name_ar'], 'city_en' => $v['city'], 'city_ar' => OW_SA_CITIES[$v['city']],
                     'description_en' => $descEn, 'description_ar' => $descAr, 'contact_email' => $v['email'], 'contact_phone' => $v['phone'], 'website_url' => '',
                     'logo_path' => $logo, 'is_active' => 0, 'via_signup' => 1];
            if ($docsOn) $cols += ['legal_name' => $v['legal_name'], 'contact_person' => $v['person'], 'reg_type' => $v['reg_type'], 'reg_number' => $v['reg_number'],
                                   'reg_file' => $saved['reg_file'] ?? '', 'licence_from' => $v['licence_from'], 'licence_number' => $v['licence_from'] !== '' ? $v['licence_number'] : '',
                                   'licence_file' => $saved['licence_file'] ?? '', 'review_link' => owSafeLink($v['link'], 'web')[0], 'docs_confirmed_at' => date('Y-m-d H:i:s')];
            $db->prepare('INSERT INTO institutes (' . implode(', ', array_keys($cols)) . ') VALUES (:' . implode(', :', array_keys($cols)) . ')')
               ->execute(array_combine(array_map(fn($k) => ':' . $k, array_keys($cols)), array_values($cols)));
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack(); error_log('register_institute: ' . $e->getMessage());
            foreach ($saved as $f) owInstDocDelete($f); if ($logo !== '') @unlink(__DIR__ . '/' . $logo);
            $err = ['Something went wrong. Please try again.', 'حدث خطأ. حاول مرة أخرى.'];
        }
        if (!$err) {
            owSignupCounted($uid, 'institute');
            require_once __DIR__ . '/alert_lib.php';
            owAdminAlert('booking', 'New institute sign-up: ' . $v['name_en'], 'معهد جديد: ' . $v['name_ar'], 'admin.php#pending');
            owSendMail($v['email'], 'Confirm your email · تأكيد بريدك الإلكتروني', owVerifyEmailBody(owSiteBase() . '/verify.php?token=' . $token));
            $ok = true;
        }
    }
}
$ba = fn(string $f) => in_array($f, $bad, true) ? ' aria-invalid="true"' : '';
$bf = fn(string $f) => in_array($f, $bad, true) ? ' has-error' : '';
owBookTop('Join as an art institute', 'انضم كمعهد فني', 'register_institute.php', $preview, '
.ri-sec{margin:var(--space-md) 0 var(--space-xs);font-size:18px;color:var(--color-dark);}
.ri-note{font-size:var(--fs-caption);color:var(--color-body);margin:0 0 var(--space-sm);line-height:1.7;}
.ri-need{display:inline-block;margin:0 0 var(--space-sm);padding:6px 12px;border-radius:10px;background:rgba(var(--rgb-primary),.16);color:var(--color-dark);font-size:14px;font-weight:700;}
.ri-radios{display:flex;flex-wrap:wrap;gap:8px 18px;margin:0 0 var(--space-sm);border:0;padding:0;}
.ri-radios legend{font-size:var(--fs-label);font-weight:700;color:var(--color-dark);margin-bottom:6px;padding:0;}
.ri-radios label{display:flex;gap:8px;align-items:center;font-size:15px;min-height:40px;}
.ri-check{display:flex;gap:8px;align-items:flex-start;margin:var(--space-sm) 0;font-size:15px;}
.ri-check input{margin-top:4px;}
.ri-private{display:flex;gap:10px;align-items:flex-start;padding:12px 14px;border-radius:12px;background:var(--color-surface-2);font-size:14px;line-height:1.7;color:var(--color-dark);margin:0 0 var(--space-sm);}
.has-error input,.has-error select,.has-error textarea{border-color:var(--color-primary-dark);border-width:2px;}
', !owCoursesOn(), 'role-institute');
?>
<div class="card">
  <h1><?= owBi('Join as an art institute', 'انضم كمعهد فني') ?></h1>
  <p><?= owBi('List your courses on Oweili. Students book here, and you follow everything from your own dashboard. Oweili keeps ' . rtrim(rtrim(number_format(owCourseCommission(), 2), '0'), '.') . '% of each registration, before VAT. You are paid once the course has started.',
              'اعرض دوراتك على أويلي. يحجز الطلاب هنا، وتتابع كل شيء من لوحتك الخاصة. يحتفظ أويلي بـ ' . rtrim(rtrim(number_format(owCourseCommission(), 2), '0'), '.') . '٪ من كل تسجيل، قبل الضريبة. وتُدفع مستحقاتك بعد بداية الدورة.') ?></p>
  <p class="mini"><?= owBi('Every institute is reviewed by the Oweili team before it shows', 'يراجع فريق أويلي كل معهد قبل ظهوره') ?></p>
</div>
<?php if ($ok): ?>
  <div class="card"><div class="alert alert-ok" role="status"><?= owBi('Thank you! Please confirm your email. We will review your institute and its documents and let you know.' . ' ' . OW_JUNK_HINT[0], 'شكرا لك! يرجى تأكيد بريدك. سنراجع معهدك ومستنداته ونبلغك.' . ' ' . OW_JUNK_HINT[1]) ?></div></div>
<?php else: ?>
<?php if ($err): ?><div class="alert alert-error" role="alert"><?= owBi($err[0], $err[1]) ?></div><?php endif; ?>
<form method="post" action="register_institute.php" class="card" enctype="multipart/form-data" novalidate><?= csrfField() ?>
  <div style="position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%);white-space:nowrap" aria-hidden="true"><input name="website_hp" tabindex="-1" autocomplete="off"></div>

  <h2 class="ri-sec"><?= owBi('Your institute', 'معهدك') ?></h2>
  <div class="g2">
    <div class="field<?= $bf('name_en') ?>"><label for="ri-ne"><?= owBi('Institute name (English)', 'اسم المعهد (بالإنجليزية)') ?></label><input id="ri-ne" name="name_en" required maxlength="200" dir="ltr" value="<?= e($v['name_en']) ?>"<?= $ba('name_en') ?>></div>
    <div class="field<?= $bf('name_ar') ?>"><label for="ri-na"><?= owBi('Institute name (Arabic)', 'اسم المعهد (بالعربية)') ?></label><input id="ri-na" name="name_ar" required maxlength="200" dir="rtl" value="<?= e($v['name_ar']) ?>"<?= $ba('name_ar') ?>></div>
    <div class="field<?= $bf('legal_name') ?>"><label for="ri-ln"><?= owBi('Legal name, as in your registration', 'الاسم النظامي كما في التسجيل') ?></label><input id="ri-ln" name="legal_name" required maxlength="200" dir="auto" value="<?= e($v['legal_name']) ?>"<?= $ba('legal_name') ?>></div>
    <div class="field<?= $bf('city') ?>"><label for="ri-city"><?= owBi('City (Saudi Arabia)', 'المدينة (المملكة العربية السعودية)') ?></label>
      <select id="ri-city" name="city" required<?= $ba('city') ?>><option value=""><?= owIsAr() ? 'اختر المدينة' : 'Choose the city' ?></option>
        <?php foreach (OW_SA_CITIES as $ce => $ca): ?><option value="<?= e($ce) ?>"<?= $v['city'] === $ce ? ' selected' : '' ?>><?= e(owIsAr() ? $ca : $ce) ?></option><?php endforeach; ?></select></div>
    <div class="field<?= $bf('desc_en') ?>"><label for="ri-de"><?= owBi('Short description (English)', 'وصف قصير (بالإنجليزية)') ?></label><textarea id="ri-de" name="desc_en" rows="3" maxlength="1500" dir="ltr"<?= $ba('desc_en') ?>><?= e($v['desc_en']) ?></textarea></div>
    <div class="field<?= $bf('desc_ar') ?>"><label for="ri-da"><?= owBi('Short description (Arabic)', 'وصف قصير (بالعربية)') ?></label><textarea id="ri-da" name="desc_ar" rows="3" maxlength="1500" dir="rtl"<?= $ba('desc_ar') ?>><?= e($v['desc_ar']) ?></textarea></div>
  </div>
  <p class="ri-note"><?= owBi('One language is enough for the description: the same words are used for both', 'تكفي لغة واحدة للوصف: تُستخدم الكلمات نفسها للغتين') ?></p>
  <div class="g2">
    <div class="field<?= $bf('logo') ?>"><label for="ri-logo"><?= owBi('Logo (optional)', 'الشعار (اختياري)') ?></label><input id="ri-logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp"<?= $ba('logo') ?>></div>
    <div class="field<?= $bf('link') ?>"><label for="ri-link"><?= owBi('Website or social link (only for our review)', 'الموقع أو رابط حساب التواصل (لمراجعتنا فقط)') ?></label><input id="ri-link" name="link" type="url" required maxlength="300" dir="ltr" placeholder="https://" value="<?= e($v['link']) ?>"<?= $ba('link') ?>></div>
  </div>
  <p class="ri-note"><?= owBi('The link helps us review your institute. It is never shown to visitors', 'يساعدنا الرابط في مراجعة معهدك، ولا يظهر للزوار أبدا') ?></p>

  <h2 class="ri-sec"><?= owBi('Contact and sign-in', 'التواصل وتسجيل الدخول') ?></h2>
  <div class="g2">
    <div class="field<?= $bf('person') ?>"><label for="ri-p"><?= owBi('Contact person', 'اسم المسؤول') ?></label><input id="ri-p" name="person" required maxlength="150" dir="auto" value="<?= e($v['person']) ?>"<?= $ba('person') ?>></div>
    <div class="field<?= $bf('phone') ?>"><label for="ri-ph"><?= owBi('Phone', 'الهاتف') ?></label><input id="ri-ph" name="phone" type="tel" required maxlength="30" dir="ltr" placeholder="+966 5X XXX XXXX" value="<?= e($v['phone']) ?>"<?= $ba('phone') ?>></div>
    <div class="field<?= $bf('email') ?>"><label for="ri-e"><?= owBi('Email (for signing in)', 'البريد الإلكتروني (لتسجيل الدخول)') ?></label><input id="ri-e" name="email" type="email" required maxlength="190" dir="ltr" value="<?= e($v['email']) ?>"<?= $ba('email') ?>></div>
    <div></div>
    <div class="field<?= $bf('password') ?>"><label for="ri-pw"><?= owBi('Password', 'كلمة المرور') ?></label><input id="ri-pw" name="password" type="password" required minlength="8" autocomplete="new-password"<?= $ba('password') ?>></div>
    <div class="field<?= $bf('confirm') ?>"><label for="ri-c"><?= owBi('Confirm password', 'تأكيد كلمة المرور') ?></label><input id="ri-c" name="confirm" type="password" required minlength="8" autocomplete="new-password"<?= $ba('confirm') ?>></div>
  </div>

  <h2 class="ri-sec"><?= owBi('Your documents', 'مستنداتك') ?></h2>
  <p class="ri-private"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" style="flex:none;margin-top:3px"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg><span><?= owBi('Your documents are private. Only the Oweili team reviews them. They are never shown to visitors or to other institutes', 'مستنداتك خاصة. لا يراجعها إلا فريق أويلي، ولا تظهر للزوار أو للمعاهد الأخرى أبدا') ?></span></p>
  <fieldset class="ri-radios<?= $bf('reg_type') ?>"><legend><?= owBi('Type of registration', 'نوع التسجيل') ?></legend>
    <?php foreach (OW_INST_REG_TYPES as $k => $l): ?><label><input type="radio" name="reg_type" value="<?= $k ?>"<?= $v['reg_type'] === $k ? ' checked' : '' ?> required> <?= owBi($l[0], $l[1]) ?></label><?php endforeach; ?></fieldset>
  <div class="g2">
    <div class="field<?= $bf('reg_number') ?>"><label for="ri-rn"><?= owBi('Registration number', 'رقم التسجيل') ?></label><input id="ri-rn" name="reg_number" required maxlength="30" dir="ltr" inputmode="text" value="<?= e($v['reg_number']) ?>"<?= $ba('reg_number') ?>></div>
    <div class="field<?= $bf('reg_file') ?>"><label for="ri-rf"><?= owBi('Upload the registration (PDF or photo, up to 10 MB)', 'ارفع التسجيل (PDF أو صورة، حتى 10 ميغابايت)') ?></label><input id="ri-rf" name="reg_file" type="file" required accept="application/pdf,image/jpeg,image/png,image/webp"<?= $ba('reg_file') ?>></div>
  </div>

  <h2 class="ri-sec"><?= owBi('Training licence', 'ترخيص التدريب') ?></h2>
  <p class="ri-need"><?= owBi('Optional now, but needed before your courses go live', 'اختياري الآن، لكنه مطلوب قبل ظهور دوراتك') ?></p>
  <fieldset class="ri-radios<?= $bf('licence_from') ?>"><legend><?= owBi('Given by', 'جهة الترخيص') ?></legend>
    <?php foreach (OW_INST_LICENCES as $k => $l): ?><label><input type="radio" name="licence_from" value="<?= $k ?>"<?= $v['licence_from'] === $k ? ' checked' : '' ?>> <?= owBi($l[0], $l[1]) ?></label><?php endforeach; ?>
    <label><input type="radio" name="licence_from" value=""<?= $v['licence_from'] === '' ? ' checked' : '' ?>> <?= owBi('I will add it later', 'سأضيفه لاحقا') ?></label></fieldset>
  <div class="g2">
    <div class="field<?= $bf('licence_number') ?>"><label for="ri-lnum"><?= owBi('Licence number', 'رقم الترخيص') ?></label><input id="ri-lnum" name="licence_number" maxlength="30" dir="ltr" value="<?= e($v['licence_number']) ?>"<?= $ba('licence_number') ?>></div>
    <div class="field<?= $bf('licence_file') ?>"><label for="ri-lf"><?= owBi('Upload the licence (PDF or photo, up to 10 MB)', 'ارفع الترخيص (PDF أو صورة، حتى 10 ميغابايت)') ?></label><input id="ri-lf" name="licence_file" type="file" accept="application/pdf,image/jpeg,image/png,image/webp"<?= $ba('licence_file') ?>></div>
  </div>

  <label class="ri-check<?= $bf('docs_ok') ?>"><input type="checkbox" name="docs_ok" value="1"<?= isset($_POST['docs_ok']) ? ' checked' : '' ?> required<?= $ba('docs_ok') ?>> <span><?= owBi('I confirm these documents are valid and mine', 'أؤكد أن هذه المستندات سارية وتخصني') ?></span></label>
  <p class="mini"><?= owBi('By joining you agree to the', 'بالانضمام توافق على') ?> <a href="terms.php"><?= owBi('Terms of Use', 'شروط الاستخدام') ?></a> <?= owBi('and the', 'و') ?> <a href="privacy.php"><?= owBi('Privacy Policy', 'سياسة الخصوصية') ?></a></p>
  <button class="btn btn-primary" type="submit"><?= owBi('Send for review', 'أرسل للمراجعة') ?></button>
</form>
<?php endif; ?>
<?php owBookBottom();
