<?php
require_once __DIR__ . '/studio_lib.php';   // the studio photo behind the page
/* =====================================================================
 *  place_claim.php — "Is this your place?" (owner, 13 Nov 2026).
 *  A museum, gallery or institute on the Art Map sends its own photo and
 *  corrections: place_claim.php?p=<art_places id> or ?i=<institutes id>.
 *  Name, email, phone (optional), photo (optional), photo credit (optional)
 *  and the corrections. Nothing shows publicly: the message waits in
 *  Admin → Waiting for approval (admin_art_map.php#claims) and an email
 *  goes to contact@oweili.com. The photo waits in the private folder
 *  .private/place-claims/ until the admin approves it.
 *  Spam protection: security token, a hidden trap field, the "I represent
 *  this place" tick, at most 3 messages per visitor per hour and 30 an hour
 *  for the whole site.
 *  Tables: art_place_claims (new, art_place_claims.sql), art_places,
 *  institutes, activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/artmap_lib.php';
require_once __DIR__ . '/request_lib.php';
require_once __DIR__ . '/lang_lib.php';

$pid = (int) ($_GET['p'] ?? 0); $iid = (int) ($_GET['i'] ?? 0);
$place = null; $pkind = $pid > 0 ? 'place' : 'institute'; $placeId = $pid > 0 ? $pid : $iid;
try {
    if ($pid > 0 && owArtPlacesReady()) {
        $st = getDB()->prepare("SELECT id, name_en, name_ar, city_en, city_ar FROM art_places WHERE id = :id AND status = 'published'"); $st->execute([':id' => $pid]); $place = $st->fetch() ?: null;
    } elseif ($iid > 0) {
        $st = getDB()->prepare('SELECT id, name_en, name_ar, city_en, city_ar FROM institutes WHERE id = :id AND is_active = 1 AND ' . owSignupInstSql()); $st->execute([':id' => $iid]); $place = $st->fetch() ?: null;
    }
} catch (Throwable $e) { $place = null; }
if (!$place) { http_response_code(404); require __DIR__ . '/404.php'; exit; }
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
$nEn = (string) ($place['name_en'] ?: $place['name_ar']); $nAr = (string) ($place['name_ar'] ?: $place['name_en']);
$self = 'place_claim.php?' . ($pkind === 'place' ? 'p=' : 'i=') . $placeId;
$ready = owClaimsReady();
$errors = []; $bad = [];
$v = ['name' => '', 'email' => '', 'phone' => '', 'message' => '', 'photo_credit' => ''];

if ($ready && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    foreach (['name' => 120, 'email' => 160, 'phone' => 30, 'photo_credit' => 200] as $k => $m) $v[$k] = owReqClean($k, $m);
    $v['message'] = owReqClean('message', 3000, true);
    $v['email'] = strtolower($v['email']);
    $hasFile = ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) $errors[] = ['The page was open too long. Please send it again.', 'انتهت صلاحية الصفحة. يرجى الإرسال مرة أخرى.'];
    elseif (trim((string) ($_POST['website'] ?? '')) !== '') { header('Location: ' . $self . '&sent=1'); exit; }   // a robot filled the hidden field: pretend all is well
    else {
        if ($v['name'] === '') { $errors[] = ['Please write your name.', 'يرجى كتابة اسمك.']; $bad[] = 'name'; }
        if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = ['Please check your email address.', 'يرجى مراجعة بريدك الإلكتروني.']; $bad[] = 'email'; }
        if ($v['phone'] !== '' && !preg_match('/^[0-9+()\-\s٠-٩]{6,30}$/u', $v['phone'])) { $errors[] = ['Please check the phone number.', 'يرجى مراجعة رقم الهاتف.']; $bad[] = 'phone'; }
        if (mb_strlen($v['message']) < 5 && !$hasFile) { $errors[] = ['Please send a photo or write your corrections.', 'يرجى إرسال صورة أو كتابة التصحيحات.']; $bad[] = 'message'; }
        if (empty($_POST['confirm'])) { $errors[] = ['Please confirm that you represent this place.', 'يرجى تأكيد أنك تمثّل هذا المكان.']; $bad[] = 'confirm'; }
        if (!$errors && owReqLimited('place_claim', 3)) $errors[] = ['Too many messages for now. Please try again in an hour.', 'رسائل كثيرة الآن. يرجى المحاولة بعد ساعة.'];
        $img = null;
        if (!$errors && $hasFile) {
            $r = owPlaceReadUpload($_FILES['photo']);
            if (!$r['ok']) { $errors[] = $r['err']; $bad[] = 'photo'; } else $img = $r['img'];
        }
        if (!$errors) {
            try {
                $file = '';
                if ($img) {   // redrawn (no hidden data), kept private until the admin approves it
                    $dir = __DIR__ . '/' . OW_CLAIM_DIR;
                    if (!is_dir($dir)) { @mkdir($dir, 0700, true); @file_put_contents($dir . '/.htaccess', "Require all denied\n"); }
                    $file = bin2hex(random_bytes(12));
                    if (!owPlaceSaveGd($img, $dir . '/' . $file, 1600, false)) throw new RuntimeException('photo save');
                }
                $lang = owLang() === 'ar' ? 'ar' : 'en';
                getDB()->prepare('INSERT INTO art_place_claims (place_kind, place_id, place_name, name, email, phone, message, photo_file, photo_credit, lang)
                                  VALUES (:k, :pid, :pn, :n, :e, :ph, :m, :f, :c, :l)')
                       ->execute([':k' => $pkind, ':pid' => $placeId, ':pn' => $nEn, ':n' => $v['name'], ':e' => $v['email'], ':ph' => $v['phone'], ':m' => $v['message'], ':f' => $file, ':c' => $v['photo_credit'], ':l' => $lang]);
                $cid = (int) getDB()->lastInsertId();
                owLog('place_claim', 'art_place_claim', $cid, $nEn, ['type' => 'guest', 'id' => null]);
                owReqCounted('place_claim');
                $body = "A new \"Is this your place?\" message from the Art Map.\r\n\r\n"
                      . "Place: $nEn" . ($nAr !== $nEn ? " · $nAr" : '') . "\r\n"
                      . "From: {$v['name']}\r\nEmail: {$v['email']}\r\n" . ($v['phone'] !== '' ? "Phone: {$v['phone']}\r\n" : '')
                      . 'Photo: ' . ($file !== '' ? 'yes' . ($v['photo_credit'] !== '' ? " (credit: {$v['photo_credit']})" : '') : 'no') . "\r\n\r\n"
                      . "Corrections:\r\n" . ($v['message'] !== '' ? $v['message'] : '(none)') . "\r\n\r\n"
                      . "Nothing shows on the site until you approve it:\r\n" . owSiteBase() . "/admin_art_map.php#claims\r\n";
                if (!owSendMail(owContactEmail(), 'Oweili - Art Map: message from ' . $nEn, $body)) error_log('place_claim: email to Oweili failed');
            } catch (Throwable $e) {
                error_log('place_claim.php: ' . $e->getMessage());
                $errors[] = ['Something went wrong on our side. Please try again in a moment.', 'حدث خطأ من جهتنا. يرجى المحاولة بعد قليل.'];
            }
            if (!$errors) { header('Location: ' . $self . '&sent=1'); exit; }
        }
    }
}
$sent = isset($_GET['sent']);
$badA = fn(string $k) => in_array($k, $bad, true) ? ' aria-invalid="true" class="is-bad"' : '';
?>
<!DOCTYPE html>
<html <?php owHtmlAttrs(); ?> data-title-en="Is this your place? · Oweili" data-title-ar="هل هذا مكانك؟ · أويلي">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= owLang() === 'ar' ? 'هل هذا مكانك؟ · أويلي' : 'Is this your place? · Oweili' ?></title>
<meta name="robots" content="noindex">
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/nav.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/discover.css') ?>">
<style>
.pc{max-width:620px;margin:0 auto;}
.pc h2{font-size:var(--fs-subtitle);margin:0 0 var(--space-xs);}
.pc .lead{margin:0 0 var(--space-md);color:var(--color-body);}
.pc .opt{font-weight:400;color:var(--color-body);}
.pc .tick{display:flex;gap:10px;align-items:flex-start;font-size:var(--fs-caption);margin:var(--space-sm) 0;}
.pc .tick input{margin-top:3px;width:18px;height:18px;flex-shrink:0;}
.pc .is-bad{border:2px solid var(--color-primary-dark) !important;}
.pc .trap{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%);white-space:nowrap;}
.pc{position:relative;}
.pc .acts{display:flex;flex-wrap:wrap;gap:var(--space-xs);}
html.dark-mode .ow-backdrop ~ * .pc.card{background:var(--color-card);}
</style>
</head>
<body class="ow">
<?= owStudioBackdrop('easels-corridor') ?>
<div class="bg-wrap" aria-hidden="true"></div>
<?php include __DIR__ . '/nav.php'; ?>
<main>
  <div class="d-wrap">
    <header class="d-head">
      <h1><?= owBi('Is this your place?', 'هل هذا مكانك؟') ?></h1>
      <p><?= owBi($nEn, $nAr) ?></p>
    </header>
    <div class="card pc">
    <?php if ($sent): ?>
      <h2><?= owBi('Thank you. We received your message.', 'شكراً لك. وصلتنا رسالتك.') ?></h2>
      <p class="lead"><?= owBi('Our team checks every photo and correction before it shows on the Art Map.', 'يراجع فريقنا كل صورة وكل تصحيح قبل ظهوره في وجهات الفن.') ?></p>
      <div class="acts"><a class="btn btn-primary" href="art_map.php"><?= owBi('Back to the Art Map', 'العودة إلى وجهات الفن') ?></a></div>
    <?php elseif (!$ready): ?>
      <p class="lead"><?= owBi('This form is being set up. Please write to us at ', 'هذا النموذج قيد الإعداد. يرجى مراسلتنا على ') ?><a href="mailto:<?= e(owContactEmail()) ?>"><?= e(owContactEmail()) ?></a></p>
    <?php else: ?>
      <h2><?= owBi('Send us your photo and details', 'أرسل لنا صورتك وبياناتك') ?></h2>
      <p class="lead"><?= owBi('Share a photo of your place or tell us what to correct. Our team checks everything before it shows on the Art Map.', 'شاركنا صورة لمكانك أو أخبرنا بما يجب تصحيحه. يراجع فريقنا كل شيء قبل ظهوره في وجهات الفن.') ?></p>
      <?php foreach ($errors as $er): ?><div class="alert alert-error" role="alert"><?= owBi($er[0], $er[1]) ?></div><?php endforeach; ?>
      <form method="post" enctype="multipart/form-data" action="<?= e($self) ?>" novalidate>
        <?= csrfField() ?>
        <div class="trap" aria-hidden="true"><label for="pc-web">Website</label><input id="pc-web" type="text" name="website" tabindex="-1" autocomplete="off"></div>
        <div class="field"><label for="pc-name"><?= owBi('Your name', 'اسمك') ?></label><input id="pc-name" type="text" name="name" maxlength="120" required autocomplete="name" value="<?= e($v['name']) ?>"<?= $badA('name') ?>></div>
        <div class="field"><label for="pc-email"><?= owBi('Email', 'البريد الإلكتروني') ?></label><input id="pc-email" type="email" name="email" maxlength="160" required autocomplete="email" dir="ltr" value="<?= e($v['email']) ?>"<?= $badA('email') ?>></div>
        <div class="field"><label for="pc-phone"><?= owBi('Phone', 'الهاتف') ?> <span class="opt"><?= owBi('(optional)', '(اختياري)') ?></span></label><input id="pc-phone" type="tel" name="phone" maxlength="30" autocomplete="tel" dir="ltr" value="<?= e($v['phone']) ?>"<?= $badA('phone') ?>></div>
        <div class="field"><label for="pc-photo"><?= owBi('A photo of your place', 'صورة لمكانك') ?> <span class="opt"><?= owBi('(optional; JPG, PNG or WebP, up to 10 MB)', '(اختياري؛ JPG أو PNG أو WebP، حتى 10 ميغابايت)') ?></span></label><input id="pc-photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp"<?= $badA('photo') ?>></div>
        <div class="field"><label for="pc-credit"><?= owBi('Photo credit', 'مصدر الصورة') ?> <span class="opt"><?= owBi('(optional, e.g. the photographer)', '(اختياري، مثل اسم المصوّر)') ?></span></label><input id="pc-credit" type="text" name="photo_credit" maxlength="200" value="<?= e($v['photo_credit']) ?>"></div>
        <div class="field"><label for="pc-msg"><?= owBi('Corrections or details', 'التصحيحات أو التفاصيل') ?></label><textarea id="pc-msg" name="message" rows="5" maxlength="3000"<?= $badA('message') ?>><?= e($v['message']) ?></textarea></div>
        <label class="tick"><input type="checkbox" name="confirm" value="1"<?= !empty($_POST['confirm']) ? ' checked' : '' ?><?= $badA('confirm') ?>><span><?= owBi('I represent this place, and I have the right to share this photo with Oweili.', 'أنا أمثّل هذا المكان، ويحق لي مشاركة هذه الصورة مع أويلي.') ?></span></label>
        <div class="acts"><button class="btn btn-primary" type="submit"><?= owBi('Send', 'أرسل') ?></button><a class="btn btn-secondary" href="art_map.php"><?= owBi('Back to the Art Map', 'العودة إلى وجهات الفن') ?></a></div>
      </form>
    <?php endif; ?>
    </div>
  </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
