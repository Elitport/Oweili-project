<?php
/* =====================================================================
 *  index.php — Oweili homepage: one full-screen hero, nothing else.
 *  Video background (bg.mp4) with a soft dark shade, badge, headline,
 *  one-line subtitle and two buttons. Text is printed in both languages
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
<meta name="description" content="Every piece here was made by real hands. And you can see how. كل قطعة هنا صنعتها يد حقيقية. وتستطيع أن ترى كيف.">
<meta property="og:title" content="Oweili · Everything here started with human hands · كل ما تراه هنا، بدأ بيدٍ حقيقية">
<meta property="og:description" content="Every piece here was made by real hands. And you can see how.">
<meta property="og:type" content="website">
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/nav.css') ?>">
<style>
/* Homepage — one screen. The headline is the first thing to notice, the two
   buttons are the action (rule 30). Colors, fonts and buttons come from oweili.css. */
html, body { height: 100%; }
body { overflow-x: hidden; }

/* full-screen video with a soft dark shade so the text stays readable */
.hero-bg { position: fixed; inset: 0; z-index: 0; overflow: hidden; background: var(--color-bg); }
.hero-bg video { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity 1.6s ease; }
.hero-bg video.on { opacity: 1; }
.hero-shade { position: absolute; inset: 0; background: var(--hero-shade); opacity: 0; transition: opacity 1.6s ease; pointer-events: none; }
.hero-bg video.on + .hero-shade { opacity: 1; }

.hero {
  position: relative; z-index: 2;
  min-height: 100vh; min-height: 100svh;
  display: flex; align-items: center; justify-content: center;
  padding: calc(var(--nav-h) + var(--space-lg)) var(--space-sm) var(--space-xl);
  text-align: center;
}
.hero-inner { max-width: 880px; display: flex; flex-direction: column; align-items: center; }

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

/* gentle fade-in, CSS only: badge, then headline, subtitle and buttons */
@keyframes ow-fadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
.fade { animation: ow-fadeUp 0.9s cubic-bezier(0.22,1,0.36,1) both; }
.fade-1 { animation-delay: 0.05s; }
.fade-2 { animation-delay: 0.2s; }
.fade-3 { animation-delay: 0.35s; }
.fade-4 { animation-delay: 0.5s; }

@media (max-width: 768px) {
  .hero h1 { font-size: 36px; }
  .hero-sub { font-size: 16px; }
}
@media (max-width: 420px) {
  .hero-btns { flex-direction: column; width: 100%; max-width: 320px; }
  .hero-btns .btn { width: 100%; min-width: 0; }
}
@media (prefers-reduced-motion: reduce) {
  .fade { animation: none; }
  .hero-bg video { display: none; }
}
</style>
</head>
<body>
<div class="hero-bg" aria-hidden="true">
  <video id="vid" autoplay loop muted playsinline preload="auto" src="<?= owBase() ?>bg.mp4"></video>
  <div class="hero-shade"></div>
</div>

<?php include __DIR__ . '/nav.php'; ?>

<main class="hero">
  <div class="hero-inner">
    <span class="hero-badge fade fade-1"><?= owBi('✦ A Real Touch', '✦ لمسة حقيقية') ?></span>
    <h1 class="fade fade-2"><?= owBi('Everything here started with human hands', 'كل ما تراه هنا، بدأ بيدٍ حقيقية') ?></h1>
    <p class="hero-sub fade fade-3"><?= owBi('Every piece here was made by real hands. And you can see how.', 'كل قطعة هنا صنعتها يد حقيقية. وتستطيع أن ترى كيف.') ?></p>
    <div class="hero-btns fade fade-4">
      <a class="btn btn-primary" href="marketplace.php"><?= owBi('Explore Gallery', 'استكشف المعرض') ?></a>
      <a class="btn btn-secondary" href="register_artist.php"><?= owBi('Join as Artist', 'انضم كفنان') ?></a>
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
