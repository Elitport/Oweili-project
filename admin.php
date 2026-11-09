<?php
require_once 'config.php';
require_once __DIR__ . '/artwork_lib.php';
require_once __DIR__ . '/mail_lib.php';
require_once __DIR__ . '/inquiry_lib.php';
require_once __DIR__ . '/admin_lib.php';
require_once __DIR__ . '/approval_lib.php';
require_once __DIR__ . '/cert_lib.php';

/* =====================================================================
 *  Oweili · admin.php — approve / ban artists
 *  Simple password gate (no user account needed). The password is stored
 *  as a BCRYPT hash in config.php (on the server only, never on GitHub):
 *     define('ADMIN_HASH', '$2y$12$...');
 *  To change it, create a new hash with
 *     php -r "echo password_hash('NEW_PASS', PASSWORD_BCRYPT, ['cost'=>12]);"
 *  and replace the value in config.php. Changing it signs out every admin.
 * ===================================================================== */
$adminHash = defined('ADMIN_HASH') ? (string) ADMIN_HASH : '';
$adminSig  = $adminHash !== '' ? hash('sha256', $adminHash) : '';

$loginError = '';

/* ---- Handle login ---- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['admin_pass'])) {
    if ($adminHash === '') {
        $loginError = 'Admin password is not set up yet. Add ADMIN_HASH to config.php.';
    } elseif (owAdminGateBlocked()) {
        /* 5 wrong passwords in 10 minutes from one real address (visitor_lib.php) */
        $loginError = 'Too many attempts. Please wait 10 minutes.';
    } elseif (verifyCsrf($_POST['csrf_token'] ?? null) && password_verify((string) $_POST['admin_pass'], $adminHash)) {
        owAdminGateRecord(true);
        session_regenerate_id(true);
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_sig'] = $adminSig;   // ties the login to the current password
        owLog('admin_login', null, null, '', ['type' => 'admin', 'id' => null]);
    } else {
        if (verifyCsrf($_POST['csrf_token'] ?? null)) owAdminGateRecord(false);
        $loginError = 'Incorrect password.';
    }
}

/* ---- Handle logout ---- */
if (isset($_GET['logout'])) {
    unset($_SESSION['is_admin'], $_SESSION['admin_sig']);
    header('Location: admin.php');
    exit;
}

/* Logged in only if the login was made with the current password —
   after the password changes, older admin sessions are signed out. */
$isAdmin = !empty($_SESSION['is_admin']) && $adminSig !== '' && hash_equals($adminSig, (string) ($_SESSION['admin_sig'] ?? ''));
/* this browser is the owner's: never count it in the visitor statistics (geo_lib.php) */
if ($isAdmin && empty($_COOKIE['ow_nostat']) && !headers_sent()) setcookie('ow_nostat', '1', ['expires' => time() + 5 * 365 * 86400, 'path' => '/', 'secure' => !empty($_SERVER['HTTPS']), 'httponly' => false, 'samesite' => 'Lax']);

/* ---- Auctions waiting list as a CSV file (opens in Excel; admin only) ---- */
if ($isAdmin && isset($_GET['auctions_csv'])) {
    require_once __DIR__ . '/auction_lib.php';
    owLog('auction_export', 'auction_waitlist', null, owAuctionCount() . ' rows', ['type' => 'admin', 'id' => null]);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="oweili-auctions-waiting-list-' . gmdate('Y-m-d') . '.csv"');
    header('Cache-Control: private, no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");   // so Excel reads Arabic names correctly
    $cell = fn($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : (string) $v;   // never a formula
    fputcsv($out, ['Signed up (UTC)', 'Email', 'Name', 'Language', 'Account']);
    foreach (owAuctionList() as $r) fputcsv($out, [$r['created_at'], $cell($r['email']), $cell($r['name']), $r['lang'], $r['user_id'] ? '#' . $r['user_id'] : '']);
    fclose($out);
    exit;
}
/* ---- Photo check: can the server make the phone-sized photo copies? ---- */
if ($isAdmin && isset($_GET['photo_check'])) {
    define('OW_ADMIN_OK', true);
    require __DIR__ . '/photo_check.php';
    exit;
}
/* ---- Error log: the private PHP error log (visitors never see errors) ---- */
if ($isAdmin && isset($_GET['error_log'])) {
    define('OW_ADMIN_OK', true);
    require __DIR__ . '/error_log_view.php';
    exit;
}
/* ---- One person (artist or buyer): profile, works, requests, favorites, activity ---- */
if ($isAdmin && isset($_GET['person'])) {
    define('OW_ADMIN_OK', true);
    require __DIR__ . '/admin_person.php';
    exit;
}
/* ---- Settings: what is switched on, checks, the Bashro connection ---- */
if ($isAdmin && isset($_GET['settings'])) {
    define('OW_ADMIN_OK', true);
    require __DIR__ . '/admin_settings.php';
    exit;
}
/* ---- Email check: can the site send reset / welcome emails? ---- */
if ($isAdmin && isset($_GET['mail_check'])) {
    define('OW_ADMIN_OK', true);
    require __DIR__ . '/mail_check.php';
    exit;
}

/* ---- Handle approve / ban actions (admin only, CSRF-checked) ---- */
$flash = '';
if ($isAdmin && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
    $uid    = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'];

    /* --- Artwork moderation --- */
    if ($action === 'approve_art' || $action === 'reject_art') {
        $awId = (int) ($_POST['artwork_id'] ?? 0);
        if ($awId > 0) {
            if ($action === 'approve_art') {
                owApproveArtwork($awId);
            } elseif (owHasColumn('artworks', 'rejected_at')) {
                /* Not approved: kept with the reason, so the artist can fix it */
                if (!owRejectArtwork($awId, (string) ($_POST['reason'] ?? ''))) {
                    $_SESSION['admin_err'] = 'Please write the reason, so the artist knows what to fix.';
                }
            } else {
                // Reject = remove the pending artwork and its uploaded image file.
                $row = getDB()->prepare('SELECT image_url FROM artworks WHERE id = :id');
                $row->execute([':id' => $awId]);
                $img = $row->fetchColumn();
                owDeleteAllStageFiles($awId);
                getDB()->prepare('DELETE FROM artworks WHERE id = :id')->execute([':id' => $awId]);
                if ($img && strpos($img, 'uploads/') === 0) {
                    $path = __DIR__ . '/' . $img;
                    if (is_file($path)) { @unlink($path); }
                }
            }
        }
        header('Location: admin.php' . (empty($_SESSION['admin_err']) ? '?ok=1' : '') . '#' . (in_array($_POST['back'] ?? '', ['artworks', 'photos'], true) ? $_POST['back'] : 'pending'));
        exit;
    }

    /* --- "Waiting for approval": changes, new artist accounts --- */
    if (in_array($action, ['approve_change', 'reject_change', 'approve_account', 'reject_account'], true)) {
        $actor = ['type' => 'admin', 'id' => null];
        $cid = (int) ($_POST['change_id'] ?? 0);
        $ok = match ($action) {
            'approve_change'  => owApproveChange($cid, $actor),
            'reject_change'   => owRejectChange($cid, (string) ($_POST['reason'] ?? ''), $actor),
            'approve_account' => owApproveArtistAccount($uid, $actor),
            'reject_account'  => owRejectArtistAccount($uid, (string) ($_POST['reason'] ?? ''), $actor),
        };
        if (!$ok) {
            $_SESSION['admin_err'] = ($GLOBALS['OW_APPROVE_ERR'] ?? '') === 'consent'
                ? 'This spotlight has photos but no active photo consent. Add the consent in Articles first, then approve.'
                : (str_starts_with($action, 'reject') && trim((string) ($_POST['reason'] ?? '')) === ''
                    ? 'Please write the reason, so the artist knows what to fix.' : 'This was already decided or no longer exists.');
        }
        header('Location: admin.php' . ($ok ? '?ok=1' : '') . '#pending');
        exit;
    }

    /* --- Certificate of Authenticity: cancel (with a reason), or make one now --- */
    if ($action === 'cert_cancel') {
        if (!owCancelCertificate((int) ($_POST['cert_id'] ?? 0), (string) ($_POST['reason'] ?? ''), null)) {
            $_SESSION['admin_err'] = trim((string) ($_POST['reason'] ?? '')) === '' ? 'Please write the reason for cancelling the certificate.' : 'This certificate was already cancelled.';
        }
        header('Location: admin.php' . (empty($_SESSION['admin_err']) ? '?ok=1' : '') . '#certs');
        exit;
    }
    if ($action === 'cert_issue') {
        if (owIssueCertificate((int) ($_POST['artwork_id'] ?? 0)) === '') $_SESSION['admin_err'] = 'No certificate yet: the artwork must be approved and have all its details and the artist’s declaration.';
        header('Location: admin.php' . (empty($_SESSION['admin_err']) ? '?ok=1' : '') . '#certs');
        exit;
    }

    /* --- "Photo changed, needs a look": the new photo is fine --- */
    if ($action === 'photo_ok') {
        owPhotoOk((int) ($_POST['artwork_id'] ?? 0));
        header('Location: admin.php?ok=1#photos');
        exit;
    }

    /* --- The owner's name on a certificate (after a sale or a resale) --- */
    if ($action === 'cert_owner') {
        owSetCertificateOwner((int) ($_POST['cert_id'] ?? 0), (string) ($_POST['owner_name'] ?? ''));
        header('Location: admin.php?ok=1#certs');
        exit;
    }

    /* --- Mark an artwork as sold / back to available --- */
    if ($action === 'sell_art' || $action === 'unsell_art') {
        $saleId = (int) ($_POST['artwork_id'] ?? 0);
        owSetArtworkSale($saleId, $action === 'sell_art');
        /* the buyer's name goes on the certificate as "Owner" (never on the public verify page) */
        $buyer = trim((string) ($_POST['buyer_name'] ?? ''));
        if ($action === 'sell_art' && $buyer !== '' && ($sc = owCertForArtwork($saleId))) owSetCertificateOwner((int) $sc['id'], $buyer);
        header('Location: admin.php?ok=1#artworks');
        exit;
    }

    /* --- "I'm Interested" request: status + private note --- */
    if ($action === 'inquiry_update') {
        owSetInquiry((int) ($_POST['inquiry_id'] ?? 0), (string) ($_POST['status'] ?? ''), isset($_POST['admin_notes']) ? (string) $_POST['admin_notes'] : null);
        header('Location: admin.php?ok=1#inquiries');
        exit;
    }

    /* --- Delete a test or spam account / one "I'm Interested" request (owner, 8 Nov 2026; the page asks "Are you sure?" first) --- */
    if ($action === 'delete_user' && $uid > 0) {
        $why = owAdminDeleteUser($uid);
        if ($why !== '') {
            $_SESSION['admin_err'] = ['staff' => 'Staff accounts cannot be deleted here. · لا يمكن حذف حسابات الفريق من هنا.',
                                      'linked' => 'This account is part of real records (gifts, commissions, orders, articles…), so it was not deleted. Suspend it instead. · هذا الحساب مرتبط بسجلات حقيقية، لذا لم يُحذف. أوقفه بدلاً من ذلك.',
                                      'not_found' => 'This account no longer exists. · هذا الحساب غير موجود.'][$why] ?? 'Not deleted.';
            header('Location: admin.php');
            exit;
        }
        header('Location: admin.php?ok=1');
        exit;
    }
    if ($action === 'delete_inquiry') {
        owAdminDeleteInquiry((int) ($_POST['inquiry_id'] ?? 0));
        header('Location: admin.php?ok=1#inquiries');
        exit;
    }

    /* --- Change a user's role --- */
    if ($action === 'set_role' && $uid > 0) {
        $newRole = $_POST['role'] ?? '';
        if (in_array($newRole, ['collector','artist','sub_admin','admin'], true)) {
            getDB()->prepare('UPDATE users SET role = :r WHERE id = :id')->execute([':r'=>$newRole, ':id'=>$uid]);
        }
        header('Location: admin.php?ok=1');
        exit;
    }

    $map = [
        'approve'   => 'UPDATE users SET is_approved = 1 WHERE id = :id',
        'unapprove' => 'UPDATE users SET is_approved = 0 WHERE id = :id',
        'ban'       => 'UPDATE users SET is_banned = 1 WHERE id = :id',
        'unban'     => 'UPDATE users SET is_banned = 0 WHERE id = :id',
    ];
    if (isset($map[$action]) && $uid > 0) {
        getDB()->prepare($map[$action])->execute([':id' => $uid]);
        owLog(['approve' => 'user_approve', 'unapprove' => 'user_unapprove', 'ban' => 'user_suspend', 'unban' => 'user_unsuspend'][$action], 'user', $uid, '', ['type' => 'admin', 'id' => null]);
        $flash = 'Updated.';
    }
    $backTo = in_array($_POST['back'] ?? '', ['collectors', 'artists'], true) ? '#' . $_POST['back'] : '';
    if (($_POST['back'] ?? '') === 'person') $backTo = '';
    header('Location: ' . (($_POST['back'] ?? '') === 'person' ? 'admin.php?person=' . $uid . '&ok=1' : 'admin.php' . ($flash ? '?ok=1' : '') . $backTo));
    exit;
}

/* ---- Load pending artworks (only when authed) ---- */
$pendingArt = [];
if ($isAdmin) {
    $pendingArt = getDB()->query(
        "SELECT a.*, a.id, a.title_en, a.title_ar, a.price, a.type, a.image_url, a.created_at,
                u.full_name_en AS artist_name
         FROM artworks a
         LEFT JOIN users u ON u.id = a.artist_id
         WHERE a.is_approved = 0" . owNotDraftSql('a') . (owHasColumn('artworks', 'rejected_at') ? ' AND a.rejected_at IS NULL' : '') . "
         ORDER BY a.created_at DESC"
    )->fetchAll();
/* Proof of Process for each pending artwork (stage photos + colors) */
$ART_TYPES     = require __DIR__ . '/art_types.php';
$pendingIds    = array_map(fn($r) => (int) $r['id'], $pendingArt);
$pendingStages = owGetStagesFor($pendingIds);
$pendingColors = owGetColorsFor($pendingIds);
}

/* Older schemas may lack last_active — detect once so queries don't fatal. */
$hasLastActive = false;
if ($isAdmin) {
    try {
        getDB()->query('SELECT last_active FROM users LIMIT 1');
        $hasLastActive = true;
    } catch (\Throwable $e) { $hasLastActive = false; }
}
$activeSel = $hasLastActive ? 'last_active' : 'NULL AS last_active';

/* ---- Online now (active within 15 minutes) ---- */
$onlineCount = 0;
if ($isAdmin && $hasLastActive) {
    $onlineCount = (int) getDB()->query(
        "SELECT COUNT(*) FROM users WHERE last_active >= (NOW() - INTERVAL 15 MINUTE)"
    )->fetchColumn();
}

/* ---- Load artists (only when authed) ---- */
$artists = [];
if ($isAdmin) {
    $artists = getDB()->query(
        "SELECT id, email, phone, full_name_en, full_name_ar, artist_name, city,
                is_verified, is_approved, profile_picture, art_video,
                COALESCE(is_banned,0) AS is_banned, created_at, last_login_at, role, $activeSel
         FROM users
         WHERE role = 'artist'
         ORDER BY is_approved ASC, created_at DESC"
    )->fetchAll();
}

/* ---- Load collectors (only when authed) ---- */
$collectors = [];
if ($isAdmin) {
    $collectors = getDB()->query(
        "SELECT id, email, phone, full_name_en, full_name_ar, city,
                is_verified, COALESCE(is_banned,0) AS is_banned, created_at, last_login_at, role, $activeSel
         FROM users
         WHERE role = 'collector'
         ORDER BY created_at DESC"
    )->fetchAll();
}

/* ---- Portfolio images per artist (id => [urls]) ---- */
$portfolio = [];
if ($isAdmin) {
    try {
        foreach (getDB()->query('SELECT artist_id, image_url FROM portfolio_images ORDER BY sort_order ASC') as $r) {
            $portfolio[(int)$r['artist_id']][] = $r['image_url'];
        }
    } catch (\Throwable $e) { /* table may not exist yet */ }
}

/* Is a user online (active within 15 min)? */
function isOnline(?string $lastActive): bool {
    return $lastActive !== null && strtotime($lastActive) >= (time() - 15 * 60);
}
function fmtDateTime(?string $v): string {
    return $v ? date('Y-m-d H:i', strtotime($v)) : '—';
}

/* ---- Load "I'm Interested" inquiries, newest first (only when authed) ---- */
$inquiries = [];
if ($isAdmin) {
    try { $inquiries = owListInquiries(); } catch (\Throwable $e) { error_log('admin inquiries: ' . $e->getMessage()); }
}


/* ---- Overview numbers and every artwork (with its status) ---- */
$overview = $isAdmin ? owAdminOverview() : [];
/* Today summary and visitor statistics (owner, 9 Nov 2026) */
$today = ['visitors' => 0, 'signups' => 0, 'interest' => 0];
$geo = [];
if ($isAdmin) {
    require_once __DIR__ . '/geo_lib.php';
    require_once __DIR__ . '/gallery_lib.php';
    try {
        $db = getDB();
        $today['visitors'] = owVisitorsToday();
        $today['signups']  = (int) $db->query("SELECT COUNT(*) FROM users WHERE role IN ('artist','collector') AND created_at >= CURDATE()")->fetchColumn();
        $today['interest'] = owHasTable('artwork_inquiries') ? (int) $db->query('SELECT COUNT(*) FROM artwork_inquiries WHERE created_at >= CURDATE()')->fetchColumn() : 0;
        foreach ([1, 7, 30] as $d) $geo[$d] = owGeoStats($d);
    } catch (Throwable $e) { error_log('admin today: ' . $e->getMessage()); }
}
$allArt = [];
if ($isAdmin) {
    try {
        $allArt = getDB()->query(
            "SELECT a.id, a.title_en, a.title_ar, a.price, a.status, a.image_url, a.created_at, a.artist_id,
                    " . owArtworkStatusSql('a') . " AS state, " . (owHasColumn('artworks', 'rejected_reason') ? 'a.rejected_reason' : 'NULL') . " AS reason,
                    " . (owHasColumn('artworks', 'view_count') ? 'a.view_count' : '0') . " AS views, " . owArtistNameSql('u') . "
             FROM artworks a LEFT JOIN users u ON u.id = a.artist_id
             WHERE 1" . owNotDraftSql('a') . " ORDER BY a.created_at DESC, a.id DESC LIMIT 1000")->fetchAll();
    } catch (Throwable $e) { error_log('admin all artworks: ' . $e->getMessage()); }
}
$photoChanges = $isAdmin ? owPhotoChangesPending() : [];
/* "Waiting for approval": changes to public things + new artist accounts */
/* Certificates of Authenticity, and approved artworks still waiting for theirs */
$allCerts = $isAdmin ? owAllCerts() : [];
$noCertYet = [];
if ($isAdmin && owCertReady()) {
    $q = getDB()->query("SELECT a.*, " . owArtistNameSql('u') . " FROM artworks a JOIN users u ON u.id = a.artist_id
                          WHERE a.is_approved = 1" . owNotDraftSql('a') . " AND NOT EXISTS (SELECT 1 FROM certificates c WHERE c.artwork_id = a.id AND c.status = 'valid')
                          ORDER BY a.id DESC LIMIT 200");
    $noCertYet = $q->fetchAll();
}
$waitChanges  = $isAdmin ? owWaitingChanges() : [];
$waitArtists  = $isAdmin ? owWaitingArtists() : [];
$waitTotal    = count($pendingArt) + count($waitChanges) + count($waitArtists);
$SALE_WORDS   = ['available' => ['Available', 'متاح'], 'sold' => ['Sold', 'مباع'], 'auction' => ['Auction', 'مزاد']];
/* one value, as the admin should read it */
$showVal = function (string $field, $v) use ($SALE_WORDS): string {
    $ART_TYPES = $GLOBALS['ART_TYPES'] ?? [];
    $v = (string) ($v ?? '');
    if ($v === '') return '<span class="mini">' . owBi('(empty)', '(فارغ)') . '</span>';
    if ($field === 'type') { $t = $ART_TYPES[$v] ?? null; return $t ? owBi($t['en'], $t['ar']) : e($v); }
    if ($field === 'status') { $w = $SALE_WORDS[$v] ?? null; return $w ? owBi($w[0], $w[1]) : e($v); }
    if ($field === 'price') return e(number_format((float) $v)) . ' ' . owBi('SAR', 'ر.س');
    if ($field === 'edition_type') { [$a1, $a2] = owCertEdition($v); return owBi($v === 'limited' ? 'Limited print' : $a1, $v === 'limited' ? 'نسخة مطبوعة محدودة' : $a2); }
    if ($field === 'profile_picture') { $src = owImgSrc($v); return $src !== '' ? '<a href="' . e($src) . '" target="_blank" rel="noopener"><img class="wc-pic" src="' . e($src) . '" alt="Photo · صورة"></a>' : e($v); }
    if (in_array($field, ['instagram', 'twitter', 'website'], true)) return '<span dir="ltr">' . e($v) . '</span>';
    return '<span dir="auto">' . nl2br(e(mb_strimwidth($v, 0, 600, '…'))) . '</span>';
};
$ART_STATE = ['draft' => ['Draft', 'مسودة'], 'pending' => ['In review', 'قيد المراجعة'], 'approved' => ['In the Gallery', 'في المعرض'],
              'rejected' => ['Not approved', 'لم تتم الموافقة'], 'sold' => ['Sold', 'مباع']];
?>
<!DOCTYPE html>
<html <?php require_once __DIR__ . '/lang_lib.php'; owHtmlAttrs(); ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin · Oweili</title>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
.ov-grid{grid-template-columns:repeat(auto-fit,minmax(150px,1fr));}
.adm-sec{margin:var(--space-md) 0 var(--space-xs);font-size:13px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--color-body);}
.today-grid{grid-template-columns:repeat(4,minmax(0,1fr));}
.today-grid .stat-card{text-decoration:none;}
.today-grid .stat-num{font-size:32px;}
.stat-wait.has{border-color:var(--color-primary);box-shadow:0 0 0 2px rgba(var(--rgb-primary),.35);}
.ov-small .stat-card{padding:12px 16px;}
.ov-small .stat-num{font-size:20px;}
@media (max-width:640px){.today-grid{grid-template-columns:repeat(2,minmax(0,1fr));}}
.adm-main{margin-top:var(--space-lg);}
.pill.hot{background:var(--color-primary);color:var(--color-on-primary);}
.adm-sub{display:none;flex-wrap:wrap;gap:8px;margin:0 0 var(--space-md);}
.adm-sub.show{display:flex;}
.sub-btn{display:inline-flex;align-items:center;gap:6px;min-height:36px;padding:6px 14px;border-radius:999px;border:1px solid var(--color-border-strong);background:var(--color-surface);color:var(--color-dark);font:inherit;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;}
.sub-btn:hover,.sub-btn:focus-visible{border-color:var(--color-accent);}
.sub-btn.on{background:var(--color-accent);border-color:var(--color-accent);color:#fff;}
.adm-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:var(--space-sm);}
.adm-cards .card{display:flex;flex-direction:column;gap:6px;text-decoration:none;color:var(--color-dark);}
.adm-cards .card span{font-size:14px;color:var(--color-body);}
.stat-range{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 var(--space-sm);}
.stat-total{font-size:16px;margin:0 0 var(--space-sm);}
.stat-total strong{font-size:28px;}
.stat-cols{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:var(--space-sm);margin-bottom:var(--space-sm);}
.stat-cols h3{font-size:16px;margin:0 0 var(--space-xs);}
.stat-cols>.card{margin:0;}
.adm-main{flex-wrap:wrap;overflow-x:visible;}
.stat-cols td.num{text-align:end;font-weight:700;width:60px;}
.bars{list-style:none;margin:0;padding:0;display:grid;gap:8px;}
.bars li{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(0,1fr) 40px;gap:8px;align-items:center;font-size:14px;}
.b-bar{height:10px;border-radius:5px;background:var(--color-border);overflow:hidden;}
.b-bar i{display:block;height:100%;background:var(--color-accent);}
.b-n{text-align:end;font-weight:700;}
.adm-search{max-width:420px;margin-bottom:var(--space-sm);}
.rej-box{display:inline-block;}
.rej-box summary{list-style:none;cursor:pointer;}
.rej-box summary::-webkit-details-marker{display:none;}
.rej-box[open]{display:block;width:100%;margin-top:var(--space-xs);padding:var(--space-sm);border:1px solid var(--color-border);border-radius:var(--radius-input);background:var(--color-surface);}
.rej-box textarea,.inq-edit textarea{width:100%;min-height:0;}
.inq-edit form{display:grid;gap:6px;min-width:220px;}
.inq-edit select{min-height:40px;}
.acard-head{flex-wrap:wrap;}
.pc-list{display:grid;gap:var(--space-md);}
.pc-item{border:1px solid var(--color-border);border-radius:var(--radius-card);padding:var(--space-sm);}
.pc-photos{display:grid;grid-template-columns:1fr 1fr;gap:var(--space-sm);margin:var(--space-sm) 0;}
.pc-photos figure{margin:0;}
.pc-photos figcaption{font-size:var(--fs-label);font-weight:700;margin-bottom:4px;}
.pc-photos img{width:100%;aspect-ratio:4/3;object-fit:contain;background:var(--color-frame);border:1px solid var(--color-border);border-radius:var(--radius-input);display:block;}
.acard-view{margin-inline-start:auto;align-self:flex-start;white-space:normal;max-width:100%;}
/* Admin panel — staff layout only; buttons, tabs, tables, cards, forms and stat cards come from oweili.css (role color: admin dark) */
.wrap{max-width:1100px;margin:0 auto;padding:var(--space-lg);}
.sub{font-size:var(--fs-body);color:var(--color-body);margin:4px 0 var(--space-sm);}
.login-card{max-width:400px;margin:8vh auto 0;}
.login-card h1{font-size:var(--fs-card);margin-bottom:var(--space-sm);}
.err{color:var(--color-dark);font-size:var(--fs-label);font-weight:600;margin-bottom:var(--space-sm);}
.top{display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:var(--space-sm);margin-bottom:var(--space-md);}
.top h1{font-size:var(--fs-section);margin:0;}
.top-actions{display:flex;flex-wrap:wrap;gap:var(--space-xs);align-items:center;}
/* staff bar (top corner) and the grouped tools row */
.adm-bar{position:sticky;top:0;z-index:50;height:64px;display:flex;align-items:center;justify-content:space-between;gap:var(--space-sm);padding:0 var(--space-lg);background:rgba(var(--rgb-surface),.85);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border-bottom:1px solid var(--color-border);}
.adm-brand{display:inline-flex;align-items:center;gap:8px;font-weight:700;letter-spacing:3px;color:var(--color-dark);text-decoration:none;font-size:16px;}
.adm-brand-sub{letter-spacing:0;font-weight:600;font-size:var(--fs-caption);color:var(--color-body);padding-inline-start:8px;border-inline-start:1px solid var(--color-border-strong);}
.adm-corner{display:flex;align-items:center;gap:var(--space-xs);}
.adm-sep{width:1px;height:24px;background:var(--color-border-strong);margin:0 4px;}
.adm-tools{display:flex;flex-wrap:wrap;gap:var(--space-sm) var(--space-md);align-items:center;padding:var(--space-sm);margin:0 0 var(--space-md);background:var(--color-surface);border:1px solid var(--color-border);border-radius:var(--radius-card);}
.adm-group{display:flex;flex-wrap:wrap;gap:var(--space-xs);align-items:center;}
.adm-group + .adm-group{padding-inline-start:var(--space-md);border-inline-start:1px solid var(--color-border);}
.adm-glabel{font-size:var(--fs-min);font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--color-body);margin-inline-end:2px;}
@media(max-width:700px){.adm-bar{padding:0 var(--space-sm);}.adm-brand-sub{display:none;}.adm-corner{gap:6px;}.adm-sep{display:none;}
  .adm-brand .adm-word{display:none;}
  .adm-tools{flex-direction:column;align-items:stretch;}
  .adm-group{display:grid;grid-template-columns:1fr 1fr;}
  .adm-group .adm-glabel{grid-column:1 / -1;}
  .adm-group + .adm-group{padding-inline-start:0;border-inline-start:0;padding-top:var(--space-sm);border-top:1px solid var(--color-border);}}
.stat-grid{margin-bottom:var(--space-lg);}
.pill.on{background:rgba(var(--rgb-accent),0.1);color:var(--color-accent-dark);}
.pill.off{background:rgba(var(--rgb-ink),0.06);color:var(--color-dark);}
.pill.ban{background:var(--color-tint-pink);border:1px solid rgba(var(--rgb-primary),0.3);color:var(--color-dark);}
.pill.role-artist{background:var(--color-tint-pink);color:var(--color-dark);}
.pill.role-collector,.pill.role-admin{background:rgba(var(--rgb-accent),0.1);color:var(--color-accent-dark);}
.acts{display:flex;gap:var(--space-xs);flex-wrap:wrap;}
.name-ar{font-size:var(--fs-caption);color:var(--color-body);}
.empty{padding:var(--space-lg);text-align:center;color:var(--color-body);}
.sec-head{margin-bottom:var(--space-sm);}
.sec-head h2{font-size:var(--fs-subtitle);display:flex;align-items:center;gap:var(--space-xs);margin:0;}
.count{background:var(--color-dark);color:var(--color-on-dark);font-size:var(--fs-min);font-weight:700;padding:2px 9px;border-radius:var(--radius-pill);}
.tab .count{background:rgba(var(--rgb-ink),0.07);color:var(--color-dark);}
.tab.active .count{background:rgba(var(--rgb-surface),0.22);color:var(--color-on-dark);}
.wc-h{font-size:18px;margin:var(--space-md) 0 var(--space-sm);display:flex;align-items:center;gap:8px;}
.wc-list{display:flex;flex-direction:column;gap:var(--space-sm);}
.wc-item{border:1px solid var(--color-border);border-radius:12px;padding:var(--space-sm);background:var(--color-surface);}
.wc-top{display:flex;justify-content:space-between;align-items:flex-start;gap:var(--space-sm);margin-bottom:var(--space-xs);}
.wc-top .pill{margin-inline-end:6px;}
.wc-table td{vertical-align:top;word-break:break-word;}
.wc-new{background:rgba(var(--rgb-accent),.06);}
.wc-pic{width:72px;height:72px;object-fit:cover;border-radius:50%;border:1px solid var(--color-border);}
.wc-lbl{display:none;font-weight:700;}
.art-cert{display:flex;flex-direction:column;gap:4px;margin-top:var(--space-xs);font-size:var(--fs-caption);color:var(--color-body);}
.art-cert .warn{color:var(--color-dark);background:var(--color-tint-pink);padding:4px 8px;border-radius:var(--radius-input);}
@media(max-width:640px){
  .wc-table thead{display:none;}
  .wc-table tr{display:block;border-bottom:1px solid var(--color-border);padding:8px 0;}
  .wc-table td{display:block;border:0;padding:4px 12px;}
  .wc-table td.wc-f{font-weight:700;}
  .wc-lbl{display:inline;}
}
.wc-note{margin:var(--space-xs) 0;padding:8px 12px;border-radius:var(--radius-input);background:var(--color-tint-pink);}
.wc-item .acts{display:flex;flex-wrap:wrap;gap:8px;align-items:flex-start;margin-top:var(--space-xs);}
.tab-panel{display:none;}
.tab-panel.active{display:block;}
.art-list{display:flex;flex-direction:column;gap:var(--space-sm);}
.art-item{display:flex;flex-wrap:wrap;align-items:center;gap:var(--space-sm);padding:var(--space-sm);border:1px solid var(--color-border);border-radius:var(--radius-card);background:var(--color-surface);}
.art-thumb{width:72px;height:72px;border-radius:var(--radius-input);background-size:contain;background-repeat:no-repeat;background-position:center;background-color:var(--color-surface);border:1px solid var(--color-border);flex-shrink:0;}
.art-meta{flex:1;min-width:0;}
.art-proof{flex-basis:100%;min-width:0;}
.art-proof .pj{margin-top:4px;}
.art-meta strong{font-size:var(--fs-body);color:var(--color-dark);}
.art-info{font-size:var(--fs-caption);color:var(--color-body);margin-top:3px;}
.verify-cell{display:flex;flex-direction:column;gap:6px;align-items:flex-start;min-width:150px;}
[dir="rtl"] .verify-cell{align-items:flex-end;}
.verify-photo{width:52px;height:52px;border-radius:var(--radius-input);object-fit:cover;border:1px solid var(--color-border);}
.verify-video{width:150px;max-width:100%;border-radius:var(--radius-input);background:#000;}
.video-link{font-size:var(--fs-label);font-weight:600;}
.no-media{font-size:var(--fs-label);color:var(--color-body);font-style:italic;}
.status-pills{display:flex;gap:5px;flex-wrap:wrap;}
.style-item{display:flex;gap:var(--space-sm);padding:var(--space-sm);border:1px solid var(--color-border);border-radius:var(--radius-card);margin-bottom:var(--space-sm);align-items:flex-start;}
.style-thumb{width:80px;height:80px;border-radius:var(--radius-input);background-size:cover;background-position:center;background-color:var(--color-surface-2);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:24px;color:var(--color-light);}
.style-body{flex:1;min-width:0;}
.style-body h3{font-size:var(--fs-body);margin:0 0 2px;}
.style-desc{font-size:var(--fs-caption);color:var(--color-body);line-height:1.6;margin:4px 0 6px;}
.tag-pill{display:inline-block;padding:3px 10px;border-radius:var(--radius-pill);font-size:var(--fs-min);font-weight:700;background:rgba(var(--rgb-accent),0.1);color:var(--color-accent-dark);}
.edit-form{display:none;margin-top:var(--space-sm);padding-top:var(--space-sm);border-top:1px solid var(--color-border);}
.edit-form.open{display:block;}
.online-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--color-light);vertical-align:middle;}
.online-dot.on{background:var(--color-subadmin);}
.port-thumbs{display:flex;gap:4px;flex-wrap:wrap;max-width:150px;}
.port-thumbs a{display:block;}
.port-thumbs img{width:34px;height:34px;border-radius:6px;object-fit:cover;border:1px solid var(--color-border);}
.role-select{min-height:var(--btn-h-sm);padding:4px 8px;border-radius:var(--radius-input);border:1px solid var(--color-border-strong);font-size:var(--fs-label);background:var(--color-surface);}
.role-form{display:flex;gap:4px;align-items:center;margin-top:4px;}
.mini{font-size:var(--fs-label);color:var(--color-body);}
.acards{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:var(--space-md);}
.acard{border:1px solid var(--color-border);border-radius:var(--radius-card);padding:var(--space-md);background:var(--color-surface);box-shadow:var(--shadow-card);display:flex;flex-direction:column;gap:var(--space-sm);}
.acard-head{display:flex;gap:var(--space-sm);align-items:center;}
.acard-photo{width:56px;height:56px;border-radius:50%;background-size:cover;background-position:center;background-color:var(--color-artist);display:flex;align-items:center;justify-content:center;color:var(--color-on-primary);font-size:22px;font-weight:700;flex-shrink:0;}
.acard-id{flex:1;min-width:0;}
.acard-id .nm{font-size:var(--fs-body);font-weight:700;color:var(--color-dark);display:flex;align-items:center;gap:6px;}
.acard-id .an{font-size:var(--fs-caption);color:var(--color-body);}
.acard-badges{display:flex;gap:5px;flex-wrap:wrap;}
.acard-info{font-size:var(--fs-caption);color:var(--color-body);line-height:1.7;}
.acard-info b{color:var(--color-dark);font-weight:600;}
.acard-media{display:flex;gap:var(--space-xs);flex-wrap:wrap;align-items:flex-start;}
.acard-media video{width:100%;border-radius:var(--radius-input);background:#000;}
.acard-foot{display:flex;flex-direction:column;gap:var(--space-xs);border-top:1px solid var(--color-border);padding-top:var(--space-sm);margin-top:auto;}
.card + .card{margin-top:var(--space-md);}
@media(max-width:768px){.wrap{padding:var(--space-sm);}}
@media(max-width:640px){.style-item{flex-direction:column;}.acards{grid-template-columns:1fr;}}
</style>
<link rel="stylesheet" href="<?= owAsset('assets/css/proof.css') ?>">
</head>
<body class="staff-page">
<!-- Staff bar: brand on one side; language, dark mode and Log out in the corner, apart from the tools (like the site menu) -->
<header class="adm-bar">
  <a class="adm-brand" href="admin.php" aria-label="Oweili admin · إدارة أويلي"><svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true"><g style="fill:var(--color-primary)"><path d="M11 1A10 10 0 0 0 1 11h10z"/><path d="M13 1v10h10A10 10 0 0 0 13 1z"/><path d="M1 13a10 10 0 0 0 10 10V13z"/><path d="M13 13v10a10 10 0 0 0 10-10z"/></g></svg><span class="adm-word">OWEILI</span> <span class="adm-brand-sub"><?= owBi('Admin', 'الإدارة') ?></span></a>
  <div class="adm-corner">
    <?php owCornerControls('tglLang()'); ?>
    <?php if ($isAdmin): ?><span class="adm-sep" aria-hidden="true"></span><a class="btn btn-secondary btn-sm" href="admin.php?logout=1"><?= owBi('Log out', 'تسجيل الخروج') ?></a><?php endif; ?>
  </div>
</header>
<div class="wrap">
<?php if (!$isAdmin): ?>

  <?php /* after signing in, open the page an alert email pointed to (admin.php?person=N or admin.php#pending) */ ?>
  <form class="card login-card" method="post" action="admin.php<?= isset($_GET['person']) ? '?person=' . (int) $_GET['person'] : '' ?>"
        onsubmit="if (/^#[a-z]+$/.test(location.hash)) this.action += location.hash;">
    <h1>Admin Login</h1>
    <?php if ($loginError): ?><div class="err"><?= e($loginError) ?></div><?php endif; ?>
    <?= csrfField() ?>
    <div class="field"><label for="admin_pass">Password</label>
    <input type="password" id="admin_pass" name="admin_pass" required autofocus></div>
    <button class="btn btn-primary btn-block" type="submit">Sign In</button>
  </form>

<?php else: ?>

  <div class="dash role-admin">
  <div class="top">
    <div>
      <h1>Admin Dashboard</h1>
      <div class="sub">Manage artists, buyers, artworks and requests.</div>
    </div>
  </div>
  <p class="adm-sec"><?= owBi('Today', 'اليوم') ?></p>
  <div class="stat-grid today-grid">
    <a class="stat-card" href="#stats" onclick="showTab('stats');return false;"><span class="stat-num"><?= (int) $today['visitors'] ?></span><span class="stat-lbl"><?= owBi('Visitors', 'الزوار') ?></span></a>
    <a class="stat-card" href="#artists" onclick="showTab('artists');return false;"><span class="stat-num"><?= (int) $today['signups'] ?></span><span class="stat-lbl"><?= owBi('New sign-ups', 'تسجيلات جديدة') ?></span></a>
    <a class="stat-card" href="#inquiries" onclick="showTab('inquiries');return false;"><span class="stat-num"><?= (int) $today['interest'] ?></span><span class="stat-lbl"><?= owBi('“I’m interested” requests', 'طلبات «أنا مهتم»') ?></span></a>
    <a class="stat-card stat-wait<?= $waitTotal ? ' has' : '' ?>" href="#pending" onclick="showTab('pending');return false;"><span class="stat-num"><?= (int) $waitTotal ?></span><span class="stat-lbl"><?= owBi('Waiting for my approval', 'بانتظار موافقتي') ?></span></a>
  </div>
  <p class="adm-sec"><?= owBi('All time', 'منذ البداية') ?></p>
  <div class="stat-grid ov-grid ov-small">
    <div class="stat-card"><span class="stat-num"><?= (int) $overview['artists'] ?></span><span class="stat-lbl"><?= owBi('Artists', 'الفنانون') ?></span></div>
    <div class="stat-card"><span class="stat-num"><?= (int) $overview['buyers'] ?></span><span class="stat-lbl"><?= owBi('Buyers', 'المشترون') ?></span></div>
    <div class="stat-card"><span class="stat-num"><?= (int) $overview['artworks'] ?></span><span class="stat-lbl"><?= owBi('Artworks (' . (int) $overview['in_gallery'] . ' in the Gallery)', 'الأعمال (' . (int) $overview['in_gallery'] . ' في المعرض)') ?></span></div>
    <div class="stat-card"><span class="stat-num"><?= (int) $overview['inquiries'] ?></span><span class="stat-lbl"><?= owBi('Interest requests (' . (int) $overview['new_inq'] . ' new)', 'طلبات الاهتمام (' . (int) $overview['new_inq'] . ' جديد)') ?></span></div>
    <div class="stat-card"><span class="stat-num"><?= (int) $overview['week'] ?></span><span class="stat-lbl"><?= owBi('New sign-ups this week', 'تسجيلات هذا الأسبوع') ?></span></div>
    <?php if ($photoChanges): ?><a class="stat-card" href="#photos" onclick="showTab('photos',document.querySelector('.tab-btn[data-tab=photos]'));return false;" style="text-decoration:none"><span class="stat-num"><?= count($photoChanges) ?></span><span class="stat-lbl"><?= owBi('Photo changed, needs a look', 'صورة تغيّرت، تحتاج نظرة') ?></span></a><?php endif; ?>
    <div class="stat-card"><span class="stat-num"><span class="online-dot on" aria-hidden="true"></span> <?= (int) $onlineCount ?></span><span class="stat-lbl">Online Now</span></div>
  </div>
  </div>

  <?php if (!empty($_SESSION['admin_err'])): ?>
    <div class="alert alert-error">
      <strong>⚠ Could not save.</strong> <?= e($_SESSION['admin_err']) ?>
      <div class="mini" style="margin-top:6px;">If this mentions a missing table, run <code>discover_setup.sql</code> in phpMyAdmin.</div>
    </div>
    <?php unset($_SESSION['admin_err']); ?>
  <?php elseif (isset($_GET['ok'])): ?>
    <div class="alert alert-ok"><strong>✓ Saved.</strong></div>
  <?php endif; ?>

  <!-- TABS (owner, 9 Nov 2026): Waiting for approval first, then grouped -->
  <?php require_once __DIR__ . '/auction_lib.php'; $auOn = function_exists('owAuctionsPageOn') && owAuctionsPageOn(); $auList = owAuctionList();
        $giftsOn = function_exists('owGiftsPageOn') && owGiftsPageOn(); $commOn = function_exists('owCommissionsPageOn') && owCommissionsPageOn(); ?>
  <div class="tabs adm-main" role="tablist">
    <button class="tab tab-btn active" data-tab="pending" onclick="showTab('pending')"><?= owBi('Waiting for approval', 'بانتظار الموافقة') ?> <span class="pill count<?= $waitTotal ? ' hot' : '' ?>"><?= (int) $waitTotal ?></span></button>
    <button class="tab tab-btn" data-tab="g-people" onclick="showTab('artists')"><?= owBi('Artists', 'الفنانون') ?></button>
    <button class="tab tab-btn" data-tab="g-art" onclick="showTab('artworks')"><?= owBi('Artworks', 'الأعمال') ?><?php if ($photoChanges): ?> <span class="pill count hot"><?= count($photoChanges) ?></span><?php endif; ?></button>
    <button class="tab tab-btn" data-tab="g-req" onclick="showTab('inquiries')"><?= owBi('Requests', 'الطلبات') ?></button>
    <button class="tab tab-btn" data-tab="articles" onclick="showTab('articles')"><?= owBi('Articles', 'المقالات') ?></button>
    <button class="tab tab-btn" data-tab="stats" onclick="showTab('stats')"><?= owBi('Statistics', 'الإحصاءات') ?></button>
    <button class="tab tab-btn" data-tab="settings" onclick="showTab('settings')"><?= owBi('Settings', 'الإعدادات') ?></button>
  </div>
  <div class="adm-sub" data-group="g-people">
    <button class="sub-btn" data-tab="artists" onclick="showTab('artists')"><?= owBi('Artists', 'الفنانون') ?> <span class="pill count"><?= count($artists) ?></span></button>
    <button class="sub-btn" data-tab="collectors" onclick="showTab('collectors')"><?= owBi('Buyers', 'المشترون') ?> <span class="pill count"><?= count($collectors) ?></span></button>
  </div>
  <div class="adm-sub" data-group="g-art">
    <button class="sub-btn" data-tab="artworks" onclick="showTab('artworks')"><?= owBi('All artworks', 'كل الأعمال') ?> <span class="pill count"><?= count($allArt) ?></span></button>
    <button class="sub-btn" data-tab="photos" onclick="showTab('photos')"><?= owBi('Photo changes', 'تغيير الصور') ?> <span class="pill count"><?= count($photoChanges) ?></span></button>
    <button class="sub-btn" data-tab="certs" onclick="showTab('certs')"><?= owBi('Certificates', 'الشهادات') ?> <span class="pill count"><?= count($allCerts) ?></span></button>
  </div>
  <div class="adm-sub" data-group="g-req">
    <button class="sub-btn" data-tab="inquiries" onclick="showTab('inquiries')"><?= owBi('“I’m interested”', '«أنا مهتم»') ?> <span class="pill count"><?= count($inquiries) ?></span></button>
    <?php if ($auOn): ?><button class="sub-btn" data-tab="auctions" onclick="showTab('auctions')"><?= owBi('Auctions', 'المزاد') ?> <span class="pill count"><?= count($auList) ?></span></button><?php endif; ?>
    <?php if ($giftsOn): ?><a class="sub-btn" href="admin_gifts.php"><?= owBi('Gifts', 'الهدايا') ?></a><?php endif; ?>
    <?php if ($commOn): ?><a class="sub-btn" href="admin_commissions.php"><?= owBi('Commissions', 'الأعمال الخاصة') ?></a><?php endif; ?>
  </div>

  <!-- ===== TAB: ARTICLES (links to the team tools) ===== -->
  <div class="tab-panel" id="tab-articles">
    <div class="adm-cards">
      <a class="card card-hover" href="admin_articles.php"><b><?= owBi('Articles & events', 'المقالات والملتقيات') ?></b><span><?= owBi('Write and publish Art Events and Art Education.', 'اكتب وانشر الملتقيات والتثقيف الفني.') ?></span></a>
      <a class="card card-hover" href="admin_gallery.php"><b><?= owBi('Gallery', 'المعرض') ?></b><span><?= owBi('Collections, Artist of the week, verified artists.', 'المجموعات، فنان الأسبوع، الفنانون الموثّقون.') ?></span></a>
      <a class="card card-hover" href="admin_institutes.php"><b><?= owBi('Institutes', 'المعاهد') ?></b><span><?= owBi('Art institutes and their courses.', 'المعاهد الفنية ودوراتها.') ?></span></a>
    </div>
  </div>

  <!-- ===== TAB: STATISTICS (visitor countries and cities, geo_lib.php) ===== -->
  <div class="tab-panel" id="tab-stats">
    <?php $hasGeo = owGeoReady(); ?>
    <div class="stat-range" role="group" aria-label="Period · المدة">
      <button class="sub-btn on" data-days="1" onclick="statRange(1,this)"><?= owBi('Today', 'اليوم') ?></button>
      <button class="sub-btn" data-days="7" onclick="statRange(7,this)"><?= owBi('Last 7 days', 'آخر 7 أيام') ?></button>
      <button class="sub-btn" data-days="30" onclick="statRange(30,this)"><?= owBi('Last 30 days', 'آخر 30 يوماً') ?></button>
    </div>
    <?php if (!owHasTable('visitor_stats')): ?>
      <p class="mini"><?= owBi('Statistics start once the visitor_stats table is added (visitor_stats.sql).', 'تبدأ الإحصاءات بعد إضافة جدول visitor_stats.') ?></p>
    <?php elseif (!$hasGeo): ?>
      <p class="mini"><?= owBi('The location file is being downloaded. Visits are counted already; cities show once it is ready.', 'يجري تنزيل ملف المواقع. الزيارات تُحسب الآن، وتظهر المدن عند جاهزيته.') ?></p>
    <?php endif; ?>
    <?php foreach ([1, 7, 30] as $d): $gs = $geo[$d] ?? ['total' => 0, 'countries' => [], 'cities' => []]; ?>
    <div class="stat-block" data-days="<?= $d ?>"<?= $d === 1 ? '' : ' hidden' ?>>
      <p class="stat-total"><strong><?= (int) $gs['total'] ?></strong> <?= owBi('visitors', 'زائر') ?></p>
      <div class="stat-cols">
        <div class="card"><h3><?= owBi('Top cities', 'أكثر المدن') ?></h3>
          <?php if (!$gs['cities']): ?><p class="mini"><?= owBi('No visits yet in this period.', 'لا زيارات بعد في هذه المدة.') ?></p><?php else: $max = max(1, (int) $gs['cities'][0]['v']); ?>
          <ol class="bars"><?php foreach (array_slice($gs['cities'], 0, 10) as $c): ?>
            <li><span class="b-name"><?= owBi((string) $c['city'], owCityName((string) $c['city'], true)) ?> <span class="mini">· <?= owBi(owCountryName($c['country'], false), owCountryName($c['country'], true)) ?></span></span><span class="b-bar"><i style="width:<?= round(100 * $c['v'] / $max) ?>%"></i></span><span class="b-n"><?= (int) $c['v'] ?></span></li>
          <?php endforeach; ?></ol><?php endif; ?>
        </div>
        <div class="card"><h3><?= owBi('By country', 'حسب الدولة') ?></h3>
          <?php if (!$gs['countries']): ?><p class="mini"><?= owBi('No visits yet in this period.', 'لا زيارات بعد في هذه المدة.') ?></p><?php else: ?>
          <table class="table"><tbody><?php foreach ($gs['countries'] as $c): ?>
            <tr><td><?= owBi(owCountryName($c['country'], false), owCountryName($c['country'], true)) ?></td><td class="num"><?= (int) $c['v'] ?></td></tr>
          <?php endforeach; ?></tbody></table><?php endif; ?>
        </div>
        <div class="card"><h3><?= owBi('By city', 'حسب المدينة') ?></h3>
          <?php if (!$gs['cities']): ?><p class="mini"><?= owBi('No visits yet in this period.', 'لا زيارات بعد في هذه المدة.') ?></p><?php else: ?>
          <table class="table"><tbody><?php foreach ($gs['cities'] as $c): ?>
            <tr><td><?= owBi((string) $c['city'], owCityName((string) $c['city'], true)) ?> <span class="mini">· <?= owBi(owCountryName($c['country'], false), owCountryName($c['country'], true)) ?></span></td><td class="num"><?= (int) $c['v'] ?></td></tr>
          <?php endforeach; ?></tbody></table><?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <p class="mini"><?= owBi('One visit = one browser on one day. Robots and your own Admin browser are not counted. Only the country, city and date are kept, never the internet address.', 'الزيارة = متصفح واحد في يوم واحد. لا تُحسب الروبوتات ولا متصفح الإدارة الخاص بك. نحفظ الدولة والمدينة والتاريخ فقط، ولا نحفظ عنوان الإنترنت.') ?>
      <?= owBi('Location data: ', 'بيانات المواقع: ') ?><a href="https://db-ip.com" target="_blank" rel="noopener">IP Geolocation by DB-IP</a> (CC BY 4.0)</p>
  </div>

  <!-- ===== TAB: SETTINGS (links) ===== -->
  <div class="tab-panel" id="tab-settings">
    <div class="adm-cards">
      <a class="card card-hover" href="admin.php?settings=1"><b><?= owBi('Site settings', 'إعدادات الموقع') ?></b><span><?= owBi('Show or hide parts of the site, Bashro, payments, signature.', 'إظهار أو إخفاء أجزاء الموقع، بشرو، الدفع، التوقيع.') ?></span></a>
      <a class="card card-hover" href="admin.php?photo_check=1"><b><?= owBi('Photo check', 'فحص الصور') ?></b><span><?= owBi('Are artwork photos and their small copies working?', 'هل تعمل صور الأعمال ونسخها الصغيرة؟') ?></span></a>
      <a class="card card-hover" href="admin.php?mail_check=1"><b><?= owBi('Email check', 'فحص البريد') ?></b><span><?= owBi('Send a test email and see the answer.', 'أرسل بريداً تجريبياً واطّلع على الرد.') ?></span></a>
      <a class="card card-hover" href="admin.php?error_log=1"><b><?= owBi('Error log', 'سجل الأخطاء') ?></b><span><?= owBi('The newest errors on the server.', 'أحدث الأخطاء على الخادم.') ?></span></a>
    </div>
  </div>

  <!-- ===== TAB: ARTISTS ===== -->
  <div class="tab-panel" id="tab-artists">
  <div class="card">
    <div class="field adm-search"><label for="s-artists"><?= owBi('Search artists (name, email, phone, city)', 'ابحث عن فنان (الاسم، البريد، الهاتف، المدينة)') ?></label>
      <input id="s-artists" type="search" data-search=".acard" autocomplete="off"></div>
    <?php if (!$artists): ?>
      <div class="empty">No artist accounts yet.</div>
    <?php else: ?>
      <div class="acards">
      <?php foreach ($artists as $a): $on = isOnline($a['last_active'] ?? null); $pics = $portfolio[(int)$a['id']] ?? []; ?>
        <div class="acard">
          <div class="acard-head">
            <?php if (!empty($a['profile_picture'])): ?>
              <a href="<?= e($a['profile_picture']) ?>" target="_blank"><span class="acard-photo" style="background-image:url('<?= e($a['profile_picture']) ?>');"></span></a>
            <?php else: ?>
              <span class="acard-photo"><?= e(mb_strtoupper(mb_substr(owRealName($a['full_name_en']) ?: 'A',0,1))) ?></span>
            <?php endif; ?>
            <div class="acard-id">
              <div class="nm"><span class="online-dot <?= $on ? 'on' : '' ?>" title="<?= $on ? 'Online' : 'Offline' ?>"></span><?= e(owRealName($a['full_name_en']) ?: (owRealName($a['artist_name']) ?: '—')) ?></div>
              <?php if (owRealName($a['artist_name']) !== ''): ?><div class="an"><?= e($a['artist_name']) ?></div><?php endif; ?>
              <?php if (owRealName($a['full_name_ar']) !== ''): ?><div class="an" dir="rtl"><?= e($a['full_name_ar']) ?></div><?php endif; ?>
            </div>
            <a class="btn btn-secondary btn-sm acard-view" href="admin.php?person=<?= (int) $a['id'] ?>"><?= owBi('View works & activity', 'الأعمال والنشاط') ?></a>
          </div>
          <span class="adm-hay" hidden><?= e(mb_strtolower(implode(' ', [$a['full_name_en'], $a['full_name_ar'], $a['artist_name'], $a['email'], $a['phone'], $a['city']]))) ?></span>

          <div class="acard-badges">
            <span class="pill <?= $a['is_verified'] ? 'on' : 'off' ?>"><?= $a['is_verified'] ? 'Verified' : 'Unverified' ?></span>
            <span class="pill <?= $a['is_approved'] ? 'on' : 'off' ?>"><?= $a['is_approved'] ? 'Approved' : 'Pending' ?></span>
            <?php if ((int)$a['is_banned'] === 1): ?><span class="pill ban">Banned</span><?php else: ?><span class="pill on">Active</span><?php endif; ?>
          </div>

          <div class="acard-info">
            <div>✉️ <?= e($a['email']) ?></div>
            <div dir="ltr">📞 <?= e($a['phone'] ?: '—') ?></div>
            <div>📍 <?= e($a['city'] ?: '—') ?></div>
            <div><b><?= owBi('Joined:', 'انضم:') ?></b> <?= e($a['created_at'] ? date('Y-m-d', strtotime($a['created_at'])) : '—') ?> · <b><?= owBi('Last sign-in:', 'آخر دخول:') ?></b> <?= e(fmtDateTime($a['last_login_at'] ?? null)) ?></div>
          </div>

          <div class="acard-media">
            <?php if ($pics): ?>
              <div class="port-thumbs" style="max-width:none;">
                <?php foreach ($pics as $pu): ?><a href="<?= e($pu) ?>" target="_blank"><img src="<?= e($pu) ?>" alt=""></a><?php endforeach; ?>
              </div>
            <?php else: ?><span class="no-media">No portfolio</span><?php endif; ?>
          </div>
          <?php if (!empty($a['art_video'])): ?>
            <video src="<?= e($a['art_video']) ?>" controls preload="metadata"></video>
          <?php else: ?><span class="no-media">No video</span><?php endif; ?>

          <div class="acard-foot">
            <div class="acts">
              <?php if (!$a['is_approved']): ?>
                <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="user_id" value="<?= (int)$a['id'] ?>"><button class="btn btn-accent btn-sm" name="action" value="approve">Approve</button></form>
              <?php else: ?>
                <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="user_id" value="<?= (int)$a['id'] ?>"><button class="btn btn-ghost btn-sm" name="action" value="unapprove">Unapprove</button></form>
              <?php endif; ?>
              <?php if ((int)$a['is_banned'] === 1): ?>
                <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="back" value="artists"><input type="hidden" name="user_id" value="<?= (int)$a['id'] ?>"><button class="btn btn-ghost btn-sm" name="action" value="unban"><?= owBi('Lift suspension', 'إلغاء الإيقاف') ?></button></form>
              <?php else: ?>
                <form method="post" action="admin.php" onsubmit="return confirm('Suspend this account? Their works leave the Gallery until you lift it.');"><?= csrfField() ?><input type="hidden" name="back" value="artists"><input type="hidden" name="user_id" value="<?= (int)$a['id'] ?>"><button class="btn btn-danger btn-sm" name="action" value="ban"><?= owBi('Suspend', 'إيقاف') ?></button></form>
              <?php endif; ?>
            </div>
            <form method="post" action="admin.php" class="role-form"><?= csrfField() ?><input type="hidden" name="user_id" value="<?= (int)$a['id'] ?>">
              <select class="role-select" name="role">
                <?php foreach (['collector','artist','sub_admin','admin'] as $r): ?><option value="<?= $r ?>" <?= ($a['role']??'')===$r?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$r)) ?></option><?php endforeach; ?>
              </select>
              <button class="btn btn-ghost btn-sm" name="action" value="set_role">Set Role</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  </div>

  <!-- ===== TAB: COLLECTORS ===== -->
  <div class="tab-panel" id="tab-collectors">
  <div class="card">
    <div class="field adm-search"><label for="s-buyers"><?= owBi('Search buyers (name, email, phone, city)', 'ابحث عن مشترٍ (الاسم، البريد، الهاتف، المدينة)') ?></label>
      <input id="s-buyers" type="search" data-search="#tab-collectors tbody tr" autocomplete="off"></div>
    <?php if (!$collectors): ?>
      <div class="empty">No collector accounts yet.</div>
    <?php else: ?>
      <div style="overflow-x:auto;">
      <table class="table">
        <thead>
          <tr><th>Name</th><th>Email</th><th>Phone</th><th>City</th><th>Dates</th><th>Verified</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($collectors as $c): $on = isOnline($c['last_active'] ?? null); ?>
          <tr>
            <td>
              <strong><span class="online-dot <?= $on ? 'on' : '' ?>" title="<?= $on ? 'Online' : 'Offline' ?>"></span><?= e(owRealName($c['full_name_en']) ?: '—') ?></strong>
              <?php if (owRealName($c['full_name_ar']) !== ''): ?><div class="name-ar" dir="rtl"><?= e($c['full_name_ar']) ?></div><?php endif; ?>
            </td>
            <td><?= e($c['email']) ?></td>
            <td dir="ltr"><?= e($c['phone'] ?: '—') ?></td>
            <td><?= e($c['city'] ?: '—') ?></td>
            <td>
              <div class="mini"><?= owBi('Joined:', 'انضم:') ?> <?= e($c['created_at'] ? date('Y-m-d', strtotime($c['created_at'])) : '—') ?></div>
              <div class="mini">Login: <?= e(fmtDateTime($c['last_login_at'] ?? null)) ?></div>
            </td>
            <td><span class="pill <?= $c['is_verified'] ? 'on' : 'off' ?>"><?= $c['is_verified'] ? 'Verified' : 'Unverified' ?></span></td>
            <td><?php if ((int)$c['is_banned'] === 1): ?><span class="pill ban">Banned</span><?php else: ?><span class="pill on">Active</span><?php endif; ?></td>
            <td>
              <div class="acts">
                <a class="btn btn-secondary btn-sm" href="admin.php?person=<?= (int) $c['id'] ?>"><?= owBi('View requests & favorites', 'الطلبات والمفضلة') ?></a>
                <?php if ((int)$c['is_banned'] === 1): ?>
                  <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="back" value="collectors"><input type="hidden" name="user_id" value="<?= (int)$c['id'] ?>"><button class="btn btn-ghost btn-sm" name="action" value="unban"><?= owBi('Lift suspension', 'إلغاء الإيقاف') ?></button></form>
                <?php else: ?>
                  <form method="post" action="admin.php" onsubmit="return confirm('Suspend this account?');"><?= csrfField() ?><input type="hidden" name="back" value="collectors"><input type="hidden" name="user_id" value="<?= (int)$c['id'] ?>"><button class="btn btn-danger btn-sm" name="action" value="ban"><?= owBi('Suspend', 'إيقاف') ?></button></form>
                <?php endif; ?>
              </div>
              <form method="post" action="admin.php" class="role-form"><?= csrfField() ?><input type="hidden" name="user_id" value="<?= (int)$c['id'] ?>">
                <select class="role-select" name="role">
                  <?php foreach (['collector','artist','sub_admin','admin'] as $r): ?><option value="<?= $r ?>" <?= ($c['role']??'')===$r?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$r)) ?></option><?php endforeach; ?>
                </select>
                <button class="btn btn-ghost btn-sm" name="action" value="set_role">Set</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    <?php endif; ?>
  </div>
  </div>

  <!-- ===== TAB: INQUIRIES ("I'm Interested" from the Gallery) ===== -->
  <div class="tab-panel" id="tab-inquiries">
  <div class="card">
    <?php if (!$inquiries): ?>
      <div class="empty">No inquiries yet. They appear here when a visitor presses "I'm Interested" in the Gallery.</div>
    <?php else: ?>
      <div style="overflow-x:auto;">
      <table class="table">
        <thead>
          <tr><th>Date</th><th>Artwork</th><th>Artist</th><th>Buyer</th><th>Phone</th><th>Email</th><th><?= owBi('Status & private note', 'الحالة وملاحظة خاصة') ?></th></tr>
        </thead>
        <tbody>
        <?php foreach ($inquiries as $q): ?>
          <tr>
            <td class="mini"><?= e(fmtDateTime($q['created_at'] ?? null)) ?></td>
            <td>
              <?php if ($q['title_en'] !== null): ?>
                <strong><?= e($q['title_en']) ?></strong>
                <?php if (!empty($q['title_ar'])): ?><div class="name-ar" dir="rtl"><?= e($q['title_ar']) ?></div><?php endif; ?>
                <div class="mini">#<?= (int) $q['artwork_id'] ?></div>
              <?php else: ?>
                <span class="mini"><?= owBi('Deleted artwork', 'عمل محذوف') ?> #<?= (int) $q['artwork_id'] ?></span>
              <?php endif; ?>
            </td>
            <td><?php if (!empty($q['artist_id'])): ?><a href="admin.php?person=<?= (int) $q['artist_id'] ?>"><?= e($q['artist_en']) ?></a><?php else: ?><?= e($q['artist_en']) ?><?php endif; ?></td>
            <td><strong><?= e($q['buyer_name']) ?></strong><?php if (!empty($q['buyer_id'])): ?><div class="mini"><a href="admin.php?person=<?= (int) $q['buyer_id'] ?>"><?= owBi('Buyer account', 'حساب المشتري') ?></a></div><?php else: ?><div class="mini"><?= owBi('Guest', 'زائر') ?></div><?php endif; ?></td>
            <td dir="ltr"><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $q['buyer_phone'])) ?>"><?= e($q['buyer_phone']) ?></a></td>
            <td><a href="mailto:<?= e($q['buyer_email']) ?>"><?= e($q['buyer_email']) ?></a></td>
            <td class="inq-edit"><?php if (isset($q['status'])): ?>
              <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="inquiry_id" value="<?= (int) $q['id'] ?>">
                <select name="status" aria-label="Status"><?php foreach (OW_INQUIRY_STATUS as $sk => $sl): ?><option value="<?= $sk ?>"<?= $q['status'] === $sk ? ' selected' : '' ?>><?= e($sl[0] . ' · ' . $sl[1]) ?></option><?php endforeach; ?></select>
                <textarea name="admin_notes" rows="2" maxlength="4000" aria-label="Private note" placeholder="Private note · ملاحظة خاصة"><?= e((string) ($q['admin_notes'] ?? '')) ?></textarea>
                <button class="btn btn-dark btn-sm" name="action" value="inquiry_update"><?= owBi('Save', 'حفظ') ?></button>
              </form><?php endif; ?>
              <form method="post" action="admin.php" onsubmit="return confirm(document.documentElement.lang === 'ar' ? 'هل أنت متأكد؟ سيُحذف هذا الطلب نهائياً.' : 'Are you sure? This request will be deleted for good.');"><?= csrfField() ?><input type="hidden" name="inquiry_id" value="<?= (int) $q['id'] ?>">
                <button class="btn btn-danger btn-sm" name="action" value="delete_inquiry"><?= owBi('Delete', 'حذف') ?></button></form></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    <?php endif; ?>
  </div>
  </div>

  <!-- ===== TAB: PENDING ARTWORKS ===== -->
  <div class="tab-panel active" id="tab-pending">
  <div class="card" id="pending">
    <div class="sec-head">
      <div class="sub" style="margin:0;"><?= owBi('Everything here waits for you before visitors see it. Until you approve, visitors see the approved version, or nothing if it is new. The artist gets an email either way.', 'كل ما هنا ينتظر موافقتك قبل أن يراه الزوار. حتى توافق، يرى الزوار النسخة المعتمدة، أو لا شيء إن كان جديداً. يصل الفنانَ بريد في الحالتين.') ?></div>
    </div>
    <?php if (!owApprovalsReady()): ?>
      <div class="mini wc-note"><?= owBi('Changes to live artworks, profiles and articles are not held yet: the database update for approvals has not been run.', 'التعديلات على الأعمال المعروضة والملفات والمقالات لا تُحجز بعد: لم يُشغَّل تحديث قاعدة البيانات الخاص بالموافقات.') ?></div>
    <?php endif; ?>
    <?php if ($waitTotal === 0): ?>
      <div class="empty"><?= owBi('Nothing waiting. All caught up.', 'لا شيء بانتظارك. كل شيء محدّث.') ?></div>
    <?php endif; ?>

    <?php if ($waitArtists): ?>
    <h3 class="wc-h"><?= owBi('New artist accounts', 'حسابات فنانين جديدة') ?> <span class="pill count"><?= count($waitArtists) ?></span></h3>
    <div class="wc-list">
      <?php foreach ($waitArtists as $wa): $wn = owRealName($wa['artist_name']) ?: (owRealName($wa['full_name_en']) ?: '—'); ?>
        <div class="wc-item">
          <div class="wc-top">
            <div><strong><?= e($wn) ?></strong><?php if (owRealName($wa['full_name_ar'])): ?> · <span dir="rtl"><?= e($wa['full_name_ar']) ?></span><?php endif; ?>
              <div class="mini"><span dir="ltr"><?= e($wa['email']) ?></span><?= $wa['city'] ? ' · ' . e($wa['city']) : '' ?> · <?php [$ce, $ca] = owDateBi($wa['created_at']); ?><?= owBi($ce, $ca) ?> · <?= (int) $wa['is_verified'] ? owBi('Email confirmed', 'البريد مؤكد') : owBi('Email not confirmed', 'البريد غير مؤكد') ?></div></div>
            <a class="btn btn-ghost btn-sm" href="admin.php?person=<?= (int) $wa['id'] ?>"><?= owBi('View', 'عرض') ?></a>
          </div>
          <div class="acts">
            <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="user_id" value="<?= (int) $wa['id'] ?>"><button class="btn btn-accent btn-sm" name="action" value="approve_account"><?= owBi('Approve', 'موافقة') ?></button></form>
            <details class="rej-box"><summary class="btn btn-danger btn-sm"><?= owBi('Reject…', 'رفض…') ?></summary>
                <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="user_id" value="<?= (int) $wa['id'] ?>">
                  <div class="field"><label for="ra-<?= (int) $wa['id'] ?>"><?= owBi('Reason (the artist sees it)', 'السبب (يراه الفنان)') ?></label>
                    <textarea id="ra-<?= (int) $wa['id'] ?>" name="reason" required maxlength="500" rows="3"></textarea></div>
                  <button class="btn btn-danger btn-sm" name="action" value="reject_account"><?= owBi('Send to the artist', 'أرسل إلى الفنان') ?></button>
                </form></details>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($waitChanges): ?>
    <h3 class="wc-h"><?= owBi('Changes to live things', 'تعديلات على ما هو ظاهر') ?> <span class="pill count"><?= count($waitChanges) ?></span></h3>
    <div class="wc-list">
      <?php foreach ($waitChanges as $wc): $wt = $wc['target_type']; $cur = $wc['current']; $wid = (int) $wc['id'];
        $kind = ['artwork' => ['Artwork changes', 'تعديلات على عمل'], 'profile' => ['Profile changes', 'تعديلات على ملف فنان'], 'article' => ['Publish an article', 'نشر مقال']][$wt] ?? [$wt, $wt]; ?>
        <div class="wc-item">
          <div class="wc-top">
            <div><span class="pill off"><?= owBi($kind[0], $kind[1]) ?></span>
              <strong><?php if ($wt === 'artwork'): ?><?= owBi((string) ($cur['title_en'] ?? '—'), (string) (($cur['title_ar'] ?? '') ?: ($cur['title_en'] ?? '—'))) ?><?php elseif ($wt === 'article'): ?><?= owBi((string) ($cur['title_en'] ?? '—'), (string) (($cur['title_ar'] ?? '') ?: ($cur['title_en'] ?? '—'))) ?><?php else: ?><?= owBi((string) $wc['artist_en'], (string) $wc['artist_ar']) ?><?php endif; ?></strong>
              <div class="mini"><?= owBi('By', 'من') ?> <?= owBi((string) $wc['artist_en'], (string) $wc['artist_ar']) ?> · <?php [$ce, $ca] = owDateBi($wc['created_at'], true); ?><?= owBi($ce, $ca) ?></div></div>
            <?php if ($wt === 'artwork'): ?><a class="btn btn-ghost btn-sm" href="artwork.php?id=<?= (int) $wc['target_id'] ?>" target="_blank" rel="noopener"><?= owBi('See it live', 'شاهده') ?></a>
            <?php elseif ($wt === 'profile'): ?><a class="btn btn-ghost btn-sm" href="admin.php?person=<?= (int) $wc['target_id'] ?>"><?= owBi('View', 'عرض') ?></a>
            <?php else: ?><a class="btn btn-ghost btn-sm" href="admin_articles.php?edit=<?= (int) $wc['target_id'] ?>"><?= owBi('Open', 'فتح') ?></a><?php endif; ?>
          </div>
          <?php if ($wt === 'article'): ?>
            <?php if (($wc['asker_role'] ?? '') === 'artist'): ?>
            <p class="mini"><?= owBi('An artist wrote this article and asks to publish it. Read it first with Preview. It stays hidden until you approve.', 'كتب فنان هذا المقال ويطلب نشره. اقرأه أولاً عبر «معاينة». يبقى مخفياً حتى توافق.') ?>
              <?php if (!empty($cur['slug'])): ?><a class="btn btn-ghost btn-sm" href="article.php?slug=<?= e(rawurlencode((string) $cur['slug'])) ?>&amp;preview=1" target="_blank" rel="noopener"><?= owBi('Preview', 'معاينة') ?></a><?php endif; ?></p>
            <?php else: ?>
            <p class="mini"><?= owBi('A team member asks to publish this article. It stays a draft until you approve.', 'يطلب أحد أعضاء الفريق نشر هذا المقال. يبقى مسودة حتى توافق.') ?></p>
            <?php endif; ?>
          <?php else: ?>
          <div class="table-wrap"><table class="table wc-table">
            <thead><tr><th><?= owBi('What', 'ماذا') ?></th><th><?= owBi('Now (visitors see)', 'الآن (يراه الزوار)') ?></th><th><?= owBi('Asked for', 'المطلوب') ?></th></tr></thead>
            <tbody>
            <?php foreach ($wc['data'] as $fk => $fv): if (!isset(OW_FIELD_WORDS[$fk])) continue; ?>
              <tr><td class="wc-f"><?= owBi(OW_FIELD_WORDS[$fk][0], OW_FIELD_WORDS[$fk][1]) ?></td><td><span class="wc-lbl"><?= owBi('Now: ', 'الآن: ') ?></span><?= $showVal($fk, $cur[$fk] ?? '') ?></td><td class="wc-new"><span class="wc-lbl"><?= owBi('Asked for: ', 'المطلوب: ') ?></span><?= $showVal($fk, $fv) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
          <?php endif; ?>
          <div class="acts">
            <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="change_id" value="<?= $wid ?>"><button class="btn btn-accent btn-sm" name="action" value="approve_change"><?= owBi('Approve', 'موافقة') ?></button></form>
            <details class="rej-box"><summary class="btn btn-danger btn-sm"><?= owBi('Reject…', 'رفض…') ?></summary>
                <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="change_id" value="<?= $wid ?>">
                  <div class="field"><label for="rc-<?= $wid ?>"><?= owBi('Reason (the artist sees it)', 'السبب (يراه الفنان)') ?></label>
                    <textarea id="rc-<?= $wid ?>" name="reason" required maxlength="500" rows="3"></textarea></div>
                  <button class="btn btn-danger btn-sm" name="action" value="reject_change"><?= owBi('Send to the artist', 'أرسل إلى الفنان') ?></button>
                </form></details>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($pendingArt): ?>
    <h3 class="wc-h"><?= owBi('New artworks', 'أعمال جديدة') ?> <span class="pill count"><?= count($pendingArt) ?></span></h3>
    <?php endif; ?>
    <?php if (!$pendingArt): ?>
    <?php else: ?>
      <div class="art-list">
      <?php foreach ($pendingArt as $art): ?>
        <div class="art-item">
          <div class="art-thumb" style="background-image:url('<?= e($art['image_url']) ?>');"></div>
          <div class="art-meta">
            <strong><?= e($art['title_en'] ?: ($art['title_ar'] ?: 'Untitled')) ?></strong>
            <?php if (!empty($art['title_ar'])): ?><div class="name-ar" dir="rtl"><?= e($art['title_ar']) ?></div><?php endif; ?>
            <div class="art-info">
              <?= e($art['artist_name'] ?: 'Unknown artist') ?> ·
              <?php $tl = $ART_TYPES[(string) $art['type']] ?? null; ?><?= $tl ? owBi($tl['en'], $tl['ar']) : e(ucfirst((string) $art['type'])) ?> ·
              <?= $art['price'] !== null ? e(number_format((float)$art['price'])) . ' ' . owBi('SAR', 'ر.س') : '—' ?>
            </div>
          </div>
          <div class="acts">
            <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="artwork_id" value="<?= (int)$art['id'] ?>"><button class="btn btn-accent btn-sm" name="action" value="approve_art">Approve</button></form>
            <?php if (owHasColumn('artworks', 'rejected_at')): ?>
            <details class="rej-box"><summary class="btn btn-danger btn-sm"><?= owBi('Not approve…', 'عدم الموافقة…') ?></summary>
              <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="artwork_id" value="<?= (int)$art['id'] ?>">
                <div class="field"><label for="rr-<?= (int)$art['id'] ?>"><?= owBi('Reason (the artist sees it)', 'السبب (يراه الفنان)') ?></label>
                  <textarea id="rr-<?= (int)$art['id'] ?>" name="reason" required maxlength="500" rows="3"></textarea></div>
                <button class="btn btn-danger btn-sm" name="action" value="reject_art"><?= owBi('Send to the artist', 'أرسل إلى الفنان') ?></button>
              </form></details>
            <?php else: ?>
            <form method="post" action="admin.php" onsubmit="return confirm('Reject and permanently delete this artwork?');"><?= csrfField() ?><input type="hidden" name="artwork_id" value="<?= (int)$art['id'] ?>"><button class="btn btn-danger btn-sm" name="action" value="reject_art">Reject</button></form>
            <?php endif; ?>
          </div>
          <?php if (owCertReady()): $cm = owCertMissing($art); ?>
          <div class="art-cert">
            <?php if (!empty($art['declared_at'])): [$dE, $dA] = owDateBi((string) $art['declared_at'], true); ?>
              <span>✓ <?= owBi('Artist’s declaration: “my original work, made by my own hand”, ' . $dE, 'إقرار الفنان: «عملي الأصلي، صنع يدي»، ' . $dA) ?></span>
            <?php endif; ?>
            <?php if ((string) ($art['medium'] ?? '') !== ''): [$sE, $sA] = owCertSize($art['width_cm'], $art['height_cm']); [$eE, $eA] = owCertEdition($art['edition_type'], $art['edition_number'], $art['edition_size']); ?>
              <span><span dir="auto"><?= e((string) $art['medium']) ?></span> · <?= owBi($sE, $sA) ?> · <?= (int) $art['year_made'] ?> · <?= owBi($eE, $eA) ?></span>
            <?php endif; ?>
            <?php if ($cm): [$mE, $mA] = owCertMissingWords($cm); ?>
              <span class="warn">⚠ <?= owBi('No certificate until the artist adds: ' . $mE . '. You can still approve it.', 'لن تصدر الشهادة حتى يضيف الفنان: ' . $mA . '. يمكنك الموافقة الآن.') ?></span>
            <?php else: ?>
              <span><?= owBi('A Certificate of Authenticity is made when you approve.', 'تصدر شهادة الأصالة عند موافقتك.') ?></span>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          <div class="art-proof"><?= owProofPanelHtml($pendingStages[(int)$art['id']] ?? [], $pendingColors[(int)$art['id']] ?? [], (string)$art['type'], $ART_TYPES) ?></div>
        </div>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  </div>

  <!-- ===== TAB: CERTIFICATES OF AUTHENTICITY ===== -->
  <div class="tab-panel" id="tab-certs">
  <div class="card" id="certs">
    <div class="sub" style="margin:0 0 var(--space-sm)"><?= owBi('Every approved artwork gets a certificate with its own number. Anyone can check a number at oweili.com/verify. A cancelled certificate shows as “Cancelled” there. Certificates are never deleted.', 'يحصل كل عمل معتمد على شهادة برقم خاص. يستطيع أي شخص التحقق من الرقم في oweili.com/verify. الشهادة الملغاة تظهر هناك «ملغاة». لا تُحذف الشهادات أبداً.') ?></div>
    <?php if (!owCertReady()): ?>
      <div class="mini wc-note"><?= owBi('Certificates are not switched on yet: the database update for certificates has not been run.', 'الشهادات غير مفعّلة بعد: لم يُشغَّل تحديث قاعدة البيانات الخاص بها.') ?></div>
    <?php endif; ?>
    <?php if ($noCertYet): ?>
      <h3 class="wc-h"><?= owBi('Approved, still without a certificate', 'معتمدة وبلا شهادة بعد') ?> <span class="pill count"><?= count($noCertYet) ?></span></h3>
      <div class="table-wrap"><table class="table"><thead><tr><th><?= owBi('Artwork', 'العمل') ?></th><th><?= owBi('Artist', 'الفنان') ?></th><th><?= owBi('Missing', 'الناقص') ?></th><th></th></tr></thead><tbody>
      <?php foreach ($noCertYet as $nc): $ncm = owCertMissing($nc); [$nE, $nA] = owCertMissingWords($ncm); ?>
        <tr><td><?= owBi((string) $nc['title_en'], (string) ($nc['title_ar'] ?: $nc['title_en'])) ?></td><td><?= owBi((string) $nc['artist_en'], (string) $nc['artist_ar']) ?></td>
          <td class="mini"><?= $ncm ? owBi($nE, $nA) : owBi('Nothing: ready', 'لا شيء: جاهز') ?></td>
          <td><?php if (!$ncm): ?><form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="artwork_id" value="<?= (int) $nc['id'] ?>"><button class="btn btn-accent btn-sm" name="action" value="cert_issue"><?= owBi('Make certificate', 'أصدر الشهادة') ?></button></form><?php endif; ?></td></tr>
      <?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
    <div class="field" style="max-width:420px;margin-top:var(--space-sm)"><label for="s-certs"><?= owBi('Search (number, artwork, artist)', 'بحث (الرقم، العمل، الفنان)') ?></label>
      <input id="s-certs" type="search" data-search="#tab-certs .cert-rows tr" autocomplete="off"></div>
    <?php if (!$allCerts): ?><div class="empty"><?= owBi('No certificates yet. They are made when you approve artworks.', 'لا شهادات بعد. تصدر عند موافقتك على الأعمال.') ?></div><?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th><?= owBi('Number', 'الرقم') ?></th><th><?= owBi('Artwork', 'العمل') ?></th><th><?= owBi('Artist', 'الفنان') ?></th><th><?= owBi('Issued', 'صدرت') ?></th><th><?= owBi('Status', 'الحالة') ?></th><th><?= owBi('Actions', 'إجراءات') ?></th></tr></thead>
      <tbody class="cert-rows">
      <?php foreach ($allCerts as $c): $cs = $c['snap']; [$ciE, $ciA] = owDateBi((string) $c['issued_at']); $ok = $c['status'] === 'valid'; ?>
        <tr>
          <td dir="ltr"><b><?= e($c['cert_no']) ?></b></td>
          <td><?= owBi((string) ($cs['title_en'] ?? ''), (string) ($cs['title_ar'] ?? '')) ?></td>
          <td><a href="admin.php?person=<?= (int) $c['artist_id'] ?>"><?= owBi((string) ($cs['artist_en'] ?? ''), (string) ($cs['artist_ar'] ?? '')) ?></a></td>
          <td class="mini"><?= owBi($ciE, $ciA) ?></td>
          <td><span class="pill <?= $ok ? 'on' : 'ban' ?>"><?= $ok ? owBi('Valid', 'سارية') : owBi('Cancelled', 'ملغاة') ?></span><?php if (!$ok): ?><div class="mini"><?= e((string) $c['cancel_reason']) ?></div><?php endif; ?></td>
          <td><div class="acts">
            <a class="btn btn-ghost btn-sm" href="certificate.php?no=<?= e(rawurlencode($c['cert_no'])) ?>"><?= owBi('View', 'عرض') ?></a>
            <?php if ($ok): ?>
            <details class="rej-box"><summary class="btn btn-secondary btn-sm"><?= trim((string) ($c['owner_name'] ?? '')) !== '' ? owBi('Owner…', 'المالك…') : owBi('Add owner…', 'أضف المالك…') ?></summary>
              <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="cert_id" value="<?= (int) $c['id'] ?>">
                <div class="field"><label for="co-<?= (int) $c['id'] ?>"><?= owBi('Owner’s name (shown on the certificate, never on the public verify page). A new name counts as a new owner.', 'اسم المالك (يظهر على الشهادة، ولا يظهر في صفحة التحقق العامة). الاسم الجديد يُحسب مالكاً جديداً.') ?></label>
                  <input id="co-<?= (int) $c['id'] ?>" name="owner_name" maxlength="200" required value="<?= e((string) ($c['owner_name'] ?? '')) ?>"></div>
                <button class="btn btn-accent btn-sm" name="action" value="cert_owner"><?= owBi('Save owner', 'احفظ المالك') ?></button>
              </form></details>
            <details class="rej-box"><summary class="btn btn-danger btn-sm"><?= owBi('Cancel…', 'إلغاء…') ?></summary>
              <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="cert_id" value="<?= (int) $c['id'] ?>">
                <div class="field"><label for="cc-<?= (int) $c['id'] ?>"><?= owBi('Reason (kept on record; the artist sees it)', 'السبب (يُحفظ في السجل ويراه الفنان)') ?></label>
                  <textarea id="cc-<?= (int) $c['id'] ?>" name="reason" required maxlength="500" rows="3"></textarea></div>
                <button class="btn btn-danger btn-sm" name="action" value="cert_cancel"><?= owBi('Cancel this certificate', 'ألغِ هذه الشهادة') ?></button>
              </form></details>
            <?php endif; ?>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table></div>
    <?php endif; ?>
  </div>
  </div>

  <!-- ===== TAB: PHOTO CHANGES (a live artwork got a new photo) ===== -->
  <div class="tab-panel" id="tab-photos">
  <div class="card" id="photos">
    <div class="sub" style="margin:0 0 var(--space-sm)"><?= owBi('An artist changed the photo of an artwork that is live in the Gallery. It stays live. Compare the photos, then press “Looks fine”, or hide the artwork and tell the artist why.', 'غيّر فنان صورة عمل معروض في المعرض. يبقى العمل معروضاً. قارن الصورتين ثم اضغط «تبدو جيدة»، أو أخفِ العمل وأخبر الفنان بالسبب.') ?></div>
    <?php if (!$photoChanges): ?><div class="empty"><?= owBi('No photo changes waiting. All caught up.', 'لا تغييرات صور بانتظارك.') ?></div><?php else: ?>
    <div class="pc-list">
    <?php foreach ($photoChanges as $pc): $pd = json_decode((string) $pc['details'], true) ?: []; $aid = (int) $pc['artwork_id'];
      $oldSrc = owImgSrc((string) ($pd['old'] ?? '')); $newSrc = owImgSrc((string) $pc['image_url']); ?>
      <div class="pc-item">
        <div class="pc-head"><strong><?= e($pc['title_en'] ?: $pc['title_ar']) ?></strong> <span class="name-ar" dir="rtl"><?= e($pc['title_ar']) ?></span>
          <div class="mini"><a href="admin.php?person=<?= (int) $pc['artist_id'] ?>"><?= e($pc['artist_en']) ?></a> · <?= owBi('changed', 'تغيّرت') ?> <?= e(fmtDateTime($pc['created_at'])) ?> · <a href="artwork.php?id=<?= $aid ?>" target="_blank" rel="noopener"><?= owBi('See it live', 'شاهده معروضاً') ?> ↗</a></div></div>
        <div class="pc-photos">
          <figure><figcaption><?= owBi('Before', 'قبل') ?></figcaption><?php if ($oldSrc !== ''): ?><a href="<?= e($oldSrc) ?>" target="_blank" rel="noopener"><img loading="lazy" src="<?= e($oldSrc) ?>" alt="Before · قبل"></a><?php else: ?><div class="empty">—</div><?php endif; ?></figure>
          <figure><figcaption><?= owBi('Now', 'الآن') ?></figcaption><?php if ($newSrc !== ''): ?><a href="<?= e($newSrc) ?>" target="_blank" rel="noopener"><img loading="lazy" src="<?= e($newSrc) ?>" alt="Now · الآن"></a><?php else: ?><div class="empty">—</div><?php endif; ?></figure>
        </div>
        <div class="acts">
          <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="artwork_id" value="<?= $aid ?>"><button class="btn btn-accent btn-sm" name="action" value="photo_ok"><?= owBi('Looks fine', 'تبدو جيدة') ?></button></form>
          <details class="rej-box"><summary class="btn btn-danger btn-sm"><?= owBi('Hide artwork…', 'إخفاء العمل…') ?></summary>
            <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="artwork_id" value="<?= $aid ?>"><input type="hidden" name="back" value="photos">
              <div class="field"><label for="ph-<?= $aid ?>"><?= owBi('Reason (the artist sees it)', 'السبب (يراه الفنان)') ?></label><textarea id="ph-<?= $aid ?>" name="reason" required maxlength="500" rows="2"></textarea></div>
              <button class="btn btn-danger btn-sm" name="action" value="reject_art"><?= owBi('Hide and tell the artist', 'أخفِه وأبلغ الفنان') ?></button></form></details>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  </div>

  <!-- ===== TAB: ALL ARTWORKS ===== -->
  <!-- ===== TAB: AUCTIONS (waiting list for the first auction) ===== -->
  <div class="tab-panel" id="tab-auctions">
  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:var(--space-sm);flex-wrap:wrap">
      <div><h2 style="margin:0"><?= owBi('Auctions waiting list', 'قائمة انتظار المزاد') ?></h2>
        <p class="mini" style="margin:4px 0 0"><?= owBi(count($auList) . ' people are waiting for the first auction. This is the real number shown on the Auctions page.', count($auList) . ' شخص بانتظار أول مزاد. هذا هو الرقم الحقيقي الظاهر في صفحة المزاد.') ?></p></div>
      <span style="display:flex;gap:var(--space-xs);flex-wrap:wrap"><a class="btn btn-ghost btn-sm" href="auctions.php" target="_blank" rel="noopener"><?= owBi('Open the page', 'افتح الصفحة') ?> ↗</a>
        <?php if ($auList): ?><a class="btn btn-accent btn-sm" href="admin.php?auctions_csv=1"><?= owBi('Export to CSV', 'تصدير CSV') ?></a><?php endif; ?></span>
    </div>
    <?php if (!owAuctionReady()): ?><p class="mini"><?= owBi('The waiting-list table is not set up yet (auctions_update.sql).', 'جدول قائمة الانتظار غير مُعدّ بعد (auctions_update.sql).') ?></p>
    <?php elseif (!$auList): ?><p class="mini"><?= owBi('No one has signed up yet.', 'لم يسجّل أحد بعد.') ?></p>
    <?php else: ?>
    <div class="table-wrap" style="margin-top:var(--space-sm)"><table class="table">
      <thead><tr><th><?= owBi('Signed up', 'تاريخ التسجيل') ?></th><th><?= owBi('Email', 'البريد') ?></th><th><?= owBi('Name', 'الاسم') ?></th><th><?= owBi('Language', 'اللغة') ?></th></tr></thead>
      <tbody><?php foreach ($auList as $au): [$ae, $aa] = owDateBi($au['created_at'], true); ?>
        <tr><td><?= owBi($ae, $aa) ?></td><td dir="ltr"><a href="mailto:<?= e((string) $au['email']) ?>"><?= e((string) $au['email']) ?></a></td>
          <td dir="auto"><?= $au['name'] !== '' ? e((string) $au['name']) : '—' ?><?php if ($au['user_id']): ?> · <a href="admin.php?person=<?= (int) $au['user_id'] ?>"><?= owBi('account', 'الحساب') ?></a><?php endif; ?></td>
          <td><?= $au['lang'] === 'ar' ? 'العربية' : 'English' ?></td></tr>
      <?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
    <p class="mini" style="margin-top:var(--space-sm)"><?= owBi('People who unsubscribe are deleted from the list straight away.', 'من يلغي اشتراكه يُحذف من القائمة فوراً.') ?></p>
  </div>
  </div>

  <div class="tab-panel" id="tab-artworks">
  <div class="card">
    <div class="field adm-search"><label for="s-art"><?= owBi('Search artworks (title or artist)', 'ابحث عن عمل (العنوان أو الفنان)') ?></label>
      <input id="s-art" type="search" data-search="#tab-artworks tbody tr" autocomplete="off"></div>
    <?php if (!$allArt): ?><div class="empty"><?= owBi('No artworks yet.', 'لا أعمال بعد.') ?></div><?php else: ?>
    <div style="overflow-x:auto;"><table class="table">
      <thead><tr><th>Artwork</th><th>Artist</th><th>Price</th><th>Status</th><th><?= owBi('Views', 'المشاهدات') ?></th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($allArt as $w): $stw = isset($ART_STATE[$w['state']]) ? $w['state'] : 'pending'; ?>
        <tr>
          <td><strong><?= e($w['title_en'] ?: $w['title_ar']) ?></strong><?php if (!empty($w['title_ar'])): ?><div class="name-ar" dir="rtl"><?= e($w['title_ar']) ?></div><?php endif; ?><div class="mini">#<?= (int) $w['id'] ?> · <?= e(date('Y-m-d', strtotime($w['created_at']))) ?></div></td>
          <td><a href="admin.php?person=<?= (int) $w['artist_id'] ?>"><?= e($w['artist_en']) ?></a></td>
          <td><?= number_format((float) $w['price']) ?> <?= owBi('SAR', 'ر.س') ?></td>
          <td><span class="pill <?= $stw === 'approved' ? 'on' : ($stw === 'rejected' ? 'ban' : 'off') ?>"><?= owBi($ART_STATE[$stw][0], $ART_STATE[$stw][1]) ?></span><?php if ($stw === 'rejected' && $w['reason']): ?><div class="mini"><?= e($w['reason']) ?></div><?php endif; ?></td>
          <td><?= (int) $w['views'] ?></td>
          <td><div class="acts">
            <?php if ($stw === 'sold'): ?>
              <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="artwork_id" value="<?= (int) $w['id'] ?>"><button class="btn btn-ghost btn-sm" name="action" value="unsell_art"><?= owBi('Back to available', 'أعده متاحاً') ?></button></form>
            <?php else: ?>
              <details class="rej-box"><summary class="btn btn-dark btn-sm"><?= owBi('Mark as sold…', 'علّمه كمباع…') ?></summary>
                <form method="post" action="admin.php" onsubmit="return confirm(document.documentElement.dir==='rtl'?'تعليم هذا العمل كمباع؟ سيخرج من المعرض.':'Mark this artwork as sold? It leaves the Gallery.');"><?= csrfField() ?><input type="hidden" name="artwork_id" value="<?= (int) $w['id'] ?>">
                  <div class="field"><label for="bn-<?= (int) $w['id'] ?>"><?= owBi('Buyer’s name for the certificate (optional)', 'اسم المشتري للشهادة (اختياري)') ?></label><input id="bn-<?= (int) $w['id'] ?>" name="buyer_name" maxlength="200" autocomplete="off"></div>
                  <button class="btn btn-dark btn-sm" name="action" value="sell_art"><?= owBi('Mark as sold', 'علّمه كمباع') ?></button></form></details>
            <?php endif; ?>
            <?php if ($stw === 'approved'): ?>
            <details class="rej-box"><summary class="btn btn-ghost btn-sm"><?= owBi('Take down…', 'إزالة من المعرض…') ?></summary>
              <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="artwork_id" value="<?= (int) $w['id'] ?>"><input type="hidden" name="back" value="artworks">
                <div class="field"><label for="rt-<?= (int) $w['id'] ?>"><?= owBi('Reason (the artist sees it)', 'السبب (يراه الفنان)') ?></label><textarea id="rt-<?= (int) $w['id'] ?>" name="reason" required maxlength="500" rows="2"></textarea></div>
                <button class="btn btn-danger btn-sm" name="action" value="reject_art"><?= owBi('Take down', 'إزالة') ?></button></form></details>
            <?php elseif ($stw === 'rejected' || $stw === 'pending'): ?>
              <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="artwork_id" value="<?= (int) $w['id'] ?>"><input type="hidden" name="back" value="artworks"><button class="btn btn-accent btn-sm" name="action" value="approve_art">Approve</button></form>
            <?php endif; ?>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table></div>
    <?php endif; ?>
  </div>
  </div>

  <!-- Art Styles, Events and Training tabs were removed (31 Oct 2026): nothing on the site reads
       those old tables any more. Art styles come from assets/js/art_styles.js, events are
       Art Events articles (admin_articles.php), institutes and courses are in admin_institutes.php -->

  <script>
  /* search boxes: hide rows that do not match */
  document.querySelectorAll('input[data-search]').forEach(function(inp){
    inp.addEventListener('input', function(){
      var q = inp.value.trim().toLowerCase();
      document.querySelectorAll(inp.dataset.search).forEach(function(row){
        var hay = (row.querySelector('.adm-hay') || row).textContent.toLowerCase();
        row.style.display = (!q || hay.indexOf(q) >= 0) ? '' : 'none';
      });
    });
  });
  /* Tabs (owner, 9 Nov 2026): each panel belongs to one main tab; some main tabs have small sub-tabs */
  var OW_GROUP={artists:'g-people',collectors:'g-people',artworks:'g-art',photos:'g-art',certs:'g-art',inquiries:'g-req',auctions:'g-req'};
  function showTab(name){
    if(!document.getElementById('tab-'+name)) name='pending';
    var g=OW_GROUP[name]||name;
    document.querySelectorAll('.tab-panel').forEach(function(p){p.classList.toggle('active',p.id==='tab-'+name);});
    document.querySelectorAll('.adm-main .tab-btn').forEach(function(b){b.classList.toggle('active',b.dataset.tab===g);});
    document.querySelectorAll('.adm-sub').forEach(function(s){s.classList.toggle('show',s.dataset.group===g);});
    document.querySelectorAll('.adm-sub .sub-btn').forEach(function(b){b.classList.toggle('on',b.dataset.tab===name);});
    try{history.replaceState(null,'','#'+name);}catch(e){}
  }
  function statRange(d,btn){
    document.querySelectorAll('.stat-block').forEach(function(b){b.hidden=b.dataset.days!=String(d);});
    document.querySelectorAll('.stat-range .sub-btn').forEach(function(b){b.classList.toggle('on',b===btn);});
  }
  // Restore the tab from the address (e.g. after approving an artwork); Waiting for approval is the first tab
  (function(){ showTab((location.hash||'#pending').replace('#','')); })();
  window.addEventListener('hashchange',function(){ var h=(location.hash||'').replace('#',''); if(h) showTab(h); });
  </script>

<?php endif; ?>
</div>
<script>
/* Bilingual admin — swaps known EN strings to AR (and back) by walking text
   nodes, so the whole panel translates without per-element markup. */
const ADICT = {
  "Admin Dashboard":"لوحة الإدارة",
  "Manage artists, buyers, artworks and requests.":"إدارة الفنانين والمشترين والأعمال والطلبات.",
  "Log out":"تسجيل الخروج",
  "Articles":"المقالات",
  "Photo check":"فحص الصور",
  "Email check":"فحص البريد",
  "Error log":"سجل الأخطاء",
  "Inquiries":"طلبات الاهتمام","Artwork":"العمل الفني","Buyer":"المشتري",
  "No inquiries yet. They appear here when a visitor presses \"I'm Interested\" in the Gallery.":"لا توجد طلبات بعد. تظهر هنا عندما يضغط زائر «أنا مهتم» في المعرض.",
  "Admin Login":"دخول الإدارة","Password":"كلمة المرور","Sign In":"تسجيل الدخول","Admin password is not set up yet. Add ADMIN_HASH to config.php.":"كلمة مرور الإدارة غير مُعدّة بعد. أضف ADMIN_HASH إلى config.php.","Incorrect password.":"كلمة مرور غير صحيحة.","Too many attempts. Please wait 10 minutes.":"محاولات كثيرة جداً. يرجى الانتظار 10 دقائق.",
  "Artists":"الفنانون","Collectors":"المقتنون","Pending Artworks":"الأعمال المعلّقة","Art Styles":"أساليب الرسم",
  "Events":"الأحداث","Training":"التدريب",
  "Add New Event":"إضافة حدث جديد","Add Event":"إضافة الحدث",
  "Add New Training":"إضافة تدريب جديد","Add Training":"إضافة التدريب",
  "Entry":"الدخول","Free":"مجاني","Paid":"مدفوع","Price (SAR)":"السعر (ريال)",
  "Contact / Details Link":"رابط التواصل / التفاصيل",
  "No events yet. Add your first one above.":"لا توجد أحداث بعد. أضف أول حدث بالأعلى.",
  "No training listed yet. Add your first one above.":"لا توجد دورات بعد. أضف أول دورة بالأعلى.",
  "Delete":"حذف",
  "Online Now":"متصل الآن","Portfolio":"معرض الأعمال","Dates":"التواريخ","None":"لا يوجد","Set":"تعيين","Set Role":"تعيين الدور",
  "No portfolio":"لا يوجد معرض","No video":"لا يوجد فيديو","No photo":"لا توجد صورة",
  "Collector":"مقتني","Sub admin":"مشرف فرعي","Admin":"مدير",
  "Add New Style":"إضافة أسلوب جديد","Add Style":"إضافة الأسلوب",
  "Title (English)":"العنوان (بالإنجليزية)","Title (Arabic)":"العنوان (بالعربية)",
  "Description (English)":"الوصف (بالإنجليزية)","Description (Arabic)":"الوصف (بالعربية)",
  "Tag":"التصنيف","Image URL":"رابط الصورة","Save Changes":"حفظ التغييرات","Cancel":"إلغاء",
  "No styles yet. Add your first one above.":"لا توجد أساليب بعد. أضف أول أسلوب بالأعلى.",
  "Traditional":"تقليدي","Digital":"رقمي","Mixed Media":"وسائط مختلطة",
  "Artist":"الفنان","Contact":"التواصل","City":"المدينة","Verification":"التحقق",
  "Verified":"موثّق","Unverified":"غير موثّق","Approved":"معتمد","Pending":"معلّق","Status":"الحالة","Actions":"إجراءات",
  "Name":"الاسم","Email":"البريد الإلكتروني","Phone":"الهاتف","Date":"التاريخ","Price":"السعر","Login:":"آخر دخول:","Online":"متصل","Offline":"غير متصل",
  "No artist accounts yet.":"لا توجد حسابات فنانين بعد.","No collector accounts yet.":"لا يوجد مقتنون بعد.",
  "Nothing pending — all caught up. 🎉":"لا يوجد معلّق — كل شيء محدّث. 🎉",
  "New uploads waiting for approval before they appear in the gallery.":"أعمال جديدة بانتظار الموافقة قبل ظهورها في المعرض.",
  "Approve":"موافقة","Unapprove":"إلغاء الموافقة","Ban":"حظر","Unban":"رفع الحظر","Reject":"رفض",
  "Yes":"نعم","No":"لا","Banned":"محظور","Active":"نشط",
  "No photo":"لا صورة","No video":"لا فيديو","Open video ↗":"فتح الفيديو ↗"
};
const AREV = {}; Object.keys(ADICT).forEach(k=>AREV[ADICT[k]]=k);
let AL='en';
function walkTranslate(root, toAr){
  const map = toAr ? ADICT : AREV;
  const walker=document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null);
  const nodes=[]; while(walker.nextNode()) nodes.push(walker.currentNode);
  nodes.forEach(n=>{
    const key=n.nodeValue.trim();
    if(key && map[key]!==undefined){ n.nodeValue=n.nodeValue.replace(key, map[key]); }
  });
}
function applyLang(l){
  const toAr = l==='ar';
  document.documentElement.setAttribute('dir', toAr?'rtl':'ltr');
  document.documentElement.lang=l;
  if(l!==AL){ walkTranslate(document.querySelector('.wrap')||document.body, toAr); AL=l; }
  const lb=document.getElementById('lb'); if(lb) lb.textContent = toAr ? 'English':'العربية';
}
function tglLang(){ const next=AL==='en'?'ar':'en'; try{localStorage.setItem('lang',next);}catch(e){} applyLang(next); }
try{var _s=localStorage.getItem('lang'); if(_s==='ar') applyLang('ar');}catch(e){}
</script>
</body>
</html>
