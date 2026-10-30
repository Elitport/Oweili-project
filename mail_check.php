<?php
/* =====================================================================
 *  mail_check.php — "Email check" for admins (admin.php?mail_check=1)
 *  Shows the mailbox settings from config.php (never the password),
 *  whether the password-reset columns exist, and sends a test email
 *  that reports the mail server's exact answer.
 *  Opened only from admin.php after the admin password check; opening
 *  this file directly shows nothing.
 * ===================================================================== */
if (!defined('OW_ADMIN_OK')) { http_response_code(404); exit; }
require_once __DIR__ . '/mail_lib.php';

$sent = null; $log = []; $testTo = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['test_to'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) { http_response_code(403); exit('Invalid CSRF token.'); }
    $testTo = trim((string) $_POST['test_to']);
    $sent = owSendMail($testTo, 'Oweili email test · اختبار البريد',
        "This is a test email from Oweili. If you can read it, emails work.\r\n\r\n"
        . "هذه رسالة تجريبية من أويلي. إن وصلتك، فالبريد يعمل.\r\n", false, $log);
}

$resetCols = false;
try {
    $cols = getDB()->query("SHOW COLUMNS FROM users LIKE 'reset_%'")->fetchAll(PDO::FETCH_COLUMN);
    $resetCols = in_array('reset_token', $cols, true) && in_array('reset_expires', $cols, true);
} catch (Throwable $e) { $resetCols = false; }

$mark = fn(bool $ok, string $en, string $ar) => '<span class="mc-' . ($ok ? 'ok' : 'bad') . '">' . ($ok ? '✓ ' : '✗ ') . owBi($en, $ar) . '</span>';
$val  = fn(string $v) => $v !== '' ? '<code>' . e($v) . '</code>' : $mark(false, 'Missing', 'غير موجود');
$passSet = owMailSetting('SMTP_PASS') !== '';
$checks = [
    ['Mail server (SMTP_HOST)', 'خادم البريد', $val(owMailSetting('SMTP_HOST'))],
    ['Port (SMTP_PORT)', 'المنفذ', $val(owMailSetting('SMTP_PORT', '465 (default)'))],
    ['Mailbox (SMTP_USER)', 'صندوق البريد', $val(owMailSetting('SMTP_USER'))],
    ['Mailbox password (SMTP_PASS)', 'كلمة مرور الصندوق', $mark(owSmtpReady(), $passSet ? (owSmtpReady() ? 'Set' : 'Looks like the sample value') : 'Missing', $passSet ? (owSmtpReady() ? 'موجودة' : 'تبدو قيمة تجريبية') : 'غير موجودة')],
    ['Emails are sent from', 'تُرسل الرسائل من', '<code>' . e(owMailFrom()) . '</code>'],
    ['Old sender in config.php', 'المرسل القديم في config.php', $mark(function_exists('sendEmail'), function_exists('sendEmail') ? 'Present (used as a backup)' : 'Not there', function_exists('sendEmail') ? 'موجود (احتياطي)' : 'غير موجود')],
    ['Secure connections (OpenSSL)', 'الاتصال الآمن (OpenSSL)', $mark(extension_loaded('openssl'), extension_loaded('openssl') ? 'Available' : 'Missing', extension_loaded('openssl') ? 'متوفر' : 'غير متوفر')],
    ['Password reset columns', 'أعمدة إعادة تعيين كلمة المرور', $mark($resetCols, $resetCols ? 'Ready' : 'Missing: run reset_setup.sql', $resetCols ? 'جاهزة' : 'غير موجودة: شغّل reset_setup.sql')],
];
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>Email check · Oweili</title>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
.wrap{max-width:900px;margin:0 auto;padding:var(--space-lg);}
.mc-ok{color:var(--color-dark);font-weight:700;}
.mc-bad{color:var(--color-dark);font-weight:700;background:rgba(var(--rgb-primary),.15);padding:0 6px;border-radius:6px;}
.mc-log{margin:var(--space-sm) 0 0;padding-inline-start:var(--space-md);}
.mc-log li{margin-bottom:4px;word-break:break-word;}
.mc-form{display:flex;gap:var(--space-xs);flex-wrap:wrap;align-items:flex-end;}
.mc-form .field{flex:1;min-width:220px;margin:0;}
.card + .card{margin-top:var(--space-md);}
@media(max-width:640px){.wrap{padding:var(--space-sm);}.mc-tbl th,.mc-tbl td{display:block;width:100%;}.mc-tbl td{padding-top:0;}}
</style>
</head>
<body class="staff-page">
<div class="wrap dash role-admin">
  <div class="top">
    <div>
      <h1><?= owBi('Email check', 'فحص البريد') ?></h1>
      <div class="sub"><?= owBi('Can the site send password reset and welcome emails?', 'هل يستطيع الموقع إرسال رسائل استعادة كلمة المرور والترحيب؟') ?></div>
    </div>
    <div class="top-actions">
      <?php owThemeButton('btn btn-ghost btn-sm'); ?>
      <a class="btn btn-dark btn-sm" href="admin.php"><?= owBi('Back to Admin', 'العودة للوحة') ?></a>
    </div>
  </div>

  <div class="card">
    <table class="table mc-tbl">
      <?php foreach ($checks as $c): ?>
      <tr><th><?= owBi($c[0], $c[1]) ?></th><td><?= $c[2] ?></td></tr>
      <?php endforeach; ?>
    </table>
  </div>

  <div class="card">
    <h2><?= owBi('Send a test email', 'أرسل رسالة تجريبية') ?></h2>
    <form class="mc-form" method="post" action="admin.php?mail_check=1">
      <?= csrfField() ?>
      <div class="field"><label for="test_to"><?= owBi('Send to', 'أرسل إلى') ?></label>
        <input type="email" id="test_to" name="test_to" required value="<?= e($testTo) ?>"></div>
      <button class="btn btn-primary" type="submit"><?= owBi('Send test', 'أرسل') ?></button>
    </form>
    <?php if ($sent !== null): ?>
      <p style="margin-top:var(--space-sm)"><?= $sent
        ? $mark(true, 'Sent. Check the inbox and the spam folder.', 'أُرسلت. راجع البريد الوارد ومجلد الرسائل غير المرغوبة.')
        : $mark(false, 'Not sent. What the mail server said:', 'لم تُرسل. رد خادم البريد:') ?></p>
      <ul class="mc-log"><?php foreach ($log as $l): ?><li><code><?= e($l) ?></code></li><?php endforeach; ?></ul>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
