<?php
/* =====================================================================
 *  art_categories.php — the 6 Gallery categories and which art types
 *  (keys from art_types.php) belong to each one.
 *  Include where needed:  $ART_CATS = require __DIR__ . '/art_categories.php';
 *  "all" has no list: it shows every type.
 *  Types not listed in any category (sculpture, other) show under All only.
 * ===================================================================== */
return [
    'all'         => ['en' => 'All',              'ar' => 'الكل',        'types' => []],
    'traditional' => ['en' => 'Traditional',      'ar' => 'تقليدي',      'types' => [
        'watercolor', 'oil', 'charcoal', 'acrylic', 'ink', 'pastel', 'gouache', 'printmaking',
        'landscape', 'portrait',
    ]],
    'digital'     => ['en' => 'Digital',          'ar' => 'رقمي',        'types' => [
        'digital_illustration', 'digital_painting', 'digital_art', 'vector_art', '3d',
    ]],
    'islamic'     => ['en' => 'Islamic Heritage', 'ar' => 'تراث إسلامي', 'types' => [
        'calligraphy', 'islamic_geometric', 'mosaic',
    ]],
    'contemporary' => ['en' => 'Contemporary',    'ar' => 'معاصر',       'types' => [
        'abstract', 'collage', 'mixed_media',
    ]],
    'photography' => ['en' => 'Photography',      'ar' => 'تصوير',       'types' => [
        'photography',
    ]],
];
