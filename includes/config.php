<?php
// OLP â€” Online Payment Portal â€” STANDALONE config
// All payment data uses LOCAL includes/dbconnect.php (mysqli $con â†’ UPHSedu_onlinepayment)
// Admin auth uses LOCAL includes/auth.php + app/config/database.php (PDO â†’ UPHSedu_main) â€” NOT UPHSedu folder
if (session_status() === PHP_SESSION_NONE) session_start();

// --- Payments DB (LOCAL) ---
require_once __DIR__ . '/dbconnect.php';
require_once __DIR__ . '/campus_table_manager.php';

if (isset($con)) {
    @mysqli_set_charset($con, 'utf8mb4');
    ensureCampusTablesExist($con);
    ensureTmpStudentTablesExist($con);
}

// --- Base path for this standalone hub ---
// Detect environment (folder-agnostic - supports pay, online_payment, olpay, olp, etc.)
//  - pay.uphsl.edu.ph or online_payment.uphsl.edu.ph => '/'
//  - uphsl.edu.ph/pay or /online_payment etc. => '/pay/' or '/online_payment/' (auto-detected)
//  - localhost/<any> => '/<folder>/' inferred from SCRIPT_NAME
if (!isset($GLOBALS['payments_base'])) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $uri  = $_SERVER['REQUEST_URI'] ?? '';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    // Dedicated subdomain = root
    if (strpos($host, 'pay.uphsl.edu.ph') !== false || strpos($host, 'online_payment') !== false) {
        $GLOBALS['payments_base'] = '/';
    } elseif (preg_match('#^/([^/]+)/#', $script, $m)) {
        $seg = $m[1];
        // If first segment looks like a folder (no dot), use it as base - works for pay, online_payment, olpay, olp, etc.
        if (strpos($seg, '.') === false) {
            $GLOBALS['payments_base'] = '/' . $seg . '/';
        } else {
            $GLOBALS['payments_base'] = '/';
        }
    } elseif (preg_match('#^/([^/]+)/#', $uri, $m)) {
        $seg = $m[1];
        if (strpos($seg, '.') === false) {
            $GLOBALS['payments_base'] = '/' . $seg . '/';
        } else {
            $GLOBALS['payments_base'] = '/';
        }
    } else {
        $GLOBALS['payments_base'] = '/';
    }
}
$payments_base = $GLOBALS['payments_base'];

// --- Admin auth â€” load LOCAL auth ---
require_once __DIR__ . '/auth.php';

function paymentsRequireAdmin() {
    olp_requireAdmin();
}

