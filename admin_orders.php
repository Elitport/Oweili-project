<?php
/* =====================================================================
 *  admin_orders.php — orders, complaints and artist payouts (admin only).
 *  Payments are SWITCHED OFF: this page shows what is still needed and
 *  stays empty until a payment provider is connected (order_lib.php).
 *  Once on: every order with its status and history, the buttons along the
 *  escrow steps (shipped, delivered, refund, cancel), open complaints with
 *  "Release to the artist" or "Refund", and the payout list (mark paid with
 *  the bank reference).
 *  Tables: orders, order_events, order_complaints, payouts, users
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/admin_shell.php';
require_once __DIR__ . '/order_lib.php';
owAdminGate();
$ready = owOrdersReady();
$on = owPaymentsOn();
$missing = owPaymentsMissing();
$db = getDB(); $actor = owAdminActor();
if ($ready && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && verifyCsrf($_POST['csrf_token'] ?? null)) {
    $do = (string) ($_POST['do'] ?? ''); $id = (int) ($_POST['id'] ?? 0); $note = mb_substr(trim((string) ($_POST['note'] ?? '')), 0, 500);
    if (in_array($do, ['shipped', 'delivered', 'released', 'refunded', 'cancelled'], true)) owOrderMove($id, $do, $actor, $note !== '' ? $note : 'by the admin');
    if ($do === 'payout_paid' && owHasColumn('payouts', 'order_id')) {
        $ref = mb_substr(trim((string) ($_POST['reference'] ?? '')), 0, 120);
        if ($ref !== '') { $db->prepare("UPDATE payouts SET status = 'paid', reference = :r WHERE id = :id AND status = 'pending'")->execute([':r' => $ref, ':id' => $id]); owLog('payout_paid', 'payout', $id, $ref, $actor); }
    }
    header('Location: admin_orders.php' . (isset($_POST['back']) ? '?id=' . (int) $_POST['back'] : '') . '#top'); exit;
}
$orders = $ready ? $db->query('SELECT o.*, ' . owArtistNameSql('u') . ' FROM orders o LEFT JOIN users u ON u.id = o.artist_id ORDER BY o.id DESC LIMIT 300')->fetchAll() : [];
$complaints = $ready ? $db->query("SELECT c.*, o.order_no, o.title FROM order_complaints c JOIN orders o ON o.id = c.order_id WHERE c.status = 'open' ORDER BY c.id")->fetchAll() : [];
$payouts = ($ready && owHasColumn('payouts', 'order_id')) ? $db->query('SELECT p.*, ' . owArtistNameSql('u') . ' FROM payouts p JOIN users u ON u.id = p.artist_id ORDER BY p.status = \'pending\' DESC, p.id DESC LIMIT 200')->fetchAll() : [];
$one = isset($_GET['id']) && $ready ? owOrderGet((int) $_GET['id']) : null;
$events = [];
if ($one) { $q = $db->prepare('SELECT * FROM order_events WHERE order_id = :o ORDER BY id'); $q->execute([':o' => (int) $one['id']]); $events = $q->fetchAll(); }
owAdminTop('Orders and payouts', 'الطلبات والمدفوعات', '.o-row{display:grid;grid-template-columns:1fr auto;gap:var(--space-sm);align-items:center;padding:var(--space-sm) 0;border-top:1px solid var(--color-border);text-decoration:none;color:inherit;}.o-row:first-of-type{border-top:0;}.miss li{margin-bottom:6px;}');
?>
  <h1 id="top"><?= owBi('Orders and payouts', 'الطلبات والمدفوعات') ?> <span class="pillx<?= $on ? ' on' : ' warn' ?>"><?= $on ? owBi('Payments on', 'الدفع يعمل') : owBi('Payments switched off', 'الدفع متوقف') ?></span></h1>
  <p class="mini"><?= owBi('Escrow: the collector’s money is held by Oweili and paid to the artist only after you approve the payout (Admin → Waiting for approval): the request comes when the collector sends "I received the artwork" with a photo, or ' . OW_PAYOUT_ASK_DAYS . ' days after delivery with no complaint; a complaint holds it. Minus Oweili’s commission (' . rtrim(rtrim(number_format(owCommissionRate(), 2), '0'), '.') . '%, before VAT).',
                       'الضمان: يحتفظ أويلي بمال المقتني ولا يُدفع للفنان إلا بعد موافقتك على الدفعة (الإدارة ← بانتظار الموافقة): يصل الطلب حين يرسل المقتني «استلمت العمل» مع صورة، أو بعد ' . OW_PAYOUT_ASK_DAYS . ' أيام من التسليم دون شكوى، والشكوى توقفه. مخصوماً منه عمولة أويلي (' . rtrim(rtrim(number_format(owCommissionRate(), 2), '0'), '.') . '٪ قبل الضريبة).') ?></p>
  <?php if (!$on): ?>
  <div class="card"><h2><?= owBi('What is needed to switch payments on', 'ما المطلوب لتشغيل الدفع') ?></h2>
    <ul class="miss"><?php foreach ($missing as [$en, $ar]): ?><li><?= owBi($en, $ar) ?></li><?php endforeach; ?></ul>
    <p class="mini"><?= owBi('Until then no buy button shows anywhere and no money can be taken. Orders, complaints and payouts below stay empty.', 'حتى ذلك الحين لا يظهر أي زر شراء ولا يمكن أخذ أي مبلغ. تبقى الطلبات والشكاوى والمدفوعات أدناه فارغة.') ?></p></div>
  <?php endif; ?>

  <?php if ($one): ?>
  <div class="card"><p><a href="admin_orders.php">← <?= owBi('All orders', 'كل الطلبات') ?></a></p>
    <h2><?= e($one['order_no']) ?> · <?= owBi(OW_ORDER_KINDS[$one['kind']][0], OW_ORDER_KINDS[$one['kind']][1]) ?> <?= owOrderPill((string) $one['status']) ?></h2>
    <dl class="kv"><dt><?= owBi('What', 'ماذا') ?></dt><dd><?= e((string) $one['title']) ?></dd>
      <dt><?= owBi('Amount', 'المبلغ') ?></dt><dd><?= number_format((float) $one['amount'], 2) ?> SAR · <?= owBi('Oweili', 'أويلي') ?> <?= number_format((float) $one['commission_amount'], 2) ?> (<?= e((string) $one['commission_rate']) ?>%) · <?= owBi('artist', 'الفنان') ?> <?= number_format((float) $one['artist_amount'], 2) ?></dd>
      <dt><?= owBi('Collector', 'المقتني') ?></dt><dd><?= e($one['buyer_name'] . ' · ' . $one['buyer_email'] . ' · ' . $one['buyer_phone']) ?></dd>
      <dt><?= owBi('Release due', 'موعد التحويل') ?></dt><dd><?= e((string) ($one['release_due_at'] ?: '—')) ?></dd></dl>
    <div class="acts"><?php foreach (OW_ORDER_FLOW[$one['status']] ?? [] as $to): if ($to === 'paid' || $to === 'complaint') continue; ?>
      <form method="post" action="admin_orders.php"><?= csrfField() ?><input type="hidden" name="do" value="<?= $to ?>"><input type="hidden" name="id" value="<?= (int) $one['id'] ?>"><input type="hidden" name="back" value="<?= (int) $one['id'] ?>">
        <button class="btn btn-sm <?= in_array($to, ['refunded', 'cancelled'], true) ? 'btn-danger' : 'btn-accent' ?>"><?= owBi(OW_ORDER_STATUS[$to][0], OW_ORDER_STATUS[$to][1]) ?></button></form><?php endforeach; ?></div>
    <ul class="mini"><?php foreach ($events as $ev): ?><li><?= e($ev['created_at'] . ' · ' . ($ev['from_status'] ?: '—') . ' → ' . $ev['to_status'] . ' · ' . $ev['actor_type'] . ($ev['note'] !== '' ? ' · ' . $ev['note'] : '')) ?></li><?php endforeach; ?></ul>
  </div>
  <?php endif; ?>

  <div class="card"><h2><?= owBi('Open complaints', 'الشكاوى المفتوحة') ?> (<?= count($complaints) ?>)</h2>
    <?php if (!$complaints): ?><p class="mini"><?= owBi('None.', 'لا يوجد.') ?></p><?php endif; ?>
    <?php foreach ($complaints as $c): ?>
    <div class="o-row"><span><b><?= e($c['order_no'] . ' · ' . $c['title']) ?></b><span class="mini" style="display:block" dir="auto"><?= e((string) $c['reason']) ?></span></span>
      <span class="acts"><?php foreach (['released' => 'btn-accent', 'refunded' => 'btn-danger'] as $to => $cls): ?><form method="post" action="admin_orders.php"><?= csrfField() ?><input type="hidden" name="do" value="<?= $to ?>"><input type="hidden" name="id" value="<?= (int) $c['order_id'] ?>">
        <button class="btn btn-sm <?= $cls ?>"><?= $to === 'released' ? owBi('Release to the artist', 'حوّل للفنان') : owBi('Refund the collector', 'أعد المبلغ للمقتني') ?></button></form><?php endforeach; ?></span></div>
    <?php endforeach; ?></div>

  <div class="card"><h2><?= owBi('Orders', 'الطلبات') ?> (<?= count($orders) ?>)</h2>
    <?php if (!$orders): ?><p class="mini"><?= owBi('No orders yet.', 'لا طلبات بعد.') ?></p><?php endif; ?>
    <?php foreach ($orders as $o): ?><a class="o-row" href="admin_orders.php?id=<?= (int) $o['id'] ?>"><span><b><?= e($o['order_no'] . ' · ' . $o['title']) ?></b><span class="mini" style="display:block"><?= number_format((float) $o['amount'], 2) ?> SAR · <?= e((string) $o['buyer_name']) ?> · <?= e((string) $o['created_at']) ?></span></span><?= owOrderPill((string) $o['status']) ?></a><?php endforeach; ?></div>

  <div class="card"><h2><?= owBi('Artist payouts', 'مستحقات الفنانين') ?></h2>
    <?php if (!$payouts): ?><p class="mini"><?= owBi('No payouts yet.', 'لا مستحقات بعد.') ?></p><?php endif; ?>
    <?php foreach ($payouts as $p): ?><div class="o-row"><span><b><?= e($p['artist_en']) ?> · <?= number_format((float) $p['amount'], 2) ?> <?= e((string) $p['currency']) ?></b><span class="mini" style="display:block"><?= e((string) $p['note']) ?> · <?= e((string) $p['created_at']) ?><?= $p['reference'] ? ' · ' . e((string) $p['reference']) : '' ?></span></span>
      <?php if ($p['status'] === 'pending'): ?><form method="post" action="admin_orders.php" class="acts"><?= csrfField() ?><input type="hidden" name="do" value="payout_paid"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
        <input name="reference" required maxlength="120" placeholder="<?= e('Bank reference · مرجع التحويل') ?>" style="max-width:200px"><button class="btn btn-accent btn-sm"><?= owBi('Mark paid', 'سجّل الدفع') ?></button></form>
      <?php else: ?><span class="pillx on"><?= e((string) $p['status']) ?></span><?php endif; ?></div><?php endforeach; ?></div>
<?php owAdminBottom();
