<?php
/* =====================================================================
 *  footer.php — the same footer on every public page
 *  Include just before </body>:  <?php include __DIR__ . '/footer.php'; ?>
 *  Social links: add the real account addresses below — an icon only
 *  shows once its address is filled in (no empty or pretend links).
 * ===================================================================== */
$OW_SOCIAL = [
    'instagram' => '',   // e.g. https://www.instagram.com/oweili
    'x'         => '',   // e.g. https://x.com/oweili
    'tiktok'    => '',   // e.g. https://www.tiktok.com/@oweili
];
$owSocialIcons = [
    'instagram' => ['Instagram', '<path d="M12 2.2c3.2 0 3.6 0 4.9.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.86s0 3.6-.07 4.86c-.05 1.17-.25 1.8-.41 2.23a3.7 3.7 0 0 1-.9 1.38c-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.86.07s-3.6 0-4.86-.07c-1.17-.05-1.8-.25-2.23-.41a3.7 3.7 0 0 1-1.38-.9 3.7 3.7 0 0 1-.9-1.38c-.16-.42-.36-1.06-.41-2.23C2.2 15.6 2.2 15.2 2.2 12s0-3.6.07-4.86c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.4 2.2 8.8 2.2 12 2.2zm0 3.13A6.67 6.67 0 1 0 18.67 12 6.67 6.67 0 0 0 12 5.33zm0 11A4.33 4.33 0 1 1 16.33 12 4.33 4.33 0 0 1 12 16.33zm6.93-11.2a1.56 1.56 0 1 1-1.56-1.55 1.56 1.56 0 0 1 1.56 1.55z"/>'],
    'x'         => ['X', '<path d="M18.9 2H22l-7.5 8.6L23 22h-6.9l-5.4-7-6.2 7H1.4l8-9.2L1 2h7l4.9 6.5L18.9 2zm-2.4 18h1.9L7.6 4H5.6l10.9 16z"/>'],
    'tiktok'    => ['TikTok', '<path d="M16.6 2h-3.4v13.6a2.9 2.9 0 1 1-2.9-2.9c.3 0 .6 0 .9.1V9.3a6.3 6.3 0 1 0 5.4 6.3V8.7a7.9 7.9 0 0 0 4.6 1.5V6.8a4.6 4.6 0 0 1-4.6-4.8z"/>'],
];
if (!function_exists('owLogoMark')) {
    function owLogoMark(int $size = 24): void {
        echo '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><g fill="#ff0055">'
           . '<path d="M11 1A10 10 0 0 0 1 11h10z"/><path d="M13 1v10h10A10 10 0 0 0 13 1z"/>'
           . '<path d="M1 13a10 10 0 0 0 10 10V13z"/><path d="M13 13v10a10 10 0 0 0 10-10z"/></g></svg>';
    }
}
$owFt = fn(string $en, string $ar) => '<span class="bi-en" lang="en">' . htmlspecialchars($en, ENT_QUOTES, 'UTF-8') . '</span><span class="bi-ar" lang="ar">' . htmlspecialchars($ar, ENT_QUOTES, 'UTF-8') . '</span>';
?>
<footer class="ow-footer">
  <div class="ow-footer-in">
    <a class="ow-footer-brand" href="index.php" aria-label="Oweili · أويلي"><?php owLogoMark(22); ?>OWEILI</a>
    <nav aria-label="Footer · روابط الموقع">
      <ul class="ow-footer-links">
        <li><a href="marketplace.php"><?= $owFt('Marketplace', 'المعرض') ?></a></li>
        <li><a href="articles_list.php?type=event"><?= $owFt('Events', 'الملتقيات') ?></a></li>
        <li><a href="training.php"><?= $owFt('Institutes', 'المعاهد') ?></a></li>
        <li><a href="support.php"><?= $owFt('Support', 'الدعم') ?></a></li>
      </ul>
    </nav>
    <?php $owSocialSet = array_filter($OW_SOCIAL, fn($u) => preg_match('#^https://#', $u)); if ($owSocialSet): ?>
      <div class="ow-footer-social">
        <?php foreach ($owSocialSet as $k => $url): ?>
          <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" aria-label="<?= $owSocialIcons[$k][0] ?> · Oweili"><svg viewBox="0 0 24 24" aria-hidden="true"><?= $owSocialIcons[$k][1] ?></svg></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="ow-footer-copy"><?= $owFt('© ' . date('Y') . ' Oweili — Real art by real human hands', '© ' . date('Y') . ' أويلي — فن حقيقي بأيدي بشرية حقيقية') ?></div>
</footer>
