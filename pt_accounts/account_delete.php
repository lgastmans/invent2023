<?
	require_once('../include/const.inc.php');
	require_once('db_params.php');
	require_once('JSON.php');
	
	if (IsSet($_GET['id']))
		delete_row($_GET['id']);
		
	function delete_row($int_id) {
		global $conn;
		$json = new Services_JSON();
		
		$qry_delete =& $conn->query("
			SELECT *
			FROM ".Monthalize('bill')."
			WHERE CC_id = $int_id
		");
		if ($qry_delete->numRows() > 0) {
			$arr_retval['replyCode'] = 501;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = "This account has transfers that cannot be deleted";
			die($json->encode($arr_retval));
		}
		
		$bool_success = true;
		$qry_delete =& $conn->query("START TRANSACTION");
		
		/*
			remove corresponding balances
		*/
		$qry_delete =& $conn->query("
			DELETE FROM ".Monthalize('account_pt_balances')."
			WHERE (account_id = $int_id)
		");
		if (MDB2::isError($qry_delete)) {
			$arr_retval['replyCode'] = 501;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = "This account has balances that cannot be deleted";
			$bool_success = false;
		}
		
		/*
			remove corresponding transfers
		*/
		$qry_delete =& $conn->query("
			DELETE FROM ".Monthalize('account_pt_transfers')."
			WHERE (id_from = $int_id)
		");
		if (MDB2::isError($qry_delete)) {
			$arr_retval['replyCode'] = 501;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = "This account has transfers that cannot be deleted";
			$bool_success = false;
		}
		
		/*
			delete the account
		*/
		$qry_delete =& $conn->query("
			DELETE FROM account_pt
			WHERE (account_id = $int_id)
		");
		if (MDB2::isError($qry_delete)) {
			$arr_retval['replyCode'] = 501;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = "Error deleting account";
			$bool_success = false;
		}
		
		if ($bool_success) {
			$qry_delete =& $conn->query("COMMIT");
			
			$arr_retval['replyCode'] = 200;
			$arr_retval['replyStatus'] = "Ok";
			$arr_retval['replyText'] = "Deleted successfully";
			echo ($json->encode($arr_retval));
		}
		else {
			$qry_delete =& $conn->query("ROLLBACK");
			echo ($json->encode($arr_retval));
		}
	}
