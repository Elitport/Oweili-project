<?php
/* =====================================================================
 *  lang_lib.php — the page language is decided ON THE SERVER (5 Nov 2026)
 *
 *  Before, every page was sent in English and the browser switched it to
 *  Arabic a moment later (a flash of English). Now the server reads the
 *  visitor's saved choice before sending anything:
 *    1. ?lang=ar / ?lang=en in the address (email links, shared links) —
 *       this also becomes the saved choice
 *    2. the cookie "ow_lang" (ar / en), written by the language button
 *    3. otherwise English
 *  and sends the page in that language: <html lang dir>, Arabic title, the
 *  menu words and every data-en / data-ar text already in Arabic.
 *
 *    owLang()                 'ar' or 'en'
 *    owIsAr()                 true for Arabic
 *    owHtmlAttrs($jsText)     prints lang="…" dir="…" inside <html …>; for
 *                             Arabic it also turns on the server translation
 *                             of this page (owLangFilter). $jsText = true on
 *                             the older pages whose words are filled in by
 *                             their own script: an Arabic visitor sees the
 *                             page only once that script has run (a few
 *                             hundredths of a second), never English first
 *    owLangUrl($url, $lang)   adds lang=ar to a link (emails)
 *  The browser side (cookie, Back button, early loading) is in theme_head.php.
 * ===================================================================== */

/* The menu words that carry an id instead of data-en / data-ar (nav.php) */
if (!defined('OW_LANG_IDS')) define('OW_LANG_IDS', [
    'nav-community-label' => 'المجتمع', 'nav-marketplace-label' => 'المعرض',
    'nav-explore-label' => 'استكشف أساليب الرسم', 'nav-support-label' => 'الدعم',
    'dd-sp1' => 'تواصل معنا', 'dd-sp2' => 'كيف يعمل الموقع', 'dd-sp3' => 'شروط الاستخدام',
    'n-login-t' => 'تسجيل الدخول', 'n-reg-t' => 'انضم مجاناً', 'lb' => 'English',
]);

if (!function_exists('owLang')) {

function owLang(): string {
    static $l = null;
    if ($l !== null) return $l;
    $q = $_GET['lang'] ?? null;
    if ($q === 'ar' || $q === 'en') { $l = $q; owLangRemember($l); return $l; }
    $c = $_COOKIE['ow_lang'] ?? '';
    $l = ($c === 'ar' || $c === 'en') ? $c : 'en';
    return $l;
}

function owIsAr(): bool { return owLang() === 'ar'; }

/* Saves the choice for a year (only this site, sent with every page) */
function owLangRemember(string $l): void {
    if (($_COOKIE['ow_lang'] ?? '') === $l) return;
    $_COOKIE['ow_lang'] = $l;
    if (headers_sent()) return;
    setcookie('ow_lang', $l, ['expires' => time() + 31536000, 'path' => '/',
        'secure' => (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off'), 'samesite' => 'Lax']);
}

function owLangUrl(string $url, ?string $lang = null): string {
    $lang = $lang ?? owLang();
    if ($lang !== 'ar' || preg_match('/[?&]lang=/', $url)) return $url;
    $hash = '';
    if (($p = strpos($url, '#')) !== false) { $hash = substr($url, $p); $url = substr($url, 0, $p); }
    return $url . (strpos($url, '?') === false ? '?' : '&') . 'lang=ar' . $hash;
}

function owHtmlAttrs(bool $jsText = false): void {
    $ar = owIsAr();
    echo 'lang="' . ($ar ? 'ar' : 'en') . '" dir="' . ($ar ? 'rtl' : 'ltr') . '"';
    if ($ar && $jsText) echo ' class="ow-wait"';
    if (!$ar || !empty($GLOBALS['OW_LANG_FILTER'])) return;
    $GLOBALS['OW_LANG_FILTER'] = ob_get_level() + 1;
    ob_start('owLangFilter');
    /* shut the buffer before any "after the page" work (theme_head.php) ends the request */
    register_shutdown_function(function () {
        $lvl = (int) ($GLOBALS['OW_LANG_FILTER'] ?? 0);
        while ($lvl > 0 && ob_get_level() >= $lvl) ob_end_flush();
    });
}


/* Arabic pages only: the page is translated before it leaves the server */
function owLangFilter(string $html): string {
    if ($html === '') return $html;
    // the title: the Arabic one the page already carries (data-title-ar on <html>)
    if (preg_match('/^[^>]*\bdata-title-ar="([^"]*)"/', $html, $m) && $m[1] !== '') {
        $html = preg_replace('#<title>[^<]*</title>#', '<title>' . str_replace(['\\', '$'], ['\\\\', '\\$'], $m[1]) . '</title>', $html, 1);
    }
    // titles written in both languages ("Gifts · هدايا · Oweili"): only the Arabic parts stay
    elseif (preg_match('#<title>([^<]*)</title>#u', $html, $t) && preg_match('/\p{Arabic}/u', $t[1]) && preg_match('/[A-Za-z]{3,}/', $t[1])) {
        $parts = preg_split('/\s+[·|]\s+/u', html_entity_decode($t[1], ENT_QUOTES, 'UTF-8'));
        $arParts = array_values(array_filter($parts, fn($x) => preg_match('/\p{Arabic}/u', $x) && !preg_match('/[A-Za-z]{3,}/', $x)));
        if ($arParts) {
            if (!preg_grep('/أويلي/u', $arParts)) $arParts[] = 'أويلي';
            $html = str_replace($t[0], '<title>' . htmlspecialchars(implode(' · ', $arParts), ENT_QUOTES, 'UTF-8') . '</title>', $html);
        }
    }
    // the wait class needs its rule before the body is drawn
    if (strpos(substr($html, 0, 400), 'ow-wait') !== false) {
        $html = preg_replace('/<head>/', '<head><style>html.ow-wait body{visibility:hidden;animation:owShow 0s 1.5s forwards}@keyframes owShow{to{visibility:visible}}</style>', $html, 1);
    }
    // every plain text with data-ar="…": the Arabic text goes in its place
    $html = preg_replace_callback('#(<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*?\sdata-ar="([^"]*)"[^>]*>)([^<]*)(</\2>)#',
        function ($m) { return trim($m[4]) === '' && $m[3] === '' ? $m[0] : $m[1] . $m[3] . $m[5]; }, $html);
    // the menu words with their own id
    $html = preg_replace_callback('#(<(span|button|a)\b[^>]*?\sid="(' . implode('|', array_map('preg_quote', array_keys(OW_LANG_IDS))) . ')"[^>]*>)([^<]*)(</\2>)#',
        function ($m) { return $m[1] . OW_LANG_IDS[$m[3]] . $m[5]; }, $html);
    return $html;
}

}
