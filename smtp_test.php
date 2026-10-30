<?php
/* =====================================================================
 *  smtp_test.php — talks to the mail server step by step and prints
 *  every answer it gives, then sends one test email to contact@elitport.com.
 *
 *  The mailbox and password come from config.php on the server
 *  (SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS). They are never written
 *  in this file, so they never reach GitHub. The password is never printed.
 *
 *  How to run it (no browser needed):
 *    - Hostinger SSH:        php ~/public_html/smtp_test.php
 *    - hPanel → Advanced → Cron Jobs → "Custom PHP": public_html/smtp_test.php
 *      (run once; the output is emailed / shown in the cron log)
 *  In a browser it only works for an admin signed in on admin.php;
 *  everyone else gets 404, so it cannot be used to send spam.
 * ===================================================================== */
require_once __DIR__ . '/config.php';

$isCli = PHP_SAPI === 'cli';
if (!$isCli) {
    $adminHash = defined('ADMIN_HASH') ? (string) ADMIN_HASH : '';
    $adminSig  = $adminHash !== '' ? hash('sha256', $adminHash) : '';
    if (!(!empty($_SESSION['is_admin']) && $adminSig !== '' && hash_equals($adminSig, (string) ($_SESSION['admin_sig'] ?? '')))) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: text/plain; charset=UTF-8');
    header('X-Robots-Tag: noindex');
}

$TO   = 'contact@elitport.com';
$host = defined('SMTP_HOST') ? (string) SMTP_HOST : '';
$port = defined('SMTP_PORT') ? (int) SMTP_PORT : 465;
$user = defined('SMTP_USER') ? (string) SMTP_USER : '';
$pass = defined('SMTP_PASS') ? (string) SMTP_PASS : '';
$from = defined('SMTP_FROM') ? (string) SMTP_FROM : $user;

function say(string $s): void { echo $s, "\n"; @flush(); }

say('Oweili SMTP test · ' . date('Y-m-d H:i:s T'));
say("Server: $host  Port: $port (" . ($port === 465 ? 'SSL' : 'STARTTLS') . ")  Mailbox: $user  Password: " . ($pass !== '' ? 'set (' . strlen($pass) . ' characters)' : 'MISSING'));
say("Send to: $TO");
say(str_repeat('-', 60));
if ($host === '' || $user === '' || $pass === '') { say('STOP: SMTP_HOST, SMTP_USER or SMTP_PASS is missing in config.php.'); exit(1); }

$fp = @stream_socket_client(($port === 465 ? 'ssl://' : 'tcp://') . $host . ':' . $port, $errno, $errstr, 20);
if (!$fp) { say("RESULT: could not connect to $host:$port: $errstr ($errno)"); exit(1); }
stream_set_timeout($fp, 20);

$read = function () use ($fp): string {
    $all = '';
    while (($line = fgets($fp, 1024)) !== false) {
        $all .= $line;
        if (strlen($line) < 4 || $line[3] === ' ') break;
    }
    return $all;
};
/* send one line, print it (password hidden) and the server's answer */
$step = function (?string $line, string $show, array $okCodes) use ($fp, $read): bool {
    if ($line !== null) { fwrite($fp, $line . "\r\n"); say('>> ' . $show); }
    $r = $read();
    foreach (preg_split('/\r?\n/', trim($r)) as $l) say('<< ' . ($l === '' ? '(no answer)' : $l));
    return in_array((int) substr($r, 0, 3), $okCodes, true);
};
$fail = function (string $why) use ($fp): void {
    @fwrite($fp, "QUIT\r\n"); fclose($fp);
    say(str_repeat('-', 60)); say('RESULT: FAILED at ' . $why); exit(1);
};

$helo = 'oweili.com';
if (!$step(null, '', [220]))                         $fail('the greeting');
if (!$step('EHLO ' . $helo, 'EHLO ' . $helo, [250])) $fail('EHLO');
if ($port !== 465) {
    if (!$step('STARTTLS', 'STARTTLS', [220]))       $fail('STARTTLS');
    if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) $fail('the secure (TLS) handshake');
    if (!$step('EHLO ' . $helo, 'EHLO ' . $helo, [250])) $fail('EHLO after STARTTLS');
}
if (!$step('AUTH LOGIN', 'AUTH LOGIN', [334]))                       $fail('AUTH LOGIN');
if (!$step(base64_encode($user), '(mailbox name: ' . $user . ')', [334])) $fail('the mailbox name');
if (!$step(base64_encode($pass), '(password hidden)', [235]))         $fail('the password: the mailbox does not exist, or the password is wrong');
if (!$step('MAIL FROM:<' . $from . '>', 'MAIL FROM:<' . $from . '>', [250])) $fail('MAIL FROM (the "from" address must be the mailbox itself)');
if (!$step('RCPT TO:<' . $TO . '>', 'RCPT TO:<' . $TO . '>', [250, 251]))    $fail('RCPT TO');
if (!$step('DATA', 'DATA', [354]))                                    $fail('DATA');

$body = "This is a test from oweili.com, sent by smtp_test.php.\r\nIf you can read it, the mailbox works.\r\n\r\n"
      . "هذه رسالة تجريبية من oweili.com. إن وصلتك، فالبريد يعمل.\r\n";
$msg = implode("\r\n", [
    'From: Oweili <' . $from . '>',
    'To: <' . $TO . '>',
    'Subject: =?UTF-8?B?' . base64_encode('Oweili SMTP test · اختبار البريد') . '?=',
    'Date: ' . date('r'),
    'Message-ID: <' . bin2hex(random_bytes(12)) . '@oweili.com>',
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: base64',
]) . "\r\n\r\n" . rtrim(chunk_split(base64_encode($body), 76, "\r\n")) . "\r\n.";
if (!$step($msg, '(the message itself)', [250])) $fail('sending the message');
$step('QUIT', 'QUIT', [221]);
fclose($fp);
say(str_repeat('-', 60));
say("RESULT: SUCCESS. The server accepted the email for $TO. Check the inbox and the spam folder.");
