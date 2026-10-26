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
<link rel="stylesheet" href="/assets/css/oweili.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:#fff;font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;overflow-x:hidden;}
.bg-wrap{position:fixed;inset:0;z-index:0;overflow:hidden;}
.bg-video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0;transition:opacity 2s ease;z-index:2;}
.bg-video.on{opacity:0.95;}
.overlay{position:fixed;inset:0;z-index:3;pointer-events:none;background:linear-gradient(160deg,rgba(255,255,255,0.18) 0%,rgba(255,255,255,0) 50%,rgba(255,255,255,0.35) 100%);}
.topnav{position:fixed;top:0;left:0;right:0;z-index:200;display:flex;align-items:center;justify-content:space-between;padding:0 16px;height:56px;background:rgba(255,255,255,0.6);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-bottom:1px solid rgba(0,0,0,0.05);}
[dir="rtl"] .dropdown{left:auto;right:0;text-align:right;}
.nav-logo{display:flex;align-items:center;gap:6px;text-decoration:none;color:#111111;flex-shrink:0;}
.nav-logo svg{color:#ff0055;}
.nav-logo span{font-size:12px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;}
.nav-center{display:flex;align-items:stretch;gap:0;height:100%;}
.nav-item{position:relative;display:flex;align-items:center;}
.nav-item > a{display:flex;align-items:center;gap:4px;padding:0 12px;height:100%;font-size:12px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:rgba(0,0,0,0.65);text-decoration:none;transition:color 0.2s;white-space:nowrap;border-bottom:2px solid transparent;}
.nav-item:hover > a{color:#0066ff;border-bottom-color:#0066ff;}
.nav-item > a .chevron{width:10px;height:10px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;flex-shrink:0;}
.dropdown{position:absolute;top:100%;left:0;min-width:200px;background:rgba(255,255,255,0.96);backdrop-filter:blur(20px);border:1px solid rgba(0,0,0,0.07);border-radius:14px;box-shadow:0 16px 40px rgba(0,0,0,0.1);padding:8px;opacity:0;visibility:hidden;transform:translateY(8px);transition:all 0.2s;pointer-events:none;}
.nav-item:hover .dropdown{opacity:1;visibility:visible;transform:translateY(0);pointer-events:auto;}
.dropdown a{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:9px;font-size:12px;color:rgba(0,0,0,0.7);text-decoration:none;}
.dropdown a:hover{background:rgba(0,100,255,0.07);color:#0066ff;}
.dropdown a .d-icon{width:14px;height:14px;fill:currentColor;opacity:0.6;flex-shrink:0;}
.nav-right{display:flex;align-items:center;gap:6px;flex-shrink:0;}
.nav-btn{padding:5px 12px;border-radius:999px;font-size:12px;font-weight:600;letter-spacing:0.04em;text-transform:uppercase;text-decoration:none;display:inline-flex;align-items:center;gap:4px;transition:all 0.22s;border:1px solid rgba(0,0,0,0.1);background:rgba(0,0,0,0.04);color:rgba(0,0,0,0.8);}
.lang-btn{padding:4px 10px;border-radius:999px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid rgba(0,0,0,0.1);background:rgba(255,255,255,0.7);color:rgba(0,0,0,0.7);}
@media(max-width:1024px){.nav-center{display:none;}}
.auth-wrap{position:relative;z-index:10;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:90px 20px 40px;}
.auth-card{width:100%;max-width:460px;background:rgba(255,255,255,0.65);backdrop-filter:blur(16px);border:1px solid rgba(0,0,0,0.07);border-radius:20px;box-shadow:0 20px 50px rgba(0,0,0,0.1);padding:36px 32px;}
.auth-head{text-align:center;margin-bottom:22px;}
.auth-head h1{font-size:24px;font-weight:700;color:#111;margin-bottom:6px;}
.auth-head p{font-size:13px;color:rgba(0,0,0,0.55);}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.field{margin-bottom:14px;}
.field label{display:block;font-size:12px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:rgba(0,0,0,0.55);margin-bottom:6px;}
.field input{width:100%;padding:11px 14px;border-radius:12px;border:1px solid rgba(0,0,0,0.12);background:rgba(255,255,255,0.7);font-size:14px;color:#111;font-family:inherit;}
.field input:focus{outline:none;border-color:#0066ff;box-shadow:0 0 0 3px rgba(0,100,255,0.12);}
.pw-rules{list-style:none;margin:6px 0 4px;padding:10px 14px;background:rgba(0,0,0,0.03);border-radius:12px;}
.pw-rules li{font-size:12px;color:rgba(0,0,0,0.55);padding:3px 0;display:flex;align-items:center;gap:8px;}

.pw-rules li .mark{width:16px;height:16px;border-radius:50%;border:1px solid rgba(0,0,0,0.2);display:inline-flex;align-items:center;justify-content:center;font-size:12px;color:transparent;flex-shrink:0;}
.pw-rules li.ok{color:#0066ff;}.pw-rules li.ok .mark{background:#0066ff;border-color:#0066ff;color:#fff;}
.btn-submit{width:100%;padding:13px;border-radius:999px;background:#0066ff;color:#fff;font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;border:none;cursor:pointer;transition:all 0.25s;}
.btn-submit:hover{background:#111;transform:translateY(-2px);}
.auth-foot{margin-top:20px;text-align:center;font-size:13px;color:rgba(0,0,0,0.6);}
.link{color:#0066ff;text-decoration:none;font-weight:600;}
.err-box{background:rgba(255,0,85,0.08);border:1px solid rgba(255,0,85,0.25);color:#111111;font-size:13px;font-weight:600;padding:11px 14px;border-radius:12px;margin-bottom:16px;}
.ok-box{background:rgba(0,102,255,0.1);border:1px solid rgba(0,102,255,0.3);color:#0066ff;font-size:13px;font-weight:600;padding:11px 14px;border-radius:12px;margin-bottom:16px;}
[dir="rtl"] .auth-card{text-align:right;}
@media(max-width:768px){.topnav{padding:10px 14px;}.nav-logo span{display:none;}.grid2{grid-template-columns:1fr;}}
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
      <button type="submit" class="btn-submit" id="t-submit">Create Account</button>
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
    nav:{studio:'The Studio',community:'Community',marketplace:'Marketplace',explore:'Explore Art Styles',support:'Support',
      s1:'Watercolor Workshop',s2:'Oil Painting Studio',s3:'Digital Art Lab',s4:'Charcoal & Ink',
      c2:'Meet Fellow Artists',c3:'Share Your Work',c4:'Learn Together',
      sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',login:'Sign In',reg:'Join Free'}},
  ar:{dir:'rtl',lb:'English',title:'تسجيل مقتني',sub:'سريع وسهل — ابدأ الاقتناء.',
    first:'الاسم الأول',last:'اسم العائلة',email:'البريد الإلكتروني',phone:'رقم الجوال',pass:'كلمة المرور',confirm:'تأكيد كلمة المرور',
    rlen:'الحد الأدنى 8 أحرف',rupper:'حرف كبير واحد على الأقل',rnum:'رقم واحد على الأقل',rspec:'رمز خاص واحد على الأقل (!@#$%^&*)',
    submit:'إنشاء الحساب',have:'لديك حساب بالفعل؟',login:'تسجيل الدخول',
    nav:{studio:'الاستوديو',community:'المجتمع',marketplace:'السوق',explore:'استكشف أساليب الرسم',support:'الدعم',
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
if(v){v.addEventListener('canplay',()=>v.classList.add('on'),{once:true});v.play().catch(()=>{});}
</script>
</body>
</html>
