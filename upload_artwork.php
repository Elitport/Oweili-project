<?php
/* =====================================================================
 *  upload_artwork.php — 3-stage proof-of-process upload (5 steps)
 *    1 Title  ·  2 Stage 25%  ·  3 Stage 50% (optional)
 *    4 Stage 100% + colors + art type  ·  5 Price & submit
 *  Each step is saved as soon as it is done, so the artist can come back
 *  days later (the artwork stays a private draft until "Submit for Review").
 *  Tables: artworks (is_draft), artwork_stages, artwork_colors
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/artwork_lib.php';

/* ---- Access: logged-in artists only ---- */
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$me = currentUser();
if (!$me || ($me['role'] ?? '') !== 'artist' || !empty($me['is_banned'])) {
    header('Location: ' . dashboardUrl($me['role'] ?? ''));
    exit;
}
$uid = (int) $me['id'];

$ART_TYPES = require __DIR__ . '/art_types.php';
$COLORS    = owColors();
$TYPES     = owUploadTypes();
$ready     = owStagesReady();

$error = '';                       // error code for the current step
$done  = isset($_GET['done']);

/* ---- Load the draft being worked on (must belong to this artist) ---- */
function owLoadDraft(int $id, int $uid): ?array {
    $st = getDB()->prepare('SELECT * FROM artworks WHERE id = :id AND artist_id = :uid AND is_draft = 1 LIMIT 1');
    $st->execute([':id' => $id, ':uid' => $uid]);
    $row = $st->fetch();
    return $row ?: null;
}

$draft = null;
$artId = (int) ($_POST['artwork_id'] ?? $_GET['id'] ?? 0);
if ($ready && $artId > 0) {
    $draft = owLoadDraft($artId, $uid);
    if (!$draft) {                 // not theirs, already submitted, or deleted
        header('Location: artist_dashboard.php');
        exit;
    }
}

/* ---- POST handlers (CSRF-checked, Post/Redirect/Get) ---- */
/* A photo bigger than the server's upload limit empties the whole form —
   show the friendly "too large" message instead of a security error. */
$tooBig = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
if ($tooBig) { $error = 'size'; }

if ($ready && !$tooBig && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) { http_response_code(403); exit('Invalid CSRF token.'); }
    $action = $_POST['action'] ?? '';
    $db = getDB();

    /* Step 1 — titles and descriptions */
    if ($action === 'save_details') {
        $tAr = sanitize($_POST['title_ar'] ?? '');
        $tEn = sanitize($_POST['title_en'] ?? '');
        $dAr = sanitize($_POST['desc_ar'] ?? '');
        $dEn = sanitize($_POST['desc_en'] ?? '');
        if ($tAr === '' || $tEn === '') {
            $error = 'titles';
        } elseif (mb_strlen($tAr) > 200 || mb_strlen($tEn) > 200 || mb_strlen($dAr) > 3000 || mb_strlen($dEn) > 3000) {
            $error = 'too_long';
        } elseif ($draft) {
            $db->prepare('UPDATE artworks SET title_ar = :tar, title_en = :ten, description_ar = :dar, description_en = :den
                          WHERE id = :id AND artist_id = :uid AND is_draft = 1')
               ->execute([':tar' => $tAr, ':ten' => $tEn, ':dar' => $dAr, ':den' => $dEn, ':id' => $draft['id'], ':uid' => $uid]);
            header('Location: upload_artwork.php?id=' . (int) $draft['id'] . '&step=2');
            exit;
        } else {
            $db->prepare('INSERT INTO artworks
                            (artist_id, title_en, title_ar, description_en, description_ar, price, type, status, image_url, is_approved, is_draft)
                          VALUES (:uid, :ten, :tar, :den, :dar, 0, "", "available", "", 0, 1)')
               ->execute([':uid' => $uid, ':ten' => $tEn, ':tar' => $tAr, ':den' => $dEn, ':dar' => $dAr]);
            header('Location: upload_artwork.php?id=' . (int) $db->lastInsertId() . '&step=2');
            exit;
        }
    }

    /* Steps 2–4 — save one stage photo (replaces an earlier one = "retake") */
    if ($action === 'save_stage' && $draft) {
        $stage = (int) ($_POST['stage'] ?? 0);
        if (!in_array($stage, OW_STAGES, true)) {
            $error = 'none';
        } else {
            $res = owStoreStagePhoto($_FILES['photo'] ?? []);
            if (!$res['ok']) {
                $error = $res['err'];
            } else {
                $old = owGetStages((int) $draft['id'])[$stage]['photo_path'] ?? '';
                $db->prepare('INSERT INTO artwork_stages (artwork_id, stage, photo_path, taken_at)
                              VALUES (:aid, :st, :p, :t)
                              ON DUPLICATE KEY UPDATE photo_path = VALUES(photo_path), taken_at = VALUES(taken_at), created_at = CURRENT_TIMESTAMP')
                   ->execute([':aid' => $draft['id'], ':st' => $stage, ':p' => $res['path'], ':t' => $res['taken_at']]);
                if ($old !== '' && $old !== $res['path']) { owDeleteStageFile($old); }
                $next = $stage === 25 ? 3 : 4;
                header('Location: upload_artwork.php?id=' . (int) $draft['id'] . '&step=' . $next . ($stage === 100 ? '#choices' : ''));
                exit;
            }
        }
    }

    /* Step 4 — colors used + art type */
    if ($action === 'save_choices' && $draft) {
        $stages = owGetStages((int) $draft['id']);
        $type   = (string) ($_POST['type'] ?? '');
        $picked = array_values(array_unique(array_intersect((array) ($_POST['colors'] ?? []), array_keys($COLORS))));
        if (!isset($stages[100])) {
            $error = 'need100';
        } elseif (!isset($TYPES[$type]) || !isset($ART_TYPES[$type])) {
            $error = 'type';
        } else {
            $db->beginTransaction();
            try {
                $db->prepare('UPDATE artworks SET type = :t WHERE id = :id AND artist_id = :uid AND is_draft = 1')
                   ->execute([':t' => $type, ':id' => $draft['id'], ':uid' => $uid]);
                $db->prepare('DELETE FROM artwork_colors WHERE artwork_id = :id')->execute([':id' => $draft['id']]);
                $ins = $db->prepare('INSERT INTO artwork_colors (artwork_id, color_name_en, color_name_ar, hex_code) VALUES (:id, :en, :ar, :hex)');
                foreach ($picked as $key) {
                    $ins->execute([':id' => $draft['id'], ':en' => $COLORS[$key]['en'], ':ar' => $COLORS[$key]['ar'], ':hex' => $COLORS[$key]['hex']]);
                }
                $db->commit();
            } catch (Throwable $e) {
                $db->rollBack();
                throw $e;
            }
            header('Location: upload_artwork.php?id=' . (int) $draft['id'] . '&step=5');
            exit;
        }
    }

    /* Step 5 — price, then submit for review */
    if ($action === 'submit' && $draft) {
        $stages = owGetStages((int) $draft['id']);
        $raw    = str_replace([',', ' '], '', (string) ($_POST['price'] ?? ''));
        if (!isset($stages[25]) || !isset($stages[100]) || $draft['type'] === '') {
            $error = 'incomplete';
        } elseif (!preg_match('/^\d{1,7}(\.\d{1,2})?$/', $raw) || (float) $raw < 1) {
            $error = 'price';
        } else {
            $db->prepare('UPDATE artworks
                             SET price = :p, image_url = :img, status = "available", is_approved = 0, is_draft = 0
                           WHERE id = :id AND artist_id = :uid AND is_draft = 1')
               ->execute([':p' => (float) $raw, ':img' => $stages[100]['photo_path'], ':id' => $draft['id'], ':uid' => $uid]);
            header('Location: upload_artwork.php?done=1');
            exit;
        }
    }
}

/* ---- Work out which steps are open ---- */
$stages = $draft ? owGetStages((int) $draft['id']) : [];
$savedColors = [];
if ($draft) {
    $st = getDB()->prepare('SELECT color_name_en FROM artwork_colors WHERE artwork_id = :id');
    $st->execute([':id' => $draft['id']]);
    $byName = array_column(array_map(fn($k, $c) => ['k' => $k, 'en' => $c['en']], array_keys($COLORS), $COLORS), 'k', 'en');
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $n) { if (isset($byName[$n])) $savedColors[] = $byName[$n]; }
}
$maxStep = !$draft ? 1 : (!isset($stages[25]) ? 2 : ((isset($stages[100]) && $draft['type'] !== '') ? 5 : 4));
$step    = (int) ($_POST['step'] ?? $_GET['step'] ?? $maxStep);
$step    = max(1, min($step, $maxStep));
$idQ     = $draft ? '?id=' . (int) $draft['id'] . '&step=' : '?step=';

/* Bilingual error messages */
$ERR = [
    'titles'     => ['Please enter the title in both Arabic and English.', 'يرجى إدخال العنوان بالعربية والإنجليزية.'],
    'too_long'   => ['Titles can be up to 200 characters and descriptions up to 3,000.', 'العنوان حتى 200 حرف والوصف حتى 3000 حرف.'],
    'none'       => ['Please take a photo first.', 'يرجى التقاط صورة أولاً.'],
    'size'       => ['This photo is larger than 10MB. Please take it again.', 'حجم الصورة أكبر من 10 ميغابايت. يرجى التقاطها مرة أخرى.'],
    'type'       => ['Please use a JPG, PNG or WEBP photo.', 'يرجى استخدام صورة بصيغة JPG أو PNG أو WEBP.'],
    'save'       => ['The photo could not be saved. Please try again.', 'تعذّر حفظ الصورة. يرجى المحاولة مرة أخرى.'],
    'need100'    => ['Please take the finished (100%) photo first.', 'يرجى التقاط صورة العمل المكتمل (100%) أولاً.'],
    'incomplete' => ['The 25% photo, the 100% photo and the art type are required before submitting.', 'صورة 25% وصورة 100% ونوع الفن مطلوبة قبل الإرسال.'],
    'price'      => ['Please enter a price of at least 1 SAR (numbers only).', 'يرجى إدخال سعر لا يقل عن 1 ريال (أرقام فقط).'],
];
if ($error === 'type' && ($_POST['action'] ?? '') === 'save_choices') {
    $ERR['type'] = ['Please choose the type of art.', 'يرجى اختيار نوع الفن.'];
}

$STEP_NAMES = [
    1 => ['Title', 'العنوان'], 2 => ['Stage 25%', 'مرحلة 25%'], 3 => ['Stage 50%', 'مرحلة 50%'],
    4 => ['Stage 100%', 'مرحلة 100%'], 5 => ['Price', 'السعر'],
];

/* Small helper: one bilingual text node (switched by nav.php's language sync) */
function t2(string $en, string $ar, string $tag = 'span', string $attrs = ''): string {
    return '<' . $tag . ' class="nav-i18n"' . ($attrs !== '' ? ' ' . $attrs : '') . ' data-en="' . e($en) . '" data-ar="' . e($ar) . '">' . e($en) . '</' . $tag . '>';
}

/* Camera block used by steps 2, 3 and 4 */
function cameraBlock(array $draft, int $stage, array $stages): string {
    $saved = $stages[$stage] ?? null;
    $txt   = owStageText($stage);
    ob_start(); ?>
    <p class="stage-msg"><?= t2($txt['en'], $txt['ar'], 'span') ?></p>
    <form method="post" action="upload_artwork.php" enctype="multipart/form-data" class="cam-form" data-max="<?= OW_STAGE_MAX_SIZE ?>">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="save_stage">
      <input type="hidden" name="artwork_id" value="<?= (int) $draft['id'] ?>">
      <input type="hidden" name="stage" value="<?= $stage ?>">
      <input type="hidden" name="step" value="<?= $stage === 25 ? 2 : ($stage === 50 ? 3 : 4) ?>">
      <input class="cam-input" id="cam-<?= $stage ?>" type="file" name="photo" accept="image/jpeg,image/png,image/webp" capture="environment">
      <div class="preview<?= $saved ? ' has' : '' ?>">
        <?php if ($saved): ?>
          <img src="<?= e($saved['photo_path']) ?>" alt="<?= e('Stage ' . $stage . '% photo · صورة مرحلة ' . $stage . '%') ?>">
          <span class="saved-tag"><?= t2('Saved', 'تم الحفظ') ?></span>
        <?php else: ?>
          <img alt="" hidden>
        <?php endif; ?>
      </div>
      <label for="cam-<?= $stage ?>" class="cam-btn">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h3l2-3h6l2 3h3a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1z"/><circle cx="12" cy="13" r="4"/></svg>
        <span class="cam-label-new"<?= $saved ? ' hidden' : '' ?>><?= t2('Open Camera', 'افتح الكاميرا') ?></span>
        <span class="cam-label-retake"<?= $saved ? '' : ' hidden' ?>><?= t2('Retake Photo', 'أعد التصوير') ?></span>
      </label>
      <p class="cam-err" role="alert" hidden><?= t2('This photo is larger than 10MB. Please take it again.', 'حجم الصورة أكبر من 10 ميغابايت. يرجى التقاطها مرة أخرى.') ?></p>
      <button type="submit" class="btn btn-dark btn-block save-photo" hidden><?= t2('Save Photo', 'احفظ الصورة') ?></button>
    </form>
    <?php return ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Start New Artwork · Oweili</title>
<meta name="robots" content="noindex">
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="/assets/css/oweili.css?v=<?= @filemtime(__DIR__ . '/assets/css/oweili.css') ?>">
<link rel="stylesheet" href="/assets/css/nav.css?v=<?= @filemtime(__DIR__ . '/assets/css/nav.css') ?>">
<style>
/* 3-stage upload — layout only; buttons, cards, inputs and alerts come from oweili.css */
.wrap h1{font-size:var(--fs-section);margin:0 0 var(--space-xs);}
.panel{margin-bottom:var(--space-md);}
.panel h2{font-size:var(--fs-card);margin:0 0 var(--space-sm);}
.field .opt{font-weight:400;color:var(--color-body);}
.btn[disabled]{opacity:0.4;cursor:wait;}
.ow{background:var(--color-bg);}
.wrap{max-width:720px;margin:0 auto;padding:calc(var(--nav-h) + var(--space-lg)) var(--space-sm) var(--space-xl);}
.lead{color:var(--color-body);margin-bottom:24px;}
/* progress bar — flex follows the page direction, so it fills right-to-left in Arabic */
.progress{margin-bottom:24px;}
.progress-label{display:flex;justify-content:space-between;font-size:13px;font-weight:600;color:var(--color-dark);margin-bottom:8px;}
.bars{display:flex;gap:6px;}
.bars a,.bars span{flex:1;height:6px;border-radius:999px;background:rgba(var(--rgb-ink),0.08);}
.bars .on{background:var(--color-accent);}
.bars a:focus-visible{outline:2px solid var(--color-accent);outline-offset:3px;}
.ow .panel h2{font-size:20px;margin-bottom:14px;}
.stage-msg{font-size:16px;line-height:1.7;color:var(--color-dark);margin-bottom:16px;}
.cam-input{position:absolute;width:1px;height:1px;opacity:0;pointer-events:none;}
.preview{display:none;position:relative;margin-bottom:14px;border-radius:14px;overflow:hidden;background:var(--color-surface-2);}
.preview.has{display:block;}
.preview img{display:block;width:100%;max-height:60vh;object-fit:contain;}
.saved-tag{position:absolute;top:10px;inset-inline-start:10px;background:var(--color-dark);color:var(--color-on-dark);font-size:12px;font-weight:700;padding:4px 10px;border-radius:999px;}
.cam-btn{display:flex;align-items:center;justify-content:center;gap:12px;min-height:88px;width:100%;border-radius:var(--radius-card);border:2px dashed rgba(var(--rgb-accent),0.4);background:rgba(var(--rgb-accent),0.05);color:var(--color-accent);font-size:17px;font-weight:700;cursor:pointer;transition:background 0.2s,border-color 0.2s;}
.cam-btn:hover{background:rgba(var(--rgb-accent),0.1);border-color:var(--color-accent);}
.cam-btn:active{background:rgba(var(--rgb-accent),0.16);}
.cam-input:focus-visible + .preview + .cam-btn{outline:2px solid var(--color-accent);outline-offset:3px;}
.cam-btn svg{width:30px;height:30px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linejoin:round;}
.cam-err{color:var(--color-dark);font-size:14px;font-weight:600;margin-top:10px;}
.chip:focus-within,.type-card:focus-within{outline:2px solid var(--color-accent);outline-offset:2px;}
.save-photo{width:100%;margin-top:12px;}
.actions{display:flex;flex-wrap:wrap;gap:10px;justify-content:space-between;align-items:center;margin-top:8px;}
.skip{font-size:14px;font-weight:600;color:var(--color-accent);text-decoration:underline;padding:12px 4px;}
.note{font-size:14px;line-height:1.7;color:var(--color-body);background:var(--color-surface-2);border-radius:12px;padding:12px 14px;margin:6px 0 18px;}
/* colors */
.chips{display:flex;flex-wrap:wrap;gap:8px;}
.chip{position:relative;}
.chip input{position:absolute;opacity:0;width:1px;height:1px;}
.chip > span{display:inline-flex;align-items:center;gap:8px;min-height:44px;padding:8px 14px;border-radius:var(--radius-pill);border:1px solid var(--color-border-strong);background:var(--color-surface);font-size:14px;font-weight:600;color:var(--color-dark);cursor:pointer;transition:background 0.15s,border-color 0.15s;}
.chip > span:hover{border-color:var(--color-accent);}
.chip i{width:16px;height:16px;border-radius:50%;border:1px solid rgba(var(--rgb-ink),0.2);flex-shrink:0;}
.chip input:checked + span{background:var(--color-accent);border-color:var(--color-accent);color:var(--color-on-accent);}
/* art types */
.types{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;}
.type-card{position:relative;}
.type-card input{position:absolute;opacity:0;width:1px;height:1px;}
.type-card > span{display:flex;flex-direction:column;align-items:center;gap:8px;min-height:116px;padding:12px 8px;border-radius:var(--radius-card);border:1px solid var(--color-border-strong);background:var(--color-surface);font-size:14px;font-weight:600;color:var(--color-dark);text-align:center;cursor:pointer;transition:border-color 0.15s,box-shadow 0.15s;}
.type-card > span:hover{border-color:var(--color-accent);}
.type-card svg{width:64px;height:64px;border-radius:10px;background:var(--color-surface-2);}
.type-card input:checked + span{border:2px solid var(--color-accent);box-shadow:0 0 0 3px rgba(var(--rgb-accent),0.15);color:var(--color-accent);}
.sub-h{font-size:16px;font-weight:700;color:var(--color-dark);margin:22px 0 4px;}
.sub-p{font-size:var(--fs-label);color:var(--color-body);margin-bottom:12px;}
.price-row{display:flex;align-items:center;gap:10px;}
.price-row input{flex:1;}
.price-row b{font-size:15px;color:var(--color-dark);}
.done-box{text-align:center;padding:40px 24px;}
.done-box svg{width:64px;height:64px;stroke:var(--color-accent);fill:none;stroke-width:2;margin-bottom:12px;}
.done-box .actions{justify-content:center;}
@media (min-width:640px){.types{grid-template-columns:repeat(3,1fr);}}
@media (min-width:900px){.types{grid-template-columns:repeat(4,1fr);}}
@media (prefers-reduced-motion:reduce){*{transition:none!important;}}
</style>
</head>
<body class="ow">
<?php include __DIR__ . '/nav.php'; ?>

<main class="wrap">
<?php if (!$ready): ?>
  <div class="card panel">
    <?= t2('Start New Artwork', 'ابدأ عملاً فنياً جديداً', 'h1') ?>
    <p class="lead"><?= t2('The new 3-stage upload is being set up. Please check back soon.', 'يجري تجهيز نظام الرفع الجديد بثلاث مراحل. يرجى العودة قريباً.') ?></p>
    <a class="btn btn-secondary" href="artist_dashboard.php"><?= t2('Back to my dashboard', 'العودة إلى لوحتي') ?></a>
  </div>

<?php elseif ($done): ?>
  <div class="card panel done-box">
    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M7 12.5l3.2 3.2L17 9"/></svg>
    <?= t2('Your artwork has been submitted. Our team will review it within 24 hours.', 'تم إرسال عملك. سيراجعه فريقنا خلال 24 ساعة.', 'h1', 'style="font-size:22px;line-height:1.5;"') ?>
    <div class="actions" style="margin-top:20px;">
      <a class="btn btn-dark" href="artist_dashboard.php"><?= t2('Back to my dashboard', 'العودة إلى لوحتي') ?></a>
      <a class="btn btn-secondary" href="upload_artwork.php"><?= t2('Start another artwork', 'ابدأ عملاً آخر') ?></a>
    </div>
  </div>

<?php else: ?>
  <?= t2('Start New Artwork', 'ابدأ عملاً فنياً جديداً', 'h1') ?>
  <p class="lead"><?= t2('Show the real journey of your work. Your progress is saved after every step — you can come back any time.', 'اعرض الرحلة الحقيقية لعملك. يُحفظ تقدّمك بعد كل خطوة — يمكنك العودة في أي وقت.') ?></p>

  <nav class="progress" aria-label="Progress · التقدم">
    <div class="progress-label">
      <span class="nav-i18n" data-en="Step <?= $step ?> of 5" data-ar="الخطوة <?= $step ?> من 5">Step <?= $step ?> of 5</span>
      <?= t2($STEP_NAMES[$step][0], $STEP_NAMES[$step][1]) ?>
    </div>
    <div class="bars">
      <?php for ($i = 1; $i <= 5; $i++):
        $cls = $i <= $step ? 'on' : '';
        $lbl = e($STEP_NAMES[$i][0] . ' · ' . $STEP_NAMES[$i][1]);
        if ($i <= $maxStep && $i !== $step): ?>
          <a class="<?= $cls ?>" href="upload_artwork.php<?= $idQ . $i ?>" aria-label="<?= $lbl ?>"></a>
        <?php else: ?>
          <span class="<?= $cls ?>" aria-label="<?= $lbl ?>"<?= $i === $step ? ' aria-current="step"' : '' ?>></span>
        <?php endif; endfor; ?>
    </div>
  </nav>

  <?php if ($error !== '' && isset($ERR[$error])): ?>
    <div class="alert alert-error" role="alert"><?= t2($ERR[$error][0], $ERR[$error][1]) ?></div>
  <?php endif; ?>

  <?php if ($step === 1): ?>
    <section class="card panel">
      <?= t2('Artwork title', 'عنوان العمل', 'h2') ?>
      <form method="post" action="upload_artwork.php">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="save_details">
        <input type="hidden" name="step" value="1">
        <?php if ($draft): ?><input type="hidden" name="artwork_id" value="<?= (int) $draft['id'] ?>"><?php endif; ?>
        <div class="field">
          <label for="title_ar"><?= t2('Title in Arabic', 'العنوان بالعربية') ?></label>
          <input id="title_ar" name="title_ar" dir="rtl" lang="ar" required maxlength="200" value="<?= e($_POST['title_ar'] ?? ($draft['title_ar'] ?? '')) ?>">
        </div>
        <div class="field">
          <label for="title_en"><?= t2('Title in English', 'العنوان بالإنجليزية') ?></label>
          <input id="title_en" name="title_en" dir="ltr" lang="en" required maxlength="200" value="<?= e($_POST['title_en'] ?? ($draft['title_en'] ?? '')) ?>">
        </div>
        <div class="field">
          <label for="desc_ar"><?= t2('Description in Arabic', 'الوصف بالعربية') ?> <span class="opt nav-i18n" data-en="(optional)" data-ar="(اختياري)">(optional)</span></label>
          <textarea id="desc_ar" name="desc_ar" dir="rtl" lang="ar" maxlength="3000"><?= e($_POST['desc_ar'] ?? ($draft['description_ar'] ?? '')) ?></textarea>
        </div>
        <div class="field">
          <label for="desc_en"><?= t2('Description in English', 'الوصف بالإنجليزية') ?> <span class="opt nav-i18n" data-en="(optional)" data-ar="(اختياري)">(optional)</span></label>
          <textarea id="desc_en" name="desc_en" dir="ltr" lang="en" maxlength="3000"><?= e($_POST['desc_en'] ?? ($draft['description_en'] ?? '')) ?></textarea>
        </div>
        <div class="actions">
          <a class="btn btn-secondary" href="artist_dashboard.php"><?= t2('Cancel', 'إلغاء') ?></a>
          <button type="submit" class="btn btn-dark"><?= t2('Save & Continue', 'احفظ وتابع') ?></button>
        </div>
      </form>
    </section>

  <?php elseif ($step === 2): ?>
    <section class="card panel">
      <?= t2('Stage 25% — Early stage', 'مرحلة 25% — البداية', 'h2') ?>
      <?= cameraBlock($draft, 25, $stages) ?>
    </section>
    <div class="actions">
      <a class="btn btn-secondary" href="upload_artwork.php<?= $idQ ?>1"><?= t2('Back', 'رجوع') ?></a>
      <?php if (isset($stages[25])): ?><a class="btn btn-dark" href="upload_artwork.php<?= $idQ ?>3"><?= t2('Continue', 'متابعة') ?></a><?php endif; ?>
    </div>

  <?php elseif ($step === 3): ?>
    <section class="card panel">
      <?= t2('Stage 50% — Middle stage', 'مرحلة 50% — المنتصف', 'h2') ?>
      <?= cameraBlock($draft, 50, $stages) ?>
    </section>
    <div class="actions">
      <a class="btn btn-secondary" href="upload_artwork.php<?= $idQ ?>2"><?= t2('Back', 'رجوع') ?></a>
      <?php if (isset($stages[50])): ?>
        <a class="btn btn-dark" href="upload_artwork.php<?= $idQ ?>4"><?= t2('Continue', 'متابعة') ?></a>
      <?php else: ?>
        <a class="skip" href="upload_artwork.php<?= $idQ ?>4"><?= t2('Skip this stage (optional)', 'تخطَّ هذه المرحلة (اختياري)') ?></a>
      <?php endif; ?>
    </div>

  <?php elseif ($step === 4): ?>
    <section class="card panel">
      <?= t2('Stage 100% — Finished piece', 'مرحلة 100% — العمل المكتمل', 'h2') ?>
      <?= cameraBlock($draft, 100, $stages) ?>
    </section>

    <?php if (isset($stages[100])): ?>
    <section class="card panel" id="choices">
      <form method="post" action="upload_artwork.php">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="save_choices">
        <input type="hidden" name="artwork_id" value="<?= (int) $draft['id'] ?>">
        <input type="hidden" name="step" value="4">

        <fieldset style="border:0;">
          <legend class="sub-h" style="margin-top:0;"><?= t2('Colors used', 'الألوان المستخدمة') ?></legend>
          <p class="sub-p"><?= t2('Tap every color you used.', 'اضغط على كل لون استخدمته.') ?></p>
          <div class="chips">
            <?php $sel = isset($_POST['colors']) ? (array) $_POST['colors'] : $savedColors;
            foreach ($COLORS as $key => $c): ?>
              <label class="chip">
                <input type="checkbox" name="colors[]" value="<?= e($key) ?>"<?= in_array($key, $sel, true) ? ' checked' : '' ?>>
                <span><i style="background:<?= e($c['hex']) ?>"></i><?= t2($c['en'], $c['ar']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </fieldset>

        <fieldset style="border:0;">
          <legend class="sub-h"><?= t2('Type of art', 'نوع الفن') ?></legend>
          <p class="sub-p"><?= t2('Choose the one closest to your work. The pictures show how each type usually looks.', 'اختر الأقرب إلى عملك. توضح الصور الشكل المعتاد لكل نوع.') ?></p>
          <div class="types">
            <?php $selType = (string) ($_POST['type'] ?? $draft['type']);
            foreach ($TYPES as $key => $svg): if (!isset($ART_TYPES[$key])) continue; ?>
              <label class="type-card">
                <input type="radio" name="type" value="<?= e($key) ?>" required<?= $selType === $key ? ' checked' : '' ?>>
                <span>
                  <svg viewBox="0 0 64 64" role="img" aria-label="<?= e($ART_TYPES[$key]['en'] . ' example · مثال ' . $ART_TYPES[$key]['ar']) ?>"><?= $svg ?></svg>
                  <?= t2($ART_TYPES[$key]['en'], $ART_TYPES[$key]['ar']) ?>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
        </fieldset>

        <div class="actions" style="margin-top:20px;">
          <a class="btn btn-secondary" href="upload_artwork.php<?= $idQ ?>3"><?= t2('Back', 'رجوع') ?></a>
          <button type="submit" class="btn btn-dark"><?= t2('Save & Continue', 'احفظ وتابع') ?></button>
        </div>
      </form>
    </section>
    <?php else: ?>
      <div class="actions"><a class="btn btn-secondary" href="upload_artwork.php<?= $idQ ?>3"><?= t2('Back', 'رجوع') ?></a></div>
    <?php endif; ?>

  <?php elseif ($step === 5): ?>
    <section class="card panel">
      <?= t2('Price', 'السعر', 'h2') ?>
      <form method="post" action="upload_artwork.php" class="submit-form">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="submit">
        <input type="hidden" name="artwork_id" value="<?= (int) $draft['id'] ?>">
        <input type="hidden" name="step" value="5">
        <div class="field">
          <label for="price"><?= t2('Price in Saudi Riyals (SAR)', 'السعر بالريال السعودي') ?></label>
          <div class="price-row">
            <input id="price" name="price" type="text" inputmode="decimal" dir="ltr" required pattern="[0-9,]+(\.[0-9]{1,2})?" maxlength="12"
                   value="<?= e($_POST['price'] ?? ((float) $draft['price'] > 0 ? (string) $draft['price'] : '')) ?>">
            <b class="nav-i18n" data-en="SAR" data-ar="ر.س">SAR</b>
          </div>
        </div>
        <p class="note"><?= t2('Oweili charges 15% commission on the sale price, which covers shipping and advertising. You receive 85% of your price.', 'تأخذ أويلي 15% عمولة من سعر البيع، تشمل الشحن والإعلانات. تستلم أنت 85% من سعرك.') ?></p>
        <div class="actions">
          <a class="btn btn-secondary" href="upload_artwork.php<?= $idQ ?>4"><?= t2('Back', 'رجوع') ?></a>
          <button type="submit" class="btn btn-primary btn-pink"><?= t2('Submit for Review', 'أرسل للمراجعة') ?></button>
        </div>
      </form>
    </section>
  <?php endif; ?>
<?php endif; ?>
</main>

<script>
/* Language switch for this page: flips direction, then nav.php's sync
   updates every .nav-i18n text. The menu's own labels are set here too. */
var NAV_T = {
  en: {'nav-studio-label':'The Studio','nav-community-label':'Community','nav-marketplace-label':'Gallery',
       'nav-explore-label':'Explore Art Styles','nav-support-label':'Support',
       'dd-s1':'Watercolor Workshop','dd-s2':'Oil Painting Studio','dd-s3':'Digital Art Lab','dd-s4':'Charcoal & Ink',
       'dd-c2':'Meet Fellow Artists','dd-c3':'Share Your Work','dd-c4':'Learn Together',
       'dd-sp1':'Contact Us','dd-sp2':'How It Works','dd-sp3':'Terms of Use','lb':'العربية'},
  ar: {'nav-studio-label':'الاستوديو','nav-community-label':'المجتمع','nav-marketplace-label':'المعرض',
       'nav-explore-label':'استكشف أساليب الرسم','nav-support-label':'الدعم',
       'dd-s1':'ورشة الألوان المائية','dd-s2':'استوديو الرسم الزيتي','dd-s3':'مختبر الفن الرقمي','dd-s4':'الفحم والحبر',
       'dd-c2':'تعرّف على فنانين','dd-c3':'شارك أعمالك','dd-c4':'تعلّم معاً',
       'dd-sp1':'تواصل معنا','dd-sp2':'كيف يعمل الموقع','dd-sp3':'شروط الاستخدام','lb':'English'}
};
function applyLang(l){
  var h = document.documentElement;
  h.setAttribute('dir', l === 'ar' ? 'rtl' : 'ltr');
  h.setAttribute('lang', l);
  var m = NAV_T[l];
  for (var id in m) { var el = document.getElementById(id); if (el) el.textContent = m[id]; }
  document.title = l === 'ar' ? 'ابدأ عملاً فنياً جديداً · أويلي' : 'Start New Artwork · Oweili';
}
function tgl(){
  var l = document.documentElement.getAttribute('dir') === 'rtl' ? 'en' : 'ar';
  applyLang(l);
  try { localStorage.setItem('lang', l); } catch (e) {}
}

/* Camera: show a preview after the photo is taken, allow retake, check size */
document.querySelectorAll('.cam-form').forEach(function(form){
  var input = form.querySelector('.cam-input');
  var prev  = form.querySelector('.preview');
  var img   = prev.querySelector('img');
  var save  = form.querySelector('.save-photo');
  var err   = form.querySelector('.cam-err');
  var max   = parseInt(form.dataset.max, 10);
  input.addEventListener('change', function(){
    var f = input.files && input.files[0];
    if (!f) return;
    if (f.size > max) { err.hidden = false; save.hidden = true; input.value = ''; return; }
    err.hidden = true;
    img.src = URL.createObjectURL(f);
    img.hidden = false;
    img.alt = 'Photo preview · معاينة الصورة';
    prev.classList.add('has');
    var tag = prev.querySelector('.saved-tag'); if (tag) tag.remove();
    form.querySelector('.cam-label-new').hidden = true;
    form.querySelector('.cam-label-retake').hidden = false;
    save.hidden = false;
  });
  form.addEventListener('submit', function(){
    setTimeout(function(){ save.disabled = true; }, 0);
    var s = save.querySelector('.nav-i18n');
    var ar = document.documentElement.getAttribute('dir') === 'rtl';
    s.textContent = ar ? 'جارٍ رفع الصورة…' : 'Uploading photo…';
  });
});
/* Loading state on the final submit */
document.querySelectorAll('.submit-form').forEach(function(f){
  f.addEventListener('submit', function(){ var b = f.querySelector('.btn-primary'); if (b) setTimeout(function(){ b.disabled = true; }, 0); });
});
/* Show the saved language straight away (nav.php may already have set the direction) */
applyLang(document.documentElement.getAttribute('dir') === 'rtl' ? 'ar' : 'en');
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
