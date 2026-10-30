<?php
/* =====================================================================
 *  events.php — retired. The old events now live as Art Events articles
 *  (articles_list.php?type=event, managed in admin_articles.php).
 *  Old links and bookmarks are sent there for good (301), in the same
 *  language. Tables: none
 * ===================================================================== */
$to = 'articles_list.php?type=event' . (($_GET['lang'] ?? '') === 'ar' ? '&lang=ar' : '');
header('Location: ' . $to, true, 301);
exit;
