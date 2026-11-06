<?php
/* =====================================================================
 *  art_categories.php — the Gallery filter: "All" and the 9 topics
 *  (owner, 6 Nov 2026: Oil, Acrylic, Watercolor, Charcoal and pencil, Arabic
 *  calligraphy, Islamic ornament, Abstract, Nature and landscapes, Saudi
 *  heritage). The one list lives in owArtTopics() (artwork_lib.php), also used
 *  by the upload form. Digital and Photography are gone: Oweili is handmade only.
 *  Include where needed:  $ART_CATS = require __DIR__ . '/art_categories.php';
 *  "all" has no list: it shows every type.
 * ===================================================================== */
require_once __DIR__ . '/artwork_lib.php';
$owOut = ['all' => ['en' => 'All', 'ar' => 'الكل', 'types' => []]];
foreach (owArtTopics() as $k => $t) $owOut[$k] = ['en' => $t['en'], 'ar' => $t['ar'], 'types' => $t['types']];
return $owOut;
