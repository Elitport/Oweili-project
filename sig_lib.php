<?php
/* =====================================================================
 *  sig_lib.php — signatures on the Certificate of Authenticity (owner, 9 Nov 2026)
 *  - The artist draws a signature once (signature.php, from Edit Profile).
 *  - Oweili has no signature, only its seal (owner, 9 Nov 2026).
 *  - Signatures are kept in the PRIVATE folder .private/signatures/ (blocked for
 *    visitors, kept by deploys through .gitignore) and are only ever placed
 *    INSIDE the certificate page as an embedded picture, never at a public
 *    address, so nobody can copy them from a link.
 *  - Every picture is redrawn as a clean PNG (no hidden data), at most
 *    900 × 300 px; anything that is not a real picture is refused.
 *  Tables: activity_log (signature_set / signature_remove)
 * ===================================================================== */

const OW_SIG_DIR = __DIR__ . '/.private/signatures';

function owSigKey(string $who): string {
    return 'artist-' . (int) $who;
}
function owSigPath(string $who): string { return OW_SIG_DIR . '/' . owSigKey($who) . '.png'; }
function owSigExists(string $who): bool { return is_file(owSigPath($who)); }

/* The signature as an embedded picture for the certificate ('' when none) */
function owSigDataUri(string $who): string {
    $f = owSigPath($who);
    return is_file($f) ? 'data:image/png;base64,' . base64_encode((string) file_get_contents($f)) : '';
}

/* Save picture bytes (PNG drawn on the page, or an uploaded PNG / JPG). Returns '' or an error code. */
function owSigSave(string $who, string $bytes): string {
    if ($bytes === '' || strlen($bytes) > 2 * 1024 * 1024) return 'size';
    if (!function_exists('imagecreatefromstring')) return 'server';
    $info = @getimagesizefromstring($bytes);
    if (!$info || !in_array($info[2] ?? 0, [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP], true)) return 'type';
    $src = @imagecreatefromstring($bytes);
    if (!$src) return 'type';
    $w = imagesx($src); $h = imagesy($src);
    if ($w < 40 || $h < 20) { imagedestroy($src); return 'small'; }
    $k = min(1, 900 / $w, 300 / $h);
    $nw = max(1, (int) round($w * $k)); $nh = max(1, (int) round($h * $k));
    $dst = imagecreatetruecolor($nw, $nh);
    imagealphablending($dst, false); imagesavealpha($dst, true);
    imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 255, 255, 255, 127));
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagedestroy($src);
    if (!is_dir(OW_SIG_DIR)) { @mkdir(OW_SIG_DIR, 0700, true); @file_put_contents(OW_SIG_DIR . '/.htaccess', "Require all denied\n"); }
    $ok = imagepng($dst, owSigPath($who) . '.tmp', 9);
    imagedestroy($dst);
    if (!$ok || !@rename(owSigPath($who) . '.tmp', owSigPath($who))) return 'server';
    @chmod(owSigPath($who), 0600);
    return '';
}

function owSigRemove(string $who): void { @unlink(owSigPath($who)); }

/* Error words for the forms */
function owSigError(string $code): array {
    return [
        'size'   => ['The signature is too large. Please try again.', 'التوقيع كبير جداً. حاول مرة أخرى.'],
        'type'   => ['Please use a PNG or JPG picture.', 'يرجى استخدام صورة PNG أو JPG.'],
        'small'  => ['The signature is too small. Please draw it bigger.', 'التوقيع صغير جداً. ارسمه بحجم أكبر.'],
        'empty'  => ['Please draw your signature first.', 'يرجى رسم توقيعك أولاً.'],
        'server' => ['We could not save it. Please try again.', 'تعذّر الحفظ. حاول مرة أخرى.'],
    ][$code] ?? ['We could not save it. Please try again.', 'تعذّر الحفظ. حاول مرة أخرى.'];
}
