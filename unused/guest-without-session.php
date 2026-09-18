<?php 	
include "dbconnect.php";
 if (isset($_POST["btnsubmit"])) {
      $totalrec = 0;
      $sql="select count(*) as totalrec from tblgtrn where locno='".$_POST["locno"]."' and gtrno='".$_POST["gtrn"]."' and is_valid=1";
	  $result = mysqli_query($con, $sql);	   
	  while ($row = mysqli_fetch_array($result)) {	
	    $totalrec = $row["totalrec"];
	  }
     if ($totalrec>=1) {   
    date_default_timezone_set("Asia/Manila");
	//$transid = "UPHSL" . date("HismdY");
	$transid = $_POST["campid"] ."_". date("HismdY");
    //header("Location: payment.php?payee=&transid=$transid");
	header("Location: payment.php?payee=&transid=$transid&locno=".$_POST["locno"]."&gtrno=".$_POST["gtrn"]);
	 } else {
	?>
	 <div style="padding:20px; background-color:#FF0000; color:#FFFFFF; font-size:30px; font-weight:bold" align="center">Entered transaction reference no. does<br>not exist or is not validated</div>
<?php	
	 }
    die;
 }
?>	
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>UPHSL Online Payment (Perpetualites)</title>
</head>

<body style="font-family: Cambria, 'Hoefler Text', 'Liberation Serif', Times, 'Times New Roman', 'serif' ">
	<form method="post">
	<div align="center">
	<h2>University of Perpetual Help System</h2>	
	<h2 style="color: #12199C">ONLINE PAYMENT</h2><br>	
	<h1 style="color: green">Welcome Perpetualites!</h1>	
	<strong>Select the UPHSL Campus to where you will be requesting your documents</strong><br><br>
		<select name="campid" style="font-size: 14px; padding: 10px;">
			<option value="UPHB">Binan</option>
			<option value="UPHMU">Medical University</option>
			<option value="UPHG">GMA</option>
			<option value="UPHM">Manila</option>
			<option value="PHCP">Pangasinan</option>
			<option value="UPHI">Isabela</option>
			<option value="UPHR">Roxas</option>
		</select>
	</div>
	<div>&nbsp;</div>
	<div align="center"><strong>Enter Locator No. for New Student / Student ID for Old Student:</strong>	  
	  <input type="text" value="" maxlength="20" size="10" name="locno" id="locno" style="text-align:center" required></div><div>&nbsp;</div>
	<div align="center"><strong>Enter Sent Generated Transaction Reference No. :</strong>	  <input type="text" value="" maxlength="20" size="10" name="gtrn" id="gtrn" style="text-align:center" required></div>
	<div align="center" style="color:#0000FF">( Provided by your College/Department )</div><div>&nbsp;</div>
	<div align="center" style="padding: 20px;"><input type="submit" value="Submit" name="btnsubmit" style="padding: 10px; border-radius: 5px; background-color:green; color: white; width: 150px; font-size: 14px;" onMouseOver="javascript:this.style.backgroundColor = '#ED822F';" onMouseOut="javascript:this.style.backgroundColor = '#008000';"></div>
	</form>	
</body>
</html>