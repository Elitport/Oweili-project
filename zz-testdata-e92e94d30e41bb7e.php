<?php
/* TEMPORARY (owner, 11 Nov 2026): full backup, then delete the test data on the live database:
   the TEST article (#1), the accounts TEST Artist (#4) and TEST Artist 2 (#5) with their artwork (#3, its
   certificate becomes Cancelled as usual), their approved edits, pictures and sign-in tries, and the test
   entries in the activity log. Locked by a secret key, runs once. Removed straight after. */
if (!hash_equals('aecb2e540c7c5afc34ee3764c8945a56fddc65e4dd4dc0e84ed9816d2711f9db', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/admin_lib.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
@set_time_limit(300);
$db = getDB();
$dbName = (string) $db->query('SELECT DATABASE()')->fetchColumn();
const TEST_USERS = [4 => 'no-reply@oweili.com', 5 => 'contact@oweili.com'];
const TEST_ARTWORK = 3; const TEST_ARTICLE = 1;
$q = function (string $title, string $sql) use ($db) {
    echo "\n== $title\n";
    try { foreach ($db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r) echo implode(' | ', array_map(fn($v) => $v === null ? '-' : str_replace(["\n", "\r"], ' ', mb_substr((string) $v, 0, 120)), $r)), "\n"; }
    catch (Throwable $e) { echo "(not available)\n"; }
};
$show = function () use ($q) {
    $q('users', 'SELECT id, role, email, artist_name FROM users ORDER BY id');
    $q('artworks', 'SELECT id, artist_id, title_en, status FROM artworks ORDER BY id');
    $q('certificates', 'SELECT id, artwork_id, cert_no, status, cancel_reason FROM certificates ORDER BY id');
    $q('articles', 'SELECT id, slug, status FROM articles ORDER BY id');
    $q('pending_changes', 'SELECT id, user_id, target_type, target_id, status FROM pending_changes ORDER BY id');
    $q('portfolio_images', 'SELECT id, artist_id, image_url FROM portfolio_images');
    $q('activity_log by action', 'SELECT action, COUNT(*) n FROM activity_log GROUP BY action ORDER BY action');
};
$q('test entries sent to Bashro and the test support message (for the owner)', "SELECT id, action, target_type, target_id, details, created_at FROM activity_log WHERE action IN ('bashro_send', 'support_message') ORDER BY id");
$q('test entries in the activity log that will be removed', "SELECT id, action, actor_type, actor_id, target_type, target_id, created_at FROM activity_log WHERE (actor_type IN ('artist','buyer') AND actor_id IN (4,5))
   OR (target_type = 'user' AND target_id IN (4,5)) OR (target_type = 'artwork' AND target_id = 3) OR (target_type = 'article' AND target_id = 1)
   OR (target_type IN ('certificate','cert') AND target_id = 1) OR action IN ('bashro_send','support_message','admin_alert','admin_alert_mail') ORDER BY id");
if (($_GET['a'] ?? '') !== 'run') { echo "\nready, database $dbName\n"; $show(); exit; }
if (is_file(__DIR__ . '/.logs/zz-testdata-done')) { echo "already run\n"; $show(); exit; }

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

/* 2. the TEST article (its author is TEST Artist 2, so it goes first) */
foreach ($db->query('SELECT image_path FROM article_images WHERE article_id = 1')->fetchAll(PDO::FETCH_COLUMN) as $f) { $f = (string) $f; if (strpos($f, 'uploads/articles/') === 0 && is_file(__DIR__ . '/' . $f)) @unlink(__DIR__ . '/' . $f); }
$cov = (string) $db->query('SELECT cover_image_path FROM articles WHERE id = 1')->fetchColumn();
$db->exec('DELETE FROM article_images WHERE article_id = 1');
$n = $db->exec("DELETE FROM articles WHERE id = 1 AND slug = 'test-how-watercolour-glazing-works'");
if ($cov !== '' && strpos($cov, 'uploads/articles/') === 0 && is_file(__DIR__ . '/' . $cov)) @unlink(__DIR__ . '/' . $cov);
echo "TEST article deleted: $n\n";

/* 3. the test entries in the activity log (before the accounts, so the record of the deletion itself stays) */
$n = $db->exec("DELETE FROM activity_log WHERE (actor_type IN ('artist','buyer') AND actor_id IN (4,5))
   OR (target_type = 'user' AND target_id IN (4,5)) OR (target_type = 'artwork' AND target_id = 3) OR (target_type = 'article' AND target_id = 1)
   OR (target_type IN ('certificate','cert') AND target_id = 1) OR action IN ('bashro_send','support_message','admin_alert','admin_alert_mail')");
echo "activity log test entries deleted: $n\n";

/* 4. the two test accounts: artwork, stage photos, colours, favorites, portfolio, profile photo, sessions;
      the certificate becomes Cancelled ("Account deleted by the Oweili team") */
foreach (TEST_USERS as $uid => $email) {
    $chk = $db->prepare('SELECT email FROM users WHERE id = ?'); $chk->execute([$uid]);
    if ((string) $chk->fetchColumn() !== $email) { echo "account $uid: email does not match, skipped\n"; continue; }
    $r = owAdminDeleteUser($uid);
    echo "account $uid ($email): ", $r === '' ? 'deleted' : "NOT deleted ($r)", "\n";
}
/* 5. their edits (pending_changes) and sign-in tries */
$n = $db->exec('DELETE FROM pending_changes WHERE user_id IN (4,5) OR (target_type = \'artwork\' AND target_id = 3) OR (target_type = \'article\' AND target_id = 1)');
echo "test edits deleted: $n\n";
try { $st = $db->prepare('DELETE FROM login_attempts WHERE email IN (?, ?)'); $st->execute(array_values(TEST_USERS)); echo "sign-in tries deleted: ", $st->rowCount(), "\n"; } catch (Throwable $e) { echo "sign-in tries: not available\n"; }
/* 6. picture files no row points to any more (left by the crashed first test sign-up) */
$used = array_merge($db->query('SELECT image_url FROM portfolio_images')->fetchAll(PDO::FETCH_COLUMN), $db->query('SELECT profile_picture FROM users')->fetchAll(PDO::FETCH_COLUMN));
foreach (['uploads/portfolio', 'uploads/profiles'] as $d) foreach ((array) @scandir(__DIR__ . '/' . $d) as $f) {
    if ($f === '.' || $f === '..' || !is_file(__DIR__ . "/$d/$f")) continue;
    if (!in_array("$d/$f", $used, true)) { @unlink(__DIR__ . "/$d/$f"); echo "unused file deleted: $d/$f\n"; }
}
@file_put_contents(__DIR__ . '/.logs/zz-testdata-done', gmdate('c'));
echo "\n--- after ---\n"; $show();
