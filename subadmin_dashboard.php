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
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
body{min-height:100vh;background:#f4f5f7;font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;color:#111111;padding:40px 20px;}
.wrap{max-width:900px;margin:0 auto;}
h1{font-size:24px;font-weight:700;margin-bottom:4px;}
.sub{font-size:13px;color:rgba(0,0,0,0.5);margin-bottom:24px;}
.card{background:#fff;border:1px solid rgba(0,0,0,0.08);border-radius:18px;box-shadow:0 10px 30px rgba(0,0,0,0.05);padding:26px;}
.btn{padding:11px 22px;border-radius:999px;background:#111111;color:#fff;font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;border:none;cursor:pointer;transition:all 0.22s;text-decoration:none;display:inline-block;}
.btn:hover{background:#ff0055;}
.btn.sm{padding:7px 14px;font-size:12px;}
.btn.green{background:#0066ff;}.btn.green:hover{background:#0066ff;}
.btn.red{background:#ff0055;}.btn.red:hover{background:#111111;}
.btn.grey{background:rgba(0,0,0,0.08);color:#111111;}.btn.grey:hover{background:rgba(0,0,0,0.16);}
.lang-btn{padding:9px 16px;border-radius:999px;font-size:12px;font-weight:700;letter-spacing:0.04em;cursor:pointer;border:1px solid rgba(0,0,0,0.1);background:#fff;color:rgba(0,0,0,0.7);transition:all 0.2s;}
.lang-btn:hover{background:#111;color:#fff;}
.top{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;gap:12px;flex-wrap:wrap;}
.sec-head h2{font-size:16px;font-weight:700;display:flex;align-items:center;gap:8px;margin-bottom:16px;}
.count{background:#ff0055;color:#fff;font-size:12px;font-weight:700;padding:2px 9px;border-radius:999px;}
.art-list{display:flex;flex-direction:column;gap:12px;}
.art-item{display:flex;flex-wrap:wrap;align-items:center;gap:16px;padding:12px;border:1px solid rgba(0,0,0,0.07);border-radius:14px;}
.art-thumb{width:64px;height:64px;border-radius:10px;background-size:cover;background-position:center;background-color:#eee;flex-shrink:0;}
.art-meta{flex:1;min-width:0;}
.art-proof{flex-basis:100%;min-width:0;}
.art-proof .pj{margin-top:4px;}
.art-meta strong{font-size:14px;}
.name-ar{font-size:12px;color:rgba(0,0,0,0.45);}
.art-info{font-size:12px;color:rgba(0,0,0,0.5);margin-top:3px;}
.acts{display:flex;gap:6px;flex-wrap:wrap;}
.empty{padding:30px;text-align:center;color:rgba(0,0,0,0.4);}
[dir="rtl"] body{direction:rtl;}

</style>
<link rel="stylesheet" href="/assets/css/proof.css">
</head>
<body>
<div class="wrap">
  <div class="top">
    <div>
      <h1>Artwork Approvals</h1>
      <div class="sub">Review and approve or reject pending artwork submissions.</div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
      <button class="lang-btn" id="lb" onclick="tglLang()">العربية</button>
      <a class="btn" href="admin_articles.php">Articles</a>
      <a class="btn grey" href="logout.php">Log out</a>
    </div>
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
            <form method="post" action="subadmin_dashboard.php"><?= csrfField() ?><input type="hidden" name="artwork_id" value="<?= (int)$art['id'] ?>"><button class="btn sm green" name="action" value="approve_art">Approve</button></form>
            <form method="post" action="subadmin_dashboard.php" onsubmit="return confirm('Reject and permanently delete this artwork?');"><?= csrfField() ?><input type="hidden" name="artwork_id" value="<?= (int)$art['id'] ?>"><button class="btn sm red" name="action" value="reject_art">Reject</button></form>
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
  "Articles":"المقالات",
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
