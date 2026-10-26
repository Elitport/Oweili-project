<?php
/* =====================================================================
 *  articles_list.php — all published articles of one type, for everyone
 *  ?type=event | artist_spotlight | art_education
 *  Tables: articles, article_images, photo_consents (see article_lib.php)
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/article_lib.php';

$TYPES = owArticleTypes();
$type  = (string) ($_GET['type'] ?? 'event');
if (!isset($TYPES[$type])) $type = 'event';

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
        'sub' => ['Why is a style called what it is? Discover the names and stories behind art.',
                  'لماذا سُمّي كل أسلوب باسمه؟ اكتشف الأسماء والحكايات خلف الفن.'],
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
<html lang="en" dir="ltr" data-title-en="<?= e($titleEn) ?>" data-title-ar="<?= e($titleAr) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($titleEn) ?></title>
<meta name="description" content="<?= e($PAGE[$type]['sub'][0] . ' ' . $PAGE[$type]['sub'][1]) ?>">
<meta property="og:title" content="<?= e($tEn . ' · ' . $tAr . ' · Oweili') ?>">
<meta property="og:type" content="website">
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="/assets/css/oweili.css?v=<?= @filemtime(__DIR__ . '/assets/css/oweili.css') ?>">
<link rel="stylesheet" href="/assets/css/nav.css?v=<?= @filemtime(__DIR__ . '/assets/css/nav.css') ?>">
<link rel="stylesheet" href="/assets/css/discover.css?v=<?= @filemtime(__DIR__ . '/assets/css/discover.css') ?>">
<script src="/assets/js/discover.js?v=<?= @filemtime(__DIR__ . '/assets/js/discover.js') ?>"></script>
</head>
<body class="ow">
<div class="bg-wrap" aria-hidden="true"><video class="bg-video" id="vid" autoplay loop muted playsinline src="bg.mp4"></video></div>
<div class="veil" aria-hidden="true"></div>

<?php include __DIR__ . '/nav.php'; ?>

<main>
  <div class="d-wrap">
    <header class="d-head reveal">
      <span class="d-badge"><i aria-hidden="true"></i><?= owBi('Discover', 'اكتشف') ?></span>
      <h1><?= owBi($tEn, $tAr) ?></h1>
      <p><?= owBi($PAGE[$type]['sub'][0], $PAGE[$type]['sub'][1]) ?></p>
    </header>

    <nav class="type-pills reveal" aria-label="Article types · أنواع المقالات">
      <?php foreach ($TYPES as $k => [$en, $ar]): ?>
        <a class="type-pill<?= $k === $type ? ' on' : '' ?>" href="articles_list.php?type=<?= e($k) ?>"<?= $k === $type ? ' aria-current="page"' : '' ?>><?= owBi($en, $ar) ?></a>
      <?php endforeach; ?>
      <a class="type-pill" href="training.php"><?= owBi('Art Institutes', 'المعاهد الفنية') ?></a>
    </nav>

    <?php if (!$items): ?>
      <div class="card empty-state d-empty reveal">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        <?= owBi($PAGE[$type]['empty'][0], $PAGE[$type]['empty'][1]) ?>
        <div><a class="btn btn-primary" href="marketplace.php"><?= owBi('Explore Gallery', 'استكشف المعرض') ?></a></div>
      </div>
    <?php else: ?>
      <div class="a-grid">
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
