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

// Check if Guest (New Enrollees) or Online Payment section is in maintenance
// isSectionInMaintenance already checks main section if sub-page is not enabled
if (isSectionInMaintenance('online-payment', 'guest')) {
    $maintenance_message = getSectionMaintenanceMessage('online-payment', null, 'guest');
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

// Legacy URL deprecated - use new hub new-enrollee (301 for GET, keep POST for AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_POST)) {
    $qs = $_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : '';
    header("Location: new-enrollee".$qs, true, 301);
    exit;
}
$_SESSION['isin'] = 'iamin';

include "dbconnect.php";
include "campus_table_manager.php";

// Ensure all campus tables exist (including Isabela and Roxas)
ensureCampusTablesExist($con);
// Ensure temporary locator-based student tables exist
ensureTmpStudentTablesExist($con);

// Find student name by locator from the campus tmp table
function findStudentByLocator($con, $locator, $campid) {
    $locator = trim($locator);
    if ($locator === '') { return null; }
    $table = mapCampusToTmpTable($campid);
    if ($table === null) { return null; }
    $t = str_replace("`", "", $table);
    $loc = mysqli_real_escape_string($con, $locator);
    $sql = "SELECT `stud_name` FROM `{$t}` WHERE `locator_num`='".$loc."' LIMIT 1";
    $res = @mysqli_query($con, $sql);
    if ($res && ($row = mysqli_fetch_assoc($res))) {
        $name = isset($row['stud_name']) ? trim($row['stud_name']) : '';
        return ($name !== '') ? $name : null;
    }
    return null;
}

// Handle AJAX verification request for locator
if (isset($_POST["verify_locator"])) {
    if (ob_get_level()) { ob_clean(); }
    header('Content-Type: application/json');
    $locno = isset($_POST['locno']) ? trim($_POST['locno']) : '';
    $campid = isset($_POST['campid']) ? $_POST['campid'] : '';
    $table = mapCampusToTmpTable($campid);
    if ($table === null) {
        echo json_encode(['success' => false, 'message' => 'Invalid campus selected.']);
    } else if (!tableExists($con, $table)) {
        echo json_encode(['success' => false, 'message' => 'Campus verification data not available.']);
    } else {
        $studentName = findStudentByLocator($con, $locno, $campid);
        if ($studentName && $locno !== '') {
            echo json_encode(['success' => true, 'name' => $studentName, 'message' => 'Locator verified successfully!']);
        } else {
            // Locator not found: advise the student to proceed with advising first
            echo json_encode([
                'success' => false,
                'message' => 'Locator number not found. Please proceed with advising before attempting payment.',
                'advice' => true
            ]);
        }
    }
    exit;
}
if (isset($_POST["btnsubmit"])) {
    date_default_timezone_set("Asia/Manila");
    $campid = $_POST["campid"] ?? '';
    $locno_raw = $_POST["locno"] ?? '';
    $locno = trim($locno_raw);
    $studentName = findStudentByLocator($con, $locno, $campid);
    if ($studentName && $locno !== '') {
        $transid = $campid ."_". date("HismdY");
        // Redirect with confirmed payee name and locator
        header("Location: payment.php?payee=" . urlencode($studentName) . "&transid=" . urlencode($transid) . "&locno=" . urlencode($locno));
        die;
    } else {
        ?>
        <div style="padding:20px; background-color:#FF0000; color:#FFFFFF; font-size:20px; font-weight:bold" align="center">Entered locator number does not exist or is not validated</div>
        <?php
        die;
    }
}
?>	
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>UPHSL Online Payment - New Students</title>
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
      <a href="new-enrollee" class="pay-nav-link active">New Enrollees</a>
      <a href="enrolled" class="pay-nav-link">Enrolled</a>
      <a href="other" class="pay-nav-link">Other</a>
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
    <span style="color:var(--muted);font-weight:600; font-size:12px">New Enrollees &bull; Locator</span>
  </div>
  <div class="form-card">
    <div class="form-head">
      <i class="fa-solid fa-user-plus"></i>
      <div>
        <div style="font-weight:800;font-size:18px">New Enrollee Payment</div>
        <div style="opacity:.9;font-size:13px">For students not previously enrolled &mdash; locator number only. UI refreshed, backend same - filename guest.php unchanged.</div>
      </div>
    </div>
    <div class="form-body">
      <form method="post" id="newForm">
        <div class="field">
          <label for="campid">Select Your Campus</label>
          <select name="campid" id="campid" required onchange="resetVerification()">
            <option value="">Choose your campus...</option>
            <option value="UPHB" <?php echo ($selected_campus === 'UPHB') ? 'selected' : ''; ?>>Binan Campus</option>
          </select>
          <small style="color:var(--muted)">Choose where you were advised.</small>
        </div>
        <div id="locator-section" style="display:none">
          <div style="background:var(--bg);border:1px solid var(--line);border-radius:14px;padding:16px;margin-top:8px">
            <h4 style="margin:0 0 8px;color:var(--blue)"><i class="fa-solid fa-magnifying-glass"></i> Verify Locator Number</h4>
            <div id="submit-help" style="text-align:center;color:var(--muted);font-size:13px;margin-bottom:10px">Please verify your locator number before proceeding</div>
            <div class="verify-box">
              <div class="field" style="margin:0"><label for="locno">Locator Number</label><input type="text" name="locno" id="locno" maxlength="20" placeholder="Enter locator number" oninput="onLocInput()" required></div>
              <button type="button" id="verifyBtn" onclick="verifyLocator()" class="btn-verify"><i class="fa-solid fa-magnifying-glass"></i> Verify</button>
            </div>
            <div id="verification-result" style="margin-top:12px"></div>
          </div>
        </div>
        <div id="submit-section" style="display:none;margin-top:14px">
          <button type="submit" name="btnsubmit" id="btnsubmit" class="btn btn-primary" style="width:100%;padding:16px;font-size:16px" disabled><i class="fa-solid fa-credit-card"></i> Proceed to Payment</button>
        </div>
      </form>
      <div class="alert info"><i class="fa-solid fa-circle-info"></i><div><strong>Note:</strong> Locator numbers are issued during advising. If not found, please complete advising first.</div></div>
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
<script>
document.getElementById('payBurger')?.addEventListener('click',()=>{ document.getElementById('payNav').classList.toggle('open'); });
</script>
<script>
let isLocatorVerified=false;
function resetVerification(){
  const campid=document.getElementById('campid').value;
  const sec=document.getElementById('locator-section');
  if(campid!==''){ sec.style.display='block'; const url=new URL(window.location.href); url.searchParams.set('campus',campid); history.replaceState({},'',url); }
  else { sec.style.display='none'; const url=new URL(window.location.href); url.searchParams.delete('campus'); history.replaceState({},'',url); }
  isLocatorVerified=false;
  document.getElementById('verification-result').innerHTML='';
  document.getElementById('submit-help').innerHTML='Please verify your locator number before proceeding';
  toggleSubmit();
}
function onLocInput(){ isLocatorVerified=false; document.getElementById('verification-result').innerHTML=''; document.getElementById('submit-help').innerHTML='Please verify your locator number before proceeding'; toggleSubmit(); }
function toggleSubmit(){
  const l=document.getElementById('locno'); const btn=document.getElementById('btnsubmit'); const sec=document.getElementById('submit-section');
  if(isLocatorVerified && l && l.value.trim()!==''){ sec.style.display='block'; btn.disabled=false; } else { sec.style.display='none'; if(btn) btn.disabled=true; }
}
function confirmLocator(){ isLocatorVerified=true; document.getElementById('submit-help').innerHTML='&#10003; Name confirmed! You may now proceed.'; toggleSubmit(); setTimeout(()=>document.getElementById('btnsubmit')?.scrollIntoView({behavior:'smooth',block:'center'}),100); }
function rejectLocator(){ isLocatorVerified=false; document.getElementById('locno').value=''; document.getElementById('verification-result').innerHTML=''; document.getElementById('submit-help').innerHTML='Please verify your locator number before proceeding'; toggleSubmit(); }
function verifyLocator(){
  const locno=document.getElementById('locno').value.trim();
  const campid=document.getElementById('campid').value;
  const resultDiv=document.getElementById('verification-result');
  const verifyBtn=document.getElementById('verifyBtn');
  if(!locno){ alert('Please enter a locator number first.'); return; }
  if(!campid){ alert('Please select a campus first.'); return; }
  verifyBtn.disabled=true; verifyBtn.innerHTML='Verifying...'; resultDiv.innerHTML='<div class="alert info">Verifying locator...</div>';
  const fd=new FormData(); fd.append('verify_locator','1'); fd.append('locno',locno); fd.append('campid',campid);
  fetch('',{method:'POST',body:fd}).then(r=>r.json()).then(data=>{
    if(data.success){
      const campLabel = document.getElementById('campid').selectedOptions[0]?.text || campid;
      const initials = data.name.trim().split(/\s+/).slice(0,2).map(w=>w[0]).join('').toUpperCase().substring(0,2) || 'x';
      resultDiv.innerHTML='<div class="verify-card">'
        +'<div class="verify-card-top ok"><div class="verify-icon"><i class="fa-solid fa-check"></i></div><div><div class="verify-title">Verified</div><div class="verify-subtitle">'+data.message+'</div></div></div>'
        +'<div class="verify-card-body">'
          +'<div class="verify-profile"><div class="verify-avatar">'+initials+'</div><div><div class="verify-name">'+data.name+'</div><div class="verify-sub">Is this you? Tap Confirm if correct</div></div></div>'
          +'<div class="verify-meta"><span>'+locno+'</span><span>'+campLabel+'</span></div>'
          +'<div class="verify-hint">Found in campus records. Confirm to proceed with payment.</div>'
        +'</div>'
        +'<div class="verify-actions"><button type="button" onclick="confirmLocator()" class="btn btn-primary"><i class="fa-solid fa-check"></i> Yes, its me</button><button type="button" onclick="rejectLocator()" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i> Not mine</button></div>'
      +'</div>';
      document.getElementById('submit-help').innerHTML='Found -- confirm your name';
    } else {
      isLocatorVerified=false;
      if(data.advice){ resultDiv.innerHTML='<div class="verify-card"><div class="verify-card-top warn"><div class="verify-icon"><i class="fa-solid fa-triangle-exclamation"></i></div><div><div class="verify-title">Not found</div><div class="verify-subtitle">No matching locator</div></div></div><div class="verify-card-body"><div class="verify-hint">'+data.message+'</div></div></div>'; document.getElementById('submit-help').innerHTML='Locator not found. Please complete advising.'; }
      else { resultDiv.innerHTML='<div class="verify-card"><div class="verify-card-top err"><div class="verify-icon"><i class="fa-solid fa-circle-xmark"></i></div><div><div class="verify-title">Failed</div><div class="verify-subtitle">Verification failed</div></div></div><div class="verify-card-body"><div class="verify-hint">'+data.message+'</div></div></div>'; document.getElementById('submit-help').innerHTML='Please verify your locator number before proceeding'; }
    }
    toggleSubmit();
  }).catch(()=>{ resultDiv.innerHTML='<div class="verify-card"><div class="verify-card-top err"><div class="verify-icon"><i class="fa-solid fa-circle-xmark"></i></div><div><div class="verify-title">Error</div><div class="verify-subtitle">Verification failed</div></div></div><div class="verify-card-body"><div class="verify-hint">Error verifying. Please try again.</div></div></div>'; toggleSubmit(); }).finally(()=>{ verifyBtn.disabled=false; verifyBtn.innerHTML='<i class="fa-solid fa-magnifying-glass"></i> Verify'; });
}
document.addEventListener('DOMContentLoaded',()=>{
  const campid=document.getElementById('campid').value;
  if(campid!=='') document.getElementById('locator-section').style.display='block';
  const l=document.getElementById('locno');
  if(l){ l.addEventListener('keydown',e=>{ if(e.key==='Enter'){ e.preventDefault(); verifyLocator(); } }); }
});
</script>
</body>
</html>
