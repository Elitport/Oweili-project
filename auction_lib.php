<?php
/* =====================================================================
 *  auction_lib.php — "Oweili Auctions, Coming soon" (3 Nov 2026)
 *  The waiting list behind auctions.php: one row per email, the REAL count
 *  of people waiting (never a made-up number), the confirmation email with
 *  an unsubscribe link (every email to this list must use owAuctionUnsubUrl),
 *  unsubscribing (the row is deleted), the admin alert and Bashro Sales.
 *  Ready for later: owAuctionsLive() is the one switch the real auction page
 *  will use (site_settings 'auctions_live' = '1'); no bidding or payment now.
 *  Tables: auction_waitlist, activity_log, site_settings, users
 * ===================================================================== */
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/mail_lib.php';

function owAuctionReady(): bool { return owHasTable('auction_waitlist'); }
/* Real auctions are not built yet: this stays false until they are */
function owAuctionsLive(): bool {
    if (!is_file(__DIR__ . '/auction_room.php')) return false;   // the bidding page does not exist yet
    require_once __DIR__ . '/gallery_lib.php';
    return owSetting('auctions_live', '0') === '1';
}
/* How many people are really waiting */
function owAuctionCount(): int {
    if (!owAuctionReady()) return 0;
    try { return (int) getDB()->query('SELECT COUNT(*) FROM auction_waitlist')->fetchColumn(); } catch (Throwable $e) { return 0; }
}
function owAuctionUnsubUrl(array $row): string {
    return 'https://oweili.com/auctions.php?leave=' . (int) $row['id'] . '&t=' . rawurlencode((string) $row['unsub_token']);
}
/* Adds one email. Returns 'new' (added), 'already' (on the list already, nothing sent again) or 'error' */
function owAuctionJoin(string $name, string $email, string $lang, ?int $userId): string {
    if (!owAuctionReady()) return 'error';
    $email = strtolower(trim($email));
    try {
        $st = getDB()->prepare('INSERT IGNORE INTO auction_waitlist (email, name, lang, user_id, unsub_token) VALUES (:e, :n, :l, :u, :t)');
        $st->execute([':e' => $email, ':n' => $name, ':l' => $lang === 'ar' ? 'ar' : 'en', ':u' => $userId, ':t' => bin2hex(random_bytes(16))]);
        if ($st->rowCount() < 1) return 'already';
        $id = (int) getDB()->lastInsertId();
        $q = getDB()->prepare('SELECT * FROM auction_waitlist WHERE id = :id'); $q->execute([':id' => $id]); $row = $q->fetch();
        owLog('auction_signup', 'auction_waitlist', $id, '', $userId ? ['type' => 'buyer', 'id' => $userId] : ['type' => 'guest', 'id' => null]);
        owAuctionTell($row);
        try {   // the admin's grouped email alerts
            require_once __DIR__ . '/alert_lib.php';
            $n = owAuctionCount();
            owAdminAlert('auction', 'New auction waiting-list sign-up (' . $n . ' waiting now)', 'تسجيل جديد في قائمة انتظار المزاد (' . $n . ' بالانتظار الآن)', 'admin.php#auctions');
        } catch (Throwable $e) { error_log('auction alert: ' . $e->getMessage()); }
        try {   // Bashro Sales, as a lead (only when the door key is in config.php)
            require_once __DIR__ . '/bashro_send.php';
            $who = $name !== '' ? $name : (strstr($email, '@', true) ?: 'Auction waiting list');
            owBashroQueue('quote', ['name' => $who, 'email' => $email, 'service' => 'Oweili Auctions waiting list', 'lang' => $row['lang'],
                'message' => 'Joined the waiting list for the first Oweili auction' . ($name === '' ? ' (no name given)' : '') . '. Wants an email when the first auction opens.']);
        } catch (Throwable $e) { error_log('bashro auction: ' . $e->getMessage()); }
        return 'new';
    } catch (Throwable $e) { error_log('owAuctionJoin: ' . $e->getMessage()); return 'error'; }
}
/* The confirmation email (both languages) with its unsubscribe link */
function owAuctionTell(array $row): void {
    try {
        $hi = $row['name'] !== '' ? $row['name'] : '';
        $leave = owAuctionUnsubUrl($row);
        $body = ($hi !== '' ? 'Hello ' . $hi . ",\r\n\r\n" : "Hello,\r\n\r\n")
              . "You are on the list for Oweili Auctions. Rare original artworks are coming to auction, and you will be among the first to know when the first one opens.\r\n"
              . "We will only use this email to tell you about Oweili auctions.\r\n\r\n"
              . "Not for you any more? Unsubscribe here: $leave\r\n\r\n"
              . ($hi !== '' ? 'أهلاً ' . $hi . "،\r\n\r\n" : "أهلاً،\r\n\r\n")
              . "أنت الآن في قائمة مزادات أويلي. أعمال فنية أصلية ونادرة في طريقها إلى المزاد، وستكون من أول من يعرف حين يُفتح أول مزاد.\r\n"
              . "لن نستخدم هذا البريد إلا لإخبارك بمزادات أويلي.\r\n\r\n"
              . "لم يعد يناسبك؟ ألغِ الاشتراك من هنا: $leave\r\n";
        owSendMail((string) $row['email'], 'You are on the list for Oweili Auctions · أنت في قائمة مزادات أويلي', $body);
    } catch (Throwable $e) { error_log('owAuctionTell: ' . $e->getMessage()); }
}
/* The row behind an unsubscribe link, or null */
function owAuctionByToken(int $id, string $token): ?array {
    if (!owAuctionReady() || $id <= 0 || !preg_match('/^[a-f0-9]{32}$/', $token)) return null;
    $st = getDB()->prepare('SELECT * FROM auction_waitlist WHERE id = :id'); $st->execute([':id' => $id]);
    $r = $st->fetch();
    return ($r && hash_equals((string) $r['unsub_token'], $token)) ? $r : null;
}
/* Unsubscribe: the email is deleted from the list */
function owAuctionLeave(array $row): bool {
    $st = getDB()->prepare('DELETE FROM auction_waitlist WHERE id = :id'); $st->execute([':id' => (int) $row['id']]);
    if ($st->rowCount() < 1) return false;
    owLog('auction_unsubscribe', 'auction_waitlist', (int) $row['id'], 'email removed from the list', ['type' => 'guest', 'id' => null]);
    return true;
}
/* For the admin: everyone waiting, newest first */
function owAuctionList(int $limit = 5000): array {
    if (!owAuctionReady()) return [];
    $st = getDB()->prepare('SELECT id, email, name, lang, user_id, created_at FROM auction_waitlist ORDER BY id DESC LIMIT :l');
    $st->bindValue(':l', $limit, PDO::PARAM_INT); $st->execute();
    return $st->fetchAll();
}
