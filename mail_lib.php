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

/* Builds the full message (headers + body) once, for every way of sending. */
function owMailMessage(string $to, string $subject, string $body, bool $isHtml): array {
    $from     = owMailFrom();
    $fromName = owMailSetting('SMTP_FROM_NAME', 'Oweili');
    $domain   = substr(strrchr($from, '@'), 1) ?: 'oweili.com';
    $enc = fn(string $s) => preg_match('/[^\x20-\x7E]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
    $headers = [
        'Date: ' . date('r'),
        'From: ' . $enc($fromName) . ' <' . $from . '>',
        'Reply-To: ' . $from,
        'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $domain . '>',
        'MIME-Version: 1.0',
        'Content-Type: ' . ($isHtml ? 'text/html' : 'text/plain') . '; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
    ];
    return [
        'from'    => $from,
        'subject' => $enc($subject),
        'headers' => $headers,
        'body'    => rtrim(chunk_split(base64_encode($body), 76, "\r\n")),
    ];
}

/* One SMTP conversation. Returns true when the server accepted the email. */
function owSmtpSend(string $to, array $msg, string $host, int $port, array &$log): bool {
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
              && $cmd(base64_encode(owMailSetting('SMTP_PASS')), [235], 'Password');
    $ok = $ok && $cmd('MAIL FROM:<' . $msg['from'] . '>', [250])
              && $cmd('RCPT TO:<' . $to . '>', [250, 251])
              && $cmd('DATA', [354]);
    if ($ok) {
        $data = implode("\r\n", array_merge(['To: ' . $to, 'Subject: ' . $msg['subject']], $msg['headers']))
              . "\r\n\r\n" . $msg['body'] . "\r\n.";
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
            if (sendEmail($to, $subject, $body, $isHtml)) { $log[] = 'Sent by sendEmail() in config.php'; return true; }
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
