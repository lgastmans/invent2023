<?

	require_once("../include/db.inc.php");
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/browser_detection.php");
	require_once("../common/tax.php");
	require_once("../common/printer.inc.php");
	require_once("../common/account.php");
  

	if (IsSet($_GET["id"])) {
		$sql_bill = new Query("
			SELECT 
				b.*, 
				u.username,
				u2.username AS cancelled_username,
				ac.account_balance
			FROM ".Monthalize("bill")." b
				LEFT JOIN user u ON (u.user_id = b.user_id)
				LEFT JOIN user u2 ON (u2.user_id = b.cancelled_user_id)
				LEFT JOIN account_cc ac ON (ac.cc_id = b.CC_id)
			WHERE (bill_id=".$_GET["id"].")
		");

		$sql_items = new Query("
			SELECT SUM(bi.quantity) AS quantity, SUM(bi.adjusted_quantity) AS adjusted_quantity,
				bi.discount, bi.price, bi.tax_id,
				sp.product_code, sp.product_description,
				sb.batch_code,
				st.tax_description,
				sup.supplier_abbreviation
			FROM ".Monthalize("bill_items")." bi
				INNER JOIN stock_product sp ON (sp.product_id = bi.product_id)
				INNER JOIN ".Yearalize('stock_batch')." sb ON (sb.batch_id = bi.batch_id)
				LEFT JOIN stock_supplier sup ON (sup.supplier_id = sp.supplier_id)
				LEFT JOIN ".Monthalize('stock_tax')." st ON (bi.tax_id = st.tax_id)
			WHERE (bi.bill_id = ".$sql_bill->FieldByName('bill_id').")
			GROUP BY product_code
			ORDER BY product_code
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
		
		$sql_settings = new Query("
			SELECT *
			FROM user_settings
			WHERE storeroom_id = ".$_SESSION["int_current_storeroom"]."
		");
		$int_eject_lines = 12;
		$str_note = "";
		if ($sql_settings->RowCount() > 0) {
			$int_eject_lines = $sql_settings->FieldByName('bill_print_lines_to_eject');
			$str_tmp = $sql_settings->FieldByName('bill_print_note');
			$str_note_2 = $sql_settings->FieldByName('bill_print_note_2');
			$str_note_3 = $sql_settings->FieldByName('bill_print_note_3');
			$str_print_address = $sql_settings->FieldByName('bill_print_address');
			$str_print_phone = $sql_settings->FieldByName('bill_print_phone');
			$str_print_batch = $sql_settings->FieldByName('bill_print_batch');
			$str_print_abbreviation = $sql_settings->FieldByName('bill_print_supplier_abbreviation');
			$str_last_loadall = makeHumanTime($sql_settings->FieldByName('admin_last_loadall'));
	
			if ($sql_bill->FieldByName('payment_type') == BILL_PT_ACCOUNT) {
				if ($str_print_batch == 'Y')
					define('BILL_WIDTH', 45);
				else
					define('BILL_WIDTH', 36);
			}
			else {
				if ($str_print_batch == 'Y')
					if ((($sql_bill->FieldByName('payment_type') == BILL_ACCOUNT) && ($is_account_taxed == 'Y')) || (($sql_bill->FieldByName('payment_type') == BILL_CASH) && ($is_cash_taxed == 'Y')) || (($sql_bill->FieldByName('payment_type') == BILL_TRANSFER_GOOD) && ($is_cash_taxed == 'Y')))
						define('BILL_WIDTH', 70);
					else
						define('BILL_WIDTH', 66);
				else
					if ((($sql_bill->FieldByName('payment_type') == BILL_ACCOUNT) && ($is_account_taxed == 'Y')) || (($sql_bill->FieldByName('payment_type') == BILL_CASH) && ($is_cash_taxed == 'Y')) || (($sql_bill->FieldByName('payment_type') == BILL_TRANSFER_GOOD) && ($is_cash_taxed == 'Y')))
						define('BILL_WIDTH', 62); // was 61
					else
						define('BILL_WIDTH', 57);
			}
	
			$int_spaces = BILL_WIDTH - strlen($str_tmp);
			$str_spaces = "";
			$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
			
			$int_spaces = BILL_WIDTH - strlen($str_note_2);
			$str_spaces_2 = "";
			$str_spaces_2 = PadWithCharacter($str_spaces, ' ', $int_spaces);
			
			$int_spaces = BILL_WIDTH - strlen($str_note_3);
			$str_spaces_3 = "";
			$str_spaces_3 = PadWithCharacter($str_spaces, ' ', $int_spaces);

			$str_note = "
".$str_tmp.$str_spaces."     ".$str_tmp;

			if (!empty($str_note_2))
				$str_note .= "\n".$str_note_2.$str_spaces_2."     ".$str_note_2;

			if (!empty($str_note_3))
				$str_note .= "\n".$str_note_3.$str_spaces_3."     ".$str_note_3;
		}
		}
?>
<html>
<head><TITLE>Printing Bill</TITLE>
<link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>

<? if (browser_detection( 'os' ) === 'lin') { ?>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0">
<? } else { ?>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0" onload="CheckTC()">
<? } ?>

<?
/*
; Data
; ----
; %1 - Bill number
; %2 - Date
*/

if ($sql_bill->FieldByName('bill_status') == BILL_STATUS_CANCELLED) {
$int_spaces = BILL_WIDTH - strlen("CANCELLED");
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$str_bill_status = "CANCELLED".$str_spaces."     CANCELLED";
}
else
$str_bill_status = '';


if ($sql_bill->FieldByName('payment_type') == BILL_TRANSFER_GOOD)
	$int_spaces = BILL_WIDTH - (10 + strlen(trim($sql_bill->FieldByName('bill_number'))) + 6 + 10);
else
	$int_spaces = BILL_WIDTH - (6 + strlen(trim($sql_bill->FieldByName('bill_number'))) + 6 + 10);
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
if ($sql_bill->FieldByName('payment_type') == BILL_TRANSFER_GOOD)
	$str_billnum_date = "Transfer: ".trim($sql_bill->FieldByName('bill_number')).$str_spaces."Date: ".FormatDate($sql_bill->FieldByName('date_created'));
else
	$str_billnum_date = "Bill: ".trim($sql_bill->FieldByName('bill_number')).$str_spaces."Date: ".FormatDate($sql_bill->FieldByName('date_created'));
$str_billnum_date .= "     ".$str_billnum_date;

$int_spaces = BILL_WIDTH - strlen($str_application_title);
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$str_app_print = $str_application_title.$str_spaces."     ".$str_application_title;
if (IsSet($str_application_title2)) {
	$int_spaces = BILL_WIDTH - strlen($str_application_title2);
	$str_spaces = "";
	$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
	$str_app_print .= "\n".$str_application_title2.$str_spaces."     ".$str_application_title2;
}

$int_spaces = BILL_WIDTH - strlen($str_print_address);
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$str_address_print = $str_print_address.$str_spaces."     ".$str_print_address;

$int_spaces = BILL_WIDTH - strlen($str_print_phone);
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$str_phone_print = $str_print_phone.$str_spaces."     ".$str_print_phone;

if ($sql_bill->FieldByName('payment_type') == BILL_CASH) {
if ($sql_bill->FieldByName('bill_status') == BILL_STATUS_CANCELLED) {
$str_bill_header=
"%c
$str_bill_status
$str_app_print
$str_address_print
$str_phone_print
$str_billnum_date";
}
else {
$str_bill_header=
"%c
$str_app_print
$str_address_print
$str_phone_print
$str_billnum_date";
}
}
else if (($sql_bill->FieldByName('payment_type') == BILL_ACCOUNT) || ($sql_bill->FieldByName('payment_type') == BILL_TRANSFER_GOOD)) {
$str_account = $sql_bill->FieldByName('account_number')." - ".$sql_bill->FieldByName('account_name');
$int_spaces = BILL_WIDTH - strlen($str_account);
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$str_account .= $str_spaces."     ".$str_account;
if ($sql_bill->FieldByName('bill_status') == BILL_STATUS_CANCELLED) {
$str_bill_header=
"%c
$str_bill_status
$str_app_print
$str_address_print
$str_phone_print
$str_account
$str_billnum_date";
}
else {
$str_bill_header=
"%c
$str_app_print
$str_address_print
$str_phone_print
$str_account
$str_billnum_date";
}
}
else if ($sql_bill->FieldByName('payment_type') == BILL_PT_ACCOUNT) {
$str_account = $sql_bill->FieldByName('account_number')." - ".$sql_bill->FieldByName('account_name');
$int_spaces = BILL_WIDTH - strlen($str_account);
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$str_account .= $str_spaces."     ".$str_account;
if ($sql_bill->FieldByName('bill_status') == BILL_STATUS_CANCELLED) {
$str_bill_header=
"%n%c
$str_bill_status
$str_app_print
$str_address_print
$str_phone_print
$str_account
$str_billnum_date";
}
else {
$str_bill_header=
"%n%c
$str_app_print
$str_address_print
$str_phone_print
$str_account
$str_billnum_date";
}
}
else if ($sql_bill->FieldByName('payment_type') == BILL_CREDIT_CARD) {
$str_bill_header=
"%c
$str_app_print
$str_address_print
$str_phone_print
".$sql_bill->FieldByName('payment_type_number')."
$str_billnum_date";
}
else if ($sql_bill->FieldByName('payment_type') == BILL_CHEQUE) {
$str_bill_header=
"%c
$str_app_print
$str_address_print
$str_phone_print
".$sql_bill->FieldByName('payment_type_number')."
$str_billnum_date";
}

/* Number of characters for 
Code				6
Batch				8
Description			25
Qty				8
Discount			3
Price				8
Tax				5
Total				8
*/

$print_tax = 'N';
if (
	(($sql_bill->FieldByName('payment_type') == BILL_ACCOUNT) && ($is_account_taxed == 'Y'))
	|| (($sql_bill->FieldByName('payment_type') == BILL_CASH) && ($is_cash_taxed == 'Y'))
	|| (($sql_bill->FieldByName('payment_type') == BILL_TRANSFER_GOOD) && ($is_cash_taxed == 'Y'))
	)
	$print_tax = 'Y';

if ($sql_bill->FieldByName('payment_type') == BILL_PT_ACCOUNT) {
if ($str_print_batch == 'Y') {
$str_bill_header .="
=============================================     =============================================
  Code    Batch Description               Qty       Code    Batch Description               Qty
---------------------------------------------     ---------------------------------------------";
}
else {
$str_bill_header .="
====================================     ====================================
  Code Description               Qty       Code Description               Qty
------------------------------------     ------------------------------------";
}
}
else {
if ($str_print_batch == 'Y') {
	if ($print_tax == 'Y')
$str_bill_header .="
=======================================================================     =======================================================================
  Code    Batch Description               Qty Dt%   Price  Tax    Total       Code    Batch Description               Qty Dt%   Price  Tax    Total
-----------------------------------------------------------------------     -----------------------------------------------------------------------";
	else
$str_bill_header .="
==================================================================     ==================================================================
  Code    Batch Description               Qty Dt%   Price    Total       Code    Batch Description               Qty Dt%   Price    Total
------------------------------------------------------------------     ------------------------------------------------------------------";
}
else {
	if ($print_tax == 'Y')
$str_bill_header .="
==============================================================     ==============================================================
  Code Description               Qty Dt%   Price  Tax    Total       Code Description               Qty Dt%   Price  Tax    Total
--------------------------------------------------------------     --------------------------------------------------------------";
	else
$str_bill_header .="
=========================================================     =========================================================
  Code Description               Qty Dt%   Price    Total       Code Description               Qty Dt%   Price    Total
---------------------------------------------------------     ---------------------------------------------------------";
}
}


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

	$total_quantity = $sql_items->FieldByName('quantity') + $sql_items->FieldByName('adjusted_quantity');
  
	if ($calculate_tax == 'Y') {
		if ($sql_items->FieldByName('discount') > 0) {
			$discount_price = $sql_items->FieldByName('price') * (1 - ($sql_items->FieldByName('discount')/100));
			$tax_amount = calculateTax($discount_price, $sql_items->FieldByName('tax_id'));
			$item_total = ($total_quantity * ($discount_price + $tax_amount));
		}
		else {
			$tax_amount = calculateTax($sql_items->FieldByName('price'), $sql_items->FieldByName('tax_id'));
			$item_total = ($total_quantity * ($sql_items->FieldByName('price') + $tax_amount));
		}
	}
	else {
		$tax_amount = 0;
		if ($sql_items->FieldByName('discount') > 0) {
			$discount_price = $sql_items->FieldByName('price') * (1 - ($sql_items->FieldByName('discount')/100));
			$item_total = ($total_quantity * $discount_price);
		}
		else {
			$item_total = ($total_quantity * $sql_items->FieldByName('price'));
		}
	}

	$int_discount_amount = intval($sql_items->FieldByName('discount'));
	if ($int_discount_amount == 0)
		$str_discount = '';
	else
		$str_discount = $int_discount_amount."%";


if ($sql_bill->FieldByName('payment_type') == BILL_PT_ACCOUNT) {
	
if ($str_print_batch == 'Y') {
	if ($str_print_abbreviation == 'Y')
		$str_product_description = PadWithCharacter($sql_items->FieldByName('product_description'), ' ', 17)." ".PadWithCharacter($sql_items->FieldByName('supplier_abbreviation'), ' ', 5);
	else
		$str_product_description = PadWithCharacter($sql_items->FieldByName('product_description'), ' ', 23);
$tmp_str = StuffWithCharacter($sql_items->FieldByName('product_code'), ' ', 6)." ".
StuffWithCharacter($sql_items->FieldByName('batch_code'), ' ', 8)." ".
PadWithCharacter($sql_items->FieldByName('product_description'), ' ', 23)." ".
StuffWithCharacter($total_quantity, ' ', 5);

} else {
	
	if ($str_print_abbreviation == 'Y')
		$str_product_description = PadWithCharacter($sql_items->FieldByName('product_description'), ' ', 17)." ".PadWithCharacter($sql_items->FieldByName('supplier_abbreviation'), ' ', 5);
	else
		$str_product_description = PadWithCharacter($sql_items->FieldByName('product_description'), ' ', 23);
$tmp_str = StuffWithCharacter($sql_items->FieldByName('product_code'), ' ', 6)." ".
PadWithCharacter($sql_items->FieldByName('product_description'), ' ', 23)." ".
StuffWithCharacter($total_quantity, ' ', 5);

}} // end BILL_PT_ACCOUNT

else {
if ($str_print_batch == 'Y') {
	if ($print_tax == 'Y') {
		if ($str_print_abbreviation == 'Y')
			$str_product_description = PadWithCharacter($sql_items->FieldByName('product_description'), ' ', 17)." ".PadWithCharacter($sql_items->FieldByName('supplier_abbreviation'), ' ', 5);
		else
			$str_product_description = PadWithCharacter($sql_items->FieldByName('product_description'), ' ', 23);
$tmp_str = StuffWithCharacter($sql_items->FieldByName('product_code'), ' ', 6)." ".
StuffWithCharacter($sql_items->FieldByName('batch_code'), ' ', 8)." ".
$str_product_description." ".
StuffWithCharacter($total_quantity, ' ', 5)." ".
StuffWithCharacter($str_discount, ' ', 3)."".
StuffWithCharacter(sprintf("%01.2f", $sql_items->FieldByName('price')), ' ', 8)." ".
StuffWithCharacter($sql_items->FieldByName('tax_description'), ' ', 4)." ".
StuffWithCharacter(sprintf("%01.2f", $item_total), ' ', 8);
	} else {
		if ($str_print_abbreviation == 'Y')
			$str_product_description = PadWithCharacter($sql_items->FieldByName('product_description'), ' ', 17)." ".PadWithCharacter($sql_items->FieldByName('supplier_abbreviation'), ' ', 5);
		else
			$str_product_description = PadWithCharacter($sql_items->FieldByName('product_description'), ' ', 23);
$tmp_str = StuffWithCharacter($sql_items->FieldByName('product_code'), ' ', 6)." ".
StuffWithCharacter($sql_items->FieldByName('batch_code'), ' ', 8)." ".
$str_product_description." ".
StuffWithCharacter($total_quantity, ' ', 5)." ".
StuffWithCharacter($str_discount, ' ', 3)."".
StuffWithCharacter(sprintf("%01.2f", $sql_items->FieldByName('price')), ' ', 8)." ".
StuffWithCharacter(sprintf("%01.2f", $item_total), ' ', 8);
	}
} else {
	if ($print_tax == 'Y') {
		if ($str_print_abbreviation == 'Y')
			$str_product_description = PadWithCharacter($sql_items->FieldByName('product_description'), ' ', 17)." ".PadWithCharacter($sql_items->FieldByName('supplier_abbreviation'), ' ', 5);
		else
			$str_product_description = PadWithCharacter($sql_items->FieldByName('product_description'), ' ', 23);
$tmp_str = StuffWithCharacter($sql_items->FieldByName('product_code'), ' ', 6)." ".
$str_product_description." ".
StuffWithCharacter($total_quantity, ' ', 5)." ".
StuffWithCharacter($str_discount, ' ', 3)."".
StuffWithCharacter(sprintf("%01.2f", $sql_items->FieldByName('price')), ' ', 8)." ".
StuffWithCharacter($sql_items->FieldByName('tax_description'), ' ', 4)." ".
StuffWithCharacter(sprintf("%01.2f", $item_total), ' ', 8);
	} else {
		if ($str_print_abbreviation == 'Y')
			$str_product_description = PadWithCharacter($sql_items->FieldByName('product_description'), ' ', 17)." ".PadWithCharacter($sql_items->FieldByName('supplier_abbreviation'), ' ', 5);
		else
			$str_product_description = PadWithCharacter($sql_items->FieldByName('product_description'), ' ', 23);
$tmp_str = StuffWithCharacter($sql_items->FieldByName('product_code'), ' ', 6)." ".
$str_product_description." ".
StuffWithCharacter($total_quantity, ' ', 5)." ".
StuffWithCharacter($str_discount, ' ', 3)."".
StuffWithCharacter(sprintf("%01.2f", $sql_items->FieldByName('price')), ' ', 8)." ".
StuffWithCharacter(sprintf("%01.2f", $item_total), ' ', 8);
	}}
}

$str_bill_items = $str_bill_items."
".$tmp_str."     ".$tmp_str;

$sql_items->next();
}

$str_user = "User: ".$sql_bill->FieldByName('username');
$int_spaces = BILL_WIDTH - (strlen($str_user) + 7 + strlen(sprintf("%01.2f", $sql_bill->FieldByName('total_amount'))));
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces,' ', $int_spaces);
$str_total = $str_user.$str_spaces."Total: ".sprintf("%01.2f", $sql_bill->FieldByName('total_amount'))."     ".$str_user.$str_spaces."Total: ".sprintf("%01.2f", $sql_bill->FieldByName('total_amount'))."\n";

if (($sql_bill->FieldByName('payment_type') == BILL_ACCOUNT) || ($sql_bill->FieldByName('payment_type') == BILL_TRANSFER_GOOD)) {
	$str_retval = get_account_status($sql_bill->FieldByName('account_number'));
	$arr_retval = explode('|',$str_retval);
	$str_balance = 'unknown';
	if ($arr_retval[0] == 'OK') {
		$str_balance = "Balance as of ".$str_last_loadall." : Rs. ".$arr_retval[2];
		$int_spaces = BILL_WIDTH - strlen($str_balance);
		$str_spaces = '';
		$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
		$str_balance = $str_balance.$str_spaces."     ".$str_balance."\n";
	}
}

if ($sql_bill->FieldByName('bill_promotion') > 0) {

$int_spaces = BILL_WIDTH - (17 + strlen(sprintf("%01.2f", $sql_bill->FieldByName('bill_promotion'))));
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$str_promotion = $str_spaces."Sales promotion: ".sprintf("%01.2f", $sql_bill->FieldByName('bill_promotion'))."     ".$str_spaces."Sales promotion: ".sprintf("%01.2f", $sql_bill->FieldByName('bill_promotion'));

$flt_grandtotal = ($sql_bill->FieldByName('total_amount') - $sql_bill->FieldByName('bill_promotion')); 
$int_spaces = BILL_WIDTH - (13 + strlen(sprintf("%01.2f", $flt_grandtotal)));
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$str_grandtotal = $str_spaces."Grand total: ".sprintf("%01.2f", $flt_grandtotal)."     ".$str_spaces."Grand total: ".sprintf("%01.2f", $flt_grandtotal);

if ($sql_bill->FieldByName('payment_type') == BILL_PT_ACCOUNT) {
if ($str_print_batch== 'Y')
$str_bill_footer="
=============================================     =============================================
".$str_total."
".$str_promotion."
".$str_grandtotal;
else
$str_bill_footer="
====================================     ====================================
".$str_total."
".$str_promotion."
".$str_grandtotal;
}
else {
if ($str_print_batch == 'Y')
	if ($print_tax == 'Y')
$str_bill_footer="
=======================================================================     =======================================================================
".$str_total."
".$str_promotion."
".$str_grandtotal;
	else
$str_bill_footer="
==================================================================     ==================================================================
".$str_total."
".$str_promotion."
".$str_grandtotal;
else
	if ($print_tax == 'Y')
$str_bill_footer="
==============================================================     ==============================================================
".$str_total."
".$str_promotion."
".$str_grandtotal;
	else
$str_bill_footer="
=========================================================     =========================================================
".$str_total."
".$str_promotion."
".$str_grandtotal;
}
}

else {
if ($sql_bill->FieldByName('payment_type') == BILL_PT_ACCOUNT) {
if ($str_print_batch == 'Y')
$str_bill_footer="
=============================================     =============================================
".$str_total;
else
$str_bill_footer="
====================================     ====================================
".$str_total;
}
else {
if ($str_print_batch == 'Y')
$str_bill_footer="
==================================================================     ==================================================================
".$str_total;
else
	if ($print_tax == 'Y')
$str_bill_footer="
==============================================================     ==============================================================
".$str_total;
	else
$str_bill_footer="
=========================================================     =========================================================
".$str_total;
}
}

if ($sql_bill->FieldByName('payment_type') == BILL_CASH) {
$str_tax = "Inclusive of sales tax";
$int_spaces = BILL_WIDTH - strlen($str_tax);
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
$str_tax = "
".$str_tax.$str_spaces."     ".$str_tax;
}
else
$str_tax = "";

$str_eject_lines = "";
for ($i=0;$i<$int_eject_lines;$i++) {
  $str_eject_lines .= "\n"; 
}
$str_bill = $str_bill_header.$str_bill_items.$str_bill_footer.$str_tax.$str_balance.$str_note.$str_eject_lines."%n";

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

<?


if (isset($sql_bill)) 
	$sql_bill->Free();
if (isset($sql_items)) 
	$sql_items->Free();

?>
</body>
</html>