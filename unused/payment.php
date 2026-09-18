<?php
session_start();
require_once __DIR__ . '/../app/config/paths.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/includes/functions.php';
// Ensure $base_path is available (fallback to parent folder)
$base_path = isset($GLOBALS['base_path']) ? $GLOBALS['base_path'] : '../';

// Check if this sub-page or Online Payment section is in maintenance
if (isSectionInMaintenance('online-payment', 'payment') || isSectionInMaintenance('online-payment')) {
    $maintenance_message = getSectionMaintenanceMessage('online-payment', null, 'payment');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Online Payment - Maintenance</title>
        <style>
            body { font-family: Arial, sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; background:#f5f5f5 }
            .maintenance-container{ text-align:center; max-width:600px; padding:3rem; background:white; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.1) }
            .btn{ display:inline-block; padding:.75rem 1.5rem; background:#1c4da1; color:white; text-decoration:none; border-radius:8px; font-weight:600 }
        </style>
    </head>
    <body>
        <div class="maintenance-container">
            <h1>Under Maintenance</h1>
            <p><?php echo htmlspecialchars($maintenance_message); ?></p>
            <a href="../index.php" class="btn">Go to Homepage</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

ob_start();

include "dbconnect.php";

/**
 * Do not forget to set these to your Account credentials.
 * It would be better to store these as an admin setting.
 **/

define('MERCHANT_ID', 'UPHSLI');
define('MERCHANT_PASSWORD', 'uSw92BkgTsVRqZT');

define('ENV_TEST', 0);
define('ENV_LIVE', 1);

//$environment = ENV_TEST;
$environment = ENV_LIVE;



?>


  <?php


$sql = "select (count(*)+1) as total from return_data where txnid like '%".$_GET["transid"]."%'";   
    $result = mysqli_query($con, $sql);
    $total=0;
    while ($r=mysqli_fetch_array($result)) {
      $total+=$r["total"];
    }

if (isset($_GET["transid"])) {
    $tid = $_GET["transid"];
    if (trim($_GET["payee"])!="") {
      $tid=$tid."_".$total;
    }
} else {$tid = "";}

  $errors = array();
  $is_link = false;

  $parameters = array(
      'merchantid' => MERCHANT_ID,
      'txnid' => $tid,
      'amount' => 0,
      'ccy' => 'PHP',
      'description' => '',
      'email' => '',
  );

  // Detect guest flow when locator is present (originated from guest.php)
  $is_guest_flow = (isset($_GET['locno']) && trim($_GET['locno']) !== '');

  $fields = array(
      'txnid' => array(
          'label' => 'Transaction ID',
          'type' => 'text',
          'attributes' => array(),
          'filter' => FILTER_SANITIZE_STRING,
          'filter_flags' => array(FILTER_FLAG_STRIP_LOW),
      ),

      'amount' => array(
          'label' => 'Amount',
          'type' => 'number',
          'attributes' => array('step="0.01"'),
          'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
          'filter_flags' => array(FILTER_FLAG_ALLOW_THOUSAND, FILTER_FLAG_ALLOW_FRACTION),
      ),

      'description' => array(
          'label' => 'Payment Description',
          'type' => 'text',
          'attributes' => array(),
          'filter' => FILTER_SANITIZE_STRING,
          'filter_flags' => array(FILTER_FLAG_STRIP_LOW),
      ),

      'email' => array(
          'label' => 'Email',
          'type' => 'email',
          'attributes' => array(),
          'filter' => FILTER_SANITIZE_EMAIL,
          'filter_flags' => array(),
      ),
  );


  if (isset($_POST['submit'])) {

    $sql = "insert into return_data (txnid) values('$tid')";     
    $result = mysqli_query($con, $sql); 

    // Check for set values.
    foreach ($fields as $key => $value) {
      if (isset($_POST[$key])) {
          $parameters[$key] = filter_input(INPUT_POST, $key, $value['filter'],
            array_reduce($value['filter_flags'], function ($a, $b) { return $a | $b; }, 0));
      }
    }

    // Build description server-side if not provided by the client. This ensures
    // the final `description` value includes payee name, school year and semester
    // even if JS did not run in the browser.
    if (empty($parameters['description'])) {
      $payee_name = (string) filter_input(INPUT_POST, 'payee_name', FILTER_SANITIZE_STRING);
      $desc_others = trim((string) filter_input(INPUT_POST, 'desc_others', FILTER_SANITIZE_STRING));
      $desc_select = trim((string) filter_input(INPUT_POST, 'descselect', FILTER_SANITIZE_STRING));
      $syfrom = trim((string) filter_input(INPUT_POST, 'syfrom', FILTER_SANITIZE_STRING));
      $sem = trim((string) filter_input(INPUT_POST, 'sem', FILTER_SANITIZE_STRING));
      $locno = trim((string) filter_input(INPUT_POST, 'locno', FILTER_SANITIZE_STRING));
      $desc_choice = '';
      if ($desc_others !== '') {
        $desc_choice = $desc_others;
      } elseif ($desc_select !== '') {
        $desc_choice = $desc_select;
      }
      if ($desc_choice !== '') {
        $desc_text = strtoupper($payee_name);
        if ($locno !== '') $desc_text .= ' (' . $locno . ')';
        $desc_text .= ' >> ' . $desc_choice;
        if ($syfrom !== '') $desc_text .= ', ' . $syfrom;
        if ($sem !== '') $desc_text .= ', ' . $sem;
        $parameters['description'] = $desc_text;
      } elseif ($is_guest_flow) {
        // For guest flow, construct a descriptive DOWNPAYMENT value that includes
        // the payer name, locator (if present), and SY/Sem so it is informative.
        $payee = strtoupper($payee_name);
        if ($payee !== '') {
            $desc_text = $payee;
            if ($locno !== '') $desc_text .= ' (' . $locno . ')';
            $desc_text .= ' >> DOWNPAYMENT';
            if ($syfrom !== '') $desc_text .= ', ' . $syfrom;
            if ($sem !== '') $desc_text .= ', ' . $sem;
            $parameters['description'] = $desc_text;
        } else {
            $parameters['description'] = 'DOWNPAYMENT';
        }
      }
    }

    // Validate values.
    if (!is_numeric($parameters['amount'])) {
      $errors[] = 'Amount should be a number.';
    }
    else if ($parameters['amount'] <= 0) {
      $errors[] = 'Amount should be greater than 0.';
    }

    if (empty($errors)) {
      // Transform amount to correct format. (2 decimal places)
      $parameters['amount'] = number_format($parameters['amount'], 2, '.', '');

      // Enforce DOWNPAYMENT server-side for guest flow to prevent client override
      if ($is_guest_flow && empty($parameters['description'])) {
        $parameters['description'] = 'DOWNPAYMENT';
      }

      // Unset later from parameter after digest.
      $parameters['key'] = MERCHANT_PASSWORD;
      $digest_string = implode(':', $parameters);
      unset($parameters['key']);

      $parameters['digest'] = sha1($digest_string);
      $url = 'https://gw.dragonpay.ph/Pay.aspx?';
      if ($environment == ENV_TEST) {
        $url = 'http://test.dragonpay.ph/Pay.aspx?';
      }

      $params = "&param1=".$parameters['amount']."&param2=".$parameters['description'];
      $url .= http_build_query($parameters, '', '&').$params;

      if ($is_link) {
        echo '<br><a href="' . $url . '">' . $url . '</a>';
      }
      else {header("Location: $url");exit(); }
    }
  }
  ?>


<!DOCTYPE html>
<html>
<head>
  <style>
  label {width: 130px;}
  input {width: 250px;}
  </style>

<script>
function updatedesc(i) {
  var ind = i;
  var descEl = document.getElementById('description');
  var descSelect = document.getElementById('descselect');
  var descOthers = document.getElementById('desc_others');
  var payeeEl = document.getElementById('payee_name');
  var locEl = document.getElementById('locno');
  var syEl = document.getElementById('syfrom');
  var semEl = document.getElementById('sem');

  // If both desc inputs are missing (guest flow), set DOWNPAYMENT and exit.
  if (!descSelect && !descOthers) {
    if (descEl) descEl.value = 'DOWNPAYMENT';
    return true;
  }

  var payee = payeeEl ? payeeEl.value.toUpperCase() : '';
  var loc = locEl ? locEl.value : '';
  var sy = syEl ? syEl.value : '';
  var sem = semEl ? semEl.value : '';

  if (ind == '0') {
    if (descEl && descSelect) {
      descEl.value = payee + ' (' + loc + ') >> ' + descSelect.value + ', ' + sy + ', ' + sem;
    }
  } else if (ind == '2') {
    var tempdesc = descEl ? descEl.value : '';
    if (descEl) descEl.value = payee + ' (' + loc + ') >> ' + tempdesc;
    if (document.getElementById('isdesc')) document.getElementById('isdesc').value = 1;
  } else if (ind == '1') {
    if (descEl) {
      if (descOthers && descOthers.value.trim() != '') {
        descEl.value = payee + ' (' + loc + ') >> ' + descOthers.value + ', ' + sy + ', ' + sem;
      } else if (descSelect) {
        descEl.value = payee + ' (' + loc + ') >> ' + descSelect.value + ', ' + sy + ', ' + sem;
      } else {
        descEl.value = 'DOWNPAYMENT';
      }
    }
    if (document.getElementById('isdesc')) document.getElementById('isdesc').value = 1;
  }
}

function checkifmaydesc() {
  // noop for guest flow; kept for compatibility
  return true;
}
</script>
	<link rel="icon" type="image/png" href="images/logo.png">
  <link rel="shortcut icon" type="image/png" href="images/logo.png">

  <link rel="icon" type="image/png" href="<?php echo $base_path; ?>assets/images/Logos/logo.png?v=2" sizes="32x32">
  <link rel="shortcut icon" href="<?php echo $base_path; ?>assets/images/Logos/logo.png?v=2" sizes="32x32">
  <link rel="apple-touch-icon" href="<?php echo $base_path; ?>assets/images/Logos/logo.png?v=2">

</head>

<body  style="font-family: Gotham, 'Helvetica Neue', Helvetica, Arial, 'sans-serif'; margin: 0px">

<form method="post" onSubmit="return checkifmaydesc()"> 
<div><img src="images/header.png" width="100%"></div>
	<table width="100%" border="0" cellspacing="10" cellpadding="5" align="center">
  <tbody>

    <tr>
      <td colspan="2" align="center">
<?php if (!empty($errors)): ?>
  <div class="errors">
    <div class="error">
    <?php echo implode('</div><div class="error">', $errors); ?>
    </div>
  </div>
  <?php endif; ?>

       <table width="100%" border="0" cellpadding="10" cellspacing="10" align="center">     
			<?php foreach ($fields as $key => $value): ?>
	   <tr>
		 <td width="50%" align="right" valign="top"><span class="label"><label for="<?php echo $key; ?>"><?php echo "<strong>".$value['label']."</strong>"; ?>:</label></span></td>  
		 <td>
			<?php 
		       if ($value['label']=='Payment Description') {
            if (!empty($is_guest_flow)) {
             // Guest flow: fixed particular DOWNPAYMENT (hidden input), display fixed label
            ?>
            <div style="padding:10px; font-size:14px; background-color:#f1f1f1; border-radius:6px;">Particulars: <strong>DOWNPAYMENT</strong></div>
             <?php
             // For guest payments, also allow selecting school year and semester again.
             ?>
             <br>
             <strong>For School Year:</strong>

  <select name="syfrom" id="syfrom" style="padding: 10px; font-size: 14px">
    <?php $d=date("Y"); while ($d>=1980) {$c=$d+1; ?> 
    <option value="<?php echo $d."-".$c;?>"><?php echo $d."-".$c;?></option>
    <?php $d=$d-1; } ?> 
  </select><br>

  <strong>For Semester:</strong>
  <select name="sem" id="sem" style="padding: 10px; font-size: 14px">
                    <option value="1st Sem">1st Sem</option>
                    <option value="2nd Sem">2nd Sem</option>
                    <option value="Summer">Summer</option>
                    <option value="Regular Semester ( for BED )">Regular Semester ( for BED )</option>
                  </select>
             <?php
           } else {
		           ?><input type="text" name="description" id="description" readonly="true" style="padding: 10px; font-size: 14px; background-color: #97ECE3" size="128" maxlength="128" required><br><span style="font-size: 12px">
		           <?php if (trim($_GET["payee"])=="") {echo "( Please select payment particulars/description below )";} else {echo "( Please select from Particulars )";} ?>
		            <br><lable style="color:blue">or if not on list, enter here <input type="text" name="desc_others" id="desc_others" placeholder=" enter here "> the description of the payment you will be paying for</lable>
		           </span><br>
		           <input type="hidden" name="isdesc" id="isdesc" value="">
		         <strong>
		           <br>                        
		         Particulars :</strong>
		  <select name="descselect" id="descselect" required style="padding: 10px; font-size: 14px">
		            <option value="DOWNPAYMENT">DOWNPAYMENT</option>
		  <?php
		   if (trim($_GET["payee"])!="")  {
		  ?>
		            <option value="TUITION FEE">TUITION FEE</option>                       
		            <option value="BACK ACCOUNT">BACK ACCOUNT</option>
		  <?php
		   } 
		  ?>
		            <option value="RESERVATION FEE (Basic Education)">RESERVATION FEE (Basic Education / Senior High School)</option>                       
				<option value="RESERVATION FEE (College)">RESERVATION FEE (College)</option>                       
		            <option values='ACTIVITY FEE'>ACTIVITY FEE</option>
		            <option values='ADDING/DROPPING FEE'>ADDING/DROPPING FEE</option>
		  </select><br>

		  <strong>For School Year:</strong>

<select name="syfrom" id="syfrom" style="padding: 10px; font-size: 14px">
  <?php $d=date("Y"); while ($d>=1980) {$c=$d+1; ?> 
  <option value="<?php echo $d."-".$c;?>"><?php echo $d."-".$c;?></option>
  <?php $d=$d-1; } ?> 
</select><br>

<strong>For Semester:</strong>
<select name="sem" id="sem" style="padding: 10px; font-size: 14px">
				  <option value="1st Sem">1st Sem</option>
				  <option value="2nd Sem">2nd Sem</option>
				  <option value="Summer">Summer</option>
				  <option value="Regular Semester ( for BED )">Regular Semester ( for BED )</option>
			    </select>

		<?php } // if payee is blank ?>
			<?php 
			   } else {
			 ?> 
			 <input type="<?php echo $value['type']; ?>"
			<?php echo implode(' ', $value['attributes']); ?>
			name="<?php echo $key; ?>" value="<?php echo $parameters[$key]; ?>" style="padding: 10px; font-size: 14px" <?php if($value['label']=='Transaction ID') {echo ' readonly  ';} ?> required >
			   <?php } ?>            
		      </td>    
		  </tr> 

		<?php endforeach; ?>
	  <tr>
	  <td align="right"><strong>Student Name<br>
	  </strong></td>

	  <td><input type="text" name="payee_name" id="payee_name" style="padding: 10px; font-size: 14px" size="80" maxlength="80" required value="<?php if (isset($_GET["payee"])) {echo htmlspecialchars($_GET["payee"], ENT_QUOTES, 'UTF-8');} ?>" > 
	  <input type="text" name="locno" id="locno" value="<?php echo htmlspecialchars($_GET["locno"] ?? '', ENT_QUOTES, 'UTF-8'); ?>" readonly="true" style="background-color:blue; color:white; text-align:center; font-weigth:bold">
	  <br>(please add a <strong>Contact No.</strong> for transaction reference) </td>

	      </tr> 
		</table>        

		</td>
    </tr>
	 
	</table> 
	
	<input type="submit" name="submit" value="Pay Now" style="padding: 20px; background-color:green; color: white; width: 100%; font-size: 14px;" onMouseOver="javascript:this.style.backgroundColor = '#ED822F';" onMouseOut="javascript:this.style.backgroundColor = '#008000';" onClick="updatedesc(1)">
</form>
</body>
</html>
