<?php
/* =====================================================================
 *  gifts.php — Oweili Gifts: "Give a gift that lasts"
 *  («أهدِ ما يبقى»). For everyone (guests and buyers).
 *  The buyer picks an occasion (wedding, graduation, new baby, Eid,
 *  Ramadan, thank you, Teacher's Day), a ready artwork from the Gallery or
 *  a custom one, gift wrapping, a card message, the delivery date, the
 *  recipient's name, city and address, and "Surprise" (the recipient never
 *  sees the price). For now it is a request: it is saved for the admin
 *  (admin_gifts.php), the admin gets an email alert and follows
 *  up by hand. No payment.
 *  ?artwork=N chooses a ready artwork, ?occasion=eid an occasion.
 *  Safety: security token, hidden robot trap, 3 requests per visitor per
 *  hour, only artworks a visitor can see in the Gallery.
 *  Tables: gift_requests (night_update.sql), artworks, users, activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/studio_lib.php'; require_once __DIR__ . '/page_cache.php'; owPublicPage();   // public page: Back opens at once, early load on touch (page_cache.php)
require_once __DIR__ . '/artwork_lib.php';
require_once __DIR__ . '/request_lib.php';
require_once __DIR__ . '/inquiry_lib.php';   // owInquiryArtwork: only artworks visitors can see

$OCC   = owGiftOccasions();
$ready = owHasTable('gift_requests');
$buyer = owReqSignedBuyer();
$errors = [];
$done = isset($_GET['sent']);
$f = [
    'occasion' => isset($OCC[$_GET['occasion'] ?? '']) ? (string) $_GET['occasion'] : '',
    'mode' => 'ready', 'artwork_id' => (int) ($_GET['artwork'] ?? 0), 'custom_brief' => '', 'budget_sar' => '',
    'wrapping' => true, 'surprise' => true, 'card_message' => '', 'delivery_date' => '',
    'recipient_name' => '', 'recipient_phone' => '', 'recipient_city' => '', 'recipient_address' => '',
    'buyer_name' => $buyer ? (string) ($buyer['full_name_en'] ?: $buyer['full_name_ar']) : '', 'buyer_email' => $buyer ? (string) $buyer['email'] : '',
    'buyer_phone' => $buyer ? (string) ($buyer['phone'] ?? '') : '',
];
$minDate = date('Y-m-d', strtotime('+3 days'));

if ($ready && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = ['This page was open for a long time. Please send it again.', 'كانت الصفحة مفتوحة لوقت طويل. يرجى الإرسال مرة أخرى.'];
    } elseif (trim((string) ($_POST['website'] ?? '')) !== '') {
        header('Location: gifts.php?sent=1'); exit;        // a robot filled the hidden field
    } elseif (owReqLimited('gift_request', 3)) {
        $errors[] = ['You have sent several requests. Please try again in an hour.', 'أرسلت عدة طلبات. حاول مرة أخرى بعد ساعة.'];
    } else {
        $f['occasion'] = (string) ($_POST['occasion'] ?? '');
        $f['mode'] = ($_POST['mode'] ?? '') === 'custom' ? 'custom' : 'ready';
        $f['artwork_id'] = (int) ($_POST['artwork_id'] ?? 0);
        $f['custom_brief'] = owReqClean('custom_brief', 2000, true);
        $f['budget_sar'] = owReqClean('budget_sar', 12);
        $f['wrapping'] = !empty($_POST['wrapping']);
        $f['surprise'] = !empty($_POST['surprise']);
        $f['card_message'] = owReqClean('card_message', 300, true);
        $f['delivery_date'] = owReqClean('delivery_date', 10);
        foreach (['recipient_name' => 150, 'recipient_phone' => 30, 'recipient_city' => 100] as $k => $m) $f[$k] = owReqClean($k, $m);
        $f['recipient_address'] = owReqClean('recipient_address', 600, true);
        $b = owReqBuyer($errors);
        $f['buyer_name'] = $b['name']; $f['buyer_email'] = $b['email']; $f['buyer_phone'] = $b['phone'];

        if (!isset($OCC[$f['occasion']])) $errors[] = ['Please choose the occasion.', 'يرجى اختيار المناسبة.'];
        $aw = null;
        if ($f['mode'] === 'ready') {
            $aw = owInquiryArtwork($f['artwork_id']);
            if (!$aw) $errors[] = ['Please choose an artwork, or ask for a custom one.', 'يرجى اختيار عمل فني، أو اطلب عملاً خاصاً.'];
        } else {
            if (mb_strlen($f['custom_brief']) < 10) $errors[] = ['Please tell us a little about the custom artwork you have in mind.', 'يرجى أن تخبرنا قليلاً عن العمل الخاص الذي تتخيله.'];
        }
        $budget = null;
        if ($f['budget_sar'] !== '') {
            $bv = str_replace([',', ' '], '', strtr($f['budget_sar'], ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']));
            if (!is_numeric($bv) || (float) $bv <= 0 || (float) $bv > 10000000) $errors[] = ['Please write the budget as a number in SAR.', 'يرجى كتابة الميزانية رقماً بالريال.'];
            else $budget = round((float) $bv, 2);
        }
        $d = DateTime::createFromFormat('Y-m-d', $f['delivery_date']);
        if (!$d || $d->format('Y-m-d') !== $f['delivery_date']) $errors[] = ['Please choose the delivery date.', 'يرجى اختيار تاريخ التوصيل.'];
        elseif ($f['delivery_date'] < $minDate) $errors[] = ['Please choose a delivery date at least 3 days from today.', 'يرجى اختيار تاريخ توصيل بعد 3 أيام على الأقل من اليوم.'];
        elseif ($f['delivery_date'] > date('Y-m-d', strtotime('+1 year'))) $errors[] = ['Please choose a delivery date within the next year.', 'يرجى اختيار تاريخ توصيل خلال السنة القادمة.'];
        if (mb_strlen($f['recipient_name']) < 2) $errors[] = ['Please write the recipient’s name.', 'يرجى كتابة اسم المُهدى إليه.'];
        if ($f['recipient_phone'] !== '' && !preg_match('/^\+?[0-9][0-9 \-()]{6,29}$/', $f['recipient_phone'])) $errors[] = ['Please check the recipient’s phone number.', 'يرجى مراجعة رقم هاتف المُهدى إليه.'];
        if (mb_strlen($f['recipient_city']) < 2) $errors[] = ['Please write the recipient’s city.', 'يرجى كتابة مدينة المُهدى إليه.'];
        if (mb_strlen($f['recipient_address']) < 8) $errors[] = ['Please write the full delivery address.', 'يرجى كتابة عنوان التوصيل كاملاً.'];
        if (preg_match('~<\s*/?\s*(script|iframe)~i', $f['card_message'] . $f['custom_brief'] . $f['recipient_address'])) $errors[] = ['Please write plain text only.', 'يرجى كتابة نص عادي فقط.'];

        if (!$errors) {
            try {
                $lang = ($_POST['lang'] ?? '') === 'ar' ? 'ar' : 'en';
                getDB()->prepare('INSERT INTO gift_requests (user_id, occasion, mode, artwork_id, custom_brief, budget_sar, wrapping, card_message, delivery_date, surprise,
                                  recipient_name, recipient_phone, recipient_city, recipient_address, buyer_name, buyer_email, buyer_phone, lang)
                                  VALUES (:u, :o, :m, :a, :cb, :bg, :w, :cm, :dd, :s, :rn, :rp, :rc, :ra, :bn, :be, :bp, :l)')
                       ->execute([':u' => $buyer ? (int) $buyer['id'] : null, ':o' => $f['occasion'], ':m' => $f['mode'], ':a' => $aw ? (int) $aw['id'] : null,
                                  ':cb' => $f['mode'] === 'custom' ? $f['custom_brief'] : '', ':bg' => $budget, ':w' => $f['wrapping'] ? 1 : 0, ':cm' => $f['card_message'],
                                  ':dd' => $f['delivery_date'], ':s' => $f['surprise'] ? 1 : 0, ':rn' => $f['recipient_name'], ':rp' => $f['recipient_phone'],
                                  ':rc' => $f['recipient_city'], ':ra' => $f['recipient_address'], ':bn' => $f['buyer_name'], ':be' => $f['buyer_email'], ':bp' => $f['buyer_phone'], ':l' => $lang]);
                $gid = (int) getDB()->lastInsertId();
                owLog('gift_request', 'gift', $gid, $f['occasion'] . ' ' . $f['mode'], $buyer ? ['type' => 'buyer', 'id' => (int) $buyer['id']] : ['type' => 'guest', 'id' => null]);
                owReqCounted('gift_request');
                [$oEn, $oAr] = [$OCC[$f['occasion']]['en'], $OCC[$f['occasion']]['ar']];
                $what = $aw ? '"' . $aw['title_en'] . '"' : 'a custom artwork';
                try {
                    require_once __DIR__ . '/alert_lib.php';
                    owAdminAlert('gift', 'New gift request (' . $oEn . '): ' . $what . ' for ' . $f['recipient_city'],
                                 'طلب هدية جديد (' . $oAr . '): ' . ($aw ? '«' . $aw['title_ar'] . '»' : 'عمل خاص') . ' إلى ' . $f['recipient_city'], 'admin_gifts.php?id=' . $gid);
                } catch (Throwable $e) { error_log('gift alert: ' . $e->getMessage()); }
                if (is_file(__DIR__ . '/bashro_send.php')) { require_once __DIR__ . '/bashro_send.php'; owBashroGift($gid); }
                owReqTellBuyer($f['buyer_email'], 'We received your gift request · وصلنا طلب هديتك',
                    'Thank you, ' . $f['buyer_name'] . '. We received your ' . $oEn . ' gift request for ' . $f['recipient_name'] . '. The Oweili team will contact you soon to confirm the details. Nothing is charged yet.',
                    'شكراً يا ' . $f['buyer_name'] . '. وصلنا طلب هدية ' . $oAr . ' إلى ' . $f['recipient_name'] . '. سيتواصل معك فريق أويلي قريباً لتأكيد التفاصيل. لا يُدفع أي مبلغ الآن.');
                header('Location: gifts.php?sent=1'); exit;
            } catch (Throwable $e) {
                error_log('gifts save: ' . $e->getMessage());
                $errors[] = ['Something went wrong. Nothing was saved. Please try again.', 'حدث خطأ. لم يُحفظ شيء. يرجى المحاولة مرة أخرى.'];
            }
        }
    }
}

/* Ready artworks to choose from: the chosen one first, then the newest */
$choices = [];
try {
    $chosen = $f['artwork_id'] > 0 ? owInquiryArtwork($f['artwork_id']) : null;
    $st = getDB()->query("SELECT a.id, a.title_en, a.title_ar, a.price, a.image_url, " . owArtistNameSql('u') . " FROM artworks a JOIN users u ON u.id = a.artist_id
                          WHERE a.is_approved = 1 AND a.status = 'available' AND u.is_banned = 0" . owNotDraftSql('a') . " ORDER BY a.created_at DESC, a.id DESC LIMIT 12");
    $choices = $st->fetchAll();
    if ($chosen && !in_array((int) $chosen['id'], array_map(fn($r) => (int) $r['id'], $choices), true)) {
        $one = getDB()->prepare('SELECT a.id, a.title_en, a.title_ar, a.price, a.image_url, ' . owArtistNameSql('u') . ' FROM artworks a JOIN users u ON u.id = a.artist_id WHERE a.id = :id');
        $one->execute([':id' => (int) $chosen['id']]);
        if ($r = $one->fetch()) array_unshift($choices, $r);
    }
    if (!$chosen && $f['artwork_id'] > 0) $f['artwork_id'] = 0;
} catch (Throwable $e) { $choices = []; }
if (!$choices && ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') $f['mode'] = 'custom';
$v = fn(string $k) => e((string) $f[$k]);
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" data-title-en="Oweili Gifts · Give a gift that lasts" data-title-ar="هدايا أويلي · أهدِ ما يبقى">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require_once __DIR__ . '/seo_lib.php'; owSeo(['en' => 'Oweili Gifts: give a gift that lasts', 'ar' => 'هدايا أويلي: أهدِ ما يبقى', 'desc_en' => 'A handmade artwork stays for years. Choose an original piece for a wedding, graduation, new baby or Eid, and we wrap and deliver it.', 'desc_ar' => 'عمل فني مصنوع باليد يبقى سنوات. اختر عملاً أصلياً لزواج أو تخرّج أو مولود أو عيد، ونحن نغلّفه ونوصله.', 'path' => 'gifts.php']); ?>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/nav.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/discover.css') ?>">
<script>document.documentElement.classList.add('js')</script><script defer src="<?= owAsset('assets/js/discover.js') ?>"></script>
<style>
/* Oweili Gifts — layout only; colors, buttons, fields and cards come from oweili.css */
.g-steps{display:grid;grid-template-columns:repeat(3,1fr);gap:var(--space-sm);margin:0 0 var(--space-xl);}
.g-step{padding:var(--space-md);}
.g-step b{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;background:var(--color-tint-pink);color:var(--color-dark);margin-bottom:var(--space-xs);}
.g-step h2{font-size:var(--fs-body);margin:0 0 4px;}
.g-step p{margin:0;font-size:var(--fs-caption);color:var(--color-body);}
.g-form{max-width:920px;margin:0 auto;padding:var(--space-xl);}
.g-form h2{font-size:var(--fs-subtitle);margin:var(--space-lg) 0 var(--space-sm);}
.g-form h2:first-of-type{margin-top:0;}
.g-occ{display:grid;grid-template-columns:repeat(7,1fr);gap:var(--space-xs);}
.g-occ label,.g-mode label{position:relative;display:flex;flex-direction:column;align-items:center;gap:6px;padding:var(--space-sm) 6px;border:1px solid var(--color-border-strong);border-radius:var(--radius-card);background:var(--color-surface);cursor:pointer;text-align:center;font-size:var(--fs-label);font-weight:700;color:var(--color-dark);transition:border-color var(--ease),background var(--ease);}
.g-occ svg{width:28px;height:28px;fill:none;stroke:currentColor;stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round;}
.g-occ input,.g-mode input,.g-art input{position:absolute;opacity:0;width:1px;height:1px;}
.g-occ label:has(input:checked),.g-mode label:has(input:checked),.g-art label:has(input:checked){border-color:var(--color-dark);background:var(--color-tint-blue);box-shadow:0 0 0 1px var(--color-dark) inset;}
.g-occ label:has(input:focus-visible),.g-mode label:has(input:focus-visible),.g-art label:has(input:focus-visible){outline:2px solid var(--color-accent);outline-offset:2px;}
.g-mode{display:grid;grid-template-columns:1fr 1fr;gap:var(--space-sm);}
.g-mode label{align-items:flex-start;text-align:start;padding:var(--space-md);}
.g-mode span{font-weight:400;color:var(--color-body);font-size:var(--fs-caption);}
.g-art{display:grid;grid-template-columns:repeat(4,1fr);gap:var(--space-xs);margin-top:var(--space-sm);}
.g-art label{position:relative;display:flex;flex-direction:column;gap:4px;padding:6px;border:1px solid var(--color-border);border-radius:12px;background:var(--color-surface);cursor:pointer;font-size:var(--fs-caption);}
.g-art .aw-img{border-radius:8px;}
.g-art b{color:var(--color-dark);font-size:13px;line-height:1.35;}
.g-more{display:inline-block;margin-top:var(--space-xs);font-size:var(--fs-caption);font-weight:700;color:var(--color-accent);}
.g-two{display:grid;grid-template-columns:1fr 1fr;gap:0 var(--space-sm);}
.g-check{display:flex;gap:var(--space-xs);align-items:flex-start;margin:0 0 var(--space-sm);font-size:15px;color:var(--color-dark);}
.g-check input{margin-top:4px;}
.g-check small{display:block;color:var(--color-body);font-size:var(--fs-caption);}
.g-note{font-size:var(--fs-caption);color:var(--color-body);}
.g-foot{display:flex;flex-wrap:wrap;gap:var(--space-sm);align-items:center;justify-content:space-between;border-top:1px solid var(--color-border);padding-top:var(--space-md);margin-top:var(--space-md);}
.trap{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);clip-path:inset(50%);white-space:nowrap;}
.g-done{max-width:620px;margin:0 auto;text-align:center;padding:var(--space-xl);}
.g-done svg{width:56px;height:56px;fill:none;stroke:var(--color-dark);stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round;margin-bottom:var(--space-sm);}
.g-done h2{font-size:var(--fs-card);margin:0 0 var(--space-xs);}
.g-done .acts{display:flex;gap:var(--space-xs);justify-content:center;flex-wrap:wrap;margin-top:var(--space-md);}
.g-custom-link{margin-top:var(--space-xs);}
[hidden]{display:none !important;}
@media(max-width:1024px){.g-occ{grid-template-columns:repeat(4,1fr);}.g-art{grid-template-columns:repeat(3,1fr);}}
@media(max-width:640px){.g-steps{grid-template-columns:1fr;}.g-form{padding:var(--space-md);}.g-occ{grid-template-columns:repeat(2,1fr);}.g-mode,.g-two{grid-template-columns:1fr;}.g-art{grid-template-columns:repeat(2,1fr);}}
</style>
</head>
<body class="ow">
<div class="bg-wrap" aria-hidden="true"></div>
<div class="veil" aria-hidden="true"></div>
<?php include __DIR__ . '/nav.php'; ?>

<main>
  <div class="d-wrap">
    <header class="d-head ow-photo-head reveal">
      <?= owStudioPhoto('pink-palette', true) ?>
      <span class="d-badge"><i aria-hidden="true"></i><?= owBi('Oweili Gifts', 'هدايا أويلي') ?></span>
      <h1><?= owBi('Give a gift that lasts', 'أهدِ ما يبقى') ?></h1>
      <p><?= owBi('A handmade artwork stays on the wall for years, and the memory stays with it. Choose a piece, and we wrap it and deliver it with your card.', 'عمل فني مصنوع باليد يبقى على الجدار سنوات، وتبقى معه الذكرى. اختر قطعة، ونحن نغلّفها ونوصلها مع بطاقتك.') ?></p>
    </header>

<?php if ($done): ?>
    <div class="card g-done reveal" role="status">
      <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="8" width="18" height="13" rx="2"/><path d="M12 8v13M3 12h18M12 8c-2-4-6-4-6-1s4 1 6 1zM12 8c2-4 6-4 6-1s-4 1-6 1z"/></svg>
      <h2><?= owBi('Thank you! Your gift request is with us.', 'شكراً! وصلنا طلب هديتك.') ?></h2>
      <p><?= owBi('The Oweili team will contact you soon to confirm the details and the price. Nothing is charged yet. We also sent you an email.', 'سيتواصل معك فريق أويلي قريباً لتأكيد التفاصيل والسعر. لا يُدفع أي مبلغ الآن. وأرسلنا لك بريداً أيضاً.') ?></p>
      <div class="acts"><a class="btn btn-primary" href="marketplace.php"><?= owBi('Back to the Gallery', 'العودة إلى المعرض') ?></a><a class="btn btn-secondary" href="gifts.php"><?= owBi('Send another gift', 'أرسل هدية أخرى') ?></a></div>
    </div>
<?php elseif (!$ready): ?>
    <div class="card g-done reveal"><h2><?= owBi('Oweili Gifts open very soon.', 'هدايا أويلي تفتح قريباً جداً.') ?></h2>
      <p><?= owBi('Write to us and we will help you choose a gift by hand.', 'راسلنا وسنساعدك في اختيار هديتك بأنفسنا.') ?> <a href="mailto:<?= e(owContactEmail()) ?>" dir="ltr"><?= e(owContactEmail()) ?></a></p></div>
<?php else: ?>
    <div class="g-steps">
      <div class="card g-step reveal"><b>1</b><h2><?= owBi('Choose the piece', 'اختر القطعة') ?></h2><p><?= owBi('A ready artwork from the Gallery, or a custom one made for the occasion.', 'عمل جاهز من المعرض، أو عمل خاص يُصنع للمناسبة.') ?></p></div>
      <div class="card g-step reveal"><b>2</b><h2><?= owBi('Add your card', 'أضف بطاقتك') ?></h2><p><?= owBi('Your words, gift wrapping, and the day it should arrive.', 'كلماتك، والتغليف، واليوم الذي تصل فيه.') ?></p></div>
      <div class="card g-step reveal"><b>3</b><h2><?= owBi('We do the rest', 'ونحن نتكفّل بالباقي') ?></h2><p><?= owBi('We call you to confirm, then wrap and deliver. With "Surprise", the price never shows.', 'نتصل بك للتأكيد، ثم نغلّف ونوصل. ومع «مفاجأة» لا يظهر السعر أبداً.') ?></p></div>
    </div>

    <form class="card g-form reveal" method="post" action="gifts.php" novalidate>
      <?= csrfField() ?>
      <input type="hidden" name="lang" value="en" id="g-lang">
      <div class="trap" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
      <?php if ($errors): ?><div class="alert alert-error" role="alert"><?= owBi('Please check these:', 'يرجى مراجعة ما يلي:') ?><ul><?php foreach ($errors as [$en, $ar]): ?><li><?= owBi($en, $ar) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

      <h2><?= owBi('1. The occasion', '1. المناسبة') ?></h2>
      <div class="g-occ" role="radiogroup" aria-label="Occasion · المناسبة">
        <?php foreach ($OCC as $ok => $o): ?>
        <label><input type="radio" name="occasion" value="<?= e($ok) ?>"<?= $f['occasion'] === $ok ? ' checked' : '' ?> required><svg viewBox="0 0 24 24" aria-hidden="true"><?= $o['icon'] ?></svg><?= owBi($o['en'], $o['ar']) ?></label>
        <?php endforeach; ?>
      </div>

      <h2><?= owBi('2. The artwork', '2. العمل الفني') ?></h2>
      <div class="g-mode">
        <label><input type="radio" name="mode" value="ready"<?= $f['mode'] === 'ready' ? ' checked' : '' ?><?= $choices ? '' : ' disabled' ?>><?= owBi('A ready artwork', 'عمل جاهز') ?><span><?= owBi('Choose one from the Gallery. It can be delivered soon.', 'اختر عملاً من المعرض. يمكن توصيله قريباً.') ?></span></label>
        <label><input type="radio" name="mode" value="custom"<?= $f['mode'] === 'custom' ? ' checked' : '' ?>><?= owBi('A custom artwork', 'عمل خاص') ?><span><?= owBi('An artist makes something new for this occasion. It takes longer.', 'يصنع فنان شيئاً جديداً لهذه المناسبة. يحتاج وقتاً أطول.') ?></span></label>
      </div>
      <div id="g-ready"<?= $f['mode'] === 'ready' ? '' : ' hidden' ?>>
        <?php if ($choices): ?>
        <div class="g-art" role="radiogroup" aria-label="Artwork · العمل">
          <?php foreach ($choices as $i => $c): $img = owImgSrc((string) $c['image_url']); $t4 = $img !== '' ? owImgSized($img, 400) : ''; ?>
          <label><input type="radio" name="artwork_id" value="<?= (int) $c['id'] ?>"<?= $f['artwork_id'] === (int) $c['id'] ? ' checked' : '' ?>>
            <span class="aw-img"><?= owArtImgHtml($img, $c['title_en'] . ' · ' . $c['title_ar'], $i < 4) ?></span>
            <b><?= owBi((string) ($c['title_en'] ?: $c['title_ar']), (string) ($c['title_ar'] ?: $c['title_en'])) ?></b>
            <span><?= owBi('by ' . $c['artist_en'], 'بواسطة ' . $c['artist_ar']) ?> · <?= number_format((float) $c['price']) ?> <?= owBi('SAR', 'ر.س') ?></span></label>
          <?php endforeach; ?>
        </div>
        <a class="g-more" href="marketplace.php"><?= owBi('See more in the Gallery, then press "Give it as a gift" in the artwork window →', 'شاهد المزيد في المعرض، ثم اضغط «أهدِه» في نافذة العمل ←') ?></a>
        <?php endif; ?>
      </div>
      <div id="g-custom"<?= $f['mode'] === 'custom' ? '' : ' hidden' ?>>
        <div class="field"><label for="g-brief"><?= owBi('What should it be?', 'كيف تتخيّله؟') ?></label>
          <textarea id="g-brief" name="custom_brief" rows="4" maxlength="2000" dir="auto" placeholder="<?= e('A calm desert landscape in blue, about 50 × 70 cm · منظر صحراوي هادئ بالأزرق، نحو 50 × 70 سم') ?>"><?= $v('custom_brief') ?></textarea></div>
        <div class="field"><label for="g-budget"><?= owBi('Budget in SAR (optional)', 'الميزانية بالريال (اختياري)') ?></label>
          <input id="g-budget" name="budget_sar" inputmode="decimal" dir="ltr" maxlength="12" value="<?= $v('budget_sar') ?>"></div>
        <?php if (is_file(__DIR__ . '/commission.php')): ?>
        <p class="g-note g-custom-link"><?= owBi('A portrait from a personal photo? Use a private commission: your photos stay confidential.', 'بورتريه من صورة شخصية؟ استخدم الطلب الخاص: تبقى صورك سرّية.') ?> <a href="commission.php"><?= owBi('Private commissions', 'الأعمال الخاصة') ?></a></p>
        <?php endif; ?>
      </div>

      <h2><?= owBi('3. Wrapping, card and day', '3. التغليف والبطاقة والموعد') ?></h2>
      <label class="g-check"><input type="checkbox" name="wrapping" value="1"<?= $f['wrapping'] ? ' checked' : '' ?>><span><?= owBi('Gift wrapping', 'تغليف هدية') ?><small><?= owBi('Wrapped with care, ready to hand over.', 'مغلّفة بعناية، جاهزة للإهداء.') ?></small></span></label>
      <label class="g-check"><input type="checkbox" name="surprise" value="1"<?= $f['surprise'] ? ' checked' : '' ?>><span><?= owBi('Surprise', 'مفاجأة') ?><small><?= owBi('The recipient never sees the price: not on the package, not in any message.', 'لا يرى المُهدى إليه السعر أبداً: لا على الطرد ولا في أي رسالة.') ?></small></span></label>
      <div class="field"><label for="g-card"><?= owBi('Card message (optional)', 'رسالة البطاقة (اختياري)') ?></label>
        <textarea id="g-card" name="card_message" rows="3" maxlength="300" dir="auto"><?= $v('card_message') ?></textarea></div>
      <div class="field"><label for="g-date"><?= owBi('Delivery date', 'تاريخ التوصيل') ?></label>
        <input id="g-date" type="date" name="delivery_date" min="<?= $minDate ?>" required value="<?= $v('delivery_date') ?>"></div>

      <h2><?= owBi('4. Who receives it', '4. لمن الهدية') ?></h2>
      <div class="g-two">
        <div class="field"><label for="g-rn"><?= owBi('Recipient’s name', 'اسم المُهدى إليه') ?></label><input id="g-rn" name="recipient_name" maxlength="150" required dir="auto" value="<?= $v('recipient_name') ?>"></div>
        <div class="field"><label for="g-rp"><?= owBi('Recipient’s phone (optional, for the delivery)', 'هاتف المُهدى إليه (اختياري، للتوصيل)') ?></label><input id="g-rp" name="recipient_phone" type="tel" maxlength="30" dir="ltr" value="<?= $v('recipient_phone') ?>"></div>
        <div class="field"><label for="g-rc"><?= owBi('City', 'المدينة') ?></label><input id="g-rc" name="recipient_city" maxlength="100" required dir="auto" value="<?= $v('recipient_city') ?>"></div>
      </div>
      <div class="field"><label for="g-ra"><?= owBi('Full address', 'العنوان كاملاً') ?></label><textarea id="g-ra" name="recipient_address" rows="2" maxlength="600" required dir="auto" placeholder="<?= e('District, street, building, national short address · الحي، الشارع، المبنى، العنوان الوطني المختصر') ?>"><?= $v('recipient_address') ?></textarea></div>

      <h2><?= owBi('5. Your details', '5. بياناتك') ?></h2>
      <div class="g-two">
        <div class="field"><label for="g-bn"><?= owBi('Your full name', 'اسمك الكامل') ?></label><input id="g-bn" name="buyer_name" maxlength="150" required autocomplete="name" dir="auto" value="<?= $v('buyer_name') ?>"></div>
        <div class="field"><label for="g-bp"><?= owBi('Your phone', 'هاتفك') ?></label><input id="g-bp" name="buyer_phone" type="tel" maxlength="30" required autocomplete="tel" dir="ltr" value="<?= $v('buyer_phone') ?>"></div>
        <div class="field"><label for="g-be"><?= owBi('Your email', 'بريدك الإلكتروني') ?></label><input id="g-be" name="buyer_email" type="email" maxlength="190" required autocomplete="email" dir="ltr" value="<?= $v('buyer_email') ?>"></div>
      </div>
      <p class="g-note"><?= owBi('We use these details only to arrange this gift. The recipient’s address is used only for the delivery.', 'نستخدم هذه البيانات فقط لترتيب هذه الهدية. ويُستخدم عنوان المُهدى إليه للتوصيل فقط.') ?> <a href="privacy.php" target="_blank" rel="noopener"><?= owBi('Privacy Policy', 'سياسة الخصوصية') ?></a></p>
      <div class="g-foot">
        <p class="g-note" style="margin:0"><?= owBi('No payment now. We confirm the price with you first.', 'لا دفع الآن. نؤكد السعر معك أولاً.') ?></p>
        <button class="btn btn-primary" type="submit"><?= owBi('Send my gift request', 'أرسل طلب الهدية') ?></button>
      </div>
    </form>
<?php endif; ?>
  </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
<script>
(function(){
  var r = document.getElementById('g-ready'), c = document.getElementById('g-custom');
  document.querySelectorAll('input[name=mode]').forEach(function(i){ i.addEventListener('change', function(){ if (!r || !c) return; r.hidden = i.value !== 'ready'; c.hidden = i.value !== 'custom'; }); });
  var f = document.querySelector('.g-form'), l = document.getElementById('g-lang');
  if (f && l) f.addEventListener('submit', function(){ l.value = document.documentElement.getAttribute('dir') === 'rtl' ? 'ar' : 'en'; });
})();
</script>
</body>
</html>
