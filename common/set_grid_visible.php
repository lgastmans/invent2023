<?
	require_once("../include/config.inc.php");
	require_once("../include/db.inc.php");

	$int_grid_id = 0;
	if (IsSet($_GET['grid_id']))
		$int_grid_id = $_GET['grid_id'];
	
	$str_checked = 'Y';
	if (IsSet($_GET['visible']))
		$str_checked = $_GET['visible'];
	
	$str_query = "
		UPDATE grid
		SET visible = '$str_checked'
		WHERE grid_id = $int_grid_id";
		
	$qry = new Query($str_query);
	
	echo "OK";
?>