<?php
/* TEMPORARY (owner, 9 Nov 2026): visitor statistics clean-up. Shows the counted rows,
   downloads the network file now, and (a=del) makes a full backup and then deletes
   the test and server rows named in rows=. Locked by a secret key. Removed straight after. */
if (!hash_equals('f5f410b167eec3144444b039a21076e8ee0ad7815423a1b07c4eaa7ecfcbce7d', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/geo_lib.php';
require_once __DIR__ . '/visitor_lib.php';
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
@set_time_limit(300);
$db = getDB();
$dbName = (string) $db->query('SELECT DATABASE()')->fetchColumn();
$a = (string) ($_GET['a'] ?? '');
$show = function () use ($db) {
    echo "== visitor_stats rows\n";
    foreach ($db->query('SELECT day, country, city, visits FROM visitor_stats ORDER BY day, country, city') as $r) echo implode(' | ', $r), "\n";
    echo "total: ", $db->query('SELECT COALESCE(SUM(visits),0) FROM visitor_stats')->fetchColumn(), "\n";
};
if ($a === 'fetch') {
    @touch(OW_GEO_DIR . '/last_try_asn');
    echo owGeoFetch(['file' => OW_GEO_ASN_FILE, 'name' => 'dbip-asn-lite', 'try' => 'last_try_asn', 'version' => 'version_asn']) ? "network file downloaded\n" : "network file download FAILED\n";
}
echo "database $dbName\n";
echo "city file: ", owGeoReady() ? filesize(OW_GEO_FILE) . ' bytes, version ' . @file_get_contents(OW_GEO_DIR . '/version') : 'missing', "\n";
echo "network file: ", is_file(OW_GEO_ASN_FILE) ? filesize(OW_GEO_ASN_FILE) . ' bytes, version ' . @file_get_contents(OW_GEO_DIR . '/version_asn') : 'missing', "\n";
$me = owVisitorIp(); $g = owGeoLookup($me); $n = owGeoAsn($me);
echo "this request: ", ($g['cc'] ?? '?'), ' ', ($g['city'] ?? ''), ' | network ', json_encode($n), ' | data center: ', owIsDataCenter($me) ? 'YES (not counted)' : 'no', "\n";
foreach (['8.8.8.8', '3.5.140.2', '20.42.0.1', '1.1.1.1'] as $ip) echo "check $ip: ", json_encode(owGeoAsn($ip)), ' data center: ', owIsDataCenter($ip) ? 'YES' : 'no', "\n";
if ($a !== 'del') { $show(); exit; }

$pairs = [];
foreach (explode(';', (string) ($_GET['rows'] ?? '')) as $p) {
    $x = explode('|', $p, 2);
    if (count($x) === 2 && preg_match('/^[A-Z]{2}$/', $x[0]) && $x[1] !== '') $pairs[] = $x;
}
if (!$pairs) { echo "no rows named\n"; exit; }
if (is_file(__DIR__ . '/.logs/zz-geofix-done')) { echo "already run\n"; $show(); exit; }

/* 1. full backup */
$dir = __DIR__ . '/.backups';
if (!is_dir($dir)) { @mkdir($dir, 0700, true); }
if (!is_file($dir . '/.htaccess')) { @file_put_contents($dir . '/.htaccess', "Require all denied\n"); }
$file = $dir . '/db-' . preg_replace('/[^a-z0-9_]/i', '', $dbName) . '-' . gmdate('Ymd-His') . '.sql.gz';
$gz = gzopen($file, 'wb6');
if (!$gz) { echo "backup FAILED: cannot write\n"; exit; }
gzwrite($gz, "-- Oweili full backup " . gmdate('c') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");
$tables = $db->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_COLUMN);
$rows = 0;
foreach ($tables as $t) {
    $t = str_replace('`', '', (string) $t);
    $c = $db->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM);
    gzwrite($gz, "\nDROP TABLE IF EXISTS `$t`;\n" . $c[1] . ";\n");
    $st = $db->query("SELECT * FROM `$t`");
    while ($r = $st->fetch(PDO::FETCH_NUM)) {
        $vals = array_map(fn($v) => $v === null ? 'NULL' : $db->quote((string) $v), $r);
        gzwrite($gz, "INSERT INTO `$t` VALUES (" . implode(',', $vals) . ");\n");
        $rows++;
    }
}
gzwrite($gz, "\nSET FOREIGN_KEY_CHECKS=1;\n");
gzclose($gz);
$size = (int) filesize($file);
if ($size < 500) { echo "backup FAILED (too small)\n"; exit; }
echo "backup ok: .backups/" . basename($file) . " (" . count($tables) . " tables, $rows rows, $size bytes)\n";

foreach ($pairs as [$cc, $city]) {
    $st = $db->prepare('DELETE FROM visitor_stats WHERE country = ? AND city = ?');
    $st->execute([$cc, $city]);
    echo "deleted $cc $city: ", $st->rowCount(), " row(s)\n";
}
@file_put_contents(__DIR__ . '/.logs/zz-geofix-done', gmdate('c'));
$show();
