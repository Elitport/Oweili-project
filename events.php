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
<link rel="stylesheet" href="/assets/css/oweili.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:#fff;font-family:var(--font-sans);overflow-x:hidden;}
.bg-wrap{position:fixed;inset:0;z-index:0;overflow:hidden;}
.bg-video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0;transition:opacity 2s ease;z-index:2;}
.bg-video.on{opacity:0.95;}
.overlay{position:fixed;inset:0;z-index:3;pointer-events:none;background:linear-gradient(160deg,rgba(255,255,255,0.2) 0%,rgba(255,255,255,0) 50%,rgba(255,255,255,0.4) 100%);}
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
.lang-btn{padding:4px 10px;border-radius:999px;font-size:12px;font-weight:600;letter-spacing:0.04em;cursor:pointer;border:1px solid rgba(0,0,0,0.1);background:rgba(255,255,255,0.7);color:rgba(0,0,0,0.7);backdrop-filter:blur(8px);transition:all 0.22s;}
.lang-btn:hover{background:#111111;color:#fff;}
@media(max-width:1024px){.nav-center{display:none;}}

.page{position:relative;z-index:10;width:100%;max-width:1160px;margin:0 auto;padding:100px 24px 60px;}
.page-header{margin-bottom:30px;}
.page-badge{display:inline-flex;align-items:center;gap:8px;padding:5px 14px;border-radius:999px;margin-bottom:14px;background:rgba(255,0,85,0.06);border:1px solid rgba(255,0,85,0.15);font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:#111111;}
.pulse-dot{width:6px;height:6px;border-radius:50%;background:#ff0055;box-shadow:0 0 8px rgba(255,0,85,0.5);animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.4}}
.page-header h1{font-size:clamp(28px,4vw,46px);font-weight:300;color:#111;margin-bottom:12px;letter-spacing:-0.02em;}
.page-header h1 em{font-style:normal;font-weight:700;background:linear-gradient(90deg,#ff0055,#0066ff,#0066ff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.page-header p{font-size:15px;color:rgba(0,0,0,0.6);line-height:1.8;max-width:600px;}
[dir="rtl"] .page-header p{text-align:right;}

.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:22px;}
.ev-card{background:rgba(255,255,255,0.62);border:1px solid rgba(0,0,0,0.07);border-radius:20px;overflow:hidden;backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);display:flex;flex-direction:column;transition:transform 0.3s ease,box-shadow 0.3s ease;}
.ev-card:hover{transform:translateY(-4px);box-shadow:0 20px 40px rgba(0,0,0,0.08);}
.ev-img{height:170px;background-size:cover;background-position:center;background-color:#eee;}
.ev-body{padding:20px;display:flex;flex-direction:column;gap:8px;flex:1;}
[dir="rtl"] .ev-body{text-align:right;direction:rtl;}
.ev-badge{align-self:flex-start;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;}
[dir="rtl"] .ev-badge{align-self:flex-end;}
.ev-badge.free{background:rgba(0,102,255,0.12);color:#0066ff;}
.ev-badge.paid{background:rgba(255,0,85,0.12);color:#111111;}
.ev-title{font-size:18px;font-weight:700;color:#111;}
.ev-meta{font-size:12.5px;color:rgba(0,0,0,0.6);display:flex;gap:14px;flex-wrap:wrap;}

.ev-desc{font-size:13px;color:rgba(0,0,0,0.65);line-height:1.7;flex:1;}
.ev-link{align-self:flex-start;margin-top:6px;padding:10px 22px;border-radius:999px;background:#111;color:#fff;font-size:12px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;text-decoration:none;transition:all 0.25s;}
[dir="rtl"] .ev-link{align-self:flex-end;}
.ev-link:hover{background:#ff0055;transform:translateY(-2px);}
.empty{padding:56px;text-align:center;color:rgba(0,0,0,0.5);background:rgba(255,255,255,0.6);border:1px dashed rgba(0,0,0,0.15);border-radius:22px;}
@media(max-width:768px){.topnav{padding:10px 14px;}.nav-logo span{display:none;}.page{padding:90px 16px 40px;}}
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
    nav:{studio:'The Studio',community:'Community',marketplace:'Marketplace',explore:'Explore Art Styles',support:'Support',
      s1:'Watercolor Workshop',s2:'Oil Painting Studio',s3:'Digital Art Lab',s4:'Charcoal & Ink',
      c2:'Meet Fellow Artists',c3:'Share Your Work',c4:'Learn Together',
      sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',login:'Sign In',reg:'Join Free'}},
  ar:{dir:'rtl',lb:'English',badge:'اكتشف',h1:'<em>أحداث</em> فنية',desc:'معارض وورش ولقاءات للفنانين ومحبي الفن في جميع أنحاء المملكة العربية السعودية.',
    free:'مجاني',paid:'مدفوع',contact:'التفاصيل والتواصل',empty:'لا توجد أحداث مدرجة بعد. عد قريباً.',
    nav:{studio:'الاستوديو',community:'المجتمع',marketplace:'السوق',explore:'استكشف أساليب الرسم',support:'الدعم',
      s1:'ورشة الألوان المائية',s2:'استوديو الرسم الزيتي',s3:'مختبر الفن الرقمي',s4:'الفحم والحبر',
      c2:'تعرّف على فنانين',c3:'شارك أعمالك',c4:'تعلّم معاً',
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
  setTxt('nav-studio-label',n.studio);setTxt('nav-community-label',n.community);
  setTxt('nav-marketplace-label',n.marketplace);setTxt('nav-explore-label',n.explore);setTxt('nav-support-label',n.support);
  setTxt('dd-s1',n.s1);setTxt('dd-s2',n.s2);setTxt('dd-s3',n.s3);setTxt('dd-s4',n.s4);
  setTxt('dd-c2',n.c2);setTxt('dd-c3',n.c3);setTxt('dd-c4',n.c4);
  setTxt('dd-sp1',n.sp1);setTxt('dd-sp2',n.sp2);setTxt('dd-sp3',n.sp3);
  setTxt('n-login-t',n.login);setTxt('n-reg-t',n.reg);
  buildGrid(l);
}
function tgl(){L=L==='en'?'ar':'en';apply(L);try{localStorage.setItem('lang',L);}catch(e){}}
try{var _s=localStorage.getItem('lang');if(_s==='ar'||_s==='en')L=_s;}catch(e){}
apply(L);
const v=document.getElementById('vid');
if(v){v.addEventListener('canplay',()=>v.classList.add('on'),{once:true});v.play().catch(()=>{});}
</script>
</body>
</html>
