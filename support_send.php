<?php
/* =====================================================================
 *  support_send.php — receives the Support page form "Write to Us"
 *  (owner, 9 Nov 2026) and answers in JSON: {"ok":true} or
 *  {"ok":false,"error":"code"}.
 *  - Checks: POST only, security token, a hidden trap field for robots,
 *    at most 3 messages per visitor per hour (also counted by the real
 *    visitor address) and 30 an hour for the whole site.
 *  - Sends: the message to Oweili (contact@oweili.com), the same message
 *    to Bashro Customer Service (kind "contact", after the page is sent),
 *    and a short "We received your message" email to the visitor.
 *  - Nothing is saved in a table; the activity log keeps only that a
 *    message was sent (no name, email, phone or text).
 *  Tables: activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/mail_lib.php';
require_once __DIR__ . '/request_lib.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
$reply = function (bool $ok, string $err = '', int $code = 200): void {
    http_response_code($code);
    echo json_encode($ok ? ['ok' => true] : ['ok' => false, 'error' => $err]);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST')   $reply(false, 'method', 405);
if (!verifyCsrf($_POST['csrf_token'] ?? null))           $reply(false, 'csrf', 403);
if (trim((string) ($_POST['website'] ?? '')) !== '')     $reply(true);   // robot filled the hidden field: pretend all is well

$clean = fn(string $s, int $max) => mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s)), 0, $max);
$one   = fn(string $s) => preg_replace('/\s+/u', ' ', $s);   // name, email and phone stay on one line
$name  = $clean($one((string) ($_POST['name'] ?? '')), 120);
$email = strtolower($clean($one((string) ($_POST['email'] ?? '')), 160));
$phone = $clean($one((string) ($_POST['phone'] ?? '')), 30);
$msg   = $clean((string) ($_POST['message'] ?? ''), 3000);
$lang  = ($_POST['lang'] ?? '') === 'ar' ? 'ar' : 'en';

if ($name === '' || $email === '' || $msg === '')                          $reply(false, 'required', 422);
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $email)) $reply(false, 'email', 422);
if ($phone !== '' && !preg_match('/^[0-9+()\-\s٠-٩]{6,30}$/u', $phone))     $reply(false, 'phone', 422);
if (mb_strlen($msg) < 5)                                                    $reply(false, 'short', 422);
if (owReqLimited('support_message', 3))                                     $reply(false, 'rate', 429);

try {
    owLog('support_message', 'support', null, $lang, ['type' => isLoggedIn() ? 'user' : 'guest', 'id' => isLoggedIn() ? (int) (currentUser()['id'] ?? 0) : null]);
    owReqCounted('support_message');

    /* 1. to Oweili */
    $body = "A new message from the Support page.\r\n\r\n"
          . "Name: $name\r\nEmail: $email\r\n" . ($phone !== '' ? "Phone: $phone\r\n" : '') . "Language: " . ($lang === 'ar' ? 'Arabic' : 'English') . "\r\n\r\n"
          . "Message:\r\n$msg\r\n\r\n"
          . "Reply to the visitor at $email.\r\n";
    if (!owSendMail(owContactEmail(), 'Oweili - Support message from ' . $name, $body)) error_log('support_send: email to Oweili failed');

    /* 2. to Bashro Customer Service (sent after the page, never waits) */
    if (is_file(__DIR__ . '/bashro_send.php')) { require_once __DIR__ . '/bashro_send.php'; owBashroContact($name, $email, $phone, $msg, $lang); }

    /* 3. a short receipt to the visitor */
    owReqTellBuyer($email, 'We received your message · وصلنا رسالتك',
        "Thank you, $name. We received your message and will reply soon, usually within 24 hours.",
        "شكراً لك، $name. وصلتنا رسالتك وسنرد عليك قريباً، غالباً خلال 24 ساعة.");
} catch (Throwable $e) {
    error_log('support_send.php: ' . $e->getMessage());
    $reply(false, 'server', 500);
}
$reply(true);
