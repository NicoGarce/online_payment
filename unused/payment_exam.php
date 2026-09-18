<?php
session_start();

// Check maintenance before any output
require_once '../app/config/database.php';
require_once '../app/includes/functions.php';

// Check if this sub-page or Online Payment section is in maintenance
if (isSectionInMaintenance('online-payment', 'guest-exam') || isSectionInMaintenance('online-payment')) {
    $maintenance_message = getSectionMaintenanceMessage('online-payment', null, 'guest-exam');
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

ob_start();

include "dbconnect.php";
include "campus_fees.php";

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
} else {
    $tid = "";
}

$errors = array();
$is_link = false;

// Course amounts are now included from campus_fees.php

$parameters = array(
    'merchantid' => MERCHANT_ID,
    'txnid' => $tid,
    'amount' => 0,
    'ccy' => 'PHP',
    'description' => 'ENTRANCE EXAM',
    'email' => '',
);

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
        'attributes' => array('step="0.01"', 'readonly'),
        'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
        'filter_flags' => array(FILTER_FLAG_ALLOW_THOUSAND, FILTER_FLAG_ALLOW_FRACTION),
    ),
    'description' => array(
        'label' => 'Payment Description',
        'type' => 'text',
        'attributes' => array('readonly'),
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

    // Validate values.
    if (!is_numeric($parameters['amount'])) {
        $errors[] = 'Amount should be a number.';
    } else if ($parameters['amount'] <= 0) {
        $errors[] = 'Amount should be greater than 0.';
    }
    
    // Validate amount against campus-specific course amounts
    $campus = '';
    if (isset($_GET['transid'])) {
        $campus = explode('_', $_GET['transid'])[0];
    }
    $selectedCourse = isset($_POST['course_select']) ? $_POST['course_select'] : '';
    
    if ($campus && $selectedCourse && isset($course_amounts[$campus][$selectedCourse])) {
        $expectedAmount = $course_amounts[$campus][$selectedCourse];
        if (abs($parameters['amount'] - $expectedAmount) > 0.01) {
            $errors[] = 'Amount does not match the expected fee for the selected course program.';
        }
    }

    if (empty($errors)) {
        // Transform amount to correct format. (2 decimal places,
        // decimal separated by period, no thousands separator)
        $parameters['amount'] = number_format($parameters['amount'], 2, '.', '');
        // Unset later from parameter after digest.
        $parameters['key'] = MERCHANT_PASSWORD;
        $digest_string = implode(':', $parameters);
        unset($parameters['key']);
        // NOTE: To check for invalid digest errors,
        // uncomment this to see the digest string generated for computation.
        // var_dump($digest_string); $is_link = true;
        $parameters['digest'] = sha1($digest_string);
        $url = 'https://gw.dragonpay.ph/Pay.aspx?';
        if ($environment == ENV_TEST) {
            $url = 'http://test.dragonpay.ph/Pay.aspx?';
        }

        $params = "&param1=".$parameters['amount']."&param2=".$parameters['description'];
        $url .= http_build_query($parameters, '', '&').$params;

        if ($is_link) {
            echo '<br><a href="' . $url . '">' . $url . '</a>';
        } else {
            header("Location: $url");
            exit(); 
        }
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
        <?php echo getCourseAmountsJS(); ?>
        
        function updateAmount() {
            var courseSelect = document.getElementById('course_select');
            var amountInput = document.getElementById('amount');
            var selectedCourse = courseSelect.value;
            
            // Get campus from transaction ID
            var txnId = document.getElementById('txnid').value;
            var campus = '';
            if (txnId) {
                campus = txnId.split('_')[0];
            }
            
            if (selectedCourse && campus && courseAmounts[campus] && courseAmounts[campus][selectedCourse]) {
                amountInput.value = courseAmounts[campus][selectedCourse].toFixed(2);
            } else {
                amountInput.value = '0.00';
            }
        }
    </script> 
    <link rel="icon" type="image/png" href="images/logo.png">
    <link rel="shortcut icon" type="image/png" href="images/logo.png">
</head>

<body style="font-family: Gotham, 'Helvetica Neue', Helvetica, Arial, 'sans-serif'; margin: 0px">

<form method="post"> 
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
        <?php if ($value['label']=='Amount'): ?>
            <!-- Course selection above amount -->
            <tr>
                <td align="right"><strong>Course Program:</strong></td>
                <td>
                    <select name="course_select" id="course_select" onchange="updateAmount()" style="padding: 10px; font-size: 14px; width: 250px;" required>
                        <option value="">Select Course Program</option>
                        <option value="Baccalaureate">Baccalaureate</option>
                        <option value="Graduate School">Graduate School</option>
                        <option value="Law/Juris Doctor">Law/Juris Doctor</option>
                        <option value="Basic Education">Basic Education</option>
                    </select>
                </td>
            </tr>
        <?php endif; ?>
        
        <tr>
            <td width="50%" align="right" valign="top">
                <span class="label">
                    <label for="<?php echo $key; ?>"><?php echo "<strong>".$value['label']."</strong>"; ?>:</label>
                </span>
            </td>  
            <td>
                <?php if ($value['label']=='Payment Description'): ?>
                    <input type="text" name="description" id="description" readonly="true" style="padding: 10px; font-size: 14px; background-color: #97ECE3" size="128" maxlength="128" value="ENTRANCE EXAM" required>
                <?php else: ?>
                    <input type="<?php echo $value['type']; ?>"
                    <?php echo implode(' ', $value['attributes']); ?>
                    name="<?php echo $key; ?>" id="<?php echo $key; ?>" value="<?php echo htmlspecialchars($parameters[$key]); ?>" style="padding: 10px; font-size: 14px" <?php if($value['label']=='Transaction ID') {echo ' readonly  ';} ?> required >
                <?php endif; ?>           
            </td>    
        </tr> 
    <?php endforeach; ?>

    <tr>
        <td align="right"><strong>Student Name:</strong></td>
        <td>
            <input type="text" name="payee_name" id="payee_name" style="padding: 10px; font-size: 14px" size="80" maxlength="80" required value="<?php if (isset($_GET["payee"])) {echo htmlspecialchars($_GET["payee"], ENT_QUOTES, 'UTF-8'); } ?>" > 
            (please add a <strong>Contact No.</strong> for transaction reference)<br>
            <span style="color: #666; font-size: 12px;">Example: Juan Dela Cruz / 09123456789</span>
        </td>
    </tr> 
</table>     

        </td>
    </tr>
</table> 

<input type="submit" name="submit" value="Pay Now" style="padding: 20px; background-color:green; color: white; width: 100%; font-size: 14px;" onMouseOver="this.style.backgroundColor = '#ED822F';" onMouseOut="this.style.backgroundColor = '#008000';">

</form>

</body>
</html>