<?
	require_once('../include/const.inc.php');
	require_once('db_params.php');
	
	$int_id = 0;
	if (IsSet($_GET['id']))
		$int_id = $_GET['id'];
	
	$str_visible = 'N';
	if (IsSet($_GET['visible']))
		$str_visible = $_GET['visible'];
	
	$str_qry = "
		UPDATE yui_grid
		SET visible = '$str_visible'
		WHERE id = $int_id
	";
	
	$qry =& $conn->query($str_qry);
	
	$returnValue['replyStatus'] = 'Ok';
	
	require_once('JSON.php');
	$json = new Services_JSON();
	echo ($json->encode($returnValue));
	
?>