<?
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");

	// this array holds the total quantities of items billed, as one item can be billed 
	// across multiple batches. It serves the purpose of checking whether the total quantity 
	// bought is equal to, or greater than, the discount_qty in the stock_storeroom_product 
	// table, in which case the discount_percent should be suggested
//	('arr_total_qty');

	// this array holds the batch code and quantity for the product that is currently being billed
//	('arr_item_batches');

	// the string version of the array to pass to javascript
//	('str_total_qty');



	if (IsSet($_GET['action'])) {

		if ($_GET['action'] == 'clear_receipt') {


			/*
				clear the session variables related to the billing
			*/
			unset($_SESSION["arr_total_qty"]);
			unset($_SESSION["arr_item_batches"]);
			$_SESSION['stock_rts_id'] = null;
			$_SESSION['current_bill_day'] = date('j');
			$_SESSION['bill_total'] = 0;
			$_SESSION['current_supplier_id'] = 0;
			$_SESSION['current_discount'] = 0;
			$_SESSION['current_note'] = '';
			$_SESSION['current_invoice_number'] = '';
			$_SESSION['current_invoice_date'] = '';
			

			/*
				get the last receipt number
			*/
			$sql = "
				SELECT bill_number
				FROM ".Monthalize('stock_rts')."
				WHERE (module_id = 2) AND 
					(storeroom_id = ".$_SESSION['int_current_storeroom'].")
				ORDER BY bill_number+0 DESC";
			$qry = new Query($sql);

			if ($qry->RowCount() > 0)
				$_SESSION['current_bill_number'] = intval($qry->FieldByName('bill_number')) +1;
			else
				$_SESSION['current_bill_number'] = 1;


			/*
				now refresh this page without the GET variable
			*/
			header('location:receipt_frameset.php');
			exit;
		} 
	}

	if (isset($_GET['id'])) {

		$sql = "SELECT *, DAY(date_created) as day FROM ".Monthalize('stock_rts')." WHERE stock_rts_id = ".$_GET['id'];
		$qry_rts = new Query($sql);

		$_SESSION['stock_rts_id'] = $_GET['id'];

		$_SESSION['current_bill_day'] = $qry_rts->FieldByName('day');
		$_SESSION['bill_total'] = $qry_rts->FieldByName('total_amount');
		$_SESSION['current_supplier_id'] = $qry_rts->FieldByName('supplier_id');
		$_SESSION['current_discount'] = $qry_rts->FieldByName('discount');
		$_SESSION['current_note'] = $qry_rts->FieldByName('description');
		$_SESSION['current_invoice_number'] = $qry_rts->FieldByName('invoice_number');
		$_SESSION['current_invoice_date'] = $qry_rts->FieldByName('invoice_date');
		$_SESSION['current_bill_number'] = $qry_rts->FieldByName('bill_number');


		$sql = "
			SELECT sri.*, sp.product_code, sp.product_description, po.invoice_number, po.invoice_date, po.date_received
			FROM ".Monthalize('stock_rts_items')." sri
			LEFT JOIN stock_product sp ON (sp.product_id = sri.product_id)
			LEFT JOIN ".Yearalize('purchase_items')." pi ON (pi.batch_id = sri.batch_id)
			LEFT JOIN ".Yearalize('purchase_order')." po ON (po.purchase_order_id = pi.purchase_order_id)
			WHERE rts_id = ".$_GET['id'];

		$qry_items = new Query($sql);		

		unset($_SESSION["arr_total_qty"]);
		unset($_SESSION["arr_item_batches"]);

		for ($i=0;$i<$qry_items->RowCount();$i++) {

			/*
				get the tax description to display on screen and print
			*/
			$sql = "
				SELECT st.tax_description
			 	FROM ".Monthalize('stock_tax_links')." stl
			 	INNER JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = stl.tax_id) AND (st.tax_id = ".$qry_items->FieldByName('tax_id').")
			 	INNER JOIN ".Monthalize('stock_tax_definition')." std ON (std.definition_id = stl.tax_definition_id)
			 	WHERE std.definition_type <> 2";

			$result_set = new Query($sql);			

			$total = round(($qry_items->FieldByName('quantity') * $qry_items->FieldByName('price')), 2);

			$_SESSION["arr_total_qty"][$i][0]	= $qry_items->FieldByName('product_code');			// product code
			$_SESSION["arr_total_qty"][$i][1]	= $qry_items->FieldByName('batch_id');				// batch code
			$_SESSION["arr_total_qty"][$i][12]	= $qry_items->FieldByName('product_description');	// product description
			$_SESSION["arr_total_qty"][$i][2]	= $qry_items->FieldByName('quantity');				// quantity billed
			$_SESSION["arr_total_qty"][$i][6]	= $qry_items->FieldByName('price');					// price
			$_SESSION["arr_total_qty"][$i][7]	= $qry_items->FieldByName('tax_id');				// tax_id
			$_SESSION["arr_total_qty"][$i][8]	= $result_set->FieldByName('tax_description');		// tax description
			$_SESSION["arr_total_qty"][$i]['invno'] = $qry_items->FieldByName('invoice_number');
			$_SESSION["arr_total_qty"][$i]['invdt'] = $qry_items->FieldByName('invoice_date');
			$_SESSION["arr_total_qty"][$i]['buying_price'] = $qry_items->FieldByName('bprice');
			$_SESSION["arr_total_qty"][$i][10]	= $total;											// total


			$qry_items->Next();
		}

	}

?>

<html>
<frameset rows='100,80,*,80,50' border=0 scrolling=no>
	<frame name='frame_supplier' src="receipt_supplier.php" scrolling=no>
	<frame name='frame_enter' src="receipt_enter.php" scrolling=no>
	<frame name='frame_list' src="receipt_list.php" scrolling=no>
	<frame name='frame_total' src="receipt_total.php" scrolling=no>
	<frame name='frame_action' src="receipt_action.php" scrolling=no>
</frameset>

</html>