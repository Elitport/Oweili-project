<?php
/* auth_state.php — answers one short code: the visitor's sign-in state now
   (page_cache.php owStateCode). A page shown from a kept copy (Back button,
   or an early load on iPhone) compares it with the code it was made with and
   reloads when they differ, so a signed-out visitor never sees a signed-in
   page. Nothing else is sent; never stored by any cache. */
require_once 'config.php';
require_once __DIR__ . '/page_cache.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
echo json_encode(['s' => owStateCode()]);
