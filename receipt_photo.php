<?php
/* =====================================================================
 *  receipt_photo.php?o=ORDER&n=0..2 — one photo the collector sent with
 *  "I received the artwork" (owner, 14 Nov 2026). PRIVATE: only the admin and
 *  the artist of that order can open it; everyone else gets 404. Never cached
 *  or indexed. The files live in .private/receipts/ (blocked for visitors).
 *  Tables: orders, activity_log (the receipt record)
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/order_lib.php';
require_once __DIR__ . '/admin_shell.php';
$no = function () { http_response_code(404); header('Cache-Control: no-store'); exit('Not found.'); };
if (!owOrdersReady()) $no();
$o = owOrderGet((int) ($_GET['o'] ?? 0));
if (!$o) $no();
$who = owIsAdmin() ? 'admin' : '';
if ($who === '' && isLoggedIn() && ($u = currentUser()) && ($u['role'] ?? '') === 'artist' && empty($u['is_banned']) && (int) $u['id'] === (int) $o['artist_id']) $who = 'artist';
if ($who === '') $no();
$r = owOrderReceipt((int) $o['id']);
$name = $r['photos'][(int) ($_GET['n'] ?? -1)] ?? '';
$path = __DIR__ . '/' . OW_RECEIPT_DIR . basename((string) $name);
if ($name === '' || !is_file($path)) $no();
$mime = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'][pathinfo($path, PATHINFO_EXTENSION)] ?? 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="oweili-receipt-' . (int) $o['id'] . '-' . (int) $_GET['n'] . '.' . pathinfo($path, PATHINFO_EXTENSION) . '"');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: private, no-store, max-age=0');
header('Referrer-Policy: no-referrer');
readfile($path);
