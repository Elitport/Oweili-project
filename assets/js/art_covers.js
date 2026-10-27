/* =====================================================================
 *  art_covers.js — the drawn picture for each of the 50 art styles
 *  (simple SVG in the brand colors, rule 31; they follow light and dark
 *  mode through the .ill classes in oweili.css).
 *  Shared by explore.php (style cards and window) and upload_artwork.php
 *  (the guided art type picker), so every style looks the same everywhere.
 *  Use: window.OW_ART_COVER(id, label, fit) returns the <svg> as text.
 * ===================================================================== */
(function () {
  'use strict';
  function esc(v) {
    return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
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
    },
    /* ---- added for the 50-style encyclopedia ---- */
    tempera: function () {
      var h = '';
      for (var i = 0; i < 22; i++) h += '<path d="M' + (150 + i * 4.6) + ' ' + (96 + Math.abs(11 - i) * 3) + ' l10 ' + (90 - Math.abs(11 - i) * 5) + '" class="st pk" stroke-width="2" opacity=".75"/>';
      return '<ellipse cx="200" cy="150" rx="66" ry="86" class="gb" opacity=".35"/>' + h +
        '<ellipse cx="200" cy="150" rx="66" ry="86" class="ln" stroke-width="2.5"/>' +
        '<rect x="60" y="40" width="280" height="220" rx="10" class="ln" stroke-width="1.5" opacity=".5"/>';
    },
    fresco: function () {
      return '<polygon points="40,40 190,40 200,140 40,150" class="gb" opacity=".55"/>' +
        '<polygon points="190,40 360,40 360,130 200,140" class="pk" opacity=".45"/>' +
        '<polygon points="40,150 200,140 210,260 40,260" class="pk" opacity=".3"/>' +
        '<polygon points="200,140 360,130 360,260 210,260" class="gb" opacity=".35"/>' +
        '<path d="M40 150 L200 140 L360 130 M190 40 L200 140 L210 260" class="ln" stroke-width="1.2" opacity=".45" stroke-dasharray="4 3"/>' +
        '<path d="M250 60 l14 30 l-8 22 l18 28" class="ln" stroke-width="1.5" opacity=".6"/>' +
        '<circle cx="120" cy="96" r="26" class="bgk" opacity=".7"/>';
    },
    pencil: function (u) {
      var h = '';
      for (var i = 0; i < 18; i++) h += '<path d="M' + (150 + i * 6) + ' 110 L' + (130 + i * 6) + ' 210"/>';
      return '<defs><clipPath id="pc' + u + '"><circle cx="190" cy="160" r="62"/></clipPath></defs>' +
        '<g clip-path="url(#pc' + u + ')" class="ln" stroke-width="1.3" opacity=".7"><g transform="translate(40 0)">' + h + '</g></g>' +
        '<circle cx="190" cy="160" r="62" class="ln" stroke-width="2"/>' +
        '<ellipse cx="200" cy="234" rx="70" ry="8" class="ink" opacity=".12"/>' +
        '<g transform="rotate(-35 300 90)"><rect x="250" y="80" width="110" height="18" class="pk"/><polygon points="250,80 230,89 250,98" class="gb"/><polygon points="236,86 230,89 236,92" class="ink"/></g>';
    },
    encaustic: function (u) {
      return '<defs><filter id="eb' + u + '"><feGaussianBlur stdDeviation="2"/></filter></defs>' +
        '<g filter="url(#eb' + u + ')"><ellipse cx="150" cy="130" rx="90" ry="60" class="pk" opacity=".45"/>' +
        '<ellipse cx="240" cy="160" rx="100" ry="66" class="gb" opacity=".5"/><ellipse cx="200" cy="200" rx="80" ry="40" class="ink" opacity=".25"/></g>' +
        '<path d="M110 150 C150 140 190 160 230 150 S300 140 320 160 M120 180 C170 170 220 190 280 178" class="st bgk" stroke-width="3" opacity=".7"/>' +
        '<path d="M300 190 v30 M318 186 v18" class="st pk" stroke-width="5" opacity=".6"/>';
    },
    chinese_ink: function () {
      return '<path d="M40 230 C80 120 110 110 140 190 C160 140 190 70 230 180 C250 150 270 130 300 200 C320 170 340 160 360 230 Z" class="ink" opacity=".18"/>' +
        '<path d="M60 240 C100 160 130 150 160 210 C190 150 220 120 260 214 C290 190 320 190 350 240 Z" class="ink" opacity=".35"/>' +
        '<path d="M140 260 h120 M170 262 c10 -8 30 -8 40 0" class="st ink" stroke-width="2.5"/>' +
        '<rect x="318" y="50" width="26" height="26" class="pk"/><path d="M324 58 h14 M324 66 h14 M331 56 v14" class="st bgk" stroke-width="2"/>';
    },
    mosaic: function () {
      var s = '';
      for (var x = 0; x < 20; x++) for (var y = 0; y < 15; y++) {
        var cx = 10 + x * 20, cy = 10 + y * 20, d = Math.sqrt(Math.pow(cx - 200, 2) + Math.pow(cy - 150, 2));
        var c = d < 60 ? 'pk' : d < 100 ? 'gb' : (x + y) % 3 ? 'bgk' : 'gb';
        s += '<rect x="' + (x * 20 + 2) + '" y="' + (y * 20 + 2) + '" width="16" height="16" rx="2" class="' + c + '"' + (c === 'gb' && d >= 100 ? ' opacity=".35"' : '') + '/>';
      }
      return '<rect width="400" height="300" class="ink" opacity=".08"/>' + s;
    },
    stained_glass: function () {
      return '<path d="M120 270 V120 A80 80 0 0 1 280 120 V270 Z" class="gb" opacity=".35"/>' +
        '<polygon points="120,120 200,40 200,150" class="pk" opacity=".6"/><polygon points="200,40 280,120 200,150" class="gb" opacity=".7"/>' +
        '<polygon points="120,120 200,150 120,210" class="gb" opacity=".55"/><polygon points="280,120 200,150 280,210" class="pk" opacity=".45"/>' +
        '<polygon points="120,210 200,150 280,210 280,270 120,270" class="pk" opacity=".3"/>' +
        '<g class="ln" stroke-width="5" stroke-linejoin="round"><path d="M120 270 V120 A80 80 0 0 1 280 120 V270 Z"/>' +
        '<path d="M120 120 L200 150 L280 120 M200 40 V150 M120 210 L200 150 L280 210 M200 150 V270"/></g>';
    },
    pixel_art: function () {
      var s = '', sun = [[13,2],[14,2],[12,3],[13,3],[14,3],[15,3],[12,4],[13,4],[14,4],[15,4],[13,5],[14,5]];
      sun.forEach(function (p) { s += '<rect x="' + p[0] * 20 + '" y="' + p[1] * 20 + '" width="20" height="20" class="pk"/>'; });
      var hills = [9, 8, 7, 7, 8, 9, 10, 9, 8, 8, 9, 10, 11, 10, 9, 9, 10, 11, 12, 12];
      hills.forEach(function (h, x) { for (var y = h; y < 15; y++) s += '<rect x="' + x * 20 + '" y="' + y * 20 + '" width="20" height="20" class="' + (y < h + 2 ? 'gb' : 'ink') + '"' + (y >= h + 2 ? ' opacity=".8"' : '') + '/>'; });
      return s;
    },
    motion_graphics: function () {
      var g = '';
      for (var i = 0; i < 6; i++) g += '<circle cx="' + (80 + i * 42) + '" cy="' + (130 - Math.sin(i / 5 * Math.PI) * 50) + '" r="22" class="pk" opacity="' + (0.12 + i * 0.16).toFixed(2) + '"/>';
      return g + '<path d="M60 230 H340" class="st ink" stroke-width="2"/>' +
        '<path d="M80 230 C160 230 240 180 320 180" class="st gb" stroke-width="2" stroke-dasharray="4 4"/>' +
        '<g class="ink"><rect x="74" y="224" width="12" height="12" transform="rotate(45 80 230)"/><rect x="194" y="224" width="12" height="12" transform="rotate(45 200 230)"/><rect x="314" y="224" width="12" height="12" transform="rotate(45 320 230)"/></g>';
    },
    concept_art: function () {
      return '<rect width="400" height="300" class="gb" opacity=".25"/>' +
        '<polygon points="250,0 290,0 230,300 170,300" class="pk" opacity=".15"/>' +
        '<path d="M40 230 V170 h20 v-30 h16 v30 h24 V110 l14 -20 l14 20 V230 Z M180 230 V150 h30 V90 h10 V60 h6 v30 h10 v60 h30 V230 Z M290 230 V160 h26 v-24 h20 v24 h24 V230 Z" class="ink" opacity=".75"/>' +
        '<rect x="0" y="222" width="400" height="78" class="gb" opacity=".6"/>' +
        '<path d="M0 222 L200 140 L400 222" class="ln" stroke-width="1" opacity=".3"/>';
    },
    generative_art: function () {
      var s = '';
      for (var i = 0; i < 26; i++) {
        var y = 40 + i * 9, d = 'M30 ' + y;
        for (var x = 30; x <= 370; x += 10) d += ' L' + x + ' ' + (y + Math.sin(x / 34 + i / 3) * (8 + i * 0.9)).toFixed(1);
        s += '<path d="' + d + '" class="ln" stroke-width="1" opacity="' + (i % 5 === 0 ? '.9' : '.4') + '"/>';
      }
      return s + '<circle cx="300" cy="80" r="14" class="pk"/>';
    },
    nft_art: function () {
      return '<rect x="70" y="50" width="200" height="200" class="ln" stroke-width="3"/>' +
        '<rect x="86" y="66" width="168" height="168" class="gb" opacity=".45"/>' +
        '<circle cx="170" cy="140" r="46" class="pk"/><path d="M86 234 L150 170 L200 214 L254 160 V234 Z" class="ink" opacity=".8"/>' +
        '<polygon points="310,150 340,167 340,201 310,218 280,201 280,167" class="bgk ln" stroke-width="3"/>' +
        '<path d="M296 184 l10 10 l18 -20" class="st pk" stroke-width="4"/>';
    },
    islamic_ornament: function () {
      var leaf = function (x, y, r) { return '<path d="M' + x + ' ' + y + ' q14 -18 28 0 q-14 18 -28 0 Z" class="pk" transform="rotate(' + r + ' ' + x + ' ' + y + ')"/>'; };
      return '<path d="M200 150 C200 90 290 90 290 150 C290 200 230 200 230 160 C230 136 260 136 260 156" class="st ink" stroke-width="3"/>' +
        '<path d="M200 150 C200 90 110 90 110 150 C110 200 170 200 170 160 C170 136 140 136 140 156" class="st ink" stroke-width="3"/>' +
        '<path d="M200 150 C200 210 290 210 300 250 M200 150 C200 210 110 210 100 250" class="st gb" stroke-width="2.5"/>' +
        leaf(262, 104, -20) + leaf(110, 104, 200) + leaf(286, 236, 30) + leaf(84, 236, 150) +
        '<circle cx="200" cy="150" r="10" class="pk"/><circle cx="200" cy="150" r="22" class="ln" stroke-width="1.5"/>';
    },
    manuscript: function () {
      var l = '';
      for (var i = 0; i < 6; i++) l += '<path d="M' + 80 + ' ' + (132 + i * 18) + ' H186 M214 ' + (132 + i * 18) + ' H320" class="st ink" stroke-width="2" opacity=".55"/>';
      return '<path d="M60 70 Q130 60 200 78 Q270 60 340 70 V250 Q270 240 200 258 Q130 240 60 250 Z" class="bgk ln" stroke-width="2"/>' +
        '<path d="M200 78 V258" class="ln" stroke-width="1.5"/>' +
        '<rect x="80" y="90" width="106" height="26" rx="4" class="pk" opacity=".8"/><rect x="214" y="90" width="106" height="26" rx="4" class="gb"/>' +
        '<circle cx="133" cy="103" r="6" class="bgk"/><circle cx="267" cy="103" r="6" class="pk"/>' + l +
        '<circle cx="340" cy="160" r="10" class="pk"/><circle cx="60" cy="160" r="10" class="gb"/>';
    },
    egyptian_linen: function () {
      var w = '';
      for (var x = 60; x <= 340; x += 10) w += '<path d="M' + x + ' 50 V230" class="ln" stroke-width="1" opacity=".3"/>';
      for (var y = 60; y <= 220; y += 10) w += '<path d="M60 ' + y + ' H340" class="ln" stroke-width="1" opacity=".2"/>';
      var f = '';
      for (var k = 64; k <= 336; k += 12) f += '<path d="M' + k + ' 230 v26" class="st ink" stroke-width="2" opacity=".6"/>';
      return '<rect x="60" y="50" width="280" height="180" class="gb" opacity=".22"/>' + w +
        '<rect x="60" y="120" width="280" height="40" class="pk" opacity=".75"/>' +
        '<path d="M70 140 l14 -12 l14 12 l14 -12 l14 12 l14 -12 l14 12 l14 -12 l14 12 l14 -12 l14 12 l14 -12 l14 12 l14 -12 l14 12 l14 -12 l14 12 l14 -12 l14 12" class="st bgk" stroke-width="3"/>' + f;
    },
    textile: function () {
      var t = '', bands = [['ink', 60], ['pk', 110], ['ink', 160], ['pk', 210]];
      bands.forEach(function (b, i) {
        t += '<rect x="40" y="' + b[1] + '" width="320" height="40" class="' + b[0] + '"' + (b[0] === 'ink' ? ' opacity=".85"' : '') + '/>';
        for (var x = 40; x < 360; x += 40) t += '<polygon points="' + (x + 20) + ',' + (b[1] + 6) + ' ' + (x + 34) + ',' + (b[1] + 20) + ' ' + (x + 20) + ',' + (b[1] + 34) + ' ' + (x + 6) + ',' + (b[1] + 20) + '" class="' + (i % 2 ? 'bgk' : 'gb') + '"/>';
      });
      return t + '<path d="M40 60 V250 M360 60 V250" class="st gb" stroke-width="6"/>';
    },
    islamic_mosaic: function () {
      var s = '';
      for (var x = 0; x < 5; x++) for (var y = 0; y < 4; y++) {
        var cx = 40 + x * 80, cy = 38 + y * 76, c = (x + y) % 2 ? 'pk' : 'gb';
        s += '<g transform="translate(' + cx + ' ' + cy + ')"><rect x="-22" y="-22" width="44" height="44" class="' + c + '"/><rect x="-22" y="-22" width="44" height="44" class="' + c + '" transform="rotate(45)"/>' +
          '<rect x="-8" y="-8" width="16" height="16" class="ink" transform="rotate(45)"/></g>';
      }
      return '<rect width="400" height="300" class="ink" opacity=".07"/>' + s;
    },
    abstract: function () {
      return '<path d="M50 220 C120 120 180 260 250 140 S350 90 360 60" class="st ink" stroke-width="26" opacity=".8"/>' +
        '<path d="M60 90 C140 70 200 120 300 100" class="st pk" stroke-width="34" opacity=".75"/>' +
        '<circle cx="290" cy="210" r="44" class="gb"/>' +
        '<circle cx="120" cy="250" r="5" class="pk"/><circle cx="140" cy="262" r="3" class="pk"/><circle cx="330" cy="140" r="4" class="ink"/>';
    },
    pop_art: function (u) {
      return '<defs><pattern id="pd' + u + '" width="14" height="14" patternUnits="userSpaceOnUse"><circle cx="7" cy="7" r="3.5" class="pk"/></pattern></defs>' +
        '<rect width="400" height="300" fill="url(#pd' + u + ')" opacity=".5"/>' +
        '<polygon points="200,40 228,100 296,84 256,138 316,176 246,184 254,254 200,206 146,254 154,184 84,176 144,138 104,84 172,100" class="bgk ln" stroke-width="5"/>' +
        '<circle cx="200" cy="152" r="34" class="gb"/><circle cx="200" cy="152" r="34" class="ln" stroke-width="4"/>';
    },
    minimalism: function () {
      return '<rect x="236" y="110" width="64" height="64" class="pk"/><path d="M70 220 H330" class="st ink" stroke-width="1.5"/>';
    },
    surrealism: function () {
      return '<rect width="400" height="210" class="gb" opacity=".3"/><path d="M0 210 H400" class="ln" stroke-width="1.5"/>' +
        '<rect x="160" y="80" width="80" height="130" class="pk" opacity=".55"/><rect x="160" y="80" width="80" height="130" class="ln" stroke-width="4"/>' +
        '<path d="M175 120 q12 -10 24 0 q12 -10 24 0" class="st bgk" stroke-width="3"/>' +
        '<circle cx="300" cy="70" r="24" class="ink"/><ellipse cx="300" cy="230" rx="24" ry="5" class="ink" opacity=".25"/>' +
        '<path d="M60 250 L120 210 M70 264 L132 222" class="st ink" stroke-width="2" opacity=".5"/>';
    },
    expressionism: function () {
      var s = '';
      for (var i = 0; i < 6; i++) s += '<path d="M' + (40 + i * 10) + ' ' + (60 + i * 26) + ' C120 ' + (20 + i * 30) + ' 200 ' + (120 + i * 22) + ' 360 ' + (50 + i * 30) + '" class="st ' + (i % 2 ? 'pk' : 'gb') + '" stroke-width="' + (10 - i) + '" opacity=".7"/>';
      return s + '<path d="M120 270 L160 160 L200 270 M240 270 L280 140 L320 270" class="st ink" stroke-width="10"/>' +
        '<circle cx="320" cy="70" r="22" class="pk"/>';
    },
    conceptual: function () {
      var chair = function (x) { return '<g transform="translate(' + x + ' 0)" class="ln" stroke-width="3"><path d="M0 130 V210 M44 130 V210 M0 170 H44 M44 170 V90"/></g>'; };
      return chair(70) + '<rect x="156" y="80" width="88" height="140" class="ln" stroke-width="2"/><g transform="translate(178 0)" class="pk">' +
        '<rect x="0" y="168" width="44" height="6"/><rect x="0" y="174" width="5" height="36"/><rect x="39" y="174" width="5" height="36"/><rect x="39" y="100" width="5" height="70"/></g>' +
        '<rect x="278" y="110" width="80" height="60" class="gb" opacity=".35"/><path d="M290 128 H346 M290 142 H340 M290 156 H326" class="st ink" stroke-width="2"/>' +
        '<path d="M40 240 H360" class="ln" stroke-width="1" stroke-dasharray="4 4"/>';
    },
    performance: function (u) {
      return '<defs><linearGradient id="pl' + u + '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" class="sbg"/><stop offset="1" class="sgb"/></linearGradient></defs>' +
        '<polygon points="180,0 220,0 300,240 100,240" class="pk" opacity=".18"/>' +
        '<ellipse cx="200" cy="240" rx="100" ry="20" class="pk" opacity=".3"/>' +
        '<path d="M150 200 C170 140 230 140 250 200 M170 180 C190 120 250 120 262 160" class="st ink" stroke-width="3" opacity=".7"/>' +
        '<rect x="0" y="260" width="400" height="40" fill="url(#pl' + u + ')"/>';
    },
    landscape: function () {
      return '<circle cx="290" cy="90" r="30" class="pk" opacity=".85"/>' +
        '<path d="M0 200 C80 170 140 190 210 168 S330 150 400 176 V300 H0Z" class="gb"/>' +
        '<path d="M0 230 C110 206 190 236 280 214 S360 208 400 220 V300 H0Z" class="pk" opacity=".45"/>' +
        '<path d="M0 262 H400 V300 H0Z" class="ink" opacity=".75"/><path d="M40 274 h40 M140 282 h60 M260 276 h50" class="st bgk" stroke-width="2"/>';
    },
    portrait: function () {
      return '<path d="M232 60 C180 50 150 90 154 130 C156 146 146 156 138 168 C134 174 142 178 150 178 C148 190 152 196 158 198 C154 206 160 214 170 212 C178 230 200 236 220 232 C240 228 250 248 252 270" class="st ink" stroke-width="3.5"/>' +
        '<path d="M232 60 C280 66 300 110 292 160 C286 200 262 220 252 270" class="st gb" stroke-width="2" opacity=".6"/>' +
        '<circle cx="200" cy="120" r="5" class="ink"/><circle cx="300" cy="80" r="14" class="pk"/>';
    },
    botanical: function () {
      return '<path d="M200 280 C200 220 196 160 210 90" class="st ink" stroke-width="3"/>' +
        '<path d="M200 200 C150 190 120 150 130 120 C170 130 200 160 200 200 Z" class="gb"/><path d="M200 200 C170 176 150 150 132 124" class="st bgk" stroke-width="2"/>' +
        '<path d="M202 170 C250 156 280 120 270 92 C230 104 204 132 202 170 Z" class="gb" opacity=".8"/>' +
        '<g transform="translate(212 80)"><circle r="12" class="ink"/><ellipse cx="0" cy="-26" rx="10" ry="18" class="pk"/><ellipse cx="0" cy="-26" rx="10" ry="18" class="pk" transform="rotate(72)"/>' +
        '<ellipse cx="0" cy="-26" rx="10" ry="18" class="pk" transform="rotate(144)"/><ellipse cx="0" cy="-26" rx="10" ry="18" class="pk" transform="rotate(216)"/><ellipse cx="0" cy="-26" rx="10" ry="18" class="pk" transform="rotate(288)"/><circle r="9" class="ink"/></g>';
    },
    animal: function () {
      return '<path d="M150 270 C150 220 160 190 176 168 C160 160 130 150 118 130 C110 118 118 104 132 108 C150 112 170 112 186 98 C200 80 214 60 236 54 C240 44 250 40 256 46 C262 56 258 66 252 72 C272 90 286 120 282 160 C278 200 262 236 262 270 Z" class="ink" opacity=".85"/>' +
        '<path d="M236 54 C230 80 214 96 196 108" class="st bgk" stroke-width="2.5"/><circle cx="152" cy="118" r="4" class="bgk"/>' +
        '<path d="M60 270 H340" class="st gb" stroke-width="3"/><circle cx="320" cy="70" r="16" class="pk"/>';
    },
    marine: function () {
      return '<circle cx="90" cy="80" r="26" class="pk" opacity=".85"/>' +
        '<path d="M120 200 L290 200 L270 226 L140 226 Z" class="ink"/><path d="M205 200 V70" class="st ink" stroke-width="4"/>' +
        '<path d="M205 76 L290 190 L150 190 Z" class="gb"/><path d="M205 76 L290 190" class="st ink" stroke-width="2"/>' +
        '<path d="M0 236 q25 -12 50 0 t50 0 t50 0 t50 0 t50 0 t50 0 t50 0 t50 0 M0 260 q25 -12 50 0 t50 0 t50 0 t50 0 t50 0 t50 0 t50 0 t50 0" class="st gb" stroke-width="3"/>';
    },
    architectural: function (u) {
      return '<defs><pattern id="ag' + u + '" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M20 0 V20 H0" class="ln" stroke-width=".6" opacity=".25"/></pattern></defs>' +
        '<rect width="400" height="300" fill="url(#ag' + u + ')"/>' +
        '<path d="M20 120 L200 80 L380 120 M20 250 L200 270 L380 250" class="ln" stroke-width="1" opacity=".35" stroke-dasharray="3 3"/>' +
        '<polygon points="110,100 200,84 200,262 110,246" class="gb" opacity=".45"/><polygon points="200,84 300,102 300,246 200,262" class="pk" opacity=".35"/>' +
        '<g class="ln" stroke-width="2.5"><polygon points="110,100 200,84 300,102 300,246 200,262 110,246"/><path d="M200 84 V262"/>' +
        '<path d="M140 150 v40 a14 14 0 0 1 28 0 v-40 M230 150 v40 a14 14 0 0 0 28 0 v-40"/></g>';
    },
    fine_art_photo: function () {
      var b = '';
      for (var i = 0; i < 6; i++) b += '<path d="M200 150 L' + (200 + Math.cos(i * Math.PI / 3) * 62).toFixed(1) + ' ' + (150 + Math.sin(i * Math.PI / 3) * 62).toFixed(1) + '" class="st bgk" stroke-width="3" transform="rotate(20 200 150)"/>';
      return '<rect x="70" y="40" width="260" height="220" class="ln" stroke-width="2" opacity=".6"/>' +
        '<circle cx="200" cy="150" r="72" class="ink"/><polygon points="200,112 233,131 233,169 200,188 167,169 167,131" class="gb"/>' + b +
        '<circle cx="200" cy="150" r="14" class="pk"/>';
    },
    bw_photo: function () {
      var h = '';
      for (var x = 40; x < 380; x += 26) h += '<rect x="' + x + '" y="64" width="12" height="10" rx="2" class="bgk"/><rect x="' + x + '" y="226" width="12" height="10" rx="2" class="bgk"/>';
      var f = '', shades = ['.15', '.35', '.6', '.85'];
      for (var k = 0; k < 4; k++) f += '<rect x="' + (52 + k * 76) + '" y="90" width="68" height="120" class="ink" opacity="' + shades[k] + '"/>';
      return '<rect x="30" y="56" width="340" height="188" class="ink"/>' + h + '<rect x="40" y="84" width="320" height="132" class="bgk"/>' + f +
        '<circle cx="125" cy="140" r="16" class="bgk" opacity=".8"/>';
    },
    artistic_photo: function (u) {
      return '<defs><filter id="lg' + u + '" x="-20%" y="-20%" width="140%" height="140%"><feGaussianBlur stdDeviation="3"/></filter></defs>' +
        '<rect width="400" height="300" class="ink" opacity=".85"/>' +
        '<g filter="url(#lg' + u + ')"><path d="M40 220 C120 60 200 260 280 110 S360 160 380 70" class="st pk" stroke-width="7"/>' +
        '<path d="M30 160 C110 250 200 40 300 200" class="st gb" stroke-width="5"/></g>' +
        '<path d="M40 220 C120 60 200 260 280 110 S360 160 380 70" class="st bgk" stroke-width="1.5" opacity=".8"/>' +
        '<circle cx="80" cy="60" r="2" class="bgk"/><circle cx="330" cy="40" r="1.5" class="bgk"/><circle cx="250" cy="70" r="1.5" class="bgk"/>';
    },
    photo_manipulation: function () {
      return '<rect x="60" y="80" width="200" height="150" class="gb" opacity=".45"/><rect x="60" y="80" width="200" height="150" class="ln" stroke-width="1.5" opacity=".5"/>' +
        '<rect x="100" y="60" width="200" height="150" class="bgk"/><path d="M100 210 L160 140 L200 180 L240 130 L300 210 Z" class="ink" opacity=".8"/>' +
        '<circle cx="250" cy="96" r="18" class="pk"/><rect x="100" y="60" width="200" height="150" class="ln" stroke-width="2"/>' +
        '<rect x="140" y="40" width="200" height="150" class="ln" stroke-width="1.5" stroke-dasharray="6 4" opacity=".6"/>' +
        '<path d="M300 230 l30 30 M320 230 h14 v14" class="st pk" stroke-width="3"/>';
    }

  };
  /* id: a style id from art_styles.js; label: the picture's text for screen
     readers; fit: true shows the whole picture, false fills the frame */
  window.OW_ART_COVER = function (id, label, fit) {
    var f = Object.prototype.hasOwnProperty.call(ART, id) ? ART[id] : null;
    uid += 1;
    return '<svg class="ill" viewBox="0 0 400 300" preserveAspectRatio="xMidYMid ' + (fit ? 'meet' : 'slice') + '" role="img" aria-label="' +
      esc(label) + '" focusable="false"><rect width="400" height="300" class="bgk"/>' + (f ? f(uid) : '') + '</svg>';
  };
})();
