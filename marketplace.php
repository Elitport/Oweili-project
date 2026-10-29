<?php
// marketplace.php — Oweili · The Gallery (live, database-driven; file name kept as marketplace.php)
require_once 'config.php';
require_once __DIR__ . '/artwork_lib.php';
$ART_TYPES = require __DIR__ . '/art_types.php';
$ART_CATS  = require __DIR__ . '/art_categories.php';

$lang = isset($_GET['lang']) && $_GET['lang'] === 'ar' ? 'ar' : 'en';
$ar   = $lang === 'ar';

/* ---- What the visitor asked for: every value is checked against a fixed list ---- */
$getStr  = fn(string $k): string => (isset($_GET[$k]) && is_string($_GET[$k])) ? $_GET[$k] : '';
$SORTS   = [
    'newest'     => 'a.created_at DESC, a.id DESC',
    'price_low'  => 'a.price ASC, a.created_at DESC, a.id DESC',
    'price_high' => 'a.price DESC, a.created_at DESC, a.id DESC',
];
$initType = isset($ART_TYPES[$getStr('type')]) ? $getStr('type') : '';      // one exact type (links from Explore Art Styles)
$cat      = isset($ART_CATS[$getStr('cat')]) ? $getStr('cat') : 'all';
if ($initType !== '') {                                                      // light up the category that holds this type
    $cat = 'all';
    foreach ($ART_CATS as $ck => $cv) { if (in_array($initType, $cv['types'], true)) { $cat = $ck; break; } }
}
$status   = in_array($getStr('status'), ['available', 'auction'], true) ? $getStr('status') : 'all';
$sort     = isset($SORTS[$getStr('sort')]) ? $getStr('sort') : 'newest';
$q        = mb_substr(trim(preg_replace('/[\x00-\x1F\x7F<>"\'`]/u', '', $getStr('q')) ?? ''), 0, 60);
$page     = max(1, (int) $getStr('page'));

/* A link to this page with some choices changed (defaults are left out, so links stay short) */
function mpUrl(array $over = []): string {
    global $lang, $cat, $initType, $status, $sort, $q;
    $st = array_merge(['lang' => $lang, 'cat' => $cat, 'type' => $initType, 'status' => $status, 'sort' => $sort, 'q' => $q, 'page' => 1], $over);
    $defaults = ['lang' => 'en', 'cat' => 'all', 'type' => '', 'status' => 'all', 'sort' => 'newest', 'q' => '', 'page' => 1];
    foreach ($defaults as $k => $d) { if ((string) $st[$k] === (string) $d) unset($st[$k]); }
    return 'marketplace.php' . ($st ? '?' . http_build_query($st) : '');
}

/* ---- One set of conditions for the count and the page: approved, for sale, artist not banned, no drafts ---- */
$where  = "a.is_approved = 1 AND a.status IN ('available','auction') AND u.is_banned = 0" . owNotDraftSql('a');
$params = [];
if ($status !== 'all') { $where .= ' AND a.status = :status'; $params[':status'] = $status; }
$typeList = $initType !== '' ? [$initType] : $ART_CATS[$cat]['types'];
if ($typeList) {
    $ph = [];
    foreach (array_values($typeList) as $i => $t) { $ph[] = ':t' . $i; $params[':t' . $i] = $t; }
    $where .= ' AND a.type IN (' . implode(',', $ph) . ')';
}
if ($q !== '') {
    $like = '%' . addcslashes($q, '\\%_') . '%';
    $where .= ' AND (a.title_en LIKE :q1 OR a.title_ar LIKE :q2)';
    $params[':q1'] = $like; $params[':q2'] = $like;
}

/* ---- Pagination ---- */
$perPage = 12;
$countStmt = getDB()->prepare("SELECT COUNT(*) FROM artworks a JOIN users u ON u.id = a.artist_id WHERE $where");
$countStmt->execute($params);
$total      = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

/* ---- This page of artworks, joined with the artist — PDO prepared, sort from the fixed list ---- */
$stmt = getDB()->prepare(
    "SELECT a.id, a.title_en, a.title_ar, a.description_en, a.description_ar,
            a.price, a.type, a.status, a.image_url, a.artist_id,
            " . owArtistNameSql('u') . "
     FROM artworks a
     JOIN users u ON u.id = a.artist_id
     WHERE $where
     ORDER BY " . $SORTS[$sort] . "
     LIMIT :lim OFFSET :off"
);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$artworks = $stmt->fetchAll();
$filtered = $cat !== 'all' || $initType !== '' || $status !== 'all' || $q !== '';

/* Creation Journey: stage photos + colors, only for the approved artworks on this page */
$pageIds  = array_map(fn($r) => (int) $r['id'], $artworks);
$mpStages = owGetStagesFor($pageIds);
$mpColors = owGetColorsFor($pageIds);

/* Pictures: cleaned up by owImgSrc() (artwork_lib.php) */
$mpImg = 'owImgSrc';

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
        'image'     => $mpImg((string) $aw['image_url']),
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
  <link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
  <link rel="stylesheet" href="<?= owAsset('assets/css/proof.css') ?>">
  <style>
    /* Gallery (marketplace.php) layout only — colors, type sizes and buttons come from oweili.css */
    .main-content { position: relative; z-index: 10; width:100%; max-width: 1200px; margin: 0 auto; padding: calc(var(--nav-h) + var(--space-xl)) var(--space-lg) var(--space-2xl); }
    .page-header{margin-bottom:var(--space-md);animation:mpRise .6s cubic-bezier(.22,1,.36,1) both;}
    .page-header h1{font-size:var(--fs-section);margin:0 0 var(--space-xs);}
    .page-header p{font-size:var(--fs-body);max-width:600px;margin:0;}
    .human-banner{text-align:center;margin:0 0 var(--space-lg);padding:var(--space-sm) var(--space-md);border-radius:var(--radius-card);font-size:var(--fs-body);font-weight:600;color:var(--color-dark);background:var(--color-tint-blue);border:1px solid rgba(var(--rgb-accent),0.15);animation:mpRise .6s .08s cubic-bezier(.22,1,.36,1) both;}

    /* filters: search, 6 category pills, then a small row with status and sort */
    .mp-tools{display:flex;flex-direction:column;gap:var(--space-sm);margin-bottom:var(--space-md);animation:mpRise .6s .14s cubic-bezier(.22,1,.36,1) both;}
    .mp-search{display:flex;gap:var(--space-xs);max-width:460px;}
    .mp-search .input{flex:1;min-width:0;}
    .cat-pills{display:flex;flex-wrap:wrap;gap:var(--space-xs);}
    .cat-pill{display:inline-flex;align-items:center;min-height:40px;padding:0 18px;border-radius:var(--radius-pill);font-size:var(--fs-button);font-weight:700;text-decoration:none;color:var(--color-dark);background:var(--color-surface);border:1px solid var(--color-border-strong);transition:background var(--ease),border-color var(--ease),color var(--ease);}
    .cat-pill:hover{background:var(--color-hover-light);color:var(--color-dark);}
    .cat-pill.active{background:var(--color-dark);border-color:var(--color-dark);color:var(--color-on-dark);}
    .cat-pill:focus-visible,.st-pill:focus-visible,.type-chip:focus-visible{outline:2px solid var(--color-accent);outline-offset:2px;}
    .mp-row{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:var(--space-sm);}
    .st-pills{display:flex;flex-wrap:wrap;align-items:center;gap:6px;}
    .mp-label{font-size:var(--fs-label);font-weight:700;color:var(--color-dark);margin-inline-end:4px;}
    .st-pill{display:inline-flex;align-items:center;min-height:32px;padding:0 12px;border-radius:var(--radius-pill);font-size:var(--fs-label);font-weight:600;text-decoration:none;color:var(--color-body);border:1px solid var(--color-border);background:transparent;transition:background var(--ease),color var(--ease);}
    .st-pill:hover{background:var(--color-hover-light);color:var(--color-dark);}
    .st-pill.active{color:var(--color-dark);border-color:var(--color-dark);background:var(--color-surface);}
    .mp-sort{display:flex;align-items:center;gap:var(--space-xs);}
    .mp-sort select{min-height:40px;padding:0 36px 0 12px;border-radius:var(--radius-input);border:1px solid var(--color-border-strong);background:var(--color-surface);color:var(--color-dark);font-size:var(--fs-label);font-weight:600;cursor:pointer;}
    [dir="rtl"] .mp-sort select{padding:0 12px 0 36px;}
    .mp-sort select:focus-visible{outline:2px solid var(--color-accent);outline-offset:2px;}
    .type-chip{display:inline-flex;align-items:center;gap:8px;align-self:flex-start;min-height:32px;padding:0 12px;border-radius:var(--radius-pill);font-size:var(--fs-label);font-weight:600;text-decoration:none;color:var(--color-dark);background:var(--color-tint-blue);border:1px solid rgba(var(--rgb-accent),0.2);}
    .type-chip span{font-size:16px;line-height:1;}
    .mp-count{font-size:var(--fs-label);color:var(--color-body);margin:0 0 var(--space-sm);}

    /* artwork cards */
    .art-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:var(--space-md);}
    .mp-card{border-radius:16px;animation:mpRise .5s cubic-bezier(.22,1,.36,1) both;}
    .mp-card:nth-child(2){animation-delay:.04s}.mp-card:nth-child(3){animation-delay:.08s}.mp-card:nth-child(4){animation-delay:.12s}
    .mp-card:nth-child(n+5){animation-delay:.16s}
    .mp-card:hover{transform:translateY(-6px);}
    .mp-card .aw-img{background:var(--color-surface);}
    .mp-card .aw-img img{object-fit:cover;background:var(--color-surface);transition:transform .6s cubic-bezier(.22,1,.36,1);}
    .mp-card:hover .aw-img img{transform:scale(1.03);}
    .mp-card .aw-body{gap:6px;padding:var(--space-sm) var(--space-sm) var(--space-sm);}
    .mp-card .aw-title{font-size:15px;line-height:1.4;margin:0;}
    .mp-card .aw-artist{font-size:var(--fs-caption);}
    .mp-meta{display:flex;flex-wrap:wrap;gap:6px;margin-top:2px;}
    .type-pill{display:inline-flex;align-items:center;padding:2px 10px;border-radius:var(--radius-pill);font-size:var(--fs-min);font-weight:700;line-height:1.6;white-space:nowrap;background:var(--color-dark);color:var(--color-on-dark);}
    .mp-card .aw-price{font-size:16px;padding-top:var(--space-xs);}
    .mp-card .btn{margin-top:var(--space-xs);border-radius:8px;}

    /* empty state */
    .gallery-empty{text-align:center;padding:var(--space-2xl) var(--space-lg);animation:mpRise .5s cubic-bezier(.22,1,.36,1) both;}
    .gallery-empty svg{width:48px;height:48px;fill:none;stroke:var(--color-light);stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round;margin-bottom:var(--space-sm);}
    .gallery-empty h2{font-size:var(--fs-subtitle);margin:0 0 var(--space-md);}
    .gallery-empty .ge-actions{display:flex;flex-wrap:wrap;gap:var(--space-xs);justify-content:center;}

    .pagination{display:flex;justify-content:center;align-items:center;gap:var(--space-sm);margin-top:var(--space-xl);}
    .page-info{font-size:var(--fs-label);color:var(--color-body);font-weight:600;}
    .pagination .disabled{opacity:.4;pointer-events:none;}

    /* details window */
    .modal-bg{position:fixed;inset:0;z-index:2000;background:rgba(var(--rgb-shadow),0.5);backdrop-filter:blur(6px);display:none;align-items:center;justify-content:center;padding:var(--space-sm);}
    .modal-bg.open{display:flex;}
    .modal{background:var(--color-menu);border:1px solid var(--color-border);border-radius:var(--radius-card);max-width:600px;width:100%;box-shadow:0 30px 70px rgba(var(--rgb-shadow),0.25);max-height:90vh;overflow-y:auto;animation:mpRise .35s cubic-bezier(.22,1,.36,1) both;}
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

    @keyframes mpRise{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:none;}}
    @media (prefers-reduced-motion: reduce){ .page-header,.human-banner,.mp-tools,.mp-card,.gallery-empty,.modal{animation:none;} .mp-card:hover,.mp-card:hover .aw-img img{transform:none;} }
    @media(max-width:1024px){ .art-grid{grid-template-columns:repeat(3,1fr);} }
    @media(max-width:768px){ .main-content{padding:calc(var(--nav-h) + var(--space-md)) var(--space-sm) var(--space-xl);} .art-grid{gap:var(--space-sm);} .mp-search{max-width:none;} }
    @media(max-width:640px){ .art-grid{grid-template-columns:repeat(2,1fr);} }
    @media(max-width:420px){ .mp-card .aw-body{padding:12px;} .mp-card .btn{padding:0 8px;} .cat-pill{padding:0 14px;} }
  </style>
</head>
<body>

<div class="bg-wrap">
  <video class="bg-video" autoplay loop muted playsinline src="<?= owBase() ?>bg.mp4"></video>
</div>

<?php include 'nav.php'; ?>
<script>
/* This page switches language with a reload (?lang=), so the shared menu's
   language button reloads too, and the menu labels are set to match. */
function tgl(){ window.location.href = <?= json_encode(mpUrl(['lang' => $ar ? 'en' : 'ar', 'page' => $page]), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>; }
<?php if ($ar): ?>
(function(){
  var t = {'nav-community-label':'المجتمع','nav-marketplace-label':'المعرض',
    'nav-explore-label':'استكشف أساليب الرسم','nav-support-label':'الدعم',
    
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
    <?= $ar ? 'كل عمل هنا رسمته يد إنسان، بصبر وحب' : 'Every piece here was drawn by a human hand, with patience and love.' ?>
  </div>

  <div class="mp-tools">
    <!-- SEARCH + SORT (one small form, so every choice travels together) -->
    <form class="mp-search" method="get" action="marketplace.php" role="search" id="mpForm">
      <?php if ($ar): ?><input type="hidden" name="lang" value="ar"><?php endif; ?>
      <?php if ($cat !== 'all' && $initType === ''): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif; ?>
      <?php if ($initType !== ''): ?><input type="hidden" name="type" value="<?= e($initType) ?>"><?php endif; ?>
      <?php if ($status !== 'all'): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
      <input type="search" class="input" name="q" maxlength="60" value="<?= e($q) ?>"
             aria-label="<?= $ar ? 'ابحث عن عمل فني' : 'Search artworks by title' ?>"
             placeholder="<?= $ar ? 'تبحث عن شيء؟ اكتب اسم العمل…' : 'Looking for something? Type a title…' ?>">
      <button type="submit" class="btn btn-secondary"><?= $ar ? 'ابحث' : 'Search' ?></button>
    </form>

    <!-- 6 CATEGORIES -->
    <nav class="cat-pills" aria-label="<?= $ar ? 'الفئات' : 'Categories' ?>">
      <?php foreach ($ART_CATS as $ck => $cv): ?>
      <a class="cat-pill<?= $cat === $ck ? ' active' : '' ?>" href="<?= e(mpUrl(['cat' => $ck, 'type' => ''])) ?>"<?= $cat === $ck ? ' aria-current="page"' : '' ?>><?= e($ar ? $cv['ar'] : $cv['en']) ?></a>
      <?php endforeach; ?>
    </nav>

    <?php if ($initType !== ''): $tl = $ART_TYPES[$initType]; ?>
    <a class="type-chip" href="<?= e(mpUrl(['type' => ''])) ?>" aria-label="<?= $ar ? 'إزالة هذا الأسلوب' : 'Remove this style' ?>"><?= e($ar ? $tl['ar'] : $tl['en']) ?> <span aria-hidden="true">×</span></a>
    <?php endif; ?>

    <!-- STATUS + SORT -->
    <div class="mp-row">
      <div class="st-pills">
        <span class="mp-label"><?= $ar ? 'الحالة' : 'Status' ?></span>
        <?php foreach (['all' => ['All', 'الكل'], 'available' => ['Available', 'متاح'], 'auction' => ['Auction', 'مزاد']] as $sk => $sl): ?>
        <a class="st-pill<?= $status === $sk ? ' active' : '' ?>" href="<?= e(mpUrl(['status' => $sk])) ?>"<?= $status === $sk ? ' aria-current="page"' : '' ?>><?= $ar ? $sl[1] : $sl[0] ?></a>
        <?php endforeach; ?>
      </div>
      <div class="mp-sort">
        <label class="mp-label" for="mpSort"><?= $ar ? 'الترتيب' : 'Sort' ?></label>
        <select id="mpSort" name="sort" form="mpForm" onchange="this.form.submit()">
          <?php foreach (['newest' => ['Newest First', 'الأحدث أولاً'], 'price_low' => ['Price: Low to High', 'السعر: من الأقل'], 'price_high' => ['Price: High to Low', 'السعر: من الأعلى']] as $ok => $ol): ?>
          <option value="<?= $ok ?>"<?= $sort === $ok ? ' selected' : '' ?>><?= $ar ? $ol[1] : $ol[0] ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <?php if ($total > 0): ?>
  <p class="mp-count"><?= $ar ? ('عدد الأعمال: ' . $total) : ($total === 1 ? '1 artwork' : $total . ' artworks') ?></p>
  <div class="art-grid" id="artGrid">
    <?php foreach ($artworks as $aw):
      $id  = (int) $aw['id'];
      $img = $mpImg((string) $aw['image_url']);
      $tEn = (string) $aw['title_en']; $tAr = (string) ($aw['title_ar'] ?: $aw['title_en']);
      $tlbl = $ART_TYPES[$aw['type']] ?? null; ?>
      <article class="aw-card mp-card">
        <div class="aw-img">
          <?= owArtImgHtml($img, $tEn . ' · ' . $tAr) ?>
          <span class="aw-status"><?= $aw['status'] === 'auction' ? ($ar ? 'مزاد' : 'Auction') : ($ar ? 'متاح' : 'Available') ?></span>
        </div>
        <div class="aw-body">
          <h2 class="aw-title"><?= e($ar ? $tAr : ($tEn ?: $tAr)) ?></h2>
          <a class="aw-artist" href="artist_dashboard.php?id=<?= (int) $aw['artist_id'] ?>&amp;lang=<?= $lang ?>"><?= $ar ? 'بواسطة' : 'by' ?> <?= e($ar ? $aw['artist_ar'] : $aw['artist_en']) ?></a>
          <div class="mp-meta">
            <?php if ($tlbl): ?><span class="type-pill"><?= e($ar ? $tlbl['ar'] : $tlbl['en']) ?></span><?php endif; ?>
            <?php if (owHasJourney($mpStages[$id] ?? [])): ?><span class="human-badge"><?= owBi('✦ A Real Touch', '✦ لمسة حقيقية') ?></span><?php endif; ?>
          </div>
          <div class="aw-price"><?= number_format((float) $aw['price']) ?> <?= $ar ? 'ر.س' : 'SAR' ?></div>
          <button type="button" class="btn btn-dark btn-block" onclick="openModal(<?= $id ?>)"><?= $ar ? 'شاهد القصة' : 'See the Story' ?></button>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <!-- EMPTY STATE -->
  <div class="card gallery-empty">
    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 16l5-5 4 4 3-3 6 6"/><circle cx="16" cy="9" r="1.6"/></svg>
    <h2><?= $ar ? 'لا أعمال هنا بعد. كن أول من يشارك عمله.' : 'No artworks here yet. Be the first to share yours.' ?></h2>
    <div class="ge-actions">
      <a class="btn btn-primary" href="register_artist.php"><?= $ar ? 'انضم كفنان' : 'Join as Artist' ?></a>
      <?php if ($filtered): ?><a class="btn btn-secondary" href="<?= e(mpUrl(['cat' => 'all', 'type' => '', 'status' => 'all', 'q' => ''])) ?>"><?= $ar ? 'شاهد كل الأعمال' : 'See all artworks' ?></a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- PAGINATION -->
  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <a class="btn btn-secondary<?= $page <= 1 ? ' disabled' : '' ?>" href="<?= e(mpUrl(['page' => max(1, $page - 1)])) ?>"<?= $page <= 1 ? ' aria-disabled="true" tabindex="-1"' : '' ?>><?= $ar ? 'السابق' : 'Previous' ?></a>
    <span class="page-info"><?= $ar ? "صفحة $page من $totalPages" : "Page $page of $totalPages" ?></span>
    <a class="btn btn-secondary<?= $page >= $totalPages ? ' disabled' : '' ?>" href="<?= e(mpUrl(['page' => min($totalPages, $page + 1)])) ?>"<?= $page >= $totalPages ? ' aria-disabled="true" tabindex="-1"' : '' ?>><?= $ar ? 'التالي' : 'Next' ?></a>
  </div>
  <?php endif; ?>

</main>

<!-- DETAILS MODAL -->
<div class="modal-bg" id="modalBg" onclick="if(event.target===this)closeModal()">
  <div class="modal">
    <div class="modal-body">
      <button class="modal-close" onclick="closeModal()" aria-label="<?= $ar ? 'إغلاق' : 'Close' ?>">&times;</button>
      <img id="m-img" class="modal-img" src="" alt="" onerror="this.hidden=true">
      <h2 id="m-title-en"></h2>
      <span class="human-badge" id="m-verified" hidden><?= owBi('✦ A Real Touch', '✦ لمسة حقيقية') ?></span>
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

/* ---- Details modal ---- */
function esc(s){ return String(s==null?'':s); }
function openModal(id){
  const a = ARTWORKS[id];
  if(!a) return;
  const mImg = document.getElementById('m-img');
  mImg.hidden = !a.image;
  if (a.image) mImg.src = a.image;
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
