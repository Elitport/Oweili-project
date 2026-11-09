<?php
/* =====================================================================
 *  bashro_send.php — Oweili sends to Bashro's receive-only door
 *  (https://bashro.com/in/), following docs/website-door-guide.md in the
 *  Bashro project. Oweili is project 3 in Bashro.
 *
 *  Needs, in config.php on the server only (never in GitHub):
 *    define('BASHRO_DOOR_SITE', '3');
 *    define('BASHRO_DOOR_KEY', '<the 64-character key from Bashro>');
 *  Without them nothing is sent and nothing shows.
 *
 *  What is sent (only the guide's fields; never passwords, card, ID or bank numbers):
 *    "I'm Interested"      → kind quote  (Bashro Sales lead)          ON
 *    Gift requests         → kind quote  (Sales)                      ON
 *    Commission requests   → kind quote  (Sales; never the photos)    ON
 *    Support page form     → kind contact (Bashro Customer Service)   ON (9 Nov 2026)
 *    New artist / buyer    → kind signup                              OFF until Bashro accepts it:
 *                                                                     define('BASHRO_DOOR_SIGNUPS', true)
 *    Approvals waiting     → kind approval (no personal details)      OFF until Bashro accepts it:
 *                                                                     define('BASHRO_DOOR_APPROVALS', true)
 *  Sent AFTER the visitor's page is delivered, so nobody waits; the visitor
 *  never sees Bashro's answer. Signed with HMAC-SHA256 (site, time, nonce,
 *  exact body). On 429 / 503 it is tried again later, at most 3 times in
 *  all (after 1 minute, then 5), from a small private queue in .logs/
 *  (removed once sent or given up). Only the kind and the status number are
 *  written to the activity log (bashro_send), never the text or the key.
 *  Tables: activity_log (status only), artworks, users, gift_requests, commissions (read)
 * ===================================================================== */
require_once __DIR__ . '/platform_lib.php';

const OW_BASHRO_DOOR = 'https://bashro.com/in/';
const OW_BASHRO_FIELDS = [
    'contact'  => ['name' => 120, 'email' => 160, 'phone' => 30, 'subject' => 200, 'message' => 5000, 'lang' => 2],   /* door guide section 4: Customer Service ticket */
    'quote'    => ['name' => 120, 'email' => 160, 'phone' => 30, 'company' => 120, 'service' => 120, 'city' => 60, 'message' => 5000, 'lang' => 2],
    'signup'   => ['role' => 10, 'name' => 120, 'email' => 160, 'phone' => 30, 'city' => 60, 'joined' => 10],   /* door guide section 4b */
    'approval' => ['ref' => 60, 'type' => 20, 'title' => 120, 'link' => 300],
    'account_deleted' => ['email' => 160],
];

/* The two values from config.php, cleaned (a stray space, line break or capital letters never break the connection) */
function owBashroSite(): string { return defined('BASHRO_DOOR_SITE') ? trim((string) BASHRO_DOOR_SITE) : ''; }
function owBashroKey(): string  { return defined('BASHRO_DOOR_KEY') ? strtolower(trim((string) BASHRO_DOOR_KEY)) : ''; }
function owBashroReady(): bool {
    return preg_match('/^\d{1,6}$/', owBashroSite()) === 1 && preg_match('/^[0-9a-f]{64}$/', owBashroKey()) === 1;
}
function owBashroKindOn(string $kind): bool {
    if (!owBashroReady()) return false;
    if ($kind === 'signup')   return defined('BASHRO_DOOR_SIGNUPS') && BASHRO_DOOR_SIGNUPS === true;
    if ($kind === 'approval') return defined('BASHRO_DOOR_APPROVALS') && BASHRO_DOOR_APPROVALS === true;
    if ($kind === 'account_deleted') return true;   // Saudi PDPL: always told once the door is set up
    return $kind === 'quote' || $kind === 'contact';
}
/* Only the allowed fields, as plain strings within their limits; empty ones are left out */
function owBashroClean(string $kind, array $m): array {
    $out = ['kind' => $kind];
    foreach (OW_BASHRO_FIELDS[$kind] as $k => $max) {
        if (!isset($m[$k])) continue;
        $v = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', (string) $m[$k]));
        if ($v === '') continue;
        $out[$k] = mb_substr($v, 0, $max);
    }
    if (isset($out['lang']) && !in_array($out['lang'], ['ar', 'en'], true)) unset($out['lang']);
    return $out;
}

/* Queue one message; it is sent after the page is delivered */
function owBashroQueue(string $kind, array $message): void {
    if (!owBashroKindOn($kind) || !isset(OW_BASHRO_FIELDS[$kind])) return;
    $GLOBALS['OW_BASHRO_OUT'][] = ['body' => json_encode(owBashroClean($kind, $message), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'kind' => $kind, 'tries' => 0];
    static $planned = false;
    if (!$planned) { $planned = true; register_shutdown_function('owBashroAfterPage'); }
}
function owBashroAfterPage(): void {
    if (function_exists('litespeed_finish_request')) litespeed_finish_request();
    elseif (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
    ignore_user_abort(true);
    @set_time_limit(60);
    foreach ((array) ($GLOBALS['OW_BASHRO_OUT'] ?? []) as $item) owBashroDeliver($item);
    $GLOBALS['OW_BASHRO_OUT'] = [];
    owBashroRetryDue();
}

/* One signed POST. Returns the status number (0 = no answer). */
function owBashroPost(string $body): int {
    $site = owBashroSite(); $key = owBashroKey();
    $time = (string) time(); $nonce = bin2hex(random_bytes(16));
    $sig = hash_hmac('sha256', $site . "\n" . $time . "\n" . $nonce . "\n" . $body, $key);
    $url = defined('BASHRO_DOOR_URL') ? (string) BASHRO_DOOR_URL : OW_BASHRO_DOOR;   // only for local tests
    if (!function_exists('curl_init')) return 0;
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS | (defined('BASHRO_DOOR_URL') ? CURLPROTO_HTTP : 0),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', "X-Bashro-Site: $site", "X-Bashro-Time: $time", "X-Bashro-Nonce: $nonce", "X-Bashro-Signature: $sig"]]);
    curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return $status;
}
function owBashroDeliver(array $item): void {
    if (!owBashroReady()) return;
    $status = 0;
    try { $status = owBashroPost((string) $item['body']); } catch (Throwable $e) { error_log('bashro send: ' . $e->getMessage()); }
    $tries = (int) $item['tries'] + 1;
    owLog('bashro_send', 'bashro', null, $item['kind'] . ' ' . ($status ?: 'no answer') . ($tries > 1 ? ' (try ' . $tries . ')' : ''), ['type' => 'system', 'id' => null]);
    if (in_array($status, [429, 503], true) && $tries < 3) {   // the guide: retry only on 429 or 503
        $item['tries'] = $tries; $item['due'] = time() + ($tries === 1 ? 60 : 300);
        owBashroQueueFile(function (array $q) use ($item) { $q[] = $item; return $q; });
    }
}
/* The private retry queue (.logs/bashro_queue.json, blocked for visitors) */
function owBashroQueueFile(callable $change): array {
    $dir = __DIR__ . '/.logs'; if (!is_dir($dir)) @mkdir($dir, 0700, true);
    $f = $dir . '/bashro_queue.json';
    $h = @fopen($f, 'c+'); if (!$h) return [];
    flock($h, LOCK_EX);
    $q = json_decode((string) stream_get_contents($h), true); if (!is_array($q)) $q = [];
    $new = array_values($change($q));
    ftruncate($h, 0); rewind($h); fwrite($h, json_encode($new, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    flock($h, LOCK_UN); fclose($h); @chmod($f, 0600);
    if (!$new) @unlink($f);
    return $q;
}
function owBashroRetryDue(): void {
    if (!is_file(__DIR__ . '/.logs/bashro_queue.json')) return;
    $due = [];
    owBashroQueueFile(function (array $q) use (&$due) {
        $keep = [];
        foreach ($q as $it) { if ((int) ($it['due'] ?? 0) <= time()) $due[] = $it; elseif (count($keep) < 200) $keep[] = $it; }
        return $keep;
    });
    foreach ($due as $it) owBashroDeliver($it);
}

/* The admin's "Send a test to Bashro" (Settings): one clearly marked test lead, sent now, its answer returned */
function owBashroTest(): int {
    if (!owBashroReady()) return 0;
    $body = json_encode(owBashroClean('quote', ['name' => 'Oweili test (please ignore)', 'email' => owContactEmail(), 'service' => 'TEST: I am interested', 'lang' => 'en',
        'message' => 'TEST from oweili.com Settings: one test "I am Interested" to check the connection. Please ignore or delete it.']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $status = 0;
    try { $status = owBashroPost($body); } catch (Throwable $e) { error_log('owBashroTest: ' . $e->getMessage()); }
    owLog('bashro_send', 'bashro', null, 'quote ' . ($status ?: 'no answer') . ' (test from Settings)', ['type' => 'admin', 'id' => null]);
    return $status;
}

/* ---------------- What Oweili sends ---------------- */
/* Links in the messages always point to the real site (never to whatever host a visitor sent) */
function owBashroHost(): string { return defined('BASHRO_DOOR_LINK_BASE') ? rtrim((string) BASHRO_DOOR_LINK_BASE, '/') : 'https://oweili.com'; }

/* The standard size for Sales and pricing (size_lib.php): ". Size: 50 × 70 cm, Medium (m), portrait" */
function owBashroSizeText(array $row): string {
    if (empty($row['size_code'])) return '';
    require_once __DIR__ . '/size_lib.php';
    $c = owSizeCheck((string) $row['size_code'], (string) ($row['orientation'] ?? ''));
    if (!$c) return '';
    return '. Size: ' . $c[3] . ' × ' . $c[4] . ' cm, ' . owSizeGroups()[$c[1]][0] . ' (' . $c[1] . '), ' . $c[2];
}

/* "I'm Interested" → Sales */
function owBashroInquiry(array $aw, array $buyer, string $lang = 'en'): void {
    owBashroQueue('quote', ['name' => $buyer['name'] ?? '', 'email' => $buyer['email'] ?? '', 'phone' => $buyer['phone'] ?? '',
        'service' => (string) $aw['title_en'], 'lang' => $lang,
        'message' => 'Interested in: ' . $aw['title_en'] . ' by ' . ($aw['artist_en'] ?? '') . ', ' . number_format((float) $aw['price']) . ' SAR' . owBashroSizeText($aw) . ', '
                   . owBashroHost() . '/artwork.php?id=' . (int) $aw['id']]);
}
/* Oweili Gifts request → Sales (the recipient's city only, never their address or phone) */
function owBashroGift(int $giftId): void {
    if (!owBashroKindOn('quote')) return;
    try {
        $st = getDB()->prepare('SELECT g.*, a.title_en, a.price' . (owHasColumn('artworks', 'size_code') ? ', a.size_code AS aw_size_code, a.orientation AS aw_orientation' : '') . ' FROM gift_requests g LEFT JOIN artworks a ON a.id = g.artwork_id WHERE g.id = :id');
        $st->execute([':id' => $giftId]); $g = $st->fetch();
        if (!$g) return;
        require_once __DIR__ . '/request_lib.php';
        $occ = owGiftOccasions()[$g['occasion']]['en'] ?? $g['occasion'];
        $what = $g['mode'] === 'ready' ? 'ready artwork "' . $g['title_en'] . '" (' . number_format((float) $g['price']) . ' SAR)' : 'custom artwork' . ($g['budget_sar'] !== null ? ', budget ' . number_format((float) $g['budget_sar']) . ' SAR' : '') . ': ' . rtrim(mb_substr((string) $g['custom_brief'], 0, 1500), '. ');
        $what .= $g['mode'] === 'ready' ? owBashroSizeText(['size_code' => $g['aw_size_code'] ?? '', 'orientation' => $g['aw_orientation'] ?? '']) : owBashroSizeText($g);
        owBashroQueue('quote', ['name' => $g['buyer_name'], 'email' => $g['buyer_email'], 'phone' => $g['buyer_phone'], 'service' => 'Oweili Gift: ' . $occ, 'city' => $g['recipient_city'], 'lang' => $g['lang'],
            'message' => 'Gift request #' . (int) $g['id'] . ' (' . $occ . '): ' . $what . '. Delivery ' . $g['delivery_date'] . ' to ' . $g['recipient_city']
                       . '. Wrapping: ' . ((int) $g['wrapping'] ? 'yes' : 'no') . '. Surprise (hide the price): ' . ((int) $g['surprise'] ? 'yes' : 'no')
                       . '. Details for the team: ' . owBashroHost() . '/admin_gifts.php?id=' . (int) $g['id']]);
    } catch (Throwable $e) { error_log('owBashroGift: ' . $e->getMessage()); }
}
/* Private commission request → Sales (never the photos) */
function owBashroCommission(int $cid): void {
    if (!owBashroKindOn('quote')) return;
    try {
        require_once __DIR__ . '/commission_lib.php';
        $c = owCommById($cid); if (!$c) return;
        $subj = owCommSubjects()[$c['subject']][0] ?? $c['subject'];
        owBashroQueue('quote', ['name' => $c['buyer_name'], 'email' => $c['buyer_email'], 'phone' => $c['buyer_phone'], 'service' => 'Private commission: ' . $subj, 'lang' => $c['lang'],
            'message' => 'Private commission ' . $c['ref_no'] . ' (' . $subj . '): ' . rtrim(mb_substr((string) $c['brief'], 0, 2500), '. ')
                       . owBashroSizeText($c) . ($c['budget_sar'] !== null ? '. Budget ' . number_format((float) $c['budget_sar']) . ' SAR' : '') . ($c['wanted_by'] ? '. Needed by ' . $c['wanted_by'] : '')
                       . '. Photos stay on Oweili (private). Details for the team: ' . owBashroHost() . '/admin_commissions.php?id=' . (int) $c['id']]);
    } catch (Throwable $e) { error_log('owBashroCommission: ' . $e->getMessage()); }
}
/* New artist or buyer account → signup (OFF until Bashro accepts the kind) */
function owBashroSignup(int $userId, string $role): void {
    if (!owBashroKindOn('signup')) return;
    try {
        $st = getDB()->prepare('SELECT * FROM users WHERE id = :id'); $st->execute([':id' => $userId]); $u = $st->fetch();
        if (!$u) return;
        $joined = substr((string) ($u['created_at'] ?? ''), 0, 10);
        owBashroQueue('signup', ['role' => $role === 'artist' ? 'artist' : 'buyer', 'name' => ($u['full_name_en'] ?: $u['full_name_ar']), 'email' => $u['email'], 'phone' => $u['phone'] ?? '',
            'city' => $u['city'] ?? '', 'joined' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $joined) ? $joined : date('Y-m-d')]);
    } catch (Throwable $e) { error_log('owBashroSignup: ' . $e->getMessage()); }
}
/* Something waiting for approval → approval (OFF until Bashro accepts the kind). No personal details. */
/* $type: artwork_new, artwork_changed, profile_change or artist_new (door guide section 4b) */
function owBashroApproval(string $type, string $title, string $link, string $ref): void {
    if (!owBashroKindOn('approval')) return;
    if (!in_array($type, ['artwork_new', 'artwork_changed', 'profile_change', 'artist_new'], true)) return;
    $ref = preg_replace('/[^A-Za-z0-9._:-]/', '-', $ref);
    owBashroQueue('approval', ['ref' => $ref, 'type' => $type, 'title' => $title, 'link' => owBashroHost() . '/' . ltrim($link, '/')]);
}
/* A person's account was deleted → Bashro removes them too (Saudi PDPL). Call it wherever an account is deleted */
function owBashroAccountDeleted(string $email): void {
    $email = strtolower(trim($email));
    if ($email === '' || !owBashroKindOn('account_deleted')) return;
    owBashroQueue('account_deleted', ['email' => $email]);
}

/* The Support page form "Write to Us" (support_send.php, owner 9 Nov 2026) → Bashro Customer Service ticket */
function owBashroContact(string $name, string $email, string $phone, string $message, string $lang = 'en'): void {
    if (!owBashroKindOn('contact')) return;
    owBashroQueue('contact', ['name' => $name, 'email' => $email, 'phone' => $phone, 'subject' => 'Oweili Support page', 'message' => $message, 'lang' => $lang === 'ar' ? 'ar' : 'en']);
}
