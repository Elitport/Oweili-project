<?php
/* =====================================================================
 *  ow_paths.php — web addresses for the site's own files
 *  owBase()        "/" when the site sits at the top of public_html (Hostinger),
 *                  or "/folder/" if it ever lives inside a folder
 *  owAsset($file)  full address of a CSS / JS file with its version number,
 *                  e.g. /assets/css/oweili.css?v=1793181648 (the number changes
 *                  when the file changes, so browsers load the new copy)
 *  Always link our own files through these, never with a typed path.
 * ===================================================================== */
if (!function_exists('owBase')) {
    function owBase(): string {
        static $base = null;
        if ($base === null) {
            $dir  = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
            $base = rtrim($dir, '/.') . '/';
            if ($base[0] !== '/') $base = '/' . ltrim($base, '/');
        }
        return $base;
    }
    function owAsset(string $file): string {
        $file = ltrim($file, '/');
        return htmlspecialchars(owBase() . $file . '?v=' . (int) @filemtime(__DIR__ . '/' . $file), ENT_QUOTES, 'UTF-8');
    }
}

/* ---------------------------------------------------------------------
 *  Oweili's admin and contact email — ONE setting for the whole site.
 *  Every admin email goes here, every email to artists and buyers uses it
 *  as the reply address, and visitors see it (footer, Support, Privacy,
 *  Terms). To change it: edit OW_CONTACT_EMAIL below, or (without a code
 *  change) add define('OWEILI_CONTACT_EMAIL', 'new@address') to config.php.
 *  Emails are still sent from the mailbox in config.php (no-reply@oweili.com).
 * ------------------------------------------------------------------- */
if (!defined('OW_CONTACT_EMAIL')) define('OW_CONTACT_EMAIL', 'contact@oweili.com');
if (!function_exists('owContactEmail')) {
    function owContactEmail(): string {
        $c = defined('OWEILI_CONTACT_EMAIL') ? trim((string) OWEILI_CONTACT_EMAIL) : '';
        return filter_var($c, FILTER_VALIDATE_EMAIL) ? $c : OW_CONTACT_EMAIL;
    }
}
