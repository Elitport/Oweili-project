<?php
/* =====================================================================
 *  signature.php — the artist draws their signature once (finger or mouse).
 *  It appears on all their Certificates of Authenticity. Only the artist
 *  sees this page; the signature is kept private (sig_lib.php) and never
 *  shown on a public page. Linked from Edit Profile and My Studio.
 *  A new, changed or removed signature waits for the admin (owner, 9 Nov
 *  2026, Admin → Waiting for approval); certificates keep the last approved
 *  signature, or none, until then.
 *  Tables: users (who is signed in), pending_changes, activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/artwork_lib.php';
require_once __DIR__ . '/platform_lib.php';
require_once __DIR__ . '/sig_lib.php';
require_once __DIR__ . '/approval_lib.php';

if (!isLoggedIn()) { header('Location: login.php?next=signature.php'); exit; }
$me = currentUser();
if (!$me) { header('Location: login.php'); exit; }
if (($me['role'] ?? '') !== 'artist') { header('Location: ' . dashboardUrl($me['role'] ?? '')); exit; }
$uid = (int) $me['id'];
$needsOk = owApprovalsReady();   // always true on the live site; without the table it saves straight away as before

/* Take a waiting signature out of the waiting profile change (other waiting profile changes stay) */
$withdraw = function () use ($uid): void {
    $p = owPendingChange('profile', $uid);
    if (!$p || !array_key_exists('signature', $p['data'])) return;
    $d = $p['data']; unset($d['signature']);
    if ($d) getDB()->prepare('UPDATE pending_changes SET data = :d WHERE id = :id')->execute([':d' => json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ':id' => (int) $p['id']]);
    else getDB()->prepare("UPDATE pending_changes SET status = 'replaced', decided_at = NOW() WHERE id = :id")->execute([':id' => (int) $p['id']]);
};

$err = ''; $done = (string) ($_GET['done'] ?? '');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $err = 'server';
    } elseif (($_POST['do'] ?? '') === 'cancel') {   // take back a signature that is still waiting
        $withdraw(); owSigRemove((string) $uid, true);
        header('Location: signature.php?done=cancelled'); exit;
    } elseif (($_POST['do'] ?? '') === 'remove') {
        if ($needsOk) {
            $withdraw(); owSigRemove((string) $uid, true);
            if (owSigExists((string) $uid)) owSubmitChange($uid, 'profile', $uid, ['signature' => 'remove']);
        } else owSigRemove((string) $uid);
        owLog('signature_remove', 'user', $uid, '', ['type' => 'artist', 'id' => $uid]);
        header('Location: signature.php?done=' . ($needsOk ? 'remove_wait' : 'removed')); exit;
    } else {
        $d = (string) ($_POST['sig_data'] ?? '');
        if (!preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $d, $m)) {
            $err = 'empty';
        } else {
            $err = owSigSave((string) $uid, (string) base64_decode($m[1], true), $needsOk);
            if ($err === '' && $needsOk && !owSubmitChange($uid, 'profile', $uid, ['signature' => 'new'])) { owSigRemove((string) $uid, true); $err = 'server'; }
            if ($err === '') {
                owLog('signature_set', 'user', $uid, '', ['type' => 'artist', 'id' => $uid]);
                header('Location: signature.php?done=' . ($needsOk ? 'wait' : 'saved')); exit;
            }
        }
    }
}
$have = owSigExists((string) $uid);
$waitP = $needsOk ? owPendingChange('profile', $uid) : null;
$waiting = $waitP && array_key_exists('signature', $waitP['data']) ? (string) $waitP['data']['signature'] : '';   // 'new', 'remove' or ''
$lastNo = '';   // the reason, when the last signature decision was "not approved"
if ($needsOk && $waiting === '') {
    $q = getDB()->prepare("SELECT status, reason, data FROM pending_changes WHERE user_id = :u AND target_type = 'profile' AND target_id = :u2 AND status IN ('approved','rejected') ORDER BY id DESC LIMIT 5");
    $q->execute([':u' => $uid, ':u2' => $uid]);
    foreach ($q->fetchAll() as $r) { $dd = json_decode((string) $r['data'], true) ?: []; if (!array_key_exists('signature', $dd)) continue; if ($r['status'] === 'rejected') $lastNo = (string) $r['reason']; break; }
}
?>
<!DOCTYPE html>
<html <?php require_once __DIR__ . '/lang_lib.php'; owHtmlAttrs(); ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>My signature · توقيعي · Oweili</title>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
.page{position:relative;z-index:10;max-width:760px;margin:0 auto;padding:calc(64px + var(--space-lg)) var(--space-sm) var(--space-xl);}
.sig-card{padding:var(--space-lg);}
.sig-card h1{font-size:var(--fs-section);margin:0 0 6px;}
.sig-pad{display:block;width:100%;height:220px;border:1px dashed var(--color-border-strong);border-radius:12px;background:#fff;touch-action:none;cursor:crosshair;margin:var(--space-sm) 0 var(--space-xs);}
.sig-line{font-size:13px;color:var(--color-body);margin:0 0 var(--space-sm);}
.sig-acts{display:flex;flex-wrap:wrap;gap:var(--space-xs);}
.sig-now{margin-top:var(--space-md);padding-top:var(--space-md);border-top:1px solid var(--color-border);}
.sig-now img{display:block;max-width:320px;max-height:110px;background:#fff;border:1px solid var(--color-border);border-radius:8px;padding:8px;}
</style>
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>
<main class="page dash role-artist">
  <div class="card sig-card">
    <h1><?= owBi('My signature', 'توقيعي') ?></h1>
    <p><?= owBi('Draw your signature once. It appears on the Certificate of Authenticity of every artwork you sell on Oweili. Only you and the Oweili team see it.', 'ارسم توقيعك مرة واحدة. يظهر على شهادة الأصالة لكل عمل تبيعه في أويلي. لا يراه إلا أنت وفريق أويلي.') ?></p>
    <?php if ($needsOk): ?><p class="sig-line"><?= owBi('A new or changed signature is checked by the Oweili team first. Your certificates show it once it is approved.', 'يراجع فريق أويلي التوقيع الجديد أو المعدّل أولاً، ويظهر على شهاداتك بعد الموافقة عليه.') ?></p><?php endif; ?>
    <?php if ($done === 'wait'): ?><div class="ok-box" style="display:block"><?= owBi('Sent. Your signature is waiting for approval by the Oweili team.', 'تم الإرسال. توقيعك بانتظار موافقة فريق أويلي.') ?></div><?php endif; ?>
    <?php if ($done === 'remove_wait'): ?><div class="ok-box" style="display:block"><?= owBi('Sent. Removing your signature is waiting for approval by the Oweili team.', 'تم الإرسال. حذف توقيعك بانتظار موافقة فريق أويلي.') ?></div><?php endif; ?>
    <?php if ($done === 'cancelled'): ?><div class="ok-box" style="display:block"><?= owBi('Done. Nothing is waiting any more.', 'تم. لا شيء بانتظار الموافقة الآن.') ?></div><?php endif; ?>
    <?php if ($lastNo !== ''): ?><div class="err-box" style="display:block"><?= owBi('Your last signature was not approved. The reason: ', 'لم تتم الموافقة على توقيعك الأخير. السبب: ') ?><?= e($lastNo) ?></div><?php endif; ?>
    <?php if ($done === 'saved'): ?><div class="ok-box" style="display:block"><?= owBi('Saved. Your signature is now on your certificates.', 'تم الحفظ. توقيعك الآن على شهاداتك.') ?></div><?php endif; ?>
    <?php if ($done === 'removed'): ?><div class="ok-box" style="display:block"><?= owBi('Your signature was removed.', 'تم حذف توقيعك.') ?></div><?php endif; ?>
    <?php if ($err !== ''): [$ee, $ea] = owSigError($err); ?><div class="err-box" style="display:block"><?= owBi($ee, $ea) ?></div><?php endif; ?>
    <form method="post" action="signature.php" id="sig-form">
      <?= csrfField() ?>
      <input type="hidden" name="sig_data" id="sig-data">
      <canvas class="sig-pad" id="sig-pad" aria-label="Signature pad · لوحة التوقيع"></canvas>
      <p class="sig-line"><?= owBi('Sign inside the box with your finger or mouse.', 'وقّع داخل المربع بإصبعك أو بالفأرة.') ?></p>
      <div class="sig-acts">
        <button type="submit" class="btn btn-primary"><?= owBi('Save signature', 'احفظ التوقيع') ?></button>
        <button type="button" class="btn btn-secondary" id="sig-clear"><?= owBi('Clear', 'امسح') ?></button>
        <a class="btn btn-ghost" href="artist_studio.php"><?= owBi('Back to My Studio', 'العودة إلى مرسمي') ?></a>
      </div>
    </form>
    <?php if ($waiting !== ''): ?>
    <div class="sig-now">
      <p><b><?= owBi('Waiting for approval', 'بانتظار الموافقة') ?></b> <span class="pill off"><?= owBi('Not on your certificates yet', 'ليس على شهاداتك بعد') ?></span></p>
      <?php if ($waiting === 'new' && owSigExists((string) $uid, true)): ?><img src="<?= e(owSigDataUri((string) $uid, true)) ?>" alt="Your waiting signature · توقيعك بانتظار الموافقة">
      <?php else: ?><p class="sig-line"><?= owBi('You asked to remove your signature.', 'طلبت حذف توقيعك.') ?></p><?php endif; ?>
      <form method="post" action="signature.php" style="margin-top:var(--space-xs)">
        <?= csrfField() ?><input type="hidden" name="do" value="cancel">
        <button class="btn btn-secondary btn-sm"><?= owBi('Cancel this request', 'ألغِ هذا الطلب') ?></button>
      </form>
    </div>
    <?php endif; ?>
    <?php if ($have): ?>
    <div class="sig-now">
      <p><b><?= $needsOk ? owBi('On your certificates now', 'على شهاداتك الآن') : owBi('Your signature now', 'توقيعك الحالي') ?></b></p>
      <img src="<?= e(owSigDataUri((string) $uid)) ?>" alt="Your signature · توقيعك">
      <form method="post" action="signature.php" style="margin-top:var(--space-xs)" onsubmit="return confirm(document.documentElement.dir==='rtl'?'حذف التوقيع؟':'Remove your signature?');">
        <?= csrfField() ?><input type="hidden" name="do" value="remove">
        <button class="btn btn-danger btn-sm"><?= owBi('Remove signature', 'احذف التوقيع') ?></button>
      </form>
    </div>
    <?php endif; ?>
  </div>
</main>
<script>
(function(){
  var c = document.getElementById('sig-pad'), x = c.getContext('2d'), drawn = false, down = false, last = null;
  function size(){ var r = c.getBoundingClientRect(), k = window.devicePixelRatio || 1; c.width = Math.round(r.width * k); c.height = Math.round(r.height * k);
    x.setTransform(k, 0, 0, k, 0, 0); x.lineWidth = 2.6; x.lineCap = 'round'; x.lineJoin = 'round'; x.strokeStyle = '#1A2538'; drawn = false; }
  function pt(e){ var r = c.getBoundingClientRect(); return { x: e.clientX - r.left, y: e.clientY - r.top }; }
  c.addEventListener('pointerdown', function(e){ down = true; last = pt(e); c.setPointerCapture(e.pointerId); x.beginPath(); x.arc(last.x, last.y, 1.2, 0, 7); x.fillStyle = '#1A2538'; x.fill(); drawn = true; });
  c.addEventListener('pointermove', function(e){ if (!down) return; var p = pt(e); x.beginPath(); x.moveTo(last.x, last.y); x.lineTo(p.x, p.y); x.stroke(); last = p; drawn = true; });
  ['pointerup', 'pointercancel', 'pointerleave'].forEach(function(n){ c.addEventListener(n, function(){ down = false; }); });
  document.getElementById('sig-clear').addEventListener('click', size);
  document.getElementById('sig-form').addEventListener('submit', function(e){
    if (!drawn) { e.preventDefault(); alert(document.documentElement.dir === 'rtl' ? 'يرجى رسم توقيعك أولاً.' : 'Please draw your signature first.'); return; }
    document.getElementById('sig-data').value = c.toDataURL('image/png');
  });
  size(); window.addEventListener('resize', function(){ if (!drawn) size(); });
})();
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
