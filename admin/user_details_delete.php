<?
	require_once('../include/const.inc.php');
	require_once('db_params.php');
	require_once('JSON.php');
	
	if (IsSet($_GET['id']))
		delete_row($_GET['id']);

	function delete_row($int_id) {
		global $conn;
		$json = new Services_JSON();
		
		$qry =& $conn->Query("
			SElECT *
			FROM user_permissions
			WHERE permission_id = $int_id
		");
		if ($qry->numRows() == 0) {
			$arr_retval['replyCode'] = 501;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = "Not found";
			die($json->encode($arr_retval));
		}
		
		$qry =& $conn->query("
			DELETE FROM user_permissions
			WHERE permission_id = $int_id
		");
		$arr_retval['replyCode'] = 200;
		$arr_retval['replyStatus'] = "Ok";
		$arr_retval['replyText'] = "Deleted successfully";
		echo ($json->encode($arr_retval));
	}
?>