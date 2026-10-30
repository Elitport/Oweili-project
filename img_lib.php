<?php
/* =====================================================================
 *  img_lib.php — makes the small, phone-friendly copies of uploaded
 *  photos (400 / 800 / 1600 pixels wide, always JPG) and keeps them in
 *  uploads/thumbs/. Used by:
 *    - img.php            (makes a missing copy when a page asks for it)
 *    - artwork_lib.php    (links a finished copy directly as a normal file;
 *                          makes copies straight after an artwork photo is saved)
 *    - photo_check.php    ("Make all small copies" button for admins)
 *
 *  Only one heavy copy is made at a time on the server (a lock file), so
 *  a phone opening a Gallery page full of new photos cannot run the
 *  hosting out of memory. Copies are always JPG: older iPhones cannot
 *  show WEBP, and JPG is the lightest choice for paintings and photos.
 * ===================================================================== */

const OW_THUMB_WIDTHS = [400, 800, 1600];

/* "uploads/…/x.jpg" when it is a real photo inside uploads/ we may resize, otherwise '' */
function owThumbSource(string $rel): string {
    $rel = ltrim($rel, '/');
    if ($rel === '' || strpos($rel, '..') !== false || strpos($rel, "\0") !== false || strpos($rel, '\\') !== false
        || !preg_match('#^uploads/(?!consents/|thumbs/)[^/][^?\#]*\.(jpe?g|png|webp)$#i', $rel)) return '';
    $uploads = realpath(__DIR__ . '/uploads');
    $src     = realpath(__DIR__ . '/' . $rel);
    if ($uploads === false || $src === false || strpos($src, $uploads . DIRECTORY_SEPARATOR) !== 0 || !is_file($src)
        || preg_match('#^(consents|thumbs)(/|$)#i', str_replace(DIRECTORY_SEPARATOR, '/', substr($src, strlen($uploads) + 1)))) return '';
    return $src;
}

/* Where the copy of $rel at width $w lives (it may not exist yet) */
function owThumbFile(string $rel, int $w, string $src): string {
    return __DIR__ . '/uploads/thumbs/' . sha1(ltrim($rel, '/') . '|' . (int) filemtime($src) . '|' . $w) . '.jpg';
}

/* Memory in bytes PHP may use now (after asking for 384M) */
function owMemLimitBytes(): int {
    if (function_exists('ini_set')) @ini_set('memory_limit', '384M');
    $lim = trim((string) ini_get('memory_limit'));
    if ($lim === '-1' || $lim === '') return PHP_INT_MAX;
    return (int) $lim * ([ 'g' => 1073741824, 'm' => 1048576, 'k' => 1024 ][strtolower(substr($lim, -1))] ?? 1);
}

/* Opens a photo with GD straight from the file (no extra copy in memory) */
function owGdOpen(string $src, int $type) {
    if ($type === IMAGETYPE_JPEG && function_exists('imagecreatefromjpeg')) return @imagecreatefromjpeg($src);
    if ($type === IMAGETYPE_PNG  && function_exists('imagecreatefrompng'))  return @imagecreatefrompng($src);
    if ($type === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) return @imagecreatefromwebp($src);
    return false;
}

/* Makes (or finds) the copy of $rel at width $w.
   Returns the full path of the JPG copy, or '' if it cannot be made. */
function owMakeThumb(string $rel, int $w): string {
    if (!in_array($w, OW_THUMB_WIDTHS, true)) return '';
    $src = owThumbSource($rel);
    if ($src === '') return '';
    $cache = owThumbFile($rel, $w, $src);
    if (is_file($cache)) return $cache;

    $info = @getimagesize($src);
    if (!$info || !in_array($info[2] ?? 0, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) return '';
    [$ow, $oh] = $info;
    $orient = 1;                                                 // old uploads may still carry the phone's rotation flag
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $orient = (int) ((@exif_read_data($src) ?: [])['Orientation'] ?? 1);
    }
    $sideways = in_array($orient, [5, 6, 7, 8], true);
    $tw = min($w, $sideways ? $oh : $ow);                        // never make a copy bigger than the photo

    $dir = dirname($cache);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    if (!is_dir($dir) || !is_writable($dir)) return '';

    /* one heavy copy at a time; others wait their turn (up to ~20 seconds) */
    $lock = @fopen($dir . '/.lock', 'c');
    if ($lock) {
        $waited = 0;
        while (!flock($lock, LOCK_EX | LOCK_NB)) {
            if ($waited >= 20000) { fclose($lock); return ''; }
            usleep(100000); $waited += 100;
        }
        if (is_file($cache)) { flock($lock, LOCK_UN); fclose($lock); return $cache; }   // made while we waited
    }
    $done = function (string $out) use ($lock): string {
        if ($lock) { flock($lock, LOCK_UN); fclose($lock); }
        return $out;
    };
    $tmp = $cache . '.' . bin2hex(random_bytes(4)) . '.tmp';

    /* GD needs about 5 bytes per pixel for the open photo, plus room for the copy */
    $gdFits = function_exists('imagecreatetruecolor')
        && ($ow * $oh * 5 * 1.3 + $tw * $tw * 5 + memory_get_usage()) < owMemLimitBytes();

    if ($gdFits) {
        $img = owGdOpen($src, (int) $info[2]);
        if ($img !== false) {
            if ($orient === 3) $img = imagerotate($img, 180, 0);
            elseif ($orient === 6) { $img = imagerotate($img, -90, 0); [$ow, $oh] = [$oh, $ow]; }
            elseif ($orient === 8) { $img = imagerotate($img, 90, 0);  [$ow, $oh] = [$oh, $ow]; }
            $nh  = max(1, (int) round($oh * $tw / $ow));
            $out = imagecreatetruecolor($tw, $nh);
            imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));   // see-through PNGs sit on white
            imagecopyresampled($out, $img, 0, 0, 0, 0, $tw, $nh, $ow, $oh);
            imagedestroy($img);
            imageinterlace($out, true);                                       // shows gradually on slow connections
            $ok = @imagejpeg($out, $tmp, 82);
            imagedestroy($out);
            if ($ok && @rename($tmp, $cache)) return $done($cache);
            @unlink($tmp);
        }
    }
    if (class_exists('Imagick')) {                                            // backup when GD is missing or short of memory
        try {
            $im = new Imagick();
            $im->setOption('jpeg:size', $tw * 2 . 'x' . $tw * 2);             // opens big JPGs at a smaller size
            $im->readImage($src);
            if ($orient !== 1 && method_exists($im, 'autoOrient')) $im->autoOrient();
            $im->setImageBackgroundColor('white');
            $im = $im->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            $im->thumbnailImage($tw, 0);
            $im->setImageFormat('jpeg');
            $im->setImageCompressionQuality(82);
            $im->setInterlaceScheme(Imagick::INTERLACE_PLANE);
            $im->stripImage();
            $ok = $im->writeImage($tmp);
            $im->clear();
            if ($ok && @rename($tmp, $cache)) return $done($cache);
        } catch (Throwable $e) { /* fall through */ }
        @unlink($tmp);
    }
    return $done('');
}

/* Makes the two copies the Gallery uses (400 and 800) right after a photo is saved */
function owMakeCardThumbs(string $rel): void {
    foreach ([400, 800] as $w) { owMakeThumb($rel, $w); }
}
