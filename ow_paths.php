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
