<?php
require_once 'config.php';

$events = [];
try {
    $events = getDB()->query('SELECT * FROM events ORDER BY (event_date IS NULL), event_date ASC, id DESC')->fetchAll();
} catch (\Throwable $e) { /* table may not exist yet */ }

$EVENTS_JSON = [];
foreach ($events as $ev) {
    $EVENTS_JSON[] = [
        'title_en' => $ev['title_en'], 'title_ar' => $ev['title_ar'],
        'desc_en'  => $ev['description_en'] ?? '', 'desc_ar' => $ev['description_ar'] ?? '',
        'city'     => $ev['city'],
        'date'     => $ev['event_date'] ? date('Y-m-d', strtotime($ev['event_date'])) : '',
        'free'     => (int) $ev['is_free'] === 1,
        'img'      => $ev['image_url'],
        'contact'  => $ev['contact_link'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" id="html">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Art Events · Oweili</title>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="/assets/css/oweili.css?v=<?= @filemtime(__DIR__ . '/assets/css/oweili.css') ?>">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:var(--color-bg);font-family:var(--font-sans);overflow-x:hidden;}
.page{position:relative;z-index:10;width:100%;max-width:1160px;margin:0 auto;padding:calc(var(--nav-h) + var(--space-xl)) var(--space-lg) var(--space-2xl);}
.page-header{margin-bottom:30px;}
.page-badge{display:inline-flex;align-items:center;gap:8px;padding:5px 14px;border-radius:999px;margin-bottom:14px;background:rgba(var(--rgb-primary),0.06);border:1px solid rgba(var(--rgb-primary),0.15);font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:var(--color-dark);}
.pulse-dot{width:6px;height:6px;border-radius:50%;background:var(--color-primary);box-shadow:0 0 8px rgba(var(--rgb-primary),0.5);animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.4}}
.page-header h1{font-size:clamp(28px,4vw,46px);font-weight:700;color:var(--color-dark);margin-bottom:12px;letter-spacing:-0.02em;}
.page-header h1 em{font-style:normal;font-weight:700;color:var(--color-highlight);}
.page-header p{font-size:var(--fs-body);color:var(--color-body);line-height:1.7;max-width:600px;}
[dir="rtl"] .page-header p{text-align:right;}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:22px;}
.ev-card{background:var(--color-surface);border:1px solid rgba(var(--rgb-ink),0.07);border-radius:var(--radius-card);overflow:hidden;display:flex;flex-direction:column;transition:transform 0.3s ease,box-shadow 0.3s ease;}
.ev-card:hover{transform:translateY(-4px);box-shadow:0 20px 40px rgba(var(--rgb-shadow),0.08);}
.ev-img{height:170px;background-size:cover;background-position:center;background-color:var(--color-surface-2);}
.ev-body{padding:20px;display:flex;flex-direction:column;gap:8px;flex:1;}
[dir="rtl"] .ev-body{text-align:right;direction:rtl;}
.ev-badge{align-self:flex-start;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;}
[dir="rtl"] .ev-badge{align-self:flex-end;}
.ev-badge.free{background:rgba(var(--rgb-accent),0.12);color:var(--color-accent);}
.ev-badge.paid{background:rgba(var(--rgb-primary),0.12);color:var(--color-dark);}
.ev-title{font-size:18px;font-weight:700;color:var(--color-dark);}
.ev-meta{font-size:12.5px;color:var(--color-body);display:flex;gap:14px;flex-wrap:wrap;}
.ev-desc{font-size:var(--fs-body);color:var(--color-body);line-height:1.7;flex:1;}
.ev-link{align-self:flex-start;margin-top:6px;padding:10px 22px;border-radius:999px;background:var(--color-dark);color:var(--color-on-dark);font-size:12px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;text-decoration:none;transition:all 0.25s;}
[dir="rtl"] .ev-link{align-self:flex-end;}
.ev-link:hover{background:var(--color-primary);transform:translateY(-2px);}
.empty{padding:56px;text-align:center;color:var(--color-body);background:var(--color-surface);border:1px dashed rgba(var(--rgb-ink),0.15);border-radius:var(--radius-card);box-shadow:var(--shadow-card);}
@media(max-width:768px){.page{padding:calc(var(--nav-h) + var(--space-md)) var(--space-sm) var(--space-xl);}}
</style>
</head>
<body>
<div class="bg-wrap"><video class="bg-video" id="vid" autoplay loop muted playsinline src="bg.mp4"></video></div>
<div class="overlay"></div>
<?php include __DIR__ . "/nav.php"; ?>

<div class="page" id="pg">
  <div class="page-header">
    <div class="page-badge"><span class="pulse-dot"></span><span id="t-badge">Discover</span></div>
    <h1 id="t-h1">Art <em>Events</em></h1>
    <p id="t-desc">Exhibitions, workshops, and gatherings for artists and art lovers across Saudi Arabia.</p>
  </div>
  <div class="grid" id="grid"></div>
  <div class="empty" id="empty" style="display:none;"></div>
</div>

<script>
const DATA = <?= json_encode($EVENTS_JSON, JSON_UNESCAPED_UNICODE) ?>;
const T={
  en:{dir:'ltr',lb:'العربية',badge:'Discover',h1:'Art <em>Events</em>',desc:'Exhibitions, workshops, and gatherings for artists and art lovers across Saudi Arabia.',
    free:'Free',paid:'Paid',contact:'Details & Contact',empty:'No events listed yet. Check back soon.',
    nav:{community:'Community',marketplace:'Gallery',explore:'Explore Art Styles',support:'Support',
      sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',login:'Sign In',reg:'Join Free'}},
  ar:{dir:'rtl',lb:'English',badge:'اكتشف',h1:'<em>أحداث</em> فنية',desc:'معارض وورش ولقاءات للفنانين ومحبي الفن في جميع أنحاء المملكة العربية السعودية.',
    free:'مجاني',paid:'مدفوع',contact:'التفاصيل والتواصل',empty:'لا توجد أحداث مدرجة بعد. عد قريباً.',
    nav:{community:'المجتمع',marketplace:'المعرض',explore:'استكشف أساليب الرسم',support:'الدعم',
      sp1:'تواصل معنا',sp2:'كيف يعمل الموقع',sp3:'شروط الاستخدام',login:'تسجيل الدخول',reg:'انضم مجاناً'}}
};
let L='en';
function esc(s){const d=document.createElement('div');d.textContent=String(s==null?'':s);return d.innerHTML;}
function buildGrid(l){
  const t=T[l], grid=document.getElementById('grid'), empty=document.getElementById('empty');
  if(!DATA.length){ grid.innerHTML=''; empty.style.display='block'; empty.textContent=t.empty; return; }
  empty.style.display='none';
  grid.innerHTML = DATA.map(function(ev){
    const title = l==='ar' ? (ev.title_ar||ev.title_en) : (ev.title_en||ev.title_ar);
    const desc  = l==='ar' ? (ev.desc_ar||ev.desc_en) : (ev.desc_en||ev.desc_ar);
    const meta=[]; if(ev.city) meta.push('📍 '+esc(ev.city)); if(ev.date) meta.push('📅 '+esc(ev.date));
    return '<div class="ev-card">'
      + (ev.img?'<div class="ev-img" style="background-image:url(\''+esc(ev.img)+'\')"></div>':'')
      + '<div class="ev-body">'
      + '<span class="ev-badge '+(ev.free?'free':'paid')+'">'+(ev.free?t.free:t.paid)+'</span>'
      + '<div class="ev-title">'+esc(title)+'</div>'
      + (meta.length?'<div class="ev-meta">'+meta.join('')+'</div>':'')
      + (desc?'<div class="ev-desc">'+esc(desc)+'</div>':'')
      + (ev.contact?'<a class="ev-link" href="'+esc(ev.contact)+'" target="_blank" rel="noopener">'+t.contact+'</a>':'')
      + '</div></div>';
  }).join('');
}
function setTxt(id,v){const el=document.getElementById(id);if(el)el.textContent=v;}
function apply(l){
  L=l; const n=T[l].nav, t=T[l];
  document.getElementById('html').lang=l;
  document.getElementById('html').setAttribute('dir',t.dir);
  document.documentElement.setAttribute('dir',t.dir);
  document.getElementById('topnav').setAttribute('dir',t.dir);
  document.getElementById('lb').textContent=t.lb;
  setTxt('t-badge',t.badge);document.getElementById('t-h1').innerHTML=t.h1;setTxt('t-desc',t.desc);
  setTxt('nav-community-label',n.community);
  setTxt('nav-marketplace-label',n.marketplace);setTxt('nav-explore-label',n.explore);setTxt('nav-support-label',n.support);
  
  setTxt('dd-sp1',n.sp1);setTxt('dd-sp2',n.sp2);setTxt('dd-sp3',n.sp3);
  setTxt('n-login-t',n.login);setTxt('n-reg-t',n.reg);
  buildGrid(l);
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
