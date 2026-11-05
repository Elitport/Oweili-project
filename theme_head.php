<?php
/* =====================================================================
 *  theme_head.php — light / dark mode, shared by every page
 *  Include it inside <head>, before the stylesheets, so the saved mode is
 *  applied before anything is drawn (no flash):
 *      <?php include __DIR__ . '/theme_head.php'; ?>
 *  - The visitor's choice is saved in localStorage ('theme' = dark / light)
 *  - Before they choose, the phone or computer setting decides
 *  - Dark mode = class "dark-mode" on <html>; the colors live in oweili.css
 *  - owThemeButton() prints the sun / moon button (nav.php, staff headers)
 *  - also the page transition script (fade out on link click; fade in is CSS)
 * ===================================================================== */
require_once __DIR__ . '/ow_paths.php';   // owAsset(): links to our CSS and JS files
/* Admin email alerts held back so they arrive grouped (alert_lib.php): when a waiting
   group is due, it is sent after this page has been delivered (costs one file check). */
if (!function_exists('owAlertsAfterPage')) {
    function owAlertsAfterPage(): void {
        if (function_exists('litespeed_finish_request')) litespeed_finish_request();
        elseif (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
        ignore_user_abort(true);
        if (!function_exists('getDB')) return;
        require_once __DIR__ . '/alert_lib.php';
        owAlertsTick();
    }
    $owDue = __DIR__ . '/.logs/admin_alerts_due';
    if (is_file($owDue) && (int) @file_get_contents($owDue) <= time()) register_shutdown_function('owAlertsAfterPage');
}
/* Bashro: a message that got 429 / 503 is tried again after a page is delivered (bashro_send.php) */
if (is_file(__DIR__ . '/.logs/bashro_queue.json') && !function_exists('owBashroRetryAfterPage')) {
    function owBashroRetryAfterPage(): void {
        if (function_exists('litespeed_finish_request')) litespeed_finish_request();
        elseif (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
        ignore_user_abort(true);
        if (!function_exists('getDB')) return;
        try { require_once __DIR__ . '/bashro_send.php'; owBashroRetryDue(); } catch (Throwable $e) { error_log('owBashroRetry: ' . $e->getMessage()); }
    }
    register_shutdown_function('owBashroRetryAfterPage');
}
/* Once a day, after a page is delivered: private commission reference photos
   are deleted 30 days after delivery (commission_lib.php owCommCleanup), and
   (only once payments are switched on) escrow releases orders 7 days after delivery */
if (!function_exists('owCommAfterPage')) {
    function owCommAfterPage(): void {
        if (function_exists('litespeed_finish_request')) litespeed_finish_request();
        elseif (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
        ignore_user_abort(true);
        if (!function_exists('getDB')) return;
        try { require_once __DIR__ . '/commission_lib.php'; owCommCleanupTick(); } catch (Throwable $e) { error_log('owCommAfterPage: ' . $e->getMessage()); }
        try { require_once __DIR__ . '/order_lib.php'; owOrderAutoRelease(); } catch (Throwable $e) { error_log('owOrderAutoRelease: ' . $e->getMessage()); }   // escrow: does nothing while payments are off
    }
    $owCommDay = __DIR__ . '/.logs/commission_cleanup_day';
    if (!is_file($owCommDay) || trim((string) @file_get_contents($owCommDay)) !== gmdate('Y-m-d')) register_shutdown_function('owCommAfterPage');
}
if (!function_exists('owThemeButton')) {
    /* The light / dark button. One shared look on every page (.ow-ctl in oweili.css);
       the old $class argument is ignored on purpose, so pages can never drift apart. */
    function owThemeButton(string $class = ''): void {
        echo '<button type="button" class="ow-ctl theme-btn" onclick="owToggleTheme()" title="Dark mode" aria-label="Dark mode">'
           . '<svg class="i-moon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20.5 14.5A8.5 8.5 0 0 1 9.5 3.5a8.5 8.5 0 1 0 11 11z"/></svg>'
           . '<svg class="i-sun" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4.5"/><path d="M12 1.5v2.5M12 20v2.5M1.5 12H4M20 12h2.5M4.6 4.6l1.8 1.8M17.6 17.6l1.8 1.8M4.6 19.4l1.8-1.8M17.6 6.4l1.8-1.8"/></svg>'
           . '</button>';
    }
    /* The language button + the light / dark button, side by side, the same on every page.
       $onclick is the page's own language switch (tgl() on public pages, tglLang() on admin). */
    function owCornerControls(string $onclick = 'tgl()'): void {
        require_once __DIR__ . '/lang_lib.php';
        echo '<span class="ow-ctls"><button type="button" class="ow-ctl lang-btn" id="lb" onclick="' . htmlspecialchars($onclick, ENT_QUOTES, 'UTF-8') . '">' . (owIsAr() ? 'English' : 'العربية') . '</button>';
        owThemeButton();
        echo '</span>';
    }
}
if (!defined('OW_THEME_HEAD')):
    define('OW_THEME_HEAD', true);
?>
<style>html{background:#fff}html.dark-mode{background:#0F1520}html.ow-wait body{visibility:hidden;animation:owShow 0s 1.5s forwards}@keyframes owShow{to{visibility:visible}}</style>
<?php require_once __DIR__ . '/page_cache.php'; ?>
<?php require_once __DIR__ . '/lang_lib.php'; ?>
<script>
/* The language (lang_lib.php): the server already sent this page in the saved
   language. Here the browser only (1) remembers a new choice when the language
   button is pressed (cookie ow_lang, read by the server, + localStorage for the
   pages' own scripts), and (2) reloads a kept copy made in the other language
   (Back button, early loading, a page prepared in the background). */
(function(){
  var d = document.documentElement, L = <?= json_encode(owLang()) ?>;
  window.OW_LANG = L;
  var url = /[?&]lang=(ar|en)(&|#|$)/.test(location.search);
  function ck(){ var m = document.cookie.match(/(?:^|;\s*)ow_lang=(ar|en)/); return m ? m[1] : null; }
  function setCk(l){ try { document.cookie = 'ow_lang=' + l + ';path=/;max-age=31536000;samesite=lax' + (location.protocol === 'https:' ? ';secure' : ''); } catch (e) {} }
  function setLs(l){ try { localStorage.setItem('lang', l); } catch (e) {} }
  function stale(){ var c = ck(); return !!c && c !== window.OW_LANG && !url; }
  function again(){
    var k = 'ow_lang_rl', now = Date.now(), last = 0;
    try { last = +sessionStorage.getItem(k) || 0; sessionStorage.setItem(k, String(now)); } catch (e) {}
    if (now - last < 5000) return false;   // never a reload loop
    d.classList.add('ow-wait'); location.reload(); return true;
  }
  var c = ck();
  if (!c) {
    /* visitors from before 5 Nov 2026 kept their choice only in this browser */
    var ls = null; try { ls = localStorage.getItem('lang'); } catch (e) {}
    if ((ls === 'ar' || ls === 'en') && ls !== L && !url) { setCk(ls); if (!document.prerendering && again()) return; }
    else setCk(L);
  } else if (stale() && !document.prerendering) { if (again()) return; }
  setLs(window.OW_LANG);
  if (document.prerendering) document.addEventListener('prerenderingchange', function(){ if (stale()) again(); }, { once: true });
  window.addEventListener('pageshow', function(e){ if (e.persisted && stale()) again(); });
  new MutationObserver(function(){
    var l = (d.getAttribute('lang') === 'ar' || d.getAttribute('dir') === 'rtl') ? 'ar' : 'en';
    if (l !== window.OW_LANG) { window.OW_LANG = l; setCk(l); setLs(l); }
  }).observe(d, { attributes: true, attributeFilter: ['lang', 'dir'] });
  document.addEventListener('DOMContentLoaded', function(){ d.classList.remove('ow-wait'); });
})();
</script>
<script>window.OW_STATE = <?= json_encode(owStateCode()) ?>; window.OW_STATE_AT = <?= (int) round(microtime(true) * 1000) ?>; window.OW_PRIVATE = <?= defined('OW_PUBLIC_PAGE') ? 'false' : 'true' ?>;</script>
<link rel="preload" href="<?= owBase() ?>assets/fonts/dmsans-300-800-latin.woff2" as="font" type="font/woff2" crossorigin>
<?php /* Instant page changes: Chrome, Edge and Android load a page in the background
   when the finger or mouse comes near its link (prefetch), and get it fully ready the
   moment it is pressed (prerender). Never for pages that change something when they
   are opened (sign out, email links, uploads, staff pages, private files). */ ?>
<script type="speculationrules">
{"prefetch":[{"source":"document","eagerness":"moderate","where":{"and":[{"href_matches":"/*"},{"not":{"href_matches":["/logout*","/verify.php*","/forgot*","/upload_artwork*","/edit_artwork*","/edit_profile*","/my_articles*","/my_orders*","/commission_file*","/certificate*","/img*","/view*","/favorite*","/inquire*","/bashro_api*","/bashro_send*","/admin*","/subadmin_dashboard*","/artist_commission*","/auth_state*"]}},{"not":{"href_matches":["/*\\?*leave=*","/*\\?*logout=*","/*\\?*token=*","/*\\?*k=*"]}},{"not":{"selector_matches":"[data-no-prefetch], [download], [target=_blank]"}}]}}],"prerender":[{"source":"document","eagerness":"conservative","where":{"and":[{"href_matches":"/*"},{"not":{"href_matches":["/logout*","/verify.php*","/forgot*","/upload_artwork*","/edit_artwork*","/edit_profile*","/my_articles*","/my_orders*","/commission_file*","/certificate*","/img*","/view*","/favorite*","/inquire*","/bashro_api*","/bashro_send*","/admin*","/subadmin_dashboard*","/artist_commission*","/auth_state*"]}},{"not":{"href_matches":["/*\\?*leave=*","/*\\?*logout=*","/*\\?*token=*","/*\\?*k=*","/*\\?*show=*"]}},{"not":{"selector_matches":"[data-no-prefetch], [download], [target=_blank]"}}]}}]}
</script>
<script>
(function(){
  var d = document.documentElement, KEY = 'theme';
  var mq = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
  function saved(){ try { var t = localStorage.getItem(KEY); return t === 'dark' || t === 'light' ? t : null; } catch (e) { return null; } }
  function isDark(){ return d.classList.contains('dark-mode'); }
  /* the button says what a click will do: moon = go dark, sun = go light */
  function sync(){
    var ar = d.getAttribute('dir') === 'rtl', dark = isDark();
    var t = dark ? (ar ? 'الوضع النهاري' : 'Light mode') : (ar ? 'الوضع الليلي' : 'Dark mode');
    document.querySelectorAll('.theme-btn').forEach(function(b){ b.title = t; b.setAttribute('aria-label', t); b.setAttribute('aria-pressed', dark ? 'true' : 'false'); });
  }
  function apply(mode){ d.classList.toggle('dark-mode', mode === 'dark'); sync(); }
  apply(saved() || (mq && mq.matches ? 'dark' : 'light'));
  window.owToggleTheme = function(){
    var mode = isDark() ? 'light' : 'dark';
    try { localStorage.setItem(KEY, mode); } catch (e) {}
    apply(mode);
  };
  /* follow the device setting until the visitor makes their own choice */
  if (mq) { var onChange = function(e){ if (!saved()) apply(e.matches ? 'dark' : 'light'); };
    if (mq.addEventListener) mq.addEventListener('change', onChange); else if (mq.addListener) mq.addListener(onChange); }
  document.addEventListener('DOMContentLoaded', function(){
    sync();
    new MutationObserver(sync).observe(d, { attributes: true, attributeFilter: ['dir'] });
  });
})();
</script>
<script>
/* Page changes open straight away (no fade-out wait; the soft cross-fade is CSS,
   see @view-transition in oweili.css). Browsers without the instant loading above
   (Safari on iPhone and Mac, Firefox) get their own early load: the moment a
   finger touches a link (or the mouse rests on it), that page is fetched with
   X-Ow-Prefetch: 1. The server answers public pages with a 10-second copy
   (page_cache.php), so the tap a moment later opens it at once. Only PUBLIC
   pages are ever fetched early (the list below), never pages that change
   something when opened (Log out, email links, uploads, staff pages). */
(function(){
  var d = document.documentElement;
  var skip = /^\/(logout|verify\.php|forgot|upload_artwork|edit_artwork|edit_profile|my_articles|my_orders|commission_file|certificate|img|view|favorite|inquire|bashro_api|bashro_send|admin|subadmin_dashboard|artist_commission|auth_state)/;
  var risky = /[?&](leave|logout|token|k|ref|preview)=/;
  var pub = /^\/(|index\.php|marketplace\.php|explore\.php|community\.php|gifts\.php|support\.php|institutes\.php|articles_list\.php|article\.php|artwork\.php|artist_dashboard\.php|login\.php|register(_artist|_collector)?\.php|privacy\.php|terms\.php|cert_verify\.php|verify|commission\.php)$/;
  var native = !!(window.HTMLScriptElement && HTMLScriptElement.supports && HTMLScriptElement.supports('speculationrules'));
  var done = {}, count = 0, timer = null, slow = false;
  try { var c = navigator.connection || {}; slow = !!c.saveData || /(^|-)(2g|3g)$/.test(c.effectiveType || ''); } catch (e) {}
  function warm(a){
    if (native || slow || count >= 8 || !a || !window.fetch || a.hasAttribute('download') || a.hasAttribute('data-no-prefetch') || (a.target && a.target !== '_self')) return;
    var u; try { u = new URL(a.href, location.href); } catch (x) { return; }
    if (u.origin !== location.origin || skip.test(u.pathname) || risky.test(u.search) || !pub.test(u.pathname)) return;
    if (u.pathname === '/artist_dashboard.php' && !/[?&]id=\d/.test(u.search)) return;   // only public profiles
    if (u.pathname === location.pathname && u.search === location.search) return;
    var k = u.pathname + u.search; if (done[k]) return; done[k] = 1; count++;
    try { fetch(k, { credentials: 'same-origin', headers: { 'X-Ow-Prefetch': '1' } }).catch(function(){}); } catch (x) {}
  }
  /* Safety for kept copies (Back button, early loads): if the visitor signed in or
     out since this page was made, reload it, so nobody sees another sign-in state */
  var made = window.OW_STATE_AT || 0;
  function check(){
    if (!window.OW_STATE || !window.fetch) return;
    fetch('/auth_state.php', { credentials: 'same-origin', cache: 'no-store' }).then(function(r){ return r.json(); })
      .then(function(j){ if (j && j.s && j.s !== window.OW_STATE) location.reload(); }).catch(function(){});
  }
  /* private pages (dashboards, studio, admin, private links) are always made fresh, never shown from a kept copy */
  window.addEventListener('pageshow', function(e){ if (!e.persisted) return; if (window.OW_PRIVATE) { location.reload(); return; } check(); });
  document.addEventListener('DOMContentLoaded', function(){ if (made && Date.now() - made > 3000) check(); });
  function link(e){ return e.target && e.target.closest ? e.target.closest('a[href]') : null; }
  /* photos: mark each one once it has loaded (or failed), so its soft placeholder tint goes away (oweili.css) */
  var mark = function(e){ var t = e.target; if (t && t.tagName === 'IMG') t.classList.add('ld'); };
  document.addEventListener('load', mark, true); document.addEventListener('error', mark, true);
  document.addEventListener('DOMContentLoaded', function(){ document.querySelectorAll('img').forEach(function(i){ if (i.complete && i.naturalWidth) i.classList.add('ld'); }); });
  document.addEventListener('touchstart', function(e){ warm(link(e)); }, { passive: true, capture: true });
  document.addEventListener('mouseover', function(e){ var a = link(e); clearTimeout(timer); if (a) timer = setTimeout(function(){ warm(a); }, 65); }, { passive: true, capture: true });
  /* Background video (about 3MB). Phones on a slow connection or with Data Saver,
     and visitors who ask for less motion, never download it (the soft page
     colour shows instead). Only the homepage has the video (since 4 Nov 2026);
     a video with data-src elsewhere would start only after the page has loaded. */
  var lite = false;
  try {
    var nc = navigator.connection || {};
    lite = !!nc.saveData || /(^|-)(2g|3g)$/.test(nc.effectiveType || '') || matchMedia('(prefers-reduced-motion: reduce)').matches;
  } catch (e) {}
  if (lite) d.classList.add('ow-lite');
  document.addEventListener('DOMContentLoaded', function(){
    if (lite) { document.querySelectorAll('video.bg-video, #vid').forEach(function(v){ if (v.getAttribute('src')) { v.removeAttribute('src'); v.load(); } }); return; }
    /* the homepage video is the hero: it starts straight away (class bg-now) */
    document.querySelectorAll('video.bg-now[data-src]').forEach(function(v){
      v.src = v.getAttribute('data-src'); v.removeAttribute('data-src');
      var p = v.play && v.play(); if (p && p.catch) p.catch(function(){});
    });
  });
  window.addEventListener('load', function(){
    if (lite) return;
    document.querySelectorAll('video[data-src]').forEach(function(v){
      v.src = v.getAttribute('data-src'); v.removeAttribute('data-src');
      var p = v.play && v.play(); if (p && p.catch) p.catch(function(){});
    });
  });
  /* background video: fade it in on every page as soon as it can play
     (also when the browser already had it ready from an earlier visit) */
  document.addEventListener('DOMContentLoaded', function(){
    if (lite) return;
    document.querySelectorAll('video.bg-video').forEach(function(v){
      var on = function(){ v.classList.add('on'); };
      if (v.readyState >= 3) on();
      v.addEventListener('canplay', on, { once: true });
      v.addEventListener('playing', on, { once: true });
      var p = v.play && v.play(); if (p && p.catch) p.catch(function(){});
    });
  });
})();
</script>
<?php endif; ?>
