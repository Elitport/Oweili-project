<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en" dir="ltr" id="html">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Community · Oweili</title>
<link rel="stylesheet" href="/assets/css/oweili.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:#fff;font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;overflow-x:hidden;}
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
.page-header{margin-bottom:48px;animation:riseUp 0.9s cubic-bezier(0.22,1,0.36,1) forwards;opacity:0;}
.page-badge{display:inline-flex;align-items:center;gap:8px;padding:5px 14px;border-radius:999px;margin-bottom:16px;background:rgba(0,100,255,0.05);border:1px solid rgba(0,100,255,0.15);font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:#0066ff;}
.pulse-dot{width:6px;height:6px;border-radius:50%;background:#0066ff;box-shadow:0 0 8px rgba(0,100,255,0.5);animation:pulse 2s infinite;}
.page-header h1{font-size:clamp(28px,4vw,48px);font-weight:300;color:#111;margin-bottom:12px;letter-spacing:-0.02em;}
.page-header h1 em{font-style:normal;font-weight:700;background:linear-gradient(90deg,#0066ff,#0066ff,#ff0055);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.page-header p{font-size:15px;color:rgba(0,0,0,0.6);line-height:1.8;max-width:580px;}
.section-title{font-size:12px;text-transform:uppercase;letter-spacing:0.16em;color:rgba(0,0,0,0.45);font-weight:700;margin-bottom:20px;}

/* STATS */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:48px;animation:riseUp 0.9s 0.05s cubic-bezier(0.22,1,0.36,1) both;}
.stat-card{background:rgba(255,255,255,0.6);backdrop-filter:blur(16px);border:1px solid rgba(0,0,0,0.07);border-radius:16px;padding:24px;text-align:center;}
.stat-num{font-size:32px;font-weight:700;background:linear-gradient(90deg,#0066ff,#0066ff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:4px;}
.stat-label{font-size:12px;color:rgba(0,0,0,0.5);text-transform:uppercase;letter-spacing:0.1em;}

/* CHAT CTA */
.chat-cta{background:rgba(255,255,255,0.6);backdrop-filter:blur(20px);border:1px solid rgba(0,100,255,0.12);border-radius:24px;padding:40px;display:flex;align-items:center;justify-content:space-between;gap:24px;margin-bottom:48px;animation:riseUp 0.9s 0.1s cubic-bezier(0.22,1,0.36,1) both;}
.chat-cta-left h2{font-size:22px;font-weight:700;color:#111;margin-bottom:8px;}
.chat-cta-left p{font-size:13px;color:rgba(0,0,0,0.6);line-height:1.8;max-width:480px;}
.chat-cta-right{flex-shrink:0;}
.live-badge{display:flex;align-items:center;gap:6px;margin-bottom:14px;font-size:12px;color:#0066ff;font-weight:600;letter-spacing:0.08em;}
.live-dot{width:8px;height:8px;border-radius:50%;background:#0066ff;box-shadow:0 0 8px rgba(0,102,255,0.5);animation:pulse 2s infinite;}
.btn-p{display:inline-block;padding:13px 28px;border-radius:999px;background:#111;color:#fff;font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;border:none;cursor:pointer;transition:all 0.25s;text-decoration:none;}
.btn-p:hover{background:#333;transform:translateY(-2px);}
.btn-blue{background:linear-gradient(90deg,#0066ff,#0066ff);border:none;}
.btn-blue:hover{opacity:0.9;transform:translateY(-2px);}

/* COMMUNITY CARDS */
.community-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:20px;margin-bottom:48px;animation:riseUp 0.9s 0.15s cubic-bezier(0.22,1,0.36,1) both;}
.comm-card{background:rgba(255,255,255,0.6);backdrop-filter:blur(16px);border:1px solid rgba(0,0,0,0.07);border-radius:20px;padding:28px;cursor:pointer;transition:all 0.3s;text-decoration:none;display:block;}
.comm-card:hover{transform:translateY(-4px);box-shadow:0 20px 40px rgba(0,0,0,0.08);}
.comm-icon{font-size:32px;margin-bottom:14px;}
.comm-card h3{font-size:17px;font-weight:700;color:#111;margin-bottom:8px;}
.comm-card p{font-size:13px;color:rgba(0,0,0,0.6);line-height:1.75;margin-bottom:16px;}
.comm-link{font-size:12px;font-weight:700;color:#0066ff;letter-spacing:0.06em;text-transform:uppercase;}

/* INSPIRATION */
.inspo-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;animation:riseUp 0.9s 0.2s cubic-bezier(0.22,1,0.36,1) both;}
.inspo-card{background:rgba(255,255,255,0.6);backdrop-filter:blur(12px);border:1px solid rgba(0,0,0,0.06);border-radius:16px;overflow:hidden;cursor:pointer;transition:all 0.3s;}
.inspo-card:hover{transform:translateY(-3px);box-shadow:0 16px 32px rgba(0,0,0,0.08);}
.inspo-img{height:140px;background-size:cover;background-position:center;transition:transform 0.5s;}
.inspo-card:hover .inspo-img{transform:scale(1.05);}
.inspo-body{padding:16px;}
.inspo-body h4{font-size:12px;font-weight:700;color:#111;margin-bottom:4px;}
.inspo-body p{font-size:12px;color:rgba(0,0,0,0.5);}

@keyframes riseUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.4}}
[dir="rtl"] .stats-row,[dir="rtl"] .community-grid,[dir="rtl"] .inspo-grid{direction:rtl;}

[dir="rtl"] .page-header p{text-align:right;}
@media(max-width:1024px){.stats-row{grid-template-columns:repeat(2,1fr);}.inspo-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:768px){.community-grid{grid-template-columns:1fr;}.stats-row{grid-template-columns:1fr 1fr;}.chat-cta{flex-direction:column;}.page{padding:90px 16px 32px;}.topnav{padding:10px 14px;}.nav-logo span{display:none;}}
</style>
</head>
<body>
<div class="bg-wrap">
  <video class="bg-video" id="vid" autoplay loop muted playsinline src="bg.mp4"></video>
</div>
<div class="overlay"></div>
<?php include __DIR__ . "/nav.php"; ?>
<div class="page" id="pg">
  <div class="page-header">
    <div class="page-badge"><div class="pulse-dot"></div><span id="t-badge">Artists Network</span></div>
    <h1 id="t-h1">The <em>Artist Community</em></h1>
    <p id="t-desc">Connect with fellow artists, share your work, learn together, and grow as part of a vibrant creative community powered by passion for nature and art.</p>
  </div>

  <div class="stats-row">
    <div class="stat-card"><div class="stat-num" id="s-artists">N/A</div><div class="stat-label" id="t-s1">Registered Artists</div></div>
    <div class="stat-card"><div class="stat-num" id="s-msgs">N/A</div><div class="stat-label" id="t-s2">Messages Shared</div></div>
    <div class="stat-card"><div class="stat-num">12</div><div class="stat-label" id="t-s3">Art Styles</div></div>
    <div class="stat-card"><div class="stat-num">🇸🇦</div><div class="stat-label" id="t-s4">Saudi Community</div></div>
  </div>

  <p class="section-title" id="t-comm-title">COMMUNITY SPACES</p>
  <div class="community-grid">
    <a class="comm-card" href="register.php">
      <div class="comm-icon">🎨</div>
      <h3 id="t-c2">Professional Artists Guild</h3>
      <p id="t-c2d">An exclusive space for advanced artists to collaborate on projects, share expertise, and mentor emerging talents in the community.</p>
      <span class="comm-link" id="t-c2l">Apply to Join →</span>
    </a>
    <a class="comm-card" href="register.php">
      <div class="comm-icon">✨</div>
      <h3 id="t-c3">Daily Inspiration Hub</h3>
      <p id="t-c3d">Get a fresh dose of creative inspiration every day — featured artworks, nature prompts, color palettes, and technique spotlights.</p>
      <span class="comm-link" id="t-c3l">Explore →</span>
    </a>
    <a class="comm-card" href="register.php">
      <div class="comm-icon">🏆</div>
      <h3 id="t-c4">Join as Art Instructor</h3>
      <p id="t-c4d">Are you an experienced artist? Apply to become an instructor and share your knowledge with our growing community of passionate creators.</p>
      <span class="comm-link" id="t-c4l">Apply Now →</span>
    </a>
  </div>

  <p class="section-title" id="t-inspo-title">DAILY INSPIRATION</p>
  <div class="inspo-grid">
    <div class="inspo-card"><div class="inspo-img" style="background-image:url('https://images.unsplash.com/photo-1448375240586-882707db888b?auto=format&fit=crop&w=600&q=80')"></div><div class="inspo-body"><h4 id="t-i1">Jungle Light Studies</h4><p id="t-i1d">Capture the dappled light filtering through dense canopy</p></div></div>
    <div class="inspo-card"><div class="inspo-img" style="background-image:url('https://images.unsplash.com/photo-1505118380757-91f5f5632de0?auto=format&fit=crop&w=600&q=80')"></div><div class="inspo-body"><h4 id="t-i2">Ocean Movement</h4><p id="t-i2d">Study the rhythm and energy of waves in motion</p></div></div>
    <div class="inspo-card"><div class="inspo-img" style="background-image:url('https://images.unsplash.com/photo-1502082553048-f009c37129b9?auto=format&fit=crop&w=600&q=80')"></div><div class="inspo-body"><h4 id="t-i3">Ancient Trees</h4><p id="t-i3d">Explore the texture and wisdom of centuries-old forests</p></div></div>
  </div>
</div>

<script>
const T={
  en:{dir:'ltr',lb:'العربية',studio:'The Studio',explore:'Explore',community:'Community',support:'Support',login:'Sign In',
    badge:'Artists Network',h1:'The <em>Artist Community</em>',desc:'Connect with fellow artists, share your work, learn together, and grow as part of a vibrant creative community powered by passion for nature and art.',
    s1:'Registered Artists',s2:'Messages Shared',s3:'Art Styles',s4:'Saudi Community',
    ctaH:'Join the Artist Chat Room',ctaP:'Talk to fellow artists in real time. Share techniques, get feedback, ask questions, and build friendships with creators who share your passion for art and nature.',live:'Live Now',ctaBtn:'Enter Chat Room',
    commTitle:'COMMUNITY SPACES',
    c1:'Artist Chat Room',c1d:'A live chat room where artists from across Saudi Arabia connect, share their work, and inspire each other in real time.',c1l:'Enter Room →',
    c2:'Professional Artists Guild',c2d:'An exclusive space for advanced artists to collaborate on projects, share expertise, and mentor emerging talents.',c2l:'Apply to Join →',
    c3:'Daily Inspiration Hub',c3d:'Get a fresh dose of creative inspiration every day — featured artworks, nature prompts, color palettes, and technique spotlights.',c3l:'Explore →',
    c4:'Join as Art Instructor',c4d:'Apply to become an instructor and share your knowledge with our growing community of passionate creators.',c4l:'Apply Now →',
    inspoTitle:'DAILY INSPIRATION',
    i1:'Jungle Light Studies',i1d:'Capture the dappled light filtering through dense canopy',
    i2:'Ocean Movement',i2d:'Study the rhythm and energy of waves in motion',
    i3:'Ancient Trees',i3d:'Explore the texture and wisdom of centuries-old forests'},
  ar:{dir:'rtl',lb:'English',studio:'الاستوديو',explore:'استكشف',community:'المجتمع',support:'الدعم',login:'تسجيل الدخول',
    badge:'شبكة الفنانين',h1:'<em>مجتمع</em> الفنانين',desc:'تواصل مع فنانين آخرين وشارك أعمالك وتعلّم معاً وانمُ كجزء من مجتمع إبداعي نابض بالحياة مدفوع بشغف الطبيعة والفن.',
    s1:'الفنانين المسجلين',s2:'الرسائل المتبادلة',s3:'أساليب فنية',s4:'المجتمع السعودي',
    ctaH:'انضم إلى غرفة محادثة الفنانين',ctaP:'تحدّث مع فنانين آخرين في الوقت الفعلي. شارك التقنيات واحصل على ملاحظات واطرح أسئلتك وابنِ صداقات مع المبدعين.',live:'مباشر الآن',ctaBtn:'ادخل غرفة المحادثة',
    commTitle:'فضاءات المجتمع',
    c1:'غرفة محادثة الفنانين',c1d:'غرفة محادثة مباشرة حيث يتواصل الفنانون من جميع أنحاء المملكة العربية السعودية ويشاركون أعمالهم.',c1l:'ادخل الغرفة →',
    c2:'رابطة الرسامين المحترفين',c2d:'مساحة حصرية للفنانين المتقدمين للتعاون في المشاريع ومشاركة الخبرات وإرشاد المواهب الناشئة.',c2l:'تقدّم للانضمام →',
    c3:'مركز الإلهام اليومي',c3d:'احصل على جرعة إبداعية يومية — أعمال مميزة وموضوعات طبيعية ولوحات ألوان وأضواء تقنية.',c3l:'استكشف →',
    c4:'انضم كمدرب فني',c4d:'هل أنت فنان متمرس؟ تقدّم لتصبح مدرباً وشارك معرفتك مع مجتمعنا المتنامي من المبدعين.',c4l:'تقدّم الآن →',
    inspoTitle:'الإلهام اليومي',
    i1:'دراسات ضوء الأدغال',i1d:'التقط الضوء المتناثر عبر الغابة الكثيفة',
    i2:'حركة المحيط',i2d:'ادرس إيقاع وطاقة الأمواج في حركتها',
    i3:'الأشجار القديمة',i3d:'استكشف ملمس وحكمة الغابات التي عمرها قرون'}
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
  const keys=['badge','h1','desc','s1','s2','s3','s4','live','c1','c1d','c1l','c2','c2d','c2l','c3','c3d','c3l','c4','c4d','c4l','i1','i1d','i2','i2d','i3','i3d'];
  keys.forEach(k=>{const el=document.getElementById('t-'+k);if(el)el.innerHTML=t[k];});
  // elements whose ids differ from their T keys
  const setH=(id,v)=>{const e=document.getElementById(id);if(e)e.innerHTML=v;};
  setH('t-cta-h',t.ctaH);
  setH('t-cta-p',t.ctaP);
  setH('t-cta-btn',t.ctaBtn);
  setH('t-comm-title',t.commTitle);
  setH('t-inspo-title',t.inspoTitle);
}
function tgl(){L=L==='en'?'ar':'en';apply(L);try{localStorage.setItem('lang',L);}catch(e){}}
try{var _s=localStorage.getItem('lang');if(_s==='ar'||_s==='en')L=_s;}catch(e){}
apply(L);

// Community stats
(function(){
  var a=document.getElementById('s-artists');if(a)a.textContent='50+';
  var m=document.getElementById('s-msgs');if(m)m.textContent='200+';
})();

const v=document.getElementById('vid');
v.addEventListener('canplay',()=>v.classList.add('on'),{once:true});
v.play().catch(()=>{});
</script>
</body>
</html>
