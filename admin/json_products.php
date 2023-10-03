<?php
require_once("../include/const.inc.php");
require_once("../include/session.inc.php");
require_once("../include/db.inc.php");

header('Content-type: application/json');

$str_grid_name = "admin_stock";

$str_view = 'default';
if (IsSet($_GET['view']))
	$str_view = $_GET['view'];

$qry_columns = new Query("
	SELECT *
	FROM grid
	WHERE grid_name = '$str_grid_name'
		AND visible = 'Y'
		AND view_name = '$str_view'
		AND user_id = ".$_SESSION['int_user_id']."
	ORDER BY column_order ASC
");
$int_columns = $qry_columns->RowCount();

// Define defaults
$results = -1; // default get all
$startIndex = 0; // default start at 0
$sort = 'product_code'; // the data is already sorted
$dir = 'asc'; // default sort dir is asc
$sort_dir = SORT_ASC;

/*
	filter
*/
$str_filter = '';
if (IsSet($_GET['filter']))
	$str_filter = $_GET['filter'];
	
$str_field = '';
if (IsSet($_GET['field']))
	$str_field = $_GET['field'];

// How many records to get?
if(IsSet($_GET['results']) > 0) {
    $results = $_GET['results'];
}

// Start at which record?
if(IsSet($_GET['startIndex']) > 0) {
    $startIndex = $_GET['startIndex'];
}

// Sorted?
if(IsSet($_GET['sort']) > 0) {
    $sort = $_GET['sort'];
}

// Sort dir?
if((IsSet($_GET['dir']) > 0)) {
	if (($_GET['dir'] == 'desc')) {
	    $dir = 'desc';
	    $sort_dir = SORT_DESC;
	} else {
	    $dir = 'asc';
	    $sort_dir = SORT_ASC;
	}
}



// Return the data
returnData($results, $startIndex, $sort, $dir, $sort_dir, $str_filter, $str_field);



function returnData($results, $startIndex, $sort, $dir, $sort_dir, $str_filter, $str_field) {
	// All records
	$allRecords = initArray($str_filter, $str_field);

	// Need to sort records
	if(!is_null($sort)) {
	
		// Obtain a list of columns
		foreach ($allRecords as $key => $row) {
			$sortByCol[$key] = $row[$sort];
		}
	
		// Valid sort value
		if(count($sortByCol) > 0) {
			// Sort the original data
			// Add $allRecords as the last parameter, to sort by the common key
			array_multisort($sortByCol, $sort_dir, $allRecords);
		}
	}

	// Invalid start value
	if(is_null($startIndex) || !is_numeric($startIndex) || ($startIndex < 0)) {
		// Default is zero
		$startIndex = 0;
	}
	// Valid start value
	else {
		// Convert to number
		$startIndex += 0;
	}

	// Invalid results value
	if(is_null($results) || !is_numeric($results) || ($results < 1) || ($results >= count($allRecords))) {
		// Default is all
		$results = count($allRecords);
	}
	// Valid results value
	else {
		// Convert to number
		$results += 0;
	}

	// Iterate through records and return from start index
	$lastIndex = $startIndex+$results;
	if($lastIndex > count($allRecords)) {
		$lastIndex = count($allRecords);
	}
	$data = array();
	for($i=$startIndex; $i<($lastIndex); $i++) {
		$data[] = $allRecords[$i];
	}
	
	// Create return value
	$returnValue = array(
		'recordsReturned'=>count($data),
		'totalRecords'=>count($allRecords),
		'startIndex'=>$startIndex,
		'sort'=>$sort,
		'dir'=>$dir,
		'records'=>$data
	);
	
	global $qry_columns;
	$qry_columns->First();
	
	if (IsSet($_GET['meta'])) {
	    for ($i=0;$i<$qry_columns->RowCount();$i++) {
			$returnValue['meta'][$i]['key'] = $qry_columns->FieldByName('field_name');
			$returnValue['meta'][$i]['label'] = $qry_columns->FieldByName('column_name');
			$returnValue['meta'][$i]['width'] = intval($qry_columns->FieldByName('width'));
			$returnValue['meta'][$i]['sortable'] = true;
//			$returnValue['meta'][$i]['editor'] = "new YAHOO.widget.TextboxCellEditor({disableBtns:true})";
			$qry_columns->Next();
	    }
	}
	
	require_once('../include/JSON.php');
	$json = new Services_JSON();
	echo ($json->encode($returnValue));
}

function initArray($str_filter, $str_field) {

	$arr_retval = array();
	
	global $qry_columns;
	global $int_columns;
	
	$str_where = '';
	if ($str_filter != '')
		$str_where = " WHERE ($str_field LIKE '%$str_filter%')";
	
	$str_query = "
		SELECT *
		FROM stock_product sp
		$str_where
	";
	$qry = new Query($str_query);
	
	$int_rows = $qry->RowCount();
	for ($i=0;$i<$int_rows;$i++) {
		$qry_columns->First();
		for ($j=0;$j<$int_columns;$j++) {
			$str_field_name = $qry_columns->FieldByName('field_name');
			$arr_retval[$i][$str_field_name] = $qry->FieldByName($str_field_name);
			
			$qry_columns->Next();
		}
		$qry->Next();
	}

	return $arr_retval;
}

?>
