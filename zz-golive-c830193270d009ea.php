<?php
/* TEMPORARY (owner, 26 Nov 2026): go-live helper for entryfill (fills the Entry of 26 checked places, nothing else). Locked by a secret key; removed straight after.
   ?a=check (read only: tables, counts, columns) · ?a=files (read only: the live file list with git fingerprints)
   ?a=backup (full database + full files backup into .backups) · ?a=run (a fresh full backup, then only the expected add-only lines of free_entry.sql, once) */
if (!hash_equals('684050d54a0162281f3cb2b9cc8adf548ab903f9257a7a27ec1b8e52d95a4d27', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/platform_lib.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
@set_time_limit(600); @ini_set('memory_limit', '512M');
$db = getDB();
$a = (string) ($_GET['a'] ?? 'check');
$root = __DIR__;
$walk = function (array $skipTop) use ($root): array {
    $out = []; $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) { if (!$f->isFile()) continue; $rel = substr($f->getPathname(), strlen($root) + 1);
        $top = explode('/', $rel)[0]; if (in_array($top, $skipTop, true)) continue; $out[] = $rel; }
    sort($out); return $out; };
$show = function () use ($db) {
    foreach (['users', 'artworks', 'activity_log', 'institutes', 'courses', 'course_registrations', 'orders', 'certificates'] as $t) {
        try { echo $t, ': ', $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn(), "\n"; } catch (Throwable $e) { echo $t, ': missing', "\n"; } }
    foreach (['artist_workshop_requests', 'artist_workshops', 'workshop_bookings'] as $t) echo 'table ', $t, ': ', $db->query("SHOW TABLES LIKE " . $db->quote($t))->fetchColumn() ? 'yes' : 'no', "\n";
    foreach ($db->query("SELECT entry_kind, COUNT(*) n FROM art_places GROUP BY entry_kind") as $r) echo 'entry ', ($r['entry_kind'] === '' ? 'not set' : $r['entry_kind']), ': ', $r['n'], "\n";
    echo 'artist_workshops_on: ', var_export($db->query("SELECT setting_value FROM site_settings WHERE setting_key='artist_workshops_on'")->fetchColumn(), true), "\n";
};
$backup = function () use ($db, $root, $walk): bool {
    $dir = $root . '/.backups'; if (!is_dir($dir)) @mkdir($dir, 0700, true);
    if (!is_file($dir . '/.htaccess')) @file_put_contents($dir . '/.htaccess', "Require all denied\n");
    $stamp = gmdate('Ymd-His'); $dbName = preg_replace('/[^a-z0-9_]/i', '', (string) $db->query('SELECT DATABASE()')->fetchColumn());
    $file = "$dir/db-$dbName-$stamp.sql.gz"; $gz = gzopen($file, 'wb6'); if (!$gz) { echo "database backup FAILED\n"; return false; }
    gzwrite($gz, "-- Oweili full backup " . gmdate('c') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");
    $tables = $db->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_COLUMN); $rows = 0;
    foreach ($tables as $t) { $t = str_replace('`', '', (string) $t); $c = $db->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM);
        gzwrite($gz, "\nDROP TABLE IF EXISTS `$t`;\n" . $c[1] . ";\n"); $st = $db->query("SELECT * FROM `$t`");
        while ($r = $st->fetch(PDO::FETCH_NUM)) { gzwrite($gz, "INSERT INTO `$t` VALUES (" . implode(',', array_map(fn($v) => $v === null ? 'NULL' : $db->quote((string) $v), $r)) . ");\n"); $rows++; } }
    gzwrite($gz, "\nSET FOREIGN_KEY_CHECKS=1;\n"); gzclose($gz);
    if (filesize($file) < 500) { echo "database backup FAILED (too small)\n"; return false; }
    echo "database backup ok: .backups/", basename($file), " (", count($tables), " tables, $rows rows, ", filesize($file), " bytes)\n";
    /* files: everything except the backups folder itself and the picture copies (they are made again by themselves) */
    $tar = "$dir/files-$stamp.tar.gz"; $g = gzopen($tar, 'wb6'); if (!$g) { echo "files backup FAILED\n"; return false; }
    $n = 0; $bytes = 0;
    foreach ($walk(['.backups']) as $rel) {
        if (strpos($rel, 'uploads/thumbs/') === 0) continue;
        $path = "$root/$rel"; $size = (int) @filesize($path); $fh = @fopen($path, 'rb'); if (!$fh) { echo "cannot read $rel\n"; continue; }
        $name = $rel; $prefix = '';
        if (strlen($name) > 100) { $cut = strrpos(substr($name, 0, 155), '/'); if ($cut === false) { echo "name too long, skipped: $rel\n"; fclose($fh); continue; } $prefix = substr($name, 0, $cut); $name = substr($name, $cut + 1); }
        $h = str_pad($name, 100, "\0") . str_pad(sprintf('%07o', 0644), 8, "\0") . str_pad('0000000', 8, "\0") . str_pad('0000000', 8, "\0")
           . str_pad(sprintf('%011o', $size), 12, "\0") . str_pad(sprintf('%011o', (int) @filemtime($path)), 12, "\0") . '        ' . '0' . str_repeat("\0", 100)
           . "ustar\0" . '00' . str_repeat("\0", 32) . str_repeat("\0", 32) . str_repeat("\0", 8) . str_repeat("\0", 8) . str_pad($prefix, 155, "\0") . str_repeat("\0", 12);
        $sum = 0; for ($i = 0; $i < 512; $i++) $sum += ord($h[$i]);
        $h = substr_replace($h, str_pad(sprintf('%06o', $sum), 6, '0', STR_PAD_LEFT) . "\0 ", 148, 8);
        gzwrite($g, $h); while (!feof($fh)) { $buf = fread($fh, 1 << 20); if ($buf === '' || $buf === false) break; gzwrite($g, $buf); }
        fclose($fh); if ($size % 512) gzwrite($g, str_repeat("\0", 512 - $size % 512)); $n++; $bytes += $size;
    }
    gzwrite($g, str_repeat("\0", 1024)); gzclose($g);
    echo "files backup ok: .backups/", basename($tar), " ($n files, $bytes bytes, ", filesize($tar), " bytes packed)\n";
    return $n > 50;
};
if ($a === 'check') { $show(); exit; }
if ($a === 'files') {
    foreach ($walk(['.backups', 'uploads', '.logs', '.private', '.git']) as $rel) {
        if ($rel === 'config.php') continue; $c = (string) @file_get_contents("$root/$rel");
        echo sha1('blob ' . strlen($c) . "\0" . $c), ' ', $rel, "\n"; }
    exit; }
if ($a === 'backup') { $show(); $backup(); exit; }
if ($a !== 'run') exit;
if (is_file($root . '/.logs/zz-golive-entryfill-done')) { echo "already run\n"; $show(); exit; }
$L = json_decode('[["Al Murabba Palace", "Riyadh", "free", null, "https://www.visitsaudi.com/en/riyadh/attractions/al-murabba-palace"], ["Diriyah Museum (Salwa Palace)", "Diriyah", "booking", null, "https://www.diriyah.sa/en/at-turaif"], ["Ibrahim Palace (Qasr Ibrahim)", "Hofuf", "free", null, "https://www.visitsaudi.com/en/al-ahsa/attractions/qasr-ibrahim"], ["Jazan Heritage Village", "Jazan", "free", null, "https://www.visitsaudi.com/en/jazan/attraction/the-heritage-village-in-jazan"], ["Matbouli House Museum", "Jeddah", "free", null, "https://www.visitsaudi.com/en/jeddah/attractions/beit-al-matbouli"], ["Museum of Traditional Architecture (At-Turaif)", "Diriyah", "booking", null, "https://www.diriyah.sa/en/at-turaif"], ["Tabuk Castle Museum", "Tabuk", "free", null, "https://www.visitsaudi.com/en/destinations/tabuk/attractions/islamic-archaeological-tabuk-castle"], ["Tayebat Museum", "Jeddah", "paid", 80, "https://visitsaudi.com/en/jeddah/attractions/tayebat-museum"], ["Al Shareef Museum", "Taif", "paid", 20, "https://www.visitsaudi.com/en/taif/attractions/al-shareef-museum"], ["Al-Raqdi Museum", "Abha", "free", null, "https://www.visitsaudi.com/en/aseer/attractions/alraqdi-museum-abha"], ["Arabian Horse Museum (At-Turaif)", "Diriyah", "booking", null, "https://www.diriyah.sa/en/at-turaif"], ["As Safiyyah Museum & Park", "Madinah", "free", null, "https://www.visitsaudi.com/en/madinah/attractions/as-safiyyah-museum-and-park"], ["Bayt Al-Bay\'ah (House of Allegiance)", "Hofuf", "free", null, "https://visitsaudi.com/en/al-ahsa/attractions/al-biah-house-in-al-ahsa"], ["Clock Tower Museum", "Makkah", "paid_from", 150, "https://www.visitsaudi.com/en/makkah/attractions/clock-tower-museum"], ["Heritage Village", "Dammam", "paid_from", 20, "https://visitsaudi.com/content/sauditourism/en/destinations/eastern-province/attractions/heritage-village-in-dammam.html"], ["Lifestyle Museum (At-Turaif)", "Diriyah", "booking", null, "https://www.diriyah.sa/en/at-turaif"], ["Military Museum (At-Turaif)", "Diriyah", "booking", null, "https://www.diriyah.sa/en/at-turaif"], ["Najran Regional Museum", "Najran", "free", null, "https://www.visitsaudi.com/en/najran/attractions/najran-museum"], ["Revelation Exhibition", "Makkah", "paid_from", 25, "https://www.visitsaudi.com/en/makkah/attractions/revelation-exhibition"], ["Al Muftaha Village", "Abha", "paid_from", 15, "https://www.visitsaudi.com/en/aseer/attractions/almuftaha-village"], ["Maraya", "AlUla", "booking", null, "https://www.experiencealula.com/en/things-to-do/experiences/maraya-tour"], ["Abstract Art Gallery", "Riyadh, Al-Nakheel", "free", null, "https://www.visitsaudi.com/en/riyadh/attractions/abstract-art-gallery"], ["Al Ajlan Art Gallery", "Riyadh", "free", null, "https://www.visitsaudi.com/en/riyadh/attractions/alajlan-art-gallery"], ["Art Street, Abha", "Abha", "free", null, "https://www.visitsaudi.com/en/aseer/attractions/art-street-in-abha"], ["Hafez Gallery", "Jeddah", "free", null, "https://www.hafezgallery.com/plan-your-visit"], ["Hayy Jameel", "Jeddah", "free", null, "https://hayyjameel.org/"]]', true); $today = '2026-11-27';
$show();
$ids = [];
foreach ($L as [$n, $city, $k, $pr, $src]) {
    $st = $db->prepare("SELECT id, entry_kind FROM art_places WHERE name_en = :n AND kind IN ('museum','gallery')"); $st->execute([':n' => $n]); $rows = $st->fetchAll();
    if (count($rows) !== 1) { echo "STOPPED: ", count($rows), " places named ", $n, ". Nothing changed\n"; exit; }
    if ((string) $rows[0]['entry_kind'] !== '') { echo "STOPPED: ", $n, " already has an entry. Nothing changed\n"; exit; }
    $ids[] = (int) $rows[0]['id'];
}
echo count($ids), " places found, all Not set\n";
if (!$backup()) { echo "STOPPED: no backup, nothing changed\n"; exit; }
$u = $db->prepare("UPDATE art_places SET entry_kind = :k, entry_price = :p, entry_source = :s, entry_checked = :c WHERE id = :id AND entry_kind = ''");
$done = 0;
foreach ($L as $i => [$n, $city, $k, $pr, $src]) {
    $u->execute([':k' => $k, ':p' => $pr === null ? null : $pr, ':s' => $src, ':c' => $today, ':id' => $ids[$i]]); $done += $u->rowCount();
    owLog('art_place_entry', 'art_place', $ids[$i], $n . ': ' . $k . ($pr !== null ? ' ' . ($k === 'paid_from' ? 'from ' : '') . $pr . ' SAR' : '') . ' (official source, checked ' . $today . ')', ['type' => 'admin', 'id' => null]);
}
echo "filled ", $done, " places\n";
@file_put_contents($root . '/.logs/zz-golive-entryfill-done', gmdate('c'));
echo "after:\n"; $show();
foreach ($db->query("SELECT name_en, entry_kind, entry_price, entry_checked FROM art_places WHERE entry_kind <> '' ORDER BY name_en") as $r) echo ' - ', $r['name_en'], ': ', $r['entry_kind'], ' ', $r['entry_price'], ' ', $r['entry_checked'], "\n";
