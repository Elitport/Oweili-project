<?php
/* =====================================================================
 *  mail_test_once.php — TEMPORARY. Removed straight after use (1 Nov 2026).
 *  Sends ONE test email, "Oweili admin email test", to Oweili's admin
 *  address (owContactEmail()) and shows the mail server's answer.
 *  Only with the one-time key (only its SHA-256 is stored here). POST only.
 * ===================================================================== */
const OW_MT_UNTIL    = 1793536232;
const OW_MT_KEY_HASH = '822eb9c050c248731f3382830e0e88ab796b9e6c62cb27a9e1c1800355d8a600';
if (time() > OW_MT_UNTIL) { http_response_code(404); exit; }
require_once 'config.php';
require_once __DIR__ . '/mail_lib.php';
$keyOk = is_string($_POST['key'] ?? null) && hash_equals(OW_MT_KEY_HASH, hash('sha256', (string) $_POST['key']));
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !$keyOk) { http_response_code(404); exit; }
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');
$to = owContactEmail();
$link = 'https://' . preg_replace('/^www\./i', '', preg_replace('/[^a-z0-9.\-]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'oweili.com'))) . '/admin.php#pending';
$html = '<!DOCTYPE html><html><body style="margin:0;padding:24px;background:#F4F6F8;font-family:Arial,sans-serif">'
  . '<table role="presentation" width="100%" style="max-width:600px;margin:0 auto;background:#fff;border:1px solid #E2E8F0;border-radius:16px"><tr><td style="padding:28px">'
  . '<div style="font-size:15px;font-weight:700;letter-spacing:.24em;color:#23314A"><span style="color:#DB8084">&#9679;</span> OWEILI · أويلي</div>'
  . '<h1 style="font-size:20px;color:#23314A;margin:16px 0 8px">Oweili admin email test</h1>'
  . '<p style="font-size:15px;color:#4A5568">This is a test from oweili.com. Admin emails now come to this address, sent from ' . htmlspecialchars(owMailFrom()) . '. When you press Reply, it goes to ' . htmlspecialchars($to) . '.</p>'
  . '<p dir="rtl" style="font-size:15px;color:#4A5568;text-align:right">هذه رسالة تجريبية من أويلي. تصل رسائل الإدارة الآن إلى هذا العنوان.</p>'
  . '<a href="' . htmlspecialchars($link) . '" style="display:inline-block;background:#23314A;color:#fff;text-decoration:none;font-weight:700;font-size:14px;padding:10px 18px;border-radius:8px">Open in the admin panel · افتح في لوحة الإدارة</a>'
  . '</td></tr></table></body></html>';
$log = [];
$ok = owSendMail($to, 'Oweili admin email test', $html, true, $log);
echo 'To: ', $to, "\nFrom: ", owMailFrom(), "\nReply-To: ", owContactEmail(), "\nResult: ", $ok ? 'SENT (the mail server accepted it)' : 'NOT SENT', "\n", implode("\n", $log), "\n";
