<?php
/* =====================================================================
 *  approval_lib.php — nothing goes public without the admin's approval.
 *  Changes an artist (or a sub-admin) makes to something visitors can
 *  already see are kept aside in pending_changes until the admin
 *  approves them. Until then visitors keep seeing the approved version.
 *    artwork  — title, description, price, type, sale status of a live artwork
 *               (a new PHOTO on a live artwork stays live and alerts the admin
 *               instead, see owPhotoChanged in admin_lib.php)
 *    profile  — an approved artist's public profile: photo, names, bio, city,
 *               country, art styles, social links
 *    article  — a sub-admin asking to publish an article or event
 *  New artworks and new artist accounts already wait (is_approved = 0).
 *  Every decision emails the artist and is written to the activity log.
 *  Until approvals_update.sql has been run, owApprovalsReady() is false and
 *  pages behave exactly as before.
 *  Tables: pending_changes, artworks, users, articles, activity_log
 * ===================================================================== */
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/mail_lib.php';

function owApprovalsReady(): bool { return owHasTable('pending_changes'); }

/* The profile fields visitors can see (changes to these wait for approval) */
const OW_PUBLIC_PROFILE_FIELDS = ['full_name_en', 'full_name_ar', 'artist_name', 'bio_en', 'bio_ar', 'city', 'country',
                                  'art_styles', 'instagram', 'twitter', 'website', 'profile_picture'];
/* The artwork fields that wait for approval (the photo does not, see above) */
const OW_ARTWORK_FIELDS = ['title_en', 'title_ar', 'description_en', 'description_ar', 'price', 'type', 'status'];

/* Field names for the admin list */
const OW_FIELD_WORDS = [
    'title_en' => ['Title (English)', 'العنوان (إنجليزي)'], 'title_ar' => ['Title (Arabic)', 'العنوان (عربي)'],
    'description_en' => ['Description (English)', 'الوصف (إنجليزي)'], 'description_ar' => ['Description (Arabic)', 'الوصف (عربي)'],
    'price' => ['Price (SAR)', 'السعر (ر.س)'], 'type' => ['Art type', 'نوع الفن'], 'status' => ['For sale', 'حالة البيع'],
    'full_name_en' => ['Name (English)', 'الاسم (إنجليزي)'], 'full_name_ar' => ['Name (Arabic)', 'الاسم (عربي)'],
    'artist_name' => ['Artist name', 'اسم الفنان'], 'bio_en' => ['Bio (English)', 'النبذة (إنجليزي)'], 'bio_ar' => ['Bio (Arabic)', 'النبذة (عربي)'],
    'city' => ['City', 'المدينة'], 'country' => ['Country', 'الدولة'], 'art_styles' => ['Art styles', 'الأساليب'],
    'instagram' => ['Instagram', 'إنستغرام'], 'twitter' => ['X', 'إكس'], 'website' => ['Website', 'الموقع'],
    'profile_picture' => ['Photo', 'الصورة'], 'publish' => ['Publish', 'النشر'],
];

/* The newest waiting change for one thing, or null */
function owPendingChange(string $type, int $targetId): ?array {
    if (!owApprovalsReady()) return null;
    $st = getDB()->prepare("SELECT * FROM pending_changes WHERE target_type = :t AND target_id = :i AND status = 'pending' ORDER BY id DESC LIMIT 1");
    $st->execute([':t' => $type, ':i' => $targetId]);
    $r = $st->fetch();
    if (!$r) return null;
    $r['data'] = json_decode((string) $r['data'], true) ?: [];
    return $r;
}

/* Keeps a change aside for approval. A newer change replaces an older waiting one. */
function owSubmitChange(int $userId, string $type, int $targetId, array $data): bool {
    if (!owApprovalsReady() || !$data) return false;
    $old = owPendingChange($type, $targetId);
    if ($old) {
        getDB()->prepare("UPDATE pending_changes SET status = 'replaced', decided_at = NOW() WHERE id = :id")->execute([':id' => (int) $old['id']]);
        $oldPic = (string) ($old['data']['profile_picture'] ?? '');
        if ($oldPic !== '' && $oldPic !== (string) ($data['profile_picture'] ?? '')) owDeletePendingFile($oldPic);
    }
    getDB()->prepare('INSERT INTO pending_changes (user_id, target_type, target_id, data) VALUES (:u, :t, :i, :d)')
           ->execute([':u' => $userId, ':t' => $type, ':i' => $targetId, ':d' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    owLog('change_submit', $type, $targetId, implode(',', array_keys($data)), ['type' => owActorType(currentUser()['role'] ?? 'artist'), 'id' => $userId]);
    return true;
}

/* A photo uploaded for a waiting profile change that was never used */
function owDeletePendingFile(string $rel): void {
    $rel = ltrim($rel, '/');
    if ($rel === '' || strpos($rel, '..') !== false || strpos($rel, 'uploads/profiles/') !== 0) return;
    $used = getDB()->prepare('SELECT COUNT(*) FROM users WHERE profile_picture = :p');
    $used->execute([':p' => $rel]);
    if ((int) $used->fetchColumn() === 0 && is_file(__DIR__ . '/' . $rel)) @unlink(__DIR__ . '/' . $rel);
}

/* Everything waiting, newest first, with who asked and what it is about */
function owWaitingChanges(): array {
    if (!owApprovalsReady()) return [];
    $rows = getDB()->query(
        "SELECT p.*, " . owArtistNameSql('u') . ", u.email
         FROM pending_changes p JOIN users u ON u.id = p.user_id
         WHERE p.status = 'pending' ORDER BY p.id DESC")->fetchAll();
    foreach ($rows as &$r) {
        $r['data'] = json_decode((string) $r['data'], true) ?: [];
        $r['current'] = [];
        if ($r['target_type'] === 'artwork') {
            $st = getDB()->prepare('SELECT * FROM artworks WHERE id = :id'); $st->execute([':id' => $r['target_id']]); $r['current'] = $st->fetch() ?: [];
        } elseif ($r['target_type'] === 'profile') {
            $st = getDB()->prepare('SELECT * FROM users WHERE id = :id'); $st->execute([':id' => $r['target_id']]); $r['current'] = $st->fetch() ?: [];
        } elseif ($r['target_type'] === 'article') {
            $st = getDB()->prepare('SELECT * FROM articles WHERE id = :id'); $st->execute([':id' => $r['target_id']]); $r['current'] = $st->fetch() ?: [];
        }
    }
    return $rows;
}

/* New artist accounts still waiting (and not turned down since they last changed) */
function owWaitingArtists(): array {
    $notRejected = owHasTable('activity_log')
        ? " AND NOT EXISTS (SELECT 1 FROM activity_log l WHERE l.action = 'user_reject' AND l.target_type = 'user' AND l.target_id = u.id)" : '';
    try {
        return getDB()->query("SELECT u.id, u.email, u.full_name_en, u.full_name_ar, u.artist_name, u.city, u.profile_picture, u.created_at, u.is_verified
                               FROM users u WHERE u.role = 'artist' AND u.is_approved = 0 AND COALESCE(u.is_banned,0) = 0$notRejected
                               ORDER BY u.created_at DESC")->fetchAll();
    } catch (Throwable $e) { return []; }
}

/* Short email to one person, in both languages */
function owTellUser(int $userId, string $subject, string $en, string $ar, string $link = ''): void {
    try {
        $st = getDB()->prepare('SELECT email FROM users WHERE id = :id'); $st->execute([':id' => $userId]);
        $email = (string) $st->fetchColumn();
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return;
        $host = preg_replace('/[^a-z0-9.:\-]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'oweili.com')) ?: 'oweili.com';
        $url = $link !== '' ? "\r\nhttps://$host/$link\r\n" : "\r\n";
        owSendMail($email, $subject, $en . $url . "\r\n" . $ar . $url);
    } catch (Throwable $e) { error_log('owTellUser: ' . $e->getMessage()); }
}

/* Approve: the waiting change goes live */
function owApproveChange(int $id, array $actor): bool {
    if (!owApprovalsReady()) return false;
    $st = getDB()->prepare("SELECT * FROM pending_changes WHERE id = :id AND status = 'pending'");
    $st->execute([':id' => $id]);
    $p = $st->fetch();
    if (!$p) return false;
    $d = json_decode((string) $p['data'], true) ?: [];
    $tid = (int) $p['target_id'];
    $db = getDB();
    $db->beginTransaction();
    try {
        if ($p['target_type'] === 'artwork') {
            $set = []; $args = [':id' => $tid];
            foreach (OW_ARTWORK_FIELDS as $f) if (array_key_exists($f, $d)) { $set[] = "$f = :$f"; $args[":$f"] = $d[$f]; }
            if (array_key_exists('status', $d)) $set[] = $d['status'] === 'sold' ? 'sold_at = COALESCE(sold_at, NOW())' : 'sold_at = NULL';
            if ($set) $db->prepare('UPDATE artworks SET ' . implode(', ', $set) . ' WHERE id = :id')->execute($args);
        } elseif ($p['target_type'] === 'profile') {
            $oldPic = '';
            if (array_key_exists('profile_picture', $d)) {
                $q = $db->prepare('SELECT profile_picture FROM users WHERE id = :id'); $q->execute([':id' => $tid]); $oldPic = (string) $q->fetchColumn();
            }
            $set = []; $args = [':id' => $tid];
            foreach (OW_PUBLIC_PROFILE_FIELDS as $f) if (array_key_exists($f, $d) && owHasColumn('users', $f)) { $set[] = "$f = :$f"; $args[":$f"] = $d[$f]; }
            if (array_key_exists('artist_name', $d) && !array_key_exists('full_name_en', $d)) { /* nothing extra */ }
            if ($set) $db->prepare('UPDATE users SET ' . implode(', ', $set) . ' WHERE id = :id')->execute($args);
            if ($oldPic !== '' && $oldPic !== (string) ($d['profile_picture'] ?? '') && strpos($oldPic, 'uploads/profiles/') === 0 && is_file(__DIR__ . '/' . $oldPic)) @unlink(__DIR__ . '/' . $oldPic);
        } elseif ($p['target_type'] === 'article') {
            /* the photo-consent rule still applies: a spotlight with photos needs an active consent */
            $a = $db->prepare('SELECT type, cover_image_path, (SELECT COUNT(*) FROM article_images i WHERE i.article_id = a.id) AS imgs,
                                      (SELECT COUNT(*) FROM photo_consents c WHERE c.article_id = a.id AND c.withdrawn_at IS NULL) AS consents
                               FROM articles a WHERE a.id = :id');
            $a->execute([':id' => $tid]);
            $art = $a->fetch();
            if (!$art) throw new RuntimeException('article gone');
            if ($art['type'] === 'artist_spotlight' && ((string) $art['cover_image_path'] !== '' || (int) $art['imgs'] > 0) && (int) $art['consents'] === 0) {
                $db->rollBack();
                $GLOBALS['OW_APPROVE_ERR'] = 'consent';
                return false;
            }
            $db->prepare("UPDATE articles SET status = 'published', published_at = COALESCE(published_at, NOW()) WHERE id = :id")->execute([':id' => $tid]);
        }
        $db->prepare("UPDATE pending_changes SET status = 'approved', decided_at = NOW(), decided_by = :by WHERE id = :id")
           ->execute([':by' => $actor['id'], ':id' => $id]);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log('owApproveChange: ' . $e->getMessage());
        return false;
    }
    owLog('change_approve', $p['target_type'], $tid, '', $actor);
    [$what, $whatAr] = owChangeWords($p['target_type']);
    owTellUser((int) $p['user_id'], 'Approved · تمت الموافقة',
        "Good news: the changes to your $what are approved and now visible on Oweili.",
        "خبر جميل: تمت الموافقة على التعديلات على {$whatAr}، وهي ظاهرة الآن في أويلي.",
        $p['target_type'] === 'profile' ? 'artist_dashboard.php?id=' . $tid : ($p['target_type'] === 'artwork' ? 'artwork.php?id=' . $tid : 'admin_articles.php'));
    return true;
}

/* Reject: the waiting change is dropped, visitors keep the approved version */
function owRejectChange(int $id, string $reason, array $actor): bool {
    $reason = mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]/u', ' ', $reason)), 0, 500);
    if ($reason === '' || !owApprovalsReady()) return false;
    $st = getDB()->prepare("SELECT * FROM pending_changes WHERE id = :id AND status = 'pending'");
    $st->execute([':id' => $id]);
    $p = $st->fetch();
    if (!$p) return false;
    $d = json_decode((string) $p['data'], true) ?: [];
    getDB()->prepare("UPDATE pending_changes SET status = 'rejected', reason = :r, decided_at = NOW(), decided_by = :by WHERE id = :id")
           ->execute([':r' => $reason, ':by' => $actor['id'], ':id' => $id]);
    if (!empty($d['profile_picture'])) owDeletePendingFile((string) $d['profile_picture']);
    owLog('change_reject', $p['target_type'], (int) $p['target_id'], $reason, $actor);
    [$what, $whatAr] = owChangeWords($p['target_type']);
    owTellUser((int) $p['user_id'], 'About your changes · بخصوص تعديلاتك',
        "The changes to your $what were not approved. The reason: $reason\r\nVisitors still see the approved version. You can make the changes again.",
        "لم تتم الموافقة على التعديلات على {$whatAr}. السبب: $reason\r\nما زال الزوار يرون النسخة المعتمدة. يمكنك إعادة التعديل.",
        $p['target_type'] === 'artwork' ? 'artist_studio.php' : 'edit_profile.php');
    return true;
}

function owChangeWords(string $type): array {
    return ['artwork' => ['artwork', 'عملك'], 'profile' => ['profile', 'ملفك'], 'article' => ['article', 'المقال']][$type] ?? ['work', 'عملك'];
}

/* Turn down a new artist account with a reason (the account stays, not approved) */
function owRejectArtistAccount(int $userId, string $reason, array $actor): bool {
    $reason = mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]/u', ' ', $reason)), 0, 500);
    if ($reason === '') return false;
    $st = getDB()->prepare("SELECT 1 FROM users WHERE id = :id AND role = 'artist' AND is_approved = 0");
    $st->execute([':id' => $userId]);
    if (!$st->fetchColumn()) return false;
    owLog('user_reject', 'user', $userId, $reason, $actor);
    owTellUser($userId, 'About your Oweili artist account · بخصوص حسابك',
        "Thank you for joining Oweili. Your artist account was not approved yet. The reason: $reason\r\nReply to this email if you have questions.",
        "شكراً لانضمامك إلى أويلي. لم تتم الموافقة على حسابك كفنان بعد. السبب: $reason\r\nرد على هذه الرسالة إن كان لديك سؤال.");
    return true;
}

/* Approve a new artist account and tell them */
function owApproveArtistAccount(int $userId, array $actor): bool {
    $st = getDB()->prepare("UPDATE users SET is_approved = 1 WHERE id = :id AND role = 'artist' AND is_approved = 0");
    $st->execute([':id' => $userId]);
    if ($st->rowCount() < 1) return false;
    owLog('user_approve', 'user', $userId, '', $actor);
    owTellUser($userId, 'Welcome to Oweili · أهلاً بك في أويلي',
        'Your artist account is approved. Your profile is now visible, and you can start your first artwork.',
        'تمت الموافقة على حسابك كفنان. ملفك ظاهر الآن، ويمكنك البدء بعملك الأول.', 'artist_studio.php');
    return true;
}

/* For the artist's own pages: the latest decision or waiting change per thing.
   Returns ['artwork:12' => ['state' => 'pending'|'rejected'|'approved', 'reason' => '…'], 'profile:5' => …] */
function owMyChanges(int $userId): array {
    if (!owApprovalsReady()) return [];
    $st = getDB()->prepare("SELECT target_type, target_id, status, reason FROM pending_changes
                            WHERE user_id = :u AND status <> 'replaced' ORDER BY id ASC");
    $st->execute([':u' => $userId]);
    $out = [];
    foreach ($st->fetchAll() as $r) $out[$r['target_type'] . ':' . (int) $r['target_id']] = ['state' => $r['status'], 'reason' => (string) $r['reason']];
    return $out;
}
