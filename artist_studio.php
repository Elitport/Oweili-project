<?php
/* =====================================================================
 *  artist_studio.php — "My Studio" («مرسمي»), the artist's ONLY home (owner, 11 Nov 2026):
 *  the old dashboard address (artist_dashboard.php without ?id) comes here, and
 *  artist_dashboard.php?id=N is only the public profile. Only the signed-in
 *  artist's OWN data; everyone else is sent away. On top: one summary
 *  (artworks, sold, the artist's share after the commission, waiting for approval)
 *  worked out once below, so every number is the same everywhere.
 *  Tabs (?tab=):
 *    works     — every artwork with its status (draft, in review, approved,
 *                not approved + the reason, sold) and its numbers: views,
 *                favorites, "I'm Interested" requests. Never buyer names,
 *                phones or emails: Oweili is the middle party.
 *    articles  — the artist's own articles (written in my_articles.php,
 *                published only after the admin approves)
 *    commissions — private commissions the admin assigned to this artist
 *                (artist_commission.php; photos only after the
 *                confidentiality agreement)
 *    account   — email, password, notification settings
 *    activity  — what the artist did and when
 *    earnings  — sales and payouts (structure only; payments come in Sprint 4)
 *  Tables: users, artworks, favorites, artwork_inquiries, purchases,
 *          payouts, activity_log, sessions
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/artwork_lib.php';
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/account_lib.php';
require_once __DIR__ . '/approval_lib.php';
require_once __DIR__ . '/cert_lib.php';

if (!isLoggedIn()) { header('Location: login.php?next=artist_studio.php'); exit; }
$me = currentUser();
if (!$me) { header('Location: login.php'); exit; }
if (($me['role'] ?? '') !== 'artist') { header('Location: ' . dashboardUrl($me['role'] ?? '')); exit; }
$uid = (int) $me['id'];

$TABS = ['works', 'certs', 'articles', 'commissions', 'account', 'activity', 'earnings', 'packing'];
/* the Commissions tab follows the Commissions switch in Admin → Settings (owner, 13 Nov 2026); an artist who already
   has a commission keeps the tab, so that work is never lost */
require_once __DIR__ . '/gallery_lib.php';
$commTabOn = owCommissionsPageOn();
if (!$commTabOn) {
    try { $q = getDB()->prepare("SELECT COUNT(*) FROM commissions WHERE artist_id = :u AND status <> 'cancelled'"); $q->execute([':u' => $uid]); $commTabOn = (int) $q->fetchColumn() > 0; }
    catch (Throwable $e) { $commTabOn = false; }
}
if (!$commTabOn) $TABS = array_values(array_diff($TABS, ['commissions']));
$tab  = in_array($_GET['tab'] ?? '', $TABS, true) ? $_GET['tab'] : 'works';
$tabLink = fn(string $t) => 'artist_studio.php?tab=' . $t;

/* ---------------------------------------------------------------------
 *  Changes (POST → back to the page)
 * ------------------------------------------------------------------- */
$err = ''; $old = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $do = (string) ($_POST['do'] ?? '');
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $err = 'csrf';
    } elseif ($do === 'email') {
        $err = owChangeEmail($me, (string) ($_POST['new_email'] ?? ''), (string) ($_POST['current_password'] ?? ''));
        $old = ['new_email' => (string) ($_POST['new_email'] ?? '')];
        if ($err === '') { header('Location: ' . $tabLink('account') . '&done=email'); exit; }
    } elseif ($do === 'password') {
        $err = owChangePassword($me, (string) ($_POST['current_password'] ?? ''), (string) ($_POST['new_password'] ?? ''), (string) ($_POST['confirm_password'] ?? ''));
        if ($err === '') { header('Location: ' . $tabLink('account') . '&done=password'); exit; }
    } elseif ($do === 'notify') {
        if (owHasColumn('users', 'notify_inquiries')) {
            getDB()->prepare('UPDATE users SET notify_inquiries = :i, notify_news = :n WHERE id = :id')
                   ->execute([':i' => isset($_POST['notify_inquiries']) ? 1 : 0, ':n' => isset($_POST['notify_news']) ? 1 : 0, ':id' => $uid]);
            owLog('notify_update', 'user', $uid, '', ['type' => 'artist', 'id' => $uid]);
        }
        header('Location: ' . $tabLink('account') . '&done=notify'); exit;
    } elseif ($do === 'resend') {
        $err = owResendVerify($me);
        if ($err === '') { header('Location: ' . $tabLink($tab) . '&done=resend'); exit; }
    }
    if ($err !== '' && $do !== 'resend') $tab = 'account';
}

/* ---------------------------------------------------------------------
 *  This artist's data
 * ------------------------------------------------------------------- */
$works = [];
try {
    $favSql = owHasTable('favorites') ? '(SELECT COUNT(*) FROM favorites f WHERE f.artwork_id = a.id)' : '0';
    $inqSql = owHasTable('artwork_inquiries') ? '(SELECT COUNT(*) FROM artwork_inquiries q WHERE q.artwork_id = a.id)' : '0';
    $viewSql = owHasColumn('artworks', 'view_count') ? 'a.view_count' : '0';
    $reason  = owHasColumn('artworks', 'rejected_reason') ? 'a.rejected_reason' : 'NULL';
    $st = getDB()->prepare(
        "SELECT a.*, a.id, a.title_en, a.title_ar, a.image_url, a.price, a.status AS sale_status, a.created_at,
                " . owArtworkStatusSql('a') . " AS state, $reason AS reason,
                $viewSql AS views, $favSql AS favs, $inqSql AS inquiries
         FROM artworks a WHERE a.artist_id = :u ORDER BY a.created_at DESC, a.id DESC");
    $st->execute([':u' => $uid]);
    $works = $st->fetchAll();
} catch (Throwable $e) { error_log('artist_studio works: ' . $e->getMessage()); }

$tot = ['views' => 0, 'favs' => 0, 'inquiries' => 0];
foreach ($works as $w) foreach ($tot as $k => $_) $tot[$k] += (int) $w[$k];

/* The one summary (owner, 11 Nov 2026). Artworks = every piece sent to Oweili (drafts in progress are counted
   apart); sold = marked sold; share = each sale minus the commission in force on its sale date;
   waiting = pieces in review + changes waiting for approval (+ the account itself while it waits). */
$soldOn = fn(array $w): string => (string) (($w['sold_at'] ?? '') ?: (($w['updated_at'] ?? '') ?: $w['created_at']));
$sum = ['works' => 0, 'drafts' => 0, 'sold' => 0, 'sales' => 0.0, 'share' => 0.0, 'share_year' => 0.0, 'waiting' => 0];
foreach ($works as $w) {
    if ($w['state'] === 'draft') { $sum['drafts']++; continue; }
    $sum['works']++;
    if ($w['state'] === 'pending') $sum['waiting']++;
    if ($w['state'] === 'sold') {
        $sh = owArtistShare((float) $w['price'], $soldOn($w));
        $sum['sold']++; $sum['sales'] += (float) $w['price']; $sum['share'] += $sh;
        if (substr($soldOn($w), 0, 4) === date('Y')) $sum['share_year'] += $sh;
    }
}

$STATE = [
    'draft'    => ['Draft', 'مسودة'],
    'pending'  => ['In review', 'قيد المراجعة'],
    'approved' => ['In the Gallery', 'في المعرض'],
    'rejected' => ['Not approved', 'لم تتم الموافقة'],
    'sold'     => ['Sold', 'مباع'],
];

$activity = $tab === 'activity' ? owActivityFor('artist', $uid, 60) : [];

$sales = []; $payouts = [];
if ($tab === 'earnings') {
    try {
        $st = getDB()->prepare('SELECT a.title_en, a.title_ar, a.price, COALESCE(a.sold_at, a.updated_at, a.created_at) AS sold_on
                                FROM artworks a WHERE a.artist_id = :u AND a.status = \'sold\' ORDER BY sold_on DESC');
        $st->execute([':u' => $uid]);
        $sales = $st->fetchAll();
    } catch (Throwable $e) {
        try { $st = getDB()->prepare('SELECT title_en, title_ar, price, created_at AS sold_on FROM artworks WHERE artist_id = :u AND status = \'sold\'');
              $st->execute([':u' => $uid]); $sales = $st->fetchAll(); } catch (Throwable $e2) {}
    }
    if (owHasTable('payouts')) {
        $st = getDB()->prepare('SELECT amount, currency, status, reference, created_at FROM payouts WHERE artist_id = :u ORDER BY created_at DESC');
        $st->execute([':u' => $uid]);
        $payouts = $st->fetchAll();
    }
}

$DONE = [
    'email'    => ['Your new email is saved. Please open the link we sent to it to confirm it.', 'تم حفظ بريدك الجديد. افتح الرابط الذي أرسلناه إليه لتأكيده.'],
    'password' => ['Your password is changed. Other phones and computers were signed out.', 'تم تغيير كلمة المرور. وتم تسجيل الخروج من الأجهزة الأخرى.'],
    'notify'   => ['Your email choices are saved.', 'تم حفظ اختياراتك للرسائل.'],
    'resend'   => ['We sent the confirmation link again. Please check your inbox.', 'أرسلنا رابط التأكيد مرة أخرى. راجع بريدك.'],
    'waiting'  => ['Saved. Your changes are waiting for the team’s approval. Visitors see the approved version until then.', 'تم الحفظ. تعديلاتك بانتظار موافقة الفريق. يرى الزوار النسخة المعتمدة حتى ذلك الحين.'],
    'saved'    => ['Your changes are saved.', 'تم حفظ تعديلاتك.'],
];
$myChanges = owMyChanges($uid);
foreach ($myChanges as $mc) if ($mc['state'] === 'pending') $sum['waiting']++;
if ((int) ($me['is_approved'] ?? 1) === 0) $sum['waiting']++;   // the account itself, until the team approves it
/* Certificates of Authenticity: one per approved artwork */
$certs = owCertsForArtist($uid);
$certByArt = [];
foreach ($certs as $c) if ($c['status'] === 'valid') $certByArt[(int) $c['artwork_id']] = $c;
$done = $DONE[$_GET['done'] ?? ''] ?? null;
$verified = (int) ($me['is_verified'] ?? 0) === 1;
$name = owRealName($me['artist_name']) ?: (owRealName($me['full_name_en']) ?: 'Artist');
$fmtDate = function (?string $d, bool $time = false): string { [$en, $ar] = owDateBi($d, $time); return owBi($en, $ar); };
?>
<!DOCTYPE html>
<html <?php require_once __DIR__ . '/lang_lib.php'; owHtmlAttrs(); ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>My Studio · مرسمي · Oweili</title>
<meta name="description" content="Your artworks, numbers and account on Oweili. أعمالك وأرقامك وحسابك في أويلي.">
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
/* My Studio — layout only; cards, buttons, stats, tabs and tables come from oweili.css (role color: artist) */
.page{position:relative;z-index:10;}
.st-head{display:flex;gap:var(--space-md);align-items:center;justify-content:space-between;flex-wrap:wrap;padding:var(--space-lg);}
.st-head h1{font-size:var(--fs-section);margin:0 0 4px;}
.st-head p{margin:0;color:var(--color-body);}
.st-head .acts{display:flex;gap:var(--space-xs);flex-wrap:wrap;}
.st-stats{grid-template-columns:repeat(4,1fr);margin-top:var(--space-md);}
.st-sub{display:block;font-size:var(--fs-caption);color:var(--color-body);margin-top:2px;}
.st-mini{display:flex;gap:var(--space-md);flex-wrap:wrap;margin-top:var(--space-sm);font-size:var(--fs-caption);color:var(--color-body);}
.st-mini b{color:var(--color-dark);}
.w-side form{margin:0;}
.pk-list{list-style:none;margin:0;padding:0;display:grid;gap:var(--space-sm);}
.pk-step{display:flex;align-items:center;gap:var(--space-md);padding:var(--space-md);}
.pk-ico{flex-shrink:0;width:72px;height:72px;display:flex;align-items:center;justify-content:center;border-radius:var(--radius-input);background:var(--color-frame);border:1px solid var(--color-border);}
.pk-ico svg{width:52px;height:52px;fill:none;stroke:var(--color-dark);stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}
.pk-txt{font-size:16px;line-height:1.6;color:var(--color-dark);}
.pk-n{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;margin-inline-end:10px;border-radius:50%;background:var(--color-primary);color:var(--color-on-primary);font-size:14px;}
@media(max-width:768px){.pk-step{gap:var(--space-sm);padding:var(--space-sm);}.pk-ico{width:56px;height:56px;}.pk-ico svg{width:40px;height:40px;}}
.st-tabs{margin:var(--space-lg) 0 var(--space-md);}
.note{font-size:var(--fs-caption);color:var(--color-body);margin:0 0 var(--space-sm);}
.w-list{display:grid;gap:var(--space-sm);}
.w-row > div{min-width:0;}
.w-row{display:grid;grid-template-columns:72px 1fr auto;gap:var(--space-sm);align-items:center;padding:var(--space-sm);}
.w-thumb{position:relative;width:72px;height:72px;border-radius:var(--radius-input);overflow:hidden;background:var(--color-frame);border:1px solid var(--color-border);}
.w-thumb img{width:100%;height:100%;object-fit:contain;display:block;}
.w-thumb img[hidden]{display:none;}
.w-title{font-weight:700;color:var(--color-dark);margin:0 0 4px;}
.w-nums{display:flex;gap:var(--space-sm);flex-wrap:wrap;font-size:var(--fs-caption);color:var(--color-body);}
.w-nums b{color:var(--color-dark);}
.w-cert{margin-top:6px;font-size:var(--fs-caption);font-weight:700;color:var(--color-dark);}
.w-cert a{color:var(--color-accent);font-weight:700;}
.w-wait a{font-weight:700;color:var(--color-accent);}
.w-wait{margin-top:6px;font-size:var(--fs-caption);color:var(--color-dark);background:var(--color-tint-blue);border:1px solid rgba(var(--rgb-accent),.2);border-radius:var(--radius-input);padding:6px 10px;}
.w-reason{margin-top:6px;font-size:var(--fs-caption);color:var(--color-dark);background:var(--color-tint-pink);border:1px solid rgba(var(--rgb-primary),.3);border-radius:var(--radius-input);padding:6px 10px;}
.w-side{display:flex;flex-direction:column;align-items:flex-end;gap:6px;}
.st-pill{display:inline-block;font-size:var(--fs-min);font-weight:700;padding:3px 10px;border-radius:var(--radius-pill);border:1px solid var(--color-border-strong);color:var(--color-dark);background:var(--color-surface);white-space:nowrap;}
.st-pill.s-approved{background:rgba(var(--rgb-accent),.1);border-color:rgba(var(--rgb-accent),.35);}
.st-pill.s-rejected{background:rgba(var(--rgb-primary),.18);border-color:rgba(var(--rgb-primary),.45);}
.st-pill.s-pending,.st-pill.s-draft{background:var(--color-surface-2);}
.form-card{max-width:620px;padding:var(--space-lg);}
.form-card h2{font-size:var(--fs-subtitle);margin:0 0 var(--space-sm);}
.form-card + .form-card{margin-top:var(--space-md);}
.hint{font-size:var(--fs-caption);color:var(--color-body);margin:-4px 0 var(--space-sm);}
.check{display:flex;gap:var(--space-xs);align-items:flex-start;margin-bottom:var(--space-sm);font-size:15px;}
.check input{margin-top:4px;}
.act-list{list-style:none;margin:0;padding:0;}
.act-list li{display:flex;justify-content:space-between;gap:var(--space-sm);padding:12px 0;border-bottom:1px solid var(--color-border);}
.act-list li:last-child{border-bottom:0;}
.act-when{font-size:var(--fs-caption);color:var(--color-body);white-space:nowrap;}
.empty{text-align:center;padding:var(--space-xl) var(--space-md);}
.empty p{margin:0 0 var(--space-sm);}
@media(max-width:768px){.st-head{padding:var(--space-md);}.st-stats{grid-template-columns:repeat(2,1fr);gap:var(--space-xs);}
  .w-row{grid-template-columns:56px 1fr;}.w-thumb{width:56px;height:56px;}.w-side{grid-column:1 / -1;flex-direction:row;flex-wrap:wrap;justify-content:space-between;align-items:center;}}
</style>
</head>
<body>
<div class="bg-wrap"></div>
<div class="overlay"></div>
<?php include __DIR__ . '/nav.php'; ?>

<main class="page dash role-artist dash-wrap" id="pg">
  <div class="card st-head">
    <div>
      <h1><?= owBi('My Studio', 'مرسمي') ?></h1>
      <p><?php $nmDot = rtrim($name, '. '); /* a name like "Sara A." keeps one full stop, not two */ ?><?= owBi('Welcome back, ' . $nmDot . '. Here is how your work is doing.', 'أهلاً بعودتك يا ' . $nmDot . '. هذا حال أعمالك.') ?></p>
    </div>
    <div class="acts">
      <a class="btn btn-primary" href="upload_artwork.php"><?= owBi('Start New Artwork', 'ابدأ عملاً جديداً') ?></a>
      <a class="btn btn-secondary" href="edit_profile.php"><?= owBi('Edit Profile', 'تعديل الملف') ?></a>
      <a class="btn btn-ghost" href="artist_dashboard.php?id=<?= $uid ?>"><?= owBi('My public page', 'صفحتي العامة') ?></a>
    </div>
  </div>

  <?php if (!$verified): ?>
  <div class="alert alert-error" role="status" style="display:flex;justify-content:space-between;align-items:center;gap:var(--space-sm);flex-wrap:wrap;margin-top:var(--space-md)">
    <span><?= owBi('Please confirm your email address. We sent you a link when you joined.', 'يرجى تأكيد بريدك الإلكتروني. أرسلنا لك رابطاً عند التسجيل.') ?></span>
    <form method="post" action="<?= e($tabLink($tab)) ?>" style="margin:0"><?= csrfField() ?><input type="hidden" name="do" value="resend">
      <button class="btn btn-secondary btn-sm" type="submit"><?= owBi('Send the link again', 'أرسل الرابط مجدداً') ?></button></form>
  </div>
  <?php endif; ?>
  <?php if ($done): ?><div class="alert alert-ok" role="status" style="margin-top:var(--space-md)"><?= owBi($done[0], $done[1]) ?></div><?php endif; ?>
  <?php if ($err !== ''): $m = OW_ACCOUNT_ERR[$err] ?? OW_ACCOUNT_ERR['server']; ?><div class="alert alert-error" role="alert" style="margin-top:var(--space-md)"><?= owBi($m[0], $m[1]) ?></div><?php endif; ?>

  <?php $dashMsg = (string) ($_SESSION['dash_msg'] ?? ''); unset($_SESSION['dash_msg']);   /* the result of Delete (artist_dashboard.php does the deleting) */
  $DASH_MSG = ['deleted' => ['alert-ok', 'The artwork is deleted. It is gone from your profile and the Gallery.', 'تم حذف العمل. لم يعد في ملفك ولا في المعرض.'],
               'not_deleted' => ['alert-error', 'We could not delete that artwork. Please try again, or write to ' . owContactEmail() . '.', 'لم نتمكن من حذف هذا العمل. حاول مرة أخرى، أو راسلنا على ' . owContactEmail() . '.'],
               'expired' => ['alert-error', 'This page was open for a long time, so nothing was changed. Please press Delete again.', 'كانت هذه الصفحة مفتوحة لوقت طويل، لذلك لم يتغير شيء. اضغط «حذف» مرة أخرى.']];
  if (isset($DASH_MSG[$dashMsg])): ?><div class="alert <?= $DASH_MSG[$dashMsg][0] ?>" role="status" style="margin-top:var(--space-md)"><?= owBi($DASH_MSG[$dashMsg][1], $DASH_MSG[$dashMsg][2]) ?></div><?php endif; ?>

  <div class="stat-grid st-stats st-sum">
    <div class="stat-card"><span class="stat-num"><?= $sum['works'] ?></span><span class="stat-lbl"><?= owBi('Artworks', 'الأعمال') ?></span><?php if ($sum['drafts']): ?><span class="st-sub"><?= owBi('+ ' . $sum['drafts'] . ' draft' . ($sum['drafts'] > 1 ? 's' : '') . ' in progress', '+ ' . $sum['drafts'] . ' ' . ($sum['drafts'] > 1 ? 'مسودات' : 'مسودة') . ' قيد الإعداد') ?></span><?php endif; ?></div>
    <div class="stat-card"><span class="stat-num"><?= $sum['sold'] ?></span><span class="stat-lbl"><?= owBi('Sold', 'المباعة') ?></span></div>
    <div class="stat-card"><span class="stat-num"><?= owArtistShareText($sum['share']) ?></span><span class="stat-lbl"><?= owBi('Your share after commission (SAR)', 'حصتك بعد العمولة (ر.س)') ?></span></div>
    <div class="stat-card"><span class="stat-num"><?= $sum['waiting'] ?></span><span class="stat-lbl"><?= owBi('Waiting for approval', 'بانتظار الموافقة') ?></span></div>
  </div>
  <div class="st-mini">
    <span><b><?= number_format($tot['views']) ?></b> <?= owBi('views', 'مشاهدة') ?></span>
    <span><b><?= number_format($tot['favs']) ?></b> <?= owBi('favorites', 'إعجاب') ?></span>
    <span><b><?= number_format($tot['inquiries']) ?></b> <?= owBi('interested collectors', 'مهتم بالشراء') ?></span>
  </div>

  <nav class="tabs st-tabs" aria-label="Studio · المرسم">
    <?php foreach (['works' => ['My artworks', 'أعمالي'], 'certs' => ['Certificates', 'الشهادات'], 'articles' => ['My articles', 'مقالاتي'], 'commissions' => ['Commissions', 'الأعمال الخاصة'], 'account' => ['Account', 'الحساب'], 'activity' => ['Activity', 'النشاط'], 'earnings' => ['Sales & payouts', 'المبيعات والمدفوعات'], 'packing' => ['Packing guide', 'دليل التغليف']] as $t => $l): if (!in_array($t, $TABS, true)) continue; ?>
      <a class="tab<?= $tab === $t ? ' active' : '' ?>" href="<?= e($tabLink($t)) ?>"<?= $tab === $t ? ' aria-current="page"' : '' ?>><?= owBi($l[0], $l[1]) ?></a>
    <?php endforeach; ?>
  </nav>

<?php if ($tab === 'works'): ?>
  <p class="note"><?= owBi('Oweili is the middle party: we talk to collectors for you and keep their details private. You see how many people are interested, never who they are.', 'أويلي هو الوسيط: نتواصل مع المقتنين نيابة عنك ونحفظ بياناتهم. ترى عدد المهتمين، لا هوياتهم.') ?></p>
  <?php if (!$works): ?>
    <div class="card empty"><p><?= owBi('Your studio is ready for its first piece.', 'مرسمك جاهز لعملك الأول.') ?></p>
      <a class="btn btn-primary" href="upload_artwork.php"><?= owBi('Start New Artwork', 'ابدأ عملاً جديداً') ?></a></div>
  <?php else: ?>
  <div class="w-list">
    <?php foreach ($works as $w):
      $s = isset($STATE[$w['state']]) ? $w['state'] : 'pending';
      $img = owImgSrc($w['image_url'] ?? ''); $t4 = $img !== '' ? owImgSized($img, 400) : '';
      $tEn = (string) ($w['title_en'] ?: $w['title_ar']); $tAr = (string) ($w['title_ar'] ?: $w['title_en']); ?>
    <div class="card w-row">
      <div class="w-thumb"><?php if ($img !== ''): ?><img loading="lazy" src="<?= e($t4) ?>" alt="<?= e($tEn . ' · ' . $tAr) ?>"<?= owImgFallbackAttrs($img, $t4) ?>><?= owImgPlaceholder(true) ?><?php else: ?><?= owImgPlaceholder(false) ?><?php endif; ?></div>
      <div>
        <p class="w-title"><?= owBi($tEn, $tAr) ?></p>
        <div class="w-nums">
          <span><b><?= number_format((int) $w['views']) ?></b> <?= owBi('views', 'مشاهدة') ?></span>
          <span><b><?= number_format((int) $w['favs']) ?></b> <?= owBi('favorites', 'إعجاب') ?></span>
          <span><b><?= number_format((int) $w['inquiries']) ?></b> <?= owBi('interested', 'مهتم') ?></span>
          <span><?= number_format((float) $w['price']) ?> <?= owBi('SAR', 'ر.س') ?></span>
        </div>
        <?php if (isset($certByArt[(int) $w['id']])): $wc = $certByArt[(int) $w['id']]; ?>
          <div class="w-cert">✓ <?= owBi('Certificate of Authenticity', 'شهادة أصالة') ?> <a href="certificate.php?no=<?= e(rawurlencode($wc['cert_no'])) ?>" dir="ltr"><?= e($wc['cert_no']) ?></a></div>
        <?php elseif (owCertReady() && $s !== 'draft' && ($wm = owCertMissing($w))): [$mE, $mA] = owCertMissingWords($wm); ?>
          <div class="w-wait"><?= owBi('For its certificate, please add: ' . $mE . '.', 'لإصدار شهادته، يرجى إضافة: ' . $mA . '.') ?> <a href="edit_artwork.php?id=<?= (int) $w['id'] ?>"><?= owBi('Add details', 'أضف التفاصيل') ?></a></div>
        <?php endif; ?>
        <?php $ch = $myChanges['artwork:' . (int) $w['id']] ?? null; if ($ch && $ch['state'] === 'pending'): ?>
          <div class="w-wait">⏳ <?= owBi('Your changes are waiting for approval. Visitors see the approved version.', 'تعديلاتك بانتظار الموافقة. يرى الزوار النسخة المعتمدة.') ?></div>
        <?php elseif ($ch && $ch['state'] === 'rejected'): ?>
          <div class="w-reason"><?= owBi('Your last changes were not approved: ', 'لم تتم الموافقة على تعديلاتك الأخيرة: ') ?><?= e($ch['reason'] ?: '—') ?></div>
        <?php endif; ?>
        <?php if ($s === 'rejected'): ?>
          <div class="w-reason"><?= owBi('Why: ', 'السبب: ') ?><?= e((string) ($w['reason'] ?: '—')) ?> · <?= owBi('Edit it and it goes back to review.', 'عدّله ليعود إلى المراجعة.') ?></div>
        <?php endif; ?>
      </div>
      <div class="w-side">
        <span class="st-pill s-<?= e($s) ?>"><?= owBi($STATE[$s][0], $STATE[$s][1]) ?></span>
        <?php if ($s === 'draft'): ?>
          <a class="btn btn-accent btn-sm" href="upload_artwork.php?id=<?= (int) $w['id'] ?>"><?= owBi('Continue', 'متابعة') ?></a>
        <?php else: ?>
          <a class="btn btn-ghost btn-sm" href="edit_artwork.php?id=<?= (int) $w['id'] ?>"><?= owBi('Edit', 'تعديل') ?></a>
        <?php endif; ?>
          <form method="post" action="artist_dashboard.php" onsubmit="return confirm(document.documentElement.dir === 'rtl' ? <?= $s === 'draft' ? "'حذف هذه المسودة؟'" : "'حذف هذا العمل؟ لا يمكن التراجع.'" ?> : <?= $s === 'draft' ? "'Delete this draft?'" : "'Delete this artwork? This cannot be undone.'" ?>);">
            <?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="artwork_id" value="<?= (int) $w['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm"><?= owBi('Delete', 'حذف') ?></button></form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

<?php elseif ($tab === 'articles'):
  $myArts = [];
  try { if (owHasTable('articles')) { $q = getDB()->prepare('SELECT id, type, slug, title_en, title_ar, status FROM articles WHERE author_user_id = :u ORDER BY updated_at DESC, id DESC LIMIT 50'); $q->execute([':u' => $uid]); $myArts = $q->fetchAll(); } } catch (Throwable $e) { $myArts = []; }
  $aState = ['draft' => ['Draft', 'مسودة'], 'pending' => ['Waiting for approval', 'بانتظار الموافقة'], 'rejected' => ['Not approved', 'لم تتم الموافقة'], 'published' => ['Published', 'منشور']];
  $aTypes = ['event' => ['Art Event', 'ملتقى فني'], 'artist_spotlight' => ['Artist Spotlight', 'أضواء على فنان'], 'art_education' => ['Art Education', 'تثقيف فني']]; ?>
  <p class="note"><?= owBi('Write Art Events, Artist Spotlights and Art Education articles. The Oweili team reads each one first, and we email you the result.', 'اكتب عن الملتقيات الفنية وأضواء على الفنانين والتثقيف الفني. يقرأ فريق أويلي كل مقال أولاً، ونراسلك بالنتيجة.') ?></p>
  <div style="display:flex;gap:var(--space-xs);flex-wrap:wrap;margin-bottom:var(--space-md)">
    <a class="btn btn-primary" href="my_articles.php?new=event"><?= owBi('New Art Event', 'ملتقى فني جديد') ?></a>
    <a class="btn btn-secondary" href="my_articles.php?new=artist_spotlight"><?= owBi('New Artist Spotlight', 'أضواء جديدة على فنان') ?></a>
    <a class="btn btn-secondary" href="my_articles.php?new=art_education"><?= owBi('New Art Education article', 'مقال تثقيف فني جديد') ?></a>
  </div>
  <?php if (!$myArts): ?>
    <div class="card empty"><p><?= owBi('No articles yet. Your first one starts with one of the buttons above.', 'لا مقالات بعد. يبدأ مقالك الأول بأحد الأزرار في الأعلى.') ?></p></div>
  <?php else: ?>
  <div class="w-list">
    <?php foreach ($myArts as $ar): $ch = $myChanges['article:' . (int) $ar['id']] ?? null;
      $s = $ar['status'] === 'published' ? 'published' : ($ch && $ch['state'] === 'pending' ? 'pending' : ($ch && $ch['state'] === 'rejected' ? 'rejected' : 'draft'));
      $ty = $aTypes[$ar['type']] ?? ['Article', 'مقال']; ?>
    <div class="card w-row" style="grid-template-columns:1fr auto">
      <div><p class="w-title"><?= owBi((string) ($ar['title_en'] ?: $ar['title_ar']), (string) ($ar['title_ar'] ?: $ar['title_en'])) ?></p>
        <div class="w-nums"><span><?= owBi($ty[0], $ty[1]) ?></span></div>
        <?php if ($s === 'rejected'): ?><div class="w-reason"><?= owBi('Why: ', 'السبب: ') ?><?= e($ch['reason'] ?: '—') ?></div><?php endif; ?></div>
      <div class="w-side"><span class="st-pill s-<?= $s === 'published' ? 'approved' : e($s) ?>"><?= owBi($aState[$s][0], $aState[$s][1]) ?></span>
        <?php if ($s === 'published'): ?><a class="btn btn-ghost btn-sm" href="article.php?slug=<?= e(rawurlencode($ar['slug'])) ?>"><?= owBi('See it', 'شاهده') ?></a>
        <?php else: ?><a class="btn btn-accent btn-sm" href="my_articles.php?edit=<?= (int) $ar['id'] ?>"><?= owBi('Edit', 'تعديل') ?></a><?php endif; ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <p class="note" style="margin-top:var(--space-sm)"><a href="my_articles.php"><?= owBi('Open all my articles', 'افتح كل مقالاتي') ?></a></p>
  <?php endif; ?>
<?php elseif ($tab === 'commissions'):
  require_once __DIR__ . '/commission_lib.php';
  $myC = [];
  try { if (owCommReady()) { $q = getDB()->prepare("SELECT c.id, c.ref_no, c.subject, c.status, c.assigned_at, (SELECT COUNT(*) FROM commission_nda n WHERE n.commission_id = c.id AND n.artist_id = c.artist_id) AS nda
                                                     FROM commissions c WHERE c.artist_id = :u AND c.status <> 'cancelled' ORDER BY c.id DESC LIMIT 100"); $q->execute([':u' => $uid]); $myC = $q->fetchAll(); } } catch (Throwable $e) { $myC = []; }
  $cS = owCommSubjects(); ?>
  <p class="note"><?= owBi('Private commissions the Oweili team chose you for: portraits and personal memories, painted from the collector’s photos. Everything here is confidential.', 'أعمال خاصة اختارك لها فريق أويلي: بورتريهات وذكريات شخصية تُرسم من صور المقتني. كل ما هنا سرّي.') ?></p>
  <?php if (!$myC): ?>
    <div class="card empty"><p><?= owBi('No private commissions yet. When the team chooses you, you get an email.', 'لا أعمال خاصة بعد. عندما يختارك الفريق تصلك رسالة.') ?></p></div>
  <?php else: ?>
  <div class="w-list">
    <?php foreach ($myC as $cm): [$ce, $ca] = owCommStatus()[$cm['status']]; ?>
    <div class="card w-row" style="grid-template-columns:1fr auto">
      <div><p class="w-title"><?= e($cm['ref_no']) ?> · <?= owBi($cS[$cm['subject']][0] ?? '', $cS[$cm['subject']][1] ?? '') ?></p>
        <?php if (!(int) $cm['nda']): ?><div class="w-wait"><?= owBi('Please accept the confidentiality agreement to see the photos.', 'يرجى قبول اتفاقية السرّية لرؤية الصور.') ?></div><?php endif; ?></div>
      <div class="w-side"><span class="st-pill"><?= owBi($ce, $ca) ?></span><a class="btn btn-accent btn-sm" href="artist_commission.php?id=<?= (int) $cm['id'] ?>"><?= owBi('Open', 'افتح') ?></a></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
<?php elseif ($tab === 'account'): ?>
  <form class="card form-card" method="post" action="<?= e($tabLink('account')) ?>" novalidate>
    <?= csrfField() ?><input type="hidden" name="do" value="notify">
    <h2><?= owBi('Emails from Oweili', 'رسائل أويلي') ?></h2>
    <label class="check"><input type="checkbox" name="notify_inquiries" value="1"<?= (int) ($me['notify_inquiries'] ?? 1) === 1 ? ' checked' : '' ?>>
      <span><?= owBi('Tell me when someone is interested in one of my works', 'أخبرني حين يهتم أحد بأحد أعمالي') ?></span></label>
    <label class="check"><input type="checkbox" name="notify_news" value="1"<?= (int) ($me['notify_news'] ?? 1) === 1 ? ' checked' : '' ?>>
      <span><?= owBi('Send me Oweili news and tips for artists', 'أرسل لي أخبار أويلي ونصائح للفنانين') ?></span></label>
    <p class="hint"><?= owBi('Important account emails (like password changes) always arrive.', 'رسائل الحساب المهمة (مثل تغيير كلمة المرور) تصلك دائماً.') ?></p>
    <button class="btn btn-primary" type="submit"><?= owBi('Save', 'حفظ') ?></button>
  </form>
  <form class="card form-card" method="post" action="<?= e($tabLink('account')) ?>" novalidate>
    <?= csrfField() ?><input type="hidden" name="do" value="email">
    <h2><?= owBi('Email address', 'البريد الإلكتروني') ?></h2>
    <p class="hint"><?= owBi('Now: ', 'الحالي: ') ?><span dir="ltr"><?= e($me['email']) ?></span><?= $verified ? '' : ' · ' . owBi('not confirmed yet', 'لم يُؤكَّد بعد') ?></p>
    <div class="field<?= in_array($err, ['email', 'email_same', 'exists'], true) ? ' has-error' : '' ?>"><label for="a-email"><?= owBi('New email', 'البريد الجديد') ?></label>
      <input id="a-email" name="new_email" type="email" required maxlength="190" autocomplete="email" dir="ltr" value="<?= e($old['new_email'] ?? '') ?>"></div>
    <div class="field"><label for="a-cur1"><?= owBi('Current password', 'كلمة المرور الحالية') ?></label>
      <input id="a-cur1" name="current_password" type="password" required autocomplete="current-password"></div>
    <p class="hint"><?= owBi('We will send a link to the new address to confirm it.', 'سنرسل رابطاً إلى البريد الجديد لتأكيده.') ?></p>
    <button class="btn btn-primary" type="submit"><?= owBi('Change email', 'غيّر البريد') ?></button>
  </form>
  <form class="card form-card" method="post" action="<?= e($tabLink('account')) ?>" novalidate>
    <?= csrfField() ?><input type="hidden" name="do" value="password">
    <h2><?= owBi('Password', 'كلمة المرور') ?></h2>
    <div class="field"><label for="a-cur2"><?= owBi('Current password', 'كلمة المرور الحالية') ?></label>
      <input id="a-cur2" name="current_password" type="password" required autocomplete="current-password"></div>
    <div class="field<?= in_array($err, ['weak', 'same'], true) ? ' has-error' : '' ?>"><label for="a-new"><?= owBi('New password', 'كلمة المرور الجديدة') ?></label>
      <input id="a-new" name="new_password" type="password" required minlength="8" autocomplete="new-password"></div>
    <p class="hint"><?= owBi('8 or more characters, with a capital letter, a number and a symbol.', '8 أحرف أو أكثر، مع حرف كبير ورقم ورمز.') ?></p>
    <div class="field<?= $err === 'mismatch' ? ' has-error' : '' ?>"><label for="a-conf"><?= owBi('New password again', 'كلمة المرور الجديدة مرة أخرى') ?></label>
      <input id="a-conf" name="confirm_password" type="password" required minlength="8" autocomplete="new-password"></div>
    <button class="btn btn-primary" type="submit"><?= owBi('Change password', 'غيّر كلمة المرور') ?></button>
  </form>

<?php elseif ($tab === 'certs'): ?>
  <p class="note"><?= owBi('Each approved artwork gets a Certificate of Authenticity with its own number. Open one to download it as a PDF, or share its verify page with a collector.', 'يحصل كل عمل معتمد على شهادة أصالة برقم خاص. افتح الشهادة لتنزيلها بصيغة PDF، أو شارك صفحة التحقق مع المقتني.') ?></p>
  <?php require_once __DIR__ . '/sig_lib.php'; $sigOn = owSigExists((string) $uid); ?>
  <div class="card" style="display:flex;flex-wrap:wrap;gap:var(--space-xs);align-items:center;justify-content:space-between;margin-bottom:var(--space-sm)">
    <span><?= $sigOn ? owBi('✓ Your signature is on your certificates.', '✓ توقيعك على شهاداتك.') : owBi('Add your signature to your certificates: draw it once.', 'أضف توقيعك إلى شهاداتك: ارسمه مرة واحدة.') ?></span>
    <a class="btn btn-secondary btn-sm" href="signature.php"><?= $sigOn ? owBi('Change signature', 'غيّر التوقيع') : owBi('Draw my signature', 'ارسم توقيعي') ?></a>
  </div>
  <?php if (!owCertReady() || !$certs): ?>
    <div class="card empty"><p><?= owBi('Your certificates will appear here once your artworks are approved.', 'ستظهر شهاداتك هنا بعد اعتماد أعمالك.') ?></p></div>
  <?php else: ?>
  <div class="w-list">
    <?php foreach ($certs as $c): $cs = $c['snap']; $ci = owImgSrc((string) ($cs['image'] ?? '')); $c4 = $ci !== '' ? owImgSized($ci, 400) : ''; [$ciE, $ciA] = owDateBi((string) $c['issued_at']); $ok = $c['status'] === 'valid'; ?>
    <div class="card w-row">
      <div class="w-thumb"><?php if ($ci !== ''): ?><img loading="lazy" src="<?= e($c4) ?>" alt="<?= e(($cs['title_en'] ?? '') . ' · ' . ($cs['title_ar'] ?? '')) ?>"<?= owImgFallbackAttrs($ci, $c4) ?>><?= owImgPlaceholder(true) ?><?php else: ?><?= owImgPlaceholder(false) ?><?php endif; ?></div>
      <div>
        <p class="w-title"><?= owBi((string) ($cs['title_en'] ?? ''), (string) ($cs['title_ar'] ?? '')) ?></p>
        <div class="w-nums"><span dir="ltr"><b><?= e($c['cert_no']) ?></b></span><span><?= owBi('Issued ' . $ciE, 'صدرت ' . $ciA) ?></span></div>
        <?php if (!$ok): ?><div class="w-reason"><?= owBi('Cancelled by Oweili. Reason: ', 'ألغتها أويلي. السبب: ') ?><?= e((string) $c['cancel_reason']) ?></div><?php endif; ?>
      </div>
      <div class="w-side">
        <span class="st-pill <?= $ok ? 's-approved' : 's-rejected' ?>"><?= $ok ? owBi('Valid', 'سارية') : owBi('Cancelled', 'ملغاة') ?></span>
        <a class="btn btn-accent btn-sm" href="certificate.php?no=<?= e(rawurlencode($c['cert_no'])) ?>"><?= owBi('View & download', 'عرض وتنزيل') ?></a>
        <a class="btn btn-ghost btn-sm" href="<?= e(owCertVerifyUrl((string) $c['cert_no'])) ?>"><?= owBi('Verify page', 'صفحة التحقق') ?></a>
        <?php if ($ok): ?><a class="btn btn-ghost btn-sm" href="cert_label.php?no=<?= e(rawurlencode($c['cert_no'])) ?>"><?= owBi('Back label', 'ملصق الظهر') ?></a><?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

<?php elseif ($tab === 'packing'): /* Packing guide (owner, 11 Nov 2026): only here, for the signed-in artist */ ?>
  <p class="note"><?= owBi('Oweili arranges the shipping. You only pack the artwork.', 'أويلي يتولى الشحن. عليك فقط تغليف العمل.') ?></p>
  <ol class="pk-list">
      <li class="card pk-step"><span class="pk-ico"><svg viewBox="0 0 64 64" aria-hidden="true"><rect x="10" y="12" width="40" height="40" rx="2"/><path d="M16 18h28v28H16z" stroke-dasharray="3 3"/><path d="M18 8h36v36l-8 8H18z"/><path d="M54 44h-8v8"/></svg></span><span class="pk-txt"><b class="pk-n">1</b><?= owBi('Cover the front of the painting with glassine paper (soft protective paper). Never put bubble wrap straight on the paint.', 'غطِّ وجه اللوحة بورق الجلاسين (ورق حماية ناعم). لا تضع الغلاف الفقاعي مباشرة على الطلاء أبداً.') ?></span></li>
      <li class="card pk-step"><span class="pk-ico"><svg viewBox="0 0 64 64" aria-hidden="true"><rect x="10" y="10" width="44" height="44" rx="2"/><path d="M10 22V10h12M42 10h12v12M54 42v12H42M22 54H10V42"/><path d="M10 22l12-12M42 10l12 12M54 42L42 54M22 54L10 42"/></svg></span><span class="pk-txt"><b class="pk-n">2</b><?= owBi('Add corner protectors.', 'أضف واقيات الزوايا.') ?></span></li>
      <li class="card pk-step"><span class="pk-ico"><svg viewBox="0 0 64 64" aria-hidden="true"><rect x="8" y="10" width="48" height="44" rx="6"/><circle cx="18" cy="20" r="3"/><circle cx="32" cy="20" r="3"/><circle cx="46" cy="20" r="3"/><circle cx="18" cy="32" r="3"/><circle cx="32" cy="32" r="3"/><circle cx="46" cy="32" r="3"/><circle cx="18" cy="44" r="3"/><circle cx="32" cy="44" r="3"/><circle cx="46" cy="44" r="3"/></svg></span><span class="pk-txt"><b class="pk-n">3</b><?= owBi('Wrap it in bubble wrap.', 'لُفَّ اللوحة بالغلاف الفقاعي.') ?></span></li>
      <li class="card pk-step"><span class="pk-ico"><svg viewBox="0 0 64 64" aria-hidden="true"><path d="M8 24l24-10 24 10v26L32 60 8 50z"/><path d="M8 24l24 10 24-10M32 34v26"/><path d="M8 24l-4 8 24 10 4-8M56 24l4 8-24 10-4-8"/></svg></span><span class="pk-txt"><b class="pk-n">4</b><?= owBi('Put it in a strong cardboard box that fits snugly, with no empty space.', 'ضعها في صندوق كرتون متين يناسب مقاسها تماماً، دون فراغات.') ?></span></li>
      <li class="card pk-step"><span class="pk-ico"><svg viewBox="0 0 64 64" aria-hidden="true"><path d="M6 26l22-9 22 9v22L28 57 6 48z"/><path d="M6 26l22 9 22-9M28 35v22"/><path d="M11 37l12 5v6l-12-5z"/><rect x="40" y="6" width="20" height="16" rx="2"/><path d="M44 12h12M44 16h8"/></svg></span><span class="pk-txt"><b class="pk-n">5</b><?= owBi('Write the certificate number on the box, and put the back label on the artwork.', 'اكتب رقم الشهادة على الصندوق، وألصق ملصق الظهر على العمل.') ?></span></li>
  </ol>
  <p class="note" style="margin-top:var(--space-sm)"><?= owBi('Your certificate numbers and back labels are in', 'تجد أرقام شهاداتك وملصقات الظهر في') ?> <a href="<?= e($tabLink('certs')) ?>"><?= owBi('Certificates', 'الشهادات') ?></a>.</p>

<?php elseif ($tab === 'activity'): ?>
  <div class="card">
    <?php if (!$activity): ?>
      <p class="empty"><?= owBi('Your activity will show here.', 'سيظهر نشاطك هنا.') ?></p>
    <?php else: ?>
    <ul class="act-list">
      <?php foreach ($activity as $a): $w = OW_ACTION_WORDS[$a['action']] ?? [ucfirst(str_replace('_', ' ', $a['action'])), $a['action']]; ?>
        <li><span><?= owBi($w[0], $w[1]) ?></span><span class="act-when"><?= $fmtDate($a['created_at'], true) ?></span></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>

<?php else: /* earnings */ ?>
  <p class="note"><?= owBi('Payments on Oweili open soon. When a work sells, Oweili keeps ' . owCommissionText() . ' (before VAT), which covers shipping and advertising. A work already sold keeps the rate it was sold at.', 'المدفوعات في أويلي قريباً. عند بيع عمل، يحتفظ أويلي بـ' . owCommissionText() . ' (قبل الضريبة) تغطي الشحن والإعلان. ويبقى العمل المباع سابقاً على النسبة التي بيع بها.') ?></p>
  <div class="stat-grid st-stats">
    <div class="stat-card"><span class="stat-num"><?= $sum['sold'] ?></span><span class="stat-lbl"><?= owBi('Works sold', 'أعمال مباعة') ?></span></div>
    <div class="stat-card"><span class="stat-num"><?= owArtistShareText($sum['sales']) ?></span><span class="stat-lbl"><?= owBi('Sales total (SAR)', 'إجمالي المبيعات (ر.س)') ?></span></div>
    <div class="stat-card"><span class="stat-num"><?= owArtistShareText($sum['share']) ?></span><span class="stat-lbl"><?= owBi('Your share, all time (SAR)', 'حصتك الإجمالية (ر.س)') ?></span></div>
    <div class="stat-card"><span class="stat-num"><?= owArtistShareText($sum['share_year']) ?></span><span class="stat-lbl"><?= owBi('Your share this year (SAR)', 'حصتك هذا العام (ر.س)') ?></span></div>
  </div>
  <div class="table-wrap" style="margin-top:var(--space-md)">
    <?php if (!$sales): ?>
      <div class="card empty"><p><?= owBi('No sales yet. Your sold works will appear here.', 'لا مبيعات بعد. ستظهر أعمالك المباعة هنا.') ?></p></div>
    <?php else: ?>
    <table class="table">
      <thead><tr><th><?= owBi('Artwork', 'العمل') ?></th><th><?= owBi('Sold on', 'تاريخ البيع') ?></th><th><?= owBi('Price', 'السعر') ?></th><th><?= owBi('Your share', 'حصتك') ?></th></tr></thead>
      <tbody><?php foreach ($sales as $s): $sRate = owCommissionAt((string) $s['sold_on']); ?>
        <tr><td><?= owBi((string) ($s['title_en'] ?: $s['title_ar']), (string) ($s['title_ar'] ?: $s['title_en'])) ?></td><td><?= $fmtDate($s['sold_on']) ?></td><td><b><?= number_format((float) $s['price']) ?></b> <?= owBi('SAR', 'ر.س') ?></td><td><b><?= owArtistShareText(owArtistShare((float) $s['price'], (string) $s['sold_on'])) ?></b> <?= owBi('SAR', 'ر.س') ?> <span class="st-sub" style="display:inline"><?= owBi('(after ' . owCommissionText($sRate) . ')', '(بعد ' . owCommissionText($sRate) . ')') ?></span></td></tr>
      <?php endforeach; ?></tbody>
    </table>
    <?php endif; ?>
  </div>
  <h2 style="font-size:var(--fs-subtitle);margin:var(--space-lg) 0 var(--space-sm)"><?= owBi('Payouts', 'المدفوعات') ?></h2>
  <?php if (!$payouts): ?>
    <div class="card empty"><p><?= owBi('No payouts yet. Once payments open, every transfer to you will be listed here.', 'لا مدفوعات بعد. حين تفتح المدفوعات، ستظهر هنا كل تحويلاتك.') ?></p></div>
  <?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th><?= owBi('Date', 'التاريخ') ?></th><th><?= owBi('Amount', 'المبلغ') ?></th><th><?= owBi('Status', 'الحالة') ?></th></tr></thead>
    <tbody><?php foreach ($payouts as $p): ?>
      <tr><td><?= $fmtDate($p['created_at']) ?></td><td><b><?= number_format((float) $p['amount'], 2) ?></b> <?= e($p['currency']) ?></td><td><?= e(ucfirst($p['status'])) ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php endif; ?>
<?php endif; ?>
</main>

<script>
function tgl(){
  var ar = document.documentElement.getAttribute('dir') !== 'rtl';
  document.documentElement.setAttribute('dir', ar ? 'rtl' : 'ltr');
  document.documentElement.setAttribute('lang', ar ? 'ar' : 'en');
}
(function(){ var v = document.querySelector('.bg-video'); if (!v) return;
  var on = function(){ v.classList.add('on'); }; if (v.readyState >= 3) on();
  v.addEventListener('canplay', on, {once:true}); v.addEventListener('playing', on, {once:true}); v.play().catch(function(){}); })();
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
