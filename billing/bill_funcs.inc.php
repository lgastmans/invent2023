<?
require_once("../include/const.inc.php");
require_once("../include/session.inc.php");
require_once("../include/db.inc.php");
require_once("../common/tax.php");

/**
	get_bill_types
	get_item_totals
	get_sales_promotion
*/

function getBillTypes() {
	// get the types that can be billed
	$qry = new Query("
		SELECT can_bill_cash, can_bill_fs_account, can_bill_pt_account, can_bill_aurocard, can_bill_creditcard
		FROM stock_storeroom
		WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")
	");
	$arr = array();
	
	$arr['can_bill_cash'] = ($qry->FieldByName('can_bill_cash') == 'Y' ? true : false);
	$arr['can_bill_fs_account'] = ($qry->FieldByName('can_bill_fs_account') == 'Y' ? true : false);
	$arr['can_bill_pt_account'] = ($qry->FieldByName('can_bill_pt_account') == 'Y' ? true : false);
	$arr['can_bill_aurocard'] = ($qry->FieldByName('can_bill_aurocard') == 'Y' ? true : false);
	$arr['can_bill_creditcard'] = ($qry->FieldByName('can_bill_creditcard') == 'Y' ? true : false);
	$arr['can_bill_transfer_good'] = (CAN_BILL_TRANSFER_GOOD === 1 ? true : false);
	$arr['can_bill_upi'] = true;
	
	return $arr;
}


    function get_item_totals($str_include_tax, $str_time_constrained, $is_morning, $day_of_month, $supplier_type, $int_bill_type) {
		
	$sql_settings = new Query("
		SELECT *
		FROM user_settings
	");
	if ($sql_settings->RowCount() > 0) {
		$str_calc_tax_first = $sql_settings->FieldByName('calculate_tax_before_discount');
	}

	$qry_storeroom = new Query("
		SELECT is_cash_taxed, is_account_taxed
		FROM stock_storeroom
		WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")
	");
	$is_cash_taxed = 'Y';
	$is_account_taxed = 'Y';
	if ($qry_storeroom->RowCount() > 0) {
		$is_cash_taxed = $qry_storeroom->FieldByName('is_cash_taxed');
		$is_account_taxed = $qry_storeroom->FieldByName('is_account_taxed');
	}
	if ($str_include_tax == 'Y') {
		$str_calculate_tax = 'N';
		if (($int_bill_type == BILL_CASH) && ($is_cash_taxed == 'Y'))
			$str_calculate_tax = 'Y';
		else if (($int_bill_type == BILL_ACCOUNT) && ($is_account_taxed == 'Y'))
			$str_calculate_tax = 'Y';
		else if (($int_bill_type == BILL_AUROCARD) && ($is_account_taxed == 'Y'))
			$str_calculate_tax = 'Y';
		else if (($int_bill_type == BILL_CREDIT_CARD) && ($is_cash_taxed == 'Y'))
			$str_calculate_tax = 'Y';
		else if (($int_bill_type == BILL_UPI) && ($is_cash_taxed == 'Y'))
			$str_calculate_tax = 'Y';
		
	}
		
	$qry_settings = new Query("
		SELECT bill_closing_time
		FROM user_settings
	");
	$str_closing_time = "12:00:00";
	if ($qry_settings->RowCount() > 0)
		$str_closing_time = $qry_settings->FieldByName('bill_closing_time');
	
	if ($is_morning == 'Y')
		$str_comparison = '<';
	else
		$str_comparison = '>';
		
	if ($str_time_constrained == 'Y') {
		$str_query = "
			SELECT *
			FROM ".Monthalize('bill')." b,
			".Monthalize('bill_items')." bi,
			".Yearalize('stock_batch')." sb,
			stock_supplier ss
			WHERE (b.payment_type = ".$int_bill_type.") 
				AND (DATE(b.date_created) = '".sprintf("%04d-%02d-%02d", $_SESSION["int_year_loaded"], $_SESSION["int_month_loaded"], $day_of_month)."')
				AND (TIME(b.date_created) ".$str_comparison." '".$str_closing_time."')
				AND (b.bill_status <> ".BILL_STATUS_CANCELLED.")
				AND (b.storeroom_id = ".$_SESSION["int_current_storeroom"].")
				AND (bi.bill_id = b.bill_id)
				AND (bi.batch_id = sb.batch_id)
				AND (sb.supplier_id = ss.supplier_id)
				AND (ss.is_supplier_delivering = '".$supplier_type."')
		";
	}
	else {
		$str_query = "
			SELECT *
			FROM ".Monthalize('bill')." b,
			".Monthalize('bill_items')." bi,
			".Yearalize('stock_batch')." sb,
			stock_supplier ss
			WHERE (b.payment_type = ".$int_bill_type.") 
				AND (DATE(b.date_created) = '".sprintf("%04d-%02d-%02d", $_SESSION["int_year_loaded"], $_SESSION["int_month_loaded"], $day_of_month)."')
				AND (b.bill_status <> ".BILL_STATUS_CANCELLED.")
				AND (b.storeroom_id = ".$_SESSION["int_current_storeroom"].")
				AND (bi.bill_id = b.bill_id)
				AND (bi.batch_id = sb.batch_id)
				AND (sb.supplier_id = ss.supplier_id)
				AND (ss.is_supplier_delivering = '".$supplier_type."')
		";
	}
		
	$flt_amount = 0;
	
	$qry_items = new Query($str_query);
	
	if ($qry_items->RowCount() > 0) {

		for ($i=0; $i<$qry_items->RowCount(); $i++) {
			
			$flt_quantity = number_format($qry_items->FieldByName('quantity') + $qry_items->FieldByName('adjusted_quantity'), 3, '.', '');
			$tmp_price = $qry_items->FieldByName('price');
			$tmp_discount = $qry_items->FieldByName('discount');
			$tmp_tax_id = $qry_items->FieldByName('tax_id');
			$flt_discount = 0;
			
			if ($tmp_discount > 0) {
				if ($str_calc_tax_first == 'Y') {
					$tax_price = round($tmp_price + calculateTax($tmp_price, $tmp_tax_id),3);
					$tax_amount = calculateTax(($tmp_price * $flt_quantity), $tmp_tax_id);
					if ($str_calculate_tax == 'Y') {
						$flt_discount = round(($flt_quantity * $tax_price) * ($tmp_discount/100),3);
						$flt_price_total = round(($flt_quantity * $tax_price - $flt_discount), 3);
					}
					else {
						$flt_discount = round(($flt_quantity * $tmp_price) * ($tmp_discount/100),3);
						$flt_price_total = round(($flt_quantity * $tmp_price - $flt_discount), 3);
					}
				}
				else {
					$discount_price = round(($tmp_price * (1 - ($tmp_discount/100))), 3);
					$tax_amount = calculateTax($flt_quantity * $discount_price, $tmp_tax_id);
					if ($str_calculate_tax == 'Y')
						$flt_price_total = round(($flt_quantity * $discount_price + $tax_amount), 3);
					else
						$flt_price_total = round(($flt_quantity * $discount_price), 3);
				}
			}
			else {
				$tax_amount = calculateTax($tmp_price * $flt_quantity, $tmp_tax_id);
				if ($str_calculate_tax == 'Y')
					$flt_price_total = round(($flt_quantity * $tmp_price + $tax_amount), 3);
				else
					$flt_price_total = round(($flt_quantity * $tmp_price), 3);
			}
			
			$flt_amount += round($flt_price_total,3);
			
			$qry_items->Next();
		}
		}
		return $flt_amount;
	}
    
    
    function get_sales_promotion($day_of_month) {
	$str_query = "
	    SELECT SUM(bill_promotion) AS total
	    FROM ".Monthalize('bill')." b
	    WHERE (DATE(b.date_created) = '".sprintf("%04d-%02d-%02d", $_SESSION["int_year_loaded"], $_SESSION["int_month_loaded"], $day_of_month)."')
		    AND (b.bill_status <> 3)
		    AND (b.storeroom_id = ".$_SESSION["int_current_storeroom"].")
	";
	$qry_sales_promotion = new Query($str_query);
	
	$flt_retval = 0;
	if ($qry_sales_promotion->RowCount() > 0)
	    $flt_retval = number_format($qry_sales_promotion->FieldByName('total'), 2, '.', '');
	
	return $flt_retval;
    }
?>