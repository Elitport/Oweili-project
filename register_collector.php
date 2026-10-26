<?php
require_once 'config.php';

$errCode = '';
$okCode  = '';
$old = ['first' => '', 'last' => '', 'email' => '', 'phone' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $old['first'] = sanitize($_POST['first_name'] ?? '');
    $old['last']  = sanitize($_POST['last_name'] ?? '');
    $old['email'] = sanitize($_POST['email'] ?? '');
    $old['phone'] = sanitize($_POST['phone'] ?? '');
    $pass    = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');

    $strong = strlen($pass) >= 8 && preg_match('/[A-Z]/', $pass) && preg_match('/[0-9]/', $pass) && preg_match('/[!@#$%^&*]/', $pass);
    $fullName = trim($old['first'] . ' ' . $old['last']);

    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errCode = 'csrf';
    } elseif ($old['first'] === '' || $old['email'] === '' || $old['phone'] === '') {
        $errCode = 'required';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errCode = 'email';
    } elseif (!preg_match('/^[+0-9][0-9 \-()]{6,29}$/', $old['phone'])) {
        $errCode = 'phone';
    } elseif (!$strong) {
        $errCode = 'weak';
    } elseif ($pass !== $confirm) {
        $errCode = 'mismatch';
    } else {
        $stmt = getDB()->prepare('SELECT 1 FROM users WHERE email = :e LIMIT 1');
        $stmt->execute([':e' => $old['email']]);
        if ($stmt->fetchColumn()) {
            $errCode = 'exists';
        } else {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 24 * 60 * 60);
            getDB()->prepare(
                'INSERT INTO users (email, password, full_name_en, phone, role, is_verified, is_approved, verification_token, verification_expires)
                 VALUES (:email,:pass,:name,:phone,"collector",0,0,:tok,:exp)'
            )->execute([
                ':email' => $old['email'], ':pass' => hashPassword($pass), ':name' => $fullName,
                ':phone' => $old['phone'], ':tok' => $token, ':exp' => $expires,
            ]);

            $verifyUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'oweili.com') . '/verify.php?token=' . $token;
            $body = "Welcome!\r\n\r\nPlease verify your account by clicking the link below:\r\n" . $verifyUrl . "\r\n\r\nThis link expires in 24 hours.\r\n";
            if (!sendEmail($old['email'], 'Verify your account', $body)) {
                error_log('register_collector: verification email failed to ' . $old['email']);
            }
            $okCode = 'check_email';
            $old = ['first' => '', 'last' => '', 'email' => '', 'phone' => ''];
        }
    }
}

$ERR = [
    'csrf'=>['en'=>'Security check failed. Please try again.','ar'=>'فشل التحقق الأمني. حاول مرة أخرى.'],
    'required'=>['en'=>'Please fill in the required fields.','ar'=>'يرجى تعبئة الحقول المطلوبة.'],
    'email'=>['en'=>'Please enter a valid email address.','ar'=>'يرجى إدخال بريد إلكتروني صالح.'],
    'phone'=>['en'=>'Please enter a valid mobile number.','ar'=>'يرجى إدخال رقم جوال صالح.'],
    'weak'=>['en'=>'Password does not meet the requirements.','ar'=>'كلمة المرور لا تستوفي الشروط.'],
    'mismatch'=>['en'=>'Passwords do not match.','ar'=>'كلمتا المرور غير متطابقتين.'],
    'exists'=>['en'=>'An account with this email already exists.','ar'=>'يوجد حساب بهذا البريد الإلكتروني بالفعل.'],
];
$OK = ['check_email'=>['en'=>'Please check your email to verify your account.','ar'=>'يرجى مراجعة بريدك لتفعيل حسابك.']];
$errEn = $errCode ? $ERR[$errCode]['en'] : ''; $errAr = $errCode ? $ERR[$errCode]['ar'] : '';
$okEn = $okCode ? $OK[$okCode]['en'] : ''; $okAr = $okCode ? $OK[$okCode]['ar'] : '';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" id="html">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Collector Sign-up · Oweili</title>
<link rel="stylesheet" href="/assets/css/oweili.css?v=<?= @filemtime(__DIR__ . '/assets/css/oweili.css') ?>">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:#fff;font-family:var(--font-sans);overflow-x:hidden;}
</style>
</head>
<body>
<div class="bg-wrap"><video class="bg-video" id="vid" autoplay loop muted playsinline src="bg.mp4"></video></div>
<div class="overlay"></div>
<?php include __DIR__ . "/nav.php"; ?>

<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-head"><h1 id="t-title">Collector Sign-up</h1><p id="t-sub">Quick and easy — start collecting.</p></div>
    <div class="err-box" id="err" data-en="<?= e($errEn) ?>" data-ar="<?= e($errAr) ?>" style="display:<?= $errCode?'block':'none' ?>;"><?= e($errEn) ?></div>
    <div class="ok-box" id="ok" data-en="<?= e($okEn) ?>" data-ar="<?= e($okAr) ?>" style="display:<?= $okCode?'block':'none' ?>;"><?= e($okEn) ?></div>
    <form method="post" action="register_collector.php">
      <?= csrfField() ?>
      <div class="grid2">
        <div class="field"><label id="t-l-first" for="first_name">First name</label><input type="text" id="first_name" name="first_name" required value="<?= e($old['first']) ?>"></div>
        <div class="field"><label id="t-l-last" for="last_name">Last name</label><input type="text" id="last_name" name="last_name" value="<?= e($old['last']) ?>"></div>
      </div>
      <div class="field"><label id="t-l-email" for="email">Email</label><input type="email" id="email" name="email" required value="<?= e($old['email']) ?>"></div>
      <div class="field"><label id="t-l-phone" for="phone">Mobile number</label><input type="tel" id="phone" name="phone" dir="ltr" required value="<?= e($old['phone']) ?>" placeholder="+966 5X XXX XXXX"></div>
      <div class="field"><label id="t-l-pass" for="password">Password</label><input type="password" id="password" name="password" required oninput="checkRules()">
        <ul class="pw-rules">
          <li id="r-len"><span class="mark">✓</span><span id="t-r-len">Minimum 8 characters</span></li>
          <li id="r-upper"><span class="mark">✓</span><span id="t-r-upper">At least one uppercase letter</span></li>
          <li id="r-num"><span class="mark">✓</span><span id="t-r-num">At least one number</span></li>
          <li id="r-spec"><span class="mark">✓</span><span id="t-r-spec">At least one special character (!@#$%^&*)</span></li>
        </ul>
      </div>
      <div class="field"><label id="t-l-confirm" for="confirm">Confirm password</label><input type="password" id="confirm" name="confirm" required></div>
      <button type="submit" class="btn btn-accent btn-block btn-submit" id="t-submit">Create Account</button>
    </form>
    <div class="auth-foot"><span id="t-have">Already have an account?</span> <a class="link" href="login.php" id="t-login">Sign In</a></div>
  </div>
</div>

<script>
const T={
  en:{dir:'ltr',lb:'العربية',title:'Collector Sign-up',sub:'Quick and easy — start collecting.',
    first:'First name',last:'Last name',email:'Email',phone:'Mobile number',pass:'Password',confirm:'Confirm password',
    rlen:'Minimum 8 characters',rupper:'At least one uppercase letter',rnum:'At least one number',rspec:'At least one special character (!@#$%^&*)',
    submit:'Create Account',have:'Already have an account?',login:'Sign In',
    nav:{studio:'The Studio',community:'Community',marketplace:'Gallery',explore:'Explore Art Styles',support:'Support',
      s1:'Watercolor Workshop',s2:'Oil Painting Studio',s3:'Digital Art Lab',s4:'Charcoal & Ink',
      c2:'Meet Fellow Artists',c3:'Share Your Work',c4:'Learn Together',
      sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',login:'Sign In',reg:'Join Free'}},
  ar:{dir:'rtl',lb:'English',title:'تسجيل مقتني',sub:'سريع وسهل — ابدأ الاقتناء.',
    first:'الاسم الأول',last:'اسم العائلة',email:'البريد الإلكتروني',phone:'رقم الجوال',pass:'كلمة المرور',confirm:'تأكيد كلمة المرور',
    rlen:'الحد الأدنى 8 أحرف',rupper:'حرف كبير واحد على الأقل',rnum:'رقم واحد على الأقل',rspec:'رمز خاص واحد على الأقل (!@#$%^&*)',
    submit:'إنشاء الحساب',have:'لديك حساب بالفعل؟',login:'تسجيل الدخول',
    nav:{studio:'الاستوديو',community:'المجتمع',marketplace:'المعرض',explore:'استكشف أساليب الرسم',support:'الدعم',
      s1:'ورشة الألوان المائية',s2:'استوديو الرسم الزيتي',s3:'مختبر الفن الرقمي',s4:'الفحم والحبر',
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
  setTxt('t-l-first',t.first);setTxt('t-l-last',t.last);setTxt('t-l-email',t.email);setTxt('t-l-phone',t.phone);
  setTxt('t-l-pass',t.pass);setTxt('t-l-confirm',t.confirm);
  setTxt('t-r-len',t.rlen);setTxt('t-r-upper',t.rupper);setTxt('t-r-num',t.rnum);setTxt('t-r-spec',t.rspec);
  setTxt('t-submit',t.submit);setTxt('t-have',t.have);setTxt('t-login',t.login);
  const err=document.getElementById('err');if(err){const v=l==='ar'?err.dataset.ar:err.dataset.en;if(v)err.textContent=v;}
  const ok=document.getElementById('ok');if(ok){const v=l==='ar'?ok.dataset.ar:ok.dataset.en;if(v)ok.textContent=v;}
  const n=t.nav;
  setTxt('nav-studio-label',n.studio);setTxt('nav-community-label',n.community);
  setTxt('nav-marketplace-label',n.marketplace);setTxt('nav-explore-label',n.explore);setTxt('nav-support-label',n.support);
  setTxt('dd-s1',n.s1);setTxt('dd-s2',n.s2);setTxt('dd-s3',n.s3);setTxt('dd-s4',n.s4);
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
