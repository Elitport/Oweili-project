<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en" dir="ltr" id="html">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>The Studio · Oweili</title>
<link rel="stylesheet" href="/assets/css/oweili.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:#fff;font-family:var(--font-sans);overflow-x:hidden;}
.bg-wrap{position:fixed;inset:0;z-index:0;overflow:hidden;}
.bg-video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0;transition:opacity 2s ease;z-index:2;}
.bg-video.on{opacity:0.95;}
.overlay{position:fixed;inset:0;z-index:3;pointer-events:none;background:linear-gradient(160deg,rgba(255,255,255,0.18) 0%,rgba(255,255,255,0) 50%,rgba(255,255,255,0.35) 100%);}
/* ── TOP NAV (shared brand navbar) ── */
.topnav{position:fixed;top:0;left:0;right:0;z-index:200;display:flex;align-items:center;justify-content:space-between;padding:0 16px;height:56px;background:rgba(255,255,255,0.6);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-bottom:1px solid rgba(0,0,0,0.05);}
[dir="rtl"] .dropdown{left:auto;right:0;text-align:right;}
.nav-logo{display:flex;align-items:center;gap:6px;text-decoration:none;color:#111111;flex-shrink:0;}
.nav-logo svg{color:#ff0055;}
.nav-logo span{font-size:12px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;}
.nav-center{display:flex;align-items:stretch;gap:0;height:100%;}
.nav-item{position:relative;display:flex;align-items:center;}
.nav-item > a{display:flex;align-items:center;gap:4px;padding:0 12px;height:100%;font-size:12px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:rgba(0,0,0,0.65);text-decoration:none;transition:color 0.2s;white-space:nowrap;border-bottom:2px solid transparent;}
.nav-item > a:hover{color:#000;}
.nav-item:hover > a{color:#0066ff;border-bottom-color:#0066ff;}
.nav-item > a .chevron{width:10px;height:10px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;transition:transform 0.2s;flex-shrink:0;}
.nav-item:hover > a .chevron{transform:rotate(180deg);}
.dropdown{position:absolute;top:100%;left:0;min-width:200px;background:rgba(255,255,255,0.96);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(0,0,0,0.07);border-radius:14px;box-shadow:0 16px 40px rgba(0,0,0,0.1);padding:8px;opacity:0;visibility:hidden;transform:translateY(8px);transition:opacity 0.2s,transform 0.2s,visibility 0.2s;pointer-events:none;}
.nav-item:hover .dropdown{opacity:1;visibility:visible;transform:translateY(0);pointer-events:auto;}
.dropdown a{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:9px;font-size:12px;color:rgba(0,0,0,0.7);text-decoration:none;transition:background 0.15s,color 0.15s;}
.dropdown a:hover{background:rgba(0,100,255,0.07);color:#0066ff;}
.dropdown a .d-icon{width:14px;height:14px;fill:currentColor;opacity:0.6;flex-shrink:0;}
.nav-right{display:flex;align-items:center;gap:6px;flex-shrink:0;}
.nav-btn{padding:5px 12px;border-radius:999px;font-size:12px;font-weight:600;letter-spacing:0.04em;text-transform:uppercase;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;transition:all 0.22s;border:1px solid rgba(0,0,0,0.1);background:rgba(0,0,0,0.04);color:rgba(0,0,0,0.8);}
.nav-btn:hover{background:rgba(0,0,0,0.08);color:#000;}
.nav-btn.primary{background:rgba(0,100,255,0.1);border-color:rgba(0,100,255,0.2);color:#0066ff;}
.nav-btn.primary:hover{background:rgba(0,100,255,0.18);}
.lang-btn{padding:4px 10px;border-radius:999px;font-size:12px;font-weight:600;letter-spacing:0.04em;cursor:pointer;border:1px solid rgba(0,0,0,0.1);background:rgba(255,255,255,0.7);color:rgba(0,0,0,0.7);backdrop-filter:blur(8px);transition:all 0.22s;}
.lang-btn:hover{background:#111111;color:#fff;}
@media(max-width:1024px){.nav-center{display:none;}}

.page{position:relative;z-index:10;width:100%;max-width:1280px;margin:0 auto;padding:100px 32px 48px;}

/* PAGE HEADER */
.page-header{margin-bottom:48px;animation:riseUp 0.9s cubic-bezier(0.22,1,0.36,1) forwards;opacity:0;}
.page-badge{display:inline-flex;align-items:center;gap:8px;padding:5px 14px;border-radius:999px;margin-bottom:16px;background:rgba(255,0,85,0.05);border:1px solid rgba(255,0,85,0.15);font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:#111111;}
.pulse-dot{width:6px;height:6px;border-radius:50%;background:#ff0055;box-shadow:0 0 8px rgba(255,0,85,0.5);animation:pulse 2s infinite;}
.page-header h1{font-size:clamp(28px,4vw,48px);font-weight:300;color:#111;margin-bottom:12px;letter-spacing:-0.02em;}
.page-header h1 em{font-style:normal;font-weight:700;background:linear-gradient(90deg,#ff0055,#0066ff,#0066ff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.page-header p{font-size:15px;color:rgba(0,0,0,0.6);line-height:1.8;max-width:580px;}

/* WORKSHOP CARDS */
.workshops-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:48px;animation:riseUp 0.9s 0.1s cubic-bezier(0.22,1,0.36,1) both;}
.workshop-card{background:rgba(255,255,255,0.6);backdrop-filter:blur(16px);border:1px solid rgba(0,0,0,0.07);border-radius:20px;overflow:hidden;cursor:pointer;transition:all 0.3s ease;text-decoration:none;display:block;}
.workshop-card:hover{transform:translateY(-4px);box-shadow:0 20px 40px rgba(0,0,0,0.08);}
.workshop-img{height:180px;background-size:cover;background-position:center;transition:transform 0.5s ease;}
.workshop-card:hover .workshop-img{transform:scale(1.04);}
.workshop-body{padding:20px;}
.workshop-tag{display:inline-block;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:10px;}
.tag-watercolor{background:rgba(0,100,255,0.08);color:#0066ff;border:1px solid rgba(0,100,255,0.15);}
.tag-oil{background:rgba(255,0,85,0.08);color:#111111;border:1px solid rgba(255,0,85,0.15);}
.tag-digital{background:rgba(0,102,255,0.08);color:#0066ff;border:1px solid rgba(0,102,255,0.15);}
.tag-charcoal{background:rgba(0,0,0,0.06);color:#333;border:1px solid rgba(0,0,0,0.12);}
.tag-pastel{background:rgba(255,0,85,0.06);color:#111111;border:1px solid rgba(255,0,85,0.15);}
.tag-mixed{background:rgba(0,102,255,0.08);color:#0066ff;border:1px solid rgba(0,102,255,0.15);}
.workshop-body h3{font-size:15px;font-weight:700;color:#111;margin-bottom:6px;}
.workshop-body p{font-size:12px;color:rgba(0,0,0,0.55);line-height:1.7;}
.workshop-meta{display:flex;align-items:center;justify-content:space-between;margin-top:14px;padding-top:14px;border-top:1px solid rgba(0,0,0,0.06);}
.workshop-level{font-size:12px;color:rgba(0,0,0,0.4);text-transform:uppercase;letter-spacing:0.08em;}
.workshop-cta{font-size:12px;font-weight:700;color:#111111;letter-spacing:0.06em;text-transform:uppercase;}

/* FEATURED SECTION */
.featured-section{animation:riseUp 0.9s 0.2s cubic-bezier(0.22,1,0.36,1) both;margin-bottom:48px;}
.section-title{font-size:12px;text-transform:uppercase;letter-spacing:0.16em;color:rgba(0,0,0,0.45);font-weight:700;margin-bottom:20px;}
.featured-grid{display:grid;grid-template-columns:1.5fr 1fr;gap:20px;}
.featured-main{background:rgba(255,255,255,0.6);backdrop-filter:blur(16px);border:1px solid rgba(0,0,0,0.07);border-radius:20px;overflow:hidden;}
.featured-main-img{height:260px;background:url('https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?auto=format&fit=crop&w=800&q=80') center/cover;}
.featured-main-body{padding:24px;}
.featured-main-body h2{font-size:20px;font-weight:700;color:#111;margin-bottom:8px;}
.featured-main-body p{font-size:13px;color:rgba(0,0,0,0.6);line-height:1.8;margin-bottom:16px;}
.featured-side{display:flex;flex-direction:column;gap:14px;}
.side-card{background:rgba(255,255,255,0.6);backdrop-filter:blur(16px);border:1px solid rgba(0,0,0,0.07);border-radius:16px;padding:20px;cursor:pointer;transition:all 0.25s;}
.side-card:hover{transform:translateX(4px);background:rgba(255,255,255,0.8);}
.side-card h4{font-size:13px;font-weight:700;color:#111;margin-bottom:4px;}
.side-card p{font-size:12px;color:rgba(0,0,0,0.5);line-height:1.6;}
.side-card-icon{font-size:20px;margin-bottom:8px;}

/* ROADMAP */
.roadmap-section{animation:riseUp 0.9s 0.3s cubic-bezier(0.22,1,0.36,1) both;}
.roadmap-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;}
.roadmap-item{background:rgba(255,255,255,0.5);backdrop-filter:blur(12px);border:1px solid rgba(0,0,0,0.06);border-radius:16px;padding:20px;text-align:center;}
.roadmap-num{font-size:28px;font-weight:300;color:rgba(0,0,0,0.15);margin-bottom:8px;}
.roadmap-item h4{font-size:12px;font-weight:700;color:#111;margin-bottom:6px;}
.roadmap-item p{font-size:12px;color:rgba(0,0,0,0.5);line-height:1.6;}
.roadmap-status{display:inline-block;margin-top:10px;padding:3px 8px;border-radius:999px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;}
.status-live{background:rgba(0,102,255,0.1);color:#0066ff;border:1px solid rgba(0,102,255,0.2);}
.status-soon{background:rgba(0,100,255,0.08);color:#0066ff;border:1px solid rgba(0,100,255,0.15);}
.status-planned{background:rgba(0,0,0,0.05);color:rgba(0,0,0,0.4);border:1px solid rgba(0,0,0,0.1);}

.btn-p{display:inline-block;padding:11px 24px;border-radius:999px;background:#111;color:#fff;font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;border:none;cursor:pointer;transition:all 0.25s;text-decoration:none;}
.btn-p:hover{background:#333;transform:translateY(-2px);}

@keyframes riseUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.4}}

[dir="rtl"] .workshops-grid,[dir="rtl"] .featured-grid,[dir="rtl"] .roadmap-grid{direction:rtl;}
[dir="rtl"] .side-card:hover{transform:translateX(-4px);}
[dir="rtl"] .page-header p{text-align:right;}

@media(max-width:1024px){.workshops-grid{grid-template-columns:repeat(2,1fr);}.roadmap-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:768px){.workshops-grid{grid-template-columns:1fr;}.featured-grid{grid-template-columns:1fr;}.roadmap-grid{grid-template-columns:1fr 1fr;}.page{padding:90px 16px 32px;}.topnav{padding:10px 14px;}.nav-logo span{display:none;}}
</style>
</head>
<body>

<div class="bg-wrap">
  <video class="bg-video" id="vid" autoplay loop muted playsinline src="bg.mp4"></video>
</div>
<div class="overlay"></div>

<!-- NAV -->
<?php include __DIR__ . "/nav.php"; ?>

<!-- PAGE -->
<div class="page" id="pg">

  <!-- HEADER -->
  <div class="page-header">
    <div class="page-badge"><div class="pulse-dot"></div><span id="t-badge">Learn &amp; Practice</span></div>
    <h1 id="t-h1">The <em>Oweili Studio</em></em></h1>
    <p id="t-desc">Learn and practice real art styles rooted in Saudi culture — from desert landscapes to Arabic calligraphy. Explore our style guides today; workshops with Saudi art institutes are coming soon.</p>
  </div>

  <!-- WORKSHOPS -->
  <p class="section-title" id="t-ws-title">UPCOMING WORKSHOPS</p>
  <div class="workshops-grid">
    <a class="workshop-card" href="register.php">
      <div class="workshop-img" style="background-image:url('https://images.unsplash.com/photo-1460661419201-fd4cecdf8a8b?auto=format&fit=crop&w=600&q=80')"></div>
      <div class="workshop-body">
        <span class="workshop-tag tag-watercolor" id="t-tag1">Watercolor</span>
        <h3 id="t-ws1">Desert Light in Watercolor</h3>
        <p id="t-ws1d">Capture the warm light and soft dunes of the Saudi desert with layered watercolor washes.</p>
        <div class="workshop-meta">
          <span class="workshop-level" id="t-lv1">Coming Soon</span>
          <span class="workshop-cta" id="t-join1">Notify Me →</span>
        </div>
      </div>
    </a>
    <a class="workshop-card" href="register.php">
      <div class="workshop-img" style="background-image:url('https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?auto=format&fit=crop&w=600&q=80')"></div>
      <div class="workshop-body">
        <span class="workshop-tag tag-oil" id="t-tag2">Oil Painting</span>
        <h3 id="t-ws2">Desert Landscapes in Oil</h3>
        <p id="t-ws2d">Paint wide desert horizons, rock formations and golden-hour skies using classical oil techniques.</p>
        <div class="workshop-meta">
          <span class="workshop-level" id="t-lv2">Coming Soon</span>
          <span class="workshop-cta" id="t-join2">Notify Me →</span>
        </div>
      </div>
    </a>
    <a class="workshop-card" href="register.php">
      <div class="workshop-img" style="background-image:url('https://images.unsplash.com/photo-1558618666-fcd25c85cd64?auto=format&fit=crop&w=600&q=80')"></div>
      <div class="workshop-body">
        <span class="workshop-tag tag-digital" id="t-tag3">Digital Art</span>
        <h3 id="t-ws3">Digital Arabic Calligraphy</h3>
        <p id="t-ws3d">Learn Arabic letterforms and Islamic geometric patterns, then bring them to life with digital tools.</p>
        <div class="workshop-meta">
          <span class="workshop-level" id="t-lv3">Coming Soon</span>
          <span class="workshop-cta" id="t-join3">Notify Me →</span>
        </div>
      </div>
    </a>
    <a class="workshop-card" href="register.php">
      <div class="workshop-img" style="background-image:url('https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=600&q=80')"></div>
      <div class="workshop-body">
        <span class="workshop-tag tag-charcoal" id="t-tag4">Charcoal</span>
        <h3 id="t-ws4">Saudi Heritage Drawing</h3>
        <p id="t-ws4d">Draw the carved doors, Najdi mud architecture and traditional crafts of Saudi heritage in charcoal and ink.</p>
        <div class="workshop-meta">
          <span class="workshop-level" id="t-lv4">Coming Soon</span>
          <span class="workshop-cta" id="t-join4">Notify Me →</span>
        </div>
      </div>
    </a>
    <a class="workshop-card" href="register.php">
      <div class="workshop-img" style="background-image:url('https://images.unsplash.com/photo-1493106641515-5cd27efb8e20?auto=format&fit=crop&w=600&q=80')"></div>
      <div class="workshop-body">
        <span class="workshop-tag tag-pastel" id="t-tag5">Community</span>
        <h3 id="t-ws5">Community Resource Library</h3>
        <p id="t-ws5d">A curated resource library is being built by our artist community. Upload your first artwork to contribute.</p>
        <div class="workshop-meta">
          <span class="workshop-level" id="t-lv5">Coming Soon</span>
          <span class="workshop-cta" id="t-join5">Contribute →</span>
        </div>
      </div>
    </a>
    <a class="workshop-card" href="register.php">
      <div class="workshop-img" style="background-image:url('https://images.unsplash.com/photo-1578662996442-48f60103fc96?auto=format&fit=crop&w=600&q=80')"></div>
      <div class="workshop-body">
        <span class="workshop-tag tag-mixed" id="t-tag6">Mixed Media</span>
        <h3 id="t-ws6">Contemporary Saudi Expression</h3>
        <p id="t-ws6d">Combine collage, paint and photography to tell the stories of Saudi Arabia today in your own contemporary voice.</p>
        <div class="workshop-meta">
          <span class="workshop-level" id="t-lv6">Coming Soon</span>
          <span class="workshop-cta" id="t-join6">Notify Me →</span>
        </div>
      </div>
    </a>
  </div>

  <!-- FEATURED -->
  <p class="section-title" id="t-feat-title">TRAINING &amp; RESOURCES</p>
  <div class="featured-section">
    <div class="featured-grid">
      <div class="featured-main">
        <div class="featured-main-img"></div>
        <div class="featured-main-body">
          <h2 id="t-feat-h">Art Workshops with Saudi Institutes</h2>
          <p id="t-feat-p">Art workshops are coming soon. We are building partnerships with Saudi art institutes to bring you real, in-person and online training.</p>
          <a class="btn-p" href="register.php" id="t-feat-btn">Notify Me</a>
        </div>
      </div>
      <div class="featured-side">
        <div class="side-card" onclick="location.href='explore.php'">
          <div class="side-card-icon">🎨</div>
          <h4 id="t-sc1">Explore 12 Art Styles</h4>
          <p id="t-sc1d">Discover 12 real art styles — from watercolor and oil to charcoal and mixed media — and choose the one you want to practice.</p>
        </div>
        <div class="side-card" onclick="location.href='explore.php'">
          <div class="side-card-icon">📚</div>
          <h4 id="t-sc2">Art Style Guides</h4>
          <p id="t-sc2d">Explore 12 real art styles with detailed guides for each one — from watercolor and oil painting to charcoal, pastel, and digital art.</p>
        </div>
        <div class="side-card" onclick="location.href='register.php'">
          <div class="side-card-icon">🏆</div>
          <h4 id="t-sc3">Monthly Art Challenges — Coming Soon</h4>
          <p id="t-sc3d">Themed challenges inspired by Saudi culture, where artists share new work and the community votes.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- ROADMAP -->
  <p class="section-title" id="t-road-title">FUTURE TOOLS ROADMAP</p>
  <div class="roadmap-section">
    <div class="roadmap-grid">
      <div class="roadmap-item">
        <div class="roadmap-num">01</div>
        <h4 id="t-r1">Color Theory Guides</h4>
        <p id="t-r1d">Learn to build harmonious color palettes by hand, with step-by-step exercises for every medium.</p>
        <span class="roadmap-status status-planned" id="t-rs1">Planned</span>
      </div>
      <div class="roadmap-item">
        <div class="roadmap-num">02</div>
        <h4 id="t-r2">Brush Simulator</h4>
        <p id="t-r2d">Realistic digital brushes that mimic watercolor, oil, and charcoal.</p>
        <span class="roadmap-status status-soon" id="t-rs2">Coming Soon</span>
      </div>
      <div class="roadmap-item">
        <div class="roadmap-num">03</div>
        <h4 id="t-r3">Texture Scanner</h4>
        <p id="t-r3d">Scan real-world textures with your phone and use them in artwork.</p>
        <span class="roadmap-status status-planned" id="t-rs3">Planned</span>
      </div>
      <div class="roadmap-item">
        <div class="roadmap-num">04</div>
        <h4 id="t-r4">Art Portfolio</h4>
        <p id="t-r4d">A personal gallery page for every artist to showcase their journey.</p>
        <span class="roadmap-status status-planned" id="t-rs4">Planned</span>
      </div>
    </div>
  </div>

</div>

<script>
const T={
  en:{dir:'ltr',lb:'العربية',
    studio:'The Studio',explore:'Explore',community:'Community',support:'Support',login:'Sign In',
    badge:'Learn & Practice',h1:'The <em>Oweili Studio</em>',
    desc:'Learn and practice real art styles rooted in Saudi culture — from desert landscapes to Arabic calligraphy. Explore our style guides today; workshops with Saudi art institutes are coming soon.',
    wsTitle:'UPCOMING WORKSHOPS',featTitle:'TRAINING & RESOURCES',roadTitle:'FUTURE TOOLS ROADMAP',
    tag1:'Watercolor',ws1:'Desert Light in Watercolor',ws1d:'Capture the warm light and soft dunes of the Saudi desert with layered watercolor washes.',lv1:'Coming Soon',join1:'Notify Me →',
    tag2:'Oil Painting',ws2:'Desert Landscapes in Oil',ws2d:'Paint wide desert horizons, rock formations and golden-hour skies using classical oil techniques.',lv2:'Coming Soon',join2:'Notify Me →',
    tag3:'Digital Art',ws3:'Digital Arabic Calligraphy',ws3d:'Learn Arabic letterforms and Islamic geometric patterns, then bring them to life with digital tools.',lv3:'Coming Soon',join3:'Notify Me →',
    tag4:'Charcoal',ws4:'Saudi Heritage Drawing',ws4d:'Draw the carved doors, Najdi mud architecture and traditional crafts of Saudi heritage in charcoal and ink.',lv4:'Coming Soon',join4:'Notify Me →',
    tag5:'Community',ws5:'Community Resource Library',ws5d:'A curated resource library is being built by our artist community. Upload your first artwork to contribute.',lv5:'Coming Soon',join5:'Contribute →',
    tag6:'Mixed Media',ws6:'Contemporary Saudi Expression',ws6d:'Combine collage, paint and photography to tell the stories of Saudi Arabia today in your own contemporary voice.',lv6:'Coming Soon',join6:'Notify Me →',
    featH:'Art Workshops with Saudi Institutes',featP:'Art workshops are coming soon. We are building partnerships with Saudi art institutes to bring you real, in-person and online training.',featBtn:'Notify Me',
    sc1:'Explore 12 Art Styles',sc1d:'Discover 12 real art styles — from watercolor and oil to charcoal and mixed media — and choose the one you want to practice.',
    sc2:'Art Style Guides',sc2d:'Explore 12 real art styles with detailed guides for each one — from watercolor and oil painting to charcoal, pastel, and digital art.',
    sc3:'Monthly Art Challenges — Coming Soon',sc3d:'Themed challenges inspired by Saudi culture, where artists share new work and the community votes.',
    r1:'Color Theory Guides',r1d:'Learn to build harmonious color palettes by hand, with step-by-step exercises for every medium.',rs1:'Planned',
    r2:'Brush Simulator',r2d:'Realistic digital brushes that mimic watercolor, oil, and charcoal.',rs2:'Coming Soon',
    r3:'Texture Scanner',r3d:'Scan real-world textures with your phone and use them in artwork.',rs3:'Planned',
    r4:'Art Portfolio',r4d:'A personal gallery page for every artist to showcase their journey.',rs4:'Planned'},
  ar:{dir:'rtl',lb:'English',
    studio:'الاستوديو',explore:'استكشف',community:'المجتمع',support:'الدعم',login:'تسجيل الدخول',
    badge:'تعلّم وتدرّب',h1:'<em>استوديو</em> أويلي',
    desc:'تعلّم وتدرّب على أساليب فنية حقيقية مستوحاة من الثقافة السعودية — من مناظر الصحراء إلى الخط العربي. استكشف أدلة الأساليب اليوم، وورش العمل مع معاهد الفنون السعودية قادمة قريباً.',
    wsTitle:'ورش العمل القادمة',featTitle:'التدريب والموارد',roadTitle:'خارطة الأدوات المستقبلية',
    tag1:'ألوان مائية',ws1:'ضوء الصحراء بالألوان المائية',ws1d:'التقط الضوء الدافئ والكثبان الناعمة للصحراء السعودية بطبقات من الألوان المائية.',lv1:'قريباً',join1:'أبلغني →',
    tag2:'رسم زيتي',ws2:'مناظر الصحراء بالرسم الزيتي',ws2d:'ارسم آفاق الصحراء الواسعة وتكوينات الصخور وسماء الغروب الذهبية بتقنيات الرسم الزيتي الكلاسيكية.',lv2:'قريباً',join2:'أبلغني →',
    tag3:'فن رقمي',ws3:'الخط العربي الرقمي',ws3d:'تعلّم أشكال الحروف العربية والزخارف الهندسية الإسلامية، ثم أحيِها بالأدوات الرقمية.',lv3:'قريباً',join3:'أبلغني →',
    tag4:'فحم',ws4:'رسم التراث السعودي',ws4d:'ارسم الأبواب المنقوشة والعمارة النجدية الطينية والحرف التقليدية من التراث السعودي بالفحم والحبر.',lv4:'قريباً',join4:'أبلغني →',
    tag5:'المجتمع',ws5:'مكتبة الموارد المجتمعية',ws5d:'مكتبة موارد فنية يبنيها مجتمع الفنانين. ارفع عملك الأول للمساهمة.',lv5:'قريباً',join5:'ساهم →',
    tag6:'وسائط مختلطة',ws6:'التعبير السعودي المعاصر',ws6d:'امزج الكولاج والطلاء والتصوير لتروي قصص السعودية اليوم بصوتك المعاصر.',lv6:'قريباً',join6:'أبلغني →',
    featH:'ورش عمل مع معاهد الفنون السعودية',featP:'ورش العمل الفنية قادمة قريباً. نبني شراكات مع معاهد الفنون السعودية لتقديم تدريب حقيقي، حضورياً وعبر الإنترنت.',featBtn:'أبلغني',
    sc1:'استكشف 12 أسلوباً فنياً',sc1d:'اكتشف 12 أسلوباً فنياً حقيقياً — من الألوان المائية والزيت إلى الفحم والوسائط المختلطة — واختر الأسلوب الذي تريد ممارسته.',
    sc2:'أدلة الأساليب الفنية',sc2d:'استكشف 12 أسلوباً فنياً حقيقياً مع أدلة تفصيلية لكل أسلوب — من الألوان المائية والرسم الزيتي إلى الفحم والباستيل والفن الرقمي.',
    sc3:'تحديات فنية شهرية — قريباً',sc3d:'تحديات بمواضيع مستوحاة من الثقافة السعودية، يشارك فيها الفنانون أعمالاً جديدة ويصوّت المجتمع.',
    r1:'أدلة نظرية الألوان',r1d:'تعلّم بناء لوحات ألوان متناسقة بيدك، مع تمارين خطوة بخطوة لكل وسيط.',rs1:'مخطط',
    r2:'محاكي الفرشاة',r2d:'فرش رقمية واقعية تحاكي الألوان المائية والزيت والفحم.',rs2:'قريباً',
    r3:'ماسح الخامات',r3d:'امسح خامات العالم الحقيقي بهاتفك واستخدمها في الأعمال الفنية.',rs3:'مخطط',
    r4:'ملف الفنان',r4d:'صفحة معرض شخصية لكل فنان لعرض رحلته الإبداعية.',rs4:'مخطط'}
};
let L='en';
const NAV={
  en:{lb:'العربية',studio:'The Studio',community:'Community',marketplace:'Marketplace',explore:'Explore Art Styles',support:'Support',
    s1:'Watercolor Workshop',s2:'Oil Painting Studio',s3:'Digital Art Lab',s4:'Charcoal & Ink',
    c1:'Artist Chat Room',c2:'Meet Fellow Artists',c3:'Share Your Work',c4:'Learn Together',
    sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',
    login:'Sign In',reg:'Join Free'},
  ar:{lb:'English',studio:'الاستوديو',community:'المجتمع',marketplace:'السوق',explore:'استكشف أساليب الرسم',support:'الدعم',
    s1:'ورشة الألوان المائية',s2:'استوديو الرسم الزيتي',s3:'مختبر الفن الرقمي',s4:'الفحم والحبر',
    c1:'غرفة محادثة الفنانين',c2:'تعرّف على فنانين',c3:'شارك أعمالك',c4:'تعلّم معاً',
    sp1:'تواصل معنا',sp2:'كيف يعمل الموقع',sp3:'شروط الاستخدام',
    login:'تسجيل الدخول',reg:'انضم مجاناً'}
};
function applyNav(l){
  const n=NAV[l];
  const set=(id,v)=>{const e=document.getElementById(id);if(e)e.textContent=v;};
  set('lb',n.lb);
  set('nav-studio-label',n.studio);set('nav-community-label',n.community);
  set('nav-marketplace-label',n.marketplace);set('nav-explore-label',n.explore);set('nav-support-label',n.support);
  set('dd-s1',n.s1);set('dd-s2',n.s2);set('dd-s3',n.s3);set('dd-s4',n.s4);
  set('dd-c1',n.c1);set('dd-c2',n.c2);set('dd-c3',n.c3);set('dd-c4',n.c4);
  set('dd-sp1',n.sp1);set('dd-sp2',n.sp2);set('dd-sp3',n.sp3);
  set('n-login-t',n.login);set('n-reg-t',n.reg);
  const mk=document.getElementById('nav-marketplace-link');
  if(mk)mk.href=l==='en'?'marketplace.php?lang=en':'marketplace.php?lang=ar';
}
function apply(l){
  const t=T[l];
  document.getElementById('html').lang=l;
  document.getElementById('html').setAttribute('dir',t.dir);
  document.documentElement.setAttribute('dir',t.dir);
  document.getElementById('pg').setAttribute('dir',t.dir);
  document.getElementById('topnav').setAttribute('dir',t.dir);
  applyNav(l);
  document.getElementById('t-badge').textContent=t.badge;
  document.getElementById('t-h1').innerHTML=t.h1;
  document.getElementById('t-desc').textContent=t.desc;
  document.getElementById('t-ws-title').textContent=t.wsTitle;
  document.getElementById('t-feat-title').textContent=t.featTitle;
  document.getElementById('t-road-title').textContent=t.roadTitle;
  const ids=['tag1','ws1','ws1d','lv1','join1','tag2','ws2','ws2d','lv2','join2','tag3','ws3','ws3d','lv3','join3','tag4','ws4','ws4d','lv4','join4','tag5','ws5','ws5d','lv5','join5','tag6','ws6','ws6d','lv6','join6','featH','featP','featBtn','sc1','sc1d','sc2','sc2d','sc3','sc3d','r1','r1d','rs1','r2','r2d','rs2','r3','r3d','rs3','r4','r4d','rs4'];
  ids.forEach(id=>{const el=document.getElementById('t-'+id);if(el)el.innerHTML=t[id];});
}
function tgl(){L=L==='en'?'ar':'en';apply(L);try{localStorage.setItem('lang',L);}catch(e){}}
try{var _s=localStorage.getItem('lang');if(_s==='ar'||_s==='en')L=_s;}catch(e){}
apply(L);
const v=document.getElementById('vid');
v.addEventListener('canplay',()=>v.classList.add('on'),{once:true});
v.play().catch(()=>{});
</script>
</body>
</html>
