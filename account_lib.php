<?php
/* =====================================================================
 *  account_lib.php — account changes a signed-in person makes for
 *  themselves: new password, new email, confirmation email again.
 *  Used by the Buyer dashboard now, the Artist dashboard next.
 *  Every function returns '' when it worked, or a short error code.
 *  Tables: users, sessions, activity_log
 * ===================================================================== */
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/mail_lib.php';

/* Words for the error codes, in English and Arabic */
const OW_ACCOUNT_ERR = [
    'csrf'      => ['The page was open too long. Please try again.', 'انتهت صلاحية الصفحة. حاول مرة أخرى.'],
    'current'   => ['Your current password is not right.', 'كلمة المرور الحالية غير صحيحة.'],
    'weak'      => ['Please use 8 or more characters with a capital letter, a number and a symbol.', 'استخدم 8 أحرف أو أكثر، مع حرف كبير ورقم ورمز.'],
    'mismatch'  => ['The two new passwords do not match.', 'كلمتا المرور الجديدتان غير متطابقتين.'],
    'same'      => ['The new password is the same as the old one.', 'كلمة المرور الجديدة مثل القديمة.'],
    'email'     => ['Please enter a valid email address.', 'يرجى إدخال بريد إلكتروني صحيح.'],
    'email_same'=> ['This is already your email address.', 'هذا هو بريدك الحالي بالفعل.'],
    'exists'    => ['This email belongs to another account.', 'هذا البريد مستخدم في حساب آخر.'],
    'rate'      => ['Please wait a little before trying again.', 'يرجى الانتظار قليلاً قبل المحاولة مجدداً.'],
    'name'      => ['Please enter your name.', 'يرجى كتابة اسمك.'],
    'phone'     => ['Please enter a valid phone number.', 'يرجى إدخال رقم هاتف صحيح.'],
    'server'    => ['Something went wrong on our side. Please try again.', 'حدث خطأ من جهتنا. حاول مرة أخرى.'],
];

/* The person's current password is right (modern hashes; old saves are
   upgraded at sign-in, so a signed-in person always has a modern one) */
function owCurrentPasswordOk(array $user, string $pass): bool {
    return $pass !== '' && verifyPassword($pass, trim((string) ($user['password'] ?? '')));
}

/* Too many tries of $what in this session (5 per 15 minutes) */
function owAccountTooMany(string $what): bool {
    $now = time();
    $k = 'ow_acc_' . $what;
    $_SESSION[$k] = array_values(array_filter((array) ($_SESSION[$k] ?? []), fn($t) => is_int($t) && $t > $now - 900));
    if (count($_SESSION[$k]) >= 5) return true;
    $_SESSION[$k][] = $now;
    return false;
}

function owChangePassword(array $user, string $current, string $new, string $confirm): string {
    if (owAccountTooMany('pass'))                return 'rate';
    if (!owCurrentPasswordOk($user, $current))   return 'current';
    if (!owPasswordStrong($new))                 return 'weak';
    if (!hash_equals($new, $confirm))            return 'mismatch';
    if (hash_equals($current, $new))             return 'same';
    $uid = (int) $user['id'];
    getDB()->prepare('UPDATE users SET password = :p, reset_token = NULL, reset_expires = NULL WHERE id = :id')
           ->execute([':p' => hashPassword($new), ':id' => $uid]);
    /* sign out every other phone and computer; this one stays signed in */
    getDB()->prepare('DELETE FROM sessions WHERE user_id = :u AND session_token <> :t')
           ->execute([':u' => $uid, ':t' => (string) ($_SESSION['session_token'] ?? '')]);
    owLog('password_change', 'user', $uid, '', ['type' => owActorType($user['role'] ?? ''), 'id' => $uid]);
    owSendMail((string) $user['email'], 'Your Oweili password was changed · تم تغيير كلمة المرور',
        "Your Oweili password was just changed. If this was not you, reset it now: https://" . owHost() . "/forgot.php\r\n\r\n"
      . "تم تغيير كلمة مرورك في أويلي الآن. إن لم تكن أنت، أعد تعيينها فوراً: https://" . owHost() . "/forgot.php\r\n");
    return '';
}

/* New email: saved straight away, then confirmed through a link sent to it.
   The old address gets a short notice, so a stolen session cannot quietly
   take the account. */
function owChangeEmail(array $user, string $newEmail, string $current): string {
    $newEmail = strtolower(trim($newEmail));
    if (owAccountTooMany('email'))                          return 'rate';
    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL) || strlen($newEmail) > 190) return 'email';
    if ($newEmail === strtolower(trim((string) $user['email']))) return 'email_same';
    if (!owCurrentPasswordOk($user, $current))              return 'current';
    $st = getDB()->prepare('SELECT 1 FROM users WHERE LOWER(TRIM(email)) = :e AND id <> :id LIMIT 1');
    $st->execute([':e' => $newEmail, ':id' => (int) $user['id']]);
    if ($st->fetchColumn())                                 return 'exists';

    $uid   = (int) $user['id'];
    $old   = (string) $user['email'];
    $token = bin2hex(random_bytes(32));
    getDB()->prepare('UPDATE users SET email = :e, is_verified = 0, verification_token = :t,
                      verification_expires = (NOW() + INTERVAL 1 DAY) WHERE id = :id')
           ->execute([':e' => $newEmail, ':t' => $token, ':id' => $uid]);
    owLog('email_change', 'user', $uid, '', ['type' => owActorType($user['role'] ?? ''), 'id' => $uid]);
    owSendMail($newEmail, 'Confirm your email · تأكيد بريدك الإلكتروني', owVerifyEmailBody('https://' . owHost() . '/verify.php?token=' . $token));
    owSendMail($old, 'Your Oweili email was changed · تم تغيير بريدك',
        "The email address of your Oweili account was just changed. If this was not you, write to us at contact@elitport.com.\r\n\r\n"
      . "تم تغيير البريد الإلكتروني لحسابك في أويلي الآن. إن لم تكن أنت، راسلنا على contact@elitport.com.\r\n");
    return '';
}

/* The confirmation email again (3 per hour) */
function owResendVerify(array $user): string {
    if ((int) ($user['is_verified'] ?? 0) === 1) return '';
    $now = time();
    $mine = array_filter((array) ($_SESSION['ow_resend'] ?? []), fn($t) => is_int($t) && $t > $now - 3600);
    if (count($mine) >= 3 || owRecentCount('verify_resend', 3600) >= 6) return 'rate';
    $_SESSION['ow_resend'] = array_merge($mine, [$now]);
    $uid   = (int) $user['id'];
    $token = bin2hex(random_bytes(32));
    getDB()->prepare('UPDATE users SET verification_token = :t, verification_expires = (NOW() + INTERVAL 1 DAY) WHERE id = :id')
           ->execute([':t' => $token, ':id' => $uid]);
    owLog('verify_resend', 'user', $uid, '', ['type' => owActorType($user['role'] ?? ''), 'id' => $uid]);
    return owSendMail((string) $user['email'], 'Confirm your email · تأكيد بريدك الإلكتروني',
        owVerifyEmailBody('https://' . owHost() . '/verify.php?token=' . $token)) ? '' : 'server';
}

/* Our own web address for links in emails */
function owHost(): string {
    return preg_replace('/[^a-z0-9.\-]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'oweili.com')) ?: 'oweili.com';
}
