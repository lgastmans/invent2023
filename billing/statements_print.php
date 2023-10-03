<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../include/browser_detection.php");
	require_once("../common/printer.inc.php");

	require_once("../common/tax.php");
	require_once("../common/product_funcs.inc.php");


	$copies = $arr_invent_config['billing']['print_copies'];
	$print_name = $arr_invent_config['billing']['print_name'];
	$print_mode = $arr_invent_config['billing']['print_mode'];
	$os = browser_detection("os");

	
	$calc_price = "SP";
	if (IsSet($_GET['price']))
		$calc_price = $_GET['price'];
	
	$where_filter_day = "";
	if (IsSet($_GET['filter_day']) && ($_GET['filter_day']!='ALL'))
		$where_filter_day = "AND (DAYOFMONTH(b.date_created)=".$_GET['filter_day'].") ";
	
	$sql_settings = new Query("
		SELECT *
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION["int_current_storeroom"]."
	");
	if ($sql_settings->RowCount() > 0) {
		$int_eject_lines = $sql_settings->FieldByName('bill_print_lines_to_eject');
		$str_print_address = $sql_settings->FieldByName('bill_print_address');
		$str_print_phone = $sql_settings->FieldByName('bill_print_phone');
		$str_calc_tax_first = $sql_settings->FieldByName('calculate_tax_before_discount');
	}
	
	$str_format = "DATE_BILL";
	if (IsSet($_GET['format']))
		$str_format = $_GET['format'];
		
	if (IsSet($_GET["supplier_id"]))
		$int_supplier_id = $_GET["supplier_id"];
	else
		$int_supplier_id = 0;
	
	$str_order_by = 'b.date_created';
	if (IsSet($_GET['order_by'])) {
		if ($_GET['order_by'] == 'date')
			$str_order_by = 'b.date_created';
		else if ($_GET['order_by'] == 'code')
			$str_order_by = 'sp.product_code';
	}
	
	

	$str_include_tax = 'Y';
	if (IsSet($_GET['include_tax']))
		$str_include_tax = $_GET['include_tax'];

	$str_include_tax = 'Y';



	$qry_supplier = new Query("
		SELECT supplier_name, commission_percent, commission_percent_2, commission_percent_3
		FROM stock_supplier
		WHERE (supplier_id = ".$int_supplier_id.")
	");
	$int_percent = 0;
	if ($qry_supplier->RowCount() > 0) {
		$flt_percent = $qry_supplier->FieldByName('commission_percent');
		$flt_percent_2 = $qry_supplier->FieldByName('commission_percent_2');
		$flt_percent_3 = $qry_supplier->FieldByName('commission_percent_3');
	}
	
/*	$qry = new Query("
		SELECT DAYOFMONTH(b.date_created) AS date_created, b.bill_number, b.is_debit_bill,
			sp.product_code,
			bi.product_description,
			bi.price,
			bi.tax_id,
			st.tax_description,
			ROUND(bi.quantity + bi.adjusted_quantity, 3) AS quantity,
			bi.discount,
			IF(bi.discount > 0,
					ROUND((bi.price * (1 - (bi.discount/100)) * (bi.quantity + bi.adjusted_quantity)), 2), 
					ROUND((bi.price * (bi.quantity + bi.adjusted_quantity)), 2)) 
			AS amount,
			smu.is_decimal
		FROM ".Monthalize('bill')." b,
			".Monthalize('bill_items')." bi,
			stock_product sp, 
			".Yearalize('stock_batch')." sb,
			stock_measurement_unit smu,
			".Monthalize('stock_tax')." st
		WHERE (bi.bill_id = b.bill_id)
			AND (
					b.bill_status = ".BILL_STATUS_RESOLVED."
					OR (b.bill_status = ".BILL_STATUS_DELIVERED.")
				)
			AND (sp.product_id = bi.product_id)
			AND (sb.product_id = bi.product_id)
			AND (sb.supplier_id = ".$int_supplier_id.")
			AND (sb.batch_id = bi.batch_id)
			AND (sp.measurement_unit_id = smu.measurement_unit_id)
			AND (bi.tax_id = st.tax_id)
		ORDER BY $str_order_by
	");*/
//	echo $str_format;
	
	if ($str_format == 'DATE_BILL')
		$str_query = "
			SELECT DAYOFMONTH(b.date_created) AS date_created, b.bill_number, b.is_debit_bill,
				sp.product_code, sp.product_id,
				bi.product_description,
				bi.price,
				bi.tax_id,
				st.tax_description,
				IF(b.is_debit_bill='Y',
					(ROUND(bi.quantity + bi.adjusted_quantity, 3)  * -1),
					ROUND(bi.quantity + bi.adjusted_quantity, 3)
				) AS quantity,
				bi.discount,
				IF(b.is_debit_bill='Y',
					IF(bi.discount > 0,
							(ROUND((bi.price * (1 - (bi.discount/100)) * (bi.quantity + bi.adjusted_quantity)), 2)*-1),
							(ROUND((bi.price * (bi.quantity + bi.adjusted_quantity)), 2)*-1)
						),
					IF(bi.discount > 0,
							ROUND((bi.price * (1 - (bi.discount/100)) * (bi.quantity + bi.adjusted_quantity)), 2), 
							ROUND((bi.price * (bi.quantity + bi.adjusted_quantity)), 2)
						)
				)
				AS amount,
				smu.is_decimal
			FROM ".Monthalize('bill')." b, 
				".Monthalize('bill_items')." bi, 
				stock_product sp, 
				".Yearalize('stock_batch')." sb,
				stock_measurement_unit smu,
				".Monthalize('stock_tax')." st,
				stock_category sc
			WHERE (bi.bill_id = b.bill_id)
				AND (
						(b.bill_status = ".BILL_STATUS_RESOLVED.")
						OR (b.bill_status = ".BILL_STATUS_DELIVERED.")
					)
				AND (sp.product_id = bi.product_id)
				AND (sb.product_id = bi.product_id)
				AND (sb.supplier_id = ".$int_supplier_id.")
				AND (sb.batch_id = bi.batch_id)
				AND (sp.measurement_unit_id = smu.measurement_unit_id)
				AND (sp.category_id = sc.category_id)
				AND (bi.tax_id = st.tax_id)
				$where_filter_day
			ORDER BY $str_order_by";
	else
		$str_query = "
			SELECT sp.product_code, sp.product_id,
				bi.product_description,
				bi.price,
				bi.tax_id,
				b.is_debit_bill,
				SUM(
					IF(b.is_debit_bill='Y',
						(ROUND(bi.quantity + bi.adjusted_quantity, 3)  * -1),
						ROUND(bi.quantity + bi.adjusted_quantity, 3)
					)
				) AS quantity,
				bi.discount,
				SUM(
					IF(b.is_debit_bill='Y',
						IF(bi.discount > 0,
								(ROUND((bi.price * (1 - (bi.discount/100)) * (IF(b.is_debit_bill='Y',
										(ROUND(bi.quantity + bi.adjusted_quantity, 3)  * -1),
										ROUND(bi.quantity + bi.adjusted_quantity, 3)
									))), 2)*-1),
								(ROUND((bi.price * (IF(b.is_debit_bill='Y',
										(ROUND(bi.quantity + bi.adjusted_quantity, 3)  * -1),
										ROUND(bi.quantity + bi.adjusted_quantity, 3)
									))), 2))
							),
						IF(bi.discount > 0,
								ROUND((bi.price * (1 - (bi.discount/100)) * (IF(b.is_debit_bill='Y',
										(ROUND(bi.quantity + bi.adjusted_quantity, 3)  * -1),
										ROUND(bi.quantity + bi.adjusted_quantity, 3)
									))), 2),
								ROUND((bi.price * (IF(b.is_debit_bill='Y',
										(ROUND(bi.quantity + bi.adjusted_quantity, 3)  * -1),
										ROUND(bi.quantity + bi.adjusted_quantity, 3)
									))), 2)
							)
					)
				)
				AS amount,
				smu.is_decimal,
				sc.category_description
			FROM ".Monthalize('bill')." b,
				".Monthalize('bill_items')." bi,
				stock_product sp,
				".Yearalize('stock_batch')." sb,
				stock_measurement_unit smu,
				".Monthalize('stock_tax')." st,
				stock_category sc
			WHERE (bi.bill_id = b.bill_id)
				AND (
					(b.bill_status = ".BILL_STATUS_RESOLVED.")
					OR (b.bill_status = ".BILL_STATUS_DELIVERED.")
				)
				AND (sp.product_id = bi.product_id)
				AND (sb.product_id = bi.product_id)
				AND (sb.supplier_id = ".$int_supplier_id.")
				AND (sb.batch_id = bi.batch_id)
				AND (sp.measurement_unit_id = smu.measurement_unit_id)
				AND (sp.category_id = sc.category_id)
				AND (bi.tax_id = st.tax_id)
				$where_filter_day
			GROUP BY bi.product_id, bi.price, b.is_debit_bill
			ORDER BY sc.category_description, sp.product_code
		";
		
	$qry = new Query($str_query);
//echo $str_query;
?>

<html>
<head><TITLE>Printing Statement</TITLE>
<link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>

<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0">

<?php

if ($calc_price == "SP")
	$str = "on selling price";
else
	$str = "on buying price";

if (IsSet($_GET['filter_day']) && ($_GET['filter_day']!='ALL'))
	$str_title = "Supplier Statement $str for ".$qry_supplier->FieldByName('supplier_name')." for ".$_GET['filter_day'].", ".getMonthName($_SESSION["int_month_loaded"])." ".$_SESSION["int_year_loaded"];

else
	$str_title = "Supplier Statement $str for ".$qry_supplier->FieldByName('supplier_name')." for ".getMonthName($_SESSION["int_month_loaded"])." ".$_SESSION["int_year_loaded"];

$str_top = "";
$str_top = PadWithCharacter($str_top, '=', 105);

$str_bottom = "";
$str_bottom = PadWithCharacter($str_bottom, '-', 105);

if ($str_format == 'DATE_BILL') {
	if ($str_include_tax == 'Y')
		$str_header = PadWithCharacter('Date', ' ', 6)." ".
			PadWithCharacter('Bill', ' ', 6)." ".
			StuffWithCharacter('Code', ' ', 8)." ".
			PadWithCharacter('Description', ' ', 20)." ".
			StuffWithCharacter('Qty', ' ', 8)." ".
			StuffWithCharacter('Price', ' ', 8)." ".
			StuffWithCharacter('Dt.', ' ', 5)." ".
			StuffWithCharacter('Tax Val', ' ', 8)." ".
			StuffWithCharacter('Tax', ' ', 8)." ".
			StuffWithCharacter('Tax Amt', ' ', 8)." ".
			StuffWithCharacter('Amount', ' ', 10);
	else
		$str_header = PadWithCharacter('Date', ' ', 6)." ".
			PadWithCharacter('Bill', ' ', 6)." ".
			StuffWithCharacter('Code', ' ', 8)." ".
			PadWithCharacter('Description', ' ', 20)." ".
			StuffWithCharacter('Price', ' ', 8)." ".
			StuffWithCharacter('Qty', ' ', 8)." ".
			StuffWithCharacter('Dt.', ' ', 5)." ".
			StuffWithCharacter('Amount', ' ', 10);
}
else {
		$str_header = PadWithCharacter('Code', ' ', 8)." ".
			PadWithCharacter('Description', ' ', 23)." ".
			PadWithCharacter('Price', ' ', 10)." ".
			PadWithCharacter('Qty', ' ', 7)." ".
			PadWithCharacter('Amount', ' ', 10);
}

if ($str_format == 'DATE_BILL') {
	$date_current = 0;
	$total = 0;
	$total_qty = 0;
	$total_taxable_value = 0;
	$total_tax_amount = 0;
	$total_amount = 0;

	$str_data = "";
	$line_counter = 9;
	
	for ($i=0;$i<$qry->RowCount();$i++) {
		if ($str_order_by == 'b.date_created') {
			if ($date_current < $qry->FieldByName('date_created')) {
				$str_data .= PadWithCharacter($qry->FieldByName('date_created'), ' ', 6)." ";
				$date_current = $qry->FieldByName('date_created');
			}
			else
				$str_data .= "       ";
		}
		else
			$str_data .= PadWithCharacter($qry->FieldByName('date_created'), ' ', 6)." ";

		if ($calc_price == "BP")
			$flt_price = number_format(getBuyingPrice($qry->FieldByName('product_id')), 2,'.','');
		else
			$flt_price = number_format($qry->FieldByName('price'), 2, '.', '');

		$discount = $qry->FieldByName('discount');
		$is_debit_bill = $qry->FieldByName('is_debit_bill');
		/*
			quantity includes adjusted (see query)
		*/
		$quantity = $qry->FieldByName('quantity');
		
		if ($str_include_tax == 'Y') {
			$tax_id = $qry->FieldByName('tax_id');
			
			if ($discount > 0) {
				if ($str_calc_tax_first == 'Y') {
					$tax_price = round($flt_price + calculateTax($flt_price, $tax_id),3);
					$tax_amount = calculateTax(($flt_price * $quantity), $tax_id);
					$flt_discount = round(($quantity * $tax_price) * ($discount/100),3);
					
					$flt_amount = round(($quantity * $tax_price - $discount), 3);
				}
				else {
					$discount_price = round(($flt_price * (1 - ($discount/100))), 3);
					$tax_amount = calculateTax($quantity * $discount_price, $tax_id);
					$flt_amount = round(($quantity * $discount_price + $tax_amount), 3);
				}
			}
			else {
				$discount_price = $flt_price;
				$tax_amount = calculateTax($flt_price * $quantity, $tax_id);
				$flt_amount = round(($quantity * $flt_price + $tax_amount), 3);
			}
			$flt_amount = number_format($flt_amount, 2, '.', '');
		}
		else {
			if ($discount > 0) {
				$flt_amount = number_format(($flt_price * (1 - ($discount/100)) * $quantity), 2, '.','');
			}
			else {
				$flt_amount = number_format(($flt_price * $quantity), 2, '.','');
			}
		}


		if (array_key_exists($tax_id, $tax_totals)) {

			$add = round((float)$tax_totals[$tax_id]['amount'],3) + round((float)$tax_amount,3);
			$tax_totals[$tax_id]['amount'] = number_format($add,2,'.','');

		} else {

			$tax_totals[$tax_id]['description'] = $qry->FieldByName('tax_description');
			$tax_totals[$tax_id]['amount'] = number_format(round((float)$tax_amount,3),2,'.','');

		}

		
		if ($is_debit_bill == 'Y')
			$flt_amount = $flt_amount * -1;

		
		if ($str_order_by == 'b.date_created, sp.product_code') {

			if ($date_current < $qry->FieldByName('date_created')) {

				$date_current = $qry->FieldByName('date_created');

			}
		}


		$tmp_qty = $qry->FieldByName('quantity');

		$str_data .= padWithCharacter($qry->FieldByName('bill_number'), ' ', 6)." ".
			StuffWithCharacter($qry->FieldByName('product_code'),' ', 8)." ".
			PadWithCharacter($qry->FieldByName('product_description'),' ', 20)." ".
			StuffWithCharacter($tmp_qty,' ', 8)." ".
			StuffWithCharacter($flt_price,' ', 8)." ".
			StuffWithCharacter($qry->FieldByName('discount'),' ', 5)." ".
			StuffWithCharacter(number_format(($discount_price * $tmp_qty),2,'.',','),' ',8)." ".
			StuffWithCharacter($qry->FieldByName('tax_description'),' ',8)." ".
			StuffWithCharacter($tax_amount,' ',8)." ".
			StuffWithCharacter($flt_amount,' ', 10)."\n";

		$total += $flt_amount;
		$total_qty += $qry->FieldByName('quantity');
		$total_taxable_value += ($discount_price * $tmp_qty);
		$total_tax_amount += $tax_amount;
		$total_amount += 100; //(($discount_price * $tmp_qty) + $tax_amount);

		
		$qry->Next();
	}

	$commission = $total_taxable_value * ($flt_percent/100);
	
	$commission_2 = 0;
	if ($flt_percent_2 > 0)
		$commission_2 = $total_taxable_value * ($flt_percent_2/100);
		
	$commission_3 = 0;
	if ($flt_percent_3 > 0)
		$commission_3 = $total_taxable_value * ($flt_percent_3/100);
		
	$total = number_format($total,2,'.','');
	$commission = number_format($commission,2,'.','');
	$commission_2 = number_format($commission_2,2,'.','');
	$commission_3 = number_format($commission_3,2,'.','');
	$flt_percent = number_format($flt_percent,2,'.','');
	$flt_percent_2 = number_format($flt_percent_2,2,'.','');
	$flt_percent_3 = number_format($flt_percent_3,2,'.','');

	$given = $total - $commission - $commission_2 - $commission_3;
	
}
else {
	$category_current = '';
	$total = 0;
	$total_qty = 0;
	
	$str_data = "";
	$line_counter = 9;
	
	for ($i=0;$i<$qry->RowCount();$i++) {
		if ($calc_price == "BP")
			$flt_price = number_format(getBuyingPrice($qry->FieldByName('product_id')), 2,'.','');
		else
			$flt_price = number_format($qry->FieldByName('price'), 2, '.', '');
		$discount = $qry->FieldByName('discount');
		$is_debit_bill = $qry->FieldByName('is_debit_bill');
		/*
			quantity includes adjusted (see query)
		*/
		$quantity = $qry->FieldByName('quantity');

		if ($str_include_tax == 'Y') {
			$tax_id = $qry->FieldByName('tax_id');

			if ($discount > 0) {
				if ($str_calc_tax_first == 'Y') {
					$tax_price = round($flt_price + calculateTax($flt_price, $tax_id),3);
					$tax_amount = calculateTax(($flt_price * $quantity), $tax_id);
					$flt_discount = round(($quantity * $tax_price) * ($discount/100),3);

					$flt_amount = round(($quantity * $tax_price - $discount), 3);
				}
				else {
					$discount_price = round(($flt_price * (1 - ($discount/100))), 3);
					$tax_amount = calculateTax($quantity * $discount_price, $tax_id);
					$flt_amount = round(($quantity * $discount_price + $tax_amount), 3);
				}
			}
			else {
				$tax_amount = calculateTax($flt_price * $quantity, $tax_id);
				$flt_amount = round(($quantity * $flt_price + $tax_amount), 3);
			}
			$flt_amount = number_format($flt_amount, 2, '.', '');
		}
		else {
			if ($discount > 0) {
				$flt_amount = number_format(($flt_price * (1 - ($discount/100)) * $quantity), 2, '.','');
			}
			else {
				$flt_amount = number_format(($flt_price * $quantity), 2, '.','');
			}
		}

		if ($is_debit_bill == 'Y')
			$flt_amount = $flt_amount * -1;
		
		if ($category_current <> $qry->FieldByName('category_description')) {
			$str_data .= $qry->FieldByName('category_description')."\n";
			$category_current = $qry->FieldByName('category_description');
			$line_counter++;
		}
		
		$tmp_qty = $qry->FieldByName('quantity');
		
		if ($qry->FieldByName('is_decimal') == 'Y') 
			$flt_quantity = number_format($tmp_qty, 2, '.', '');
		else
			$flt_quantity = number_format($tmp_qty, 0, '.', '');
		
		$str_data .= StuffWithCharacter($qry->FieldByName('product_code'),' ', 8)." ".
			PadWithCharacter($qry->FieldByName('product_description'),' ', 20)." ".
			StuffWithCharacter($flt_price,' ', 8)." ".
			StuffWithCharacter($flt_quantity,' ', 8)." ".
			StuffWithCharacter($flt_amount,' ', 10)."\n";
		
		$total += $flt_amount;
		$total_qty += $qry->FieldByName('quantity');
		
		if ($line_counter >= 65) {
			$line_counter = 0;
			$str_data .= "%e";
		}
		$line_counter++;
		
		$qry->Next();
	}
	
	$commission = $total * ($flt_percent/100);

	$commission_2 = 0;
	if ($flt_percent_2 > 0)
		$commission_2 = $total * ($flt_percent_2/100);
		
	$commission_3 = 0;
	if ($flt_percent_3 > 0)
		$commission_3 = $total * ($flt_percent_3/100);
		
	$given = $total - $commission - $commission_2 - $commission_3;
}

$int_spaces = 20 - 8;
$str_spaces = '';
$str_spaces = StuffWithCharacter($str_spaces, ' ', $int_spaces);
$str_totals = $str_spaces."Total : ";

$int_spaces = 10 - strlen(sprintf("%01.2f", $total));
$str_spaces = '';
$str_spaces = StuffWithCharacter($str_spaces, ' ', $int_spaces);
$str_totals .= $str_spaces.sprintf("%01.2f", $total)."\n";

$int_spaces = 20 - strlen("Commission ".$flt_percent."% : ");
$str_spaces = '';
$str_spaces = StuffWithCharacter($str_spaces, ' ', $int_spaces);
$str_totals .= $str_spaces."Commission ".$flt_percent."% : ";

$int_spaces = 10 - strlen(sprintf("%01.2f", $commission));
$str_spaces = '';
$str_spaces = StuffWithCharacter($str_spaces, ' ', $int_spaces);
$str_totals .= $str_spaces.sprintf("%01.2f", $commission)."\n";

if ($flt_percent_2 > 0) {
	$int_spaces = 20 - strlen("Commission ".$flt_percent_2."% : ");
	$str_spaces = '';
	$str_spaces = StuffWithCharacter($str_spaces, ' ', $int_spaces);
	$str_totals .= $str_spaces."Commission ".$flt_percent_2."% : ";
	
	$int_spaces = 10 - strlen(sprintf("%01.2f", $commission_2));
	$str_spaces = '';
	$str_spaces = StuffWithCharacter($str_spaces, ' ', $int_spaces);
	$str_totals .= $str_spaces.sprintf("%01.2f", $commission_2)."\n";
}

if ($flt_percent_3 > 0) {
	$int_spaces = 20 - strlen("Commission ".$flt_percent_3."% : ");
	$str_spaces = '';
	$str_spaces = StuffWithCharacter($str_spaces, ' ', $int_spaces);
	$str_totals .= $str_spaces."Commission ".$flt_percent_3."% : ";
	
	$int_spaces = 10 - strlen(sprintf("%01.2f", $commission_3));
	$str_spaces = '';
	$str_spaces = StuffWithCharacter($str_spaces, ' ', $int_spaces);
	$str_totals .= $str_spaces.sprintf("%01.2f", $commission_3)."\n";
}
	
$int_spaces = 20 - 8;
$str_spaces = '';
$str_spaces = StuffWithCharacter($str_spaces, ' ', $int_spaces);
$str_totals .= $str_spaces."Given : ";

$int_spaces = 10 - strlen(sprintf("%01.2f", $given));
$str_spaces = '';
$str_spaces = StuffWithCharacter($str_spaces, ' ', $int_spaces);
$str_totals .= $str_spaces.sprintf("%01.2f", $given);

$str_eject_lines = "";
for ($i=0;$i<$int_eject_lines;$i++) {
  $str_eject_lines .= "\n"; 
}

$str_statement = "
".$str_application_title."
".$str_print_address."
".$str_print_phone."

".$str_title."
".$str_top."
".$str_header."
".$str_bottom."
".$str_data."
".$str_bottom."
".$str_totals."
".$str_top."%e";

$str_statement = replaceSpecialCharacters($str_statement);
?>

<PRE>
<?
	echo $str_statement;
?>
</PRE>


<form name="printerForm" method="POST" action="http://localhost/print.php">

<table width="100%" bgcolor="#E0E0E0">
  <tr>
    <td height=45 class="headerText" bgcolor="#808080">
      &nbsp;<font class='title'>Printing Statement</font>
    </td>
  </tr>
  <tr>
    <td>
      <br>

      <input type="hidden" name="data" value="<? echo ($str_statement); ?>"><br>

	  <input type="hidden" name="os" value="<? echo $os;?>"><br>
	  <input type="hidden" name="print_name" value="<? echo $print_name?>"><br>
	  <input type="hidden" name="print_mode" value="<? echo $print_mode?>"><br>

    </td>
  </tr>
  <tr>
    <td class='normaltext'>
      <textarea name='printerStatus' height=5 rows=5 cols=40 class='editbox'></textarea>
    </td>
  </tr>
  <tr>
    <td align='center'>
      <br><input type='submit' name='doaction' value="Print">
      <input type='button' onclick="window.close();" name='doaction' value="Close">
    </td>
  </tr>
</table>

</form>


<script language="JavaScript">
	printerForm.submit();
</script>


</body>
</html>
