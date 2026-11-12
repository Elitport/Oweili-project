<?php
/* TEMPORARY (owner, 12 Nov 2026): list the hidden institutes of the older institutes list, then (a=run&ids=…)
   make a full backup and show only the chosen ones (institutes.is_active = 1). Nothing else changes.
   Locked by a secret key, runs once. Removed straight after. */
if (!hash_equals('1947b6b728eeb6c615f347432eabbdfa408e79070414cc2ec5cd1b09ca8cf0dc', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
@set_time_limit(300);
$db = getDB();
$show = function () use ($db) {
    foreach ($db->query('SELECT id, is_active, name_en, name_ar, city_en, website_url, description_en FROM institutes ORDER BY id') as $r)
        echo $r['id'], ' | ', $r['is_active'] ? 'SHOWN' : 'hidden', ' | ', $r['name_en'], ' | ', $r['name_ar'], ' | ', $r['city_en'], ' | ', $r['website_url'], "\n   ", preg_replace('/\s+/', ' ', (string) $r['description_en']), "\n";
};
if (($_GET['a'] ?? '') !== 'run') { $show(); exit; }
if (is_file(__DIR__ . '/.logs/zz-institutes-done')) { echo "already run\n"; $show(); exit; }
$ids = array_values(array_filter(array_map('intval', explode(',', (string) ($_GET['ids'] ?? ''))), fn($i) => $i > 0));
if (!$ids || count($ids) > 11) { echo "no ids\n"; exit; }

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

$st = $db->prepare('UPDATE institutes SET is_active = 1 WHERE is_active = 0 AND id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
$st->execute($ids);
@file_put_contents(__DIR__ . '/.logs/zz-institutes-done', gmdate('c'));
echo "shown now: ", $st->rowCount(), "\n"; $show();
