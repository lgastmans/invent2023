<?php
	require_once("../include/fpdf/fpdf.php");
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");
    require_once("../common/tax.php");
//    require_once("../Numbers/Words.php");
//    require_once("../common/number_to_words.php");
    require_once("../common/product_funcs.inc.php");


    /*
	
		ALTER TABLE `stock_rts_2017_12` ADD `invoice_number` VARCHAR(64) NULL AFTER `module_id`;
		ALTER TABLE `stock_rts_2017_12` ADD `invoice_date` DATE NULL AFTER `invoice_number`;

		ALTER TABLE `stock_supplier` ADD `is_other_state` CHAR(1) NOT NULL DEFAULT 'N' AFTER `is_active`;
	
	*/

    if (IsSet($_GET['id']))
        $int_id = $_GET['id'];

    /*
    	amount in words
    */
    function ExpandAmount($amount) {

//        $nw = new Numbers_Words();
		$nw = new NumberFormatter("en", NumberFormatter::SPELLOUT);

        if (strpos($amount,'.') !== false) {

            $numwords = explode('.',$amount);

            if (intval($numwords[1]) > 0)
                $res = $nw->format($numwords[0]).' and paise '.$nw->format($numwords[1]).' only';
            else
                $res = $nw->format($numwords[0]).' only';
        }
        else  {
            $res = $nw->format($amount);
        }
        return ucfirst($res);
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
//			if ($qry_tax_headers->FieldByName('definition_percent') > 0) {
				unset($arr_tmp);
				$arr_tmp['tax_id'] = $qry_tax_headers->FieldByName('tax_id');
				$arr_tmp['definition_percent'] = $qry_tax_headers->FieldByName('definition_percent');
				$arr_tmp['amount'] = 0.0;
				$arr_tmp['taxable_value'] = 0.0;
				$arr_taxes[] = $arr_tmp;
//			}
			$qry_tax_headers->Next();
		}
	}

	function getColumn($arr_dest, $int_tax_id) {
		$int_retval = -1;
		for ($i=0; $i<count($arr_dest); $i++) {
			//echo $arr_dest[$i]['tax_id'].":".$int_tax_id."<br>"; 
			if ($arr_dest[$i]['tax_id'] === $int_tax_id) {
				$int_retval = $i;
				break;
			}
		}
		return $int_retval;
	}


    /*
    	get debit note data
    */
    $sql = "
        SELECT sr.*, ss.*
        FROM ".Monthalize('stock_rts')." sr
        INNER JOIN stock_supplier ss ON (ss.supplier_id = sr.supplier_id)
        WHERE stock_rts_id = $int_id";
    $qry = new Query($sql);
//echo $sql;

    $sql = "
	    SELECT sri.*, sp.product_code, sp.product_description, sc.hsn
	    FROM ".Monthalize('stock_rts_items')." sri
	    LEFT JOIN stock_product sp ON (sp.product_id = sri.product_id)
		LEFT JOIN stock_category sc ON (sc.category_id = sp.category_id)
	    WHERE sri.rts_id = $int_id
	    ORDER BY sp.product_code";
	$qry_items = new Query($sql);
//echo $sql;

    /*
    	pdf filename
    */
    $filename = 'Debit Note '.$qry->FieldByName('bill_number')."-".$qry->FieldByName('supplier_name').".pdf";


    function get_date($str_mysql_date) {
        $str_date = substr($str_mysql_date, 0, 10);
        $arr_date = explode("-", $str_date);
        return $arr_date[2]."-".$arr_date[1]."-".$arr_date[0];
    }
    


class PDF extends FPDF
{
	function Header()
	{

	    $this->SetFont('Arial','B',10);
	    // Move to the right
	    $this->Cell(80);
    	$this->Cell(30,10,'DEBIT NOTE',0,0,'C');
	    $this->Ln(8);

	    $this->SetFont('Arial','B',14);
	    $this->Cell(80);
	    $this->Cell(30,10,$this->company->FieldByName('title'),0,0,'C');
	    $this->Ln(5);

		$this->SetFont('Arial','',10);

	    $this->Cell(80);
	    $this->Cell(30,10,$this->company->FieldByName('legal_name'),0,0,'C');
	    $this->Ln(5);

	    /*
	    $y = $this->GetY();
	    $pageWidth = $this->GetPageWidth();
	    $x = ($pageWidth/2) - 20;
		$this->Image('../settings/images/Invoice_Header.jpg',$x,$y+5,40);
		$this->Ln(12);
		*/

	    $this->Cell(80);
	    $this->Cell(30,10,$this->company->FieldByName('trust'),0,0,'C');
	    $this->Ln(5);
	    $this->Cell(80);
	    $this->Cell(30,10,$this->company->FieldByName('address'),0,0,'C');
	    $this->Ln(5);
	    $this->Cell(80);
	    $this->Cell(30,10,$this->company->FieldByName('phone').", ".$this->company->FieldByName('email'),0,0,'C');
	    $this->Ln(5);
	    $this->Cell(80);
		$this->Cell(30,10,'GSTIN:'.$this->company->FieldByName('gstin'),0,0,'C');
	    $this->Ln(5);

	    $this->Ln(5);
	}

	function Footer()
	{
	    $this->SetY(-15);
	    $this->SetFont('Arial','I',8);
	    $this->Cell(0,10,$this->company->FieldByName('footer'),0,0,'C');
	    $this->Cell(0,10,'Page '.$this->PageNo(),0,0,'R');
	}
}


	/*
		Instantiation of inherited class
	*/
	$pdf = new PDF();
	$pdf->company = $company;

	$pdf->AliasNbPages();
	$pdf->AddPage();

    define('L_MARGIN',7);
    define('LEN_SN', 5);
    define('LEN_CODE', 12);
    define('LEN_BATCH', 12);
    define('LEN_INVNO', 12);
    define('LEN_INVDT', 15);
    define('LEN_DESCRIPTION', 45);
    define('LEN_HSN', 17);
    define('LEN_QTY', 8);
    define('LEN_PRICE', 12);
    define('LEN_TAXABLE_VALUE', 12);
    define('LEN_DISCOUNT', 12);
    define('LEN_VALUE', 12);
    define('LEN_IGST', 12);
    define('LEN_SGST', 12);
    define('LEN_CGST', 12);

	$pdf->SetLeftMargin(L_MARGIN);    


	/*
		company details
	*/
	$pdf->SetFont('Arial','',10);


    /*
    	debit note details
    */
	$pdf->Cell(55,10,'Debit Note No.:',0,0,'R');
	$pdf->Cell(20,10,$qry->FieldByName('bill_number'),0,0,'L');
	$pdf->Cell(40);
	$pdf->Cell(30,10,'Date:',0,0,'R');
	$pdf->Cell(20,10,get_date($qry->FieldByName('date_created')),0,0,'L');
	$pdf->Ln(4);
	$pdf->Ln(4);

	$pdf->Cell(55,10,'Remarks:',0,0,'R');
	$pdf->Cell(5);
	$pdf->Cell(20,10,"   Price Change       Tax Change       Unit Request       Other",0,0,'L');
	$pdf->Ln(2);

	$y = $pdf->GetY()+1;
	$pdf->Rect(66, $y, 4, 4, 'D');
	$pdf->Rect(95, $y, 4, 4, 'D');
	$pdf->Rect(121, $y, 4, 4, 'D');
	$pdf->Rect(148, $y, 4, 4, 'D');
	$pdf->Ln(3);

	if (!empty($qry->FieldByName('description'))) {
		$pdf->Cell(55,10,'Note:',0,0,'R');
		$pdf->Cell(5);
		$pdf->Cell(20,10,$qry->FieldByName('description'),0,0,'L');
		$pdf->Ln(5);
	}


	$pdf->Cell(L_MARGIN,10,'TO:',0,0,'R');
	$str='';
	if (!empty($qry->FieldByName('trust')))
		$str = ", ".$qry->FieldByName('trust');
	$pdf->Cell(20,10,$qry->FieldByName('supplier_name').$str,0,0,'L');
	$pdf->Ln(4);

	if (!empty($qry->FieldByName('supplier_address'))) {
		$pdf->Cell(L_MARGIN,10,'',0,0,'R');
		$pdf->Cell(20,10,$qry->FieldByName('supplier_address'),0,0,'L');
		$pdf->Ln(4);
	}
	if ((!empty($qry->FieldByName('supplier_city'))) || (!empty($qry->FieldByName('supplier_zip'))) ) {
		$pdf->Cell(L_MARGIN,10,'',0,0,'R');
		$pdf->Cell(20,10,$qry->FieldByName('supplier_city')." ".$qry->FieldByName('supplier_zip'),0,0,'L');
		$pdf->Ln(4);
	}
	if (!empty($qry->FieldByName('supplier_state'))) {
		$pdf->Cell(L_MARGIN,10,'',0,0,'R');
		$pdf->Cell(20,10,$qry->FieldByName('supplier_state'),0,0,'L');
		$pdf->Ln(4);
	}
	if (!empty($qry->FieldByName('supplier_TIN'))) {
		$pdf->Cell(L_MARGIN,10,'',0,0,'R');
		$pdf->Cell(20,10,$qry->FieldByName('supplier_TIN'),0,0,'L');
		$pdf->Ln(4);
	}
	if (!empty($qry->FieldByName('gstin'))) {
		$pdf->Cell(L_MARGIN,10,'',0,0,'R');
		$pdf->Cell(20,10,"GSTIN: ".$qry->FieldByName('gstin'),0,0,'L');
		$pdf->Ln(4);
	}

	$pdf->Ln(10);


    /*
    	HEADER FOR PRODUCT LIST
    */
    $pdf->SetFont('Arial','B',7);


    $pdf->Cell(LEN_SN,10,'SN',1,0,'R');
    $pdf->Cell(LEN_CODE,10,'Code',1,0,'L');
    $pdf->Cell(LEN_BATCH,10,'Batch',1,0,'L');
    $pdf->Cell(LEN_INVNO,10,'Inv No',1,0,'L');
    $pdf->Cell(LEN_INVDT,10,'Inv Dt',1,0,'L');
    $pdf->Cell(LEN_DESCRIPTION,10,'Description',1,0,'C');
    $pdf->Cell(LEN_HSN,10,'HSN',1,0,'C');
    $pdf->Cell(LEN_QTY,10,'Qty',1,0,'C');
    //$pdf->Cell(15,10,'Price',1,0,'C');
    //$pdf->Cell(15,10,'Discount',1,0,'C');
    $y=$pdf->GetY();
    $x=$pdf->GetX();
    $pdf->MultiCell(LEN_PRICE,5,'Buying Price',1,'C');
    $pdf->SetXY($x+LEN_PRICE,$y);

    $y=$pdf->GetY();
    $x=$pdf->GetX();

	//if ($qry->FieldByName('trust') == $company->FieldByName('trust'))
	//	$pdf->MultiCell(LEN_TAXABLE_VALUE,5,'Value        ',1,'C');
	//else
    	$pdf->MultiCell(LEN_TAXABLE_VALUE,5,'Taxable Value',1,'C');

	//if ($qry->FieldByName('trust') !== $company->FieldByName('trust')) {

    	$pdf->SetXY($x+LEN_TAXABLE_VALUE,$y);

		if ($qry->FieldByName('is_other_state')=='Y') { 

		    $y=$pdf->GetY();
		    $x=$pdf->GetX();
			$pdf->MultiCell(LEN_IGST,5,'IGST Rate',1,'C');
		    $pdf->SetXY($x+LEN_IGST,$y);
			$pdf->MultiCell(LEN_IGST,5,'IGST Amount',1,'C');

		} else {

		    $y=$pdf->GetY();
		    $x=$pdf->GetX();
			$pdf->MultiCell(LEN_CGST,5,'CGST Rate',1,'C');
		    $pdf->SetXY($x+LEN_CGST,$y);

		    $y=$pdf->GetY();
		    $x=$pdf->GetX();
			$pdf->MultiCell(LEN_CGST,5,'CGST Amount',1,'C');
		    $pdf->SetXY($x+LEN_CGST,$y);

		    $y=$pdf->GetY();
		    $x=$pdf->GetX();
			$pdf->MultiCell(LEN_SGST,5,'SGST Rate',1,'C');
		    $pdf->SetXY($x+LEN_SGST,$y);

		    $y=$pdf->GetY();
		    $x=$pdf->GetX();
			$pdf->MultiCell(LEN_SGST,5,'SGST Amount',1,'C');
		    //$pdf->SetXY($x+15,$y);

		}
	//}

    $pdf->SetFont('Arial','',7);


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
        
        $calculate_tax = 'Y';

        $total_quantity = $qry_items->FieldByName('quantity');
        
       	$flt_price = $qry_items->FieldByName('bprice');

       	/*
       		This query is kept for backward compatibility, as prior to August 2019 the tax_id was not saved in the p.o. items
       		If this query is deleted in future, then the purchase order invoice details could be retrieved from qry_items
       	*/
		$qry_batch = new Query("
			SELECT sb.batch_code, sb.tax_id, po.invoice_number, po.invoice_date
			FROM ".Yearalize('stock_batch')." sb
			LEFT JOIN ".Yearalize('purchase_items')." pi ON (pi.batch_id = sb.batch_id)
			LEFT JOIN ".Yearalize('purchase_order')." po ON (po.purchase_order_id = pi.purchase_order_id)
			WHERE (sb.batch_id = ".$qry_items->FieldByName('batch_id').") AND
				(sb.storeroom_id = ".$_SESSION["int_current_storeroom"].")
			");

        if ($calculate_tax == 'Y') {

			if ($qry_items->FieldByName('tax_id')==0)
				$int_tax_id = $qry_batch->FieldByName('tax_id');
			else
				$int_tax_id = $qry_items->FieldByName('tax_id');

            $tax_amount = calculateTax($flt_price , $int_tax_id);
	
            //$flt_price = $qry_items->FieldByName('price'); //$qry_items->FieldByName('price') + $tax_amount;
            $item_total = $total_quantity * $flt_price; //($total_quantity * ($qry_items->FieldByName('price') + $tax_amount));
        }
        
        $qry_tax = new Query("
			SELECT * 
			FROM ".Monthalize('stock_tax')." st
			LEFT JOIN ".Monthalize('stock_tax_links')." stl ON (stl.tax_id = st.tax_id)
			LEFT JOIN ".Monthalize('stock_tax_definition')." std ON (std.definition_id = stl.tax_definition_id)
			WHERE st.tax_id = ".$int_tax_id
		);
			
        $flt_total += $item_total;


	    $pdf->Cell(LEN_SN,$h,($i+1),1,0,'R');
	    $pdf->Cell(LEN_CODE,$h,$qry_items->FieldByName('product_code'),1,0,'R');
	    $pdf->Cell(LEN_BATCH,$h,$qry_batch->FieldByName('batch_code'),1,0,'R');
	    $pdf->Cell(LEN_INVNO,$h,$qry_batch->FieldByName('invoice_number'),1,0,'R');
	    $pdf->Cell(LEN_INVDT,$h,get_date($qry_batch->FieldByName('invoice_date')),1,0,'R');
	    $pdf->Cell(LEN_DESCRIPTION,$h,$qry_items->FieldByName('product_description'),1,0,'L');
	    $pdf->Cell(LEN_HSN,$h,$qry_items->FieldByName('hsn'),1,0,'C');
	    $pdf->Cell(LEN_QTY,$h,number_format($total_quantity, 0, '.', ''),1,0,'C');
	    //$pdf->Cell(15,$h,number_format($flt_price, 2, '.', ''),1,0,'C');
	    $y=$pdf->GetY();
	    $x=$pdf->GetX();
	    $pdf->MultiCell(LEN_PRICE,$h,number_format($flt_price,2,'.',','),1,'R');
	    $pdf->SetXY($x+LEN_PRICE,$y);

	    $y=$pdf->GetY();
	    $x=$pdf->GetX();
	    $pdf->MultiCell(LEN_TAXABLE_VALUE,$h,number_format(($flt_price * $total_quantity),2,'.',','),1,'R');

		if ($qry->FieldByName('trust') !== $company->FieldByName('trust')) {

	    	$pdf->SetXY($x+LEN_TAXABLE_VALUE,$y);

			if ($qry->FieldByName('is_other_state')=='Y') { 

				$tax = $qry_tax->FieldByName('definition_percent');
				$tax_rate = $tax;
				$tax_amount = $item_total * ($tax_rate / 100);
				$tax_total += $tax_amount;

				$int_index = getColumn($arr_taxes, $int_tax_id); //$qry_items->FieldByName('tax_id'));
				if ($int_index > -1) {
					$arr_taxes[$int_index]['amount'] += number_format(round((float)$tax_amount,3),2,'.','');
					$arr_taxes[$int_index]['taxable_value'] += number_format(round((float)$item_total,3),2,'.','');
				}

			    $y=$pdf->GetY();
			    $x=$pdf->GetX();
				$pdf->MultiCell(LEN_IGST,$h,number_format($tax_rate, 2, '.', ',').'%',1,'R');
			    $pdf->SetXY($x+LEN_IGST,$y);
				$pdf->MultiCell(LEN_IGST,$h,number_format($tax_amount, 2, '.', ','),1,'R');

			} else {

				$tax = $qry_tax->FieldByName('definition_percent');
				$tax_rate = $tax / 2;
				$tax_amount = $item_total * ($tax_rate / 100);
				$tax_total += ($tax_amount * 2);


				$int_index = getColumn($arr_taxes, $int_tax_id); //$qry_items->FieldByName('tax_id'));
				if ($int_index > -1) {
					$arr_taxes[$int_index]['amount'] += number_format(round((float)($tax_amount*2),3),2,'.','');
					$arr_taxes[$int_index]['taxable_value'] += number_format(round((float)$item_total,3),2,'.','');
				}


			    $y=$pdf->GetY();
			    $x=$pdf->GetX();
				$pdf->MultiCell(LEN_CGST,$h,number_format($tax_rate, 2, '.', '')."%",1,'R');
			    $pdf->SetXY($x+LEN_CGST,$y);

			    $y=$pdf->GetY();
			    $x=$pdf->GetX();
				$pdf->MultiCell(LEN_CGST,$h,number_format($tax_amount, 2, '.', ''),1,'R');
			    $pdf->SetXY($x+LEN_CGST,$y);

			    $y=$pdf->GetY();
			    $x=$pdf->GetX();
				$pdf->MultiCell(LEN_SGST,$h,number_format($tax_rate, 2, '.', '')."%",1,'R');
			    $pdf->SetXY($x+LEN_SGST,$y);

			    $y=$pdf->GetY();
			    $x=$pdf->GetX();
				$pdf->MultiCell(LEN_SGST,$h,number_format($tax_amount, 2, '.', ''),1,'R');

			}
		}

		$taxable_value += $tax_amount;
        $qty_total += $total_quantity;

        $qry_items->Next();

        $pdf->Ln(0);

    }

	$flt_total = $flt_total + $tax_total;

	$flt_discount = 0;//$qry->FieldByName('discount');
	
/*
	$flt_grand_total = $flt_total + $flt_handling + $flt_handling_tax - $flt_discount;

    /*
    	BOTTOM COLUMN TOTALS
    */

	if ($qry->FieldByName('is_other_state')=='Y') {
		
		$start = L_MARGIN + LEN_SN + LEN_CODE + LEN_BATCH + LEN_INVNO + LEN_INVDT + LEN_DESCRIPTION + LEN_HSN;
		$pdf->SetX($start);
		
		$pdf->Cell(LEN_QTY,5,number_format(($qty_total)),1,0,'C');
	    $pdf->Cell(LEN_PRICE,5,'',1,0,'R');
	    $pdf->Cell(LEN_TAXABLE_VALUE,5,number_format(($flt_total-$tax_total),2),1,0,'R');
	    if ($qry->FieldByName('trust') !== $company->FieldByName('trust')) {
			$pdf->Cell(LEN_IGST,5,'',1,0,'R');
	    	$pdf->Cell(LEN_IGST,5,number_format(($taxable_value),2),1,0,'R');
	    }
	    $l_margin = 98;

	} else {

		$start = L_MARGIN + LEN_SN + LEN_CODE + LEN_BATCH + LEN_INVNO + LEN_INVDT + LEN_DESCRIPTION + LEN_HSN;
		$pdf->SetX($start);

		$pdf->Cell(LEN_QTY,5,number_format(($qty_total)),1,0,'C');
	    $pdf->Cell(LEN_PRICE,5,'',1,0,'R');
	    $pdf->Cell(LEN_TAXABLE_VALUE,5,number_format(($flt_total-$tax_total),2),1,0,'R');
		if ($qry->FieldByName('trust') !== $company->FieldByName('trust')) {
			$pdf->Cell(LEN_CGST,5,'',1,0,'R');
		    $pdf->Cell(LEN_CGST,5,number_format(($taxable_value),2),1,0,'R');
			$pdf->Cell(LEN_SGST,5,'',1,0,'R');
		    $pdf->Cell(LEN_SGST,5,number_format(($taxable_value),2),1,0,'R');
		}
	    $l_margin = 113;

	}

	$pdf->Ln(5);

	if ($qry->FieldByName('is_other_state')=='Y') {
		if ($qry->FieldByName('trust') == $company->FieldByName('trust')) {
			$start = L_MARGIN + LEN_SN + LEN_CODE + LEN_BATCH + LEN_INVNO + LEN_INVDT + LEN_DESCRIPTION + LEN_HSN + LEN_QTY;
			$width = LEN_PRICE;
		}
		else {
			$start = L_MARGIN + LEN_SN + LEN_CODE + LEN_BATCH + LEN_INVNO + LEN_INVDT + LEN_DESCRIPTION + LEN_HSN + LEN_QTY;
			$width = LEN_PRICE + LEN_TAXABLE_VALUE;
		}
	}
	else {
		if ($qry->FieldByName('trust') == $company->FieldByName('trust')) {
			$start = L_MARGIN + LEN_SN + LEN_CODE + LEN_BATCH + LEN_INVNO + LEN_INVDT + LEN_DESCRIPTION + LEN_HSN + LEN_QTY;
			$width = LEN_PRICE;
		}
		else {
			$start = L_MARGIN + LEN_SN + LEN_CODE + LEN_BATCH + LEN_INVNO + LEN_INVDT + LEN_DESCRIPTION + LEN_HSN + LEN_QTY + LEN_PRICE + LEN_VALUE;
			$width = LEN_CGST + LEN_SGST;
		}
	}

	$pdf->SetFont('Arial','B',8);
	$pdf->SetX($start);
	$pdf->Cell($width,5,"Subtotal",1,0,'R');
	$pdf->SetFont('Arial','',8);
	$pdf->Cell($width,5,number_format(($flt_total-$tax_total),2),1,0,'R');
	$pdf->Ln(5);

	/*
		the separate tax totals
	*/

	for ($i=0;$i<count($arr_taxes);$i++) {

		if ($arr_taxes[$i]['amount'] > 0) {

		    $pdf->SetFont('Arial','B',8);
			$pdf->SetX($start);
			$pdf->Cell($width,5,"Tax ".$arr_taxes[$i]['definition_percent']."%",1,0,'R');
		    $pdf->SetFont('Arial','',8);
			$pdf->Cell($width,5,number_format($arr_taxes[$i]['amount'],2,'.',''),1,0,'R');
			$pdf->Ln(5);
		}
	}

    $pdf->SetFont('Arial','B',8);
	$pdf->SetX($start);
	$pdf->Cell($width,5,'Total:',1,0,'R');
    $pdf->SetFont('Arial','',8);
	$pdf->Cell($width,5,number_format(($flt_total),2),1,0,'R');
	$pdf->Ln(5);


    $str_amount = ExpandAmount(number_format($flt_total,2,'.',''));
	$pdf->SetY($y);
	$pdf->MultiCell(130, 32, "Rupees ".$str_amount, 0, 'L');


	/*
		tabular details of taxes
	*/
    define('LN_HSN', 40);
    define('LN_TAXABLE_VALUE', 25);
    define('LN_IGST', 22);
    define('LN_SGST', 22);
    define('LN_CGST', 22);
    define('LN_TOTAL', 30);
    define('ROW_HEIGHT',3);

    $pdf->Cell(LN_HSN, (ROW_HEIGHT*2), 'HSN',1,0,'C');
	$pdf->Cell(LN_TAXABLE_VALUE, (ROW_HEIGHT*2), 'Taxable Value',1,0,'C');

	if ($qry->FieldByName('is_other_state')=='Y') { 

		$pdf->Cell(LN_IGST, (ROW_HEIGHT*2), 'IGST Rate',1,0,'C');
		$pdf->Cell(LN_IGST, (ROW_HEIGHT*2), 'IGST Amount',1,0,'C');

	} else {

		$pdf->Cell(LN_CGST, (ROW_HEIGHT*2), 'CGST Rate',1,0,'C');
		$pdf->Cell(LN_CGST, (ROW_HEIGHT*2), 'CGST Amount',1,0,'C');

		$pdf->Cell(LN_SGST, (ROW_HEIGHT*2), 'SGST Rate',1,0,'C');
		$pdf->Cell(LN_SGST, (ROW_HEIGHT*2), 'SGST Amount',1,0,'C');

	}

    $y=$pdf->GetY();
    $x=$pdf->GetX();
	$pdf->MultiCell(LN_TOTAL, (ROW_HEIGHT*2), 'Total Tax Amount',1,'C');
    $pdf->SetXY($x+LN_IGST,$y);

	$pdf->Ln((ROW_HEIGHT*2));

    $start = L_MARGIN;

    $total_taxable_value = 0;
    $total_amount = 0;

    foreach ($arr_taxes as $row) {

    	if ($row['taxable_value'] > 0) {

			$pdf->SetX($start);
			$pdf->Cell(LN_HSN, ROW_HEIGHT, '',1,0,'R');
			$pdf->Cell(LN_TAXABLE_VALUE, ROW_HEIGHT, number_format($row['taxable_value'],2,'.',','),1,0,'R');

			if ($qry->FieldByName('is_other_state')=='Y') { 
				$pdf->Cell(LN_IGST, ROW_HEIGHT, $row['definition_percent']."%",1,0,'R');
				$pdf->Cell(LN_IGST, ROW_HEIGHT, number_format($row['amount'],2,'.',''),1,0,'R');
			}
			else {
				$pdf->Cell(LN_SGST, ROW_HEIGHT, ($row['definition_percent']/2)."%",1,0,'R');
				$pdf->Cell(LN_SGST, ROW_HEIGHT, number_format(($row['amount']/2),2,'.',''),1,0,'R');
				$pdf->Cell(LN_SGST, ROW_HEIGHT, ($row['definition_percent']/2)."%",1,0,'R');
				$pdf->Cell(LN_SGST, ROW_HEIGHT, number_format(($row['amount']/2),2,'.',''),1,0,'R');
			}
			$pdf->Cell(LN_TOTAL, ROW_HEIGHT, number_format($row['amount'],2,'.',''),1,0,'R');

			$pdf->Ln(ROW_HEIGHT);

			$total_taxable_value += $row['taxable_value'];
			$total_amount += $row['amount'];
    	}
    }

    /*
    	tax totals at bottom of table
    */
    $pdf->SetFont('Arial','B',8);
	$pdf->Cell(LN_HSN, ROW_HEIGHT, 'Totals',1,0,'R');
	$pdf->Cell(LN_TAXABLE_VALUE, ROW_HEIGHT, number_format($total_taxable_value,2,'.',','),1,0,'R');

	if ($qry->FieldByName('is_other_state')=='Y') { 
		$pdf->Cell(LN_SGST, ROW_HEIGHT, '',1,0,'R');
		$pdf->Cell(LN_SGST, ROW_HEIGHT, number_format($total_amount,2,'.',''),1,0,'R');
	}
	else {
		$pdf->Cell(LN_SGST, ROW_HEIGHT, '',1,0,'R');
		$pdf->Cell(LN_SGST, ROW_HEIGHT, number_format(($total_amount/2),2,'.',''),1,0,'R');
		$pdf->Cell(LN_SGST, ROW_HEIGHT, '',1,0,'R');
		$pdf->Cell(LN_SGST, ROW_HEIGHT, number_format(($total_amount/2),2,'.',''),1,0,'R');
	}
	$pdf->Cell(LN_TOTAL, ROW_HEIGHT, number_format($total_amount,2,'.',''),1,0,'R');



    /*
    	footer
    */
	$pdf->Ln(15);
    $pdf->SetFont('Arial','B',8);
	$pdf->SetX(10);
	$pdf->Cell(30,5,'Prepared By',0,0,'L');

	$pdf->Ln(10);
    $pdf->SetFont('Arial','B',8);
	$pdf->SetX(10);
	$pdf->Cell(30,5,'Received By',0,0,'L');
	$pdf->SetX(142);
	$pdf->Cell(30,5,'Verified By',0,0,'R');
	$pdf->Ln(5);

    $pdf->SetFont('Arial','',8);
	$pdf->SetX(10);
	$pdf->Cell(30,5,$qry->FieldByName('supplier_name'),0,0,'L');
	$pdf->SetX(142);
	$pdf->Cell(30,5,'for '.$company->FieldByName('title'),0,0,'R');
	$pdf->Ln(5);

	$title = 'Debit Note';
	$pdf->SetTitle($company->FieldByName('title'));
	$pdf->SetAuthor($company->FieldByName('title'));

	$pdf->Output($filename, 'I');
?>