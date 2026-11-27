<?php
/* =====================================================================
 *  workshops.php — Artist workshops («ورش الفنانين»), the list of approved
 *  workshops visitors can book (owner, 25 Nov 2026; workshop_lib.php).
 *  Hidden behind the switch "Artist workshops": 404 for visitors while it
 *  is off, a preview for a signed-in admin. Cards like the Gallery grid.
 *  Tables: artist_workshops, workshop_bookings, users
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/workshop_lib.php';
require_once __DIR__ . '/artwork_lib.php';
require_once __DIR__ . '/article_lib.php';
$preview = owHiddenPageGate('artist_workshops');
header('Cache-Control: no-store');
$list = owWsPublicList();
owBookTop('Artist workshops', 'ورش الفنانين', 'workshops.php', $preview,
  '.bk{max-width:1100px}.ws-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-top:var(--space-sm)}
   .ws-card{display:flex;flex-direction:column;gap:6px;padding:16px;text-decoration:none;color:inherit}.ws-card h2{font-size:15px;margin:0;line-height:1.5}
   .ws-card .p{font-size:16px;font-weight:700;color:var(--color-dark);margin-top:auto}
   @media(min-width:641px){.ws-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:28px}}@media(min-width:1025px){.ws-grid{grid-template-columns:repeat(4,minmax(0,1fr))}}', !owWsOn(), 'role-artist');
?>
<div class="card"><h1><?= owBi('Artist workshops', 'ورش الفنانين') ?></h1>
  <p class="mini"><?= owBi('Learn by hand from Oweili artists. Every workshop is checked by the Oweili team', 'تعلّم بيدك من فناني أويلي. يراجع فريق أويلي كل ورشة') ?></p></div>
<?php if (!$list): ?>
  <div class="card"><p><?= owBi('No workshops right now. New ones will appear here', 'لا ورش حاليا. ستظهر الورش الجديدة هنا') ?></p></div>
<?php else: ?>
<div class="ws-grid">
  <?php foreach ($list as $w): [$dEn, $dAr] = owDateRangeBi($w['start_date'], $w['end_date']); $left = owWsSeatsLeft($w); ?>
  <a class="card ws-card" href="workshop.php?id=<?= (int) $w['id'] ?>">
    <span class="mini"><?= owBiName((string) $w['artist_en'], (string) $w['artist_ar']) ?></span>
    <h2><?= owBi((string) $w['title_en'], (string) $w['title_ar']) ?></h2>
    <span class="mini"><?= owBi($dEn, $dAr) ?> · <?= $w['mode'] === 'online' ? owBi('Online', 'عن بعد') : e((string) $w['city']) ?></span>
    <span class="mini"><?= $left > 0 ? owBi($left . ' seats left', 'متبق ' . $left . ' مقاعد') : owBi('Full', 'اكتمل العدد') ?></span>
    <?php require_once __DIR__ . '/vat_lib.php'; ?><span class="p"><?= (float) $w['price'] > 0 ? owSar(owGross((float) $w['price'])) . owVatTag() : owBi('Free', 'مجانية') ?></span>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php owBookBottom();
