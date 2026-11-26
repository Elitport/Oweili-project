<?php
/* =====================================================================
 *  collector_dashboard.php — the collector dashboard («لوحة المقتني»), rebuilt
 *  in the calm style with a side menu like the owner dashboard (owner, 24 Nov 2026).
 *  Inside the database the account type is still "collector".
 *  Only the signed-in collector's OWN data, never another person's (no ?id).
 *  Pages (?tab=): home (Bento) · collection (every artwork bought, with its
 *  certificate, QR and receipt photos) · orders · bookings (courses and tickets)
 *  · requests ("I'm Interested", gifts, private commissions) · favorites ·
 *  following (artists they follow) · messages (group messages from Oweili) ·
 *  complaints · profile · account (email and password).
 *  Old links keep working: no tab = home, ?tab=purchases = collection.
 *  Orders and complaints show only once payments are switched on (owPaymentsOn).
 *  Every change is checked on the server (CSRF, own account only) and written
 *  to the activity log. Data: collector_lib.php
 *  Tables: users, artwork_inquiries, favorites, artworks, purchases, orders,
 *          order_complaints, certificates, artist_follows, course_registrations,
 *          courses, museum_tickets, sent_messages, gift_requests, commissions,
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
/* institute and museum accounts (booking_lib.php, 14 Nov 2026) have their own dashboards; dashboardUrl() in config.php sends them here */
if (in_array($me['role'] ?? '', ['institute', 'museum'], true)) { header('Location: ' . ($me['role'] === 'institute' ? 'institute_dashboard.php' : 'museum_dashboard.php')); exit; }
if (($me['role'] ?? '') !== 'collector') { header('Location: ' . dashboardUrl($me['role'] ?? '')); exit; }
$uid = (int) $me['id'];

require_once __DIR__ . '/collector_lib.php';
require_once __DIR__ . '/order_lib.php';
require_once __DIR__ . '/gallery_lib.php';
/* Pages (?tab=). Old links keep working: no tab = Home (was My requests), purchases = My collection */
$payOn  = owPaymentsOn();                         // orders and complaints show only once payments are switched on
$TABS = ['home', 'collection', 'orders', 'bookings', 'requests', 'favorites', 'following', 'messages', 'complaints', 'profile', 'account'];
$tabIn = (string) ($_GET['tab'] ?? '');
if ($tabIn === 'purchases') $tabIn = 'collection';
$tab  = in_array($tabIn, $TABS, true) ? $tabIn : 'home';
if (!$payOn && in_array($tab, ['orders', 'complaints'], true)) $tab = 'home';
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
        $old = ['name' => $one('full_name', 150), 'phone' => $one('phone', 30), 'country' => owCountryEn($one('country', 80)),
                'city' => $one('city', 100), 'lang' => (string) ($_POST['preferred_lang'] ?? '') === 'ar' ? 'ar' : 'en'];
        if (mb_strlen($old['name']) < 2 || owRealName($old['name']) === '')                     $err = 'name';
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
$name   = owRealName($me['full_name_en']) ?: (owRealName($me['full_name_ar']) ?: 'Collector');
$nameAr = owRealName($me['full_name_ar']) ?: (owRealName($me['full_name_en']) ?: 'مقتن');   // the right name for each language (owner, 24 Nov 2026)
$val    = fn(string $k, string $col) => $old[$k] ?? (string) ($me[$col] ?? '');
$prefL  = $old['lang'] ?? ((string) ($me['preferred_lang'] ?? '') === 'ar' ? 'ar' : 'en');

$DONE = [
    'profile'  => ['Your profile is saved', 'تم حفظ ملفك الشخصي'],
    'email'    => ['Your new email is saved. Please open the link we sent to it to confirm it.' . ' ' . OW_JUNK_HINT[0], 'تم حفظ بريدك الجديد. افتح الرابط الذي أرسلناه إليه لتأكيده.' . ' ' . OW_JUNK_HINT[1]],
    'password' => ['Your password is changed. Other phones and computers were signed out.', 'تم تغيير كلمة المرور. وتم تسجيل الخروج من الأجهزة الأخرى.'],
    'resend'   => ['We sent the confirmation link again. Please check your inbox.' . ' ' . OW_JUNK_HINT[0], 'أرسلنا رابط التأكيد مرة أخرى. راجع بريدك.' . ' ' . OW_JUNK_HINT[1]],
    'unfav'    => ['Removed from your favorites', 'أُزيل من المفضلة'],
    'follow'   => ['You follow this artist now', 'أصبحت تتابع هذا الفنان'],
    'unfollow' => ['You no longer follow this artist', 'لم تعد تتابع هذا الفنان'],
];
$done = $DONE[$_GET['done'] ?? ''] ?? null;

$heart = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 7.9 3.6 4.5 7 4.5c2 0 3.6 1.1 5 2.9 1.4-1.8 3-2.9 5-2.9 3.4 0 5.6 3.4 4.3 6.8-1.8 4.6-9.3 9.2-9.3 9.2z"/></svg>';
$tabLink = fn(string $t) => 'collector_dashboard.php?tab=' . $t;
$fmtDate = function (?string $d): string { [$en, $ar] = owDateBi($d); return owBi($en, $ar); };

/* ---------------------------------------------------------------------
 *  More of this collector's own data (collector_lib.php)
 * ------------------------------------------------------------------- */
$safe = function (callable $f, $empty = []) { try { return $f(); } catch (Throwable $e) { error_log('collector dashboard: ' . $e->getMessage()); return $empty; } };
$collection = $safe(fn() => owCollection($uid));
$orders     = $payOn ? $safe(fn() => owCollectorOrders($uid)) : [];
$complaints = $payOn ? $safe(fn() => owCollectorComplaints($uid)) : [];
$bookings   = $safe(fn() => owCollectorBookings($uid, (string) $me['email'], $verified));
$tickets    = $safe(fn() => owCollectorTickets($uid));
$messages   = $safe(fn() => owCollectorMessages($uid));
$following  = $safe(fn() => owFollowedArtists($uid));
$newWorks   = $safe(fn() => owFollowNewWorks($uid, 8));
require_once __DIR__ . '/booking_lib.php';
$showBookings = owCoursesOn() || $bookings || $tickets;
if ($tab === 'bookings' && !$showBookings) $tab = 'home';

/* What needs the collector now */
$needs = [];
foreach ($orders as $o) {
    if ($o['status'] === 'delivered' && empty($o['confirmed_at']))
        $needs[] = ['my_orders.php?o=' . (int) $o['id'], ['Confirm you received ' . $o['title_en'] . ' and add its photos', 'أكد استلام ' . $o['title_ar'] . ' وأضف صوره'], true];
}
foreach ($bookings as $b) {
    if (owRegIsBooking($b) && (float) $b['amount'] > 0 && $b['status'] !== 'cancelled')
        $needs[] = [$tabLink('bookings'), ['Your seat in ' . ($b['title_en'] ?: $b['title_ar']) . ' is booked. We will contact you to pay', 'مقعدك في ' . ($b['title_ar'] ?: $b['title_en']) . ' محجوز. سنتواصل معك للدفع'], false];
}
if (!$verified) $needs[] = [$tabLink('account'), ['Please confirm your email address', 'يرجى تأكيد بريدك الإلكتروني'], true];
$openOrders = array_values(array_filter($orders, fn($o) => in_array(owBuyerOrderStep($o), ['created', 'paid', 'shipped', 'delivered', 'complaint'], true)));

$ICON = [
 'home' => '<path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/>',
 'frame' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 16l5-5 4 4 3-3 6 6"/>',
 'cart' => '<path d="M3 4h2l2.5 11h11L21 7H6"/><circle cx="9" cy="19" r="1.5"/><circle cx="17" cy="19" r="1.5"/>',
 'ticket' => '<path d="M3 8a2 2 0 0 0 0 4v0a2 2 0 0 1 0 4v2h18v-2a2 2 0 0 1 0-4 2 2 0 0 0 0-4V6H3z"/>',
 'chat' => '<path d="M4 5h16v11H8l-4 4z"/>',
 'heart' => '<path d="M12 20s-7-4.4-9-9a5 5 0 0 1 9-3 5 5 0 0 1 9 3c-2 4.6-9 9-9 9z"/>',
 'artist' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/>',
 'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
 'flag' => '<path d="M5 21V4M5 4h11l-2 4 2 4H5"/>',
 'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/>',
 'lock' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
 'gallery' => '<path d="M4 20V9l8-5 8 5v11"/><path d="M9 20v-6h6v6"/>',
 'out' => '<path d="M15 4h4v16h-4"/><path d="M10 8l-4 4 4 4M6 12h10"/>',
 'cert' => '<circle cx="12" cy="9" r="5"/><path d="M9 13.5L8 21l4-2 4 2-1-7.5"/>',
];
$icon = fn(string $k): string => '<svg class="o-ic" viewBox="0 0 24 24" aria-hidden="true">' . ($ICON[$k] ?? '') . '</svg>';
/* [tab, English, Arabic, icon, shown] */
$MENU = [
  [['Home', 'الرئيسية'], [['home', 'Home', 'الرئيسية', 'home', true]]],
  [['My art', 'مقتنياتي'], [
     ['collection', 'My collection', 'مجموعتي', 'frame', true],
     ['orders', 'Orders', 'الطلبات', 'cart', $payOn],
     ['bookings', 'Courses and bookings', 'الدورات والحجوزات', 'ticket', $showBookings],
     ['requests', 'My requests', 'طلباتي', 'chat', true]]],
  [['Following', 'المتابعة'], [
     ['favorites', 'Favourites', 'المفضلة', 'heart', true],
     ['following', 'Artists I follow', 'فنانون أتابعهم', 'artist', true]]],
  [['Messages', 'الرسائل'], [
     ['messages', 'Messages from Oweili', 'رسائل من أويلي', 'mail', true],
     ['complaints', 'Complaints', 'الشكاوى', 'flag', $payOn]]],
  [['Account', 'الحساب'], [
     ['profile', 'Profile', 'الملف الشخصي', 'user', true],
     ['account', 'Email and password', 'البريد وكلمة المرور', 'lock', true]]],
];
$TITLE = ['home' => ['My dashboard', 'لوحتي'], 'collection' => ['My collection', 'مجموعتي'], 'orders' => ['Orders', 'الطلبات'],
          'bookings' => ['Courses and bookings', 'الدورات والحجوزات'], 'requests' => ['My requests', 'طلباتي'], 'favorites' => ['Favourites', 'المفضلة'],
          'following' => ['Artists I follow', 'فنانون أتابعهم'], 'messages' => ['Messages from Oweili', 'رسائل من أويلي'], 'complaints' => ['Complaints', 'الشكاوى'],
          'profile' => ['Profile', 'الملف الشخصي'], 'account' => ['Email and password', 'البريد وكلمة المرور']];
$tLine = function (array $r): array { $en = (string) ($r['title_en'] ?: $r['title_ar']); $ar = (string) ($r['title_ar'] ?: $r['title_en']); return [$en, $ar]; };
$money = fn($n): string => '<bdi dir="ltr">' . number_format((float) $n) . '</bdi> <span class="c-cur">' . owBi('SAR', 'ر.س') . '</span>';
$thumb = function (string $raw, string $alt, string $cls = 'c-thumb'): string {
    $img = owImgSrc($raw); $s = $img !== '' ? owImgSized($img, 400) : '';
    return '<span class="' . $cls . '">' . ($img !== '' ? '<img loading="lazy" src="' . e($s) . '" alt="' . e($alt) . '"' . owImgFallbackAttrs($img, $s) . '>' . owImgPlaceholder(true) : owImgPlaceholder(false)) . '</span>';
};
/* the small progress line of an order */
$steps = function (array $o): string {
    $now = owBuyerOrderStep($o);
    if (!in_array($now, OW_BUYER_ORDER_STEPS, true)) return '';
    $at = array_search($now, OW_BUYER_ORDER_STEPS, true);
    $h = '<ol class="c-steps" aria-label="' . (owIsAr() ? 'مراحل الطلب' : 'Order steps') . '">';
    foreach (OW_BUYER_ORDER_STEPS as $i => $s) {
        $w = $s === 'paid' ? ['Paid, being prepared', 'مدفوع، قيد التجهيز'] : OW_BUYER_ORDER_WORDS[$s];
        $h .= '<li class="' . ($i < $at ? 'done' : ($i === $at ? 'now' : '')) . '"' . ($i === $at ? ' aria-current="step"' : '') . '><span class="c-dot" aria-hidden="true"></span>' . owBi($w[0], $w[1]) . '</li>';
    }
    return $h . '</ol>';
};
?>
<!DOCTYPE html>
<html <?php require_once __DIR__ . '/lang_lib.php'; owHtmlAttrs(); ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title><?= e(owIsAr() ? $TITLE[$tab][1] . ' · أويلي' : $TITLE[$tab][0] . ' · Oweili') ?></title>
<meta name="description" content="Your collection, orders, requests and favourites on Oweili. مجموعتك وطلباتك ومفضلتك في أويلي">
<?php include __DIR__ . '/theme_head.php'; ?>
<?php if (in_array($_GET['lang'] ?? '', ['en', 'ar'], true)): ?>
<script>/* the preferred language was just saved: use it from now on */ try { localStorage.setItem('lang', <?= json_encode($_GET['lang']) ?>); } catch (e) {}</script>
<?php endif; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/owner.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/collector.css') ?>">
<script>try{if(localStorage.getItem('ow_side')==='mini')document.documentElement.classList.add('o-mini');}catch(e){}</script>
</head>
<body class="o-body c-body role-collector">
<div class="o-shell">
  <aside class="o-side" id="o-side" aria-label="<?= owIsAr() ? 'قائمة لوحتي' : 'My dashboard menu' ?>">
    <div class="o-side-top">
      <a class="o-brand" href="index.php"><span class="o-logo" aria-hidden="true"></span><b>OWEILI</b></a>
      <button type="button" class="o-collapse" id="o-collapse" aria-label="<?= owIsAr() ? 'تصغير القائمة' : 'Collapse the menu' ?>" aria-pressed="false"><svg class="o-ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg></button>
    </div>
    <nav class="o-nav">
      <?php foreach ($MENU as [$gName, $items]): $vis = array_values(array_filter($items, fn($it) => $it[4])); if (!$vis) continue; ?>
        <div class="o-group">
          <p class="o-gname"><?= owBi($gName[0], $gName[1]) ?></p>
          <?php foreach ($vis as $it): $on = $it[0] === $tab; ?>
            <a class="o-link<?= $on ? ' on' : '' ?>" href="<?= e($tabLink($it[0])) ?>"<?= $on ? ' aria-current="page"' : '' ?> title="<?= e(owIsAr() ? $it[2] : $it[1]) ?>"><?= $icon($it[3]) ?><span class="o-lt"><?= owBi($it[1], $it[2]) ?></span></a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </nav>
    <div class="o-side-foot">
      <a class="o-link" href="marketplace.php" title="<?= owIsAr() ? 'المعرض' : 'Gallery' ?>"><?= $icon('gallery') ?><span class="o-lt"><?= owBi('Gallery', 'المعرض') ?></span></a>
      <a class="o-link" href="logout.php" title="<?= owIsAr() ? 'تسجيل الخروج' : 'Sign out' ?>"><?= $icon('out') ?><span class="o-lt"><?= owBi('Sign out', 'تسجيل الخروج') ?></span></a>
    </div>
  </aside>
  <div class="o-scrim" id="o-scrim" hidden></div>
  <div class="o-main">
    <header class="o-top">
      <button type="button" class="o-burger" id="o-burger" aria-controls="o-side" aria-expanded="false" aria-label="<?= owIsAr() ? 'القائمة' : 'Menu' ?>"><svg class="o-ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
      <h1 class="o-title"><?= owBi($TITLE[$tab][0], $TITLE[$tab][1]) ?></h1>
      <div class="o-top-r"><a class="o-site" href="index.php"><?= owBi('View the site', 'عرض الموقع') ?></a><?php owCornerControls('owLang()'); ?></div>
    </header>
    <main class="o-content c-content" id="o-content">

  <?php if ($done): ?><div class="o-flash" role="status"><?= owBi($done[0], $done[1]) ?></div><?php endif; ?>
  <?php if ($err !== ''): $m = OW_ACCOUNT_ERR[$err] ?? OW_ACCOUNT_ERR['server']; ?><div class="o-flash bad" role="alert"><?= owBi($m[0], $m[1]) ?></div><?php endif; ?>

<?php if ($tab === 'home'): ?>
  <div class="o-bento c-bento">
    <section class="o-t t-hi c-hi">
      <div>
        <p class="o-hello"><span class="bi-en" lang="en">Hello, <bdi><?= e($name) ?></bdi></span><span class="bi-ar" lang="ar">أهلا، <bdi><?= e($nameAr) ?></bdi></span></p>
        <p class="o-sub"><?= owBi('Your collection, orders and saved works, all in one place', 'مجموعتك وطلباتك والأعمال المحفوظة، في مكان واحد') ?></p>
      </div>
      <div class="o-quick">
        <a class="o-qa" href="marketplace.php"><?= $icon('gallery') ?><?= owBi('Explore the Gallery', 'استكشف المعرض') ?></a>
        <a class="o-qa" href="<?= e($tabLink('collection')) ?>"><?= $icon('frame') ?><?= owBi('My collection', 'مجموعتي') ?></a>
      </div>
    </section>

    <div class="o-kpis c-kpis" style="--k:4">
      <a class="o-t o-kpi" href="<?= e($tabLink('collection')) ?>"><span class="o-kl"><?= owBi('Artworks in my collection', 'أعمال في مجموعتي') ?></span><span class="o-kv"><?= count($collection) ?></span></a>
      <?php if ($payOn): ?><a class="o-t o-kpi" href="<?= e($tabLink('orders')) ?>"><span class="o-kl"><?= owBi('Orders on their way', 'طلبات في الطريق') ?></span><span class="o-kv"><?= count($openOrders) ?></span></a>
      <?php else: ?><a class="o-t o-kpi" href="<?= e($tabLink('requests')) ?>"><span class="o-kl"><?= owBi('My requests', 'طلباتي') ?></span><span class="o-kv"><?= count($requests) ?></span></a><?php endif; ?>
      <a class="o-t o-kpi" href="<?= e($tabLink('favorites')) ?>"><span class="o-kl"><?= owBi('Saved artworks', 'أعمال محفوظة') ?></span><span class="o-kv"><?= count($favorites) ?></span></a>
      <a class="o-t o-kpi" href="<?= e($tabLink('following')) ?>"><span class="o-kl"><?= owBi('Artists I follow', 'فنانون أتابعهم') ?></span><span class="o-kv"><?= count($following) ?></span></a>
    </div>

    <section class="o-t c-needs">
      <div class="o-th"><h2><?= owBi('Needs you now', 'يحتاجك الآن') ?></h2><?php if ($needs): ?><span class="o-badge hot"><?= count($needs) ?></span><?php endif; ?></div>
      <?php if (!$needs): ?><p class="o-empty"><?= owBi('Nothing needs you now', 'لا شيء يحتاجك الآن') ?></p>
      <?php else: ?><ul class="o-alerts"><?php foreach ($needs as [$href, $w, $hot]): ?>
        <li><a href="<?= e($href) ?>"><span class="o-an<?= $hot ? ' hot' : '' ?>" aria-hidden="true">!</span><span><?= owBi($w[0], $w[1]) ?></span><span class="o-go" aria-hidden="true">›</span></a></li>
      <?php endforeach; ?></ul><?php endif; ?>
    </section>

    <?php if ($payOn): ?>
    <section class="o-t c-orders">
      <div class="o-th"><h2><?= owBi('My orders', 'طلباتي من الأعمال') ?></h2></div>
      <?php if (!$openOrders): ?><p class="o-empty"><?= owBi('No orders on their way', 'لا طلبات في الطريق') ?></p>
      <?php else: ?><ul class="c-olist"><?php foreach (array_slice($openOrders, 0, 3) as $o): ?>
        <li><div class="c-orow"><b><?= owBi($o['title_en'], $o['title_ar']) ?></b><?= owBuyerOrderPill($o) ?></div><?= $steps($o) ?></li>
      <?php endforeach; ?></ul><?php endif; ?>
      <a class="o-more" href="<?= e($tabLink('orders')) ?>"><?= owBi('All orders', 'كل الطلبات') ?></a>
    </section>
    <?php endif; ?>

    <section class="o-t c-new">
      <div class="o-th"><h2><?= owBi('New from artists you follow', 'جديد الفنانين الذين تتابعهم') ?></h2></div>
      <?php if (!$newWorks): ?><p class="o-empty"><?= $following ? owBi('No new works yet. They will show here', 'لا أعمال جديدة بعد. ستظهر هنا') : owBi('Follow artists from their page to see their new works here', 'تابع الفنانين من صفحاتهم لترى أعمالهم الجديدة هنا') ?></p>
      <?php else: ?><div class="c-strip"><?php foreach (array_slice($newWorks, 0, 4) as $w): [$en, $ar] = $tLine($w); ?>
        <a class="c-mini" href="marketplace.php?show=<?= (int) $w['id'] ?>#artwork-<?= (int) $w['id'] ?>"><?= $thumb((string) $w['image_url'], $en . ' · ' . $ar) ?>
          <span class="c-mt"><?= owBi($en, $ar) ?></span><span class="c-ms"><?= owBiName((string) $w['artist_en'], (string) $w['artist_ar']) ?></span></a>
      <?php endforeach; ?></div><?php endif; ?>
      <a class="o-more" href="<?= e($tabLink('following')) ?>"><?= owBi('Artists I follow', 'فنانون أتابعهم') ?></a>
    </section>

    <section class="o-t c-saved">
      <div class="o-th"><h2><?= owBi('Saved artworks', 'أعمال محفوظة') ?></h2></div>
      <?php if (!$favorites): ?><p class="o-empty"><?= owBi('Tap the heart on any artwork in the Gallery to save it here', 'اضغط القلب على أي عمل في المعرض ليُحفظ هنا') ?></p>
      <?php else: ?><div class="c-strip"><?php foreach (array_slice($favorites, 0, 4) as $f): [$en, $ar] = $tLine($f); ?>
        <a class="c-mini" href="<?= (int) $f['visible'] === 1 ? 'marketplace.php?show=' . (int) $f['id'] . '#artwork-' . (int) $f['id'] : e($tabLink('favorites')) ?>"><?= $thumb((string) $f['image_url'], $en . ' · ' . $ar) ?>
          <span class="c-mt"><?= owBi($en, $ar) ?></span><span class="c-ms"><?= $money($f['price']) ?></span></a>
      <?php endforeach; ?></div><?php endif; ?>
      <a class="o-more" href="<?= e($tabLink('favorites')) ?>"><?= owBi('All favourites', 'كل المفضلة') ?></a>
    </section>

    <?php if ($showBookings): ?>
    <section class="o-t c-book">
      <div class="o-th"><h2><?= owBi('Courses and bookings', 'الدورات والحجوزات') ?></h2></div>
      <?php $nextB = array_slice(array_values(array_filter($bookings, fn($b) => $b['status'] !== 'cancelled' && (string) $b['end_date'] >= date('Y-m-d'))), 0, 3);
      if (!$nextB): ?><p class="o-empty"><?= owBi('No upcoming courses', 'لا دورات قادمة') ?></p>
      <?php else: ?><ul class="c-olist"><?php foreach ($nextB as $b): [$en, $ar] = $tLine($b); [$dEn, $dAr] = owDateBi($b['start_date']); ?>
        <li><div class="c-orow"><b><?= owBi($en, $ar) ?></b><?= owRegIsBooking($b) ? '<span class="c-pill warn">' . owBi('Booked, waiting for payment', 'محجوز، بانتظار الدفع') . '</span>' : '<span class="c-pill on">' . owBi('Confirmed', 'مؤكد') . '</span>' ?></div>
          <span class="o-sm"><?= owBi('Starts ' . $dEn, 'يبدأ ' . $dAr) ?></span></li>
      <?php endforeach; ?></ul><?php endif; ?>
      <a class="o-more" href="<?= e($tabLink('bookings')) ?>"><?= owBi('All bookings', 'كل الحجوزات') ?></a>
    </section>
    <?php endif; ?>
  </div>

<?php elseif ($tab === 'collection'): ?>
  <p class="o-note"><?= owBi('Every artwork you bought, with its certificate of authenticity', 'كل عمل اقتنيته، مع شهادة الأصالة') ?></p>
  <?php if (!$collection): ?>
    <div class="o-t"><p class="o-empty"><?= owBi('No artworks yet. Your collection will appear here', 'لا أعمال بعد. ستظهر مجموعتك هنا') ?></p>
      <p class="c-center"><a class="btn btn-primary" href="marketplace.php"><?= owBi('Explore the Gallery', 'استكشف المعرض') ?></a></p></div>
  <?php else: require_once __DIR__ . '/cert_lib.php'; require_once __DIR__ . '/qr_lib.php'; ?>
    <div class="c-grid">
    <?php foreach ($collection as $c): [$en, $ar] = $tLine($c); [$dEn, $dAr] = owDateBi($c['bought_at']);
      $rc = !empty($c['order_id']) ? owOrderReceipt((int) $c['order_id']) : null; ?>
      <article class="o-t c-art">
        <?= $thumb((string) $c['image_url'], $en . ' · ' . $ar, 'c-cover') ?>
        <div class="c-art-b">
          <h2 class="c-at"><?= owBi($en, $ar) ?></h2>
          <a class="c-artist" href="artist_dashboard.php?id=<?= (int) $c['artist_id'] ?>"><?= owBiName((string) $c['artist_en'], (string) $c['artist_ar'], 'by', 'بريشة') ?></a>
          <span class="o-sm"><?= owBi('Bought ' . $dEn, 'اقتنيته في ' . $dAr) ?> · <?= $money($c['price']) ?></span>
          <?php if ($c['cert_no'] !== ''): ?>
            <div class="c-cert">
              <span class="c-qr"><?= owQrSvg(owCertVerifyUrl($c['cert_no']), 84, owIsAr() ? 'رمز التحقق من الشهادة' : 'Certificate check code') ?></span>
              <div>
                <span class="c-cn"><?= $icon('cert') ?><bdi dir="ltr"><?= e($c['cert_no']) ?></bdi></span>
                <div class="c-links">
                  <?php if ($c['cert_own']): ?><a href="certificate.php?no=<?= e(rawurlencode($c['cert_no'])) ?>"><?= owBi('Open the certificate', 'افتح الشهادة') ?></a><?php endif; ?>
                  <a href="<?= e(owCertVerifyUrl($c['cert_no'])) ?>"><?= owBi('Verify', 'تحقق') ?></a>
                </div>
                <?php if (!$c['cert_own']): ?><span class="o-sm"><?= owBi('The full certificate opens here once you confirm you received the artwork', 'تفتح الشهادة كاملة هنا بعد تأكيد استلامك للعمل') ?></span><?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
          <?php if ($rc && $rc['photos']): ?>
            <p class="c-rh"><?= owBi('Your photos when it arrived', 'صورك عند الاستلام') ?></p>
            <div class="c-rphotos"><?php foreach ($rc['photos'] as $i => $_): ?>
              <a href="<?= e(owReceiptPhotoUrl((int) $c['order_id'], $i)) ?>" target="_blank" rel="noopener"><img loading="lazy" src="<?= e(owReceiptPhotoUrl((int) $c['order_id'], $i)) ?>" alt="<?= e(($i + 1) . ' · ' . $en . ' · ' . $ar) ?>"></a>
            <?php endforeach; ?></div>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php elseif ($tab === 'orders'): ?>
  <?php if (!$orders): ?><div class="o-t"><p class="o-empty"><?= owBi('No orders yet', 'لا طلبات بعد') ?></p></div>
  <?php else: ?><div class="c-list"><?php foreach ($orders as $o): [$dEn, $dAr] = owDateBi($o['created_at']); $k = OW_ORDER_KINDS[$o['kind']] ?? ['Order', 'طلب']; ?>
    <section class="o-t c-order">
      <div class="c-orow"><div><h2 class="c-at"><?= owBi($o['title_en'], $o['title_ar']) ?></h2>
        <span class="o-sm"><?= owBi($k[0], $k[1]) ?> · <bdi dir="ltr"><?= e($o['order_no']) ?></bdi> · <?= owBi($dEn, $dAr) ?> · <?= $money($o['amount']) ?></span></div>
        <?= owBuyerOrderPill($o) ?></div>
      <?= $steps($o) ?>
      <?php if ($o['status'] === 'delivered' && empty($o['confirmed_at'])): ?>
        <div class="o-row-btns"><a class="btn btn-primary btn-sm" href="my_orders.php?o=<?= (int) $o['id'] ?>"><?= owBi('I received the artwork', 'استلمت العمل') ?></a>
          <a class="btn btn-secondary btn-sm" href="my_orders.php?o=<?= (int) $o['id'] ?>"><?= owBi('Something is wrong', 'هناك مشكلة') ?></a></div>
      <?php endif; ?>
    </section>
  <?php endforeach; ?></div><?php endif; ?>

<?php elseif ($tab === 'bookings'): ?>
  <?php if (!$bookings && !$tickets): ?><div class="o-t"><p class="o-empty"><?= owBi('No bookings yet', 'لا حجوزات بعد') ?></p>
    <?php if (owCoursesOn()): ?><p class="c-center"><a class="btn btn-primary" href="institutes.php"><?= owBi('See the courses', 'اطلع على الدورات') ?></a></p><?php endif; ?></div>
  <?php else: ?><div class="c-list">
    <?php foreach ($bookings as $b): [$en, $ar] = $tLine($b); [$dEn, $dAr] = owDateBi($b['start_date']); ?>
      <section class="o-t c-order"><div class="c-orow"><div><h2 class="c-at"><?= owBi($en, $ar) ?></h2>
        <span class="o-sm"><?= owBi('Course', 'دورة') ?> · <bdi dir="ltr"><?= e($b['ref_no']) ?></bdi> · <?= owBi('Starts ' . $dEn, 'يبدأ ' . $dAr) ?><?= (float) $b['amount'] > 0 ? ' · ' . $money($b['amount']) : ' · ' . owBi('Free', 'مجاني') ?></span></div>
        <?php if ($b['status'] === 'cancelled'): ?><span class="c-pill off"><?= owBi('Cancelled', 'ملغى') ?></span>
        <?php elseif (owRegIsBooking($b)): ?><span class="c-pill warn"><?= owBi('Booked, waiting for payment', 'محجوز، بانتظار الدفع') ?></span>
        <?php else: ?><span class="c-pill on"><?= owBi('Confirmed', 'مؤكد') ?></span><?php endif; ?></div>
        <?php if (owRegIsBooking($b) && (float) $b['amount'] > 0 && $b['status'] !== 'cancelled'): ?><p class="o-sm"><?= owBi('Your seat is held. We will contact you to arrange the payment', 'مقعدك محجوز. سنتواصل معك لترتيب الدفع') ?></p><?php endif; ?>
      </section>
    <?php endforeach; ?>
    <?php foreach ($tickets as $t): [$dEn, $dAr] = owDateBi($t['visit_date']); ?>
      <section class="o-t c-order"><div class="c-orow"><div><h2 class="c-at"><?= owBi((string) $t['name_en'], (string) ($t['name_ar'] ?: $t['name_en'])) ?></h2>
        <span class="o-sm"><?= owBi('Museum ticket', 'تذكرة متحف') ?> · <bdi dir="ltr"><?= e($t['ref_no']) ?></bdi> · <?= owBi($dEn, $dAr) ?></span></div>
        <span class="c-pill<?= $t['status'] === 'paid' ? ' on' : '' ?>"><?= $t['status'] === 'used' ? owBi('Used', 'مستخدمة') : ($t['status'] === 'cancelled' ? owBi('Cancelled', 'ملغاة') : owBi('Ready', 'جاهزة')) ?></span></div>
      </section>
    <?php endforeach; ?>
    <p class="o-foot-note"><a href="my_bookings.php"><?= owBi('Booking details, meeting links and tickets', 'تفاصيل الحجز وروابط الدورات والتذاكر') ?></a></p>
  </div><?php endif; ?>

<?php elseif ($tab === 'requests'): ?>
  <?php if (!$requests): ?>
    <div class="o-t"><p class="o-empty"><?= owBi('No requests yet. When a work speaks to you, press “I’m Interested” and we will contact you.', 'لا طلبات بعد. حين يلامسك عمل، اضغط «أنا مهتم» وسنتواصل معك.') ?></p>
      <p class="c-center"><a class="btn btn-primary" href="marketplace.php"><?= owBi('Explore the Gallery', 'استكشف المعرض') ?></a></p></div>
  <?php else: ?>
    <div class="req-list">
    <?php foreach ($requests as $r):
      $st = isset(OW_INQUIRY_STATUS[$r['status']]) ? $r['status'] : 'new';
      $img = owImgSrc($r['image_url'] ?? ''); $t400 = $img !== '' ? owImgSized($img, 400) : ''; ?>
      <div class="o-t req">
        <div class="req-thumb"><?php if ($img !== ''): ?><img loading="lazy" src="<?= e($t400) ?>" alt="<?= e(($r['title_en'] ?? '') . ' · ' . ($r['title_ar'] ?? '')) ?>"<?= owImgFallbackAttrs($img, $t400) ?>><?= owImgPlaceholder(true) ?><?php else: ?><?= owImgPlaceholder(false) ?><?php endif; ?></div>
        <div class="req-body">
          <p class="req-title"><?= $r['aid'] ? owBi((string) ($r['title_en'] ?: $r['title_ar']), (string) ($r['title_ar'] ?: $r['title_en'])) : owBi('This artwork was removed', 'تمت إزالة هذا العمل') ?></p>
          <div class="req-meta"><?php if ($r['aid']): ?><?= owBiName((string) $r['artist_en'], (string) $r['artist_ar'], 'by', 'بواسطة') ?> · <?php endif; ?><?= owBi('Sent', 'أُرسل') ?> <?= $fmtDate($r['created_at']) ?></div>
        </div>
        <div class="req-side">
          <span class="st-pill st-<?= e($st) ?>"><?= owBi(OW_INQUIRY_STATUS[$st][0], OW_INQUIRY_STATUS[$st][1]) ?></span>
          <?php if ($r['aid'] && (int) $r['visible'] === 1): ?><a class="btn btn-ghost btn-sm" href="marketplace.php?show=<?= (int) $r['aid'] ?>#artwork-<?= (int) $r['aid'] ?>"><?= owBi('See the artwork', 'شاهد العمل') ?></a><?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php /* Gifts and private commissions this collector sent (by account, or by the confirmed email) */
  $myGifts = $myComms = [];
  try {
      $byWho = 'user_id = :u' . ($verified ? ' OR buyer_email = :e' : '');
      $wArgs = [':u' => (int) $me['id']] + ($verified ? [':e' => strtolower(trim((string) $me['email']))] : []);
      if (owHasTable('gift_requests')) { $q = getDB()->prepare("SELECT id, occasion, mode, delivery_date, recipient_city, status, created_at FROM gift_requests WHERE $byWho ORDER BY id DESC LIMIT 30"); $q->execute($wArgs); $myGifts = $q->fetchAll(); }
      if (owHasTable('commissions'))   { $q = getDB()->prepare("SELECT id, ref_no, subject, status, created_at FROM commissions WHERE $byWho ORDER BY id DESC LIMIT 30"); $q->execute($wArgs); $myComms = $q->fetchAll(); }
  } catch (Throwable $e) { $myGifts = $myComms = []; }
  if ($myGifts || $myComms):
      require_once __DIR__ . '/request_lib.php'; require_once __DIR__ . '/commission_lib.php';
      $gOcc = owGiftOccasions(); $gSt = ['new' => ['New', 'جديد'], 'contacted' => ['Contacted', 'تم التواصل'], 'in_progress' => ['In progress', 'قيد التنفيذ'], 'done' => ['Done', 'تم'], 'cancelled' => ['Cancelled', 'ملغى']]; ?>
    <h2 class="c-sec"><?= owBi('Gifts and private commissions', 'الهدايا والأعمال الخاصة') ?></h2>
    <div class="req-list">
    <?php foreach ($myGifts as $g): $o = $gOcc[$g['occasion']] ?? ['en' => 'Gift', 'ar' => 'هدية']; ?>
      <div class="o-t req">
        <div class="req-body"><p class="req-title"><?= owBi('Gift: ' . $o['en'], 'هدية: ' . $o['ar']) ?></p>
          <div class="req-meta"><?= owBi('To ' . $g['recipient_city'], 'إلى ' . $g['recipient_city']) ?> · <?= owBi('Delivery', 'التوصيل') ?> <?= $fmtDate($g['delivery_date']) ?> · <?= owBi('Sent', 'أُرسل') ?> <?= $fmtDate($g['created_at']) ?></div></div>
        <div class="req-side"><span class="st-pill"><?= owBi($gSt[$g['status']][0] ?? $g['status'], $gSt[$g['status']][1] ?? $g['status']) ?></span></div>
      </div>
    <?php endforeach; ?>
    <?php foreach ($myComms as $c): $cs = owCommSubjects()[$c['subject']] ?? ['Commission', 'عمل خاص']; $cw = owCommStatus()[$c['status']] ?? [$c['status'], $c['status']]; ?>
      <div class="o-t req">
        <div class="req-body"><p class="req-title"><?= owBi('Private commission: ' . $cs[0], 'عمل خاص: ' . $cs[1]) ?> <span dir="ltr" class="req-meta"><?= e($c['ref_no']) ?></span></p>
          <div class="req-meta"><?= owBi('Open the private link in our first email to see every step', 'افتح الرابط الخاص في أول رسالة منا لترى كل خطوة') ?></div></div>
        <div class="req-side"><span class="st-pill"><?= owBi($cw[0], $cw[1]) ?></span></div>
      </div>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php elseif ($tab === 'favorites'): ?>
  <?php if (!$favorites): ?>
    <div class="o-t"><p class="o-empty"><?= owBi('Nothing saved yet. Tap the heart on any artwork in the Gallery to keep it here.', 'لا شيء محفوظ بعد. اضغط القلب على أي عمل في المعرض ليبقى هنا.') ?></p>
      <p class="c-center"><a class="btn btn-primary" href="marketplace.php"><?= owBi('Explore the Gallery', 'استكشف المعرض') ?></a></p></div>
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
          <span class="aw-artist"><?= owBiName((string) $f['artist_en'], (string) $f['artist_ar'], 'by', 'بواسطة') ?></span>
          <?php if ($tl): ?><span class="type-pill"><?= owBi($tl['en'], $tl['ar']) ?></span><?php endif; ?>
          <div class="aw-price"><?= $money($f['price']) ?></div>
          <?php if ((int) $f['visible'] === 1): ?>
            <a class="btn btn-dark btn-block btn-sm" href="marketplace.php?show=<?= $fid ?>#artwork-<?= $fid ?>"><?= owBi('See the Story', 'شاهد القصة') ?></a>
          <?php else: ?>
            <span class="fav-gone"><?= owBi('No longer available', 'لم يعد متاحا') ?></span>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php elseif ($tab === 'following'): ?>
  <p class="o-note"><?= owBi('Their new works show on your home page. Artists see only how many follow them, never who', 'تظهر أعمالهم الجديدة في صفحتك الرئيسية. يرى الفنانون عدد متابعيهم فقط، لا هوياتهم') ?></p>
  <?php if (!$following): ?>
    <div class="o-t"><p class="o-empty"><?= owBi('You do not follow any artist yet. Open an artist’s page and press Follow', 'لا تتابع أي فنان بعد. افتح صفحة فنان واضغط «تابع»') ?></p>
      <p class="c-center"><a class="btn btn-primary" href="marketplace.php"><?= owBi('Explore the Gallery', 'استكشف المعرض') ?></a></p></div>
  <?php else: ?><div class="c-list">
    <?php foreach ($following as $a): $pp = owImgSrc((string) ($a['profile_picture'] ?? '')); ?>
      <section class="o-t c-fw">
        <a class="c-fw-a" href="artist_dashboard.php?id=<?= (int) $a['id'] ?>">
          <span class="c-av"<?= $pp !== '' ? ' style="background-image:url(\'' . e(owImgSized($pp, 400)) . '\')"' : '' ?> aria-hidden="true"><?= $pp === '' ? e(mb_strtoupper(mb_substr((string) $a['artist_en'], 0, 1))) : '' ?></span>
          <span><b><?= owBiName((string) $a['artist_en'], (string) $a['artist_ar']) ?></b>
            <span class="o-sm"><?= owBi((int) $a['works'] . ' ' . ((int) $a['works'] === 1 ? 'artwork' : 'artworks') . ' in the Gallery', (int) $a['works'] . ' ' . ((int) $a['works'] === 1 ? 'عمل' : 'أعمال') . ' في المعرض') ?></span></span>
        </a>
        <form method="post" action="follow.php"><?= csrfField() ?><input type="hidden" name="artist_id" value="<?= (int) $a['id'] ?>"><input type="hidden" name="do" value="unfollow"><input type="hidden" name="from" value="dash">
          <button class="btn btn-secondary btn-sm" type="submit"><?= owBi('Unfollow', 'إلغاء المتابعة') ?></button></form>
      </section>
    <?php endforeach; ?>
  </div><?php endif; ?>

<?php elseif ($tab === 'messages'): ?>
  <?php if (!$messages): ?><div class="o-t"><p class="o-empty"><?= owBi('No messages from Oweili yet', 'لا رسائل من أويلي بعد') ?></p></div>
  <?php else: ?><div class="c-list"><?php foreach ($messages as $m): [$dEn, $dAr] = owDateBi($m['sent_at']); ?>
    <details class="o-t c-msg">
      <summary><b dir="auto"><?= e(owLangPick((string) $m['subject'], owIsAr())) /* one language: "English · عربي" keeps the page language */ ?></b><span class="o-sm"><?= owBi($dEn, $dAr) ?> · <?= $m['kind'] === 'news' ? owBi('News', 'أخبار') : owBi('Service', 'خدمة') ?></span></summary>
      <div class="c-msg-b" dir="auto"><?= nl2br(e(owIsAr() ? ((string) $m['body_ar'] ?: (string) $m['body_en']) : ((string) $m['body_en'] ?: (string) $m['body_ar']))) ?></div>
    </details>
  <?php endforeach; ?></div><?php endif; ?>
  <p class="o-foot-note"><?= owBi('Here are the messages the Oweili team sent to everyone or to a group. Emails about your own requests and orders stay in your inbox', 'هنا الرسائل التي أرسلها فريق أويلي للجميع أو لمجموعة. رسائل طلباتك الخاصة تبقى في بريدك') ?></p>

<?php elseif ($tab === 'complaints'): ?>
  <?php if (!$complaints): ?><div class="o-t"><p class="o-empty"><?= owBi('No complaints. If something is wrong with an order, tell us from Orders', 'لا شكاوى. إن كانت هناك مشكلة في طلب، أخبرنا من صفحة الطلبات') ?></p></div>
  <?php else: ?><div class="c-list"><?php foreach ($complaints as $c): [$dEn, $dAr] = owDateBi($c['created_at']); $cw = ['open' => [['Open, we are looking into it', 'مفتوحة، نعمل عليها'], 'warn'], 'released' => [['Solved', 'تم الحل'], 'on'], 'refunded' => [['Solved, money returned', 'تم الحل، أعيد المبلغ'], 'on']][$c['status']] ?? [[$c['status'], $c['status']], '']; ?>
    <section class="o-t c-order">
      <div class="c-orow"><div><h2 class="c-at"><?= owBi((string) $c['title_en'], (string) $c['title_ar']) ?></h2><span class="o-sm"><bdi dir="ltr"><?= e($c['order_no']) ?></bdi> · <?= owBi($dEn, $dAr) ?></span></div>
        <span class="c-pill <?= $cw[1] ?>"><?= owBi($cw[0][0], $cw[0][1]) ?></span></div>
      <p class="c-quote" dir="auto"><?= nl2br(e((string) $c['reason'])) ?></p>
      <?php if ($c['resolution_note'] !== ''): ?><p class="o-sm" dir="auto"><?= owBi('Our answer: ', 'ردنا: ') ?><?= e($c['resolution_note']) ?></p><?php endif; ?>
    </section>
  <?php endforeach; ?></div><?php endif; ?>

<?php elseif ($tab === 'profile'): ?>
  <form class="o-t form-card" method="post" action="<?= e($tabLink('profile')) ?>" novalidate>
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
        <datalist id="countries"><?php require_once __DIR__ . '/lang_lib.php'; foreach (OW_COUNTRIES as $cEn => $cAr): ?><option value="<?= e(owIsAr() ? $cAr : $cEn) ?>"><?php endforeach; ?></datalist></div>
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

<?php else: /* account */ ?>
  <?php if (!$verified): ?>
  <div class="o-flash bad c-verify" role="status">
    <span><?= owBi('Please confirm your email address. We sent you a link when you joined.', 'يرجى تأكيد بريدك الإلكتروني. أرسلنا لك رابطا عند التسجيل.') ?></span>
    <form method="post" action="<?= e($tabLink('account')) ?>"><?= csrfField() ?><input type="hidden" name="do" value="resend">
      <button class="btn btn-secondary btn-sm" type="submit"><?= owBi('Send the link again', 'أرسل الرابط مجددا') ?></button></form>
  </div>
  <?php endif; ?>
  <form class="o-t form-card" method="post" action="<?= e($tabLink('account')) ?>" novalidate>
    <?= csrfField() ?><input type="hidden" name="do" value="email">
    <h2><?= owBi('Email address', 'البريد الإلكتروني') ?></h2>
    <p class="hint"><?= owBi('Now: ', 'الحالي: ') ?><span dir="ltr"><?= e($me['email']) ?></span><?= $verified ? '' : ' · ' . owBi('not confirmed yet', 'لم يُؤكَّد بعد') ?></p>
    <div class="field<?= in_array($err, ['email', 'email_same', 'exists'], true) ? ' has-error' : '' ?>"><label for="a-email"><?= owBi('New email', 'البريد الجديد') ?></label>
      <input id="a-email" name="new_email" type="email" required maxlength="190" autocomplete="email" dir="ltr" value="<?= e($old['new_email'] ?? '') ?>"></div>
    <div class="field"><label for="a-cur1"><?= owBi('Current password', 'كلمة المرور الحالية') ?></label>
      <input id="a-cur1" name="current_password" type="password" required autocomplete="current-password"></div>
    <p class="hint"><?= owBi('We will send a link to the new address to confirm it', 'سنرسل رابطا إلى البريد الجديد لتأكيده') ?></p>
    <button class="btn btn-primary" type="submit"><?= owBi('Change email', 'غيّر البريد') ?></button>
  </form>
  <form class="o-t form-card" method="post" action="<?= e($tabLink('account')) ?>" novalidate>
    <?= csrfField() ?><input type="hidden" name="do" value="password">
    <h2><?= owBi('Password', 'كلمة المرور') ?></h2>
    <div class="field"><label for="a-cur2"><?= owBi('Current password', 'كلمة المرور الحالية') ?></label>
      <input id="a-cur2" name="current_password" type="password" required autocomplete="current-password"></div>
    <div class="field<?= in_array($err, ['weak', 'same'], true) ? ' has-error' : '' ?>"><label for="a-new"><?= owBi('New password', 'كلمة المرور الجديدة') ?></label>
      <input id="a-new" name="new_password" type="password" required minlength="8" autocomplete="new-password"></div>
    <p class="hint"><?= owBi('8 or more characters, with a capital letter, a number and a symbol', '8 أحرف أو أكثر، مع حرف كبير ورقم ورمز') ?></p>
    <div class="field<?= $err === 'mismatch' ? ' has-error' : '' ?>"><label for="a-conf"><?= owBi('New password again', 'كلمة المرور الجديدة مرة أخرى') ?></label>
      <input id="a-conf" name="confirm_password" type="password" required minlength="8" autocomplete="new-password"></div>
    <button class="btn btn-primary" type="submit"><?= owBi('Change password', 'غيّر كلمة المرور') ?></button>
  </form>
<?php endif; ?>

    </main>
  </div>
</div>
<script>
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
<script src="<?= owAsset('assets/js/owner.js') ?>" defer></script>
</body>
</html>
