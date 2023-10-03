<?
	function deleteTemplate($int_id) {
		$str_retval = "OK|Template deleted successfully";
		
		$qry_delete = new Query("
			SELECT *
			FROM templates
			WHERE id = $int_id
		");
		if ($qry_delete->RowCount() > 0) {
			
			$bool_success = true;
			
			if ($qry_delete->FieldByName('is_main') == 'Y') {
				$str_retval = "ERROR|This template cannot be deleted";
				$bool_success = false;
			}
			else {
				if ($qry_delete->FieldByName('is_default') == 'Y') {
					$qry_delete->Query("UPDATE templates SET is_default='Y' WHERE is_main='Y'");
				}
				
				//==========
				// delete template
				//==========
				$qry_delete->Query("
					DELETE FROM templates
					WHERE id = $int_id
					LIMIT 1
				");
				if ($qry_delete->b_error == true) {
					$str_retval = "ERROR|Could not delete the template";
					$bool_success = false;
				}
			}
		}
		else
			$str_retval = "ERROR|Order not found";
		
		return $str_retval;
	}
?>