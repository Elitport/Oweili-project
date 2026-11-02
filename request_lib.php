<?php
/* =====================================================================
 *  request_lib.php — shared pieces for the buyer request forms
 *  (gifts.php = Oweili Gifts, commission.php = private commissions):
 *    owReqClean       one clean line / text from the form
 *    owReqLimited     at most N requests per visitor per hour
 *    owReqBuyer       name, email, phone checks (both languages)
 *    owGiftOccasions  the gift occasions with their words and drawings
 *    owReqTellBuyer   the "we received your request" email (both languages)
 *  Tables: activity_log (counts), users (the signed-in buyer)
 * ===================================================================== */
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/mail_lib.php';

function owReqClean(string $k, int $max, bool $multi = false): string {
    $v = (string) ($_POST[$k] ?? '');
    $v = $multi ? preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v) : preg_replace('/[\x00-\x1F\x7F]/u', ' ', $v);
    return mb_substr(trim((string) $v), 0, $max);
}

/* true when this visitor already sent $max of $action in the last hour (session + address code) */
function owReqLimited(string $action, int $max): bool {
    $now = time();
    $key = 'ow_req_' . $action;
    $sent = array_values(array_filter((array) ($_SESSION[$key] ?? []), fn($t) => is_int($t) && $t > $now - 3600));
    return count($sent) >= $max || owRecentCount($action, 3600) >= $max + 2;
}
function owReqCounted(string $action): void {
    $key = 'ow_req_' . $action;
    $sent = array_values(array_filter((array) ($_SESSION[$key] ?? []), fn($t) => is_int($t) && $t > time() - 3600));
    $sent[] = time();
    $_SESSION[$key] = $sent;
}

/* Checks the buyer's own details. Adds [en, ar] errors to $errors. */
function owReqBuyer(array &$errors): array {
    $b = ['name' => owReqClean('buyer_name', 150), 'email' => strtolower(owReqClean('buyer_email', 190)), 'phone' => owReqClean('buyer_phone', 30)];
    if (mb_strlen($b['name']) < 2) $errors[] = ['Please write your full name.', 'يرجى كتابة اسمك الكامل.'];
    if (!filter_var($b['email'], FILTER_VALIDATE_EMAIL)) $errors[] = ['Please write a valid email address.', 'يرجى كتابة بريد إلكتروني صحيح.'];
    if (!preg_match('/^\+?[0-9][0-9 \-()]{6,29}$/', $b['phone'])) $errors[] = ['Please write a valid phone number.', 'يرجى كتابة رقم هاتف صحيح.'];
    return $b;
}

/* The signed-in buyer (role collector), or null */
function owReqSignedBuyer(): ?array {
    if (!isLoggedIn()) return null;
    $u = currentUser();
    return ($u && ($u['role'] ?? '') === 'collector') ? $u : null;
}

function owGiftOccasions(): array {
    return [
        'wedding'    => ['en' => 'Wedding',        'ar' => 'زواج',          'icon' => '<path d="M8 14a5 5 0 1 0 0-10 5 5 0 0 0 0 10z"/><path d="M16 20a5 5 0 1 0 0-10 5 5 0 0 0 0 10z"/>'],
        'graduation' => ['en' => 'Graduation',     'ar' => 'تخرّج',          'icon' => '<path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1 2.7 3 6 3s6-2 6-3v-5"/>'],
        'baby'       => ['en' => 'New baby',       'ar' => 'مولود جديد',     'icon' => '<circle cx="12" cy="8" r="4"/><path d="M5 21c1-4 4-6 7-6s6 2 7 6"/>'],
        'eid'        => ['en' => 'Eid',            'ar' => 'العيد',          'icon' => '<path d="M15 3a8 8 0 1 0 6 12A7 7 0 0 1 15 3z"/><path d="M18 6l.6 1.4L20 8l-1.4.6L18 10l-.6-1.4L16 8l1.4-.6z"/>'],
        'ramadan'    => ['en' => 'Ramadan',        'ar' => 'رمضان',          'icon' => '<path d="M12 2v3M8 8h8l-1 9H9z"/><path d="M7 21h10"/>'],
        'thanks'     => ['en' => 'Thank you',      'ar' => 'شكر وامتنان',    'icon' => '<path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 7.9 3.6 4.5 7 4.5c2 0 3.6 1.1 5 2.9 1.4-1.8 3-2.9 5-2.9 3.4 0 5.6 3.4 4.3 6.8-1.8 4.6-9.3 9.2-9.3 9.2z"/>'],
        'teacher'    => ['en' => 'Teacher’s Day',  'ar' => 'يوم المعلم',     'icon' => '<path d="M2 4h6a4 4 0 0 1 4 4v13a3 3 0 0 0-3-3H2z"/><path d="M22 4h-6a4 4 0 0 0-4 4v13a3 3 0 0 1 3-3h7z"/>'],
    ];
}

/* The "we received your request" email to the buyer */
function owReqTellBuyer(string $email, string $subject, string $en, string $ar, string $link = ''): void {
    try {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return;
        $host = preg_replace('/[^a-z0-9.\-]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'oweili.com')) ?: 'oweili.com';
        $url = $link !== '' ? "\r\nhttps://$host/$link\r\n" : "\r\n";
        owSendMail($email, $subject, $en . $url . "\r\n" . $ar . $url);
    } catch (Throwable $e) { error_log('owReqTellBuyer: ' . $e->getMessage()); }
}
