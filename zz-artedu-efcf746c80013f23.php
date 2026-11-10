<?php
/* TEMPORARY (owner, 10 Nov 2026): full backup, then (1) the TEST Art Education article back to draft
   (hidden from visitors) and (2) a cover picture + a one-line picture credit for the 5 Art Education
   articles. The pictures are the site's own free-licence copies in assets/img/styles/ (the same files and
   credits as Art Styles). Locked by a secret key, runs once. Removed straight after. */
if (!hash_equals('c5ed9675e33f08ea4ec1a57e3eff9d66a4090dde8c23517dcfbdf265d51c41ef', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
@set_time_limit(300);
$db = getDB();
$dbName = (string) $db->query('SELECT DATABASE()')->fetchColumn();
$PICS = [
  "how-to-tell-an-original-painting-from-a-printed-copy" => ["impasto", "Vincent van Gogh, Wheat Field with Cypresses, 1889. CC0, via Wikimedia Commons.", "فنسنت فان غوخ، «حقل قمح مع أشجار السرو»، 1889. رخصة CC0، عبر ويكيميديا كومنز."],
  "how-to-choose-a-painting-for-your-home" => ["post_impressionism", "Paul Cézanne, Mont Sainte-Victoire and the Viaduct of the Arc River Valley, 1882 to 1885. CC0, via Wikimedia Commons.", "بول سيزان، «جبل سانت فيكتوار وجسر وادي آرك»، 1882 إلى 1885. رخصة CC0، عبر ويكيميديا كومنز."],
  "how-to-care-for-a-painting-light-heat-and-cleaning" => ["hyperrealism", "Cornelis Norbertus Gijsbrechts, The Reverse of a Framed Painting, about 1670. Public domain, via Wikimedia Commons.", "كورنيليس خيسبرختس، «ظهر لوحة مؤطرة»، نحو 1670. ملكية عامة، عبر ويكيميديا كومنز."],
  "why-original-art-prices-differ" => ["baroque", "Jacob van Ruisdael, The Windmill at Wijk bij Duurstede, about 1670. Public domain, via Wikimedia Commons.", "ياكوب فان رويسدال، «طاحونة الهواء في فايك بي دورستيده»، نحو 1670. ملكية عامة، عبر ويكيميديا كومنز."],
  "what-a-certificate-of-authenticity-is-and-why-it-matters" => ["animal", "Albrecht Dürer, Young Hare, 1502. Public domain, via Wikimedia Commons.", "ألبرخت دورر، «الأرنب البري الصغير»، 1502. ملكية عامة، عبر ويكيميديا كومنز."]
];
$TEST = 'test-how-watercolour-glazing-works';
$show = function () use ($db) {
    foreach ($db->query("SELECT id, slug, status, cover_image_path FROM articles WHERE type = 'art_education' ORDER BY id") as $r) echo implode(' | ', $r), "\n";
};
if (($_GET['a'] ?? '') !== 'run') { echo "ready, database $dbName\n"; $show(); exit; }
if (is_file(__DIR__ . '/.logs/zz-artedu-done')) { echo "already run\n"; $show(); exit; }

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

$n = $db->prepare("UPDATE articles SET status = 'draft' WHERE slug = ? AND type = 'art_education'");
$n->execute([$TEST]); echo "TEST article set to draft: ", $n->rowCount(), "\n";
$dir = __DIR__ . '/uploads/articles';
if (!is_dir($dir)) @mkdir($dir, 0755, true);
foreach ($PICS as $slug => [$style, $credEn, $credAr]) {
    $src = __DIR__ . '/assets/img/styles/' . $style . '.jpg';
    $rel = 'uploads/articles/' . md5('oweili-article-cover-' . $slug) . '.jpg';
    if (!is_file($src) || !@copy($src, __DIR__ . '/' . $rel)) { echo "FAILED to copy $style for $slug\n"; continue; }
    $st = $db->prepare("SELECT id, body_en, body_ar FROM articles WHERE slug = ? AND type = 'art_education'"); $st->execute([$slug]); $a = $st->fetch();
    if (!$a) { echo "not found: $slug\n"; continue; }
    $en = rtrim((string) $a['body_en']); $ar = rtrim((string) $a['body_ar']);
    $lineEn = 'Picture: ' . $credEn; $lineAr = 'الصورة: ' . $credAr;
    if (strpos($en, $lineEn) === false) $en .= "\n\n" . $lineEn;
    if (strpos($ar, $lineAr) === false) $ar .= "\n\n" . $lineAr;
    $u = $db->prepare('UPDATE articles SET cover_image_path = ?, body_en = ?, body_ar = ? WHERE id = ?');
    $u->execute([$rel, $en, $ar, (int) $a['id']]);
    echo "cover + credit: $slug -> $rel (", filesize(__DIR__ . '/' . $rel), " bytes)\n";
}
@file_put_contents(__DIR__ . '/.logs/zz-artedu-done', gmdate('c'));
$show();
