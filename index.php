<?php
/* =====================================================================
 *  index.php — Oweili homepage
 *  Sections: hero · how it works · latest verified artworks ·
 *            join the community · discover the art world
 *  Text is printed in both languages (bi-en / bi-ar); the shared menu's
 *  language switch shows the one that matches the page direction.
 *  Tables: artworks, users, artwork_stages (stage photos, if set up)
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/artwork_lib.php';

/* ---- Latest verified artworks: 8 newest approved, available pieces ---- */
$featured = [];
$sqlBase = "SELECT a.id, a.title_en, a.title_ar, a.price, a.image_url, a.artist_id,
                   u.full_name_en, u.full_name_ar%s
            FROM artworks a
            JOIN users u ON u.id = a.artist_id
            WHERE a.is_approved = 1 AND a.status IN ('available','auction')%s
            ORDER BY a.created_at DESC, a.id DESC
            LIMIT :lim";
try {
    // Hide work by banned artists and show the artist's chosen name (columns from migrations.sql / profiles_setup.sql)
    $st = getDB()->prepare(sprintf($sqlBase, ', u.artist_name', owNotDraftSql('a') . ' AND u.is_banned = 0'));
    $st->bindValue(':lim', 8, PDO::PARAM_INT);
    $st->execute();
    $featured = $st->fetchAll();
} catch (Throwable $e) {
    try {   // older database without those columns
        $st = getDB()->prepare(sprintf($sqlBase, '', owNotDraftSql('a')));
        $st->bindValue(':lim', 8, PDO::PARAM_INT);
        $st->execute();
        $featured = $st->fetchAll();
    } catch (Throwable $e2) {
        $featured = [];   // the warm empty message is shown instead
    }
}
$featStages = owGetStagesFor(array_column($featured, 'id'));

/* Small helper: one link/button label in both languages */
function hb(string $en, string $ar): string { return owBi($en, $ar); }
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Oweili · Where Human Hands Create Art</title>
<meta name="description" content="Oweili is the home of real art — made by real artists, verified by proof, trusted by collectors worldwide. أويلي هو بيت الفن الحقيقي — يصنعه فنانون حقيقيون، موثّق بالدليل.">
<meta property="og:title" content="Oweili · Where Human Hands Create Art · حيث تصنع اليد البشرية الفن">
<meta property="og:description" content="Real art by real artists, with proof of every stage of creation.">
<meta property="og:type" content="website">
<link rel="stylesheet" href="/assets/css/oweili.css?v=<?= @filemtime(__DIR__ . '/assets/css/oweili.css') ?>">
<link rel="stylesheet" href="/assets/css/nav.css?v=<?= @filemtime(__DIR__ . '/assets/css/nav.css') ?>">
<link rel="stylesheet" href="/assets/css/proof.css?v=<?= @filemtime(__DIR__ . '/assets/css/proof.css') ?>">
<style>
/* Homepage layout only — colors, type, buttons, cards and badges come from oweili.css */
html{scroll-behavior:smooth;}
main{position:relative;z-index:2;}
section{padding:var(--section-pad) 0;}
.sec-title{font-size:var(--fs-section);text-align:center;margin:0 0 var(--space-sm);}
.sec-sub{font-size:var(--fs-subtitle);color:var(--color-body);text-align:center;max-width:640px;margin:0 auto var(--space-xl);}
.btn:focus-visible,.aw-card a:focus-visible,.disc-card:focus-visible,.scroll-cue:focus-visible{outline:2px solid var(--color-accent);outline-offset:3px;}

/* 1 — hero: the headline is the one thing to notice first, then the two actions */
.hero{min-height:calc(100vh - var(--nav-h));min-height:calc(100svh - var(--nav-h));display:flex;align-items:center;padding:calc(var(--nav-h) + var(--space-xl)) 0 var(--space-2xl);}
.hero-inner{text-align:center;max-width:820px;margin:0 auto;}
.hero-badge{display:inline-flex;align-items:center;gap:var(--space-xs);padding:6px 14px;border-radius:var(--radius-pill);background:#fff;border:1px solid rgba(0,102,255,0.2);font-size:var(--fs-label);font-weight:700;color:var(--color-dark);margin-bottom:var(--space-md);}
.hero-badge i{width:8px;height:8px;border-radius:50%;background:var(--color-accent);}
.hero h1{font-size:var(--fs-hero);letter-spacing:-0.03em;line-height:1.1;margin:0 0 var(--space-md);}
[dir="rtl"] .hero h1{line-height:1.3;}
.hero h1 em{font-style:normal;color:var(--color-primary);}
.hero-sub{font-size:var(--fs-subtitle);color:var(--color-body);max-width:640px;margin:0 auto var(--space-lg);}
.hero-btns{display:flex;flex-wrap:wrap;gap:var(--space-sm);justify-content:center;}
.scroll-cue{display:inline-flex;flex-direction:column;align-items:center;gap:6px;margin-top:var(--space-xl);font-size:var(--fs-label);font-weight:600;color:var(--color-body);text-decoration:none;}
.scroll-cue:hover{color:var(--color-dark);}
.scroll-cue svg{width:20px;height:20px;stroke:currentColor;fill:none;stroke-width:2;}

/* 2 — how it works */
.steps{display:grid;grid-template-columns:repeat(3,1fr);gap:var(--space-md);}
.step{text-align:center;}
.step-icon{width:56px;height:56px;border-radius:var(--radius-card);display:inline-flex;align-items:center;justify-content:center;background:var(--color-tint-blue);color:var(--color-accent);margin-bottom:var(--space-sm);}
.step-icon svg{width:28px;height:28px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}
.step-num{display:block;margin-bottom:var(--space-xs);}
.step h3{font-size:var(--fs-card);margin:0 0 var(--space-xs);}
.step p{margin:0;}

/* 3 — latest verified artworks: 4 columns desktop, 2 tablet, 1-2 phone */
.art-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:var(--space-md);}
.sec-foot{text-align:center;margin-top:var(--space-lg);}

/* 4 — join */
.join{display:grid;grid-template-columns:1fr 1fr;gap:var(--space-md);}
.join-card{padding:var(--space-lg);display:flex;flex-direction:column;align-items:flex-start;gap:var(--space-sm);}
.join-card h3{font-size:var(--fs-card);margin:0;}
.join-card p{margin:0 0 var(--space-xs);}

/* 5 — discover */
.disc{display:grid;grid-template-columns:repeat(3,1fr);gap:var(--space-md);}
.disc-card{display:flex;align-items:center;gap:var(--space-sm);text-decoration:none;color:var(--color-dark);}
.disc-card:hover{color:var(--color-dark);}
.disc-card svg.ic{width:44px;height:44px;flex-shrink:0;padding:10px;border-radius:12px;background:var(--color-tint-blue);stroke:var(--color-accent);fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}
.disc-card b{display:block;font-size:var(--fs-subtitle);}
.disc-card span.small{display:block;font-size:var(--fs-caption);color:var(--color-body);margin-top:2px;}
.disc-card .arrow{margin-inline-start:auto;font-size:20px;color:var(--color-accent);}
[dir="rtl"] .disc-card .arrow{transform:scaleX(-1);}

/* rise-up on scroll (only when JavaScript is on, so content is never hidden without it) */
.js .reveal{opacity:0;transform:translateY(22px);}
.js .reveal.in{animation:ow-riseUp 0.9s cubic-bezier(0.22,1,0.36,1) both;}

@media (max-width:1024px){.art-grid{grid-template-columns:repeat(2,1fr);}.disc{grid-template-columns:1fr;max-width:620px;margin:0 auto;}}
@media (max-width:768px){
  .steps{grid-template-columns:1fr;}
  .art-grid{gap:var(--space-sm);}
  .join{grid-template-columns:1fr;}
  .join-card{padding:var(--space-md);}
}
@media (max-width:360px){.art-grid{grid-template-columns:1fr;}}
@media (prefers-reduced-motion:reduce){
  html{scroll-behavior:auto;}
  .js .reveal{opacity:1;transform:none;}
  .js .reveal.in{animation:none;}
}
</style>
<script>document.documentElement.classList.add('js');</script>
</head>
<body class="ow">
<div class="bg-wrap" aria-hidden="true">
  <video class="bg-video" id="vid" autoplay loop muted playsinline src="bg.mp4"></video>
</div>
<div class="veil" aria-hidden="true"></div>

<?php include __DIR__ . '/nav.php'; ?>

<main>
  <!-- 1 — HERO -->
  <section class="hero" id="top">
    <div class="container hero-inner reveal">
      <span class="hero-badge"><i aria-hidden="true"></i><?= hb('Real artists · Real art · Verified by proof', 'فنانون حقيقيون · فن حقيقي · موثّق بالدليل') ?></span>
      <h1><span class="bi-en" lang="en">Where Human Hands <em>Create Art</em></span><span class="bi-ar" lang="ar">حيث تصنع <em>اليد البشرية</em> الفن</span></h1>
      <p class="hero-sub"><?= hb('Oweili is the home of real art — made by real artists, verified by proof, trusted by collectors worldwide.', 'أويلي هو بيت الفن الحقيقي — يصنعه فنانون حقيقيون، موثّق بالدليل، يثق به المقتنون حول العالم.') ?></p>
      <div class="hero-btns">
        <a class="btn btn-primary" href="marketplace.php"><?= hb('Explore Artworks', 'استكشف الأعمال') ?></a>
        <a class="btn btn-secondary" href="register_artist.php"><?= hb('Join as Artist', 'انضم كفنان') ?></a>
      </div>
      <a class="scroll-cue" href="#how">
        <?= hb('How it works', 'كيف يعمل') ?>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
      </a>
    </div>
  </section>

  <!-- 2 — HOW IT WORKS -->
  <section id="how">
    <div class="container">
      <h2 class="sec-title reveal"><?= hb('Art You Can Trust', 'فن يمكنك الوثوق به') ?></h2>
      <p class="sec-sub reveal"><?= hb('Three simple steps stand behind every artwork on Oweili.', 'ثلاث خطوات بسيطة وراء كل عمل فني على أويلي.') ?></p>
      <div class="steps">
        <div class="card step reveal">
          <span class="step-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h3l2-3h6l2 3h3a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1z"/><circle cx="12" cy="13" r="4"/></svg></span>
          <span class="step-num t-eyebrow"><?= hb('STEP 1', 'الخطوة 1') ?></span>
          <h3><?= hb('Artists photograph every stage', 'الفنانون يصوّرون كل مرحلة') ?></h3>
          <p><?= hb('From first stroke to finished piece — every artwork comes with proof of creation.', 'من أول ضربة ريشة حتى اللمسة الأخيرة — كل عمل فني يأتي مع دليل على خلقه.') ?></p>
        </div>
        <div class="card step reveal">
          <span class="step-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M8.5 12.5l2.5 2.5 4.5-5"/></svg></span>
          <span class="step-num t-eyebrow"><?= hb('STEP 2', 'الخطوة 2') ?></span>
          <h3><?= hb('Our team verifies the work', 'فريقنا يتحقق من العمل') ?></h3>
          <p><?= hb('Every artwork is reviewed by our team before it goes live.', 'كل عمل فني يراجعه فريقنا قبل نشره.') ?></p>
        </div>
        <div class="card step reveal">
          <span class="step-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/></svg></span>
          <span class="step-num t-eyebrow"><?= hb('STEP 3', 'الخطوة 3') ?></span>
          <h3><?= hb('Collectors buy with confidence', 'المقتنون يشترون بثقة') ?></h3>
          <p><?= hb('See the full creation journey before you buy. Real art, real artists.', 'شاهد رحلة الإبداع كاملة قبل الشراء. فن حقيقي، فنانون حقيقيون.') ?></p>
        </div>
      </div>
    </div>
  </section>

  <!-- 3 — LATEST VERIFIED ARTWORKS -->
  <section id="latest">
    <div class="container">
      <h2 class="sec-title reveal"><?= hb('Latest Verified Artworks', 'أحدث الأعمال الموثّقة') ?></h2>
      <p class="sec-sub reveal"><?= hb('Fresh from our artists’ studios, reviewed by our team.', 'جديد من مراسم فنانينا، بعد مراجعة فريقنا.') ?></p>
      <?php if (!$featured): ?>
        <div class="card empty-state reveal">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3c-4.97 0-9 3.58-9 8 0 2.5 1.3 4.7 3.3 6.2L6 21l3.6-1.8c.8.2 1.6.3 2.4.3 4.97 0 9-3.58 9-8s-4.03-8-9-8z"/><circle cx="8" cy="11" r="1"/><circle cx="12" cy="11" r="1"/><circle cx="16" cy="11" r="1"/></svg>
          <?= hb('Our artists are preparing their first works. Check back soon.', 'فنانونا يجهّزون أعمالهم الأولى. عد قريباً.') ?>
        </div>
      <?php else: ?>
        <div class="art-grid">
          <?php foreach ($featured as $aw):
            $aid    = (int) $aw['id'];
            $stg    = $featStages[$aid] ?? [];
            $img    = isset($stg[100]) && owSafeStagePath((string) $stg[100]['photo_path']) ? $stg[100]['photo_path'] : $aw['image_url'];
            $tEn    = $aw['title_en'] !== '' ? $aw['title_en'] : $aw['title_ar'];
            $tAr    = $aw['title_ar'] !== '' ? $aw['title_ar'] : $aw['title_en'];
            $artistEn = trim((string) (($aw['artist_name'] ?? '') ?: $aw['full_name_en'] ?: $aw['full_name_ar']));
            $artistAr = trim((string) (($aw['artist_name'] ?? '') ?: $aw['full_name_ar'] ?: $aw['full_name_en']));
          ?>
          <article class="aw-card reveal">
            <a class="aw-img" href="marketplace.php" tabindex="-1" aria-hidden="true">
              <img loading="lazy" src="<?= e($img) ?>" alt="<?= e($tEn . ' · ' . $tAr) ?>">
            </a>
            <div class="aw-body">
              <a class="aw-title" href="marketplace.php"><?= hb($tEn, $tAr) ?></a>
              <a class="aw-artist" href="artist_dashboard.php?id=<?= (int) $aw['artist_id'] ?>"><?= hb('by ' . $artistEn, 'بواسطة ' . $artistAr) ?></a>
              <?php if (owHasJourney($stg)): ?>
                <span class="human-badge"><?= hb('✓ Human Art', '✓ فن بشري') ?></span>
              <?php endif; ?>
              <div class="aw-price"><?= e(number_format((float) $aw['price'])) ?> <?= hb('SAR', 'ر.س') ?></div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
        <div class="sec-foot reveal"><a class="btn btn-secondary" href="marketplace.php"><?= hb('See all artworks', 'شاهد كل الأعمال') ?></a></div>
      <?php endif; ?>
    </div>
  </section>

  <!-- 4 — JOIN THE COMMUNITY -->
  <section id="join">
    <div class="container">
      <div class="join">
        <div class="card join-card reveal">
          <span class="t-eyebrow"><?= hb('FOR ARTISTS', 'للفنانين') ?></span>
          <h3><?= hb('Are you an artist?', 'هل أنت فنان؟') ?></h3>
          <p><?= hb('Share your work, prove your craft, and reach collectors worldwide.', 'شارك أعمالك، أثبت حرفتك، وصل إلى مقتنين حول العالم.') ?></p>
          <a class="btn btn-primary" href="register_artist.php"><?= hb('Join as Artist', 'انضم كفنان') ?></a>
        </div>
        <div class="card join-card reveal">
          <span class="t-eyebrow"><?= hb('FOR COLLECTORS', 'للمقتنين') ?></span>
          <h3><?= hb('Looking for real art?', 'تبحث عن فن حقيقي؟') ?></h3>
          <p><?= hb('Discover verified human artworks and buy directly from the artist.', 'اكتشف أعمالاً فنية بشرية موثّقة واشترِ مباشرة من الفنان.') ?></p>
          <a class="btn btn-secondary" href="marketplace.php"><?= hb('Start Exploring', 'ابدأ الاستكشاف') ?></a>
        </div>
      </div>
    </div>
  </section>

  <!-- 5 — DISCOVER THE ART WORLD -->
  <section id="discover">
    <div class="container">
      <h2 class="sec-title reveal"><?= hb('Discover the Art World', 'اكتشف عالم الفن') ?></h2>
      <p class="sec-sub reveal"><?= hb('Find a place to learn, the gatherings that bring artists together, and the stories behind every art style.', 'اعثر على مكان للتعلّم، وعلى الملتقيات التي تجمع الفنانين، وعلى قصص الأساليب الفنية.') ?></p>
      <div class="disc">
        <a class="card card-hover disc-card reveal" href="training.php">
          <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1 2.7 3 6 3s6-2 6-3v-5"/></svg>
          <span><b><?= hb('Art Institutes', 'المعاهد الفنية') ?></b><span class="small"><?= hb('Courses and schools in one place', 'الدورات والمعاهد في مكان واحد') ?></span></span>
          <span class="arrow" aria-hidden="true">→</span>
        </a>
        <a class="card card-hover disc-card reveal" href="articles_list.php?type=event">
          <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          <span><b><?= hb('Global Art Events', 'الملتقيات الفنية العالمية') ?></b><span class="small"><?= hb('Exhibitions and gatherings worldwide', 'معارض وملتقيات حول العالم') ?></span></span>
          <span class="arrow" aria-hidden="true">→</span>
        </a>
        <a class="card card-hover disc-card reveal" href="articles_list.php?type=art_education">
          <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
          <span><b><?= hb('Art Education', 'التثقيف الفني') ?></b><span class="small"><?= hb('The names, stories and history of art styles', 'أسماء الأساليب الفنية وقصصها وتاريخها') ?></span></span>
          <span class="arrow" aria-hidden="true">→</span>
        </a>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>

<script>
/* Language button in the shared menu: flip the page direction; nav.php then
   updates the menu words and the bi-en / bi-ar texts follow the direction. */
function tgl(){
  var h = document.documentElement, ar = h.getAttribute('dir') !== 'rtl';
  h.setAttribute('dir', ar ? 'rtl' : 'ltr');
  h.setAttribute('lang', ar ? 'ar' : 'en');
  document.title = ar ? 'أويلي · حيث تصنع اليد البشرية الفن' : 'Oweili · Where Human Hands Create Art';
  try { localStorage.setItem('lang', ar ? 'ar' : 'en'); } catch (e) {}
}
if (document.documentElement.getAttribute('dir') === 'rtl') { document.title = 'أويلي · حيث تصنع اليد البشرية الفن'; }

/* Sections rise in as they scroll into view */
(function(){
  var els = document.querySelectorAll('.reveal');
  if (!('IntersectionObserver' in window)) { els.forEach(function(el){ el.classList.add('in'); }); return; }
  var io = new IntersectionObserver(function(entries){
    entries.forEach(function(en){ if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
  }, { rootMargin: '0px 0px -8% 0px' });
  els.forEach(function(el){ io.observe(el); });
})();

/* Background video fades in once it can play */
(function(){
  var v = document.getElementById('vid');
  if (!v) return;
  v.addEventListener('canplay', function(){ v.classList.add('on'); }, { once: true });
  var p = v.play(); if (p && p.catch) p.catch(function(){});
})();
</script>
</body>
</html>
