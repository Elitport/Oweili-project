/* Admin → Art Map → "Map location" (owner, 15 Nov 2026). Paste a Google Maps link: the latitude and longitude are read
   from it (short share links are opened by our own server), the two boxes fill in and a small OpenStreetMap preview shows
   the pin, so the admin can check it before saving. Typing the numbers by hand moves the pin too. Leaflet from our own
   files (assets/vendor/leaflet); nothing is saved until the admin presses "Save the location". */
(function () {
  var AR = function () { return document.documentElement.dir === 'rtl'; };
  var say = function (en, ar) { return AR() ? ar : en; };
  var DIG = { '٠': 0, '١': 1, '٢': 2, '٣': 3, '٤': 4, '٥': 5, '٦': 6, '٧': 7, '٨': 8, '٩': 9, '۰': 0, '۱': 1, '۲': 2, '۳': 3, '۴': 4, '۵': 5, '۶': 6, '۷': 7, '۸': 8, '۹': 9 };
  function num(v) {
    v = String(v || '').replace(/[٠-٩۰-۹]/g, function (d) { return DIG[d]; }).replace(/٫/g, '.').replace(/−/g, '-').replace(/\s+/g, '');
    return /^-?\d{1,3}(\.\d{1,12})?$/.test(v) ? parseFloat(v) : NaN;
  }
  function ok(la, lo) {
    la = parseFloat(la); lo = parseFloat(lo);
    if (isNaN(la) || isNaN(lo) || la < -90 || la > 90 || lo < -180 || lo > 180 || (la === 0 && lo === 0)) return null;
    return [Math.round(la * 1e6) / 1e6, Math.round(lo * 1e6) / 1e6];
  }
  function fromText(s) {
    var t = String(s || '');
    for (var i = 0; i < 3; i++) { try { var d = decodeURIComponent(t); if (d === t) break; t = d; } catch (e) { break; } }
    var n = '(-?\\d{1,3}\\.\\d+)', m, c, last = null, re = new RegExp('!3d' + n + '!4d' + n, 'g');
    while ((m = re.exec(t))) last = m;
    if (last && (c = ok(last[1], last[2]))) return c;
    if ((m = t.match(new RegExp('[?&](?:q|query|ll|sll|center|destination|daddr)=\\s*\\+?' + n + '\\s*,\\s*\\+?' + n))) && (c = ok(m[1], m[2]))) return c;
    if ((m = t.match(new RegExp('/(?:place|search|dir)/+\\+?' + n + '\\s*,\\s*\\+?' + n))) && (c = ok(m[1], m[2]))) return c;
    if ((m = t.match(new RegExp('@' + n + ',' + n))) && (c = ok(m[1], m[2]))) return c;
    var parts = String(s || '').replace(/،/g, ',').trim().split(/\s*[,;]\s*|\s+/);
    if (parts.length === 2) { var a = num(parts[0]), b = num(parts[1]); if (!isNaN(a) && !isNaN(b)) return ok(a, b); }
    return null;
  }
  function isShort(s) { return /^https:\/\/(?:maps\.app\.goo\.gl|goo\.gl)\//i.test(String(s || '').trim()); }

  function setup(form) {
    var link = form.querySelector('.mp-link'), lat = form.querySelector('.mp-lat'), lng = form.querySelector('.mp-lng');
    var box = form.querySelector('.mp-map'), msg = form.querySelector('.mp-msg'), map = null, pin = null, asked = 0;
    function note(en, ar, bad) { msg.textContent = say(en, ar); msg.classList.toggle('bad', !!bad); }
    function show() {
      var c = ok(num(lat.value), num(lng.value));
      if (!c || !window.L) { box.hidden = true; return c; }
      box.hidden = false;
      if (!map) {
        map = L.map(box, { scrollWheelZoom: false });
        map.attributionControl.setPrefix(false);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>' }).addTo(map);
      }
      var css = getComputedStyle(document.documentElement);
      var pink = (css.getPropertyValue('--color-primary') || '#DB8084').trim(), navy = (css.getPropertyValue('--color-accent') || '#23314A').trim();
      if (pin) pin.setLatLng(c); else pin = L.circleMarker(c, { radius: 9, color: navy, weight: 2, fillColor: pink, fillOpacity: 0.95 }).addTo(map);
      map.invalidateSize(); map.setView(c, 15);
      return c;
    }
    function outside(c) { return c && (c[0] < 16 || c[0] > 33 || c[1] < 34 || c[1] > 56); }
    function put(c) {
      lat.value = c[0]; lng.value = c[1]; show();
      if (outside(c)) note('Read: ' + c[0] + ', ' + c[1] + '. This point is outside Saudi Arabia. Please check it on the small map.', 'تمت القراءة: ' + c[0] + '، ' + c[1] + '. هذه النقطة خارج السعودية. يرجى التأكد منها على الخريطة الصغيرة.', true);
      else note('Read: ' + c[0] + ', ' + c[1] + '. Check the pin on the small map, then press "Save the location".', 'تمت القراءة: ' + c[0] + '، ' + c[1] + '. تأكد من الدبوس على الخريطة الصغيرة ثم اضغط «احفظ الموقع».');
    }
    function read() {
      var v = link.value.trim();
      if (!v) { note('', ''); return; }
      var c = fromText(v);
      if (c) { put(c); return; }
      if (!isShort(v)) { note('We could not read a location from this link. Open the place in Google Maps and copy the link, or type the two numbers.', 'لم نتمكن من قراءة الموقع من هذا الرابط. افتح المكان في خرائط Google وانسخ الرابط، أو اكتب الرقمين.', true); return; }
      var my = ++asked;
      note('Reading the link…', 'جارٍ قراءة الرابط…');
      var fd = new FormData(); fd.append('do', 'resolve_link'); fd.append('link', v);
      var tok = form.querySelector('input[name="csrf_token"]'); if (tok) fd.append('csrf_token', tok.value);
      fetch(form.getAttribute('action') || 'admin_art_map.php', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          if (my !== asked) return;
          if (j && j.ok) put([j.lat, j.lng]);
          else if (j && j.why === 'expired') note('The page was open too long. Please reload it and paste again.', 'انتهت صلاحية الصفحة. أعد تحميلها والصق الرابط مرة أخرى.', true);
          else note('We could not read a location from this short link. Open it, copy the long link from the address bar, or type the two numbers.', 'لم نتمكن من قراءة الموقع من هذا الرابط القصير. افتحه وانسخ الرابط الطويل من شريط العنوان، أو اكتب الرقمين.', true);
        })
        .catch(function () { if (my === asked) note('We could not reach the server. Please try again.', 'تعذّر الاتصال بالخادم. حاول مرة أخرى.', true); });
    }
    link.addEventListener('paste', function () { setTimeout(read, 0); });
    link.addEventListener('change', read);
    [lat, lng].forEach(function (el) { el.addEventListener('input', function () { show(); note('', ''); }); });
    var det = form.closest('details');
    if (det) det.addEventListener('toggle', function () { if (det.open) show(); });
    if (!det || det.open) show();
  }
  function start() { Array.prototype.forEach.call(document.querySelectorAll('form.mp-form'), setup); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();
