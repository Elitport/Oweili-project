<?php
/* TEMPORARY (owner, 15 Nov 2026): backs up the unused pink brush video before it is deleted, and checks that the server
   can reach Google Maps (for reading short map links in Admin → Art Map). Locked by a secret key. Removed straight after. */
if (!hash_equals('4cad6ebd60e37a4fdd2f5c33754ca20126dcd63b32d648ae0a8ecca62ffbdc80', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
$f = __DIR__ . '/assets/video/hero-pink-tall.mp4';
echo 'video: ', is_file($f) ? filesize($f) . ' bytes' : 'missing', "\n";
if (($_GET['a'] ?? '') === 'run') {
    $dir = __DIR__ . '/.backups/rejected-video-' . gmdate('Ymd-His');
    if (is_file($f) && @mkdir($dir, 0700, true) && @copy($f, $dir . '/hero-pink-tall.mp4') && filesize($dir . '/hero-pink-tall.mp4') === filesize($f)) echo 'backup ok: ', substr($dir, strlen(__DIR__) + 1), "/hero-pink-tall.mp4\n";
    else echo "backup FAILED\n";
}
if (($_GET['a'] ?? '') === 'net' && function_exists('curl_init')) {
    foreach (['https://maps.app.goo.gl/', 'https://www.google.com/maps?q=National+Museum+Riyadh'] as $u) {
        $ch = curl_init($u); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 8, CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; OweiliAdmin/1.0)']);
        $b = (string) curl_exec($ch); echo $u, ' => ', curl_getinfo($ch, CURLINFO_HTTP_CODE), ' redirect=', (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL) !== '' ? 'yes' : 'no', ' coords_in_page=', preg_match('/(?:center=|markers=)(-?\d{1,3}\.\d+)(?:%2C|,)(-?\d{1,3}\.\d+)/', $b) ? 'yes' : 'no', ' err=', curl_error($ch), "\n";
        curl_close($ch);
    }
}
