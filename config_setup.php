<?php
/* =====================================================================
 *  config_setup.php — TEMPORARY. Remove once the passwords are changed.
 *  Makes the live config.php safe without anyone opening it by hand:
 *    1. Debug off: visitors never see database errors or file paths
 *       (loads error_guard.php, hides PHP errors, friendly error page)
 *    2. New database password  — tested first, saved only if it connects
 *    3. New email password     — tested first, saved only if the mail
 *       server accepts it. The two must be different.
 *  Who: an admin signed in on admin.php. Debug off and the status check
 *  also accept a one-time key (only its SHA-256 is stored here).
 *  Switches itself off at OW_SETUP_UNTIL (everyone then gets 404).
 *  Passwords are never shown, logged or kept anywhere but config.php.
 *  Every change: the new file is checked for PHP mistakes, written in one
 *  step, then the home page and Gallery are opened from the server; if
 *  either breaks, the old config.php is put back at once.
 * ===================================================================== */
const OW_SETUP_UNTIL   = 1793424600;            // 31 Oct 2026, about 8 hours after it went live
const OW_SETUP_KEY_SHA = '7523d357872547f9d0cccd3ebbb1431bcbeee40ee036df58e7ce9bb35553f59e';

if (time() > OW_SETUP_UNTIL) { http_response_code(404); exit; }

require_once 'config.php';
require_once __DIR__ . '/mail_lib.php';
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

const OW_CFG = __DIR__ . '/config.php';

/* ---- who may use it ---- */
$adminHash = defined('ADMIN_HASH') ? (string) ADMIN_HASH : '';
$isAdmin = !empty($_SESSION['is_admin']) && $adminHash !== ''
        && hash_equals(hash('sha256', $adminHash), (string) ($_SESSION['admin_sig'] ?? ''));
$post   = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$hasKey = $post && is_string($_POST['key'] ?? null) && hash_equals(OW_SETUP_KEY_SHA, hash('sha256', $_POST['key']));
if (!$isAdmin && !$hasKey) { http_response_code(404); exit; }

/* ---------------------------------------------------------------------
 *  Helpers
 * ------------------------------------------------------------------- */
function owCfgRead(): string { return (string) @file_get_contents(OW_CFG); }

/* true when the PHP code has no syntax mistakes */
function owCfgParses(string $code): bool {
    try { token_get_all($code, TOKEN_PARSE); return true; } catch (Throwable $e) { return false; }
}

/* Writes config.php in one step (no half-written file), keeping its permissions */
function owCfgWrite(string $code): bool {
    $perms = @fileperms(OW_CFG) & 0777 ?: 0644;
    $tmp = __DIR__ . '/.cfgtmp-' . bin2hex(random_bytes(6));
    if (@file_put_contents($tmp, $code, LOCK_EX) !== strlen($code)) { @unlink($tmp); return false; }
    @chmod($tmp, $perms);
    if (!@rename($tmp, OW_CFG)) { @unlink($tmp); return false; }
    clearstatcache(true, OW_CFG);
    if (function_exists('opcache_invalidate')) @opcache_invalidate(OW_CFG, true);
    return true;
}

/* One safety copy of the original config.php (before the first change), private */
function owCfgBackupOnce(string $code): bool {
    $dir = __DIR__ . '/.backups';
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    if (!is_file($dir . '/.htaccess')) @file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\n");
    foreach (glob($dir . '/config-original-*.php') ?: [] as $f) return true;   // already kept
    $f = $dir . '/config-original-' . date('Ymd-His') . '.php';
    if (@file_put_contents($f, $code, LOCK_EX) !== strlen($code)) return false;
    @chmod($f, 0600);
    return true;
}

/* Opens a page of the live site from the server. Returns [status, body] or [0, ''] */
function owSelfGet(string $path): array {
    if (!function_exists('curl_init')) return [0, ''];
    $host = preg_replace('/[^a-z0-9.:-]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'oweili.com')) ?: 'oweili.com';
    $ch = curl_init('https://' . $host . $path . (strpos($path, '?') === false ? '?' : '&') . 'owcheck=' . time());
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 8,
                            CURLOPT_FOLLOWLOCATION => false, CURLOPT_HTTPHEADER => ['Cache-Control: no-cache']]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return [$body === false ? 0 : $code, (string) $body];
}

/* After a change: home page and Gallery must still open. 'ok', 'broken' or 'unknown' */
function owSiteWorks(): string {
    usleep(300000);
    $seen = 0;
    foreach (['/', '/marketplace.php'] as $p) {
        [$code, $body] = owSelfGet($p);
        if ($code === 0) continue;
        $seen++;
        if ($code >= 500 || stripos($body, 'DB ERROR') !== false || stripos($body, 'We will be right back') !== false
            || stripos($body, 'A server error occurred') !== false || stripos($body, 'Fatal error') !== false) return 'broken';
    }
    return $seen > 0 ? 'ok' : 'unknown';
}

/* Saves new config.php text safely; puts the old one back if the site breaks */
function owCfgApply(string $old, string $new): array {
    if ($new === $old)            return [true, 'Nothing to change.'];
    if (!owCfgParses($new))       return [false, 'The new settings had a mistake, so nothing was changed.'];
    if (!owCfgBackupOnce($old))   return [false, 'Could not keep a safety copy, so nothing was changed.'];
    if (!owCfgWrite($new))        return [false, 'The server did not allow saving config.php. Nothing was changed.'];
    $works = owSiteWorks();
    if ($works === 'broken') {
        owCfgWrite($old);
        return [false, 'The site stopped working with the change, so the old settings were put back.'];
    }
    return [true, $works === 'ok' ? 'Saved. The home page and Gallery still open.' : 'Saved. (The server could not open its own pages to double-check.)'];
}

/* Replaces define('NAME', '...'); — must be found exactly once */
function owCfgSetDefine(string $code, string $name, #[\SensitiveParameter] string $value): ?string {
    $re = "/define\(\s*'" . preg_quote($name, '/') . "'\s*,\s*(?:'(?:[^'\\\\]|\\\\.)*'|\"(?:[^\"\\\\]|\\\\.)*\")\s*\)\s*;/";
    if (preg_match_all($re, $code) !== 1) return null;
    return preg_replace_callback($re, fn() => "define('" . $name . "', " . var_export($value, true) . ');', $code, 1);
}

/* config.php with debug off. null = an expected line was not found (nothing is changed) */
function owCfgDebugOff(string $code): ?string {
    foreach (['display_errors', 'display_startup_errors'] as $k) {
        $re = "/ini_set\(\s*'" . $k . "'\s*,\s*'1'\s*\)\s*;/";
        $n = preg_match_all($re, $code);
        if ($n > 1) return null;
        if ($n === 1) $code = preg_replace($re, "ini_set('" . $k . "', '0');", $code, 1);
    }
    $code = str_replace("// Reveal any fatal/parse-time error instead of a blank HTTP 500.",
                        "// Errors are hidden from visitors and logged privately (error_guard.php).", $code);
    if (strpos($code, "/error_guard.php'") === false) {                 // not loaded yet
        $re = '/declare\(\s*strict_types\s*=\s*1\s*\)\s*;/';
        if (preg_match_all($re, $code) !== 1) return null;
        $code = preg_replace_callback($re, fn($m) => $m[0] . "\n\n// Visitors never see PHP errors or file paths; real errors go to the private log.\n"
            . "if (is_file(__DIR__ . '/error_guard.php')) require_once __DIR__ . '/error_guard.php';", $code, 1);
    }
    if (strpos($code, "'DB ERROR: '") !== false) {
        $re = "/exit\(\s*'DB ERROR: '.*?\)\s*;/s";
        if (preg_match_all($re, $code) !== 1) return null;
        $code = preg_replace_callback($re, fn() => "if (function_exists('owFriendlyError')) owFriendlyError(503);\n"
            . "        exit('A server error occurred. Please try again later.');", $code, 1);
    }
    return $code;
}

/* Can we sign in to the database with this password? */
function owDbWorks(#[\SensitiveParameter] string $pass): bool {
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, $pass,
                       [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8]);
        $pdo->query('SELECT 1');
        return true;
    } catch (Throwable $e) {
        error_log('config_setup: database sign-in test failed (' . $e->getCode() . ')');
        return false;
    }
}

/* Does the mail server accept this password? Ports 465 then 587, signs in only */
function owMailWorks(#[\SensitiveParameter] string $pass, array &$log): bool {
    $log = [];
    $msg = owMailMessage('contact@elitport.com', 'check', 'check', false);
    $host = owMailSetting('SMTP_HOST', 'smtp.hostinger.com');
    foreach ([465, 587] as $port) {
        if (owSmtpSend('contact@elitport.com', $msg, $host, $port, $log, true, $pass)) return true;
    }
    return false;
}

function owStatus(): array {
    $c = owCfgRead();
    $dbPass = defined('DB_PASS') ? (string) DB_PASS : '';
    $smPass = defined('SMTP_PASS') ? (string) SMTP_PASS : '';
    $mailLog = [];
    return [
        'config_found'        => $c !== '',
        'config_writable'     => is_writable(OW_CFG) && is_writable(__DIR__),
        'errors_shown'        => (bool) preg_match("/ini_set\(\s*'display_errors'\s*,\s*'1'/", $c),
        'error_guard_loaded'  => defined('OW_ERROR_GUARD'),
        'db_error_details'    => strpos($c, "'DB ERROR: '") !== false,
        'database_connects'   => owDbWorks($dbPass),
        'mail_signs_in'       => owMailWorks($smPass, $mailLog),
        'passwords_separate'  => $dbPass !== '' && $smPass !== '' && !hash_equals($dbPass, $smPass),
        'safety_copy_kept'    => (bool) glob(__DIR__ . '/.backups/config-original-*.php'),
        'switches_off_at_utc' => gmdate('Y-m-d H:i', OW_SETUP_UNTIL),
    ];
}

function owPassProblem(#[\SensitiveParameter] string $p): string {
    if ($p === '') return 'Please type the new password.';
    if (strlen($p) < 12) return 'Please use at least 12 characters.';
    if (strlen($p) > 128 || preg_match('/[\x00-\x1F\x7F]/', $p)) return 'That password has characters the site cannot use. Please choose another.';
    return '';
}

/* ---------------------------------------------------------------------
 *  Key access (no admin session): status and debug off only, JSON reply
 * ------------------------------------------------------------------- */
if (!$isAdmin) {
    header('Content-Type: application/json; charset=UTF-8');
    $do = (string) ($_POST['do'] ?? 'status');
    if ($do === 'debug_off') {
        $old = owCfgRead();
        $new = $old === '' ? null : owCfgDebugOff($old);
        [$ok, $msg] = $new === null ? [false, 'Expected lines not found; nothing changed.'] : owCfgApply($old, $new);
        echo json_encode(['ok' => $ok, 'message' => $msg]);
    } else {
        echo json_encode(owStatus());
    }
    exit;
}

/* ---------------------------------------------------------------------
 *  Admin page
 * ------------------------------------------------------------------- */
$flash = []; $flashOk = true;
if ($post) {
    $tries = array_values(array_filter((array) ($_SESSION['ow_setup_tries'] ?? []), fn($t) => is_int($t) && $t > time() - 3600));
    $do = (string) ($_POST['do'] ?? '');
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        [$flashOk, $flash] = [false, ['The page was open too long. Please try again.']];
    } elseif (in_array($do, ['db_pass', 'mail_pass'], true) && count($tries) >= 15) {
        [$flashOk, $flash] = [false, ['Too many tries in one hour. Please wait a little.']];
    } elseif ($do === 'debug_off') {
        $old = owCfgRead();
        $new = $old === '' ? null : owCfgDebugOff($old);
        [$flashOk, $m] = $new === null ? [false, 'Some expected lines were not found in config.php, so nothing was changed.'] : owCfgApply($old, $new);
        $flash = [$m];
    } elseif ($do === 'db_pass' || $do === 'mail_pass') {
        $tries[] = time();
        $_SESSION['ow_setup_tries'] = $tries;
        $p = (string) ($_POST['new_pass'] ?? '');
        unset($_POST['new_pass']);
        $curDb = defined('DB_PASS') ? (string) DB_PASS : '';
        $curMail = defined('SMTP_PASS') ? (string) SMTP_PASS : '';
        $err = owPassProblem($p);
        if ($err === '' && ($do === 'db_pass' ? hash_equals($curMail, $p) : hash_equals($curDb, $p))) {
            $err = 'This is the same as the ' . ($do === 'db_pass' ? 'email' : 'database') . ' password. The two must be different.';
        }
        if ($err === '' && hash_equals($do === 'db_pass' ? $curDb : $curMail, $p)) {
            $err = 'This is the password already saved. Please set a new one in hPanel first.';
        }
        if ($err === '') {
            $mailLog = [];
            $works = $do === 'db_pass' ? owDbWorks($p) : owMailWorks($p, $mailLog);
            if (!$works) {
                $err = $do === 'db_pass'
                    ? 'The database did not accept this password. Nothing was saved. Check it matches the one you set in hPanel → Databases.'
                    : 'The mail server did not accept this password. Nothing was saved. Check it matches the one you set in hPanel → Emails.';
            }
        }
        if ($err === '') {
            $old = owCfgRead();
            $new = $old === '' ? null : owCfgSetDefine($old, $do === 'db_pass' ? 'DB_PASS' : 'SMTP_PASS', $p);
            if ($new === null) $err = 'The password line was not found in config.php, so nothing was changed.';
            else { [$ok, $m] = owCfgApply($old, $new); if (!$ok) $err = $m; else $flash = ['Password tested and saved. ' . preg_replace('/^Saved\. /', '', $m)]; }
        }
        $p = '';
        if ($err !== '') [$flashOk, $flash] = [false, [$err]];
        if ($flashOk) error_log('config_setup: ' . ($do === 'db_pass' ? 'database' : 'email') . ' password changed by an admin');
    } elseif ($do === 'test_mail') {
        $log = [];
        $sent = owSendMail('contact@elitport.com', 'Oweili test email · رسالة تجريبية',
            "This is a test from the Oweili password setup page.\nهذه رسالة تجريبية من صفحة إعداد كلمات المرور.", false, $log);
        [$flashOk, $flash] = [$sent, [$sent ? 'Test email sent to contact@elitport.com.' : 'The test email could not be sent.']];
        if (!$sent) $flash = array_merge($flash, $log);
    }
    if ($flashOk) { $_SESSION['ow_setup_flash'] = $flash; header('Location: config_setup.php'); exit; }   // no resubmits
}
if (!$post && !empty($_SESSION['ow_setup_flash'])) { $flash = (array) $_SESSION['ow_setup_flash']; unset($_SESSION['ow_setup_flash']); }
$st = owStatus();
$yes = fn(bool $b, bool $good = true) => '<span class="st ' . ($b === $good ? 'st-ok' : 'st-bad') . '">' . ($b ? 'Yes' : 'No') . '</span>';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>Password setup · Oweili</title>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
.wrap{max-width:720px;margin:0 auto;padding:var(--space-lg) var(--space-sm);}
.wrap .card{margin-bottom:var(--space-md);}
.wrap h1{font-size:28px;margin:0 0 var(--space-xs);}
.wrap h2{font-size:20px;margin:0 0 var(--space-xs);}
.wrap p{margin:0 0 var(--space-sm);}
.st-table{width:100%;border-collapse:collapse;font-size:15px;}
.st-table td{padding:8px 0;border-bottom:1px solid var(--color-border);}
.st-table td:last-child{text-align:right;}
.st{font-weight:700;font-size:13px;padding:2px 10px;border-radius:999px;}
.st-ok{background:rgba(var(--rgb-accent),.1);color:var(--color-accent);}
.st-bad{background:rgba(var(--rgb-primary),.18);color:var(--color-dark);}
.top{display:flex;justify-content:space-between;align-items:center;gap:var(--space-sm);flex-wrap:wrap;margin-bottom:var(--space-md);}
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <a class="btn btn-ghost btn-sm" href="admin.php">← Back to admin</a>
    <span class="st st-bad">Temporary page · switches off <?= e(gmdate('j M, H:i', OW_SETUP_UNTIL)) ?> UTC</span>
  </div>
  <h1>Password setup</h1>
  <p>Each password is tested first. It is saved only if it works, and it is never shown again.</p>

  <?php if ($flash): ?>
    <div class="<?= $flashOk ? 'alert-ok' : 'alert-error' ?>" role="status"><?php foreach ($flash as $f): ?><div><?= e((string) $f) ?></div><?php endforeach; ?></div>
  <?php endif; ?>

  <div class="card">
    <h2>Right now</h2>
    <table class="st-table">
      <tr><td>Visitors see error details</td><td><?= $yes($st['errors_shown'] || $st['db_error_details'], false) ?></td></tr>
      <tr><td>Errors go to the private log</td><td><?= $yes($st['error_guard_loaded']) ?></td></tr>
      <tr><td>Database connects</td><td><?= $yes($st['database_connects']) ?></td></tr>
      <tr><td>Email box signs in</td><td><?= $yes($st['mail_signs_in']) ?></td></tr>
      <tr><td>Database and email passwords are different</td><td><?= $yes($st['passwords_separate']) ?></td></tr>
    </table>
    <?php if ($st['errors_shown'] || $st['db_error_details'] || !$st['error_guard_loaded']): ?>
    <form method="post" style="margin-top:var(--space-sm)">
      <?= csrfField() ?><input type="hidden" name="do" value="debug_off">
      <button class="btn btn-primary" type="submit">Hide errors from visitors</button>
    </form>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>1. New database password</h2>
    <p>Type the new password you just set in hPanel → Databases.</p>
    <form method="post" autocomplete="off">
      <?= csrfField() ?><input type="hidden" name="do" value="db_pass">
      <div class="field"><label for="dbp">New database password</label>
        <input id="dbp" type="password" name="new_pass" autocomplete="new-password" required minlength="12"></div>
      <button class="btn btn-primary" type="submit">Test and save</button>
    </form>
  </div>

  <div class="card">
    <h2>2. New email password</h2>
    <p>Type the new password you just set in hPanel → Emails for <?= e(owMailSetting('SMTP_USER')) ?>. It must be different from the database password.</p>
    <form method="post" autocomplete="off">
      <?= csrfField() ?><input type="hidden" name="do" value="mail_pass">
      <div class="field"><label for="mp">New email password</label>
        <input id="mp" type="password" name="new_pass" autocomplete="new-password" required minlength="12"></div>
      <button class="btn btn-primary" type="submit">Test and save</button>
    </form>
  </div>

  <div class="card">
    <h2>3. Send a test email</h2>
    <p>Sends a short test to contact@elitport.com with the saved settings.</p>
    <form method="post">
      <?= csrfField() ?><input type="hidden" name="do" value="test_mail">
      <button class="btn btn-secondary" type="submit">Send test email</button>
    </form>
  </div>
</div>
</body>
</html>
