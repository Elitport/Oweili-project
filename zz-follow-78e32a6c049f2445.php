<?php
/* TEMPORARY (owner, 24 Nov 2026): adds the collector dashboard's one table (collector_update.sql: artist_follows;
   only adds, safe to run twice), after a full backup. Locked by a secret key, runs once. Removed straight after.
   ?a=check shows what exists (read only); ?a=run backs up and adds. */
if (!hash_equals('4140a659534a5dd258211f6c989e5b8d3d7f3aa73fb94a256e0d029748e8934e', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/gallery_lib.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
@set_time_limit(300);
$db = getDB();
$show = function () use ($db) {
    foreach (['artist_follows'] as $t) echo 'table ', $t, ': ', $db->query("SHOW TABLES LIKE " . $db->quote($t))->fetchColumn() ? 'yes' : 'no', "\n";
    echo 'users: ', $db->query('SELECT COUNT(*) FROM users')->fetchColumn(), ' · artworks: ', $db->query('SELECT COUNT(*) FROM artworks')->fetchColumn(), ' · activity_log: ', $db->query('SELECT COUNT(*) FROM activity_log')->fetchColumn(), "\n";
};
$a = (string) ($_GET['a'] ?? 'check');
$show();
if ($a !== 'run') exit;
if (is_file(__DIR__ . '/.logs/zz-follow-done')) { echo "already run\n"; exit; }
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

$sql = (string) file_get_contents(__DIR__ . '/collector_update.sql');
$sql = preg_replace('/^\s*--.*$/m', '', $sql);
$n = 0;
foreach (array_filter(array_map('trim', explode(';', $sql))) as $st) {
    if (!preg_match('/^CREATE TABLE IF NOT EXISTS `artist_follows`/i', $st)) { echo "skipped an unexpected statement, nothing else run\n"; exit; }
    $db->exec($st); $n++;
}
echo "ran $n add-only statements\n";
owSchemaForget();
@file_put_contents(__DIR__ . '/.logs/zz-follow-done', gmdate('c'));
echo "after:\n"; $show();
