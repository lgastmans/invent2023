<?
	function delete_product_type_description($int_description_id) {
		$str_retval = "OK|Successfully deleted product type";
		$bool_success = true;
		
		$str_query = "
			SELECT *
			FROM stock_product_type
			WHERE stock_type_description_id = $int_description_id
		";
		$qry = new Query($str_query);
		if ($qry->RowCount() > 0) {
			$str_retval = "FALSE|Product type description in use.";
			return $str_retval;
		}

		$str_query = "
			DELETE FROM stock_type_description
			WHERE stock_type_description_id = $int_description_id
			LIMIT 1
		";
		$qry->Query($str_query);
		if ($qry->b_error == true) {
			$bool_success = false;
			$str_retval = "FALSE|Error deleting description";
		}

		return $str_retval;
	}
?>