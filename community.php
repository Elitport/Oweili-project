<?php
/* =====================================================================
 *  community.php: The Artist Community
 *  - Number boxes show real counts: artists (users, role artist, not
 *    banned), messages shared (messages), and art styles covered (counted
 *    from assets/js/art_styles.js, the one source for styles).
 *  - Daily Inspiration shows the 3 newest approved artworks from the Gallery
 *    (approved, for sale, finished, by artists who are not banned), each
 *    linking to that artwork in the Gallery. Empty spots invite artists.
 *  All database reads use prepared statements. If the database is not
 *  reachable, the page still shows, with friendly empty cards.
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/artwork_lib.php';   // owBi(), owNotDraftSql()

/* 54 → "54+", 7 → "7" (a "+" only once the number is over 10) */
function owCountLabel(int $n): string { return $n > 10 ? $n . '+' : (string) $n; }

$artistCount = 0; $messageCount = 0; $inspo = [];
try {
    $db = getDB();
    $st = $db->prepare("SELECT COUNT(*) FROM users WHERE role = :role AND is_banned = 0");
    $st->execute([':role' => 'artist']);
    $artistCount = (int) $st->fetchColumn();

    $st = $db->prepare("SELECT COUNT(*) FROM messages");
    $st->execute();
    $messageCount = (int) $st->fetchColumn();

    $st = $db->prepare(
        "SELECT a.id, a.title_en, a.title_ar, a.image_url, " . owArtistNameSql('u') . "
         FROM artworks a
         JOIN users u ON u.id = a.artist_id
         WHERE a.is_approved = 1 AND a.status IN ('available','auction') AND u.is_banned = 0" . owNotDraftSql('a') . "
         ORDER BY a.created_at DESC, a.id DESC
         LIMIT :lim"
    );
    $st->bindValue(':lim', 3, PDO::PARAM_INT);
    $st->execute();
    $inspo = $st->fetchAll();
} catch (Throwable $ex) {
    $inspo = [];
}

/* Art styles covered: counted from the shared style file, never typed in */
$styleCount = 0;
$stylesJs = @file_get_contents(__DIR__ . '/assets/js/art_styles.js');
if ($stylesJs !== false && ($p = strpos($stylesJs, 'OW_ART_STYLES =')) !== false) {
    $list = json_decode(rtrim(trim(substr($stylesJs, $p + strlen('OW_ART_STYLES ='))), ';'), true);
    $styleCount = is_array($list) ? count($list) : 0;
}

/* pictures only from our own uploads folder (or a full https address) */
$owSafeImg = fn(string $u): string => (preg_match('#^(https://|/?uploads/)[^"\'<>\s]*$#', $u) && strpos($u, '..') === false) ? $u : '';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" id="html">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Community · Oweili</title>
<meta name="description" content="Art grows when artists meet. Share your work, learn from each other, and make friends who get it. الفن يكبر حين يلتقي الفنانون. شارك أعمالك، وتعلّم من غيرك، واصنع صداقات تفهم شغفك.">
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="/assets/css/oweili.css?v=<?= @filemtime(__DIR__ . '/assets/css/oweili.css') ?>">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:var(--color-bg);font-family:var(--font-sans);overflow-x:hidden;}
.page{position:relative;z-index:10;width:100%;max-width:1280px;margin:0 auto;padding:calc(var(--nav-h) + var(--space-xl)) var(--space-lg) var(--space-2xl);}
.page-header{margin-bottom:48px;animation:riseUp 0.9s cubic-bezier(0.22,1,0.36,1) forwards;opacity:0;}
.page-badge{display:inline-flex;align-items:center;gap:8px;padding:5px 14px;border-radius:999px;margin-bottom:16px;background:rgba(var(--rgb-accent),0.05);border:1px solid rgba(var(--rgb-accent),0.15);font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:var(--color-accent);}
.pulse-dot{width:6px;height:6px;border-radius:50%;background:var(--color-accent);box-shadow:0 0 8px rgba(var(--rgb-accent),0.5);animation:pulse 2s infinite;}
.page-header h1{font-size:clamp(28px,4vw,48px);font-weight:700;color:var(--color-dark);margin-bottom:12px;letter-spacing:-0.02em;}
.page-header h1 em{font-style:normal;font-weight:700;color:var(--color-highlight);}
.page-header p{font-size:var(--fs-body);color:var(--color-body);line-height:1.7;max-width:580px;}.section-title{font-size:var(--fs-section);font-weight:700;color:var(--color-dark);letter-spacing:-0.02em;margin:0 0 var(--space-md);}
/* STATS */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:48px;animation:riseUp 0.9s 0.05s cubic-bezier(0.22,1,0.36,1) both;}
.stat-card{background:var(--color-surface);border:1px solid rgba(var(--rgb-ink),0.07);border-radius:16px;padding:24px;text-align:center;box-shadow:var(--shadow-card);}
.stat-num{font-size:32px;font-weight:700;color:var(--color-highlight);margin-bottom:4px;}
.stat-label{font-size:12px;color:var(--color-body);text-transform:uppercase;letter-spacing:0.1em;}
/* COMMUNITY CARDS */
.community-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:20px;margin-bottom:48px;animation:riseUp 0.9s 0.15s cubic-bezier(0.22,1,0.36,1) both;}
.comm-card{background:var(--color-surface);border:1px solid rgba(var(--rgb-ink),0.07);border-radius:var(--radius-card);padding:28px;cursor:pointer;transition:all 0.3s;text-decoration:none;display:block;box-shadow:var(--shadow-card);}
.comm-card:hover{transform:translateY(-4px);box-shadow:0 20px 40px rgba(var(--rgb-shadow),0.08);}
.comm-icon{font-size:32px;margin-bottom:14px;}
.comm-card h3{font-size:var(--fs-subtitle);font-weight:700;color:var(--color-dark);margin-bottom:8px;}
.comm-card p{font-size:var(--fs-body);color:var(--color-body);line-height:1.7;margin-bottom:16px;}
.comm-link{font-size:12px;font-weight:700;color:var(--color-accent);letter-spacing:0.06em;text-transform:uppercase;}
/* INSPIRATION */
.inspo-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;animation:riseUp 0.9s 0.2s cubic-bezier(0.22,1,0.36,1) both;}
.inspo-card{background:var(--color-surface);border:1px solid rgba(var(--rgb-ink),0.06);border-radius:16px;overflow:hidden;cursor:pointer;transition:all 0.3s;box-shadow:var(--shadow-card);}
.inspo-card:hover{transform:translateY(-3px);box-shadow:0 16px 32px rgba(var(--rgb-shadow),0.08);}
.inspo-img{height:140px;background-size:cover;background-position:center;transition:transform 0.5s;}
.inspo-card:hover .inspo-img{transform:scale(1.05);}
.inspo-img img{display:block;width:100%;height:100%;object-fit:contain;background:var(--color-frame);}
a.inspo-card{display:block;color:inherit;text-decoration:none;}
a.inspo-card:focus-visible{outline:2px solid var(--color-accent);outline-offset:2px;}
.inspo-empty{cursor:default;text-align:center;}
.inspo-empty:hover{transform:none;}
.inspo-empty .inspo-img{display:flex;align-items:center;justify-content:center;background:rgba(var(--rgb-primary),0.10);}
.inspo-empty:hover .inspo-img{transform:none;}
.inspo-empty svg{width:48px;height:48px;fill:none;stroke:var(--color-body);stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round;}
.inspo-empty .btn{margin-top:12px;}
.stat-link{display:block;color:inherit;text-decoration:none;}
.stat-link:focus-visible{outline:2px solid var(--color-accent);outline-offset:4px;border-radius:8px;}
.inspo-body .comm-link{display:inline-block;margin-top:8px;}
.inspo-body{padding:16px;}
.inspo-body h4{font-size:var(--fs-subtitle);font-weight:700;color:var(--color-dark);margin-bottom:4px;}
.inspo-body p{font-size:var(--fs-body);color:var(--color-body);}
@keyframes riseUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.4}}
[dir="rtl"] .stats-row,[dir="rtl"] .community-grid,[dir="rtl"] .inspo-grid{direction:rtl;}
[dir="rtl"] .page-header p{text-align:right;}
@media(max-width:1024px){.stats-row{grid-template-columns:repeat(2,1fr);}.inspo-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:768px){.community-grid{grid-template-columns:1fr;}.stats-row{grid-template-columns:1fr 1fr;}.page{padding:calc(var(--nav-h) + var(--space-md)) var(--space-sm) var(--space-xl);}}
@media(max-width:560px){.inspo-grid{grid-template-columns:1fr;}.inspo-img{height:200px;}}
</style>
</head>
<body>
<div class="bg-wrap">
  <video class="bg-video" id="vid" autoplay loop muted playsinline src="bg.mp4"></video>
</div>
<div class="overlay"></div>
<?php include __DIR__ . "/nav.php"; ?>
<div class="page" id="pg">
  <div class="page-header">
    <div class="page-badge"><div class="pulse-dot"></div><span id="t-badge">Artists Together</span></div>
    <h1 id="t-h1">The <em>Artist Community</em></h1>
    <p id="t-desc">Art grows when artists meet. Share your work, learn from each other, and make friends who get it.</p>
  </div>

  <div class="stats-row">
    <div class="stat-card"><div class="stat-num" id="s-artists"><?= e(owCountLabel($artistCount)) ?></div><div class="stat-label" id="t-s1">Artists With Us</div></div>
    <div class="stat-card"><div class="stat-num" id="s-msgs"><?= e(owCountLabel($messageCount)) ?></div><div class="stat-label" id="t-s2">Messages Shared</div></div>
    <div class="stat-card"><a class="stat-link" href="explore.php"><div class="stat-num" id="s-styles"><?= (int) $styleCount ?></div><div class="stat-label" id="t-s3">Art Styles Covered</div></a></div>
    <div class="stat-card"><div class="stat-num">🇸🇦</div><div class="stat-label" id="t-s4">Saudi Community</div></div>
  </div>

  <p class="section-title" id="t-comm-title">Community Spaces</p>
  <div class="community-grid">
    <a class="comm-card" href="register.php">
      <div class="comm-icon">🎨</div>
      <h3 id="t-c2">Professional Artists Guild</h3>
      <p id="t-c2d">A space for experienced artists to work together and guide new talent.</p>
      <span class="comm-link" id="t-c2l">Apply to Join →</span>
    </a>
    <a class="comm-card" href="register.php">
      <div class="comm-icon">✨</div>
      <h3 id="t-c3">Daily Inspiration</h3>
      <p id="t-c3d">A little spark every day. Featured works, color palettes and new techniques to try.</p>
      <span class="comm-link" id="t-c3l">Explore →</span>
    </a>
    <a class="comm-card" href="register.php">
      <div class="comm-icon">🏆</div>
      <h3 id="t-c4">Teach With Us</h3>
      <p id="t-c4d">Love sharing what you know? Become an instructor and help others grow.</p>
      <span class="comm-link" id="t-c4l">Apply Now →</span>
    </a>
  </div>

  <p class="section-title" id="t-inspo-title">Daily Inspiration</p>
  <div class="inspo-grid">
    <?php foreach ($inspo as $aw):
      $img = $owSafeImg((string) $aw['image_url']);
      $tEn = (string) $aw['title_en']; $tAr = (string) ($aw['title_ar'] ?: $aw['title_en']);
      $nEn = (string) ($aw['artist_en'] ?: $aw['artist_ar']); $nAr = (string) ($aw['artist_ar'] ?: $aw['artist_en']); ?>
    <a class="inspo-card" href="marketplace.php#artwork-<?= (int) $aw['id'] ?>">
      <div class="inspo-img"><?php if ($img !== ''): ?><img src="<?= e($img) ?>" alt="<?= e($tEn . ' · ' . $tAr) ?>" loading="lazy"><?php endif; ?></div>
      <div class="inspo-body">
        <h4><?= owBi($tEn, $tAr) ?></h4>
        <p><?= owBi('by ' . $nEn, 'بواسطة ' . $nAr) ?></p>
        <span class="comm-link"><?= owBi('See it in the Gallery →', 'شاهده في المعرض ←') ?></span>
      </div>
    </a>
    <?php endforeach; ?>
    <?php for ($k = count($inspo); $k < 3; $k++): ?>
    <div class="inspo-card inspo-empty">
      <div class="inspo-img" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 16l5-5 4 4 3-3 6 6"/><circle cx="16" cy="9" r="1.6"/></svg></div>
      <div class="inspo-body">
        <h4><?= owBi('Be the first to share your work here.', 'كن أول من يشارك عمله هنا.') ?></h4>
        <a class="btn btn-primary btn-sm" href="register_artist.php"><?= owBi('Join as Artist', 'انضم كفنان') ?></a>
      </div>
    </div>
    <?php endfor; ?>
  </div>
</div>

<script>
const T={
  en:{dir:'ltr',lb:'العربية',explore:'Explore',community:'Community',support:'Support',login:'Sign In',
    badge:'Artists Together',h1:'The <em>Artist Community</em>',desc:'Art grows when artists meet. Share your work, learn from each other, and make friends who get it.',
    s1:'Artists With Us',s2:'Messages Shared',s3:'Art Styles Covered',s4:'Saudi Community',
    commTitle:'Community Spaces',
    c2:'Professional Artists Guild',c2d:'A space for experienced artists to work together and guide new talent.',c2l:'Apply to Join →',
    c3:'Daily Inspiration',c3d:'A little spark every day. Featured works, color palettes and new techniques to try.',c3l:'Explore →',
    c4:'Teach With Us',c4d:'Love sharing what you know? Become an instructor and help others grow.',c4l:'Apply Now →',
    inspoTitle:'Daily Inspiration',
},
  ar:{dir:'rtl',lb:'English',explore:'استكشف',community:'المجتمع',support:'الدعم',login:'تسجيل الدخول',
    badge:'فنانون معاً',h1:'<em>مجتمع</em> الفنانين',desc:'الفن يكبر حين يلتقي الفنانون. شارك أعمالك، وتعلّم من غيرك، واصنع صداقات تفهم شغفك.',
    s1:'فنانون معنا',s2:'الرسائل المتبادلة',s3:'أسلوب فني موثّق',s4:'المجتمع السعودي',
    commTitle:'مساحات المجتمع',
    c2:'رابطة الرسامين المحترفين',c2d:'مساحة للفنانين المتمرّسين ليتعاونوا ويأخذوا بيد المواهب الجديدة.',c2l:'تقدّم للانضمام →',
    c3:'الإلهام اليومي',c3d:'شرارة صغيرة كل يوم. أعمال مختارة، ولوحات ألوان، وتقنيات جديدة لتجربتها.',c3l:'استكشف →',
    c4:'علّم معنا',c4d:'تحب أن تشارك ما تعرفه؟ كن مدرباً وساعد غيرك على النمو.',c4l:'تقدّم الآن →',
    inspoTitle:'إلهام اليوم',
}
};
let L='en';
const NAV={
  en:{lb:'العربية',community:'Community',marketplace:'Gallery',explore:'Explore Art Styles',support:'Support',
    c2:'Meet Fellow Artists',c3:'Share Your Work',c4:'Learn Together',
    sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',
    login:'Sign In',reg:'Join Free'},
  ar:{lb:'English',community:'المجتمع',marketplace:'المعرض',explore:'استكشف أساليب الرسم',support:'الدعم',
    c2:'تعرّف على فنانين',c3:'شارك أعمالك',c4:'تعلّم معاً',
    sp1:'تواصل معنا',sp2:'كيف يعمل الموقع',sp3:'شروط الاستخدام',
    login:'تسجيل الدخول',reg:'انضم مجاناً'}
};
function applyNav(l){
  const n=NAV[l];
  const set=(id,v)=>{const e=document.getElementById(id);if(e)e.textContent=v;};
  set('lb',n.lb);
  set('nav-community-label',n.community);
  set('nav-marketplace-label',n.marketplace);set('nav-explore-label',n.explore);set('nav-support-label',n.support);
  set('dd-c2',n.c2);set('dd-c3',n.c3);set('dd-c4',n.c4);
  set('dd-sp1',n.sp1);set('dd-sp2',n.sp2);set('dd-sp3',n.sp3);
  set('n-login-t',n.login);set('n-reg-t',n.reg);
  const mk=document.getElementById('nav-marketplace-link');
  if(mk)mk.href=l==='en'?'marketplace.php?lang=en':'marketplace.php?lang=ar';
}
function apply(l){
  const t=T[l];
  document.getElementById('html').lang=l;
  document.getElementById('html').setAttribute('dir',t.dir);
  document.documentElement.setAttribute('dir',t.dir);
  document.getElementById('pg').setAttribute('dir',t.dir);
  document.getElementById('topnav').setAttribute('dir',t.dir);
  applyNav(l);
  const keys=['badge','h1','desc','s1','s2','s3','s4','c2','c2d','c2l','c3','c3d','c3l','c4','c4d','c4l'];
  keys.forEach(k=>{const el=document.getElementById('t-'+k);if(el)el.innerHTML=t[k];});
  // elements whose ids differ from their T keys
  const setH=(id,v)=>{const e=document.getElementById(id);if(e)e.innerHTML=v;};
  setH('t-comm-title',t.commTitle);
  setH('t-inspo-title',t.inspoTitle);
}
function tgl(){L=L==='en'?'ar':'en';apply(L);try{localStorage.setItem('lang',L);}catch(e){}}
try{var _s=localStorage.getItem('lang');if(_s==='ar'||_s==='en')L=_s;}catch(e){}
apply(L);

const v=document.getElementById('vid');
{const on=()=>v.classList.add('on');if(v.readyState>=3)on();v.addEventListener('canplay',on,{once:true});v.addEventListener('playing',on,{once:true});}
v.play().catch(()=>{});
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
