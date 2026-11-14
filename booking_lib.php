<?php
/* =====================================================================
 *  booking_lib.php — course booking (institutes) and ticket sales
 *  (partner museums). Owner, 14 Nov 2026.
 *
 *  HIDDEN behind ONE switch: Admin → Settings → "Hidden for now" →
 *  "Course booking and museum tickets" (site_settings 'bookings_on').
 *  While it is off every public page here answers the normal 404
 *  (a signed-in admin sees a preview), the Art Map shows nothing new,
 *  and the Terms do not show the booking rules.
 *
 *  Payments: TEST MODE ONLY (pay_lib.php). No real money moves.
 *
 *  Three payout rules, kept apart on purpose:
 *    Artworks  order_lib.php (escrow, released after delivery; untouched here)
 *    Courses   the institute is paid once the course has started
 *              (due on the start date), minus the course commission
 *              (site_settings 'course_commission', 10 by default)
 *    Museums   per museum, by agreement (art_places.payout_rule / payout_days /
 *              payout_note), minus that museum's own commission
 *              (art_places.ticket_commission)
 *  Money for institutes and museums goes in partner_payouts, never in the
 *  artists' payouts table.
 *  Tables: courses, course_registrations, institutes, art_places,
 *          museum_ticket_types, museum_ticket_dates, museum_tickets,
 *          booking_payments, partner_payouts, users, site_settings, activity_log
 * ===================================================================== */
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/gallery_lib.php';   // owSetting, owPartOn, owHiddenPageGate, owBi
require_once __DIR__ . '/article_lib.php';   // owFormDate, owDateRangeBi
require_once __DIR__ . '/request_lib.php';   // owSiteBase, owReqTellBuyer
require_once __DIR__ . '/mail_lib.php';
require_once __DIR__ . '/admin_shell.php';   // owIsAdmin
require_once __DIR__ . '/lang_lib.php';     // owIsAr

const OW_BOOK_HOLD_MIN = 30;   // a seat or a ticket is held this long while the payment is made
const OW_COURSE_TYPES  = ['in_person' => ['In person', 'حضوري'], 'online' => ['Online', 'عن بُعد']];
const OW_COURSE_LEVELS = ['beginner' => ['Beginner', 'مبتدئ'], 'intermediate' => ['Intermediate', 'متوسط'], 'advanced' => ['Advanced', 'متقدم']];
const OW_COURSE_LANGS  = ['ar' => ['Arabic', 'العربية'], 'en' => ['English', 'الإنجليزية'], 'both' => ['Arabic and English', 'العربية والإنجليزية']];
const OW_COURSE_STATUS = ['pending' => ['Waiting for approval', 'بانتظار الموافقة'], 'approved' => ['Approved', 'معتمدة'], 'rejected' => ['Not approved', 'غير معتمدة']];
const OW_REG_STATUS    = ['pending' => ['Waiting for payment', 'بانتظار الدفع'], 'confirmed' => ['Confirmed', 'مؤكد'], 'cancelled' => ['Cancelled', 'ملغى']];
const OW_TICKET_STATUS = ['pending' => ['Waiting for payment', 'بانتظار الدفع'], 'paid' => ['Valid', 'صالحة'], 'used' => ['Used', 'مستخدمة'],
                          'cancelled' => ['Cancelled', 'ملغاة'], 'expired' => ['Not paid in time', 'لم تُدفع في الوقت']];
const OW_PAYOUT_RULES  = ['after_visit' => ['A set number of days after the visit date', 'بعد تاريخ الزيارة بعدد أيام محدد'],
                          'month_end'   => ['Once a month, after the month of the visit', 'مرة في الشهر، بعد انتهاء شهر الزيارة'],
                          'agreement'   => ['By hand, as written in the agreement', 'يدوياً، كما في الاتفاقية']];
const OW_PAYOUT_STATUS = ['pending' => ['Waiting', 'بالانتظار'], 'paid' => ['Paid', 'مدفوع'], 'cancelled' => ['Cancelled', 'ملغى']];

/* ---------------- the one switch ---------------- */
function owBookingsOn(): bool { return owPartOn('bookings'); }
function owBookingsReady(): bool {
    return owHasTable('booking_payments') && owHasTable('partner_payouts') && owHasTable('museum_tickets')
        && owHasColumn('courses', 'bookable') && owHasColumn('art_places', 'partner');
}
/* For public pages: 404 to visitors while hidden; a signed-in admin gets a preview (true) */
function owBookingGate(): bool {
    if (!owBookingsReady()) { require_once __DIR__ . '/page_cache.php'; http_response_code(404); require __DIR__ . '/404.php'; exit; }
    return owHiddenPageGate('bookings');
}
/* For the institute and museum dashboards and the sign-up: same rule */
function owBookingAccountGate(): bool { return owBookingGate(); }

/* ---------------- money ---------------- */
function owCourseCommission(): float {
    $v = (float) owSetting('course_commission', '10');
    return ($v >= 0 && $v <= 50) ? $v : 10.0;
}
function owSplit(float $amount, float $rate): array {
    $fee = round($amount * $rate / 100, 2);
    return ['rate' => $rate, 'commission' => $fee, 'partner' => round($amount - $fee, 2)];
}
function owSar(float $v): string {
    $n = number_format($v, $v == floor($v) ? 0 : 2);
    return owBi($n . ' SAR', $n . ' ر.س');
}
function owBookRef(string $prefix, string $table): string {
    $abc = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    for ($i = 0; $i < 20; $i++) {
        $n = $prefix . '-' . date('y') . '-'; for ($j = 0; $j < 6; $j++) $n .= $abc[random_int(0, 31)];
        $st = getDB()->prepare("SELECT 1 FROM $table WHERE ref_no = :n"); $st->execute([':n' => $n]);
        if (!$st->fetchColumn()) return $n;
    }
    throw new RuntimeException('no free booking number');
}
function owPill(array $map, string $s): string {
    $w = $map[$s] ?? [$s, $s];
    $cls = in_array($s, ['pending', 'expired'], true) ? ' warn' : (in_array($s, ['approved', 'confirmed', 'paid'], true) ? ' on' : '');
    return '<span class="pillx' . $cls . '">' . owBi($w[0], $w[1]) . '</span>';
}

/* ---------------- holds that ran out ---------------- */
function owBookingCleanup(): void {
    if (!owBookingsReady()) return;
    static $done = false; if ($done) return; $done = true;
    $db = getDB();
    try {
        foreach ($db->query("SELECT id, course_id FROM course_registrations WHERE status = 'pending' AND hold_until IS NOT NULL AND hold_until < NOW() LIMIT 200")->fetchAll() as $r) {
            $db->beginTransaction();
            $n = $db->prepare("UPDATE course_registrations SET status = 'cancelled', cancelled_at = NOW(), hold_until = NULL WHERE id = :id AND status = 'pending'");
            $n->execute([':id' => $r['id']]);
            if ($n->rowCount()) $db->prepare('UPDATE courses SET seats_remaining = LEAST(seats_total, seats_remaining + 1) WHERE id = :c')->execute([':c' => $r['course_id']]);
            $db->commit();
        }
        foreach ($db->query("SELECT id, date_id, people FROM museum_tickets WHERE status = 'pending' AND hold_until IS NOT NULL AND hold_until < NOW() LIMIT 200")->fetchAll() as $t) {
            $db->beginTransaction();
            $n = $db->prepare("UPDATE museum_tickets SET status = 'expired', hold_until = NULL WHERE id = :id AND status = 'pending'");
            $n->execute([':id' => $t['id']]);
            if ($n->rowCount()) $db->prepare('UPDATE museum_ticket_dates SET sold = GREATEST(0, CAST(sold AS SIGNED) - :p) WHERE id = :d')->execute([':p' => (int) $t['people'], ':d' => $t['date_id']]);
            $db->commit();
        }
    } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); error_log('owBookingCleanup: ' . $e->getMessage()); }
}

/* ---------------- institutes and courses ---------------- */
function owMyInstitute(int $userId): ?array {
    $st = getDB()->prepare('SELECT * FROM institutes WHERE user_id = :u ORDER BY id LIMIT 1'); $st->execute([':u' => $userId]);
    return $st->fetch() ?: null;
}
function owCourseGet(int $id): ?array {
    $st = getDB()->prepare('SELECT c.*, i.name_en AS inst_en, i.name_ar AS inst_ar, i.city_en AS inst_city_en, i.city_ar AS inst_city_ar,
                                   i.logo_path AS inst_logo, i.contact_email AS inst_email, i.user_id AS inst_user, i.is_active AS inst_active
                            FROM courses c JOIN institutes i ON i.id = c.institute_id WHERE c.id = :id');
    $st->execute([':id' => $id]);
    return $st->fetch() ?: null;
}
/* Can a visitor see this course page and book it? */
function owCoursePublic(array $c): bool {
    return (int) $c['bookable'] === 1 && $c['status'] === 'approved' && (int) $c['is_active'] === 1 && (int) $c['inst_active'] === 1;
}
function owCourseOpen(array $c): bool {   // registration open: public, not started, seats left
    return owCoursePublic($c) && ($c['start_date'] === null || $c['start_date'] > date('Y-m-d')) && (int) $c['seats_remaining'] > 0;
}
/* The course form, shared by the institute dashboard: checks every field, returns [values, errors] */
function owCourseFromPost(array $p): array {
    $t = fn(string $k, int $max) => mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) ($p[$k] ?? ''))), 0, $max);
    $v = [
        'title_en' => $t('title_en', 200), 'title_ar' => $t('title_ar', 200),
        'description_en' => $t('description_en', 600), 'description_ar' => $t('description_ar', 600),
        'course_type' => isset(OW_COURSE_TYPES[$p['course_type'] ?? '']) ? $p['course_type'] : 'in_person',
        'level' => isset(OW_COURSE_LEVELS[$p['level'] ?? '']) ? $p['level'] : 'beginner',
        'learn_en' => $t('learn_en', 2000), 'learn_ar' => $t('learn_ar', 2000),
        'materials_included' => !empty($p['materials_included']) ? 1 : 0,
        'start_date' => owFormDate((string) ($p['start_date'] ?? '')), 'end_date' => owFormDate((string) ($p['end_date'] ?? '')),
        'schedule_en' => $t('schedule_en', 300), 'schedule_ar' => $t('schedule_ar', 300),
        'total_hours' => ($h = (float) str_replace(',', '.', (string) ($p['total_hours'] ?? ''))) > 0 ? min(9999, round($h, 1)) : null,
        'price' => max(0, round((float) str_replace(',', '', (string) ($p['price'] ?? '0')), 2)),
        'seats_total' => max(0, min(5000, (int) ($p['seats_total'] ?? 0))),
        'location_en' => $t('location_en', 300), 'location_ar' => $t('location_ar', 300),
        'meeting_link' => $t('meeting_link', 500),
        'course_lang' => isset(OW_COURSE_LANGS[$p['course_lang'] ?? '']) ? $p['course_lang'] : 'ar',
        'age_min' => ($a = (int) ($p['age_min'] ?? 0)) > 0 ? min(99, $a) : null,
        'age_max' => ($b = (int) ($p['age_max'] ?? 0)) > 0 ? min(99, $b) : null,
    ];
    $err = [];
    if ($v['title_en'] === '' || $v['title_ar'] === '') $err[] = ['The title is needed in Arabic and English.', 'العنوان مطلوب بالعربية والإنجليزية.'];
    if ($v['description_en'] === '' || $v['description_ar'] === '') $err[] = ['The short description is needed in Arabic and English.', 'الوصف القصير مطلوب بالعربية والإنجليزية.'];
    if ($v['learn_en'] === '' || $v['learn_ar'] === '') $err[] = ['Please write what students will learn, in Arabic and English.', 'يرجى كتابة ما سيتعلمه الطلاب، بالعربية والإنجليزية.'];
    if (!$v['start_date'] || !$v['end_date']) $err[] = ['Please choose the start and end dates.', 'يرجى اختيار تاريخ البداية والنهاية.'];
    elseif ($v['end_date'] < $v['start_date']) $err[] = ['The end date is before the start date.', 'تاريخ النهاية قبل تاريخ البداية.'];
    elseif ($v['start_date'] <= date('Y-m-d')) $err[] = ['The start date must be in the future.', 'يجب أن يكون تاريخ البداية في المستقبل.'];
    if ($v['schedule_en'] === '' || $v['schedule_ar'] === '') $err[] = ['Please write the days and times, in Arabic and English.', 'يرجى كتابة الأيام والأوقات، بالعربية والإنجليزية.'];
    if ($v['total_hours'] === null) $err[] = ['Please write the total hours.', 'يرجى كتابة إجمالي الساعات.'];
    if ($v['seats_total'] < 1) $err[] = ['Please write the number of seats.', 'يرجى كتابة عدد المقاعد.'];
    if ($v['course_type'] === 'in_person' && ($v['location_en'] === '' || $v['location_ar'] === '')) $err[] = ['Please write the location, in Arabic and English.', 'يرجى كتابة المكان، بالعربية والإنجليزية.'];
    if ($v['course_type'] === 'online') {
        if ($v['meeting_link'] === '') $err[] = ['Please add the meeting link (only registered students see it).', 'يرجى إضافة رابط الاجتماع (يراه الطلاب المسجلون فقط).'];
        else { [$l, $ok] = owSafeLink($v['meeting_link'], 'web'); if ($ok && str_starts_with($l, 'https://')) $v['meeting_link'] = $l; else $err[] = ['The meeting link must be a full https:// address.', 'يجب أن يكون رابط الاجتماع عنواناً كاملاً يبدأ بـ https://.']; }
    }
    if ($v['age_min'] !== null && $v['age_max'] !== null && $v['age_max'] < $v['age_min']) $err[] = ['The age range is the wrong way round.', 'نطاق العمر معكوس.'];
    return [$v, $err];
}
function owCourseAgeBi(array $c): array {
    $a = $c['age_min'] !== null ? (int) $c['age_min'] : 0; $b = $c['age_max'] !== null ? (int) $c['age_max'] : 0;
    if ($a && $b) return ["Ages $a to $b", "من $a إلى $b سنة"];
    if ($a) return ["Ages $a and up", "من $a سنة فأكثر"];
    if ($b) return ["Up to age $b", "حتى $b سنة"];
    return ['All ages', 'جميع الأعمار'];
}

/* Start a registration: holds one seat for OW_BOOK_HOLD_MIN minutes. Returns [regId, errorBi|null] */
function owCourseHold(int $courseId, array $user, string $name, string $email, string $phone): array {
    $db = getDB();
    try {
        $db->beginTransaction();
        $st = $db->prepare('SELECT * FROM courses WHERE id = :id FOR UPDATE'); $st->execute([':id' => $courseId]);
        $c = $st->fetch();
        $full = $c ? owCourseGet($courseId) : null;
        if (!$c || !$full || !owCoursePublic($full) || ($c['start_date'] !== null && $c['start_date'] <= date('Y-m-d'))) { $db->rollBack(); return [0, ['Registration for this course is closed.', 'التسجيل في هذه الدورة مغلق.']]; }
        $ex = $db->prepare('SELECT * FROM course_registrations WHERE course_id = :c AND user_id = :u FOR UPDATE');
        $ex->execute([':c' => $courseId, ':u' => (int) $user['id']]);
        $r = $ex->fetch();
        if ($r && $r['status'] === 'confirmed') { $db->rollBack(); return [(int) $r['id'], ['You are already registered for this course.', 'أنت مسجل في هذه الدورة بالفعل.']]; }
        $holding = $r && $r['status'] === 'pending';
        if (!$holding) {
            if ((int) $c['seats_remaining'] < 1) { $db->rollBack(); return [0, ['Sorry, this course is full.', 'عذراً، اكتمل العدد في هذه الدورة.']]; }
            $db->prepare('UPDATE courses SET seats_remaining = seats_remaining - 1 WHERE id = :id')->execute([':id' => $courseId]);
        }
        $split = owSplit((float) $c['price'], owCourseCommission());
        $args = [':n' => mb_substr($name, 0, 150), ':e' => mb_substr($email, 0, 190), ':p' => mb_substr($phone, 0, 30), ':am' => $split['commission'] + $split['partner'],
                 ':cr' => $split['rate'], ':ca' => $split['commission'], ':pa' => $split['partner']];
        if ($r) {
            $db->prepare("UPDATE course_registrations SET status = 'pending', student_name = :n, student_email = :e, student_phone = :p, amount = :am, commission_rate = :cr,
                          commission_amount = :ca, partner_amount = :pa, cancelled_at = NULL, refund_amount = 0, paid_at = NULL, payment_id = NULL,
                          hold_until = NOW() + INTERVAL " . OW_BOOK_HOLD_MIN . " MINUTE, ref_no = COALESCE(ref_no, :ref) WHERE id = :id")
               ->execute($args + [':ref' => owBookRef('C', 'course_registrations'), ':id' => (int) $r['id']]);
            $id = (int) $r['id'];
        } else {
            $db->prepare("INSERT INTO course_registrations (course_id, user_id, status, ref_no, student_name, student_email, student_phone, amount, commission_rate, commission_amount, partner_amount, hold_until)
                          VALUES (:c, :u, 'pending', :ref, :n, :e, :p, :am, :cr, :ca, :pa, NOW() + INTERVAL " . OW_BOOK_HOLD_MIN . " MINUTE)")
               ->execute($args + [':c' => $courseId, ':u' => (int) $user['id'], ':ref' => owBookRef('C', 'course_registrations')]);
            $id = (int) $db->lastInsertId();
        }
        $db->commit();
        owLog('course_book_start', 'course_registration', $id, 'course ' . $courseId, ['type' => owActorType($user['role'] ?? 'collector'), 'id' => (int) $user['id']]);
        return [$id, null];
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log('owCourseHold: ' . $e->getMessage());
        return [0, ['Something went wrong. Please try again.', 'حدث خطأ. يرجى المحاولة مرة أخرى.']];
    }
}

/* ---------------- museums and tickets ---------------- */
function owMuseumGet(int $id): ?array {
    if (!owBookingsReady()) return null;
    $st = getDB()->prepare("SELECT * FROM art_places WHERE id = :id AND kind = 'museum'"); $st->execute([':id' => $id]);
    return $st->fetch() ?: null;
}
function owMyMuseum(int $userId): ?array {
    $st = getDB()->prepare("SELECT * FROM art_places WHERE user_id = :u AND kind = 'museum' ORDER BY id LIMIT 1"); $st->execute([':u' => $userId]);
    return $st->fetch() ?: null;
}
function owMuseumTypes(int $placeId, bool $activeOnly = true): array {
    $st = getDB()->prepare('SELECT * FROM museum_ticket_types WHERE place_id = :p' . ($activeOnly ? ' AND is_active = 1' : '') . ' ORDER BY sort_order, id');
    $st->execute([':p' => $placeId]); return $st->fetchAll();
}
function owMuseumDates(int $placeId, bool $futureOnly = true): array {
    $st = getDB()->prepare('SELECT * FROM museum_ticket_dates WHERE place_id = :p' . ($futureOnly ? ' AND is_active = 1 AND visit_date >= CURDATE()' : '') . ' ORDER BY visit_date LIMIT 400');
    $st->execute([':p' => $placeId]); return $st->fetchAll();
}
function owMuseumSells(array $p): bool {   // a partner museum with at least one type and one date
    return (int) ($p['partner'] ?? 0) === 1 && $p['status'] === 'published' && owMuseumTypes((int) $p['id']) && owMuseumDates((int) $p['id']);
}
/* The Art Map button: "Buy ticket" (partner) or "Official tickets" (others, if we have a link). Nothing while hidden. */
function owTicketButton(array $p): string {
    if (($p['kind'] ?? '') !== 'museum' || !owBookingsReady() || !owBookingsOn()) return '';
    if (!array_key_exists('partner', $p)) { $p = owMuseumGet((int) ($p['id'] ?? 0)); if (!$p) return ''; }
    if ((int) ($p['partner'] ?? 0) === 1 && owMuseumSells($p))
        return '<a class="btn btn-primary btn-sm m-ticket" href="' . e(owBase() . 'ticket_buy.php?p=' . (int) $p['id']) . '">' . owBi('Buy ticket', 'اشترِ تذكرتك') . '</a>';
    $u = trim((string) ($p['tickets_url'] ?? ''));
    if ($u === '') $u = trim((string) ($p['website'] ?? ''));
    if ($u === '') return '';
    [$link, $ok] = owSafeLink($u, 'web');
    return $ok ? '<a class="btn btn-secondary btn-sm m-ticket" href="' . e($link) . '" target="_blank" rel="noopener">' . owBi('Official tickets', 'التذاكر الرسمية') . ' <span aria-hidden="true">↗</span></a>' : '';
}
/* When a museum is paid for one visit, by its own rule */
function owMuseumDueOn(array $p, string $visitDate): ?string {
    $rule = (string) ($p['payout_rule'] ?? 'after_visit');
    if ($rule === 'agreement') return null;
    if ($rule === 'month_end') return date('Y-m-01', strtotime(date('Y-m-01', strtotime($visitDate)) . ' +1 month'));
    return date('Y-m-d', strtotime($visitDate . ' +' . max(0, (int) ($p['payout_days'] ?? 7)) . ' days'));
}
function owMuseumRuleBi(array $p): array {
    $rule = (string) ($p['payout_rule'] ?? 'after_visit'); $d = (int) ($p['payout_days'] ?? 7);
    $r = match ($rule) {
        'month_end' => ['Paid once a month, after the month of the visit.', 'يُدفع مرة في الشهر، بعد انتهاء شهر الزيارة.'],
        'agreement' => ['Paid by hand, as written in the agreement.', 'يُدفع يدوياً، كما في الاتفاقية.'],
        default     => ["Paid $d days after the visit date.", "يُدفع بعد $d أيام من تاريخ الزيارة."],
    };
    $note = trim((string) ($p['payout_note'] ?? ''));
    return $note !== '' ? [$r[0] . ' ' . $note, $r[1] . ' ' . $note] : $r;
}
/* Hold tickets for one date: $qty = [typeId => count]. Returns [ticketId, errorBi|null] */
function owTicketHold(array $place, int $dateId, array $qty, array $user, string $name, string $email, string $phone): array {
    $types = []; foreach (owMuseumTypes((int) $place['id']) as $t) $types[(int) $t['id']] = $t;
    $lines = []; $people = 0; $amount = 0.0;
    foreach ($qty as $tid => $n) {
        $n = max(0, min(20, (int) $n)); $tid = (int) $tid;
        if ($n < 1 || !isset($types[$tid])) continue;
        $t = $types[$tid];
        $lines[] = ['type' => $tid, 'en' => $t['name_en'], 'ar' => $t['name_ar'], 'qty' => $n, 'price' => (float) $t['price']];
        $people += $n * max(1, (int) $t['people']); $amount += $n * (float) $t['price'];
    }
    if (!$lines) return [0, ['Please choose at least one ticket.', 'يرجى اختيار تذكرة واحدة على الأقل.']];
    if ($people > 40) return [0, ['For more than 40 people, please contact us.', 'لأكثر من 40 شخصاً، يرجى التواصل معنا.']];
    $db = getDB();
    try {
        $db->beginTransaction();
        $st = $db->prepare('SELECT * FROM museum_ticket_dates WHERE id = :d AND place_id = :p AND is_active = 1 AND visit_date >= CURDATE() FOR UPDATE');
        $st->execute([':d' => $dateId, ':p' => (int) $place['id']]);
        $d = $st->fetch();
        if (!$d) { $db->rollBack(); return [0, ['Please choose a date.', 'يرجى اختيار تاريخ.']]; }
        if ((int) $d['capacity'] - (int) $d['sold'] < $people) { $db->rollBack(); return [0, ['Not enough tickets left on this date. Please choose another date or fewer tickets.', 'لا تكفي التذاكر المتبقية في هذا التاريخ. اختر تاريخاً آخر أو عدداً أقل.']]; }
        $db->prepare('UPDATE museum_ticket_dates SET sold = sold + :n WHERE id = :d')->execute([':n' => $people, ':d' => $dateId]);
        $split = owSplit(round($amount, 2), (float) $place['ticket_commission']);
        $db->prepare("INSERT INTO museum_tickets (ref_no, qr_token, place_id, date_id, visit_date, user_id, buyer_name, buyer_email, buyer_phone, lines_json, people, amount,
                      commission_rate, commission_amount, partner_amount, status, hold_until)
                      VALUES (:ref, :q, :p, :d, :vd, :u, :n, :e, :ph, :l, :pe, :am, :cr, :ca, :pa, 'pending', NOW() + INTERVAL " . OW_BOOK_HOLD_MIN . " MINUTE)")
           ->execute([':ref' => owBookRef('T', 'museum_tickets'), ':q' => bin2hex(random_bytes(20)), ':p' => (int) $place['id'], ':d' => $dateId, ':vd' => $d['visit_date'],
                      ':u' => (int) $user['id'], ':n' => mb_substr($name, 0, 150), ':e' => mb_substr($email, 0, 190), ':ph' => mb_substr($phone, 0, 30),
                      ':l' => json_encode($lines, JSON_UNESCAPED_UNICODE), ':pe' => $people, ':am' => round($amount, 2),
                      ':cr' => $split['rate'], ':ca' => $split['commission'], ':pa' => $split['partner']]);
        $id = (int) $db->lastInsertId();
        $db->commit();
        owLog('ticket_book_start', 'museum_ticket', $id, 'place ' . $place['id'], ['type' => owActorType($user['role'] ?? 'collector'), 'id' => (int) $user['id']]);
        return [$id, null];
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log('owTicketHold: ' . $e->getMessage());
        return [0, ['Something went wrong. Please try again.', 'حدث خطأ. يرجى المحاولة مرة أخرى.']];
    }
}
function owTicketLinesBi(array $t): array {
    $en = []; $ar = [];
    foreach (json_decode((string) $t['lines_json'], true) ?: [] as $l) { $en[] = $l['qty'] . ' × ' . $l['en']; $ar[] = $l['qty'] . ' × ' . $l['ar']; }
    return [implode(', ', $en), implode('، ', $ar)];
}

/* Ticket types and dates: the same form for the museum's dashboard and for the owner (admin_bookings.php) */
function owMuseumSetupPost(array $p, array $in, array $actor): ?array {
    $db = getDB(); $pid = (int) $p['id']; $do = (string) ($in['do'] ?? '');
    if ($do === 'type_save') {
        $id = (int) ($in['id'] ?? 0);
        $ne = mb_substr(trim((string) ($in['name_en'] ?? '')), 0, 120); $na = mb_substr(trim((string) ($in['name_ar'] ?? '')), 0, 120);
        $price = round((float) str_replace(',', '', (string) ($in['price'] ?? '')), 2); $people = max(1, min(10, (int) ($in['people'] ?? 1)));
        if ($ne === '' || $na === '') return ['The ticket name is needed in Arabic and English.', 'اسم التذكرة مطلوب بالعربية والإنجليزية.'];
        if ($price <= 0 || $price > 100000) return ['Please write a price above 0.', 'يرجى كتابة سعر أكبر من صفر.'];
        $args = [':ne' => $ne, ':na' => $na, ':pr' => $price, ':pe' => $people, ':a' => !empty($in['is_active']) ? 1 : 0, ':p' => $pid];
        if ($id) $db->prepare('UPDATE museum_ticket_types SET name_en = :ne, name_ar = :na, price = :pr, people = :pe, is_active = :a WHERE id = :id AND place_id = :p')->execute($args + [':id' => $id]);
        else $db->prepare('INSERT INTO museum_ticket_types (place_id, name_en, name_ar, price, people, is_active) VALUES (:p, :ne, :na, :pr, :pe, :a)')->execute($args);
        owLog('ticket_type_save', 'art_place', $pid, $ne . ' ' . $price, $actor);
        return null;
    }
    if ($do === 'dates_add') {
        $from = owFormDate((string) ($in['from'] ?? '')); $to = owFormDate((string) ($in['to'] ?? '')); $cap = max(1, min(100000, (int) ($in['capacity'] ?? 0)));
        $days = array_map('intval', (array) ($in['wd'] ?? []));
        if (!$from || !$to || $to < $from) return ['Please choose the first and last date.', 'يرجى اختيار أول تاريخ وآخر تاريخ.'];
        if ($from < date('Y-m-d')) return ['The first date cannot be in the past.', 'لا يمكن أن يكون أول تاريخ في الماضي.'];
        if ((strtotime($to) - strtotime($from)) / 86400 > 366) return ['Please add at most one year at a time.', 'يرجى إضافة سنة واحدة كحد أقصى في كل مرة.'];
        if (!$days) return ['Please tick the open days.', 'يرجى تحديد أيام العمل.'];
        $ins = $db->prepare('INSERT INTO museum_ticket_dates (place_id, visit_date, capacity, is_active) VALUES (:p, :d, :c, 1) ON DUPLICATE KEY UPDATE capacity = GREATEST(sold, VALUES(capacity)), is_active = 1');
        $n = 0;
        for ($t = strtotime($from); $t <= strtotime($to); $t += 86400) if (in_array((int) date('w', $t), $days, true)) { $ins->execute([':p' => $pid, ':d' => date('Y-m-d', $t), ':c' => $cap]); $n++; }
        owLog('ticket_dates_add', 'art_place', $pid, "$from..$to $n days cap $cap", $actor);
        return null;
    }
    if ($do === 'date_save') {
        $id = (int) ($in['id'] ?? 0); $cap = max(0, min(100000, (int) ($in['capacity'] ?? 0)));
        $db->prepare('UPDATE museum_ticket_dates SET capacity = GREATEST(sold, :c), is_active = :a WHERE id = :id AND place_id = :p')
           ->execute([':c' => $cap, ':a' => !empty($in['is_active']) ? 1 : 0, ':id' => $id, ':p' => $pid]);
        owLog('ticket_date_save', 'art_place', $pid, "date $id cap $cap", $actor);
        return null;
    }
    return ['Unknown action.', 'إجراء غير معروف.'];
}
function owMuseumSetupCss(): string {
    return '.ms-row{display:grid;grid-template-columns:1fr 1fr 110px 90px auto auto;gap:var(--space-xs);align-items:end;padding:8px 0;border-bottom:1px solid var(--color-border);}
.ms-row .field{margin:0;}.ms-days{display:flex;flex-wrap:wrap;gap:4px 12px;margin:6px 0 var(--space-sm);}
.ms-dates{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:6px;max-height:420px;overflow:auto;}
.ms-dates form{display:flex;gap:6px;align-items:center;border:1px solid var(--color-border);border-radius:10px;padding:6px 8px;margin:0;font-size:14px;}
.ms-dates input[type=number]{width:72px;padding:4px;min-height:32px;}
@media(max-width:700px){.ms-row{grid-template-columns:1fr 1fr;}}';
}
function owMuseumSetupHtml(array $p, string $action): string {
    $pid = (int) $p['id']; $ar = owIsAr();
    $field = fn($id, $label, $input) => '<div class="field"><label for="' . $id . '">' . $label . '</label>' . $input . '</div>';
    $h = '<div class="card"><h2>' . owBi('Ticket types', 'أنواع التذاكر') . '</h2><p class="mini">' . owBi('For example Adult, Child, Family. "People" is how many people one ticket lets in (a family ticket can be 4).', 'مثل: بالغ، طفل، عائلة. «الأشخاص» هو عدد من تُدخلهم التذكرة الواحدة (تذكرة العائلة قد تكون 4).') . '</p>';
    foreach (array_merge(owMuseumTypes($pid, false), [['id' => 0, 'name_en' => '', 'name_ar' => '', 'price' => '', 'people' => 1, 'is_active' => 1]]) as $t) {
        $k = 'tt' . (int) $t['id'];
        $h .= '<form method="post" action="' . e($action) . '" class="ms-row">' . csrfField() . '<input type="hidden" name="do" value="type_save"><input type="hidden" name="place" value="' . $pid . '"><input type="hidden" name="id" value="' . (int) $t['id'] . '">'
            . $field($k . 'e', owBi('Name (English)', 'الاسم (بالإنجليزية)'), '<input id="' . $k . 'e" name="name_en" maxlength="120" dir="ltr" required value="' . e($t['name_en']) . '">')
            . $field($k . 'a', owBi('Name (Arabic)', 'الاسم (بالعربية)'), '<input id="' . $k . 'a" name="name_ar" maxlength="120" dir="rtl" required value="' . e($t['name_ar']) . '">')
            . $field($k . 'p', owBi('Price (SAR)', 'السعر (ر.س)'), '<input id="' . $k . 'p" name="price" inputmode="decimal" dir="ltr" required value="' . e((string) $t['price']) . '">')
            . $field($k . 'n', owBi('People', 'الأشخاص'), '<input id="' . $k . 'n" name="people" type="number" min="1" max="10" value="' . (int) $t['people'] . '">')
            . '<label class="chk" style="display:flex;gap:6px;align-items:center;margin:0 0 12px"><input type="checkbox" name="is_active" value="1"' . ((int) $t['is_active'] ? ' checked' : '') . '> ' . owBi('On sale', 'معروضة') . '</label>'
            . '<button class="btn btn-' . ((int) $t['id'] ? 'secondary' : 'primary') . ' btn-sm" style="margin-bottom:8px">' . ((int) $t['id'] ? owBi('Save', 'حفظ') : owBi('Add', 'إضافة')) . '</button></form>';
    }
    $h .= '</div><div class="card"><h2>' . owBi('Dates on sale', 'التواريخ المعروضة') . '</h2>'
        . '<form method="post" action="' . e($action) . '">' . csrfField() . '<input type="hidden" name="do" value="dates_add"><input type="hidden" name="place" value="' . $pid . '">'
        . '<div class="g2">' . $field('md-f', owBi('From', 'من'), '<input id="md-f" type="date" name="from" required>') . $field('md-t', owBi('To', 'إلى'), '<input id="md-t" type="date" name="to" required>') . '</div>'
        . $field('md-c', owBi('Tickets per day (people)', 'عدد التذاكر في اليوم (أشخاص)'), '<input id="md-c" name="capacity" type="number" min="1" max="100000" required style="max-width:160px">')
        . '<fieldset style="border:0;padding:0;margin:0"><legend class="mini">' . owBi('Open days', 'أيام العمل') . '</legend><div class="ms-days">';
    $wd = [['Sun', 'الأحد'], ['Mon', 'الاثنين'], ['Tue', 'الثلاثاء'], ['Wed', 'الأربعاء'], ['Thu', 'الخميس'], ['Fri', 'الجمعة'], ['Sat', 'السبت']];
    foreach ($wd as $i => $w) $h .= '<label class="chk" style="display:flex;gap:4px;align-items:center"><input type="checkbox" name="wd[]" value="' . $i . '" checked> ' . owBi($w[0], $w[1]) . '</label>';
    $h .= '</div></fieldset><button class="btn btn-primary btn-sm">' . owBi('Add these dates', 'أضف هذه التواريخ') . '</button></form>';
    $dates = owMuseumDates($pid, false);
    $dates = array_values(array_filter($dates, fn($d) => $d['visit_date'] >= date('Y-m-d')));
    if ($dates) {
        $h .= '<p class="mini" style="margin-top:var(--space-md)">' . owBi('Each date: tickets per day, sold so far, and whether it is on sale.', 'لكل تاريخ: التذاكر في اليوم، والمباع حتى الآن، وهل هو معروض.') . '</p><div class="ms-dates">';
        foreach ($dates as $d) {
            [$de, $da] = owDateRangeBi($d['visit_date'], null);
            $h .= '<form method="post" action="' . e($action) . '">' . csrfField() . '<input type="hidden" name="do" value="date_save"><input type="hidden" name="place" value="' . $pid . '"><input type="hidden" name="id" value="' . (int) $d['id'] . '">'
                . '<span style="flex:1">' . owBi($de, $da) . '<br><span class="mini">' . owBi((int) $d['sold'] . ' sold', 'مباع ' . (int) $d['sold']) . '</span></span>'
                . '<input type="number" name="capacity" min="' . (int) $d['sold'] . '" value="' . (int) $d['capacity'] . '" aria-label="' . e('Tickets per day · التذاكر في اليوم') . '">'
                . '<input type="checkbox" name="is_active" value="1"' . ((int) $d['is_active'] ? ' checked' : '') . ' aria-label="' . e('On sale · معروض') . '"><button class="btn btn-ghost btn-sm">' . owBi('Save', 'حفظ') . '</button></form>';
        }
        $h .= '</div>';
    }
    return $h . '</div>';
}

/* ---------------- after the money arrives ---------------- */
/* Called once a payment is confirmed (pay_lib.php). Safe to call twice. */
function owBookingPaid(array $pay): bool {
    $db = getDB();
    try {
        $db->beginTransaction();
        $st = $db->prepare('SELECT * FROM booking_payments WHERE id = :id FOR UPDATE'); $st->execute([':id' => (int) $pay['id']]);
        $pay = $st->fetch();
        if (!$pay || $pay['status'] === 'paid') { $db->rollBack(); return (bool) $pay; }
        $db->prepare("UPDATE booking_payments SET status = 'paid', paid_at = NOW() WHERE id = :id")->execute([':id' => (int) $pay['id']]);
        if ($pay['kind'] === 'course') {
            $r = $db->prepare('SELECT * FROM course_registrations WHERE id = :id FOR UPDATE'); $r->execute([':id' => (int) $pay['ref_id']]); $reg = $r->fetch();
            if (!$reg) throw new RuntimeException('registration missing');
            if ($reg['status'] === 'cancelled') {   // the hold ran out before the money came: take the seat back if one is free
                $s = $db->prepare('UPDATE courses SET seats_remaining = seats_remaining - 1 WHERE id = :c AND seats_remaining > 0'); $s->execute([':c' => $reg['course_id']]);
                if (!$s->rowCount()) { $db->prepare("UPDATE booking_payments SET note = 'paid after the hold ran out and the course was full: refund needed' WHERE id = :id")->execute([':id' => (int) $pay['id']]); $db->commit(); return false; }
            }
            $db->prepare("UPDATE course_registrations SET status = 'confirmed', paid_at = NOW(), payment_id = :p, hold_until = NULL, cancelled_at = NULL WHERE id = :id")
               ->execute([':p' => (int) $pay['id'], ':id' => (int) $reg['id']]);
            $c = owCourseGet((int) $reg['course_id']);
            $db->prepare("INSERT IGNORE INTO partner_payouts (payee_kind, payee_id, source_kind, source_id, amount, commission, due_on) VALUES ('institute', :i, 'course', :s, :a, :c, :d)")
               ->execute([':i' => (int) $c['institute_id'], ':s' => (int) $reg['id'], ':a' => $reg['partner_amount'], ':c' => $reg['commission_amount'], ':d' => $c['start_date']]);
            $db->commit();
            owLog('course_book_paid', 'course_registration', (int) $reg['id'], $pay['gateway'], ['type' => 'system', 'id' => null]);
            owCourseConfirmMail((int) $reg['id']);
            return true;
        }
        $r = $db->prepare('SELECT * FROM museum_tickets WHERE id = :id FOR UPDATE'); $r->execute([':id' => (int) $pay['ref_id']]); $t = $r->fetch();
        if (!$t) throw new RuntimeException('ticket missing');
        if ($t['status'] === 'expired') {
            $s = $db->prepare('UPDATE museum_ticket_dates SET sold = sold + :n WHERE id = :d AND capacity >= sold + :n2'); $s->execute([':n' => (int) $t['people'], ':n2' => (int) $t['people'], ':d' => $t['date_id']]);
            if (!$s->rowCount()) { $db->prepare("UPDATE booking_payments SET note = 'paid after the hold ran out and the date was full: refund needed' WHERE id = :id")->execute([':id' => (int) $pay['id']]); $db->commit(); return false; }
        }
        $db->prepare("UPDATE museum_tickets SET status = 'paid', paid_at = NOW(), payment_id = :p, hold_until = NULL WHERE id = :id")->execute([':p' => (int) $pay['id'], ':id' => (int) $t['id']]);
        $place = owMuseumGet((int) $t['place_id']);
        $db->prepare("INSERT IGNORE INTO partner_payouts (payee_kind, payee_id, source_kind, source_id, amount, commission, due_on) VALUES ('museum', :m, 'ticket', :s, :a, :c, :d)")
           ->execute([':m' => (int) $t['place_id'], ':s' => (int) $t['id'], ':a' => $t['partner_amount'], ':c' => $t['commission_amount'], ':d' => owMuseumDueOn($place ?: [], (string) $t['visit_date'])]);
        $db->commit();
        owLog('ticket_paid', 'museum_ticket', (int) $t['id'], $pay['gateway'], ['type' => 'system', 'id' => null]);
        owTicketMail((int) $t['id']);
        return true;
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log('owBookingPaid: ' . $e->getMessage());
        return false;
    }
}
/* Free courses: no payment at all */
function owCourseConfirmFree(int $regId): bool {
    $db = getDB();
    $r = $db->prepare("UPDATE course_registrations SET status = 'confirmed', paid_at = NOW(), hold_until = NULL WHERE id = :id AND status = 'pending' AND amount = 0");
    $r->execute([':id' => $regId]);
    if (!$r->rowCount()) return false;
    owLog('course_book_free', 'course_registration', $regId, '', ['type' => 'system', 'id' => null]);
    owCourseConfirmMail($regId);
    return true;
}

/* Cancel (by the admin or the institute / museum), with a refund when it was paid. Returns errorBi or null */
function owBookingCancel(string $kind, int $id, array $actor, bool $refund, string $why = ''): ?array {
    require_once __DIR__ . '/pay_lib.php';
    $db = getDB();
    $table = $kind === 'course' ? 'course_registrations' : 'museum_tickets';
    $st = $db->prepare("SELECT * FROM $table WHERE id = :id"); $st->execute([':id' => $id]); $row = $st->fetch();
    if (!$row) return ['Not found.', 'غير موجود.'];
    $paidStates = $kind === 'course' ? ['confirmed'] : ['paid'];
    if (!in_array($row['status'], array_merge($paidStates, ['pending']), true)) return ['This one is already closed.', 'هذا الحجز مغلق بالفعل.'];
    $wasPaid = in_array($row['status'], $paidStates, true) && (float) $row['amount'] > 0 && $row['payment_id'];
    if ($wasPaid && $refund) {
        $p = $db->prepare('SELECT * FROM booking_payments WHERE id = :id'); $p->execute([':id' => (int) $row['payment_id']]); $pay = $p->fetch();
        if (!$pay || !owPayRefund($pay)) return ['The refund could not be made with the payment provider. Nothing was changed.', 'تعذّر الاسترداد لدى مزوّد الدفع. لم يتغير شيء.'];
    }
    try {
        $db->beginTransaction();
        if ($kind === 'course') {
            $u = $db->prepare("UPDATE course_registrations SET status = 'cancelled', cancelled_at = NOW(), hold_until = NULL, refund_amount = :r, notes = :n WHERE id = :id AND status IN ('pending','confirmed')");
            $u->execute([':r' => $wasPaid && $refund ? $row['amount'] : 0, ':n' => mb_substr($why, 0, 1000), ':id' => $id]);
            if ($u->rowCount()) $db->prepare('UPDATE courses SET seats_remaining = LEAST(seats_total, seats_remaining + 1) WHERE id = :c')->execute([':c' => $row['course_id']]);
        } else {
            $u = $db->prepare("UPDATE museum_tickets SET status = 'cancelled', cancelled_at = NOW(), hold_until = NULL, refund_amount = :r WHERE id = :id AND status IN ('pending','paid')");
            $u->execute([':r' => $wasPaid && $refund ? $row['amount'] : 0, ':id' => $id]);
            if ($u->rowCount()) $db->prepare('UPDATE museum_ticket_dates SET sold = GREATEST(0, CAST(sold AS SIGNED) - :p) WHERE id = :d')->execute([':p' => (int) $row['people'], ':d' => $row['date_id']]);
        }
        $db->prepare("UPDATE partner_payouts SET status = 'cancelled' WHERE source_kind = :k AND source_id = :id AND status = 'pending'")->execute([':k' => $kind === 'course' ? 'course' : 'ticket', ':id' => $id]);
        $db->commit();
    } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); error_log('owBookingCancel: ' . $e->getMessage()); return ['Something went wrong.', 'حدث خطأ.']; }
    owLog($kind === 'course' ? 'course_book_cancel' : 'ticket_cancel', $kind === 'course' ? 'course_registration' : 'museum_ticket', $id, ($wasPaid && $refund ? 'refunded ' : '') . $why, $actor);
    $email = $kind === 'course' ? (string) $row['student_email'] : (string) $row['buyer_email'];
    if ($email !== '' && $row['status'] !== 'pending') {
        require_once __DIR__ . '/request_lib.php';
        owReqTellBuyer($email, 'Your booking is cancelled · تم إلغاء حجزك',
            'Your booking ' . $row['ref_no'] . ' is cancelled.' . ($wasPaid && $refund ? ' The full amount (' . number_format((float) $row['amount'], 2) . ' SAR) is refunded to the way you paid.' : ''),
            'تم إلغاء حجزك ' . $row['ref_no'] . '.' . ($wasPaid && $refund ? ' أعدنا المبلغ كاملاً (' . number_format((float) $row['amount'], 2) . ' ر.س) إلى وسيلة الدفع نفسها.' : ''), 'my_bookings.php');
    }
    return null;
}

/* ---------------- emails ---------------- */
function owBookMailHtml(string $en, string $ar, string $extra = ''): string {
    $box = 'font-family:Tahoma,Arial,sans-serif;font-size:15px;line-height:1.7;color:#23314A;';
    return '<div style="' . $box . '">' . $en . '<hr style="border:0;border-top:1px solid #e5e7eb;margin:20px 0">'
         . '<div dir="rtl" style="text-align:right">' . $ar . '</div>' . $extra . '</div>';
}
function owCourseConfirmMail(int $regId): void {
    try {
        $st = getDB()->prepare('SELECT * FROM course_registrations WHERE id = :id'); $st->execute([':id' => $regId]); $r = $st->fetch();
        if (!$r || !filter_var($r['student_email'], FILTER_VALIDATE_EMAIL)) return;
        $c = owCourseGet((int) $r['course_id']); if (!$c) return;
        $h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        [$dEn, $dAr] = owDateRangeBi($c['start_date'], $c['end_date']);
        $online = $c['course_type'] === 'online';
        $whereEn = $online ? 'Online. Your meeting link: <a href="' . $h($c['meeting_link']) . '">' . $h($c['meeting_link']) . '</a>' : 'Place: ' . $h($c['location_en']);
        $whereAr = $online ? 'عن بُعد. رابط الاجتماع: <a href="' . $h($c['meeting_link']) . '">' . $h($c['meeting_link']) . '</a>' : 'المكان: ' . $h($c['location_ar']);
        $amt = (float) $r['amount'];
        $en = '<p><b>You are registered.</b></p><p>' . $h($c['title_en']) . '<br>' . $h($c['inst_en']) . '<br>' . $h($dEn) . ' · ' . $h($c['schedule_en']) . '<br>' . $whereEn . '</p>'
            . '<p>Booking number: <b>' . $h($r['ref_no']) . '</b><br>Paid: ' . ($amt > 0 ? number_format($amt, 2) . ' SAR' : 'Free') . '</p>'
            . '<p>See your bookings: <a href="' . owSiteBase() . '/my_bookings.php">' . owSiteBase() . '/my_bookings.php</a></p>';
        $ar = '<p><b>تم تسجيلك.</b></p><p>' . $h($c['title_ar']) . '<br>' . $h($c['inst_ar']) . '<br>' . $h($dAr) . ' · ' . $h($c['schedule_ar']) . '<br>' . $whereAr . '</p>'
            . '<p>رقم الحجز: <b>' . $h($r['ref_no']) . '</b><br>المدفوع: ' . ($amt > 0 ? number_format($amt, 2) . ' ر.س' : 'مجاني') . '</p>'
            . '<p>حجوزاتك: <a href="' . owSiteBase() . '/my_bookings.php?lang=ar">' . owSiteBase() . '/my_bookings.php</a></p>';
        owSendMail((string) $r['student_email'], 'You are registered · تم تسجيلك: ' . $c['title_en'], owBookMailHtml($en, $ar), true);
        $inst = trim((string) $c['inst_email']);
        if ($inst !== '' && filter_var($inst, FILTER_VALIDATE_EMAIL))
            owSendMail($inst, 'New registration · تسجيل جديد: ' . $c['title_en'], "A new student registered for {$c['title_en']} ({$r['ref_no']}). See your dashboard: " . owSiteBase() . "/institute_dashboard.php\r\n\r\n"
                . "طالب جديد سجّل في {$c['title_ar']} ({$r['ref_no']}). لوحتك: " . owSiteBase() . '/institute_dashboard.php');
    } catch (Throwable $e) { error_log('owCourseConfirmMail: ' . $e->getMessage()); }
}
function owTicketUrl(array $t): string { return owSiteBase() . '/ticket.php?k=' . $t['qr_token']; }
function owTicketCheckUrl(array $t): string { return owSiteBase() . '/ticket_check.php?k=' . $t['qr_token']; }
function owTicketMail(int $ticketId): void {
    try {
        $st = getDB()->prepare('SELECT * FROM museum_tickets WHERE id = :id'); $st->execute([':id' => $ticketId]); $t = $st->fetch();
        if (!$t || !filter_var($t['buyer_email'], FILTER_VALIDATE_EMAIL)) return;
        $p = owMuseumGet((int) $t['place_id']); if (!$p) return;
        $h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        [$lEn, $lAr] = owTicketLinesBi($t);
        [$dEn, $dAr] = owDateRangeBi($t['visit_date'], null);
        $qr = '<p style="text-align:center;margin:20px 0"><img src="' . owSiteBase() . '/ticket_qr.php?k=' . $h($t['qr_token']) . '" width="220" height="220" alt="QR code · رمز QR" style="border:1px solid #e5e7eb"></p>'
            . '<p style="text-align:center"><a href="' . $h(owTicketUrl($t)) . '">' . $h(owTicketUrl($t)) . '</a></p>';
        $en = '<p><b>Your ticket</b></p><p>' . $h($p['name_en']) . '<br>' . $h($p['city_en']) . '<br>Visit date: ' . $h($dEn) . '<br>' . $h($lEn) . '</p>'
            . '<p>Ticket number: <b>' . $h($t['ref_no']) . '</b><br>Paid: ' . number_format((float) $t['amount'], 2) . ' SAR</p><p>Show the QR code at the entrance. It works once.</p>';
        $ar = '<p><b>تذكرتك</b></p><p>' . $h($p['name_ar']) . '<br>' . $h($p['city_ar']) . '<br>تاريخ الزيارة: ' . $h($dAr) . '<br>' . $h($lAr) . '</p>'
            . '<p>رقم التذكرة: <b>' . $h($t['ref_no']) . '</b><br>المدفوع: ' . number_format((float) $t['amount'], 2) . ' ر.س</p><p>اعرض رمز QR عند المدخل. يعمل مرة واحدة.</p>';
        owSendMail((string) $t['buyer_email'], 'Your ticket · تذكرتك: ' . $p['name_en'], owBookMailHtml($en, $ar, $qr), true);
    } catch (Throwable $e) { error_log('owTicketMail: ' . $e->getMessage()); }
}

/* ---------------- payouts ---------------- */
function owPayoutDueBi(?string $due): array {
    if ($due === null) return ['By agreement', 'حسب الاتفاقية'];
    [$en, $ar] = owDateRangeBi($due, null);
    return $due <= date('Y-m-d') ? ['Due now (since ' . $en . ')', 'مستحق الآن (منذ ' . $ar . ')'] : ['Due on ' . $en, 'يستحق في ' . $ar];
}
function owPayoutsFor(string $kind, int $payeeId): array {
    $st = getDB()->prepare('SELECT * FROM partner_payouts WHERE payee_kind = :k AND payee_id = :p ORDER BY (status = \'pending\') DESC, due_on IS NULL, due_on, id DESC LIMIT 300');
    $st->execute([':k' => $kind, ':p' => $payeeId]); return $st->fetchAll();
}
function owPayoutTotals(array $rows): array {
    $t = ['earned' => 0.0, 'paid' => 0.0, 'due' => 0.0, 'waiting' => 0.0];
    foreach ($rows as $r) {
        if ($r['status'] === 'cancelled') continue;
        $a = (float) $r['amount']; $t['earned'] += $a;
        if ($r['status'] === 'paid') $t['paid'] += $a;
        elseif ($r['due_on'] !== null && $r['due_on'] <= date('Y-m-d')) $t['due'] += $a;
        else $t['waiting'] += $a;
    }
    return array_map(fn($v) => round($v, 2), $t);
}

/* ---------------- cancellation and refund terms (draft) ---------------- */
const OW_LAW_ECOMMERCE = 'https://laws.boe.gov.sa/BoeLaws/Laws/LawDetails/360de590-0286-4fa5-a243-aa9100c31979/1';
const OW_LAW_MC        = 'https://mc.gov.sa';
/* Each rule: [en, ar, source en, source ar, url] */
function owBookingRules(): array {
    return [
        ['Courses: you can cancel and get the full amount back until the day before the course starts.',
         'الدورات: يمكنك الإلغاء واسترداد المبلغ كاملاً حتى اليوم السابق لبداية الدورة.',
         'E-Commerce Law, Article 13 (the right to cancel within 7 days if the service has not been used). Oweili gives more time than the law.',
         'نظام التجارة الإلكترونية، المادة 13 (حق فسخ العقد خلال 7 أيام ما لم تُستخدم الخدمة). يمنحك أويلي مدة أطول من النظام.', OW_LAW_ECOMMERCE],
        ['Once a course has started, it is not refunded, because the service is being used.',
         'بعد بداية الدورة لا يُسترد المبلغ، لأن الخدمة بدأت تُستخدم.',
         'E-Commerce Law, Article 13 (the right to cancel applies only when the service has not been used).',
         'نظام التجارة الإلكترونية، المادة 13 (حق الفسخ ما لم يُستفد من الخدمة).', OW_LAW_ECOMMERCE],
        ['Museum tickets: you can cancel and get the full amount back until the day before your visit date, if the ticket has not been used.',
         'تذاكر المتاحف: يمكنك الإلغاء واسترداد المبلغ كاملاً حتى اليوم السابق لتاريخ زيارتك، ما لم تُستخدم التذكرة.',
         'E-Commerce Law, Article 13.', 'نظام التجارة الإلكترونية، المادة 13.', OW_LAW_ECOMMERCE],
        ['A used ticket, or a visit date that has passed, is not refunded.',
         'التذكرة المستخدمة، أو التي مضى تاريخ زيارتها، لا تُسترد.',
         'E-Commerce Law, Article 13.', 'نظام التجارة الإلكترونية، المادة 13.', OW_LAW_ECOMMERCE],
        ['If the institute or the museum cancels, changes the date, or the course does not start within 15 days of the agreed date, you get the full amount back.',
         'إذا ألغى المعهد أو المتحف، أو غيّر الموعد، أو لم تبدأ الدورة خلال 15 يوماً من الموعد المتفق عليه، يُعاد إليك المبلغ كاملاً.',
         'E-Commerce Law, Article 14 (delay of more than 15 days: the right to cancel and get the money back).',
         'نظام التجارة الإلكترونية، المادة 14 (التأخير أكثر من 15 يوماً: حق الفسخ واسترداد المبلغ).', OW_LAW_ECOMMERCE],
        ['Refunds go back to the same way you paid. We start them within 3 working days of the cancellation.',
         'يعود المبلغ إلى وسيلة الدفع نفسها. نبدأ إجراءه خلال 3 أيام عمل من الإلغاء.',
         'Oweili’s own promise. The details of refunds are in the Implementing Regulations of the E-Commerce Law (Ministry of Commerce).',
         'التزام من أويلي. تفاصيل الاسترداد في اللائحة التنفيذية لنظام التجارة الإلكترونية (وزارة التجارة).', OW_LAW_MC],
        ['Before you pay, we show the full price, what it includes, the date, and these rules. You get a confirmation email with your booking number.',
         'قبل الدفع نعرض لك السعر كاملاً، وما يشمله، والموعد، وهذه القواعد. وتصلك رسالة تأكيد برقم حجزك.',
         'E-Commerce Law, Articles 7 and 8 (clear information before and after the contract).',
         'نظام التجارة الإلكترونية، المادتان 7 و8 (وضوح البيانات قبل التعاقد وبعده).', OW_LAW_ECOMMERCE],
        ['To cancel, write to us from the email you booked with, with your booking number.',
         'للإلغاء، راسلنا من البريد الذي حجزت به، مع رقم الحجز.',
         'Oweili’s own process.', 'إجراء أويلي.', ''],
    ];
}
/* The Terms section (terms.php shows it only while the switch is on) */
function owBookingTermsHtml(): string {
    $h = '<ol class="bk-rules">';
    foreach (owBookingRules() as $r) {
        $src = $r[4] !== '' ? '<a href="' . e($r[4]) . '" target="_blank" rel="noopener">' . owBi($r[2], $r[3]) . ' ↗</a>' : owBi($r[2], $r[3]);
        $h .= '<li>' . owBi($r[0], $r[1]) . '<br><small>' . owBi('Source: ', 'المصدر: ') . $src . '</small></li>';
    }
    return $h . '</ol>';
}

/* ---------------- a simple page frame for the public booking pages ---------------- */
function owBookTop(string $titleEn, string $titleAr, string $path, bool $preview, string $css = '', bool $noindex = true, string $role = 'role-collector'): void {
    $GLOBALS['OW_BOOK_PREVIEW'] = $preview; ?>
<!DOCTYPE html>
<html <?php require_once __DIR__ . '/lang_lib.php'; owHtmlAttrs(); ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require_once __DIR__ . '/seo_lib.php'; owSeo(['en' => $titleEn, 'ar' => $titleAr, 'path' => $path, 'noindex' => $noindex || $preview]); ?>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/nav.css') ?>">
<style>
.page{position:relative;z-index:10;}
.bk{max-width:860px;margin:0 auto;}
.bk .card{padding:var(--space-md);margin-top:var(--space-sm);}
.bk h1{font-size:var(--fs-section);margin:0 0 4px;}
.bk h2{font-size:20px;margin:0 0 var(--space-sm);}
.mini{font-size:var(--fs-caption);color:var(--color-body);}
.kv{display:grid;grid-template-columns:max-content 1fr;gap:6px var(--space-sm);font-size:15px;margin:0;}
.kv dt{font-weight:700;color:var(--color-dark);}
.kv dd{margin:0;overflow-wrap:anywhere;}
.pillx{display:inline-block;font-size:var(--fs-min);font-weight:700;padding:2px 10px;border-radius:var(--radius-pill);border:1px solid var(--color-border-strong);white-space:nowrap;}
.pillx.on{background:rgba(var(--rgb-accent),.1);border-color:rgba(var(--rgb-accent),.35);}
.pillx.warn{background:rgba(var(--rgb-primary),.18);border-color:rgba(var(--rgb-primary),.45);}
.acts{display:flex;gap:var(--space-xs);flex-wrap:wrap;align-items:center;margin-top:var(--space-sm);}
.acts form{margin:0;}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:0 var(--space-sm);}
.bk-rules{margin:0;padding-inline-start:20px;display:grid;gap:10px;font-size:15px;}
.bk-rules small{font-size:var(--fs-caption);color:var(--color-body);}
.bk-rules a{color:var(--color-accent);}
@media(max-width:700px){.g2{grid-template-columns:1fr;}.kv{grid-template-columns:1fr;}.kv dd{margin-bottom:6px;}}
<?= $css ?>
</style>
</head>
<body class="ow">
<div class="bg-wrap" aria-hidden="true"></div>
<?php include __DIR__ . '/nav.php'; ?>
<?php if ($preview) echo owHiddenNotice(); ?>
<main class="page dash <?= e($role) ?> dash-wrap"><div class="bk">
<?php }
function owBookBottom(): void { ?>
</div></main>
<?php include __DIR__ . '/footer.php'; ?>
</body></html>
<?php }
