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
 * ===================================================================== */
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
<?php endif; ?>
