<?php
/* TEMPORARY (owner, 12 Nov 2026): add free-licence photos to Art Map places that have none.
   Read-only without a=run; with a=run makes a full backup, then fills photo_path / photo_credit / photo_page / photo_license
   only where photo_path is still empty. Locked by a secret key, runs once. Removed straight after. */
if (!hash_equals('9666b0ee4bca4c83a479bc3416830cc98256c12fae0fb26b5c259170998c0e18', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
@set_time_limit(300);
$db = getDB();
$rowsIn = [
    ['old-pe91334a854', 'assets/img/places/old-pe91334a854', 'Kolaiel, CC0, Wikimedia Commons', 'https://commons.wikimedia.org/wiki/File:Red_Sea_Museum_Jeddah_Saudi_Arabia_043.jpg', 'CC0'],
    ['imp-41', 'assets/img/places/imp-41', 'Sammy Six, CC BY 2.0, Flickr', 'https://www.flickr.com/photos/sammysix/6573572949/', 'CC BY 2.0'],
    ['imp-45', 'assets/img/places/imp-45', 'Ali Lajami, CC BY 2.0, Flickr', 'https://www.flickr.com/photos/199903594@N07/53782829763/', 'CC BY 2.0'],
    ['imp-82', 'assets/img/places/imp-82', 'xiquinhosilva, CC BY 4.0, Flickr', 'https://www.flickr.com/photos/xiquinho/54872237080/', 'CC BY 4.0'],
    ['imp-96', 'assets/img/places/imp-96', 'Abuhamzah 28, CC BY-SA 4.0, Wikimedia Commons', 'https://commons.wikimedia.org/wiki/File:Dar_al-Madinah_Museum_(Medina)_-_Facade.jpg', 'CC BY-SA 4.0'],
    ['imp-104', 'assets/img/places/imp-104', 'Ali Lajami, Public domain, Flickr', 'https://www.flickr.com/photos/199903594@N07/53521942135/', 'Public domain'],
    ['imp-118', 'assets/img/places/imp-118', 'Richard Mortel, CC BY 2.0, Flickr', 'https://www.flickr.com/photos/prof_richard/50224154903/', 'CC BY 2.0']
];
$show = function () use ($db) {
    $n = 0; foreach ($db->query("SELECT status, COUNT(*) c, SUM(photo_path <> '') p FROM art_places GROUP BY status") as $r) echo $r['status'], ': ', $r['c'], ' places, ', $r['p'], " with photo\n";
};
if (($_GET['a'] ?? '') !== 'run') { $show(); foreach ($rowsIn as $x) { $st = $db->prepare('SELECT name_en, status, photo_path FROM art_places WHERE src_ref = ?'); $st->execute([$x[0]]); $r = $st->fetch(PDO::FETCH_ASSOC); echo $x[0], ' | ', $r ? $r['name_en'] . ' | ' . $r['status'] . ' | photo now: ' . ($r['photo_path'] ?: '-') : 'NOT FOUND', ' | file ', is_file(__DIR__ . '/' . $x[1] . '.jpg') ? 'ok' : 'MISSING', "\n"; } exit; }
if (is_file(__DIR__ . '/.logs/zz-photos-done')) { echo "already run\n"; $show(); exit; }
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

$st = $db->prepare("UPDATE art_places SET photo_path = ?, photo_credit = ?, photo_page = ?, photo_license = ? WHERE src_ref = ? AND (photo_path IS NULL OR photo_path = '')");
$done = 0;
foreach ($rowsIn as $x) { if (!is_file(__DIR__ . '/' . $x[1] . '.jpg')) { echo "skip (no file) ", $x[0], "\n"; continue; } $st->execute([$x[1], $x[2], $x[3], $x[4], $x[0]]); $done += $st->rowCount(); }
@file_put_contents(__DIR__ . '/.logs/zz-photos-done', gmdate('c'));
echo "photos added: $done\n"; $show();
