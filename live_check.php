<?php
/* TEMPORARY (3 Nov 2026): why the sign-in lock did not trip from the test computer. Shows only short one-way codes, never an address. Removed right after. */
header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || time() > 1793746758 || !hash_equals('f2cfad40a8085ff70914cf394710297807bd66696497dcd9ba98412b58e11408', hash('sha256', (string) ($_POST['k'] ?? '')))) { http_response_code(404); exit; }
require_once 'config.php'; require_once __DIR__ . '/platform_lib.php';
header('Content-Type: text/plain; charset=utf-8');
$c = fn($v) => substr(hash('sha256', 'lc|' . $v), 0, 8);
echo "this request: remote=", $c(owVisitorIp()), " xff_set=", isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? 1 : 0, "\n";
try {
  $cols = getDB()->query('SHOW COLUMNS FROM login_attempts')->fetchAll(PDO::FETCH_COLUMN); echo "columns: ", implode(',', $cols), "\n";
  foreach (getDB()->query("SELECT ip_address, email LIKE '%(admin.php%' a, success, COUNT(*) n FROM login_attempts WHERE created_at > NOW() - INTERVAL 30 MINUTE GROUP BY 1,2,3")->fetchAll() as $r)
    echo "ip=", $c($r['ip_address']), " admin=", $r['a'], " ok=", $r['success'], " n=", $r['n'], "\n";
  echo "fails now: login=", owAttemptFails(null, OW_ADMIN_GATE_TAG), " admin=", owAttemptFails(OW_ADMIN_GATE_TAG), "\n";
} catch (Throwable $e) { echo "error: ", get_class($e), "\n"; }
