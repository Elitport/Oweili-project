<?php
require_once __DIR__ . '/studio_lib.php';   // the studio photo behind the page
/* =====================================================================
 *  art_place.php — the Art Map's pages for Google (owner, 13 Nov 2026).
 *  /art-map/<region>/<name>  (art_place.php?region=…&slug=…): one place:
 *    name, city, type, text, photo (or the region band), address, phone,
 *    website, Instagram and a Google Maps link, "Is this your place?", and
 *    Google place data (schema.org Museum, ArtGallery or School, with a
 *    breadcrumb).
 *  /art-map/<region>  (art_place.php?region=…): "Museums and art galleries
 *    in Riyadh": every public place of one region, linked to its page.
 *  Addresses come from owMapDirectory() (artmap_lib.php); .htaccess turns
 *  the clean addresses into this page. Unknown addresses get the 404 page.
 *  Tables: art_places, institutes (read)
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/page_cache.php'; owPublicPage();
require_once __DIR__ . '/artmap_lib.php';
require_once __DIR__ . '/lang_lib.php';
require_once __DIR__ . '/seo_lib.php';

$rg = (string) ($_GET['region'] ?? '');
$slug = (string) ($_GET['slug'] ?? '');
if (!isset(OW_MAP_REGIONS[$rg]) || ($slug !== '' && !preg_match('/^[a-z0-9-]{1,90}$/', $slug))) { http_response_code(404); require __DIR__ . '/404.php'; exit; }
$all = owMapDirectory();
$place = null;
if ($slug !== '') { foreach ($all as $r) if ($r['region'] === $rg && $r['slug'] === $slug) { $place = $r; break; } if (!$place) { http_response_code(404); require __DIR__ . '/404.php'; exit; } }
$inRegion = array_values(array_filter($all, fn($r) => $r['region'] === $rg));
$R = OW_MAP_REGIONS[$rg];
[$rtEn, $rtAr] = owMapRegionTitle($rg);
$regionUrl = 'art-map/' . $rg;
$PIN = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>';
$cut = function (string $s, int $n): string { $s = owMapPlain($s); return mb_strlen($s) > $n ? rtrim(mb_substr($s, 0, $n - 1), " ,.;:،") . '…' : $s; };

if ($place) {
    $K = OW_MAP_KINDS[$place['kind']] ?? OW_MAP_KINDS['museum'];
    $nEn = (string) $place['name_en']; $nAr = (string) $place['name_ar'];
    $cEn = (string) $place['city_en']; $cAr = (string) $place['city_ar'];
    $ph = owPlacePhotoBase($place);
    $web = owMapUrl((string) $place['website']); $insta = owInstaUrl((string) $place['instagram']);
    $maps = owMapsLink($nEn, $cEn, (string) $place['maps_url']);
    $dEn = (string) ($place['desc_en'] ?: $place['desc_ar']); $dAr = (string) ($place['desc_ar'] ?: $place['desc_en']);
    $seo = ['en' => $nEn . ', ' . $cEn, 'ar' => $nAr . '، ' . $cAr,
            'desc_en' => $cut($dEn !== '' ? $dEn : $K[2] . ' in ' . $cEn . ', Saudi Arabia.', 150) . ' Address, website and map on Oweili.',
            'desc_ar' => $cut($dAr !== '' ? $dAr : $K[3] . ' في ' . $cAr . '، السعودية.', 150) . ' العنوان والموقع والخريطة على أويلي.',
            'path' => $place['url'], 'image' => $ph !== '' ? owAbsUrl($ph . '.jpg') : ''];
    /* Google place data */
    $ld = ['@context' => 'https://schema.org', '@type' => ['museum' => 'Museum', 'gallery' => 'ArtGallery', 'institute' => 'School'][$place['kind']] ?? 'Place',
           'name' => $nEn, 'alternateName' => $nAr, 'url' => owAbsUrl($place['url'])];
    if ($dEn !== '') $ld['description'] = owMapPlain($dEn);
    $ld['address'] = array_filter(['@type' => 'PostalAddress', 'streetAddress' => trim((string) $place['address']) ?: null, 'addressLocality' => $cEn, 'addressRegion' => $R[0], 'addressCountry' => 'SA']);
    if (($tel = owTelHref((string) $place['phone'])) !== '') $ld['telephone'] = substr($tel, 4);
    if ($ph !== '') $ld['image'] = owAbsUrl($ph . '.jpg');
    $ld['hasMap'] = $maps;
    if (is_numeric($place['lat'] ?? null) && is_numeric($place['lng'] ?? null)) $ld['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) $place['lat'], 'longitude' => (float) $place['lng']];
    $same = array_values(array_filter([$web, $insta])); if ($same) $ld['sameAs'] = $same;
    $crumbs = [['Art Map', 'art_map.php'], [$R[0], $regionUrl], [$nEn, $place['url']]];
} else {
    $seo = ['en' => $rtEn, 'ar' => $rtAr,
            'desc_en' => 'Museums, art galleries and art institutes in ' . $R[0] . ', Saudi Arabia: ' . count($inRegion) . ' places with addresses, websites and maps, on Oweili\'s Art Map.',
            'desc_ar' => 'المتاحف والمعارض والمعاهد الفنية في ' . $R[1] . ': ' . count($inRegion) . ' وجهة بعناوينها ومواقعها وخرائطها، على وجهات الفن في أويلي.',
            'path' => $regionUrl];
    $ld = ['@context' => 'https://schema.org', '@type' => 'CollectionPage', 'name' => $rtEn, 'alternateName' => $rtAr, 'url' => owAbsUrl($regionUrl),
           'mainEntity' => ['@type' => 'ItemList', 'numberOfItems' => count($inRegion), 'itemListElement' => array_map(fn($r, $n) => ['@type' => 'ListItem', 'position' => $n + 1, 'url' => owAbsUrl($r['url']), 'name' => $r['name_en']], $inRegion, array_keys($inRegion))]];
    $crumbs = [['Art Map', 'art_map.php'], [$R[0], $regionUrl]];
}
$bc = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array_map(fn($c, $n) => ['@type' => 'ListItem', 'position' => $n + 1, 'name' => $c[0], 'item' => owAbsUrl($c[1])], $crumbs, array_keys($crumbs))];
$json = fn(array $a) => str_replace('</', '<\/', json_encode($a, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
$kindOrder = ['museum' => 0, 'gallery' => 1, 'institute' => 2];
?>
<!DOCTYPE html>
<html <?php owHtmlAttrs(); ?> data-title-en="<?= e($seo['en']) ?> · Oweili" data-title-ar="<?= e($seo['ar']) ?> · أويلي">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<base href="<?= e(owBase()) ?>"><!-- links work from the clean address /art-map/… -->
<?php require_once __DIR__ . '/seo_lib.php'; owSeo($seo + ['type' => 'website']); ?>
<script type="application/ld+json"><?= $json($ld) ?></script>
<script type="application/ld+json"><?= $json($bc) ?></script>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/nav.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/discover.css') ?>">
<style>
/* Art Map place and region pages — layout only; colours, buttons and cards come from oweili.css */
.ap{max-width:760px;margin:0 auto;display:flex;flex-direction:column;gap:var(--space-sm);}
.ap-crumbs{display:flex;flex-wrap:wrap;gap:6px;justify-content:center;font-size:var(--fs-caption);margin:0 auto var(--space-md);width:fit-content;max-width:100%;padding:6px 16px;border-radius:var(--radius-card);background:var(--studio-glass);-webkit-backdrop-filter:blur(10px);backdrop-filter:blur(10px);}
.ap-crumbs a{color:var(--color-dark);}
.ap-crumbs span[aria-hidden]{color:var(--color-body);}
.ap h1{font-size:var(--fs-section);line-height:1.25;margin:0;}
.ap-city{display:flex;align-items:center;gap:4px;font-size:var(--fs-caption);color:var(--color-body);}
.ap-city svg,.m-acts svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2;}
.ap-photo{margin:calc(-1 * var(--space-md)) calc(-1 * var(--space-md)) 0;border-radius:var(--radius-card) var(--radius-card) 0 0;overflow:hidden;background:var(--color-surface);}
.ap-photo img{display:block;width:100%;aspect-ratio:16/9;height:auto;object-fit:cover;}
.ap-photo figcaption{font-size:12px;line-height:1.4;color:var(--color-body);padding:6px var(--space-md) 0;}
.ap-photo figcaption a{color:inherit;}
.m-kind{align-self:flex-start;padding:2px 10px;border-radius:var(--radius-pill);font-size:var(--fs-min);font-weight:700;color:var(--color-dark);background:var(--color-tint-pink);border:1px solid rgba(var(--rgb-primary),0.35);}
.ap-desc{margin:0;}
.ap-info{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:6px;font-size:var(--fs-body);color:var(--color-body);}
.ap-info b{color:var(--color-dark);}
.ap-info a{color:var(--color-dark);}
.m-acts{display:flex;flex-wrap:wrap;gap:var(--space-xs);}
.m-acts .btn{gap:6px;flex:1 1 auto;justify-content:center;}
.m-claim{align-self:flex-start;font-size:var(--fs-caption);color:var(--color-body);text-decoration:underline;text-underline-offset:3px;padding:4px 0;}
.m-claim:hover{color:var(--color-dark);}
.ap-more{display:flex;flex-wrap:wrap;gap:var(--space-xs);}
/* the band of a place without a photo (same tints as the Art Map) */
.m-band{--rh:350;--rs:45%;display:flex;align-items:center;gap:10px;min-height:56px;margin:calc(-1 * var(--space-md)) calc(-1 * var(--space-md)) 0;padding:10px var(--space-md);
  border-radius:var(--radius-card) var(--radius-card) 0 0;background:linear-gradient(120deg,hsl(var(--rh) var(--rs) 91%),hsl(calc(var(--rh) + 18) var(--rs) 95%));
  border-bottom:1px solid hsl(var(--rh) var(--rs) 84%);color:var(--color-dark);font-size:var(--fs-caption);font-weight:700;}
.m-band svg{width:26px;height:26px;flex-shrink:0;stroke:currentColor;fill:none;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round;opacity:.8;}
html.dark-mode .m-band{background:linear-gradient(120deg,hsl(var(--rh) 28% 22%),hsl(calc(var(--rh) + 18) 24% 18%));border-bottom-color:hsl(var(--rh) 24% 30%);}
.m-band.r-riyadh{--rh:356;}.m-band.r-makkah{--rh:218;--rs:32%;}.m-band.r-eastern{--rh:205;--rs:28%;}.m-band.r-madinah{--rh:32;--rs:42%;}
.m-band.r-qassim{--rh:265;--rs:22%;}.m-band.r-hail{--rh:150;--rs:20%;}.m-band.r-tabuk{--rh:330;--rs:32%;}.m-band.r-jouf{--rh:45;--rs:24%;}
.m-band.r-northern{--rh:195;--rs:34%;}.m-band.r-asir{--rh:15;--rs:52%;}.m-band.r-jazan{--rh:175;--rs:28%;}.m-band.r-najran{--rh:5;--rs:30%;}
.m-band.r-baha{--rh:95;--rs:18%;}
/* region page list */
.rg-group{margin:0 0 var(--space-lg);}
.rg-group h2{font-size:var(--fs-subtitle);margin:0 auto var(--space-sm);width:fit-content;padding:4px 16px;border-radius:var(--radius-card);background:var(--studio-glass);-webkit-backdrop-filter:blur(10px);backdrop-filter:blur(10px);}
.rg-list{display:flex;flex-wrap:wrap;justify-content:center;gap:var(--space-sm);align-items:stretch;}
.rg-list + .rg-list{margin-top:var(--space-sm);}
.rg-list > .rg-item{flex:0 1 calc((100% - 2 * var(--space-sm)) / 3);min-width:0;}   /* photo places and the others in separate even rows (owner, 13 Nov 2026) */
.card.rg-item{display:flex;flex-direction:column;gap:6px;padding:0 0 var(--space-sm);overflow:hidden;text-decoration:none;color:inherit;}
.rg-item .rg-img img{display:block;width:100%;aspect-ratio:16/10;object-fit:cover;}
.rg-item .m-band{margin:0;border-radius:0;}
.rg-item b{padding:0 var(--space-sm);color:var(--color-dark);line-height:1.35;}
.rg-item span.c{padding:0 var(--space-sm);font-size:var(--fs-caption);color:var(--color-body);}
.rg-item:hover b{text-decoration:underline;}
html.dark-mode .ow-backdrop ~ * .card{background:var(--color-card);}
@media (max-width:900px){.rg-list > .rg-item{flex-basis:calc((100% - var(--space-sm)) / 2);}}
@media (max-width:560px){.rg-list > .rg-item{flex-basis:100%;}}
</style>
</head>
<body class="ow">
<?= ($place && ($ph ?? '') !== '') ? '' : owStudioBackdrop('easels-corridor') /* a place with its own photo gets a calm plain background, not a second photo (owner, 13 Nov 2026) */ ?>
<div class="bg-wrap" aria-hidden="true"></div>
<?php include __DIR__ . '/nav.php'; ?>
<main>
  <div class="d-wrap">
    <nav class="ap-crumbs" aria-label="<?= owIsAr() ? 'مسار الصفحة' : 'Breadcrumb' ?>">
      <a href="art_map.php"><?= owBi('Art Map', 'وجهات الفن') ?></a><span aria-hidden="true">›</span>
      <?php if ($place): ?><a href="<?= e($regionUrl) ?>"><?= owBi($R[0], $R[1]) ?></a><span aria-hidden="true">›</span><span><?= owBi($nEn, $nAr) ?></span>
      <?php else: ?><span><?= owBi($R[0], $R[1]) ?></span><?php endif; ?>
    </nav>
<?php if ($place): ?>
    <article class="card ap">
      <?php if ($ph !== ''): ?>
        <figure class="ap-photo"><?= owPlacePicture($ph, $nEn . ' · ' . $nAr, 800, 450, false) ?>
          <?php if ((string) $place['photo_credit'] !== ''): ?><figcaption><?= owBi('Photo: ', 'الصورة: ') ?><?= owMapUrl((string) $place['photo_page']) !== '' ? '<a href="' . e((string) $place['photo_page']) . '" target="_blank" rel="noopener nofollow">' . e((string) $place['photo_credit']) . '</a>' : e((string) $place['photo_credit']) ?></figcaption><?php endif; ?></figure>
      <?php else: ?>
        <div class="m-band r-<?= e($rg) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><?= owMapKindIcon((string) $place['kind']) ?></svg><span><?= owBi($cEn, $cAr) ?></span></div>
      <?php endif; ?>
      <h1><?= owBi($nEn, $nAr) ?></h1>
      <div class="ap-city"><?= $PIN ?><?= mb_strtolower($cEn) === mb_strtolower($R[0]) ? owBi($cEn, $cAr) : owBi($cEn . ', ' . $R[0], $cAr . '، ' . $R[1]) ?></div>
      <span class="m-kind"><?= owBi($K[2], $K[3]) ?></span>
      <?php if ($dEn !== ''): ?><p class="ap-desc"><?= owBi(owMapPlain($dEn), owMapPlain($dAr)) ?></p><?php endif; ?>
      <?php $info = '';
        if (trim((string) $place['address']) !== '') $info .= '<li><b>' . owBi('Address', 'العنوان') . '</b> <bdi>' . owBi((string) $place['address'], owPlaceAddressAr($place)) . '</bdi></li>';
        if (($tel = owTelHref((string) $place['phone'])) !== '') $info .= '<li><b>' . owBi('Phone', 'الهاتف') . '</b> <a href="' . e($tel) . '" dir="ltr">' . e((string) $place['phone']) . '</a></li>';
        if ($info !== '') echo '<ul class="ap-info">' . $info . '</ul>'; ?>
      <?= owMapActs($web, $maps, $nEn, $insta) ?>
      <?= owMapClaimLink(($place['src'] === 'p' ? 'p=' : 'i=') . (int) $place['id']) ?>
    </article>
    <div class="ap ap-more" style="margin-top:var(--space-md);justify-content:center">
      <a class="btn btn-secondary" href="<?= e($regionUrl) ?>"><?= owBi('More in ' . $R[0], 'المزيد في ' . $R[1]) ?></a>
      <a class="btn btn-primary" href="art_map.php"><?= owBi('Back to the Art Map', 'العودة إلى وجهات الفن') ?></a>
    </div>
<?php else: ?>
    <header class="d-head">
      <h1><?= owBi($rtEn, $rtAr) ?></h1>
      <p><?= owBi(count($inRegion) . ' museums, art galleries and art institutes in ' . $R[0] . ', on one page.', 'المتاحف والمعارض والمعاهد الفنية في ' . $R[1] . ' في صفحة واحدة: ' . count($inRegion) . ' وجهة.') ?></p>
    </header>
    <?php if (!$inRegion): ?>
      <div class="card empty-state d-empty"><?= owBi('No art places in this region on our map yet.', 'لا وجهات فنية في هذه المنطقة على خريطتنا بعد.') ?><div><a class="btn btn-primary" href="art_map.php"><?= owBi('Back to the Art Map', 'العودة إلى وجهات الفن') ?></a></div></div>
    <?php endif; ?>
    <?php foreach (['museum', 'gallery', 'institute'] as $k):
      $list = array_values(array_filter($inRegion, fn($r) => $r['kind'] === $k));
      usort($list, fn($a, $b) => [owPlacePhotoBase($b) !== '', $a['name_en']] <=> [owPlacePhotoBase($a) !== '', $b['name_en']]);   // photo places first
      if (!$list) continue; $KK = OW_MAP_KINDS[$k]; ?>
    <section class="rg-group">
      <h2><?= owBi($KK[0], $KK[1]) ?></h2>
      <?php foreach ([array_values(array_filter($list, fn($r) => owPlacePhotoBase($r) !== '')), array_values(array_filter($list, fn($r) => owPlacePhotoBase($r) === ''))] as $part): if (!$part) continue; ?>
      <div class="rg-list">
        <?php foreach ($part as $r): $rp = owPlacePhotoBase($r); ?>
        <a class="card rg-item" href="<?= e($r['url']) ?>">
          <?php if ($rp !== ''): ?><span class="rg-img"><?= owPlacePicture($rp, $r['name_en'] . ' · ' . $r['name_ar']) ?></span>
          <?php else: ?><span class="m-band r-<?= e($rg) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><?= owMapKindIcon($k) ?></svg></span><?php endif; ?>
          <b><?= owBi((string) $r['name_en'], (string) $r['name_ar']) ?></b>
          <span class="c"><?= owBi((string) $r['city_en'], (string) $r['city_ar']) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
    </section>
    <?php endforeach; ?>
    <div style="text-align:center"><a class="btn btn-primary" href="art_map.php?region=<?= e($rg) ?>"><?= owBi('See them on the Art Map', 'شاهدها في وجهات الفن') ?></a></div>
<?php endif; ?>
  </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
