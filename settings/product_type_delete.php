<?
	function delete_product_type($int_product_type_id) {
		$str_retval = "OK|Successfully deleted product type";
		$bool_success = true;
		
		$str_query = "
			SELECT *
			FROM stock_product_type
			WHERE stock_type_id = $int_product_type_id
		";
		$qry = new Query($str_query);
		if ($qry->RowCount() > 0) {
			$str_retval = "FALSE|Product type in use.";
			return $str_retval;
		}

		$qry->Query("START TRANSACTION");

		$str_query = "
			DELETE FROM stock_type_description
			WHERE stock_type_id = $int_product_type_id
		";
		$qry->Query($str_query);
		if ($qry->b_error == true) {
			$bool_success = false;
			$str_retval = "FALSE|Error deleting corresponding descriptions";
		}

		$str_query = "
			DELETE FROM stock_type
			WHERE stock_type_id = $int_product_type_id
		";
		$qry->Query($str_query);
		if ($qry->b_error == true) {
			$bool_success = false;
			$str_retval = "FALSE|Error deleting product type";
		}
		
		if ($bool_success)
			$qry->Query("COMMIT");
		else
			$qry->Query("ROLLBACK");
		
		return $str_retval;
	}
?>