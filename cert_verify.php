<?php
/* =====================================================================
 *  cert_verify.php — the public verification page: oweili.com/verify
 *  (.htaccess sends /verify here; the QR code on every certificate opens
 *  /verify?no=OW-…). Anyone can type a certificate number and see whether
 *  it is real: the artwork photo and details, the artist, the issue date.
 *  A cancelled certificate shows "Cancelled". An unknown or fake number
 *  shows a clear "not found". Buyer details are never shown.
 *  At most 30 checks a minute from one visitor (stops number guessing).
 *  Tables: certificates, artworks (read), activity_log (the check count)
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/page_cache.php'; owPublicPage();   // public page: Back opens at once, early load on touch (page_cache.php)
require_once __DIR__ . '/cert_lib.php';
require_once __DIR__ . '/seo_lib.php';

$raw   = trim((string) ($_GET['no'] ?? ''));
$asked = $raw !== '';
$state = '';            // valid | cancelled | notfound | busy | (empty: nothing asked yet)
$cert  = null;
if ($asked) {
    if (owRecentCount('cert_check', 60) >= 30) {
        $state = 'busy';
    } else {
        owLog('cert_check', null, null, '', ['type' => 'guest', 'id' => null]);
        $cert  = owCertByNo($raw);
        $state = $cert ? ($cert['status'] === 'valid' ? 'valid' : 'cancelled') : 'notfound';
    }
}
$s = $cert['snap'] ?? [];
$typed = mb_substr(preg_replace('/[^A-Za-z0-9\- ]/', '', $raw), 0, 30);
$live = false;
if ($state === 'valid') {   // link to the artwork page while it is in the Gallery
    $st = getDB()->prepare("SELECT 1 FROM artworks a JOIN users u ON u.id = a.artist_id WHERE a.id = :id AND a.is_approved = 1
                            AND a.status IN ('available','auction') AND u.is_banned = 0" . owNotDraftSql('a'));
    $st->execute([':id' => (int) $cert['artwork_id']]);
    $live = (bool) $st->fetchColumn();
}
/* Ownership (owner, 9 Nov 2026): the public page never shows the buyer's name, only that it is sold and how many owners it has had */
$sold = false; $owners = 0;
if ($state === 'valid') {
    $owners = owCertOwnerCount($cert);
    $st = getDB()->prepare("SELECT status FROM artworks WHERE id = :id");
    $st->execute([':id' => (int) $cert['artwork_id']]);
    $sold = $owners > 0 || $st->fetchColumn() === 'sold';
}
if (!headers_sent()) header('Cache-Control: no-store');
?>
<!DOCTYPE html>
<html <?php require_once __DIR__ . '/lang_lib.php'; owHtmlAttrs(); ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<base href="<?= e(owBase()) ?>">
<?php owSeo(['en' => 'Verify a Certificate of Authenticity', 'ar' => 'تحقق من شهادة الأصالة',
             'desc_en' => 'Type the number on an Oweili certificate, or scan its QR code, to see if it is real.',
             'desc_ar' => 'اكتب رقم شهادة أويلي أو امسح رمزها لتعرف إن كانت حقيقية.', 'path' => 'verify', 'noindex' => $asked]); ?>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
.vf{position:relative;z-index:10;max-width:860px;margin:0 auto;padding:calc(var(--nav-h) + var(--space-xl)) var(--space-sm) var(--space-2xl);}
.vf h1{font-size:var(--fs-section);margin:0 0 var(--space-xs);}
.vf .lead{color:var(--color-body);margin:0 0 var(--space-md);max-width:600px;}
.vf-form{display:flex;gap:var(--space-xs);flex-wrap:wrap;align-items:flex-end;}
.vf-form .field{flex:1;min-width:220px;margin:0;}
.vf-form input{letter-spacing:.06em;text-transform:uppercase;font-weight:600;}
.vf-res{margin-top:var(--space-lg);}
.vf-state{display:flex;align-items:center;gap:12px;padding:var(--space-sm) var(--space-md);border-radius:var(--radius-card) var(--radius-card) 0 0;font-weight:700;font-size:18px;}
.vf-state svg{width:30px;height:30px;flex-shrink:0;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}
.vf-state small{display:block;font-size:var(--fs-caption);font-weight:500;color:var(--color-body);}
.vf-state.ok{background:var(--color-dark);color:var(--color-on-dark);}
.vf-state.ok small{color:var(--color-on-dark);opacity:.85;}
.vf-state.bad{background:var(--color-tint-pink);color:var(--color-dark);border:1px solid rgba(var(--rgb-primary),.45);border-bottom:0;}
.vf-card{border:1px solid var(--color-border);border-top:0;border-radius:0 0 var(--radius-card) var(--radius-card);background:var(--color-surface);padding:var(--space-md);
  display:grid;grid-template-columns:260px 1fr;gap:var(--space-md);}
.vf-card.void{opacity:.75;}
.vf-pic{border:1px solid var(--color-border);border-radius:12px;background:var(--color-frame);aspect-ratio:4/3;display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative;}
.vf-pic img{width:100%;height:100%;object-fit:contain;display:block;}
.vf-pic img[hidden]{display:none;}
.vf-card h2{font-size:22px;margin:0 0 2px;}
.vf-card .by{margin:0 0 var(--space-sm);color:var(--color-body);}
.vf-dl{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px var(--space-sm);margin:0 0 var(--space-sm);}
.vf-dl dt{font-size:var(--fs-caption);font-weight:700;color:var(--color-body);}
.vf-dl dd{margin:0;color:var(--color-dark);font-weight:600;overflow-wrap:anywhere;}
.vf-acts{display:flex;gap:var(--space-xs);flex-wrap:wrap;margin-top:var(--space-sm);}
.vf-none{border:1px solid rgba(var(--rgb-primary),.45);border-top:0;border-radius:0 0 var(--radius-card) var(--radius-card);background:var(--color-surface);padding:var(--space-md);color:var(--color-body);}
.vf-how{margin-top:var(--space-xl);}
.vf-how h2{font-size:20px;margin:0 0 var(--space-xs);}
.vf-how ul{margin:0;padding-inline-start:20px;color:var(--color-body);line-height:1.8;}
@media(max-width:640px){.vf-card{grid-template-columns:1fr;}.vf-dl{grid-template-columns:1fr 1fr;}}
</style>
</head>
<body>
<div class="bg-wrap"></div>
<div class="overlay"></div>
<?php include __DIR__ . '/nav.php'; ?>
<main class="vf">
  <h1><?= owBi('Verify a certificate', 'تحقق من شهادة') ?></h1>
  <p class="lead"><?= owBi('Every artwork approved on Oweili has a Certificate of Authenticity. Type its number, or scan the QR code on it.', 'لكل عمل معتمد في أويلي شهادة أصالة. اكتب رقمها، أو امسح رمز QR الموجود عليها.') ?></p>
  <form class="vf-form" method="get" action="verify" role="search">
    <div class="field">
      <label for="vf-no"><?= owBi('Certificate number', 'رقم الشهادة') ?></label>
      <input id="vf-no" name="no" type="text" dir="ltr" maxlength="30" autocomplete="off" autocapitalize="characters" spellcheck="false" required
             placeholder="OW-2026-XXXX-XXXX" value="<?= e($typed) ?>">
    </div>
    <button class="btn btn-primary" type="submit"><?= owBi('Verify', 'تحقّق') ?></button>
  </form>

  <?php if ($state !== ''): ?>
  <section class="vf-res" aria-live="polite">
    <?php if ($state === 'valid' || $state === 'cancelled'):
      $img = owImgSrc((string) ($s['image'] ?? '')); $img8 = $img !== '' ? owImgSized($img, 800) : '';
      [$iE, $iA] = owDateBi((string) $cert['issued_at']);
      [$szE, $szA] = owCertSize($s['width_cm'] ?? 0, $s['height_cm'] ?? 0);
      [$edE, $edA] = owCertEdition($s['edition_type'] ?? 'original', $s['edition_number'] ?? null, $s['edition_size'] ?? null); ?>
      <?php if ($state === 'valid'): ?>
        <div class="vf-state ok"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M7.5 12.3l3 3 6-6.3"/></svg>
          <div><?= owBi('This certificate is real', 'هذه الشهادة حقيقية') ?><small dir="ltr"><?= e($cert['cert_no']) ?></small></div></div>
      <?php else: [$cE, $cA] = owDateBi((string) $cert['cancelled_at']); ?>
        <div class="vf-state bad"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M8 8l8 8M16 8l-8 8"/></svg>
          <div><?= owBi('Cancelled: this certificate is no longer valid', 'ملغاة: هذه الشهادة لم تعد سارية') ?><small><?= owBi('Cancelled by Oweili on ' . $cE . ' · ', 'ألغتها أويلي بتاريخ ' . $cA . ' · ') ?><span dir="ltr"><?= e($cert['cert_no']) ?></span></small></div></div>
      <?php endif; ?>
      <div class="vf-card<?= $state === 'cancelled' ? ' void' : '' ?>">
        <div class="vf-pic"><?php if ($img8 !== ''): ?><img src="<?= e($img8) ?>" alt="<?= e(($s['title_en'] ?? '') . ' · ' . ($s['title_ar'] ?? '')) ?>"<?= owImgFallbackAttrs($img, $img8) ?>><?= owImgPlaceholder(true) ?><?php else: ?><?= owImgPlaceholder(false) ?><?php endif; ?></div>
        <div>
          <h2><?= owBi((string) ($s['title_en'] ?? ''), (string) ($s['title_ar'] ?? '')) ?></h2>
          <p class="by"><?= owBi('by ' . ($s['artist_en'] ?? ''), 'بواسطة ' . ($s['artist_ar'] ?? '')) ?></p>
          <dl class="vf-dl">
            <div><dt><?= owBi('Medium', 'الخامة') ?></dt><dd dir="auto"><?= e((string) ($s['medium'] ?? '')) ?></dd></div>
            <div><dt><?= owBi('Size', 'المقاس') ?></dt><dd><?= owBi($szE, $szA) ?></dd></div>
            <div><dt><?= owBi('Year', 'السنة') ?></dt><dd><?= (int) ($s['year_made'] ?? 0) ?></dd></div>
            <div><dt><?= owBi('Type', 'النوع') ?></dt><dd><?= owBi($edE, $edA) ?></dd></div>
            <div><dt><?= owBi('Issued by Oweili on', 'أصدرتها أويلي بتاريخ') ?></dt><dd><?= owBi($iE, $iA) ?></dd></div>
            <div><dt><?= owBi('Artist’s declaration', 'إقرار الفنان') ?></dt><dd><?= owBi('Made by the artist’s own hand', 'من صنع يد الفنان') ?></dd></div>
            <?php if ($sold): ?><div><dt><?= owBi('Ownership', 'الملكية') ?></dt><dd><?= owBi('Sold · owned by a verified collector', 'مباع · يملكه مقتنٍ موثّق') ?><?php if ($owners > 1): ?><br><span class="mini"><?= owBi('Owners so far: ' . $owners, 'عدد المالكين حتى الآن: ' . $owners) ?></span><?php endif; ?></dd></div><?php endif; ?>
          </dl>
          <?php if (!empty($s['real_touch'])): ?><span class="human-badge"><?= owBi('✦ A Real Touch', '✦ لمسة حقيقية') ?></span><?php endif; ?>
          <p class="rights-note"><?= owBi(OW_CERT_RIGHTS[0], OW_CERT_RIGHTS[1]) ?></p>
          <?php if ($state === 'valid'): ?><div class="vf-acts">
            <?php if ($live): ?><a class="btn btn-secondary btn-sm" href="artwork.php?id=<?= (int) $cert['artwork_id'] ?>"><?= owBi('See the artwork', 'شاهد العمل') ?></a><?php endif; ?>
            <a class="btn btn-ghost btn-sm" href="artist_dashboard.php?id=<?= (int) $cert['artist_id'] ?>"><?= owBi('Meet the artist', 'تعرّف على الفنان') ?></a>
          </div><?php endif; ?>
        </div>
      </div>
    <?php elseif ($state === 'busy'): ?>
      <div class="vf-state bad"><?= owBi('Too many checks in a short time', 'عمليات تحقق كثيرة في وقت قصير') ?></div>
      <div class="vf-none"><?= owBi('Please wait a minute, then try again.', 'يرجى الانتظار دقيقة ثم المحاولة مرة أخرى.') ?></div>
    <?php else: ?>
      <div class="vf-state bad"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 7.5v6M12 16.5v.01"/></svg>
        <div><?= owBi('Certificate not found', 'لم نجد هذه الشهادة') ?><small dir="ltr"><?= e($typed) ?></small></div></div>
      <div class="vf-none"><?= owBi('No Oweili certificate has this number. Please check the number and try again. If it is right, this certificate was not issued by Oweili.', 'لا توجد شهادة من أويلي بهذا الرقم. تأكد من الرقم وحاول مرة أخرى. إذا كان الرقم صحيحاً، فهذه الشهادة لم تصدرها أويلي.') ?></div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <section class="vf-how card">
    <h2><?= owBi('How it works', 'كيف يعمل التحقق') ?></h2>
    <ul>
      <li><?= owBi('The artist confirms each work is their own, made by hand.', 'يؤكد الفنان أن كل عمل من إبداعه ومن صنع يده.') ?></li>
      <li><?= owBi('Our team reviews the work and its creation photos.', 'يراجع فريقنا العمل وصور مراحل إنجازه.') ?></li>
      <li><?= owBi('Once approved, Oweili issues a certificate with its own number.', 'بعد الموافقة تصدر أويلي شهادة برقم خاص بها.') ?></li>
      <li><?= owBi('The buyer’s name is never shown here.', 'لا يظهر اسم المشتري هنا أبداً.') ?></li>
    </ul>
  </section>
</main>
<script>
function tgl(){ var ar = document.documentElement.getAttribute('dir') !== 'rtl';
  document.documentElement.setAttribute('dir', ar ? 'rtl' : 'ltr'); document.documentElement.setAttribute('lang', ar ? 'ar' : 'en'); }
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
