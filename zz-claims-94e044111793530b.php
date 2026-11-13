<?php
/* TEMPORARY (owner, 13 Nov 2026): make a full backup, then create the art_place_claims table ("Is this your place?").
   Only adds one table (art_place_claims.sql). Locked by a secret key, runs once. Removed straight after. */
if (!hash_equals('b3c8c1d625bc6cea3d7a4ff359bc956a913df2fe8d2945c05e70cb00b0bf8077', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
@set_time_limit(300);
$db = getDB();
$has = fn() => (bool) $db->query("SHOW TABLES LIKE 'art_place_claims'")->fetchColumn();
if (($_GET['a'] ?? '') !== 'run') { echo 'table exists: ', $has() ? 'yes' : 'no', "\n"; exit; }
if (is_file(__DIR__ . '/.logs/zz-claims-done')) { echo "already run\n"; exit; }
$dbName = (string) $db->query('SELECT DATABASE()')->fetchColumn();
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
$sql = preg_replace('/^\s*--.*$/m', '', (string) file_get_contents(__DIR__ . '/art_place_claims.sql'));
$db->exec($sql);
@file_put_contents(__DIR__ . '/.logs/zz-claims-done', gmdate('c'));
if (is_file(__DIR__ . '/.logs/schema_cache.json')) @unlink(__DIR__ . '/.logs/schema_cache.json');   // forget "table missing"
echo 'table exists: ', $has() ? 'yes' : 'no', "\n";
