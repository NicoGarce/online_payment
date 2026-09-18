<?php 
include "dbconnect.php";
$s = "";
if ($_POST["status"]=="S") {$s="Success";} 
if ($_POST["status"]=="F") {$s="Failure";} 
if ($_POST["status"]=="P") {$s="Pending";} 
if ($_POST["status"]=="U") {$s="Unknown";} 
if ($_POST["status"]=="R") {$s="Refund";} 
if ($_POST["status"]=="K") {$s="Chargeback";} 
if ($_POST["status"]=="V") {$s="Void";} 
if ($_POST["status"]=="A") {$s="Authorized";}

//$m = $_POST["param2"]." ||| ".$_POST["param1"];
//$m = $_POST["param2"];

$sql = "update return_data set status='".$s."', message='".$_POST["param2"]."' where refno='".$_POST["refno"]."'";	   
$result = mysqli_query($con, $sql)

?>
