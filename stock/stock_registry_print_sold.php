<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
	$str_code = "";
	if (IsSet($_GET["code"]))
		$str_code = $_GET["code"];

	$sql_settings = new Query("
		SELECT *
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION["int_current_storeroom"]."
	");
	$int_eject_lines = 12;
	if ($sql_settings->RowCount() > 0) {
		$int_eject_lines = $sql_settings->FieldByName('bill_print_lines_to_eject');
		$str_print_address = $sql_settings->FieldByName('bill_print_address');
		$str_print_phone = $sql_settings->FieldByName('bill_print_phone');
	}
	
	$qry_product = new Query("
		SELECT *
		FROM stock_product sp, stock_measurement_unit mu, ".Monthalize('stock_storeroom_product')." ssp
		WHERE product_code = '".$str_code."'
			AND (sp.measurement_unit_id = mu.measurement_unit_id)
			AND (sp.deleted = 'N')
			AND (ssp.product_id = sp.product_id)
			AND (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")
	");
	$flt_adjusted_stock = $qry_product->FieldByName('stock_adjusted');

	if ($qry_product->RowCount() > 0) {
		$str_unit = $qry_product->FieldByName('measurement_unit');
		
		$int_decimals = 3;
		if ($qry_product->FieldByName('is_decimal') == 'N')
			$int_decimals = 0;
		
		$str_query = "
			SELECT *
			FROM ".Yearalize('stock_balance')."
			WHERE (product_id = ".$qry_product->FieldByName('product_id').")
				AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
				AND (balance_month = ".$_SESSION["int_month_loaded"].")
				AND (balance_year = ".$_SESSION["int_year_loaded"].")
		";
		$qry_summary = new Query($str_query);
	}
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
$str_double = "";
$str_double .= PadWithCharacter($str_double, '=', 80)."\n";
$str_single = "";
$str_single .= PadWithCharacter($str_single, '=', 80)."\n";
$str_eject_lines = "";
for ($i=0;$i<$int_eject_lines;$i++) {
	$str_eject_lines .= "\n"; 
}

$str_statement =
	$str_application_title."\n".
	$str_print_address."\n".
	$str_print_phone."\n".
	$str_double.
	"Statement of stock sold for ".getMonthName($_SESSION['int_month_loaded']-1)." ".$_SESSION['int_year_loaded']."\n".
	$str_single.
	"   Code: ".$qry_product->FieldByName('product_code')."\n".
	"Product: %b".$qry_product->FieldByName('product_description')."%b\n".
	"   Sold: ".number_format($qry_summary->FieldByName('stock_sold'),3,'.',',')." $str_unit\n".
	$str_double.
	$str_eject_lines;
?>

<PRE>
<?
	echo $str_statement;
?>
</PRE>


<? if (stripos($_SERVER['HTTP_USER_AGENT'], 'win') !== FALSE) { ?>
<form name="printerForm" onsubmit="return false;">
<? } else { ?>
<form name="printerForm" method="POST" action="http://10.0.2.2/html/pourtous/print.php">
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

<? if (stripos($_SERVER['HTTP_USER_AGENT'], 'win') !== FALSE) { ?>
<script language="JavaScript">
	writedata();
</script>
<? } else { ?>
<script language="JavaScript">
	printerForm.submit();
</script>
<? } ?>

</body>

</html>