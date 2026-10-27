<?php
/* =====================================================================
 *  explore.php — Explore Art Styles: a calm, magazine-like reference.
 *  A grid of 16 style cards (filter: All / Traditional / Digital /
 *  Mixed Media / Modern). A card opens a window with the full story,
 *  techniques, signs of a human hand, famous artists, a collector's guide
 *  and a link to that style in the Gallery.
 *  Content: assets/js/art_styles.js (the single source). Drawing and the
 *  window: assets/js/explore.js. Read only — no forms, no database.
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/artwork_lib.php';   // owBi(): one text in both languages
$v = fn(string $f) => @filemtime(__DIR__ . $f);
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" data-title-en="Explore Art Styles · Oweili" data-title-ar="استكشف أساليب الرسم · أويلي">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Explore Art Styles · Oweili</title>
<meta name="description" content="Learn the story behind every style. Know what you are looking at. تعرّف على قصة كل أسلوب. اعرف ما تراه.">
<meta property="og:title" content="Explore Art Styles · استكشف أساليب الرسم · Oweili">
<meta property="og:description" content="Learn the story behind every style. Know what you are looking at.">
<meta property="og:type" content="website">
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="/assets/css/oweili.css?v=<?= $v('/assets/css/oweili.css') ?>">
<link rel="stylesheet" href="/assets/css/nav.css?v=<?= $v('/assets/css/nav.css') ?>">
<link rel="stylesheet" href="/assets/css/discover.css?v=<?= $v('/assets/css/discover.css') ?>">
<script src="/assets/js/discover.js?v=<?= $v('/assets/js/discover.js') ?>"></script>
<script src="/assets/js/art_styles.js?v=<?= $v('/assets/js/art_styles.js') ?>"></script>
<script src="/assets/js/explore.js?v=<?= $v('/assets/js/explore.js') ?>"></script>
<style>
/* Explore Art Styles — layout only. Colors, type and buttons come from oweili.css. */
.ex-head { margin-bottom: var(--space-lg); }
.ex-head h1 { font-weight: 300; letter-spacing: -0.02em; }
[dir="rtl"] .ex-head h1 { letter-spacing: 0; }
.ex-head p { color: var(--color-body); }

/* filter bar: small refined pills */
.sc-filters { display: flex; flex-wrap: wrap; justify-content: center; gap: var(--space-xs); margin: 0 0 var(--space-sm); }
.pill-f {
  min-height: 36px; padding: 0 16px; border-radius: var(--radius-pill);
  border: 1px solid var(--color-border-strong); background: transparent; color: var(--color-dark);
  font-family: inherit; font-size: 13px; font-weight: 600; cursor: pointer;
  transition: background var(--ease), color var(--ease), border-color var(--ease);
}
.pill-f:hover { background: var(--color-hover-light); }
.pill-f.on { background: var(--color-dark); border-color: var(--color-dark); color: var(--color-on-dark); }
.pill-f:focus-visible { outline: 2px solid var(--color-accent); outline-offset: 2px; }
.sc-count { text-align: center; font-size: var(--fs-caption); color: var(--color-body); margin: 0 0 var(--space-lg); }

/* the grid: square cards, all the same size */
.sc-grid { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--space-md); }
.sc {
  width: 100%; aspect-ratio: 1 / 1; display: flex; flex-direction: column; padding: 0; overflow: hidden;
  background: var(--color-menu); border: 1px solid var(--color-border); border-radius: var(--radius-card);
  box-shadow: var(--shadow-card); cursor: pointer; font-family: inherit; text-align: center; color: inherit;
  transition: transform 0.35s cubic-bezier(0.22,1,0.36,1), box-shadow 0.35s ease, border-color 0.35s ease;
}
.sc:hover { transform: translateY(-4px); box-shadow: var(--shadow-card-hover); border-color: var(--color-primary); }
.sc:active { transform: translateY(-1px); }
.sc:focus-visible { outline: 2px solid var(--color-accent); outline-offset: 3px; }
.sc-img { flex: 1 1 auto; min-height: 0; display: block; overflow: hidden; }
.sc-img .ill { width: 100%; height: 100%; display: block; transition: transform 0.6s ease; }
.sc:hover .sc-img .ill { transform: scale(1.04); }
.sc-body { flex: 0 0 auto; display: block; padding: var(--space-sm) var(--space-xs) 18px; }
.sc-name { display: block; font-size: 18px; font-weight: 600; line-height: 1.3; color: var(--color-dark); }
.sc-feel { display: block; margin-top: 4px; font-size: 12px; font-weight: 500; letter-spacing: 0.2em; text-transform: uppercase; color: var(--color-body); }
[dir="rtl"] .sc-feel { letter-spacing: 0; font-size: 13px; }

/* the drawn cover pictures (brand colors, follow light and dark mode) */
.ill .bgk { fill: var(--color-surface-2); }
.ill .pk { fill: var(--color-primary); }
.ill .gb { fill: var(--color-neutral); }
.ill .ink { fill: var(--color-dark); }
.ill .ln { fill: none; stroke: var(--color-dark); }
.ill .st { fill: none; stroke-linecap: round; stroke-linejoin: round; }
.ill .st.pk { stroke: var(--color-primary); }
.ill .st.gb { stroke: var(--color-neutral); }
.ill .st.ink { stroke: var(--color-dark); }
.ill .st.bgk { stroke: var(--color-surface-2); }
.ill .tx { fill: var(--color-dark); font-family: var(--font-sans); font-size: 13px; font-weight: 600; }
.ill .sgb { stop-color: var(--color-neutral); }
.ill .sbg { stop-color: var(--color-surface-2); }
.ill .sink { stop-color: var(--color-dark); }

/* closing note under the grid */
.ex-end { max-width: 620px; margin: var(--space-2xl) auto 0; text-align: center; }
.ex-end p { font-size: var(--fs-subtitle); line-height: 1.7; color: var(--color-body); margin: 0 0 var(--space-md); }
.ex-end .btns { display: flex; flex-wrap: wrap; justify-content: center; gap: var(--space-sm); }

/* ---- the style window ---- */
html.m-open { overflow: hidden; }
.m-dlg {
  width: min(720px, calc(100% - 32px)); max-width: 720px; max-height: calc(100vh - 48px); max-height: calc(100dvh - 48px);
  padding: 0; border: 1px solid var(--color-border); border-radius: var(--radius-card);
  background: var(--color-menu); color: var(--color-body); box-shadow: var(--shadow-card-hover); overflow: hidden;
}
.m-dlg[open] { display: flex; flex-direction: column; animation: m-in 0.4s cubic-bezier(0.22,1,0.36,1) both; }
.m-dlg::backdrop { background: rgba(var(--rgb-shadow, 35,49,74), 0.55); -webkit-backdrop-filter: blur(4px); backdrop-filter: blur(4px); animation: m-fade 0.3s ease both; }
@keyframes m-in { from { opacity: 0; transform: translateY(18px) scale(0.98); } to { opacity: 1; transform: none; } }
@keyframes m-fade { from { opacity: 0; } to { opacity: 1; } }
.m-body { overflow-y: auto; overscroll-behavior: contain; -webkit-overflow-scrolling: touch; }
.m-close {
  position: absolute; top: 12px; inset-inline-end: 12px; z-index: 3;
  width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center;
  border-radius: 50%; border: 1px solid var(--color-border-strong); background: var(--color-menu); color: var(--color-dark); cursor: pointer;
  transition: background var(--ease);
}
.m-close:hover { background: var(--color-surface-2); }
.m-close:focus-visible { outline: 2px solid var(--color-accent); outline-offset: 2px; }
.m-close svg { width: 18px; height: 18px; stroke: currentColor; stroke-width: 2; stroke-linecap: round; fill: none; }
.m-cover { height: 300px; overflow: hidden; background: var(--color-surface-2); }  /* the whole drawing shows, centered */
.m-cover .ill { width: 100%; height: 100%; display: block; }
.m-in { padding: var(--space-lg) 40px var(--space-xl); }
.m-kicker { margin: 0 0 var(--space-xs); font-size: 12px; font-weight: 600; letter-spacing: 0.2em; text-transform: uppercase; color: var(--color-body); }
.m-title { margin: 0 0 12px; font-size: 40px; font-weight: 300; letter-spacing: -0.02em; line-height: 1.2; color: var(--color-dark); }
.m-essence { margin: 0; font-size: 18px; line-height: 1.6; color: var(--color-body); }
[dir="rtl"] .m-kicker, [dir="rtl"] .m-title { letter-spacing: 0; }
[dir="rtl"] .m-kicker { font-size: 13px; }
.m-sec { margin-top: var(--space-lg); padding-top: var(--space-md); border-top: 1px solid var(--color-border); }
.m-h { margin: 0 0 12px; font-size: 13px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: var(--color-dark); line-height: 1.5; }
[dir="rtl"] .m-h { letter-spacing: 0; font-size: 15px; }
.m-story p { font-size: 16px; line-height: 1.75; margin: 0 0 12px; }
.m-story p:last-child { margin-bottom: 0; }
.m-hint { font-size: var(--fs-caption); color: var(--color-body); margin: 0 0 12px; }
.tchips { display: flex; flex-wrap: wrap; gap: var(--space-xs); }
.tchip {
  position: relative; min-height: 36px; padding: 0 14px; border-radius: var(--radius-pill);
  border: 1px solid var(--color-border-strong); background: transparent; color: var(--color-dark);
  font-family: inherit; font-size: 13px; font-weight: 600; cursor: help;
  transition: background var(--ease), border-color var(--ease);
}
.tchip:hover, .tchip.on { background: var(--color-tint-pink); border-color: var(--color-primary); }
.tchip:focus-visible { outline: 2px solid var(--color-accent); outline-offset: 2px; }
.ttip {
  position: absolute; bottom: calc(100% + 8px); inset-inline-start: 0; z-index: 4;
  width: max-content; max-width: 240px; padding: 8px 12px; border-radius: 8px;
  background: var(--color-dark); color: var(--color-on-dark); font-size: 13px; font-weight: 400; line-height: 1.5; text-align: start;
  opacity: 0; visibility: hidden; transform: translateY(4px); pointer-events: none; transition: opacity 0.2s ease, transform 0.2s ease;
}
@media (hover: hover) { .tchip:hover .ttip { opacity: 1; visibility: visible; transform: none; } }
.tdesc { min-height: 1.6em; margin: 12px 0 0; font-size: 15px; line-height: 1.6; color: var(--color-dark); }
.m-list { margin: 0; padding: 0; list-style: none; }
.m-signs .m-list, .m-guide .m-list { counter-reset: n; }
.m-signs li, .m-guide li { counter-increment: n; position: relative; padding-inline-start: 44px; margin-bottom: 14px; font-size: 16px; line-height: 1.65; }
.m-signs li::before, .m-guide li::before {
  content: counter(n); position: absolute; inset-inline-start: 0; top: 0;
  width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700; color: var(--color-dark); background: var(--color-tint-pink); border: 1px solid rgba(var(--rgb-primary),0.45);
}
.m-guide li::before { background: var(--color-tint-blue); border-color: var(--color-border-strong); }
.m-artists .m-list { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
.m-artists li { padding: 14px 16px; border: 1px solid var(--color-border); border-radius: 12px; background: var(--color-surface-2); }
.a-name { display: block; font-size: 16px; font-weight: 600; line-height: 1.4; color: var(--color-dark); }
.a-meta { display: block; margin-top: 2px; font-size: var(--fs-caption); color: var(--color-body); }
.m-cta { margin-top: var(--space-xl); text-align: center; }

@media (max-width: 1024px) { .sc-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 640px) {
  .sc-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
  .sc-body { padding: 12px 6px 14px; }
  .sc-name { font-size: 15px; }
  .sc-feel { font-size: 12px; letter-spacing: 0.12em; }
  .m-dlg { width: 100%; max-width: 100%; height: 100%; max-height: 100%; margin: 0; border: 0; border-radius: 0; }
  .m-body { height: 100%; }
  .m-cover { height: 240px; }
  .m-in { padding: var(--space-md) 20px var(--space-xl); }
  .m-title { font-size: 32px; }
  .m-artists .m-list { grid-template-columns: 1fr; }
}
@media (hover: none) { .ttip { display: none; } }
@media (prefers-reduced-motion: reduce) {
  .m-dlg[open], .m-dlg::backdrop { animation: none; }
  .sc, .sc-img .ill { transition: none; }
}
</style>
</head>
<body class="ow">

<?php include __DIR__ . '/nav.php'; ?>

<main>
  <div class="d-wrap">
    <header class="d-head ex-head reveal">
      <h1><?= owBi('Explore Art Styles', 'استكشف أساليب الرسم') ?></h1>
      <p><?= owBi('Learn the story behind every style. Know what you are looking at.', 'تعرّف على قصة كل أسلوب. اعرف ما تراه.') ?></p>
    </header>

    <nav class="sc-filters" id="sc-filters" aria-label="Filter styles · تصفية الأساليب"></nav>
    <p class="sc-count" id="sc-count" aria-live="polite"></p>
    <ul class="sc-grid" id="sc-grid"></ul>
    <noscript><p class="empty-state"><?= owBi('Please turn on JavaScript to explore the art styles.', 'يرجى تفعيل جافاسكربت لاستكشاف أساليب الرسم.') ?></p></noscript>

    <section class="ex-end reveal">
      <p><?= owBi('Every style here holds human patience and courage. When you collect on Oweili, you keep proof that a person dared to leave a mark.', 'كل أسلوب هنا يحمل صبر الإنسان وجرأته. حين تقتني من أويلي، تحتفظ بدليل على إنسان تجرّأ أن يترك أثراً.') ?></p>
      <div class="btns">
        <a class="btn btn-dark" href="marketplace.php"><?= owBi('Explore Gallery', 'استكشف المعرض') ?></a>
        <a class="btn btn-secondary" href="mailto:contact@oweili.com?subject=Art%20Style%20Suggestion"><?= owBi('Suggest a Style', 'اقترح أسلوباً') ?></a>
      </div>
    </section>
  </div>
</main>

<dialog class="m-dlg" id="style-dlg" aria-labelledby="m-title">
  <button type="button" class="m-close" aria-label="Close · إغلاق"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
  <div class="m-body" id="style-body"></div>
</dialog>

<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
