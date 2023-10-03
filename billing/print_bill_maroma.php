<?
	
	require_once("../include/db.inc.php");
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/browser_detection.php");
	require_once("../common/tax.php");
	require_once("../common/printer.inc.php");
	require_once("../common/account.php");
	
	$copies = $arr_invent_config['billing']['print_copies'];
	
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
		$str_print_header = $sql_settings->FieldByName('bill_print_header');
		$str_bill_header_text = $sql_settings->FieldByName('bill_header');
		$str_print_tax_totals = $sql_settings->FieldByName('bill_print_tax_totals');
		$str_calc_tax_first = $sql_settings->FieldByName('calculate_tax_before_discount');
	}
	
	
	function getColumn($arr_dest, $int_definition_id) {
		$int_retval = -1;
		for ($i=0; $i<count($arr_dest); $i++) {
			if ($arr_dest[$i][0] === $int_definition_id) {
				$int_retval = $i;
				break;
			}
		}
		return $int_retval;
	}
	
	// get all taxes that are not "surcharge"
	$qry_tax_headers = new Query("
		SELECT *
		FROM ".Monthalize('stock_tax_definition')."
		WHERE definition_type <> 2
		ORDER BY definition_type, definition_percent
	");
	$arr_taxes = array();
	// add four columns to the header, after the taxes: Sales, Taxes, Round and Total
	if ($qry_tax_headers->RowCount() > 0) {
		for ($i=0; $i<$qry_tax_headers->RowCount(); $i++) {
			if ($qry_tax_headers->FieldByName('definition_percent') > 0) {
				unset($arr_tmp);
				$arr_tmp[] = $qry_tax_headers->FieldByName('definition_id');
				$arr_tmp[] = $qry_tax_headers->FieldByName('definition_percent');
				$arr_tmp[] = 0.0;
				$arr_taxes[] = $arr_tmp;
			}
			$qry_tax_headers->Next();
		}
	}

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
		
		if ($str_print_batch == 'Y') {
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
				GROUP BY product_code, bi.price
				ORDER BY bi.bill_item_id
			");
		}
		else {
			$str = "
				SELECT SUM(bi.quantity) AS quantity, SUM(bi.adjusted_quantity) AS adjusted_quantity,
					bi.discount, bi.price, bi.tax_id,
					sp.product_code, sp.product_description,
					sb.batch_code,
					st.tax_description,
					sup.supplier_abbreviation
				FROM ".Monthalize("bill_items")." bi
					INNER JOIN stock_product sp ON (sp.product_id = bi.product_id)
					LEFT JOIN ".Yearalize('stock_batch')." sb ON (sb.batch_id = bi.batch_id)
					LEFT JOIN stock_supplier sup ON (sup.supplier_id = sp.supplier_id)
					LEFT JOIN ".Monthalize('stock_tax')." st ON (bi.tax_id = st.tax_id)
				WHERE (bi.bill_id = ".$sql_bill->FieldByName('bill_id').")
				GROUP BY product_code
				ORDER BY bi.bill_item_id
			";
			$sql_items = new Query($str);
		}
		echo $str;
		// get the tax details for the storeroom
		$result_set = new Query("
			SELECT is_taxed, is_cash_taxed, is_account_taxed, bill_credit_account
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
			$str_credit_account = $result_set->FieldByName('bill_credit_account');
		}
		
		if (($sql_bill->FieldByName('payment_type') == BILL_PT_ACCOUNT) ) {
			if ($str_print_batch == 'Y')
				define('BILL_WIDTH', 45);
			else
				define('BILL_WIDTH', 36);
		}
		else {
			if ($str_print_batch == 'Y')
				if ((($sql_bill->FieldByName('payment_type') == BILL_ACCOUNT) && ($is_account_taxed == 'Y')) || (($sql_bill->FieldByName('payment_type') == BILL_CASH) && ($is_cash_taxed == 'Y')) ||
				(($sql_bill->FieldByName('payment_type') == BILL_AUROCARD) && ($is_account_taxed == 'Y')) || (($sql_bill->FieldByName('payment_type') == BILL_TRANSFER_GOOD) && ($is_cash_taxed == 'Y')))
					define('BILL_WIDTH', 62); // was 70
				else
					define('BILL_WIDTH', 66);
			else
				if ((($sql_bill->FieldByName('payment_type') == BILL_ACCOUNT) && ($is_account_taxed == 'Y')) || (($sql_bill->FieldByName('payment_type') == BILL_CASH) && ($is_cash_taxed == 'Y')) || (($sql_bill->FieldByName('payment_type') == BILL_AUROCARD) && ($is_account_taxed == 'Y')) ||  (($sql_bill->FieldByName('payment_type') == BILL_TRANSFER_GOOD) && ($is_cash_taxed == 'Y')))
					if ($copies == 2)
						define('BILL_WIDTH', 64); //62); // was 61
					else
						define('BILL_WIDTH', 83); // was 61
				else
					define('BILL_WIDTH', 57);
		}
		define('SPACE_BETWEEN', 5);
		$space_between = PadWithBlank($space_between, SPACE_BETWEEN);
		
		$int_spaces = BILL_WIDTH - strlen($str_tmp);
		$str_spaces = "";
		$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
		
		$int_spaces = BILL_WIDTH - strlen($str_note_2);
		$str_spaces_2 = "";
		$str_spaces_2 = PadWithCharacter($str_spaces, ' ', $int_spaces);
		
		$int_spaces = BILL_WIDTH - strlen($str_note_3);
		$str_spaces_3 = "";
		$str_spaces_3 = PadWithCharacter($str_spaces, ' ', $int_spaces);
		
		if ($copies == 2)
			$str_note = "\n".$str_tmp.$str_spaces.$space_between.$str_tmp;
		else
			$str_note = "\n".$str_tmp;
		
		if (!empty($str_note_2))
			if ($copies == 2)
				$str_note .= "\n".$str_note_2.$str_spaces_2.$space_between.$str_note_2;
			else
				$str_note .= "\n".$str_note_2;
		
		if (!empty($str_note_3))
			if ($copies == 2)
				$str_note .= "\n".$str_note_3.$str_spaces_3.$space_between.$str_note_3;
			else
				$str_note .= "\n".$str_note_3;
	}
?>
<html>
<head><TITLE>Printing Bill</TITLE>
	<link href="../include/styles.css" rel="stylesheet" type="text/css">
	<style>
		table td {
			padding:0px;
		}
	</style>
</head>

<? if (browser_detection( 'os' ) === 'lin') { ?>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0">
<? } else { ?>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0" onload="CheckTC()">
<? } ?>

<?
if ($sql_bill->FieldByName('bill_status') == BILL_STATUS_CANCELLED) {
	$int_spaces = BILL_WIDTH - strlen("CANCELLED");
	$str_spaces = "";
	$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
	if ($copies == 2)
		$str_bill_status = "CANCELLED".$str_spaces.$space_between."CANCELLED";
	else
		$str_bill_status = "CANCELLED";
	
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

if ($copies == 2)
	$str_billnum_date .= $space_between.$str_billnum_date;


$int_spaces = BILL_WIDTH - strlen($str_application_title);
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
if ($copies == 2)
	$str_app_print = $str_application_title.$str_spaces.$space_between.$str_application_title;
else
	$str_app_print = $str_application_title;
	
if (IsSet($str_application_title2)) {
	$int_spaces = BILL_WIDTH - strlen($str_application_title2);
	$str_spaces = "";
	$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
	if ($copies == 2)
		$str_app_print .= "\n".$str_application_title2.$str_spaces.$space_between.$str_application_title2;
	else
		$str_app_print .= "\n".$str_application_title2;
}

$int_spaces = BILL_WIDTH - strlen($str_print_address);
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
if ($copies == 2)
	$str_address_print = $str_print_address.$str_spaces.$space_between.$str_print_address;
else
	$str_address_print = $str_print_address;

$int_spaces = BILL_WIDTH - strlen($str_print_phone);
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
if ($copies == 2)
	$str_phone_print = $str_print_phone.$str_spaces.$space_between.$str_print_phone;
else
	$str_phone_print = $str_print_phone;


if ($sql_bill->FieldByName('payment_type') == BILL_CASH) {
	if ($sql_bill->FieldByName('bill_status') == BILL_STATUS_CANCELLED) {
		$str_bill_header="%c\n".
		$str_bill_status."\n".
		$str_app_print."\n".
		$str_address_print."\n".
		$str_phone_print."\n".
		$str_billnum_date;
	}
	else {
		$str_bill_header="%c\n".
		$str_app_print."\n".
		$str_address_print."\n".
		$str_phone_print."\n".
		$str_billnum_date;
	}
}
else if (($sql_bill->FieldByName('payment_type') == BILL_ACCOUNT) ||  ($sql_bill->FieldByName('payment_type') == BILL_TRANSFER_GOOD)) {
	$str_account2 = '';
	if ($sql_bill->FieldByName('is_debit_bill') == 'Y') {
		$str_account =  "CREDIT: ".$sql_bill->FieldByName('account_number')." - ".$sql_bill->FieldByName('account_name');
		$str_account2 .= "DEBIT: ".$str_credit_account;
		$int_spaces = BILL_WIDTH - strlen($str_account2);
		$str_spaces = "";
		$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
		if ($copies == 2)
			$str_account2 = "\n".$str_account2.$str_spaces.$space_between.$str_account2;
		else
			$str_account2 = "\n".$str_account2;
	}
	else
		$str_account = $sql_bill->FieldByName('account_number')." - ".$sql_bill->FieldByName('account_name');
	$int_spaces = BILL_WIDTH - strlen($str_account);
	$str_spaces = "";
	$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
	if ($copies == 2)
		$str_account .= $str_spaces.$space_between.$str_account;
	
	if ($sql_bill->FieldByName('bill_status') == BILL_STATUS_CANCELLED) {
		$str_bill_header="%c\n".
		$str_bill_status."\n".
		$str_app_print."\n".
		$str_address_print."\n".
		$str_phone_print."\n".
		"%b".$str_account.$str_account2."%n%c\n".
		$str_billnum_date;
	}
	else {
		$str_bill_header= "%c\n".
		$str_app_print."\n".
		$str_address_print."\n".
		$str_phone_print."\n".
		"%b".$str_account.$str_account2."%n%c\n".
		$str_billnum_date;
	}
}
else if ($sql_bill->FieldByName('payment_type') == BILL_PT_ACCOUNT) {
	$str_account = $sql_bill->FieldByName('account_number')." - ".$sql_bill->FieldByName('account_name');
	$int_spaces = BILL_WIDTH - strlen($str_account);
	$str_spaces = "";
	$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
	if ($copies == 2)
		$str_account .= $str_spaces.$space_between.$str_account;

	if ($sql_bill->FieldByName('bill_status') == BILL_STATUS_CANCELLED) {
		$str_bill_header="%c\n".
		$str_bill_status."\n".
		$str_app_print."\n".
		$str_address_print."\n".
		$str_phone_print."\n".
		$str_account."\n".
		$str_billnum_date;
	}
	else {
		$str_bill_header="%c\n".
		$str_app_print."\n".
		$str_address_print."\n".
		$str_phone_print."\n".
		$str_account."\n".
		$str_billnum_date;
	}
}
else if ($sql_bill->FieldByName('payment_type') == BILL_AUROCARD) {
	$str_account = "Card No: ".$sql_bill->FieldByName('aurocard_number')." Trans Id: ".$sql_bill->FieldByName('aurocard_transaction_id');
	$int_spaces = BILL_WIDTH - strlen($str_account);
	$str_spaces = "";
	$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
	if ($copies == 2)
		$str_account .= $str_spaces.$space_between.$str_account;
	
	if ($sql_bill->FieldByName('bill_status') == BILL_STATUS_CANCELLED) {
		$str_bill_header="%c\n".
		$str_bill_status."\n".
		$str_app_print."\n".
		$str_address_print."\n".
		$str_phone_print."\n".
		"%b".$str_account.$str_account2."%n%c\n".
		$str_billnum_date;
	}
	else {
		$str_bill_header= "%c\n".
		$str_app_print."\n".
		$str_address_print."\n".
		$str_phone_print."\n".
		"%b".$str_account.$str_account2."%n%c\n".
		$str_billnum_date;
	}
}
else if ($sql_bill->FieldByName('payment_type') == BILL_CREDIT_CARD) {
	$str_bill_header="%c\n".
	$str_app_print."\n".
	$str_address_print."\n".
	$str_phone_print."\n".
	$sql_bill->FieldByName('payment_type_number')."\n".
	$str_billnum_date;
}
else if ($sql_bill->FieldByName('payment_type') == BILL_CHEQUE) {
	$str_bill_header="%c\n".
	$str_app_print."\n".
	$str_address_print."\n".
	$str_phone_print."\n".
	$sql_bill->FieldByName('payment_type_number')."\n".
	$str_billnum_date;
}

if ($copies == 2) {
	define('WIDTH_CODE', 8);
	define('WIDTH_BATCH', 8);
	if ($str_print_abbreviation == 'Y')
		define('WIDTH_DESCR', 17);
	else
		define('WIDTH_DESCR', 23);
	define('WIDTH_QTY', 5);
	define('WIDTH_DISCOUNT', 2);
	define('WIDTH_PRICE', 8);
	define('WIDTH_TAX', 4);
	define('WIDTH_TOTAL', 8);
}
else {
	define('WIDTH_CODE', 15);
	define('WIDTH_BATCH', 8);
	if ($str_print_abbreviation == 'Y')
		define('WIDTH_DESCR', 29);
	else
		define('WIDTH_DESCR', 35);
	define('WIDTH_QTY', 5);
	define('WIDTH_DISCOUNT', 2);
	define('WIDTH_PRICE', 8);
	define('WIDTH_TAX', 4);
	define('WIDTH_TOTAL', 8);
}


$print_tax = 'N';
if (
	(($sql_bill->FieldByName('payment_type') == BILL_ACCOUNT) && ($is_account_taxed == 'Y'))
	|| (($sql_bill->FieldByName('payment_type') == BILL_CASH) && ($is_cash_taxed == 'Y'))
	|| (($sql_bill->FieldByName('payment_type') == BILL_TRANSFER_GOOD) && ($is_cash_taxed == 'Y')) ||
	(($sql_bill->FieldByName('payment_type') == BILL_AUROCARD) && ($is_account_taxed == 'Y'))
	)
	$print_tax = 'Y';

$double_line = PadWithCharacter($double_line, '=', BILL_WIDTH);
$single_line = PadWithCharacter($single_line, '-', BILL_WIDTH);
$col_code = StuffWithBlank('Code', WIDTH_CODE)." ";
$col_batch = PadWithBlank('Batch', WIDTH_BATCH)." ";
$col_descr = PadWithBlank('Description', WIDTH_DESCR)." ";
$col_qty = StuffWithBlank('Qty', WIDTH_QTY)." ";
$col_discount = PadWithBlank('Dt', WIDTH_DISCOUNT)." ";
$col_price = StuffWithBlank('Price', WIDTH_PRICE)." ";
$col_tax = StuffWithBlank('Tax', WIDTH_TAX)." ";
$col_total = StuffWithBlank('Total', WIDTH_TOTAL);


$str_user = "User: ".$sql_bill->FieldByName('username');
$int_spaces = BILL_WIDTH - (strlen($str_user) + 20 + strlen(sprintf("%01.2f", $sql_bill->FieldByName('total_amount'))));
$str_spaces = "";
$str_spaces = PadWithCharacter($str_spaces,' ', $int_spaces);
if ($copies == 2)
	$str_total = $str_user.$str_spaces."Total (rounded 5p): ".sprintf("%01.2f", $sql_bill->FieldByName('total_amount')).$space_between.$str_user.$str_spaces."Total (rounded 5p): ".sprintf("%01.2f", $sql_bill->FieldByName('total_amount'))."\n";
else
	$str_total = $str_user.$str_spaces."Total (rounded 5p): ".sprintf("%01.2f", $sql_bill->FieldByName('total_amount'))."\n";

$str_balance = '';
if (DOWNLOAD_ALL == 1) {
	if (($sql_bill->FieldByName('payment_type') == BILL_ACCOUNT) || ($sql_bill->FieldByName('payment_type') == BILL_TRANSFER_GOOD)) {
		$str_retval = get_account_status($sql_bill->FieldByName('account_number'));
		$arr_retval = explode('|',$str_retval);
		$str_balance = 'unknown';
		if ($arr_retval[0] == 'OK') {
			$str_balance = "Balance as of ".$str_last_loadall." : Rs. ".$arr_retval[2];
			$int_spaces = BILL_WIDTH - strlen($str_balance);
			$str_spaces = '';
			$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
			if ($copies == 2)
				$str_balance = $str_balance.$str_spaces.$space_between.$str_balance."\n";
			else
				$str_balance = $str_balance."\n";
		}
	}
}

if ($sql_bill->FieldByName('bill_promotion') > 0) {

	$int_spaces = BILL_WIDTH - (17 + strlen(sprintf("%01.2f", $sql_bill->FieldByName('bill_promotion'))));
	$str_spaces = "";
	$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
	if ($copies == 2)
		$str_promotion = $str_spaces."Sales promotion: ".sprintf("%01.2f", $sql_bill->FieldByName('bill_promotion')).$space_between.$str_spaces."Sales promotion: ".sprintf("%01.2f", $sql_bill->FieldByName('bill_promotion'));
	else
		$str_promotion = $str_spaces."Sales promotion: ".sprintf("%01.2f", $sql_bill->FieldByName('bill_promotion'));
	
	$flt_grandtotal = ($sql_bill->FieldByName('total_amount') - $sql_bill->FieldByName('bill_promotion')); 
	$int_spaces = BILL_WIDTH - (13 + strlen(sprintf("%01.2f", $flt_grandtotal)));
	$str_spaces = "";
	$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
	if ($copies == 2)
		$str_grandtotal = $str_spaces."Grand total: ".sprintf("%01.2f", $flt_grandtotal).$space_between.$str_spaces."Grand total: ".sprintf("%01.2f", $flt_grandtotal);
	else
		$str_grandtotal = $str_spaces."Grand total: ".sprintf("%01.2f", $flt_grandtotal);
	
	
	if (($sql_bill->FieldByName('payment_type') == BILL_PT_ACCOUNT) ) {
		if ($str_print_batch== 'Y') {
			if ($copies == 2)
				$str_bill_footer="\n".
					$double_line.$space_between.$double_line."\n".
					$str_total."\n".
					$str_promotion."\n".
					$str_grandtotal;
			else
				$str_bill_footer="\n".
					$double_line."\n".
					$str_total."\n".
					$str_promotion."\n".
					$str_grandtotal;
		}
		else {
			if ($copies == 2)
				$str_bill_footer="\n".
					$double_line.$space_between.$double_line."\n".
					$str_total."\n".
					$str_promotion."\n".
					$str_grandtotal;
			else
				$str_bill_footer="\n".
					$double_line."\n".
					$str_total."\n".
					$str_promotion."\n".
					$str_grandtotal;
		}
	}
	else {
		if ($str_print_batch == 'Y') {
			if ($print_tax == 'Y') {
				if ($copies == 2)
					$str_bill_footer="\n".
					$double_line.$space_between.$double_line."\n".
					$str_total."\n".
					$str_promotion."\n".
					$str_grandtotal;
				else
					$str_bill_footer="\n".
					$double_line."\n".
					$str_total."\n".
					$str_promotion."\n".
					$str_grandtotal;
			}
			else {
				if ($copies == 2)
					$str_bill_footer="\n".
					$double_line.$space_between.$double_line."\n".
					$str_total."\n".
					$str_promotion."\n".
					$str_grandtotal;
				else
					$str_bill_footer="\n".
					$double_line."\n".
					$str_total."\n".
					$str_promotion."\n".
					$str_grandtotal;
			}
		}
		else {
			if ($print_tax == 'Y') {
				if ($copies == 2)
					$str_bill_footer="\n".
					$double_line.$space_between.$double_line."\n".
					$str_promotion."\n".
					$str_grandtotal;
				else
					$str_bill_footer="\n".
					$double_line."\n".
					$str_total."\n".
					$str_promotion."\n".
					$str_grandtotal;
			}
			else {
				if ($copies == 2)
					$str_bill_footer="\n".
					$double_line.$space_between.$double_line."\n".
					$str_total."\n".
					$str_promotion."\n".
					$str_grandtotal;
				else
					$str_bill_footer="\n".
					$double_line."\n".
					$str_total."\n".
					$str_promotion."\n".
					$str_grandtotal;
			}
		}
	}
} // this closes "if ($sql_bill->FieldByName('bill_promotion') > 0)"
else {
	if (($sql_bill->FieldByName('payment_type') == BILL_PT_ACCOUNT) ) {
		if ($str_print_batch == 'Y') {
			if ($copies == 2)
				$str_bill_footer="\n".
					$double_line.$space_between.$double_line."\n".
					$str_total;
			else
				$str_bill_footer="\n".
					$double_line."\n".
					$str_total;
		}
		else {
			if ($copies == 2)
				$str_bill_footer="\n".
					$double_line.$space_between.$double_line."\n".
					$str_total;
			else
				$str_bill_footer="\n".
					$double_line."\n".
					$str_total;
		}
	}
	else {
		if ($str_print_batch == 'Y') {
			if ($copies == 2)
				$str_bill_footer="\n".
					$double_line.$space_between.$double_line."\n".
					$str_total;
			else
				$str_bill_footer="\n".
					$double_line."\n".
					$str_total;
		}
		else if ($print_tax == 'Y') {
			if ($copies == 2)
				$str_bill_footer="\n".
					$double_line.$space_between.$double_line."\n".
					$str_total;
			else
				$str_bill_footer="\n".
					$double_line."\n".
					$str_total;
		}
		else {
			if ($copies == 2)
				$str_bill_footer="\n".
					$double_line.$space_between.$double_line."\n".
					$str_total;
			else
				$str_bill_footer="\n".
					$double_line."\n".
					$str_total;
		}
	}
}
if ($str_print_tax_totals == 'Y') {
	$str_taxes = '';
	for ($i=0;$i<count($arr_taxes);$i++) {
		$str_taxes = "Tax ".$arr_taxes[$i][1]."% total = ".number_format($arr_taxes[$i][2],2,'.','');
		$int_spaces = BILL_WIDTH - strlen($str_taxes);
		$str_spaces = "";
		$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
		if ($copies == 2)
			$str_bill_footer .= $str_taxes.$str_spaces.$space_between.$str_taxes."\n";
		else
			$str_bill_footer .= $str_taxes."\n";
	}
}

if ($sql_bill->FieldByName('payment_type') == BILL_CASH) {
	$str_tax = "Inclusive of sales tax";
	$int_spaces = BILL_WIDTH - strlen($str_tax);
	$str_spaces = "";
	$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
	if ($copies == 2)
		$str_tax = "\n".$str_tax.$str_spaces.$space_between.$str_tax;
	else
		$str_tax = "\n".$str_tax;
}
else
	$str_tax = "";

$str_eject_lines = "";
for ($i=0;$i<$int_eject_lines;$i++) {
  $str_eject_lines .= "\n"; 
}

if ($str_print_header == 'Y') {
	$int_spaces = BILL_WIDTH - strlen($str_bill_header_text);
	$str_spaces = "";
	$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
	if ($copies == 2)
		$str_bill_header_text = $str_bill_header_text.$str_spaces.$space_between.$str_bill_header_text;
	else
		$str_bill_header_text = $str_bill_header_text;

	$str_bill = "%b".$str_bill_header_text."%n%c".$str_bill_header.$str_bill_items.$str_bill_footer.$str_tax.$str_balance.$str_note.$str_eject_lines."%n";
}
else
	$str_bill = $str_bill_header.$str_bill_items.$str_bill_footer.$str_tax.$str_balance.$str_note.$str_eject_lines."%n";

$str_bill = replaceSpecialCharacters($str_bill);

?>

<PRE>
<?
// echo $str_bill;
?>
</PRE>

<? if (browser_detection("os") === "lin") { ?>
	<form name="printerForm" method="POST" action="http://localhost/print.php">
<? } else { ?>
	<form name="printerForm" onsubmit="return false;">
<? } ?>

<table width="672px" height="558px" border="0" background="images/maroma.JPG" style="font-size:12px;">
	<tr height="101px">
		<td valign="bottom" colspan="10" style="padding-left:55px;">
			<?php
				echo trim($sql_bill->FieldByName('bill_number'));
			?>
		</td>
	</tr>
	<tr height="28px">
		<td valign="bottom" colspan="10" style="padding-left:55px;">
			<?php
				echo FormatDate($sql_bill->FieldByName('date_created'));
			?>
		</td>
	</tr>
	<tr height="30px">
		<td valign="bottom" colspan="10" style="padding-left:55px;">
		</td>
	</tr>
<?php
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
		else if (($sql_bill->FieldByName('payment_type') == BILL_ACCOUNT) || ($sql_bill->FieldByName('payment_type') == BILL_AUROCARD)){
			if ($is_account_taxed == 'Y')
				$calculate_tax = 'Y';
			else
				$calculate_tax = 'N';
		}
	}
	else
		$calculate_tax = 'N';

	$total_quantity = $sql_items->FieldByName('quantity') + $sql_items->FieldByName('adjusted_quantity');
	$discount_price = 0;
	if ($calculate_tax == 'Y') {
		if ($sql_items->FieldByName('discount') > 0) {
			if ($str_calc_tax_first == 'Y') {
				$tmp_price = $sql_items->FieldByName('price');
				$tmp_qty = $total_quantity;
				$tmp_discount = $sql_items->FieldByName('discount');
				$tmp_tax_id = $sql_items->FieldByName('tax_id');
				
				$tax_price = round($tmp_price + calculateTax($tmp_price, $tmp_tax_id),3);
				$discount_price = $tax_price * (1 - ($sql_items->FieldByName('discount')/100));
				$tax_amount = calculateTax(($tmp_price * $tmp_qty), $tmp_tax_id);
				$flt_discount = round(($tmp_qty * $tax_price) * ($tmp_discount/100),3);
				$item_total = round(($tmp_qty * $tax_price - $flt_discount), 3);
			}
			else {
				$discount_price = $sql_items->FieldByName('price') * (1 - ($sql_items->FieldByName('discount')/100));
				$tax_amount = calculateTax($discount_price, $sql_items->FieldByName('tax_id'));
				$item_total = ($total_quantity * ($discount_price + $tax_amount));
			}
		}
		else {
			$tax_amount = calculateTax($sql_items->FieldByName('price'), $sql_items->FieldByName('tax_id'));
			$item_total = round($total_quantity * ($sql_items->FieldByName('price') + $tax_amount),3);
		}
		$int_index = getColumn($arr_taxes, $sql_items->FieldByName('tax_id'));
		if ($int_index > -1)
			$arr_taxes[$int_index][2] += ($tax_amount * $total_quantity);
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
		$str_discount = ' ';
	else
		$str_discount = $int_discount_amount."%";
		
	echo "<tr height=\"15px\" style=\"font-size:11px;\">";
	echo "<td width='43px' align='right'>".($i+1)."</td>";
	echo "<td width='72px' align='right'>".$sql_items->FieldByName('product_code')."</td>";
	echo "<td width='220px' align='left'>".$sql_items->FieldByName('product_description')."</td>";
	echo "<td width='25px' align='right'>".$total_quantity."</td>";
	echo "<td width='55px' align='right'>".sprintf("%01.2f", $sql_items->FieldByName('price'))."</td>";
	if ($sql_items->FieldByName('tax_description')=='None')
		echo "<td width='30px' align='right'>0</td>";
	else
		echo "<td width='30px' align='right'>".substr($sql_items->FieldByName('tax_description'),0,strlen($sql_items->FieldByName('tax_description'))-1)."</td>";
	echo "<td width='45px' align='right'>".sprintf("%01.2f", $str_discount)."</td>";
	echo "<td width='68px' align='right'>".sprintf("%01.2f", $discount_price)."</td>";
	echo "<td width='70px' align='right'>".sprintf("%01.2f", $item_total)."</td>";
	echo "<td></td>";
	echo "</tr>\n";

	$sql_items->next();
}
?>
	<tr height="30px">
		<td align="right" colspan="10" style="font-size:11px;font-weight:bold;padding-right:24px;">
			<?php
				echo sprintf("%01.2f", $sql_bill->FieldByName('total_amount'));
			?>
		</td>
	</tr>
	<tr>
		<td>
		</td>
	</tr>
</table>


</form>

<? if (browser_detection( 'os' ) === 'lin') { ?>

<script language="JavaScript">
	window.print();
</script>

<? } else { ?>

<script language="JavaScript">
	window.print();
</script>

<? } ?>

</body>
</html>