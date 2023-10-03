<?
	if (isSet($_GET["filter_from"])) {
		$arr_period = explode("_", $_GET["filter_from"]);
		$int_start = intval($arr_period[1]);
		$month_start = intval($arr_period[0]);
	}
	else {
		$int_start = date('Y');
		$month_start = date('n');
	}
	$str_filter_from = $month_start."_".$int_start;

	if (isSet($_GET["filter_to"])) {
		$arr_period = explode('_', $_GET['filter_to']);
		$int_end = intval($arr_period[1]);
		$month_end = intval($arr_period[0]);
	}
	else {
		$int_end = date('Y');
		$month_end = date('n');
	}
	$str_filter_to = $month_end."_".$int_end;
	
	$str_code='';
	if (IsSet($_GET['product_code']))
		$str_code = $_GET['product_code'];
	
?>
<html><head><TITLE><? require_once('../include/const.inc.php'); echo $str_application_title; ?></TITLE></head>
<frameset id='month_registry_statement' rows='*' border=2 scrolling=yes>
	<frame name='content' src="stock_registry_month_content.php?filter_from=<?echo $str_filter_from;?>&filter_to=<?echo $str_filter_to;?>&product_code=<?echo $str_code?>" scrolling=YES>
</frameset>
</html>