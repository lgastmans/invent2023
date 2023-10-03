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


/*    $qry_items = new Query("
        SELECT *
        FROM ".Monthalize('order_items')." oi
        LEFT JOIN stock_product sp ON (sp.product_id = oi.product_id)
		LEFT JOIN stock_category sc ON (sc.category_id = sp.category_id)
        WHERE order_id = ".$qry_order->FieldByName('order_id')
    );
*/

    $sql_items = "
        SELECT *, sp.tax_id as tax_id, SUM(bi.quantity + bi.adjusted_quantity) AS quantity
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
    $filename = 'PROFORMA '.sprintf('%03d', $qry_order->FieldByName('order_reference')).$suffix." - ".$qry_customer->FieldByName('company').".pdf";

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

class PDF extends FPDF
{
	// Page header
	function Header()
	{
	    // Logo
	    //$this->Image('logo.png',10,6,30);
	    // Arial bold 15
	    $this->SetFont('Arial','B',10);
	    // Move to the right
	    $this->Cell(80);

	    // Title
    	$this->Cell(30,10,'PROFORMA',0,0,'C');
	    $this->Ln(5);

	    $y = $this->GetY();
	    $pageWidth = $this->GetPageWidth();
	    $imgWidth = 20;
	    $x = ($pageWidth/2) - ($imgWidth/2);
		$this->Image('../settings/images/Invoice_Header.jpg',$x,$y+5,$imgWidth);
		$this->Ln(12);

	    $this->SetFont('Arial','B',14);
	    $this->Cell(80);
	    $this->Cell(30,10,$this->company->FieldByName('legal_name'),0,0,'C');
	    $this->Ln(5);

		$this->SetFont('Arial','',10);
	    $this->Cell(80);
	    $this->Cell(30,10,$this->company->FieldByName('trust'),0,0,'C');
	    //$this->Cell(40);
	    //$this->Cell(80,10,'Original Invoice',0,0,'R');
	    $this->Ln(5);
	    $this->Cell(80);
	    $this->Cell(30,10,$this->company->FieldByName('address'),0,0,'C');
	    $this->Ln(5);
	    $this->Cell(80);
	    $this->Cell(30,10,$this->company->FieldByName('phone').", ".$this->company->FieldByName('email'),0,0,'C');
	    $this->Ln(5);

	    $this->Ln(5);
	}

	// Page footer
	function Footer()
	{
	    // Position at 1.5 cm from bottom
	    $this->SetY(-15);
	    // Arial italic 8
	    $this->SetFont('Arial','I',8);
	    // Page number
	    $this->Cell(0,10,$this->company->FieldByName('footer'),0,0,'C');
	}
}


	/*
		Instantiation of inherited class
	*/
	$pdf = new PDF();
	$pdf->company = $company;

	$pdf->AliasNbPages();
	$pdf->AddPage();

	$pdf->SetLeftMargin(10);


	/*
		OUR DETAILS
	*/
	$pdf->SetFont('Arial','',10);

    $pdf->Cell(55,10,'GSTIN:',0,0,'R');
    $pdf->Cell(20,10,$pdf->company->FieldByName('gstin'),0,0,'L');
    $pdf->Cell(40);
    $pdf->Cell(30,10,'Transportation Mode:',0,0,'R');
    $pdf->Ln(4);

    $pdf->Cell(55,10,'Tax Is Payable On Reverse Charge:',0,0,'R');
    $pdf->Cell(20,10,'N',0,0,'L');
    $pdf->Cell(40);
    $pdf->Cell(30,10,'Veh. No.:',0,0,'R');
    $pdf->Ln(4);

    $pdf->Cell(55,10,'Proforma No.:',0,0,'R');
    $pdf->Cell(20,10,$qry_order->FieldByName('order_reference').$suffix,0,0,'L');

    $pdf->Cell(40);
    $pdf->Cell(30,10,'Date & Time of Supply:',0,0,'R');

    $str_date = substr($qry_bill->FieldByName('supply_date_time'), 0, 10);
    $arr_date = explode("-", $str_date);    
    
    if (intval($arr_date[0]) > 1970) {
    	$pdf->Cell(20,10,$qry_bill->FieldByName('supply_date_time'));
    }
    else
    	$pdf->Cell(20,10,'');
    $pdf->Ln(4);

    $pdf->Cell(55,10,'Date:',0,0,'R');
    $pdf->Cell(20,10,get_date($qry_bill->FieldByName('date_created')),0,0,'L');
    $pdf->Cell(40);
    $pdf->Cell(30,10,'Place of Supply:',0,0,'R');
    $pdf->Cell(20,10,$qry_bill->FieldByName('supply_place'));
    $pdf->Ln(8);


	/*
		CLIENT DETAILS (BILLED TO)
	*/
	$col2 = 70;

	$pdf->SetFont('Arial','B',10);
    $pdf->Cell(100,10,'Details of Receiver (Billed To)',0,0,'L');
    $pdf->Cell(60,10,'Details of Consignee (Shipped To)',0,0,'L');
    $pdf->Ln(4);

    $pdf->SetFont('Arial','',10);

    $pdf->Cell(20,10,'Name:',0,0,'R');
    $pdf->Cell(20,10,$customer,0,0,'L');
    $pdf->Cell($col2,10,'Name:',0,0,'R');
    $pdf->Cell(20,10,$ship_customer,0,0,'L');
    $pdf->Ln(4);

    $pdf->Cell(20,10,'Address:',0,0,'R');
    $pdf->Cell(20,10,$address,0,0,'L');
    $pdf->Cell($col2,10,'Address:',0,0,'R');
    $pdf->Cell(20,10,$ship_address,0,0,'L');
    $pdf->Ln(4);

    if (!empty($address2)) {
	    $pdf->Cell(20,10,'',0,0,'R');
	    $pdf->Cell(20,10,$address2,0,0,'L');
	    $pdf->Cell($col2,10,'',0,0,'R');
	    $pdf->Cell(20,10,$ship_address2,0,0,'L');
	    $pdf->Ln(4);
	}

    $pdf->Cell(20,10,'State:',0,0,'R');
    $pdf->Cell(20,10,$city.", ".$state,0,0,'L');
    $pdf->Cell($col2,10,'State:',0,0,'R');
    $pdf->Cell(20,10,$ship_city.", ".$ship_state,0,0,'L');
    $pdf->Ln(4);

    $pdf->Cell(20,10,'State Code:',0,0,'R');
    $pdf->Cell(20,10,$state_code,0,0,'L');
    $pdf->Cell($col2,10,'State Code:',0,0,'R');
    $pdf->Cell(20,10,$ship_state_code,0,0,'L');
    $pdf->Ln(4);

    $pdf->Cell(20,10,'GSTIN:',0,0,'R');
    $pdf->Cell(20,10,$gstin,0,0,'L');
    $pdf->Cell($col2,10,'GSTIN:',0,0,'R');
    $pdf->Cell(20,10,$ship_gstin,0,0,'L');

	if (!empty($cell)) {
	    $pdf->Ln(4);

	    $pdf->Cell(20,10,'Ph:',0,0,'R');
	    $pdf->Cell(20,10,$cell,0,0,'L');
	    $pdf->Cell($col2,10,'Ph:',0,0,'R');
	    $pdf->Cell(20,10,$cell,0,0,'L');
	}

    $pdf->Ln(10);


    /*
    	HEADER FOR PRODUCT LIST
    */
    $pdf->SetFont('Arial','B',8);


    $pdf->Cell(7,10,'SN',1,0,'R');
    $pdf->Cell(14,10,'Code',1,0,'R');
    $pdf->Cell(40,10,'Description',1,0,'C');
    $pdf->Cell(17,10,'HSN',1,0,'C');
    $pdf->Cell(10,10,'Qty',1,0,'C');
    $pdf->Cell(15,10,'Price',1,0,'C');
    $pdf->Cell(15,10,'Discount',1,0,'C');
    $y=$pdf->GetY();
    $x=$pdf->GetX();
    $pdf->MultiCell(15,5,'Taxable Value',1,'C');
    $pdf->SetXY($x+15,$y);

	if ($qry_customer->FieldByName('is_other_state')=='Y') { 

	    $y=$pdf->GetY();
	    $x=$pdf->GetX();
		$pdf->MultiCell(15,5,'IGST Rate',1,'C');
	    $pdf->SetXY($x+15,$y);
		$pdf->MultiCell(15,5,'IGST Amount',1,'C');

	} else {

	    $y=$pdf->GetY();
	    $x=$pdf->GetX();
		$pdf->MultiCell(15,5,'CGST Rate',1,'C');
	    $pdf->SetXY($x+15,$y);

	    $y=$pdf->GetY();
	    $x=$pdf->GetX();
		$pdf->MultiCell(15,5,'CGST Amount',1,'C');
	    $pdf->SetXY($x+15,$y);

	    $y=$pdf->GetY();
	    $x=$pdf->GetX();
		$pdf->MultiCell(15,5,'SGST Rate',1,'C');
	    $pdf->SetXY($x+15,$y);

	    $y=$pdf->GetY();
	    $x=$pdf->GetX();
		$pdf->MultiCell(15,5,'SGST Amount',1,'C');
	    //$pdf->SetXY($x+15,$y);

	}

    $pdf->SetFont('Arial','',8);


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
        
        // if ($proforma=='Y') 
        // 	$flt_price = getSellingPrice($qry_items->FieldByName('product_id'));
        // else
        	$flt_price = $qry_items->FieldByName('price') * $flt_price_increase;

		//$flt_discount_price = $flt_price * (1 - ($qry_customer->FieldByName('discount')/100));
		$flt_discount_price = $flt_price * (1 - ($qry_bill->FieldByName('discount')/100));
		
        if ($calculate_tax == 'Y') {
			if ($qry_items->FieldByName('tax_id')==NULL)
				$int_tax_id = 0;
			else
				$int_tax_id = $qry_items->FieldByName('tax_id');
//echo $int_tax_id;
            $tax_amount = calculateTax($flt_price , $int_tax_id);
	
            //$flt_price = $qry_items->FieldByName('price'); //$qry_items->FieldByName('price') + $tax_amount;
            $item_total = $total_quantity * $flt_discount_price; //($total_quantity * ($qry_items->FieldByName('price') + $tax_amount));
        }
        else {
            $tax_amount = 0;
            //$flt_price = $qry_items->FieldByName('price');
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


	    $pdf->Cell(7,$h,($i+1),1,0,'R');
	    $pdf->Cell(14,$h,$qry_items->FieldByName('product_code'),1,0,'R');
	    $pdf->Cell(40,$h,substr($qry_items->FieldByName('product_description'),0,30),1,0,'L');
	    $pdf->Cell(17,$h,$qry_items->FieldByName('hsn'),1,0,'C');
	    $pdf->Cell(10,$h,number_format($total_quantity, 0, '.', ''),1,0,'C');
	    $pdf->Cell(15,$h,number_format($flt_price, 2, '.', ''),1,0,'C');
	    $pdf->Cell(15,$h,$qry_bill->FieldByName('discount'),1,0,'C');

	    $y=$pdf->GetY();
	    $x=$pdf->GetX();
	    $pdf->MultiCell(15,$h,number_format(($flt_discount_price * $total_quantity),2,'.',','),1,'R');
	    $pdf->SetXY($x+15,$y);


		if ($qry_customer->FieldByName('is_other_state')=='Y') { 

			$tax = $qry_tax->FieldByName('definition_percent');
			$tax_rate = $tax;
			$tax_amount = $item_total * ($tax_rate / 100);
			$tax_total += $tax_amount;


			$int_index = getColumn($arr_taxes, $qry_items->FieldByName('tax_id'));
			if ($int_index > -1) {
				$arr_taxes[$int_index][2] += number_format(round((float)$tax_amount,3),2,'.','');
			}

		    $y=$pdf->GetY();
		    $x=$pdf->GetX();
			$pdf->MultiCell(15,$h,number_format($tax_rate, 2, '.', ',').'%',1,'R');
		    $pdf->SetXY($x+15,$y);
			$pdf->MultiCell(15,$h,number_format($tax_amount, 2, '.', ','),1,'R');
			//$pdf->SetXY($x+15,$y);

		} else {

			$tax = $qry_tax->FieldByName('definition_percent');
			$tax_rate = $tax / 2;
			$tax_amount = $item_total * ($tax_rate / 100);
			$tax_total += ($tax_amount * 2);


			$int_index = getColumn($arr_taxes, $qry_items->FieldByName('tax_id'));
//			echo $int_index.">".$qry_items->FieldByName('tax_id').">";
			if ($int_index > -1) {
//				echo $tax_amount."::";
				$arr_taxes[$int_index][2] += number_format(round((float)($tax_amount*2),3),2,'.','');
			}


		    $y=$pdf->GetY();
		    $x=$pdf->GetX();
			$pdf->MultiCell(15,$h,number_format($tax_rate, 2, '.', '')."%",1,'R');
		    $pdf->SetXY($x+15,$y);

		    $y=$pdf->GetY();
		    $x=$pdf->GetX();
			$pdf->MultiCell(15,$h,number_format($tax_amount, 2, '.', ''),1,'R');
		    $pdf->SetXY($x+15,$y);

		    $y=$pdf->GetY();
		    $x=$pdf->GetX();
			$pdf->MultiCell(15,$h,number_format($tax_rate, 2, '.', '')."%",1,'R');
		    $pdf->SetXY($x+15,$y);

		    $y=$pdf->GetY();
		    $x=$pdf->GetX();
			$pdf->MultiCell(15,$h,number_format($tax_amount, 2, '.', ''),1,'R');
		    //$pdf->SetXY($x+15,$y);

		}
		$taxable_value += $tax_amount;
        $qty_total += $total_quantity;

        $qry_items->Next();

        $pdf->Ln(0);

    }

	$flt_total = $flt_total + $tax_total;

	$flt_discount = 0; //$qry_bill->FieldByName('discount');
	
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

		$pdf->SetX(88);
		$pdf->Cell(10,5,number_format(($qty_total)),1,0,'C');
		$pdf->Cell(15,5,'',1,0,'R');
		$pdf->Cell(15,5,'',1,0,'R');
	    $pdf->Cell(15,5,number_format(($flt_total-$tax_total),2),1,0,'R');
		$pdf->Cell(15,5,'',1,0,'R');
	    $pdf->Cell(15,5,number_format(($taxable_value),2),1,0,'R');

	    $l_margin = 113;

	} else {

		$pdf->SetX(88);
		$pdf->Cell(10,5,number_format(($qty_total)),1,0,'C');
		$pdf->Cell(15,5,'',1,0,'R');
		$pdf->Cell(15,5,'',1,0,'R');
	    $pdf->Cell(15,5,number_format(($flt_total-$tax_total),2),1,0,'R');
		$pdf->Cell(15,5,'',1,0,'R');
	    $pdf->Cell(15,5,number_format(($taxable_value),2),1,0,'R');
		$pdf->Cell(15,5,'',1,0,'R');
	    $pdf->Cell(15,5,number_format(($taxable_value),2),1,0,'R');

	    $l_margin = 143;

	}

	$pdf->Ln(5);


	$pdf->SetFont('Arial','B',8);
	$pdf->SetX($l_margin);
	$pdf->Cell(30,5,"Total Taxable Value",1,0,'R');
	$pdf->SetFont('Arial','',8);
	$pdf->Cell(30,5,number_format(($flt_total-$tax_total),2),1,0,'R');
	$pdf->Ln(5);

	for ($i=0;$i<count($arr_taxes);$i++) {
		if ($arr_taxes[$i][2] > 0) {
		    $pdf->SetFont('Arial','B',8);
			$pdf->SetX($l_margin);
			$pdf->Cell(30,5,"Tax ".$arr_taxes[$i][1]."%",1,0,'R');
		    $pdf->SetFont('Arial','',8);
			$pdf->Cell(30,5,number_format($arr_taxes[$i][2],2,'.',''),1,0,'R');
			$pdf->Ln(5);
		}
	}



    $pdf->SetFont('Arial','B',8);
	$pdf->SetX($l_margin);
	$pdf->Cell(30,5,'Subtotal:',1,0,'R');
    $pdf->SetFont('Arial','',8);
	$pdf->Cell(30,5,number_format(($flt_total),2),1,0,'R');
	$pdf->Ln(5);

    $pdf->SetFont('Arial','B',8);
	$pdf->SetX($l_margin);
	$pdf->Cell(30,5,'Handling:',1,0,'R');
    $pdf->SetFont('Arial','',8);
	$pdf->Cell(30,5,number_format($flt_handling, 2, '.', ','),1,0,'R');
	$pdf->Ln(5);

    $pdf->SetFont('Arial','B',8);
	$pdf->SetX($l_margin);
	$pdf->Cell(30,5,'Handling GST 12%:',1,0,'R');
    $pdf->SetFont('Arial','',8);
	$pdf->Cell(30,5,number_format($flt_handling_tax, 2, '.', ','),1,0,'R');
	$pdf->Ln(5);

    $pdf->SetFont('Arial','B',8);
	$pdf->SetX($l_margin);
	$pdf->Cell(30,5,'Discount:',1,0,'R');
    $pdf->SetFont('Arial','',8);
	$pdf->Cell(30,5,number_format($flt_discount, 2, '.', ','),1,0,'R');
	$pdf->Ln(5);

	$y = $pdf->getY();

    $pdf->SetFont('Arial','B',8);
	$pdf->SetX($l_margin);
	$pdf->Cell(30,5,'Total:',1,0,'R');
    $pdf->SetFont('Arial','',8);
	$pdf->Cell(30,5,number_format($flt_grand_total, 2, '.', ','),1,0,'R');
	$pdf->Ln(5);

    $pdf->SetFont('Arial','B',8);
	$pdf->SetX($l_margin);
	$pdf->Cell(30,5,'Amount Paid:',1,0,'R');
    $pdf->SetFont('Arial','',8);
	$pdf->Cell(30,5,number_format($qry_order->FieldByName('advance_paid'), 2, '.', ','),1,0,'R');
	$pdf->Ln(5);

    $pdf->SetFont('Arial','B',8);
	$pdf->SetX($l_margin);
	$pdf->Cell(30,5,'Amount Due:',1,0,'R');
    $pdf->SetFont('Arial','',8);
	$pdf->Cell(30,5,number_format($flt_total_due, 2, '.', ','),1,0,'R');
	$pdf->Ln(5);

	$pdf->SetY($y);
	$pdf->MultiCell(130, 12, "Rupees ".$str_amount, 0, 'L');

	$pdf->Ln(10);

    $pdf->SetFont('Arial','I',8);
	$pdf->SetX(162);
	$pdf->Cell(30,5,'Authorized Signatory',0,0,'R');
	$pdf->Ln(15);
	$pdf->SetX(162);
	$pdf->Cell(30,5,'for '.$company->FieldByName('title'),0,0,'R');
	$pdf->Ln(5);

	$title = 'Invoice';
	$pdf->SetTitle($title);
	$pdf->SetAuthor($company->FieldByName('title'));

	$pdf->Output($filename, 'I');
?>