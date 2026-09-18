<?php
session_start();
ob_start();
//include "dbconnect.php"; 
if (isset("myform")) {
	$content = $_GET["myform"];
	echo $content;
}
end;
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>UPHSL Website Content Poster</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
 <!-- Include stylesheet -->
 <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
 
 <script type="text/javascript">
  function go()  { 
      if ((document.getElementById("editor").value).length == 0 ) {
        alert("Blank entry is not accepted.");return false;
      }

      // Get HTML content
      //var html = quill.root.innerHTML;
  
      // Copy HTML content in hidden form
      document.getElementById("quill-html").value =  html ;
  
      // Post form
      //document.getElementById("reloadonly").value="savethis";
      myform.submit();    
  }
 </script>
 
</head>

<body bgcolor="#FF99FF">
   <label style="color:white" class="mb-2"><strong>Type your details or copy and paste an image</strong></label>
	   <div>&nbsp;</div>
	   <form name="myform" method="get">
	   <div id="editor" style="height:300px; padding:5px; background-color:#FFFFFF"></div>
	   <div class="p-3 mb-5 mt-3 text-center">
	   <div>&nbsp;</div>
	   <input type="button" value="Save Content" id="btn-submit" name="btn-submit" onclick="go()" class="btn btn-primary btn-lg">
	   <div>&nbsp;</div>
	   </div>          
   </form>
   
   <!-- Include the Quill library -->
  <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
  
  <!-- Initialize Quill editor -->
  <script>
    //var quillA = new Quill('#editor2', {
    //  theme: 'snow',
  //"modules": {
      //"toolbar": false
  //}
//    });

    var quill = new Quill('#editor', {
      theme: 'snow'
    });

  </script>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
  
</body>
</html>
