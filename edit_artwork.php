<?php
require_once 'config.php';
require_once __DIR__ . '/artwork_lib.php';

if (!isLoggedIn()) { header('Location: login.php'); exit; }
$viewerId  = (int) $_SESSION['user_id'];
$ART_TYPES = require __DIR__ . '/art_types.php';

$artId = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_POST['artwork_id'] ?? 0);

/* Load the artwork — must belong to the logged-in user (ownership check). */
$stmt = getDB()->prepare('SELECT * FROM artworks WHERE id = :id AND artist_id = :uid LIMIT 1');
$stmt->execute([':id' => $artId, ':uid' => $viewerId]);
$aw = $stmt->fetch();
if (!$aw) { http_response_code(404); exit('Artwork not found or not yours.'); }
/* Unfinished 3-stage drafts are completed in the upload flow, not here. */
if (!empty($aw['is_draft'])) { header('Location: upload_artwork.php?id=' . (int) $aw['id']); exit; }

/* Artworks verified through the 3-stage process keep their main photo:
   swapping it would break the proof that buyers and reviewers rely on. */
$photoLocked = owGetStages((int) $aw['id']) !== [];

$err = '';

/* ---- Handle save ---- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) { http_response_code(403); exit('Invalid CSRF token.'); }

    $tEn    = sanitize($_POST['title_en'] ?? '');
    $tAr    = sanitize($_POST['title_ar'] ?? '');
    $dEn    = sanitize($_POST['desc_en'] ?? '');
    $dAr    = sanitize($_POST['desc_ar'] ?? '');
    $price  = $_POST['price'] ?? '';
    $type   = array_key_exists($_POST['type'] ?? '', $ART_TYPES) ? $_POST['type'] : '';
    $status = in_array($_POST['status'] ?? '', ['available','sold','auction'], true) ? $_POST['status'] : $aw['status'];

    // Descriptions are optional (same as the 3-stage upload); titles, type and price are required.
    if ($tEn === '' || $tAr === '' || $type === '' || !is_numeric($price) || (float) $price < 0) {
        $err = 'required';
    } else {
        $imageUrl = $aw['image_url'];

        // Optional image replacement — never for 3-stage artworks (checked here on the server too).
        if (!$photoLocked && !empty($_FILES['image']['name'])) {
            $check = validateUpload($_FILES['image']);
            if (!$check['ok']) {
                $err = 'image';
            } else {
                $dir = __DIR__ . '/uploads/artworks/';
                if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
                $fname = safeUploadName($check['ext']);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . $fname)) {
                    // remove the old file if it was a local upload
                    $old = __DIR__ . '/' . ltrim((string) $aw['image_url'], '/');
                    if ($aw['image_url'] && strpos($aw['image_url'], 'uploads/artworks/') === 0 && is_file($old)) {
                        @unlink($old);
                    }
                    $imageUrl = 'uploads/artworks/' . $fname;
                } else {
                    $err = 'image';
                }
            }
        }

        if ($err === '') {
            // Stamp the sale date the first time an artwork becomes 'sold';
            // clear it if it's moved back to available/auction.
            $soldSql = $status === 'sold'
                ? 'sold_at = COALESCE(sold_at, NOW())'
                : 'sold_at = NULL';
            $upd = getDB()->prepare(
                'UPDATE artworks
                 SET title_en=:ten, title_ar=:tar, description_en=:den, description_ar=:dar,
                     price=:price, type=:type, status=:status, image_url=:img, ' . $soldSql . '
                 WHERE id=:id AND artist_id=:uid'
            );
            $upd->execute([
                ':ten' => $tEn, ':tar' => $tAr, ':den' => $dEn, ':dar' => $dAr,
                ':price' => (float) $price, ':type' => $type, ':status' => $status,
                ':img' => $imageUrl, ':id' => $artId, ':uid' => $viewerId,
            ]);
            header('Location: artist_dashboard.php?id=' . $viewerId);
            exit;
        }
    }
    // On error, keep submitted values for redisplay.
    $aw = array_merge($aw, [
        'title_en' => $tEn, 'title_ar' => $tAr,
        'description_en' => $dEn, 'description_ar' => $dAr,
        'price' => $price, 'type' => $type ?: $aw['type'], 'status' => $status,
    ]);
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" id="html">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Artwork · Oweili</title>
<link rel="stylesheet" href="/assets/css/oweili.css">
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
.nav-btn.primary{background:rgba(0,100,255,0.1);border-color:rgba(0,100,255,0.2);color:#0066ff;}
.nav-btn.primary:hover{background:rgba(0,100,255,0.18);}
.lang-btn{padding:4px 10px;border-radius:999px;font-size:12px;font-weight:600;letter-spacing:0.04em;cursor:pointer;border:1px solid rgba(0,0,0,0.1);background:rgba(255,255,255,0.7);color:rgba(0,0,0,0.7);backdrop-filter:blur(8px);transition:all 0.22s;}
.lang-btn:hover{background:#111111;color:#fff;}
@media(max-width:1024px){.nav-center{display:none;}}

.page{position:relative;z-index:10;width:100%;max-width:760px;margin:0 auto;padding:100px 24px 56px;}
.gc{background:rgba(255,255,255,0.6);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(0,0,0,0.07);border-radius:20px;padding:28px;}
h1{font-size:24px;font-weight:700;color:#111111;margin-bottom:6px;}
.sub{font-size:13px;color:rgba(0,0,0,0.55);margin-bottom:22px;}
.cur-img{width:100%;max-height:220px;object-fit:cover;border-radius:14px;margin-bottom:18px;}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.field{margin-bottom:14px;}
.field label{display:block;font-size:12px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:rgba(0,0,0,0.55);margin-bottom:6px;}
.field input,.field textarea,.field select{width:100%;padding:11px 14px;border-radius:12px;border:1px solid rgba(0,0,0,0.12);background:rgba(255,255,255,0.7);font-size:14px;color:#111111;font-family:inherit;}
.field textarea{resize:vertical;min-height:70px;}
.field input:focus,.field textarea:focus,.field select:focus{outline:none;border-color:#0066ff;box-shadow:0 0 0 3px rgba(0,100,255,0.12);}
.row{display:flex;gap:12px;align-items:center;margin-top:8px;}

.btn{padding:13px 30px;border-radius:999px;background:#111111;color:#fff;font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;border:none;cursor:pointer;transition:all 0.25s;text-decoration:none;display:inline-block;}
.btn:hover{background:#ff0055;transform:translateY(-2px);}
.btn.ghost{background:rgba(0,0,0,0.06);color:#111111;}
.btn.ghost:hover{background:rgba(0,0,0,0.12);transform:none;}
.err-box{background:rgba(255,0,85,0.08);border:1px solid rgba(255,0,85,0.25);color:#111111;font-size:13px;font-weight:600;padding:11px 14px;border-radius:12px;margin-bottom:16px;}
[dir="rtl"] .gc{text-align:right;}
@media(max-width:768px){.topnav{padding:10px 14px;}.nav-logo span{display:none;}.grid2{grid-template-columns:1fr;}}
</style>
<style>
html:not([dir="rtl"]) .bi-ar{display:none;}
html[dir="rtl"] .bi-en{display:none;}
.lock-note{font-size:13px;line-height:1.6;color:#111111;background:rgba(0,102,255,0.06);border:1px solid rgba(0,102,255,0.2);border-radius:12px;padding:12px 14px;}
.lock-note::before{content:'🔒 ';}
</style>
</head>
<body>
<div class="bg-wrap"><video class="bg-video" id="vid" autoplay loop muted playsinline src="bg.mp4"></video></div>
<div class="overlay"></div>

<?php include __DIR__ . "/nav.php"; ?>

<div class="page" id="pg">
  <div class="gc">
    <h1 id="t-title">Edit Artwork</h1>
    <div class="sub" id="t-sub">Update the details of your artwork.</div>

    <?php if ($err): ?>
      <div class="err-box"
           data-en="<?= $err === 'image' ? 'The image must be a valid JPG/PNG/WEBP under 5MB.' : 'Both titles are required and the price must be a number.' ?>"
           data-ar="<?= $err === 'image' ? 'يجب أن تكون الصورة JPG/PNG/WEBP بحجم أقل من 5 ميغابايت.' : 'العنوانان مطلوبان ويجب أن يكون السعر رقماً.' ?>"
           id="errbox"><?= $err === 'image' ? 'The image must be a valid JPG/PNG/WEBP under 5MB.' : 'Both titles are required and the price must be a number.' ?></div>
    <?php endif; ?>

    <?php if (!empty($aw['image_url'])): ?>
      <img class="cur-img" src="<?= e($aw['image_url']) ?>" alt="">
    <?php endif; ?>

    <form method="post" action="edit_artwork.php?id=<?= (int) $artId ?>" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="artwork_id" value="<?= (int) $artId ?>">

      <div class="grid2">
        <div class="field"><label id="t-l-ten">Title in English</label>
          <input type="text" id="f-title-en" name="title_en" value="<?= e($aw['title_en']) ?>" required></div>
        <div class="field"><label id="t-l-tar">Title in Arabic</label>
          <input type="text" id="f-title-ar" name="title_ar" dir="rtl" value="<?= e($aw['title_ar']) ?>" required></div>
      </div>
      <div class="grid2">
        <div class="field"><label id="t-l-den">Description in English</label>
          <textarea id="f-desc-en" name="desc_en"><?= e($aw['description_en']) ?></textarea></div>
        <div class="field"><label id="t-l-dar">Description in Arabic</label>
          <textarea id="f-desc-ar" name="desc_ar" dir="rtl"><?= e($aw['description_ar']) ?></textarea></div>
      </div>
      <div class="grid2">
        <div class="field"><label id="t-l-price">Price in SAR</label>
          <input type="number" name="price" min="0" step="0.01" value="<?= e($aw['price']) ?>" required></div>
        <div class="field"><label id="t-l-type">Type</label>
          <select name="type" class="type-select" required>
            <?php foreach ($ART_TYPES as $slug => $lbl): ?>
              <option value="<?= e($slug) ?>" data-en="<?= e($lbl['en']) ?>" data-ar="<?= e($lbl['ar']) ?>" <?= $aw['type'] === $slug ? 'selected' : '' ?>><?= e($lbl['en']) ?></option>
            <?php endforeach; ?>
          </select></div>
      </div>
      <div class="grid2">
        <div class="field"><label id="t-l-status">Status</label>
          <select name="status" class="status-select" required>
            <option value="available" data-en="Available" data-ar="متاح" <?= $aw['status']==='available'?'selected':'' ?>>Available</option>
            <option value="sold" data-en="Sold" data-ar="مباع" <?= $aw['status']==='sold'?'selected':'' ?>>Sold</option>
            <option value="auction" data-en="Auction" data-ar="مزاد" <?= $aw['status']==='auction'?'selected':'' ?>>Auction</option>
          </select></div>
        <?php if ($photoLocked): ?>
        <div class="field"><div class="lock-note" role="note">
          <span class="bi-en" lang="en">This artwork was verified through the 3-stage process. The main photo cannot be changed to protect its authenticity.</span>
          <span class="bi-ar" lang="ar">تم التحقق من هذا العمل عبر نظام المراحل الثلاث. لا يمكن تغيير الصورة الرئيسية للحفاظ على أصالته.</span>
        </div></div>
        <?php else: ?>
        <div class="field"><label id="t-l-img">Replace Image (optional, JPG/PNG/WEBP, max 5MB)</label>
          <input type="file" name="image" accept="image/jpeg,image/png,image/webp"></div>
        <?php endif; ?>
      </div>

      <div class="row">
        <button type="submit" class="btn" id="t-save">Save Changes</button>
        <a class="btn ghost" href="artist_dashboard.php?id=<?= (int) $viewerId ?>" id="t-cancel">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
let L = 'en';

/* Bilingual nav + labels */
const NAV={
  en:{lb:'العربية',studio:'The Studio',community:'Community',marketplace:'Marketplace',explore:'Explore Art Styles',support:'Support',
    s1:'Watercolor Workshop',s2:'Oil Painting Studio',s3:'Digital Art Lab',s4:'Charcoal & Ink',
    c1:'Artist Chat Room',c2:'Meet Fellow Artists',c3:'Share Your Work',c4:'Learn Together',
    sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',login:'Sign In',reg:'Join Free',
    title:'Edit Artwork',sub:'Update the details of your artwork.',
    ten:'Title in English',tar:'Title in Arabic',den:'Description in English',dar:'Description in Arabic',
    price:'Price in SAR',type:'Type',status:'Status',img:'Replace Image (optional, JPG/PNG/WEBP, max 5MB)',
    save:'Save Changes',cancel:'Cancel'},
  ar:{lb:'English',studio:'الاستوديو',community:'المجتمع',marketplace:'السوق',explore:'استكشف أساليب الرسم',support:'الدعم',
    s1:'ورشة الألوان المائية',s2:'استوديو الرسم الزيتي',s3:'مختبر الفن الرقمي',s4:'الفحم والحبر',
    c1:'غرفة محادثة الفنانين',c2:'تعرّف على فنانين',c3:'شارك أعمالك',c4:'تعلّم معاً',
    sp1:'تواصل معنا',sp2:'كيف يعمل الموقع',sp3:'شروط الاستخدام',login:'تسجيل الدخول',reg:'انضم مجاناً',
    title:'تعديل العمل الفني',sub:'حدّث تفاصيل عملك الفني.',
    ten:'العنوان بالإنجليزية',tar:'العنوان بالعربية',den:'الوصف بالإنجليزية',dar:'الوصف بالعربية',
    price:'السعر بالريال',type:'النوع',status:'الحالة',img:'استبدال الصورة (اختياري، JPG/PNG/WEBP، بحد أقصى 5 ميغابايت)',
    save:'حفظ التغييرات',cancel:'إلغاء'}
};
function setTxt(id,v){const el=document.getElementById(id);if(el)el.textContent=v;}
function apply(l){
  L=l; const n=NAV[l];
  document.getElementById('html').lang=l;
  document.getElementById('html').setAttribute('dir', l==='ar'?'rtl':'ltr');
  document.documentElement.setAttribute('dir', l==='ar'?'rtl':'ltr');
  document.getElementById('topnav').setAttribute('dir', l==='ar'?'rtl':'ltr');
  document.getElementById('lb').textContent=n.lb;
  setTxt('nav-studio-label',n.studio);setTxt('nav-community-label',n.community);
  setTxt('nav-marketplace-label',n.marketplace);setTxt('nav-explore-label',n.explore);setTxt('nav-support-label',n.support);
  setTxt('dd-s1',n.s1);setTxt('dd-s2',n.s2);setTxt('dd-s3',n.s3);setTxt('dd-s4',n.s4);
  setTxt('dd-c1',n.c1);setTxt('dd-c2',n.c2);setTxt('dd-c3',n.c3);setTxt('dd-c4',n.c4);
  setTxt('dd-sp1',n.sp1);setTxt('dd-sp2',n.sp2);setTxt('dd-sp3',n.sp3);
  setTxt('n-login-t',n.login);setTxt('n-reg-t',n.reg);
  setTxt('t-title',n.title);setTxt('t-sub',n.sub);
  setTxt('t-l-ten',n.ten);setTxt('t-l-tar',n.tar);setTxt('t-l-den',n.den);setTxt('t-l-dar',n.dar);
  setTxt('t-l-price',n.price);setTxt('t-l-type',n.type);setTxt('t-l-status',n.status);setTxt('t-l-img',n.img);
  setTxt('t-save',n.save);setTxt('t-cancel',n.cancel);
  document.querySelectorAll('.type-select option, .status-select option').forEach(o=>{
    o.textContent = (l==='ar' ? o.dataset.ar : o.dataset.en) || o.textContent; });
  const eb=document.getElementById('errbox'); if(eb){ const v=l==='ar'?eb.dataset.ar:eb.dataset.en; if(v) eb.textContent=v; }
}
function tgl(){L=L==='en'?'ar':'en';apply(L);try{localStorage.setItem('lang',L);}catch(e){}}
try{var _s=localStorage.getItem('lang');if(_s==='ar'||_s==='en')L=_s;}catch(e){}
apply(L);
const v=document.getElementById('vid');
if(v){v.addEventListener('canplay',()=>v.classList.add('on'),{once:true});v.play().catch(()=>{});}
</script>
</body>
</html>
