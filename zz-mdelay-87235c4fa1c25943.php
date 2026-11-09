<?php
/* TEMPORARY check (owner, 9 Nov 2026): why emails arrive about one hour late. ?do=send sends ONE test email to the site's own
   sending mailbox (no-reply@oweili.com); the default view reads that mailbox (read-only) and shows the delivery stamps of the
   newest emails. Never shows passwords. Locked by a secret key. Removed straight after. */
if (!hash_equals('f0874d13cce33c69cbea7d81d201b064ad9cac225ee18bb5911321b3ecd574d8', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mail_lib.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
echo 'now ', gmdate('Y-m-d H:i:s'), " UTC\n";

if (($_GET['do'] ?? '') === 'send') {
    require_once __DIR__ . '/lib/PHPMailer/Exception.php'; require_once __DIR__ . '/lib/PHPMailer/PHPMailer.php'; require_once __DIR__ . '/lib/PHPMailer/SMTP.php';
    $to = 'no-reply@oweili.com';
    $subject = 'Oweili delay test ' . gmdate('H:i:s');
    $msg = owMailMessage($to, $subject, "Delay test, sent at " . gmdate('Y-m-d H:i:s') . " UTC. Please ignore.", false);
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $tr = [];
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = function ($s) use (&$tr) { if (!preg_match('/^CLIENT -> SERVER: (AUTH|[A-Za-z0-9+\/=]{12,}\s*$)/', $s)) $tr[] = gmdate('H:i:s') . ' ' . rtrim($s); };
    try {
        $mail->isSMTP(); $mail->Host = owMailSetting('SMTP_HOST', 'smtp.hostinger.com'); $mail->SMTPAuth = true;
        $mail->Username = owMailSetting('SMTP_USER'); $mail->Password = owMailSetting('SMTP_PASS');
        $mail->Port = 465; $mail->SMTPSecure = 'ssl'; $mail->CharSet = 'UTF-8'; $mail->Timeout = 15;
        $mail->setFrom(owMailFrom(), OW_MAIL_FROM_NAME); $mail->addReplyTo(owContactEmail(), 'Oweili'); $mail->addAddress($to);
        $mail->isHTML(true); $mail->Subject = $subject; $mail->Body = $msg['html']; $mail->AltBody = $msg['text'];
        $mail->send(); echo "SENT: $subject\n";
    } catch (\Throwable $e) { echo 'FAILED ', $mail->ErrorInfo, "\n"; }
    foreach ($tr as $l) if (preg_match('/SERVER -> CLIENT: (2|4|5)\d\d|MAIL FROM|RCPT TO/', $l)) echo $l, "\n";
    exit;
}

echo "\n== DNS ==\n";
foreach (['oweili.com' => DNS_TXT, '_dmarc.oweili.com' => DNS_TXT] as $h => $t) foreach ((array) @dns_get_record($h, $t) as $r) echo $h, ' TXT ', $r['txt'] ?? '', "\n";
foreach ((array) @dns_get_record('oweili.com', DNS_MX) as $r) echo 'MX ', $r['target'] ?? '', ' ', $r['pri'] ?? '', "\n";

echo "\n== newest emails in no-reply@oweili.com (read-only) ==\n";
$imap = @stream_socket_client('ssl://imap.hostinger.com:993', $en, $es, 15);
if (!$imap) { echo "IMAP connect failed\n"; exit; }
stream_set_timeout($imap, 20);
$n = 0;
$cmd = function (string $c) use ($imap, &$n) { $t = 'A' . (++$n); fwrite($imap, $t . ' ' . $c . "\r\n"); $o = ''; while (($l = fgets($imap)) !== false) { $o .= $l; if (strpos($l, $t . ' ') === 0) break; } return $o; };
fgets($imap);
$q = fn($s) => '"' . addcslashes($s, "\"\\") . '"';
$cmd('LOGIN ' . $q(owMailSetting('SMTP_USER')) . ' ' . $q(owMailSetting('SMTP_PASS')));
foreach (['INBOX', 'INBOX.Junk'] as $box) {
    $r = $cmd('EXAMINE ' . $q($box));
    preg_match('/\* (\d+) EXISTS/', $r, $m); $count = (int) ($m[1] ?? 0);
    echo "\n-- $box: $count emails\n";
    for ($i = max(1, $count - 2); $i <= $count; $i++) {
        $h = $cmd("FETCH $i (INTERNALDATE BODY.PEEK[HEADER])");
        preg_match('/INTERNALDATE "([^"]+)"/', $h, $d);
        echo "\n#$i arrived in mailbox: ", $d[1] ?? '?', "\n";
        $h = preg_replace("/\r?\n[ \t]+/", ' ', $h);
        foreach (preg_split("/\r?\n/", $h) as $line) {
            if (preg_match('/^(Received|Date|Subject|From|To|X-Mailchannels[^:]*|X-MC-[^:]*|Authentication-Results|X-Spam[^:]*|X-Hostinger[^:]*|X-Rspamd[^:]*|Message-ID):/i', $line)) {
                echo '  ', preg_replace('/(token=)[0-9a-f]+/i', '$1…', mb_substr($line, 0, 400)), "\n";
            }
        }
    }
}
$cmd('LOGOUT'); fclose($imap);
