<?php
/* TEMPORARY, READ ONLY (owner, 14 Nov 2026): which artists' public name (artist_name) was replaced by their full name
   through Edit Profile. Reads the users table, profile changes, the activity log and the server's own backups.
   Changes nothing. Locked by a secret key. Removed straight after. */
if (!hash_equals('ed9b45f56b33e5ec270de04816c30806bc2ec37138fdeac8b22f0d01610c14f3', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
@set_time_limit(120);
$db = getDB();
echo "== artists now\n";
foreach ($db->query("SELECT id, is_approved, artist_name, full_name_en, full_name_ar, created_at FROM users WHERE role = 'artist' ORDER BY id") as $u) {
    echo $u['id'], ' | approved ', $u['is_approved'], ' | public: [', $u['artist_name'], '] | full en: [', $u['full_name_en'], '] | full ar: [', $u['full_name_ar'], '] | public = full: ',
        (trim((string) $u['artist_name']) === trim((string) $u['full_name_en']) ? 'YES' : 'no'), ' | joined ', $u['created_at'], "\n";
}
echo "\n== profile saves in the activity log\n";
try {
    foreach ($db->query("SELECT target_id, details, created_at FROM activity_log WHERE action = 'profile_update' ORDER BY id") as $r) echo 'user ', $r['target_id'], ' | ', $r['created_at'], ' | ', $r['details'], "\n";
} catch (Throwable $e) { echo 'log: ', $e->getMessage(), "\n"; }
echo "\n== profile changes (waiting or decided) that carry a public name\n";
try {
    foreach ($db->query("SELECT id, target_id, status, data, created_at FROM pending_changes WHERE target_type = 'profile' ORDER BY id") as $r) {
        $d = json_decode((string) $r['data'], true) ?: [];
        if (array_key_exists('artist_name', $d)) echo '#', $r['id'], ' user ', $r['target_id'], ' | ', $r['status'], ' | ', $r['created_at'], ' | public name asked: [', $d['artist_name'], ']',
            (array_key_exists('full_name_en', $d) ? ' | full en asked: [' . $d['full_name_en'] . ']' : ''), "\n";
    }
} catch (Throwable $e) { echo 'pending: ', $e->getMessage(), "\n"; }
echo "\n== public name in each server backup\n";
$files = glob(__DIR__ . '/.backups/db-*.sql.gz') ?: [];
sort($files);
$re = "/'((?:[^'\\\\]|\\\\.)*)'|NULL/s";
foreach ($files as $f) {
    $gz = @gzopen($f, 'rb');
    if (!$gz) continue;
    $cols = []; $inUsers = false; $out = [];
    while (($line = gzgets($gz, 4194304)) !== false) {
        if (strpos($line, 'CREATE TABLE `users`') === 0) { $inUsers = true; $cols = []; continue; }
        if ($inUsers) { if (preg_match('/^\s+`([^`]+)`/', $line, $m)) { $cols[] = $m[1]; continue; } $inUsers = false; }
        if (strpos($line, 'INSERT INTO `users` VALUES') === 0 && $cols) {
            preg_match_all($re, substr($line, 27), $mm, PREG_SET_ORDER);
            $vals = array_map(fn($x) => isset($x[1]) ? stripcslashes($x[1]) : null, $mm);
            if (count($vals) < count($cols)) continue;
            $row = array_combine($cols, array_slice($vals, 0, count($cols)));
            if (($row['role'] ?? '') === 'artist') $out[] = $row['id'] . ':[' . ($row['artist_name'] ?? '') . ']';
        }
    }
    gzclose($gz);
    echo basename($f), ' -> ', $out ? implode('  ', $out) : '(no artists)', "\n";
}
