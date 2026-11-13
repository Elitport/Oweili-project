<?php
/* Shared brand navbar — include with: <?php include __DIR__ . "/nav.php"; ?>
   Assumes config.php was already required by the host page (session started). */
if (!function_exists('currentUser')) { @require_once __DIR__ . '/config.php'; }
$navUser  = function_exists('currentUser') ? currentUser() : null;
$navIsAdmin = function_exists('isAdmin') && isAdmin();
$navName  = $navUser ? (function_exists('owRealName') ? (owRealName($navUser['full_name_en']) ?: (owRealName($navUser['full_name_ar']) ?: (string) $navUser['email'])) : trim((string)($navUser['full_name_en'] ?: $navUser['email']))) : '';
if ($navName === '') { $navName = 'Account'; }
$navFirst = $navUser ? (explode(' ', $navName)[0]) : '';
/* Auctions stays out of the menu until the admin switches the page on (gallery_lib.php) */
$owAucOn = $owGiftOn = $owComOn = $owCommOn = $owSpotOn = false;
try { if (function_exists('getDB')) { require_once __DIR__ . '/gallery_lib.php'; $owAucOn = owAuctionsPageOn(); $owGiftOn = owGiftsPageOn(); $owComOn = owCommunityPageOn(); $owCommOn = owCommissionsPageOn(); $owSpotOn = owSpotlightsOn(); } } catch (Throwable $e) { $owAucOn = $owGiftOn = $owComOn = $owCommOn = $owSpotOn = false; }
/* Hidden for now, to bring back later (6 Nov 2026): Gifts, Commissions, Community, Artist Spotlights.
   The Collect menu shows only while one of its pages is shown; its top word opens the first of them */
$owCollectUrl = $owGiftOn ? 'gifts.php' : ($owCommOn ? 'commission.php' : ($owAucOn ? 'auctions.php' : ''));
$navId    = $navUser ? (int) $navUser['id'] : 0;

/* Which menu item is the current page (pink underline) */
$navPage  = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$navType  = (string) ($_GET['type'] ?? '');
if ($navPage === 'article.php') { $navType = ''; }
$navSection = [
    'community.php' => 'community', 'marketplace.php' => 'marketplace', 'auctions.php' => 'collect', 'gifts.php' => 'collect', 'commission.php' => 'collect',
    'explore.php' => 'explore', 'support.php' => 'support', 'art_map.php' => 'institutes', 'art_place.php' => 'institutes', 'institutes.php' => 'institutes', 'training.php' => 'institutes', 'events.php' => 'art_event',
][$navPage] ?? '';
/* Art Events / Art Education / Artist Spotlights are their own menu items (6 Nov 2026): the list page
   by its ?type=, one article by its own type (article.php keeps it in $a) */
if ($navPage === 'articles_list.php') { $navSection = 'art_' . (in_array($navType, ['event', 'artist_spotlight', 'art_education'], true) ? $navType : 'event'); }
if ($navPage === 'article.php') { $navSection = 'art_' . (string) (($GLOBALS['a']['type'] ?? '') ?: 'event'); }
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
<?php /* the language is decided by the server (lang_lib.php); no early switch here any more */ ?>
<?php include __DIR__ . '/theme_head.php'; ?>
<link rel="stylesheet" href="<?= owAsset('assets/css/oweili.css') ?>">
<link rel="stylesheet" href="<?= owAsset('assets/css/nav.css') ?>">
<nav class="topnav" id="topnav">
  <a class="nav-logo" href="index.php" aria-label="Oweili · أويلي"><?php owLogoMark(); ?><span class="nav-word">OWEILI</span></a>
  <div class="nav-center" id="nav-center">
    <div class="nav-item"><a href="marketplace.php" id="nav-marketplace-link"<?= navAct('marketplace') ?>><span id="nav-marketplace-label">Gallery</span></a></div>
<?php if ($owCollectUrl !== ''): ?>
    <div class="nav-item">
      <a href="<?= $owCollectUrl ?>" id="nav-collect-link"<?= navAct('collect') ?>><span class="nav-i18n" data-en="Collect" data-ar="اقتنِ">Collect</span><svg class="chevron" viewBox="0 0 10 6"><polyline points="1,1 5,5 9,1"/></svg></a>
      <div class="dropdown" id="dd-collect">
<?php if ($owGiftOn): ?>        <a href="gifts.php"<?= navAct('', 'gifts.php') ?>><svg class="d-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="18" height="13" rx="2"/><path d="M12 8v13M3 12h18M12 8c-2-4-6-4-6-1s4 1 6 1zM12 8c2-4 6-4 6-1s-4 1-6 1z"/></svg><span class="nav-i18n" data-en="Gifts" data-ar="هدايا">Gifts</span></a><?php endif; ?>
<?php if ($owCommOn): ?>        <a href="commission.php"<?= navAct('', 'commission.php') ?>><svg class="d-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="9" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/></svg><span class="nav-i18n" data-en="Commissions" data-ar="طلب لوحة خاصة">Commissions</span></a><?php endif; ?>
        <?php if ($owAucOn): ?>        <a href="auctions.php" id="nav-auctions-link"<?= navAct('', 'auctions.php') ?>><svg class="d-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4l6 6M11 7l6 6M13 5l-7 7 3 3 7-7M8 14l-5 5"/><path d="M3 21h9"/></svg><span class="nav-i18n" data-en="Auctions" data-ar="المزاد">Auctions</span><span class="nav-soon nav-i18n" data-en="Soon" data-ar="قريباً">Soon</span></a><?php endif; ?>
      </div>
    </div>
<?php endif; ?>
    <div class="nav-item"><a href="explore.php" id="nav-explore-link"<?= navAct('explore') ?>><span id="nav-explore-label">Art Styles</span></a></div>
    <?php /* the old Discover items, straight in the menu (owner, 6 Nov 2026) */ ?>
    <div class="nav-item"><a href="articles_list.php?type=event" id="nav-events-link"<?= navAct('art_event') ?>><span class="nav-i18n" data-en="Art Events" data-ar="الملتقيات الفنية">Art Events</span></a></div>
<?php if ($owSpotOn): ?>    <div class="nav-item"><a href="articles_list.php?type=artist_spotlight"<?= navAct('art_artist_spotlight') ?>><span class="nav-i18n" data-en="Artist Spotlights" data-ar="أضواء على الفنانين">Artist Spotlights</span></a></div><?php endif; ?>
    <div class="nav-item"><a href="articles_list.php?type=art_education" id="nav-education-link"<?= navAct('art_art_education') ?>><span class="nav-i18n" data-en="Art Education" data-ar="التثقيف الفني">Art Education</span></a></div>
    <div class="nav-item"><a href="art_map.php" id="nav-institutes-link"<?= navAct('institutes') ?>><span class="nav-i18n" data-en="Art Map" data-ar="وجهات">Art Map</span></a></div>
<?php if ($owComOn): ?>    <div class="nav-item"><a href="community.php" id="nav-community-link"<?= navAct('community') ?>><span id="nav-community-label">Community</span></a></div><?php endif; ?>
    <div class="nav-item">
      <a href="support.php" id="nav-support-link"<?= navAct('support') ?>><span id="nav-support-label">Support</span><svg class="chevron" viewBox="0 0 10 6"><polyline points="1,1 5,5 9,1"/></svg></a>
      <div class="dropdown" id="dd-support">
        <a href="mailto:<?= e(owContactEmail()) ?>"><svg class="d-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg><span id="dd-sp1">Contact Us</span></a>
        <a href="support.php#how"><svg class="d-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-2.9 2.6-2.9 4"/><path d="M12 17.5h.01"/></svg><span id="dd-sp2">How It Works</span></a>
        <a href="terms.php"><svg class="d-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><span id="dd-sp3">Terms of Use</span></a>
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
    <?php owCornerControls('tgl()'); ?>
  </div>
  <button type="button" class="mnav-btn" id="mnav-btn" aria-expanded="false" aria-controls="mnav-panel" aria-label="Open menu" data-label-en="Open menu" data-label-ar="فتح القائمة" data-close-en="Close menu" data-close-ar="إغلاق القائمة">
    <svg class="mnav-bars" viewBox="0 0 24 24" aria-hidden="true"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></svg>
    <svg class="mnav-x" viewBox="0 0 24 24" aria-hidden="true"><line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/></svg>
  </button>
  <div class="mnav-panel" id="mnav-panel">
    <a class="mnav-link" href="marketplace.php" id="mnav-marketplace"<?= navAct('marketplace') ? ' data-active="1"' : '' ?>><span class="nav-i18n" data-en="Gallery" data-ar="المعرض">Gallery</span></a>
<?php if ($owCollectUrl !== ''): ?>    <a class="mnav-link" href="<?= $owCollectUrl ?>"<?= navAct('collect') ? ' data-active="1"' : '' ?>><span class="nav-i18n" data-en="Collect" data-ar="اقتنِ">Collect</span></a>
<?php if ($owGiftOn): ?>    <a class="mnav-link mnav-sub" href="gifts.php"><span class="nav-i18n" data-en="Gifts" data-ar="هدايا">Gifts</span></a><?php endif; ?>
<?php if ($owCommOn): ?>    <a class="mnav-link mnav-sub" href="commission.php"><span class="nav-i18n" data-en="Commissions" data-ar="طلب لوحة خاصة">Commissions</span></a><?php endif; ?>
<?php if ($owAucOn): ?>    <a class="mnav-link mnav-sub" href="auctions.php"><span class="nav-i18n" data-en="Auctions" data-ar="المزاد">Auctions</span> <span class="nav-soon nav-i18n" data-en="Soon" data-ar="قريباً">Soon</span></a><?php endif; ?><?php endif; /* the Collect group */ ?>
    <a class="mnav-link" href="explore.php"<?= navAct('explore') ? ' data-active="1"' : '' ?>><span class="nav-i18n" data-en="Art Styles" data-ar="أساليب الرسم">Art Styles</span></a>
    <a class="mnav-link" href="articles_list.php?type=event"<?= navAct('art_event') ? ' data-active="1"' : '' ?>><span class="nav-i18n" data-en="Art Events" data-ar="الملتقيات الفنية">Art Events</span></a>
<?php if ($owSpotOn): ?>    <a class="mnav-link" href="articles_list.php?type=artist_spotlight"<?= navAct('art_artist_spotlight') ? ' data-active="1"' : '' ?>><span class="nav-i18n" data-en="Artist Spotlights" data-ar="أضواء على الفنانين">Artist Spotlights</span></a><?php endif; ?>
    <a class="mnav-link" href="articles_list.php?type=art_education"<?= navAct('art_art_education') ? ' data-active="1"' : '' ?>><span class="nav-i18n" data-en="Art Education" data-ar="التثقيف الفني">Art Education</span></a>
    <a class="mnav-link" href="art_map.php"<?= navAct('institutes') ? ' data-active="1"' : '' ?>><span class="nav-i18n" data-en="Art Map" data-ar="وجهات">Art Map</span></a>
<?php if ($owComOn): ?>    <a class="mnav-link" href="community.php"<?= navAct('community') ? ' data-active="1"' : '' ?>><span class="nav-i18n" data-en="Community" data-ar="المجتمع">Community</span></a><?php endif; ?>
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
    'nav-explore-label':     ['Art Styles', 'أساليب الرسم'],
    'nav-support-label':     ['Support', 'الدعم'],
    'dd-sp1': ['Contact Us', 'تواصل معنا'], 'dd-sp2': ['How It Works', 'كيف يعمل الموقع'], 'dd-sp3': ['Terms of Use', 'شروط الاستخدام'],
    'n-login-t': ['Sign In', 'تسجيل الدخول'], 'n-reg-t': ['Join Free', 'انضم مجاناً'], 'lb': ['ع', 'E']
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
