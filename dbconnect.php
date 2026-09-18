<?php    
$con = mysqli_connect("localhost","root","","uphsledu_onlinepayment");    
if(!$con){  	echo "FAILED TO CONNECT";  }  else {  	echo "";  }  
mysqli_set_charset($con, "utf8mb4");
?>