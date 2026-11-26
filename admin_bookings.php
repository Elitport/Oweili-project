<?php
/* =====================================================================
 *  admin_bookings.php — the owner's tool for course booking and museum
 *  tickets (owner, 14 Nov 2026): the three payout rules side by side,
 *  institutes waiting for approval, courses waiting for approval,
 *  registrations, partner museums (ticket types and dates), tickets and
 *  payouts to institutes and museums. Admin only.
 *  Works while the bookings switch is off (Admin → Settings), so everything
 *  can be prepared and tested before visitors see it.
 *  Tables: users, institutes, courses, course_registrations, art_places,
 *          museum_ticket_types, museum_ticket_dates, museum_tickets,
 *          booking_payments, partner_payouts, activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/admin_shell.php';
require_once __DIR__ . '/booking_lib.php';
require_once __DIR__ . '/inst_lib.php';
require_once __DIR__ . '/pay_lib.php';
require_once __DIR__ . '/order_lib.php';   // the artwork rule (OW_PAYOUT_ASK_DAYS), read only
owAdminGate();
$actor = owAdminActor();
$ready = owBookingsReady();
$db = getDB(); $msg = null; $bad = null;
if ($ready) owBookingCleanup();
$mailTo = function (string $to, string $subj, string $en, string $ar, string $link = '') { require_once __DIR__ . '/request_lib.php'; owReqTellBuyer($to, $subj, $en, $ar, $link); };

if ($ready && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $do = (string) ($_POST['do'] ?? ''); $id = (int) ($_POST['id'] ?? 0); $why = mb_substr(trim((string) ($_POST['reason'] ?? '')), 0, 500);
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) $bad = ['The page was open too long. Please try again.', 'انتهت صلاحية الصفحة. حاول مرة أخرى.'];
    elseif ($do === 'inst_approve' || $do === 'inst_reject') {
        $st = $db->prepare("SELECT i.*, u.email, u.id AS uid FROM institutes i JOIN users u ON u.id = i.user_id WHERE i.id = :id AND u.role = 'institute'"); $st->execute([':id' => $id]); $i = $st->fetch();
        if (!$i) $bad = ['Not found', 'غير موجود'];
        elseif ($do === 'inst_reject' && mb_strlen($why) < 3) $bad = ['Please write the reason', 'يرجى كتابة السبب'];
        else {
            $on = $do === 'inst_approve' ? 1 : 0;
            $db->prepare('UPDATE users SET is_approved = :a WHERE id = :u')->execute([':a' => $on, ':u' => $i['uid']]);
            $db->prepare('UPDATE institutes SET is_active = :a WHERE id = :id')->execute([':a' => $on, ':id' => $id]);
            /* the reason is kept, so a refused institute no longer waits in Waiting for approval (owner, 25 Nov 2026) */
            if (owHasColumn('institutes', 'reject_reason')) $db->prepare('UPDATE institutes SET reject_reason = :r WHERE id = :id')->execute([':r' => $on ? '' : $why, ':id' => $id]);
            owLog($on ? 'institute_approve' : 'institute_reject', 'institute', $id, $why, $actor);
            $on ? $mailTo($i['email'], 'Your institute is approved · تم اعتماد معهدك', 'Welcome to Oweili! Your institute is approved. Add your courses from your dashboard.', 'أهلا بك في أويلي! تم اعتماد معهدك. أضف دوراتك من لوحتك.', 'institute_dashboard.php')
                : $mailTo($i['email'], 'About your institute · بخصوص معهدك', 'We could not approve your institute yet. Reason: ' . $why, 'لم نتمكن من اعتماد معهدك بعد. السبب: ' . $why);
            $msg = $on ? ['Institute approved', 'تم اعتماد المعهد'] : ['Institute not approved. They were told the reason.', 'لم يُعتمد المعهد، وأُبلغ بالسبب'];
            if (($_POST['back'] ?? '') === 'pending') { header('Location: admin.php?inst_done=' . ($on ? 'approved' : 'refused') . '#pending'); exit; }
        }
    } elseif ($do === 'course_approve' || $do === 'course_reject') {
        $c = owCourseGet($id);
        if (!$c || (int) $c['bookable'] !== 1) $bad = ['Not found', 'غير موجود'];
        elseif ($do === 'course_reject' && mb_strlen($why) < 3) $bad = ['Please write the reason', 'يرجى كتابة السبب'];
        /* a course goes live only once its institute is approved and has its training licence (owner, 25 Nov 2026) */
        elseif ($do === 'course_approve' && (int) $c['inst_active'] !== 1) $bad = ['Approve the institute first', 'اعتمد المعهد أولا'];
        elseif ($do === 'course_approve' && owInstDocsReady() && ($lc = $db->prepare('SELECT licence_file, via_signup FROM institutes WHERE id = :i')) && $lc->execute([':i' => (int) $c['institute_id']]) && ($lr = $lc->fetch()) && (int) $lr['via_signup'] === 1 && (string) $lr['licence_file'] === '')
            $bad = ['This institute has not added its training licence yet. Courses go live only with a licence', 'لم يُضف هذا المعهد ترخيص التدريب بعد. لا تظهر الدورات إلا مع الترخيص'];
        else {
            $s = $do === 'course_approve' ? 'approved' : 'rejected';
            $db->prepare('UPDATE courses SET status = :s, reject_reason = :r WHERE id = :id')->execute([':s' => $s, ':r' => $s === 'rejected' ? $why : '', ':id' => $id]);
            owLog('course_' . ($s === 'approved' ? 'approve' : 'reject'), 'course', $id, $why, $actor);
            if ($c['inst_email'] !== '') $s === 'approved'
                ? $mailTo($c['inst_email'], 'Course approved · تم اعتماد الدورة', 'Your course "' . $c['title_en'] . '" is approved.', 'تم اعتماد دورتك «' . $c['title_ar'] . '».', 'course.php?id=' . $id)
                : $mailTo($c['inst_email'], 'About your course · بخصوص دورتك', 'Your course "' . $c['title_en'] . '" was not approved. Reason: ' . $why, 'لم تُعتمد دورتك «' . $c['title_ar'] . '». السبب: ' . $why, 'institute_dashboard.php');
            $msg = $s === 'approved' ? ['Course approved', 'تم اعتماد الدورة'] : ['Course not approved. The institute was told the reason.', 'لم تُعتمد الدورة، وأُبلغ المعهد بالسبب'];
        }
    } elseif ($do === 'course_art' && owCourseArtReady()) {   // the admin sets a course's art type and style (owner, 21 Nov 2026); its status is not changed
        $c = owCourseGet($id); $art = (string) ($_POST['art_type'] ?? ''); $sty = (string) ($_POST['art_style'] ?? '');
        if (!$c || (int) $c['bookable'] !== 1) $bad = ['Not found', 'غير موجود'];
        elseif (!isset(OW_COURSE_ARTS[$art])) $bad = ['Please choose the art type', 'يرجى اختيار نوع الفن'];
        elseif ($sty !== '' && !isset(owCourseArtStyles()[$art][$sty])) $bad = ['Please choose a style of this art type, or leave the style empty', 'يرجى اختيار أسلوب من نوع الفن نفسه، أو ترك الأسلوب فارغا'];
        else {
            $db->prepare('UPDATE courses SET art_type = :a, art_style = :s WHERE id = :id')->execute([':a' => $art, ':s' => $sty, ':id' => $id]);
            owLog('course_art', 'course', $id, $art . ($sty !== '' ? ' / ' . $sty : ''), $actor); $msg = ['Saved', 'تم الحفظ'];
        }
    } elseif ($do === 'reg_cancel' || $do === 'ticket_cancel') {
        $e2 = owBookingCancel($do === 'reg_cancel' ? 'course' : 'ticket', $id, $actor, !empty($_POST['refund']), $why);
        $e2 ? $bad = $e2 : $msg = ['Cancelled.' . (!empty($_POST['refund']) ? ' The money was refunded' : ''), 'تم الإلغاء.' . (!empty($_POST['refund']) ? ' وأُعيد المبلغ' : '')];
    } elseif ($do === 'reg_paid') {   // a seat booked before online payment, paid to Oweili by hand (owner, 15 Nov 2026)
        $e2 = owCourseMarkPaid($id, $actor, mb_substr(trim((string) ($_POST['reference'] ?? '')), 0, 300));
        $e2 ? $bad = $e2 : $msg = ['Marked as paid. The student got the confirmation, and the institute’s payout was added.', 'تم التأشير بالدفع. وصل الطالبَ التأكيد، وأُضيف مستحق المعهد.'];
    } elseif ($do === 'lead_status' && owCourseLeadsReady()) {
        $st2 = in_array($_POST['status'] ?? '', ['new', 'contacted', 'closed'], true) ? $_POST['status'] : 'new';
        $db->prepare('UPDATE course_leads SET status = :s WHERE id = :id')->execute([':s' => $st2, ':id' => $id]);
        owLog('course_lead_status', 'course_lead', $id, $st2, $actor); $msg = ['Saved', 'تم الحفظ'];
    } elseif ($do === 'payout_paid') {
        $ref = mb_substr(trim((string) ($_POST['reference'] ?? '')), 0, 120);
        if ($ref === '') $bad = ['Please write the bank transfer reference', 'يرجى كتابة مرجع التحويل البنكي'];
        else {
            $u = $db->prepare("UPDATE partner_payouts SET status = 'paid', paid_at = NOW(), reference = :r WHERE id = :id AND status = 'pending'"); $u->execute([':r' => $ref, ':id' => $id]);
            if ($u->rowCount()) { owLog('partner_payout_paid', 'partner_payout', $id, $ref, $actor); $msg = ['Marked as paid', 'تم التأشير بالدفع']; }
        }
    } elseif (in_array($do, ['type_save', 'dates_add', 'date_save'], true)) {
        $pl = owMuseumGet((int) ($_POST['place'] ?? 0));
        $e2 = $pl ? owMuseumSetupPost($pl, $_POST, $actor) : ['Not found', 'غير موجود'];
        $e2 ? $bad = $e2 : $msg = ['Saved', 'تم الحفظ'];
    }
}

$K = fn(string $sql, array $a = []) => (function () use ($db, $sql, $a) { $s = $db->prepare($sql); $s->execute($a); return $s->fetchAll(); })();
$insts = $ready ? $K("SELECT i.*, u.email, u.is_verified, u.is_approved, u.full_name_en AS person FROM institutes i JOIN users u ON u.id = i.user_id WHERE i.via_signup = 1 ORDER BY u.is_approved, i.id DESC") : [];
$courses = $ready ? $K("SELECT c.*, i.name_en AS inst_en, i.name_ar AS inst_ar FROM courses c JOIN institutes i ON i.id = c.institute_id WHERE c.bookable = 1 ORDER BY (c.status = 'pending') DESC, c.start_date DESC LIMIT 300") : [];
$regs = $ready ? $K("SELECT r.*, c.title_en, c.title_ar, c.start_date FROM course_registrations r JOIN courses c ON c.id = r.course_id WHERE c.bookable = 1 AND (r.status = 'confirmed' OR r.paid_at IS NOT NULL OR (r.status = 'pending' AND r.hold_until IS NULL)) ORDER BY (r.status = 'pending') DESC, r.id DESC LIMIT 300") : [];
$leads = $ready && owCourseLeadsReady() ? $K("SELECT l.*, i.name_en AS inst_en, i.name_ar AS inst_ar, p.name_en AS pl_en, p.name_ar AS pl_ar FROM course_leads l
                        LEFT JOIN institutes i ON i.id = l.institute_id LEFT JOIN art_places p ON p.id = l.place_id ORDER BY (l.status = 'new') DESC, l.id DESC LIMIT 300") : [];
$museums = $ready ? $K("SELECT p.*, u.email AS acct_email, (SELECT COUNT(*) FROM museum_ticket_types t WHERE t.place_id = p.id AND t.is_active = 1) AS n_types,
                        (SELECT COUNT(*) FROM museum_ticket_dates d WHERE d.place_id = p.id AND d.is_active = 1 AND d.visit_date >= CURDATE()) AS n_dates
                        FROM art_places p LEFT JOIN users u ON u.id = p.user_id WHERE p.kind = 'museum' AND (p.partner = 1 OR p.user_id IS NOT NULL) ORDER BY p.name_en") : [];
$tickets = $ready ? $K("SELECT t.*, p.name_en, p.name_ar FROM museum_tickets t JOIN art_places p ON p.id = t.place_id WHERE t.status IN ('paid','used','cancelled') ORDER BY t.id DESC LIMIT 300") : [];
$payouts = $ready ? $K("SELECT pp.*, CASE pp.payee_kind WHEN 'institute' THEN (SELECT name_en FROM institutes WHERE id = pp.payee_id) ELSE (SELECT name_en FROM art_places WHERE id = pp.payee_id) END AS payee_en,
                        CASE pp.payee_kind WHEN 'institute' THEN (SELECT payout_note FROM institutes WHERE id = pp.payee_id) ELSE (SELECT payout_note FROM art_places WHERE id = pp.payee_id) END AS payee_note
                        FROM partner_payouts pp ORDER BY (pp.status = 'pending') DESC, pp.due_on IS NULL, pp.due_on, pp.id DESC LIMIT 400") : [];
$setup = isset($_GET['setup']) ? owMuseumGet((int) $_GET['setup']) : null;
$waitI = count(array_filter($insts, fn($i) => !(int) $i['is_approved'])); $waitC = count(array_filter($courses, fn($c) => $c['status'] === 'pending'));
$keys = owPayKeysStatus();

owAdminTop('Courses and tickets', 'الدورات والتذاكر', '
.rules{display:grid;grid-template-columns:repeat(3,1fr);gap:var(--space-sm);}
.rules .card{margin:0;border-top:4px solid var(--color-border-strong);}
.rules .r-art{border-top-color:var(--color-primary);} .rules .r-course{border-top-color:var(--color-accent);} .rules .r-museum{border-top-color:#8FA1B1;}
.jump{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:var(--space-md);}
.tbl{width:100%;border-collapse:collapse;font-size:14px;}
.tbl th,.tbl td{padding:8px;border-bottom:1px solid var(--color-border);text-align:start;vertical-align:top;}
.tbl form{display:inline-flex;gap:4px;flex-wrap:wrap;align-items:center;margin:2px 0;}
.tbl input[type=text]{min-height:34px;padding:4px 8px;max-width:180px;}
' . owMuseumSetupCss() . '
@media(max-width:800px){.rules{grid-template-columns:1fr;}.tbl thead{display:none;}.tbl td{display:block;border:0;padding:2px 0;}.tbl tr{display:block;border-bottom:1px solid var(--color-border);padding:8px 0;}}
');
?>
<h1><?= owBi('Courses and tickets', 'الدورات والتذاكر') ?></h1>
<p class="mini"><?= owBi('Courses: ', 'الدورات: ') ?><?= owCoursesOn() ? '<span class="pillx on">' . owBi('Shown to visitors', 'ظاهر للزوار') . '</span>' : '<span class="pillx warn">' . owBi('Hidden from visitors', 'مخفي عن الزوار') . '</span>' ?>
  · <?= owBi('Museum tickets: ', 'تذاكر المتاحف: ') ?><?= owBookingsOn() ? '<span class="pillx on">' . owBi('Shown to visitors', 'ظاهر للزوار') . '</span>' : '<span class="pillx warn">' . owBi('Hidden from visitors', 'مخفي عن الزوار') . '</span>' ?>
  <?= owBi('Two switches: Admin → Settings → "Hidden for now"', 'مفتاحان: الإدارة ← الإعدادات ← «مخفي حاليا»') ?> <a href="admin.php?settings=1#hidden-parts"><?= owBi('Settings', 'الإعدادات') ?></a></p>
<?php if (!$ready): ?><div class="card"><div class="alert alert-error"><?= owBi('The database part is not in place yet (bookings_update.sql)', 'جزء قاعدة البيانات غير موجود بعد (bookings_update.sql)') ?></div></div><?php owAdminBottom(); exit; endif; ?>
<?php if ($msg): ?><div class="alert alert-ok" role="status"><?= owBi($msg[0], $msg[1]) ?></div><?php endif; ?>
<?php if ($bad): ?><div class="alert alert-error" role="alert"><?= owBi($bad[0], $bad[1]) ?></div><?php endif; ?>
<nav class="jump">
  <a class="btn btn-secondary btn-sm" href="#rules"><?= owBi('Payout rules', 'قواعد المستحقات') ?></a>
  <a class="btn btn-secondary btn-sm" href="#institutes"><?= owBi('Institutes', 'المعاهد') ?><?= $waitI ? ' (' . $waitI . ')' : '' ?></a>
  <a class="btn btn-secondary btn-sm" href="#courses"><?= owBi('Courses', 'الدورات') ?><?= $waitC ? ' (' . $waitC . ')' : '' ?></a>
  <a class="btn btn-secondary btn-sm" href="#registrations"><?= owBi('Registrations', 'التسجيلات') ?><?= ($nb = count(array_filter($regs, 'owRegIsBooking'))) ? ' (' . $nb . ')' : '' ?></a>
  <a class="btn btn-secondary btn-sm" href="#leads"><?= owBi('Notify me', 'نبّهني') ?><?= ($nl = count(array_filter($leads, fn($l) => $l['status'] === 'new'))) ? ' (' . $nl . ')' : '' ?></a>
  <a class="btn btn-secondary btn-sm" href="#museums"><?= owBi('Partner museums', 'المتاحف الشريكة') ?></a>
  <a class="btn btn-secondary btn-sm" href="#tickets"><?= owBi('Tickets', 'التذاكر') ?></a>
  <a class="btn btn-secondary btn-sm" href="#payouts"><?= owBi('Payouts', 'المستحقات') ?></a>
  <a class="btn btn-secondary btn-sm" href="#payments"><?= owBi('Payment providers', 'مزودو الدفع') ?></a>
</nav>

<?php if ($setup): ?>
<div class="card" id="setup"><h2><?= owBi('Ticket types and dates: ' . $setup['name_en'], 'أنواع التذاكر والتواريخ: ' . $setup['name_ar']) ?></h2><a class="btn btn-ghost btn-sm" href="admin_bookings.php#museums">← <?= owBi('Back', 'رجوع') ?></a></div>
<?= owMuseumSetupHtml($setup, 'admin_bookings.php?setup=' . (int) $setup['id'] . '#setup') ?>
<?php endif; ?>

<h2 id="rules"><?= owBi('The three payout rules (kept separate)', 'قواعد المستحقات الثلاث (منفصلة)') ?></h2>
<div class="rules">
  <div class="card r-art"><h3><?= owBi('1. Artworks (artists)', '1. الأعمال الفنية (الفنانون)') ?></h3>
    <p><?= owBi('Oweili holds the money. The artist is paid only after you approve the payout. The request reaches you when the collector sends "I received the artwork" with a photo, or ' . OW_PAYOUT_ASK_DAYS . ' days after delivery with no complaint. A complaint holds it. Commission: ' . owCommissionText() . ' (Settings → Oweili’s commission).',
                'يحتفظ أويلي بالمبلغ، ولا يُدفع للفنان إلا بعد موافقتك على الدفعة. يصلك الطلب حين يرسل المقتني «استلمت العمل» مع صورة، أو بعد ' . OW_PAYOUT_ASK_DAYS . ' أيام من التسليم دون شكوى. والشكوى توقفه. العمولة: ' . owCommissionText() . ' (الإعدادات ← عمولة أويلي).') ?></p>
    <p class="mini"><?= owBi('Switched off until payments for artworks are ready (Settings → Payments and escrow). Not changed by this tool.', 'متوقف حتى يجهز الدفع للأعمال الفنية (الإعدادات ← الدفع والضمان). لا تغيّره هذه الأداة.') ?></p></div>
  <div class="card r-course"><h3><?= owBi('2. Courses (institutes)', '2. الدورات (المعاهد)') ?></h3>
    <p><?= owBi('Due to the institute once the course has started (on its start date). Commission: ' . rtrim(rtrim(number_format(owCourseCommission(), 2), '0'), '.') . '% (Settings → Course commission).',
                'يستحق للمعهد بعد بداية الدورة (في تاريخ بدايتها). العمولة: ' . rtrim(rtrim(number_format(owCourseCommission(), 2), '0'), '.') . '٪ (الإعدادات ← عمولة الدورات).') ?></p>
    <p class="mini"><?= owBi('If the course is cancelled before it starts, the student is refunded and nothing is due', 'إن أُلغيت الدورة قبل بدايتها يُعاد المبلغ للطالب ولا يستحق شيء') ?></p></div>
  <div class="card r-museum"><h3><?= owBi('3. Museums (partners)', '3. المتاحف (الشركاء)') ?></h3>
    <p><?= owBi('Set per museum, by agreement: a number of days after the visit date, once a month, or by hand. Each museum has its own commission. Set in Admin → Art Map (each museum).',
                'لكل متحف حسب الاتفاقية: بعد تاريخ الزيارة بعدد أيام، أو مرة في الشهر، أو يدويا. ولكل متحف عمولته. تُضبط في الإدارة ← خريطة الفن (لكل متحف).') ?></p></div>
</div>

<h2 id="institutes" style="margin-top:var(--space-lg)"><?= owBi('Institutes that joined', 'المعاهد المنضمة') ?></h2>
<div class="card"><?php if (!$insts): ?><p><?= owBi('No institute has joined yet', 'لم ينضم أي معهد بعد') ?></p><?php else: ?>
<table class="tbl"><thead><tr><th><?= owBi('Institute', 'المعهد') ?></th><th><?= owBi('Contact', 'التواصل') ?></th><th><?= owBi('Status', 'الحالة') ?></th><th></th></tr></thead><tbody>
<?php foreach ($insts as $i): ?>
<tr><td><b><?= owBi($i['name_en'], $i['name_ar']) ?></b><br><span class="mini"><?= owBi($i['city_en'], $i['city_ar']) ?></span><?php if ($i['description_en'] !== ''): ?><br><span class="mini"><?= e(owExcerpt((string) $i['description_en'], 160)) ?></span><?php endif; ?></td>
  <td><?= e($i['person']) ?><br><span class="mini" dir="ltr"><?= e($i['email']) ?> · <?= e($i['contact_phone']) ?></span><?= $i['website_url'] !== '' ? '<br><a class="mini" href="' . e($i['website_url']) . '" target="_blank" rel="noopener">' . e($i['website_url']) . '</a>' : '' ?>
    <?= $i['payout_note'] !== '' ? '<br><span class="mini">' . owBi('Payout: ', 'المستحقات: ') . e($i['payout_note']) . '</span>' : '' ?>
    <?php if (owInstDocsReady()): ?><?= ($i['legal_name'] ?? '') !== '' ? '<br><span class="mini">' . owBi('Legal name: ', 'الاسم النظامي: ') . e((string) $i['legal_name']) . '</span>' : '' ?><?= owInstDocsHtml($i) ?><?php endif; ?></td>
  <td><?= (int) $i['is_approved'] ? '<span class="pillx on">' . owBi('Approved', 'معتمد') . '</span>' : '<span class="pillx warn">' . owBi('Waiting', 'بالانتظار') . '</span>' ?><?= !(int) $i['is_verified'] ? '<br><span class="mini">' . owBi('Email not confirmed', 'البريد غير مؤكد') . '</span>' : '' ?></td>
  <td><?php if (!(int) $i['is_approved']): ?>
    <form method="post" action="admin_bookings.php#institutes"><?= csrfField() ?><input type="hidden" name="do" value="inst_approve"><input type="hidden" name="id" value="<?= (int) $i['id'] ?>"><button class="btn btn-primary btn-sm"><?= owBi('Approve', 'اعتماد') ?></button></form>
    <?php endif; ?>
    <form method="post" action="admin_bookings.php#institutes"><?= csrfField() ?><input type="hidden" name="do" value="inst_reject"><input type="hidden" name="id" value="<?= (int) $i['id'] ?>"><input type="text" name="reason" placeholder="<?= e(owIsAr() ? 'السبب' : 'Reason') ?>" aria-label="<?= e('Reason · السبب') ?>"><button class="btn btn-danger btn-sm"><?= (int) $i['is_approved'] ? owBi('Suspend', 'إيقاف') : owBi('Not approved', 'رفض') ?></button></form></td></tr>
<?php endforeach; ?></tbody></table><?php endif; ?></div>

<h2 id="courses"><?= owBi('Courses', 'الدورات') ?></h2>
<div class="card"><?php if (!$courses): ?><p><?= owBi('No courses yet', 'لا دورات بعد') ?></p><?php else: ?>
<table class="tbl"><thead><tr><th><?= owBi('Course', 'الدورة') ?></th><th><?= owBi('Details', 'التفاصيل') ?></th><th><?= owBi('Status', 'الحالة') ?></th><th></th></tr></thead><tbody>
<?php foreach ($courses as $c): [$de, $da] = owDateRangeBi($c['start_date'], $c['end_date']); $ty = OW_COURSE_TYPES[$c['course_type']] ?? ['', '']; $lv = OW_COURSE_LEVELS[$c['level']] ?? ['', '']; ?>
<tr><td><b><?= owBi($c['title_en'], $c['title_ar']) ?></b><br><span class="mini"><?= owBi($c['inst_en'], $c['inst_ar']) ?></span><br><a class="mini" href="course.php?id=<?= (int) $c['id'] ?>"><?= owBi('Preview the page', 'معاينة الصفحة') ?></a></td>
  <td class="mini"><?= owBi($de, $da) ?> · <?= owBi($ty[0], $ty[1]) ?> · <?= owBi($lv[0], $lv[1]) ?><br><?= (float) $c['price'] > 0 ? owSar((float) $c['price']) : owBi('Free', 'مجاني') ?> · <?= (int) $c['seats_total'] - (int) $c['seats_remaining'] ?>/<?= (int) $c['seats_total'] ?> <?= owBi('seats', 'مقعد') ?>
    <?php if (owCourseArtReady()): $ca = (string) ($c['art_type'] ?? 'painting'); $cs = (string) ($c['art_style'] ?? ''); /* art type and style: shown and editable (owner, 21 Nov 2026) */ ?>
    <form method="post" action="admin_bookings.php#courses" style="margin-top:6px"><?= csrfField() ?><input type="hidden" name="do" value="course_art"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
      <select name="art_type" aria-label="<?= e('Art type · نوع الفن') ?>"><?php foreach (OW_COURSE_ARTS as $k => $w): ?><option value="<?= $k ?>"<?= $ca === $k ? ' selected' : '' ?>><?= e(owIsAr() ? $w[1] : $w[0]) ?></option><?php endforeach; ?></select>
      <select name="art_style" aria-label="<?= e('Style · الأسلوب') ?>"><option value=""><?= owIsAr() ? 'الأسلوب: بدون تحديد' : 'Style: not set' ?></option><?php foreach (owCourseArtStyles() as $ak => $list): ?><optgroup label="<?= e(owIsAr() ? OW_COURSE_ARTS[$ak][1] : OW_COURSE_ARTS[$ak][0]) ?>"><?php foreach ($list as $sk => $sw): ?><option value="<?= e($sk) ?>"<?= $cs === $sk ? ' selected' : '' ?>><?= e(owIsAr() ? $sw[1] : $sw[0]) ?></option><?php endforeach; ?></optgroup><?php endforeach; ?></select>
      <button class="btn btn-secondary btn-sm"><?= owBi('Save art type', 'حفظ نوع الفن') ?></button></form>
    <?php endif; ?></td>
  <td><?= owPill(OW_COURSE_STATUS, (string) $c['status']) ?><?= !(int) $c['is_active'] ? ' <span class="pillx">' . owBi('Hidden by the institute', 'أخفاها المعهد') . '</span>' : '' ?></td>
  <td><?php if ($c['status'] !== 'approved'): ?><form method="post" action="admin_bookings.php#courses"><?= csrfField() ?><input type="hidden" name="do" value="course_approve"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><button class="btn btn-primary btn-sm"><?= owBi('Approve', 'اعتماد') ?></button></form><?php endif; ?>
    <?php if ($c['status'] !== 'rejected'): ?><form method="post" action="admin_bookings.php#courses"><?= csrfField() ?><input type="hidden" name="do" value="course_reject"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><input type="text" name="reason" placeholder="<?= e(owIsAr() ? 'السبب' : 'Reason') ?>" aria-label="<?= e('Reason · السبب') ?>"><button class="btn btn-danger btn-sm"><?= owBi('Not approved', 'رفض') ?></button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table><?php endif; ?></div>

<h2 id="registrations"><?= owBi('Registrations', 'التسجيلات') ?></h2>
<div class="card"><?php if (!$regs): ?><p><?= owBi('No registrations yet', 'لا تسجيلات بعد') ?></p><?php else: ?>
<table class="tbl"><thead><tr><th><?= owBi('Student', 'الطالب') ?></th><th><?= owBi('Course', 'الدورة') ?></th><th><?= owBi('Money', 'المبلغ') ?></th><th><?= owBi('Status', 'الحالة') ?></th><th></th></tr></thead><tbody>
<?php foreach ($regs as $r): ?>
<tr><td><b><?= e($r['student_name']) ?></b><br><span class="mini" dir="ltr"><?= e($r['ref_no']) ?> · <?= e($r['student_email']) ?> · <?= e($r['student_phone']) ?></span></td>
  <td><?= owBi($r['title_en'], $r['title_ar']) ?></td>
  <td class="mini"><?= owSar((float) $r['amount']) ?><br><?= owBi('Oweili ', 'أويلي ') ?><?= owSar((float) $r['commission_amount']) ?> (<?= rtrim(rtrim((string) $r['commission_rate'], '0'), '.') ?>%)<?= (float) $r['refund_amount'] > 0 ? '<br>' . owBi('Refunded', 'مُسترد') : '' ?></td>
  <td><?= owRegPill($r) ?></td>
  <td><?php if (owRegIsBooking($r)): ?><form method="post" action="admin_bookings.php#registrations" onsubmit="return confirm(document.documentElement.dir==='rtl'?'هل استلمت الدفع لهذا الحجز؟':'Did you receive the payment for this booking?')"><?= csrfField() ?><input type="hidden" name="do" value="reg_paid"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
    <input type="text" name="reference" placeholder="<?= e(owIsAr() ? 'مرجع الدفع (اختياري)' : 'Payment reference (optional)') ?>" aria-label="<?= e('Payment reference · مرجع الدفع') ?>"><button class="btn btn-accent btn-sm"><?= owBi('Mark as paid', 'تم الدفع') ?></button></form>
    <form method="post" action="admin_bookings.php#registrations" onsubmit="return confirm(document.documentElement.dir==='rtl'?'إلغاء هذا الحجز؟':'Cancel this booking?')"><?= csrfField() ?><input type="hidden" name="do" value="reg_cancel"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="text" name="reason" placeholder="<?= e(owIsAr() ? 'السبب' : 'Reason') ?>" aria-label="<?= e('Reason · السبب') ?>"><button class="btn btn-danger btn-sm"><?= owBi('Cancel', 'إلغاء') ?></button></form>
  <?php elseif ($r['status'] === 'confirmed'): ?><form method="post" action="admin_bookings.php#registrations" onsubmit="return confirm(document.documentElement.dir==='rtl'?'إلغاء هذا التسجيل؟':'Cancel this registration?')"><?= csrfField() ?><input type="hidden" name="do" value="reg_cancel"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
    <label class="mini"><input type="checkbox" name="refund" value="1" checked> <?= owBi('Refund', 'استرداد') ?></label><input type="text" name="reason" placeholder="<?= e(owIsAr() ? 'السبب' : 'Reason') ?>" aria-label="<?= e('Reason · السبب') ?>"><button class="btn btn-danger btn-sm"><?= owBi('Cancel', 'إلغاء') ?></button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table><?php endif; ?></div>

<h2 id="leads"><?= owBi('Notify me: waiting for a course', 'نبّهني: بانتظار دورة') ?></h2>
<div class="card"><?php if (!$leads): ?><p><?= owBi('No requests yet', 'لا طلبات بعد') ?></p><?php else: ?>
<?php $leadCityAr = []; try { foreach ($K("SELECT city_en, MIN(city_ar) AS city_ar FROM institutes WHERE city_en <> '' GROUP BY city_en") as $r) $leadCityAr[$r['city_en']] = $r['city_ar'] ?: $r['city_en']; } catch (Throwable $e) {} ?>
<table class="tbl"><thead><tr><th><?= owBi('Person', 'الشخص') ?></th><th><?= owBi('Institute', 'المعهد') ?></th><th><?= owBi('City or Online', 'المدينة أو عن بعد') ?></th><th><?= owBi('Which art?', 'أي فن؟') ?></th><th><?= owBi('Date', 'التاريخ') ?></th><th><?= owBi('Status', 'الحالة') ?></th></tr></thead><tbody>
<?php foreach ($leads as $l): $lc = (string) ($l['city'] ?? ''); $ln = $l['inst_en'] !== null ? [$l['inst_en'], $l['inst_ar'] ?: $l['inst_en']] : ($l['pl_en'] !== null ? [$l['pl_en'], $l['pl_ar'] ?: $l['pl_en']] : ['Any institute', 'أي معهد']); ?>
<tr><td><b><?= e($l['name']) ?></b><br><span class="mini" dir="ltr"><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', (string) $l['phone'])) ?>"><?= e($l['phone']) ?></a> · <a href="mailto:<?= e($l['email']) ?>"><?= e($l['email']) ?></a></span></td>
  <td><?= owBi($ln[0], $ln[1]) ?></td><td><?= $lc === '' ? '' : ($lc === 'online' ? owBi('Online', 'عن بعد') : owBi($lc, $leadCityAr[$lc] ?? $lc)) ?></td><td><?php $la = (string) ($l['art_type'] ?? ''); echo $la === 'any' ? owBi('Any', 'أي فن') : (isset(OW_COURSE_ARTS[$la]) ? owBi(OW_COURSE_ARTS[$la][0], OW_COURSE_ARTS[$la][1]) : ''); ?></td><td class="mini" dir="ltr"><?= e(substr((string) $l['created_at'], 0, 16)) ?></td>
  <td><form method="post" action="admin_bookings.php#leads"><?= csrfField() ?><input type="hidden" name="do" value="lead_status"><input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
    <select name="status" aria-label="<?= e('Status · الحالة') ?>" onchange="this.form.submit()"><?php foreach (['new' => ['New', 'جديد'], 'contacted' => ['Contacted', 'تم التواصل'], 'closed' => ['Closed', 'مغلق']] as $k => $w): ?><option value="<?= $k ?>"<?= $l['status'] === $k ? ' selected' : '' ?>><?= e(owIsAr() ? $w[1] : $w[0]) ?></option><?php endforeach; ?></select>
    <noscript><button class="btn btn-secondary btn-sm"><?= owBi('Save', 'حفظ') ?></button></noscript></form></td></tr>
<?php endforeach; ?></tbody></table><?php endif; ?></div>

<h2 id="museums"><?= owBi('Partner museums', 'المتاحف الشريكة') ?></h2>
<div class="card"><p class="mini"><?= owBi('Make a museum a partner, set its commission, its payout rule and its account in Admin → Art Map (open the museum, "Tickets")', 'اجعل المتحف شريكا واضبط عمولته وقاعدة مستحقاته وحسابه من الإدارة ← خريطة الفن (افتح المتحف ← «التذاكر»)') ?> <a href="admin_art_map.php?k=museum"><?= owBi('Art Map', 'خريطة الفن') ?></a></p>
<?php if (!$museums): ?><p><?= owBi('No partner museum yet', 'لا متاحف شريكة بعد') ?></p><?php else: ?>
<table class="tbl"><thead><tr><th><?= owBi('Museum', 'المتحف') ?></th><th><?= owBi('Commission and payout rule', 'العمولة وقاعدة المستحقات') ?></th><th><?= owBi('Tickets on sale', 'التذاكر المعروضة') ?></th><th></th></tr></thead><tbody>
<?php foreach ($museums as $m): [$re, $ra] = owMuseumRuleBi($m); ?>
<tr><td><b><?= owBi($m['name_en'], $m['name_ar']) ?></b> <?= (int) $m['partner'] ? '<span class="pillx on">' . owBi('Partner', 'شريك') . '</span>' : '<span class="pillx">' . owBi('Not partner', 'غير شريك') . '</span>' ?><br><span class="mini" dir="ltr"><?= e($m['acct_email'] ?? owBi('No account', 'لا حساب')) ?></span></td>
  <td class="mini"><?= rtrim(rtrim((string) $m['ticket_commission'], '0'), '.') ?>% · <?= owBi($re, $ra) ?></td>
  <td class="mini"><?= owBi((int) $m['n_types'] . ' types, ' . (int) $m['n_dates'] . ' dates', (int) $m['n_types'] . ' أنواع، ' . (int) $m['n_dates'] . ' تواريخ') ?></td>
  <td><a class="btn btn-secondary btn-sm" href="admin_bookings.php?setup=<?= (int) $m['id'] ?>#setup"><?= owBi('Types and dates', 'الأنواع والتواريخ') ?></a> <a class="btn btn-ghost btn-sm" href="ticket_buy.php?p=<?= (int) $m['id'] ?>"><?= owBi('Preview', 'معاينة') ?></a></td></tr>
<?php endforeach; ?></tbody></table><?php endif; ?></div>

<h2 id="tickets"><?= owBi('Tickets', 'التذاكر') ?></h2>
<div class="card"><?php if (!$tickets): ?><p><?= owBi('No tickets sold yet', 'لم تُبع تذاكر بعد') ?></p><?php else: ?>
<table class="tbl"><thead><tr><th><?= owBi('Ticket', 'التذكرة') ?></th><th><?= owBi('Museum and date', 'المتحف والتاريخ') ?></th><th><?= owBi('Money', 'المبلغ') ?></th><th><?= owBi('Status', 'الحالة') ?></th><th></th></tr></thead><tbody>
<?php foreach ($tickets as $t): [$de, $da] = owDateRangeBi($t['visit_date'], null); [$lEn, $lAr] = owTicketLinesBi($t); ?>
<tr><td><b dir="ltr"><?= e($t['ref_no']) ?></b><br><?= e($t['buyer_name']) ?><br><span class="mini" dir="ltr"><?= e($t['buyer_email']) ?> · <?= e($t['buyer_phone']) ?></span></td>
  <td><?= owBi($t['name_en'], $t['name_ar']) ?><br><span class="mini"><?= owBi($de, $da) ?> · <?= owBi($lEn, $lAr) ?></span></td>
  <td class="mini"><?= owSar((float) $t['amount']) ?><br><?= owBi('Oweili ', 'أويلي ') ?><?= owSar((float) $t['commission_amount']) ?> (<?= rtrim(rtrim((string) $t['commission_rate'], '0'), '.') ?>%)<?= (float) $t['refund_amount'] > 0 ? '<br>' . owBi('Refunded', 'مُسترد') : '' ?></td>
  <td><?= owPill(OW_TICKET_STATUS, (string) $t['status']) ?></td>
  <td><?php if ($t['status'] === 'paid'): ?><form method="post" action="admin_bookings.php#tickets" onsubmit="return confirm(document.documentElement.dir==='rtl'?'إلغاء هذه التذكرة؟':'Cancel this ticket?')"><?= csrfField() ?><input type="hidden" name="do" value="ticket_cancel"><input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
    <label class="mini"><input type="checkbox" name="refund" value="1" checked> <?= owBi('Refund', 'استرداد') ?></label><button class="btn btn-danger btn-sm"><?= owBi('Cancel', 'إلغاء') ?></button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table><?php endif; ?></div>

<h2 id="payouts"><?= owBi('Payouts to institutes and museums', 'مستحقات المعاهد والمتاحف') ?></h2>
<div class="card"><p class="mini"><?= owBi('Separate from the artists’ payouts. Pay by bank transfer, then write the transfer reference here.', 'منفصلة عن مستحقات الفنانين. ادفع بالتحويل البنكي ثم اكتب مرجع التحويل هنا.') ?></p>
<?php if (!$payouts): ?><p><?= owBi('Nothing yet', 'لا شيء بعد') ?></p><?php else: ?>
<table class="tbl"><thead><tr><th><?= owBi('To', 'إلى') ?></th><th><?= owBi('Amount', 'المبلغ') ?></th><th><?= owBi('When', 'متى') ?></th><th><?= owBi('Status', 'الحالة') ?></th><th></th></tr></thead><tbody>
<?php foreach ($payouts as $po): [$we, $wa] = owPayoutDueBi($po['due_on']); ?>
<tr><td><b><?= e((string) $po['payee_en']) ?></b><br><span class="mini"><?= $po['payee_kind'] === 'institute' ? owBi('Institute · course', 'معهد · دورة') : owBi('Museum · ticket', 'متحف · تذكرة') ?> #<?= (int) $po['source_id'] ?></span><?= $po['payee_note'] ? '<br><span class="mini">' . e($po['payee_note']) . '</span>' : '' ?></td>
  <td><?= owSar((float) $po['amount']) ?><br><span class="mini"><?= owBi('Commission ', 'العمولة ') ?><?= owSar((float) $po['commission']) ?></span></td>
  <td class="mini"><?= $po['status'] === 'paid' ? e(substr((string) $po['paid_at'], 0, 10)) : owBi($we, $wa) ?></td>
  <td><?= owPill(OW_PAYOUT_STATUS, (string) $po['status']) ?><?= $po['reference'] !== '' ? '<br><span class="mini" dir="ltr">' . e($po['reference']) . '</span>' : '' ?></td>
  <td><?php if ($po['status'] === 'pending'): ?><form method="post" action="admin_bookings.php#payouts"><?= csrfField() ?><input type="hidden" name="do" value="payout_paid"><input type="hidden" name="id" value="<?= (int) $po['id'] ?>"><input type="text" name="reference" placeholder="<?= e(owIsAr() ? 'مرجع التحويل' : 'Transfer reference') ?>" aria-label="<?= e('Transfer reference · مرجع التحويل') ?>"><button class="btn btn-secondary btn-sm"><?= owBi('Mark paid', 'تم الدفع') ?></button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table><?php endif; ?></div>

<h2 id="payments"><?= owBi('Payment providers (test mode only)', 'مزودو الدفع (وضع تجريبي فقط)') ?></h2>
<div class="card">
  <p><?= owBi('Every payment is a test: no real money moves. Oweili’s own test page always works. A provider shows on the payment page once its TEST keys are in config.php on the server (never in GitHub or chat).', 'كل دفعة تجريبية: لا ينتقل مال حقيقي. صفحة أويلي التجريبية تعمل دائما. ويظهر المزوّد في صفحة الدفع حين توضع مفاتيحه التجريبية في config.php على الخادم (لا في GitHub ولا في المحادثة).') ?></p>
  <ul class="rows">
    <li><span><?= owBi('Oweili test page', 'صفحة أويلي التجريبية') ?></span><span class="pillx on"><?= owBi('Ready', 'جاهزة') ?></span></li>
    <?php foreach (['moyasar' => 'Moyasar (mada, cards, Apple Pay)', 'tabby' => 'Tabby', 'tamara' => 'Tamara'] as $g => $n): ?>
    <li><span><?= e($n) ?></span><?= $keys[$g] === '' ? '<span class="pillx on">' . owBi('Test keys found', 'المفاتيح التجريبية موجودة') . '</span>' : '<span class="pillx warn" style="white-space:normal;overflow-wrap:anywhere;max-width:100%">' . owBi('Needs: ', 'يحتاج: ') . '<span dir="ltr">' . e($keys[$g]) . '</span></span>' ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php owAdminBottom();
