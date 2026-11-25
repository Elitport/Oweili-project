<?php
/* =====================================================================
 *  admin_workshops.php — Artist workshops for the owner (admin only),
 *  owner, 25 Nov 2026 (workshop_lib.php). Works while the switch
 *  "Artist workshops" is off too, so the owner can prepare and check.
 *    #requests   artists asking to offer workshops: the conditions with a
 *                tick each, their experience and plan; Approve or Not
 *                approved (a reason is required; the artist gets an email)
 *    #workshops  workshops waiting for approval, and the approved ones
 *                (Hide / Show again)
 *    #bookings   every booking with the full student details (admin only):
 *                Mark as paid (makes the artist's payout row: the price minus
 *                the course commission) and Cancel (gives the seat back)
 *  Tables: artist_workshop_requests, artist_workshops, workshop_bookings,
 *          payouts, users, activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/admin_shell.php';
require_once __DIR__ . '/workshop_lib.php';
require_once __DIR__ . '/artwork_lib.php';
owAdminGate();
header('Cache-Control: no-store');
$ready = owWsReady();
$msg = null;

if ($ready && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $do = (string) ($_POST['do'] ?? ''); $id = (int) ($_POST['id'] ?? 0); $reason = (string) ($_POST['reason'] ?? '');
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) $msg = ['bad', 'This page was open for a long time, so nothing was changed. Please try again.', 'كانت هذه الصفحة مفتوحة لوقت طويل، لذلك لم يتغير شيء. حاول مرة أخرى.'];
    else {
        $okd = false; $back = '';
        if ($do === 'req_approve') { $okd = owWsDecideRequest($id, true, $reason); $back = 'requests'; }
        elseif ($do === 'req_refuse') { $okd = owWsDecideRequest($id, false, $reason); $back = 'requests'; if (!$okd && trim($reason) === '') $msg = ['bad', 'Please write the reason', 'يرجى كتابة السبب']; }
        elseif (in_array($do, ['ws_approve', 'ws_refuse', 'ws_hide', 'ws_show'], true)) { $okd = owWsDecide($id, substr($do, 3), $reason); $back = 'workshops'; if (!$okd && $do === 'ws_refuse' && trim($reason) === '') $msg = ['bad', 'Please write the reason', 'يرجى كتابة السبب']; }
        elseif ($do === 'bk_paid') { $okd = owWsMarkPaid($id, (string) ($_POST['pay_ref'] ?? '')); $back = 'bookings'; }
        elseif ($do === 'bk_cancel') { $okd = owWsCancelBooking($id); $back = 'bookings'; }
        if ($okd) { header('Location: admin_workshops.php?done=1#' . $back); exit; }
        $msg = $msg ?? ['bad', 'Nothing was changed. It may already be decided.', 'لم يتغير شيء. ربما اتُّخذ القرار من قبل.'];
    }
}
if (isset($_GET['done']) && !$msg) $msg = ['', 'Saved', 'تم الحفظ'];

$reqs = $wss = $bks = [];
if ($ready) {
    $db = getDB();
    $reqs = $db->query("SELECT r.*, u.email, u.is_approved, u.is_banned, u.is_verified, u.created_at AS joined, u.id AS uid, u.role, " . owArtistNameSql('u') . "
                        FROM artist_workshop_requests r JOIN users u ON u.id = r.artist_id ORDER BY (r.status = 'pending') DESC, r.id DESC LIMIT 200")->fetchAll();
    $wss = $db->query("SELECT w.*, " . owArtistNameSql('u') . ", (SELECT COUNT(*) FROM workshop_bookings b WHERE b.workshop_id = w.id AND b.status <> 'cancelled') AS booked
                       FROM artist_workshops w JOIN users u ON u.id = w.artist_id ORDER BY (w.status = 'pending') DESC, w.start_date DESC LIMIT 300")->fetchAll();
    $bks = $db->query("SELECT b.*, w.title_en, w.title_ar, w.artist_id FROM workshop_bookings b JOIN artist_workshops w ON w.id = b.workshop_id ORDER BY (b.status = 'booked') DESC, b.id DESC LIMIT 500")->fetchAll();
}
$nReq = count(array_filter($reqs, fn($r) => $r['status'] === 'pending'));
$nWs = count(array_filter($wss, fn($w) => $w['status'] === 'pending'));
$nBk = count(array_filter($bks, fn($b) => $b['status'] === 'booked'));
$date = function (?string $d): string { [$en, $ar] = owDateBi($d); return owBi($en, $ar); };
$sw = owWsOn();

owAdminTop('Artist workshops', 'ورش الفنانين', '.ws-ch{list-style:none;margin:0 0 var(--space-sm);padding:0;font-size:14px}.ws-ch li{margin:2px 0}.ws-ch .n{color:var(--color-primary-dark);font-weight:700}.txt{white-space:pre-wrap;font-size:15px;line-height:1.8;margin:0 0 var(--space-sm)}.rs{min-width:220px}');
?>
  <h1><?= owBi('Artist workshops', 'ورش الفنانين') ?></h1>
  <p class="mini"><?= $sw ? owBi('The switch is ON: visitors can see approved workshops and book', 'المفتاح مُشغَّل: يرى الزوار الورش المعتمدة ويمكنهم الحجز')
      : owBi('The switch is OFF: artists, visitors and Google see nothing of this. Turn it on in Admin → Settings → Hidden for now', 'المفتاح مُطفأ: لا يرى الفنانون ولا الزوار ولا جوجل شيئا من هذا. شغّله من الإدارة ← الإعدادات ← مخفي حاليا') ?>
    · <?= owBi('Oweili keeps ' . owCommissionText(owWsCommission()) . ' of each seat (the course commission setting)', 'يحتفظ أويلي بـ' . owCommissionText(owWsCommission()) . ' من كل مقعد (إعداد عمولة الدورات)') ?></p>
  <?php if (!$ready): ?><div class="card"><p><?= owBi('The workshop tables are not in the database yet (artist_workshops.sql)', 'جداول الورش ليست في قاعدة البيانات بعد (artist_workshops.sql)') ?></p></div><?php endif; ?>
  <?php if ($msg): ?><div class="alert <?= $msg[0] === 'bad' ? 'alert-error' : 'alert-ok' ?>" role="status"><?= owBi($msg[1], $msg[2]) ?></div><?php endif; ?>
  <p class="acts"><a class="btn btn-secondary btn-sm" href="#requests"><?= owBi('Requests', 'الطلبات') ?> (<?= $nReq ?>)</a> <a class="btn btn-secondary btn-sm" href="#workshops"><?= owBi('Workshops waiting', 'ورش بانتظار الموافقة') ?> (<?= $nWs ?>)</a> <a class="btn btn-secondary btn-sm" href="#bookings"><?= owBi('Bookings waiting for payment', 'حجوزات بانتظار الدفع') ?> (<?= $nBk ?>)</a></p>

  <h2 id="requests"><?= owBi('Requests to offer workshops', 'طلبات تقديم ورش العمل') ?></h2>
  <?php if (!$reqs): ?><div class="card"><p class="mini"><?= owBi('No requests yet', 'لا طلبات بعد') ?></p></div><?php endif; ?>
  <?php foreach ($reqs as $r): $u = ['id' => (int) $r['uid'], 'is_approved' => $r['is_approved'], 'is_banned' => $r['is_banned'], 'is_verified' => $r['is_verified'], 'created_at' => $r['joined']]; ?>
  <div class="card">
    <p><b><bdi><?= e($r['artist_en']) ?></bdi></b> · <a href="admin.php?person=<?= (int) $r['uid'] ?>"><?= owBi('Open the person', 'افتح الشخص') ?></a> · <a href="artist_dashboard.php?id=<?= (int) $r['uid'] ?>"><?= owBi('Public page', 'الصفحة العامة') ?></a>
      · <span class="pillx <?= $r['status'] === 'approved' ? 'on' : ($r['status'] === 'pending' ? 'warn' : '') ?>"><?= owBi(OW_WS_STATUS[$r['status']][0] ?? $r['status'], OW_WS_STATUS[$r['status']][1] ?? $r['status']) ?></span> <span class="mini"><?= $date($r['created_at']) ?></span></p>
    <ul class="ws-ch"><?php foreach (owWsChecks($u) as [$k, $en, $ar, $ok, $dEn, $dAr]): ?><li class="<?= $ok ? '' : 'n' ?>"><?= $ok ? '✓' : '✗' ?> <?= owBi($en, $ar) ?><?= $dEn !== '' ? ' · ' . owBi($dEn, $dAr) : '' ?></li><?php endforeach; ?></ul>
    <dl class="kv"><dt><?= owBi('Would teach', 'سيعلّم') ?></dt><dd><?= e($r['subjects']) ?></dd>
      <dt><?= owBi('Where', 'أين') ?></dt><dd><?= owBi(OW_WS_REQ_MODES[$r['mode']][0] ?? '', OW_WS_REQ_MODES[$r['mode']][1] ?? '') ?><?= $r['city'] !== '' ? ' · ' . e($r['city']) : '' ?></dd></dl>
    <details><summary><?= owBi('Teaching experience and first workshop plan', 'الخبرة في التدريس وخطة الورشة الأولى') ?></summary>
      <p class="mini"><?= owBi('Experience', 'الخبرة') ?></p><p class="txt"><?= e((string) $r['experience']) ?></p>
      <p class="mini"><?= owBi('First workshop plan', 'خطة الورشة الأولى') ?></p><p class="txt"><?= e((string) $r['plan']) ?></p></details>
    <?php if ($r['status'] === 'pending'): ?>
    <div class="acts">
      <form method="post" action="admin_workshops.php#requests"><?= csrfField() ?><input type="hidden" name="do" value="req_approve"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn btn-primary btn-sm" type="submit"><?= owBi('Approve', 'موافقة') ?></button></form>
      <form method="post" action="admin_workshops.php#requests" class="acts"><?= csrfField() ?><input type="hidden" name="do" value="req_refuse"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
        <input class="rs" name="reason" maxlength="500" required placeholder="<?= e(owIsAr() ? 'السبب (يصل إلى الفنان)' : 'Reason (sent to the artist)') ?>" aria-label="<?= e(owIsAr() ? 'السبب' : 'Reason') ?>"><button class="btn btn-danger btn-sm" type="submit"><?= owBi('Not approved', 'عدم الموافقة') ?></button></form>
    </div>
    <?php elseif ($r['reason'] !== ''): ?><p class="mini"><?= owBi('Reason: ', 'السبب: ') ?><?= e($r['reason']) ?></p><?php endif; ?>
  </div>
  <?php endforeach; ?>

  <h2 id="workshops"><?= owBi('Workshops', 'الورش') ?></h2>
  <?php if (!$wss): ?><div class="card"><p class="mini"><?= owBi('No workshops yet', 'لا ورش بعد') ?></p></div><?php endif; ?>
  <?php foreach ($wss as $w): $st = OW_WS_STATUS[$w['status']] ?? ['', '']; ?>
  <div class="card">
    <p><b><?= owBi((string) $w['title_en'], (string) $w['title_ar']) ?></b> · <bdi><?= e($w['artist_en']) ?></bdi> · <span class="pillx <?= $w['status'] === 'approved' ? 'on' : ($w['status'] === 'pending' ? 'warn' : '') ?>"><?= owBi($st[0], $st[1]) ?></span></p>
    <dl class="kv">
      <dt><?= owBi('Dates', 'التواريخ') ?></dt><dd><?= $date($w['start_date']) ?> → <?= $date($w['end_date']) ?> · <?= owBi((string) $w['schedule_en'], (string) $w['schedule_ar']) ?> · <?= e(rtrim(rtrim((string) $w['hours'], '0'), '.')) ?> <?= owBi('hours', 'ساعة') ?></dd>
      <dt><?= owBi('Where', 'أين') ?></dt><dd><?= $w['mode'] === 'online' ? owBi('Online', 'عن بعد') : e((string) $w['city']) ?> · <?= owBi(OW_WS_LEVELS[$w['level']][0] ?? '', OW_WS_LEVELS[$w['level']][1] ?? '') ?> · <?= (int) $w['materials'] ? owBi('materials included', 'الأدوات مشمولة') : owBi('materials not included', 'الأدوات غير مشمولة') ?></dd>
      <dt><?= owBi('Price and seats', 'السعر والمقاعد') ?></dt><dd><?= owSar((float) $w['price']) ?> · <?= (int) $w['booked'] ?> / <?= (int) $w['seats'] ?> <?= owBi('booked', 'محجوز') ?></dd>
    </dl>
    <details><summary><?= owBi('What students will learn', 'ما سيتعلمه المتدربون') ?></summary><p class="txt"><?= owBi((string) $w['desc_en'], (string) $w['desc_ar']) ?></p></details>
    <div class="acts">
      <?php if ($w['status'] === 'pending'): ?>
        <form method="post" action="admin_workshops.php#workshops"><?= csrfField() ?><input type="hidden" name="do" value="ws_approve"><input type="hidden" name="id" value="<?= (int) $w['id'] ?>"><button class="btn btn-primary btn-sm" type="submit"><?= owBi('Approve', 'موافقة') ?></button></form>
        <form method="post" action="admin_workshops.php#workshops" class="acts"><?= csrfField() ?><input type="hidden" name="do" value="ws_refuse"><input type="hidden" name="id" value="<?= (int) $w['id'] ?>">
          <input class="rs" name="reason" maxlength="500" required placeholder="<?= e(owIsAr() ? 'السبب (يصل إلى الفنان)' : 'Reason (sent to the artist)') ?>" aria-label="<?= e(owIsAr() ? 'السبب' : 'Reason') ?>"><button class="btn btn-danger btn-sm" type="submit"><?= owBi('Not approved', 'عدم الموافقة') ?></button></form>
      <?php elseif ($w['status'] === 'approved'): ?>
        <a class="btn btn-ghost btn-sm" href="workshop.php?id=<?= (int) $w['id'] ?>"><?= owBi('See its page', 'شاهد صفحتها') ?></a>
        <form method="post" action="admin_workshops.php#workshops"><?= csrfField() ?><input type="hidden" name="do" value="ws_hide"><input type="hidden" name="id" value="<?= (int) $w['id'] ?>"><button class="btn btn-secondary btn-sm" type="submit"><?= owBi('Hide', 'إخفاء') ?></button></form>
      <?php elseif ($w['status'] === 'hidden'): ?>
        <form method="post" action="admin_workshops.php#workshops"><?= csrfField() ?><input type="hidden" name="do" value="ws_show"><input type="hidden" name="id" value="<?= (int) $w['id'] ?>"><button class="btn btn-secondary btn-sm" type="submit"><?= owBi('Show again', 'إظهار مجددا') ?></button></form>
      <?php elseif ($w['reason'] !== ''): ?><span class="mini"><?= owBi('Reason: ', 'السبب: ') ?><?= e($w['reason']) ?></span><?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <h2 id="bookings"><?= owBi('Bookings', 'الحجوزات') ?></h2>
  <p class="mini"><?= owBi('Only the Oweili team sees the full details. The artist sees only first names and the number of seats', 'لا يرى التفاصيل الكاملة إلا فريق أويلي. يرى الفنان الأسماء الأولى وعدد المقاعد فقط') ?></p>
  <?php if (!$bks): ?><div class="card"><p class="mini"><?= owBi('No bookings yet', 'لا حجوزات بعد') ?></p></div><?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th><?= owBi('Booking', 'الحجز') ?></th><th><?= owBi('Workshop', 'الورشة') ?></th><th><?= owBi('Student', 'المتدرب') ?></th><th><?= owBi('Amount', 'المبلغ') ?></th><th><?= owBi('Status', 'الحالة') ?></th><th></th></tr></thead>
    <tbody><?php foreach ($bks as $b): $bs = OW_WSB_STATUS[$b['status']] ?? ['', '']; ?>
      <tr><td dir="ltr"><?= e($b['ref_no']) ?><br><span class="mini"><?= $date($b['created_at']) ?></span></td>
        <td><?= owBi((string) $b['title_en'], (string) $b['title_ar']) ?></td>
        <td><bdi><?= e($b['name']) ?></bdi><br><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $b['phone'])) ?>" dir="ltr"><?= e($b['phone']) ?></a><br><a href="mailto:<?= e($b['email']) ?>" dir="ltr"><?= e($b['email']) ?></a></td>
        <td><?= owSar((float) $b['amount']) ?><br><span class="mini"><?= owBi('artist ', 'الفنان ') ?><?= owSar((float) $b['artist_amount']) ?> · <?= owBi('Oweili ', 'أويلي ') ?><?= owSar((float) $b['commission_amount']) ?></span></td>
        <td><span class="pillx <?= $b['status'] === 'paid' ? 'on' : ($b['status'] === 'booked' ? 'warn' : '') ?>"><?= owBi($bs[0], $bs[1]) ?></span><?= $b['pay_ref'] !== '' ? '<br><span class="mini" dir="ltr">' . e($b['pay_ref']) . '</span>' : '' ?></td>
        <td><?php if ($b['status'] === 'booked'): ?><div class="acts">
          <form method="post" action="admin_workshops.php#bookings" class="acts"><?= csrfField() ?><input type="hidden" name="do" value="bk_paid"><input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
            <input name="pay_ref" maxlength="120" placeholder="<?= e(owIsAr() ? 'مرجع الدفع (اختياري)' : 'Payment reference (optional)') ?>" aria-label="<?= e(owIsAr() ? 'مرجع الدفع' : 'Payment reference') ?>"><button class="btn btn-primary btn-sm" type="submit"><?= owBi('Mark as paid', 'تأكيد الدفع') ?></button></form>
          <form method="post" action="admin_workshops.php#bookings" onsubmit="return confirm(document.documentElement.dir === 'rtl' ? 'إلغاء هذا الحجز؟' : 'Cancel this booking?');"><?= csrfField() ?><input type="hidden" name="do" value="bk_cancel"><input type="hidden" name="id" value="<?= (int) $b['id'] ?>"><button class="btn btn-danger btn-sm" type="submit"><?= owBi('Cancel', 'إلغاء') ?></button></form>
        </div><?php endif; ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php endif; ?>
<?php owAdminBottom();
