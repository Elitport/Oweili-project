<?php
/* =====================================================================
 *  identity_lib.php — identity checks at sign-up (owner, 27 Nov 2026)
 *
 *  Artists (required at sign-up): an identity document — Saudi national ID,
 *  OR Iqama, OR passport for non-residents — with its type, number, expiry
 *  date and a clear photo; plus 3 to 5 photos of past artworks (the existing
 *  "Your artworks" box) and an optional Instagram or website link.
 *  Institutes (added to their sign-up): the Saudi National Address
 *  certificate, 2 to 5 photos of the premises or of students' work, and an
 *  official social account or website (now required).
 *
 *  Privacy (owner):
 *   - every file is ENCRYPTED (AES-256-GCM) and kept OUTSIDE the public
 *     folder (the folder above the website when the server allows it, else
 *     the blocked .private/ folder); the document number is encrypted in
 *     the database and only its last 4 digits are shown in lists
 *   - only the owner opens them (id_doc.php), and the sub-admins the owner
 *     ticks ("Identity documents" in Owner settings and team); every
 *     opening is written to the activity log; never cached
 *   - kept after approval and after the account closes (deleted, or the
 *     sign-up refused) for the years set in Owner settings (default 5), then
 *     the files and the number are deleted automatically (once a day)
 *   - a required tick at sign-up, and a line in the Privacy Policy
 *  The owner approves or refuses (with a reason) in Waiting for approval.
 *  Key: OWEILI_ID_KEY in config.php (64 hex characters) when set; otherwise
 *  a key file made once next to the files (id.key, 0600). Without the key
 *  the files cannot be read, so the key must be backed up with the server.
 *  Tables: identity_checks, identity_photos (identity_update.sql),
 *          users, site_settings, activity_log
 * ===================================================================== */
require_once __DIR__ . '/platform_lib.php';

const OW_ID_TYPES = ['national_id' => ['Saudi national ID', 'الهوية الوطنية السعودية'], 'iqama' => ['Iqama (resident ID)', 'الإقامة'], 'passport' => ['Passport (non-residents)', 'جواز السفر (لغير المقيمين)']];
const OW_ID_FILE_MAX = 10 * 1024 * 1024;   // 10 MB each
const OW_ID_KEEP_DEFAULT = 5;              // years after the account closes
const OW_ID_CONSENT = ['Oweili keeps your identity document for 5 years after your account closes, for fraud prevention and legal claims',
                       'تحتفظ أويلي بوثيقة هويتك لمدة 5 سنوات بعد إغلاق حسابك، لمنع الاحتيال وللمطالبات القانونية'];

function owIdReady(): bool { return owHasTable('identity_checks') && owHasTable('identity_photos'); }

/* ---------------- where the files live, and the key ---------------- */
function owIdDir(): string {
    static $dir = null;
    if ($dir !== null) return $dir;
    if (defined('OWEILI_ID_DIR') && (string) OWEILI_ID_DIR !== '') $base = rtrim((string) OWEILI_ID_DIR, '/') . '/';
    else {
        $above = dirname(__DIR__) . '/oweili-private/';   // outside public_html on Hostinger
        $base = (is_dir($above) || @mkdir($above, 0700, true)) && is_writable($above) ? $above : __DIR__ . '/.private/';
    }
    $dir = $base . 'id-docs/';
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    if (!is_file($dir . '.htaccess')) @file_put_contents($dir . '.htaccess', "Require all denied\nDeny from all\n");
    return $dir;
}
function owIdKey(): string {
    if (defined('OWEILI_ID_KEY') && preg_match('/^[a-f0-9]{64}$/i', (string) OWEILI_ID_KEY)) return hex2bin((string) OWEILI_ID_KEY);
    $f = owIdDir() . 'id.key';
    if (!is_file($f)) { @file_put_contents($f, bin2hex(random_bytes(32)), LOCK_EX); @chmod($f, 0600); }
    $k = trim((string) @file_get_contents($f));
    if (!preg_match('/^[a-f0-9]{64}$/', $k)) throw new RuntimeException('identity key missing');
    return hex2bin($k);
}
function owIdEncrypt(string $plain): string {
    $iv = random_bytes(12); $tag = '';
    $c = openssl_encrypt($plain, 'aes-256-gcm', owIdKey(), OPENSSL_RAW_DATA, $iv, $tag);
    if ($c === false) throw new RuntimeException('encrypt failed');
    return 'OWID1' . $iv . $tag . $c;
}
function owIdDecrypt(string $blob): ?string {
    if (strlen($blob) < 33 || substr($blob, 0, 5) !== 'OWID1') return null;
    $p = openssl_decrypt(substr($blob, 33), 'aes-256-gcm', owIdKey(), OPENSSL_RAW_DATA, substr($blob, 5, 12), substr($blob, 17, 16));
    return $p === false ? null : $p;
}

/* ---------------- settings ---------------- */
function owIdKeepYears(): int {
    require_once __DIR__ . '/gallery_lib.php';
    $y = (int) owSetting('id_keep_years', (string) OW_ID_KEEP_DEFAULT);
    return $y >= 1 && $y <= 20 ? $y : OW_ID_KEEP_DEFAULT;
}
function owIdConsentText(): array {
    $y = owIdKeepYears();
    return [str_replace('5 years', $y . ($y === 1 ? ' year' : ' years'), OW_ID_CONSENT[0]), str_replace('5 سنوات', $y . ($y === 1 ? ' سنة' : ($y === 2 ? ' سنتين' : ($y <= 10 ? ' سنوات' : ' سنة'))), OW_ID_CONSENT[1])];
}
function owIdSubadmins(): array {
    require_once __DIR__ . '/gallery_lib.php';
    $j = json_decode((string) owSetting('subadmin_id_docs', '[]'), true);
    return is_array($j) ? array_values(array_map('intval', $j)) : [];
}
/* The owner, and the sub-admins the owner ticked */
function owIdAllowed(): bool {
    require_once __DIR__ . '/admin_shell.php';
    if (owIsAdmin()) return true;
    if (function_exists('isLoggedIn') && isLoggedIn() && ($u = currentUser()) && ($u['role'] ?? '') === 'sub_admin' && empty($u['is_banned']))
        return in_array((int) $u['id'], owIdSubadmins(), true);
    return false;
}

/* ---------------- one file: checked, cleaned, encrypted ---------------- */
/* $pdf: a PDF is also accepted (the National Address certificate). Photos are redrawn (no location or camera data).
   Returns ['ok' => true, 'name' => …] or ['ok' => false, 'err' => [en, ar] | null (no file)] */
function owIdStoreFile(array $file, bool $pdf = false): array {
    $E = fn(string $en, string $ar) => ['ok' => false, 'err' => [$en, $ar]];
    $kinds = $pdf ? ['Please upload a PDF or a photo (JPG, PNG or WebP)', 'يرجى رفع ملف PDF أو صورة (JPG أو PNG أو WebP)'] : ['Please upload a photo (JPG, PNG or WebP)', 'يرجى رفع صورة (JPG أو PNG أو WebP)'];
    if (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return ['ok' => false, 'err' => null];
    if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || (int) $file['size'] > OW_ID_FILE_MAX) return $E('Each file can be up to 10 MB', 'حجم كل ملف حتى 10 ميغابايت');
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) || (int) $file['size'] <= 0) return $E('The file could not be saved. Please try again.', 'تعذّر حفظ الملف. حاول مرة أخرى.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if ($mime === 'application/pdf' && $pdf) {
        $bytes = (string) file_get_contents($file['tmp_name']);
        if (substr($bytes, 0, 5) !== '%PDF-') return $E($kinds[0], $kinds[1]);
        $ext = 'pdf';
    } elseif (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        $info = @getimagesize($file['tmp_name']);
        $img = ($info && function_exists('imagecreatefromstring')) ? @imagecreatefromstring((string) file_get_contents($file['tmp_name'])) : false;
        if (!$img || $info[0] < 300 || $info[1] < 200) return $img ? $E('The photo is too small to read. Please take a clear, close photo', 'الصورة صغيرة جدا ولا تُقرأ. يرجى التقاط صورة واضحة وقريبة') : $E($kinds[0], $kinds[1]);
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $o = (int) ((@exif_read_data($file['tmp_name']))['Orientation'] ?? 1);
            if ($o === 3) $img = imagerotate($img, 180, 0); elseif ($o === 6) $img = imagerotate($img, -90, 0); elseif ($o === 8) $img = imagerotate($img, 90, 0);
        }
        $w = imagesx($img); $h = imagesy($img);
        $k = min(1, 2400 / max($w, $h)); $nw = (int) round($w * $k); $nh = (int) round($h * $k);
        $out = imagecreatetruecolor($nw, $nh); imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
        imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        ob_start(); imagejpeg($out, null, 88); $bytes = (string) ob_get_clean(); imagedestroy($img); imagedestroy($out);
        $ext = 'jpg';
    } else return $E($kinds[0], $kinds[1]);
    try {
        $name = bin2hex(random_bytes(16)) . '.' . $ext . '.enc';
        $dir = owIdDir();
        if (@file_put_contents($dir . $name, owIdEncrypt($bytes), LOCK_EX) === false) return $E('The file could not be saved. Please try again.', 'تعذّر حفظ الملف. حاول مرة أخرى.');
        @chmod($dir . $name, 0600);
        return ['ok' => true, 'name' => $name];
    } catch (Throwable $e) { error_log('owIdStoreFile: ' . $e->getMessage()); return $E('The file could not be saved. Please try again.', 'تعذّر حفظ الملف. حاول مرة أخرى.'); }
}
function owIdFilePath(string $name): string { return preg_match('/^[a-f0-9]{32}\.(jpg|pdf)\.enc$/', $name) ? owIdDir() . $name : ''; }
/* [bytes, mime] or null */
function owIdReadFile(string $name): ?array {
    $p = owIdFilePath($name);
    if ($p === '' || !is_file($p)) return null;
    $b = owIdDecrypt((string) file_get_contents($p));
    return $b === null ? null : [$b, str_contains($name, '.pdf.') ? 'application/pdf' : 'image/jpeg'];
}
function owIdDeleteFile(string $name): void { $p = owIdFilePath($name); if ($p !== '' && is_file($p)) @unlink($p); }

/* ---------------- the sign-up form ---------------- */
function owIdDigits(string $s): string { return strtr(trim($s), ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9', '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9']); }
/* An artist's identity fields: [values, error code ('' = fine), the bad box]. Files are checked here and saved by owIdSaveArtist */
function owIdArtistFromPost(array $post, array $files): array {
    $v = ['type' => (string) ($post['id_type'] ?? ''), 'number' => strtoupper(preg_replace('/[\s-]/', '', owIdDigits((string) ($post['id_number'] ?? '')))),
          'expiry' => trim((string) ($post['id_expiry'] ?? '')), 'link' => mb_substr(trim((string) ($post['review_link'] ?? '')), 0, 255), 'consent' => isset($post['id_consent'])];
    if (!isset(OW_ID_TYPES[$v['type']])) return [$v, 'id_type', 'id_type'];
    $okNo = match ($v['type']) { 'national_id' => (bool) preg_match('/^1\d{9}$/', $v['number']), 'iqama' => (bool) preg_match('/^2\d{9}$/', $v['number']), default => (bool) preg_match('/^[A-Z0-9]{6,12}$/', $v['number']) };
    if (!$okNo) return [$v, 'id_number', 'id_number'];
    $d = DateTime::createFromFormat('!Y-m-d', $v['expiry']);
    if (!$d || $d->format('Y-m-d') !== $v['expiry']) return [$v, 'id_expiry', 'id_expiry'];
    if ($v['expiry'] <= date('Y-m-d')) return [$v, 'id_expired', 'id_expiry'];
    $f = $files['id_photo'] ?? [];
    if (empty($f['tmp_name']) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [$v, 'id_photo', 'id_photo'];
    if ((int) ($f['size'] ?? 0) > OW_ID_FILE_MAX) return [$v, 'id_photo', 'id_photo'];
    if (!in_array((new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']), ['image/jpeg', 'image/png', 'image/webp'], true)) return [$v, 'id_photo', 'id_photo'];
    if ($v['link'] !== '' && !owIdLinkOk($v['link'])) return [$v, 'review_link', 'review_link'];
    if (!$v['consent']) return [$v, 'id_consent', 'id_consent'];
    return [$v, '', ''];
}
function owIdLinkOk(string $l): bool {
    if (preg_match('/^@?[A-Za-z0-9._]{2,30}$/', $l)) return true;   // an Instagram name
    return (bool) filter_var($l, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $l);
}
function owIdLinkClean(string $l): string {
    if ($l === '') return '';
    if (preg_match('/^@?([A-Za-z0-9._]{2,30})$/', $l, $m)) return 'https://www.instagram.com/' . $m[1];
    return $l;
}
/* Save an artist's check (after the account row exists). Returns '' or an error code */
function owIdSaveArtist(int $userId, string $name, string $email, array $v, array $file): string {
    if (!owIdReady()) return '';
    $r = owIdStoreFile($file, false);
    if (!$r['ok']) return 'id_photo';
    try {
        getDB()->prepare('INSERT INTO identity_checks (user_id, role, person_name, person_email, id_type, id_number_enc, id_last4, id_expiry, id_file, review_link, consent_at)
                          VALUES (:u, \'artist\', :n, :e, :t, :num, :l4, :x, :f, :lk, NOW())')
               ->execute([':u' => $userId, ':n' => mb_substr($name, 0, 150), ':e' => mb_substr($email, 0, 190), ':t' => $v['type'], ':num' => base64_encode(owIdEncrypt($v['number'])),
                          ':l4' => substr($v['number'], -4), ':x' => $v['expiry'], ':f' => $r['name'], ':lk' => owIdLinkClean($v['link'])]);
        owLog('identity_saved', 'user', $userId, 'artist ' . $v['type'], ['type' => 'artist', 'id' => $userId]);
        return '';
    } catch (Throwable $e) { owIdDeleteFile($r['name']); error_log('owIdSaveArtist: ' . $e->getMessage()); return 'id_save'; }
}
/* Save an institute's check: the National Address certificate, 2 to 5 photos, the official link */
function owIdSaveInstitute(int $userId, string $name, string $email, string $addressName, array $photoNames, string $link): void {
    if (!owIdReady()) return;
    try {
        $db = getDB();
        $db->prepare('INSERT INTO identity_checks (user_id, role, person_name, person_email, address_file, review_link, consent_at) VALUES (:u, \'institute\', :n, :e, :a, :lk, NOW())')
           ->execute([':u' => $userId, ':n' => mb_substr($name, 0, 150), ':e' => mb_substr($email, 0, 190), ':a' => $addressName, ':lk' => mb_substr(owIdLinkClean($link), 0, 255)]);
        $cid = (int) $db->lastInsertId();
        $ins = $db->prepare('INSERT INTO identity_photos (check_id, file_name) VALUES (:c, :f)');
        foreach ($photoNames as $p) $ins->execute([':c' => $cid, ':f' => $p]);
        owLog('identity_saved', 'user', $userId, 'institute', ['type' => 'institute', 'id' => $userId]);
    } catch (Throwable $e) { error_log('owIdSaveInstitute: ' . $e->getMessage()); }
}
/* A list of uploaded files from one multiple <input> */
function owIdFileList(array $files, string $key): array {
    $out = [];
    foreach ((array) ($files[$key]['tmp_name'] ?? []) as $i => $tmp) {
        if (($files[$key]['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        $out[] = ['name' => $files[$key]['name'][$i] ?? '', 'type' => $files[$key]['type'][$i] ?? '', 'tmp_name' => $tmp, 'error' => $files[$key]['error'][$i] ?? 0, 'size' => $files[$key]['size'][$i] ?? 0];
    }
    return $out;
}

/* ---------------- the owner's view and decision ---------------- */
function owIdForUser(int $userId): ?array {
    if (!owIdReady()) return null;
    $st = getDB()->prepare('SELECT * FROM identity_checks WHERE user_id = :u'); $st->execute([':u' => $userId]);
    $r = $st->fetch(); if (!$r) return null;
    $p = getDB()->prepare('SELECT id, file_name FROM identity_photos WHERE check_id = :c ORDER BY id'); $p->execute([':c' => (int) $r['id']]);
    $r['photos'] = $p->fetchAll();
    return $r;
}
/* approved / refused, with the reason. A refused sign-up counts as closed: the keeping clock starts */
function owIdDecide(int $userId, string $status, string $reason = ''): void {
    if (!owIdReady() || !in_array($status, ['approved', 'refused'], true)) return;
    try {
        getDB()->prepare('UPDATE identity_checks SET status = :s, reason = :r, decided_at = NOW(), closed_at = ' . ($status === 'refused' ? 'COALESCE(closed_at, NOW())' : 'NULL') . ' WHERE user_id = :u AND purged_at IS NULL')
               ->execute([':s' => $status, ':r' => mb_substr($reason, 0, 500), ':u' => $userId]);
    } catch (Throwable $e) { error_log('owIdDecide: ' . $e->getMessage()); }
}
/* The account was deleted: keep the record, start the clock (the name and email stay on the record) */
function owIdAccountClosed(int $userId): void {
    if (!owIdReady()) return;
    try { getDB()->prepare('UPDATE identity_checks SET closed_at = COALESCE(closed_at, NOW()), user_id = NULL WHERE user_id = :u')->execute([':u' => $userId]); }
    catch (Throwable $e) { error_log('owIdAccountClosed: ' . $e->getMessage()); }
}
/* Once a day: records whose account vanished are closed now; records closed more than the keeping years ago lose their files and number */
function owIdPurgeDue(): int {
    if (!owIdReady()) return 0;
    $db = getDB(); $n = 0;
    try {
        $db->exec('UPDATE identity_checks c LEFT JOIN users u ON u.id = c.user_id SET c.closed_at = NOW(), c.user_id = NULL WHERE c.user_id IS NOT NULL AND u.id IS NULL AND c.closed_at IS NULL');
        $st = $db->prepare('SELECT * FROM identity_checks WHERE purged_at IS NULL AND closed_at IS NOT NULL AND closed_at < NOW() - INTERVAL ' . owIdKeepYears() . ' YEAR LIMIT 200');
        $st->execute();
        foreach ($st->fetchAll() as $r) {
            owIdDeleteFile((string) $r['id_file']); owIdDeleteFile((string) $r['address_file']);
            $p = $db->prepare('SELECT file_name FROM identity_photos WHERE check_id = :c'); $p->execute([':c' => (int) $r['id']]);
            foreach ($p->fetchAll(PDO::FETCH_COLUMN) as $f) owIdDeleteFile((string) $f);
            $db->prepare('DELETE FROM identity_photos WHERE check_id = :c')->execute([':c' => (int) $r['id']]);
            $db->prepare("UPDATE identity_checks SET id_number_enc = NULL, id_file = '', address_file = '', review_link = '', purged_at = NOW() WHERE id = :id")->execute([':id' => (int) $r['id']]);
            owLog('identity_purged', 'identity_check', (int) $r['id'], 'kept ' . owIdKeepYears() . ' years after closing', ['type' => 'system', 'id' => null]);
            $n++;
        }
    } catch (Throwable $e) { error_log('owIdPurgeDue: ' . $e->getMessage()); }
    return $n;
}
function owIdUrl(int $checkId, string $what): string { return 'id_doc.php?c=' . $checkId . '&f=' . $what; }
/* The block the owner (and allowed sub-admins) see under each waiting artist or institute */
function owIdHtml(?array $c): string {
    if (!$c) return '<p class="o-sm id-none">' . owBi('No identity check (joined before 27 November 2026)', 'لا يوجد تحقق هوية (انضم قبل 27 نوفمبر 2026)') . '</p>';
    $allowed = owIdAllowed();
    static $css = false;
    $o = $css ? '' : '<style>.id-docs{display:grid;grid-template-columns:max-content 1fr;gap:4px 12px;margin:8px 0 0;font-size:14px;line-height:1.6}.id-docs dt{font-weight:700;color:var(--color-dark)}.id-docs dd{margin:0;overflow-wrap:anywhere}.id-docs a{font-weight:700}@media(max-width:700px){.id-docs{grid-template-columns:1fr}.id-docs dd{margin-bottom:6px}}</style>';
    $css = true;
    $o .= '<dl class="id-docs">';
    if ($c['role'] === 'artist') {
        $t = OW_ID_TYPES[$c['id_type']] ?? ['', ''];
        [$xEn, $xAr] = $c['id_expiry'] ? [date('j M Y', strtotime($c['id_expiry'])), $c['id_expiry']] : ['—', '—'];
        $o .= '<dt>' . owBi('Identity document', 'وثيقة الهوية') . '</dt><dd>' . owBi($t[0], $t[1]) . ' · <bdi dir="ltr">•••• ' . e((string) $c['id_last4']) . '</bdi> · ' . owBi('expires ' . $xEn, 'تنتهي ' . $xAr)
            . ($allowed && $c['id_file'] !== '' ? ' · <a href="' . e(owIdUrl((int) $c['id'], 'id')) . '" target="_blank" rel="noopener" data-no-prefetch>' . owBi('Open the document', 'افتح الوثيقة') . '</a> · <a href="' . e(owIdUrl((int) $c['id'], 'num')) . '" target="_blank" rel="noopener" data-no-prefetch>' . owBi('Show the full number', 'اعرض الرقم كاملا') . '</a>' : '') . '</dd>';
    } else {
        $o .= '<dt>' . owBi('National Address', 'العنوان الوطني') . '</dt><dd>' . ($c['address_file'] !== '' ? ($allowed ? '<a href="' . e(owIdUrl((int) $c['id'], 'address')) . '" target="_blank" rel="noopener" data-no-prefetch>' . owBi('Open the certificate', 'افتح الشهادة') . '</a>' : owBi('Uploaded', 'مرفوعة')) : owBi('Not given', 'لم تُرفع')) . '</dd>';
        $o .= '<dt>' . owBi('Premises or students’ work', 'المقر أو أعمال الطلاب') . '</dt><dd>' . owBi(count($c['photos']) . ' photos', count($c['photos']) . ' صور');
        if ($allowed) foreach ($c['photos'] as $i => $p) $o .= ' · <a href="' . e(owIdUrl((int) $c['id'], 'p' . (int) $p['id'])) . '" target="_blank" rel="noopener" data-no-prefetch>' . owBi('Photo ' . ($i + 1), 'صورة ' . ($i + 1)) . '</a>';
        $o .= '</dd>';
    }
    if ($c['review_link'] !== '' && $c['role'] === 'artist') $o .= '<dt>' . owBi('Link for review', 'رابط للمراجعة') . '</dt><dd><a href="' . e($c['review_link']) . '" target="_blank" rel="noopener noreferrer nofollow">' . e($c['review_link']) . '</a></dd>';
    $o .= '<dt>' . owBi('Consent', 'الموافقة') . '</dt><dd>' . ($c['consent_at'] ? owBi('Ticked ' . date('j M Y, H:i', strtotime($c['consent_at'])), 'وافق في ' . $c['consent_at']) : '—') . '</dd>';
    if (!$allowed) $o .= '<dt></dt><dd class="o-sm">' . owBi('Only the owner, and the sub-admins the owner allows, can open identity documents', 'لا يفتح وثائق الهوية إلا المالك ومن يسمح له من المشرفين') . '</dd>';
    return $o . '</dl>';
}
