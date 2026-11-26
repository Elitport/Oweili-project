<?php
/* =====================================================================
 *  unsubscribe.php — stop Oweili news emails (owner, 27 Nov 2026)
 *  unsubscribe.php?u=ID&t=TOKEN (the personal link in every news email):
 *    - opened in a browser: one button "Stop news emails" (a button, so link
 *      checkers in mail apps never unsubscribe anyone by opening the link)
 *    - mail apps' own "Unsubscribe" button (List-Unsubscribe-Post, RFC 8058):
 *      a POST with List-Unsubscribe=One-Click stops the news at once
 *  No sign-in. Only news stops; service emails (confirmations, orders) go on.
 *  Tables: users (notify_news), site_settings, activity_log (unsub_lib.php)
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/unsub_lib.php';
header('X-Robots-Tag: noindex'); header('Cache-Control: no-store');

$uid = (int) ($_GET['u'] ?? $_POST['u'] ?? 0);
$tok = (string) ($_GET['t'] ?? $_POST['t'] ?? '');
$who = owNewsUnsubUser($uid, $tok);
$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

/* The mail app's one-click button: answer plainly, no page */
if ($isPost && (string) ($_POST['List-Unsubscribe'] ?? '') === 'One-Click') {
    if ($who) owNewsUnsubscribe($who, 'one-click button in the mail app');
    http_response_code($who ? 200 : 404);
    header('Content-Type: text/plain; charset=utf-8');
    echo $who ? 'Unsubscribed' : 'Not found';
    exit;
}

$state = !$who ? 'bad' : ((int) $who['notify_news'] === 0 ? 'done' : 'ask');
if ($who && $isPost && ($_POST['do'] ?? '') === 'stop') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) $state = 'expired';
    else { if ((int) $who['notify_news'] !== 0) owNewsUnsubscribe($who, 'the link in a news email'); $state = 'done'; }
}
?>
<!DOCTYPE html>
<html <?php require_once __DIR__ . '/lang_lib.php'; owHtmlAttrs(); ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title data-title-ar="إلغاء الاشتراك في أخبار أويلي">Unsubscribe from Oweili news</title>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
.un-card{text-align:center;}
.un-card h1{font-size:clamp(26px,4vw,34px);margin:0 0 var(--space-sm);}
.un-card p{color:var(--color-body);margin:0 0 var(--space-md);}
.un-mail{font-size:14px;color:var(--color-dark);margin:0 0 var(--space-md);word-break:break-all;}
.un-card .btn{width:100%;}
</style>
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>
<main class="auth-wrap">
  <div class="auth-card un-card">
  <?php if ($state === 'ask'): ?>
    <h1><?= owBi('Stop Oweili news?', 'إيقاف أخبار أويلي؟') ?></h1>
    <p><?= owBi('You will no longer get news emails. Emails about your account, requests and orders still arrive', 'لن تصلك رسائل الأخبار بعد الآن. وتبقى رسائل حسابك وطلباتك تصلك كالمعتاد') ?></p>
    <p class="un-mail" dir="ltr"><?= e((string) $who['email']) ?></p>
    <form method="post" action="unsubscribe.php"><?= csrfField() ?>
      <input type="hidden" name="u" value="<?= (int) $who['id'] ?>"><input type="hidden" name="t" value="<?= e($tok) ?>"><input type="hidden" name="do" value="stop">
      <button class="btn btn-primary" type="submit"><?= owBi('Stop news emails', 'أوقف رسائل الأخبار') ?></button>
    </form>
  <?php elseif ($state === 'done'): ?>
    <h1><?= owBi('You are unsubscribed', 'تم إلغاء اشتراكك') ?></h1>
    <p><?= owBi('You will no longer get Oweili news. You can turn it on again any time in your account', 'لن تصلك أخبار أويلي بعد الآن. ويمكنك تفعيلها مرة أخرى متى شئت من حسابك') ?></p>
    <a class="btn btn-secondary" href="index.php"><?= owBi('Back to Oweili', 'العودة إلى أويلي') ?></a>
  <?php elseif ($state === 'expired'): ?>
    <h1><?= owBi('Please try again', 'حاول مرة أخرى من فضلك') ?></h1>
    <p><?= owBi('This page was open for a long time, so nothing was changed. Open the link in the email again', 'بقيت هذه الصفحة مفتوحة طويلا، فلم يتغير شيء. افتح الرابط في الرسالة مرة أخرى') ?></p>
  <?php else: ?>
    <h1><?= owBi('This link does not work', 'هذا الرابط لا يعمل') ?></h1>
    <p><?= owBi('It may be old or copied wrongly. You can stop news emails from your account, or write to us', 'ربما يكون قديما أو نُسخ بشكل غير كامل. يمكنك إيقاف رسائل الأخبار من حسابك، أو مراسلتنا') ?></p>
    <a class="btn btn-secondary" href="login.php"><?= owBi('Sign in', 'تسجيل الدخول') ?></a>
  <?php endif; ?>
  </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
