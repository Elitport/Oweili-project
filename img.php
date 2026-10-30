<?php
/* =====================================================================
 *  img.php — a smaller copy of an uploaded photo, sized for the screen
 *  img.php?f=uploads/artworks/x.jpg&w=800   (?src= works the same)
 *  Phones cannot cope with a page full of full-size camera photos
 *  (often 4000x3000 and several MB each), so cards ask for a copy that
 *  is only as wide as they need. The copy is made once by img_lib.php,
 *  kept in uploads/thumbs/, and after that pages link the copy directly
 *  as a normal picture file (owImgSized in artwork_lib.php).
 *  - Only files inside uploads/ (never uploads/consents/ or uploads/thumbs/),
 *    checked again after the real path is found. Any file name works
 *  - Only JPG / PNG / WEBP in, always JPG out, only the widths in img_lib.php
 *  - If a copy cannot be made, the original photo is sent instead
 *  No database, no login: these are the same public photos as before.
 * ===================================================================== */
require_once __DIR__ . '/img_lib.php';

$f = $_GET['f'] ?? $_GET['src'] ?? '';
$f = is_string($f) ? $f : '';
$w = (int) ($_GET['w'] ?? 0);

function owImgFail(int $code): void { http_response_code($code); header('Cache-Control: no-store'); exit; }

/* send a file with long caching (copy names change whenever the photo changes) */
function owImgSend(string $path, string $type, int $maxAge = 31536000): void {
    header('Content-Type: ' . $type);
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: public, max-age=' . $maxAge . ($maxAge > 86400 ? ', immutable' : '') . ', no-transform');   // CDNs must not re-shrink or convert it
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', (int) filemtime($path)) . ' GMT');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

if (!in_array($w, OW_THUMB_WIDTHS, true)) owImgFail(404);
$src = owThumbSource($f);
if ($src === '') owImgFail(404);

$copy = owMakeThumb($f, $w);
if ($copy !== '') owImgSend($copy, 'image/jpeg');

/* could not make a copy: send the original, but let browsers ask again tomorrow */
$types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
owImgSend($src, $types[strtolower(pathinfo($src, PATHINFO_EXTENSION))] ?? 'application/octet-stream', 86400);
