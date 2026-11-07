<?php
// marketplace.php — Oweili · The Gallery (live, database-driven; file name kept as marketplace.php)
require_once 'config.php';
require_once __DIR__ . '/studio_lib.php'; require_once __DIR__ . '/page_cache.php'; owPublicPage();   // public page: Back opens at once, early load on touch (page_cache.php)
require_once __DIR__ . '/artwork_lib.php';
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/gallery_lib.php';   // filters, collections, Artist of the week, Verified artist
$ART_TYPES = require __DIR__ . '/art_types.php';
$ART_CATS  = require __DIR__ . '/art_categories.php';

require_once __DIR__ . '/lang_lib.php';
$lang = owLang();
$ar   = $lang === 'ar';

/* Who is looking: a signed-in buyer gets hearts that save, and the
   "I'm Interested" form filled in. Guests see the heart too (it asks them
   to sign in); artists and staff do not. */
$viewer  = isLoggedIn() ? currentUser() : null;
$isBuyer = $viewer && ($viewer['role'] ?? '') === 'collector';
$showFav = (!$viewer || $isBuyer) && owHasTable('favorites');
$favIds  = $isBuyer ? array_flip(owFavIds((int) $viewer['id'])) : [];
$pre     = $isBuyer ? ['name' => (string) ($viewer['full_name_en'] ?: $viewer['full_name_ar']), 'phone' => (string) ($viewer['phone'] ?? ''), 'email' => (string) $viewer['email']]
                    : ['name' => '', 'phone' => '', 'email' => ''];
$favLbl  = $ar ? ['on' => 'احفظ في المفضلة', 'off' => 'أزل من المفضلة'] : ['on' => 'Save to favorites', 'off' => 'Remove from favorites'];
const MP_HEART = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 7.9 3.6 4.5 7 4.5c2 0 3.6 1.1 5 2.9 1.4-1.8 3-2.9 5-2.9 3.4 0 5.6 3.4 4.3 6.8-1.8 4.6-9.3 9.2-9.3 9.2z"/></svg>';

/* ---- What the visitor asked for: every value is checked against a fixed list ---- */
$getStr  = fn(string $k): string => (isset($_GET[$k]) && is_string($_GET[$k])) ? $_GET[$k] : '';
$SORTS   = [
    'newest'     => 'a.created_at DESC, a.id DESC',
    'price_low'  => 'a.price ASC, a.created_at DESC, a.id DESC',
    'price_high' => 'a.price DESC, a.created_at DESC, a.id DESC',
    'popular'    => (owHasColumn('artworks', 'view_count') ? 'a.view_count DESC, ' : '') . 'a.created_at DESC, a.id DESC',
];
$MEDIUMS = owMediumFilters(); $PRICES = owPriceFilters(); $SIZES = owSizeFilters(); $ORIENTS = owOrientFilters(); $COLORS = owColorFamilies();
$CITIES  = owGalleryCities();
$hasDims = owHasColumn('artworks', 'size_category') && owHasColumn('artworks', 'orientation');   // the standard sizes (size_lib.php)
$hasMed  = owHasColumn('artworks', 'medium');
$initType = isset($ART_TYPES[$getStr('type')]) ? $getStr('type') : '';      // one exact type (links from Art Styles)
$cat      = isset($ART_CATS[$getStr('cat')]) ? $getStr('cat') : 'all';
if ($initType !== '') {                                                      // light up the category that holds this type
    $cat = 'all';
    foreach ($ART_CATS as $ck => $cv) { if (in_array($initType, $cv['types'], true)) { $cat = $ck; break; } }
}
$status   = in_array($getStr('status'), ['available', 'auction'], true) ? $getStr('status') : 'all';
$sort     = isset($SORTS[$getStr('sort')]) ? $getStr('sort') : 'newest';
$q        = mb_substr(trim(preg_replace('/[\x00-\x1F\x7F<>"\'`]/u', '', $getStr('q')) ?? ''), 0, 60);
$page     = max(1, (int) $getStr('page'));
$medium   = $hasMed && isset($MEDIUMS[$getStr('medium')]) ? $getStr('medium') : '';
$price    = isset($PRICES[$getStr('price')]) ? $getStr('price') : '';
$sizeIn   = ['small' => 's', 'medium' => 'm', 'large' => 'l'][$getStr('size')] ?? $getStr('size');   // older links keep working
$size     = $hasDims && isset($SIZES[$sizeIn]) ? $sizeIn : '';
$orient   = $hasDims && isset($ORIENTS[$getStr('orient')]) ? $getStr('orient') : '';
$city     = in_array($getStr('city'), $CITIES, true) ? $getStr('city') : '';
$color    = isset($COLORS[$getStr('color')]) ? $getStr('color') : '';
$vonly    = $getStr('verified') === '1' && owVerifiedReady() ? '1' : '';
$colObj   = $getStr('col') !== '' ? owCollectionBySlug($getStr('col')) : null;
$col      = $colObj ? (string) $colObj['slug'] : '';

/* A link to this page with some choices changed (defaults are left out, so links stay short) */
function mpUrl(array $over = []): string {
    global $lang, $cat, $initType, $status, $sort, $q, $medium, $price, $size, $orient, $city, $color, $vonly, $col;
    $st = array_merge(['lang' => $lang, 'col' => $col, 'cat' => $cat, 'type' => $initType, 'status' => $status, 'medium' => $medium, 'price' => $price, 'size' => $size, 'orient' => $orient,
                       'city' => $city, 'color' => $color, 'verified' => $vonly, 'sort' => $sort, 'q' => $q, 'page' => 1], $over);
    $defaults = ['lang' => 'en', 'col' => '', 'cat' => 'all', 'type' => '', 'status' => 'all', 'medium' => '', 'price' => '', 'size' => '', 'orient' => '', 'city' => '', 'color' => '', 'verified' => '', 'sort' => 'newest', 'q' => '', 'page' => 1];
    foreach ($defaults as $k => $d) { if ((string) $st[$k] === (string) $d) unset($st[$k]); }
    return 'marketplace.php' . ($st ? '?' . http_build_query($st) : '');
}

/* Hidden fields that carry the current choices in a GET form (except the ones the form sets itself) */
function mpHidden(array $skip): string {
    global $lang, $cat, $initType, $status, $sort, $q, $medium, $price, $size, $orient, $city, $color, $vonly, $col;
    $cur = ['lang' => $lang === 'ar' ? 'ar' : '', 'col' => $col, 'cat' => $initType === '' && $cat !== 'all' ? $cat : '', 'type' => $initType, 'status' => $status === 'all' ? '' : $status,
            'medium' => $medium, 'price' => $price, 'size' => $size, 'orient' => $orient, 'city' => $city, 'color' => $color, 'verified' => $vonly, 'sort' => $sort === 'newest' ? '' : $sort, 'q' => $q];
    $h = '';
    foreach ($cur as $k => $v) if ($v !== '' && !in_array($k, $skip, true)) $h .= '<input type="hidden" name="' . $k . '" value="' . htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') . '">';
    return $h;
}

/* ---- One set of conditions for the count and the page: approved, for sale, artist not banned, no drafts ---- */
$where  = "a.is_approved = 1 AND a.status IN ('available','auction') AND u.is_banned = 0" . owNotDraftSql('a');
$params = [];
if ($status !== 'all') { $where .= ' AND a.status = :status'; $params[':status'] = $status; }
$typeList = $initType !== '' ? [$initType] : $ART_CATS[$cat]['types'];
/* a style from Explore also shows the artworks saved under its topic (e.g. Pencil → "Charcoal and pencil") */
if ($initType !== '' && $cat !== 'all' && ($tStore = owArtTopics()[$cat]['store'] ?? '') !== '' && $tStore !== $initType) $typeList[] = $tStore;
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
if ($medium !== '') {
    $mw = [];
    foreach ($MEDIUMS[$medium]['words'] as $i => $w) { $mw[] = 'a.medium LIKE :md' . $i; $params[':md' . $i] = '%' . addcslashes($w, '\\%_') . '%'; }
    $where .= ' AND (' . implode(' OR ', $mw) . ')';
}
if ($price !== '') {
    if ($PRICES[$price]['min'] !== null) { $where .= ' AND a.price >= :pmin'; $params[':pmin'] = $PRICES[$price]['min']; }
    if ($PRICES[$price]['max'] !== null) { $where .= ' AND a.price <= :pmax'; $params[':pmax'] = $PRICES[$price]['max']; }
}
if ($size !== '') {
    $where .= ' AND a.size_category = :scat'; $params[':scat'] = $size;
}
if ($orient !== '') { $where .= ' AND a.orientation = :orient'; $params[':orient'] = $orient; }
if ($city !== '') { $where .= ' AND TRIM(u.city) = :city'; $params[':city'] = $city; }
if ($color !== '' && ($cn = owColorFamilyNames($color))) {
    $ph = [];
    foreach ($cn as $i => $n) { $ph[] = ':cl' . $i; $params[':cl' . $i] = $n; }
    $where .= ' AND EXISTS (SELECT 1 FROM artwork_colors ac WHERE ac.artwork_id = a.id AND ac.color_name_en IN (' . implode(',', $ph) . '))';
}
if ($vonly === '1') $where .= ' AND ' . owVerifiedSql('u') . ' = 1';
if ($colObj) { [$cc, $cp] = owCollectionCondition($colObj); $where .= ' AND ' . $cc; $params += $cp; }
$moreOn = ($medium !== '' ? 1 : 0) + ($price !== '' ? 1 : 0) + ($size !== '' ? 1 : 0) + ($orient !== '' ? 1 : 0) + ($city !== '' ? 1 : 0) + ($color !== '' ? 1 : 0) + ($vonly !== '' ? 1 : 0);

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
            " . owArtistNameSql('u') . ", " . owVerifiedSql('u') . " AS verified
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
$filtered = $cat !== 'all' || $initType !== '' || $status !== 'all' || $q !== '' || $moreOn > 0 || $col !== '';
/* Above the grid, on the first unfiltered page: curated collections and the Artist of the week */
$showcase  = !$filtered && $page === 1;
$cols      = $showcase ? owCollections() : [];
$colCovers = $cols ? owCollectionCovers($cols) : [];
$cols      = array_values(array_filter($cols, fn($c) => ($colCovers[(int) $c['id']]['count'] ?? 0) > 0));
$aotw      = $showcase ? owArtistOfWeek() : null;

/* A shared link (artwork.php, Community) can ask for one artwork's window with ?show=N,
   even when that artwork is not on this page: it is added to the window data only */
$modalArtworks = $artworks;
$showId = (int) $getStr('show');
if ($showId > 0 && !in_array($showId, array_map(fn($r) => (int) $r['id'], $artworks), true)) {
    $one = getDB()->prepare("SELECT a.id, a.title_en, a.title_ar, a.description_en, a.description_ar, a.price, a.type, a.status, a.image_url, a.artist_id,
                                    " . owArtistNameSql('u') . ", " . owVerifiedSql('u') . " AS verified FROM artworks a JOIN users u ON u.id = a.artist_id
                             WHERE a.id = :id AND a.is_approved = 1 AND a.status IN ('available','auction') AND u.is_banned = 0" . owNotDraftSql('a'));
    $one->execute([':id' => $showId]);
    if ($row = $one->fetch()) $modalArtworks[] = $row;
}

/* Creation Journey: stage photos + colors, only for the approved artworks on this page */
$pageIds  = array_map(fn($r) => (int) $r['id'], $modalArtworks);
$mpStages = owGetStagesFor($pageIds);
$mpColors = owGetColorsFor($pageIds);

/* Pictures: cleaned up by owImgSrc() (artwork_lib.php) */
$mpImg = 'owImgSrc';

/* Data for the details modal (escaped on output via JSON flags) */
$modalData = [];
foreach ($modalArtworks as $aw) {
    $modalData[(int) $aw['id']] = [
        'title_en'  => $aw['title_en'],
        'title_ar'  => $aw['title_ar'],
        'desc_en'   => $aw['description_en'] ?? '',
        'desc_ar'   => $aw['description_ar'] ?? '',
        'artist_en' => $aw['artist_en'],
        'artist_ar' => $aw['artist_ar'],
        'artist_id' => (int) $aw['artist_id'],
        'verified'  => (int) ($aw['verified'] ?? 0) === 1,
        'price'     => (float) $aw['price'],
        'image'     => ($mBig = owImgSized($mOrig = $mpImg((string) $aw['image_url']), 1600)),   // details window: a large copy, not the camera original
        'image_fb'  => $mOrig === '' ? [] : array_values(array_unique(array_filter([owImgSized($mOrig, 800), owImgSized($mOrig, 400), owImgSized($mOrig, 800, false), $mOrig], fn($u) => $u !== '' && $u !== $mBig))),
        'status'    => $aw['status'],
        'journey'   => owHasJourney($mpStages[(int) $aw['id']] ?? []),
    ];
}
?>
<!DOCTYPE html>
<html <?php require_once __DIR__ . '/lang_lib.php'; owHtmlAttrs(); ?>>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require_once __DIR__ . '/seo_lib.php'; owSeo(['en' => 'The Gallery', 'ar' => 'المعرض', 'desc_en' => 'Original works by real artists, from Saudi Arabia and all over the world. Every piece has a story.', 'desc_ar' => 'أعمال أصلية لفنانين حقيقيين، من السعودية ومن كل العالم. لكل قطعة حكاية.', 'path' => 'marketplace.php' . ($ar ? '?lang=ar' : '')]); ?>
  <?php include __DIR__ . '/theme_head.php'; ?>
  <link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
  <link rel="stylesheet" href="<?= owAsset('assets/css/proof.css') ?>">
  <style>
    /* Gallery (marketplace.php) layout only — colors, type sizes and buttons come from oweili.css */
    .main-content { position: relative; z-index: 10; width:100%; max-width: 1200px; margin: 0 auto; padding: calc(var(--nav-h) + var(--space-xl)) var(--space-lg) var(--space-2xl); }
    .page-header{margin-bottom:var(--space-md);animation:mpRise .15s ease-out both;}
    .page-header h1{font-size:var(--fs-section);margin:0 0 var(--space-xs);}
    .page-header p{font-size:var(--fs-body);max-width:600px;margin:0;}
    .human-banner{text-align:center;margin:0 0 var(--space-lg);padding:var(--space-sm) var(--space-md);border-radius:var(--radius-card);font-size:var(--fs-body);font-weight:600;color:var(--color-dark);background:var(--color-tint-blue);border:1px solid rgba(var(--rgb-accent),0.15);animation:mpRise .15s ease-out both;}

    /* filters: search, 6 category pills, then a small row with status and sort */
    .mp-tools{display:flex;flex-direction:column;gap:var(--space-sm);margin-bottom:var(--space-md);animation:mpRise .15s ease-out both;}
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
    .ow-backdrop ~ * .mp-label{display:inline-block;width:fit-content;padding:4px 12px;border-radius:var(--radius-card);background:var(--studio-glass);-webkit-backdrop-filter:blur(14px) saturate(1.15);backdrop-filter:blur(14px) saturate(1.15);border:1px solid var(--studio-glass-edge);text-shadow:none;}
    .ow-backdrop ~ * .st-pill:not(.active),.ow-backdrop ~ * .mp-count{background:var(--studio-glass);-webkit-backdrop-filter:blur(14px) saturate(1.15);backdrop-filter:blur(14px) saturate(1.15);border:1px solid var(--studio-glass-edge);color:var(--color-dark);text-shadow:none;}
    .ow-backdrop ~ * .mp-count{width:fit-content;padding:4px 12px;border-radius:var(--radius-card);}
    .st-pill{display:inline-flex;align-items:center;min-height:32px;padding:0 12px;border-radius:var(--radius-pill);font-size:var(--fs-label);font-weight:600;text-decoration:none;color:var(--color-body);border:1px solid var(--color-border);background:transparent;transition:background var(--ease),color var(--ease);}
    .st-pill:hover{background:var(--color-hover-light);color:var(--color-dark);}
    .st-pill.active{color:var(--color-dark);border-color:var(--color-dark);background:var(--color-surface);}
    .mp-sort{display:flex;align-items:center;gap:var(--space-xs);}
    .mp-sort select{min-height:40px;padding:0 36px 0 12px;border-radius:var(--radius-input);border:1px solid var(--color-border-strong);background:var(--color-surface);color:var(--color-dark);font-size:var(--fs-label);font-weight:600;cursor:pointer;}
    [dir="rtl"] .mp-sort select{padding:0 12px 0 36px;}
    .mp-sort select:focus-visible{outline:2px solid var(--color-accent);outline-offset:2px;}
    .mp-topic{display:flex;flex-direction:column;gap:6px;max-width:460px;}
    .mp-topic select{width:100%;min-height:48px;padding:0 36px 0 12px;border-radius:var(--radius-input);border:1px solid var(--color-border-strong);background:var(--color-surface);color:var(--color-dark);font-size:var(--fs-label);font-weight:600;cursor:pointer;}
    [dir="rtl"] .mp-topic select{padding:0 12px 0 36px;}
    .mp-topic select:focus-visible{outline:2px solid var(--color-accent);outline-offset:2px;}
    .type-chip{display:inline-flex;align-items:center;gap:8px;align-self:flex-start;min-height:32px;padding:0 12px;border-radius:var(--radius-pill);font-size:var(--fs-label);font-weight:600;text-decoration:none;color:var(--color-dark);background:var(--color-tint-blue);border:1px solid rgba(var(--rgb-accent),0.2);}
    .type-chip span{font-size:16px;line-height:1;}
    .mp-count{font-size:var(--fs-label);color:var(--color-body);margin:0 0 var(--space-sm);}

    /* artwork cards */
    .art-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:var(--space-md);}
    .mp-card{border-radius:16px;animation:mpRise .15s ease-out both;}
    .mp-card:nth-child(2){animation-delay:.04s}.mp-card:nth-child(3){animation-delay:.08s}.mp-card:nth-child(4){animation-delay:.12s}
    .mp-card:nth-child(n+5){animation-delay:.16s}
    .mp-card:hover{transform:translateY(-6px);}
    .mp-card .aw-img{background:var(--color-surface);}
    .mp-card .aw-img img{width:100%;height:100%;object-fit:cover;background:var(--color-surface);transition:transform .6s cubic-bezier(.22,1,.36,1);}
    .mp-card:hover .aw-img img{transform:scale(1.03);}
    .mp-card .aw-body{gap:6px;padding:var(--space-sm) var(--space-sm) var(--space-sm);}
    .mp-card .aw-title{font-size:15px;line-height:1.4;margin:0;}
    .mp-card .aw-artist{font-size:var(--fs-caption);}
    .mp-meta{display:flex;flex-wrap:wrap;gap:6px;margin-top:2px;}
    .type-pill{display:inline-flex;align-items:center;padding:2px 10px;border-radius:var(--radius-pill);font-size:var(--fs-min);font-weight:700;line-height:1.6;white-space:nowrap;background:var(--color-dark);color:var(--color-on-dark);}
    .mp-card .aw-price{font-size:16px;padding-top:var(--space-xs);}
    .mp-card .btn{margin-top:var(--space-xs);border-radius:8px;}

    /* empty state */
    .gallery-empty{text-align:center;padding:var(--space-2xl) var(--space-lg);animation:mpRise .15s ease-out both;}
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
    .m-interest{margin-top:var(--space-sm);}
    .mi-form{margin-top:var(--space-sm);display:grid;gap:var(--space-xs);}
    .mi-form .field{margin:0;}
    .mi-note{font-size:var(--fs-caption);color:var(--color-body);}
    .m-share{display:inline-block;margin:var(--space-xs) 0;font-size:var(--fs-caption);font-weight:700;color:var(--color-accent);}
    .m-price-row{display:flex;align-items:center;justify-content:space-between;gap:var(--space-sm);margin-top:var(--space-sm);}
    .modal-body .m-price-row .m-price{margin-top:0;}
    .m-interest [hidden]{display:none !important;}
    .mi-trap{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;}
    #mi-ok{margin-top:var(--space-sm);}
    .modal-noimg{display:flex;align-items:center;justify-content:center;color:var(--color-light);background:var(--color-surface-2);}
    .modal-noimg[hidden]{display:none;}
    .modal-noimg svg{width:56px;height:56px;fill:none;stroke:currentColor;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round;}
    .modal-close{float:right;width:40px;height:40px;border-radius:var(--radius-btn);background:none;border:1px solid var(--color-border-strong);font-size:22px;cursor:pointer;color:var(--color-dark);line-height:1;}
    .modal-close:hover{background:var(--color-hover-light);}
    [dir="rtl"] .modal-close{float:left;}

    /* more filters */
    .mp-more > summary{list-style:none;display:inline-flex;align-items:center;gap:8px;cursor:pointer;width:auto;}
    .mp-more > summary::-webkit-details-marker{display:none;}
    .mp-more > summary svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;}
    .mp-n{display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;padding:0 6px;border-radius:var(--radius-pill);background:var(--color-dark);color:var(--color-on-dark);font-size:var(--fs-min);}
    .mp-filters{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:var(--space-sm);align-items:end;margin-top:var(--space-sm);padding:var(--space-md);border-radius:var(--radius-card);background:var(--color-surface);border:1px solid var(--color-border);}
    .mp-filters .field{margin:0;min-width:0;}
    .mp-filters select{width:100%;min-height:44px;}
    .f-colors{grid-column:1 / -1;border:0;padding:0;margin:0;display:flex;flex-wrap:wrap;gap:6px;align-items:center;}
    .f-colors legend{font-size:var(--fs-label);font-weight:700;color:var(--color-dark);margin-bottom:6px;padding:0;width:100%;}
    .sw,.sw-any{display:inline-flex;align-items:center;gap:6px;min-height:36px;padding:0 12px 0 8px;border-radius:var(--radius-pill);border:1px solid var(--color-border-strong);background:var(--color-surface);font-size:var(--fs-label);font-weight:600;color:var(--color-dark);cursor:pointer;}
    [dir="rtl"] .sw{padding:0 8px 0 12px;}
    .sw-any{padding:0 12px;}
    .sw input,.sw-any input{position:absolute;opacity:0;width:1px;height:1px;}
    .sw i{width:18px;height:18px;border-radius:50%;border:1px solid rgba(var(--rgb-ink),0.25);}
    .sw:has(input:checked),.sw-any:has(input:checked){border-color:var(--color-dark);box-shadow:0 0 0 1px var(--color-dark) inset;}
    .sw:has(input:focus-visible),.sw-any:has(input:focus-visible){outline:2px solid var(--color-accent);outline-offset:2px;}
    .f-check{grid-column:1 / span 2;display:flex;align-items:center;gap:8px;font-size:15px;font-weight:600;color:var(--color-dark);}
    .f-acts{grid-column:3 / -1;display:flex;gap:var(--space-xs);justify-content:flex-end;flex-wrap:wrap;}
    /* artist of the week */
    .aotw{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,1fr);gap:0;overflow:hidden;padding:0;margin:0 0 var(--space-lg);animation:mpRise .15s ease-out both;}
    .aotw-art{position:relative;aspect-ratio:4/3;background:var(--color-frame);}
    .aotw-art img{width:100%;height:100%;object-fit:cover;display:block;}
    .aotw-txt{display:flex;flex-direction:column;justify-content:center;align-items:flex-start;gap:var(--space-xs);padding:var(--space-lg);}
    .aotw-kicker,.col-kicker{font-size:var(--fs-min);font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--color-body);}
    [dir="rtl"] .aotw-kicker,[dir="rtl"] .col-kicker{letter-spacing:0;font-size:13px;}
    .aotw-txt h2{font-size:var(--fs-card);margin:0;display:flex;align-items:center;gap:10px;}
    .aotw-face{width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid var(--color-surface);box-shadow:0 2px 8px rgba(var(--rgb-shadow),.15);}
    .aotw-city{margin:0;font-size:var(--fs-caption);color:var(--color-body);}
    .aotw-note{margin:0 0 var(--space-xs);max-width:420px;}
    /* collections */
    .cols{margin:0 0 var(--space-lg);}
    .cols-h{font-size:var(--fs-subtitle);margin:0 0 var(--space-sm);}
    .cols-row{display:grid;grid-auto-flow:column;grid-auto-columns:minmax(220px,1fr);gap:var(--space-sm);overflow-x:auto;padding-bottom:6px;scroll-snap-type:x proximity;}
    .col-card{display:flex;flex-direction:column;gap:4px;padding:var(--space-xs) var(--space-xs) var(--space-sm);text-decoration:none;color:inherit;scroll-snap-align:start;}
    .col-pics{display:grid;gap:3px;aspect-ratio:16/10;border-radius:10px;overflow:hidden;background:var(--color-frame);margin-bottom:6px;}
    .col-pics.n2{grid-template-columns:1fr 1fr;}
    .col-pics.n3{grid-template-columns:2fr 1fr;grid-template-rows:1fr 1fr;}
    .col-pics.n3 .col-pic:first-child{grid-row:1 / span 2;}
    .col-pic{position:relative;overflow:hidden;background:var(--color-surface-2);}
    .col-pic img{width:100%;height:100%;object-fit:cover;display:block;}
    .col-t{font-weight:700;color:var(--color-dark);padding:0 4px;}
    .col-n{font-size:var(--fs-caption);color:var(--color-body);padding:0 4px;}
    .col-back{display:inline-block;margin-bottom:var(--space-xs);font-size:var(--fs-caption);font-weight:700;color:var(--color-accent);}
    .col-kicker{display:block;margin-bottom:4px;}
    @media(max-width:1024px){ .mp-filters{grid-template-columns:repeat(3,minmax(0,1fr));} .f-check{grid-column:1 / -1;} .f-acts{grid-column:1 / -1;justify-content:flex-start;} }
    @media(max-width:640px){ .mp-filters{grid-template-columns:1fr 1fr;padding:var(--space-sm);} .aotw{grid-template-columns:1fr;} .aotw-txt{padding:var(--space-md);} .cols-row{grid-auto-columns:72%;} }

    .mp-promos{display:grid;grid-template-columns:1fr 1fr;gap:var(--space-sm);margin:0 0 var(--space-lg);}
    .mp-promos.one{grid-template-columns:1fr;}
    .mp-promo{display:flex;gap:var(--space-sm);align-items:center;padding:var(--space-md);text-decoration:none;color:var(--color-body);font-size:var(--fs-caption);}
    .mp-promo b{display:block;font-size:var(--fs-body);color:var(--color-dark);margin-bottom:2px;}
    .mp-promo svg{width:40px;height:40px;flex-shrink:0;padding:8px;border-radius:12px;background:var(--color-tint-pink);fill:none;stroke:var(--color-dark);stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round;}
    .m-gift{margin-inline-start:var(--space-sm);}
    @media(max-width:640px){ .mp-promos{grid-template-columns:1fr;} }
    @keyframes mpRise {from{opacity:1}to{opacity:1}}
    @media (prefers-reduced-motion: reduce){ .page-header,.human-banner,.mp-tools,.mp-card,.gallery-empty,.modal{animation:none;} .mp-card:hover,.mp-card:hover .aw-img img{transform:none;} }
    @media(max-width:1024px){ .art-grid{grid-template-columns:repeat(3,1fr);} }
    /* computers: the same centred column as Art Institutes (owner, 6 Nov 2026): the title card, the line under it,
       the search, the topics, the sort, "More filters", the count and the empty card sit one under the other in the middle */
    @media(min-width:769px){
      [dir] .page-header{margin-left:auto;margin-right:auto;text-align:center;}
      [dir] .page-header p{margin-left:auto;margin-right:auto;text-align:center;}
      .mp-tools{max-width:420px;margin-left:auto;margin-right:auto;}
      .mp-search,.mp-topic{max-width:none;}
      .cat-pills,.st-pills,.mp-row{justify-content:center;}
      .mp-more{text-align:center;}
      .mp-more .mp-filters{grid-template-columns:1fr 1fr;text-align:start;}
      .mp-more .mp-filters :is(.f-check,.f-acts){grid-column:1 / -1;}
      .mp-count{text-align:center;margin-left:auto;margin-right:auto;}
      .gallery-empty{max-width:420px !important;}
    }
    @media(max-width:768px){ .main-content{padding:calc(var(--nav-h) + var(--space-md)) var(--space-sm) var(--space-xl);} .art-grid{gap:var(--space-sm);} .mp-search,.mp-topic{max-width:none;} }
    @media(max-width:640px){ .art-grid{grid-template-columns:repeat(2,1fr);} }
    @media(max-width:420px){ .mp-card .aw-body{padding:12px;} .mp-card .btn{padding:0 8px;} .cat-pill{padding:0 14px;} }
  </style>
</head>
<body>
<?= owStudioBackdrop('gallery-visit') /* the woman looking at the framed paintings, always (owner, 6 Nov 2026: wide photos only) */ ?>

<div class="bg-wrap">
  
</div>

<?php include 'nav.php'; ?>
<script>
/* This page switches language with a reload (?lang=), so the shared menu's
   language button reloads too, and the menu labels are set to match. */
function tgl(){ window.location.href = <?= json_encode(($u = mpUrl(['lang' => 'en', 'page' => $page])) . (strpos($u, '?') === false ? '?' : '&') . 'lang=' . ($ar ? 'en' : 'ar'), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>; }   /* the new language is always in the address, so the saved choice never overrides it */
<?php if ($ar): ?>
(function(){
  var t = {'nav-community-label':'المجتمع','nav-marketplace-label':'المعرض',
    'nav-explore-label':'أساليب الرسم','nav-support-label':'الدعم',
    
    'dd-sp1':'تواصل معنا','dd-sp2':'كيف يعمل الموقع','dd-sp3':'شروط الاستخدام',
    'n-login-t':'تسجيل الدخول','n-reg-t':'انضم مجاناً','lb':'English'};
  for (var id in t) { var el = document.getElementById(id); if (el) el.textContent = t[id]; }
})();
<?php endif; ?>
</script>

<main class="main-content">

  <?php if ($colObj): ?>
  <div class="page-header">
    <a class="col-back" href="<?= e(mpUrl(['col' => ''])) ?>"><?= $ar ? '→ كل المعرض' : '← The whole Gallery' ?></a>
    <span class="col-kicker"><?= $ar ? 'مجموعة مختارة' : 'Curated collection' ?></span>
    <h1><?= e($ar ? ($colObj['title_ar'] ?: $colObj['title_en']) : ($colObj['title_en'] ?: $colObj['title_ar'])) ?></h1>
    <?php $ci = $ar ? ($colObj['intro_ar'] ?: $colObj['intro_en']) : ($colObj['intro_en'] ?: $colObj['intro_ar']); if ($ci !== ''): ?><p><?= e($ci) ?></p><?php endif; ?>
  </div>
  <?php else: ?>
  <div class="page-header">
    <h1><?= $ar ? 'المعرض' : 'The Gallery' ?></h1>
    <p><?= $ar
        ? 'هنا تلتقي بأعمال أصلية لفنانين حقيقيين، من السعودية ومن كل العالم. لكل قطعة حكاية، ولا تتكرر أبداً.'
        : 'Meet original works by real artists, from Saudi Arabia and all over the world. Every piece has a story. None of them repeat.' ?></p>
  </div>
  <?php endif; ?>

  <!-- HANDMADE-ONLY BANNER -->
  <div class="human-banner">
    <?= $ar ? 'كل عمل هنا رسمته يد فنان، بصبر وحب' : 'Every piece here was made by an artist’s own hand, with patience and love.' ?>
  </div>

  <?php if ($aotw): $aw0 = $aotw['art']; $aImg = $aw0 ? owImgSrc((string) $aw0['image_url']) : ''; $pImg = owImgSrc((string) ($aotw['profile_picture'] ?? ''));
    $nEn = $ar ? $aotw['note_ar'] : $aotw['note_en']; if ($nEn === '') $nEn = $ar ? $aotw['note_en'] : $aotw['note_ar']; ?>
  <!-- ARTIST OF THE WEEK (picked by the admin in admin_gallery.php) -->
  <section class="aotw card" aria-label="<?= $ar ? 'فنان الأسبوع' : 'Artist of the week' ?>">
    <div class="aotw-art"><?php if ($aImg !== ''): ?><?= owArtImgHtml($aImg, ($aw0['title_en'] ?? '') . ' · ' . ($aw0['title_ar'] ?? ''), true) ?><?php else: ?><?= owImgPlaceholder(false) ?><?php endif; ?></div>
    <div class="aotw-txt">
      <span class="aotw-kicker"><?= $ar ? 'فنان الأسبوع' : 'Artist of the week' ?></span>
      <h2><?php if ($pImg !== ''): ?><img class="aotw-face" src="<?= e(owImgSized($pImg, 400)) ?>" alt="" loading="lazy" onerror="this.remove()"><?php endif; ?><?= e($ar ? $aotw['artist_ar'] : $aotw['artist_en']) ?></h2>
      <?php if ((int) $aotw['verified'] === 1): ?><?= owVerifiedBadge() ?><?php endif; ?>
      <?php if (trim((string) $aotw['city']) !== ''): ?><p class="aotw-city"><?= e((string) $aotw['city']) ?></p><?php endif; ?>
      <?php if ($nEn !== ''): ?><p class="aotw-note"><?= e($nEn) ?></p><?php endif; ?>
      <a class="btn btn-dark" href="artist_dashboard.php?id=<?= (int) $aotw['id'] ?>&amp;lang=<?= $lang ?>"><?= $ar ? 'تعرّف على أعماله' : 'Meet the artist' ?></a>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($cols): ?>
  <!-- CURATED COLLECTIONS (made by the admin) -->
  <section class="cols" aria-label="<?= $ar ? 'مجموعات مختارة' : 'Curated collections' ?>">
    <h2 class="cols-h"><?= $ar ? 'مجموعات اخترناها لك' : 'Collections we picked for you' ?></h2>
    <div class="cols-row">
      <?php foreach ($cols as $c): $cv = $colCovers[(int) $c['id']]; ?>
      <a class="col-card card card-hover" href="<?= e(mpUrl(['col' => $c['slug']])) ?>">
        <span class="col-pics n<?= min(3, count($cv['pics'])) ?>"><?php foreach (array_slice($cv['pics'], 0, 3) as $pc): $pi = owImgSrc((string) $pc['image_url']); ?><span class="col-pic"><?php if ($pi !== ''): ?><img loading="lazy" src="<?= e(owImgSized($pi, 400)) ?>" alt="<?= e($pc['title_en'] . ' · ' . $pc['title_ar']) ?>"<?= owImgFallbackAttrs($pi, owImgSized($pi, 400)) ?>><?php endif; ?></span><?php endforeach; ?></span>
        <span class="col-t"><?= e($ar ? ($c['title_ar'] ?: $c['title_en']) : ($c['title_en'] ?: $c['title_ar'])) ?></span>
        <span class="col-n"><?= $ar ? ($cv['count'] === 1 ? 'عمل واحد' : $cv['count'] . ' أعمال') : ($cv['count'] === 1 ? '1 artwork' : $cv['count'] . ' artworks') ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php $mpGift = owGiftsPageOn(); $mpComm = owCommissionsPageOn();   /* hidden for now (6 Nov 2026): their cards, the window's gift link */ ?>
  <?php if ($showcase && ($mpGift || $mpComm)): ?>
  <!-- GIFTS + PRIVATE COMMISSIONS -->
  <div class="mp-promos<?= $mpGift && $mpComm ? '' : ' one' ?>">
    <?php if ($mpGift): ?><a class="card card-hover mp-promo" href="gifts.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="8" width="18" height="13" rx="2"/><path d="M12 8v13M3 12h18M12 8c-2-4-6-4-6-1s4 1 6 1zM12 8c2-4 6-4 6-1s-4 1-6 1z"/></svg>
      <span><b><?= $ar ? 'أهدِ ما يبقى' : 'Give a gift that lasts' ?></b><?= $ar ? 'زواج، تخرّج، مولود، عيد. نغلّفها ونوصلها.' : 'Weddings, graduations, new babies, Eid. We wrap and deliver.' ?></span></a><?php endif; ?>
    <?php if (is_file(__DIR__ . '/commission.php') && $mpComm): ?>
    <a class="card card-hover mp-promo" href="commission.php"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="9" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/><path d="M17 3l1 1.5L20 5l-1.5 1L18 8l-1-2-2-.5 2-1z"/></svg>
      <span><b><?= $ar ? 'عمل خاص من صورتك' : 'A private commission from your photo' ?></b><?= $ar ? 'بورتريه أو ذكرى، يرسمها فنان. صورك تبقى سرّية.' : 'A portrait or a memory, painted by an artist. Your photos stay confidential.' ?></span></a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="mp-tools">
    <!-- SEARCH + SORT (one small form, so every choice travels together) -->
    <form class="mp-search" method="get" action="marketplace.php" role="search" id="mpForm">
      <?= mpHidden(['q', 'sort']) ?>
      <input type="search" class="input" name="q" maxlength="60" value="<?= e($q) ?>"
             aria-label="<?= $ar ? 'ابحث عن عمل فني' : 'Search artworks by title' ?>"
             placeholder="<?= $ar ? 'تبحث عن شيء؟ اكتب اسم العمل…' : 'Looking for something? Type a title…' ?>">
      <button type="submit" class="btn btn-secondary"><?= $ar ? 'ابحث' : 'Search' ?></button>
    </form>

    <!-- TOPIC: one dropdown list (was 10 buttons), same filter as before -->
    <form class="mp-topic" method="get" action="marketplace.php">
      <?= mpHidden(['cat', 'type']) ?>
      <label class="mp-label" for="mpTopic"><?= $ar ? 'الموضوع' : 'Topic' ?></label>
      <select id="mpTopic" name="cat" onchange="this.form.submit()">
        <?php foreach ($ART_CATS as $ck => $cv): ?>
        <option value="<?= e($ck) ?>"<?= $cat === $ck ? ' selected' : '' ?>><?= e($ar ? $cv['ar'] : $cv['en']) ?></option>
        <?php endforeach; ?>
      </select>
      <noscript><button type="submit" class="btn btn-secondary btn-sm"><?= $ar ? 'عرض' : 'Show' ?></button></noscript>
    </form>

    <?php if ($initType !== ''): $tl = $ART_TYPES[$initType]; ?>
    <a class="type-chip" href="<?= e(mpUrl(['type' => ''])) ?>" aria-label="<?= $ar ? 'إزالة هذا الأسلوب' : 'Remove this style' ?>"><?= e($ar ? $tl['ar'] : $tl['en']) ?> <span aria-hidden="true">×</span></a>
    <?php endif; ?>

    <!-- STATUS + SORT -->
    <div class="mp-row">
      <div class="st-pills">
        <span class="mp-label"><?= $ar ? 'الحالة' : 'Status' ?></span>
        <?php $stOpts = ['all' => ['All', 'الكل'], 'available' => ['Available', 'متاح']]; if (owAuctionsPageOn() || $status === 'auction') $stOpts['auction'] = ['Auction', 'مزاد'];   /* the Auction filter shows only while the Auctions page is switched on */ ?>
        <?php foreach ($stOpts as $sk => $sl): ?>
        <a class="st-pill<?= $status === $sk ? ' active' : '' ?>" href="<?= e(mpUrl(['status' => $sk])) ?>"<?= $status === $sk ? ' aria-current="page"' : '' ?>><?= $ar ? $sl[1] : $sl[0] ?></a>
        <?php endforeach; ?>
      </div>
      <div class="mp-sort">
        <label class="mp-label" for="mpSort"><?= $ar ? 'الترتيب' : 'Sort' ?></label>
        <select id="mpSort" name="sort" form="mpForm" onchange="this.form.submit()">
          <?php foreach (['newest' => ['Newest First', 'الأحدث أولاً'], 'popular' => ['Most viewed', 'الأكثر مشاهدة'], 'price_low' => ['Price: Low to High', 'السعر: من الأقل'], 'price_high' => ['Price: High to Low', 'السعر: من الأعلى']] as $ok => $ol): ?>
          <option value="<?= $ok ?>"<?= $sort === $ok ? ' selected' : '' ?>><?= $ar ? $ol[1] : $ol[0] ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- MORE FILTERS: style, medium, price, size, orientation, city, color, verified artists -->
    <details class="mp-more"<?= $moreOn || $initType !== '' ? ' open' : '' ?>>
      <summary class="btn btn-secondary"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg><?= $ar ? 'تصفية أدق' : 'More filters' ?><?php if ($moreOn): ?> <span class="mp-n"><?= $moreOn ?></span><?php endif; ?></summary>
      <form class="mp-filters" method="get" action="marketplace.php">
        <?= mpHidden(['type', 'medium', 'price', 'size', 'orient', 'city', 'color', 'verified', 'cat']) ?>
        <div class="field"><label for="f-style"><?= $ar ? 'الأسلوب' : 'Style' ?></label>
          <select id="f-style" name="type"><option value=""><?= $ar ? 'كل الأساليب' : 'All styles' ?></option>
            <?php foreach (owStyleGroups() as $gk => $g): $opts = array_filter($ART_TYPES, fn($t) => ($t['cat'] ?? '') === $gk && empty($t['legacy']) && empty($t['topic_only'])); if (!$opts) continue; ?>
            <optgroup label="<?= e($ar ? $g['ar'] : $g['en']) ?>"><?php foreach ($opts as $tk => $tv): ?><option value="<?= e($tk) ?>"<?= $initType === $tk ? ' selected' : '' ?>><?= e($ar ? $tv['ar'] : $tv['en']) ?></option><?php endforeach; ?></optgroup>
            <?php endforeach; ?>
          </select></div>
        <?php if ($hasMed): ?><div class="field"><label for="f-medium"><?= $ar ? 'الخامة' : 'Medium' ?></label>
          <select id="f-medium" name="medium"><option value=""><?= $ar ? 'كل الخامات' : 'All mediums' ?></option>
            <?php foreach ($MEDIUMS as $mk => $mv): ?><option value="<?= e($mk) ?>"<?= $medium === $mk ? ' selected' : '' ?>><?= e($ar ? $mv['ar'] : $mv['en']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
        <div class="field"><label for="f-price"><?= $ar ? 'السعر' : 'Price' ?></label>
          <select id="f-price" name="price"><option value=""><?= $ar ? 'كل الأسعار' : 'Any price' ?></option>
            <?php foreach ($PRICES as $pk => $pv): ?><option value="<?= e($pk) ?>"<?= $price === $pk ? ' selected' : '' ?>><?= e($ar ? $pv['ar'] : $pv['en']) ?></option><?php endforeach; ?></select></div>
        <?php if ($hasDims): ?><div class="field"><label for="f-size"><?= $ar ? 'المقاس' : 'Size' ?></label>
          <select id="f-size" name="size"><option value=""><?= $ar ? 'كل المقاسات' : 'Any size' ?></option>
            <?php foreach ($SIZES as $zk => $zv): ?><option value="<?= e($zk) ?>"<?= $size === $zk ? ' selected' : '' ?>><?= e($ar ? $zv['ar'] : $zv['en']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="f-orient"><?= $ar ? 'الاتجاه' : 'Orientation' ?></label>
          <select id="f-orient" name="orient"><option value=""><?= $ar ? 'كل الاتجاهات' : 'Any orientation' ?></option>
            <?php foreach ($ORIENTS as $ok => $ov): ?><option value="<?= e($ok) ?>"<?= $orient === $ok ? ' selected' : '' ?>><?= e($ar ? $ov['ar'] : $ov['en']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
        <?php if ($CITIES): ?><div class="field"><label for="f-city"><?= $ar ? 'مدينة الفنان' : 'Artist’s city' ?></label>
          <select id="f-city" name="city"><option value=""><?= $ar ? 'كل المدن' : 'All cities' ?></option>
            <?php foreach ($CITIES as $cName): ?><option value="<?= e($cName) ?>"<?= $city === $cName ? ' selected' : '' ?>><?= e($cName) ?></option><?php endforeach; ?></select></div><?php endif; ?>
        <fieldset class="f-colors"><legend><?= $ar ? 'اللون' : 'Color' ?></legend>
          <label class="sw-any"><input type="radio" name="color" value=""<?= $color === '' ? ' checked' : '' ?>><span><?= $ar ? 'أي لون' : 'Any' ?></span></label>
          <?php foreach ($COLORS as $ck2 => $cv2): ?>
          <label class="sw" title="<?= e($ar ? $cv2['ar'] : $cv2['en']) ?>"><input type="radio" name="color" value="<?= e($ck2) ?>"<?= $color === $ck2 ? ' checked' : '' ?>><i style="background:<?= e($cv2['hex']) ?>"></i><span><?= e($ar ? $cv2['ar'] : $cv2['en']) ?></span></label>
          <?php endforeach; ?>
        </fieldset>
        <?php if (owVerifiedReady()): ?><label class="f-check"><input type="checkbox" name="verified" value="1"<?= $vonly ? ' checked' : '' ?>> <span><?= $ar ? 'فنانون موثّقون فقط' : 'Verified artists only' ?></span></label><?php endif; ?>
        <div class="f-acts">
          <button type="submit" class="btn btn-dark"><?= $ar ? 'اعرض النتائج' : 'Show results' ?></button>
          <?php if ($moreOn || $initType !== ''): ?><a class="btn btn-ghost" href="<?= e(mpUrl(['type' => '', 'medium' => '', 'price' => '', 'size' => '', 'orient' => '', 'city' => '', 'color' => '', 'verified' => ''])) ?>"><?= $ar ? 'امسح التصفية' : 'Clear filters' ?></a><?php endif; ?>
        </div>
      </form>
    </details>
  </div>

  <?php if ($total > 0): ?>
  <p class="mp-count"><?= $ar ? ('عدد الأعمال: ' . $total) : ($total === 1 ? '1 artwork' : $total . ' artworks') ?></p>
  <div class="art-grid" id="artGrid">
    <?php $cardNo = 0; foreach ($artworks as $aw):
      $id  = (int) $aw['id'];
      $img = $mpImg((string) $aw['image_url']);
      $tEn = (string) $aw['title_en']; $tAr = (string) ($aw['title_ar'] ?: $aw['title_en']);
      $tlbl = $ART_TYPES[$aw['type']] ?? null; ?>
      <article class="aw-card mp-card">
        <div class="aw-img">
          <?= owArtImgHtml($img, $tEn . ' · ' . $tAr, $cardNo++ < 4) ?>
          <span class="aw-status"><?= $aw['status'] === 'auction' ? ($ar ? 'مزاد' : 'Auction') : ($ar ? 'متاح' : 'Available') ?></span>
          <?php if ($showFav): $fon = isset($favIds[$id]); ?><button type="button" class="fav-btn" data-fav="<?= $id ?>" aria-pressed="<?= $fon ? 'true' : 'false' ?>" aria-label="<?= e($fon ? $favLbl['off'] : $favLbl['on']) ?>"><?= MP_HEART ?></button><?php endif; ?>
        </div>
        <div class="aw-body">
          <h2 class="aw-title"><?= e($ar ? $tAr : ($tEn ?: $tAr)) ?></h2>
          <a class="aw-artist" href="artist_dashboard.php?id=<?= (int) $aw['artist_id'] ?>&amp;lang=<?= $lang ?>"><?= $ar ? 'بواسطة' : 'by' ?> <?= e($ar ? $aw['artist_ar'] : $aw['artist_en']) ?><?php if ((int) ($aw['verified'] ?? 0) === 1): ?> <?= owVerifiedBadge(true) ?><?php endif; ?></a>
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
      <?php if ($filtered): ?><a class="btn btn-secondary" href="<?= e(mpUrl(['cat' => 'all', 'type' => '', 'status' => 'all', 'q' => '', 'medium' => '', 'price' => '', 'size' => '', 'orient' => '', 'city' => '', 'color' => '', 'verified' => '', 'col' => ''])) ?>"><?= $ar ? 'شاهد كل الأعمال' : 'See all artworks' ?></a><?php endif; ?>
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
      <img id="m-img" class="modal-img" alt="" onerror="if(window.modalImgFailed)modalImgFailed(this)">
      <div class="modal-img modal-noimg" id="m-noimg" hidden aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 16l5-5 4 4 3-3 6 6"/><circle cx="16" cy="9" r="1.6"/></svg></div>
      <h2 id="m-title-en"></h2>
      <span class="human-badge" id="m-verified" hidden><?= owBi('✦ A Real Touch', '✦ لمسة حقيقية') ?></span>
      <div class="m-title-ar" id="m-title-ar" dir="rtl"></div>
      <a class="m-artist" id="m-artist" href="#"></a> <span id="m-vf" hidden><?= owVerifiedBadge(true) ?></span>
      <div class="m-label" id="m-label-en-h"><?= $ar?'عن العمل (بالإنجليزية)':'About this work (English)' ?></div>
      <p id="m-desc-en"></p>
      <div class="m-label" id="m-label-ar-h"><?= $ar?'عن العمل (بالعربية)':'About this work (Arabic)' ?></div>
      <p id="m-desc-ar" dir="rtl"></p>
      <div class="m-price-row"><div class="m-price" id="m-price"></div>
        <?php if ($showFav): ?><button type="button" class="fav-btn fav-inline" id="m-fav" data-fav="" aria-pressed="false" aria-label="<?= e($favLbl['on']) ?>"><?= MP_HEART ?></button><?php endif; ?>
      </div>
      <a class="m-share" id="m-share" href="#" onclick="return shareArtwork(this)"><?= $ar ? 'شارك هذا العمل' : 'Share this artwork' ?></a>
      <?php if (owGiftsPageOn()): ?><a class="m-share m-gift" id="m-gift" href="gifts.php"><?= $ar ? 'قدّمه هدية' : 'Give it as a gift' ?></a><?php endif; ?>
      <!-- I'm Interested: name, phone, email → inquire.php -->
      <div class="m-interest">
        <button type="button" class="btn btn-primary btn-block" id="mi-open" onclick="openInterest()"><?= $ar ? 'أنا مهتم' : 'I’m Interested' ?></button>
        <form id="mi-form" class="mi-form" hidden novalidate onsubmit="return sendInterest(event)">
          <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
          <input type="hidden" name="artwork_id" id="mi-aw" value="">
          <input type="hidden" name="lang" value="<?= $ar ? 'ar' : 'en' ?>">
          <div class="mi-trap" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
          <div class="field"><label for="mi-name"><?= $ar ? 'الاسم الكامل' : 'Full Name' ?></label>
            <input type="text" id="mi-name" name="name" required maxlength="120" autocomplete="name" value="<?= e($pre['name']) ?>"></div>
          <div class="field"><label for="mi-phone"><?= $ar ? 'رقم الهاتف' : 'Phone Number' ?></label>
            <input type="tel" id="mi-phone" name="phone" required maxlength="40" autocomplete="tel" inputmode="tel" dir="ltr" value="<?= e($pre['phone']) ?>"></div>
          <div class="field"><label for="mi-email"><?= $ar ? 'البريد الإلكتروني' : 'Email Address' ?></label>
            <input type="email" id="mi-email" name="email" required maxlength="190" autocomplete="email" dir="ltr" value="<?= e($pre['email']) ?>"></div>
          <?php if ($isBuyer): ?><p class="mi-note"><?= $ar ? 'أضفنا بياناتك من حسابك. يمكنك تعديلها هنا.' : 'We filled in your details from your account. You can change them here.' ?></p><?php endif; ?>
          <p class="mi-note"><?= $ar ? 'نستخدم بياناتك فقط للتواصل معك بخصوص هذا العمل.' : 'We use your details only to contact you about this artwork.' ?> <a href="privacy.php<?= $ar ? '?lang=ar' : '' ?>" target="_blank" rel="noopener"><?= $ar ? 'سياسة الخصوصية' : 'Privacy Policy' ?></a></p>
          <div class="err-box" id="mi-err" role="alert" hidden></div>
          <button type="submit" class="btn btn-primary btn-block" id="mi-send"><?= $ar ? 'إرسال' : 'Send' ?></button>
        </form>
        <div class="ok-box" id="mi-ok" role="status" hidden><?= $ar ? 'شكراً! سنتواصل معك قريباً.' : 'Thank you! We will contact you soon.' ?></div>
      </div>
      <?php foreach ($modalArtworks as $aw):
        $jid = (int) $aw['id'];
        $jh  = owJourneyHtml($mpStages[$jid] ?? [], $mpColors[$jid] ?? [], (string) $aw['type'], $ART_TYPES);
        if ($jh === '') continue; ?>
      <div class="m-journey" data-id="<?= $jid ?>" hidden><?= $jh ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php if ($showFav): ?>
<script>
window.OW_FAV = {
  buyer: <?= $isBuyer ? 'true' : 'false' ?>, csrf: <?= json_encode(csrfToken()) ?>,
  ids: <?= json_encode(array_map('strval', array_keys($favIds))) ?>,
  labels: <?= json_encode($favLbl, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?>,
  login: function(id){ location.href = 'login.php?next=' + encodeURIComponent('marketplace.php?show=' + id + (LANG === 'ar' ? '&lang=ar' : '') + '#artwork-' + id); }
};
</script>
<script src="<?= owAsset('assets/js/favorites.js') ?>"></script>
<?php endif; ?>
<script>
const LANG = <?= json_encode($lang) ?>;
const ARTWORKS = <?= json_encode($modalData, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?>;

/* ---- Details modal ---- */
function esc(s){ return String(s==null?'':s); }
function openModal(id){
  const a = ARTWORKS[id];
  if(!a) return;
  const mImg = document.getElementById('m-img');
  mImg.dataset.fb = (a.image_fb || []).join('|');
  mImg.hidden = !a.image;
  document.getElementById('m-noimg').hidden = !!a.image;
  if (a.image) mImg.src = a.image;
  document.getElementById('m-img').alt = a.title_en + ' · ' + a.title_ar;
  document.getElementById('m-title-en').textContent = a.title_en;
  document.getElementById('m-title-ar').textContent = a.title_ar;
  const artist = document.getElementById('m-artist');
  artist.textContent = (LANG==='ar' ? 'بواسطة ' : 'by ') + (LANG==='ar' ? a.artist_ar : a.artist_en);
  artist.href = 'artist_dashboard.php?id=' + a.artist_id + '&lang=' + LANG;
  document.getElementById('m-vf').hidden = !a.verified;
  document.getElementById('m-desc-en').textContent = esc(a.desc_en);
  document.getElementById('m-desc-ar').textContent = esc(a.desc_ar);
  document.getElementById('m-price').textContent =
    Number(a.price).toLocaleString() + ' ' + (LANG==='ar' ? 'ر.س' : 'SAR');
  // Creation Journey + trust badge (only for artworks with stage photos)
  document.getElementById('m-verified').hidden = !a.journey;
  document.querySelectorAll('.m-journey').forEach(function(j){ j.hidden = j.dataset.id !== String(id); });
  resetInterest(id);
  document.getElementById('m-share').dataset.id = id;
  const mg = document.getElementById('m-gift'); if (mg) mg.href = 'gifts.php?artwork=' + id;
  /* one view for the artist's numbers (counted once a day per visitor) */
  try { const vf = new FormData(); vf.append('artwork_id', id); navigator.sendBeacon ? navigator.sendBeacon('view.php', vf) : fetch('view.php', {method:'POST', body:vf, credentials:'same-origin', keepalive:true}); } catch (e) {}
  const mf = document.getElementById('m-fav');
  if (mf && window.owFavSync) { mf.dataset.fav = id; owFavSync(id, owFavHas(id)); }
  document.getElementById('modalBg').classList.add('open');
}
/* ---- I'm Interested ---- */
const MI_ERR = <?= json_encode($ar ? [
  'required' => 'يرجى تعبئة الحقول الثلاثة.', 'name' => 'يرجى كتابة اسمك الكامل.',
  'phone' => 'يرجى كتابة رقم هاتف صحيح.', 'email' => 'يرجى كتابة بريد إلكتروني صحيح.',
  'rate' => 'أرسلت عدة طلبات. حاول مرة أخرى بعد ساعة.', 'other' => 'حدث خطأ. حاول مرة أخرى.',
] : [
  'required' => 'Please fill in all three fields.', 'name' => 'Please enter your full name.',
  'phone' => 'Please enter a valid phone number.', 'email' => 'Please enter a valid email address.',
  'rate' => 'You have sent several requests. Please try again in an hour.', 'other' => 'Something went wrong. Please try again.',
], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?>;
function resetInterest(id){
  const f = document.getElementById('mi-form');
  f.reset(); f.hidden = true;
  document.getElementById('mi-aw').value = id;
  document.getElementById('mi-open').hidden = false;
  document.getElementById('mi-ok').hidden = true;
  document.getElementById('mi-err').hidden = true;
  document.getElementById('mi-send').disabled = false;
}
function openInterest(){
  document.getElementById('mi-open').hidden = true;
  document.getElementById('mi-form').hidden = false;
  document.getElementById('mi-name').focus();
}
function showInterestError(code){
  const box = document.getElementById('mi-err');
  box.textContent = MI_ERR[code] || MI_ERR.other; box.hidden = false;
}
function sendInterest(ev){
  ev.preventDefault();
  const f = ev.target, btn = document.getElementById('mi-send');
  document.getElementById('mi-err').hidden = true;
  const v = n => f.elements[n].value.trim();
  if (!v('name') || !v('phone') || !v('email')) { showInterestError('required'); return false; }
  if (!f.elements.email.checkValidity()) { showInterestError('email'); return false; }
  btn.disabled = true;
  fetch('inquire.php', {method:'POST', body:new FormData(f), credentials:'same-origin'})
    .then(r => r.json().catch(() => ({ok:false, error:'other'})))
    .then(d => {
      if (d && d.ok) { f.hidden = true; document.getElementById('mi-ok').hidden = false; }
      else { showInterestError(d && d.error); btn.disabled = false; }
    })
    .catch(() => { showInterestError('other'); btn.disabled = false; });
  return false;
}
/* The big picture failed: try the next copy, then the original, then show the drawing */
function modalImgFailed(img){
  const l = (img.dataset.fb || '').split('|').filter(Boolean);
  if (l.length) { img.dataset.fb = l.slice(1).join('|'); img.src = l[0]; return; }
  img.hidden = true; document.getElementById('m-noimg').hidden = false;
}
/* Share: the artwork's own page, so WhatsApp and X show its photo and title */
function shareArtwork(el){
  const url = location.origin + '/artwork.php?id=' + el.dataset.id + (LANG === 'ar' ? '&lang=ar' : '');
  const a = ARTWORKS[el.dataset.id] || {};
  if (navigator.share) { navigator.share({title: LANG === 'ar' ? a.title_ar : a.title_en, url: url}).catch(function(){}); return false; }
  const done = function(){ el.textContent = LANG === 'ar' ? 'تم نسخ الرابط' : 'Link copied'; };
  if (navigator.clipboard) navigator.clipboard.writeText(url).then(done, function(){ location.href = url; }); else location.href = url;
  return false;
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
