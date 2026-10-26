<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en" dir="ltr" id="html">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Join Oweili · Choose Account Type</title>
<link rel="stylesheet" href="/assets/css/oweili.css?v=<?= @filemtime(__DIR__ . '/assets/css/oweili.css') ?>">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:#fff;font-family:var(--font-sans);overflow-x:hidden;}
.wrap{position:relative;z-index:10;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:100px 20px 50px;}
.head{text-align:center;margin-bottom:34px;animation:riseUp 0.7s cubic-bezier(0.22,1,0.36,1) both;}
.head h1{font-size:clamp(28px,4vw,40px);font-weight:700;color:var(--color-dark);margin-bottom:10px;letter-spacing:-0.02em;}
.head h1 em{font-style:normal;font-weight:700;color:var(--color-primary);}
.head p{font-size:var(--fs-body);color:var(--color-body);}
.choices{display:grid;grid-template-columns:1fr 1fr;gap:22px;width:100%;max-width:720px;}
.choice{display:block;text-decoration:none;background:#fff;border:1px solid rgba(0,0,0,0.08);border-radius:var(--radius-card);padding:36px 28px;text-align:center;transition:transform 0.3s ease,box-shadow 0.3s ease,border-color 0.3s;animation:riseUp 0.8s 0.1s cubic-bezier(0.22,1,0.36,1) both;}
.choice:hover{transform:translateY(-6px);box-shadow:0 24px 50px rgba(0,0,0,0.1);border-color:rgba(0,100,255,0.3);}
.choice .icon{width:74px;height:74px;border-radius:50%;margin:0 auto 18px;display:flex;align-items:center;justify-content:center;font-size:34px;}
.choice.artist .icon{background:rgba(255,0,85,0.1);}
.choice.collector .icon{background:rgba(0,100,255,0.1);}
.choice h2{font-size:var(--fs-card);font-weight:700;color:var(--color-dark);margin-bottom:8px;}
.choice p{font-size:var(--fs-body);color:var(--color-body);line-height:1.7;margin-bottom:18px;}
.choice .go{display:inline-block;padding:10px 24px;border-radius:999px;font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:#fff;}
.choice.artist .go{background:var(--color-primary);}
.choice.collector .go{background:var(--color-accent);}
.foot{margin-top:26px;font-size:13px;color:var(--color-body);}
.foot a{color:var(--color-accent);text-decoration:none;font-weight:600;}
@keyframes riseUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
@media(max-width:640px){.choices{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="bg-wrap"><video class="bg-video" id="vid" autoplay loop muted playsinline src="bg.mp4"></video></div>
<div class="overlay"></div>
<?php include __DIR__ . "/nav.php"; ?>

<div class="wrap">
  <div class="head">
    <h1 id="t-h1">Join <em>Oweili</em></h1>
    <p id="t-sub">Choose the account that fits you.</p>
  </div>
  <div class="choices">
    <a class="choice artist" href="register_artist.php">
      <div class="icon">🎨</div>
      <h2 id="t-a-title">I am an Artist</h2>
      <p id="t-a-desc">Showcase and sell your work. Requires a profile picture, portfolio, and a short video, all reviewed by our team.</p>
      <span class="go" id="t-a-go">Register as Artist</span>
    </a>
    <a class="choice collector" href="register_collector.php">
      <div class="icon">🖼️</div>
      <h2 id="t-c-title">I am a Collector</h2>
      <p id="t-c-desc">Discover and collect original art. Quick sign-up: just your details and you're in.</p>
      <span class="go" id="t-c-go">Register as Collector</span>
    </a>
  </div>
  <div class="foot">
    <span id="t-have">Already have an account?</span> <a href="login.php" id="t-login">Sign In</a>
  </div>
</div>

<script>
const T={
  en:{dir:'ltr',lb:'العربية',h1:'Join <em>Oweili</em>',sub:'Choose the account that fits you.',
    aTitle:'I am an Artist',aDesc:'Showcase and sell your work. Requires a profile picture, portfolio, and a short video, all reviewed by our team.',aGo:'Register as Artist',
    cTitle:'I am a Collector',cDesc:"Discover and collect original art. Quick sign-up: just your details and you're in.",cGo:'Register as Collector',
    have:'Already have an account?',login:'Sign In',
    nav:{studio:'The Studio',community:'Community',marketplace:'Marketplace',explore:'Explore Art Styles',support:'Support',
      s1:'Watercolor Workshop',s2:'Oil Painting Studio',s3:'Digital Art Lab',s4:'Charcoal & Ink',
      c2:'Meet Fellow Artists',c3:'Share Your Work',c4:'Learn Together',
      sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',login:'Sign In',reg:'Join Free'}},
  ar:{dir:'rtl',lb:'English',h1:'انضم إلى <em>أويلي</em>',sub:'اختر نوع الحساب المناسب لك.',
    aTitle:'أنا فنان',aDesc:'اعرض أعمالك وبِعها. يتطلب صورة شخصية وأعمالاً وفيديو قصير، تتم مراجعتها جميعاً من فريقنا.',aGo:'التسجيل كفنان',
    cTitle:'أنا مقتني',cDesc:'اكتشف واقتنِ الفن الأصلي. تسجيل سريع، فقط بياناتك وتنضم فوراً.',cGo:'التسجيل كمقتني',
    have:'لديك حساب بالفعل؟',login:'تسجيل الدخول',
    nav:{studio:'الاستوديو',community:'المجتمع',marketplace:'السوق',explore:'استكشف أساليب الرسم',support:'الدعم',
      s1:'ورشة الألوان المائية',s2:'استوديو الرسم الزيتي',s3:'مختبر الفن الرقمي',s4:'الفحم والحبر',
      c2:'تعرّف على فنانين',c3:'شارك أعمالك',c4:'تعلّم معاً',
      sp1:'تواصل معنا',sp2:'كيف يعمل الموقع',sp3:'شروط الاستخدام',login:'تسجيل الدخول',reg:'انضم مجاناً'}}
};
let L='en';
function setTxt(id,v){const el=document.getElementById(id);if(el)el.textContent=v;}
function setHtml(id,v){const el=document.getElementById(id);if(el)el.innerHTML=v;}
function apply(l){
  const t=T[l];
  document.getElementById('html').lang=l;
  document.getElementById('html').setAttribute('dir',t.dir);
  document.documentElement.setAttribute('dir',t.dir);
  document.getElementById('topnav').setAttribute('dir',t.dir);
  document.getElementById('lb').textContent=t.lb;
  setHtml('t-h1',t.h1);setTxt('t-sub',t.sub);
  setTxt('t-a-title',t.aTitle);setTxt('t-a-desc',t.aDesc);setTxt('t-a-go',t.aGo);
  setTxt('t-c-title',t.cTitle);setTxt('t-c-desc',t.cDesc);setTxt('t-c-go',t.cGo);
  setTxt('t-have',t.have);setTxt('t-login',t.login);
  const n=t.nav;
  setTxt('nav-studio-label',n.studio);setTxt('nav-community-label',n.community);
  setTxt('nav-marketplace-label',n.marketplace);setTxt('nav-explore-label',n.explore);setTxt('nav-support-label',n.support);
  setTxt('dd-s1',n.s1);setTxt('dd-s2',n.s2);setTxt('dd-s3',n.s3);setTxt('dd-s4',n.s4);
  setTxt('dd-c2',n.c2);setTxt('dd-c3',n.c3);setTxt('dd-c4',n.c4);
  setTxt('dd-sp1',n.sp1);setTxt('dd-sp2',n.sp2);setTxt('dd-sp3',n.sp3);
  setTxt('n-login-t',n.login);setTxt('n-reg-t',n.reg);
}
function tgl(){L=L==='en'?'ar':'en';apply(L);try{localStorage.setItem('lang',L);}catch(e){}}
try{var _s=localStorage.getItem('lang');if(_s==='ar'||_s==='en')L=_s;}catch(e){}
apply(L);
const v=document.getElementById('vid');
if(v){v.addEventListener('canplay',()=>v.classList.add('on'),{once:true});v.play().catch(()=>{});}
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
