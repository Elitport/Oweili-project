<?php
require_once 'config.php';
require_once __DIR__ . '/artwork_lib.php';

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
                getDB()->prepare('UPDATE artworks SET is_approved = 1 WHERE id = :id' . owNotDraftSql())
                       ->execute([':id' => $awId]);
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
        header('Location: admin.php?ok=1#pending');
        exit;
    }

    /* --- Art styles CRUD --- */
    if ($action === 'style_add' || $action === 'style_edit' || $action === 'style_delete') {
        $sid     = (int) ($_POST['id'] ?? 0);
        $titleEn = sanitize($_POST['title_en'] ?? '');
        $titleAr = sanitize($_POST['title_ar'] ?? '');
        $descEn  = trim((string) ($_POST['description_en'] ?? ''));
        $descAr  = trim((string) ($_POST['description_ar'] ?? ''));
        $tag     = sanitize($_POST['tag'] ?? 'Traditional');
        $simg    = sanitize($_POST['image_url'] ?? '');
        try {
            if ($action === 'style_add') {
                getDB()->prepare(
                    'INSERT INTO art_styles (title_en,title_ar,description_en,description_ar,tag,image_url,sort_order)
                     VALUES (:te,:ta,:de,:da,:tag,:img,
                        (SELECT COALESCE(MAX(sort_order),0)+1 FROM (SELECT * FROM art_styles) x))'
                )->execute([':te'=>$titleEn, ':ta'=>$titleAr, ':de'=>$descEn, ':da'=>$descAr, ':tag'=>$tag, ':img'=>$simg]);
            } elseif ($action === 'style_edit' && $sid > 0) {
                getDB()->prepare(
                    'UPDATE art_styles SET title_en=:te,title_ar=:ta,description_en=:de,description_ar=:da,tag=:tag,image_url=:img WHERE id=:id'
                )->execute([':te'=>$titleEn, ':ta'=>$titleAr, ':de'=>$descEn, ':da'=>$descAr, ':tag'=>$tag, ':img'=>$simg, ':id'=>$sid]);
            } elseif ($action === 'style_delete' && $sid > 0) {
                getDB()->prepare('DELETE FROM art_styles WHERE id=:id')->execute([':id'=>$sid]);
            }
        } catch (\Throwable $e) {
            error_log('admin styles: ' . $e->getMessage());
        }
        header('Location: admin.php?ok=1#styles');
        exit;
    }

    /* --- Events CRUD --- */
    if ($action === 'event_add' || $action === 'event_delete') {
        $eid = (int) ($_POST['id'] ?? 0);
        try {
            if ($action === 'event_add') {
                getDB()->prepare(
                    'INSERT INTO events (title_en,title_ar,description_en,description_ar,city,event_date,is_free,image_url,contact_link)
                     VALUES (:te,:ta,:de,:da,:city,:dt,:free,:img,:link)'
                )->execute([
                    ':te'=>sanitize($_POST['title_en'] ?? ''), ':ta'=>sanitize($_POST['title_ar'] ?? ''),
                    ':de'=>trim((string)($_POST['description_en'] ?? '')), ':da'=>trim((string)($_POST['description_ar'] ?? '')),
                    ':city'=>sanitize($_POST['city'] ?? ''),
                    ':dt'=>($_POST['event_date'] ?? '') !== '' ? $_POST['event_date'] : null,
                    ':free'=>(int)($_POST['is_free'] ?? 1) === 1 ? 1 : 0,
                    ':img'=>sanitize($_POST['image_url'] ?? ''), ':link'=>sanitize($_POST['contact_link'] ?? ''),
                ]);
            } elseif ($action === 'event_delete' && $eid > 0) {
                getDB()->prepare('DELETE FROM events WHERE id=:id')->execute([':id'=>$eid]);
            }
        } catch (\Throwable $e) {
            error_log('admin events: ' . $e->getMessage());
            $_SESSION['admin_err'] = 'Events error: ' . $e->getMessage();
            header('Location: admin.php?err=1#events');
            exit;
        }
        header('Location: admin.php?ok=1#events');
        exit;
    }

    /* --- Training CRUD --- */
    if ($action === 'training_add' || $action === 'training_delete') {
        $tid = (int) ($_POST['id'] ?? 0);
        try {
            if ($action === 'training_add') {
                getDB()->prepare(
                    'INSERT INTO training (title_en,title_ar,description_en,description_ar,city,price,course_date,image_url,contact_link)
                     VALUES (:te,:ta,:de,:da,:city,:price,:dt,:img,:link)'
                )->execute([
                    ':te'=>sanitize($_POST['title_en'] ?? ''), ':ta'=>sanitize($_POST['title_ar'] ?? ''),
                    ':de'=>trim((string)($_POST['description_en'] ?? '')), ':da'=>trim((string)($_POST['description_ar'] ?? '')),
                    ':city'=>sanitize($_POST['city'] ?? ''),
                    ':price'=>(float)($_POST['price'] ?? 0),
                    ':dt'=>($_POST['course_date'] ?? '') !== '' ? $_POST['course_date'] : null,
                    ':img'=>sanitize($_POST['image_url'] ?? ''), ':link'=>sanitize($_POST['contact_link'] ?? ''),
                ]);
            } elseif ($action === 'training_delete' && $tid > 0) {
                getDB()->prepare('DELETE FROM training WHERE id=:id')->execute([':id'=>$tid]);
            }
        } catch (\Throwable $e) {
            error_log('admin training: ' . $e->getMessage());
            $_SESSION['admin_err'] = 'Training error: ' . $e->getMessage();
            header('Location: admin.php?err=1#training');
            exit;
        }
        header('Location: admin.php?ok=1#training');
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
        $flash = 'Updated.';
    }
    header('Location: admin.php' . ($flash ? '?ok=1' : ''));
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
         WHERE a.is_approved = 0" . owNotDraftSql('a') . "
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

/* ---- Load art styles (only when authed) ---- */
$styles = [];
$stylesTableMissing = false;
if ($isAdmin) {
    try {
        $styles = getDB()->query('SELECT * FROM art_styles ORDER BY sort_order ASC, id ASC')->fetchAll();
    } catch (\Throwable $e) {
        $stylesTableMissing = true;
    }
}

/* ---- Load events (only when authed) ---- */
$events = [];
$eventsTableMissing = false;
if ($isAdmin) {
    try {
        $events = getDB()->query('SELECT * FROM events ORDER BY (event_date IS NULL), event_date ASC, id DESC')->fetchAll();
    } catch (\Throwable $e) {
        $eventsTableMissing = true;
    }
}

/* ---- Load training (only when authed) ---- */
$training = [];
$trainingTableMissing = false;
if ($isAdmin) {
    try {
        $training = getDB()->query('SELECT * FROM training ORDER BY (course_date IS NULL), course_date ASC, id DESC')->fetchAll();
    } catch (\Throwable $e) {
        $trainingTableMissing = true;
    }
}
$STYLE_TAGS = ['Traditional', 'Digital', 'Mixed Media'];
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin · Oweili</title>
<link rel="stylesheet" href="/assets/css/oweili.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
body{min-height:100vh;background:#f4f5f7;font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;color:#111111;padding:40px 20px;}
.wrap{max-width:1000px;margin:0 auto;}
h1{font-size:24px;font-weight:700;margin-bottom:4px;}
.sub{font-size:13px;color:rgba(0,0,0,0.5);margin-bottom:24px;}
.card{background:#fff;border:1px solid rgba(0,0,0,0.08);border-radius:18px;box-shadow:0 10px 30px rgba(0,0,0,0.05);padding:26px;}
.login-card{max-width:380px;margin:8vh auto 0;}
.login-card h1{margin-bottom:16px;}
label{display:block;font-size:12px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:rgba(0,0,0,0.5);margin-bottom:6px;}
input[type=password]{width:100%;padding:12px 14px;border-radius:12px;border:1px solid rgba(0,0,0,0.15);font-size:14px;font-family:inherit;}
input:focus{outline:none;border-color:#0066ff;box-shadow:0 0 0 3px rgba(0,100,255,0.12);}
.btn{padding:11px 22px;border-radius:999px;background:#111111;color:#fff;font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;border:none;cursor:pointer;transition:all 0.22s;text-decoration:none;display:inline-block;}
.btn:hover{background:#ff0055;}
.btn.sm{padding:7px 14px;font-size:12px;}
.btn.green{background:#0066ff;}.btn.green:hover{background:#0066ff;}
.btn.red{background:#ff0055;}.btn.red:hover{background:#111111;}
.btn.grey{background:rgba(0,0,0,0.08);color:#111111;}.btn.grey:hover{background:rgba(0,0,0,0.16);}
.err{color:#111111;font-size:13px;font-weight:600;margin-bottom:12px;}
.top{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;}
table{width:100%;border-collapse:collapse;font-size:13px;}
th,td{text-align:left;padding:12px 10px;border-bottom:1px solid rgba(0,0,0,0.06);vertical-align:middle;}
th{font-size:12px;text-transform:uppercase;letter-spacing:0.08em;color:rgba(0,0,0,0.45);}
.pill{display:inline-block;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:700;}
.pill.on{background:rgba(0,102,255,0.14);color:#0066ff;}
.pill.off{background:rgba(0,0,0,0.06);color:rgba(0,0,0,0.5);}
.pill.ban{background:rgba(255,0,85,0.12);color:#111111;}
.acts{display:flex;gap:6px;flex-wrap:wrap;}
.name-ar{font-size:12px;color:rgba(0,0,0,0.45);}
.empty{padding:30px;text-align:center;color:rgba(0,0,0,0.4);}
.sec-head{margin-bottom:16px;}
.sec-head h2{font-size:16px;font-weight:700;display:flex;align-items:center;gap:8px;}
.count{background:#ff0055;color:#fff;font-size:12px;font-weight:700;padding:2px 9px;border-radius:999px;}
.art-list{display:flex;flex-direction:column;gap:12px;}
.art-item{display:flex;flex-wrap:wrap;align-items:center;gap:16px;padding:12px;border:1px solid rgba(0,0,0,0.07);border-radius:14px;}
.art-thumb{width:64px;height:64px;border-radius:10px;background-size:cover;background-position:center;background-color:#eee;flex-shrink:0;}
.art-meta{flex:1;min-width:0;}
.art-proof{flex-basis:100%;min-width:0;}
.art-proof .pj{margin-top:4px;}
.art-meta strong{font-size:14px;}
.art-info{font-size:12px;color:rgba(0,0,0,0.5);margin-top:3px;}
.verify-cell{display:flex;flex-direction:column;gap:6px;align-items:flex-start;min-width:150px;}
.verify-photo{width:52px;height:52px;border-radius:10px;object-fit:cover;border:1px solid rgba(0,0,0,0.1);}
.verify-video{width:150px;max-width:100%;border-radius:10px;background:#000;}
.video-link{font-size:12px;font-weight:600;color:#0066ff;text-decoration:none;}
.video-link:hover{text-decoration:underline;}
.no-media{font-size:12px;color:rgba(0,0,0,0.35);font-style:italic;}
.pill.role-artist{background:rgba(255,0,85,0.1);color:#111111;}
.pill.role-collector{background:rgba(0,100,255,0.1);color:#0066ff;}
.pill.role-admin{background:rgba(0,102,255,0.1);color:#0066ff;}
.status-pills{display:flex;gap:5px;flex-wrap:wrap;}
.tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;}
.tab-btn{padding:10px 22px;border-radius:999px;font-size:12px;font-weight:700;letter-spacing:0.04em;cursor:pointer;border:1px solid rgba(0,0,0,0.1);background:#fff;color:rgba(0,0,0,0.6);transition:all 0.2s;display:flex;align-items:center;gap:8px;}
.tab-btn:hover{background:rgba(0,0,0,0.03);color:#111;}
.tab-btn.active{background:#111111;color:#fff;border-color:#111111;}
.tab-btn .count{background:rgba(255,255,255,0.25);}
.tab-btn:not(.active) .count{background:#ff0055;color:#fff;}
.tab-panel{display:none;}
.tab-panel.active{display:block;}
.lang-btn{padding:9px 16px;border-radius:999px;font-size:12px;font-weight:700;letter-spacing:0.04em;cursor:pointer;border:1px solid rgba(0,0,0,0.1);background:#fff;color:rgba(0,0,0,0.7);transition:all 0.2s;}
.lang-btn:hover{background:#111;color:#fff;}
[dir="rtl"] body{direction:rtl;}
[dir="rtl"] th,[dir="rtl"] td{text-align:right;}

[dir="rtl"] .verify-cell{align-items:flex-end;}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.field{margin-bottom:14px;}
.field label{margin-bottom:6px;}
.field input,.field textarea,.field select{width:100%;padding:11px 14px;border-radius:12px;border:1px solid rgba(0,0,0,0.15);font-size:14px;font-family:inherit;background:#fff;}
.field textarea{resize:vertical;min-height:60px;}
.field input:focus,.field textarea:focus,.field select:focus{outline:none;border-color:#0066ff;box-shadow:0 0 0 3px rgba(0,100,255,0.12);}
.style-item{display:flex;gap:16px;padding:14px;border:1px solid rgba(0,0,0,0.07);border-radius:14px;margin-bottom:12px;align-items:flex-start;}
.style-thumb{width:80px;height:80px;border-radius:10px;background-size:cover;background-position:center;background-color:#eee;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:24px;color:rgba(0,0,0,0.25);}
.style-body{flex:1;min-width:0;}
.style-body h3{font-size:15px;font-weight:700;margin-bottom:2px;}
.style-desc{font-size:12px;color:rgba(0,0,0,0.6);line-height:1.6;margin:4px 0 6px;}
.tag-pill{display:inline-block;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:700;background:rgba(0,100,255,0.1);color:#0066ff;}
.edit-form{display:none;margin-top:12px;padding-top:12px;border-top:1px solid rgba(0,0,0,0.06);}
.edit-form.open{display:block;}

.online-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#ccc;margin-inline-end:5px;vertical-align:middle;}
.online-dot.on{background:#0066ff;box-shadow:0 0 6px rgba(0,102,255,0.6);}
.online-stat{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:#0066ff;background:rgba(0,102,255,0.1);border:1px solid rgba(0,102,255,0.25);padding:6px 14px;border-radius:999px;}
.port-thumbs{display:flex;gap:4px;flex-wrap:wrap;max-width:150px;}
.port-thumbs a{display:block;}
.port-thumbs img{width:34px;height:34px;border-radius:6px;object-fit:cover;border:1px solid rgba(0,0,0,0.1);}
.role-select{padding:6px 8px;border-radius:8px;border:1px solid rgba(0,0,0,0.15);font-size:12px;font-family:inherit;background:#fff;}
.role-form{display:flex;gap:4px;align-items:center;margin-top:4px;}
.mini{font-size:12px;color:rgba(0,0,0,0.4);}
/* Artist review cards */
.acards{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:18px;}
.acard{border:1px solid rgba(0,0,0,0.08);border-radius:16px;padding:18px;background:#fff;display:flex;flex-direction:column;gap:12px;}
.acard-head{display:flex;gap:12px;align-items:center;}
.acard-photo{width:56px;height:56px;border-radius:50%;object-fit:cover;background-size:cover;background-position:center;background:linear-gradient(135deg,#ff0055,#0066ff,#0066ff);display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;font-weight:700;flex-shrink:0;}
.acard-id{flex:1;min-width:0;}
.acard-id .nm{font-size:15px;font-weight:700;display:flex;align-items:center;gap:6px;}
.acard-id .an{font-size:12px;color:rgba(0,0,0,0.5);}
.acard-badges{display:flex;gap:5px;flex-wrap:wrap;}
.acard-info{font-size:12px;color:rgba(0,0,0,0.6);line-height:1.7;}
.acard-info b{color:#111;font-weight:600;}
.acard-media{display:flex;gap:8px;flex-wrap:wrap;align-items:flex-start;}
.acard-media video{width:100%;border-radius:10px;background:#000;}
.acard-foot{display:flex;flex-direction:column;gap:8px;border-top:1px solid rgba(0,0,0,0.06);padding-top:12px;margin-top:auto;}
@media(max-width:640px){.grid2{grid-template-columns:1fr;}.style-item{flex-direction:column;}.acards{grid-template-columns:1fr;}}
</style>
<link rel="stylesheet" href="/assets/css/proof.css">
</head>
<body>
<div class="wrap">
<?php if (!$isAdmin): ?>

  <div style="max-width:380px;margin:6vh auto 0;text-align:right;"><button class="lang-btn" id="lb" onclick="tglLang()">العربية</button></div>
  <form class="card login-card" method="post" action="admin.php" style="margin-top:12px;">
    <h1>Admin Login</h1>
    <?php if ($loginError): ?><div class="err"><?= e($loginError) ?></div><?php endif; ?>
    <?= csrfField() ?>
    <label for="admin_pass">Password</label>
    <input type="password" id="admin_pass" name="admin_pass" required autofocus>
    <div style="margin-top:16px;"><button class="btn" type="submit">Sign In</button></div>
  </form>

<?php else: ?>

  <div class="top">
    <div>
      <h1>Admin Dashboard</h1>
      <div class="sub">Manage artists, collectors, and artwork approvals.</div>
      <div class="online-stat"><span class="online-dot on"></span><?= $onlineCount ?>&nbsp;<span>Online Now</span></div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
      <button class="lang-btn" id="lb" onclick="tglLang()">العربية</button>
      <a class="btn" href="admin_articles.php">Articles</a>
      <a class="btn grey" href="admin.php?logout=1">Log out</a>
    </div>
  </div>

  <?php if (!empty($_SESSION['admin_err'])): ?>
    <div class="card" style="border:1px solid rgba(255,0,85,0.3);background:rgba(255,0,85,0.06);color:#111111;margin-bottom:16px;">
      <strong>⚠ Could not save.</strong> <?= e($_SESSION['admin_err']) ?>
      <div class="mini" style="margin-top:6px;color:#111111;">If this mentions a missing table, run <code>discover_setup.sql</code> in phpMyAdmin.</div>
    </div>
    <?php unset($_SESSION['admin_err']); ?>
  <?php elseif (isset($_GET['ok'])): ?>
    <div class="card" style="border:1px solid rgba(0,102,255,0.25);background:rgba(0,102,255,0.06);color:#111111;margin-bottom:16px;"><strong>✓ Saved.</strong></div>
  <?php endif; ?>

  <!-- TABS -->
  <div class="tabs">
    <button class="tab-btn active" data-tab="artists" onclick="showTab('artists',this)">Artists <span class="pill count"><?= count($artists) ?></span></button>
    <button class="tab-btn" data-tab="collectors" onclick="showTab('collectors',this)">Collectors <span class="pill count"><?= count($collectors) ?></span></button>
    <button class="tab-btn" data-tab="pending" onclick="showTab('pending',this)">Pending Artworks <span class="pill count"><?= count($pendingArt) ?></span></button>
    <button class="tab-btn" data-tab="styles" onclick="showTab('styles',this)">Art Styles <span class="pill count"><?= count($styles) ?></span></button>
    <button class="tab-btn" data-tab="events" onclick="showTab('events',this)">Events <span class="pill count"><?= count($events) ?></span></button>
    <button class="tab-btn" data-tab="training" onclick="showTab('training',this)">Training <span class="pill count"><?= count($training) ?></span></button>
  </div>

  <!-- ===== TAB: ARTISTS ===== -->
  <div class="tab-panel active" id="tab-artists">
  <div class="card">
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
          </div>

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
                <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="user_id" value="<?= (int)$a['id'] ?>"><button class="btn sm green" name="action" value="approve">Approve</button></form>
              <?php else: ?>
                <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="user_id" value="<?= (int)$a['id'] ?>"><button class="btn sm grey" name="action" value="unapprove">Unapprove</button></form>
              <?php endif; ?>
              <?php if ((int)$a['is_banned'] === 1): ?>
                <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="user_id" value="<?= (int)$a['id'] ?>"><button class="btn sm grey" name="action" value="unban">Unban</button></form>
              <?php else: ?>
                <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="user_id" value="<?= (int)$a['id'] ?>"><button class="btn sm red" name="action" value="ban">Ban</button></form>
              <?php endif; ?>
            </div>
            <form method="post" action="admin.php" class="role-form"><?= csrfField() ?><input type="hidden" name="user_id" value="<?= (int)$a['id'] ?>">
              <select class="role-select" name="role">
                <?php foreach (['collector','artist','sub_admin','admin'] as $r): ?><option value="<?= $r ?>" <?= ($a['role']??'')===$r?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$r)) ?></option><?php endforeach; ?>
              </select>
              <button class="btn sm grey" name="action" value="set_role">Set Role</button>
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
    <?php if (!$collectors): ?>
      <div class="empty">No collector accounts yet.</div>
    <?php else: ?>
      <div style="overflow-x:auto;">
      <table>
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
                <?php if ((int)$c['is_banned'] === 1): ?>
                  <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="user_id" value="<?= (int)$c['id'] ?>"><button class="btn sm grey" name="action" value="unban">Unban</button></form>
                <?php else: ?>
                  <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="user_id" value="<?= (int)$c['id'] ?>"><button class="btn sm red" name="action" value="ban">Ban</button></form>
                <?php endif; ?>
              </div>
              <form method="post" action="admin.php" class="role-form"><?= csrfField() ?><input type="hidden" name="user_id" value="<?= (int)$c['id'] ?>">
                <select class="role-select" name="role">
                  <?php foreach (['collector','artist','sub_admin','admin'] as $r): ?><option value="<?= $r ?>" <?= ($c['role']??'')===$r?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$r)) ?></option><?php endforeach; ?>
                </select>
                <button class="btn sm grey" name="action" value="set_role">Set</button>
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

  <!-- ===== TAB: PENDING ARTWORKS ===== -->
  <div class="tab-panel" id="tab-pending">
  <div class="card" id="pending">
    <div class="sec-head">
      <div class="sub" style="margin:0;">New uploads waiting for approval before they appear in the marketplace.</div>
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
            <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="artwork_id" value="<?= (int)$art['id'] ?>"><button class="btn sm green" name="action" value="approve_art">Approve</button></form>
            <form method="post" action="admin.php" onsubmit="return confirm('Reject and permanently delete this artwork?');"><?= csrfField() ?><input type="hidden" name="artwork_id" value="<?= (int)$art['id'] ?>"><button class="btn sm red" name="action" value="reject_art">Reject</button></form>
          </div>
          <div class="art-proof"><?= owProofPanelHtml($pendingStages[(int)$art['id']] ?? [], $pendingColors[(int)$art['id']] ?? [], (string)$art['type'], $ART_TYPES) ?></div>
        </div>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  </div>

  <!-- ===== TAB: ART STYLES ===== -->
  <div class="tab-panel" id="tab-styles">
    <?php if ($stylesTableMissing): ?>
      <div class="card"><div class="empty">The <code>art_styles</code> table does not exist yet. Run <code>styles_setup.sql</code> in phpMyAdmin.</div></div>
    <?php else: ?>
    <div class="card" style="margin-bottom:20px;">
      <div class="sec-head"><h2>Add New Style</h2></div>
      <form method="post" action="admin.php#styles">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="style_add">
        <div class="grid2">
          <div class="field"><label>Title (English)</label><input type="text" name="title_en" required></div>
          <div class="field"><label>Title (Arabic)</label><input type="text" name="title_ar" dir="rtl"></div>
        </div>
        <div class="grid2">
          <div class="field"><label>Description (English)</label><textarea name="description_en"></textarea></div>
          <div class="field"><label>Description (Arabic)</label><textarea name="description_ar" dir="rtl"></textarea></div>
        </div>
        <div class="grid2">
          <div class="field"><label>Tag</label>
            <select name="tag"><?php foreach ($STYLE_TAGS as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?></select>
          </div>
          <div class="field"><label>Image URL</label><input type="text" name="image_url" dir="ltr" placeholder="https://…"></div>
        </div>
        <button type="submit" class="btn green">Add Style</button>
      </form>
    </div>

    <div class="card">
      <div class="sec-head"><h2>Art Styles <span class="count" style="background:#0066ff;"><?= count($styles) ?></span></h2></div>
      <?php if (!$styles): ?>
        <div class="empty">No styles yet. Add your first one above.</div>
      <?php else: ?>
        <?php foreach ($styles as $s): ?>
          <div class="style-item">
            <div class="style-thumb" <?= $s['image_url'] ? 'style="background-image:url(\'' . e($s['image_url']) . '\');"' : '' ?>><?= $s['image_url'] ? '' : '🎨' ?></div>
            <div class="style-body">
              <h3><?= e($s['title_en'] ?: 'N/A') ?></h3>
              <?php if (!empty($s['title_ar'])): ?><div class="name-ar" dir="rtl"><?= e($s['title_ar']) ?></div><?php endif; ?>
              <?php if (!empty($s['description_en'])): ?><p class="style-desc"><?= e($s['description_en']) ?></p><?php endif; ?>
              <span class="tag-pill"><?= e($s['tag']) ?></span>

              <div class="edit-form" id="style-edit-<?= (int)$s['id'] ?>">
                <form method="post" action="admin.php#styles">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="style_edit">
                  <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                  <div class="grid2">
                    <div class="field"><label>Title (English)</label><input type="text" name="title_en" value="<?= e($s['title_en']) ?>" required></div>
                    <div class="field"><label>Title (Arabic)</label><input type="text" name="title_ar" dir="rtl" value="<?= e($s['title_ar']) ?>"></div>
                  </div>
                  <div class="grid2">
                    <div class="field"><label>Description (English)</label><textarea name="description_en"><?= e($s['description_en']) ?></textarea></div>
                    <div class="field"><label>Description (Arabic)</label><textarea name="description_ar" dir="rtl"><?= e($s['description_ar']) ?></textarea></div>
                  </div>
                  <div class="grid2">
                    <div class="field"><label>Tag</label>
                      <select name="tag"><?php foreach ($STYLE_TAGS as $t): ?><option value="<?= e($t) ?>" <?= $s['tag']===$t?'selected':'' ?>><?= e($t) ?></option><?php endforeach; ?></select>
                    </div>
                    <div class="field"><label>Image URL</label><input type="text" name="image_url" dir="ltr" value="<?= e($s['image_url']) ?>"></div>
                  </div>
                  <button type="submit" class="btn green sm">Save Changes</button>
                  <button type="button" class="btn grey sm" onclick="toggleStyleEdit(<?= (int)$s['id'] ?>)">Cancel</button>
                </form>
              </div>
            </div>
            <div class="acts" style="flex-direction:column;">
              <button type="button" class="btn grey sm" onclick="toggleStyleEdit(<?= (int)$s['id'] ?>)">Edit</button>
              <form method="post" action="admin.php#styles" onsubmit="return confirm('Delete this style?');" style="margin:0;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="style_delete">
                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                <button type="submit" class="btn red sm">Delete</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- ===== TAB: EVENTS ===== -->
  <div class="tab-panel" id="tab-events">
    <?php if ($eventsTableMissing): ?>
      <div class="card"><div class="empty">The <code>events</code> table does not exist yet. Run <code>discover_setup.sql</code> in phpMyAdmin.</div></div>
    <?php else: ?>
    <div class="card" style="margin-bottom:20px;">
      <div class="sec-head"><h2>Add New Event</h2></div>
      <form method="post" action="admin.php#events">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="event_add">
        <div class="grid2">
          <div class="field"><label>Title (English)</label><input type="text" name="title_en" required></div>
          <div class="field"><label>Title (Arabic)</label><input type="text" name="title_ar" dir="rtl"></div>
        </div>
        <div class="grid2">
          <div class="field"><label>Description (English)</label><textarea name="description_en"></textarea></div>
          <div class="field"><label>Description (Arabic)</label><textarea name="description_ar" dir="rtl"></textarea></div>
        </div>
        <div class="grid2">
          <div class="field"><label>City</label><input type="text" name="city" placeholder="Riyadh"></div>
          <div class="field"><label>Date</label><input type="date" name="event_date"></div>
        </div>
        <div class="grid2">
          <div class="field"><label>Entry</label>
            <select name="is_free"><option value="1">Free</option><option value="0">Paid</option></select>
          </div>
          <div class="field"><label>Image URL</label><input type="text" name="image_url" dir="ltr" placeholder="https://…"></div>
        </div>
        <div class="field"><label>Contact / Details Link</label><input type="text" name="contact_link" dir="ltr" placeholder="https://… or mailto:…"></div>
        <button type="submit" class="btn green">Add Event</button>
      </form>
    </div>

    <div class="card">
      <div class="sec-head"><h2>Events <span class="count" style="background:#0066ff;"><?= count($events) ?></span></h2></div>
      <?php if (!$events): ?>
        <div class="empty">No events yet. Add your first one above.</div>
      <?php else: ?>
        <?php foreach ($events as $ev): ?>
          <div class="style-item">
            <div class="style-thumb" <?= $ev['image_url'] ? 'style="background-image:url(\'' . e($ev['image_url']) . '\');"' : '' ?>><?= $ev['image_url'] ? '' : '📅' ?></div>
            <div class="style-body">
              <h3><?= e($ev['title_en'] ?: 'N/A') ?></h3>
              <?php if (!empty($ev['title_ar'])): ?><div class="name-ar" dir="rtl"><?= e($ev['title_ar']) ?></div><?php endif; ?>
              <?php if (!empty($ev['description_en'])): ?><p class="style-desc"><?= e($ev['description_en']) ?></p><?php endif; ?>
              <span class="tag-pill"><?= e($ev['city'] ?: '—') ?></span>
              <?php if (!empty($ev['event_date'])): ?><span class="tag-pill"><?= e(date('Y-m-d', strtotime($ev['event_date']))) ?></span><?php endif; ?>
              <span class="tag-pill"><?= ((int)$ev['is_free'] === 1) ? 'Free' : 'Paid' ?></span>
            </div>
            <div class="acts" style="flex-direction:column;">
              <form method="post" action="admin.php#events" onsubmit="return confirm('Delete this event?');" style="margin:0;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="event_delete">
                <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>">
                <button type="submit" class="btn red sm">Delete</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- ===== TAB: TRAINING ===== -->
  <div class="tab-panel" id="tab-training">
    <?php if ($trainingTableMissing): ?>
      <div class="card"><div class="empty">The <code>training</code> table does not exist yet. Run <code>discover_setup.sql</code> in phpMyAdmin.</div></div>
    <?php else: ?>
    <div class="card" style="margin-bottom:20px;">
      <div class="sec-head"><h2>Add New Training</h2></div>
      <form method="post" action="admin.php#training">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="training_add">
        <div class="grid2">
          <div class="field"><label>Title (English)</label><input type="text" name="title_en" required></div>
          <div class="field"><label>Title (Arabic)</label><input type="text" name="title_ar" dir="rtl"></div>
        </div>
        <div class="grid2">
          <div class="field"><label>Description (English)</label><textarea name="description_en"></textarea></div>
          <div class="field"><label>Description (Arabic)</label><textarea name="description_ar" dir="rtl"></textarea></div>
        </div>
        <div class="grid2">
          <div class="field"><label>City</label><input type="text" name="city" placeholder="Riyadh"></div>
          <div class="field"><label>Price (SAR)</label><input type="number" name="price" min="0" step="0.01" value="0"></div>
        </div>
        <div class="grid2">
          <div class="field"><label>Date</label><input type="date" name="course_date"></div>
          <div class="field"><label>Image URL</label><input type="text" name="image_url" dir="ltr" placeholder="https://…"></div>
        </div>
        <div class="field"><label>Contact / Details Link</label><input type="text" name="contact_link" dir="ltr" placeholder="https://… or mailto:…"></div>
        <button type="submit" class="btn green">Add Training</button>
      </form>
    </div>

    <div class="card">
      <div class="sec-head"><h2>Training <span class="count" style="background:#0066ff;"><?= count($training) ?></span></h2></div>
      <?php if (!$training): ?>
        <div class="empty">No training listed yet. Add your first one above.</div>
      <?php else: ?>
        <?php foreach ($training as $tr): ?>
          <div class="style-item">
            <div class="style-thumb" <?= $tr['image_url'] ? 'style="background-image:url(\'' . e($tr['image_url']) . '\');"' : '' ?>><?= $tr['image_url'] ? '' : '🎓' ?></div>
            <div class="style-body">
              <h3><?= e($tr['title_en'] ?: 'N/A') ?></h3>
              <?php if (!empty($tr['title_ar'])): ?><div class="name-ar" dir="rtl"><?= e($tr['title_ar']) ?></div><?php endif; ?>
              <?php if (!empty($tr['description_en'])): ?><p class="style-desc"><?= e($tr['description_en']) ?></p><?php endif; ?>
              <span class="tag-pill"><?= e($tr['city'] ?: '—') ?></span>
              <?php if (!empty($tr['course_date'])): ?><span class="tag-pill"><?= e(date('Y-m-d', strtotime($tr['course_date']))) ?></span><?php endif; ?>
              <span class="tag-pill"><?= ((float)$tr['price'] > 0) ? ('SAR ' . rtrim(rtrim(number_format((float)$tr['price'], 2), '0'), '.')) : 'Free' ?></span>
            </div>
            <div class="acts" style="flex-direction:column;">
              <form method="post" action="admin.php#training" onsubmit="return confirm('Delete this training?');" style="margin:0;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="training_delete">
                <input type="hidden" name="id" value="<?= (int)$tr['id'] ?>">
                <button type="submit" class="btn red sm">Delete</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <script>
  function toggleStyleEdit(id){ var el=document.getElementById('style-edit-'+id); if(el) el.classList.toggle('open'); }
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
    if(h==='pending'||h==='collectors'||h==='artists'||h==='styles'||h==='events'||h==='training'){
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
  "Manage artists, collectors, and artwork approvals.":"إدارة الفنانين والمقتنين واعتماد الأعمال.",
  "Log out":"تسجيل الخروج",
  "Articles":"المقالات",
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
  "New uploads waiting for approval before they appear in the marketplace.":"أعمال جديدة بانتظار الموافقة قبل ظهورها في السوق.",
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
