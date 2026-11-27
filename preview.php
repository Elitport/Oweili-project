<?php
/* =====================================================================
 *  preview.php?as=collector|artist|institute — the owner sees a dashboard
 *  exactly as its users see it, with FICTIONAL data, read-only
 *  (owner, 27 Nov 2026). The owner only (the admin password), never a
 *  sub-admin, never anyone else (404). See preview_lib.php for how it stays
 *  safe: private empty copies of every table for this one request, the
 *  owner's own session never changed, no email / alert / invoice / job,
 *  every form refused. Never cached or indexed.
 *  Tables: none changed (temporary copies only; site_settings read)
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/admin_shell.php';
require_once __DIR__ . '/preview_lib.php';
header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow');
$role = (string) ($_GET['as'] ?? '');
if (!isset(OW_PREVIEW_ROLES[$role]) || !owIsAdmin()) { http_response_code(404); require __DIR__ . '/404.php'; exit; }
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') { http_response_code(405); header('Allow: GET'); exit('Preview: nothing is saved · معاينة: لا يُحفظ شيء'); }
define('OW_PREVIEW', true);
/* the owner's own session is saved and closed now; what follows lives only in memory */
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
if (!owPreviewSandbox()) { http_response_code(503); exit('The preview could not be prepared. Nothing was changed.'); }
require_once __DIR__ . '/lang_lib.php';
$uid = owPreviewSeed($role);
if ($uid <= 0) { http_response_code(503); exit('The preview could not be prepared. Nothing was changed.'); }
$tok = bin2hex(random_bytes(32));
getDB()->prepare('INSERT INTO sessions (user_id, session_token, ip_address, expires_at) VALUES (?, ?, ?, NOW() + INTERVAL 1 HOUR)')->execute([$uid, $tok, '']);
$_SESSION = ['user_id' => $uid, 'session_token' => $tok, 'csrf_token' => bin2hex(random_bytes(32))];
ob_start(fn(string $html) => owPreviewFilter($html, $role));
require __DIR__ . '/' . OW_PREVIEW_ROLES[$role][2];
