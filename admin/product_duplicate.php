<?
	require_once("../include/db.inc.php");
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	
	$int_id = 0;
	
	if (IsSet($_GET['id']))
		$int_id = $_GET['id'];
	
	if ($int_id > 0) {
		$bool_success = true;
		$str_message = 'Product details successfully copied<br>An asterisk has been added at the end of the code';
		
		$str_query = "
			SELECT *
			FROM stock_product
			WHERE product_id = $int_id
		";
		$qry_source = new Query($str_query);
		
		if ($qry_source->RowCount() > 0) {
			$qry_insert = new Query("
				INSERT INTO stock_product
				(
					product_code,
					product_bar_code,
					product_description,
					product_abbreviation,
					is_available,
					minimum_qty,
					is_minimum_consolidated,
					is_av_product,
					tax_id,
					is_perishable,
					shelf_life,
					measurement_unit_id,
					category_id,
					deleted,
					supplier_id,
					supplier2_id,
					supplier3_id,
					quantity_per_box,
					margin_percent,
					adjusted_stock,
					list_in_purchase,
					purchase_round,
					list_in_order_sheet,
					product_weight,
					mrp,
					list_in_price_list,
					bulk_unit_id
				)
				VALUES (
					'".$qry_source->FieldByName('product_code')."*',
					'".$qry_source->FieldByName('product_bar_code')."',
					'".$qry_source->FieldByName('product_description')."',
					'".$qry_source->FieldByName('product_abbreviation')."',
					'".$qry_source->FieldByName('is_available')."',
					".$qry_source->FieldByName('minimum_qty').",
					'".$qry_source->FieldByName('is_minimum_consolidated')."',
					'".$qry_source->FieldByName('is_av_product')."',
					".$qry_source->FieldByName('tax_id').",
					'".$qry_source->FieldByName('is_perishable')."',
					".$qry_source->FieldByName('shelf_life').",
					".$qry_source->FieldByName('measurement_unit_id').",
					".$qry_source->FieldByName('category_id').",
					'".$qry_source->FieldByName('deleted')."',
					".$qry_source->FieldByName('supplier_id').",
					".$qry_source->FieldByName('supplier2_id').",
					".$qry_source->FieldByName('supplier3_id').",
					".$qry_source->FieldByName('quantity_per_box').",
					".$qry_source->FieldByName('margin_percent').",
					".$qry_source->FieldByName('adjusted_stock').",
					'".$qry_source->FieldByName('list_in_purchase')."',
					".$qry_source->FieldByName('purchase_round').",
					'".$qry_source->FieldByName('list_in_order_sheet')."',
					".$qry_source->FieldByName('product_weight').",
					".$qry_source->FieldByName('mrp').",
					'".$qry_source->FieldByName('list_in_price_list')."',
					".$qry_source->FieldByName('bulk_unit_id')."
				)
			");
			if ($qry_insert->b_error == true) {
				$str_message = "Error copying product: ".mysql_error();
				$bool_success = false;
			}
		}
	}
?>

<html>
<head><TITLE></TITLE>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
</head>

<body id='body_bgcolor'>
	<table width='100%' height='100%' border='0'>
		<tr>
			<TD align="center" valign='center' class='normaltext_bold'><?echo $str_message;?></TD>
		</TR>
	</table>
</body>
</html>