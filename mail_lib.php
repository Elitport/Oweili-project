<?php
/* =====================================================================
 *  mail_lib.php — sends the site's emails (password reset, account
 *  verification, welcome) through the Hostinger mailbox.
 *
 *  owSendMail($to, $subject, $body, $isHtml = false, &$log = null)
 *    1. Talks to the mail server directly (SMTP) with the mailbox set in
 *       config.php: SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS and
 *       optionally SMTP_FROM / SMTP_FROM_NAME. Port 465 (SSL) and 587
 *       (STARTTLS) are both tried, so a blocked port does not stop mail.
 *    2. If that fails, tries the older sendEmail() from config.php.
 *    3. Last, PHP mail() from the same mailbox address.
 *  Every failure is written to the PHP error log with the server's reply
 *  (never the password). $log receives the same notes for the admin
 *  Email check (admin.php?mail_check=1).
 *  No secrets live in this file: they stay in config.php on the server.
 * ===================================================================== */

require_once __DIR__ . '/ow_paths.php';   // owContactEmail(): the one admin / contact address

if (!function_exists('owMailSetting')) {
    function owMailSetting(string $name, string $default = ''): string {
        return defined($name) ? trim((string) constant($name)) : $default;
    }
}

/* The address emails come from. Hostinger only accepts mail "from" the
   mailbox that signs in, so that is the default. */
function owMailFrom(): string {
    $from = owMailSetting('SMTP_FROM', owMailSetting('SMTP_USER'));
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
        $host = preg_replace('/^www\./', '', strtolower((string) ($_SERVER['HTTP_HOST'] ?? 'oweili.com')));
        $from = 'no-reply@' . (preg_match('/^[a-z0-9.-]+$/', $host) ? $host : 'oweili.com');
    }
    return $from;
}

/* True when config.php has a mailbox and a real password (not the sample). */
function owSmtpReady(): bool {
    $pass = owMailSetting('SMTP_PASS');
    return owMailSetting('SMTP_HOST') !== '' && owMailSetting('SMTP_USER') !== ''
        && $pass !== '' && !preg_match('/^(your[_ -]?(smtp[_ -]?|email[_ -]?)?pass(word)?|change[_ -]?me|x{3,}|\*+)$/i', $pass);
}

/* ---------------------------------------------------------------------
 *  Every email goes out the same, trusted way (owMailMessage):
 *   - From "Oweili · أويلي" <no-reply@oweili.com>, Reply-To contact@oweili.com
 *   - two versions in one email (multipart/alternative): plain text AND HTML,
 *     made from whichever one the page wrote
 *   - the same footer: Oweili, Elitport Limited, Jeddah, Saudi Arabia,
 *     contact@oweili.com, oweili.com (+ the commercial registration number
 *     once it is set in ow_paths.php / config.php)
 *   - a correct Date and a unique Message-ID on oweili.com
 * ------------------------------------------------------------------- */
const OW_MAIL_FROM_NAME = 'Oweili · أويلي';

/* The footer, as plain text and as HTML */
function owMailFooter(): array {
    $site = 'https://oweili.com';
    $cr = owCompanyCr();
    $text = "--\r\nOweili · أويلي, the home of real human art\r\n" . OW_COMPANY_NAME . ' · ' . OW_COMPANY_CITY . "\r\n"
          . owContactEmail() . ' · ' . $site . "\r\n" . ($cr !== '' ? 'Commercial Registration No. ' . $cr . "\r\n" : '')
          . "This is a service email from Oweili. · هذه رسالة خدمة من أويلي.";
    $h = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    $html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:16px auto 0;font-family:Arial,sans-serif">'
          . '<tr><td style="padding:16px 28px;border-top:1px solid #E2E8F0;font-size:12px;line-height:1.7;color:#4A5568;text-align:center">'
          . '<strong style="color:#23314A">Oweili · أويلي</strong>, the home of real human art<br>'
          . $h(OW_COMPANY_NAME) . ' · ' . $h(OW_COMPANY_CITY) . '<br>'
          . '<a href="mailto:' . $h(owContactEmail()) . '" style="color:#23314A">' . $h(owContactEmail()) . '</a> · <a href="' . $site . '" style="color:#23314A">oweili.com</a>'
          . ($cr !== '' ? '<br>Commercial Registration No. ' . $h($cr) : '')
          . '<br>This is a service email from Oweili. · <span dir="rtl">هذه رسالة خدمة من أويلي.</span>'
          . '</td></tr></table>';
    return [$text, $html];
}

/* Plain text → simple HTML (links clickable, Arabic lines right to left) */
function owMailTextToHtml(string $text): string {
    $h = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    $out = '';
    foreach (preg_split("/(\r\n|\n){2,}/", trim($text)) as $para) {
        $lines = array_map(function ($l) use ($h) {
            $l = $h(rtrim($l));
            return preg_replace('#(https?://[^\s<]+)#', '<a href="$1" style="color:#23314A;word-break:break-all">$1</a>', $l);
        }, preg_split("/\r\n|\n/", $para));
        $rtl = preg_match('/\p{Arabic}/u', $para) && !preg_match('/[A-Za-z]{4,}/', preg_replace('#https?://\S+#', '', $para));
        $out .= '<p dir="' . ($rtl ? 'rtl' : 'ltr') . '" style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#23314A;text-align:' . ($rtl ? 'right' : 'left') . '">' . implode('<br>', $lines) . '</p>';
    }
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width"></head>'
         . '<body style="margin:0;padding:24px;background:#F4F6F8;font-family:Arial,sans-serif">'
         . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background:#ffffff;border:1px solid #E2E8F0;border-radius:16px">'
         . '<tr><td style="padding:24px 28px 10px"><div style="font-size:15px;font-weight:700;letter-spacing:.24em;color:#23314A">OWEILI · أويلي</div></td></tr>'
         . '<tr><td style="padding:8px 28px 14px">' . $out . '</td></tr></table></body></html>';
}

/* HTML → plain text (keeps the words and the link addresses) */
function owMailHtmlToText(string $html): string {
    $t = preg_replace('#<(script|style|head)[^>]*>.*?</\1>#is', '', $html);
    $t = preg_replace('#<a\s[^>]*href="([^"]+)"[^>]*>(.*?)</a>#is', '$2: $1', $t);
    $t = preg_replace('#<(br|/p|/div|/tr|/h\d|/li)[^>]*>#i', "\n", $t);
    $t = html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $t = preg_replace("/[ \t]+/", ' ', $t);
    $t = preg_replace("/\n\s*\n\s*\n+/", "\n\n", preg_replace("/ *\n */", "\n", $t));
    return str_replace("\n", "\r\n", trim($t));
}

/* Builds the full message (headers + both versions) once, for every way of sending. */
function owMailMessage(string $to, string $subject, string $body, bool $isHtml): array {
    $from     = owMailFrom();
    $fromName = defined('OWEILI_FROM_NAME') ? (string) OWEILI_FROM_NAME : OW_MAIL_FROM_NAME;
    $domain   = substr(strrchr($from, '@'), 1) ?: 'oweili.com';
    $enc = fn(string $s) => preg_match('/[^\x20-\x7E]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
    [$footText, $footHtml] = owMailFooter();
    if ($isHtml) { $html = $body; $text = owMailHtmlToText($body); }
    else { $text = str_replace(["\r\n", "\n"], ["\n", "\r\n"], trim($body)); $html = owMailTextToHtml($body); }
    $text .= "\r\n\r\n" . $footText;
    $html = stripos($html, '</body>') !== false ? substr_replace($html, $footHtml, strripos($html, '</body>'), 0) : $html . $footHtml;
    $boundary = 'ow_' . bin2hex(random_bytes(12));
    $part = fn(string $type, string $content) => '--' . $boundary . "\r\n"
        . 'Content-Type: ' . $type . '; charset=UTF-8' . "\r\n" . 'Content-Transfer-Encoding: quoted-printable' . "\r\n\r\n"
        . quoted_printable_encode(str_replace(["\r\n", "\n"], ["\n", "\r\n"], $content)) . "\r\n";
    $headers = [
        'Date: ' . date(DATE_RFC2822),
        'From: ' . $enc($fromName) . ' <' . $from . '>',
        'Reply-To: Oweili <' . owContactEmail() . '>',          // replies reach Oweili's contact mailbox
        'Message-ID: <' . bin2hex(random_bytes(16)) . '.' . time() . '@' . $domain . '>',
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    ];
    return [
        'from'    => $from,
        'subject' => $enc($subject),
        'headers' => $headers,
        'html'    => $html,
        'body'    => $part('text/plain', $text) . $part('text/html', $html) . '--' . $boundary . "--",
    ];
}

/* One SMTP conversation. Returns true when the server accepted the email. */
/* $loginOnly = true only signs in and signs out (used by config_setup.php to
   check the mailbox and password without sending anything). */
/* $pass: test a different password than the one in config.php (config_setup.php). */
function owSmtpSend(string $to, array $msg, string $host, int $port, array &$log, bool $loginOnly = false,
                    #[\SensitiveParameter] ?string $pass = null): bool {
    $secure = $port === 465 ? 'ssl://' : 'tcp://';
    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true]]);
    $fp = @stream_socket_client($secure . $host . ':' . $port, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);   // 10s: never keep a visitor waiting long
    if (!$fp) { $log[] = "Could not connect to $host:$port ($errstr)"; return false; }
    stream_set_timeout($fp, 20);

    $read = function () use ($fp): string {
        $all = '';
        while (($line = fgets($fp, 1024)) !== false) {
            $all .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') break;      // last line of the reply
        }
        return $all;
    };
    $cmd = function (string $c, array $okCodes, string $show = '') use ($fp, $read, &$log): bool {
        fwrite($fp, $c . "\r\n");
        $r = $read();
        $code = (int) substr($r, 0, 3);
        if (!in_array($code, $okCodes, true)) {
            $log[] = ($show !== '' ? $show : strtok($c, ' ')) . ' → ' . trim($r ?: 'no answer');
            return false;
        }
        return true;
    };

    $hello = preg_replace('/[^a-z0-9.-]/i', '', (string) ($_SERVER['SERVER_NAME'] ?? 'oweili.com')) ?: 'oweili.com';
    $greet = $read();
    $ok = (int) substr($greet, 0, 3) === 220;
    if (!$ok) $log[] = "$host:$port greeting → " . trim($greet ?: 'no answer');
    $ok = $ok && $cmd('EHLO ' . $hello, [250]);
    if ($ok && $port !== 465) {
        $ok = $cmd('STARTTLS', [220]);
        if ($ok && !@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            $log[] = "$host:$port secure connection (STARTTLS) failed";
            $ok = false;
        }
        $ok = $ok && $cmd('EHLO ' . $hello, [250]);
    }
    $ok = $ok && $cmd('AUTH LOGIN', [334])
              && $cmd(base64_encode(owMailSetting('SMTP_USER')), [334], 'Mailbox name')
              && $cmd(base64_encode($pass ?? owMailSetting('SMTP_PASS')), [235], 'Password');
    if ($ok && $loginOnly) {
        @fwrite($fp, "QUIT\r\n");
        fclose($fp);
        $log[] = "$host:$port: mailbox and password accepted";
        return true;
    }
    $ok = $ok && $cmd('MAIL FROM:<' . $msg['from'] . '>', [250])
              && $cmd('RCPT TO:<' . $to . '>', [250, 251])
              && $cmd('DATA', [354]);
    if ($ok) {
        $data = implode("\r\n", array_merge(['To: ' . $to, 'Subject: ' . $msg['subject']], $msg['headers']))
              . "\r\n\r\n" . preg_replace('/^\./m', '..', $msg['body']) . "\r\n.";   // a line that starts with "." is doubled (SMTP rule)
        $ok = $cmd($data, [250], 'Sending the message');
    }
    @fwrite($fp, "QUIT\r\n");
    fclose($fp);
    if ($ok) $log[] = "Sent through $host:$port";
    return $ok;
}

function owSendMail(string $to, string $subject, string $body, bool $isHtml = false, ?array &$log = null): bool {
    $log = [];
    $to  = trim($to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $to . $subject)) {
        $log[] = 'Not a valid email address';
        return false;
    }
    $msg = owMailMessage($to, $subject, $body, $isHtml);

    /* 1. Our own SMTP sender, with the mailbox from config.php */
    if (owSmtpReady()) {
        $host  = owMailSetting('SMTP_HOST');
        $first = (int) owMailSetting('SMTP_PORT', '465') ?: 465;
        foreach (array_unique([$first, $first === 465 ? 587 : 465]) as $port) {
            if (owSmtpSend($to, $msg, $host, $port, $log)) return true;
        }
    } else {
        $log[] = 'SMTP settings are missing in config.php (SMTP_HOST, SMTP_USER, SMTP_PASS)';
    }

    /* 2. The older sender in config.php, if there is one */
    if (function_exists('sendEmail')) {
        try {
            if (sendEmail($to, $subject, $msg['html'], true)) { $log[] = 'Sent by sendEmail() in config.php'; return true; }
            $log[] = 'sendEmail() in config.php also failed';
        } catch (Throwable $e) { $log[] = 'sendEmail() in config.php stopped with an error'; }
    }

    /* 3. PHP mail(), from the same mailbox */
    if (function_exists('mail')
        && @mail($to, $msg['subject'], $msg['body'], implode("\r\n", $msg['headers']), '-f' . $msg['from'])) {
        $log[] = 'Handed to PHP mail() (delivery not guaranteed)';
        return true;
    }
    $log[] = 'PHP mail() failed too';
    error_log('Oweili mail to ' . $to . ' failed: ' . implode(' | ', $log));
    return false;
}
