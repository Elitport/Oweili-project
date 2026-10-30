<?php
require_once __DIR__ . '/ow_paths.php';   // owBase(): web address of the site's folder
require_once __DIR__ . '/img_lib.php';    // small phone-friendly copies of photos
/* =====================================================================
 *  artwork_lib.php — shared helpers for the 3-stage proof-of-process upload
 *  Used by: upload_artwork.php, artist_dashboard.php, edit_artwork.php,
 *           admin.php, subadmin_dashboard.php
 *  Tables:  artworks (is_draft), artwork_stages, artwork_colors
 *           — created by database_update.sql
 * ===================================================================== */

const OW_STAGE_DIR      = 'uploads/artworks/stages/';
const OW_STAGE_MAX_SIZE = 10 * 1024 * 1024;   // 10MB per photo
const OW_STAGES         = [25, 50, 100];

/* Colors artists can pick during upload (hex is only used for the swatch). */
function owColors(): array {
    return [
        'titanium_white'  => ['en' => 'Titanium White',   'ar' => 'أبيض التيتانيوم', 'hex' => '#F4F4F0'],
        'ivory_black'     => ['en' => 'Ivory Black',      'ar' => 'أسود العاج',      'hex' => '#1B1B1B'],
        'cadmium_red'     => ['en' => 'Cadmium Red',      'ar' => 'أحمر الكادميوم',  'hex' => '#E30022'],
        'crimson'         => ['en' => 'Crimson',          'ar' => 'قرمزي',           'hex' => '#B0103A'],
        'burnt_sienna'    => ['en' => 'Burnt Sienna',     'ar' => 'سيينا محروقة',    'hex' => '#8A3324'],
        'raw_umber'       => ['en' => 'Raw Umber',        'ar' => 'أمبر خام',        'hex' => '#826644'],
        'yellow_ochre'    => ['en' => 'Yellow Ochre',     'ar' => 'أصفر أوكر',       'hex' => '#CB9D06'],
        'cadmium_yellow'  => ['en' => 'Cadmium Yellow',   'ar' => 'أصفر الكادميوم',  'hex' => '#FFE500'],
        'cobalt_blue'     => ['en' => 'Cobalt Blue',      'ar' => 'أزرق الكوبالت',   'hex' => '#0047AB'],
        'ultramarine'     => ['en' => 'Ultramarine Blue', 'ar' => 'أزرق أولترامارين', 'hex' => '#1C2A8F'],
        'viridian'        => ['en' => 'Viridian Green',   'ar' => 'أخضر فيريديان',   'hex' => '#40826D'],
        'sap_green'       => ['en' => 'Sap Green',        'ar' => 'أخضر الصبار',     'hex' => '#507D2A'],
        'burnt_umber'     => ['en' => 'Burnt Umber',      'ar' => 'أمبر محروق',      'hex' => '#6E3A1E'],
        'paynes_grey'     => ['en' => "Payne's Grey",     'ar' => 'رمادي باين',      'hex' => '#536878'],
        'naples_yellow'   => ['en' => 'Naples Yellow',    'ar' => 'أصفر نابولي',     'hex' => '#FADA5E'],
        'cerulean'        => ['en' => 'Cerulean Blue',    'ar' => 'أزرق سيرولين',    'hex' => '#2A52BE'],
    ];
}

/* The 6 groups of the guided art type picker, in the same order and words as
   Explore Art Styles. The styles in each group come from art_types.php ('cat'). */
function owStyleGroups(): array {
    return [
        'traditional'  => ['en' => 'Traditional',             'ar' => 'تقليدي'],
        'digital'      => ['en' => 'Digital',                 'ar' => 'رقمي'],
        'heritage'     => ['en' => 'Islamic & Arab Heritage', 'ar' => 'تراث إسلامي وعربي'],
        'contemporary' => ['en' => 'Contemporary',            'ar' => 'معاصر'],
        'nature'       => ['en' => 'Nature & Portrait',       'ar' => 'طبيعة وبورتريه'],
        'photography'  => ['en' => 'Photography',             'ar' => 'تصوير'],
    ];
}

/* Stage messages shown to the artist (Steps 2–4). */
function owStageText(int $stage): array {
    $t = [
        25  => ['en' => 'Photograph your artwork at its beginning — first strokes, first layer, first marks.',
                'ar' => 'صوّر عملك في بدايته — أول ضربات الريشة، أول طبقة، أول علامة.'],
        50  => ['en' => 'Your artwork is taking shape. Photograph it now at its halfway point.',
                'ar' => 'عملك يأخذ شكله. صوّره الآن في منتصف الطريق.'],
        100 => ['en' => 'Your artwork is complete. Take the final photograph — this is what buyers will see first.',
                'ar' => 'اكتمل عملك. التقط الصورة النهائية — هذا ما سيراه المشترون أولاً.'],
    ];
    return $t[$stage];
}

/* Has database_update.sql been run? (new tables + artworks.is_draft) */
function owStagesReady(): bool {
    static $ready = null;
    if ($ready !== null) return $ready;
    try {
        $db = getDB();
        $db->query('SELECT 1 FROM artwork_stages LIMIT 0');
        $db->query('SELECT 1 FROM artwork_colors LIMIT 0');
        $db->query('SELECT is_draft FROM artworks LIMIT 0');
        $ready = true;
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}

/* SQL snippet that hides unfinished drafts — empty until the column exists,
   so older pages keep working before database_update.sql is run. */
function owNotDraftSql(string $alias = ''): string {
    if (!owStagesReady()) return '';
    return ' AND ' . ($alias !== '' ? $alias . '.' : '') . 'is_draft = 0';
}

/* The artist's public name for visitors, as two columns artist_en / artist_ar.
   English: the artist name chosen at sign-up, else the full name.
   Arabic: the Arabic name if filled in, else the English one.
   Names made only of digits are skipped; with no name at all: "Oweili Artist" / «فنان أويلي». */
function owArtistNameSql(string $u = 'u'): string {
    // a name made only of digits (e.g. "82") is never shown: the next name is used
    $nm = fn(string $col): string => "CASE WHEN TRIM($u.$col) = '' OR TRIM($u.$col) REGEXP '^[0-9]+$' THEN NULL ELSE TRIM($u.$col) END";
    $en = "COALESCE(" . $nm('artist_name') . ", " . $nm('full_name_en') . ", " . $nm('full_name_ar') . ", 'Oweili Artist')";
    $ar = "COALESCE(" . $nm('full_name_ar') . ", " . $nm('artist_name') . ", " . $nm('full_name_en') . ", 'فنان أويلي')";
    return "$en AS artist_en, $ar AS artist_ar";
}

/* Stage photos for one artwork, keyed by stage (25 / 50 / 100). */
function owGetStages(int $artworkId): array {
    if (!owStagesReady()) return [];
    $st = getDB()->prepare('SELECT stage, photo_path, taken_at FROM artwork_stages WHERE artwork_id = :id ORDER BY stage');
    $st->execute([':id' => $artworkId]);
    $out = [];
    foreach ($st->fetchAll() as $r) { $out[(int) $r['stage']] = $r; }
    return $out;
}

/* Stage photos for many artworks at once: [artwork_id => [stage => row]]. */
function owGetStagesFor(array $artworkIds): array {
    $ids = array_values(array_unique(array_filter(array_map('intval', $artworkIds))));
    if (!$ids || !owStagesReady()) return [];
    $in = implode(',', array_fill(0, count($ids), '?'));
    $st = getDB()->prepare("SELECT artwork_id, stage, photo_path, taken_at FROM artwork_stages WHERE artwork_id IN ($in) ORDER BY stage");
    $st->execute($ids);
    $out = [];
    foreach ($st->fetchAll() as $r) { $out[(int) $r['artwork_id']][(int) $r['stage']] = $r; }
    return $out;
}

/* Remove a stored stage photo file (only inside the stages folder). */
function owDeleteStageFile(string $relPath): void {
    if (strpos($relPath, OW_STAGE_DIR) !== 0 || strpos($relPath, '..') !== false) return;
    $abs = __DIR__ . '/' . $relPath;
    if (is_file($abs)) @unlink($abs);
}

/* Remove every stage photo file of an artwork (rows go by ON DELETE CASCADE). */
function owDeleteAllStageFiles(int $artworkId): void {
    foreach (owGetStages($artworkId) as $row) { owDeleteStageFile((string) $row['photo_path']); }
}

/* ---------------------------------------------------------------------
 *  Validate and store one stage photo (owStoreImage does the same for any
 *  folder, e.g. article photos in uploads/articles/).
 *  Returns ['ok'=>true,'path'=>..., 'taken_at'=>...] or ['ok'=>false,'err'=>code]
 *  Error codes: none | size | type | save
 *  The photo is re-saved with GD when available: this applies the phone's
 *  rotation and strips hidden data such as GPS location (privacy / PDPL).
 * ------------------------------------------------------------------- */
function owStoreStagePhoto(array $file): array {
    $r = owStoreImage($file, OW_STAGE_DIR);
    if ($r['ok']) owMakeCardThumbs($r['path']);   // phone-sized copies ready before anyone opens the Gallery
    return $r;
}

function owStoreImage(array $file, string $relDir): array {
    if (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'err' => 'none'];
    }
    if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
        return ['ok' => false, 'err' => 'size'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'err' => 'save'];
    }
    if ((int) $file['size'] <= 0 || (int) $file['size'] > OW_STAGE_MAX_SIZE) {
        return ['ok' => false, 'err' => 'size'];
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext  = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!isset($allowed[$mime]) || !in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return ['ok' => false, 'err' => 'type'];
    }
    $info = @getimagesize($file['tmp_name']);
    if (!$info || $info[0] < 1 || $info[1] < 1) {
        return ['ok' => false, 'err' => 'type'];
    }
    $outExt = $allowed[$mime];

    // When the photo was taken: the camera's own timestamp if it looks real, else now.
    $takenAt = date('Y-m-d H:i:s');
    $orientation = 1;
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file['tmp_name']);
        if (!empty($exif['DateTimeOriginal'])) {
            $ts = strtotime(str_replace(':', '-', substr($exif['DateTimeOriginal'], 0, 10)) . substr($exif['DateTimeOriginal'], 10));
            if ($ts && $ts > strtotime('2000-01-01') && $ts <= time() + 86400) {
                $takenAt = date('Y-m-d H:i:s', $ts);
            }
        }
        $orientation = (int) ($exif['Orientation'] ?? 1);
    }

    $dir = __DIR__ . '/' . $relDir;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return ['ok' => false, 'err' => 'save'];
    }
    $name = bin2hex(random_bytes(16)) . '.' . $outExt;
    $dest = $dir . $name;

    $saved = false;
    if (function_exists('imagecreatefromstring')) {
        $img = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
        if ($img === false) {
            return ['ok' => false, 'err' => 'type'];
        }
        if ($orientation === 3)      { $img = imagerotate($img, 180, 0); }
        elseif ($orientation === 6)  { $img = imagerotate($img, -90, 0); }
        elseif ($orientation === 8)  { $img = imagerotate($img, 90, 0); }
        if ($outExt === 'jpg') {
            $saved = imagejpeg($img, $dest, 90);
        } elseif ($outExt === 'png') {
            imagesavealpha($img, true);
            $saved = imagepng($img, $dest, 6);
        } elseif (function_exists('imagewebp')) {
            $saved = imagewebp($img, $dest, 90);
        }
        imagedestroy($img);
    }
    if (!$saved) {
        $saved = move_uploaded_file($file['tmp_name'], $dest);
    }
    if (!$saved) {
        return ['ok' => false, 'err' => 'save'];
    }
    @chmod($dest, 0644);
    return ['ok' => true, 'path' => $relDir . $name, 'taken_at' => $takenAt];
}

/* =====================================================================
 *  Showing the proof of process (marketplace + admin review)
 *  Text is printed in both languages; assets/css/proof.css shows the one
 *  that matches the page direction, so it follows each page's own switch.
 * ===================================================================== */

/* Colors used, for many artworks at once: [artwork_id => [rows]]. */
function owGetColorsFor(array $artworkIds): array {
    $ids = array_values(array_unique(array_filter(array_map('intval', $artworkIds))));
    if (!$ids || !owStagesReady()) return [];
    $in = implode(',', array_fill(0, count($ids), '?'));
    $st = getDB()->prepare("SELECT artwork_id, color_name_en, color_name_ar, hex_code FROM artwork_colors WHERE artwork_id IN ($in) ORDER BY id");
    $st->execute($ids);
    $out = [];
    foreach ($st->fetchAll() as $r) { $out[(int) $r['artwork_id']][] = $r; }
    return $out;
}

/* One piece of text in both languages. */
/* A safe picture address for an artwork photo, or '' when nothing usable is saved.
   Saved values are cleaned up so they work wherever the site lives:
   - "uploads/x.jpg", "/uploads/x.jpg", "./uploads/x.jpg" or a full server path
     ".../public_html/uploads/x.jpg"            -> "/uploads/x.jpg" (from owBase())
   - "https://oweili.com/uploads/x.jpg" or http://  -> "/uploads/x.jpg"
   - a picture on another site                       -> kept, http upgraded to https
   - a bare file name "x.jpg"                        -> uploads/artworks/x.jpg when that file is there
   The browser always tries the real photo; owArtImgHtml() swaps in a drawn
   placeholder only if it truly fails to load. */
function owImgSrc(?string $u): string {
    $u = str_replace('\\', '/', trim((string) $u));
    if ($u === '' || preg_match('/["\'<>\s]/', $u) || strpos($u, '..') !== false) return '';
    if (preg_match('#^(https?:)?//([^/]+)(/.*)?$#i', $u, $m)) {
        $host = strtolower($m[2]);
        if ($host === 'oweili.com' || $host === 'www.oweili.com') {
            $path = ltrim($m[3] ?? '', '/');
            return $path === '' ? '' : owBase() . $path;
        }
        return 'https://' . $m[2] . ($m[3] ?? '');
    }
    if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $u)) return '';           // data:, javascript: and the like
    $p = strpos($u, 'uploads/');
    if ($p !== false && ($p === 0 || $u[$p - 1] === '/')) return owBase() . substr($u, $p);
    $u = ltrim(preg_replace('#^(\./)+#', '', $u), '/');
    if (strpos($u, '/') === false && is_file(__DIR__ . '/uploads/artworks/' . $u)) return owBase() . 'uploads/artworks/' . $u;
    return owBase() . $u;
}

/* The address of a smaller copy of one of our uploaded photos ($w pixels wide: 400, 800
   or 1600). When the copy already exists it is linked directly as a normal picture file
   (fast, nothing for the server to do). When it does not exist yet, img.php makes it on
   first view, and the copy is also made in the background right after the page is sent
   (owMakeQueuedThumbs). Photos on other sites and anything else are returned as is.
   $direct = false always gives the img.php address (used as a fallback). */
function owImgSized(string $src, int $w, bool $direct = true): string {
    $base = owBase();
    if ($src === '' || strpos($src, $base . 'uploads/') !== 0) return $src;
    $rel = rawurldecode(substr($src, strlen($base)));
    if (preg_match('#^uploads/(consents|thumbs)/#', $rel)) return $src;
    $viaPhp = $base . 'img.php?' . http_build_query(['f' => $rel, 'w' => $w]);
    if (!$direct) return $viaPhp;
    $real = owThumbSource($rel);
    if ($real === '') return $viaPhp;
    if (is_file($copy = owThumbFile($rel, $w, $real))) return $base . 'uploads/thumbs/' . basename($copy);
    owQueueThumb($rel, $w);
    return $viaPhp;
}

/* Copies a page needed but did not have yet: made after the page has been sent,
   so the next visitor (and the next phone) gets plain picture files. */
function owQueueThumb(string $rel, int $w): void {
    static $started = false;
    $GLOBALS['ow_thumb_queue'][$rel . '|' . $w] = [$rel, $w];
    if ($started) return;
    $started = true;
    register_shutdown_function('owMakeQueuedThumbs');
}
function owMakeQueuedThumbs(): void {
    $queue = $GLOBALS['ow_thumb_queue'] ?? [];
    if (!$queue) return;
    /* only when the server can finish sending the page first, so no visitor ever waits */
    if (function_exists('litespeed_finish_request')) litespeed_finish_request();
    elseif (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
    else return;
    ignore_user_abort(true);
    @set_time_limit(60);
    $stop = microtime(true) + 45;
    foreach ($queue as [$rel, $w]) {
        if (microtime(true) > $stop) break;
        owMakeThumb($rel, $w);
    }
}

/* The pictures to try, in order, when one fails to load: the 400 copy, the same
   copy made fresh by img.php, then the original photo. */
function owImgFallbacks(string $src, string $tried): string {
    $list = [];
    foreach ([owImgSized($src, 400), owImgSized($src, 400, false), $src] as $u) {
        if ($u !== '' && $u !== $tried && !in_array($u, $list, true)) $list[] = $u;
    }
    return implode('|', $list);
}

/* The two attributes that give any <img> of an uploaded photo its fallbacks */
function owImgFallbackAttrs(string $src, string $tried): string {
    return ' data-fb="' . e(owImgFallbacks($src, $tried)) . '" onerror="' . e(OW_IMG_ONERROR) . '"';
}

/* The drawn picture shown when no photo can be loaded at all */
function owImgPlaceholder(bool $hidden = true): string {
    return '<div class="aw-img-noimg"' . ($hidden ? ' hidden' : '') . ' aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 16l5-5 4 4 3-3 6 6"/><circle cx="16" cy="9" r="1.6"/></svg></div>';
}

/* Runs when a picture fails: try the next address; when none is left, show the drawing */
const OW_IMG_ONERROR = "var l=(this.dataset.fb||'').split('|').filter(Boolean);"
    . "if(l.length){this.removeAttribute('srcset');this.dataset.fb=l.slice(1).join('|');this.src=l[0];return;}"
    . "this.hidden=true;if(this.nextElementSibling)this.nextElementSibling.hidden=false";

/* The artwork picture for a card: a copy sized for the screen (phones cannot cope with a
   page of full-size camera photos), with a drawn placeholder that takes its place if
   the photo is missing or fails to load (no broken "?" picture).
   $eager: the first cards on screen load straight away; cards further down wait (lazy).
   If the sized copy fails, the original photo is tried once before the placeholder shows. */
function owArtImgHtml(string $src, string $alt, bool $eager = false): string {
    $ph = owImgPlaceholder($src !== '');
    if ($src === '') return $ph;
    $small  = owImgSized($src, 400);
    $medium = owImgSized($src, 800);
    $set    = $medium !== $src ? ' srcset="' . e($small) . ' 400w, ' . e($medium) . ' 800w" sizes="(max-width: 640px) 50vw, (max-width: 1024px) 33vw, 300px"' : '';
    return '<img ' . ($eager ? 'fetchpriority="high"' : 'loading="lazy"') . ' decoding="async" src="' . e($medium) . '"' . $set
         . ' data-full="' . e($src) . '" alt="' . e($alt) . '"' . owImgFallbackAttrs($src, $medium) . '>' . $ph;
}

function owBi(string $en, string $ar, string $tag = 'span'): string {
    return '<' . $tag . ' class="bi-en" lang="en">' . e($en) . '</' . $tag . '><' . $tag . ' class="bi-ar" lang="ar">' . e($ar) . '</' . $tag . '>';
}

/* A date (and optional time) in English and Arabic. */
function owDateBi(?string $when, bool $withTime = false): array {
    if (!$when || !($ts = strtotime($when))) return ['Date unknown', 'التاريخ غير معروف'];
    $arMonths = [1 => 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
    $en = date('j M Y', $ts);
    $ar = date('j', $ts) . ' ' . $arMonths[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    if ($withTime) {
        $en .= ', ' . date('g:i A', $ts);
        $ar .= '، ' . date('g:i', $ts) . ' ' . (date('A', $ts) === 'AM' ? 'ص' : 'م');
    }
    return [$en, $ar];
}

/* "3 days after stage 25%" in both languages, with correct Arabic counting words. */
function owGapBi(int $seconds, int $fromStage): array {
    $ar = function (int $n, array $w): string {   // [one, two, few (3–10), many (11+)]
        if ($n === 1) return $w[0];
        if ($n === 2) return $w[1];
        return $n . ' ' . ($n <= 10 ? $w[2] : $w[3]);
    };
    if ($seconds >= 86400) {
        $n = intdiv($seconds, 86400);
        $en = $n . ' day' . ($n === 1 ? '' : 's');
        $arT = $ar($n, ['يوم واحد', 'يومين', 'أيام', 'يوماً']);
    } elseif ($seconds >= 3600) {
        $n = intdiv($seconds, 3600);
        $en = $n . ' hour' . ($n === 1 ? '' : 's');
        $arT = $ar($n, ['ساعة واحدة', 'ساعتين', 'ساعات', 'ساعة']);
    } else {
        $n = max(1, intdiv($seconds, 60));
        $en = $n . ' minute' . ($n === 1 ? '' : 's');
        $arT = $ar($n, ['دقيقة واحدة', 'دقيقتين', 'دقائق', 'دقيقة']);
    }
    return [$en . ' after stage ' . $fromStage . '%', 'بعد ' . $arT . ' من مرحلة ' . $fromStage . '%'];
}

/* Stage labels shown to buyers. */
function owStageLabel(int $stage): array {
    return [25 => ['The Beginning', 'البداية'], 50 => ['Taking Shape', 'يأخذ شكله'], 100 => ['The Finished Piece', 'العمل المكتمل']][$stage];
}

/* Only paths this system created are shown. */
function owSafeStagePath(string $p): bool {
    return strpos($p, OW_STAGE_DIR) === 0 && strpos($p, '..') === false;
}

/* Does this artwork carry a full proof of process (25% and 100%)? */
function owHasJourney(array $stages): bool {
    return isset($stages[25], $stages[100]);
}

/* Colors + art type (shared by both views). */
function owMetaHtml(string $type, array $colors, array $artTypes): string {
    $h = '';
    if ($type !== '' && isset($artTypes[$type])) {
        $h .= '<div class="pj-meta"><span class="pj-meta-label">' . owBi('Art type', 'نوع الفن') . '</span> <span class="pj-type">'
            . owBi($artTypes[$type]['en'], $artTypes[$type]['ar']) . '</span></div>';
    }
    if ($colors) {
        $h .= '<div class="pj-meta"><span class="pj-meta-label">' . owBi('Colors used', 'الألوان المستخدمة') . '</span><span class="pj-chips">';
        foreach ($colors as $c) {
            $hex = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $c['hex_code']) ? $c['hex_code'] : '#cccccc';
            $h .= '<span class="pj-chip"><i style="background:' . $hex . '"></i>' . owBi($c['color_name_en'], $c['color_name_ar']) . '</span>';
        }
        $h .= '</span></div>';
    }
    return $h;
}

/* ---------------------------------------------------------------------
 *  The 3 stage photos as same-size squares joined by a dashed line
 *  (design system: .stages / .stage / .stage-line in oweili.css).
 *  $mode: 'strip'   — square + "25%" (cards, drafts)
 *         'journey' — + stage name and date (buyers)
 *         'proof'   — + date, time gap and warnings; photos open full size (reviewers)
 * ------------------------------------------------------------------- */
function owStagesHtml(array $stages, string $mode = 'strip'): string {
    $missingIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h3l2-3h6l2 3h3a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1z"/><circle cx="12" cy="13" r="4"/></svg>';
    $h = '<div class="stages stages-' . $mode . '">';
    $prevTs = null; $prevStage = null; $first = true;
    foreach (OW_STAGES as $stg) {
        if (!$first) $h .= '<span class="stage-line" aria-hidden="true"></span>';
        $first = false;
        [$lEn, $lAr] = owStageLabel($stg);
        $row = $stages[$stg] ?? null;
        $ok  = $row && owSafeStagePath((string) $row['photo_path']);
        if (!$ok) {
            [$mEn, $mAr] = $stg === 50 ? ['Skipped', 'تم التخطي'] : ['Not yet', 'لم تُضف بعد'];
            if ($mode === 'proof') [$mEn, $mAr] = $stg === 50 ? ['Artist skipped middle stage', 'تخطى الفنان المرحلة الوسطى'] : ['Photo missing', 'الصورة مفقودة'];
            $h .= '<div class="stage missing"><span class="stage-img">' . $missingIcon . '</span><span class="stage-label">' . $stg . '%</span>'
                . ($mode === 'strip' ? '' : '<span class="stage-note">' . owBi($mEn, $mAr) . '</span>') . '</div>';
            continue;
        }
        $sp  = owBase() . ltrim((string) $row['photo_path'], '/');
        $s4  = owImgSized($sp, 400);
        $img = '<span class="stage-img"><img loading="lazy" decoding="async" src="' . e($s4) . '" alt="' . e($lEn . ' (' . $stg . '%) · ' . $lAr) . '"' . owImgFallbackAttrs($sp, $s4) . '></span>';
        $cap = '<span class="stage-label">' . $stg . '%</span>';
        if ($mode === 'journey') {
            [$dEn, $dAr] = owDateBi($row['taken_at']);
            $cap .= '<span class="stage-note">' . owBi($lEn, $lAr) . '</span><span class="pj-date">' . owBi($dEn, $dAr) . '</span>';
        } elseif ($mode === 'proof') {
            $ts = $row['taken_at'] ? strtotime($row['taken_at']) : null;
            [$dEn, $dAr] = owDateBi($row['taken_at'], true);
            $cap .= '<span class="pj-date">' . owBi($dEn, $dAr) . '</span>';
            if ($prevTs !== null && $ts !== null) {
                $gap = $ts - $prevTs;
                if ($gap < 0) {
                    $cap .= '<span class="pj-warn">' . owBi('⚠ Dated before stage ' . $prevStage . '%', '⚠ تاريخها أقدم من مرحلة ' . $prevStage . '%') . '</span>';
                } else {
                    [$gEn, $gAr] = owGapBi($gap, $prevStage);
                    $cap .= '<span class="pj-gap">' . owBi($gEn, $gAr) . '</span>';
                    if ($gap < 3600) {
                        $cap .= '<span class="pj-warn">' . owBi('⚠ Less than an hour after the previous stage', '⚠ بعد أقل من ساعة من المرحلة السابقة') . '</span>';
                    }
                }
            }
            if ($ts !== null) { $prevTs = $ts; $prevStage = $stg; }
        }
        $h .= $mode === 'strip'
            ? '<div class="stage">' . $img . $cap . '</div>'
            : '<a class="stage" href="' . e(owBase() . ltrim((string) $row['photo_path'], '/')) . '" target="_blank" rel="noopener" aria-label="' . e($stg . '% — ' . $lEn . ' · ' . $lAr . ' (opens full size)') . '">' . $img . $cap . '</a>';
    }
    return $h . '</div>';
}

/* Buyer view: "Creation Journey — Proof of Human Art". Empty string if no journey. */
function owJourneyHtml(array $stages, array $colors, string $type, array $artTypes): string {
    if (!owHasJourney($stages)) return '';
    return '<section class="pj" aria-label="Creation Journey · رحلة الإبداع">'
       . '<h3 class="pj-title">' . owBi('Creation Journey — Proof of Human Art', 'رحلة الإبداع — دليل الفن البشري') . '</h3>'
       . owStagesHtml($stages, 'journey') . owMetaHtml($type, $colors, $artTypes) . '</section>';
}

/* Admin / sub-admin view: "Proof of Process" with dates, gaps and warnings. */
function owProofPanelHtml(array $stages, array $colors, string $type, array $artTypes): string {
    $h = '<section class="pj pj-admin"><h3 class="pj-title">' . owBi('Proof of Process', 'دليل المراحل') . '</h3>';
    if (!$stages) {
        return $h . '<p class="pj-note">' . owBi('No stage photos — this artwork was uploaded before the 3-stage system.', 'لا توجد صور مراحل — رُفع هذا العمل قبل نظام المراحل الثلاث.') . '</p></section>';
    }
    return $h . owStagesHtml($stages, 'proof') . owMetaHtml($type, $colors, $artTypes)
        . '<p class="pj-note">' . owBi('Check that the photos show real progress. The dates should make sense for this type of artwork.', 'تحقق أن الصور تظهر تقدماً حقيقياً. يجب أن تكون التواريخ منطقية لهذا النوع من الفن.') . '</p>'
        . '</section>';
}
