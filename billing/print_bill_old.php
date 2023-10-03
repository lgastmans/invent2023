<?

  require_once("../include/db.inc.php");
  require_once("../include/const.inc.php");
  require_once("../include/session.inc.php");
  require_once("../include/browser_detection.php");
	require_once("../common/tax.php");

	if (IsSet($_GET["id"])) {
		$sql_bill = new Query("
			SELECT b.*, ac.account_number, ac.account_name
			FROM ".Monthalize("bill")." b
				LEFT JOIN account_cc ac ON (ac.cc_id = b.CC_id)
			WHERE (bill_id=".$_GET["id"].")
		");

		$sql_items = new Query("
			SELECT bi.*, sp.product_code, sp.product_description, sb.batch_code, st.tax_description
			FROM ".Monthalize("bill_items")." bi
				INNER JOIN stock_product sp ON (sp.product_id = bi.product_id)
				INNER JOIN ".Yearalize('stock_batch')." sb ON (sb.batch_id = bi.batch_id)
				LEFT JOIN ".Monthalize('stock_tax')." st ON (bi.tax_id = st.tax_id)
			WHERE (bi.bill_id = ".$sql_bill->FieldByName('bill_id').")
		");

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
<head><TITLE>Printing Bill</TITLE>
<link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>

<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0" onload="CheckTC();">

<?
  require "../common/printer.inc.php";
  
/*
; Data
; ----
; %1 - Bill number
; %2 - Date
*/

$int_spaces = 66 - (6 + strlen($sql_bill->FieldByName('bill_number')) + 6 + 10);
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$str_billnum_date = "Bill: ".$sql_bill->FieldByName('bill_number').$str_spaces."Date: ".FormatDate($sql_bill->FieldByName('date_created'));
$str_billnum_date .= "     ".$str_billnum_date;

$int_spaces = 66 - strlen($str_application_title);
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$str_app_print = $str_application_title.$str_spaces."     ".$str_application_title;

if ($sql_bill->FieldByName('payment_type') == BILL_CASH) {
$str_bill_header=
"%c
$str_app_print
$str_billnum_date";
}
else if ($sql_bill->FieldByName('payment_type') == BILL_ACCOUNT) {
$str_bill_header=
"%c
$str_application_title
".$sql_bill->FieldByName('account_number')." - ".$sql_bill->FieldByName('account_name')."
$str_billnum_date";
}
else if ($sql_bill->FieldByName('payment_type') == BILL_CREDIT_CARD) {
$str_bill_header=
"%c
$str_application_title
".$sql_bill->FieldByName('payment_type_number')."
$str_billnum_date";
}
else if ($sql_bill->FieldByName('payment_type') == BILL_CHEQUE) {
$str_bill_header=
"%c
$str_application_title
".$sql_bill->FieldByName('payment_type_number')."
$str_billnum_date";
}

/* Number of characters for 
Code				6
Batch				6
Description		25
Qty				8
Discount			3
Price				8
Tax				5
Total				8
*/

$str_bill_header .="
==================================================================     ==================================================================
  Code    Batch Description               Qty Dt%   Price    Total       Code    Batch Description               Qty Dt%   Price    Total
------------------------------------------------------------------     ------------------------------------------------------------------";

$str_bill_items="";

for ($i=0; $i<$sql_items->RowCount(); $i++) {

	$item_total = 0;

	$calculate_tax = $is_taxed;
	// calculate the tax and the total cost per item billed
	if ($is_taxed == 'Y') {
		if ($sql_bill->FieldByName('payment_type') == BILL_CASH) {
			if ($is_cash_taxed == 'Y')
				$calculate_tax = 'Y';
			else
				$calculate_tax = 'N';
		}
		else if ($sql_bill->FieldByName('payment_type') == BILL_ACCOUNT) {
			if ($is_account_taxed == 'Y')
				$calculate_tax = 'Y';
			else
				$calculate_tax = 'N';
		}
	}
	else
		$calculate_tax = 'N';

	if ($calculate_tax == 'Y') {
		if ($sql_items->FieldByName('discount') > 0) {
			$discount_price = $sql_items->FieldByName('price') * (1 - ($sql_items->FieldByName('discount')/100));
			$tax_amount = calculateTax($discount_price, $sql_items->FieldByName('tax_id'));
			$item_total = ($sql_items->FieldByName('quantity') * ($discount_price + $tax_amount));
		}
		else {
			$tax_amount = calculateTax($sql_items->FieldByName('price'), $sql_items->FieldByName('tax_id'));
			$item_total = ($sql_items->FieldByName('quantity') * ($sql_items->FieldByName('price') + $tax_amount));
		}
	}
	else {
		$tax_amount = 0;
		if ($sql_items->FieldByName('discount') > 0) {
			$discount_price = $sql_items->FieldByName('price') * (1 - ($sql_items->FieldByName('discount')/100));
			$item_total = ($sql_items->FieldByName('quantity') * $discount_price);
		}
		else {
			$item_total = ($sql_items->FieldByName('quantity') * $sql_items->FieldByName('price'));
		}
	}

$tmp_str = StuffWithCharacter($sql_items->FieldByName('product_code'), ' ', 6)." ".
StuffWithCharacter($sql_items->FieldByName('batch_code'), ' ', 8)." ".
PadWithCharacter($sql_items->FieldByName('product_description'), ' ', 23)." ".
StuffWithCharacter($sql_items->FieldByName('quantity'), ' ', 5)." ".
StuffWithCharacter($sql_items->FieldByName('discount'), ' ', 2)."%".
StuffWithCharacter(sprintf("%01.2f", $sql_items->FieldByName('price')), ' ', 8)." ".
StuffWithCharacter(sprintf("%01.2f", $item_total), ' ', 8);

$str_bill_items = $str_bill_items."
".$tmp_str."     ".$tmp_str;

$sql_items->next();
}

$int_spaces = 66 - (7 + strlen(sprintf("%01.2f", $sql_bill->FieldByName('total_amount'))));
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces,' ', $int_spaces);
$str_total = $str_spaces."Total: ".sprintf("%01.2f", $sql_bill->FieldByName('total_amount'))."     ".$str_spaces."Total: ".sprintf("%01.2f", $sql_bill->FieldByName('total_amount'));


if ($sql_bill->FieldByName('bill_promotion') > 0) {

$int_spaces = 66 - (17 + strlen(sprintf("%01.2f", $sql_bill->FieldByName('bill_promotion'))));
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$str_promotion = $str_spaces."Sales promotion: ".sprintf("%01.2f", $sql_bill->FieldByName('bill_promotion'))."     ".$str_spaces."Sales promotion: ".sprintf("%01.2f", $sql_bill->FieldByName('bill_promotion'));

$flt_grandtotal = ($sql_bill->FieldByName('total_amount') - $sql_bill->FieldByName('bill_promotion')); 
$int_spaces = 66 - (13 + strlen(sprintf("%01.2f", $flt_grandtotal)));
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$str_grandtotal = $str_spaces."Grand total: ".sprintf("%01.2f", $flt_grandtotal)."     ".$str_spaces."Grand total: ".sprintf("%01.2f", $flt_grandtotal);

$str_bill_footer="
==================================================================     ==================================================================
".$str_total."
".$str_promotion."
".$str_grandtotal;
}

else {
$str_bill_footer="
==================================================================     ==================================================================
".$str_total;
}

if ($sql_bill->FieldByName('payment_type') == BILL_CASH) {
$str_tax = "Inclusive of sales tax";
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', 44);
$str_tax = "
".$str_tax.$str_spaces."     ".$str_tax;
}
else
$str_tax = "";

$str_bill = $str_bill_header.$str_bill_items.$str_bill_footer.$str_tax."\n\n\n\n\n\n\n\n\n\n\n\n%n";

$str_bill = replaceSpecialCharacters($str_bill);

?>
<form name="printerForm" onsubmit="return false;">

<table width="100%" bgcolor="#E0E0E0"><TR><TD height=45 class="headerText" bgcolor="#808080">&nbsp;<font class='title'>Printing Payment</font></TD></TR>
<tr>
<TD>
<br>
<input type="hidden" name="output" value="<? echo htmlentities($str_bill); ?>"><br>
<table border=0>
<Td class='normaltext'><textarea name='printerStatus' height=5 rows=5 cols=40 class='editbox' ></textarea></td></TR>
<TR><td align='center'><br> <input type='button' onclick="window.close();" name='doaction' value="Close"></td></TR>
</table>
</td></tr>
</table>

</form>
<script language="JavaScript">
 writedata();
</script>
<?


if (isset($sql_bill)) 
	$sql_bill->Free();
if (isset($sql_items)) 
	$sql_items->Free();

?>
</body>
</html>