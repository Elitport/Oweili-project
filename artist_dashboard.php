<?php
require_once 'config.php';
require_once __DIR__ . '/artwork_lib.php';

/* ---------------------------------------------------------------------
 *  Check 1 — Session check.
 *  A public profile (?id=N) can be viewed by anyone, logged in or not.
 *  Your own dashboard (no id) needs you to be logged in.
 * ------------------------------------------------------------------- */
$hasId = isset($_GET['id']) && (int) $_GET['id'] > 0;
if (!isLoggedIn() && !$hasId) {
    header('Location: login.php');
    exit;
}
$viewerId = isLoggedIn() ? (int) $_SESSION['user_id'] : 0;

/* Artwork types (shared list) */
$ART_TYPES = require __DIR__ . '/art_types.php';

/* Which profile are we viewing?
 *  - ?id=N  -> that user's profile
 *  - no id  -> the logged-in user's own profile
 */
$profileId = $hasId ? (int) $_GET['id'] : $viewerId;
$isOwner   = $viewerId === $profileId;

/* This is the artist dashboard. Any non-artist landing on their OWN dashboard
   is redirected to the correct one for their role. (Viewing another artist's
   public profile by ?id is still allowed — private data stays owner-only.) */
$viewer = currentUser();
if ($isOwner && $viewer && ($viewer['role'] ?? '') !== 'artist') {
    header('Location: ' . dashboardUrl($viewer['role'] ?? ''));
    exit;
}

/* ---------------------------------------------------------------------
 *  POST handlers (owner-only, CSRF-protected). PRG pattern.
 * ------------------------------------------------------------------- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!isLoggedIn()) { header('Location: login.php'); exit; }
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) { http_response_code(403); exit('Invalid CSRF token.'); }

    $action = $_POST['action'] ?? '';

    /* ---- Delete an artwork (must own it — verified on backend) ---- */
    if ($action === 'delete') {
        $artId = (int) ($_POST['artwork_id'] ?? 0);
        $stmt = getDB()->prepare('SELECT image_url FROM artworks WHERE id = :id AND artist_id = :uid LIMIT 1');
        $stmt->execute([':id' => $artId, ':uid' => $viewerId]);
        $row = $stmt->fetch();
        if ($row) {
            owDeleteAllStageFiles($artId);
            getDB()->prepare('DELETE FROM artworks WHERE id = :id AND artist_id = :uid')
                ->execute([':id' => $artId, ':uid' => $viewerId]);
            // best-effort remove the file
            $path = __DIR__ . '/' . ltrim((string) $row['image_url'], '/');
            if ($row['image_url'] && strpos($row['image_url'], 'uploads/artworks/') === 0 && is_file($path)) {
                @unlink($path);
            }
        }
        header('Location: artist_dashboard.php?id=' . $viewerId);
        exit;
    }
}

/* ---------------------------------------------------------------------
 *  Load profile + artworks.
 * ------------------------------------------------------------------- */
$stmt = getDB()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $profileId]);
$artist = $stmt->fetch();

/* Visitors can only see real, active artist profiles (not collectors, admins or banned accounts). */
if ($artist && !$isOwner && (($artist['role'] ?? '') !== 'artist' || !empty($artist['is_banned']))) {
    $artist = null;
}
if (!$artist) {
    // If the logged-in user's own record is missing, the session is stale -> login.
    if ($profileId === $viewerId) {
        header('Location: login.php');
        exit;
    }
    http_response_code(404);
    $artist = null;
}

$artworks = [];
$soldCount = 0;
if ($artist) {
    // Unfinished drafts are listed separately (owner only); visitors only see approved work.
    $stmt = getDB()->prepare('SELECT * FROM artworks WHERE artist_id = :id' . owNotDraftSql()
        . ($isOwner ? '' : ' AND is_approved = 1') . ' ORDER BY created_at DESC');
    $stmt->execute([':id' => $profileId]);
    $artworks = $stmt->fetchAll();
    foreach ($artworks as $a) {
        if ($a['status'] === 'sold') $soldCount++;
    }
}

/* Drafts in progress (3-stage upload not yet submitted) — owner only */
$drafts = [];
$draftStages = [];
if ($artist && $isOwner && owStagesReady()) {
    $stmt = getDB()->prepare('SELECT id, title_en, title_ar, created_at FROM artworks WHERE artist_id = :id AND is_draft = 1 ORDER BY created_at DESC');
    $stmt->execute([':id' => $profileId]);
    $drafts = $stmt->fetchAll();
    $draftStages = owGetStagesFor(array_column($drafts, 'id'));
}

/* Creation Journey for each artwork shown (stage photos + colors) */
$artStages = owGetStagesFor(array_column($artworks, 'id'));
$artColors = owGetColorsFor(array_column($artworks, 'id'));

$memberSince = $artist && !empty($artist['created_at']) ? date('Y', strtotime($artist['created_at'])) : 'N/A';
$totalCount  = count($artworks);

/* ---- Financial summary (owner's own figures) ---- */
$earnAll   = 0.0;
$earnYear  = 0.0;
$soldList  = [];
$thisYear  = date('Y');
foreach ($artworks as $a) {
    if ($a['status'] === 'sold') {
        $price = (float) $a['price'];
        $earnAll += $price;
        // sold_at may not exist on older schemas; fall back to created_at.
        $when = !empty($a['sold_at']) ? $a['sold_at'] : $a['created_at'];
        if ($when && date('Y', strtotime($when)) === $thisYear) {
            $earnYear += $price;
        }
        $soldList[] = [
            'title_en' => $a['title_en'],
            'title_ar' => $a['title_ar'],
            'price'    => $price,
            'date'     => $when ? date('Y-m-d', strtotime($when)) : 'N/A',
        ];
    }
}

/* helpers for optional social columns (may not exist in older schemas) */
$ig  = $artist['instagram'] ?? '';
$tw  = $artist['twitter']   ?? '';
$web = $artist['website']   ?? '';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" id="html">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $artist ? e($artist['full_name_en']) . ' · ' : '' ?>Artist Profile · Oweili</title>
<link rel="stylesheet" href="/assets/css/oweili.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;min-height:100vh;background:#fff;font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;overflow-x:hidden;}
.bg-wrap{position:fixed;inset:0;z-index:0;overflow:hidden;}
.bg-video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0;transition:opacity 2s ease;z-index:2;}
.bg-video.on{opacity:0.95;}
.overlay{position:fixed;inset:0;z-index:3;pointer-events:none;background:linear-gradient(160deg,rgba(255,255,255,0.18) 0%,rgba(255,255,255,0) 50%,rgba(255,255,255,0.35) 100%);}
/* ── TOP NAV (shared brand navbar) ── */
.topnav{position:fixed;top:0;left:0;right:0;z-index:200;display:flex;align-items:center;justify-content:space-between;padding:0 16px;height:56px;background:rgba(255,255,255,0.6);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-bottom:1px solid rgba(0,0,0,0.05);}
[dir="rtl"] .dropdown{left:auto;right:0;text-align:right;}
.nav-logo{display:flex;align-items:center;gap:6px;text-decoration:none;color:#111111;flex-shrink:0;}
.nav-logo svg{color:#ff0055;}
.nav-logo span{font-size:12px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;}
.nav-center{display:flex;align-items:stretch;gap:0;height:100%;}
.nav-item{position:relative;display:flex;align-items:center;}
.nav-item > a{display:flex;align-items:center;gap:4px;padding:0 12px;height:100%;font-size:12px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:rgba(0,0,0,0.65);text-decoration:none;transition:color 0.2s;white-space:nowrap;border-bottom:2px solid transparent;}
.nav-item > a:hover{color:#000;}
.nav-item:hover > a{color:#0066ff;border-bottom-color:#0066ff;}
.nav-item > a .chevron{width:10px;height:10px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;transition:transform 0.2s;flex-shrink:0;}
.nav-item:hover > a .chevron{transform:rotate(180deg);}
.dropdown{position:absolute;top:100%;left:0;min-width:200px;background:rgba(255,255,255,0.96);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(0,0,0,0.07);border-radius:14px;box-shadow:0 16px 40px rgba(0,0,0,0.1);padding:8px;opacity:0;visibility:hidden;transform:translateY(8px);transition:opacity 0.2s,transform 0.2s,visibility 0.2s;pointer-events:none;}
.nav-item:hover .dropdown{opacity:1;visibility:visible;transform:translateY(0);pointer-events:auto;}
.dropdown a{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:9px;font-size:12px;color:rgba(0,0,0,0.7);text-decoration:none;transition:background 0.15s,color 0.15s;}
.dropdown a:hover{background:rgba(0,100,255,0.07);color:#0066ff;}
.dropdown a .d-icon{width:14px;height:14px;fill:currentColor;opacity:0.6;flex-shrink:0;}
.nav-right{display:flex;align-items:center;gap:6px;flex-shrink:0;}
.nav-btn{padding:5px 12px;border-radius:999px;font-size:12px;font-weight:600;letter-spacing:0.04em;text-transform:uppercase;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;transition:all 0.22s;border:1px solid rgba(0,0,0,0.1);background:rgba(0,0,0,0.04);color:rgba(0,0,0,0.8);}
.nav-btn:hover{background:rgba(0,0,0,0.08);color:#000;}
.nav-btn.primary{background:rgba(0,100,255,0.1);border-color:rgba(0,100,255,0.2);color:#0066ff;}
.nav-btn.primary:hover{background:rgba(0,100,255,0.18);}
.lang-btn{padding:4px 10px;border-radius:999px;font-size:12px;font-weight:600;letter-spacing:0.04em;cursor:pointer;border:1px solid rgba(0,0,0,0.1);background:rgba(255,255,255,0.7);color:rgba(0,0,0,0.7);backdrop-filter:blur(8px);transition:all 0.22s;}
.lang-btn:hover{background:#111111;color:#fff;}
@media(max-width:1024px){.nav-center{display:none;}}

.page{position:relative;z-index:10;width:100%;max-width:1180px;margin:0 auto;padding:90px 24px 56px;}
.gc{background:rgba(255,255,255,0.6);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(0,0,0,0.07);border-radius:20px;}
.section-title{font-size:12px;text-transform:uppercase;letter-spacing:0.16em;color:rgba(0,0,0,0.45);font-weight:700;margin:36px 0 16px;}

/* PROFILE HEADER */
.profile-head{padding:32px;display:flex;gap:28px;align-items:center;animation:riseUp 0.8s cubic-bezier(0.22,1,0.36,1) forwards;opacity:0;}
[dir="rtl"] .profile-head{text-align:right;}
.avatar{width:120px;height:120px;border-radius:50%;object-fit:cover;flex-shrink:0;border:3px solid rgba(255,255,255,0.8);box-shadow:0 8px 24px rgba(0,0,0,0.12);background:linear-gradient(135deg,#ff0055,#0066ff,#0066ff);display:flex;align-items:center;justify-content:center;color:#fff;font-size:42px;font-weight:700;}
.profile-info{flex:1;min-width:0;}
.profile-info h1{font-size:28px;font-weight:700;color:#111111;margin-bottom:2px;}
.profile-info .name-ar{font-size:16px;color:rgba(0,0,0,0.55);margin-bottom:8px;}
.profile-info .city{font-size:13px;color:#0066ff;font-weight:600;margin-bottom:10px;display:flex;align-items:center;gap:5px;}

.profile-info .bio{font-size:13px;color:rgba(0,0,0,0.65);line-height:1.7;margin-bottom:6px;max-width:640px;}
.profile-info .bio-ar{font-size:13px;color:rgba(0,0,0,0.5);line-height:1.8;max-width:640px;}
.stats-row{display:flex;gap:28px;margin-top:16px;}

.stat .num{font-size:24px;font-weight:700;background:linear-gradient(90deg,#ff0055,#0066ff,#0066ff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.stat .lbl{font-size:12px;text-transform:uppercase;letter-spacing:0.08em;color:rgba(0,0,0,0.45);font-weight:600;}
.head-actions{display:flex;flex-direction:column;gap:10px;align-items:flex-end;flex-shrink:0;}
[dir="rtl"] .head-actions{align-items:flex-start;}
.btn-edit{padding:10px 20px;border-radius:999px;background:#111111;color:#fff;font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;text-decoration:none;border:none;cursor:pointer;transition:all 0.25s;white-space:nowrap;}
.btn-edit:hover{background:#ff0055;transform:translateY(-2px);}
.socials{display:flex;gap:8px;}
.socials a{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.05);color:#111111;transition:all 0.2s;}
.socials a:hover{background:#0066ff;color:#fff;}
.socials svg{width:16px;height:16px;fill:currentColor;}

/* ARTWORK GRID */
.art-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:20px;}
.art-card{overflow:hidden;display:flex;flex-direction:column;transition:transform 0.3s ease,box-shadow 0.3s ease;}
.art-card:hover{transform:translateY(-4px);box-shadow:0 20px 40px rgba(0,0,0,0.08);}
.art-img-wrap{position:relative;height:190px;overflow:hidden;}
.art-img{width:100%;height:100%;object-fit:cover;transition:transform 0.5s ease;}
.art-card:hover .art-img{transform:scale(1.05);}
.badge{position:absolute;top:10px;left:10px;padding:4px 11px;border-radius:999px;font-size:12px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;backdrop-filter:blur(8px);}
[dir="rtl"] .badge{left:auto;right:10px;}
.b-available{background:rgba(0,102,255,0.16);color:#0066ff;border:1px solid rgba(0,102,255,0.3);}
.b-sold{background:rgba(255,0,85,0.13);color:#111111;border:1px solid rgba(255,0,85,0.25);}
.b-auction{background:rgba(0,102,255,0.13);color:#0066ff;border:1px solid rgba(0,102,255,0.25);}
.art-body{padding:16px;display:flex;flex-direction:column;gap:3px;flex:1;}
[dir="rtl"] .art-body{text-align:right;}
.art-title{font-size:15px;font-weight:700;color:#111111;}
.art-title-ar{font-size:12px;color:rgba(0,0,0,0.55);}
.art-price{font-size:16px;font-weight:700;color:#0066ff;margin-top:8px;}
.art-actions{display:flex;gap:8px;margin-top:12px;}
.mini-btn{flex:1;padding:8px;border-radius:10px;font-size:12px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;border:1px solid rgba(0,0,0,0.12);background:rgba(255,255,255,0.7);color:#111111;cursor:pointer;text-decoration:none;text-align:center;transition:all 0.2s;}
.mini-btn:hover{background:#111111;color:#fff;}
.mini-btn.danger:hover{background:#ff0055;border-color:#ff0055;color:#fff;}
.empty-msg{padding:40px;text-align:center;font-size:14px;color:rgba(0,0,0,0.5);}

/* NEW ARTWORK + DRAFTS */
.start-card{padding:28px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px;}
.start-card p{font-size:14px;line-height:1.7;color:rgba(0,0,0,0.65);max-width:520px;}
.btn-start{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:52px;padding:14px 28px;border-radius:999px;background:#ff0055;color:#fff;font-size:15px;font-weight:700;text-decoration:none;transition:background 0.2s,transform 0.2s;}
.btn-start:hover{background:var(--color-primary-dark,#e0004b);transform:translateY(-2px);}
.btn-start:active{transform:translateY(0);}
.btn-start:focus-visible,.mini-btn:focus-visible{outline:2px solid #0066ff;outline-offset:2px;}
.draft-list{display:grid;gap:12px;margin-top:14px;}
.draft{display:flex;align-items:center;gap:14px;padding:14px;}
.draft-thumb{width:64px;height:64px;border-radius:12px;object-fit:cover;background:#f4f5f7;flex-shrink:0;}
.draft-info{flex:1;min-width:0;}
.draft-title{font-size:14px;font-weight:700;color:#111111;}
.draft-stages{display:flex;gap:6px;margin-top:6px;flex-wrap:wrap;}
.draft-stages span{font-size:12px;font-weight:600;padding:2px 8px;border-radius:999px;border:1px solid rgba(0,0,0,0.12);color:rgba(0,0,0,0.55);}
.draft-stages span.ok{background:rgba(0,102,255,0.1);border-color:rgba(0,102,255,0.25);color:var(--color-accent-dark,#0047ab);}
.draft .art-actions{margin-top:0;flex-shrink:0;}
.draft .mini-btn{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:8px 14px;font-size:12px;text-decoration:none;}
.b-review{background:rgba(0,0,0,0.7);color:#fff;}
/* Creation Journey on artwork cards */
.art-verified{align-self:flex-start;margin-top:6px;}
.stage-strip{display:flex;gap:6px;margin-top:8px;}
.stage-strip span{position:relative;flex:1;height:56px;border-radius:8px;overflow:hidden;background:#f4f5f7;}
.stage-strip img{width:100%;height:100%;object-fit:cover;display:block;}
.stage-strip b{position:absolute;bottom:3px;inset-inline-start:3px;font-size:12px;font-weight:700;color:#fff;background:rgba(0,0,0,0.6);padding:1px 6px;border-radius:999px;}
.journey-toggle{margin-top:8px;}
.journey-toggle summary{cursor:pointer;font-size:13px;font-weight:700;color:#0066ff;padding:6px 0;list-style:none;}
.journey-toggle summary::-webkit-details-marker{display:none;}
.journey-toggle summary::after{content:' +';}
.journey-toggle[open] summary::after{content:' −';}
.journey-toggle summary:hover{text-decoration:underline;}
.journey-toggle summary:focus-visible{outline:2px solid #0066ff;outline-offset:2px;border-radius:4px;}
.journey-toggle .pj{margin-top:6px;}
.art-card .pj-row{overflow-x:auto;}
.art-card .pj-step{flex:0 0 78%;}
.art-card .pj-step img{height:140px;}
@media(max-width:560px){.draft{flex-wrap:wrap;}.draft .art-actions{width:100%;}}

/* FINANCIAL REPORT */
.fin-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px;}
.fin-card{padding:22px;text-align:center;}
[dir="rtl"] .fin-card{text-align:center;}
.fin-num{font-size:26px;font-weight:700;color:#111;margin-bottom:4px;}
.fin-num.money{background:linear-gradient(90deg,#0066ff,#0066ff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.fin-lbl{font-size:12px;text-transform:uppercase;letter-spacing:0.08em;color:rgba(0,0,0,0.45);font-weight:600;}
.fin-table-wrap{padding:20px 24px;overflow-x:auto;}
.fin-table{width:100%;border-collapse:collapse;font-size:13px;}
.fin-table th,.fin-table td{text-align:left;padding:11px 10px;border-bottom:1px solid rgba(0,0,0,0.06);}
[dir="rtl"] .fin-table th,[dir="rtl"] .fin-table td{text-align:right;}
.fin-table th{font-size:12px;text-transform:uppercase;letter-spacing:0.08em;color:rgba(0,0,0,0.45);}
.fin-table .price{color:#0066ff;font-weight:700;white-space:nowrap;}
.fin-empty{padding:24px;text-align:center;color:rgba(0,0,0,0.45);font-size:13px;}
.saved-box{background:rgba(0,102,255,0.1);border:1px solid rgba(0,102,255,0.3);color:#0066ff;font-size:13px;font-weight:600;padding:11px 14px;border-radius:12px;margin-top:16px;}
@media(max-width:768px){.fin-grid{grid-template-columns:1fr 1fr;}}
@keyframes riseUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
@media(max-width:768px){.topnav{padding:10px 14px;}.nav-logo span{display:none;}.profile-head{flex-direction:column;text-align:center;}[dir="rtl"] .profile-head{flex-direction:column;}.head-actions{align-items:center;}.upload-card .grid2{grid-template-columns:1fr;}.stats-row{justify-content:center;}}
</style>
<link rel="stylesheet" href="/assets/css/proof.css">
</head>
<body>
<div class="bg-wrap"><video class="bg-video" id="vid" autoplay loop muted playsinline src="bg.mp4"></video></div>
<div class="overlay"></div>

<!-- NAV (shared brand navbar) -->
<?php include __DIR__ . "/nav.php"; ?>

<div class="page" id="pg">
<?php if (!$artist): ?>
  <div class="gc empty-msg" style="margin-top:40px;">Artist not found.</div>
<?php else: ?>

  <!-- SECTION 1: PROFILE HEADER -->
  <p class="section-title" id="t-profile-title">Artist Profile</p>
  <div class="gc profile-head">
    <?php if (!empty($artist['profile_picture'])): ?>
      <img class="avatar" src="<?= e($artist['profile_picture']) ?>" alt="<?= e($artist['full_name_en']) ?>">
    <?php else: ?>
      <div class="avatar"><?= e(mb_strtoupper(mb_substr($artist['full_name_en'] ?: 'A', 0, 1))) ?></div>
    <?php endif; ?>

    <div class="profile-info">
      <h1><?= e($artist['full_name_en']) ?></h1>
      <?php if (!empty($artist['full_name_ar'])): ?>
        <div class="name-ar" dir="rtl"><?= e($artist['full_name_ar']) ?></div>
      <?php endif; ?>
      <?php if (!empty($artist['city'])): ?>
        <div class="city">📍 <?= e($artist['city']) ?></div>
      <?php endif; ?>
      <?php if (!empty($artist['bio_en'])): ?>
        <p class="bio"><?= e($artist['bio_en']) ?></p>
      <?php endif; ?>
      <?php if (!empty($artist['bio_ar'])): ?>
        <p class="bio-ar" dir="rtl"><?= e($artist['bio_ar']) ?></p>
      <?php endif; ?>

      <div class="stats-row">
        <div class="stat"><div class="num"><?= (int) $totalCount ?></div><div class="lbl" id="t-stat-total">Total Artworks</div></div>
        <div class="stat"><div class="num"><?= (int) $soldCount ?></div><div class="lbl" id="t-stat-sold">Artworks Sold</div></div>
        <div class="stat"><div class="num"><?= e($memberSince) ?></div><div class="lbl" id="t-stat-since">Member Since</div></div>
      </div>
    </div>

    <div class="head-actions">
      <?php if ($isOwner): ?>
        <a class="btn-edit" href="edit_profile.php" id="t-edit">Edit Profile</a>
      <?php endif; ?>
      <?php if ($ig || $tw || $web): ?>
      <div class="socials">
        <?php if ($ig): ?><a href="<?= e($ig) ?>" target="_blank" rel="noopener" aria-label="Instagram"><svg viewBox="0 0 24 24"><path d="M12 2.2c3.2 0 3.6 0 4.9.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.86s0 3.6-.07 4.86c-.05 1.17-.25 1.8-.41 2.23a3.7 3.7 0 0 1-.9 1.38c-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.86.07s-3.6 0-4.86-.07c-1.17-.05-1.8-.25-2.23-.41a3.7 3.7 0 0 1-1.38-.9 3.7 3.7 0 0 1-.9-1.38c-.16-.42-.36-1.06-.41-2.23C2.2 15.6 2.2 15.2 2.2 12s0-3.6.07-4.86c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.4 2.2 8.8 2.2 12 2.2zm0 3.13A6.67 6.67 0 1 0 18.67 12 6.67 6.67 0 0 0 12 5.33zm0 11A4.33 4.33 0 1 1 16.33 12 4.33 4.33 0 0 1 12 16.33zm6.93-11.2a1.56 1.56 0 1 1-1.56-1.55 1.56 1.56 0 0 1 1.56 1.55z"/></svg></a><?php endif; ?>
        <?php if ($tw): ?><a href="<?= e($tw) ?>" target="_blank" rel="noopener" aria-label="Twitter/X"><svg viewBox="0 0 24 24"><path d="M18.9 2H22l-7.5 8.6L23 22h-6.9l-5.4-7-6.2 7H1.4l8-9.2L1 2h7l4.9 6.5L18.9 2zm-2.4 18h1.9L7.6 4H5.6l10.9 16z"/></svg></a><?php endif; ?>
        <?php if ($web): ?><a href="<?= e($web) ?>" target="_blank" rel="noopener" aria-label="Website"><svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm6.9 6h-2.8a15.7 15.7 0 0 0-1.2-3.1A8 8 0 0 1 18.9 8zM12 4a14 14 0 0 1 1.7 4h-3.4A14 14 0 0 1 12 4zM4.3 14a7.8 7.8 0 0 1 0-4h3.2a16.6 16.6 0 0 0 0 4zm.8 2h2.8a15.7 15.7 0 0 0 1.2 3.1A8 8 0 0 1 5.1 16zm2.8-8H5.1a8 8 0 0 1 4-3.1A15.7 15.7 0 0 0 7.9 8zM12 20a14 14 0 0 1-1.7-4h3.4A14 14 0 0 1 12 20zm2.1-6H9.9a14.7 14.7 0 0 1 0-4h4.2a14.7 14.7 0 0 1 0 4zm.8 5.1a15.7 15.7 0 0 0 1.2-3.1h2.8a8 8 0 0 1-4 3.1zm1.6-5.1a16.6 16.6 0 0 0 0-4h3.2a7.8 7.8 0 0 1 0 4z"/></svg></a><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($isOwner && isset($_GET['saved'])): ?>
    <div class="saved-box" id="t-saved">Profile updated successfully.</div>
  <?php endif; ?>

  <!-- SECTION: FINANCIAL REPORT (owner only) -->
  <?php if ($isOwner): ?>
  <p class="section-title" id="t-fin-title">Financial Report</p>
  <div class="fin-grid">
    <div class="gc fin-card"><div class="fin-num"><?= (int) $totalCount ?></div><div class="fin-lbl" id="t-fin-total">Total Artworks</div></div>
    <div class="gc fin-card"><div class="fin-num"><?= (int) $soldCount ?></div><div class="fin-lbl" id="t-fin-sold">Artworks Sold</div></div>
    <div class="gc fin-card"><div class="fin-num money"><?= number_format($earnYear) ?></div><div class="fin-lbl" id="t-fin-year">Earnings This Year (SAR)</div></div>
    <div class="gc fin-card"><div class="fin-num money"><?= number_format($earnAll) ?></div><div class="fin-lbl" id="t-fin-all">Earnings All Time (SAR)</div></div>
  </div>
  <div class="gc fin-table-wrap">
    <?php if (!$soldList): ?>
      <div class="fin-empty" id="t-fin-empty">No sales yet. Your sold artworks will appear here.</div>
    <?php else: ?>
      <table class="fin-table">
        <thead><tr>
          <th id="t-fin-h-art">Artwork</th>
          <th id="t-fin-h-date">Sale Date</th>
          <th id="t-fin-h-price">Price</th>
        </tr></thead>
        <tbody>
        <?php foreach ($soldList as $s): ?>
          <tr>
            <td><?= e($s['title_en'] ?: ($s['title_ar'] ?: 'N/A')) ?><?php if (!empty($s['title_ar'])): ?><div class="art-title-ar" dir="rtl"><?= e($s['title_ar']) ?></div><?php endif; ?></td>
            <td><?= e($s['date']) ?></td>
            <td class="price"><?= number_format($s['price']) ?> <span class="sar">SAR</span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- SECTION 2: ARTWORKS -->
  <p class="section-title" id="t-works-title">Artworks</p>
  <?php if ($totalCount === 0): ?>
    <div class="gc empty-msg" id="t-empty">No artworks yet. Start by uploading your first piece.</div>
  <?php else: ?>
    <div class="art-grid">
      <?php foreach ($artworks as $aw):
        $st = $aw['status']; // available | sold | auction
        $badgeClass = $st === 'sold' ? 'b-sold' : ($st === 'auction' ? 'b-auction' : 'b-available');
      ?>
      <div class="gc art-card">
        <div class="art-img-wrap">
          <img class="art-img" src="<?= e($aw['image_url']) ?>" alt="<?= e($aw['title_en'] . ($aw['title_ar'] !== '' ? ' · ' . $aw['title_ar'] : '')) ?>">
          <?php if ($isOwner && (int) $aw['is_approved'] === 0): ?>
          <span class="badge b-review" data-status="review">In review</span>
          <?php else: ?>
          <span class="badge <?= $badgeClass ?>" data-status="<?= e($st) ?>"><?= e(ucfirst($st)) ?></span>
          <?php endif; ?>
        </div>
        <div class="art-body">
          <div class="art-title"><?= e($aw['title_en']) ?></div>
          <div class="art-title-ar" dir="rtl"><?= e($aw['title_ar']) ?></div>
          <?php $stg = $artStages[(int) $aw['id']] ?? [];
          if (owHasJourney($stg)): ?>
          <span class="verified-badge art-verified"><?= owBi('✓ Verified Human Artist', '✓ فنان بشري موثّق') ?></span>
          <div class="stage-strip" aria-hidden="true">
            <?php foreach (OW_STAGES as $n): if (!isset($stg[$n]) || !owSafeStagePath((string) $stg[$n]['photo_path'])) continue; ?>
              <span><img loading="lazy" src="<?= e($stg[$n]['photo_path']) ?>" alt=""><b><?= $n ?>%</b></span>
            <?php endforeach; ?>
          </div>
          <details class="journey-toggle">
            <summary><?= owBi('See the creation journey', 'شاهد رحلة الإبداع') ?></summary>
            <?= owJourneyHtml($stg, $artColors[(int) $aw['id']] ?? [], (string) $aw['type'], $ART_TYPES) ?>
          </details>
          <?php endif; ?>
          <div class="art-price"><?= number_format((float) $aw['price']) ?> <span class="sar">SAR</span></div>
          <?php if ($isOwner): ?>
          <div class="art-actions">
            <a class="mini-btn" href="edit_artwork.php?id=<?= (int) $aw['id'] ?>" data-t="edit">Edit</a>
            <form method="post" action="artist_dashboard.php?id=<?= (int) $profileId ?>" style="flex:1;margin:0;"
                  onsubmit="return confirm('Delete this artwork?');">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="artwork_id" value="<?= (int) $aw['id'] ?>">
              <button type="submit" class="mini-btn danger" style="width:100%;" data-t="delete">Delete</button>
            </form>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- SECTION 3: NEW ARTWORK + DRAFTS (owner only) -->
  <?php if ($isOwner): ?>
  <p class="section-title" id="t-upload-title">New Artwork</p>
  <div class="gc start-card">
    <p id="t-start-desc">Show the real journey of your work: photograph it at 25%, 50% and 100%. Your progress is saved after every step.</p>
    <a class="btn-start" href="upload_artwork.php" id="t-start">Start New Artwork</a>
  </div>
  <?php if ($drafts): ?>
  <p class="section-title" id="t-drafts-title">Drafts in progress</p>
  <div class="draft-list">
    <?php foreach ($drafts as $d):
      $ds = $draftStages[(int) $d['id']] ?? [];
      $thumb = $ds[100]['photo_path'] ?? ($ds[50]['photo_path'] ?? ($ds[25]['photo_path'] ?? ''));
    ?>
    <div class="gc draft">
      <?php if ($thumb): ?>
        <img class="draft-thumb" src="<?= e($thumb) ?>" alt="<?= e($d['title_en'] . ' · ' . $d['title_ar']) ?>">
      <?php else: ?>
        <div class="draft-thumb" aria-hidden="true"></div>
      <?php endif; ?>
      <div class="draft-info">
        <div class="draft-title"><?= e($d['title_en']) ?> · <span dir="rtl"><?= e($d['title_ar']) ?></span></div>
        <div class="draft-stages">
          <?php foreach (OW_STAGES as $stg): ?>
            <span class="<?= isset($ds[$stg]) ? 'ok' : '' ?>"><?= $stg ?>%<?= isset($ds[$stg]) ? ' ✓' : '' ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="art-actions">
        <a class="mini-btn" href="upload_artwork.php?id=<?= (int) $d['id'] ?>" data-t="continue">Continue</a>
        <form method="post" action="artist_dashboard.php?id=<?= (int) $profileId ?>" style="margin:0;"
              onsubmit="return confirm(document.documentElement.dir === 'rtl' ? 'حذف هذه المسودة؟' : 'Delete this draft?');">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="artwork_id" value="<?= (int) $d['id'] ?>">
          <button type="submit" class="mini-btn danger" data-t="delete">Delete</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>

<?php endif; ?>
</div>

<script>
const IS_OWNER = <?= $isOwner ? 'true' : 'false' ?>;

const T={
  en:{dir:'ltr',lb:'العربية',
    profileTitle:'Artist Profile',worksTitle:'Artworks',uploadTitle:'New Artwork',start:'Start New Artwork',startDesc:'Show the real journey of your work: photograph it at 25%, 50% and 100%. Your progress is saved after every step.',draftsTitle:'Drafts in progress',cardContinue:'Continue',review:'In review',
    statTotal:'Total Artworks',statSold:'Artworks Sold',statSince:'Member Since',
    finTitle:'Financial Report',finTotal:'Total Artworks',finSold:'Artworks Sold',finYear:'Earnings This Year (SAR)',finAll:'Earnings All Time (SAR)',
    finEmpty:'No sales yet. Your sold artworks will appear here.',finHArt:'Artwork',finHDate:'Sale Date',finHPrice:'Price',saved:'Profile updated successfully.',
    edit:'Edit Profile',empty:'No artworks yet. Start by uploading your first piece.',
    abstract:'Abstract',landscape:'Landscape',portrait:'Portrait',other:'Other',
    cardEdit:'Edit',cardDelete:'Delete',
    available:'Available',sold:'Sold',auction:'Auction',sar:'SAR',
    nav:{studio:'The Studio',community:'Community',marketplace:'Marketplace',explore:'Explore Art Styles',support:'Support',
      s1:'Watercolor Workshop',s2:'Oil Painting Studio',s3:'Digital Art Lab',s4:'Charcoal & Ink',
      c1:'Artist Chat Room',c2:'Meet Fellow Artists',c3:'Share Your Work',c4:'Learn Together',
      sp1:'Contact Us',sp2:'How It Works',sp3:'Terms of Use',login:'Sign In',reg:'Join Free'}},
  ar:{dir:'rtl',lb:'English',
    profileTitle:'الملف الشخصي للفنان',worksTitle:'الأعمال الفنية',uploadTitle:'عمل فني جديد',start:'ابدأ عملاً فنياً جديداً',startDesc:'اعرض الرحلة الحقيقية لعملك: صوّره عند 25% و50% و100%. يُحفظ تقدّمك بعد كل خطوة.',draftsTitle:'مسودات قيد العمل',cardContinue:'متابعة',review:'قيد المراجعة',
    statTotal:'إجمالي الأعمال',statSold:'الأعمال المباعة',statSince:'عضو منذ',
    finTitle:'التقرير المالي',finTotal:'إجمالي الأعمال',finSold:'الأعمال المباعة',finYear:'أرباح هذا العام (ر.س)',finAll:'إجمالي الأرباح (ر.س)',
    finEmpty:'لا توجد مبيعات بعد. ستظهر أعمالك المباعة هنا.',finHArt:'العمل الفني',finHDate:'تاريخ البيع',finHPrice:'السعر',saved:'تم تحديث الملف الشخصي بنجاح.',
    edit:'تعديل الملف الشخصي',empty:'لا توجد أعمال بعد. ابدأ برفع عملك الأول.',
    abstract:'تجريدي',landscape:'طبيعي',portrait:'بورتريه',other:'أخرى',
    cardEdit:'تعديل',cardDelete:'حذف',
    available:'متاح',sold:'مباع',auction:'مزاد',sar:'ر.س',
    nav:{studio:'الاستوديو',community:'المجتمع',marketplace:'السوق',explore:'استكشف أساليب الرسم',support:'الدعم',
      s1:'ورشة الألوان المائية',s2:'استوديو الرسم الزيتي',s3:'مختبر الفن الرقمي',s4:'الفحم والحبر',
      c1:'غرفة محادثة الفنانين',c2:'تعرّف على فنانين',c3:'شارك أعمالك',c4:'تعلّم معاً',
      sp1:'تواصل معنا',sp2:'كيف يعمل الموقع',sp3:'شروط الاستخدام',login:'تسجيل الدخول',reg:'انضم مجاناً'}}
};
let L='en';
function setTxt(id,v){const el=document.getElementById(id);if(el)el.textContent=v;}
function apply(l){
  const t=T[l];
  document.getElementById('html').lang=l;
  document.getElementById('html').setAttribute('dir',t.dir);
  document.documentElement.setAttribute('dir',t.dir);
  document.getElementById('topnav').setAttribute('dir',t.dir);
  document.getElementById('lb').textContent=t.lb;
  setTxt('t-profile-title',t.profileTitle);setTxt('t-works-title',t.worksTitle);setTxt('t-upload-title',t.uploadTitle);
  setTxt('t-stat-total',t.statTotal);setTxt('t-stat-sold',t.statSold);setTxt('t-stat-since',t.statSince);
  setTxt('t-fin-title',t.finTitle);setTxt('t-fin-total',t.finTotal);setTxt('t-fin-sold',t.finSold);
  setTxt('t-fin-year',t.finYear);setTxt('t-fin-all',t.finAll);setTxt('t-fin-empty',t.finEmpty);
  setTxt('t-fin-h-art',t.finHArt);setTxt('t-fin-h-date',t.finHDate);setTxt('t-fin-h-price',t.finHPrice);setTxt('t-saved',t.saved);
  setTxt('t-edit',t.edit);setTxt('t-empty',t.empty);
  setTxt('t-start',t.start);setTxt('t-start-desc',t.startDesc);setTxt('t-drafts-title',t.draftsTitle);
  document.querySelectorAll('[data-t="continue"]').forEach(el=>el.textContent=t.cardContinue);
  document.querySelectorAll('.badge').forEach(b=>{const s=b.dataset.status;if(t[s])b.textContent=t[s];});
  document.querySelectorAll('.sar').forEach(s=>s.textContent=t.sar);
  document.querySelectorAll('[data-t="edit"]').forEach(el=>el.textContent=t.cardEdit);
  document.querySelectorAll('[data-t="delete"]').forEach(el=>el.textContent=t.cardDelete);
  const n=t.nav;
  setTxt('nav-studio-label',n.studio);setTxt('nav-community-label',n.community);
  setTxt('nav-marketplace-label',n.marketplace);setTxt('nav-explore-label',n.explore);setTxt('nav-support-label',n.support);
  setTxt('dd-s1',n.s1);setTxt('dd-s2',n.s2);setTxt('dd-s3',n.s3);setTxt('dd-s4',n.s4);
  setTxt('dd-c1',n.c1);setTxt('dd-c2',n.c2);setTxt('dd-c3',n.c3);setTxt('dd-c4',n.c4);
  setTxt('dd-sp1',n.sp1);setTxt('dd-sp2',n.sp2);setTxt('dd-sp3',n.sp3);
  setTxt('n-login-t',n.login);setTxt('n-reg-t',n.reg);
}
function tgl(){L=L==='en'?'ar':'en';apply(L);try{localStorage.setItem('lang',L);}catch(e){}}
try{var _s=localStorage.getItem('lang');if(_s==='ar'||_s==='en')L=_s;}catch(e){}
apply(L);
const v=document.getElementById('vid');
if(v){v.addEventListener('canplay',()=>v.classList.add('on'),{once:true});v.play().catch(()=>{});}
</script>
</body>
</html>
