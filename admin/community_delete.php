<?
	require_once('../include/const.inc.php');
	require_once('db_params.php');
	require_once('JSON.php');
	
	if (IsSet($_GET['id']))
		delete_row($_GET['id']);
		
	function delete_row($int_id) {

		global $conn;
		$json = new Services_JSON();
		
		/*
			check whether the account exists in the account_pt table
		*/
		$qry = $conn->query("
			SELECT *
			FROM account_pt
			WHERE (community_id = $int_id)
		");
		if ($qry->num_rows > 0) {

			$arr_retval['replyCode'] = 501;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = "This community is in use in the pt accounts";

			die($json->encode($arr_retval));
		}
	
	
		$qry =& $conn->query("
			DELETE FROM communities
			WHERE (community_id = $int_id)
		");

		$arr_retval['replyCode'] = 200;
		$arr_retval['replyStatus'] = "Ok";
		$arr_retval['replyText'] = "Deleted successfully";

		echo ($json->encode($arr_retval));
	}
