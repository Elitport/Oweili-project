<?php
/* =====================================================================
 *  collector_dashboard.php — the Buyer dashboard («لوحة المشتري»).
 *  Inside the database the account type is still "collector".
 *  Only the signed-in buyer's OWN data, never another person's (no ?id).
 *  Tabs (?tab=):
 *    requests   — every "I'm Interested" request with its status
 *    favorites  — artworks saved with the heart
 *    profile    — name, phone, country, city, preferred language
 *    account    — email and password
 *    purchases  — purchase history (payments come later, Sprint 4)
 *  Every change is checked on the server (CSRF, own account only) and
 *  written to the activity log.
 *  Tables: users, artwork_inquiries, favorites, artworks, purchases,
 *          sessions, activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/artwork_lib.php';
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/account_lib.php';

if (!isLoggedIn()) { header('Location: login.php?next=collector_dashboard.php'); exit; }
$me = currentUser();
if (!$me) { header('Location: login.php'); exit; }
/* Artists and staff belong on their own dashboards */
if (($me['role'] ?? '') !== 'collector') { header('Location: ' . dashboardUrl($me['role'] ?? '')); exit; }
$uid = (int) $me['id'];

$TABS = ['requests', 'favorites', 'profile', 'account', 'purchases'];
$tab  = in_array($_GET['tab'] ?? '', $TABS, true) ? $_GET['tab'] : 'requests';
$back = fn(string $t, string $msg) => 'collector_dashboard.php?tab=' . $t . '&done=' . $msg;

/* ---------------------------------------------------------------------
 *  Changes (POST, then back to the page so a refresh never repeats them)
 * ------------------------------------------------------------------- */
$err = ''; $errTab = '';
$old = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $do = (string) ($_POST['do'] ?? '');
    $errTab = ['profile' => 'profile', 'email' => 'account', 'password' => 'account', 'resend' => $tab, 'unfav' => 'favorites'][$do] ?? $tab;
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $err = 'csrf';
    } elseif ($do === 'profile') {
        $one = fn(string $k, int $max) => mb_substr(trim(preg_replace('/[\x00-\x1F\x7F<>]/u', ' ', (string) ($_POST[$k] ?? ''))), 0, $max);
        $old = ['name' => $one('full_name', 150), 'phone' => $one('phone', 30), 'country' => $one('country', 80),
                'city' => $one('city', 100), 'lang' => (string) ($_POST['preferred_lang'] ?? '') === 'ar' ? 'ar' : 'en'];
        if (mb_strlen($old['name']) < 2)                                                     $err = 'name';
        elseif ($old['phone'] !== '' && !preg_match('/^\+?[0-9][0-9 \-()]{6,29}$/', $old['phone'])) $err = 'phone';
        else {
            $set = 'full_name_en = :n, phone = :p, city = :c'; $args = [':n' => $old['name'], ':p' => $old['phone'] ?: null, ':c' => $old['city'] ?: null, ':id' => $uid];
            if (owHasColumn('users', 'country'))        { $set .= ', country = :co'; $args[':co'] = $old['country'] ?: null; }
            if (owHasColumn('users', 'preferred_lang')) { $set .= ', preferred_lang = :l'; $args[':l'] = $old['lang']; }
            getDB()->prepare("UPDATE users SET $set WHERE id = :id")->execute($args);
            owLog('profile_update', 'user', $uid, '', ['type' => 'buyer', 'id' => $uid]);
            header('Location: ' . $back('profile', 'profile') . '&lang=' . $old['lang']); exit;
        }
    } elseif ($do === 'email') {
        $err = owChangeEmail($me, (string) ($_POST['new_email'] ?? ''), (string) ($_POST['current_password'] ?? ''));
        $old = ['new_email' => (string) ($_POST['new_email'] ?? '')];
        if ($err === '') { header('Location: ' . $back('account', 'email')); exit; }
    } elseif ($do === 'password') {
        $err = owChangePassword($me, (string) ($_POST['current_password'] ?? ''), (string) ($_POST['new_password'] ?? ''), (string) ($_POST['confirm_password'] ?? ''));
        if ($err === '') { header('Location: ' . $back('account', 'password')); exit; }
    } elseif ($do === 'resend') {
        $err = owResendVerify($me);
        if ($err === '') { header('Location: ' . $back($tab, 'resend')); exit; }
    } elseif ($do === 'unfav') {               // the heart without JavaScript
        $aid = (int) ($_POST['artwork_id'] ?? 0);
        if ($aid > 0 && owFavSet($uid, $aid, false)) owLog('favorite_remove', 'artwork', $aid, '', ['type' => 'buyer', 'id' => $uid]);
        header('Location: ' . $back('favorites', 'unfav')); exit;
    }
    if ($err !== '') $tab = $errTab;
}

/* ---------------------------------------------------------------------
 *  This buyer's data
 * ------------------------------------------------------------------- */
$ready    = owPlatformReady();
$verified = (int) ($me['is_verified'] ?? 0) === 1;
$visible  = "(a.is_approved = 1 AND a.status IN ('available','auction')" . owNotDraftSql('a') . " AND COALESCE(u.is_banned,0) = 0)";

/* Requests: linked to this account, or (only once the email is confirmed)
   sent as a guest from the same email address */
$requests = [];
try {
    $statusCol = owHasColumn('artwork_inquiries', 'status') ? 'q.status' : "'new'";
    $who = [];
    $args = [];
    if (owHasColumn('artwork_inquiries', 'buyer_id')) { $who[] = 'q.buyer_id = :u'; $args[':u'] = $uid; }
    if ($verified) { $who[] = 'q.buyer_email = :e'; $args[':e'] = strtolower(trim((string) $me['email'])); }
    if ($who && owHasTable('artwork_inquiries')) {
        $st = getDB()->prepare(
            "SELECT q.id, q.created_at, $statusCol AS status, a.id AS aid, a.title_en, a.title_ar, a.image_url, a.price,
                    " . owArtistNameSql('u') . ", $visible AS visible
             FROM artwork_inquiries q
             LEFT JOIN artworks a ON a.id = q.artwork_id
             LEFT JOIN users u ON u.id = a.artist_id
             WHERE (" . implode(' OR ', $who) . ")
             ORDER BY q.created_at DESC, q.id DESC LIMIT 200");
        $st->execute($args);
        $requests = $st->fetchAll();
    }
} catch (Throwable $e) { error_log('buyer dashboard requests: ' . $e->getMessage()); }

$favorites = [];
try {
    if (owHasTable('favorites')) {
        $st = getDB()->prepare(
            "SELECT f.created_at AS saved_at, a.id, a.title_en, a.title_ar, a.image_url, a.price, a.type, a.artist_id,
                    " . owArtistNameSql('u') . ", $visible AS visible
             FROM favorites f
             JOIN artworks a ON a.id = f.artwork_id
             LEFT JOIN users u ON u.id = a.artist_id
             WHERE f.user_id = :u
             ORDER BY f.created_at DESC, f.id DESC LIMIT 200");
        $st->execute([':u' => $uid]);
        $favorites = $st->fetchAll();
    }
} catch (Throwable $e) { error_log('buyer dashboard favorites: ' . $e->getMessage()); }

$purchases = [];
try {
    $st = getDB()->prepare(
        "SELECT p.price, p.purchased_at, a.title_en, a.title_ar, a.image_url, " . owArtistNameSql('u') . "
         FROM purchases p JOIN artworks a ON a.id = p.artwork_id LEFT JOIN users u ON u.id = a.artist_id
         WHERE p.collector_id = :uid ORDER BY p.purchased_at DESC");
    $st->execute([':uid' => $uid]);
    $purchases = $st->fetchAll();
} catch (Throwable $e) { error_log('buyer dashboard purchases: ' . $e->getMessage()); }

$ART_TYPES = require __DIR__ . '/art_types.php';
$memberSince = !empty($me['created_at']) ? date('Y', strtotime($me['created_at'])) : '';
$name   = trim((string) ($me['full_name_en'] ?: $me['full_name_ar'])) ?: 'Buyer';
$val    = fn(string $k, string $col) => $old[$k] ?? (string) ($me[$col] ?? '');
$prefL  = $old['lang'] ?? ((string) ($me['preferred_lang'] ?? '') === 'ar' ? 'ar' : 'en');

$DONE = [
    'profile'  => ['Your profile is saved.', 'تم حفظ ملفك الشخصي.'],
    'email'    => ['Your new email is saved. Please open the link we sent to it to confirm it.', 'تم حفظ بريدك الجديد. افتح الرابط الذي أرسلناه إليه لتأكيده.'],
    'password' => ['Your password is changed. Other phones and computers were signed out.', 'تم تغيير كلمة المرور. وتم تسجيل الخروج من الأجهزة الأخرى.'],
    'resend'   => ['We sent the confirmation link again. Please check your inbox.', 'أرسلنا رابط التأكيد مرة أخرى. راجع بريدك.'],
    'unfav'    => ['Removed from your favorites.', 'أُزيل من المفضلة.'],
];
$done = $DONE[$_GET['done'] ?? ''] ?? null;

$heart = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 7.9 3.6 4.5 7 4.5c2 0 3.6 1.1 5 2.9 1.4-1.8 3-2.9 5-2.9 3.4 0 5.6 3.4 4.3 6.8-1.8 4.6-9.3 9.2-9.3 9.2z"/></svg>';
$tabLink = fn(string $t) => 'collector_dashboard.php?tab=' . $t;
$fmtDate = function (?string $d): string { [$en, $ar] = owDateBi($d); return owBi($en, $ar); };
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>My Dashboard · لوحتي · Oweili</title>
<meta name="description" content="Your requests, favorites and profile on Oweili. طلباتك ومفضلتك وملفك في أويلي.">
<?php include __DIR__ . '/theme_head.php'; ?>
<?php if (in_array($_GET['lang'] ?? '', ['en', 'ar'], true)): ?>
<script>/* the preferred language was just saved: use it from now on */ try { localStorage.setItem('lang', <?= json_encode($_GET['lang']) ?>); } catch (e) {}</script>
<?php endif; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
/* Buyer dashboard — layout only; cards, buttons, stats, tabs and tables come from oweili.css (role color: collector) */
.page{position:relative;z-index:10;}
.bd-head{display:flex;gap:var(--space-md);align-items:center;padding:var(--space-lg);}
.bd-avatar{width:72px;height:72px;border-radius:50%;flex-shrink:0;background:var(--color-collector);color:var(--color-on-accent);display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:700;background-size:cover;background-position:center;}
.bd-head h1{font-size:var(--fs-section);margin:0 0 4px;}
.bd-head p{margin:0;color:var(--color-body);}
.bd-head .head-actions{margin-inline-start:auto;display:flex;gap:var(--space-xs);flex-wrap:wrap;}
.bd-stats{grid-template-columns:repeat(3,1fr);margin-top:var(--space-md);}
.bd-stats a.stat-card{text-decoration:none;color:inherit;}
.bd-stats a.stat-card:hover{transform:translateY(-2px);}
.bd-tabs{margin:var(--space-lg) 0 var(--space-md);overflow-x:auto;white-space:nowrap;-webkit-overflow-scrolling:touch;}
.bd-verify{display:flex;align-items:center;justify-content:space-between;gap:var(--space-sm);flex-wrap:wrap;margin-top:var(--space-md);}
.bd-verify form{margin:0;}
.req-list{display:grid;gap:var(--space-sm);}
.req{display:flex;gap:var(--space-sm);align-items:center;padding:var(--space-sm);}
.req-thumb{position:relative;width:72px;height:72px;flex-shrink:0;border-radius:var(--radius-input);overflow:hidden;background:var(--color-frame);border:1px solid var(--color-border);}
.req-thumb img{width:100%;height:100%;object-fit:contain;display:block;}
.req-thumb img[hidden]{display:none;}
.req-body{flex:1;min-width:0;}
.req-title{font-weight:700;color:var(--color-dark);margin:0 0 2px;overflow:hidden;text-overflow:ellipsis;}
.req-meta{font-size:var(--fs-caption);color:var(--color-body);}
.req-side{display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex-shrink:0;}
.st-pill{display:inline-block;font-size:var(--fs-min);font-weight:700;padding:3px 10px;border-radius:var(--radius-pill);border:1px solid var(--color-border-strong);color:var(--color-dark);background:var(--color-surface);}
.st-pill.st-new{background:rgba(var(--rgb-primary),.16);border-color:rgba(var(--rgb-primary),.4);}
.st-pill.st-contacted,.st-pill.st-in_progress{background:rgba(var(--rgb-accent),.1);border-color:rgba(var(--rgb-accent),.35);}
.st-pill.st-closed{opacity:.8;}
.fav-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:var(--space-md);}
.fav-gone{font-size:var(--fs-caption);color:var(--color-body);}
.empty{text-align:center;padding:var(--space-xl) var(--space-md);}
.empty p{margin:0 0 var(--space-sm);}
.form-card{max-width:620px;padding:var(--space-lg);}
.form-card h2{font-size:var(--fs-subtitle);margin:0 0 var(--space-sm);}
.form-card + .form-card{margin-top:var(--space-md);}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 var(--space-sm);}
.lang-choice{display:flex;gap:var(--space-sm);flex-wrap:wrap;}
.lang-choice label{display:flex;align-items:center;gap:6px;font-weight:400;font-size:15px;}
.label-like{display:block;font-size:var(--fs-label);font-weight:700;color:var(--color-dark);margin-bottom:6px;}
.hint{font-size:var(--fs-caption);color:var(--color-body);margin:-4px 0 var(--space-sm);}
.pur-thumb{width:56px;height:56px;border-radius:var(--radius-input);object-fit:contain;background:var(--color-frame);border:1px solid var(--color-border);}
.pur-art{display:flex;align-items:center;gap:var(--space-sm);}
@media(max-width:1024px){.fav-grid{grid-template-columns:repeat(3,1fr);}}
@media(max-width:768px){.bd-head{flex-direction:column;text-align:center;padding:var(--space-md);}.bd-head .head-actions{margin:0;justify-content:center;}
  .bd-stats{grid-template-columns:repeat(3,1fr);gap:var(--space-xs);}.fav-grid{grid-template-columns:repeat(2,1fr);gap:var(--space-sm);}.form-grid{grid-template-columns:1fr;}
  .req{flex-wrap:wrap;align-items:flex-start;}.req-body{flex:1 1 0;}.req-side{flex-direction:row;align-items:center;justify-content:space-between;width:100%;}}
</style>
</head>
<body>
<div class="bg-wrap"><video preload="none" class="bg-video" autoplay loop muted playsinline data-src="<?= owBase() ?>bg.mp4"></video></div>
<div class="overlay"></div>

<?php include __DIR__ . '/nav.php'; ?>

<main class="page dash role-collector dash-wrap" id="pg">

  <div class="card bd-head">
    <div class="bd-avatar" aria-hidden="true"<?= ($pp = owImgSrc($me['profile_picture'] ?? '')) !== '' ? ' style="background-image:url(\'' . e(owImgSized($pp, 400)) . '\')"' : '' ?>><?= ($pp ?? '') === '' ? e(mb_strtoupper(mb_substr($name, 0, 1))) : '' ?></div>
    <div>
      <h1><?= owBi('Hello, ' . $name, 'أهلاً، ' . $name) ?></h1>
      <p><?= owBi('Your requests, favorites and account, all in one place.', 'طلباتك ومفضلتك وحسابك، في مكان واحد.') ?></p>
    </div>
    <div class="head-actions">
      <a class="btn btn-primary" href="marketplace.php"><?= owBi('Explore the Gallery', 'استكشف المعرض') ?></a>
    </div>
  </div>

  <?php if (!$verified): ?>
  <div class="alert alert-error bd-verify" role="status">
    <span><?= owBi('Please confirm your email address. We sent you a link when you joined.', 'يرجى تأكيد بريدك الإلكتروني. أرسلنا لك رابطاً عند التسجيل.') ?></span>
    <form method="post" action="<?= e($tabLink($tab)) ?>"><?= csrfField() ?><input type="hidden" name="do" value="resend">
      <button class="btn btn-secondary btn-sm" type="submit"><?= owBi('Send the link again', 'أرسل الرابط مجدداً') ?></button></form>
  </div>
  <?php endif; ?>

  <?php if ($done): ?><div class="alert alert-ok" role="status" style="margin-top:var(--space-md)"><?= owBi($done[0], $done[1]) ?></div><?php endif; ?>
  <?php if ($err !== ''): $m = OW_ACCOUNT_ERR[$err] ?? OW_ACCOUNT_ERR['server']; ?><div class="alert alert-error" role="alert" style="margin-top:var(--space-md)"><?= owBi($m[0], $m[1]) ?></div><?php endif; ?>

  <div class="stat-grid bd-stats">
    <a class="stat-card" href="<?= e($tabLink('requests')) ?>"><span class="stat-num"><?= count($requests) ?></span><span class="stat-lbl"><?= owBi('My requests', 'طلباتي') ?></span></a>
    <a class="stat-card" href="<?= e($tabLink('favorites')) ?>"><span class="stat-num"><?= count($favorites) ?></span><span class="stat-lbl"><?= owBi('Favorites', 'المفضلة') ?></span></a>
    <div class="stat-card"><span class="stat-num"><?= e($memberSince ?: '·') ?></span><span class="stat-lbl"><?= owBi('Member since', 'عضو منذ') ?></span></div>
  </div>

  <nav class="tabs bd-tabs" aria-label="Dashboard · اللوحة">
    <?php foreach (['requests' => ['My requests', 'طلباتي'], 'favorites' => ['Favorites', 'المفضلة'], 'profile' => ['Profile', 'الملف الشخصي'],
                    'account' => ['Email & password', 'البريد وكلمة المرور'], 'purchases' => ['Purchases', 'المشتريات']] as $t => $l): ?>
      <a class="tab<?= $tab === $t ? ' active' : '' ?>" href="<?= e($tabLink($t)) ?>"<?= $tab === $t ? ' aria-current="page"' : '' ?>><?= owBi($l[0], $l[1]) ?></a>
    <?php endforeach; ?>
  </nav>

<?php if ($tab === 'requests'): ?>
  <?php if (!$requests): ?>
    <div class="card empty">
      <p><?= owBi('No requests yet. When a work speaks to you, press “I’m Interested” and we will contact you.', 'لا طلبات بعد. حين يلامسك عمل، اضغط «أنا مهتم» وسنتواصل معك.') ?></p>
      <a class="btn btn-primary" href="marketplace.php"><?= owBi('Explore the Gallery', 'استكشف المعرض') ?></a>
    </div>
  <?php else: ?>
    <div class="req-list">
    <?php foreach ($requests as $r):
      $st = isset(OW_INQUIRY_STATUS[$r['status']]) ? $r['status'] : 'new';
      $img = owImgSrc($r['image_url'] ?? ''); $t400 = $img !== '' ? owImgSized($img, 400) : ''; ?>
      <div class="card req">
        <div class="req-thumb"><?php if ($img !== ''): ?><img loading="lazy" src="<?= e($t400) ?>" alt="<?= e(($r['title_en'] ?? '') . ' · ' . ($r['title_ar'] ?? '')) ?>"<?= owImgFallbackAttrs($img, $t400) ?>><?= owImgPlaceholder(true) ?><?php else: ?><?= owImgPlaceholder(false) ?><?php endif; ?></div>
        <div class="req-body">
          <p class="req-title"><?= $r['aid'] ? owBi((string) ($r['title_en'] ?: $r['title_ar']), (string) ($r['title_ar'] ?: $r['title_en'])) : owBi('This artwork was removed', 'تمت إزالة هذا العمل') ?></p>
          <div class="req-meta"><?php if ($r['aid']): ?><?= owBi('by ' . $r['artist_en'], 'بواسطة ' . $r['artist_ar']) ?> · <?php endif; ?><?= owBi('Sent', 'أُرسل') ?> <?= $fmtDate($r['created_at']) ?></div>
        </div>
        <div class="req-side">
          <span class="st-pill st-<?= e($st) ?>"><?= owBi(OW_INQUIRY_STATUS[$st][0], OW_INQUIRY_STATUS[$st][1]) ?></span>
          <?php if ($r['aid'] && (int) $r['visible'] === 1): ?><a class="btn btn-ghost btn-sm" href="marketplace.php?show=<?= (int) $r['aid'] ?>#artwork-<?= (int) $r['aid'] ?>"><?= owBi('See the artwork', 'شاهد العمل') ?></a><?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php elseif ($tab === 'favorites'): ?>
  <?php if (!$favorites): ?>
    <div class="card empty">
      <p><?= owBi('Nothing saved yet. Tap the heart on any artwork in the Gallery to keep it here.', 'لا شيء محفوظ بعد. اضغط القلب على أي عمل في المعرض ليبقى هنا.') ?></p>
      <a class="btn btn-primary" href="marketplace.php"><?= owBi('Explore the Gallery', 'استكشف المعرض') ?></a>
    </div>
  <?php else: ?>
    <div class="fav-grid">
    <?php foreach ($favorites as $f):
      $fid = (int) $f['id']; $img = owImgSrc($f['image_url'] ?? '');
      $tEn = (string) ($f['title_en'] ?: $f['title_ar']); $tAr = (string) ($f['title_ar'] ?: $f['title_en']); $tl = $ART_TYPES[$f['type']] ?? null; ?>
      <article class="aw-card fav-item" data-card="<?= $fid ?>">
        <div class="aw-img">
          <?= owArtImgHtml($img, $tEn . ' · ' . $tAr) ?>
          <form method="post" action="<?= e($tabLink('favorites')) ?>"><?= csrfField() ?><input type="hidden" name="do" value="unfav"><input type="hidden" name="artwork_id" value="<?= $fid ?>">
            <button type="submit" class="fav-btn" data-fav="<?= $fid ?>" aria-pressed="true" aria-label="Remove from favorites · أزل من المفضلة"><?= $heart ?></button></form>
        </div>
        <div class="aw-body">
          <h3 class="aw-title"><?= owBi($tEn, $tAr) ?></h3>
          <span class="aw-artist"><?= owBi('by ' . $f['artist_en'], 'بواسطة ' . $f['artist_ar']) ?></span>
          <?php if ($tl): ?><span class="type-pill"><?= owBi($tl['en'], $tl['ar']) ?></span><?php endif; ?>
          <div class="aw-price"><?= number_format((float) $f['price']) ?> <?= owBi('SAR', 'ر.س') ?></div>
          <?php if ((int) $f['visible'] === 1): ?>
            <a class="btn btn-dark btn-block btn-sm" href="marketplace.php?show=<?= $fid ?>#artwork-<?= $fid ?>"><?= owBi('See the Story', 'شاهد القصة') ?></a>
          <?php else: ?>
            <span class="fav-gone"><?= owBi('No longer available', 'لم يعد متاحاً') ?></span>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php elseif ($tab === 'profile'): ?>
  <form class="card form-card" method="post" action="<?= e($tabLink('profile')) ?>" novalidate>
    <?= csrfField() ?><input type="hidden" name="do" value="profile">
    <h2><?= owBi('My profile', 'ملفي الشخصي') ?></h2>
    <p class="hint"><?= owBi('Only the Oweili team sees these details. We use them to reach you about the works you ask for.', 'لا يرى هذه البيانات إلا فريق أويلي. نستخدمها للتواصل معك بخصوص الأعمال التي تطلبها.') ?></p>
    <div class="field<?= $err === 'name' ? ' has-error' : '' ?>"><label for="p-name"><?= owBi('Full name', 'الاسم الكامل') ?></label>
      <input id="p-name" name="full_name" type="text" required maxlength="150" autocomplete="name" value="<?= e($val('name', 'full_name_en')) ?>"></div>
    <div class="field<?= $err === 'phone' ? ' has-error' : '' ?>"><label for="p-phone"><?= owBi('Phone number', 'رقم الهاتف') ?></label>
      <input id="p-phone" name="phone" type="tel" maxlength="30" autocomplete="tel" inputmode="tel" dir="ltr" value="<?= e($val('phone', 'phone')) ?>"></div>
    <div class="form-grid">
      <div class="field"><label for="p-country"><?= owBi('Country', 'الدولة') ?></label>
        <input id="p-country" name="country" type="text" maxlength="80" autocomplete="country-name" list="countries" value="<?= e($val('country', 'country')) ?>">
        <datalist id="countries"><?php foreach (OW_COUNTRIES as $cEn => $cAr): ?><option value="<?= e($cEn) ?>"><?= e($cAr) ?></option><?php endforeach; ?></datalist></div>
      <div class="field"><label for="p-city"><?= owBi('City', 'المدينة') ?></label>
        <input id="p-city" name="city" type="text" maxlength="100" autocomplete="address-level2" value="<?= e($val('city', 'city')) ?>"></div>
    </div>
    <div class="field"><span class="label-like"><?= owBi('Preferred language', 'اللغة المفضلة') ?></span>
      <div class="lang-choice">
        <label><input type="radio" name="preferred_lang" value="en"<?= $prefL === 'en' ? ' checked' : '' ?>> English</label>
        <label><input type="radio" name="preferred_lang" value="ar"<?= $prefL === 'ar' ? ' checked' : '' ?>> العربية</label>
      </div></div>
    <p class="hint"><?= owBi('Email: ', 'البريد: ') ?><span dir="ltr"><?= e($me['email']) ?></span> · <a href="<?= e($tabLink('account')) ?>"><?= owBi('Change it', 'غيّره') ?></a></p>
    <button class="btn btn-primary" type="submit"><?= owBi('Save', 'حفظ') ?></button>
  </form>

<?php elseif ($tab === 'account'): ?>
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

<?php else: /* purchases */ ?>
  <div class="table-wrap">
    <?php if (!$purchases): ?>
      <div class="card empty"><p><?= owBi('No purchases yet. Your collection will appear here.', 'لا مشتريات بعد. ستظهر مجموعتك هنا.') ?></p>
        <a class="btn btn-primary" href="marketplace.php"><?= owBi('Explore the Gallery', 'استكشف المعرض') ?></a></div>
    <?php else: ?>
      <table class="table">
        <thead><tr><th><?= owBi('Artwork', 'العمل الفني') ?></th><th><?= owBi('Artist', 'الفنان') ?></th><th><?= owBi('Date', 'التاريخ') ?></th><th><?= owBi('Price', 'السعر') ?></th></tr></thead>
        <tbody>
        <?php foreach ($purchases as $p): $pImg = owImgSrc($p['image_url'] ?? ''); $p4 = $pImg !== '' ? owImgSized($pImg, 400) : ''; ?>
          <tr>
            <td><div class="pur-art"><?php if ($pImg !== ''): ?><img class="pur-thumb" loading="lazy" src="<?= e($p4) ?>" alt="<?= e(($p['title_en'] ?? '') . ' · ' . ($p['title_ar'] ?? '')) ?>"<?= owImgFallbackAttrs($pImg, $p4) ?>><?php endif; ?>
              <strong><?= owBi((string) ($p['title_en'] ?: $p['title_ar']), (string) ($p['title_ar'] ?: $p['title_en'])) ?></strong></div></td>
            <td><?= owBi((string) $p['artist_en'], (string) $p['artist_ar']) ?></td>
            <td><?= $fmtDate($p['purchased_at']) ?></td>
            <td><strong><?= number_format((float) $p['price']) ?></strong> <?= owBi('SAR', 'ر.س') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
<?php endif; ?>

</main>

<script>
/* Language: the page shows English or Arabic by direction (bi-en / bi-ar);
   nav.php remembers the choice */
function tgl(){
  var ar = document.documentElement.getAttribute('dir') !== 'rtl';
  document.documentElement.setAttribute('dir', ar ? 'rtl' : 'ltr');
  document.documentElement.setAttribute('lang', ar ? 'ar' : 'en');
}
(function(){ var v = document.querySelector('.bg-video'); if (!v) return;
  var on = function(){ v.classList.add('on'); }; if (v.readyState >= 3) on();
  v.addEventListener('canplay', on, {once:true}); v.addEventListener('playing', on, {once:true}); v.play().catch(function(){}); })();
window.OW_FAV = {
  buyer: true, csrf: <?= json_encode(csrfToken()) ?>,
  ids: <?= json_encode(array_map(fn($f) => (string) $f['id'], $favorites)) ?>,
  labels: { on: 'Save to favorites · احفظ في المفضلة', off: 'Remove from favorites · أزل من المفضلة' },
  changed: function(id, on){
    if (on) return;
    var card = document.querySelector('[data-card="' + id + '"]');
    if (card) { card.style.transition = 'opacity .25s'; card.style.opacity = '0'; setTimeout(function(){ card.remove(); }, 260); }
  }
};
</script>
<script src="<?= owAsset('assets/js/favorites.js') ?>"></script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
