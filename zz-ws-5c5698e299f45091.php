<?php
/* TEMPORARY (owner, 24 Nov 2026): writing style in the database texts (articles, Art Map places incl. the older institutes, settings):
   removes tanween everywhere, and the full stop at the end of short one-sentence lines (titles, names,
   places, short texts). Article bodies only lose tanween. Full backup first. Locked by a secret key,
   runs once. Removed straight after. ?a=dry lists what would change; ?a=run backs up and changes. */
if (!hash_equals('93dd748009c4c6c6edae5febc20c9bf10dd3c9cde39ffd3cc65cc424b5886fc9', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
@set_time_limit(300);
$db = getDB();
$a = (string) ($_GET['a'] ?? 'dry');

function wsTan(string $s): string { return preg_replace('/[\x{064B}-\x{064D}]/u', '', $s); }
function wsShort(string $s): bool {
    if (substr($s, -1) !== '.' || substr($s, -2) === '..') return false;
    $vis = preg_replace('/<[^<>]*>/u', '', $s);
    if (strpbrk($vis, '<>') !== false || substr($vis, -1) !== '.') return false;
    if (!preg_match('/[A-Za-z\x{0621}-\x{064A}]/u', $vis) || preg_match('/[;{}\\\\$`|]/u', $vis)) return false;
    if (preg_match('/(?:\b(?:e\.g|i\.e|etc|ltd|inc|co|no|st|dr|vs|mr|mrs|ms|jr|sr|approx|rd|ave|blvd|a\.m|p\.m)|(?<![A-Za-z])[A-Z])\.$/iu', $vis)) return false;
    if (preg_match('/[.!?\x{061F}](?:\s|["\x{00BB}\x{201D})\]]|$)/u', substr($vis, 0, -1)) || strpos(trim($vis), "\n") !== false) return false;
    if (strpos(trim($vis), ' ') === false && strpos(substr($vis, 0, -1), '.') !== false) return false;
    return true;
}
function wsFix(string $s, bool $short): string { $s = wsTan($s); return ($short && wsShort($s)) ? substr($s, 0, -1) : $s; }
/* the check: the new text is the old text with only tanween marks and one end full stop taken away */
function wsOnly(string $old, string $new, array &$n): bool {
    $a = preg_split('//u', $old, -1, PREG_SPLIT_NO_EMPTY); $b = preg_split('//u', $new, -1, PREG_SPLIT_NO_EMPTY);
    $i = 0; $j = 0; $tan = 0; $dot = 0;
    while ($i < count($a)) {
        if ($j < count($b) && $a[$i] === $b[$j]) { $i++; $j++; continue; }
        if (preg_match('/^[\x{064B}-\x{064D}]$/u', $a[$i])) { $tan++; $i++; continue; }
        if ($a[$i] === '.' && $i === count($a) - 1) { $dot++; $i++; continue; }
        return false;
    }
    if ($j !== count($b)) return false;
    $n['tan'] += $tan; $n['dot'] += $dot; return true;
}
function wsWalk($v, bool $short) {
    if (is_string($v)) return wsFix($v, $short);
    if (is_array($v)) { foreach ($v as $k => $x) $v[$k] = wsWalk($x, $short); }
    return $v;
}
function wsWalkCheck($o, $n, array &$c): bool {
    if (is_string($o)) return is_string($n) && wsOnly($o, $n, $c);
    if (is_array($o)) { if (!is_array($n) || array_keys($o) !== array_keys($n)) return false;
        foreach ($o as $k => $x) if (!wsWalkCheck($x, $n[$k], $c)) return false; return true; }
    return $o === $n;
}

/* the plan: table => [id column, [column => short line?]] */
$plan = [
    'articles'   => ['id', ['title_en' => true, 'title_ar' => true, 'event_location_en' => true, 'event_location_ar' => true, 'body_en' => false, 'body_ar' => false]],
    'art_places' => ['id', ['name_en' => true, 'name_ar' => true, 'city_en' => true, 'city_ar' => true, 'desc_en' => true, 'desc_ar' => true, 'address' => true, 'address_ar' => true, 'photo_credit' => true]],
    'institutes' => ['id', ['name_en' => true, 'name_ar' => true, 'city_en' => true, 'city_ar' => true, 'description_en' => true, 'description_ar' => true]],   // the older institutes, shown on the Art Map
];
$changes = []; $cnt = ['tan' => 0, 'dot' => 0]; $bad = 0;
foreach ($plan as $t => [$idc, $cols]) {
    $have = $db->query("SHOW COLUMNS FROM `$t`")->fetchAll(PDO::FETCH_COLUMN);
    $cols = array_intersect_key($cols, array_flip($have));
    $st = $db->query("SELECT `$idc`, `" . implode('`, `', array_keys($cols)) . "` FROM `$t` ORDER BY `$idc`");
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
        foreach ($cols as $c => $short) {
            $old = (string) ($r[$c] ?? ''); if ($old === '' || $r[$c] === null) continue;
            $new = wsFix($old, $short);
            if ($new === $old) continue;
            if (!wsOnly($old, $new, $cnt)) { $bad++; echo "CHECK FAILED $t #{$r[$idc]} $c\n"; continue; }
            $changes[] = [$t, $idc, $r[$idc], $c, $old, $new];
        }
    }
}
/* settings: plain text values, and the text inside JSON values (written back the same way they were stored) */
foreach ($db->query('SELECT setting_key, setting_value FROM site_settings ORDER BY setting_key') as $r) {
    $old = (string) ($r['setting_value'] ?? ''); if ($old === '') continue;
    $j = json_decode($old, true);
    if (is_array($j)) {
        $nj = wsWalk($j, true);
        if ($nj === $j) continue;
        $flags = (strpos($old, '\\u') === false ? JSON_UNESCAPED_UNICODE : 0) | (strpos($old, '\\/') === false ? JSON_UNESCAPED_SLASHES : 0);
        if (json_encode($j, $flags) !== $old) { echo "SKIPPED setting {$r['setting_key']}: its stored form cannot be rebuilt exactly\n"; continue; }
        if (!wsWalkCheck($j, $nj, $cnt)) { $bad++; echo "CHECK FAILED setting {$r['setting_key']}\n"; continue; }
        $changes[] = ['site_settings', 'setting_key', $r['setting_key'], 'setting_value', $old, json_encode($nj, $flags)];
    } else {
        $new = wsFix($old, true);
        if ($new === $old) continue;
        if (!wsOnly($old, $new, $cnt)) { $bad++; echo "CHECK FAILED setting {$r['setting_key']}\n"; continue; }
        $changes[] = ['site_settings', 'setting_key', $r['setting_key'], 'setting_value', $old, $new];
    }
}
$by = [];
foreach ($changes as $c) $by[$c[0]] = ($by[$c[0]] ?? 0) + 1;
echo 'fields to change: ', count($changes), ' (', json_encode($by), ') | tanween: ', $cnt['tan'], ' | end full stops: ', $cnt['dot'], ' | check failures: ', $bad, "\n\n";
if ($a === 'dry') {
    foreach ($changes as [$t, , $id, $c, $o, $n]) {
        $show = function ($s) { return mb_strlen($s) > 160 ? '…' . mb_substr($s, -150) : $s; };
        echo "$t #$id $c\n  - ", $show($o), "\n  + ", $show($n), "\n";
    }
    exit;
}
if ($a !== 'run') exit;
if (is_file(__DIR__ . '/.logs/zz-ws-done')) { echo "already run\n"; exit; }
if ($bad) { echo "stopped: a check failed, nothing changed\n"; exit; }
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

$db->beginTransaction();
try {
    $done = 0;
    foreach ($changes as [$t, $idc, $id, $c, $o, $n]) {
        $u = $db->prepare("UPDATE `$t` SET `$c` = :n WHERE `$idc` = :id AND `$c` = :o");
        $u->execute([':n' => $n, ':id' => $id, ':o' => $o]);
        $done += $u->rowCount();
    }
    if ($done !== count($changes)) { $db->rollBack(); echo "stopped: only $done of ", count($changes), " rows matched, nothing changed\n"; exit; }
    $db->commit();
} catch (Throwable $e) { $db->rollBack(); echo "stopped: database error, nothing changed\n"; exit; }
@file_put_contents(__DIR__ . '/.logs/zz-ws-done', gmdate('c'));
echo "changed $done fields\n";
/* read back: every changed field now holds the new text */
$ok = 0;
foreach ($changes as [$t, $idc, $id, $c, $o, $n]) {
    $s = $db->prepare("SELECT `$c` FROM `$t` WHERE `$idc` = :id"); $s->execute([':id' => $id]);
    if ((string) $s->fetchColumn() === $n) $ok++;
}
echo "read back ok: $ok of ", count($changes), "\n";
