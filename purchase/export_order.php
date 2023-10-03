<?php
	require_once("../include/fpdf/fpdf.php");
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");
    require_once("../common/tax.php");
    //require_once("../Numbers/Words.php");
    require_once("../common/number_to_words.php");
    require_once("../common/product_funcs.inc.php");

    /*
	
		ALTER TABLE `stock_rts_2017_12` ADD `invoice_number` VARCHAR(64) NULL AFTER `module_id`;
		ALTER TABLE `stock_rts_2017_12` ADD `invoice_date` DATE NULL AFTER `invoice_number`;

		ALTER TABLE `stock_supplier` ADD `is_other_state` CHAR(1) NOT NULL DEFAULT 'N' AFTER `is_active`;
	
	*/
	$int_id = 0;
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
        $res = 'Rupees '.$res;
        return ucfirst($res);
    }

    function get_date($str_mysql_date) {
        $str_date = substr($str_mysql_date, 0, 10);
        $arr_date = explode("-", $str_date);
        return $arr_date[2]."-".$arr_date[1]."-".$arr_date[0];
    }


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


	$sql = "
		SELECT *
		FROM ".Yearalize('purchase_order')." po 
		LEFT JOIN stock_supplier ss ON (ss.supplier_id = po.supplier_id)
		WHERE (po.purchase_order_id = ".$int_id.")
	";
	$qry_order = new Query($sql);

//echo $sql;die();

	$sql = "
		SELECT *
		FROM ".Yearalize('purchase_items')." pi
		INNER JOIN stock_product sp ON (sp.product_id = pi.product_id)
		LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
		LEFT JOIN stock_supplier ss ON (ss.supplier_id = pi.supplier_id)
		LEFT JOIN stock_category sc ON (sc.category_id = sp.category_id)
		WHERE (pi.purchase_order_id = ".$int_id.")
	";
	$qry_items = new Query($sql);		

//echo $sql; die();





    /*
    	pdf filename
    */
	$filename = 'Purchase Order '.$qry_order->FieldByName('purchase_order_ref')."-".$qry_order->FieldByName('supplier_name').".pdf";


class PDF extends FPDF
{
	function Header()
	{

	    $this->SetFont('Arial','B',10);
	    // Move to the right
	    $this->Cell(80);
    	$this->Cell(30,10,'Purchase Order',0,0,'C');
	    $this->Ln(8);

	    $this->SetFont('Arial','B',14);
	    $this->Cell(80);
	    $this->Cell(30,10,$this->company->FieldByName('title'),0,0,'C');
	    $this->Ln(5);

		$this->SetFont('Arial','',10);

	    $this->Cell(80);
	    $this->Cell(30,10,$this->company->FieldByName('legal_name'),0,0,'C');
	    $this->Ln(5);

	    $this->Cell(80);
	    $this->Cell(30,10,$this->company->FieldByName('trust'),0,0,'C');
	    $this->Ln(5);
	    $this->Cell(80);
	    $this->Cell(30,10,$this->company->FieldByName('address'),0,0,'C');
	    $this->Ln(5);
	    $this->Cell(80);
	    $this->Cell(30,10,$this->company->FieldByName('phone').", ".$this->company->FieldByName('email'),0,0,'C');
	    $this->Ln(5);

	    $this->Ln(5);
	}

	function Footer()
	{
	    $this->SetY(-15);
	    $this->SetFont('Arial','I',8);
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

    define('L_MARGIN',10);
    define('LEN_SN', 7);
    define('LEN_CODE', 15);
    define('LEN_BATCH', 10);
	if ($qry_order->FieldByName('is_other_state')=='Y')    
		define('LEN_DESCRIPTION', 60);
	else
    	define('LEN_DESCRIPTION', 45);
    define('LEN_HSN', 17);
    define('LEN_PRICE', 15);
    define('LEN_QTY', 10);
    define('LEN_VALUE', 15);
    define('LEN_IGST', 13);
    define('LEN_SGST', 13);
    define('LEN_CGST', 13);

	$pdf->SetLeftMargin(L_MARGIN);    


	/*
		company details
	*/
		
	$pdf->SetFont('Arial','',10);

    $pdf->Cell(55,10,'GSTIN:',0,0,'R');
    $pdf->Cell(20,10,$pdf->company->FieldByName('gstin'),0,0,'L');
    $pdf->Ln(4);


    /*
    	debit note details
    */
    	
	$pdf->Cell(55,10,'Purchase Order No.:',0,0,'R');
	$pdf->Cell(20,10,$qry_order->FieldByName('purchase_order_ref'),0,0,'L');
	$pdf->Cell(40);
	$pdf->Cell(30,10,'Date:',0,0,'R');
	$pdf->Cell(20,10,get_date($qry_order->FieldByName('date_received')),0,0,'L');
	$pdf->Ln(4);

	$pdf->Cell(55,10,'Supplier:',0,0,'R');
	$pdf->Cell(20,10,$qry_order->FieldByName('supplier_name'),0,0,'L');
	$pdf->Ln(4);

	$pdf->Cell(55,10,'Supplier Inv No.:',0,0,'R');
	$pdf->Cell(20,10,$qry_order->FieldByName('invoice_number'),0,0,'L');
	$pdf->Ln(4);

	$pdf->Cell(55,10,'Supplier Inv Dt.:',0,0,'R');
	$pdf->Cell(20,10,get_date($qry_order->FieldByName('invoice_date')),0,0,'L');
	$pdf->Ln(24);


    /*
    	HEADER FOR PRODUCT LIST
    */
    $pdf->SetFont('Arial','B',8);


    $pdf->Cell(LEN_SN,10,'SN',1,0,'R');
    $pdf->Cell(LEN_CODE,10,'Code',1,0,'R');
    $pdf->Cell(LEN_BATCH,10,'Batch',1,0,'R');
    $pdf->Cell(LEN_DESCRIPTION,10,'Description',1,0,'C');
    $pdf->Cell(LEN_HSN,10,'HSN',1,0,'C');
//    $pdf->Cell(LEN_QTY,10,'Ordered',1,0,'C');
    $pdf->Cell(LEN_QTY,10,'Qty',1,0,'C');
    $pdf->Cell(LEN_PRICE,10,'B. Price',1,0,'C');
    $y=$pdf->GetY();
    $x=$pdf->GetX();
    $pdf->MultiCell(LEN_VALUE,5,'Taxable Value',1,'C');
    $pdf->SetXY($x+15,$y);
//    $pdf->Cell(LEN_SUPPLIER,10,'Supplier',1,0,'C');

	if ($qry_order->FieldByName('is_other_state')=='Y') { 

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

    $pdf->SetFont('Arial','',8);


    /*
    	PRODUCT LIST
    */
	$h = 4;
	
    for ($i=0; $i<$qry_items->RowCount(); $i++) {
        
        $item_total = 0;

        /*
		if (str_word_count($result)>1) {
			$s = substr($result, 0, LEN_CODE);
			$result = substr($s, 0, strrpos($s, ' '));
		}
		*/


		$int_tax_id = $qry_items->FieldByName('tax_id');

		$tax_amount = calculateTax($flt_price , $int_tax_id);

	    if ($qry_order->FieldByName('purchase_status')==3) 
	    	$quantity = $qry_items->FieldByName('quantity_received');
	    else
	    	$quantity = $qry_items->FieldByName('quantity_ordered');

		$value = ($quantity * ($qry_items->FieldByName('buying_price') + $tax_amount));

        $qry_tax = new Query("
			SELECT * 
			FROM ".Monthalize('stock_tax')." st
			LEFT JOIN ".Monthalize('stock_tax_links')." stl ON (stl.tax_id = st.tax_id)
			LEFT JOIN ".Monthalize('stock_tax_definition')." std ON (std.definition_id = stl.tax_definition_id)
			WHERE st.tax_id = ".$qry_items->FieldByName('tax_id')
		);

        

	    $pdf->Cell(LEN_SN,$h,($i+1),1,0,'R');
	    $pdf->Cell(LEN_CODE,$h,$qry_items->FieldByName('product_code'),1,0,'R');
	    $pdf->Cell(LEN_BATCH,$h,$qry_items->FieldByName('batch_id'),1,0,'R');
	    $pdf->Cell(LEN_DESCRIPTION,$h,$qry_items->FieldByName('product_description'),1,0,'L');
	    $pdf->Cell(LEN_HSN,$h,$qry_items->FieldByName('hsn'),1,0,'C');
	    $pdf->Cell(LEN_QTY,$h,$quantity,1,0,'C');
	    $pdf->Cell(LEN_PRICE,$h,number_format($qry_items->FieldByName('buying_price'),3,'.',''),1,0,'R');

	    $y=$pdf->GetY();
	    $x=$pdf->GetX();
	    $pdf->MultiCell(LEN_VALUE,$h,number_format($value,2,'.',','),1,'R');
	    $pdf->SetXY($x+15,$y);


		if ($qry_order->FieldByName('is_other_state')=='Y') { 

			$tax = $qry_tax->FieldByName('definition_percent');
			$tax_rate = $tax;
			$tax_amount = $value * ($tax_rate / 100);


			$int_index = getColumn($arr_taxes, $qry_items->FieldByName('tax_id'));
			if ($int_index > -1) {
				$arr_taxes[$int_index][2] += number_format(round((float)$tax_amount,3),2,'.','');
			}

		    $y=$pdf->GetY();
		    $x=$pdf->GetX();
			$pdf->MultiCell(LEN_IGST,$h,number_format($tax_rate, 2, '.', ',').'%',1,'R');
		    $pdf->SetXY($x+LEN_IGST,$y);
			$pdf->MultiCell(LEN_IGST,$h,number_format($tax_amount, 2, '.', ','),1,'R');
			//$pdf->SetXY($x+15,$y);

			$total_tax += $tax_amount;
		} else {

			$tax = $qry_tax->FieldByName('definition_percent');
			$tax_rate = $tax / 2;
			$tax_amount = $value * ($tax_rate / 100);


			$int_index = getColumn($arr_taxes, $qry_items->FieldByName('tax_id'));
			if ($int_index > -1) {
				$arr_taxes[$int_index][2] += number_format(round((float)($tax_amount*2),3),2,'.','');
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

			$total_tax += ($tax_amount*2);
		}

        $qry_items->Next();

		$total_value += $value;
        $total_qty += $quantity;

        $pdf->Ln(0);

    }

	if ($qry_order->FieldByName('is_other_state')=='Y') {

		$start = L_MARGIN + LEN_SN + LEN_CODE + LEN_BATCH + LEN_DESCRIPTION + LEN_HSN;
		$pdf->SetX($start);
		$pdf->Cell(LEN_QTY,5,number_format(($total_qty)),1,0,'C');
		$pdf->Cell(LEN_PRICE,5,'',1,0,'R');
	    $pdf->Cell(LEN_VALUE,5,number_format($total_value,2),1,0,'R');
		$pdf->Cell(LEN_IGST,5,'',1,0,'R');
	    $pdf->Cell(LEN_IGST,5,number_format($total_tax,2),1,0,'R');

		$start = L_MARGIN + LEN_SN + LEN_CODE + LEN_BATCH + LEN_DESCRIPTION + LEN_HSN + LEN_QTY;
		$width = (LEN_IGST) + (LEN_PRICE/2) + (LEN_VALUE/2);

	} else {

		$start = L_MARGIN + LEN_SN + LEN_CODE + LEN_BATCH + LEN_DESCRIPTION + LEN_HSN;
		$pdf->SetX($start);
		$pdf->Cell(LEN_QTY,5,number_format($total_qty),1,0,'C');
		$pdf->Cell(LEN_PRICE,5,'',1,0,'R');
	    $pdf->Cell(LEN_VALUE,5,number_format($total_value,2),1,0,'R');
		$pdf->Cell(LEN_CGST,5,'',1,0,'R');
	    $pdf->Cell(LEN_CGST,5,number_format(($total_tax/2),2),1,0,'R');
		$pdf->Cell(LEN_SGST,5,'',1,0,'R');
	    $pdf->Cell(LEN_SGST,5,number_format(($total_tax/2),2),1,0,'R');

		$start = L_MARGIN + LEN_SN + LEN_CODE + LEN_BATCH + LEN_DESCRIPTION + LEN_HSN + LEN_QTY + LEN_PRICE + LEN_VALUE;
		$width = (LEN_CGST) + (LEN_SGST);
	}

	$pdf->Ln(5);



	$pdf->SetFont('Arial','B',8);
	$pdf->SetX($start);
	$pdf->Cell($width,5,"Subtotal",1,0,'R');
	$pdf->SetFont('Arial','',8);
	$pdf->Cell($width,5,number_format($total_value,2),1,0,'R');
	$pdf->Ln(5);


	for ($i=0;$i<count($arr_taxes);$i++) {
		if ($arr_taxes[$i][2] > 0) {
		    $pdf->SetFont('Arial','B',8);
			$pdf->SetX($start);
			$pdf->Cell($width,5,"Tax ".$arr_taxes[$i][1]."%",1,0,'R');
		    $pdf->SetFont('Arial','',8);
			$pdf->Cell($width,5,number_format($arr_taxes[$i][2],2,'.',''),1,0,'R');
			$pdf->Ln(5);
		}
	}



    $pdf->SetFont('Arial','B',8);
	$pdf->SetX($start);
	$pdf->Cell($width,5,'Total',1,0,'R');
    $pdf->SetFont('Arial','',8);
	$pdf->Cell($width,5,number_format(($total_value + $total_tax),2),1,0,'R');
	$pdf->Ln(5);


	$pdf->SetX(L_MARGIN);
	$pdf->Cell(180,5,ExpandAmount(number_format($total_value + $total_tax,2,'.','')),0,0,'L');


	$title = 'Purchase Order';
	$pdf->SetTitle($company->FieldByName('title'));
	$pdf->SetAuthor($company->FieldByName('title'));

	$pdf->Output($filename, 'I');
?>