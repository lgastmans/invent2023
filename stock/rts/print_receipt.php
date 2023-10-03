<?
	
	require_once("../../include/db.inc.php");
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/browser_detection.php");
	require_once("../../common/printer.inc.php");

	$copies = $arr_invent_config['billing']['print_copies'];
	$print_name = $arr_invent_config['billing']['print_name'];
	$print_mode = $arr_invent_config['billing']['print_mode'];
	$print_os = browser_detection("os");

	if (IsSet($_GET["id"])) {
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
			$str_tax = $sql_settings->FieldByName('bill_print_note_2');
		}
		
		$sql_receipt = new Query("
			SELECT
				sr.stock_rts_id,
				sr.storeroom_id,
				sr.bill_number,
				sr.date_created,
				sr.total_amount,
				sr.discount,
				sr.description,
				sr.bill_status,
				sr.user_id,
				sr.supplier_id,
				user.username,
				ss.supplier_name
			FROM
				".Monthalize('stock_rts')." sr
				INNER JOIN user ON (user.user_id = sr.user_id)
				INNER JOIN stock_supplier ss ON (sr.supplier_id = ss.supplier_id)
			WHERE (stock_rts_id = ".$_GET["id"].")
		");

		$sql_items = new Query("
			SELECT
				sri.rts_item_id,
				sri.quantity,
				sri.bprice,
				sri.price,
				sp.product_code,
				sp.product_description,
				sb.batch_code
			FROM
				".Monthalize('stock_rts_items')." sri
				INNER JOIN stock_product sp ON (sp.product_id = sri.product_id)
				INNER JOIN ".Yearalize('stock_batch')." sb ON (sb.batch_id = sri.batch_id)
			WHERE (sri.rts_id = ".$sql_receipt->FieldByName('stock_rts_id').")
		");
	}
?>
<html>
<head><TITLE>Printing Bill</TITLE>
<link href="../../include/styles.css" rel="stylesheet" type="text/css">
</head>

<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0">

<?
	define('BILL_WIDTH', 76);

// Application Title
$str_app_print = $str_application_title;

// User address
$str_address_print = $str_print_address;

// User phone
$str_phone_print = $str_print_phone;

// Receipt number and date
$int_spaces = BILL_WIDTH - (9 + strlen(trim($sql_receipt->FieldByName('bill_number'))) + 6 + 10);
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$str_billnum_date = "Receipt: ".trim($sql_receipt->FieldByName('bill_number')).$str_spaces."Date: ".FormatDate($sql_receipt->FieldByName('date_created'));

// Returned to supplier
$str_supplier = "To : ".$sql_receipt->FieldByName('supplier_name');

// Note
$str_note='';
if ($sql_receipt->FieldByName('description') != '')
	$str_note = "Note : ".$sql_receipt->FieldByName('description');
$str_note = wordwrap($str_note, BILL_WIDTH, "\n");

$str_check_type = "[] Damaged goods     [] Customer discount     [] Unit's request \nOther:".PadWithCharacter($str_note,'.',60);;

$str_bill_header=
"
$str_app_print
$str_address_print
$str_phone_print \n
%b Debit Note %n \n
$str_supplier
$str_check_type
$str_note
$str_billnum_date";

/* Number of characters for 
Code				6
Batch				6
Description		25
Qty				8
Price				8
Tax				5
Total				8
*/


$str_bill_header .="
============================================================================
      Code    Batch Description               Qty   BPrice    Price    Total
----------------------------------------------------------------------------";

$str_bill_items="";

$flt_bprice_total = 0;

for ($i=0; $i<$sql_items->RowCount(); $i++) {
$item_total = 0;
$item_total = ($sql_items->FieldByName('quantity') * $sql_items->FieldByName('price'));

$tmp_str = StuffWithCharacter($sql_items->FieldByName('product_code'), ' ', 10)." ".
StuffWithCharacter($sql_items->FieldByName('batch_code'), ' ', 8)." ".
addslashes(PadWithCharacter($sql_items->FieldByName('product_description'), ' ', 23))." ".
StuffWithCharacter($sql_items->FieldByName('quantity'), ' ', 5)." ".
StuffWithCharacter(sprintf("%01.2f", $sql_items->FieldByName('bprice')), ' ', 8)." ".
StuffWithCharacter(sprintf("%01.2f", $sql_items->FieldByName('price')), ' ', 8)." ".
StuffWithCharacter(sprintf("%01.2f", $item_total), ' ', 8);

$str_bill_items = $str_bill_items."
".$tmp_str;

$flt_bprice_total += $sql_items->FieldByName('bprice') * $sql_items->FieldByName('quantity');

$sql_items->next();
}

$str_user = "User: ".$sql_receipt->FieldByName('username');

$int_spaces = BILL_WIDTH - 8 - 18 - (strlen(sprintf("%01.2f", $flt_bprice_total)));
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$int_spaces2 = 9 - strlen(sprintf("%01.2f", $sql_receipt->FieldByName('total_amount')));
$str_spaces2 = PadWithCharacter($str_spaces2, ' ', $int_spaces2);
$str_total = "Totals: ".$str_spaces.sprintf("%01.2f", $flt_bprice_total).$str_spaces2.sprintf("%01.2f", $sql_receipt->FieldByName('total_amount'));


if ($sql_receipt->FieldByName('discount') > 0) {
$flt_discount = ($sql_receipt->FieldByName('total_amount') * ($sql_receipt->FieldByName('discount')/100)); 

$int_spaces = BILL_WIDTH - (13 + strlen(number_format($sql_receipt->FieldByName('discount'),0,'.','')) + strlen(number_format($flt_discount, 2, '.', '')));
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$str_promotion = $str_spaces."Discount ".number_format($sql_receipt->FieldByName('discount'),0,'.','')."% : ".number_format($flt_discount, 2, '.', '');

$flt_grandtotal = ($sql_receipt->FieldByName('total_amount') * (1 - $sql_receipt->FieldByName('discount')/100)); 
$int_spaces = BILL_WIDTH - (13 + strlen(sprintf("%01.2f", $flt_grandtotal)));
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$str_grandtotal = $str_spaces."Grand total: ".sprintf("%01.2f", $flt_grandtotal);



$str_bill_footer="
----------------------------------------------------------------------------
".$str_total."
".$str_promotion."
".$str_grandtotal."
============================================================================
".$str_user."
";
}

else {
$str_bill_footer="
----------------------------------------------------------------------------
".$str_total."
============================================================================
".$str_user."
";
}

$str_eject_lines = "";
for ($i=0;$i<$int_eject_lines;$i++) {
  $str_eject_lines .= "\n"; 
}

$str_signature = "\n\n\n\n\n
Party                      for ".$str_application_title."
Signature                  Executive\n";

$str_bill = $str_bill_header.$str_bill_items.$str_bill_footer.$str_tax.$str_signature.$str_eject_lines."%n";
$str_bill = replaceSpecialCharacters($str_bill);
?>

<PRE>
<?
	echo $str_bill;
?>
</PRE>

<form name="printerForm" method="POST" action="http://localhost/print.php">

<table width="100%" bgcolor="#E0E0E0"><TR><TD height=45 class="headerText" bgcolor="#808080">&nbsp;<font class='title'>Printing Payment</font></TD></TR>
<tr>
<TD>
<br>
	<input type="hidden" name="data" value="<? echo ($str_bill); ?>"><br>
	  <input type="hidden" name="os" value="<? echo $os;?>"><br>
	  <input type="hidden" name="print_name" value="<? echo $print_name?>"><br>
	  <input type="hidden" name="print_mode" value="<? echo $print_mode?>"><br>

<table border=0>
<Td class='normaltext'><textarea name='printerStatus' height=5 rows=5 cols=40 class='editbox'></textarea></td></TR>
<TR><td align='center'><br> <input type='submit' name='doaction' value="Print">
<input type='button' onclick="window.close();" name='doaction' value="Close"></td></TR>
</table>
</td></tr>
</table>

</form>

<script language="JavaScript">
	printerForm.submit();
</script>

<?
if (isset($sql_receipt)) 
	$sql_receipt->Free();
if (isset($sql_items)) 
	$sql_items->Free();
?>
</body>
</html>