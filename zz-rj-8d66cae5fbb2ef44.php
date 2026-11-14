<?php
/* TEMPORARY (owner, 14 Nov 2026): rejected or replaced photos. Backs up the old studio photos on the server, finds the old
   Art Education cover copies no page uses any more, backs them up and deletes them; sets "Made With Your Magic" to open on
   5 December 2026. Full database backup first. Locked by a secret key, runs once. Removed straight after. */
if (!hash_equals('c57cd4c9cf92315c063ffb8037dff41b63d899246c0e4e26fb7f6fc7b5cd5587', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
@set_time_limit(300);
$db = getDB();
$used = [];
foreach ($db->query("SELECT cover_image_path FROM articles") as $r) $used[basename((string) reset($r))] = 1;
foreach ($db->query("SELECT image_path FROM article_images") as $r) $used[basename((string) reset($r))] = 1;
$pend = '';
try { foreach ($db->query("SELECT data FROM pending_changes") as $r) $pend .= (string) reset($r); } catch (Throwable $e) {}
$unused = [];
foreach (glob(__DIR__ . "/uploads/articles/*") as $f) {
    $b = basename($f);
    if (!is_file($f) || $b === '' || isset($used[$b]) || strpos($pend, $b) !== false) continue;
    $unused[] = $b;
    echo "not used: uploads/articles/$b (", filesize($f), " bytes, ", gmdate('Y-m-d', filemtime($f)), ")\n";
}
unset($used[""]); echo count($unused), " unused article pictures; ", count($used), " in use\n"; if (($_GET["a"] ?? "") === "run" && count($used) < 5) { echo "stopped: too few pictures in use, something is wrong\n"; exit; }
$st = $db->prepare("SELECT id, title_en, event_start_date, event_end_date, status FROM articles WHERE slug = 'made-with-your-magic-at-hayy-arts' OR title_en LIKE 'Made With Your Magic%'");
$st->execute(); $mw = $st->fetchAll();
foreach ($mw as $r) echo "event #", $r['id'], " ", $r['title_en'], " ", $r['event_start_date'], " to ", $r['event_end_date'], " ", $r['status'], "\n";
$studio = glob(__DIR__ . '/assets/img/studio/{art-book,brushes-jars,gallery-visit,home-painting,paint-buckets,paint-cloth,palette-hand}-*', GLOB_BRACE);
$hero = glob(__DIR__ . '/assets/img/hero/hero-pink-tall.*');
echo count($studio) + count($hero), " old studio / hero photo files still on the server\n";
if (($_GET['a'] ?? '') !== 'run') exit;
if (is_file(__DIR__ . '/.logs/zz-rj-done')) { echo "already run\n"; exit; }
if (count($mw) !== 1) { echo "stopped: Made With Your Magic not found exactly once, nothing changed\n"; exit; }
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

$bdir = __DIR__ . '/.backups/rejected-photos-' . gmdate('Ymd-His');
if (!@mkdir($bdir . '/studio', 0700, true) || !@mkdir($bdir . '/articles', 0700, true)) { echo "backup folder FAILED, files not touched\n"; exit; }
foreach (array_merge($studio, $hero) as $f) { if (!@copy($f, $bdir . '/studio/' . basename($f))) { echo "copy FAILED $f, stopped\n"; exit; } }
foreach ($unused as $b) { if (!@copy(__DIR__ . '/uploads/articles/' . $b, $bdir . '/articles/' . $b)) { echo "copy FAILED $b, stopped\n"; exit; } }
echo "files backed up: .backups/", basename($bdir), " (", count($studio) + count($hero), " studio, ", count($unused), " article pictures)\n";
foreach ($unused as $b) { @unlink(__DIR__ . '/uploads/articles/' . $b); foreach (glob(__DIR__ . '/uploads/thumbs/*' . pathinfo($b, PATHINFO_FILENAME) . '*') as $t) @unlink($t); echo "deleted uploads/articles/$b\n"; }
$db->prepare("UPDATE articles SET event_start_date = '2026-12-05' WHERE id = :id")->execute([':id' => (int) $mw[0]['id']]);
echo "Made With Your Magic now starts 2026-12-05\n";
@file_put_contents(__DIR__ . '/.logs/zz-rj-done', gmdate('c'));
echo "done\n";
