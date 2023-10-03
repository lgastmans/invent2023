<?php
	require_once('../include/const.inc.php');
	require_once('db_params.php');
	require_once('JSON.php');

	
	$str_search = '';
	if (IsSet($_GET['search']))
		$str_search = $_GET['search'];
	
	$str_query = "
		SELECT *
		FROM help
		WHERE text LIKE '%$str_search%'
	";
	$qry =& $conn_help->query($str_query);

	if ($qry->numRows() > 0) {
		$i = 0;
		
		while ($obj = $qry->fetchRow()) {
			$arr_result[$i] = $obj->id;
			
			$i++;
		}
		$arr_retval['Result']['replyStatus'] = 'Ok';
		$arr_retval['Result']['replyText'] = $arr_result;
	}
	else {
		$arr_retval['Result']['replyStatus'] = 'Error';
		$arr_retval['Result']['replyText'] = '';
	}
	$json = new Services_JSON();
	echo ($json->encode($arr_retval));
	
?>