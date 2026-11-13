<?php
/* =====================================================================
 *  alert_lib.php — email alerts to the Oweili admin (owContactEmail(),
 *  contact@oweili.com), each item with a button that opens the right page
 *  of the admin panel:
 *    artwork  a new artwork (or one sent back after changes) waits for approval
 *    change   changes to a live artwork, a profile or an article wait for approval
 *    artist   a new artist signed up (waits for approval)
 *    buyer    a new buyer signed up
 *    gift     a new Oweili Gifts request (gifts.php)
 *    commission  a new private commission request, or a buyer's answer (commission.php)
 *  Not flooded: the first alert is sent at once. Alerts that come within
 *  15 minutes of the last alert email wait and are sent together in ONE
 *  email when the 15 minutes are over (the next page anyone opens sends it,
 *  after that page has been delivered, so nobody waits).
 *  Nothing new in the database: alerts are kept in the activity log
 *  (admin_alert), each sent email too (admin_alert_mail, with the last
 *  alert it covered). A small file in .logs/ remembers when a waiting group
 *  is due, so ordinary page views cost nothing.
 *  Tables: activity_log, users (names only)
 * ===================================================================== */
require_once __DIR__ . '/ow_paths.php';
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/mail_lib.php';

const OW_ALERT_GAP = 900;   // seconds between two alert emails (15 minutes)
function owAlertDueFile(): string { return __DIR__ . '/.logs/admin_alerts_due'; }

/* Queue one alert and send now if the last alert email was 15+ minutes ago */
function owAdminAlert(string $kind, string $en, string $ar, string $link): void {
    try {
        if (!owHasTable('activity_log')) return;
        owLog('admin_alert', $kind, null, json_encode(['en' => mb_substr($en, 0, 160), 'ar' => mb_substr($ar, 0, 160), 'link' => $link], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
              ['type' => 'system', 'id' => null]);
        /* Bashro: approvals waiting (switched off until Bashro accepts the kind; no personal details) */
        if (in_array($kind, ['artwork', 'change', 'artist'], true) && is_file(__DIR__ . '/bashro_send.php')) {
            require_once __DIR__ . '/bashro_send.php';
            $title = $kind === 'artist' ? 'New artist account waiting for approval' : trim(preg_replace(['/ by .*$/u', '/\s*\([^()]*\)\s*$/u'], '', $en));
            $type = $kind === 'artist' ? 'artist_new' : ($kind === 'artwork' ? (str_starts_with($en, 'New artwork') ? 'artwork_new' : 'artwork_changed')
                  : (str_starts_with($en, 'Profile') ? 'profile_change' : (str_starts_with($en, 'Artwork') ? 'artwork_changed' : '')));   // articles have no Bashro type: not sent
            if ($type !== '') owBashroApproval($type, $title, $link, 'oweili-alert-' . (int) getDB()->lastInsertId());
        }
        /* sent after the visitor's page has been delivered, so sign-up and upload never wait for the mail server */
        static $planned = false;
        if (!$planned) { $planned = true; register_shutdown_function('owAlertsAfterRequest'); }
    } catch (Throwable $e) { error_log('owAdminAlert: ' . $e->getMessage()); }
}
function owAlertsAfterRequest(): void {
    if (function_exists('litespeed_finish_request')) litespeed_finish_request();
    elseif (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
    ignore_user_abort(true);
    @set_time_limit(60);
    owFlushAdminAlerts();
}

/* Send the waiting alerts (one email for all of them) when it is time */
function owFlushAdminAlerts(): void {
    $dir = __DIR__ . '/.logs';
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    $lock = @fopen($dir . '/admin_alerts.lock', 'c');
    if ($lock && !flock($lock, LOCK_EX | LOCK_NB)) { fclose($lock); return; }   // another request is sending
    try {
        $db = getDB();
        $last = $db->query("SELECT target_id, UNIX_TIMESTAMP(created_at) AS t FROM activity_log WHERE action = 'admin_alert_mail' ORDER BY id DESC LIMIT 1")->fetch();
        $after = (int) ($last['target_id'] ?? 0);
        $st = $db->prepare("SELECT id, target_type, details, created_at FROM activity_log WHERE action = 'admin_alert' AND id > :a ORDER BY id LIMIT 200");
        $st->execute([':a' => $after]);
        $items = $st->fetchAll();
        if (!$items) { @unlink(owAlertDueFile()); return; }
        $now = (int) $db->query('SELECT UNIX_TIMESTAMP()')->fetchColumn();
        $due = (int) ($last['t'] ?? 0) + OW_ALERT_GAP;
        if ($last && $now < $due) {                                  // too soon: hold them, send as one group later
            @file_put_contents(owAlertDueFile(), (string) (time() + ($due - $now)));
            return;
        }
        [$subject, $html] = owAlertEmail($items);
        if (owSendMail(owContactEmail(), $subject, $html, true)) {
            owLog('admin_alert_mail', 'admin', (int) end($items)['id'], count($items) . ' alert(s)', ['type' => 'system', 'id' => null]);
            @unlink(owAlertDueFile());
        } else {
            @file_put_contents(owAlertDueFile(), (string) (time() + 300));   // mail server busy: try again in 5 minutes
        }
    } catch (Throwable $e) {
        error_log('owFlushAdminAlerts: ' . $e->getMessage());
    } finally {
        if ($lock) { flock($lock, LOCK_UN); fclose($lock); }
    }
}

/* Called after any page is delivered (theme_head.php): sends a waiting group once it is due */
function owAlertsTick(): void {
    $f = owAlertDueFile();
    if (!is_file($f) || (int) @file_get_contents($f) > time()) return;
    owFlushAdminAlerts();
}

/* The email: one item, or a list of items. Each item has its own button. */
function owAlertEmail(array $items): array {
    $base = 'https://oweili.com' . owBase();   // always the real site, never the visitor's Host header
    $kinds = ['artwork' => ['Waiting for approval', 'بانتظار الموافقة'], 'change' => ['Changes waiting for approval', 'تعديلات بانتظار الموافقة'],
              'artist' => ['New artist sign-up', 'فنان جديد'], 'buyer' => ['New collector sign-up', 'مقتنٍ جديد'],
              'gift' => ['Gift request', 'طلب هدية'], 'commission' => ['Private commission', 'طلب عمل خاص'], 'order' => ['Order', 'طلب شراء'], 'auction' => ['Auctions waiting list', 'قائمة انتظار المزاد']];
    $h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    $rows = '';
    foreach ($items as $it) {
        $d = json_decode((string) $it['details'], true) ?: [];
        $k = $kinds[$it['target_type']] ?? ['Update', 'تحديث'];
        $link = $base . ltrim((string) ($d['link'] ?? 'admin.php'), '/');
        $when = date('j M Y, H:i', strtotime((string) $it['created_at'])) . ' UTC';
        $rows .= '<tr><td style="padding:16px 0;border-bottom:1px solid #E2E8F0">'
               . '<div style="font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#4A5568">' . $h($k[0]) . ' · <span dir="rtl">' . $h($k[1]) . '</span></div>'
               . '<div style="font-size:16px;font-weight:700;color:#23314A;margin:4px 0 2px">' . $h($d['en'] ?? '') . '</div>'
               . '<div dir="rtl" style="font-size:15px;color:#23314A;text-align:right;margin:0 0 4px">' . $h($d['ar'] ?? '') . '</div>'
               . '<div style="font-size:12px;color:#4A5568;margin:0 0 10px">' . $h($when) . '</div>'
               . '<a href="' . $h($link) . '" style="display:inline-block;background:#23314A;color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;padding:10px 18px;border-radius:8px">Open in the admin panel · افتح في لوحة الإدارة</a>'
               . '</td></tr>';
    }
    $n = count($items);
    if ($n === 1) {
        $d = json_decode((string) $items[0]['details'], true) ?: [];
        $subject = 'Oweili - ' . (string) ($d['en'] ?? 'New update');
        $intro = ['There is something new for you on Oweili.', 'لديك جديد في أويلي.'];
    } else {
        $subject = 'Oweili - ' . $n . ' updates waiting for you';
        $intro = [$n . ' things happened close together, so here they are in one email.', 'حدثت ' . $n . ' أمور متقاربة، فجمعناها لك في رسالة واحدة.'];
    }
    $html = '<!DOCTYPE html><html><body style="margin:0;padding:24px;background:#F4F6F8;font-family:\'DM Sans\',\'Tajawal\',Arial,sans-serif">'
          . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background:#ffffff;border:1px solid #E2E8F0;border-radius:16px">'
          . '<tr><td style="padding:24px 28px 8px"><div style="font-size:15px;font-weight:700;letter-spacing:.24em;color:#23314A"><span style="color:#DB8084">&#9679;</span> OWEILI · أويلي</div>'
          . '<p style="font-size:15px;color:#4A5568;margin:14px 0 0">' . $h($intro[0]) . '</p><p dir="rtl" style="font-size:15px;color:#4A5568;margin:4px 0 0;text-align:right">' . $h($intro[1]) . '</p></td></tr>'
          . '<tr><td style="padding:0 28px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0">' . $rows . '</table></td></tr>'
          . '<tr><td style="padding:16px 28px 24px;font-size:12px;color:#4A5568">You get these alerts as the Oweili admin. To avoid a flood, alerts that come within 15 minutes of each other arrive together in one email.'
          . '<div dir="rtl" style="text-align:right;margin-top:4px">تصلك هذه التنبيهات بصفتك مدير أويلي. التنبيهات المتقاربة خلال 15 دقيقة تصلك معاً في رسالة واحدة.</div></td></tr>'
          . '</table></body></html>';
    return [$subject, $html];
}
