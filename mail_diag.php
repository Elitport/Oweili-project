<?php
/* =====================================================================
 *  mail_diag.php — TEMPORARY. Removed straight after use (1 Nov 2026).
 *  Finds out why Oweili's emails land in spam, from the live server:
 *    dns     the domain's mail records (SPF, DKIM, DMARC, MX) — read only
 *    bl      blacklist checks for a domain or an address — read only
 *    mt_new  asks mail-tester.com for a fresh test address
 *    send    sends ONE site email (the admin alert) to a mail-tester address only
 *    mt      reads the mail-tester report (JSON)
 *  Only with the one-time key (only its SHA-256 is stored here). POST only.
 * ===================================================================== */
const OW_MD_UNTIL    = 1793543877;
const OW_MD_KEY_HASH = 'c794eb04bcc82c10eb2c39cd594138562b0572c13e01d1e6befccac69dea2935';
if (time() > OW_MD_UNTIL) { http_response_code(404); exit; }
require_once 'config.php';
$keyOk = is_string($_POST['key'] ?? null) && hash_equals(OW_MD_KEY_HASH, hash('sha256', (string) $_POST['key']));
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !$keyOk) { http_response_code(404); exit; }
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');
@set_time_limit(120);
$step = (string) ($_POST['step'] ?? '');
$get = function (string $url): string {
    $c = curl_init($url);
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 40, CURLOPT_USERAGENT => 'Mozilla/5.0 (Oweili mail check)']);
    $r = (string) curl_exec($c); curl_close($c); return $r;
};
if ($step === 'dns') {
    $d = preg_replace('/[^a-z0-9.\-]/i', '', (string) ($_POST['domain'] ?? 'oweili.com'));
    $show = function (string $name, int $type) {
        $r = @dns_get_record($name, $type);
        echo $name, ' [', [DNS_TXT => 'TXT', DNS_MX => 'MX', DNS_CNAME => 'CNAME', DNS_A => 'A', DNS_NS => 'NS'][$type], "]\n";
        if (!$r) { echo "   (none)\n"; return; }
        foreach ($r as $x) echo '   ', $x['txt'] ?? ($x['target'] ?? ($x['ip'] ?? json_encode($x))), isset($x['pri']) ? ' (priority ' . $x['pri'] . ')' : '', "\n";
    };
    $show($d, DNS_NS); $show($d, DNS_A); $show($d, DNS_MX); $show($d, DNS_TXT); $show('_dmarc.' . $d, DNS_TXT);
    foreach (['hostingermail1', 'hostingermail2', 'hostingermail3', 'hostingermail-a', 'hostingermail-b', 'hostingermail-c', 'default', 'dkim', 'mail', 'selector1', 'selector2'] as $sel) {
        $n = $sel . '._domainkey.' . $d;
        $c = @dns_get_record($n, DNS_CNAME); $t = @dns_get_record($n, DNS_TXT);
        if ($c || $t) { echo $n, "\n"; foreach ((array) $c as $x) echo '   CNAME ', $x['target'], "\n"; foreach ((array) $t as $x) echo '   TXT ', substr($x['txt'], 0, 120), (strlen($x['txt']) > 120 ? '…' : ''), "\n"; }
    }
    exit;
}
if ($step === 'bl') {
    $what = preg_replace('/[^a-z0-9.\-]/i', '', (string) ($_POST['what'] ?? ''));
    if (filter_var($what, FILTER_VALIDATE_IP)) {
        $rev = implode('.', array_reverse(explode('.', $what)));
        $lists = ['zen.spamhaus.org', 'b.barracudacentral.org', 'bl.spamcop.net', 'dnsbl.sorbs.net', 'psbl.surriel.com', 'bl.mailspike.net', 'dnsbl-1.uceprotect.net'];
    } else { $rev = $what; $lists = ['dbl.spamhaus.org', 'multi.surbl.org', 'multi.uribl.com', 'dbl.nordspam.com']; }
    foreach ($lists as $l) { $a = @dns_get_record($rev . '.' . $l, DNS_A); echo str_pad($l, 26), $a ? 'LISTED / answer ' . implode(',', array_column($a, 'ip')) : 'not listed', "\n"; }
    exit;
}
if ($step === 'mt_new') {
    $h = $get('https://www.mail-tester.com/');
    preg_match_all('/[a-z0-9._\-]+@[a-z0-9.\-]*mail-tester\.com/i', $h, $m);
    echo implode("\n", array_unique($m[0] ?? [])) ?: 'no address found', "\n";
    foreach (preg_split('/\n/', strip_tags($h)) as $ln) { $ln = trim($ln); if ($ln !== '' && preg_match('/mail-tester|@|test-|send/i', $ln)) echo '  | ', substr($ln, 0, 160), "\n"; }
    preg_match_all('/(id|data-[a-z]+|value)="([^"]{4,60})"/i', $h, $v); echo 'attrs: ', implode(' ', array_slice(array_unique($v[2]), 0, 40)), "\n";
    exit;
}
if ($step === 'send') {
    $to = (string) ($_POST['to'] ?? '');
    if (!preg_match('/^[a-z0-9._\-]+@(srv\d+\.)?mail-tester\.com$/i', $to)) { echo "only mail-tester addresses\n"; exit; }
    require_once __DIR__ . '/alert_lib.php';
    [$subject, $html] = owAlertEmail([['id' => 0, 'target_type' => 'artist', 'created_at' => date('Y-m-d H:i:s'),
        'details' => json_encode(['en' => 'New artist sign-up: Test Artist (Jeddah)', 'ar' => 'فنان جديد: فنان تجريبي (جدة)', 'link' => 'admin.php#pending'], JSON_UNESCAPED_UNICODE)]]);
    $log = [];
    $ok = owSendMail($to, $subject, $html, true, $log);
    echo 'Subject: ', $subject, "\nResult: ", $ok ? 'SENT' : 'NOT SENT', "\n", implode("\n", $log), "\n";
    exit;
}
if ($step === 'mt') {
    $id = preg_replace('/[^a-z0-9._\-]/i', '', (string) ($_POST['id'] ?? ''));
    echo $get('https://www.mail-tester.com/' . $id . '&format=json');
    exit;
}
echo "Unknown step\n";
