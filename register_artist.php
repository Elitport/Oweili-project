<?php
require_once 'config.php';
require_once __DIR__ . '/mail_lib.php';
require_once __DIR__ . '/platform_lib.php';

const AR_VIDEO_MAX = 50 * 1024 * 1024; // 50 MB
const AR_PORTFOLIO_MAX = 5;

function validateVideoUpload(array $file): array {
    if (!isset($file['error']) || is_array($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return ['ok'=>false,'ext'=>''];
    if ($file['size'] <= 0 || $file['size'] > AR_VIDEO_MAX) return ['ok'=>false,'ext'=>''];
    if (!is_uploaded_file($file['tmp_name'])) return ['ok'=>false,'ext'=>''];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    // MP4, or MOV (what iPhones record)
    $types = ['mp4' => ['video/mp4', 'application/mp4'], 'mov' => ['video/quicktime', 'video/mp4', 'application/octet-stream']];
    if (!isset($types[$ext]) || !in_array($mime, $types[$ext], true)) return ['ok'=>false,'ext'=>''];
    return ['ok'=>true,'ext'=>$ext];
}

/* A size from the server settings ("8M", "512K", "1G") in bytes; 0 = no limit */
function arIniBytes(string $key): int {
    $v = trim((string) ini_get($key));
    if ($v === '' || $v === '-1') return 0;
    $n = (int) $v;
    return $n * ([ 'g' => 1073741824, 'm' => 1048576, 'k' => 1024 ][strtolower(substr($v, -1))] ?? 1);
}
$arMb = fn(int $b): string => rtrim(rtrim(number_format($b / 1048576, 1, '.', ''), '0'), '.');
/* The real limits: our own, but never more than the server allows */
$arFileMax  = arIniBytes('upload_max_filesize');                        // each single file
$arTotalMax = arIniBytes('post_max_size');                              // everything sent together
$arPhotoMax = defined('UPLOAD_MAX_BYTES') ? (int) UPLOAD_MAX_BYTES : 5 * 1024 * 1024;
if ($arFileMax > 0) $arPhotoMax = min($arPhotoMax, $arFileMax);
$arVideoMax = $arFileMax > 0 ? min(AR_VIDEO_MAX, $arFileMax) : AR_VIDEO_MAX;
$LIM = ['photo' => $arMb($arPhotoMax), 'video' => $arMb($arVideoMax), 'total' => $arTotalMax > 0 ? $arMb($arTotalMax) : ''];

$errCode = '';
$okCode  = '';
$old = ['first'=>'','last'=>'','artist'=>'','email'=>'','phone'=>'','city'=>'','bioEn'=>'','bioAr'=>''];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $old['first']  = sanitize($_POST['first_name'] ?? '');
    $old['last']   = sanitize($_POST['last_name'] ?? '');
    $old['artist'] = sanitize($_POST['artist_name'] ?? '');
    $old['email']  = strtolower(sanitize($_POST['email'] ?? ''));
    $old['phone']  = sanitize($_POST['phone'] ?? '');
    $old['city']   = sanitize($_POST['city'] ?? '');
    $old['bioEn']  = trim((string)($_POST['bio_en'] ?? ''));
    $old['bioAr']  = trim((string)($_POST['bio_ar'] ?? ''));
    $pass    = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');

    $strong = strlen($pass) >= 8 && preg_match('/[A-Z]/', $pass) && preg_match('/[0-9]/', $pass) && preg_match('/[!@#$%^&*]/', $pass);
    $fullName = trim($old['first'] . ' ' . $old['last']);

    $hasPhoto = !empty($_FILES['profile_picture']['name']);
    $hasVideo = !empty($_FILES['art_video']['name']);
    // Count non-empty portfolio uploads.
    $portCount = 0;
    if (!empty($_FILES['portfolio']['name']) && is_array($_FILES['portfolio']['name'])) {
        foreach ($_FILES['portfolio']['name'] as $nm) { if ($nm !== '') $portCount++; }
    }

    /* When the files together are bigger than the server allows, the server
       throws the whole form away and nothing arrives (not even the security
       token). Say so clearly instead of "security check failed". */
    $tooBig = empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
    if ($tooBig) {
        $errCode = 'too_big';
    } elseif (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errCode = 'csrf';
    } elseif (owSignupLimited()) {
        $errCode = 'rate';
    } elseif ($old['first']==='' || $old['artist']==='' || $old['email']==='' || $old['phone']==='' || $old['city']==='') {
        $errCode = 'required';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errCode = 'email';
    } elseif (!preg_match('/^[+0-9][0-9 \-()]{6,29}$/', $old['phone'])) {
        $errCode = 'phone';
    } elseif (!$strong) {
        $errCode = 'weak';
    } elseif ($pass !== $confirm) {
        $errCode = 'mismatch';
    } elseif (!$hasPhoto || !validateUpload($_FILES['profile_picture'])['ok']) {
        $errCode = 'photo';
    } elseif ($portCount < 1) {
        $errCode = 'portfolio_req';
    } elseif ($portCount > AR_PORTFOLIO_MAX) {
        $errCode = 'portfolio_max';
    } elseif ($hasVideo && !validateVideoUpload($_FILES['art_video'])['ok']) {
        $errCode = 'video';                                      // the video is optional, but must be valid if sent
    } else {
        // Validate every portfolio image before saving anything.
        $portOk = true;
        for ($i = 0; $i < count($_FILES['portfolio']['name']); $i++) {
            if ($_FILES['portfolio']['name'][$i] === '') continue;
            $one = [
                'name'=>$_FILES['portfolio']['name'][$i], 'type'=>$_FILES['portfolio']['type'][$i],
                'tmp_name'=>$_FILES['portfolio']['tmp_name'][$i], 'error'=>$_FILES['portfolio']['error'][$i],
                'size'=>$_FILES['portfolio']['size'][$i],
            ];
            if (!validateUpload($one)['ok']) { $portOk = false; break; }
        }
        if (!$portOk) {
            $errCode = 'portfolio';
        } else {
            $stmt = getDB()->prepare('SELECT 1 FROM users WHERE LOWER(TRIM(email)) = :e LIMIT 1');
            $stmt->execute([':e' => $old['email']]);
            if ($stmt->fetchColumn()) {
                $errCode = 'exists';
            } else {
                // Save profile picture
                $pChk = validateUpload($_FILES['profile_picture']);
                $pdir = __DIR__ . '/uploads/profiles/'; if (!is_dir($pdir)) @mkdir($pdir, 0755, true);
                $pName = safeUploadName($pChk['ext']); $photoRel = '';
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $pdir . $pName)) $photoRel = 'uploads/profiles/' . $pName;
                // Save video (optional)
                $videoRel = '';
                if ($hasVideo) {
                    $vChk = validateVideoUpload($_FILES['art_video']);
                    $vdir = __DIR__ . '/uploads/videos/'; if (!is_dir($vdir)) @mkdir($vdir, 0755, true);
                    $vName = safeUploadName($vChk['ext']);
                    if (move_uploaded_file($_FILES['art_video']['tmp_name'], $vdir . $vName)) $videoRel = 'uploads/videos/' . $vName;
                }
                // Insert user
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 24*60*60);
                getDB()->prepare(
                    'INSERT INTO users (email,password,full_name_en,artist_name,phone,city,bio_en,bio_ar,profile_picture,art_video,role,is_verified,is_approved,verification_token,verification_expires)
                     VALUES (:email,:pass,:name,:aname,:phone,:city,:ben,:bar,:pic,:vid,"artist",0,0,:tok,:exp)'
                )->execute([
                    ':email'=>$old['email'], ':pass'=>hashPassword($pass), ':name'=>$fullName, ':aname'=>$old['artist'],
                    ':phone'=>$old['phone'], ':city'=>$old['city'], ':ben'=>$old['bioEn'], ':bar'=>$old['bioAr'],
                    ':pic'=>$photoRel, ':vid'=>$videoRel, ':tok'=>$token, ':exp'=>$expires,
                ]);
                $newId = (int) getDB()->lastInsertId();
                owSignupCounted($newId, 'artist');
                // Save portfolio images
                $podir = __DIR__ . '/uploads/portfolio/'; if (!is_dir($podir)) @mkdir($podir, 0755, true);
                $sort = 0;
                for ($i = 0; $i < count($_FILES['portfolio']['name']); $i++) {
                    if ($_FILES['portfolio']['name'][$i] === '') continue;
                    $one = [
                        'name'=>$_FILES['portfolio']['name'][$i], 'type'=>$_FILES['portfolio']['type'][$i],
                        'tmp_name'=>$_FILES['portfolio']['tmp_name'][$i], 'error'=>$_FILES['portfolio']['error'][$i],
                        'size'=>$_FILES['portfolio']['size'][$i],
                    ];
                    $c = validateUpload($one); if (!$c['ok']) continue;
                    $fn = safeUploadName($c['ext']);
                    if (move_uploaded_file($one['tmp_name'], $podir . $fn)) {
                        getDB()->prepare('INSERT INTO portfolio_images (artist_id,image_url,sort_order) VALUES (:a,:u,:s)')
                               ->execute([':a'=>$newId, ':u'=>'uploads/portfolio/'.$fn, ':s'=>$sort++]);
                    }
                }
                $verifyUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'oweili.com') . '/verify.php?token=' . $token;
                if (!owSendMail($old['email'], 'Confirm your email · تأكيد بريدك الإلكتروني', owVerifyEmailBody($verifyUrl, true))) {
                    error_log('register_artist: verification email failed to ' . $old['email']);
                }
                $okCode = 'check_email';
                $old = ['first'=>'','last'=>'','artist'=>'','email'=>'','phone'=>'','city'=>'','bioEn'=>'','bioAr'=>''];
            }
        }
    }
}

$ERR = [
    'too_big'=>['en'=>'Your files are too large to send together. The limit is ' . ($LIM['total'] !== '' ? $LIM['total'] . 'MB in total' : 'set by the server') . ': photo up to ' . $LIM['photo'] . 'MB, each artwork photo up to ' . $LIM['photo'] . 'MB, video up to ' . $LIM['video'] . 'MB. Please choose smaller files or leave the video out.',
                'ar'=>'ملفاتك أكبر من المسموح إرسالها معاً. الحد ' . ($LIM['total'] !== '' ? $LIM['total'] . ' ميغابايت للمجموع' : 'يحدده الخادم') . ': الصورة حتى ' . $LIM['photo'] . ' ميغابايت، وكل صورة عمل حتى ' . $LIM['photo'] . ' ميغابايت، والفيديو حتى ' . $LIM['video'] . ' ميغابايت. يرجى اختيار ملفات أصغر أو ترك الفيديو.'],
    'csrf'=>['en'=>'This page was open for a long time, so we could not send it. Please try again. Files must stay under ' . ($LIM['total'] !== '' ? $LIM['total'] . 'MB in total' : 'the size limits shown') . '.',
             'ar'=>'بقيت الصفحة مفتوحة وقتاً طويلاً فلم نتمكن من إرسالها. حاول مرة أخرى. يجب ألا تتجاوز الملفات ' . ($LIM['total'] !== '' ? $LIM['total'] . ' ميغابايت للمجموع' : 'الحدود الموضحة') . '.'],
    'required'=>['en'=>'Please fill in all required fields.','ar'=>'يرجى تعبئة جميع الحقول المطلوبة.'],
    'email'=>['en'=>'Please enter a valid email address.','ar'=>'يرجى إدخال بريد إلكتروني صالح.'],
    'phone'=>['en'=>'Please enter a valid mobile number.','ar'=>'يرجى إدخال رقم جوال صالح.'],
    'weak'=>['en'=>'Password does not meet the requirements.','ar'=>'كلمة المرور لا تستوفي الشروط.'],
    'mismatch'=>['en'=>'Passwords do not match.','ar'=>'كلمتا المرور غير متطابقتين.'],
    'photo'=>['en'=>'A profile picture (JPG/PNG, max ' . $LIM['photo'] . 'MB) is required.','ar'=>'الصورة الشخصية مطلوبة (JPG/PNG، بحد أقصى ' . $LIM['photo'] . ' ميغابايت).'],
    'portfolio_req'=>['en'=>'Please upload at least one portfolio image.','ar'=>'يرجى رفع صورة واحدة على الأقل لأعمالك.'],
    'portfolio_max'=>['en'=>'You can upload up to 5 portfolio images.','ar'=>'يمكنك رفع حتى 5 صور لأعمالك.'],
    'portfolio'=>['en'=>'Portfolio images must be JPG/PNG under ' . $LIM['photo'] . 'MB each.','ar'=>'يجب أن تكون صور الأعمال JPG/PNG وأقل من ' . $LIM['photo'] . ' ميغابايت لكل صورة.'],
    'video'=>['en'=>'The video must be MP4 or MOV and under ' . $LIM['video'] . 'MB. You can also leave it out.','ar'=>'يجب أن يكون الفيديو بصيغة MP4 أو MOV وأقل من ' . $LIM['video'] . ' ميغابايت. ويمكنك تركه.'],
    'exists'=>['en'=>'An account with this email already exists.','ar'=>'يوجد حساب بهذا البريد الإلكتروني بالفعل.'],
    'rate'=>['en'=>'Too many new accounts from here. Please try again in an hour.','ar'=>'حسابات جديدة كثيرة من هنا. حاول مرة أخرى بعد ساعة.'],
];
$OK = ['check_email'=>['en'=>'Thanks! Check your email to verify. Our team will review your portfolio before approval.','ar'=>'شكراً! راجع بريدك للتفعيل. سيراجع فريقنا أعمالك قبل الموافقة.']];
$errEn = $errCode ? $ERR[$errCode]['en'] : ''; $errAr = $errCode ? $ERR[$errCode]['ar'] : '';
$okEn = $okCode ? $OK[$okCode]['en'] : ''; $okAr = $okCode ? $OK[$okCode]['ar'] : '';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" id="html">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Artist Sign-up · Oweili</title>
<meta name="description" content="We are glad you are here. Our team reviews every profile by hand. سعداء بوجودك معنا. يراجع فريقنا كل ملف بعناية.">
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:var(--color-bg);font-family:var(--font-sans);overflow-x:hidden;}
.opt-badge{display:inline-block;font-size:var(--fs-min);font-weight:700;line-height:1.4;padding:2px 8px;border-radius:999px;background:rgba(var(--rgb-ink),0.07);color:var(--color-dark);margin-inline-start:6px;vertical-align:middle;}
</style>
</head>
<body>
<div class="bg-wrap"><video class="bg-video" id="vid" autoplay loop muted playsinline src="<?= owBase() ?>bg.mp4"></video></div>
<div class="overlay"></div>
<?php include __DIR__ . "/nav.php"; ?>

<div class="auth-wrap">
  <div class="auth-card wide">
    <div class="auth-head"><h1 id="t-title">Join as an Artist</h1><p id="t-sub">We are glad you are here. Our team reviews every profile by hand.</p></div>
    <div class="err-box" id="err" data-en="<?= e($errEn) ?>" data-ar="<?= e($errAr) ?>" style="display:<?= $errCode?'block':'none' ?>;"><?= e($errEn) ?></div>
    <div class="ok-box" id="ok" data-en="<?= e($okEn) ?>" data-ar="<?= e($okAr) ?>" style="display:<?= $okCode?'block':'none' ?>;"><?= e($okEn) ?></div>
    <form method="post" action="register_artist.php" enctype="multipart/form-data" onsubmit="return checkSizes()" data-total="<?= (int) $arTotalMax ?>">
      <?= csrfField() ?>
      <div class="grid2">
        <div class="field"><label id="t-l-first">First name</label><input type="text" name="first_name" required value="<?= e($old['first']) ?>"></div>
        <div class="field"><label id="t-l-last">Last name</label><input type="text" name="last_name" value="<?= e($old['last']) ?>"></div>
      </div>
      <div class="field"><label id="t-l-artist">Artist name (the name people will see)</label><input type="text" name="artist_name" required value="<?= e($old['artist']) ?>"></div>
      <div class="grid2">
        <div class="field"><label id="t-l-email">Email</label><input type="email" name="email" required value="<?= e($old['email']) ?>"></div>
        <div class="field"><label id="t-l-phone">Mobile number</label><input type="tel" name="phone" dir="ltr" required value="<?= e($old['phone']) ?>" placeholder="+966 5X XXX XXXX"></div>
      </div>
      <div class="field"><label id="t-l-city">City</label><input type="text" name="city" required value="<?= e($old['city']) ?>"></div>
      <div class="grid2">
        <div class="field"><label id="t-l-bioen">About you in English (optional)</label><textarea name="bio_en"><?= e($old['bioEn']) ?></textarea></div>
        <div class="field"><label id="t-l-bioar">About you in Arabic (optional)</label><textarea name="bio_ar" dir="rtl"><?= e($old['bioAr']) ?></textarea></div>
      </div>
      <div class="field"><label id="t-l-photo" for="f-photo">Your photo (required)</label>
        <input type="file" id="f-photo" name="profile_picture" accept="image/jpeg,image/png" required data-max="<?= (int) $arPhotoMax ?>">
        <p class="hint" id="t-photo-hint">JPG or PNG, up to <?= e($LIM['photo']) ?>MB</p></div>
      <div class="field"><label id="t-l-port" for="f-port">Your artworks (required, up to 5 photos)</label>
        <input type="file" id="f-port" name="portfolio[]" accept="image/jpeg,image/png" multiple required data-max="<?= (int) $arPhotoMax ?>">
        <p class="hint" id="t-port-hint">Pick the pieces you are most proud of. JPG or PNG, up to <?= e($LIM['photo']) ?>MB each.</p></div>
      <div class="field"><label for="f-video"><span id="t-l-video">A short video of you at work</span> <span class="opt-badge" id="t-opt">Optional / اختياري</span></label>
        <input type="file" id="f-video" name="art_video" accept="video/mp4,video/quicktime,.mp4,.mov" data-max="<?= (int) $arVideoMax ?>">
        <p class="hint" id="t-video-hint">Film your hands drawing or painting. It shows us the art is truly yours. MP4 or MOV, up to <?= e($LIM['video']) ?>MB.</p></div>
      <?php if ($LIM['total'] !== ''): ?><p class="hint" id="t-total-hint">All files together: up to <?= e($LIM['total']) ?>MB.</p><?php endif; ?>
      <div class="field"><label id="t-l-pass">Password</label><input type="password" id="password" name="password" required oninput="checkRules()">
        <ul class="pw-rules">
          <li id="r-len"><span class="mark">✓</span><span id="t-r-len">Minimum 8 characters</span></li>
          <li id="r-upper"><span class="mark">✓</span><span id="t-r-upper">At least one uppercase letter</span></li>
          <li id="r-num"><span class="mark">✓</span><span id="t-r-num">At least one number</span></li>
          <li id="r-spec"><span class="mark">✓</span><span id="t-r-spec">At least one special character (!@#$%^&*)</span></li>
        </ul>
      </div>
      <div class="field"><label id="t-l-confirm">Confirm password</label><input type="password" name="confirm" required></div>
      <button type="submit" class="btn btn-primary btn-block btn-submit" id="t-submit">Send for Review</button>
    </form>
    <div class="auth-foot"><span id="t-have">Already one of us?</span> <a class="link" href="login.php" id="t-login">Sign In</a></div>
  </div>
</div>

<script>
const LIM=<?= json_encode($LIM, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
const T={
  en:{dir:'ltr',lb:'العربية',title:'Join as an Artist',sub:'We are glad you are here. Our team reviews every profile by hand.',
    first:'First name',last:'Last name',artist:'Artist name (the name people will see)',email:'Email',phone:'Mobile number',city:'City',
    bioen:'About you in English (optional)',bioar:'About you in Arabic (optional)',
    photo:'Your photo (required)',photoHint:'JPG or PNG, up to '+LIM.photo+'MB',port:'Your artworks (required, up to 5 photos)',portHint:'Pick the pieces you are most proud of. JPG or PNG, up to '+LIM.photo+'MB each.',
    video:'A short video of you at work',videoHint:'Film your hands drawing or painting. It shows us the art is truly yours. MP4 or MOV, up to '+LIM.video+'MB.',
    totalHint:'All files together: up to '+LIM.total+'MB.',
    tooBigFile:function(n,mb){return '“'+n+'” is larger than '+mb+'MB. Please choose a smaller file.';},
    tooBigAll:'Your files are larger than '+LIM.total+'MB together. Please choose smaller files or leave the video out.',
    pass:'Password',confirm:'Confirm password',
    rlen:'Minimum 8 characters',rupper:'At least one uppercase letter',rnum:'At least one number',rspec:'At least one special character (!@#$%^&*)',
    submit:'Send for Review',have:'Already one of us?',login:'Sign In',
    nav:{community:'Community',marketplace:'Gallery',explore:'Explore Art Styles',support:'Support',
      sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',login:'Sign In',reg:'Join Free'}},
  ar:{dir:'rtl',lb:'English',title:'انضم إلينا كفنان',sub:'سعداء بوجودك معنا. يراجع فريقنا كل ملف بعناية.',
    first:'الاسم الأول',last:'اسم العائلة',artist:'اسمك الفني (الاسم الذي سيراه الناس)',email:'البريد الإلكتروني',phone:'رقم الجوال',city:'المدينة',
    bioen:'نبذة عنك بالإنجليزية (اختياري)',bioar:'نبذة عنك بالعربية (اختياري)',
    photo:'صورتك (مطلوبة)',photoHint:'JPG أو PNG حتى '+LIM.photo+' ميغابايت',port:'أعمالك (مطلوبة، حتى 5 صور)',portHint:'اختر الأعمال التي تفخر بها أكثر. JPG أو PNG حتى '+LIM.photo+' ميغابايت لكل صورة.',
    video:'مقطع قصير وأنت تعمل',videoHint:'صوّر يديك وهي ترسم أو تلوّن. هكذا نعرف أن الفن من صنعك. MP4 أو MOV حتى '+LIM.video+' ميغابايت.',
    totalHint:'كل الملفات معاً: حتى '+LIM.total+' ميغابايت.',
    tooBigFile:function(n,mb){return '«'+n+'» أكبر من '+mb+' ميغابايت. يرجى اختيار ملف أصغر.';},
    tooBigAll:'ملفاتك معاً أكبر من '+LIM.total+' ميغابايت. يرجى اختيار ملفات أصغر أو ترك الفيديو.',
    pass:'كلمة المرور',confirm:'تأكيد كلمة المرور',
    rlen:'الحد الأدنى 8 أحرف',rupper:'حرف كبير واحد على الأقل',rnum:'رقم واحد على الأقل',rspec:'رمز خاص واحد على الأقل (!@#$%^&*)',
    submit:'أرسل للمراجعة',have:'أنت معنا بالفعل؟',login:'تسجيل الدخول',
    nav:{community:'المجتمع',marketplace:'المعرض',explore:'استكشف أساليب الرسم',support:'الدعم',
      sp1:'تواصل معنا',sp2:'كيف يعمل الموقع',sp3:'شروط الاستخدام',login:'تسجيل الدخول',reg:'انضم مجاناً'}}
};
let L='en';
/* Check sizes on the phone before sending, so nothing is uploaded for nothing */
function checkSizes(){
  const t=T[L], err=document.getElementById('err'), form=document.querySelector('form[data-total]');
  let total=0, msg='';
  form.querySelectorAll('input[type=file][data-max]').forEach(function(inp){
    const max=+inp.dataset.max;
    Array.from(inp.files||[]).forEach(function(f){ total+=f.size; if(!msg && max && f.size>max) msg=t.tooBigFile(f.name,(max/1048576).toFixed(0)); });
  });
  const lim=+form.dataset.total;
  if(!msg && lim && total>lim*0.97) msg=t.tooBigAll;
  if(msg){ err.textContent=msg; err.style.display='block'; err.scrollIntoView({behavior:'smooth',block:'center'}); return false; }
  return true;
}
function checkRules(){const p=document.getElementById('password').value;const set=(id,ok)=>document.getElementById(id).classList.toggle('ok',ok);set('r-len',p.length>=8);set('r-upper',/[A-Z]/.test(p));set('r-num',/[0-9]/.test(p));set('r-spec',/[!@#$%^&*]/.test(p));}
function setTxt(id,v){const el=document.getElementById(id);if(el)el.textContent=v;}
function apply(l){
  const t=T[l];
  document.getElementById('html').lang=l;document.getElementById('html').setAttribute('dir',t.dir);
  document.documentElement.setAttribute('dir',t.dir);document.getElementById('topnav').setAttribute('dir',t.dir);
  document.getElementById('lb').textContent=t.lb;
  setTxt('t-title',t.title);setTxt('t-sub',t.sub);
  setTxt('t-l-first',t.first);setTxt('t-l-last',t.last);setTxt('t-l-artist',t.artist);setTxt('t-l-email',t.email);setTxt('t-l-phone',t.phone);setTxt('t-l-city',t.city);
  setTxt('t-l-bioen',t.bioen);setTxt('t-l-bioar',t.bioar);
  setTxt('t-l-photo',t.photo);setTxt('t-l-port',t.port);setTxt('t-port-hint',t.portHint);
  setTxt('t-l-video',t.video);setTxt('t-video-hint',t.videoHint);setTxt('t-photo-hint',t.photoHint);setTxt('t-total-hint',t.totalHint);
  setTxt('t-l-pass',t.pass);setTxt('t-l-confirm',t.confirm);
  setTxt('t-r-len',t.rlen);setTxt('t-r-upper',t.rupper);setTxt('t-r-num',t.rnum);setTxt('t-r-spec',t.rspec);
  setTxt('t-submit',t.submit);setTxt('t-have',t.have);setTxt('t-login',t.login);
  const err=document.getElementById('err');if(err){const v=l==='ar'?err.dataset.ar:err.dataset.en;if(v)err.textContent=v;}
  const ok=document.getElementById('ok');if(ok){const v=l==='ar'?ok.dataset.ar:ok.dataset.en;if(v)ok.textContent=v;}
  const n=t.nav;
  setTxt('nav-community-label',n.community);
  setTxt('nav-marketplace-label',n.marketplace);setTxt('nav-explore-label',n.explore);setTxt('nav-support-label',n.support);
  
  setTxt('dd-sp1',n.sp1);setTxt('dd-sp2',n.sp2);setTxt('dd-sp3',n.sp3);
  setTxt('n-login-t',n.login);setTxt('n-reg-t',n.reg);
}
function tgl(){L=L==='en'?'ar':'en';apply(L);try{localStorage.setItem('lang',L);}catch(e){}}
try{var _s=localStorage.getItem('lang');if(_s==='ar'||_s==='en')L=_s;}catch(e){}
apply(L);
const v=document.getElementById('vid');
if(v){{const on=()=>v.classList.add('on');if(v.readyState>=3)on();v.addEventListener('canplay',on,{once:true});v.addEventListener('playing',on,{once:true});}v.play().catch(()=>{});}
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
