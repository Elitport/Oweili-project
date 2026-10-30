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
<html lang="en" dir="ltr">
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
    <span><button class="btn btn-ghost btn-sm" onclick="tgl()" id="lb">العربية</button> <?php owThemeButton('btn btn-ghost btn-sm'); ?></span>
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

  <div class="card"><h2><?= owBi('Database backups on the server', 'نسخ قاعدة البيانات على الخادم') ?></h2>
    <?php if (!$backups): ?><p class="mini"><?= owBi('No backups yet.', 'لا نسخ بعد.') ?></p><?php else: ?>
    <ul class="rows"><?php foreach (array_slice($backups, 0, 10) as $b): ?><li><span dir="ltr"><?= e(basename($b)) ?></span><span class="mini"><?= e(gmdate('Y-m-d H:i', (int) filemtime($b))) ?> UTC · <?= number_format((int) filesize($b) / 1024, 1) ?> KB</span></li><?php endforeach; ?></ul>
    <p class="mini"><?= owBi('To download one: hPanel → Files → File Manager → public_html → show hidden files → .backups.', 'للتنزيل: hPanel ← الملفات ← مدير الملفات ← public_html ← إظهار الملفات المخفية ← ‎.backups') ?></p><?php endif; ?>
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
try { if (localStorage.getItem('lang') === 'ar') tgl(); } catch (e) {}
</script>
</body>
</html>
