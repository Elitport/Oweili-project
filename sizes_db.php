<?php
/* =====================================================================
 *  sizes_db.php — TEMPORARY. Removed straight after use (5 Nov 2026).
 *  Adds the standard sizes (sizes_update.sql; approved by the owner: only
 *  adds columns) and maps every existing artwork to its nearest standard size.
 *    status  read only: do the columns exist, row counts
 *    backup  full database backup into .backups/
 *    create  runs sizes_update.sql (only after a backup from the last hour)
 *    preview read only: the list of artworks and the size each would get
 *    map     saves those sizes (only artworks with a width and height and no size yet;
 *            width and height themselves are not changed)
 *  Only with the one-time key (only its SHA-256 is stored here). POST only.
 * ===================================================================== */
const OW_AP_UNTIL    = 1793903077;
const OW_AP_KEY_HASH = '5609acfa293c9b9926af8b20efa18e97596e976034b01c2f2685531b727b22ce';
if (time() > OW_AP_UNTIL) { http_response_code(404); exit; }
require_once 'config.php';
$keyOk = is_string($_POST['key'] ?? null) && hash_equals(OW_AP_KEY_HASH, hash('sha256', (string) $_POST['key']));
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !$keyOk) { http_response_code(404); exit; }
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');
@set_time_limit(300);
$db = getDB();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$name = (string) $db->query('SELECT DATABASE()')->fetchColumn();
$dir  = __DIR__ . '/.backups';
$say  = function (string $s) { echo $s, "\n"; };
function dbuTables(PDO $db): array {
    return $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
}
function dbuCounts(PDO $db): array {
    $out = [];
    foreach (dbuTables($db) as $t) $out[$t] = (int) $db->query('SELECT COUNT(*) FROM `' . str_replace('`', '``', $t) . '`')->fetchColumn();
    return $out;
}
function dbuColumn(PDO $db, string $table, string $col): ?array {
    $st = $db->prepare('SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $st->execute([$table, $col]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}
function dbuHasTable(PDO $db, string $t): bool {
    $st = $db->prepare('SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $st->execute([$t]); return (bool) $st->fetchColumn();
}
function dbuHasIndex(PDO $db, string $t, string $idx): bool {
    $st = $db->prepare('SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
    $st->execute([$t, $idx]); return (bool) $st->fetchColumn();
}




$step = (string) ($_POST['step'] ?? 'status');
$say("Database: $name   Step: $step   Time: " . gmdate('Y-m-d H:i:s') . ' UTC');
if ($step === 'status') {
    foreach (['artworks', 'commissions', 'gift_requests'] as $t) foreach (['size_code', 'size_category', 'orientation'] as $c) $say("$t.$c: " . (dbuColumn($db, $t, $c)['COLUMN_TYPE'] ?? 'missing'));
    foreach (dbuCounts($db) as $t => $n) $say(sprintf('  %-28s %8d rows', $t, $n));
    exit;
}
/* ---------------- backup: full dump of every table ---------------- */
if ($step === 'backup') {
    if (!is_dir($dir) && !@mkdir($dir, 0700, true)) { $say('FAILED at backup: could not make the .backups folder'); exit; }
    @file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\n");
    @file_put_contents($dir . '/index.html', '');
    $file = $dir . '/db-' . $name . '-' . gmdate('Ymd-His') . '.sql.gz';
    $gz = @gzopen($file, 'wb6');
    if (!$gz) { $say('FAILED at backup: could not write the backup file'); exit; }
    gzwrite($gz, "-- Oweili database backup of `$name`, " . gmdate('Y-m-d H:i:s') . " UTC\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");
    $written = [];
    foreach (dbuTables($db) as $t) {
        $q = '`' . str_replace('`', '``', $t) . '`';
        $create = $db->query("SHOW CREATE TABLE $q")->fetch(PDO::FETCH_NUM)[1];
        gzwrite($gz, "DROP TABLE IF EXISTS $q;\n$create;\n");
        $n = 0;
        $rows = $db->query("SELECT * FROM $q", PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $vals = array_map(fn($v) => $v === null ? 'NULL' : $db->quote((string) $v), array_values($row));
            gzwrite($gz, "INSERT INTO $q VALUES (" . implode(',', $vals) . ");\n");
            $n++;
        }
        gzwrite($gz, "\n");
        $written[$t] = $n;
    }
    gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n-- end of backup\n");
    gzclose($gz);
    /* check: the file reads back, ends properly, and has every row */
    $check = dbuCounts($db);
    $txt   = (string) @file_get_contents('compress.zlib://' . $file);
    $okEnd = str_ends_with(rtrim($txt), '-- end of backup');
    $bad   = array_keys(array_filter($check, fn($n, $t) => ($written[$t] ?? -1) !== $n, ARRAY_FILTER_USE_BOTH));
    foreach ($written as $t => $n) $say(sprintf('  %-28s %8d rows saved', $t, $n));
    if (!$okEnd || $bad) { $say('FAILED at backup check: ' . (!$okEnd ? 'file is incomplete ' : '') . ($bad ? 'row counts differ in ' . implode(', ', $bad) : '')); exit; }
    @chmod($file, 0600);
    $say('BACKUP OK: .backups/' . basename($file) . ' (' . filesize($file) . ' bytes, ' . count($written) . ' tables, sha256 ' . hash_file('sha256', $file) . ')');
    exit;
}




if ($step === 'create') {
    $recent = array_filter(glob($dir . '/*.sql.gz') ?: [], fn($f) => filemtime($f) > time() - 3600);
    if (!$recent) { $say('STOPPED: no backup from the last hour. Run the backup step first.'); exit; }
    $before = dbuCounts($db);
    $sql = (string) file_get_contents(__DIR__ . '/sizes_update.sql');
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) $db->exec($stmt);
    $after = dbuCounts($db);
    $changed = array_keys(array_filter($before, fn($n, $t) => ($after[$t] ?? -1) !== $n, ARRAY_FILTER_USE_BOTH));
    foreach (['artworks', 'commissions', 'gift_requests'] as $t) foreach (['size_code', 'size_category', 'orientation'] as $c) $say("$t.$c: " . (dbuColumn($db, $t, $c)['COLUMN_TYPE'] ?? 'MISSING'));
    $say('index idx_artworks_size: ' . (dbuHasIndex($db, 'artworks', 'idx_artworks_size') ? 'yes' : 'MISSING'));
    $say('tables with changed row counts: ' . ($changed ? implode(', ', $changed) : 'none'));
    if (is_file(__DIR__ . '/.logs/schema_cache.json')) @unlink(__DIR__ . '/.logs/schema_cache.json');   // pages see the new columns at once
    $say('CREATE OK');
    exit;
}
if ($step === 'preview' || $step === 'map') {
    if (!dbuColumn($db, 'artworks', 'size_code')) { $say('STOPPED: run the create step first.'); exit; }
    require_once __DIR__ . '/size_lib.php';
    $rows = $db->query('SELECT id, title_en, width_cm, height_cm, size_code, orientation FROM artworks ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    $say(count($rows) . ' artworks in the database');
    $fmt = fn($x) => rtrim(rtrim(number_format($x, 1, '.', ''), '0'), '.');
    $n = 0; $set = $db->prepare('UPDATE artworks SET size_code = :c, size_category = :g, orientation = :o WHERE id = :id AND size_code IS NULL');
    foreach ($rows as $r) {
        $w = (float) $r['width_cm']; $h = (float) $r['height_cm'];
        $t = mb_substr((string) $r['title_en'], 0, 40);
        if ($r['size_code'] !== null && $r['size_code'] !== '') { $say("  #{$r['id']} $t: already {$r['size_code']} {$r['orientation']}"); continue; }
        if ($w <= 0 || $h <= 0) { $say("  #{$r['id']} $t: no width and height yet (the artist chooses a size when editing)"); continue; }
        $c = owSizeNearest($w, $h);
        $say(sprintf('  #%d %s: %s x %s cm -> %s (%s, %s)', $r['id'], $t, $fmt($w), $fmt($h), $c[0], owSizeGroups()[$c[1]][0], $c[2]));
        if ($step === 'map') { $set->execute([':c' => $c[0], ':g' => $c[1], ':o' => $c[2], ':id' => (int) $r['id']]); $n += $set->rowCount(); }
    }
    $say($step === 'map' ? "MAP OK: $n artworks saved" : 'PREVIEW (nothing saved)');
    exit;
}
$say('Unknown step');
