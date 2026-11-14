/* Art Map: the free OpenStreetMap map above the cards (owner, 14 Nov 2026). One pin per place shown in the cards
   (the same tab, region and search); tapping a pin shows its name and a link to its page. Leaflet is served from our
   own files (assets/vendor/leaflet, BSD-2-Clause); the map pictures come from tile.openstreetmap.org with its credit. */
(function () {
  function start() {
    var el = document.getElementById('m-map');
    if (!el || !window.L) return;
    var pins = [];
    try { pins = JSON.parse(el.getAttribute('data-pins') || '[]'); } catch (e) { return; }
    if (!pins.length) return;
    var ar = document.documentElement.dir === 'rtl';
    var map = L.map(el, { scrollWheelZoom: false, attributionControl: true, zoomControl: true });
    map.attributionControl.setPrefix(false);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 18,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">' + (ar ? 'المساهمون في OpenStreetMap' : 'OpenStreetMap contributors') + '</a>'
    }).addTo(map);
    var css = getComputedStyle(document.documentElement);
    var pink = (css.getPropertyValue('--color-primary') || '#DB8084').trim();
    var navy = (css.getPropertyValue('--color-accent') || '#23314A').trim();
    var bounds = [];
    pins.forEach(function (p) {
      var m = L.circleMarker([p.lat, p.lng], { radius: 8, color: navy, weight: 2, fillColor: pink, fillOpacity: 0.95 }).addTo(map);
      var box = document.createElement('div');
      box.className = 'm-pop';
      var b = document.createElement('strong'); b.textContent = p.name; box.appendChild(b);
      if (p.city) { var c = document.createElement('div'); c.textContent = p.city; box.appendChild(c); }
      if (p.url) { var a = document.createElement('a'); a.href = p.url; a.textContent = ar ? 'صفحة المكان' : 'See the place'; box.appendChild(a); }
      m.bindPopup(box);
      bounds.push([p.lat, p.lng]);
    });
    if (bounds.length === 1) map.setView(bounds[0], 13);
    else map.fitBounds(bounds, { padding: [28, 28], maxZoom: 13 });
    el.addEventListener('click', function () { map.scrollWheelZoom.enable(); }, { once: true });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();
