<?
	require_once($str_application_path.'common/product_funcs.inc.php');
	require_once($str_application_path.'common/product_bill_debit.php');

	function cancelOrderBill($anOrderBillId, $force='N') {

		global $str_application_path;
		
		$str_retval = "ERROR|ERROR";
		$can_cancel = true;

		$qry_cancel = new Query("
			SELECT *
			FROM ".Monthalize('bill')."
			WHERE (bill_id = ".$anOrderBillId.")
				AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
		");
		$bool_is_debit = $qry_cancel->FieldByName('is_debit_bill');
		$bill_number = $qry_cancel->FieldByName('bill_number');
		
		if ($qry_cancel->FieldByName('bill_status') == BILL_STATUS_CANCELLED) {
			$str_retval = "ERROR|This order bill is cancelled already";
			$can_cancel = false;
		}
		else if ($qry_cancel->FieldByName('bill_status') == BILL_STATUS_RESOLVED) {
			if ($force == 'N') {
				$str_retval = "ERROR_001|This order bill has been resolved and cannot be cancelled";
				$can_cancel = false;
			}
			else {
				$can_cancel = 'Y';
			}
		}
		
		if ($can_cancel) {
			$qry_items = new Query("
				SELECT *
				FROM ".Monthalize('bill_items')."
				WHERE bill_id = ".$anOrderBillId."
			");
			for ($i=0; $i<$qry_items->RowCount(); $i++) {
				if ($bool_is_debit == 'N') {
					//***
					// update the "ordered" field for each item
					//***
					$qry_cancel->Query("
						UPDATE ".Monthalize('stock_storeroom_product')."
						SET stock_reserved = ROUND(stock_reserved - ".$qry_items->FieldByName('quantity').", 3)
						WHERE product_id = ".$qry_items->FieldByName('product_id')."
							AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
					");
				}
				
				if ($force == 'Y') {
					//***
					// if it is a CREDIT order
					// the quantities should be added to stock
					//***
					if ($bool_is_debit == 'N') {
						require($str_application_path."billing/bill_cancel.php");
						$str_reason = "Order Module";
						cancelBill($anOrderBillId, $str_reason, 7); // 7 = orders module id
					}
					//***
					// if it is a DEBIT order
					// the quantities should be removed from stock ("billed")
					//***
					else {
						//***
						// array: get the available batches for the given product
						// 0 - batch code
						// 1 - batch id
						// 2 - stock
						//***
						$arr_batches = get_active_batches($qry_items->FieldByName('product_id'));
						
						//***
						// array: get the breakdown on how to deduct the stock
						//	$arr_retval[0]['batch_id']
						//	$arr_retval[0]['product_id']
						//	$arr_retval[0]['quantity']
						//	$arr_retval[0]['adjusted_quantity']
						//***
						$arr_bill_details = set_stock_details(
							$arr_batches,
							$qry_items->FieldByName('product_id'),
							$arr_batches[0][1],
							$qry_items->FieldByName('quantity'));
							
						//***
						// deduct the stock
						//***
						for ($i=0;$i<count($arr_bill_details);$i++) {
							product_bill_debit(
								$arr_bill_details[$i]['batch_id'],
								$qry_items->FieldByName('product_id'),
								$arr_bill_details[$i]['quantity'],
								$arr_bill_details[$i]['adjusted_quantity'],
								$bill_number,
								date('j', time()),
								7,
								$anOrderBillId);
						}
					}
				}
				
				$qry_items->Next();
			}
			
			$qry_cancel->Query("
				UPDATE ".Monthalize('bill')."
				SET bill_status = ".BILL_STATUS_CANCELLED."
				WHERE (bill_id = ".$anOrderBillId.")
					AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
			");
			if ($qry_cancel->b_error == false)
				$str_retval = "OK|Order bill ".$qry_cancel->FieldByName('bill_number')." cancelled successfully";
		}
		
		return $str_retval;
	}
	
	function set_stock_details($arr_batch_details, $product_id, $batch_id, $billed_quantity) {
		$arr_retval = array();
		
		$fltBilledQty = number_format($billed_quantity, 3, '.', '');
		
		$result_set = new Query("SELECT * FROM stock_product WHERE product_id = $product_id");
		
		//***
		// get the quantity in the batch that has been selected,
		// and the total quantity in all the batches
		//***
		$flt_BatchQty = number_format($arr_batch_details[0][2],3,'.','');
		$flt_TotalQty = 0;
		for ($i=0; $i<count($arr_batch_details); $i++) {
			$flt_TotalQty = $flt_TotalQty + $arr_batch_details[$i][2];
		}
		$flt_TotalQty = number_format($flt_TotalQty, 3, '.', '');
			
		//***
		// query initialization
		//***
		$qry = new Query("SELECT * FROM stock_product LIMIT 1");
		
		//***
		// if the billed quantity is less than the quantity in the
		// selected batch, create a single entry
		//***
		if ($fltBilledQty <= $flt_BatchQty) {
			$arr_retval[0]['batch_id'] = $arr_batch_details[0][1];
			$arr_retval[0]['product_id'] = $product_id;
			$arr_retval[0]['quantity'] = $fltBilledQty;
			$arr_retval[0]['adjusted_quantity'] = 0;
			
		}
		else {
			if ($fltBilledQty <= $flt_TotalQty) {
				
				//***
				// create as many entries as needed to meet the currently billed quantity
				//***
				$flt_TempQty = 0;
				$flt_BilledSoFar = 0;
				
				for ($i=0; $i<count($arr_batch_details); $i++) {
					$intLength = count($arr_retval);
					
					$flt_TempQty = $flt_TempQty + $arr_batch_details[$i][2];
					$flt_TempQty = number_format($flt_TempQty, 3, '.', '');
					
					if ($flt_TempQty < $fltBilledQty) {
						$arr_retval[$intLength]['batch_id'] = $arr_batch_details[$i][1];
						$arr_retval[$intLength]['product_id'] = $product_id;
						$arr_retval[$intLength]['quantity'] = number_format($arr_batch_details[$i][2],3,'.','');
						$arr_retval[$intLength]['adjusted_quantity'] = 0;
						
						$flt_BilledSoFar = $flt_BilledSoFar + $arr_batch_details[$i][2];
						$flt_BilledSoFar = number_format($flt_BilledSoFar, 3, '.', '');
					}
					else {
						$flt_qty = ($fltBilledQty - $flt_BilledSoFar);
						
						$arr_retval[$intLength]['batch_id'] = $arr_batch_details[$i][1];
						$arr_retval[$intLength]['product_id'] = $product_id;
						$arr_retval[$intLength]['quantity'] = number_format($flt_qty,3,'.','');
						$arr_retval[$intLength]['adjusted_quantity'] = 0;
						
						break;
					}
				}
			}
			else {
				//***
				// the billed amount is greater than the total available across batches
				// enter all the available batches in the session array, 
				// plus the extra quantity to be added to the last entry
				//***
				$flt_Remainder = $fltBilledQty - $flt_TotalQty;
				$flt_Remainder = number_format($flt_Remainder, 3, '.', '');
				$flt_TempQty = $flt_BatchQty;
				$flt_BilledSoFar = $flt_BatchQty;
					
				// loop through the available batches except the last
				for ($i=0; $i<count($arr_batch_details)-1; $i++) {
					
					$intLength = count($arr_retval);
						
					$flt_TempQty = $flt_TempQty + $arr_batch_details[$i][2];
					$flt_TempQty = number_format($flt_TempQty, 3, '.', '');
						
					if ($flt_TempQty < $fltBilledQty) {
						$arr_retval[$intLength]['batch_id'] = $arr_batch_details[$i][1];
						$arr_retval[$intLength]['product_id'] = $product_id;
						$arr_retval[$intLength]['quantity'] = number_format($arr_batch_details[$i][2],3,'.','');
						$arr_retval[$intLength]['adjusted_quantity'] = 0;
						
						$flt_BilledSoFar = $flt_BilledSoFar + $arr_batch_details[$i][2];
						$flt_BilledSoFar = number_format($flt_BilledSoFar, 3, '.', '');
					}
					else {
						$flt_qty = ($fltBilledQty - $flt_BilledSoFar);
						
						$arr_retval[$intLength]['batch_id'] = $arr_batch_details[$i][1];
						$arr_retval[$intLength]['product_id'] = $product_id;
						$arr_retval[$intLength]['quantity'] = number_format($flt_qty,3,'.','');
						$arr_retval[$intLength]['adjusted_quantity'] = 0;
						
						break;
					}
				}
				
				$intLength = count($arr_retval);
				
				$arr_retval[$intLength]['batch_id'] = $arr_batch_details[$i][1];
				$arr_retval[$intLength]['product_id'] = $product_id;
				$arr_retval[$intLength]['quantity'] = number_format($arr_batch_details[$i][2],3, '.','');
				$arr_retval[$intLength]['adjusted_quantity'] = number_format($flt_Remainder, 3,'.','');
			}
		}
		return $arr_retval;
	}

?>