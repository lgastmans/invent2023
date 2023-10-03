<?
/*
	this function deducts stock from bills
*/

function product_bill(
	$batch_id,
	$product_id,
	$quantity,
	$adjusted_quantity,
	$bill_number,
	$bill_day,
	$module_id,
	$module_record_id,
	$is_DC='N') {
	
	
	$str_retval = "OK|OK";
	
	//***
	// get the batch_id
	//***
	$result_set = new Query("
		SELECT batch_id, supplier_id
		FROM ".Yearalize('stock_batch')."
		WHERE (batch_id = ".$batch_id.") 
			AND (product_id = ".$product_id.")
			AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
	");
	$current_batch_id = $result_set->FieldByName('batch_id');
	
	//***
	// check whether a manual transfer should be made
	//***
	if ($adjusted_quantity > 0) {
		$str_retval = createManualTransfer(
			$adjusted_quantity,
			$product_id,
			$batch_id,
			$bill_number,
			$bill_day,
			$module_record_id,
			$module_id);
		$arr_retval = explode($str_retval, "|");
		if ($arr_retval[0] == 'ERROR') {
			$bool_success = false;
			$str_retval = $arr_retval[1];
		}
	}
	
	$flt_quantity_to_bill = number_format(($quantity + $adjusted_quantity), 3,'.','');
	
	//***
	// TABLE stock_storeroom_product
	//***
	$result_set->Query("UPDATE ".Monthalize('stock_storeroom_product')."
		SET stock_current = ABS(ROUND((stock_current - ".$flt_quantity_to_bill."),3))
		WHERE (product_id=".$product_id.")
			AND (storeroom_id=".$_SESSION["int_current_storeroom"].")"
	);
	if ($result_set->b_error == true) {
		$str_retval = "ERROR|Error updating stock_storeroom_product";
	}
	
	//***
	// TABLE stock_storeroom_batch
	// There was some strange behaviour subtracting here, hence the ROUND function call
	// very small amounts were generated, as -7.12548e-9
	//***
	$result_set->Query("
		UPDATE ".Monthalize('stock_storeroom_batch')."
		SET stock_available = ABS(ROUND((stock_available - ".$flt_quantity_to_bill."),3))
		WHERE (batch_id = ".$current_batch_id.")
			AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
			AND (product_id = ".$product_id.")
	");
	if ($result_set->b_error == true) {
		$str_retval = "ERROR|Error updating stock_storeroom_batch";
	}
	
	//***
	// if the current stock becomes zero, then set the batch's is_active flag to false
	// if there is more than one active batch available. There should always be one active
	// batch regardless of the available stock
	//***
	$result_set->Query("
		SELECT stock_available 
		FROM ".Monthalize('stock_storeroom_batch')."
		WHERE (batch_id = ".$current_batch_id.")
			AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
			AND (product_id = ".$product_id.")
	");
	$flt_stock_available = number_format($result_set->FieldByName('stock_available'),3,'.','');
	if ($flt_stock_available <= 0) {
		//***
		// check number of available active batches,
		// and if it is greater than one
		// set the current batch's is_active flag to false
		//***
		$qry_check = new Query("
			SELECT * 
			FROM ".Monthalize('stock_storeroom_batch')." 
			WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")
				AND (product_id = ".$product_id.")
				AND (is_active = 'Y')
		");
		if ($qry_check->RowCount() > 1) {
			$result_set->Query("
				UPDATE ".Monthalize('stock_storeroom_batch')."
				SET is_active = 'N',
					debug = 'billing'
				WHERE (batch_id = ".$current_batch_id.")
					AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
					AND (product_id = ".$product_id.")
			");
		}
	}
	
	//***
	// TABLE stock_balance
	//***
	$result_set->Query("
		UPDATE ".Yearalize('stock_balance')."
		SET stock_sold = stock_sold + ".$quantity.",
			stock_closing_balance = ROUND((stock_closing_balance - ".$quantity."),3)
		WHERE (product_id = ".$product_id.")
			AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
			AND (balance_month = ".$_SESSION["int_month_loaded"].")
			AND (balance_year = ".$_SESSION["int_year_loaded"].")
	");
	if ($result_set->b_error == true) {
		$str_retval = "ERROR|Error updating stock_balance";
	}
	
	//***
	// TABLE stock_transfer
	//***
	if ($is_DC=='N') {
		$transfer_description = "BILL NUMBER ".$bill_number;
		$transfer_type = TYPE_BILL;
	}
	else {
		$transfer_description = "DC NUMBER ".$bill_number;
		$transfer_type = TYPE_DC;
	}
	
	$result_set->Query("
		INSERT INTO  ".Monthalize('stock_transfer')."
			(transfer_quantity,
			transfer_description,
			date_created,
			module_id,
			user_id,
			storeroom_id_from,
			storeroom_id_to,
			product_id,
			batch_id,
			module_record_id,
			transfer_type,
			transfer_status,
			user_id_dispatched,
			user_id_received,
			is_deleted)
		VALUES(".
			$quantity.", '". // here, only index 2 is saved, as a manual transfer
			$transfer_description."', '".  // for the remaining amount is already created
			getBillDate($bill_day)."', ".
			$module_id.", ".
			$_SESSION["int_user_id"].", ".
			$_SESSION["int_current_storeroom"].", ".
			"0, ".
			$product_id.", ".
			$current_batch_id.", ".
			$module_record_id.", ".
			$transfer_type.", ".
			STATUS_COMPLETED.", ".
			$_SESSION["int_user_id"].", ".
			"0, ".
			"'N')
	");
	if ($result_set->b_error == true) {
		$str_retval = "ERROR|Error updating stock_transfer for product id ".$product_id." and batch id ".$current_batch_id;
	}
		
	return $str_retval;
}


function createManualTransfer($adjusted_quantity, $product_id, $batch_id, $bill_number, $bill_day, $module_record_id, $module_id, $is_DC='N') {

	$str_retval = "OK|OK";

	$qry_string = "
		UPDATE ".Monthalize('stock_storeroom_product')."
		SET stock_current = stock_current + ".$adjusted_quantity."
		WHERE (product_id=".$product_id.")
			AND (storeroom_id=".$_SESSION["int_current_storeroom"].")";

	//***
	// TABLE stock_storeroom_product
	//***
	$result_set = new Query($qry_string);
	if ($result_set->b_error == true) {
		$str_retval = "ERROR|Manual transfer - Error updating stock_storeroom_product";
	}

	//***
	// TABLE stock_storeroom_batch
	//***
	$result_set->Query("
		UPDATE ".Monthalize('stock_storeroom_batch')."
		SET stock_available = stock_available + ".$adjusted_quantity."
		WHERE (batch_id = ".$batch_id.") 
			AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
			AND (product_id = ".$product_id.")
	");
	if ($result_set->b_error == true) {
		$str_retval = "ERROR|Manual transfer - Error updating stock_storeroom_batch";
	}

	//***
	// TABLE stock_balance
	//***
	$result_set->Query("
		UPDATE ".Yearalize('stock_balance')."
		SET stock_sold = stock_sold + ".$adjusted_quantity."
		WHERE (product_id = ".$product_id.")
			AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
			AND (balance_month = ".$_SESSION["int_month_loaded"].")
			AND (balance_year = ".$_SESSION["int_year_loaded"].")
	");
	if ($result_set->b_error == true) {
		$str_retval = "ERROR|Manual transfer - Error updating stock_balance";
	}

	//***
	// TABLE stock_transfer
	//***
	if ($is_DC=='N') {
		$transfer_description = "BILL NUMBER ".$bill_number;
	}
	else {
		$transfer_description = "DC NUMBER ".$bill_number;
	}
	
	$result_set->Query("
		INSERT INTO  ".Monthalize('stock_transfer')."
			(transfer_quantity,
			transfer_description,
			date_created,
			module_id,
			user_id,
			storeroom_id_from,
			storeroom_id_to,
			product_id,
			batch_id,
			module_record_id,
			transfer_type,
			transfer_status,
			user_id_dispatched,
			user_id_received,
			is_deleted)
		VALUES(".
			$adjusted_quantity.", '".
			$transfer_description."', '".
			getBillDate($bill_day)."', ".
			$module_id.", ".
			$_SESSION["int_user_id"].", ".
			$_SESSION["int_current_storeroom"].", ".
			"0, ".
			$product_id.", ".
			$batch_id.", ".
			$module_record_id .", ".
			TYPE_ADJUSTMENT.", ".
			STATUS_COMPLETED.", ".
			$_SESSION["int_user_id"].", ".
			"0, ".
			"'N')
	");
	if ($result_set->b_error == true) {
		$str_retval = "ERROR|Manual transfer - Error updating stock_transfer";
	}

	//***
	// TABLE stock_product
	//***
	$result_set->Query("
		UPDATE ".Monthalize('stock_storeroom_product')."
		SET stock_adjusted = stock_adjusted + ".$adjusted_quantity."
		WHERE (product_id = ".$product_id.")
			AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
	");
	if ($result_set->b_error == true) {
		$str_retval = "ERROR|Manual transfer - Error updating stock_storeroom_product";
	}

	return $str_retval;
}


function getBillDate($int_day) {
	$str_date = $_SESSION["int_year_loaded"]."-".sprintf("%02d", $_SESSION["int_month_loaded"])."-".$int_day." ".date("H:i:s");
	
	return $str_date;
}

?>