<?php
require_once 'config.php';

if (!isLoggedIn()) { header('Location: login.php'); exit; }
$uid = (int) $_SESSION['user_id'];

$me = currentUser();
if (!$me) { header('Location: login.php'); exit; }

/* Artists/admins belong on their own dashboards — this page is for collectors,
   and only ever shows the logged-in user's OWN data (no ?id parameter). */
if (($me['role'] ?? '') !== 'collector') {
    header('Location: ' . dashboardUrl($me['role'] ?? ''));
    exit;
}

/* Load this collector's purchases (own data only). Tolerate a missing
   purchases table so the page still renders before the migration is run. */
$purchases = [];
$totalSpent = 0.0;
try {
    $stmt = getDB()->prepare(
        "SELECT p.price, p.purchased_at,
                a.title_en, a.title_ar, a.image_url,
                u.full_name_en AS artist_en, u.full_name_ar AS artist_ar
         FROM purchases p
         JOIN artworks a ON a.id = p.artwork_id
         LEFT JOIN users u ON u.id = a.artist_id
         WHERE p.collector_id = :uid
         ORDER BY p.purchased_at DESC"
    );
    $stmt->execute([':uid' => $uid]);
    $purchases = $stmt->fetchAll();
    foreach ($purchases as $p) { $totalSpent += (float) $p['price']; }
} catch (\Throwable $e) {
    error_log('collector_dashboard: ' . $e->getMessage());
}

$memberSince = !empty($me['created_at']) ? date('Y', strtotime($me['created_at'])) : '—';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" id="html">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Collection · Oweili</title>
<link rel="stylesheet" href="/assets/css/oweili.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:#fff;font-family:var(--font-sans);overflow-x:hidden;}
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
.lang-btn{padding:4px 10px;border-radius:999px;font-size:12px;font-weight:600;letter-spacing:0.04em;cursor:pointer;border:1px solid rgba(0,0,0,0.1);background:rgba(255,255,255,0.7);color:rgba(0,0,0,0.7);backdrop-filter:blur(8px);transition:all 0.22s;}
.lang-btn:hover{background:#111111;color:#fff;}
@media(max-width:1024px){.nav-center{display:none;}}

.page{position:relative;z-index:10;width:100%;max-width:1100px;margin:0 auto;padding:90px 24px 56px;}
.gc{background:rgba(255,255,255,0.6);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(0,0,0,0.07);border-radius:20px;}
.section-title{font-size:12px;text-transform:uppercase;letter-spacing:0.16em;color:rgba(0,0,0,0.45);font-weight:700;margin:36px 0 16px;}
.saved-box{background:rgba(0,102,255,0.1);border:1px solid rgba(0,102,255,0.3);color:#0066ff;font-size:13px;font-weight:600;padding:11px 14px;border-radius:12px;margin-top:16px;}

/* PROFILE HEADER */
.profile-head{padding:32px;display:flex;gap:28px;align-items:center;animation:riseUp 0.8s cubic-bezier(0.22,1,0.36,1) forwards;opacity:0;}
[dir="rtl"] .profile-head{text-align:right;}
.avatar{width:110px;height:110px;border-radius:50%;object-fit:cover;flex-shrink:0;border:3px solid rgba(255,255,255,0.8);box-shadow:0 8px 24px rgba(0,0,0,0.12);background-size:cover;background-position:center;background:linear-gradient(135deg,#0066ff,#0066ff,#ff0055);display:flex;align-items:center;justify-content:center;color:#fff;font-size:40px;font-weight:700;}
.profile-info{flex:1;min-width:0;}
.profile-info h1{font-size:26px;font-weight:700;color:#111;margin-bottom:2px;}
.profile-info .name-ar{font-size:15px;color:rgba(0,0,0,0.55);margin-bottom:8px;}
.profile-info .meta{font-size:13px;color:rgba(0,0,0,0.6);display:flex;gap:16px;flex-wrap:wrap;margin-top:6px;}

.head-actions{flex-shrink:0;}
.btn-edit{padding:10px 20px;border-radius:999px;background:#111;color:#fff;font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;text-decoration:none;transition:all 0.25s;white-space:nowrap;}
.btn-edit:hover{background:#ff0055;transform:translateY(-2px);}

/* STATS */
.stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
.stat-card{padding:22px;text-align:center;}
.stat-num{font-size:26px;font-weight:700;color:#111;margin-bottom:4px;}
.stat-num.money{background:linear-gradient(90deg,#0066ff,#0066ff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.stat-lbl{font-size:12px;text-transform:uppercase;letter-spacing:0.08em;color:rgba(0,0,0,0.45);font-weight:600;}

/* PURCHASES */
.pur-wrap{padding:20px 24px;overflow-x:auto;}
.pur-table{width:100%;border-collapse:collapse;font-size:13px;}
.pur-table th,.pur-table td{text-align:left;padding:12px 10px;border-bottom:1px solid rgba(0,0,0,0.06);vertical-align:middle;}
[dir="rtl"] .pur-table th,[dir="rtl"] .pur-table td{text-align:right;}
.pur-table th{font-size:12px;text-transform:uppercase;letter-spacing:0.08em;color:rgba(0,0,0,0.45);}
.pur-thumb{width:52px;height:52px;border-radius:10px;object-fit:cover;border:1px solid rgba(0,0,0,0.1);}
.pur-price{color:#0066ff;font-weight:700;white-space:nowrap;}
.pur-empty{padding:34px;text-align:center;color:rgba(0,0,0,0.5);font-size:14px;}
.title-ar{font-size:12px;color:rgba(0,0,0,0.5);}
@keyframes riseUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
@media(max-width:768px){.topnav{padding:10px 14px;}.nav-logo span{display:none;}.profile-head{flex-direction:column;text-align:center;}[dir="rtl"] .profile-head{flex-direction:column;}.stats-row{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="bg-wrap"><video class="bg-video" id="vid" autoplay loop muted playsinline src="bg.mp4"></video></div>
<div class="overlay"></div>

<?php include __DIR__ . "/nav.php"; ?>

<div class="page" id="pg">

  <?php if (isset($_GET['saved'])): ?>
    <div class="saved-box" id="t-saved">Profile updated successfully.</div>
  <?php endif; ?>

  <!-- PROFILE -->
  <p class="section-title" id="t-profile-title">My Profile</p>
  <div class="gc profile-head">
    <?php if (!empty($me['profile_picture'])): ?>
      <div class="avatar" style="background-image:url('<?= e($me['profile_picture']) ?>');"></div>
    <?php else: ?>
      <div class="avatar"><?= e(mb_strtoupper(mb_substr($me['full_name_en'] ?: 'C', 0, 1))) ?></div>
    <?php endif; ?>
    <div class="profile-info">
      <h1><?= e($me['full_name_en'] ?: 'Collector') ?></h1>
      <?php if (!empty($me['full_name_ar'])): ?><div class="name-ar" dir="rtl"><?= e($me['full_name_ar']) ?></div><?php endif; ?>
      <div class="meta">
        <?php if (!empty($me['city'])): ?><span>📍 <?= e($me['city']) ?></span><?php endif; ?>
        <?php if (!empty($me['phone'])): ?><span dir="ltr">📞 <?= e($me['phone']) ?></span><?php endif; ?>
        <span><?= e($me['email']) ?></span>
      </div>
    </div>
    <div class="head-actions">
      <a class="btn-edit" href="edit_profile.php" id="t-edit">Edit Profile</a>
    </div>
  </div>

  <!-- STATS -->
  <div class="stats-row" style="margin-top:20px;">
    <div class="gc stat-card"><div class="stat-num"><?= count($purchases) ?></div><div class="stat-lbl" id="t-stat-count">Artworks Purchased</div></div>
    <div class="gc stat-card"><div class="stat-num money"><?= number_format($totalSpent) ?></div><div class="stat-lbl" id="t-stat-spent">Total Spent (SAR)</div></div>
    <div class="gc stat-card"><div class="stat-num"><?= e($memberSince) ?></div><div class="stat-lbl" id="t-stat-since">Member Since</div></div>
  </div>

  <!-- PURCHASES -->
  <p class="section-title" id="t-pur-title">Purchase History</p>
  <div class="gc pur-wrap">
    <?php if (!$purchases): ?>
      <div class="pur-empty" id="t-pur-empty">No purchases yet. Explore the marketplace to start your collection.</div>
    <?php else: ?>
      <table class="pur-table">
        <thead><tr>
          <th id="t-h-art">Artwork</th>
          <th id="t-h-artist">Artist</th>
          <th id="t-h-date">Date</th>
          <th id="t-h-price">Price</th>
        </tr></thead>
        <tbody>
        <?php foreach ($purchases as $p): ?>
          <tr>
            <td style="display:flex;align-items:center;gap:12px;">
              <?php if (!empty($p['image_url'])): ?><img class="pur-thumb" src="<?= e($p['image_url']) ?>" alt=""><?php endif; ?>
              <div>
                <strong><?= e($p['title_en'] ?: ($p['title_ar'] ?: '—')) ?></strong>
                <?php if (!empty($p['title_ar'])): ?><div class="title-ar" dir="rtl"><?= e($p['title_ar']) ?></div><?php endif; ?>
              </div>
            </td>
            <td><?= e($p['artist_en'] ?: ($p['artist_ar'] ?: '—')) ?></td>
            <td><?= e($p['purchased_at'] ? date('Y-m-d', strtotime($p['purchased_at'])) : '—') ?></td>
            <td class="pur-price"><?= number_format((float)$p['price']) ?> <span class="sar">SAR</span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<script>
const T={
  en:{dir:'ltr',lb:'العربية',profileTitle:'My Profile',edit:'Edit Profile',saved:'Profile updated successfully.',
    statCount:'Artworks Purchased',statSpent:'Total Spent (SAR)',statSince:'Member Since',
    purTitle:'Purchase History',purEmpty:'No purchases yet. Explore the marketplace to start your collection.',
    hArt:'Artwork',hArtist:'Artist',hDate:'Date',hPrice:'Price',sar:'SAR',
    nav:{studio:'The Studio',community:'Community',marketplace:'Marketplace',explore:'Explore Art Styles',support:'Support',
      s1:'Watercolor Workshop',s2:'Oil Painting Studio',s3:'Digital Art Lab',s4:'Charcoal & Ink',
      c2:'Meet Fellow Artists',c3:'Share Your Work',c4:'Learn Together',
      sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',login:'Sign In',reg:'Join Free'}},
  ar:{dir:'rtl',lb:'English',profileTitle:'ملفي الشخصي',edit:'تعديل الملف الشخصي',saved:'تم تحديث الملف الشخصي بنجاح.',
    statCount:'الأعمال المقتناة',statSpent:'إجمالي الإنفاق (ر.س)',statSince:'عضو منذ',
    purTitle:'سجل المشتريات',purEmpty:'لا توجد مشتريات بعد. استكشف السوق لبدء مجموعتك.',
    hArt:'العمل الفني',hArtist:'الفنان',hDate:'التاريخ',hPrice:'السعر',sar:'ر.س',
    nav:{studio:'الاستوديو',community:'المجتمع',marketplace:'السوق',explore:'استكشف أساليب الرسم',support:'الدعم',
      s1:'ورشة الألوان المائية',s2:'استوديو الرسم الزيتي',s3:'مختبر الفن الرقمي',s4:'الفحم والحبر',
      c2:'تعرّف على فنانين',c3:'شارك أعمالك',c4:'تعلّم معاً',
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
  setTxt('t-profile-title',t.profileTitle);setTxt('t-edit',t.edit);setTxt('t-saved',t.saved);
  setTxt('t-stat-count',t.statCount);setTxt('t-stat-spent',t.statSpent);setTxt('t-stat-since',t.statSince);
  setTxt('t-pur-title',t.purTitle);setTxt('t-pur-empty',t.purEmpty);
  setTxt('t-h-art',t.hArt);setTxt('t-h-artist',t.hArtist);setTxt('t-h-date',t.hDate);setTxt('t-h-price',t.hPrice);
  document.querySelectorAll('.sar').forEach(s=>s.textContent=t.sar);
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
