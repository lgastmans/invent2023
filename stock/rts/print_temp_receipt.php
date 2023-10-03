<?
	
	require_once("../../include/db.inc.php");
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/browser_detection.php");
	require_once("../../common/printer.inc.php");

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
	
	$qry_number = new Query("
		SELECT MAX(bill_number) AS bill_number
		FROM ".Monthalize('stock_rts'));
	$int_bill_number = $qry_number->FieldByName('bill_number') +1;
	
	$qry_supplier = new Query("
		SELECT *
		FROM stock_supplier
		WHERE supplier_id = ".$_SESSION['current_supplier_id']);

	$qry_user = new Query("
		SELECT *
		FROM user
		WHERE user_id = ".$_SESSION['int_user_id']."
	");
?>
<html>
<head><TITLE>Printing Bill</TITLE>
<link href="../../include/styles.css" rel="stylesheet" type="text/css">
</head>

<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0">

<?
	define('BILL_WIDTH', 66);

// Application Title
$str_app_print = $str_application_title;

// User address
$str_address_print = $str_print_address;

// User phone
$str_phone_print = $str_print_phone;

// Receipt number and date
$int_spaces = BILL_WIDTH - (9 + strlen(trim($int_bill_number)) + 6 + 10);
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$str_billnum_date = "Receipt: ".trim($int_bill_number).$str_spaces."Date: ".FormatDate(date('Y')."-".date('n')."-".date('j'));

// Returned to supplier
$str_supplier = "To : ".$qry_supplier->FieldByName('supplier_name');

// Note
$str_note='';
if ($_SESSION['current_note'] != '')
	$str_note = "Note : ".$_SESSION['current_note'];
$str_note = wordwrap($str_note, BILL_WIDTH, "\n");

$str_check_type = "[] Damaged goods     [] Customer discount     [] Unit's request \nOther:".PadWithCharacter($str_note,'.',60);;

$str_bill_header=
"
$str_app_print
$str_address_print
$str_phone_print \n
%b Debit Note %b \n
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
==================================================================
  Code    Batch Description               Qty    Price    Total
------------------------------------------------------------------";

$str_bill_items="";

$flt_total_amount = 0;
for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {
$item_total = 0;
$item_total = ($_SESSION["arr_total_qty"][$i][2] * $_SESSION["arr_total_qty"][$i][6]);

$tmp_str = StuffWithCharacter($_SESSION["arr_total_qty"][$i][0], ' ', 6)." ".
StuffWithCharacter($_SESSION["arr_total_qty"][$i][1], ' ', 8)." ".
PadWithCharacter($_SESSION["arr_total_qty"][$i][12], ' ', 23)." ".
StuffWithCharacter($_SESSION["arr_total_qty"][$i][2], ' ', 5)." ".
StuffWithCharacter(sprintf("%01.2f", $_SESSION["arr_total_qty"][$i][6]), ' ', 8)." ".
StuffWithCharacter(sprintf("%01.2f", $item_total), ' ', 8);

$flt_total_amount += $item_total;

$str_bill_items = $str_bill_items."
".$tmp_str;
}

$str_user = "User: ".$qry_user->FieldByName('username');
$int_spaces = BILL_WIDTH - (strlen($str_user) + 7 + strlen(sprintf("%01.2f", $flt_total_amount)));
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces,' ', $int_spaces);
$str_total = $str_user.$str_spaces."Total: ".sprintf("%01.2f", $flt_total_amount);


if ($_SESSION['current_discount'] > 0) {
$flt_discount = ($flt_total_amount * ($_SESSION['current_discount']/100)); 

$int_spaces = BILL_WIDTH - (13 + strlen(number_format($_SESSION['current_discount'],0,'.','')) + strlen(number_format($flt_discount, 2, '.', '')));
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$str_promotion = $str_spaces."Discount ".number_format($_SESSION['current_discount'],0,'.','')."% : ".number_format($flt_discount, 2, '.', '');

$flt_grandtotal = ($flt_total_amount * (1 - $_SESSION['current_discount']/100)); 
$int_spaces = BILL_WIDTH - (13 + strlen(sprintf("%01.2f", $flt_grandtotal)));
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$str_grandtotal = $str_spaces."Grand total: ".sprintf("%01.2f", $flt_grandtotal);


$str_bill_footer="
------------------------------------------------------------------
".$str_total."
".$str_promotion."
".$str_grandtotal."
==================================================================
";
}

else {
$str_bill_footer="
------------------------------------------------------------------
".$str_total."
==================================================================
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
//	echo $str_bill;
?>
</PRE>

<form name="printerForm" method="POST" action="http://localhost/print.php">

<table width="100%" bgcolor="#E0E0E0"><TR><TD height=45 class="headerText" bgcolor="#808080">&nbsp;<font class='title'>Printing Payment</font></TD></TR>
	<tr>
		<td>
			<br>
			<input type="hidden" name="data" value="<? echo ($str_bill); ?>"><br>

			<table border=0>
				<Td class='normaltext'><textarea name='printerStatus' height=5 rows=5 cols=40 class='editbox'></textarea></td></TR>
				<TR><td align='center'><br> <input type='submit' name='doaction' value="Print">
				<input type='button' onclick="window.close();" name='doaction' value="Close"></td></TR>
			</table>
		</td>
	</tr>
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