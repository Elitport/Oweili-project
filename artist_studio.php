<?php
/* =====================================================================
 *  artist_studio.php — "My Studio" («مرسمي»), the artist's private area.
 *  Linked from the artist dashboard (artist_dashboard.php). Only the
 *  signed-in artist's OWN data (no ?id); everyone else is sent away.
 *  Tabs (?tab=):
 *    works     — every artwork with its status (draft, in review, approved,
 *                not approved + the reason, sold) and its numbers: views,
 *                favorites, "I'm Interested" requests. Never buyer names,
 *                phones or emails: Oweili is the middle party.
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

if (!isLoggedIn()) { header('Location: login.php?next=artist_studio.php'); exit; }
$me = currentUser();
if (!$me) { header('Location: login.php'); exit; }
if (($me['role'] ?? '') !== 'artist') { header('Location: ' . dashboardUrl($me['role'] ?? '')); exit; }
$uid = (int) $me['id'];

$TABS = ['works', 'account', 'activity', 'earnings'];
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
        "SELECT a.id, a.title_en, a.title_ar, a.image_url, a.price, a.status AS sale_status, a.created_at,
                " . owArtworkStatusSql('a') . " AS state, $reason AS reason,
                $viewSql AS views, $favSql AS favs, $inqSql AS inquiries
         FROM artworks a WHERE a.artist_id = :u ORDER BY a.created_at DESC, a.id DESC");
    $st->execute([':u' => $uid]);
    $works = $st->fetchAll();
} catch (Throwable $e) { error_log('artist_studio works: ' . $e->getMessage()); }

$tot = ['views' => 0, 'favs' => 0, 'inquiries' => 0];
foreach ($works as $w) foreach ($tot as $k => $_) $tot[$k] += (int) $w[$k];

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
$salesTotal = array_sum(array_map(fn($s) => (float) $s['price'], $sales));

$DONE = [
    'email'    => ['Your new email is saved. Please open the link we sent to it to confirm it.', 'تم حفظ بريدك الجديد. افتح الرابط الذي أرسلناه إليه لتأكيده.'],
    'password' => ['Your password is changed. Other phones and computers were signed out.', 'تم تغيير كلمة المرور. وتم تسجيل الخروج من الأجهزة الأخرى.'],
    'notify'   => ['Your email choices are saved.', 'تم حفظ اختياراتك للرسائل.'],
    'resend'   => ['We sent the confirmation link again. Please check your inbox.', 'أرسلنا رابط التأكيد مرة أخرى. راجع بريدك.'],
    'waiting'  => ['Saved. Your changes are waiting for the team’s approval. Visitors see the approved version until then.', 'تم الحفظ. تعديلاتك بانتظار موافقة الفريق. يرى الزوار النسخة المعتمدة حتى ذلك الحين.'],
    'saved'    => ['Your changes are saved.', 'تم حفظ تعديلاتك.'],
];
$myChanges = owMyChanges($uid);
$done = $DONE[$_GET['done'] ?? ''] ?? null;
$verified = (int) ($me['is_verified'] ?? 0) === 1;
$name = owRealName($me['artist_name']) ?: (owRealName($me['full_name_en']) ?: 'Artist');
$fmtDate = function (?string $d, bool $time = false): string { [$en, $ar] = owDateBi($d, $time); return owBi($en, $ar); };
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
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
.st-tabs{margin:var(--space-lg) 0 var(--space-md);}
.note{font-size:var(--fs-caption);color:var(--color-body);margin:0 0 var(--space-sm);}
.w-list{display:grid;gap:var(--space-sm);}
.w-row{display:grid;grid-template-columns:72px 1fr auto;gap:var(--space-sm);align-items:center;padding:var(--space-sm);}
.w-thumb{position:relative;width:72px;height:72px;border-radius:var(--radius-input);overflow:hidden;background:var(--color-frame);border:1px solid var(--color-border);}
.w-thumb img{width:100%;height:100%;object-fit:contain;display:block;}
.w-thumb img[hidden]{display:none;}
.w-title{font-weight:700;color:var(--color-dark);margin:0 0 4px;}
.w-nums{display:flex;gap:var(--space-sm);flex-wrap:wrap;font-size:var(--fs-caption);color:var(--color-body);}
.w-nums b{color:var(--color-dark);}
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
  .w-row{grid-template-columns:56px 1fr;}.w-thumb{width:56px;height:56px;}.w-side{grid-column:1 / -1;flex-direction:row;justify-content:space-between;align-items:center;}}
</style>
</head>
<body>
<div class="bg-wrap"><video preload="none" class="bg-video" autoplay loop muted playsinline data-src="<?= owBase() ?>bg.mp4"></video></div>
<div class="overlay"></div>
<?php include __DIR__ . '/nav.php'; ?>

<main class="page dash role-artist dash-wrap" id="pg">
  <div class="card st-head">
    <div>
      <h1><?= owBi('My Studio', 'مرسمي') ?></h1>
      <p><?= owBi('Welcome back, ' . $name . '. Here is how your work is doing.', 'أهلاً بعودتك يا ' . $name . '. هذا حال أعمالك.') ?></p>
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

  <div class="stat-grid st-stats">
    <div class="stat-card"><span class="stat-num"><?= count($works) ?></span><span class="stat-lbl"><?= owBi('Artworks', 'الأعمال') ?></span></div>
    <div class="stat-card"><span class="stat-num"><?= number_format($tot['views']) ?></span><span class="stat-lbl"><?= owBi('Views', 'المشاهدات') ?></span></div>
    <div class="stat-card"><span class="stat-num"><?= number_format($tot['favs']) ?></span><span class="stat-lbl"><?= owBi('Favorites', 'الإعجابات') ?></span></div>
    <div class="stat-card"><span class="stat-num"><?= number_format($tot['inquiries']) ?></span><span class="stat-lbl"><?= owBi('Interested buyers', 'مهتمون بالشراء') ?></span></div>
  </div>

  <nav class="tabs st-tabs" aria-label="Studio · المرسم">
    <?php foreach (['works' => ['My artworks', 'أعمالي'], 'account' => ['Account', 'الحساب'], 'activity' => ['Activity', 'النشاط'], 'earnings' => ['Sales & payouts', 'المبيعات والمدفوعات']] as $t => $l): ?>
      <a class="tab<?= $tab === $t ? ' active' : '' ?>" href="<?= e($tabLink($t)) ?>"<?= $tab === $t ? ' aria-current="page"' : '' ?>><?= owBi($l[0], $l[1]) ?></a>
    <?php endforeach; ?>
  </nav>

<?php if ($tab === 'works'): ?>
  <p class="note"><?= owBi('Oweili is the middle party: we talk to buyers for you and keep their details private. You see how many people are interested, never who they are.', 'أويلي هو الوسيط: نتواصل مع المشترين نيابة عنك ونحفظ بياناتهم. ترى عدد المهتمين، لا هوياتهم.') ?></p>
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
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <p class="note" style="margin-top:var(--space-sm)"><?= owBi('To delete an artwork, open your dashboard and press Delete under it.', 'لحذف عمل، افتح لوحتك واضغط «حذف» تحته.') ?> <a href="artist_dashboard.php"><?= owBi('Open dashboard', 'افتح اللوحة') ?></a></p>
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
  <p class="note"><?= owBi('Payments on Oweili open soon. When a work sells, Oweili keeps 15% (before VAT), which covers shipping and advertising.', 'المدفوعات في أويلي قريباً. عند بيع عمل، يحتفظ أويلي بـ15% (قبل الضريبة) تغطي الشحن والإعلان.') ?></p>
  <div class="stat-grid st-stats" style="grid-template-columns:repeat(2,1fr)">
    <div class="stat-card"><span class="stat-num"><?= count($sales) ?></span><span class="stat-lbl"><?= owBi('Works sold', 'أعمال مباعة') ?></span></div>
    <div class="stat-card"><span class="stat-num"><?= number_format($salesTotal) ?></span><span class="stat-lbl"><?= owBi('Sales total (SAR)', 'إجمالي المبيعات (ر.س)') ?></span></div>
  </div>
  <div class="table-wrap" style="margin-top:var(--space-md)">
    <?php if (!$sales): ?>
      <div class="card empty"><p><?= owBi('No sales yet. Your sold works will appear here.', 'لا مبيعات بعد. ستظهر أعمالك المباعة هنا.') ?></p></div>
    <?php else: ?>
    <table class="table">
      <thead><tr><th><?= owBi('Artwork', 'العمل') ?></th><th><?= owBi('Sold on', 'تاريخ البيع') ?></th><th><?= owBi('Price', 'السعر') ?></th></tr></thead>
      <tbody><?php foreach ($sales as $s): ?>
        <tr><td><?= owBi((string) ($s['title_en'] ?: $s['title_ar']), (string) ($s['title_ar'] ?: $s['title_en'])) ?></td><td><?= $fmtDate($s['sold_on']) ?></td><td><b><?= number_format((float) $s['price']) ?></b> <?= owBi('SAR', 'ر.س') ?></td></tr>
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
