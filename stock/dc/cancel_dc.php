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

if (IsSet($_GET['id'])) {
	$res = cancelDC($_GET['id']);
	$arr = explode("|", $res);
	
	$arr_retval['replyCode'] = 201;
	$arr_retval['replyStatus'] = $arr[0];
	$arr_retval['replyText'] = $arr[1];
	
	echo json_encode($arr_retval);
}
else {
	$arr_retval['replyCode'] = 501;
	$arr_retval['replyStatus'] = "Error";
	$arr_retval['replyText'] = "Could not cancel DC";
	echo json_encode($arr_retval);
}

function cancelDC($f_record_id, $str_reason='') {

	$str_result_message = "OK|DC cancelled successfully.";

	$qry_bill = new Query("
		SELECT *
		FROM ".Monthalize('dc')."
		WHERE (dc_id = $f_record_id)
			AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
	");

	if ($qry_bill->RowCount() > 0) {
		/*
			check the status of the DC
		*/
		if ($qry_bill->FieldByName('dc_status') == DC_STATUS_CANCELLED) {
			$str_result_message = "ERROR|DC already cancelled";
		}
		else {
			
			$qry_transaction = new Query("START TRANSACTION");
			
			/*
				get the corresponding items for the given bill
			*/
			$qry_items = new Query("
				SELECT *
				FROM ".Monthalize('dc_items')."
				WHERE dc_id = ".$qry_bill->FieldByName('dc_id')."
			");
			
			$cancel_success = true;
			
			if ($qry_bill->FieldByName('dc_status') == DC_STATUS_RESOLVED) {
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
								$qry_bill->FieldByName('dc_number'),
								$qry_bill->FieldByName('dc_id'),
								0
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
			}
			
			/*
				set the bill's status to CANCELLED
			*/
			$qry_bill->Query("
				UPDATE ".Monthalize('dc')."
				SET dc_status = ".DC_STATUS_CANCELLED."
				WHERE (dc_id = $f_record_id)
					AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
			");
			if ($qry_bill->b_error == true) {
				$cancel_success = false;
				$str_result_message = "ERROR|DC status modification";
			}
			
			if ($cancel_success) {
				$qry_transaction->Query("COMMIT");
			}
			else
				$qry_transaction->Query("ROLLBACK");
		}
	}
	
	return $str_result_message;
}

?>
