<?php
/* =====================================================================
 *  artmap_lib.php — Art Map («خريطة الفن» page, «وجهات» in the menu; owner,
 *  12 Nov 2026): museums, art galleries and art institutes in Saudi Arabia.
 *  Since the 148-place import (12 Nov 2026) the places live in their own
 *  table, art_places (art_places.sql, add-only), instead of one saved setting.
 *  Every place starts as a draft; only the admin publishes it
 *  (admin_art_map.php). The older institutes directory (table institutes,
 *  with courses) stays as it is and shows on the Institutes tab next to the
 *  published art_places institutes.
 *  Each place: kind, name, city, region (one of the 13), short text in both
 *  languages, address, phone, official website, Instagram, Google Maps link,
 *  a free-licence photo with its credit (assets/img/places/), and, for the
 *  admin only, the sources checked and a confidence level (high/medium/low).
 *  Tables: art_places, institutes (read by art_map.php)
 * ===================================================================== */
require_once __DIR__ . '/gallery_lib.php';

const OW_MAP_KINDS = [
    'museum'    => ['Museums', 'المتاحف', 'Museum', 'متحف'],
    'gallery'   => ['Art Galleries', 'المعارض الفنية', 'Art gallery', 'معرض فني'],
    'institute' => ['Institutes', 'المعاهد', 'Art institute', 'معهد فني'],
];
/* The region filter: the 13 regions of Saudi Arabia (owner, 12 Nov 2026) */
const OW_MAP_REGIONS = [
    'riyadh'   => ['Riyadh', 'الرياض'],
    'makkah'   => ['Makkah', 'مكة المكرمة'],
    'eastern'  => ['Eastern Province', 'المنطقة الشرقية'],
    'madinah'  => ['Madinah', 'المدينة المنورة'],
    'qassim'   => ['Qassim', 'القصيم'],
    'hail'     => ['Hail', 'حائل'],
    'tabuk'    => ['Tabuk', 'تبوك'],
    'jouf'     => ['Al-Jouf', 'الجوف'],
    'northern' => ['Northern Borders', 'الحدود الشمالية'],
    'asir'     => ['Asir', 'عسير'],
    'jazan'    => ['Jazan', 'جازان'],
    'najran'   => ['Najran', 'نجران'],
    'baha'     => ['Al-Baha', 'الباحة'],
];
const OW_MAP_CONF = ['high' => ['High', 'عالية'], 'medium' => ['Medium', 'متوسطة'], 'low' => ['Low', 'منخفضة']];

/* The region of a city or region name (used for the institutes table and old links) */
function owMapRegionKey(string $s): string {
    $c = mb_strtolower(trim($s));
    $map = [
        'riyadh'   => 'riyadh|diriyah|الرياض|الدرعية|shaqra|ushaiger|kharj',
        'makkah'   => 'makkah|mecca|jedd?ah|jidd?ah|taif|مكة|جدة|جده|الطائف',
        'eastern'  => 'eastern|dammam|khobar|dhahran|ahsa|hofuf|mubarraz|qatif|tarout|jubail|الشرقية|الدمام|الخبر|الظهران|الأحساء|الاحساء',
        'madinah'  => 'madinah|medina|al\s*-?\s*ula|yanbu|المدينة|العلا|ينبع',
        'qassim'   => 'qassim|buraydah|unaizah|القصيم|بريدة|عنيزة',
        'hail'     => 'hail|حائل',
        'tabuk'    => 'tabuk|tayma|تبوك|تيماء',
        'jouf'     => 'jouf|sakaka|dumat|الجوف|سكاكا|دومة',
        'northern' => 'northern|arar|rafha|الحدود|عرعر',
        'asir'     => 'asir|aseer|abha|namas|rijal|khamis|ahad rafidah|عسير|أبها|ابها|النماص|رجال',
        'jazan'    => 'jazan|jizan|farasan|samtah|dayer|جازان|فرسان',
        'najran'   => 'najran|نجران',
        'baha'     => 'baha|الباحة',
    ];
    foreach ($map as $k => $re) if (preg_match('/' . $re . '/u', $c)) return $k;
    return '';
}

/* The small outline icon of each kind (museum, gallery, institute), the inside of a 24 × 24 <svg> */
function owMapKindIcon(string $kind): string {
    if ($kind === 'institute') return '<path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1 2.7 3 6 3s6-2 6-3v-5"/>';
    if ($kind === 'gallery') return '<rect x="3" y="4" width="18" height="14" rx="1.5"/><path d="m3 15 5-5 4 4 3-3 6 6"/><circle cx="15.5" cy="8.5" r="1.5"/>';
    return '<path d="M3 21h18M5 21V10l7-5 7 5v11"/><path d="M9 21v-6h6v6"/>';
}

function owArtPlacesReady(): bool { return owHasTable('art_places'); }

/* Published places of one kind (optionally one region), for visitors */
function owArtPlacesPublished(string $kind, string $region = ''): array {
    if (!owArtPlacesReady()) return [];
    $sql = "SELECT * FROM art_places WHERE status = 'published' AND kind = :k" . ($region !== '' ? ' AND region = :r' : '') . ' ORDER BY name_en';
    $st = getDB()->prepare($sql);
    $st->execute($region !== '' ? [':k' => $kind, ':r' => $region] : [':k' => $kind]);
    return $st->fetchAll();
}
/* All published places (museums, galleries, institutes), optionally one region: the Art Map's "All" tab (owner, 12 Nov 2026) */
function owArtPlacesAllPublished(string $region = ''): array {
    if (!owArtPlacesReady()) return [];
    $st = getDB()->prepare("SELECT * FROM art_places WHERE status = 'published'" . ($region !== '' ? ' AND region = :r' : '') . " ORDER BY FIELD(kind, 'museum', 'gallery', 'institute'), name_en");
    $st->execute($region !== '' ? [':r' => $region] : []);
    return $st->fetchAll();
}
function owArtPlacesDrafts(): int {
    try { return owArtPlacesReady() ? (int) getDB()->query("SELECT COUNT(*) FROM art_places WHERE status <> 'published'")->fetchColumn() : 0; }
    catch (Throwable $e) { return 0; }
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
/* Instagram: a full instagram.com address, or "@name" / "name" → the full address */
function owInstaUrl(string $v): string {
    $v = trim($v);
    if ($v === '') return '';
    if (preg_match('#^https?://(www\.)?instagram\.com/([A-Za-z0-9._]{1,30})/?#i', $v, $m)) return 'https://www.instagram.com/' . $m[2];
    if (preg_match('/^@?([A-Za-z0-9._]{1,30})$/', $v, $m)) return 'https://www.instagram.com/' . $m[1];
    return '';
}
/* A phone number we can dial: digits and a leading + only */
function owTelHref(string $v): string {
    $t = preg_replace('/[^0-9+]/', '', $v);
    return strlen($t) >= 7 ? 'tel:' . $t : '';
}
/* The place's photo (assets/img/places/<file>.webp + .jpg), or '' */
function owPlacePhotoBase(array $p): string {
    $b = (string) ($p['photo_path'] ?? '');
    if ($b === '' || !preg_match('#^assets/img/places/[a-z0-9-]+$#', $b) || !is_file(__DIR__ . '/' . $b . '.jpg')) return '';
    return $b;
}
