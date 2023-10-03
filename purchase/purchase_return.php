<?php

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");

	require_once("../stock/rts/rts.inc.php");
	require_once("../common/product_funcs.inc.php");

	/*
		get all the months to query for the 
		currently selected database
		(function at bottom of file)
	*/
	$period = get_current_months();


	$ret = array();


	if (isset($_POST['ID'])) {


		/*
			retrieve the purchase order
		*/
		$sql = "
			SELECT *
			FROM ".Yearalize('purchase_order')." po
			WHERE purchase_order_id = ".$_POST['ID'];

		$qry = new Query($sql);


		if ($qry->RowCount() > 0) {

			/*
				generate the return to supplier
			*/
			$rts = new RTS();

			$rts->discount 		= $qry->FieldByName('discount');
			$rts->bill_status 	= BILL_STATUS_RESOLVED;
			$rts->description 	= $qry->FieldByName('purchase_order_ref');
			$rts->supplier_id 	= $qry->FieldByName('supplier_id');
			$rts->invoice_number = $qry->FieldByName('invoice_number');
			$rts->invoice_date 	= $qry->FieldByName('invoice_date');


			/*
				retrieve the purchase order items
			*/
			$sql = "
				SELECT *
				FROM ".Yearalize('purchase_items')." pi
				WHERE purchase_order_id = ".$_POST['ID'];

			$items = new Query($sql);

			$arr = array();

			if ($items->RowCount() > 0) {

				$has_stock = false;

				for ($i=0; $i<$items->RowCount(); $i++) {
	
					/*
						get the balance to return
					*/
					$balance = 0;

					$sql = "
						SELECT ssb.stock_available
						FROM ".Monthalize('stock_storeroom_batch')." ssb
						WHERE (ssb.product_id = ".$items->FieldByName('product_id').") 
							AND (ssb.batch_id = ".$items->FieldByName('batch_id').")
							AND (ssb.storeroom_id = ".$_SESSION['int_current_storeroom'].")";

					$qry_batch = new Query($sql);

					if ($qry_batch->RowCount() > 0)
						$balance = $qry_batch->FieldByName('stock_available');


					if ($balance > 0) {

						$has_stock = true;

						$arr[$i]['quantity'] = $balance;
						$arr[$i]['price'] = $items->FieldByName('selling_price');
						$arr[$i]['bprice'] = $items->FieldByName('buying_price');
						$arr[$i]['product_id'] = $items->FieldByName('product_id');
						$arr[$i]['batch_id'] = $items->FieldByName('batch_id');
						$arr[$i]['tax_id'] = $items->FieldByName('tax_id');
					}

					$items->Next();
				}

				$rts->items = $arr;


				/*
					save the return to supplier 
				*/
				if ($has_stock) {

					$ret = $rts->save();

				}
				else {

					$ret['error'] = true;
					$ret['msg'] = "The stock of the products of this purchase order are all zero.";

				}

			}
			else {

				$ret['error'] = true;
				$ret['msg'] = "This purchase order has no entries.";

			}
		}
	}


	echo json_encode($ret);
?>