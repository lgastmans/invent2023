<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once($str_application_path."common/functions.inc.php");

	//====================
	// get the user defined styles
	//====================
	if ($_SESSION['str_user_color_scheme'] == 'standard')
		$str_css_filename = 'bill_styles.css';
	else if ($_SESSION['str_user_color_scheme'] == 'blue')
		$str_css_filename = 'bill_styles_blue.css';
	else
		$str_css_filename = 'bill_styles.css';

	//====================
	// get the tax list
	//====================
	$arr_taxes = buildTaxList();
	for ($i=0;$i<count($arr_taxes);$i++) {
		$tax_list2[$i][0] = $arr_taxes[$i]["tax_id"]; 
		$tax_list2[$i][1] = $arr_taxes[$i]["tax_description"];
	}

	//====================
	// get the list of categories
	//====================
	$qry_categories = new Query("
		SElECT *
		FROM stock_category
		ORDER BY category_description
	");

	//====================
	// process GET parameters
	//====================
	$str_message = '';
	if (IsSet($_GET['action'])) {
		if ($_GET['action'] == 'replace') {
			$int_tax_id_from = intval($_GET['tax_id_from']);
			$int_tax_id_with = intval($_GET['tax_id_with']);
			$str_replace_stock = $_GET['replace_stock'];
			$str_filter_category = $_GET['filter_category'];
			$int_category_id = $_GET['category_id'];
			$str_filter = '';
			
			if ($int_tax_id_from <> $int_tax_id_with) {
				
				$bool_success = true;
				$str_message = 'Tax updated successfully';
				
				$qry_replace = new Query("START TRANSACTION");
				
				if ($str_filter_category == 'Y') {
					$str_query = "
						UPDATE stock_product sp
						SET sp.tax_id = ".$int_tax_id_with."
						WHERE (sp.tax_id = ".$int_tax_id_from.")
							AND (sp.category_id = ".$int_category_id.")";
				}
				else {
					$str_query = "
						UPDATE stock_product
						SET tax_id = ".$int_tax_id_with."
						WHERE tax_id = ".$int_tax_id_from;
				}
				$qry_replace->Query($str_query);
				if ($qry_replace->b_error == true) {
					$bool_success = false;
					$str_message = "Error updating stock_product";
				}

				if ($str_replace_stock == 'Y') {
					if ($str_filter_category == 'Y') {
						$str_query = "
							UPDATE ".Yearalize('stock_batch')." sb, stock_product sp
							SET sb.tax_id = ".$int_tax_id_with."
							WHERE (sb.tax_id = ".$int_tax_id_from.")
								AND (sb.product_id = sp.product_id)
								AND (sp.category_id = ".$int_category_id.")
								AND (sb.is_active = 'Y')";
					}
					else {
						$str_query = "
							UPDATE ".Yearalize('stock_batch')."
							SET tax_id = ".$int_tax_id_with."
							WHERE tax_id = ".$int_tax_id_from."
								AND (is_active = 'Y')";
					}
					$qry_replace->Query($str_query);
					if ($qry_replace->b_error == true) {
						$bool_success = false;
						$str_message = "Error updating stock_batch";
					}
				}
				
				if ($bool_success) {
					$qry_replace->Query("COMMIT");
				} else {
					$qry_replace->Query("ROLLBACK");
				}
			}
		}
	}

?>

<html>
<head>
	<link rel="stylesheet" type="text/css" href="../include/<?echo $str_css_filename;?>" />

	<script language="javascript">

		function replaceTax() {
			var oSelectFrom = document.taxes_replace.select_from;
			var oSelectWith = document.taxes_replace.select_with;
			var oCheckBoxStock = document.taxes_replace.checkbox_stock;
			var str_stock_selected = '';
			var oSelectCategory = document.taxes_replace.select_category;
			var oCheckBoxCategory = document.taxes_replace.checkbox_category;
			var str_filter_category = '';
			var str_url = '';

			if (oCheckBoxStock.checked)
				str_stock_selected = 'Y';
			else
				str_stock_selected = 'N';

			if (oCheckBoxCategory.checked)
				str_filter_category = 'Y';
			else
				str_filter_category = 'N';
			
			str_url = 'taxes_replace.php?action=replace'+
				'&tax_id_from='+oSelectFrom.value+
				'&tax_id_with='+oSelectWith.value+
				'&replace_stock='+str_stock_selected+
				'&filter_category='+str_filter_category+
				'&category_id='+oSelectCategory.value;

			if (oSelectFrom.value == oSelectWith.value)
				alert('The values selected cannot be equal');
			else {
				if (confirm('Are you sure?'))
					document.location = str_url;
			}
		}

		function WindowClose() {
			if (window.opener)
				window.opener.document.location=window.opener.document.location.href;
			window.close();
		}

	</script>
	
</head>

<body>
<form name='taxes_replace' method='get'>

	<table width='100%' height='100%' border='0'>
		<tr>
			<td align='right'>Replace :</td>
			<td>
				<select name='select_from'>
				<?
					for ($i=0; $i<count($tax_list2); $i++) {
						echo "<option value='".$tax_list2[$i][0]."'>".$tax_list2[$i][1]."\n";
					}
				?>
				</select>
			</td>
		</tr>
		<tr>
			<td align='right'>With :</td>
			<td>
				<select name='select_with'>
				<?
					for ($i=0; $i<count($tax_list2); $i++) {
						echo "<option value='".$tax_list2[$i][0]."'>".$tax_list2[$i][1]."\n";
					}
				?>
				</select>
			</td>
		</tr>
		<tr>
			<td align='right'><input type='checkbox' name='checkbox_category' checked>Where category :</td>
			<td>
				<select name='select_category'>
				<?
					for ($i=0; $i<$qry_categories->RowCount(); $i++) {
						echo "<option value='".$qry_categories->FieldByName('category_id')."'>".$qry_categories->FieldByName('category_description')."\n";
						$qry_categories->Next();
					}
				?>
				</select>
			</td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type='checkbox' name='checkbox_stock' checked>Replace taxes in current stock also
				<br><span id='message_text'></span>
			</td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type='button' name='action' value='Replace' onclick='replaceTax()'>
				<input type='button' name='action' value='Close' onclick='WindowClose()'>
			</td>
		</tr>
	</table>
</form>

<? if ($str_message <> '') { ?>
	<script language='javascript'>
		var oSpan = document.getElementById('message_text');
		oSpan.innerHTML = '<? echo $str_message; ?>';
		setTimeout("oSpan.innerHTML = ''", 5000);
	</script>
<? } ?>

</body>
</html>