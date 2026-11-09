<?php
/* =====================================================================
 *  geo_lib.php — visitor city statistics (owner, 9 Nov 2026)
 *
 *  - The country and city of a visitor come from DB-IP's free "IP to City
 *    Lite" database (CC BY 4.0: https://db-ip.com), kept on OUR server in
 *    the private folder .private/geo/ (never in GitHub, never sent to
 *    visitors). No outside service is asked on a visit.
 *  - Read with MaxMind's open reader (lib/maxmind-db, Apache-2.0).
 *  - Only date + country + city + a count are saved (table visitor_stats).
 *    The internet address is used for the lookup and then forgotten.
 *  - One count per browser per day: a cookie that holds only the date
 *    (ow_seen=YYYYMMDD), so nothing that identifies a person is kept.
 *  - Not counted: robots (user agent), browsers run by test or robot
 *    software (navigator.webdriver), visits from data centers and cloud
 *    servers (DB-IP's free "IP to ASN Lite" file, same folder, CC BY 4.0,
 *    matched against OW_DC_ASN / OW_DC_WORDS below), staff (admin password
 *    session, admin / sub-admin accounts) and any browser that once opened
 *    Admin (cookie ow_nostat).
 *  - The database files are refreshed once a month by the site itself, after
 *    a visitor's page has been sent (owGeoMaybeUpdate).
 *  Tables: visitor_stats (visitor_stats.sql)
 * ===================================================================== */

const OW_GEO_DIR  = __DIR__ . '/.private/geo';
const OW_GEO_FILE = OW_GEO_DIR . '/dbip-city-lite.mmdb';
const OW_GEO_ASN_FILE = OW_GEO_DIR . '/dbip-asn-lite.mmdb';   // which network an address belongs to

function owGeoReady(): bool { return is_file(OW_GEO_FILE) && filesize(OW_GEO_FILE) > 1000; }

/* MaxMind's open reader for one of our files (kept open for the request) */
function owGeoReader(string $file): ?\MaxMind\Db\Reader {
    static $readers = [], $auto = false;
    if (isset($readers[$file])) return $readers[$file];
    if (!$auto) {
        $auto = true;
        spl_autoload_register(function ($c) {
            if (strpos($c, 'MaxMind\\Db\\') !== 0) return;
            $f = __DIR__ . '/lib/maxmind-db/src/' . str_replace('\\', '/', $c) . '.php';
            if (is_file($f)) require_once $f;
        });
    }
    if (!is_file($file) || filesize($file) <= 1000) return null;
    try { return $readers[$file] = new \MaxMind\Db\Reader($file); }
    catch (\Throwable $e) { error_log('owGeoReader: ' . $e->getMessage()); return null; }
}

/* Data centers and cloud servers (owner, 9 Nov 2026). A real person browses from a
   home, mobile or office network; robots, test browsers and copied-page services
   run on rented servers. The network of an address comes from DB-IP's ASN file.
   Not on the list on purpose: Cloudflare (13335), Fastly (54113) and Akamai's
   relay network (36183), because Apple's iCloud Private Relay and Cloudflare WARP
   send real people's visits through them. */
const OW_DC_ASN = [
    16509, 14618, 8987, 38895, 7224,          // Amazon (AWS)
    15169, 396982, 19527, 36040, 139070,      // Google, Google Cloud
    8075, 8068, 12076, 3598,                  // Microsoft, Azure
    31898, 7160,                               // Oracle Cloud
    14061, 63949, 16276, 24940, 213230, 20473, // DigitalOcean, Linode (Akamai Cloud), OVH, Hetzner, Vultr / Choopa
    45102, 37963, 132203, 45090,              // Alibaba Cloud, Tencent Cloud
    51167, 12876, 60781, 28753, 16265, 9009,  // Contabo, Scaleway, Leaseweb (3), M247
    47583, 136907, 55990, 36352, 35916,       // Hostinger, Huawei Cloud (2), ColoCrossing, Multacom
    46664, 62567, 40065, 32097, 53667,        // VolumeDrive, DigitalOcean, CNSERVERS, WholeSale Internet, FranTech / BuyVM
    212238, 200651, 60068,                    // Datacamp, Flokinet, CDN77
];
const OW_DC_WORDS = '/\b(amazon|aws|google cloud|microsoft|azure|digitalocean|digital ocean|ovh|hetzner|linode|vultr|choopa|oracle|alibaba|aliyun|tencent|huawei cloud|contabo|scaleway|leaseweb|m247|hostinger|godaddy|ionos|hostwinds|colocrossing|datacamp|cdn77|psychz|quadranet|zenlayer|g-core|gcore|kamatera|upcloud|hivelocity|worldstream|nforce|frantech|buyvm|racknerd|servers\.com|data ?cent(er|re)|hosting|colocation|vps|dedicated server|server farm)\b/i';
const OW_DC_KEEP = [13335, 54113, 36183];   // relays real people use (see above)

/* Network number and name for an address, or null when the file is missing */
function owGeoAsn(string $ip): ?array {
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return null;
    $r = owGeoReader(OW_GEO_ASN_FILE);
    if (!$r) return null;
    try { $a = $r->get($ip); } catch (\Throwable $e) { return null; }
    if (!is_array($a)) return null;
    return ['asn' => (int) ($a['autonomous_system_number'] ?? 0), 'org' => (string) ($a['autonomous_system_organization'] ?? '')];
}

/* True when the address belongs to a data center or cloud server */
function owIsDataCenter(string $ip): bool {
    $a = owGeoAsn($ip);
    if (!$a || $a['asn'] <= 0) return false;            // unknown: counted (never guessed away)
    if (in_array($a['asn'], OW_DC_KEEP, true)) return false;
    if (in_array($a['asn'], OW_DC_ASN, true)) return true;
    return (bool) preg_match(OW_DC_WORDS, $a['org']);
}

/* Robots, link previews, monitors and empty user agents */
function owIsBot(string $ua): bool {
    if (trim($ua) === '') return true;
    return (bool) preg_match('/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|discord|skype|embedly|quora|pinterest|bingpreview|headless|lighthouse|pagespeed|gtmetrix|pingdom|uptime|monitor|curl|wget|python|java\/|go-http|okhttp|axios|node-fetch|php\/|scrapy|httpclient|feed|validator|playwright|puppeteer|selenium|phantom/i', $ua);
}

/* Country code + city for an address, or null */
function owGeoLookup(string $ip): ?array {
    if (!owGeoReady() || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return null;
    $reader = owGeoReader(OW_GEO_FILE);
    if (!$reader) return null;
    try {
        $r = $reader->get($ip);
    } catch (\Throwable $e) { error_log('owGeoLookup: ' . $e->getMessage()); return null; }
    if (!is_array($r)) return null;
    $cc   = strtoupper((string) ($r['country']['iso_code'] ?? ''));
    $city = trim((string) ($r['city']['names']['en'] ?? ''));
    if (!preg_match('/^[A-Z]{2}$/', $cc)) return null;
    return ['cc' => $cc, 'city' => mb_substr(preg_replace('/[\x00-\x1F\x7F<>]/u', '', $city), 0, 100)];
}

/* Add one visit (date, country, city). Nothing else is written. */
function owGeoCount(string $ip): bool {
    if (!function_exists('owHasTable') || !owHasTable('visitor_stats')) return false;
    $g = owGeoLookup($ip);
    $cc = $g['cc'] ?? 'ZZ';                 // ZZ = unknown
    $city = $g['city'] ?? '';
    getDB()->prepare('INSERT INTO visitor_stats (day, country, city, visits) VALUES (CURDATE(), :c, :t, 1)
                      ON DUPLICATE KEY UPDATE visits = visits + 1')
           ->execute([':c' => $cc, ':t' => $city]);
    return true;
}

/* Names in the page language (country names from PHP's own list) */
function owCountryName(string $cc, bool $ar): string {
    if ($cc === 'ZZ' || $cc === '') return $ar ? 'غير معروف' : 'Unknown';
    if (class_exists('Locale')) { $n = \Locale::getDisplayRegion('-' . $cc, $ar ? 'ar' : 'en'); if ($n !== '' && $n !== $cc) return $n; }
    return $cc;
}

/* Arabic names for the main Saudi and Gulf cities (owner, 9 Nov 2026). The location
   file gives city names in English only; other cities stay in English. Several
   spellings of the same city are listed, because the file is not always the same. */
const OW_CITY_AR = [
    // Saudi Arabia
    'riyadh' => 'الرياض', 'jeddah' => 'جدة', 'jiddah' => 'جدة', 'mecca' => 'مكة المكرمة', 'makkah' => 'مكة المكرمة',
    'medina' => 'المدينة المنورة', 'madinah' => 'المدينة المنورة', 'dammam' => 'الدمام', 'khobar' => 'الخبر', 'dhahran' => 'الظهران',
    'taif' => 'الطائف', 'tabuk' => 'تبوك', 'abha' => 'أبها', 'khamis mushait' => 'خميس مشيط', 'buraidah' => 'بريدة', 'buraydah' => 'بريدة',
    'unaizah' => 'عنيزة', 'unayzah' => 'عنيزة', 'hail' => 'حائل', "ha'il" => 'حائل', 'jazan' => 'جازان', 'jizan' => 'جازان', 'najran' => 'نجران',
    'hofuf' => 'الهفوف', 'hufuf' => 'الهفوف', 'mubarraz' => 'المبرز', 'jubail' => 'الجبيل', 'yanbu' => 'ينبع', 'kharj' => 'الخرج',
    'qatif' => 'القطيف', 'hafar al-batin' => 'حفر الباطن', 'hafar al batin' => 'حفر الباطن', 'arar' => 'عرعر', 'sakakah' => 'سكاكا',
    'baha' => 'الباحة', 'bahah' => 'الباحة', 'ula' => 'العلا', 'rabigh' => 'رابغ', 'thuwal' => 'ثول', 'qurayyat' => 'القريات',
    'majmaah' => 'المجمعة', 'zulfi' => 'الزلفي', 'bisha' => 'بيشة', 'ras tanura' => 'رأس تنورة', 'khafji' => 'الخفجي',
    // United Arab Emirates
    'dubai' => 'دبي', 'abu dhabi' => 'أبوظبي', 'sharjah' => 'الشارقة', 'ajman' => 'عجمان', 'ain' => 'العين',
    'ras al-khaimah' => 'رأس الخيمة', 'ras al khaimah' => 'رأس الخيمة', 'fujairah' => 'الفجيرة', 'umm al quwain' => 'أم القيوين', 'umm al-quwain' => 'أم القيوين',
    // Kuwait
    'kuwait city' => 'مدينة الكويت', 'kuwait' => 'الكويت', 'hawalli' => 'حولي', 'ahmadi' => 'الأحمدي', 'jahra' => 'الجهراء', 'salmiya' => 'السالمية', 'farwaniya' => 'الفروانية',
    // Qatar
    'doha' => 'الدوحة', 'rayyan' => 'الريان', 'wakrah' => 'الوكرة', 'khor' => 'الخور', 'lusail' => 'لوسيل',
    // Bahrain
    'manama' => 'المنامة', 'muharraq' => 'المحرق', 'riffa' => 'الرفاع', 'isa town' => 'مدينة عيسى', 'hamad town' => 'مدينة حمد',
    // Oman
    'muscat' => 'مسقط', 'salalah' => 'صلالة', 'sohar' => 'صحار', 'nizwa' => 'نزوى', 'seeb' => 'السيب', 'sur' => 'صور', 'ibri' => 'عبري',
];
function owCityName(string $city, bool $ar): string {
    if (!$ar || $city === '') return $city;
    $k = strtolower(trim(preg_replace('/\s*\(.*\)\s*$/u', '', $city)));   // "Riyadh (Al Olaya)" → riyadh
    if (isset(OW_CITY_AR[$k])) return OW_CITY_AR[$k];
    $k2 = preg_replace('/^(al|ar|ad|as|ash|az)[\s\-]+/', '', $k);     // "Al Khobar", "Al-Ain", "Ar Rayyan" …
    return OW_CITY_AR[$k2] ?? $city;
}

/* Statistics for Admin: totals, by country, by city, for 1 / 7 / 30 days */
function owGeoStats(int $days): array {
    $out = ['total' => 0, 'countries' => [], 'cities' => []];
    if (!function_exists('owHasTable') || !owHasTable('visitor_stats')) return $out;
    $db = getDB();
    $since = 'day > CURDATE() - INTERVAL ' . max(1, min(366, $days)) . ' DAY';
    $out['total'] = (int) $db->query("SELECT COALESCE(SUM(visits),0) FROM visitor_stats WHERE $since")->fetchColumn();
    $out['countries'] = $db->query("SELECT country, SUM(visits) v FROM visitor_stats WHERE $since GROUP BY country ORDER BY v DESC, country LIMIT 50")->fetchAll();
    $out['cities'] = $db->query("SELECT country, city, SUM(visits) v FROM visitor_stats WHERE $since AND city <> '' GROUP BY country, city ORDER BY v DESC, city LIMIT 50")->fetchAll();
    return $out;
}
function owVisitorsToday(): int {
    if (!function_exists('owHasTable') || !owHasTable('visitor_stats')) return 0;
    return (int) getDB()->query('SELECT COALESCE(SUM(visits),0) FROM visitor_stats WHERE day = CURDATE()')->fetchColumn();
}

/* The monthly refresh of the two files (city, network). Runs after a page has
   been sent, at most one try every 6 hours per file, only when a file is missing
   or older than 32 days. A new file is checked before it replaces the old. */
function owGeoMaybeUpdate(): void {
    $files = [
        ['file' => OW_GEO_FILE,     'name' => 'dbip-city-lite', 'try' => 'last_try',     'version' => 'version'],
        ['file' => OW_GEO_ASN_FILE, 'name' => 'dbip-asn-lite',  'try' => 'last_try_asn', 'version' => 'version_asn'],
    ];
    foreach ($files as $g) {
        if (is_file($g['file']) && filesize($g['file']) > 1000 && filemtime($g['file']) > time() - 32 * 86400) continue;
        if (!is_dir(OW_GEO_DIR)) { @mkdir(OW_GEO_DIR, 0700, true); @file_put_contents(OW_GEO_DIR . '/.htaccess', "Require all denied\n"); }
        $try = OW_GEO_DIR . '/' . $g['try'];
        if (is_file($try) && filemtime($try) > time() - 6 * 3600) continue;
        @touch($try);
        @set_time_limit(300);
        owGeoFetch($g);
        return;   // one download per page at most
    }
}
function owGeoFetch(array $g): bool {
    foreach ([gmdate('Y-m'), gmdate('Y-m', strtotime('first day of last month'))] as $ym) {
        $url = 'https://download.db-ip.com/free/' . $g['name'] . '-' . $ym . '.mmdb.gz';
        $tmp = OW_GEO_DIR . '/new-' . $g['name'] . '.mmdb';
        $in = @gzopen($url, 'rb');
        if (!$in) continue;
        $out = @fopen($tmp, 'wb');
        if (!$out) { gzclose($in); return false; }
        while (!gzeof($in)) { $chunk = gzread($in, 1 << 20); if ($chunk === false) break; fwrite($out, $chunk); }
        gzclose($in); fclose($out);
        owGeoReader(OW_GEO_DIR . '/__none__');   // makes sure the reader code is loaded
        try {
            $r = new \MaxMind\Db\Reader($tmp);
            $ok = is_array($r->get('8.8.8.8')); $r->close();
        } catch (\Throwable $e) { $ok = false; }
        if ($ok && filesize($tmp) > 1000) {
            @rename($tmp, $g['file']);
            @file_put_contents(OW_GEO_DIR . '/' . $g['version'], $ym);
            return true;
        }
        @unlink($tmp);
    }
    return false;
}
