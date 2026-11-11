<?php
/* =====================================================================
 *  stolen_lib.php — stolen-art warnings for the admin (owner, 11 Nov 2026).
 *  Shown ONLY in admin.php → Waiting for approval → New artworks. Nothing here
 *  is public, and nothing ever blocks an upload: the admin decides.
 *
 *  1. Duplicate check: a small picture fingerprint (difference hash, 64 bits) of
 *     the artwork's main photo, of its whole picture and of two centre crops, so a
 *     resized or cropped copy still matches. Compared with the main photo of every
 *     other artwork on Oweili (drafts left out). Fingerprints are kept in
 *     .logs/img_hash.json (private) so each photo is read only once.
 *  2. Photo time check: when a stage photo (25%, 50%, 100%) is uploaded, its camera
 *     make, model and whether it carries the camera's own date are read BEFORE the
 *     photo is cleaned (owStoreImage) and kept in the activity log (action
 *     stage_photo, actor "system", so the artist's own Activity list does not show
 *     it). Warns when all the stage photos were taken within OW_STAGE_SPAN_MIN
 *     minutes, or with different cameras.
 *  Tables: artworks, artwork_stages, activity_log, users (names). No structure change.
 * ===================================================================== */
require_once __DIR__ . '/artwork_lib.php';
require_once __DIR__ . '/platform_lib.php';

const OW_DUP_MAX_DIST   = 5;    // of 64 bits: 0 = the same picture. Measured 11 Nov 2026: a resized or cropped copy = 1; 65 different paintings = 8 or more
const OW_STAGE_SPAN_MIN = 30;   // all stage photos within this many minutes = a warning

/* Called by upload_artwork.php right after a stage photo is saved */
function owLogStageCamera(int $artworkId, int $stage, array $res): void {
    $c = $res['camera'] ?? [];
    $d = json_encode(['s' => $stage, 'make' => mb_substr((string) ($c['make'] ?? ''), 0, 60), 'model' => mb_substr((string) ($c['model'] ?? ''), 0, 60),
                      'cd' => !empty($c['cam_date']) ? 1 : 0, 't' => (string) ($res['taken_at'] ?? ''), 'p' => (string) ($res['path'] ?? '')], JSON_UNESCAPED_UNICODE);
    owLog('stage_photo', 'artwork', $artworkId, (string) $d, ['type' => 'system', 'id' => null]);
}

/* The latest camera note for each stage photo still in place: [stage => [...]] */
function owStageCameras(int $artworkId, array $stages): array {
    $out = [];
    try {
        if (!owHasTable('activity_log')) return [];
        $st = getDB()->prepare("SELECT details FROM activity_log WHERE action = 'stage_photo' AND target_type = 'artwork' AND target_id = :id ORDER BY id");
        $st->execute([':id' => $artworkId]);
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $j) {
            $m = json_decode((string) $j, true);
            if (!is_array($m) || !isset($m['s'])) continue;
            $s = (int) $m['s'];
            if (isset($stages[$s]) && (string) $stages[$s]['photo_path'] === (string) ($m['p'] ?? '')) $out[$s] = $m;   // only the photo that is there now
        }
    } catch (Throwable $e) {}
    return $out;
}

/* ---------------- picture fingerprints ---------------- */
function owImgFile(string $src): string {
    $rel = ltrim(preg_replace('#^https?://[^/]+/#', '', owImgSrc($src)), '/');
    if ($rel === '' || strpos($rel, 'uploads/') !== 0 || strpos($rel, '..') !== false) return '';
    $f = __DIR__ . '/' . $rel;
    return is_file($f) ? $f : '';
}
function owDHash($img, int $x, int $y, int $w, int $h): string {
    $t = imagecreatetruecolor(9, 8);
    imagecopyresampled($t, $img, 0, 0, $x, $y, 9, 8, $w, $h);
    $bits = '';
    for ($r = 0; $r < 8; $r++) {
        $prev = null;
        for ($c = 0; $c < 9; $c++) {
            $rgb = imagecolorat($t, $c, $r);
            $g = (($rgb >> 16) & 255) * 0.299 + (($rgb >> 8) & 255) * 0.587 + ($rgb & 255) * 0.114;
            if ($prev !== null) $bits .= $g > $prev ? '1' : '0';
            $prev = $g;
        }
    }
    imagedestroy($t);
    return $bits;
}
/* [whole, centre 80%, centre 60%] fingerprints of one photo, or [] */
function owImgHashes(string $file): array {
    static $cache = null; static $dirty = false;
    $store = __DIR__ . '/.logs/img_hash.json';
    if ($cache === null) { $cache = is_file($store) ? (json_decode((string) @file_get_contents($store), true) ?: []) : []; register_shutdown_function(function () use (&$cache, &$dirty, $store) { if ($dirty) { if (!is_dir(dirname($store))) @mkdir(dirname($store), 0700, true); @file_put_contents($store, json_encode($cache), LOCK_EX); } }); }
    if ($file === '' || !function_exists('imagecreatefromstring')) return [];
    $key = md5($file . '|' . @filesize($file) . '|' . @filemtime($file));
    if (isset($cache[$key])) return $cache[$key];
    $img = @imagecreatefromstring((string) @file_get_contents($file));   // read once; the fingerprint is kept
    if (!$img) return [];
    $w = imagesx($img); $h = imagesy($img); $out = [owDHash($img, 0, 0, $w, $h)];
    foreach ([0.8, 0.6] as $k) { $cw = (int) round($w * $k); $ch = (int) round($h * $k); $out[] = owDHash($img, (int) (($w - $cw) / 2), (int) (($h - $ch) / 2), $cw, $ch); }
    imagedestroy($img);
    $cache[$key] = $out; $dirty = true;
    return $out;
}
function owHashDist(array $a, array $b): int {
    $best = 64;
    foreach ($a as $x) foreach ($b as $y) if (strlen($x) === 64 && strlen($y) === 64) $best = min($best, substr_count($x ^ $y, "\x01"));
    return $best;
}

/* Other artworks whose main photo looks like this one: [[artwork row, distance], ...] */
function owLooksLike(array $art): array {
    $mine = owImgHashes(owImgFile((string) $art['image_url']));
    if (!$mine) return [];
    $st = getDB()->prepare('SELECT a.id, a.title_en, a.title_ar, a.image_url, a.artist_id, a.is_approved, ' . owArtistNameSql('u') . '
                            FROM artworks a JOIN users u ON u.id = a.artist_id WHERE a.id <> :id AND a.image_url <> \'\'' . owNotDraftSql('a'));
    $st->execute([':id' => (int) $art['id']]);
    $hits = [];
    foreach ($st->fetchAll() as $o) {
        $h = owImgHashes(owImgFile((string) $o['image_url']));
        if ($h && ($d = owHashDist($mine, $h)) <= OW_DUP_MAX_DIST) $hits[] = [$o, $d];
    }
    usort($hits, fn($x, $y) => $x[1] <=> $y[1]);
    return array_slice($hits, 0, 5);
}

/* The warnings box for one artwork waiting for approval ('' when there is nothing to say) */
function owStolenWarningsHtml(array $art, array $stages): string {
    $rows = [];
    foreach (owLooksLike($art) as [$o, $d]) {
        $t = owImgSized(owImgSrc((string) $o['image_url']), 400); $m = owImgSized(owImgSrc((string) $art['image_url']), 400);
        $rows[] = '<div class="sw-dup"><span class="warn">⚠ ' . owBi('Looks like an artwork by ' . $o['artist_en'], 'يشبه عملاً للفنان ' . $o['artist_ar']) . ' (' . owBi($d === 0 ? 'the same picture' : 'very close', $d === 0 ? 'الصورة نفسها' : 'قريب جداً') . ')'
                . ((int) $o['artist_id'] === (int) $art['artist_id'] ? ' · ' . owBi('the same artist', 'الفنان نفسه') : '') . '</span>'
                . '<span class="sw-pics"><figure><img src="' . e($m) . '" alt="' . e((string) $art['title_en']) . '"><figcaption>' . owBi('This upload', 'هذا العمل') . '</figcaption></figure>'
                . '<figure><img src="' . e($t) . '" alt="' . e((string) $o['title_en']) . '"><figcaption>' . e((string) ($o['title_en'] ?: $o['title_ar'])) . ' · <a href="artwork.php?id=' . (int) $o['id'] . '" target="_blank" rel="noopener">#' . (int) $o['id'] . '</a>'
                . ((int) $o['is_approved'] === 1 ? '' : ' (' . owBi('not in the Gallery', 'ليس في المعرض') . ')') . '</figcaption></figure></span></div>';
    }
    /* photo times and cameras */
    $cams = owStageCameras((int) $art['id'], $stages);
    $withDate = []; $noDate = 0; $models = [];
    foreach ($stages as $s => $row) {
        $m = $cams[$s] ?? null;
        if ($m && (int) $m['cd'] === 1 && !empty($row['taken_at'])) $withDate[$s] = strtotime((string) $row['taken_at']); else $noDate++;
        if ($m && trim($m['make'] . ' ' . $m['model']) !== '') $models[trim($m['make'] . ' ' . $m['model'])] = true;
    }
    if (count($stages) >= 2 && count($withDate) === count($stages)) {
        $span = (int) round((max($withDate) - min($withDate)) / 60);
        if ($span <= OW_STAGE_SPAN_MIN) $rows[] = '<div class="warn">⚠ ' . owBi('All ' . count($stages) . ' stage photos were taken within ' . $span . ' minute' . ($span === 1 ? '' : 's') . ' of each other (camera time).', 'التُقطت صور المراحل الـ' . count($stages) . ' كلها خلال ' . $span . ' دقيقة من بعضها (وقت الكاميرا).') . '</div>';
    }
    if (count($models) > 1) $rows[] = '<div class="warn">⚠ ' . owBi('The stage photos come from different cameras: ', 'صور المراحل من كاميرات مختلفة: ') . '<span dir="ltr">' . e(implode(' · ', array_keys($models))) . '</span></div>';
    if ($stages && $cams && $noDate > 0) $rows[] = '<div class="mini">' . owBi($noDate . ' of the ' . count($stages) . ' stage photos carry no camera date (for example a screenshot, an edited photo, or a photo sent through an app).', $noDate . ' من ' . count($stages) . ' صور مراحل بلا تاريخ كاميرا (مثل لقطة شاشة أو صورة معدّلة أو مرسلة عبر تطبيق).') . '</div>';
    if (!$rows) return '';
    return '<div class="sw-box"><b>' . owBi('Checks for copied art (only you see this; nothing is blocked)', 'فحص الأعمال المنسوخة (تراه أنت فقط، ولا يُمنع شيء)') . '</b>' . implode('', $rows) . '</div>';
}
