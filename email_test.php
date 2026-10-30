<?php
/* =====================================================================
 *  email_test.php — tests the email setup in config.php (admins only)
 *  1. Signs in to the mailbox (SMTP_HOST / SMTP_USER / SMTP_PASS) on
 *     ports 465 and 587 and shows what the mail server answers, without
 *     sending anything. "authentication failed" = the mailbox does not
 *     exist or the password is wrong.
 *  2. "Send test" calls sendEmail() from config.php directly and shows
 *     what it returned, plus any error or warning it raised.
 *  Only for admins signed in on admin.php (same check as admin.php).
 *  Everyone else gets 404, so it cannot be used to send spam.
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/artwork_lib.php';
require_once __DIR__ . '/mail_lib.php';

$adminHash = defined('ADMIN_HASH') ? (string) ADMIN_HASH : '';
$adminSig  = $adminHash !== '' ? hash('sha256', $adminHash) : '';
if (!(!empty($_SESSION['is_admin']) && $adminSig !== '' && hash_equals($adminSig, (string) ($_SESSION['admin_sig'] ?? '')))) {
    http_response_code(404);
    exit;
}
header('X-Robots-Tag: noindex');

/* ---- 1. Mailbox sign-in check (nothing is sent) ---- */
$login = [];
if (owSmtpReady()) {
    $host = owMailSetting('SMTP_HOST');
    $first = (int) owMailSetting('SMTP_PORT', '465') ?: 465;
    $msg = owMailMessage(owMailFrom(), 'x', 'x', false);
    foreach (array_unique([$first, $first === 465 ? 587 : 465]) as $port) {
        $log = [];
        $ok = owSmtpSend(owMailFrom(), $msg, $host, $port, $log, true);
        $login[] = ['port' => $port, 'ok' => $ok, 'log' => $log];
    }
}

/* ---- 2. sendEmail() from config.php ---- */
$res = null; $to = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) { http_response_code(403); exit('Invalid CSRF token.'); }
    $to = trim((string) ($_POST['to'] ?? ''));
    $res = ['exists' => function_exists('sendEmail'), 'ok' => false, 'notes' => [], 'printed' => ''];
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $res['notes'][] = 'Please type a valid email address.';
    } elseif ($res['exists']) {
        set_error_handler(function ($no, $str, $file, $line) use (&$res) {
            $res['notes'][] = 'PHP said: ' . $str . ' (line ' . $line . ' of ' . basename($file) . ')';
            return true;
        });
        ob_start();
        $t = microtime(true);
        try {
            $res['ok'] = (bool) sendEmail($to, 'Oweili email test · اختبار البريد',
                "This is a test from Oweili. If you can read it, emails work.\r\n\r\nهذه رسالة تجريبية من أويلي. إن وصلتك، فالبريد يعمل.\r\n");
        } catch (Throwable $e) {
            $res['notes'][] = 'sendEmail() stopped with an error: ' . $e->getMessage();
        }
        $res['ms'] = (int) round((microtime(true) - $t) * 1000);
        $res['printed'] = trim(strip_tags((string) ob_get_clean()));
        restore_error_handler();
    }
}
$pass = owMailSetting('SMTP_PASS');
/* never show the password itself, even here */
$clean = fn(string $s) => $pass !== '' ? str_replace([$pass, base64_encode($pass)], '••••', $s) : $s;
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>Email test · Oweili</title>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
.wrap{max-width:900px;margin:0 auto;padding:var(--space-lg);}
.et-ok{color:var(--color-dark);font-weight:700;}
.et-bad{color:var(--color-dark);font-weight:700;background:rgba(var(--rgb-primary),.15);padding:0 6px;border-radius:6px;}
.et-log{margin:var(--space-xs) 0 var(--space-sm);padding-inline-start:var(--space-md);}
.et-log li{margin-bottom:4px;word-break:break-word;}
.et-form{display:flex;gap:var(--space-xs);flex-wrap:wrap;align-items:flex-end;}
.et-form .field{flex:1;min-width:220px;margin:0;}
.card + .card{margin-top:var(--space-md);}
@media(max-width:640px){.wrap{padding:var(--space-sm);}}
</style>
</head>
<body class="staff-page">
<div class="wrap dash role-admin">
  <div class="top">
    <div>
      <h1><?= owBi('Email test', 'اختبار البريد') ?></h1>
      <div class="sub"><?= owBi('Checks the mailbox in config.php and the sendEmail() function.', 'يفحص صندوق البريد في config.php ودالة sendEmail().') ?></div>
    </div>
    <div class="top-actions">
      <?php owThemeButton('btn btn-ghost btn-sm'); ?>
      <a class="btn btn-dark btn-sm" href="admin.php"><?= owBi('Back to Admin', 'العودة للوحة') ?></a>
    </div>
  </div>

  <div class="card">
    <h2><?= owBi('1. Can the site sign in to the mailbox?', '١. هل يستطيع الموقع الدخول إلى صندوق البريد؟') ?></h2>
    <p><?= owBi('Mailbox', 'الصندوق') ?>: <code><?= e(owMailSetting('SMTP_USER') ?: '—') ?></code> · <?= owBi('Server', 'الخادم') ?>: <code><?= e(owMailSetting('SMTP_HOST') ?: '—') ?></code></p>
    <?php if (!$login): ?>
      <p class="et-bad"><?= owBi('config.php has no SMTP_HOST, SMTP_USER or SMTP_PASS.', 'لا يحتوي config.php على SMTP_HOST أو SMTP_USER أو SMTP_PASS.') ?></p>
    <?php endif; ?>
    <?php foreach ($login as $l): ?>
      <p class="<?= $l['ok'] ? 'et-ok' : 'et-bad' ?>"><?= $l['ok'] ? '✓' : '✗' ?> Port <?= (int) $l['port'] ?>:
        <?= $l['ok'] ? owBi('mailbox and password accepted', 'تم قبول الصندوق وكلمة المرور') : owBi('refused', 'مرفوض') ?></p>
      <ul class="et-log"><?php foreach ($l['log'] as $x): ?><li><code><?= e($clean($x)) ?></code></li><?php endforeach; ?></ul>
    <?php endforeach; ?>
    <p><?= owBi('"authentication failed" means the mailbox does not exist on Hostinger or its password is different. "Could not connect" means the server name or port is wrong or blocked.', '«authentication failed» تعني أن الصندوق غير موجود في هوستنجر أو أن كلمة مروره مختلفة. «Could not connect» تعني أن اسم الخادم أو المنفذ خطأ أو محجوب.') ?></p>
  </div>

  <div class="card">
    <h2><?= owBi('2. Send a test with sendEmail()', '٢. أرسل رسالة تجريبية عبر sendEmail()') ?></h2>
    <form class="et-form" method="post" action="email_test.php">
      <?= csrfField() ?>
      <div class="field"><label for="to"><?= owBi('Send to', 'أرسل إلى') ?></label>
        <input type="email" id="to" name="to" required value="<?= e($to) ?>"></div>
      <button class="btn btn-primary" type="submit"><?= owBi('Send test', 'أرسل') ?></button>
    </form>
    <?php if ($res !== null): ?>
      <?php if (!$res['exists']): ?>
        <p class="et-bad"><?= owBi('config.php has no sendEmail() function.', 'لا توجد دالة sendEmail() في config.php.') ?></p>
      <?php elseif (isset($res['ms'])): ?>
        <p class="<?= $res['ok'] ? 'et-ok' : 'et-bad' ?>"><?= $res['ok']
          ? '✓ ' . owBi('sendEmail() says it was sent. Check the inbox and spam folder.', 'تقول sendEmail() إنها أُرسلت. راجع البريد الوارد والرسائل غير المرغوبة.')
          : '✗ ' . owBi('sendEmail() returned false: the email was not sent.', 'أعادت sendEmail() القيمة false: لم تُرسل الرسالة.') ?>
          (<?= (int) $res['ms'] ?> ms)</p>
      <?php endif; ?>
      <?php if ($res['notes'] || $res['printed'] !== ''): ?>
        <ul class="et-log">
          <?php foreach ($res['notes'] as $n): ?><li><code><?= e($clean($n)) ?></code></li><?php endforeach; ?>
          <?php if ($res['printed'] !== ''): ?><li><code><?= e($clean($res['printed'])) ?></code></li><?php endif; ?>
        </ul>
      <?php endif; ?>
      <p><?= owBi('sendEmail() writes its exact reason to the PHP error log: hPanel → Advanced → PHP error log.', 'تكتب sendEmail() السبب الدقيق في سجل أخطاء PHP: hPanel ← متقدم ← سجل أخطاء PHP.') ?></p>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
