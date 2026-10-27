<?php
/* =====================================================================
 *  art_categories.php — the 6 Gallery categories and which art types
 *  (keys from art_types.php) belong to each one.
 *  Include where needed:  $ART_CATS = require __DIR__ . '/art_categories.php';
 *  "all" has no list: it shows every type.
 *  Built from each type's encyclopedia group, so a new style lands in the
 *  right category by itself. Nature & Portrait styles sit in Traditional.
 *  Types with no group (sculpture, other) show under All only.
 * ===================================================================== */
$owTypes = require __DIR__ . '/art_types.php';
$owCats = [
    'all'          => ['en' => 'All',              'ar' => 'الكل',        'from' => []],
    'traditional'  => ['en' => 'Traditional',      'ar' => 'تقليدي',      'from' => ['traditional', 'nature']],
    'digital'      => ['en' => 'Digital',          'ar' => 'رقمي',        'from' => ['digital']],
    'islamic'      => ['en' => 'Islamic Heritage', 'ar' => 'تراث إسلامي', 'from' => ['heritage']],
    'contemporary' => ['en' => 'Contemporary',     'ar' => 'معاصر',       'from' => ['contemporary']],
    'photography'  => ['en' => 'Photography',      'ar' => 'تصوير',       'from' => ['photography']],
];
$owOut = [];
foreach ($owCats as $k => $c) {
    $types = [];
    foreach ($owTypes as $slug => $t) { if (in_array($t['cat'] ?? '', $c['from'], true)) $types[] = $slug; }
    $owOut[$k] = ['en' => $c['en'], 'ar' => $c['ar'], 'types' => $types];
}
return $owOut;
