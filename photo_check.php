<?php
/* =====================================================================
 *  photo_check.php — "Photo check" for admins (admin.php?photo_check=1)
 *  Shows whether the server can make the small phone-sized copies of
 *  artwork photos (img.php): GD / Imagick, memory, folders, and the
 *  newest artworks with their photo address, size and a live test.
 *  Opened only from admin.php after the admin password check; opening
 *  this file directly shows nothing.
 * ===================================================================== */
if (!defined('OW_ADMIN_OK')) { http_response_code(404); exit; }

$gd   = function_exists('gd_info') ? gd_info() : [];
$memBefore = (string) ini_get('memory_limit');
if (function_exists('ini_set')) @ini_set('memory_limit', '384M');   // some hosts switch ini_set off
$memAfter  = (string) ini_get('memory_limit');
$up    = __DIR__ . '/uploads';
$thumb = $up . '/thumbs';
$yes = fn(bool $ok, string $en, string $ar) => '<span class="pc-' . ($ok ? 'ok' : 'bad') . '">' . ($ok ? '✓ ' : '✗ ') . owBi($en, $ar) . '</span>';

$checks = [
    ['GD image library', 'مكتبة الصور GD', $yes(!empty($gd), !empty($gd) ? 'Available ' . ($gd['GD Version'] ?? '') : 'Missing', !empty($gd) ? 'متوفرة' : 'غير متوفرة')],
    ['GD: JPG / PNG / WEBP', 'GD: JPG / PNG / WEBP', e(implode(' · ', [
        'JPG ' . (!empty($gd['JPEG Support']) ? '✓' : '✗'),
        'PNG ' . (!empty($gd['PNG Support']) ? '✓' : '✗'),
        'WEBP ' . (!empty($gd['WebP Support']) ? '✓' : '✗')]))],
    ['Imagick (backup)', 'Imagick (بديل)', $yes(class_exists('Imagick'), class_exists('Imagick') ? 'Available' : 'Not installed', class_exists('Imagick') ? 'متوفرة' : 'غير مثبتة')],
    ['Phone rotation (EXIF)', 'دوران صور الجوال (EXIF)', $yes(function_exists('exif_read_data'), function_exists('exif_read_data') ? 'Available' : 'Missing', function_exists('exif_read_data') ? 'متوفر' : 'غير متوفر')],
    ['Memory limit', 'حد الذاكرة', e($memBefore . ' → ' . $memAfter . ' (img.php asks for 384M)')],
    ['PHP version', 'إصدار PHP', e(PHP_VERSION)],
    ['uploads/ folder', 'مجلد uploads/', $yes(is_dir($up) && is_writable($up), is_dir($up) ? (is_writable($up) ? 'Writable' : 'Not writable') : 'Missing', is_dir($up) ? (is_writable($up) ? 'قابل للكتابة' : 'غير قابل للكتابة') : 'غير موجود')],
    ['uploads/thumbs/ (small copies)', 'uploads/thumbs/ (النسخ الصغيرة)', is_dir($thumb)
        ? $yes(is_writable($thumb), (is_writable($thumb) ? 'Writable' : 'Not writable') . ' · ' . count(glob($thumb . '/*.jpg') ?: []) . ' copies', (is_writable($thumb) ? 'قابل للكتابة' : 'غير قابل للكتابة'))
        : $yes(is_writable($up), 'Not made yet (made on first photo)', 'لم يُنشأ بعد')],
];

/* ---- "Make all small copies": every artwork photo and stage photo, one at a time ---- */
$made = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['make_copies'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) { http_response_code(403); exit('Invalid CSRF token.'); }
    @set_time_limit(120);
    $paths = [];
    try {
        foreach (getDB()->query("SELECT image_url FROM artworks WHERE image_url <> ''")->fetchAll(PDO::FETCH_COLUMN) as $u) $paths[] = $u;
        if (owStagesReady()) foreach (getDB()->query('SELECT photo_path FROM artwork_stages')->fetchAll(PDO::FETCH_COLUMN) as $u) $paths[] = $u;
    } catch (Throwable $e) { /* table missing: nothing to do */ }
    $made = ['done' => 0, 'skip' => 0, 'fail' => 0, 'left' => 0];
    $stop = microtime(true) + 50;                                  // stay well under the server's time limit
    foreach (array_unique($paths) as $u) {
        $src = owImgSrc((string) $u);
        if (strpos($src, owBase() . 'uploads/') !== 0) { $made['skip']++; continue; }   // photo on another website
        $rel = rawurldecode(substr($src, strlen(owBase())));
        foreach ([400, 800, 1600] as $w) {
            if (microtime(true) > $stop) { $made['left']++; continue 2; }
            owMakeThumb($rel, $w) !== '' ? $made['done']++ : $made['fail']++;
        }
    }
}

$rows = [];
try {
    $rows = getDB()->query('SELECT id, title_en, title_ar, image_url FROM artworks WHERE image_url <> \'\' ORDER BY id DESC LIMIT 8')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $rows = []; $dbErr = true; }
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>Photo check · Oweili</title>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
.wrap{max-width:1100px;margin:0 auto;padding:var(--space-lg);}
.pc-ok{color:var(--color-dark);font-weight:700;}
.pc-bad{color:var(--color-dark);font-weight:700;background:rgba(var(--rgb-primary),.15);padding:0 6px;border-radius:6px;}
.pc-list{display:grid;gap:var(--space-sm);}
.pc-aw{display:grid;grid-template-columns:160px 1fr;gap:var(--space-sm);align-items:start;}
.pc-aw img{width:160px;height:120px;object-fit:contain;background:var(--color-card);border:1px solid var(--color-border);border-radius:var(--radius-input);}
.pc-aw code{font-size:var(--fs-label);word-break:break-all;}
.pc-res{font-weight:700;}
.card + .card{margin-top:var(--space-md);}
@media(max-width:640px){.wrap{padding:var(--space-sm);}.pc-tbl th,.pc-tbl td{display:block;width:100%;}.pc-tbl td{padding-top:0;}.pc-aw{grid-template-columns:1fr;}}
</style>
</head>
<body class="staff-page">
<div class="wrap dash role-admin">
  <div class="top">
    <div>
      <h1><?= owBi('Photo check', 'فحص الصور') ?></h1>
      <div class="sub"><?= owBi('Can the server make the small phone copies of artwork photos?', 'هل يستطيع الخادم صنع نسخ صغيرة من صور الأعمال للجوال؟') ?></div>
    </div>
    <div class="top-actions">
      <?php owCornerControls('tgl()'); ?>
      <a class="btn btn-dark btn-sm" href="admin.php"><?= owBi('Back to Admin', 'العودة للوحة') ?></a>
    </div>
  </div>

  <div class="card">
    <table class="table pc-tbl">
      <?php foreach ($checks as $c): ?>
      <tr><th><?= owBi($c[0], $c[1]) ?></th><td><?= $c[2] ?></td></tr>
      <?php endforeach; ?>
    </table>
  </div>

  <div class="card">
    <h2><?= owBi('Make all small copies', 'اصنع كل النسخ الصغيرة') ?></h2>
    <p><?= owBi('Makes the phone-sized copies of every artwork photo now, one at a time, so phones never wait for them. New uploads get their copies by themselves.', 'يصنع نسخ الجوال لكل صور الأعمال الآن، واحدة تلو الأخرى، حتى لا ينتظرها الجوال أبداً. الصور الجديدة تحصل على نسخها تلقائياً.') ?></p>
    <form method="post" action="admin.php?photo_check=1" style="margin-top:var(--space-sm)">
      <?= csrfField() ?>
      <button class="btn btn-primary" type="submit" name="make_copies" value="1"><?= owBi('Make all small copies', 'اصنع كل النسخ الصغيرة') ?></button>
    </form>
    <?php if ($made !== null): ?>
      <p style="margin-top:var(--space-sm)"><?= $yes($made['fail'] === 0,
        'Ready: ' . $made['done'] . ' copies' . ($made['fail'] ? ' · could not make ' . $made['fail'] : '') . ($made['skip'] ? ' · ' . $made['skip'] . ' photos on other websites' : ''),
        'جاهز: ' . $made['done'] . ' نسخة' . ($made['fail'] ? ' · تعذّر صنع ' . $made['fail'] : '') . ($made['skip'] ? ' · ' . $made['skip'] . ' صور على مواقع أخرى' : '')) ?></p>
      <?php if ($made['left']): ?><p><?= owBi('Some photos are still waiting. Press the button again to finish.', 'ما زالت بعض الصور بانتظار دورها. اضغط الزر مرة أخرى لإكمالها.') ?></p><?php endif; ?>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2><?= owBi('Newest artwork photos', 'أحدث صور الأعمال') ?></h2>
    <p><?= owBi('Each small copy below is loaded the same way phones load it. "Small copy works" means phones get a light photo.', 'كل نسخة صغيرة هنا تُحمّل كما يحمّلها الجوال. «النسخة الصغيرة تعمل» تعني أن الجوال يحصل على صورة خفيفة.') ?></p>
    <?php if (!$rows): ?><p><?= !empty($dbErr) ? owBi('Could not read the artworks from the database.', 'تعذّرت قراءة الأعمال من قاعدة البيانات.') : owBi('No artwork photos found.', 'لا توجد صور أعمال.') ?></p><?php endif; ?>
    <div class="pc-list">
    <?php foreach ($rows as $r):
        $raw   = (string) $r['image_url'];
        $clean = owImgSrc($raw);
        $small = owImgSized($clean, 400);
        $local = strpos($clean, owBase()) === 0 ? __DIR__ . '/' . rawurldecode(substr($clean, strlen(owBase()))) : '';
        $exists = $local !== '' && is_file($local);
        $dim    = $exists ? @getimagesize($local) : false;
    ?>
      <div class="pc-aw">
        <img src="<?= e($small) ?>" alt="<?= e($r['title_en'] . ' · ' . $r['title_ar']) ?>" data-res="res<?= (int) $r['id'] ?>" data-small="<?= $small !== $clean ? '1' : '0' ?>">
        <div>
          <strong>#<?= (int) $r['id'] ?> · <?= owBi((string) $r['title_en'], (string) $r['title_ar']) ?></strong><br>
          <?= owBi('Saved address:', 'العنوان المحفوظ:') ?> <code><?= e($raw) ?></code><br>
          <?= owBi('Used address:', 'العنوان المستخدم:') ?> <code><?= e($clean) ?></code><br>
          <?= owBi('File on server:', 'الملف على الخادم:') ?>
          <?= $local === '' ? owBi('on another website', 'في موقع آخر') : ($exists
              ? e(number_format(filesize($local) / 1048576, 1) . ' MB' . ($dim ? ' · ' . $dim[0] . '×' . $dim[1] : ' · not a readable picture'))
              : '<span class="pc-bad">' . owBi('Not found', 'غير موجود') . '</span>') ?><br>
          <?= owBi('Phone copy:', 'نسخة الجوال:') ?> <code><?= e($small) ?></code><br>
          <span class="pc-res" id="res<?= (int) $r['id'] ?>">…</span>
          · <a href="<?= e($clean) ?>" target="_blank" rel="noopener"><?= owBi('Open original', 'فتح الأصل') ?></a>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  </div>
</div>
<script>
document.querySelectorAll('img[data-res]').forEach(function (im) {
  var out = document.getElementById(im.dataset.res);
  function show() {
    if (!im.complete) return;
    var ok = im.naturalWidth > 0, small = im.dataset.small === '1';
    out.className = 'pc-res ' + (ok && (!small || im.naturalWidth <= 400) ? 'pc-ok' : 'pc-bad');
    out.textContent = !ok ? '✗ Photo does not load / الصورة لا تظهر'
      : !small ? '✗ No small copy (photo is outside uploads/) / لا توجد نسخة صغيرة'
      : im.naturalWidth <= 400 ? '✓ Small copy works (' + im.naturalWidth + 'px) / النسخة الصغيرة تعمل'
      : '✗ Got the full photo (' + im.naturalWidth + 'px), small copy failed / وصلت الصورة الكاملة';
  }
  im.addEventListener('load', show); im.addEventListener('error', function () { out.className = 'pc-res pc-bad'; out.textContent = '✗ Photo does not load / الصورة لا تظهر'; });
  show();
});
</script>
<script>
function tgl(){ var ar = document.documentElement.getAttribute('dir') !== 'rtl'; document.documentElement.setAttribute('dir', ar ? 'rtl' : 'ltr'); document.documentElement.lang = ar ? 'ar' : 'en';
  document.getElementById('lb').textContent = ar ? 'English' : 'العربية'; try { localStorage.setItem('lang', ar ? 'ar' : 'en'); } catch (e) {} }
try { if (localStorage.getItem('lang') === 'ar') tgl(); } catch (e) {}
</script>
</body>
</html>
