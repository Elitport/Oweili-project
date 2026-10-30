<?php
/* =====================================================================
 *  favorite.php — the heart button in the Gallery and the Buyer dashboard.
 *  POST artwork_id + on (1 = save, 0 = remove) + csrf_token.
 *  Answers JSON: {"ok":true,"on":true} or {"ok":false,"error":"login"|"role"|...}
 *  Only signed-in buyers. Saving needs an artwork a visitor can see in
 *  the Gallery; removing always works (the work may have been sold since).
 *  At most 60 changes per 10 minutes per session.
 *  Tables: favorites, artworks, users, activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/mail_lib.php';
require_once __DIR__ . '/inquiry_lib.php';    // owInquiryArtwork(): the artworks visitors can see

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
$reply = function (bool $ok, array $more = [], int $code = 200): void {
    http_response_code($code);
    echo json_encode(['ok' => $ok] + $more);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST')  $reply(false, ['error' => 'method'], 405);
if (!verifyCsrf($_POST['csrf_token'] ?? null))          $reply(false, ['error' => 'csrf'], 403);
if (!isLoggedIn())                                      $reply(false, ['error' => 'login'], 401);
$me = currentUser();
if (!$me || ($me['role'] ?? '') !== 'collector')        $reply(false, ['error' => 'role'], 403);
if (!owHasTable('favorites'))                           $reply(false, ['error' => 'setup'], 503);

$now  = time();
$hits = array_values(array_filter((array) ($_SESSION['ow_fav_hits'] ?? []), fn($t) => is_int($t) && $t > $now - 600));
if (count($hits) >= 60) $reply(false, ['error' => 'rate'], 429);
$hits[] = $now;
$_SESSION['ow_fav_hits'] = $hits;

$aid = (int) ($_POST['artwork_id'] ?? 0);
$on  = (string) ($_POST['on'] ?? '1') === '1';
if ($aid <= 0) $reply(false, ['error' => 'artwork'], 422);
try {
    if ($on && !owInquiryArtwork($aid)) $reply(false, ['error' => 'artwork'], 404);
    if (owFavSet((int) $me['id'], $aid, $on)) {
        owLog($on ? 'favorite_add' : 'favorite_remove', 'artwork', $aid, '', ['type' => 'buyer', 'id' => (int) $me['id']]);
    }
} catch (Throwable $e) {
    error_log('favorite.php: ' . $e->getMessage());
    $reply(false, ['error' => 'server'], 500);
}
$reply(true, ['on' => $on]);
