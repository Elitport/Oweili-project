<?php
/* =====================================================================
 *  platform_lib.php — shared pieces for the Artist, Buyer and Admin
 *  dashboards, ready for Bashro (the central control center) later.
 *    - owHasColumn / owHasTable: pages keep working before
 *      dashboards_update.sql has been run on the live database
 *    - owLog(): the activity log (who did what and when)
 *    - owUserStatusSql / owArtworkStatusSql: one status word for every
 *      person and artwork, worked out from the existing columns
 *    - favorites, inquiry status words, rate limits
 *  Tables: activity_log, favorites, users, artworks, artwork_inquiries
 * ===================================================================== */
require_once __DIR__ . '/artwork_lib.php';

/* Does this table have this column? (read once per page) */
function owHasColumn(string $table, string $col): bool {
    static $cols = [];
    if (!isset($cols[$table])) {
        $cols[$table] = [];
        try {
            $st = getDB()->prepare('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
            $st->execute([$table]);
            $cols[$table] = array_flip($st->fetchAll(PDO::FETCH_COLUMN));
        } catch (Throwable $e) { error_log('owHasColumn: ' . $e->getMessage()); }
    }
    return isset($cols[$table][$col]);
}
function owHasTable(string $table): bool {
    static $t = [];
    if (!isset($t[$table])) {
        try {
            $st = getDB()->prepare('SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
            $st->execute([$table]);
            $t[$table] = (bool) $st->fetchColumn();
        } catch (Throwable $e) { $t[$table] = false; }
    }
    return $t[$table];
}
/* true once dashboards_update.sql has been run */
function owPlatformReady(): bool {
    return owHasTable('activity_log') && owHasTable('favorites') && owHasColumn('artwork_inquiries', 'status');
}

/* ---------------------------------------------------------------------
 *  Activity log
 * ------------------------------------------------------------------- */

/* A one-way code of the visitor's address, new every month (never the address itself) */
function owIpHash(): string {
    $salt = defined('ADMIN_HASH') ? (string) ADMIN_HASH : __DIR__;
    return hash('sha256', 'oweili-log|' . gmdate('Y-m') . '|' . clientIp() . '|' . $salt);
}

/* The account role as an activity-log actor type */
function owActorType(?string $role): string {
    return ['artist' => 'artist', 'collector' => 'buyer', 'admin' => 'admin', 'sub_admin' => 'sub_admin',
            'institute' => 'institute'][(string) $role] ?? 'guest';
}

/* Writes one line: $actor = ['type' => 'buyer', 'id' => 5]. Without it: the
   signed-in person, else an admin signed in on admin.php, else a guest.
   Never stops the page if the log cannot be written. */
function owLog(string $action, ?string $targetType = null, ?int $targetId = null, string $details = '', ?array $actor = null): void {
    try {
        if (!owHasTable('activity_log')) return;
        if ($actor === null) {
            $actor = ['type' => 'guest', 'id' => null];
            if (!empty($_SESSION['user_id']) && function_exists('currentUser') && ($u = currentUser())) {
                $actor = ['type' => owActorType($u['role'] ?? ''), 'id' => (int) $u['id']];
            } elseif (!empty($_SESSION['is_admin'])) {
                $actor = ['type' => 'admin', 'id' => null];
            }
        }
        getDB()->prepare('INSERT INTO activity_log (actor_type, actor_id, action, target_type, target_id, details, ip_hash)
                          VALUES (:at, :ai, :ac, :tt, :ti, :d, :ip)')
               ->execute([':at' => $actor['type'], ':ai' => $actor['id'], ':ac' => substr($action, 0, 60),
                          ':tt' => $targetType, ':ti' => $targetId, ':d' => mb_substr($details, 0, 500), ':ip' => owIpHash()]);
    } catch (Throwable $e) {
        error_log('owLog: ' . $e->getMessage());
    }
}

/* How many times this visitor did $action in the last $seconds (for rate limits) */
function owRecentCount(string $action, int $seconds): int {
    try {
        if (!owHasTable('activity_log')) return 0;
        $st = getDB()->prepare('SELECT COUNT(*) FROM activity_log WHERE ip_hash = :ip AND action = :a
                                AND created_at > (NOW() - INTERVAL :s SECOND)');
        $st->bindValue(':ip', owIpHash());
        $st->bindValue(':a', $action);
        $st->bindValue(':s', $seconds, PDO::PARAM_INT);
        $st->execute();
        return (int) $st->fetchColumn();
    } catch (Throwable $e) { return 0; }
}

/* One person's own activity, newest first */
function owActivityFor(string $actorType, int $actorId, int $limit = 20): array {
    try {
        if (!owHasTable('activity_log')) return [];
        $st = getDB()->prepare('SELECT action, target_type, target_id, details, created_at FROM activity_log
                                WHERE actor_type = :t AND actor_id = :i ORDER BY id DESC LIMIT :l');
        $st->bindValue(':t', $actorType);
        $st->bindValue(':i', $actorId, PDO::PARAM_INT);
        $st->bindValue(':l', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    } catch (Throwable $e) { return []; }
}

/* Words for the activity list, in English and Arabic */
const OW_ACTION_WORDS = [
    'signup'          => ['Created the account', 'أنشأ الحساب'],
    'email_verified'  => ['Confirmed the email address', 'أكّد البريد الإلكتروني'],
    'login'           => ['Signed in', 'سجّل الدخول'],
    'logout'          => ['Signed out', 'سجّل الخروج'],
    'profile_update'  => ['Updated the profile', 'حدّث الملف الشخصي'],
    'password_change' => ['Changed the password', 'غيّر كلمة المرور'],
    'password_reset'  => ['Reset the password', 'أعاد تعيين كلمة المرور'],
    'email_change'    => ['Changed the email address', 'غيّر البريد الإلكتروني'],
    'favorite_add'    => ['Saved an artwork to favorites', 'حفظ عملاً في المفضلة'],
    'favorite_remove' => ['Removed an artwork from favorites', 'أزال عملاً من المفضلة'],
    'inquiry_sent'    => ['Sent an "I’m Interested" request', 'أرسل طلب «أنا مهتم»'],
    'verify_resend'   => ['Asked for a new confirmation email', 'طلب رسالة تأكيد جديدة'],
    'notify_update'   => ['Changed the email choices', 'غيّر اختيارات الرسائل'],
    'artwork_submit'  => ['Sent an artwork for review', 'أرسل عملاً للمراجعة'],
    'artwork_edit'    => ['Edited an artwork', 'عدّل عملاً'],
    'artwork_delete'  => ['Deleted an artwork', 'حذف عملاً'],
    'artwork_approve' => ['Approved an artwork', 'وافق على عمل'],
    'artwork_reject'  => ['Did not approve an artwork', 'لم يوافق على عمل'],
    'artwork_sold'    => ['Marked an artwork as sold', 'علّم عملاً كمباع'],
    'user_approve'    => ['Approved an artist', 'وافق على فنان'],
    'user_unapprove'  => ['Set an artist back to pending', 'أعاد فناناً إلى الانتظار'],
    'user_suspend'    => ['Suspended an account', 'أوقف حساباً'],
    'user_unsuspend'  => ['Lifted a suspension', 'ألغى إيقاف حساب'],
    'inquiry_status'  => ['Changed a request status', 'غيّر حالة طلب'],
    'inquiry_note'    => ['Wrote a private note on a request', 'كتب ملاحظة خاصة على طلب'],
    'institute_save'  => ['Saved an institute', 'حفظ معهداً'],
    'course_save'     => ['Saved a course', 'حفظ دورة'],
    'admin_login'     => ['Admin signed in', 'دخول الإدارة'],
    'photo_change'    => ['Changed the photo of a live artwork', 'غيّر صورة عمل معروض'],
    'photo_ok'        => ['Checked a changed photo: looks fine', 'راجع صورة متغيّرة: جيدة'],
    'change_submit'   => ['Sent changes for approval', 'أرسل تعديلات للموافقة'],
    'change_approve'  => ['Approved changes', 'وافق على تعديلات'],
    'change_reject'   => ['Rejected changes', 'رفض تعديلات'],
    'user_reject'     => ['Did not approve an artist account', 'لم يوافق على حساب فنان'],
];

/* ---------------------------------------------------------------------
 *  One status word for people and artworks (for dashboards and Bashro)
 * ------------------------------------------------------------------- */

/* person: active / pending (artist waiting for approval) / suspended */
function owUserStatusSql(string $u = 'u'): string {
    return "CASE WHEN COALESCE($u.is_banned,0) = 1 THEN 'suspended'
                 WHEN $u.role = 'artist' AND COALESCE($u.is_approved,0) = 0 THEN 'pending'
                 ELSE 'active' END";
}
/* artwork: draft / pending / approved / rejected / sold */
function owArtworkStatusSql(string $a = 'a'): string {
    $draft = owHasColumn('artworks', 'is_draft') ? "WHEN $a.is_draft = 1 THEN 'draft' " : '';
    $rej   = owHasColumn('artworks', 'rejected_at') ? "WHEN $a.is_approved = 0 AND $a.rejected_at IS NOT NULL THEN 'rejected' " : '';
    return "CASE $draft WHEN $a.status = 'sold' THEN 'sold' $rej WHEN $a.is_approved = 0 THEN 'pending' ELSE 'approved' END";
}

/* "I'm Interested" status words */
const OW_INQUIRY_STATUS = [
    'new'         => ['New', 'جديد'],
    'contacted'   => ['Contacted', 'تم التواصل'],
    'in_progress' => ['In progress', 'قيد المتابعة'],
    'closed'      => ['Closed', 'مغلق'],
];

/* ---------------------------------------------------------------------
 *  Favorites
 * ------------------------------------------------------------------- */

/* ids of the artworks this person saved */
function owFavIds(int $uid): array {
    if ($uid <= 0 || !owHasTable('favorites')) return [];
    try {
        $st = getDB()->prepare('SELECT artwork_id FROM favorites WHERE user_id = :u');
        $st->execute([':u' => $uid]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $e) { return []; }
}

/* Save or remove. Returns true when something changed. */
function owFavSet(int $uid, int $artworkId, bool $on): bool {
    if (!owHasTable('favorites')) return false;
    if ($on) {
        $st = getDB()->prepare('INSERT IGNORE INTO favorites (user_id, artwork_id) VALUES (:u, :a)');
    } else {
        $st = getDB()->prepare('DELETE FROM favorites WHERE user_id = :u AND artwork_id = :a');
    }
    $st->execute([':u' => $uid, ':a' => $artworkId]);
    return $st->rowCount() > 0;
}

/* ---------------------------------------------------------------------
 *  Small shared helpers
 * ------------------------------------------------------------------- */

/* A safe page to return to after signing in: only our own pages, never another site */
function owSafeNext(?string $next): string {
    $next = trim((string) $next);
    if ($next === '' || strlen($next) > 300 || preg_match('#[\s\\\\]|//|:#', $next)) return '';
    return preg_match('#^[a-z_]+\.php(\?[A-Za-z0-9_=&%.\-]*)?(\#[A-Za-z0-9_\-]*)?$#', $next) ? $next : '';
}

/* Password rule used at sign-up (8+ characters, a capital, a number, a symbol) */
function owPasswordStrong(string $p): bool {
    return strlen($p) >= 8 && strlen($p) <= 200 && preg_match('/[A-Z]/', $p) && preg_match('/[0-9]/', $p) && preg_match('/[^A-Za-z0-9]/', $p);
}

/* Countries offered in profile forms (any other can be typed) */
const OW_COUNTRIES = [
    'Saudi Arabia' => 'السعودية', 'United Arab Emirates' => 'الإمارات', 'Kuwait' => 'الكويت', 'Qatar' => 'قطر',
    'Bahrain' => 'البحرين', 'Oman' => 'عُمان', 'Egypt' => 'مصر', 'Jordan' => 'الأردن', 'Lebanon' => 'لبنان',
    'Morocco' => 'المغرب', 'Iraq' => 'العراق', 'United Kingdom' => 'المملكة المتحدة', 'United States' => 'الولايات المتحدة',
    'France' => 'فرنسا', 'Germany' => 'ألمانيا', 'Italy' => 'إيطاليا', 'Spain' => 'إسبانيا', 'Turkey' => 'تركيا',
    'India' => 'الهند', 'Pakistan' => 'باكستان', 'Japan' => 'اليابان', 'Canada' => 'كندا', 'Australia' => 'أستراليا',
];

/* ---------------------------------------------------------------------
 *  Sign-up helpers (register_collector.php, register_artist.php)
 * ------------------------------------------------------------------- */

/* Too many new accounts from this visitor: 5 per hour per address, 3 per browser session */
function owSignupLimited(): bool {
    $now  = time();
    $mine = array_filter((array) ($_SESSION['ow_signups'] ?? []), fn($t) => is_int($t) && $t > $now - 3600);
    return count($mine) >= 3 || owRecentCount('signup', 3600) >= 5;
}
function owSignupCounted(int $newUserId, string $role): void {
    $_SESSION['ow_signups'] = array_merge((array) ($_SESSION['ow_signups'] ?? []), [time()]);
    owLog('signup', 'user', $newUserId, $role, ['type' => owActorType($role), 'id' => $newUserId]);
}

/* The "please confirm your email" message, in English and Arabic */
function owVerifyEmailBody(string $verifyUrl, bool $artist = false): string {
    return "Welcome to Oweili!\r\n\r\nPlease confirm your email address with this link:\r\n" . $verifyUrl . "\r\n\r\n"
         . "The link works for 24 hours." . ($artist ? " After that, our team reviews your portfolio before your profile goes live." : '') . "\r\n\r\n"
         . "—\r\n\r\n"
         . "أهلاً بك في أويلي!\r\n\r\nيرجى تأكيد بريدك الإلكتروني عبر هذا الرابط:\r\n" . $verifyUrl . "\r\n\r\n"
         . "يعمل الرابط لمدة 24 ساعة." . ($artist ? " بعدها يراجع فريقنا أعمالك قبل ظهور ملفك." : '') . "\r\n";
}

/* A social or website link that is safe to show as a link: only http(s).
   "@name" or "name" becomes the full Instagram / X address.
   Returns [link, ok]: ok = false when it is not a link we can use. */
function owSafeLink(string $v, string $kind = 'web'): array {
    $v = trim($v);
    if ($v === '') return ['', true];
    $handle = ltrim($v, '@');
    if ($kind !== 'web' && preg_match('/^[A-Za-z0-9._]{1,30}$/', $handle)) {
        return [($kind === 'instagram' ? 'https://instagram.com/' : 'https://x.com/') . $handle, true];
    }
    if (!preg_match('#^https?://#i', $v) && preg_match('#^[a-z0-9][a-z0-9.\-]*\.[a-z]{2,}(/.*)?$#i', $v)) $v = 'https://' . $v;
    $ok = (bool) preg_match('#^https?://[^\s"\'<>]+$#i', $v) && filter_var($v, FILTER_VALIDATE_URL) && strlen($v) <= 255;
    return [$ok ? $v : '', $ok];
}

/* The 50 art styles an artist can pick for their profile (no old types) */
function owStyleChoices(): array {
    $all = require __DIR__ . '/art_types.php';
    return array_filter($all, fn($t) => empty($t['legacy']));
}

/* ---------------------------------------------------------------------
 *  Names: a real name has at least one letter (any language) and no "@".
 *  A name made only of numbers or symbols (like "82") is never shown and
 *  never accepted when someone types it.
 * ------------------------------------------------------------------- */
function owRealName(?string $name): string {
    $name = trim((string) $name);
    return ($name !== '' && preg_match('/\p{L}/u', $name) && strpos($name, '@') === false) ? $name : '';
}
/* '' when fine; otherwise an error code for a name a person typed */
function owNameProblem(string $name, bool $required): string {
    $name = trim($name);
    if ($name === '') return $required ? 'name' : '';
    return owRealName($name) === '' ? 'name_letters' : '';
}
