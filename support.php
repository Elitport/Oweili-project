<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en" dir="ltr" id="html">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Support · Oweili</title>
<link rel="stylesheet" href="/assets/css/oweili.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:#fff;font-family:var(--font-sans);overflow-x:hidden;}
.page{position:relative;z-index:10;width:100%;max-width:1000px;margin:0 auto;padding:calc(var(--nav-h) + var(--space-xl)) var(--space-lg) var(--space-2xl);}
.page-header{margin-bottom:48px;animation:riseUp 0.9s cubic-bezier(0.22,1,0.36,1) forwards;opacity:0;}
.page-badge{display:inline-flex;align-items:center;gap:8px;padding:5px 14px;border-radius:999px;margin-bottom:16px;background:rgba(0,102,255,0.05);border:1px solid rgba(0,102,255,0.15);font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:var(--color-accent);}
.pulse-dot{width:6px;height:6px;border-radius:50%;background:var(--color-accent);box-shadow:0 0 8px rgba(0,102,255,0.5);animation:pulse 2s infinite;}
.page-header h1{font-size:clamp(28px,4vw,48px);font-weight:700;color:var(--color-dark);margin-bottom:12px;letter-spacing:-0.02em;}
.page-header h1 em{font-style:normal;font-weight:700;color:var(--color-primary);}
.page-header p{font-size:var(--fs-body);color:var(--color-body);line-height:1.7;max-width:580px;}.section-title{font-size:var(--fs-section);font-weight:700;color:var(--color-dark);letter-spacing:-0.02em;margin:0 0 var(--space-md);}
/* CONTACT CARDS */
.contact-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:48px;animation:riseUp 0.9s 0.1s cubic-bezier(0.22,1,0.36,1) both;}
.contact-card{background:#fff;border:1px solid rgba(0,0,0,0.07);border-radius:var(--radius-card);padding:28px;text-align:center;cursor:pointer;transition:all 0.3s;text-decoration:none;display:block;box-shadow:var(--shadow-card);}
.contact-card:hover{transform:translateY(-4px);box-shadow:0 16px 32px rgba(0,0,0,0.08);}
.contact-icon{font-size:32px;margin-bottom:14px;}
.contact-card h3{font-size:var(--fs-subtitle);font-weight:700;color:var(--color-dark);margin-bottom:8px;}
.contact-card p{font-size:var(--fs-body);color:var(--color-body);line-height:1.7;margin-bottom:14px;}
.contact-link{font-size:12px;font-weight:700;color:var(--color-accent);letter-spacing:0.06em;text-transform:uppercase;}
/* FAQ */
.faq-section{margin-bottom:48px;animation:riseUp 0.9s 0.15s cubic-bezier(0.22,1,0.36,1) both;}
.faq-item{background:#fff;border:1px solid rgba(0,0,0,0.07);border-radius:14px;margin-bottom:10px;overflow:hidden;box-shadow:var(--shadow-card);}
.faq-q{padding:18px 20px;font-size:14px;font-weight:600;color:var(--color-dark);cursor:pointer;display:flex;align-items:center;justify-content:space-between;transition:background 0.2s;}
.faq-q:hover{background:rgba(255,255,255,0.8);}
.faq-arrow{font-size:var(--fs-body);color:var(--color-body);transition:transform 0.3s;}
.faq-item.open .faq-arrow{transform:rotate(180deg);}
.faq-a{max-height:0;overflow:hidden;transition:max-height 0.35s ease,padding 0.35s ease;}
.faq-item.open .faq-a{max-height:200px;padding:0 20px 18px;}
.faq-a p{font-size:var(--fs-body);color:var(--color-body);line-height:1.7;}
/* LEGAL */
.legal-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;animation:riseUp 0.9s 0.2s cubic-bezier(0.22,1,0.36,1) both;}
.legal-card{background:#fff;border:1px solid rgba(0,0,0,0.07);border-radius:16px;padding:24px;cursor:pointer;transition:all 0.25s;text-decoration:none;display:block;box-shadow:var(--shadow-card);}
.legal-card:hover{background:rgba(255,255,255,0.85);transform:translateX(4px);}
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
    <div class="page-badge"><div class="pulse-dot"></div><span id="t-badge">We're Here to Help</span></div>
    <h1 id="t-h1">Artist <em>Support Center</em></h1>
    <p id="t-desc">Have a question or need help? We're here for every artist in the Oweili community. Find answers, get in touch, and access our resources below.</p>
  </div>

  <p class="section-title" id="t-ct-title">Get in Touch</p>
  <div class="contact-grid">
    <a class="contact-card" href="mailto:contact@oweili.com">
      <div class="contact-icon">✉️</div>
      <h3 id="t-c1">Contact the Gallery</h3>
      <p id="t-c1d">Send us a message and our team will get back to you within 24 hours.</p>
      <span class="contact-link">contact@oweili.com</span>
    </a>
    <a class="contact-card" href="register.php">
      <div class="contact-icon">🎨</div>
      <h3 id="t-c3">Join the Community</h3>
      <p id="t-c3d">Create a free account to access all features, workshops, and the artist network.</p>
      <span class="contact-link" id="t-c3l">Join Free →</span>
    </a>
  </div>

  <p class="section-title" id="t-faq-title">Frequently Asked Questions</p>
  <div class="faq-section" id="faqSection"></div>

  <p class="section-title" id="t-legal-title">Legal & Policies</p>
  <div class="legal-grid">
    <a class="legal-card" href="#">
      <span class="legal-arrow">→</span>
      <h4 id="t-l1">Artwork Rights Policy</h4>
      <p id="t-l1d">All artwork shared on Oweili remains the intellectual property of the original creator.</p>
    </a>
    <a class="legal-card" href="#">
      <span class="legal-arrow">→</span>
      <h4 id="t-l2">Terms of Digital Use</h4>
      <p id="t-l2d">By using Oweili you agree to our community guidelines and terms of service.</p>
    </a>
    <a class="legal-card" href="#">
      <span class="legal-arrow">→</span>
      <h4 id="t-l3">Privacy Policy</h4>
      <p id="t-l3d">We are committed to protecting your personal data and creative work at all times.</p>
    </a>
    <a class="legal-card" href="mailto:contact@oweili.com">
      <span class="legal-arrow">→</span>
      <h4 id="t-l4">Report an Infringement</h4>
      <p id="t-l4d">If you believe your artwork has been used without permission, contact us immediately.</p>
    </a>
  </div>
</div>

<script>
const FAQS={
  en:[
    {q:'How do I create an account?',a:'Click "Join Free" in the navigation bar, fill in your artist name, email, phone number, and password. Then verify your email to activate your account.'},
    {q:'Is Oweili free to use?',a:'Yes! Oweili is completely free for all artists. Create your account, join workshops, and connect with the community at no cost.'},
    {q:'Can I share my artwork on Oweili?',a:'Absolutely! Once your artist account is approved, upload your work from your dashboard and it appears in the marketplace and on your public profile.'},
    {q:'How do I report a problem?',a:'You can contact us directly at contact@oweili.com and our team will help with any community issues.'},
    {q:'Is the platform available in Arabic?',a:'Yes! Oweili fully supports Arabic with RTL layout. Click the language button in the top navigation to switch between English and Arabic.'}
  ],
  ar:[
    {q:'كيف أنشئ حساباً؟',a:'انقر على "انضم مجاناً" في شريط التنقل، أدخل اسمك الفني والبريد الإلكتروني ورقم الجوال وكلمة السر. ثم تحقق من بريدك لتفعيل الحساب.'},
    {q:'هل Oweili مجاني؟',a:'نعم! Oweili مجاني تماماً لجميع الفنانين. أنشئ حسابك، انضم إلى الورش، وتواصل مع المجتمع بدون أي تكلفة.'},
    {q:'هل يمكنني مشاركة أعمالي الفنية؟',a:'بالتأكيد! بمجرد اعتماد حساب الفنان، ارفع أعمالك من لوحة التحكم لتظهر في السوق وفي ملفك الشخصي العام.'},
    {q:'كيف أبلّغ عن مشكلة؟',a:'يمكنك التواصل معنا مباشرة على contact@oweili.com وسيساعدك فريقنا في أي مشكلات تتعلق بالمجتمع.'},
    {q:'هل المنصة متاحة باللغة العربية؟',a:'نعم! Oweili يدعم اللغة العربية بالكامل مع تخطيط RTL. انقر على زر اللغة في التنقل العلوي للتبديل بين الإنجليزية والعربية.'}
  ]
};
const T={
  en:{dir:'ltr',lb:'العربية',studio:'The Studio',explore:'Explore',community:'Community',support:'Support',login:'Sign In',
    badge:"We're Here to Help",h1:'Artist <em>Support Center</em>',desc:"Have a question or need help? We're here for every artist in the Oweili community.",
    ctTitle:'Get in Touch',c1:'Contact the Gallery',c1d:'Send us a message and our team will get back to you within 24 hours.',c2:'Artist Chat Room',c2d:'Ask the community directly — artists helping artists, in real time.',c2l:'Open Chat →',c3:'Join the Community',c3d:'Create a free account to access all features, workshops, and the artist network.',c3l:'Join Free →',
    faqTitle:'Frequently Asked Questions',legalTitle:'Legal & Policies',
    l1:'Artwork Rights Policy',l1d:'All artwork shared on Oweili remains the intellectual property of the original creator.',
    l2:'Terms of Digital Use',l2d:'By using Oweili you agree to our community guidelines and terms of service.',
    l3:'Privacy Policy',l3d:'We are committed to protecting your personal data and creative work at all times.',
    l4:'Report an Infringement',l4d:'If you believe your artwork has been used without permission, contact us immediately.'},
  ar:{dir:'rtl',lb:'English',studio:'الاستوديو',explore:'استكشف',community:'المجتمع',support:'الدعم',login:'تسجيل الدخول',
    badge:'نحن هنا للمساعدة',h1:'<em>مركز دعم</em> الفنانين',desc:'هل لديك سؤال أو تحتاج مساعدة؟ نحن هنا لكل فنان في مجتمع أويلي.',
    ctTitle:'تواصل معنا',c1:'تواصل مع المعرض',c1d:'أرسل لنا رسالة وسيرد فريقنا خلال 24 ساعة.',c2:'غرفة محادثة الفنانين',c2d:'اسأل المجتمع مباشرة — فنانون يساعدون فنانين، في الوقت الفعلي.',c2l:'افتح المحادثة →',c3:'انضم للمجتمع',c3d:'أنشئ حساباً مجانياً للوصول إلى جميع الميزات والورش وشبكة الفنانين.',c3l:'انضم مجاناً →',
    faqTitle:'الأسئلة الشائعة',legalTitle:'القانوني والسياسات',
    l1:'سياسة حقوق اللوحات',l1d:'جميع الأعمال الفنية المشتركة على Oweili تبقى ملكاً فكرياً للمبدع الأصلي.',
    l2:'شروط الاستخدام الرقمي',l2d:'باستخدام Oweili فإنك توافق على إرشادات مجتمعنا وشروط الخدمة.',
    l3:'سياسة الخصوصية',l3d:'نحن ملتزمون بحماية بياناتك الشخصية وأعمالك الإبداعية في جميع الأوقات.',
    l4:'الإبلاغ عن انتهاك فني',l4d:'إذا كنت تعتقد أن أعمالك الفنية استُخدمت دون إذن، تواصل معنا فوراً.'}
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
v.addEventListener('canplay',()=>v.classList.add('on'),{once:true});
v.play().catch(()=>{});
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
