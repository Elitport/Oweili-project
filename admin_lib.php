<?php
/* =====================================================================
 *  admin_lib.php — staff actions shared by admin.php and
 *  subadmin_dashboard.php, and the lists used by the admin panel.
 *    - owApproveArtwork / owRejectArtwork (with the reason, the artwork is
 *      kept so the artist can fix it) / owSetArtworkSale
 *    - owSetInquiry: status + private note
 *    - owAdminOverview: the numbers on top of the admin panel
 *  Every action is written to the activity log, and the artist gets a
 *  short bilingual email when a work is approved or not approved.
 *  Tables: artworks, users, artwork_inquiries, activity_log
 * ===================================================================== */
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/mail_lib.php';

/* The staff member doing it: an admin signed in on admin.php, or a sub-admin */
function owStaffActor(): array {
    if (!empty($_SESSION['user_id']) && ($u = currentUser()) && in_array($u['role'] ?? '', ['admin', 'sub_admin'], true)) {
        return ['type' => owActorType($u['role']), 'id' => (int) $u['id']];
    }
    return ['type' => 'admin', 'id' => null];
}

/* Short note to the artist about one of their works */
function owTellArtist(int $artworkId, string $subject, string $en, string $ar): void {
    try {
        $st = getDB()->prepare('SELECT u.email, a.title_en, a.title_ar FROM artworks a JOIN users u ON u.id = a.artist_id WHERE a.id = :id');
        $st->execute([':id' => $artworkId]);
        $r = $st->fetch();
        if (!$r || !filter_var($r['email'], FILTER_VALIDATE_EMAIL)) return;
        $host = preg_replace('/[^a-z0-9.\-]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'oweili.com')) ?: 'oweili.com';
        $body = str_replace('{t}', (string) $r['title_en'], $en) . "\r\nhttps://$host/artist_studio.php\r\n\r\n"
              . str_replace('{t}', (string) ($r['title_ar'] ?: $r['title_en']), $ar) . "\r\n";
        owSendMail((string) $r['email'], $subject, $body);
    } catch (Throwable $e) { error_log('owTellArtist: ' . $e->getMessage()); }
}

function owApproveArtwork(int $id): bool {
    $rej = owHasColumn('artworks', 'rejected_at') ? ', rejected_at = NULL, rejected_reason = NULL' : '';
    $st = getDB()->prepare("UPDATE artworks SET is_approved = 1$rej WHERE id = :id" . owNotDraftSql());
    $st->execute([':id' => $id]);
    if ($st->rowCount() < 1) return false;
    owLog('artwork_approve', 'artwork', $id, '', owStaffActor());
    owTellArtist($id, 'Your artwork is in the Gallery · عملك في المعرض',
        'Lovely news: "{t}" is now in the Oweili Gallery for everyone to see.',
        'خبر جميل: عملك «{t}» صار في معرض أويلي ليراه الجميع.');
    return true;
}

/* Not approved: the artwork stays (hidden from the Gallery) with the reason,
   so the artist can fix it and send it again. */
function owRejectArtwork(int $id, string $reason): bool {
    $reason = mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]/u', ' ', $reason)), 0, 500);
    if ($reason === '') return false;
    if (!owHasColumn('artworks', 'rejected_at')) return false;
    $st = getDB()->prepare('UPDATE artworks SET is_approved = 0, rejected_at = NOW(), rejected_reason = :r WHERE id = :id' . owNotDraftSql());
    $st->execute([':r' => $reason, ':id' => $id]);
    if ($st->rowCount() < 1) return false;
    owLog('artwork_reject', 'artwork', $id, $reason, owStaffActor());
    owTellArtist($id, 'About your artwork · بخصوص عملك',
        '"{t}" was not approved yet. The reason: ' . $reason . "\r\nYou can edit it in My Studio and it goes back to review.",
        'لم تتم الموافقة على «{t}» بعد. السبب: ' . $reason . "\r\nيمكنك تعديله من مرسمي ليعود إلى المراجعة.");
    return true;
}

/* Mark as sold (or back to available) */
function owSetArtworkSale(int $id, bool $sold): bool {
    $st = getDB()->prepare($sold
        ? "UPDATE artworks SET status = 'sold', sold_at = COALESCE(sold_at, NOW()) WHERE id = :id"
        : "UPDATE artworks SET status = 'available', sold_at = NULL WHERE id = :id AND status = 'sold'");
    $st->execute([':id' => $id]);
    if ($st->rowCount() < 1) return false;
    owLog($sold ? 'artwork_sold' : 'artwork_edit', 'artwork', $id, $sold ? '' : 'back to available', owStaffActor());
    return true;
}

/* Request status and private note (admin only) */
function owSetInquiry(int $id, string $status, ?string $note): bool {
    if (!owHasColumn('artwork_inquiries', 'status') || !isset(OW_INQUIRY_STATUS[$status])) return false;
    $old = getDB()->prepare('SELECT status, admin_notes FROM artwork_inquiries WHERE id = :id');
    $old->execute([':id' => $id]);
    $was = $old->fetch();
    if (!$was) return false;
    $note = $note === null ? (string) $was['admin_notes'] : mb_substr(trim($note), 0, 4000);
    getDB()->prepare('UPDATE artwork_inquiries SET status = :s, admin_notes = :n WHERE id = :id')
           ->execute([':s' => $status, ':n' => $note !== '' ? $note : null, ':id' => $id]);
    if ($was['status'] !== $status) owLog('inquiry_status', 'inquiry', $id, $was['status'] . ' -> ' . $status, owStaffActor());
    if ((string) $was['admin_notes'] !== $note) owLog('inquiry_note', 'inquiry', $id, '', owStaffActor());
    return true;
}

/* The numbers on top of the admin panel */
function owAdminOverview(): array {
    $one = function (string $sql): int { try { return (int) getDB()->query($sql)->fetchColumn(); } catch (Throwable $e) { return 0; } };
    $draft = owHasColumn('artworks', 'is_draft') ? ' AND is_draft = 0' : '';
    return [
        'artists'    => $one("SELECT COUNT(*) FROM users WHERE role = 'artist'"),
        'buyers'     => $one("SELECT COUNT(*) FROM users WHERE role = 'collector'"),
        'artworks'   => $one("SELECT COUNT(*) FROM artworks WHERE 1$draft"),
        'in_gallery' => $one("SELECT COUNT(*) FROM artworks WHERE is_approved = 1 AND status IN ('available','auction')$draft"),
        'inquiries'  => $one('SELECT COUNT(*) FROM artwork_inquiries'),
        'new_inq'    => owHasColumn('artwork_inquiries', 'status') ? $one("SELECT COUNT(*) FROM artwork_inquiries WHERE status = 'new'") : 0,
        'week'       => $one('SELECT COUNT(*) FROM users WHERE created_at >= (NOW() - INTERVAL 7 DAY)'),
    ];
}

/* ---------------------------------------------------------------------
 *  "Photo changed, needs a look"
 *  When an artist swaps the photo of an artwork that is live in the
 *  Gallery, it stays live, the old photo is kept for comparison, the
 *  team gets an email with both photos, and the admin panel lists it
 *  until the admin presses "Looks fine" or hides the artwork (with a
 *  reason the artist is told). The list lives in the activity log
 *  (action photo_change, closed by photo_ok or artwork_reject), so no
 *  database change was needed.
 * ------------------------------------------------------------------- */

/* Called by edit_artwork.php right after a live artwork's photo was replaced */
function owPhotoChanged(array $aw, string $oldPath, string $newPath, int $artistId): void {
    owLog('photo_change', 'artwork', (int) $aw['id'], json_encode(['old' => $oldPath, 'new' => $newPath], JSON_UNESCAPED_SLASHES),
          ['type' => 'artist', 'id' => $artistId]);
    $host = preg_replace('/[^a-z0-9.\-]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'oweili.com')) ?: 'oweili.com';
    $h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    $img = function (string $p) use ($host, $h): string {
        $src = owImgSrc($p);
        if ($src === '') return '<p>(no photo)</p>';
        $url = preg_match('#^https?://#', $src) ? $src : 'https://' . $host . '/' . ltrim($src, '/');
        return '<a href="' . $h($url) . '"><img src="' . $h($url) . '" alt="" width="260" style="max-width:260px;border:1px solid #E2E8F0;border-radius:8px"></a>';
    };
    $name = '';
    try { $st = getDB()->prepare('SELECT ' . owArtistNameSql('u') . ' FROM users u WHERE u.id = :id'); $st->execute([':id' => $artistId]); $name = (string) ($st->fetch()['artist_en'] ?? ''); } catch (Throwable $e) {}
    $body = '<div style="font-family:Arial,sans-serif;color:#23314A;font-size:15px;line-height:1.6">'
          . '<p><b>Photo changed on a live artwork · تغيّرت صورة عمل معروض</b></p>'
          . '<p>' . $h($aw['title_en']) . ' · ' . $h($aw['title_ar']) . '<br>Artist / الفنان: ' . $h($name) . '</p>'
          . '<p>The artwork stays in the Gallery. If the new photo is not acceptable, hide it from the admin panel; the artist is told why.<br>'
          . 'يبقى العمل في المعرض. إن لم تكن الصورة الجديدة مقبولة، أخفِه من لوحة الإدارة وسيُبلَّغ الفنان بالسبب.</p>'
          . '<table cellpadding="6"><tr><td><b>Before · قبل</b><br>' . $img($oldPath) . '</td><td><b>Now · الآن</b><br>' . $img($newPath) . '</td></tr></table>'
          . '<p><a href="https://' . $host . '/admin.php#photos">Open the admin panel · افتح لوحة الإدارة</a></p></div>';
    if (!owSendMail(OW_INQUIRY_TO_ADMIN, 'Photo changed: ' . $aw['title_en'] . ' · تغيّرت صورة عمل', $body, true)) {
        error_log('photo change: email to the team failed for artwork ' . (int) $aw['id']);   // still listed in admin
    }
}
const OW_INQUIRY_TO_ADMIN = 'contact@elitport.com';

/* Live photo changes still waiting for a look (newest change per artwork) */
function owPhotoChangesPending(): array {
    if (!owHasTable('activity_log')) return [];
    try {
        return getDB()->query(
            "SELECT l.id AS log_id, l.target_id AS artwork_id, l.details, l.created_at, a.title_en, a.title_ar, a.image_url, a.artist_id,
                    " . owArtistNameSql('u') . ", " . owArtworkStatusSql('a') . " AS state
             FROM activity_log l
             JOIN artworks a ON a.id = l.target_id
             JOIN users u ON u.id = a.artist_id
             WHERE l.action = 'photo_change' AND l.target_type = 'artwork'
               AND NOT EXISTS (SELECT 1 FROM activity_log r WHERE r.target_type = 'artwork' AND r.target_id = l.target_id
                               AND r.id > l.id AND r.action IN ('photo_change', 'photo_ok', 'artwork_reject', 'artwork_delete'))
             ORDER BY l.id DESC")->fetchAll();
    } catch (Throwable $e) { error_log('owPhotoChangesPending: ' . $e->getMessage()); return []; }
}

/* "Looks fine": closes it, and the kept old photo is deleted (no longer needed) */
function owPhotoOk(int $artworkId): bool {
    foreach (owPhotoChangesPending() as $p) {
        if ((int) $p['artwork_id'] !== $artworkId) continue;
        $d = json_decode((string) $p['details'], true) ?: [];
        owLog('photo_ok', 'artwork', $artworkId, '', owStaffActor());
        $old = ltrim((string) ($d['old'] ?? ''), '/');
        if ($old !== '' && strpos($old, 'uploads/artworks/') === 0 && strpos($old, '..') === false) {
            $still = getDB()->prepare('SELECT COUNT(*) FROM artworks WHERE image_url = :p OR image_url = :p2');
            $still->execute([':p' => $old, ':p2' => '/' . $old]);
            if ((int) $still->fetchColumn() === 0 && is_file(__DIR__ . '/' . $old)) @unlink(__DIR__ . '/' . $old);
        }
        return true;
    }
    return false;
}
