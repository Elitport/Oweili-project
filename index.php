<?php
/* =====================================================================
 *  index.php — Oweili homepage: glass + Bento grid (owner's choice "Sample B", 16 Nov 2026).
 *  Soft, large brand colour shapes (pink, navy, grey-blue) sit behind the whole page; every box is
 *  frosted glass (see-through, blurred, thin light edge, soft shadow; lighter blur on phones).
 *  Boxes: the big box (badge, headline, two lines, Explore Gallery and Join as Artist), the painting
 *  video in its own clear box (still frame first; alone on slow connections, Data Saver or reduced
 *  motion), "Artist of the week" with the artwork photo large and never blurred (only when the admin
 *  picked one in admin_gallery.php), the three trust points, and six boxes linking to the site's
 *  sections with the top menu's names (Art Institutes only while it is in the menu).
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
<?php require_once __DIR__ . '/seo_lib.php'; owSeo(['en' => 'Everything here started with real hands', 'ar' => 'كل ما تراه هنا بدأ بيدٍ حقيقية', 'desc_en' => 'Oweili is the home of real art. See how every piece was made, then make it yours.', 'desc_ar' => 'أويلي بيت الفن الأصيل. شاهد كيف صُنعت كل قطعة، ثم اقتنها.', 'path' => '']); ?>
<meta property="og:title" content="Oweili · Everything here started with real hands · كل ما تراه هنا، بدأ بيدٍ حقيقية">
<meta property="og:description" content="Every piece here was made by real hands. And you can see how.">
<meta property="og:type" content="website">
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/nav.css') ?>">
<style>
/* =====================================================================
   Home page: glass + Bento grid ("Sample B", chosen by the owner, 16 Nov 2026).
   Soft brand colour shapes behind the whole page; the painting video sits in its own box.
   Every box is real frosted glass: see-through, blurred, a thin light edge, a soft shadow.
   Lighter blur on phones so older phones stay fast. Artwork photos are never blurred or covered.
   ===================================================================== */
html, body { margin: 0; }
body { overflow-x: hidden; background: transparent; }
html { background: var(--color-bg); }

/* ---- glass ---- */
:root {
  --gl-bg: rgba(255,255,255,0.46);
  --gl-bg-strong: rgba(255,255,255,0.62);
  --gl-edge: rgba(255,255,255,0.72);
  --gl-shine: rgba(255,255,255,0.55);
  --gl-shadow: 0 18px 50px rgba(35,49,74,0.22), 0 2px 6px rgba(35,49,74,0.08);
  --gl-blur: blur(22px) saturate(170%);
  --gl-chip: rgba(255,255,255,0.55);
}
html.dark-mode {
  --gl-bg: rgba(15,21,32,0.48);
  --gl-bg-strong: rgba(15,21,32,0.62);
  --gl-edge: rgba(255,255,255,0.16);
  --gl-shine: rgba(255,255,255,0.10);
  --gl-shadow: 0 18px 50px rgba(0,0,0,0.45), 0 2px 6px rgba(0,0,0,0.25);
  --gl-chip: rgba(255,255,255,0.10);
}
.gl {
  position: relative; border-radius: 22px;
  background: var(--gl-bg);
  -webkit-backdrop-filter: var(--gl-blur); backdrop-filter: var(--gl-blur);
  border: 1px solid var(--gl-edge);
  box-shadow: var(--gl-shadow), inset 0 1px 0 var(--gl-shine);
  color: var(--color-dark);
}
/* a soft light sweep along the top edge, like real glass */
.gl::before { content: ""; position: absolute; inset: 0; border-radius: inherit; pointer-events: none;
  background: linear-gradient(160deg, var(--gl-shine) 0%, rgba(255,255,255,0) 38%); opacity: .55; }
@supports not ((-webkit-backdrop-filter: blur(1px)) or (backdrop-filter: blur(1px))) { .gl { background: var(--gl-bg-strong); } }
@media (prefers-reduced-transparency: reduce) { .gl { background: var(--color-surface); -webkit-backdrop-filter: none; backdrop-filter: none; } }
a.gl { text-decoration: none; transition: transform .25s cubic-bezier(.2,.7,.2,1), box-shadow .25s ease, border-color .25s ease; }
a.gl:hover { transform: translateY(-3px); box-shadow: 0 24px 60px rgba(var(--rgb-shadow),0.28), inset 0 1px 0 var(--gl-shine); border-color: rgba(var(--rgb-primary),0.7); }
a.gl:active { transform: translateY(-1px) scale(.99); }
a.gl:focus-visible { outline: 2px solid var(--color-accent); outline-offset: 3px; }

/* ---- page and grid ---- */
.bento-page { position: relative; z-index: 2; max-width: 1240px; margin: 0 auto; padding: calc(var(--nav-h) + 28px) 24px 56px; box-sizing: border-box; }
.bento { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); grid-auto-rows: minmax(152px, auto); grid-auto-flow: row dense; gap: 16px; }
.b-hero { grid-column: span 2; grid-row: span 3; }
.b-media { grid-column: span 2; grid-row: span 2; }
.b-aotw { grid-column: span 2; grid-row: span 2; }
.lg-wide { grid-column: span 2; }

/* ---- the big box: title, text, two buttons ---- */
.b-hero { display: flex; flex-direction: column; justify-content: center; align-items: flex-start; padding: 40px 40px 36px; text-align: start; }
.hero-badge { display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; margin-bottom: 22px; border-radius: 999px;
  background: var(--gl-chip); border: 1px solid var(--gl-edge); font-size: var(--fs-min); font-weight: 600; letter-spacing: .04em; color: var(--color-dark); }
[dir="rtl"] .hero-badge { letter-spacing: 0; font-size: 13px; }
.b-hero h1 { font-size: 52px; font-weight: 300; letter-spacing: -0.025em; line-height: 1.08; color: var(--color-dark); margin: 0 0 20px; text-wrap: balance; }
[dir="rtl"] .b-hero h1 { letter-spacing: 0; line-height: 1.3; }
.hero-what { font-size: 18px; font-weight: 600; line-height: 1.5; color: var(--color-dark); max-width: 520px; margin: 0 0 10px; }
.hero-sub { font-size: 17px; line-height: 1.6; color: var(--color-body); max-width: 520px; margin: 0 0 28px; }
[dir="rtl"] .hero-what, [dir="rtl"] .hero-sub { line-height: 1.75; }
.hero-btns { display: flex; flex-wrap: wrap; gap: 12px; }
.hero-btns .btn { min-width: 190px; white-space: nowrap; }
.hero-btns .btn-secondary { background: rgba(var(--rgb-surface),0.7); }

/* ---- small trust boxes ---- */
.b-trust { display: flex; flex-direction: column; justify-content: space-between; gap: 14px; padding: 22px; }
.b-trust .ico, .b-sec .ico { width: 44px; height: 44px; border-radius: 14px; display: grid; place-items: center; flex-shrink: 0;
  background: var(--gl-chip); border: 1px solid var(--gl-edge); }
.b-trust svg, .b-sec svg { width: 22px; height: 22px; fill: none; stroke: var(--color-dark); stroke-width: 1.6; stroke-linecap: round; stroke-linejoin: round; }
.b-trust p { margin: 0; font-size: 16px; font-weight: 700; line-height: 1.4; color: var(--color-dark); text-wrap: balance; overflow-wrap: normal; word-break: keep-all; hyphens: none; }
[dir="rtl"] .b-trust p { line-height: 1.6; }

/* ---- section boxes (links to the site's sections) ---- */
.b-sec { display: flex; flex-direction: column; justify-content: space-between; gap: 14px; padding: 22px; color: var(--color-dark); }
.b-sec .ttl { display: flex; align-items: flex-end; justify-content: space-between; gap: 10px; }
.b-sec .ttl span.t { font-size: 20px; font-weight: 700; line-height: 1.25; letter-spacing: -0.01em; color: var(--color-dark); }
[dir="rtl"] .b-sec .ttl span.t { letter-spacing: 0; line-height: 1.45; }
.b-sec .go { width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; flex-shrink: 0; background: var(--color-primary); transition: transform .25s ease; }
.b-sec .go svg { width: 16px; height: 16px; stroke: var(--color-on-primary); stroke-width: 2.2; }
[dir="rtl"] .b-sec .go svg { transform: scaleX(-1); }
a.b-sec:hover .go { transform: translateX(3px); }
[dir="rtl"] a.b-sec:hover .go { transform: translateX(-3px); }

/* ---- Artist of the week: the artwork photo large and clear (never blurred or covered) ---- */
.b-aotw { display: flex; flex-direction: column; padding: 12px; color: inherit; }
.aotw-pic { position: relative; flex: 1 1 auto; min-height: 190px; border-radius: 14px; overflow: hidden; background: rgba(var(--rgb-ink),0.10); }
.aotw-pic img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain; display: block; }
.aotw-txt { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; padding: 14px 10px 6px; }
.aotw-k { display: block; font-size: var(--fs-min); font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--color-body); }
[dir="rtl"] .aotw-k { letter-spacing: 0; font-size: 13px; }
.aotw-n { display: block; font-size: 20px; font-weight: 700; color: var(--color-dark); line-height: 1.3; }
.aotw-go { display: block; font-size: 14px; font-weight: 700; color: var(--color-dark); white-space: nowrap; padding: 8px 14px; border-radius: 999px; background: var(--gl-chip); border: 1px solid var(--gl-edge); }

/* ---- the painting (video + still frame); placed inside its own box below ---- */
.hero-bg { position: fixed; inset: 0; z-index: 0; overflow: hidden; background: var(--color-bg); }
.hero-bg video { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: 66% 50%; opacity: 0; transition: opacity 1.6s ease; }
.hero-bg video.on { opacity: 1; }
.hero-still, .hero-still img { position: absolute; inset: 0; width: 100%; height: 100%; }
.hero-still img { object-fit: cover; object-position: 66% 50%; display: block; }

/* ---- Sample B: soft brand colour shapes behind the whole page; the video in its own clear box ---- */
.ow-blobs { position: fixed; inset: -10%; z-index: 0; pointer-events: none; background:
  radial-gradient(38% 42% at 12% 18%, rgba(219,128,132,0.85), rgba(219,128,132,0) 70%),
  radial-gradient(40% 45% at 88% 82%, rgba(35,49,74,0.70), rgba(35,49,74,0) 70%),
  radial-gradient(34% 38% at 78% 22%, rgba(143,161,177,0.75), rgba(143,161,177,0) 70%),
  radial-gradient(36% 40% at 22% 86%, rgba(235,181,183,0.85), rgba(235,181,183,0) 70%),
  radial-gradient(30% 30% at 52% 50%, rgba(219,128,132,0.35), rgba(219,128,132,0) 70%),
  #F4EEF0; }
html.dark-mode .ow-blobs { background:
  radial-gradient(38% 42% at 12% 18%, rgba(219,128,132,0.42), rgba(219,128,132,0) 70%),
  radial-gradient(40% 45% at 88% 82%, rgba(70,96,140,0.55), rgba(70,96,140,0) 70%),
  radial-gradient(34% 38% at 78% 22%, rgba(143,161,177,0.30), rgba(143,161,177,0) 70%),
  radial-gradient(36% 40% at 22% 86%, rgba(201,106,110,0.30), rgba(201,106,110,0) 70%),
  #0F1520; }
@keyframes ow-drift { 0% { transform: translate3d(0,0,0) scale(1); } 50% { transform: translate3d(-2%,1.5%,0) scale(1.04); } 100% { transform: translate3d(0,0,0) scale(1); } }
@media (min-width: 1024px) and (prefers-reduced-motion: no-preference) { .ow-blobs { animation: ow-drift 28s ease-in-out infinite; } }
.s-b .b-media { position: relative; overflow: hidden; padding: 0; }
.s-b .b-media .hero-bg { position: absolute; inset: 0; z-index: 0; border-radius: inherit; }
.s-b .b-media .media-tag { position: absolute; z-index: 2; bottom: 14px; inset-inline-start: 14px; padding: 6px 12px; border-radius: 999px; font-size: 13px; font-weight: 700; color: var(--color-dark); }
.s-b .gl { --gl-bg: rgba(255,255,255,0.40); }
html.dark-mode .s-b .gl { --gl-bg: rgba(15,21,32,0.46); }
/* Sample B glass: a brighter edge that catches the light */
.s-b .gl::after { content: ""; position: absolute; inset: 0; border-radius: inherit; padding: 1px; pointer-events: none;
  background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(255,255,255,0.05) 40%, rgba(219,128,132,0.55));
  -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0); -webkit-mask-composite: xor; mask-composite: exclude; }
html.dark-mode .s-b .gl::after { background: linear-gradient(135deg, rgba(255,255,255,0.35), rgba(255,255,255,0.02) 40%, rgba(219,128,132,0.45)); }

/* ---- tablets: two columns ---- */
@media (max-width: 1023px) {
  .bento { grid-template-columns: repeat(2, minmax(0, 1fr)); grid-auto-rows: minmax(140px, auto); }
  .b-hero, .b-media, .b-aotw { grid-column: span 2; grid-row: auto; }
  .b-media { aspect-ratio: 16 / 9; }
  .b-aotw .aotw-pic { min-height: 0; aspect-ratio: 4 / 3; }
  .lg-wide { grid-column: auto; }
  .sm-wide { grid-column: span 2; }
}

/* ---- phones ---- */
@media (max-width: 640px) {
  :root { --gl-blur: blur(10px) saturate(150%); }
  .bento-page { padding: calc(var(--nav-h) + 16px) 14px 36px; }
  .bento { gap: 12px; grid-auto-rows: auto; }
  .gl { border-radius: 18px; }
  .b-hero { padding: 26px 20px 24px; }
  .b-hero h1 { font-size: 34px; margin-bottom: 14px; }
  [dir="rtl"] .b-hero h1 { font-size: 32px; }
  .hero-badge { margin-bottom: 16px; }
  /* the two buttons straight under the headline, so "Explore Gallery" is on the first screen (owner, 13 Nov 2026) */
  .b-hero .hero-btns { order: 1; width: 100%; margin: 0 0 18px; flex-direction: column; }
  .b-hero .hero-btns .btn { width: 100%; min-width: 0; }
  .b-hero .hero-what { order: 2; font-size: 16px; }
  .b-hero .hero-sub { order: 3; font-size: 15px; margin-bottom: 0; }
  .b-trust { grid-column: span 2; flex-direction: row; align-items: center; justify-content: flex-start; padding: 14px 16px; min-height: 0; }
  .b-trust p { font-size: 15px; }
  .b-sec { padding: 16px; min-height: 124px; }
  .b-sec .ttl span.t { font-size: 17px; }
  .b-sec .go { position: absolute; top: 16px; inset-inline-end: 16px; width: 30px; height: 30px; }
  .b-sec .ttl span.t { text-wrap: balance; }
  .aotw-n { font-size: 18px; }
}
@media (max-width: 360px) {
  .b-hero h1 { font-size: 30px; }
  .b-sec .ttl span.t { font-size: 16px; }
}
@media (prefers-reduced-motion: reduce) {
  a.gl, a.gl:hover, .b-sec .go { transition: none; transform: none; }
  .hero-bg video { display: none; }
}
</style>
</head>
<body class="s-b">
<?php require_once __DIR__ . '/studio_lib.php'; ?>
<?php
/* the painting: the still frame first (its tiny copy shows at once), then the looping video, inside its own clear box
   in the grid. Behind the whole page: the soft colour shapes. */
ob_start(); ?>
<div class="hero-bg" aria-hidden="true">
  <style>.hero-bg{background:url(<?= OW_HERO_TINY['blue'] ?>) 66% 50% / cover no-repeat}</style>
  <picture class="hero-still">
    <source type="image/avif" srcset="<?= owAsset('assets/img/hero/hero-wide.avif') ?>"><source type="image/webp" srcset="<?= owAsset('assets/img/hero/hero-wide.webp') ?>">
    <img src="<?= owAsset('assets/img/hero/hero-wide.jpg') ?>" alt="A hand painting white dots on a colourful canvas · يد ترسم نقاطاً بيضاء على لوحة ملونة" width="1280" height="720" fetchpriority="high" decoding="sync">
  </picture>
  <video id="vid" class="bg-now" autoplay muted loop playsinline preload="none" data-src="<?= owAsset('assets/video/hero-wide.mp4') ?>"></video>
</div>
<?php $owPainting = ob_get_clean();
echo '<div class="ow-blobs" aria-hidden="true"></div>';

/* the site's sections, with the same names as the top menu; Art Institutes only while it is in the menu */
$owInstOnHome = false; try { $owInstOnHome = owPartOn('courses'); } catch (Throwable $e) { $owInstOnHome = false; }
$owSecs = [
  ['marketplace.php', 'Gallery', 'المعرض', '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 16l5-5 4 4 3-3 6 6"/><circle cx="15.5" cy="9" r="1.5"/>'],
  ['explore.php', 'Art Styles', 'أساليب الرسم', '<path d="M12 3a9 9 0 1 0 0 18c1.1 0 1.6-.8 1.6-1.6 0-.5-.2-.9-.5-1.2-.3-.3-.5-.7-.5-1.2 0-.9.7-1.6 1.6-1.6H16a5 5 0 0 0 5-5c0-4-4-7.4-9-7.4z"/><circle cx="7.5" cy="11" r="1"/><circle cx="10" cy="7" r="1"/><circle cx="14.5" cy="7" r="1"/>'],
  ['art_map.php', 'Art Map', 'وجهات', '<path d="M12 21s-6-5.3-6-10.5a6 6 0 0 1 12 0C18 15.7 12 21 12 21z"/><circle cx="12" cy="10.5" r="2.2"/>'],
];
if ($owInstOnHome) $owSecs[] = ['institutes.php', 'Art Institutes', 'المعاهد الفنية', '<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c2 2 10 2 12 0v-5"/><path d="M22 9v6"/>'];
$owSecs[] = ['articles_list.php?type=event', 'Art Events', 'الملتقيات الفنية', '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/><path d="M8 14h3v3H8z"/>'];
$owSecs[] = ['articles_list.php?type=art_education', 'Art Education', 'التثقيف الفني', '<path d="M2 5h6a4 4 0 0 1 4 4v11a3 3 0 0 0-3-3H2z"/><path d="M22 5h-6a4 4 0 0 0-4 4v11a3 3 0 0 1 3-3h7z"/>'];
/* fill every row of the grid: some section boxes take two places when the count needs it */
$nSec = count($owSecs);
$cells = 6 + 4 + ($aotw ? 4 : 0) + 3 + $nSec;
$lgExtra = (4 - $cells % 4) % 4;
$smExtra = $nSec % 2;
?>

<?php include __DIR__ . '/nav.php'; ?>

<main class="bento-page">
  <div class="bento">
    <section class="gl b-hero">
      <span class="hero-badge"><?= owBi('✦ A Real Touch', '✦ لمسة حقيقية') ?></span>
      <h1><?= owBi('Everything here started with real hands', 'كل ما تراه هنا، بدأ بيدٍ حقيقية') ?></h1>
      <p class="hero-what"><?= owBi('Oweili is an online gallery where you buy original handmade paintings straight from real artists.', 'أويلي معرض إلكتروني تقتني فيه لوحات أصلية مرسومة باليد، مباشرة من فنانين حقيقيين.') ?></p>
      <p class="hero-sub"><?= owBi('Every piece here was made by real hands. And you can see how.', 'كل قطعة هنا صنعتها يد حقيقية. وتستطيع أن ترى كيف.') ?></p>
      <div class="hero-btns">
        <a class="btn btn-primary" href="marketplace.php"><?= owBi('Explore Gallery', 'استكشف المعرض') ?></a>
        <a class="btn btn-secondary" href="register_artist.php"><?= owBi('Join as Artist', 'انضم كفنان') ?></a>
      </div>
    </section>
    <div class="gl b-media"><?= $owPainting ?></div>
    <?php if ($aotw): $aImg = $aotw['art'] ? owImgSrc((string) $aotw['art']['image_url']) : ''; if ($aImg === '') $aImg = owImgSrc((string) ($aotw['profile_picture'] ?? '')); ?>
    <a class="gl b-aotw" href="artist_dashboard.php?id=<?= (int) $aotw['id'] ?>">
      <span class="aotw-pic"><?php if ($aImg !== ''): ?><img src="<?= e(owImgSized($aImg, 800)) ?>" alt="<?= e($aotw['artist_en'] . ' · ' . $aotw['artist_ar']) ?>"<?= owImgFallbackAttrs($aImg, owImgSized($aImg, 800)) ?>><?php endif; ?></span>
      <span class="aotw-txt"><span><span class="aotw-k"><?= owBi('Artist of the week', 'فنان الأسبوع') ?></span>
        <span class="aotw-n"><?= owBiName((string) $aotw['artist_en'], (string) $aotw['artist_ar']) ?><?php if ((int) $aotw['verified'] === 1): ?> <?= owVerifiedBadge(true) ?><?php endif; ?></span></span>
        <span class="aotw-go"><?= owBi('Meet the artist →', 'تعرّف على الفنان ←') ?></span></span>
    </a>
    <?php endif; ?>
    <div class="gl b-trust"><span class="ico"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="14" width="5" height="7" rx="1"/><rect x="9.5" y="9" width="5" height="12" rx="1"/><rect x="16" y="3" width="5" height="18" rx="1"/></svg></span><p><?= owBi('Proof of process: 25%, 50%, 100%', 'إثبات مراحل العمل: 25% و50% و100%') ?></p></div>
    <div class="gl b-trust"><span class="ico"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3M21 14v.01M14 21h7v-4"/></svg></span><p><?= owBi("Certificate of authenticity with\u{00A0}QR", "شهادة أصالة برمز\u{00A0}QR") ?></p></div>
    <div class="gl b-trust"><span class="ico"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.5l7.5 3v6c0 4.6-3.2 8.6-7.5 10-4.3-1.4-7.5-5.4-7.5-10v-6z"/><path d="M8.5 12l2.5 2.5 4.5-4.5"/></svg></span><p><?= owBi('Every artist reviewed', 'نراجع كل فنان') ?></p></div>
    <?php foreach ($owSecs as $i => $s): $cls = ($i < $lgExtra ? ' lg-wide' : '') . ($i < $smExtra ? ' sm-wide' : ''); ?>
    <a class="gl b-sec<?= $cls ?>" href="<?= e($s[0]) ?>">
      <span class="ico"><svg viewBox="0 0 24 24" aria-hidden="true"><?= $s[3] ?></svg></span>
      <span class="ttl"><span class="t"><?= owBi($s[1], $s[2]) ?></span><span class="go" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span></span>
    </a>
    <?php endforeach; ?>
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
