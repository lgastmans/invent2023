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

	/*
		only bills from billing, ie where module_id = 2
	*/
	$str_bills = "
		SELECT *
		FROM ".Monthalize('bill')."
		WHERE (
					(bill_status = ".BILL_STATUS_RESOLVED.")
					OR (bill_status = ".BILL_STATUS_DISPATCHED.")
					OR (bill_status = ".BILL_STATUS_DELIVERED.")
				)
			AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
			AND (module_id = 2)
		ORDER BY bill_number";	

//echo $str_bills;

	$qry_bills = new Query($str_bills);	

	$data = array();

	$counter = 0;

	for ($i=0; $i<$qry_bills->RowCount(); $i++) {

		$sql = "
			SELECT * 
			FROM ".Monthalize('bill_items')."
			WHERE (bill_id = ".$qry_bills->FieldByName('bill_id').")";

		$qry_items = new Query($sql);

		//====================
		// column 0 is bill numbers
		//====================
		$tax_total = 0;


		for ($j=0; $j<$qry_items->RowCount(); $j++) {
			
			$data[$counter]['type'] = "OE";
			$data[$counter]['pos'] = "TN";

			$flt_quantity = number_format($qry_items->FieldByName('quantity') + $qry_items->FieldByName('adjusted_quantity'), 3, '.', '');
			$tmp_price = $qry_items->FieldByName('price');
			$tmp_discount = $qry_items->FieldByName('discount');
			$flt_discount = 0;
			$flt_transfer_tax = 0;
			
			if ($tmp_discount > 0) {
				/*
				if ($str_calc_tax_first == 'Y') {
					$tmp_taxes = getTaxBreakdown($tmp_price * $flt_quantity, $qry_items->FieldByName('tax_id'));
					$calc_price = round($tmp_price + calculateTax($tmp_price, $qry_items->FieldByName('tax_id')),3);
					$flt_discount = round(($flt_quantity * $calc_price) * ($tmp_discount/100),3);
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
				$tmp_amount = ($calc_price * $flt_quantity) * -1;
			}
			else {
				$tmp_amount = ($calc_price * $flt_quantity);
			}
			
			/*
				$tmp_taxes[0] is the definition_id
			*/
			$sql = "SELECT st.tax_description 
				FROM ".Monthalize('stock_tax_links')." stl 
				LEFT JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = stl.tax_id) 
				WHERE tax_definition_id = ". $tmp_taxes[0];
			$qry_rate = new Query($sql);

			$data[$counter]['rate'] = '';
			if ($qry_rate->RowCount() > 0)
				$data[$counter]['rate'] = $qry_rate->FieldByName('tax_description');
			$data[$counter]['value'] = number_format(round((float)$tmp_amount,3),2,'.','');
			$data[$counter]['cess'] = number_format(round((float)$tmp_taxes[1],3),2,'.','');
			$data[$counter]['gstin'] = '';

			$counter++;

			$qry_items->Next();
		}
	
		$qry_bills->Next();
	}

	echo json_encode($data);

?>