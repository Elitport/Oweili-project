<?php
/* TEMPORARY read-only check (remove after use). Switches itself off at 2026-10-30 21:32 UTC.
   Shows only folder and file names, sizes and dates, and the deploy log.
   No passwords, no database, no personal data. Changes nothing. */
if (time() > 1793395974) { http_response_code(404); exit; }
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
$root = __DIR__;
$up   = $root . '/uploads';
$show = function (string $dir, int $max = 40) {
    if (!is_dir($dir)) { echo "   (folder does not exist)\n"; return; }
    $items = @scandir($dir) ?: [];
    $n = 0;
    foreach ($items as $i) {
        if ($i === '.' || $i === '..') continue;
        if (++$n > $max) { echo "   ... more\n"; break; }
        $p = $dir . '/' . $i;
        printf("   %-50s %s %10s  %s\n", $i, is_link($p) ? 'link' : (is_dir($p) ? 'dir ' : 'file'), is_file($p) ? filesize($p) : '', date('Y-m-d H:i', (int) @filemtime($p)));
    }
    if ($n === 0) echo "   (empty)\n";
};
echo "Website folder name: ", basename($root), "\n";
echo "Parent folder name:  ", basename(dirname($root)), "\n\n";
echo "uploads/ exists: ", is_dir($up) ? 'yes' : 'NO', is_link($up) ? ' (it is a link)' : '', "\n";
foreach (['91d10c567c8e0c73ae89babc699c21f5.jpg', 'b970363c1992c5dd1a2706dd155367f6.webp'] as $f) {
    echo "  uploads/artworks/$f: ", is_file($up . '/artworks/' . $f) ? 'FOUND' : 'missing', "\n";
}
echo "\nInside uploads/:\n";          $show($up);
echo "\nInside uploads/artworks/:\n"; $show($up . '/artworks');
echo "\nFolders next to the website folder:\n"; $show(dirname($root));
foreach (glob(dirname($root) . '/*/uploads/artworks', GLOB_ONLYDIR) ?: [] as $other) {
    echo "\nAnother uploads/artworks found in: ", basename(dirname(dirname($other))), "\n"; $show($other, 10);
}
echo "\nGit deploy record (newest last):\n";
$log = @file($root . '/.git/logs/HEAD', FILE_IGNORE_NEW_LINES) ?: [];
if (!$log) echo "   (no deploy record found)\n";
foreach (array_slice($log, -25) as $line) {
    if (preg_match('/^(\w{40}) (\w{40}) .*? (\d{9,11}) [+-]\d{4}\t(.*)$/', $line, $m)) {
        printf("   %s  %s -> %s  %s\n", gmdate('Y-m-d H:i', (int) $m[3]), substr($m[1], 0, 7), substr($m[2], 0, 7), $m[4]);
    }
}
echo "\n.git folder created: ", is_dir($root . '/.git') ? date('Y-m-d H:i', (int) filemtime($root . '/.git/config')) : 'no .git folder', "\n";

echo "\nconfig.php: ", is_link($root . '/config.php') ? 'is a link to ' . str_replace(dirname($root), '[site]', (string) readlink($root . '/config.php')) : (is_file($root . '/config.php') ? 'normal file, dated ' . date('Y-m-d H:i', (int) filemtime($root . '/config.php')) : 'missing'), "\n";
echo ".user.ini: ", is_file($root . '/.user.ini') ? 'present' : 'missing', "\n";
echo "\nInside .builds (names only, 3 levels):\n";
$walk = function (string $dir, int $depth, string $pad) use (&$walk) {
    foreach (array_slice(array_diff(@scandir($dir) ?: [], ['.', '..']), 0, 25) as $i) {
        $p = $dir . '/' . $i;
        printf("%s%s%s  %s\n", $pad, $i, is_dir($p) ? '/' : '', date('Y-m-d H:i', (int) @filemtime($p)));
        if (is_dir($p) && !is_link($p) && $depth > 1 && $i !== '.git' && $i !== 'node_modules') $walk($p, $depth - 1, $pad . '   ');
    }
};
$walk(dirname($root) . '/.builds', 3, '   ');

/* Deploy survival test: one tiny file in uploads/ that only holds the time it was made */
$mdir = $up . '/deploy_test';
$mark = $mdir . '/marker.txt';
if (!is_file($mark)) { @mkdir($mdir, 0755, true); @file_put_contents($mark, 'made ' . gmdate('Y-m-d H:i:s') . " UTC\n"); $new = true; }
echo "\nDeploy survival test file: ", is_file($mark) ? trim((string) file_get_contents($mark)) . (!empty($new) ? ' (just made now)' : ' (already there from before this deploy)') : 'could not be made', "\n";
