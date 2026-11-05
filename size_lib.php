<?php
/* =====================================================================
 *  size_lib.php — the standard artwork sizes (5 Nov 2026)
 *
 *  One fixed list instead of free width and height, used by the upload and
 *  edit forms (cert_fields.php), private commissions, custom gifts and the
 *  Gallery filter. Each artwork keeps its size code (e.g. "50x70"), its size
 *  group (xs / s / m / l / xl) and its orientation (portrait / landscape /
 *  square), so Bashro Sales and pricing can use them later.
 *
 *    OW_SIZES                    group => [sizes "AxB" with A <= B]
 *    owSizeGroups()              group => [EN, AR] names
 *    owSizeOrientations()        portrait / landscape / square => [EN, AR]
 *    owSizeCheck($code, $orient) [code, group, orientation, width, height] or null
 *    owSizeNearest($w, $h)       the closest standard size to any width × height
 *    owSizeLabel($code, $orient) ["50 × 70 cm · Medium · Portrait", Arabic]
 *    owSizeSelect(...)           the size list + orientation for a form
 *    owSizeFromPost($p, $need)   [values or null, error] from a form
 *  Database: size_code, size_category, orientation in artworks, commissions
 *  and gift_requests (sizes_update.sql, only adds).
 * ===================================================================== */

if (!defined('OW_SIZES')) define('OW_SIZES', [
    'xs' => ['20x20', '20x30', '25x25'],
    's'  => ['30x30', '30x40', '40x40'],
    'm'  => ['40x50', '50x50', '50x70', '60x60'],
    'l'  => ['60x90', '70x100', '80x80', '80x120'],
    'xl' => ['100x100', '100x150', '120x180'],
]);

if (!function_exists('owSizeGroups')) {

function owSizeGroups(): array {
    return ['xs' => ['Extra small', 'صغير جداً'], 's' => ['Small', 'صغير'], 'm' => ['Medium', 'متوسط'],
            'l' => ['Large', 'كبير'], 'xl' => ['Extra large', 'كبير جداً']];
}

function owSizeOrientations(): array {
    return ['portrait' => ['Portrait (tall)', 'طولي'], 'landscape' => ['Landscape (wide)', 'عرضي'], 'square' => ['Square', 'مربع']];
}

/* "50x70" → [50, 70] */
function owSizeParts(string $code): ?array {
    return preg_match('/^(\d{2,3})x(\d{2,3})$/', $code, $m) ? [(int) $m[1], (int) $m[2]] : null;
}

function owSizeGroupOf(string $code): ?string {
    foreach (OW_SIZES as $g => $list) if (in_array($code, $list, true)) return $g;
    return null;
}

/* A checked size: [code, group, orientation, width cm, height cm] or null.
   Square sizes are always "square"; the others need portrait or landscape. */
function owSizeCheck(string $code, string $orient): ?array {
    $g = owSizeGroupOf($code);
    if ($g === null) return null;
    [$a, $b] = owSizeParts($code);
    if ($a === $b) return [$code, $g, 'square', $a, $b];
    if ($orient === 'portrait')  return [$code, $g, 'portrait', $a, $b];
    if ($orient === 'landscape') return [$code, $g, 'landscape', $b, $a];
    return null;
}

/* The closest standard size to a free width × height (used once to map older artworks) */
function owSizeNearest(float $w, float $h): ?array {
    if ($w <= 0 || $h <= 0) return null;
    $lo = min($w, $h); $hi = max($w, $h);
    $best = null; $bestD = INF;
    foreach (OW_SIZES as $list) foreach ($list as $code) {
        [$a, $b] = owSizeParts($code);
        /* closest in centimetres; on a tie, the closest shape (ratio of the sides) */
        $d = abs($lo - $a) + abs($hi - $b) + abs($lo / $hi - $a / $b) / 100;
        if ($d < $bestD - 0.000001) { $bestD = $d; $best = $code; }
    }
    [$a, $b] = owSizeParts($best);
    $orient = $a === $b ? 'square' : ($w > $h ? 'landscape' : ($w < $h ? 'portrait' : 'portrait'));
    return owSizeCheck($best, $orient);
}

function owSizeLabel(?string $code, ?string $orient = null): array {
    $c = owSizeCheck((string) $code, (string) $orient) ?? (owSizeParts((string) $code) ? owSizeCheck((string) $code, 'portrait') : null);
    if (!$c) return ['', ''];
    [$code, $g, $o, $w, $h] = $c;
    $G = owSizeGroups()[$g]; $O = owSizeOrientations()[$o];
    return [$w . ' × ' . $h . ' cm · ' . $G[0] . ' · ' . preg_replace('/ \(.*\)$/', '', $O[0]),
            $w . ' × ' . $h . ' سم · ' . $G[1] . ' · ' . $O[1]];
}

/* Reads size_code + orientation from a form. $need = the size is required. */
function owSizeFromPost(array $p, bool $need): array {
    $code = trim((string) ($p['size_code'] ?? ''));
    if ($code === '') return [null, $need ? 'size' : ''];
    $c = owSizeCheck($code, (string) ($p['orientation'] ?? ''));
    if (!$c) return [null, owSizeGroupOf($code) === null ? 'size' : 'orientation'];
    return [['size_code' => $c[0], 'size_category' => $c[1], 'orientation' => $c[2], 'width_cm' => $c[3], 'height_cm' => $c[4]], ''];
}

function owSizeErrors(): array {
    return ['size' => ['Please choose a size from the list.', 'يرجى اختيار مقاس من القائمة.'],
            'orientation' => ['Please choose portrait or landscape.', 'يرجى اختيار طولي أو عرضي.']];
}

/* The form part: one list of sizes (grouped) and the orientation.
   $optional adds "No preference" at the top (commissions, gifts). */
function owSizeSelect(string $code = '', string $orient = '', bool $optional = false, string $id = 'sz'): string {
    $e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    $bi = fn($en, $ar) => '<span class="bi-en" lang="en">' . $e($en) . '</span><span class="bi-ar" lang="ar">' . $e($ar) . '</span>';
    $h  = '<div class="sz-pick" data-sz>';
    $h .= '<div class="field"><label for="' . $e($id) . '">' . $bi($optional ? 'Size (optional)' : 'Size', $optional ? 'المقاس (اختياري)' : 'المقاس') . '</label>';
    $h .= '<select id="' . $e($id) . '" name="size_code"' . ($optional ? '' : ' required') . '>';
    /* the options are in the page language (decided on the server, lang_lib.php): "70 × 100 cm" or "70 × 100 سم" */
    require_once __DIR__ . '/lang_lib.php';
    $ar = owIsAr();
    $h .= '<option value="">' . ($optional ? ($ar ? 'بلا تفضيل' : 'No preference') : ($ar ? 'اختر المقاس' : 'Choose a size')) . '</option>';
    foreach (OW_SIZES as $g => $list) {
        [$gEn, $gAr] = owSizeGroups()[$g];
        $h .= '<optgroup label="' . $e($ar ? $gAr : $gEn) . '">';
        foreach ($list as $c) {
            [$a, $b] = owSizeParts($c);
            $h .= '<option value="' . $c . '" data-sq="' . ($a === $b ? '1' : '0') . '"' . ($c === $code ? ' selected' : '') . '>' . ($ar ? '&#x200F;' . $a . ' × ' . $b . ' سم' : $a . ' × ' . $b . ' cm') . '</option>';
        }
        $h .= '</optgroup>';
    }
    $h .= '</select><p class="sz-hint">' . $bi('Sizes in centimetres. Choose the closest one.', 'المقاسات بالسنتيمتر. اختر الأقرب.') . '</p></div>';
    $h .= '<div class="field sz-orient"><span class="cf-label">' . $bi('Orientation', 'الاتجاه') . '</span><div class="cf-choice">';
    foreach (owSizeOrientations() as $o => [$en, $ar]) {
        $h .= '<label><input type="radio" name="orientation" value="' . $o . '"' . ($o === $orient ? ' checked' : '') . '> ' . $bi($en, $ar) . '</label>';
    }
    $h .= '</div></div></div>';
    /* square sizes are always square; the others are tall or wide */
    $h .= '<script>(function(){var b=document.currentScript.previousElementSibling,s=b.querySelector("select"),rs=b.querySelectorAll("input[name=orientation]");'
        . 'function f(){var o=s.options[s.selectedIndex],sq=o&&o.getAttribute("data-sq")==="1",none=!s.value;'
        . 'rs.forEach(function(r){var isSq=r.value==="square";r.disabled=none||(sq?!isSq:isSq);if(r.disabled)r.checked=false;if(sq&&isSq)r.checked=true;});'
        . 'b.querySelector(".sz-orient").hidden=none;}s.addEventListener("change",f);f();})();</script>';
    return $h;
}

}
