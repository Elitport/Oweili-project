<?php
/* =====================================================================
 *  cert_label.php — a small label for the BACK of the artwork
 *  (cert_label.php?no=…, owner 9 Nov 2026): the certificate number, the
 *  title, the artist and the QR code that opens the public verify page.
 *  10 × 7 cm, ready to print (or save as PDF) and stick on the back.
 *  Who can open it: the same people as the certificate (its artist and
 *  the Oweili team). Linked from My Studio → Certificates.
 *  Tables: certificates (read)
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/cert_lib.php';
require_once __DIR__ . '/qr_lib.php';

$cert = owCertByNo((string) ($_GET['no'] ?? ''));
if (!$cert || !owCertCanView($cert) || $cert['status'] !== 'valid') {
    if ($cert && !isLoggedIn() && !owCertIsStaff()) { header('Location: login.php?next=' . rawurlencode('cert_label.php?no=' . $cert['cert_no'])); exit; }
    http_response_code(404); require __DIR__ . '/404.php'; exit;
}
$s = $cert['snap'];
$no = (string) $cert['cert_no'];
$verify = owCertVerifyUrl($no);
$qr = owQrSvg($verify, 100, 'Scan to verify · امسح للتحقق');
$mark = '<svg viewBox="0 0 24 24" aria-hidden="true"><g fill="currentColor"><path d="M11 1A10 10 0 0 0 1 11h10z"/><path d="M13 1v10h10A10 10 0 0 0 13 1z"/><path d="M1 13a10 10 0 0 0 10 10V13z"/><path d="M13 13v10a10 10 0 0 0 10-10z"/></g></svg>';
?>
<!DOCTYPE html>
<html <?php require_once __DIR__ . '/lang_lib.php'; owHtmlAttrs(); ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Oweili-Label-<?= e($no) ?></title>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
body.label-page{background:var(--color-surface-2);margin:0;min-height:100vh;}
.lb-bar{display:flex;flex-wrap:wrap;gap:var(--space-xs);align-items:center;justify-content:space-between;padding:12px var(--space-sm);background:var(--color-surface);border-bottom:1px solid var(--color-border);}
.lb-bar .acts{display:flex;gap:var(--space-xs);flex-wrap:wrap;align-items:center;}
.lb-hint{max-width:560px;margin:var(--space-sm) auto 0;padding:0 var(--space-sm);font-size:13px;color:var(--color-body);}
.lb-stage{display:flex;justify-content:center;padding:var(--space-md) var(--space-sm) var(--space-xl);}
/* the label: 100 × 70 mm */
.lb{width:100mm;height:70mm;box-sizing:border-box;background:var(--cert-paper);color:var(--cert-ink);color-scheme:light;font-family:var(--font-sans);
  border:1px solid var(--cert-ink);outline:2px solid var(--cert-accent);outline-offset:-5px;padding:6mm;display:grid;grid-template-columns:1fr auto;gap:4mm;
  box-shadow:0 12px 40px rgba(35,49,74,.18);}
.lb *{box-sizing:border-box;}
.lb-brand{display:flex;align-items:center;gap:6px;font-weight:700;letter-spacing:.22em;font-size:11px;}
.lb-brand svg{width:16px;height:16px;color:var(--cert-accent);}
.lb-brand .ar{letter-spacing:0;color:var(--cert-body);}
.lb-kind{font-size:8.5px;letter-spacing:.1em;text-transform:uppercase;color:var(--cert-body);margin:2mm 0 1mm;}
.lb-title{font-size:13px;font-weight:700;line-height:1.25;margin:0;}
.lb-title .ar{display:block;font-size:12px;text-align:right;}
.lb-by{font-size:10px;color:var(--cert-body);margin:1mm 0 0;}
.lb-no{margin-top:3mm;font-size:8.5px;color:var(--cert-body);}
.lb-no b{display:block;font-size:13px;letter-spacing:.05em;color:var(--cert-ink);white-space:nowrap;}
.lb-side{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1.5mm;}
.lb-side svg{width:28mm;height:28mm;display:block;}
.lb-side span{font-size:8px;color:var(--cert-body);text-align:center;line-height:1.35;}
.lb-url{grid-column:1 / -1;font-size:8.5px;white-space:nowrap;text-align:center;border-top:1px solid var(--cert-line);padding-top:2mm;align-self:end;}
@page{size:100mm 70mm;margin:0;}
@media print{
  .lb-bar,.lb-hint,.bg-wrap,.overlay{display:none !important;}
  html,body{background:#fff !important;}
  .lb-stage{padding:0;display:block;}
  .lb{box-shadow:none;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
}
</style>
</head>
<body class="label-page">
<div class="lb-bar">
  <div class="acts"><a class="btn btn-ghost btn-sm" href="<?= owCertIsStaff() && !isLoggedIn() ? 'admin.php#certs' : 'artist_studio.php?tab=certs' ?>">← <?= owBi('Back', 'رجوع') ?></a></div>
  <div class="acts"><button type="button" class="btn btn-primary btn-sm" onclick="window.print()"><?= owBi('Print label', 'اطبع الملصق') ?></button><?php owCornerControls(); ?></div>
</div>
<p class="lb-hint"><?= owBi('Print at 100% size (10 × 7 cm) and stick it on the back of the artwork. Anyone can scan the code to check the certificate.', 'اطبعه بالحجم الحقيقي 100% (10 × 7 سم) والصقه على ظهر العمل. يمكن لأي شخص مسح الرمز للتحقق من الشهادة.') ?></p>
<div class="lb-stage">
  <article class="lb" dir="ltr" aria-label="Back label · ملصق الظهر">
    <div>
      <div class="lb-brand"><?= $mark ?><span>OWEILI</span><span class="ar" lang="ar">أويلي</span></div>
      <p class="lb-kind">Certificate of Authenticity · <span lang="ar">شهادة أصالة</span></p>
      <p class="lb-title"><?= e((string) ($s['title_en'] ?? '')) ?><?php if (($s['title_ar'] ?? '') !== '' && $s['title_ar'] !== $s['title_en']): ?><span class="ar" lang="ar" dir="rtl"><?= e((string) $s['title_ar']) ?></span><?php endif; ?></p>
      <p class="lb-by">by <?= e((string) ($s['artist_en'] ?? '')) ?> · <span lang="ar" dir="rtl"><?= e((string) ($s['artist_ar'] ?? '')) ?></span></p>
      <p class="lb-no">Certificate no. · <span lang="ar">رقم الشهادة</span><b><?= e($no) ?></b></p>
    </div>
    <div class="lb-side"><?= $qr ?><span>Scan to verify<br><span lang="ar">امسح للتحقق</span></span></div>
    <div class="lb-url" dir="ltr"><?= e(preg_replace('#^https://#', '', $verify)) ?></div>
  </article>
</div>
<script>
function tgl(){ var ar = document.documentElement.getAttribute('dir') !== 'rtl'; document.documentElement.setAttribute('dir', ar ? 'rtl' : 'ltr'); document.documentElement.lang = ar ? 'ar' : 'en';
  var lb = document.getElementById('lb'); if (lb) lb.textContent = ar ? 'English' : 'العربية'; try { localStorage.setItem('lang', ar ? 'ar' : 'en'); } catch (e) {} }
</script>
</body>
</html>
