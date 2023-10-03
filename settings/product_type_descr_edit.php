<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
	$int_product_type_id = 0;
	if (IsSet($_GET['product_type_id']))
		$int_product_type_id = $_GET['product_type_id'];

	$int_id = 0;
	if (IsSet($_GET['id']))
		$int_id = $_GET['id'];

	$str_message = '';

	if (IsSet($_POST['action'])) {
		if ($_POST['action'] == 'save') {
			if (IsSet($_POST['id']))
				$int_id = $_POST['id'];
			
			if (IsSet($_POST['product_type_id']))
				$int_product_type_id = $_POST['product_type_id'];
				
			$str_description = $_POST['description'];
			
			if ($int_id > 0) {
				//***
				// edit existing record
				//***
				// check duplicate entry
				//***
				$str_query = "SELECT stock_type_id FROM stock_type_description WHERE stock_type_description_id = $int_id";
				$qry = new Query($str_query);
				$int_stock_type_id = $qry->FieldByName('stock_type_id');
				
				$str_query = "
					SELECT *
					FROM stock_type_description
					WHERE stock_type_id = $int_stock_type_id
						AND description = '$str_description'
						AND stock_type_description_id <> $int_id
				";
				$qry = new Query($str_query);
				if ($qry->RowCount() > 0) {
					$str_message = 'Duplicate entry encountered';
				}
				
				$str_query = "
					UPDATE stock_type_description
					SET description = '$str_description',
						is_modified = 'Y'
					WHERE stock_type_description_id = $int_id
				";
			}
			else if ($int_product_type_id > 0) {
				//***
				// insert a new record
				//***
				// check duplicate entry
				//***
				$str_query = "
					SELECT *
					FROM stock_type_description
					WHERE stock_type_id = $int_product_type_id
						AND description = '$str_description'
				";
				$qry = new Query($str_query);
				if ($qry->RowCount() > 0) {
					$str_message = 'Duplicate entry encountered';
				}
				
				$str_query = "
					INSERT INTO stock_type_description
					(
						stock_type_id,
						description,
						is_modified
					)
					VALUES (
						$int_product_type_id,
						'$str_description',
						'Y'
					)
				";
			}

			if ($str_message == '') {
				$qry->Query($str_query);
				if ($qry->b_error == false) {
					echo "<script language='javascript'>";
					echo "if (top.window.opener)\n";
					echo "top.window.opener.document.location=top.window.opener.document.location.href;\n";
					echo "top.window.close();\n";
					echo "</script>";
				}
				else
					echo $str_query;
			}
			else {
				echo "<font color='red'>".$str_message."</font>";
			}
		}
	}
	
	$str_description = '';
	if ($int_id > 0) {
		$qry = new Query("
			SELECT *
			FROM stock_type_description
			WHERE stock_type_description_id = $int_id
		");
		$str_description = $qry->FieldByName('description');
	}
?>

<html>
<head><TITLE></TITLE>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	<script language="javascript">
		function trim(s) {
			return s.replace(/^\s+|\s+$/g, "");
		}
		
		function isEmpty(str){
			return (str == null) || (str.length == 0);
		}
		
		function saveData() {
			var oDescription = document.product_type_description_edit.description;
			var can_save = true;
			
			if (isEmpty(oDescription.value)) {
				can_save = false;
				alert('Value cannot be blank');
				oDescription.focus();
				return false;
			}
			
			if (can_save)
				document.product_type_description_edit.submit();
		}
	
		function closeWindow() {
			if (top.window.opener)
				top.window.opener.document.location=top.window.opener.document.location.href;
			top.window.close();
		}
	</script>
</head>
<body id='body_bgcolor' marginwidth=5 marginheight=5>

<form name='product_type_description_edit' method='POST'>

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

if ($int_id > 0)
	echo "<input type='hidden' name='id' value='".$int_id."'>";

if ($int_product_type_id > 0)
	echo "<input type='hidden' name='product_type_id' value='".$int_product_type_id."'>";

?>

<table width='100%' cellpadding="3" cellspacing="0" border='0'>
	<tr>
		<td class='normaltext_bold'>Description</td>
		<td>
			<input type='text' name='description' value='<?echo $str_description?>' class='input_200' autocomplete='OFF'>
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
    boundingBoxEnd("400", "../images/blank.gif");
?>
</td></tr>
</table>
<?
//-------------------
// end bounding box
//===================
?>

<script language='javascript'>
	var oDescription = document.product_type_description_edit.description;
	oDescription.focus();
</script>

</body>
</html>