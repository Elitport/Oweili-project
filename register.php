<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en" dir="ltr" id="html">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require_once __DIR__ . '/seo_lib.php'; owSeo(['en' => 'Join Oweili', 'ar' => 'انضم إلى أويلي', 'desc_en' => 'Join free as an artist or a buyer. Real art, made by real human hands.', 'desc_ar' => 'انضم مجاناً كفنان أو مشترٍ. فن حقيقي بأيدٍ بشرية حقيقية.', 'path' => 'register.php']); ?>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:var(--color-bg);font-family:var(--font-sans);overflow-x:hidden;}
.wrap{position:relative;z-index:10;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:100px 20px 50px;}
.head{text-align:center;margin-bottom:34px;animation:riseUp 0.7s cubic-bezier(0.22,1,0.36,1) both;}
.head h1{font-size:clamp(28px,4vw,40px);font-weight:700;color:var(--color-dark);margin-bottom:10px;letter-spacing:-0.02em;}
.head h1 em{font-style:normal;font-weight:700;color:var(--color-highlight);}
.head p{font-size:var(--fs-body);color:var(--color-body);}
.choices{display:grid;grid-template-columns:1fr 1fr;gap:22px;width:100%;max-width:720px;}
.choice{display:block;text-decoration:none;background:var(--color-surface);border:1px solid rgba(var(--rgb-ink),0.08);border-radius:var(--radius-card);padding:36px 28px;text-align:center;transition:transform 0.3s ease,box-shadow 0.3s ease,border-color 0.3s;animation:riseUp 0.8s 0.1s cubic-bezier(0.22,1,0.36,1) both;}
.choice:hover{transform:translateY(-6px);box-shadow:0 24px 50px rgba(var(--rgb-shadow),0.1);border-color:rgba(var(--rgb-accent),0.3);}
.choice .icon{width:74px;height:74px;border-radius:50%;margin:0 auto 18px;display:flex;align-items:center;justify-content:center;font-size:34px;}
.choice.artist .icon{background:rgba(var(--rgb-primary),0.1);}
.choice.collector .icon{background:rgba(var(--rgb-accent),0.1);}
.choice h2{font-size:var(--fs-card);font-weight:700;color:var(--color-dark);margin-bottom:8px;}
.choice p{font-size:var(--fs-body);color:var(--color-body);line-height:1.7;margin-bottom:18px;}
.choice .go{display:inline-block;padding:10px 24px;border-radius:999px;font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;}
.choice.artist .go{background:var(--color-primary);color:var(--color-on-primary);}
.choice.collector .go{background:var(--color-accent);color:var(--color-on-accent);}
.foot{margin-top:26px;font-size:13px;color:var(--color-body);}
.foot a{color:var(--color-accent);text-decoration:none;font-weight:600;}
@keyframes riseUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
@media(max-width:640px){.choices{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="bg-wrap"><video class="bg-video" id="vid" autoplay loop muted playsinline src="<?= owBase() ?>bg.mp4"></video></div>
<div class="overlay"></div>
<?php include __DIR__ . "/nav.php"; ?>

<div class="wrap">
  <div class="head">
    <h1 id="t-h1">Join <em>Oweili</em></h1>
    <p id="t-sub">Choose how you would like to join us.</p>
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
    <span id="t-have">Already one of us?</span> <a href="login.php" id="t-login">Sign In</a>
  </div>
</div>

<script>
const T={
  en:{dir:'ltr',lb:'العربية',h1:'Join <em>Oweili</em>',sub:'Choose how you would like to join us.',
    aTitle:'I am an Artist',aDesc:'Share your work and sell it to people who care. We review every artist by hand. You will need a photo, a few artworks and a short video.',aGo:'Join as Artist',
    cTitle:'I am a Collector',cDesc:'Find art made by real hands and bring it home. Signing up takes a minute.',cGo:'Join as Collector',
    have:'Already one of us?',login:'Sign In',
    nav:{community:'Community',marketplace:'Gallery',explore:'Explore Art Styles',support:'Support',
      sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',login:'Sign In',reg:'Join Free'}},
  ar:{dir:'rtl',lb:'English',h1:'انضم إلى <em>أويلي</em>',sub:'اختر كيف تحب أن تنضم إلينا.',
    aTitle:'أنا فنان',aDesc:'شارك أعمالك وبِعها لمن يقدّرها. نراجع كل فنان بعناية. ستحتاج صورة وبعض أعمالك ومقطعاً قصيراً.',aGo:'انضم كفنان',
    cTitle:'أنا مقتنٍ',cDesc:'اعثر على فن صنعته أيدٍ حقيقية، وخذه إلى بيتك. التسجيل يستغرق دقيقة.',cGo:'انضم كمقتنٍ',
    have:'أنت معنا بالفعل؟',login:'تسجيل الدخول',
    nav:{community:'المجتمع',marketplace:'المعرض',explore:'استكشف أساليب الرسم',support:'الدعم',
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
  setTxt('nav-community-label',n.community);
  setTxt('nav-marketplace-label',n.marketplace);setTxt('nav-explore-label',n.explore);setTxt('nav-support-label',n.support);
  
  setTxt('dd-sp1',n.sp1);setTxt('dd-sp2',n.sp2);setTxt('dd-sp3',n.sp3);
  setTxt('n-login-t',n.login);setTxt('n-reg-t',n.reg);
}
function tgl(){L=L==='en'?'ar':'en';apply(L);try{localStorage.setItem('lang',L);}catch(e){}}
try{var _s=localStorage.getItem('lang');if(_s==='ar'||_s==='en')L=_s;}catch(e){}
apply(L);
const v=document.getElementById('vid');
if(v){{const on=()=>v.classList.add('on');if(v.readyState>=3)on();v.addEventListener('canplay',on,{once:true});v.addEventListener('playing',on,{once:true});}v.play().catch(()=>{});}
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
