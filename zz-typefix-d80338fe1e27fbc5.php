<?php
/* TEMPORARY (owner, 9 Nov 2026): full database backup into .backups/, then one column change: artworks.type from the old four-word list to plain text (the same line as reset_setup.sql). Nothing deleted.
   Locked by a secret key. Removed straight after. */
if (!hash_equals('7e7fa797f7f3990b73f9a933a312bed3b78ff8f9e7582afd39843dc757de1c57', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
@set_time_limit(300);
$db = getDB();
$dbName = (string) $db->query('SELECT DATABASE()')->fetchColumn();
$showType = function () use ($db) { $c = $db->query("SHOW COLUMNS FROM `artworks` LIKE 'type'")->fetch(); echo 'type column: ', $c['Type'], ' default ', $c['Default'], "\n"; foreach ($db->query('SELECT type, COUNT(*) n FROM artworks GROUP BY type') as $r) echo '  value [', $r['type'], '] ', $r['n'], "\n"; };
$showType();
if (($_GET['a'] ?? '') !== 'run') { echo "ready, database $dbName\n"; exit; }
if (is_file(__DIR__ . '/.logs/typefix_done')) { echo "already done\n"; exit; }

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

/* 2. the one column change (the same line as reset_setup.sql) */
$q = "ALTER TABLE `artworks` MODIFY COLUMN `type` VARCHAR(50) NOT NULL DEFAULT 'other'";
try { $db->exec($q); echo "ok: $q\n"; } catch (Throwable $e) { echo "FAILED: $q :: " . $e->getMessage() . "\n"; exit; }
@file_put_contents(__DIR__ . '/.logs/typefix_done', gmdate('c'));
$cache = __DIR__ . '/.logs/schema_cache.json';
if (is_file($cache)) { @unlink($cache); }
$showType();
