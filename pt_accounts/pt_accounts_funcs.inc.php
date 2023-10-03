<?
	require_once("../include/db.inc.php");
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");

	function getClosingBalance($int_account_id, $flt_opening_balance) {
	
		$flt_retval = $flt_opening_balance;
		
		$str_qry = "
			SELECT SUM(amount) AS total 
			FROM ".Monthalize('account_pt_transfers')."
			WHERE (transfer_status = ".ACCOUNT_TRANSFER_COMPLETE.")
				AND (id_from = $int_account_id)
		";
		
		$qry = new Query($str_qry);
		
		if ($qry->RowCount() > 0)
			$flt_retval = $flt_opening_balance - $qry->FieldByName('total');
		
		return $flt_retval;
	}
?>