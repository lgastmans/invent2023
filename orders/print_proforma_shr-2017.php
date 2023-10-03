<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");
    require_once("../common/tax.php");
    require_once("../Numbers/Words.php");
    require_once("../common/number_to_words.php");

    if (IsSet($_GET['order_id']))
        $int_id = $_GET['order_id'];
    
    if (IsSet($_GET['is_bill_id'])) {
        $qry = new Query("SELECT module_record_id FROM ".Monthalize('bill')." WHERE bill_id = $int_id");
        $int_id = $qry->FieldByName('module_record_id');
        $qry->Free();
    }
    
    
    function ExpandAmount($amount) {
        $nw = new Numbers_Words();
        if (strpos($amount,'.') !== false) {
            $numwords = explode('.',$amount);
            if (intval($numwords[1]) > 0)
                $res = $nw->toWords($numwords[0]).' and paise '.$nw->toWords($numwords[1]).' only';
            else
                $res = $nw->toWords($numwords[0]).' only';
        }
        else  {
            $res = $nw->toWords($amount);
        }
        return $res;
    }

    $qry = new Query("SELECT * FROM templates WHERE template_type = ".TEMPLATE_ORDER_PROFORMA." AND is_default = 'Y'");
    if ($qry->RowCount() == 0)
        die('No template found');

    //======================================
    // get the tax details for the storeroom
    //--------------------------------------
    $qry_tax = new Query("
            SELECT is_taxed, is_cash_taxed, is_account_taxed
            FROM stock_storeroom
            WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")"
    );
    $is_taxed = 'Y';
    $is_cash_taxed = 'Y';
    $is_account_taxed = 'Y';
    if ($qry_tax->RowCount() > 0) {
            $is_taxed = $qry_tax->FieldByName('is_taxed');
            $is_cash_taxed = $qry_tax->FieldByName('is_cash_taxed');
            $is_account_taxed = $qry_tax->FieldByName('is_account_taxed');
    }

    $str_title = stripslashes($qry->FieldByName('title'));
    $str_header = stripslashes($qry->FieldByName('header'));
    $str_content = stripslashes($qry->FieldByName('content'));
    $str_footer = stripslashes($qry->FieldByName('footer'));
    
    $qry_order = new Query("
        SELECT *
        FROM ".Monthalize('orders')."
        WHERE order_id = ".$int_id
    );
    
    $qry_items = new Query("
        SELECT *
        FROM ".Monthalize('order_items')." oi
        LEFT JOIN stock_product sp ON (sp.product_id = oi.product_id)
        WHERE order_id = ".$qry_order->FieldByName('order_id')
    );
    
    $qry_customer = new Query("
        SELECT *
        FROM customer
        WHERE id = ".$qry_order->FieldByName('CC_id'));

	/*
		get the customer's price_increase field
	*/
	$flt_price_increase = 0;
	if ($qry_customer->RowCount() > 0)
		$flt_price_increase = 1 + ($qry_customer->FieldByName('price_increase') / 100);
	/*
		---
	*/
    $qry_tax = new Query("
        SELECT *
        FROM ".Monthalize('stock_tax')."
        WHERE tax_id = ".$qry_customer->FieldByName('tax_id')
    );
    $str_tax_description = $qry_tax->FieldByName('tax_description');

    function get_date($str_mysql_date) {
        $str_date = substr($str_mysql_date, 0, 10);
        $arr_date = explode("-", $str_date);
        return $arr_date[2]."-".$arr_date[1]."-".$arr_date[0];
    }
    
    //========================================================
    // header
    //--------------------------------------------------------
    $str_header = str_replace('$customer', $qry_customer->FieldByName('company'), $str_header);
    $str_header = str_replace('$address', $qry_customer->FieldByName('address'), $str_header);
    $str_header = str_replace('$city', $qry_customer->FieldByName('city')." ".$qry_customer->FieldByName('zip'), $str_header);
    $str_header = str_replace('$order_date', get_date($qry_order->FieldByName('order_date')), $str_header);
    
    //========================================================
    // content
    //--------------------------------------------------------
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
        
        $total_quantity = $qry_items->FieldByName('quantity_ordered');
        
        $flt_price = $qry_items->FieldByName('price') * $flt_price_increase;
        if ($calculate_tax == 'Y') {
			if ($qry_items->FieldByName('tax_id')==NULL)
				$int_tax_id = 0;
			else
				$int_tax_id = $qry_items->FieldByName('tax_id');
            $tax_amount = calculateTax($flt_price , $int_tax_id);
			
            //$flt_price = $qry_items->FieldByName('price'); //$qry_items->FieldByName('price') + $tax_amount;
            $item_total = $total_quantity * $flt_price; //($total_quantity * ($qry_items->FieldByName('price') + $tax_amount));
        }
        else {
            $tax_amount = 0;
            //$flt_price = $qry_items->FieldByName('price');
            $item_total = $total_quantity * $flt_price;
        }
        
        $qry_tax->Query("SELECT * FROM ".Monthalize('stock_tax')." WHERE tax_id = ".$qry_items->FieldByName('tax_id'));
        
        $flt_total += $item_total;
        
        $str_items .= "<tr><td style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>".$qry_items->FieldByName('product_code')."</td>".
            "<td style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>".$qry_items->FieldByName('product_description')."</td>".
            "<td align='right' style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>".number_format($flt_price, 2, '.', '')."</td>".
            "<td align='right' style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>".number_format($total_quantity, 0, '.', '')."</td>".
            "<td align='right' style='font-family:Arial,sans-serif;font-size:12px;font-weight:normal;'>".number_format($item_total, 2, '.', '')."</td></tr>";
                
        $qry_items->Next();
    }

    $flt_discount = $flt_total * $qry_customer->FieldByName('discount') / 100;
    $flt_tax = calculateTax(($flt_total - $flt_discount), $qry_customer->FieldByName('tax_id'));
    $flt_handling = $qry_order->FieldByName('handling_charge');
    $flt_grand_total = $flt_total - $flt_discount + $flt_tax + $flt_handling;
    $flt_total_due = $flt_grand_total - ($qry_order->FieldByName('advance_paid'));
    $flt_total_due = round($flt_total_due);
    $str_amount = ExpandAmount(number_format($flt_total_due,2,'.',''));

    $str_content = str_replace('$items', $str_items, $str_content);
    
    $str_content = str_replace('$subtotal', number_format($flt_total, 2, '.', ','), $str_content);
    $str_content = str_replace('$discount_percentage', $qry_customer->FieldByName('discount')."%", $str_content);
    $str_content = str_replace('$discount', number_format($flt_discount, 2, '.', ','), $str_content);
    $str_content = str_replace('$tax_percentage', $str_tax_description , $str_content);
    $str_content = str_replace('$salestax', number_format($flt_tax, 2, '.', ','), $str_content);
    $str_content = str_replace('$handling', number_format($flt_handling, 2, '.', ','), $str_content);
    $str_content = str_replace('$total', number_format($flt_grand_total, 2, '.', ','), $str_content);
    $str_content = str_replace('$paid', number_format($qry_order->FieldByName('advance_paid'), 2, '.', ','), $str_content);
    $str_content = str_replace('$due', number_format($flt_total_due, 2, '.', ','), $str_content);
    $str_content = str_replace('$str_amount_words', $str_amount, $str_content);

    $str_template = $str_title.$str_header.$str_content.$str_footer;
    
    echo $str_template;

?>