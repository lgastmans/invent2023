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


	/*
		settings
	*/
	$str_calc_tax_first = 'N';

	$qry_settings = new Query("SELECT gstin FROM user_settings WHERE storeroom_id = ".$_SESSION['int_current_storeroom']);
	

	$data = array();

	/*
		only bills from clients, ie where module_id = 7
	*/
	if (getModuleByID(9) !== null) {
		$str_bills = "
			SELECT b.*, c.gstin
			FROM ".Monthalize('bill')." b
			LEFT JOIN ".Monthalize('orders')." o ON (o.order_id = b.module_record_id)
			LEFT JOIN customer c ON (c.id = o.CC_id)
			WHERE (
						(bill_status = ".BILL_STATUS_RESOLVED.")
						OR (bill_status = ".BILL_STATUS_DISPATCHED.")
						OR (bill_status = ".BILL_STATUS_DELIVERED.")
					)
				AND (b.storeroom_id = ".$_SESSION["int_current_storeroom"].")
				AND (b.module_id = 7)
			ORDER BY bill_number";	
	} else {
		$data['ERROR'] = "Cannot display statement: missing Clients module";
		echo json_encode($data);
		die();
	}


	$qry_bills = new Query($str_bills);	


	/*
		data
	*/
	$counter = 0 ;

	for ($i=0; $i<$qry_bills->RowCount(); $i++) {


		/*
			billed items
		*/
		$qry_items = new Query("
			SELECT bi.*, sp.product_description, sc.hsn, smu.measurement_unit
			FROM ".Monthalize('bill_items')." bi
			LEFT JOIN stock_product sp ON (sp.product_id = bi.product_id)
			LEFT JOIN stock_category sc ON (sc.category_id = sp.category_id)
			LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			WHERE (bi.bill_id = ".$qry_bills->FieldByName('bill_id').")
		");


		$invoice_value = 0;
		$tax_total = 0;

		$taxes = array();

		for ($j=0; $j<$qry_items->RowCount(); $j++) {
			
			$flt_quantity = number_format($qry_items->FieldByName('quantity') + $qry_items->FieldByName('adjusted_quantity'), 3, '.', '');
			$tmp_price = $qry_items->FieldByName('price');
			$tmp_discount = $qry_items->FieldByName('discount');
			$flt_discount = 0;
			$flt_transfer_tax = 0;
			
			/*
				tax
			*/
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

				//echo "discount ".$calc_price."<br>";
				
			}
			else {
				$calc_price = $tmp_price;
				$tmp_taxes = getTaxBreakdown($calc_price * $flt_quantity, $qry_items->FieldByName('tax_id'));

				//echo " NO discount ".$calc_price."<br>";
			}

			/*
				value
			*/
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
			/*
			$data[$i]['sgst'] = 0;
			$data[$i]['cgst'] = 0;
			$data[$i]['igst'] = 0;

			if ($qry_bills->FieldByName('module_id') == 2) {
				$data[$i]['sgst'] = $tmp_taxes[1] / 2;
				$data[$i]['cgst'] = $tmp_taxes[1] / 2;
			}
			else {
				$qry_client = new Query("
						SELECT c.is_other_state
						FROM orders o
						LEFT JOIN customer c ON (c.id = o.CC_id)
						WHERE o.id = ".$qry_bills->FieldByName('module_record_id')
					);

				if ($qry_client->FieldByName('is_other_state')=='Y') {
					$data[$i]['igst'] = $tmp_taxes[1];
				} else {
					$data[$i]['sgst'] = $tmp_taxes[1] / 2;
					$data[$i]['cgst'] = $tmp_taxes[1] / 2;
				}
			}
			*/
			
			$invoice_value += $tmp_amount;
			$tax_total += $tmp_amount;

			/*
				$tmp_taxes[0] is the definition_id
			*/
			$sql = "SELECT st.tax_description 
				FROM ".Monthalize('stock_tax_links')." stl 
				LEFT JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = stl.tax_id) 
				WHERE tax_definition_id = ". $tmp_taxes[0];
			$qry_rate = new Query($sql);
			$rate = '';
			if ($qry_rate->RowCount() > 0)
				$rate = $qry_rate->FieldByName('tax_description');


			if (array_key_exists($tmp_taxes[0], $taxes)) {

				$taxes[$tmp_taxes[0]]['rate'] = $rate;

				$add = round((float)$taxes[$tmp_taxes[0]]['taxable_value'],3) + round((float)$tmp_amount,3);
				$taxes[$tmp_taxes[0]]['taxable_value'] = number_format($add,2,'.','');

				$add = round((float)$taxes[$tmp_taxes[0]]['cess'],3) + round((float)$tmp_taxes[1],3);
				$taxes[$tmp_taxes[0]]['cess'] = number_format($add,2,'.','');

			} else {

				$taxes[$tmp_taxes[0]]['rate'] = $rate;
				$taxes[$tmp_taxes[0]]['taxable_value'] = number_format(round((float)$tmp_amount,3),2,'.','');
				$taxes[$tmp_taxes[0]]['cess'] = number_format(round((float)$tmp_taxes[1],3),2,'.','');

			}

			$qry_items->Next();
		}
	

		foreach ($taxes as $key=>$value){
			$data[$counter]['gstin'] = (is_null($qry_bills->FieldByName('gstin')) ? "" : $qry_bills->FieldByName('gstin'));
			$data[$counter]['invoice_number'] = $qry_bills->FieldByName('bill_number');
			$data[$counter]['invoice_date'] = set_formatted_date($qry_bills->FieldByName('date_created'),"-");
			$data[$counter]['invoice_value'] = $invoice_value;
			$data[$counter]['place_of_supply'] = (is_null($qry_bills->FieldByName('supply_place')) ? "" : $qry_bills->FieldByName('supply_place'));
			$data[$counter]['reverse_charge'] = 'N';
			$data[$counter]['invoice_type'] = 'Regular';
			$data[$counter]['ecom_gstin'] = '';

			$data[$counter]['rate'] = $value['rate'];
			$data[$counter]['taxable_value'] = $value['taxable_value'];
			$data[$counter]['cess'] = $value['cess'];

			$counter++;
		}

//print_r($data);
		

		$qry_bills->Next();
		
	}

	echo json_encode($data);
?>