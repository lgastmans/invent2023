<?php
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");
	require_once("../../common/product_bill.php");
	
	function get_day($str) {
		$tmp = substr($str,0,10);
		$arr = explode("-", $tmp);
		return $arr[2];
	}
	
	$bool_success = true;

	$id = 0;
	if (IsSet($_POST['id']))
		$id = $_POST['id'];
	
	/*
		get the DC details
	*/
	$str = "
		SELECT *
		FROM ".Monthalize('dc')."
		WHERE (dc_id = $id)
			AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")";
	$qry = new Query($str);
	
	$str = "SELECT * FROM ".Monthalize('dc_items')." WHERE dc_id = $id";
	$qry_items = new Query($str);
	
	if ($qry->FieldByName('dc_status') > DC_STATUS_UNRESOLVED) {
		$arr['result'] = 'Error';
		$arr['message'] = 'The selected DC has been resolved/cancelled';
		
		die(json_encode($arr));
	}
	
	$trans = new Query("START TRANSACTION");
	
	/*
		2020 update
		Status is changed to "Delivered", but stock not deducted
		In orders, "Delivered" DCs can be imported, and invoiced
			thus setting the status to "Invoiced"
	*/
	/*
	for ($i=0; $i<$qry_items->RowCount(); $i++) {
		
		$str_retval = product_bill(
			$qry_items->FieldByName('batch_id'),
			$qry_items->FieldByName('product_id'),
			$qry_items->FieldByName('quantity'),
			0,
			$qry->FieldByName('dc_number'),
			get_day($qry->FieldByName('date_created')),
			1,
			$qry->FieldByName('dc_id'),
			'Y');
			
		$arr_retval = explode($str_retval, "|");
		
		if ($arr_retval[0] == 'ERROR') {
			$bool_success = false;
			break;
		}
		
		$qry_items->Next();
	}
	*/
	
	if ($bool_success) {
		/*
			update DC status
		*/
		$str = "
			UPDATE ".Monthalize('dc')."
			SET dc_status = 2
			WHERE (dc_id = $id)
				AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")";
		$qry->query($str);
		
		$trans->Query("COMMIT");
		
		$arr['result'] = 'Ok';
		$arr['message'] = '';
	}
	else {
		$trans->Query("ROLLBACK");
		$arr['result'] = 'Error';
		$arr['message'] = $arr_retval[1];
	}
	
	echo json_encode($arr);
?>