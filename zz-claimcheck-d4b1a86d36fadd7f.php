<?php
/* TEMPORARY (owner, 13 Nov 2026): read-only check that the "Is this your place?" test message arrived.
   Shows only id, place, sender name, status, time, and the waiting count Admin shows. Locked by a secret key. Removed straight after. */
if (!hash_equals('e6a1ea7c866b38b6e62d47ea82cd8e64ffc8066d1316a353adb48e3bd47f98b6', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/artmap_lib.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
foreach (getDB()->query('SELECT id, place_name, name, status, created_at, photo_file <> "" AS photo FROM art_place_claims ORDER BY id') as $r)
    echo $r['id'], ' | ', $r['place_name'], ' | ', $r['name'], ' | ', $r['status'], ' | ', $r['created_at'], ' | photo ', $r['photo'] ? 'yes' : 'no', "\n";
echo 'Waiting count shown in Admin: ', owClaimsWaiting(), "\n";
