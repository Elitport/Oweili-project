<?php
/* TEMPORARY (owner, 12 Nov 2026): full backup, then publish every Art Map draft whose confidence is not 'low'
   (art_places.status draft -> published). Low-confidence places stay drafts. Nothing else changes.
   Locked by a secret key, runs once. Removed straight after. */
if (!hash_equals('ee18700c5887196dddc3ef5e9afba9f7cd8bab4a00ee3223d4e6326ac663c8a2', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
@set_time_limit(300);
$db = getDB();
$dbName = (string) $db->query('SELECT DATABASE()')->fetchColumn();
$show = function () use ($db) {
    foreach ($db->query('SELECT kind, status, confidence, COUNT(*) n FROM art_places GROUP BY kind, status, confidence ORDER BY 1,2,3') as $r) echo implode(' | ', array_map('strval', $r)), "\n";
    echo "-- published per region: region | museums | galleries | institutes | total\n";
    foreach ($db->query("SELECT region, SUM(kind='museum'), SUM(kind='gallery'), SUM(kind='institute'), COUNT(*) FROM art_places WHERE status='published' GROUP BY region ORDER BY region") as $r) echo implode(' | ', array_map('strval', $r)), "\n";
    echo "old institutes table, shown: ", (int) $db->query('SELECT COUNT(*) FROM institutes WHERE is_active = 1')->fetchColumn(), ", hidden: ", (int) $db->query('SELECT COUNT(*) FROM institutes WHERE is_active = 0')->fetchColumn(), "\n";
};
if (($_GET['a'] ?? '') !== 'run') { echo "ready, database $dbName\n"; $show(); exit; }
if (is_file(__DIR__ . '/.logs/zz-publish-done')) { echo "already run\n"; $show(); exit; }

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

$n = $db->exec("UPDATE art_places SET status = 'published' WHERE status = 'draft' AND confidence <> 'low'");
@file_put_contents(__DIR__ . '/.logs/zz-publish-done', gmdate('c'));
echo "published $n places\n"; $show();
