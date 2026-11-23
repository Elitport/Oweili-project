<?php
/* =====================================================================
 *  index.php — Oweili homepage in the calm style (owner, 19 Nov 2026).
 *  The painting video fills the hero at the top (still frame first; alone on slow connections,
 *  Data Saver or reduced motion) under a warm soft dark layer; the badge, the headline, the two
 *  lines, Explore Gallery and Join as Artist sit straight on it, centred. Then the footer (owner, 24 Nov 2026:
 *  no trust points and no row of section links on the home page). Under the hero only when the admin
 *  picked one in admin_gallery.php: "Artist of the week" (the artwork photo whole, never covered).
 *  Text is printed in both languages (bi-en / bi-ar).
 *  Tables read: site_settings, users, artworks (gallery_lib.php)
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/page_cache.php'; owPublicPage();   // public page: Back opens at once, early load on touch (page_cache.php)
require_once __DIR__ . '/artwork_lib.php';   // owBi(): one text in both languages
require_once __DIR__ . '/gallery_lib.php';   // Artist of the week
$aotw = null;
try { $aotw = owArtistOfWeek(); } catch (Throwable $e) { $aotw = null; }
?>
<!DOCTYPE html>
<html <?php require_once __DIR__ . '/lang_lib.php'; owHtmlAttrs(); ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require_once __DIR__ . '/seo_lib.php'; owSeo(['en' => 'Everything here started with real hands', 'ar' => 'كل ما تراه هنا بدأ بيد حقيقية', 'desc_en' => 'Oweili is the home of real art. See how every piece was made, then make it yours.', 'desc_ar' => 'أويلي بيت الفن الأصيل. شاهد كيف صُنعت كل قطعة، ثم اقتنها.', 'path' => '']); ?>
<meta property="og:title" content="Oweili · Everything here started with real hands · كل ما تراه هنا، بدأ بيد حقيقية">
<meta property="og:description" content="Every piece here was made by real hands">
<meta property="og:type" content="website">
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/nav.css') ?>">
<style>
/* =====================================================================
   Home page, calm style (owner, 19 Nov 2026): the shared calm layer (assets/css/oweili.css) gives the
   fonts, colours, the hero layer, the transparent menu and the cards; this block places the video hero,
   the trust line, Artist of the week and the row of section links.
   ===================================================================== */
html, body { margin: 0; }
body { overflow-x: hidden; }

/* ---- the hero: the painting video behind the centred text ---- */
.c-home .c-hero { position: relative; isolation: isolate; overflow: hidden; }
.c-home .c-hero .hero-bg { position: absolute; inset: 0; z-index: -2; overflow: hidden; background-color: #2B1F1C; }
.hero-bg video { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: 40% 50%; opacity: 0; transition: opacity 1.6s ease; }
.hero-bg video.on { opacity: 1; }
.hero-still, .hero-still img { position: absolute; inset: 0; width: 100%; height: 100%; }
.hero-still img { object-fit: cover; object-position: 40% 50%; display: block; }
/* phones get their own upright cut of the video and still, with the brush and canvas in the middle (owner, 20 Nov 2026) */
@media (max-width: 640px) { .hero-bg video, .hero-still img { object-position: 50% 50%; } }
.c-home .c-hero::after { content: ""; position: absolute; inset: 0; z-index: -1; pointer-events: none; background: var(--calm-veil); }
/* the home video since 20 Nov 2026 has a bright white canvas: the same warm dark layer, a little stronger, so every white word stays clear (owner) */
.c-home .c-hero::after { background: linear-gradient(180deg, rgba(40,28,22,.60) 0%, rgba(40,28,22,.54) 55%, rgba(40,28,22,.64) 100%); }
.c-home .hero-what { font-size: 21px; font-weight: 500; line-height: 1.6; max-width: 640px; margin: 0 auto 8px; color: #fff; }
.c-home .hero-sub { font-size: 17px; line-height: 1.7; max-width: 560px; margin: 0 auto 30px; color: rgba(255,255,255,0.92); }
.c-home .hero-btns { display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; }
.c-home .hero-btns .btn { min-width: 190px; white-space: nowrap; }
/* ---- under the hero ---- */
.c-below { max-width: 1100px; margin: 0 auto; padding: 72px 24px 88px; display: flex; flex-direction: column; gap: 56px; }
/* Artist of the week: the artwork photo whole and clear (never covered) */
.b-aotw { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr); align-items: center; gap: 0; overflow: hidden; color: inherit; text-decoration: none; }
.aotw-pic { position: relative; display: block; aspect-ratio: 4 / 3; background: var(--color-frame, #f3efe9); }
.aotw-pic img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain; display: block; }
.aotw-txt { display: flex; flex-direction: column; align-items: flex-start; gap: 18px; padding: 36px; }
.aotw-k { display: block; font-size: 13px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--color-body); margin-bottom: 6px; }
[dir="rtl"] .aotw-k { letter-spacing: 0; font-size: 14px; }
.aotw-n { display: block; font-family: var(--calm-serif); font-size: 32px; font-weight: 700; color: var(--color-dark); line-height: 1.3; }
.aotw-go { display: inline-flex; align-items: center; min-height: 44px; font-size: 15px; font-weight: 600; color: var(--color-dark); white-space: nowrap; padding: 0 18px; border-radius: 999px; border: 1px solid var(--calm-line); }
.b-aotw:hover .aotw-go, .b-aotw:focus-visible .aotw-go { border-color: var(--color-dark); }
@media (max-width: 1023px) {
  .b-aotw { grid-template-columns: 1fr; }
  .aotw-txt { padding: 26px; }
}
@media (max-width: 640px) {
  .c-home .hero-what { font-size: 18px; }
  .c-home .hero-sub { font-size: 16px; margin-bottom: 24px; }
  .c-home .hero-btns { flex-direction: column; align-items: stretch; width: 100%; max-width: 360px; margin: 0 auto; }
  .c-home .hero-btns .btn { width: 100%; min-width: 0; }
  .c-below { padding: 48px 16px 64px; gap: 40px; }
}
@media (prefers-reduced-motion: reduce) {
  .hero-bg video { display: none; }
}
</style>
</head>
<body class="s-b c-page">
<?php require_once __DIR__ . '/studio_lib.php'; ?>
<?php
/* the painting: the still frame first (its tiny copy shows at once), then the looping video, behind the hero */
ob_start(); ?>
<div class="hero-bg" aria-hidden="true">
  <style>.hero-bg{background:url(<?= OW_HERO_TINY['paint'] ?>) 40% 50% / cover no-repeat}</style>
  <picture class="hero-still">
    <source media="(max-width: 640px)" type="image/avif" srcset="<?= owAsset('assets/img/hero/hero-paint-phone.avif') ?>"><source media="(max-width: 640px)" type="image/webp" srcset="<?= owAsset('assets/img/hero/hero-paint-phone.webp') ?>"><source media="(max-width: 640px)" srcset="<?= owAsset('assets/img/hero/hero-paint-phone.jpg') ?>">
    <source type="image/avif" srcset="<?= owAsset('assets/img/hero/hero-paint.avif') ?>"><source type="image/webp" srcset="<?= owAsset('assets/img/hero/hero-paint.webp') ?>">
    <img src="<?= owAsset('assets/img/hero/hero-paint.jpg') ?>" alt="A hand painting with a brush on an abstract canvas · يد ترسم بالفرشاة على لوحة تجريدية" width="1280" height="720" fetchpriority="high" decoding="sync">
  </picture>
  <video id="vid" class="bg-now" autoplay muted loop playsinline preload="none" data-src="<?= owAsset('assets/video/hero-paint.mp4') ?>" data-src-phone="<?= owAsset('assets/video/hero-paint-phone.mp4') ?>"></video>
</div>
<?php $owPainting = ob_get_clean();

?>

<?php include __DIR__ . '/nav.php'; ?>

<main class="c-home">
  <section class="c-hero">
    <?= $owPainting ?>
    <div class="c-hero-in">
      <span class="hero-badge"><?= owBi('✦ A Real Touch', '✦ لمسة حقيقية') ?></span>
      <h1><?= owBi('Everything here started with real hands', 'كل ما تراه هنا، بدأ بيد حقيقية') ?></h1>
      <p class="hero-what"><?= owBi('Oweili is an online gallery where you buy original handmade paintings straight from real artists', 'أويلي معرض إلكتروني تقتني فيه لوحات أصلية مرسومة باليد، مباشرة من فنانين حقيقيين') ?></p>
<?php ob_start(); ?>
      <p class="hero-sub"><?= owBi('Every piece here was made by real hands', 'كل قطعة هنا صنعتها يد حقيقية') ?></p>
<?php $owHeroSub = ob_get_clean(); echo $owHeroSub; ?>
      <div class="hero-btns">
        <a class="btn btn-primary" href="marketplace.php"><?= owBi('Explore Gallery', 'استكشف المعرض') ?></a>
        <a class="btn btn-secondary" href="register_artist.php"><?= owBi('Join as Artist', 'انضم كفنان') ?></a>
      </div>
    </div>
  </section>
  <div class="c-under"><?= $owHeroSub /* the second line: in the hero on iPads and laptops, just under it on phones (owner, 20 Nov 2026) */ ?></div>
  <?php if ($aotw): ?>
  <div class="c-below">
    <?php $aImg = $aotw['art'] ? owImgSrc((string) $aotw['art']['image_url']) : ''; if ($aImg === '') $aImg = owImgSrc((string) ($aotw['profile_picture'] ?? '')); ?>
    <a class="card b-aotw" href="artist_dashboard.php?id=<?= (int) $aotw['id'] ?>">
      <span class="aotw-pic"><?php if ($aImg !== ''): ?><img src="<?= e(owImgSized($aImg, 800)) ?>" alt="<?= e($aotw['artist_en'] . ' · ' . $aotw['artist_ar']) ?>"<?= owImgFallbackAttrs($aImg, owImgSized($aImg, 800)) ?>><?php endif; ?></span>
      <span class="aotw-txt"><span><span class="aotw-k"><?= owBi('Artist of the week', 'فنان الأسبوع') ?></span>
        <span class="aotw-n"><?= owBiName((string) $aotw['artist_en'], (string) $aotw['artist_ar']) ?><?php if ((int) $aotw['verified'] === 1): ?> <?= owVerifiedBadge(true) ?><?php endif; ?></span></span>
        <span class="aotw-go"><?= owBi('Meet the artist →', 'تعرّف على الفنان ←') ?></span></span>
    </a>
  </div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/footer.php'; ?>

<script>
/* Language button in the shared menu: flip the page direction; nav.php then
   updates the menu words and the bi-en / bi-ar texts follow the direction. */
function tgl(){
  var h = document.documentElement, ar = h.getAttribute('dir') !== 'rtl';
  h.setAttribute('dir', ar ? 'rtl' : 'ltr');
  h.setAttribute('lang', ar ? 'ar' : 'en');
  document.title = ar ? 'أويلي · كل ما تراه هنا، بدأ بيد حقيقية' : 'Oweili · Everything here started with real hands';
  try { localStorage.setItem('lang', ar ? 'ar' : 'en'); } catch (e) {}
}
if (document.documentElement.getAttribute('dir') === 'rtl') { document.title = 'أويلي · كل ما تراه هنا، بدأ بيد حقيقية'; }

/* One video, looping (owner, 6 Nov 2026): it fades in over its still frame as soon as it can
   play. Slow connections, Data Saver and reduced motion get the still frame only. */
(function(){
  var a = document.getElementById('vid');
  if (!a) return;
  var on = function(){ a.classList.add('on'); };
  if (a.readyState >= 3) on();   /* already ready (e.g. saved in the browser) */
  a.addEventListener('canplay', on, { once: true });
  a.addEventListener('playing', on, { once: true });
  var p = a.play(); if (p && p.catch) p.catch(function(){});
})();
</script>
</body>
</html>
