<?php
/* TEMPORARY (owner, 11 Nov 2026): full backup, then delete 5 drafts that are not about painting: the event drafts
   Venice Biennale, Ithra Design Week, Tuwaiq Sculpture and In Praise of the Artisan, and the hidden institute
   Diriyah Art Futures. Only while they are still drafts / hidden. Locked by a secret key, runs once. Removed straight after. */
if (!hash_equals('580b1382416d11d44cf7cc17fc21b3ad7700db39084c0f36f3c5d41e7c692e05', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
@set_time_limit(300);
$db = getDB();
$dbName = (string) $db->query('SELECT DATABASE()')->fetchColumn();
$SLUGS = ['venice-biennale-2026-in-minor-keys-final-days', 'ithra-design-week-2026', 'tuwaiq-sculpture-2027', 'in-praise-of-the-artisan-at-ithra'];
$INST = 'Diriyah Art Futures';
$show = function () use ($db) {
    echo "\n== event articles\n"; foreach ($db->query("SELECT id, slug, status FROM articles WHERE type = 'event' ORDER BY id") as $r) echo implode(' | ', array_map('strval', $r)), "\n";
    echo "\n== institutes\n"; foreach ($db->query('SELECT id, name_en, is_active FROM institutes ORDER BY id') as $r) echo implode(' | ', array_map('strval', $r)), "\n";
};
if (($_GET['a'] ?? '') !== 'run') { echo "ready, database $dbName\n"; $show(); exit; }
if (is_file(__DIR__ . '/.logs/zz-drafts5-done')) { echo "already run\n"; $show(); exit; }

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

$in = implode(',', array_fill(0, count($SLUGS), '?'));
$ids = $db->prepare("SELECT id FROM articles WHERE type = 'event' AND status = 'draft' AND slug IN ($in)"); $ids->execute($SLUGS); $ids = $ids->fetchAll(PDO::FETCH_COLUMN);
foreach ($ids as $id) { $db->prepare('DELETE FROM article_images WHERE article_id = ?')->execute([$id]); $db->prepare("DELETE FROM articles WHERE id = ? AND status = 'draft'")->execute([$id]); }
echo "event drafts deleted: ", count($ids), "\n";
$st = $db->prepare('DELETE FROM institutes WHERE name_en = ? AND is_active = 0 AND NOT EXISTS (SELECT 1 FROM courses c WHERE c.institute_id = institutes.id)'); $st->execute([$INST]);
echo "hidden institute deleted: ", $st->rowCount(), "\n";
@file_put_contents(__DIR__ . '/.logs/zz-drafts5-done', gmdate('c'));
$show();
