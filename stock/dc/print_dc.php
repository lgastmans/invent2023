<?
	
	require_once("../../include/db.inc.php");
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/browser_detection.php");
	require_once("../../common/printer.inc.php");
	require_once("../../common/product_funcs.inc.php");

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
		
		$qry = new Query("
			SELECT
				*,
				user.username
			FROM
				".Monthalize('dc')." d
				LEFT JOIN customer c ON (c.id = d.client_id)
				INNER JOIN user ON (user.user_id = d.user_id)
			WHERE (dc_id = ".$_GET["id"].")
		");

		$qry_items = new Query("
			SELECT
				di.*,
				sp.product_code,
				sp.product_description,
				sb.batch_code
			FROM
				".Monthalize('dc_items')." di
				INNER JOIN stock_product sp ON (sp.product_id = di.product_id)
				INNER JOIN ".Yearalize('stock_batch')." sb ON (sb.batch_id = di.batch_id)
			WHERE (di.dc_id = ".$qry->FieldByName('dc_id').")
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
$int_spaces = BILL_WIDTH - (9 + strlen(trim($qry->FieldByName('dc_number'))) + 6 + 10);
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$str_billnum_date = "DC: ".trim($qry->FieldByName('dc_number')).$str_spaces."Date: ".FormatDate($qry->FieldByName('date_created'));

// Client
$str_supplier = "To : ".$qry->FieldByName('company');

$str_bill_header=
"
$str_app_print
$str_address_print
$str_phone_print \n
%b Delivery Chalan %n \n
$str_supplier
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
$total_amount = 0;

for ($i=0; $i<$qry_items->RowCount(); $i++) {
$item_total = 0;
$item_total = ($qry_items->FieldByName('quantity') * $qry_items->FieldByName('price'));

$bprice = getBuyingPrice($qry_items->FieldByName('product_id'), $qry_items->FieldByName('batch_id'));

$tmp_str = StuffWithCharacter($qry_items->FieldByName('product_code'), ' ', 10)." ".
StuffWithCharacter($qry_items->FieldByName('batch_code'), ' ', 8)." ".
addslashes(PadWithCharacter($qry_items->FieldByName('product_description'), ' ', 23))." ".
StuffWithCharacter($qry_items->FieldByName('quantity'), ' ', 5)." ".
StuffWithCharacter(sprintf("%01.2f", $bprice), ' ', 8)." ".
StuffWithCharacter(sprintf("%01.2f", $qry_items->FieldByName('price')), ' ', 8)." ".
StuffWithCharacter(sprintf("%01.2f", $item_total), ' ', 8);

$total_amount += $qry_items->FieldByName('price') * $qry_items->FieldByName('quantity');

$str_bill_items = $str_bill_items."
".$tmp_str;

$flt_bprice_total += $bprice * $qry_items->FieldByName('quantity');

$qry_items->next();
}

$str_user = "User: ".$qry->FieldByName('username');

$int_spaces = BILL_WIDTH - 8 - 18 - (strlen(sprintf("%01.2f", $flt_bprice_total)));
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$int_spaces2 = 9 - strlen(sprintf("%01.2f", $total_amount));
$str_spaces2 = PadWithCharacter($str_spaces2, ' ', $int_spaces2);
$str_total = "Totals: ".$str_spaces.sprintf("%01.2f", $flt_bprice_total).$str_spaces2.sprintf("%01.2f", $total_amount);


if ($qry->FieldByName('discount') > 0) {
$flt_discount = ($total_amount * ($qry->FieldByName('discount')/100));

$int_spaces = BILL_WIDTH - (13 + strlen(number_format($qry->FieldByName('discount'),0,'.','')) + strlen(number_format($flt_discount, 2, '.', '')));
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$str_promotion = $str_spaces."Discount ".number_format($qry->FieldByName('discount'),0,'.','')."% : ".number_format($flt_discount, 2, '.', '');

$flt_grandtotal = ($total_amount * (1 - $qry->FieldByName('discount')/100));
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
	//printerForm.submit();
	//setTimeout("window.close();",2000);
</script>

<?
if (isset($qry))
	$qry->Free();
if (isset($qry_items))
	$qry_items->Free();
?>
</body>
</html>