<?php
/* =====================================================================
 *  art_types.php — single source of truth for artwork types (slug + labels).
 *  Include where needed:  $ART_TYPES = require __DIR__ . '/art_types.php';
 *  Keys are the DB values stored in artworks.type (VARCHAR).
 *
 *  The 50 styles come from assets/js/art_styles.js (the encyclopedia), so
 *  names are never typed twice: key = style id, 'cat' = its encyclopedia
 *  group (traditional | digital | heritage | contemporary | nature | photography).
 *  Older types that some artworks still use are kept with 'legacy' => true:
 *  they still show their label, but the upload form no longer offers them.
 * ===================================================================== */
if (!function_exists('owLoadArtTypes')) {
    function owLoadArtTypes(): array {
        static $types = null;
        if ($types !== null) return $types;
        $types = [];
        $js = @file_get_contents(__DIR__ . '/assets/js/art_styles.js');
        if ($js !== false && ($p = strpos($js, 'OW_ART_STYLES =')) !== false) {
            $list = json_decode(rtrim(trim(substr($js, $p + strlen('OW_ART_STYLES ='))), ';'), true);
            foreach (is_array($list) ? $list : [] as $s) {
                if (!isset($s['id'], $s['cat'], $s['en']['name'], $s['ar']['name'])) continue;
                $types[(string) $s['id']] = ['en' => (string) $s['en']['name'], 'ar' => (string) $s['ar']['name'], 'cat' => (string) $s['cat']];
            }
        }
        $legacy = [
            'vector_art'  => ['en' => 'Vector Art',  'ar' => 'فن المتجهات',      'cat' => 'digital'],
            '3d'          => ['en' => '3D Art',      'ar' => 'فن ثلاثي الأبعاد', 'cat' => 'digital'],
            'digital_art' => ['en' => 'Digital Art', 'ar' => 'فن رقمي',          'cat' => 'digital'],
            'mixed_media' => ['en' => 'Mixed Media', 'ar' => 'وسائط مختلطة',     'cat' => 'contemporary'],
            'photography' => ['en' => 'Photography', 'ar' => 'تصوير فوتوغرافي',  'cat' => 'photography'],
            'sculpture'   => ['en' => 'Sculpture',   'ar' => 'نحت',              'cat' => ''],
            'other'       => ['en' => 'Other',       'ar' => 'أخرى',             'cat' => ''],
        ];
        foreach ($legacy as $k => $v) {
            if (!isset($types[$k])) $types[$k] = $v + ['legacy' => true];
        }
        return $types;
    }
}
return owLoadArtTypes();
