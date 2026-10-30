<?php
/* =====================================================================
 *  artwork.php — one artwork's own page (artwork.php?id=N), for sharing.
 *  When the link is shared on WhatsApp, X and others, the preview shows
 *  the artwork photo, its title and the artist (seo_lib.php).
 *  Shows: the photo, titles, artist, type, price, description, the
 *  Creation Journey, a heart (favorites), "I’m Interested" (opens the
 *  artwork's window in the Gallery) and a way back to the Gallery.
 *  Only artworks a visitor can see in the Gallery; others get the
 *  friendly "not found" page.
 *  Tables: artworks, users, artwork_stages, artwork_colors, favorites
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/artwork_lib.php';
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/seo_lib.php';
$ART_TYPES = require __DIR__ . '/art_types.php';

$id = (int) ($_GET['id'] ?? 0);
$aw = null;
if ($id > 0) {
    $st = getDB()->prepare("SELECT a.*, " . owArtistNameSql('u') . " FROM artworks a JOIN users u ON u.id = a.artist_id
                            WHERE a.id = :id AND a.is_approved = 1 AND a.status IN ('available','auction') AND u.is_banned = 0" . owNotDraftSql('a'));
    $st->execute([':id' => $id]);
    $aw = $st->fetch() ?: null;
}
if (!$aw) { http_response_code(404); require __DIR__ . '/404.php'; exit; }

$ar   = ($_GET['lang'] ?? '') === 'ar';
$img  = owImgSrc((string) $aw['image_url']);
$big  = $img !== '' ? owImgSized($img, 1600) : '';
$tEn  = (string) ($aw['title_en'] ?: $aw['title_ar']);
$tAr  = (string) ($aw['title_ar'] ?: $aw['title_en']);
$type = $ART_TYPES[$aw['type']] ?? null;
$stages = owGetStagesFor([$id])[$id] ?? [];
$colors = owGetColorsFor([$id])[$id] ?? [];
$journey = owJourneyHtml($stages, $colors, (string) $aw['type'], $ART_TYPES);
$viewer  = isLoggedIn() ? currentUser() : null;
$isBuyer = $viewer && ($viewer['role'] ?? '') === 'collector';
$showFav = (!$viewer || $isBuyer) && owHasTable('favorites');
$faved   = $isBuyer && in_array($id, owFavIds((int) $viewer['id']), true);
$price   = number_format((float) $aw['price']);
$descEn  = 'By ' . $aw['artist_en'] . ' · ' . $price . ' SAR' . ($type ? ' · ' . $type['en'] : '') . '. Made by human hands, on Oweili.';
$descAr  = 'بواسطة ' . $aw['artist_ar'] . ' · ' . $price . ' ر.س' . ($type ? ' · ' . $type['ar'] : '') . '. صُنع بيد بشرية، في أويلي.';
$share   = $img !== '' ? owImgSized($img, 800) : '';
$galleryLink = 'marketplace.php?show=' . $id . ($ar ? '&lang=ar' : '') . '#artwork-' . $id;
?>
<!DOCTYPE html>
<html lang="<?= $ar ? 'ar' : 'en' ?>" dir="<?= $ar ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php owSeo(['en' => $tEn, 'ar' => $tAr, 'desc_en' => $descEn, 'desc_ar' => $descAr, 'image' => $share, 'path' => 'artwork.php?id=' . $id, 'type' => 'article']); ?>
<?php include __DIR__ . '/theme_head.php'; ?>
<?php if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ar'], true)): ?><script>try { localStorage.setItem('lang', <?= json_encode($_GET['lang']) ?>); } catch (e) {}</script><?php endif; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/proof.css') ?>">
<?php if ($big !== ''): ?><link rel="preload" as="image" href="<?= e($big) ?>"><?php endif; ?>
<style>
.aw-page{position:relative;z-index:10;max-width:1100px;margin:0 auto;padding:calc(var(--nav-h) + var(--space-lg)) var(--space-sm) var(--space-2xl);display:grid;grid-template-columns:1.2fr 1fr;gap:var(--space-lg);align-items:start;}
.aw-photo{position:relative;border-radius:var(--radius-card);overflow:hidden;background:var(--color-frame);border:1px solid var(--color-border);aspect-ratio:4/3;}
.aw-photo img{width:100%;height:100%;object-fit:contain;display:block;}
.aw-photo img[hidden]{display:none;}
.aw-info h1{font-size:var(--fs-section);margin:0 0 4px;}
.aw-info .sub{font-size:var(--fs-subtitle);color:var(--color-body);margin:0 0 var(--space-sm);}
.aw-info .by{font-size:15px;margin:0 0 var(--space-sm);}
.aw-info .row{display:flex;gap:var(--space-xs);flex-wrap:wrap;align-items:center;margin-bottom:var(--space-sm);}
.aw-info .price{font-size:var(--fs-card);font-weight:700;color:var(--color-dark);margin:var(--space-sm) 0;}
.aw-info .desc{color:var(--color-body);white-space:pre-line;}
.aw-acts{display:flex;gap:var(--space-xs);flex-wrap:wrap;align-items:center;margin-top:var(--space-md);}
.aw-journey{grid-column:1 / -1;}
@media(max-width:860px){.aw-page{grid-template-columns:1fr;padding-top:calc(var(--nav-h) + var(--space-md));}}
</style>
</head>
<body>
<div class="bg-wrap"><video class="bg-video" autoplay loop muted playsinline preload="none" src="<?= owBase() ?>bg.mp4"></video></div>
<div class="overlay"></div>
<?php include __DIR__ . '/nav.php'; ?>
<main class="aw-page">
  <div class="aw-photo">
    <?php if ($img !== ''): ?>
      <img src="<?= e($big) ?>" alt="<?= e($tEn . ' · ' . $tAr) ?>" fetchpriority="high" decoding="async"<?= owImgFallbackAttrs($img, $big) ?>><?= owImgPlaceholder(true) ?>
    <?php else: ?><?= owImgPlaceholder(false) ?><?php endif; ?>
    <?php if ($showFav): ?><button type="button" class="fav-btn" data-fav="<?= $id ?>" aria-pressed="<?= $faved ? 'true' : 'false' ?>" aria-label="<?= $faved ? 'Remove from favorites · أزل من المفضلة' : 'Save to favorites · احفظ في المفضلة' ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 7.9 3.6 4.5 7 4.5c2 0 3.6 1.1 5 2.9 1.4-1.8 3-2.9 5-2.9 3.4 0 5.6 3.4 4.3 6.8-1.8 4.6-9.3 9.2-9.3 9.2z"/></svg></button><?php endif; ?>
  </div>
  <div class="aw-info card">
    <h1><?= owBi($tEn, $tAr) ?></h1>
    <p class="sub"><?= owBi($tAr !== $tEn ? $tAr : '', $tEn !== $tAr ? $tEn : '') ?></p>
    <p class="by"><a href="artist_dashboard.php?id=<?= (int) $aw['artist_id'] ?>"><?= owBi('by ' . $aw['artist_en'], 'بواسطة ' . $aw['artist_ar']) ?></a></p>
    <div class="row">
      <?php if ($type): ?><span class="type-pill"><?= owBi($type['en'], $type['ar']) ?></span><?php endif; ?>
      <?php if (owHasJourney($stages)): ?><span class="human-badge"><?= owBi('✦ A Real Touch', '✦ لمسة حقيقية') ?></span><?php endif; ?>
      <?php if ($aw['status'] === 'auction'): ?><span class="type-pill"><?= owBi('Auction', 'مزاد') ?></span><?php endif; ?>
    </div>
    <div class="price"><?= $price ?> <?= owBi('SAR', 'ر.س') ?></div>
    <?php if (trim((string) $aw['description_en']) !== '' || trim((string) $aw['description_ar']) !== ''): ?>
      <p class="desc"><span class="bi-en" lang="en"><?= e((string) ($aw['description_en'] ?: $aw['description_ar'])) ?></span><span class="bi-ar" lang="ar"><?= e((string) ($aw['description_ar'] ?: $aw['description_en'])) ?></span></p>
    <?php endif; ?>
    <div class="aw-acts">
      <a class="btn btn-primary" href="<?= e($galleryLink) ?>"><?= owBi('I’m Interested', 'أنا مهتم') ?></a>
      <a class="btn btn-secondary" href="marketplace.php<?= $ar ? '?lang=ar' : '' ?>"><?= owBi('See more in the Gallery', 'شاهد المزيد في المعرض') ?></a>
    </div>
  </div>
  <?php if ($journey !== ''): ?><section class="aw-journey card"><?= $journey ?></section><?php endif; ?>
</main>
<script>
function tgl(){ var ar = document.documentElement.getAttribute('dir') !== 'rtl';
  document.documentElement.setAttribute('dir', ar ? 'rtl' : 'ltr'); document.documentElement.setAttribute('lang', ar ? 'ar' : 'en'); }
(function(){ var v = document.querySelector('.bg-video'); if (!v) return; var on = function(){ v.classList.add('on'); };
  v.addEventListener('canplay', on, {once:true}); v.addEventListener('playing', on, {once:true}); v.play().catch(function(){}); })();
<?php if ($showFav): ?>
window.OW_FAV = { buyer: <?= $isBuyer ? 'true' : 'false' ?>, csrf: <?= json_encode(csrfToken()) ?>, ids: <?= $faved ? '["' . $id . '"]' : '[]' ?>,
  labels: { on: 'Save to favorites · احفظ في المفضلة', off: 'Remove from favorites · أزل من المفضلة' },
  login: function(id){ location.href = 'login.php?next=' + encodeURIComponent('artwork.php?id=' + id); } };
<?php endif; ?>
</script>
<?php if ($showFav): ?><script src="<?= owAsset('assets/js/favorites.js') ?>"></script><?php endif; ?>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
