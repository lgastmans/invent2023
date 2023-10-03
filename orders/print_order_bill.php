<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
	$int_id = 0;
	if (IsSet($_GET['id']))
		$int_id = $_GET['id'];

	$qry = new Query("SELECT * FROM templates WHERE is_default = 'Y'");
	
	if ($qry->FieldByName('is_main') == 'Y')
		require_once("print_order_bill_textmode.php");
	else
		header("Location:print_order_bill_template.php?id=".$int_id);
?>
	
