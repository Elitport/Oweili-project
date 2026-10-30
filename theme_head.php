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
if (!function_exists('owThemeButton')) {
    function owThemeButton(string $class = 'lang-btn'): void {
        echo '<button type="button" class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . ' theme-btn" onclick="owToggleTheme()" title="Dark mode" aria-label="Dark mode">'
           . '<svg class="i-moon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20.5 14.5A8.5 8.5 0 0 1 9.5 3.5a8.5 8.5 0 1 0 11 11z"/></svg>'
           . '<svg class="i-sun" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4.5"/><path d="M12 1.5v2.5M12 20v2.5M1.5 12H4M20 12h2.5M4.6 4.6l1.8 1.8M17.6 17.6l1.8 1.8M4.6 19.4l1.8-1.8M17.6 6.4l1.8-1.8"/></svg>'
           . '</button>';
    }
}
if (!defined('OW_THEME_HEAD')):
    define('OW_THEME_HEAD', true);
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
/* Page transitions: fade out for 0.2s when a link to another page on this site is
   followed (the fade-in is pure CSS in oweili.css). Left alone: new-tab clicks,
   downloads, other sites, email/phone links, jumps on the same page, and links
   any page script already handled. Listening on window means page scripts run first. */
(function(){
  var d = document.documentElement;
  var calm = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var undo = null;
  function reset(){ d.classList.remove('ow-leaving'); if (undo) { clearTimeout(undo); undo = null; } }
  window.addEventListener('click', function(e){
    if (calm || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    var a = e.target && e.target.closest ? e.target.closest('a[href]') : null;
    if (!a || (a.target && a.target !== '_self') || a.hasAttribute('download')) return;
    var raw = a.getAttribute('href') || '', u;
    if (raw.charAt(0) === '#') return;
    try { u = new URL(a.href, location.href); } catch (x) { return; }
    if (u.origin !== location.origin) return;
    if (u.pathname === location.pathname && u.search === location.search && u.hash) return;
    e.preventDefault();
    d.classList.add('ow-leaving');
    setTimeout(function(){ location.href = u.href; }, 200);
    undo = setTimeout(reset, 3000);   // a link that never leaves the page (e.g. a download) shows the page again
  });
  /* back / forward buttons can show a saved copy of the page: never leave it faded */
  window.addEventListener('pageshow', reset);
  /* Background video (about 3MB). Phones on a slow connection or with Data Saver,
     and visitors who ask for less motion, never download it (the soft page
     colour shows instead). On other pages than the homepage it only starts
     after the page itself has loaded (data-src), so it never slows the page. */
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
