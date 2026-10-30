<?php
/* =====================================================================
 *  cleanup_tool.php — TEMPORARY. Remove it right after use.
 *  One-time clean-up of the live site (owner's request, 31 Oct 2026):
 *    status        read only: old events, articles, test files left behind
 *    backup        full database backup into .backups/ (same as db_update.php)
 *    move_events   copies every old event (events table) into an Art Events
 *                  article (published), once. The old rows are kept.
 *    delete_tests  deletes uploads/deploy_test/ (a test file made on 30 Oct)
 *  Switches itself off at OW_CT_UNTIL. Only an admin signed in on admin.php,
 *  or the one-time key (only its SHA-256 is stored here). POST only.
 *  Never touches real artwork photos.
 * ===================================================================== */
const OW_CT_UNTIL    = 1793417441;
const OW_CT_KEY_HASH = '085fd8b3ad36380f2c2a4bc4ed595a7dd31cd0e131f0fabf03f6fb7f0709ac8c';

if (time() > OW_CT_UNTIL) { http_response_code(404); exit; }
require_once 'config.php';
require_once __DIR__ . '/article_lib.php';
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

$adminHash = defined('ADMIN_HASH') ? (string) ADMIN_HASH : '';
$adminSig  = $adminHash !== '' ? hash('sha256', $adminHash) : '';
$isAdmin   = !empty($_SESSION['is_admin']) && $adminSig !== '' && hash_equals($adminSig, (string) ($_SESSION['admin_sig'] ?? ''));
$keyOk     = is_string($_POST['key'] ?? null) && hash_equals(OW_CT_KEY_HASH, hash('sha256', (string) $_POST['key']));
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || (!$isAdmin && !$keyOk)) { http_response_code(404); exit; }

@set_time_limit(300);
$db   = getDB();
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
$testDir = __DIR__ . '/uploads/deploy_test';

if ($step === 'status') {
    $ev = dbuHasTable($db, 'events') ? (int) $db->query('SELECT COUNT(*) FROM events')->fetchColumn() : -1;
    $say('old events rows: ' . $ev);
    if ($ev > 0) foreach ($db->query('SELECT id, title_en, event_date, city FROM events ORDER BY id') as $r) $say("  #{$r['id']} {$r['title_en']} ({$r['event_date']}, {$r['city']})");
    $say('event articles: ' . (int) $db->query("SELECT COUNT(*) FROM articles WHERE type = 'event'")->fetchColumn());
    $say('uploads/deploy_test: ' . (is_dir($testDir) ? implode(', ', array_map('basename', glob($testDir . '/*') ?: [])) : 'not there'));
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


if ($step === 'move_events') {
    $recent = array_filter(glob($dir . '/*.sql.gz') ?: [], fn($f) => filemtime($f) > time() - 3600);
    if (!$recent) { $say('STOPPED: no backup from the last hour. Run the backup step first.'); exit; }
    if (!dbuHasTable($db, 'events')) { $say('No old events table. Nothing to move.'); exit; }
    $n = 0; $skip = 0;
    foreach ($db->query('SELECT * FROM events ORDER BY id')->fetchAll() as $e) {
        $tEn = trim((string) $e['title_en']) ?: trim((string) $e['title_ar']);
        $tAr = trim((string) $e['title_ar']) ?: $tEn;
        if ($tEn === '') { $skip++; continue; }
        $dup = $db->prepare("SELECT 1 FROM articles WHERE type = 'event' AND title_en = :t AND (event_start_date <=> :d) LIMIT 1");
        $dup->execute([':t' => $tEn, ':d' => $e['event_date'] ?: null]);
        if ($dup->fetchColumn()) { $skip++; $say("  skipped (already an article): $tEn"); continue; }
        $extraEn = ''; $extraAr = '';
        if ((string) $e['contact_link'] !== '') { $extraEn .= "\n\nMore details: " . $e['contact_link']; $extraAr .= "\n\nالتفاصيل: " . $e['contact_link']; }
        $extraEn .= "\n\n" . ((int) $e['is_free'] === 1 ? 'Entry is free.' : 'Entry is paid.');
        $extraAr .= "\n\n" . ((int) $e['is_free'] === 1 ? 'الدخول مجاني.' : 'الدخول مدفوع.');
        $cover = (string) $e['image_url'];
        if (!owSafeArticlePath($cover)) $cover = '';
        $db->prepare("INSERT INTO articles (type, slug, title_en, title_ar, body_en, body_ar, cover_image_path, status, published_at,
                                            event_start_date, event_location_en, event_location_ar)
                      VALUES ('event', :slug, :ten, :tar, :ben, :bar, :cover, 'published', NOW(), :d, :loc, :loc2)")
           ->execute([':slug' => owUniqueSlug($tEn), ':ten' => $tEn, ':tar' => $tAr,
                      ':ben' => trim((string) ($e['description_en'] ?: $e['description_ar'])) . $extraEn,
                      ':bar' => trim((string) ($e['description_ar'] ?: $e['description_en'])) . $extraAr,
                      ':cover' => $cover, ':d' => $e['event_date'] ?: null, ':loc' => (string) $e['city'], ':loc2' => (string) $e['city']]);
        $n++; $say("  moved: $tEn");
    }
    $say("MOVE OK: $n moved, $skip skipped. The old events rows are kept.");
    exit;
}

if ($step === 'delete_tests') {
    if (!is_dir($testDir)) { $say('uploads/deploy_test is already gone.'); exit; }
    foreach (glob($testDir . '/{,.}[!.]*', GLOB_BRACE) ?: [] as $f) { if (is_file($f)) { @unlink($f); $say('  deleted ' . basename($f)); } }
    @rmdir($testDir);
    $say(is_dir($testDir) ? 'FAILED: the folder is still there' : 'DELETE OK: uploads/deploy_test removed');
    exit;
}
$say('Unknown step');
