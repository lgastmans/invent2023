<?
	header("Cache-control:private,no-cache");
	header("Expires: Mon, 26 Jun 1997 05:00:00 GMT");
	header("Pragma:no-cache");
	header("Cache:no-cache");
	
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
				
				//=================================
				// check for duplicate product code
				//---------------------------------
				$qry_check = new Query("
					SELECT *
					FROM stock_product
					WHERE (product_code = '".addslashes($_POST['code'])."')
						AND (product_id <> $int_id)
						AND (deleted = 'N')
				");
				if ($qry_check->RowCount() > 0) {
					$str_message = 'Duplicate product code found';
				}
				else {
					$str_query = "
						UPDATE stock_product
						SET
							product_code = '".($_POST['code'])."',
							product_bar_code = '".($_POST['barcode'])."',
							product_abbreviation = '".($_POST['abbreviation'])."',
							product_description = '".($_POST['description'])."',
							mrp = ".$_POST['mrp'].",
							is_available = '".($_POST['available'])."',
							list_in_purchase = '".($_POST['purchase_list'])."',
							list_in_order_sheet = '".($_POST['order_sheet'])."',
							list_in_price_list = '".($_POST['price_list'])."',
							purchase_round = ".$_POST['purchase_round'].",
							minimum_qty = ".$_POST['minimum_quantity'].",
							margin_percent = ".$_POST['margin_percent'].",
							category_id = ".$_POST['category'].",
							tax_id = ".$_POST['tax'].",
							measurement_unit_id = ".$_POST['unit'].",
							supplier_id = ".$_POST['supplier_1'].",
							supplier2_id = ".$_POST['supplier_2'].",
							supplier3_id = ".$_POST['supplier_3'].",
							currency_id = ".$_POST['currency'].",
							is_modified = 'Y'
						WHERE (product_id = $int_id)
					";
					$qry = new Query($str_query);
					//die($str_query);
					if ($qry->b_error == true)
						$str_message = 'Error updating supplier information';
				}
			}
			else {
				//==================================
				// check for duplicate supplier code
				//----------------------------------
				$qry_check = new Query("
					SELECT *
					FROM stock_product
					WHERE (product_code = '".addslashes($_POST['code'])."')
				");
				if ($qry_check->RowCount() > 0) {
					$str_message = 'Duplicate product code found';
				}
				else {
					$str_query = "
						INSERT INTO stock_product
						(
							product_code,
							product_bar_code,
							product_abbreviation,
							product_description,
							mrp,
							is_available,
							list_in_purchase,
							list_in_order_sheet,
							list_in_price_list,
							purchase_round,
							minimum_qty,
							margin_percent,
							category_id,
							tax_id,
							measurement_unit_id,
							supplier_id,
							supplier2_id,
							supplier3_id,
							currency_id,
							is_modified
						)
						VALUES (
							'".($_POST['code'])."',
							'".($_POST['barcode'])."',
							'".($_POST['abbreviation'])."',
							'".($_POST['description'])."',
							".$_POST['mrp'].",
							'".($_POST['available'])."',
							'".($_POST['purchase_list'])."',
							'".($_POST['order_sheet'])."',
							'".($_POST['price_list'])."',
							".$_POST['purchase_round'].",
							".$_POST['minimum_quantity'].",
							".$_POST['margin_percent'].",
							".$_POST['category'].",
							".$_POST['tax'].",
							".$_POST['unit'].",
							".$_POST['supplier_1'].",
							".$_POST['supplier_2'].",
							".$_POST['supplier_3'].",
							".$_POST['currency'].",
							'Y'
						)
					";
					$qry = new Query($str_query);
					if ($qry->b_error == true) {
						$str_message = 'Error inserting new product';
						echo $str_query;
					}
					else {
						$int_id = $qry->getInsertedID();
						echo "<script language='javascript'>";
						echo "top.document.location = 'product_edit.php?id=$int_id'";
						echo "</script>";
					}
				}
			}
		}
	}

	$str_code = '';
	$str_barcode = '';
	$str_abbreviation = '';
	$str_description = '';
	$int_mrp = 0;
	$str_available = 'Y';
	$str_purchase_list = 'Y';
	$str_order_sheet = 'N';
	$str_price_list = 'N';
	$int_purchase_roundup = 1;
	$int_minimum_quantity = 1;
	$int_margin_percent = 0;
	$int_category = 0;
	$int_tax = 0;
	$int_unit = 0;
	$int_supplier = 0;
	$int_supplier2 = 0;
	$int_supplier3 = 0;
	$int_currency = 0;

	if ($int_id > 0) {
		$str_query = "
			SELECT *
			FROM stock_product sp
			WHERE sp.product_id = $int_id";
		
		$qry = new Query($str_query);
		
		if ($qry->RowCount() > 0) {
			$str_code = $qry->FieldByName('product_code');
			$str_barcode = $qry->FieldByName('product_bar_code');
			$str_abbreviation = $qry->FieldByName('product_abbreviation');
			$str_description = ($qry->FieldByName('product_description'));
			$int_mrp = $qry->FieldByName('mrp');
			$str_available = $qry->FieldByName('is_available');
			$str_purchase_list = $qry->FieldByName('list_in_purchase');
			$str_order_sheet = $qry->FieldByName('list_in_order_sheet');
			$str_price_list = $qry->FieldByName('list_in_price_list');
			$int_purchase_roundup = $qry->FieldByName('purchase_round');
			$int_minimum_quantity = $qry->FieldByName('minimum_qty');
			$int_margin_percent = $qry->FieldByName('margin_percent');
			$int_category = $qry->FieldByName('category_id');
			$int_tax = $qry->FieldByName('tax_id');
			$int_unit = $qry->FieldByName('measurement_unit_id');
			$int_supplier = $qry->FieldByName('supplier_id');
			$int_supplier2 = $qry->FieldByName('supplier2_id');
			$int_supplier3 = $qry->FieldByName('supplier3_id');
			$int_currency = $qry->FieldByName('currency_id');
		}
	}
	else {
		$qry_defaults = new Query("SELECT * FROM stock_storeroom WHERE storeroom_id = ".$_SESSION['int_current_storeroom']);
		if ($qry_defaults->RowCount() > 0) {
			$int_category = $qry_defaults->FieldByName('default_category_id');
			$int_tax = $qry_defaults->FieldByName('default_tax_id');
			$int_supplier = $qry_defaults->FieldByName('default_supplier_id');
			$int_currency = $qry_defaults->FieldByName('default_currency_id');
			$int_unit = $qry_defaults->FieldByName('default_unit_id');
		}
	}

	$qry_unit = new Query("
		SELECT *
		FROM stock_measurement_unit
		ORDER BY measurement_unit
	");
	


	$qry_category = new Query("
		SELECT *
		FROM stock_category
		ORDER BY category_description
	");

	for ($i=0;$i<$qry_category->RowCount();$i++) {

		$arr_categories[$qry_category->FieldByName('category_id')] = $qry_category->FieldByName('category_description');

		$qry_category->Next();
	}

	//$arr_category = buildCategoryList();
	//for ($i=0;$i<count($arr_category);$i++) {
	//	$arr_categories[$arr_category[$i]["category_id"]] =  $arr_category[$i]["category_description"];
	//}

	
	
	$qry_tax = new Query("
		SELECT *
		FROM ".Monthalize('stock_tax')."
		ORDER BY tax_description
	");
	
	$qry_supplier = new Query("
		SELECT *
		FROM stock_supplier
		ORDER BY supplier_name
	");
	
	$qry_currency = new Query("
		SELECT *
		FROM stock_currency
		ORDER BY currency_name
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
		
		function trim(s) {
			return s.replace(/^\s+|\s+$/g, "");
		}
		
		function isFloat(s) {
			var n = trim(s);
			return n.length>0 && !(/[^0-9.]/).test(n) && (/\.\d/).test(n);
		}

		function makeFloat(s) {
			s = String(s);
			if (s.indexOf('.') < 0)
				s += '.0';
			return s;
		}
		
		function saveData() {
			var oTextCode = document.product_basic.code;
			var oTextDescription = document.product_basic.description;
			var oTextMRP = document.product_basic.mrp;
			var oTextPurchaseRound = document.product_basic.purchase_round;
			var oTextMinimumQty = document.product_basic.minimum_quantity;
			var oTextMarginPercent = document.product_basic.margin_percent;
			var can_save = true;
			
			if (isEmpty(oTextCode.value)) {
				can_save = false;
				alert('Code cannot be blank');
				oTextCode.focus();
			}
			if (isEmpty(oTextDescription.value)) {
				can_save = false;
				alert('Description cannot be blank');
				oTextDescription.focus();
			}
			strVal = makeFloat(oTextMRP.value);
			if (!isFloat(strVal)) {
				alert('Invalid MRP');
				can_save = false;
				oTextMRP.focus();
			}
			if ((!isNumeric(oTextPurchaseRound.value)) || (isEmpty(oTextPurchaseRound.value))) {
				can_save = false;
				alert('Invalid Purchase List Round Up value');
				oTextPurchaseRound.focus();
			}
			if ((!isNumeric(oTextMinimumQty.value)) || (isEmpty(oTextMinimumQty.value))) {
				can_save = false;
				alert('Invalid Minimum Quantity value');
				oTextMinimumQty.focus();
			}
			if ((!isNumeric(oTextMarginPercent.value)) || (isEmpty(oTextMarginPercent.value))) {
				can_save = false;
				alert('Invalid Margin Percent value');
				oTextMarginPercent.focus();
			}
			
			if (can_save) {
				document.product_basic.submit();
			}
			
			return can_save;
		}
	</script>
</head>
<body bgcolor="#e9ecf1" topmargin="0" rightmargin="0" bottommargin="0" leftmargin="0">
<form name='product_basic' method='POST'>
<?
	if ($str_message <> '') { ?>
	<script language="javascript">alert('<?echo $str_message?>');</script>
<?
	}
	if ($int_id > -1)
		echo "<input type='hidden' name='id' value='".$int_id."'>";

?>
<input type='hidden' name='action' value='save'>

<table width='100%' cellpadding="1" cellspacing="0" border='0'>
	<?
	 	// CODE
	?>
	<tr>
		<td width='100px' class='normaltext_bold'>Code</td>
		<td>
			<input type='text' name='code' value='<?echo $str_code?>' class='input_200'>
		</td>
	</tr>
	
	<?
	 	// BARCODE
	?>
	<tr>
		<td class='normaltext_bold'>Barcode</td>
		<td>
			<input type='text' name='barcode' value='<?echo $str_barcode?>' class='input_200'>
		</td>
	</tr>
	
	<?
	 	// ABBREVIATION
	?>
	<tr>
		<td class='normaltext_bold'>Abbreviation</td>
		<td>
			<input type='text' name='abbreviation' value='<?echo htmlentities($str_abbreviation)?>' class='input_100'>
		</td>
	</tr>
</table>

<table width='100%' cellpadding="3" cellspacing="0" border='0'>
	<?
	 	// DESCRIPTION
	?>
	<tr>
		<td class='normaltext_bold'>Description</td>
	</tr>
	<tr>
		<td>
			<input type='text' name='description' value="<?echo htmlentities($str_description)?>" class='input_400'>
		</td>
	</tr>
</table>

<table width='100%' cellpadding="1" cellspacing="0" border='0'>
	<?
	 	// MRP
	?>
	<tr>
		<td class='normaltext_bold'>MRP</td>
		<td>
			<input type='text' name='mrp' value='<?echo $int_mrp?>' class='input_100'>
		</td>
	</tr>
	
	<?
	 	// AVAILABLE
	?>
	<tr>
		<td class='normaltext_bold'>Available</td>
		<td>
			<select name='available' class='select_100'>
				<option value='Y'<?if($str_available=='Y') echo "selected";?>>Yes</option>
				<option value='N'<?if($str_available=='N') echo "selected";?>>No</option>
			</select>
		</td>
	</tr>
	
	<?
	 	// PURCHASE LIST
	?>
	<tr>
		<td class='normaltext_bold'>List in purchase list</td>
		<td>
			<select name='purchase_list' class='select_100'>
				<option value='Y'<?if($str_purchase_list=='Y') echo "selected";?>>Yes</option>
				<option value='N'<?if($str_purchase_list=='N') echo "selected";?>>No</option>
			</select>
		</td>
	</tr>
	
	<tr>
		<td class='normaltext_bold'>Purchase list round up</td>
		<td>
			<input type='text' name='purchase_round' value='<?echo $int_purchase_roundup?>' class='input_100'>
		</td>
	</tr>
	
	<tr>
		<td class='normaltext_bold'>List in order sheet</td>
		<td>
			<select name='order_sheet' class='select_100'>
				<option value='Y'<?if($str_order_sheet=='Y') echo "selected";?>>Yes</option>
				<option value='N'<?if($str_order_sheet=='N') echo "selected";?>>No</option>
			</select>
		</td>
	</tr>
	
	<tr>
		<td class='normaltext_bold'>List in price list</td>
		<td>
			<select name='price_list' class='select_100'>
				<option value='Y'<?if($str_price_list=='Y') echo "selected";?>>Yes</option>
				<option value='N'<?if($str_price_list=='N') echo "selected";?>>No</option>
			</select>
		</td>
	</tr>
	
	<tr>
		<td class='normaltext_bold'>Minimum quantity</td>
		<td>
			<input type='text' name='minimum_quantity' value='<?echo $int_minimum_quantity?>' class='input_100'>
		</td>
	</tr>
	
	<tr>
		<td class='normaltext_bold'>Margin Percent</td>
		<td>
			<input type='text' name='margin_percent' value='<?echo $int_margin_percent?>' class='input_100'>
		</td>
	</tr>
	
	<tr>
		<td class='normaltext_bold'>Category</td>
		<td>
			<select name='category' class='select_200'>
			<?
				foreach ($arr_categories as $key=>$value) {
					if ($int_category == $key)
						echo "<option value='".$key."' selected>".$value."</option>\n";
					else
						echo "<option value='".$key."'>".$value."</option>\n";
				}
			?>
			</select>
		</td>
	</tr>
	
	<tr>
		<td class='normaltext_bold'>Currency</td>
		<td>
			<select name='currency' class='select_200'>
			<?
				$qry_currency->First();
				for ($i=0;$i<$qry_currency->RowCount();$i++) {
					if ($int_currency == $qry_currency->FieldByName('currency_id'))
						echo "<option value='".$qry_currency->FieldByName('currency_id')."' selected>".$qry_currency->FieldByName('currency_name')."</option>\n";
					else
						echo "<option value='".$qry_currency->FieldByName('currency_id')."'>".$qry_currency->FieldByName('currency_name')."</option>\n";
					$qry_currency->Next();
				}
			?>
			</select>
		</td>
	</tr>
	
	<tr>
		<td class='normaltext_bold'>Tax</td>
		<td>
			<select name='tax' class='select_200'>
			<?
				$qry_tax->First();
				for ($i=0;$i<$qry_tax->RowCount();$i++) {
					if ($int_tax == $qry_tax->FieldByName('tax_id'))
						echo "<option value='".$qry_tax->FieldByName('tax_id')."' selected>".$qry_tax->FieldByName('tax_description')."</option>\n";
					else
						echo "<option value='".$qry_tax->FieldByName('tax_id')."'>".$qry_tax->FieldByName('tax_description')."</option>\n";
					$qry_tax->Next();
				}
			?>
			</select>
		</td>
	</tr>
	
	<tr>
		<td class='normaltext_bold'>Unit</td>
		<td>
			<select name='unit' class='select_200'>
			<?
				$qry_unit->First();
				for ($i=0;$i<$qry_unit->RowCount();$i++) {
					if ($int_unit == $qry_unit->FieldByName('measurement_unit_id'))
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

<table width='100%' cellpadding="1" cellspacing="0" border='0'>
	<tr>
		<td colspan='2' class='normaltext_bold'>Default Supplier 1</td>
	</tr>
	<tr>
		<td colspan='2'>
			<select name='supplier_1' class='select_400'>
			<?
				$qry_supplier->First();
				for ($i=0;$i<$qry_supplier->RowCount();$i++) {
					if ($int_supplier == $qry_supplier->FieldByName('supplier_id'))
						echo "<option value='".$qry_supplier->FieldByName('supplier_id')."' selected>".$qry_supplier->FieldByName('supplier_name')."</option>\n";
					else
						echo "<option value='".$qry_supplier->FieldByName('supplier_id')."'>".$qry_supplier->FieldByName('supplier_name')."</option>\n";
					$qry_supplier->Next();
				}
			?>
			</select>
		</td>
	</tr>
	
	<tr>
		<td colspan='2' class='normaltext_bold'>Default Supplier 2</tdclass='normaltext_bold'>
	</tr>
	<tr>
		<td colspan='2'>
			<select name='supplier_2' class='select_400'>
				<option value='0'>--select--</option>
			<?
				$qry_supplier->First();
				for ($i=0;$i<$qry_supplier->RowCount();$i++) {
					if ($int_supplier2 == $qry_supplier->FieldByName('supplier_id'))
						echo "<option value='".$qry_supplier->FieldByName('supplier_id')."' selected>".$qry_supplier->FieldByName('supplier_name')."</option>\n";
					else
						echo "<option value='".$qry_supplier->FieldByName('supplier_id')."'>".$qry_supplier->FieldByName('supplier_name')."</option>\n";
					$qry_supplier->Next();
				}
			?>
			</select>
		</td>
	</tr>
	
	<tr>
		<td colspan='2' class='normaltext_bold'>Default Supplier 3</td>
	</tr>
	<tr>
		<td colspan='2'>
			<select name='supplier_3' class='select_400'>
				<option value='0'>--select--</option>
			<?
				$qry_supplier->First();
				for ($i=0;$i<$qry_supplier->RowCount();$i++) {
					if ($int_supplier3 == $qry_supplier->FieldByName('supplier_id'))
						echo "<option value='".$qry_supplier->FieldByName('supplier_id')."' selected>".$qry_supplier->FieldByName('supplier_name')."</option>\n";
					else
						echo "<option value='".$qry_supplier->FieldByName('supplier_id')."'>".$qry_supplier->FieldByName('supplier_name')."</option>\n";
					$qry_supplier->Next();
				}
			?>
			</select>
		</td>
	</tr>
</table>

<script language="javascript">
	var oCode = document.product_basic.code;
	oCode.focus();
</script>

</form>
</body>
</html>