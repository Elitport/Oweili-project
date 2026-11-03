<?php
/* =====================================================================
 *  visitor_lib.php — the ONE place that reads a visitor's address.
 *
 *  config.php's clientIp() believes the X-Forwarded-For header, which any
 *  visitor can fake, so rate limits could be dodged. On Hostinger the real
 *  visitor address is REMOTE_ADDR (checked on the live server, 3 Nov 2026:
 *  a faked X-Forwarded-For was ignored by REMOTE_ADDR, and X-Real-IP was
 *  equal to it). Every Oweili rate limit, log code and sign-in record uses
 *  owVisitorIp() from this file. Never call clientIp() in new code.
 *
 *    owVisitorIp()          the real visitor address (never a header)
 *    owLoginBlocked()       too many wrong sign-ins from this address
 *    owLoginRecord()        writes one sign-in try (login_attempts)
 *    owLoginStart($uid)     signs in (config.php loginUser) and saves the
 *                           real address on the new session row
 *    owAdminGateBlocked()   too many wrong admin passwords (admin.php)
 *    owAdminGateRecord()    writes one admin password try
 *  Tables: login_attempts, sessions
 * ===================================================================== */

if (!function_exists('owVisitorIp')) {

function owVisitorIp(): string {
    $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    return filter_var($ip, FILTER_VALIDATE_IP) !== false ? substr($ip, 0, 45) : '';
}

function owLoginMax(): int    { return defined('LOGIN_MAX_ATTEMPTS') ? (int) LOGIN_MAX_ATTEMPTS : 5; }
function owLoginWindow(): int { return defined('LOGIN_WINDOW_SECONDS') ? (int) LOGIN_WINDOW_SECONDS : 600; }

/* Wrong tries from this address in the window; $email = '' counts sign-in tries, a tag counts that tag only */
function owAttemptFails(?string $only = null, ?string $not = null): int {
    try {
        $sql = 'SELECT COUNT(*) FROM login_attempts WHERE ip_address = :ip AND success = 0
                AND created_at > (NOW() - INTERVAL :win SECOND)';
        if ($only !== null) $sql .= ' AND email = :only';
        if ($not !== null)  $sql .= ' AND email <> :not';
        $st = getDB()->prepare($sql);
        $st->bindValue(':ip', owVisitorIp());
        $st->bindValue(':win', owLoginWindow(), PDO::PARAM_INT);
        if ($only !== null) $st->bindValue(':only', $only);
        if ($not !== null)  $st->bindValue(':not', $not);
        $st->execute();
        return (int) $st->fetchColumn();
    } catch (Throwable $e) { error_log('visitor_lib: ' . $e->getMessage()); return 0; }
}

function owAttemptWrite(string $email, bool $ok): void {
    try {
        getDB()->prepare('INSERT INTO login_attempts (ip_address, email, success) VALUES (:ip, :e, :ok)')
               ->execute([':ip' => owVisitorIp(), ':e' => mb_substr($email, 0, 190), ':ok' => $ok ? 1 : 0]);
    } catch (Throwable $e) { error_log('visitor_lib: ' . $e->getMessage()); }
}

if (!defined('OW_ADMIN_GATE_TAG')) define('OW_ADMIN_GATE_TAG', '(admin.php password)');

function owLoginBlocked(): bool { return owAttemptFails(null, OW_ADMIN_GATE_TAG) >= owLoginMax(); }
function owLoginRecord(string $email, bool $ok): void { owAttemptWrite($email, $ok); }

function owLoginStart(int $userId): string {
    $token = loginUser($userId);
    try {
        getDB()->prepare('UPDATE sessions SET ip_address = :ip WHERE session_token = :t')
               ->execute([':ip' => owVisitorIp(), ':t' => $token]);
    } catch (Throwable $e) { error_log('visitor_lib: ' . $e->getMessage()); }
    return $token;
}

function owAdminGateBlocked(): bool { return owAttemptFails(OW_ADMIN_GATE_TAG) >= owLoginMax(); }
function owAdminGateRecord(bool $ok): void { owAttemptWrite(OW_ADMIN_GATE_TAG, $ok); }

}
