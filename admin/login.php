<?php
// OLP Standalone Admin Login — secure with Employee Number + CSRF + Rate Limiting
if (session_status() === PHP_SESSION_NONE) {
    // Harden session cookie before start
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
require_once __DIR__ . '/../includes/config.php';

$error = '';
// ensure PDO tables exist (migration)
olp_getUsersPDO();
$csrf = olp_csrf_token();

if (olp_isLoggedIn() && olp_isSuperAdmin()) {
    $redir = $_GET['redirect'] ?? $payments_base . 'admin/';
    header("Location: $redir"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!olp_verify_csrf($token)) {
        $error = 'Security token expired. Please refresh and try again.';
    } else {
        // Accept identifier as username / employee_number / email (backward compat with username field)
        $identifier = trim($_POST['identifier'] ?? $_POST['username'] ?? $_POST['employee_number'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if ($identifier === '' || $password === '') {
            $error = 'Please enter Employee No. / Username and password.';
        } else {
            // Rate limit check
            [$isLocked, $remain] = olp_login_is_locked($identifier, $ip);
            if ($isLocked) {
                $mins = (int)ceil($remain / 60);
                $error = "Too many failed attempts. Please try again in {$mins} minute(s).";
            } else {
                $user = olp_getUserByLogin($identifier);
                // Use password_verify only if user exists to avoid timing leak difference, but still generic error
                $valid = $user && password_verify($password, $user['password']);
                if ($valid) {
                    if ($user['role'] !== 'super_admin') {
                        // still count as failure for enumeration protection? but show role error
                        olp_login_record_failure($identifier, $ip);
                        $error = 'Only Super Admin can access OLP Admin. Your role: ' . htmlspecialchars($user['role']);
                    } else {
                        // Optional: rehash if needed
                        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                            try {
                                $pdo = olp_getUsersPDO();
                                $newHash = password_hash($password, PASSWORD_DEFAULT);
                                $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
                                $stmt->execute([$newHash, $user['id']]);
                            } catch (Throwable $e) {}
                        }
                        olp_login_clear_attempts($identifier, $ip);
                        // Regenerate session to prevent fixation
                        session_regenerate_id(true);
                        $_SESSION['olp_user_id'] = $user['id'];
                        $_SESSION['olp_user_role'] = $user['role'];
                        $_SESSION['olp_username'] = $user['username'];
                        $_SESSION['olp_employee_number'] = $user['employee_number'] ?? null;
                        // also mirror to generic session for compatibility
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_role'] = $user['role'];
                        $redir = $_GET['redirect'] ?? $payments_base . 'admin/';
                        // prevent open redirect to external
                        if (strpos($redir, '//') !== false || strpos($redir, 'http') === 0) $redir = $payments_base . 'admin/';
                        header("Location: $redir"); exit;
                    }
                } else {
                    olp_login_record_failure($identifier, $ip);
                    // Check if now locked to give feedback
                    [$nowLocked, $nowRemain] = olp_login_is_locked($identifier, $ip);
                    if ($nowLocked) {
                        $mins = (int)ceil($nowRemain / 60);
                        $error = "Too many failed attempts. Account locked for {$mins} minute(s).";
                    } else {
                        $error = 'Invalid credentials. Please check Employee No. / Username and password.';
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login - UPHSL Payments</title>
<link rel="icon" type="image/png" href="<?= $payments_base ?>assets/UPHSJ_LOGO_2026Edition.png">
<link rel="shortcut icon" type="image/png" href="<?= $payments_base ?>assets/UPHSJ_LOGO_2026Edition.png">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@600;700;800&family=Barlow+Semi+Condensed:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
:root{--blue:#1c4da1;--blue2:#2a64c8;--gold:#ffc63e;--bg:#f6f8fc;--line:#e5e9f2}
*{box-sizing:border-box}body{margin:0;font-family:Inter,system-ui;background:linear-gradient(135deg,var(--blue),#4a7ee0);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;border-radius:20px;max-width:460px;width:100%;box-shadow:0 20px 50px rgba(0,0,0,.2);overflow:hidden;border:1px solid rgba(255,255,255,.6)}
.head{background:linear-gradient(135deg,var(--blue),var(--blue2));color:#fff;padding:22px;text-align:center}
.head img{width:56px;height:56px;background:#fff;border-radius:12px;padding:8px;object-fit:contain}
.head h1{margin:10px 0 4px;font-family:"Barlow Semi Condensed",sans-serif;font-size:22px}
.head p{margin:0;opacity:.9;font-size:13px}
.body{padding:22px;display:grid;gap:14px}
.field label{font-weight:800;font-size:13px;color:#1d2a44}
.field input{width:100%;margin-top:8px;padding:13px 14px;border:1px solid #dfe6f5;border-radius:12px;background:#fbfdff;font:600 14px Inter}
.field input:focus{outline:none;border-color:var(--blue);background:#fff;box-shadow:0 0 0 4px rgba(28,77,161,.12)}
.btn{width:100%;padding:14px;background:var(--blue);color:#fff;border:none;border-radius:12px;font-weight:800;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px}
.btn:hover{background:#12307a}
.alert{padding:12px 14px;border-radius:12px;border:1px solid #fecaca;background:#fef2f2;color:#7f1d1d;font-size:13px;display:flex;gap:8px}
.alert.ok{border-color:#bbf7d0;background:#f0fdf4;color:#14532d}
.note{color:#667085;font-size:12px;text-align:center}
.hint{font-size:11px;color:#667085;margin-top:4px}
a{color:var(--blue);font-weight:700;text-decoration:none}
</style>
</head>
<body>
<div class="card">
  <div class="head">
    <img src="<?= $payments_base ?>assets/UPHSJ_LOGO_2026Edition.png" alt="UPHSL">
    <h1>OLP Admin</h1>
    <p>Online Payment Portal — Secure Login</p>
  </div>
  <form method="post" class="body" autocomplete="off">
    <?php if($error): ?><div class="alert"><i class="fa-solid fa-triangle-exclamation"></i><div><?= htmlspecialchars($error) ?></div></div><?php endif; ?>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <div class="field">
      <label><i class="fa-solid fa-id-badge"></i> Employee No. / Username / Email</label>
      <input type="text" name="identifier" required autofocus placeholder="e.g. EMP-00123 or web-admin" value="<?= htmlspecialchars($_POST['identifier'] ?? $_POST['username'] ?? '') ?>">
      <div class="hint">Use your <strong>Employee Number</strong> (preferred), username, or email.</div>
    </div>
    <div class="field"><label><i class="fa-solid fa-lock"></i> Password</label><input type="password" name="password" required placeholder="••••••••"></div>
    <button type="submit" class="btn"><i class="fa-solid fa-right-to-bracket"></i> Sign In Securely</button>
    <div class="note">OLP users in <code>uphsledu_onlinepayment.users</code> with <code>employee_number</code> (super_admin only). Protected by CSRF &amp; rate limiting.<br><a href="<?= $payments_base ?>">Back to OLP Hub</a> • <a href="/uphsledu/auth/login">Main Site Login</a></div>
  </form>
</div>
</body>
</html>
