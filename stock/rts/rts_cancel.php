<?

require_once("../../include/const.inc.php");

function cancelReceipt($f_record_id) {

	$str_result_message = "Receipt $f_record_id cancelled successfully.";

	$qry_bill = new Query("SELECT *
		FROM ".Monthalize('stock_rts')."
		WHERE (stock_rts_id = $f_record_id)
	");

	if ($qry_bill->RowCount() > 0) {
		// check the status of the bill
		if ($qry_bill->FieldByName('bill_status') == BILL_STATUS_CANCELLED) {
			$str_result_message = "Receipt already cancelled";
		}
		else {

			$qry_transaction = new Query("START TRANSACTION");

			// get the corresponding items for the given bill
			$qry_items = new Query("SELECT * 
				FROM ".Monthalize('stock_rts_items')."
				WHERE rts_id = ".$qry_bill->FieldByName('stock_rts_id')."
			");
	
			$cancel_success = true;
	
			// update the stock for each item
			for ($i=0; $i<$qry_items->RowCount(); $i++) {
				if (!cancelItem($qry_items->FieldByName('product_id'), 
						$qry_items->FieldByName('quantity'), 
						$qry_items->FieldByName('batch_id'),
						$qry_bill->FieldByName('bill_number'),
						$qry_bill->FieldByName('stock_rts_id'))) {
					$cancel_success = false;
					$str_result_message = "Item id ".$qry_items->FieldByName('product_id')." could not be cancelled";
					break;
				}
	
				$qry_items->Next();
			}
	
			// set the receipt's status to CANCELLED
			$qry_bill->Query("UPDATE ".Monthalize('stock_rts')."
				SET bill_status = ".BILL_STATUS_CANCELLED."
				WHERE (stock_rts_id = $f_record_id)
			");
			if ($qry_bill->b_error == true) {
				$cancel_success = false;
				$str_result_message = "Receipt status modification";
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
			$str_message = "Error: stock_storeroom_product";
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
			$str_message = "Error: stock_storeroom_batch";
			$bool_success = false;
		}

		// TABLE stock_balance
		$result_set->Query("UPDATE ".Yearalize('stock_balance')."
				SET stock_cancelled = stock_cancelled + ".$flt_quantity.",
					stock_closing_balance = stock_closing_balance + ".$flt_quantity."
				WHERE (product_id = ".$int_product_id.") AND
					(storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
					(balance_month = ".$_SESSION["int_month_loaded"].") AND
					(balance_year = ".$_SESSION["int_year_loaded"].")");
		if ($result_set->b_error == true) {
			$str_message = "Error: stock_balance";
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
				"CANCELLATION OF RTD TO SECTION NUMBER ".$int_bill_number."', '".
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
			$str_message = "Error: stock_transfer";
			$bool_success = false;
		}
		
		if (!$bool_success) {
			echo "<script language=\"javascript\">";
			echo "alert('".$str_message."')";
			echo "</script>";
		}
		return $bool_success;
	}

?>
