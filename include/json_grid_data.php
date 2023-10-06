<?php

if (file_exists('../include/const.inc.php'))
	require_once('../include/const.inc.php');
else if (file_exists('../../include/const.inc.php'))
	require_once('../../include/const.inc.php');
require_once('session.inc.php');
require_once('db_params.php');

/*
	SQL
*/
$sql = '';
if (IsSet($_GET['SQL']))
	$sql = $_GET['SQL'];

/*
	grid name
*/
$grid_name='';
if (IsSet($_GET['gridname']))
	$grid_name = $_GET['gridname'];

/*
	sort dir
*/
$dir = 'ASC';
if(IsSet($_GET['dir'])) {
	if (strtoupper($_GET['dir']) == 'DESC') {
	    $dir = 'DESC';
	} else {
	    $dir = 'ASC';
	}
}

/*
	sort field
*/
$sort = 'id';
if (IsSet($_GET['sort']))
	$sort = $_GET['sort'];
$alias = '';
if (IsSet($_GET['alias']))
	$alias = $_GET['alias'];

/*
	results per page
*/
$results = 20;
if (IsSet($_GET['results']))
	$results = $_GET['results'];

/*
	start index
*/
$start_index = 0;
if (IsSet($_GET['startIndex']))
	$start_index = $_GET['startIndex'];

/*
	filter
*/
$filter = '';
if (IsSet($_GET['filter']))
	$filter = $_GET['filter'];

$field = 'id';
if (IsSet($_GET['field']))
	$field = $_GET['field'];

$mode = 'contains';
if (IsSet($_GET['mode']))
	$mode = $_GET['mode'];

/*
	unique filter
*/
$unique = '';
if (IsSet($_GET['uniqueFilter']))
	$unique = stripslashes($_GET['uniqueFilter']);

/*
	sum
*/
$sum = '';
if (isset($_GET['sum']))
	$sum = $_GET['sum'];

/*
	additional filters
	GET param should be as: field,filter|field,filter
*/
$arr_filter = array();
if (IsSet($_GET['additional_filters'])) {
	$str = $_GET['additional_filters'];
	if (strlen($str) > 0) {
		$arr_tmp = explode('|', $_GET['additional_filters']);
		if (count($arr_tmp)>=0) {
			for ($i=0;$i<count($arr_tmp);$i++) {
				$arr_tmp2 = explode(',', $arr_tmp[$i]);
				
				$arr_filter[$i]['field'] = $arr_tmp2[0];
				$arr_filter[$i]['filter'] = $arr_tmp2[1];
			}
		}
	}
}

/*
	print flag
*/
$str_print = "N";
if (IsSet($_GET['print'])) {
	$str_print = $_GET['print'];
}

function format_date($str_date, $str_separator) {
	$str_new_date = substr($str_date,0,10);
	$str_time = substr($str_date,10,strlen($str_date));
	
	$arr_date = explode($str_separator,$str_new_date);
	return $arr_date[2].$str_separator.$arr_date[1].$str_separator.$arr_date[0].$str_time;
}

returnData($str_print);
	
function returnData($print="N") {
	global $conn;
	global $dir;
	global $sort;
	global $results;
	global $start_index;
	global $filter;
	global $field;
	global $mode;
	global $sql;
	global $grid_name;
	global $alias;
	global $unique;
	global $arr_filter;
	global $sum;

	$arr_retval = array();
	
	$str_where = '';
	if ($unique != '')
		$str_where .= " WHERE ($unique)";
	
	if ($filter != '') {
		$pos = strpos($str_where, "WHERE");
		if ($pos === false)
			$str_conjunction = "WHERE";
		else
			$str_conjunction = "AND";
			
		if ($mode == 'contains')
			$str_where .= " $str_conjunction ($field LIKE '%$filter%')";
		else if ($mode == 'equals')
			$str_where .= " $str_conjunction ($field = '$filter')";
		else if ($mode == 'starts')
			$str_where .= " $str_conjunction ($field LIKE '$filter%')";
		
	}
	
	$count = count($arr_filter);
	if ($count >= 0) {
		$pos = strpos($str_where, "WHERE");
		if ($pos === false)
			$str_conjunction = "WHERE";
		else
			$str_conjunction = "AND";
			
		for ($i=0;$i<$count;$i++) {
			$str_where .= $str_conjunction." (".$arr_filter[$i]['field']." LIKE '%".$arr_filter[$i]['filter']."%') ";
			if ($str_conjunction == 'WHERE')
				$str_conjunction = 'AND';
		}
	}

	/*
		get all records in the dataset
	*/
	$str_query = "
		$sql
		$str_where
	";
	$qry = $conn->query($str_query);
	if(PEAR::isError($qry)) {
		die('Error message (1) : '.$qry->getMessage()."::".$qry->getDebugInfo());
	}
	$int_all_records = mysqli_num_rows($qry);


	/*
		get the sum, if requested
	*/
	if ($sum != '') {
		$sum_sql = substr_replace($sql, "SUM($sum) AS Total, ", 7, 0);

		$sum_qry = $conn->query($sum_sql." ".$str_where);

		$result = mysqli_fetch_object($sum_qry);
		$sum = $result->Total;
	}


	/*
		get the fields
	*/
	if (($print=='Y') || ($print=="CSV"))
		$str_qry = "
			SELECT *
			FROM yui_grid
			WHERE gridname = '$grid_name'
				AND user_id = ".$_SESSION['int_user_id']."
				AND visible = 'Y'
			ORDER BY position
		";
	else
		$str_qry = "
			SELECT *
			FROM yui_grid
			WHERE gridname = '$grid_name'
				AND user_id = ".$_SESSION['int_user_id']."
				AND ((visible = 'Y') OR (is_primary_key = 'Y'))
			ORDER BY position
		";
	$qry_fields = $conn->query($str_qry);
	
	/*
		get the requested subset
	*/
	$str_limit = "LIMIT $start_index, $results";
	if (($print=="Y") || ($print == "CSV"))
		$str_limit = "";
	
	$str_query = "
		$sql
		$str_where
		ORDER BY $alias.$sort $dir
		$str_limit
	";
	$qry = $conn->query($str_query);

	// if(PEAR::isError($qry)) {
	// 	die('Error message (2) : '.$qry->getMessage()."::".$qry->getDebugInfo());
	// }
	$int_returned_records = mysqli_num_rows($qry);
	
	$i = 0;
	while ($obj = mysqli_fetch_array($qry)) {

		//$qry_fields =& $conn->query($str_qry);
		mysqli_data_seek($qry_fields,0);

		while ($field = mysqli_fetch_object($qry_fields)) {

			//$str_field = $field->yui_fieldname;
			/*
				yui grid "key" should not contain a full-stop
			*/
			//$pos = strpos($str_field, '.');
			//if ($pos > 0)
			//	$str_field = substr($str_field, $pos+1);

			if ($field->parser=="datetime")
				$arr_retval[$i][$field->yui_fieldname] = format_date($obj[$field->yui_fieldname], "-");
			else
				$arr_retval[$i][$field->yui_fieldname] = $obj[$field->yui_fieldname];
		}
		
		//$arr_retval[$i]['test'] = $i.'here';

		$i++;
	}
	
	// Create return value
	$returnValue = array(
		'recordsReturned'=>$int_returned_records,
		'totalRecords'=>$int_all_records,
		'startIndex'=>$start_index,
		'sort'=>$sort,
		'dir'=>$str_query, //$dir,
		'sumTotal'=>number_format($sum,2,'.',','),
		'records'=>$arr_retval
	);
	
	/*
		field set
	*/
	if ($print=="N") {
		require_once('JSON.php');
		$json = new Services_JSON();
		$json_arr = $json->encode($returnValue);
		echo $json_arr;
	}
	else if ($print == "Y") {
		require("print_grid.php");
	}
	else if ($print == "CSV") {
		header("Content-Type: application/text; name=invent.csv");
		header("Content-Transfer-Encoding: binary");
		header("Content-Disposition: attachment; filename=invent.csv");
		header("Expires: 0");
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");
		
		$arr_print = $returnValue['records'];
		if (count($arr_print) > 0)
			$arr_header = $arr_print[0];
		$int_num_recs = count($arr_print);
		
		$str_current = '';
		foreach ($arr_header as $key=>$value) {
			$str_current .= "\"".$key."\",";
		}
		$str_current = substr($str_current, 0, strlen($str_current) -1);
		echo $str_current."\n";
		
		foreach ($arr_print as $value) {
			
			$str_current = '';
			
			$arr_row = $value;
			foreach ($arr_row as $row) {
				$element = $row;
				$str_current .= "\"".$element."\",";
			}
			$str_current = substr($str_current, 0, strlen($str_current) -1);
			echo $str_current."\n";
		}
	}
}


?>
