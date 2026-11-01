<?php
/* TEMPORARY (1 Nov 2026), removed straight after use: sends the admin alert email (the PHPMailer way)
   to contact@oweili.com or one mail-tester address, and shows the mail server's answer. One-time key, POST only. */
const OW_MT_UNTIL = 1793541881;
const OW_MT_KEY_HASH = '42f2e2e327c49afc97400482d916bcc7a84e30586a456fa160108549e3c1aa79';
if (time() > OW_MT_UNTIL) { http_response_code(404); exit; }
require_once 'config.php';
$keyOk = is_string($_POST['key'] ?? null) && hash_equals(OW_MT_KEY_HASH, hash('sha256', (string) $_POST['key']));
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !$keyOk) { http_response_code(404); exit; }
header('Content-Type: text/plain; charset=UTF-8'); header('Cache-Control: no-store');
require_once __DIR__ . '/alert_lib.php';
$to = (string) ($_POST['to'] ?? '');
if ($to !== owContactEmail() && !preg_match('/^test-[a-z0-9]+@srv1\.mail-tester\.com$/i', $to)) { echo "not allowed\n"; exit; }
[$subject, $html] = owAlertEmail([['id' => 0, 'target_type' => 'artist', 'created_at' => date('Y-m-d H:i:s'),
    'details' => json_encode(['en' => 'New artist sign-up: Test Artist (Jeddah)', 'ar' => 'فنان جديد: فنان تجريبي (جدة)', 'link' => 'admin.php#pending'], JSON_UNESCAPED_UNICODE)]]);
$log = []; $ok = owSendMail($to, $subject, $html, true, $log);
echo 'To: ', $to, "\nSubject: ", $subject, "\nResult: ", $ok ? 'SENT' : 'NOT SENT', "\n", implode("\n", $log), "\n";
