<?php
/* TEMPORARY (owner, 21 Nov 2026): courses by art type. Full backup first, then courses_art_type.sql
   (adds courses.art_type, courses.art_style, course_leads.art_type; add-only). Locked by a secret key, runs once. Removed straight after. */
if (!hash_equals('a737abc3b251e14d921eac3fc6e30bdf7cf42cd23d97f89d8cc2cd53d4cfae0c', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
@set_time_limit(300);
$db = getDB();
$col = fn($t, $c) => (int) $db->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND COLUMN_NAME = '$c'")->fetchColumn();
$show = function () use ($db, $col) {
    echo 'courses.art_type: ', $col('courses', 'art_type') ? 'yes' : 'no', '; courses.art_style: ', $col('courses', 'art_style') ? 'yes' : 'no', '; course_leads.art_type: ', $col('course_leads', 'art_type') ? 'yes' : 'no', "\n";
    echo 'courses rows: ', $db->query('SELECT COUNT(*) FROM courses')->fetchColumn(), '; course_leads rows: ', $db->query('SELECT COUNT(*) FROM course_leads')->fetchColumn(), "\n";
    if ($col('courses', 'art_type')) foreach ($db->query('SELECT art_type, COUNT(*) n FROM courses GROUP BY art_type') as $r) echo '  courses ', $r['art_type'], ': ', $r['n'], "\n";
};
$show();
if (($_GET['a'] ?? '') !== 'run') exit;
if (is_file(__DIR__ . '/.logs/zz-at-done')) { echo "already run\n"; exit; }
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

$db->exec("ALTER TABLE `courses` ADD COLUMN IF NOT EXISTS `art_type` VARCHAR(20) NOT NULL DEFAULT 'painting'");
$db->exec("ALTER TABLE `courses` ADD COLUMN IF NOT EXISTS `art_style` VARCHAR(50) NOT NULL DEFAULT ''");
$db->exec("ALTER TABLE `course_leads` ADD COLUMN IF NOT EXISTS `art_type` VARCHAR(20) NOT NULL DEFAULT ''");
@unlink(__DIR__ . '/.logs/schema_cache.json');
@file_put_contents(__DIR__ . '/.logs/zz-at-done', gmdate('c'));
echo "done.\n"; $show();
