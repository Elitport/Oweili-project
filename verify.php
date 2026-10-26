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
<body style="margin:0;padding:0;background:#f4f5f7;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:32px 12px;">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.06);">
        <!-- Header -->
        <tr><td style="background:#111111;padding:34px 32px;text-align:center;">
          <div style="font-size:22px;font-weight:700;letter-spacing:0.14em;color:#ffffff;text-transform:uppercase;">OWEILI</div>
          <div style="height:3px;width:56px;margin:14px auto 0;background:linear-gradient(90deg,#ff0055,#0066ff,#0066ff);border-radius:3px;"></div>
        </td></tr>

        <!-- English -->
        <tr><td style="padding:32px 32px 8px;text-align:left;" dir="ltr">
          <h1 style="margin:0 0 12px;font-size:22px;color:#111111;">Welcome, {$en}! 🎨</h1>
          <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#444;">
            Your account is verified and you're now part of the <strong>Oweili</strong> art community —
            a bilingual home for Saudi artists, collectors, and art lovers.
          </p>
          <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#444;">
            Explore original artworks, connect with fellow artists, and share your own work with the world.
          </p>
          <table role="presentation" cellpadding="0" cellspacing="0"><tr>
            <td style="padding-right:10px;"><a href="{$dU}" style="display:inline-block;padding:12px 24px;background:#111111;color:#fff;font-size:13px;font-weight:700;text-decoration:none;border-radius:999px;">Go to your dashboard</a></td>
            <td><a href="{$mU}" style="display:inline-block;padding:12px 24px;background:#ff0055;color:#fff;font-size:13px;font-weight:700;text-decoration:none;border-radius:999px;">Browse the marketplace</a></td>
          </tr></table>
        </td></tr>

        <tr><td style="padding:8px 32px;"><hr style="border:none;border-top:1px solid #eee;margin:16px 0;"></td></tr>

        <!-- Arabic -->
        <tr><td style="padding:8px 32px 32px;text-align:right;" dir="rtl">
          <h1 style="margin:0 0 12px;font-size:22px;color:#111111;">مرحباً، {$ar}! 🎨</h1>
          <p style="margin:0 0 14px;font-size:15px;line-height:1.9;color:#444;">
            تم تفعيل حسابك، وأنت الآن جزء من مجتمع <strong>أويلي</strong> الفني —
            منصة ثنائية اللغة تجمع الفنانين السعوديين والمقتنين ومحبي الفن.
          </p>
          <p style="margin:0 0 20px;font-size:15px;line-height:1.9;color:#444;">
            استكشف الأعمال الفنية الأصلية، وتواصل مع فنانين آخرين، وشارك أعمالك مع العالم.
          </p>
          <table role="presentation" cellpadding="0" cellspacing="0" align="right"><tr>
            <td style="padding-left:10px;"><a href="{$dU}" style="display:inline-block;padding:12px 24px;background:#111111;color:#fff;font-size:13px;font-weight:700;text-decoration:none;border-radius:999px;">لوحة التحكم</a></td>
            <td><a href="{$mU}" style="display:inline-block;padding:12px 24px;background:#ff0055;color:#fff;font-size:13px;font-weight:700;text-decoration:none;border-radius:999px;">تصفّح السوق</a></td>
          </tr></table>
        </td></tr>

        <!-- Footer -->
        <tr><td style="background:#fafafa;padding:20px 32px;text-align:center;">
          <p style="margin:0;font-size:12px;color:#999;">© Oweili · oweili.com — Saudi bilingual art marketplace</p>
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
<link rel="stylesheet" href="/assets/css/oweili.css">
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
.auth-card{width:100%;max-width:440px;background:rgba(255,255,255,0.6);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(0,0,0,0.07);border-radius:20px;box-shadow:0 20px 50px rgba(0,0,0,0.1);padding:40px 32px;text-align:center;animation:riseUp 0.8s cubic-bezier(0.22,1,0.36,1) forwards;opacity:0;}
.v-icon{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:34px;}
.v-icon.ok{background:rgba(0,102,255,0.12);border:1px solid rgba(0,102,255,0.3);}
.v-icon.bad{background:rgba(255,0,85,0.1);border:1px solid rgba(255,0,85,0.25);}
.auth-card h1{font-size:24px;font-weight:700;color:#111111;margin-bottom:10px;}
.auth-card p{font-size:14px;color:rgba(0,0,0,0.6);line-height:1.7;margin-bottom:24px;}
.btn-submit{display:inline-block;padding:12px 28px;border-radius:999px;background:#111111;color:#fff;font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;border:none;cursor:pointer;transition:all 0.25s;text-decoration:none;}
.btn-submit:hover{background:#ff0055;transform:translateY(-2px);}
.redir-note{margin-top:16px;font-size:12px;color:rgba(0,0,0,0.45);}
@keyframes riseUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
@media(max-width:768px){.topnav{padding:10px 14px;}.nav-logo span{display:none;}}
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
      <a class="btn-submit" href="login.php" id="t-btn">Sign In</a>
      <p class="redir-note" id="t-redir">Redirecting to sign in…</p>
    <?php else: ?>
      <div class="v-icon bad">✕</div>
      <h1 id="t-title">Invalid or expired link</h1>
      <p id="t-sub">This verification link is invalid or has expired. Please register again or request a new link.</p>
      <a class="btn-submit" href="register.php" id="t-btn">Register</a>
    <?php endif; ?>
  </div>
</div>

<script>
const STATE = <?= json_encode($state) ?>;
const T={
  en:{dir:'ltr',lb:'العربية',
    okTitle:'Your account is verified',okSub:'You can now sign in.',okBtn:'Sign In',redir:'Redirecting to sign in…',
    badTitle:'Invalid or expired link',badSub:'This verification link is invalid or has expired. Please register again or request a new link.',badBtn:'Register',
    nav:{studio:'The Studio',community:'Community',marketplace:'Marketplace',explore:'Explore Art Styles',support:'Support',
      s1:'Watercolor Workshop',s2:'Oil Painting Studio',s3:'Digital Art Lab',s4:'Charcoal & Ink',
      c1:'Artist Chat Room',c2:'Meet Fellow Artists',c3:'Share Your Work',c4:'Learn Together',
      sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',login:'Sign In',reg:'Join Free'}},
  ar:{dir:'rtl',lb:'English',
    okTitle:'تم التحقق من حسابك',okSub:'يمكنك الآن تسجيل الدخول.',okBtn:'تسجيل الدخول',redir:'جارٍ التحويل إلى تسجيل الدخول…',
    badTitle:'رابط غير صالح أو منتهي',badSub:'رابط التحقق غير صالح أو منتهي الصلاحية. يرجى التسجيل مرة أخرى أو طلب رابط جديد.',badBtn:'إنشاء حساب',
    nav:{studio:'الاستوديو',community:'المجتمع',marketplace:'السوق',explore:'استكشف أساليب الرسم',support:'الدعم',
      s1:'ورشة الألوان المائية',s2:'استوديو الرسم الزيتي',s3:'مختبر الفن الرقمي',s4:'الفحم والحبر',
      c1:'غرفة محادثة الفنانين',c2:'تعرّف على فنانين',c3:'شارك أعمالك',c4:'تعلّم معاً',
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
// Auto-redirect to login 3s after a successful verification.
if(STATE==='success'){ setTimeout(()=>{ window.location.href='login.php'; }, 3000); }
</script>
</body>
</html>
