<?php
/* =====================================================================
 *  article_lib.php — shared helpers for the articles system
 *  (admin_articles.php, article.php, articles_list.php) and the
 *  institutes directory (training.php).
 *  Tables: articles, article_images, photo_consents, institutes, courses
 *  (all created by database_update.sql).
 * ===================================================================== */
require_once __DIR__ . '/artwork_lib.php';

const OW_ARTICLE_DIR = 'uploads/articles/';
const OW_CONSENT_DIR = 'uploads/consents/';   // private: blocked from the public (see .htaccess)
const OW_ARTICLE_MAX_IMAGES = 12;

/* The three article types: [english name, arabic name] */
function owArticleTypes(): array {
    return [
        'event'            => ['Art Events', 'الملتقيات الفنية'],
        'artist_spotlight' => ['Artist Spotlights', 'أضواء على الفنانين'],
        'art_education'    => ['Art Education', 'التثقيف الفني'],
    ];
}

/* How the artist gave photo consent: [english, arabic] */
function owConsentMethods(): array {
    return [
        'email'       => ['Email', 'البريد الإلكتروني'],
        'whatsapp'    => ['WhatsApp', 'واتساب'],
        'signed_form' => ['Signed Form', 'نموذج موقّع'],
        'in_person'   => ['In Person', 'شخصياً'],
        'other'       => ['Other', 'أخرى'],
    ];
}

/* True once database_update.sql has created the article tables. */
function owArticlesReady(): bool {
    static $ok = null;
    if ($ok !== null) return $ok;
    try {
        getDB()->query('SELECT 1 FROM articles LIMIT 1');
        getDB()->query('SELECT 1 FROM article_images LIMIT 1');
        getDB()->query('SELECT 1 FROM photo_consents LIMIT 1');
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
    }
    return $ok;
}

/* Who may manage articles: sub-admins and admins (logged in with that role),
   or an admin signed in through the admin.php password gate.
   Returns ['user_id' => int|null] or null for everyone else. */
function owArticleStaff(): ?array {
    if (function_exists('isLoggedIn') && isLoggedIn()) {
        $u = currentUser();
        if ($u && in_array($u['role'] ?? '', ['admin', 'sub_admin'], true) && empty($u['is_banned'])) {
            return ['user_id' => (int) $u['id']];
        }
    }
    $hash = defined('ADMIN_HASH') ? (string) ADMIN_HASH : '';
    if ($hash !== '' && !empty($_SESSION['is_admin'])
        && hash_equals(hash('sha256', $hash), (string) ($_SESSION['admin_sig'] ?? ''))) {
        return ['user_id' => null];
    }
    return null;
}

/* Clean web address from the English title: lowercase, spaces to hyphens. */
function owSlugify(string $title): string {
    $s = trim($title);
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t !== false) $s = $t;
    }
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9\s-]+/', '', $s);
    $s = preg_replace('/[\s_-]+/', '-', $s);
    $s = trim(substr(trim($s, '-'), 0, 120), '-');
    return $s !== '' ? $s : 'article';
}

/* Same as owSlugify, plus -2, -3 … when the address is already taken. */
function owUniqueSlug(string $title, int $exceptId = 0): string {
    $base = owSlugify($title);
    $slug = $base;
    $st = getDB()->prepare('SELECT COUNT(*) FROM articles WHERE slug = :s AND id <> :id');
    for ($n = 2; ; $n++) {
        $st->execute([':s' => $slug, ':id' => $exceptId]);
        if ((int) $st->fetchColumn() === 0) return $slug;
        $slug = $base . '-' . $n;
    }
}

/* SQL condition: what visitors may see. Only published articles — and an
   artist spotlight that has photos also needs a consent record that was
   not withdrawn (business rule: no local artist photo without consent). */
function owArticlePublicSql(string $a = 'a'): string {
    return "$a.status = 'published' AND ($a.type <> 'artist_spotlight'
        OR ($a.cover_image_path = '' AND NOT EXISTS (SELECT 1 FROM article_images ai WHERE ai.article_id = $a.id))
        OR EXISTS (SELECT 1 FROM photo_consents pc WHERE pc.article_id = $a.id AND pc.withdrawn_at IS NULL))";
}

/* Only show photos we saved ourselves. */
function owSafeArticlePath(string $p): bool {
    return (bool) preg_match('#^uploads/articles/[a-f0-9]{32}\.(jpg|png|webp)$#', $p);
}

function owDeleteArticleFile(string $p): void {
    if (owSafeArticlePath($p) && is_file(__DIR__ . '/' . $p)) { @unlink(__DIR__ . '/' . $p); }
}

/* Body text → safe paragraphs (no HTML from the editor is ever printed). */
function owBodyHtml(string $text, string $lang): string {
    $out = '';
    foreach (preg_split('/\R{2,}/u', trim($text)) as $para) {
        if (trim($para) === '') continue;
        $out .= '<p>' . nl2br(e(trim($para)), false) . '</p>';
    }
    return '<div class="article-body bi-' . $lang . '" lang="' . $lang . '"' . ($lang === 'ar' ? ' dir="rtl"' : '') . '>' . $out . '</div>';
}

/* Short plain-text summary, e.g. for the page description. */
function owExcerpt(string $text, int $len = 155): string {
    $t = trim(preg_replace('/\s+/u', ' ', $text));
    return mb_strlen($t) > $len ? rtrim(mb_substr($t, 0, $len - 1)) . '…' : $t;
}

/* "12 Nov 2026" or "12 Nov – 20 Dec 2026" style range, in both languages. */
function owDateRangeBi(?string $start, ?string $end): array {
    if (!$start) return ['Dates to be announced', 'المواعيد تُعلن لاحقاً'];
    [$sEn, $sAr] = owDateBi($start);
    if (!$end || $end === $start) return [$sEn, $sAr];
    [$eEn, $eAr] = owDateBi($end);
    return [$sEn . ' – ' . $eEn, $sAr . ' – ' . $eAr];
}

/* A date typed into a form (YYYY-MM-DD), or null if it is not a real date. */
function owFormDate(string $v): ?string {
    $d = DateTime::createFromFormat('!Y-m-d', $v);
    return ($d && $d->format('Y-m-d') === $v) ? $v : null;
}

/* ---------------------------------------------------------------------
 *  Consent proof (screenshot or signed form). Kept in a private folder:
 *  only staff can open it, through admin_articles.php?proof=ID.
 *  Photos are re-saved (hidden data removed); PDFs are kept as they are.
 * ------------------------------------------------------------------- */
function owStoreConsentProof(array $file): array {
    owProtectConsentDir();
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name'] ?? '')
        && strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION)) === 'pdf') {
        if ((int) $file['size'] <= 0 || (int) $file['size'] > OW_STAGE_MAX_SIZE) return ['ok' => false, 'err' => 'size'];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $head = (string) file_get_contents($file['tmp_name'], false, null, 0, 5);
        if ($mime !== 'application/pdf' || $head !== '%PDF-') return ['ok' => false, 'err' => 'type'];
        $name = bin2hex(random_bytes(16)) . '.pdf';
        if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/' . OW_CONSENT_DIR . $name)) return ['ok' => false, 'err' => 'save'];
        return ['ok' => true, 'path' => OW_CONSENT_DIR . $name];
    }
    return owStoreImage($file, OW_CONSENT_DIR);
}

/* Second lock on the private folder, in case the main .htaccess is changed. */
function owProtectConsentDir(): void {
    $dir = __DIR__ . '/' . OW_CONSENT_DIR;
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    if (is_dir($dir) && !is_file($dir . '.htaccess')) {
        @file_put_contents($dir . '.htaccess', "# Private consent records - staff open them through admin_articles.php\n<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n");
    }
}

function owSafeConsentPath(string $p): bool {
    return (bool) preg_match('#^uploads/consents/[a-f0-9]{32}\.(jpg|png|webp|pdf)$#', $p);
}

/* Bilingual message for an upload problem code from owStoreImage. */
function owUploadErrBi(string $code, string $whatEn, string $whatAr): array {
    switch ($code) {
        case 'size': return [$whatEn . ': the file is too large (10MB maximum).', $whatAr . ': الملف كبير جداً (الحد الأقصى 10 ميغابايت).'];
        case 'type': return [$whatEn . ': please choose a JPG, PNG or WEBP photo.', $whatAr . ': يرجى اختيار صورة بصيغة JPG أو PNG أو WEBP.'];
        default:     return [$whatEn . ': the file could not be saved. Please try again.', $whatAr . ': تعذّر حفظ الملف. يرجى المحاولة مرة أخرى.'];
    }
}

/* Institute logos: our own uploads folder or a secure web address only. */
function owSafeLogo(string $p): bool {
    return (bool) preg_match('#^(uploads/[A-Za-z0-9/_-]+\.(png|jpe?g|webp|svg)|https://[^\s"\'<>]+)$#i', $p) && strpos($p, '..') === false;
}

/* "3 active courses" with correct Arabic counting words. */
function owCourseCountBi(int $n): array {
    $en = $n === 1 ? '1 active course' : $n . ' active courses';
    if ($n === 0)      $ar = 'لا توجد دورات متاحة';
    elseif ($n === 1)  $ar = 'دورة واحدة متاحة';
    elseif ($n === 2)  $ar = 'دورتان متاحتان';
    elseif ($n <= 10)  $ar = $n . ' دورات متاحة';
    else               $ar = $n . ' دورة متاحة';
    return [$en, $ar];
}
