<?php
/* map_coords_lib.php: the "Map location" box shared by Admin → Art Map (admin_art_map.php) and Admin → Institutes
   (admin_institutes.php), owner 15 Nov 2026. Tables: none (the pages save the numbers themselves). */
/* Read the coordinates from a Google Maps link, or take them typed by hand.
   owCoordsFromText() knows the usual link shapes (…!3dLAT!4dLNG, @LAT,LNG, ?q=LAT,LNG, /place/LAT,LNG) and plain "LAT, LNG".
   owResolveMapsLink() opens a short share link (maps.app.goo.gl / goo.gl) on our server, following Google's own addresses only. */
function owCoordNum(string $v): string {
    $v = strtr(trim($v), ['٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9','۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٫'=>'.','−'=>'-',' '=>'']);
    return preg_match('/^-?\d{1,3}(\.\d{1,12})?$/', $v) ? $v : ($v === '' ? '' : 'x');
}
function owCoordsOk($la, $lo): ?array {
    $la = (float) $la; $lo = (float) $lo;
    if ($la < -90 || $la > 90 || $lo < -180 || $lo > 180 || ($la == 0.0 && $lo == 0.0)) return null;
    return [round($la, 6), round($lo, 6)];
}
function owCoordsFromText(string $s): ?array {
    $t = $s; for ($i = 0; $i < 3; $i++) { $d = rawurldecode($t); if ($d === $t) break; $t = $d; }
    $t = html_entity_decode($t, ENT_QUOTES, 'UTF-8');
    $n = '(-?\d{1,3}\.\d+)';
    if (preg_match_all('/!3d' . $n . '!4d' . $n . '/', $t, $m, PREG_SET_ORDER)) { $x = end($m); if ($c = owCoordsOk($x[1], $x[2])) return $c; }
    if (preg_match('/[?&](?:q|query|ll|sll|center|destination|daddr)=\s*\+?' . $n . '\s*,\s*\+?' . $n . '/', $t, $m) && ($c = owCoordsOk($m[1], $m[2]))) return $c;
    if (preg_match('~/(?:place|search|dir)/+\+?' . $n . '\s*,\s*\+?' . $n . '~', $t, $m) && ($c = owCoordsOk($m[1], $m[2]))) return $c;
    if (preg_match('/@' . $n . ',' . $n . '/', $t, $m) && ($c = owCoordsOk($m[1], $m[2]))) return $c;
    $p = strtr($s, ['،' => ',', '٫' => '.']); $p = owCoordNumPair($p);
    return $p;
}
function owCoordNumPair(string $s): ?array {
    $parts = preg_split('/\s*[,;]\s*|\s+/', trim($s));
    if (count($parts) !== 2) return null;
    $a = owCoordNum($parts[0]); $b = owCoordNum($parts[1]);
    return ($a === '' || $a === 'x' || $b === '' || $b === 'x') ? null : owCoordsOk($a, $b);
}
function owResolveMapsLink(string $u): ?array {
    $u = trim($u);
    if (!preg_match('~^https://(?:maps\.app\.goo\.gl|goo\.gl)/[A-Za-z0-9/_\-?=&.%]{3,300}$~', $u) || !function_exists('curl_init')) return null;
    $okHost = fn(string $h) => (bool) preg_match('/^(?:maps\.app\.goo\.gl|goo\.gl|(?:www\.|maps\.|consent\.)?google\.com(?:\.sa)?)$/', strtolower($h));
    for ($hop = 0; $hop < 6; $hop++) {
        if ($c = owCoordsFromText($u)) return $c;
        $pu = parse_url($u);
        if (($pu['scheme'] ?? '') !== 'https' || !$okHost((string) ($pu['host'] ?? ''))) return null;
        $body = '';
        $ch = curl_init($u);
        curl_setopt_array($ch, [CURLOPT_FOLLOWLOCATION => false, CURLOPT_RETURNTRANSFER => false, CURLOPT_TIMEOUT => 8, CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; OweiliAdmin/1.0)', CURLOPT_HTTPHEADER => ['Accept-Language: en'],
            CURLOPT_WRITEFUNCTION => function ($h, $chunk) use (&$body) { $body .= $chunk; return strlen($body) > 800000 ? 0 : strlen($chunk); }]);
        curl_exec($ch);
        $next = (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);
        if ($next !== '') { $u = $next; continue; }
        if ($body !== '' && preg_match('/(?:center=|markers=)(-?\d{1,3}\.\d+)(?:%2C|,)(-?\d{1,3}\.\d+)/', $body, $m) && ($c = owCoordsOk($m[1], $m[2]))) return $c;
        return $body !== '' && preg_match('/!3d(-?\d{1,3}\.\d+)!4d(-?\d{1,3}\.\d+)/', $body, $m) ? owCoordsOk($m[1], $m[2]) : null;
    }
    return null;
}
/* the posted "Map location" form: typed numbers win; otherwise the pasted Google Maps link is read. Returns
   ['c' => [lat, lng] | false (both empty = off the map) | null, 'err' => null | [English, Arabic]] */
function owCoordsFromPost(string $lat, string $lng, string $link): array {
    $la = owCoordNum($lat); $lo = owCoordNum($lng); $link = trim($link);
    if ($la === '' && $lo === '' && $link !== '') {
        $c = owCoordsFromText($link) ?? owResolveMapsLink($link);
        return $c ? ['c' => $c, 'err' => null] : ['c' => null, 'err' => ['We could not read a location from this link. Open the place in Google Maps, copy the link from the address bar (or Share), or type the two numbers.', 'لم نتمكن من قراءة الموقع من هذا الرابط. افتح المكان في خرائط Google وانسخ الرابط من شريط العنوان (أو «مشاركة»)، أو اكتب الرقمين.']];
    }
    if ($la === '' && $lo === '') return ['c' => false, 'err' => null];
    $c = ($la === 'x' || $lo === 'x' || $la === '' || $lo === '') ? null : owCoordsOk($la, $lo);
    return $c ? ['c' => $c, 'err' => null] : ['c' => null, 'err' => ['Please check the two numbers: latitude from -90 to 90, longitude from -180 to 180 (for example 24.647142 and 46.710704)', 'يرجى مراجعة الرقمين: خط العرض من -90 إلى 90، وخط الطول من -180 إلى 180 (مثلا 24.647142 و 46.710704)']];
}
/* the map preview asks the server to open a short Google Maps link (staff pages only, with the security token) */
function owCoordsResolveEndpoint(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || ($_POST['do'] ?? '') !== 'resolve_link') return;
    header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) { echo json_encode(['ok' => false, 'why' => 'expired']); exit; }
    $link = mb_substr(trim((string) ($_POST['link'] ?? '')), 0, 2000);
    $c = owCoordsFromText($link) ?? owResolveMapsLink($link);
    echo json_encode($c ? ['ok' => true, 'lat' => $c[0], 'lng' => $c[1]] : ['ok' => false, 'why' => 'none']); exit;
}
