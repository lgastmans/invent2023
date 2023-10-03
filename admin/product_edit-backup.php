<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/functions.inc.php");
	
	$bool_new = true;
	$int_id = -1;
	if (IsSet($_GET['id'])) {
		$int_id = $_GET['id'];
		$bool_new = false;
	}
	
	$qry_settings = new Query("
		SELECT admin_product_type
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION['int_current_storeroom']."
	");
	
?>

<html>
<head><TITLE></TITLE>

	<link href="../include/bootstrap-3.3.4-dist/css/bootstrap.min.css" rel="stylesheet">

	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	<script language="javascript">
		bool_new = <?if ($bool_new) echo "true"; else echo "false";?>;
		
		function editTypes(intID) {
			myWin = window.open("product_types_edit.php?id="+intID,'product_types','toolbar=no,location=no,directories=no,status=yes,fullscreen=no,menubar=no,scrollbars=yes,resizable=yes,width=450,height=600');
			myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 600/2));
			myWin.focus();
		}
		
		function saveData() {
			bool_success = window.frames['basic_frame'].saveData();
			if (bool_success) {
				if (window.frames['extended_frame'])
					bool_success = window.frames['extended_frame'].saveData();
			}
			
			/*
			if (bool_success) {
				if (!bool_new)
					CloseWindow();
			}
			*/
		}
	
		function CloseWindow() {
			if (top.window.opener)
				top.window.opener.document.location=top.window.opener.document.location.href;
			top.window.close();
		}
	</script>
</head>
<body id='body_bgcolor' topmargin="5" rightmargin="0" bottommargin="0" leftmargin="0">
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
			<iframe id='basic_frame' name='basic_frame' src='product_basic.php?id=<?echo $int_id?>' width='100%' height='525px' frameborder="0"></iframe>
		</td>

		<td width='400px' valign='top'>


			<ul class="nav nav-pills">
				<li role="presentation" class="active">
					<a id="consumables" href="#">Consumables</a>
				</li>
				<li role="presentation">
					<a id="books" href="#">Books</a>
				</li>
				<li role="presentation">
					<a id="web" href="#">Web</a>
				</li>
			</ul>


			<? if (($qry_settings->FieldByName('admin_product_type') == 2) && ($int_id > -1)) { ?>

				<iframe id='extended_frame' name='extended_frame' src='product_consumable.php?id=<?echo $int_id?>' width='100%' height='500px' frameborder="0"></iframe>

			<? } else if (($qry_settings->FieldByName('admin_product_type') == 3) && ($int_id > -1)) { ?>

				<iframe id='extended_frame' name='extended_frame' src='product_book.php?id=<?echo $int_id?>' width='100%' height='500px' frameborder="0"></iframe>

			<? } else if (($qry_settings->FieldByName('admin_product_type') == 4) && ($int_id > -1)) { ?>

				<iframe id='extended_frame' name='extended_frame' src='product_shradhanjali.php?id=<?echo $int_id?>' width='100%' height='500px' frameborder="0"></iframe>

			<? } else if ($int_id == -1) {?>

				these extra settings are available after saving

			<? } ?>
		</td>
	</tr>
</table>

<table cellpadding="3" cellspacing="0" border='0'>
	<tr>
		<td>
			<input type="button" class="settings_button" name="button_edit_types" value="Edit user-defined types" onclick="javascript:editTypes(<?echo $int_id?>)">
		</td>
		<td>
			<input type="button" class="settings_button" name="button_save" value="Save" onclick="javascript:saveData()">
		</td>
		<td>
			<input type="button" name="button_close" value="Close" class="settings_button" onclick="CloseWindow()">
		</td>
		<td>&nbsp;</td>
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


<script src="../include/js/jquery-3.2.1.min.js"></script>
<script src="../include/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>
<script src="../include/js/bootbox.min.js"></script>

<script>

    $( document ).ready(function() {

		$("#consumables").click(function() {
			parent.window.frames["content"].location.href = 'index_verification_tools.php?action=batches';
		});

		$("#books").click(function() {
			parent.window.frames["content"].location.href = 'index_verification_tools.php?action=batches';
		});

		$("#web").click(function() {
			parent.window.frames["content"].location.href = 'index_verification_tools.php?action=batches';
		});

    });

</script>

</body>
</html>