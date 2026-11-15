<?php
/* =====================================================================
 *  about.php — About Oweili («من نحن») (owner, 14 Nov 2026)
 *  The story of Oweili, why handmade art matters, proof of process, the certificate,
 *  and how collectors and artists are protected. Linked only from the footer.
 *  Same look as the other photo pages (studio photo, frosted title card). Tables: none
 *  (reads Oweili's commission from site_settings through owCommissionText()).
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/page_cache.php'; owPublicPage();   // public page: Back opens at once, early load on touch
require_once __DIR__ . '/studio_lib.php';
require_once __DIR__ . '/artwork_lib.php';
require_once __DIR__ . '/platform_lib.php';
$pct = '15%';
try { $pct = owCommissionText(); } catch (Throwable $e) {}
$P = fn(string $en, string $ar) => '<p>' . owBi($en, $ar) . '</p>';
?>
<!DOCTYPE html>
<html <?php require_once __DIR__ . '/lang_lib.php'; owHtmlAttrs(); ?> data-title-en="About Oweili" data-title-ar="من نحن · أويلي">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require_once __DIR__ . '/seo_lib.php'; owSeo(['en' => 'About Oweili', 'ar' => 'من نحن', 'desc_en' => 'Oweili is the home of real art. Handmade paintings, proof of process and a certificate with every piece.', 'desc_ar' => 'أويلي بيت الفن الأصيل. لوحات مرسومة باليد، ودليل على مراحل الرسم، وشهادة أصالة مع كل عمل.', 'path' => 'about.php']); ?>
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
      <h1><?= owBi('About Oweili', 'من نحن') ?></h1>
      <p><?= owBi('The home of real art. Every piece here was made by an artist’s own hand.', 'بيت الفن الأصيل. كل عمل هنا رسمته يد فنان.') ?></p>
    </header>

    <section class="card ab-card reveal">
      <h2><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3c-4.97 0-9 3.58-9 8 0 4.4 3.5 8 8 8 1 0 1.5-.6 1.5-1.4 0-.8-.6-1.1-.6-1.9 0-.8.6-1.4 1.4-1.4H15c3.3 0 6-2.3 6-5.3C21 5.9 17 3 12 3z"/><circle cx="7.5" cy="10.5" r="1"/><circle cx="11" cy="7" r="1"/><circle cx="15.5" cy="8" r="1"/></svg><?= owBi('Our story', 'حكايتنا') ?></h2>
      <?= $P('Oweili began in Saudi Arabia with a simple wish. We wanted a place where people can buy real art and know it is real.', 'بدأ أويلي في المملكة العربية السعودية بأمنية بسيطة. أردنا مكاناً يقتني فيه الناس فناً أصيلاً، وهم واثقون أنه أصيل.') ?>
      <?= $P('Today Oweili is an online gallery open to artists and collectors from every country. We celebrate Saudi and Arab art, and we welcome every artist who paints with their own hands.', 'واليوم أويلي معرض إلكتروني مفتوح للفنانين والمقتنين من كل البلدان. نحتفي بالفن السعودي والعربي، ونرحب بكل فنان يرسم بيديه.') ?>
    </section>

    <section class="card ab-card reveal">
      <h2><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 3l3 3-9.5 9.5-4 1 1-4z"/><path d="M14.5 6.5l3 3"/><path d="M3 21c2-1 3-3 6-3"/></svg><?= owBi('Why handmade art matters', 'لماذا يهمنا الفن المرسوم باليد') ?></h2>
      <?= $P('A handmade painting carries hours of work, many choices and a real hand. No two are the same.', 'اللوحة المرسومة باليد تحمل ساعات من العمل، وقرارات كثيرة، ويداً حقيقية. ولا تتشابه لوحتان أبداً.') ?>
      <?= $P('When you buy one, you own a piece of an artist’s time and story. And you help that artist keep painting.', 'حين تقتني واحدة، فأنت تملك جزءاً من وقت الفنان وحكايته. وتساعده على أن يواصل الرسم.') ?>
    </section>

    <section class="card ab-card reveal">
      <h2><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="13" rx="2"/><circle cx="12" cy="12.5" r="3.5"/><path d="M8 6l1.5-2h5L16 6"/></svg><?= owBi('Proof of process', 'دليل مراحل الرسم') ?></h2>
      <?= $P('Every artist photographs the artwork three times while making it, using the camera inside Oweili.', 'يصوّر كل فنان عمله ثلاث مرات أثناء رسمه، بكاميرا أويلي من داخل الموقع.') ?>
      <div class="ab-stages">
        <div class="ab-stage"><b>25%</b><span><?= owBi('The first strokes', 'الخطوط الأولى') ?></span></div>
        <div class="ab-stage"><b>50%</b><span><?= owBi('Taking shape', 'العمل يتشكّل') ?></span></div>
        <div class="ab-stage"><b>100%</b><span><?= owBi('The finished piece', 'العمل مكتملاً') ?></span></div>
      </div>
      <?= $P('Collectors see this creation journey on every artwork. Not only the final piece, but how it grew.', 'يرى المقتنون رحلة الإبداع هذه في كل عمل. ليس العمل النهائي فقط، بل كيف كبر خطوة بخطوة.') ?>
    </section>

    <section class="card ab-card reveal">
      <h2><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM18 18h3v3h-3zM14 20h1M20 14h1"/></svg><?= owBi('A certificate with every piece', 'شهادة أصالة مع كل عمل') ?></h2>
      <?= $P('Every approved artwork gets a Certificate of Authenticity with its own number and a QR code.', 'يحصل كل عمل معتمد على شهادة أصالة برقم خاص ورمز QR.') ?>
      <?= $P('Scan the code, or type the number on our verify page, and you will see the artwork, its artist and its details.', 'امسح الرمز، أو اكتب الرقم في صفحة التحقق، وسترى العمل وفنانه وتفاصيله.') ?>
      <p><a href="cert_verify.php"><?= owBi('Verify a certificate', 'تحقّق من شهادة') ?></a></p>
    </section>

    <div class="ab-two">
      <section class="card ab-card reveal">
        <h2><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/></svg><?= owBi('How we protect collectors', 'كيف نحمي المقتنين') ?></h2>
        <ul class="ab-list">
          <li><?= owBi('Our team reviews every artist and every artwork before it goes live.', 'يراجع فريقنا كل فنان وكل عمل قبل أن يظهر في الموقع.') ?></li>
          <li><?= owBi('When your artwork arrives, you confirm it with a photo of the piece.', 'حين يصلك العمل، تؤكد الاستلام بصورة للعمل.') ?></li>
          <li><?= owBi('Your payment is held until the artwork is delivered. If something is wrong, tell us and we hold it until it is solved.', 'نحتفظ بالمبلغ حتى يصلك العمل. وإن كانت هناك مشكلة، أخبرنا ونبقيه محفوظاً حتى تُحل.') ?></li>
        </ul>
      </section>
      <section class="card ab-card reveal">
        <h2><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 21v-1a5 5 0 0 0-5-5H9a5 5 0 0 0-5 5v1"/><circle cx="12" cy="8" r="4"/></svg><?= owBi('How we protect artists', 'كيف نحمي الفنانين') ?></h2>
        <ul class="ab-list">
          <li><?= owBi('You choose your public name. Your phone and email stay private.', 'تختار اسمك العام. ويبقى هاتفك وبريدك خاصّين.') ?></li>
          <li><?= owBi('Joining is free. Oweili keeps ' . $pct . ' only when a work sells, before VAT.', 'الانضمام مجاني. يأخذ أويلي ' . $pct . ' فقط عند بيع العمل، قبل ضريبة القيمة المضافة.') ?></li>
          <li><?= owBi('Your copyright always stays with you. The collector owns the painting itself.', 'تبقى حقوق النشر لك دائماً. ويملك المقتني اللوحة نفسها.') ?></li>
        </ul>
      </section>
    </div>

    <section class="card ab-card ab-cta reveal">
      <h2><?= owBi('Come and see', 'تعال وشاهد') ?></h2>
      <?= $P('Browse real handmade art, or share your own.', 'تصفّح فناً أصيلاً مرسوماً باليد، أو شارك أعمالك.') ?>
      <div class="btns">
        <a class="btn btn-primary" href="marketplace.php"><?= owBi('Explore Gallery', 'استكشف المعرض') ?></a>
        <a class="btn btn-secondary" href="register_artist.php"><?= owBi('Join as Artist', 'انضم كفنان') ?></a>
      </div>
    </section>
  </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
