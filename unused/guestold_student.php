<?php 	
session_start();

// Check maintenance before any output - gracefully handle missing app files
$__db_cfg = __DIR__ . '/../app/config/database.php';
if (file_exists($__db_cfg)) { require_once $__db_cfg; }
elseif (file_exists(__DIR__ . '/../../uphsledu/app/config/database.php')) { require_once __DIR__ . '/../../uphsledu/app/config/database.php'; }
elseif (file_exists('C:/xampp/htdocs/uphsledu/app/config/database.php')) { require_once 'C:/xampp/htdocs/uphsledu/app/config/database.php'; }

$__fn_inc = __DIR__ . '/../app/includes/functions.php';
if (file_exists($__fn_inc)) { require_once $__fn_inc; }
elseif (file_exists(__DIR__ . '/../../uphsledu/app/includes/functions.php')) { require_once __DIR__ . '/../../uphsledu/app/includes/functions.php'; }
elseif (file_exists('C:/xampp/htdocs/uphsledu/app/includes/functions.php')) { require_once 'C:/xampp/htdocs/uphsledu/app/includes/functions.php'; }

// Fallback stubs if maintenance functions still not available (e.g., app folder missing)
if (!function_exists('isSectionInMaintenance')) { function isSectionInMaintenance($sectionKey, $subKey = null) { return false; } }
if (!function_exists('getSectionMaintenanceMessage')) { function getSectionMaintenanceMessage($sectionKey, $defaultMessage = null, $subKey = null) { return $defaultMessage ?? 'This section is currently under maintenance. Please check back soon.'; } }

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

// Check if Guest Old Student or Online Payment section is in maintenance
// isSectionInMaintenance already checks main section if sub-page is not enabled
if (isSectionInMaintenance('online-payment', 'guestold-student')) {
    $maintenance_message = getSectionMaintenanceMessage('online-payment', null, 'guestold-student');
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

	// Legacy URL deprecated - use new hub enrolled (301 for GET, keep POST for AJAX)
	if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_POST) && !isset($_GET['ajax'])) {
	    $qs = $_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : '';
	    header("Location: enrolled".$qs, true, 301);
	    exit;
	}
	include "dbconnect.php";
	include "campus_table_manager.php";

	// Ensure all campus tables exist (including Isabela and Roxas)
	ensureCampusTablesExist($con);

	// Ensure all campus tables exist (including Isabela and Roxas)
	ensureCampusTablesExist($con);

	// Functions are now provided by campus_table_manager.php
	// No need for local duplicates

	function findStudentByNumber($con, $studentNumber, $campid) {
		$studentNumber = trim($studentNumber);
		if ($studentNumber === '') { return null; }
		$table = mapCampusToTable($campid);
		if ($table === null) { return null; }
		$t = str_replace("`", "", $table);
		$stud = mysqli_real_escape_string($con, $studentNumber);
	$sql = "SELECT `lname`, `fname` FROM `{$t}` WHERE `stud_num`='".$stud."' LIMIT 1";
	$res = @mysqli_query($con, $sql);
	if ($res && ($row = mysqli_fetch_assoc($res))) {
		$lname = isset($row['lname']) ? trim($row['lname']) : '';
		$fname = isset($row['fname']) ? trim($row['fname']) : '';
		$name = trim($fname . (($fname !== '' && $lname !== '') ? ' ' : '') . $lname);
		return ($name !== '') ? $name : null;
	}
		return null;
	}


	// Handle AJAX verification request
	if (isset($_POST["verify_student"])) {
		// Clear any previous output
		while (ob_get_level()) {
			ob_end_clean();
		}
		
		// Set proper headers
		header('Content-Type: application/json');
		
		$studno = isset($_POST['studentno']) ? trim($_POST['studentno']) : '';
		$campid = isset($_POST['campid']) ? $_POST['campid'] : '';
		
		// Validate inputs
		if ($studno === '') {
			echo json_encode(['success' => false, 'message' => 'Student number is required.']);
			exit;
		}
		
		if ($campid === '') {
			echo json_encode(['success' => false, 'message' => 'Campus selection is required.']);
			exit;
		}
		
		$table = mapCampusToTable($campid);
		
		if ($table === null) {
			echo json_encode(['success' => false, 'message' => 'Invalid campus selected.']);
			exit;
		}
		
		if (!tableExists($con, $table)) {
			echo json_encode(['success' => false, 'message' => 'Campus database not available.']);
			exit;
		}
		
		// Enable error reporting for debugging
		error_reporting(E_ALL);
		ini_set('display_errors', 0);
		
		try {
			$studentName = findStudentByNumber($con, $studno, $campid);
			
			if ($studentName && $studno !== '') {
				// Ensure the name is properly encoded for JSON
				$studentName = mb_convert_encoding($studentName, 'UTF-8', 'UTF-8');
				$jsonResponse = json_encode(['success' => true, 'name' => $studentName, 'message' => 'Student verified successfully!']);
				
				if ($jsonResponse === false) {
					echo json_encode(['success' => false, 'message' => 'Error encoding student data. Please contact support.']);
					error_log("JSON encode error for student: " . $studno . " - " . json_last_error_msg());
				} else {
					echo $jsonResponse;
				}
			} else {
				echo json_encode(['success' => false, 'message' => 'Student number not found. Please check your student number and campus selection.']);
			}
		} catch (Exception $e) {
			echo json_encode(['success' => false, 'message' => 'An error occurred while verifying student. Please try again.']);
			error_log("Student verification error for " . $studno . ": " . $e->getMessage());
		}
		
		exit;
	}

	if (isset($_POST["btnsubmit"])) {
		date_default_timezone_set("Asia/Manila");
		$transid = $_POST["campid"] ."_". date("HismdY");
		$studno = isset($_POST['studentno']) ? trim($_POST['studentno']) : '';
		$campid = isset($_POST['campid']) ? $_POST['campid'] : '';
		$table = mapCampusToTable($campid);
		if ($table === null) {
			$err = "We couldn't verify the student number. Please review your campus and student number, then try again.";
		} else if (!tableExists($con, $table)) {
			$err = "We couldn't verify the student number. Please review your campus and student number, then try again.";
		} else {
			$studentName = findStudentByNumber($con, $studno, $campid);
			if ($studentName && $studno !== '') {
				header("Location: payment_oldstud.php?payee=".urlencode($studentName)."&transid=$transid&studentno=".urlencode($studno));
				die;
			} else {
				$err = "We couldn't verify the student number. Please review your campus and student number, then try again.";
			}
		}
	}
?>	

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>UPHSL Online Payment - Current Students</title>
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
      <a href="enrolled" class="pay-nav-link active">Enrolled</a>
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
    <span style="color:var(--muted);font-weight:600; font-size:12px">Enrolled Students • Student No</span>
  </div>
  <?php if (isset($err)): ?><div class="alert err" style="margin-bottom:12px"><i class="fa-solid fa-triangle-exclamation"></i><div><?php echo htmlspecialchars($err); ?></div></div><?php endif; ?>
  <div class="form-card">
    <div class="form-head">
      <i class="fa-solid fa-id-card"></i>
      <div>
        <div style="font-weight:800;font-size:18px">Enrolled Student Payment (Legacy)</div>
        <div style="opacity:.9;font-size:13px">Student number verification per campus. UI refreshed, backend same - filename guestold_student.php unchanged.</div>
      </div>
    </div>
    <div class="form-body">
      <form method="post" id="studentForm">
        <div class="field">
          <label for="campid">Select Your Campus</label>
          <select name="campid" id="campid" required onchange="resetVerification()">
            <option value="">Choose your campus...</option>
            <option value="UPHB" <?php echo ($selected_campus === 'UPHB') ? 'selected' : ''; ?>>Binan Campus</option>
            <option value="UPHMU" <?php echo ($selected_campus === 'UPHMU') ? 'selected' : ''; ?>>Medical University</option>
            <option value="UPHG" <?php echo ($selected_campus === 'UPHG') ? 'selected' : ''; ?>>GMA Campus</option>
            <option value="UPHM" <?php echo ($selected_campus === 'UPHM') ? 'selected' : ''; ?>>Manila Campus</option>
            <option value="PHCP" <?php echo ($selected_campus === 'PHCP') ? 'selected' : ''; ?>>Pangasinan Campus</option>
            <option value="UPHI" <?php echo ($selected_campus === 'UPHI') ? 'selected' : ''; ?>>Isabela Campus</option>
            <option value="UPHR" <?php echo ($selected_campus === 'UPHR') ? 'selected' : ''; ?>>Roxas Campus</option>
          </select>
        </div>
        <div id="verification-section" style="display:none">
          <div style="background:var(--bg);border:1px solid var(--line);border-radius:14px;padding:16px;margin-top:8px">
            <h4 style="margin:0 0 8px;color:var(--blue)"><i class="fa-solid fa-magnifying-glass"></i> Verify Student Number</h4>
            <div id="submit-help" style="text-align:center;color:var(--muted);font-size:13px;margin-bottom:10px">Please verify your student number before proceeding</div>
            <div class="verify-box">
              <div class="field" style="margin:0"><label for="studentno">Student Number</label><input type="text" name="studentno" id="studentno" maxlength="50" placeholder="Enter student number" oninput="resetVerification()" required></div>
              <button type="button" id="verifyBtn" onclick="verifyStudent()" class="btn-verify"><i class="fa-solid fa-magnifying-glass"></i> Verify</button>
            </div>
            <div id="verification-result" style="margin-top:12px"></div>
          </div>
        </div>
        <div id="submit-section" style="display:none;margin-top:14px">
          <button type="submit" name="btnsubmit" id="btnsubmit" class="btn btn-primary" style="width:100%;padding:16px;font-size:16px" disabled><i class="fa-solid fa-credit-card"></i> Proceed to Payment</button>
        </div>
      </form>
      <div class="alert info"><i class="fa-solid fa-circle-info"></i><div><strong>Note:</strong> Student number is verified against campus tables via mapCampusToTable().</div></div>
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
<script>
var isStudentVerified = false;
function toggleSubmit(){
  var s = document.getElementById('studentno');
  var btn = document.getElementById('btnsubmit');
  var submitSection = document.getElementById('submit-section');
  if (isStudentVerified && s.value.trim() !== '') {
    submitSection.style.display = 'block';
    btn.disabled = false;
  } else {
    submitSection.style.display = 'none';
    btn.disabled = true;
  }
}
function resetVerification(){
  var campid = document.getElementById('campid').value;
  var verificationSection = document.getElementById('verification-section');
  if (campid !== '') {
    verificationSection.style.display = 'block';
    var url=new URL(window.location.href); url.searchParams.set('campus',campid); history.replaceState({},'',url);
  } else {
    verificationSection.style.display = 'none';
    var url=new URL(window.location.href); url.searchParams.delete('campus'); history.replaceState({},'',url);
  }
  isStudentVerified = false;
  document.getElementById('verification-result').innerHTML = '';
  document.getElementById('submit-help').innerHTML = 'Please verify your student number before proceeding';
  toggleSubmit();
}
function confirmStudent(){
  isStudentVerified = true;
  document.getElementById('submit-help').innerHTML = '&#10003; Student confirmed! You may now proceed with payment.';
  toggleSubmit();
  setTimeout(function(){
    var submitBtn = document.getElementById('btnsubmit');
    if (submitBtn) submitBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }, 100);
}
function rejectStudent(){
  isStudentVerified = false;
  document.getElementById('studentno').value = '';
  document.getElementById('verification-result').innerHTML = '';
  document.getElementById('submit-help').innerHTML = 'Please verify your student number before proceeding';
  toggleSubmit();
  document.getElementById('verification-section').style.display = 'none';
  document.getElementById('campid').value = '';
}
function verifyStudent(){
  var studentno = document.getElementById('studentno').value.trim();
  var campid = document.getElementById('campid').value;
  var resultDiv = document.getElementById('verification-result');
  var verifyBtn = document.getElementById('verifyBtn');
  if (studentno === '') { alert('Please enter a student number first.'); return; }
  if (campid === '') { alert('Please select a campus first.'); return; }
  verifyBtn.disabled = true;
  verifyBtn.innerHTML = 'Verifying...';
  resultDiv.innerHTML = '<div class="alert info">Verifying student...</div>';
  var formData = new FormData();
  formData.append('verify_student', '1');
  formData.append('studentno', studentno);
  formData.append('campid', campid);
  fetch('', { method: 'POST', body: formData })
  .then(response => {
    if (!response.ok) throw new Error('Server returned ' + response.status);
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) throw new Error('Server did not return JSON');
    return response.text().then(text => { if (!text || text.trim() === '') throw new Error('Server returned empty response'); return JSON.parse(text); });
  })
  .then(data => {
    if (data.success) {
      var initials = data.name.trim().split(/\s+/).slice(0,2).map(w=>w[0]).join('').toUpperCase().substring(0,2) || 'x';
      var campLabel = document.getElementById('campid').selectedOptions[0]?.text || campid;
      resultDiv.innerHTML = '<div class="verify-card">'
        +'<div class="verify-card-top ok"><div class="verify-icon"><i class="fa-solid fa-check"></i></div><div><div class="verify-title">Verified</div><div class="verify-subtitle">'+data.message+'</div></div></div>'
        +'<div class="verify-card-body"><div class="verify-profile"><div class="verify-avatar">'+initials+'</div><div><div class="verify-name">'+data.name+'</div><div class="verify-sub">Is this you?</div></div></div><div class="verify-meta"><span>'+studentno+'</span><span>'+campLabel+'</span></div></div>'
        +'<div class="verify-actions"><button type="button" onclick="confirmStudent()" class="btn btn-primary"><i class="fa-solid fa-check"></i> Yes</button><button type="button" onclick="rejectStudent()" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i> No</button></div>'
      +'</div>';
      document.getElementById('submit-help').innerHTML = 'Please confirm the student name above to proceed';
      toggleSubmit();
      setTimeout(function(){ if (resultDiv) resultDiv.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 100);
    } else {
      isStudentVerified = false;
      resultDiv.innerHTML = '<div class="verify-card"><div class="verify-card-top err"><div class="verify-icon"><i class="fa-solid fa-circle-xmark"></i></div><div><div class="verify-title">Failed</div><div class="verify-subtitle">Verification failed</div></div></div><div class="verify-card-body"><div class="verify-hint">'+data.message+'</div></div></div>';
      document.getElementById('submit-help').innerHTML = 'Please verify your student number before proceeding';
      toggleSubmit();
      setTimeout(function(){ if (resultDiv) resultDiv.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 100);
    }
  })
  .catch(error => {
    isStudentVerified = false;
    resultDiv.innerHTML = '<div class="verify-card"><div class="verify-card-top err"><div class="verify-icon"><i class="fa-solid fa-circle-xmark"></i></div><div><div class="verify-title">Error</div><div class="verify-subtitle">Verification failed</div></div></div><div class="verify-card-body"><div class="verify-hint">Error verifying student. Please try again.</div></div></div>';
    document.getElementById('submit-help').innerHTML = 'Please verify your student number before proceeding';
    console.error('Verification Error:', error);
    toggleSubmit();
    setTimeout(function(){ if (resultDiv) resultDiv.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 100);
  })
  .finally(() => {
    verifyBtn.disabled = false;
    verifyBtn.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i> Verify';
  });
}
document.addEventListener('DOMContentLoaded', function() {
  var campid = document.getElementById('campid').value;
  var verificationSection = document.getElementById('verification-section');
  if (campid !== '' && verificationSection) verificationSection.style.display = 'block';
});
</script>
</body>
</html>
