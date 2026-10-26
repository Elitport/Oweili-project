<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en" dir="ltr" id="html">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>The Studio · Oweili</title>
<meta name="description" content="This is where your practice grows. Learn real art styles rooted in Saudi culture. Start with our style guides today. Workshops with Saudi art institutes are on their way. هنا تنمو موهبتك يوماً بعد يوم. تعلّم أساليب فنية أصيلة من روح الثقافة السعودية. ابدأ اليوم بأدلة الأساليب. وورش العمل مع معاهد الفنون السعودية في الطريق إليك.">
<link rel="stylesheet" href="/assets/css/oweili.css?v=<?= @filemtime(__DIR__ . '/assets/css/oweili.css') ?>">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:var(--color-bg);font-family:var(--font-sans);overflow-x:hidden;}
.page{position:relative;z-index:10;width:100%;max-width:1280px;margin:0 auto;padding:calc(var(--nav-h) + var(--space-xl)) var(--space-lg) var(--space-2xl);}
/* PAGE HEADER */
.page-header{margin-bottom:48px;animation:riseUp 0.9s cubic-bezier(0.22,1,0.36,1) forwards;opacity:0;}
.page-badge{display:inline-flex;align-items:center;gap:8px;padding:5px 14px;border-radius:999px;margin-bottom:16px;background:rgba(var(--rgb-primary),0.05);border:1px solid rgba(var(--rgb-primary),0.15);font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:var(--color-dark);}
.pulse-dot{width:6px;height:6px;border-radius:50%;background:var(--color-primary);box-shadow:0 0 8px rgba(var(--rgb-primary),0.5);animation:pulse 2s infinite;}
.page-header h1{font-size:clamp(28px,4vw,48px);font-weight:700;color:var(--color-dark);margin-bottom:12px;letter-spacing:-0.02em;}
.page-header h1 em{font-style:normal;font-weight:700;color:var(--color-highlight);}
.page-header p{font-size:var(--fs-body);color:var(--color-body);line-height:1.7;max-width:580px;}
/* WORKSHOP CARDS */
.workshops-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:48px;animation:riseUp 0.9s 0.1s cubic-bezier(0.22,1,0.36,1) both;}
.workshop-card{background:var(--color-surface);border:1px solid rgba(var(--rgb-ink),0.07);border-radius:var(--radius-card);overflow:hidden;cursor:pointer;transition:all 0.3s ease;text-decoration:none;display:block;box-shadow:var(--shadow-card);}
.workshop-card:hover{transform:translateY(-4px);box-shadow:0 20px 40px rgba(var(--rgb-shadow),0.08);}
.workshop-img{height:180px;background-size:cover;background-position:center;transition:transform 0.5s ease;}
.workshop-card:hover .workshop-img{transform:scale(1.04);}
.workshop-body{padding:20px;}
.workshop-tag{display:inline-block;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:10px;}
.tag-watercolor{background:rgba(var(--rgb-accent),0.08);color:var(--color-accent);border:1px solid rgba(var(--rgb-accent),0.15);}
.tag-oil{background:rgba(var(--rgb-primary),0.08);color:var(--color-dark);border:1px solid rgba(var(--rgb-primary),0.15);}
.tag-digital{background:rgba(var(--rgb-accent),0.08);color:var(--color-accent);border:1px solid rgba(var(--rgb-accent),0.15);}
.tag-charcoal{background:rgba(var(--rgb-ink),0.06);color:var(--color-dark);border:1px solid rgba(var(--rgb-ink),0.12);}
.tag-pastel{background:rgba(var(--rgb-primary),0.06);color:var(--color-dark);border:1px solid rgba(var(--rgb-primary),0.15);}
.tag-mixed{background:rgba(var(--rgb-accent),0.08);color:var(--color-accent);border:1px solid rgba(var(--rgb-accent),0.15);}
.workshop-body h3{font-size:var(--fs-subtitle);font-weight:700;color:var(--color-dark);margin-bottom:6px;}
.workshop-body p{font-size:var(--fs-body);color:var(--color-body);line-height:1.7;}
.workshop-meta{display:flex;align-items:center;justify-content:space-between;margin-top:14px;padding-top:14px;border-top:1px solid rgba(var(--rgb-ink),0.06);}
.workshop-level{font-size:12px;color:var(--color-body);text-transform:uppercase;letter-spacing:0.08em;}
.workshop-cta{font-size:12px;font-weight:700;color:var(--color-dark);letter-spacing:0.06em;text-transform:uppercase;}
/* FEATURED SECTION */
.featured-section{animation:riseUp 0.9s 0.2s cubic-bezier(0.22,1,0.36,1) both;margin-bottom:48px;}.section-title{font-size:var(--fs-section);font-weight:700;color:var(--color-dark);letter-spacing:-0.02em;margin:0 0 var(--space-md);}
.featured-grid{display:grid;grid-template-columns:1.5fr 1fr;gap:20px;}
.featured-main{background:var(--color-surface);border:1px solid rgba(var(--rgb-ink),0.07);border-radius:var(--radius-card);overflow:hidden;box-shadow:var(--shadow-card);}
.featured-main-img{height:260px;background:url('https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?auto=format&fit=crop&w=800&q=80') center/cover;}
.featured-main-body{padding:24px;}
.featured-main-body h2{font-size:var(--fs-card);font-weight:700;color:var(--color-dark);margin-bottom:8px;}
.featured-main-body p{font-size:var(--fs-body);color:var(--color-body);line-height:1.7;margin-bottom:16px;}
.featured-side{display:flex;flex-direction:column;gap:14px;}
.side-card{background:var(--color-surface);border:1px solid rgba(var(--rgb-ink),0.07);border-radius:16px;padding:20px;cursor:pointer;transition:all 0.25s;box-shadow:var(--shadow-card);}
.side-card:hover{transform:translateX(4px);background:rgba(var(--rgb-surface),0.8);}
.side-card h4{font-size:var(--fs-subtitle);font-weight:700;color:var(--color-dark);margin-bottom:4px;}
.side-card p{font-size:var(--fs-body);color:var(--color-body);line-height:1.6;}
.side-card-icon{font-size:20px;margin-bottom:8px;}
/* ROADMAP */
.roadmap-section{animation:riseUp 0.9s 0.3s cubic-bezier(0.22,1,0.36,1) both;}
.roadmap-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;}
.roadmap-item{background:var(--color-surface);border:1px solid rgba(var(--rgb-ink),0.06);border-radius:16px;padding:20px;text-align:center;box-shadow:var(--shadow-card);}
.roadmap-num{font-size:28px;font-weight:300;color:rgba(var(--rgb-ink),0.15);margin-bottom:8px;}
.roadmap-item h4{font-size:var(--fs-subtitle);font-weight:700;color:var(--color-dark);margin-bottom:6px;}
.roadmap-item p{font-size:var(--fs-body);color:var(--color-body);line-height:1.6;}
.roadmap-status{display:inline-block;margin-top:10px;padding:3px 8px;border-radius:999px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;}
.status-live{background:rgba(var(--rgb-accent),0.1);color:var(--color-accent);border:1px solid rgba(var(--rgb-accent),0.2);}
.status-soon{background:rgba(var(--rgb-accent),0.08);color:var(--color-accent);border:1px solid rgba(var(--rgb-accent),0.15);}
.status-planned{background:rgba(var(--rgb-ink),0.05);color:var(--color-body);border:1px solid rgba(var(--rgb-ink),0.1);}
@keyframes riseUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.4}}
[dir="rtl"] .workshops-grid,[dir="rtl"] .featured-grid,[dir="rtl"] .roadmap-grid{direction:rtl;}
[dir="rtl"] .side-card:hover{transform:translateX(-4px);}
[dir="rtl"] .page-header p{text-align:right;}
@media(max-width:1024px){.workshops-grid{grid-template-columns:repeat(2,1fr);}.roadmap-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:768px){.workshops-grid{grid-template-columns:1fr;}.featured-grid{grid-template-columns:1fr;}.roadmap-grid{grid-template-columns:1fr 1fr;}.page{padding:calc(var(--nav-h) + var(--space-md)) var(--space-sm) var(--space-xl);}}
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
    <p id="t-desc">This is where your practice grows. Learn real art styles rooted in Saudi culture. Start with our style guides today. Workshops with Saudi art institutes are on their way.</p>
  </div>

  <!-- WORKSHOPS -->
  <p class="section-title" id="t-ws-title">Upcoming Workshops</p>
  <div class="workshops-grid">
    <a class="workshop-card" href="register.php">
      <div class="workshop-img" style="background-image:url('https://images.unsplash.com/photo-1460661419201-fd4cecdf8a8b?auto=format&fit=crop&w=600&q=80')"></div>
      <div class="workshop-body">
        <span class="workshop-tag tag-watercolor" id="t-tag1">Watercolor</span>
        <h3 id="t-ws1">Desert Light in Watercolor</h3>
        <p id="t-ws1d">Catch the warm light of the Saudi desert. Layer soft washes until the dunes glow.</p>
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
        <p id="t-ws2d">Paint wide horizons and golden evening skies. Learn classic oil techniques, one layer at a time.</p>
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
        <p id="t-ws3d">Fall in love with Arabic letterforms and geometric patterns. Then bring them to life on screen.</p>
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
        <p id="t-ws4d">Draw carved doors and Najdi mud houses in charcoal and ink. Every line keeps a story alive.</p>
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
        <p id="t-ws5d">Our artists are building this library together. Share your first artwork and add your voice.</p>
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
        <p id="t-ws6d">Mix collage, paint and photography. Tell the story of Saudi Arabia today, in your own voice.</p>
        <div class="workshop-meta">
          <span class="workshop-level" id="t-lv6">Coming Soon</span>
          <span class="workshop-cta" id="t-join6">Notify Me →</span>
        </div>
      </div>
    </a>
  </div>

  <!-- FEATURED -->
  <p class="section-title" id="t-feat-title">Training &amp; Resources</p>
  <div class="featured-section">
    <div class="featured-grid">
      <div class="featured-main">
        <div class="featured-main-img"></div>
        <div class="featured-main-body">
          <h2 id="t-featH">Workshops with Saudi Art Institutes</h2>
          <p id="t-featP">We are partnering with Saudi art institutes. Soon you can learn with real teachers, in person and online.</p>
          <a class="btn btn-dark" href="register.php" id="t-featBtn">Notify Me</a>
        </div>
      </div>
      <div class="featured-side">
        <div class="side-card" onclick="location.href='explore.php'">
          <div class="side-card-icon">🎨</div>
          <h4 id="t-sc1">Explore 12 Art Styles</h4>
          <p id="t-sc1d">Watercolor, oil, charcoal, mixed media and more. Find the style that feels like yours.</p>
        </div>
        <div class="side-card" onclick="location.href='explore.php'">
          <div class="side-card-icon">📚</div>
          <h4 id="t-sc2">Art Style Guides</h4>
          <p id="t-sc2d">A friendly guide for each of the 12 styles. From watercolor and oil to pastel and digital art.</p>
        </div>
        <div class="side-card" onclick="location.href='register.php'">
          <div class="side-card-icon">🏆</div>
          <h4 id="t-sc3">Monthly Art Challenges (Coming Soon)</h4>
          <p id="t-sc3d">A new theme each month, inspired by Saudi culture. Share your work and cheer on fellow artists.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- ROADMAP -->
  <p class="section-title" id="t-road-title">Future Tools Roadmap</p>
  <div class="roadmap-section">
    <div class="roadmap-grid">
      <div class="roadmap-item">
        <div class="roadmap-num">01</div>
        <h4 id="t-r1">Color Theory Guides</h4>
        <p id="t-r1d">Learn to mix colors that sing together. Simple, hands-on exercises for every medium.</p>
        <span class="roadmap-status status-planned" id="t-rs1">Planned</span>
      </div>
      <div class="roadmap-item">
        <div class="roadmap-num">02</div>
        <h4 id="t-r2">Brush Simulator</h4>
        <p id="t-r2d">Digital brushes that feel like real watercolor, oil and charcoal.</p>
        <span class="roadmap-status status-soon" id="t-rs2">Coming Soon</span>
      </div>
      <div class="roadmap-item">
        <div class="roadmap-num">03</div>
        <h4 id="t-r3">Texture Scanner</h4>
        <p id="t-r3d">Capture real textures with your phone. Then use them in your art.</p>
        <span class="roadmap-status status-planned" id="t-rs3">Planned</span>
      </div>
      <div class="roadmap-item">
        <div class="roadmap-num">04</div>
        <h4 id="t-r4">Art Portfolio</h4>
        <p id="t-r4d">A personal gallery page where every artist shares their journey.</p>
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
    desc:'This is where your practice grows. Learn real art styles rooted in Saudi culture. Start with our style guides today. Workshops with Saudi art institutes are on their way.',
    wsTitle:'Upcoming Workshops',featTitle:'Training & Resources',roadTitle:'Tools We Are Building',
    tag1:'Watercolor',ws1:'Desert Light in Watercolor',ws1d:'Catch the warm light of the Saudi desert. Layer soft washes until the dunes glow.',lv1:'Coming Soon',join1:'Notify Me →',
    tag2:'Oil Painting',ws2:'Desert Landscapes in Oil',ws2d:'Paint wide horizons and golden evening skies. Learn classic oil techniques, one layer at a time.',lv2:'Coming Soon',join2:'Notify Me →',
    tag3:'Digital Art',ws3:'Digital Arabic Calligraphy',ws3d:'Fall in love with Arabic letterforms and geometric patterns. Then bring them to life on screen.',lv3:'Coming Soon',join3:'Notify Me →',
    tag4:'Charcoal',ws4:'Saudi Heritage Drawing',ws4d:'Draw carved doors and Najdi mud houses in charcoal and ink. Every line keeps a story alive.',lv4:'Coming Soon',join4:'Notify Me →',
    tag5:'Community',ws5:'Community Resource Library',ws5d:'Our artists are building this library together. Share your first artwork and add your voice.',lv5:'Coming Soon',join5:'Contribute →',
    tag6:'Mixed Media',ws6:'Contemporary Saudi Expression',ws6d:'Mix collage, paint and photography. Tell the story of Saudi Arabia today, in your own voice.',lv6:'Coming Soon',join6:'Notify Me →',
    featH:'Workshops with Saudi Art Institutes',featP:'We are partnering with Saudi art institutes. Soon you can learn with real teachers, in person and online.',featBtn:'Notify Me',
    sc1:'Explore 12 Art Styles',sc1d:'Watercolor, oil, charcoal, mixed media and more. Find the style that feels like yours.',
    sc2:'Art Style Guides',sc2d:'A friendly guide for each of the 12 styles. From watercolor and oil to pastel and digital art.',
    sc3:'Monthly Art Challenges (Coming Soon)',sc3d:'A new theme each month, inspired by Saudi culture. Share your work and cheer on fellow artists.',
    r1:'Color Theory Guides',r1d:'Learn to mix colors that sing together. Simple, hands-on exercises for every medium.',rs1:'Planned',
    r2:'Brush Simulator',r2d:'Digital brushes that feel like real watercolor, oil and charcoal.',rs2:'Coming Soon',
    r3:'Texture Scanner',r3d:'Capture real textures with your phone. Then use them in your art.',rs3:'Planned',
    r4:'Art Portfolio',r4d:'A personal gallery page where every artist shares their journey.',rs4:'Planned'},
  ar:{dir:'rtl',lb:'English',
    studio:'الاستوديو',explore:'استكشف',community:'المجتمع',support:'الدعم',login:'تسجيل الدخول',
    badge:'تعلّم وتدرّب',h1:'<em>استوديو</em> أويلي',
    desc:'هنا تنمو موهبتك يوماً بعد يوم. تعلّم أساليب فنية أصيلة من روح الثقافة السعودية. ابدأ اليوم بأدلة الأساليب. وورش العمل مع معاهد الفنون السعودية في الطريق إليك.',
    wsTitle:'ورش العمل القادمة',featTitle:'التدريب والموارد',roadTitle:'أدوات نعمل عليها',
    tag1:'ألوان مائية',ws1:'ضوء الصحراء بالألوان المائية',ws1d:'التقط دفء ضوء الصحراء السعودية. طبقة بعد طبقة، حتى تتوهّج الكثبان.',lv1:'قريباً',join1:'أبلغني →',
    tag2:'رسم زيتي',ws2:'مناظر الصحراء بالرسم الزيتي',ws2d:'ارسم الآفاق الواسعة وسماء الغروب الذهبية. تعلّم تقنيات الزيت الكلاسيكية خطوة بخطوة.',lv2:'قريباً',join2:'أبلغني →',
    tag3:'فن رقمي',ws3:'الخط العربي الرقمي',ws3d:'اعشق الحرف العربي والزخارف الهندسية. ثم امنحها الحياة على الشاشة.',lv3:'قريباً',join3:'أبلغني →',
    tag4:'فحم',ws4:'رسم التراث السعودي',ws4d:'ارسم الأبواب المنقوشة والبيوت النجدية بالفحم والحبر. كل خط يحفظ حكاية.',lv4:'قريباً',join4:'أبلغني →',
    tag5:'المجتمع',ws5:'مكتبة الموارد المجتمعية',ws5d:'فنانونا يبنون هذه المكتبة معاً. شارك عملك الأول وأضف صوتك.',lv5:'قريباً',join5:'ساهم →',
    tag6:'وسائط مختلطة',ws6:'التعبير السعودي المعاصر',ws6d:'امزج الكولاج والألوان والتصوير. احكِ قصة السعودية اليوم بصوتك أنت.',lv6:'قريباً',join6:'أبلغني →',
    featH:'ورش عمل مع معاهد الفنون السعودية',featP:'نبني شراكات مع معاهد الفنون السعودية. قريباً تتعلّم مع معلمين حقيقيين، حضورياً وعبر الإنترنت.',featBtn:'أبلغني',
    sc1:'استكشف 12 أسلوباً فنياً',sc1d:'الألوان المائية والزيت والفحم والوسائط المختلطة وغيرها. اعثر على الأسلوب الذي يشبهك.',
    sc2:'أدلة الأساليب الفنية',sc2d:'دليل ودود لكل أسلوب من الأساليب الاثني عشر. من الألوان المائية والزيت إلى الباستيل والفن الرقمي.',
    sc3:'تحديات فنية شهرية (قريباً)',sc3d:'موضوع جديد كل شهر من وحي الثقافة السعودية. شارك عملك وشجّع زملاءك الفنانين.',
    r1:'أدلة نظرية الألوان',r1d:'تعلّم مزج ألوان تتناغم معاً. تمارين بسيطة بيدك لكل وسيط.',rs1:'مخطط',
    r2:'محاكي الفرشاة',r2d:'فرش رقمية تشبه الألوان المائية والزيت والفحم الحقيقية.',rs2:'قريباً',
    r3:'ماسح الخامات',r3d:'التقط خامات حقيقية بهاتفك. ثم استخدمها في أعمالك.',rs3:'مخطط',
    r4:'ملف الفنان',r4d:'صفحة معرض شخصية يروي فيها كل فنان رحلته.',rs4:'مخطط'}
};
let L='en';
const NAV={
  en:{lb:'العربية',studio:'The Studio',community:'Community',marketplace:'Gallery',explore:'Explore Art Styles',support:'Support',
    s1:'Watercolor Workshop',s2:'Oil Painting Studio',s3:'Digital Art Lab',s4:'Charcoal & Ink',
    c1:'Artist Chat Room',c2:'Meet Fellow Artists',c3:'Share Your Work',c4:'Learn Together',
    sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',
    login:'Sign In',reg:'Join Free'},
  ar:{lb:'English',studio:'الاستوديو',community:'المجتمع',marketplace:'المعرض',explore:'استكشف أساليب الرسم',support:'الدعم',
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
{const on=()=>v.classList.add('on');if(v.readyState>=3)on();v.addEventListener('canplay',on,{once:true});v.addEventListener('playing',on,{once:true});}
v.play().catch(()=>{});
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
