<?php
/* =====================================================================
 *  legal_lib.php — the shared layout of the Privacy Policy (privacy.php)
 *  and Terms of Use (terms.php). Both pages are DRAFTS under review:
 *  a banner says so on top, in both languages.
 *  owLegalPage($meta, $sections) prints the whole page. Each section is
 *  ['id', 'Heading EN', 'العنوان', [English paragraphs], [Arabic paragraphs]].
 *  A paragraph that starts with "- " is shown as a list item.
 *  Both languages are in the page (good for search engines); the one that
 *  matches the page direction is shown (bi-en / bi-ar in oweili.css).
 *  Tables: none
 * ===================================================================== */
require_once __DIR__ . '/artwork_lib.php';

const OW_LEGAL_CONTACT = 'contact@elitport.com';

function owLegalBlock(array $paras): string {
    $out = ''; $inList = false;
    foreach ($paras as $p) {
        if (strncmp($p, '- ', 2) === 0) {
            if (!$inList) { $out .= '<ul>'; $inList = true; }
            $out .= '<li>' . e(substr($p, 2)) . '</li>';
        } else {
            if ($inList) { $out .= '</ul>'; $inList = false; }
            $out .= '<p>' . e($p) . '</p>';
        }
    }
    return $out . ($inList ? '</ul>' : '');
}

function owLegalPage(array $meta, array $sections): void {
    $ar = ($_GET['lang'] ?? '') === 'ar';
    $other = $meta['other'];   // [file, EN title, AR title]
    ?>
<!DOCTYPE html>
<html lang="<?= $ar ? 'ar' : 'en' ?>" dir="<?= $ar ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require_once __DIR__ . '/seo_lib.php'; owSeo(['en' => $meta['title_en'], 'ar' => $meta['title_ar'], 'desc_en' => $meta['desc_en'], 'desc_ar' => $meta['desc_ar'], 'path' => $meta['path']]); ?>
<?php include __DIR__ . '/theme_head.php'; ?>
<?php if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ar'], true)): ?><script>try { localStorage.setItem('lang', <?= json_encode($_GET['lang']) ?>); } catch (e) {}</script><?php endif; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
.legal{position:relative;z-index:10;max-width:820px;margin:0 auto;padding:calc(var(--nav-h) + var(--space-xl)) var(--space-sm) var(--space-2xl);}
.legal h1{font-size:var(--fs-section);margin:0 0 var(--space-xs);}
.legal .lead{font-size:var(--fs-subtitle);color:var(--color-body);margin:0 0 var(--space-md);}
.draft{display:flex;gap:var(--space-xs);align-items:flex-start;padding:12px var(--space-sm);border:1px dashed var(--color-primary);background:var(--color-tint-pink);border-radius:var(--radius-input);color:var(--color-dark);font-weight:700;margin-bottom:var(--space-md);}
.legal .card{padding:var(--space-lg);}
.toc{margin:0 0 var(--space-md);padding:0;list-style:none;display:flex;flex-wrap:wrap;gap:6px;}
.toc a{display:inline-block;padding:6px 12px;border:1px solid var(--color-border-strong);border-radius:var(--radius-pill);font-size:var(--fs-caption);font-weight:600;color:var(--color-dark);text-decoration:none;background:var(--color-surface);}
.toc a:hover{border-color:var(--color-dark);}
.legal section{scroll-margin-top:calc(var(--nav-h) + 16px);padding:var(--space-sm) 0;border-bottom:1px solid var(--color-border);}
.legal section:last-child{border-bottom:0;}
.legal h2{font-size:var(--fs-subtitle);margin:0 0 var(--space-xs);}
.legal p,.legal li{font-size:16px;line-height:1.6;color:var(--color-body);}
html[dir="rtl"] .legal p,html[dir="rtl"] .legal li{line-height:1.7;}
.legal ul{margin:0 0 var(--space-sm);padding-inline-start:22px;}
.legal .meta{font-size:var(--fs-caption);color:var(--color-body);margin-top:var(--space-md);}
.legal .next{display:flex;gap:var(--space-xs);flex-wrap:wrap;margin-top:var(--space-md);}
@media(max-width:768px){.legal{padding:calc(var(--nav-h) + var(--space-md)) var(--space-sm) var(--space-xl);}.legal .card{padding:var(--space-md);}}
</style>
</head>
<body>
<div class="bg-wrap"><video class="bg-video" autoplay loop muted playsinline preload="none" src="<?= owBase() ?>bg.mp4"></video></div>
<div class="overlay"></div>
<?php include __DIR__ . '/nav.php'; ?>
<main class="legal">
  <h1><?= owBi($meta['title_en'], $meta['title_ar']) ?></h1>
  <p class="lead"><?= owBi($meta['lead_en'], $meta['lead_ar']) ?></p>
  <div class="draft" role="note"><span aria-hidden="true">✎</span><span><?= owBi('Draft, under review. This page is not final yet and may change before launch.', 'مسودة قيد المراجعة. هذه الصفحة ليست نهائية بعد وقد تتغير قبل الإطلاق.') ?></span></div>
  <ul class="toc" aria-label="Contents · المحتويات">
    <?php foreach ($sections as $s): ?><li><a href="#<?= e($s[0]) ?>"><?= owBi($s[1], $s[2]) ?></a></li><?php endforeach; ?>
  </ul>
  <div class="card">
    <?php foreach ($sections as $s): ?>
    <section id="<?= e($s[0]) ?>">
      <h2><?= owBi($s[1], $s[2]) ?></h2>
      <div class="bi-en" lang="en"><?= owLegalBlock($s[3]) ?></div>
      <div class="bi-ar" lang="ar"><?= owLegalBlock($s[4]) ?></div>
    </section>
    <?php endforeach; ?>
  </div>
  <p class="meta"><?= owBi('Last updated: ' . $meta['updated_en'], 'آخر تحديث: ' . $meta['updated_ar']) ?></p>
  <div class="next">
    <a class="btn btn-primary" href="marketplace.php"><?= owBi('Explore the Gallery', 'استكشف المعرض') ?></a>
    <a class="btn btn-secondary" href="<?= e($other[0]) ?>"><?= owBi($other[1], $other[2]) ?></a>
    <a class="btn btn-ghost" href="mailto:<?= OW_LEGAL_CONTACT ?>"><?= owBi('Write to us', 'راسلنا') ?></a>
  </div>
</main>
<script>
function tgl(){ var ar = document.documentElement.getAttribute('dir') !== 'rtl';
  document.documentElement.setAttribute('dir', ar ? 'rtl' : 'ltr'); document.documentElement.setAttribute('lang', ar ? 'ar' : 'en'); }
(function(){ var v = document.querySelector('.bg-video'); if (!v) return; var on = function(){ v.classList.add('on'); };
  v.addEventListener('canplay', on, {once:true}); v.addEventListener('playing', on, {once:true}); v.play().catch(function(){}); })();
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
<?php
}
