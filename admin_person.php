<?php
/* =====================================================================
 *  admin_person.php — one artist or buyer, for the admin
 *  (admin.php?person=N). Opened only from admin.php after the admin
 *  password check; opening this file directly shows nothing.
 *  Shows everything about the person (personal data is for the admin
 *  only): details, status with approve / suspend, their artworks,
 *  requests they sent, requests on their works, favorites, activity.
 *  Tables: users, artworks, artwork_inquiries, favorites, activity_log
 * ===================================================================== */
if (!defined('OW_ADMIN_OK')) { http_response_code(404); exit; }

$pid = (int) ($_GET['person'] ?? 0);
$st = getDB()->prepare('SELECT u.*, ' . owUserStatusSql('u') . ' AS state, ' . owArtistNameSql('u') . ' FROM users u WHERE u.id = :id');
$st->execute([':id' => $pid]);
$p = $st->fetch();

$works = $sent = $received = $favs = $log = [];
if ($p) {
    $isArtist = $p['role'] === 'artist';
    if ($isArtist) {
        $q = getDB()->prepare('SELECT a.id, a.title_en, a.title_ar, a.price, a.created_at, ' . owArtworkStatusSql('a') . ' AS state,
                               ' . (owHasColumn('artworks', 'view_count') ? 'a.view_count' : '0') . ' AS views
                               FROM artworks a WHERE a.artist_id = :id ORDER BY a.created_at DESC');
        $q->execute([':id' => $pid]);
        $works = $q->fetchAll();
        if (owHasTable('artwork_inquiries')) {
            $q = getDB()->prepare('SELECT q.*, a.title_en FROM artwork_inquiries q LEFT JOIN artworks a ON a.id = q.artwork_id WHERE q.artist_id = :id ORDER BY q.created_at DESC LIMIT 200');
            $q->execute([':id' => $pid]);
            $received = $q->fetchAll();
        }
    }
    if (owHasTable('artwork_inquiries')) {
        $who = owHasColumn('artwork_inquiries', 'buyer_id') ? 'q.buyer_id = :id OR q.buyer_email = :e' : 'q.buyer_email = :e';
        $q = getDB()->prepare("SELECT q.*, a.title_en FROM artwork_inquiries q LEFT JOIN artworks a ON a.id = q.artwork_id WHERE $who ORDER BY q.created_at DESC LIMIT 200");
        $q->execute(owHasColumn('artwork_inquiries', 'buyer_id') ? [':id' => $pid, ':e' => strtolower(trim((string) $p['email']))] : [':e' => strtolower(trim((string) $p['email']))]);
        $sent = $q->fetchAll();
    }
    if (owHasTable('favorites')) {
        $q = getDB()->prepare('SELECT f.created_at, a.id, a.title_en, a.title_ar FROM favorites f JOIN artworks a ON a.id = f.artwork_id WHERE f.user_id = :id ORDER BY f.created_at DESC');
        $q->execute([':id' => $pid]);
        $favs = $q->fetchAll();
    }
    if (owHasTable('activity_log')) {
        $q = getDB()->prepare("SELECT actor_type, actor_id, action, target_type, target_id, details, created_at FROM activity_log
                               WHERE (actor_id = :id AND actor_type <> 'admin') OR (target_type = 'user' AND target_id = :id2)
                                  OR (target_type = 'artwork' AND target_id IN (SELECT id FROM artworks WHERE artist_id = :id3))
                               ORDER BY id DESC LIMIT 150");
        $q->execute([':id' => $pid, ':id2' => $pid, ':id3' => $pid]);
        $log = $q->fetchAll();
    }
}
$STATE_W = ['active' => ['Active', 'نشط'], 'pending' => ['Waiting for approval', 'بانتظار الموافقة'], 'suspended' => ['Suspended', 'موقوف']];
$ART_W = ['draft' => ['Draft', 'مسودة'], 'pending' => ['In review', 'قيد المراجعة'], 'approved' => ['In the Gallery', 'في المعرض'], 'rejected' => ['Not approved', 'لم تتم الموافقة'], 'sold' => ['Sold', 'مباع']];
$d = fn(?string $v) => $v ? e(date('Y-m-d H:i', strtotime($v))) : '—';
$inqTable = function (array $rows, bool $showBuyer) use ($d): void {
    if (!$rows) { echo '<p class="mini">' . owBi('None yet.', 'لا شيء بعد.') . '</p>'; return; }
    echo '<div style="overflow-x:auto"><table class="table"><thead><tr><th>' . owBi('Date', 'التاريخ') . '</th><th>' . owBi('Artwork', 'العمل') . '</th>'
       . ($showBuyer ? '<th>' . owBi('Buyer', 'المشتري') . '</th>' : '') . '<th>' . owBi('Status', 'الحالة') . '</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $s = OW_INQUIRY_STATUS[$r['status'] ?? 'new'] ?? OW_INQUIRY_STATUS['new'];
        echo '<tr><td class="mini">' . $d($r['created_at']) . '</td><td>' . e((string) ($r['title_en'] ?? '#' . $r['artwork_id'])) . '</td>'
           . ($showBuyer ? '<td>' . e($r['buyer_name']) . '<div class="mini" dir="ltr">' . e($r['buyer_phone']) . ' · ' . e($r['buyer_email']) . '</div></td>' : '')
           . '<td>' . owBi($s[0], $s[1]) . (!empty($r['admin_notes']) ? '<div class="mini">' . e($r['admin_notes']) . '</div>' : '') . '</td></tr>';
    }
    echo '</tbody></table></div>';
};
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title><?= $p ? e($p['full_name_en'] ?: $p['email']) . ' · ' : '' ?>Admin · Oweili</title>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
.wrap{max-width:1100px;margin:0 auto;padding:var(--space-lg) var(--space-sm);}
.top{display:flex;justify-content:space-between;align-items:center;gap:var(--space-sm);flex-wrap:wrap;margin-bottom:var(--space-md);}
.wrap .card{margin-bottom:var(--space-md);}
.wrap h1{font-size:28px;margin:0;}
.wrap h2{font-size:20px;margin:0 0 var(--space-sm);}
.kv{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:var(--space-sm);}
.kv div{font-size:15px;}.kv b{display:block;font-size:var(--fs-label);color:var(--color-body);font-weight:700;}
.mini{font-size:var(--fs-caption);color:var(--color-body);}
.acts{display:flex;gap:var(--space-xs);flex-wrap:wrap;margin-top:var(--space-sm);}
.acts form{margin:0;}
.pillx{display:inline-block;font-size:var(--fs-min);font-weight:700;padding:3px 10px;border-radius:var(--radius-pill);border:1px solid var(--color-border-strong);}
</style>
</head>
<body>
<div class="wrap dash role-admin">
  <div class="top">
    <a class="btn btn-ghost btn-sm" href="admin.php#<?= $p && $p['role'] === 'collector' ? 'collectors' : 'artists' ?>">← <?= owBi('Back to admin', 'العودة للإدارة') ?></a>
    <span><button class="btn btn-ghost btn-sm" onclick="tgl()" id="lb">العربية</button> <?php owThemeButton('btn btn-ghost btn-sm'); ?></span>
  </div>
<?php if (!$p): ?>
  <div class="card"><p><?= owBi('This account was not found.', 'لم يتم العثور على هذا الحساب.') ?></p></div>
<?php else: $sw = $STATE_W[$p['state']] ?? $STATE_W['active']; ?>
  <?php if (isset($_GET['ok'])): ?><div class="alert alert-ok"><?= owBi('Saved.', 'تم الحفظ.') ?></div><?php endif; ?>
  <div class="card">
    <h1><?= e($p['full_name_en'] ?: $p['artist_en']) ?><?php if ($p['full_name_ar']): ?> · <span dir="rtl"><?= e($p['full_name_ar']) ?></span><?php endif; ?></h1>
    <p class="mini"><?= owBi($p['role'] === 'collector' ? 'Buyer' : ucfirst(str_replace('_', ' ', $p['role'])), ['collector' => 'مشترٍ', 'artist' => 'فنان', 'admin' => 'مدير', 'sub_admin' => 'مشرف فرعي', 'institute' => 'معهد'][$p['role']] ?? $p['role']) ?>
      · <span class="pillx"><?= owBi($sw[0], $sw[1]) ?></span> · ID <span dir="ltr"><?= e((string) ($p['public_id'] ?? $p['id'])) ?></span></p>
    <div class="kv">
      <div><b><?= owBi('Email', 'البريد') ?></b><a href="mailto:<?= e($p['email']) ?>" dir="ltr"><?= e($p['email']) ?></a> <?= (int) $p['is_verified'] ? '✓' : owBi('(not confirmed)', '(غير مؤكد)') ?></div>
      <div><b><?= owBi('Phone', 'الهاتف') ?></b><span dir="ltr"><?= e((string) ($p['phone'] ?: '—')) ?></span></div>
      <div><b><?= owBi('City / country', 'المدينة / الدولة') ?></b><?= e(implode(', ', array_filter([(string) $p['city'], (string) ($p['country'] ?? '')])) ?: '—') ?></div>
      <div><b><?= owBi('Language', 'اللغة') ?></b><?= e(['en' => 'English', 'ar' => 'العربية'][$p['preferred_lang'] ?? ''] ?? '—') ?></div>
      <div><b><?= owBi('Joined', 'انضم') ?></b><?= $d($p['created_at']) ?></div>
      <div><b><?= owBi('Last sign-in', 'آخر دخول') ?></b><?= $d($p['last_login_at'] ?? null) ?></div>
      <?php if ($p['role'] === 'artist'): ?><div><b><?= owBi('Public name', 'الاسم الظاهر') ?></b><a href="artist_dashboard.php?id=<?= (int) $p['id'] ?>" target="_blank" rel="noopener"><?= e($p['artist_en']) ?> ↗</a></div><?php endif; ?>
    </div>
    <div class="acts">
      <?php if ($p['role'] === 'artist'): ?>
        <form method="post" action="admin.php"><?= csrfField() ?><input type="hidden" name="back" value="person"><input type="hidden" name="user_id" value="<?= (int) $p['id'] ?>">
          <?php if ((int) $p['is_approved'] === 1): ?><button class="btn btn-ghost btn-sm" name="action" value="unapprove"><?= owBi('Set back to pending', 'إعادة إلى الانتظار') ?></button><?php else: ?><button class="btn btn-accent btn-sm" name="action" value="approve"><?= owBi('Approve', 'موافقة') ?></button><?php endif; ?></form>
      <?php endif; ?>
      <form method="post" action="admin.php"<?= (int) $p['is_banned'] === 1 ? '' : ' onsubmit="return confirm(\'Suspend this account?\');"' ?>><?= csrfField() ?><input type="hidden" name="back" value="person"><input type="hidden" name="user_id" value="<?= (int) $p['id'] ?>">
        <?php if ((int) $p['is_banned'] === 1): ?><button class="btn btn-ghost btn-sm" name="action" value="unban"><?= owBi('Lift suspension', 'إلغاء الإيقاف') ?></button><?php else: ?><button class="btn btn-danger btn-sm" name="action" value="ban"><?= owBi('Suspend', 'إيقاف') ?></button><?php endif; ?></form>
    </div>
  </div>

  <?php if ($p['role'] === 'artist'): ?>
  <div class="card"><h2><?= owBi('Artworks', 'الأعمال') ?> (<?= count($works) ?>)</h2>
    <?php if (!$works): ?><p class="mini"><?= owBi('None yet.', 'لا شيء بعد.') ?></p><?php else: ?>
    <div style="overflow-x:auto"><table class="table"><thead><tr><th><?= owBi('Artwork', 'العمل') ?></th><th><?= owBi('Status', 'الحالة') ?></th><th><?= owBi('Price', 'السعر') ?></th><th><?= owBi('Views', 'المشاهدات') ?></th><th><?= owBi('Added', 'أُضيف') ?></th></tr></thead><tbody>
    <?php foreach ($works as $w): $aw = $ART_W[$w['state']] ?? $ART_W['pending']; ?>
      <tr><td><?= e($w['title_en'] ?: $w['title_ar']) ?></td><td><?= owBi($aw[0], $aw[1]) ?></td><td><?= number_format((float) $w['price']) ?> <?= owBi('SAR', 'ر.س') ?></td><td><?= (int) $w['views'] ?></td><td class="mini"><?= $d($w['created_at']) ?></td></tr>
    <?php endforeach; ?></tbody></table></div><?php endif; ?>
  </div>
  <div class="card"><h2><?= owBi('Interest in their works', 'الاهتمام بأعماله') ?> (<?= count($received) ?>)</h2><?php $inqTable($received, true); ?></div>
  <?php endif; ?>

  <?php if ($p['role'] !== 'artist' || $sent): ?>
  <div class="card"><h2><?= owBi('Requests they sent', 'الطلبات التي أرسلها') ?> (<?= count($sent) ?>)</h2><?php $inqTable($sent, false); ?></div>
  <div class="card"><h2><?= owBi('Favorites', 'المفضلة') ?> (<?= count($favs) ?>)</h2>
    <?php if (!$favs): ?><p class="mini"><?= owBi('None yet.', 'لا شيء بعد.') ?></p><?php else: ?><ul>
    <?php foreach ($favs as $f): ?><li><?= e($f['title_en'] ?: $f['title_ar']) ?> <span class="mini">· <?= $d($f['created_at']) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="card"><h2><?= owBi('Activity', 'النشاط') ?></h2>
    <?php if (!$log): ?><p class="mini"><?= owBi('No activity recorded yet.', 'لا نشاط مسجل بعد.') ?></p><?php else: ?>
    <div style="overflow-x:auto"><table class="table"><thead><tr><th><?= owBi('When', 'متى') ?></th><th><?= owBi('Who', 'من') ?></th><th><?= owBi('What', 'ماذا') ?></th></tr></thead><tbody>
    <?php foreach ($log as $l): $w = OW_ACTION_WORDS[$l['action']] ?? [$l['action'], $l['action']]; ?>
      <tr><td class="mini"><?= $d($l['created_at']) ?></td><td><?= e($l['actor_type'] === 'buyer' ? 'Buyer' : ucfirst(str_replace('_', ' ', $l['actor_type']))) ?></td>
        <td><?= owBi($w[0], $w[1]) ?><?= $l['target_type'] === 'artwork' ? ' <span class="mini">#' . (int) $l['target_id'] . '</span>' : '' ?><?= $l['details'] ? '<div class="mini">' . e($l['details']) . '</div>' : '' ?></td></tr>
    <?php endforeach; ?></tbody></table></div><?php endif; ?>
  </div>
<?php endif; ?>
</div>
<script>
function tgl(){ var ar = document.documentElement.getAttribute('dir') !== 'rtl'; document.documentElement.setAttribute('dir', ar ? 'rtl' : 'ltr'); document.documentElement.lang = ar ? 'ar' : 'en';
  document.getElementById('lb').textContent = ar ? 'English' : 'العربية'; try { localStorage.setItem('lang', ar ? 'ar' : 'en'); } catch (e) {} }
try { if (localStorage.getItem('lang') === 'ar') tgl(); } catch (e) {}
</script>
</body>
</html>
