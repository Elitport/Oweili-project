<?php
// marketplace.php — Oweili · The Gallery (live, database-driven; file name kept as marketplace.php)
require_once 'config.php';
require_once __DIR__ . '/artwork_lib.php';
$ART_TYPES = require __DIR__ . '/art_types.php';

$lang = isset($_GET['lang']) && $_GET['lang'] === 'ar' ? 'ar' : 'en';
$ar   = $lang === 'ar';

/* ---- Start filtered by type (e.g. from Explore Art Styles): only a known type is accepted ---- */
$initType = (isset($_GET['type']) && is_string($_GET['type']) && isset($ART_TYPES[$_GET['type']])) ? $_GET['type'] : 'all';
$typeQ    = $initType !== 'all' ? '&type=' . rawurlencode($initType) : '';   // kept when switching language or page

/* ---- Pagination ---- */
$perPage = 12;
$page    = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset  = ($page - 1) * $perPage;

/* ---- Count (for Prev/Next) — PDO prepared ---- */
$countStmt = getDB()->prepare(
    "SELECT COUNT(*) FROM artworks
     WHERE is_approved = 1 AND status IN ('available','auction')" . owNotDraftSql() . "
");
$countStmt->execute();
$total      = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }

/* ---- Fetch this page of artworks, joined with the artist — PDO prepared ---- */
$stmt = getDB()->prepare(
    "SELECT a.id, a.title_en, a.title_ar, a.description_en, a.description_ar,
            a.price, a.type, a.status, a.image_url, a.artist_id,
            u.full_name_en AS artist_en, u.full_name_ar AS artist_ar
     FROM artworks a
     JOIN users u ON u.id = a.artist_id
     WHERE a.is_approved = 1 AND a.status IN ('available','auction')" . owNotDraftSql('a') . "
     ORDER BY a.created_at DESC
     LIMIT :lim OFFSET :off"
);
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$artworks = $stmt->fetchAll();

/* Creation Journey: stage photos + colors, only for the approved artworks on this page */
$pageIds  = array_map(fn($r) => (int) $r['id'], $artworks);
$mpStages = owGetStagesFor($pageIds);
$mpColors = owGetColorsFor($pageIds);

/* Data for the details modal (escaped on output via JSON flags) */
$modalData = [];
foreach ($artworks as $aw) {
    $modalData[(int) $aw['id']] = [
        'title_en'  => $aw['title_en'],
        'title_ar'  => $aw['title_ar'],
        'desc_en'   => $aw['description_en'] ?? '',
        'desc_ar'   => $aw['description_ar'] ?? '',
        'artist_en' => $aw['artist_en'],
        'artist_ar' => $aw['artist_ar'],
        'artist_id' => (int) $aw['artist_id'],
        'price'     => (float) $aw['price'],
        'image'     => $aw['image_url'],
        'status'    => $aw['status'],
        'journey'   => owHasJourney($mpStages[(int) $aw['id']] ?? []),
    ];
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $ar ? 'rtl' : 'ltr' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $ar ? 'المعرض · أويلي' : 'The Gallery · Oweili' ?></title>
  <meta name="description" content="<?= $ar ? 'أعمال أصلية لفنانين حقيقيين، من السعودية ومن كل العالم. لكل قطعة حكاية.' : 'Original works by real artists, from Saudi Arabia and all over the world. Every piece has a story.' ?>">
  <script>
  /* Honor the site-wide saved language. This page switches language via a
     server reload (?lang=), so if the visitor previously chose Arabic on
     another page, redirect to the Arabic version before rendering. */
  (function(){
    var KEY='lang';
    var params=new URLSearchParams(location.search);
    var urlLang=params.get('lang');
    var saved=null; try{ saved=localStorage.getItem(KEY); }catch(e){}
    if(urlLang){ try{ localStorage.setItem(KEY, urlLang==='ar'?'ar':'en'); }catch(e){} }
    else if(saved==='ar'){ params.set('lang','ar'); location.replace(location.pathname+'?'+params.toString()+location.hash); }
  })();
  </script>
  <?php include __DIR__ . '/theme_head.php'; ?>
  <link rel="stylesheet" href="/assets/css/oweili.css?v=<?= @filemtime(__DIR__ . '/assets/css/oweili.css') ?>">
  <link rel="stylesheet" href="/assets/css/proof.css?v=<?= @filemtime(__DIR__ . '/assets/css/proof.css') ?>">
  <style>
    /* Gallery (marketplace.php) layout only — type, colors, buttons, artwork cards and badges come from oweili.css */
    .main-content { position: relative; z-index: 10; width:100%; max-width: 1200px; margin: 0 auto; padding: calc(var(--nav-h) + var(--space-xl)) var(--space-lg) var(--space-2xl); }
    .page-header{margin-bottom:var(--space-md);}
    .page-header h1{font-size:var(--fs-section);margin:0 0 var(--space-xs);}
    .page-header h1 em{font-style:normal;color:var(--color-highlight);}
    .page-header p{font-size:var(--fs-body);max-width:600px;margin:0;}
    .search-box{max-width:420px;margin-bottom:var(--space-sm);}
    .filter-group{display:flex;flex-wrap:wrap;gap:var(--space-xs);margin-bottom:var(--space-sm);align-items:center;}
    .filter-label{font-size:var(--fs-label);font-weight:700;color:var(--color-dark);margin-inline-end:4px;}
    /* filters are chips (on/off choices), so they stay rounded; real buttons use .btn */
    .filter-btn{min-height:36px;padding:0 14px;border-radius:var(--radius-pill);font-family:inherit;font-size:var(--fs-label);font-weight:600;cursor:pointer;border:1px solid var(--color-border-strong);background:var(--color-surface);color:var(--color-dark);transition:background var(--ease),border-color var(--ease);}
    .filter-btn:hover{background:var(--color-hover-light);}
    .filter-btn.active{background:var(--color-dark);color:var(--color-on-dark);border-color:var(--color-dark);}
    .filter-btn:focus-visible{outline:2px solid var(--color-accent);outline-offset:2px;}
    .art-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:var(--space-md);margin-top:var(--space-md);}
    .aw-card.hidden{display:none;}
    .aw-type{font-size:var(--fs-min);font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-accent-dark);}
    .aw-alt{font-size:var(--fs-caption);color:var(--color-body);}
    .aw-body .btn{margin-top:var(--space-sm);}
    .empty-state{display:none;margin-top:var(--space-md);}
    .human-banner{display:flex;align-items:center;justify-content:center;gap:var(--space-xs);text-align:center;margin:0 0 var(--space-md);padding:var(--space-sm) var(--space-md);border-radius:var(--radius-card);font-size:var(--fs-body);font-weight:600;color:var(--color-dark);background:var(--color-tint-blue);border:1px solid rgba(var(--rgb-accent),0.15);}
    .human-banner .hb-icon{font-size:18px;flex-shrink:0;}
    .gallery-empty{text-align:center;padding:var(--space-2xl) var(--space-lg);margin-top:var(--space-sm);}
    .gallery-empty .ge-emoji{font-size:44px;margin-bottom:var(--space-sm);}
    .gallery-empty h2{font-size:var(--fs-card);margin:0 0 var(--space-xs);}
    .gallery-empty p{max-width:460px;margin:0 auto var(--space-md);}
    .pagination{display:flex;justify-content:center;align-items:center;gap:var(--space-sm);margin-top:var(--space-xl);}
    .page-info{font-size:var(--fs-label);color:var(--color-body);font-weight:600;}
    /* details window */
    .modal-bg{position:fixed;inset:0;z-index:2000;background:rgba(var(--rgb-shadow),0.5);backdrop-filter:blur(6px);display:none;align-items:center;justify-content:center;padding:var(--space-sm);}
    .modal-bg.open{display:flex;}
    .modal{background:var(--color-menu);border:1px solid var(--color-border);border-radius:var(--radius-card);max-width:600px;width:100%;box-shadow:0 30px 70px rgba(var(--rgb-shadow),0.25);max-height:90vh;overflow-y:auto;}
    .modal-body{padding:var(--space-md);}
    .modal-img{width:100%;aspect-ratio:4/3;object-fit:contain;background:var(--color-frame);border:1px solid var(--color-border);border-radius:12px;margin-bottom:var(--space-sm);display:block;}
    .modal-body h2{font-size:var(--fs-card);margin:0 0 4px;}
    .modal-body .m-title-ar{font-size:var(--fs-body);color:var(--color-body);margin-bottom:var(--space-xs);}
    .modal-body .m-artist{font-size:var(--fs-caption);font-weight:600;margin-bottom:var(--space-sm);display:inline-block;}
    .modal-body .m-label{font-size:var(--fs-label);font-weight:700;color:var(--color-dark);margin:var(--space-sm) 0 4px;}
    .modal-body p{margin:0;}
    .modal-body .m-price{font-size:var(--fs-card);font-weight:700;color:var(--color-dark);margin-top:var(--space-sm);}
    .modal-close{float:right;width:40px;height:40px;border-radius:var(--radius-btn);background:none;border:1px solid var(--color-border-strong);font-size:22px;cursor:pointer;color:var(--color-dark);line-height:1;}
    .modal-close:hover{background:var(--color-hover-light);}
    [dir="rtl"] .modal-close{float:left;}
    @media(max-width:1024px){ .art-grid{grid-template-columns:repeat(2,1fr);} }
    @media(max-width:768px){ .main-content{padding:calc(var(--nav-h) + var(--space-md)) var(--space-sm) var(--space-xl);} .art-grid{gap:var(--space-sm);} }
    @media(max-width:420px){ .art-grid{grid-template-columns:1fr;} }
  </style>
</head>
<body>

<div class="bg-wrap">
  <video class="bg-video" autoplay loop muted playsinline src="bg.mp4"></video>
</div>

<?php include 'nav.php'; ?>
<script>
/* This page switches language with a reload (?lang=), so the shared menu's
   language button reloads too, and the menu labels are set to match. */
function tgl(){ window.location.href = '?lang=<?= $ar ? 'en' : 'ar' ?><?= e($typeQ) ?>'; }
<?php if ($ar): ?>
(function(){
  var t = {'nav-community-label':'المجتمع','nav-marketplace-label':'المعرض',
    'nav-explore-label':'استكشف أساليب الرسم','nav-support-label':'الدعم',
    'dd-c2':'تعرّف على فنانين','dd-c3':'شارك أعمالك','dd-c4':'تعلّم معاً',
    'dd-sp1':'تواصل معنا','dd-sp2':'كيف يعمل الموقع','dd-sp3':'شروط الاستخدام',
    'n-login-t':'تسجيل الدخول','n-reg-t':'انضم مجاناً','lb':'English'};
  for (var id in t) { var el = document.getElementById(id); if (el) el.textContent = t[id]; }
})();
<?php endif; ?>
</script>

<main class="main-content">

  <div class="page-header">
    <h1><?= $ar ? 'المعرض' : 'The Gallery' ?></h1>
    <p><?= $ar
        ? 'هنا تلتقي بأعمال أصلية لفنانين حقيقيين، من السعودية ومن كل العالم. لكل قطعة حكاية، ولا تتكرر أبداً.'
        : 'Meet original works by real artists, from Saudi Arabia and all over the world. Every piece has a story. None of them repeat.' ?></p>
  </div>

  <!-- HUMAN-ONLY BANNER -->
  <div class="human-banner">
    <span class="hb-icon">✋</span>
    <?= $ar ? 'كل عمل هنا رسمته يد إنسان، بصبر وحب' : 'Every work here was made by a human hand, with patience and love' ?>
  </div>

  <?php if ($total > 0): ?>
  <input type="search" class="input search-box" id="searchBox" aria-label="<?= $ar ? 'ابحث عن عمل فني' : 'Search artworks by title' ?>"
         placeholder="<?= $ar ? 'تبحث عن شيء؟ اكتب اسم العمل…' : 'Looking for something? Type a title…' ?>"
         oninput="onSearch(this.value)">

  <!-- TYPE FILTER -->
  <div class="filter-group">
    <span class="filter-label"><?= $ar?'النوع':'Type' ?></span>
    <button class="filter-btn<?= $initType === 'all' ? ' active' : '' ?>" data-type="all" onclick="setType('all',this)"><?= $ar?'الكل':'All' ?></button>
    <?php foreach ($ART_TYPES as $slug => $lbl): ?>
    <button class="filter-btn<?= $initType === $slug ? ' active' : '' ?>" data-type="<?= e($slug) ?>" onclick="setType('<?= e($slug) ?>',this)"><?= e($ar ? $lbl['ar'] : $lbl['en']) ?></button>
    <?php endforeach; ?>
  </div>

  <!-- STATUS FILTER -->
  <div class="filter-group">
    <span class="filter-label"><?= $ar?'الحالة':'Status' ?></span>
    <button class="filter-btn active" data-status="all" onclick="setStatus('all',this)"><?= $ar?'الكل':'All' ?></button>
    <button class="filter-btn" data-status="available" onclick="setStatus('available',this)"><?= $ar?'متاح':'Available' ?></button>
    <button class="filter-btn" data-status="auction" onclick="setStatus('auction',this)"><?= $ar?'مزاد':'Auction' ?></button>
  </div>
  <?php endif; ?>

  <div class="art-grid" id="artGrid">
    <?php foreach ($artworks as $aw):
      $st = $aw['status']; // available | auction (sold excluded by query)
        $badgeLabel = $st === 'auction' ? ($ar?'مزاد':'Auction') : ($st === 'sold' ? ($ar?'مباع':'Sold') : ($ar?'متاح':'Available'));
    ?>
      <article class="aw-card" data-type="<?= e($aw['type']) ?>" data-status="<?= e($st) ?>"
           data-title-en="<?= e($aw['title_en']) ?>" data-title-ar="<?= e($aw['title_ar']) ?>">
        <div class="aw-img">
          <img loading="lazy" src="<?= e($aw['image_url']) ?>" alt="<?= e($aw['title_en'] . ' · ' . $aw['title_ar']) ?>">
          <span class="aw-status"><?= e($badgeLabel) ?></span>
        </div>
        <div class="aw-body">
          <?php if ($ar): ?>
            <div class="aw-title"><?= e($aw['title_ar'] ?: $aw['title_en']) ?></div>
            <?php if (!empty($aw['title_en'])): ?><div class="aw-alt" lang="en"><?= e($aw['title_en']) ?></div><?php endif; ?>
          <?php else: ?>
            <div class="aw-title"><?= e($aw['title_en']) ?></div>
            <?php if (!empty($aw['title_ar'])): ?><div class="aw-alt" lang="ar" dir="rtl"><?= e($aw['title_ar']) ?></div><?php endif; ?>
          <?php endif; ?>
          <a class="aw-artist" href="artist_dashboard.php?id=<?= (int) $aw['artist_id'] ?>&lang=<?= $lang ?>"><?= $ar?'بواسطة':'by' ?> <?= e($ar ? $aw['artist_ar'] : $aw['artist_en']) ?></a>
          <?php $tlbl = $ART_TYPES[$aw['type']] ?? null; ?>
          <?php if ($tlbl): ?><div class="aw-type"><?= e($ar ? $tlbl['ar'] : $tlbl['en']) ?></div><?php endif; ?>
          <?php if (owHasJourney($mpStages[(int) $aw['id']] ?? [])): ?>
            <span class="human-badge"><?= owBi('✓ Human Art', '✓ فن بشري') ?></span>
          <?php endif; ?>
          <div class="aw-price"><?= number_format((float) $aw['price']) ?> <?= $ar?'ر.س':'SAR' ?></div>
          <button type="button" class="btn btn-dark btn-block" onclick="openModal(<?= (int) $aw['id'] ?>)"><?= $ar?'شاهد القصة':'See the Story' ?></button>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <!-- EMPTY STATE -->
  <div class="card empty-state" id="emptyState"
       data-en="Nothing matches yet. Try another word or style." data-ar="لا يوجد ما يطابق بحثك. جرّب كلمة أو أسلوباً آخر.">
    <?php if ($total === 0): ?>
      <?= $ar ? 'لا يوجد ما يطابق بحثك. جرّب كلمة أو أسلوباً آخر.' : 'Nothing matches yet. Try another word or style.' ?>
    <?php else: ?>
      <?= $ar ? 'لا يوجد ما يطابق بحثك. جرّب كلمة أو أسلوباً آخر.' : 'Nothing matches yet. Try another word or style.' ?>
    <?php endif; ?>
  </div>
  <?php if ($total === 0): ?>
    <div class="card gallery-empty">
      <div class="ge-emoji">🎨</div>
      <h2><?= $ar ? 'المعرض ينتظر أول لمسة فرشاة' : 'The gallery is waiting for its first brushstroke' ?></h2>
      <p><?= $ar ? 'هل أنت فنان؟ قد يكون عملك أول ما يراه الناس هنا.' : 'Are you an artist? Your work could be the first one here.' ?></p>
      <a class="btn btn-primary" href="register_artist.php"><?= $ar ? 'انضم كفنان' : 'Join as Artist' ?></a>
    </div>
  <?php endif; ?>

  <!-- PAGINATION -->
  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <a class="btn btn-secondary <?= $page <= 1 ? 'disabled' : '' ?>"
       href="?lang=<?= $lang ?>&page=<?= max(1, $page - 1) ?><?= e($typeQ) ?>"><?= $ar?'السابق':'Previous' ?></a>
    <span class="page-info"><?= $ar ? "صفحة $page من $totalPages" : "Page $page of $totalPages" ?></span>
    <a class="btn btn-secondary <?= $page >= $totalPages ? 'disabled' : '' ?>"
       href="?lang=<?= $lang ?>&page=<?= min($totalPages, $page + 1) ?><?= e($typeQ) ?>"><?= $ar?'التالي':'Next' ?></a>
  </div>
  <?php endif; ?>

</main>

<!-- DETAILS MODAL -->
<div class="modal-bg" id="modalBg" onclick="if(event.target===this)closeModal()">
  <div class="modal">
    <div class="modal-body">
      <button class="modal-close" onclick="closeModal()" aria-label="<?= $ar ? 'إغلاق' : 'Close' ?>">&times;</button>
      <img id="m-img" class="modal-img" src="" alt="">
      <h2 id="m-title-en"></h2>
      <span class="human-badge" id="m-verified" hidden><?= owBi('✓ Human Art', '✓ فن بشري') ?></span>
      <div class="m-title-ar" id="m-title-ar" dir="rtl"></div>
      <a class="m-artist" id="m-artist" href="#"></a>
      <div class="m-label" id="m-label-en-h"><?= $ar?'عن العمل (بالإنجليزية)':'About this work (English)' ?></div>
      <p id="m-desc-en"></p>
      <div class="m-label" id="m-label-ar-h"><?= $ar?'عن العمل (بالعربية)':'About this work (Arabic)' ?></div>
      <p id="m-desc-ar" dir="rtl"></p>
      <div class="m-price" id="m-price"></div>
      <?php foreach ($artworks as $aw):
        $jid = (int) $aw['id'];
        $jh  = owJourneyHtml($mpStages[$jid] ?? [], $mpColors[$jid] ?? [], (string) $aw['type'], $ART_TYPES);
        if ($jh === '') continue; ?>
      <div class="m-journey" data-id="<?= $jid ?>" hidden><?= $jh ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
const LANG = <?= json_encode($lang) ?>;
const ARTWORKS = <?= json_encode($modalData, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?>;

/* ---- Filters + search (client-side, no reload) ---- */
let curType = <?= json_encode($initType) ?>, curStatus = 'all', curSearch = '';
if (curType !== 'all') document.addEventListener('DOMContentLoaded', applyFilters);

function sanitizeSearch(v){
  // strip characters that have no business in a title search
  return String(v).replace(/[<>"'`]/g, '').trim().toLowerCase();
}
function setType(t, btn){
  curType = t;
  document.querySelectorAll('[data-type]').forEach(b=>{ if(b.classList.contains('filter-btn')) b.classList.remove('active'); });
  btn.classList.add('active');
  applyFilters();
}
function setStatus(s, btn){
  curStatus = s;
  document.querySelectorAll('[data-status].filter-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  applyFilters();
}
function onSearch(v){ curSearch = sanitizeSearch(v); applyFilters(); }

function applyFilters(){
  let visible = 0;
  document.querySelectorAll('#artGrid .aw-card').forEach(card=>{
    const okType   = curType === 'all'   || card.dataset.type === curType;
    const okStatus = curStatus === 'all' || card.dataset.status === curStatus;
    const okSearch = !curSearch
      || (card.dataset.titleEn||'').toLowerCase().includes(curSearch)
      || (card.dataset.titleAr||'').toLowerCase().includes(curSearch);
    const show = okType && okStatus && okSearch;
    card.classList.toggle('hidden', !show);
    if (show) visible++;
  });
  const empty = document.getElementById('emptyState');
  empty.style.display = visible === 0 ? 'block' : 'none';
}

/* ---- Details modal ---- */
function esc(s){ return String(s==null?'':s); }
function openModal(id){
  const a = ARTWORKS[id];
  if(!a) return;
  document.getElementById('m-img').src = a.image;
  document.getElementById('m-img').alt = a.title_en + ' · ' + a.title_ar;
  document.getElementById('m-title-en').textContent = a.title_en;
  document.getElementById('m-title-ar').textContent = a.title_ar;
  const artist = document.getElementById('m-artist');
  artist.textContent = (LANG==='ar' ? 'بواسطة ' : 'by ') + (LANG==='ar' ? a.artist_ar : a.artist_en);
  artist.href = 'artist_dashboard.php?id=' + a.artist_id + '&lang=' + LANG;
  document.getElementById('m-desc-en').textContent = esc(a.desc_en);
  document.getElementById('m-desc-ar').textContent = esc(a.desc_ar);
  document.getElementById('m-price').textContent =
    Number(a.price).toLocaleString() + ' ' + (LANG==='ar' ? 'ر.س' : 'SAR');
  // Creation Journey + trust badge (only for artworks with stage photos)
  document.getElementById('m-verified').hidden = !a.journey;
  document.querySelectorAll('.m-journey').forEach(function(j){ j.hidden = j.dataset.id !== String(id); });
  document.getElementById('modalBg').classList.add('open');
}
function closeModal(){ document.getElementById('modalBg').classList.remove('open'); }
document.addEventListener('keydown', e=>{ if(e.key==='Escape') closeModal(); });
/* Links like marketplace.php#artwork-12 (from the Community page) open that artwork */
(function(){
  const m = /^#artwork-(\d+)$/.exec(location.hash);
  if (m && ARTWORKS[m[1]]) openModal(m[1]);
})();
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
