<?php
/* =====================================================================
 *  unsub_lib.php — one-click "stop Oweili news" (owner, 27 Nov 2026)
 *  Every news email carries a personal link (unsubscribe.php?u=ID&t=TOKEN)
 *  and the hidden List-Unsubscribe / List-Unsubscribe-Post headers. The
 *  token is a signature of that person's id and email with a secret kept in
 *  site_settings (news_unsub_secret, made once), so a link works only for
 *  that one person and stops working if their email changes. No sign-in.
 *  It only switches users.notify_news off; service emails are not affected.
 *  Tables: users (notify_news), site_settings, activity_log
 * ===================================================================== */
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/gallery_lib.php';   // owSetting / owSetSetting

function owNewsUnsubSecret(): string {
    $s = (string) owSetting('news_unsub_secret', '');
    if (strlen($s) < 32) { $s = bin2hex(random_bytes(32)); owSetSetting('news_unsub_secret', $s); }
    return $s;
}
function owNewsUnsubToken(int $uid, string $email): string {
    return substr(hash_hmac('sha256', 'news-unsub|' . $uid . '|' . strtolower(trim($email)), owNewsUnsubSecret()), 0, 40);
}
function owNewsUnsubUrl(int $uid, string $email): string {
    return 'https://oweili.com/unsubscribe.php?u=' . $uid . '&t=' . owNewsUnsubToken($uid, $email);
}
/* The person behind a link, or null when the link is wrong */
function owNewsUnsubUser(int $uid, string $token): ?array {
    if ($uid <= 0 || !preg_match('/^[a-f0-9]{40}$/', $token)) return null;
    try {
        $st = getDB()->prepare('SELECT id, email' . (owHasColumn('users', 'notify_news') ? ', notify_news' : ', 1 AS notify_news') . ' FROM users WHERE id = ? LIMIT 1');
        $st->execute([$uid]); $u = $st->fetch();
    } catch (Throwable $e) { return null; }
    if (!$u || !hash_equals(owNewsUnsubToken((int) $u['id'], (string) $u['email']), $token)) return null;
    return $u;
}
function owNewsUnsubscribe(array $u, string $how): void {
    if (!owHasColumn('users', 'notify_news')) return;
    getDB()->prepare('UPDATE users SET notify_news = 0 WHERE id = ?')->execute([(int) $u['id']]);
    owLog('news_unsubscribe', 'user', (int) $u['id'], $how, ['type' => 'guest', 'id' => null]);
}
