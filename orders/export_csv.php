<?php
	require_once("../include/fpdf/fpdf.php");
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");
    require_once("../common/tax.php");
    //require_once("../Numbers/Words.php");
    //require_once("../common/number_to_words.php");
    require_once("../common/product_funcs.inc.php");

	// ALTER TABLE  `user_settings` ADD  `bill_invoice_suffix` VARCHAR( 15 ) NULL AFTER  `bill_display_messages` ;

    if (IsSet($_GET['id']))
        $int_id = $_GET['id'];

    
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


    /*
    	get Company details
    */
    $company = new Query("SELECT * FROM company WHERE 1");


	/*
		get all taxes that are not "surcharge"
	*/
	$sql = "
		SELECT *
		FROM ".Monthalize('stock_tax_links')." stl
		INNER JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = stl.tax_id)
		INNER JOIN ".Monthalize('stock_tax_definition')." std ON (std.definition_id = stl.tax_definition_id)
		WHERE std.definition_type <> 2";
	$qry_tax_headers = new Query($sql);

	$arr_taxes = array();
	if ($qry_tax_headers->RowCount() > 0) {
		for ($i=0; $i<$qry_tax_headers->RowCount(); $i++) {
			if ($qry_tax_headers->FieldByName('definition_percent') > 0) {
				unset($arr_tmp);
				$arr_tmp[] = $qry_tax_headers->FieldByName('tax_id');
				$arr_tmp[] = $qry_tax_headers->FieldByName('definition_percent');
				$arr_tmp[] = 0.0;
				$arr_taxes[] = $arr_tmp;
			}
			$qry_tax_headers->Next();
		}
	}
//print_r($arr_taxes);

	function getColumn($arr_dest, $int_tax_id) {
		$int_retval = -1;
		for ($i=0; $i<count($arr_dest); $i++) {
			if ($arr_dest[$i][0] === $int_tax_id) {
				$int_retval = $i;
				break;
			}
		}
		return $int_retval;
	}

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


    $qry_bill = new Query("
        SELECT *
        FROM ".Monthalize('bill')."
        WHERE bill_id = $int_id");
    

    
    $qry_order = new Query("
        SELECT *
        FROM ".Monthalize('orders')."
        WHERE order_id = ".$qry_bill->FieldByName('module_record_id'));

    $sql_items = "
        SELECT *, bi.tax_id, SUM(bi.quantity + bi.adjusted_quantity) AS quantity
        FROM ".Monthalize('bill_items')." bi
        LEFT JOIN stock_product sp ON (sp.product_id = bi.product_id)
		LEFT JOIN stock_category sc ON (sc.category_id = sp.category_id)
        WHERE bi.bill_id = $int_id
        GROUP BY bi.product_id, bi.bill_item_id
        ORDER BY sp.product_code
    ";
    $qry_items = new Query($sql_items);

//echo $sql_items;die();

	$qry_customer = new Query("
        SELECT *
        FROM customer
        WHERE id = ".$qry_order->FieldByName('CC_id'));

    /*
    	INVOICE NUMBER & FILENAME
    */
    $suffix = '';
    $qry_settings = new Query("
            SELECT bill_invoice_suffix
            FROM user_settings
            WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")"
    );
    $suffix = $qry_settings->FieldByName('bill_invoice_suffix');
    $filename = 'Invoice '.sprintf('%03d', $qry_bill->FieldByName('bill_number')).$suffix." - ".$qry_customer->FieldByName('company').".pdf";

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
	$customer = $qry_customer->FieldByName('company');
	$address = $qry_customer->FieldByName('address');
	$address2 = $qry_customer->FieldByName('address2');
	$city = $qry_customer->FieldByName('city')." ".$qry_customer->FieldByName('zip');
	$state_code = $qry_customer->FieldByName('state_code');
	$gstin = $qry_customer->FieldByName('gstin');
	$cell = $qry_customer->FieldByName('cell');

	$qry_state = new Query("
        SELECT state
        FROM state_codes
        WHERE id = ".$qry_customer->FieldByName('state')
    );
    $state = $qry_state->FieldByName('state');

    
	if ($qry_customer->FieldByName('same_address')=='Y') {
		$ship_customer = $qry_customer->FieldByName('company');
		$ship_address = $qry_customer->FieldByName('address');
		$ship_address2 = $qry_customer->FieldByName('address2');
		$ship_city = $qry_customer->FieldByName('city')." ".$qry_customer->FieldByName('zip');
		$ship_state = $state;
		$ship_state_code = $qry_customer->FieldByName('state_code');
		$ship_gstin = $qry_customer->FieldByName('gstin');
	}
	else {
		$ship_customer = $qry_customer->FieldByName('ship_company');
		$ship_address = $qry_customer->FieldByName('ship_address');
		$ship_address2 = $qry_customer->FieldByName('ship_address2');
		$ship_city = $qry_customer->FieldByName('ship_city')." ".$qry_customer->FieldByName('ship_zip');
		$ship_state_code = $qry_customer->FieldByName('ship_state_code');
		$ship_gstin = $qry_customer->FieldByName('ship_gstin');

		$qry_state = new Query("
	        SELECT state
	        FROM state_codes
	        WHERE id = ".$qry_customer->FieldByName('ship_state')
	    );
	    $ship_state = $qry_state->FieldByName('state');

	}    

	$filename = "order_".$qry_order->FieldByName('purchase_order_ref').".csv";

	header("Content-Type: application/text; name=".$filename);
	header("Content-Transfer-Encoding: binary");
	header("Content-Disposition: attachment; filename=".$filename);
	header("Expires: 0");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");

	$delimiter = "\t";	


    // Title
	echo 'TAX INVOICE'."\n";

    echo $company->FieldByName('legal_name')."\n";

    echo $company->FieldByName('trust')."\n";
    echo $company->FieldByName('address')."\n";
    echo $company->FieldByName('phone').", ".$company->FieldByName('email')."\n\n";



	/*
		OUR DETAILS
	*/
    echo 'GSTIN:'.$company->FieldByName('gstin')."\n";
    echo 'Invoice No.:'.$qry_bill->FieldByName('bill_number')."\n";
    echo 'Date:'.$qry_bill->FieldByName('date_created')."\n\n";


	/*
		CLIENT DETAILS (BILLED TO)
	*/
    echo 'Details of Receiver (Billed To)'."\n";
    echo 'Name:'.$customer."\n";
    echo 'Address:'.$address."\n";
    if (!empty($address2)) {
	    echo $address2."\n";
	}
    echo 'State:'.$city.", ".$state."\n";
    echo 'GSTIN:'.$gstin."\n\n\n";


    /*
    	HEADER FOR PRODUCT LIST
    */


    $header = 'SN'.$delimiter.
	    'Code'.$delimiter.
	    'Description'.$delimiter.
	    'HSN'.$delimiter.
	    'Qty'.$delimiter.
	    'Price'.$delimiter.
	    'Discount'.$delimiter.
	    'Taxable Value'.$delimiter;

	if ($qry_customer->FieldByName('is_other_state')=='Y') { 
		$header .= 'IGST Rate'.$delimiter.'IGST Amount';
	} else {
		$header .= 'CGST Rate'.$delimiter.'CGST Amount'.$delimiter.'SGST Rate'.$delimiter.'SGST Amount';
	}

	echo $header."\n";

    /*
    	PRODUCT LIST
    */

    $flt_total = 0;
	$tax_total = 0;
	$qty_total = 0;
	$taxable_value = 0;

	$h = 4;
	
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
        
        $total_quantity = $qry_items->FieldByName('quantity');
        
       	$flt_price = $qry_items->FieldByName('price') * $flt_price_increase;

		$flt_discount_price = $flt_price * (1 - ($qry_customer->FieldByName('discount')/100));
		
        if ($calculate_tax == 'Y') {
			if ($qry_items->FieldByName('tax_id')==NULL)
				$int_tax_id = 0;
			else
				$int_tax_id = $qry_items->FieldByName('tax_id');

            $tax_amount = calculateTax($flt_price , $int_tax_id);
	
            $item_total = $total_quantity * $flt_discount_price; 
        }
        else {
            $tax_amount = 0;
            $item_total = $total_quantity * $flt_discount_price;
        }
        
        $qry_tax->Query("
			SELECT * 
			FROM ".Monthalize('stock_tax')." st
			LEFT JOIN ".Monthalize('stock_tax_links')." stl ON (stl.tax_id = st.tax_id)
			LEFT JOIN ".Monthalize('stock_tax_definition')." std ON (std.definition_id = stl.tax_definition_id)
			WHERE st.tax_id = ".$qry_items->FieldByName('tax_id')
		);
			
        $flt_total += $item_total;


	    $row = ($i+1).$delimiter.$qry_items->FieldByName('product_code')
	    	.$delimiter.substr($qry_items->FieldByName('product_description'),0,30)
	    	.$delimiter.$qry_items->FieldByName('hsn')
	    	.$delimiter.number_format($total_quantity, 0, '.', '')
	    	.$delimiter.number_format($flt_price, 2, '.', '')
	    	.$delimiter.$qry_customer->FieldByName('discount')
			.$delimiter.number_format(($flt_discount_price * $total_quantity),2,'.',',').$delimiter;

		if ($qry_customer->FieldByName('is_other_state')=='Y') { 

			$tax = $qry_tax->FieldByName('definition_percent');
			$tax_rate = $tax;
			$tax_amount = $item_total * ($tax_rate / 100);
			$tax_total += $tax_amount;


			$int_index = getColumn($arr_taxes, $qry_items->FieldByName('tax_id'));
			if ($int_index > -1) {
				$arr_taxes[$int_index][2] += number_format(round((float)$tax_amount,3),2,'.','');
			}

			$row .= number_format($tax_rate, 2, '.', ',').'%'.$delimiter.number_format($tax_amount, 2, '.', ',');

		} else {

			$tax = $qry_tax->FieldByName('definition_percent');
			$tax_rate = $tax / 2;
			$tax_amount = $item_total * ($tax_rate / 100);
			$tax_total += ($tax_amount * 2);

			$int_index = getColumn($arr_taxes, $qry_items->FieldByName('tax_id'));
			if ($int_index > -1) {
				$arr_taxes[$int_index][2] += number_format(round((float)($tax_amount*2),3),2,'.','');
			}

			$row .= number_format($tax_rate, 2, '.', '')."%".$delimiter.
				number_format($tax_amount, 2, '.', '').$delimiter.
				number_format($tax_rate, 2, '.', '')."%".$delimiter.
				number_format($tax_amount, 2, '.', '');

		}
		$taxable_value += $tax_amount;
        $qty_total += $total_quantity;

        $qry_items->Next();

        echo $row."\n";
    }

	$flt_total = $flt_total + $tax_total;

	$flt_discount = $qry_bill->FieldByName('discount');
	
    $flt_handling = $qry_order->FieldByName('handling_charge');
    $flt_handling_tax = $flt_handling * 0.12;
    $flt_grand_total = $flt_total + $flt_handling + $flt_handling_tax - $flt_discount;
    $flt_total_due = $flt_grand_total - ($qry_order->FieldByName('advance_paid'));
    $flt_total_due = round($flt_total_due);

    $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
    $str_amount = $f->format($flt_total_due);


    /*
    	BOTTOM COLUMN TOTALS
    */
	if ($qry_customer->FieldByName('is_other_state')=='Y') {

		$str = number_format($qty_total).$delimiter.$delimiter.$delimiter.number_format(($flt_total-$tax_total),2).$delimiter.$delimiter.number_format(($taxable_value),2);
	} else {
		$str = number_format($qty_total).$delimiter.$delimiter.$delimiter.number_format(($flt_total-$tax_total),2).$delimiter.$delimiter.number_format(($taxable_value),2).$delimiter.$delimiter.number_format(($taxable_value),2);
	}

	echo $str."\n\n";

/*
	echo "Total Taxable Value".$delimiter.number_format(($flt_total-$tax_total),2);

	for ($i=0;$i<count($arr_taxes);$i++) {
		if ($arr_taxes[$i][2] > 0) {
			echo "Tax ".$arr_taxes[$i][1]."%".$delimiter.number_format($arr_taxes[$i][2],2,'.','');
		}
	}


	if (($flt_handling > 0) || ($flt_discount > 0) || ($qry_order->FieldByName('advance_paid') > 0)) {
		echo 'Subtotal:'.number_format(($flt_total),2);
	}

	if ($flt_handling > 0) {
		echo 'Handling:'.number_format($flt_handling, 2, '.', ',');
		echo 'Handling GST 12%:'.number_format($flt_handling_tax, 2, '.', ',');
	}

	if ($flt_discount > 0) {
		echo 'Discount:'.number_format($flt_discount, 2, '.', ',');
	}
	echo 'Total:'.number_format($flt_grand_total, 2, '.', ',');
*/
	/*
	if ($qry_order->FieldByName('advance_paid') > 0) {
	    $pdf->SetFont('Arial','B',8);
		$pdf->SetX($start);
		$pdf->Cell(30,5,'Amount Paid:',1,0,'R');
	    $pdf->SetFont('Arial','',8);
		$pdf->Cell(30,5,number_format($qry_order->FieldByName('advance_paid'), 2, '.', ','),1,0,'R');
		$pdf->Ln(5);

	    $pdf->SetFont('Arial','B',8);
		$pdf->SetX($start);
		$pdf->Cell(30,5,'Amount Due:',1,0,'R');
	    $pdf->SetFont('Arial','',8);
		$pdf->Cell(30,5,number_format($flt_total_due, 2, '.', ','),1,0,'R');
		$pdf->Ln(5);
	}
	*/

	echo "Rupees ".$str_amount."\n";


	echo 'Authorized Signatory'."\n\n\n\n";
	echo 'for '.$company->FieldByName('title')."\n";

?>