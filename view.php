<?php
/* =====================================================================
 *  view.php — counts one view when a visitor opens an artwork's window
 *  in the Gallery (marketplace.php sends it quietly in the background).
 *  POST artwork_id. Counted once per visitor (browser session) per
 *  artwork per day, only for artworks a visitor can see, and never when
 *  the artist looks at their own work. Answers 204 with no content.
 *  Tables: artworks (view_count)
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/mail_lib.php';
require_once __DIR__ . '/inquiry_lib.php';     // owInquiryArtwork(): artworks visitors can see

header('Cache-Control: no-store');
$done = function (): void { http_response_code(204); exit; };
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !owHasColumn('artworks', 'view_count')) $done();

$aid = (int) ($_POST['artwork_id'] ?? 0);
if ($aid <= 0) $done();
$day  = gmdate('Ymd');
$seen = (array) ($_SESSION['ow_seen'] ?? []);
if (($seen[$aid] ?? '') === $day) $done();
if (count($seen) > 300) $seen = array_slice($seen, -200, null, true);     // keep the session small

try {
    $aw = owInquiryArtwork($aid);
    if (!$aw) $done();
    if (!empty($_SESSION['user_id']) && (int) $_SESSION['user_id'] === (int) $aw['artist_id']) $done();
    getDB()->prepare('UPDATE artworks SET view_count = view_count + 1, updated_at = updated_at WHERE id = :id')->execute([':id' => $aid]);
    $seen[$aid] = $day;
    $_SESSION['ow_seen'] = $seen;
} catch (Throwable $e) {
    error_log('view.php: ' . $e->getMessage());
}
$done();
