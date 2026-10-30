<?php
/* =====================================================================
 *  img.php — a smaller copy of an uploaded photo, sized for the screen
 *  img.php?f=uploads/artworks/x.jpg&w=800
 *  Phones cannot cope with a page full of full-size camera photos
 *  (often 4000x3000 and several MB each), so cards ask for a copy that
 *  is only as wide as they need. Each copy is made once and kept in
 *  uploads/thumbs/, then served straight from there.
 *  - Only files inside uploads/ (never uploads/consents/ or uploads/thumbs/)
 *  - Only JPG / PNG / WEBP, only the widths below. Any file name works
 *    (older uploads have dots or spaces in their names); the real-path
 *    check below keeps every request inside uploads/
 *  - Made with GD, or with Imagick when GD is missing or short of memory
 *  - If a copy cannot be made, the original photo is sent instead
 *  No database, no login: these are the same public photos as before.
 * ===================================================================== */
$WIDTHS = [400, 800, 1600];

$f = isset($_GET['f']) && is_string($_GET['f']) ? $_GET['f'] : '';
$w = (int) ($_GET['w'] ?? 0);

function owImgFail(int $code): void { http_response_code($code); header('Cache-Control: no-store'); exit; }

if (!in_array($w, $WIDTHS, true)
    || strpos($f, '..') !== false || strpos($f, "\0") !== false || strpos($f, '\\') !== false
    || !preg_match('#^uploads/(?!consents/|thumbs/)[^/][^?\#]*\.(jpe?g|png|webp)$#i', $f)) {
    owImgFail(404);
}
$uploads = realpath(__DIR__ . '/uploads');
$src     = realpath(__DIR__ . '/' . $f);
if ($uploads === false || $src === false || strpos($src, $uploads . DIRECTORY_SEPARATOR) !== 0 || !is_file($src)
    || preg_match('#^(consents|thumbs)(/|$)#i', str_replace(DIRECTORY_SEPARATOR, '/', substr($src, strlen($uploads) + 1)))) {
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
if (!$info) $sendOriginal();
[$ow, $oh] = $info;
$orient = 1;                                            // old uploads may still carry the phone's rotation flag
if (($info[2] ?? 0) === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
    $orient = (int) ((@exif_read_data($src) ?: [])['Orientation'] ?? 1);
}
$sideways = in_array($orient, [5, 6, 7, 8], true);
if (($sideways ? $oh : $ow) <= $w) $sendOriginal();     // already small enough

if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
$tmp = $cache . '.' . bin2hex(random_bytes(4)) . '.tmp';
$done = function () use ($tmp, $cache, $mtime): void {
    if (@rename($tmp, $cache)) owImgSend($cache, 'image/jpeg', $mtime);
    if (is_file($tmp)) {                                // could not keep the copy: send it once
        header('Content-Type: image/jpeg');
        header('Cache-Control: public, max-age=86400');
        readfile($tmp);
        @unlink($tmp);
        exit;
    }
};

/* GD needs about 5 bytes per pixel for the open photo, plus room for the copy */
if (function_exists('ini_set')) @ini_set('memory_limit', '384M');   // some hosts switch ini_set off
$lim = trim((string) ini_get('memory_limit'));
$limBytes = $lim === '-1' ? PHP_INT_MAX : (int) $lim * ([ 'g' => 1073741824, 'm' => 1048576, 'k' => 1024 ][strtolower(substr($lim, -1))] ?? 1);
$gdFits = function_exists('imagecreatefromstring') && ($ow * $oh * 5 * 1.7 + $w * $w * 5 + memory_get_usage()) < $limBytes;

if (!$gdFits && class_exists('Imagick')) {
    try {
        $im = new Imagick($src);
        if ($orient !== 1 && method_exists($im, 'autoOrient')) $im->autoOrient();
        $im->setImageBackgroundColor('white');
        $im = $im->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);  // see-through PNGs sit on white
        $im->thumbnailImage($w, 0);
        $im->setImageFormat('jpeg');
        $im->setImageCompressionQuality(82);
        $im->setInterlaceScheme(Imagick::INTERLACE_PLANE);
        $im->stripImage();
        if ($im->writeImage($tmp)) { $im->clear(); $done(); }
    } catch (Throwable $e) { /* fall through */ }
    @unlink($tmp);
    $sendOriginal();
}
if (!$gdFits) $sendOriginal();

$img = @imagecreatefromstring((string) file_get_contents($src));
if ($img === false) $sendOriginal();
if ($orient === 3) $img = imagerotate($img, 180, 0);
elseif ($orient === 6) { $img = imagerotate($img, -90, 0); [$ow, $oh] = [$oh, $ow]; }
elseif ($orient === 8) { $img = imagerotate($img, 90, 0);  [$ow, $oh] = [$oh, $ow]; }

$nh  = max(1, (int) round($oh * $w / $ow));
$out = imagecreatetruecolor($w, $nh);
imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));   // see-through PNGs sit on white
imagecopyresampled($out, $img, 0, 0, 0, 0, $w, $nh, $ow, $oh);
imagedestroy($img);
imageinterlace($out, true);                                       // shows gradually on slow connections
$ok = @imagejpeg($out, $tmp, 82);
imagedestroy($out);
if ($ok) $done();
@unlink($tmp);
$sendOriginal();
