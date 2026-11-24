<?php
/* =====================================================================
 *  follow.php — a signed-in collector follows or unfollows an artist
 *  (owner, 24 Nov 2026). POST only, security token, the collector's own
 *  account only, only approved artists who are not suspended.
 *  Then back to the artist's public page or to the collector dashboard.
 *  Tables: artist_follows, users, activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/collector_lib.php';
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

$aid  = (int) ($_POST['artist_id'] ?? 0);
$from = ($_POST['from'] ?? '') === 'dash' ? 'collector_dashboard.php?tab=following' : 'artist_dashboard.php?id=' . $aid;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || $aid <= 0) { header('Location: index.php'); exit; }
if (!isLoggedIn() || !($me = currentUser())) { header('Location: login.php?next=' . rawurlencode('artist_dashboard.php?id=' . $aid)); exit; }
if (($me['role'] ?? '') !== 'collector' || !empty($me['is_banned'])) { header('Location: ' . $from); exit; }
if (!verifyCsrf($_POST['csrf_token'] ?? null)) { header('Location: ' . $from . (str_contains($from, '?') ? '&' : '?') . 'fw=expired#follow'); exit; }

$on = ($_POST['do'] ?? '') === 'follow';
if (owFollowSet((int) $me['id'], $aid, $on)) {
    owLog($on ? 'artist_follow' : 'artist_unfollow', 'user', $aid, '', ['type' => 'buyer', 'id' => (int) $me['id']]);
}
header('Location: ' . $from . ($from === 'collector_dashboard.php?tab=following' ? '&done=' . ($on ? 'follow' : 'unfollow') : '#follow'));
exit;
