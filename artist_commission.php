<?php
/* =====================================================================
 *  artist_commission.php — one private commission, for its assigned artist
 *  (linked from My Studio → Commissions). artist_commission.php?id=N
 *  Before anything private is shown, the artist must accept the
 *  confidentiality agreement inside the site (typed name + tick); the
 *  acceptance is saved with the date (commission_nda). Until then only the
 *  kind of artwork and the brief's first words are shown, never a photo.
 *  After it: the reference photos (through commission_file.php), the
 *  buyer's request (never their name, phone or email: Oweili is the middle
 *  party), and the artist's steps: send a price → send a draft → send the
 *  final photo. The admin arranges delivery and money by hand.
 *  Tables: commissions, commission_files, commission_nda, users, activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/commission_lib.php';
header('X-Robots-Tag: noindex', false);

if (!isLoggedIn()) { header('Location: login.php?next=' . rawurlencode('artist_commission.php?id=' . (int) ($_GET['id'] ?? 0))); exit; }
$me = currentUser();
if (!$me || ($me['role'] ?? '') !== 'artist') { header('Location: ' . dashboardUrl($me['role'] ?? '')); exit; }
if (!empty($me['is_banned'])) { http_response_code(404); exit('Not found.'); }   // a suspended artist loses access to private commissions
$uid = (int) $me['id'];
$ART_TYPES = require __DIR__ . '/art_types.php';
$SUBJ = owCommSubjects();
$c = owCommReady() ? owCommById((int) ($_GET['id'] ?? $_POST['id'] ?? 0)) : null;
if (!$c || (int) $c['artist_id'] !== $uid || $c['status'] === 'cancelled') { http_response_code(404); }
$ok = $c && (int) $c['artist_id'] === $uid && $c['status'] !== 'cancelled';
$nda = $ok && owCommNdaOk((int) $c['id'], $uid);
$paid = $ok && owCommPaid((int) $c['id']);   // no photos and no work before the payment is confirmed (Admin → Mark as paid)
$errors = []; $msg = null;
$actor = ['type' => 'artist', 'id' => $uid];
$txt = fn(string $k, int $max) => mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) ($_POST[$k] ?? ''))), 0, $max);

if ($ok && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $do = (string) ($_POST['do'] ?? '');
    if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) $errors[] = ['The photo was too large. 10MB at most.', 'الصورة كبيرة جداً. 10 ميغابايت كحد أقصى.'];
    elseif (!verifyCsrf($_POST['csrf_token'] ?? null)) $errors[] = ['This page was open for a long time. Please try again.', 'كانت الصفحة مفتوحة لوقت طويل. يرجى المحاولة مرة أخرى.'];
    elseif ($do === 'nda' && !$nda) {
        $typed = $txt('typed_name', 150);
        $real = array_filter([(string) $me['full_name_en'], (string) $me['full_name_ar'], (string) $me['artist_name']]);
        $match = false; foreach ($real as $r) if ($r !== '' && mb_strtolower(trim($r)) === mb_strtolower($typed)) $match = true;
        if (empty($_POST['accept'])) $errors[] = ['Please tick the box to accept the agreement.', 'يرجى وضع علامة لقبول الاتفاقية.'];
        if (!$match) $errors[] = ['Please type your full name exactly as in your account.', 'يرجى كتابة اسمك الكامل تماماً كما في حسابك.'];
        if (!$errors) {
            getDB()->prepare('INSERT IGNORE INTO commission_nda (commission_id, artist_id, version, typed_name, accepted_at, ip_code) VALUES (:c, :a, :v, :t, NOW(), :ip)')
                   ->execute([':c' => (int) $c['id'], ':a' => $uid, ':v' => OW_COMM_NDA_VERSION, ':t' => $typed, ':ip' => substr(owIpHash(), 0, 16)]);
            owLog('commission_nda_accept', 'commission', (int) $c['id'], 'version ' . OW_COMM_NDA_VERSION, $actor);
            header('Location: artist_commission.php?id=' . (int) $c['id'] . '&m=nda'); exit;
        }
    } elseif ($nda && $do === 'quote' && $c['status'] === 'assigned') {
        $price = (float) str_replace([',', ' '], '', strtr((string) ($_POST['quote_sar'] ?? ''), ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']));
        $days = (int) ($_POST['quote_days'] ?? 0);
        $note = $txt('quote_note', 1000);
        if ($price <= 0 || $price > 10000000) $errors[] = ['Please write your price in SAR.', 'يرجى كتابة سعرك بالريال.'];
        if ($days < 1 || $days > 365) $errors[] = ['Please write how many days you need (1 to 365).', 'يرجى كتابة عدد الأيام التي تحتاجها (من 1 إلى 365).'];
        if (!$errors) {
            if (!owCommSetStatus((int) $c['id'], 'quoted', ['quote_sar' => round($price, 2), 'quote_days' => $days, 'quote_note' => $note, 'quoted_at' => date('Y-m-d H:i:s')], $actor, '', [$c['status']], $uid)) { header('Location: artist_commission.php?id=' . (int) $c['id']); exit; };
            owCommAlert($c, 'The artist sent a price: ' . number_format($price) . ' SAR', 'أرسل الفنان سعراً: ' . number_format($price) . ' ر.س');
            owCommTellBuyer($c, 'Your price is ready · سعرك جاهز', 'The artist sent the price for your private commission: ' . number_format($price) . ' SAR, ready in about ' . $days . ' days. Please accept it or ask for another price on your private page.',
                'أرسل الفنان سعر عملك الخاص: ' . number_format($price) . ' ر.س، جاهز خلال ' . $days . ' يوماً تقريباً. يرجى الموافقة عليه أو طلب سعر آخر من صفحتك الخاصة.');
            header('Location: artist_commission.php?id=' . (int) $c['id'] . '&m=quote'); exit;
        }
    } elseif ($nda && $paid && in_array($do, ['draft', 'final'], true) && (($do === 'draft' && $c['status'] === 'quote_accepted') || ($do === 'final' && $c['status'] === 'draft_approved'))) {
        $r = owCommStore($_FILES['photo'] ?? [], (int) $c['id'], $do, 'artist');
        if (!$r['ok']) $errors[] = owUploadErrBi($r['err'] === 'none' ? 'none' : $r['err'], 'Photo', 'الصورة');
        else {
            $to = $do === 'draft' ? 'draft_sent' : 'final_sent';
            if (!owCommSetStatus((int) $c['id'], $to, ['buyer_note' => ''], $actor, '', [$c['status']], $uid)) { header('Location: artist_commission.php?id=' . (int) $c['id']); exit; };
            if ($do === 'draft') {
                owCommAlert($c, 'The artist sent a draft', 'أرسل الفنان مسودة');
                owCommTellBuyer($c, 'Your draft is ready · مسودتك جاهزة', 'The artist sent a draft of your piece. Please approve it, or ask for changes, on your private page.', 'أرسل الفنان مسودة قطعتك. يرجى الموافقة عليها أو طلب تعديلات من صفحتك الخاصة.');
            } else {
                owCommAlert($c, 'The artist finished the piece: please arrange delivery', 'أنهى الفنان القطعة: يرجى ترتيب التسليم');
                owCommTellBuyer($c, 'Your piece is finished · قطعتك اكتملت', 'Your private commission is finished. You can see it on your private page. The Oweili team will contact you about the delivery.', 'اكتمل عملك الخاص. تستطيع رؤيته في صفحتك الخاصة. سيتواصل معك فريق أويلي بخصوص التسليم.');
            }
            header('Location: artist_commission.php?id=' . (int) $c['id'] . '&m=' . $do); exit;
        }
    }
}
$MSG = ['nda' => ['Thank you. The agreement is saved. You can now see the photos.', 'شكراً لك. حُفظت الاتفاقية. يمكنك الآن رؤية الصور.'], 'quote' => ['Your price is sent to the buyer.', 'أُرسل سعرك إلى المشتري.'],
        'draft' => ['Your draft is sent to the buyer.', 'أُرسلت مسودتك إلى المشتري.'], 'final' => ['Your final photo is sent. The Oweili team arranges the delivery.', 'أُرسلت صورتك النهائية. يرتب فريق أويلي التسليم.']];
$msg = $MSG[$_GET['m'] ?? ''] ?? null;
$NDA = owCommNdaText();
$files = $nda ? owCommFiles((int) $c['id']) : [];
if (!$paid) $files = array_values(array_filter($files, fn($ff) => $ff['kind'] !== 'reference'));   // reference photos stay closed until paid
$byKind = ['reference' => [], 'draft' => [], 'final' => []];
foreach ($files as $ff) $byKind[$ff['kind']][] = $ff;
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<meta name="referrer" content="no-referrer">
<title>Private commission · عمل خاص · Oweili</title>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
.page{position:relative;z-index:10;}
.ac{max-width:860px;margin:var(--space-md) auto 0;padding:var(--space-lg);}
.ac h1{font-size:var(--fs-section);margin:0 0 4px;}
.ac h2{font-size:var(--fs-subtitle);margin:var(--space-lg) 0 var(--space-sm);}
.note{font-size:var(--fs-caption);color:var(--color-body);}
.nda{background:var(--color-tint-blue);border:1px solid rgba(var(--rgb-accent),.18);border-radius:var(--radius-card);padding:var(--space-md);}
.nda ol{margin:0 0 var(--space-sm);padding-inline-start:20px;}
.nda li{margin-bottom:6px;}
.check{display:flex;gap:var(--space-xs);align-items:flex-start;font-size:15px;margin:var(--space-xs) 0;}
.check input{margin-top:4px;}
.pics{display:grid;grid-template-columns:repeat(3,1fr);gap:var(--space-xs);}
.pics a{display:block;aspect-ratio:1;border-radius:10px;overflow:hidden;background:var(--color-frame);}
.pics img{width:100%;height:100%;object-fit:cover;display:block;}
.two{display:grid;grid-template-columns:1fr 1fr;gap:0 var(--space-sm);}
.kv{display:grid;grid-template-columns:max-content 1fr;gap:4px var(--space-sm);}
.kv dt{font-weight:700;color:var(--color-dark);}
.kv dd{margin:0;overflow-wrap:anywhere;}
.pillx{display:inline-block;font-size:var(--fs-min);font-weight:700;padding:2px 10px;border-radius:var(--radius-pill);border:1px solid var(--color-border-strong);}
.pillx.warn{background:rgba(var(--rgb-primary),.18);border-color:rgba(var(--rgb-primary),.45);}
.pillx.on{background:rgba(var(--rgb-accent),.1);}
.w-reason{font-size:var(--fs-caption);color:var(--color-dark);background:var(--color-tint-pink);border:1px solid rgba(var(--rgb-primary),.3);border-radius:var(--radius-input);padding:8px 10px;}
@media(max-width:640px){.ac{padding:var(--space-md);}.pics{grid-template-columns:repeat(2,1fr);}.two,.kv{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="bg-wrap"></div>
<div class="overlay"></div>
<?php include __DIR__ . '/nav.php'; ?>
<main class="page dash role-artist dash-wrap">
<?php if (!$ok): ?>
  <div class="card ac"><h1><?= owBi('Not found', 'غير موجود') ?></h1><p><?= owBi('This commission is not assigned to you, or it was cancelled.', 'هذا الطلب غير مُسند إليك، أو أُلغي.') ?></p>
    <a class="btn btn-secondary" href="artist_studio.php?tab=commissions"><?= owBi('Back to My Studio', 'العودة إلى مرسمي') ?></a></div>
<?php else: [$sE, $sA] = owCommStatus()[$c['status']]; ?>
  <div class="card ac">
    <p><a href="artist_studio.php?tab=commissions">← <?= owBi('My commissions', 'أعمالي الخاصة') ?></a></p>
    <h1><?= owBi('Private commission ' . $c['ref_no'], 'عمل خاص ' . $c['ref_no']) ?></h1>
    <p><?= owCommPill((string) $c['status']) ?> · <?= owBi($SUBJ[$c['subject']][0] ?? '', $SUBJ[$c['subject']][1] ?? '') ?></p>
    <?php if ($msg): ?><div class="alert alert-ok" role="status"><?= owBi($msg[0], $msg[1]) ?></div><?php endif; ?>
    <?php if ($errors): ?><div class="alert alert-error" role="alert"><ul style="margin:0;padding-inline-start:20px"><?php foreach ($errors as [$en, $ar]): ?><li><?= owBi($en, $ar) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

  <?php if (!$nda): ?>
    <p><?= owBi('The Oweili team chose you for a private commission. Before you can see the buyer’s photos and request, please read and accept the confidentiality agreement.', 'اختارك فريق أويلي لعمل خاص. قبل أن ترى صور المشتري وطلبه، يرجى قراءة اتفاقية السرّية وقبولها.') ?></p>
    <form class="nda" method="post" action="artist_commission.php?id=<?= (int) $c['id'] ?>">
      <?= csrfField() ?><input type="hidden" name="do" value="nda"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
      <h2 style="margin-top:0"><?= owBi('Confidentiality agreement', 'اتفاقية السرّية') ?></h2>
      <ol class="bi-en" dir="ltr"><?php foreach ($NDA['en'] as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ol>
      <ol class="bi-ar" dir="rtl"><?php foreach ($NDA['ar'] as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ol>
      <label class="check"><input type="checkbox" name="accept" value="1"> <span><?= owBi('I have read this agreement and I accept it.', 'قرأت هذه الاتفاقية وأقبلها.') ?></span></label>
      <div class="field"><label for="tn"><?= owBi('Type your full name as your signature', 'اكتب اسمك الكامل توقيعاً') ?></label><input id="tn" name="typed_name" maxlength="150" required dir="auto" autocomplete="off"></div>
      <p class="note"><?= owBi('We save your acceptance with the date and time.', 'نحفظ قبولك مع التاريخ والوقت.') ?></p>
      <button class="btn btn-primary" type="submit"><?= owBi('Accept and continue', 'أقبل وأتابع') ?></button>
    </form>
  <?php else: ?>
    <h2><?= owBi('The request', 'الطلب') ?></h2>
    <dl class="kv">
      <dt><?= owBi('About it', 'التفاصيل') ?></dt><dd dir="auto"><?= nl2br(e((string) $c['brief'])) ?></dd>
      <?php if ($c['art_style'] !== '' && isset($ART_TYPES[$c['art_style']])): ?><dt><?= owBi('Style', 'الأسلوب') ?></dt><dd><?= owBi($ART_TYPES[$c['art_style']]['en'], $ART_TYPES[$c['art_style']]['ar']) ?></dd><?php endif; ?>
      <?php if ($c['size_note'] !== ''): ?><dt><?= owBi('Size', 'المقاس') ?></dt><dd dir="auto"><?= e((string) $c['size_note']) ?></dd><?php endif; ?>
      <?php if ($c['budget_sar'] !== null): ?><dt><?= owBi('Buyer’s budget', 'ميزانية المشتري') ?></dt><dd><?= number_format((float) $c['budget_sar']) ?> <?= owBi('SAR', 'ر.س') ?></dd><?php endif; ?>
      <?php if ($c['wanted_by']): [$we, $wa] = owDateBi($c['wanted_by']); ?><dt><?= owBi('Needed by', 'مطلوب قبل') ?></dt><dd><?= owBi($we, $wa) ?></dd><?php endif; ?>
      <dt><?= owBi('May it be shown?', 'هل يُعرض؟') ?></dt><dd><?= (int) $c['may_show'] === 1 ? owBi('The buyer agreed in writing. Oweili will tell you before anything is shown.', 'وافق المشتري كتابياً. سيبلغك أويلي قبل عرض أي شيء.') : owBi('No. Never show it anywhere.', 'لا. لا تعرضه في أي مكان.') ?></dd>
    </dl>
    <?php if ($c['buyer_note'] !== ''): ?><p class="w-reason"><?= owBi('The buyer’s note: ', 'ملاحظة المشتري: ') ?><?= e((string) $c['buyer_note']) ?></p><?php endif; ?>
    <h2><?= owBi('Reference photos (private)', 'الصور المرجعية (خاصة)') ?></h2>
    <?php if (!$paid && !$c['photos_deleted_at']): ?><p class="w-reason"><?= owBi('The photos open for you once the buyer has paid and the Oweili team confirms it. Please do not start the piece before that. You can already send your price.', 'تُفتح لك الصور بعد أن يدفع المشتري ويؤكد فريق أويلي ذلك. يرجى عدم البدء في القطعة قبل ذلك. يمكنك إرسال سعرك الآن.') ?></p>
    <?php elseif ($byKind['reference']): ?><div class="pics"><?php foreach ($byKind['reference'] as $rf): ?><a href="commission_file.php?id=<?= (int) $rf['id'] ?>" target="_blank" rel="noopener noreferrer"><img src="commission_file.php?id=<?= (int) $rf['id'] ?>" alt="<?= e('Reference photo · صورة مرجعية') ?>"></a><?php endforeach; ?></div>
    <?php else: ?><p class="note"><?= owBi('The reference photos were deleted.', 'حُذفت الصور المرجعية.') ?></p><?php endif; ?>
    <?php if ($paid): ?><p class="note"><?= owBi('Please do not download or share them. They are deleted ' . OW_COMM_KEEP_DAYS . ' days after delivery.', 'يرجى عدم تنزيلها أو مشاركتها. تُحذف بعد ' . OW_COMM_KEEP_DAYS . ' يوماً من التسليم.') ?></p><?php endif; ?>

    <?php if ($c['status'] === 'assigned'): ?>
      <h2><?= owBi('Your price', 'سعرك') ?></h2>
      <form method="post" action="artist_commission.php?id=<?= (int) $c['id'] ?>"><?= csrfField() ?><input type="hidden" name="do" value="quote"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
        <div class="two">
          <div class="field"><label for="qp"><?= owBi('Price in SAR (before VAT; Oweili keeps 15%)', 'السعر بالريال (قبل الضريبة؛ يحتفظ أويلي بـ15%)') ?></label><input id="qp" name="quote_sar" inputmode="decimal" dir="ltr" required></div>
          <div class="field"><label for="qd"><?= owBi('Days you need', 'الأيام التي تحتاجها') ?></label><input id="qd" name="quote_days" type="number" min="1" max="365" dir="ltr" required></div>
        </div>
        <div class="field"><label for="qn"><?= owBi('A short note for the buyer (optional)', 'ملاحظة قصيرة للمشتري (اختياري)') ?></label><textarea id="qn" name="quote_note" rows="2" maxlength="1000" dir="auto"></textarea></div>
        <button class="btn btn-primary" type="submit"><?= owBi('Send my price', 'أرسل سعري') ?></button></form>
    <?php elseif ($c['status'] === 'quoted'): ?>
      <p><?= owBi('Your price (' . number_format((float) $c['quote_sar']) . ' SAR) is waiting for the buyer.', 'سعرك (' . number_format((float) $c['quote_sar']) . ' ر.س) بانتظار المشتري.') ?></p>
    <?php elseif (in_array($c['status'], ['quote_accepted', 'draft_approved'], true) && !$paid): ?>
      <p><?= owBi('The buyer accepted your price. Waiting for the payment: you will get an email when you can start.', 'وافق المشتري على سعرك. بانتظار الدفع: ستصلك رسالة عندما يمكنك البدء.') ?></p>
    <?php elseif (in_array($c['status'], ['quote_accepted', 'draft_approved'], true)): $isDraft = $c['status'] === 'quote_accepted'; ?>
      <h2><?= $isDraft ? owBi('Send a draft', 'أرسل مسودة') : owBi('Send the final photo', 'أرسل الصورة النهائية') ?></h2>
      <p class="note"><?= $isDraft ? owBi('A photo of the work in progress, so the buyer can approve or ask for changes.', 'صورة للعمل وهو قيد التنفيذ، ليوافق المشتري أو يطلب تعديلات.') : owBi('A clear photo of the finished piece. The Oweili team arranges the delivery.', 'صورة واضحة للقطعة المكتملة. يرتب فريق أويلي التسليم.') ?></p>
      <form method="post" action="artist_commission.php?id=<?= (int) $c['id'] ?>" enctype="multipart/form-data"><?= csrfField() ?><input type="hidden" name="do" value="<?= $isDraft ? 'draft' : 'final' ?>"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
        <div class="field"><label for="ph"><?= owBi('Photo (JPG, PNG or WEBP, 10MB at most)', 'الصورة (JPG أو PNG أو WEBP، بحد أقصى 10 ميغابايت)') ?></label><input id="ph" type="file" name="photo" accept="image/jpeg,image/png,image/webp" capture="environment" required></div>
        <button class="btn btn-primary" type="submit"><?= owBi('Send', 'أرسل') ?></button></form>
    <?php elseif (in_array($c['status'], ['draft_sent', 'final_sent'], true)): ?>
      <p><?= $c['status'] === 'draft_sent' ? owBi('Your draft is waiting for the buyer.', 'مسودتك بانتظار المشتري.') : owBi('Your final photo is sent. The Oweili team arranges the delivery.', 'أُرسلت صورتك النهائية. يرتب فريق أويلي التسليم.') ?></p>
    <?php elseif ($c['status'] === 'delivered'): ?>
      <p><?= owBi('Delivered. Thank you for your beautiful work.', 'تم التسليم. شكراً لعملك الجميل.') ?></p>
    <?php endif; ?>
    <?php foreach (['draft' => ['Your drafts', 'مسوداتك'], 'final' => ['Final photo', 'الصورة النهائية']] as $k => $l): if (!$byKind[$k]) continue; ?>
      <h2><?= owBi($l[0], $l[1]) ?></h2><div class="pics"><?php foreach ($byKind[$k] as $df): ?><a href="commission_file.php?id=<?= (int) $df['id'] ?>" target="_blank" rel="noopener noreferrer"><img src="commission_file.php?id=<?= (int) $df['id'] ?>" alt="<?= e($l[0] . ' · ' . $l[1]) ?>"></a><?php endforeach; ?></div>
    <?php endforeach; ?>
  <?php endif; ?>
  </div>
<?php endif; ?>
</main>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
