<?
	// formatted at 136 characters page width for condensed mode at 10cpi
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../include/browser_detection.php");
	require_once("../common/printer.inc.php");

	$sql_settings = new Query("
		SELECT *
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION["int_current_storeroom"]."
	");
	$int_eject_lines = 12;
	$str_note = "";
	if ($sql_settings->RowCount() > 0) {
		$int_eject_lines = $sql_settings->FieldByName('bill_print_lines_to_eject');
		$str_print_address = $sql_settings->FieldByName('bill_print_address');
		$str_print_phone = $sql_settings->FieldByName('bill_print_phone');
	}

	$int_prev_month=getPreviousMonth();

	$str_supplier = "
		SELECT
			ss.supplier_name,
			ss.contact_person,
			ss.supplier_address,
			ss.supplier_phone,
			ss.supplier_cell
		FROM stock_supplier ss, ".Yearalize('purchase_order')." po
		WHERE (ss.supplier_id = po.supplier_id)
			AND (po.purchase_order_id = ".$_GET["po_id"].")";
	$qry_supplier = new Query($str_supplier);
	
	$qry_print = "
		SELECT po.purchase_order_ref, po.date_created, po.single_supplier, 
			sp.product_code, sp.product_description, sp.deleted,
			pi.buying_price, pi.selling_price, pi.quantity_ordered,
			ssp.stock_minimum, ssp.stock_ordered, ssp.stock_current,
			sby.stock_sold,
			ss.supplier_name,
			ss.supplier_phone,
			user.username,
			smu.measurement_unit
		FROM 
			".Monthalize('stock_storeroom_product')." ssp,
			stock_supplier ss
		INNER JOIN ".Yearalize('purchase_order')." po ON (po.purchase_order_id = ".$_GET["po_id"].")
		INNER JOIN ".Yearalize('purchase_items')." pi ON (pi.purchase_order_id = ".$_GET["po_id"].")
		INNER JOIN stock_product sp ON (sp.product_id = pi.product_id)
		LEFT JOIN user ON (user.user_id = po.assigned_to_user_id)
		LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
		LEFT JOIN ".Yearalize('stock_balance')." sby ON (sby.product_id = pi.product_id)
			AND (sby.storeroom_id = ".$_SESSION["int_current_storeroom"].")
			AND (sby.balance_month = ".$int_prev_month[1].")
		WHERE (ssp.product_id = pi.product_id)
			AND (ssp.storeroom_id = ".$_SESSION["int_current_storeroom"].") 	
			AND (ss.supplier_id = sp.supplier_id)
		ORDER BY supplier_name, product_code
		";
//echo $qry_print;
	$result_print = new Query($qry_print);

	$str_app_print = $str_application_title;	
	$str_address_print = $str_print_address;
	$str_phone_print = $str_print_phone;
	$str_data ='';
	
	$str_supplier_info = '';
	if ($result_print->FieldByName('single_supplier') == 'Y') {
		$str_double = "=================================================================\n";
		$str_single = "----- ------------------------------ -------- ----------- -------\n";
		$str_supplier_info = "Supplier : ".$qry_supplier->FieldByName('supplier_name')."\n".
			"Contact  : ".$qry_supplier->FieldByName('contact_person')."\n".
			"Address  : ".$qry_supplier->FieldByName('supplier_address')."\n".
			"Phone    : ".$qry_supplier->FieldByName('supplier_phone')."\n".
			"Cell     : ".$qry_supplier->FieldByName('supplier_cell')."\n\n";
		
		$str_header = $str_double.
			"                                       Buying                    \n".
			" Item Description                       Price     Ordered  Actual\n".
			$str_single;
	}
	else {
		$str_double = "========================================================================================\n";
		$str_single = "----- ------------------------------ -------- ----------- ------- ----------------------\n";
		$str_header =
			$str_double.
			"                                       Buying                                           \n".
			" Item Description                       Price     Ordered  Actual Supplier              \n".
			$str_single;
	}

if ($result_print->RowCount() > 0) {

$str_data =
$str_app_print."\n".
$str_address_print."\n".
$str_phone_print."\n\n".
$str_supplier_info.
"Purchase Order: ".$result_print->FieldByName('purchase_order_ref')."  Assigned to: ".$result_print->FieldByName('username')."\n".
"Date Created  : ".FormatDate($result_print->FieldByName('date_created'))."\n".
$str_header;

		$cur_supplier = "";
		$str_items = '';

		for ($i=0;$i<$result_print->RowCount();$i++) {
			$str_supplier = '';
			if ($cur_supplier != $result_print->FieldByName('supplier_name')) {
				$cur_supplier = $result_print->FieldByName('supplier_name');
				$str_supplier = StuffWithCharacter($result_print->FieldByName('supplier_name'), ' ', 20);
			}
			
			if ($result_print->FieldByName('single_supplier') == 'Y')
				$str_supplier = '';
				
			$str_items .= StuffWithCharacter($result_print->FieldByName('product_code'), ' ', '5')." ".
				PadWithCharacter($result_print->FieldByName('product_description'), ' ', 30)." ".
				StuffWithCharacter(number_format($result_print->FieldByName('buying_price'), 2, '.', ''), ' ', 8)." ".
				StuffWithCharacter(number_format($result_print->FieldByName('quantity_ordered'), 2, '.', ''), ' ', 7)." ".
				PadWithCharacter($result_print->FieldByName('measurement_unit'), ' ', 3)." ".
				StuffWithCharacter(number_format($result_print->FieldByName('stock_ordered'), 2, '.', ''), ' ', 7)." ".$str_supplier."\n";

			$result_print->Next();
		}
		$str_data .= $str_items.$str_double;
		$str_data .= $result_print->RowCount()." items listed.\n";
	}


$str_eject_lines = "";
for ($i=0;$i<$int_eject_lines;$i++) {
  $str_eject_lines .= "\n"; 
}

$str_data .= $str_eject_lines;


?>

<PRE>
<?
echo $str_data;
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
<input type="hidden" name="data" value="<? echo ($str_data); ?>"><br>
<? } else { ?>
<input type="hidden" name="output" value="<? echo htmlentities($str_data); ?>">
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
