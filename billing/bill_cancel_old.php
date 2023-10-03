<?

require_once("../include/const.inc.php");

function cancelBill($f_record_id) {

	$str_result_message = "Bill $f_record_id cancelled successfully.";

	$qry_bill = new Query("SELECT *
		FROM ".Monthalize('bill')."
		WHERE (bill_id = $f_record_id)
	");

	if ($qry_bill->RowCount() > 0) {
		// check the status of the bill
		if ($qry_bill->FieldByName('bill_status') == BILL_STATUS_CANCELLED) {
			$str_result_message = "Bill already cancelled";
		}
		else {

			$qry_transaction = new Query("START TRANSACTION");

			// get the corresponding items for the given bill
			$qry_items = new Query("SELECT * 
				FROM ".Monthalize('bill_items')."
				WHERE bill_id = ".$qry_bill->FieldByName('bill_id')."
			");
	
			$cancel_success = true;
	
      // if it is a PT Account bill
      if ($qry_bill->FieldByName('payment_type') == BILL_PT_ACCOUNT) {
        // adjust the closing balance
        $qry_account = new Query("
          UPDATE ".Monthalize('account_pt_balances')."
          SET closing_balance = closing_balance + ".$qry_bill->FieldByName('total_amount')."
          WHERE (account_id = ".$qry_bill->FieldByName('CC_id').")
        ");
        
        // set the transfer status to "cancelled"
        $qry_account->Query("
          UPDATE ".Monthalize('account_pt_transfers')."
          SET transfer_status = ".ACCOUNT_TRANSFER_CANCELLED."
          WHERE (module_record_id = ".$qry_bill->FieldByName('bill_id').")
        ");
      }
      
			// update the stock for each item
			for ($i=0; $i<$qry_items->RowCount(); $i++) {
				if (!cancelItem($qry_items->FieldByName('product_id'), 
						$qry_items->FieldByName('quantity'), 
						$qry_items->FieldByName('batch_id'),
						$qry_bill->FieldByName('bill_number'),
						$qry_bill->FieldByName('bill_id'))) {
					$cancel_success = false;
					$str_result_message = "Item id ".$qry_items->FieldByName('product_id')."could not be cancelled";
					break;
				}
	
				$qry_items->Next();
			}
	
			// set the bill's status to CANCELLED
			$qry_bill->Query("UPDATE ".Monthalize('bill')."
				SET bill_status = ".BILL_STATUS_CANCELLED."
				WHERE (bill_id = $f_record_id)
			");
			if ($qry_bill->b_error == true) {
				$cancel_success = false;
				$str_result_message = "Bill status modification";
			}
	
			if ($cancel_success)
				$qry_transaction->Query("COMMIT");
			else
				$qry_transaction->Query("ROLLBACK");
		}
	}

	return $str_result_message;
}

	function cancelItem($int_product_id, $flt_quantity, $int_batch_id, $int_bill_number, $bill_id) {

		$bool_success = true;

		$qry_string = "UPDATE ".Monthalize('stock_storeroom_product')."
			SET stock_current = stock_current + ".$flt_quantity."
			WHERE (product_id=".$int_product_id.") AND
				(storeroom_id=".$_SESSION["int_current_storeroom"].")";

		// TABLE stock_storeroom_product
		$result_set = new Query($qry_string);
		if ($result_set->b_error == true) {
			$bool_success = false;
		}

		// TABLE stock_storeroom_batch
		$result_set->Query("UPDATE ".Monthalize('stock_storeroom_batch')."
			SET stock_available = stock_available + ".$flt_quantity.",
				is_active = 'Y'
			WHERE (batch_id = ".$int_batch_id.") AND
				(storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
				(product_id = ".$int_product_id.")
		");
		if ($result_set->b_error == true) {
			$bool_success = false;
		}

		// TABLE stock_balance
		$result_set->Query("UPDATE ".Yearalize('stock_balance')."
				SET stock_sold = stock_sold + ".$flt_quantity."
				WHERE (product_id = ".$int_product_id.") AND
					(storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
					(balance_month = ".$_SESSION["int_month_loaded"].") AND
					(balance_year = ".$_SESSION["int_year_loaded"].")");
		if ($result_set->b_error == true) {
			$bool_success = false;
		}

		// TABLE stock_transfer
		$result_set->Query("INSERT INTO  ".Monthalize('stock_transfer')."
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
				"CANCELLATION OF BILL NUMBER ".$int_bill_number."', '".
				date("Y-m-d H:i:s")."', ".
				"2, ".
				$_SESSION["int_user_id"].", ".
				"0, ".
				$_SESSION["int_current_storeroom"].", ".
				$int_product_id.", ".
				$int_batch_id.", ".
				$bill_id.", ".
				TYPE_ADJUSTMENT.", ".
				STATUS_COMPLETED.", ".
				$_SESSION["int_user_id"].", ".
				"0, ".
				"'N')");
		if ($result_set->b_error == true) {
			$bool_success = false;
		}

		return $bool_success;
	}

?>
