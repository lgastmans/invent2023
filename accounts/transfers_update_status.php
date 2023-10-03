<?php

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");

	$arr = array();

	$sql = "
		SELECT *
		FROM ".Monthalize('account_transfers')."
		WHERE (transfer_status = ".ACCOUNT_TRANSFER_INSUFFICIENT_FUNDS.") OR (transfer_status = ".ACCOUNT_TRANSFER_ERROR.")";
	$qry_update = new Query($sql);
	$int_records = $qry_update->RowCount();
	
	$qry_update = new Query("
		UPDATE ".Monthalize('account_transfers')."
		SET transfer_status = ".ACCOUNT_TRANSFER_PENDING."
		WHERE (transfer_status = ".ACCOUNT_TRANSFER_INSUFFICIENT_FUNDS.") OR (transfer_status = ".ACCOUNT_TRANSFER_ERROR.")"
	);

	
	if ($qry_update->b_error == true) {
		$arr['msg'] = "Error updating the status of ".$int_records." transfers";
	}
	else {
		$arr['msg'] = "Updated the status to 'Pending' of ".$int_records." transfers successfully";
	}


	echo json_encode($arr);











