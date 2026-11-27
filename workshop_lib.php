<?php
/* =====================================================================
 *  workshop_lib.php — artist workshops (owner, 25 Nov 2026), HIDDEN behind
 *  the switch "Artist workshops" (Admin → Settings → Hidden for now;
 *  site_settings artist_workshops_on, gallery_lib.php OW_HIDDEN_PARTS).
 *
 *  1. An artist ASKS to offer workshops (artist_workshops.php). They can
 *     send the request only when the automatic conditions are met:
 *     approved for at least 3 months, at least 5 approved artworks, no
 *     complaint and no suspension ever, a confirmed email, a signature on
 *     file. They also write their teaching experience and a first workshop
 *     plan. The owner approves or refuses with a reason (admin_workshops.php).
 *  2. An approved artist adds workshops. Every new or changed workshop waits
 *     for the owner's approval. A workshop with bookings cannot be changed
 *     by the artist (they write to Oweili).
 *  3. Visitors "Book your seat" (workshop.php): name, mobile, email, the
 *     rules tick. Oweili handles contact and payment; the admin marks a
 *     booking paid, which makes the artist's payout row (price minus the
 *     course commission setting, 10% by default).
 *  Privacy: the artist sees only first names and the number of seats, never
 *  a student's full name, phone or email.
 *  Tables: artist_workshop_requests, artist_workshops, workshop_bookings,
 *          payouts, users, artworks, orders, order_complaints, activity_log
 * ===================================================================== */
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/gallery_lib.php';
require_once __DIR__ . '/request_lib.php';
require_once __DIR__ . '/booking_lib.php';

const OW_WS_MIN_MONTHS = 3;
const OW_WS_MIN_WORKS  = 5;
const OW_WS_BOOK_HOUR  = 5;   // bookings per visitor per hour
const OW_WS_LEVELS = [
    'beginner'     => ['Beginner', 'مبتدئ'],
    'intermediate' => ['Intermediate', 'متوسط'],
    'advanced'     => ['Advanced', 'متقدم'],
    'all'          => ['All levels', 'كل المستويات'],
];
const OW_WS_MODES = ['in_person' => ['In person', 'حضوري'], 'online' => ['Online', 'عن بعد']];
const OW_WS_REQ_MODES = ['in_person' => ['In person', 'حضوري'], 'online' => ['Online', 'عن بعد'], 'both' => ['In person and online', 'حضوري وعن بعد']];
const OW_WS_STATUS = [
    'pending'  => ['Waiting for approval', 'بانتظار الموافقة'],
    'approved' => ['Approved', 'معتمدة'],
    'refused'  => ['Not approved', 'لم تتم الموافقة'],
    'hidden'   => ['Hidden', 'مخفية'],
];
const OW_WSB_STATUS = [
    'booked'    => ['Booked, waiting for payment', 'محجوز، بانتظار الدفع'],
    'paid'      => ['Paid', 'مدفوع'],
    'cancelled' => ['Cancelled', 'ملغى'],
];

function owWsOn(): bool { return owPartOn('artist_workshops'); }
function owWsReady(): bool { return owHasTable('artist_workshop_requests') && owHasTable('artist_workshops') && owHasTable('workshop_bookings'); }
function owWsCommission(): float { return owCourseCommission(); }

/* -------------------------------------------------------------------
 *  The conditions to qualify
 * ----------------------------------------------------------------- */
/* When the account was approved: the first approval in the activity log; older accounts (approved before the log) count from sign-up */
function owWsApprovedSince(array $u): ?string {
    if ((int) ($u['is_approved'] ?? 0) !== 1) return null;
    try {
        $st = getDB()->prepare("SELECT MIN(created_at) FROM activity_log WHERE action = 'user_approve' AND target_type = 'user' AND target_id = :id");
        $st->execute([':id' => (int) $u['id']]);
        $d = (string) $st->fetchColumn();
        if ($d !== '') return $d;
    } catch (Throwable $e) {}
    return (string) ($u['created_at'] ?? '') ?: null;
}
function owWsApprovedWorks(int $uid): int {
    try {
        $st = getDB()->prepare('SELECT COUNT(*) FROM artworks a WHERE a.artist_id = :u AND ' . owArtworkStatusSql('a') . " IN ('approved','sold')");
        $st->execute([':u' => $uid]); return (int) $st->fetchColumn();
    } catch (Throwable $e) { return 0; }
}
function owWsEverComplaint(int $uid): bool {
    try {
        if (!owHasTable('order_complaints') || !owHasTable('orders')) return false;
        $st = getDB()->prepare('SELECT COUNT(*) FROM order_complaints c JOIN orders o ON o.id = c.order_id WHERE o.artist_id = :u');
        $st->execute([':u' => $uid]); return (int) $st->fetchColumn() > 0;
    } catch (Throwable $e) { return false; }
}
function owWsEverSuspended(array $u): bool {
    if (!empty($u['is_banned'])) return true;
    try {
        $st = getDB()->prepare("SELECT COUNT(*) FROM activity_log WHERE action = 'user_suspend' AND target_type = 'user' AND target_id = :id");
        $st->execute([':id' => (int) $u['id']]); return (int) $st->fetchColumn() > 0;
    } catch (Throwable $e) { return false; }
}
/* Each automatic condition: [key, English, Arabic, met?, detail EN, detail AR] */
function owWsChecks(array $u): array {
    require_once __DIR__ . '/sig_lib.php';
    $uid = (int) $u['id'];
    $since = owWsApprovedSince($u);
    $months = $since ? (int) floor((time() - strtotime($since)) / (86400 * 30.44)) : 0;
    $works = owWsApprovedWorks($uid);
    return [
        ['months', 'Approved artist for at least ' . OW_WS_MIN_MONTHS . ' months', 'فنان معتمد منذ ' . OW_WS_MIN_MONTHS . ' أشهر على الأقل', $since !== null && $months >= OW_WS_MIN_MONTHS,
            $since ? 'Now: ' . $months . ' month' . ($months === 1 ? '' : 's') : 'Your account is not approved yet', $since ? 'الآن: ' . $months . ' ' . ($months >= 3 && $months <= 10 ? 'أشهر' : 'شهر') : 'حسابك لم يُعتمد بعد'],
        ['works', 'At least ' . OW_WS_MIN_WORKS . ' approved artworks', OW_WS_MIN_WORKS . ' أعمال معتمدة على الأقل', $works >= OW_WS_MIN_WORKS, 'Now: ' . $works, 'الآن: ' . $works],
        ['complaint', 'No complaint about a sale, ever', 'لا شكوى على أي عملية بيع، أبدا', !owWsEverComplaint($uid), '', ''],
        ['suspend', 'Never suspended', 'لم يُعلَّق الحساب أبدا', !owWsEverSuspended($u), '', ''],
        ['email', 'Email confirmed', 'البريد الإلكتروني مؤكد', (int) ($u['is_verified'] ?? 0) === 1, '', ''],
        ['sign', 'Signature on file', 'التوقيع محفوظ', owSigExists((string) $uid), '', ''],
    ];
}
function owWsQualifies(array $u): bool { foreach (owWsChecks($u) as $c) if (!$c[3]) return false; return true; }

/* -------------------------------------------------------------------
 *  Requests to offer workshops
 * ----------------------------------------------------------------- */
function owWsLastRequest(int $uid): ?array {
    if (!owWsReady()) return null;
    $st = getDB()->prepare('SELECT * FROM artist_workshop_requests WHERE artist_id = :u ORDER BY id DESC LIMIT 1');
    $st->execute([':u' => $uid]); return $st->fetch() ?: null;
}
function owWsArtistApproved(int $uid): bool { $r = owWsLastRequest($uid); return $r !== null && $r['status'] === 'approved'; }

/* Returns '' when sent, else an error key */
function owWsSendRequest(array $u, array &$errors): bool {
    if (!owWsReady() || !owWsOn()) { $errors[] = ['Workshops are not open yet', 'ورش العمل غير متاحة بعد']; return false; }
    $last = owWsLastRequest((int) $u['id']);
    if ($last && in_array($last['status'], ['pending', 'approved'], true)) { $errors[] = ['You already sent a request', 'أرسلت طلبا من قبل']; return false; }
    if (!owWsQualifies($u)) { $errors[] = ['Some conditions are not met yet', 'بعض الشروط لم تتحقق بعد']; return false; }
    $subjects = owReqClean('ws_subjects', 500);
    $exp = owReqClean('ws_experience', 3000, true);
    $plan = owReqClean('ws_plan', 3000, true);
    $mode = array_key_exists($_POST['ws_mode'] ?? '', OW_WS_REQ_MODES) ? (string) $_POST['ws_mode'] : '';
    $city = owReqClean('ws_city', 80);
    if (mb_strlen($subjects) < 3) $errors[] = ['Please write what you would teach', 'يرجى كتابة ما ستعلّمه'];
    if (mb_strlen($exp) < 20) $errors[] = ['Please describe your teaching experience (at least a few lines)', 'يرجى وصف خبرتك في التدريس (بضعة أسطر على الأقل)'];
    if (mb_strlen($plan) < 20) $errors[] = ['Please write your first workshop plan: title, level, hours, materials, seats and price', 'يرجى كتابة خطة ورشتك الأولى: العنوان والمستوى والساعات والأدوات والمقاعد والسعر'];
    if ($mode === '') $errors[] = ['Please choose in person or online', 'يرجى اختيار حضوري أو عن بعد'];
    if ($mode !== 'online' && mb_strlen($city) < 2) $errors[] = ['Please write the city', 'يرجى كتابة المدينة'];
    if ($errors) return false;
    getDB()->prepare('INSERT INTO artist_workshop_requests (artist_id, subjects, experience, plan, mode, city) VALUES (:a, :s, :e, :p, :m, :c)')
           ->execute([':a' => (int) $u['id'], ':s' => $subjects, ':e' => $exp, ':p' => $plan, ':m' => $mode, ':c' => $mode === 'online' ? '' : $city]);
    $id = (int) getDB()->lastInsertId();
    owLog('workshop_request', 'workshop_request', $id, '', ['type' => 'artist', 'id' => (int) $u['id']]);
    require_once __DIR__ . '/alert_lib.php';
    owAdminAlert('workshop', 'An artist asks to offer workshops', 'فنان يطلب تقديم ورش عمل', 'admin_workshops.php#requests');
    return true;
}
function owWsDecideRequest(int $id, bool $approve, string $reason): bool {
    if (!owWsReady()) return false;
    $reason = mb_substr(trim($reason), 0, 500);
    if (!$approve && $reason === '') return false;
    $st = getDB()->prepare("SELECT * FROM artist_workshop_requests WHERE id = :id AND status = 'pending'"); $st->execute([':id' => $id]);
    $r = $st->fetch(); if (!$r) return false;
    getDB()->prepare('UPDATE artist_workshop_requests SET status = :s, reason = :r, decided_at = NOW() WHERE id = :id')
           ->execute([':s' => $approve ? 'approved' : 'refused', ':r' => $reason, ':id' => $id]);
    owLog($approve ? 'workshop_request_approve' : 'workshop_request_refuse', 'workshop_request', $id, $reason, ['type' => 'admin', 'id' => null]);
    require_once __DIR__ . '/approval_lib.php';
    if ($approve) owTellUser((int) $r['artist_id'], 'You can offer workshops on Oweili · يمكنك تقديم ورش عمل في أويلي',
        'Your request to offer workshops is approved. You can now add your first workshop. Each workshop is checked by the Oweili team before visitors see it.',
        'تمت الموافقة على طلبك لتقديم ورش العمل. يمكنك الآن إضافة ورشتك الأولى. يراجع فريق أويلي كل ورشة قبل أن يراها الزوار.', 'artist_workshops.php');
    else owTellUser((int) $r['artist_id'], 'Your workshop request · طلب ورش العمل',
        'Your request to offer workshops was not approved this time. Reason: ' . $reason,
        'لم تتم الموافقة على طلبك لتقديم ورش العمل هذه المرة. السبب: ' . $reason, 'artist_workshops.php');
    return true;
}

/* -------------------------------------------------------------------
 *  Workshops
 * ----------------------------------------------------------------- */
function owWsGet(int $id): ?array {
    if (!owWsReady()) return null;
    $st = getDB()->prepare('SELECT * FROM artist_workshops WHERE id = :id'); $st->execute([':id' => $id]);
    return $st->fetch() ?: null;
}
function owWsOfArtist(int $uid): array {
    if (!owWsReady()) return [];
    $st = getDB()->prepare("SELECT w.*, (SELECT COUNT(*) FROM workshop_bookings b WHERE b.workshop_id = w.id AND b.status <> 'cancelled') AS booked
                            FROM artist_workshops w WHERE w.artist_id = :u ORDER BY w.start_date DESC, w.id DESC");
    $st->execute([':u' => $uid]); return $st->fetchAll();
}
function owWsBookedCount(int $wid): int {
    $st = getDB()->prepare("SELECT COUNT(*) FROM workshop_bookings WHERE workshop_id = :w AND status <> 'cancelled'"); $st->execute([':w' => $wid]);
    return (int) $st->fetchColumn();
}
function owWsSeatsLeft(array $w): int { return max(0, (int) $w['seats'] - owWsBookedCount((int) $w['id'])); }

/* Checks the workshop form. Returns the clean row or null with $errors */
function owWsClean(array &$errors): ?array {
    $d = [
        'title_en' => owReqClean('title_en', 200), 'title_ar' => owReqClean('title_ar', 200),
        'desc_en' => owReqClean('desc_en', 3000, true), 'desc_ar' => owReqClean('desc_ar', 3000, true),
        'level' => array_key_exists($_POST['level'] ?? '', OW_WS_LEVELS) ? (string) $_POST['level'] : '',
        'mode' => array_key_exists($_POST['mode'] ?? '', OW_WS_MODES) ? (string) $_POST['mode'] : '',
        'city' => owReqClean('city', 80),
        'start_date' => (string) ($_POST['start_date'] ?? ''), 'end_date' => (string) ($_POST['end_date'] ?? ''),
        'schedule_en' => owReqClean('schedule_en', 200), 'schedule_ar' => owReqClean('schedule_ar', 200),
        'hours' => (float) str_replace(',', '.', owDigitsWestern((string) ($_POST['hours'] ?? '0'))),
        'price' => (float) str_replace(',', '', owDigitsWestern((string) ($_POST['price'] ?? '0'))),
        'seats' => (int) owDigitsWestern((string) ($_POST['seats'] ?? '0')),
        'materials' => isset($_POST['materials']) ? 1 : 0,
    ];
    if ($d['title_en'] === '' && $d['title_ar'] === '') $errors[] = ['Please write the workshop title (Arabic or English)', 'يرجى كتابة عنوان الورشة (بالعربية أو الإنجليزية)'];
    if ($d['title_en'] === '') $d['title_en'] = $d['title_ar']; if ($d['title_ar'] === '') $d['title_ar'] = $d['title_en'];
    if ($d['desc_en'] === '' && $d['desc_ar'] === '') $errors[] = ['Please write what students will learn', 'يرجى كتابة ما سيتعلمه المتدربون'];
    if ($d['desc_en'] === '') $d['desc_en'] = $d['desc_ar']; if ($d['desc_ar'] === '') $d['desc_ar'] = $d['desc_en'];
    if ($d['schedule_en'] === '') $d['schedule_en'] = $d['schedule_ar']; if ($d['schedule_ar'] === '') $d['schedule_ar'] = $d['schedule_en'];
    if ($d['level'] === '') $errors[] = ['Please choose the level', 'يرجى اختيار المستوى'];
    if ($d['mode'] === '') $errors[] = ['Please choose in person or online', 'يرجى اختيار حضوري أو عن بعد'];
    if ($d['mode'] === 'in_person' && mb_strlen($d['city']) < 2) $errors[] = ['Please write the city', 'يرجى كتابة المدينة'];
    if ($d['mode'] === 'online') $d['city'] = '';
    $okDate = fn(string $s) => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) && checkdate((int) substr($s, 5, 2), (int) substr($s, 8, 2), (int) substr($s, 0, 4));
    if (!$okDate($d['start_date']) || $d['start_date'] <= date('Y-m-d')) $errors[] = ['Please choose a start date after today', 'يرجى اختيار تاريخ بدء بعد اليوم'];
    if ($d['end_date'] === '') $d['end_date'] = $d['start_date'];
    elseif (!$okDate($d['end_date']) || $d['end_date'] < $d['start_date']) $errors[] = ['The end date must be on or after the start date', 'يجب أن يكون تاريخ الانتهاء في يوم البدء أو بعده'];
    if ($d['hours'] <= 0 || $d['hours'] > 500) $errors[] = ['Please write the total hours', 'يرجى كتابة عدد الساعات'];
    if ($d['price'] < 0 || $d['price'] > 100000) $errors[] = ['Please write a price in SAR (0 for free)', 'يرجى كتابة السعر بالريال (0 للمجاني)'];
    if ($d['seats'] < 1 || $d['seats'] > 200) $errors[] = ['Please write the number of seats (1 to 200)', 'يرجى كتابة عدد المقاعد (1 إلى 200)'];
    return $errors ? null : $d;
}
function owDigitsWestern(string $s): string { return strtr($s, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9', '٫' => '.', '٬' => ',']); }

/* New ($id = 0) or changed workshop: always waits for the owner. Returns the id or 0 */
function owWsSave(int $uid, int $id, array &$errors): int {
    if (!owWsReady() || !owWsOn() || !owWsArtistApproved($uid)) { $errors[] = ['You cannot add workshops yet', 'لا يمكنك إضافة ورش بعد']; return 0; }
    if ($id > 0) {
        $w = owWsGet($id);
        if (!$w || (int) $w['artist_id'] !== $uid) { $errors[] = ['Workshop not found', 'الورشة غير موجودة']; return 0; }
        if (owWsBookedCount($id) > 0) { $errors[] = ['This workshop has bookings, so it cannot be changed here. Please write to ' . owContactEmail(), 'لهذه الورشة حجوزات، لذا لا يمكن تعديلها هنا. يرجى مراسلة ' . owContactEmail()]; return 0; }
    }
    $d = owWsClean($errors); if (!$d) return 0;
    $cols = array_keys($d);
    if ($id > 0) {
        $set = implode(', ', array_map(fn($c) => "$c = :$c", $cols));
        $p = []; foreach ($d as $k => $v) $p[":$k"] = $v; $p[':id'] = $id; $p[':u'] = $uid;
        getDB()->prepare("UPDATE artist_workshops SET $set, status = 'pending', reason = '', decided_at = NULL WHERE id = :id AND artist_id = :u")->execute($p);
    } else {
        $p = [':u' => $uid]; foreach ($d as $k => $v) $p[":$k"] = $v;
        getDB()->prepare('INSERT INTO artist_workshops (artist_id, ' . implode(', ', $cols) . ') VALUES (:u, :' . implode(', :', $cols) . ')')->execute($p);
        $id = (int) getDB()->lastInsertId();
    }
    owLog('workshop_submit', 'workshop', $id, '', ['type' => 'artist', 'id' => $uid]);
    require_once __DIR__ . '/alert_lib.php';
    owAdminAlert('workshop', 'Workshop waiting for approval: ' . $d['title_en'], 'ورشة بانتظار الموافقة: ' . $d['title_ar'], 'admin_workshops.php#workshops');
    return $id;
}
function owWsDecide(int $id, string $do, string $reason): bool {
    $w = owWsGet($id); if (!$w) return false;
    $reason = mb_substr(trim($reason), 0, 500);
    $to = ['approve' => 'approved', 'refuse' => 'refused', 'hide' => 'hidden', 'show' => 'approved'][$do] ?? '';
    if ($to === '' || ($do === 'refuse' && $reason === '')) return false;
    if (in_array($do, ['approve', 'refuse'], true) && $w['status'] !== 'pending') return false;
    if ($do === 'hide' && $w['status'] !== 'approved') return false;
    if ($do === 'show' && $w['status'] !== 'hidden') return false;
    getDB()->prepare('UPDATE artist_workshops SET status = :s, reason = :r, decided_at = NOW() WHERE id = :id')->execute([':s' => $to, ':r' => $reason, ':id' => $id]);
    owLog('workshop_' . $do, 'workshop', $id, $reason, ['type' => 'admin', 'id' => null]);
    require_once __DIR__ . '/approval_lib.php';
    if ($do === 'approve') owTellUser((int) $w['artist_id'], 'Your workshop is approved · تمت الموافقة على ورشتك',
        'Your workshop "' . $w['title_en'] . '" is approved. Visitors can now book a seat.', 'تمت الموافقة على ورشتك «' . $w['title_ar'] . '». يمكن للزوار الآن حجز مقعد.', 'artist_workshops.php');
    if ($do === 'refuse') owTellUser((int) $w['artist_id'], 'Your workshop · ورشتك',
        'Your workshop "' . $w['title_en'] . '" was not approved. Reason: ' . $reason, 'لم تتم الموافقة على ورشتك «' . $w['title_ar'] . '». السبب: ' . $reason, 'artist_workshops.php');
    return true;
}

/* What visitors may see: approved workshops of approved, not suspended artists, not started yet */
function owWsPublicSql(string $w = 'w', string $u = 'u'): string {
    return "$w.status = 'approved' AND $w.start_date > CURDATE() AND $u.role = 'artist' AND $u.is_approved = 1 AND COALESCE($u.is_banned, 0) = 0";
}
function owWsPublicList(int $artistId = 0): array {
    if (!owWsReady()) return [];
    require_once __DIR__ . '/artwork_lib.php';
    $sql = 'SELECT w.*, ' . owArtistNameSql('u') . "
            FROM artist_workshops w JOIN users u ON u.id = w.artist_id WHERE " . owWsPublicSql() . ($artistId > 0 ? ' AND w.artist_id = :a' : '') . ' ORDER BY w.start_date, w.id';
    $st = getDB()->prepare($sql); $st->execute($artistId > 0 ? [':a' => $artistId] : []);
    return $st->fetchAll();
}
function owWsPublicGet(int $id): ?array {
    if (!owWsReady()) return null;
    require_once __DIR__ . '/artwork_lib.php';
    $st = getDB()->prepare('SELECT w.*, ' . owArtistNameSql('u') . '
                            FROM artist_workshops w JOIN users u ON u.id = w.artist_id WHERE w.id = :id AND ' . owWsPublicSql());
    $st->execute([':id' => $id]); return $st->fetch() ?: null;
}

/* -------------------------------------------------------------------
 *  Bookings
 * ----------------------------------------------------------------- */
function owWsFirstName(string $name): string {
    $p = preg_split('/\s+/u', trim($name)) ?: [];
    return mb_substr((string) ($p[0] ?? ''), 0, 30);
}
/* "Book your seat". Returns the booking ref or '' with $errors */
function owWsBook(array $w, array &$errors): string {
    if (!owWsReady()) { $errors[] = ['Booking is not open yet', 'الحجز غير متاح بعد']; return ''; }
    if (trim((string) ($_POST['website'] ?? '')) !== '') { $errors[] = ['We could not save your booking. Please try again.', 'تعذر حفظ حجزك. حاول مرة أخرى.']; return ''; }   // the hidden robot trap
    $b = owReqBuyer($errors);
    if (!isset($_POST['rules_ok'])) $errors[] = ['Please accept the booking and refund rules', 'يرجى الموافقة على قواعد الحجز والاسترداد'];
    if ($errors) return '';
    if (owReqLimited('workshop_book', OW_WS_BOOK_HOUR)) { $errors[] = ['Too many bookings from this device. Please try again in an hour.', 'حجوزات كثيرة من هذا الجهاز. حاول مرة أخرى بعد ساعة.']; return ''; }
    $db = getDB();
    try {
        $db->beginTransaction();
        $lock = $db->prepare('SELECT seats FROM artist_workshops WHERE id = :id FOR UPDATE'); $lock->execute([':id' => (int) $w['id']]);
        if (owWsSeatsLeft($w) < 1) { $db->rollBack(); $errors[] = ['Sorry, this workshop is full', 'عذرا، اكتملت مقاعد هذه الورشة']; return ''; }
        $dup = $db->prepare('SELECT id FROM workshop_bookings WHERE workshop_id = :w AND email = :e'); $dup->execute([':w' => (int) $w['id'], ':e' => $b['email']]);
        if ($dup->fetch()) { $db->rollBack(); $errors[] = ['This email has already booked this workshop', 'هذا البريد حجز هذه الورشة من قبل']; return ''; }
        $price = (float) $w['price']; $rate = owWsCommission(); $sp = owSplit($price, $rate);
        $ref = owBookRef('W', 'workshop_bookings');
        $free = $price <= 0;
        $db->prepare('INSERT INTO workshop_bookings (workshop_id, ref_no, name, phone, email, lang, amount, commission_rate, commission_amount, artist_amount, status, paid_at)
                      VALUES (:w, :r, :n, :p, :e, :l, :a, :cr, :ca, :aa, :s, ' . ($free ? 'NOW()' : 'NULL') . ')')
           ->execute([':w' => (int) $w['id'], ':r' => $ref, ':n' => $b['name'], ':p' => $b['phone'], ':e' => $b['email'], ':l' => owIsAr() ? 'ar' : 'en',
                      ':a' => $sp['gross'], ':cr' => $rate, ':ca' => $sp['commission'], ':aa' => $sp['partner'], ':s' => $free ? 'paid' : 'booked']);
        $bookingId = (int) $db->lastInsertId();
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log('owWsBook: ' . $e->getMessage());
        $errors[] = ['We could not save your booking. Please try again.', 'تعذر حفظ حجزك. حاول مرة أخرى.']; return '';
    }
    owReqCounted('workshop_book');
    owLog('workshop_book', 'workshop', (int) $w['id'], $ref);
    owReqTellBuyer($b['email'], 'We received your booking · وصلنا حجزك',
        'Thank you. Your seat in "' . $w['title_en'] . '" is ' . ($free ? 'confirmed' : 'booked') . '. Booking number: ' . $ref . '.' . ($free ? '' : ' The Oweili team will contact you to arrange the payment.'),
        'شكرا لك. مقعدك في «' . $w['title_ar'] . '» ' . ($free ? 'مؤكد' : 'محجوز') . '. رقم الحجز: ' . $ref . '.' . ($free ? '' : ' سيتواصل معك فريق أويلي لترتيب الدفع.'),
        'workshop.php?id=' . (int) $w['id']);
    require_once __DIR__ . '/alert_lib.php';
    owAdminAlert('workshop', 'New workshop booking: ' . $w['title_en'], 'حجز جديد في ورشة: ' . $w['title_ar'], 'admin_workshops.php#bookings');
    if ($free) { require_once __DIR__ . '/invoice_lib.php'; owInvoiceSend('workshop', $bookingId); }   // a free seat gets its receipt too (27 Nov 2026)
    return $ref;
}
/* The admin marks a booking paid: the artist's payout row is made (price minus the course commission) */
function owWsMarkPaid(int $bookingId, string $payRef): bool {
    $db = getDB();
    $st = $db->prepare("SELECT b.*, w.artist_id, w.title_en FROM workshop_bookings b JOIN artist_workshops w ON w.id = b.workshop_id WHERE b.id = :id AND b.status = 'booked'");
    $st->execute([':id' => $bookingId]); $b = $st->fetch(); if (!$b) return false;
    $db->prepare("UPDATE workshop_bookings SET status = 'paid', paid_at = NOW(), pay_ref = :r WHERE id = :id AND status = 'booked'")->execute([':r' => mb_substr(trim($payRef), 0, 120), ':id' => $bookingId]);
    if ((float) $b['artist_amount'] > 0 && owHasTable('payouts')) {
        $hasNote = owHasColumn('payouts', 'note');
        $db->prepare('INSERT INTO payouts (artist_id, amount, currency, status' . ($hasNote ? ', note' : '') . ") VALUES (:a, :m, 'SAR', 'pending'" . ($hasNote ? ', :n' : '') . ')')
           ->execute(array_merge([':a' => (int) $b['artist_id'], ':m' => (float) $b['artist_amount']], $hasNote ? [':n' => 'Workshop ' . $b['ref_no']] : []));
    }
    owLog('workshop_book_paid', 'workshop_booking', $bookingId, $b['ref_no'], ['type' => 'admin', 'id' => null]);
    owReqTellBuyer((string) $b['email'], 'Your seat is confirmed · تم تأكيد مقعدك',
        'Thank you. We received your payment. Your seat in "' . $b['title_en'] . '" is confirmed. Booking number: ' . $b['ref_no'] . '.',
        'شكرا لك. وصلنا دفعك. تم تأكيد مقعدك في الورشة. رقم الحجز: ' . $b['ref_no'] . '.');
    require_once __DIR__ . '/invoice_lib.php';
    owInvoiceSend('workshop', $bookingId);   // the receipt or tax invoice, by email as a PDF (27 Nov 2026)
    return true;
}
function owWsCancelBooking(int $bookingId): bool {
    $st = getDB()->prepare("UPDATE workshop_bookings SET status = 'cancelled', cancelled_at = NOW() WHERE id = :id AND status = 'booked'");
    $st->execute([':id' => $bookingId]);
    if ($st->rowCount() < 1) return false;
    owLog('workshop_book_cancel', 'workshop_booking', $bookingId, '', ['type' => 'admin', 'id' => null]);
    return true;
}
/* For the artist: first names only, by workshop (never a full name, phone or email) */
function owWsArtistBookings(int $uid): array {
    if (!owWsReady()) return [];
    $st = getDB()->prepare("SELECT b.workshop_id, b.name, b.status FROM workshop_bookings b JOIN artist_workshops w ON w.id = b.workshop_id
                            WHERE w.artist_id = :u AND b.status <> 'cancelled' ORDER BY b.id");
    $st->execute([':u' => $uid]);
    $out = [];
    foreach ($st->fetchAll() as $r) $out[(int) $r['workshop_id']][] = ['first' => owWsFirstName((string) $r['name']), 'paid' => $r['status'] === 'paid'];
    return $out;
}
