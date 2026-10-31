<?php
/* =====================================================================
 *  admin_institutes.php — team tool for the Art Institutes directory
 *  (training.php). Add and edit institutes and their courses, instead of
 *  phpMyAdmin. Only sub-admins and admins (owArticleStaff in article_lib.php).
 *  Nothing is ever deleted here: an institute or course is switched off
 *  ("Hidden"), so its history stays. Logos are re-saved like artwork
 *  photos (rotation fixed, hidden data removed) into uploads/institutes/.
 *  Tables: institutes, courses, activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/article_lib.php';
require_once __DIR__ . '/platform_lib.php';

$staff = owArticleStaff();
if (!$staff) {
    http_response_code(403); ?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><?php include __DIR__ . '/theme_head.php'; ?>
<title>Staff only · Oweili</title><meta name="robots" content="noindex"><link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>"></head>
<body><div style="max-width:480px;margin:15vh auto;padding:24px" class="card"><h1><?= owBi('Staff only', 'للفريق فقط') ?></h1><p><a class="btn btn-primary" href="admin.php"><?= owBi('Sign in', 'تسجيل الدخول') ?></a></p></div></body></html>
<?php exit; }
$actor = $staff['user_id'] ? ['type' => 'admin', 'id' => $staff['user_id']] : ['type' => 'admin', 'id' => null];
if (!empty($_SESSION['user_id']) && ($cu = currentUser()) && ($cu['role'] ?? '') === 'sub_admin') $actor = ['type' => 'sub_admin', 'id' => (int) $cu['id']];

$db = getDB();
$ready = owHasTable('institutes') && owHasTable('courses');
$errors = []; $okMsg = '';
$txt = fn(string $k, int $max) => mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) ($_POST[$k] ?? ''))), 0, $max);

if ($ready && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = ['The page was open too long. Please try again.', 'انتهت صلاحية الصفحة. حاول مرة أخرى.'];
    } elseif (($_POST['do'] ?? '') === 'institute') {
        $id = (int) ($_POST['id'] ?? 0);
        $v = ['name_en' => $txt('name_en', 200), 'name_ar' => $txt('name_ar', 200), 'city_en' => $txt('city_en', 120), 'city_ar' => $txt('city_ar', 120),
              'description_en' => $txt('description_en', 4000), 'description_ar' => $txt('description_ar', 4000),
              'contact_email' => strtolower($txt('contact_email', 190)), 'contact_phone' => $txt('contact_phone', 30), 'website_url' => $txt('website_url', 500),
              'is_active' => isset($_POST['is_active']) ? 1 : 0];
        if ($v['name_en'] === '' || $v['name_ar'] === '' || $v['city_en'] === '' || $v['city_ar'] === '') $errors[] = ['Name and city are needed in both languages.', 'الاسم والمدينة مطلوبان باللغتين.'];
        if ($v['contact_email'] !== '' && !filter_var($v['contact_email'], FILTER_VALIDATE_EMAIL)) $errors[] = ['Please check the email address.', 'يرجى مراجعة البريد الإلكتروني.'];
        if ($v['contact_phone'] !== '' && !preg_match('/^\+?[0-9][0-9 \-()]{6,29}$/', $v['contact_phone'])) $errors[] = ['Please check the phone number.', 'يرجى مراجعة رقم الهاتف.'];
        if ($v['website_url'] !== '') { [$link, $ok] = owSafeLink($v['website_url'], 'web'); if ($ok) $v['website_url'] = $link; else $errors[] = ['Please check the website address.', 'يرجى مراجعة عنوان الموقع.']; }
        $logo = null;
        if (!$errors && !empty($_FILES['logo']['name'])) {
            $r = owStoreImage($_FILES['logo'], 'uploads/institutes/');
            if ($r['ok']) $logo = $r['path']; else $errors[] = owUploadErrBi($r['err'], 'Logo', 'الشعار');
        }
        if (!$errors) {
            $cols = array_keys($v); $args = [];
            foreach ($v as $k => $x) $args[':' . $k] = $x;
            if ($logo !== null) { $cols[] = 'logo_path'; $args[':logo_path'] = $logo; }
            if ($id > 0) {
                $set = implode(', ', array_map(fn($c) => "$c = :$c", $cols));
                $db->prepare("UPDATE institutes SET $set WHERE id = :id")->execute($args + [':id' => $id]);
            } else {
                $db->prepare('INSERT INTO institutes (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_map(fn($c) => ":$c", $cols)) . ')')->execute($args);
                $id = (int) $db->lastInsertId();
            }
            owLog('institute_save', 'institute', $id, $v['name_en'], $actor);
            header('Location: admin_institutes.php?ok=1&edit=' . $id . '#i' . $id); exit;
        }
    } elseif (($_POST['do'] ?? '') === 'course') {
        $id  = (int) ($_POST['id'] ?? 0);
        $iid = (int) ($_POST['institute_id'] ?? 0);
        $v = ['title_en' => $txt('title_en', 200), 'title_ar' => $txt('title_ar', 200), 'description_en' => $txt('description_en', 4000), 'description_ar' => $txt('description_ar', 4000),
              'start_date' => owFormDate((string) ($_POST['start_date'] ?? '')), 'end_date' => owFormDate((string) ($_POST['end_date'] ?? '')),
              'price' => max(0, (float) str_replace(',', '', (string) ($_POST['price'] ?? '0'))), 'seats_total' => max(0, (int) ($_POST['seats_total'] ?? 0)),
              'seats_remaining' => max(0, (int) ($_POST['seats_remaining'] ?? 0)), 'is_active' => isset($_POST['is_active']) ? 1 : 0];
        $ex = $db->prepare('SELECT 1 FROM institutes WHERE id = :id'); $ex->execute([':id' => $iid]);
        if (!$ex->fetchColumn()) $errors[] = ['Please choose the institute.', 'يرجى اختيار المعهد.'];
        if ($v['title_en'] === '' || $v['title_ar'] === '') $errors[] = ['The course title is needed in both languages.', 'عنوان الدورة مطلوب باللغتين.'];
        if ($v['seats_remaining'] > $v['seats_total']) $errors[] = ['Seats left cannot be more than all seats.', 'المقاعد المتبقية لا تزيد عن إجمالي المقاعد.'];
        if ($v['start_date'] && $v['end_date'] && $v['end_date'] < $v['start_date']) $errors[] = ['The end date is before the start date.', 'تاريخ النهاية قبل تاريخ البداية.'];
        if (!$errors) {
            $args = [':institute_id' => $iid]; foreach ($v as $k => $x) $args[':' . $k] = $x;
            $cols = array_merge(['institute_id'], array_keys($v));
            if ($id > 0) {
                $db->prepare('UPDATE courses SET ' . implode(', ', array_map(fn($c) => "$c = :$c", $cols)) . ' WHERE id = :id')->execute($args + [':id' => $id]);
            } else {
                $db->prepare('INSERT INTO courses (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_map(fn($c) => ":$c", $cols)) . ')')->execute($args);
                $id = (int) $db->lastInsertId();
            }
            owLog('course_save', 'course', $id, $v['title_en'], $actor);
            header('Location: admin_institutes.php?ok=1&edit=' . $iid . '#i' . $iid); exit;
        }
    }
}

$insts = $ready ? $db->query('SELECT * FROM institutes ORDER BY is_active DESC, name_en ASC')->fetchAll() : [];
$courses = [];
if ($insts) foreach ($db->query('SELECT * FROM courses ORDER BY start_date IS NULL, start_date ASC, id ASC') as $c) $courses[(int) $c['institute_id']][] = $c;
$edit = (int) ($_GET['edit'] ?? 0);
$val = fn(array $row, string $k) => e((string) ($_POST[$k] ?? $row[$k] ?? ''));
function owInstField(string $name, string $en, string $ar, string $value, string $type = 'text', string $extra = ''): string {
    $id = 'f-' . $name . '-' . substr(md5($en . $value), 0, 6);
    $input = $type === 'textarea' ? '<textarea id="' . $id . '" name="' . $name . '" rows="3"' . $extra . '>' . $value . '</textarea>'
                                  : '<input id="' . $id . '" type="' . $type . '" name="' . $name . '" value="' . $value . '"' . $extra . '>';
    return '<div class="field"><label for="' . $id . '">' . owBi($en, $ar) . '</label>' . $input . '</div>';
}
$instForm = function (array $i) use ($val): void { ?>
  <form method="post" action="admin_institutes.php<?= !empty($i['id']) ? '?edit=' . (int) $i['id'] : '' ?>" enctype="multipart/form-data" class="i-form">
    <?= csrfField() ?><input type="hidden" name="do" value="institute"><input type="hidden" name="id" value="<?= (int) ($i['id'] ?? 0) ?>">
    <div class="g2"><?= owInstField('name_en', 'Name (English)', 'الاسم (بالإنجليزية)', $val($i, 'name_en'), 'text', ' required maxlength="200"') ?>
      <?= owInstField('name_ar', 'Name (Arabic)', 'الاسم (بالعربية)', $val($i, 'name_ar'), 'text', ' required maxlength="200" dir="rtl"') ?></div>
    <div class="g2"><?= owInstField('city_en', 'City (English)', 'المدينة (بالإنجليزية)', $val($i, 'city_en'), 'text', ' required maxlength="120"') ?>
      <?= owInstField('city_ar', 'City (Arabic)', 'المدينة (بالعربية)', $val($i, 'city_ar'), 'text', ' required maxlength="120" dir="rtl"') ?></div>
    <div class="g2"><?= owInstField('description_en', 'Short description (English)', 'وصف قصير (بالإنجليزية)', $val($i, 'description_en'), 'textarea') ?>
      <?= owInstField('description_ar', 'Short description (Arabic)', 'وصف قصير (بالعربية)', $val($i, 'description_ar'), 'textarea', ' dir="rtl"') ?></div>
    <div class="g3"><?= owInstField('contact_email', 'Email', 'البريد', $val($i, 'contact_email'), 'email', ' dir="ltr" maxlength="190"') ?>
      <?= owInstField('contact_phone', 'Phone', 'الهاتف', $val($i, 'contact_phone'), 'tel', ' dir="ltr" maxlength="30"') ?>
      <?= owInstField('website_url', 'Website', 'الموقع', $val($i, 'website_url'), 'text', ' dir="ltr" maxlength="500"') ?></div>
    <div class="field"><label><?= owBi('Logo (JPG, PNG or WEBP, optional)', 'الشعار (JPG أو PNG أو WEBP، اختياري)') ?></label><input type="file" name="logo" accept="image/jpeg,image/png,image/webp"></div>
    <label class="chk"><input type="checkbox" name="is_active" value="1"<?= (int) ($i['is_active'] ?? 1) === 1 ? ' checked' : '' ?>> <?= owBi('Shown in the directory', 'يظهر في الدليل') ?></label>
    <button class="btn btn-primary btn-sm" type="submit"><?= owBi('Save institute', 'احفظ المعهد') ?></button>
  </form>
<?php };
$courseForm = function (int $iid, array $c) use ($val): void { ?>
  <form method="post" action="admin_institutes.php?edit=<?= $iid ?>" class="i-form">
    <?= csrfField() ?><input type="hidden" name="do" value="course"><input type="hidden" name="institute_id" value="<?= $iid ?>"><input type="hidden" name="id" value="<?= (int) ($c['id'] ?? 0) ?>">
    <div class="g2"><?= owInstField('title_en', 'Course title (English)', 'عنوان الدورة (بالإنجليزية)', e((string) ($c['title_en'] ?? '')), 'text', ' required maxlength="200"') ?>
      <?= owInstField('title_ar', 'Course title (Arabic)', 'عنوان الدورة (بالعربية)', e((string) ($c['title_ar'] ?? '')), 'text', ' required maxlength="200" dir="rtl"') ?></div>
    <div class="g3"><?= owInstField('start_date', 'Starts', 'تبدأ', e((string) ($c['start_date'] ?? '')), 'date') ?>
      <?= owInstField('end_date', 'Ends', 'تنتهي', e((string) ($c['end_date'] ?? '')), 'date') ?>
      <?= owInstField('price', 'Price in SAR (0 = free)', 'السعر بالريال (0 = مجاني)', e((string) ($c['price'] ?? '0')), 'number', ' min="0" step="0.01"') ?></div>
    <div class="g3"><?= owInstField('seats_total', 'All seats', 'كل المقاعد', e((string) ($c['seats_total'] ?? '0')), 'number', ' min="0"') ?>
      <?= owInstField('seats_remaining', 'Seats left', 'المقاعد المتبقية', e((string) ($c['seats_remaining'] ?? '0')), 'number', ' min="0"') ?>
      <label class="chk" style="align-self:end"><input type="checkbox" name="is_active" value="1"<?= (int) ($c['is_active'] ?? 1) === 1 ? ' checked' : '' ?>> <?= owBi('Shown', 'ظاهرة') ?></label></div>
    <button class="btn btn-dark btn-sm" type="submit"><?= owBi(!empty($c['id']) ? 'Save course' : 'Add course', !empty($c['id']) ? 'احفظ الدورة' : 'أضف الدورة') ?></button>
  </form>
<?php };
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>Institutes · Admin · Oweili</title>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
.wrap{max-width:1000px;margin:0 auto;padding:var(--space-lg) var(--space-sm);}
.top{display:flex;justify-content:space-between;align-items:center;gap:var(--space-sm);flex-wrap:wrap;margin-bottom:var(--space-md);}
.wrap h1{font-size:28px;margin:0 0 4px;}
.wrap .card{margin-bottom:var(--space-md);}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:0 var(--space-sm);}
.g3{display:grid;grid-template-columns:repeat(3,1fr);gap:0 var(--space-sm);}
.chk{display:flex;gap:6px;align-items:center;margin:0 0 var(--space-sm);font-size:15px;}
.i-head{display:flex;justify-content:space-between;gap:var(--space-sm);align-items:center;flex-wrap:wrap;}
.i-head h2{font-size:20px;margin:0;}
.mini{font-size:var(--fs-caption);color:var(--color-body);}
.c-row{border-top:1px solid var(--color-border);padding-top:var(--space-sm);margin-top:var(--space-sm);}
details > summary{cursor:pointer;font-weight:700;margin:var(--space-sm) 0;}
.pillx{font-size:var(--fs-min);font-weight:700;padding:2px 10px;border-radius:var(--radius-pill);border:1px solid var(--color-border-strong);}
@media(max-width:700px){.g2,.g3{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="wrap dash role-admin">
  <div class="top">
    <a class="btn btn-ghost btn-sm" href="admin.php">← <?= owBi('Back to admin', 'العودة للإدارة') ?></a>
    <span><a class="btn btn-ghost btn-sm" href="training.php" target="_blank" rel="noopener"><?= owBi('See the directory', 'شاهد الدليل') ?> ↗</a> <?php owCornerControls('tgl()'); ?></span>
  </div>
  <h1><?= owBi('Institutes and courses', 'المعاهد والدورات') ?></h1>
  <p class="mini"><?= owBi('Everything here appears on the Art Institutes page. Hide something instead of deleting it, so its history stays.', 'كل ما هنا يظهر في صفحة المعاهد الفنية. أخفِ العنصر بدل حذفه ليبقى تاريخه.') ?></p>
  <?php if (!$ready): ?><div class="alert alert-error"><?= owBi('The institutes tables are not set up yet.', 'جداول المعاهد غير مُعدّة بعد.') ?></div><?php endif; ?>
  <?php if (isset($_GET['ok'])): ?><div class="alert alert-ok"><?= owBi('Saved.', 'تم الحفظ.') ?></div><?php endif; ?>
  <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= owBi($er[0], $er[1]) ?></div><?php endforeach; ?>

  <?php if ($ready): ?>
  <div class="card"><details<?= !$insts || (($_POST['do'] ?? '') === 'institute' && (int) ($_POST['id'] ?? 0) === 0 && $errors) ? ' open' : '' ?>><summary><?= owBi('+ New institute', '+ معهد جديد') ?></summary>
    <?php $instForm((($_POST['do'] ?? '') === 'institute' && (int) ($_POST['id'] ?? 0) === 0) ? $_POST : []); ?></details></div>

  <?php if (!$insts): ?><div class="card"><p><?= owBi('No institutes yet. Add the first one above.', 'لا معاهد بعد. أضف أول معهد بالأعلى.') ?></p></div><?php endif; ?>
  <?php foreach ($insts as $i): $iid = (int) $i['id']; $cs = $courses[$iid] ?? []; ?>
  <div class="card" id="i<?= $iid ?>">
    <div class="i-head"><h2><?= e($i['name_en']) ?> · <span dir="rtl"><?= e($i['name_ar']) ?></span></h2>
      <span><span class="pillx"><?= (int) $i['is_active'] === 1 ? owBi('Shown', 'ظاهر') : owBi('Hidden', 'مخفي') ?></span> <span class="mini"><?= e($i['city_en']) ?> · <?= count($cs) ?> <?= owBi('courses', 'دورات') ?></span></span></div>
    <details<?= $edit === $iid ? ' open' : '' ?>><summary><?= owBi('Edit institute', 'عدّل المعهد') ?></summary><?php $instForm($i); ?></details>
    <details<?= $edit === $iid ? ' open' : '' ?>><summary><?= owBi('Courses', 'الدورات') ?> (<?= count($cs) ?>)</summary>
      <?php foreach ($cs as $c): ?><div class="c-row"><p class="mini"><b><?= e($c['title_en']) ?></b> · <?= e((string) $c['start_date']) ?> · <?= (int) $c['is_active'] === 1 ? owBi('Shown', 'ظاهرة') : owBi('Hidden', 'مخفية') ?></p><?php $courseForm($iid, $c); ?></div><?php endforeach; ?>
      <div class="c-row"><p class="mini"><b><?= owBi('New course', 'دورة جديدة') ?></b></p><?php $courseForm($iid, []); ?></div>
    </details>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>
<script>
function tgl(){ var ar = document.documentElement.getAttribute('dir') !== 'rtl'; document.documentElement.setAttribute('dir', ar ? 'rtl' : 'ltr'); document.documentElement.lang = ar ? 'ar' : 'en';
  document.getElementById('lb').textContent = ar ? 'English' : 'العربية'; try { localStorage.setItem('lang', ar ? 'ar' : 'en'); } catch (e) {} }
try { if (localStorage.getItem('lang') === 'ar') tgl(); } catch (e) {}
</script>
</body>
</html>
