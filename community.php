<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en" dir="ltr" id="html">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Community · Oweili</title>
<meta name="description" content="Art grows when artists meet. Share your work, learn from each other, and make friends who get it. الفن يكبر حين يلتقي الفنانون. شارك أعمالك، وتعلّم من غيرك، واصنع صداقات تفهم شغفك.">
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="/assets/css/oweili.css?v=<?= @filemtime(__DIR__ . '/assets/css/oweili.css') ?>">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:var(--color-bg);font-family:var(--font-sans);overflow-x:hidden;}
.page{position:relative;z-index:10;width:100%;max-width:1280px;margin:0 auto;padding:calc(var(--nav-h) + var(--space-xl)) var(--space-lg) var(--space-2xl);}
.page-header{margin-bottom:48px;animation:riseUp 0.9s cubic-bezier(0.22,1,0.36,1) forwards;opacity:0;}
.page-badge{display:inline-flex;align-items:center;gap:8px;padding:5px 14px;border-radius:999px;margin-bottom:16px;background:rgba(var(--rgb-accent),0.05);border:1px solid rgba(var(--rgb-accent),0.15);font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:var(--color-accent);}
.pulse-dot{width:6px;height:6px;border-radius:50%;background:var(--color-accent);box-shadow:0 0 8px rgba(var(--rgb-accent),0.5);animation:pulse 2s infinite;}
.page-header h1{font-size:clamp(28px,4vw,48px);font-weight:700;color:var(--color-dark);margin-bottom:12px;letter-spacing:-0.02em;}
.page-header h1 em{font-style:normal;font-weight:700;color:var(--color-highlight);}
.page-header p{font-size:var(--fs-body);color:var(--color-body);line-height:1.7;max-width:580px;}.section-title{font-size:var(--fs-section);font-weight:700;color:var(--color-dark);letter-spacing:-0.02em;margin:0 0 var(--space-md);}
/* STATS */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:48px;animation:riseUp 0.9s 0.05s cubic-bezier(0.22,1,0.36,1) both;}
.stat-card{background:var(--color-surface);border:1px solid rgba(var(--rgb-ink),0.07);border-radius:16px;padding:24px;text-align:center;box-shadow:var(--shadow-card);}
.stat-num{font-size:32px;font-weight:700;color:var(--color-highlight);margin-bottom:4px;}
.stat-label{font-size:12px;color:var(--color-body);text-transform:uppercase;letter-spacing:0.1em;}
/* CHAT CTA */
.chat-cta{background:var(--color-surface);border:1px solid rgba(var(--rgb-accent),0.12);border-radius:var(--radius-card);padding:40px;display:flex;align-items:center;justify-content:space-between;gap:24px;margin-bottom:48px;animation:riseUp 0.9s 0.1s cubic-bezier(0.22,1,0.36,1) both;box-shadow:var(--shadow-card);}
.chat-cta-left h2{font-size:var(--fs-card);font-weight:700;color:var(--color-dark);margin-bottom:8px;}
.chat-cta-left p{font-size:var(--fs-body);color:var(--color-body);line-height:1.7;max-width:480px;}
.chat-cta-right{flex-shrink:0;}
.live-badge{display:flex;align-items:center;gap:6px;margin-bottom:14px;font-size:12px;color:var(--color-accent);font-weight:600;letter-spacing:0.08em;}
.live-dot{width:8px;height:8px;border-radius:50%;background:var(--color-accent);box-shadow:0 0 8px rgba(var(--rgb-accent),0.5);animation:pulse 2s infinite;}
/* COMMUNITY CARDS */
.community-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:20px;margin-bottom:48px;animation:riseUp 0.9s 0.15s cubic-bezier(0.22,1,0.36,1) both;}
.comm-card{background:var(--color-surface);border:1px solid rgba(var(--rgb-ink),0.07);border-radius:var(--radius-card);padding:28px;cursor:pointer;transition:all 0.3s;text-decoration:none;display:block;box-shadow:var(--shadow-card);}
.comm-card:hover{transform:translateY(-4px);box-shadow:0 20px 40px rgba(var(--rgb-shadow),0.08);}
.comm-icon{font-size:32px;margin-bottom:14px;}
.comm-card h3{font-size:var(--fs-subtitle);font-weight:700;color:var(--color-dark);margin-bottom:8px;}
.comm-card p{font-size:var(--fs-body);color:var(--color-body);line-height:1.7;margin-bottom:16px;}
.comm-link{font-size:12px;font-weight:700;color:var(--color-accent);letter-spacing:0.06em;text-transform:uppercase;}
/* INSPIRATION */
.inspo-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;animation:riseUp 0.9s 0.2s cubic-bezier(0.22,1,0.36,1) both;}
.inspo-card{background:var(--color-surface);border:1px solid rgba(var(--rgb-ink),0.06);border-radius:16px;overflow:hidden;cursor:pointer;transition:all 0.3s;box-shadow:var(--shadow-card);}
.inspo-card:hover{transform:translateY(-3px);box-shadow:0 16px 32px rgba(var(--rgb-shadow),0.08);}
.inspo-img{height:140px;background-size:cover;background-position:center;transition:transform 0.5s;}
.inspo-card:hover .inspo-img{transform:scale(1.05);}
.inspo-body{padding:16px;}
.inspo-body h4{font-size:var(--fs-subtitle);font-weight:700;color:var(--color-dark);margin-bottom:4px;}
.inspo-body p{font-size:var(--fs-body);color:var(--color-body);}
@keyframes riseUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.4}}
[dir="rtl"] .stats-row,[dir="rtl"] .community-grid,[dir="rtl"] .inspo-grid{direction:rtl;}
[dir="rtl"] .page-header p{text-align:right;}
@media(max-width:1024px){.stats-row{grid-template-columns:repeat(2,1fr);}.inspo-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:768px){.community-grid{grid-template-columns:1fr;}.stats-row{grid-template-columns:1fr 1fr;}.chat-cta{flex-direction:column;}.page{padding:calc(var(--nav-h) + var(--space-md)) var(--space-sm) var(--space-xl);}}
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
    <div class="page-badge"><div class="pulse-dot"></div><span id="t-badge">Artists Together</span></div>
    <h1 id="t-h1">The <em>Artist Community</em></h1>
    <p id="t-desc">Art grows when artists meet. Share your work, learn from each other, and make friends who get it.</p>
  </div>

  <div class="stats-row">
    <div class="stat-card"><div class="stat-num" id="s-artists">N/A</div><div class="stat-label" id="t-s1">Artists With Us</div></div>
    <div class="stat-card"><div class="stat-num" id="s-msgs">N/A</div><div class="stat-label" id="t-s2">Messages Shared</div></div>
    <div class="stat-card"><div class="stat-num">12</div><div class="stat-label" id="t-s3">Art Styles</div></div>
    <div class="stat-card"><div class="stat-num">🇸🇦</div><div class="stat-label" id="t-s4">Saudi Community</div></div>
  </div>

  <p class="section-title" id="t-comm-title">Community Spaces</p>
  <div class="community-grid">
    <a class="comm-card" href="register.php">
      <div class="comm-icon">🎨</div>
      <h3 id="t-c2">Professional Artists Guild</h3>
      <p id="t-c2d">A space for experienced artists to work together and guide new talent.</p>
      <span class="comm-link" id="t-c2l">Apply to Join →</span>
    </a>
    <a class="comm-card" href="register.php">
      <div class="comm-icon">✨</div>
      <h3 id="t-c3">Daily Inspiration</h3>
      <p id="t-c3d">A little spark every day. Featured works, color palettes and new techniques to try.</p>
      <span class="comm-link" id="t-c3l">Explore →</span>
    </a>
    <a class="comm-card" href="register.php">
      <div class="comm-icon">🏆</div>
      <h3 id="t-c4">Teach With Us</h3>
      <p id="t-c4d">Love sharing what you know? Become an instructor and help others grow.</p>
      <span class="comm-link" id="t-c4l">Apply Now →</span>
    </a>
  </div>

  <p class="section-title" id="t-inspo-title">Daily Inspiration</p>
  <div class="inspo-grid">
    <div class="inspo-card"><div class="inspo-img" style="background-image:url('https://images.unsplash.com/photo-1448375240586-882707db888b?auto=format&fit=crop&w=600&q=80')"></div><div class="inspo-body"><h4 id="t-i1">Light Studies</h4><p id="t-i1d">Watch how light falls through leaves and windows.</p></div></div>
    <div class="inspo-card"><div class="inspo-img" style="background-image:url('https://images.unsplash.com/photo-1505118380757-91f5f5632de0?auto=format&fit=crop&w=600&q=80')"></div><div class="inspo-body"><h4 id="t-i2">Ocean Movement</h4><p id="t-i2d">Feel the rhythm of the waves, then paint it.</p></div></div>
    <div class="inspo-card"><div class="inspo-img" style="background-image:url('https://images.unsplash.com/photo-1502082553048-f009c37129b9?auto=format&fit=crop&w=600&q=80')"></div><div class="inspo-body"><h4 id="t-i3">Old Trees</h4><p id="t-i3d">Study bark, roots and the patience of time.</p></div></div>
  </div>
</div>

<script>
const T={
  en:{dir:'ltr',lb:'العربية',studio:'The Studio',explore:'Explore',community:'Community',support:'Support',login:'Sign In',
    badge:'Artists Together',h1:'The <em>Artist Community</em>',desc:'Art grows when artists meet. Share your work, learn from each other, and make friends who get it.',
    s1:'Artists With Us',s2:'Messages Shared',s3:'Art Styles',s4:'Saudi Community',
    ctaH:'Join the Artist Chat Room',ctaP:'Talk with fellow artists right now. Share a trick, ask a question, get honest feedback. Friendships start here.',live:'Live Now',ctaBtn:'Enter the Chat Room',
    commTitle:'Community Spaces',
    c1:'Artist Chat Room',c1d:'Artists from Saudi Arabia and beyond, chatting in real time. Pull up a chair.',c1l:'Enter Room →',
    c2:'Professional Artists Guild',c2d:'A space for experienced artists to work together and guide new talent.',c2l:'Apply to Join →',
    c3:'Daily Inspiration',c3d:'A little spark every day. Featured works, color palettes and new techniques to try.',c3l:'Explore →',
    c4:'Teach With Us',c4d:'Love sharing what you know? Become an instructor and help others grow.',c4l:'Apply Now →',
    inspoTitle:'Daily Inspiration',
    i1:'Light Studies',i1d:'Watch how light falls through leaves and windows.',
    i2:'Ocean Movement',i2d:'Feel the rhythm of the waves, then paint it.',
    i3:'Old Trees',i3d:'Study bark, roots and the patience of time.'},
  ar:{dir:'rtl',lb:'English',studio:'الاستوديو',explore:'استكشف',community:'المجتمع',support:'الدعم',login:'تسجيل الدخول',
    badge:'فنانون معاً',h1:'<em>مجتمع</em> الفنانين',desc:'الفن يكبر حين يلتقي الفنانون. شارك أعمالك، وتعلّم من غيرك، واصنع صداقات تفهم شغفك.',
    s1:'فنانون معنا',s2:'الرسائل المتبادلة',s3:'أساليب فنية',s4:'المجتمع السعودي',
    ctaH:'انضم إلى غرفة محادثة الفنانين',ctaP:'تحدّث مع فنانين مثلك الآن. شارك فكرة، واسأل سؤالاً، واحصل على رأي صادق. هنا تبدأ الصداقات.',live:'مباشر الآن',ctaBtn:'ادخل غرفة المحادثة',
    commTitle:'مساحات المجتمع',
    c1:'غرفة محادثة الفنانين',c1d:'فنانون من السعودية وخارجها يتحدثون مباشرة. تفضّل وانضم إليهم.',c1l:'ادخل الغرفة →',
    c2:'رابطة الرسامين المحترفين',c2d:'مساحة للفنانين المتمرّسين ليتعاونوا ويأخذوا بيد المواهب الجديدة.',c2l:'تقدّم للانضمام →',
    c3:'الإلهام اليومي',c3d:'شرارة صغيرة كل يوم. أعمال مختارة، ولوحات ألوان، وتقنيات جديدة لتجربتها.',c3l:'استكشف →',
    c4:'علّم معنا',c4d:'تحب أن تشارك ما تعرفه؟ كن مدرباً وساعد غيرك على النمو.',c4l:'تقدّم الآن →',
    inspoTitle:'إلهام اليوم',
    i1:'دراسات الضوء',i1d:'تأمّل كيف يتسلل الضوء بين الأوراق والنوافذ.',
    i2:'حركة البحر',i2d:'اشعر بإيقاع الموج، ثم ارسمه.',
    i3:'الأشجار المعمّرة',i3d:'تأمّل اللحاء والجذور وصبر السنين.'}
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
{const on=()=>v.classList.add('on');if(v.readyState>=3)on();v.addEventListener('canplay',on,{once:true});v.addEventListener('playing',on,{once:true});}
v.play().catch(()=>{});
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
