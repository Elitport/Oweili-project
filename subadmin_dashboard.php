<?php
require_once 'config.php';
require_once __DIR__ . '/artwork_lib.php';

/* =====================================================================
 *  Oweili · subadmin_dashboard.php
 *  Sub-admins (and full admins) can approve/reject pending artworks ONLY.
 *  No financial data, no user bans, no other management.
 * ===================================================================== */

if (!isLoggedIn()) { header('Location: login.php'); exit; }
$me = currentUser();
if (!$me) { header('Location: login.php'); exit; }

$role = $me['role'] ?? '';
/* Only sub_admins and full admins may access this page. Everyone else is
   redirected to their own dashboard — no cross-role access. */
if ($role !== 'sub_admin' && $role !== 'admin') {
    header('Location: ' . dashboardUrl($role));
    exit;
}

/* ---- Approve / reject actions (CSRF-checked) ---- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) { http_response_code(403); exit('Invalid CSRF token.'); }
    $action = $_POST['action'];
    $awId   = (int) ($_POST['artwork_id'] ?? 0);
    if ($awId > 0 && ($action === 'approve_art' || $action === 'reject_art')) {
        if ($action === 'approve_art') {
            getDB()->prepare('UPDATE artworks SET is_approved = 1 WHERE id = :id' . owNotDraftSql())->execute([':id' => $awId]);
        } else {
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
    header('Location: subadmin_dashboard.php?ok=1');
    exit;
}

/* ---- Load pending artworks ---- */
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

$firstName = trim((string) ($me['full_name_en'] ?: $me['email']));
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sub-admin · Oweili</title>
<link rel="stylesheet" href="/assets/css/oweili.css">
<style>
/* Sub-admin review panel — staff layout only; buttons, cards and stat cards come from oweili.css (role color: sub-admin green) */
.wrap{max-width:960px;margin:0 auto;padding:var(--space-lg);}
.sub{font-size:var(--fs-body);color:var(--color-body);margin:4px 0 0;}
.top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:var(--space-md);gap:var(--space-sm);flex-wrap:wrap;}
.top h1{font-size:var(--fs-section);margin:0;}
.top-actions{display:flex;flex-wrap:wrap;gap:var(--space-xs);align-items:center;}
.stat-grid{grid-template-columns:repeat(3,1fr);margin-bottom:var(--space-lg);}
.sec-head h2{font-size:var(--fs-subtitle);display:flex;align-items:center;gap:var(--space-xs);margin:0 0 var(--space-sm);}
.count{background:var(--color-dark);color:#fff;font-size:var(--fs-min);font-weight:700;padding:2px 9px;border-radius:var(--radius-pill);}
.art-list{display:flex;flex-direction:column;gap:var(--space-sm);}
.art-item{display:flex;flex-wrap:wrap;align-items:center;gap:var(--space-sm);padding:var(--space-sm);border:1px solid var(--color-border);border-radius:var(--radius-card);background:#fff;}
.art-thumb{width:72px;height:72px;border-radius:var(--radius-input);background-size:contain;background-repeat:no-repeat;background-position:center;background-color:#fff;border:1px solid var(--color-border);flex-shrink:0;}
.art-meta{flex:1;min-width:0;}
.art-proof{flex-basis:100%;min-width:0;}
.art-proof .pj{margin-top:4px;}
.art-meta strong{font-size:var(--fs-body);color:var(--color-dark);}
.name-ar{font-size:var(--fs-caption);color:var(--color-body);}
.art-info{font-size:var(--fs-caption);color:var(--color-body);margin-top:3px;}
.acts{display:flex;gap:var(--space-xs);flex-wrap:wrap;}
.empty{padding:var(--space-lg);text-align:center;color:var(--color-body);}
@media(max-width:768px){.wrap{padding:var(--space-sm);}.stat-grid{grid-template-columns:1fr 1fr;}}
</style>
<link rel="stylesheet" href="/assets/css/proof.css">
</head>
<body class="staff-page">
<div class="wrap dash role-subadmin">
  <div class="top">
    <div>
      <h1>Artwork Approvals</h1>
      <div class="sub">Review and approve or reject pending artwork submissions.</div>
    </div>
    <div class="top-actions">
      <button class="btn btn-ghost btn-sm lang-btn" id="lb" onclick="tglLang()">العربية</button>
      <a class="btn btn-dark btn-sm" href="admin_articles.php">Articles</a>
      <a class="btn btn-ghost btn-sm" href="logout.php">Log out</a>
    </div>
  </div>

  <div class="stat-grid">
    <div class="stat-card"><span class="stat-num"><?= count($pendingArt) ?></span><span class="stat-lbl">Pending Artworks</span></div>
    <div class="stat-card"><span class="stat-num"><?= count(array_filter($pendingArt, fn($x) => !empty($pendingStages[(int) $x['id']]))) ?></span><span class="stat-lbl">With Proof of Process</span></div>
    <div class="stat-card"><span class="stat-num"><?= count(array_filter($pendingArt, fn($x) => empty($pendingStages[(int) $x['id']]))) ?></span><span class="stat-lbl">Without Stage Photos</span></div>
  </div>

  <div class="card">
    <div class="sec-head"><h2>Pending Artworks <span class="count"><?= count($pendingArt) ?></span></h2></div>
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
            <form method="post" action="subadmin_dashboard.php"><?= csrfField() ?><input type="hidden" name="artwork_id" value="<?= (int)$art['id'] ?>"><button class="btn btn-accent btn-sm" name="action" value="approve_art">Approve</button></form>
            <form method="post" action="subadmin_dashboard.php" onsubmit="return confirm('Reject and permanently delete this artwork?');"><?= csrfField() ?><input type="hidden" name="artwork_id" value="<?= (int)$art['id'] ?>"><button class="btn btn-danger btn-sm" name="action" value="reject_art">Reject</button></form>
          </div>
          <div class="art-proof"><?= owProofPanelHtml($pendingStages[(int)$art['id']] ?? [], $pendingColors[(int)$art['id']] ?? [], (string)$art['type'], $ART_TYPES) ?></div>
        </div>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
/* Bilingual — swap known EN strings to AR (and back) by walking text nodes. */
const ADICT = {
  "Artwork Approvals":"اعتماد الأعمال الفنية",
  "Review and approve or reject pending artwork submissions.":"راجع الأعمال المُرسلة ووافق عليها أو ارفضها.",
  "Log out":"تسجيل الخروج",
  "Articles":"المقالات","With Proof of Process":"مع دليل المراحل","Without Stage Photos":"بدون صور مراحل",
  "Pending Artworks":"الأعمال المعلّقة",
  "Nothing pending — all caught up. 🎉":"لا يوجد معلّق — كل شيء محدّث. 🎉",
  "Approve":"موافقة","Reject":"رفض","Untitled":"بدون عنوان","Unknown artist":"فنان غير معروف"
};
const AREV = {}; Object.keys(ADICT).forEach(k=>AREV[ADICT[k]]=k);
let AL='en';
function walkTranslate(root, toAr){
  const map = toAr ? ADICT : AREV;
  const w=document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null);
  const nodes=[]; while(w.nextNode()) nodes.push(w.currentNode);
  nodes.forEach(n=>{ const k=n.nodeValue.trim(); if(k && map[k]!==undefined) n.nodeValue=n.nodeValue.replace(k, map[k]); });
}
function applyLang(l){
  const toAr=l==='ar';
  document.documentElement.setAttribute('dir', toAr?'rtl':'ltr');
  document.documentElement.lang=l;
  if(l!==AL){ walkTranslate(document.body, toAr); AL=l; }
  const lb=document.getElementById('lb'); if(lb) lb.textContent = toAr ? 'English':'العربية';
}
function tglLang(){ const next=AL==='en'?'ar':'en'; try{localStorage.setItem('lang',next);}catch(e){} applyLang(next); }
try{var _s=localStorage.getItem('lang'); if(_s==='ar') applyLang('ar');}catch(e){}
</script>
</body>
</html>
