<?php
/* =====================================================================
 *  sitemap.php — the list of public pages for Google and other search
 *  engines, made fresh on every visit. Served as /sitemap.xml
 *  (.htaccess) and named in robots.txt.
 *  Includes: the main pages, the 3 article lists, published articles,
 *  every artwork visitors can see (artwork.php?id=N) and every approved
 *  artist's public profile. Never drafts, hidden or suspended accounts.
 *  Tables read: artworks, users, articles, photo_consents, article_images
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/article_lib.php';
require_once __DIR__ . '/seo_lib.php';

header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=3600');
header('X-Robots-Tag: noindex');

$urls = [];
$add = function (string $path, ?string $when = null, string $freq = 'weekly', string $prio = '0.6') use (&$urls) {
    $urls[] = ['loc' => owAbsUrl($path), 'when' => $when ? gmdate('Y-m-d', strtotime($when)) : null, 'freq' => $freq, 'prio' => $prio];
};
foreach ([['', 'daily', '1.0'], ['marketplace.php', 'daily', '0.9'], ['explore.php', 'monthly', '0.8'], ['community.php', 'daily', '0.6'],
          ['training.php', 'weekly', '0.6'], ['articles_list.php?type=event', 'weekly', '0.6'], ['articles_list.php?type=artist_spotlight', 'weekly', '0.6'],
          ['articles_list.php?type=art_education', 'monthly', '0.5'], ['support.php', 'monthly', '0.4'], ['register_artist.php', 'monthly', '0.5'],
          ['register_collector.php', 'monthly', '0.4'], ['privacy.php', 'yearly', '0.2'], ['terms.php', 'yearly', '0.2']] as [$p, $f, $pr]) {
    $add($p, null, $f, $pr);
}
$db = getDB();
try {
    $upd = owHasColumn('artworks', 'updated_at') ? 'a.updated_at' : 'a.created_at';
    $st = $db->query("SELECT a.id, $upd AS changed FROM artworks a JOIN users u ON u.id = a.artist_id
                      WHERE a.is_approved = 1 AND a.status IN ('available','auction') AND u.is_banned = 0" . owNotDraftSql('a') . " ORDER BY a.id DESC LIMIT 5000");
    foreach ($st as $r) $add('artwork.php?id=' . (int) $r['id'], $r['changed'], 'weekly', '0.8');
    $st = $db->query("SELECT u.id, MAX($upd) AS changed FROM users u JOIN artworks a ON a.artist_id = u.id
                      WHERE u.role = 'artist' AND u.is_approved = 1 AND u.is_banned = 0 AND a.is_approved = 1" . owNotDraftSql('a') . " GROUP BY u.id LIMIT 5000");
    foreach ($st as $r) $add('artist_dashboard.php?id=' . (int) $r['id'], $r['changed'], 'weekly', '0.7');
} catch (Throwable $e) { error_log('sitemap artworks: ' . $e->getMessage()); }
try {
    if (owArticlesReady()) {
        $st = $db->query('SELECT a.slug, a.updated_at FROM articles a WHERE ' . owArticlePublicSql('a') . ' ORDER BY a.id DESC LIMIT 5000');
        foreach ($st as $r) $add('article.php?slug=' . rawurlencode((string) $r['slug']), $r['updated_at'], 'monthly', '0.6');
    }
} catch (Throwable $e) { error_log('sitemap articles: ' . $e->getMessage()); }

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8') . '</loc>'
       . ($u['when'] ? '<lastmod>' . $u['when'] . '</lastmod>' : '')
       . '<changefreq>' . $u['freq'] . '</changefreq><priority>' . $u['prio'] . "</priority></url>\n";
}
echo "</urlset>\n";
