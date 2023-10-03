<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/functions.inc.php");
	
	$str_message = '';

	$int_id = -1;
	if (IsSet($_GET['id'])) {
		$int_id = $_GET['id'];
	}
	
	if (IsSet($_POST['action'])) {
		if ($_POST['action'] == 'save') {
			if (IsSet($_POST['id'])) {
				$int_id = $_POST['id'];
				
				$str_query = "
					UPDATE stock_product
					SET
						is_perishable = '".addslashes($_POST['perishable'])."',
						is_av_product = '".addslashes($_POST['av_product'])."',
						product_weight = ".$_POST['weight'].",
						bulk_unit_id = ".$_POST['bulk_unit']."
					WHERE (product_id = $int_id)
				";
				$qry = new Query($str_query);
				
				if ($qry->b_error == true)
					$str_message = 'Error updating supplier information';
			}
			else {
				$str_query = "
					INSERT INTO stock_product
					(
						is_perishable,
						is_av_product,
						product_weight,
						bulk_unit_id
					)
					VALUES (
						'".addslashes($_POST['perishable'])."',
						'".addslashes($_POST['av_product'])."',
						".$_POST['weight'].",
						".$_POST['bulk_unit']."
					)
				";
				$qry = new Query($str_query);
				if ($qry->b_error == true) {
					$str_message = 'Error inserting new product';
					echo $str_query;
				}
			}
		}
	}

	$str_perishable = 'N';
	$str_av_product = 'N';
	$int_weight = 0;
	$int_bulk_unit = 0;

	if ($int_id > 0) {
		$str_query = "
			SELECT *
			FROM stock_product sp
			WHERE sp.product_id = $int_id";
		
		$qry = new Query($str_query);
		
		if ($qry->RowCount() > 0) {
			$str_perishable = $qry->FieldByName('is_perishable');
			$str_av_product = $qry->FieldByName('is_av_product');
			$int_weight = $qry->FieldByName('product_weight');
			$int_bulk_unit = $qry->FieldByName('bulk_unit_id');
		}
	}

	$qry_unit = new Query("
		SELECT *
		FROM stock_measurement_unit
		ORDER BY measurement_unit
	");
?>

<html>
<head><TITLE></TITLE>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	<script language="javascript">
		function isEmpty(str){
			return (str == null) || (str.length == 0);
		}
		
		function isNumeric(str){
			var re = /[\D]/g
			if (re.test(str)) return false;
			return true;
		}
		
		function saveData() {
			var oTextWeight = document.product_consumables.weight;
			var can_save = true;
			
			if ((!isNumeric(oTextWeight.value)) || (isEmpty(oTextWeight.value))) {
				can_save = false;
				alert('Invalid Weight value');
				oTextWeight.focus();
			}
			
			if (can_save) {
				document.product_consumables.submit();
			}

			return can_save;
		}
	
		function CloseWindow() {
			if (top.window.opener)
				top.window.opener.document.location=top.window.opener.document.location.href;
			top.window.close();
		}
	</script>
</head>
<body bgcolor="#e9ecf1" marginwidth=5 marginheight=5>
<form name='product_consumables' method='POST'>
<?
	if ($int_id > -1)
		echo "<input type='hidden' name='id' value='".$int_id."'>";

?>
<input type='hidden' name='action' value='save'>

<table width='100%' cellpadding="3" cellspacing="0" border='0'>
	
	<?
	 	// PERISHABLE & AV PRODUCT
	?>
	<tr>
		<td class='normaltext_bold'>Perishable</td>
		<td>
			<select name='perishable' class='select_100'>
				<option value='Y'<?if($str_perishable=='Y') echo "selected";?>>Yes</option>
				<option value='N'<?if($str_perishable=='N') echo "selected";?>>No</option>
			</select>
		</td>
	</tr>
	<tr>
		<td class='normaltext_bold'>AV Product</td>
		<td>
			<select name='av_product' class='select_100'>
				<option value='Y'<?if($str_av_product=='Y') echo "selected";?>>Yes</option>
				<option value='N'<?if($str_av_product=='N') echo "selected";?>>No</option>
			</select>
		</td>
	</tr>
	
	<tr>
		<td class='normaltext_bold'>Product Weight</td>
		<td>
			<input type='text' name='weight' value='<?echo $int_weight?>' class='input_100'>
		</td>
	</tr>
	
	<tr>
		<td class='normaltext_bold'>Bulk Unit</td>
		<td>
			<select name='bulk_unit' class='select_200'>
			<?
				$qry_unit->First();
				for ($i=0;$i<$qry_unit->RowCount();$i++) {
					if ($int_bulk_unit == $qry_unit->FieldByName('measurement_unit_id'))
						echo "<option value='".$qry_unit->FieldByName('measurement_unit_id')."' selected>".$qry_unit->FieldByName('measurement_unit')."</option>\n";
					else
						echo "<option value='".$qry_unit->FieldByName('measurement_unit_id')."'>".$qry_unit->FieldByName('measurement_unit')."</option>\n";
					$qry_unit->Next();
				}
			?>
			</select>
		</td>
	</tr>
	
</table>

</form>


	<script src="../include/js/jquery-3.2.1.min.js"></script>


<script>

    $( document ).ready(function() {

    	window.saveData = function() {

			var oTextWeight = document.product_consumables.weight;
			var can_save = true;
			
			if ((!isNumeric(oTextWeight.value)) || (isEmpty(oTextWeight.value))) {
				can_save = false;
				alert('Invalid Weight value');
				oTextWeight.focus();
			}
			
			if (can_save) {
				document.product_consumables.submit();
			}

			return can_save;
    	}

    });

</script>


</body>
</html>