<?php
/* =====================================================================
 *  institutes.php: retired address (owner, 12 Nov 2026). Art Institutes are
 *  now the Institutes tab of the Art Map (art_map.php), which took their place
 *  in the top menu. Old links and bookmarks are sent there for good (301),
 *  keeping the city (as one of the 13 Art Map regions) and the language.
 *  The old page is kept in git history (before 12 Nov 2026). Tables: none
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/artmap_lib.php';
$q = ['tab' => 'institutes'];
$city = trim((string) ($_GET['city'] ?? ''));
if ($city !== '' && mb_strlen($city) <= 120 && ($r = owMapRegionKey($city)) !== '') $q['region'] = $r;
if (in_array($_GET['lang'] ?? '', ['ar', 'en'], true)) $q['lang'] = $_GET['lang'];
header('Location: art_map.php?' . http_build_query($q), true, 301);
exit;
