<?php
/* =====================================================================
 *  live_check.php — TEMPORARY (3 Nov 2026), removed straight after use.
 *    bashro  do BASHRO_DOOR_SITE / BASHRO_DOOR_KEY look right? (never prints them)
 *    send    sends ONE test "I'm Interested" to Bashro and shows its answer
 *    ip      how this server sees the visitor's address (for clientIp in config.php)
 *  Only with the one-time key (only its SHA-256 is stored here). POST only.
 * ===================================================================== */
const OW_LC_UNTIL = 1793714617;
const OW_LC_HASH  = 'c33678a3c8510f4a6b0cc1a8fc47357840e0c83e57f78f4c646c7fe00a8886e3';
if (time() > OW_LC_UNTIL || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !is_string($_POST['key'] ?? null) || !hash_equals(OW_LC_HASH, hash('sha256', (string) $_POST['key']))) { http_response_code(404); exit; }
require_once 'config.php';
require_once __DIR__ . '/bashro_send.php';
header('Content-Type: text/plain; charset=UTF-8'); header('Cache-Control: no-store');
$step = (string) ($_POST['step'] ?? '');
if ($step === 'bashro') {
    $rawSite = defined('BASHRO_DOOR_SITE') ? (string) BASHRO_DOOR_SITE : null; $rawKey = defined('BASHRO_DOOR_KEY') ? (string) BASHRO_DOOR_KEY : null;
    echo 'BASHRO_DOOR_SITE defined: ', $rawSite === null ? 'no' : 'yes', $rawSite !== null ? ' · digits only: ' . (preg_match('/^\d{1,6}$/', trim($rawSite)) ? 'yes' : 'no') . ' · equals 3: ' . (trim($rawSite) === '3' ? 'yes' : 'no') . ' · had spaces: ' . ($rawSite !== trim($rawSite) ? 'yes' : 'no') : '', "\n";
    echo 'BASHRO_DOOR_KEY defined: ', $rawKey === null ? 'no' : 'yes', $rawKey !== null ? ' · length ' . strlen(trim($rawKey)) . ' · only 0-9 a-f: ' . (preg_match('/^[0-9a-fA-F]+$/', trim($rawKey)) ? 'yes' : 'no') . ' · had spaces: ' . ($rawKey !== trim($rawKey) ? 'yes' : 'no') . ' · had capitals: ' . (preg_match('/[A-F]/', $rawKey) ? 'yes' : 'no') : '', "\n";
    echo 'other door lines: URL=', defined('BASHRO_DOOR_URL') ? 'yes (should not be there)' : 'no', ' SIGNUPS=', defined('BASHRO_DOOR_SIGNUPS') ? var_export(BASHRO_DOOR_SIGNUPS, true) : 'no', ' APPROVALS=', defined('BASHRO_DOOR_APPROVALS') ? var_export(BASHRO_DOOR_APPROVALS, true) : 'no', "\n";
    echo 'sender ready: ', owBashroReady() ? 'yes' : 'no', ' · quote kind on: ', owBashroKindOn('quote') ? 'yes' : 'no', ' · curl: ', function_exists('curl_init') ? 'yes' : 'no', "\n";
    exit;
}
if ($step === 'send') {
    if (!owBashroReady()) { echo "not ready\n"; exit; }
    $body = json_encode(owBashroClean('quote', ['name' => 'Oweili test (please ignore)', 'email' => 'contact@oweili.com', 'phone' => '0500000000', 'service' => 'TEST artwork', 'lang' => 'en',
        'message' => 'TEST from oweili.com, 3 Nov 2026: one test "I\'m Interested" to check the connection. Please ignore or delete. Interested in: TEST artwork by Oweili, 1 SAR, https://oweili.com/marketplace.php']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $t0 = microtime(true); $status = owBashroPost($body);
    owLog('bashro_send', 'bashro', null, 'quote ' . ($status ?: 'no answer') . ' (connection test)', ['type' => 'system', 'id' => null]);
    echo 'Bashro answered: ', $status ?: 'no answer', ' (', round((microtime(true) - $t0) * 1000), " ms)\n";
    echo $status === 202 ? "ACCEPTED\n" : "NOT accepted\n";
    exit;
}
if ($step === 'cfg') {
    $f = __DIR__ . '/config.php';
    echo 'config.php loaded from this folder: ', in_array(realpath($f), array_map('realpath', get_included_files()), true) ? 'yes' : 'no', ' · size ', filesize($f), ' bytes · changed ', gmdate('Y-m-d H:i', filemtime($f)), " UTC\n";
    echo 'opcache on: ', (function_exists('opcache_get_status') && @opcache_get_status(false)) ? 'yes' : 'no', "\n";
    $lines = file($f, FILE_IGNORE_NEW_LINES); $closeAt = null; $returnAt = null;
    foreach ($lines as $i => $l) { if ($closeAt === null && preg_match('/\?>/', $l)) $closeAt = $i + 1; if ($returnAt === null && preg_match('/^\s*(return|exit|die)\b/', $l)) $returnAt = $i + 1; }
    echo 'lines: ', count($lines), ' · first "?>" at line: ', $closeAt ?? 'none', ' · first top-level return/exit at line: ', $returnAt ?? 'none', "\n";
    foreach ($lines as $i => $l) {
        if (stripos($l, 'BASHRO') === false) continue;
        $mask = preg_replace("/'([0-9a-fA-F]{12,})'/", "'<key, " . "' . 'len>'", $l);
        $mask = preg_replace_callback("/(['\"])([0-9a-fA-F]{12,})\\1/", fn($m) => $m[1] . '<' . strlen($m[2]) . ' hex characters>' . $m[1], $l);
        $mask = preg_replace('/[^\x20-\x7E]/', '?', $mask);
        echo 'line ', $i + 1, ': ', mb_substr($mask, 0, 160), "\n";
    }
    exit;
}
if ($step === 'ip') {
    foreach (['REMOTE_ADDR', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CF_CONNECTING_IP', 'HTTP_X_CLIENT_IP', 'HTTP_TRUE_CLIENT_IP'] as $h) echo str_pad($h, 24), (string) ($_SERVER[$h] ?? '-'), "\n";
    echo 'clientIp() gives        ', function_exists('clientIp') ? clientIp() : '(no clientIp function)', "\n";
    exit;
}
echo "unknown step\n";
