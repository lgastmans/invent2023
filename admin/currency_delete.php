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
			FROM stock_product
			WHERE (currency_id = $int_id)
		");
		
		$can_delete = true;

		if ($qry_delete->num_rows > 0) {

			$arr_retval['replyCode'] = 501;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = "This currency is in use";

			die($json->encode($arr_retval));
		}
		
		$qry_delete =& $conn->query("
			DELETE FROM stock_currency
			WHERE (currency_id = $int_id)
		");

		$arr_retval['replyCode'] = 200;
		$arr_retval['replyStatus'] = "Ok";
		$arr_retval['replyText'] = "Deleted successfully";

		echo ($json->encode($arr_retval));
	}
