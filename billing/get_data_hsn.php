<?php

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db_mysqli.php");
	require_once("../common/tax.php");

	$str_calc_tax_first = 'Y';

	$qry_storeroom = $conn->Query("
		SELECT is_cash_taxed, is_account_taxed
		FROM stock_storeroom
		WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")
	");
	$is_cash_taxed = 'Y';
	$is_account_taxed = 'Y';
	if ($qry_storeroom->num_rows > 0) {
		$obj = $qry_storeroom->fetch_object();
		$is_cash_taxed = $obj->is_cash_taxed;
		$is_account_taxed = $obj->is_account_taxed;
	}

	/*
		get all taxes 
		that are not "surcharge"
	*/
	$sql = "
		SELECT std.*, st.tax_id AS tax_id
		FROM ".Monthalize('stock_tax_links')." stl
		INNER JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = stl.tax_id)
		INNER JOIN ".Monthalize('stock_tax_definition')." std ON (std.definition_id = stl.tax_definition_id)
		WHERE (std.definition_type <> 2) AND (st.is_active='Y')
		ORDER BY definition_type, definition_percent
	";	
	$qry_taxes = $conn->query($sql);

	$arr_taxes = array();

	while ($obj = $qry_taxes->fetch_object()) {

		$arr_taxes[] = ['tax_id'=>$obj->tax_id."S", 'description'=>"Sales<br>".$obj->definition_description];

		if ($obj->definition_percent <>0){
			$arr_taxes[] = ['tax_id'=>$obj->tax_id, 'description'=>($obj->definition_percent/2)."%"];
			$arr_taxes[] = ['tax_id'=>$obj->tax_id, 'description'=>($obj->definition_percent/2)."%"];
		}
	}

//print_r($arr_taxes);die();


	/*
		get the hsn codes
		GROUP_CONCAT() function returns a string with concatenated non-NULL value from a group
	*/
	$sql = "
		SELECT hsn, GROUP_CONCAT(category_id) AS category_id
		FROM stock_category 
		GROUP BY hsn
	";
	$qry_hsn = $conn->query($sql);


	$arr_hsn = array();

	while ($obj = $qry_hsn->fetch_object()) {

		$arr_hsn[] = ['hsn' => $obj->hsn, 'category_id' => $obj->category_id];

	}
	$arr_hsn[] = ['hsn' => "TOTALS", 'category_id' => 'T'];

//print_r($arr_hsn);die();


	/*
		initialize the array

	*/

	$data = array();

	foreach ($arr_hsn as $row) {

		$data[$row['category_id']]['hsn'] = $row['hsn'];

		foreach($arr_taxes as $col) {

			$data[$row['category_id']][$col['tax_id']] = number_format(0,2,'.','');

		}

	}


	/*
		returns the row based on hsn 
		an hsn code can have multiple categories
	*/
	function getCoords($category_id=0, $tax_id=0) {
		$x=0;

		global $data;
		foreach($data as $key=>$value) {
			$srch = explode(',',$key);
			if (in_array($category_id,$srch)) {
				$x = $key;
				continue;
			}
		}

		return $x;
	}



	/*
		get the list of items billed

	*/
	/*
	$sql = "
		SELECT b.is_debit_bill, b.payment_type, bi.*, bi.discount AS discount, bi.tax_id AS tax_id, sp.tax_id AS stock_tax_id, sp.category_id AS category_id
		FROM ".Monthalize('bill_items')." bi
		INNER JOIN ".Monthalize('bill')." b ON (b.bill_id = bi.bill_id)
		INNER JOIN stock_product sp ON (sp.product_id = bi.product_id)
		WHERE (DATE(date_created) = '".sprintf("%04d-%02d-%02d", $_SESSION["int_year_loaded"], $_SESSION["int_month_loaded"], 2)."')
		AND (
				(bill_status = ".BILL_STATUS_RESOLVED.")
				OR (bill_status = ".BILL_STATUS_DISPATCHED.")
				OR (bill_status = ".BILL_STATUS_DELIVERED.")
			)
			AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
		ORDER BY date_created";
	*/
	$sql = "
		SELECT b.is_debit_bill, b.payment_type, bi.*, bi.discount AS discount, bi.tax_id AS tax_id, sp.tax_id AS stock_tax_id, sp.category_id AS category_id
		FROM ".Monthalize('bill_items')." bi
		INNER JOIN ".Monthalize('bill')." b ON (b.bill_id = bi.bill_id)
		INNER JOIN stock_product sp ON (sp.product_id = bi.product_id)
		WHERE (
				(bill_status = ".BILL_STATUS_RESOLVED.")
				OR (bill_status = ".BILL_STATUS_DISPATCHED.")
				OR (bill_status = ".BILL_STATUS_DELIVERED.")
			)
			AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
		ORDER BY date_created";
	
	$qry_items = $conn->query($sql);


	$tax_total = 0;
	$i = 0;

	while ($obj = $qry_items->fetch_object()) {

		$flt_quantity = number_format(($obj->quantity + $obj->adjusted_quantity), 3, '.', '');
		$tmp_price = $obj->price;
		$tmp_discount = $obj->discount;
		$flt_discount = 0;
		
		if ($tmp_discount > 0) {

			if ($str_calc_tax_first == 'Y') {
				$tmp_taxes = getTaxBreakdown($tmp_price * $flt_quantity, $obj->tax_id);
				$calc_price = round($tmp_price + calculateTax($tmp_price, $obj->tax_id),3);
				$flt_discount = round(($flt_quantity * $calc_price) * ($tmp_discount/100),3);
				$calc_price = $tmp_price;
			}
			else {
				$calc_price = round(($tmp_price * (1 - ($tmp_discount/100))), 3);
				$tmp_taxes = getTaxBreakdown($calc_price * $flt_quantity, $obj->tax_id);
			}
		}
		else {
			$calc_price = $tmp_price;
			$tmp_taxes = getTaxBreakdown($calc_price * $flt_quantity, $obj->tax_id);
		}

		if ($obj->is_debit_bill == 'Y')
			$tmp_amount = ($calc_price * $flt_quantity) * -1;
		else
			$tmp_amount = ($calc_price * $flt_quantity);

		
		$coords = getCoords($obj->category_id);

		// add quantity under the right column for each returned value and tax id

		$x = $coords;
		$y = $obj->tax_id; 

		if ($obj->is_debit_bill == 'Y')
			$tmp_taxes[1] = $tmp_taxes[1] * -1;
		

		if (($obj->payment_type == BILL_CASH) 
			|| ($obj->payment_type == BILL_CREDIT_CARD)
		) {
			if ($is_cash_taxed == 'Y')
				$flt_temp = round($tmp_taxes[1], 3);
			else
				$flt_temp = 0;
		}
		else if (($obj->payment_type == BILL_ACCOUNT) 
			|| ($obj->payment_type == BILL_PT_ACCOUNT) || ($obj->payment_type == BILL_AUROCARD)
			|| ($obj->payment_type == BILL_TRANSFER_GOOD)
		) {
			if ($is_account_taxed == 'Y')
				$flt_temp = round($tmp_taxes[1],3);
			else
				$flt_temp = 0;
		}

		$data[$x][$y] = number_format(round((float)$data[$x][$y],3) + round((float)$flt_temp,3),2,'.','');
		$data[$x][$y."S"] = number_format(round((float)$data[$x][$y."S"],3) + round((float)$tmp_amount,3),2,'.','');

		$coords = getCoords('T');

		$data[$coords][$y] = number_format(round((float)$data[$coords][$y],3) + round((float)$flt_temp,3),2,'.','');
		$data[$coords][$y."S"] = number_format(round((float)$data[$coords][$y."S"],3) + round((float)$tmp_amount,3),2,'.','');

	}  /* while $qry_items */


	/*
		columns for datatable
	*/
	$columns = array();

	$columns[0]['data'] = 'hsn';
	$columns[0]['name'] = 'HSN';
	$counter = 1;
	foreach($arr_taxes as $col) {
		$columns[$counter]['data'] = $col['tax_id'];
		$columns[$counter]['name'] = $col['description'];
		$counter++;
	}


	/*
		reset the main key (it should not contain the commas) of the array for Datatable
	*/
	// foreach($data as $value)
	// 	$vdata[] = $value;

	foreach($data as $value) {
		
		$row = array();

		foreach ($value as $key=>$val) {

			$pos = strpos($key, "S");

			if ($key=='hsn')
				$row[$key] = $val;
			else if ($pos===false)
				$row[$key] = number_format($val/2,2,'.',',');
			else
				$row[$key] = number_format($val,2,'.',',');

		}

		$vdata[] = $row;
	}

//print_r($data);die();


	$ret = array("data"=>$vdata,"columns"=>$columns,"footer"=>'TOTALS');

	echo json_encode($ret);
?>