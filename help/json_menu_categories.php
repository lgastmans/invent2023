<?php
	require_once('../include/const.inc.php');
	require_once('db_params.php');
	require_once('JSON.php');
	
	$str_query = "
		SELECT *
		FROM categories
		ORDER BY order_index
	";
	$qry =& $conn_help->query($str_query);
	if (MDB2::isError($qry))
		die($qry->getDebugInfo());
	$arr_retval = array();
	
	$i=0;
	while ($obj = $qry->fetchRow()) {
		$arr_retval['Categories'][$i]['category_id'] = $obj->category_id;
		$arr_retval['Categories'][$i]['label'] = $obj->title;
		$arr_retval['Categories'][$i]['module_id'] = 0;
		$arr_retval['Categories'][$i]['section_id'] = 0;
		$arr_retval['Categories'][$i]['level'] = 1;

		$i++;
	}
	
	$json = new Services_JSON();
	echo ($json->encode($arr_retval));
?>
