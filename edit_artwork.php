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
    $pType  = (string) ($_POST['type'] ?? '');
    // one of the 50 styles, or the older type this artwork already has
    $type   = (isset($ART_TYPES[$pType]) && (empty($ART_TYPES[$pType]['legacy']) || $pType === (string) $aw['type'])) ? $pType : '';
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
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="assets/css/oweili.css?v=<?= @filemtime(__DIR__ . '/assets/css/oweili.css') ?>">
<style>
/* Edit artwork — layout only; card, fields, buttons and messages come from oweili.css */
.page{position:relative;z-index:10;width:100%;max-width:760px;margin:0 auto;padding:calc(var(--nav-h) + var(--space-xl)) var(--space-lg) var(--space-2xl);}
.gc{background:var(--color-surface);border:1px solid var(--color-border);border-radius:var(--radius-card);box-shadow:var(--shadow-card);padding:var(--space-lg);}
.gc h1{font-size:var(--fs-section);margin:0 0 var(--space-xs);}
.sub{margin-bottom:var(--space-md);}
.cur-img{width:100%;aspect-ratio:4/3;max-height:320px;object-fit:contain;background:var(--color-frame);border:1px solid var(--color-border);border-radius:12px;margin-bottom:var(--space-md);}
.row{display:flex;flex-wrap:wrap;gap:var(--space-sm);align-items:center;margin-top:var(--space-xs);}
@media(max-width:768px){.page{padding:calc(var(--nav-h) + var(--space-md)) var(--space-sm) var(--space-xl);}.gc{padding:var(--space-md);}}
</style>
<style>
.lock-note{font-size:15px;line-height:1.6;color:var(--color-dark);background:var(--color-tint-blue);border:1px solid rgba(var(--rgb-accent),0.2);border-radius:var(--radius-input);padding:12px 16px;}
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

    <?php if (($curImg = owImgSrc($aw['image_url'])) !== ''): ?>
      <img class="cur-img" src="<?= e($curImg) ?>" alt="<?= e($aw['title_en'] . ' · ' . $aw['title_ar']) ?>">
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
            <?php if (!empty($ART_TYPES[$aw['type']]['legacy'])): $lbl = $ART_TYPES[$aw['type']]; ?>
              <option value="<?= e($aw['type']) ?>" data-en="<?= e($lbl['en']) ?>" data-ar="<?= e($lbl['ar']) ?>" selected><?= e($lbl['en']) ?></option>
            <?php endif; ?>
            <?php foreach (owStyleGroups() as $gKey => $g): ?>
              <optgroup label="<?= e($g['en']) ?>" data-en="<?= e($g['en']) ?>" data-ar="<?= e($g['ar']) ?>">
              <?php foreach ($ART_TYPES as $slug => $lbl): if (($lbl['cat'] ?? '') !== $gKey || !empty($lbl['legacy'])) continue; ?>
                <option value="<?= e($slug) ?>" data-en="<?= e($lbl['en']) ?>" data-ar="<?= e($lbl['ar']) ?>" <?= $aw['type'] === $slug ? 'selected' : '' ?>><?= e($lbl['en']) ?></option>
              <?php endforeach; ?>
              </optgroup>
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
        <button type="submit" class="btn btn-primary" id="t-save">Save Changes</button>
        <a class="btn btn-secondary" href="artist_dashboard.php?id=<?= (int) $viewerId ?>" id="t-cancel">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
let L = 'en';

/* Bilingual nav + labels */
const NAV={
  en:{lb:'العربية',community:'Community',marketplace:'Gallery',explore:'Explore Art Styles',support:'Support',
    sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',login:'Sign In',reg:'Join Free',
    title:'Edit Artwork',sub:'Update the details of your artwork.',
    ten:'Title in English',tar:'Title in Arabic',den:'Description in English',dar:'Description in Arabic',
    price:'Price in SAR',type:'Type',status:'Status',img:'Replace Image (optional, JPG/PNG/WEBP, max 5MB)',
    save:'Save Changes',cancel:'Cancel'},
  ar:{lb:'English',community:'المجتمع',marketplace:'المعرض',explore:'استكشف أساليب الرسم',support:'الدعم',
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
  setTxt('nav-community-label',n.community);
  setTxt('nav-marketplace-label',n.marketplace);setTxt('nav-explore-label',n.explore);setTxt('nav-support-label',n.support);
  
  setTxt('dd-sp1',n.sp1);setTxt('dd-sp2',n.sp2);setTxt('dd-sp3',n.sp3);
  setTxt('n-login-t',n.login);setTxt('n-reg-t',n.reg);
  setTxt('t-title',n.title);setTxt('t-sub',n.sub);
  setTxt('t-l-ten',n.ten);setTxt('t-l-tar',n.tar);setTxt('t-l-den',n.den);setTxt('t-l-dar',n.dar);
  setTxt('t-l-price',n.price);setTxt('t-l-type',n.type);setTxt('t-l-status',n.status);setTxt('t-l-img',n.img);
  setTxt('t-save',n.save);setTxt('t-cancel',n.cancel);
  document.querySelectorAll('.type-select option, .status-select option').forEach(o=>{
    o.textContent = (l==='ar' ? o.dataset.ar : o.dataset.en) || o.textContent; });
  document.querySelectorAll('.type-select optgroup').forEach(g=>{ g.label = (l==='ar' ? g.dataset.ar : g.dataset.en) || g.label; });
  const eb=document.getElementById('errbox'); if(eb){ const v=l==='ar'?eb.dataset.ar:eb.dataset.en; if(v) eb.textContent=v; }
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
