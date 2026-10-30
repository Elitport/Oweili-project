<?php
/* =====================================================================
 *  error_log_view.php — "Error log" for admins (admin.php?error_log=1)
 *  Shows the newest lines of the private PHP error log (.logs/php-errors.log,
 *  written by error_guard.php). Opened only from admin.php after the admin
 *  password check; opening this file directly shows nothing.
 * ===================================================================== */
if (!defined('OW_ADMIN_OK')) { http_response_code(404); exit; }
$logFile = defined('OW_ERROR_LOG') ? OW_ERROR_LOG : __DIR__ . '/.logs/php-errors.log';
$lines = [];
if (is_file($logFile) && ($fh = @fopen($logFile, 'r'))) {
    $size = filesize($logFile);
    fseek($fh, max(0, $size - 64 * 1024));                       // the last 64 KB is plenty
    $lines = array_slice(array_filter(explode("\n", (string) stream_get_contents($fh)), 'strlen'), -150);
    fclose($fh);
    $lines = array_reverse($lines);                               // newest first
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>Error log · Oweili</title>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
.wrap{max-width:1100px;margin:0 auto;padding:var(--space-lg);}
.el-list{font-size:var(--fs-label);line-height:1.6;white-space:pre-wrap;word-break:break-word;margin:0;padding:0;list-style:none;}
.el-list li{padding:var(--space-xs) 0;border-bottom:1px solid var(--color-border);}
@media(max-width:640px){.wrap{padding:var(--space-sm);}}
</style>
</head>
<body class="staff-page">
<div class="wrap dash role-admin">
  <div class="top">
    <div>
      <h1><?= owBi('Error log', 'سجل الأخطاء') ?></h1>
      <div class="sub"><?= owBi('Private. Visitors never see these. Newest first.', 'خاص. لا يراه الزوار أبداً. الأحدث أولاً.') ?></div>
    </div>
    <div class="top-actions">
      <?php owThemeButton('btn btn-ghost btn-sm'); ?>
      <a class="btn btn-dark btn-sm" href="admin.php"><?= owBi('Back to Admin', 'العودة للوحة') ?></a>
    </div>
  </div>
  <div class="card">
    <?php if (!$lines): ?>
      <p><?= owBi('No errors have been written yet.', 'لم تُسجَّل أي أخطاء حتى الآن.') ?></p>
    <?php else: ?>
      <ul class="el-list"><?php foreach ($lines as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
