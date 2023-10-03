<?

/*

stock is returned through a debit bill, or a debit invoice
and is added to the most recent active batch

if there is adjusted stock for the given product
	case 1: quantity > current adjusted
		a. the current adjusted quantity should be treated as case 2 below
		b. the remainder should be treated as case 3 below
	case 2: quantity < current adjusted
		a. deduct from the stock adjusted in the STOCK_STOREROOM_PRODUCT TABLE
		b. add to the received quantity
	case 3: no current adjusted
		a. added to the current stock in the STOCK_STOREROOM_PRODUCT table
		b. added to the received quantity,
			added to the closing balance in the STOCK_BALANCE table
		c. added to the available stock in the STOCK_STOREROOM_BATCH

*/

require_once("product_funcs.inc.php");

function debit_receive($product_id, $quantity, $bill_number, $bill_day, $module_id, $module_record_id) {
	$str_retval = "OK|Successful";
	
	$str_date = $_SESSION["int_year_loaded"]."-".sprintf("%02d", $_SESSION["int_month_loaded"])."-".$bill_day." ".date("H:i:s");
	
	//***
	// get a list of the active batches
	// and set the id of the first 
	//***
	$arr_batches = get_active_batches($product_id);
	
	$batch_id = $arr_batches[0][1];
	
	//***
	// query initialization
	//***
	$result_set = new Query("SELECT * FROM stock_product LIMIT 1");
	
	//***
	// get the adjusted quantity for the given product
	//***
	$result_set->Query("
		SELECT stock_adjusted
		FROM ".Monthalize('stock_storeroom_product')."
		WHERE (product_id=".$product_id.")
			AND (storeroom_id=".$_SESSION["int_current_storeroom"].")
	");
	if ($result_set->b_error == true) {
		$str_retval = "ERROR|Error retrieving adjusted quantity from stock_storeroom_product";
	}
	
	$flt_stock_adjusted = number_format($result_set->FieldByName('stock_adjusted'),3,'.',',');
	
	$flt_quantity = number_format($quantity, 3,'.','');
	
	if ($flt_stock_adjusted == 0) {
		$str_retval = apply_cancellation(
			$product_id,
			$quantity,
			$batch_id,
			$bill_number,
			$module_record_id,
			$module_id);
	}
	else {
		//***
		//case 1: quantity > current adjusted
		//***
		if ($flt_quantity > $flt_stock_adjusted) {
			
			$flt_remainder = $flt_quantity - $flt_stock_adjusted;
			
			$result_set = new Query("
				UPDATE ".Monthalize('stock_storeroom_product')."
				SET stock_adjusted = ROUND((stock_adjusted - $flt_stock_adjusted),3)
				WHERE (product_id = $product_id)
					AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
			");
			if ($result_set->b_error == true) {
				$str_retval = "ERROR|Error updating stock_product";
			}
			
			$result_set->Query("
				UPDATE ".Yearalize('stock_balance')."
				SET stock_sold = ROUND(stock_sold - ".$flt_stock_adjusted.", 3)
				WHERE (product_id = ".$product_id.")
					AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
					AND (balance_month = ".$_SESSION["int_month_loaded"].")
					AND (balance_year = ".$_SESSION["int_year_loaded"].")
			");
			if ($result_set->b_error == true) {
				$str_retval = "ERROR|Error updating stock_balance";
			}
			
			$str_retval = apply_cancellation(
				$product_id,
				$flt_remainder,
				$batch_id,
				$bill_number,
				$module_record_id,
				$module_id);
		}
		//***
		//case 2: quantity < current adjusted
		//***
		else {
			$result_set = new Query("
				UPDATE ".Monthalize('stock_storeroom_product')."
				SET stock_adjusted = ROUND((stock_adjusted - $flt_quantity),3)
				WHERE (product_id = $product_id)
					AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
			");
			if ($result_set->b_error == true) {
				$str_retval = "ERROR|Error updating stock_product";
			}
			
			$result_set->Query("
				UPDATE ".Yearalize('stock_balance')."
				SET stock_received = ROUND(stock_received + ".$flt_quantity.", 3)
				WHERE (product_id = ".$product_id.")
					AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
					AND (balance_month = ".$_SESSION["int_month_loaded"].")
					AND (balance_year = ".$_SESSION["int_year_loaded"].")
			");
			if ($result_set->b_error == true) {
				$str_retval = "ERROR|Error updating stock_balance";
			}
		}
	}
	
	return $str_retval;
}


function apply_cancellation($int_product_id, $flt_quantity, $int_batch_id, $int_bill_number, $module_record_id, $module_id) {
	$bool_success = "ok|ok";
	
	//***
	// TABLE stock_storeroom_product
	// the current stock gets incremented
	//***
	$qry_string = "UPDATE ".Monthalize('stock_storeroom_product')."
		SET stock_current = stock_current + ".$flt_quantity."
		WHERE (product_id=".$int_product_id.") 
			AND (storeroom_id=".$_SESSION["int_current_storeroom"].")";
	
	$result_set = new Query($qry_string);
	if ($result_set->b_error == true) {
		$bool_success = "ERROR|Error updating ".Monthalize('stock_storeroom_product');
	}
	
	//***
	// TABLE stock_storeroom_batch
	// the available stock gets incremented for the batch
	//***
	$qry_string = "
		UPDATE ".Monthalize('stock_storeroom_batch')."
		SET stock_available = stock_available + ".$flt_quantity.",
			is_active = 'Y'
		WHERE (batch_id = ".$int_batch_id.")
			AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
			AND (product_id = ".$int_product_id.")";
	$result_set->Query($qry_string);
	if ($result_set->b_error == true) {
		$bool_success = "ERROR|Error updating ".Monthalize('stock_storeroom_batch');
	}
	
	//***
	// TABLE stock_balance
	// increment the received quantity,
	// increment the closing balance
	//***
	$qry_string = "
		UPDATE ".Yearalize('stock_balance')."
		SET stock_received = stock_received + ".$flt_quantity.",
			stock_closing_balance = stock_closing_balance + ".$flt_quantity."
		WHERE (product_id = ".$int_product_id.")
			AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
			AND (balance_month = ".$_SESSION["int_month_loaded"].")
			AND (balance_year = ".$_SESSION["int_year_loaded"].")";
	$result_set->Query($qry_string);
	if ($result_set->b_error == true) {
		$bool_success = "ERROR|Error updating ".Yearalize('stock_balance');
	}
	
	//***
	// TABLE stock_transfer
	//***
	$qry_string = "
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
			$flt_quantity.", '".
			"DEBIT BILL NUMBER ".$int_bill_number."', '".
			date("Y-m-d H:i:s")."', ".
			$module_id.", ".
			$_SESSION["int_user_id"].", ".
			"0, ".
			$_SESSION["int_current_storeroom"].", ".
			$int_product_id.", ".
			$int_batch_id.", ".
			$module_record_id.", ".
			TYPE_DEBIT_BILL.", ".
			STATUS_COMPLETED.", ".
			$_SESSION["int_user_id"].", ".
			"0, ".
			"'N')";
	$result_set->Query($qry_string);
	if ($result_set->b_error == true) {
		$bool_success = "ERROR|Error inserting into ".Monthalize('stock_transfer');
	}
	
	return $bool_success;
}

?>