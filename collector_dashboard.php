<?php
require_once 'config.php';
require_once __DIR__ . '/artwork_lib.php';   // owArtistNameSql(): the artist's public name

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
                " . owArtistNameSql('u') . "
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
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
/* Collector dashboard — layout only; cards, buttons, stats and tables come from oweili.css (role color: collector blue) */
.page{position:relative;z-index:10;}
.section-title{font-size:var(--fs-subtitle);font-weight:700;color:var(--color-dark);margin:var(--space-xl) 0 var(--space-sm);}
.section-title:first-of-type{margin-top:0;}
.profile-head{display:flex;gap:var(--space-lg);align-items:center;padding:var(--space-lg);}
.avatar{width:110px;height:110px;border-radius:50%;flex-shrink:0;border:3px solid var(--color-bg);box-shadow:var(--shadow-card);background-color:var(--color-collector);background-size:cover;background-position:center;display:flex;align-items:center;justify-content:center;color:var(--color-on-accent);font-size:40px;font-weight:700;}
.profile-info{flex:1;min-width:0;}
.profile-info h1{font-size:var(--fs-section);margin:0 0 4px;}
.profile-info .name-ar{font-size:var(--fs-subtitle);color:var(--color-body);margin-bottom:var(--space-xs);}
.profile-info .meta{font-size:15px;color:var(--color-body);display:flex;gap:var(--space-sm);flex-wrap:wrap;margin-top:6px;}
.head-actions{flex-shrink:0;}
.stats-row{grid-template-columns:repeat(3,1fr);margin-top:var(--space-md);}
.pur-thumb{width:56px;height:56px;border-radius:var(--radius-input);object-fit:contain;background:var(--color-frame);border:1px solid var(--color-border);}
.pur-art{display:flex;align-items:center;gap:var(--space-sm);}
.pur-price{font-weight:700;white-space:nowrap;}
.title-ar{font-size:var(--fs-caption);color:var(--color-body);}
.pur-empty{text-align:center;padding:var(--space-xl) var(--space-md);}
.pur-empty p{margin:0 0 var(--space-sm);}
@media(max-width:768px){.profile-head{flex-direction:column;text-align:center;padding:var(--space-md);}.stats-row{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="bg-wrap"><video class="bg-video" id="vid" autoplay loop muted playsinline src="<?= owBase() ?>bg.mp4"></video></div>
<div class="overlay"></div>

<?php include __DIR__ . "/nav.php"; ?>

<div class="page dash role-collector dash-wrap" id="pg">

  <?php if (isset($_GET['saved'])): ?>
    <div class="ok-box" id="t-saved">Profile updated successfully.</div>
  <?php endif; ?>

  <!-- PROFILE -->
  <p class="section-title" id="t-profile-title">My Profile</p>
  <div class="card profile-head">
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
      <a class="btn btn-secondary" href="edit_profile.php" id="t-edit">Edit Profile</a>
    </div>
  </div>

  <!-- STATS -->
  <div class="stat-grid stats-row">
    <div class="stat-card"><span class="stat-num"><?= count($purchases) ?></span><span class="stat-lbl" id="t-stat-count">Artworks Purchased</span></div>
    <div class="stat-card"><span class="stat-num"><?= number_format($totalSpent) ?></span><span class="stat-lbl" id="t-stat-spent">Total Spent (SAR)</span></div>
    <div class="stat-card"><span class="stat-num"><?= e($memberSince) ?></span><span class="stat-lbl" id="t-stat-since">Member Since</span></div>
  </div>

  <!-- PURCHASES -->
  <p class="section-title" id="t-pur-title">Purchase History</p>
  <div class="table-wrap">
    <?php if (!$purchases): ?>
      <div class="pur-empty"><p id="t-pur-empty">No purchases yet. Explore the gallery to start your collection.</p><a class="btn btn-primary" href="marketplace.php" id="t-pur-go">Explore Artworks</a></div>
    <?php else: ?>
      <table class="table">
        <thead><tr>
          <th id="t-h-art">Artwork</th>
          <th id="t-h-artist">Artist</th>
          <th id="t-h-date">Date</th>
          <th id="t-h-price">Price</th>
        </tr></thead>
        <tbody>
        <?php foreach ($purchases as $p): ?>
          <tr>
            <td><div class="pur-art">
              <?php if (($pImg = owImgSrc($p['image_url'] ?? '')) !== ''): ?><img class="pur-thumb" loading="lazy" src="<?= e(owImgSized($pImg, 400)) ?>" alt="<?= e(($p['title_en'] ?? '') . ' · ' . ($p['title_ar'] ?? '')) ?>"><?php endif; ?>
              <div>
                <strong><?= e($p['title_en'] ?: ($p['title_ar'] ?: '—')) ?></strong>
                <?php if (!empty($p['title_ar'])): ?><div class="title-ar" dir="rtl"><?= e($p['title_ar']) ?></div><?php endif; ?>
              </div>
            </div></td>
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
    purTitle:'Purchase History',purEmpty:'No purchases yet. Explore the gallery to start your collection.',purGo:'Explore Artworks',
    hArt:'Artwork',hArtist:'Artist',hDate:'Date',hPrice:'Price',sar:'SAR',
    nav:{community:'Community',marketplace:'Gallery',explore:'Explore Art Styles',support:'Support',
      sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',login:'Sign In',reg:'Join Free'}},
  ar:{dir:'rtl',lb:'English',profileTitle:'ملفي الشخصي',edit:'تعديل الملف الشخصي',saved:'تم تحديث الملف الشخصي بنجاح.',
    statCount:'الأعمال المقتناة',statSpent:'إجمالي الإنفاق (ر.س)',statSince:'عضو منذ',
    purTitle:'سجل المشتريات',purEmpty:'لا توجد مشتريات بعد. استكشف المعرض لبدء مجموعتك.',purGo:'استكشف الأعمال',
    hArt:'العمل الفني',hArtist:'الفنان',hDate:'التاريخ',hPrice:'السعر',sar:'ر.س',
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
  setTxt('t-profile-title',t.profileTitle);setTxt('t-edit',t.edit);setTxt('t-saved',t.saved);
  setTxt('t-stat-count',t.statCount);setTxt('t-stat-spent',t.statSpent);setTxt('t-stat-since',t.statSince);
  setTxt('t-pur-title',t.purTitle);setTxt('t-pur-empty',t.purEmpty);setTxt('t-pur-go',t.purGo);
  setTxt('t-h-art',t.hArt);setTxt('t-h-artist',t.hArtist);setTxt('t-h-date',t.hDate);setTxt('t-h-price',t.hPrice);
  document.querySelectorAll('.sar').forEach(s=>s.textContent=t.sar);
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
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
