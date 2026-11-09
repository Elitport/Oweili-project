<?php
/* TEMPORARY (owner, 9 Nov 2026): full database backup into .backups/, then the add-only fixes in portfolio_update.sql.
   Locked by a secret key. Removed straight after. */
if (!hash_equals('ed57db2300b46f70bbfb98ceffb63ca40c8821f1a06380e105bb7212f2d0a279', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
@set_time_limit(300);
$db = getDB();
$dbName = (string) $db->query('SELECT DATABASE()')->fetchColumn();
if (($_GET['a'] ?? '') !== 'run') { echo "ready, database $dbName\n"; exit; }

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

/* 2. the add-only fixes (the same as portfolio_update.sql) */
$sql = [
    "ALTER TABLE `artworks` ADD COLUMN IF NOT EXISTS `sold_at` TIMESTAMP NULL DEFAULT NULL",
    "ALTER TABLE `portfolio_images` ADD COLUMN IF NOT EXISTS `artist_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `id`",
    "ALTER TABLE `portfolio_images` ADD COLUMN IF NOT EXISTS `sort_order` INT NOT NULL DEFAULT 0",
    "ALTER TABLE `portfolio_images` ADD INDEX IF NOT EXISTS `idx_portfolio_artist` (`artist_id`)",
    "UPDATE `portfolio_images` SET `artist_id` = `user_id` WHERE `artist_id` IS NULL",
];
foreach ($sql as $q) {
    try { $n = $db->exec($q); echo "ok: $q ($n)\n"; }
    catch (Throwable $e) { echo "FAILED: $q :: " . $e->getMessage() . "\n"; exit; }
}
$cache = __DIR__ . '/.logs/schema_cache.json';   // the "does this column exist" answers are asked again
if (is_file($cache)) { @unlink($cache); }
foreach (['portfolio_images', 'artworks'] as $t) {
    echo "== $t: ";
    foreach ($db->query("SHOW COLUMNS FROM `$t`") as $r) { echo $r['Field'], ' '; }
    echo "\n";
}
echo "portfolio rows: ", $db->query('SELECT COUNT(*) FROM portfolio_images')->fetchColumn(), "\n";
