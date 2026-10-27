<?php
/* =====================================================================
 *  index.php — Oweili homepage: one full-screen hero, nothing else.
 *  Calm luxury-gallery hero: faint video (bg.mp4) under a strong veil,
 *  small badge, light headline, one short line and two navy buttons. Text is printed in both languages
 *  (bi-en / bi-ar); the shared menu's language switch shows the one that
 *  matches the page direction. No database tables used.
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/artwork_lib.php';   // owBi(): one text in both languages
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Oweili · Everything here started with human hands</title>
<meta name="description" content="Original art. Real artists. Verified. فن أصيل. فنانون حقيقيون. موثّق.">
<meta property="og:title" content="Oweili · Everything here started with human hands · كل ما تراه هنا، بدأ بيدٍ حقيقية">
<meta property="og:description" content="Original art. Real artists. Verified.">
<meta property="og:type" content="website">
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="/assets/css/oweili.css?v=<?= @filemtime(__DIR__ . '/assets/css/oweili.css') ?>">
<link rel="stylesheet" href="/assets/css/nav.css?v=<?= @filemtime(__DIR__ . '/assets/css/nav.css') ?>">
<style>
/* Homepage — a calm, white gallery entrance. One centered block:
   badge, headline, one short line, two navy buttons. Nothing else.
   Every color comes from oweili.css (light and dark mode). */
html, body { height: 100%; }
body { overflow: hidden; }

/* the video is barely there; a strong veil keeps the page mostly white (or navy) */
.hero-bg { position: fixed; inset: 0; z-index: 0; overflow: hidden; background: var(--color-bg); }
.hero-bg video { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity 1.6s ease; }
.hero-bg video.on { opacity: var(--hero-video-opacity); }
.hero-shade { position: absolute; inset: 0; background: var(--hero-shade); pointer-events: none; }

.hero {
  position: relative; z-index: 2;
  height: 100vh; height: 100svh;
  display: flex; align-items: center; justify-content: center;
  padding: var(--nav-h) var(--space-md) 0;
  text-align: center;
}
.hero-inner { max-width: 920px; display: flex; flex-direction: column; align-items: center; }

.hero-badge {
  display: inline-block; margin-bottom: 32px; padding: 7px 18px;
  border: 1px solid rgba(var(--rgb-ink),0.14); border-radius: var(--radius-pill);
  font-size: 11px; font-weight: 500; letter-spacing: 0.2em; text-transform: uppercase; line-height: 1.4;
  color: var(--hero-ink);
}
[dir="rtl"] .hero-badge { letter-spacing: 0.08em; }

.hero h1 {
  margin: 0 0 20px;
  font-size: 64px; font-weight: 200; letter-spacing: -0.03em; line-height: 1.2;
  color: var(--hero-ink);
}
[dir="rtl"] .hero h1 { letter-spacing: 0; }

.hero-sub {
  margin: 0 0 48px;
  font-size: 16px; letter-spacing: 0.05em; line-height: 1.6;
  color: var(--color-light);
}
[dir="rtl"] .hero-sub { letter-spacing: 0; }

.hero-btns { display: flex; gap: 16px; justify-content: center; }
.hero-btn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 180px; height: 52px; padding: 0 12px; border-radius: 4px;
  border: 1px solid var(--hero-ink);
  font-family: inherit; font-size: 13px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
  text-decoration: none; white-space: nowrap;
  transition: opacity var(--ease), background var(--ease);
}
[dir="rtl"] .hero-btn { letter-spacing: 0; font-size: 14px; }
.hero-btn-primary { background: var(--hero-ink); color: var(--hero-on-ink); }
.hero-btn-primary:hover { opacity: 0.85; color: var(--hero-on-ink); }
.hero-btn-secondary { background: transparent; color: var(--hero-ink); }
.hero-btn-secondary:hover { background: rgba(var(--rgb-ink),0.06); color: var(--hero-ink); }
.hero-btn:focus-visible { outline: 2px solid var(--hero-ink); outline-offset: 3px; }
.hero-btn:active { transform: translateY(1px); }

/* a slow, quiet fade-in (CSS only) */
@keyframes ow-fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
.fade { animation: ow-fadeUp 1.1s cubic-bezier(0.22,1,0.36,1) both; }
.fade-1 { animation-delay: 0.1s; }
.fade-2 { animation-delay: 0.25s; }
.fade-3 { animation-delay: 0.4s; }
.fade-4 { animation-delay: 0.55s; }

@media (max-width: 768px) {
  .hero h1 { font-size: 38px; }
}
@media (max-width: 420px) {
  .hero-btns { width: 100%; max-width: 376px; }
  .hero-btn { flex: 1 1 0; width: auto; min-width: 0; }
}
@media (prefers-reduced-motion: reduce) {
  .fade { animation: none; }
  .hero-bg video { display: none; }
}
</style>
</head>
<body>
<div class="hero-bg" aria-hidden="true">
  <video id="vid" autoplay loop muted playsinline preload="auto" src="bg.mp4"></video>
  <div class="hero-shade"></div>
</div>

<?php include __DIR__ . '/nav.php'; ?>

<main class="hero">
  <div class="hero-inner">
    <span class="hero-badge fade fade-1"><?= owBi('Oweili · Art', 'أويلي · فن') ?></span>
    <h1 class="fade fade-2"><?= owBi('Everything here started with human hands', 'كل ما تراه هنا، بدأ بيدٍ حقيقية') ?></h1>
    <p class="hero-sub fade fade-3"><?= owBi('Original art. Real artists. Verified.', 'فن أصيل. فنانون حقيقيون. موثّق.') ?></p>
    <div class="hero-btns fade fade-4">
      <a class="hero-btn hero-btn-primary" href="marketplace.php"><?= owBi('Explore Gallery', 'استكشف المعرض') ?></a>
      <a class="hero-btn hero-btn-secondary" href="register_artist.php"><?= owBi('Join as Artist', 'انضم كفنان') ?></a>
    </div>
  </div>
</main>

<script>
/* Language button in the shared menu: flip the page direction; nav.php then
   updates the menu words and the bi-en / bi-ar texts follow the direction. */
function tgl(){
  var h = document.documentElement, ar = h.getAttribute('dir') !== 'rtl';
  h.setAttribute('dir', ar ? 'rtl' : 'ltr');
  h.setAttribute('lang', ar ? 'ar' : 'en');
  document.title = ar ? 'أويلي · كل ما تراه هنا، بدأ بيدٍ حقيقية' : 'Oweili · Everything here started with human hands';
  try { localStorage.setItem('lang', ar ? 'ar' : 'en'); } catch (e) {}
}
if (document.documentElement.getAttribute('dir') === 'rtl') { document.title = 'أويلي · كل ما تراه هنا، بدأ بيدٍ حقيقية'; }

/* The video (and its shade) fade in once it can play */
(function(){
  var v = document.getElementById('vid');
  if (!v) return;
  var on = function(){ v.classList.add('on'); };
  if (v.readyState >= 3) on();   /* already ready (e.g. saved in the browser) */
  v.addEventListener('canplay', on, { once: true });
  v.addEventListener('playing', on, { once: true });
  var p = v.play(); if (p && p.catch) p.catch(function(){});
})();
</script>
</body>
</html>
