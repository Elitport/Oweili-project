<?php
require_once 'config.php';

/* forgot.php — request a reset link, or set a new password via ?token= */

$token   = isset($_GET['token']) ? sanitize($_GET['token']) : (isset($_POST['token']) ? sanitize($_POST['token']) : '');
$mode    = ($token !== '') ? 'reset' : 'request';
$errCode = '';
$okCode  = '';

/* Validate a token against the DB (non-expired). Returns user id or 0. */
function resetUserForToken(string $token): int {
    if ($token === '' || !ctype_xdigit($token) || strlen($token) > 128) return 0;
    $stmt = getDB()->prepare(
        'SELECT id FROM users
         WHERE reset_token = :t AND (reset_expires IS NULL OR reset_expires > NOW())
         LIMIT 1'
    );
    $stmt->execute([':t' => $token]);
    return (int) ($stmt->fetchColumn() ?: 0);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {

    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errCode = 'csrf';
    }
    /* ---- Step 1: request a reset link ---- */
    elseif ($mode === 'request') {
        $email = sanitize($_POST['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errCode = 'email';
        } else {
            $stmt = getDB()->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');
            $stmt->execute([':e' => $email]);
            $uid = (int) ($stmt->fetchColumn() ?: 0);

            if ($uid > 0) {
                $rtok    = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 60 * 60); // 1 hour
                getDB()->prepare('UPDATE users SET reset_token = :t, reset_expires = :x WHERE id = :id')
                       ->execute([':t' => $rtok, ':x' => $expires, ':id' => $uid]);

                $resetUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'oweili.com') . '/forgot.php?token=' . $rtok;
                $subject  = 'Reset your password';
                $body     = "You (or someone) requested a password reset.\r\n\r\n"
                          . "Open this link to set a new password (valid for 1 hour):\r\n"
                          . $resetUrl . "\r\n\r\nIf you didn't request this, ignore this email.\r\n";

                // Authenticated SMTP only (Hostinger mail() is unreliable).
                if (!sendEmail($email, $subject, $body)) {
                    error_log('forgot.php: password-reset email failed to send to ' . $email);
                }
            }
            // Always show the same message (don't reveal whether the email exists).
            $okCode = 'sent';
        }
    }
    /* ---- Step 2: set the new password ---- */
    else {
        $uid  = resetUserForToken($token);
        $pass = (string) ($_POST['password'] ?? '');
        $conf = (string) ($_POST['confirm'] ?? '');
        $strong = strlen($pass) >= 8 && preg_match('/[A-Z]/', $pass)
                && preg_match('/[0-9]/', $pass) && preg_match('/[!@#$%^&*]/', $pass);

        if ($uid === 0)          { $errCode = 'token'; $mode = 'request'; }
        elseif (!$strong)        { $errCode = 'weak'; }
        elseif ($pass !== $conf) { $errCode = 'mismatch'; }
        else {
            getDB()->prepare('UPDATE users SET password = :p, reset_token = NULL, reset_expires = NULL WHERE id = :id')
                   ->execute([':p' => hashPassword($pass), ':id' => $uid]);
            $okCode = 'done';
            $mode   = 'done';
        }
    }
}

/* If arriving via GET with an invalid/expired token, fall back to request mode. */
if ($mode === 'reset' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && resetUserForToken($token) === 0) {
    $mode = 'request';
    $errCode = 'token';
}

$ERR = [
    'csrf'     => ['en' => 'Security check failed. Please try again.', 'ar' => 'فشل التحقق الأمني. حاول مرة أخرى.'],
    'email'    => ['en' => 'Please enter a valid email address.', 'ar' => 'يرجى إدخال بريد إلكتروني صالح.'],
    'token'    => ['en' => 'This reset link is invalid or has expired.', 'ar' => 'رابط إعادة التعيين غير صالح أو منتهي.'],
    'weak'     => ['en' => 'Password does not meet the requirements.', 'ar' => 'كلمة المرور لا تستوفي الشروط.'],
    'mismatch' => ['en' => 'Passwords do not match.', 'ar' => 'كلمتا المرور غير متطابقتين.'],
];
$OK = [
    'sent' => ['en' => 'If that email is registered, a reset link has been sent. Please check your inbox.', 'ar' => 'إذا كان هذا البريد مسجلاً، فقد أُرسل رابط إعادة التعيين. يرجى مراجعة بريدك.'],
    'done' => ['en' => 'Your password has been reset. You can now sign in.', 'ar' => 'تمت إعادة تعيين كلمة المرور. يمكنك الآن تسجيل الدخول.'],
];
$errEn = $errCode ? $ERR[$errCode]['en'] : '';  $errAr = $errCode ? $ERR[$errCode]['ar'] : '';
$okEn  = $okCode ? $OK[$okCode]['en'] : '';      $okAr  = $okCode ? $OK[$okCode]['ar'] : '';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" id="html">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password · Oweili</title>
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
.nav-item > a:hover{color:#000;}
.nav-item:hover > a{color:#0066ff;border-bottom-color:#0066ff;}
.nav-item > a .chevron{width:10px;height:10px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;transition:transform 0.2s;flex-shrink:0;}
.nav-item:hover > a .chevron{transform:rotate(180deg);}
.dropdown{position:absolute;top:100%;left:0;min-width:200px;background:rgba(255,255,255,0.96);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(0,0,0,0.07);border-radius:14px;box-shadow:0 16px 40px rgba(0,0,0,0.1);padding:8px;opacity:0;visibility:hidden;transform:translateY(8px);transition:opacity 0.2s,transform 0.2s,visibility 0.2s;pointer-events:none;}
.nav-item:hover .dropdown{opacity:1;visibility:visible;transform:translateY(0);pointer-events:auto;}
.dropdown a{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:9px;font-size:12px;color:rgba(0,0,0,0.7);text-decoration:none;transition:background 0.15s,color 0.15s;}
.dropdown a:hover{background:rgba(0,100,255,0.07);color:#0066ff;}
.dropdown a .d-icon{width:14px;height:14px;fill:currentColor;opacity:0.6;flex-shrink:0;}
.nav-right{display:flex;align-items:center;gap:6px;flex-shrink:0;}
.nav-btn{padding:5px 12px;border-radius:999px;font-size:12px;font-weight:600;letter-spacing:0.04em;text-transform:uppercase;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;transition:all 0.22s;border:1px solid rgba(0,0,0,0.1);background:rgba(0,0,0,0.04);color:rgba(0,0,0,0.8);}
.nav-btn:hover{background:rgba(0,0,0,0.08);color:#000;}
.nav-btn.primary{background:rgba(0,100,255,0.1);border-color:rgba(0,100,255,0.2);color:#0066ff;}
.nav-btn.primary:hover{background:rgba(0,100,255,0.18);}
.lang-btn{padding:4px 10px;border-radius:999px;font-size:12px;font-weight:600;letter-spacing:0.04em;cursor:pointer;border:1px solid rgba(0,0,0,0.1);background:rgba(255,255,255,0.7);color:rgba(0,0,0,0.7);backdrop-filter:blur(8px);transition:all 0.22s;}
.lang-btn:hover{background:#111111;color:#fff;}
@media(max-width:1024px){.nav-center{display:none;}}

.auth-wrap{position:relative;z-index:10;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:90px 20px 40px;}
.auth-card{width:100%;max-width:420px;background:rgba(255,255,255,0.6);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(0,0,0,0.07);border-radius:20px;box-shadow:0 20px 50px rgba(0,0,0,0.1);padding:36px 32px;animation:riseUp 0.8s cubic-bezier(0.22,1,0.36,1) forwards;opacity:0;}
.auth-head{margin-bottom:22px;text-align:center;}
.auth-head h1{font-size:24px;font-weight:700;color:#111111;margin-bottom:6px;}
.auth-head p{font-size:13px;color:rgba(0,0,0,0.55);}
.field{margin-bottom:16px;}
.field label{display:block;font-size:12px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:rgba(0,0,0,0.55);margin-bottom:6px;}
.field input{width:100%;padding:12px 14px;border-radius:12px;border:1px solid rgba(0,0,0,0.12);background:rgba(255,255,255,0.7);font-size:14px;color:#111111;font-family:inherit;}
.field input:focus{outline:none;border-color:#0066ff;box-shadow:0 0 0 3px rgba(0,100,255,0.12);}
.btn-submit{width:100%;padding:13px;border-radius:999px;background:#111111;color:#fff;font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;border:none;cursor:pointer;transition:all 0.25s;}
.btn-submit:hover{background:#ff0055;transform:translateY(-2px);}
.auth-foot{margin-top:20px;text-align:center;font-size:13px;color:rgba(0,0,0,0.6);}
.link{color:#0066ff;text-decoration:none;font-weight:600;}
.link:hover{text-decoration:underline;}
.err-box{background:rgba(255,0,85,0.08);border:1px solid rgba(255,0,85,0.25);color:#111111;font-size:13px;font-weight:600;padding:11px 14px;border-radius:12px;margin-bottom:16px;}
.ok-box{background:rgba(0,102,255,0.1);border:1px solid rgba(0,102,255,0.3);color:#0066ff;font-size:13px;font-weight:600;padding:11px 14px;border-radius:12px;margin-bottom:16px;}
.rules{list-style:none;margin:0 0 14px;padding:10px 14px;background:rgba(0,0,0,0.03);border-radius:12px;font-size:12px;color:rgba(0,0,0,0.55);}
.rules li{padding:2px 0;}
[dir="rtl"] .auth-card{text-align:right;}
@keyframes riseUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
@media(max-width:768px){.topnav{padding:10px 14px;}.nav-logo span{display:none;}}
</style>
</head>
<body>
<div class="bg-wrap"><video class="bg-video" id="vid" autoplay loop muted playsinline src="bg.mp4"></video></div>
<div class="overlay"></div>

<?php include __DIR__ . "/nav.php"; ?>

<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-head">
      <h1 id="t-title"><?= $mode === 'reset' ? 'Set a new password' : 'Reset password' ?></h1>
      <p id="t-sub"><?= $mode === 'reset' ? 'Choose a new password for your account.' : 'Enter your email and we\'ll send a reset link.' ?></p>
    </div>

    <?php if ($errCode): ?>
      <div class="err-box" id="err" data-en="<?= e($errEn) ?>" data-ar="<?= e($errAr) ?>"><?= e($errEn) ?></div>
    <?php endif; ?>
    <?php if ($okCode): ?>
      <div class="ok-box" id="ok" data-en="<?= e($okEn) ?>" data-ar="<?= e($okAr) ?>"><?= e($okEn) ?></div>
    <?php endif; ?>

    <?php if ($mode === 'done'): ?>
      <div style="text-align:center;"><a class="btn-submit" href="login.php" style="display:inline-block;text-decoration:none;" id="t-go">Go to Sign In</a></div>

    <?php elseif ($mode === 'reset'): ?>
      <form method="post" action="forgot.php">
        <?= csrfField() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="field"><label id="t-l-pass">New password</label>
          <input type="password" name="password" required></div>
        <div class="field"><label id="t-l-conf">Confirm password</label>
          <input type="password" name="confirm" required></div>
        <ul class="rules" id="t-rules">
          <li id="t-r1">Minimum 8 characters</li>
          <li id="t-r2">At least one uppercase letter</li>
          <li id="t-r3">At least one number</li>
          <li id="t-r4">At least one special character (!@#$%^&*)</li>
        </ul>
        <button type="submit" class="btn-submit" id="t-submit">Reset Password</button>
      </form>

    <?php else: ?>
      <form method="post" action="forgot.php">
        <?= csrfField() ?>
        <div class="field"><label id="t-l-email">Email</label>
          <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></div>
        <button type="submit" class="btn-submit" id="t-submit">Send Reset Link</button>
      </form>
    <?php endif; ?>

    <div class="auth-foot">
      <a class="link" href="login.php" id="t-back">Back to Sign In</a>
    </div>
  </div>
</div>

<script>
let L='en';
const MODE = <?= json_encode($mode) ?>;
const NAV={
  en:{lb:'العربية',studio:'The Studio',community:'Community',marketplace:'Marketplace',explore:'Explore Art Styles',support:'Support',
    s1:'Watercolor Workshop',s2:'Oil Painting Studio',s3:'Digital Art Lab',s4:'Charcoal & Ink',
    c1:'Artist Chat Room',c2:'Meet Fellow Artists',c3:'Share Your Work',c4:'Learn Together',
    sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',login:'Sign In',reg:'Join Free',
    titleReq:'Reset password',subReq:"Enter your email and we'll send a reset link.",
    titleRes:'Set a new password',subRes:'Choose a new password for your account.',
    email:'Email',pass:'New password',conf:'Confirm password',
    r1:'Minimum 8 characters',r2:'At least one uppercase letter',r3:'At least one number',r4:'At least one special character (!@#$%^&*)',
    sendBtn:'Send Reset Link',resetBtn:'Reset Password',go:'Go to Sign In',back:'Back to Sign In'},
  ar:{lb:'English',studio:'الاستوديو',community:'المجتمع',marketplace:'السوق',explore:'استكشف أساليب الرسم',support:'الدعم',
    s1:'ورشة الألوان المائية',s2:'استوديو الرسم الزيتي',s3:'مختبر الفن الرقمي',s4:'الفحم والحبر',
    c1:'غرفة محادثة الفنانين',c2:'تعرّف على فنانين',c3:'شارك أعمالك',c4:'تعلّم معاً',
    sp1:'تواصل معنا',sp2:'كيف يعمل الموقع',sp3:'شروط الاستخدام',login:'تسجيل الدخول',reg:'انضم مجاناً',
    titleReq:'إعادة تعيين كلمة المرور',subReq:'أدخل بريدك الإلكتروني وسنرسل لك رابط إعادة التعيين.',
    titleRes:'تعيين كلمة مرور جديدة',subRes:'اختر كلمة مرور جديدة لحسابك.',
    email:'البريد الإلكتروني',pass:'كلمة المرور الجديدة',conf:'تأكيد كلمة المرور',
    r1:'الحد الأدنى 8 أحرف',r2:'حرف كبير واحد على الأقل',r3:'رقم واحد على الأقل',r4:'رمز خاص واحد على الأقل (!@#$%^&*)',
    sendBtn:'إرسال رابط إعادة التعيين',resetBtn:'إعادة تعيين كلمة المرور',go:'الذهاب لتسجيل الدخول',back:'العودة لتسجيل الدخول'}
};
function setTxt(id,v){const el=document.getElementById(id);if(el)el.textContent=v;}
function apply(l){
  L=l; const n=NAV[l];
  document.getElementById('html').lang=l;
  document.getElementById('html').setAttribute('dir', l==='ar'?'rtl':'ltr');
  document.documentElement.setAttribute('dir', l==='ar'?'rtl':'ltr');
  document.getElementById('topnav').setAttribute('dir', l==='ar'?'rtl':'ltr');
  document.getElementById('lb').textContent=n.lb;
  setTxt('nav-studio-label',n.studio);setTxt('nav-community-label',n.community);
  setTxt('nav-marketplace-label',n.marketplace);setTxt('nav-explore-label',n.explore);setTxt('nav-support-label',n.support);
  setTxt('dd-s1',n.s1);setTxt('dd-s2',n.s2);setTxt('dd-s3',n.s3);setTxt('dd-s4',n.s4);
  setTxt('dd-c1',n.c1);setTxt('dd-c2',n.c2);setTxt('dd-c3',n.c3);setTxt('dd-c4',n.c4);
  setTxt('dd-sp1',n.sp1);setTxt('dd-sp2',n.sp2);setTxt('dd-sp3',n.sp3);
  setTxt('n-login-t',n.login);setTxt('n-reg-t',n.reg);
  if(MODE==='reset'){ setTxt('t-title',n.titleRes); setTxt('t-sub',n.subRes);
    setTxt('t-l-pass',n.pass);setTxt('t-l-conf',n.conf);
    setTxt('t-r1',n.r1);setTxt('t-r2',n.r2);setTxt('t-r3',n.r3);setTxt('t-r4',n.r4);
    setTxt('t-submit',n.resetBtn);
  } else if(MODE==='done'){ setTxt('t-go',n.go);
  } else { setTxt('t-title',n.titleReq); setTxt('t-sub',n.subReq);
    setTxt('t-l-email',n.email); setTxt('t-submit',n.sendBtn); }
  setTxt('t-back',n.back);
  const err=document.getElementById('err'); if(err){const v=l==='ar'?err.dataset.ar:err.dataset.en; if(v)err.textContent=v;}
  const ok=document.getElementById('ok'); if(ok){const v=l==='ar'?ok.dataset.ar:ok.dataset.en; if(v)ok.textContent=v;}
}
function tgl(){L=L==='en'?'ar':'en';apply(L);try{localStorage.setItem('lang',L);}catch(e){}}
try{var _s=localStorage.getItem('lang');if(_s==='ar'||_s==='en')L=_s;}catch(e){}
apply(L);
const v=document.getElementById('vid');
if(v){v.addEventListener('canplay',()=>v.classList.add('on'),{once:true});v.play().catch(()=>{});}
</script>
</body>
</html>
