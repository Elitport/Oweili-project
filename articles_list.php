<?php
require_once __DIR__ . '/studio_lib.php';   // the studio photo behind the page
/* =====================================================================
 *  articles_list.php — all published articles of one type, for everyone
 *  ?type=event | artist_spotlight | art_education
 *  Tables: articles, article_images, photo_consents (see article_lib.php)
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/page_cache.php'; owPublicPage();   // public page: Back opens at once, early load on touch (page_cache.php)
require_once __DIR__ . '/article_lib.php';

$TYPES = owArticleTypes();
$type  = (string) ($_GET['type'] ?? 'event');
if (!isset($TYPES[$type])) $type = 'event';
/* Artist Spotlights hidden for now, to bring back later (6 Nov 2026): 404 for visitors, a preview for the admin */
require_once __DIR__ . '/gallery_lib.php';
$owHidPrev = $type === 'artist_spotlight' ? owHiddenPageGate('spotlights') : false;
if (!owSpotlightsOn() && !$owHidPrev) unset($TYPES['artist_spotlight']);

$PAGE = [
    'event' => [
        'sub' => ['Exhibitions and art gatherings from around the world. Chosen with care by the Oweili team.',
                  'معارض وملتقيات فنية من حول العالم. يختارها فريق أويلي بعناية.'],
        'empty' => ['No events yet. We are finding the next ones for you right now.',
                    'لا توجد ملتقيات بعد. نبحث لك الآن عن القادمة.'],
    ],
    'artist_spotlight' => [
        'sub' => ['Meet the artists behind the work. Their stories, their hands, their journeys.',
                  'تعرّف على الفنانين خلف الأعمال. حكاياتهم، وأيديهم، ورحلاتهم.'],
        'empty' => ['The first artist stories are being written. They are worth the wait.',
                    'نكتب الآن أولى حكايات الفنانين. وتستحق الانتظار.'],
    ],
    'art_education' => [
        'sub' => ['Guides for buying and caring for art.',
                  'أدلة لشراء الفن والعناية به.'],   // owner, 13 Nov 2026
        'empty' => ['Our first lessons are almost ready. Come back soon and learn with us.',
                    'دروسنا الأولى أوشكت أن تجهز. عد قريباً وتعلّم معنا.'],
    ],
];

$items = [];
if (owArticlesReady()) {
    try {
        $order = $type === 'event' ? 'a.event_start_date DESC, a.id DESC' : 'a.published_at DESC, a.id DESC';
        $st = getDB()->prepare('SELECT a.slug, a.title_en, a.title_ar, a.cover_image_path, a.published_at, a.event_start_date, a.event_end_date,
                                       a.event_location_en, a.event_location_ar
                                FROM articles a WHERE a.type = :t AND ' . owArticlePublicSql('a') . ' ORDER BY ' . $order . ' LIMIT 200');
        $st->execute([':t' => $type]);
        $items = $st->fetchAll();
    } catch (Throwable $e) {
        error_log('articles_list: ' . $e->getMessage());
        $items = [];
    }
}

[$tEn, $tAr] = $TYPES[$type];
$titleEn = $tEn . ' · Oweili';
$titleAr = $tAr . ' · أويلي';
?>
<!DOCTYPE html>
<html <?php require_once __DIR__ . '/lang_lib.php'; owHtmlAttrs(); ?> data-title-en="<?= e($titleEn) ?>" data-title-ar="<?= e($titleAr) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require_once __DIR__ . '/seo_lib.php'; owSeo(['en' => $tEn, 'ar' => $tAr, 'desc_en' => $PAGE[$type]['sub'][0], 'desc_ar' => $PAGE[$type]['sub'][1], 'path' => 'articles_list.php?type=' . $type]); ?>
<meta property="og:title" content="<?= e($tEn . ' · ' . $tAr . ' · Oweili') ?>">
<meta property="og:type" content="website">
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/nav.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/discover.css') ?>">
<script>document.documentElement.classList.add('js')</script><script defer src="<?= owAsset('assets/js/discover.js') ?>"></script>
<style>
/* no card alone on a row (owner, 13 Nov 2026): when 4 per row would leave one card alone (5, 9, 13 …),
   3 per row with the last row in the middle */
.a-grid.a-even{display:flex;flex-wrap:wrap;justify-content:center;}
.a-grid.a-even > .a-card{flex:0 1 calc((100% - 2 * var(--space-md)) / 3);min-width:0;}
@media (max-width:768px){.a-grid.a-even > .a-card{flex-basis:calc((100% - var(--space-sm)) / 2);}}
@media (max-width:560px){.a-grid.a-even > .a-card{flex-basis:100%;}}
</style>
</head>
<body class="ow">
<?= owStudioBackdrop($type === 'art_education' ? 'open-books' : ($type === 'event' ? 'events-hall' : 'museum-walk')) /* the owner's wide photos: Art Education the open books (6 Nov 2026), Art Events the hall with posters (7 Nov 2026), the hidden Spotlights still the museum hall */ ?>
<div class="bg-wrap" aria-hidden="true"></div>
<div class="veil" aria-hidden="true"></div>

<?php include __DIR__ . '/nav.php'; ?>
<?php if ($owHidPrev) echo owHiddenNotice(); ?>

<main>
  <div class="d-wrap">
    <header class="d-head reveal">
      <h1><?= owBi($tEn, $tAr) ?></h1>
      <p><?= owBi($PAGE[$type]['sub'][0], $PAGE[$type]['sub'][1]) ?></p>
    </header>

    <?php /* no type tabs and no Discover badge here (owner, 6 Nov 2026): Art Events and Art Education are in the top menu */ ?>

    <?php if (!$items): ?>
      <div class="card empty-state d-empty reveal">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        <?= owBi($PAGE[$type]['empty'][0], $PAGE[$type]['empty'][1]) ?>
        <div><a class="btn btn-primary" href="marketplace.php"><?= owBi('Explore Gallery', 'استكشف المعرض') ?></a></div>
      </div>
    <?php else: ?>
      <div class="a-grid<?= count($items) > 1 && count($items) % 4 === 1 ? ' a-even' : '' ?>">
        <?php foreach ($items as $it):
          [$dEn, $dAr] = $type === 'event' ? owDateRangeBi($it['event_start_date'], $it['event_end_date']) : owDateBi($it['published_at']);
          $cover = (string) $it['cover_image_path'];
        ?>
        <a class="card card-hover a-card reveal" href="article.php?slug=<?= e(rawurlencode($it['slug'])) ?>">
          <?php if ($cover !== '' && owSafeArticlePath($cover)): ?>
            <span class="card-media"><img loading="lazy" src="<?= e($cover) ?>" alt="<?= e($it['title_en'] . ' · ' . $it['title_ar']) ?>"></span>
          <?php else: ?>
            <span class="card-media none" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3c-4.97 0-9 3.58-9 8 0 4.4 3.5 8 8 8 1 0 1.5-.6 1.5-1.4 0-.8-.6-1.1-.6-1.9 0-.8.6-1.4 1.4-1.4H15c3.3 0 6-2.3 6-5.3C21 5.9 17 3 12 3z"/><circle cx="7.5" cy="10.5" r="1"/><circle cx="11" cy="7" r="1"/><circle cx="15.5" cy="8" r="1"/></svg></span>
          <?php endif; ?>
          <span class="a-body">
            <span class="a-title"><?= owBi($it['title_en'], $it['title_ar']) ?></span>
            <span class="a-date"><?= owBi($dEn, $dAr) ?><?php if ($type === 'event' && ($it['event_location_en'] !== '' || $it['event_location_ar'] !== '')): ?> · <?= owBi($it['event_location_en'], $it['event_location_ar']) ?><?php endif; ?></span>
            <span class="a-more"><?= owBi('Read more →', 'اقرأ المزيد ←') ?></span>
          </span>
        </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
