<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../include/browser_detection.php");
	require_once("../common/printer.inc.php");
	require_once("../common/print_funcs.inc.php");

	$sql_settings = new Query("
		SELECT *
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION["int_current_storeroom"]."
	");
	if ($sql_settings->RowCount() > 0) {
		$int_eject_lines = $sql_settings->FieldByName('bill_print_lines_to_eject');
		$str_print_address = $sql_settings->FieldByName('bill_print_address');
		$str_print_phone = $sql_settings->FieldByName('bill_print_phone');
	}

	$str_totals_date = date('d-m-Y');
	if (IsSet($_GET['totals_date']))
		$str_totals_date = $_GET['totals_date'];
	
	$str_include_delivered = 'N';
	if (IsSet($_GET['include_delivered']))
		$str_include_delivered = $_GET['include_delivered'];

	function getMySQLDate($str_date) {
		if ($str_date == '')
			$str_date = date('d-m-Y');
		$arr_date = explode('-', $str_date);
		return sprintf("%04d-%02d-%02d", $arr_date[2], $arr_date[1], $arr_date[0]);
	}

	if ($str_include_delivered == 'Y')
		$str_totals = "
			SELECT
				sp.product_code, sp.product_description, SUM(bi.quantity_ordered) AS total,
				smu.measurement_unit
			FROM ".Monthalize('bill')." b,
				".Monthalize('bill_items')." bi,
				stock_product sp
			LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			WHERE (bi.product_id = sp.product_id)
				AND (bi.bill_id = b.bill_id)
				AND (b.module_id = 7)
				AND (DATE(b.date_created) = '".getMySQLDate($str_totals_date)."')
			GROUP BY bi.product_id
			ORDER BY sp.product_description";
	else
		$str_totals = "
			SELECT
				sp.product_code, sp.product_description, SUM(bi.quantity_ordered) AS total,
				smu.measurement_unit
			FROM ".Monthalize('bill')." b,
				".Monthalize('bill_items')." bi,
				stock_product sp
			LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			WHERE (bi.product_id = sp.product_id)
				AND (bi.bill_id = b.bill_id)
				AND (b.module_id = 7)
				AND (DATE(b.date_created) = '".getMySQLDate($str_totals_date)."')
				AND (b.is_pending = 'Y')
				AND (b.bill_status = ".BILL_STATUS_UNRESOLVED.")
			GROUP BY bi.product_id
			ORDER BY sp.product_description";

	$qry_totals = new Query($str_totals);
?>

<html>
<head><TITLE>Printing Statement</TITLE>
<link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>

<? if (browser_detection( 'os' ) === 'lin') { ?>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0">
<? } else { ?>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0" onload="CheckTC()">
<? } ?>

<?
$str_info = "Order Totals for ".makeHumanDate(getMySQLDate($str_totals_date));

    $print = new print_page;
    $print->query = $qry_totals;
    $print->arr_columns = array(
        0 => array('product_code', 'Code', 6, 'right', 'string'),
        1 => array('product_description', 'Description', 20, 'left', 'string'),
        2 => array('total', 'Ordered', 10, 'right', 'number'),
        3 => array('measurement_unit', 'Unit', 5, 'left', 'string')
    );
    $print->int_space_between = 1;
    $print->int_total_lines = 65;
    $print->int_total_columns = 2;
    
    $str_header = $print->get_header();
    $str_data = $print->get_data();

$str_eject_lines = "";
for ($i=0;$i<$int_eject_lines;$i++) {
  $str_eject_lines .= "\n"; 
}

$str_statement = "
".$str_application_title."
".$str_print_address."
".$str_print_phone."

".$str_info."
".$str_header.$str_data;

$str_statement = replaceSpecialCharacters($str_statement);
?>

<PRE>
<?
 echo $str_statement;
?>
</PRE>


<? if (browser_detection("os") === "lin") { ?>
<form name="printerForm" method="POST" action="http://localhost/print.php">
<? } else { ?>
<form name="printerForm" onsubmit="return false;">
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
      <? if (browser_detection("os") === "lin") { ?>
      <input type="hidden" name="data" value="<? echo ($str_statement); ?>"><br>
      <? } else { ?>
      <input type="hidden" name="output" value="<? echo htmlentities($str_statement); ?>">
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

<? if (browser_detection( 'os' ) === 'lin') { ?>

<script language="JavaScript">
	printerForm.submit();
</script>

<? } else { ?>

<script language="JavaScript">
	writedata();
</script>

<? } ?>

</body>
</html>
