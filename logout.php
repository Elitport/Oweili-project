<?php
require_once 'config.php';

require_once __DIR__ . '/platform_lib.php';
if (!empty($_SESSION['user_id']) && isLoggedIn() && ($u = currentUser())) {
    owLog('logout', 'user', (int) $u['id'], '', ['type' => owActorType($u['role'] ?? ''), 'id' => (int) $u['id']]);
}

// End the session everywhere (deletes the sessions-table row + clears cookie).
if (function_exists('logoutUser')) {
    logoutUser();
} else {
    // Fallback if logoutUser() isn't defined in config.php.
    if (!empty($_SESSION['session_token'])) {
        getDB()->prepare('DELETE FROM sessions WHERE session_token = :t')
               ->execute([':t' => $_SESSION['session_token']]);
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

header('Location: login.php');
exit;
