<?php

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

	

	//echo explode("_",$tid)[1];	

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

	  

	//$tid=$tid."_".$total;

    

	$sql = "insert into return_data (txnid) values('$tid')";	   

    $result = mysqli_query($con, $sql); 

	  

    // Check for set values.

    foreach ($fields as $key => $value) {

      // Sanitize user input. However:

      // NOTE: this is a sample, user's SHOULD NOT be inputting these values.

      if (isset($_POST[$key])) {

          $parameters[$key] = filter_input(INPUT_POST, $key, $value['filter'],

            array_reduce($value['filter_flags'], function ($a, $b) { return $a | $b; }, 0));

      }

    }



    // Validate values.

    // Example, amount validation.

    // Do not rely on browser validation as the client can manually send

    // invalid values, or be using old browsers.

    if (!is_numeric($parameters['amount'])) {

      $errors[] = 'Amount should be a number.';

    }

    else if ($parameters['amount'] <= 0) {

      $errors[] = 'Amount should be greater than 0.';

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

      }

      else {header("Location: $url");exit(); 

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

	function updatedesc(i) {

		var ind = i;

	if (ind=="0")	{	

	   document.getElementById("description").value="";

	   document.getElementById("description").value=(document.getElementById("payee_name").value).toUpperCase()+" >> "+document.getElementById("descselect").value+", "+document.getElementById("syfrom").value+", "+document.getElementById("sem").value;

	}

	

	if (ind=="2")	{	

	   tempdesc = document.getElementById("description").value;

	   document.getElementById("description").value="";

	   document.getElementById("description").value=(document.getElementById("payee_name").value).toUpperCase()+" >> "+tempdesc;

	   document.getElementById("isdesc").value=1;

	}

		

	if (ind=="1")	{

		 document.getElementById("description").value="";

	   document.getElementById("description").value=(document.getElementById("payee_name").value).toUpperCase()+" >> "+document.getElementById("descselect").value+", "+document.getElementById("syfrom").value+", "+document.getElementById("sem").value;

		document.getElementById("isdesc").value=1;

	}		

	}

	function checkifmaydesc() {

	  //if (document.getElementById("isdesc").value==1) {return true;} else {alert("You forgot to select the particular description of your transaction.");return false;}

	}

	</script>	

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

				?><input type="text" name="description" id="description" <?php if (trim($_GET["payee"])!="") { ?> readonly <?php } ?> style="padding: 10px; font-size: 14px; background-color: #97ECE3" size="128" maxlength="128" required><br><span style="font-size: 12px">

				 <?php if (trim($_GET["payee"])=="") {echo "( Please select payment Particulars/Description below)";} else {echo "( Please select from Particulars )";} ?>

				 </span><br>

				 <input type="hidden" name="isdesc" id="isdesc" value="">

			    <strong>



					<?php 
					//if (trim($_GET["payee"])!="") 
					{ ?>

					<br>					

				Particulars :</strong>

<select name="descselect" id="descselect" required style="padding: 10px; font-size: 14px" onChange="updatedesc('1')">

				    <option value="none selected"></option>					
<?php
 if (trim($_GET["payee"])!="")  {
?>
				    <option value="DOWNPAYMENT">DOWNPAYMENT</option>					

				    <option value="TUITION FEE">TUITION FEE</option>				       
					<option value="BACK ACCOUNT">BACK ACCOUNT</option>
<?php
 } 
?>
				    <option value="RESERVATION FEE">RESERVATION FEE</option>				       

				    <option value="OTHER FEE NOT ON LIST">OTHER FEE NOT ON LIST</option>

				    <option values='ACTIVITY FEE'>ACTIVITY FEE</option>

					 <option values='ADDING/DROPPING FEE'>ADDING/DROPPING FEE</option>

					 <option values='ALUMNI ASSOCIATION MEMBERSIP'>ALUMNI ASSOCIATION MEMBERSIP</option>

					 <option values='AUTHENTICATION'>AUTHENTICATION</option>

					 <option values='BAR UNIFORM'>BAR UNIFORM</option>

					 <option values='BASIC OCCUPATIONAL SAFETY & HEALTH'>BASIC OCCUPATIONAL SAFETY & HEALTH</option>

					 <option values='BASIC TRAINING'>BASIC TRAINING</option>

					 <option values='CAV'>CAV</option>

					 <option values='CERTIFICATE OF BASIC TRAINING'>CERTIFICATE OF BASIC TRAINING</option>

					 <option values='CERTIFICATION'>CERTIFICATION</option>

					 <option values='CHANGE'>CHANGE</option>

					 <option values='CHEF UNIFORM'>CHEF UNIFORM</option>

					 <option values='CLASS PICTURE'>CLASS PICTURE</option>

					 <option values='COMMUNIT OUTREACH PROGRAM'>COMMUNIT OUTREACH PROGRAM</option>

					 <option values='COMPLETION FORM'>COMPLETION FORM</option>

					 <option values='COUNCIL FEE'>COUNCIL FEE</option>

					 <option values='COURIER SERVICES'>COURIER SERVICES</option>

					 <option values='DEPARTMENT DAYS'>DEPARTMENT DAYS</option>

					 <option values='DIPLOMA'>DIPLOMA</option>

					 <option values='E LEARNING'>E LEARNING</option>

					 <option values='EMPLOYEES UNIFORM'>EMPLOYEES UNIFORM</option>

					 <option values='ENRICHMENT FEE'>ENRICHMENT FEE</option>

					 <option values='ENTRANCE EXAM - SHS GRADE 11'>ENTRANCE EXAM - SHS GRADE 11</option>

					 <option values='ESC GRANTEES'>ESC GRANTEES</option>

					 <option values='E-THESIS FEE'>E-THESIS FEE</option>

					 <option values='FORM 137'>FORM 137</option>

					 <option values='GALA UNIFORM'>GALA UNIFORM</option>

					 <option values='GRADUATION FEE'>GRADUATION FEE</option>

					 <option values='GRADUATION PIN'>GRADUATION PIN</option>

					 <option values='HOOD W/ MEDALLON'>HOOD W/ MEDALLON</option>

					 <option values='IT ERA FEE'>IT ERA FEE</option>

					 <option values='RESEARCH FEE'>RESEARCH FEE</option>

					 <option values='RECOLLECTION FEE'>RECOLLECTION FEE</option>

					 <option values='ROBOTICS'>ROBOTICS</option>

					 <option values='SCAPULAR'>SCAPULAR</option>

					 <option values='SPECIAL EXAM'>SPECIAL EXAM</option>

					 <option values='THESIS REQUIREMENTS'>THESIS REQUIREMENTS</option>

					 <option values='TRANSCRIPT OF RECORDS'>TRANSCRIPT OF RECORDS</option>

					 <option values='USC-BLUE TSHIRT'>USC-BLUE TSHIRT</option>

					 <option values='USC-COLLEGE BLOUSE'>USC-COLLEGE BLOUSE</option>

					 <option values='USC-COLLEGE POLO'>USC-COLLEGE POLO</option>

					 <option values='USC-DEPT. SHIRT'>USC-DEPT. SHIRT</option>

					 <option values='USC-ENG'G PANTS'>USC-ENG'G PANTS</option>

					 <option values='USC-ENROLLMENT KIT'>USC-ENROLLMENT KIT</option>

					 <option values='USC-ID LACE W/ PROTECTOR'>USC-ID LACE W/ PROTECTOR</option>

					 <option values='USC-JACKET'>USC-JACKET</option>

					 <option values='USC-JOGGING PANTS'>USC-JOGGING PANTS</option>

					 <option values='USC-MR BAG'>USC-MR BAG</option>

					 <option values='USC-NAMEPLATE'>USC-NAMEPLATE</option>

					 <option values='USC-NSTP T-SHIRT'>USC-NSTP T-SHIRT</option>

					 <option values='USC-PARAPHERNALIA'>USC-PARAPHERNALIA</option>

					 <option values='USC-PE T-SHIRT'>USC-PE T-SHIRT</option>

					 <option values='USC-PE UNIFORM'>USC-PE UNIFORM</option>

					 <option values='USC-SHOES'>USC-SHOES</option>

					 <option values='USC-SKIRT'>USC-SKIRT</option>

					 <option values='USC-STICKER'>USC-STICKER</option>

					 <option values='USC-STUDENT POLO SHIRT'>USC-STUDENT POLO SHIRT</option>

					 <option values='USC-STUDENT T-SHIRT'>USC-STUDENT T-SHIRT</option>

					 <option values='USC-UNIFORM'>USC-UNIFORM</option>

					 <option values='U-WEEK - AVIATION'>U-WEEK - AVIATION</option>

					 <option values='U-WEEK - BASIC EDUCATION'>U-WEEK - BASIC EDUCATION</option>

					 <option values='U-WEEK - CAS'>U-WEEK - CAS</option>

					 <option values='YEARBOOK'>YEARBOOK</option>



				 </select><br>

				 <strong>For School Year:</strong>

<select name="syfrom" id="syfrom" style="padding: 10px; font-size: 14px" onChange="updatedesc('0')">

  <?php $d=date("Y"); while ($d>=1980) {$c=$d+1; ?> 

  <option value="<?php echo $d."-".$c;?>"><?php echo $d."-".$c;?></option>

  <?php $d=$d-1; } ?> 

</select><br>



<strong>For Semester:</strong>

<select name="sem" id="sem" style="padding: 10px; font-size: 14px" onChange="updatedesc('0')">

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

		  <td align="right"><strong>Student Name<br>(please type your name legibly)</strong></td>

		  <td><input type="text" name="payee_name" id="payee_name" style="padding: 10px; font-size: 14px" size="80" maxlength="80" required <?php if (isset($_GET["payee"]) && trim($_GET["payee"])!="" ) {?> onChange="updatedesc('0')" <?php } ?> <?php if (isset($_GET["payee"]) && trim($_GET["payee"])=="" ) {?> onChange="updatedesc('2')" <?php } ?> value="<?php if (isset($_GET["payee"])) {echo $_GET["payee"];} ?>" <?php if (isset($_GET["payee"]) && trim($_GET["payee"])!="" ) {echo " readonly ";}?>></td>

	      </tr> 

		</table>		

      

    

		</td>

    </tr>

	 

	</table> 

	

	<input type="submit" name="submit" value="Pay Now" style="padding: 20px; background-color:green; color: white; width: 100%; font-size: 14px;" onMouseOver="javascript:this.style.backgroundColor = '#ED822F';" onMouseOut="javascript:this.style.backgroundColor = '#008000';">

	<!--<div style="background-color:#008000"></div>-->

	</form>

	

	

  

</body>

</html>