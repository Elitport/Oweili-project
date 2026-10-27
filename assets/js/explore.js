/* =====================================================================
 *  explore.js — Explore Art Styles (explore.php)
 *  - draws the grid of style cards from art_styles.js (the one data source)
 *  - the filter bar (All + 6 categories, each with its count)
 *  - the style window (a <dialog>) with 10 parts: name, essence, story,
 *    techniques, signs of a human hand, tools, difficulty and time, famous
 *    artists, the collector's guide, and the latest artwork of this type
 *    from the Gallery (window.OW_LIVE, filled by explore.php from the database)
 *  - every text is printed in both languages (bi-en / bi-ar); the page
 *    direction decides which one shows, so the language button needs no redraw
 *  - cover pictures come from art_covers.js (SVG in the brand colors, rule 31),
 *    so they follow light and dark mode. Real artworks can replace them later.
 *  Read only: nothing the visitor types is used. The web address (#id) is
 *  only matched against the known style list.
 * ===================================================================== */
(function () {
  'use strict';
  var STYLES = window.OW_ART_STYLES || [];
  var BY_ID = {};
  STYLES.forEach(function (s) { BY_ID[s.id] = s; });

  var LIVE = window.OW_LIVE || {};
  var CATS = [
    ['all', 'All', 'الكل'],
    ['traditional', 'Traditional', 'تقليدي'],
    ['digital', 'Digital', 'رقمي'],
    ['heritage', 'Islamic & Arab Heritage', 'تراث إسلامي وعربي'],
    ['contemporary', 'Contemporary', 'معاصر'],
    ['nature', 'Nature & Portrait', 'طبيعة وبورتريه'],
    ['photography', 'Photography', 'تصوير']
  ];
  var LEVELS = {
    beginner: ['Beginner', 'مبتدئ', 1],
    intermediate: ['Intermediate', 'متوسط', 2],
    advanced: ['Advanced', 'متقدم', 3]
  };

  function esc(v) {
    return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function bi(en, ar) {
    return '<span class="bi-en" lang="en">' + esc(en) + '</span><span class="bi-ar" lang="ar">' + esc(ar) + '</span>';
  }

  /* ---------- cover pictures: drawn in art_covers.js (shared with the upload form) ---------- */
  function cover(s, fit) {
    return window.OW_ART_COVER ? window.OW_ART_COVER(s.id, s.en.name + ' · ' + s.ar.name, fit) : '';
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
      var n = c[0] === 'all' ? STYLES.length : STYLES.filter(function (s) { return s.cat === c[0]; }).length;
      return '<button type="button" class="pill-f' + (c[0] === curCat ? ' on' : '') + '" data-cat="' + c[0] + '" aria-pressed="' + (c[0] === curCat) + '">' +
        bi(c[1], c[2]) + '<span class="pill-n">' + n + '</span></button>';
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
  /* only show a picture from our own uploads folder (or a full https address) */
  function safeImg(u) { u = String(u || ''); return /^(https:\/\/|\/?uploads\/)[^"'<>\s]*$/.test(u) && u.indexOf('..') === -1 ? u : ''; }
  function liveHtml(s) {
    var en = s.en, ar = s.ar;
    var href = s.type ? 'marketplace.php?type=' + encodeURIComponent(s.type) : 'marketplace.php';
    var btn = s.type ? bi('See ' + en.name + ' in Gallery', 'شاهد ' + ar.name + ' في المعرض') : bi('Explore our Gallery', 'استكشف معرضنا');
    var a = s.type ? LIVE[s.type] : null, img = a ? safeImg(a.image) : '';
    var inner;
    if (a && img) {
      inner = '<a class="live-card" href="' + esc(href) + '"><span class="live-img"><img src="' + esc(img) + '" alt="' + esc(a.title_en + ' · ' + a.title_ar) + '" loading="lazy"></span>' +
        '<span class="live-txt"><span class="live-kick">' + bi('Newest in our Gallery', 'الأحدث في معرضنا') + '</span>' +
        '<span class="live-title">' + bi(a.title_en, a.title_ar) + '</span>' +
        '<span class="live-by">' + bi('by ' + a.artist_en, 'بواسطة ' + a.artist_ar) + '</span></span></a>';
    } else {
      inner = '<div class="live-card live-empty"><span class="live-img">' + cover(s, true) + '</span>' +
        '<span class="live-txt"><span class="live-title">' + bi('No ' + en.name + ' work in our Gallery yet.', 'لا يوجد عمل من ' + ar.name + ' في معرضنا بعد.') + '</span>' +
        '<span class="live-by">' + bi('Are you an artist? Your piece could be the first one here.', 'هل أنت فنان؟ قد يكون عملك أول ما يُعرض هنا.') + '</span>' +
        '<a class="link" href="register_artist.php">' + bi('Join as Artist', 'انضم كفنان') + '</a></span></div>';
    }
    return section('From Our Gallery', 'من معرضنا', inner + '<div class="m-cta"><a class="btn btn-primary" href="' + esc(href) + '">' + btn + '</a></div>', 'm-live');
  }
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
    var tools = '';
    for (i = 0; i < (en.tools || []).length; i++) {
      tools += '<li><span class="t-name">' + bi(en.tools[i][0], ar.tools[i][0]) + '</span><span class="t-use">' + bi(en.tools[i][1], ar.tools[i][1]) + '</span></li>';
    }
    var lv = LEVELS[s.level] || LEVELS.intermediate, dots = '';
    for (i = 1; i <= 3; i++) dots += '<i class="' + (i <= lv[2] ? 'on' : '') + '"></i>';
    var facts = '<div class="facts">' +
      '<div class="fact"><span class="f-k">' + bi('Difficulty', 'مستوى الصعوبة') + '</span><span class="f-v"><span class="lvl" aria-hidden="true">' + dots + '</span>' + bi(lv[0], lv[1]) + '</span></div>' +
      '<div class="fact"><span class="f-k">' + bi('Time for one artwork', 'وقت العمل الواحد') + '</span><span class="f-v">' + bi(en.time, ar.time) + '</span></div>' +
      '<div class="fact fact-wide"><span class="f-k">' + bi('Best for', 'يناسب') + '</span><span class="f-v">' + bi(en.bestFor, ar.bestFor) + '</span></div></div>';
    var artists = '';
    for (i = 0; i < en.artists.length; i++) {
      artists += '<li><span class="a-name">' + bi(en.artists[i][0], ar.artists[i][0]) + '</span><span class="a-meta">' + bi(en.artists[i][1], ar.artists[i][1]) + '</span>' +
        (en.artists[i][2] ? '<span class="a-note">' + bi(en.artists[i][2], ar.artists[i][2]) + '</span>' : '') + '</li>';
    }
    var guide = '';
    for (i = 0; i < en.guide.length; i++) guide += '<li>' + bi(en.guide[i], ar.guide[i]) + '</li>';
    return '<div class="m-cover">' + cover(s, true) + '</div>' +
      '<div class="m-in">' +
      '<p class="m-kicker">' + bi(en.feel, ar.feel) + '</p>' +
      '<h2 class="m-title" id="m-title">' + bi(en.name, ar.name) + '</h2>' +
      '<p class="m-essence">' + bi(en.essence, ar.essence) + '</p>' +
      section('The Human Story', 'الحكاية الإنسانية', '<div class="m-story">' + story + '</div>') +
      section('Key Techniques', 'التقنيات الأساسية', '<p class="m-hint">' + bi('Hover or tap a technique to learn more.', 'مرّر أو اضغط على التقنية لتعرف أكثر.') + '</p><div class="tchips">' + tech + '</div><p class="tdesc" aria-live="polite"></p>') +
      section('Signs of a Human Hand', 'علامات اليد البشرية', list(signs, 'ol'), 'm-signs') +
      section('Tools & Materials', 'الأدوات والخامات', list(tools, 'ul'), 'm-tools') +
      section('Difficulty & Time', 'الصعوبة والوقت', facts, 'm-facts') +
      section('Famous Artists', 'فنانون صنعوا هذا الأسلوب', list(artists, 'ul'), 'm-artists') +
      section('Collector’s Guide: How to recognize authentic ' + en.name + ' work', 'دليل المقتني: كيف تتعرّف على عمل أصيل في ' + ar.name, list(guide, 'ol'), 'm-guide') +
      liveHtml(s) +
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
