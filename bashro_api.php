<?php
/* =====================================================================
 *  bashro_api.php — the private, read-only data connection for Bashro
 *  (Oweili's central control center). SWITCHED OFF by default.
 *
 *  It only answers when config.php (on the server, never in GitHub) has:
 *      define('BASHRO_API_ENABLED', true);
 *      define('BASHRO_API_KEY', '…at least 32 random characters…');
 *  and optionally a list of allowed addresses:
 *      define('BASHRO_API_IPS', '203.0.113.10,198.51.100.7');
 *  Otherwise every request gets 404, as if the file did not exist.
 *
 *  Bashro asks with the key in a header (never in the web address, so it
 *  never ends up in logs):   Authorization: Bearer <key>
 *    GET bashro_api.php?resource=summary
 *    GET bashro_api.php?resource=people|artworks|inquiries|favorites|activity
 *        &since=2026-10-01T00:00:00Z   (changed on or after; uses updated_at / created_at)
 *        &after=<id from next_after>   (next page)   &limit=1..500 (default 200)
 *  Every record has its permanent public_id, a status, created_at and
 *  updated_at. Read only: nothing can be changed through it.
 *  Tables read: users, artworks, artwork_inquiries, favorites, activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/platform_lib.php';

function owApiOut(int $code, array $data): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
/* switched off (the normal state): look like a missing page */
function owApiOff(): void { http_response_code(404); header('Cache-Control: no-store'); exit; }

$enabled = defined('BASHRO_API_ENABLED') && BASHRO_API_ENABLED === true;
$key     = defined('BASHRO_API_KEY') ? (string) BASHRO_API_KEY : '';
if (!$enabled || strlen($key) < 32) owApiOff();
if (defined('BASHRO_API_IPS') && trim((string) BASHRO_API_IPS) !== '') {
    $ips = array_map('trim', explode(',', (string) BASHRO_API_IPS));
    if (!in_array((string) ($_SERVER['REMOTE_ADDR'] ?? ''), $ips, true)) owApiOff();
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') owApiOut(405, ['error' => 'read_only']);

$auth = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
if (!preg_match('/^Bearer\s+(\S+)$/', $auth, $m) || !hash_equals($key, $m[1])) {
    owLog('bashro_denied', null, null, '', ['type' => 'system', 'id' => null]);
    owApiOut(401, ['error' => 'unauthorized']);
}

/* at most 120 requests a minute (counted in the activity log) */
if (owRecentCount('bashro_read', 60) >= 120) owApiOut(429, ['error' => 'slow_down']);

$res   = (string) ($_GET['resource'] ?? 'summary');
$limit = max(1, min(500, (int) ($_GET['limit'] ?? 200)));
$after = max(0, (int) ($_GET['after'] ?? 0));
$since = null;
if (isset($_GET['since']) && is_string($_GET['since']) && ($t = strtotime($_GET['since'])) !== false) $since = gmdate('Y-m-d H:i:s', $t);

$db = getDB();
$page = function (string $sql, array $args, string $dateCol) use ($db, $limit, $after, $since): array {
    $sql .= ' AND t.id > :after' . ($since ? " AND t.$dateCol >= :since" : '') . ' ORDER BY t.id ASC LIMIT ' . ($limit + 1);
    $st = $db->prepare($sql);
    foreach ($args + [':after' => $after] + ($since ? [':since' => $since] : []) as $k => $v) $st->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $more = count($rows) > $limit;
    if ($more) array_pop($rows);
    $next = $more && $rows ? (int) end($rows)['id'] : null;
    foreach ($rows as &$r) unset($r['id']);         // Bashro uses the permanent public_id
    return ['data' => $rows, 'next_after' => $next];
};

try {
    switch ($res) {
        case 'summary':
            $one = fn(string $q) => (int) $db->query($q)->fetchColumn();
            $out = ['data' => [
                'artists'   => $one("SELECT COUNT(*) FROM users WHERE role = 'artist'"),
                'buyers'    => $one("SELECT COUNT(*) FROM users WHERE role = 'collector'"),
                'artworks'  => $one('SELECT COUNT(*) FROM artworks WHERE is_draft = 0'),
                'in_gallery'=> $one("SELECT COUNT(*) FROM artworks WHERE is_approved = 1 AND is_draft = 0 AND status IN ('available','auction')"),
                'inquiries' => $one('SELECT COUNT(*) FROM artwork_inquiries'),
                'favorites' => $one('SELECT COUNT(*) FROM favorites'),
                'generated_at' => gmdate('c'),
            ]];
            break;
        case 'people':
            $out = $page("SELECT t.id, t.public_id, t.role, " . owUserStatusSql('t') . " AS status, t.full_name_en, t.full_name_ar, t.artist_name,
                                 t.email, t.phone, t.city, t.country, t.preferred_lang, t.is_verified, t.created_at, t.updated_at, t.last_login_at
                          FROM users t WHERE t.role IN ('artist','collector','institute')", [], 'updated_at');
            break;
        case 'artworks':
            $out = $page("SELECT t.id, t.public_id, u.public_id AS artist_public_id, t.title_en, t.title_ar, t.type, t.price, t.status AS sale_status,
                                 " . owArtworkStatusSql('t') . " AS status, t.rejected_reason, t.view_count, t.created_at, t.updated_at, t.sold_at,
                                 (SELECT COUNT(*) FROM favorites f WHERE f.artwork_id = t.id) AS favorites,
                                 (SELECT COUNT(*) FROM artwork_inquiries q WHERE q.artwork_id = t.id) AS inquiries
                          FROM artworks t JOIN users u ON u.id = t.artist_id WHERE 1", [], 'updated_at');
            break;
        case 'inquiries':
            $out = $page("SELECT t.id, t.public_id, a.public_id AS artwork_public_id, au.public_id AS artist_public_id, b.public_id AS buyer_public_id,
                                 t.status, t.buyer_name, t.buyer_phone, t.buyer_email, t.admin_notes, t.created_at, t.updated_at
                          FROM artwork_inquiries t LEFT JOIN artworks a ON a.id = t.artwork_id LEFT JOIN users au ON au.id = t.artist_id
                          LEFT JOIN users b ON b.id = t.buyer_id WHERE 1", [], 'updated_at');
            break;
        case 'favorites':
            $out = $page("SELECT t.id, u.public_id AS user_public_id, a.public_id AS artwork_public_id, t.created_at
                          FROM favorites t JOIN users u ON u.id = t.user_id JOIN artworks a ON a.id = t.artwork_id WHERE 1", [], 'created_at');
            break;
        case 'activity':
            $out = $page("SELECT t.id, t.public_id, t.actor_type, u.public_id AS actor_public_id, t.action, t.target_type,
                                 CASE t.target_type WHEN 'user' THEN (SELECT public_id FROM users WHERE id = t.target_id)
                                                    WHEN 'artwork' THEN (SELECT public_id FROM artworks WHERE id = t.target_id)
                                                    WHEN 'inquiry' THEN (SELECT public_id FROM artwork_inquiries WHERE id = t.target_id) END AS target_public_id,
                                 t.details, t.created_at
                          FROM activity_log t LEFT JOIN users u ON u.id = t.actor_id AND t.actor_type <> 'admin' WHERE t.action NOT IN ('bashro_read','bashro_denied')", [], 'created_at');
            break;
        default:
            owApiOut(400, ['error' => 'unknown_resource', 'resources' => ['summary', 'people', 'artworks', 'inquiries', 'favorites', 'activity']]);
    }
} catch (Throwable $e) {
    error_log('bashro_api: ' . $e->getMessage());
    owApiOut(500, ['error' => 'server']);
}
owLog('bashro_read', null, null, $res, ['type' => 'system', 'id' => null]);
owApiOut(200, ['resource' => $res] + $out);
