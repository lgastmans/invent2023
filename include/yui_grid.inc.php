<?
	require_once("db.inc.php");
	
	function insert_grid_fields($str_table_name, $str_grid_name, $int_user_id) {
		/*
			check whether there is an entry for the given grid name and user
		*/
		$qry_grid = new Query("
			SELECT *
			FROM grid
			WHERE grid_name = '$str_grid_name'
				AND view_name = 'default'
				AND user_id = $int_user_id
		");
		
		/*
			insert the fields in the grid table if not found
		*/
		if ($qry_grid->RowCount() == 0) {
			$qry_grid->Query("
				SELECT *
				FROM $str_table_name
				LIMIT 1
			");
			$arr_fields = array();
			$arr_fields = $qry_grid->getFieldNames();
			
			for ($i=0;$i<count($arr_fields);$i++) {
				
				$str_query = "
					INSERT INTO grid
					(
						user_id,
						grid_name,
						column_name,
						field_name,
						field_type,
						width,
						view_name,
						column_order
					)
					VALUES(
						$int_user_id,
						'$str_grid_name',
						'".$arr_fields[$i]['fieldname']."',
						'".$arr_fields[$i]['fieldname']."',
						'".$arr_fields[$i]['fieldtype']."',
						".$arr_fields[$i]['fieldlen'].",
						'default',
						$i
					)
				";
				$qry_grid->Query($str_query);
//				echo $str_query;
			}
		}
	}
?>