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
 *  - Not counted: robots (user agent), staff (admin password session,
 *    admin / sub-admin accounts) and any browser that once opened Admin
 *    (cookie ow_nostat).
 *  - The database file is refreshed once a month by the site itself, after
 *    a visitor's page has been sent (owGeoMaybeUpdate).
 *  Tables: visitor_stats (visitor_stats.sql)
 * ===================================================================== */

const OW_GEO_DIR  = __DIR__ . '/.private/geo';
const OW_GEO_FILE = OW_GEO_DIR . '/dbip-city-lite.mmdb';

function owGeoReady(): bool { return is_file(OW_GEO_FILE) && filesize(OW_GEO_FILE) > 1000; }

/* Robots, link previews, monitors and empty user agents */
function owIsBot(string $ua): bool {
    if (trim($ua) === '') return true;
    return (bool) preg_match('/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|discord|skype|embedly|quora|pinterest|bingpreview|headless|lighthouse|pagespeed|gtmetrix|pingdom|uptime|monitor|curl|wget|python|java\/|go-http|okhttp|axios|node-fetch|php\/|scrapy|httpclient|feed|validator|playwright|puppeteer|selenium|phantom/i', $ua);
}

/* Country code + city for an address, or null */
function owGeoLookup(string $ip): ?array {
    if (!owGeoReady() || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return null;
    static $reader = null;
    try {
        if ($reader === null) {
            spl_autoload_register(function ($c) {
                if (strpos($c, 'MaxMind\\Db\\') !== 0) return;
                $f = __DIR__ . '/lib/maxmind-db/src/' . str_replace('\\', '/', $c) . '.php';
                if (is_file($f)) require $f;
            });
            $reader = new \MaxMind\Db\Reader(OW_GEO_FILE);
        }
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

/* The monthly refresh of the location file. Runs after a page has been
   sent, at most one try every 6 hours, only when the file is missing or
   older than 32 days. The new file is checked before it replaces the old. */
function owGeoMaybeUpdate(): void {
    if (owGeoReady() && filemtime(OW_GEO_FILE) > time() - 32 * 86400) return;
    if (!is_dir(OW_GEO_DIR)) { @mkdir(OW_GEO_DIR, 0700, true); @file_put_contents(OW_GEO_DIR . '/.htaccess', "Require all denied\n"); }
    $try = OW_GEO_DIR . '/last_try';
    if (is_file($try) && filemtime($try) > time() - 6 * 3600) return;
    @touch($try);
    @set_time_limit(300);
    foreach ([gmdate('Y-m'), gmdate('Y-m', strtotime('first day of last month'))] as $ym) {
        $url = 'https://download.db-ip.com/free/dbip-city-lite-' . $ym . '.mmdb.gz';
        $tmp = OW_GEO_DIR . '/new.mmdb';
        $in = @gzopen($url, 'rb');
        if (!$in) continue;
        $out = @fopen($tmp, 'wb');
        if (!$out) { gzclose($in); return; }
        while (!gzeof($in)) { $chunk = gzread($in, 1 << 20); if ($chunk === false) break; fwrite($out, $chunk); }
        gzclose($in); fclose($out);
        try {
            spl_autoload_register(function ($c) {
                if (strpos($c, 'MaxMind\\Db\\') !== 0) return;
                $f = __DIR__ . '/lib/maxmind-db/src/' . str_replace('\\', '/', $c) . '.php';
                if (is_file($f)) require_once $f;
            });
            $r = new \MaxMind\Db\Reader($tmp);
            $ok = is_array($r->get('8.8.8.8')); $r->close();
        } catch (\Throwable $e) { $ok = false; }
        if ($ok && filesize($tmp) > 1000) {
            @rename($tmp, OW_GEO_FILE);
            @file_put_contents(OW_GEO_DIR . '/version', $ym);
            return;
        }
        @unlink($tmp);
    }
}
