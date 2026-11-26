<?php
/* =====================================================================
 *  owner_lib.php — the owner dashboard's shared code (owner, 24 Nov 2026).
 *    Who:      the owner = signed in with the admin password (the same
 *              sign-in as admin.php, never an account). Sub-admins (accounts
 *              with the sub_admin role) see only the menu groups the owner
 *              ticked for them in Owner settings.
 *    Numbers:  read from the existing tables, plus page_stats (anonymous
 *              daily counts, stat.php), crm_people and sent_messages
 *              (owner_update.sql). Every reading is safe before the new
 *              tables exist: it gives 0 or an empty list.
 *    Settings: site_settings owner_name, owner_name_ar, vat (JSON), subadmin_groups (JSON)
 * ===================================================================== */
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/artwork_lib.php';
require_once __DIR__ . '/gallery_lib.php';

const OW_OWNER_GROUPS = [
    'people'   => ['People', 'الأشخاص'],
    'content'  => ['Art and content', 'الفن والمحتوى'],
    'money'    => ['Money', 'المال'],
    'messages' => ['Messages', 'الرسائل'],
];
const OW_CRM_STAGES = [
    'new'        => ['New', 'جديد'],
    'contacted'  => ['Contacted', 'تم التواصل'],
    'onboarding' => ['Getting started', 'في البداية'],
    'active'     => ['Active', 'نشط'],
    'inactive'   => ['Inactive', 'غير نشط'],
];
const OW_CRM_TYPES = [
    'artist'    => ['Artists', 'الفنانون', 'Artist', 'فنان'],
    'collector' => ['Collectors', 'المقتنون', 'Collector', 'مقتن'],
    'institute' => ['Institutes', 'المعاهد', 'Institute', 'معهد'],
];
/* Group messages are sent in small batches, so Hostinger's sending limits are never reached */
const OW_MSG_BATCH = 10;      // emails in one batch
const OW_MSG_GAP   = 120;     // seconds between two batches
const OW_MSG_HOUR  = 60;      // at most this many group emails in one hour

/* ---------------- who is looking ---------------- */
function owOwnerIsOwner(): bool {
    $hash = defined('ADMIN_HASH') ? (string) ADMIN_HASH : '';
    return $hash !== '' && !empty($_SESSION['is_admin']) && hash_equals(hash('sha256', $hash), (string) ($_SESSION['admin_sig'] ?? ''));
}
/* ['owner' => bool, 'user' => sub-admin row or null, 'groups' => allowed groups] — null for everyone else */
function owOwnerViewer(): ?array {
    if (owOwnerIsOwner()) return ['owner' => true, 'user' => null, 'groups' => array_keys(OW_OWNER_GROUPS)];
    if (function_exists('isLoggedIn') && isLoggedIn() && ($u = currentUser()) && ($u['role'] ?? '') === 'sub_admin' && empty($u['is_banned'])) {
        return ['owner' => false, 'user' => $u, 'groups' => owSubadminGroups((int) $u['id'])];
    }
    return null;
}
function owSubadminGroupsAll(): array {
    $j = json_decode((string) owSetting('subadmin_groups', '{}'), true);
    return is_array($j) ? $j : [];
}
function owSubadminGroups(int $uid): array {
    $all = owSubadminGroupsAll();
    return array_values(array_intersect(array_keys(OW_OWNER_GROUPS), (array) ($all[(string) $uid] ?? [])));
}
function owOwnerCan(array $viewer, string $group): bool { return $viewer['owner'] || in_array($group, $viewer['groups'], true); }
function owOwnerActor(array $viewer): array {
    return $viewer['owner'] ? ['type' => 'admin', 'id' => null] : ['type' => 'sub_admin', 'id' => (int) $viewer['user']['id']];
}
function owOwnerName(): string { return trim((string) owSetting('owner_name', '')); }
/* the owner's name in Arabic (owner, 24 Nov 2026); without it the English name is used */
function owOwnerNameAr(): string { $n = trim((string) owSetting('owner_name_ar', '')); return $n !== '' ? $n : owOwnerName(); }
function owVat(): array {
    $j = json_decode((string) owSetting('vat', ''), true);
    $j = is_array($j) ? $j : [];
    return ['on' => !empty($j['on']), 'rate' => isset($j['rate']) ? (float) $j['rate'] : 15.0, 'number' => (string) ($j['number'] ?? '')];
}

/* ---------------- small helpers ---------------- */
function owQ(string $sql, array $p = []) {      // one number, or 0 when the table is not there yet
    try { $st = getDB()->prepare($sql); $st->execute($p); $v = $st->fetchColumn(); return $v === false || $v === null ? 0 : $v; }
    catch (Throwable $e) { return 0; }
}
function owQAll(string $sql, array $p = []): array {
    try { $st = getDB()->prepare($sql); $st->execute($p); return $st->fetchAll(); }
    catch (Throwable $e) { return []; }
}
function owNum($n, int $dec = 0): string { return number_format((float) $n, $dec, '.', ','); }
function owMoney($n): string {
    $n = (float) $n; $d = abs($n - round($n)) > 0.004 ? 2 : 0;
    return '<span class="o-num">' . owNum($n, $d) . '</span> <span class="o-cur">' . owBi('SAR', 'ر.س') . '</span>';
}
const OW_MONTHS_AR = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
function owODate(?string $d, bool $time = false): string {
    if (!$d || !($t = strtotime($d))) return '—';
    $ar = date('j', $t) . ' ' . OW_MONTHS_AR[(int) date('n', $t) - 1] . ' ' . date('Y', $t) . ($time ? ' ' . date('H:i', $t) : '');
    return owBi(date('j M Y', $t) . ($time ? ' ' . date('H:i', $t) : ''), $ar);
}
function owMonthBi(string $ym): string {
    $t = strtotime($ym . '-01');
    return owBi(date('F Y', $t), OW_MONTHS_AR[(int) date('n', $t) - 1] . ' ' . date('Y', $t));
}
function owPct(float $now, float $before): ?float { return $before > 0 ? round(($now - $before) / $before * 100) : null; }

/* The chosen period: 1 (today), 7 or 30 days, and the same length just before it */
function owOwnerPeriod(): int { $d = (int) ($_GET['d'] ?? 7); return in_array($d, [1, 7, 30], true) ? $d : 7; }
function owRange(int $days): array {
    $today = (string) owQ('SELECT CURDATE()') ?: date('Y-m-d');
    $from = date('Y-m-d', strtotime($today . ' -' . ($days - 1) . ' days'));
    $pFrom = date('Y-m-d', strtotime($from . ' -' . $days . ' days'));
    $pTo = date('Y-m-d', strtotime($from . ' -1 day'));
    return ['from' => $from, 'to' => $today, 'pfrom' => $pFrom, 'pto' => $pTo];
}

/* ---------------- the numbers ---------------- */
function owKVisitors(string $a, string $b): int {
    return owHasTable('visitor_stats') ? (int) owQ('SELECT COALESCE(SUM(visits),0) FROM visitor_stats WHERE day BETWEEN :a AND :b', [':a' => $a, ':b' => $b]) : 0;
}
function owKNewUsers(string $role, string $a, string $b): int {
    return (int) owQ('SELECT COUNT(*) FROM users WHERE role = :r AND DATE(created_at) BETWEEN :a AND :b', [':r' => $role, ':a' => $a, ':b' => $b]);
}
function owKSent(string $a, string $b): int {     // artworks sent for review in the period
    return owHasTable('activity_log') ? (int) owQ("SELECT COUNT(*) FROM activity_log WHERE action = 'artwork_submit' AND DATE(created_at) BETWEEN :a AND :b", [':a' => $a, ':b' => $b]) : 0;
}
/* sold artworks in a period: count, value, Oweili's commission (each at the rate on its sale day) */
function owKSales(string $a, string $b): array {
    $out = ['n' => 0, 'value' => 0.0, 'commission' => 0.0];
    if (!owHasColumn('artworks', 'sold_at')) return $out;
    foreach (owQAll("SELECT price, sold_at FROM artworks WHERE status = 'sold' AND sold_at IS NOT NULL AND DATE(sold_at) BETWEEN :a AND :b", [':a' => $a, ':b' => $b]) as $r) {
        $out['n']++; $out['value'] += (float) $r['price'];
        $out['commission'] += round((float) $r['price'] * owCommissionAt((string) $r['sold_at']) / 100, 2);
    }
    return $out;
}
function owKBookings(string $a, string $b): int {
    if (!owHasTable('course_registrations')) return 0;
    $col = owHasColumn('course_registrations', 'registered_at') ? 'registered_at' : 'created_at';
    return (int) owQ("SELECT COUNT(*) FROM course_registrations WHERE status <> 'cancelled' AND DATE($col) BETWEEN :a AND :b", [':a' => $a, ':b' => $b]);
}
/* Oweili's commission from courses and museum tickets paid in a period */
function owKPartnerCommission(string $a, string $b): float {
    $s = 0.0;
    if (owHasColumn('course_registrations', 'commission_amount'))
        $s += (float) owQ("SELECT COALESCE(SUM(commission_amount),0) FROM course_registrations WHERE paid_at IS NOT NULL AND status <> 'cancelled' AND DATE(paid_at) BETWEEN :a AND :b", [':a' => $a, ':b' => $b]);
    if (owHasTable('museum_tickets'))
        $s += (float) owQ("SELECT COALESCE(SUM(commission_amount),0) FROM museum_tickets WHERE paid_at IS NOT NULL AND status NOT IN ('cancelled','refunded') AND DATE(paid_at) BETWEEN :a AND :b", [':a' => $a, ':b' => $b]);
    return $s;
}
function owKInquiries(string $a, string $b): int {
    return owHasTable('artwork_inquiries') ? (int) owQ('SELECT COUNT(*) FROM artwork_inquiries WHERE DATE(created_at) BETWEEN :a AND :b', [':a' => $a, ':b' => $b]) : 0;
}

/* Everything waiting for the owner, the same lists as admin.php → Waiting for approval */
function owWaitingTotal(): int {
    require_once __DIR__ . '/approval_lib.php';
    $n = (int) owQ('SELECT COUNT(*) FROM artworks a WHERE a.is_approved = 0' . owNotDraftSql('a') . (owHasColumn('artworks', 'rejected_at') ? ' AND a.rejected_at IS NULL' : ''));
    try { $n += count(owWaitingChanges()) + count(owWaitingArtists()); } catch (Throwable $e) {}
    if (owHasTable('articles')) $n += (int) owQ("SELECT COUNT(*) FROM articles WHERE status = 'draft' AND author_user_id IS NULL");
    try { require_once __DIR__ . '/artmap_lib.php'; $n += owArtPlacesDrafts() + owClaimsWaiting(); } catch (Throwable $e) {}
    if (owHasColumn('courses', 'bookable') && owHasColumn('institutes', 'via_signup'))
        $n += (int) owQ("SELECT (SELECT COUNT(*) FROM courses WHERE bookable = 1 AND status = 'pending') + (SELECT COUNT(*) FROM institutes i JOIN users u ON u.id = i.user_id WHERE i.via_signup = 1 AND u.is_approved = 0)");
    $n += count(owPayoutsWaiting());
    return $n;
}
function owPayoutsWaiting(): array {
    try { require_once __DIR__ . '/order_lib.php'; return owOrderPayoutWaiting(); } catch (Throwable $e) { return []; }
}
/* The alerts on the home page: only what really needs the owner, each with its page */
function owOwnerAlerts(): array {
    $a = [];
    $add = function (int $n, string $en, string $ar, string $href, string $tone = 'warn') use (&$a) { if ($n > 0) $a[] = compact('n', 'en', 'ar', 'href', 'tone'); };
    $add(owWaitingTotal(), 'Waiting for your approval', 'بانتظار موافقتك', 'admin.php#pending', 'hot');
    $add(count(owPayoutsWaiting()), 'Artist payouts to approve', 'دفعات فنانين بانتظار موافقتك', 'admin.php#pending', 'hot');
    if (owHasTable('order_complaints')) $add((int) owQ("SELECT COUNT(*) FROM order_complaints WHERE status = 'open'"), 'Open complaints from collectors', 'شكاوى مفتوحة من المقتنين', 'admin_orders.php', 'hot');
    try { require_once __DIR__ . '/artmap_lib.php'; $add(owClaimsWaiting(), '"Is this your place?" messages', 'رسائل «هل هذا مكانك؟»', 'admin_art_map.php#claims'); } catch (Throwable $e) {}
    if (owHasTable('artwork_inquiries') && owHasColumn('artwork_inquiries', 'status')) $add((int) owQ("SELECT COUNT(*) FROM artwork_inquiries WHERE status = 'new'"), 'New "I’m Interested" requests', 'طلبات «أنا مهتم» جديدة', 'admin.php#inquiries');
    if (owHasTable('course_leads')) $add((int) owQ("SELECT COUNT(*) FROM course_leads WHERE status = 'new'"), 'New "Notify me" requests', 'طلبات «نبّهني» جديدة', 'admin_bookings.php#leads');
    if (owHasColumn('course_registrations', 'hold_until')) $add((int) owQ("SELECT COUNT(*) FROM course_registrations WHERE status = 'pending' AND hold_until IS NULL AND paid_at IS NULL"), 'Course bookings waiting for payment', 'حجوزات دورات بانتظار الدفع', 'admin_bookings.php');
    /* Artist workshops (owner, 25 Nov 2026): requests, workshops and bookings waiting */
    if (owHasTable('artist_workshop_requests')) $add((int) owQ("SELECT COUNT(*) FROM artist_workshop_requests WHERE status = 'pending'"), 'Artists asking to offer workshops', 'فنانون يطلبون تقديم ورش', 'admin_workshops.php#requests', 'hot');
    if (owHasTable('artist_workshops')) $add((int) owQ("SELECT COUNT(*) FROM artist_workshops WHERE status = 'pending'"), 'Workshops waiting for approval', 'ورش بانتظار الموافقة', 'admin_workshops.php#workshops', 'hot');
    if (owHasTable('workshop_bookings')) $add((int) owQ("SELECT COUNT(*) FROM workshop_bookings WHERE status = 'booked'"), 'Workshop bookings waiting for payment', 'حجوزات ورش بانتظار الدفع', 'admin_workshops.php#bookings');
    if (owHasTable('crm_people')) $add((int) owQ('SELECT COUNT(*) FROM crm_people WHERE follow_up IS NOT NULL AND follow_up <= CURDATE()'), 'Follow-ups due today', 'متابعات موعدها اليوم', 'owner.php?p=crm&f=due');
    return $a;
}

/* One value per day for the trend chart */
function owDaily(string $kind, string $a, string $b): array {
    $sql = [
        'visitors' => "SELECT day d, SUM(visits) n FROM visitor_stats WHERE day BETWEEN :a AND :b GROUP BY day",
        'accounts' => "SELECT DATE(created_at) d, COUNT(*) n FROM users WHERE role IN ('artist','collector','institute') AND DATE(created_at) BETWEEN :a AND :b GROUP BY DATE(created_at)",
        'requests' => "SELECT DATE(created_at) d, COUNT(*) n FROM artwork_inquiries WHERE DATE(created_at) BETWEEN :a AND :b GROUP BY DATE(created_at)",
        'bookings' => "SELECT DATE(registered_at) d, COUNT(*) n FROM course_registrations WHERE status <> 'cancelled' AND DATE(registered_at) BETWEEN :a AND :b GROUP BY DATE(registered_at)",
    ][$kind] ?? '';
    $got = [];
    foreach (owQAll($sql, [':a' => $a, ':b' => $b]) as $r) $got[(string) $r['d']] = (int) $r['n'];
    $out = [];
    for ($t = strtotime($a); $t <= strtotime($b); $t += 86400) { $d = date('Y-m-d', $t); $out[$d] = $got[$d] ?? 0; }
    return $out;
}
/* A small line chart drawn on the server (no outside tools) */
function owSvgLine(array $series, string $label): string {
    $vals = array_values($series); $keys = array_keys($series); $n = count($vals);
    $W = 640; $H = 240; $pl = 36; $pr = 12; $pt = 12; $pb = 28;
    $max = max(1, max($vals ?: [0])); $max = $max <= 4 ? 4 : (int) ceil($max / 4) * 4;
    $x = function ($i) use ($n, $W, $pl, $pr) { return $n <= 1 ? ($pl + ($W - $pl - $pr) / 2) : $pl + ($W - $pl - $pr) * $i / ($n - 1); };
    $y = function ($v) use ($max, $H, $pt, $pb) { return $H - $pb - ($H - $pt - $pb) * $v / $max; };
    $pts = []; foreach ($vals as $i => $v) $pts[] = round($x($i), 1) . ',' . round($y($v), 1);
    $s = '<svg class="o-chart" viewBox="0 0 ' . $W . ' ' . $H . '" role="img" aria-label="' . e($label) . '">';
    for ($g = 0; $g <= 4; $g++) { $gy = round($y($max * $g / 4), 1); $s .= '<line class="o-grid" x1="' . $pl . '" x2="' . ($W - $pr) . '" y1="' . $gy . '" y2="' . $gy . '"/><text class="o-ax" x="' . ($pl - 6) . '" y="' . ($gy + 4) . '" text-anchor="end">' . owNum($max * $g / 4) . '</text>'; }
    if ($n > 0) {
        $s .= '<polygon class="o-area" points="' . round($x(0), 1) . ',' . ($H - $pb) . ' ' . implode(' ', $pts) . ' ' . round($x($n - 1), 1) . ',' . ($H - $pb) . '"/>';
        $s .= '<polyline class="o-line" points="' . implode(' ', $pts) . '"/>';
        $step = max(1, (int) ceil($n / 7));
        foreach ($keys as $i => $k) {
            if ($i % $step !== 0 && $i !== $n - 1) continue;
            $s .= '<text class="o-ax" x="' . round($x($i), 1) . '" y="' . ($H - 8) . '" text-anchor="middle">' . date('j/n', strtotime($k)) . '</text>';
        }
        foreach ($vals as $i => $v) $s .= '<circle class="o-dot" cx="' . round($x($i), 1) . '" cy="' . round($y($v), 1) . '" r="3"><title>' . e($keys[$i] . ': ' . $v) . '</title></circle>';
    }
    return $s . '</svg>';
}

/* page_stats sums */
function owStatSum(string $kind, string $a, string $b, string $lang = '', string $dev = '', string $by = 'item', int $limit = 0): array {
    if (!owHasTable('page_stats')) return [];
    $w = 'kind = :k AND day BETWEEN :a AND :b'; $p = [':k' => $kind, ':a' => $a, ':b' => $b];
    if ($lang !== '') { $w .= ' AND lang = :l'; $p[':l'] = $lang; }
    if ($dev !== '') { $w .= ' AND device = :d'; $p[':d'] = $dev; }
    $col = in_array($by, ['item', 'lang', 'device'], true) ? $by : 'item';
    $rows = owQAll("SELECT $col g, SUM(views) n FROM page_stats WHERE $w GROUP BY $col ORDER BY n DESC, g" . ($limit > 0 ? ' LIMIT ' . (int) $limit : ''), $p);
    $out = []; foreach ($rows as $r) $out[(string) $r['g']] = (int) $r['n'];
    return $out;
}
/* The visitor journey: home → gallery → artwork → "I'm Interested" (each browser once a day) */
function owJourney(string $a, string $b): array {
    $s = owStatSum('step', $a, $b);
    $steps = [
        ['home', 'Home page', 'الصفحة الرئيسية'], ['gallery', 'Gallery', 'المعرض'],
        ['artwork', 'An artwork', 'عمل فني'], ['interest', '"I’m Interested" sent', 'إرسال «أنا مهتم»'],
    ];
    $out = []; $worst = null; $worstDrop = -1;
    foreach ($steps as $i => [$k, $en, $ar]) {
        $n = (int) ($s[$k] ?? 0);
        $drop = null;
        if ($i > 0) {
            $prev = (int) $out[$i - 1]['n'];
            $drop = $prev > 0 ? max(0, (int) round((1 - $n / $prev) * 100)) : null;
            if ($drop !== null && $drop > $worstDrop) { $worstDrop = $drop; $worst = $i; }
        }
        $out[] = compact('k', 'en', 'ar', 'n', 'drop');
    }
    return ['steps' => $out, 'worst' => $worst];
}

/* Recent activity (the people and the team), without the system's own notes */
const OW_ACTIVITY_HIDE = ['stage_photo', 'cert_check', 'admin_alert', 'admin_alert_mail', 'bashro_send', 'auction_export', 'view'];
function owRecentActivity(int $limit = 10): array {
    if (!owHasTable('activity_log')) return [];
    $in = implode(',', array_map(fn($x) => getDB()->quote($x), OW_ACTIVITY_HIDE));
    $dev = owHasColumn('activity_log', 'device') ? 'device' : 'NULL AS device';
    return owQAll("SELECT l.actor_type, l.actor_id, l.action, l.target_type, l.target_id, l.created_at, l.$dev,
                          u.full_name_en, u.artist_name
                   FROM activity_log l LEFT JOIN users u ON u.id = l.actor_id
                   WHERE l.action NOT IN ($in) AND l.actor_type NOT IN ('system', 'admin') ORDER BY l.created_at DESC, l.id DESC LIMIT " . (int) $limit);
}
const OW_ACTOR_WORDS = ['artist' => ['Artist', 'فنان'], 'buyer' => ['Collector', 'مقتن'], 'admin' => ['Owner', 'المالك'], 'sub_admin' => ['Sub-admin', 'مشرف'],
    'institute' => ['Institute', 'معهد'], 'museum' => ['Museum', 'متحف'], 'guest' => ['Visitor', 'زائر'], 'system' => ['Oweili', 'أويلي']];
function owActionText(string $action): array {
    if ($action === 'admin_login') return ['Signed in', 'سجّل الدخول'];
    if ($action === 'setting_change') return ['Changed a setting', 'غيّر إعدادا'];
    if ($action === 'crm_update') return ['Updated a follow-up note', 'حدّث ملاحظة متابعة'];
    if ($action === 'group_message') return ['Sent a group message', 'أرسل رسالة جماعية'];
    if (isset(OW_ACTION_WORDS[$action])) return OW_ACTION_WORDS[$action];
    $en = ucfirst(str_replace('_', ' ', $action));
    return [$en, $en];
}

/* Site health: errors in the last 24 hours, the newest database backup, the important switches */
function owSiteHealth(): array {
    $errors = 0; $f = __DIR__ . '/.logs/php-errors.log';
    if (is_file($f) && is_readable($f)) {
        $since = time() - 86400; $fh = @fopen($f, 'r');
        if ($fh) {
            $size = filesize($f); if ($size > 600000) fseek($fh, $size - 600000);
            while (($line = fgets($fh)) !== false) if (preg_match('/^\[([^\]]+)\]/', $line, $m) && ($t = strtotime($m[1])) && $t >= $since) $errors++;
            fclose($fh);
        }
    }
    $backup = null;
    foreach (glob(__DIR__ . '/.backups/*.gz') ?: [] as $b) { $t = filemtime($b); if ($backup === null || $t > $backup) $backup = $t; }
    $demo = false; try { require_once __DIR__ . '/demo_lib.php'; $demo = owDemoOn(); } catch (Throwable $e) {}
    $pay = false; try { require_once __DIR__ . '/order_lib.php'; $pay = owPaymentsOn(); } catch (Throwable $e) {}
    return ['errors' => $errors, 'backup' => $backup, 'demo' => $demo, 'payments' => $pay,
            'courses' => owPartOn('courses'), 'tickets' => owPartOn('bookings')];
}

/* ---------------- money, month by month ---------------- */
function owMonthReport(int $months = 12): array {
    $rows = []; $vat = owVat();
    $first = date('Y-m-01', strtotime(date('Y-m-01') . ' -' . ($months - 1) . ' months'));
    for ($t = strtotime($first); $t <= time(); $t = strtotime(date('Y-m-01', $t) . ' +1 month')) {
        $a = date('Y-m-01', $t); $b = date('Y-m-t', $t);
        $s = owKSales($a, $b);
        $cb = owHasColumn('course_registrations', 'amount') ? owQAll("SELECT COUNT(*) n, COALESCE(SUM(amount),0) amt, COALESCE(SUM(commission_amount),0) com, COALESCE(SUM(partner_amount),0) prt
                     FROM course_registrations WHERE paid_at IS NOT NULL AND status <> 'cancelled' AND DATE(paid_at) BETWEEN :a AND :b", [':a' => $a, ':b' => $b])[0] ?? [] : [];
        $tk = owHasTable('museum_tickets') ? owQAll("SELECT COALESCE(SUM(amount),0) amt, COALESCE(SUM(commission_amount),0) com, COALESCE(SUM(partner_amount),0) prt
                     FROM museum_tickets WHERE paid_at IS NOT NULL AND status NOT IN ('cancelled','refunded') AND DATE(paid_at) BETWEEN :a AND :b", [':a' => $a, ':b' => $b])[0] ?? [] : [];
        $refunds = 0.0;
        if (owHasColumn('course_registrations', 'refund_amount')) $refunds += (float) owQ('SELECT COALESCE(SUM(refund_amount),0) FROM course_registrations WHERE cancelled_at IS NOT NULL AND DATE(cancelled_at) BETWEEN :a AND :b', [':a' => $a, ':b' => $b]);
        if (owHasTable('orders')) $refunds += (float) owQ("SELECT COALESCE(SUM(amount),0) FROM orders WHERE status = 'refunded' AND DATE(updated_at) BETWEEN :a AND :b", [':a' => $a, ':b' => $b]);
        $paidOut = 0.0;
        if (owHasTable('payouts')) $paidOut += (float) owQ("SELECT COALESCE(SUM(amount),0) FROM payouts WHERE status = 'paid' AND DATE(updated_at) BETWEEN :a AND :b", [':a' => $a, ':b' => $b]);
        if (owHasTable('partner_payouts')) $paidOut += (float) owQ("SELECT COALESCE(SUM(amount),0) FROM partner_payouts WHERE status = 'paid' AND DATE(paid_at) BETWEEN :a AND :b", [':a' => $a, ':b' => $b]);
        $courseCom = (float) ($cb['com'] ?? 0) + (float) ($tk['com'] ?? 0);
        $commission = $s['commission'] + $courseCom;
        $rows[] = [
            'month' => date('Y-m', $t), 'sold' => $s['n'], 'sales' => $s['value'], 'art_com' => $s['commission'], 'artist_share' => $s['value'] - $s['commission'],
            'bookings' => (int) ($cb['n'] ?? 0), 'partner_sales' => (float) ($cb['amt'] ?? 0) + (float) ($tk['amt'] ?? 0), 'partner_com' => $courseCom,
            'partner_share' => (float) ($cb['prt'] ?? 0) + (float) ($tk['prt'] ?? 0), 'commission' => $commission,
            'vat' => $vat['on'] ? round($commission * $vat['rate'] / 100, 2) : 0.0, 'refunds' => $refunds, 'paid_out' => $paidOut,
        ];
    }
    return array_reverse($rows);
}
function owPayoutsOpen(): array {
    $o = ['artists' => 0.0, 'artists_n' => 0, 'partners' => 0.0, 'partners_n' => 0];
    if (owHasTable('payouts')) { $r = owQAll("SELECT COUNT(*) n, COALESCE(SUM(amount),0) s FROM payouts WHERE status = 'pending'")[0] ?? []; $o['artists'] = (float) ($r['s'] ?? 0); $o['artists_n'] = (int) ($r['n'] ?? 0); }
    if (owHasTable('partner_payouts')) { $r = owQAll("SELECT COUNT(*) n, COALESCE(SUM(amount),0) s FROM partner_payouts WHERE status = 'pending'")[0] ?? []; $o['partners'] = (float) ($r['s'] ?? 0); $o['partners_n'] = (int) ($r['n'] ?? 0); }
    return $o;
}

/* ---------------- people (CRM) ---------------- */
function owCrmList(array $f, int $limit = 60, int $offset = 0): array {
    $types = array_keys(OW_CRM_TYPES);
    $w = ['u.role IN (' . implode(',', array_map(fn($x) => getDB()->quote($x), $types)) . ')']; $p = [];
    if (isset(OW_CRM_TYPES[$f['t'] ?? ''])) { $w[] = 'u.role = :t'; $p[':t'] = $f['t']; }
    $crm = owHasTable('crm_people');
    if ($crm && isset(OW_CRM_STAGES[$f['s'] ?? ''])) { $w[] = "COALESCE(c.stage,'new') = :s"; $p[':s'] = $f['s']; }
    if ($crm && ($f['f'] ?? '') === 'due') $w[] = 'c.follow_up IS NOT NULL AND c.follow_up <= CURDATE()';
    if ($crm && ($f['f'] ?? '') === 'soon') $w[] = 'c.follow_up > CURDATE()';
    if (($f['st'] ?? '') === 'suspended') $w[] = 'COALESCE(u.is_banned,0) = 1';
    if (($f['st'] ?? '') === 'pending') $w[] = "COALESCE(u.is_banned,0) = 0 AND u.role = 'artist' AND u.is_approved = 0";
    if (($f['st'] ?? '') === 'active') $w[] = "COALESCE(u.is_banned,0) = 0 AND (u.role <> 'artist' OR u.is_approved = 1)";
    $q = trim((string) ($f['q'] ?? ''));
    if ($q !== '') {
        $w[] = '(u.full_name_en LIKE :q1 OR u.full_name_ar LIKE :q2 OR u.artist_name LIKE :q3 OR u.email LIKE :q4 OR u.phone LIKE :q5 OR u.city LIKE :q6)';
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], mb_substr($q, 0, 80)) . '%';
        foreach (['q1', 'q2', 'q3', 'q4', 'q5', 'q6'] as $k) $p[':' . $k] = $like;
    }
    $join = $crm ? 'LEFT JOIN crm_people c ON c.user_id = u.id' : '';
    $cols = $crm ? "COALESCE(c.stage,'new') AS stage, c.follow_up, c.note" : "'new' AS stage, NULL AS follow_up, NULL AS note";
    $last = owHasColumn('users', 'last_login_at') ? 'u.last_login_at' : 'NULL';
    $where = implode(' AND ', $w);
    $total = (int) owQ("SELECT COUNT(*) FROM users u $join WHERE $where", $p);
    $rows = owQAll("SELECT u.id, u.role, u.email, u.phone, u.city, u.full_name_en, u.full_name_ar, u.artist_name, u.is_approved,
                           COALESCE(u.is_banned,0) is_banned, u.created_at, $last AS last_login_at, $cols
                    FROM users u $join WHERE $where
                    ORDER BY " . ($crm && ($f['f'] ?? '') !== '' ? 'c.follow_up IS NULL, c.follow_up,' : '') . " u.created_at DESC LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset, $p);
    return ['total' => $total, 'rows' => $rows];
}
function owCrmPerson(int $uid): ?array {
    $crm = owHasTable('crm_people');
    $cols = $crm ? "COALESCE(c.stage,'new') AS stage, c.follow_up, c.note, c.updated_at AS crm_updated" : "'new' AS stage, NULL AS follow_up, NULL AS note, NULL AS crm_updated";
    $last = owHasColumn('users', 'last_login_at') ? 'u.last_login_at' : 'NULL';
    $rows = owQAll("SELECT u.id, u.role, u.email, u.phone, u.city, " . (owHasColumn('users', 'country') ? 'u.country' : "'' AS country") . ", u.full_name_en, u.full_name_ar, u.artist_name, u.is_approved, u.is_verified,
                           COALESCE(u.is_banned,0) is_banned, u.created_at, $last AS last_login_at, $cols" . (owHasColumn('users', 'notify_news') ? ', u.notify_news' : ', 1 AS notify_news') . "
                    FROM users u " . ($crm ? 'LEFT JOIN crm_people c ON c.user_id = u.id' : '') . "
                    WHERE u.id = :id AND u.role IN ('artist','collector','institute')", [':id' => $uid]);
    return $rows[0] ?? null;
}
function owCrmSave(int $uid, string $stage, ?string $follow, string $note, array $actor): bool {
    if (!owHasTable('crm_people') || !isset(OW_CRM_STAGES[$stage])) return false;
    if ($follow !== null && $follow !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $follow)) return false;
    if (!owCrmPerson($uid)) return false;
    getDB()->prepare('INSERT INTO crm_people (user_id, stage, follow_up, note) VALUES (:u, :s, :f, :n)
                      ON DUPLICATE KEY UPDATE stage = VALUES(stage), follow_up = VALUES(follow_up), note = VALUES(note)')
           ->execute([':u' => $uid, ':s' => $stage, ':f' => ($follow ?: null), ':n' => mb_substr(trim($note), 0, 4000)]);
    owLog('crm_update', 'user', $uid, $stage, $actor);
    return true;
}
/* a name kept whole, so a Latin name such as "Lina A." reads correctly on an Arabic page */
function owBdi(string $s): string { return '<bdi>' . e($s) . '</bdi>'; }
function owPersonName(array $u): string {
    $n = owRealName((string) ($u['artist_name'] ?? '')) ?: owRealName((string) ($u['full_name_en'] ?? '')) ?: owRealName((string) ($u['full_name_ar'] ?? ''));
    return $n !== '' ? $n : '#' . (int) $u['id'];
}

/* ---------------- group messages ---------------- */
/* Who gets it. News: only people who ticked "email me Oweili news". Service: everyone.
   Always: a confirmed email address, not suspended, never staff */
function owMsgRecipients(string $who, string $kind, string $stage, array $ids): array {
    $w = ["COALESCE(u.is_banned,0) = 0", "u.email <> ''", "u.role IN ('artist','collector','institute')"]; $p = [];
    if (owHasColumn('users', 'is_verified')) $w[] = 'u.is_verified = 1';
    if ($kind === 'news') $w[] = owHasColumn('users', 'notify_news') ? 'u.notify_news = 1' : '0';
    if ($who === 'picked') {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) return [];
        $w[] = 'u.id IN (' . implode(',', $ids) . ')';
    } elseif (isset(OW_CRM_TYPES[$who])) { $w[] = 'u.role = :r'; $p[':r'] = $who; }
    elseif ($who !== 'all') return [];
    $join = '';
    if (isset(OW_CRM_STAGES[$stage]) && owHasTable('crm_people')) { $join = 'LEFT JOIN crm_people c ON c.user_id = u.id'; $w[] = "COALESCE(c.stage,'new') = :s"; $p[':s'] = $stage; }
    return owQAll('SELECT u.id, u.email FROM users u ' . $join . ' WHERE ' . implode(' AND ', $w) . ' ORDER BY u.id', $p);
}
function owMsgQueue(array $people, string $kind, string $subjEn, string $subjAr, string $bodyEn, string $bodyAr): string {
    if (!owHasTable('sent_messages') || !$people) return '';
    $batch = bin2hex(random_bytes(8));
    $subject = mb_substr(trim($subjEn) . ' · ' . trim($subjAr), 0, 200);
    $st = getDB()->prepare('INSERT INTO sent_messages (batch, user_id, kind, subject, body_en, body_ar) VALUES (:b, :u, :k, :s, :e, :a)');
    foreach ($people as $p) $st->execute([':b' => $batch, ':u' => (int) $p['id'], ':k' => $kind, ':s' => $subject, ':e' => $bodyEn, ':a' => $bodyAr]);
    return $batch;
}
function owMsgBody(array $m): string {
    $txt = trim((string) $m['body_en']) . "\n\n— — —\n\n" . trim((string) $m['body_ar']);
    if ($m['kind'] === 'news') {
        $txt .= "\n\n" . 'You get this because you chose to receive Oweili news. To stop, untick “Oweili news” in your account: https://oweili.com/login.php'
              . "\n" . 'وصلتك هذه الرسالة لأنك اخترت استلام أخبار أويلي. لإيقافها، ألغِ اختيار «أخبار أويلي» في حسابك: https://oweili.com/login.php';
    }
    return $txt;
}
/* Sends the next small batch, if it is time. Called after the owner's pages have been sent */
function owMsgTick(): int {
    if (!owHasTable('sent_messages')) return 0;
    $db = getDB();
    $lastBatch = (string) owQ("SELECT MAX(sent_at) FROM sent_messages WHERE status IN ('sent','failed')");
    if ($lastBatch !== '0' && $lastBatch !== '' && strtotime((string) owQ('SELECT NOW()')) - strtotime($lastBatch) < OW_MSG_GAP) return 0;
    $hour = (int) owQ("SELECT COUNT(*) FROM sent_messages WHERE status IN ('sent','failed') AND sent_at > (NOW() - INTERVAL 1 HOUR)");
    $room = min(OW_MSG_BATCH, OW_MSG_HOUR - $hour);
    if ($room <= 0) return 0;
    $lock = __DIR__ . '/.logs/owner_msg.lock';
    @mkdir(__DIR__ . '/.logs', 0750, true);
    $fh = @fopen($lock, 'c'); if (!$fh || !flock($fh, LOCK_EX | LOCK_NB)) return 0;
    $done = 0;
    try {
        require_once __DIR__ . '/mail_lib.php';
        $rows = owQAll("SELECT m.*, u.email, COALESCE(u.is_banned,0) banned" . (owHasColumn('users', 'notify_news') ? ', u.notify_news' : ', 1 AS notify_news') . "
                        FROM sent_messages m JOIN users u ON u.id = m.user_id WHERE m.status = 'queued' ORDER BY m.id LIMIT " . (int) $room);
        $up = $db->prepare('UPDATE sent_messages SET status = :s, sent_at = NOW() WHERE id = :id');
        foreach ($rows as $m) {
            if ((int) $m['banned'] === 1 || ($m['kind'] === 'news' && (int) $m['notify_news'] !== 1)) { $up->execute([':s' => 'skipped', ':id' => $m['id']]); continue; }
            $opt = [];   // news: the personal one-click unsubscribe link and headers (owner, 27 Nov 2026)
            if ($m['kind'] === 'news') { require_once __DIR__ . '/unsub_lib.php'; $opt['unsub'] = owNewsUnsubUrl((int) $m['user_id'], (string) $m['email']); }
            $noLog = null;
            $ok = owSendMail((string) $m['email'], (string) $m['subject'], owMsgBody($m), false, $noLog, $opt);
            $up->execute([':s' => $ok ? 'sent' : 'failed', ':id' => $m['id']]);
            $done++;
        }
    } catch (Throwable $e) { error_log('owMsgTick: ' . $e->getMessage()); }
    flock($fh, LOCK_UN); fclose($fh);
    return $done;
}
function owMsgBatches(int $limit = 20): array {
    if (!owHasTable('sent_messages')) return [];
    return owQAll("SELECT batch, MIN(subject) subject, MIN(kind) kind, MIN(created_at) created_at, COUNT(*) total,
                          SUM(status = 'sent') sent, SUM(status = 'failed') failed, SUM(status = 'queued') queued, SUM(status = 'skipped') skipped
                   FROM sent_messages GROUP BY batch ORDER BY MIN(id) DESC LIMIT " . (int) $limit);
}
