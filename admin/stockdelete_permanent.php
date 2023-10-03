<?
	require_once('../include/const.inc.php');
	require_once("db_mysqli.php");
	

	if (IsSet($_GET['id']))
		delete_row($_GET['id']);


		
	function delete_row($int_id) {
	
		global $conn;
		
		$qry = $conn->Query("
			SELECT *
			FROM stock_product
			WHERE product_id = $int_id
		");
		
		if ($qry->num_rows == 0) {

			$arr_retval['replyCode'] = 501;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = "Not found";

			die(json_encode($arr_retval));
		}

		$obj = $qry->fetch_object();
		$str_code = $obj->product_code;
		

		/*
			check to see if there is a balance of stock
		*/
		$qry =& $conn->query("
			SELECT *
			FROM ".Yearalize("stock_balance")."
			WHERE balance_month=".$_SESSION['int_month_loaded']."
			AND balance_year=".$_SESSION['int_year_loaded']."
			AND stock_closing_balance<>0
			AND product_id=$int_id");

		if ($qry->num_rows > 0) {

			$arr_retval['replyCode'] = 501;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = "This product has stock";

			die(json_encode($arr_retval));
		}

		
		$bool_success = true;
		$str_message = "Permanently deleted product $str_code";
		/*
			start a transaction for the deletion process
		*/
		$qry =& $conn->query("START TRANSACTION");
		
		/*
			STOCK_PRODUCT
		*/
		$qry =& $conn->query("
			DELETE FROM stock_product
			WHERE product_id = $int_id
		");
		if (!$qry) {
			$bool_success = false;
			$str_message = "Error deleting from table stock_product ".mysqli_error($conn);
		}
		
		$cur_year = $_SESSION['int_year_loaded'];
		$bool_continue = true;
		while ($bool_continue) {
			$qry =& $conn->query("SELECT * FROM stock_batch_".$cur_year);
			if (!$qry)
				break;
			
			/*
				STOCK_BATCH
			*/
			$qry =& $conn->query("
				DELETE FROM stock_batch_".$cur_year."
				WHERE product_id = ".$int_id
			);
			if (!$qry) {
				$bool_success = false;
				$str_message = "Error deleting from table stock_batch_".$cur_year;
				break;
			}
			
			/*
				STOCK_BALANCE
			*/
			$qry =& $conn->query("
				DELETE FROM stock_balance_".$cur_year."
				WHERE product_id = ".$int_id
			);
			if (!$qry) {
				$bool_success = false;
				$str_message = "Error deleting from table stock_balance_".$cur_year;
				break;
			}
			
			$cur_year--;
		}
		
		$cur_year = $_SESSION['int_year_loaded'];
		$cur_month = $_SESSION['int_month_loaded'];
		$bool_continue = true;
		while ($bool_continue) {
			$qry =& $conn->Query("SELECT * FROM stock_storeroom_product_".$cur_year."_".$cur_month);
			if (!$qry)
				break;
			
			/*
				STOCK_STOREROOM_PRODUCT
			*/
			$qry =& $conn->query("
				DELETE FROM stock_storeroom_product_".$cur_year."_".$cur_month."
				WHERE product_id = ".$int_id
			);
			if (!$qry) {
				$bool_success = false;
				$str_message = "Error deleting from table stock_storeroom_product_".$cur_year."_".$cur_month;
				break;
			}
			
			/*
				STOCK_STOREROOM_BATCH
			*/
			$qry =& $conn->query("
				DELETE FROM stock_storeroom_batch_".$cur_year."_".$cur_month."
				WHERE product_id = ".$int_id
			);
			if (!$qry) {
				$bool_success = false;
				$str_message = "Error deleting from table stock_storeroom_batch_".$cur_year."_".$cur_month;
				break;
			}
			
			/*
				STOCK_TRANSFER
			*/
			$qry =& $conn->query("
				DELETE FROM stock_transfer_".$cur_year."_".$cur_month."
				WHERE product_id = ".$int_id
			);
			if (!$qry) {
				$bool_success = false;
				$str_message = "Error deleting from table stock_transfer_".$cur_year."_".$cur_month;
				break;
			}
			
			if ($cur_month == 1) {
				$cur_month = 12;
				$cur_year--; 
			}
			else
				$cur_month--;
		}
		
		if ($bool_success == true) {
			$qry =& $conn->query("COMMIT");
			$arr_retval['replyCode'] = 200;
			$arr_retval['replyStatus'] = "Ok";
			$arr_retval['replyText'] = "Deleted successfully";
			echo (json_encode($arr_retval));
		}
		else {
			$qry =& $conn->query("ROLLBACK");
			$arr_retval['replyCode'] = 501;
			$arr_retval['replyStatus'] = "Error";
			$arr_retval['replyText'] = $str_message;
			echo (json_encode($arr_retval));
		}
		
	}
?>
