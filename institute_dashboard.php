<?php
/* =====================================================================
 *  institute_dashboard.php — an art institute's own dashboard: its profile,
 *  its courses (each new or changed course waits for the owner's approval),
 *  its registrations, and its earnings and payouts. Only its own data.
 *  HIDDEN until the bookings switch is on (booking_lib.php).
 *  Tables: users, institutes, courses, course_registrations, partner_payouts, activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/booking_lib.php';
$preview = owBookingGate();
if (!isLoggedIn()) { header('Location: login.php?next=institute_dashboard.php'); exit; }
$me = currentUser();
if (!$me || ($me['role'] ?? '') !== 'institute') { header('Location: ' . dashboardUrl($me['role'] ?? '')); exit; }
$inst = owMyInstitute((int) $me['id']);
if (!$inst) { http_response_code(404); require __DIR__ . '/404.php'; exit; }
$iid = (int) $inst['id']; $db = getDB();
$actor = ['type' => 'institute', 'id' => (int) $me['id']];
$TABS = ['courses' => ['Courses', 'الدورات'], 'registrations' => ['Registrations', 'التسجيلات'], 'money' => ['Earnings and payouts', 'الأرباح والمستحقات'], 'profile' => ['Profile', 'الملف']];
$tab = isset($TABS[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'courses';
$err = []; $done = (string) ($_GET['done'] ?? '');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) $err[] = ['The page was open too long. Please try again.', 'انتهت صلاحية الصفحة. حاول مرة أخرى.'];
    elseif (($_POST['do'] ?? '') === 'course') {
        $cid = (int) ($_POST['id'] ?? 0); $tab = 'courses';
        $old = null;
        if ($cid) { $st = $db->prepare('SELECT * FROM courses WHERE id = :id AND institute_id = :i AND bookable = 1'); $st->execute([':id' => $cid, ':i' => $iid]); $old = $st->fetch(); if (!$old) $err[] = ['Course not found.', 'الدورة غير موجودة.']; }
        [$v, $e2] = owCourseFromPost($_POST); $err = array_merge($err, $e2);
        $held = 0;
        if ($old) { $h = $db->prepare("SELECT COUNT(*) FROM course_registrations WHERE course_id = :c AND status IN ('pending','confirmed')"); $h->execute([':c' => $cid]); $held = (int) $h->fetchColumn(); }
        if ($old && $v['seats_total'] < $held) $err[] = ["Seats cannot be fewer than the $held students already registered.", "لا يمكن أن تقل المقاعد عن $held طلاب مسجلين بالفعل."];
        if (!$err) {
            $v['seats_remaining'] = $v['seats_total'] - $held;
            $v['status'] = 'pending'; $v['reject_reason'] = '';
            $cols = array_keys($v); $args = []; foreach ($v as $k => $x) $args[':' . $k] = $x;
            if ($old) $db->prepare('UPDATE courses SET ' . implode(', ', array_map(fn($c) => "$c = :$c", $cols)) . ' WHERE id = :id AND institute_id = :iid')->execute($args + [':id' => $cid, ':iid' => $iid]);
            else { $db->prepare('INSERT INTO courses (institute_id, bookable, is_active, ' . implode(', ', $cols) . ') VALUES (:iid, 1, 1, ' . implode(', ', array_map(fn($c) => ":$c", $cols)) . ')')->execute($args + [':iid' => $iid]); $cid = (int) $db->lastInsertId(); }
            owLog('course_submit', 'course', $cid, $v['title_en'], $actor);
            require_once __DIR__ . '/alert_lib.php';
            owAdminAlert('booking', 'Course waiting for approval: ' . $v['title_en'], 'دورة بانتظار الموافقة: ' . $v['title_ar'], 'admin_bookings.php#courses');
            header('Location: institute_dashboard.php?tab=courses&done=course'); exit;
        }
    } elseif (($_POST['do'] ?? '') === 'hide') {
        $db->prepare('UPDATE courses SET is_active = :a WHERE id = :id AND institute_id = :i AND bookable = 1')->execute([':a' => !empty($_POST['show']) ? 1 : 0, ':id' => (int) ($_POST['id'] ?? 0), ':i' => $iid]);
        owLog('course_' . (!empty($_POST['show']) ? 'show' : 'hide'), 'course', (int) ($_POST['id'] ?? 0), '', $actor);
        header('Location: institute_dashboard.php?tab=courses'); exit;
    } elseif (($_POST['do'] ?? '') === 'profile') {
        $tab = 'profile';
        $t = fn(string $k, int $m) => mb_substr(trim((string) ($_POST[$k] ?? '')), 0, $m);
        $p = ['description_en' => $t('description_en', 1500), 'description_ar' => $t('description_ar', 1500), 'contact_phone' => $t('contact_phone', 30), 'website_url' => $t('website_url', 200), 'payout_note' => $t('payout_note', 500)];
        if ($p['contact_phone'] !== '' && !preg_match('/^[+0-9][0-9 \-()]{6,29}$/', $p['contact_phone'])) $err[] = ['Please check the phone number.', 'يرجى مراجعة رقم الهاتف.'];
        if ($p['website_url'] !== '') { [$l, $ok] = owSafeLink($p['website_url'], 'web'); if ($ok) $p['website_url'] = $l; else $err[] = ['Please check the website address.', 'يرجى مراجعة عنوان الموقع.']; }
        if (!$err) {
            $db->prepare('UPDATE institutes SET description_en = :de, description_ar = :da, contact_phone = :ph, website_url = :w, payout_note = :pn WHERE id = :id')
               ->execute([':de' => $p['description_en'], ':da' => $p['description_ar'], ':ph' => $p['contact_phone'], ':w' => $p['website_url'], ':pn' => $p['payout_note'], ':id' => $iid]);
            owLog('institute_profile', 'institute', $iid, '', $actor);
            require_once __DIR__ . '/alert_lib.php';
            owAdminAlert('booking', 'Institute profile changed: ' . $inst['name_en'], 'تغيّر ملف المعهد: ' . $inst['name_ar'], 'admin_bookings.php#institutes');
            header('Location: institute_dashboard.php?tab=profile&done=profile'); exit;
        }
    }
}
$st = $db->prepare('SELECT * FROM courses WHERE institute_id = :i AND bookable = 1 ORDER BY start_date DESC, id DESC'); $st->execute([':i' => $iid]); $courses = $st->fetchAll();
$st = $db->prepare("SELECT r.*, c.title_en, c.title_ar, c.start_date FROM course_registrations r JOIN courses c ON c.id = r.course_id
                    WHERE c.institute_id = :i AND c.bookable = 1 AND r.status IN ('confirmed','cancelled') AND (r.paid_at IS NOT NULL OR r.status = 'confirmed') ORDER BY r.id DESC LIMIT 500");
$st->execute([':i' => $iid]); $regs = $st->fetchAll();
$payouts = owPayoutsFor('institute', $iid); $tot = owPayoutTotals($payouts);
$confirmed = count(array_filter($regs, fn($r) => $r['status'] === 'confirmed'));
$edit = null;
if (isset($_GET['edit'])) { foreach ($courses as $c) if ((int) $c['id'] === (int) $_GET['edit']) $edit = $c; $tab = 'courses'; }
$newForm = isset($_GET['new']) || ($err && ($_POST['do'] ?? '') === 'course');
$f = function (string $k) use ($edit) { return e((string) ($_POST[$k] ?? ($edit[$k] ?? ''))); };
$sel = function (string $k, string $val, string $def) use ($edit) { $cur = (string) ($_POST[$k] ?? ($edit[$k] ?? $def)); return $cur === $val ? ' selected' : ''; };

owBookTop('My institute', 'معهدي', 'institute_dashboard.php', $preview, '
.bk{max-width:1100px;}
.stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:var(--space-sm);margin-top:var(--space-sm);}
.stat-row .card{margin:0;}
.stat-row b{display:block;font-size:24px;}
.tabs{display:flex;gap:6px;flex-wrap:wrap;margin-top:var(--space-md);}
.tbl{width:100%;border-collapse:collapse;font-size:15px;}
.tbl th,.tbl td{padding:8px;border-bottom:1px solid var(--color-border);text-align:start;vertical-align:top;}
.cf textarea{min-height:90px;}
@media(max-width:700px){.stat-row{grid-template-columns:1fr 1fr;}.tbl thead{display:none;}.tbl td{display:block;border:0;padding:2px 0;}.tbl tr{display:block;border-bottom:1px solid var(--color-border);padding:8px 0;}}
', true, 'role-institute');
?>
<div class="card">
  <p class="mini" style="margin:0"><?= owBi('My institute', 'معهدي') ?></p>
  <h1><?= owBi($inst['name_en'], $inst['name_ar']) ?></h1>
  <?php if (!(int) $me['is_verified']): ?><div class="alert alert-error" role="status"><?= owBi('Please confirm your email (check your inbox).', 'يرجى تأكيد بريدك (راجع صندوق الوارد).') ?></div><?php endif; ?>
  <?php if (!(int) $me['is_approved'] || !(int) $inst['is_active']): ?><div class="alert alert-error" role="status"><?= owBi('Your institute is waiting for the Oweili team’s review. You can already add courses; they show once both are approved.', 'معهدك بانتظار مراجعة فريق أويلي. يمكنك إضافة الدورات الآن، وتظهر بعد اعتماد المعهد والدورة.') ?></div><?php endif; ?>
  <?php if ($done === 'course'): ?><div class="alert alert-ok" role="status"><?= owBi('Saved. The course waits for the Oweili team’s approval before it shows.', 'تم الحفظ. تنتظر الدورة موافقة فريق أويلي قبل ظهورها.') ?></div><?php endif; ?>
  <?php if ($done === 'profile'): ?><div class="alert alert-ok" role="status"><?= owBi('Profile saved.', 'تم حفظ الملف.') ?></div><?php endif; ?>
  <?php foreach ($err as $x): ?><div class="alert alert-error" role="alert"><?= owBi($x[0], $x[1]) ?></div><?php endforeach; ?>
</div>
<div class="stat-row">
  <div class="card"><span class="mini"><?= owBi('Courses', 'الدورات') ?></span><b><?= count($courses) ?></b></div>
  <div class="card"><span class="mini"><?= owBi('Registrations', 'التسجيلات') ?></span><b><?= $confirmed ?></b></div>
  <div class="card"><span class="mini"><?= owBi('Earned (after commission)', 'المكتسب (بعد العمولة)') ?></span><b><?= owSar($tot['earned']) ?></b></div>
  <div class="card"><span class="mini"><?= owBi('Paid to you', 'دُفع لك') ?></span><b><?= owSar($tot['paid']) ?></b></div>
</div>
<nav class="tabs" aria-label="<?= e('Sections · الأقسام') ?>">
  <?php foreach ($TABS as $k => $w): ?><a class="btn btn-sm <?= $tab === $k ? 'btn-dark' : 'btn-secondary' ?>" href="institute_dashboard.php?tab=<?= $k ?>"<?= $tab === $k ? ' aria-current="page"' : '' ?>><?= owBi($w[0], $w[1]) ?></a><?php endforeach; ?>
</nav>

<?php if ($tab === 'courses'): ?>
  <?php if ($edit || $newForm): ?>
  <form method="post" action="institute_dashboard.php?tab=courses<?= $edit ? '&edit=' . (int) $edit['id'] : '&new=1' ?>" class="card cf"><?= csrfField() ?>
    <input type="hidden" name="do" value="course"><input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
    <h2><?= $edit ? owBi('Edit course', 'تعديل الدورة') : owBi('New course', 'دورة جديدة') ?></h2>
    <?php if ($edit): ?><p class="mini"><?= owBi('Any change sends the course back for approval.', 'أي تعديل يعيد الدورة للموافقة.') ?></p><?php endif; ?>
    <div class="g2">
      <div class="field"><label for="c-te"><?= owBi('Title (English)', 'العنوان (بالإنجليزية)') ?></label><input id="c-te" name="title_en" required maxlength="200" dir="ltr" value="<?= $f('title_en') ?>"></div>
      <div class="field"><label for="c-ta"><?= owBi('Title (Arabic)', 'العنوان (بالعربية)') ?></label><input id="c-ta" name="title_ar" required maxlength="200" dir="rtl" value="<?= $f('title_ar') ?>"></div>
      <div class="field"><label for="c-de"><?= owBi('Short description (English)', 'وصف قصير (بالإنجليزية)') ?></label><textarea id="c-de" name="description_en" required maxlength="600" dir="ltr"><?= $f('description_en') ?></textarea></div>
      <div class="field"><label for="c-da"><?= owBi('Short description (Arabic)', 'وصف قصير (بالعربية)') ?></label><textarea id="c-da" name="description_ar" required maxlength="600" dir="rtl"><?= $f('description_ar') ?></textarea></div>
      <div class="field"><label for="c-le"><?= owBi('What students learn (English)', 'ماذا يتعلم الطلاب (بالإنجليزية)') ?></label><textarea id="c-le" name="learn_en" required maxlength="2000" dir="ltr"><?= $f('learn_en') ?></textarea></div>
      <div class="field"><label for="c-la"><?= owBi('What students learn (Arabic)', 'ماذا يتعلم الطلاب (بالعربية)') ?></label><textarea id="c-la" name="learn_ar" required maxlength="2000" dir="rtl"><?= $f('learn_ar') ?></textarea></div>
      <div class="field"><label for="c-ty"><?= owBi('Type', 'النوع') ?></label><select id="c-ty" name="course_type"><?php foreach (OW_COURSE_TYPES as $k => $w): ?><option value="<?= $k ?>"<?= $sel('course_type', $k, 'in_person') ?>><?= e(owIsAr() ? $w[1] : $w[0]) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="c-lv"><?= owBi('Level', 'المستوى') ?></label><select id="c-lv" name="level"><?php foreach (OW_COURSE_LEVELS as $k => $w): ?><option value="<?= $k ?>"<?= $sel('level', $k, 'beginner') ?>><?= e(owIsAr() ? $w[1] : $w[0]) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="c-sd"><?= owBi('Start date', 'تاريخ البداية') ?></label><input id="c-sd" type="date" name="start_date" required value="<?= $f('start_date') ?>"></div>
      <div class="field"><label for="c-ed"><?= owBi('End date', 'تاريخ النهاية') ?></label><input id="c-ed" type="date" name="end_date" required value="<?= $f('end_date') ?>"></div>
      <div class="field"><label for="c-se"><?= owBi('Days and times (English), e.g. Sun and Tue, 5 to 7 pm', 'الأيام والأوقات (بالإنجليزية)') ?></label><input id="c-se" name="schedule_en" required maxlength="300" dir="ltr" value="<?= $f('schedule_en') ?>"></div>
      <div class="field"><label for="c-sa"><?= owBi('Days and times (Arabic)', 'الأيام والأوقات (بالعربية)، مثل: الأحد والثلاثاء، 5 إلى 7 مساءً') ?></label><input id="c-sa" name="schedule_ar" required maxlength="300" dir="rtl" value="<?= $f('schedule_ar') ?>"></div>
      <div class="field"><label for="c-h"><?= owBi('Total hours', 'إجمالي الساعات') ?></label><input id="c-h" name="total_hours" required inputmode="decimal" dir="ltr" maxlength="6" value="<?= $f('total_hours') ?>"></div>
      <div class="field"><label for="c-p"><?= owBi('Price (SAR, 0 = free)', 'السعر (ر.س، 0 = مجاني)') ?></label><input id="c-p" name="price" required inputmode="decimal" dir="ltr" maxlength="10" value="<?= $f('price') ?>"></div>
      <div class="field"><label for="c-st"><?= owBi('Seats', 'المقاعد') ?></label><input id="c-st" name="seats_total" required inputmode="numeric" dir="ltr" maxlength="5" value="<?= $f('seats_total') ?>"></div>
      <div class="field"><label for="c-lg"><?= owBi('Language', 'اللغة') ?></label><select id="c-lg" name="course_lang"><?php foreach (OW_COURSE_LANGS as $k => $w): ?><option value="<?= $k ?>"<?= $sel('course_lang', $k, 'ar') ?>><?= e(owIsAr() ? $w[1] : $w[0]) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="c-a1"><?= owBi('Youngest age (optional)', 'أصغر عمر (اختياري)') ?></label><input id="c-a1" name="age_min" inputmode="numeric" dir="ltr" maxlength="2" value="<?= $f('age_min') ?>"></div>
      <div class="field"><label for="c-a2"><?= owBi('Oldest age (optional)', 'أكبر عمر (اختياري)') ?></label><input id="c-a2" name="age_max" inputmode="numeric" dir="ltr" maxlength="2" value="<?= $f('age_max') ?>"></div>
      <div class="field"><label for="c-loe"><?= owBi('Location (English), for in-person', 'المكان (بالإنجليزية)، للدورات الحضورية') ?></label><input id="c-loe" name="location_en" maxlength="300" dir="ltr" value="<?= $f('location_en') ?>"></div>
      <div class="field"><label for="c-loa"><?= owBi('Location (Arabic), for in-person', 'المكان (بالعربية)، للدورات الحضورية') ?></label><input id="c-loa" name="location_ar" maxlength="300" dir="rtl" value="<?= $f('location_ar') ?>"></div>
    </div>
    <div class="field"><label for="c-ml"><?= owBi('Meeting link, for online (only registered students see it)', 'رابط الاجتماع، للدورات عن بُعد (يراه المسجلون فقط)') ?></label><input id="c-ml" name="meeting_link" maxlength="500" dir="ltr" placeholder="https://" value="<?= $f('meeting_link') ?>"></div>
    <label class="chk" style="display:flex;gap:6px;align-items:center;margin:0 0 var(--space-sm)"><input type="checkbox" name="materials_included" value="1"<?= (int) ($_POST['materials_included'] ?? ($edit['materials_included'] ?? 0)) ? ' checked' : '' ?>> <?= owBi('Materials are included in the price', 'المواد مشمولة في السعر') ?></label>
    <div class="acts"><button class="btn btn-primary" type="submit"><?= owBi('Send for approval', 'أرسل للموافقة') ?></button><a class="btn btn-ghost" href="institute_dashboard.php?tab=courses"><?= owBi('Cancel', 'إلغاء') ?></a></div>
  </form>
  <?php else: ?>
  <div class="card"><a class="btn btn-primary" href="institute_dashboard.php?tab=courses&new=1">+ <?= owBi('New course', 'دورة جديدة') ?></a></div>
  <?php endif; ?>
  <div class="card">
    <?php if (!$courses): ?><p><?= owBi('No courses yet. Add your first course.', 'لا دورات بعد. أضف دورتك الأولى.') ?></p><?php else: ?>
    <table class="tbl"><thead><tr><th><?= owBi('Course', 'الدورة') ?></th><th><?= owBi('Dates', 'التواريخ') ?></th><th><?= owBi('Seats', 'المقاعد') ?></th><th><?= owBi('Status', 'الحالة') ?></th><th></th></tr></thead><tbody>
    <?php foreach ($courses as $c): [$de, $da] = owDateRangeBi($c['start_date'], $c['end_date']); ?>
      <tr><td><b><?= owBi($c['title_en'], $c['title_ar']) ?></b><br><span class="mini"><?= (float) $c['price'] > 0 ? owSar((float) $c['price']) : owBi('Free', 'مجاني') ?></span></td>
        <td><?= owBi($de, $da) ?></td>
        <td><?= (int) $c['seats_total'] - (int) $c['seats_remaining'] ?> / <?= (int) $c['seats_total'] ?></td>
        <td><?= owPill(OW_COURSE_STATUS, (string) $c['status']) ?><?= !(int) $c['is_active'] ? ' <span class="pillx">' . owBi('Hidden', 'مخفية') . '</span>' : '' ?>
          <?php if ($c['status'] === 'rejected' && $c['reject_reason'] !== ''): ?><br><span class="mini"><?= e($c['reject_reason']) ?></span><?php endif; ?></td>
        <td><div class="acts" style="margin:0"><a class="btn btn-secondary btn-sm" href="institute_dashboard.php?tab=courses&edit=<?= (int) $c['id'] ?>"><?= owBi('Edit', 'تعديل') ?></a>
          <a class="btn btn-ghost btn-sm" href="course.php?id=<?= (int) $c['id'] ?>"><?= owBi('View', 'عرض') ?></a>
          <form method="post" action="institute_dashboard.php?tab=courses"><?= csrfField() ?><input type="hidden" name="do" value="hide"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><?php if (!(int) $c['is_active']): ?><input type="hidden" name="show" value="1"><?php endif; ?>
            <button class="btn btn-ghost btn-sm"><?= (int) $c['is_active'] ? owBi('Hide', 'إخفاء') : owBi('Show', 'إظهار') ?></button></form></div></td></tr>
    <?php endforeach; ?></tbody></table>
    <?php endif; ?>
  </div>

<?php elseif ($tab === 'registrations'): ?>
  <div class="card">
    <?php if (!$regs): ?><p><?= owBi('No registrations yet.', 'لا تسجيلات بعد.') ?></p><?php else: ?>
    <table class="tbl"><thead><tr><th><?= owBi('Student', 'الطالب') ?></th><th><?= owBi('Course', 'الدورة') ?></th><th><?= owBi('Paid', 'المدفوع') ?></th><th><?= owBi('Status', 'الحالة') ?></th></tr></thead><tbody>
    <?php foreach ($regs as $r): ?>
      <tr><td><b><?= e($r['student_name']) ?></b><br><span class="mini" dir="ltr"><?= e($r['student_phone']) ?> · <?= e($r['student_email']) ?></span><br><span class="mini" dir="ltr"><?= e($r['ref_no']) ?></span></td>
        <td><?= owBi($r['title_en'], $r['title_ar']) ?></td>
        <td><?= (float) $r['amount'] > 0 ? owSar((float) $r['amount']) : owBi('Free', 'مجاني') ?><?= (float) $r['refund_amount'] > 0 ? '<br><span class="mini">' . owBi('Refunded', 'مُسترد') . '</span>' : '' ?></td>
        <td><?= owPill(OW_REG_STATUS, (string) $r['status']) ?></td></tr>
    <?php endforeach; ?></tbody></table>
    <p class="mini"><?= owBi('Use these details only for this course. To cancel a registration, write to Oweili.', 'استخدم هذه البيانات لهذه الدورة فقط. لإلغاء تسجيل، راسل أويلي.') ?></p>
    <?php endif; ?>
  </div>

<?php elseif ($tab === 'money'): ?>
  <div class="card">
    <h2><?= owBi('How you are paid', 'كيف تُدفع مستحقاتك') ?></h2>
    <p><?= owBi('For each registration Oweili keeps ' . rtrim(rtrim(number_format(owCourseCommission(), 2), '0'), '.') . '% (before VAT). The rest is due to you once the course has started (on its start date). Oweili pays by bank transfer.',
                'يحتفظ أويلي بـ ' . rtrim(rtrim(number_format(owCourseCommission(), 2), '0'), '.') . '٪ من كل تسجيل (قبل الضريبة). والباقي يستحق لك بعد بداية الدورة (في تاريخ بدايتها). يدفع أويلي بالتحويل البنكي.') ?></p>
    <div class="stat-row">
      <div class="card"><span class="mini"><?= owBi('Earned', 'المكتسب') ?></span><b><?= owSar($tot['earned']) ?></b></div>
      <div class="card"><span class="mini"><?= owBi('Due now', 'مستحق الآن') ?></span><b><?= owSar($tot['due']) ?></b></div>
      <div class="card"><span class="mini"><?= owBi('Waiting for the course to start', 'بانتظار بداية الدورة') ?></span><b><?= owSar($tot['waiting']) ?></b></div>
      <div class="card"><span class="mini"><?= owBi('Paid to you', 'دُفع لك') ?></span><b><?= owSar($tot['paid']) ?></b></div>
    </div>
  </div>
  <div class="card">
    <?php if (!$payouts): ?><p><?= owBi('Nothing yet.', 'لا شيء بعد.') ?></p><?php else: ?>
    <table class="tbl"><thead><tr><th><?= owBi('For', 'مقابل') ?></th><th><?= owBi('Your share', 'حصتك') ?></th><th><?= owBi('When', 'متى') ?></th><th><?= owBi('Status', 'الحالة') ?></th></tr></thead><tbody>
    <?php $regById = []; foreach ($regs as $r) $regById[(int) $r['id']] = $r;
      foreach ($payouts as $po): $r = $regById[(int) $po['source_id']] ?? null; [$we, $wa] = owPayoutDueBi($po['due_on']); ?>
      <tr><td><?= $r ? owBi($r['title_en'], $r['title_ar']) . '<br><span class="mini" dir="ltr">' . e($r['ref_no']) . '</span>' : '#' . (int) $po['source_id'] ?></td>
        <td><?= owSar((float) $po['amount']) ?><br><span class="mini"><?= owBi('Commission ', 'العمولة ') ?><?= owSar((float) $po['commission']) ?></span></td>
        <td><?= $po['status'] === 'paid' ? owBi('Paid ' . substr((string) $po['paid_at'], 0, 10), 'دُفع ' . substr((string) $po['paid_at'], 0, 10)) : owBi($we, $wa) ?></td>
        <td><?= owPill(OW_PAYOUT_STATUS, (string) $po['status']) ?><?= $po['reference'] !== '' ? '<br><span class="mini" dir="ltr">' . e($po['reference']) . '</span>' : '' ?></td></tr>
    <?php endforeach; ?></tbody></table><?php endif; ?>
  </div>

<?php else: ?>
  <form method="post" action="institute_dashboard.php?tab=profile" class="card"><?= csrfField() ?><input type="hidden" name="do" value="profile">
    <p class="mini"><?= owBi('The name and city are changed by the Oweili team. Write to us if they need a change.', 'يغيّر فريق أويلي الاسم والمدينة. راسلنا إن احتاجا تعديلاً.') ?></p>
    <dl class="kv" style="margin-bottom:var(--space-sm)"><dt><?= owBi('Name', 'الاسم') ?></dt><dd><?= owBi($inst['name_en'], $inst['name_ar']) ?></dd><dt><?= owBi('City', 'المدينة') ?></dt><dd><?= owBi($inst['city_en'], $inst['city_ar']) ?></dd><dt><?= owBi('Sign-in email', 'بريد الدخول') ?></dt><dd dir="ltr"><?= e($me['email']) ?></dd></dl>
    <div class="g2">
      <div class="field"><label for="p-de"><?= owBi('Short description (English)', 'وصف قصير (بالإنجليزية)') ?></label><textarea id="p-de" name="description_en" rows="4" maxlength="1500" dir="ltr"><?= e($_POST['description_en'] ?? $inst['description_en']) ?></textarea></div>
      <div class="field"><label for="p-da"><?= owBi('Short description (Arabic)', 'وصف قصير (بالعربية)') ?></label><textarea id="p-da" name="description_ar" rows="4" maxlength="1500" dir="rtl"><?= e($_POST['description_ar'] ?? $inst['description_ar']) ?></textarea></div>
      <div class="field"><label for="p-ph"><?= owBi('Phone', 'الهاتف') ?></label><input id="p-ph" name="contact_phone" type="tel" maxlength="30" dir="ltr" value="<?= e($_POST['contact_phone'] ?? $inst['contact_phone']) ?>"></div>
      <div class="field"><label for="p-w"><?= owBi('Website', 'الموقع') ?></label><input id="p-w" name="website_url" maxlength="200" dir="ltr" value="<?= e($_POST['website_url'] ?? $inst['website_url']) ?>"></div>
    </div>
    <div class="field"><label for="p-pn"><?= owBi('Payment details for payouts (bank name and account holder; we will ask for the IBAN by phone)', 'بيانات استلام المستحقات (اسم البنك وصاحب الحساب؛ نطلب رقم الآيبان هاتفياً)') ?></label><input id="p-pn" name="payout_note" maxlength="500" dir="auto" value="<?= e($_POST['payout_note'] ?? $inst['payout_note']) ?>"></div>
    <button class="btn btn-primary" type="submit"><?= owBi('Save', 'حفظ') ?></button>
  </form>
<?php endif; ?>
<?php owBookBottom();
