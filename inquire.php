<?php
/* =====================================================================
 *  inquire.php — receives the "I'm Interested" form from the Gallery
 *  popup (marketplace.php) and answers in JSON: {"ok":true} or
 *  {"ok":false,"error":"code"}.
 *  Checks: POST only, security token (CSRF), a hidden trap field for
 *  robots, at most 5 inquiries per visitor per hour, and the artwork
 *  must be one a visitor can see in the Gallery.
 *  A signed-in buyer's request is linked to their account (buyer_id).
 *  Tables: artwork_inquiries (created automatically), artworks, users, activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/artwork_lib.php';
require_once __DIR__ . '/mail_lib.php';
require_once __DIR__ . '/inquiry_lib.php';
require_once __DIR__ . '/platform_lib.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
$reply = function (bool $ok, string $err = '', int $code = 200): void {
    http_response_code($code);
    echo json_encode($ok ? ['ok' => true] : ['ok' => false, 'error' => $err]);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST')   $reply(false, 'method', 405);
if (!verifyCsrf($_POST['csrf_token'] ?? null))           $reply(false, 'csrf', 403);
if (trim((string) ($_POST['website'] ?? '')) !== '')     $reply(true);   // robot filled the hidden field: pretend all is well

/* at most 5 inquiries per visitor per hour */
$now  = time();
$sent = array_values(array_filter((array) ($_SESSION['ow_inquiries'] ?? []), fn($t) => is_int($t) && $t > $now - 3600));
if (count($sent) >= 5) $reply(false, 'rate', 429);

[$v, $err] = owCheckInquiry($_POST);
if ($err !== '') $reply(false, $err, 422);

try {
    $aw = owInquiryArtwork((int) ($_POST['artwork_id'] ?? 0));
    if (!$aw) $reply(false, 'artwork', 404);
    /* a signed-in buyer: the request is linked to their account (their Buyer dashboard) */
    $buyer = isLoggedIn() ? currentUser() : null;
    owSaveInquiry($aw, $v, ($buyer && ($buyer['role'] ?? '') === 'collector') ? (int) $buyer['id'] : 0);
} catch (Throwable $e) {
    error_log('inquire.php: ' . $e->getMessage());
    $reply(false, 'server', 500);
}
$sent[] = $now;
$_SESSION['ow_inquiries'] = $sent;
$reply(true);
