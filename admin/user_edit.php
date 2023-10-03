<?
require_once("../include/const.inc.php");
require_once("session.inc.php");
require_once("db.inc.php");
require_once("../common/functions.inc.php");

//***
//  check permissions
//***
$bool_can_modify_record = false;
if ($_SESSION["int_user_type"] > 1) {
	$bool_can_modify_record = true;
}

$str_message = '';

$int_id = 0;
if (IsSet($_GET["id"]))
	$int_id = $_GET['id'];

if (IsSet($_POST['action'])) {
	if ($_POST['action'] == 'save') {
	
		$str_can_change_price = 'N';
		if (IsSet($_POST['can_change_price']))
			$str_can_change_price = 'Y';
			
		$str_can_change_bill_date = 'N';
		if (IsSet($_POST['can_change_bill_date']))
			$str_can_change_bill_date = 'Y';
			
		$str_can_edit_batch = 'N';
		if (IsSet($_POST['can_edit_batch']))
			$str_can_edit_batch = 'Y';
		
		$_SESSION['str_user_supplier_access'] = $_POST['selected_suppliers'];
		
		if (IsSet($_POST['id'])) {
			if ($bool_can_modify_record)
				$str_query = "
					UPDATE user
					SET username = '".$_POST['username']."',
						user_type = '".$_POST['user_type']."',
						default_storeroom_id = ".$_POST['default_storeroom_id'].",
						po_prediction_method = ".$_POST['po_prediction_method'].",
						printing_type = ".$_POST['printing_type'].",
						can_change_price = '$str_can_change_price',
						can_change_bill_date = '$str_can_change_bill_date',
						can_edit_batch = '$str_can_edit_batch',
						supplier_access = '".$_POST['selected_suppliers']."'
					WHERE user_id = ".$_POST['id']."
				";
			else
				$str_query = "
					UPDATE user
					SET username = '".$_POST['username']."',
						default_storeroom_id = ".$_POST['default_storeroom_id'].",
						po_prediction_method = ".$_POST['po_prediction_method'].",
						printing_type = ".$_POST['printing_type']."
					WHERE user_id = ".$_POST['id']."
				";
			

			$qry = new Query($str_query);
			
			if ($qry->b_error == true) {
				$str_message = 'Error updating user details';
				echo $str_query;
				die();
			}
			else {
				/*
				echo "<script language='javascript'>\n;";
				echo "if (top.window.opener)\n";
				echo "top.window.opener.document.location=top.window.opener.document.location.href;\n";
				echo "top.window.close();\n";
				echo "</script>";
				*/
			}
		}
		else {
			$str_query = "
				INSERT INTO user
				(
					username,
					password,
					user_type,
					default_storeroom_id,
					po_prediction_method,
					printing_type,
					can_change_price,
					can_change_bill_date,
					can_edit_batch,
					supplier_access
				)
				VALUES (
					'".$_POST['username']."',
					'".base64_encode($_POST['username'])."',
					".$_POST['user_type'].",
					".$_POST['default_storeroom_id'].",
					".$_POST['po_prediction_method'].",
					".$_POST['printing_type'].",
					'$str_can_change_price',
					'$str_can_change_bill_date',
					'$str_can_edit_batch',
					'".$_POST['selected_suppliers']."'
				)
			";
			$qry = new Query($str_query);
			
			if ($qry->b_error == true) {
				$str_message = 'Error updating user details';
				echo $str_query;
				die();
			}
			else {
				echo "<script language='javascript'>\n;";
				echo "alert('A password set to the username has been created.');\n";
				echo "if (top.window.opener)\n";
				echo "top.window.opener.document.location=top.window.opener.document.location.href;\n";
				echo "top.window.close();\n";
				echo "</script>\n";
			}
		}
	}
}


$str_username = '';
$user_type = 1;
$int_default_storeroom_id = 0;
$int_po_prediction_method = 1;
$int_printing_type = 1;
$str_can_change_price = 'N';
$str_can_change_bill_date = 'N';
$str_can_edit_batch = 'N';
$arr_supplier_access = array();

if ($int_id > 0) {
	$str_query = "
		SELECT *
		FROM user
		WHERE user_id = $int_id
	";
	$qry = new Query($str_query);
	if ($qry->RowCount() > 0) {
		$str_username = $qry->FieldByName('username');
		$user_type = $qry->FieldByName('user_type');
		$int_default_storeroom_id = $qry->FieldByName('default_storeroom_id');
		$int_po_prediction_method = $qry->FieldByName('po_prediction_method');
		$int_printing_type = $qry->FieldByName('printing_type');
		$str_can_change_price = $qry->FieldByName('can_change_price');
		$str_can_change_bill_date = $qry->FieldByName('can_change_bill_date');
		$str_can_edit_batch = $qry->FieldByName('can_edit_batch');
		$arr_supplier_access = explode(',', $qry->FieldByName('supplier_access'));
	}
}

/*
	list of storerooms
*/
$qry_storerooms = new Query("
	SELECT *
	FROM stock_storeroom
	ORDER BY description
");

?>

<html>
<head><TITLE></TITLE>
	<script src="../include/js/jquery-1.3.2.js" type="text/javascript"></script>
	<script src="../include/js/jquery.bgiframe.min.js" type="text/javascript"></script>
	<script src="../include/js/jquery.multiSelect.js" type="text/javascript"></script>
	<link href="../include/js/jquery.multiSelect.css" rel="stylesheet" type="text/css" />
	
	<script type="text/javascript">
		$(document).ready( function() {
			// Default options
			$("#supplier_access").multiSelect();
			
			// Show test data
			$("FORM").submit( function() {
				var results = $(this).serialize().replace(/&/g, '\n');
				results = decodeURI(results);
				alert(results);
				return false;
			});
			
		});
	</script>
	
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	<script language="javascript">
		
		function getSelectedSuppliers() {
			var myArray = [];
			$(".multiSelectOptions input[type=checkbox]:checked").each( function( intIndex ){
				myArray.push( $(this).val() );
			});
			return myArray;
		}
		
		function saveData() {
			var can_save = true;
			var oSuppliers = document.getElementById('selected_suppliers');
			
			if (can_save) {
				arrSuppliers = getSelectedSuppliers();
				oSuppliers.value = arrSuppliers;
				document.user_edit.submit();
			}
		}
	
		function closeWindow() {
			if (top.window.opener)
				top.window.opener.document.location=top.window.opener.document.location.href;
			top.window.close();
		}
	</script>
</head>

<body id='body_bgcolor' marginwidth=5 marginheight=5>

<form name='user_edit' action="" method='POST'>
<?
	if ($int_id > 0)
		echo "<input type='hidden' name='id' value='".$int_id."'>";
	if (!$bool_can_modify_record) {
		echo "<input type='hidden' name='user_type' value='$user_type'>";
	}
	echo "<input type='hidden' id='selected_suppliers' name='selected_suppliers' value=''>";
	
	if ($str_message != '')  { ?>
		<script language='javascript'>
		alert('<?echo $str_message?>');
		</script>
<?	}

//===================
// bounding box start
//-------------------
?>
<table width='100%' height='90%' border='0' >
<tr>
	<td align='center' valign='center'>
	
<?
	boundingBoxStart("400", "../images/blank.gif");
	
//===================
?>

<table width='100%' cellpadding="5" cellspacing="0">
	<tr>
		<td width='160px' align="right" class='normaltext_bold'>Username:</td>
		<td>
			<input type='text' name='username' value='<?echo $str_username?>' class='input_200'>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>User Type:</td>
		<td>
			<select name='user_type' class='select_200' <?if (!$bool_can_modify_record) echo "disabled";?>>
				<option value='1' <?if ($user_type == 1) echo 'selected';?> >Normal</option>
				<option value='2' <?if ($user_type == 2) echo 'selected';?> >Admin</option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>Default Storeroom:</td>
		<td>
			<select name='default_storeroom_id' class='select_200'>
			<?
				for ($i=0; $i<$qry_storerooms->RowCount(); $i++) {
					if ($qry_storerooms->FieldByName('storeroom_id') == $int_default_storeroom_id)
						echo "<option value=".$qry_storerooms->FieldByName('storeroom_id')." selected>".$qry_storerooms->FieldByName('description');
					else
						echo "<option value=".$qry_storerooms->FieldByName('storeroom_id').">".$qry_storerooms->FieldByName('description');
					$qry_storerooms->Next();
				}
			?>
			</select>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>Supplier Access:</td>
		<td>
			<select id="supplier_access" name="supplier_access" multiple="multiple" size="5">
			<?php
				$arr_suppliers = getAllSuppliers();
				foreach ($arr_suppliers as $key=>$value) {
					if (in_array($key,$arr_supplier_access))
						echo "<option value=\"$key\" \"selected\">$value</option>\n";
					else
						echo "<option value=\"$key\">$value</option>\n";
				}
			?>
			</select>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>Prediction Method:</td>
		<td>
			<select name='po_prediction_method' class='select_200'>
				<option value='<? echo PO_PREDICT_NONE ?>' <?if ($int_po_prediction_method == PO_PREDICT_NONE) echo 'selected';?> >None</option>
				<option value='<? echo PO_PREDICT_PREVIOUS ?>' <?if ($int_po_prediction_method == PO_PREDICT_PREVIOUS) echo 'selected';?> >Previous</option>
				<option value='<? echo PO_PREDICT_PREVIOUS_CURRENT ?>' <?if ($int_po_prediction_method == PO_PREDICT_PREVIOUS_CURRENT) echo 'selected';?> >Previous and Current</option>
				<option value='<? echo PO_PREDICT_CURRENT ?>' <?if ($int_po_prediction_method == PO_PREDICT_CURRENT) echo 'selected';?> >Current</option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="right" class='normaltext_bold'>Printer:</td>
		<td>
			<select name='printing_type' class='select_200'>
				<option value='1' <?if ($int_printing_type == 1) echo 'selected';?> >Local printer</option>
				<option value='2' <?if ($int_printing_type == 2) echo 'selected';?> >Network printer</option>
			</select>
		</td>
	</tr>
	<tr>
		<td align='right'></td>
		<td>
			<label class='normaltext_bold'>
				<input type='checkbox' name='can_change_price' <?if ($str_can_change_price == 'Y') echo "checked";?> <?if (!$bool_can_modify_record) echo "disabled";?>>
				Can change prices
			</label>
		</td>
	</tr>
	<tr>
		<td align='right'></td>
		<td>
			<label class='normaltext_bold'>
				<input type='checkbox' name='can_change_bill_date' <?if ($str_can_change_bill_date == 'Y') echo "checked";?> <?if (!$bool_can_modify_record) echo "disabled";?>>
				Can change bill date
			</label>
		</td>
	</tr>
	<tr>
		<td align='right'></td>
		<td>
			<label class='normaltext_bold'>
				<input type='checkbox' name='can_edit_batch' <?if ($str_can_edit_batch == 'Y') echo "checked";?> <?if (!$bool_can_modify_record) echo "disabled";?>>
				Can edit batch details
			</label>
		</td>
	</tr>
</table>

<table cellpadding="3" cellspacing="0" border='0'>
	<tr>
		<td>
			<input type='hidden' name='action' value='save'>
			<input type="button" class="settings_button" name="button_save" value="Save" onclick="javascript:saveData()">
		</td>
		<td>
			<input type="button" name="button_close" value="Close" class="settings_button" onclick="closeWindow()">
		</td>
		<td>&nbsp;</td>
	</tr>
</table>

<?
//=================
// bounding box end
//-----------------
    boundingBoxEnd("400", "../images/blank.gif");
?>
</td></tr>
</table>
<?
//===================
?>
</form>
</body>
</html>
