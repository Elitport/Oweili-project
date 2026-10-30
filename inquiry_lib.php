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

/* Saves one inquiry and emails the team. Returns true when it was saved. */
function owSaveInquiry(array $aw, array $v): bool {
    owEnsureInquiryTable();
    /* the same person asking about the same artwork twice in a day is saved once */
    $dup = getDB()->prepare('SELECT 1 FROM artwork_inquiries WHERE artwork_id = :a AND buyer_email = :e
                             AND created_at > (NOW() - INTERVAL 1 DAY) LIMIT 1');
    $dup->execute([':a' => (int) $aw['id'], ':e' => $v['email']]);
    if ($dup->fetchColumn()) return true;

    getDB()->prepare('INSERT INTO artwork_inquiries (artwork_id, artist_id, buyer_name, buyer_phone, buyer_email)
                      VALUES (:a, :ar, :n, :p, :e)')
           ->execute([':a' => (int) $aw['id'], ':ar' => (int) $aw['artist_id'],
                      ':n' => $v['name'], ':p' => $v['phone'], ':e' => $v['email']]);

    $host = preg_replace('/[^a-z0-9.\-]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'oweili.com')) ?: 'oweili.com';
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
