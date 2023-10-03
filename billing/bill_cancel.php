<?
if (file_exists("const.inc.php")) {
	require_once("const.inc.php");
}
else if (file_exists("include/const.inc.php")) {
	require_once("include/const.inc.php");
}
else if (file_exists("../include/const.inc.php")) {
	require_once("../include/const.inc.php");
}
else if (file_exists("../../include/const.inc.php")) {
	require_once("../../include/const.inc.php");
}
else if (file_exists("../../../include/const.inc.php")) {
	require_once("../../../include/const.inc.php");
}

require_once($str_application_path."common/account.php");
require_once($str_application_path."common/product_batches.php");
require_once($str_application_path."common/product_cancel.php");


/*
	the "int_origin" parameter defines which module is
	cancelling the bill
	ie, a bill linked to an order can only be cancelled
	from the order module
*/
function cancelBill($f_record_id, $str_reason, $int_origin) {

	$str_result_message = "OK|Bill cancelled successfully.";

	$qry_bill = new Query("
		SELECT bill_credit_account, bill_description
		FROM stock_storeroom
		WHERE (storeroom_id = ".$_SESSION['int_current_storeroom'].")
	");
	$credit_acount = $qry_bill->FieldByName('bill_credit_account');

	$qry_bill->Query("SELECT *
		FROM ".Monthalize('bill')."
		WHERE (bill_id = $f_record_id)
			AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
	");

	if ($qry_bill->RowCount() > 0) {
		//=============================
		// check the status of the bill
		//-----------------------------
		if ($qry_bill->FieldByName('bill_status') == BILL_STATUS_CANCELLED) {
			$str_result_message = "ERROR|Bill already cancelled";
		}
		//=====================================
		// check the module cancelling the bill
		//-------------------------------------
		else if ($qry_bill->FieldByName('module_id') <> $int_origin) {
			if ($qry_bill->FieldByName('module_id') == 7)
				$str_result_message = "ERROR|This bill is related to an order. Cancel the corresponding order";
			else
				$str_result_message = "ERROR|No rights to cancel this bill";
		}
		else {
			
			$qry_transaction = new Query("START TRANSACTION");
			
			//===============================================
			// get the corresponding items for the given bill
			//-----------------------------------------------
			$qry_items = new Query("
				SELECT *
				FROM ".Monthalize('bill_items')."
				WHERE bill_id = ".$qry_bill->FieldByName('bill_id')."
			");
			
			$cancel_success = true;
			
			//===========================
			// if it is a PT Account bill
			//---------------------------
			if ($qry_bill->FieldByName('payment_type') == BILL_PT_ACCOUNT) {
				//===========================
				// adjust the closing balance
				//---------------------------
				$qry_account = new Query("
					UPDATE ".Monthalize('account_pt_balances')."
					SET closing_balance = closing_balance + ".$qry_bill->FieldByName('total_amount')."
					WHERE (account_id = ".$qry_bill->FieldByName('CC_id').")
				");
				
				//=======================================
				// set the transfer status to "cancelled"
				//---------------------------------------
				$qry_account->Query("
					UPDATE ".Monthalize('account_pt_transfers')."
					SET transfer_status = ".ACCOUNT_TRANSFER_CANCELLED."
					WHERE (module_record_id = ".$qry_bill->FieldByName('bill_id').")
				");
			}
			
			/*
				update the stock for each item
			*/
			for ($i=0; $i<$qry_items->RowCount(); $i++) {
				/*
					if the batch_id is zero, it will be an item from an order
					where the delivered quantity was zero
				*/
				if ($qry_items->FieldByName('batch_id') > 0) {
					$str_result = cancelItem(
							$qry_items->FieldByName('product_id'),
							$qry_items->FieldByName('quantity'),
							$qry_items->FieldByName('batch_id'),
							$qry_bill->FieldByName('bill_number'),
							$qry_bill->FieldByName('bill_id'),
							$qry_items->FieldByName('adjusted_quantity')
							);
							
					$arr_result = explode("|", $str_result);
					if ($arr_result[0] == 'false') {
						$cancel_success = false;
						$str_result_message = "ERROR|".$arr_result[1];
						break;
					}
				}
				
				$qry_items->Next();
			}
			
			/*
				set the bill's status to CANCELLED
			*/
			$qry_bill->Query("UPDATE ".Monthalize('bill')."
				SET bill_status = ".BILL_STATUS_CANCELLED.",
					cancelled_user_id = ".$_SESSION['int_user_id'].",
					cancelled_reason = '".addslashes($str_reason)."'
				WHERE (bill_id = $f_record_id) AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
			");
			if ($qry_bill->b_error == true) {
				$cancel_success = false;
				$str_result_message = "ERROR|Bill status modification";
			}
			
			$qry_bill->Query("
				SELECT *
				FROM ".Monthalize('bill')."
				WHERE (bill_id = $f_record_id) AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
			");
			
			if ($cancel_success) {
				//=====================================================
				// if it is an FS account bill, make a reverse transfer
				// if the bill has been resolved
				//-----------------------------------------------------
				if ($qry_bill->FieldByName('payment_type') == BILL_ACCOUNT) {
					//=====================================================================
					// double check
					// reverse transfer only possible if the initial transfer was completed
					//---------------------------------------------------------------------
					$qry_transfer = new Query("
						SELECT *
						FROM ".monthalize('account_transfers')." 
						WHERE  module_record_id = ".$qry_bill->FieldByName('bill_id')
					);
					
					if ($qry_transfer->RowCount() > 0) {
						if ($qry_transfer->FieldByName('transfer_status') == ACCOUNT_TRANSFER_COMPLETE) {
							//===========================
							// create reverse transaction
							//---------------------------
							$int_result = createTransfer(
								$credit_acount,
								$qry_bill->FieldByName('account_number'),
								"CANCELLATION OF BN: ".$qry_bill->FieldByName('bill_number'),
								$qry_bill->FieldByName('total_amount'),
								$qry_bill->FieldByName('module_id'),
								$qry_bill->FieldByName('module_record_id')
							);
							
							if (($int_result > 0) || ($int_result == -1))
								$str_result_message = "OK|Bill cancelled successfully and reverse transfer completed.";
							else
								$str_result_message = "OK|Bill cancelled successfully BUT ERROR MAKING REVERSE TRANSFER.";
						}
						else {
							$qry_transfer->Query("
								UPDATE ".monthalize('account_transfers')."
								SET transfer_status = ".ACCOUNT_TRANSFER_CANCELLED."
								WHERE module_record_id = ".$qry_bill->FieldByName('bill_id')
							);
							$str_result_message = "ERROR|Bill cancelled successfully and transfer flagged cancelled.";
						}
					}
					else
						$str_result_message = "ERROR|Bill cancelled successfully BUT CORRESPONDING TRANSFER NOT FOUND.";
				}
				$qry_transaction->Query("COMMIT");
			}
			else
				$qry_transaction->Query("ROLLBACK");
		}
	}

	return $str_result_message;
}

?>
