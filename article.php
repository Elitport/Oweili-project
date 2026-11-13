<?php
require_once __DIR__ . '/studio_lib.php';   // the studio photo behind the page
/* =====================================================================
 *  article.php — one published article, for everyone
 *  article.php?slug=arabic-calligraphy
 *  ?preview=1: the team (owArticleStaff) can see an article that is not
 *  public yet (an artist's article waiting for approval), never indexed.
 *  Articles written by an artist show "Written by <artist>" with a link.
 *  Tables: articles, article_images, photo_consents (see article_lib.php)
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/page_cache.php'; if (empty($_GET['preview'])) owPublicPage();   // staff previews stay private
require_once __DIR__ . '/article_lib.php';

$TYPES = owArticleTypes();
require_once __DIR__ . '/gallery_lib.php';
require_once __DIR__ . '/admin_shell.php';
if (!owSpotlightsOn() && !owIsAdmin()) unset($TYPES['artist_spotlight']);   // hidden for now (6 Nov 2026): no type button to it; visitors never reach a spotlight (404)
$slug  = (string) ($_GET['slug'] ?? '');
$a = null;
$images = [];
$byArtist = null;
$preview = !empty($_GET['preview']) && owArticleStaff() !== null;
if ($slug !== '' && strlen($slug) <= 190 && preg_match('/^[a-z0-9-]+$/', $slug) && owArticlesReady()) {
    try {
        $st = getDB()->prepare('SELECT a.* FROM articles a WHERE a.slug = :s' . ($preview ? '' : ' AND ' . owArticlePublicSql('a')));
        $st->execute([':s' => $slug]);
        $a = $st->fetch() ?: null;
        if ($a && $a['author_user_id']) {
            require_once __DIR__ . '/artwork_lib.php';
            $st = getDB()->prepare("SELECT u.id, " . owArtistNameSql('u') . " FROM users u WHERE u.id = :id AND u.role = 'artist' AND u.is_approved = 1 AND COALESCE(u.is_banned, 0) = 0");
            $st->execute([':id' => $a['author_user_id']]);
            $byArtist = $st->fetch() ?: null;
        }
        if ($a) {
            $st = getDB()->prepare('SELECT image_path, alt_en, alt_ar FROM article_images WHERE article_id = :id ORDER BY sort_order, id');
            $st->execute([':id' => $a['id']]);
            $images = array_values(array_filter($st->fetchAll(), fn($im) => owSafeArticlePath((string) $im['image_path'])));
        }
    } catch (Throwable $e) {
        error_log('article: ' . $e->getMessage());
        $a = null;
    }
}
if (!$a) http_response_code(404);

$type    = $a['type'] ?? 'event';
$cover   = $a && owSafeArticlePath((string) $a['cover_image_path']) ? $a['cover_image_path'] : '';
$titleEn = $a ? $a['title_en'] . ' · Oweili' : 'Article not found · Oweili';
$titleAr = $a ? $a['title_ar'] . ' · أويلي' : 'المقال غير موجود · أويلي';
?>
<!DOCTYPE html>
<html <?php require_once __DIR__ . '/lang_lib.php'; owHtmlAttrs(); ?> data-title-en="<?= e($titleEn) ?>" data-title-ar="<?= e($titleAr) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require_once __DIR__ . '/seo_lib.php';
if ($a) owSeo(['en' => (string) $a['title_en'], 'ar' => (string) $a['title_ar'], 'desc_en' => owExcerpt((string) $a['body_en'], 150), 'desc_ar' => owExcerpt((string) $a['body_ar'], 150),
               'image' => (string) ($cover ?? ''), 'path' => 'article.php?slug=' . rawurlencode((string) $a['slug']), 'type' => 'article'] + ($preview ? ['noindex' => true] : []));
else owSeo(['en' => 'Article not found', 'ar' => 'المقال غير موجود', 'path' => 'articles_list.php', 'noindex' => true]); ?>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/nav.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/discover.css') ?>">
<script>document.documentElement.classList.add('js')</script><script defer src="<?= owAsset('assets/js/discover.js') ?>"></script>
<style>
/* One article — reading layout only; type, colors, buttons and cards come from oweili.css */
.cover{position:relative;z-index:2;margin-top:var(--nav-h);height:clamp(220px,52vh,520px);background:var(--color-surface-2);overflow:hidden;}
.cover img{width:100%;height:100%;object-fit:cover;display:block;}
.art-wrap{max-width:820px;margin:0 auto;padding:var(--space-lg) var(--space-lg) var(--space-2xl);}
.art-wrap.no-cover{padding-top:calc(var(--nav-h) + var(--space-xl));}
.art-card{padding:var(--space-xl);}
.cover + main .art-card{margin-top:-80px;position:relative;}
.art-type{display:inline-block;padding:3px 12px;border-radius:var(--radius-pill);font-size:var(--fs-min);font-weight:700;color:var(--color-accent-dark);background:rgba(var(--rgb-accent),0.08);border:1px solid rgba(var(--rgb-accent),0.2);margin-bottom:var(--space-sm);text-decoration:none;}
.art-type:hover{background:rgba(var(--rgb-accent),0.15);color:var(--color-accent-dark);}
.art-card h1{font-size:var(--fs-hero);letter-spacing:-0.03em;line-height:1.15;margin:0 0 var(--space-xs);}
[dir="rtl"] .art-card h1{line-height:1.35;}
.art-date{font-size:var(--fs-caption);color:var(--color-body);margin:0 0 var(--space-md);}
.event-box{display:grid;gap:var(--space-xs);padding:var(--space-sm) var(--space-md);margin:0 0 var(--space-md);border-radius:var(--radius-card);background:var(--color-tint-blue);border:1px solid rgba(var(--rgb-accent),0.18);}
.event-row{display:flex;gap:var(--space-xs);align-items:flex-start;font-size:var(--fs-body);font-weight:600;color:var(--color-dark);}
.event-row svg{width:20px;height:20px;flex-shrink:0;margin-top:2px;stroke:var(--color-accent);fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}
.event-note{font-size:var(--fs-label);font-weight:600;color:var(--color-accent-dark);}
.article-body p{font-size:17px;color:rgba(var(--rgb-ink),0.72);margin:0 0 var(--space-md);}
.article-body[dir="rtl"] p{font-size:18px;}
html.dark-mode .article-body p{color:var(--color-light);}   /* dark mode: a little lighter, easier to read (owner, 11 Nov 2026) */
.gallery{display:grid;grid-template-columns:repeat(2,1fr);gap:var(--space-sm);margin-top:var(--space-lg);}
.gallery figure{margin:0;border-radius:12px;overflow:hidden;background:var(--color-surface);border:1px solid var(--color-border);}
.gallery a{display:block;}
.gallery img{width:100%;aspect-ratio:4/3;object-fit:cover;display:block;transition:transform 0.4s;}
.gallery a:hover img{transform:scale(1.03);}
.gallery a:focus-visible{outline:2px solid var(--color-accent);outline-offset:2px;}
.art-actions{display:flex;flex-wrap:wrap;gap:var(--space-sm);margin-top:var(--space-lg);padding-top:var(--space-md);border-top:1px solid var(--color-border);}
@media (max-width:768px){
  .art-wrap{padding:var(--space-sm) var(--space-sm) var(--space-xl);}
  .art-wrap.no-cover{padding-top:calc(var(--nav-h) + var(--space-md));}
  .art-card{padding:var(--space-md);}
  .cover + main .art-card{margin-top:-40px;}
  .gallery{grid-template-columns:1fr;}
  .article-body p{font-size:16px;}
}
</style>
</head>
<body class="ow">
<?= owStudioBackdrop($type === 'art_education' ? 'open-books' : ($type === 'event' ? 'events-hall' : 'museum-walk')) /* the owner's wide photos: Art Education the open books (6 Nov 2026), Art Events the hall with posters (7 Nov 2026), the hidden Spotlights still the museum hall */ ?>
<div class="bg-wrap" aria-hidden="true"></div>
<div class="veil strong" aria-hidden="true"></div>

<?php include __DIR__ . '/nav.php'; ?>

<?php if (!$a): ?>
<main>
  <div class="d-wrap">
    <div class="card empty-state d-empty">
      <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      <h1 style="font-size:var(--fs-card);margin:0 0 var(--space-xs);"><?= owBi('We could not find this article', 'لم نجد هذا المقال') ?></h1>
      <?= owBi('It may have been moved or is not published yet.', 'ربما نُقل أو لم يُنشر بعد.') ?>
      <div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;">
        <?php foreach ($TYPES as $k => [$en, $ar]): ?>
          <a class="btn btn-secondary" href="articles_list.php?type=<?= e($k) ?>"><?= owBi($en, $ar) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</main>
<?php else:
  [$typeEn, $typeAr] = $TYPES[$type];
  [$pEn, $pAr] = owDateBi($a['published_at']);
?>
<?php if ($cover): ?>
<div class="cover"><img src="<?= e($cover) ?>" alt="<?= e($a['title_en'] . ' · ' . $a['title_ar']) ?>"></div>
<?php endif; ?>
<main>
  <div class="art-wrap<?= $cover ? '' : ' no-cover' ?>">
    <article class="card art-card">
      <a class="art-type" href="articles_list.php?type=<?= e($type) ?>"><?= owBi($typeEn, $typeAr) ?></a>
      <h1><?= owBi($a['title_en'], $a['title_ar']) ?></h1>
      <?php if ($preview && $a['status'] !== 'published'): ?><p class="alert alert-error" role="status"><?= owBi('Preview for the team: visitors cannot see this article yet.', 'معاينة للفريق: لا يرى الزوار هذا المقال بعد.') ?></p><?php endif; ?>
      <p class="art-date"><?php if ($a['published_at']): ?><?= owBi('Published ' . $pEn, 'نُشر في ' . $pAr) ?><?php endif; ?><?php if ($byArtist): ?><?= $a['published_at'] ? ' · ' : '' ?><?= owBi('Written by', 'بقلم') ?> <a href="artist_dashboard.php?id=<?= (int) $byArtist['id'] ?>"><?= owBiName((string) $byArtist['artist_en'], (string) $byArtist['artist_ar']) ?></a><?php endif; ?></p>

      <?php if ($type === 'event'): [$dEn, $dAr] = owDateRangeBi($a['event_start_date'], $a['event_end_date']); ?>
        <div class="event-box">
          <div class="event-row"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg><?= owBi($dEn, $dAr) ?></div>
          <?php if ($a['event_location_en'] !== '' || $a['event_location_ar'] !== ''): ?>
            <div class="event-row"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg><?= owBi($a['event_location_en'] ?: $a['event_location_ar'], $a['event_location_ar'] ?: $a['event_location_en']) ?></div>
          <?php endif; ?>
          <div class="event-note"><?= $byArtist ? owBi('Shared by an Oweili artist, checked by the Oweili team', 'شاركه فنان من أويلي، وراجعه فريق أويلي') : owBi('Added by the Oweili team', 'أضافه فريق أويلي') ?></div>
        </div>
      <?php endif; ?>

      <?= owBodyHtml((string) $a['body_en'], 'en') ?>
      <?= owBodyHtml((string) $a['body_ar'], 'ar') ?>

      <?php if ($images): ?>
        <div class="gallery">
          <?php foreach ($images as $im): $alt = $im['alt_en'] . ' · ' . $im['alt_ar']; ?>
            <figure><a href="<?= e($im['image_path']) ?>" target="_blank" rel="noopener" aria-label="<?= e($alt) ?>"><img loading="lazy" src="<?= e($im['image_path']) ?>" alt="<?= e($alt) ?>"></a></figure>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="art-actions">
        <a class="btn btn-secondary" href="articles_list.php?type=<?= e($type) ?>"><?= owBi('← Back to ' . $typeEn, '→ العودة إلى ' . $typeAr) ?></a>
        <a class="btn btn-primary" href="marketplace.php"><?= owBi('Explore Gallery', 'استكشف المعرض') ?></a>
      </div>
    </article>
  </div>
</main>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
