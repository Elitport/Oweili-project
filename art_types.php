<?php
/* =====================================================================
 *  art_types.php — single source of truth for artwork types (slug + labels).
 *  Include where needed:  $ART_TYPES = require __DIR__ . '/art_types.php';
 *  Keys are the DB values stored in artworks.type (VARCHAR).
 * ===================================================================== */
return [
    'abstract'    => ['en' => 'Abstract',        'ar' => 'تجريدي'],
    'landscape'   => ['en' => 'Landscape',       'ar' => 'مناظر طبيعية'],
    'portrait'    => ['en' => 'Portrait',        'ar' => 'بورتريه'],
    'watercolor'  => ['en' => 'Watercolor',      'ar' => 'ألوان مائية'],
    'oil'         => ['en' => 'Oil Painting',    'ar' => 'رسم زيتي'],
    'digital_illustration' => ['en' => 'Digital Illustration', 'ar' => 'رسم توضيحي رقمي'],
    'digital_painting'     => ['en' => 'Digital Painting',     'ar' => 'رسم رقمي'],
    'vector_art'           => ['en' => 'Vector Art',           'ar' => 'فن المتجهات'],
    'charcoal'    => ['en' => 'Charcoal',        'ar' => 'فحم'],
    'acrylic'     => ['en' => 'Acrylic',         'ar' => 'أكريليك'],
    'pastel'      => ['en' => 'Pastel',          'ar' => 'باستيل'],
    'ink'         => ['en' => 'Ink & Pen',       'ar' => 'حبر وقلم'],
    'collage'     => ['en' => 'Collage',         'ar' => 'كولاج'],
    'gouache'     => ['en' => 'Gouache',         'ar' => 'غواش'],
    'printmaking' => ['en' => 'Printmaking',     'ar' => 'طباعة فنية'],
    '3d'          => ['en' => '3D Art',          'ar' => 'فن ثلاثي الأبعاد'],
    'photography' => ['en' => 'Photography',     'ar' => 'تصوير فوتوغرافي'],
    'sculpture'   => ['en' => 'Sculpture',       'ar' => 'نحت'],
    'calligraphy' => ['en' => 'Calligraphy',     'ar' => 'خط عربي'],
    'mosaic'      => ['en' => 'Mosaic',          'ar' => 'فسيفساء'],
    'mixed_media'       => ['en' => 'Mixed Media',       'ar' => 'وسائط مختلطة'],
    'digital_art'       => ['en' => 'Digital Art',       'ar' => 'فن رقمي'],
    'islamic_geometric' => ['en' => 'Islamic Geometric', 'ar' => 'فن هندسي إسلامي'],
    'other'       => ['en' => 'Other',           'ar' => 'أخرى'],
];
