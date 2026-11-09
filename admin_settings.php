<?php
/* =====================================================================
 *  admin_settings.php — "Settings" in admin.php (admin.php?settings=1).
 *  Opened only from admin.php after the admin password check; opening
 *  this file directly shows nothing. Read only: it shows what is
 *  switched on, where the checks are, the database backups, and how the
 *  Bashro connection is switched on (it is off by default).
 *  Secrets are never shown. Tables read: none (column checks only)
 * ===================================================================== */
if (!defined('OW_ADMIN_OK')) { http_response_code(404); exit; }

$bashroOn  = defined('BASHRO_API_ENABLED') && BASHRO_API_ENABLED === true && defined('BASHRO_API_KEY') && strlen((string) BASHRO_API_KEY) >= 32;
$bashroIps = defined('BASHRO_API_IPS') ? trim((string) BASHRO_API_IPS) : '';
$backups   = glob(__DIR__ . '/.backups/*.gz') ?: [];
rsort($backups);
$checks = [
    [owPlatformReady(), 'Dashboards database update is in place', 'تحديث قاعدة البيانات للوحات موجود'],
    [defined('OW_ERROR_GUARD'), 'Visitors never see error details (private error log on)', 'لا يرى الزوار تفاصيل الأخطاء (السجل الخاص يعمل)'],
    [owSmtpReady(), 'Email box is set up', 'صندوق البريد مُعدّ'],
    [is_writable(__DIR__ . '/uploads'), 'Photos can be saved (uploads folder)', 'يمكن حفظ الصور (مجلد الرفع)'],
    [is_file(__DIR__ . '/robots.txt'), 'robots.txt for search engines', 'ملف robots.txt لمحركات البحث'],
];
$yes = fn(bool $b) => '<span class="pillx ' . ($b ? 'ok' : 'no') . '">' . ($b ? owBi('Yes', 'نعم') : owBi('No', 'لا')) . '</span>';
?>
<!DOCTYPE html>
<html <?php require_once __DIR__ . '/lang_lib.php'; owHtmlAttrs(); ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>Settings · Admin · Oweili</title>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
.wrap{max-width:860px;margin:0 auto;padding:var(--space-lg) var(--space-sm);}
.top{display:flex;justify-content:space-between;align-items:center;gap:var(--space-sm);flex-wrap:wrap;margin-bottom:var(--space-md);}
.wrap .card{margin-bottom:var(--space-md);}
.wrap h1{font-size:28px;margin:0 0 var(--space-sm);}
.wrap h2{font-size:20px;margin:0 0 var(--space-sm);}
.rows{list-style:none;margin:0;padding:0;}
.rows li{display:flex;justify-content:space-between;gap:var(--space-sm);padding:10px 0;border-bottom:1px solid var(--color-border);}
.rows li:last-child{border-bottom:0;}
.pillx{font-size:var(--fs-min);font-weight:700;padding:3px 10px;border-radius:var(--radius-pill);border:1px solid var(--color-border-strong);white-space:nowrap;}
.pillx.ok{background:rgba(var(--rgb-accent),.1);}
.pillx.no{background:rgba(var(--rgb-primary),.18);}
.links{display:flex;gap:var(--space-xs);flex-wrap:wrap;}
pre{white-space:pre-wrap;background:var(--color-surface-2);padding:var(--space-sm);border-radius:var(--radius-input);font-size:14px;direction:ltr;text-align:left;}
.mini{font-size:var(--fs-caption);color:var(--color-body);}
</style>
</head>
<body>
<div class="wrap dash role-admin">
  <div class="top">
    <a class="btn btn-ghost btn-sm" href="admin.php">← <?= owBi('Back to admin', 'العودة للإدارة') ?></a>
    <?php owCornerControls('tgl()'); ?>
  </div>
  <h1><?= owBi('Settings', 'الإعدادات') ?></h1>

  <div class="card"><h2><?= owBi('What is switched on', 'ما الذي يعمل') ?></h2>
    <ul class="rows"><?php foreach ($checks as [$ok, $en, $ar]): ?><li><span><?= owBi($en, $ar) ?></span><?= $yes($ok) ?></li><?php endforeach; ?></ul>
  </div>

  <div class="card"><h2><?= owBi('Checks and tools', 'الفحوصات والأدوات') ?></h2>
    <div class="links">
      <a class="btn btn-secondary btn-sm" href="admin.php?error_log=1"><?= owBi('Error log', 'سجل الأخطاء') ?></a>
      <a class="btn btn-secondary btn-sm" href="admin.php?mail_check=1"><?= owBi('Email check', 'فحص البريد') ?></a>
      <a class="btn btn-secondary btn-sm" href="admin.php?photo_check=1"><?= owBi('Photo check', 'فحص الصور') ?></a>
      <a class="btn btn-secondary btn-sm" href="admin_articles.php"><?= owBi('Articles, events and spotlights', 'المقالات والملتقيات والأضواء') ?></a>
      <a class="btn btn-secondary btn-sm" href="admin_institutes.php"><?= owBi('Institutes and courses', 'المعاهد والدورات') ?></a>
    </div>
  </div>

  <?php /* Oweili's signature on every Certificate of Authenticity (owner, 9 Nov 2026), next to the seal. Kept private (sig_lib.php). */
  require_once __DIR__ . '/sig_lib.php'; $sigMsg = '';
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && in_array($_POST['do'] ?? '', ['oweili_sig', 'oweili_sig_remove'], true) && verifyCsrf($_POST['csrf_token'] ?? null)) {
      if ($_POST['do'] === 'oweili_sig_remove') { owSigRemove('oweili'); $sigMsg = 'removed'; owLog('signature_remove', 'admin', null, 'oweili', ['type' => 'admin', 'id' => null]); }
      else {
          $f = $_FILES['sig'] ?? null;
          $r = ($f && ($f['error'] ?? 1) === UPLOAD_ERR_OK && is_uploaded_file($f['tmp_name'])) ? owSigSave('oweili', (string) file_get_contents($f['tmp_name'])) : 'type';
          $sigMsg = $r === '' ? 'saved' : $r;
          if ($r === '') owLog('signature_set', 'admin', null, 'oweili', ['type' => 'admin', 'id' => null]);
      }
  } ?>
  <div class="card" id="oweili-signature"><h2><?= owBi('Oweili signature on certificates', 'توقيع أويلي على الشهادات') ?> <?= owSigExists('oweili') ? '<span class="pillx ok">' . owBi('Added', 'مضاف') . '</span>' : '<span class="pillx no">' . owBi('Not added', 'غير مضاف') . '</span>' ?></h2>
    <p class="mini"><?= owBi('Upload your signature once (a PNG with a clear or white background works best). It appears next to the Oweili seal on every certificate. It is kept private and is never shown at a public address.', 'ارفع توقيعك مرة واحدة (يفضّل PNG بخلفية شفافة أو بيضاء). يظهر بجوار ختم أويلي على كل شهادة، ويُحفظ بشكل خاص ولا يظهر في أي رابط عام.') ?></p>
    <?php if ($sigMsg === 'saved'): ?><p class="pillx ok"><?= owBi('Saved.', 'تم الحفظ.') ?></p><?php elseif ($sigMsg === 'removed'): ?><p class="pillx ok"><?= owBi('Removed.', 'تم الحذف.') ?></p><?php elseif ($sigMsg !== ''): [$se, $sa] = owSigError($sigMsg); ?><p class="pillx no"><?= owBi($se, $sa) ?></p><?php endif; ?>
    <?php if (owSigExists('oweili')): ?><p><img src="<?= e(owSigDataUri('oweili')) ?>" alt="Oweili signature · توقيع أويلي" style="max-width:280px;max-height:100px;background:#fff;border:1px solid var(--color-border);border-radius:8px;padding:8px"></p><?php endif; ?>
    <form method="post" action="admin.php?settings=1#oweili-signature" enctype="multipart/form-data" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
      <?= csrfField() ?><input type="hidden" name="do" value="oweili_sig">
      <input type="file" name="sig" accept="image/png,image/jpeg" required>
      <button class="btn btn-primary btn-sm"><?= owSigExists('oweili') ? owBi('Replace signature', 'استبدل التوقيع') : owBi('Upload signature', 'ارفع التوقيع') ?></button>
    </form>
    <?php if (owSigExists('oweili')): ?><form method="post" action="admin.php?settings=1#oweili-signature" style="margin-top:8px" onsubmit="return confirm('Remove? · حذف؟');"><?= csrfField() ?><input type="hidden" name="do" value="oweili_sig_remove"><button class="btn btn-danger btn-sm"><?= owBi('Remove', 'احذف') ?></button></form><?php endif; ?>
  </div>

  <div class="card"><h2><?= owBi('Database backups on the server', 'نسخ قاعدة البيانات على الخادم') ?></h2>
    <?php if (!$backups): ?><p class="mini"><?= owBi('No backups yet.', 'لا نسخ بعد.') ?></p><?php else: ?>
    <ul class="rows"><?php foreach (array_slice($backups, 0, 10) as $b): ?><li><span dir="ltr"><?= e(basename($b)) ?></span><span class="mini"><?= e(gmdate('Y-m-d H:i', (int) filemtime($b))) ?> UTC · <?= number_format((int) filesize($b) / 1024, 1) ?> KB</span></li><?php endforeach; ?></ul>
    <p class="mini"><?= owBi('To download one: hPanel → Files → File Manager → public_html → show hidden files → .backups.', 'للتنزيل: hPanel ← الملفات ← مدير الملفات ← public_html ← إظهار الملفات المخفية ← ‎.backups') ?></p><?php endif; ?>
  </div>

<?php require_once __DIR__ . '/order_lib.php'; $payMiss = owPaymentsMissing(); ?>
  <div class="card"><h2><?= owBi('Payments and escrow', 'الدفع والضمان') ?> <?= !$payMiss ? '<span class="pillx ok">' . owBi('On', 'يعمل') . '</span>' : '<span class="pillx no">' . owBi('Switched off', 'متوقف') . '</span>' ?></h2>
    <p><?= owBi('Orders for artworks, gifts, commissions and course or workshop tickets are built, with escrow: Oweili holds the money and releases it to the artist after the buyer confirms receipt, or ' . OW_ESCROW_DAYS . ' days after delivery with no complaint, minus Oweili’s commission (' . rtrim(rtrim(number_format(owCommissionRate(), 2), '0'), '.') . '%, before VAT). Everything stays switched off and hidden until these are ready:',
                 'طلبات الأعمال والهدايا والأعمال الخاصة وتذاكر الدورات والورش جاهزة، مع الضمان: يحتفظ أويلي بالمال ويحوّله للفنان بعد تأكيد المشتري الاستلام، أو بعد ' . OW_ESCROW_DAYS . ' أيام من التسليم دون شكوى، مخصوماً منه عمولة أويلي (' . rtrim(rtrim(number_format(owCommissionRate(), 2), '0'), '.') . '٪ قبل الضريبة). يبقى كل شيء متوقفاً ومخفياً حتى يجهز ما يلي:') ?></p>
    <ul class="rows">
      <li><span><?= owBi('Commercial registration number (in config.php: OWEILI_COMPANY_CR)', 'رقم السجل التجاري (في config.php: OWEILI_COMPANY_CR)') ?></span><?= $yes(owCompanyCr() !== '') ?></li>
      <li><span><?= owBi('A payment provider: Moyasar, Tap or HyperPay (merchant account, keys in config.php, its connection built and tested)', 'مزوّد دفع: Moyasar أو Tap أو HyperPay (حساب تاجر، ومفاتيحه في config.php، وبناء الربط واختباره)') ?></span><?= $yes(owGatewayName() !== '') ?></li>
      <li><span><?= owBi('A bank account for the money held and for paying artists', 'حساب بنكي للمبالغ المحفوظة ولدفع مستحقات الفنانين') ?></span><span class="mini"><?= owBi('your step', 'خطوتك') ?></span></li>
      <li><span><?= owBi('VAT: whether Oweili is VAT-registered (ZATCA e-invoicing if yes)', 'ضريبة القيمة المضافة: هل أويلي مسجّل (والفوترة الإلكترونية من زاتكا إن كان مسجلاً)') ?></span><span class="mini"><?= owBi('your decision', 'قرارك') ?></span></li>
      <li><span><?= owBi('Terms of Use and Privacy Policy updated for payments, refunds and escrow (reviewed by a lawyer)', 'تحديث شروط الاستخدام وسياسة الخصوصية للدفع والاسترداد والضمان (بمراجعة محامٍ)') ?></span><span class="mini"><?= owBi('your step', 'خطوتك') ?></span></li>
      <li><span><?= owBi('Your final "switch on"', 'موافقتك النهائية بالتشغيل') ?></span><?= $yes(owSetting('payments_enabled', '0') === '1') ?></li>
    </ul>
    <p class="mini"><a href="admin_orders.php"><?= owBi('Orders and payouts', 'الطلبات والمدفوعات') ?></a></p>
  </div>

<?php require_once __DIR__ . '/bashro_send.php'; $doorOn = owBashroReady();
  $doorTest = null;
  if ($doorOn && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['do'] ?? '') === 'bashro_test' && verifyCsrf($_POST['csrf_token'] ?? null)) $doorTest = owBashroTest();
  /* Auctions page switch (owner, 5 Nov 2026): one click to show or hide oweili.com/auctions */
  require_once __DIR__ . '/gallery_lib.php';
  $aucMsg = '';
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['do'] ?? '') === 'auctions_page' && verifyCsrf($_POST['csrf_token'] ?? null)) {
      $want = ($_POST['on'] ?? '') === '1' ? '1' : '0';
      if (owSetSetting('auctions_page_on', $want)) { owLog('setting_change', null, null, 'auctions_page_on=' . $want, ['type' => 'admin', 'id' => null]); $aucMsg = $want; }
  }
  $aucOn = owAuctionsPageOn();
  $doorLog = []; try { if (owHasTable('activity_log')) $doorLog = getDB()->query("SELECT details, created_at FROM activity_log WHERE action = 'bashro_send' ORDER BY id DESC LIMIT 8")->fetchAll(); } catch (Throwable $e) {} ?>
  <div class="card" id="auctions-switch"><h2><?= owBi('Auctions page', 'صفحة المزاد') ?> <?= $aucOn ? '<span class="pillx ok">' . owBi('Shown', 'ظاهرة') . '</span>' : '<span class="pillx no">' . owBi('Hidden', 'مخفية') . '</span>' ?></h2>
    <p><?= $aucOn ? owBi('Visitors can open oweili.com/auctions, and it is in the menu (Collect → Auctions) and the sitemap.', 'يستطيع الزوار فتح oweili.com/auctions، وهي في القائمة (اقتنِ ← المزاد) وفي خريطة الموقع.')
                  : owBi('Hidden: not in the menu or the sitemap, and visitors who open oweili.com/auctions see "page not found". You can still preview it. The waiting list and its sign-ups are kept, and the unsubscribe links in its emails still work.', 'مخفية: ليست في القائمة ولا في خريطة الموقع، ومن يفتح oweili.com/auctions يرى «الصفحة غير موجودة». تستطيع معاينتها. قائمة الانتظار وتسجيلاتها محفوظة، وروابط إلغاء الاشتراك في رسائلها تعمل.') ?></p>
    <?php if ($aucMsg !== ''): ?><div class="alert alert-ok" role="status"><?= $aucMsg === '1' ? owBi('The Auctions page is shown again.', 'صفحة المزاد ظاهرة الآن.') : owBi('The Auctions page is hidden.', 'صفحة المزاد مخفية الآن.') ?></div><?php endif; ?>
    <form method="post" action="admin.php?settings=1#auctions-switch" style="display:flex;gap:var(--space-xs);flex-wrap:wrap"><?= csrfField() ?><input type="hidden" name="do" value="auctions_page"><input type="hidden" name="on" value="<?= $aucOn ? '0' : '1' ?>">
      <button class="btn <?= $aucOn ? 'btn-secondary' : 'btn-primary' ?> btn-sm" type="submit"><?= $aucOn ? owBi('Hide the Auctions page', 'إخفاء صفحة المزاد') : owBi('Show the Auctions page', 'إظهار صفحة المزاد') ?></button>
      <a class="btn btn-ghost btn-sm" href="auctions.php" target="_blank" rel="noopener"><?= owBi('Preview', 'معاينة') ?></a></form>
  </div>
<?php /* Hidden for now, to bring back later (owner, 6 Nov 2026): one switch each (gallery_lib.php OW_HIDDEN_PARTS) */
  $hidMsg = [];
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['do'] ?? '') === 'part_switch' && verifyCsrf($_POST['csrf_token'] ?? null)) {
      $part = (string) ($_POST['part'] ?? '');
      if (isset(OW_HIDDEN_PARTS[$part])) {
          $want = ($_POST['on'] ?? '') === '1' ? '1' : '0';
          if (owSetSetting(OW_HIDDEN_PARTS[$part][0], $want)) { owLog('setting_change', null, null, OW_HIDDEN_PARTS[$part][0] . '=' . $want, ['type' => 'admin', 'id' => null]); $hidMsg = [$part, $want]; }
      }
  } ?>
  <div class="card" id="hidden-parts"><h2><?= owBi('Hidden for now, to bring back later', 'مخفي الآن، يعود لاحقاً') ?></h2>
    <p><?= owBi('While hidden: not in the menu, the footer, the sitemap or any link, and visitors who open the address see "page not found". You can still preview the pages. Nothing is deleted: the pages, the code, the tables and every saved request stay.', 'أثناء الإخفاء: لا تظهر في القائمة ولا في أسفل الموقع ولا في خريطة الموقع ولا في أي رابط، ومن يفتح العنوان يرى «الصفحة غير موجودة». تستطيع معاينة الصفحات. لا يُحذف شيء: الصفحات والبرمجة والجداول وكل الطلبات المحفوظة باقية.') ?></p>
    <?php if ($hidMsg): ?><div class="alert alert-ok" role="status"><?= $hidMsg[1] === '1' ? owBi(OW_HIDDEN_PARTS[$hidMsg[0]][1] . ' is shown again.', OW_HIDDEN_PARTS[$hidMsg[0]][2] . ': ظاهرة الآن.') : owBi(OW_HIDDEN_PARTS[$hidMsg[0]][1] . ' is hidden.', OW_HIDDEN_PARTS[$hidMsg[0]][2] . ': مخفية الآن.') ?></div><?php endif; ?>
    <ul class="rows">
    <?php foreach (OW_HIDDEN_PARTS as $pk => [$pKey, $pEn, $pAr, $pUrl]): $pOn = owPartOn($pk); ?>
      <li><span><?= owBi($pEn, $pAr) ?> <?= $pOn ? '<span class="pillx ok">' . owBi('Shown', 'ظاهرة') . '</span>' : '<span class="pillx no">' . owBi('Hidden', 'مخفية') . '</span>' ?></span>
        <form method="post" action="admin.php?settings=1#hidden-parts" style="display:flex;gap:var(--space-xs);flex-wrap:wrap"><?= csrfField() ?><input type="hidden" name="do" value="part_switch"><input type="hidden" name="part" value="<?= e($pk) ?>"><input type="hidden" name="on" value="<?= $pOn ? '0' : '1' ?>">
          <button class="btn <?= $pOn ? 'btn-secondary' : 'btn-primary' ?> btn-sm" type="submit"><?= $pOn ? owBi('Hide', 'إخفاء') : owBi('Show', 'إظهار') ?></button>
          <a class="btn btn-ghost btn-sm" href="<?= e($pUrl) ?>" target="_blank" rel="noopener"><?= owBi('Preview', 'معاينة') ?></a></form></li>
    <?php endforeach; ?>
    </ul>
  </div>
  <div class="card"><h2><?= owBi('Sending to Bashro (website door)', 'الإرسال إلى بشرو (بوابة الموقع)') ?> <?= $doorOn ? '<span class="pillx ok">' . owBi('On', 'يعمل') . '</span>' : '<span class="pillx no">' . owBi('Off', 'متوقف') . '</span>' ?></h2>
    <p><?= owBi('"I’m Interested", gift and commission requests go to Bashro Sales (project 3) after the visitor’s page is delivered. Never photos, passwords, card, ID or bank numbers.', 'تذهب طلبات «أنا مهتم» والهدايا والأعمال الخاصة إلى مبيعات بشرو (المشروع 3) بعد عرض صفحة الزائر. لا صور ولا كلمات مرور ولا أرقام بطاقات أو هويات أو حسابات بنكية أبداً.') ?></p>
    <ul class="rows">
      <li><span><?= owBi('Requests to Sales', 'الطلبات إلى المبيعات') ?></span><?= $yes(owBashroKindOn('quote')) ?></li>
      <li><span><?= owBi('New artist and buyer sign-ups (waits until Bashro accepts them)', 'تسجيلات الفنانين والمشترين الجدد (بانتظار قبول بشرو لها)') ?></span><?= $yes(owBashroKindOn('signup')) ?></li>
      <li><span><?= owBi('Approvals waiting (waits until Bashro accepts them)', 'ما ينتظر الموافقة (بانتظار قبول بشرو له)') ?></span><?= $yes(owBashroKindOn('approval')) ?></li>
    </ul>
    <?php if (!$doorOn): ?><p class="mini"><?= owBi('Switched on by adding two lines to config.php on the server (hPanel → File Manager), with the key made in Bashro:', 'يعمل بإضافة سطرين إلى config.php على الخادم (hPanel ← مدير الملفات)، مع المفتاح الذي يُصنع في بشرو:') ?></p>
      <pre>define('BASHRO_DOOR_SITE', '3');
define('BASHRO_DOOR_KEY', 'the 64-character key from Bashro');</pre><?php endif; ?>
    <?php if ($doorTest !== null): ?><div class="<?= $doorTest === 202 ? 'alert alert-ok' : 'alert alert-error' ?>" role="status"><?= $doorTest === 202 ? owBi('Bashro accepted the test (202). Look for "Oweili test (please ignore)" in Bashro Sales.', 'قبل بشرو رسالة الاختبار (202). ابحث عن «Oweili test (please ignore)» في مبيعات بشرو.')
        : owBi('Bashro did not accept the test. Answer: ' . ($doorTest ?: 'no answer') . '. 401 = the key does not match, 403 = the website connection is off in Bashro.', 'لم يقبل بشرو رسالة الاختبار. الرد: ' . ($doorTest ?: 'لا رد') . '. 401 = المفتاح غير مطابق، 403 = ربط الموقع متوقف في بشرو.') ?></div><?php endif; ?>
    <?php if ($doorOn): ?><form method="post" action="admin.php?settings=1" style="margin:var(--space-xs) 0"><?= csrfField() ?><input type="hidden" name="do" value="bashro_test">
      <button class="btn btn-secondary btn-sm" type="submit"><?= owBi('Send a test to Bashro', 'أرسل اختباراً إلى بشرو') ?></button></form><?php endif; ?>
    <?php if ($doorLog): ?><p class="mini"><?= owBi('Last answers from Bashro (202 = accepted):', 'آخر ردود بشرو (202 = مقبول):') ?></p>
      <ul class="rows"><?php foreach ($doorLog as $dl): ?><li><span dir="ltr"><?= e((string) $dl['details']) ?></span><span class="mini"><?= e((string) $dl['created_at']) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
  </div>

  <div class="card"><h2><?= owBi('Bashro connection', 'الربط مع بشرو') ?> <?= $bashroOn ? '<span class="pillx ok">' . owBi('On', 'يعمل') . '</span>' : '<span class="pillx no">' . owBi('Off', 'متوقف') . '</span>' ?></h2>
    <p><?= owBi('A private, read-only connection that lets Bashro read people, artworks, requests, favorites and the activity log. Nothing can be changed through it.', 'ربط خاص للقراءة فقط يسمح لبشرو بقراءة الأشخاص والأعمال والطلبات والمفضلة وسجل النشاط. لا يمكن تغيير أي شيء من خلاله.') ?></p>
    <?php if ($bashroOn): ?>
      <p class="mini"><?= owBi('Address: ', 'العنوان: ') ?><span dir="ltr">https://<?= e((string) ($_SERVER['HTTP_HOST'] ?? 'oweili.com')) ?>/bashro_api.php?resource=summary</span> · <?= owBi('Allowed addresses: ', 'العناوين المسموحة: ') ?><?= e($bashroIps ?: 'any') ?></p>
    <?php else: ?>
      <p class="mini"><?= owBi('It stays off until these two lines are added to config.php on the server (hPanel → File Manager). Use a long random key and give it only to Bashro:', 'يبقى متوقفاً حتى يُضاف هذان السطران إلى config.php على الخادم (hPanel ← مدير الملفات). استخدم مفتاحاً طويلاً عشوائياً وأعطه لبشرو فقط:') ?></p>
      <pre>define('BASHRO_API_ENABLED', true);
define('BASHRO_API_KEY', 'a long random key, 32 characters or more');</pre>
    <?php endif; ?>
  </div>
</div>
<script>
function tgl(){ var ar = document.documentElement.getAttribute('dir') !== 'rtl'; document.documentElement.setAttribute('dir', ar ? 'rtl' : 'ltr'); document.documentElement.lang = ar ? 'ar' : 'en';
  document.getElementById('lb').textContent = ar ? 'English' : 'العربية'; try { localStorage.setItem('lang', ar ? 'ar' : 'en'); } catch (e) {} }
try { if ((localStorage.getItem('lang') === 'ar') !== (document.documentElement.getAttribute('dir') === 'rtl')) tgl(); } catch (e) {}
</script>
</body>
</html>
