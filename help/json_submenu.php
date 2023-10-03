<?php
	require_once('../include/const.inc.php');
	require_once('db_params.php');
	require_once('JSON.php');
	
	$int_category_id = 0;
	if (IsSet($_GET['category_id']))
		$int_category_id = $_GET['category_id'];
		
	$int_section_id = 0;
	if (IsSet($_GET['section_id']))
		$int_section_id = $_GET['section_id'];
		
	$int_module_id = 0;
	if (IsSet($_GET['module_id'])) 
		$int_module_id = $_GET['module_id'];

	if ($int_category_id == 2) {
		if (($int_module_id > 0) && ($int_section_id == 0)) {
			$str_query = "
				SELECT *
				FROM help
				WHERE module_id = $int_module_id
					AND section_id > 0
				ORDER BY section_id
			";
		}
		else {
			$str_query = "
				SELECT *
				FROM help
				WHERE category_id = $int_category_id
					AND section_id = 0
				ORDER BY section_id
			";
		}
	}
	else {
		$str_query = "
			SELECT *
			FROM help
			WHERE category_id = $int_category_id
			ORDER BY section_id
		";
	}
	$qry =& $conn_help->query($str_query);
	
	$arr_retval = array();
	
	$i=0;
	while ($obj = $qry->fetchRow()) {
		$arr_retval['Result'][$i]['id'] = $obj->id;
		$arr_retval['Result'][$i]['label'] = $obj->section_name;
		$arr_retval['Result'][$i]['category_id'] = $obj->category_id;
		$arr_retval['Result'][$i]['module_id'] = $obj->module_id;
		$arr_retval['Result'][$i]['section_id'] = $obj->section_id;
		$arr_retval['Result'][$i]['level'] = $obj->level;
		
		$i++;
	}
	
	$json = new Services_JSON();
	echo ($json->encode($arr_retval));
?>
