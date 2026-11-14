<?php
/* =====================================================================
 *  my_bookings.php — a collector's course registrations and museum tickets.
 *  HIDDEN until the bookings switch is on (booking_lib.php).
 *  Tables: course_registrations, courses, museum_tickets, art_places
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/booking_lib.php';
$preview = owBookingGate();
if (!isLoggedIn()) { header('Location: login.php?next=my_bookings.php'); exit; }
$me = currentUser();
if (!$me || ($me['role'] ?? '') !== 'collector') { header('Location: ' . dashboardUrl($me['role'] ?? '')); exit; }
owBookingCleanup();
$db = getDB(); $uid = (int) $me['id'];
$st = $db->prepare("SELECT r.*, c.title_en, c.title_ar, c.start_date, c.end_date, c.course_type, c.meeting_link, c.location_en, c.location_ar, c.schedule_en, c.schedule_ar
                    FROM course_registrations r JOIN courses c ON c.id = r.course_id WHERE r.user_id = :u AND c.bookable = 1 AND (r.status = 'confirmed' OR r.paid_at IS NOT NULL) ORDER BY c.start_date DESC");
$st->execute([':u' => $uid]); $regs = $st->fetchAll();
$st = $db->prepare("SELECT t.*, p.name_en, p.name_ar FROM museum_tickets t JOIN art_places p ON p.id = t.place_id WHERE t.user_id = :u AND t.status IN ('paid','used','cancelled') ORDER BY t.visit_date DESC");
$st->execute([':u' => $uid]); $tickets = $st->fetchAll();
owBookTop('My bookings', 'حجوزاتي', 'my_bookings.php', $preview);
?>
<div class="card"><h1><?= owBi('My bookings', 'حجوزاتي') ?></h1>
  <?php if (isset($_GET['done'])): ?><div class="alert alert-ok" role="status"><?= owBi('You are registered. We sent you an email.', 'تم تسجيلك. أرسلنا لك رسالة.') ?></div><?php endif; ?>
  <p class="mini"><?= owBi('To cancel, write to us from this email with your booking number.', 'للإلغاء، راسلنا من هذا البريد مع رقم الحجز.') ?> <a href="support.php"><?= owBi('Help', 'المساعدة') ?></a></p></div>
<div class="card"><h2><?= owBi('Courses', 'الدورات') ?></h2>
  <?php if (!$regs): ?><p><?= owBi('No courses yet.', 'لا دورات بعد.') ?></p><?php endif; ?>
  <?php foreach ($regs as $r): [$de, $da] = owDateRangeBi($r['start_date'], $r['end_date']); ?>
  <div style="padding:10px 0;border-bottom:1px solid var(--color-border)">
    <b><?= owBi($r['title_en'], $r['title_ar']) ?></b> <?= owPill(OW_REG_STATUS, (string) $r['status']) ?><br>
    <span class="mini"><?= owBi($de, $da) ?> · <?= owBi((string) $r['schedule_en'], (string) $r['schedule_ar']) ?> · <span dir="ltr"><?= e($r['ref_no']) ?></span></span><br>
    <?php if ($r['status'] === 'confirmed'): ?>
      <?= $r['course_type'] === 'online' ? owBi('Meeting link: ', 'رابط الاجتماع: ') . '<a href="' . e($r['meeting_link']) . '" target="_blank" rel="noopener" dir="ltr">' . e($r['meeting_link']) . '</a>' : owBi((string) $r['location_en'], (string) $r['location_ar']) ?>
    <?php endif; ?>
    <a class="mini" href="course.php?id=<?= (int) $r['course_id'] ?>"><?= owBi('Course page', 'صفحة الدورة') ?></a>
  </div>
  <?php endforeach; ?>
</div>
<div class="card"><h2><?= owBi('Museum tickets', 'تذاكر المتاحف') ?></h2>
  <?php if (!$tickets): ?><p><?= owBi('No tickets yet.', 'لا تذاكر بعد.') ?></p><?php endif; ?>
  <?php foreach ($tickets as $t): [$de, $da] = owDateRangeBi($t['visit_date'], null); [$lEn, $lAr] = owTicketLinesBi($t); ?>
  <div style="padding:10px 0;border-bottom:1px solid var(--color-border)">
    <b><?= owBi($t['name_en'], $t['name_ar']) ?></b> <?= owPill(OW_TICKET_STATUS, (string) $t['status']) ?><br>
    <span class="mini"><?= owBi($de, $da) ?> · <?= owBi($lEn, $lAr) ?> · <span dir="ltr"><?= e($t['ref_no']) ?></span></span><br>
    <?php if ($t['status'] === 'paid'): ?><a class="btn btn-primary btn-sm" style="margin-top:6px" href="ticket.php?k=<?= e($t['qr_token']) ?>"><?= owBi('Show my ticket', 'اعرض تذكرتي') ?></a><?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php owBookBottom();
