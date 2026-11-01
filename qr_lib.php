<?php
/* =====================================================================
 *  qr_lib.php — a small QR code maker, written for Oweili (no outside
 *  service, no library). Used for the QR code on each Certificate of
 *  Authenticity, which opens the public verification page.
 *    owQrMatrix($text)  → square grid of true (dark) / false (light)
 *    owQrSvg($text, $px) → the same grid as a crisp SVG picture
 *  Standard QR code (ISO/IEC 18004): byte mode, error level M (about 15%
 *  of the code can be damaged and it still reads), versions 1 to 6, i.e.
 *  up to 106 characters, which is plenty for a verification link.
 *  Tables: none
 * ===================================================================== */

/* Error level M, versions 1–6: [data codewords per block, number of blocks, EC codewords per block] */
const OW_QR_BLOCKS = [1 => [16, 1, 10], 2 => [28, 1, 16], 3 => [44, 1, 26], 4 => [32, 2, 18], 5 => [43, 2, 24], 6 => [27, 4, 16]];
const OW_QR_ALIGN  = [1 => [], 2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30], 6 => [6, 34]];

function owQrGfMul(int $x, int $y): int {
    $z = 0;
    for ($i = 7; $i >= 0; $i--) {
        $z = ($z << 1) ^ (($z >> 7) * 0x11D);
        $z ^= (($y >> $i) & 1) * $x;
    }
    return $z & 0xFF;
}

/* Reed–Solomon: the error-correction codewords for one block */
function owQrEcc(array $data, int $degree): array {
    $div = array_fill(0, $degree, 0);
    $div[$degree - 1] = 1;
    $root = 1;
    for ($i = 0; $i < $degree; $i++) {
        for ($j = 0; $j < $degree; $j++) {
            $div[$j] = owQrGfMul($div[$j], $root);
            if ($j + 1 < $degree) $div[$j] ^= $div[$j + 1];
        }
        $root = owQrGfMul($root, 2);
    }
    $res = array_fill(0, $degree, 0);
    foreach ($data as $b) {
        $f = $b ^ array_shift($res);
        $res[] = 0;
        for ($i = 0; $i < $degree; $i++) $res[$i] ^= owQrGfMul($div[$i], $f);
    }
    return $res;
}

/* The 15 format bits (error level M = 00 + mask), with their check bits */
function owQrFormatBits(int $mask): int {
    $data = (0 << 3) | $mask;          // M = 00
    $rem = $data;
    for ($i = 0; $i < 10; $i++) $rem = ($rem << 1) ^ (($rem >> 9) * 0x537);
    return (($data << 10) | ($rem & 0x3FF)) ^ 0x5412;
}

function owQrMaskOn(int $mask, int $x, int $y): bool {
    switch ($mask) {
        case 0: return ($x + $y) % 2 === 0;
        case 1: return $y % 2 === 0;
        case 2: return $x % 3 === 0;
        case 3: return ($x + $y) % 3 === 0;
        case 4: return (intdiv($x, 3) + intdiv($y, 2)) % 2 === 0;
        case 5: return ($x * $y) % 2 + ($x * $y) % 3 === 0;
        case 6: return (($x * $y) % 2 + ($x * $y) % 3) % 2 === 0;
        default: return (($x + $y) % 2 + ($x * $y) % 3) % 2 === 0;
    }
}

/* All the codewords (data + error correction, interleaved) for $text */
function owQrCodewords(string $text, int &$version): array {
    $len = strlen($text);
    $version = 0;
    foreach (OW_QR_BLOCKS as $v => [$d, $n]) {
        if ($len <= intdiv($d * $n * 8 - 12, 8)) { $version = $v; break; }
    }
    if ($version === 0) throw new InvalidArgumentException('QR text too long');
    [$dPer, $nBlocks, $ecPer] = OW_QR_BLOCKS[$version];
    $cap = $dPer * $nBlocks * 8;
    $bits = '0100' . str_pad(decbin($len), 8, '0', STR_PAD_LEFT);
    for ($i = 0; $i < $len; $i++) $bits .= str_pad(decbin(ord($text[$i])), 8, '0', STR_PAD_LEFT);
    $bits .= str_repeat('0', min(4, $cap - strlen($bits)));
    if (strlen($bits) % 8) $bits .= str_repeat('0', 8 - strlen($bits) % 8);
    $data = array_map('bindec', str_split($bits, 8));
    for ($pad = 0xEC; count($data) < $dPer * $nBlocks; $pad ^= 0xEC ^ 0x11) $data[] = $pad;
    $blocks = array_chunk($data, $dPer);
    $ecs = array_map(fn($b) => owQrEcc($b, $ecPer), $blocks);
    $out = [];
    for ($i = 0; $i < $dPer; $i++) foreach ($blocks as $b) $out[] = $b[$i];
    for ($i = 0; $i < $ecPer; $i++) foreach ($ecs as $e) $out[] = $e[$i];
    return $out;
}

/* Penalty score of one finished grid (lower reads more easily) */
function owQrPenalty(array $m): int {
    $n = count($m); $p = 0;
    for ($pass = 0; $pass < 2; $pass++) {
        for ($a = 0; $a < $n; $a++) {
            $run = 1; $line = '';
            for ($b = 0; $b < $n; $b++) {
                $c = $pass ? $m[$b][$a] : $m[$a][$b];
                $line .= $c ? '1' : '0';
                if ($b > 0) {
                    $prev = $pass ? $m[$b - 1][$a] : $m[$a][$b - 1];
                    if ($c === $prev) { $run++; if ($run === 5) $p += 3; elseif ($run > 5) $p++; } else $run = 1;
                }
            }
            $p += 40 * (substr_count($line, '10111010000') + substr_count($line, '00001011101'));
        }
    }
    $dark = 0;
    for ($y = 0; $y < $n; $y++) for ($x = 0; $x < $n; $x++) {
        if ($m[$y][$x]) $dark++;
        if ($x < $n - 1 && $y < $n - 1 && $m[$y][$x] === $m[$y][$x + 1] && $m[$y][$x] === $m[$y + 1][$x] && $m[$y][$x] === $m[$y + 1][$x + 1]) $p += 3;
    }
    $p += intdiv(abs($dark * 20 - $n * $n * 10), $n * $n) * 10;
    return $p;
}

/* The finished grid: $grid[row][column] = true for a dark square */
function owQrMatrix(string $text): array {
    $version = 0;
    $cw = owQrCodewords($text, $version);
    $n = 17 + 4 * $version;
    $mod = array_fill(0, $n, array_fill(0, $n, false));
    $fn  = array_fill(0, $n, array_fill(0, $n, false));
    $set = function (int $x, int $y, bool $dark) use (&$mod, &$fn) { $mod[$y][$x] = $dark; $fn[$y][$x] = true; };
    for ($i = 0; $i < $n; $i++) { $set(6, $i, $i % 2 === 0); $set($i, 6, $i % 2 === 0); }      // timing lines
    foreach ([[3, 3], [$n - 4, 3], [3, $n - 4]] as [$cx, $cy]) {                              // the 3 corner squares
        for ($dy = -4; $dy <= 4; $dy++) for ($dx = -4; $dx <= 4; $dx++) {
            $x = $cx + $dx; $y = $cy + $dy;
            if ($x >= 0 && $x < $n && $y >= 0 && $y < $n) { $d = max(abs($dx), abs($dy)); $set($x, $y, $d !== 2 && $d !== 4); }
        }
    }
    $al = OW_QR_ALIGN[$version]; $last = count($al) - 1;                                       // small alignment squares
    foreach ($al as $i => $ay) foreach ($al as $j => $ax) {
        if (($i === 0 && $j === 0) || ($i === 0 && $j === $last) || ($i === $last && $j === 0)) continue;
        for ($dy = -2; $dy <= 2; $dy++) for ($dx = -2; $dx <= 2; $dx++) $set($ax + $dx, $ay + $dy, max(abs($dx), abs($dy)) !== 1);
    }
    $drawFormat = function (int $mask) use ($set, $n) {
        $b = owQrFormatBits($mask);
        $bit = fn(int $i) => (($b >> $i) & 1) === 1;
        for ($i = 0; $i <= 5; $i++) $set(8, $i, $bit($i));
        $set(8, 7, $bit(6)); $set(8, 8, $bit(7)); $set(7, 8, $bit(8));
        for ($i = 9; $i < 15; $i++) $set(14 - $i, 8, $bit($i));
        for ($i = 0; $i < 8; $i++) $set($n - 1 - $i, 8, $bit($i));
        for ($i = 8; $i < 15; $i++) $set(8, $n - 15 + $i, $bit($i));
        $set(8, $n - 8, true);                                                               // the fixed dark square
    };
    $drawFormat(0);                                                                          // reserves the format places
    $total = count($cw) * 8; $i = 0;                                                         // the data, in the zigzag order
    for ($right = $n - 1; $right >= 1; $right -= 2) {
        if ($right === 6) $right = 5;
        for ($vert = 0; $vert < $n; $vert++) for ($j = 0; $j < 2; $j++) {
            $x = $right - $j;
            $y = (($right + 1) & 2) === 0 ? $n - 1 - $vert : $vert;
            if (!$fn[$y][$x] && $i < $total) { $mod[$y][$x] = (($cw[$i >> 3] >> (7 - ($i & 7))) & 1) === 1; $i++; }
        }
    }
    $best = null; $bestScore = PHP_INT_MAX;                                                  // pick the clearest of the 8 masks
    for ($mask = 0; $mask < 8; $mask++) {
        $try = $mod;
        for ($y = 0; $y < $n; $y++) for ($x = 0; $x < $n; $x++) if (!$fn[$y][$x] && owQrMaskOn($mask, $x, $y)) $try[$y][$x] = !$try[$y][$x];
        $keep = $mod; $mod = $try; $drawFormat($mask); $try = $mod; $mod = $keep;
        $score = owQrPenalty($try);
        if ($score < $bestScore) { $bestScore = $score; $best = $try; }
    }
    return $best;
}

/* The QR code as an SVG picture (black on white, with the 4-square quiet edge) */
function owQrSvg(string $text, int $px = 160, string $label = 'QR code'): string {
    $m = owQrMatrix($text);
    $n = count($m); $q = 4; $s = $n + 2 * $q;
    $d = '';
    foreach ($m as $y => $row) foreach ($row as $x => $dark) if ($dark) $d .= 'M' . ($x + $q) . ' ' . ($y + $q) . 'h1v1h-1z';
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $s . ' ' . $s . '" width="' . $px . '" height="' . $px . '" role="img" aria-label="'
         . htmlspecialchars($label, ENT_QUOTES) . '" shape-rendering="crispEdges"><rect width="' . $s . '" height="' . $s . '" fill="#fff"/><path fill="#000" d="' . $d . '"/></svg>';
}
