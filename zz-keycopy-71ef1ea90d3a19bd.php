<?php
/* TEMPORARY (owner, 28 Nov 2026): makes a second copy of the identity key in a separate private folder outside public_html
   and checks it matches. Never prints the key. Never changes the original. Removed straight after. */
if (!hash_equals('d78abfb425e6ae02367385ba623c44fadfacbcc1c668df99488d8747c13f1eeb', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
$up = dirname(__DIR__);
$orig = $up . '/oweili-private/id-docs/id.key';
$dir = $up . '/oweili-key-backup/';
$copy = $dir . 'id.key';
if (!is_file($orig)) { echo "original not found at oweili-private/id-docs/id.key: nothing done\n"; exit; }
$o = (string) file_get_contents($orig);
if (($_GET['a'] ?? '') === 'copy' && !is_file($copy)) {
    if (!is_dir($dir) && !@mkdir($dir, 0700, true)) { echo "FAILED: could not make the folder\n"; exit; }
    @chmod($dir, 0700);
    if (@file_put_contents($copy, $o, LOCK_EX) !== strlen($o)) { echo "FAILED: could not write the copy\n"; exit; }
    @chmod($copy, 0600);
    echo "copy made\n";
}
$rel = fn($p) => substr($p, strlen($up) + 1);
echo 'original: ', $rel($orig), ' · ', strlen($o), ' bytes · permissions ', substr(sprintf('%o', fileperms($orig)), -4), "\n";
if (!is_file($copy)) { echo "copy: not made yet\n"; exit; }
$c = (string) file_get_contents($copy);
echo 'copy: ', $rel($copy), ' · ', strlen($c), ' bytes · permissions ', substr(sprintf('%o', fileperms($copy)), -4), ' · folder ', substr(sprintf('%o', fileperms($dir)), -4), "\n";
echo 'copy inside public_html: ', strpos($copy, __DIR__ . '/') === 0 ? 'yes' : 'no', "\n";
echo 'copy matches the original: ', hash_equals(hash('sha256', $o), hash('sha256', $c)) ? 'YES' : 'NO', "\n";
