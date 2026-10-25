<?php
/* Shared brand navbar — include with: <?php include __DIR__ . "/nav.php"; ?>
   Assumes config.php was already required by the host page (session started). */
if (!function_exists('currentUser')) { @require_once __DIR__ . '/config.php'; }
$navUser  = function_exists('currentUser') ? currentUser() : null;
$navIsAdmin = function_exists('isAdmin') && isAdmin();
$navName  = $navUser ? trim((string)($navUser['full_name_en'] ?: ($navUser['full_name_ar'] ?: $navUser['email']))) : '';
if ($navName === '') { $navName = 'Account'; }
$navFirst = $navUser ? (explode(' ', $navName)[0]) : '';
$navId    = $navUser ? (int) $navUser['id'] : 0;
?>
<script>
/* Apply the saved language direction as early as possible to avoid a flash
   of the wrong direction before the page's own script runs. */
(function(){try{var l=localStorage.getItem('lang');if(l==='ar'){document.documentElement.setAttribute('dir','rtl');document.documentElement.setAttribute('lang','ar');}}catch(e){}})();
</script>
<link rel="stylesheet" href="/assets/css/oweili.css">
<style>
/* ── Mobile menu (phones and tablets, where the desktop links are hidden) ── */
.mnav-btn{display:none;align-items:center;justify-content:center;width:40px;height:40px;margin-inline-start:6px;padding:0;border:1px solid var(--color-border,rgba(0,0,0,0.07));border-radius:12px;background:rgba(255,255,255,0.7);color:var(--color-dark,#111111);cursor:pointer;flex-shrink:0;transition:background 0.2s;}
.mnav-btn:hover{background:rgba(0,0,0,0.06);}
.mnav-btn:active{background:rgba(0,0,0,0.1);}
.mnav-btn:focus-visible,.mnav-link:focus-visible{outline:2px solid var(--color-accent,#0066ff);outline-offset:2px;}
.mnav-btn svg{width:20px;height:20px;stroke:currentColor;stroke-width:2;stroke-linecap:round;fill:none;}
.mnav-btn .mnav-x{display:none;}
.mnav-btn[aria-expanded="true"] .mnav-bars{display:none;}
.mnav-btn[aria-expanded="true"] .mnav-x{display:block;}
.mnav-panel{position:absolute;top:100%;left:0;right:0;max-height:calc(100vh - 56px);overflow-y:auto;padding:8px 16px 16px;background:rgba(255,255,255,0.97);-webkit-backdrop-filter:blur(20px);backdrop-filter:blur(20px);border-bottom:1px solid var(--color-border,rgba(0,0,0,0.07));box-shadow:0 16px 40px rgba(0,0,0,0.08);opacity:0;visibility:hidden;transform:translateY(-8px);transition:opacity 0.25s ease,transform 0.25s ease,visibility 0.25s;}
.mnav-panel.open{opacity:1;visibility:visible;transform:translateY(0);}
.mnav-link{display:flex;align-items:center;min-height:48px;padding:0 12px;border-radius:12px;font-size:15px;font-weight:600;color:var(--color-dark,#111111);text-decoration:none;transition:background 0.15s,color 0.15s;}
.mnav-link:hover{background:rgba(0,102,255,0.07);color:var(--color-accent,#0066ff);}
.mnav-link:active{background:rgba(0,102,255,0.12);}
.mnav-sub{padding-inline-start:28px;font-weight:500;color:var(--color-body,rgba(0,0,0,0.65));}
[dir="rtl"] .mnav-panel{text-align:right;}
.mnav-acct{display:none;margin-top:8px;padding-top:8px;border-top:1px solid var(--color-border,rgba(0,0,0,0.07));}
@media (max-width:1024px){.mnav-btn{display:inline-flex;}.topnav .nav-right{margin-inline-start:auto;}}
/* Small phones: the account buttons move from the top bar into the menu so the bar never overflows */
@media (max-width:560px){.topnav .nav-right .nav-btn{display:none;}.mnav-acct{display:block;}}
@media (min-width:1025px){.mnav-panel{display:none;}}
@media (prefers-reduced-motion:reduce){.mnav-panel{transition:none;}}
</style>
<nav class="topnav" id="topnav">
  <a class="nav-logo" href="index.html">
    <span style="display:inline-block;background:linear-gradient(135deg,#ff0055,#0066ff,#aa00ff);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent;font-weight:800;font-size:15px;letter-spacing:0.14em;">OWEILI</span>
    <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><defs><linearGradient id="navLogoGrad" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#ff0055"/><stop offset="50%" stop-color="#0066ff"/><stop offset="100%" stop-color="#aa00ff"/></linearGradient></defs><path fill="url(#navLogoGrad)" d="M12 0C12 6 18 12 24 12C18 12 12 18 12 24C12 18 6 12 0 12C6 12 12 6 12 0Z"/></svg>
  </a>
  <div class="nav-center" id="nav-center">
    <div class="nav-item">
      <a href="studio.php" id="nav-studio-link"><span id="nav-studio-label">The Studio</span><svg class="chevron" viewBox="0 0 10 6"><polyline points="1,1 5,5 9,1"/></svg></a>
      <div class="dropdown" id="dd-studio">
        <a href="studio.php#watercolor"><svg class="d-icon" viewBox="0 0 24 24"><path d="M12 2C8 2 4 6 4 10c0 5.25 8 12 8 12s8-6.75 8-12c0-4-4-8-8-8z"/></svg><span id="dd-s1">Watercolor Workshop</span></a>
        <a href="studio.php#oil"><svg class="d-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg><span id="dd-s2">Oil Painting Studio</span></a>
        <a href="studio.php#digital"><svg class="d-icon" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg><span id="dd-s3">Digital Art Lab</span></a>
        <a href="studio.php#charcoal"><svg class="d-icon" viewBox="0 0 24 24"><path d="M3 17l4-8 4 4 4-6 4 10"/></svg><span id="dd-s4">Charcoal & Ink</span></a>
      </div>
    </div>
    <div class="nav-item">
      <a href="community.php" id="nav-community-link"><span id="nav-community-label">Community</span><svg class="chevron" viewBox="0 0 10 6"><polyline points="1,1 5,5 9,1"/></svg></a>
      <div class="dropdown" id="dd-community">
        <a href="community.php#meet"><svg class="d-icon" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg><span id="dd-c2">Meet Fellow Artists</span></a>
        <a href="community.php#share"><svg class="d-icon" viewBox="0 0 24 24"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg><span id="dd-c3">Share Your Work</span></a>
        <a href="community.php#learn"><svg class="d-icon" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg><span id="dd-c4">Learn Together</span></a>
      </div>
    </div>
    <div class="nav-item"><a href="marketplace.php" id="nav-marketplace-link"><span id="nav-marketplace-label">Marketplace</span></a></div>
    <div class="nav-item"><a href="explore.php" id="nav-explore-link"><span id="nav-explore-label">Explore Art Styles</span></a></div>
    <div class="nav-item">
      <a href="events.php"><span class="nav-i18n" data-en="Discover" data-ar="اكتشف">Discover</span><svg class="chevron" viewBox="0 0 10 6"><polyline points="1,1 5,5 9,1"/></svg></a>
      <div class="dropdown" id="dd-discover">
        <a href="events.php"><svg class="d-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg><span class="nav-i18n" data-en="Art Events" data-ar="أحداث فنية">Art Events</span></a>
        <a href="training.php"><svg class="d-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1 2.7 3 6 3s6-2 6-3v-5"/></svg><span class="nav-i18n" data-en="Training Institutes" data-ar="معاهد تدريب">Training Institutes</span></a>
      </div>
    </div>
    <div class="nav-item">
      <a href="support.php" id="nav-support-link"><span id="nav-support-label">Support</span><svg class="chevron" viewBox="0 0 10 6"><polyline points="1,1 5,5 9,1"/></svg></a>
      <div class="dropdown" id="dd-support">
        <a href="mailto:contact@oweili.com"><svg class="d-icon" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg><span id="dd-sp1">Contact Us</span></a>
        <a href="support.php#how"><svg class="d-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><span id="dd-sp2">How It Works</span></a>
        <a href="support.php#terms"><svg class="d-icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><span id="dd-sp3">Terms of Use</span></a>
      </div>
    </div>
  </div>
  <div class="nav-right">
    <?php if ($navUser): ?>
      <?php if ($navIsAdmin): ?>
      <a class="nav-btn" href="admin.php" id="n-admin"><span class="nav-i18n" data-en="Admin" data-ar="الإدارة">Admin</span></a>
      <?php endif; ?>
      <a class="nav-btn" href="<?= e(dashboardUrl($navUser['role'] ?? '')) ?>" id="n-acct"><span><?= htmlspecialchars($navFirst, ENT_QUOTES) ?></span></a>
      <a class="nav-btn" href="logout.php" id="n-logout"><span class="nav-i18n" data-en="Sign Out" data-ar="تسجيل الخروج">Sign Out</span></a>
    <?php else: ?>
      <a class="nav-btn" href="login.php" id="n-login"><span id="n-login-t">Sign In</span></a>
      <a class="nav-btn" href="register.php" id="n-reg"><span id="n-reg-t">Join Free</span></a>
    <?php endif; ?>
    <button class="lang-btn" onclick="tgl()" id="lb">العربية</button>
  </div>
  <button type="button" class="mnav-btn" id="mnav-btn" aria-expanded="false" aria-controls="mnav-panel" aria-label="Open menu" data-label-en="Open menu" data-label-ar="فتح القائمة" data-close-en="Close menu" data-close-ar="إغلاق القائمة">
    <svg class="mnav-bars" viewBox="0 0 24 24" aria-hidden="true"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></svg>
    <svg class="mnav-x" viewBox="0 0 24 24" aria-hidden="true"><line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/></svg>
  </button>
  <div class="mnav-panel" id="mnav-panel">
    <a class="mnav-link" href="studio.php"><span class="nav-i18n" data-en="The Studio" data-ar="الاستوديو">The Studio</span></a>
    <a class="mnav-link" href="community.php"><span class="nav-i18n" data-en="Community" data-ar="المجتمع">Community</span></a>
    <a class="mnav-link" href="marketplace.php" id="mnav-marketplace"><span class="nav-i18n" data-en="Marketplace" data-ar="السوق">Marketplace</span></a>
    <a class="mnav-link" href="explore.php"><span class="nav-i18n" data-en="Explore Art Styles" data-ar="استكشف أساليب الرسم">Explore Art Styles</span></a>
    <a class="mnav-link" href="events.php"><span class="nav-i18n" data-en="Discover" data-ar="اكتشف">Discover</span></a>
    <a class="mnav-link mnav-sub" href="events.php"><span class="nav-i18n" data-en="Art Events" data-ar="أحداث فنية">Art Events</span></a>
    <a class="mnav-link mnav-sub" href="training.php"><span class="nav-i18n" data-en="Training Institutes" data-ar="معاهد تدريب">Training Institutes</span></a>
    <a class="mnav-link" href="support.php"><span class="nav-i18n" data-en="Support" data-ar="الدعم">Support</span></a>
    <div class="mnav-acct">
    <?php if ($navUser): ?>
      <?php if ($navIsAdmin): ?>
      <a class="mnav-link" href="admin.php"><span class="nav-i18n" data-en="Admin" data-ar="الإدارة">Admin</span></a>
      <?php endif; ?>
      <a class="mnav-link" href="<?= e(dashboardUrl($navUser['role'] ?? '')) ?>"><span class="nav-i18n" data-en="My Account" data-ar="حسابي">My Account</span></a>
      <a class="mnav-link" href="logout.php"><span class="nav-i18n" data-en="Sign Out" data-ar="تسجيل الخروج">Sign Out</span></a>
    <?php else: ?>
      <a class="mnav-link" href="login.php"><span class="nav-i18n" data-en="Sign In" data-ar="تسجيل الدخول">Sign In</span></a>
      <a class="mnav-link" href="register.php"><span class="nav-i18n" data-en="Join Free" data-ar="انضم مجاناً">Join Free</span></a>
    <?php endif; ?>
    </div>
  </div>
</nav>
<script>
/* =====================================================================
 *  Language persistence — shared across every page that includes nav.php.
 *  Saves the EN/AR choice in localStorage and re-applies it on each load,
 *  so switching to Arabic sticks when navigating between pages.
 * ===================================================================== */
(function(){
  var KEY = 'lang';

  // Capture the saved preference NOW, before the page's own apply('en') runs
  // (it runs later, at the end of the body) and before the observer below can
  // overwrite storage with an intermediate value.
  var want = null;
  try { want = localStorage.getItem(KEY); } catch (e) {}

  function curLang(){
    return (document.documentElement.getAttribute('dir') === 'rtl' ||
            document.documentElement.lang === 'ar') ? 'ar' : 'en';
  }
  function save(l){ try { localStorage.setItem(KEY, l); } catch (e) {} }

  function syncNavI18n(){
    var ar = curLang() === 'ar';
    document.querySelectorAll('.nav-i18n').forEach(function(el){
      el.textContent = ar ? (el.dataset.ar || el.dataset.en) : el.dataset.en;
    });
  }

  // Persist whatever language the page settles on (toggle, or our re-apply).
  new MutationObserver(function(){ syncNavI18n(); save(curLang()); })
    .observe(document.documentElement, { attributes: true, attributeFilter: ['dir', 'lang'] });

  // Re-apply the saved language after the page's inline script has run.
  function applySaved(){
    if (want && want !== curLang() && typeof window.tgl === 'function') {
      // Use the page's own toggle so its internal language state stays in sync.
      window.tgl();
    } else if (want && want !== curLang()) {
      // Fallback for pages without a tgl(): set direction directly.
      var ar = want === 'ar';
      document.documentElement.setAttribute('dir', ar ? 'rtl' : 'ltr');
      document.documentElement.setAttribute('lang', want);
    }
    syncNavI18n();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applySaved);
  } else {
    applySaved();
  }
})();
/* ── Mobile menu: open/close, close on link tap, outside tap or Escape ── */
(function(){
  var btn = document.getElementById('mnav-btn');
  var panel = document.getElementById('mnav-panel');
  if (!btn || !panel) return;
  function isAr(){ return document.documentElement.getAttribute('dir') === 'rtl'; }
  function setLabel(open){
    var k = (open ? 'close' : 'label') + (isAr() ? 'Ar' : 'En');
    btn.setAttribute('aria-label', btn.dataset[k]);
  }
  function setOpen(open){
    panel.classList.toggle('open', open);
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    setLabel(open);
  }
  btn.addEventListener('click', function(ev){
    ev.stopPropagation();
    setOpen(!panel.classList.contains('open'));
  });
  panel.addEventListener('click', function(ev){
    if (ev.target.closest('a')) setOpen(false);
  });
  document.addEventListener('click', function(ev){
    if (panel.classList.contains('open') && !panel.contains(ev.target)) setOpen(false);
  });
  document.addEventListener('keydown', function(ev){
    if (ev.key === 'Escape' && panel.classList.contains('open')) { setOpen(false); btn.focus(); }
  });
  // Keep the button label in the current language when the page switches EN/AR.
  new MutationObserver(function(){ setLabel(panel.classList.contains('open')); })
    .observe(document.documentElement, { attributes: true, attributeFilter: ['dir'] });
  setLabel(false);
})();
</script>
