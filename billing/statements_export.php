<?php

	header("Content-Type: application/text; name=supplier_statement.csv");
	header("Content-Transfer-Encoding: binary");
	header("Content-Disposition: attachment; filename=supplier_statement.csv");
	header("Expires: 0");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");


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

	$_SESSION["int_bills_menu_selected"] = 7;

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
			GROUP BY bi.product_id, bi.price, b.is_debit_bill, bi.discount
			ORDER BY sc.category_description, sp.product_code
		";

	//echo $str_query;
	$qry = new Query($str_query);


		if ($str_format == "DATE_BILL") {

			if ($str_include_tax == 'Y')
				echo '"Date","Bill","Code","Description","Qty","Price","Discount","Taxable Value","Tax Rate","Tax Amt","Amount"';
			else
				echo '"Date","Bill","Code","Description","Qty","Price","Discount","Taxable Value","Amount"';
			echo "\n\r";

			$date_current = 0;
			$total = 0;
			$total_qty = 0;
			$total_taxable_value = 0;
			$total_tax_amount = 0;
			$total_amount = 0;

			$current_supplier = '';
			
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
				
				if ($is_debit_bill == 'Y')
					$flt_amount = $flt_amount * -1;

				
				if ($_GET['supplier_id']=='__ALL') {
					if ($qry->FieldByName('supplier_name') != $current_supplier) {
						echo '"'.$qry->FieldByName('supplier_name').'", ';
						$current_supplier = $qry->FieldByName('supplier_name');
					}
					else
						echo '" ",';
				}

				if ($str_order_by == 'b.date_created, sp.product_code') {

					if ($date_current < $qry->FieldByName('date_created')) {

						echo '"'.$qry->FieldByName('date_created').'",';
						$date_current = $qry->FieldByName('date_created');

					}
					else
						echo '" ",';
				}
				else
					echo '"'.$qry->FieldByName('date_created').'",';

				
				echo '"'.$qry->FieldByName('bill_number').'",';

				echo '"'.$qry->FieldByName('product_code').'",';

				echo '"'.$qry->FieldByName('product_description').'",';

				$tmp_qty = $qry->FieldByName('quantity');
				if ($qry->FieldByName('is_decimal') == 'Y')
					echo '"'.number_format($tmp_qty, 2, '.', '').'",';
				else
					echo '"'.number_format($tmp_qty, 0, '.', '').'",';

				echo '"'.$flt_price.'",';

				echo '"'.$qry->FieldByName('discount').'",';

				echo '"'.number_format(($discount_price * $tmp_qty),2,'.',',').'",';

				if ($str_include_tax == 'Y') {
					echo '"'.$qry->FieldByName('tax_description').'",';
					echo '"'.$tax_amount.'",';
				}
				
				
				echo '"'.$flt_amount.'"';
				
				echo "\n\r";

				$total += $flt_amount;
				$total_qty += $qry->FieldByName('quantity');
				$total_taxable_value += ($discount_price * $tmp_qty);
				$total_tax_amount += $tax_amount;
				$total_amount += $ftl_amount;

				
				$qry->Next();
			}

			$commission = $total * ($flt_percent/100);
			
			$commission_2 = 0;
			if ($flt_percent_2 > 0)
				$commission_2 = $total * ($flt_percent_2/100);
				
			$commission_3 = 0;
			if ($flt_percent_3 > 0)
				$commission_3 = $total * ($flt_percent_3/100);
				
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
				
				if ($is_debit_bill == 'Y')
					$flt_amount = $flt_amount * -1;
				
				if ($category_current <> $qry->FieldByName('category_description')) {
					echo '"'.$qry->FieldByName('category_description').'"\n\r';
					$category_current = $qry->FieldByName('category_description');
				}
				echo '"\n\r';
				
				echo '"'.$qry->FieldByName('product_code').'",';
				echo '"'.$qry->FieldByName('product_description').'",';

				$tmp_qty = $qry->FieldByName('quantity');
				if ($qry->FieldByName('is_decimal') == 'Y')
					echo '"'.number_format($tmp_qty, 2, '.', '').'",';
				else
					echo '"'.number_format($tmp_qty, 0, '.', '').'",';

				echo '"'.$flt_price.'",';
				echo '"'.$qry->FieldByName('discount').'",';
				echo '"'.($discount_price * $tmp_qty).'",';

				echo '"'.$flt_amount.'"';
				echo "\n\r";
				
				$total += $flt_amount;
				$total_qty += $qry->FieldByName('quantity');
				$total_taxable_value = $discount_price * $tmp_qty;
				
				$qry->Next();
			}
			
			$commission = $total * ($flt_percent/100);
			
			$commission_2 = 0;
			if ($flt_percent_2 > 0)
				$commission_2 = $total * ($flt_percent_2/100);
				
			$commission_3 = 0;
			if ($flt_percent_3 > 0)
				$commission_3 = $total * ($flt_percent_3/100);
				
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

		
		if ($calc_price == 'SP') {

			echo '"Total","'.number_format($flt_total, 2, '.', ',').'"\n\r';
			echo '"Commission'.$flt_percent.'%",'.number_format($commission, 2, '.', ',').'"\n\r';

			if ($flt_percent_2 > 0) { 
				echo '"Commission'.$flt_percent_2.'%",'.number_format($commission_2, 2, '.', ',').'"\n\r';
			} 
			
			if ($flt_percent_3 > 0) {
				echo '"Commission'.$flt_percent_3.'%:",'.number_format($commission_3, 2, '.', ',').'"\n\r';
			}

			//echo '"Given :",'.number_format($given, 2, '.', ',').'"\n\r';

		}

		if ($calc_price == 'BP') {
			echo '" "," "," "," ",';
			echo '"'.$total_qty.'",';
			echo '" "," ",';
			echo '"'.number_format($total_taxable_value,2,'.',',').'",';
			echo '" ",';
			echo '"'.number_format($total_tax_amount,2,'.',',').'",';
			echo '"'.number_format($total,2,'.',',').'"';
			echo "\n\r";
		}
?>