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
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="/assets/css/oweili.css?v=<?= @filemtime(__DIR__ . '/assets/css/oweili.css') ?>">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:var(--color-bg);font-family:var(--font-sans);overflow-x:hidden;}
@keyframes riseUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
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
      <div style="text-align:center;"><a class="btn btn-primary" href="login.php" id="t-go">Go to Sign In</a></div>

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
        <button type="submit" class="btn btn-primary btn-block btn-submit" id="t-submit">Reset Password</button>
      </form>

    <?php else: ?>
      <form method="post" action="forgot.php">
        <?= csrfField() ?>
        <div class="field"><label id="t-l-email">Email</label>
          <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></div>
        <button type="submit" class="btn btn-primary btn-block btn-submit" id="t-submit">Send Reset Link</button>
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
  en:{lb:'العربية',community:'Community',marketplace:'Gallery',explore:'Explore Art Styles',support:'Support',
    sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',login:'Sign In',reg:'Join Free',
    titleReq:'Reset password',subReq:"Enter your email and we'll send a reset link.",
    titleRes:'Set a new password',subRes:'Choose a new password for your account.',
    email:'Email',pass:'New password',conf:'Confirm password',
    r1:'Minimum 8 characters',r2:'At least one uppercase letter',r3:'At least one number',r4:'At least one special character (!@#$%^&*)',
    sendBtn:'Send Reset Link',resetBtn:'Reset Password',go:'Go to Sign In',back:'Back to Sign In'},
  ar:{lb:'English',community:'المجتمع',marketplace:'المعرض',explore:'استكشف أساليب الرسم',support:'الدعم',
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
  setTxt('nav-community-label',n.community);
  setTxt('nav-marketplace-label',n.marketplace);setTxt('nav-explore-label',n.explore);setTxt('nav-support-label',n.support);
  
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
if(v){{const on=()=>v.classList.add('on');if(v.readyState>=3)on();v.addEventListener('canplay',on,{once:true});v.addEventListener('playing',on,{once:true});}v.play().catch(()=>{});}
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
