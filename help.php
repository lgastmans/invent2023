<?
  session_start();
  if (!empty($_GET['showindex'])) {
	session_unregister('str_context_help');
  }
?>
<html>
<head>
	<link href="include/styles.css" rel="stylesheet" type="text/css">
</head>
<body bgcolor='#FFFED8'>
<?

   if (!empty($_SESSION['str_context_help'])) {
	include($_SESSION['str_context_help']);
	die();
  }

?>
Welcome to the online help!
<br>
Click a link below to find out more: <p>
Stock<br>
Purchase<br>
</body>
</html>
	