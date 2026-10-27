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
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="/assets/css/oweili.css?v=<?= @filemtime(__DIR__ . '/assets/css/oweili.css') ?>">
<style>
/* Artist dashboard / public profile — layout only; cards, buttons, stats, tables,
   artwork cards and stage squares come from oweili.css (role color: artist pink) */
.page{position:relative;z-index:10;}
.section-title{font-size:var(--fs-subtitle);font-weight:700;color:var(--color-dark);margin:var(--space-xl) 0 var(--space-sm);}
.section-title:first-child{margin-top:0;}
/* PROFILE HEADER */
.profile-head{display:flex;gap:var(--space-lg);align-items:center;padding:var(--space-lg);}
.avatar{width:120px;height:120px;border-radius:50%;object-fit:cover;flex-shrink:0;border:3px solid var(--color-bg);box-shadow:var(--shadow-card);background:var(--color-artist);display:flex;align-items:center;justify-content:center;color:var(--color-on-primary);font-size:42px;font-weight:700;}
.profile-info{flex:1;min-width:0;}
.profile-info h1{font-size:var(--fs-section);margin:0 0 4px;}
.profile-info .name-ar{font-size:var(--fs-subtitle);color:var(--color-body);margin-bottom:var(--space-xs);}
.profile-info .city{font-size:15px;font-weight:600;color:var(--color-dark);margin-bottom:var(--space-xs);display:flex;align-items:center;gap:6px;}
.profile-info .bio,.profile-info .bio-ar{max-width:640px;margin:0 0 var(--space-xs);}
.stats-row{display:flex;flex-wrap:wrap;gap:var(--space-lg);margin-top:var(--space-sm);}
.stat .num{font-size:var(--fs-card);font-weight:700;color:var(--color-dark);}
.stat .lbl{font-size:var(--fs-label);font-weight:600;color:var(--color-body);}
.head-actions{display:flex;flex-direction:column;gap:var(--space-sm);align-items:flex-end;flex-shrink:0;}
[dir="rtl"] .head-actions{align-items:flex-start;}
.socials{display:flex;gap:var(--space-xs);}
.socials a{width:40px;height:40px;border-radius:var(--radius-btn);display:flex;align-items:center;justify-content:center;border:1px solid var(--color-border-strong);color:var(--color-dark);transition:background var(--ease),color var(--ease);}
.socials a:hover{background:var(--color-dark);color:var(--color-on-dark);}
.socials svg{width:16px;height:16px;fill:currentColor;}
/* ARTWORKS — 4 columns desktop, 2 tablet, 1 phone */
.art-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:var(--space-md);}
.aw-review{background:var(--color-dark);color:var(--color-on-dark);border-color:var(--color-dark);}
.art-actions{display:flex;gap:var(--space-xs);margin-top:var(--space-sm);}
.art-actions > *{flex:1;}
.art-actions form{margin:0;display:flex;}
.art-actions form .btn{flex:1;}
.aw-body .stages{margin-top:var(--space-xs);}
.aw-body .stages-strip{--stage-size:56px;}
.journey-toggle{margin-top:var(--space-xs);}
.journey-toggle summary{cursor:pointer;font-size:var(--fs-label);font-weight:700;color:var(--color-accent);padding:6px 0;list-style:none;}
.journey-toggle summary::-webkit-details-marker{display:none;}
.journey-toggle summary::after{content:' +';}
.journey-toggle[open] summary::after{content:' −';}
.journey-toggle summary:hover{text-decoration:underline;}
.journey-toggle summary:focus-visible{outline:2px solid var(--color-accent);outline-offset:2px;border-radius:4px;}
.journey-toggle .pj{margin-top:6px;}
/* NEW ARTWORK + DRAFTS */
.start-card{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:var(--space-sm);}
.start-card p{max-width:560px;margin:0;}
.draft-list{display:grid;gap:var(--space-sm);}
.draft{display:flex;align-items:center;gap:var(--space-md);flex-wrap:wrap;}
.draft-info{flex:1;min-width:220px;}
.draft-title{font-size:var(--fs-body);font-weight:700;color:var(--color-dark);margin-bottom:var(--space-xs);}
.draft .stages{--stage-size:64px;}
.draft .art-actions{margin-top:0;flex-shrink:0;}
/* FINANCIAL REPORT */
.fin-price{font-weight:700;white-space:nowrap;}
.art-title-ar{font-size:var(--fs-caption);color:var(--color-body);}
.fin-empty{text-align:center;color:var(--color-body);padding:var(--space-md);}
@media(max-width:1024px){.art-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:768px){.profile-head{flex-direction:column;text-align:center;padding:var(--space-md);}.head-actions{align-items:center;}[dir="rtl"] .head-actions{align-items:center;}.stats-row{justify-content:center;}.art-grid{gap:var(--space-sm);}}
@media(max-width:480px){.art-grid{grid-template-columns:1fr;}.draft .art-actions{width:100%;}}
</style>
<link rel="stylesheet" href="/assets/css/proof.css?v=<?= @filemtime(__DIR__ . '/assets/css/proof.css') ?>">
</head>
<body>
<div class="bg-wrap"><video class="bg-video" id="vid" autoplay loop muted playsinline src="bg.mp4"></video></div>
<div class="overlay"></div>

<!-- NAV (shared brand navbar) -->
<?php include __DIR__ . "/nav.php"; ?>

<div class="page dash role-artist dash-wrap" id="pg">
<?php if (!$artist): ?>
  <div class="card empty-state">Artist not found.</div>
<?php else: ?>

  <!-- SECTION 1: PROFILE HEADER -->
  <p class="section-title" id="t-profile-title">Artist Profile</p>
  <div class="card profile-head">
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
        <a class="btn btn-secondary" href="edit_profile.php" id="t-edit">Edit Profile</a>
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
    <div class="ok-box" id="t-saved" style="margin-top:var(--space-sm);">Profile updated successfully.</div>
  <?php endif; ?>

  <!-- SECTION: FINANCIAL REPORT (owner only) -->
  <?php if ($isOwner): ?>
  <p class="section-title" id="t-fin-title">Financial Report</p>
  <div class="stat-grid" style="margin-bottom:var(--space-md);">
    <div class="stat-card"><span class="stat-num"><?= (int) $totalCount ?></span><span class="stat-lbl" id="t-fin-total">Total Artworks</span></div>
    <div class="stat-card"><span class="stat-num"><?= (int) $soldCount ?></span><span class="stat-lbl" id="t-fin-sold">Artworks Sold</span></div>
    <div class="stat-card"><span class="stat-num"><?= number_format($earnYear) ?></span><span class="stat-lbl" id="t-fin-year">Earnings This Year (SAR)</span></div>
    <div class="stat-card"><span class="stat-num"><?= number_format($earnAll) ?></span><span class="stat-lbl" id="t-fin-all">Earnings All Time (SAR)</span></div>
  </div>
  <div class="table-wrap">
    <?php if (!$soldList): ?>
      <div class="fin-empty" id="t-fin-empty">No sales yet. Your sold artworks will appear here.</div>
    <?php else: ?>
      <table class="table">
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
            <td class="fin-price"><?= number_format($s['price']) ?> <span class="sar">SAR</span></td>
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
    <div class="card empty-state" id="t-empty">No artworks yet. Start by uploading your first piece.</div>
  <?php else: ?>
    <div class="art-grid">
      <?php foreach ($artworks as $aw):
        $st = $aw['status']; // available | sold | auction
      ?>
      <article class="aw-card">
        <div class="aw-img">
          <img loading="lazy" src="<?= e($aw['image_url']) ?>" alt="<?= e($aw['title_en'] . ($aw['title_ar'] !== '' ? ' · ' . $aw['title_ar'] : '')) ?>">
          <?php if ($isOwner && (int) $aw['is_approved'] === 0): ?>
          <span class="aw-status aw-review badge" data-status="review">In review</span>
          <?php else: ?>
          <span class="aw-status badge" data-status="<?= e($st) ?>"><?= e(ucfirst($st)) ?></span>
          <?php endif; ?>
        </div>
        <div class="aw-body">
          <div class="aw-title"><?= e($aw['title_en']) ?></div>
          <?php if ($aw['title_ar'] !== ''): ?><div class="art-title-ar" dir="rtl" lang="ar"><?= e($aw['title_ar']) ?></div><?php endif; ?>
          <?php $stg = $artStages[(int) $aw['id']] ?? [];
          if (owHasJourney($stg)): ?>
          <span class="human-badge"><?= owBi('✓ Human Art', '✓ فن بشري') ?></span>
          <?= owStagesHtml($stg, 'strip') ?>
          <details class="journey-toggle">
            <summary><?= owBi('See the creation journey', 'شاهد رحلة الإبداع') ?></summary>
            <?= owJourneyHtml($stg, $artColors[(int) $aw['id']] ?? [], (string) $aw['type'], $ART_TYPES) ?>
          </details>
          <?php endif; ?>
          <div class="aw-price"><?= number_format((float) $aw['price']) ?> <span class="sar">SAR</span></div>
          <?php if ($isOwner): ?>
          <div class="art-actions">
            <a class="btn btn-ghost btn-sm" href="edit_artwork.php?id=<?= (int) $aw['id'] ?>" data-t="edit">Edit</a>
            <form method="post" action="artist_dashboard.php?id=<?= (int) $profileId ?>"
                  onsubmit="return confirm('Delete this artwork?');">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="artwork_id" value="<?= (int) $aw['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-t="delete">Delete</button>
            </form>
          </div>
          <?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- SECTION 3: NEW ARTWORK + DRAFTS (owner only) -->
  <?php if ($isOwner): ?>
  <p class="section-title" id="t-upload-title">New Artwork</p>
  <div class="card start-card">
    <p id="t-start-desc">Show the real journey of your work: photograph it at 25%, 50% and 100%. Your progress is saved after every step.</p>
    <a class="btn btn-primary" href="upload_artwork.php" id="t-start">Start New Artwork</a>
  </div>
  <?php if ($drafts): ?>
  <p class="section-title" id="t-drafts-title">Drafts in progress</p>
  <div class="draft-list">
    <?php foreach ($drafts as $d):
      $ds = $draftStages[(int) $d['id']] ?? [];
          ?>
    <div class="card draft">
      <div class="draft-info">
        <div class="draft-title"><?= e($d['title_en']) ?> · <span dir="rtl"><?= e($d['title_ar']) ?></span></div>
        <?= owStagesHtml($ds, 'strip') ?>
      </div>
      <div class="art-actions">
        <a class="btn btn-accent btn-sm" href="upload_artwork.php?id=<?= (int) $d['id'] ?>" data-t="continue">Continue</a>
        <form method="post" action="artist_dashboard.php?id=<?= (int) $profileId ?>"
              onsubmit="return confirm(document.documentElement.dir === 'rtl' ? 'حذف هذه المسودة؟' : 'Delete this draft?');">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="artwork_id" value="<?= (int) $d['id'] ?>">
          <button type="submit" class="btn btn-danger btn-sm" data-t="delete">Delete</button>
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
    nav:{community:'Community',marketplace:'Gallery',explore:'Explore Art Styles',support:'Support',
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
    nav:{community:'المجتمع',marketplace:'المعرض',explore:'استكشف أساليب الرسم',support:'الدعم',
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
  setTxt('nav-community-label',n.community);
  setTxt('nav-marketplace-label',n.marketplace);setTxt('nav-explore-label',n.explore);setTxt('nav-support-label',n.support);
  setTxt('dd-c1',n.c1);setTxt('dd-c2',n.c2);setTxt('dd-c3',n.c3);setTxt('dd-c4',n.c4);
  setTxt('dd-sp1',n.sp1);setTxt('dd-sp2',n.sp2);setTxt('dd-sp3',n.sp3);
  setTxt('n-login-t',n.login);setTxt('n-reg-t',n.reg);
}
function tgl(){L=L==='en'?'ar':'en';apply(L);try{localStorage.setItem('lang',L);}catch(e){}}
try{var _s=localStorage.getItem('lang');if(_s==='ar'||_s==='en')L=_s;}catch(e){}
apply(L);
const v=document.getElementById('vid');
if(v){{const on=()=>v.classList.add('on');if(v.readyState>=3)on();v.addEventListener('canplay',on,{once:true});v.addEventListener('playing',on,{once:true});}v.play().catch(()=>{});}
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
