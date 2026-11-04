<?php
/* =====================================================================
 *  commission.php — private commissions («الأعمال الخاصة»): a portrait or a
 *  personal memory painted from the buyer's own photos, in full confidence.
 *    commission.php                      the request form (photos go to the
 *                                        PRIVATE folder, never uploads/)
 *    commission.php?ref=C-2026-XXXXXX&k= the buyer's private page (the link
 *                                        is emailed once; only its SHA-256 is
 *                                        stored): steps, the price to accept,
 *                                        the draft to approve or change, the
 *                                        finished piece, "I received it"
 *  The buyer may agree IN WRITING that the finished piece can be shown
 *  (they type their name); without that it is never shown anywhere.
 *  Safety: security token, robot trap, 2 requests per visitor per hour.
 *  Tables: commissions, commission_files, users, activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/commission_lib.php';
require_once __DIR__ . '/request_lib.php';
$ART_TYPES = require __DIR__ . '/art_types.php';
if (isset($_GET['ref']) || isset($_GET['k'])) header('X-Robots-Tag: noindex, nofollow', false);   // the buyer's private page

$ready = owCommReady();
$SUBJ = owCommSubjects();
$STAT = owCommStatus();
$buyer = owReqSignedBuyer();
$errors = []; $flash = null;

/* ---------- The buyer's private page ---------- */
$ref = (string) ($_GET['ref'] ?? $_POST['ref'] ?? '');
$key = (string) ($_GET['k'] ?? $_POST['k'] ?? '');
$c = ($ready && $ref !== '') ? owCommByKey($ref, $key) : null;
$selfLink = $c ? 'commission.php?ref=' . rawurlencode($c['ref_no']) . '&k=' . rawurlencode($key) : '';
if ($ref !== '' && !$c) http_response_code(404);

if ($c && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $do = (string) ($_POST['do'] ?? '');
    $note = owReqClean('note', 1000, true);
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) { $flash = ['This page was open for a long time. Please try again.', 'كانت الصفحة مفتوحة لوقت طويل. يرجى المحاولة مرة أخرى.', true]; }
    elseif ($do === 'accept_quote' && $c['status'] === 'quoted') {
        if (!owCommSetStatus((int) $c['id'], 'quote_accepted', ['buyer_note' => ''], ['type' => 'guest', 'id' => null], '', [$c['status']])) { header('Location: ' . $selfLink); exit; };
        owCommAlert($c, 'The buyer accepted the price: arrange the payment, then press Mark as paid', 'وافق المشتري على السعر: رتّب الدفع ثم اضغط «سجّل الدفع»');
        owCommTellArtist($c, 'The buyer accepted your price · وافق المشتري على سعرك', 'Good news: the buyer accepted your price. Please wait for the payment: you will get an email the moment you can see the photos and start.', 'خبر جميل: وافق المشتري على سعرك. يرجى انتظار الدفع: ستصلك رسالة فور أن تستطيع رؤية الصور والبدء.');
        header('Location: ' . $selfLink . '&done=quote'); exit;
    } elseif ($do === 'other_quote' && $c['status'] === 'quoted') {
        if (!owCommSetStatus((int) $c['id'], 'assigned', ['buyer_note' => $note], ['type' => 'guest', 'id' => null], 'asked for another price', [$c['status']])) { header('Location: ' . $selfLink); exit; };
        owCommAlert($c, 'The buyer asked for another price', 'طلب المشتري سعراً آخر');
        owCommTellArtist($c, 'The buyer asks about the price · المشتري يسأل عن السعر', 'The buyer would like another price. Their note: ' . ($note ?: '—'), 'يرغب المشتري في سعر آخر. ملاحظته: ' . ($note ?: '—'));
        header('Location: ' . $selfLink . '&done=asked'); exit;
    } elseif ($do === 'approve_draft' && $c['status'] === 'draft_sent') {
        if (!owCommSetStatus((int) $c['id'], 'draft_approved', ['buyer_note' => ''], ['type' => 'guest', 'id' => null], '', [$c['status']])) { header('Location: ' . $selfLink); exit; };
        owCommAlert($c, 'The buyer approved the draft', 'وافق المشتري على المسودة');
        owCommTellArtist($c, 'The draft is approved · تمت الموافقة على المسودة', 'The buyer approved your draft. Please finish the piece and send the final photo.', 'وافق المشتري على مسودتك. يرجى إنهاء العمل وإرسال الصورة النهائية.');
        header('Location: ' . $selfLink . '&done=draft'); exit;
    } elseif ($do === 'change_draft' && $c['status'] === 'draft_sent') {
        if (mb_strlen($note) < 3) { $flash = ['Please tell the artist what to change.', 'يرجى إخبار الفنان بما يجب تغييره.', true]; }
        else {
            if (!owCommSetStatus((int) $c['id'], 'quote_accepted', ['buyer_note' => $note], ['type' => 'guest', 'id' => null], 'changes asked', [$c['status']])) { header('Location: ' . $selfLink); exit; };
            owCommAlert($c, 'The buyer asked for changes to the draft', 'طلب المشتري تعديلات على المسودة');
            owCommTellArtist($c, 'Changes asked for the draft · تعديلات مطلوبة على المسودة', 'The buyer asks for changes: ' . $note, 'يطلب المشتري تعديلات: ' . $note);
            header('Location: ' . $selfLink . '&done=asked'); exit;
        }
    } elseif ($do === 'received' && $c['status'] === 'final_sent') {
        if (!owCommSetStatus((int) $c['id'], 'delivered', ['delivered_at' => date('Y-m-d H:i:s')], ['type' => 'guest', 'id' => null], 'buyer confirmed receipt', [$c['status']])) { header('Location: ' . $selfLink); exit; };
        owCommAlert($c, 'The buyer received the commission', 'استلم المشتري العمل الخاص');
        header('Location: ' . $selfLink . '&done=received'); exit;
    }
    $c = owCommById((int) $c['id']);
}

/* ---------- A new request ---------- */
$f = ['subject' => 'portrait', 'brief' => '', 'art_style' => '', 'size_note' => '', 'budget_sar' => '', 'wanted_by' => '', 'may_show' => false, 'may_show_name' => '',
      'buyer_name' => $buyer ? (string) ($buyer['full_name_en'] ?: $buyer['full_name_ar']) : '', 'buyer_email' => $buyer ? (string) $buyer['email'] : '', 'buyer_phone' => $buyer ? (string) ($buyer['phone'] ?? '') : '',
      'agree' => false];
$newLink = '';
if (!$c && $ready && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['do'] ?? '') === 'request') {
    if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $errors[] = ['The photos were too large to send together. Please send fewer or smaller photos (10MB each at most).', 'الصور كبيرة جداً لإرسالها معاً. يرجى إرسال صور أقل أو أصغر (10 ميغابايت لكل صورة كحد أقصى).'];
    } elseif (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = ['This page was open for a long time. Please send it again.', 'كانت الصفحة مفتوحة لوقت طويل. يرجى الإرسال مرة أخرى.'];
    } elseif (trim((string) ($_POST['website'] ?? '')) !== '') {
        header('Location: commission.php?sent=1'); exit;
    } elseif (owReqLimited('commission_request', 2)) {
        $errors[] = ['You have sent several requests. Please try again in an hour.', 'أرسلت عدة طلبات. حاول مرة أخرى بعد ساعة.'];
    } else {
        $f['subject'] = (string) ($_POST['subject'] ?? '');
        $f['brief'] = owReqClean('brief', 3000, true);
        $f['art_style'] = (string) ($_POST['art_style'] ?? '');
        $f['size_note'] = owReqClean('size_note', 120);
        $f['budget_sar'] = owReqClean('budget_sar', 12);
        $f['wanted_by'] = owReqClean('wanted_by', 10);
        $f['may_show'] = !empty($_POST['may_show']);
        $f['may_show_name'] = owReqClean('may_show_name', 150);
        $f['agree'] = !empty($_POST['agree']);
        $b = owReqBuyer($errors);
        $f['buyer_name'] = $b['name']; $f['buyer_email'] = $b['email']; $f['buyer_phone'] = $b['phone'];
        if (!isset($SUBJ[$f['subject']])) $errors[] = ['Please choose what the artwork is of.', 'يرجى اختيار موضوع العمل.'];
        if (mb_strlen($f['brief']) < 10) $errors[] = ['Please describe the artwork you would like.', 'يرجى وصف العمل الذي تريده.'];
        if ($f['art_style'] !== '' && !isset($ART_TYPES[$f['art_style']])) $f['art_style'] = '';
        $budget = null;
        if ($f['budget_sar'] !== '') {
            $bv = str_replace([',', ' '], '', strtr($f['budget_sar'], ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']));
            if (!is_numeric($bv) || (float) $bv <= 0 || (float) $bv > 10000000) $errors[] = ['Please write the budget as a number in SAR.', 'يرجى كتابة الميزانية رقماً بالريال.']; else $budget = round((float) $bv, 2);
        }
        $wanted = null;
        if ($f['wanted_by'] !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $f['wanted_by']);
            if (!$d || $d->format('Y-m-d') !== $f['wanted_by'] || $f['wanted_by'] < date('Y-m-d', strtotime('+7 days'))) $errors[] = ['The date should be at least one week from today.', 'يجب أن يكون التاريخ بعد أسبوع على الأقل من اليوم.']; else $wanted = $f['wanted_by'];
        }
        if ($f['may_show'] && mb_strtolower(trim($f['may_show_name'])) !== mb_strtolower(trim($f['buyer_name']))) $errors[] = ['To agree that it may be shown, please type your full name exactly as above.', 'للموافقة على عرضه، يرجى كتابة اسمك الكامل تماماً كما في الأعلى.'];
        if (!$f['agree']) $errors[] = ['Please confirm you have the right to share these photos.', 'يرجى تأكيد أن لك حق مشاركة هذه الصور.'];
        $photos = [];
        if (!empty($_FILES['photos']['name']) && is_array($_FILES['photos']['name'])) {
            foreach ($_FILES['photos']['name'] as $i => $n) {
                if (($_FILES['photos']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
                $photos[] = ['name' => $n, 'type' => $_FILES['photos']['type'][$i], 'tmp_name' => $_FILES['photos']['tmp_name'][$i], 'error' => $_FILES['photos']['error'][$i], 'size' => $_FILES['photos']['size'][$i]];
            }
        }
        if (!$photos) $errors[] = ['Please add at least one reference photo.', 'يرجى إضافة صورة مرجعية واحدة على الأقل.'];
        if (count($photos) > OW_COMM_MAX_REFS) $errors[] = ['Please send ' . OW_COMM_MAX_REFS . ' photos at most.', 'يرجى إرسال ' . OW_COMM_MAX_REFS . ' صور كحد أقصى.'];
        if (!$errors) {
            $db = getDB();
            try {
                $refNo = owCommNewRef();
                $k = bin2hex(random_bytes(24));
                $db->beginTransaction();
                $db->prepare('INSERT INTO commissions (ref_no, access_hash, user_id, buyer_name, buyer_email, buyer_phone, lang, subject, brief, art_style, size_note, budget_sar, wanted_by, may_show, may_show_text, may_show_at)
                              VALUES (:r, :h, :u, :bn, :be, :bp, :l, :s, :br, :st, :sz, :bg, :wb, :ms, :mt, :ma)')
                   ->execute([':r' => $refNo, ':h' => hash('sha256', $k), ':u' => $buyer ? (int) $buyer['id'] : null, ':bn' => $f['buyer_name'], ':be' => $f['buyer_email'], ':bp' => $f['buyer_phone'],
                              ':l' => ($_POST['lang'] ?? '') === 'ar' ? 'ar' : 'en', ':s' => $f['subject'], ':br' => $f['brief'], ':st' => $f['art_style'], ':sz' => $f['size_note'], ':bg' => $budget, ':wb' => $wanted,
                              ':ms' => $f['may_show'] ? 1 : 0, ':mt' => $f['may_show'] ? 'I agree that Oweili and the artist may show the finished artwork in public. Signed: ' . $f['may_show_name'] : '', ':ma' => $f['may_show'] ? date('Y-m-d H:i:s') : null]);
                $cid = (int) $db->lastInsertId();
                foreach ($photos as $i => $p) {
                    $r = owCommStore($p, $cid, 'reference', 'buyer');
                    if (!$r['ok']) throw new DomainException(json_encode(owUploadErrBi($r['err'], 'Photo ' . ($i + 1), 'الصورة ' . ($i + 1))));
                }
                $db->commit();
                owReqCounted('commission_request');
                owLog('commission_request', 'commission', $cid, $f['subject'] . ', ' . count($photos) . ' photo(s)', $buyer ? ['type' => 'buyer', 'id' => (int) $buyer['id']] : ['type' => 'guest', 'id' => null]);
                $nc = owCommById($cid);
                $newLink = 'https://' . owCommHost() . '/commission.php?ref=' . rawurlencode($refNo) . '&k=' . $k;
                owCommAlert($nc, 'New private commission request: ' . $SUBJ[$f['subject']][0], 'طلب عمل خاص جديد: ' . $SUBJ[$f['subject']][1]);
                if (is_file(__DIR__ . '/bashro_send.php')) { require_once __DIR__ . '/bashro_send.php'; owBashroCommission($cid); }
                owCommTellBuyer($nc, 'Your private commission request · طلب عملك الخاص',
                    'Thank you, ' . $f['buyer_name'] . '. We received your request and your photos. They are kept private: only the Oweili team and the one artist we choose (after they sign our confidentiality agreement) can see them. Keep this private link to follow every step:',
                    'شكراً يا ' . $f['buyer_name'] . '. وصلنا طلبك وصورك. صورك محفوظة بسرّية: لا يراها إلا فريق أويلي والفنان الذي نختاره (بعد توقيعه اتفاقية السرّية). احتفظ بهذا الرابط الخاص لتتابع كل خطوة:', $newLink);
                $_SESSION['ow_comm_new'] = $newLink;
                header('Location: commission.php?sent=1'); exit;
            } catch (Throwable $e) {
                if ($db->inTransaction()) $db->rollBack();
                if (isset($cid)) foreach (owCommFiles((int) $cid) as $ff) owCommDeleteFile($ff, 'failed');
                $m = $e instanceof DomainException ? json_decode($e->getMessage(), true) : null;
                $errors[] = is_array($m) ? $m : ['Something went wrong. Nothing was saved. Please try again.', 'حدث خطأ. لم يُحفظ شيء. يرجى المحاولة مرة أخرى.'];
                if (!$m) error_log('commission save: ' . $e->getMessage());
            }
        }
    }
}
$sentLink = isset($_GET['sent']) ? (string) ($_SESSION['ow_comm_new'] ?? '') : '';
$v = fn(string $k) => e((string) $f[$k]);
$DONE = ['quote' => ['Thank you. The artist is starting your piece.', 'شكراً لك. بدأ الفنان العمل على قطعتك.'], 'draft' => ['Thank you. The artist is finishing your piece.', 'شكراً لك. الفنان ينهي قطعتك الآن.'],
         'asked' => ['Sent. The artist will see your note.', 'تم الإرسال. سيرى الفنان ملاحظتك.'], 'received' => ['Thank you. We hope you love it.', 'شكراً لك. نتمنى أن تحبها.']];
if (!$flash && $c && isset($DONE[$_GET['done'] ?? ''])) $flash = $DONE[$_GET['done']] + [2 => false];
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" data-title-en="Private commissions · Oweili" data-title-ar="الأعمال الخاصة · أويلي">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require_once __DIR__ . '/seo_lib.php'; owSeo(['en' => 'Private commissions', 'ar' => 'الأعمال الخاصة', 'desc_en' => 'A portrait or a memory, painted by an Oweili artist from your own photos. Your photos stay confidential.', 'desc_ar' => 'بورتريه أو ذكرى، يرسمها فنان من أويلي من صورك. تبقى صورك سرّية.', 'path' => 'commission.php'] + ($ref !== '' ? ['noindex' => true] : [])); ?>
<meta name="referrer" content="no-referrer">
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/nav.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/discover.css') ?>">
<script>document.documentElement.classList.add('js')</script><script defer src="<?= owAsset('assets/js/discover.js') ?>"></script>
<style>
/* Private commissions — layout only; colors, buttons, fields and cards come from oweili.css */
.c-trust{display:grid;grid-template-columns:repeat(3,1fr);gap:var(--space-sm);margin:0 0 var(--space-xl);}
.c-trust .card{padding:var(--space-md);}
.c-trust svg{width:30px;height:30px;fill:none;stroke:var(--color-dark);stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round;margin-bottom:var(--space-xs);}
.c-trust h2{font-size:var(--fs-body);margin:0 0 4px;}
.c-trust p{margin:0;font-size:var(--fs-caption);color:var(--color-body);}
.c-form,.c-view{max-width:880px;margin:0 auto;padding:var(--space-xl);}
.c-form h2,.c-view h2{font-size:var(--fs-subtitle);margin:var(--space-lg) 0 var(--space-sm);}
.c-form h2:first-of-type,.c-view h2:first-of-type{margin-top:0;}
.c-two{display:grid;grid-template-columns:1fr 1fr;gap:0 var(--space-sm);}
.c-check{display:flex;gap:var(--space-xs);align-items:flex-start;margin:0 0 var(--space-sm);font-size:15px;color:var(--color-dark);}
.c-check input{margin-top:4px;}
.c-check small{display:block;color:var(--color-body);font-size:var(--fs-caption);}
.c-note{font-size:var(--fs-caption);color:var(--color-body);}
.c-box{background:var(--color-tint-blue);border:1px solid rgba(var(--rgb-accent),.18);border-radius:var(--radius-card);padding:var(--space-md);margin:var(--space-sm) 0;}
.trap{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);clip-path:inset(50%);white-space:nowrap;}
.c-foot{display:flex;flex-wrap:wrap;gap:var(--space-sm);align-items:center;justify-content:space-between;border-top:1px solid var(--color-border);padding-top:var(--space-md);margin-top:var(--space-md);}
.c-steps{list-style:none;margin:0 0 var(--space-md);padding:0;display:grid;gap:6px;}
.c-steps li{display:flex;gap:var(--space-xs);align-items:center;font-size:15px;color:var(--color-body);}
.c-steps li i{width:22px;height:22px;flex-shrink:0;border-radius:50%;border:2px solid var(--color-border-strong);background:var(--color-surface);}
.c-steps li.done{color:var(--color-dark);}
.c-steps li.done i{background:var(--color-dark);border-color:var(--color-dark);}
.c-steps li.now{color:var(--color-dark);font-weight:700;}
.c-steps li.now i{border-color:var(--color-primary);background:var(--color-tint-pink);}
.c-pics{display:grid;grid-template-columns:repeat(3,1fr);gap:var(--space-xs);margin:var(--space-xs) 0 var(--space-sm);}
.c-pics a{display:block;border-radius:10px;overflow:hidden;background:var(--color-frame);aspect-ratio:1;}
.c-pics img{width:100%;height:100%;object-fit:cover;display:block;}
.c-big img{width:100%;max-height:70vh;object-fit:contain;border-radius:12px;background:var(--color-frame);display:block;}
.c-acts{display:flex;gap:var(--space-xs);flex-wrap:wrap;align-items:flex-start;margin-top:var(--space-sm);}
.c-acts form{margin:0;}
.c-acts details{flex:1 1 280px;}
.c-link{word-break:break-all;font-size:var(--fs-caption);background:var(--color-surface-2);border:1px solid var(--color-border);border-radius:8px;padding:8px 10px;margin:var(--space-xs) 0;}
.c-done{max-width:640px;margin:0 auto;padding:var(--space-xl);text-align:center;}
@media(max-width:640px){.c-trust{grid-template-columns:1fr;}.c-form,.c-view{padding:var(--space-md);}.c-two{grid-template-columns:1fr;}.c-pics{grid-template-columns:repeat(2,1fr);}}
</style>
</head>
<body class="ow">
<div class="bg-wrap" aria-hidden="true"></div>
<div class="veil" aria-hidden="true"></div>
<?php include __DIR__ . '/nav.php'; ?>
<main>
  <div class="d-wrap">
    <header class="d-head reveal">
      <span class="d-badge"><i aria-hidden="true"></i><?= owBi('Private commissions', 'الأعمال الخاصة') ?></span>
      <h1><?= $c ? owBi('Your private commission', 'عملك الخاص') : owBi('Your photo, painted for you', 'صورتك، مرسومة لك') ?></h1>
      <p><?= $c ? owBi('Number ' . $c['ref_no'] . '. Only you, the Oweili team and your artist can see this page.', 'الرقم ' . $c['ref_no'] . '. لا يرى هذه الصفحة إلا أنت وفريق أويلي وفنانك.')
                : owBi('A portrait of someone you love, a family moment, a pet, a place you miss. An Oweili artist paints it from your photos, and your photos stay private.', 'بورتريه لمن تحب، لحظة عائلية، حيوان أليف، مكان تشتاق إليه. يرسمه فنان من أويلي من صورك، وتبقى صورك خاصة.') ?></p>
    </header>

<?php if ($sentLink !== '' || isset($_GET['sent'])): ?>
    <div class="card c-done reveal" role="status">
      <h2><?= owBi('Thank you! Your request is with us.', 'شكراً! وصلنا طلبك.') ?></h2>
      <p><?= owBi('We will choose the right artist and send you a price. Nothing is charged yet. We emailed you a private link to follow every step.', 'سنختار الفنان المناسب ونرسل لك السعر. لا يُدفع أي مبلغ الآن. أرسلنا لك رابطاً خاصاً لتتابع كل خطوة.') ?></p>
      <?php if ($sentLink !== ''): ?><p class="c-note"><?= owBi('Your private link (keep it to yourself):', 'رابطك الخاص (احتفظ به لنفسك):') ?></p><p class="c-link" dir="ltr"><a href="<?= e($sentLink) ?>"><?= e($sentLink) ?></a></p><?php unset($_SESSION['ow_comm_new']); endif; ?>
      <p><a class="btn btn-primary" href="marketplace.php"><?= owBi('Back to the Gallery', 'العودة إلى المعرض') ?></a></p>
    </div>

<?php elseif ($c): $sIdx = array_search($c['status'], OW_COMM_STEPS, true);
  $refs = owCommFiles((int) $c['id'], 'reference'); $drafts = owCommFiles((int) $c['id'], 'draft'); $finals = owCommFiles((int) $c['id'], 'final');
  $fileUrl = fn(array $ff) => 'commission_file.php?id=' . (int) $ff['id'] . '&ref=' . rawurlencode($c['ref_no']) . '&k=' . rawurlencode($key); ?>
    <div class="card c-view reveal">
      <?php if ($flash): ?><div class="alert <?= $flash[2] ? 'alert-error' : 'alert-ok' ?>" role="status"><?= owBi($flash[0], $flash[1]) ?></div><?php endif; ?>
      <h2><?= owBi('Where it is now', 'أين وصل الآن') ?></h2>
      <?php if ($c['status'] === 'cancelled'): ?>
        <p><?= owBi('This commission was cancelled. If you have a question, write to us.', 'أُلغي هذا الطلب. إن كان لديك سؤال فراسلنا.') ?> <a href="mailto:<?= e(owContactEmail()) ?>" dir="ltr"><?= e(owContactEmail()) ?></a></p>
      <?php else: ?>
      <ol class="c-steps">
        <?php foreach (OW_COMM_STEPS as $i => $sk): ?><li class="<?= $i < $sIdx ? 'done' : ($i === $sIdx ? 'now' : '') ?>"><i aria-hidden="true"></i><?= owBi($STAT[$sk][0], $STAT[$sk][1]) ?></li><?php endforeach; ?>
      </ol>
      <?php endif; ?>

      <?php $cPaid = owCommPaid((int) $c['id']); $cLeft = owCommDaysToExpire($c);
      if (!$cPaid && in_array($c['status'], ['quote_accepted', 'draft_sent', 'draft_approved', 'final_sent'], true)): ?>
        <div class="c-box">
          <h2 style="margin-top:0"><?= owBi('Next: the payment', 'الخطوة التالية: الدفع') ?></h2>
          <p style="margin:0"><?= owBi('The Oweili team will contact you about paying. Your artist sees your photos and starts only after the payment is confirmed.', 'سيتواصل معك فريق أويلي بخصوص الدفع. لا يرى فنانك صورك ولا يبدأ إلا بعد تأكيد الدفع.') ?></p>
          <?php if ($cLeft !== null): ?><p class="c-note" style="margin:var(--space-xs) 0 0"><?= owBi('If it is not paid within ' . $cLeft . ' days, we delete your reference photos to protect your privacy. Your request stays.', 'إن لم يُدفع خلال ' . $cLeft . ' يوماً نحذف صورك المرجعية حفاظاً على خصوصيتك. ويبقى طلبك.') ?></p><?php endif; ?>
        </div>
      <?php elseif ($cPaid && $c['status'] === 'quote_accepted'): ?>
        <div class="c-box"><p style="margin:0"><?= owBi('Payment received. Your artist is working on your piece.', 'وصلنا الدفع. فنانك يعمل على قطعتك.') ?></p></div>
      <?php endif; ?>
      <?php if ($c['status'] === 'quoted'): ?>
        <div class="c-box">
          <h2 style="margin-top:0"><?= owBi('The price for your piece', 'سعر قطعتك') ?></h2>
          <p style="font-size:var(--fs-card);font-weight:700;color:var(--color-dark);margin:0"><?= number_format((float) $c['quote_sar']) ?> <?= owBi('SAR', 'ر.س') ?></p>
          <?php if ($c['quote_days']): ?><p class="c-note"><?= owBi('Ready in about ' . (int) $c['quote_days'] . ' days.', 'جاهزة خلال ' . (int) $c['quote_days'] . ' يوماً تقريباً.') ?></p><?php endif; ?>
          <?php if ($c['quote_note'] !== ''): ?><p dir="auto"><?= nl2br(e((string) $c['quote_note'])) ?></p><?php endif; ?>
          <p class="c-note"><?= owBi('No payment on the site yet: the Oweili team will contact you about paying.', 'لا دفع في الموقع بعد: سيتواصل معك فريق أويلي بخصوص الدفع.') ?></p>
          <div class="c-acts">
            <form method="post" action="<?= e($selfLink) ?>"><?= csrfField() ?><input type="hidden" name="do" value="accept_quote"><button class="btn btn-primary"><?= owBi('Accept the price', 'أوافق على السعر') ?></button></form>
            <details><summary class="btn btn-secondary"><?= owBi('Ask for another price', 'اطلب سعراً آخر') ?></summary>
              <form method="post" action="<?= e($selfLink) ?>"><?= csrfField() ?><input type="hidden" name="do" value="other_quote">
                <div class="field"><label for="n1"><?= owBi('Your note (optional)', 'ملاحظتك (اختياري)') ?></label><textarea id="n1" name="note" rows="2" maxlength="1000" dir="auto"></textarea></div>
                <button class="btn btn-secondary btn-sm"><?= owBi('Send', 'أرسل') ?></button></form></details>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($drafts && in_array($c['status'], ['draft_sent', 'draft_approved', 'final_sent', 'delivered'], true)): $dl = end($drafts); ?>
        <h2><?= owBi('The draft', 'المسودة') ?></h2>
        <div class="c-big"><img src="<?= e($fileUrl($dl)) ?>" alt="<?= e('Draft · المسودة') ?>"></div>
        <?php if ($c['status'] === 'draft_sent'): ?>
        <div class="c-acts">
          <form method="post" action="<?= e($selfLink) ?>"><?= csrfField() ?><input type="hidden" name="do" value="approve_draft"><button class="btn btn-primary"><?= owBi('I love it, please finish', 'أعجبني، أكمِل') ?></button></form>
          <details><summary class="btn btn-secondary"><?= owBi('Ask for changes', 'اطلب تعديلات') ?></summary>
            <form method="post" action="<?= e($selfLink) ?>"><?= csrfField() ?><input type="hidden" name="do" value="change_draft">
              <div class="field"><label for="n2"><?= owBi('What should change?', 'ما الذي يجب تغييره؟') ?></label><textarea id="n2" name="note" rows="3" maxlength="1000" required dir="auto"></textarea></div>
              <button class="btn btn-secondary btn-sm"><?= owBi('Send to the artist', 'أرسل إلى الفنان') ?></button></form></details>
        </div>
        <?php endif; ?>
      <?php endif; ?>

      <?php if ($finals && in_array($c['status'], ['final_sent', 'delivered'], true)): $fl = end($finals); ?>
        <h2><?= owBi('Your finished piece', 'قطعتك المكتملة') ?></h2>
        <div class="c-big"><img src="<?= e($fileUrl($fl)) ?>" alt="<?= e('Finished artwork · العمل المكتمل') ?>"></div>
        <?php if ($c['status'] === 'final_sent'): ?>
          <p class="c-note"><?= owBi('The Oweili team will arrange the delivery with you. When it arrives, please tell us here.', 'سيرتب فريق أويلي التسليم معك. عند وصوله أخبرنا هنا.') ?></p>
          <form method="post" action="<?= e($selfLink) ?>"><?= csrfField() ?><input type="hidden" name="do" value="received"><button class="btn btn-primary"><?= owBi('I received it', 'استلمته') ?></button></form>
        <?php endif; ?>
      <?php endif; ?>

      <h2><?= owBi('Your request', 'طلبك') ?></h2>
      <p dir="auto"><b><?= owBi($SUBJ[$c['subject']][0] ?? '', $SUBJ[$c['subject']][1] ?? '') ?></b><br><?= nl2br(e((string) $c['brief'])) ?></p>
      <?php if ($refs): ?>
        <p class="c-note"><?= owBi('Your reference photos (private):', 'صورك المرجعية (خاصة):') ?></p>
        <div class="c-pics"><?php foreach ($refs as $rf): ?><a href="<?= e($fileUrl($rf)) ?>" target="_blank" rel="noopener noreferrer"><img src="<?= e($fileUrl($rf)) ?>" alt="<?= e('Reference photo · صورة مرجعية') ?>"></a><?php endforeach; ?></div>
      <?php elseif ($c['photos_deleted_at']): ?><p class="c-note"><?= owBi('Your reference photos were deleted ' . OW_COMM_KEEP_DAYS . ' days after delivery, as promised.', 'حُذفت صورك المرجعية بعد ' . OW_COMM_KEEP_DAYS . ' يوماً من التسليم، كما وعدنا.') ?></p><?php endif; ?>
      <p class="c-note"><?= (int) $c['may_show'] === 1 ? owBi('You agreed in writing that the finished piece may be shown in public.', 'وافقت كتابياً على أن تُعرض القطعة المكتملة علناً.') : owBi('The finished piece will never be shown in public.', 'لن تُعرض القطعة المكتملة علناً أبداً.') ?></p>
    </div>

<?php elseif ($ref !== ''): ?>
    <div class="card c-done reveal"><h2><?= owBi('This private link does not work', 'هذا الرابط الخاص لا يعمل') ?></h2>
      <p><?= owBi('Please open the link from your email exactly as it is. If it still does not work, write to us.', 'يرجى فتح الرابط من بريدك كما هو. إن لم يعمل فراسلنا.') ?> <a href="mailto:<?= e(owContactEmail()) ?>" dir="ltr"><?= e(owContactEmail()) ?></a></p></div>

<?php elseif (!$ready): ?>
    <div class="card c-done reveal"><h2><?= owBi('Private commissions open very soon.', 'الأعمال الخاصة تفتح قريباً جداً.') ?></h2><p><a href="mailto:<?= e(owContactEmail()) ?>" dir="ltr"><?= e(owContactEmail()) ?></a></p></div>

<?php else: ?>
    <div class="c-trust">
      <div class="card reveal"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><h2><?= owBi('Your photos stay private', 'صورك تبقى خاصة') ?></h2><p><?= owBi('Kept in a locked place, never in the Gallery. Deleted ' . OW_COMM_KEEP_DAYS . ' days after delivery.', 'تُحفظ في مكان مقفل، ولا تظهر في المعرض أبداً. تُحذف بعد ' . OW_COMM_KEEP_DAYS . ' يوماً من التسليم.') ?></p></div>
      <div class="card reveal"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 14l2 2 4-4"/></svg><h2><?= owBi('One artist, under agreement', 'فنان واحد، باتفاقية') ?></h2><p><?= owBi('Only the artist we choose sees them, after signing our confidentiality agreement.', 'لا يراها إلا الفنان الذي نختاره، بعد توقيعه اتفاقية السرّية.') ?></p></div>
      <div class="card reveal"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg><h2><?= owBi('You approve every step', 'توافق على كل خطوة') ?></h2><p><?= owBi('The price first, then a draft. Nothing is shown in public unless you agree in writing.', 'السعر أولاً، ثم المسودة. لا يُعرض شيء علناً إلا بموافقتك كتابياً.') ?></p></div>
    </div>

    <form class="card c-form reveal" method="post" action="commission.php" enctype="multipart/form-data" novalidate>
      <?= csrfField() ?><input type="hidden" name="do" value="request"><input type="hidden" name="lang" value="en" id="c-lang">
      <div class="trap" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
      <?php if ($errors): ?><div class="alert alert-error" role="alert"><?= owBi('Please check these:', 'يرجى مراجعة ما يلي:') ?><ul><?php foreach ($errors as [$en, $ar]): ?><li><?= owBi($en, $ar) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

      <h2><?= owBi('1. What should it be?', '1. ماذا تريد؟') ?></h2>
      <div class="c-two">
        <div class="field"><label for="c-sub"><?= owBi('It is of', 'موضوعه') ?></label>
          <select id="c-sub" name="subject"><?php foreach ($SUBJ as $sk => $sl): ?><option value="<?= $sk ?>"<?= $f['subject'] === $sk ? ' selected' : '' ?>><?= e($sl[0] . ' · ' . $sl[1]) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="c-style"><?= owBi('Style (optional)', 'الأسلوب (اختياري)') ?></label>
          <select id="c-style" name="art_style"><option value=""><?= e('Let the artist suggest · دع الفنان يقترح') ?></option>
            <?php foreach ($ART_TYPES as $tk => $tv): if (!empty($tv['legacy'])) continue; ?><option value="<?= e($tk) ?>"<?= $f['art_style'] === $tk ? ' selected' : '' ?>><?= e($tv['en'] . ' · ' . $tv['ar']) ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="field"><label for="c-brief"><?= owBi('Tell us about it', 'حدّثنا عنه') ?></label>
        <textarea id="c-brief" name="brief" rows="4" maxlength="3000" required dir="auto" placeholder="<?= e('My grandmother in her garden, warm colors, for her 80th birthday · جدّتي في حديقتها، بألوان دافئة، لعيد ميلادها الثمانين') ?>"><?= $v('brief') ?></textarea></div>
      <div class="c-two">
        <div class="field"><label for="c-size"><?= owBi('Size (optional)', 'المقاس (اختياري)') ?></label><input id="c-size" name="size_note" maxlength="120" dir="auto" value="<?= $v('size_note') ?>" placeholder="50 × 70 cm"></div>
        <div class="field"><label for="c-budget"><?= owBi('Budget in SAR (optional)', 'الميزانية بالريال (اختياري)') ?></label><input id="c-budget" name="budget_sar" inputmode="decimal" maxlength="12" dir="ltr" value="<?= $v('budget_sar') ?>"></div>
        <div class="field"><label for="c-when"><?= owBi('Needed by (optional)', 'مطلوب قبل (اختياري)') ?></label><input id="c-when" type="date" name="wanted_by" min="<?= date('Y-m-d', strtotime('+7 days')) ?>" value="<?= $v('wanted_by') ?>"></div>
      </div>

      <h2><?= owBi('2. Your reference photos', '2. صورك المرجعية') ?></h2>
      <div class="field"><label for="c-photos"><?= owBi('1 to ' . OW_COMM_MAX_REFS . ' photos (JPG, PNG or WEBP, 10MB each at most)', 'من 1 إلى ' . OW_COMM_MAX_REFS . ' صور (JPG أو PNG أو WEBP، بحد أقصى 10 ميغابايت لكل صورة)') ?></label>
        <input id="c-photos" type="file" name="photos[]" multiple required accept="image/jpeg,image/png,image/webp"></div>
      <p class="c-note"><?= owBi('Location and other hidden data are removed from every photo. They are kept in a private place visitors can never open.', 'تُزال بيانات الموقع وغيرها من البيانات المخفية من كل صورة. وتُحفظ في مكان خاص لا يفتحه الزوار أبداً.') ?></p>
      <label class="c-check"><input type="checkbox" name="agree" value="1"<?= $f['agree'] ? ' checked' : '' ?>><span><?= owBi('I took these photos or have the right to share them, and the people in them agree.', 'التقطتُ هذه الصور أو يحق لي مشاركتها، والأشخاص الظاهرون فيها موافقون.') ?></span></label>

      <h2><?= owBi('3. Showing the finished piece', '3. عرض القطعة المكتملة') ?></h2>
      <div class="c-box">
        <p style="margin-top:0"><?= owBi('By default, your commission is never shown: not in the Gallery, not on the artist’s profile, nowhere.', 'بشكل افتراضي لا يُعرض عملك الخاص أبداً: لا في المعرض، ولا في ملف الفنان، ولا في أي مكان.') ?></p>
        <label class="c-check"><input type="checkbox" name="may_show" value="1"<?= $f['may_show'] ? ' checked' : '' ?> id="c-show"><span><?= owBi('I agree that Oweili and the artist may show the finished artwork in public.', 'أوافق على أن يعرض أويلي والفنان العمل المكتمل علناً.') ?><small><?= owBi('Optional. To agree, type your full name below as your written signature.', 'اختياري. للموافقة اكتب اسمك الكامل أدناه توقيعاً كتابياً.') ?></small></span></label>
        <div class="field" style="margin:0"><label for="c-sign"><?= owBi('Your full name (signature)', 'اسمك الكامل (توقيع)') ?></label><input id="c-sign" name="may_show_name" maxlength="150" dir="auto" value="<?= $v('may_show_name') ?>"></div>
      </div>

      <h2><?= owBi('4. Your details', '4. بياناتك') ?></h2>
      <div class="c-two">
        <div class="field"><label for="c-bn"><?= owBi('Your full name', 'اسمك الكامل') ?></label><input id="c-bn" name="buyer_name" maxlength="150" required autocomplete="name" dir="auto" value="<?= $v('buyer_name') ?>"></div>
        <div class="field"><label for="c-bp"><?= owBi('Your phone', 'هاتفك') ?></label><input id="c-bp" name="buyer_phone" type="tel" maxlength="30" required autocomplete="tel" dir="ltr" value="<?= $v('buyer_phone') ?>"></div>
        <div class="field"><label for="c-be"><?= owBi('Your email', 'بريدك الإلكتروني') ?></label><input id="c-be" name="buyer_email" type="email" maxlength="190" required autocomplete="email" dir="ltr" value="<?= $v('buyer_email') ?>"></div>
      </div>
      <p class="c-note"><?= owBi('The artist never sees your name, phone or email: Oweili is the middle party.', 'لا يرى الفنان اسمك أو هاتفك أو بريدك: أويلي هو الوسيط.') ?> <a href="privacy.php" target="_blank" rel="noopener"><?= owBi('Privacy Policy', 'سياسة الخصوصية') ?></a></p>
      <div class="c-foot"><p class="c-note" style="margin:0"><?= owBi('No payment now. You approve the price first.', 'لا دفع الآن. توافق على السعر أولاً.') ?></p>
        <button class="btn btn-primary" type="submit"><?= owBi('Send my private request', 'أرسل طلبي الخاص') ?></button></div>
    </form>
<?php endif; ?>
  </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
<script>
(function(){ var f = document.querySelector('.c-form'), l = document.getElementById('c-lang');
  if (f && l) f.addEventListener('submit', function(){ l.value = document.documentElement.getAttribute('dir') === 'rtl' ? 'ar' : 'en'; }); })();
</script>
</body>
</html>
