<?
	function deleteOrder($int_id) {
		$str_retval = "OK|Order deleted successfully";

		$qry_delete = new Query("
			SELECT *
			FROM ".Monthalize('orders')."
			WHERE order_id = $int_id
		");
		if ($qry_delete->RowCount() > 0) {
			
			$bool_success = true;
			
			$qry_delete->Query("START TRANSACTION");
			
			//==========
			// delete corresponding items
			//==========
			$qry_delete->Query("
				DELETE FROM ".Monthalize('order_items')."
				WHERE order_id = $int_id
			");
			if ($qry_delete->b_error == true) {
				$str_retval = "ERROR|Could not delete the order items";
				$bool_success = false;
			}
			
			//==========
			// delete order
			//==========
			$qry_delete->Query("
				DELETE FROM ".Monthalize('orders')."
				WHERE order_id = $int_id
				LIMIT 1
			");
			if ($qry_delete->b_error == true) {
				$str_retval = "ERROR|Could not delete the order";
				$bool_success = false;
			}
			
			if ($bool_success == true)
				$qry_delete->Query("COMMIT");
			else
				$qry_delete->Query("ROLLBACK");
			
		}
		else
			$str_retval = "ERROR|Order not found";
		
		return $str_retval;
	}
?>