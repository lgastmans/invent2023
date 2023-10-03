<?
	
	require_once("../include/db.inc.php");
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/browser_detection.php");
	require_once("../common/tax.php");
	require_once("../common/printer.inc.php");
	require_once("../common/account.php");
	require_once("../include/common_funcs.inc.php");
	

	function my_offset($text) {
	    preg_match('/\d/', $text, $m, PREG_OFFSET_CAPTURE);
	    if (sizeof($m))
	        return $m[0][1];

	    // return anything you need for the case when there's no numbers in the string
	    return strlen($text);
	}


	$copies = $arr_invent_config['billing']['print_copies'];
	$print_name = $arr_invent_config['billing']['print_name'];
	$print_mode = $arr_invent_config['billing']['print_mode'];
	$print_os = browser_detection("os");
	

	/*
		company details
	*/
	$company_title = '';
	$company_gstin = '';
	$company_trust = '';
	$company_trade_name = '';
	$company_address = '';
	$company_phone = '';
	$company_email = '';
	$company_footer = '';

	$sql = 'SELECT * FROM company';
	$qry = new Query($sql);

	if ($qry->RowCount() > 0) {
		$company_title = $qry->FieldByName('title');
		$company_gstin = $qry->FieldByName('gstin');
		$company_trust = $qry->FieldByName('trust');
		$company_trade_name = $qry->FieldByName('trade_name');
		$company_address = $qry->FieldByName('address');
		$company_phone = $qry->FieldByName('phone');
		$company_email = $qry->FieldByName('email');
		$company_footer = $qry->FieldByName('footer');
	}


	/*
		user settings
	*/	
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
	


	/*
		get all taxes that are not "surcharge"
	*/
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
				$arr_tmp[] = $qry_tax_headers->FieldByName('definition_description');  // GST
				$arr_tmp[] = 0.0;
				$arr_taxes[] = $arr_tmp;
			}
			$qry_tax_headers->Next();
		}
	}


	/*
		bill details
	*/
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

			$sql_str = "
				SELECT SUM(bi.quantity) AS quantity, SUM(bi.adjusted_quantity) AS adjusted_quantity,
					bi.discount, bi.price, bi.tax_id,
					sp.product_code, sp.product_description,
					sb.batch_code,
					st.tax_description,
					stl.tax_definition_id,
					sup.supplier_abbreviation
				FROM ".Monthalize("bill_items")." bi
					INNER JOIN stock_product sp ON (sp.product_id = bi.product_id)
					INNER JOIN ".Yearalize('stock_batch')." sb ON (sb.batch_id = bi.batch_id)
					LEFT JOIN stock_supplier sup ON (sup.supplier_id = sp.supplier_id)
					LEFT JOIN ".Monthalize('stock_tax')." st ON (bi.tax_id = st.tax_id)
					LEFT JOIN ".Monthalize('stock_tax_links')." stl ON (bi.tax_id = stl.tax_id)
				WHERE (bi.bill_id = ".$sql_bill->FieldByName('bill_id').")
				GROUP BY product_code, bi.price
				ORDER BY bi.bill_item_id
			";

		}
		else {

			$sql_str = "
				SELECT SUM(bi.quantity) AS quantity, SUM(bi.adjusted_quantity) AS adjusted_quantity,
					bi.discount, bi.price, bi.tax_id,
					sp.product_code, sp.product_description,
					sb.batch_code,
					st.tax_description,
					stl.tax_definition_id,
					sup.supplier_abbreviation
				FROM ".Monthalize("bill_items")." bi
					INNER JOIN stock_product sp ON (sp.product_id = bi.product_id)
					LEFT JOIN ".Yearalize('stock_batch')." sb ON (sb.batch_id = bi.batch_id)
					LEFT JOIN stock_supplier sup ON (sup.supplier_id = sp.supplier_id)
					LEFT JOIN ".Monthalize('stock_tax')." st ON (bi.tax_id = st.tax_id)
					LEFT JOIN ".Monthalize('stock_tax_links')." stl ON (bi.tax_id = stl.tax_id)
				WHERE (bi.bill_id = ".$sql_bill->FieldByName('bill_id').")
				GROUP BY product_code
				ORDER BY bi.bill_item_id
			";

		}

		$sql_items = new Query($sql_str);

	}


	/*
		get the tax details for the storeroom
	*/
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
		


	/*
		column widths
	*/
	define('WIDTH_CODE', 12);
	define('WIDTH_BATCH', 0);
	define('WIDTH_DESCR', 25);
	define('WIDTH_QTY', 5);
	define('WIDTH_PRICE', 8);
	define('WIDTH_DISCOUNT', 2);
	define('WIDTH_TAX1', 5);
	define('WIDTH_TAX2', 5);
	define('WIDTH_TOTAL', 8);

	/*
		Total width 
		including x for space between columns
	*/
	$bill_width = WIDTH_CODE + WIDTH_BATCH + WIDTH_DESCR + WIDTH_QTY + WIDTH_DISCOUNT + WIDTH_PRICE + WIDTH_TAX1 + WIDTH_TAX2 + WIDTH_TOTAL + 7;
	define('BILL_WIDTH', $bill_width);
		

	/*
		space between two bills
	*/
	define('SPACE_BETWEEN', 5);
	$space_between = PadWithCharacter($space_between, ' ', SPACE_BETWEEN);
	
	
?>
<html>
<head><TITLE>Printing Bill</TITLE>
<link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>

<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0">

<?
	/*
		string containing the bill
	*/
	$str_bill = '';



	/*
		tax invoice header
	*/
		$int_spaces = intval(BILL_WIDTH/2) - intval(strlen("TAX INVOICE")/2);
		$str_spaces = "";
		$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
		$str_bill = $str_spaces."TAX INVOICE\n\n";
		

	/*
		cancelled bill
	*/
	if ($sql_bill->FieldByName('bill_status') == BILL_STATUS_CANCELLED) {

		$str_bill .= "CANCELLED\n\n";

	}


	/*
		company title and details
	*/
	$str_bill .= $company_title."\n".
		(!empty($company_trust) ? $company_trust.", " : "").
		$company_trade_name."\n".
		(!empty($company_address) ? $company_address."\n" : "").
		(!empty($company_phone) ? "Phone: ".$company_phone."\n" : "").
		(!empty($company_email) ? "Email: ".$company_email."\n" : "").
		(!empty($company_gstin) ? "GSTIN: ".$company_gstin."\n" : "");

	$str_bill .= "\n";



	/*
		FS account bill
		or Transfer of Goods bill
	*/
	if (($sql_bill->FieldByName('payment_type') == BILL_ACCOUNT) ||  ($sql_bill->FieldByName('payment_type') == BILL_TRANSFER_GOOD)) {

		$str_account2 = '';

		if ($sql_bill->FieldByName('is_debit_bill') == 'Y') {

			$str_account =  "CREDIT: ".$sql_bill->FieldByName('account_number')." - ".$sql_bill->FieldByName('account_name');
			$str_account2 .= "DEBIT: ".$str_credit_account;
			$int_spaces = BILL_WIDTH - strlen($str_account2);
			$str_spaces = "";
			$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
				$str_account2 = "\n".$str_account2;
		}
		else
			$str_account = $sql_bill->FieldByName('account_number')." - ".$sql_bill->FieldByName('account_name');

		$int_spaces = BILL_WIDTH - strlen($str_account);
		$str_spaces = "";
		$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);

		
		$str_bill .= "%b".$str_account.$str_account2."%n%c\n";
	}
	/*
		New Pour Tous (PTDC)
	*/
	else if ($sql_bill->FieldByName('payment_type') == BILL_PT_ACCOUNT) {

		$str_account = $sql_bill->FieldByName('account_number')." - ".$sql_bill->FieldByName('account_name');
		$int_spaces = BILL_WIDTH - strlen($str_account);
		$str_spaces = "";
		$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);

		$str_bill .= $str_account."\n";
	}
	/*
		Aurocard
	*/
	else if ($sql_bill->FieldByName('payment_type') == BILL_AUROCARD) {

		$str_account = "Card No: ".$sql_bill->FieldByName('aurocard_number')." Trans Id: ".$sql_bill->FieldByName('aurocard_transaction_id');
		$int_spaces = BILL_WIDTH - strlen($str_account);
		$str_spaces = "";
		$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);

		$str_bill .= "%b".$str_account.$str_account2."%n%c\n";
	}
	/*
		Credit Card
	*/
	else if ($sql_bill->FieldByName('payment_type') == BILL_CREDIT_CARD) {

		$str_bill .= $sql_bill->FieldByName('payment_type_number')."\n";

	}
	/*
		Cheque
	*/
	else if ($sql_bill->FieldByName('payment_type') == BILL_CHEQUE) {

		$str_bill .= $sql_bill->FieldByName('payment_type_number')."\n";

	}

	/*
		bill number and date
	*/
	if ($sql_bill->FieldByName('payment_type') == BILL_TRANSFER_GOOD)
		$int_spaces = BILL_WIDTH - (10 + strlen(trim($sql_bill->FieldByName('bill_number'))) + 6 + 10);
	else
		$int_spaces = BILL_WIDTH - (9 + strlen(trim($sql_bill->FieldByName('bill_number'))) + 6 + 10);
	$str_spaces = "";
	$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);

	if ($sql_bill->FieldByName('payment_type') == BILL_TRANSFER_GOOD)
		$str_billnum_date = "Transfer: ".trim($sql_bill->FieldByName('bill_number')).$str_spaces."Date: ".FormatDate($sql_bill->FieldByName('date_created'));
	else
		$str_billnum_date = "Invoice: ".trim($sql_bill->FieldByName('bill_number')).$str_spaces."Date: ".FormatDate($sql_bill->FieldByName('date_created'));

	$str_bill .= $str_billnum_date."\n";


	/*
		column header
	*/
	$print_tax = 'Y';

	$double_line = PadWithCharacter($double_line, '=', BILL_WIDTH);
	$single_line = PadWithCharacter($single_line, '-', BILL_WIDTH);

	$col_code = StuffWithCharacter('Code', ' ', WIDTH_CODE)." ";
	$col_descr = PadWithCharacter('Description', ' ', WIDTH_DESCR)." ";
	$col_qty = StuffWithCharacter('Qty', ' ', WIDTH_QTY)." ";
	$col_discount = PadWithCharacter('Dt', ' ', WIDTH_DISCOUNT)." ";
	$col_price = StuffWithCharacter('Price', ' ', WIDTH_PRICE)." ";
	$col_tax1 = StuffWithCharacter('SGST%', ' ', WIDTH_TAX1)." ";
	$col_tax2 = StuffWithCharacter('CGST%', ' ', WIDTH_TAX2)." ";
	$col_total = StuffWithCharacter('Total', ' ', WIDTH_TOTAL);


	if ($print_tax == 'Y') {

		$cols = $col_code.$col_descr.$col_qty.$col_price.$col_discount.$col_tax1.$col_tax2.$col_total;

	}
	else {

		$cols = $col_code.$col_descr.$col_qty.$col_price.$col_discount.$col_total;

	}

	$str_bill .= 
		$double_line."\n".
		$cols."\n".
		$single_line;


	/*
		billed products
	*/
	$tax1='';
	$tax2='';
			
	$str_bill_items="";

	$has_discount = false;
	$total_without_discount = 0;
	$discount_total = 0;

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
	  
		if ($calculate_tax == 'Y') {

			if ($sql_items->FieldByName('discount') > 0) {

				$has_discount = true;

				if ($str_calc_tax_first == 'Y') {

					$tmp_price = $sql_items->FieldByName('price');
					$tmp_qty = $total_quantity;
					$tmp_discount = $sql_items->FieldByName('discount');
					$tmp_tax_id = $sql_items->FieldByName('tax_id');
					
					$tax_price = round($tmp_price + calculateTax($tmp_price, $tmp_tax_id),3);
					$tax_amount = calculateTax(($tmp_price * $tmp_qty), $tmp_tax_id);
					$flt_discount = round(($tmp_qty * $tax_price) * ($tmp_discount/100),3);
					$item_total = round(($tmp_qty * $tax_price - $flt_discount), 3);

				}
				else {

					$discount_price = $sql_items->FieldByName('price') * (1 - ($sql_items->FieldByName('discount')/100));
					$tax_amount = calculateTax($discount_price, $sql_items->FieldByName('tax_id'));
					$item_total = ($total_quantity * ($discount_price + $tax_amount));

					$discount_total += $total_quantity * ($sql_items->FieldByName('price') * ($sql_items->FieldByName('discount')/100));

					$price = $sql_items->FieldByName('price');
					$total_without_discount += ($total_quantity * ($price + $tax_amount));

				}

			}
			else {

				$tax_amount = calculateTax($sql_items->FieldByName('price'), $sql_items->FieldByName('tax_id'));
				$item_total = round($total_quantity * ($sql_items->FieldByName('price') + $tax_amount),3);

				$total_without_discount += ($total_quantity * ($sql_items->FieldByName('price') + $tax_amount));				
			}

			$int_index = getColumn($arr_taxes, $sql_items->FieldByName('tax_definition_id'));
			if ($int_index > -1)
				$arr_taxes[$int_index][3] += ($tax_amount * $total_quantity);

		}
		else {

			$tax_amount = 0;

			if ($sql_items->FieldByName('discount') > 0) {

				$has_discount = true;

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

		$pos1 = my_offset($sql_items->FieldByName('tax_description'));
		$tax1 = substr($sql_items->FieldByName('tax_description'), $pos1);
		$tax2 = floatval($tax1)/2;
		

		if ($print_tax == 'Y') {

			$str_product_description = PadWithCharacter($sql_items->FieldByName('product_description'), ' ', WIDTH_DESCR);

			$tmp_str = StuffWithCharacter($sql_items->FieldByName('product_code'), ' ', WIDTH_CODE)." ".		
				$str_product_description." ".
				StuffWithCharacter($total_quantity, ' ', WIDTH_QTY)." ".
				StuffWithCharacter(sprintf("%01.2f", $sql_items->FieldByName('price')), ' ', WIDTH_PRICE)." ".
				StuffWithCharacter($str_discount, ' ', WIDTH_DISCOUNT)." ".
				StuffWithCharacter($tax2."%", ' ', WIDTH_TAX1)." ".
				StuffWithCharacter($tax2."%", ' ', WIDTH_TAX2)." ".
				StuffWithCharacter(sprintf("%01.2f", $item_total), ' ', WIDTH_TOTAL);

		} else {

			$str_product_description = PadWithCharacter($sql_items->FieldByName('product_description'), ' ', WIDTH_DESCR);
			
			$tmp_str = StuffWithCharacter($sql_items->FieldByName('product_code'), ' ', WIDTH_CODE)." ".
				$str_product_description." ".
				StuffWithCharacter($total_quantity, ' ', WIDTH_QTY)." ".
				StuffWithCharacter($str_discount, ' ', WIDTH_DISCOUNT)." ".
				StuffWithCharacter(sprintf("%01.2f", $sql_items->FieldByName('price')), ' ', WIDTH_PRICE)." ".
				StuffWithCharacter(sprintf("%01.2f", $item_total), ' ', WIDTH_PRICE);
		}

		$str_bill_items = $str_bill_items."\n".$tmp_str;
		
		$sql_items->next();
	}

	$str_bill .= $str_bill_items."\n";


	$str_bill .= $double_line."\n";


	/*
		bill total and user name
	*/
	if ($has_discount) {

		$tmp = "Total without discount: ".number_format($total_without_discount,2,'.',',');
		$int_spaces = BILL_WIDTH - strlen($tmp);
		$str_spaces = PadWithCharacter($str_spaces,' ', $int_spaces);
		$str_bill .= $str_spaces.$tmp."\n";

		$tmp = "Total discount: ".number_format($discount_total,2,'.',',');
		$int_spaces = BILL_WIDTH - strlen($tmp);
		$str_spaces = PadWithCharacter($str_spaces,' ', $int_spaces);
		$str_bill .= $str_spaces.$tmp."\n";

		$total_amount = number_format(round($total_without_discount - $discount_total),2,'.',',');
	}
	else {
		//$total_amount = number_format(round($sql_bill->FieldByName('total_amount')),2,'.',',');
		$total_amount = number_format(round($total_without_discount),2,'.',',');
	}

	$str_user = "User: ".$sql_bill->FieldByName('username');
	$int_spaces = BILL_WIDTH - (strlen($str_user) + 21 + strlen($total_amount));
	$str_spaces = "";
	$str_spaces = PadWithCharacter($str_spaces,' ', $int_spaces);
	$str_total = $str_user.$str_spaces."Total (rounded): Rs. ".$total_amount."\n";

	$str_bill .= $str_total;


	/*
		Sales Promotion (overall discount)
	*/
	if ($sql_bill->FieldByName('bill_promotion') > 0) {

		$bill_promotion = number_format($sql_bill->FieldByName('bill_promotion'),2,'.',',');

		$int_spaces = BILL_WIDTH - (17 + strlen($bill_promotion));
		$str_spaces = "";
		$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
		$str_promotion = $str_spaces."Sales promotion: ".$bill_promotion;
		
		$flt_grandtotal = number_format(round($sql_bill->FieldByName('total_amount') - $sql_bill->FieldByName('bill_promotion')),2,'.',','); 
		$int_spaces = BILL_WIDTH - (13 + strlen($flt_grandtotal));
		$str_spaces = "";
		$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
		$str_grandtotal = $str_spaces."Grand total: ".$flt_grandtotal;
		
		
		$str_amount_words = ExpandAmountRs($flt_grandtotal);


		$str_bill .= $str_promotion."\n".
			$str_grandtotal."\n";
	}


	/*
		breakdown of tax totals
	*/
	if ($str_print_tax_totals == 'Y') {

		$str_taxes = '';

		for ($i=0;$i<count($arr_taxes);$i++) {

			if (floatval($arr_taxes[$i][3]) > 0) {

				$str_taxes = $arr_taxes[$i][2]." = ".number_format($arr_taxes[$i][3],2,'.','');
				$int_spaces = BILL_WIDTH - strlen($str_taxes);
				$str_spaces = "";
				$str_spaces = PadWithCharacter($str_spaces, ' ', $int_spaces);
				$str_bill_footer .= $str_spaces.$str_taxes."\n";
			}
		}

		$str_bill .= "\n".$str_bill_footer;

	}


	/*
		notes to print add end of bill
	*/
	$str_note = "\n".$str_tmp;
	
	if (!empty($str_note_2))
			$str_note .= "\n".$str_note_2;
	
	if (!empty($str_note_3))
			$str_note .= "\n".$str_note_3;

	$str_bill .= $str_note;


	/*
		blank lines to eject at end of bill
	*/
	$str_eject_lines = "";
	for ($i=0;$i<$int_eject_lines;$i++) {
	  $str_eject_lines .= "\n"; 
	}

	$str_bill .= $str_eject_lines;


?>


<PRE>
<?
 	echo $str_bill;
?>
</PRE>

	<form name="printerForm" method="POST" action="http://localhost/print.php">

		<table width="100%" bgcolor="#E0E0E0">
		  <tr>
		    <td>
		      <br>
		      <input type="hidden" name="data" value="<? echo htmlentities($str_bill); ?>"><br>
			  <input type="hidden" name="os" value="<? echo $os;?>"><br>
			  <input type="hidden" name="print_name" value="<? echo $print_name?>"><br>
			  <input type="hidden" name="print_mode" value="<? echo $print_mode?>"><br>
		    </td>
		  </tr>
		</table>
	</form>


<script language="JavaScript">
	//printerForm.submit();
	//setTimeout("window.close();",2000);
</script>

</body>
</html>