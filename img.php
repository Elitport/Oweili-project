<?php
/* =====================================================================
 *  img.php — a smaller copy of an uploaded photo, sized for the screen
 *  img.php?f=uploads/artworks/x.jpg&w=800
 *  Phones cannot cope with a page full of full-size camera photos
 *  (often 4000x3000 and several MB each), so cards ask for a copy that
 *  is only as wide as they need. Each copy is made once and kept in
 *  uploads/thumbs/, then served straight from there.
 *  - Only files inside uploads/ (never uploads/consents/ or uploads/thumbs/)
 *  - Only JPG / PNG / WEBP, only the widths below
 *  - If a copy cannot be made, the original photo is sent instead
 *  No database, no login: these are the same public photos as before.
 * ===================================================================== */
$WIDTHS = [400, 800, 1600];

$f = isset($_GET['f']) && is_string($_GET['f']) ? $_GET['f'] : '';
$w = (int) ($_GET['w'] ?? 0);

function owImgFail(int $code): void { http_response_code($code); header('Cache-Control: no-store'); exit; }

if (!in_array($w, $WIDTHS, true)
    || strpos($f, '..') !== false
    || !preg_match('#^uploads/(?!consents/|thumbs/)[A-Za-z0-9_\-/]+\.(jpe?g|png|webp)$#i', $f)) {
    owImgFail(404);
}
$uploads = realpath(__DIR__ . '/uploads');
$src     = realpath(__DIR__ . '/' . $f);
if ($uploads === false || $src === false || strpos($src, $uploads . DIRECTORY_SEPARATOR) !== 0 || !is_file($src)) {
    owImgFail(404);
}

$mtime = (int) filemtime($src);
$types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];

/* send a file with long caching (file names are random, so they never change) */
function owImgSend(string $path, string $type, int $mtime): void {
    header('Content-Type: ' . $type);
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}
$sendOriginal = function () use ($src, $f, $types, $mtime): void {
    owImgSend($src, $types[strtolower(pathinfo($f, PATHINFO_EXTENSION))], $mtime);
};

$cacheDir = $uploads . '/thumbs';
$cache    = $cacheDir . '/' . sha1($f . '|' . $mtime . '|' . $w) . '.jpg';
if (is_file($cache)) owImgSend($cache, 'image/jpeg', $mtime);

$info = @getimagesize($src);
if (!$info || !function_exists('imagecreatefromstring')) $sendOriginal();
[$ow, $oh] = $info;
if ($ow <= $w) $sendOriginal();                       // already small enough

@ini_set('memory_limit', '384M');                       // big camera photos need room to be opened
$img = @imagecreatefromstring((string) file_get_contents($src));
if ($img === false) $sendOriginal();

/* old uploads may still carry the phone's rotation flag */
if (($info[2] ?? 0) === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
    $o = (int) ((@exif_read_data($src) ?: [])['Orientation'] ?? 1);
    if ($o === 3) $img = imagerotate($img, 180, 0);
    elseif ($o === 6) { $img = imagerotate($img, -90, 0); [$ow, $oh] = [$oh, $ow]; }
    elseif ($o === 8) { $img = imagerotate($img, 90, 0);  [$ow, $oh] = [$oh, $ow]; }
}
if ($ow <= $w) { imagedestroy($img); $sendOriginal(); }

$nh  = max(1, (int) round($oh * $w / $ow));
$out = imagecreatetruecolor($w, $nh);
imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));   // see-through PNGs sit on white
imagecopyresampled($out, $img, 0, 0, 0, 0, $w, $nh, $ow, $oh);
imagedestroy($img);
imageinterlace($out, true);                                       // shows gradually on slow connections

if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
$tmp = $cache . '.' . bin2hex(random_bytes(4)) . '.tmp';
if (@imagejpeg($out, $tmp, 82) && @rename($tmp, $cache)) {
    imagedestroy($out);
    owImgSend($cache, 'image/jpeg', $mtime);
}
@unlink($tmp);
/* could not save the copy (folder not writable): send it once without keeping it */
header('Content-Type: image/jpeg');
header('Cache-Control: public, max-age=86400');
imagejpeg($out, null, 82);
imagedestroy($out);
