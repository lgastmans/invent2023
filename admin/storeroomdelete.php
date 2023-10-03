<?
	require_once('../include/const.inc.php');
	require_once('db_params.php');
	require_once('JSON.php');
	
	if (IsSet($_GET['id']))
		delete_row($_GET['id']);
  
	function delete_row($int_id) {
		global $conn;
		$json = new Services_JSON();
		
		$qry =& $conn->query("
			SELECT *
			FROM stock_storeroom
			WHERE storeroom_id = $int_id
		");
		if ($qry->numRows() == 0) {
			$arr_retval['replyCode'] = 501;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = "Not found";
			die($json->encode($arr_retval));
		}
			
		/*
			check to see if there is something with a balance
		*/
		$qry =& $conn->query("
			SELECT *
			FROM ".Monthalize('stock_storeroom_product')."
			WHERE storeroom_id = $int_id
		");
		if ($qry->numRows() > 0) {
			$arr_retval['replyCode'] = 501;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = "There are products in this storeroom";
			die($json->encode($arr_retval));
		}
		
		$bool_success=true;
		$qry =& $conn->query("START TRANSACTION");
		
		$qry =& $conn->query("
			DELETE FROM stock_storeroom
			WHERE storeroom_id = $int_id
		");
		if (MDB2::isError($qry)) {
			$bool_success = false;
		}
		
		$qry =& $conn->query("
			DELETE FROM user_settings
			WHERE storeroom_id = $int_id
		");
		if (MDB2::isError($qry)) {
			$bool_success = false;
		}
		
		if ($bool_success) {
			$qry =& $conn->query("COMMIT");
			$arr_retval['replyCode'] = 200;
			$arr_retval['replyStatus'] = "Ok";
			$arr_retval['replyText'] = "Deleted successfully";
			echo ($json->encode($arr_retval));
		}
		else {
			$qry =& $conn->query("ROLLBACK");
			$arr_retval['replyCode'] = 500;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = "An error occurred";
			echo ($json->encode($arr_retval));
		}
		
		
		
		
	} 
?>
