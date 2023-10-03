<?php

	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");
	require_once("../../common/tax.php");
	require_once("../../common/product_funcs.inc.php");


	header("Content-Type: application/json;charset=utf-8");

	$int_decimals = 3;


	$purchase_order_id = 0;
	if (isset($_POST['purchase_order_id']))
		$purchase_order_id = $_POST['purchase_order_id'];

	/*
		retrieve all the product details for the selected purchase order.
	*/

	$sql = "
		SELECT pi.batch_id, pi.quantity_received, pi.buying_price, pi.tax_id, 
			ss.supplier_name,
			sp.product_id, sp.product_code, sp.product_description
		FROM ".Yearalize('purchase_items')." pi
		INNER JOIN stock_product sp ON (sp.product_id = pi.product_id)
		INNER JOIN stock_supplier ss ON (ss.supplier_id = pi.supplier_id)
		WHERE pi.purchase_order_id = ".$purchase_order_id;

	$qry_pi = new Query($sql);


	/*
		store in an array the months across which
		to retrieve the quantity sold per item
	*/
	$arr = explode("_", $_SESSION['invent_database_loaded']);

	$period = array();
	
	if (count($arr) <= 1) { 

		if (date('n') <= 3) {

			if ($_SESSION['int_month_loaded'] <= 3) {
				/*
					current year, and selected month is between Jan and Mar
				*/
				$year = date('Y');
				for ($i=1;$i<=$_SESSION['int_month_loaded'];$i++)
					$period[$i] = $year."_".$i;
				$year--;
				for ($i=4;$i<=12;$i++)
					$period[$i] = $year."_".$i;
			}
			else {
				/*
					current year, but selected month is between Apr and Dec
				*/
				$year = date('Y')-1;
				for ($i=4;$i<=$_SESSION['int_month_loaded'];$i++)
					$period[$i] = $year."_".$i;
			}
		}
		else {
			
			$year = date('Y');
			for ($i=4;$i<=$_SESSION['int_month_loaded'];$i++)
				$period[$i] = $year."_".$i;
		}
	}
	else {
		/*
			previous financial year
		*/
		if ($_SESSION['int_month_loaded'] <= 3) {

			$year = intval($arr[2]);
			for ($i=1;$i<=$_SESSION['int_month_loaded'];$i++)
				$period[$i] = $year."_".$i;
			$year--;
			for ($i=4;$i<=12;$i++)
				$period[$i] = $year."_".$i;

		}
		else {

			$year = intval($arr[1]);
			for ($i=4;$i<=12;$i++)
				$period[$i] = $year."_".$i;

		}
		
	}


	$podata = array();
	$extra = '';
	
	/*
		iterate through each product
	*/
	for ($i=0; $i < $qry_pi->RowCount(); $i++) {

		$podata[$i]['id'] = 1;
		
		$podata[$i]['code'] = $qry_pi->FieldByName('product_code');
		$podata[$i]['batch'] = $qry_pi->FieldByName('batch_id');
		$podata[$i]['description'] = $qry_pi->FieldByName('product_description');
		$podata[$i]['supplier'] = $qry_pi->FieldByName('supplier_name');
		$podata[$i]['quantity'] = $qry_pi->FieldByName('quantity_received');
		$podata[$i]['price'] = number_format($qry_pi->FieldByName('buying_price'), $int_decimals,'.','');


		/*
			get the quantities sold per product over the months
		*/
		$product_id = $qry_pi->FieldByName('product_id');
		$batch_id = $qry_pi->FieldByName('batch_id');
		$sold = 0;
		$extra = '<table id="extra-table"><thead><tr><th>Bill No.</th><th>Date</th><th>Sold</th><th>Price</th></tr></thead>';
		
		foreach ($period as $month) {

			$sql = "
				SELECT *
				FROM bill_items_".$month." bi
				INNER JOIN bill_".$month." b ON (b.bill_id = bi.bill_id)
				WHERE (bi.product_id = $product_id) AND (bi.batch_id = $batch_id)";

			$qry_items = new Query($sql);
			

			/*
				retrieve the details per bill
			*/
	
			for ($k=0;$k<$qry_items->RowCount();$k++) {

				$sold += $qry_items->FieldByName('quantity') + $qry_items->FieldByName('adjusted_quantity');
				//$price = number_format($qry_items->FieldByName('bprice'), $int_decimals,'.','');

				$extra .= "
					<tr>
						<td style='text-align:left;'>".$qry_items->FieldByName('bill_number')."</td>
						<td style='text-align:left;'>".set_formatted_date($qry_items->FieldByName('date_created'), '-')."</td>
						<td style='text-align:left !important;'>".($qry_items->FieldByName('quantity') + $qry_items->FieldByName('adjusted_quantity'))."</td>
						<td style='text-align:left !important;'>".number_format($qry_items->FieldByName('price'), $int_decimals,'.','')."</td>
					</tr>";

				$qry_items->Next();
			}
			
			//$extra .= $sql;
		}
		
		$extra .= '</table>';

		$podata[$i]['sold'] = $sold;
		$podata[$i]['price'] = getSellingPrice($product_id, $batch_id);
		$podata[$i]['balance'] = ($qry_pi->FieldByName('quantity_received') - $sold);
		$podata[$i]['extra'] = $extra;


		$qry_pi->Next();

	}

	$podata = array_reverse($podata);

	$ret = array("data"=>$podata, "po_id" => $extra, "num_rows" => $qry_pi->RowCount());

	echo json_encode($ret);

?>