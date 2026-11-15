<?php
/* TEMPORARY (owner, 15 Nov 2026): Art Institutes and "Book your seat". Full backup first, then courses_update.sql
   (course_registrations.user_id may be empty; new table course_leads) and the switch courses_on = 1.
   Museum tickets (bookings_on) are not touched. Locked by a secret key, runs once. Removed straight after. */
if (!hash_equals('ef51d16432da11663f229718860ade16f59cb5fc02771afaecb0d7b72b71c1f7', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
@set_time_limit(300);
$db = getDB();
$col = $db->query("SELECT COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'course_registrations' AND COLUMN_NAME = 'user_id'")->fetch(PDO::FETCH_ASSOC);
echo 'course_registrations.user_id: ', json_encode($col), "\n";
echo 'course_leads table: ', (int) $db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'course_leads'")->fetchColumn(), "\n";
foreach ($db->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('bookings_on','courses_on')") as $r) echo $r['setting_key'], ' = ', $r['setting_value'], "\n";
echo 'bookable approved active courses: ', $db->query("SELECT COUNT(*) FROM courses WHERE bookable = 1 AND status = 'approved' AND is_active = 1")->fetchColumn(), "\n";
echo 'institutes active: ', $db->query("SELECT COUNT(*) FROM institutes WHERE is_active = 1")->fetchColumn(), '; signed up: ', $db->query("SELECT COUNT(*) FROM institutes WHERE via_signup = 1")->fetchColumn(), "\n";
echo 'registrations: ', $db->query("SELECT COUNT(*) FROM course_registrations")->fetchColumn(), "\n";
if (($_GET['a'] ?? '') !== 'run') exit;
if (is_file(__DIR__ . '/.logs/zz-courses-done')) { echo "already run\n"; exit; }
if (!$col || !preg_match('/^bigint(\(20\))? unsigned$/i', (string) $col['COLUMN_TYPE'])) { echo "STOP: user_id is not the expected type, nothing changed\n"; exit; }
$dbName = (string) $db->query('SELECT DATABASE()')->fetchColumn();
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
$sql = base64_decode('LS0gPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09Ci0tIGNvdXJzZXNfdXBkYXRlLnNxbCDigJQgQXJ0IEluc3RpdHV0ZXMgYW5kICJCb29rIHlvdXIgc2VhdCIgKG93bmVyLCAxNSBOb3YgMjAyNikKLS0gT25seSBjaGFuZ2VzIHdoYXQgdGhlIGZlYXR1cmUgbmVlZHM7IHNhZmUgdG8gcnVuIG1vcmUgdGhhbiBvbmNlLgotLSAgMS4gY291cnNlX3JlZ2lzdHJhdGlvbnMudXNlcl9pZCBtYXkgYmUgZW1wdHk6IGEgdmlzaXRvciBib29rcyBhIHNlYXQgd2l0aAotLSAgICAgbmFtZSwgZW1haWwgYW5kIHBob25lLCB3aXRob3V0IGFuIGFjY291bnQgKG9uZSBzZWF0IGhlbGQgdW50aWwgaXQgaXMKLS0gICAgIHBhaWQgb3IgY2FuY2VsbGVkKQotLSAgMi4gY291cnNlX2xlYWRzOiAiTm90aWZ5IG1lIiBmb3IgYW4gaW5zdGl0dXRlIHdpdGggbm8gY291cnNlcyB5ZXQKLS0gICAgIChuYW1lLCBlbWFpbCwgcGhvbmUpLCBmb3IgdGhlIG93bmVyIGFuZCBCYXNocm8gU2FsZXMKLS0gPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09CkFMVEVSIFRBQkxFIGNvdXJzZV9yZWdpc3RyYXRpb25zIE1PRElGWSBDT0xVTU4gdXNlcl9pZCBCSUdJTlQgVU5TSUdORUQgTlVMTDsKCkNSRUFURSBUQUJMRSBJRiBOT1QgRVhJU1RTIGNvdXJzZV9sZWFkcyAoCiAgaWQgICAgICAgICAgICBCSUdJTlQgVU5TSUdORUQgTk9UIE5VTEwgQVVUT19JTkNSRU1FTlQsCiAgaW5zdGl0dXRlX2lkICBCSUdJTlQgVU5TSUdORUQgTlVMTCwgICAgICAgICAgICAtLSBpbnN0aXR1dGVzLmlkICh0aGUgb2xkZXIgaW5zdGl0dXRlcyBhbmQgc2lnbmVkLXVwIG9uZXMpCiAgcGxhY2VfaWQgICAgICBCSUdJTlQgVU5TSUdORUQgTlVMTCwgICAgICAgICAgICAtLSBhcnRfcGxhY2VzLmlkIChBcnQgTWFwIGluc3RpdHV0ZXMpCiAgbmFtZSAgICAgICAgICBWQVJDSEFSKDE1MCkgTk9UIE5VTEwsCiAgZW1haWwgICAgICAgICBWQVJDSEFSKDE5MCkgTk9UIE5VTEwgREVGQVVMVCAnJywKICBwaG9uZSAgICAgICAgIFZBUkNIQVIoMzApICBOT1QgTlVMTCwKICBsYW5nICAgICAgICAgIENIQVIoMikgICAgICBOT1QgTlVMTCBERUZBVUxUICdlbicsCiAgc3RhdHVzICAgICAgICBFTlVNKCduZXcnLCdjb250YWN0ZWQnLCdjbG9zZWQnKSBOT1QgTlVMTCBERUZBVUxUICduZXcnLAogIGNyZWF0ZWRfYXQgICAgVElNRVNUQU1QIE5PVCBOVUxMIERFRkFVTFQgQ1VSUkVOVF9USU1FU1RBTVAsCiAgUFJJTUFSWSBLRVkgKGlkKSwKICBLRVkgaWR4X2NvdXJzZV9sZWFkc19pbnN0IChpbnN0aXR1dGVfaWQpLAogIEtFWSBpZHhfY291cnNlX2xlYWRzX3BsYWNlIChwbGFjZV9pZCksCiAgS0VZIGlkeF9jb3Vyc2VfbGVhZHNfY3JlYXRlZCAoY3JlYXRlZF9hdCkKKSBFTkdJTkU9SW5ub0RCIERFRkFVTFQgQ0hBUlNFVD11dGY4bWI0IENPTExBVEU9dXRmOG1iNF91bmljb2RlX2NpOwo=');
foreach (array_filter(array_map('trim', explode(';', preg_replace('/^\s*--.*$/m', '', $sql)))) as $q) $db->exec($q);
$db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('courses_on', '1') ON DUPLICATE KEY UPDATE setting_value = '1'")->execute();
@unlink(__DIR__ . '/.logs/schema_cache.json');
@file_put_contents(__DIR__ . '/.logs/zz-courses-done', gmdate('c'));
echo "done\n";
$col = $db->query("SELECT COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'course_registrations' AND COLUMN_NAME = 'user_id'")->fetch(PDO::FETCH_ASSOC);
echo 'course_registrations.user_id now: ', json_encode($col), "\n";
echo 'course_leads table now: ', (int) $db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'course_leads'")->fetchColumn(), "\n";
foreach ($db->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('bookings_on','courses_on')") as $r) echo $r['setting_key'], ' = ', $r['setting_value'], "\n";
