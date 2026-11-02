<?php
/* =====================================================================
 *  training.php: retired address. Art Institutes have their own page,
 *  institutes.php (2 Nov 2026). Old links and bookmarks are sent there for
 *  good (301), keeping the city filter and the language. Tables: none
 * ===================================================================== */
$q = [];
$city = trim((string) ($_GET['city'] ?? ''));
if ($city !== '' && mb_strlen($city) <= 120) $q['city'] = $city;
if (($_GET['lang'] ?? '') === 'ar') $q['lang'] = 'ar';
header('Location: institutes.php' . ($q ? '?' . http_build_query($q) : ''), true, 301);
exit;
