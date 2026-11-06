<?php
/* TEMPORARY test page (owner request 6 Nov 2026): one marked test of each section 4b kind. Removed right after use. */
header('Content-Type: application/json'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
if (!hash_equals('9c1f3eefa90bad1a91b8e612f2159b07aeb53a8828a111389f2294f0f486a54b', hash('sha256', (string) ($_GET['t'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/bashro_send.php';
$out = ['ready' => owBashroReady(), 'signup_on' => owBashroKindOn('signup'), 'approval_on' => owBashroKindOn('approval')];
if (($_GET['send'] ?? '') === '1' && owBashroReady()) {
    $tests = [
        'signup' => ['role' => 'buyer', 'name' => 'Oweili test (please ignore)', 'email' => owContactEmail(), 'city' => 'Jeddah', 'joined' => date('Y-m-d')],
        'approval' => ['ref' => 'oweili-test-' . date('YmdHis'), 'type' => 'artwork_new', 'title' => 'TEST from Oweili (please ignore)', 'link' => 'https://oweili.com/admin.php#pending'],
        'account_deleted' => ['email' => 'deleted-test-' . date('YmdHis') . '@oweili.com'],
    ];
    foreach ($tests as $k => $m) {
        $st = owBashroPost(json_encode(owBashroClean($k, $m), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        owLog('bashro_send', 'bashro', null, $k . ' ' . ($st ?: 'no answer') . ' (test)', ['type' => 'admin', 'id' => null]);
        $out[$k] = $st;
    }
}
echo json_encode($out);
