<?php
/* =====================================================================
 *  admin_gifts.php — Oweili Gifts requests (gifts.php), admin only.
 *  Every request with the occasion, the artwork (or the custom brief),
 *  wrapping, card message, delivery date, Surprise, the recipient and the
 *  buyer. The admin follows up by hand: status (New / Contacted / In
 *  progress / Done / Cancelled) and a private note. No payment yet.
 *  Buyer and recipient details are shown only here (admin only).
 *  PAYMENT FIRST for custom gifts (3 Nov 2026): no work starts on a custom
 *  artwork until the admin presses "Mark as paid" (activity log 'gift_paid',
 *  the one record of the payment while payments are switched off); until then
 *  the status cannot move to In progress or Done.
 *  Tables: gift_requests, artworks, users, activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/admin_shell.php';
require_once __DIR__ . '/artwork_lib.php';
require_once __DIR__ . '/request_lib.php';
owAdminGate();
$OCC = owGiftOccasions();
$STATUS = ['new' => ['New', 'جديد'], 'contacted' => ['Contacted', 'تم التواصل'], 'in_progress' => ['In progress', 'قيد التنفيذ'], 'done' => ['Done', 'تم'], 'cancelled' => ['Cancelled', 'ملغى']];
$ready = owHasTable('gift_requests');
$db = getDB();
/* When a custom gift was marked paid (activity log 'gift_paid'), or null */
function owGiftPaidAt(int $id): ?string {
    $st = getDB()->prepare("SELECT MIN(created_at) FROM activity_log WHERE action = 'gift_paid' AND target_type = 'gift' AND target_id = :id");
    $st->execute([':id' => $id]); $v = $st->fetchColumn();
    return $v ? (string) $v : null;
}
if ($ready && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && verifyCsrf($_POST['csrf_token'] ?? null)) {
    $id = (int) ($_POST['id'] ?? 0);
    $st = (string) ($_POST['status'] ?? '');
    $g = $db->prepare('SELECT mode, status FROM gift_requests WHERE id = :id'); $g->execute([':id' => $id]); $g = $g->fetch();
    if ($g && ($_POST['do'] ?? '') === 'paid') {
        if ($g['mode'] === 'custom' && $g['status'] !== 'cancelled' && !owGiftPaidAt($id)) owLog('gift_paid', 'gift', $id, mb_substr(trim((string) ($_POST['pay_note'] ?? '')), 0, 300), owAdminActor());
        header('Location: admin_gifts.php?id=' . $id . '&ok=1'); exit;
    }
    if ($g && $g['mode'] === 'custom' && in_array($st, ['in_progress', 'done'], true) && !owGiftPaidAt($id)) { header('Location: admin_gifts.php?id=' . $id . '&unpaid=1'); exit; }
    if ($g && isset($STATUS[$st])) {
        $db->prepare('UPDATE gift_requests SET status = :s, admin_note = :n WHERE id = :id')
           ->execute([':s' => $st, ':n' => mb_substr(trim((string) ($_POST['admin_note'] ?? '')), 0, 4000), ':id' => $id]);
        owLog('gift_update', 'gift', $id, $st, owAdminActor());
    }
    header('Location: admin_gifts.php?id=' . $id . '&ok=1'); exit;
}
$filter = isset($STATUS[$_GET['status'] ?? '']) ? (string) $_GET['status'] : '';
$one = null; $list = []; $counts = [];
if ($ready) {
    foreach ($db->query('SELECT status, COUNT(*) n FROM gift_requests GROUP BY status') as $r) $counts[$r['status']] = (int) $r['n'];
    if (isset($_GET['id'])) {
        $q = $db->prepare('SELECT g.*, a.title_en, a.title_ar, a.price, a.image_url, a.artist_id FROM gift_requests g LEFT JOIN artworks a ON a.id = g.artwork_id WHERE g.id = :id');
        $q->execute([':id' => (int) $_GET['id']]); $one = $q->fetch() ?: null;
    }
    $q = $db->prepare('SELECT g.id, g.occasion, g.mode, g.delivery_date, g.recipient_city, g.buyer_name, g.status, g.created_at, a.title_en FROM gift_requests g LEFT JOIN artworks a ON a.id = g.artwork_id'
                      . ($filter !== '' ? ' WHERE g.status = :s' : '') . ' ORDER BY g.id DESC LIMIT 300');
    $q->execute($filter !== '' ? [':s' => $filter] : []); $list = $q->fetchAll();
}
owAdminTop('Gift requests', 'طلبات الهدايا', '
.g-row{display:grid;grid-template-columns:1fr auto;gap:var(--space-sm);align-items:center;padding:var(--space-sm) 0;border-top:1px solid var(--color-border);text-decoration:none;color:inherit;}
.g-row:first-child{border-top:0;}
.g-row:hover b{text-decoration:underline;}
.g-img{width:140px;border-radius:10px;overflow:hidden;background:var(--color-frame);}
.g-img img{width:100%;display:block;}
.tabs{margin:0 0 var(--space-md);}
', '<a class="btn btn-ghost btn-sm" href="gifts.php" target="_blank" rel="noopener">' . owBi('Open the Gifts page', 'افتح صفحة الهدايا') . ' ↗</a>');
?>
  <h1><?= owBi('Gift requests', 'طلبات الهدايا') ?></h1>
  <p class="mini"><?= owBi('From "Give a gift that lasts". Call or write to the buyer, confirm the price, then arrange wrapping and delivery by hand. No payment is taken on the site yet.', 'من «أهدِ ما يبقى». اتصل بالمشتري أو راسله، أكّد السعر، ثم رتّب التغليف والتوصيل بنفسك. لا يُدفع أي مبلغ في الموقع بعد.') ?></p>
  <?php if (!$ready): ?><div class="alert alert-error"><?= owBi('The gifts table is not set up yet (night_update.sql).', 'جدول الهدايا غير مُعدّ بعد (night_update.sql).') ?></div><?php endif; ?>
  <?php if (isset($_GET['ok'])): ?><div class="alert alert-ok"><?= owBi('Saved.', 'تم الحفظ.') ?></div><?php endif; ?>
  <?php if (isset($_GET['unpaid'])): ?><div class="alert alert-error"><?= owBi('A custom gift can only move to In progress or Done after it is marked as paid.', 'لا ينتقل الطلب الخاص إلى «قيد التنفيذ» أو «تم» إلا بعد تسجيل الدفع.') ?></div><?php endif; ?>

<?php if ($one): $o = $OCC[$one['occasion']] ?? ['en' => $one['occasion'], 'ar' => $one['occasion']]; ?>
  <div class="card">
    <p><a href="admin_gifts.php">← <?= owBi('All gift requests', 'كل طلبات الهدايا') ?></a></p>
    <h2><?= owBi('Gift #' . (int) $one['id'] . ': ' . $o['en'], 'هدية رقم ' . (int) $one['id'] . ': ' . $o['ar']) ?> <span class="pillx"><?= owBi($STATUS[$one['status']][0], $STATUS[$one['status']][1]) ?></span></h2>
    <?php if ($one['mode'] === 'ready' && $one['title_en'] !== null): $gi = owImgSrc((string) $one['image_url']); ?>
      <?php if ($gi !== ''): ?><div class="g-img"><img src="<?= e(owImgSized($gi, 400)) ?>" alt="<?= e($one['title_en'] . ' · ' . $one['title_ar']) ?>"></div><?php endif; ?>
    <?php endif; ?>
    <dl class="kv">
      <dt><?= owBi('Sent', 'أُرسل') ?></dt><dd><?php [$de, $da] = owDateBi($one['created_at'], true); ?><?= owBi($de, $da) ?> · <?= $one['lang'] === 'ar' ? 'العربية' : 'English' ?></dd>
      <dt><?= owBi('Artwork', 'العمل') ?></dt><dd><?php if ($one['mode'] === 'ready'): ?><?php if ($one['title_en'] !== null): ?><a href="artwork.php?id=<?= (int) $one['artwork_id'] ?>" target="_blank" rel="noopener"><?= e($one['title_en'] . ' · ' . $one['title_ar']) ?></a> · <?= number_format((float) $one['price']) ?> SAR<?php else: ?><?= owBi('(the artwork is no longer on the site)', '(العمل لم يعد في الموقع)') ?><?php endif; ?>
        <?php else: ?><b><?= owBi('Custom artwork', 'عمل خاص') ?></b><br><?= nl2br(e((string) $one['custom_brief'])) ?><?php endif; ?></dd>
      <?php if ($one['budget_sar'] !== null): ?><dt><?= owBi('Budget', 'الميزانية') ?></dt><dd><?= number_format((float) $one['budget_sar']) ?> SAR</dd><?php endif; ?>
      <dt><?= owBi('Wrapping', 'التغليف') ?></dt><dd><?= (int) $one['wrapping'] ? owBi('Yes', 'نعم') : owBi('No', 'لا') ?></dd>
      <?php if ($one['mode'] === 'custom'): $gPaid = owGiftPaidAt((int) $one['id']); ?>
      <dt><?= owBi('Payment', 'الدفع') ?></dt><dd><?= $gPaid ? '<span class="pillx on">' . owBi('Paid', 'مدفوع') . '</span> ' . e($gPaid) : '<span class="pillx warn">' . owBi('Not paid yet: no work starts on the custom artwork', 'لم يُدفع بعد: لا يبدأ العمل على القطعة الخاصة') . '</span>' ?>
        <?php if (!$gPaid && $one['status'] !== 'cancelled'): ?>
        <details><summary class="btn btn-accent btn-sm"><?= owBi('Mark as paid…', 'سجّل الدفع…') ?></summary>
          <form method="post" action="admin_gifts.php"><?= csrfField() ?><input type="hidden" name="do" value="paid"><input type="hidden" name="id" value="<?= (int) $one['id'] ?>">
            <div class="field"><label><?= owBi('Note (optional, e.g. the bank transfer reference)', 'ملاحظة (اختيارية، مثل مرجع التحويل البنكي)') ?><input name="pay_note" maxlength="300"></label></div>
            <button class="btn btn-accent btn-sm"><?= owBi('Confirm: it is paid', 'تأكيد: تم الدفع') ?></button></form></details>
        <?php endif; ?></dd>
      <?php endif; ?>
      <dt><?= owBi('Surprise', 'مفاجأة') ?></dt><dd><?= (int) $one['surprise'] ? owBi('Yes: never show the price to the recipient', 'نعم: لا يُظهر السعر للمُهدى إليه أبداً') : owBi('No', 'لا') ?></dd>
      <dt><?= owBi('Card message', 'رسالة البطاقة') ?></dt><dd dir="auto"><?= $one['card_message'] !== '' ? nl2br(e((string) $one['card_message'])) : '—' ?></dd>
      <dt><?= owBi('Delivery date', 'تاريخ التوصيل') ?></dt><dd><?php [$xe, $xa] = owDateBi($one['delivery_date']); ?><?= owBi($xe, $xa) ?></dd>
      <dt><?= owBi('Recipient', 'المُهدى إليه') ?></dt><dd dir="auto"><?= e((string) $one['recipient_name']) ?><?php if ($one['recipient_phone'] !== ''): ?> · <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', (string) $one['recipient_phone'])) ?>" dir="ltr"><?= e((string) $one['recipient_phone']) ?></a><?php endif; ?><br><?= e((string) $one['recipient_city']) ?><br><?= nl2br(e((string) $one['recipient_address'])) ?></dd>
      <dt><?= owBi('Buyer', 'المشتري') ?></dt><dd><?= e((string) $one['buyer_name']) ?><?php if ($one['user_id']): ?> · <a href="admin.php?person=<?= (int) $one['user_id'] ?>"><?= owBi('account', 'الحساب') ?></a><?php endif; ?><br>
        <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', (string) $one['buyer_phone'])) ?>" dir="ltr"><?= e((string) $one['buyer_phone']) ?></a> · <a href="mailto:<?= e((string) $one['buyer_email']) ?>" dir="ltr"><?= e((string) $one['buyer_email']) ?></a></dd>
    </dl>
    <form method="post" action="admin_gifts.php">
      <?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $one['id'] ?>">
      <div class="g2">
        <div class="field"><label><?= owBi('Status', 'الحالة') ?><select name="status"><?php foreach ($STATUS as $sk => $sl): ?><option value="<?= $sk ?>"<?= $one['status'] === $sk ? ' selected' : '' ?>><?= e($sl[0] . ' · ' . $sl[1]) ?></option><?php endforeach; ?></select></label></div>
      </div>
      <div class="field"><label><?= owBi('Private note (only the admin sees it)', 'ملاحظة خاصة (للإدارة فقط)') ?><textarea name="admin_note" rows="3" maxlength="4000"><?= e((string) $one['admin_note']) ?></textarea></label></div>
      <button class="btn btn-primary btn-sm" type="submit"><?= owBi('Save', 'احفظ') ?></button>
    </form>
  </div>
<?php endif; ?>

<?php if ($ready): ?>
  <nav class="tabs"><a class="tab<?= $filter === '' ? ' active' : '' ?>" href="admin_gifts.php"><?= owBi('All', 'الكل') ?> (<?= array_sum($counts) ?>)</a>
    <?php foreach ($STATUS as $sk => $sl): ?><a class="tab<?= $filter === $sk ? ' active' : '' ?>" href="admin_gifts.php?status=<?= $sk ?>"><?= owBi($sl[0], $sl[1]) ?> (<?= (int) ($counts[$sk] ?? 0) ?>)</a><?php endforeach; ?></nav>
  <div class="card">
    <?php if (!$list): ?><p class="mini"><?= owBi('No gift requests here yet.', 'لا طلبات هدايا هنا بعد.') ?></p><?php endif; ?>
    <?php foreach ($list as $g): $o = $OCC[$g['occasion']] ?? ['en' => $g['occasion'], 'ar' => $g['occasion']]; [$de, $da] = owDateBi($g['created_at']); [$xe, $xa] = owDateBi($g['delivery_date']); ?>
    <a class="g-row" href="admin_gifts.php?id=<?= (int) $g['id'] ?>">
      <span><b>#<?= (int) $g['id'] ?> · <?= owBi($o['en'], $o['ar']) ?> · <?= $g['mode'] === 'ready' ? e((string) ($g['title_en'] ?? '—')) : owBi('Custom artwork', 'عمل خاص') ?></b>
        <span class="mini" style="display:block"><?= e((string) $g['buyer_name']) ?> → <?= e((string) $g['recipient_city']) ?> · <?= owBi('deliver ' . $xe, 'التوصيل ' . $xa) ?> · <?= owBi('sent ' . $de, 'أُرسل ' . $da) ?></span></span>
      <span class="pillx<?= $g['status'] === 'new' ? ' warn' : '' ?>"><?= owBi($STATUS[$g['status']][0], $STATUS[$g['status']][1]) ?></span>
    </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php owAdminBottom();
