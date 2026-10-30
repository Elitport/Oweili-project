<?php
/* =====================================================================
 *  error_guard.php — loaded by config.php on every page.
 *  Visitors never see PHP errors, database errors or file paths.
 *  The real error is written to the private log .logs/php-errors.log
 *  (blocked for visitors by .htaccess, kept by deploys via .gitignore,
 *  readable by admins at admin.php?error_log=1). Visitors get a short,
 *  friendly page in English and Arabic instead.
 * ===================================================================== */
if (defined('OW_ERROR_GUARD')) return;
define('OW_ERROR_GUARD', true);

@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
@ini_set('log_errors', '1');
error_reporting(E_ALL);

const OW_ERROR_LOG = __DIR__ . '/.logs/php-errors.log';
(function () {
    $dir = dirname(OW_ERROR_LOG);
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    if (!is_dir($dir)) return;
    if (!is_file($dir . '/.htaccess')) @file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\n");
    if (is_file(OW_ERROR_LOG) && @filesize(OW_ERROR_LOG) > 5 * 1024 * 1024) {      // keep the log small: start a new one at 5 MB
        @rename(OW_ERROR_LOG, OW_ERROR_LOG . '.old');
    }
    @ini_set('error_log', OW_ERROR_LOG);
})();

/* The friendly page. Plain HTML with no database, so it always works. */
function owFriendlyError(int $code = 500): void {
    if (PHP_SAPI === 'cli') { fwrite(STDERR, "Error $code (see the error log)\n"); exit(1); }
    while (ob_get_level() > 0) @ob_end_clean();
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store');
        header('Retry-After: 120');
    }
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<meta name="robots" content="noindex"><title>One moment · Oweili</title>'
       . '<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#fff;color:#4A5568;'
       . 'font-family:"DM Sans","Tajawal",system-ui,sans-serif;padding:24px;box-sizing:border-box}'
       . '.c{max-width:480px;text-align:center;border:1px solid #E2E8F0;border-radius:16px;padding:32px 24px}'
       . 'h1{color:#23314A;font-size:24px;margin:0 0 8px}p{font-size:16px;line-height:1.6;margin:0 0 16px}'
       . '.ar{direction:rtl;font-family:"Tajawal",system-ui,sans-serif;line-height:1.7}'
       . 'a{display:inline-block;margin-top:8px;background:#DB8084;color:#23314A;font-weight:700;font-size:14px;text-decoration:none;'
       . 'padding:14px 24px;border-radius:8px}a:hover{background:#C96A6E}hr{border:0;border-top:1px solid #E2E8F0;margin:20px 0}</style></head>'
       . '<body><div class="c"><h1>We will be right back</h1><p>Something went wrong on our side. Please try again in a moment.</p>'
       . '<hr><div class="ar" lang="ar"><h1>سنعود حالاً</h1><p>حدث خطأ من جهتنا. يرجى المحاولة بعد قليل.</p></div>'
       . '<a href="/">Back to Oweili · العودة إلى أويلي</a></div></body></html>';
    exit;
}

/* Anything a page does not catch: log it privately, show the friendly page */
set_exception_handler(function (Throwable $e) {
    error_log('Uncaught ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    owFriendlyError(500);
});
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        owFriendlyError(500);
    }
});
