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
