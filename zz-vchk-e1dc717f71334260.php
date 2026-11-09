<?php
/* TEMPORARY read-only check (owner, 9 Nov 2026): why the confirm link for TEST Artist 2 said "invalid". Shows only short parts of the code. Locked by a secret key. Removed straight after. */
if (!hash_equals('82f6cdee2a67d1dcd8df38055298d6ab6f47d06f20f49dad1a5a7b1fa1ca2da5', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
$db = getDB();
echo 'db now ', $db->query('SELECT NOW()')->fetchColumn(), ' tz ', $db->query('SELECT @@session.time_zone')->fetchColumn(), ' php ', date('Y-m-d H:i:s T'), "\n";
foreach ($db->query("SELECT id, email, is_verified, LENGTH(verification_token) len, LEFT(verification_token, 6) a, RIGHT(verification_token, 4) b, verification_expires, created_at FROM users WHERE id IN (4, 5)") as $r) echo implode("\t", $r), "\n";
foreach ($db->query("SELECT id, action, target_id, created_at FROM activity_log WHERE id > 26 ORDER BY id") as $r) echo implode("\t", $r), "\n";
$mask = fn($l) => preg_replace_callback('/token=([0-9a-fA-F%]{0,200})/', fn($m) => 'token=' . substr($m[1], 0, 6) . '...(' . strlen($m[1]) . ' chars)' . substr($m[1], -4), $l);
$base = dirname(__DIR__);
foreach (array_merge(glob($base . '/logs/*') ?: [], glob($base . '/access-logs/*') ?: [], glob(__DIR__ . '/.logs/*') ?: []) as $f) {
    echo "\n== ", basename($f), ' ', @filesize($f), "\n";
    if (!is_file($f) || !is_readable($f)) continue;
    $lines = preg_grep('/verify\.php/', (array) @file($f, FILE_IGNORE_NEW_LINES));
    foreach (array_slice($lines, -20) as $l) echo $mask(substr($l, 0, 400)), "\n";
}
