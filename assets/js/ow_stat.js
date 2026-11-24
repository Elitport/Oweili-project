/* ow_stat.js — the owner dashboard's anonymous counts (owner, 24 Nov 2026).
   After a page has loaded, sends one tiny signal to stat.php: what was seen (the page, an artwork,
   an artist, an Art Styles style, an Art Map place), the page language and the device type
   (phone / tablet / computer). Nothing about the person is sent or kept: no name, no address,
   no internet address. Each thing is counted once per browser per day (the list of what was
   already counted today stays in this browser only). Nothing is sent by a browser that opened
   Admin (cookie ow_nostat), on staff pages, or by test and robot software (navigator.webdriver).
   Other pages call window.owStat(kind, item) for things that open inside a page. */
(function () {
  'use strict';
  var me = document.currentScript, END = (me && me.getAttribute('data-end')) || '/stat.php';
  function off() {
    try {
      return !!navigator.webdriver || !navigator.sendBeacon || /(^|; )ow_nostat=/.test(document.cookie) ||
        /\/(admin|subadmin|owner)[^\/]*$/.test(location.pathname);
    } catch (e) { return true; }
  }
  function device() {
    var ua = navigator.userAgent || '', tp = navigator.maxTouchPoints || 0;
    var w = Math.min(screen.width || 0, screen.height || 0);
    if (/iPad/.test(ua) || (/Macintosh/.test(ua) && tp > 1)) return 'tablet';
    if (/Mobi|iPhone|iPod|Android.*Mobile|Windows Phone/.test(ua)) return 'phone';
    if (/Android|Tablet|Silk|Kindle/.test(ua)) return 'tablet';
    if (tp > 0 && w > 0 && w < 600) return 'phone';
    return 'computer';
  }
  function today() { return new Date().toISOString().slice(0, 10).replace(/-/g, ''); }
  function seen(key) {
    try {
      var s = JSON.parse(localStorage.getItem('ow_st') || '{}');
      if (s.d !== today()) s = { d: today(), k: {} };
      if (s.k[key]) return true;
      s.k[key] = 1;
      localStorage.setItem('ow_st', JSON.stringify(s));
    } catch (e) {}
    return false;
  }
  function send(kind, item) {
    if (off()) return;
    item = String(item == null ? '' : item).slice(0, 80);
    if (seen(kind + ':' + item)) return;
    try {
      var f = new FormData();
      f.append('k', kind); f.append('i', item);
      f.append('l', document.documentElement.lang === 'ar' ? 'ar' : 'en');
      f.append('d', device());
      navigator.sendBeacon(END, f);
    } catch (e) {}
  }
  window.owStat = send;
  function page() {
    var p = location.pathname, q = new URLSearchParams(location.search);
    var m = p.match(/\/art-map\/([a-z0-9-]+)(?:\/([a-z0-9-]+))?\/?$/);
    send('visit', '');
    if (m) {
      send('page', 'art_place');
      if (m[2]) send('place', m[1] + '/' + m[2]); else send('region', m[1]);
      return;
    }
    var name = (p.split('/').pop() || 'index').replace(/\.php$/, '') || 'index';
    if (/^verify$/.test(name) && !/\.php$/.test(p)) name = 'cert_verify';
    send('page', name);
    if (name === 'index') send('step', 'home');
    if (name === 'marketplace') send('step', 'gallery');
    if (name === 'artwork' && /^\d+$/.test(q.get('id') || '')) { send('artwork', q.get('id')); send('step', 'artwork'); }
    if (name === 'artist_dashboard' && /^\d+$/.test(q.get('id') || '')) send('artist', q.get('id'));
  }
  function go() { try { page(); } catch (e) {} }
  if (document.prerendering) document.addEventListener('prerenderingchange', go, { once: true });
  else if (document.readyState === 'complete') setTimeout(go, 0);
  else addEventListener('load', function () { setTimeout(go, 0); });
})();
