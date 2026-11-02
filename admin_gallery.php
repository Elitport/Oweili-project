<?php
/* =====================================================================
 *  admin_gallery.php — the admin shapes the Gallery (admin only)
 *    Collections      curated sets ("Under 1,000 SAR", "Calligraphy",
 *                     "Graduation gifts"): title and intro in both
 *                     languages, an optional rule (price up to / from, one
 *                     art type) and artworks picked by hand. Shown on top of
 *                     the Gallery and at marketplace.php?col=<slug>. Hidden,
 *                     never deleted.
 *    Artist of week   one approved artist + a short line in both languages,
 *                     shown on the homepage and on top of the Gallery
 *    Verified artist  the badge, given after checking an artist
 *  Tables: collections, collection_items, site_settings, users, artworks,
 *          activity_log
 * ===================================================================== */
require_once 'config.php';
require_once __DIR__ . '/admin_shell.php';
require_once __DIR__ . '/gallery_lib.php';
owAdminGate();
$ART_TYPES = require __DIR__ . '/art_types.php';
$actor = owAdminActor();
$db = getDB();
$ready = owCollectionsReady() && owHasTable('site_settings') && owVerifiedReady();
$txt = fn(string $k, int $max) => mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) ($_POST[$k] ?? ''))), 0, $max);
$num = function (string $k): ?float { $v = trim(str_replace([',', ' '], '', strtr((string) ($_POST[$k] ?? ''), ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'])));
    return $v === '' ? null : (is_numeric($v) && (float) $v >= 0 && (float) $v < 100000000 ? round((float) $v, 2) : -1.0); };
$errors = [];
$tab = in_array($_GET['tab'] ?? '', ['collections', 'week', 'verified'], true) ? $_GET['tab'] : 'collections';

if ($ready && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $do = (string) ($_POST['do'] ?? '');
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = ['The page was open too long. Please try again.', 'انتهت صلاحية الصفحة. حاول مرة أخرى.'];
    } elseif ($do === 'collection') {
        $id = (int) ($_POST['id'] ?? 0);
        $v = ['title_en' => $txt('title_en', 150), 'title_ar' => $txt('title_ar', 150), 'intro_en' => $txt('intro_en', 500), 'intro_ar' => $txt('intro_ar', 500),
              'rule_max_price' => $num('rule_max_price'), 'rule_min_price' => $num('rule_min_price'), 'rule_type' => (string) ($_POST['rule_type'] ?? ''),
              'is_active' => isset($_POST['is_active']) ? 1 : 0, 'sort_order' => max(-999, min(999, (int) ($_POST['sort_order'] ?? 0)))];
        if ($v['title_en'] === '' || $v['title_ar'] === '') $errors[] = ['The title is needed in both languages.', 'العنوان مطلوب باللغتين.'];
        if ($v['rule_max_price'] === -1.0 || $v['rule_min_price'] === -1.0) $errors[] = ['Please write the prices as numbers.', 'يرجى كتابة الأسعار كأرقام.'];
        if ($v['rule_type'] !== '' && !isset($ART_TYPES[$v['rule_type']])) $v['rule_type'] = '';
        if (!$errors) {
            if ($id > 0) {
                $db->prepare('UPDATE collections SET title_en=:te, title_ar=:ta, intro_en=:ie, intro_ar=:ia, rule_max_price=:mx, rule_min_price=:mn, rule_type=:rt, is_active=:on, sort_order=:so WHERE id=:id')
                   ->execute([':te' => $v['title_en'], ':ta' => $v['title_ar'], ':ie' => $v['intro_en'], ':ia' => $v['intro_ar'], ':mx' => $v['rule_max_price'], ':mn' => $v['rule_min_price'],
                              ':rt' => $v['rule_type'], ':on' => $v['is_active'], ':so' => $v['sort_order'], ':id' => $id]);
            } else {
                $db->prepare('INSERT INTO collections (slug, title_en, title_ar, intro_en, intro_ar, rule_max_price, rule_min_price, rule_type, is_active, sort_order) VALUES (:sl,:te,:ta,:ie,:ia,:mx,:mn,:rt,:on,:so)')
                   ->execute([':sl' => owCollectionSlug($v['title_en']), ':te' => $v['title_en'], ':ta' => $v['title_ar'], ':ie' => $v['intro_en'], ':ia' => $v['intro_ar'], ':mx' => $v['rule_max_price'],
                              ':mn' => $v['rule_min_price'], ':rt' => $v['rule_type'], ':on' => $v['is_active'], ':so' => $v['sort_order']]);
                $id = (int) $db->lastInsertId();
            }
            owLog('collection_save', 'collection', $id, $v['title_en'], $actor);
            header('Location: admin_gallery.php?ok=1&edit=' . $id . '#c' . $id); exit;
        }
    } elseif ($do === 'items') {
        $id = (int) ($_POST['id'] ?? 0);
        $ex = $db->prepare('SELECT 1 FROM collections WHERE id = :id'); $ex->execute([':id' => $id]);
        if ($ex->fetchColumn()) {
            $pick = array_slice(array_unique(array_filter(array_map('intval', (array) ($_POST['pick'] ?? [])))), 0, 200);
            $db->beginTransaction();
            $db->prepare('DELETE FROM collection_items WHERE collection_id = :id')->execute([':id' => $id]);
            $ins = $db->prepare('INSERT INTO collection_items (collection_id, artwork_id, sort_order) SELECT :c, a.id, :o FROM artworks a WHERE a.id = :a');
            foreach ($pick as $i => $aid) $ins->execute([':c' => $id, ':o' => $i, ':a' => $aid]);
            $db->commit();
            owLog('collection_items', 'collection', $id, count($pick) . ' picked', $actor);
            header('Location: admin_gallery.php?ok=1&edit=' . $id . '#c' . $id); exit;
        }
    } elseif ($do === 'week') {
        $aid = (int) ($_POST['artist_id'] ?? 0);
        if ($aid === 0) { owSetSetting('artist_of_week', null); owLog('artist_of_week', 'user', null, 'cleared', $actor); header('Location: admin_gallery.php?tab=week&ok=1'); exit; }
        $ex = $db->prepare("SELECT 1 FROM users WHERE id = :id AND role = 'artist' AND is_approved = 1 AND COALESCE(is_banned, 0) = 0"); $ex->execute([':id' => $aid]);
        if (!$ex->fetchColumn()) $errors[] = ['Please choose an approved artist.', 'يرجى اختيار فنان معتمد.'];
        else {
            owSetSetting('artist_of_week', json_encode(['id' => $aid, 'note_en' => $txt('note_en', 220), 'note_ar' => $txt('note_ar', 220), 'at' => gmdate('Y-m-d H:i:s')], JSON_UNESCAPED_UNICODE));
            owLog('artist_of_week', 'user', $aid, '', $actor);
            header('Location: admin_gallery.php?tab=week&ok=1'); exit;
        }
        $tab = 'week';
    } elseif ($do === 'verify') {
        owSetVerifiedArtist((int) ($_POST['artist_id'] ?? 0), ($_POST['on'] ?? '') === '1');
        header('Location: admin_gallery.php?tab=verified&ok=1#a' . (int) ($_POST['artist_id'] ?? 0)); exit;
    }
}

$cols = $ready ? owCollections(false) : [];
$covers = $cols ? owCollectionCovers($cols, 1) : [];
$edit = (int) ($_GET['edit'] ?? 0);
$visible = [];
if ($ready) {
    $visible = $db->query("SELECT a.id, a.title_en, a.title_ar, a.price, a.type, a.image_url, " . owArtistNameSql('u') . " FROM artworks a JOIN users u ON u.id = a.artist_id
                           WHERE a.is_approved = 1 AND a.status IN ('available','auction') AND u.is_banned = 0" . owNotDraftSql('a') . " ORDER BY a.created_at DESC, a.id DESC LIMIT 400")->fetchAll();
}
$picked = [];
if ($edit && $ready) { $st = $db->prepare('SELECT artwork_id FROM collection_items WHERE collection_id = :id'); $st->execute([':id' => $edit]); $picked = array_flip(array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN))); }
$artists = $ready ? $db->query("SELECT u.id, u.email, u.city, u.is_approved, u.is_verified_artist, u.verified_artist_at, " . owArtistNameSql('u') . ",
                                  (SELECT COUNT(*) FROM artworks a WHERE a.artist_id = u.id AND a.is_approved = 1" . owNotDraftSql('a') . ") AS works
                                  FROM users u WHERE u.role = 'artist' AND COALESCE(u.is_banned, 0) = 0 ORDER BY u.is_verified_artist DESC, u.is_approved DESC, u.id DESC LIMIT 500")->fetchAll() : [];
$week = $ready ? json_decode((string) owSetting('artist_of_week', ''), true) : null;
$cv = fn(array $r, string $k) => e((string) ($_POST[$k] ?? $r[$k] ?? ''));

owAdminTop('Gallery', 'المعرض', '
.tabs{margin:0 0 var(--space-md);}
.c-head{display:flex;justify-content:space-between;align-items:center;gap:var(--space-sm);flex-wrap:wrap;}
.c-head h2{margin:0;}
.c-thumb{width:56px;height:42px;border-radius:6px;object-fit:cover;background:var(--color-frame);vertical-align:middle;}
.pick{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:var(--space-xs);max-height:520px;overflow:auto;padding:4px;}
.pick label{display:grid;grid-template-columns:auto 1fr;gap:6px;align-items:start;padding:6px;border:1px solid var(--color-border);border-radius:8px;font-size:var(--fs-caption);cursor:pointer;background:var(--color-surface);}
.pick label:has(input:checked){border-color:var(--color-accent);background:var(--color-tint-blue);}
.pick img{grid-column:1 / -1;width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:6px;background:var(--color-frame);}
.pick b{display:block;color:var(--color-dark);font-size:13px;}
.pick-search{max-width:360px;margin-bottom:var(--space-xs);}
.a-row{display:grid;grid-template-columns:1fr auto;gap:var(--space-sm);align-items:center;padding:var(--space-sm) 0;border-top:1px solid var(--color-border);}
.a-row:first-child{border-top:0;}
', '<a class="btn btn-ghost btn-sm" href="marketplace.php" target="_blank" rel="noopener">' . owBi('See the Gallery', 'شاهد المعرض') . ' ↗</a>');
?>
  <h1><?= owBi('Gallery', 'المعرض') ?></h1>
  <p class="mini"><?= owBi('Shape what visitors see first: curated collections, the Artist of the week and the Verified artist badge.', 'شكّل ما يراه الزوار أولاً: المجموعات المختارة وفنان الأسبوع وشارة الفنان الموثّق.') ?></p>
  <?php if (!$ready): ?><div class="alert alert-error"><?= owBi('The Gallery tables are not set up yet (night_update.sql).', 'جداول المعرض غير مُعدّة بعد (night_update.sql).') ?></div><?php endif; ?>
  <?php if (isset($_GET['ok'])): ?><div class="alert alert-ok"><?= owBi('Saved.', 'تم الحفظ.') ?></div><?php endif; ?>
  <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= owBi($er[0], $er[1]) ?></div><?php endforeach; ?>

  <nav class="tabs"><?php foreach (['collections' => ['Collections', 'المجموعات'], 'week' => ['Artist of the week', 'فنان الأسبوع'], 'verified' => ['Verified artists', 'الفنانون الموثّقون']] as $t => $l): ?>
    <a class="tab<?= $tab === $t ? ' active' : '' ?>" href="admin_gallery.php?tab=<?= $t ?>"><?= owBi($l[0], $l[1]) ?></a><?php endforeach; ?></nav>

<?php if ($ready && $tab === 'collections'): ?>
  <?php $colForm = function (array $c) use ($cv, $ART_TYPES): void { ?>
    <form method="post" action="admin_gallery.php<?= !empty($c['id']) ? '?edit=' . (int) $c['id'] : '' ?>">
      <?= csrfField() ?><input type="hidden" name="do" value="collection"><input type="hidden" name="id" value="<?= (int) ($c['id'] ?? 0) ?>">
      <div class="g2">
        <div class="field"><label><?= owBi('Title (English)', 'العنوان (بالإنجليزية)') ?><input name="title_en" required maxlength="150" value="<?= $cv($c, 'title_en') ?>" placeholder="Under 1,000 SAR"></label></div>
        <div class="field"><label><?= owBi('Title (Arabic)', 'العنوان (بالعربية)') ?><input name="title_ar" required maxlength="150" dir="rtl" value="<?= $cv($c, 'title_ar') ?>" placeholder="أقل من 1,000 ر.س"></label></div>
        <div class="field"><label><?= owBi('Short intro (English)', 'مقدمة قصيرة (بالإنجليزية)') ?><input name="intro_en" maxlength="500" value="<?= $cv($c, 'intro_en') ?>"></label></div>
        <div class="field"><label><?= owBi('Short intro (Arabic)', 'مقدمة قصيرة (بالعربية)') ?><input name="intro_ar" maxlength="500" dir="rtl" value="<?= $cv($c, 'intro_ar') ?>"></label></div>
      </div>
      <p class="mini"><?= owBi('Rule (optional): every Gallery artwork that matches is added by itself. Artworks you pick by hand are always in.', 'قاعدة (اختيارية): يُضاف كل عمل في المعرض يطابقها تلقائياً. الأعمال التي تختارها بيدك موجودة دائماً.') ?></p>
      <div class="g3">
        <div class="field"><label><?= owBi('Price up to (SAR)', 'السعر حتى (ر.س)') ?><input name="rule_max_price" inputmode="decimal" dir="ltr" value="<?= $cv($c, 'rule_max_price') ?>"></label></div>
        <div class="field"><label><?= owBi('Price from (SAR)', 'السعر من (ر.س)') ?><input name="rule_min_price" inputmode="decimal" dir="ltr" value="<?= $cv($c, 'rule_min_price') ?>"></label></div>
        <div class="field"><label><?= owBi('Art type', 'نوع الفن') ?><select name="rule_type"><option value=""><?= e('— Any · أي نوع —') ?></option>
          <?php $cur = (string) ($_POST['rule_type'] ?? $c['rule_type'] ?? ''); foreach ($ART_TYPES as $tk => $tv): if (!empty($tv['legacy']) && $cur !== $tk) continue; ?><option value="<?= e($tk) ?>"<?= $cur === $tk ? ' selected' : '' ?>><?= e($tv['en'] . ' · ' . $tv['ar']) ?></option><?php endforeach; ?></select></label></div>
      </div>
      <div class="g2">
        <div class="field"><label><?= owBi('Order (smaller shows first)', 'الترتيب (الأصغر أولاً)') ?><input name="sort_order" type="number" min="-999" max="999" dir="ltr" value="<?= $cv($c, 'sort_order') ?: '0' ?>"></label></div>
        <label class="chk"><input type="checkbox" name="is_active" value="1"<?= ((int) ($c['is_active'] ?? 1)) === 1 ? ' checked' : '' ?>> <?= owBi('Shown in the Gallery', 'ظاهرة في المعرض') ?></label>
      </div>
      <button class="btn btn-primary btn-sm" type="submit"><?= owBi('Save collection', 'احفظ المجموعة') ?></button>
    </form>
  <?php }; ?>
  <div class="card"><details<?= !$cols ? ' open' : '' ?>><summary><?= owBi('+ New collection', '+ مجموعة جديدة') ?></summary><?php $colForm([]); ?></details></div>
  <?php if (!$cols): ?><div class="card"><p><?= owBi('No collections yet. Ideas: "Under 1,000 SAR", "Calligraphy", "Graduation gifts".', 'لا مجموعات بعد. أفكار: «أقل من 1,000 ر.س»، «الخط العربي»، «هدايا التخرج».') ?></p></div><?php endif; ?>
  <?php foreach ($cols as $c): $cid = (int) $c['id']; $cc = $covers[$cid] ?? ['pics' => [], 'count' => 0]; $p0 = $cc['pics'][0] ?? null; ?>
  <div class="card" id="c<?= $cid ?>">
    <div class="c-head"><h2><?php if ($p0 && ($pi = owImgSrc((string) $p0['image_url'])) !== ''): ?><img class="c-thumb" src="<?= e(owImgSized($pi, 400)) ?>" alt=""> <?php endif; ?><?= e($c['title_en']) ?> · <span dir="rtl"><?= e($c['title_ar']) ?></span></h2>
      <span class="acts"><span class="pillx<?= (int) $c['is_active'] === 1 ? ' on' : '' ?>"><?= (int) $c['is_active'] === 1 ? owBi('Shown', 'ظاهرة') : owBi('Hidden', 'مخفية') ?></span>
        <span class="mini"><?= owBi($cc['count'] . ' artworks now', $cc['count'] . ' عملاً الآن') ?></span>
        <a class="btn btn-ghost btn-sm" href="marketplace.php?col=<?= e(rawurlencode($c['slug'])) ?>" target="_blank" rel="noopener"><?= owBi('Open', 'افتح') ?> ↗</a></span></div>
    <details<?= $edit === $cid ? ' open' : '' ?>><summary><?= owBi('Edit', 'تعديل') ?></summary><?php $colForm($c); ?></details>
    <details<?= $edit === $cid ? ' open' : '' ?>><summary><?= owBi('Pick artworks by hand', 'اختر الأعمال بيدك') ?></summary>
      <?php if (!$visible): ?><p class="mini"><?= owBi('There are no artworks in the Gallery yet.', 'لا توجد أعمال في المعرض بعد.') ?></p><?php else:
        if ($edit !== $cid) { $st = $db->prepare('SELECT artwork_id FROM collection_items WHERE collection_id = :id'); $st->execute([':id' => $cid]); $pk = array_flip(array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN))); } else $pk = $picked; ?>
      <form method="post" action="admin_gallery.php?edit=<?= $cid ?>">
        <?= csrfField() ?><input type="hidden" name="do" value="items"><input type="hidden" name="id" value="<?= $cid ?>">
        <input class="input pick-search" type="search" placeholder="<?= e('Search · ابحث') ?>" oninput="var q=this.value.toLowerCase();this.nextElementSibling.querySelectorAll('label').forEach(function(l){l.hidden=q&&l.textContent.toLowerCase().indexOf(q)<0;})">
        <div class="pick">
          <?php foreach ($visible as $w): $wi = owImgSrc((string) $w['image_url']); ?>
          <label><input type="checkbox" name="pick[]" value="<?= (int) $w['id'] ?>"<?= isset($pk[(int) $w['id']]) ? ' checked' : '' ?>><span><b><?= e($w['title_en'] ?: $w['title_ar']) ?></b><?= e($w['artist_en']) ?> · <?= number_format((float) $w['price']) ?> SAR</span>
            <?php if ($wi !== ''): ?><img loading="lazy" src="<?= e(owImgSized($wi, 400)) ?>" alt=""><?php endif; ?></label>
          <?php endforeach; ?>
        </div>
        <p><button class="btn btn-primary btn-sm" type="submit"><?= owBi('Save the picks', 'احفظ الاختيار') ?></button></p>
      </form>
      <?php endif; ?>
    </details>
  </div>
  <?php endforeach; ?>

<?php elseif ($ready && $tab === 'week'): $wk = owArtistOfWeek(); ?>
  <div class="card">
    <h2><?= owBi('Artist of the week', 'فنان الأسبوع') ?></h2>
    <?php if ($wk): ?><p><?= owBi('Now: ', 'الآن: ') ?><b><?= e($wk['artist_en']) ?></b> <?php if (!empty($week['at'])): [$we, $wa] = owDateBi($week['at']); ?><span class="mini">(<?= owBi('since ' . $we, 'منذ ' . $wa) ?>)</span><?php endif; ?></p>
    <?php else: ?><p class="mini"><?= owBi('No one is picked. The homepage and the Gallery show nothing in this place.', 'لم يُختر أحد. لا تعرض الصفحة الرئيسية والمعرض شيئاً في هذا المكان.') ?></p><?php endif; ?>
    <form method="post" action="admin_gallery.php?tab=week">
      <?= csrfField() ?><input type="hidden" name="do" value="week">
      <div class="field"><label><?= owBi('Artist', 'الفنان') ?><select name="artist_id"><option value="0"><?= e('— No one · لا أحد —') ?></option>
        <?php foreach ($artists as $a): if ((int) $a['is_approved'] !== 1) continue; ?><option value="<?= (int) $a['id'] ?>"<?= (int) ($week['id'] ?? 0) === (int) $a['id'] ? ' selected' : '' ?>><?= e($a['artist_en'] . ' · ' . $a['artist_ar'] . ' (' . (int) $a['works'] . ')') ?></option><?php endforeach; ?></select></label></div>
      <div class="g2">
        <div class="field"><label><?= owBi('One line about them (English)', 'سطر عنه (بالإنجليزية)') ?><input name="note_en" maxlength="220" value="<?= e((string) ($week['note_en'] ?? '')) ?>"></label></div>
        <div class="field"><label><?= owBi('One line about them (Arabic)', 'سطر عنه (بالعربية)') ?><input name="note_ar" maxlength="220" dir="rtl" value="<?= e((string) ($week['note_ar'] ?? '')) ?>"></label></div>
      </div>
      <button class="btn btn-primary btn-sm" type="submit"><?= owBi('Save', 'احفظ') ?></button>
    </form>
  </div>

<?php elseif ($ready): ?>
  <div class="card">
    <h2><?= owBi('Verified artists', 'الفنانون الموثّقون') ?></h2>
    <p class="mini"><?= owBi('Give the badge after you check an artist (who they are, and that the work is their own). Visitors see it on cards, the artwork window and the profile, and can filter by it.', 'امنح الشارة بعد أن تتحقق من الفنان (هويته، وأن العمل من صنعه). يراها الزوار على البطاقات ونافذة العمل والملف، ويمكنهم التصفية بها.') ?></p>
    <?php if (!$artists): ?><p class="mini"><?= owBi('No artists yet.', 'لا فنانين بعد.') ?></p><?php endif; ?>
    <?php foreach ($artists as $a): $on = (int) $a['is_verified_artist'] === 1; ?>
    <div class="a-row" id="a<?= (int) $a['id'] ?>">
      <div><b><?= e($a['artist_en']) ?></b> · <span dir="rtl"><?= e($a['artist_ar']) ?></span>
        <div class="mini"><?= e((string) $a['city']) ?> · <?= (int) $a['works'] ?> <?= owBi('approved works', 'أعمال معتمدة') ?> · <?= (int) $a['is_approved'] === 1 ? owBi('account approved', 'الحساب معتمد') : owBi('account waiting', 'الحساب بانتظار الموافقة') ?>
          <?php if ($on && $a['verified_artist_at']): [$ve, $va] = owDateBi($a['verified_artist_at']); ?> · <?= owBi('verified ' . $ve, 'وُثّق ' . $va) ?><?php endif; ?></div></div>
      <div class="acts"><?php if ($on): ?><?= owVerifiedBadge() ?><?php endif; ?>
        <a class="btn btn-ghost btn-sm" href="admin.php?person=<?= (int) $a['id'] ?>"><?= owBi('View', 'عرض') ?></a>
        <form method="post" action="admin_gallery.php?tab=verified"><?= csrfField() ?><input type="hidden" name="do" value="verify"><input type="hidden" name="artist_id" value="<?= (int) $a['id'] ?>"><input type="hidden" name="on" value="<?= $on ? '0' : '1' ?>">
          <button class="btn <?= $on ? 'btn-danger' : 'btn-accent' ?> btn-sm" type="submit"><?= $on ? owBi('Remove badge', 'أزل الشارة') : owBi('Give the badge', 'امنح الشارة') ?></button></form></div>
    </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php owAdminBottom();
