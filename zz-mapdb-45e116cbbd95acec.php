<?php
/* TEMPORARY (owner, 14 Nov 2026): the Art Map's map. Adds institutes.lat / lng (institutes_coords.sql, add-only) and saves
   the coordinates found on free sources (OpenStreetMap, Wikimedia Commons) for places that have none yet. Full backup first.
   Locked by a secret key, runs once. Removed straight after. */
if (!hash_equals('314393a9019f180153d6ffb95ffe1cfc7c1d98773d48bf884f6eac73f19ca920', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
@set_time_limit(300);
$db = getDB();
$p = json_decode(base64_decode('eyJzcWwiOiAiLS0gPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09XG4tLSAgaW5zdGl0dXRlc19jb29yZHMuc3FsIFx1MjAxNCBtYXAgY29vcmRpbmF0ZXMgZm9yIHRoZSBvbGRlciBpbnN0aXR1dGVzIGxpc3QgKG93bmVyLCAxNCBOb3YgMjAyNikuXG4tLSAgT25seSBhZGRzIHR3byBjb2x1bW5zOyBzYWZlIHRvIHJ1biBtb3JlIHRoYW4gb25jZS4gVGhlIEFydCBNYXAgcHV0cyBhIHBpbiBvbiB0aGUgbWFwXG4tLSAgZm9yIGV2ZXJ5IGluc3RpdHV0ZSB0aGF0IGhhcyBib3RoLiBTYW1lIHR5cGUgYXMgYXJ0X3BsYWNlcy5sYXQgLyBsbmcuXG4tLSA9PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT1cbkFMVEVSIFRBQkxFIGBpbnN0aXR1dGVzYCBBREQgQ09MVU1OIElGIE5PVCBFWElTVFMgYGxhdGAgREVDSU1BTCg5LDYpIE5VTEwgREVGQVVMVCBOVUxMO1xuQUxURVIgVEFCTEUgYGluc3RpdHV0ZXNgIEFERCBDT0xVTU4gSUYgTk9UIEVYSVNUUyBgbG5nYCBERUNJTUFMKDksNikgTlVMTCBERUZBVUxUIE5VTEw7XG4iLCAicm93cyI6IFt7ImtpbmQiOiAicCIsICJpZCI6IDEsICJsYXQiOiAyNC42NDcxNDIsICJsbmciOiA0Ni43MTA3MDR9LCB7ImtpbmQiOiAicCIsICJpZCI6IDQsICJsYXQiOiAyMS40ODI5NjEsICJsbmciOiAzOS4xODc0MzF9LCB7ImtpbmQiOiAicCIsICJpZCI6IDIwLCAibGF0IjogMTguMjEzMTM4LCAibG5nIjogNDIuNDk1NTU3fSwgeyJraW5kIjogInAiLCAiaWQiOiAzMSwgImxhdCI6IDI0Ljc1Mjc5MSwgImxuZyI6IDQ2LjczNzA3OH0sIHsia2luZCI6ICJwIiwgImlkIjogNDYsICJsYXQiOiAyNC42NjM4NTgsICJsbmciOiA0Ni42OTAyMzF9LCB7ImtpbmQiOiAicCIsICJpZCI6IDUyLCAibGF0IjogMjEuNDg0OTk0LCAibG5nIjogMzkuMTg4ODQ0fSwgeyJraW5kIjogInAiLCAiaWQiOiA1MywgImxhdCI6IDIxLjQ4Mzk2NCwgImxuZyI6IDM5LjE4NzY2OH0sIHsia2luZCI6ICJwIiwgImlkIjogNTQsICJsYXQiOiAyMS40OTQ3OTMsICJsbmciOiAzOS4xODMyNDV9LCB7ImtpbmQiOiAicCIsICJpZCI6IDU2LCAibGF0IjogMjEuNDE3OTc2LCAibG5nIjogMzkuODI1NTM5fSwgeyJraW5kIjogInAiLCAiaWQiOiA1NywgImxhdCI6IDIxLjQzMzgzNCwgImxuZyI6IDM5Ljc1NDM2OH0sIHsia2luZCI6ICJwIiwgImlkIjogNTgsICJsYXQiOiAyMS40NTY0NjQsICJsbmciOiAzOS44Njk1OTR9LCB7ImtpbmQiOiAicCIsICJpZCI6IDYwLCAibGF0IjogMjEuNDU3MTMxLCAibG5nIjogMzkuODY5MjA4fSwgeyJraW5kIjogInAiLCAiaWQiOiA2MywgImxhdCI6IDIxLjI4NTg5OCwgImxuZyI6IDQwLjQxNTA4M30sIHsia2luZCI6ICJwIiwgImlkIjogODQsICJsYXQiOiAyNS4zNzQwOTEsICJsbmciOiA0OS41ODc4NH0sIHsia2luZCI6ICJwIiwgImlkIjogODYsICJsYXQiOiAyNS4zNzg4NzksICJsbmciOiA0OS41ODY3NjN9LCB7ImtpbmQiOiAicCIsICJpZCI6IDg4LCAibGF0IjogMjYuMTY3MDczLCAibG5nIjogNTAuMTQ1MjM3fSwgeyJraW5kIjogInAiLCAiaWQiOiA5NywgImxhdCI6IDI0LjQ2Mzc2NCwgImxuZyI6IDM5LjYxMDk4OH0sIHsia2luZCI6ICJwIiwgImlkIjogOTgsICJsYXQiOiAyNC40NzY2NzMsICJsbmciOiAzOS42ODc2ODh9LCB7ImtpbmQiOiAicCIsICJpZCI6IDEwMSwgImxhdCI6IDI0LjQ2NTg4NiwgImxuZyI6IDM5LjYwODY4fSwgeyJraW5kIjogInAiLCAiaWQiOiAxMDQsICJsYXQiOiAyNi43NDMxNDYsICJsbmciOiAzNy44NjUyNzh9LCB7ImtpbmQiOiAicCIsICJpZCI6IDEwOCwgImxhdCI6IDI2LjM1MDI0NywgImxuZyI6IDQzLjk1NzYxNH0sIHsia2luZCI6ICJpIiwgImlkIjogMiwgImxhdCI6IDI2LjMzNTQxMSwgImxuZyI6IDUwLjEyMDU3OX0sIHsia2luZCI6ICJpIiwgImlkIjogNiwgImxhdCI6IDI0LjcyNzkzNSwgImxuZyI6IDQ2LjYzMzM2MX0sIHsia2luZCI6ICJwIiwgImlkIjogMywgImxhdCI6IDIxLjQ4MjI1OSwgImxuZyI6IDM5LjE4NDQzN31dfQ=='), true);
$has = (int) $db->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'institutes' AND COLUMN_NAME IN ('lat','lng')")->fetchColumn();
echo "institutes lat/lng columns: $has of 2\n";
echo 'art_places published with coordinates now: ', $db->query("SELECT COUNT(*) FROM art_places WHERE status = 'published' AND lat IS NOT NULL")->fetchColumn(), "\n";
echo 'coordinates to save: ', count($p['rows']), "\n";
if (($_GET['a'] ?? '') !== 'run') exit;
if (is_file(__DIR__ . '/.logs/zz-mapdb-done')) { echo "already run\n"; exit; }
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

foreach (array_filter(array_map('trim', explode(';', preg_replace('/^\s*--.*$/m', '', $p['sql'])))) as $q) $db->exec($q);
@unlink(__DIR__ . '/.logs/schema_cache.json');
$n = 0;
foreach ($p['rows'] as $r) {
    $t = $r['kind'] === 'i' ? 'institutes' : 'art_places';
    $st = $db->prepare("UPDATE `$t` SET lat = :la, lng = :lo WHERE id = :id AND lat IS NULL");
    $st->execute([':la' => $r['lat'], ':lo' => $r['lng'], ':id' => (int) $r['id']]);
    $n += $st->rowCount();
}
@file_put_contents(__DIR__ . '/.logs/zz-mapdb-done', gmdate('c'));
echo "done: columns added, $n places got coordinates\n";
echo 'art_places with coordinates now: ', $db->query("SELECT COUNT(*) FROM art_places WHERE status = 'published' AND lat IS NOT NULL")->fetchColumn(), "; institutes: ", $db->query("SELECT COUNT(*) FROM institutes WHERE is_active = 1 AND lat IS NOT NULL")->fetchColumn(), "\n";
