<?php
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");
    require_once("../common/tax.php");
    //require_once("../Numbers/Words.php");
    require_once("../common/number_to_words.php");
    require_once("../common/product_funcs.inc.php");

	$int_id = 0;
    if (IsSet($_GET['id']))
        $int_id = $_GET['id'];

    /*
    	amount in words
    */
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




	$filename = "purchase_order_".$qry_order->FieldByName('purchase_order_ref').".csv";

	header("Content-Type: application/text; name=".$filename);
	header("Content-Transfer-Encoding: binary");
	header("Content-Disposition: attachment; filename=".$filename);
	header("Expires: 0");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");



	$delimiter = "\t";



    echo "GSTIN:".$delimiter.$company->FieldByName('gstin')."\n";
	echo 'Purchase Order No.:'.$delimiter.$qry_order->FieldByName('purchase_order_ref')."\n";
	echo 'Date:'.$delimiter.get_date($qry_order->FieldByName('date_received'))."\n";
	echo "\r\n";

	echo 'Supplier:'.$delimiter.$qry_order->FieldByName('supplier_name')."\n";
	echo "\r\n";

	echo 'Supplier Inv No.:'.$delimiter.$qry_order->FieldByName('invoice_number')."\n";
	echo 'Supplier Inv Dt.:'.$delimiter.get_date($qry_order->FieldByName('invoice_date'))."\n";
	echo "\r\n";


	$str = 'SN'.$delimiter.'Code'.$delimiter.'Batch'.$delimiter.'Description'.$delimiter.'HSN'.$delimiter.'Qty'.$delimiter.'B. Price'.$delimiter.'Taxable Value'.$delimiter;

	if ($qry_order->FieldByName('is_other_state')=='Y') { 
		$str .= 'IGST Rate'.$delimiter.'IGST Amount';
	} else {
		$str .= 'CGST Rate'.$delimiter.'CGST Amount'.$delimiter.'SGST Rate'.$delimiter.'SGST Amount';
	}
	echo $str."\r\n";

	$total_tax = 0;
	$total_value = 0;
	$total_qty = 0;

    for ($i=0; $i<$qry_items->RowCount(); $i++) {
        
        $item_total = 0;

		$int_tax_id = $qry_items->FieldByName('tax_id');

		$flt_price = $qry_items->FieldByName('buying_price');

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

        
        $str = ($i+1).$delimiter.
        	$qry_items->FieldByName('product_code').$delimiter.
	    	$qry_items->FieldByName('batch_id').$delimiter.
	    	$qry_items->FieldByName('product_description').$delimiter.
	    	$qry_items->FieldByName('hsn').$delimiter.
	    	$quantity.$delimiter.
	    	number_format($qry_items->FieldByName('buying_price'),3,'.','').$delimiter.
	    	number_format($value,2,'.',',').$delimiter;

		if ($qry_order->FieldByName('is_other_state')=='Y') { 

			$tax = $qry_tax->FieldByName('definition_percent');
			$tax_rate = $tax;
			$tax_amount = $value * ($tax_rate / 100);


			$int_index = getColumn($arr_taxes, $qry_items->FieldByName('tax_id'));
			if ($int_index > -1) {
				$arr_taxes[$int_index][2] += number_format(round((float)$tax_amount,3),2,'.','');
			}

			$str .= number_format($tax_rate, 2, '.', ',').$delimiter.
				number_format($tax_amount, 2, '.', ',').$delimiter;

			$total_tax += $tax_amount;
		} else {

			$tax = $qry_tax->FieldByName('definition_percent');
			$tax_rate = $tax / 2;
			$tax_amount = $value * ($tax_rate / 100);


			$int_index = getColumn($arr_taxes, $qry_items->FieldByName('tax_id'));
			if ($int_index > -1) {
				$arr_taxes[$int_index][2] += number_format(round((float)($tax_amount*2),3),2,'.','');
			}

			$str .= number_format($tax_rate, 2, '.', '').$delimiter.
				number_format($tax_amount, 2, '.', '').$delimiter.
				number_format($tax_rate, 2, '.', '').$delimiter.
				number_format($tax_amount, 2, '.', '').$delimiter;

			$total_tax += ($tax_amount*2);
		}

        $qry_items->Next();

		$total_value += $value;
        $total_qty += $quantity;

        $str .= "\n";

        echo $str;
    }


	if ($qry_order->FieldByName('is_other_state')=='Y') {

		echo $delimiter.$delimiter.$delimiter.$delimiter.$delimiter.
			number_format(($total_qty),0,'.','').$delimiter.$delimiter.
			number_format($total_value,2,'.','').$delimiter.$delimiter.
			number_format($total_tax,2,'.','')."\r\n";

	} else {

		echo $delimiter.$delimiter.$delimiter.$delimiter.$delimiter.
			number_format($total_qty).$delimiter.$delimiter.
	    	number_format($total_value,2,'.','').$delimiter.$delimiter.
	    	number_format(($total_tax/2),2,'.','').$delimiter.$delimiter.
	    	number_format(($total_tax/2),2,'.','')."\r\n";
	}


	echo "Subtotal".$delimiter.number_format($total_value,2,'.','')."\r\n";


	for ($i=0;$i<count($arr_taxes);$i++) {
		if ($arr_taxes[$i][2] > 0) {
			echo "Tax ".$arr_taxes[$i][1]."%".$delimiter.number_format($arr_taxes[$i][2],2,'.','')."\r\n";
		}
	}

	echo 'Total'.$delimiter.number_format(($total_value + $total_tax),2,'.','')."\r\n";

?>