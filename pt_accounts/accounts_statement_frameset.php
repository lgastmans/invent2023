<?
	/*
	if (isSet($_GET["int_start"]))
		$int_start = $_GET["int_start"];
	else
		$int_start = date('Y');

	if (isSet($_GET["month_start"]))
		$month_start = $_GET["month_start"];
	else
		$month_start = date('m');
	
	if (IsSet($_GET["int_end"]))
		$int_end = $_GET["int_end"];
	else
		$int_end = date('Y');

	if (isSet($_GET["month_end"]))
		$month_end = $_GET["month_end"];
	else
		$month_end = date('m');
	*/
	
	if (isSet($_GET["filter_from"])) {
		$arr_period = explode("_", $_GET["filter_from"]);
		$int_start = intval($arr_period[1]);
		$month_start = intval($arr_period[0]);
	}
	else {
		$int_start = date('Y');
		$month_start = date('n');
	}

	if (isSet($_GET["filter_to"])) {
		$arr_period = explode('_', $_GET['filter_to']);
		$int_end = intval($arr_period[1]);
		$month_end = intval($arr_period[0]);
	}
	else {
		$int_end = date('Y');
		$month_end = date('n');
	}

	$str_filter='';
	if (IsSet($_GET['filter_account']))
		$str_filter = $_GET['filter_account'];
	
	$str_order = '';
	if (IsSet($_GET['order_by']))
		$str_order = $_GET['order_by'];
	
?>
<html><head><TITLE><? require_once('../include/const.inc.php'); echo $str_application_title; ?></TITLE></head>
<frameset id='accounts_statements' rows='65,*' border=2 scrolling=yes>
<frame name='header' src="accounts_statement_header.php?int_start=<?echo $int_start;?>&month_start=<?echo $month_start?>&int_end=<?echo $int_end;?>&month_end=<?echo $month_end?>" scrolling=auto>
<frame name='content' src="accounts_statement_content.php?int_start=<?echo $int_start;?>&month_start=<?echo $month_start?>&int_end=<?echo $int_end;?>&month_end=<?echo $month_end?>&filter_account=<?echo $str_filter?>&order_by=<?echo $str_order?>" scrolling=auto>
</frameset>
</html>