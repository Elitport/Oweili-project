<?php
/* artist_workshops.php — a short address for the Workshops page of My Studio
   (artist_studio.php?tab=workshops), used in the workshop emails (owner, 25 Nov 2026) */
$edit = (int) ($_GET['edit'] ?? 0);
header('Location: artist_studio.php?tab=workshops' . ($edit > 0 ? '&edit=' . $edit : ''), true, 302);
exit;
