/* =====================================================================
 *  explore.js — Explore Art Styles (explore.php)
 *  - draws the grid of style cards from art_styles.js (the one data source)
 *  - the filter bar (All / Traditional / Digital / Mixed Media / Modern)
 *  - the style window (a <dialog>) with the full story, techniques,
 *    signs of a human hand, famous artists and the collector's guide
 *  - every text is printed in both languages (bi-en / bi-ar); the page
 *    direction decides which one shows, so the language button needs no redraw
 *  - cover pictures are drawn in SVG with the brand colors (rule 31), so they
 *    follow light and dark mode. Real artworks from Oweili artists can
 *    replace them later.
 *  Read only: nothing the visitor types is used. The web address (#id) is
 *  only matched against the known style list.
 * ===================================================================== */
(function () {
  'use strict';
  var STYLES = window.OW_ART_STYLES || [];
  var BY_ID = {};
  STYLES.forEach(function (s) { BY_ID[s.id] = s; });

  var CATS = [
    ['all', 'All', 'الكل'],
    ['traditional', 'Traditional', 'تقليدي'],
    ['digital', 'Digital', 'رقمي'],
    ['mixed', 'Mixed Media', 'وسائط مختلطة'],
    ['modern', 'Modern', 'حديث ومعاصر']
  ];

  function esc(v) {
    return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function bi(en, ar) {
    return '<span class="bi-en" lang="en">' + esc(en) + '</span><span class="bi-ar" lang="ar">' + esc(ar) + '</span>';
  }

  /* ---------- cover pictures (SVG, brand colors via CSS classes) ---------- */
  var uid = 0;
  function rnd(seed) { return function () { seed = (seed * 9301 + 49297) % 233280; return seed / 233280; }; }
  var ART = {
    watercolor: function (u) {
      return '<defs><filter id="wb' + u + '" x="-30%" y="-30%" width="160%" height="160%"><feGaussianBlur stdDeviation="16"/></filter></defs>' +
        '<g filter="url(#wb' + u + ')"><circle cx="160" cy="140" r="78" class="pk" opacity=".55"/><circle cx="245" cy="168" r="82" class="gb" opacity=".55"/>' +
        '<circle cx="205" cy="102" r="52" class="ink" opacity=".22"/></g>' +
        '<path d="M60 236 C140 226 260 246 340 232" class="ln" stroke-width="1.2" opacity=".35"/>';
    },
    oil: function () {
      return '<circle cx="292" cy="92" r="32" class="pk"/>' +
        '<path d="M20 182 C110 168 230 198 380 176" class="st gb" stroke-width="30"/>' +
        '<path d="M20 222 C130 206 250 236 380 214" class="st ink" stroke-width="28" opacity=".85"/>' +
        '<path d="M20 262 C120 250 260 274 380 256" class="st pk" stroke-width="26" opacity=".6"/>' +
        '<path d="M70 170 l26 -6 M150 184 l30 4 M240 206 l24 -4 M110 214 l28 6 M300 248 l22 4" class="st bgk" stroke-width="5" opacity=".7"/>';
    },
    charcoal: function (u) {
      return '<defs><filter id="cb' + u + '"><feGaussianBlur stdDeviation="9"/></filter></defs>' +
        '<ellipse cx="205" cy="168" rx="120" ry="58" class="ink" opacity=".16" filter="url(#cb' + u + ')"/>' +
        '<path d="M70 210 C120 90 200 80 250 140 S330 220 350 110" class="st ink" stroke-width="12" opacity=".85"/>' +
        '<path d="M90 228 C150 150 210 150 270 196" class="st ink" stroke-width="4" opacity=".55"/>' +
        '<path d="M120 100 C170 70 230 74 280 92" class="st ink" stroke-width="2.5" opacity=".45"/>' +
        '<path d="M60 250 L340 246" class="st ink" stroke-width="1.5" opacity=".3"/>';
    },
    acrylic: function () {
      return '<rect x="40" y="168" width="320" height="104" rx="4" class="gb"/>' +
        '<rect x="232" y="54" width="110" height="114" class="pk"/>' +
        '<rect x="252" y="80" width="26" height="34" class="bgk"/><rect x="296" y="80" width="26" height="34" class="bgk"/>' +
        '<rect x="70" y="156" width="96" height="10" class="ink"/>' +
        '<path d="M150 210 q14 -14 28 0 q14 14 28 0 M120 240 q14 -12 28 0 q14 12 28 0 M220 232 q12 -12 24 0" class="st bgk" stroke-width="5"/>';
    },
    ink: function (u) {
      var l = '';
      for (var i = -200; i < 260; i += 11) l += '<path d="M' + (100 + i) + ' 50 L' + (300 + i) + ' 250" />';
      var x = '';
      for (var j = -200; j < 120; j += 11) x += '<path d="M' + (300 - j) + ' 50 L' + (100 - j) + ' 250" />';
      return '<defs><clipPath id="ic' + u + '"><circle cx="200" cy="150" r="96"/></clipPath><clipPath id="ih' + u + '"><rect x="200" y="40" width="120" height="220"/></clipPath></defs>' +
        '<g clip-path="url(#ic' + u + ')" class="ln" stroke-width="1.4"><g>' + l + '</g><g clip-path="url(#ih' + u + ')">' + x + '</g></g>' +
        '<circle cx="200" cy="150" r="96" class="ln" stroke-width="2"/>' +
        '<path d="M60 262 C120 248 180 270 236 258" class="st pk" stroke-width="5"/>';
    },
    pastel: function () {
      var s = '', r = rnd(7), rows = [['pk', 80], ['pk', 118], ['gb', 156], ['gb', 194], ['ink', 232]];
      rows.forEach(function (row) {
        for (var x = 30; x < 370; x += 22) {
          var y = row[1] + (r() * 14 - 7);
          s += '<path d="M' + x + ' ' + y + ' l26 ' + (r() * 8 - 4).toFixed(1) + '" class="st ' + row[0] + '" stroke-width="15" stroke-dasharray="3 2" opacity="' + (0.45 + r() * 0.4).toFixed(2) + '"/>';
        }
      });
      return s;
    },
    gouache: function () {
      return '<circle cx="128" cy="96" r="38" class="bgk"/>' +
        '<path d="M0 210 Q100 130 200 200 T400 180 V300 H0Z" class="gb"/>' +
        '<path d="M0 240 Q120 180 230 236 T400 222 V300 H0Z" class="pk"/>' +
        '<path d="M0 272 Q140 232 260 270 T400 262 V300 H0Z" class="ink"/>' +
        '<path d="M272 104 q10 -8 20 0 q10 -8 20 0 M300 84 q7 -6 14 0 q7 -6 14 0" class="st ink" stroke-width="3"/>';
    },
    printmaking: function () {
      var w = '';
      for (var i = 0; i < 4; i++) w += '<path d="M' + (40 + i * 80) + ' 210 C' + (60 + i * 80) + ' 140 ' + (120 + i * 80) + ' 130 ' + (128 + i * 80) + ' 168 C' + (110 + i * 80) + ' 160 ' + (96 + i * 80) + ' 176 ' + (110 + i * 80) + ' 190" class="st ink" stroke-width="5"/>';
      return '<rect x="34" y="40" width="332" height="220" class="ln" stroke-width="1.5" opacity=".5"/>' +
        '<circle cx="300" cy="90" r="22" class="pk"/>' + w +
        '<path d="M40 222 H360 M40 236 H360" class="st gb" stroke-width="3"/>' +
        '<text x="352" y="282" class="tx" text-anchor="end">3/20</text>' +
        '<path d="M14 20 h16 M22 12 v16 M370 20 h16 M378 12 v16" class="st ink" stroke-width="1.2" opacity=".6"/>';
    },
    digital_illustration: function () {
      var px = '';
      for (var i = 0; i < 4; i++) for (var j = 0; j < 4; j++) if ((i + j) % 2 === 0) px += '<rect x="' + (300 + i * 12) + '" y="' + (206 + j * 12) + '" width="10" height="10" class="gb"/>';
      return '<rect x="96" y="70" width="128" height="128" rx="22" class="gb"/>' +
        '<circle cx="214" cy="160" r="66" class="pk"/>' +
        '<rect x="96" y="70" width="128" height="128" rx="22" class="ln" stroke-width="4"/>' +
        '<circle cx="214" cy="160" r="66" class="ln" stroke-width="4"/>' +
        '<path d="M112 244 C150 220 190 262 236 238" class="st ink" stroke-width="4"/>' +
        '<path d="M270 80 L334 46 L346 58 L284 96 Z" class="ink"/><path d="M270 80 l-8 22 l22 -6" class="ink"/>' + px;
    },
    digital_painting: function (u) {
      return '<defs><linearGradient id="ds' + u + '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" class="sgb"/><stop offset="1" class="sbg"/></linearGradient></defs>' +
        '<rect width="400" height="300" fill="url(#ds' + u + ')"/>' +
        '<polygon points="230,0 300,0 210,300 120,300" class="pk" opacity=".18"/>' +
        '<path d="M0 220 L70 150 L130 200 L200 120 L280 196 L340 150 L400 200 V300 H0Z" class="gb" opacity=".7"/>' +
        '<path d="M0 250 L90 196 L170 240 L250 180 L320 232 L400 210 V300 H0Z" class="ink" opacity=".85"/>' +
        '<circle cx="268" cy="70" r="16" class="pk"/>';
    },
    sculpting_3d: function (u) {
      return '<defs><radialGradient id="sg' + u + '" cx=".35" cy=".32" r=".75"><stop offset="0" class="sbg"/><stop offset=".55" class="sgb"/><stop offset="1" class="sink"/></radialGradient></defs>' +
        '<ellipse cx="200" cy="252" rx="92" ry="14" class="ink" opacity=".18"/>' +
        '<circle cx="200" cy="146" r="92" fill="url(#sg' + u + ')"/>' +
        '<g class="ln" stroke-width="1" opacity=".45"><ellipse cx="200" cy="146" rx="92" ry="30"/><ellipse cx="200" cy="146" rx="92" ry="62"/>' +
        '<ellipse cx="200" cy="146" rx="30" ry="92"/><ellipse cx="200" cy="146" rx="62" ry="92"/><path d="M200 54 V238"/></g>' +
        '<circle cx="306" cy="70" r="8" class="pk"/>';
    },
    collage: function () {
      return '<rect x="70" y="60" width="150" height="120" class="gb" transform="rotate(-8 145 120)"/>' +
        '<rect x="170" y="100" width="150" height="130" class="pk" transform="rotate(6 245 165)"/>' +
        '<polygon points="110,170 210,160 230,250 120,262 126,230 104,214" class="ink" opacity=".85"/>' +
        '<circle cx="290" cy="80" r="30" class="bgk ln" stroke-width="2"/>' +
        '<rect x="150" y="84" width="60" height="16" class="gb" opacity=".55" transform="rotate(-20 180 92)"/>' +
        '<rect x="262" y="210" width="56" height="14" class="gb" opacity=".55" transform="rotate(14 290 217)"/>';
    },
    street_art: function () {
      var d = '', r = rnd(11);
      for (var i = 0; i < 90; i++) d += '<circle cx="' + (40 + r() * 320).toFixed(1) + '" cy="' + (60 + r() * 170).toFixed(1) + '" r="' + (0.8 + r() * 2.2).toFixed(1) + '" class="' + (i % 3 ? 'ink' : 'pk') + '" opacity="' + (0.15 + r() * 0.4).toFixed(2) + '"/>';
      return d +
        '<path d="M60 170 C90 80 150 80 160 140 S220 210 250 120 S330 70 344 150" class="st pk" stroke-width="22"/>' +
        '<path d="M160 150 V214 M250 132 V236 M95 140 V190 M330 150 V200" class="st pk" stroke-width="5"/>' +
        '<path d="M52 250 H348" class="st ink" stroke-width="3" opacity=".5"/>';
    },
    hyperrealism: function (u) {
      return '<defs><radialGradient id="hg' + u + '" cx=".36" cy=".3" r=".8"><stop offset="0" class="sbg"/><stop offset=".35" class="sgb"/><stop offset="1" class="sink"/></radialGradient>' +
        '<filter id="hb' + u + '"><feGaussianBlur stdDeviation="6"/></filter></defs>' +
        '<ellipse cx="206" cy="246" rx="86" ry="12" class="ink" opacity=".25" filter="url(#hb' + u + ')"/>' +
        '<circle cx="200" cy="148" r="86" fill="url(#hg' + u + ')"/>' +
        '<ellipse cx="170" cy="112" rx="26" ry="15" class="bgk" opacity=".9" transform="rotate(-30 170 112)"/>' +
        '<path d="M244 196 a60 60 0 0 1 -60 22" class="st bgk" stroke-width="3" opacity=".5"/>';
    },
    calligraphy: function () {
      return '<path d="M96 120 C96 250 304 250 304 120" class="st ink" stroke-width="22"/>' +
        '<rect x="188" y="62" width="24" height="24" class="pk" transform="rotate(45 200 74)"/>' +
        '<g class="pk" opacity=".75"><rect x="44" y="70" width="12" height="12" transform="rotate(45 50 76)"/><rect x="44" y="92" width="12" height="12" transform="rotate(45 50 98)"/>' +
        '<rect x="44" y="114" width="12" height="12" transform="rotate(45 50 120)"/><rect x="44" y="136" width="12" height="12" transform="rotate(45 50 142)"/></g>' +
        '<path d="M44 266 H356" class="st gb" stroke-width="2"/>';
    },
    islamic_geometric: function (u) {
      return '<defs><pattern id="gp' + u + '" width="60" height="60" patternUnits="userSpaceOnUse">' +
        '<g class="ln" stroke-width="1.3" opacity=".55"><rect x="15" y="15" width="30" height="30"/><rect x="15" y="15" width="30" height="30" transform="rotate(45 30 30)"/>' +
        '<path d="M0 0 L15 15 M60 0 L45 15 M0 60 L15 45 M60 60 L45 45"/></g></pattern></defs>' +
        '<rect width="400" height="300" fill="url(#gp' + u + ')"/>' +
        '<g transform="translate(200 150)"><rect x="-58" y="-58" width="116" height="116" class="pk" opacity=".85"/><rect x="-58" y="-58" width="116" height="116" class="pk" opacity=".85" transform="rotate(45)"/>' +
        '<circle r="30" class="bgk"/><rect x="-16" y="-16" width="32" height="32" class="ink" transform="rotate(45)"/></g>';
    }
  };
  function cover(s, fit) {
    var f = ART[s.id];
    uid += 1;
    return '<svg class="ill" viewBox="0 0 400 300" preserveAspectRatio="xMidYMid ' + (fit ? 'meet' : 'slice') + '" role="img" aria-label="' +
      esc(s.en.name + ' · ' + s.ar.name) + '" focusable="false"><rect width="400" height="300" class="bgk"/>' + (f ? f(uid) : '') + '</svg>';
  }

  /* ---------- grid + filter ---------- */
  var grid, filterBar, curCat = 'all';
  function drawGrid() {
    grid.innerHTML = STYLES.map(function (s) {
      return '<li class="sc-item" data-cat="' + esc(s.cat) + '"><button type="button" class="sc" data-id="' + esc(s.id) + '" aria-haspopup="dialog">' +
        '<span class="sc-img">' + cover(s) + '</span>' +
        '<span class="sc-body"><span class="sc-name">' + bi(s.en.name, s.ar.name) + '</span>' +
        '<span class="sc-feel">' + bi(s.en.feel, s.ar.feel) + '</span></span></button></li>';
    }).join('');
  }
  function drawFilters() {
    filterBar.innerHTML = CATS.map(function (c) {
      return '<button type="button" class="pill-f' + (c[0] === curCat ? ' on' : '') + '" data-cat="' + c[0] + '" aria-pressed="' + (c[0] === curCat) + '">' + bi(c[1], c[2]) + '</button>';
    }).join('');
  }
  function setCat(cat) {
    curCat = cat;
    filterBar.querySelectorAll('.pill-f').forEach(function (b) {
      var on = b.dataset.cat === cat; b.classList.toggle('on', on); b.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
    var n = 0;
    grid.querySelectorAll('.sc-item').forEach(function (li) {
      var show = cat === 'all' || li.dataset.cat === cat; li.hidden = !show; if (show) n++;
    });
    var cnt = document.getElementById('sc-count');
    if (cnt) cnt.innerHTML = bi(n === 1 ? '1 style' : n + ' styles', n === 1 ? 'أسلوب واحد' : n + ' أساليب');
  }

  /* ---------- the style window ---------- */
  var dlg, body, lastFocus = null;
  function section(titleEn, titleAr, inner, cls) {
    return '<section class="m-sec' + (cls ? ' ' + cls : '') + '"><h3 class="m-h">' + bi(titleEn, titleAr) + '</h3>' + inner + '</section>';
  }
  function list(items, tag) { return '<' + tag + ' class="m-list">' + items + '</' + tag + '>'; }
  function windowHtml(s) {
    var en = s.en, ar = s.ar, i;
    var story = '';
    for (i = 0; i < en.story.length; i++) story += '<p>' + bi(en.story[i], ar.story[i]) + '</p>';
    var tech = '';
    for (i = 0; i < en.tech.length; i++) {
      tech += '<button type="button" class="tchip">' + bi(en.tech[i][0], ar.tech[i][0]) +
        '<span class="ttip" role="tooltip">' + bi(en.tech[i][1], ar.tech[i][1]) + '</span></button>';
    }
    var signs = '';
    for (i = 0; i < en.signs.length; i++) signs += '<li>' + bi(en.signs[i], ar.signs[i]) + '</li>';
    var artists = '';
    for (i = 0; i < en.artists.length; i++) {
      artists += '<li><span class="a-name">' + bi(en.artists[i][0], ar.artists[i][0]) + '</span><span class="a-meta">' + bi(en.artists[i][1], ar.artists[i][1]) + '</span></li>';
    }
    var guide = '';
    for (i = 0; i < en.guide.length; i++) guide += '<li>' + bi(en.guide[i], ar.guide[i]) + '</li>';
    /* the Gallery link only ever carries a type from the fixed list in art_styles.js */
    var href = s.type ? 'marketplace.php?type=' + encodeURIComponent(s.type) : 'marketplace.php';
    var btn = s.type ? bi('See this style in our Gallery', 'شاهد هذا الأسلوب في معرضنا') : bi('Explore our Gallery', 'استكشف معرضنا');
    return '<div class="m-cover">' + cover(s, true) + '</div>' +
      '<div class="m-in">' +
      '<p class="m-kicker">' + bi(en.feel, ar.feel) + '</p>' +
      '<h2 class="m-title" id="m-title">' + bi(en.name, ar.name) + '</h2>' +
      '<p class="m-essence">' + bi(en.essence, ar.essence) + '</p>' +
      section('The Human Story', 'الحكاية الإنسانية', '<div class="m-story">' + story + '</div>') +
      section('Key Techniques', 'التقنيات الأساسية', '<p class="m-hint">' + bi('Hover or tap a technique to learn more.', 'مرّر أو اضغط على التقنية لتعرف أكثر.') + '</p><div class="tchips">' + tech + '</div><p class="tdesc" aria-live="polite"></p>') +
      section('Signs of a Human Hand', 'علامات اليد البشرية', list(signs, 'ol'), 'm-signs') +
      section('Famous Artists', 'فنانون صنعوا هذا الأسلوب', list(artists, 'ul'), 'm-artists') +
      section('Collector’s Guide: How to recognize authentic ' + en.name + ' work', 'دليل المقتني: كيف تتعرّف على عمل أصيل في ' + ar.name, list(guide, 'ol'), 'm-guide') +
      '<div class="m-cta"><a class="btn btn-primary" href="' + esc(href) + '">' + btn + '</a></div>' +
      '</div>';
  }
  function openStyle(id, push) {
    var s = BY_ID[id];
    if (!s || !dlg) return;
    lastFocus = document.activeElement;
    body.innerHTML = windowHtml(s);
    body.scrollTop = 0;
    if (typeof dlg.showModal === 'function') { if (!dlg.open) dlg.showModal(); } else { dlg.setAttribute('open', ''); }
    document.documentElement.classList.add('m-open');
    if (push !== false && history.replaceState) history.replaceState(null, '', '#' + s.id);
    var closeBtn = dlg.querySelector('.m-close'); if (closeBtn) closeBtn.focus();
  }
  function closeStyle() {
    if (!dlg) return;
    if (dlg.open && typeof dlg.close === 'function') dlg.close(); else dlg.removeAttribute('open');
  }
  function onClosed() {
    document.documentElement.classList.remove('m-open');
    if (history.replaceState && location.hash) history.replaceState(null, '', location.pathname + location.search);
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }

  document.addEventListener('DOMContentLoaded', function () {
    grid = document.getElementById('sc-grid');
    filterBar = document.getElementById('sc-filters');
    dlg = document.getElementById('style-dlg');
    body = document.getElementById('style-body');
    if (!grid || !filterBar) return;
    drawGrid(); drawFilters(); setCat('all');

    filterBar.addEventListener('click', function (e) { var b = e.target.closest('.pill-f'); if (b) setCat(b.dataset.cat); });
    grid.addEventListener('click', function (e) { var b = e.target.closest('.sc'); if (b) openStyle(b.dataset.id); });
    if (dlg) {
      /* a technique explains itself in the line under the chips (tap on phones, hover or keyboard) */
      var showTech = function (e) {
        var c = e.target.closest && e.target.closest('.tchip'); if (!c) return;
        body.querySelectorAll('.tchip.on').forEach(function (x) { x.classList.remove('on'); });
        c.classList.add('on');
        var d = body.querySelector('.tdesc'), t = c.querySelector('.ttip');
        if (d && t) d.innerHTML = t.innerHTML;
      };
      body.addEventListener('click', showTech);
      body.addEventListener('focusin', showTech);
      body.addEventListener('mouseover', showTech);
      dlg.querySelector('.m-close').addEventListener('click', closeStyle);
      dlg.addEventListener('close', onClosed);
      /* click on the dark area outside the window closes it */
      dlg.addEventListener('click', function (e) { if (e.target === dlg) closeStyle(); });
    }
    /* open a style straight from a shared link, e.g. explore.php#calligraphy */
    var h = decodeURIComponent((location.hash || '').slice(1));
    if (h && BY_ID[h]) openStyle(h, false);
  });
})();
