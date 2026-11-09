<?php
/* TEMPORARY read-only check (owner, 9 Nov 2026): lists the live database's tables and columns (structure only, no data). Locked by a secret key. Removed straight after. */
if (!hash_equals('1cd255745f29515402ca8042f8231f3dcce6385517d1c8d1883d264ba85ba449', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
$db = getDB();
$st = $db->query("SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME, ORDINAL_POSITION");
foreach ($st as $r) echo $r['TABLE_NAME'], "\t", $r['COLUMN_NAME'], "\t", $r['COLUMN_TYPE'], "\t", $r['IS_NULLABLE'], "\t", $r['COLUMN_KEY'], "\n";
