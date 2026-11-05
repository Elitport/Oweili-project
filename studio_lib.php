<?php
/* =====================================================================
 *  studio_lib.php — the studio mood photos (5 Nov 2026)
 *
 *  Photos of an artist's studio that give the site its atmosphere. They
 *  are MOOD photos only: never shown as artworks for sale, never as the
 *  work of an Oweili artist, never in the Gallery, the sitemap or as an
 *  artwork's share picture.
 *
 *  Files: assets/img/studio/<key>-<tall|wide>-<width>.<avif|webp|jpg>
 *  (made once from the owner's originals: cropped for phone ("tall") and
 *  computer ("wide"), compressed, hidden data removed). Phones get the
 *  "tall" crop, screens over 640px the "wide" one; each browser takes the
 *  lightest format it can show (AVIF, then WebP, then JPEG).
 *
 *    owStudioPhoto($key, $eager)   the <picture> (lazy unless $eager)
 *    owStudioHas($key)             true when the files exist
 *    owStudioBackdrop(...$keys)    the photo as the background of the WHOLE
 *                                  page (the first key whose files exist):
 *                                  a still layer behind everything with a soft
 *                                  veil (light and dark mode). It is a fixed
 *                                  layer, not background-attachment: fixed,
 *                                  so it also stays still on iPhone
 *
 *  Where they are used: homepage (behind the video), Join as an artist,
 *  Gifts, Explore Art Styles, the 404 page and the empty Gallery; "hand on the
 *  palette" → Commissions, "woman looking at framed paintings" → Art Events,
 *  Artist Spotlights, Art Education, articles and Institutes, "studio table
 *  with brushes" → Support.
 * ===================================================================== */
require_once __DIR__ . '/ow_paths.php';

const OW_STUDIO = [
    // key => [phone widths, computer widths, phone w/h, computer w/h, alt EN, alt AR]
    // home-painting: the homepage photo behind the video. The others fill the WHOLE page
    // behind everything (owStudioBackdrop): 9:16 for phones, 16:9 for computers.
    'home-painting' => [[640, 960], [1333], [2, 3], [16, 9],
        'An artist painting a sunny landscape on an easel outdoors',
        'فنانة ترسم منظراً طبيعياً مشمساً على حامل لوحات في الهواء الطلق'],
    'brushes-jars'  => [[480, 720], [1280], [9, 16], [16, 9],
        'Well-used paint brushes standing in glass jars in a studio',
        'فُرَش رسم مستعملة في برطمانات زجاجية داخل مرسم'],
    'pink-palette'  => [[480, 632], [1280, 1500], [9, 16], [16, 9],
        'A wooden palette with pink and orange paint beside paint brushes',
        'لوحة ألوان خشبية بألوان وردية وبرتقالية بجانب فُرَش الرسم'],
    'paint-buckets' => [[480, 632], [1280, 1920], [9, 16], [16, 9],
        'A hand dipping a brush into buckets of colourful paint, seen from above',
        'يد تغمس فرشاة في علب ألوان زاهية، من الأعلى'],
    'paint-cloth'   => [[480, 720], [1280], [9, 16], [16, 9],
        'A paint-splattered cloth with bowls of colour and brushes',
        'قماش مرشوش بالألوان مع أوعية الألوان والفُرَش'],
    // the owner's last 3 photos (5 Nov 2026); palette-hand is cut so the painting of roses never shows
    'palette-hand'  => [[480, 720], [1100], [9, 16], [16, 9],
        'A hand holding a paint palette in a studio',
        'يد تمسك لوحة الألوان داخل مرسم'],
    'gallery-visit' => [[480, 720], [1280, 1920], [9, 16], [16, 9],
        'A woman looking at framed paintings in a gallery',
        'امرأة تتأمل لوحات مؤطرة في معرض'],
    'studio-table'  => [[480, 720], [1280, 1920], [9, 16], [16, 9],
        'A studio table with brushes and paints',
        'طاولة مرسم عليها فُرَش وألوان'],
];

function owStudioFile(string $key, string $var, int $w, string $ext): string {
    return 'assets/img/studio/' . $key . '-' . $var . '-' . $w . '.' . $ext;
}

function owStudioHas(string $key): bool {
    if (!isset(OW_STUDIO[$key])) return false;
    [$tall, $wide] = OW_STUDIO[$key];
    return is_file(__DIR__ . '/' . owStudioFile($key, 'wide', end($wide), 'jpg'))
        && is_file(__DIR__ . '/' . owStudioFile($key, 'tall', $tall[0], 'jpg'));
}

/* $sizesWide: how wide the photo is on a computer screen (for the browser's choice) */
function owStudioPhoto(string $key, bool $eager = false, string $class = 'ow-studio', string $sizesWide = '(min-width: 1240px) 1200px, 100vw'): string {
    if (!owStudioHas($key)) return '';
    [$tall, $wide, $ta, $wa, $altEn, $altAr] = OW_STUDIO[$key];
    $b = owBase();
    $set = function (string $var, array $widths, string $ext) use ($key, $b): string {
        return implode(', ', array_map(fn($w) => $b . owStudioFile($key, $var, $w, $ext) . ' ' . $w . 'w', $widths));
    };
    $h = '<picture class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">';
    foreach (['avif' => 'image/avif', 'webp' => 'image/webp'] as $ext => $mime) {
        $h .= '<source media="(min-width: 641px)" type="' . $mime . '" srcset="' . $set('wide', $wide, $ext) . '" sizes="' . $sizesWide . '">';
        $h .= '<source type="' . $mime . '" srcset="' . $set('tall', $tall, $ext) . '" sizes="100vw">';
    }
    $h .= '<source media="(min-width: 641px)" srcset="' . $set('wide', $wide, 'jpg') . '" sizes="' . $sizesWide . '">';
    $w0 = $tall[0]; $h0 = (int) round($w0 * $ta[1] / $ta[0]);
    $h .= '<img src="' . $b . owStudioFile($key, 'tall', $w0, 'jpg') . '" srcset="' . $set('tall', $tall, 'jpg') . '" sizes="100vw"'
        . ' width="' . $w0 . '" height="' . $h0 . '" decoding="async"'
        . ($eager ? ' fetchpriority="high"' : ' loading="lazy"')
        . ' alt="' . htmlspecialchars($altEn . ' · ' . $altAr, ENT_QUOTES, 'UTF-8') . '">';
    return $h . '</picture>';
}

/* The photo behind the whole page (5 Nov 2026). Printed right after <body>.
   Phones load a 480 / 720 wide copy (AVIF 13 to 68 KB), computers 1280+.
   The body becomes see-through so the layer shows behind every section. */
function owStudioBackdrop(string ...$keys): string {
    foreach ($keys as $k) {
        if (!owStudioHas($k)) continue;
        return '<style>html body{background:transparent!important}</style><div class="ow-backdrop">'
             . owStudioPhoto($k, true, 'ow-studio', '100vw') . '</div>';
    }
    return '';
}
