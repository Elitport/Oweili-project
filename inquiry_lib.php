<?php
/* =====================================================================
 *  inquiry_lib.php — "I'm Interested" requests from the Gallery.
 *  A visitor leaves name, phone and email on an artwork; it is saved in
 *  artwork_inquiries and emailed to the Oweili team.
 *  The table is created automatically the first time it is needed.
 *  Used by inquire.php (saving) and admin.php (Inquiries tab).
 * ===================================================================== */

const OW_INQUIRY_TO = 'contact@elitport.com';   // who receives every new inquiry

/* Creates the table once (safe to call every time). */
function owEnsureInquiryTable(): void {
    static $done = false;
    if ($done) return;
    getDB()->exec(
        "CREATE TABLE IF NOT EXISTS artwork_inquiries (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            artwork_id  INT UNSIGNED NOT NULL,
            artist_id   INT UNSIGNED NOT NULL,
            buyer_name  VARCHAR(120) NOT NULL,
            buyer_phone VARCHAR(40)  NOT NULL,
            buyer_email VARCHAR(190) NOT NULL,
            created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_inq_artwork (artwork_id),
            KEY idx_inq_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $done = true;
}

/* Checks the three fields. Returns [clean values, error code or ''] */
function owCheckInquiry(array $in): array {
    $one = fn($k, $max) => mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]/u', ' ', (string) ($in[$k] ?? ''))), 0, $max);
    $v = ['name' => $one('name', 120), 'phone' => $one('phone', 40), 'email' => strtolower($one('email', 190))];
    if ($v['name'] === '' || $v['phone'] === '' || $v['email'] === '') return [$v, 'required'];
    if (mb_strlen($v['name']) < 2)                                     return [$v, 'name'];
    if (!preg_match('/^\+?[0-9][0-9 \-()]{6,29}$/', $v['phone']))      return [$v, 'phone'];
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL))               return [$v, 'email'];
    return [$v, ''];
}

/* The artwork a visitor may ask about: approved, for sale, finished, artist not banned. */
function owInquiryArtwork(int $id): ?array {
    if ($id <= 0) return null;
    $st = getDB()->prepare(
        "SELECT a.id, a.artist_id, a.title_en, a.title_ar, a.price, " . owArtistNameSql('u') . "
         FROM artworks a JOIN users u ON u.id = a.artist_id
         WHERE a.id = :id AND a.is_approved = 1 AND a.status IN ('available','auction') AND u.is_banned = 0"
         . owNotDraftSql('a') . ' LIMIT 1'
    );
    $st->execute([':id' => $id]);
    return $st->fetch() ?: null;
}

/* Saves one inquiry and emails the team. Returns true when it was saved.
   $buyerId: the signed-in buyer's account (0 for guests), so the request
   shows in their Buyer dashboard. */
function owSaveInquiry(array $aw, array $v, int $buyerId = 0): bool {
    owEnsureInquiryTable();
    /* the same person asking about the same artwork twice in a day is saved once */
    $dup = getDB()->prepare('SELECT 1 FROM artwork_inquiries WHERE artwork_id = :a AND buyer_email = :e
                             AND created_at > (NOW() - INTERVAL 1 DAY) LIMIT 1');
    $dup->execute([':a' => (int) $aw['id'], ':e' => $v['email']]);
    if ($dup->fetchColumn()) return true;

    $withBuyer = $buyerId > 0 && function_exists('owHasColumn') && owHasColumn('artwork_inquiries', 'buyer_id');
    getDB()->prepare('INSERT INTO artwork_inquiries (artwork_id, artist_id, buyer_name, buyer_phone, buyer_email' . ($withBuyer ? ', buyer_id' : '') . ')
                      VALUES (:a, :ar, :n, :p, :e' . ($withBuyer ? ', :b' : '') . ')')
           ->execute([':a' => (int) $aw['id'], ':ar' => (int) $aw['artist_id'],
                      ':n' => $v['name'], ':p' => $v['phone'], ':e' => $v['email']] + ($withBuyer ? [':b' => $buyerId] : []));
    $newId = (int) getDB()->lastInsertId();
    if (function_exists('owLog')) {
        owLog('inquiry_sent', 'inquiry', $newId, 'artwork ' . (int) $aw['id'],
              $buyerId > 0 ? ['type' => 'buyer', 'id' => $buyerId] : ['type' => 'guest', 'id' => null]);
    }

    $host = preg_replace('/[^a-z0-9.\-]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'oweili.com')) ?: 'oweili.com';
    owNotifyArtistOfInterest($aw, $host);
    $body = "New interest in an artwork on Oweili\r\n"
          . "اهتمام جديد بعمل فني على أويلي\r\n\r\n"
          . 'Artwork / العمل: ' . $aw['title_en'] . ' · ' . $aw['title_ar'] . "\r\n"
          . 'Artist / الفنان: ' . $aw['artist_en'] . "\r\n"
          . 'Price / السعر: ' . number_format((float) $aw['price']) . " SAR\r\n"
          . 'Link: https://' . $host . '/marketplace.php#artwork-' . (int) $aw['id'] . "\r\n\r\n"
          . 'Buyer name / الاسم: ' . $v['name'] . "\r\n"
          . 'Phone / الهاتف: ' . $v['phone'] . "\r\n"
          . 'Email / البريد: ' . $v['email'] . "\r\n\r\n"
          . "All inquiries: https://$host/admin.php#inquiries\r\n";
    if (!owSendMail(OW_INQUIRY_TO, 'New inquiry: ' . $aw['title_en'], $body)) {
        error_log('inquiry: email to the team failed for artwork ' . (int) $aw['id']);   // still saved; shows in admin
    }
    return true;
}

/* All inquiries for the admin panel, newest first. */
function owListInquiries(int $limit = 500): array {
    owEnsureInquiryTable();
    $st = getDB()->prepare(
        "SELECT q.*, a.title_en, a.title_ar, " . owArtistNameSql('u') . "
         FROM artwork_inquiries q
         LEFT JOIN artworks a ON a.id = q.artwork_id
         LEFT JOIN users u ON u.id = q.artist_id
         ORDER BY q.created_at DESC, q.id DESC LIMIT :lim"
    );
    $st->bindValue(':lim', $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

/* Tells the artist that someone is interested, without any buyer details
   (Oweili is the middle party). Only when the artist kept the
   "Tell me when someone is interested" choice on (My Studio → Account). */
function owNotifyArtistOfInterest(array $aw, string $host): void {
    try {
        $hasPref = function_exists('owHasColumn') && owHasColumn('users', 'notify_inquiries');
        $st = getDB()->prepare('SELECT email' . ($hasPref ? ', notify_inquiries' : '') . ' FROM users WHERE id = :id AND role = \'artist\' LIMIT 1');
        $st->execute([':id' => (int) $aw['artist_id']]);
        $a = $st->fetch();
        if (!$a || ($hasPref && (int) $a['notify_inquiries'] !== 1) || !filter_var($a['email'], FILTER_VALIDATE_EMAIL)) return;
        $body = "Good news: someone is interested in your artwork \"" . $aw['title_en'] . "\" on Oweili.\r\n"
              . "Our team will talk to them and keep you posted. Buyer details stay with Oweili.\r\n"
              . "See your numbers: https://$host/artist_studio.php\r\n\r\n"
              . "خبر جميل: هناك من يهتم بعملك «" . $aw['title_ar'] . "» في أويلي.\r\n"
              . "سيتواصل فريقنا معه ويطلعك على المستجدات. تبقى بيانات المشتري لدى أويلي.\r\n"
              . "شاهد أرقامك: https://$host/artist_studio.php\r\n\r\n"
              . "To stop these emails: My Studio → Account. لإيقاف هذه الرسائل: مرسمي ← الحساب.\r\n";
        if (!owSendMail((string) $a['email'], 'Someone loves your work · هناك من أعجبه عملك', $body)) {
            error_log('inquiry: notice to the artist failed for artwork ' . (int) $aw['id']);
        }
    } catch (Throwable $e) { error_log('owNotifyArtistOfInterest: ' . $e->getMessage()); }
}
