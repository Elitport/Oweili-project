<?php
/* =====================================================================
 *  seo_lib.php — the page title, description and link preview for every
 *  public page, in English and Arabic.
 *  owSeo([...]) prints, inside <head>:
 *    <title>, description, canonical address, and the preview tags that
 *    WhatsApp, X, Facebook and LinkedIn read (og:*, twitter:*), so a shared
 *    link shows the picture, title and description.
 *  Keys: en, ar (titles), desc_en, desc_ar, image (an /uploads/... photo or
 *  empty for the Oweili share picture), path (this page's address, e.g.
 *  'marketplace.php'), type (website / article / profile), noindex.
 *  Tables: none
 * ===================================================================== */
require_once __DIR__ . '/ow_paths.php';

const OW_SITE_URL = 'https://oweili.com';

/* A full https://oweili.com/... address for our own files */
function owAbsUrl(string $path): string {
    if (preg_match('#^https?://#i', $path)) return $path;
    return OW_SITE_URL . '/' . ltrim($path, '/');
}

function owSeo(array $o): void {
    $h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    $en = trim((string) ($o['en'] ?? 'Oweili'));
    $ar = trim((string) ($o['ar'] ?? 'أويلي'));
    $title = $en . ' · ' . $ar . ' | Oweili';
    $dEn = trim((string) ($o['desc_en'] ?? ''));
    $dAr = trim((string) ($o['desc_ar'] ?? ''));
    $desc = trim($dEn . ' ' . $dAr);
    $path = (string) ($o['path'] ?? '');
    $url  = owAbsUrl($path);
    $img  = (string) ($o['image'] ?? '');
    if ($img === '' || !preg_match('#^(/?uploads/|https://)#', $img)) $img = 'assets/img/share.jpg';
    $img  = owAbsUrl($img);
    $type = (string) ($o['type'] ?? 'website');
    echo '<title>' . $h($title) . "</title>\n";
    if ($desc !== '') echo '<meta name="description" content="' . $h(mb_substr($desc, 0, 300)) . "\">\n";
    if (!empty($o['noindex'])) echo "<meta name=\"robots\" content=\"noindex, follow\">\n";
    echo '<link rel="canonical" href="' . $h($url) . "\">\n";
    echo '<meta property="og:site_name" content="Oweili · أويلي">' . "\n";
    echo '<meta property="og:type" content="' . $h($type) . "\">\n";
    echo '<meta property="og:title" content="' . $h($en . ' · ' . $ar) . "\">\n";
    if ($desc !== '') echo '<meta property="og:description" content="' . $h(mb_substr($dEn !== '' ? $dEn : $dAr, 0, 200)) . "\">\n";
    echo '<meta property="og:url" content="' . $h($url) . "\">\n";
    echo '<meta property="og:image" content="' . $h($img) . "\">\n";
    echo '<meta property="og:image:alt" content="' . $h($en . ' · ' . $ar) . "\">\n";
    echo "<meta property=\"og:locale\" content=\"en_US\">\n<meta property=\"og:locale:alternate\" content=\"ar_SA\">\n";
    echo "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
    echo '<meta name="twitter:title" content="' . $h($en . ' · ' . $ar) . "\">\n";
    if ($desc !== '') echo '<meta name="twitter:description" content="' . $h(mb_substr($dEn !== '' ? $dEn : $dAr, 0, 200)) . "\">\n";
    echo '<meta name="twitter:image" content="' . $h($img) . "\">\n";
}
