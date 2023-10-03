<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
<head>
    <title>Update Manager </title>
    <link href="../../include/styles.css" rel="stylesheet" type="text/css">
	<script language='javascript'>
		function goBack() {
			document.location = '../../admin/toolmaster.php';
		}
	</script>
</head>

<body id='body_bgcolor'>
<?

include "clientclass.php";

$client= new updateClient('.');

$client->GetUpdates();

include "../update.php";

?>

    <table width='100%' height='100%' cellpadding='0' cellspacing='0'>
    <tr><td valign='center' align='center' class='normaltext'>
        Updates complete...
        <br><br>
        <input type='button' name='action' value='Back' class='settings_button' onclick='javascript:goBack()'>
    </td></tr>
    </table>

</body>
</html>