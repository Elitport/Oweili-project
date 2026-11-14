<?php
/* =====================================================================
 *  museum_dashboard.php — a partner museum's own dashboard: its tickets,
 *  a QR scanner to mark tickets used, its ticket types and dates, and its
 *  earnings and payouts. Only its own data. Museum accounts are made by the
 *  owner in Admin → Art Map (there is no public museum sign-up).
 *  HIDDEN until the bookings switch is on (booking_lib.php).
 *  Tables: art_places, museum_ticket_types, museum_ticket_dates, museum_tickets, partner_payouts
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/booking_lib.php';
$preview = owBookingGate();
if (!isLoggedIn()) { header('Location: login.php?next=museum_dashboard.php'); exit; }
$me = currentUser();
if (!$me || ($me['role'] ?? '') !== 'museum') { header('Location: ' . dashboardUrl($me['role'] ?? '')); exit; }
$p = owMyMuseum((int) $me['id']);
if (!$p) { http_response_code(404); require __DIR__ . '/404.php'; exit; }
$pid = (int) $p['id']; $db = getDB(); $actor = ['type' => 'museum', 'id' => (int) $me['id']];
$TABS = ['tickets' => ['Tickets', 'التذاكر'], 'scan' => ['Scan a ticket', 'امسح تذكرة'], 'setup' => ['Ticket types and dates', 'أنواع التذاكر والتواريخ'], 'money' => ['Earnings and payouts', 'الأرباح والمستحقات']];
$tab = isset($TABS[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'tickets';
$err = null; $done = (string) ($_GET['done'] ?? '');
owBookingCleanup();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $tab = 'setup';
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) $err = ['The page was open too long. Please try again.', 'انتهت صلاحية الصفحة. حاول مرة أخرى.'];
    else { $err = owMuseumSetupPost($p, $_POST, $actor); if (!$err) { header('Location: museum_dashboard.php?tab=setup&done=1'); exit; } }
}
$day = owFormDate((string) ($_GET['day'] ?? '')) ?: '';
$sql = "SELECT * FROM museum_tickets WHERE place_id = :p AND status IN ('paid','used','cancelled')" . ($day !== '' ? ' AND visit_date = :d' : '') . ' ORDER BY visit_date DESC, id DESC LIMIT 500';
$st = $db->prepare($sql); $st->execute([':p' => $pid] + ($day !== '' ? [':d' => $day] : [])); $tickets = $st->fetchAll();
$payouts = owPayoutsFor('museum', $pid); $tot = owPayoutTotals($payouts);
$today = $db->prepare("SELECT COUNT(*), COALESCE(SUM(people),0), COALESCE(SUM(status='used'),0) FROM museum_tickets WHERE place_id = :p AND visit_date = CURDATE() AND status IN ('paid','used')");
$today->execute([':p' => $pid]); [$tN, $tPeople, $tUsed] = $today->fetch(PDO::FETCH_NUM);
[$rEn, $rAr] = owMuseumRuleBi($p);

owBookTop('My museum', 'متحفي', 'museum_dashboard.php', $preview, '
.bk{max-width:1100px;}
.stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:var(--space-sm);margin-top:var(--space-sm);}
.stat-row .card{margin:0;}
.stat-row b{display:block;font-size:24px;}
.tabs{display:flex;gap:6px;flex-wrap:wrap;margin-top:var(--space-md);}
.tbl{width:100%;border-collapse:collapse;font-size:15px;}
.tbl th,.tbl td{padding:8px;border-bottom:1px solid var(--color-border);text-align:start;vertical-align:top;}
#scan-video{width:100%;max-width:420px;border-radius:12px;background:#000;display:none;}
@media(max-width:700px){.stat-row{grid-template-columns:1fr 1fr;}.tbl thead{display:none;}.tbl td{display:block;border:0;padding:2px 0;}.tbl tr{display:block;border-bottom:1px solid var(--color-border);padding:8px 0;}}
' . owMuseumSetupCss(), true, 'role-institute');
?>
<div class="card">
  <p class="mini" style="margin:0"><?= owBi('My museum', 'متحفي') ?></p>
  <h1><?= owBi($p['name_en'], $p['name_ar']) ?></h1>
  <?php if (!(int) $p['partner']): ?><div class="alert alert-error" role="status"><?= owBi('Ticket sales are not switched on for your museum yet. The Oweili team will let you know.', 'لم يُفعّل بيع التذاكر لمتحفك بعد. سيبلغك فريق أويلي.') ?></div><?php endif; ?>
  <?php if ($done): ?><div class="alert alert-ok" role="status"><?= owBi('Saved.', 'تم الحفظ.') ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-error" role="alert"><?= owBi($err[0], $err[1]) ?></div><?php endif; ?>
</div>
<div class="stat-row">
  <div class="card"><span class="mini"><?= owBi('Visitors today', 'زوار اليوم') ?></span><b><?= (int) $tPeople ?></b></div>
  <div class="card"><span class="mini"><?= owBi('Tickets used today', 'تذاكر استُخدمت اليوم') ?></span><b><?= (int) $tUsed ?> / <?= (int) $tN ?></b></div>
  <div class="card"><span class="mini"><?= owBi('Earned (after commission)', 'المكتسب (بعد العمولة)') ?></span><b><?= owSar($tot['earned']) ?></b></div>
  <div class="card"><span class="mini"><?= owBi('Paid to you', 'دُفع لك') ?></span><b><?= owSar($tot['paid']) ?></b></div>
</div>
<nav class="tabs" aria-label="<?= e('Sections · الأقسام') ?>">
  <?php foreach ($TABS as $k => $w): ?><a class="btn btn-sm <?= $tab === $k ? 'btn-dark' : 'btn-secondary' ?>" href="museum_dashboard.php?tab=<?= $k ?>"<?= $tab === $k ? ' aria-current="page"' : '' ?>><?= owBi($w[0], $w[1]) ?></a><?php endforeach; ?>
</nav>

<?php if ($tab === 'tickets'): ?>
  <form class="card acts" method="get" action="museum_dashboard.php" style="margin-top:var(--space-sm)"><input type="hidden" name="tab" value="tickets">
    <div class="field" style="margin:0"><label for="f-day"><?= owBi('Visit date', 'تاريخ الزيارة') ?></label><input id="f-day" type="date" name="day" value="<?= e($day) ?>"></div>
    <button class="btn btn-secondary btn-sm"><?= owBi('Show', 'عرض') ?></button><a class="btn btn-ghost btn-sm" href="museum_dashboard.php?tab=tickets&day=<?= date('Y-m-d') ?>"><?= owBi('Today', 'اليوم') ?></a></form>
  <div class="card">
    <?php if (!$tickets): ?><p><?= owBi('No tickets yet.', 'لا تذاكر بعد.') ?></p><?php else: ?>
    <table class="tbl"><thead><tr><th><?= owBi('Ticket', 'التذكرة') ?></th><th><?= owBi('Visit date', 'تاريخ الزيارة') ?></th><th><?= owBi('People', 'الأشخاص') ?></th><th><?= owBi('Status', 'الحالة') ?></th></tr></thead><tbody>
    <?php foreach ($tickets as $t): [$lEn, $lAr] = owTicketLinesBi($t); [$de, $da] = owDateRangeBi($t['visit_date'], null); ?>
      <tr><td><b dir="ltr"><?= e($t['ref_no']) ?></b><br><?= e($t['buyer_name']) ?></td><td><?= owBi($de, $da) ?></td><td><?= owBi($lEn, $lAr) ?></td>
        <td><?= owPill(OW_TICKET_STATUS, (string) $t['status']) ?><?php if ($t['status'] === 'paid'): ?> <a class="btn btn-ghost btn-sm" href="ticket_check.php?k=<?= e($t['qr_token']) ?>"><?= owBi('Check in', 'تسجيل الدخول') ?></a><?php endif; ?></td></tr>
    <?php endforeach; ?></tbody></table><?php endif; ?>
  </div>

<?php elseif ($tab === 'scan'): ?>
  <div class="card">
    <h2><?= owBi('Scan a ticket', 'امسح تذكرة') ?></h2>
    <p><?= owBi('Point the camera at the visitor’s QR code. You can also scan it with your phone’s own camera, or type the ticket number.', 'وجّه الكاميرا إلى رمز QR لدى الزائر. ويمكنك أيضاً مسحه بكاميرا جوالك، أو كتابة رقم التذكرة.') ?></p>
    <button class="btn btn-primary" id="scan-start" type="button"><?= owBi('Open the camera', 'افتح الكاميرا') ?></button>
    <p class="mini" id="scan-msg" role="status"></p>
    <video id="scan-video" playsinline muted></video>
    <form method="get" action="ticket_check.php" class="acts" style="margin-top:var(--space-md)">
      <div class="field" style="margin:0"><label for="s-code"><?= owBi('Ticket number', 'رقم التذكرة') ?></label><input id="s-code" name="code" dir="ltr" maxlength="20" placeholder="T-26-XXXXXX" autocapitalize="characters"></div>
      <button class="btn btn-secondary"><?= owBi('Check', 'افحص') ?></button></form>
  </div>
  <script>
  (function(){
    var b = document.getElementById('scan-start'), v = document.getElementById('scan-video'), m = document.getElementById('scan-msg'), ar = document.documentElement.dir === 'rtl';
    b.addEventListener('click', async function(){
      if (!('BarcodeDetector' in window)) { m.textContent = ar ? 'هذا المتصفح لا يقرأ رمز QR. استخدم كاميرا جوالك أو اكتب رقم التذكرة.' : 'This browser cannot read QR codes. Use your phone’s camera or type the ticket number.'; return; }
      try {
        var d = new BarcodeDetector({formats: ['qr_code']});
        var s = await navigator.mediaDevices.getUserMedia({video: {facingMode: 'environment'}});
        v.srcObject = s; v.style.display = 'block'; await v.play(); b.disabled = true;
        (async function loop(){
          try { var r = await d.detect(v); if (r.length) { var u = r[0].rawValue || ''; var k = (u.match(/[?&]k=([a-f0-9]{40})/) || [])[1];
            if (k) { s.getTracks().forEach(function(t){ t.stop(); }); location.href = 'ticket_check.php?k=' + k; return; } } } catch (e) {}
          requestAnimationFrame(loop); })();
      } catch (e) { m.textContent = ar ? 'تعذّر فتح الكاميرا.' : 'The camera could not be opened.'; }
    });
  })();
  </script>

<?php elseif ($tab === 'setup'): ?>
  <?= owMuseumSetupHtml($p, 'museum_dashboard.php?tab=setup') ?>

<?php else: ?>
  <div class="card">
    <h2><?= owBi('How you are paid', 'كيف تُدفع مستحقاتك') ?></h2>
    <p><?= owBi('For each ticket Oweili keeps ' . rtrim(rtrim(number_format((float) $p['ticket_commission'], 2), '0'), '.') . '% (before VAT). ' . $rEn, 'يحتفظ أويلي بـ ' . rtrim(rtrim(number_format((float) $p['ticket_commission'], 2), '0'), '.') . '٪ من كل تذكرة (قبل الضريبة). ' . $rAr) ?></p>
    <div class="stat-row">
      <div class="card"><span class="mini"><?= owBi('Earned', 'المكتسب') ?></span><b><?= owSar($tot['earned']) ?></b></div>
      <div class="card"><span class="mini"><?= owBi('Due now', 'مستحق الآن') ?></span><b><?= owSar($tot['due']) ?></b></div>
      <div class="card"><span class="mini"><?= owBi('Not due yet', 'لم يستحق بعد') ?></span><b><?= owSar($tot['waiting']) ?></b></div>
      <div class="card"><span class="mini"><?= owBi('Paid to you', 'دُفع لك') ?></span><b><?= owSar($tot['paid']) ?></b></div>
    </div>
  </div>
  <div class="card">
    <?php if (!$payouts): ?><p><?= owBi('Nothing yet.', 'لا شيء بعد.') ?></p><?php else: ?>
    <table class="tbl"><thead><tr><th><?= owBi('Ticket', 'التذكرة') ?></th><th><?= owBi('Your share', 'حصتك') ?></th><th><?= owBi('When', 'متى') ?></th><th><?= owBi('Status', 'الحالة') ?></th></tr></thead><tbody>
    <?php $tById = []; foreach ($tickets as $t) $tById[(int) $t['id']] = $t;
      foreach ($payouts as $po): $t = $tById[(int) $po['source_id']] ?? null; [$we, $wa] = owPayoutDueBi($po['due_on']); ?>
      <tr><td dir="ltr"><?= $t ? e($t['ref_no']) : '#' . (int) $po['source_id'] ?></td><td><?= owSar((float) $po['amount']) ?></td>
        <td><?= $po['status'] === 'paid' ? owBi('Paid ' . substr((string) $po['paid_at'], 0, 10), 'دُفع ' . substr((string) $po['paid_at'], 0, 10)) : owBi($we, $wa) ?></td>
        <td><?= owPill(OW_PAYOUT_STATUS, (string) $po['status']) ?></td></tr>
    <?php endforeach; ?></tbody></table><?php endif; ?>
  </div>
<?php endif; ?>
<?php owBookBottom();
