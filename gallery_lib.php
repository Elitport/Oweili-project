<?php
/* =====================================================================
 *  gallery_lib.php — shared pieces for a rich, varied Gallery (2 Nov 2026)
 *    owSetting / owSetSetting   small site settings (site_settings table)
 *    Gallery filters            medium, price range, size, city, color
 *    Curated collections        made by the admin in admin_gallery.php
 *    Artist of the week         picked by the admin, shown on the homepage
 *                               and on top of the Gallery
 *    Verified artist badge      given by the admin after checking an artist
 *  Every part checks that its table/column exists, so pages keep working
 *  before night_update.sql is run.
 *  Tables: site_settings, collections, collection_items, users, artworks,
 *          artwork_colors
 * ===================================================================== */
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/artwork_lib.php';

/* ---------------- Settings ---------------- */
function owSetting(string $key, ?string $default = null): ?string {
    $cache = &$GLOBALS['OW_SETTINGS_CACHE'];
    if (!is_array($cache)) $cache = [];
    if (array_key_exists($key, $cache)) return $cache[$key] ?? $default;
    $val = null;
    try {
        if (owHasTable('site_settings')) {
            $st = getDB()->prepare('SELECT setting_value FROM site_settings WHERE setting_key = :k');
            $st->execute([':k' => $key]);
            $v = $st->fetchColumn();
            $val = $v === false ? null : (string) $v;
        }
    } catch (Throwable $e) { $val = null; }
    $cache[$key] = $val;
    return $val ?? $default;
}
function owSetSetting(string $key, ?string $value): bool {
    unset($GLOBALS['OW_SETTINGS_CACHE'][$key]);
    try {
        if (!owHasTable('site_settings')) return false;
        if ($value === null) { getDB()->prepare('DELETE FROM site_settings WHERE setting_key = :k')->execute([':k' => $key]); return true; }
        getDB()->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)')
               ->execute([':k' => $key, ':v' => $value]);
        return true;
    } catch (Throwable $e) { error_log('owSetSetting: ' . $e->getMessage()); return false; }
}

/* ---------------- Verified artist ---------------- */
function owVerifiedReady(): bool { return owHasColumn('users', 'is_verified_artist'); }
/* SQL for "this artist is verified" (0 before the database update) */
function owVerifiedSql(string $u = 'u'): string { return owVerifiedReady() ? "COALESCE($u.is_verified_artist, 0)" : '0'; }
/* The small badge next to an artist name */
function owVerifiedBadge(bool $small = false): string {
    return '<span class="vf-badge' . ($small ? ' sm' : '') . '" title="Verified artist · فنان موثّق"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l2.4 2.1 3.2-.4.9 3.1 2.8 1.6-1.2 3 1.2 3-2.8 1.6-.9 3.1-3.2-.4L12 22l-2.4-2.1-3.2.4-.9-3.1L2.7 15.6l1.2-3-1.2-3 2.8-1.6.9-3.1 3.2.4z"/><path class="vf-tick" d="M8.5 12.2l2.4 2.4 4.6-4.8"/></svg>'
         . ($small ? '<span class="vf-sr">' . owBi('Verified artist', 'فنان موثّق') . '</span>' : owBi('Verified artist', 'فنان موثّق')) . '</span>';
}
function owSetVerifiedArtist(int $userId, bool $on): bool {
    if (!owVerifiedReady()) return false;
    $st = getDB()->prepare("UPDATE users SET is_verified_artist = :v, verified_artist_at = " . ($on ? 'NOW()' : 'NULL') . " WHERE id = :id AND role = 'artist'");
    $st->execute([':v' => $on ? 1 : 0, ':id' => $userId]);
    if ($st->rowCount() > 0) { owLog($on ? 'artist_verify' : 'artist_unverify', 'user', $userId, ''); return true; }
    return false;
}

/* ---------------- Filters ---------------- */
/* Medium: matched against the medium the artist typed (English or Arabic words) */
function owMediumFilters(): array {
    return [
        'oil'         => ['en' => 'Oil',           'ar' => 'زيت',           'words' => ['oil', 'زيت']],
        'acrylic'     => ['en' => 'Acrylic',       'ar' => 'أكريليك',       'words' => ['acrylic', 'أكريليك', 'اكريليك']],
        'watercolour' => ['en' => 'Watercolour',   'ar' => 'ألوان مائية',   'words' => ['watercolo', 'مائي']],
        'ink'         => ['en' => 'Ink',           'ar' => 'حبر',           'words' => ['ink', 'حبر']],
        'charcoal'    => ['en' => 'Charcoal & pencil', 'ar' => 'فحم ورصاص', 'words' => ['charcoal', 'pencil', 'graphite', 'فحم', 'رصاص']],
        'mixed'       => ['en' => 'Mixed media',   'ar' => 'وسائط مختلطة',  'words' => ['mixed', 'collage', 'مختلط', 'كولاج']],
        'print'       => ['en' => 'Print',         'ar' => 'طباعة',         'words' => ['print', 'طباعة', 'مطبوع']],
    ];
}
/* Price range presets (SAR) */
function owPriceFilters(): array {
    return [
        'u500'   => ['en' => 'Under 500 SAR',        'ar' => 'أقل من 500 ر.س',          'min' => null, 'max' => 499.99],
        '500'    => ['en' => '500 to 1,000 SAR',     'ar' => 'من 500 إلى 1,000 ر.س',     'min' => 500,  'max' => 1000],
        '1000'   => ['en' => '1,000 to 5,000 SAR',   'ar' => 'من 1,000 إلى 5,000 ر.س',   'min' => 1000.01, 'max' => 5000],
        'o5000'  => ['en' => 'Over 5,000 SAR',       'ar' => 'أكثر من 5,000 ر.س',        'min' => 5000.01, 'max' => null],
    ];
}
/* Size by the longest side (cm) */
function owSizeFilters(): array {
    return [
        'small'  => ['en' => 'Small (up to 40 cm)',   'ar' => 'صغير (حتى 40 سم)',    'min' => null, 'max' => 40],
        'medium' => ['en' => 'Medium (40 to 100 cm)', 'ar' => 'متوسط (40 إلى 100 سم)', 'min' => 40.01, 'max' => 100],
        'large'  => ['en' => 'Large (over 100 cm)',   'ar' => 'كبير (أكثر من 100 سم)', 'min' => 100.01, 'max' => null],
    ];
}
/* Color families, built from the paint list the artists pick in the upload */
function owColorFamilies(): array {
    return [
        'red'    => ['en' => 'Red',    'ar' => 'أحمر',  'hex' => '#C8102E', 'paints' => ['cadmium_red', 'crimson']],
        'yellow' => ['en' => 'Yellow', 'ar' => 'أصفر',  'hex' => '#E8B400', 'paints' => ['yellow_ochre', 'cadmium_yellow', 'naples_yellow']],
        'blue'   => ['en' => 'Blue',   'ar' => 'أزرق',  'hex' => '#1F4FB0', 'paints' => ['cobalt_blue', 'ultramarine', 'cerulean']],
        'green'  => ['en' => 'Green',  'ar' => 'أخضر',  'hex' => '#3F7F5A', 'paints' => ['viridian', 'sap_green']],
        'brown'  => ['en' => 'Earth & brown', 'ar' => 'ترابي وبني', 'hex' => '#7A4A2A', 'paints' => ['burnt_sienna', 'raw_umber', 'burnt_umber']],
        'black'  => ['en' => 'Black',  'ar' => 'أسود',  'hex' => '#1B1B1B', 'paints' => ['ivory_black']],
        'white'  => ['en' => 'White',  'ar' => 'أبيض',  'hex' => '#F4F4F0', 'paints' => ['titanium_white']],
        'grey'   => ['en' => 'Grey',   'ar' => 'رمادي', 'hex' => '#6B7A88', 'paints' => ['paynes_grey']],
    ];
}
/* The English paint names of one color family (artwork_colors.color_name_en) */
function owColorFamilyNames(string $family): array {
    $fam = owColorFamilies()[$family] ?? null;
    if (!$fam) return [];
    $all = owColors();
    return array_values(array_filter(array_map(fn($k) => $all[$k]['en'] ?? '', $fam['paints'])));
}
/* Cities of artists who have artworks in the Gallery (for the City filter) */
function owGalleryCities(): array {
    try {
        $rows = getDB()->query("SELECT TRIM(u.city) AS city, COUNT(DISTINCT a.id) AS n FROM artworks a JOIN users u ON u.id = a.artist_id
                                WHERE a.is_approved = 1 AND a.status IN ('available','auction') AND u.is_banned = 0" . owNotDraftSql('a') . "
                                  AND TRIM(COALESCE(u.city, '')) <> '' GROUP BY TRIM(u.city) ORDER BY n DESC, city LIMIT 40")->fetchAll();
        return array_values(array_filter(array_map(fn($r) => (string) $r['city'], $rows), fn($c) => mb_strlen($c) <= 100));
    } catch (Throwable $e) { return []; }
}

/* ---------------- Collections ---------------- */
function owCollectionsReady(): bool { return owHasTable('collections') && owHasTable('collection_items'); }
function owCollections(bool $activeOnly = true): array {
    if (!owCollectionsReady()) return [];
    try {
        return getDB()->query('SELECT * FROM collections' . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY sort_order, id')->fetchAll();
    } catch (Throwable $e) { return []; }
}
function owCollectionBySlug(string $slug): ?array {
    if (!owCollectionsReady() || !preg_match('/^[a-z0-9-]{1,120}$/', $slug)) return null;
    $st = getDB()->prepare('SELECT * FROM collections WHERE slug = :s AND is_active = 1');
    $st->execute([':s' => $slug]);
    return $st->fetch() ?: null;
}
/* SQL condition (with its parameters) for "this artwork is in the collection":
   picked by hand, OR (when the collection has a rule) matching the rule */
function owCollectionCondition(array $c, string $a = 'a'): array {
    $p = [':colid' => (int) $c['id']];
    $hand = "$a.id IN (SELECT ci.artwork_id FROM collection_items ci WHERE ci.collection_id = :colid)";
    $rule = [];
    if ($c['rule_max_price'] !== null && $c['rule_max_price'] !== '') { $rule[] = "$a.price <= :colmax"; $p[':colmax'] = (float) $c['rule_max_price']; }
    if ($c['rule_min_price'] !== null && $c['rule_min_price'] !== '') { $rule[] = "$a.price >= :colmin"; $p[':colmin'] = (float) $c['rule_min_price']; }
    if ((string) $c['rule_type'] !== '') { $rule[] = "$a.type = :coltype"; $p[':coltype'] = (string) $c['rule_type']; }
    $sql = $rule ? "($hand OR (" . implode(' AND ', $rule) . '))' : $hand;
    return [$sql, $p];
}
/* Up to $n cover pictures per collection (for the collection cards) */
function owCollectionCovers(array $cols, int $n = 3): array {
    $out = [];
    foreach ($cols as $c) {
        try {
            [$cond, $p] = owCollectionCondition($c);
            $st = getDB()->prepare("SELECT a.id, a.image_url, a.title_en, a.title_ar FROM artworks a JOIN users u ON u.id = a.artist_id
                                    WHERE a.is_approved = 1 AND a.status IN ('available','auction') AND u.is_banned = 0" . owNotDraftSql('a') . " AND $cond
                                    ORDER BY a.created_at DESC, a.id DESC LIMIT " . (int) $n);
            $st->execute($p);
            $rows = $st->fetchAll();
            $cnt = getDB()->prepare("SELECT COUNT(*) FROM artworks a JOIN users u ON u.id = a.artist_id
                                     WHERE a.is_approved = 1 AND a.status IN ('available','auction') AND u.is_banned = 0" . owNotDraftSql('a') . " AND $cond");
            $cnt->execute($p);
            $out[(int) $c['id']] = ['pics' => $rows, 'count' => (int) $cnt->fetchColumn()];
        } catch (Throwable $e) { $out[(int) $c['id']] = ['pics' => [], 'count' => 0]; }
    }
    return $out;
}

/* ---------------- Artist of the week ---------------- */
/* ['id','artist_en','artist_ar','city','picture','note_en','note_ar','art' => [one artwork]] or null */
function owArtistOfWeek(): ?array {
    $raw = owSetting('artist_of_week');
    if (!$raw) return null;
    $d = json_decode($raw, true);
    if (!is_array($d) || empty($d['id'])) return null;
    try {
        $st = getDB()->prepare("SELECT u.id, u.city, u.profile_picture, " . owArtistNameSql('u') . ", " . owVerifiedSql('u') . " AS verified
                                FROM users u WHERE u.id = :id AND u.role = 'artist' AND u.is_approved = 1 AND COALESCE(u.is_banned, 0) = 0");
        $st->execute([':id' => (int) $d['id']]);
        $u = $st->fetch();
        if (!$u) return null;
        $aw = getDB()->prepare("SELECT a.id, a.title_en, a.title_ar, a.image_url FROM artworks a
                                WHERE a.artist_id = :id AND a.is_approved = 1 AND a.status IN ('available','auction')" . owNotDraftSql('a') . "
                                ORDER BY a.created_at DESC, a.id DESC LIMIT 1");
        $aw->execute([':id' => (int) $u['id']]);
        $u['art'] = $aw->fetch() ?: null;
        $u['note_en'] = (string) ($d['note_en'] ?? '');
        $u['note_ar'] = (string) ($d['note_ar'] ?? '');
        return $u;
    } catch (Throwable $e) { return null; }
}

/* A clean web address from a title (collections) */
function owCollectionSlug(string $title, int $exceptId = 0): string {
    $base = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-');
    if ($base === '') $base = 'collection';
    $base = substr($base, 0, 100);
    $slug = $base; $i = 2;
    while (true) {
        $st = getDB()->prepare('SELECT COUNT(*) FROM collections WHERE slug = :s AND id <> :id');
        $st->execute([':s' => $slug, ':id' => $exceptId]);
        if ((int) $st->fetchColumn() === 0) return $slug;
        $slug = $base . '-' . $i++;
    }
}
