<?
	require_once("../include/browser_detection.php");
	require_once("../common/tax.php");
	require_once("../common/printer.inc.php");
	require_once("../common/account.php");

	if (IsSet($_GET["id"])) {
		
		$qry_order = new Query("
			SELECT 
				b.*, 
				u.username
			FROM ".Monthalize("bill")." b
				LEFT JOIN user u ON (u.user_id = b.user_id)
			WHERE (bill_id=".$_GET["id"].")
		");
		
		$qry_order_details = new Query("
			SELECT *
			FROM ".Monthalize('orders')."
			WHERE order_id = ".$qry_order->FieldByName('module_record_id')."
		");
		if ($qry_order_details->FieldByName('day_of_week') == ORDER_DAY_SUNDAY)
			$str_order_day = 'SUNDAY order';
		else if ($qry_order_details->FieldByName('day_of_week') == ORDER_DAY_MONDAY)
			$str_order_day = 'MONDAY order';
		else if ($qry_order_details->FieldByName('day_of_week') == ORDER_DAY_TUESDAY)
			$str_order_day = 'TUESDAY order';
		else if ($qry_order_details->FieldByName('day_of_week') == ORDER_DAY_WEDNESDAY)
			$str_order_day = 'WEDNESDAY order';
		else if ($qry_order_details->FieldByName('day_of_week') == ORDER_DAY_THURSDAY)
			$str_order_day = 'THURSDAY order';
		else if ($qry_order_details->FieldByName('day_of_week') == ORDER_DAY_FRIDAY)
			$str_order_day = 'FRIDAY order';
		else if ($qry_order_details->FieldByName('day_of_week') == ORDER_DAY_SATURDAY)
			$str_order_day = 'SATURDAY order';

		$qry_items = new Query("
			SELECT bi.quantity_ordered, SUM(bi.quantity) AS quantity, SUM(bi.adjusted_quantity) AS adjusted_quantity,
				bi.discount, bi.price, bi.tax_id,
				sp.product_code, sp.product_description
			FROM ".Monthalize("bill_items")." bi
				INNER JOIN stock_product sp ON (sp.product_id = bi.product_id)
			WHERE (bi.bill_id = ".$qry_order->FieldByName('bill_id').")
			GROUP BY product_code
			ORDER BY product_code
		");
		
		$sql_settings = new Query("
			SELECT *
			FROM user_settings
			WHERE storeroom_id = ".$_SESSION["int_current_storeroom"]."
		");
		$int_eject_lines = 12;
		$str_note = "";
		if ($sql_settings->RowCount() > 0) {
			$int_eject_lines = $sql_settings->FieldByName('bill_print_lines_to_eject');
			$str_tmp = $sql_settings->FieldByName('order_global_message');
			$str_print_address = $sql_settings->FieldByName('bill_print_address');
			$str_print_phone = $sql_settings->FieldByName('bill_print_phone');
			define('BILL_WIDTH', 80);
			$str_note = wordwrap($str_tmp, BILL_WIDTH, "\n");
		}


		// get the tax details for the storeroom
		$result_set = new Query("
			SELECT is_taxed, is_cash_taxed, is_account_taxed
			FROM stock_storeroom
			WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")"
		);
		$is_taxed = 'Y';
		$is_cash_taxed = 'Y';
		$is_account_taxed = 'Y';
		if ($result_set->RowCount() > 0) {
			$is_taxed = $result_set->FieldByName('is_taxed');
			$is_cash_taxed = $result_set->FieldByName('is_cash_taxed');
			$is_account_taxed = $result_set->FieldByName('is_account_taxed');
		}
	}
?>
<html>
<head><TITLE>Printing Order Bill</TITLE>
<link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>

<? if (browser_detection( 'os' ) === 'lin') { ?>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0">
<? } else { ?>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0" onload="CheckTC()">
<? } ?>

<?
$str_double = '';
$str_double = PadWithCharacter($str_double, '=', BILL_WIDTH)."\n";

$str_single = '';
$str_single = PadWithCharacter($str_single, '-', BILL_WIDTH)."\n";

$str_bill_number = "Bill ".$qry_order->FieldByName('bill_number')."\n";

$str_header = $qry_order->FieldByName('account_number')." ".$qry_order->FieldByName('account_name');
$int_spaces = BILL_WIDTH - 10 - (strlen($str_header));
$str_spaces = '';
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$str_header = $str_header.$str_spaces.FormatDate($qry_order->FieldByName('date_created'))."\n";

$str_columns = StuffWithCharacter("Code", ' ', 5)." ".
	PadWithCharacter("Description", ' ', 26)." ".
	StuffWithCharacter("Ordered", ' ', 8)." ".
	StuffWithCharacter("Delivered", ' ', 9)." ".
	StuffWithCharacter("Rate", ' ', 7)." ".
	StuffWithCharacter("Total", ' ', 9)."\n";

$str_items = '';
$flt_total = 0;

for ($i=0; $i<$qry_items->RowCount(); $i++) {

	$item_total = 0;

	$calculate_tax = $is_taxed;
	// calculate the tax and the total cost per item billed
	if ($is_taxed == 'Y') {
		if ($qry_order->FieldByName('payment_type') == BILL_CASH) {
			if ($is_cash_taxed == 'Y')
				$calculate_tax = 'Y';
			else
				$calculate_tax = 'N';
		}
		else if ($qry_order->FieldByName('payment_type') == BILL_ACCOUNT) {
			if ($is_account_taxed == 'Y')
				$calculate_tax = 'Y';
			else
				$calculate_tax = 'N';
		}
	}
	else
		$calculate_tax = 'N';

	$total_quantity = $qry_items->FieldByName('quantity') + $qry_items->FieldByName('adjusted_quantity');
  
	$flt_price = 0;
	if ($calculate_tax == 'Y') {
		if ($qry_items->FieldByName('discount') > 0) {
			$discount_price = $qry_items->FieldByName('price') * (1 - ($qry_items->FieldByName('discount')/100));
			$tax_amount = calculateTax($discount_price, $qry_items->FieldByName('tax_id'));
			$flt_price = $discount_price + $tax_amount;
			$item_total = ($total_quantity * ($discount_price + $tax_amount));
		}
		else {
			$tax_amount = calculateTax($qry_items->FieldByName('price'), $qry_items->FieldByName('tax_id'));
			$flt_price = $qry_items->FieldByName('price') + $tax_amount;
			$item_total = ($total_quantity * ($qry_items->FieldByName('price') + $tax_amount));
		}
	}
	else {
		$tax_amount = 0;
		if ($qry_items->FieldByName('discount') > 0) {
			$discount_price = $qry_items->FieldByName('price') * (1 - ($qry_items->FieldByName('discount')/100));
			$flt_price = $discount_price;
			$item_total = ($total_quantity * $discount_price);
		}
		else {
			$flt_price = $qry_items->FieldByName('price');
			$item_total = ($total_quantity * $qry_items->FieldByName('price'));
		}
	}

	$flt_total += $item_total;
	
	$str_items .= StuffWithCharacter($qry_items->FieldByName('product_code'), ' ', 5)." ".
		PadWithCharacter($qry_items->FieldByName('product_description'), ' ', 26)." ".
		StuffWithCharacter(number_format($qry_items->FieldByName('quantity_ordered'), 2, '.', ''), ' ', 8)." ".
		StuffWithCharacter(number_format($total_quantity, 2, '.', ''), ' ', 9)." ".
		StuffWithCharacter(number_format($flt_price, 2, '.', ''), ' ', 7)." ".
		StuffWithCharacter(number_format($item_total, 2, '.', ''), ' ', 9)."\n";
		
	$qry_items->Next();
}

//==============================================================================
// Total
//------------------------------------------------------------------------------
$str_total = "Total : ".number_format($flt_total, 2, '.', '');
$int_spaces = BILL_WIDTH - 11 - strlen($str_total);
$str_spaces = '';
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$str_total = $str_spaces.$str_total."\n";

//==============================================================================
// Balance
//------------------------------------------------------------------------------
$str_retval = get_account_status($qry_order->FieldByName('account_number'));
$arr_retval = explode('|',$str_retval);
$str_balance = 'unknown';
if ($arr_retval[0] == 'OK') {
	$str_balance = "Your balance: Rs. ".$arr_retval[2]."\n";
}

$str_eject_lines = "";
for ($i=0;$i<$int_eject_lines;$i++) {
  $str_eject_lines .= "\n"; 
}

$str_bill =
	$str_bill_number.
	$str_order_day."\n".
	$str_header.
	$str_double.
	$str_columns.
	$str_single.
	$str_items.
	$str_single.
	"%b".$str_total."%n".
	"%b".$str_balance."%n".
	"%b".$str_note."%n".
	$str_eject_lines."%n";

$str_bill = replaceSpecialCharacters($str_bill);

?>

<PRE>
<?
 echo $str_bill;
?>
</PRE>

<? if (browser_detection("os") === "lin") { ?>
<form name="printerForm" method="POST" action="http://localhost/print.php">
<? } else { ?>
<form name="printerForm" onsubmit="return false;">
<? } ?>

<table width="100%" bgcolor="#E0E0E0"><TR><TD height=45 class="headerText" bgcolor="#808080">&nbsp;<font class='title'>Printing Payment</font></TD></TR>
<tr>
<TD>
<br>
<? if (browser_detection("os") === "lin") { ?>
<input type="hidden" name="data" value="<? echo ($str_bill); ?>"><br>
<? } else { ?>
<input type="hidden" name="output" value="<? echo htmlentities($str_bill); ?>">
<? } ?>

<table border=0>
<Td class='normaltext'><textarea name='printerStatus' height=5 rows=5 cols=40 class='editbox'></textarea></td></TR>
<TR><td align='center'><br> <input type='submit' name='doaction' value="Print">
<input type='button' onclick="window.close();" name='doaction' value="Close"></td></TR>
</table>
</td></tr>
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