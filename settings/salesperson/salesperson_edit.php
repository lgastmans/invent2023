<?php
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");
	
	$int_id = 0;
	if (IsSet($_GET['id']))
		$int_id = $_GET['id'];

	$str_message = '';

	if (IsSet($_POST['action'])) {
		if ($_POST['action'] == 'save') {
			if (IsSet($_POST['id']))
				$int_id = $_POST['id'];
			
			$first = $_POST['first'];
			$last = $_POST['last'];
			$address = $_POST['address'];
			
			if ($int_id > 0) {
				//***
				// check duplicate entry
				//***
/*				$str_query = "
					SELECT *
					FROM stock_type
					WHERE product_type = '$str_product_type'
						AND stock_type_id <> $int_id
				";
				$qry = new Query($str_query);
				if ($qry->RowCount() > 0) {
					$str_message = 'Duplicate entry encountered';
				}*/
				
				$str_query = "
					UPDATE salespersons
					SET first = '$first',
						last = '$last',
						address = '$address'
					WHERE id = $int_id
				";
			}
			else {
				//***
				// check duplicate entry
				//***
/*				$str_query = "
					SELECT *
					FROM stock_type
					WHERE product_type = '$str_product_type'
				";
				$qry = new Query($str_query);
				if ($qry->RowCount() > 0) {
					$str_message = 'Duplicate entry encountered';
				}*/
				
				$str_query = "
					INSERT INTO salespersons
					(
						first,
						last,
						address
					)
					VALUES (
						'$first',
						'$last',
						'$address'
					)
				";
			}

			if ($str_message == '') {
				$qry = new Query($str_query);
				if ($qry->b_error == false) {
					echo "<script language='javascript'>\n";
					echo "if (top.window.opener)\n";
					echo "top.window.opener.document.location=top.window.opener.document.location.href;\n";
					echo "top.window.close();\n";
					echo "</script>\n";
				}
				else {
					echo $str_query;
				}
			}
			else {
				echo "<font color='red'>".$str_message."</font>";
			}
		}
	}
	
	$first = '';
	$last = '';
	$address = '';
	if ($int_id > 0) {
		$qry = new Query("
			SELECT *
			FROM salespersons
			WHERE id = $int_id
		");
		$first = $qry->FieldByName('first');
		$last = $qry->FieldByName('last');
		$address = $qry->FieldByName('address');
	}
?>

<html>
<head><TITLE></TITLE>
	<link rel="stylesheet" type="text/css" href="../../include/styles.css" />
	<script language="javascript">
		function trim(s) {
			return s.replace(/^\s+|\s+$/g, "");
		}
		
		function isEmpty(str){
			return (str == null) || (str.length == 0);
		}
		
		function saveData() {
			var oFirst = document.getElementById('first');
			var can_save = true;
			
			if (isEmpty(oFirst.value)) {
				can_save = false;
				alert('Value cannot be blank');
				oFirst.focus();
				return false;
			}
			
			if (can_save)
				document.forms[0].submit();
		}
	
		function closeWindow() {
			if (top.window.opener)
				top.window.opener.document.location=top.window.opener.document.location.href;
			top.window.close();
		}
	</script>
</head>
<body id='body_bgcolor' marginwidth=5 marginheight=5>

<form name='salesperson_edit' method='POST'>

<?
//===================
// bounding box start
//-------------------
?>
<table width='100%' height='90%' border='0' >
<tr>
	<td align='center' valign='top'>
	
<?
	boundingBoxStart("400", "../../images/blank.gif");
//-------------------
// end bounding box
//===================

if ($int_id > 0)
	echo "<input type='hidden' name='id' value='".$int_id."'>";

?>

<table width='100%' cellpadding="3" cellspacing="0" border='0'>
	<tr>
		<td class='normaltext_bold'>First</td>
		<td>
			<input type='text' name='first' id='first' value='<?echo $first?>' class='input_200' autocomplete='OFF'>
		</td>
	</tr>
	<tr>
		<td class='normaltext_bold'>Last</td>
		<td>
			<input type='text' name='last' id='last' value='<?echo $last?>' class='input_200' autocomplete='OFF'>
		</td>
	</tr>
	<tr>
		<td class='normaltext_bold'>Address</td>
		<td>
			<input type='text' name='address' id='address' value='<?echo $address?>' class='input_200' autocomplete='OFF'>
		</td>
	</tr>
	
	<tr>
		<td colspan='2'>
			<input type='hidden' name='action' value='save'>
			<input type='button' name='button_save' value='Save' class="settings_button" onclick="saveData()">
			<input type="button" name="button_close" value="Close" class="settings_button" onclick="closeWindow()">
		</td>
	</tr>
</table>

</form>

<?
//=================
// bounding box end
//-----------------
    boundingBoxEnd("400", "../../images/blank.gif");
?>
</td></tr>
</table>
<?
//-------------------
// end bounding box
//===================
?>

<script language='javascript'>
	var oProductType = document.forms[0].first;
	oProductType.focus();
</script>

</body>
</html>