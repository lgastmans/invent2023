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
			FROM stock_supplier
			WHERE supplier_id = $int_id");

		if ($qry->num_rows == 0) {
			$arr_retval['replyCode'] = 501;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = "Not found";
			die($json->encode($arr_retval));
		}
		
		$qry =& $conn->query("
			SELECT * 
			FROM stock_product
			WHERE supplier_id = $int_id
		");
		if ($qry->num_rows > 0) {
			$arr_retval['replyCode'] = 501;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = "There are products using this supplier";
			die($json->encode($arr_retval));
		}
		
		$qry =& $conn->query("
			SELECT * 
			FROM ".Yearalize('stock_batch')."
			WHERE supplier_id = $int_id
		");
		if ($qry->num_rows > 0) {
			$arr_retval['replyCode'] = 501;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = "There is stock using this supplier";
			die($json->encode($arr_retval));
		}
		
		$qry =& $conn->query("
			DELETE FROM stock_supplier
			WHERE supplier_id = $int_id
		");
		$arr_retval['replyCode'] = 200;
		$arr_retval['replyStatus'] = "Ok";
		$arr_retval['replyText'] = "Deleted successfully";
		echo ($json->encode($arr_retval));
	}
?>
