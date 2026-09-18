<?php 	
session_start();

// Check maintenance before any output
require_once '../app/config/database.php';
require_once '../app/includes/functions.php';

// Get campus from URL parameter for pre-selection
$selected_campus = isset($_GET['campus']) ? strtoupper(trim($_GET['campus'])) : '';

// Function to generate shareable link with campus parameter
function generateShareableLink($campus) {
    $base_url = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
    $current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $current_path = dirname($current_path);
    
    // Remove existing query parameters
    $base_url = rtrim($base_url, '/');
    
    return $base_url . '?campus=' . $campus;
}

// Check if Other Payments or Online Payment section is in maintenance
// isSectionInMaintenance already checks main section if sub-page is not enabled
if (isSectionInMaintenance('online-payment', 'guestold')) {
    $maintenance_message = getSectionMaintenanceMessage('online-payment', null, 'guestold');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Online Payment - Maintenance</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
                background: #f5f5f5;
            }
            .maintenance-container {
                text-align: center;
                max-width: 600px;
                padding: 3rem;
                background: white;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            }
            .maintenance-icon {
                font-size: 4rem;
                color: #1c4da1;
                margin-bottom: 1.5rem;
            }
            h1 {
                font-size: 2rem;
                color: #1c4da1;
                margin-bottom: 1rem;
            }
            p {
                font-size: 1.1rem;
                color: #666;
                line-height: 1.6;
                margin-bottom: 2rem;
            }
            .btn {
                display: inline-block;
                padding: 0.75rem 1.5rem;
                background: #1c4da1;
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
            }
        </style>
    </head>
    <body>
        <div class="maintenance-container">
            <div class="maintenance-icon">🔧</div>
            <h1>Under Maintenance</h1>
            <p><?php echo htmlspecialchars($maintenance_message); ?></p>
            <a href="../index.php" class="btn">Go to Homepage</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Legacy URL deprecated - use new hub other (301 for GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_POST)) {
    $qs = $_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : '';
    header("Location: other".$qs, true, 301);
    exit;
}
$_SESSION['isin'] = 'iamin';

include "dbconnect.php";
include "campus_table_manager.php";

// Ensure all campus tables exist (including Isabela and Roxas)
ensureCampusTablesExist($con);

if (isset($_POST["btnsubmit"])) {
    date_default_timezone_set("Asia/Manila");
    $transid = $_POST["campid"] ."_". date("HismdY");
    header("Location: paymentold.php?payee=&transid=$transid");
    die;
}
?>	

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>UPHSL Online Payment - Perpetualites</title>
<link rel="icon" type="image/png" href="assets/UPHSJ_LOGO_2026Edition.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@600;700;800&family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="assets/style.css?v=17">
</head>
<body>
<header class="pay-header">
  <div class="pay-header-inner">
    <a href="./" class="pay-brand">
      <img src="assets/UPHSJ_LOGO_2026Edition.png" alt="UPHSL Logo">
      <div class="pay-brand-text">
        <span class="pay-brand-title">UPHS</span>
        <span class="pay-brand-sub">Online Payments</span>
      </div>
    </a>
    <nav class="pay-nav" id="payNav">
      <a href="new-enrollee" class="pay-nav-link">New Enrollees</a>
      <a href="enrolled" class="pay-nav-link">Enrolled</a>
      <a href="other" class="pay-nav-link active">Other</a>
    </nav>
    <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
      <a href="instructions" class="pay-howtopay-persist" aria-label="How to Pay"><i class="fa-solid fa-circle-question"></i><span>How to Pay</span></a>
      <button class="pay-burger" id="payBurger" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
    </div>
  </div>
</header>
<main class="pay-main">
<div style="max-width:780px;margin:0 auto">
  <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
    <a href="./" class="btn" style="background:#fff;border:1px solid var(--line); padding:7px 12px; font-size:12px">Back</a>
    <span style="color:var(--muted);font-weight:600; font-size:12px">Other Payments • Legacy guestold</span>
  </div>
  <div class="form-card">
    <div class="form-head">
      <i class="fa-solid fa-file-invoice"></i>
      <div>
        <div style="font-weight:800;font-size:18px">Other Payments</div>
        <div style="opacity:.9;font-size:13px">General payment - campus selection only. UI refreshed, backend same - filename guestold.php unchanged.</div>
      </div>
    </div>
    <div class="form-body">
      <form method="post">
        <div class="field">
          <label for="campid">Select Your Campus</label>
          <select name="campid" id="campid" required>
            <option value="">Choose your campus...</option>
            <option value="UPHB" <?php echo ($selected_campus === 'UPHB') ? 'selected' : ''; ?>>Binan Campus</option>
            <option value="UPHMU" <?php echo ($selected_campus === 'UPHMU') ? 'selected' : ''; ?>>Medical University</option>
            <option value="UPHG" <?php echo ($selected_campus === 'UPHG') ? 'selected' : ''; ?>>GMA Campus</option>
            <option value="UPHM" <?php echo ($selected_campus === 'UPHM') ? 'selected' : ''; ?>>Manila Campus</option>
            <option value="PHCP" <?php echo ($selected_campus === 'PHCP') ? 'selected' : ''; ?>>Pangasinan Campus</option>
            <option value="UPHI" <?php echo ($selected_campus === 'UPHI') ? 'selected' : ''; ?>>Isabela Campus</option>
            <option value="UPHR" <?php echo ($selected_campus === 'UPHR') ? 'selected' : ''; ?>>Roxas Campus</option>
          </select>
          <small style="color:var(--muted)">Proceeds to paymentold.php - backend unchanged.</small>
        </div>
        <button type="submit" name="btnsubmit" class="btn btn-primary" style="width:100%;padding:16px;font-size:16px;margin-top:14px"><i class="fa-solid fa-credit-card"></i> Proceed to Payment</button>
      </form>
      <div class="alert info" style="margin-top:12px"><i class="fa-solid fa-circle-info"></i><div><strong>Note:</strong> No student verification required for this legacy flow.</div></div>
    </div>
  </div>
</div>
</main>
<footer class="pay-footer">
  <div class="pay-footer-inner" style="justify-content:center; text-align:center">
    <div class="pay-footer-brand" style="justify-content:center">
      <img src="assets/UPHSJ_LOGO_2026Edition.png" alt="UPHSL Logo">
      <div><strong>University of Perpetual Help System</strong></div>
    </div>
  </div>
  <div class="pay-footer-bottom" style="display:flex;align-items:center;justify-content:center;gap:10px;flex-wrap:wrap">&copy; <?php echo date('Y'); ?> UPHSL <img src="assets/dragonpay-xendit-logo-removebg-preview.png" alt="DragonPay" class="dp-logo dp-logo--sm" style="height:20px"></div>
</footer>
<script>document.getElementById('payBurger')?.addEventListener('click',()=>{ document.getElementById('payNav').classList.toggle('open'); });</script>
</body>
</html>
