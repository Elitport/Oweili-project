<?php
/* =====================================================================
 *  account_check.php — TEMPORARY. Removed straight after use (31 Oct 2026).
 *  Why the admin overview shows 0 artists while "Azeem" has artworks.
 *    status     read only: every account (role, approval, confirmed, banned,
 *               masked email, name, how many artworks), and every artwork's owner
 *    backup     full database backup into .backups/
 *    fix_artist turns the ONE account that owns Gallery artworks but is not an
 *               artist account into an approved artist account. Only that one
 *               account and only its role / approval change. Nothing is deleted.
 *  Only with the one-time key (only its SHA-256 is stored here). POST only.
 * ===================================================================== */
const OW_AC_UNTIL    = 1793440028;
const OW_AC_KEY_HASH = '412c206ca20dbaa1d19116e6228576cd7aa8a6e71f0d6365502b9a102829f8ab';
if (time() > OW_AC_UNTIL) { http_response_code(404); exit; }
require_once 'config.php';
$keyOk = is_string($_POST['key'] ?? null) && hash_equals(OW_AC_KEY_HASH, hash('sha256', (string) $_POST['key']));
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



$mask = function (string $e): string { $p = explode('@', $e, 2); return (strlen($p[0]) > 2 ? substr($p[0], 0, 2) . str_repeat('*', max(1, strlen($p[0]) - 2)) : $p[0] . '*') . '@' . ($p[1] ?? ''); };
$step = (string) ($_POST['step'] ?? 'status');
$say("Database: $name   Step: $step   Time: " . gmdate('Y-m-d H:i:s') . ' UTC');
$owners = function () use ($db): array {
    return $db->query("SELECT u.id FROM users u WHERE u.role <> 'artist' AND EXISTS (SELECT 1 FROM artworks a WHERE a.artist_id = u.id)")->fetchAll(PDO::FETCH_COLUMN);
};

if ($step === 'status') {
    foreach ($db->query('SELECT id, role, email, full_name_en, full_name_ar, artist_name, is_verified, is_approved, COALESCE(is_banned,0) AS banned,
                         created_at, last_login_at, password FROM users ORDER BY id') as $u) {
        $n = $db->prepare('SELECT COUNT(*) FROM artworks WHERE artist_id = ?'); $n->execute([$u['id']]);
        $pw = (string) $u['password'];
        $kind = $pw === '' ? 'none' : (password_get_info(trim($pw))['algo'] ? 'secure hash' : 'old style');
        $say(sprintf('user #%d role=%s email=%s name=%s / %s / artist_name=%s verified=%d approved=%d banned=%d joined=%s last_sign_in=%s password=%s artworks=%d',
            $u['id'], $u['role'], $mask((string) $u['email']), $u['full_name_en'], $u['full_name_ar'], $u['artist_name'], $u['is_verified'], $u['is_approved'],
            $u['banned'], $u['created_at'], $u['last_login_at'] ?? '-', $kind, (int) $n->fetchColumn()));
    }
    foreach ($db->query('SELECT a.id, a.title_en, a.artist_id, a.is_approved, a.status, u.role FROM artworks a LEFT JOIN users u ON u.id = a.artist_id ORDER BY a.id') as $a)
        $say("artwork #{$a['id']} {$a['title_en']} owner=#{$a['artist_id']} (role " . ($a['role'] ?? 'MISSING ACCOUNT') . ") approved={$a['is_approved']} status={$a['status']}");
    $say('accounts that own artworks but are not artist accounts: ' . json_encode($owners()));
    $say('sessions table rows: ' . (int) $db->query('SELECT COUNT(*) FROM sessions')->fetchColumn());
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



if ($step === 'fix_artist') {
    $recent = array_filter(glob($dir . '/*.sql.gz') ?: [], fn($f) => filemtime($f) > time() - 3600);
    if (!$recent) { $say('STOPPED: no backup from the last hour. Run the backup step first.'); exit; }
    $ids = $owners();
    if (count($ids) !== 1) { $say('STOPPED: expected exactly one such account, found ' . count($ids) . '. Nothing changed.'); exit; }
    $id = (int) $ids[0];
    $before = $db->prepare('SELECT role, is_approved FROM users WHERE id = ?'); $before->execute([$id]); $b = $before->fetch();
    $db->prepare("UPDATE users SET role = 'artist', is_approved = 1 WHERE id = ?")->execute([$id]);
    try { $db->prepare("INSERT INTO activity_log (actor_type, action, target_type, target_id, details) VALUES ('system', 'user_role_fix', 'user', ?, ?)")
             ->execute([$id, 'role ' . $b['role'] . ' -> artist, approved (owner request: owns Gallery artworks)']); } catch (Throwable $e) {}
    $say("FIX OK: user #$id role {$b['role']} -> artist, approved {$b['is_approved']} -> 1. Nothing deleted.");
    exit;
}
$say('Unknown step');
