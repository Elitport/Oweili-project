<?php
require_once 'config.php';

/* ---- Handle submit (before any output) ---- */
$errCode = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errCode = 'csrf';
    } elseif (isRateLimited()) {
        $errCode = 'rate';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $pass  = (string) ($_POST['password'] ?? '');
        $stmt  = getDB()->prepare('SELECT * FROM users WHERE email = :e LIMIT 1');
        $stmt->execute([':e' => $email]);
        $u = $stmt->fetch();

        if ($u && verifyPassword($pass, $u['password'])) {
            recordLoginAttempt($email, true);
            loginUser((int) $u['id']);            // regenerates session + stores token + last_login_at
            $role = $u['role'];
            header('Location: ' . dashboardUrl($role));
            exit;
        } else {
            recordLoginAttempt($email, false);
            $errCode = 'invalid';
        }
    }
}

/* Bilingual error strings */
$ERR = [
    'csrf'    => ['en' => 'Security check failed. Please try again.', 'ar' => 'فشل التحقق الأمني. حاول مرة أخرى.'],
    'rate'    => ['en' => 'Too many attempts. Please wait 10 minutes.', 'ar' => 'محاولات كثيرة جداً. يرجى الانتظار 10 دقائق.'],
    'invalid' => ['en' => 'Invalid email or password.', 'ar' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.'],
];
$errEn = $errCode ? $ERR[$errCode]['en'] : '';
$errAr = $errCode ? $ERR[$errCode]['ar'] : '';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" id="html">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In · Oweili</title>
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:#fff;font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;overflow-x:hidden;}
.bg-wrap{position:fixed;inset:0;z-index:0;overflow:hidden;}
.bg-video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0;transition:opacity 2s ease;z-index:2;}
.bg-video.on{opacity:0.95;}
.overlay{position:fixed;inset:0;z-index:3;pointer-events:none;background:linear-gradient(160deg,rgba(255,255,255,0.18) 0%,rgba(255,255,255,0) 50%,rgba(255,255,255,0.35) 100%);}
/* ── TOP NAV (shared brand navbar) ── */
.topnav{position:fixed;top:0;left:0;right:0;z-index:200;display:flex;align-items:center;justify-content:space-between;padding:0 16px;height:56px;background:rgba(255,255,255,0.6);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-bottom:1px solid rgba(0,0,0,0.05);}
[dir="rtl"] .dropdown{left:auto;right:0;text-align:right;}
.nav-logo{display:flex;align-items:center;gap:6px;text-decoration:none;color:#111111;flex-shrink:0;}
.nav-logo svg{color:#ff0055;}
.nav-logo span{font-size:12px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;}
.nav-center{display:flex;align-items:stretch;gap:0;height:100%;}
.nav-item{position:relative;display:flex;align-items:center;}
.nav-item > a{display:flex;align-items:center;gap:4px;padding:0 12px;height:100%;font-size:10px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:rgba(0,0,0,0.65);text-decoration:none;transition:color 0.2s;white-space:nowrap;border-bottom:2px solid transparent;}
.nav-item > a:hover{color:#000;}
.nav-item:hover > a{color:#0055ff;border-bottom-color:#0055ff;}
.nav-item > a .chevron{width:10px;height:10px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;transition:transform 0.2s;flex-shrink:0;}
.nav-item:hover > a .chevron{transform:rotate(180deg);}
.dropdown{position:absolute;top:100%;left:0;min-width:200px;background:rgba(255,255,255,0.96);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(0,0,0,0.07);border-radius:14px;box-shadow:0 16px 40px rgba(0,0,0,0.1);padding:8px;opacity:0;visibility:hidden;transform:translateY(8px);transition:opacity 0.2s,transform 0.2s,visibility 0.2s;pointer-events:none;}
.nav-item:hover .dropdown{opacity:1;visibility:visible;transform:translateY(0);pointer-events:auto;}
.dropdown a{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:9px;font-size:12px;color:rgba(0,0,0,0.7);text-decoration:none;transition:background 0.15s,color 0.15s;}
.dropdown a:hover{background:rgba(0,100,255,0.07);color:#0055ff;}
.dropdown a .d-icon{width:14px;height:14px;fill:currentColor;opacity:0.6;flex-shrink:0;}
.nav-right{display:flex;align-items:center;gap:6px;flex-shrink:0;}
.nav-btn{padding:5px 12px;border-radius:999px;font-size:10px;font-weight:600;letter-spacing:0.04em;text-transform:uppercase;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;transition:all 0.22s;border:1px solid rgba(0,0,0,0.1);background:rgba(0,0,0,0.04);color:rgba(0,0,0,0.8);}
.nav-btn:hover{background:rgba(0,0,0,0.08);color:#000;}
.nav-btn.primary{background:rgba(0,100,255,0.1);border-color:rgba(0,100,255,0.2);color:#0066ff;}
.nav-btn.primary:hover{background:rgba(0,100,255,0.18);}
.lang-btn{padding:4px 10px;border-radius:999px;font-size:10px;font-weight:600;letter-spacing:0.04em;cursor:pointer;border:1px solid rgba(0,0,0,0.1);background:rgba(255,255,255,0.7);color:rgba(0,0,0,0.7);backdrop-filter:blur(8px);transition:all 0.22s;}
.lang-btn:hover{background:#111111;color:#fff;}
@media(max-width:1024px){.nav-center{display:none;}}

/* ── AUTH CARD ── */
.auth-wrap{position:relative;z-index:10;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:90px 20px 40px;}
.auth-card{width:100%;max-width:420px;background:rgba(255,255,255,0.6);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(0,0,0,0.07);border-radius:20px;box-shadow:0 20px 50px rgba(0,0,0,0.1);padding:36px 32px;animation:riseUp 0.8s cubic-bezier(0.22,1,0.36,1) forwards;opacity:0;}
.auth-head{margin-bottom:24px;text-align:center;}
.auth-head h1{font-size:26px;font-weight:700;color:#111111;margin-bottom:6px;}
.auth-head p{font-size:13px;color:rgba(0,0,0,0.55);}
.field{margin-bottom:16px;}
.field label{display:block;font-size:11px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:rgba(0,0,0,0.55);margin-bottom:6px;}
.field input[type=email],.field input[type=password],.field input[type=text]{width:100%;padding:12px 14px;border-radius:12px;border:1px solid rgba(0,0,0,0.12);background:rgba(255,255,255,0.7);font-size:14px;color:#111111;transition:border 0.2s,box-shadow 0.2s;font-family:inherit;}
.field input:focus{outline:none;border-color:#0066ff;box-shadow:0 0 0 3px rgba(0,100,255,0.12);}
.pw-wrap{position:relative;}
.pw-toggle{position:absolute;top:50%;transform:translateY(-50%);right:10px;background:none;border:none;cursor:pointer;font-size:11px;font-weight:600;color:#0066ff;text-transform:uppercase;letter-spacing:0.04em;padding:4px;}
[dir="rtl"] .pw-toggle{right:auto;left:10px;}
.row-between{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;font-size:12px;}
[dir="rtl"] .row-between{flex-direction:row-reverse;}
.remember{display:flex;align-items:center;gap:7px;color:rgba(0,0,0,0.65);cursor:pointer;}
[dir="rtl"] .remember{flex-direction:row-reverse;}
.remember input{width:15px;height:15px;accent-color:#ff0055;cursor:pointer;}
.link{color:#0066ff;text-decoration:none;font-weight:600;}
.link:hover{text-decoration:underline;}
.btn-submit{width:100%;padding:13px;border-radius:999px;background:#111111;color:#fff;font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;border:none;cursor:pointer;transition:all 0.25s;}
.btn-submit:hover{background:#ff0055;transform:translateY(-2px);}
.auth-foot{margin-top:20px;text-align:center;font-size:13px;color:rgba(0,0,0,0.6);}
.err-box{background:rgba(255,0,85,0.08);border:1px solid rgba(255,0,85,0.25);color:#ff0055;font-size:13px;font-weight:600;padding:11px 14px;border-radius:12px;margin-bottom:18px;}
[dir="rtl"] .auth-card{text-align:right;}
@keyframes riseUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
@media(max-width:768px){.topnav{padding:10px 14px;}.nav-logo span{display:none;}}
</style>
</head>
<body>
<div class="bg-wrap"><video class="bg-video" id="vid" autoplay loop muted playsinline src="bg.mp4"></video></div>
<div class="overlay"></div>

<!-- NAV (shared brand navbar) -->
<?php include __DIR__ . "/nav.php"; ?>

<!-- LOGIN CARD -->
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-head">
      <h1 id="t-title">Sign In</h1>
      <p id="t-sub">Welcome back to Oweili</p>
    </div>

    <div class="err-box" id="err"
         data-en="<?= e($errEn) ?>" data-ar="<?= e($errAr) ?>"
         style="display:<?= $errCode ? 'block' : 'none' ?>;"><?= e($errEn) ?></div>

    <form method="post" action="login.php" autocomplete="on">
      <?= csrfField() ?>
      <div class="field">
        <label id="t-l-email" for="email">Email</label>
        <input type="email" id="email" name="email" required
               value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="field">
        <label id="t-l-pass" for="password">Password</label>
        <div class="pw-wrap">
          <input type="password" id="password" name="password" required>
          <button type="button" class="pw-toggle" id="pw-toggle" onclick="togglePw('password',this)">SHOW</button>
        </div>
      </div>
      <div class="row-between">
        <label class="remember"><input type="checkbox" name="remember" value="1"><span id="t-remember">Remember me</span></label>
        <a class="link" href="forgot.php" id="t-forgot">Forgot password?</a>
      </div>
      <button type="submit" class="btn-submit" id="t-submit">Sign In</button>
    </form>

    <div class="auth-foot">
      <span id="t-noacc">Don't have an account?</span>
      <a class="link" href="register.php" id="t-joinlink">Join Free</a>
    </div>
  </div>
</div>

<script>
const T={
  en:{dir:'ltr',lb:'العربية',title:'Sign In',sub:'Welcome back to Oweili',
    email:'Email',pass:'Password',remember:'Remember me',forgot:'Forgot password?',
    submit:'Sign In',noacc:"Don't have an account?",joinlink:'Join Free',show:'SHOW',hide:'HIDE',
    nav:{studio:'The Studio',community:'Community',marketplace:'Marketplace',explore:'Explore Art Styles',support:'Support',
      s1:'Watercolor Workshop',s2:'Oil Painting Studio',s3:'Digital Art Lab',s4:'Charcoal & Ink',
      c1:'Artist Chat Room',c2:'Meet Fellow Artists',c3:'Share Your Work',c4:'Learn Together',
      sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',login:'Sign In',reg:'Join Free'}},
  ar:{dir:'rtl',lb:'English',title:'تسجيل الدخول',sub:'مرحباً بعودتك إلى أويلي',
    email:'البريد الإلكتروني',pass:'كلمة المرور',remember:'تذكرني',forgot:'نسيت كلمة المرور؟',
    submit:'تسجيل الدخول',noacc:'ليس لديك حساب؟',joinlink:'انضم مجاناً',show:'إظهار',hide:'إخفاء',
    nav:{studio:'الاستوديو',community:'المجتمع',marketplace:'السوق',explore:'استكشف أساليب الرسم',support:'الدعم',
      s1:'ورشة الألوان المائية',s2:'استوديو الرسم الزيتي',s3:'مختبر الفن الرقمي',s4:'الفحم والحبر',
      c1:'غرفة محادثة الفنانين',c2:'تعرّف على فنانين',c3:'شارك أعمالك',c4:'تعلّم معاً',
      sp1:'تواصل معنا',sp2:'كيف يعمل الموقع',sp3:'شروط الاستخدام',login:'تسجيل الدخول',reg:'انضم مجاناً'}}
};
let L='en';
function togglePw(id,btn){
  const f=document.getElementById(id);
  const show=f.type==='password';
  f.type=show?'text':'password';
  btn.textContent=show?T[L].hide:T[L].show;
  btn.dataset.state=show?'shown':'hidden';
}
function setTxt(id,v){const el=document.getElementById(id);if(el)el.textContent=v;}
function apply(l){
  const t=T[l];
  document.getElementById('html').lang=l;
  document.getElementById('html').setAttribute('dir',t.dir);
  document.documentElement.setAttribute('dir',t.dir);
  document.getElementById('topnav').setAttribute('dir',t.dir);
  document.getElementById('lb').textContent=t.lb;
  setTxt('t-title',t.title);setTxt('t-sub',t.sub);
  setTxt('t-l-email',t.email);setTxt('t-l-pass',t.pass);
  setTxt('t-remember',t.remember);setTxt('t-forgot',t.forgot);
  setTxt('t-submit',t.submit);setTxt('t-noacc',t.noacc);setTxt('t-joinlink',t.joinlink);
  // password toggle label respects current shown/hidden state
  const pt=document.getElementById('pw-toggle');
  if(pt) pt.textContent=(pt.dataset.state==='shown')?t.hide:t.show;
  // error message in current language
  const err=document.getElementById('err');
  if(err){const v=l==='ar'?err.dataset.ar:err.dataset.en;if(v)err.textContent=v;}
  // navbar
  const n=t.nav;
  setTxt('nav-studio-label',n.studio);setTxt('nav-community-label',n.community);
  setTxt('nav-marketplace-label',n.marketplace);setTxt('nav-explore-label',n.explore);setTxt('nav-support-label',n.support);
  setTxt('dd-s1',n.s1);setTxt('dd-s2',n.s2);setTxt('dd-s3',n.s3);setTxt('dd-s4',n.s4);
  setTxt('dd-c1',n.c1);setTxt('dd-c2',n.c2);setTxt('dd-c3',n.c3);setTxt('dd-c4',n.c4);
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
