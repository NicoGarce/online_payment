<?php 
include "dbconnect.php";

$curdate = date("Y-m-d"); 

if (isset($_GET["refno"])) {
  // Use prepared statement to prevent SQL injection
  $stmt = mysqli_prepare($con, "UPDATE return_data SET receipt_no=?, postdate=NOW() WHERE refno=?");
  if ($stmt) {
    $orno = $_GET["orno"] ?? '';
    $refno = $_GET["refno"] ?? '';
    mysqli_stmt_bind_param($stmt, "ss", $orno, $refno);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
  } else {
    $result = false;
  }
}

//if (isset($_GET["verifycsv"])) {

//	$file = fopen("transaction.csv","r");
//	while(! feof($file))
//	  {
//	  //print_r(fgetcsv($file));echo "<br>";
//		if (fgetcsv($file)[1]=="S") {
//		   echo fgetcsv($file)[1];echo "<br>";
//		}
//	  }
//	fclose($file);

//}

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>UPHSL Online Payment</title>
<link rel="icon" type="image/png" href="images/logo.png">
<link rel="shortcut icon" type="image/png" href="images/logo.png">
	
<script>
	function getor(refno) {
	   var orno = prompt("Enter OR No. for Reference No.: "+refno);
	   if (confirm("If you are sure that this OR number is correct, click OK")==true ) {
		   window.location = "transaction.php?refno="+refno+"&orno="+orno;
	   };
	}
	
	function verifycsv() {
		window.location = "transaction.php?verifycsv=1";
	}
</script>	
	
</head>

<body style="font-family: Gotham, 'Helvetica Neue', Helvetica, Arial, 'sans-serif'">
<table width="100%" border="0" cellspacing="2" cellpadding="2" align="center">
	<tr>
		<td valign="top">
			<table width="100%" border="0" cellspacing="2" cellpadding="2" align="center">
  <tbody>
    <tr>
      <td height="106" colspan="8" align="center" style="font-size: 30px; background-color:blue; color: white"><p>University of Perpetual Help System Laguna<br>
        <font size="4px">Binan Campus and Medical University</font></p></td>
    </tr>
<form name="frmmain" method="post">
    <tr>
      <td height="79"  colspan="5" align="center" ><u><strong>Online Payment Transaction List</strong></u></td>
      <td height="79" align="left" colspan="2">Starting Date: <input type="text" name="dfrom" size="12" maxlength="12" value="<?php if(isset($_POST["dfrom"])) {echo htmlspecialchars($_POST["dfrom"], ENT_QUOTES, 'UTF-8');} else {echo htmlspecialchars($curdate, ENT_QUOTES, 'UTF-8');} ?>" > <font size="2px">(yyyy-mm-dd)</font><br>Ending Date: <input type="text" name="dto" size="12" maxlength="12" value="<?php if(isset($_POST["dto"])) {echo htmlspecialchars($_POST["dto"], ENT_QUOTES, 'UTF-8');} else {echo htmlspecialchars($curdate, ENT_QUOTES, 'UTF-8');} ?>" > <font size="2px">(yyyy-mm-dd)</font></td>
      <td height="79" align="left"><input type="submit" name="btnrefresh" value="Refresh List" style="padding: 5px; background-color: green; color: white; border-radius: 5px;"></td>
    </tr>
 </form>	  
    <tr bgcolor="#649DB5" style="color: white">
	  <td align="center"><strong>Transaction Date</strong></td>	
      <td align="center"><strong>Transaction No</strong></td>
      <td align="center"><strong>Reference No</strong></td>
      <td align="center"><strong>Status</strong></td>
      <td align="center"><strong>Amount</strong></td>
      <td align="center"><strong>Message</strong></td>
	  <td align="center"><strong>OR No.</strong></td>
	  <td align="center"><strong>Set Date</strong></td>
    </tr>
 <?php 
	// Use prepared statement to prevent SQL injection
	$sql = "SELECT * FROM return_data WHERE (txnid LIKE 'UPHB_%' OR txnid LIKE 'UPHMU_%') AND status='Success'";
	$params = [];
	$types = "";
	
	if(isset($_POST["dfrom"]) && isset($_POST["dto"])) {
		$sql .= " AND transdate BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY) AND transdate IS NOT NULL";
		$params[] = $_POST["dfrom"];
		$params[] = $_POST["dto"];
		$types .= "ss";
	}
	
	$sql .= " ORDER BY transdate";
	
	$stmt = mysqli_prepare($con, $sql);
	if ($stmt && !empty($params)) {
		mysqli_stmt_bind_param($stmt, $types, ...$params);
		mysqli_stmt_execute($stmt);
		$result = mysqli_stmt_get_result($stmt);
	} elseif ($stmt) {
		mysqli_stmt_execute($stmt);
		$result = mysqli_stmt_get_result($stmt);
	} else {
		$result = mysqli_query($con, $sql);
	}
	$cnt=0;
	while ($r=mysqli_fetch_array($result)) {
  ?>	  
    <tr <?php 
		if($cnt==0) {
	      $cnt=1; 
		} else {
			$cnt=0;
			 }
		if (is_null($r["receipt_no"]) ) {
			  echo " bgcolor='#87F0DB' ";
		  } else {
			if ($cnt==1) {
			  echo " bgcolor='#E0DEDE' ";
			}
		  }
		?>>
	  <td align="left" style="font-size: 12px"><?php echo htmlspecialchars($r["transdate"] ?? '', ENT_QUOTES, 'UTF-8');?></td>
      <td align="left" style="font-size: 12px"><?php echo htmlspecialchars($r["txnid"] ?? '', ENT_QUOTES, 'UTF-8');?></td>
      <td align="center" style="font-size: 12px"><?php echo htmlspecialchars($r["refno"] ?? '', ENT_QUOTES, 'UTF-8');?></td>
      <td align="center" style="font-size: 12px <?php if($r["status"]=='Success') ?>"><?php echo htmlspecialchars($r["status"] ?? '', ENT_QUOTES, 'UTF-8');?></td>
      <td align="center" style="font-size: 12px"><?php echo htmlspecialchars($r["amount"] ?? '', ENT_QUOTES, 'UTF-8');?></td>
      <td align="center" style="font-size: 12px"><?php echo htmlspecialchars($r["message"] ?? '', ENT_QUOTES, 'UTF-8');?></td>
	  <td align="center" style="font-size: 12px"><?php if (is_null($r["receipt_no"])) {?> 
		  <a href="javascript:getor(<?php echo json_encode($r["refno"] ?? ''); ?>)">Set</a>
		  <?php } else { echo htmlspecialchars($r["receipt_no"], ENT_QUOTES, 'UTF-8'); } ?>
	  </td>
	<td align="center" style="font-size: 12px"><?php echo htmlspecialchars($r["postdate"] ?? '', ENT_QUOTES, 'UTF-8');?></td>	
    </tr>
<?php 	
	}  
  ?>
  </tbody>
</table>
		</td>
	</tr>
</table>	
	

	
	
</body>
</html>