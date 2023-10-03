<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
	$int_id = 0;
	if (IsSet($_GET['id']))
		$int_id = $_GET['id'];

	$qry = new Query("SELECT * FROM stock_product_type LIMIT 1");
	
	if (IsSet($_POST['action'])) {
		if ($_POST['action'] == 'save') {
			$int_id = $_POST['id'];
			
			$arr_values = array();
			
			foreach ($_POST as $key=>$value) {
				reset($arr_values);
				$arr_values = explode('_', $value);
				
				if ($arr_values[0] == 'sel') {
					//***
					// check if an entry already exists for this product
					//***
					$str_query = "
						SELECT *
						FROM stock_product_type
						WHERE product_id = $int_id
							AND stock_type_id = ".$arr_values[1];
					$qry->Query($str_query);
					
					if ($qry->RowCount() > 0) {
						//***
						// update
						//***
						$str_query = "
							UPDATE stock_product_type
							SET stock_type_description_id = ".$arr_values[2].",
								is_modified = 'Y'
							WHERE product_id = $int_id
								AND stock_type_id = ".$arr_values[1]."
						";
					}
					else {
						//***
						// insert
						//***
						$str_query = "
							INSERT INTO stock_product_type
							(
								product_id,
								stock_type_id,
								stock_type_description_id,
								is_modified
							)
							VALUES (
								$int_id,
								".$arr_values[1].",
								".$arr_values[2].",
								'Y'
							)
						";
					}
					$qry->Query($str_query);
				}
			}
			echo "<script language='javascript'>\n";
			echo "window.close();\n";
			echo "</script>\n";
		}
	}
	
	$str_query = "
		SELECT *
		FROM stock_product_type
		WHERE product_id = $int_id
		LIMIT 1
	";
	$qry_product = new Query($str_query);
	
	$str_query = "
		SELECT *
		FROM stock_type
		ORDER BY product_type
	";
	$qry_types = new Query($str_query);

	$qry_defs = new Query("SELECT * FROM stock_type_description LIMIT 1");
?>

<html>
<head><TITLE></TITLE>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	<script language="javascript">
		function saveData() {
			document.product_types_edit.submit();
		}
	
		function closeWindow() {
			if (top.window.opener)
				top.window.opener.document.location=top.window.opener.document.location.href;
			top.window.close();
		}
	</script>
</head>
<body id='body_bgcolor' marginwidth=5 marginheight=5>

<form name='product_types_edit' method='POST'>

<?
//===================
// bounding box start
//-------------------
?>
<table width='100%' height='90%' border='0' >
<tr>
	<td align='center' valign='top'>
	
<?
	boundingBoxStart("400", "../images/blank.gif");
//-------------------
// end bounding box
//===================

if ($int_id > -1)
	echo "<input type='hidden' name='id' value='".$int_id."'>";

?>

<table width='100%' cellpadding="3" cellspacing="0" border='0'>
<?
	for ($i=0;$i<$qry_types->RowCount();$i++) {
		echo "<tr>\n";
		echo "<td class='normaltext_bold'>".$qry_types->FieldByName('product_type')."</td>\n";
		echo "<td>\n";
		
			echo "<select name='".$qry_types->FieldByName('product_type')."' class='select_200'>\n";
			echo "<option value=0>--select--";
			
				$qry_defs->Query("
					SELECT *
					FROM stock_type_description
					WHERE stock_type_id = ".$qry_types->FieldByName('stock_type_id')."
					ORDER BY description
				");
				
				$tmp_id = 0;
				$qry_product->Query("
					SELECT *
					FROM stock_product_type
					WHERE product_id = $int_id
						AND stock_type_id = ".$qry_types->FieldByName('stock_type_id')."
				");
				if ($qry_product->RowCount() > 0) {
					$tmp_id = $qry_product->FieldByName('stock_type_description_id');
				}
				
				for ($j=0;$j<$qry_defs->RowCount();$j++) {
					$str_value = "sel_".$qry_types->FieldByName('stock_type_id')."_".$qry_defs->FieldByName('stock_type_description_id');
					if ($tmp_id == $qry_defs->FieldByName('stock_type_description_id'))
						echo "<option value='$str_value' selected>".$qry_defs->FieldByName('description')."</option>\n";
					else
						echo "<option value='$str_value'>".$qry_defs->FieldByName('description')."</option>\n";
					$qry_defs->Next();
				}
				
			echo "</select>\n";
			
		echo "</td>\n";
		echo "</tr>\n";

		$qry_types->Next();
	}
?>
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
    boundingBoxEnd("400", "../images/blank.gif");
?>
</td></tr>
</table>
<?
//-------------------
// end bounding box
//===================
?>

</body>
</html>