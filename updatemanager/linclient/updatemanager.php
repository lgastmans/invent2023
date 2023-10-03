<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
<head>
	<link href="../../include/styles.css" rel="stylesheet" type="text/css">
	<title>Update Manager </title>
	<script language='javascript'>
		function goBack() {
			document.location = '../../admin/toolmaster.php';
		}
	</script>
</head>
<body id='body_bgcolor' leftmargin='20px' topmargin='20px'>
<?

include "clientclass.php";
include "../../include/const.inc.php";
$client= new updateClient('.');

?>

	<table width='100%' height='500px' cellpadding='0' cellspacing='0'>
	<tr><td valign='center' align='center'>

<?
if ($client->CheckUpdates() ) {
	echo "<font class='normaltext'>New updates are available...</font><br><br>";
?>
	<FORM action="getUpdate.php" method="POST" target="_self" >
	<INPUT type="submit" value="Update" class='settings_button'>
	<input type='button' name='action' value='Back' class='settings_button' onclick='javascript:goBack()'>
	</FORM>
<?
}
else {
	if ($client->badlogin) {
		echo "<font class='normaltext' style='color:red;'>Could not access the server for the updates.<br>Invalid username and/or password.</font>";
	}
	else {
		echo "<font class='normaltext'>Your application is upto date</font>";
	}
	echo "<br><br><input type='button' name='action' value='Back' class='settings_button' onclick='javascript:goBack()'>";
}
?>

	</td></tr>
	</table>

</body>
</html>