<?php
/* =====================================================================
 *  db_update.php — TEMPORARY page that backs up the database and then
 *  runs dashboards_update.sql safely (approved by the owner, 30 Oct 2026).
 *  Remove it right after use.
 *  - Switches itself off at the time in OW_DBU_UNTIL (about 5 hours after it was made)
 *  - Only for an admin signed in on admin.php, or with the one-time key whose
 *    SHA-256 is OW_DBU_KEY_HASH (the key itself is never stored anywhere here)
 *  - POST only. Steps: status (read only), backup, update, verify
 *  - Only adds columns, tables and indexes (every statement uses IF NOT
 *    EXISTS), so it is safe to run twice. Stops at the first failure.
 *  - The backup goes to .backups/ next to this file: hidden from visitors
 *    (.htaccess blocks every dot folder, plus its own deny file) and kept by
 *    deploys (.gitignore)
 * ===================================================================== */
const OW_DBU_UNTIL    = 1793417991;
const OW_DBU_KEY_HASH = '7b79052dd64af312dc5d6d93ce758830ce3f2c44335508ba6ffcd503a4d9482a';

if (time() > OW_DBU_UNTIL) { http_response_code(404); exit; }
require_once 'config.php';
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

$adminHash = defined('ADMIN_HASH') ? (string) ADMIN_HASH : '';
$adminSig  = $adminHash !== '' ? hash('sha256', $adminHash) : '';
$isAdmin   = !empty($_SESSION['is_admin']) && $adminSig !== '' && hash_equals($adminSig, (string) ($_SESSION['admin_sig'] ?? ''));
$keyOk     = is_string($_POST['key'] ?? null) && hash_equals(OW_DBU_KEY_HASH, hash('sha256', (string) $_POST['key']));
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
$say("Database: $name   Server: " . $db->getAttribute(PDO::ATTR_SERVER_VERSION) . "   Step: $step   Time: " . gmdate('Y-m-d H:i:s') . ' UTC');

/* ---------------- status / verify: read only ---------------- */
if ($step === 'status' || $step === 'verify') {
    foreach (dbuCounts($db) as $t => $n) $say(sprintf('  %-28s %8d rows', $t, $n));
    foreach (['users', 'artworks', 'artwork_inquiries', 'purchases'] as $t) {
        $have = array_filter(['public_id', 'updated_at', 'country', 'preferred_lang', 'rejected_reason', 'view_count', 'buyer_id', 'status', 'admin_notes'],
                             fn($c) => dbuColumn($db, $t, $c) !== null);
        $say("$t new columns present: " . ($have ? implode(', ', $have) : 'none'));
    }
    foreach (['favorites', 'activity_log', 'shipping_addresses', 'payouts'] as $t) $say("table $t: " . (dbuHasTable($db, $t) ? 'present' : 'missing'));
    $r = dbuColumn($db, 'users', 'role');      $say('users.role: ' . ($r['COLUMN_TYPE'] ?? 'missing') . ' default ' . var_export($r['COLUMN_DEFAULT'] ?? null, true));
    $say('users.id: '    . (dbuColumn($db, 'users', 'id')['COLUMN_TYPE'] ?? 'missing'));
    $say('artworks.id: ' . (dbuColumn($db, 'artworks', 'id')['COLUMN_TYPE'] ?? 'missing'));
    $say('artworks.is_draft: ' . (dbuColumn($db, 'artworks', 'is_draft')['COLUMN_TYPE'] ?? 'missing'));
    $roles = $db->query('SELECT role, COUNT(*) FROM users GROUP BY role')->fetchAll(PDO::FETCH_KEY_PAIR);
    $say('roles in use: ' . json_encode($roles));
    $say('backups: ' . (is_dir($dir) ? implode(', ', array_map(fn($f) => basename($f) . ' (' . filesize($f) . ' bytes)', glob($dir . '/*.sql.gz') ?: [])) : 'none yet'));
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

/* ---------------- update: dashboards_update.sql, statement by statement ---------------- */
if ($step === 'update') {
    $recent = array_filter(glob($dir . '/*.sql.gz') ?: [], fn($f) => filemtime($f) > time() - 3600);
    if (!$recent) { $say('STOPPED: no backup from the last hour. Run the backup step first.'); exit; }
    $sqlFile = __DIR__ . '/dashboards_update.sql';
    if (!is_file($sqlFile)) { $say('STOPPED: dashboards_update.sql not found'); exit; }
    /* comments out, then one statement per ";" at a line end */
    $lines = array_map(fn($l) => preg_replace('/\s--\s.*$|^\s*--.*$/', '', $l), explode("\n", (string) file_get_contents($sqlFile)));
    $stmts = array_values(array_filter(array_map('trim', preg_split('/;\s*$/m', implode("\n", $lines))), 'strlen'));
    $before = dbuCounts($db);
    $n = 0;
    foreach ($stmts as $sql) {
        $n++;
        $label = preg_replace('/\s+/', ' ', substr($sql, 0, 90));
        try { $db->exec($sql); $say("Step $n  $label ... done"); }
        catch (Throwable $e) {
            $say("FAILED at step $n  $label: " . $e->getMessage());
            $say('Nothing after this step was run. Existing data was not touched. The backup is in .backups/.');
            exit;
        }
    }
    $after = dbuCounts($db);
    $changed = [];
    foreach ($before as $t => $c) if (($after[$t] ?? -1) !== $c) $changed[] = "$t: $c -> " . ($after[$t] ?? 'gone');
    $say($changed ? 'WARNING row counts changed: ' . implode('; ', $changed) : 'Existing data unchanged: every existing table has the same number of rows (' . count($before) . ' tables)');
    foreach (['users', 'artworks', 'artwork_inquiries', 'purchases'] as $t) {
        $missing = (int) $db->query("SELECT COUNT(*) FROM `$t` WHERE public_id IS NULL OR public_id = ''")->fetchColumn();
        $say("$t rows without a permanent ID: $missing");
    }
    $say('UPDATE OK');
    exit;
}
/* ---------------- files: read only — what is in uploads/ (names, sizes, dates) ---------------- */
if ($step === 'files') {
    $base = __DIR__ . '/uploads';
    $say('uploads/ exists: ' . (is_dir($base) ? 'yes, made ' . gmdate('Y-m-d H:i', (int) filectime($base)) . ' UTC' : 'no'));
    if (is_dir($base)) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
        $rows = [];
        foreach ($it as $f) {
            if (!$f->isFile()) continue;
            $rel = substr($f->getPathname(), strlen(__DIR__) + 1);
            if (strpos($rel, 'uploads/consents/') === 0) { $rel = 'uploads/consents/(private file)'; }
            $rows[] = [$f->getMTime(), $rel, $f->getSize()];
        }
        usort($rows, fn($a, $b) => $b[0] <=> $a[0]);
        $say(count($rows) . ' files in uploads/ (newest first, up to 60):');
        foreach (array_slice($rows, 0, 60) as [$t, $r, $z]) $say('  ' . gmdate('Y-m-d H:i', $t) . ' UTC  ' . str_pad((string) $z, 9, ' ', STR_PAD_LEFT) . '  ' . $r);
    }
    foreach ($db->query('SELECT id, image_url, created_at FROM artworks ORDER BY id') as $a) {
        $rel = ltrim((string) $a['image_url'], '/');
        $say("artwork {$a['id']} (saved {$a['created_at']}): $rel -> " . ($rel !== '' && is_file(__DIR__ . '/' . $rel) ? 'file present' : 'FILE MISSING'));
    }
    exit;
}

/* ---------------- cleanup_test: removes only the live test buyer made for the Phase 1 check ---------------- */
if ($step === 'cleanup_test') {
    $email = 'contact+oweili-qa@elitport.com';
    $st = $db->prepare('SELECT id FROM users WHERE email = ? AND role = ?');
    $st->execute([$email, 'collector']);
    $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    if (count($ids) !== 1) { $say('Nothing removed: found ' . count($ids) . ' test accounts'); exit; }
    $id = $ids[0];
    foreach (['DELETE FROM favorites WHERE user_id = ?', 'DELETE FROM artwork_inquiries WHERE buyer_id = ? OR buyer_email = ' . $db->quote($email),
              "DELETE FROM activity_log WHERE actor_id = ? AND actor_type = 'buyer'", 'DELETE FROM sessions WHERE user_id = ?',
              'DELETE FROM login_attempts WHERE email = ' . $db->quote($email) . ' AND ? > 0', 'DELETE FROM users WHERE id = ?'] as $sql) {
        $d = $db->prepare($sql); $d->execute([$id]);
        $say(preg_replace('/\s+/', ' ', substr($sql, 0, 40)) . '... ' . $d->rowCount() . ' rows');
    }
    $say('CLEANUP OK');
    exit;
}
$say('Unknown step');
