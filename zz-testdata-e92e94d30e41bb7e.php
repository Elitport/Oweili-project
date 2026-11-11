<?php
/* TEMPORARY (owner, 11 Nov 2026): read-only list of the test data on the live database, so the owner's
   "delete all test data" can be checked first. Locked by a secret key. Removed straight after. */
if (!hash_equals('aecb2e540c7c5afc34ee3764c8945a56fddc65e4dd4dc0e84ed9816d2711f9db', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
$db = getDB();
$q = function (string $title, string $sql) use ($db) {
    echo "\n== $title\n";
    try { foreach ($db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r) echo implode(' | ', array_map(fn($v) => $v === null ? '-' : str_replace(["\n", "\r"], ' ', mb_substr((string) $v, 0, 90)), $r)), "\n"; }
    catch (Throwable $e) { echo "(not available: ", get_class($e), ")\n"; }
};
echo "database ", $db->query('SELECT DATABASE()')->fetchColumn(), "\n";
$q('users', 'SELECT id, role, email, artist_name, full_name_en, full_name_ar, is_verified, is_approved, is_banned, created_at FROM users ORDER BY id');
$q('artworks', 'SELECT id, artist_id, title_en, title_ar, status, is_approved, is_draft, medium, created_at FROM artworks ORDER BY id');
$q('certificates', 'SELECT id, artwork_id, cert_no, status, cancel_reason FROM certificates ORDER BY id');
$q('articles', 'SELECT id, type, slug, status, author_user_id, title_en FROM articles ORDER BY id');
$q('article_images', 'SELECT id, article_id, image_path FROM article_images ORDER BY id');
$q('photo_consents', 'SELECT id, article_id, artist_name_en, status FROM photo_consents ORDER BY id');
$q('artwork_inquiries', 'SELECT id, artwork_id, artist_id, buyer_id, buyer_name, buyer_email, status, created_at FROM artwork_inquiries ORDER BY id');
$q('gift_requests', 'SELECT id, user_id, buyer_name, buyer_email, status, created_at FROM gift_requests ORDER BY id');
$q('commissions', 'SELECT id, ref, user_id, artist_id, buyer_name, buyer_email, status, created_at FROM commissions ORDER BY id');
$q('auction_waitlist', 'SELECT id, user_id, name, email, created_at FROM auction_waitlist ORDER BY id');
$q('pending_changes', 'SELECT id, user_id, target_type, target_id, status, created_at FROM pending_changes ORDER BY id');
$q('favorites', 'SELECT user_id, artwork_id FROM favorites');
$q('portfolio_images', 'SELECT id, artist_id, image_url FROM portfolio_images ORDER BY id');
$q('collections / items', 'SELECT c.id, c.slug, (SELECT COUNT(*) FROM collection_items i WHERE i.collection_id = c.id) n FROM collections c');
$q('site_settings (artist of the week)', "SELECT * FROM site_settings WHERE skey LIKE 'aotw%' OR skey LIKE 'artist_of%'");
$q('institutes', 'SELECT id, name_en, city_en, is_active FROM institutes ORDER BY id');
$q('courses', 'SELECT id, institute_id, title_en, is_active FROM courses ORDER BY id');
$q('orders / payouts / purchases', "SELECT 'orders', COUNT(*) FROM orders UNION ALL SELECT 'payouts', COUNT(*) FROM payouts UNION ALL SELECT 'purchases', COUNT(*) FROM purchases");
$q('activity_log by action', 'SELECT action, COUNT(*) n, MIN(created_at), MAX(created_at) FROM activity_log GROUP BY action ORDER BY action');
$q('activity support_message', "SELECT id, actor_type, actor_id, target_type, target_id, detail, created_at FROM activity_log WHERE action = 'support_message' ORDER BY id");
$q('activity bashro_send', "SELECT id, target_type, target_id, detail, created_at FROM activity_log WHERE action = 'bashro_send' ORDER BY id");
$q('activity columns', 'SHOW COLUMNS FROM activity_log');
foreach (['/.private/signatures', '/uploads/portfolio', '/uploads/profiles', '/uploads/videos', '/uploads/artworks/stages'] as $d) {
    echo "\n== files in $d\n"; foreach ((array) @scandir(__DIR__ . $d) as $f) if ($f !== '.' && $f !== '..' && $f !== false) echo $f, ' ', @filesize(__DIR__ . $d . '/' . $f), "\n";
}
