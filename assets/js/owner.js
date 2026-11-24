/* owner.js — the owner dashboard's menu and language button (owner, 24 Nov 2026).
   Laptop: the arrow button shrinks the menu to icons (remembered in this browser).
   iPad and phone: the menu button opens the full menu; Escape, a tap outside or a link closes it.
   While a group message is going out, an open owner page asks the server every minute,
   so the next small group of emails can go. */
function owLang() {
  var ar = document.documentElement.getAttribute('dir') !== 'rtl';
  try { var u = new URL(location.href); u.searchParams.set('lang', ar ? 'ar' : 'en'); location.href = u.toString(); } catch (e) { location.reload(); }
}
(function () {
  'use strict';
  var root = document.documentElement, side = document.getElementById('o-side'), burger = document.getElementById('o-burger'),
      scrim = document.getElementById('o-scrim'), col = document.getElementById('o-collapse');
  if (col) {
    col.setAttribute('aria-pressed', root.classList.contains('o-mini') ? 'true' : 'false');
    col.addEventListener('click', function () {
      var mini = root.classList.toggle('o-mini');
      col.setAttribute('aria-pressed', mini ? 'true' : 'false');
      try { localStorage.setItem('ow_side', mini ? 'mini' : 'full'); } catch (e) {}
    });
  }
  function setOpen(open) {
    root.classList.toggle('o-open', open);
    if (burger) burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (scrim) scrim.hidden = !open;
    if (open && side) { var first = side.querySelector('a'); if (first) first.focus(); }
    else if (!open && burger && document.activeElement && side && side.contains(document.activeElement)) burger.focus();
  }
  if (burger) burger.addEventListener('click', function () { setOpen(!root.classList.contains('o-open')); });
  if (scrim) scrim.addEventListener('click', function () { setOpen(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && root.classList.contains('o-open')) setOpen(false); });
  if (side) side.addEventListener('click', function (e) { if (e.target.closest('a') && root.classList.contains('o-open')) setOpen(false); });
  var b = document.getElementById('o-batches');
  if (b && b.getAttribute('data-sending') === '1') {
    var n = 0, t = setInterval(function () {
      if (++n > 120) { clearInterval(t); return; }
      fetch('owner.php?ping=1', { credentials: 'same-origin', cache: 'no-store' }).catch(function () {});
    }, 60000);
  }
})();
