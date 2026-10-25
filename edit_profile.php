<?php
require_once 'config.php';

if (!isLoggedIn()) { header('Location: login.php'); exit; }
$uid = (int) $_SESSION['user_id'];

$err = '';
$ok  = false;

/* Load current profile */
$stmt = getDB()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $uid]);
$u = $stmt->fetch();
if (!$u) { header('Location: login.php'); exit; }

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) { http_response_code(403); exit('Invalid CSRF token.'); }

    $nameEn = sanitize($_POST['full_name_en'] ?? '');
    $nameAr = sanitize($_POST['full_name_ar'] ?? '');
    $bioEn  = trim((string) ($_POST['bio_en'] ?? ''));
    $bioAr  = trim((string) ($_POST['bio_ar'] ?? ''));
    $city   = sanitize($_POST['city'] ?? '');
    $phone  = sanitize($_POST['phone'] ?? '');
    $ig     = sanitize($_POST['instagram'] ?? '');
    $tw     = sanitize($_POST['twitter'] ?? '');
    $web    = sanitize($_POST['website'] ?? '');

    $hasPhoto = !empty($_FILES['profile_picture']['name']);

    if ($nameEn === '') {
        $err = 'name';
    } elseif ($phone !== '' && !preg_match('/^[+0-9][0-9 \-()]{6,29}$/', $phone)) {
        $err = 'phone';
    } elseif ($hasPhoto && !validateUpload($_FILES['profile_picture'])['ok']) {
        $err = 'photo';
    } else {
        $picRel = $u['profile_picture'];
        if ($hasPhoto) {
            $chk = validateUpload($_FILES['profile_picture']);
            $dir = __DIR__ . '/uploads/profiles/';
            if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
            $fname = safeUploadName($chk['ext']);
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $dir . $fname)) {
                // remove the old local picture
                if ($picRel && strpos($picRel, 'uploads/profiles/') === 0 && is_file(__DIR__ . '/' . $picRel)) {
                    @unlink(__DIR__ . '/' . $picRel);
                }
                $picRel = 'uploads/profiles/' . $fname;
            }
        }

        getDB()->prepare(
            'UPDATE users SET
                full_name_en = :nen, full_name_ar = :nar, artist_name = :aname,
                bio_en = :ben, bio_ar = :bar, city = :city, phone = :phone,
                instagram = :ig, twitter = :tw, website = :web,
                profile_picture = :pic
             WHERE id = :id'
        )->execute([
            ':nen' => $nameEn, ':nar' => $nameAr, ':aname' => $nameEn,
            ':ben' => $bioEn, ':bar' => $bioAr, ':city' => $city, ':phone' => $phone,
            ':ig' => $ig, ':tw' => $tw, ':web' => $web, ':pic' => $picRel,
            ':id' => $uid,
        ]);

        $dash = dashboardUrl($u['role'] ?? '');
        $sep  = strpos($dash, '?') !== false ? '&' : '?';
        header('Location: ' . $dash . $sep . 'saved=1');
        exit;
    }
}

$val = fn($k) => e((string) ($u[$k] ?? ''));
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" id="html">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile · Oweili</title>
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
.nav-item > a{display:flex;align-items:center;gap:4px;padding:0 12px;height:100%;font-size:10px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:rgba(0,0,0,0.65);text-decoration:none;transition:color 0.2s;white-space:nowrap;border-bottom:2px solid transparent;}
.nav-item > a:hover{color:#000;}
.nav-item:hover > a{color:#0055ff;border-bottom-color:#0055ff;}
.nav-item > a .chevron{width:10px;height:10px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;transition:transform 0.2s;flex-shrink:0;}
.nav-item:hover > a .chevron{transform:rotate(180deg);}
.dropdown{position:absolute;top:100%;left:0;min-width:200px;background:rgba(255,255,255,0.96);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(0,0,0,0.07);border-radius:14px;box-shadow:0 16px 40px rgba(0,0,0,0.1);padding:8px;opacity:0;visibility:hidden;transform:translateY(8px);transition:opacity 0.2s,transform 0.2s,visibility 0.2s;pointer-events:none;}
.nav-item:hover .dropdown{opacity:1;visibility:visible;transform:translateY(0);pointer-events:auto;}
.dropdown a{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:9px;font-size:12px;color:rgba(0,0,0,0.7);text-decoration:none;transition:background 0.15s,color 0.15s;}
.dropdown a:hover{background:rgba(0,100,255,0.07);color:#0055ff;}
.dropdown a .d-icon{width:14px;height:14px;fill:currentColor;opacity:0.6;flex-shrink:0;}
.nav-right{display:flex;align-items:center;gap:6px;flex-shrink:0;}
.nav-btn{padding:5px 12px;border-radius:999px;font-size:10px;font-weight:600;letter-spacing:0.04em;text-transform:uppercase;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;transition:all 0.22s;border:1px solid rgba(0,0,0,0.1);background:rgba(0,0,0,0.04);color:rgba(0,0,0,0.8);}
.nav-btn:hover{background:rgba(0,0,0,0.08);color:#000;}
.lang-btn{padding:4px 10px;border-radius:999px;font-size:10px;font-weight:600;letter-spacing:0.04em;cursor:pointer;border:1px solid rgba(0,0,0,0.1);background:rgba(255,255,255,0.7);color:rgba(0,0,0,0.7);backdrop-filter:blur(8px);transition:all 0.22s;}
.lang-btn:hover{background:#111111;color:#fff;}
@media(max-width:1024px){.nav-center{display:none;}}

.page{position:relative;z-index:10;width:100%;max-width:720px;margin:0 auto;padding:100px 24px 56px;}
.gc{background:rgba(255,255,255,0.6);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(0,0,0,0.07);border-radius:20px;}
.head{margin-bottom:22px;}
.head h1{font-size:26px;font-weight:700;color:#111;margin-bottom:4px;}
.head p{font-size:13px;color:rgba(0,0,0,0.55);}
[dir="rtl"] .head{text-align:right;}
.card{padding:30px;}
.avatar-row{display:flex;align-items:center;gap:18px;margin-bottom:22px;}
[dir="rtl"] .avatar-row{flex-direction:row-reverse;}
.avatar-prev{width:84px;height:84px;border-radius:50%;object-fit:cover;background-size:cover;background-position:center;background:linear-gradient(135deg,#ff0055,#0066ff,#aa00ff);display:flex;align-items:center;justify-content:center;color:#fff;font-size:32px;font-weight:700;flex-shrink:0;border:3px solid rgba(255,255,255,0.8);box-shadow:0 6px 18px rgba(0,0,0,0.1);}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.field{margin-bottom:14px;}
.field label{display:block;font-size:11px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:rgba(0,0,0,0.55);margin-bottom:6px;}
.field input,.field textarea{width:100%;padding:11px 14px;border-radius:12px;border:1px solid rgba(0,0,0,0.12);background:rgba(255,255,255,0.7);font-size:14px;color:#111;font-family:inherit;transition:border 0.2s,box-shadow 0.2s;}
.field textarea{resize:vertical;min-height:70px;}
.field input:focus,.field textarea:focus{outline:none;border-color:#0066ff;box-shadow:0 0 0 3px rgba(0,100,255,0.12);}
.field input[type=file]{padding:9px 12px;font-size:12px;cursor:pointer;}
.field input[type=file]::file-selector-button{margin-right:12px;padding:6px 14px;border-radius:999px;border:none;background:#111;color:#fff;font-size:11px;font-weight:700;text-transform:uppercase;cursor:pointer;font-family:inherit;}
[dir="rtl"] .field input[type=file]::file-selector-button{margin-right:0;margin-left:12px;}
.actions{display:flex;gap:10px;margin-top:6px;}
[dir="rtl"] .actions{flex-direction:row-reverse;}
.btn-submit{padding:13px 30px;border-radius:999px;background:#111;color:#fff;font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;border:none;cursor:pointer;transition:all 0.25s;}
.btn-submit:hover{background:#ff0055;transform:translateY(-2px);}
.btn-cancel{padding:13px 30px;border-radius:999px;background:rgba(0,0,0,0.06);color:#111;font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;text-decoration:none;transition:all 0.25s;}
.btn-cancel:hover{background:rgba(0,0,0,0.12);}
.err-box{background:rgba(255,0,85,0.08);border:1px solid rgba(255,0,85,0.25);color:#ff0055;font-size:13px;font-weight:600;padding:11px 14px;border-radius:12px;margin-bottom:16px;}
[dir="rtl"] .card{text-align:right;}
@media(max-width:768px){.topnav{padding:10px 14px;}.nav-logo span{display:none;}.grid2{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="bg-wrap"><video class="bg-video" id="vid" autoplay loop muted playsinline src="bg.mp4"></video></div>
<div class="overlay"></div>

<?php include __DIR__ . "/nav.php"; ?>

<div class="page" id="pg">
  <div class="head">
    <h1 id="t-title">Edit Profile</h1>
    <p id="t-sub">Update your public artist profile.</p>
  </div>

  <div class="gc card">
    <?php if ($err): ?>
      <div class="err-box" id="err"
           data-en="<?= $err==='name' ? 'Please enter your name.' : ($err==='phone' ? 'Please enter a valid phone number.' : 'Profile picture must be a JPG or PNG under 5 MB.') ?>"
           data-ar="<?= $err==='name' ? 'يرجى إدخال اسمك.' : ($err==='phone' ? 'يرجى إدخال رقم هاتف صالح.' : 'يجب أن تكون الصورة الشخصية بصيغة JPG أو PNG وأقل من 5 ميجابايت.') ?>">
      </div>
    <?php endif; ?>

    <form method="post" action="edit_profile.php" enctype="multipart/form-data">
      <?= csrfField() ?>

      <div class="avatar-row">
        <?php if (!empty($u['profile_picture'])): ?>
          <div class="avatar-prev" id="avPrev" style="background-image:url('<?= $val('profile_picture') ?>');"></div>
        <?php else: ?>
          <div class="avatar-prev" id="avPrev"><?= e(mb_strtoupper(mb_substr($u['full_name_en'] ?: 'A',0,1))) ?></div>
        <?php endif; ?>
        <div style="flex:1;">
          <label id="t-l-photo" style="display:block;font-size:11px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:rgba(0,0,0,0.55);margin-bottom:6px;">Profile picture (JPG/PNG, max 5MB)</label>
          <input type="file" name="profile_picture" accept="image/jpeg,image/png" onchange="previewPhoto(this)">
        </div>
      </div>

      <div class="grid2">
        <div class="field"><label id="t-l-nen">Artist name (English)</label><input type="text" name="full_name_en" required value="<?= $val('full_name_en') ?>"></div>
        <div class="field"><label id="t-l-nar">Artist name (Arabic)</label><input type="text" name="full_name_ar" dir="rtl" value="<?= $val('full_name_ar') ?>"></div>
      </div>

      <div class="grid2">
        <div class="field"><label id="t-l-city">City</label><input type="text" name="city" value="<?= $val('city') ?>"></div>
        <div class="field"><label id="t-l-phone">Phone number</label><input type="tel" name="phone" dir="ltr" value="<?= $val('phone') ?>"></div>
      </div>

      <div class="field"><label id="t-l-ben">Bio (English)</label><textarea name="bio_en"><?= $val('bio_en') ?></textarea></div>
      <div class="field"><label id="t-l-bar">Bio (Arabic)</label><textarea name="bio_ar" dir="rtl"><?= $val('bio_ar') ?></textarea></div>

      <div class="grid2">
        <div class="field"><label id="t-l-ig">Instagram</label><input type="text" name="instagram" dir="ltr" value="<?= $val('instagram') ?>" placeholder="https://instagram.com/…"></div>
        <div class="field"><label id="t-l-tw">Twitter / X</label><input type="text" name="twitter" dir="ltr" value="<?= $val('twitter') ?>" placeholder="https://x.com/…"></div>
      </div>
      <div class="field"><label id="t-l-web">Website</label><input type="text" name="website" dir="ltr" value="<?= $val('website') ?>" placeholder="https://…"></div>

      <div class="actions">
        <button type="submit" class="btn-submit" id="t-save">Save Changes</button>
        <a class="btn-cancel" href="<?= e(dashboardUrl($u['role'] ?? '')) ?>" id="t-cancel">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
function previewPhoto(inp){
  if(inp.files && inp.files[0]){
    var r=new FileReader();
    r.onload=function(e){var p=document.getElementById('avPrev');p.textContent='';p.style.backgroundImage="url('"+e.target.result+"')";};
    r.readAsDataURL(inp.files[0]);
  }
}
const T={
  en:{dir:'ltr',lb:'العربية',title:'Edit Profile',sub:'Update your public artist profile.',
    photo:'Profile picture (JPG/PNG, max 5MB)',nen:'Artist name (English)',nar:'Artist name (Arabic)',
    city:'City',phone:'Phone number',ben:'Bio (English)',bar:'Bio (Arabic)',ig:'Instagram',tw:'Twitter / X',web:'Website',
    save:'Save Changes',cancel:'Cancel',
    nav:{studio:'The Studio',community:'Community',marketplace:'Marketplace',explore:'Explore Art Styles',support:'Support',
      s1:'Watercolor Workshop',s2:'Oil Painting Studio',s3:'Digital Art Lab',s4:'Charcoal & Ink',
      c2:'Meet Fellow Artists',c3:'Share Your Work',c4:'Learn Together',
      sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',login:'Sign In',reg:'Join Free'}},
  ar:{dir:'rtl',lb:'English',title:'تعديل الملف الشخصي',sub:'حدّث ملفك الشخصي العام كفنان.',
    photo:'الصورة الشخصية (JPG/PNG، بحد أقصى 5 ميجابايت)',nen:'اسم الفنان (بالإنجليزية)',nar:'اسم الفنان (بالعربية)',
    city:'المدينة',phone:'رقم الهاتف',ben:'نبذة (بالإنجليزية)',bar:'نبذة (بالعربية)',ig:'إنستغرام',tw:'تويتر / X',web:'الموقع الإلكتروني',
    save:'حفظ التغييرات',cancel:'إلغاء',
    nav:{studio:'الاستوديو',community:'المجتمع',marketplace:'السوق',explore:'استكشف أساليب الرسم',support:'الدعم',
      s1:'ورشة الألوان المائية',s2:'استوديو الرسم الزيتي',s3:'مختبر الفن الرقمي',s4:'الفحم والحبر',
      c2:'تعرّف على فنانين',c3:'شارك أعمالك',c4:'تعلّم معاً',
      sp1:'تواصل معنا',sp2:'كيف يعمل الموقع',sp3:'شروط الاستخدام',login:'تسجيل الدخول',reg:'انضم مجاناً'}}
};
let L='en';
function setTxt(id,v){const el=document.getElementById(id);if(el)el.textContent=v;}
function apply(l){
  const t=T[l];
  document.getElementById('html').lang=l;
  document.getElementById('html').setAttribute('dir',t.dir);
  document.documentElement.setAttribute('dir',t.dir);
  document.getElementById('topnav').setAttribute('dir',t.dir);
  document.getElementById('lb').textContent=t.lb;
  setTxt('t-title',t.title);setTxt('t-sub',t.sub);
  setTxt('t-l-photo',t.photo);setTxt('t-l-nen',t.nen);setTxt('t-l-nar',t.nar);
  setTxt('t-l-city',t.city);setTxt('t-l-phone',t.phone);setTxt('t-l-ben',t.ben);setTxt('t-l-bar',t.bar);
  setTxt('t-l-ig',t.ig);setTxt('t-l-tw',t.tw);setTxt('t-l-web',t.web);
  setTxt('t-save',t.save);setTxt('t-cancel',t.cancel);
  const err=document.getElementById('err');if(err){const v=l==='ar'?err.dataset.ar:err.dataset.en;if(v)err.textContent=v;}
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
</body>
</html>
