<?php
/* =====================================================================
 *  invoice.php?no=OW-2026-00001 — one invoice as a PDF (owner, 27 Nov 2026).
 *  PRIVATE: only the owner (admin) and the customer it belongs to (their
 *  account, or their CONFIRMED email for a purchase made as a guest) can
 *  open it; everyone else gets 404. Never cached or indexed.
 *  &dl=1 downloads it, otherwise it opens in the browser.
 *  Tables: invoices (read), users (who is signed in), activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/invoice_lib.php';
require_once __DIR__ . '/admin_shell.php';
header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow');
$no = function () { http_response_code(404); exit('Not found'); };
$inv = owInvByNo(strtoupper(trim((string) ($_GET['no'] ?? ''))));
if (!$inv) $no();
$owner = owIsAdmin();
$me = (!$owner && isLoggedIn()) ? currentUser() : null;
if ($me && !empty($me['is_banned'])) $me = null;
if (!owInvCanOpen($inv, $me, $owner)) {
    if (!$owner && !isLoggedIn()) { header('Location: login.php?next=' . rawurlencode('invoice.php?no=' . $inv['invoice_no'])); exit; }
    $no();
}
try { $pdf = owInvoicePdf($inv); } catch (Throwable $e) { error_log('invoice.php: ' . $e->getMessage()); http_response_code(500); exit('Please try again'); }
owLog('invoice_open', 'invoice', (int) $inv['id'], (string) $inv['invoice_no'], $owner ? ['type' => 'admin', 'id' => null] : ['type' => owActorType($me['role'] ?? 'collector'), 'id' => (int) $me['id']]);
header('Content-Type: application/pdf');
header('Content-Length: ' . strlen($pdf));
header('Content-Disposition: ' . (isset($_GET['dl']) ? 'attachment' : 'inline') . '; filename="' . owInvFileName($inv) . '"');
header('X-Content-Type-Options: nosniff');
echo $pdf;
