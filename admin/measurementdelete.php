<?
	require_once('../include/const.inc.php');
	require_once('db_params.php');
	require_once('JSON.php');
	
	if (IsSet($_GET['id']))
		delete_row($_GET['id']);
	
	function delete_row($int_id) {

		global $conn;
		global $arr_ini_config;
		
		$path = $arr_ini_config['application']['path'];
		
		$str_query = "
			SELECT * 
			FROM stock_measurement_unit
			WHERE measurement_unit_id = $int_id
		";
		$qry =& $conn->query($str_query);

		if ($qry->num_rows == 0) {
			$arr_retval['replyCode'] = 501;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = "Not found";
		}
		else {
			$str_query = "
				SELECT *
				FROM stock_product
				WHERE measurement_unit_id = $int_id
			";
			$qry =& $conn->query($str_query);

			if ($qry->num_rows > 0) {

				$obj = $qry->fetchRow();

				$arr_retval['replyCode'] = 502;
				$arr_retval['replyStatus'] = "Error";
				$arr_retval['replyText'] = "In use by product ".$obj->product_code;
			}
			else {

				$str_query = "
					DELETE FROM stock_measurement_unit 
					WHERE measurement_unit_id = $int_id
				";
				$qry =& $conn->query($str_query);
				
				$arr_retval['replyCode'] = 200;
				$arr_retval['replyStatus'] = "Ok";
				$arr_retval['replyText'] = "Deleted successfully";
			}
		}
		
		$json = new Services_JSON();
		echo ($json->encode($arr_retval));
	}
?>
