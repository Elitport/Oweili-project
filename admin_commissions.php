<?php
/* =====================================================================
 *  admin_commissions.php — private commissions, admin only.
 *  List and one commission: the request, the buyer (only here), the
 *  reference photos, drafts and finals (through commission_file.php), the
 *  buyer's written agreement to show it (or not), the assigned artist and
 *  whether they accepted the confidentiality agreement (with the date),
 *  and the steps. The admin assigns an artist, can cancel, marks it
 *  delivered (the 30-day photo deletion starts then), writes a private
 *  note. Money is handled by hand. Opening this page also runs the
 *  automatic photo deletion.
 *  Tables: commissions, commission_files, commission_nda, users, activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/admin_shell.php';
require_once __DIR__ . '/commission_lib.php';
owAdminGate();
$ready = owCommReady();
$SUBJ = owCommSubjects(); $STAT = owCommStatus();
$ART_TYPES = require __DIR__ . '/art_types.php';
$db = getDB(); $actor = owAdminActor(); $errors = [];
if ($ready) owCommCleanup();

if ($ready && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $c = owCommById((int) ($_POST['id'] ?? 0));
    $do = (string) ($_POST['do'] ?? '');
    if (!$c || !verifyCsrf($_POST['csrf_token'] ?? null)) { header('Location: admin_commissions.php'); exit; }
    if ($do === 'assign' && in_array($c['status'], ['requested', 'assigned'], true)) {
        $aid = (int) ($_POST['artist_id'] ?? 0);
        $ok = $db->prepare("SELECT 1 FROM users WHERE id = :id AND role = 'artist' AND is_approved = 1 AND COALESCE(is_banned, 0) = 0"); $ok->execute([':id' => $aid]);
        if ($ok->fetchColumn()) {
            if (!owCommSetStatus((int) $c['id'], 'assigned', ['artist_id' => $aid, 'assigned_at' => date('Y-m-d H:i:s')], $actor, 'artist ' . $aid, [$c['status']])) { header('Location: admin_commissions.php?id=' . (int) $c['id']); exit; };
            $c['artist_id'] = $aid;
            owCommTellArtist($c, 'A private commission for you · عمل خاص لك', 'The Oweili team chose you for a private commission. Please open it, accept the confidentiality agreement, then send your price.',
                'اختارك فريق أويلي لعمل خاص. يرجى فتحه وقبول اتفاقية السرّية ثم إرسال سعرك.');
        }
    } elseif ($do === 'delivered' && in_array($c['status'], ['final_sent', 'draft_approved'], true)) {
        if (!owCommSetStatus((int) $c['id'], 'delivered', ['delivered_at' => date('Y-m-d H:i:s')], $actor, 'marked delivered by the admin', [$c['status']])) { header('Location: admin_commissions.php?id=' . (int) $c['id']); exit; };
        owCommTellBuyer($c, 'Delivered · تم التسليم', 'Your private commission is delivered. Thank you for trusting Oweili. Your reference photos will be deleted in ' . OW_COMM_KEEP_DAYS . ' days.', 'تم تسليم عملك الخاص. شكراً لثقتك بأويلي. ستُحذف صورك المرجعية بعد ' . OW_COMM_KEEP_DAYS . ' يوماً.');
    } elseif ($do === 'cancel' && !in_array($c['status'], ['delivered', 'cancelled'], true)) {
        $why = mb_substr(trim((string) ($_POST['reason'] ?? '')), 0, 500);
        if ($why === '') $errors[] = ['Please write the reason.', 'يرجى كتابة السبب.'];
        else {
            if (!owCommSetStatus((int) $c['id'], 'cancelled', [], $actor, $why, [$c['status']])) { header('Location: admin_commissions.php?id=' . (int) $c['id']); exit; };
            owCommTellBuyer($c, 'About your private commission · بخصوص عملك الخاص', 'Your private commission was cancelled. The reason: ' . $why . "\r\nYour reference photos will be deleted in " . OW_COMM_KEEP_DAYS . ' days.', 'أُلغي عملك الخاص. السبب: ' . $why . "\r\nستُحذف صورك المرجعية بعد " . OW_COMM_KEEP_DAYS . ' يوماً.');
        }
    } elseif ($do === 'paid') {
        owCommMarkPaid($c, $actor, (string) ($_POST['pay_note'] ?? ''));
    } elseif ($do === 'note') {
        $db->prepare('UPDATE commissions SET admin_note = :n WHERE id = :id')->execute([':n' => mb_substr(trim((string) ($_POST['admin_note'] ?? '')), 0, 4000), ':id' => (int) $c['id']]);
    } elseif ($do === 'delete_refs') {
        $n = 0; foreach (owCommFiles((int) $c['id'], 'reference') as $ff) { owCommDeleteFile($ff, 'admin'); $n++; }
        $db->prepare('UPDATE commissions SET photos_deleted_at = NOW() WHERE id = :id')->execute([':id' => (int) $c['id']]);
        owLog('commission_photos_deleted', 'commission', (int) $c['id'], $n . ' reference photo(s) deleted by the admin', $actor);
    }
    if (!$errors) { header('Location: admin_commissions.php?id=' . (int) $c['id'] . '&ok=1'); exit; }
}
$one = null; $list = []; $nda = null; $files = [];
if ($ready) {
    if (isset($_GET['id']) || isset($_POST['id'])) {
        $one = owCommById((int) ($_GET['id'] ?? $_POST['id']));
        if ($one) {
            $files = owCommFiles((int) $one['id']);
            if ($one['artist_id']) { $q = $db->prepare('SELECT n.*, ' . owArtistNameSql('u') . ' FROM commission_nda n JOIN users u ON u.id = n.artist_id WHERE n.commission_id = :c AND n.artist_id = :a'); $q->execute([':c' => (int) $one['id'], ':a' => (int) $one['artist_id']]); $nda = $q->fetch() ?: null; }
        }
    }
    $list = $db->query('SELECT c.id, c.ref_no, c.subject, c.status, c.buyer_name, c.created_at, c.artist_id, ' . owArtistNameSql('u') . ' FROM commissions c LEFT JOIN users u ON u.id = c.artist_id ORDER BY c.id DESC LIMIT 300')->fetchAll();
}
$artists = $ready ? $db->query("SELECT u.id, " . owArtistNameSql('u') . ", u.city FROM users u WHERE u.role = 'artist' AND u.is_approved = 1 AND COALESCE(u.is_banned, 0) = 0 ORDER BY u.id DESC LIMIT 500")->fetchAll() : [];
$log = [];
if ($one) { $q = $db->prepare("SELECT action, actor_type, details, created_at FROM activity_log WHERE target_type = 'commission' AND target_id = :id ORDER BY id DESC LIMIT 60"); $q->execute([':id' => (int) $one['id']]); $log = $q->fetchAll(); }

owAdminTop('Private commissions', 'الأعمال الخاصة', '
.c-row{display:grid;grid-template-columns:1fr auto;gap:var(--space-sm);align-items:center;padding:var(--space-sm) 0;border-top:1px solid var(--color-border);text-decoration:none;color:inherit;}
.c-row:first-child{border-top:0;}
.pics{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:var(--space-xs);margin-bottom:var(--space-sm);}
.pics a{display:block;aspect-ratio:1;border-radius:10px;overflow:hidden;background:var(--color-frame);position:relative;}
.pics img{width:100%;height:100%;object-fit:cover;display:block;}
.pics span{position:absolute;inset-inline-start:6px;top:6px;font-size:11px;font-weight:700;background:var(--color-surface);border-radius:6px;padding:1px 6px;}
.log li{font-size:var(--fs-caption);margin-bottom:4px;}
', '<a class="btn btn-ghost btn-sm" href="commission.php" target="_blank" rel="noopener">' . owBi('Open the request page', 'افتح صفحة الطلب') . ' ↗</a>');
?>
  <h1><?= owBi('Private commissions', 'الأعمال الخاصة') ?></h1>
  <p class="mini"><?= owBi('Portraits and personal photos, in full confidence. Photos open only here, for the collector with their link, and for the assigned artist after the confidentiality agreement. Money is handled by hand.', 'بورتريهات وصور شخصية بسرّية تامة. تُفتح الصور هنا فقط، وللمقتني عبر رابطه، وللفنان المُسند إليه بعد اتفاقية السرّية. يُدار المال يدوياً.') ?></p>
  <?php if (!$ready): ?><div class="alert alert-error"><?= owBi('The commission tables are not set up yet (night_update.sql).', 'جداول الأعمال الخاصة غير مُعدّة بعد (night_update.sql).') ?></div><?php endif; ?>
  <?php if (isset($_GET['ok'])): ?><div class="alert alert-ok"><?= owBi('Saved.', 'تم الحفظ.') ?></div><?php endif; ?>
  <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= owBi($er[0], $er[1]) ?></div><?php endforeach; ?>

<?php if ($one): $fu = fn($ff) => 'commission_file.php?id=' . (int) $ff['id']; ?>
  <div class="card">
    <p><a href="admin_commissions.php">← <?= owBi('All commissions', 'كل الأعمال الخاصة') ?></a></p>
    <h2><?= e($one['ref_no']) ?> · <?= owBi($SUBJ[$one['subject']][0] ?? '', $SUBJ[$one['subject']][1] ?? '') ?> <?= owCommPill((string) $one['status']) ?></h2>
    <dl class="kv">
      <dt><?= owBi('Request', 'الطلب') ?></dt><dd dir="auto"><?= nl2br(e((string) $one['brief'])) ?></dd>
      <?php if ($one['art_style'] !== '' && isset($ART_TYPES[$one['art_style']])): ?><dt><?= owBi('Style', 'الأسلوب') ?></dt><dd><?= owBi($ART_TYPES[$one['art_style']]['en'], $ART_TYPES[$one['art_style']]['ar']) ?></dd><?php endif; ?>
      <dt><?= owBi('Size · budget · by', 'المقاس · الميزانية · الموعد') ?></dt><dd><?= e($one['size_note'] ?: '—') ?> · <?= $one['budget_sar'] !== null ? number_format((float) $one['budget_sar']) . ' SAR' : '—' ?> · <?= e((string) ($one['wanted_by'] ?: '—')) ?></dd>
      <dt><?= owBi('Collector', 'المقتني') ?></dt><dd><?= e((string) $one['buyer_name']) ?><?php if ($one['user_id']): ?> · <a href="admin.php?person=<?= (int) $one['user_id'] ?>"><?= owBi('account', 'الحساب') ?></a><?php endif; ?><br>
        <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', (string) $one['buyer_phone'])) ?>" dir="ltr"><?= e((string) $one['buyer_phone']) ?></a> · <a href="mailto:<?= e((string) $one['buyer_email']) ?>" dir="ltr"><?= e((string) $one['buyer_email']) ?></a> · <?= $one['lang'] === 'ar' ? 'العربية' : 'English' ?></dd>
      <dt><?= owBi('May be shown', 'يُسمح بعرضه') ?></dt><dd><?= (int) $one['may_show'] === 1 ? owBi('Yes, agreed in writing', 'نعم، بموافقة كتابية') . ' · <span dir="auto">' . e((string) $one['may_show_text']) . '</span> · ' . e((string) $one['may_show_at']) : owBi('No. Never show it.', 'لا. لا يُعرض أبداً.') ?></dd>
      <dt><?= owBi('Price', 'السعر') ?></dt><dd><?= $one['quote_sar'] !== null ? number_format((float) $one['quote_sar']) . ' SAR · ' . (int) $one['quote_days'] . ' ' . owBi('days', 'يوماً') . ($one['quote_note'] !== '' ? ' · ' . e((string) $one['quote_note']) : '') : '—' ?></dd>
      <?php if ($one['buyer_note'] !== ''): ?><dt><?= owBi('Collector’s last note', 'آخر ملاحظة للمقتني') ?></dt><dd dir="auto"><?= e((string) $one['buyer_note']) ?></dd><?php endif; ?>
      <dt><?= owBi('Artist', 'الفنان') ?></dt><dd><?php if ($one['artist_id']): ?><a href="admin.php?person=<?= (int) $one['artist_id'] ?>">#<?= (int) $one['artist_id'] ?></a> · <?= $nda ? owBi('confidentiality accepted ' . $nda['accepted_at'] . ' (signed "' . $nda['typed_name'] . '")', 'قبل اتفاقية السرّية ' . $nda['accepted_at'] . ' (وقّع «' . $nda['typed_name'] . '»)') : owBi('has NOT accepted the confidentiality agreement yet: no photo is shown to them', 'لم يقبل اتفاقية السرّية بعد: لا تُعرض عليه أي صورة') ?><?php else: ?><?= owBi('Not chosen yet', 'لم يُختر بعد') ?><?php endif; ?></dd>
      <?php $paidAt = owCommPaidAt((int) $one['id']); $left = owCommDaysToExpire($one); ?>
      <dt><?= owBi('Payment', 'الدفع') ?></dt><dd><?= $paidAt ? '<span class="pillx on">' . owBi('Paid', 'مدفوع') . '</span> ' . e($paidAt) . ' · ' . owBi('the artist can see the photos and work', 'يستطيع الفنان رؤية الصور والعمل') : '<span class="pillx warn">' . owBi('Not paid yet', 'لم يُدفع بعد') . '</span> ' . owBi('the artist cannot see the reference photos or start', 'لا يستطيع الفنان رؤية الصور المرجعية أو البدء') . ($left !== null ? ' · ' . owBi('photos deleted in ' . $left . ' days if not paid', 'تُحذف الصور بعد ' . $left . ' يوماً إن لم يُدفع') : '') ?></dd>
      <dt><?= owBi('Delivered', 'التسليم') ?></dt><dd><?= e((string) ($one['delivered_at'] ?: '—')) ?><?= $one['photos_deleted_at'] ? ' · ' . owBi('reference photos deleted ' . $one['photos_deleted_at'], 'حُذفت الصور المرجعية ' . $one['photos_deleted_at']) : '' ?></dd>
    </dl>
    <?php if ($files): ?><div class="pics"><?php foreach ($files as $ff): $kw = ['reference' => ['Reference', 'مرجعية'], 'draft' => ['Draft', 'مسودة'], 'final' => ['Final', 'نهائية']][$ff['kind']]; ?>
      <a href="<?= e($fu($ff)) ?>" target="_blank" rel="noopener noreferrer"><img src="<?= e($fu($ff)) ?>" alt="<?= e($kw[0] . ' · ' . $kw[1]) ?>"><span><?= owBi($kw[0], $kw[1]) ?></span></a><?php endforeach; ?></div><?php endif; ?>

    <div class="acts">
      <?php if (in_array($one['status'], ['requested', 'assigned'], true)): ?>
      <form method="post" action="admin_commissions.php"><?= csrfField() ?><input type="hidden" name="do" value="assign"><input type="hidden" name="id" value="<?= (int) $one['id'] ?>">
        <select name="artist_id" aria-label="Artist · الفنان"><?php foreach ($artists as $a): ?><option value="<?= (int) $a['id'] ?>"<?= (int) $one['artist_id'] === (int) $a['id'] ? ' selected' : '' ?>><?= e($a['artist_en'] . ' · ' . $a['artist_ar'] . ($a['city'] ? ' (' . $a['city'] . ')' : '')) ?></option><?php endforeach; ?></select>
        <button class="btn btn-accent btn-sm"><?= $one['artist_id'] ? owBi('Change artist', 'غيّر الفنان') : owBi('Assign this artist', 'أسند إلى هذا الفنان') ?></button></form>
      <?php endif; ?>
      <?php if (!$paidAt && !in_array($one['status'], ['cancelled', 'delivered'], true)): ?>
      <details><summary class="btn btn-accent btn-sm"><?= owBi('Mark as paid…', 'سجّل الدفع…') ?></summary>
        <form method="post" action="admin_commissions.php" onsubmit="return confirm('The artist will see the reference photos and can start. Continue? · سيرى الفنان الصور المرجعية ويمكنه البدء. متابعة؟')"><?= csrfField() ?><input type="hidden" name="do" value="paid"><input type="hidden" name="id" value="<?= (int) $one['id'] ?>">
          <div class="field"><label><?= owBi('Note (optional, e.g. the bank transfer reference)', 'ملاحظة (اختيارية، مثل مرجع التحويل البنكي)') ?><input name="pay_note" maxlength="300"></label></div>
          <button class="btn btn-accent btn-sm"><?= owBi('Confirm: it is paid', 'تأكيد: تم الدفع') ?></button></form></details>
      <?php endif; ?>
      <?php if (in_array($one['status'], ['final_sent', 'draft_approved'], true)): ?>
      <form method="post" action="admin_commissions.php"><?= csrfField() ?><input type="hidden" name="do" value="delivered"><input type="hidden" name="id" value="<?= (int) $one['id'] ?>"><button class="btn btn-primary btn-sm"><?= owBi('Mark as delivered', 'سجّل التسليم') ?></button></form>
      <?php endif; ?>
      <?php if (owCommFiles((int) $one['id'], 'reference')): ?>
      <form method="post" action="admin_commissions.php" onsubmit="return confirm('Delete the reference photos now? · حذف الصور المرجعية الآن؟')"><?= csrfField() ?><input type="hidden" name="do" value="delete_refs"><input type="hidden" name="id" value="<?= (int) $one['id'] ?>"><button class="btn btn-danger btn-sm"><?= owBi('Delete reference photos now', 'احذف الصور المرجعية الآن') ?></button></form>
      <?php endif; ?>
      <?php if (!in_array($one['status'], ['delivered', 'cancelled'], true)): ?>
      <details><summary class="btn btn-danger btn-sm"><?= owBi('Cancel…', 'إلغاء…') ?></summary>
        <form method="post" action="admin_commissions.php"><?= csrfField() ?><input type="hidden" name="do" value="cancel"><input type="hidden" name="id" value="<?= (int) $one['id'] ?>">
          <div class="field"><label><?= owBi('Reason (the collector sees it)', 'السبب (يراه المقتني)') ?><textarea name="reason" rows="2" required maxlength="500"></textarea></label></div>
          <button class="btn btn-danger btn-sm"><?= owBi('Cancel the commission', 'ألغِ الطلب') ?></button></form></details>
      <?php endif; ?>
    </div>
    <form method="post" action="admin_commissions.php" style="margin-top:var(--space-md)"><?= csrfField() ?><input type="hidden" name="do" value="note"><input type="hidden" name="id" value="<?= (int) $one['id'] ?>">
      <div class="field"><label><?= owBi('Private note (payments, calls; only the admin sees it)', 'ملاحظة خاصة (الدفع، المكالمات؛ للإدارة فقط)') ?><textarea name="admin_note" rows="3" maxlength="4000"><?= e((string) $one['admin_note']) ?></textarea></label></div>
      <button class="btn btn-secondary btn-sm"><?= owBi('Save note', 'احفظ الملاحظة') ?></button></form>
    <?php if ($log): ?><details><summary><?= owBi('History', 'السجل') ?></summary><ul class="log"><?php foreach ($log as $l): ?><li><?= e($l['created_at'] . ' · ' . $l['action'] . ' · ' . $l['actor_type'] . ($l['details'] !== '' ? ' · ' . $l['details'] : '')) ?></li><?php endforeach; ?></ul></details><?php endif; ?>
  </div>
<?php endif; ?>

<?php if ($ready): ?>
  <div class="card">
    <?php if (!$list): ?><p class="mini"><?= owBi('No private commissions yet.', 'لا أعمال خاصة بعد.') ?></p><?php endif; ?>
    <?php foreach ($list as $r): [$de, $da] = owDateBi($r['created_at']); ?>
    <a class="c-row" href="admin_commissions.php?id=<?= (int) $r['id'] ?>"><span><b><?= e($r['ref_no']) ?> · <?= owBi($SUBJ[$r['subject']][0] ?? '', $SUBJ[$r['subject']][1] ?? '') ?></b>
      <span class="mini" style="display:block"><?= e((string) $r['buyer_name']) ?> · <?= $r['artist_id'] ? owBi('artist: ' . $r['artist_en'], 'الفنان: ' . $r['artist_ar']) : owBi('no artist yet', 'بلا فنان بعد') ?> · <?= owBi($de, $da) ?></span></span><?= owCommPill((string) $r['status']) ?></a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php owAdminBottom();
