<?php
    require_once("../../include/const.inc.php");
    require_once("../../include/session.inc.php");
    require_once("../../include/db.inc.php");
    require_once("../../common/tax.php");
    require_once("../../common/product_funcs.inc.php");



    if (IsSet($_GET['id']))
        $int_id = $_GET['id'];


    /*
    	amount in words
    */
    function ExpandAmount($amount) {

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

    function get_date($str_mysql_date) {
        $str_date = substr($str_mysql_date, 0, 10);
        $arr_date = explode("-", $str_date);
        return $arr_date[2]."-".$arr_date[1]."-".$arr_date[0];
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
    	get debit note data
    */
    $sql = "
        SELECT sr.*, ss.*
        FROM ".Monthalize('stock_rts')." sr
        INNER JOIN stock_supplier ss ON (ss.supplier_id = sr.supplier_id)
        WHERE stock_rts_id = $int_id";
    $qry = new Query($sql);

	$qry_items = new Query("
	    SELECT *
	    FROM ".Monthalize('stock_rts_items')." sri
	    LEFT JOIN stock_product sp ON (sp.product_id = sri.product_id)
		LEFT JOIN stock_category sc ON (sc.category_id = sp.category_id)
	    WHERE sri.rts_id = $int_id
	    ORDER BY sp.product_code
	");




	$filename = $qry->FieldByName('supplier_name')."_debit_note_".$qry->FieldByName('bill_number').".csv";


	header("Content-Type: application/text; name=".$filename);
	header("Content-Transfer-Encoding: binary");
	header("Content-Disposition: attachment; filename=".$filename);
	header("Expires: 0");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");



	$delimiter = "\t";


    echo "DEBIT NOTE\n";
	echo $company->FieldByName('title')."\n";
	echo $company->FieldByName('legal_name')."\n";
	echo $company->FieldByName('trust')."\n";
	echo $company->FieldByName('address')."\n";
	echo $company->FieldByName('phone').", ".$company->FieldByName('email')."\n";
	echo "\r\n";


    define('L_MARGIN',10);
    define('LEN_SN', 5);
    define('LEN_CODE', 15);
    define('LEN_BATCH', 12);
    define('LEN_DESCRIPTION', 45);
    define('LEN_HSN', 17);
    define('LEN_QTY', 8);
    define('LEN_PRICE', 15);
    define('LEN_TAXABLE_VALUE', 15);
    define('LEN_DISCOUNT', 15);
    define('LEN_VALUE', 15);
    define('LEN_IGST', 15);
    define('LEN_SGST', 15);
    define('LEN_CGST', 15);



	/*
		company details
	*/
	echo 'GSTIN:'.$delimiter.$company->FieldByName('gstin')."\n";



    /*
    	debit note details
    */
	echo 'Debit Note No.:'.$delimiter.$qry->FieldByName('bill_number')."\n";
	echo 'Date:'.$delimiter.get_date($qry->FieldByName('date_created'))."\n";
	echo 'Purchase Return:'."\n";

	echo 'Supplier Inv No.:'.$delimiter.$qry->FieldByName('invoice_number')."\n";

	echo 'Supplier Inv Dt.:'.$delimiter.$qry->FieldByName('invoice_date')."\n";

	echo 'Remarks:'."\n";
	echo "Price Change".$delimiter."Tax Change".$delimiter."Unit Request".$delimiter."Other"."\n";



	echo 'TO:'.$delimiter.$qry->FieldByName('supplier_name').", ".$qry->FieldByName('trust')."\n";
	echo $delimiter.$qry->FieldByName('supplier_address')."\n";
	echo $delimiter.$qry->FieldByName('supplier_city')." ".$qry->FieldByName('supplier_zip')."\n";
	echo $delimiter.$qry->FieldByName('supplier_state')."\n";
	echo $delimiter.$qry->FieldByName('supplier_TIN')."\n";



    /*
    	HEADER FOR PRODUCT LIST
    */
    echo 'SN'.$delimiter.'Code'.$delimiter.'Batch'.$delimiter.'Description'.$delimiter.'HSN'.$delimiter.'Qty'.$delimiter.'Buying Price'.$delimiter.'Taxable Value'.$delimiter;


	if ($qry->FieldByName('is_other_state')=='Y') { 

		echo 'IGST Rate'.$delimiter.'IGST Amount'."\n";

	} else {

		echo 'CGST Rate'.$delimiter.'CGST Amount'.$delimiter.'SGST Rate'.$delimiter.'SGST Amount'."\n";

	}









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

		$qry_batch = new Query("
			SELECT sb.batch_code, sb.tax_id
			FROM ".Yearalize('stock_batch')." sb
			WHERE (sb.batch_id = ".$qry_items->FieldByName('batch_id').") AND
				(sb.storeroom_id = ".$_SESSION["int_current_storeroom"].")
			");

        if ($calculate_tax == 'Y') {
			if ($qry_batch->FieldByName('tax_id')==NULL)
				$int_tax_id = 0;
			else
				$int_tax_id = $qry_batch->FieldByName('tax_id');

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


	    echo ($i+1).$delimiter;
	    echo $qry_items->FieldByName('product_code').$delimiter;
	    echo $qry_batch->FieldByName('batch_code').$delimiter;
	    echo $qry_items->FieldByName('product_description').$delimiter;
	    echo $qry_items->FieldByName('hsn').$delimiter;
	    echo number_format($total_quantity, 0, '.', '').$delimiter;
	    //$pdf->Cell(15,$h,number_format($flt_price, 2, '.', ''),1,0,'C');
		echo number_format($flt_price,2,'.',',').$delimiter;
	    echo number_format(($flt_price * $total_quantity),2,'.',',').$delimiter;

		if ($qry->FieldByName('trust') !== $company->FieldByName('trust')) {

			if ($qry->FieldByName('is_other_state')=='Y') { 

				$tax = $qry_tax->FieldByName('definition_percent');
				$tax_rate = $tax;
				$tax_amount = $item_total * ($tax_rate / 100);
				$tax_total += $tax_amount;

				$int_index = getColumn($arr_taxes, $int_tax_id); //$qry_items->FieldByName('tax_id'));
				if ($int_index > -1) {
					$arr_taxes[$int_index][2] += number_format(round((float)$tax_amount,3),2,'.','');
				}

				echo number_format($tax_rate, 2, '.', ',').'%'.$delimiter;
				echo number_format($tax_amount, 2, '.', ',').$delimiter;

			} else {

				$tax = $qry_tax->FieldByName('definition_percent');
				$tax_rate = $tax / 2;
				$tax_amount = $item_total * ($tax_rate / 100);
				$tax_total += ($tax_amount * 2);


				$int_index = getColumn($arr_taxes, $int_tax_id); //$qry_items->FieldByName('tax_id'));
				if ($int_index > -1) {
					$arr_taxes[$int_index][2] += number_format(round((float)($tax_amount*2),3),2,'.','');
				}


				echo number_format($tax_rate, 2, '.', '')."%".$delimiter;
				echo number_format($tax_amount, 2, '.', '').$delimiter;
				echo number_format($tax_rate, 2, '.', '')."%".$delimiter;
				echo number_format($tax_amount, 2, '.', '').$delimiter;

			}
		}

		echo "\n";

		$taxable_value += $tax_amount;
        $qty_total += $total_quantity;

        $qry_items->Next();

    }

	echo "\n";


	$flt_total = $flt_total + $tax_total;

	$flt_discount = 0;//$qry->FieldByName('discount');
	

	if ($qry->FieldByName('is_other_state')=='Y') {
		
		echo $delimiter.$delimiter.$delimiter.$delimiter.$delimiter.number_format(($qty_total)).$delimiter;
	    echo $delimiter;
	    echo number_format(($flt_total-$tax_total),2).$delimiter;

	    if ($qry->FieldByName('trust') !== $company->FieldByName('trust')) {

		    echo $delimiter.$delimiter;
	    	echo number_format(($taxable_value),2).$delimiter;

	    }

	    $l_margin = 98;

	} else {

		echo $delimiter.$delimiter.$delimiter.$delimiter.$delimiter.number_format(($qty_total)).$delimiter;
	    echo $delimiter;
	    number_format(($flt_total-$tax_total),2).$delimiter;

		if ($qry->FieldByName('trust') !== $company->FieldByName('trust')) {

		    echo $delimiter.$delimiter;
		    echo number_format(($taxable_value),2).$delimiter;
		    echo $delimiter;
		    echo number_format(($taxable_value),2).$delimiter;
		}

	    $l_margin = 113;

	}
	echo "\n";

	echo "Subtotal".$delimiter;
	echo number_format(($flt_total-$tax_total),2).$delimiter;
	echo "\n";

	for ($i=0;$i<count($arr_taxes);$i++) {

		if ($arr_taxes[$i][2] > 0) {

			echo "Tax ".$arr_taxes[$i][1]."%".$delimiter;
			echo number_format($arr_taxes[$i][2],2,'.','').$delimiter;

		}
	}
	echo "\n";

	echo 'Total:'.$delimiter;
	echo number_format(($flt_total),2).$delimiter;
	echo "\n";
	echo "\n";


    $str_amount = ExpandAmount(number_format($flt_total,2,'.',''));
	echo "Rupees ".$str_amount.$delimiter;
	echo "\n";

	echo 'Prepared By'.$delimiter;
	echo 'Received By'.$delimiter;
	echo 'Verified By'.$delimiter;
	echo "\n";
	echo "\n";

	echo $qry->FieldByName('supplier_name').$delimiter;
	echo $company->FieldByName('title').$delimiter;


?>