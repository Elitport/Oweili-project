<?php
/* =====================================================================
 *  admin_art_map.php — the admin's tool for the Art Map (owner, 12 Nov 2026).
 *  Admin only. Museums, art galleries and art institutes from the art_places
 *  table. Every place starts as a draft and is shown on art_map.php only
 *  after the admin presses Publish. Filters (type, status, region, confidence,
 *  search), Publish / Back to draft, Edit and + New place; nothing is deleted.
 *  The sources and the confidence level are shown here only, never to
 *  visitors; "Low" confidence is marked in red. The older institutes
 *  directory keeps its own tool (admin_institutes.php).
 *  Tables: art_places, activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/admin_shell.php';
require_once __DIR__ . '/artmap_lib.php';
owAdminGate();
$actor = owAdminActor();
$ready = owArtPlacesReady();
$db = getDB();
$errors = []; $photoErr = 0;
$txt = fn(string $k, int $max) => mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) ($_POST[$k] ?? ''))), 0, $max);
$keep = function (): string {   // the list filters, kept after an action
    $f = array_intersect_key($_GET, array_flip(['k', 'st', 'rg', 'cf', 's']));
    return $f ? '&' . http_build_query($f) : '';
};

/* "Is this your place?" photo, for the admin only (it waits in the private folder until approved) */
if (isset($_GET['claimphoto']) && owClaimsReady()) {
    $st = $db->prepare('SELECT photo_file FROM art_place_claims WHERE id = :id'); $st->execute([':id' => (int) $_GET['claimphoto']]);
    $f = owClaimPhotoPath((string) $st->fetchColumn());
    if ($f === '' || !is_file($f)) { http_response_code(404); exit; }
    header('Content-Type: image/jpeg'); header('Cache-Control: private, no-store'); header('X-Robots-Tag: noindex');
    readfile($f); exit;
}
/* approve or reject one "Is this your place?" message (owner, 13 Nov 2026) */
if (owClaimsReady() && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && in_array($_POST['do'] ?? '', ['claim_ok', 'claim_no'], true)) {
    $cid = (int) ($_POST['claim'] ?? 0);
    $st = $db->prepare("SELECT * FROM art_place_claims WHERE id = :id AND status = 'new'"); $st->execute([':id' => $cid]); $cl = $st->fetch();
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) { header('Location: admin_art_map.php?ok=expired#claims'); exit; }
    if ($cl) {
        $ok = $_POST['do'] === 'claim_ok';
        $usePhoto = $ok && $cl['place_kind'] === 'place' && !empty($_POST['use_photo']);
        $f = owClaimPhotoPath((string) $cl['photo_file']);
        if ($usePhoto && $f !== '' && is_file($f) && ($img = @imagecreatefromjpeg($f))) {
            $r = owPlaceWriteImage($img, (int) $cl['place_id']);
            if ($r['ok']) {
                $old = $db->prepare('SELECT photo_path FROM art_places WHERE id = :id'); $old->execute([':id' => (int) $cl['place_id']]); $oldPath = (string) $old->fetchColumn();
                $db->prepare("UPDATE art_places SET photo_path = :b, photo_credit = :c, photo_page = '', photo_license = '' WHERE id = :id")
                   ->execute([':b' => $r['base'], ':c' => (string) ($cl['photo_credit'] !== '' ? $cl['photo_credit'] : $cl['place_name']), ':id' => (int) $cl['place_id']]);
                owPlaceDropUpload($oldPath);
            }
        }
        $db->prepare('UPDATE art_place_claims SET status = :s, decided_at = NOW() WHERE id = :id')->execute([':s' => $ok ? 'approved' : 'rejected', ':id' => $cid]);
        if ($f !== '' && is_file($f)) @unlink($f);   // the waiting photo is not kept after the decision
        owLog($ok ? 'place_claim_approve' : 'place_claim_reject', 'art_place_claim', $cid, (string) $cl['place_name'], $actor);
        header('Location: admin_art_map.php?ok=' . ($ok ? ($usePhoto ? 'claim_photo' : 'claim_ok') : 'claim_no') . '#claims'); exit;
    }
    header('Location: admin_art_map.php#claims'); exit;
}

if ($ready && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $do = (string) ($_POST['do'] ?? '');
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = ['The page was open too long. Please try again.', 'انتهت صلاحية الصفحة. حاول مرة أخرى.'];
    } elseif (($do === 'publish' || $do === 'draft') && $id > 0) {
        $db->prepare('UPDATE art_places SET status = :s WHERE id = :id')->execute([':s' => $do === 'publish' ? 'published' : 'draft', ':id' => $id]);
        $nm = $db->prepare('SELECT name_en FROM art_places WHERE id = :id'); $nm->execute([':id' => $id]);
        owLog($do === 'publish' ? 'art_place_publish' : 'art_place_draft', 'art_place', $id, (string) $nm->fetchColumn(), $actor);
        header('Location: admin_art_map.php?ok=' . $do . $keep() . '#p' . $id); exit;
    } elseif ($do === 'photo' && $id > 0) {
        /* the admin's own photo for a place, e.g. one a museum sent (owner, 13 Nov 2026); replaces the old photo */
        $cur = $db->prepare('SELECT name_en, photo_path FROM art_places WHERE id = :id'); $cur->execute([':id' => $id]); $cur = $cur->fetch();
        if (!$cur) { header('Location: admin_art_map.php'); exit; }
        $link = $txt('photo_page', 500);
        if ($link !== '' && owMapUrl($link) === '') $errors[] = ['Please check the credit link (it must start with https://).', 'يرجى مراجعة رابط المصدر (يبدأ بـ https://).'];
        if (!$errors) {
            $r = owPlaceStorePhoto($_FILES['photo'] ?? [], $id);
            if (!$r['ok']) $errors[] = $r['err'];
            else {
                $db->prepare("UPDATE art_places SET photo_path = :b, photo_credit = :c, photo_page = :l, photo_license = '' WHERE id = :id")
                   ->execute([':b' => $r['base'], ':c' => $txt('photo_credit', 200), ':l' => owMapUrl($link), ':id' => $id]);
                owPlaceDropUpload((string) $cur['photo_path']);   // an older uploaded photo of this place (our own search photos stay in the code)
                owLog('art_place_photo', 'art_place', $id, (string) $cur['name_en'], $actor);
                header('Location: admin_art_map.php?ok=photo' . $keep() . '#p' . $id); exit;
            }
        }
        $photoErr = $id;
    } elseif ($do === 'save') {
        $v = ['kind' => (string) ($_POST['kind'] ?? ''), 'region' => (string) ($_POST['region'] ?? ''), 'confidence' => (string) ($_POST['confidence'] ?? 'medium'),
              'name_en' => $txt('name_en', 200), 'name_ar' => $txt('name_ar', 200), 'city_en' => $txt('city_en', 120), 'city_ar' => $txt('city_ar', 120),
              'desc_en' => $txt('desc_en', 800), 'desc_ar' => $txt('desc_ar', 800), 'address' => $txt('address', 300), 'phone' => $txt('phone', 40),
              'website' => owMapUrl($txt('website', 500)), 'instagram' => owInstaUrl($txt('instagram', 200)), 'maps_url' => owMapUrl($txt('maps_url', 500))];
        if (owHasColumn('art_places', 'address_ar')) $v['address_ar'] = $txt('address_ar', 300);   // the address for Arabic pages (owner, 13 Nov 2026)
        $src = array_values(array_filter(array_map(fn($l) => owMapUrl(trim($l)), preg_split('/\R/', $txt('sources', 3000)))));
        $v['sources'] = json_encode($src, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!isset(OW_MAP_KINDS[$v['kind']])) $errors[] = ['Please choose the type.', 'يرجى اختيار النوع.'];
        if (!isset(OW_MAP_REGIONS[$v['region']])) $errors[] = ['Please choose the region.', 'يرجى اختيار المنطقة.'];
        if (!isset(OW_MAP_CONF[$v['confidence']])) $v['confidence'] = 'medium';
        if ($v['name_en'] === '' || $v['name_ar'] === '') $errors[] = ['The name is needed in both languages.', 'الاسم مطلوب باللغتين.'];
        if ($v['city_en'] === '' || $v['city_ar'] === '') $errors[] = ['The city is needed in both languages.', 'المدينة مطلوبة باللغتين.'];
        if ($v['desc_en'] === '' || $v['desc_ar'] === '') $errors[] = ['The short text is needed in both languages.', 'النص القصير مطلوب باللغتين.'];
        foreach (['website' => ['the website', 'الموقع'], 'instagram' => ['the Instagram link', 'رابط إنستغرام'], 'maps_url' => ['the Google Maps link', 'رابط خرائط Google']] as $k => $w)
            if ($txt($k, 500) !== '' && $v[$k] === '') $errors[] = ['Please check ' . $w[0] . '.', 'يرجى مراجعة ' . $w[1] . '.'];
        if ($v['phone'] !== '' && !preg_match('/^\+?[0-9][0-9 \-()]{6,39}$/', $v['phone'])) $errors[] = ['Please check the phone number.', 'يرجى مراجعة رقم الهاتف.'];
        if (!$errors) {
            $args = []; foreach ($v as $k => $x) $args[':' . $k] = $x;
            if ($id > 0) {
                $db->prepare('UPDATE art_places SET ' . implode(', ', array_map(fn($c) => "$c = :$c", array_keys($v))) . ' WHERE id = :id')->execute($args + [':id' => $id]);
            } else {
                $db->prepare('INSERT INTO art_places (' . implode(', ', array_keys($v)) . ", status) VALUES (" . implode(', ', array_keys($args)) . ", 'draft')")->execute($args);
                $id = (int) $db->lastInsertId();
            }
            owLog('art_place_save', 'art_place', $id, $v['name_en'], $actor);
            header('Location: admin_art_map.php?ok=save' . $keep() . '#p' . $id); exit;
        }
    }
}

/* the list, with its filters */
$fk = (string) ($_GET['k'] ?? ''); $fst = (string) ($_GET['st'] ?? ''); $frg = (string) ($_GET['rg'] ?? ''); $fcf = (string) ($_GET['cf'] ?? ''); $fs = mb_substr(trim((string) ($_GET['s'] ?? '')), 0, 80);
$where = []; $args = [];
if (isset(OW_MAP_KINDS[$fk])) { $where[] = 'kind = :k'; $args[':k'] = $fk; }
if (in_array($fst, ['draft', 'published'], true)) { $where[] = 'status = :st'; $args[':st'] = $fst; }
if (isset(OW_MAP_REGIONS[$frg])) { $where[] = 'region = :rg'; $args[':rg'] = $frg; }
if (isset(OW_MAP_CONF[$fcf])) { $where[] = 'confidence = :cf'; $args[':cf'] = $fcf; }
if ($fs !== '') { $where[] = '(name_en LIKE :s1 OR name_ar LIKE :s2 OR city_en LIKE :s3)'; $args[':s1'] = $args[':s2'] = $args[':s3'] = '%' . $fs . '%'; }   // one name per place in the query (the same name three times stopped the page)
$rows = []; $count = ['all' => 0, 'draft' => 0, 'low' => 0];
if ($ready) {
    $st = $db->prepare('SELECT * FROM art_places' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY FIELD(kind,'museum','gallery','institute'), status = 'published', FIELD(region,'" . implode("','", array_keys(OW_MAP_REGIONS)) . "'), name_en");
    $st->execute($args); $rows = $st->fetchAll();
    $c = $db->query("SELECT COUNT(*) a, SUM(status <> 'published') d, SUM(confidence = 'low') l FROM art_places")->fetch();
    $count = ['all' => (int) $c['a'], 'draft' => (int) $c['d'], 'low' => (int) $c['l']];
}

$opt = function (array $opts, string $cur, int $i = 0, string $all = ''): string {
    $h = $all !== '' ? '<option value="">' . e($all) . '</option>' : '';
    foreach ($opts as $k => $o) $h .= '<option value="' . e($k) . '"' . ($k === $cur ? ' selected' : '') . '>' . e($o[$i] . ' · ' . $o[$i + 1]) . '</option>';
    return $h;
};
$field = function (string $name, string $en, string $ar, string $value, string $extra = '', bool $area = false, string $pid = 'new'): string {
    $id = 'f-' . $name . '-' . $pid;
    $in = $area ? '<textarea id="' . $id . '" name="' . $name . '" rows="3"' . $extra . '>' . e($value) . '</textarea>'
                : '<input id="' . $id . '" type="text" name="' . $name . '" value="' . e($value) . '"' . $extra . '>';
    return '<div class="field"><label for="' . $id . '">' . owBi($en, $ar) . '</label>' . $in . '</div>';
};
$form = function (array $p) use ($opt, $field, $keep): void {
    $pid = (string) ($p['id'] ?? 'new');
    $mine = (int) ($_POST['id'] ?? -1) === (int) ($p['id'] ?? 0) && ($_POST['do'] ?? '') === 'save';
    $val = fn(string $k) => (string) ($mine && isset($_POST[$k]) ? $_POST[$k] : ($p[$k] ?? ''));
    $srcVal = $mine ? (string) ($_POST['sources'] ?? '') : implode("\n", (array) (json_decode((string) ($p['sources'] ?? '[]'), true) ?: [])); ?>
  <form method="post" action="admin_art_map.php?<?= e(ltrim($keep(), '&')) ?>">
    <?= csrfField() ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" value="<?= (int) ($p['id'] ?? 0) ?>">
    <div class="g3">
      <div class="field"><label for="k-<?= e($pid) ?>"><?= owBi('Type', 'النوع') ?></label><select id="k-<?= e($pid) ?>" name="kind"><?= $opt(OW_MAP_KINDS, $val('kind') ?: 'museum', 2) ?></select></div>
      <div class="field"><label for="r-<?= e($pid) ?>"><?= owBi('Region', 'المنطقة') ?></label><select id="r-<?= e($pid) ?>" name="region"><?= $opt(OW_MAP_REGIONS, $val('region') ?: 'riyadh') ?></select></div>
      <div class="field"><label for="c-<?= e($pid) ?>"><?= owBi('Confidence (only you see it)', 'درجة الثقة (تراها أنت فقط)') ?></label><select id="c-<?= e($pid) ?>" name="confidence"><?= $opt(OW_MAP_CONF, $val('confidence') ?: 'medium') ?></select></div>
    </div>
    <div class="g2"><?= $field('name_en', 'Name (English)', 'الاسم (بالإنجليزية)', $val('name_en'), ' required maxlength="200"', false, $pid) ?><?= $field('name_ar', 'Name (Arabic)', 'الاسم (بالعربية)', $val('name_ar'), ' required maxlength="200" dir="rtl"', false, $pid) ?></div>
    <div class="g2"><?= $field('city_en', 'City (English)', 'المدينة (بالإنجليزية)', $val('city_en'), ' required maxlength="120"', false, $pid) ?><?= $field('city_ar', 'City (Arabic)', 'المدينة (بالعربية)', $val('city_ar'), ' required maxlength="120" dir="rtl"', false, $pid) ?></div>
    <div class="g2"><?= $field('desc_en', 'Short text (English)', 'نص قصير (بالإنجليزية)', $val('desc_en'), ' required maxlength="800"', true, $pid) ?><?= $field('desc_ar', 'Short text (Arabic)', 'نص قصير (بالعربية)', $val('desc_ar'), ' required maxlength="800" dir="rtl"', true, $pid) ?></div>
    <div class="g2"><?= $field('address', 'Address (optional)', 'العنوان (اختياري)', $val('address'), ' maxlength="300"', false, $pid) ?><?= $field('phone', 'Phone (optional)', 'الهاتف (اختياري)', $val('phone'), ' dir="ltr" maxlength="40"', false, $pid) ?></div>
    <?php if (owHasColumn('art_places', 'address_ar')): ?><?= $field('address_ar', 'Address in Arabic, for Arabic pages (optional)', 'العنوان بالعربية للصفحات العربية (اختياري)', $val('address_ar'), ' maxlength="300" dir="rtl"', false, $pid) ?><?php endif; ?>
    <div class="g3"><?= $field('website', 'Official website (optional)', 'الموقع الرسمي (اختياري)', $val('website'), ' dir="ltr" maxlength="500"', false, $pid) ?>
      <?= $field('instagram', 'Instagram (optional)', 'إنستغرام (اختياري)', $val('instagram'), ' dir="ltr" maxlength="200"', false, $pid) ?>
      <?= $field('maps_url', 'Google Maps link (optional)', 'رابط خرائط Google (اختياري)', $val('maps_url'), ' dir="ltr" maxlength="500"', false, $pid) ?></div>
    <?= $field('sources', 'Sources checked, one link per line (only you see them)', 'المصادر، رابط في كل سطر (تراها أنت فقط)', $srcVal, ' dir="ltr" maxlength="3000"', true, $pid) ?>
    <p class="mini"><?= owBi('Empty fields are not shown to visitors. Without a Google Maps link, the page searches Google Maps for the name and city.', 'الحقول الفارغة لا تظهر للزوار. بدون رابط خرائط Google، تبحث الصفحة في خرائط Google عن الاسم والمدينة.') ?></p>
    <button class="btn btn-primary btn-sm" type="submit"><?= owBi('Save', 'احفظ') ?></button>
  </form>
<?php };

owAdminTop('Art Map', 'خريطة الفن', '.pl{display:grid;grid-template-columns:120px 1fr;gap:var(--space-sm);}.pl img,.pl .noimg{width:120px;height:75px;object-fit:cover;border-radius:8px;background:var(--color-surface-2);display:block;}.pl .noimg{display:flex;align-items:center;justify-content:center;font-size:11px;color:var(--color-body);text-align:center;}
.pl-head{display:flex;justify-content:space-between;gap:var(--space-sm);align-items:flex-start;flex-wrap:wrap;}.pl-head h3{font-size:17px;margin:0;}.pl-links{display:flex;flex-wrap:wrap;gap:4px 14px;font-size:var(--fs-caption);margin:6px 0;}.pl-links a{color:var(--color-accent-dark);}
.cf{display:inline-block;font-size:12px;font-weight:700;padding:2px 10px;border-radius:999px;border:1px solid var(--color-border-strong);}.cf.low{background:#b42318;border-color:#b42318;color:#fff;}.cf.high{background:rgba(var(--rgb-accent),.1);}
.card.is-low{border:2px solid #b42318;}.flt{display:flex;flex-wrap:wrap;gap:8px;align-items:end;}.flt .field{margin:0;min-width:150px;flex:1 1 150px;}.src{font-size:12px;margin:4px 0 0;padding-inline-start:18px;}.src a{color:var(--color-accent-dark);overflow-wrap:anywhere;}
@media(max-width:600px){.pl{grid-template-columns:1fr;}.pl img,.pl .noimg{width:100%;height:auto;aspect-ratio:16/10;}}',
    '<a class="btn btn-ghost btn-sm" href="art_map.php" target="_blank" rel="noopener">' . owBi('See the Art Map', 'شاهد خريطة الفن') . ' ↗</a>');
?>
  <h1><?= owBi('Art Map: museums, galleries and institutes', 'خريطة الفن: المتاحف والمعارض والمعاهد') ?></h1>
  <p class="mini"><?= owBi('Every place starts as a draft. Visitors see it only after you press Publish. Sources and confidence are shown only here, never to visitors. The older institutes (with courses) are in their own tool:', 'كل مكان يبدأ مسودة، ولا يراه الزوار إلا بعد أن تضغط «انشر». المصادر ودرجة الثقة تظهر هنا فقط، ولا يراها الزوار أبداً. المعاهد القديمة (مع الدورات) في أداتها الخاصة:') ?> <a href="admin_institutes.php"><?= owBi('Institutes and courses', 'المعاهد والدورات') ?></a></p>
  <?php if (!$ready): ?><div class="alert alert-error"><?= owBi('The Art Map table is not set up yet (art_places.sql).', 'جدول خريطة الفن غير مُعدّ بعد (art_places.sql).') ?></div><?php endif; ?>
  <?php if (isset($_GET['ok'])): ?><div class="alert alert-ok"><?= ['claim_photo' => owBi('Approved. The photo is on the Art Map now.', 'تمت الموافقة. الصورة ظاهرة في وجهات الفن الآن.'), 'claim_ok' => owBi('Approved. Apply any corrections with Edit below.', 'تمت الموافقة. طبّق التصحيحات عبر «تعديل» أدناه.'), 'claim_no' => owBi('Rejected. Nothing was shown.', 'تم الرفض. لم يظهر شيء.'), 'expired' => owBi('The page was open too long. Please try again.', 'انتهت صلاحية الصفحة. حاول مرة أخرى.'), 'photo' => owBi('Photo saved.', 'تم حفظ الصورة.'), 'publish' => owBi('Published. It is on the Art Map now.', 'تم النشر. صار ظاهراً في خريطة الفن.'), 'draft' => owBi('Back to draft. Visitors no longer see it.', 'أُعيد مسودة. لم يعد ظاهراً للزوار.')][$_GET['ok']] ?? owBi('Saved.', 'تم الحفظ.') ?></div><?php endif; ?>
  <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= owBi($er[0], $er[1]) ?></div><?php endforeach; ?>
  <?php if ($ready): ?>
  <p><span class="pillx warn"><?= owBi($count['draft'] . ' waiting for you', $count['draft'] . ' بانتظارك') ?></span> <span class="cf low"><?= owBi($count['low'] . ' with low confidence', $count['low'] . ' بثقة منخفضة') ?></span> <span class="mini"><?= owBi($count['all'] . ' places in all', 'المجموع ' . $count['all']) ?></span></p>

  <?php $claims = [];
  if (owClaimsReady()) { try { $claims = $db->query("SELECT * FROM art_place_claims WHERE status = 'new' ORDER BY id")->fetchAll(); } catch (Throwable $e) { $claims = []; } } ?>
  <div id="claims">
  <?php if ($claims): ?>
  <h2 style="font-size:20px;margin:var(--space-md) 0 var(--space-xs)"><?= owBi('"Is this your place?" messages', 'رسائل «هل هذا مكانك؟»') ?> <span class="pillx warn"><?= count($claims) ?></span></h2>
  <p class="mini"><?= owBi('Sent by places from the Art Map. Nothing shows to visitors until you approve. Approve can put their photo on the place; apply text corrections yourself with Edit.', 'أرسلتها الأماكن من وجهات الفن. لا يظهر شيء للزوار حتى توافق. يمكن للموافقة وضع صورتهم على المكان؛ أما التصحيحات النصية فطبّقها بنفسك عبر «تعديل».') ?></p>
  <?php foreach ($claims as $cl): $hasPh = owClaimPhotoPath((string) $cl['photo_file']) !== '' && is_file(owClaimPhotoPath((string) $cl['photo_file'])); ?>
  <div class="card">
    <div class="pl">
      <?php if ($hasPh): ?><a href="admin_art_map.php?claimphoto=<?= (int) $cl['id'] ?>" target="_blank" rel="noopener"><img src="admin_art_map.php?claimphoto=<?= (int) $cl['id'] ?>" alt="<?= e('Photo sent for ' . $cl['place_name']) ?>"></a><?php else: ?><span class="noimg"><?= owBi('No photo sent', 'لم تُرسل صورة') ?></span><?php endif; ?>
      <div>
        <h3 style="font-size:17px;margin:0"><?= e($cl['place_name']) ?> <span class="mini">· <?= $cl['place_kind'] === 'place' ? '<a href="#p' . (int) $cl['place_id'] . '">' . owBi('see the place', 'شاهد المكان') . '</a>' : owBi('older institutes list (Institutes and courses tool)', 'قائمة المعاهد القديمة (أداة المعاهد والدورات)') ?></span></h3>
        <div class="pl-links"><span><?= e($cl['name']) ?></span><a href="mailto:<?= e($cl['email']) ?>"><?= e($cl['email']) ?></a><?php if ($cl['phone'] !== ''): ?><a dir="ltr" href="<?= e(owTelHref((string) $cl['phone'])) ?>"><?= e($cl['phone']) ?></a><?php endif; ?><span><?= e((string) $cl['created_at']) ?></span></div>
        <?php if ($hasPh && $cl['photo_credit'] !== ''): ?><p class="mini" style="margin:0"><?= owBi('Photo credit: ', 'مصدر الصورة: ') ?><?= e($cl['photo_credit']) ?></p><?php endif; ?>
        <?php if (trim((string) $cl['message']) !== ''): ?><p style="white-space:pre-line;margin:6px 0"><?= e((string) $cl['message']) ?></p><?php endif; ?>
        <form method="post" action="admin_art_map.php" class="acts" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center"><?= csrfField() ?><input type="hidden" name="claim" value="<?= (int) $cl['id'] ?>">
          <?php if ($hasPh && $cl['place_kind'] === 'place'): ?><label class="mini" style="display:flex;gap:6px;align-items:center"><input type="checkbox" name="use_photo" value="1" checked> <?= owBi('Use their photo on the place', 'استخدم صورتهم على المكان') ?></label><?php endif; ?>
          <button class="btn btn-accent btn-sm" name="do" value="claim_ok"><?= owBi('Approve', 'وافق') ?></button>
          <button class="btn btn-danger btn-sm" name="do" value="claim_no" onclick="return confirm(document.documentElement.dir==='rtl'?'رفض هذه الرسالة؟':'Reject this message?')"><?= owBi('Reject', 'ارفض') ?></button>
        </form>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
  </div>

  <form class="card flt" method="get" action="admin_art_map.php">
    <div class="field"><label for="fk"><?= owBi('Type', 'النوع') ?></label><select id="fk" name="k"><?= $opt(OW_MAP_KINDS, $fk, 2, 'All · الكل') ?></select></div>
    <div class="field"><label for="fst"><?= owBi('Status', 'الحالة') ?></label><select id="fst" name="st"><?= $opt(['draft' => ['Draft', 'مسودة'], 'published' => ['Published', 'منشور']], $fst, 0, 'All · الكل') ?></select></div>
    <div class="field"><label for="frg"><?= owBi('Region', 'المنطقة') ?></label><select id="frg" name="rg"><?= $opt(OW_MAP_REGIONS, $frg, 0, 'All · الكل') ?></select></div>
    <div class="field"><label for="fcf"><?= owBi('Confidence', 'الثقة') ?></label><select id="fcf" name="cf"><?= $opt(OW_MAP_CONF, $fcf, 0, 'All · الكل') ?></select></div>
    <div class="field"><label for="fs"><?= owBi('Search', 'بحث') ?></label><input id="fs" type="search" name="s" value="<?= e($fs) ?>" maxlength="80"></div>
    <button class="btn btn-dark btn-sm" type="submit"><?= owBi('Show', 'عرض') ?></button>
  </form>
  <p class="mini"><?= owBi(count($rows) . ' shown', 'المعروض ' . count($rows)) ?></p>

  <div class="card"><details<?= $errors && (int) ($_POST['id'] ?? 0) === 0 ? ' open' : '' ?>><summary><?= owBi('+ New place', '+ مكان جديد') ?></summary><?php $form([]); ?></details></div>

  <?php foreach ($rows as $p): $pub = $p['status'] === 'published'; $K = OW_MAP_KINDS[$p['kind']] ?? OW_MAP_KINDS['museum']; $R = OW_MAP_REGIONS[$p['region']] ?? ['—', '—']; $cf = OW_MAP_CONF[$p['confidence']] ?? OW_MAP_CONF['medium'];
        $ph = owPlacePhotoBase($p); $src = (array) (json_decode((string) $p['sources'], true) ?: []); ?>
  <div class="card<?= $p['confidence'] === 'low' ? ' is-low' : '' ?>" id="p<?= (int) $p['id'] ?>">
    <div class="pl">
      <?php if ($ph !== ''): ?><img loading="lazy" src="<?= e(owBase() . $ph . '.jpg') ?>" alt="<?= e($p['name_en'] . ' · ' . $p['name_ar']) ?>"><?php else: ?><span class="noimg"><?= owBi('No photo (placeholder)', 'بلا صورة (بديل)') ?></span><?php endif; ?>
      <div>
        <div class="pl-head">
          <div><h3><?= e($p['name_en']) ?> · <span dir="rtl"><?= e($p['name_ar']) ?></span></h3>
            <div class="mini"><?= owBi($K[2], $K[3]) ?> · <?= e($p['city_en']) ?> · <span dir="rtl"><?= e($p['city_ar']) ?></span> · <?= owBi($R[0], $R[1]) ?></div></div>
          <div class="acts"><span class="cf <?= e($p['confidence']) ?>"><?= owBi('Confidence: ' . $cf[0], 'الثقة: ' . $cf[1]) ?></span> <span class="pillx<?= $pub ? ' on' : ' warn' ?>"><?= $pub ? owBi('Published', 'منشور') : owBi('Draft', 'مسودة') ?></span>
            <form method="post" action="admin_art_map.php?<?= e(ltrim($keep(), '&')) ?>"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
              <?php if ($pub): ?><button class="btn btn-secondary btn-sm" name="do" value="draft"><?= owBi('Back to draft', 'أعده مسودة') ?></button>
              <?php else: ?><button class="btn btn-accent btn-sm" name="do" value="publish"><?= owBi('Publish', 'انشر') ?></button><?php endif; ?></form></div>
        </div>
        <p class="mini" style="margin:6px 0 0"><?= e((string) $p['desc_en']) ?></p><p class="mini" dir="rtl" style="margin:2px 0 0"><?= e((string) $p['desc_ar']) ?></p>
        <div class="pl-links">
          <?php if ($p['address'] !== ''): ?><span><?= owBi('Address: ', 'العنوان: ') ?><?= e($p['address']) ?></span><?php endif; ?>
          <?php if ($p['phone'] !== ''): ?><span dir="ltr"><?= e($p['phone']) ?></span><?php endif; ?>
          <?php if (owMapUrl((string) $p['website']) !== ''): ?><a href="<?= e($p['website']) ?>" target="_blank" rel="noopener"><?= owBi('Website', 'الموقع') ?> ↗</a><?php endif; ?>
          <?php if (owInstaUrl((string) $p['instagram']) !== ''): ?><a href="<?= e(owInstaUrl((string) $p['instagram'])) ?>" target="_blank" rel="noopener">Instagram ↗</a><?php endif; ?>
          <a href="<?= e(owMapsLink($p['name_en'], $p['city_en'], (string) $p['maps_url'])) ?>" target="_blank" rel="noopener"><?= owBi('Google Maps', 'خرائط Google') ?> ↗</a>
          <?php if ($p['photo_credit'] !== ''): ?><span><?= owBi('Photo: ', 'الصورة: ') ?><?= e($p['photo_credit']) ?></span><?php endif; ?>
        </div>
        <?php if ($src): ?><div class="mini"><b><?= owBi('Sources (only you see these):', 'المصادر (تراها أنت فقط):') ?></b><ol class="src"><?php foreach ($src as $u): if (owMapUrl((string) $u) === '') continue; ?><li><a href="<?= e($u) ?>" target="_blank" rel="noopener nofollow"><?= e($u) ?></a></li><?php endforeach; ?></ol></div><?php endif; ?>
        <details<?= $photoErr === (int) $p['id'] ? ' open' : '' ?>><summary><?= $ph !== '' ? owBi('Change the photo', 'غيّر الصورة') : owBi('Add a photo', 'أضف صورة') ?></summary>
          <form method="post" enctype="multipart/form-data" action="admin_art_map.php?<?= e(ltrim($keep(), '&')) ?>"><?= csrfField() ?><input type="hidden" name="do" value="photo"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
            <div class="field"><label for="ph-<?= (int) $p['id'] ?>"><?= owBi('Photo (JPG, PNG or WebP, up to 10 MB)', 'الصورة (JPG أو PNG أو WebP، حتى 10 ميغابايت)') ?></label><input id="ph-<?= (int) $p['id'] ?>" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required></div>
            <div class="g2"><?= $field('photo_credit', 'Credit line (optional), e.g. the museum or the photographer', 'سطر المصدر (اختياري)، مثل اسم المتحف أو المصوّر', $photoErr === (int) $p['id'] ? (string) ($_POST['photo_credit'] ?? '') : '', ' maxlength="200"', false, 'ph' . $p['id']) ?>
              <?= $field('photo_page', 'Credit link (optional)', 'رابط المصدر (اختياري)', $photoErr === (int) $p['id'] ? (string) ($_POST['photo_page'] ?? '') : '', ' dir="ltr" maxlength="500"', false, 'ph' . $p['id']) ?></div>
            <p class="mini"><?= owBi('It replaces the current photo and shows on the Art Map at once (a published place). Only use a photo you have the right to show.', 'تحلّ محل الصورة الحالية وتظهر في خريطة الفن فوراً (للمكان المنشور). استخدم فقط صورة يحق لك نشرها.') ?></p>
            <button class="btn btn-primary btn-sm" type="submit"><?= owBi('Upload the photo', 'ارفع الصورة') ?></button>
          </form></details>
        <details<?= $errors && !$photoErr && (int) ($_POST['id'] ?? 0) === (int) $p['id'] ? ' open' : '' ?>><summary><?= owBi('Edit', 'تعديل') ?></summary><?php $form($p); ?></details>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
<?php owAdminBottom();
