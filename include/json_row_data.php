<?php
if (file_exists('../include/const.inc.php'))
	require_once('../include/const.inc.php');
else if (file_exists('../../include/const.inc.php'))
	require_once('../../include/const.inc.php');
require_once('session.inc.php');
require_once('db_params.php');

function format_date($str_date, $str_separator) {
	$str_new_date = substr($str_date,0,10);
	$str_time = substr($str_date,10,strlen($str_date));
	
	$arr_date = split($str_separator,$str_new_date);
	return $arr_date[2].$str_separator.$arr_date[1].$str_separator.$arr_date[0].$str_time;
}

$filter = array();
if (IsSet($_POST['filter']))
	$filter = explode('|', $_POST['filter']);

$sql = '';
if (IsSet($_POST['sql']))
	$sql = $_POST['sql'];

$grid_name = 'default';
if (IsSet($_POST['gridname']))
	$grid_name = $_POST['gridname'];

/*
	get the fields
*/
$str_qry = "
	SELECT *
	FROM yui_grid
	WHERE gridname = '$grid_name'
		AND user_id = ".$_SESSION['int_user_id']."
		AND ((visible = 'Y') OR (is_primary_key = 'Y'))
	ORDER BY position
";
$qry_fields =& $conn->query($str_qry);

$str_query = $sql." WHERE (".$filter[0]."=".$filter[1].") ";

$qry = $conn->query($str_query);

while ($obj = $qry->fetchRow(MDB2_FETCHMODE_ASSOC)) {
	$qry_fields->seek();
	while ($field = $qry_fields->fetchRow()) {
		if ($field->parser=="datetime")
			$arr_retval[$field->yui_fieldname] = format_date($obj[$field->yui_fieldname], "-");
		else
			$arr_retval[$field->yui_fieldname] = $obj[$field->yui_fieldname];
	}
}

echo json_encode($arr_retval);

?>