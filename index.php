<?php
/* =====================================================================
 *  index.php — Oweili homepage in the calm style (owner, 19 Nov 2026).
 *  The painting video fills the hero at the top (still frame first; alone on slow connections,
 *  Data Saver or reduced motion) under a warm soft dark layer; the badge, the headline, the two
 *  lines, Explore Gallery and Join as Artist and the three trust points sit straight on it, centred.
 *  Under the hero, on the warm off-white page: "Artist of the week" (only when the admin picked one
 *  in admin_gallery.php; the artwork photo whole, never covered) and a simple row of links to the
 *  site's sections with the top menu's names (Art Institutes only while it is in the menu).
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
   Home page, calm style (owner, 19 Nov 2026): the shared calm layer (assets/css/oweili.css) gives the
   fonts, colours, the hero layer, the transparent menu and the cards; this block places the video hero,
   the trust line, Artist of the week and the row of section links.
   ===================================================================== */
html, body { margin: 0; }
body { overflow-x: hidden; }

/* ---- the hero: the painting video behind the centred text ---- */
.c-home .c-hero { position: relative; isolation: isolate; overflow: hidden; }
.c-home .c-hero .hero-bg { position: absolute; inset: 0; z-index: -2; overflow: hidden; background-color: #2B1F1C; }
.hero-bg video { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: 66% 50%; opacity: 0; transition: opacity 1.6s ease; }
.hero-bg video.on { opacity: 1; }
.hero-still, .hero-still img { position: absolute; inset: 0; width: 100%; height: 100%; }
.hero-still img { object-fit: cover; object-position: 66% 50%; display: block; }
.c-home .c-hero::after { content: ""; position: absolute; inset: 0; z-index: -1; pointer-events: none; background: var(--calm-veil); }
.c-home .hero-what { font-size: 21px; font-weight: 500; line-height: 1.6; max-width: 640px; margin: 0 auto 8px; color: #fff; }
.c-home .hero-sub { font-size: 17px; line-height: 1.7; max-width: 560px; margin: 0 auto 30px; color: rgba(255,255,255,0.92); }
.c-home .hero-btns { display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; }
.c-home .hero-btns .btn { min-width: 190px; white-space: nowrap; }
/* the three trust points: one small line under the buttons */
.c-trust { display: flex; flex-wrap: wrap; justify-content: center; gap: 6px 22px; margin: 26px auto 0; padding: 0; list-style: none; }
.c-trust li { display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 500; color: rgba(255,255,255,0.94); }
.c-trust .ico { display: inline-grid; place-items: center; width: 22px; height: 22px; }
.c-trust svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 1.6; stroke-linecap: round; stroke-linejoin: round; }
.c-trust p { margin: 0; color: inherit; font-size: inherit; font-weight: inherit; }

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
/* the site's sections: a simple row of links */
.c-secs { display: flex; flex-wrap: wrap; justify-content: center; gap: 14px; margin: 0; padding: 0; }
.c-secs .b-sec { display: inline-flex; align-items: center; gap: 12px; min-height: 56px; padding: 0 22px; border-radius: 999px; background: var(--color-card); border: 1px solid var(--calm-line); color: var(--color-dark); text-decoration: none; font-size: 16px; font-weight: 600; transition: border-color .2s ease, transform .2s ease; }
.c-secs .b-sec:hover { border-color: var(--color-dark); transform: translateY(-2px); }
.c-secs .b-sec:focus-visible { outline: 2px solid var(--color-accent); outline-offset: 3px; }
.c-secs .ico { display: inline-grid; place-items: center; }
.c-secs svg { width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 1.6; stroke-linecap: round; stroke-linejoin: round; }
.c-secs .go { display: inline-grid; place-items: center; }
.c-secs .go svg { width: 16px; height: 16px; stroke-width: 2; }
[dir="rtl"] .c-secs .go svg { transform: scaleX(-1); }
.c-secs .ttl { display: inline-flex; align-items: center; gap: 10px; }

@media (max-width: 1023px) {
  .b-aotw { grid-template-columns: 1fr; }
  .aotw-txt { padding: 26px; }
}
@media (max-width: 640px) {
  .c-home .hero-what { font-size: 18px; }
  .c-home .hero-sub { font-size: 16px; margin-bottom: 24px; }
  .c-home .hero-btns { flex-direction: column; align-items: stretch; width: 100%; max-width: 360px; margin: 0 auto; }
  .c-home .hero-btns .btn { width: 100%; min-width: 0; }
  .c-trust { flex-direction: column; align-items: center; gap: 8px; margin-top: 22px; }
  .c-below { padding: 48px 16px 64px; gap: 40px; }
  .c-secs { gap: 10px; }
  .c-secs .b-sec { width: 100%; justify-content: space-between; }
}
@media (prefers-reduced-motion: reduce) {
  .c-secs .b-sec, .c-secs .b-sec:hover { transition: none; transform: none; }
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
  <style>.hero-bg{background:url(<?= OW_HERO_TINY['blue'] ?>) 66% 50% / cover no-repeat}</style>
  <picture class="hero-still">
    <source type="image/avif" srcset="<?= owAsset('assets/img/hero/hero-wide.avif') ?>"><source type="image/webp" srcset="<?= owAsset('assets/img/hero/hero-wide.webp') ?>">
    <img src="<?= owAsset('assets/img/hero/hero-wide.jpg') ?>" alt="A hand painting white dots on a colourful canvas · يد ترسم نقاطاً بيضاء على لوحة ملونة" width="1280" height="720" fetchpriority="high" decoding="sync">
  </picture>
  <video id="vid" class="bg-now" autoplay muted loop playsinline preload="none" data-src="<?= owAsset('assets/video/hero-wide.mp4') ?>"></video>
</div>
<?php $owPainting = ob_get_clean();

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
?>

<?php include __DIR__ . '/nav.php'; ?>

<main class="c-home">
  <section class="c-hero">
    <?= $owPainting ?>
    <div class="c-hero-in">
      <span class="hero-badge"><?= owBi('✦ A Real Touch', '✦ لمسة حقيقية') ?></span>
      <h1><?= owBi('Everything here started with real hands', 'كل ما تراه هنا، بدأ بيدٍ حقيقية') ?></h1>
      <p class="hero-what"><?= owBi('Oweili is an online gallery where you buy original handmade paintings straight from real artists.', 'أويلي معرض إلكتروني تقتني فيه لوحات أصلية مرسومة باليد، مباشرة من فنانين حقيقيين.') ?></p>
      <p class="hero-sub"><?= owBi('Every piece here was made by real hands. And you can see how.', 'كل قطعة هنا صنعتها يد حقيقية. وتستطيع أن ترى كيف.') ?></p>
      <div class="hero-btns">
        <a class="btn btn-primary" href="marketplace.php"><?= owBi('Explore Gallery', 'استكشف المعرض') ?></a>
        <a class="btn btn-secondary" href="register_artist.php"><?= owBi('Join as Artist', 'انضم كفنان') ?></a>
      </div>
      <ul class="c-trust">
        <li><span class="ico"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="14" width="5" height="7" rx="1"/><rect x="9.5" y="9" width="5" height="12" rx="1"/><rect x="16" y="3" width="5" height="18" rx="1"/></svg></span><p><?= owBi('Proof of process: 25%, 50%, 100%', 'إثبات مراحل العمل: 25% و50% و100%') ?></p></li>
        <li><span class="ico"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3M21 14v.01M14 21h7v-4"/></svg></span><p><?= owBi("Certificate of authenticity with\u{00A0}QR", "شهادة أصالة برمز\u{00A0}QR") ?></p></li>
        <li><span class="ico"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.5l7.5 3v6c0 4.6-3.2 8.6-7.5 10-4.3-1.4-7.5-5.4-7.5-10v-6z"/><path d="M8.5 12l2.5 2.5 4.5-4.5"/></svg></span><p><?= owBi('Every artist reviewed', 'نراجع كل فنان') ?></p></li>
      </ul>
    </div>
  </section>
  <div class="c-below">
    <?php if ($aotw): $aImg = $aotw['art'] ? owImgSrc((string) $aotw['art']['image_url']) : ''; if ($aImg === '') $aImg = owImgSrc((string) ($aotw['profile_picture'] ?? '')); ?>
    <a class="card b-aotw" href="artist_dashboard.php?id=<?= (int) $aotw['id'] ?>">
      <span class="aotw-pic"><?php if ($aImg !== ''): ?><img src="<?= e(owImgSized($aImg, 800)) ?>" alt="<?= e($aotw['artist_en'] . ' · ' . $aotw['artist_ar']) ?>"<?= owImgFallbackAttrs($aImg, owImgSized($aImg, 800)) ?>><?php endif; ?></span>
      <span class="aotw-txt"><span><span class="aotw-k"><?= owBi('Artist of the week', 'فنان الأسبوع') ?></span>
        <span class="aotw-n"><?= owBiName((string) $aotw['artist_en'], (string) $aotw['artist_ar']) ?><?php if ((int) $aotw['verified'] === 1): ?> <?= owVerifiedBadge(true) ?><?php endif; ?></span></span>
        <span class="aotw-go"><?= owBi('Meet the artist →', 'تعرّف على الفنان ←') ?></span></span>
    </a>
    <?php endif; ?>
    <nav class="c-secs" aria-label="<?= e(owIsAr() ? 'أقسام أويلي' : 'Oweili sections') ?>">
    <?php foreach ($owSecs as $s): ?>
    <a class="b-sec" href="<?= e($s[0]) ?>">
      <span class="ico"><svg viewBox="0 0 24 24" aria-hidden="true"><?= $s[3] ?></svg></span>
      <span class="ttl"><span class="t"><?= owBi($s[1], $s[2]) ?></span><span class="go" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span></span>
    </a>
    <?php endforeach; ?>
    </nav>
  </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>

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
