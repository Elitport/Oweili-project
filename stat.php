<?php
/* =====================================================================
 *  stat.php — receives the owner dashboard's anonymous signal from
 *  assets/js/ow_stat.js (owner, 24 Nov 2026) and adds 1 to one daily
 *  count: day + what was seen + language + device. Answers 204 at once.
 *  Saves nothing about the person: no name, no account, no internet
 *  address. Not counted (the same rules as the visitor statistics,
 *  visit.php): robots, data centers and cloud servers, staff and the
 *  owner's browser, other websites. Only known pages and things that
 *  visitors can really see are counted (demo items are never in the
 *  database, so they are never counted).
 *  Tables: page_stats (owner_update.sql)
 * ===================================================================== */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/geo_lib.php';
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

const OW_STAT_PAGES = ['index', 'marketplace', 'explore', 'articles_list', 'article', 'art_map', 'art_place', 'institutes', 'course',
    'support', 'about', 'terms', 'privacy', 'artwork', 'artist_dashboard', 'register', 'register_artist', 'register_collector',
    'register_institute', 'login', 'cert_verify', 'auctions', 'gifts', 'commission', 'community', '404'];
const OW_STAT_STEPS = ['home', 'gallery', 'artwork', 'interest'];

$kind = (string) ($_POST['k'] ?? '');
$item = (string) ($_POST['i'] ?? '');
$lang = ($_POST['l'] ?? '') === 'ar' ? 'ar' : 'en';
$dev  = in_array($_POST['d'] ?? '', ['phone', 'tablet', 'computer'], true) ? (string) $_POST['d'] : 'computer';

$ok = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
   && in_array($_SERVER['HTTP_SEC_FETCH_SITE'] ?? 'same-origin', ['same-origin', ''], true)
   && empty($_COOKIE['ow_nostat'])
   && empty($_SESSION['is_admin'])
   && !(function_exists('isLoggedIn') && isLoggedIn() && in_array((string) (currentUser()['role'] ?? ''), ['admin', 'sub_admin'], true))
   && !owIsBot((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''))
   && strlen($item) <= 80;

/* a quick first check of the shape, before anything is read from the database */
if ($ok) {
    switch ($kind) {
        case 'visit':   $ok = $item === ''; break;
        case 'page':    $ok = in_array($item, OW_STAT_PAGES, true); break;
        case 'step':    $ok = in_array($item, OW_STAT_STEPS, true); break;
        case 'artwork':
        case 'artist':  $ok = (bool) preg_match('/^[1-9][0-9]{0,9}$/', $item); break;
        case 'style':   $ok = (bool) preg_match('/^[a-z][a-z0-9_]{1,40}$/', $item); break;
        case 'place':   $ok = (bool) preg_match('/^[a-z0-9-]{2,20}\/[a-z0-9-]{1,58}$/', $item); break;
        case 'region':  $ok = (bool) preg_match('/^[a-z0-9-]{2,20}$/', $item); break;
        default:        $ok = false;
    }
}
/* at most 150 counts from one browser session a day (the browser already sends each thing once a day) */
if ($ok) {
    $day = gmdate('Ymd');
    $n = (array) ($_SESSION['ow_stat_n'] ?? []);
    if (($n['d'] ?? '') !== $day) $n = ['d' => $day, 'c' => 0];
    if ((int) $n['c'] >= 150) $ok = false; else { $n['c'] = (int) $n['c'] + 1; $_SESSION['ow_stat_n'] = $n; }
}

http_response_code(204);
header('Content-Length: 0');
if (function_exists('litespeed_finish_request')) litespeed_finish_request();
elseif (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
ignore_user_abort(true);

if (!$ok || !owHasTable('page_stats')) exit;
try {
    require_once __DIR__ . '/visitor_lib.php';
    if (owIsDataCenter(owVisitorIp())) exit;   // data centers and cloud servers are not people
    /* only things a visitor can really see */
    if ($kind === 'artwork') {
        require_once __DIR__ . '/mail_lib.php';
        require_once __DIR__ . '/inquiry_lib.php';
        if (!owInquiryArtwork((int) $item)) exit;
    } elseif ($kind === 'artist') {
        $st = getDB()->prepare("SELECT 1 FROM users WHERE id = :id AND role = 'artist' AND is_approved = 1 AND COALESCE(is_banned, 0) = 0");
        $st->execute([':id' => (int) $item]);
        if (!$st->fetchColumn()) exit;
    } elseif ($kind === 'style') {
        $types = require __DIR__ . '/art_types.php';
        if (!isset($types[$item])) exit;
    } elseif ($kind === 'place' || $kind === 'region') {
        require_once __DIR__ . '/artmap_lib.php';
        if ($kind === 'region') { if (!isset(OW_MAP_REGIONS[$item])) exit; }
        else {
            $found = false;
            foreach (owMapDirectory() as $p) if (($p['region'] . '/' . $p['slug']) === $item) { $found = true; break; }
            if (!$found) exit;
        }
    }
    getDB()->prepare('INSERT INTO page_stats (day, kind, item, lang, device, views) VALUES (CURDATE(), :k, :i, :l, :d, 1)
                      ON DUPLICATE KEY UPDATE views = views + 1')
           ->execute([':k' => $kind, ':i' => $item, ':l' => $lang, ':d' => $dev]);
} catch (Throwable $e) {
    error_log('stat.php: ' . $e->getMessage());
}
