<?php
/* =====================================================================
 *  commission_lib.php — private commissions (portraits and personal
 *  photos), with full confidentiality (2 Nov 2026).
 *    Steps: requested → assigned (admin picks an artist) → quoted (the
 *    artist's price) → quote_accepted (the buyer) → draft_sent (the artist)
 *    → draft_approved (the buyer; or "changes, please" back to the artist)
 *    → final_sent (the artist) → delivered (the admin). Cancel any time
 *    (admin). No payment yet: the admin handles money by hand.
 *    Files: reference photos, drafts and finals live in the PRIVATE folder
 *    .private/commissions/ (blocked for visitors in .htaccess, its own deny
 *    file, kept by deploys through .gitignore), never in uploads/. They are
 *    opened only through commission_file.php after a check: the admin, the
 *    buyer with their private link, or the assigned artist AFTER they
 *    accepted the confidentiality agreement (commission_nda).
 *    Reference photos are deleted automatically 30 days after delivery (or
 *    after a cancel), and the deletion is logged.
 *    A commission is never an artwork row, so it never shows in the
 *    Gallery, a profile or the sitemap. The buyer's written agreement to
 *    show it (may_show) is kept for the admin to decide later.
 *  Tables: commissions, commission_files, commission_nda, users, activity_log
 * ===================================================================== */
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/artwork_lib.php';
require_once __DIR__ . '/mail_lib.php';

const OW_COMM_DIR = '.private/commissions/';
const OW_COMM_MAX_REFS = 6;
const OW_COMM_KEEP_DAYS = 30;
const OW_COMM_NDA_VERSION = '1';

function owCommReady(): bool { return owHasTable('commissions') && owHasTable('commission_files') && owHasTable('commission_nda'); }

function owCommSubjects(): array {
    return ['portrait' => ['Portrait of a person', 'بورتريه لشخص'], 'family' => ['Family or couple', 'عائلة أو زوجان'], 'pet' => ['A pet', 'حيوان أليف'],
            'place' => ['A place or a memory', 'مكان أو ذكرى'], 'other' => ['Something else', 'شيء آخر']];
}
function owCommStatus(): array {
    return [
        'requested'      => ['Request received', 'وصل الطلب'],
        'assigned'       => ['Artist chosen', 'تم اختيار الفنان'],
        'quoted'         => ['Price ready for you', 'السعر جاهز لك'],
        'quote_accepted' => ['Price accepted, work started', 'تمت الموافقة على السعر وبدأ العمل'],
        'draft_sent'     => ['Draft ready for you', 'المسودة جاهزة لك'],
        'draft_approved' => ['Draft approved, finishing', 'تمت الموافقة على المسودة، قيد الإنهاء'],
        'final_sent'     => ['Finished, preparing delivery', 'اكتمل العمل، قيد التجهيز للتسليم'],
        'delivered'      => ['Delivered', 'تم التسليم'],
        'cancelled'      => ['Cancelled', 'ملغى'],
    ];
}
/* The order of the steps, for the buyer's timeline */
const OW_COMM_STEPS = ['requested', 'assigned', 'quoted', 'quote_accepted', 'draft_sent', 'draft_approved', 'final_sent', 'delivered'];

/* The confidentiality agreement the artist accepts before seeing any photo */
function owCommNdaText(): array {
    return [
        'en' => ['These photos are private. They belong to the buyer and the people in them.',
                 'I will use them only to make this one artwork, and for nothing else.',
                 'I will not save, copy, share, post or show them to anyone, and I will delete any copy once the work is delivered.',
                 'I will not show the finished artwork in public, on Oweili or anywhere else, unless Oweili tells me the buyer agreed in writing.',
                 'I will not contact the buyer outside Oweili.',
                 'If I break this, Oweili may close my account and the buyer may take legal action.'],
        'ar' => ['هذه الصور خاصة، وهي ملك للمشتري وللأشخاص الظاهرين فيها.',
                 'سأستخدمها فقط لصنع هذا العمل الفني، ولا شيء غيره.',
                 'لن أحفظها أو أنسخها أو أشاركها أو أنشرها أو أعرضها على أحد، وسأحذف أي نسخة منها بعد تسليم العمل.',
                 'لن أعرض العمل المكتمل علناً، لا في أويلي ولا في أي مكان آخر، إلا إذا أبلغني أويلي أن المشتري وافق كتابياً.',
                 'لن أتواصل مع المشتري خارج أويلي.',
                 'إن خالفت ذلك، يحق لأويلي إغلاق حسابي، ويحق للمشتري اتخاذ إجراء قانوني.'],
    ];
}

/* ---- The private folder ---- */
function owCommProtectDir(): bool {
    $dir = __DIR__ . '/' . OW_COMM_DIR;
    if (!is_dir($dir) && !@mkdir($dir, 0700, true)) return false;
    $root = dirname($dir);   // .private/
    foreach ([$root, $dir] as $d) {
        if (!is_file($d . '/.htaccess')) @file_put_contents($d . '/.htaccess', "Require all denied\nDeny from all\n");
        if (!is_file($d . '/index.html')) @file_put_contents($d . '/index.html', '');
    }
    return true;
}
/* Saves one photo into the private folder (rotation fixed, hidden data such as GPS removed) */
function owCommStore(array $file, int $commissionId, string $kind, string $by): array {
    if (!owCommProtectDir()) return ['ok' => false, 'err' => 'save'];
    $r = owStoreImage($file, OW_COMM_DIR);
    if (!$r['ok']) return $r;
    $name = basename((string) $r['path']);
    $full = __DIR__ . '/' . OW_COMM_DIR . $name;
    @chmod($full, 0600);
    $mime = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'][pathinfo($name, PATHINFO_EXTENSION)] ?? 'image/jpeg';
    getDB()->prepare('INSERT INTO commission_files (commission_id, kind, file_name, mime, bytes, uploaded_by) VALUES (:c, :k, :f, :m, :b, :u)')
           ->execute([':c' => $commissionId, ':k' => $kind, ':f' => $name, ':m' => $mime, ':b' => (int) @filesize($full), ':u' => $by]);
    return ['ok' => true, 'id' => (int) getDB()->lastInsertId()];
}
function owCommFiles(int $commissionId, ?string $kind = null): array {
    $st = getDB()->prepare('SELECT * FROM commission_files WHERE commission_id = :c AND deleted_at IS NULL' . ($kind ? ' AND kind = :k' : '') . ' ORDER BY id');
    $st->execute([':c' => $commissionId] + ($kind ? [':k' => $kind] : []));
    return $st->fetchAll();
}
function owCommDeleteFile(array $f, string $why): void {
    $name = basename((string) $f['file_name']);
    $full = __DIR__ . '/' . OW_COMM_DIR . $name;
    if ($name !== '' && is_file($full)) @unlink($full);
    getDB()->prepare('UPDATE commission_files SET deleted_at = NOW() WHERE id = :id')->execute([':id' => (int) $f['id']]);
}

/* ---- Reading one commission ---- */
function owCommById(int $id): ?array {
    $st = getDB()->prepare('SELECT * FROM commissions WHERE id = :id'); $st->execute([':id' => $id]);
    return $st->fetch() ?: null;
}
/* The buyer's private link: ref number + key (only the key's SHA-256 is stored) */
function owCommByKey(string $ref, string $key): ?array {
    if (!preg_match('/^C-\d{4}-[A-Z0-9]{4,8}$/', $ref) || !preg_match('/^[a-f0-9]{48}$/', $key)) return null;
    $st = getDB()->prepare('SELECT * FROM commissions WHERE ref_no = :r'); $st->execute([':r' => $ref]);
    $c = $st->fetch();
    return ($c && hash_equals((string) $c['access_hash'], hash('sha256', $key))) ? $c : null;
}
function owCommNewRef(): string {
    $abc = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    for ($i = 0; $i < 20; $i++) {
        $r = 'C-' . date('Y') . '-';
        for ($j = 0; $j < 6; $j++) $r .= $abc[random_int(0, strlen($abc) - 1)];
        $st = getDB()->prepare('SELECT 1 FROM commissions WHERE ref_no = :r'); $st->execute([':r' => $r]);
        if (!$st->fetchColumn()) return $r;
    }
    throw new RuntimeException('no free commission number');
}
function owCommNdaOk(int $commissionId, int $artistId): bool {
    $st = getDB()->prepare('SELECT 1 FROM commission_nda WHERE commission_id = :c AND artist_id = :a'); $st->execute([':c' => $commissionId, ':a' => $artistId]);
    return (bool) $st->fetchColumn();
}
function owCommSetStatus(int $id, string $to, array $extra = [], ?array $actor = null, string $note = ''): bool {
    if (!isset(owCommStatus()[$to])) return false;
    $set = ['status = :s']; $args = [':s' => $to, ':id' => $id];
    foreach ($extra as $k => $v) { $set[] = "$k = :x_$k"; $args[":x_$k"] = $v; }
    getDB()->prepare('UPDATE commissions SET ' . implode(', ', $set) . ' WHERE id = :id')->execute($args);
    owLog('commission_' . $to, 'commission', $id, $note, $actor);
    return true;
}

/* ---- Emails ---- */
function owCommHost(): string { return preg_replace('/[^a-z0-9.\-]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'oweili.com')) ?: 'oweili.com'; }
/* To the buyer. The private link key is only known at the request time, so later
   emails send the buyer to the same link they got first (kept only in their email). */
function owCommTellBuyer(array $c, string $subject, string $en, string $ar, string $link = ''): void {
    try {
        if (!filter_var($c['buyer_email'], FILTER_VALIDATE_EMAIL)) return;
        $tail = $link !== '' ? "\r\n$link\r\n" : "\r\n" . 'Open the private link in your first email from us to see it.' . "\r\n";
        $tailAr = $link !== '' ? "\r\n$link\r\n" : "\r\n" . 'افتح الرابط الخاص في أول رسالة أرسلناها لك لتراه.' . "\r\n";
        owSendMail((string) $c['buyer_email'], $subject . ' (' . $c['ref_no'] . ')', $en . $tail . "\r\n" . $ar . $tailAr);
    } catch (Throwable $e) { error_log('owCommTellBuyer: ' . $e->getMessage()); }
}
function owCommTellArtist(array $c, string $subject, string $en, string $ar): void {
    try {
        if (empty($c['artist_id'])) return;
        $st = getDB()->prepare('SELECT email FROM users WHERE id = :id'); $st->execute([':id' => (int) $c['artist_id']]);
        $email = (string) $st->fetchColumn();
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return;
        $url = 'https://' . owCommHost() . '/artist_commission.php?id=' . (int) $c['id'];
        owSendMail($email, $subject . ' (' . $c['ref_no'] . ')', $en . "\r\n$url\r\n\r\n" . $ar . "\r\n$url\r\n");
    } catch (Throwable $e) { error_log('owCommTellArtist: ' . $e->getMessage()); }
}
function owCommAlert(array $c, string $en, string $ar): void {
    try { require_once __DIR__ . '/alert_lib.php'; owAdminAlert('commission', $en . ' (' . $c['ref_no'] . ')', $ar . ' (' . $c['ref_no'] . ')', 'admin_commissions.php?id=' . (int) $c['id']); }
    catch (Throwable $e) { error_log('commission alert: ' . $e->getMessage()); }
}

/* ---- Automatic deletion of reference photos, 30 days after delivery or cancel ---- */
function owCommCleanup(): int {
    if (!owCommReady()) return 0;
    $n = 0;
    try {
        $rows = getDB()->query("SELECT id, ref_no FROM commissions WHERE photos_deleted_at IS NULL AND (
                                   (status = 'delivered' AND delivered_at IS NOT NULL AND delivered_at < NOW() - INTERVAL " . OW_COMM_KEEP_DAYS . " DAY)
                                OR (status = 'cancelled' AND updated_at < NOW() - INTERVAL " . OW_COMM_KEEP_DAYS . " DAY)) LIMIT 50")->fetchAll();
        foreach ($rows as $r) {
            $files = owCommFiles((int) $r['id'], 'reference');
            foreach ($files as $f) owCommDeleteFile($f, 'auto');
            getDB()->prepare('UPDATE commissions SET photos_deleted_at = NOW() WHERE id = :id')->execute([':id' => (int) $r['id']]);
            owLog('commission_photos_deleted', 'commission', (int) $r['id'], count($files) . ' reference photo(s) deleted automatically after ' . OW_COMM_KEEP_DAYS . ' days', ['type' => 'system', 'id' => null]);
            $n++;
        }
    } catch (Throwable $e) { error_log('owCommCleanup: ' . $e->getMessage()); }
    return $n;
}
/* Once a day, after any page has been delivered (theme_head.php) */
function owCommCleanupTick(): void {
    $f = __DIR__ . '/.logs/commission_cleanup_day';
    $today = gmdate('Y-m-d');
    if (is_file($f) && trim((string) @file_get_contents($f)) === $today) return;
    if (!is_dir(__DIR__ . '/.logs')) @mkdir(__DIR__ . '/.logs', 0700, true);
    @file_put_contents($f, $today);
    owCommCleanup();
}

/* A small status pill */
function owCommPill(string $status): string {
    $w = owCommStatus()[$status] ?? [$status, $status];
    $cls = in_array($status, ['quoted', 'draft_sent'], true) ? ' warn' : ($status === 'delivered' ? ' on' : '');
    return '<span class="pillx' . $cls . '">' . owBi($w[0], $w[1]) . '</span>';
}
