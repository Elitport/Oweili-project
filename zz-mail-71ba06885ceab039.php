<?php
/* TEMPORARY read-only email check (owner, 9 Nov 2026): what the site logged when it tried to send the sign-up emails. Sends nothing. Locked by a secret key. Removed straight after. */
if (!hash_equals('d8aaa193d5cd71e5549f9afcc98391d5efbdd97e2e968c08f41d8035b6c27412', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mail_lib.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
$db = getDB();

require_once __DIR__ . '/platform_lib.php';
/* ?do=send: one diagnostic copy of the real "Confirm your email" message to mail-tester.com and to no-reply@oweili.com, with the mail server's answers */
if (($_GET['do'] ?? '') === 'send') {
    $page = @file_get_contents('https://www.mail-tester.com/', false, stream_context_create(['http' => ['timeout' => 15, 'header' => "User-Agent: Mozilla/5.0\r\n"]]));
    preg_match('/([a-z0-9-]+)@srv1\.mail-tester\.com/i', (string) $page, $mm);
    $mt = $mm[0] ?? ''; echo "mail-tester address: ", $mt ?: '(not found)', "\n";
    require_once __DIR__ . '/lib/PHPMailer/Exception.php'; require_once __DIR__ . '/lib/PHPMailer/PHPMailer.php'; require_once __DIR__ . '/lib/PHPMailer/SMTP.php';
    foreach (array_filter([$mt, 'no-reply@oweili.com']) as $to) {
        $subject = 'Confirm your email · تأكيد بريدك الإلكتروني';
        $msg = owMailMessage($to, $subject, owVerifyEmailBody('https://oweili.com/verify.php?token=diagnostic-test-not-a-real-link', true), false);
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $tr = [];
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function ($str) use (&$tr) { if (!preg_match('/^CLIENT -> SERVER: (AUTH|[A-Za-z0-9+\/=]{12,}\s*$)/', $str)) $tr[] = rtrim($str); };
        try {
            $mail->isSMTP(); $mail->Host = owMailSetting('SMTP_HOST', 'smtp.hostinger.com'); $mail->SMTPAuth = true;
            $mail->Username = owMailSetting('SMTP_USER'); $mail->Password = owMailSetting('SMTP_PASS');
            $mail->Port = 465; $mail->SMTPSecure = 'ssl'; $mail->CharSet = 'UTF-8'; $mail->Timeout = 15;
            $mail->setFrom(owMailFrom(), OW_MAIL_FROM_NAME); $mail->addReplyTo(owContactEmail(), 'Oweili'); $mail->addAddress($to);
            $mail->isHTML(true); $mail->Subject = $subject; $mail->Body = $msg['html']; $mail->AltBody = $msg['text'];
            $mail->send(); echo "\n== to $to: SENT ==\n";
        } catch (\Throwable $e) { echo "\n== to $to: FAILED ", $mail->ErrorInfo, " ==\n"; }
        foreach ($tr as $l) if (preg_match('/SERVER -> CLIENT|SMTP ERROR|MAIL FROM|RCPT TO/', $l)) echo $l, "\n";
    }
    exit;
}
if (($_GET['do'] ?? '') === 'mt' && preg_match('/^[a-z0-9-]+$/i', (string) ($_GET['id'] ?? ''))) {
    echo @file_get_contents('https://www.mail-tester.com/' . $_GET['id'] . '&format=json', false, stream_context_create(['http' => ['timeout' => 20, 'header' => "User-Agent: Mozilla/5.0\r\n"]]));
    exit;
}
echo "now (db): ", $db->query('SELECT NOW()')->fetchColumn(), "  php: ", date('Y-m-d H:i:s T'), "\n";
echo "SMTP_HOST=", owMailSetting('SMTP_HOST'), " PORT=", owMailSetting('SMTP_PORT'), " USER=", owMailSetting('SMTP_USER'), " PASS set=", owMailSetting('SMTP_PASS') !== '' ? 'yes' : 'no', "\n";
echo "From=", owMailFrom(), " contact=", owContactEmail(), " phpmailer file=", is_file(__DIR__ . '/lib/PHPMailer/PHPMailer.php') ? 'yes' : 'no', "\n";
echo "MX oweili.com: "; foreach ((array) @dns_get_record('oweili.com', DNS_MX) as $m) echo $m['target'] ?? '', '(', $m['pri'] ?? '', ') '; echo "\n";
$f = __DIR__ . '/.logs/admin_alerts_due'; echo "alerts due file: ", is_file($f) ? file_get_contents($f) . ' (now ' . time() . ')' : 'none', "\n\n";
echo "== activity_log since 8 Nov (mail, alerts, sign-ups) ==\n";
$st = $db->query("SELECT id, action, target_type, target_id, LEFT(details, 160) d, created_at FROM activity_log WHERE created_at >= '2026-11-08' AND (action LIKE '%alert%' OR action LIKE '%mail%' OR action LIKE '%signup%' OR action LIKE '%verify%' OR action LIKE '%inquir%') ORDER BY id DESC LIMIT 60");
foreach ($st as $r) echo $r['id'], "\t", $r['created_at'], "\t", $r['action'], "\t", $r['target_type'], ":", $r['target_id'], "\t", $r['d'], "\n";
echo "\n== last admin_alert_mail rows (any date) ==\n";
foreach ($db->query("SELECT id, target_id, details, created_at FROM activity_log WHERE action = 'admin_alert_mail' ORDER BY id DESC LIMIT 8") as $r) echo $r['id'], "\t", $r['created_at'], "\t", $r['target_id'], "\t", $r['details'], "\n";
echo "\n== users (TEST accounts) ==\n";
foreach ($db->query("SELECT id, role, email, created_at FROM users WHERE email IN ('contact@oweili.com','no-reply@oweili.com') ORDER BY id") as $r) echo implode("\t", $r), "\n";
echo "\n== error log lines about mail (last 3 days) ==\n";
$log = __DIR__ . '/.logs/php-errors.log';
if (is_file($log)) {
    $lines = file($log, FILE_IGNORE_NEW_LINES) ?: [];
    $out = [];
    foreach ($lines as $l) if (preg_match('/0[789]-Nov-2026/', $l) && preg_match('/mail|smtp|phpmailer|alert|register_artist|verify/i', $l)) $out[] = $l;
    echo implode("\n", array_slice($out, -60)), "\n";
    echo "(log size ", filesize($log), ", lines ", count($lines), ")\n";
} else echo "no log file\n";
foreach (glob(__DIR__ . '/.logs/*') as $g) echo basename($g), "\t", @filesize($g), "\t", date('Y-m-d H:i', (int) @filemtime($g)), "\n";

echo "\n== no-reply mailbox (read-only, EXAMINE) ==\n";
$imap = @stream_socket_client('ssl://imap.hostinger.com:993', $en, $es, 15);
if (!$imap) { echo "IMAP connect failed: $es\n"; exit; }
stream_set_timeout($imap, 20);
$n = 0;
$cmd = function (string $c) use ($imap, &$n) {
    $t = 'A' . (++$n); fwrite($imap, $t . ' ' . $c . "\r\n"); $out = '';
    while (($l = fgets($imap)) !== false) { $out .= $l; if (strpos($l, $t . ' ') === 0) break; }
    return $out;
};
fgets($imap);
$q = function ($s) { return '"' . addcslashes($s, "\"\\") . '"'; };
$r = $cmd('LOGIN ' . $q(owMailSetting('SMTP_USER')) . ' ' . $q(owMailSetting('SMTP_PASS')));
echo "login: ", preg_match('/^A1 OK/m', $r) ? 'ok' : 'failed', "\n";
echo preg_replace('/^A\d+ .*$/m', '', $cmd('LIST "" "*"'));
foreach (['INBOX', 'INBOX.Junk', 'INBOX.Spam', 'Junk', 'Spam'] as $box) {
    $r = $cmd('EXAMINE ' . $q($box)); if (!preg_match('/^A\d+ OK/m', $r)) continue;
    $s = $cmd('SEARCH SINCE 9-Nov-2026');
    preg_match('/\* SEARCH([\d ]*)/', $s, $m); $ids = array_filter(explode(' ', trim($m[1] ?? '')));
    echo "\n-- $box: ", count($ids), " since 8 Nov\n";
    foreach (array_slice($ids, -20) as $id) {
        $h = $cmd("FETCH $id (BODY.PEEK[HEADER.FIELDS (FROM TO SUBJECT DATE X-SPAM-STATUS X-SPAM-FLAG)])");
        $h = preg_replace(['/^\* \d+ FETCH.*$/m', '/^A\d+ .*$/m', '/^\)\s*$/m'], '', $h);
        echo "#$id ", trim(preg_replace('/\s*\r?\n\s*/', ' | ', $h)), "\n";
        if (preg_match('/mailer-daemon|postmaster|undeliver|delivery status|failure|returned/i', $h)) {
            $b = $cmd("FETCH $id (BODY.PEEK[TEXT]<0.4000>)");
            echo "   BODY: ", substr(preg_replace('/\s+/', ' ', $b), 0, 2500), "\n";
        }
    }
}
$cmd('LOGOUT'); fclose($imap);
