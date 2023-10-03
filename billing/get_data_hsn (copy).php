<?php
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/tax.php");

	/*
		E or OE (mostly OE)
		POS (mostly TN or PY)
		Combined Tax Rate (%)
		Taxable Value
		Cess Amount
		GSTIN (if applicable)
	*/


	$str_calc_tax_first = 'N';


	$str_bills = "
		SELECT *
		FROM ".Monthalize('bill')."
		WHERE (
					(bill_status = ".BILL_STATUS_RESOLVED.")
					OR (bill_status = ".BILL_STATUS_DISPATCHED.")
					OR (bill_status = ".BILL_STATUS_DELIVERED.")
				)
			AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
		ORDER BY bill_number";	

	$qry_bills = new Query($str_bills);	


	$data = array();

	$counter = 0;

	for ($i=0; $i<$qry_bills->RowCount(); $i++) {

		
		$qry_items = new Query("
			SELECT bi.*, sp.product_description, sc.hsn, sc.category_description, smu.measurement_unit
			FROM ".Monthalize('bill_items')." bi
			LEFT JOIN stock_product sp ON (sp.product_id = bi.product_id)
			LEFT JOIN stock_category sc ON (sc.category_id = sp.category_id)
			LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			WHERE (bi.bill_id = ".$qry_bills->FieldByName('bill_id').")
		");

		$tax_total = 0;

		for ($j=0; $j<$qry_items->RowCount(); $j++) {
			
			$flt_quantity = number_format($qry_items->FieldByName('quantity') + $qry_items->FieldByName('adjusted_quantity'), 3, '.', '');
			$tmp_price = $qry_items->FieldByName('price');
			$tmp_discount = $qry_items->FieldByName('discount');
			$flt_discount = 0;
			$flt_transfer_tax = 0;
			
			if ($tmp_discount > 0) {
				/*
				if ($str_calc_tax_first == 'Y') {
					$tmp_taxes = getTaxBreakdown($tmp_price * $flt_quantity, $qry_items->FieldByName('tax_id'));
					$calc_price = number_format($tmp_price + calculateTax($tmp_price, $qry_items->FieldByName('tax_id')),3);
					$flt_discount = number_format(($flt_quantity * $calc_price) * ($tmp_discount/100),3);
					$calc_price = $tmp_price;
				}
				else {
				*/
				
				$calc_price = number_format(round((float)($tmp_price * (1 - ($tmp_discount/100))), 3),2,'.','');
				$tmp_taxes = getTaxBreakdown($calc_price * $flt_quantity, $qry_items->FieldByName('tax_id'));
				
			}
			else {
				$calc_price = $tmp_price;
				$tmp_taxes = getTaxBreakdown($calc_price * $flt_quantity, $qry_items->FieldByName('tax_id'));
			}

			if ($qry_bills->FieldByName('is_debit_bill') == 'Y') {
				$amount_before_discount = ($tmp_price * $flt_quantity) * -1;
				$tmp_amount = ($calc_price * $flt_quantity) * -1;
			}
			else {
				$amount_before_discount = ($tmp_price * $flt_quantity);
				$tmp_amount = ($calc_price * $flt_quantity);
			}
			
			/*
				if module_id = 2 it is a POS bill, in which case it intra-state: S(U)GST/CGST
				if module_id = 7 it is an INVOICE, in which the client has to verified
			*/
			$sgst = 0;
			$cgst = 0;
			$igst = 0;

			if ($qry_bills->FieldByName('module_id') == 2) {
				$sgst = $tmp_taxes[1] / 2;
				$cgst = $tmp_taxes[1] / 2;
			}
			else {
				$qry_client = new Query("
						SELECT c.is_other_state
						FROM ".Monthalize('orders')." o
						LEFT JOIN customer c ON (c.id = o.CC_id)
						WHERE o.order_id = ".$qry_bills->FieldByName('module_record_id')
					);

				if ($qry_client->FieldByName('is_other_state')=='Y') {
					$igst = $tmp_taxes[1];
				} else {
					$sgst = number_format(($tmp_taxes[1]/2), 2);
					$cgst = number_format(($tmp_taxes[1]/2), 2);
				}
			}

			$tax_total += $tmp_amount;


			$hsn = $qry_items->FieldByName('hsn');

			if (array_key_exists($hsn, $data)) {

				$data[$hsn]['hsn'] = $qry_items->FieldByName('hsn');
				$data[$hsn]['description'] = $qry_items->FieldByName('category_description');
				$data[$hsn]['unit'] = $qry_items->FieldByName('measurement_unit');
				$data[$hsn]['sgst'] += $sgst;
				$data[$hsn]['cgst'] += $cgst;
				$data[$hsn]['igst'] += $igst;
				$data[$hsn]['qty'] += $flt_quantity;
				$data[$hsn]['total_value'] += $amount_before_discount;
				$data[$hsn]['taxable_value'] += $tmp_amount;
				$data[$hsn]['cess'] += $tmp_taxes[1];

			} else {
				$data[$hsn]['hsn'] = $qry_items->FieldByName('hsn');
				$data[$hsn]['description'] = $qry_items->FieldByName('category_description');
				$data[$hsn]['unit'] = $qry_items->FieldByName('measurement_unit');
				$data[$hsn]['sgst'] = $sgst;
				$data[$hsn]['cgst'] = $cgst;
				$data[$hsn]['igst'] = $igst;
				$data[$hsn]['qty'] = $flt_quantity;
				$data[$hsn]['total_value'] = $amount_before_discount;
				$data[$hsn]['taxable_value'] = $tmp_amount;
				$data[$hsn]['cess'] = $tmp_taxes[1];
			}

			$counter++;

			$qry_items->Next();
		}
	
		$qry_bills->Next();
	}

	echo json_encode($data);

?>