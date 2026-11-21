<?php
/* TEMPORARY (owner, 21 Nov 2026): "City or Online" on the Art Institutes "Notify me" form. Full backup first, then
   course_leads_city.sql (adds course_leads.city, add-only). Locked by a secret key, runs once. Removed straight after. */
if (!hash_equals('d87d614e8b44ef91b8284d726b46525d2174a4398c4d23217e295219e993b1fc', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
@set_time_limit(300);
$db = getDB();
$col = fn() => (int) $db->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'course_leads' AND COLUMN_NAME = 'city'")->fetchColumn();
echo 'course_leads.city column: ', $col() ? 'yes' : 'no', "\n";
echo 'course_leads rows: ', $db->query('SELECT COUNT(*) FROM course_leads')->fetchColumn(), "\n";
if (($_GET['a'] ?? '') !== 'run') exit;
if (is_file(__DIR__ . '/.logs/zz-lc-done')) { echo "already run\n"; exit; }
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

$db->exec("ALTER TABLE `course_leads` ADD COLUMN IF NOT EXISTS `city` VARCHAR(120) NOT NULL DEFAULT '' AFTER `phone`");
@unlink(__DIR__ . '/.logs/schema_cache.json');
@file_put_contents(__DIR__ . '/.logs/zz-lc-done', gmdate('c'));
echo 'done. course_leads.city column: ', $col() ? 'yes' : 'no', '; rows: ', $db->query('SELECT COUNT(*) FROM course_leads')->fetchColumn(), "\n";
