<?php
/* TEMPORARY check page (owner request 6 Nov 2026): yes/no only, never the key. Removed right after use. */
header('Content-Type: application/json'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
if (!hash_equals('a0f1fd0ad16ec97a77a3522c970e4a07a7a73cb8e454095521f64cf8e116aa0d', hash('sha256', (string) ($_GET['t'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/bashro_send.php';
$out = ['site_defined' => defined('BASHRO_DOOR_SITE'), 'site_is_3' => owBashroSite() === '3', 'key_defined' => defined('BASHRO_DOOR_KEY'), 'key_is_64_hex' => preg_match('/^[0-9a-f]{64}$/', owBashroKey()) === 1, 'ready' => owBashroReady()];
if (($_GET['send'] ?? '') === '1' && owBashroReady()) $out['test_status'] = owBashroTest();
echo json_encode($out);
