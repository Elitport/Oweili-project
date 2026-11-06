<?php
/* chat.php — the Artist Chat Room was removed. Old links and bookmarks
   are sent to the Community page (permanent redirect) while it is shown;
   while Community is hidden (6 Nov 2026) they go to the Gallery. */
require_once 'config.php';
require_once __DIR__ . '/gallery_lib.php';
$to = 'marketplace.php';
try { if (owCommunityPageOn()) $to = 'community.php'; } catch (Throwable $e) {}
header('Location: ' . $to, true, $to === 'community.php' ? 301 : 302);
exit;
