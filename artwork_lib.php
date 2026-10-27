<?php
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

/* Art types offered in the guided picker. Keys must exist in art_types.php.
   'svg' is a small drawn example of how the medium looks. */
function owUploadTypes(): array {
    return [
        'watercolor'        => '<circle cx="22" cy="24" r="14" fill="#2A52BE" opacity=".35"/><circle cx="38" cy="30" r="13" fill="#E30022" opacity=".3"/><circle cx="30" cy="38" r="12" fill="#CB9D06" opacity=".35"/>',
        'oil'               => '<path d="M8 40 C16 14 30 46 40 20 S54 30 56 16" stroke="#8A3324" stroke-width="9" fill="none" stroke-linecap="round"/><path d="M8 48 C22 38 34 54 56 40" stroke="#CB9D06" stroke-width="7" fill="none" stroke-linecap="round"/>',
        'acrylic'           => '<rect x="8" y="10" width="22" height="22" rx="3" fill="#0047AB"/><rect x="34" y="14" width="22" height="18" rx="3" fill="#FFE500"/><rect x="14" y="36" width="36" height="16" rx="3" fill="#E30022"/>',
        'charcoal'          => '<path d="M8 50 L20 14 L30 44 L40 18 L54 50" stroke="#1B1B1B" stroke-width="5" fill="none" stroke-linejoin="round" opacity=".8"/><path d="M8 54 H56" stroke="#1B1B1B" stroke-width="10" opacity=".15"/>',
        'ink'               => '<path d="M10 46 C20 20 44 20 54 46" stroke="currentColor" stroke-width="2.5" fill="none"/><path d="M14 52 L50 12 M20 54 L54 18 M26 56 L56 26" stroke="currentColor" stroke-width="1.5" opacity=".6"/>',
        'pastel'            => '<rect x="8" y="12" width="48" height="12" rx="6" fill="#FADA5E" opacity=".8"/><rect x="8" y="28" width="48" height="12" rx="6" fill="#F6A6B8" opacity=".8"/><rect x="8" y="44" width="48" height="10" rx="5" fill="#9FC5E8" opacity=".8"/>',
        'mixed_media'       => '<rect x="8" y="10" width="26" height="30" fill="#D9C7A7" transform="rotate(-6 21 25)"/><circle cx="40" cy="36" r="14" fill="#0047AB" opacity=".6"/><path d="M10 52 L54 46" stroke="#E30022" stroke-width="4"/>',
        'digital_art'       => '<rect x="8" y="10" width="48" height="34" rx="4" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M14 38 L26 24 L34 32 L42 20 L50 38 Z" fill="currentColor" opacity=".7"/><path d="M26 54 H38 M32 44 V54" stroke="currentColor" stroke-width="2.5"/>',
        'calligraphy'       => '<path d="M50 18 C40 18 40 30 30 30 C20 30 22 44 12 44" stroke="currentColor" stroke-width="5" fill="none" stroke-linecap="round"/><circle cx="46" cy="44" r="3" fill="currentColor"/><circle cx="20" cy="18" r="3" fill="currentColor"/>',
        'islamic_geometric' => '<path d="M32 6 L38 22 L54 18 L44 32 L54 46 L38 42 L32 58 L26 42 L10 46 L20 32 L10 18 L26 22 Z" fill="none" stroke="#0047AB" stroke-width="2.5"/><circle cx="32" cy="32" r="8" fill="none" stroke="#CB9D06" stroke-width="2.5"/>',
        'gouache'           => '<rect x="8" y="10" width="48" height="16" fill="#E30022" opacity=".85"/><rect x="8" y="26" width="48" height="14" fill="#FADA5E"/><rect x="8" y="40" width="48" height="14" fill="#40826D" opacity=".9"/>',
        'printmaking'       => '<rect x="10" y="10" width="44" height="44" fill="none" stroke="currentColor" stroke-width="2"/><path d="M16 46 L28 22 L36 36 L42 28 L48 46 Z" fill="currentColor"/><circle cx="44" cy="18" r="4" fill="#E30022"/>',
        'other'             => '<circle cx="20" cy="32" r="4" fill="currentColor"/><circle cx="32" cy="32" r="4" fill="currentColor"/><circle cx="44" cy="32" r="4" fill="currentColor"/>',
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
   Arabic: the Arabic name if filled in, else the English one. */
function owArtistNameSql(string $u = 'u'): string {
    $en = "COALESCE(NULLIF(TRIM($u.artist_name), ''), NULLIF(TRIM($u.full_name_en), ''), NULLIF(TRIM($u.full_name_ar), ''), '')";
    $ar = "COALESCE(NULLIF(TRIM($u.full_name_ar), ''), $en)";
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
    return owStoreImage($file, OW_STAGE_DIR);
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
        $img = '<span class="stage-img"><img loading="lazy" src="' . e($row['photo_path']) . '" alt="' . e($lEn . ' (' . $stg . '%) · ' . $lAr) . '"></span>';
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
            : '<a class="stage" href="' . e($row['photo_path']) . '" target="_blank" rel="noopener" aria-label="' . e($stg . '% — ' . $lEn . ' · ' . $lAr . ' (opens full size)') . '">' . $img . $cap . '</a>';
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
