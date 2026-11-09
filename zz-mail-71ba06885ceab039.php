<?php
/* TEMPORARY read-only email check (owner, 9 Nov 2026): what the site logged when it tried to send the sign-up emails. Sends nothing. Locked by a secret key. Removed straight after. */
if (!hash_equals('d8aaa193d5cd71e5549f9afcc98391d5efbdd97e2e968c08f41d8035b6c27412', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mail_lib.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
$db = getDB();
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
