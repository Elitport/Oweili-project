<?php
/* =====================================================================
 *  page_cache.php — lets PUBLIC pages open instantly (4 Nov 2026)
 *
 *  PHP's session sends "Cache-Control: no-store" on every page, which stops
 *  the browser from keeping the page for the Back button (back/forward
 *  cache) and makes any early loading useless. Public pages call
 *  owPublicPage() right after config.php, before any output:
 *    - normal visit:  "private, no-cache"  → the Back button shows the kept
 *      page at once; a new visit still asks the server every time
 *    - early load on touch (theme_head.php sends X-Ow-Prefetch: 1, used on
 *      iPhone / Safari where Chrome's own early loading does not exist):
 *      "private, max-age=10" → the tap a moment later opens that copy at once
 *  "private" = never kept by Hostinger's CDN or any shared cache, only in
 *  the visitor's own browser.
 *
 *  Never used on private pages (dashboards, studio, admin, edit pages,
 *  private commission links, certificates, staff previews): they keep
 *  "no-store". Safety: every page carries a short code of the visitor's
 *  sign-in (owStateCode: account + that sign-in's own code + admin); a page shown from a kept copy asks
 *  auth_state.php and reloads if the visitor signed in or out since.
 * ===================================================================== */

function owPublicPage(): void {
    if (headers_sent()) return;
    if (!defined('OW_PUBLIC_PAGE')) define('OW_PUBLIC_PAGE', true);
    $early = ($_SERVER['HTTP_X_OW_PREFETCH'] ?? '') === '1' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET';
    header('Cache-Control: ' . ($early ? 'private, max-age=10' : 'private, no-cache'));
    header_remove('Pragma');
    header_remove('Expires');
}

/* A short one-way code of "who is signed in, with which sign-in". It changes
   when the visitor signs in (new sign-in code), signs out, or the admin signs in
   or out. It does not use the browser session's number: for visitors who are not
   signed in the live server starts a fresh session on almost every page, which
   would make every kept copy look out of date. */
function owStateCode(): string {
    $uid = (int) ($_SESSION['user_id'] ?? 0);
    $tok = $uid ? (string) ($_SESSION['session_token'] ?? '') : '';
    $adm = !empty($_SESSION['is_admin']) ? (string) ($_SESSION['admin_sig'] ?? '1') : '';
    $salt = defined('ADMIN_HASH') ? (string) ADMIN_HASH : __DIR__;
    return substr(hash('sha256', 'ow-state|' . $uid . '|' . $tok . '|' . $adm . '|' . $salt), 0, 16);
}
