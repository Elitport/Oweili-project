<?php
/* TEMPORARY (owner, 14 Nov 2026): Art Education: a lighter free cover for "What a certificate of authenticity is"
   (Cézanne, Mont Sainte-Victoire, CC0) and its credit line in both languages. Makes a full backup first.
   The publish status is NOT changed. Locked by a secret key, runs once. Removed straight after. */
if (!hash_equals('2a15bbd498bc550448f5e63e96572108c129cf8ba2c0f2ce3c310be5bfdb952a', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
@set_time_limit(300);
$db = getDB();
$p = json_decode(base64_decode('eyJzbHVnIjogIndoYXQtYS1jZXJ0aWZpY2F0ZS1vZi1hdXRoZW50aWNpdHktaXMtYW5kLXdoeS1pdC1tYXR0ZXJzIiwgImltZyI6ICJhc3NldHMvaW1nL3N0eWxlcy9wb3N0X2ltcHJlc3Npb25pc20uanBnIiwgIm9sZF9lbiI6ICJQaWN0dXJlOiBBcmtoaXAgS3VpbmR6aGksIE1vb25saXQgTmlnaHQgb24gdGhlIERuaWVwZXIsIDE4ODAuIFB1YmxpYyBkb21haW4sIHZpYSBXaWtpbWVkaWEgQ29tbW9ucy4iLCAibmV3X2VuIjogIlBpY3R1cmU6IFBhdWwgQ8OpemFubmUsIE1vbnQgU2FpbnRlLVZpY3RvaXJlIGFuZCB0aGUgVmlhZHVjdCBvZiB0aGUgQXJjIFJpdmVyIFZhbGxleSwgMTg4MuKAkzg1LiBDQzAsIHZpYSBXaWtpbWVkaWEgQ29tbW9ucy4iLCAib2xkX2FyIjogItin2YTYtdmI2LHYqTog2KPYsdiu2YrYqCDZg9mI2YrZhtis2YrYjCDCq9mE2YrZhNipINmF2YLZhdix2Kkg2LnZhNmJINmG2YfYsSDYp9mE2K/ZhtmK2KjYscK72IwgMTg4MC4g2YXZhNmD2YrYqSDYudin2YXYqdiMINi52KjYsSDZiNmK2YPZitmF2YrYr9mK2Kcg2YPZiNmF2YbYsi4iLCAibmV3X2FyIjogItin2YTYtdmI2LHYqTog2KjZiNmEINiz2YrYstin2YbYjCDCq9is2KjZhCDYs9in2YbYqiDZgdmK2YPYqtmI2KfYsSDZiNis2LPYsSDZiNin2K/ZiiDYotix2YPCu9iMIDE4ODLigJMxODg1LiDYsdiu2LXYqSBDQzDYjCDYudio2LEg2YjZitmD2YrZhdmK2K/ZitinINmD2YjZhdmG2LIuIn0='), true);
$st = $db->prepare("SELECT id, status, cover_image_path, body_en, body_ar FROM articles WHERE slug = :s AND type = 'art_education'");
$st->execute([':s' => $p['slug']]);
$a = $st->fetch();
if (!$a) { echo "article not found\n"; exit; }
$okEn = strpos((string) $a['body_en'], $p['old_en']) !== false; $okAr = strpos((string) $a['body_ar'], $p['old_ar']) !== false;
echo 'article ', $a['id'], ' | ', $a['status'], ' | ', $a['cover_image_path'], ' | credit en found: ', $okEn ? 'yes' : 'NO', ' | ar found: ', $okAr ? 'yes' : 'NO',
     ' | picture file: ', is_file(__DIR__ . '/' . $p['img']) ? 'ok' : 'MISSING', "\n";
if (($_GET['a'] ?? '') !== 'run') exit;
if (is_file(__DIR__ . '/.logs/zz-edu7-done')) { echo "already run\n"; exit; }
if (!$okEn || !$okAr || !is_file(__DIR__ . '/' . $p['img'])) { echo "stopped: something does not match, nothing changed\n"; exit; }
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

$dir = __DIR__ . '/uploads/articles';
if (!is_dir($dir)) @mkdir($dir, 0755, true);
$name = bin2hex(random_bytes(16)) . '.jpg';
if (!@copy(__DIR__ . '/' . $p['img'], $dir . '/' . $name)) { echo "copy failed, nothing changed\n"; exit; }
$db->prepare('UPDATE articles SET cover_image_path = :c, body_en = :be, body_ar = :ba WHERE id = :id')
   ->execute([':c' => 'uploads/articles/' . $name, ':be' => str_replace($p['old_en'], $p['new_en'], (string) $a['body_en']),
              ':ba' => str_replace($p['old_ar'], $p['new_ar'], (string) $a['body_ar']), ':id' => (int) $a['id']]);
@file_put_contents(__DIR__ . '/.logs/zz-edu7-done', gmdate('c'));
echo "done: cover uploads/articles/$name (the old cover file is kept)\n";
