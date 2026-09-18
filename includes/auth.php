<?php
// OLP Standalone Admin Auth — does NOT depend on uphsledu/app
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/dbconnect.php';
// OLP users now live in uphsledu_onlinepayment (independent from uphsledu_main)
// Prefer local onlinepayment PDO; fallback to legacy getDBConnection if needed
if (!function_exists('getDBConnection')) {
    $candidates = [
        __DIR__ . '/../app/config/database.php',
        __DIR__ . '/../../uphsledu/app/config/database.php',
        'C:/xampp/htdocs/uphsledu/app/config/database.php',
    ];
    foreach ($candidates as $c) {
        if (is_file($c)) { require_once $c; break; }
    }
}
function olp_getUsersPDO(){
    static $pdo = null;
    if ($pdo) return $pdo;
    // Try onlinepayment first (independent)
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=uphsledu_onlinepayment;charset=utf8mb4", "root", "", [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
        // ensure users table exists (light check)
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            employee_number VARCHAR(50) UNIQUE DEFAULT NULL,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            role ENUM('super_admin','admin','author','hr') DEFAULT 'hr',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        // migrate: add employee_number if missing (for existing installs)
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'employee_number'")->fetchAll();
            if (empty($cols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN employee_number VARCHAR(50) UNIQUE DEFAULT NULL AFTER email");
            }
        } catch (Throwable $e2) {}
        // ensure default web-admin has employee_number 11745 (production auto-fix)
        try {
            $pdo->exec("UPDATE users SET employee_number='11745' WHERE username='web-admin' AND (employee_number IS NULL OR employee_number != '11745')");
        } catch (Throwable $e2) {
            // if duplicate 11745 taken, keep existing; log silently
        }
        // ensure login_attempts table exists for rate limiting
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS olp_login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                identifier VARCHAR(100) NOT NULL,
                ip VARCHAR(45) NOT NULL,
                attempts INT NOT NULL DEFAULT 1,
                last_attempt DATETIME NOT NULL,
                locked_until DATETIME DEFAULT NULL,
                INDEX idx_identifier (identifier),
                INDEX idx_ip (ip)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (Throwable $e2) {}
        return $pdo;
    } catch (Throwable $e) {
        // fallback to legacy main DB if onlinepayment unavailable
        try { if (function_exists('getDBConnection')) return getDBConnection(); } catch (Throwable $e2) {}
        return null;
    }
}

function olp_isLoggedIn() {
    return isset($_SESSION['olp_user_id']) && isset($_SESSION['olp_user_role']);
}
function olp_isSuperAdmin() {
    return olp_isLoggedIn() && $_SESSION['olp_user_role'] === 'super_admin';
}
function olp_isAdmin() {
    return olp_isLoggedIn() && in_array($_SESSION['olp_user_role'] ?? '', ['admin','super_admin']);
}
function olp_getUserById($id) {
    try {
        $pdo = olp_getUsersPDO() ?: (function_exists('getDBConnection') ? getDBConnection() : null);
        if (!$pdo) return null;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([(int)$id]);
        return $stmt->fetch();
    } catch (Throwable $e) { return null; }
}
function olp_getUserByUsername($username) {
    try {
        $pdo = olp_getUsersPDO() ?: (function_exists('getDBConnection') ? getDBConnection() : null);
        if (!$pdo) return null;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        return $stmt->fetch();
    } catch (Throwable $e) { return null; }
}
function olp_getUserByEmployeeNumber($empNo) {
    try {
        $pdo = olp_getUsersPDO() ?: (function_exists('getDBConnection') ? getDBConnection() : null);
        if (!$pdo) return null;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE employee_number = ? LIMIT 1");
        $stmt->execute([$empNo]);
        return $stmt->fetch();
    } catch (Throwable $e) { return null; }
}
function olp_getUserByLogin($identifier) {
    // Allow login via username, email, or employee_number (case-insensitive for email)
    $identifier = trim($identifier);
    if ($identifier === '') return null;
    try {
        $pdo = olp_getUsersPDO() ?: (function_exists('getDBConnection') ? getDBConnection() : null);
        if (!$pdo) return null;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? OR employee_number = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier, $identifier]);
        return $stmt->fetch();
    } catch (Throwable $e) { return null; }
}
// --- Security helpers: CSRF + Rate limiting ---
function olp_csrf_token() {
    if (empty($_SESSION['olp_csrf_token'])) {
        $_SESSION['olp_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['olp_csrf_token'];
}
function olp_verify_csrf($token) {
    return isset($_SESSION['olp_csrf_token']) && hash_equals($_SESSION['olp_csrf_token'], (string)$token);
}
function olp_login_is_locked($identifier, $ip) {
    try {
        $pdo = olp_getUsersPDO();
        if (!$pdo) return [false, 0];
        $stmt = $pdo->prepare("SELECT attempts, locked_until FROM olp_login_attempts WHERE identifier=? AND ip=? LIMIT 1");
        $stmt->execute([$identifier, $ip]);
        $row = $stmt->fetch();
        if (!$row) return [false, 0];
        if (!empty($row['locked_until']) && strtotime($row['locked_until']) > time()) {
            $remain = strtotime($row['locked_until']) - time();
            return [true, $remain];
        }
        // expired lock - clear
        if (!empty($row['locked_until']) && strtotime($row['locked_until']) <= time()) {
            $pdo->prepare("DELETE FROM olp_login_attempts WHERE identifier=? AND ip=?")->execute([$identifier, $ip]);
            return [false, 0];
        }
        return [false, 0];
    } catch (Throwable $e) { return [false, 0]; }
}
function olp_login_record_failure($identifier, $ip) {
    $maxAttempts = 5;
    $lockSeconds = 900; // 15 min
    try {
        $pdo = olp_getUsersPDO();
        if (!$pdo) return;
        $stmt = $pdo->prepare("SELECT id, attempts FROM olp_login_attempts WHERE identifier=? AND ip=? LIMIT 1");
        $stmt->execute([$identifier, $ip]);
        $row = $stmt->fetch();
        $now = date('Y-m-d H:i:s');
        if (!$row) {
            $pdo->prepare("INSERT INTO olp_login_attempts (identifier, ip, attempts, last_attempt) VALUES (?,?,1,?)")->execute([$identifier, $ip, $now]);
        } else {
            $attempts = (int)$row['attempts'] + 1;
            if ($attempts >= $maxAttempts) {
                $lockedUntil = date('Y-m-d H:i:s', time() + $lockSeconds);
                $pdo->prepare("UPDATE olp_login_attempts SET attempts=?, last_attempt=?, locked_until=? WHERE id=?")->execute([$attempts, $now, $lockedUntil, $row['id']]);
            } else {
                $pdo->prepare("UPDATE olp_login_attempts SET attempts=?, last_attempt=? WHERE id=?")->execute([$attempts, $now, $row['id']]);
            }
        }
    } catch (Throwable $e) {}
}
function olp_login_clear_attempts($identifier, $ip) {
    try {
        $pdo = olp_getUsersPDO();
        if (!$pdo) return;
        $pdo->prepare("DELETE FROM olp_login_attempts WHERE identifier=? AND ip=?")->execute([$identifier, $ip]);
    } catch (Throwable $e) {}
}
function olp_requireAdmin() {
    if (!olp_isLoggedIn() || !olp_isSuperAdmin()) {
        $payments_base = $GLOBALS['payments_base'] ?? '/';
        header('Location: ' . $payments_base . 'admin/login?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? $payments_base . 'admin/'));
        exit;
    }
}
function olp_redirect($url){ header("Location: $url"); exit; }
// Compat wrappers for legacy admin pages (students.php/monitoring.php copied from uphsledu/admin)
if (!function_exists('getUserById')) {
    function getUserById($id) { return olp_getUserById($id); }
}
if (!function_exists('getUserByUsername')) {
    function getUserByUsername($username) { return olp_getUserByUsername($username); }
}
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() { return olp_isLoggedIn(); }
}
if (!function_exists('isSuperAdmin')) {
    function isSuperAdmin() { return olp_isSuperAdmin(); }
}
