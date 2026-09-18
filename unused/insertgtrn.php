<?php
include "dbconnect.php";
 if (isset($_POST["btnsubmit"])) {
      $sql="insert into tblgtrn values('".$_POST["locno"]."','".$_POST["gtrn"]."',1)";
	  if (mysqli_query($con, $sql)) {
	  echo "<div>&nbsp;</div><div>Record Sucessfully Inserted.</div>";
	  }	else {
	  echo "<div>&nbsp;</div><div>Insert Error.</div>";
	  }
	  die;
 }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>GTRN</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<body>
<div>&nbsp;</div>    
<div align="center" style="font-size:20px; font-family:arial;font-weigth:bold">ONLINE PAYMENT TRANSACTION REQUEST</div>    
<div>&nbsp;</div>
<form method="post">
<div>&nbsp;</div>
<div align="center" style="padding:10px; font-family:Arial, Helvetica, sans-serif; font-size:20px; background-color:blue; color:#FFFFFF ">Enter Locator no. for New Student or Student ID for Old Student: <input style="text-align:center" type="text" name="locno" id="locno" required>&nbsp;</div>
<div align="center" style="padding:10px; font-family:Arial, Helvetica, sans-serif; font-size:20px; background-color:blue; color:#FFFFFF ">Generated Transaction No. : <input style="text-align:center" type="text" name="gtrn" id="gtrn" readonly="true" value="
<?php
echo generateDateBasedCode();
?>">&nbsp;<input type="submit" name="btnsubmit" value=" Submit "></div>
<div>&nbsp;</div>
</form>
</body>
</html>

<?php
function generateDateBasedCode() {
    // Prefix: today's date (e.g. 250822 for 2025-08-22)
    $date = date("ymd");

    // Random 3 letters
    $letters = strtoupper(substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 3));

    // Random 3 digits
    $numbers = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);

    return $date . "-" . $letters . $numbers;
}
?>