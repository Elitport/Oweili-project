<?php
/* =====================================================================
 *  owner.php — the owner dashboard (owner, 24 Nov 2026).
 *  The owner signs in with the admin password (the same as admin.php).
 *  Sub-admins (sub_admin accounts) see only the groups ticked for them.
 *  Pages (?p=): home (default), crm, money, orders, content, messages,
 *  settings. Every current admin page keeps working; the side menu links
 *  to them. Shared code: owner_lib.php. Look: assets/css/owner.css.
 *  Tables read: users, artworks, visitor_stats, page_stats, activity_log,
 *  artwork_inquiries, course_registrations, museum_tickets, orders,
 *  payouts, partner_payouts, booking_payments, crm_people, sent_messages
 *  and the waiting lists. Written: crm_people, sent_messages,
 *  site_settings (owner_name, vat, subadmin_groups), activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/owner_lib.php';
require_once __DIR__ . '/visitor_lib.php';
require_once __DIR__ . '/lang_lib.php';
header('Cache-Control: private, no-store');
header('X-Robots-Tag: noindex');

/* ---- the owner's sign-in: the admin password, exactly like admin.php ---- */
$loginError = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['owner_pass'])) {
    $hash = defined('ADMIN_HASH') ? (string) ADMIN_HASH : '';
    if ($hash === '') $loginError = 'setup';
    elseif (owAdminGateBlocked()) $loginError = 'wait';
    elseif (verifyCsrf($_POST['csrf_token'] ?? null) && password_verify((string) $_POST['owner_pass'], $hash)) {
        owAdminGateRecord(true);
        session_regenerate_id(true);
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_sig'] = hash('sha256', $hash);
        owLog('admin_login', null, null, 'owner.php', ['type' => 'admin', 'id' => null]);
        header('Location: owner.php'); exit;
    } else {
        if (verifyCsrf($_POST['csrf_token'] ?? null)) owAdminGateRecord(false);
        $loginError = 'wrong';
    }
}
$V = owOwnerViewer();
if ($V && $V['owner'] && empty($_COOKIE['ow_nostat']) && !headers_sent())   // the owner's browser is never counted
    setcookie('ow_nostat', '1', ['expires' => time() + 5 * 365 * 86400, 'path' => '/', 'secure' => !empty($_SERVER['HTTPS']), 'httponly' => false, 'samesite' => 'Lax']);

$PAGES = ['home' => '', 'crm' => 'people', 'money' => 'money', 'orders' => 'money', 'content' => 'content', 'messages' => 'messages', 'settings' => 'owner', 'preview' => 'owner'];
$page = (string) ($_GET['p'] ?? 'home');
if (!isset($PAGES[$page])) $page = 'home';
$allowed = function (string $p) use ($V, $PAGES): bool {
    if (!$V) return false;
    $g = $PAGES[$p] ?? 'owner';
    if ($g === '') return true;
    if ($g === 'owner') return $V['owner'];
    return owOwnerCan($V, $g);
};
if ($V && !$allowed($page)) $page = 'home';
$flash = (string) ($_SESSION['owner_flash'] ?? ''); unset($_SESSION['owner_flash']);
$back = function (string $to, string $msg = '') { if ($msg !== '') $_SESSION['owner_flash'] = $msg; header('Location: ' . $to); exit; };

/* ---- saving (every form has its security token) ---- */
if ($V && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['act'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) $back('owner.php?p=' . $page, 'expired');
    $act = (string) $_POST['act'];
    $actor = owOwnerActor($V);
    if ($act === 'crm_save' && $allowed('crm')) {
        $uid = (int) ($_POST['uid'] ?? 0);
        $ok = owCrmSave($uid, (string) ($_POST['stage'] ?? ''), (string) ($_POST['follow_up'] ?? ''), (string) ($_POST['note'] ?? ''), $actor);
        $back('owner.php?p=crm&u=' . $uid, $ok ? 'saved' : 'notsaved');
    }
    if ($act === 'crm_pick' && $allowed('messages')) {
        $_SESSION['owner_picked'] = array_slice(array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])))), 0, 500);
        $back('owner.php?p=messages&who=picked');
    }
    if (($act === 'msg_preview' || $act === 'msg_send') && $allowed('messages')) {
        $m = ['who' => (string) ($_POST['who'] ?? ''), 'kind' => ($_POST['kind'] ?? '') === 'news' ? 'news' : 'service', 'stage' => (string) ($_POST['stage'] ?? ''),
              'subj_en' => trim((string) ($_POST['subj_en'] ?? '')), 'subj_ar' => trim((string) ($_POST['subj_ar'] ?? '')),
              'body_en' => trim((string) ($_POST['body_en'] ?? '')), 'body_ar' => trim((string) ($_POST['body_ar'] ?? ''))];
        $_SESSION['owner_msg'] = $m;
        $bad = $m['subj_en'] === '' || $m['subj_ar'] === '' || mb_strlen($m['body_en']) < 5 || mb_strlen($m['body_ar']) < 5;
        if ($bad) $back('owner.php?p=messages&step=edit', 'msg_missing');
        $people = owMsgRecipients($m['who'], $m['kind'], $m['stage'], (array) ($_SESSION['owner_picked'] ?? []));
        if ($act === 'msg_preview') { $_SESSION['owner_msg_n'] = count($people); $back('owner.php?p=messages&step=check'); }
        if (!$people) $back('owner.php?p=messages&step=edit', 'msg_nobody');
        $batch = owMsgQueue($people, $m['kind'], $m['subj_en'], $m['subj_ar'], $m['body_en'], $m['body_ar']);
        owLog('group_message', 'message', null, $m['kind'] . ' · ' . count($people) . ' people · ' . $batch, $actor);
        unset($_SESSION['owner_msg'], $_SESSION['owner_msg_n'], $_SESSION['owner_picked']);
        $back('owner.php?p=messages', 'queued');
    }
    if ($act === 'msg_stop' && $allowed('messages') && owHasTable('sent_messages')) {
        $b = preg_replace('/[^a-f0-9]/', '', (string) ($_POST['batch'] ?? ''));
        getDB()->prepare("UPDATE sent_messages SET status = 'skipped' WHERE batch = :b AND status = 'queued'")->execute([':b' => $b]);
        owLog('group_message_stop', 'message', null, $b, $actor);
        $back('owner.php?p=messages', 'stopped');
    }
    if ($V['owner'] && $act === 'owner_name') {
        $n = mb_substr(trim(preg_replace('/\s+/u', ' ', (string) ($_POST['owner_name'] ?? ''))), 0, 60);
        $na = mb_substr(trim(preg_replace('/\s+/u', ' ', (string) ($_POST['owner_name_ar'] ?? ''))), 0, 60);
        owSetSetting('owner_name', $n);
        owSetSetting('owner_name_ar', $na);
        owLog('setting_change', 'setting', null, 'owner_name', $actor);
        $back('owner.php?p=settings', 'saved');
    }
    if ($V['owner'] && $act === 'vat') {
        $rate = (float) str_replace(',', '.', (string) ($_POST['rate'] ?? '15'));
        $num = preg_replace('/\D/', '', (string) ($_POST['number'] ?? ''));
        if ($rate < 0 || $rate > 50 || ($num !== '' && strlen($num) !== 15)) $back('owner.php?p=settings', 'vat_bad');
        owSetSetting('vat', json_encode(['on' => !empty($_POST['on']), 'rate' => $rate, 'number' => $num]));
        owLog('setting_change', 'setting', null, 'vat', $actor);
        $back('owner.php?p=settings', 'saved');
    }
    if ($V['owner'] && $act === 'id_keep') {   // how long identity documents are kept after an account closes (owner, 27 Nov 2026)
        $y = (int) ($_POST['years'] ?? 0);
        if ($y < 1 || $y > 20) $back('owner.php?p=settings', 'years_bad');
        owSetSetting('id_keep_years', (string) $y);
        owLog('setting_change', 'setting', null, 'id_keep_years ' . $y, $actor);
        $back('owner.php?p=settings', 'saved');
    }
    if ($V['owner'] && $act === 'subadmins') {
        $all = [];
        foreach (owQAll("SELECT id FROM users WHERE role = 'sub_admin'") as $s) {
            $all[(string) $s['id']] = array_values(array_intersect(array_keys(OW_OWNER_GROUPS), (array) ($_POST['g'][$s['id']] ?? [])));
        }
        owSetSetting('subadmin_groups', json_encode($all));
        owLog('setting_change', 'setting', null, 'subadmin_groups', $actor);
        /* which sub-admins may open the institutes' private documents (owner, 25 Nov 2026; inst_lib.php) */
        $docs = [];
        foreach (owQAll("SELECT id FROM users WHERE role = 'sub_admin'") as $s) if (!empty($_POST['instdocs'][$s['id']])) $docs[] = (int) $s['id'];
        owSetSetting('subadmin_inst_docs', json_encode($docs));
        owLog('setting_change', 'setting', null, 'subadmin_inst_docs', $actor);
        /* which sub-admins may open identity documents (owner, 27 Nov 2026; identity_lib.php) */
        $ids = [];
        foreach (owQAll("SELECT id FROM users WHERE role = 'sub_admin'") as $s) if (!empty($_POST['iddocs'][$s['id']])) $ids[] = (int) $s['id'];
        owSetSetting('subadmin_id_docs', json_encode($ids));
        owLog('setting_change', 'setting', null, 'subadmin_id_docs', $actor);
        $back('owner.php?p=settings', 'saved');
    }
    $back('owner.php?p=' . $page);
}

/* ---- while a group message is going out, an open owner page asks here every minute (owner.js); the next small group goes after the answer ---- */
if ($V && isset($_GET['ping'])) { http_response_code(204); header('Content-Length: 0'); $page = 'ping'; }
else {
/* ---- the monthly report as a CSV file (opens in Excel) ---- */
if ($V && $page === 'money' && isset($_GET['csv'])) {
    $rows = owMonthReport(12); $vat = owVat();
    owLog('money_export', null, null, '12 months', owOwnerActor($V));
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="oweili-monthly-report-' . gmdate('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w'); fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Month', 'Artworks sold', 'Artwork sales (SAR)', 'Oweili commission on artworks', 'Artists share', 'Course bookings paid',
                   'Course and ticket sales', 'Oweili commission on courses and tickets', 'Institutes and museums share', 'Oweili commission (total)',
                   $vat['on'] ? 'VAT on Oweili commission (' . $vat['rate'] . '%)' : 'VAT (off)', 'Refunds', 'Payouts paid']);
    foreach ($rows as $r) fputcsv($out, [$r['month'], $r['sold'], $r['sales'], $r['art_com'], $r['artist_share'], $r['bookings'], $r['partner_sales'],
                                         $r['partner_com'], $r['partner_share'], $r['commission'], $r['vat'], $r['refunds'], $r['paid_out']]);
    fclose($out); exit;
}
}
if ($page !== 'ping'):

/* ---- the side menu: groups, each item with an icon and a name ---- */
$IC = [
 'home' => '<path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/>',
 'wait' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
 'artist' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/>',
 'collector' => '<path d="M3 7h18l-2 13H5z"/><path d="M8 7a4 4 0 0 1 8 0"/>',
 'institute' => '<path d="M3 10l9-6 9 6"/><path d="M5 10v9M19 10v9M9 10v9M15 10v9M3 21h18"/>',
 'art' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 16l5-5 4 4 3-3 6 6"/>',
 'cert' => '<circle cx="12" cy="9" r="5"/><path d="M9 13.5L8 21l4-2 4 2-1-7.5"/>',
 'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
 'article' => '<path d="M5 3h10l4 4v14H5z"/><path d="M9 12h6M9 16h6M9 8h3"/>',
 'star' => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
 'map' => '<path d="M9 4l-6 2v14l6-2 6 2 6-2V4l-6 2z"/><path d="M9 4v14M15 6v14"/>',
 'school' => '<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c3 2 9 2 12 0v-5"/>',
 'money' => '<rect x="2" y="6" width="20" height="13" rx="2"/><circle cx="12" cy="12.5" r="3"/>',
 'cart' => '<path d="M3 4h2l2.5 11h11L21 7H6"/><circle cx="9" cy="19" r="1.5"/><circle cx="17" cy="19" r="1.5"/>',
 'ticket' => '<path d="M3 8a2 2 0 0 0 0 4v0a2 2 0 0 1 0 4v2h18v-2a2 2 0 0 1 0-4 2 2 0 0 0 0-4V6H3z"/>',
 'brush' => '<path d="M14 4l6 6-8 8H6v-6z"/><path d="M6 18l-3 3"/>',
 'gift' => '<rect x="3" y="9" width="18" height="12" rx="1"/><path d="M12 9v12M3 13h18"/><path d="M12 9c-2-4-6-4-6-1s6 1 6 1 6 2 6-1-4-3-6 1"/>',
 'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
 'heart' => '<path d="M12 20s-7-4.4-9-9a5 5 0 0 1 9-3 5 5 0 0 1 9 3c-2 4.6-9 9-9 9z"/>',
 'bell' => '<path d="M6 16V11a6 6 0 0 1 12 0v5l2 2H4z"/><path d="M10 20a2 2 0 0 0 4 0"/>',
 'gavel' => '<path d="M14 4l6 6M11 7l6 6M8 10l6-6M4 20l8-8"/>',
 'gear' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M4.9 19.1L7 17M17 7l2.1-2.1"/>',
 'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20c1-3.5 3.8-5 7-5s6 1.5 7 5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 15c2 .6 3.4 2.2 4 5"/>',
 'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/>',
 'old' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>',
 'out' => '<path d="M15 4h4v16h-4"/><path d="M10 8l-4 4 4 4M6 12h10"/>',
 'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
];
$icon = function (string $k) use ($IC): string { return '<svg class="o-ic" viewBox="0 0 24 24" aria-hidden="true">' . ($IC[$k] ?? '') . '</svg>'; };
/* [page or link, English, Arabic, icon, who: o = owner only, s = sub-admins only, g = owner + sub-admins with the group] */
$MENU = [
  'home' => [['Home', 'الرئيسية'], [['owner.php', 'Dashboard', 'لوحة التحكم', 'home', 'g', 'home']]],
  'people' => [['People', 'الأشخاص'], [
      ['owner.php?p=crm&t=artist', 'Artists', 'الفنانون', 'artist', 'g', 'crm:artist'],
      ['owner.php?p=crm&t=collector', 'Collectors', 'المقتنون', 'collector', 'g', 'crm:collector'],
      ['owner.php?p=crm&t=institute', 'Institutes', 'المعاهد', 'institute', 'g', 'crm:institute'],
      ['admin.php#pending', 'Waiting for approval', 'بانتظار الموافقة', 'wait', 'o', '']]],
  'content' => [['Art and content', 'الفن والمحتوى'], [
      ['owner.php?p=content', 'Content statistics', 'إحصاءات المحتوى', 'chart', 'g', 'content'],
      ['admin.php#artworks', 'Artworks', 'الأعمال الفنية', 'art', 'o', ''],
      ['subadmin_dashboard.php', 'Approve artworks', 'الموافقة على الأعمال', 'art', 's', ''],
      ['admin.php#certs', 'Certificates', 'الشهادات', 'cert', 'o', ''],
      ['admin_articles.php', 'Articles and events', 'المقالات والملتقيات', 'article', 'g', ''],
      ['admin_gallery.php', 'Gallery tools', 'أدوات المعرض', 'star', 'o', ''],
      ['admin_art_map.php', 'Art Map', 'وجهات', 'map', 'g', ''],   // sub-admins with this group: the Entry box only (owner, 27 Nov 2026)
      ['admin_institutes.php', 'Institutes and courses', 'المعاهد والدورات', 'school', 'g', ''],
      ['admin_workshops.php', 'Artist workshops', 'ورش الفنانين', 'school', 'o', '']]],
  'money' => [['Money', 'المال'], [
      ['owner.php?p=money', 'Accounting', 'المحاسبة', 'money', 'g', 'money'],
      ['owner.php?p=orders', 'Orders and payments overview', 'نظرة على الطلبات والمدفوعات', 'cart', 'g', 'orders'],
      ['admin_bookings.php', 'Manage courses and tickets', 'إدارة الدورات والتذاكر', 'ticket', 'o', ''],
      ['admin_orders.php', 'Artwork orders and escrow', 'طلبات الأعمال والضمان', 'cart', 'o', ''],
      ['admin_commissions.php', 'Private commissions', 'الأعمال الخاصة', 'brush', 'o', ''],
      ['admin_gifts.php', 'Gifts', 'الهدايا', 'gift', 'o', '']]],
  'messages' => [['Messages', 'الرسائل'], [
      ['owner.php?p=messages', 'Group messages', 'رسائل جماعية', 'mail', 'g', 'messages'],
      ['admin.php#inquiries', '"I’m Interested"', '«أنا مهتم»', 'heart', 'o', ''],
      ['admin_bookings.php#leads', '"Notify me"', '«نبّهني»', 'bell', 'o', ''],
      ['admin.php#auctions', 'Auctions waiting list', 'قائمة انتظار المزاد', 'gavel', 'o', '']]],
  'settings' => [['Settings and team', 'الإعدادات والفريق'], [
      ['owner.php?p=settings', 'Owner settings and team', 'إعدادات المالك والفريق', 'users', 'o', 'settings'],
      ['owner.php?p=preview', 'Preview pages', 'معاينة الصفحات', 'eye', 'o', 'preview'],   // the dashboards as users see them, fictional data (27 Nov 2026)
      ['admin.php?settings=1', 'Site settings', 'إعدادات الموقع', 'gear', 'o', ''],
      ['admin.php#stats', 'Visitors by city', 'الزوار حسب المدينة', 'globe', 'o', ''],
      ['admin.php', 'Classic admin page', 'صفحة الإدارة السابقة', 'old', 'o', '']]],
];
$cur = $page === 'crm' ? 'crm:' . (isset(OW_CRM_TYPES[$_GET['t'] ?? '']) ? $_GET['t'] : 'artist') : $page;
if ($page === 'crm' && !isset($_GET['t']) && isset($_GET['u'])) $cur = 'crm:' . ((owCrmPerson((int) $_GET['u'])['role'] ?? 'artist'));
$showItem = function (array $it, string $gk) use ($V): bool {
    if (!$V) return false;
    if ($it[4] === 'o') return $V['owner'];
    if ($it[4] === 's') return !$V['owner'];
    return $gk === 'home' || $gk === 'settings' ? ($gk === 'home' || $V['owner']) : owOwnerCan($V, $gk);
};
$TITLES = ['home' => ['Dashboard', 'لوحة التحكم'], 'crm' => ['People', 'الأشخاص'], 'money' => ['Accounting', 'المحاسبة'],
           'orders' => ['Orders and payments overview', 'نظرة على الطلبات والمدفوعات'], 'content' => ['Content statistics', 'إحصاءات المحتوى'],
           'messages' => ['Group messages', 'رسائل جماعية'], 'settings' => ['Owner settings and team', 'إعدادات المالك والفريق'], 'preview' => ['Preview pages', 'معاينة الصفحات']];
$qs = function (array $set) { $q = array_merge($_GET, $set); foreach ($q as $k => $v) if ($v === null || $v === '') unset($q[$k]); return 'owner.php?' . http_build_query($q); };
?>
<!DOCTYPE html>
<html <?php owHtmlAttrs(); ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title><?= e(owIsAr() ? ($TITLES[$page][1] ?? 'لوحة التحكم') . ' · أويلي' : ($TITLES[$page][0] ?? 'Dashboard') . ' · Oweili') ?></title>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/owner.css') ?>">
<script>try{if(localStorage.getItem('ow_side')==='mini')document.documentElement.classList.add('o-mini');}catch(e){}</script>
</head>
<body class="o-body">
<?php if (!$V): ?>
  <main class="o-login">
    <div class="o-card o-login-card">
      <div class="o-brand"><span class="o-logo" aria-hidden="true"></span><b>OWEILI</b></div>
      <h1><?= owBi('Owner dashboard', 'لوحة المالك') ?></h1>
      <?php if ($loginError !== ''): ?><p class="o-err" role="alert"><?= ['setup' => owBi('The admin password is not set up yet', 'كلمة مرور الإدارة غير مُعدّة بعد'), 'wait' => owBi('Too many attempts. Please wait 10 minutes', 'محاولات كثيرة جدا. يرجى الانتظار 10 دقائق'), 'wrong' => owBi('Incorrect password', 'كلمة مرور غير صحيحة')][$loginError] ?></p><?php endif; ?>
      <form method="post" action="owner.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <label class="o-lbl" for="op"><?= owBi('Admin password', 'كلمة مرور الإدارة') ?></label>
        <input class="o-in" id="op" type="password" name="owner_pass" autocomplete="current-password" required autofocus>
        <button class="btn btn-primary btn-block" type="submit"><?= owBi('Sign in', 'تسجيل الدخول') ?></button>
      </form>
      <p class="o-mini-note"><?= owBi('Sub-admins: sign in with your account first', 'المشرفون: سجّلوا الدخول بحسابكم أولا') ?> · <a href="login.php?next=owner.php"><?= owBi('Sign in', 'تسجيل الدخول') ?></a></p>
      <div class="o-login-ctl"><?php owCornerControls('owLang()'); ?></div>
    </div>
  </main>
<?php else: ?>
<div class="o-shell">
  <aside class="o-side" id="o-side" aria-label="<?= owIsAr() ? 'قائمة لوحة المالك' : 'Owner menu' ?>">
    <div class="o-side-top">
      <a class="o-brand" href="owner.php"><span class="o-logo" aria-hidden="true"></span><b>OWEILI</b></a>
      <button type="button" class="o-collapse" id="o-collapse" aria-label="<?= owIsAr() ? 'تصغير القائمة' : 'Collapse the menu' ?>" aria-pressed="false"><svg class="o-ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg></button>
    </div>
    <nav class="o-nav">
      <?php foreach ($MENU as $gk => [$gName, $items]):
        $vis = array_values(array_filter($items, fn($it) => $showItem($it, $gk)));
        if (!$vis) continue; ?>
        <div class="o-group">
          <p class="o-gname"><?= owBi($gName[0], $gName[1]) ?></p>
          <?php foreach ($vis as $it): $on = $it[5] !== '' && $it[5] === $cur; ?>
            <a class="o-link<?= $on ? ' on' : '' ?>" href="<?= e($it[0]) ?>"<?= $on ? ' aria-current="page"' : '' ?> title="<?= e(owIsAr() ? $it[2] : $it[1]) ?>"><?= $icon($it[3]) ?><span class="o-lt"><?= owBi($it[1], $it[2]) ?></span></a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </nav>
    <div class="o-side-foot">
      <a class="o-link" href="<?= $V['owner'] ? 'admin.php?logout=1' : 'logout.php' ?>" title="<?= owIsAr() ? 'تسجيل الخروج' : 'Sign out' ?>"><?= $icon('out') ?><span class="o-lt"><?= owBi('Sign out', 'تسجيل الخروج') ?></span></a>
    </div>
  </aside>
  <div class="o-scrim" id="o-scrim" hidden></div>
  <div class="o-main">
    <header class="o-top">
      <button type="button" class="o-burger" id="o-burger" aria-controls="o-side" aria-expanded="false" aria-label="<?= owIsAr() ? 'القائمة' : 'Menu' ?>"><svg class="o-ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
      <?php $ttl = $TITLES[$page]; if ($page === 'crm' && strpos($cur, 'crm:') === 0 && empty($_GET['u']) && ($_GET['t'] ?? '') !== 'all') { $tw = OW_CRM_TYPES[substr($cur, 4)] ?? null; if ($tw) $ttl = [$tw[0], $tw[1]]; } ?>
      <h1 class="o-title"><?= owBi($ttl[0], $ttl[1]) ?></h1>
      <div class="o-top-r"><a class="o-site" href="index.php"><?= owBi('View the site', 'عرض الموقع') ?></a><?php owCornerControls('owLang()'); ?></div>
    </header>
    <main class="o-content" id="o-content">
      <?php if ($flash !== ''): ?>
        <div class="o-flash<?= in_array($flash, ['notsaved', 'expired', 'vat_bad', 'years_bad', 'msg_missing', 'msg_nobody'], true) ? ' bad' : '' ?>" role="status"><?= [
          'saved' => owBi('Saved', 'تم الحفظ'), 'notsaved' => owBi('Not saved. Please check the date and try again', 'لم يُحفظ. يرجى التحقق من التاريخ والمحاولة مرة أخرى'),
          'expired' => owBi('This page was open for a long time, so nothing was changed. Please try again', 'كانت هذه الصفحة مفتوحة لوقت طويل، فلم يتغير شيء. يرجى المحاولة مرة أخرى'),
          'years_bad' => owBi('Please write a number of years from 1 to 20', 'يرجى كتابة عدد سنوات من 1 إلى 20'),
          'vat_bad' => owBi('Please check the VAT rate (0 to 50) and the VAT number (15 digits)', 'يرجى مراجعة نسبة الضريبة (0 إلى 50) والرقم الضريبي (15 رقما)'),
          'msg_missing' => owBi('Please write the subject and the message in both languages', 'يرجى كتابة الموضوع والرسالة باللغتين'),
          'msg_nobody' => owBi('Nobody matches this choice', 'لا أحد يطابق هذا الاختيار'),
          'queued' => owBi('The message is on its way. It goes out in small groups, a few every 2 minutes', 'الرسالة في طريقها. تُرسل على دفعات صغيرة، بضع رسائل كل دقيقتين'),
          'stopped' => owBi('Stopped. Nothing more is sent from this message', 'تم الإيقاف. لن يُرسل شيء آخر من هذه الرسالة'),
        ][$flash] ?? '' ?></div>
      <?php endif; ?>
      <?php require __DIR__ . '/owner_pages.php'; ?>
    </main>
  </div>
</div>
<?php endif; ?>
<script src="<?= owAsset('assets/js/owner.js') ?>" defer></script>
</body>
</html>
<?php
endif;
/* After the page has been sent: the next small group of messages, if one is waiting and it is time */
if ($V && owHasTable('sent_messages') && (int) owQ("SELECT COUNT(*) FROM sent_messages WHERE status = 'queued'") > 0) {
    if (function_exists('litespeed_finish_request')) litespeed_finish_request();
    elseif (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
    ignore_user_abort(true);
    owMsgTick();
}
