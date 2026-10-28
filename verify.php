<?php
require_once 'config.php';

/* ---- Validate token (before output) ---- */
$token = sanitize($_GET['token'] ?? '');
$state = 'invalid';   // 'success' | 'invalid'

if ($token !== '' && ctype_xdigit($token) && strlen($token) <= 128) {
    $stmt = getDB()->prepare(
        'SELECT id, email, full_name_en, full_name_ar, is_verified
         FROM users
         WHERE verification_token = :t
           AND (verification_expires IS NULL OR verification_expires > NOW())
         LIMIT 1'
    );
    $stmt->execute([':t' => $token]);
    $row = $stmt->fetch();
    if ($row) {
        $alreadyVerified = (int) $row['is_verified'] === 1;
        $upd = getDB()->prepare(
            'UPDATE users
             SET is_verified = 1, verification_token = NULL, verification_expires = NULL
             WHERE id = :id'
        );
        $upd->execute([':id' => $row['id']]);
        $state = 'success';

        // Send the bilingual welcome email once, only on first verification.
        if (!$alreadyVerified) {
            $host      = $_SERVER['HTTP_HOST'] ?? 'oweili.com';
            $dashUrl   = 'https://' . $host . '/artist_dashboard.php?id=' . (int) $row['id'];
            $marketUrl = 'https://' . $host . '/marketplace.php';
            $welcome   = welcomeEmailHtml(
                $row['full_name_en'] ?: '',
                $row['full_name_ar'] ?: '',
                $dashUrl,
                $marketUrl
            );
            if (!sendEmail($row['email'], 'Welcome to Oweili · مرحباً بك في أويلي', $welcome, true)) {
                error_log('verify.php: welcome email failed to send to ' . $row['email']);
            }
        }
    }
}

/**
 * Build the bilingual (EN + AR) HTML welcome email. Uses inline styles and a
 * table layout so it renders in every email client.
 */
function welcomeEmailHtml(string $nameEn, string $nameAr, string $dashUrl, string $marketUrl): string {
    $en  = htmlspecialchars($nameEn !== '' ? $nameEn : 'there', ENT_QUOTES);
    $ar  = htmlspecialchars($nameAr !== '' ? $nameAr : 'صديقنا', ENT_QUOTES);
    $dU  = htmlspecialchars($dashUrl, ENT_QUOTES);
    $mU  = htmlspecialchars($marketUrl, ENT_QUOTES);
    return <<<HTML
<!DOCTYPE html>
<html>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:'DM Sans','Tajawal','Helvetica Neue',Helvetica,Arial,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:32px 12px;">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.06);">
        <!-- Header -->
        <tr><td style="background:#23314A;padding:34px 32px;text-align:center;">
          <div style="font-size:22px;font-weight:700;letter-spacing:0.14em;color:#ffffff;text-transform:uppercase;">OWEILI</div>
          <div style="height:3px;width:56px;margin:14px auto 0;background:linear-gradient(90deg,#DB8084,#8FA1B1,#23314A);border-radius:3px;"></div>
        </td></tr>

        <!-- English -->
        <tr><td style="padding:32px 32px 8px;text-align:left;" dir="ltr">
          <h1 style="margin:0 0 12px;font-size:22px;color:#23314A;">Welcome, {$en}! 🎨</h1>
          <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#4A5568;">
            Your account is verified and you're now part of the <strong>Oweili</strong> art community —
            a bilingual home for Saudi artists, collectors, and art lovers.
          </p>
          <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#4A5568;">
            Explore original artworks, connect with fellow artists, and share your own work with the world.
          </p>
          <table role="presentation" cellpadding="0" cellspacing="0"><tr>
            <td style="padding-right:10px;"><a href="{$dU}" style="display:inline-block;padding:12px 24px;background:#23314A;color:#ffffff;font-size:13px;font-weight:700;text-decoration:none;border-radius:999px;">Go to your dashboard</a></td>
            <td><a href="{$mU}" style="display:inline-block;padding:12px 24px;background:#DB8084;color:#23314A;font-size:13px;font-weight:700;text-decoration:none;border-radius:999px;">Browse the gallery</a></td>
          </tr></table>
        </td></tr>

        <tr><td style="padding:8px 32px;"><hr style="border:none;border-top:1px solid #eee;margin:16px 0;"></td></tr>

        <!-- Arabic -->
        <tr><td style="padding:8px 32px 32px;text-align:right;" dir="rtl">
          <h1 style="margin:0 0 12px;font-size:22px;color:#23314A;">مرحباً، {$ar}! 🎨</h1>
          <p style="margin:0 0 14px;font-size:15px;line-height:1.9;color:#4A5568;">
            تم تفعيل حسابك، وأنت الآن جزء من مجتمع <strong>أويلي</strong> الفني —
            منصة ثنائية اللغة تجمع الفنانين السعوديين والمقتنين ومحبي الفن.
          </p>
          <p style="margin:0 0 20px;font-size:15px;line-height:1.9;color:#4A5568;">
            استكشف الأعمال الفنية الأصلية، وتواصل مع فنانين آخرين، وشارك أعمالك مع العالم.
          </p>
          <table role="presentation" cellpadding="0" cellspacing="0" align="right"><tr>
            <td style="padding-left:10px;"><a href="{$dU}" style="display:inline-block;padding:12px 24px;background:#23314A;color:#ffffff;font-size:13px;font-weight:700;text-decoration:none;border-radius:999px;">لوحة التحكم</a></td>
            <td><a href="{$mU}" style="display:inline-block;padding:12px 24px;background:#DB8084;color:#23314A;font-size:13px;font-weight:700;text-decoration:none;border-radius:999px;">تصفّح المعرض</a></td>
          </tr></table>
        </td></tr>

        <!-- Footer -->
        <tr><td style="background:#fafafa;padding:20px 32px;text-align:center;">
          <p style="margin:0;font-size:12px;color:#6B7A8A;">© Oweili · oweili.com — Saudi-born bilingual art gallery</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" id="html">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Account · Oweili</title>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="/assets/css/oweili.css?v=<?= @filemtime(__DIR__ . '/assets/css/oweili.css') ?>">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:var(--color-bg);font-family:var(--font-sans);overflow-x:hidden;}
.v-icon{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:34px;}
.v-icon.ok{background:rgba(var(--rgb-accent),0.12);border:1px solid rgba(var(--rgb-accent),0.3);}
.v-icon.bad{background:rgba(var(--rgb-primary),0.1);border:1px solid rgba(var(--rgb-primary),0.25);}
.redir-note{margin-top:16px;font-size:12px;color:var(--color-body);}
@keyframes riseUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
</style>
</head>
<body>
<div class="bg-wrap"><video class="bg-video" id="vid" autoplay loop muted playsinline src="bg.mp4"></video></div>
<div class="overlay"></div>

<!-- NAV (shared brand navbar) -->
<?php include __DIR__ . "/nav.php"; ?>

<div class="auth-wrap">
  <div class="auth-card">
    <?php if ($state === 'success'): ?>
      <div class="v-icon ok">✓</div>
      <h1 id="t-title">Your account is verified</h1>
      <p id="t-sub">You can now sign in.</p>
      <a class="btn btn-primary" href="login.php" id="t-btn">Sign In</a>
      <p class="redir-note" id="t-redir">Redirecting to sign in…</p>
    <?php else: ?>
      <div class="v-icon bad">✕</div>
      <h1 id="t-title">Invalid or expired link</h1>
      <p id="t-sub">This verification link is invalid or has expired. Please register again or request a new link.</p>
      <a class="btn btn-primary" href="register.php" id="t-btn">Register</a>
    <?php endif; ?>
  </div>
</div>

<script>
const STATE = <?= json_encode($state) ?>;
const T={
  en:{dir:'ltr',lb:'العربية',
    okTitle:'Your account is verified',okSub:'You can now sign in.',okBtn:'Sign In',redir:'Redirecting to sign in…',
    badTitle:'Invalid or expired link',badSub:'This verification link is invalid or has expired. Please register again or request a new link.',badBtn:'Register',
    nav:{community:'Community',marketplace:'Gallery',explore:'Explore Art Styles',support:'Support',
      sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',login:'Sign In',reg:'Join Free'}},
  ar:{dir:'rtl',lb:'English',
    okTitle:'تم التحقق من حسابك',okSub:'يمكنك الآن تسجيل الدخول.',okBtn:'تسجيل الدخول',redir:'جارٍ التحويل إلى تسجيل الدخول…',
    badTitle:'رابط غير صالح أو منتهي',badSub:'رابط التحقق غير صالح أو منتهي الصلاحية. يرجى التسجيل مرة أخرى أو طلب رابط جديد.',badBtn:'إنشاء حساب',
    nav:{community:'المجتمع',marketplace:'المعرض',explore:'استكشف أساليب الرسم',support:'الدعم',
      sp1:'تواصل معنا',sp2:'كيف يعمل الموقع',sp3:'شروط الاستخدام',login:'تسجيل الدخول',reg:'انضم مجاناً'}}
};
let L='en';
function setTxt(id,v){const el=document.getElementById(id);if(el)el.textContent=v;}
function apply(l){
  const t=T[l];
  document.getElementById('html').lang=l;
  document.getElementById('html').setAttribute('dir',t.dir);
  document.documentElement.setAttribute('dir',t.dir);
  document.getElementById('topnav').setAttribute('dir',t.dir);
  document.getElementById('lb').textContent=t.lb;
  if(STATE==='success'){
    setTxt('t-title',t.okTitle);setTxt('t-sub',t.okSub);setTxt('t-btn',t.okBtn);setTxt('t-redir',t.redir);
  }else{
    setTxt('t-title',t.badTitle);setTxt('t-sub',t.badSub);setTxt('t-btn',t.badBtn);
  }
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
// Auto-redirect to login 3s after a successful verification.
if(STATE==='success'){ setTimeout(()=>{ window.location.href='login.php'; }, 3000); }
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
