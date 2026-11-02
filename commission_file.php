<?php
/* =====================================================================
 *  commission_file.php — the ONLY way to open a private commission photo
 *  (reference photos, drafts, finals in .private/commissions/).
 *  Allowed: the admin; the buyer with their private link (?ref=&k=); the
 *  assigned artist, signed in, AFTER accepting the confidentiality
 *  agreement (commission_nda). Everyone else: 404. Never cached, never
 *  indexed. Artists only see the files of commissions assigned to them,
 *  and never after the commission is cancelled.
 *  Tables: commission_files, commissions, commission_nda
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/commission_lib.php';
require_once __DIR__ . '/admin_shell.php';
$no = function () { http_response_code(404); header('Cache-Control: no-store'); exit('Not found.'); };
if (!owCommReady()) $no();
$st = getDB()->prepare('SELECT f.*, c.artist_id, c.status, c.ref_no, c.access_hash FROM commission_files f JOIN commissions c ON c.id = f.commission_id WHERE f.id = :id AND f.deleted_at IS NULL');
$st->execute([':id' => (int) ($_GET['id'] ?? 0)]);
$f = $st->fetch();
if (!$f) $no();
$who = '';
if (owIsAdmin()) $who = 'admin';
elseif (isset($_GET['k'], $_GET['ref']) && (string) $_GET['ref'] === (string) $f['ref_no'] && preg_match('/^[a-f0-9]{48}$/', (string) $_GET['k'])
        && hash_equals((string) $f['access_hash'], hash('sha256', (string) $_GET['k']))) $who = 'buyer';
elseif (isLoggedIn() && ($u = currentUser()) && ($u['role'] ?? '') === 'artist' && empty($u['is_banned']) && (int) $u['id'] === (int) $f['artist_id']
        && $f['status'] !== 'cancelled' && owCommNdaOk((int) $f['commission_id'], (int) $u['id'])) $who = 'artist';
if ($who === '') $no();
$path = __DIR__ . '/' . OW_COMM_DIR . basename((string) $f['file_name']);
if (!is_file($path)) $no();
if ($who === 'artist' && $f['kind'] === 'reference') owLog('commission_photo_view', 'commission', (int) $f['commission_id'], 'file ' . (int) $f['id']);
header('Content-Type: ' . (in_array($f['mime'], ['image/jpeg', 'image/png', 'image/webp'], true) ? $f['mime'] : 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="oweili-commission-' . (int) $f['id'] . '.' . pathinfo($path, PATHINFO_EXTENSION) . '"');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: private, no-store, max-age=0');
header('Referrer-Policy: no-referrer');
readfile($path);
