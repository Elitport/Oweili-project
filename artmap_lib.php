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
/* The address for Arabic pages: the Arabic one when it is saved (owner, 14 Nov 2026), else the English one */
function owPlaceAddressAr(array $p): string { $a = trim((string) ($p['address_ar'] ?? '')); return $a !== '' ? $a : (string) ($p['address'] ?? ''); }

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
/* The place's photo (assets/img/places/<file> from our own search, or uploads/places/<file> uploaded by the admin;
   .jpg always, .webp when the server could make one), or '' */
function owPlacePhotoBase(array $p): string {
    $b = (string) ($p['photo_path'] ?? '');
    if ($b === '' || !preg_match('#^(assets/img/places|uploads/places)/[a-z0-9-]+$#', $b) || !is_file(__DIR__ . '/' . $b . '.jpg')) return '';
    return $b;
}
/* <picture> of a place photo (webp first when it exists) */
function owPlacePicture(string $base, string $alt, int $w = 480, int $h = 300, bool $lazy = true): string {
    $webp = is_file(__DIR__ . '/' . $base . '.webp') ? '<source type="image/webp" srcset="' . e(owBase() . $base . '.webp') . '">' : '';
    return '<picture>' . $webp . '<img' . ($lazy ? ' loading="lazy"' : '') . ' decoding="async" width="' . $w . '" height="' . $h . '" src="' . e(owBase() . $base . '.jpg') . '" alt="' . e($alt) . '"></picture>';
}
/* Save a photo the admin uploads for a place (owner, 13 Nov 2026): JPG / PNG / WebP up to 10 MB, turned upright,
   hidden data (GPS, camera) dropped by redrawing, at most 800 px wide, saved as JPG + WebP in uploads/places/ (kept by
   deploys). Returns ['ok' => true, 'base' => 'uploads/places/…'] or ['ok' => false, 'err' => [en, ar]] */
function owPlaceStorePhoto(array $file, int $placeId): array {
    $r = owPlaceReadUpload($file);
    if (!$r['ok']) return $r;
    return owPlaceWriteImage($r['img'], $placeId);
}
/* Read one uploaded photo (JPG / PNG / WebP up to 10 MB, at least 200 × 120), turned upright. ['ok' => true, 'img' => GdImage] or an error */
function owPlaceReadUpload(array $file): array {
    $bad = fn(string $en, string $ar) => ['ok' => false, 'err' => [$en, $ar]];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || empty($file['tmp_name'])) return $bad('Please choose a photo.', 'يرجى اختيار صورة.');
    if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || (int) $file['size'] > 10 * 1024 * 1024) return $bad('The photo is too large (10 MB at most).', 'الصورة كبيرة جداً (10 ميغابايت كحد أقصى).');
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) return $bad('The photo could not be received. Please try again.', 'تعذّر استلام الصورة. حاول مرة أخرى.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || !function_exists('imagecreatefromstring')) return $bad('Please send a JPG, PNG or WebP photo.', 'يرجى إرسال صورة JPG أو PNG أو WebP.');
    $img = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
    if (!$img) return $bad('Please send a JPG, PNG or WebP photo.', 'يرجى إرسال صورة JPG أو PNG أو WebP.');
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $o = (int) (@exif_read_data($file['tmp_name'])['Orientation'] ?? 1);
        if ($o === 3) $img = imagerotate($img, 180, 0); elseif ($o === 6) $img = imagerotate($img, -90, 0); elseif ($o === 8) $img = imagerotate($img, 90, 0);
    }
    if (imagesx($img) < 200 || imagesy($img) < 120) return $bad('The photo is too small (at least 200 × 120 pixels).', 'الصورة صغيرة جداً (200 × 120 بكسل على الأقل).');
    return ['ok' => true, 'img' => $img];
}
/* Redraw a photo at most $maxW wide (no hidden data kept) and save it as $absBase.jpg (+ .webp when $webp) */
function owPlaceSaveGd($img, string $absBase, int $maxW = 800, bool $webp = true): bool {
    $w = imagesx($img); $h = imagesy($img);
    $nw = min($maxW, $w); $nh = (int) round($h * $nw / $w);
    $out = imagecreatetruecolor($nw, $nh);
    imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
    imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
    if (!@imagejpeg($out, $absBase . '.jpg', 80)) return false;
    if ($webp && function_exists('imagewebp')) @imagewebp($out, $absBase . '.webp', 78);
    return true;
}
/* A place photo in uploads/places/ (kept by deploys). ['ok' => true, 'base' => 'uploads/places/…'] or an error */
function owPlaceWriteImage($img, int $placeId): array {
    $dir = __DIR__ . '/uploads/places';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $base = 'uploads/places/p' . $placeId . '-' . bin2hex(random_bytes(5));
    if (!is_dir($dir) || !owPlaceSaveGd($img, __DIR__ . '/' . $base)) return ['ok' => false, 'err' => ['The photo could not be saved.', 'تعذّر حفظ الصورة.']];
    return ['ok' => true, 'base' => $base];
}

/* "Is this your place?" messages (art_place_claims.sql; owner, 13 Nov 2026). Photos wait in .private/place-claims/ */
const OW_CLAIM_DIR = '.private/place-claims';
function owClaimsReady(): bool { return owHasTable('art_place_claims'); }
function owClaimsWaiting(): int {
    try { return owClaimsReady() ? (int) getDB()->query("SELECT COUNT(*) FROM art_place_claims WHERE status = 'new'")->fetchColumn() : 0; }
    catch (Throwable $e) { return 0; }
}
function owClaimPhotoPath(string $file): string {
    return preg_match('/^[a-f0-9]{24}$/', $file) ? __DIR__ . '/' . OW_CLAIM_DIR . '/' . $file . '.jpg' : '';
}

/* Remove an uploaded place photo's files (never our own assets/img/places files, which come with the code) */
function owPlaceDropUpload(string $base): void {
    if (!preg_match('#^uploads/places/[a-z0-9-]+$#', $base)) return;
    foreach (['.jpg', '.webp'] as $x) if (is_file(__DIR__ . '/' . $base . $x)) @unlink(__DIR__ . '/' . $base . $x);
}

/* =====================================================================
 *  Place pages for Google (owner, 13 Nov 2026): every public place has its
 *  own page at /art-map/<region>/<name> (art_place.php, through .htaccess)
 *  and every region a page at /art-map/<region> ("Museums and art galleries
 *  in Riyadh"). Addresses are made from the English name; two places with
 *  the same name in one region get -2, -3. No database change.
 * ===================================================================== */
function owMapSlug(string $s): string {
    $s = preg_replace('/\s*\(.*?\)\s*/u', ' ', $s);
    if (function_exists('iconv')) { $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s); if ($t !== false) $s = $t; }
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s), '-'));
    return $s !== '' ? mb_substr($s, 0, 80) : 'place';
}
/* Every public place once: the published art_places and the shown older institutes (an older institute with the same
   name as a museum or gallery is left out, like on the All tab). Each with region, slug and url. Kept for the request */
function owMapDirectory(): array {
    static $dir = null;
    if ($dir !== null) return $dir;
    $dir = [];
    $rows = [];
    try { foreach (owArtPlacesAllPublished() as $p) { $p['src'] = 'p'; $rows[] = $p; } } catch (Throwable $e) {}
    try {
        if (owHasTable('institutes')) {
            $seen = [];
            foreach ($rows as $p) if ($p['kind'] !== 'institute') $seen[mb_strtolower(trim(preg_replace('/\s*\(.*\)\s*$/u', '', (string) $p['name_en'])))] = 1;
            $upd = owHasColumn('institutes', 'updated_at') ? 'updated_at' : 'NULL AS updated_at';   // the institutes table may have no updated_at
            foreach (getDB()->query("SELECT id, name_en, name_ar, city_en, city_ar, description_en, description_ar, contact_phone, website_url, $upd FROM institutes WHERE is_active = 1 ORDER BY name_en") as $i) {
                if (isset($seen[mb_strtolower(trim(preg_replace('/\s*\(.*\)\s*$/u', '', (string) $i['name_en'])))])) continue;
                $rg = owMapRegionKey((string) ($i['city_en'] ?: $i['city_ar']));
                if ($rg === '') continue;
                $rows[] = ['src' => 'i', 'id' => $i['id'], 'kind' => 'institute', 'region' => $rg, 'name_en' => (string) ($i['name_en'] ?: $i['name_ar']), 'name_ar' => (string) ($i['name_ar'] ?: $i['name_en']),
                           'city_en' => (string) ($i['city_en'] ?: $i['city_ar']), 'city_ar' => (string) ($i['city_ar'] ?: $i['city_en']), 'desc_en' => owMapPlain((string) ($i['description_en'] ?: $i['description_ar'])), 'desc_ar' => owMapPlain((string) ($i['description_ar'] ?: $i['description_en'])),
                           'address' => '', 'phone' => (string) $i['contact_phone'], 'website' => (string) $i['website_url'], 'instagram' => '', 'maps_url' => '', 'lat' => null, 'lng' => null,
                           'photo_path' => '', 'photo_credit' => '', 'photo_page' => '', 'updated_at' => $i['updated_at'] ?? null];
            }
        }
    } catch (Throwable $e) {}
    $used = [];
    foreach ($rows as $r) {
        if (!isset(OW_MAP_REGIONS[(string) $r['region']])) continue;
        $base = owMapSlug((string) $r['name_en']); $slug = $base; $n = 2;
        while (isset($used[$r['region'] . '/' . $slug])) $slug = $base . '-' . $n++;
        $used[$r['region'] . '/' . $slug] = 1;
        $r['slug'] = $slug; $r['url'] = 'art-map/' . $r['region'] . '/' . $slug;
        $dir[$r['src'] . $r['id']] = $r;
    }
    return $dir;
}
function owMapPlain(string $s): string { return trim(preg_replace('/\s+/u', ' ', strip_tags($s))); }
function owMapPlaceUrl(string $src, int $id): string { return owMapDirectory()[$src . $id]['url'] ?? ''; }
/* The region page's title: "Museums and art galleries in Riyadh" / «متاحف ومعارض الرياض» */
function owMapRegionTitle(string $rg): array {
    $R = OW_MAP_REGIONS[$rg];
    $in = in_array($rg, ['eastern', 'northern'], true) ? 'the ' . $R[0] : $R[0];
    return ['Museums and art galleries in ' . $in, 'متاحف ومعارض ' . $R[1]];
}
/* "Official website", "Instagram" and "Google Maps" buttons of one place (only the ones that exist); shared by the Art Map and the place pages */
function owMapActs(string $web, string $maps, string $nameEn, string $insta = ''): string {
    $h = '<div class="m-acts">';
    if ($web !== '') $h .= '<a class="btn btn-secondary btn-sm" href="' . e($web) . '" target="_blank" rel="noopener">' . owBi('Official website', 'الموقع الرسمي') . ' <span aria-hidden="true">↗</span></a>';
    if ($insta !== '') $h .= '<a class="btn btn-secondary btn-sm" href="' . e($insta) . '" target="_blank" rel="noopener" aria-label="' . e('Instagram: ' . $nameEn) . '"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="0.6"/></svg>Instagram</a>';
    $h .= '<a class="btn btn-dark btn-sm" href="' . e($maps) . '" target="_blank" rel="noopener" aria-label="' . e('Google Maps: ' . $nameEn) . '"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>' . owBi('Google Maps', 'خرائط Google') . '</a>';
    return $h . '</div>';
}
/* "Is this your place?" link (owner, 13 Nov 2026) */
function owMapClaimLink(string $q): string {
    return '<a class="m-claim" href="' . e(owBase() . 'place_claim.php?' . $q) . '">' . owBi('Is this your place? Send us your photo and details', 'هل هذا مكانك؟ أرسل لنا صورتك وبياناتك') . '</a>';
}
