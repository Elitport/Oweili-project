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
require_once __DIR__ . '/cert_lib.php';      // the Certificate of Authenticity is made on approval

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
    $certNo = '';
    try { $certNo = owIssueCertificate($id, owStaffActor()['id']); } catch (Throwable $e) { error_log('owIssueCertificate: ' . $e->getMessage()); }
    owTellArtist($id, 'Your artwork is in the Gallery · عملك في المعرض',
        'Lovely news: "{t}" is now in the Oweili Gallery for everyone to see.'
          . ($certNo !== '' ? ' Its Certificate of Authenticity (' . $certNo . ') is ready in My Studio.' : ''),
        'خبر جميل: عملك «{t}» صار في معرض أويلي ليراه الجميع.'
          . ($certNo !== '' ? ' شهادة الأصالة الخاصة به (' . $certNo . ') جاهزة في مرسمي.' : ''));
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
    if (!owSendMail(OW_INQUIRY_TO_ADMIN, 'Oweili - Photo changed: ' . $aw['title_en'], $body, true)) {
        error_log('photo change: email to the team failed for artwork ' . (int) $aw['id']);   // still listed in admin
    }
}
define('OW_INQUIRY_TO_ADMIN', owContactEmail());   // Oweili's admin address (one setting: ow_paths.php)

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
        try { owRefreshCertificate($artworkId); } catch (Throwable $e) { error_log('owRefreshCertificate: ' . $e->getMessage()); }   // the certificate shows the new photo
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

/* ---------------------------------------------------------------------
 *  Admin delete (owner, 8 Nov 2026): remove a test or spam account, or
 *  one "I'm Interested" request. Admin only (admin.php checks), with an
 *  "Are you sure?" step on the page. Certificates are never deleted:
 *  the account's artworks are removed and their certificates cancelled.
 *  An account that is part of a real record (gift, commission, order,
 *  payout, purchase, article, consent, institute, course) is refused:
 *  suspend it instead, so no record is broken.
 * ------------------------------------------------------------------- */
function owAdminDeleteBlockers(int $uid): array {
    $checks = [
        ['gift_requests', 'user_id', 'gift requests', 'طلبات هدايا'],
        ['commissions', 'user_id', 'commissions (as buyer)', 'أعمال خاصة (كمشترٍ)'],
        ['commissions', 'artist_id', 'commissions (as artist)', 'أعمال خاصة (كفنان)'],
        ['commission_nda', 'artist_id', 'confidentiality agreements', 'اتفاقيات سرية'],
        ['orders', 'buyer_user_id', 'orders (as buyer)', 'طلبات شراء (كمشترٍ)'],
        ['orders', 'artist_id', 'orders (as artist)', 'طلبات شراء (كفنان)'],
        ['payouts', 'artist_id', 'payouts', 'مدفوعات'],
        ['purchases', 'collector_id', 'purchases', 'مشتريات'],
        ['articles', 'author_user_id', 'articles', 'مقالات'],
        ['photo_consents', 'recorded_by', 'photo consents', 'موافقات صور'],
        ['institutes', 'user_id', 'institutes', 'معاهد'],
        ['course_registrations', 'user_id', 'course registrations', 'تسجيلات دورات'],
    ];
    $out = [];
    foreach ($checks as [$t, $c, $en, $ar]) {
        try {
            if (!owHasTable($t) || !owHasColumn($t, $c)) continue;
            $st = getDB()->prepare("SELECT COUNT(*) FROM `$t` WHERE `$c` = :id");
            $st->execute([':id' => $uid]);
            if ((int) $st->fetchColumn() > 0) $out[] = [$en, $ar];
        } catch (Throwable $e) { $out[] = [$en, $ar]; }   // when in doubt, keep the account
    }
    return $out;
}

/* Returns '' when deleted, or the reason it was not (an error code) */
function owAdminDeleteUser(int $uid): string {
    require_once __DIR__ . '/artwork_lib.php';
    $st = getDB()->prepare('SELECT id, role, profile_picture FROM users WHERE id = :id LIMIT 1');
    $st->execute([':id' => $uid]);
    $u = $st->fetch();
    if (!$u) return 'not_found';
    if (!in_array((string) $u['role'], ['artist', 'collector'], true)) return 'staff';   // staff accounts are never deleted here
    if (owAdminDeleteBlockers($uid)) return 'linked';

    /* the account's artworks: the same tidy-up as the artist's own Delete button; certificates are cancelled, never deleted */
    $aw = getDB()->prepare('SELECT id, image_url FROM artworks WHERE artist_id = :id');
    $aw->execute([':id' => $uid]);
    foreach ($aw->fetchAll() as $a) {
        $aid = (int) $a['id'];
        $stageFiles = array_map(fn($s) => (string) $s['photo_path'], owGetStages($aid));
        if (function_exists('owCancelCertificatesOfArtwork')) owCancelCertificatesOfArtwork($aid, 'Account deleted by the Oweili team');
        getDB()->prepare('DELETE FROM artworks WHERE id = :id')->execute([':id' => $aid]);
        foreach ($stageFiles as $sf) owDeleteStageFile($sf);
        foreach (['DELETE FROM artwork_stages WHERE artwork_id = :id', 'DELETE FROM artwork_colors WHERE artwork_id = :id', 'DELETE FROM favorites WHERE artwork_id = :id',
                  "UPDATE pending_changes SET status = 'replaced', decided_at = NOW() WHERE target_type = 'artwork' AND target_id = :id AND status = 'pending'"] as $q) {
            try { getDB()->prepare($q)->execute([':id' => $aid]); } catch (Throwable $e) {}
        }
        $img = (string) $a['image_url'];
        if (strpos($img, 'uploads/artworks/') === 0 && is_file(__DIR__ . '/' . $img)) @unlink(__DIR__ . '/' . $img);
        owLog('artwork_delete', 'artwork', $aid, 'account deleted by admin', ['type' => 'admin', 'id' => null]);
    }
    /* the account's own pieces */
    try {
        if (owHasTable('portfolio_images')) {
            $pi = getDB()->prepare('SELECT image_url FROM portfolio_images WHERE artist_id = :id');
            $pi->execute([':id' => $uid]);
            foreach ($pi->fetchAll(PDO::FETCH_COLUMN) as $f) { $f = (string) $f; if (strpos($f, 'uploads/portfolio/') === 0 && is_file(__DIR__ . '/' . $f)) @unlink(__DIR__ . '/' . $f); }
            getDB()->prepare('DELETE FROM portfolio_images WHERE artist_id = :id')->execute([':id' => $uid]);
        }
    } catch (Throwable $e) {}
    foreach (['DELETE FROM favorites WHERE user_id = :id', 'DELETE FROM sessions WHERE user_id = :id',
              "UPDATE pending_changes SET status = 'replaced', decided_at = NOW() WHERE user_id = :id AND status = 'pending'",
              'UPDATE artwork_inquiries SET buyer_id = NULL WHERE buyer_id = :id'] as $q) {
        try { getDB()->prepare($q)->execute([':id' => $uid]); } catch (Throwable $e) {}
    }
    $pic = (string) ($u['profile_picture'] ?? '');
    getDB()->prepare('DELETE FROM users WHERE id = :id AND role IN (\'artist\', \'collector\')')->execute([':id' => $uid]);
    if (strpos($pic, 'uploads/') === 0 && strpos($pic, 'uploads/consents/') !== 0 && is_file(__DIR__ . '/' . $pic)) @unlink(__DIR__ . '/' . $pic);
    owLog('user_delete', 'user', $uid, (string) $u['role'], ['type' => 'admin', 'id' => null]);
    return '';
}

/* One "I'm Interested" request */
function owAdminDeleteInquiry(int $id): bool {
    $st = getDB()->prepare('DELETE FROM artwork_inquiries WHERE id = :id');
    $st->execute([':id' => $id]);
    if ($st->rowCount() < 1) return false;
    owLog('inquiry_delete', 'inquiry', $id, '', ['type' => 'admin', 'id' => null]);
    return true;
}
