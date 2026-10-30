<?php
require_once 'config.php';
require_once __DIR__ . '/artwork_lib.php';
require_once __DIR__ . '/mail_lib.php';
require_once __DIR__ . '/inquiry_lib.php';
require_once __DIR__ . '/admin_lib.php';

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
    } elseif (verifyCsrf($_POST['csrf_token'] ?? null) && password_verify((string) $_POST['admin_pass'], $adminHash)) {
        session_regenerate_id(true);
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_sig'] = $adminSig;   // ties the login to the current password
        owLog('admin_login', null, null, '', ['type' => 'admin', 'id' => null]);
    } else {
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
        header('Location: admin.php' . (empty($_SESSION['admin_err']) ? '?ok=1' : '') . '#' . (($_POST['back'] ?? '') === 'artworks' ? 'artworks' : 'pending'));
        exit;
    }

    /* --- Mark an artwork as sold / back to available --- */
    if ($action === 'sell_art' || $action === 'unsell_art') {
        owSetArtworkSale((int) ($_POST['artwork_id'] ?? 0), $action === 'sell_art');
        header('Location: admin.php?ok=1#artworks');
        exit;
    }

    /* --- "I'm Interested" request: status + private note --- */
    if ($action === 'inquiry_update') {
        owSetInquiry((int) ($_POST['inquiry_id'] ?? 0), (string) ($_POST['status'] ?? ''), isset($_POST['admin_notes']) ? (string) $_POST['admin_notes'] : null);
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
        "SELECT a.id, a.title_en, a.title_ar, a.price, a.type, a.image_url, a.created_at,
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
    return $v ? date('Y-m-d H:i', strtotime($v)) : 'N/A';
}

/* ---- Load "I'm Interested" inquiries, newest first (only when authed) ---- */
$inquiries = [];
if ($isAdmin) {
    try { $inquiries = owListInquiries(); } catch (\Throwable $e) { error_log('admin inquiries: ' . $e->getMessage()); }
}


/* ---- Overview numbers and every artwork (with its status) ---- */
$overview = $isAdmin ? owAdminOverview() : [];
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
$ART_STATE = ['draft' => ['Draft', 'مسودة'], 'pending' => ['In review', 'قيد المراجعة'], 'approved' => ['In the Gallery', 'في المعرض'],
              'rejected' => ['Not approved', 'لم تتم الموافقة'], 'sold' => ['Sold', 'مباع']];
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin · Oweili</title>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
.ov-grid{grid-template-columns:repeat(auto-fit,minmax(150px,1fr));}
.adm-search{max-width:420px;margin-bottom:var(--space-sm);}
.rej-box{display:inline-block;}
.rej-box summary{list-style:none;cursor:pointer;}
.rej-box summary::-webkit-details-marker{display:none;}
.rej-box[open]{display:block;width:100%;margin-top:var(--space-xs);padding:var(--space-sm);border:1px solid var(--color-border);border-radius:var(--radius-input);background:var(--color-surface);}
.rej-box textarea,.inq-edit textarea{width:100%;min-height:0;}
.inq-edit form{display:grid;gap:6px;min-width:220px;}
.inq-edit select{min-height:40px;}
.acard-head{flex-wrap:wrap;}
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
<div class="wrap">
<?php if (!$isAdmin): ?>

  <div style="max-width:380px;margin:6vh auto 0;text-align:right;"><button class="btn btn-ghost btn-sm lang-btn" id="lb" onclick="tglLang()">العربية</button> <?php owThemeButton('btn btn-ghost btn-sm'); ?></div>
  <form class="card login-card" method="post" action="admin.php">
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
    <div class="top-actions">
      <button class="btn btn-ghost btn-sm lang-btn" id="lb" onclick="tglLang()">العربية</button> <?php owThemeButton('btn btn-ghost btn-sm'); ?>
      <a class="btn btn-dark btn-sm" href="admin_articles.php">Articles</a>
      <a class="btn btn-dark btn-sm" href="admin_institutes.php"><?= owBi('Institutes', 'المعاهد') ?></a>
      <a class="btn btn-ghost btn-sm" href="admin.php?settings=1"><?= owBi('Settings', 'الإعدادات') ?></a>
      <a class="btn btn-ghost btn-sm" href="admin.php?photo_check=1">Photo check</a>
      <a class="btn btn-ghost btn-sm" href="admin.php?mail_check=1">Email check</a>
      <a class="btn btn-ghost btn-sm" href="admin.php?error_log=1">Error log</a>
      <a class="btn btn-ghost btn-sm" href="admin.php?logout=1">Log out</a>
    </div>
  </div>
  <div class="stat-grid ov-grid">
    <div class="stat-card"><span class="stat-num"><?= (int) $overview['artists'] ?></span><span class="stat-lbl"><?= owBi('Artists', 'الفنانون') ?></span></div>
    <div class="stat-card"><span class="stat-num"><?= (int) $overview['buyers'] ?></span><span class="stat-lbl"><?= owBi('Buyers', 'المشترون') ?></span></div>
    <div class="stat-card"><span class="stat-num"><?= (int) $overview['artworks'] ?></span><span class="stat-lbl"><?= owBi('Artworks (' . (int) $overview['in_gallery'] . ' in the Gallery)', 'الأعمال (' . (int) $overview['in_gallery'] . ' في المعرض)') ?></span></div>
    <div class="stat-card"><span class="stat-num"><?= (int) $overview['inquiries'] ?></span><span class="stat-lbl"><?= owBi('Interest requests (' . (int) $overview['new_inq'] . ' new)', 'طلبات الاهتمام (' . (int) $overview['new_inq'] . ' جديد)') ?></span></div>
    <div class="stat-card"><span class="stat-num"><?= (int) $overview['week'] ?></span><span class="stat-lbl"><?= owBi('New sign-ups this week', 'تسجيلات هذا الأسبوع') ?></span></div>
    <div class="stat-card"><span class="stat-num"><?= count($pendingArt) ?></span><span class="stat-lbl"><?= owBi('Waiting for review', 'بانتظار المراجعة') ?></span></div>
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

  <!-- TABS -->
  <div class="tabs">
    <button class="tab tab-btn active" data-tab="artists" onclick="showTab('artists',this)">Artists <span class="pill count"><?= count($artists) ?></span></button>
    <button class="tab tab-btn" data-tab="collectors" onclick="showTab('collectors',this)"><?= owBi('Buyers', 'المشترون') ?> <span class="pill count"><?= count($collectors) ?></span></button>
    <button class="tab tab-btn" data-tab="pending" onclick="showTab('pending',this)">Pending Artworks <span class="pill count"><?= count($pendingArt) ?></span></button>
    <button class="tab tab-btn" data-tab="artworks" onclick="showTab('artworks',this)"><?= owBi('All artworks', 'كل الأعمال') ?> <span class="pill count"><?= count($allArt) ?></span></button>
    <a class="tab" href="admin_articles.php"><?= owBi('Articles & events', 'المقالات والملتقيات') ?></a>
    <a class="tab" href="admin_institutes.php"><?= owBi('Institutes', 'المعاهد') ?></a>
    <button class="tab tab-btn" data-tab="inquiries" onclick="showTab('inquiries',this)">Inquiries <span class="pill count"><?= count($inquiries) ?></span></button>
  </div>

  <!-- ===== TAB: ARTISTS ===== -->
  <div class="tab-panel active" id="tab-artists">
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
              <span class="acard-photo"><?= e(mb_strtoupper(mb_substr($a['full_name_en'] ?: 'A',0,1))) ?></span>
            <?php endif; ?>
            <div class="acard-id">
              <div class="nm"><span class="online-dot <?= $on ? 'on' : '' ?>" title="<?= $on ? 'Online' : 'Offline' ?>"></span><?= e($a['full_name_en'] ?: 'N/A') ?></div>
              <?php if (!empty($a['artist_name'])): ?><div class="an"><?= e($a['artist_name']) ?></div><?php endif; ?>
              <?php if (!empty($a['full_name_ar'])): ?><div class="an" dir="rtl"><?= e($a['full_name_ar']) ?></div><?php endif; ?>
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
            <div dir="ltr">📞 <?= e($a['phone'] ?: 'N/A') ?></div>
            <div>📍 <?= e($a['city'] ?: 'N/A') ?></div>
            <div><b>Reg:</b> <?= e($a['created_at'] ? date('Y-m-d', strtotime($a['created_at'])) : 'N/A') ?> · <b>Login:</b> <?= e(fmtDateTime($a['last_login_at'] ?? null)) ?></div>
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
              <strong><span class="online-dot <?= $on ? 'on' : '' ?>" title="<?= $on ? 'Online' : 'Offline' ?>"></span><?= e($c['full_name_en'] ?: 'N/A') ?></strong>
              <?php if (!empty($c['full_name_ar'])): ?><div class="name-ar" dir="rtl"><?= e($c['full_name_ar']) ?></div><?php endif; ?>
            </td>
            <td><?= e($c['email']) ?></td>
            <td dir="ltr"><?= e($c['phone'] ?: 'N/A') ?></td>
            <td><?= e($c['city'] ?: 'N/A') ?></td>
            <td>
              <div class="mini">Reg: <?= e($c['created_at'] ? date('Y-m-d', strtotime($c['created_at'])) : 'N/A') ?></div>
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
                <span class="mini">Deleted artwork #<?= (int) $q['artwork_id'] ?></span>
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
              </form><?php endif; ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    <?php endif; ?>
  </div>
  </div>

  <!-- ===== TAB: PENDING ARTWORKS ===== -->
  <div class="tab-panel" id="tab-pending">
  <div class="card" id="pending">
    <div class="sec-head">
      <div class="sub" style="margin:0;">New uploads waiting for approval before they appear in the gallery.</div>
    </div>
    <?php if (!$pendingArt): ?>
      <div class="empty">Nothing pending — all caught up. 🎉</div>
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
              <?= e(ucfirst((string)$art['type'])) ?> ·
              <?= $art['price'] !== null ? e(number_format((float)$art['price'])) . ' SAR' : 'N/A' ?>
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
          <div class="art-proof"><?= owProofPanelHtml($pendingStages[(int)$art['id']] ?? [], $pendingColors[(int)$art['id']] ?? [], (string)$art['type'], $ART_TYPES) ?></div>
        </div>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  </div>

  <!-- ===== TAB: ALL ARTWORKS ===== -->
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
          <td><?= number_format((float) $w['price']) ?> SAR</td>
          <td><span class="pill <?= $stw === 'approved' ? 'on' : ($stw === 'rejected' ? 'ban' : 'off') ?>"><?= owBi($ART_STATE[$stw][0], $ART_STATE[$stw][1]) ?></span><?php if ($stw === 'rejected' && $w['reason']): ?><div class="mini"><?= e($w['reason']) ?></div><?php endif; ?></td>
          <td><?= (int) $w['views'] ?></td>
          <td><div class="acts">
            <?php if ($stw === 'sold'): ?>
              <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="artwork_id" value="<?= (int) $w['id'] ?>"><button class="btn btn-ghost btn-sm" name="action" value="unsell_art"><?= owBi('Back to available', 'أعده متاحاً') ?></button></form>
            <?php else: ?>
              <form method="post" action="admin.php" onsubmit="return confirm('Mark this artwork as sold? It leaves the Gallery.');"><?= csrfField() ?><input type="hidden" name="artwork_id" value="<?= (int) $w['id'] ?>"><button class="btn btn-dark btn-sm" name="action" value="sell_art"><?= owBi('Mark as sold', 'علّمه كمباع') ?></button></form>
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
  function showTab(name, btn){
    document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    var panel=document.getElementById('tab-'+name); if(panel)panel.classList.add('active');
    if(btn)btn.classList.add('active');
    try{location.hash=name;}catch(e){}
  }
  // Restore the tab from the URL hash (e.g. after approving an artwork).
  (function(){
    var h=(location.hash||'').replace('#','');
    if(['pending','collectors','artists','artworks','inquiries'].indexOf(h)>=0){
      var btn=document.querySelector('.tab-btn[data-tab="'+h+'"]');
      showTab(h,btn);
    }
  })();
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
  "Admin Login":"دخول الإدارة","Password":"كلمة المرور","Sign In":"تسجيل الدخول","Admin password is not set up yet. Add ADMIN_HASH to config.php.":"كلمة مرور الإدارة غير مُعدّة بعد. أضف ADMIN_HASH إلى config.php.","Incorrect password.":"كلمة مرور غير صحيحة.",
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
  "Name":"الاسم","Email":"البريد الإلكتروني","Phone":"الهاتف","Date":"التاريخ",
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
