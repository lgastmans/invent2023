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
			FROM ".Monthalize('stock_tax')."
			WHERE tax_id = $int_id
		");
		
		if ($qry->num_rows == 0) {

			$arr_retval['replyCode'] = 501;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = "Not found";
		}
		else {

			$qry =& $conn->query("
				SELECT *
				FROM stock_product 
				WHERE tax_id = $int_id
			");

			if ($qry->num_rows > 0) {

				$arr_retval['replyCode'] = 501;
				$arr_retval['replyStatus'] = "Error";
				$arr_retval['replyText'] = "There are products that use this tax.  Please disassociate them before deleting the tax.";

			}
			else {
				
				$qry = $conn->query("
					SELECT *
					FROM ".Yearalize('stock_batch')."
					WHERE is_active = 'Y'
						AND tax_id = $int_id
				");

				if ($qry->num_rows > 0) {

					$arr_retval['replyCode'] = 501;
					$arr_retval['replyStatus'] = "Error";
					$arr_retval['replyText'] = "There are active batches that use this tax.  Please deactivate batches first";

				}
				else {

					$qry =& $conn->query("
						DELETE FROM ".Monthalize('stock_tax')."
						WHERE tax_id = $int_id
					");
					$qry =& $conn->query("
						DELETE FROM ".Monthalize('stock_tax_links')."
						WHERE tax_id = $int_id
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