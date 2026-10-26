<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en" dir="ltr" id="html">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Support · Oweili</title>
<meta name="description" content="Got a question? Just ask. A real person on our team will help you. لديك سؤال؟ اسألنا فقط. سيساعدك شخص حقيقي من فريقنا.">
<link rel="stylesheet" href="/assets/css/oweili.css?v=<?= @filemtime(__DIR__ . '/assets/css/oweili.css') ?>">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:var(--color-bg);font-family:var(--font-sans);overflow-x:hidden;}
.page{position:relative;z-index:10;width:100%;max-width:1000px;margin:0 auto;padding:calc(var(--nav-h) + var(--space-xl)) var(--space-lg) var(--space-2xl);}
.page-header{margin-bottom:48px;animation:riseUp 0.9s cubic-bezier(0.22,1,0.36,1) forwards;opacity:0;}
.page-badge{display:inline-flex;align-items:center;gap:8px;padding:5px 14px;border-radius:999px;margin-bottom:16px;background:rgba(var(--rgb-accent),0.05);border:1px solid rgba(var(--rgb-accent),0.15);font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:var(--color-accent);}
.pulse-dot{width:6px;height:6px;border-radius:50%;background:var(--color-accent);box-shadow:0 0 8px rgba(var(--rgb-accent),0.5);animation:pulse 2s infinite;}
.page-header h1{font-size:clamp(28px,4vw,48px);font-weight:700;color:var(--color-dark);margin-bottom:12px;letter-spacing:-0.02em;}
.page-header h1 em{font-style:normal;font-weight:700;color:var(--color-highlight);}
.page-header p{font-size:var(--fs-body);color:var(--color-body);line-height:1.7;max-width:580px;}.section-title{font-size:var(--fs-section);font-weight:700;color:var(--color-dark);letter-spacing:-0.02em;margin:0 0 var(--space-md);}
/* CONTACT CARDS */
.contact-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:48px;animation:riseUp 0.9s 0.1s cubic-bezier(0.22,1,0.36,1) both;}
.contact-card{background:var(--color-surface);border:1px solid rgba(var(--rgb-ink),0.07);border-radius:var(--radius-card);padding:28px;text-align:center;cursor:pointer;transition:all 0.3s;text-decoration:none;display:block;box-shadow:var(--shadow-card);}
.contact-card:hover{transform:translateY(-4px);box-shadow:0 16px 32px rgba(var(--rgb-shadow),0.08);}
.contact-icon{font-size:32px;margin-bottom:14px;}
.contact-card h3{font-size:var(--fs-subtitle);font-weight:700;color:var(--color-dark);margin-bottom:8px;}
.contact-card p{font-size:var(--fs-body);color:var(--color-body);line-height:1.7;margin-bottom:14px;}
.contact-link{font-size:12px;font-weight:700;color:var(--color-accent);letter-spacing:0.06em;text-transform:uppercase;}
/* FAQ */
.faq-section{margin-bottom:48px;animation:riseUp 0.9s 0.15s cubic-bezier(0.22,1,0.36,1) both;}
.faq-item{background:var(--color-surface);border:1px solid rgba(var(--rgb-ink),0.07);border-radius:14px;margin-bottom:10px;overflow:hidden;box-shadow:var(--shadow-card);}
.faq-q{padding:18px 20px;font-size:14px;font-weight:600;color:var(--color-dark);cursor:pointer;display:flex;align-items:center;justify-content:space-between;transition:background 0.2s;}
.faq-q:hover{background:rgba(var(--rgb-surface),0.8);}
.faq-arrow{font-size:var(--fs-body);color:var(--color-body);transition:transform 0.3s;}
.faq-item.open .faq-arrow{transform:rotate(180deg);}
.faq-a{max-height:0;overflow:hidden;transition:max-height 0.35s ease,padding 0.35s ease;}
.faq-item.open .faq-a{max-height:200px;padding:0 20px 18px;}
.faq-a p{font-size:var(--fs-body);color:var(--color-body);line-height:1.7;}
/* LEGAL */
.legal-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;animation:riseUp 0.9s 0.2s cubic-bezier(0.22,1,0.36,1) both;}
.legal-card{background:var(--color-surface);border:1px solid rgba(var(--rgb-ink),0.07);border-radius:16px;padding:24px;cursor:pointer;transition:all 0.25s;text-decoration:none;display:block;box-shadow:var(--shadow-card);}
.legal-card:hover{background:rgba(var(--rgb-surface),0.85);transform:translateX(4px);}
.legal-card h4{font-size:var(--fs-subtitle);font-weight:700;color:var(--color-dark);margin-bottom:6px;}
.legal-card p{font-size:var(--fs-body);color:var(--color-body);line-height:1.6;}
.legal-arrow{float:right;color:var(--color-body);font-size:14px;}
[dir="rtl"] .legal-card:hover{transform:translateX(-4px);}
[dir="rtl"] .legal-arrow{float:left;}
@keyframes riseUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.4}}
[dir="rtl"] .contact-grid,[dir="rtl"] .legal-grid{direction:rtl;}
[dir="rtl"] .page-header p{text-align:right;}
@media(max-width:768px){.contact-grid{grid-template-columns:1fr;}.legal-grid{grid-template-columns:1fr;}.page{padding:calc(var(--nav-h) + var(--space-md)) var(--space-sm) var(--space-xl);}}
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
    <div class="page-badge"><div class="pulse-dot"></div><span id="t-badge">We Are Here for You</span></div>
    <h1 id="t-h1">Artist <em>Support Center</em></h1>
    <p id="t-desc">Got a question? Just ask. A real person on our team will help you.</p>
  </div>

  <p class="section-title" id="t-ct-title">Get in Touch</p>
  <div class="contact-grid">
    <a class="contact-card" href="mailto:contact@oweili.com">
      <div class="contact-icon">✉️</div>
      <h3 id="t-c1">Write to Us</h3>
      <p id="t-c1d">Send us a message. We reply within 24 hours, usually sooner.</p>
      <span class="contact-link">contact@oweili.com</span>
    </a>
    <a class="contact-card" href="register.php">
      <div class="contact-icon">🎨</div>
      <h3 id="t-c3">Join the Community</h3>
      <p id="t-c3d">Create your free account and meet artists who share your love of art.</p>
      <span class="contact-link" id="t-c3l">Join Free →</span>
    </a>
  </div>

  <p class="section-title" id="t-faq-title">Frequently Asked Questions</p>
  <div class="faq-section" id="faqSection"></div>

  <p class="section-title" id="t-legal-title">Legal & Policies</p>
  <div class="legal-grid">
    <a class="legal-card" href="#">
      <span class="legal-arrow">→</span>
      <h4 id="t-l1">Your Art Stays Yours</h4>
      <p id="t-l1d">Everything you share on Oweili still belongs to you. Always.</p>
    </a>
    <a class="legal-card" href="#">
      <span class="legal-arrow">→</span>
      <h4 id="t-l2">Terms of Use</h4>
      <p id="t-l2d">Using Oweili means you agree to our community guidelines.</p>
    </a>
    <a class="legal-card" href="#">
      <span class="legal-arrow">→</span>
      <h4 id="t-l3">Privacy</h4>
      <p id="t-l3d">We protect your personal data and your creative work. That is a promise.</p>
    </a>
    <a class="legal-card" href="mailto:contact@oweili.com">
      <span class="legal-arrow">→</span>
      <h4 id="t-l4">Report Misuse of Your Art</h4>
      <p id="t-l4d">Someone used your art without asking? Tell us right away.</p>
    </a>
  </div>
</div>

<script>
const FAQS={
  en:[
    {q:'How do I create an account?',a:'Tap "Join Free" at the top. Add your name, email, phone and a password. Then confirm your email, and you are in.'},
    {q:'Does it cost anything to join?',a:'Joining Oweili is free. When a piece sells, Oweili keeps 15% before VAT. That covers shipping and advertising.'},
    {q:'Can I share my artwork on Oweili?',a:'Yes, please do. Once our team approves your profile, add your work from your dashboard. Photograph it at 25%, 50% and 100%. Then it appears in the gallery and on your profile.'},
    {q:'How do I report a problem?',a:'Write to us at contact@oweili.com. A real person on our team will help you.'},
    {q:'Is Oweili available in Arabic?',a:'Yes. Oweili speaks Arabic and English. Tap the language button at the top to switch.'}
  ],
  ar:[
    {q:'كيف أنشئ حساباً؟',a:'اضغط «انضم مجاناً» في الأعلى. أضف اسمك وبريدك وجوالك وكلمة مرور. ثم أكّد بريدك، وأهلاً بك معنا.'},
    {q:'هل الانضمام مجاني؟',a:'الانضمام إلى أويلي مجاني. وعند بيع عمل، يأخذ أويلي 15% قبل ضريبة القيمة المضافة. وهي تغطي الشحن والإعلان.'},
    {q:'هل يمكنني مشاركة أعمالي على أويلي؟',a:'نعم، ويسعدنا ذلك. بعد موافقة فريقنا على ملفك، أضف أعمالك من لوحة التحكم. صوّر العمل عند 25% و50% و100%. بعدها يظهر في المعرض وفي ملفك.'},
    {q:'كيف أبلّغ عن مشكلة؟',a:'راسلنا على contact@oweili.com. سيساعدك شخص حقيقي من فريقنا.'},
    {q:'هل أويلي متاح بالعربية؟',a:'نعم. أويلي يتحدث العربية والإنجليزية. اضغط زر اللغة في الأعلى للتبديل.'}
  ]
};
const T={
  en:{dir:'ltr',lb:'العربية',studio:'The Studio',explore:'Explore',community:'Community',support:'Support',login:'Sign In',
    badge:'We Are Here for You',h1:'Artist <em>Support Center</em>',desc:'Got a question? Just ask. A real person on our team will help you.',
    ctTitle:'Get in Touch',c1:'Write to Us',c1d:'Send us a message. We reply within 24 hours, usually sooner.',c2:'Artist Chat Room',c2d:'Ask fellow artists directly. Artists helping artists, right now.',c2l:'Open Chat →',c3:'Join the Community',c3d:'Create your free account and meet artists who share your love of art.',c3l:'Join Free →',
    faqTitle:'Questions We Hear Often',legalTitle:'Rights & Privacy',
    l1:'Your Art Stays Yours',l1d:'Everything you share on Oweili still belongs to you. Always.',
    l2:'Terms of Use',l2d:'Using Oweili means you agree to our community guidelines.',
    l3:'Privacy',l3d:'We protect your personal data and your creative work. That is a promise.',
    l4:'Report Misuse of Your Art',l4d:'Someone used your art without asking? Tell us right away.'},
  ar:{dir:'rtl',lb:'English',studio:'الاستوديو',explore:'استكشف',community:'المجتمع',support:'الدعم',login:'تسجيل الدخول',
    badge:'نحن هنا من أجلك',h1:'<em>مركز دعم</em> الفنانين',desc:'لديك سؤال؟ اسألنا فقط. سيساعدك شخص حقيقي من فريقنا.',
    ctTitle:'تواصل معنا',c1:'اكتب لنا',c1d:'أرسل لنا رسالة. نرد خلال 24 ساعة، وغالباً أسرع.',c2:'غرفة محادثة الفنانين',c2d:'اسأل الفنانين مباشرة. فنانون يساعدون فنانين، الآن.',c2l:'افتح المحادثة →',c3:'انضم للمجتمع',c3d:'أنشئ حسابك المجاني والتقِ بفنانين يشاركونك حب الفن.',c3l:'انضم مجاناً →',
    faqTitle:'أسئلة تصلنا كثيراً',legalTitle:'الحقوق والخصوصية',
    l1:'فنك يبقى لك',l1d:'كل ما تشاركه على أويلي يبقى ملكك. دائماً.',
    l2:'شروط الاستخدام',l2d:'باستخدامك أويلي توافق على إرشادات مجتمعنا.',
    l3:'الخصوصية',l3d:'نحمي بياناتك وأعمالك الإبداعية. هذا وعد منا.',
    l4:'أبلغ عن استخدام فنك دون إذن',l4d:'استخدم أحد فنك دون إذنك؟ أخبرنا فوراً.'}
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
function buildFAQ(l){
  const container=document.getElementById('faqSection');
  container.innerHTML=FAQS[l].map((f,i)=>`
    <div class="faq-item" id="faq${i}" onclick="toggleFaq(${i})">
      <div class="faq-q">${f.q}<span class="faq-arrow">▼</span></div>
      <div class="faq-a"><p>${f.a}</p></div>
    </div>`).join('');
}
function toggleFaq(i){
  const el=document.getElementById('faq'+i);
  el.classList.toggle('open');
}
function apply(l){
  const t=T[l];
  document.getElementById('html').lang=l;
  document.getElementById('html').setAttribute('dir',t.dir);
  document.documentElement.setAttribute('dir',t.dir);
  document.getElementById('pg').setAttribute('dir',t.dir);
  document.getElementById('topnav').setAttribute('dir',t.dir);
  applyNav(l);
  const keys=['badge','h1','desc','c1','c1d','c2','c2d','c2l','c3','c3d','c3l','l1','l1d','l2','l2d','l3','l3d','l4','l4d'];
  keys.forEach(k=>{const el=document.getElementById('t-'+k);if(el)el.innerHTML=t[k];});
  // section titles whose ids differ from their T keys
  const setH=(id,v)=>{const e=document.getElementById(id);if(e)e.innerHTML=v;};
  setH('t-ct-title',t.ctTitle);
  setH('t-faq-title',t.faqTitle);
  setH('t-legal-title',t.legalTitle);
  buildFAQ(l);
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
