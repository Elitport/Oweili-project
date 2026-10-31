<?php
/* =====================================================================
 *  live_check.php — TEMPORARY read-only check of the live site, removed
 *  straight after use (31 Oct 2026, after the owner changed the passwords).
 *    status        database, email box sign-in (never shows the password),
 *                  server name and location details, recent private errors
 *    mail_test     sends one test email to contact@elitport.com
 *    cleanup_test  removes only the test buyer contact+oweili-qa@elitport.com
 *  Only with the one-time key (only its SHA-256 is stored here). POST only.
 *  Switches itself off at OW_LC_UNTIL.
 * ===================================================================== */
const OW_LC_UNTIL    = 1793438201;
const OW_LC_KEY_HASH = '20bc9291add7c63cb212b75b77ee8d744dd454d0ef45ce673c3c8fd8c8412e03';
if (time() > OW_LC_UNTIL) { http_response_code(404); exit; }
require_once 'config.php';
require_once __DIR__ . '/mail_lib.php';
$keyOk = is_string($_POST['key'] ?? null) && hash_equals(OW_LC_KEY_HASH, hash('sha256', (string) $_POST['key']));
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !$keyOk) { http_response_code(404); exit; }
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');
$say = function (string $s) { echo $s, "\n"; };
$step = (string) ($_POST['step'] ?? 'status');

if ($step === 'status') {
    try { $say('database: ' . (getDB()->query('SELECT 1')->fetchColumn() ? 'connects' : 'no')); } catch (Throwable $e) { $say('database: FAILED'); }
    $say('database and email passwords different: ' . ((defined('DB_PASS') && defined('SMTP_PASS') && !hash_equals((string) DB_PASS, (string) SMTP_PASS)) ? 'yes' : 'NO'));
    $log = []; $msg = owMailMessage('contact@elitport.com', 'x', 'x', false); $ok = false;
    foreach ([465, 587] as $port) { if (owSmtpSend('contact@elitport.com', $msg, owMailSetting('SMTP_HOST', 'smtp.hostinger.com'), $port, $log, true)) { $ok = true; break; } }
    $say('email box signs in: ' . ($ok ? 'yes' : 'NO') . ' (' . implode(' | ', $log) . ')');
    $say('config_setup.php on server: ' . (is_file(__DIR__ . '/config_setup.php') ? 'yes' : 'no'));
    $say('config.php last changed: ' . gmdate('Y-m-d H:i', (int) filemtime(__DIR__ . '/config.php')) . ' UTC');
    $say('server name: ' . gethostname());
    $say('server address: ' . ($_SERVER['SERVER_ADDR'] ?? '?'));
    $out = @file_get_contents('https://api.ipify.org', false, stream_context_create(['http' => ['timeout' => 6]]));
    $say('server outgoing address: ' . ($out ?: '?'));
    $say('server time zone: ' . date_default_timezone_get() . ' · PHP ' . PHP_VERSION);
    $lf = __DIR__ . '/.logs/php-errors.log';
    if (is_file($lf)) {
        $lines = array_slice(array_filter(explode("\n", (string) file_get_contents($lf, false, null, max(0, filesize($lf) - 20000))), 'strlen'), -25);
        $say('--- last private error lines (' . count($lines) . '):');
        foreach ($lines as $l) $say('  ' . preg_replace('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+/', '[email]', $l));
    } else $say('private error log: empty');
    exit;
}
if ($step === 'mail_test') {
    $log = [];
    $ok = owSendMail('contact@elitport.com', 'Oweili email check after the password change · فحص البريد', "This is a test from Oweili after the new email password was saved. No action needed.\r\nرسالة تجريبية من أويلي بعد حفظ كلمة مرور البريد الجديدة. لا حاجة لأي إجراء.\r\n", false, $log);
    $say('test email: ' . ($ok ? 'sent' : 'FAILED') . ' (' . implode(' | ', $log) . ')');
    exit;
}
if ($step === 'cleanup_test') {
    $db = getDB(); $email = 'contact+oweili-qa@elitport.com';
    $st = $db->prepare("SELECT id FROM users WHERE email = ? AND role = 'collector'"); $st->execute([$email]);
    $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    if (count($ids) !== 1) { $say('Nothing removed: found ' . count($ids) . ' test accounts'); exit; }
    $id = $ids[0];
    foreach (['DELETE FROM favorites WHERE user_id = ?', 'DELETE FROM artwork_inquiries WHERE buyer_id = ? OR buyer_email = ' . $db->quote($email),
              "DELETE FROM activity_log WHERE actor_id = ? AND actor_type = 'buyer'", 'DELETE FROM sessions WHERE user_id = ?',
              'DELETE FROM login_attempts WHERE email = ' . $db->quote($email) . ' AND ? > 0', 'DELETE FROM users WHERE id = ?'] as $sql) {
        $d = $db->prepare($sql); $d->execute([$id]); $say(substr($sql, 0, 36) . '... ' . $d->rowCount() . ' rows');
    }
    $say('CLEANUP OK'); exit;
}
$say('Unknown step');
