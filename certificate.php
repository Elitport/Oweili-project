<?php
/* =====================================================================
 *  certificate.php — one Certificate of Authenticity (certificate.php?no=…),
 *  ready to print or save as a PDF (A4, landscape, one page).
 *  English and Arabic side by side, so the same PDF works for everyone.
 *  Who can open it: the artist it belongs to, the Oweili team, and later
 *  the buyer it is assigned to. Everyone else uses the public verify page
 *  (oweili.com/verify), which the QR code on the certificate opens.
 *  Tables: certificates (read), users (who is signed in)
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/cert_lib.php';
require_once __DIR__ . '/qr_lib.php';

$cert = owCertByNo((string) ($_GET['no'] ?? ''));
if (!$cert || !owCertCanView($cert)) {
    if ($cert && !isLoggedIn() && !owCertIsStaff()) { header('Location: login.php?next=' . rawurlencode('certificate.php?no=' . $cert['cert_no'])); exit; }
    http_response_code(404); require __DIR__ . '/404.php'; exit;
}
$s       = $cert['snap'];
$no      = (string) $cert['cert_no'];
$valid   = $cert['status'] === 'valid';
$img     = owImgSrc((string) ($s['image'] ?? ''));
$imgBig  = $img !== '' ? owImgSized($img, 800) : '';
[$issEn, $issAr] = owDateBi((string) $cert['issued_at']);
[$decEn, $decAr] = owDateBi((string) ($s['declared_at'] ?? ''), true);
[$sizeEn, $sizeAr] = owCertSize($s['width_cm'] ?? 0, $s['height_cm'] ?? 0);
[$edEn, $edAr] = owCertEdition($s['edition_type'] ?? 'original', $s['edition_number'] ?? null, $s['edition_size'] ?? null);
$verify  = owCertVerifyUrl($no);
$qr      = owQrSvg($verify, 112, 'Scan to verify · امسح للتحقق');
$stages  = $s['stages'] ?? [];
$owner   = trim((string) ($cert['owner_name'] ?? ''));
$fileName = 'Oweili-Certificate-' . $no;
$mark = '<svg viewBox="0 0 24 24" aria-hidden="true"><g fill="currentColor"><path d="M11 1A10 10 0 0 0 1 11h10z"/><path d="M13 1v10h10A10 10 0 0 0 13 1z"/><path d="M1 13a10 10 0 0 0 10 10V13z"/><path d="M13 13v10a10 10 0 0 0 10-10z"/></g></svg>';
/* one line of the details: English label · value · Arabic label */
$row = function (string $en, string $ar, string $valEn, ?string $valAr = null): string {
    $valAr = $valAr ?? $valEn;
    $same = $valEn === $valAr;
    return '<tr><th scope="row" lang="en">' . e($en) . '</th><td>' . ($same ? '<span dir="auto">' . e($valEn) . '</span>'
         : '<span lang="en" dir="ltr">' . e($valEn) . '</span><span class="v-ar" lang="ar" dir="rtl">' . e($valAr) . '</span>')
         . '</td><th scope="row" lang="ar" dir="rtl" class="th-ar">' . e($ar) . '</th></tr>';
};
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= e($fileName) ?></title>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
/* ---- the page around the certificate (screen only) ---- */
body.cert-page{background:var(--color-surface-2);min-height:100vh;margin:0;}
.cert-bar{position:sticky;top:0;z-index:5;display:flex;align-items:center;justify-content:space-between;gap:var(--space-sm);flex-wrap:wrap;
  padding:12px var(--space-sm);background:var(--color-surface);border-bottom:1px solid var(--color-border);}
.cert-bar .acts{display:flex;gap:var(--space-xs);flex-wrap:wrap;align-items:center;}
.cert-hint-print{font-size:var(--fs-caption);color:var(--color-body);max-width:1123px;margin:var(--space-sm) auto 0;padding:0 var(--space-sm);}
.cert-stage{padding:var(--space-md) var(--space-sm) var(--space-xl);display:flex;justify-content:center;}
.cert-fit{width:1123px;}
/* ---- the certificate: A4 landscape, 297 × 210 mm (1123 × 794 px) ---- */
.cert{position:relative;width:1123px;height:794px;box-sizing:border-box;background:var(--cert-paper);color:var(--cert-ink);
  font-family:var(--font-sans);padding:30px;box-shadow:0 20px 60px rgba(35,49,74,.18);overflow:hidden;color-scheme:light;}
.cert *{box-sizing:border-box;}
.cert-frame{position:absolute;inset:18px;border:1px solid var(--cert-ink);pointer-events:none;}
.cert-frame::after{content:'';position:absolute;inset:6px;border:2px solid var(--cert-accent);}
.cert-corner{position:absolute;width:46px;height:46px;color:var(--cert-accent);}
.cert-corner svg{width:100%;height:100%;}
.cert-corner.tl{top:30px;left:30px}.cert-corner.tr{top:30px;right:30px;transform:scaleX(-1)}
.cert-corner.bl{bottom:30px;left:30px;transform:scaleY(-1)}.cert-corner.br{bottom:30px;right:30px;transform:scale(-1,-1)}
.cert-in{position:relative;height:100%;padding:26px 54px 22px;display:grid;grid-template-rows:auto 1fr auto;gap:14px;}
.c-head{display:grid;grid-template-columns:minmax(0,1fr) auto minmax(220px,1fr);align-items:center;gap:16px;}
.c-brand{display:flex;align-items:center;gap:10px;font-weight:700;letter-spacing:.24em;font-size:15px;}
.c-brand svg{width:30px;height:30px;color:var(--cert-accent);}
.c-brand .ar{letter-spacing:0;font-weight:700;color:var(--cert-body);}
.c-title{text-align:center;}
.c-title h1{margin:0;font-size:31px;font-weight:300;letter-spacing:.12em;text-transform:uppercase;line-height:1.15;color:var(--cert-ink);}
.c-title .t-ar{display:block;font-size:24px;font-weight:700;letter-spacing:0;margin-top:2px;}
.c-no{text-align:end;font-size:11px;color:var(--cert-body);line-height:1.5;}
.c-no .d{display:block;text-align:right;}
.c-no b{display:block;font-size:16px;letter-spacing:.06em;color:var(--cert-ink);font-weight:700;white-space:nowrap;}
.c-body{display:grid;grid-template-columns:330px 1fr;gap:30px;min-height:0;}
.c-photo{display:flex;flex-direction:column;gap:10px;min-height:0;}
.c-photo .pic{flex:1;min-height:0;border:1px solid var(--cert-line);background:var(--cert-tint);display:flex;align-items:center;justify-content:center;padding:10px;max-height:300px;}
.c-photo .pic img{max-width:100%;max-height:100%;object-fit:contain;display:block;}
.c-photo .pic .ph{font-size:12px;color:var(--cert-muted);}
.c-stages{display:flex;align-items:center;gap:6px;}
.c-stages figure{margin:0;flex:1;text-align:center;}
.c-stages .s{aspect-ratio:1;border:1px solid var(--cert-line);background:var(--cert-tint);display:flex;align-items:center;justify-content:center;overflow:hidden;}
.c-stages img{width:100%;height:100%;object-fit:cover;display:block;}
.c-stages figcaption{font-size:10px;font-weight:700;color:var(--cert-body);margin-top:3px;}
.c-stages .gap{width:14px;border-top:1px dashed var(--cert-muted);flex:0 0 14px;}
.c-touch{align-self:center;display:inline-block;padding:4px 12px;border:1px solid var(--cert-accent);border-radius:999px;font-size:12px;font-weight:700;color:var(--cert-ink);background:var(--cert-tint);}
.c-details{display:flex;flex-direction:column;gap:10px;min-width:0;}
.c-table{width:100%;border-collapse:collapse;font-size:13px;}
.c-table th,.c-table td{padding:6px 0;border-bottom:1px solid var(--cert-line);vertical-align:top;text-align:start;}
.c-table th{width:118px;font-size:10.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--cert-body);padding-top:8px;}
.c-table th.th-ar{text-align:end;letter-spacing:0;text-transform:none;font-size:12px;}
.c-table td{font-weight:600;color:var(--cert-ink);padding-inline:12px;}
.c-table td .v-ar{display:block;font-weight:600;text-align:right;}
.c-table tr.big td{font-size:17px;font-weight:700;}
.c-declare{border-inline-start:3px solid var(--cert-accent);background:var(--cert-tint);padding:8px 14px;font-size:12.5px;line-height:1.55;}
.c-declare q{display:block;font-style:italic;quotes:"“" "”";}
.c-declare q[lang=ar]{font-style:normal;text-align:right;quotes:"«" "»";}
.c-declare .when{font-size:11px;color:var(--cert-body);margin-top:3px;display:flex;justify-content:space-between;gap:8px;}
.c-rights{font-size:11px;line-height:1.5;color:var(--cert-body);display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.c-rights [lang=ar]{text-align:right;}
.c-owner{font-size:12px;color:var(--cert-ink);}
.c-foot{display:grid;grid-template-columns:auto 1fr auto;align-items:end;gap:20px;border-top:1px solid var(--cert-line);padding-top:12px;}
.c-qr{display:flex;align-items:center;gap:12px;}
.c-qr svg{display:block;width:96px;height:96px;}
.c-qr .how{font-size:11px;line-height:1.5;color:var(--cert-body);max-width:200px;}
.c-qr .how b{color:var(--cert-ink);}
.c-qr .url{font-size:10px;word-break:break-all;color:var(--cert-ink);}
.c-issued{text-align:center;font-size:11.5px;line-height:1.6;color:var(--cert-body);}
.c-issued b{color:var(--cert-ink);}
.c-seal{width:96px;height:96px;border-radius:50%;border:2px solid var(--cert-accent);display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;
  color:var(--cert-ink);font-size:9px;font-weight:700;letter-spacing:.12em;line-height:1.3;position:relative;}
.c-seal::before{content:'';position:absolute;inset:4px;border:1px dashed var(--cert-accent);border-radius:50%;}
.c-seal svg{width:24px;height:24px;color:var(--cert-accent);margin-bottom:2px;}
.c-void{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;z-index:3;}
.c-void span{transform:rotate(-18deg);border:6px solid var(--cert-accent-dark);color:var(--cert-accent-dark);font-size:64px;font-weight:700;letter-spacing:.14em;
  padding:6px 30px;background:rgba(255,255,255,.82);text-align:center;line-height:1.15;}
.c-void small{display:block;font-size:26px;letter-spacing:0;}
/* ---- print / save as PDF: exactly one A4 landscape page ---- */
@page{size:A4 landscape;margin:0;}
@media print{
  html,body{background:#fff !important;}
  .cert-bar,.cert-hint-print,.bg-wrap,.overlay{display:none !important;}
  .cert-stage{padding:0;display:block;}
  .cert-fit{width:297mm !important;height:auto !important;}
  .cert{transform:none !important;}
  .cert{width:297mm;height:210mm;box-shadow:none;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
  body{animation:none !important;opacity:1 !important;}
}
</style>
</head>
<body class="cert-page">
<div class="cert-bar">
  <div class="acts">
    <a class="btn btn-ghost btn-sm" href="<?= owCertIsStaff() && !isLoggedIn() ? 'admin.php#certs' : 'artist_studio.php?tab=certs' ?>">← <?= owBi('Back', 'رجوع') ?></a>
    <a class="btn btn-secondary btn-sm" href="<?= e($verify) ?>"><?= owBi('Verification page', 'صفحة التحقق') ?></a>
  </div>
  <div class="acts">
    <button type="button" class="btn btn-primary btn-sm" onclick="owSavePdf()"><?= owBi('Download PDF', 'تنزيل PDF') ?></button>
    <?php owCornerControls('tgl()'); ?>
  </div>
</div>
<p class="cert-hint-print"><?= owBi('In the window that opens, choose “Save as PDF”. On a phone, use Share or Print, then Save to Files.', 'في النافذة التي تظهر اختر «حفظ بصيغة PDF». على الجوال استخدم «مشاركة» أو «طباعة» ثم «حفظ في الملفات».') ?></p>

<div class="cert-stage"><div class="cert-fit" id="fit">
<article class="cert" dir="ltr" aria-label="Certificate of Authenticity · شهادة أصالة">
  <div class="cert-frame" aria-hidden="true"></div>
  <?php foreach (['tl', 'tr', 'bl', 'br'] as $cn): ?><span class="cert-corner <?= $cn ?>" aria-hidden="true"><svg viewBox="0 0 46 46"><path d="M2 44V14A12 12 0 0 1 14 2h30" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M8 44V18a10 10 0 0 1 10-10h26" fill="none" stroke="currentColor" stroke-width=".8"/><circle cx="14" cy="14" r="2.2" fill="currentColor"/></svg></span><?php endforeach; ?>
  <?php if (!$valid): ?><div class="c-void"><span>CANCELLED<small lang="ar">ملغاة</small></span></div><?php endif; ?>
  <div class="cert-in">
    <header class="c-head">
      <div class="c-brand"><?= $mark ?><span>OWEILI</span><span class="ar" lang="ar">أويلي</span></div>
      <div class="c-title"><h1>Certificate of Authenticity<span class="t-ar" lang="ar" dir="rtl">شهادة أصالة</span></h1></div>
      <div class="c-no"><span>Certificate no. · رقم الشهادة</span><b dir="ltr"><?= e($no) ?></b><span class="d" dir="ltr"><?= e($issEn) ?></span><span class="d" lang="ar" dir="rtl"><?= e($issAr) ?></span></div>
    </header>

    <section class="c-body">
      <div class="c-photo">
        <div class="pic"><?php if ($imgBig !== ''): ?><img src="<?= e($imgBig) ?>" alt="<?= e(($s['title_en'] ?? '') . ' · ' . ($s['title_ar'] ?? '')) ?>"<?= owImgFallbackAttrs($img, $imgBig) ?>><?php else: ?><span class="ph">Photo · الصورة</span><?php endif; ?></div>
        <?php if ($stages): ?>
        <div class="c-stages" aria-label="Creation journey · رحلة الإبداع">
          <?php $firstS = true; foreach ([25, 50, 100] as $pct): $st = $stages[(string) $pct] ?? null; if (!$st) continue; $sp = owImgSrc((string) $st['photo']); ?>
            <?php if (!$firstS): ?><span class="gap" aria-hidden="true"></span><?php endif; $firstS = false; ?>
            <figure><div class="s"><?php if ($sp !== ''): $s4 = owImgSized($sp, 400); ?><img src="<?= e($s4) ?>" alt="Stage <?= $pct ?>% · مرحلة <?= $pct ?>%"<?= owImgFallbackAttrs($sp, $s4) ?>><?php endif; ?></div><figcaption><?= $pct ?>%</figcaption></figure>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($s['real_touch'])): ?><span class="c-touch">✦ A Real Touch · <span lang="ar">لمسة حقيقية</span> ✦</span><?php endif; ?>
      </div>

      <div class="c-details">
        <table class="c-table">
          <tbody>
            <?= str_replace('<tr>', '<tr class="big">', $row('Title', 'العنوان', (string) ($s['title_en'] ?? ''), (string) ($s['title_ar'] ?? ''))) ?>
            <?= $row('Artist', 'الفنان', (string) ($s['artist_en'] ?? ''), (string) ($s['artist_ar'] ?? '')) ?>
            <?= $row('Medium', 'الخامة', (string) ($s['medium'] ?? '')) ?>
            <?= $row('Size', 'المقاس', $sizeEn, $sizeAr) ?>
            <?= $row('Year', 'السنة', (string) (int) ($s['year_made'] ?? 0)) ?>
            <?= $row('Type', 'النوع', $edEn, $edAr) ?>
            <?= $row('Art style', 'الأسلوب', (string) ($s['type_en'] ?? ''), (string) ($s['type_ar'] ?? '')) ?>
          </tbody>
        </table>
        <div class="c-declare">
          <q lang="en"><?= e(OW_CERT_DECLARATION[0]) ?></q>
          <q lang="ar" dir="rtl"><?= e(OW_CERT_DECLARATION[1]) ?></q>
          <div class="when"><span>Declared by the artist on <?= e($decEn) ?></span><span lang="ar" dir="rtl">أقرّ به الفنان بتاريخ <?= e($decAr) ?></span></div>
        </div>
        <?php if ($owner !== ''): ?><p class="c-owner"><b>Owner · المالك:</b> <span dir="auto"><?= e($owner) ?></span></p><?php endif; ?>
        <div class="c-rights"><p lang="en"><?= e(OW_CERT_RIGHTS[0]) ?></p><p lang="ar" dir="rtl"><?= e(OW_CERT_RIGHTS[1]) ?></p></div>
      </div>
    </section>

    <footer class="c-foot">
      <div class="c-qr"><?= $qr ?><div class="how"><b>Scan to verify</b> · <span lang="ar">امسح للتحقق</span><br>or open · أو افتح<br><span class="url" dir="ltr"><?= e(preg_replace('#^https://#', '', $verify)) ?></span></div></div>
      <div class="c-issued">Issued by <b>Oweili</b>, the home of real human art, on <b><?= e($issEn) ?></b><br>
        <span lang="ar" dir="rtl">صادرة عن <b>أويلي</b>، بيت الفن البشري الحقيقي، بتاريخ <b><?= e($issAr) ?></b></span></div>
      <div class="c-seal" aria-hidden="true"><?= $mark ?>OWEILI<br>AUTHENTIC<br>أصلي</div>
    </footer>
  </div>
</article>
</div></div>

<script>
/* fit the A4 certificate to the screen width (phones see the whole page); printing uses the real size */
function owFit2(){ var f = document.getElementById('fit'), c = f.firstElementChild; var k = Math.min(1, (document.documentElement.clientWidth - 24) / 1123);
  c.style.transformOrigin = 'top left'; c.style.transform = k < 1 ? 'scale(' + k + ')' : '';
  f.style.width = (1123 * k) + 'px'; f.style.height = (794 * k) + 'px'; }
window.addEventListener('resize', owFit2); owFit2();
function owSavePdf(){ var c = document.querySelector('.cert'), t = c.style.transform; c.style.transform = ''; window.print(); c.style.transform = t; }
window.addEventListener('beforeprint', function(){ document.querySelector('.cert').style.transform = ''; });
window.addEventListener('afterprint', owFit2);
function tgl(){ var ar = document.documentElement.getAttribute('dir') !== 'rtl'; document.documentElement.setAttribute('dir', ar ? 'rtl' : 'ltr'); document.documentElement.lang = ar ? 'ar' : 'en';
  document.getElementById('lb').textContent = ar ? 'English' : 'العربية'; try { localStorage.setItem('lang', ar ? 'ar' : 'en'); } catch (e) {} owFit2(); }
try { if (localStorage.getItem('lang') === 'ar') tgl(); } catch (e) {}
</script>
</body>
</html>
