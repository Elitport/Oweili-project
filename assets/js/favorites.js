/* =====================================================================
 *  favorites.js — the heart button, shared by the Gallery
 *  (marketplace.php) and the Buyer dashboard (collector_dashboard.php).
 *  The page sets window.OW_FAV first:
 *    { buyer: true|false, csrf: '…', ids: ['12', …],
 *      labels: { on: 'Save to favorites', off: 'Remove from favorites' },
 *      login: function(id){ … },          // guests: where to sign in
 *      changed: function(id, on){ … } }   // optional, after a save/remove
 *  Any button with data-fav="ID" becomes a heart. Saves through favorite.php.
 * ===================================================================== */
(function(){
  var C = window.OW_FAV || {};
  var favs = new Set((C.ids || []).map(String));
  var labels = C.labels || { on: 'Save to favorites', off: 'Remove from favorites' };

  function sync(id, on){
    id = String(id); if (on) favs.add(id); else favs.delete(id);
    document.querySelectorAll('[data-fav="' + id + '"]').forEach(function(b){
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
      b.setAttribute('aria-label', on ? labels.off : labels.on);
    });
  }
  window.owFavSync = sync;
  window.owFavHas = function(id){ return favs.has(String(id)); };

  document.addEventListener('click', function(ev){
    var b = ev.target.closest ? ev.target.closest('[data-fav]') : null;
    if (!b || !b.dataset.fav) return;
    ev.preventDefault(); ev.stopPropagation();
    var id = b.dataset.fav;
    if (!C.buyer) { if (C.login) C.login(id); return; }
    var on = b.getAttribute('aria-pressed') !== 'true';
    sync(id, on);
    var fd = new FormData();
    fd.append('csrf_token', C.csrf || ''); fd.append('artwork_id', id); fd.append('on', on ? '1' : '0');
    fetch('favorite.php', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (d && d.ok) { if (C.changed) C.changed(id, on); return; }
        sync(id, !on);
        if (d && d.error === 'login' && C.login) C.login(id);
      })
      .catch(function(){ sync(id, !on); });
  });
})();
