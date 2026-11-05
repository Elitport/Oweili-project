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
 *
 *  Where they are used: homepage (behind the video), Join as an artist,
 *  Gifts, Explore Art Styles, the 404 page and the empty Gallery. Still to
 *  come when the owner sends the files: "hand on the palette" → Commissions,
 *  "woman looking at framed paintings" → Art Events / Discover,
 *  "studio table with brushes" → Support. Add them to OW_STUDIO with the
 *  same file names (keys palette-hand, gallery-visit, studio-table).
 * ===================================================================== */
require_once __DIR__ . '/ow_paths.php';

const OW_STUDIO = [
    // key => [tall widths, wide widths, tall w/h, wide w/h, alt EN, alt AR]
    'home-painting' => [[640, 960], [1333], [2, 3], [16, 9],
        'An artist painting a sunny landscape on an easel outdoors',
        'فنانة ترسم منظراً طبيعياً مشمساً على حامل لوحات في الهواء الطلق'],
    'brushes-jars'  => [[640], [1280], [4, 3], [12, 5],
        'Well-used paint brushes standing in glass jars in a studio',
        'فُرَش رسم مستعملة في برطمانات زجاجية داخل مرسم'],
    'pink-palette'  => [[640], [1280], [4, 3], [12, 5],
        'A wooden palette with pink and orange paint beside paint brushes',
        'لوحة ألوان خشبية بألوان وردية وبرتقالية بجانب فُرَش الرسم'],
    'paint-buckets' => [[640], [1280], [4, 3], [12, 5],
        'A hand dipping a brush into buckets of colourful paint, seen from above',
        'يد تغمس فرشاة في علب ألوان زاهية، من الأعلى'],
    'paint-cloth'   => [[640], [1280], [4, 3], [12, 5],
        'A paint-splattered cloth with bowls of colour and brushes',
        'قماش مرشوش بالألوان مع أوعية الألوان والفُرَش'],
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
