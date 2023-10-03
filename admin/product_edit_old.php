<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/functions.inc.php");
	
	$int_id = -1;
	if (IsSet($_GET['id'])) {
		$int_id = $_GET['id'];
	}
	
	$qry_settings = new Query("
		SELECT admin_product_type
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION['int_current_storeroom']."
	");
	
?>

<html>
<head><TITLE></TITLE>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	<script language="javascript">
		function saveData() {
			window.frames['basic_frame'].saveData();
			if (window.frames['extended_frame'])
				window.frames['extended_frame'].saveData();
		}
	
		function CloseWindow() {
			if (top.window.opener)
				top.window.opener.document.location=top.window.opener.document.location.href;
			top.window.close();
		}
	</script>
</head>
<body id='body_bgcolor' leftmargin=7 topmargin=7 marginwidth=15 marginheight=15>
<form name='product_edit' method='POST'>
<?
	if ($int_id > -1)
		echo "<input type='hidden' name='id' value='".$int_id."'>";

//===================
// bounding box start
//-------------------
?>
<table width='100%' height='90%' border='0' >
<tr>
	<td align='center' valign='center'>
	
<?
	boundingBoxStart("930", "../images/blank.gif");

//===================
?>


<table width='100%' cellpadding="3" cellspacing="0" border='0'>
	<tr>
		<td width='400px' valign='top'>
			<iframe id='basic_frame' name='basic_frame' src='product_basic.php?id=<?echo $int_id?>' width='100%' height='500px' frameborder="0"></iframe>
		</td>
		<td width='400px' valign='top'>
			<? if ($qry_settings->FieldByName('admin_product_type') == 2) { ?>
				<iframe id='extended_frame' name='extended_frame' src='product_consumable.php?id=<?echo $int_id?>' width='100%' height='500px' frameborder="0"></iframe>
			<? } else if ($qry_settings->FieldByName('admin_product_type') == 3) { ?>
				<iframe id='extended_frame' name='extended_frame' src='product_book.php?id=<?echo $int_id?>' width='100%' height='500px' frameborder="0"></iframe>
			<? } ?>
		</td>
	</tr>
</table>

<table width='100%' cellpadding="3" cellspacing="0" border='0'>
	<tr>
		<td align='right'>
			<input type="button" class="settings_button" name="button_save" value="Save" onclick="javascript:saveData()">
		</td>
		<td>
			<input type="button" name="button_close" value="Close" class="settings_button" onclick="CloseWindow()">
		</td>
	</tr>
</table>


<?
//=================
// bounding box end
//-----------------
    boundingBoxEnd("930", "../images/blank.gif");
?>
</td></tr>
</table>
<?
//===================
?>

</form>
</body>
</html>