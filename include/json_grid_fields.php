<?
	if (file_exists('../include/const.inc.php'))
		require_once('../include/const.inc.php');
	else if (file_exists('../../include/const.inc.php'))
		require_once('../../include/const.inc.php');
	require_once('session.inc.php');
	require_once('db_params.php');
	
	function init_grid_fields($arr_fields, $grid_name, $view_name='default', $user_id) {
		global $conn;
		
		$str_query = "
			SELECT *
			FROM yui_grid
			WHERE gridname = '$grid_name'
				AND view_name = '$view_name'
				AND user_id = $user_id
		";
		$qry = $conn->query($str_query);

		if (mysqli_num_rows($qry)==0) {
		
			/*
			$str_query = "DESCRIBE $table_name";
			$qry =& $conn->query($str_query);
			
			while ($obj =& $qry->fetchRow(MDB2_FETCHMODE_ASSOC)) {
				echo "field:".$obj['field']."<br>";
				echo "type:".$obj['type']."<br>";
				echo "key:".$obj['key']."<br>";
				echo "<br><br>";
			}
			*/
			
			for ($i=0;$i<count($arr_fields);$i++) {
				$arr_defs = $arr_fields[$i];
				
				if ($arr_defs['is_primary_key'] == 'Y') {
					$visible = 'N';
					$default_sort = 'N';
				}
				else {
					if ($arr_defs['hidden'])
						$visible = ($arr_defs['hidden'] == 'Y') ? "N" : "Y";
					else
						$visible = ($arr_defs['is_primary_key'] =='Y') ? "N" : "Y";
						
					if (($default_sort == 'N') && (!$default_sort_set)) {
						$default_sort = 'Y';
						$default_sort_set = true;
					}
					elseif ($default_sort_set)
						$default_sort = 'N';
				}
				$str_query = "
					INSERT INTO yui_grid
					(
						fieldname,
						yui_fieldname,
						columnname,
						formatter,
						is_custom_formatter,
						parser,
						visible,
						filter,
						gridname,
						view_name,
						position,
						is_primary_key,
						user_id,
						alias,
						defaultsort
					)
					VALUES (
						'".$arr_defs['field']."',
						'".$arr_defs['yui_field']."',
						'".$arr_defs['field']."',
						'".$arr_defs['formatter']."',
						'".$arr_defs['is_custom_formatter']."',
						'".$arr_defs['parser']."',
						'$visible',
						'".$arr_defs['filter']."',
						'$grid_name',
						'$view_name',
						$i,
						'".$arr_defs['is_primary_key']."',
						$user_id,
						'".$arr_defs['alias']."',
						'".$default_sort."'
					)";
				$qry =& $conn->query($str_query);
			}
		}
		
	}
	
	/*
		this function is for the YUI DataTable's myColumnDefs array
		and should not include the columns that are set "not visible"
	*/
	function get_grid_fields($grid_name, $user_id=1) {
		global $conn;
		
		$qry_fields = $conn->query("
			SELECT *
			FROM yui_grid
			WHERE gridname = '$grid_name'
				AND user_id = $user_id
				AND (visible='Y')
			ORDER BY position
		");
		
		if (mysqli_num_rows($qry_fields) > 0) {
			$i=0;
			while ($obj = mysqli_fetch_object($qry_fields)) {
				$fieldname = $obj->yui_fieldname;
				/*
					yui grid "key" should not contain a full-stop
				*/
				$pos = strpos($fieldname, '.');
				if ($pos > 0)
					$fieldname = substr($fieldname, $pos+1);
				$returnValue[$i]['key'] = $fieldname;
				$returnValue[$i]['formatter'] = $obj->formatter;
				$returnValue[$i]['parser'] = $obj->parser;
				$returnValue[$i]['label'] = $obj->columnname;
				$returnValue[$i]['width'] = intval($obj->columnwidth);
				$returnValue[$i]['hidden'] = ($obj->visible=='N');
				$returnValue[$i]['sortable'] = ($obj->sortable=='Y');
				$returnValue[$i]['isPrimaryKey'] = ($obj->is_primary_key=='Y');
				$returnValue[$i]['alias'] = $obj->alias;
				$returnValue[$i]['fieldname'] = $obj->fieldname;
				
				$i++;
			}
		}
		
		require_once('JSON.php');
		$json = new Services_JSON();
		echo ($json->encode($returnValue));
	}
	
	/*
		this function is for the YUI DataSource and should include all columns
	*/
	function get_grid_schema($grid_name, $user_id=1) {
		global $conn;
		
		$qry_fields = $conn->query("
			SELECT *
			FROM yui_grid
			WHERE gridname = '$grid_name'
				AND user_id = $user_id
			ORDER BY position
		");
		
		$i=0;
		while ($obj = mysqli_fetch_object($qry_fields)) {
			$fieldname = $obj->yui_fieldname;
			/*
				yui grid "key" should not contain a full-stop
			*/
			$pos = strpos($fieldname, '.');
			if ($pos > 0)
				$fieldname = substr($fieldname, $pos+1);
			$returnValue[$i]['key'] = $fieldname;
			$returnValue[$i]['parser'] = $obj->parser;
			$returnValue[$i]['alias'] = $obj->alias;
			$returnValue[$i]['fieldname'] = $obj->fieldname;
			
			$i++;
		}
		
		//require_once('JSON.php');
		//$json = new Services_JSON();
		//$str_retval = $json->encode($returnValue);
		
		$str_retval = json_encode($returnValue);

		/*
			remove the double quotes from the parser function
		*/
		mysqli_data_seek($qry_fields,0);
		while ($obj = mysqli_fetch_object($qry_fields)) {
			if ($obj->is_custom_formatter == 'Y') {
				$str_retval = str_replace('"'.$obj->parser.'"', $obj->parser, $str_retval);
			}
		}
		
		echo $str_retval;
	}
	
	function get_filter_fields($grid_name, $user_id=1) {
		global $conn;
		
		$sql = "
			SELECT *
			FROM yui_grid
			WHERE gridname = '$grid_name'
				AND user_id = $user_id
				AND filter = 'Y'
				AND (visible = 'Y')
			ORDER BY position
		";

		$qry_fields = $conn->query($sql);
		
		$i=0;
		while ($obj = mysqli_fetch_object($qry_fields)) {
			$returnValue[$i]['fieldname'] = $obj->fieldname;
			$returnValue[$i]['yui_fieldname'] = $obj->yui_fieldname;
			$returnValue[$i]['columnname'] = $obj->columnname;
			$returnValue[$i]['alias'] = $obj->alias;
			
			$i++;
		}
		
		return $returnValue;
	}

	function get_default_sort($grid_name, $view_name='default', $user_id=1) {
		global $conn;
		
		$qry_fields = $conn->query("
			SELECT yui_fieldname
			FROM yui_grid
			WHERE gridname = '$grid_name'
				AND view_name = '$view_name'
				AND user_id = $user_id
				AND defaultsort = 'Y'
		");
		$obj = mysqli_fetch_object($qry_fields);
		
		return $obj->yui_fieldname;
	}

	function get_default_dir($grid_name, $view_name='default', $user_id=1) {
		global $conn;
		
		$qry_fields = $conn->query("
			SELECT IF(defaultdir='A','ASC','DESC') AS defaultdir
			FROM yui_grid
			WHERE gridname = '$grid_name'
				AND view_name = '$view_name'
				AND user_id = $user_id
				AND defaultsort = 'Y'
		");
		$obj = mysqli_fetch_object($qry_fields);
		
		return $obj->defaultdir;
	}
	
	function get_default_alias($grid_name, $view_name='default', $user_id=1) {
		global $conn;
		
		$qry_fields = $conn->query("
			SELECT alias
			FROM yui_grid
			WHERE gridname = '$grid_name'
				AND view_name = '$view_name'
				AND user_id = $user_id
				AND defaultsort = 'Y'
		");
		$obj = mysqli_fetch_object($qry_fields);
		
		return $obj->alias;
	}
?>