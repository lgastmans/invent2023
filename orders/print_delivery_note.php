<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../include/browser_detection.php");
	require_once("../common/printer.inc.php");

	//====================
	// get the user defined settings
	//====================
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
	
	$arr_days = array();
	$arr_days[ORDER_DAY_SUNDAY] = 'Sunday';
	$arr_days[ORDER_DAY_MONDAY] = 'Monday';
	$arr_days[ORDER_DAY_TUESDAY] = 'Tuesday';
	$arr_days[ORDER_DAY_WEDNESDAY] = 'Wednesday';
	$arr_days[ORDER_DAY_THURSDAY] = 'Thursday';
	$arr_days[ORDER_DAY_FRIDAY] = 'Friday';
	$arr_days[ORDER_DAY_SATURDAY] = 'Saturday';
	

	$int_id = 0;
	if (IsSet($_GET['id'])) {
		$int_id = $_GET['id'];
		
		$str_query = "
			SELECT *
			FROM ".Monthalize('orders')." o, ".Monthalize('bill')." b
			WHERE (b.bill_id = ".$int_id.")
				AND (b.module_id = 7)
				AND (b.module_record_id = o.order_id)
				AND (b.storeroom_id = ".$_SESSION['int_current_storeroom'].")
				AND (o.storeroom_id = ".$_SESSION['int_current_storeroom'].")
		";
		$qry = new Query($str_query);
		
		$str_items = "
			SELECT *, SUM(bi.quantity + bi.adjusted_quantity) AS quantity
			FROM ".Monthalize('bill_items')." bi, stock_product sp, stock_measurement_unit smu
			WHERE (bi.product_id = sp.product_id)
				AND (bi.bill_id = ".$int_id.")
				AND (sp.measurement_unit_id = smu.measurement_unit_id)
			GROUP BY bi.product_id
			ORDER BY sp.product_description
		";
		$qry_items = new Query($str_items);
		
		$is_cancelled = false;
		if ($qry->FieldByName('bill_status') == BILL_STATUS_CANCELLED)
			$is_cancelled = true;

	}
	else if (IsSet($_GET['order_id'])) {
		$int_id = $_GET['order_id'];
		
		$str_query = "
			SELECT *
			FROM ".Monthalize('orders')." o
			WHERE (o.order_id = ".$int_id.")
				AND (o.storeroom_id = ".$_SESSION['int_current_storeroom'].")
		";
		$qry = new Query($str_query);
		
		$str_items = "
			SELECT *, oi.quantity_ordered AS quantity
			FROM ".Monthalize('order_items')." oi, stock_product sp, stock_measurement_unit smu
			WHERE (oi.product_id = sp.product_id)
				AND (oi.order_id = ".$int_id.")
				AND (sp.measurement_unit_id = smu.measurement_unit_id)
			ORDER BY sp.product_description
		";
		$qry_items = new Query($str_items);
		
		$is_cancelled = false;
		if ($qry->FieldByName('order_status') == ORDER_STATUS_CANCELLED)
			$is_cancelled = true;
	}


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
$str_cancelled = '';
if ($is_cancelled)
	$str_cancelled = "THIS ORDER IS CANCELLED\n";

$str_double = "";
$str_double = PadWithCharacter($str_double, '=', 80);

$str_single = "";
$str_single = PadWithCharacter($str_single, '-', 80);

$str_temp = "";
if ($qry->FieldByName('payment_type') == BILL_ACCOUNT) {
	$str_temp = "Account: ".$qry->FieldByName('account_number')." ".$qry->FieldByName('account_name');
}
$str_header = PadWithCharacter($str_temp, ' ', 43);

$str_temp = "Comm.: ".$qry->FieldByName('community');
$str_header .= PadWithCharacter($str_temp, ' ', 22);

$str_temp = "Day: ".$arr_days[$qry->FieldByName('day_of_week')];
$str_header .= PadWithCharacter($str_temp, ' ', 16);

$str_note = $qry->FieldByName('note');

$str_column_headers = " Code  ".
PadWithCharacter("Description", ' ', 25)." ".
StuffWithCharacter("Ordered", ' ', 12)."  ".
PadWithCharacter("Delivered", ' ', 9)."   ".
PadWithCharacter("Changes", ' ', 9)."  ".
PadWithCharacter("New Orders", ' ', 10);


$str_data = "";
for ($i=0;$i<$qry_items->RowCount();$i++) {
	
	if ($qry_items->FieldByName('is_decimal') == 'Y')
		$flt_quantity = number_format($qry_items->FieldByName('quantity'),2,'.','');
	else
		$flt_quantity = number_format($qry_items->FieldByName('quantity'),0,'.','');
	
	if ($qry_items->FieldByName('quantity') > 0)
		$str_data .= StuffWithCharacter($qry_items->FieldByName('product_code'), ' ', 5)."  ".
			PadWithCharacter($qry_items->FieldByName('product_description'), ' ', 25)." ".
			StuffWithCharacter(number_format($flt_quantity,2,'.','')." ".$qry_items->FieldByName('measurement_unit'), ' ', 12)."  ".
			PadWithCharacter(". . . . .", ' ', 9)."   ".
			PadWithCharacter(". . . . .", ' ', 9)."  ".
			PadWithCharacter("", ' ', 10)."\n";
	
	$qry_items->Next();
}

//====================
// generate the number spaces after
//====================

$str_eject_lines = "";
for ($i=0;$i<$int_eject_lines;$i++) {
  $str_eject_lines .= "\n"; 
}

$str_statement = "
".$str_application_title."
".$str_print_address."
".$str_print_phone."

".$str_cancelled."
".$str_header."
".$str_double."
".$str_column_headers."
".$str_single."
".$str_data."
".$str_single."
".$str_note."
".$str_eject_lines;

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