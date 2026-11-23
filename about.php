<?php
/* =====================================================================
 *  about.php — About Oweili («من نحن») (owner, 14 Nov 2026; text from the owner, 15 Nov 2026)
 *  The owner's own Arabic text, used exactly, with a matching English version: who we are,
 *  why Oweili, our standards, how collectors and artists are protected, more than a shop.
 *  Linked only from the footer. Same look as the other photo pages (studio photo, frosted
 *  title card). Tables: none.
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/page_cache.php'; owPublicPage();   // public page: Back opens at once, early load on touch
require_once __DIR__ . '/studio_lib.php';
require_once __DIR__ . '/artwork_lib.php';
$P = fn(string $en, string $ar) => '<p>' . owBi($en, $ar) . '</p>';
$L = fn(string $en1, string $en2, string $ar1, string $ar2) => '<li><b>' . owBi($en1, $ar1) . '</b> ' . owBi($en2, $ar2) . '</li>';
?>
<!DOCTYPE html>
<html <?php require_once __DIR__ . '/lang_lib.php'; owHtmlAttrs(); ?> data-title-en="About Oweili" data-title-ar="من نحن · أويلي">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require_once __DIR__ . '/seo_lib.php'; owSeo(['en' => 'About Oweili', 'ar' => 'من نحن', 'desc_en' => 'Oweili, the home of real art. A Saudi platform for original artworks by real artists.', 'desc_ar' => 'أويلي بيت الفن الأصيل. منصة سعودية تجمع الأعمال الفنية الأصلية من فنانين حقيقيين.', 'path' => 'about.php']); ?>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/nav.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/discover.css') ?>">
<script>document.documentElement.classList.add('js')</script><script defer src="<?= owAsset('assets/js/discover.js') ?>"></script>
<style>
.ab-wrap{max-width:860px;}
.ab-wrap .d-head{margin-inline:auto;text-align:center;}
.ab-card{padding:28px 32px;margin-bottom:var(--space-md);}
.ab-card h2{font-size:22px;font-weight:700;color:var(--color-dark);margin:0 0 12px;letter-spacing:-0.01em;display:flex;align-items:center;gap:12px;}
.ab-card h2 svg{flex:none;width:28px;height:28px;fill:none;stroke:var(--color-accent);stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round;}
.ab-card p{font-size:16px;line-height:1.7;color:var(--color-body);margin:0 0 10px;}
.ab-card p:last-child{margin-bottom:0;}
.ab-stages{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:16px 0 4px;}
.ab-stage{border:1px solid var(--color-border);border-radius:12px;padding:14px;text-align:center;}
.ab-stage b{display:block;font-size:22px;color:var(--color-dark);}
.ab-stage span{font-size:14px;color:var(--color-body);}
.ab-two{display:grid;grid-template-columns:1fr 1fr;gap:var(--space-md);}
.ab-two{margin-bottom:var(--space-md);}
.ab-two .ab-card{margin-bottom:0;}
.ab-list{margin:0;padding-inline-start:20px;}
.ab-list li{font-size:16px;line-height:1.7;color:var(--color-body);margin-bottom:6px;}
.ab-cta{text-align:center;margin-top:var(--space-md);}.ab-cta h2{justify-content:center;}
.ab-cta .btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:14px;}
@media (max-width:768px){.ab-two{grid-template-columns:1fr;}.ab-card{padding:20px;}.ab-stages{gap:8px;}.ab-stage{padding:10px 6px;}.ab-stage b{font-size:18px;}}
</style>
</head>
<body class="ow">
<?= owStudioBackdrop('art-supplies') /* the owner's photo of art supplies (15 Nov 2026) */ ?>
<div class="bg-wrap" aria-hidden="true"></div>
<div class="veil" aria-hidden="true"></div>

<?php include __DIR__ . '/nav.php'; ?>

<main>
  <div class="d-wrap ab-wrap">
    <header class="d-head reveal">
      <h1><?= owBi('Oweili, the home of real art.', 'أويلي بيت الفن الأصيل.') ?></h1>
      <p><?= owBi('A Saudi platform that brings together original artworks by real artists, and takes them to the people who value them, in Saudi Arabia and around the world.', 'منصة سعودية تجمع الأعمال الفنية الأصلية من فنانين حقيقيين، وتوصلها لمن يقدّرها، في السعودية وحول العالم.') ?></p>
    </header>

    <section class="card ab-card reveal">
      <h2><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 3l3 3-9.5 9.5-4 1 1-4z"/><path d="M14.5 6.5l3 3"/><path d="M3 21c2-1 3-3 6-3"/></svg><?= owBi('Why Oweili?', 'لماذا أويلي؟') ?></h2>
      <?= $P('At a time when making pictures has become easy, real art is worth more than ever. Oweili was born to protect the value of work made by an artist’s hand, and to give collectors full confidence in what they buy.', 'في زمن أصبح فيه إنتاج الصور سهلا، صار الفن الأصيل أثمن من أي وقت مضى. وُلدت أويلي لتحمي قيمة العمل الذي صنعته يد فنان، ولتمنح المقتني ثقة كاملة بما يشتريه.') ?>
    </section>

    <section class="card ab-card reveal">
      <h2><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="13" rx="2"/><circle cx="12" cy="12.5" r="3.5"/><path d="M8 6l1.5-2h5L16 6"/></svg><?= owBi('Our standards', 'معاييرنا') ?></h2>
      <ul class="ab-list">
        <?= $L('Proof of process:', 'every artwork is documented with three photos from the stages of its making (25%, 50%, 100%).', 'إثبات مراحل العمل:', 'كل عمل يُوثّق بثلاث صور من مراحل صنعه (25٪، 50٪، 100٪).') ?>
        <?= $L('Every artist and every artwork reviewed:', 'nothing is published until the Oweili team has checked and approved it.', 'مراجعة كل فنان وكل عمل:', 'لا يُنشر شيء قبل أن يمر على فريق أويلي ويُعتمد.') ?>
        <?= $L('A certificate of authenticity with a number and a QR code:', 'every artwork has a certificate that anyone can verify at any time.', 'شهادة أصالة برقم ورمز QR:', 'لكل عمل شهادة يمكن لأي شخص التحقق منها في أي وقت.') ?>
        <?= $L('A smart check for copied art:', 'we compare every new artwork with what is already on Oweili, and we review the details of the stage photos.', 'فحص ذكي للأعمال المنسوخة:', 'نقارن كل عمل جديد بما في المنصة، ونراجع بيانات صور المراحل.') ?>
      </ul>
    </section>

    <div class="ab-two">
      <section class="card ab-card reveal">
        <h2><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/></svg><?= owBi('How we protect the collector', 'كيف نحمي المقتني') ?></h2>
        <?= $P('The artist is paid only after the collector receives the artwork and checks that it arrived safely, and Oweili approves it.', 'لا يصل المبلغ للفنان إلا بعد أن يستلم المقتني العمل ويتأكد من سلامته، وتعتمد أويلي ذلك.') ?>
      </section>
      <section class="card ab-card reveal">
        <h2><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 21v-1a5 5 0 0 0-5-5H9a5 5 0 0 0-5 5v1"/><circle cx="12" cy="8" r="4"/></svg><?= owBi('How we protect the artist', 'كيف نحمي الفنان') ?></h2>
        <?= $P('Only their public name is shown to visitors, their private details stay private, and the commission is clear, published, and never changes after the sale.', 'اسمه العام وحده يظهر للزوار، وبياناته الخاصة تبقى خاصة، وعمولة واضحة ومعلنة لا تتغير بعد البيع.') ?>
      </section>
    </div>

    <section class="card ab-card reveal">
      <h2><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg><?= owBi('More than a shop', 'أكثر من متجر') ?></h2>
      <?= $P('We guide you to museums, galleries and art institutes across the regions of Saudi Arabia, and we offer guides that help you buy art and care for it.', 'ندلّك على المتاحف والمعارض والمعاهد الفنية في مناطق المملكة، ونقدّم أدلة تساعدك تشتري الفن وتعتني به.') ?>
    </section>

    <section class="card ab-card ab-cta reveal">
      <?= $P('Oweili is run by Elitport Limited, a registered Saudi company.', 'أويلي تديرها شركة إيليتبورت المحدودة، شركة سعودية مسجّلة.') ?>
      <div class="btns">
        <a class="btn btn-primary" href="marketplace.php"><?= owBi('Explore Gallery', 'استكشف المعرض') ?></a>
        <a class="btn btn-secondary" href="register_artist.php"><?= owBi('Join as Artist', 'انضم كفنان') ?></a>
        <a class="btn btn-secondary" href="cert_verify.php"><?= owBi('Verify a certificate', 'تحقق من شهادة') ?></a>
      </div>
    </section>
  </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
