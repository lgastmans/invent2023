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
  
		$qry = $conn->query("
			SELECT *
			FROM stock_category
			WHERE category_id = $int_id
		");
		if ($qry->num_rows == 0) {
			$arr_retval['replyCode'] = 501;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = "Not found";
		}
		else {
			$qry =& $conn->query("
				SELECT *
				FROM stock_category
				WHERE parent_category_id = $int_id
			");
			if ($qry->num_rows > 0) {
				$arr_retval['replyCode'] = 501;
				$arr_retval['replyStatus'] = "Error";
				$arr_retval['replyText'] = "Category has subcategories, please remove them first";
			}
			else {
				$qry = $conn->query("
					SELECT *
					FROM stock_product
					WHERE category_id = $int_id
				");
				if ($qry->num_rows > 0) {
					$arr_retval['replyCode'] = 501;
					$arr_retval['replyStatus'] = "Error";
					$arr_retval['replyText'] = "Unable to delete records because there are products that use this category";
				}
				else {
					$qry =& $conn->query("
						DELETE FROM stock_category
						WHERE category_id = $int_id
					");
					$arr_retval['replyCode'] = 200;
					$arr_retval['replyStatus'] = "Ok";
					$arr_retval['replyText'] = "Deleted successfully";
				}
			}
		}

		$json = new Services_JSON();
		echo ($json->encode($arr_retval));
		
	}
?>