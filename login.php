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
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="/assets/css/oweili.css?v=<?= @filemtime(__DIR__ . '/assets/css/oweili.css') ?>">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:var(--color-bg);font-family:var(--font-sans);overflow-x:hidden;}
/* ── AUTH CARD ── */
.pw-wrap{position:relative;}
.pw-toggle{position:absolute;top:50%;transform:translateY(-50%);right:10px;background:none;border:none;cursor:pointer;font-size:12px;font-weight:600;color:var(--color-accent);text-transform:uppercase;letter-spacing:0.04em;padding:4px;}
[dir="rtl"] .pw-toggle{right:auto;left:10px;}
.row-between{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;font-size:12px;}
.remember{display:flex;align-items:center;gap:7px;color:var(--color-body);cursor:pointer;}
.remember input{width:15px;height:15px;accent-color:var(--color-primary);cursor:pointer;}
@keyframes riseUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
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
      <button type="submit" class="btn btn-primary btn-block btn-submit" id="t-submit">Sign In</button>
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
    nav:{community:'Community',marketplace:'Gallery',explore:'Explore Art Styles',support:'Support',
      c2:'Meet Fellow Artists',c3:'Share Your Work',c4:'Learn Together',
      sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',login:'Sign In',reg:'Join Free'}},
  ar:{dir:'rtl',lb:'English',title:'تسجيل الدخول',sub:'مرحباً بعودتك إلى أويلي',
    email:'البريد الإلكتروني',pass:'كلمة المرور',remember:'تذكرني',forgot:'نسيت كلمة المرور؟',
    submit:'تسجيل الدخول',noacc:'ليس لديك حساب؟',joinlink:'انضم مجاناً',show:'إظهار',hide:'إخفاء',
    nav:{community:'المجتمع',marketplace:'المعرض',explore:'استكشف أساليب الرسم',support:'الدعم',
      c2:'تعرّف على فنانين',c3:'شارك أعمالك',c4:'تعلّم معاً',
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
