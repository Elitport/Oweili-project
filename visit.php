<?php
/* visit.php — receives the small "a page was seen" signal sent by the
   browser after the page has loaded (theme_head.php, once per browser per
   day). Answers at once with "204 No content"; the country and city are
   looked up afterwards, so no visitor waits. See geo_lib.php.
   Saves only: date, country, city, +1. Never the internet address.
   Not counted: visits from data centers and cloud servers (owIsDataCenter). */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/geo_lib.php';
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

$ok = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
   && in_array($_SERVER['HTTP_SEC_FETCH_SITE'] ?? 'same-origin', ['same-origin', ''], true)   // only our own pages send it
   && empty($_COOKIE['ow_nostat'])                                                        // a browser that opened Admin
   && empty($_SESSION['is_admin'])                                                         // admin password session
   && !(function_exists('isLoggedIn') && isLoggedIn() && in_array((string) (currentUser()['role'] ?? ''), ['admin', 'sub_admin'], true))   // staff accounts
   && !owIsBot((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));

http_response_code(204);
header('Content-Length: 0');
if (function_exists('litespeed_finish_request')) litespeed_finish_request();
elseif (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
ignore_user_abort(true);

if ($ok) {
    require_once __DIR__ . '/visitor_lib.php';
    // data centers and cloud servers are not people
    try { $ip = owVisitorIp(); if (!owIsDataCenter($ip)) owGeoCount($ip); } catch (Throwable $e) { error_log('visit.php: ' . $e->getMessage()); }
}
try { owGeoMaybeUpdate(); } catch (Throwable $e) { error_log('owGeoMaybeUpdate: ' . $e->getMessage()); }
