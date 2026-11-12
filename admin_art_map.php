<?php
/* =====================================================================
 *  admin_art_map.php — the admin's tool for the Art Map's museums and art
 *  galleries (owner, 12 Nov 2026). Admin only. Every place starts as a draft
 *  and is shown on art_map.php only after the admin presses Publish. Edit,
 *  add, publish or take back to draft; nothing is deleted. Each place keeps
 *  the source the team checked it on. Institutes keep their own tool
 *  (admin_institutes.php).
 *  Tables: site_settings (art_places, artmap_lib.php), activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/admin_shell.php';
require_once __DIR__ . '/artmap_lib.php';
owAdminGate();
$actor = owAdminActor();
$errors = [];
$txt = fn(string $k, int $max) => mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) ($_POST[$k] ?? ''))), 0, $max);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $places = owArtPlaces();
    $id = preg_replace('/[^a-z0-9]/', '', (string) ($_POST['id'] ?? ''));
    $at = null;
    foreach ($places as $k => $p) if ($p['id'] === $id) $at = $k;
    $do = (string) ($_POST['do'] ?? '');
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = ['The page was open too long. Please try again.', 'انتهت صلاحية الصفحة. حاول مرة أخرى.'];
    } elseif (($do === 'publish' || $do === 'draft') && $at !== null) {
        $places[$at]['status'] = $do === 'publish' ? 'published' : 'draft';
        owArtPlacesSave($places);
        owLog($do === 'publish' ? 'art_place_publish' : 'art_place_draft', 'art_place', null, $places[$at]['name_en'], $actor);
        header('Location: admin_art_map.php?ok=' . $do . '#' . $id); exit;
    } elseif ($do === 'save') {
        $v = ['kind' => (string) ($_POST['kind'] ?? ''), 'city' => (string) ($_POST['city'] ?? ''),
              'name_en' => $txt('name_en', 200), 'name_ar' => $txt('name_ar', 200), 'city_en' => $txt('city_en', 80), 'city_ar' => $txt('city_ar', 80),
              'desc_en' => $txt('desc_en', 600), 'desc_ar' => $txt('desc_ar', 600),
              'website' => owMapUrl($txt('website', 500)), 'maps' => owMapUrl($txt('maps', 500)), 'source' => owMapUrl($txt('source', 500))];
        if (!isset(OW_MAP_KINDS[$v['kind']])) $errors[] = ['Please choose Museum or Art gallery.', 'يرجى اختيار متحف أو معرض فني.'];
        if (!isset(OW_MAP_CITIES[$v['city']])) $errors[] = ['Please choose the city.', 'يرجى اختيار المدينة.'];
        if ($v['name_en'] === '' || $v['name_ar'] === '') $errors[] = ['The name is needed in both languages.', 'الاسم مطلوب باللغتين.'];
        if ($v['desc_en'] === '' || $v['desc_ar'] === '') $errors[] = ['The short text is needed in both languages.', 'النص القصير مطلوب باللغتين.'];
        if ($v['website'] === '') $errors[] = ['Please add the official website (https://…).', 'يرجى إضافة الموقع الرسمي (https://…).'];
        if ($txt('maps', 500) !== '' && $v['maps'] === '') $errors[] = ['Please check the Google Maps link.', 'يرجى مراجعة رابط خرائط Google.'];
        if (!$errors) {
            if ($at !== null) { $places[$at] = array_merge($places[$at], $v); $name = $v['name_en']; }
            else { $id = 'p' . bin2hex(random_bytes(5)); $places[] = ['id' => $id, 'status' => 'draft', 'added' => date('Y-m-d')] + $v; $name = $v['name_en']; }
            owArtPlacesSave($places);
            owLog('art_place_save', 'art_place', null, $name, $actor);
            header('Location: admin_art_map.php?ok=save#' . $id); exit;
        }
    }
}

$all = owArtPlaces();
$drafts = owArtPlacesDrafts();
$sel = function (array $opts, string $cur, bool $kinds): string {
    $h = '';
    foreach ($opts as $k => $o) $h .= '<option value="' . e($k) . '"' . ($k === $cur ? ' selected' : '') . '>' . e($kinds ? $o[2] . ' · ' . $o[3] : $o[0] . ' · ' . $o[1]) . '</option>';
    return $h;
};
$field = function (string $name, string $en, string $ar, string $value, string $extra = '', bool $area = false, string $pid = 'new'): string {
    $id = 'f-' . $name . '-' . $pid;
    $in = $area ? '<textarea id="' . $id . '" name="' . $name . '" rows="3"' . $extra . '>' . e($value) . '</textarea>'
                : '<input id="' . $id . '" type="text" name="' . $name . '" value="' . e($value) . '"' . $extra . '>';
    return '<div class="field"><label for="' . $id . '">' . owBi($en, $ar) . '</label>' . $in . '</div>';
};
$form = function (array $p) use ($sel, $field): void {
    $pid = (string) ($p['id'] ?? 'new');
    $val = fn(string $k) => (string) ((($_POST['id'] ?? '') === ($p['id'] ?? '') && isset($_POST[$k])) ? $_POST[$k] : ($p[$k] ?? '')); ?>
  <form method="post" action="admin_art_map.php">
    <?= csrfField() ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" value="<?= e((string) ($p['id'] ?? '')) ?>">
    <div class="g2">
      <div class="field"><label for="k-<?= e($pid) ?>"><?= owBi('Type', 'النوع') ?></label><select id="k-<?= e($pid) ?>" name="kind"><?= $sel(OW_MAP_KINDS, $val('kind') ?: 'museum', true) ?></select></div>
      <div class="field"><label for="c-<?= e($pid) ?>"><?= owBi('City in the filter', 'المدينة في التصفية') ?></label><select id="c-<?= e($pid) ?>" name="city"><?= $sel(OW_MAP_CITIES, $val('city') ?: 'riyadh', false) ?></select></div>
    </div>
    <div class="g2"><?= $field('name_en', 'Name (English)', 'الاسم (بالإنجليزية)', $val('name_en'), ' required maxlength="200"', false, $pid) ?><?= $field('name_ar', 'Name (Arabic)', 'الاسم (بالعربية)', $val('name_ar'), ' required maxlength="200" dir="rtl"', false, $pid) ?></div>
    <div class="g2"><?= $field('city_en', 'City shown (English, optional, e.g. Dhahran)', 'المدينة المعروضة (بالإنجليزية، اختياري، مثل الظهران)', $val('city_en'), ' maxlength="80"', false, $pid) ?><?= $field('city_ar', 'City shown (Arabic, optional)', 'المدينة المعروضة (بالعربية، اختياري)', $val('city_ar'), ' maxlength="80" dir="rtl"', false, $pid) ?></div>
    <div class="g2"><?= $field('desc_en', 'Short text (English)', 'نص قصير (بالإنجليزية)', $val('desc_en'), ' required maxlength="600"', true, $pid) ?><?= $field('desc_ar', 'Short text (Arabic)', 'نص قصير (بالعربية)', $val('desc_ar'), ' required maxlength="600" dir="rtl"', true, $pid) ?></div>
    <div class="g3"><?= $field('website', 'Official website', 'الموقع الرسمي', $val('website'), ' required dir="ltr" maxlength="500"', false, $pid) ?>
      <?= $field('maps', 'Google Maps link (optional)', 'رابط خرائط Google (اختياري)', $val('maps'), ' dir="ltr" maxlength="500"', false, $pid) ?>
      <?= $field('source', 'Source checked (only you see it)', 'المصدر الذي تم التحقق منه (تراه أنت فقط)', $val('source'), ' dir="ltr" maxlength="500"', false, $pid) ?></div>
    <p class="mini"><?= owBi('Without a Google Maps link, the page searches Google Maps for the name and city.', 'بدون رابط خرائط Google، تبحث الصفحة في خرائط Google عن الاسم والمدينة.') ?></p>
    <button class="btn btn-primary btn-sm" type="submit"><?= owBi('Save', 'احفظ') ?></button>
  </form>
<?php };

owAdminTop('Art Map', 'خريطة الفن', '.pl-head{display:flex;justify-content:space-between;gap:var(--space-sm);align-items:flex-start;flex-wrap:wrap;}.pl-head h3{font-size:18px;margin:0;}.pl-links{display:flex;flex-wrap:wrap;gap:4px 14px;font-size:var(--fs-caption);margin:6px 0;}.pl-links a{color:var(--color-accent-dark);}',
    '<a class="btn btn-ghost btn-sm" href="art_map.php" target="_blank" rel="noopener">' . owBi('See the Art Map', 'شاهد خريطة الفن') . ' ↗</a>');
?>
  <h1><?= owBi('Art Map: museums and art galleries', 'خريطة الفن: المتاحف والمعارض الفنية') ?></h1>
  <p class="mini"><?= owBi('Every place starts as a draft. Visitors see it only after you press Publish. Institutes are in their own tool:', 'كل مكان يبدأ مسودة، ولا يراه الزوار إلا بعد أن تضغط «انشر». المعاهد في أداتها الخاصة:') ?> <a href="admin_institutes.php"><?= owBi('Institutes and courses', 'المعاهد والدورات') ?></a></p>
  <?php if (isset($_GET['ok'])): ?><div class="alert alert-ok"><?= ['publish' => owBi('Published. It is on the Art Map now.', 'تم النشر. صار ظاهراً في خريطة الفن.'), 'draft' => owBi('Back to draft. Visitors no longer see it.', 'أُعيد مسودة. لم يعد ظاهراً للزوار.')][$_GET['ok']] ?? owBi('Saved.', 'تم الحفظ.') ?></div><?php endif; ?>
  <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= owBi($er[0], $er[1]) ?></div><?php endforeach; ?>
  <p><span class="pillx warn"><?= owBi($drafts . ' waiting for you', $drafts . ' بانتظارك') ?></span> <span class="mini"><?= owBi(count($all) . ' places in all', 'المجموع ' . count($all)) ?></span></p>

  <div class="card"><details<?= ($errors && empty($_POST['id'])) || !$all ? ' open' : '' ?>><summary><?= owBi('+ New place', '+ مكان جديد') ?></summary><?php $form(empty($_POST['id']) && $errors ? $_POST : []); ?></details></div>

  <?php foreach (OW_MAP_KINDS as $kk => $K): $list = array_values(array_filter($all, fn($p) => $p['kind'] === $kk)); ?>
  <h2><?= owBi($K[0], $K[1]) ?> <span class="mini">(<?= count($list) ?>)</span></h2>
  <?php foreach ($list as $p): $pub = ($p['status'] ?? 'draft') === 'published'; $c = OW_MAP_CITIES[$p['city']] ?? OW_MAP_CITIES['other']; ?>
  <div class="card" id="<?= e($p['id']) ?>">
    <div class="pl-head">
      <div><h3><?= e($p['name_en']) ?> · <span dir="rtl"><?= e($p['name_ar']) ?></span></h3>
        <div class="mini"><?= e(($p['city_en'] ?: $c[0]) . ' · ') ?><span dir="rtl"><?= e($p['city_ar'] ?: $c[1]) ?></span> · <?= owBi('filter: ' . $c[0], 'التصفية: ' . $c[1]) ?></div></div>
      <div class="acts"><span class="pillx<?= $pub ? ' on' : ' warn' ?>"><?= $pub ? owBi('Published', 'منشور') : owBi('Draft', 'مسودة') ?></span>
        <form method="post" action="admin_art_map.php"><?= csrfField() ?><input type="hidden" name="id" value="<?= e($p['id']) ?>">
          <?php if ($pub): ?><button class="btn btn-secondary btn-sm" name="do" value="draft"><?= owBi('Back to draft', 'أعده مسودة') ?></button>
          <?php else: ?><button class="btn btn-accent btn-sm" name="do" value="publish"><?= owBi('Publish', 'انشر') ?></button><?php endif; ?></form></div>
    </div>
    <p class="mini" style="margin:8px 0 0"><?= e($p['desc_en']) ?></p><p class="mini" dir="rtl" style="margin:4px 0 0"><?= e($p['desc_ar']) ?></p>
    <div class="pl-links">
      <a href="<?= e($p['website']) ?>" target="_blank" rel="noopener"><?= owBi('Official website', 'الموقع الرسمي') ?> ↗</a>
      <a href="<?= e(owMapsLink($p['name_en'], $p['city_en'] ?: $c[0], (string) ($p['maps'] ?? ''))) ?>" target="_blank" rel="noopener"><?= owBi('Google Maps', 'خرائط Google') ?> ↗</a>
      <?php if (!empty($p['source'])): ?><a href="<?= e($p['source']) ?>" target="_blank" rel="noopener"><?= owBi('Source checked', 'المصدر') ?> ↗</a><?php endif; ?>
    </div>
    <details<?= ($errors && ($_POST['id'] ?? '') === $p['id']) ? ' open' : '' ?>><summary><?= owBi('Edit', 'تعديل') ?></summary><?php $form($p); ?></details>
  </div>
  <?php endforeach; if (!$list): ?><div class="card"><p class="mini"><?= owBi('None yet.', 'لا شيء بعد.') ?></p></div><?php endif; ?>
  <?php endforeach; ?>
<?php owAdminBottom();
