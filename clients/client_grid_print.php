<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/printer.inc.php");
	require_once("../common/print_funcs.inc.php");
	
	/*
		get the columns to print for the given grid_name
	*/
	$str_grid_name = "";
	if (IsSet($_GET['grid_name']))
		$str_grid_name = $_GET['grid_name'];
	
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
	/*
		if there are no columns, die
	*/
	if ($qry_columns->RowCount() == 0)
		die('No columns found to pring');
	
	/*
		get user settings like address, blank lines to add
	*/
	$qry_settings = new Query("
		SELECT *
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION["int_current_storeroom"]."
	");
	if ($qry_settings->RowCount() > 0) {
		$int_eject_lines = $qry_settings->FieldByName('bill_print_lines_to_eject');
		$str_print_address = $qry_settings->FieldByName('bill_print_address');
		$str_print_phone = $qry_settings->FieldByName('bill_print_phone');
	}
	
	/*
		get the data based on filter, if any
	*/
	$str_filter = "";
	if (IsSet($_GET['filter']))
		$str_filter = $_GET['filter'];

	$str_filter_field = "";
	if (IsSet($_GET['field']))
		$str_filter_field = $_GET['field'];
	
	$str_where = '';
	if ($str_filter != '')
		$str_where = " WHERE ($str_filter_field LIKE '%$str_filter%')";
	
	$str_query = "
		SELECT *
		FROM customer
		$str_where
	";
	echo $str_query;
	$qry = new Query($str_query);

?>

<html>
<head><TITLE>Printing Statement</TITLE>
<link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>

<? if (stripos($_SERVER['HTTP_USER_AGENT'], 'win') !== FALSE) { ?>
	<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0" onload="CheckTC()">
<? } else { ?>
	<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0">
<? } ?>

<?
	$str_info = "";
	$str_info .= "%n";

	$print = new print_page;
	$print->query = $qry;
	
	$arr_columns = array();
	for ($i=0;$i<$qry_columns->RowCount();$i++) {
		$arr_columns[] = array(
			$qry_columns->FieldByName('field_name'),
			$qry_columns->FieldByName('column_name'),
			$qry_columns->FieldByName('width'),
			'left',
			'string'
		);
		$qry_columns->Next();
	}
	$print->arr_columns = $arr_columns;
	
	$print->str_print_all = 'Y';
	$print->int_page_from = 0;
	$print->int_page_to = 0;
	$print->int_space_between = 1;
	$print->int_total_lines = 62;
	$print->int_total_columns = 1;
	$print->int_page_width = 120;
	$print->int_linecounter_start = 11;
//	if (($str_print_range == 'RANGE') && ($int_range_from > 1))
//		$print->int_linecounter_start = 1;

	$str_header = $print->get_header();
	$str_data = $print->get_data();

	$str_data = replaceSpecialCharacters($str_data);

	$str_statement = $str_application_title."\n".
		$str_application_title2."\n".
		$str_print_address."\n".
		$str_print_phone."\n".
		$str_header.
		$str_data;
?>

<PRE>
<?
	echo $str_statement;
?>
</PRE>


<? if (stripos($_SERVER['HTTP_USER_AGENT'], 'win') !== FALSE) { ?>
	<form name="printerForm" onsubmit="return false;">
<? } else { ?>
	<form name="printerForm" method="POST" action="http://localhost/html/print.php">
<? } ?>


<table width="100%" bgcolor="#E0E0E0">
	<tr>
		<td height=45 class="headerText" bgcolor="#808080">
		&nbsp;<font class='title'>Printing Statement</font>
		</td>
	</tr>
	<tr>
		<td>
		<br>
		<? if (stripos($_SERVER['HTTP_USER_AGENT'], 'win') !== FALSE) { ?>
			<input type="hidden" name="output" value="<? echo htmlentities($str_statement); ?>">
		<? } else { ?>
			<input type="hidden" name="data" value="<? echo ($str_statement); ?>"><br>
		<? } ?>
		</td>
	</tr>
	<tr>
		<td class='normaltext'>
		<textarea name='printerStatus' height=5 rows=5 cols=40 class='editbox'></textarea>
		</td>
	</tr>
	<tr>
		<td align='center'>
		<br><input type='submit' name='doaction' value="Print">
		<input type='button' onclick="window.close();" name='doaction' value="Close">
		</td>
	</tr>
</table>

</form>

<?if (stripos($_SERVER['HTTP_USER_AGENT'], 'win') !== FALSE) { ?>
	<script language="JavaScript">
		writedata();
	</script>
<? } else { ?>
	<script language="JavaScript">
//		printerForm.submit();
	</script>
<? } ?>

</body>
</html>
