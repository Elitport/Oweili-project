<?php
/* =====================================================================
 *  institute_doc.php?i=N&d=reg|licence — opens one institute's private
 *  document (owner, 25 Nov 2026). Only the owner (the admin) and the
 *  sub-admins the owner allows (owner.php → Owner settings and team).
 *  Everyone else, the institute itself included, gets 404. Never cached
 *  or indexed. Every opening is written to the activity log.
 *  Tables: institutes, activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/inst_lib.php';
header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow');
$id = (int) ($_GET['i'] ?? 0); $kind = (string) ($_GET['d'] ?? '');
$nf = function () { http_response_code(404); require __DIR__ . '/404.php'; exit; };
if ($id <= 0 || !in_array($kind, ['reg', 'licence'], true) || !owInstDocsReady() || !owInstDocsAllowed()) $nf();
$st = getDB()->prepare('SELECT reg_file, licence_file FROM institutes WHERE id = :id'); $st->execute([':id' => $id]);
$r = $st->fetch();
$path = $r ? owInstDocPath((string) $r[$kind === 'reg' ? 'reg_file' : 'licence_file']) : '';
if ($path === '' || !is_file($path)) $nf();
owLog('institute_doc_open', 'institute', $id, $kind);
$pdf = str_ends_with($path, '.pdf');
header('Content-Type: ' . ($pdf ? 'application/pdf' : 'image/jpeg'));
header('Content-Disposition: inline; filename="institute-' . $id . '-' . $kind . ($pdf ? '.pdf' : '.jpg') . '"');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($path));
readfile($path);
