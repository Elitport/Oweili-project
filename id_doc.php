<?php
/* =====================================================================
 *  id_doc.php?c=CHECK&f=id|num|address|pN — opens one private identity
 *  file (owner, 27 Nov 2026): the identity document photo (id), the full
 *  document number (num), an institute's National Address certificate
 *  (address) or one of its premises photos (p + photo id).
 *  Only the owner, and the sub-admins the owner ticks ("Identity documents"
 *  in Owner settings and team). Everyone else, the account itself
 *  included, gets 404. Never cached or indexed. Every opening is written
 *  to the activity log. Files are decrypted only here, in memory.
 *  Tables: identity_checks, identity_photos, activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/identity_lib.php';
header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
$nf = function () { http_response_code(404); require __DIR__ . '/404.php'; exit; };
$cid = (int) ($_GET['c'] ?? 0); $f = (string) ($_GET['f'] ?? '');
if ($cid <= 0 || !preg_match('/^(id|num|address|p\d{1,12})$/', $f) || !owIdReady() || !owIdAllowed()) $nf();
$st = getDB()->prepare('SELECT * FROM identity_checks WHERE id = :id AND purged_at IS NULL'); $st->execute([':id' => $cid]);
$c = $st->fetch(); if (!$c) $nf();
$actor = function () { require_once __DIR__ . '/admin_shell.php'; return owIsAdmin() ? ['type' => 'admin', 'id' => null] : ['type' => 'sub_admin', 'id' => (int) (currentUser()['id'] ?? 0)]; };
if ($f === 'num') {
    $num = $c['id_number_enc'] ? owIdDecrypt((string) base64_decode((string) $c['id_number_enc'])) : null;
    if ($num === null) $nf();
    owLog('identity_doc_open', 'identity_check', $cid, 'number', $actor());
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Identity number</title>'
       . '<style>body{font-family:Tahoma,Arial,sans-serif;color:#23314A;background:#fff;display:grid;place-items:center;min-height:90vh;margin:0}p{font-size:15px;margin:6px 0;text-align:center}b{font-size:26px;letter-spacing:2px}</style></head><body><div>'
       . '<p>' . htmlspecialchars(OW_ID_TYPES[$c['id_type']][0] ?? 'Document', ENT_QUOTES) . ' · <span dir="rtl">' . htmlspecialchars(OW_ID_TYPES[$c['id_type']][1] ?? '', ENT_QUOTES) . '</span></p>'
       . '<p><b dir="ltr">' . htmlspecialchars($num, ENT_QUOTES) . '</b></p><p>' . htmlspecialchars((string) $c['person_name'], ENT_QUOTES) . '</p>'
       . '<p style="font-size:13px">This opening is recorded · تم تسجيل هذا الاطلاع</p></div></body></html>';
    exit;
}
if ($f === 'id') $name = (string) $c['id_file'];
elseif ($f === 'address') $name = (string) $c['address_file'];
else { $p = getDB()->prepare('SELECT file_name FROM identity_photos WHERE id = :p AND check_id = :c'); $p->execute([':p' => (int) substr($f, 1), ':c' => $cid]); $name = (string) $p->fetchColumn(); }
$file = $name !== '' ? owIdReadFile($name) : null;
if (!$file) $nf();
owLog('identity_doc_open', 'identity_check', $cid, $f, $actor());
header('Content-Type: ' . $file[1]);
header('Content-Disposition: inline; filename="identity-' . $cid . '-' . $f . ($file[1] === 'application/pdf' ? '.pdf' : '.jpg') . '"');
header('Content-Length: ' . strlen($file[0]));
echo $file[0];
