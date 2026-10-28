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

/* Which menu item is the current page (pink underline) */
$navPage  = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$navType  = (string) ($_GET['type'] ?? '');
if ($navPage === 'article.php') { $navType = ''; }
$navSection = [
    'community.php' => 'community', 'marketplace.php' => 'marketplace',
    'explore.php' => 'explore', 'support.php' => 'support', 'articles_list.php' => 'discover',
    'article.php' => 'discover', 'training.php' => 'discover', 'events.php' => 'discover',
][$navPage] ?? '';
if (!function_exists('navAct')) {
function navAct(string $section, string $page = '', string $type = ''): string {
    global $navSection, $navPage, $navType;
    if ($page !== '') {
        $hit = $navPage === $page && ($type === '' || $navType === $type || ($navType === '' && $type === 'event' && $page === 'articles_list.php'));
        return $hit ? ' class="active" aria-current="page"' : '';
    }
    return $navSection === $section ? ' class="active"' : '';
}
}
/* The Oweili mark: a circle made of four quarters, in brand pink */
if (!function_exists('owLogoMark')) {
    function owLogoMark(int $size = 24): void {
        echo '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><g style="fill:var(--color-primary)">'
           . '<path d="M11 1A10 10 0 0 0 1 11h10z"/><path d="M13 1v10h10A10 10 0 0 0 13 1z"/>'
           . '<path d="M1 13a10 10 0 0 0 10 10V13z"/><path d="M13 13v10a10 10 0 0 0 10-10z"/></g></svg>';
    }
}
?>
<script>
/* Apply the saved language direction as early as possible to avoid a flash
   of the wrong direction before the page's own script runs. */
(function(){try{var l=localStorage.getItem('lang');if(l==='ar'){document.documentElement.setAttribute('dir','rtl');document.documentElement.setAttribute('lang','ar');}}catch(e){}})();
</script>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="/assets/css/oweili.css?v=<?= @filemtime(__DIR__ . '/assets/css/oweili.css') ?>">
<link rel="stylesheet" href="/assets/css/nav.css?v=<?= @filemtime(__DIR__ . '/assets/css/nav.css') ?>">
<nav class="topnav" id="topnav">
  <a class="nav-logo" href="index.php" aria-label="Oweili · أويلي"><?php owLogoMark(); ?><span class="nav-word">OWEILI</span></a>
  <div class="nav-center" id="nav-center">
    <div class="nav-item"><a href="marketplace.php" id="nav-marketplace-link"<?= navAct('marketplace') ?>><span id="nav-marketplace-label">Gallery</span></a></div>
    <div class="nav-item"><a href="explore.php" id="nav-explore-link"<?= navAct('explore') ?>><span id="nav-explore-label">Explore Art Styles</span></a></div>
    <div class="nav-item">
      <a href="articles_list.php?type=event"<?= navAct('discover') ?>><span class="nav-i18n" data-en="Discover" data-ar="اكتشف">Discover</span><svg class="chevron" viewBox="0 0 10 6"><polyline points="1,1 5,5 9,1"/></svg></a>
      <div class="dropdown" id="dd-discover">
        <a href="articles_list.php?type=event"<?= navAct('', 'articles_list.php', 'event') ?>><svg class="d-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg><span class="nav-i18n" data-en="Art Events" data-ar="الملتقيات الفنية">Art Events</span></a>
        <a href="articles_list.php?type=artist_spotlight"<?= navAct('', 'articles_list.php', 'artist_spotlight') ?>><svg class="d-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg><span class="nav-i18n" data-en="Artist Spotlights" data-ar="أضواء على الفنانين">Artist Spotlights</span></a>
        <a href="training.php"<?= navAct('', 'training.php') ?>><svg class="d-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1 2.7 3 6 3s6-2 6-3v-5"/></svg><span class="nav-i18n" data-en="Art Institutes" data-ar="المعاهد الفنية">Art Institutes</span></a>
      </div>
    </div>
    <div class="nav-item"><a href="community.php" id="nav-community-link"<?= navAct('community') ?>><span id="nav-community-label">Community</span></a></div>
    <div class="nav-item">
      <a href="support.php" id="nav-support-link"<?= navAct('support') ?>><span id="nav-support-label">Support</span><svg class="chevron" viewBox="0 0 10 6"><polyline points="1,1 5,5 9,1"/></svg></a>
      <div class="dropdown" id="dd-support">
        <a href="mailto:contact@oweili.com"><svg class="d-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg><span id="dd-sp1">Contact Us</span></a>
        <a href="support.php#how"><svg class="d-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-2.9 2.6-2.9 4"/><path d="M12 17.5h.01"/></svg><span id="dd-sp2">How It Works</span></a>
        <a href="support.php#terms"><svg class="d-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><span id="dd-sp3">Terms of Use</span></a>
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
    <?php owThemeButton(); ?>
  </div>
  <button type="button" class="mnav-btn" id="mnav-btn" aria-expanded="false" aria-controls="mnav-panel" aria-label="Open menu" data-label-en="Open menu" data-label-ar="فتح القائمة" data-close-en="Close menu" data-close-ar="إغلاق القائمة">
    <svg class="mnav-bars" viewBox="0 0 24 24" aria-hidden="true"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></svg>
    <svg class="mnav-x" viewBox="0 0 24 24" aria-hidden="true"><line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/></svg>
  </button>
  <div class="mnav-panel" id="mnav-panel">
    <a class="mnav-link" href="marketplace.php" id="mnav-marketplace"<?= navAct('marketplace') ? ' data-active="1"' : '' ?>><span class="nav-i18n" data-en="Gallery" data-ar="المعرض">Gallery</span></a>
    <a class="mnav-link" href="explore.php"<?= navAct('explore') ? ' data-active="1"' : '' ?>><span class="nav-i18n" data-en="Explore Art Styles" data-ar="استكشف أساليب الرسم">Explore Art Styles</span></a>
    <a class="mnav-link" href="articles_list.php?type=event"><span class="nav-i18n" data-en="Discover" data-ar="اكتشف">Discover</span></a>
    <a class="mnav-link mnav-sub" href="articles_list.php?type=event"><span class="nav-i18n" data-en="Art Events" data-ar="الملتقيات الفنية">Art Events</span></a>
    <a class="mnav-link mnav-sub" href="articles_list.php?type=artist_spotlight"><span class="nav-i18n" data-en="Artist Spotlights" data-ar="أضواء على الفنانين">Artist Spotlights</span></a>
    <a class="mnav-link mnav-sub" href="training.php"><span class="nav-i18n" data-en="Art Institutes" data-ar="المعاهد الفنية">Art Institutes</span></a>
    <a class="mnav-link" href="community.php"<?= navAct('community') ? ' data-active="1"' : '' ?>><span class="nav-i18n" data-en="Community" data-ar="المجتمع">Community</span></a>
    <a class="mnav-link" href="support.php"<?= navAct('support') ? ' data-active="1"' : '' ?>><span class="nav-i18n" data-en="Support" data-ar="الدعم">Support</span></a>
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

  // One set of menu words for every page (pages no longer need their own copy).
  var NAV_WORDS = {
    'nav-community-label':   ['Community', 'المجتمع'],
    'nav-marketplace-label': ['Gallery', 'المعرض'],
    'nav-explore-label':     ['Explore Art Styles', 'استكشف أساليب الرسم'],
    'nav-support-label':     ['Support', 'الدعم'],
    'dd-sp1': ['Contact Us', 'تواصل معنا'], 'dd-sp2': ['How It Works', 'كيف يعمل الموقع'], 'dd-sp3': ['Terms of Use', 'شروط الاستخدام'],
    'n-login-t': ['Sign In', 'تسجيل الدخول'], 'n-reg-t': ['Join Free', 'انضم مجاناً'], 'lb': ['العربية', 'English']
  };
  function syncNavI18n(){
    var ar = curLang() === 'ar';
    document.querySelectorAll('.nav-i18n').forEach(function(el){
      el.textContent = ar ? (el.dataset.ar || el.dataset.en) : el.dataset.en;
    });
    for (var id in NAV_WORDS) {
      var el = document.getElementById(id);
      if (el) el.textContent = NAV_WORDS[id][ar ? 1 : 0];
    }
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
