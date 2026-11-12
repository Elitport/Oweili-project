<?php
// marketplace.php — Oweili · The Gallery (live, database-driven; file name kept as marketplace.php)
require_once 'config.php';
require_once __DIR__ . '/studio_lib.php'; require_once __DIR__ . '/page_cache.php'; owPublicPage();   // public page: Back opens at once, early load on touch (page_cache.php)
require_once __DIR__ . '/artwork_lib.php';
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/gallery_lib.php';   // filters, collections, Artist of the week, Verified artist
require_once __DIR__ . '/cert_lib.php';      // size words and the certificate badge in the details window
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

/* Size and certificate for the details window (one read each, only the artworks in the window data) */
$mpDims = []; $mpCerts = [];
if ($pageIds && owHasColumn('artworks', 'width_cm')) {
    $in  = implode(',', array_fill(0, count($pageIds), '?'));
    $sel = 'id, width_cm, height_cm' . ($hasDims && owHasColumn('artworks', 'size_code') ? ', size_code, orientation' : '');
    $dq  = getDB()->prepare("SELECT $sel FROM artworks WHERE id IN ($in)"); $dq->execute($pageIds);
    foreach ($dq->fetchAll() as $r) $mpDims[(int) $r['id']] = $r;
    if (owCertReady()) {
        $cq = getDB()->prepare("SELECT * FROM certificates WHERE status = 'valid' AND artwork_id IN ($in) ORDER BY id"); $cq->execute($pageIds);
        foreach ($cq->fetchAll() as $r) $mpCerts[(int) $r['artwork_id']] = $r;
    }
}

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
        'size'      => ($szw = owArtworkSizeWords($mpDims[(int) $aw['id']] ?? [])) ? $szw[$ar ? 1 : 0] : '',
        'cert'      => isset($mpCerts[(int) $aw['id']]) ? owCertBadgeHtml($mpCerts[(int) $aw['id']]) : '',
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

    .ow-backdrop ~ * .mp-count{background:var(--studio-glass);-webkit-backdrop-filter:blur(14px) saturate(1.15);backdrop-filter:blur(14px) saturate(1.15);border:1px solid var(--studio-glass-edge);color:var(--color-dark);text-shadow:none;}
    .ow-backdrop ~ * .mp-count{width:fit-content;padding:4px 12px;border-radius:var(--radius-card);}
    .mp-count{font-size:var(--fs-label);color:var(--color-body);margin:0 0 var(--space-sm);}
    /* ---- Search and filters (owner, 12 Nov 2026): one rounded frosted bar like a travel-site search bar ---- */
    .ph-note{margin:8px auto 0 !important;font-weight:600;color:var(--color-dark);}
    .mp-tools{display:flex;flex-direction:column;align-items:center;gap:10px;margin:0 0 12px;}
    .mp-barwrap{width:100%;display:flex;justify-content:center;}
    .mp-bar{display:flex;align-items:center;width:100%;max-width:880px;min-height:64px;padding:6px;gap:0;border-radius:var(--radius-pill);background:var(--studio-glass);-webkit-backdrop-filter:blur(16px) saturate(1.2);backdrop-filter:blur(16px) saturate(1.2);border:1px solid var(--studio-glass-edge);box-shadow:0 8px 28px rgba(var(--rgb-shadow),.14),0 1px 2px rgba(var(--rgb-shadow),.06);transition:box-shadow var(--ease);}
    .mp-bar:focus-within{box-shadow:0 10px 34px rgba(var(--rgb-shadow),.2),0 1px 2px rgba(var(--rgb-shadow),.06);}
    .mb-q{display:flex;align-items:center;flex:1 1 auto;min-width:200px;height:52px;padding:0 12px 0 6px;border-radius:var(--radius-pill);transition:background var(--ease);}
    [dir="rtl"] .mb-q{padding:0 6px 0 12px;}
    .mb-q:hover{background:var(--color-hover-light);}
    .mb-go{flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border:0;border-radius:50%;background:none;color:var(--color-dark);cursor:pointer;}
    .mb-go svg,.mb-filt svg{width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}
    .mb-q input{flex:1;min-width:0;height:44px;border:0;background:transparent;outline:none;font:inherit;font-size:15px;font-weight:600;color:var(--color-dark);}
    .mb-q input::placeholder{color:var(--color-body);font-weight:500;opacity:1;}
    .mb-q input::-webkit-search-cancel-button{cursor:pointer;}
    .mb-seg{position:relative;flex:0 0 auto;height:52px;border-radius:var(--radius-pill);cursor:pointer;transition:background var(--ease);}
    .mb-seg::before{content:"";position:absolute;inset-inline-start:0;top:12px;bottom:12px;width:1px;background:var(--color-border-strong);transition:opacity var(--ease);}
    .mb-seg:hover,.mb-seg:focus-within{background:var(--color-hover-light);}
    .mb-seg:hover::before,.mb-seg:focus-within::before,.mb-seg:hover + .mb-seg::before,.mb-seg:focus-within + .mb-seg::before,.mb-seg:hover + .mb-filt::before{opacity:0;}
    .mb-q:hover + .mb-seg::before{opacity:0;}
    .mb-topic{width:184px;} .mb-status{width:128px;} .mb-sort{width:178px;}
    .mb-l{position:absolute;inset-inline-start:20px;top:8px;font-size:12px;font-weight:700;line-height:1.2;color:var(--color-dark);pointer-events:none;transition:opacity .15s ease,transform .2s ease;}
    .mb-seg select{position:absolute;inset:0;width:100%;height:100%;padding:22px 34px 0 20px;margin:0;border:0;border-radius:var(--radius-pill);background:transparent;color:var(--color-body);font:inherit;font-size:14px;font-weight:600;line-height:1.2;cursor:pointer;-webkit-appearance:none;appearance:none;text-overflow:ellipsis;outline:none;transition:padding .2s ease;}
    [dir="rtl"] .mb-seg select{padding:22px 20px 0 34px;}
    .mb-seg select option{color:var(--color-dark);background:var(--color-menu);}
    .mb-seg::after{content:"";position:absolute;inset-inline-end:16px;top:calc(50% + 4px);width:7px;height:7px;margin-top:-6px;border-right:1.6px solid var(--color-body);border-bottom:1.6px solid var(--color-body);transform:rotate(45deg);pointer-events:none;transition:top .2s ease;}
    .mb-seg:has(select:focus-visible){outline:2px solid var(--color-accent);outline-offset:-2px;}
    .mb-filt{position:relative;flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;width:52px;height:52px;margin-inline-start:6px;border:0;border-radius:50%;background:var(--color-primary);color:var(--color-on-primary);cursor:pointer;transition:background var(--ease),transform .15s ease;}
    .mb-filt:hover{background:var(--color-primary-dark);}
    .mb-filt:active{transform:scale(.96);}
    .mb-filt:focus-visible,.mb-go:focus-visible,.mp-chip:focus-visible,.pn-x:focus-visible,.pn-clear:focus-visible{outline:2px solid var(--color-accent);outline-offset:2px;}
    .mb-filt .mp-n{position:absolute;top:-2px;inset-inline-end:-2px;min-width:20px;height:20px;padding:0 5px;border:2px solid var(--color-menu);}
    .mb-noscript{margin-inline-start:6px;}
    .mp-n{display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;padding:0 6px;border-radius:var(--radius-pill);background:var(--color-dark);color:var(--color-on-dark);font-size:var(--fs-min);font-weight:700;}
    /* stuck under the menu: slim, still frosted (laptops) */
    .mp-barwrap.stuck .mp-bar{position:fixed;z-index:150;top:calc(var(--nav-h) + 8px);left:0;right:0;margin:0 auto;width:calc(100% - 32px);max-width:760px;min-height:0;padding:4px;animation:mbIn .25s cubic-bezier(.22,1,.36,1) both;}
    .mp-barwrap.stuck :is(.mb-q,.mb-seg){height:42px;}
    .mp-barwrap.stuck .mb-q input{height:38px;font-size:14px;}
    .mp-barwrap.stuck .mb-go{width:34px;height:34px;}
    .mp-barwrap.stuck .mb-l{opacity:0;transform:translateY(-4px);}
    .mp-barwrap.stuck .mb-seg select{padding-top:0;}
    .mp-barwrap.stuck .mb-seg::after{top:50%;}
    .mp-barwrap.stuck .mb-seg::before{top:9px;bottom:9px;}
    .mp-barwrap.stuck .mb-filt{width:42px;height:42px;}
    .mp-barwrap.stuck .mb-topic{width:168px;} .mp-barwrap.stuck .mb-status{width:112px;} .mp-barwrap.stuck .mb-sort{width:164px;}
    @keyframes mbIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none}}
    /* the chosen filters, each removable */
    .mp-chips{display:flex;flex-wrap:wrap;justify-content:center;gap:8px;margin:0;padding:0;list-style:none;}
    .mp-chip{display:inline-flex;align-items:center;gap:6px;min-height:32px;padding:0 8px 0 14px;border-radius:var(--radius-pill);background:var(--studio-glass);-webkit-backdrop-filter:blur(12px);backdrop-filter:blur(12px);border:1px solid var(--studio-glass-edge);box-shadow:0 2px 8px rgba(var(--rgb-shadow),.08);font-size:13px;font-weight:600;color:var(--color-dark);text-decoration:none;animation:chIn .2s ease-out both;transition:background var(--ease);}
    [dir="rtl"] .mp-chip{padding:0 14px 0 8px;}
    .mp-chip svg{width:18px;height:18px;padding:3px;border-radius:50%;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;transition:background var(--ease);}
    .mp-chip:hover{color:var(--color-dark);background:var(--color-menu);}
    .mp-chip:hover svg{background:var(--color-hover-light);}
    @keyframes chIn{from{opacity:0;transform:scale(.94)}to{opacity:1;transform:none}}
    /* the panel: slides in from the side on laptops, up from the bottom on phones */
    html.mp-lock,html.mp-lock body{overflow:hidden;}
    .mp-scrim{position:fixed;inset:0;z-index:1500;background:rgba(var(--rgb-shadow),.38);-webkit-backdrop-filter:blur(2px);backdrop-filter:blur(2px);opacity:0;transition:opacity .3s ease;}
    .mp-scrim.open{opacity:1;}
    .mp-panel{position:fixed;z-index:1501;top:0;bottom:0;inset-inline-end:0;width:min(440px,92vw);display:flex;flex-direction:column;background:var(--color-menu);color:var(--color-body);box-shadow:0 0 50px rgba(var(--rgb-shadow),.25);transform:translateX(102%);transition:transform .32s cubic-bezier(.22,1,.36,1);}
    [dir="rtl"] .mp-panel{transform:translateX(-102%);}
    .mp-panel.open,[dir="rtl"] .mp-panel.open{transform:none;}
    .mp-panel,.mp-panel *,.mp-chip{text-shadow:none;}
    .mp-panel[hidden],.mp-scrim[hidden]{display:none;}
    .mp-panel form{display:flex;flex-direction:column;height:100%;min-height:0;}
    .pn-head{position:relative;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:20px 24px 16px;border-bottom:1px solid var(--color-border);}
    .pn-head h2{margin:0;font-size:20px;}
    .pn-t-ph,.pn-grab{display:none;}
    .pn-x{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border:0;border-radius:50%;background:none;color:var(--color-dark);cursor:pointer;transition:background var(--ease);}
    .pn-x:hover{background:var(--color-hover-light);}
    .pn-x svg{width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;}
    .pn-body{flex:1;min-height:0;overflow-y:auto;overscroll-behavior:contain;padding:8px 24px 24px;}
    .pn-basic{display:none;}
    .pn-sec{border:0;margin:0;padding:18px 0 4px;min-width:0;}
    .pn-sec + .pn-sec,.pn-basic + .pn-sec{border-top:1px solid var(--color-border);}
    .pn-sec legend{float:left;width:100%;padding:0;margin:0 0 12px;font-size:15px;font-weight:700;color:var(--color-dark);}
    [dir="rtl"] .pn-sec legend{float:right;}
    .pn-sub{clear:both;margin:14px 0 8px;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--color-body);}
    [dir="rtl"] .pn-sub{letter-spacing:0;font-size:13px;}
    .pn-chips{clear:both;display:flex;flex-wrap:wrap;gap:8px;}
    .ch{position:relative;display:inline-flex;}
    .ch input{position:absolute;opacity:0;width:1px;height:1px;pointer-events:none;}
    .ch span{display:inline-flex;align-items:center;gap:8px;min-height:40px;padding:0 16px;border-radius:var(--radius-pill);border:1px solid var(--color-border-strong);background:var(--color-surface);font-size:14px;font-weight:600;color:var(--color-dark);cursor:pointer;user-select:none;transition:background var(--ease),border-color var(--ease),color var(--ease),transform .12s ease;}
    .ch span:hover{border-color:var(--color-dark);}
    .ch span:active{transform:scale(.97);}
    .ch input:checked + span{background:var(--color-dark);border-color:var(--color-dark);color:var(--color-on-dark);}
    .ch input:focus-visible + span{outline:2px solid var(--color-accent);outline-offset:2px;}
    .ch i{width:16px;height:16px;border-radius:50%;border:1px solid rgba(var(--rgb-ink),.25);}
    .ch input:checked + span i{border-color:var(--color-on-dark);}
    .pn-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 24px calc(14px + env(safe-area-inset-bottom));border-top:1px solid var(--color-border);background:var(--color-menu);}
    .pn-clear{min-height:44px;padding:0 6px;border:0;background:none;font:inherit;font-size:15px;font-weight:700;color:var(--color-dark);text-decoration:underline;text-underline-offset:3px;cursor:pointer;}
    .pn-foot .btn{min-width:170px;}
    @media(prefers-reduced-motion: reduce){ .mp-panel,.mp-scrim,.mb-l,.mb-seg select,.mb-seg::after{transition:none;} .mp-barwrap.stuck .mp-bar,.mp-chip{animation:none;} }
    @media(max-width:768px){
      .mp-tools{align-items:stretch;}
      .mp-bar{min-height:0;padding:0;gap:8px;max-width:none;background:none;border:0;box-shadow:none;-webkit-backdrop-filter:none;backdrop-filter:none;border-radius:0;}
      .mp-bar:focus-within{box-shadow:none;}
      .mb-seg{display:none;}
      .mb-q{min-width:0;height:52px;background:var(--studio-glass);-webkit-backdrop-filter:blur(16px) saturate(1.2);backdrop-filter:blur(16px) saturate(1.2);border:1px solid var(--studio-glass-edge);box-shadow:0 6px 20px rgba(var(--rgb-shadow),.12);}
      .mb-q:hover{background:var(--studio-glass);}
      .mb-filt{margin:0;box-shadow:0 6px 20px rgba(var(--rgb-shadow),.14);}
      .mp-chips{justify-content:flex-start;}
      .mp-panel,[dir="rtl"] .mp-panel{top:auto;left:0;right:0;width:auto;max-height:88vh;max-height:88dvh;border-radius:20px 20px 0 0;transform:translateY(102%);}
      .mp-panel.open,[dir="rtl"] .mp-panel.open{transform:none;}
      .pn-grab{display:block;position:absolute;top:8px;left:50%;width:40px;height:4px;margin-left:-20px;border-radius:4px;background:var(--color-border-strong);}
      .pn-head{padding:22px 16px 12px;}
      .pn-t-lap{display:none;} .pn-t-ph{display:inline;}
      .pn-body{padding:4px 16px 20px;}
      .pn-basic{display:block;}
      .pn-basic .pn-sec:first-child{padding-top:14px;}
      .pn-foot{padding:12px 16px calc(12px + env(safe-area-inset-bottom));}
      .pn-foot .btn{flex:1;min-width:0;}
    }
    @media(min-width:769px){ .pn-basic + .pn-sec{border-top:0;} }

    /* artwork cards */
    .art-grid{--cols:4;--g:var(--space-md);display:grid;grid-template-columns:repeat(var(--cols),1fr);gap:var(--g);}
    /* fewer artworks than a full row: the same card width, centred (owner, 11 Nov 2026) */
    .art-grid[data-few]{display:flex;flex-wrap:wrap;justify-content:center;}
    .art-grid[data-few] > *{flex:0 0 calc((100% - (var(--cols) - 1) * var(--g)) / var(--cols));min-width:0;}
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
    .modal-body .m-artist{font-size:var(--fs-caption);font-weight:600;margin-bottom:var(--space-sm);display:inline-block;}
    .modal-body .m-label{font-size:var(--fs-label);font-weight:700;color:var(--color-dark);margin:var(--space-sm) 0 4px;}
    .modal-body p{margin:0;}
    .modal-body .m-price{font-size:var(--fs-card);font-weight:700;color:var(--color-dark);margin-top:var(--space-sm);}
    .m-interest{margin-top:var(--space-sm);}
    .mi-form{margin-top:var(--space-sm);display:grid;gap:var(--space-xs);}
    .mi-form .field{margin:0;}
    .mi-note{font-size:var(--fs-caption);color:var(--color-body);}
    .m-share{display:inline-block;margin:var(--space-xs) 0;font-size:var(--fs-caption);font-weight:700;color:var(--color-accent);}
    .modal-body .m-next{font-size:var(--fs-caption);color:var(--color-body);margin:4px 0 0;}
    .m-size{font-size:var(--fs-caption);color:var(--color-body);margin-top:var(--space-xs);}
    .m-size b{color:var(--color-dark);}
    .m-cert{margin-top:var(--space-xs);}
    .m-cert .cert-badge{font-size:12px;padding:4px 10px;gap:4px;}
    .m-cert .cert-badge svg{width:14px;height:14px;}
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
    @media(max-width:640px){ .aotw{grid-template-columns:1fr;} .aotw-txt{padding:var(--space-md);} .cols-row{grid-auto-columns:72%;} }

    .mp-promos{display:grid;grid-template-columns:1fr 1fr;gap:var(--space-sm);margin:0 0 var(--space-lg);}
    .mp-promos.one{grid-template-columns:1fr;}
    .mp-promo{display:flex;gap:var(--space-sm);align-items:center;padding:var(--space-md);text-decoration:none;color:var(--color-body);font-size:var(--fs-caption);}
    .mp-promo b{display:block;font-size:var(--fs-body);color:var(--color-dark);margin-bottom:2px;}
    .mp-promo svg{width:40px;height:40px;flex-shrink:0;padding:8px;border-radius:12px;background:var(--color-tint-pink);fill:none;stroke:var(--color-dark);stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round;}
    .m-gift{margin-inline-start:var(--space-sm);}
    @media(max-width:640px){ .mp-promos{grid-template-columns:1fr;} }
    @keyframes mpRise {from{opacity:1}to{opacity:1}}
    @media (prefers-reduced-motion: reduce){ .page-header,.human-banner,.mp-tools,.mp-card,.gallery-empty,.modal{animation:none;} .mp-card:hover,.mp-card:hover .aw-img img{transform:none;} }
    @media(max-width:1024px){ .art-grid{--cols:3;} }
    /* computers: the same centred column as Art Institutes (owner, 6 Nov 2026): the title card, the line under it,
       the search, the topics, the sort, "More filters", the count and the empty card sit one under the other in the middle */
    @media(min-width:769px){
      [dir] .page-header{margin-left:auto;margin-right:auto;text-align:center;}
      [dir] .page-header p{margin-left:auto;margin-right:auto;text-align:center;}
      .mp-count{text-align:center;margin-left:auto;margin-right:auto;}
      .gallery-empty{max-width:420px !important;}
    }
    @media(max-width:768px){ .main-content{padding:calc(var(--nav-h) + var(--space-md)) var(--space-sm) var(--space-xl);} .art-grid{--g:var(--space-sm);} }
    @media(max-width:640px){ .art-grid{--cols:2;} }
    @media(max-width:420px){ .mp-card .aw-body{padding:12px;} .mp-card .btn{padding:0 8px;} .cat-pill{padding:0 14px;} }
    /* A shorter top part, so artworks show on the first screen (owner, 11 Nov 2026): a smaller title card;
       laptops put Topic, Status, Sort and "More filters" side by side in one row under the search;
       phones put Topic and Sort side by side */
    .ow-backdrop ~ .main-content .page-header{padding:12px 22px;margin-bottom:12px;}
    .ow-backdrop ~ .main-content .page-header h1{font-size:28px;margin-bottom:4px;}
    .ow-backdrop ~ .main-content .page-header p{font-size:14px;}
    @media(min-width:769px){
      .mp-count{margin-bottom:12px;}
    }
    @media(max-width:768px){
      .ow-backdrop ~ .main-content .page-header{padding:10px 16px;margin-bottom:10px;}
      .ow-backdrop ~ .main-content .page-header h1{font-size:24px;}
    }
  </style>
</head>
<body>
<?= owStudioBackdrop('gallery-hall') /* the owner's gallery hall with the bench, always (owner, 8 Nov 2026) */ ?>

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
    <p class="ph-note"><?= $ar ? 'كل عمل هنا رسمته يد فنان، بصبر وحب' : 'Every piece here was made by an artist’s own hand, with patience and love.' ?></p>
  </div>
  <?php else: ?>
  <div class="page-header">
    <h1><?= $ar ? 'المعرض' : 'The Gallery' ?></h1>
    <p><?= $ar
        ? 'هنا تلتقي بأعمال أصلية لفنانين حقيقيين، من السعودية ومن كل العالم. لكل قطعة حكاية، ولا تتكرر أبداً.'
        : 'Meet original works by real artists, from Saudi Arabia and all over the world. Every piece has a story. None of them repeat.' ?></p>
    <!-- the handmade line, inside the title card (owner, 12 Nov 2026; was a pill under it) -->
    <p class="ph-note"><?= $ar ? 'كل عمل هنا رسمته يد فنان، بصبر وحب' : 'Every piece here was made by an artist’s own hand, with patience and love.' ?></p>
  </div>
  <?php endif; ?>

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

  <?php
  /* The search and filter area (owner, 12 Nov 2026): laptops get one rounded frosted bar
     [search | Topic | Status | Sort | filters button] that sticks under the menu when the page scrolls;
     the filters button opens "More filters" as a side panel. Phones get [search] [filters button],
     and the button opens a bottom sheet with every filter. Chosen filters show as removable chips.
     Same filters, same web addresses as before. */
  $mpShownTopics = owArtTopicsShown();
  $topicOpts = [];
  foreach ($ART_CATS as $ck => $cv) { if ($ck !== 'all' && $ck !== $cat && !isset($mpShownTopics[$ck])) continue; /* hidden topics (owner, 8 Nov 2026) */ $topicOpts[$ck] = $ar ? $cv['ar'] : $cv['en']; }
  $stOpts = ['all' => ['All', 'الكل'], 'available' => ['Available', 'متاح']]; if (owAuctionsPageOn() || $status === 'auction') $stOpts['auction'] = ['Auction', 'مزاد'];   /* the Auction filter shows only while the Auctions page is switched on */
  $sortOpts = ['newest' => ['Newest First', 'الأحدث أولاً'], 'popular' => ['Most viewed', 'الأكثر مشاهدة'], 'price_low' => ['Price: Low to High', 'السعر: من الأقل'], 'price_high' => ['Price: High to Low', 'السعر: من الأعلى']];
  /* the chosen filters, each with a link that removes only that one */
  $mpChips = [];
  if ($q !== '') $mpChips[] = ['“' . $q . '”', mpUrl(['q' => ''])];
  if ($initType !== '') $mpChips[] = [$ar ? $ART_TYPES[$initType]['ar'] : $ART_TYPES[$initType]['en'], mpUrl(['type' => ''])];
  elseif ($cat !== 'all') $mpChips[] = [$ar ? $ART_CATS[$cat]['ar'] : $ART_CATS[$cat]['en'], mpUrl(['cat' => 'all'])];
  if ($status !== 'all' && isset($stOpts[$status])) $mpChips[] = [$stOpts[$status][$ar ? 1 : 0], mpUrl(['status' => 'all'])];
  if ($medium !== '') $mpChips[] = [$ar ? $MEDIUMS[$medium]['ar'] : $MEDIUMS[$medium]['en'], mpUrl(['medium' => ''])];
  if ($price !== '')  $mpChips[] = [$ar ? $PRICES[$price]['ar'] : $PRICES[$price]['en'], mpUrl(['price' => ''])];
  if ($size !== '')   $mpChips[] = [$ar ? $SIZES[$size]['ar'] : $SIZES[$size]['en'], mpUrl(['size' => ''])];
  if ($orient !== '') $mpChips[] = [$ar ? $ORIENTS[$orient]['ar'] : $ORIENTS[$orient]['en'], mpUrl(['orient' => ''])];
  if ($city !== '')   $mpChips[] = [$city, mpUrl(['city' => ''])];
  if ($color !== '')  $mpChips[] = [$ar ? $COLORS[$color]['ar'] : $COLORS[$color]['en'], mpUrl(['color' => ''])];
  if ($vonly !== '')  $mpChips[] = [$ar ? 'فنانون موثّقون فقط' : 'Verified artists only', mpUrl(['verified' => ''])];
  $filtN = $moreOn + ($initType !== '' ? 1 : 0);
  /* one group of tappable chips (radio buttons) in the panel */
  $mpGroup = function (string $title, string $name, array $opts, string $cur, string $defLabel) {
      $h = '<fieldset class="pn-sec"><legend>' . e($title) . '</legend><div class="pn-chips">';
      if ($defLabel !== '') $h .= '<label class="ch"><input type="radio" name="' . $name . '" value="" data-def' . ($cur === '' ? ' checked' : '') . '><span>' . e($defLabel) . '</span></label>';
      foreach ($opts as $v => $l) $h .= '<label class="ch"><input type="radio" name="' . $name . '" value="' . e((string) $v) . '"' . ($defLabel === '' && $v === array_key_first($opts) ? ' data-def' : '') . ((string) $cur === (string) $v ? ' checked' : '') . '><span>' . e($l) . '</span></label>';
      return $h . '</div></fieldset>';
  };
  ?>
  <div class="mp-tools">
    <div class="mp-barwrap" id="mpBarWrap">
      <form class="mp-bar" method="get" action="marketplace.php" role="search" id="mpForm">
        <?= mpHidden(['q', 'cat', 'status', 'sort']) ?>
        <div class="mb-q">
          <button type="submit" class="mb-go" aria-label="<?= $ar ? 'ابحث' : 'Search' ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/></svg></button>
          <input type="search" name="q" maxlength="60" value="<?= e($q) ?>" enterkeyhint="search"
                 aria-label="<?= $ar ? 'ابحث عن عمل فني' : 'Search artworks by title' ?>"
                 placeholder="<?= $ar ? 'ابحث عن عمل فني' : 'Search artworks' ?>">
        </div>
        <label class="mb-seg mb-topic"><span class="mb-l"><?= $ar ? 'الموضوع' : 'Topic' ?></span>
          <select id="mpTopic" name="cat"><?php foreach ($topicOpts as $ck => $cl): ?><option value="<?= e($ck) ?>"<?= $cat === $ck ? ' selected' : '' ?>><?= e($cl) ?></option><?php endforeach; ?></select></label>
        <label class="mb-seg mb-status"><span class="mb-l"><?= $ar ? 'الحالة' : 'Status' ?></span>
          <select id="mpStatus" name="status"><?php foreach ($stOpts as $sk => $sl): ?><option value="<?= $sk ?>"<?= $status === $sk ? ' selected' : '' ?>><?= $sl[$ar ? 1 : 0] ?></option><?php endforeach; ?></select></label>
        <label class="mb-seg mb-sort"><span class="mb-l"><?= $ar ? 'الترتيب' : 'Sort' ?></span>
          <select id="mpSort" name="sort"><?php foreach ($sortOpts as $ok => $ol): ?><option value="<?= $ok ?>"<?= $sort === $ok ? ' selected' : '' ?>><?= $ol[$ar ? 1 : 0] ?></option><?php endforeach; ?></select></label>
        <noscript><button type="submit" class="mb-noscript btn btn-secondary btn-sm"><?= $ar ? 'عرض' : 'Show' ?></button></noscript>
        <button type="button" class="mb-filt" id="mpFiltBtn" aria-controls="mpPanel" aria-expanded="false" aria-label="<?= $ar ? 'تصفية أدق' : 'More filters' ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h10M18 7h2M4 17h4M12 17h8"/><circle cx="16" cy="7" r="2"/><circle cx="10" cy="17" r="2"/></svg><?php if ($filtN): ?><span class="mp-n"><?= $filtN ?></span><?php endif; ?></button>
      </form>
    </div>
    <?php if ($mpChips): ?>
    <ul class="mp-chips" aria-label="<?= $ar ? 'التصفية المختارة' : 'Chosen filters' ?>">
      <?php foreach ($mpChips as [$cl, $cu]): ?><li><a class="mp-chip" href="<?= e($cu) ?>" aria-label="<?= e(($ar ? 'إزالة: ' : 'Remove: ') . $cl) ?>"><span><?= e($cl) ?></span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 7l10 10M17 7L7 17"/></svg></a></li><?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>

  <!-- MORE FILTERS: a side panel on laptops, a bottom sheet with every filter on phones -->
  <div class="mp-scrim" id="mpScrim" hidden></div>
  <div class="mp-panel" id="mpPanel" role="dialog" aria-modal="true" aria-labelledby="mpPanelT" hidden>
    <form id="mpPanelForm" method="get" action="marketplace.php">
      <div class="pn-head">
        <span class="pn-grab" aria-hidden="true"></span>
        <h2 id="mpPanelT"><span class="pn-t-lap"><?= $ar ? 'تصفية أدق' : 'More filters' ?></span><span class="pn-t-ph"><?= $ar ? 'التصفية' : 'Filters' ?></span></h2>
        <button type="button" class="pn-x" id="mpPanelX" aria-label="<?= $ar ? 'إغلاق' : 'Close' ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
      </div>
      <div class="pn-body">
        <?= mpHidden(['cat', 'status', 'sort', 'type', 'medium', 'price', 'size', 'orient', 'city', 'color', 'verified']) ?>
        <div class="pn-basic">
          <?= $mpGroup($ar ? 'الموضوع' : 'Topic', 'cat', $topicOpts, $cat, '') ?>
          <?= $mpGroup($ar ? 'الحالة' : 'Status', 'status', array_map(fn($s) => $s[$ar ? 1 : 0], $stOpts), $status, '') ?>
          <?= $mpGroup($ar ? 'الترتيب' : 'Sort', 'sort', array_map(fn($s) => $s[$ar ? 1 : 0], $sortOpts), $sort, '') ?>
        </div>
        <fieldset class="pn-sec"><legend><?= $ar ? 'الأسلوب' : 'Style' ?></legend>
          <div class="pn-chips"><label class="ch"><input type="radio" name="type" value="" data-def<?= $initType === '' ? ' checked' : '' ?>><span><?= $ar ? 'كل الأساليب' : 'All styles' ?></span></label></div>
          <?php /* while the 15 other styles are hidden (owner, 8 Nov 2026), Al-Qatt Al-Asiri sits under Traditional and the Heritage group is gone */
          $mpMoreOn = owMoreStylesOn(); $mpGrp = fn($t) => (!$mpMoreOn && ($t['cat'] ?? '') === 'heritage') ? 'traditional' : ($t['cat'] ?? '');
          foreach (owStyleGroups() as $gk => $g): if (!$mpMoreOn && $gk === 'heritage') continue; $opts = array_filter($ART_TYPES, fn($t) => $mpGrp($t) === $gk && empty($t['legacy']) && empty($t['topic_only'])); if (!$opts) continue; ?>
          <p class="pn-sub"><?= e($ar ? $g['ar'] : $g['en']) ?></p>
          <div class="pn-chips"><?php foreach ($opts as $tk => $tv): ?><label class="ch"><input type="radio" name="type" value="<?= e($tk) ?>"<?= $initType === $tk ? ' checked' : '' ?>><span><?= e($ar ? $tv['ar'] : $tv['en']) ?></span></label><?php endforeach; ?></div>
          <?php endforeach; ?>
        </fieldset>
        <?php if ($hasMed): ?><?= $mpGroup($ar ? 'الخامة' : 'Medium', 'medium', array_map(fn($m) => $ar ? $m['ar'] : $m['en'], $MEDIUMS), $medium, $ar ? 'كل الخامات' : 'All mediums') ?><?php endif; ?>
        <?= $mpGroup($ar ? 'السعر' : 'Price', 'price', array_map(fn($m) => $ar ? $m['ar'] : $m['en'], $PRICES), $price, $ar ? 'كل الأسعار' : 'Any price') ?>
        <?php if ($hasDims): ?>
        <?= $mpGroup($ar ? 'المقاس' : 'Size', 'size', array_map(fn($m) => $ar ? $m['ar'] : $m['en'], $SIZES), $size, $ar ? 'كل المقاسات' : 'Any size') ?>
        <?= $mpGroup($ar ? 'الاتجاه' : 'Orientation', 'orient', array_map(fn($m) => $ar ? $m['ar'] : $m['en'], $ORIENTS), $orient, $ar ? 'كل الاتجاهات' : 'Any orientation') ?>
        <?php endif; ?>
        <?php if ($CITIES): ?><?= $mpGroup($ar ? 'مدينة الفنان' : 'Artist’s city', 'city', array_combine($CITIES, $CITIES), $city, $ar ? 'كل المدن' : 'All cities') ?><?php endif; ?>
        <fieldset class="pn-sec"><legend><?= $ar ? 'اللون' : 'Color' ?></legend>
          <div class="pn-chips">
            <label class="ch"><input type="radio" name="color" value="" data-def<?= $color === '' ? ' checked' : '' ?>><span><?= $ar ? 'أي لون' : 'Any' ?></span></label>
            <?php foreach ($COLORS as $ck2 => $cv2): ?>
            <label class="ch"><input type="radio" name="color" value="<?= e($ck2) ?>"<?= $color === $ck2 ? ' checked' : '' ?>><span><i style="background:<?= e($cv2['hex']) ?>"></i><?= e($ar ? $cv2['ar'] : $cv2['en']) ?></span></label>
            <?php endforeach; ?>
          </div>
        </fieldset>
        <?php if (owVerifiedReady()): ?><fieldset class="pn-sec"><legend><?= $ar ? 'الفنانون' : 'Artists' ?></legend><div class="pn-chips"><label class="ch"><input type="checkbox" name="verified" value="1"<?= $vonly ? ' checked' : '' ?>><span><?= $ar ? 'فنانون موثّقون فقط' : 'Verified artists only' ?></span></label></div></fieldset><?php endif; ?>
      </div>
      <div class="pn-foot">
        <button type="button" class="pn-clear" id="mpClear"><?= $ar ? 'امسح الكل' : 'Clear all' ?></button>
        <button type="submit" class="btn btn-dark"><?= $ar ? 'اعرض النتائج' : 'Show results' ?></button>
      </div>
    </form>
  </div>
  <script>
  (function(){
    var f = document.getElementById('mpForm'), pf = document.getElementById('mpPanelForm');
    var panel = document.getElementById('mpPanel'), scrim = document.getElementById('mpScrim'), btn = document.getElementById('mpFiltBtn');
    if (!f || !pf || !panel) return;
    document.body.appendChild(scrim); document.body.appendChild(panel);   /* above the menu, outside the page's own layer */
    /* the same web addresses as before: empty and default choices are left out */
    var DEF = {q: '', cat: 'all', status: 'all', sort: 'newest'};
    function go(form){
      var p = new URLSearchParams();
      new FormData(form).forEach(function(v, k){ v = String(v); if (v === '' || DEF[k] === v) return; p.append(k, v); });
      var s = p.toString(); location.href = 'marketplace.php' + (s ? '?' + s : '');
    }
    f.addEventListener('submit', function(e){ e.preventDefault(); go(f); });
    pf.addEventListener('submit', function(e){ e.preventDefault(); go(pf); });
    f.addEventListener('change', function(e){
      var t = e.target; if (t.tagName !== 'SELECT') return;
      if (t.name === 'cat') { var h = f.querySelector('input[type="hidden"][name="type"]'); if (h) h.remove(); }   /* a new topic replaces a style, as before */
      go(f);
    });
    pf.addEventListener('change', function(e){
      if (e.target.name === 'cat') { var a = pf.querySelector('input[name="type"][data-def]'); if (a) a.checked = true; }
    });
    document.getElementById('mpClear').addEventListener('click', function(){
      pf.querySelectorAll('.pn-sec').forEach(function(s){
        if (!s.getClientRects().length) return;   /* only what the visitor can see (laptops: not Topic, Status, Sort) */
        s.querySelectorAll('input').forEach(function(i){ i.checked = i.type === 'radio' && i.hasAttribute('data-def'); });
      });
    });
    /* open and close the panel / sheet */
    var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches, closing;
    function open(){
      clearTimeout(closing); panel.hidden = false; scrim.hidden = false; document.documentElement.classList.add('mp-lock');
      requestAnimationFrame(function(){ requestAnimationFrame(function(){ panel.classList.add('open'); scrim.classList.add('open'); }); });
      btn.setAttribute('aria-expanded', 'true');
      setTimeout(function(){ var x = document.getElementById('mpPanelX'); if (x) x.focus({preventScroll: true}); }, reduce ? 0 : 60);
    }
    function close(){
      if (panel.hidden) return;
      panel.classList.remove('open'); scrim.classList.remove('open'); document.documentElement.classList.remove('mp-lock');
      btn.setAttribute('aria-expanded', 'false');
      closing = setTimeout(function(){ panel.hidden = true; scrim.hidden = true; }, reduce ? 0 : 320);
      btn.focus({preventScroll: true});
    }
    btn.addEventListener('click', open);
    scrim.addEventListener('click', close);
    document.getElementById('mpPanelX').addEventListener('click', close);
    document.addEventListener('keydown', function(e){
      if (panel.hidden) return;
      if (e.key === 'Escape') { close(); return; }
      if (e.key === 'Tab') {   /* keep the keyboard inside the open panel */
        var els = [].filter.call(panel.querySelectorAll('button, input, a[href]'), function(el){ return el.getClientRects().length && !el.disabled; });
        if (!els.length) return; var a = els[0], z = els[els.length - 1];
        if (e.shiftKey && document.activeElement === a) { e.preventDefault(); z.focus(); }
        else if (!e.shiftKey && document.activeElement === z) { e.preventDefault(); a.focus(); }
      }
    });
    /* laptops: the bar sticks under the menu, slim and frosted, once the page scrolls past it */
    var wrap = document.getElementById('mpBarWrap'), lap = window.matchMedia ? matchMedia('(min-width: 769px)') : null, tick;
    function stick(){
      tick = 0;
      if (!lap || !lap.matches) { wrap.classList.remove('stuck'); wrap.style.minHeight = ''; return; }
      var nav = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--nav-h')) || 64;
      if (!wrap.classList.contains('stuck')) wrap.style.minHeight = wrap.offsetHeight + 'px';   /* keeps its place, so nothing jumps */
      wrap.classList.toggle('stuck', wrap.getBoundingClientRect().top < nav + 8);
    }
    addEventListener('scroll', function(){ if (!tick) tick = requestAnimationFrame(stick); }, {passive: true});
    addEventListener('resize', function(){ wrap.classList.remove('stuck'); wrap.style.minHeight = ''; stick(); });
    stick();
  })();
  </script>

  <?php if ($total > 0): ?>
  <p class="mp-count"><?= $ar ? ('عدد الأعمال: ' . $total) : ($total === 1 ? '1 artwork' : $total . ' artworks') ?></p>
  <div class="art-grid" id="artGrid"<?= count($artworks) < 4 ? ' data-few' : '' ?>>
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
      <h2 id="m-title" dir="auto"></h2>
      <span class="human-badge" id="m-verified" hidden><?= owBi('✦ A Real Touch', '✦ لمسة حقيقية') ?></span>
      <a class="m-artist" id="m-artist" href="#"></a> <span id="m-vf" hidden><?= owVerifiedBadge(true) ?></span>
      <div class="m-label" id="m-desc-h"><?= $ar ? 'عن العمل' : 'About this work' ?></div>
      <p id="m-desc" dir="auto"></p>
      <div class="m-price-row"><div class="m-price" id="m-price"></div>
        <?php if ($showFav): ?><button type="button" class="fav-btn fav-inline" id="m-fav" data-fav="" aria-pressed="false" aria-label="<?= e($favLbl['on']) ?>"><?= MP_HEART ?></button><?php endif; ?>
      </div>
      <p class="m-next"><?= e(OW_BUY_NEXT[$ar ? 1 : 0]) ?></p>
      <div class="m-size" id="m-size" hidden><b><?= $ar ? 'المقاس' : 'Size' ?></b> <span id="m-size-v"></span></div>
      <div class="m-cert" id="m-cert" hidden></div>
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
  /* one language per page: the page language, or the other one when the artist left it empty */
  document.getElementById('m-title').textContent = LANG==='ar' ? (a.title_ar || a.title_en) : (a.title_en || a.title_ar);
  const artist = document.getElementById('m-artist');
  artist.textContent = (LANG==='ar' ? 'بواسطة ' : 'by ') + (LANG==='ar' ? a.artist_ar : a.artist_en);
  artist.href = 'artist_dashboard.php?id=' + a.artist_id + '&lang=' + LANG;
  document.getElementById('m-vf').hidden = !a.verified;
  const desc = esc(LANG==='ar' ? (a.desc_ar || a.desc_en) : (a.desc_en || a.desc_ar)).trim();
  document.getElementById('m-desc').textContent = desc;
  document.getElementById('m-desc').hidden = document.getElementById('m-desc-h').hidden = desc === '';
  document.getElementById('m-size-v').textContent = a.size || '';
  document.getElementById('m-size').hidden = !a.size;
  document.getElementById('m-cert').innerHTML = a.cert || '';   /* made and escaped on the server (owCertBadgeHtml) */
  document.getElementById('m-cert').hidden = !a.cert;
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
  miMark([]);
}
function openInterest(){
  document.getElementById('mi-open').hidden = true;
  document.getElementById('mi-form').hidden = false;
  document.getElementById('mi-name').focus();
}
function showInterestError(code, boxes){
  const box = document.getElementById('mi-err');
  box.textContent = MI_ERR[code] || MI_ERR.other; box.hidden = false;
  /* mark the exact boxes that are wrong, and go to the first one */
  if (!boxes) boxes = ['name', 'phone', 'email'].indexOf(code) >= 0 ? [code] : [];
  miMark(boxes);
}
function miMark(boxes){
  const f = document.getElementById('mi-form');
  ['name', 'phone', 'email'].forEach(function(n){
    const i = f.elements[n], on = boxes.indexOf(n) >= 0;
    i.closest('.field').classList.toggle('has-error', on);
    if (on) i.setAttribute('aria-invalid', 'true'); else i.removeAttribute('aria-invalid');
  });
  if (boxes.length) f.elements[boxes[0]].focus();
}
function sendInterest(ev){
  ev.preventDefault();
  const f = ev.target, btn = document.getElementById('mi-send');
  document.getElementById('mi-err').hidden = true; miMark([]);
  const v = n => f.elements[n].value.trim();
  const empty = ['name', 'phone', 'email'].filter(n => !v(n));
  if (empty.length) { showInterestError('required', empty); return false; }
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
/* typing in a marked box clears its error look */
document.getElementById('mi-form').addEventListener('input', function(e){
  const d = e.target.closest('.field'); if (d) { d.classList.remove('has-error'); e.target.removeAttribute('aria-invalid'); }
});
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
