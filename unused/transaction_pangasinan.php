<?php 
include "dbconnect.php";

$curdate = date("Y-m-d"); 

if (isset($_GET["refno"])) {
  $sql = "update return_data set receipt_no ='".$_GET["orno"]."',postdate=now() where refno = '".$_GET["refno"]."'";	   
  $result = mysqli_query($con, $sql);
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
	
<script>
	function getor(refno) {
	   var orno = prompt("Enter OR No. for Reference No.: "+refno);
	   if (confirm("If you are sure that this OR number is correct, click OK")==true ) {
		   window.location = "transaction_pangasinan.php?refno="+refno+"&orno="+orno;
	   };
	}
	
	function verifycsv() {
		window.location = "transaction_pangasinan.php?verifycsv=1";
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
        <font size="4px">Pangasinan Campus</font></p></td>
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
	$add = "";  
	if(isset($_POST["dfrom"])) {
	 $add = " and transdate between '".$_POST["dfrom"]."' and date_add('".$_POST["dto"]."', interval 1 day) and transdate is not null "	;
	} 
	
	$sql = "select * from return_data where txnid like 'PHCP%' and status='Success' ".$add." order by transdate";	   
    $result = mysqli_query($con, $sql);
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
	  <td align="left" style="font-size: 12px"><?php echo $r["transdate"];?></td>
      <td align="left" style="font-size: 12px"><?php echo $r["txnid"];?></td>
      <td align="center" style="font-size: 12px"><?php echo $r["refno"];?></td>
      <td align="center" style="font-size: 12px <?php if($r["status"]=='Success') ?>"><?php echo $r["status"];?></td>
      <td align="center" style="font-size: 12px"><?php echo $r["amount"];?></td>
      <td align="center" style="font-size: 12px"><?php echo $r["message"];?></td>
	  <td align="center" style="font-size: 12px"><?php if (is_null($r["receipt_no"])) {?> 
		  <a href="javascript:getor('<?php echo $r["refno"]; ?>')">Set</a>
		  <?php } else { echo $r["receipt_no"]; } ?>
	  </td>
	<td align="center" style="font-size: 12px"><?php echo $r["postdate"];?></td>	
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