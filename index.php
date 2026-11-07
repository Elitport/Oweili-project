<?php
/* =====================================================================
 *  index.php — Oweili homepage: one full-screen hero, nothing else.
 *  Background: one real Pexels video, looping, filling the whole screen (owner, 6 Nov 2026):
 *  the wide blue hand painting dots (assets/video/hero-wide.mp4). Phones (640 px and narrower,
 *  owner 7 Nov 2026) show it whole under the menu with the card below. A still frame of the
 *  same video shows at once
 *  (and alone on slow connections, Data Saver or
 *  reduced motion). The text sits on a small frosted-glass card so it reads clearly. Badge, headline,
 *  one-line subtitle and two buttons. Text is printed in both languages
 *  (bi-en / bi-ar); the shared menu's language switch shows the one that
 *  matches the page direction.
 *  "Artist of the week" (picked by the admin in admin_gallery.php): a small
 *  frosted card in the corner of the same screen, hidden when none is picked.
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
<?php require_once __DIR__ . '/seo_lib.php'; owSeo(['en' => 'Everything here started with real hands', 'ar' => 'كل ما تراه هنا بدأ بيدٍ حقيقية', 'desc_en' => 'Oweili is the home of real art. See how every piece was made, then make it yours.', 'desc_ar' => 'أويلي بيت الفن الأصيل. شاهد كيف صُنعت كل قطعة، ثم اقتنها.', 'path' => '']); ?>
<meta property="og:title" content="Oweili · Everything here started with real hands · كل ما تراه هنا، بدأ بيدٍ حقيقية">
<meta property="og:description" content="Every piece here was made by real hands. And you can see how.">
<meta property="og:type" content="website">
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/nav.css') ?>">
<style>
/* Homepage — one screen. The headline is the first thing to notice, the two
   buttons are the action (rule 30). Colors, fonts and buttons come from oweili.css. */
html, body { height: 100%; margin: 0; }
body { overflow-x: hidden; overflow-y: hidden; max-height: 100vh; max-height: 100svh; }

/* full-screen video in its real colours (no veil); its still frame shows first */
.hero-bg { position: fixed; inset: 0; z-index: 0; overflow: hidden; background: var(--color-bg); }
/* EDGE TO EDGE (owner, 6 Nov 2026): both videos and the still frame fill the whole screen, like the
   background photos (object-fit: cover), no blurred sides. Each keeps its main subject in view:
   the hand and brush of the blue video (right of centre), the tip of the pink brush. The blue file
   was re-made from the owner's original with its thin black side bars trimmed off. */
.hero-bg video { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: 66% 50%; opacity: 0; transition: opacity 1.6s ease; }
.hero-bg video.on { opacity: 1; }
.hero-still, .hero-still img { position: absolute; inset: 0; width: 100%; height: 100%; }
.hero-still img { object-fit: cover; object-position: 66% 50%; display: block; }
/* PHONES (owner, 7 Nov 2026): the same wide blue video as computers, shown WHOLE at full screen width
   just under the menu (16:9, no zoom, no crop), like the photo pages on phones. Its bottom fades softly
   into a colour taken from the video's own bottom edge (#6b5551), which fills the rest of the screen,
   and the title card sits below the video. Dark mode: the same dimming as computers (oweili.css,
   brightness 72% on the whole layer, so the video and the colour match). Computers unchanged. */
@media (max-width: 640px) {
  /* the colour fills the whole page; the page may scroll a little so the card is never cut off */
  html { background: #6b5551 !important; }
  html.dark-mode { background: color-mix(in srgb, #6b5551 72%, #000) !important; }
  html, body { height: auto; }
  body { max-height: none; overflow-y: visible; background: transparent !important; }
  .hero-bg { position: absolute; bottom: auto; height: calc(var(--nav-h) + 100vw * 9 / 16); background-color: #6b5551; }
  .hero-bg video, .hero-still { top: var(--nav-h); bottom: auto; height: calc(100vw * 9 / 16); }
  .hero-bg video, .hero-still img { object-position: 50% 50%; }
  .hero-bg::after { content: ""; position: absolute; left: 0; right: 0; top: calc(var(--nav-h) + 100vw * 9 / 16 * 0.7); height: calc(100vw * 9 / 16 * 0.3 + 1px);
    background: linear-gradient(to bottom, rgba(107,85,81,0), #6b5551); pointer-events: none; }
  main.hero { align-items: flex-start; min-height: 0; padding-top: calc(var(--nav-h) + 100vw * 9 / 16 + var(--space-md)); }
}

.hero {
  position: relative; z-index: 2;
  min-height: 100vh; min-height: 100svh;
  display: flex; align-items: center; justify-content: center;
  padding: calc(var(--nav-h) + var(--space-lg)) var(--space-sm) var(--space-xl);
  text-align: center;
}
.hero-inner { max-width: 880px; display: flex; flex-direction: column; align-items: center; }
/* the text sits on a small frosted-glass card, so the video keeps its strong colours */
.hero-card { display: flex; flex-direction: column; align-items: center; padding: var(--space-lg) var(--space-lg) var(--space-md);
  border-radius: var(--radius-card); background: rgba(var(--rgb-surface),0.74); -webkit-backdrop-filter: blur(14px) saturate(1.2); backdrop-filter: blur(14px) saturate(1.2);
  border: 1px solid rgba(var(--rgb-surface),0.55); box-shadow: 0 12px 40px rgba(var(--rgb-shadow),0.18); }
.hero-card .hero-badge { background: transparent; }
@supports not ((-webkit-backdrop-filter: blur(1px)) or (backdrop-filter: blur(1px))) { .hero-card { background: rgba(var(--rgb-surface),0.92); } }
@media (max-width: 420px) { .hero-card { padding: var(--space-md) var(--space-sm); } }

.hero-badge {
  display: inline-flex; align-items: center; gap: var(--space-xs);
  padding: 6px 14px; margin-bottom: var(--space-lg);
  border: 1px solid rgba(var(--rgb-ink),0.18); border-radius: var(--radius-pill);
  background: rgba(var(--rgb-surface),0.72); -webkit-backdrop-filter: blur(10px); backdrop-filter: blur(10px);
  font-size: var(--fs-min); font-weight: 500; letter-spacing: 0.04em; color: var(--color-dark);
}
[dir="rtl"] .hero-badge { letter-spacing: 0; font-size: 13px; }

.hero h1 {
  font-size: 56px; font-weight: 300; letter-spacing: -0.02em; line-height: 1.1;
  color: var(--color-dark); margin: 0 0 var(--space-md);
}
[dir="rtl"] .hero h1 { letter-spacing: 0; line-height: 1.3; }
.hero-sub { font-size: 18px; line-height: 1.6; color: var(--color-body); max-width: 520px; margin: 0 auto var(--space-lg); }
[dir="rtl"] .hero-sub { line-height: 1.7; }

.hero-btns { display: flex; flex-wrap: wrap; gap: var(--space-sm); justify-content: center; }
.hero-btns .btn { min-width: 200px; }

/* one very quick soft fade (0.15 s), everything at once: nothing starts hidden or waits */
@keyframes ow-fadeUp {from{opacity:1}to{opacity:1}}
.fade { animation:ow-fadeUp .15s ease-out both; }

.aotw-home { position: fixed; z-index: 3; bottom: var(--space-md); inset-inline-end: var(--space-md); display: flex; align-items: center; gap: 12px;
  max-width: 340px; padding: 10px 16px 10px 10px; border-radius: var(--radius-card); text-decoration: none; color: inherit;
  background: rgba(var(--rgb-surface),0.82); -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px);
  border: 1px solid rgba(var(--rgb-ink),0.12); box-shadow: 0 10px 30px rgba(var(--rgb-shadow),0.12); text-align: start; }
[dir="rtl"] .aotw-home { padding: 10px 10px 10px 16px; }
.aotw-home:hover { transform: translateY(-2px); }
.aotw-home:focus-visible { outline: 2px solid var(--color-accent); outline-offset: 2px; }
.aotw-pic { width: 64px; height: 64px; flex-shrink: 0; border-radius: 12px; overflow: hidden; background: var(--color-frame); }
.aotw-pic img { width: 100%; height: 100%; object-fit: cover; display: block; }
.aotw-k { display: block; font-size: var(--fs-min); font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--color-body); }
[dir="rtl"] .aotw-k { letter-spacing: 0; font-size: 13px; }
.aotw-n { display: block; font-size: 16px; font-weight: 700; color: var(--color-dark); line-height: 1.3; }
.aotw-go { display: block; font-size: var(--fs-caption); font-weight: 700; color: var(--color-accent); }
@media (max-width: 768px) {
  .aotw-home { position: static; margin: var(--space-lg) auto 0; max-width: 320px; }
  .hero h1 { font-size: 36px; }
  .hero-sub { font-size: 16px; }
}
@media (max-width: 420px) {
  .hero-btns { flex-direction: column; width: 100%; max-width: 320px; }
  .hero-btns .btn { width: 100%; min-width: 0; }
}
@media (max-height: 640px) and (max-width: 768px) { .aotw-home { display: none; } }
@media (prefers-reduced-motion: reduce) {
  .aotw-home:hover { transform: none; }
  .fade { animation: none; }
  .hero-bg video { display: none; }
}
</style>
</head>
<body>
<?php require_once __DIR__ . '/studio_lib.php'; ?>
<?php /* the still frame's tiny copy fills the screen the moment the page opens (no white flash) */ ?>
<div class="hero-bg" aria-hidden="true">
  <style>.hero-bg{background:url(<?= OW_HERO_TINY['blue'] ?>) 66% 50% / cover no-repeat}@media (max-width:640px){.hero-bg{background:url(<?= OW_HERO_TINY['blue'] ?>) 50% var(--nav-h) / 100% auto no-repeat #6b5551}}</style>
  <picture class="hero-still">
    <source type="image/avif" srcset="<?= owAsset('assets/img/hero/hero-wide.avif') ?>"><source type="image/webp" srcset="<?= owAsset('assets/img/hero/hero-wide.webp') ?>">
    <img src="<?= owAsset('assets/img/hero/hero-wide.jpg') ?>" alt="A hand painting white dots on a colourful canvas · يد ترسم نقاطاً بيضاء على لوحة ملونة" width="1280" height="720" fetchpriority="high" decoding="sync">
  </picture>
  <?php /* one video only: the wide blue hand painting dots, on computers and (since 7 Nov 2026) on phones too */ ?>
  <video id="vid" class="bg-now" autoplay muted loop playsinline preload="none" data-src="<?= owAsset('assets/video/hero-wide.mp4') ?>"></video>
</div>
</div>

<?php include __DIR__ . '/nav.php'; ?>

<main class="hero">
  <div class="hero-inner">
    <div class="hero-card">
      <span class="hero-badge fade fade-1"><?= owBi('✦ A Real Touch', '✦ لمسة حقيقية') ?></span>
      <h1 class="fade fade-2"><?= owBi('Everything here started with real hands', 'كل ما تراه هنا، بدأ بيدٍ حقيقية') ?></h1>
      <p class="hero-sub fade fade-3"><?= owBi('Every piece here was made by real hands. And you can see how.', 'كل قطعة هنا صنعتها يد حقيقية. وتستطيع أن ترى كيف.') ?></p>
      <div class="hero-btns fade fade-4">
        <a class="btn btn-primary" href="marketplace.php"><?= owBi('Explore Gallery', 'استكشف المعرض') ?></a>
        <a class="btn btn-secondary" href="register_artist.php"><?= owBi('Join as Artist', 'انضم كفنان') ?></a>
      </div>
    </div>
    <?php if ($aotw): $aImg = $aotw['art'] ? owImgSrc((string) $aotw['art']['image_url']) : ''; if ($aImg === '') $aImg = owImgSrc((string) ($aotw['profile_picture'] ?? '')); ?>
    <a class="aotw-home fade fade-4" href="artist_dashboard.php?id=<?= (int) $aotw['id'] ?>">
      <span class="aotw-pic"><?php if ($aImg !== ''): ?><img src="<?= e(owImgSized($aImg, 400)) ?>" alt="<?= e($aotw['artist_en'] . ' · ' . $aotw['artist_ar']) ?>"<?= owImgFallbackAttrs($aImg, owImgSized($aImg, 400)) ?>><?php endif; ?></span>
      <span><span class="aotw-k"><?= owBi('Artist of the week', 'فنان الأسبوع') ?></span>
        <span class="aotw-n"><?= owBi((string) $aotw['artist_en'], (string) $aotw['artist_ar']) ?><?php if ((int) $aotw['verified'] === 1): ?> <?= owVerifiedBadge(true) ?><?php endif; ?></span>
        <span class="aotw-go"><?= owBi('Meet the artist →', 'تعرّف على الفنان ←') ?></span></span>
    </a>
    <?php endif; ?>
  </div>
</main>

<script>
/* Language button in the shared menu: flip the page direction; nav.php then
   updates the menu words and the bi-en / bi-ar texts follow the direction. */
function tgl(){
  var h = document.documentElement, ar = h.getAttribute('dir') !== 'rtl';
  h.setAttribute('dir', ar ? 'rtl' : 'ltr');
  h.setAttribute('lang', ar ? 'ar' : 'en');
  document.title = ar ? 'أويلي · كل ما تراه هنا، بدأ بيدٍ حقيقية' : 'Oweili · Everything here started with real hands';
  try { localStorage.setItem('lang', ar ? 'ar' : 'en'); } catch (e) {}
}
if (document.documentElement.getAttribute('dir') === 'rtl') { document.title = 'أويلي · كل ما تراه هنا، بدأ بيدٍ حقيقية'; }

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
