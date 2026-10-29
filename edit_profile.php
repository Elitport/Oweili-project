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
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
/* Edit profile — layout only; card, fields, buttons and messages come from oweili.css */
.page{position:relative;z-index:10;width:100%;max-width:760px;margin:0 auto;padding:calc(var(--nav-h) + var(--space-xl)) var(--space-lg) var(--space-2xl);}
.head{margin-bottom:var(--space-md);}
.head h1{font-size:var(--fs-section);margin:0 0 4px;}
.head p{margin:0;}
.avatar-row{display:flex;align-items:center;gap:var(--space-sm);margin-bottom:var(--space-md);}
.avatar-row > div:last-child{flex:1;min-width:0;}
.avatar-row input[type=file]{width:100%;}
.avatar-prev{width:84px;height:84px;border-radius:50%;background-size:cover;background-position:center;background-color:var(--color-artist);display:flex;align-items:center;justify-content:center;color:var(--color-on-primary);font-size:30px;font-weight:700;flex-shrink:0;}
.actions{display:flex;flex-wrap:wrap;gap:var(--space-sm);margin-top:var(--space-xs);}
@media(max-width:768px){.page{padding:calc(var(--nav-h) + var(--space-md)) var(--space-sm) var(--space-xl);}}
</style>
</head>
<body>
<div class="bg-wrap"><video class="bg-video" id="vid" autoplay loop muted playsinline src="<?= owBase() ?>bg.mp4"></video></div>
<div class="overlay"></div>

<?php include __DIR__ . "/nav.php"; ?>

<div class="page" id="pg">
  <div class="head">
    <h1 id="t-title">Edit Profile</h1>
    <p id="t-sub">Update your public artist profile.</p>
  </div>

  <div class="card">
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
          <label id="t-l-photo" class="label" style="display:block;margin-bottom:var(--space-xs);">Profile picture (JPG/PNG, max 5MB)</label>
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
        <button type="submit" class="btn btn-primary" id="t-save">Save Changes</button>
        <a class="btn btn-secondary" href="<?= e(dashboardUrl($u['role'] ?? '')) ?>" id="t-cancel">Cancel</a>
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
    nav:{community:'Community',marketplace:'Gallery',explore:'Explore Art Styles',support:'Support',
      sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',login:'Sign In',reg:'Join Free'}},
  ar:{dir:'rtl',lb:'English',title:'تعديل الملف الشخصي',sub:'حدّث ملفك الشخصي العام كفنان.',
    photo:'الصورة الشخصية (JPG/PNG، بحد أقصى 5 ميجابايت)',nen:'اسم الفنان (بالإنجليزية)',nar:'اسم الفنان (بالعربية)',
    city:'المدينة',phone:'رقم الهاتف',ben:'نبذة (بالإنجليزية)',bar:'نبذة (بالعربية)',ig:'إنستغرام',tw:'تويتر / X',web:'الموقع الإلكتروني',
    save:'حفظ التغييرات',cancel:'إلغاء',
    nav:{community:'المجتمع',marketplace:'المعرض',explore:'استكشف أساليب الرسم',support:'الدعم',
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
