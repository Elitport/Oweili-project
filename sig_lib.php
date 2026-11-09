<?php
/* =====================================================================
 *  sig_lib.php — signatures on the Certificate of Authenticity (owner, 9 Nov 2026)
 *  - The artist draws a signature once (signature.php, from Edit Profile).
 *  - Oweili has no signature, only its seal (owner, 9 Nov 2026).
 *  - Signatures are kept in the PRIVATE folder .private/signatures/ (blocked for
 *    visitors, kept by deploys through .gitignore) and are only ever placed
 *    INSIDE the certificate page as an embedded picture, never at a public
 *    address, so nobody can copy them from a link.
 *  - A new or changed signature waits for the admin (owner, 9 Nov 2026): it is
 *    saved apart as artist-N.pending.png and sent to Admin → Waiting for
 *    approval (pending_changes, as a profile change with data signature =
 *    new / remove). Certificates show only the approved artist-N.png, so until
 *    the admin approves they keep the last approved signature, or none.
 *  - Every picture is redrawn as a clean PNG (no hidden data), at most
 *    900 × 300 px; anything that is not a real picture is refused.
 *  Tables: activity_log (signature_set / signature_remove)
 * ===================================================================== */

const OW_SIG_DIR = __DIR__ . '/.private/signatures';

function owSigKey(string $who): string {
    return 'artist-' . (int) $who;
}
function owSigPath(string $who, bool $pending = false): string { return OW_SIG_DIR . '/' . owSigKey($who) . ($pending ? '.pending' : '') . '.png'; }
function owSigExists(string $who, bool $pending = false): bool { return is_file(owSigPath($who, $pending)); }

/* The signature as an embedded picture for the certificate ('' when none) */
function owSigDataUri(string $who, bool $pending = false): string {
    $f = owSigPath($who, $pending);
    return is_file($f) ? 'data:image/png;base64,' . base64_encode((string) file_get_contents($f)) : '';
}

/* Save picture bytes (PNG drawn on the page, or an uploaded PNG / JPG). Returns '' or an error code. */
function owSigSave(string $who, string $bytes, bool $pending = false): string {
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
    $to = owSigPath($who, $pending);
    $ok = imagepng($dst, $to . '.tmp', 9);
    imagedestroy($dst);
    if (!$ok || !@rename($to . '.tmp', $to)) return 'server';
    @chmod($to, 0600);
    return '';
}

function owSigRemove(string $who, bool $pending = false): void { @unlink(owSigPath($who, $pending)); }

/* The admin approved the waiting signature: it goes on the certificates ('new'), or the old one is removed ('remove') */
function owSigApprovePending(int $uid, string $what): void {
    if ($what === 'remove') { owSigRemove((string) $uid); owSigRemove((string) $uid, true); return; }
    $from = owSigPath((string) $uid, true);
    if (is_file($from) && @rename($from, owSigPath((string) $uid))) @chmod(owSigPath((string) $uid), 0600);
}

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
