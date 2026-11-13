<?php
/* TEMPORARY (owner, 13 Nov 2026): make a full backup, then delete only the "Is this your place?" test message
   (name TEST Oweili, email contact@oweili.com, message "TEST, please ignore"). Locked by a secret key, runs once. Removed straight after. */
if (!hash_equals('3279469a864f1caa656c1b66a514666964ce681c12d11d05b307ab61b87b8d45', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/artmap_lib.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
@set_time_limit(300);
$db = getDB();
$show = function () use ($db) { foreach ($db->query('SELECT id, place_name, name, status FROM art_place_claims ORDER BY id') as $r) echo $r['id'], ' | ', $r['place_name'], ' | ', $r['name'], ' | ', $r['status'], "\n"; echo 'Waiting count shown in Admin: ', owClaimsWaiting(), "\n"; };
if (($_GET['a'] ?? '') !== 'run') { $show(); exit; }
if (is_file(__DIR__ . '/.logs/zz-deltest-done')) { echo "already run\n"; $show(); exit; }
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
$st = $db->prepare("DELETE FROM art_place_claims WHERE name = 'TEST Oweili' AND email = 'contact@oweili.com' AND message = 'TEST, please ignore' AND photo_file = ''");
$st->execute();
@file_put_contents(__DIR__ . '/.logs/zz-deltest-done', gmdate('c'));
echo 'deleted: ', $st->rowCount(), "\n"; $show();
