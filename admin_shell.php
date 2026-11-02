<?php
/* =====================================================================
 *  admin_shell.php — the shared frame of the newer admin tools
 *  (admin_gallery.php, admin_requests.php, admin_orders.php):
 *    owIsAdmin()        the admin: signed in on admin.php with the admin
 *                       password, or an account with the admin role
 *    owAdminGate()      everyone else gets "Admin only" (403) and stops
 *    owAdminTop() / owAdminBottom()   page head, the staff bar (back link,
 *                       language and dark mode) and the closing script
 *  Tables: none (users through currentUser)
 * ===================================================================== */
require_once __DIR__ . '/platform_lib.php';

function owIsAdmin(): bool {
    $hash = defined('ADMIN_HASH') ? (string) ADMIN_HASH : '';
    if ($hash !== '' && !empty($_SESSION['is_admin']) && hash_equals(hash('sha256', $hash), (string) ($_SESSION['admin_sig'] ?? ''))) return true;
    if (function_exists('isLoggedIn') && isLoggedIn() && ($u = currentUser()) && ($u['role'] ?? '') === 'admin' && empty($u['is_banned'])) return true;
    return false;
}
function owAdminActor(): array {
    if (!empty($_SESSION['user_id']) && ($u = currentUser()) && ($u['role'] ?? '') === 'admin') return ['type' => 'admin', 'id' => (int) $u['id']];
    return ['type' => 'admin', 'id' => null];
}
function owAdminGate(string $back = 'admin.php'): void {
    if (owIsAdmin()) return;
    http_response_code(403); ?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><?php include __DIR__ . '/theme_head.php'; ?>
<title>Admin only · Oweili</title><meta name="robots" content="noindex"><link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<script>try{if(localStorage.getItem('lang')==='ar'){document.documentElement.dir='rtl';document.documentElement.lang='ar';}}catch(e){}</script></head>
<body><div style="max-width:480px;margin:15vh auto;padding:24px" class="card"><h1><?= owBi('Admin only', 'للإدارة فقط') ?></h1>
<p><?= owBi('Please sign in on the admin page first.', 'يرجى تسجيل الدخول في صفحة الإدارة أولاً.') ?></p><p><a class="btn btn-primary" href="<?= htmlspecialchars($back, ENT_QUOTES, 'UTF-8') ?>"><?= owBi('Admin sign-in', 'دخول الإدارة') ?></a></p></div></body></html>
<?php exit;
}
function owAdminTop(string $titleEn, string $titleAr, string $css = '', string $extraLink = ''): void { ?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title><?= htmlspecialchars($titleEn . ' · ' . $titleAr . ' · Oweili Admin', ENT_QUOTES, 'UTF-8') ?></title>
<script>try{if(localStorage.getItem('lang')==='ar'){document.documentElement.dir='rtl';document.documentElement.lang='ar';}}catch(e){}</script>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<style>
.wrap{max-width:1100px;margin:0 auto;padding:var(--space-lg) var(--space-sm) var(--space-2xl);}
.top{display:flex;justify-content:space-between;align-items:center;gap:var(--space-sm);flex-wrap:wrap;margin-bottom:var(--space-md);}
.wrap h1{font-size:28px;margin:0 0 4px;}
.wrap h2{font-size:20px;margin:0 0 var(--space-sm);}
.wrap .card{margin-bottom:var(--space-md);}
.mini{font-size:var(--fs-caption);color:var(--color-body);}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:0 var(--space-sm);}
.g3{display:grid;grid-template-columns:repeat(3,1fr);gap:0 var(--space-sm);}
.chk{display:flex;gap:6px;align-items:center;margin:0 0 var(--space-sm);font-size:15px;}
.pillx{display:inline-block;font-size:var(--fs-min);font-weight:700;padding:2px 10px;border-radius:var(--radius-pill);border:1px solid var(--color-border-strong);white-space:nowrap;}
.pillx.on{background:rgba(var(--rgb-accent),.1);border-color:rgba(var(--rgb-accent),.35);}
.pillx.warn{background:rgba(var(--rgb-primary),.18);border-color:rgba(var(--rgb-primary),.45);}
.acts{display:flex;gap:var(--space-xs);flex-wrap:wrap;align-items:center;}
.acts form{margin:0;}
details > summary{cursor:pointer;font-weight:700;margin:var(--space-xs) 0;}
.kv{display:grid;grid-template-columns:max-content 1fr;gap:4px var(--space-sm);font-size:15px;margin:0 0 var(--space-sm);}
.kv dt{font-weight:700;color:var(--color-dark);}
.kv dd{margin:0;overflow-wrap:anywhere;}
@media(max-width:700px){.g2,.g3{grid-template-columns:1fr;}.kv{grid-template-columns:1fr;}.kv dd{margin-bottom:6px;}}
<?= $css ?>
</style>
</head>
<body>
<div class="wrap dash role-admin">
  <div class="top">
    <a class="btn btn-ghost btn-sm" href="admin.php">← <?= owBi('Back to admin', 'العودة للإدارة') ?></a>
    <span><?= $extraLink ?> <?php owCornerControls('tgl()'); ?></span>
  </div>
<?php }
function owAdminBottom(): void { ?>
</div>
<script>
function tgl(){ var ar = document.documentElement.getAttribute('dir') !== 'rtl'; document.documentElement.setAttribute('dir', ar ? 'rtl' : 'ltr'); document.documentElement.lang = ar ? 'ar' : 'en';
  var b = document.getElementById('lb'); if (b) b.textContent = ar ? 'English' : 'العربية'; try { localStorage.setItem('lang', ar ? 'ar' : 'en'); } catch (e) {} }
(function(){ var b = document.getElementById('lb'); if (b && document.documentElement.getAttribute('dir') === 'rtl') b.textContent = 'English'; })();
</script>
</body>
</html>
<?php }
