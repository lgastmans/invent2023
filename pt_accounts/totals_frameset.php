<?
	/*
	if (isSet($_GET["int_start"]))
		$int_start = $_GET["int_start"];
	else
		$int_start = date('Y');

	if (IsSet($_GET["int_end"]))
		$int_end = $_GET["int_end"];
	else
		$int_end = date('Y');
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
?>
<html><head><TITLE><? require_once('../include/const.inc.php'); echo $str_application_title; ?></TITLE></head>
<frameset id='Statements' rows='40,*,80' border=2 scrolling=no>
<frame name='header' src="totals_header.php" scrolling=no noresize>
<frame name='content' src="totals_statement.php?int_start=<?echo $int_start;?>&int_end=<?echo $int_end;?>" scrolling=auto>
<frame name='footer' src="totals_footer.php" scrolling=no noresize>
</frameset>
</html>