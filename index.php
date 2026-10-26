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
<link rel="stylesheet" href="/assets/css/oweili.css">
<link rel="stylesheet" href="/assets/css/nav.css">
<link rel="stylesheet" href="/assets/css/proof.css">
<style>
html{scroll-behavior:smooth;}
.ow{background:var(--color-bg);overflow-x:hidden;}

/* background video with a white veil (kept from the original homepage) */
.bg-wrap{position:fixed;inset:0;z-index:0;overflow:hidden;}
.bg-video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0;transition:opacity 2s ease;}
.bg-video.on{opacity:0.9;}
.veil{position:fixed;inset:0;z-index:1;pointer-events:none;background:linear-gradient(180deg,rgba(255,255,255,0.55) 0%,rgba(255,255,255,0.78) 55%,rgba(255,255,255,0.92) 100%);}
main{position:relative;z-index:2;}

.wrap{max-width:1200px;margin:0 auto;padding:0 32px;}
section{padding:72px 0;}
.ow .sec-title{font-size:clamp(24px,3.4vw,36px);font-weight:700;letter-spacing:-0.02em;color:var(--color-dark);text-align:center;margin:0 0 12px;}
.ow .sec-sub{font-size:15px;line-height:1.75;color:var(--color-body);text-align:center;max-width:620px;margin:0 auto 36px;}

/* glass card */
.glass-card{background:rgba(255,255,255,0.7);-webkit-backdrop-filter:blur(18px);backdrop-filter:blur(18px);border:1px solid rgba(0,0,0,0.07);border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.04);}

/* buttons */
.btn{display:inline-flex;align-items:center;justify-content:center;min-height:52px;padding:14px 30px;border-radius:999px;font-size:16px;font-weight:700;text-decoration:none;transition:background 0.2s,transform 0.2s,box-shadow 0.2s;border:1px solid transparent;}
.btn-pink{background:var(--color-primary);color:#fff;box-shadow:0 10px 24px rgba(255,0,85,0.22);}
.btn-pink:hover{background:var(--color-primary-dark,#e0004b);transform:translateY(-2px);}
.btn-glass{background:rgba(255,255,255,0.8);color:var(--color-dark);border-color:rgba(0,0,0,0.12);-webkit-backdrop-filter:blur(10px);backdrop-filter:blur(10px);}
.btn-glass:hover{background:#fff;transform:translateY(-2px);}
.btn:active{transform:translateY(0);}
.btn:focus-visible,.art-card a:focus-visible,.disc-card:focus-visible,.text-link:focus-visible,.scroll-cue:focus-visible{outline:2px solid var(--color-accent);outline-offset:3px;}

/* 1 — hero */
.hero{min-height:calc(100vh - 56px);min-height:calc(100svh - 56px);display:flex;align-items:center;padding:96px 0 64px;}
.hero-inner{text-align:center;max-width:820px;margin:0 auto;}
.hero-badge{display:inline-flex;align-items:center;gap:8px;padding:6px 14px;border-radius:999px;background:rgba(255,255,255,0.75);border:1px solid rgba(0,102,255,0.2);font-size:13px;font-weight:700;color:var(--color-dark);margin-bottom:22px;}
.hero-badge i{width:8px;height:8px;border-radius:50%;background:var(--color-accent);}
.ow .hero h1{font-size:clamp(38px,7vw,72px);font-weight:700;letter-spacing:-0.035em;line-height:1.05;color:var(--color-dark);margin:0 0 20px;}
.hero h1 em{font-style:normal;color:var(--color-primary);}
.ow .hero-sub{font-size:clamp(16px,2.2vw,19px);line-height:1.75;color:var(--color-body);max-width:640px;margin:0 auto 34px;}
.hero-btns{display:flex;flex-wrap:wrap;gap:12px;justify-content:center;}
.scroll-cue{display:inline-flex;flex-direction:column;align-items:center;gap:6px;margin-top:44px;font-size:13px;font-weight:600;color:var(--color-body);text-decoration:none;}
.scroll-cue svg{width:20px;height:20px;stroke:currentColor;fill:none;stroke-width:2;}

/* 2 — how it works */
.steps{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;counter-reset:step;}
.step{padding:30px 26px;text-align:center;}
.step-icon{width:60px;height:60px;border-radius:18px;display:inline-flex;align-items:center;justify-content:center;background:rgba(0,102,255,0.08);color:var(--color-accent);margin-bottom:16px;}
.step-icon svg{width:30px;height:30px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}
.step-num{display:block;font-size:12px;font-weight:700;color:var(--color-light);letter-spacing:0.08em;margin-bottom:6px;}
.ow .step h3{font-size:18px;font-weight:700;color:var(--color-dark);margin:0 0 8px;}
.ow .step p{font-size:14px;line-height:1.75;color:var(--color-body);margin:0;}

/* 3 — latest verified artworks */
.art-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;}
.art-card{overflow:hidden;display:flex;flex-direction:column;transition:transform 0.3s,box-shadow 0.3s;}
.art-card:hover{transform:translateY(-4px);box-shadow:0 16px 40px rgba(0,0,0,0.08);}
.art-img{display:block;aspect-ratio:4/5;background:#f4f5f7;overflow:hidden;}
.art-img img{width:100%;height:100%;object-fit:cover;display:block;transition:transform 0.5s;}
.art-card:hover .art-img img{transform:scale(1.04);}
.art-body{padding:14px 16px 16px;display:flex;flex-direction:column;gap:4px;flex:1;}
.art-title{font-size:15px;font-weight:700;color:var(--color-dark);text-decoration:none;line-height:1.4;}
.art-title:hover{color:var(--color-accent);}
.art-artist{font-size:13px;font-weight:600;color:var(--color-accent);text-decoration:none;}
.art-artist:hover{text-decoration:underline;}
.art-badge{align-self:flex-start;margin-top:4px;padding:2px 8px;}
.art-price{margin-top:auto;padding-top:8px;font-size:16px;font-weight:700;color:var(--color-dark);}
.sec-foot{text-align:center;margin-top:28px;}
.text-link{font-size:15px;font-weight:700;color:var(--color-accent);text-decoration:none;}
.text-link:hover{text-decoration:underline;}
.empty{padding:44px 24px;text-align:center;font-size:16px;line-height:1.75;color:var(--color-dark);}
.empty svg{width:44px;height:44px;stroke:var(--color-accent);fill:none;stroke-width:1.6;display:block;margin:0 auto 12px;}

/* 4 — join */
.join{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
.join-card{padding:36px 32px;display:flex;flex-direction:column;align-items:flex-start;gap:12px;}
.ow .join-card h3{font-size:24px;font-weight:700;letter-spacing:-0.02em;color:var(--color-dark);margin:0;}
.ow .join-card p{font-size:15px;line-height:1.75;color:var(--color-body);margin:0 0 8px;}
.join-card .tag-line{font-size:12px;font-weight:700;letter-spacing:0.08em;color:var(--color-accent);}

/* 5 — discover */
.disc{display:grid;grid-template-columns:1fr 1fr;gap:18px;max-width:820px;margin:0 auto;}
.disc-card{display:flex;align-items:center;gap:16px;padding:22px 24px;text-decoration:none;color:var(--color-dark);transition:transform 0.2s,box-shadow 0.2s;}
.disc-card:hover{transform:translateY(-3px);box-shadow:0 12px 30px rgba(0,0,0,0.07);}
.disc-card svg.ic{width:40px;height:40px;flex-shrink:0;padding:9px;border-radius:12px;background:rgba(0,102,255,0.08);stroke:var(--color-accent);fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}
.disc-card b{display:block;font-size:17px;}
.disc-card span.small{display:block;font-size:13px;color:var(--color-body);margin-top:2px;}
.disc-card .arrow{margin-inline-start:auto;font-size:20px;color:var(--color-accent);}
[dir="rtl"] .disc-card .arrow{transform:scaleX(-1);}

footer{position:relative;z-index:2;padding:28px 0 36px;text-align:center;font-size:13px;color:var(--color-body);}
footer a{color:var(--color-dark);font-weight:600;}

/* rise-up on scroll (only when JavaScript is on, so content is never hidden without it) */
.js .reveal{opacity:0;transform:translateY(22px);}
.js .reveal.in{animation:ow-riseUp 0.9s cubic-bezier(0.22,1,0.36,1) both;}

@media (max-width:1024px){.art-grid{grid-template-columns:repeat(3,1fr);}}
@media (max-width:768px){
  .wrap{padding:0 16px;}
  section{padding:52px 0;}
  .steps{grid-template-columns:1fr;}
  .art-grid{grid-template-columns:repeat(2,1fr);gap:12px;}
  .join,.disc{grid-template-columns:1fr;}
  .join-card{padding:28px 22px;}
}
@media (max-width:360px){.art-grid{grid-template-columns:1fr;}}
@media (prefers-reduced-motion:reduce){
  html{scroll-behavior:auto;}
  .js .reveal{opacity:1;transform:none;}
  .js .reveal.in{animation:none;}
  .bg-video{display:none;}
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
    <div class="wrap hero-inner reveal">
      <span class="hero-badge"><i aria-hidden="true"></i><?= hb('Real artists · Real art · Verified by proof', 'فنانون حقيقيون · فن حقيقي · موثّق بالدليل') ?></span>
      <h1><span class="bi-en" lang="en">Where Human Hands <em>Create Art</em></span><span class="bi-ar" lang="ar">حيث تصنع <em>اليد البشرية</em> الفن</span></h1>
      <p class="hero-sub"><?= hb('Oweili is the home of real art — made by real artists, verified by proof, trusted by collectors worldwide.', 'أويلي هو بيت الفن الحقيقي — يصنعه فنانون حقيقيون، موثّق بالدليل، يثق به المقتنون حول العالم.') ?></p>
      <div class="hero-btns">
        <a class="btn btn-pink" href="marketplace.php"><?= hb('Explore Artworks', 'استكشف الأعمال') ?></a>
        <a class="btn btn-glass" href="register_artist.php"><?= hb('Join as Artist', 'انضم كفنان') ?></a>
      </div>
      <a class="scroll-cue" href="#how">
        <?= hb('How it works', 'كيف يعمل') ?>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
      </a>
    </div>
  </section>

  <!-- 2 — HOW IT WORKS -->
  <section id="how">
    <div class="wrap">
      <h2 class="sec-title reveal"><?= hb('Art You Can Trust', 'فن يمكنك الوثوق به') ?></h2>
      <p class="sec-sub reveal"><?= hb('Three simple steps stand behind every artwork on Oweili.', 'ثلاث خطوات بسيطة وراء كل عمل فني على أويلي.') ?></p>
      <div class="steps">
        <div class="glass-card step reveal">
          <span class="step-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h3l2-3h6l2 3h3a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1z"/><circle cx="12" cy="13" r="4"/></svg></span>
          <span class="step-num"><?= hb('STEP 1', 'الخطوة 1') ?></span>
          <h3><?= hb('Artists photograph every stage', 'الفنانون يصوّرون كل مرحلة') ?></h3>
          <p><?= hb('From first stroke to finished piece — every artwork comes with proof of creation.', 'من أول ضربة ريشة حتى اللمسة الأخيرة — كل عمل فني يأتي مع دليل على خلقه.') ?></p>
        </div>
        <div class="glass-card step reveal">
          <span class="step-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M8.5 12.5l2.5 2.5 4.5-5"/></svg></span>
          <span class="step-num"><?= hb('STEP 2', 'الخطوة 2') ?></span>
          <h3><?= hb('Our team verifies the work', 'فريقنا يتحقق من العمل') ?></h3>
          <p><?= hb('Every artwork is reviewed by our team before it goes live.', 'كل عمل فني يراجعه فريقنا قبل نشره.') ?></p>
        </div>
        <div class="glass-card step reveal">
          <span class="step-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/></svg></span>
          <span class="step-num"><?= hb('STEP 3', 'الخطوة 3') ?></span>
          <h3><?= hb('Collectors buy with confidence', 'المقتنون يشترون بثقة') ?></h3>
          <p><?= hb('See the full creation journey before you buy. Real art, real artists.', 'شاهد رحلة الإبداع كاملة قبل الشراء. فن حقيقي، فنانون حقيقيون.') ?></p>
        </div>
      </div>
    </div>
  </section>

  <!-- 3 — LATEST VERIFIED ARTWORKS -->
  <section id="latest">
    <div class="wrap">
      <h2 class="sec-title reveal"><?= hb('Latest Verified Artworks', 'أحدث الأعمال الموثّقة') ?></h2>
      <p class="sec-sub reveal"><?= hb('Fresh from our artists’ studios, reviewed by our team.', 'جديد من مراسم فنانينا، بعد مراجعة فريقنا.') ?></p>
      <?php if (!$featured): ?>
        <div class="glass-card empty reveal">
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
          <article class="glass-card art-card reveal">
            <a class="art-img" href="marketplace.php" tabindex="-1" aria-hidden="true">
              <img loading="lazy" src="<?= e($img) ?>" alt="<?= e($tEn . ' · ' . $tAr) ?>">
            </a>
            <div class="art-body">
              <a class="art-title" href="marketplace.php"><?= hb($tEn, $tAr) ?></a>
              <a class="art-artist" href="artist_dashboard.php?id=<?= (int) $aw['artist_id'] ?>"><?= hb('by ' . $artistEn, 'بواسطة ' . $artistAr) ?></a>
              <?php if (owHasJourney($stg)): ?>
                <span class="verified-badge art-badge"><?= hb('✓ Human Art', '✓ فن بشري') ?></span>
              <?php endif; ?>
              <div class="art-price"><?= e(number_format((float) $aw['price'])) ?> <?= hb('SAR', 'ر.س') ?></div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
        <div class="sec-foot reveal"><a class="text-link" href="marketplace.php"><?= hb('See all artworks →', 'شاهد كل الأعمال ←') ?></a></div>
      <?php endif; ?>
    </div>
  </section>

  <!-- 4 — JOIN THE COMMUNITY -->
  <section id="join">
    <div class="wrap">
      <div class="join">
        <div class="glass-card join-card reveal">
          <span class="tag-line"><?= hb('FOR ARTISTS', 'للفنانين') ?></span>
          <h3><?= hb('Are you an artist?', 'هل أنت فنان؟') ?></h3>
          <p><?= hb('Share your work, prove your craft, and reach collectors worldwide.', 'شارك أعمالك، أثبت حرفتك، وصل إلى مقتنين حول العالم.') ?></p>
          <a class="btn btn-pink" href="register_artist.php"><?= hb('Join as Artist', 'انضم كفنان') ?></a>
        </div>
        <div class="glass-card join-card reveal">
          <span class="tag-line"><?= hb('FOR COLLECTORS', 'للمقتنين') ?></span>
          <h3><?= hb('Looking for real art?', 'تبحث عن فن حقيقي؟') ?></h3>
          <p><?= hb('Discover verified human artworks and buy directly from the artist.', 'اكتشف أعمالاً فنية بشرية موثّقة واشترِ مباشرة من الفنان.') ?></p>
          <a class="btn btn-glass" href="marketplace.php"><?= hb('Start Exploring', 'ابدأ الاستكشاف') ?></a>
        </div>
      </div>
    </div>
  </section>

  <!-- 5 — DISCOVER THE ART WORLD -->
  <section id="discover">
    <div class="wrap">
      <h2 class="sec-title reveal"><?= hb('Discover the Art World', 'اكتشف عالم الفن') ?></h2>
      <p class="sec-sub reveal"><?= hb('Find a place to learn, and the gatherings that bring artists together.', 'اعثر على مكان للتعلّم، وعلى الملتقيات التي تجمع الفنانين.') ?></p>
      <div class="disc">
        <a class="glass-card disc-card reveal" href="training.php">
          <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1 2.7 3 6 3s6-2 6-3v-5"/></svg>
          <span><b><?= hb('Art Institutes', 'المعاهد الفنية') ?></b><span class="small"><?= hb('Courses and schools in one place', 'الدورات والمعاهد في مكان واحد') ?></span></span>
          <span class="arrow" aria-hidden="true">→</span>
        </a>
        <a class="glass-card disc-card reveal" href="events.php">
          <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          <span><b><?= hb('Global Art Events', 'الملتقيات الفنية العالمية') ?></b><span class="small"><?= hb('Exhibitions and gatherings worldwide', 'معارض وملتقيات حول العالم') ?></span></span>
          <span class="arrow" aria-hidden="true">→</span>
        </a>
      </div>
    </div>
  </section>
</main>

<footer>
  <div class="wrap"><?= hb('© ' . date('Y') . ' Oweili — real art by real artists.', '© ' . date('Y') . ' أويلي — فن حقيقي بأيدي فنانين حقيقيين.') ?> · <a href="support.php"><?= hb('Support', 'الدعم') ?></a></div>
</footer>

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
