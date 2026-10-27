<?php
require_once 'config.php';

const AR_VIDEO_MAX = 50 * 1024 * 1024; // 50 MB
const AR_PORTFOLIO_MAX = 5;

function validateVideoUpload(array $file): array {
    if (!isset($file['error']) || is_array($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return ['ok'=>false,'ext'=>''];
    if ($file['size'] <= 0 || $file['size'] > AR_VIDEO_MAX) return ['ok'=>false,'ext'=>''];
    if (!is_uploaded_file($file['tmp_name'])) return ['ok'=>false,'ext'=>''];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($mime, ['video/mp4','application/mp4'], true) || $ext !== 'mp4') return ['ok'=>false,'ext'=>''];
    return ['ok'=>true,'ext'=>'mp4'];
}

$errCode = '';
$okCode  = '';
$old = ['first'=>'','last'=>'','artist'=>'','email'=>'','phone'=>'','city'=>'','bioEn'=>'','bioAr'=>''];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $old['first']  = sanitize($_POST['first_name'] ?? '');
    $old['last']   = sanitize($_POST['last_name'] ?? '');
    $old['artist'] = sanitize($_POST['artist_name'] ?? '');
    $old['email']  = sanitize($_POST['email'] ?? '');
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

    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errCode = 'csrf';
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
    } elseif (!$hasVideo) {
        $errCode = 'video_req';
    } elseif (!validateVideoUpload($_FILES['art_video'])['ok']) {
        $errCode = 'video';
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
            $stmt = getDB()->prepare('SELECT 1 FROM users WHERE email = :e LIMIT 1');
            $stmt->execute([':e' => $old['email']]);
            if ($stmt->fetchColumn()) {
                $errCode = 'exists';
            } else {
                // Save profile picture
                $pChk = validateUpload($_FILES['profile_picture']);
                $pdir = __DIR__ . '/uploads/profiles/'; if (!is_dir($pdir)) @mkdir($pdir, 0755, true);
                $pName = safeUploadName($pChk['ext']); $photoRel = '';
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $pdir . $pName)) $photoRel = 'uploads/profiles/' . $pName;
                // Save video
                $vChk = validateVideoUpload($_FILES['art_video']);
                $vdir = __DIR__ . '/uploads/videos/'; if (!is_dir($vdir)) @mkdir($vdir, 0755, true);
                $vName = safeUploadName($vChk['ext']); $videoRel = '';
                if (move_uploaded_file($_FILES['art_video']['tmp_name'], $vdir . $vName)) $videoRel = 'uploads/videos/' . $vName;
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
                $body = "Welcome!\r\n\r\nPlease verify your account by clicking the link below:\r\n" . $verifyUrl . "\r\n\r\nAfter verifying, our team reviews your portfolio before your profile goes live.\r\n";
                if (!sendEmail($old['email'], 'Verify your artist account', $body)) {
                    error_log('register_artist: verification email failed to ' . $old['email']);
                }
                $okCode = 'check_email';
                $old = ['first'=>'','last'=>'','artist'=>'','email'=>'','phone'=>'','city'=>'','bioEn'=>'','bioAr'=>''];
            }
        }
    }
}

$ERR = [
    'csrf'=>['en'=>'Security check failed. Please try again.','ar'=>'فشل التحقق الأمني. حاول مرة أخرى.'],
    'required'=>['en'=>'Please fill in all required fields.','ar'=>'يرجى تعبئة جميع الحقول المطلوبة.'],
    'email'=>['en'=>'Please enter a valid email address.','ar'=>'يرجى إدخال بريد إلكتروني صالح.'],
    'phone'=>['en'=>'Please enter a valid mobile number.','ar'=>'يرجى إدخال رقم جوال صالح.'],
    'weak'=>['en'=>'Password does not meet the requirements.','ar'=>'كلمة المرور لا تستوفي الشروط.'],
    'mismatch'=>['en'=>'Passwords do not match.','ar'=>'كلمتا المرور غير متطابقتين.'],
    'photo'=>['en'=>'A profile picture (JPG/PNG, max 5MB) is required.','ar'=>'الصورة الشخصية مطلوبة (JPG/PNG، بحد أقصى 5 ميجابايت).'],
    'portfolio_req'=>['en'=>'Please upload at least one portfolio image.','ar'=>'يرجى رفع صورة واحدة على الأقل لأعمالك.'],
    'portfolio_max'=>['en'=>'You can upload up to 5 portfolio images.','ar'=>'يمكنك رفع حتى 5 صور لأعمالك.'],
    'portfolio'=>['en'=>'Portfolio images must be JPG/PNG under 5MB each.','ar'=>'يجب أن تكون صور الأعمال JPG/PNG وأقل من 5 ميجابايت لكل صورة.'],
    'video_req'=>['en'=>'A video of you creating art is required.','ar'=>'مطلوب فيديو أثناء إبداعك للفن.'],
    'video'=>['en'=>'The art video must be an MP4 file under 50MB.','ar'=>'يجب أن يكون الفيديو بصيغة MP4 وأقل من 50 ميجابايت.'],
    'exists'=>['en'=>'An account with this email already exists.','ar'=>'يوجد حساب بهذا البريد الإلكتروني بالفعل.'],
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
<link rel="stylesheet" href="/assets/css/oweili.css?v=<?= @filemtime(__DIR__ . '/assets/css/oweili.css') ?>">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:var(--color-bg);font-family:var(--font-sans);overflow-x:hidden;}
</style>
</head>
<body>
<div class="bg-wrap"><video class="bg-video" id="vid" autoplay loop muted playsinline src="bg.mp4"></video></div>
<div class="overlay"></div>
<?php include __DIR__ . "/nav.php"; ?>

<div class="auth-wrap">
  <div class="auth-card wide">
    <div class="auth-head"><h1 id="t-title">Join as an Artist</h1><p id="t-sub">We are glad you are here. Our team reviews every profile by hand.</p></div>
    <div class="err-box" id="err" data-en="<?= e($errEn) ?>" data-ar="<?= e($errAr) ?>" style="display:<?= $errCode?'block':'none' ?>;"><?= e($errEn) ?></div>
    <div class="ok-box" id="ok" data-en="<?= e($okEn) ?>" data-ar="<?= e($okAr) ?>" style="display:<?= $okCode?'block':'none' ?>;"><?= e($okEn) ?></div>
    <form method="post" action="register_artist.php" enctype="multipart/form-data">
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
      <div class="field"><label id="t-l-photo">Your photo (required, JPG or PNG, up to 5MB)</label>
        <input type="file" name="profile_picture" accept="image/jpeg,image/png" required></div>
      <div class="field"><label id="t-l-port">Your artworks (required, up to 5 photos)</label>
        <input type="file" name="portfolio[]" accept="image/jpeg,image/png" multiple required>
        <p class="hint" id="t-port-hint">Pick the pieces you are most proud of.</p></div>
      <div class="field"><label id="t-l-video">A short video of you at work (required, MP4 up to 50MB)</label>
        <input type="file" name="art_video" accept="video/mp4" required>
        <p class="hint" id="t-video-hint">Film your hands drawing or painting. It shows us the art is truly yours.</p></div>
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
const T={
  en:{dir:'ltr',lb:'العربية',title:'Join as an Artist',sub:'We are glad you are here. Our team reviews every profile by hand.',
    first:'First name',last:'Last name',artist:'Artist name (the name people will see)',email:'Email',phone:'Mobile number',city:'City',
    bioen:'About you in English (optional)',bioar:'About you in Arabic (optional)',
    photo:'Your photo (required, JPG or PNG, up to 5MB)',port:'Your artworks (required, up to 5 photos)',portHint:'Pick the pieces you are most proud of.',
    video:'A short video of you at work (required, MP4 up to 50MB)',videoHint:'Film your hands drawing or painting. It shows us the art is truly yours.',
    pass:'Password',confirm:'Confirm password',
    rlen:'Minimum 8 characters',rupper:'At least one uppercase letter',rnum:'At least one number',rspec:'At least one special character (!@#$%^&*)',
    submit:'Send for Review',have:'Already one of us?',login:'Sign In',
    nav:{community:'Community',marketplace:'Gallery',explore:'Explore Art Styles',support:'Support',
      c2:'Meet Fellow Artists',c3:'Share Your Work',c4:'Learn Together',
      sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',login:'Sign In',reg:'Join Free'}},
  ar:{dir:'rtl',lb:'English',title:'انضم إلينا كفنان',sub:'سعداء بوجودك معنا. يراجع فريقنا كل ملف بعناية.',
    first:'الاسم الأول',last:'اسم العائلة',artist:'اسمك الفني (الاسم الذي سيراه الناس)',email:'البريد الإلكتروني',phone:'رقم الجوال',city:'المدينة',
    bioen:'نبذة عنك بالإنجليزية (اختياري)',bioar:'نبذة عنك بالعربية (اختياري)',
    photo:'صورتك (مطلوبة، JPG أو PNG حتى 5 ميغابايت)',port:'أعمالك (مطلوبة، حتى 5 صور)',portHint:'اختر الأعمال التي تفخر بها أكثر.',
    video:'مقطع قصير وأنت تعمل (مطلوب، MP4 حتى 50 ميغابايت)',videoHint:'صوّر يديك وهي ترسم أو تلوّن. هكذا نعرف أن الفن من صنعك.',
    pass:'كلمة المرور',confirm:'تأكيد كلمة المرور',
    rlen:'الحد الأدنى 8 أحرف',rupper:'حرف كبير واحد على الأقل',rnum:'رقم واحد على الأقل',rspec:'رمز خاص واحد على الأقل (!@#$%^&*)',
    submit:'أرسل للمراجعة',have:'أنت معنا بالفعل؟',login:'تسجيل الدخول',
    nav:{community:'المجتمع',marketplace:'المعرض',explore:'استكشف أساليب الرسم',support:'الدعم',
      c2:'تعرّف على فنانين',c3:'شارك أعمالك',c4:'تعلّم معاً',
      sp1:'تواصل معنا',sp2:'كيف يعمل الموقع',sp3:'شروط الاستخدام',login:'تسجيل الدخول',reg:'انضم مجاناً'}}
};
let L='en';
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
  setTxt('t-l-video',t.video);setTxt('t-video-hint',t.videoHint);
  setTxt('t-l-pass',t.pass);setTxt('t-l-confirm',t.confirm);
  setTxt('t-r-len',t.rlen);setTxt('t-r-upper',t.rupper);setTxt('t-r-num',t.rnum);setTxt('t-r-spec',t.rspec);
  setTxt('t-submit',t.submit);setTxt('t-have',t.have);setTxt('t-login',t.login);
  const err=document.getElementById('err');if(err){const v=l==='ar'?err.dataset.ar:err.dataset.en;if(v)err.textContent=v;}
  const ok=document.getElementById('ok');if(ok){const v=l==='ar'?ok.dataset.ar:ok.dataset.en;if(v)ok.textContent=v;}
  const n=t.nav;
  setTxt('nav-community-label',n.community);
  setTxt('nav-marketplace-label',n.marketplace);setTxt('nav-explore-label',n.explore);setTxt('nav-support-label',n.support);
  setTxt('dd-c2',n.c2);setTxt('dd-c3',n.c3);setTxt('dd-c4',n.c4);
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
