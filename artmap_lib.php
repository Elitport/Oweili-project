<?php
/* =====================================================================
 *  artmap_lib.php — Art Map («خريطة الفن», owner, 12 Nov 2026): museums and
 *  art galleries in Saudi Arabia, next to the existing art institutes.
 *  Museums and galleries are a short, team-made list, kept as ONE saved
 *  setting (site_settings 'art_places', JSON), so the database structure
 *  does not change. Every place starts as a draft; only the admin publishes
 *  it (admin_art_map.php). Institutes stay in their own table (institutes).
 *  Each place: kind (museum / gallery), name, city, short text in both
 *  languages, the official website, the source checked, an optional Google
 *  Maps link (otherwise a Google Maps search for its name and city).
 *  Tables: site_settings (one key), institutes (read by art_map.php)
 * ===================================================================== */
require_once __DIR__ . '/gallery_lib.php';   // owSetting / owSetSetting

const OW_MAP_KINDS = [
    'museum'  => ['Museums', 'المتاحف', 'Museum', 'متحف'],
    'gallery' => ['Art Galleries', 'المعارض الفنية', 'Art gallery', 'معرض فني'],
];
/* The city filter (owner's list), then "Other cities" */
const OW_MAP_CITIES = [
    'riyadh'  => ['Riyadh', 'الرياض'],
    'jeddah'  => ['Jeddah', 'جدة'],
    'alula'   => ['AlUla', 'العلا'],
    'eastern' => ['Dammam / Khobar', 'الدمام / الخبر'],
    'abha'    => ['Abha', 'أبها'],
    'other'   => ['Other cities', 'مدن أخرى'],
];

/* Which filter city a typed city name belongs to (used for the institutes) */
function owMapCityKey(string $city): string {
    $c = mb_strtolower(trim($city));
    if ($c === '') return 'other';
    if (preg_match('/riyadh|diriyah|الرياض|الدرعية/u', $c)) return 'riyadh';
    if (preg_match('/jedd?ah|jidd?ah|جدة|جده/u', $c)) return 'jeddah';
    if (preg_match('/al\s*-?\s*ula|العلا/u', $c)) return 'alula';
    if (preg_match('/dammam|khobar|dhahran|الدمام|الخبر|الظهران/u', $c)) return 'eastern';
    if (preg_match('/abha|أبها|ابها/u', $c)) return 'abha';
    return 'other';
}

/* Every museum and gallery, drafts included (the admin's list) */
function owArtPlaces(): array {
    $j = json_decode((string) owSetting('art_places', '[]'), true);
    if (!is_array($j)) return [];
    $out = [];
    foreach ($j as $p) if (is_array($p) && !empty($p['id']) && isset(OW_MAP_KINDS[$p['kind'] ?? ''])) $out[] = $p;
    return $out;
}
function owArtPlacesSave(array $places): bool {
    return owSetSetting('art_places', json_encode(array_values($places), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}
/* Only the published ones, for visitors */
function owArtPlacesPublished(string $kind, string $city = ''): array {
    $out = [];
    foreach (owArtPlaces() as $p) {
        if (($p['status'] ?? 'draft') !== 'published' || $p['kind'] !== $kind) continue;
        if ($city !== '' && ($p['city'] ?? 'other') !== $city) continue;
        $out[] = $p;
    }
    usort($out, fn($a, $b) => strcasecmp((string) $a['name_en'], (string) $b['name_en']));
    return $out;
}
function owArtPlacesDrafts(): int {
    $n = 0;
    foreach (owArtPlaces() as $p) if (($p['status'] ?? 'draft') !== 'published') $n++;
    return $n;
}
/* A web address we are willing to link to (https / http only) */
function owMapUrl(string $u): string {
    $u = trim($u);
    return (preg_match('#^https?://[^\s<>"]+$#i', $u) && filter_var($u, FILTER_VALIDATE_URL)) ? $u : '';
}
/* The Google Maps link: the saved one (Google addresses only), else a search for the name and city */
function owMapsLink(string $nameEn, string $cityEn, string $saved = ''): string {
    $s = owMapUrl($saved);
    if ($s !== '' && preg_match('#^https://(www\.)?(google\.[a-z.]+/maps|maps\.google\.[a-z.]+|maps\.app\.goo\.gl|goo\.gl/maps)#i', $s)) return $s;
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(trim($nameEn . ', ' . $cityEn . ', Saudi Arabia', ', '));
}
