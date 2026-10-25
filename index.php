<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Oweili · منصة الفن السعودية</title>
<link rel="stylesheet" href="/assets/css/oweili.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:#ffffff;font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;overflow-x:hidden;}

/* ── BACKGROUND ── */
.bg-wrap{position:fixed;inset:0;z-index:0;overflow:hidden;}
.bg-video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0;transition:opacity 2s ease;z-index:2;}
.bg-video.on{opacity:0.95;}
.overlay{position:fixed;inset:0;z-index:3;pointer-events:none;background:linear-gradient(160deg,rgba(255,255,255,0.2) 0%,rgba(255,255,255,0) 50%,rgba(255,255,255,0.4) 100%);}
.overlay2{position:fixed;inset:0;z-index:3;pointer-events:none;background:radial-gradient(ellipse at 20% 50%,rgba(0,100,255,0.06) 0%,transparent 60%);}

/* ── TOP NAV ── */
.topnav{
  position:fixed;top:0;left:0;right:0;z-index:200;
  display:flex;align-items:center;justify-content:space-between;
  padding:0 16px;height:56px;
  background:rgba(255,255,255,0.6);
  backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);
  border-bottom:1px solid rgba(0,0,0,0.05);
}
[dir="rtl"] .nav-center{flex-direction:row-reverse;}
[dir="rtl"] .dropdown{leftauto;right:0;text-align:right;}
[dir="rtl"] .dropdown a{flex-direction:row-reverse;}
.nav-logo{display:flex;align-items:center;gap:6px;text-decoration:none;color:#111111;flex-shrink:0;}
.nav-logo svg{color:#ff0055;}
.nav-logo span{font-size:12px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;}

/* ── NAV CENTER (dropdown menu) ── */
.nav-center{display:flex;align-items:stretch;gap:0;height:100%;}

.nav-item{position:relative;display:flex;align-items:center;}
.nav-item > a{
  display:flex;align-items:center;gap:4px;
  padding:0 12px;height:100%;
  font-size:10px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;
  color:rgba(0,0,0,0.65);text-decoration:none;
  transition:color 0.2s;white-space:nowrap;
  border-bottom:2px solid transparent;
}
.nav-item > a:hover{color:#000;}
.nav-item:hover > a{color:#0055ff;border-bottom-color:#0055ff;}

.nav-item > a .chevron{
  width:10px;height:10px;fill:none;stroke:currentColor;stroke-width:2;
  stroke-linecap:round;stroke-linejoin:round;
  transition:transform 0.2s;flex-shrink:0;
}
.nav-item:hover > a .chevron{transform:rotate(180deg);}

/* dropdown */
.dropdown{
  position:absolute;top:100%;left:0;min-width:200px;
  background:rgba(255,255,255,0.96);
  backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);
  border:1px solid rgba(0,0,0,0.07);
  border-radius:14px;box-shadow:0 16px 40px rgba(0,0,0,0.1);
  padding:8px;
  opacity:0;visibility:hidden;transform:translateY(8px);
  transition:opacity 0.2s,transform 0.2s,visibility 0.2s;
  pointer-events:none;
}
[dir="rtl"] .dropdown{leftauto;right:0;}
.nav-item:hover .dropdown{opacity:1;visibility:visible;transform:translateY(0);pointer-events:auto;}

.dropdown a{
  display:flex;align-items:center;gap:10px;
  padding:9px 12px;border-radius:9px;
  font-size:12px;color:rgba(0,0,0,0.7);text-decoration:none;
  transition:background 0.15s,color 0.15s;
}
.dropdown a:hover{background:rgba(0,100,255,0.07);color:#0055ff;}
.dropdown a .d-icon{width:14px;height:14px;fill:currentColor;opacity:0.6;flex-shrink:0;}

/* ── NAV RIGHT ── */
.nav-right{display:flex;align-items:center;gap:6px;flex-shrink:0;}

.nav-btn{
  padding:5px 12px;border-radius:999px;font-size:10px;font-weight:600;
  letter-spacing:0.04em;text-transform:uppercase;cursor:pointer;
  text-decoration:none;display:inline-flex;align-items:center;gap:4px;
  transition:all 0.22s;border:1px solid rgba(0,0,0,0.1);
  background:rgba(0,0,0,0.04);color:rgba(0,0,0,0.8);
}
.nav-btn:hover{background:rgba(0,0,0,0.08);color:#000;}
.nav-btn.primary{background:rgba(0,100,255,0.1);border-color:rgba(0,100,255,0.2);color:#0066ff;}
.nav-btn.primary:hover{background:rgba(0,100,255,0.18);}

.lang-btn{
  padding:4px 10px;border-radius:999px;font-size:10px;font-weight:600;
  letter-spacing:0.04em;cursor:pointer;
  border:1px solid rgba(0,0,0,0.1);
  background:rgba(255,255,255,0.7);color:rgba(0,0,0,0.7);
  backdrop-filter:blur(8px);transition:all 0.22s;
}
.lang-btn:hover{background:#111111;color:#fff;}

/* ── PAGE ── */
.page{position:relative;z-index:10;width:100%;max-width:1280px;margin:0 auto;padding:90px 32px 48px;min-height:100vh;display:flex;flex-direction:column;justify-content:center;gap:48px;}

/* ── HERO ── */
.hero{max-width:600px;animation:riseUp 1.1s cubic-bezier(0.22,1,0.36,1) forwards;opacity:0;}
[dir="ltr"] .hero{margin-right:auto;}
[dir="rtl"] .hero{margin-left:auto;text-align:right;}

.hero-badge{display:inline-flex;align-items:center;gap:8px;padding:5px 14px;border-radius:999px;margin-bottom:22px;background:rgba(255,0,85,0.05);border:1px solid rgba(255,0,85,0.15);font-size:10px;letter-spacing:0.14em;text-transform:uppercase;color:#ff0055;}
.pulse-dot{width:6px;height:6px;border-radius:50%;background:#ff0055;box-shadow:0 0 10px rgba(255,0,85,0.5);animation:pulse 2s infinite;}

.hero h1{font-size:clamp(28px,4.8vw,52px);font-weight:300;line-height:1.15;color:#111111;margin-bottom:16px;letter-spacing:-0.02em;}
.hero h1 em{font-style:normal;font-weight:700;background:linear-gradient(90deg,#ff0055,#0066ff,#aa00ff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.hero p{font-size:14px;line-height:1.82;color:rgba(15,15,15,0.75);margin-bottom:30px;}
.hero-btns{display:flex;gap:14px;flex-wrap:wrap;}
[dir="rtl"] .hero-btns{flex-direction:row-reverse;}

.btn-p{padding:13px 28px;border-radius:999px;background:#111111;color:#ffffff;font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;border:none;cursor:pointer;transition:all 0.25s ease;box-shadow:0 10px 25px rgba(0,0,0,0.08);}
.btn-p:hover{background:#333333;transform:translateY(-2px);}
.btn-g{padding:13px 28px;border-radius:999px;background:rgba(255,255,255,0.6);color:#111111;font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;border:1px solid rgba(0,0,0,0.12);cursor:pointer;transition:all 0.25s ease;backdrop-filter:blur(6px);}
.btn-g:hover{background:rgba(255,255,255,0.9);transform:translateY(-2px);}

/* ── MODAL ── */
.modal-bg{position:fixed;inset:0;z-index:500;background:rgba(255,255,255,0.85);backdrop-filter:blur(12px);display:none;align-items:center;justify-content:center;padding:20px;}
.modal-bg.open{display:flex;}
.modal{background:#ffffff;border:1px solid rgba(0,100,255,0.1);border-radius:24px;max-width:560px;width:100%;overflow:hidden;position:relative;animation:riseUp 0.4s ease;box-shadow:0 30px 70px rgba(0,0,0,0.12);}
.modal-img{width:100%;height:220px;object-fit:cover;display:block;}
.modal-body{padding:28px;}
.modal-tag{display:inline-block;padding:3px 10px;border-radius:999px;background:rgba(0,100,255,0.08);border:1px solid rgba(0,100,255,0.15);font-size:10px;color:#0055ff;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:12px;}
.modal-body h2{font-size:20px;font-weight:700;color:#111111;margin-bottom:10px;}
.modal-body p{font-size:13px;color:rgba(0,0,0,0.7);line-height:1.8;margin-bottom:18px;}
.modal-tools{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px;}
.modal-tool{padding:4px 10px;border-radius:8px;background:rgba(0,0,0,0.04);border:1px solid rgba(0,0,0,0.06);font-size:11px;color:rgba(0,0,0,0.7);}
.modal-close{position:absolute;top:14px;right:14px;width:32px;height:32px;border-radius:50%;background:#ffffff;border:1px solid rgba(0,0,0,0.1);color:#111111;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background 0.2s;}
.modal-close:hover{background:rgba(255,0,0,0.1);color:red;}

@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:0.4;transform:scale(0.8)}}
@keyframes riseUp{from{opacity:0;transform:translateY(26px);}to{opacity:1;transform:translateY(0);}}

/* ── MOBILE ── */
@media(max-width:1024px){.nav-center{display:none;}}
@media(max-width:768px){.gallery-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:560px){.gallery-grid{grid-template-columns:1fr 1fr;}.page{padding:75px 16px 24px;}.topnav{padding:0 14px;}.nav-logo span{display:none;}}

/* ── WELCOME SPLASH ── */
#splash{position:fixed;inset:0;z-index:9999;background:#ffffff;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:20px;opacity:0;transition:opacity 0.8s ease;}
#splash.show{opacity:1;}
#splash.hide{opacity:0;pointer-events:none;}
#splash .sp-logo{width:96px;height:96px;transform:scale(0.8);opacity:0;transition:transform 1s cubic-bezier(0.22,1,0.36,1),opacity 1s ease;}
#splash.show .sp-logo{transform:scale(1);opacity:1;}
#splash .sp-word{font-size:24px;font-weight:800;letter-spacing:0.32em;text-transform:uppercase;padding-inline-start:0.32em;background:linear-gradient(135deg,#ff0055,#0066ff,#aa00ff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;opacity:0;transition:opacity 1s ease 0.15s;}
#splash.show .sp-word{opacity:1;}
#splash .sp-welcome{font-size:14px;letter-spacing:0.05em;color:rgba(0,0,0,0.55);opacity:0;transition:opacity 1s ease 0.3s;}
#splash.show .sp-welcome{opacity:1;}
</style>
</head>
<body>

<!-- WELCOME SPLASH (once per session) -->
<div id="splash" aria-hidden="true">
  <svg class="sp-logo" viewBox="0 0 24 24">
    <defs><linearGradient id="spGrad" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#ff0055"/><stop offset="50%" stop-color="#0066ff"/><stop offset="100%" stop-color="#aa00ff"/>
    </linearGradient></defs>
    <path fill="url(#spGrad)" d="M12 0C12 6 18 12 24 12C18 12 12 18 12 24C12 18 6 12 0 12C6 12 12 6 12 0Z"/>
  </svg>
  <div class="sp-word">OWEILI</div>
  <div class="sp-welcome" id="sp-welcome">Welcome to Oweili</div>
</div>
<script>
/* Luxury welcome splash: once per browser session, bilingual, gentle scale + fade. */
(function(){
  var sp=document.getElementById('splash');
  if(!sp) return;
  var seen=null; try{seen=sessionStorage.getItem('oweili_splash');}catch(e){}
  if(seen){ if(sp.parentNode) sp.parentNode.removeChild(sp); return; }
  try{sessionStorage.setItem('oweili_splash','1');}catch(e){}
  var ar=false; try{ar=localStorage.getItem('lang')==='ar';}catch(e){}
  var w=document.getElementById('sp-welcome');
  if(ar){ w.textContent='أهلاً بك في أويلي'; sp.setAttribute('dir','rtl'); }
  else { w.textContent='Welcome to Oweili'; }
  requestAnimationFrame(function(){ requestAnimationFrame(function(){ sp.classList.add('show'); }); });
  setTimeout(function(){
    sp.classList.remove('show'); sp.classList.add('hide');
    setTimeout(function(){ if(sp.parentNode) sp.parentNode.removeChild(sp); }, 900);
  }, 3000);
})();
</script>

<div class="bg-wrap">
  <video class="bg-video" id="vid" autoplay loop muted playsinline src="bg.mp4"></video>
</div>
<div class="overlay"></div>
<div class="overlay2"></div>

<?php include 'nav.php'; ?>

<div class="page" id="pg">
  <div class="hero">
    <div class="hero-badge"><div class="pulse-dot"></div><span id="t-badge">Saudi Art Community · مجتمع الفن السعودي</span></div>
    <h1 id="t-h1">Where artists<br><em>come alive</em></h1>
    <p id="t-desc">Discover, learn, and sell your artwork. Oweili is Saudi Arabia's bilingual art platform — connecting artists, collectors, and art lovers in one creative space.</p>
    <div class="hero-btns">
      <button class="btn-p" onclick="location.href='/register.php'" id="t-b1">Start Creating Free</button>
      <button class="btn-g" onclick="location.href='/explore.php'" id="t-b2">Explore Art Styles</button>
    </div>
  </div>
</div>

<div class="modal-bg" id="modal" onclick="closeModal(event)">
  <div class="modal" id="modal-box">
    <button class="modal-close" onclick="closeModal()">✕</button>
    <img id="m-img" src="" alt="" class="modal-img">
    <div class="modal-body">
      <span class="modal-tag" id="m-tag"></span>
      <h2 id="m-title"></h2>
      <p id="m-desc"></p>
      <div class="modal-tools" id="m-tools"></div>
      <a class="btn-p" href="/register.php" style="display:inline-block;text-decoration:none;font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;" id="m-cta">Start This Style</a>
    </div>
  </div>
</div>

<script src="/art_styles.js"></script>
<script>
const T = {
  en:{dir:'ltr',lb:'العربية',
    badge:'Digital Ink Studio',
    h1:'Where art meets<br><em>the living wild</em>',
    desc:"Step inside the jungle. Paint with the rhythm of ocean waves and the breath of ancient forests. A fully immersive creative sanctuary built for artists who draw from nature's soul.",
    b1:'Start Creating Free',b2:'Explore Art Styles',
    galleryTitle:'Explore Art Styles',
    'n-login':'Sign In','n-reg':'Join Free',
    modalCta:'Start This Style',
    nav:{
      studio:'The Studio', community:'Community', marketplace:'Marketplace', explore:'Explore Art Styles', support:'Support',
      's1':'Watercolor Workshop','s2':'Oil Painting Studio','s3':'Digital Art Lab','s4':'Charcoal & Ink',
      'c2':'Meet Fellow Artists','c3':'Share Your Work','c4':'Learn Together',
      'sp1':'Contact Us','sp2':'How It Works','sp3':'Terms of Use'
    }
  },
  ar:{dir:'rtl',lb:'English',
    badge:'مجتمع الفن السعودي · Saudi Art Community',
    h1:'حيث يتألق<br><em>الفنانون</em>',
    desc:'اكتشف الفن وتعلّم وبِع أعمالك. أويلي هي منصة الفن السعودية الثنائية اللغة — تجمع الفنانين والهواة والمقتنين في فضاء إبداعي واحد.',
    b1:'ابدأ الإبداع مجاناً',b2:'استكشف أساليب الرسم',
    galleryTitle:'استكشف أساليب الرسم',
    'n-login':'تسجيل الدخول','n-reg':'انضم مجاناً',
    modalCta:'ابدأ هذا الأسلوب',
    nav:{
      studio:'الاستوديو', community:'المجتمع', marketplace:'السوق', explore:'استكشف أساليب الرسم', support:'الدعم',
      's1':'ورشة الألوان المائية','s2':'استوديو الرسم الزيتي','s3':'مختبر الفن الرقمي','s4':'الفحم والحبر',
      'c2':'تعرّف على فنانين','c3':'شارك أعمالك','c4':'تعلّم معاً',
      'sp1':'تواصل معنا','sp2':'كيف يعمل الموقع','sp3':'شروط الاستخدام'
    }
  }
};

let L = 'en';

function closeModal(e) {
  if(!e || e.target === document.getElementById('modal')) {
    document.getElementById('modal').classList.remove('open');
    document.body.style.overflow = '';
  }
}
document.addEventListener('keydown', e=>{ if(e.key==='Escape') closeModal(); });

function apply(l) {
  const t = T[l];
  document.documentElement.lang = l;
  document.documentElement.setAttribute('dir', t.dir);
  document.getElementById('pg').setAttribute('dir', t.dir);
  document.getElementById('topnav').setAttribute('dir', t.dir);
  document.getElementById('nav-center').setAttribute('dir', t.dir);
  document.getElementById('lb').textContent = t.lb;
  document.getElementById('t-badge').textContent = t.badge;
  document.getElementById('t-h1').innerHTML = t.h1;
  document.getElementById('t-desc').textContent = t.desc;
  document.getElementById('t-b1').textContent = t.b1;
  document.getElementById('t-b2').textContent = t.b2;
  // Sign In / Join Free only exist when logged out (shared nav.php)
  const nLogin = document.getElementById('n-login-t'); if (nLogin) nLogin.textContent = t['n-login'];
  const nReg = document.getElementById('n-reg-t'); if (nReg) nReg.textContent = t['n-reg'];
  const n = t.nav;
  document.getElementById('nav-explore-label').textContent = n.explore;
  document.getElementById('nav-studio-label').textContent = n.studio;
  document.getElementById('nav-community-label').textContent = n.community;
  document.getElementById('nav-marketplace-label').textContent = n.marketplace;
  document.getElementById('nav-support-label').textContent = n.support;
  
  // تحديث الرابط ديناميكياً ليمرر اللغة للمتجر أوتوماتيكياً
  document.getElementById('nav-marketplace-link').href = l === 'en' ? 'marketplace.php?lang=en' : 'marketplace.php?lang=ar';

  ['s1','s2','s3','s4','c2','c3','c4','sp1','sp2','sp3'].forEach(k=>{
    const el = document.getElementById('dd-'+k);
    if(el) el.textContent = n[k];
  });
}

function tgl() { L = L==='en'?'ar':'en'; apply(L); try{localStorage.setItem('lang',L);}catch(e){} }

/* Restore the site-wide saved language (shared with every other page). */
try{ var _saved=localStorage.getItem('lang'); L=(_saved==='ar')?'ar':'en'; }catch(e){}
apply(L);

const v = document.getElementById('vid');
if(v){
  v.addEventListener('canplay', ()=>v.classList.add('on'), {once:true});
  v.play().catch(()=>{});
}
</script>
</body>
</html>