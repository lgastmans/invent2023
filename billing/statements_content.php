<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/tax.php");
	require_once("../common/product_funcs.inc.php");

	$calc_price = "BP";
	if (IsSet($_GET['price']))
		$calc_price = $_GET['price'];
	
	$where_filter_day = "";
	if (IsSet($_GET['filter_day']) && ($_GET['filter_day']!='ALL'))
		$where_filter_day = "AND (DAYOFMONTH(b.date_created)=".$_GET['filter_day'].") ";


	$_SESSION["int_bills_menu_selected"] = 8;

	$sql_settings = new Query("
		SELECT *
		FROM user_settings
	");
	/*
	if ($sql_settings->RowCount() > 0) {
		$str_calc_tax_first = $sql_settings->FieldByName('calculate_tax_before_discount');
	}
	*/
	$str_calc_tax_first = "N";

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
			$str_order_by = 'b.date_created, sp.product_code';
		else if ($_GET['order_by'] == 'code')
			$str_order_by = 'sp.product_code';
	}
	
	$_SESSION['global_current_supplier_id'] = $int_supplier_id;
	
	$str_include_tax = 'Y';
	if (IsSet($_GET['include_tax']))
		$str_include_tax = $_GET['include_tax'];


	$qry_supplier = new Query("
		SELECT is_supplier_delivering
		FROM stock_supplier
		WHERE supplier_id = $int_supplier_id
	");

	$consignment = ($qry_supplier->FieldByName('is_supplier_delivering') == 'Y' ? true : false);


	/*
		for previous month/year requests get the commissions
		from the table stock_supplier_commissions
	*/
	if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
		$qry_supplier = new Query("
			SELECT commission_percent, commission_percent_2, commission_percent_3
			FROM stock_supplier
			WHERE (supplier_id = ".$int_supplier_id.")
		");
	}
	else {
		$str = "
			SELECT commission_percent, commission_percent_2, commission_percent_3
			FROM stock_supplier_commissions ssc
			WHERE (ssc.`supplier_id` = ".$int_supplier_id.")
				AND (ssc.`month` = ".$_SESSION["int_month_loaded"].")
				AND (ssc.`year` = ".$_SESSION["int_year_loaded"].")
		";
		$qry_supplier = new Query($str);
	}
	
	$flt_percent = 0;
	$flt_percent_2 = 0;
	$flt_percent_3 = 0;
	if ($qry_supplier->RowCount() > 0) {
		$flt_percent = $qry_supplier->FieldByName('commission_percent');
		$flt_percent_2 = $qry_supplier->FieldByName('commission_percent_2');
		$flt_percent_3 = $qry_supplier->FieldByName('commission_percent_3');
	}
	
	if ($str_format == 'DATE_BILL') {

		$select_clause = '';
		$from_clause = '';
		$where_clause = 'AND (sp.product_id = bi.product_id)';
		$where_clause2 = "AND (sb.supplier_id = ".$int_supplier_id.")";
		if ($_GET['supplier_id']=='__ALL') {
			$select_clause = 'ss.supplier_name, ';
			$from_clause = ', stock_supplier ss';
			$where_clause = 'AND ((sp.product_id = bi.product_id) AND (sp.supplier_id = ss.supplier_id))';
			$where_clause2 = '';
			$str_order_by = "ss.supplier_name, ".$str_order_by;
		}

		$str_query = "
			SELECT $select_clause DAYOFMONTH(b.date_created) AS date_created, b.bill_number, b.is_debit_bill,
				sp.product_code, sp.product_id,
				bi.product_description,
				bi.price, bi.bprice,
				bi.batch_id,
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
				$from_clause
			WHERE (bi.bill_id = b.bill_id)
				AND (
						(b.bill_status = ".BILL_STATUS_RESOLVED.")
						OR (b.bill_status = ".BILL_STATUS_DELIVERED.")
					)
				$where_clause
				AND (sb.product_id = bi.product_id)
				$where_clause2
				AND (sb.batch_id = bi.batch_id)
				AND (sp.measurement_unit_id = smu.measurement_unit_id)
				AND (sp.category_id = sc.category_id)
				AND (bi.tax_id = st.tax_id)
				$where_filter_day
			ORDER BY $str_order_by";
	}
	else {
		$str_query = "
			SELECT sp.product_code, sp.product_id,
				bi.product_description,
				bi.price, bi.bprice,
				GROUP_CONCAT(bi.batch_id) AS `batches`,
				bi.batch_id,
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
			GROUP BY bi.product_id, bi.price, b.is_debit_bill, bi.discount
			ORDER BY sc.category_description, sp.product_code
		";
	}

	//echo $str_query;
	$qry = new Query($str_query);
?>

<html>
    <head>
        <link type="text/css" href="../include/styles.css" rel="stylesheet"/>
		<?php if (($_GET['supplier_id']=='__ALL') && ($where_filter_day == "")) { ?>
	    	<link href="../include/bootstrap-3.3.4-dist/css/bootstrap.min.css" rel="stylesheet"/>
	        <style>
				body {
					margin:25px;
				}
			</style>
		<?php } ?>
    </head>

	<body id='body_bgcolor' leftmargin=15 topmargin=5 marginwidth=5 marginheight=5>


	<?php
		if (($_GET['supplier_id']=='__ALL') && ($where_filter_day == "")) {
			echo "<div class='container'><div class='alert alert-danger' role='alert'>Day cannot be \"ALL\" when supplier is set to \"ALL\"</div></div>";
			die();
		}
	?>

	<font class='normaltext'>
	<table border=1 cellpadding=7 cellspacing=0>
	<?php
		if ($str_format == "DATE_BILL") {
			$date_current = 0;
			$total = 0;
			$total_qty = 0;
			$total_taxable_value = 0;
			$total_tax_amount = 0;
			$total_amount = 0;

			$tax_totals = array();
			$current_supplier = '';
			
			for ($i=0;$i<$qry->RowCount();$i++) {
				if ($i % 2 == 0)
					$str_color="#eff7ff";
				else
					$str_color="#deecfb";
				
				echo "<tr class='normaltext' bgcolor=".$str_color.">";
				
				if ($calc_price == "BP") {
					//$flt_price = getBuyingPrice($qry->FieldByName('product_id'), $qry->FieldByName('batch_id'));
					//$flt_price = number_format($flt_price, 2,'.','');
					//echo $qry->FieldByName('product_id').":".$qry->FieldByName('batch_id').":".$flt_price."||";
					$flt_price = number_format($qry->FieldByName('bprice'), 2,'.','');
					if ($flt_price == 0)
						$flt_price = getBuyingPrice($qry->FieldByName('product_id'), $qry->FieldByName('batch_id'));
				}
				else {
					//$flt_price = getSellingPrice($qry->FieldByName('product_id'), $qry->FieldByName('batch_id'));
					//$flt_price = number_format($flt_price, 2,'.','');
					$flt_price = number_format($qry->FieldByName('price'), 2, '.', '');
					if ($flt_price == 0)
						$flt_price = getSellingPrice($qry->FieldByName('product_id'), $qry->FieldByName('batch_id'));
				}


				/*
					if supplier is direct sales, 
					there is no discount
					on the buying price
				*/
				$discount = $qry->FieldByName('discount');

				if ((!$consignment) && ($calc_price == "BP")) {
					$discount =  0;
				}


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

				
				if ($_GET['supplier_id']=='__ALL') {
					if ($qry->FieldByName('supplier_name') != $current_supplier) {
						echo "<td width='150px'>".$qry->FieldByName('supplier_name')."</td>";
						$current_supplier = $qry->FieldByName('supplier_name');
					}
					else
						echo "<td width='150px'>&nbsp;</td>";
				}

				if ($str_order_by == 'b.date_created, sp.product_code') {

					if ($date_current < $qry->FieldByName('date_created')) {

						echo "<td width='50px'>".$qry->FieldByName('date_created')."</td>";
						$date_current = $qry->FieldByName('date_created');

					}
					else
						echo "<td width='50px'>&nbsp;</td>";
				}
				else
					echo "<td width='50px'>".$qry->FieldByName('date_created')."</td>";

				
				echo "<td width='60px' align=right>".$qry->FieldByName('bill_number')."</td>";

				echo "<td width='60px' align=right>".$qry->FieldByName('batch_id')."</td>";

				echo "<td width='120px' align=right>".$qry->FieldByName('product_code')."</td>";

				echo "<td width='250px'>".$qry->FieldByName('product_description')."</td>";

				$tmp_qty = $qry->FieldByName('quantity');
				if ($qry->FieldByName('is_decimal') == 'Y')
					echo "<td width='80px' align=right>".number_format($tmp_qty, 2, '.', '')."</td>";
				else
					echo "<td width='80px' align=right>".number_format($tmp_qty, 0, '.', '')."</td>";

				echo "<td width='80px' align=right>".$flt_price."</td>";

				echo "<td width='80px' align=right>".$discount."</td>";

				echo "<td width='80px' align=right>".number_format(($discount_price * $tmp_qty),2,'.',',')."</td>";

				if ($str_include_tax == 'Y') {
					echo "<td width='60px' align=right>".$qry->FieldByName('tax_description')."</td>";
					echo "<td width='60px' align=right>".$tax_amount."</td>";
				}
				
				
				echo "<td width='120px' align=right>".$flt_amount."</td>";
				
				echo "</tr>";

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
		}
		else {
			$category_current = '';
			$total = 0;
			$total_qty = 0;
			$total_taxable_value = 0;
			
			for ($i=0;$i<$qry->RowCount();$i++) {
				if ($i % 2 == 0)
					$str_color="#eff7ff";
				else
					$str_color="#deecfb";
				
				if ($calc_price == "BP") {
					//$flt_price = getBuyingPrice($qry->FieldByName('product_id'), $qry->FieldByName('batch_id'));
					//$flt_price = number_format($flt_price, 2,'.','');
					$flt_price = number_format($qry->FieldByName('bprice'), 2,'.','');
				}
				else
					$flt_price = number_format($qry->FieldByName('price'), 2, '.', '');
				
				/*
					if supplier is direct sales, 
					there is no discount
					on the buying price
				*/

				$discount = $qry->FieldByName('discount');

				if ((!$consignment) && ($calc_price == "BP")) {
					$discount =  0;
				}

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
				
				if ($category_current <> $qry->FieldByName('category_description')) {
					echo "<tr class='normaltext' bgcolor='lightgrey'>";
					echo "<td width='50px' colspan='7'><b>".$qry->FieldByName('category_description')."</b></td></tr>";
					$category_current = $qry->FieldByName('category_description');
				}
				echo "<tr class='normaltext' bgcolor=".$str_color.">";
				
				echo "<td width='120px'>".$qry->FieldByName('product_code')."</td>";
				echo "<td width='250px'>".$qry->FieldByName('product_description')."</td>";

				//echo "<td width='120px'>".$qry->FieldByName('batches')."</td>";

				$tmp_qty = $qry->FieldByName('quantity');
				if ($qry->FieldByName('is_decimal') == 'Y')
					echo "<td width='80px' align=right>".number_format($tmp_qty, 2, '.', '')."</td>";
				else
					echo "<td width='80px' align=right>".number_format($tmp_qty, 0, '.', '')."</td>";

				echo "<td width='80px' align=right>".$flt_price."</td>";
				echo "<td width='80px' align=right>".$qry->FieldByName('discount')."</td>";
				echo "<td width='80px' align=right>".number_format(($discount_price * $tmp_qty),2,'.',',')."</td>";

				echo "<td width='120px' align=right>".$flt_amount."</td>";
				echo "</tr>";
				
				$total += $flt_amount;
				$total_qty += $qry->FieldByName('quantity');
				$total_taxable_value += $discount_price * $tmp_qty;
				$total_tax_amount += $tax_amount;

				
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
		}

		$total_qty = number_format($total_qty,2,'.','');
		$total_taxable_value = number_format($total_taxable_value,2,'.','');
		$total_tax_amount = number_format($total_tax_amount,2,'.','');
		$total_amount = number_format($total_amount,2,'.','');

		$taxes = urlencode(json_encode($tax_totals));

	?>
	</table>
	</font>
	<script language='javascript'>

		str_header = "statements_header.php?include_tax=<?echo $str_include_tax;?>&format=<?php echo $str_format?>&supplier_id=<? echo $_GET['supplier_id']?>";

		parent.frames["header"].document.location = str_header;

		str_footer = "statements_footer.php?total=<?echo $total_taxable_value;?>&commission=<?echo $commission;?>&commission_2=<?echo $commission_2;?>&commission_3=<?echo $commission_3;?>&percent=<?echo $flt_percent;?>&percent_2=<?echo $flt_percent_2;?>&percent_3=<?echo $flt_percent_3;?>&total_qty=<?php echo $total_qty;?>&total_taxable_value=<?php echo $total_taxable_value;?>&total_tax_amount=<?php echo $total_tax_amount;?>&total_amount=<?php echo $total_amount;?>&calc_price=<?php echo $calc_price;?>&taxes=<?php print_r($taxes);?>";

		parent.frames["footer"].document.location = str_footer;

	</script>
</body>
</html>
