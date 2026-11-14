<?php
/* =====================================================================
 *  ticket_qr.php?k=TOKEN — the ticket's QR code as a PNG picture, for the
 *  ticket email (email apps do not show SVG). Made with GD, never stored.
 *  Tables: museum_tickets
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/booking_lib.php';
require_once __DIR__ . '/qr_lib.php';
$k = (string) ($_GET['k'] ?? '');
$t = null;
if (owBookingsReady() && preg_match('/^[a-f0-9]{40}$/', $k)) { $st = getDB()->prepare("SELECT * FROM museum_tickets WHERE qr_token = :k AND status = 'paid'"); $st->execute([':k' => $k]); $t = $st->fetch() ?: null; }
if (!$t || !function_exists('imagecreatetruecolor')) { http_response_code(404); exit; }
$m = owQrMatrix(owTicketCheckUrl($t)); $n = count($m); $q = 4; $s = 8; $size = ($n + 2 * $q) * $s;
$im = imagecreatetruecolor($size, $size);
$white = imagecolorallocate($im, 255, 255, 255); $black = imagecolorallocate($im, 0, 0, 0);
imagefilledrectangle($im, 0, 0, $size, $size, $white);
foreach ($m as $y => $row) foreach ($row as $x => $dark) if ($dark) imagefilledrectangle($im, ($x + $q) * $s, ($y + $q) * $s, ($x + $q + 1) * $s - 1, ($y + $q + 1) * $s - 1, $black);
header('Content-Type: image/png'); header('Cache-Control: private, no-store'); header('X-Robots-Tag: noindex');
imagepng($im); imagedestroy($im);
