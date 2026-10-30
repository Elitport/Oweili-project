<?php
/* =====================================================================
 *  404.php — the friendly "page not found" page, in English and Arabic,
 *  with a button back to the Gallery. Used by .htaccess (ErrorDocument)
 *  for any missing address, and by pages such as artwork.php when what
 *  was asked for is not there. Tables: none
 * ===================================================================== */
if (!function_exists('owAsset')) require_once __DIR__ . '/ow_paths.php';
if (!function_exists('owBi'))    require_once __DIR__ . '/artwork_lib.php';
if (!function_exists('owSeo'))   require_once __DIR__ . '/seo_lib.php';
if (!headers_sent()) { http_response_code(404); header('Cache-Control: no-store'); }
$ar404 = ($_GET['lang'] ?? '') === 'ar';
?>
<!DOCTYPE html>
<html lang="<?= $ar404 ? 'ar' : 'en' ?>" dir="<?= $ar404 ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<base href="<?= htmlspecialchars(owBase(), ENT_QUOTES) ?>"><!-- links work from any missing address, e.g. /a/b/c -->
<?php owSeo(['en' => 'Page not found', 'ar' => 'الصفحة غير موجودة', 'desc_en' => 'This page is not here. The art is waiting in the Gallery.', 'desc_ar' => 'هذه الصفحة غير موجودة. الفن بانتظارك في المعرض.', 'path' => '', 'noindex' => true]); ?>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
.nf{position:relative;z-index:10;min-height:calc(100vh - var(--nav-h));display:flex;align-items:center;justify-content:center;padding:calc(var(--nav-h) + var(--space-lg)) var(--space-sm) var(--space-xl);}
.nf .card{max-width:520px;text-align:center;padding:var(--space-xl) var(--space-lg);}
.nf .num{font-size:72px;font-weight:300;line-height:1;color:var(--color-highlight);margin:0 0 var(--space-sm);}
.nf h1{font-size:var(--fs-section);margin:0 0 var(--space-xs);}
.nf p{color:var(--color-body);margin:0 0 var(--space-md);}
.nf .acts{display:flex;gap:var(--space-xs);justify-content:center;flex-wrap:wrap;}
</style>
</head>
<body>
<div class="bg-wrap"><video class="bg-video" autoplay loop muted playsinline preload="none" src="<?= owBase() ?>bg.mp4"></video></div>
<div class="overlay"></div>
<?php include __DIR__ . '/nav.php'; ?>
<main class="nf">
  <div class="card">
    <p class="num" aria-hidden="true">404</p>
    <h1><?= owBi('This page has wandered off', 'هذه الصفحة غير موجودة') ?></h1>
    <p><?= owBi('The page you looked for is not here. The art is still waiting for you in the Gallery.', 'الصفحة التي تبحث عنها ليست هنا. والفن ما زال بانتظارك في المعرض.') ?></p>
    <div class="acts">
      <a class="btn btn-primary" href="<?= owBase() ?>marketplace.php"><?= owBi('Back to the Gallery', 'العودة إلى المعرض') ?></a>
      <a class="btn btn-secondary" href="<?= owBase() ?>index.php"><?= owBi('Home', 'الرئيسية') ?></a>
    </div>
  </div>
</main>
<script>
function tgl(){ var ar = document.documentElement.getAttribute('dir') !== 'rtl';
  document.documentElement.setAttribute('dir', ar ? 'rtl' : 'ltr'); document.documentElement.setAttribute('lang', ar ? 'ar' : 'en'); }
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
