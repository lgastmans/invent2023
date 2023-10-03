<?
	/*
		when a bill/order is cancelled there are two quantities involved:
		1. actual quantity - this is stock that was available at the time the bill was saved
		2. adjusted quantity - this is stock that was NOT available at the time the bill was saved
		
		actual quantity when cancelled gets:
			a. added to the current stock in the STOCK_STOREROOM_PRODUCT table
			b. deducted from the sold quantity, 
				added to the cancelled quantity,
				added to the closing balance in the STOCK_BALANCE table
			c. added to the available stock in the STOCK_STOREROOM_BATCH
			
		adjusted quantity gets:
			case 1. adjusted > current adjusted
					a. the current adjusted quantity should be treated as case 2 below
					b. the remainder should be treated as case 3 below
			case 2. adjusted < current adjusted
					a. deduct from the stock adjusted in the STOCK_STOREROOM_PRODUCT TABLE
					b. deduct from the sold quantity
			case 3. no current adjusted.
					Implies that stock was received in the meantime,
					and should be treated as actual stock
	*/
	
	function cancelItem($int_product_id, $flt_quantity, $int_batch_id, $int_bill_number, $bill_id, $flt_adjusted) {
		
		$bool_success = "ok|ok";
		
		//***
		//In orders that are pending, it is possible that there are products 
		//that have not been entered in stock yet
		//***
		
		$qry_exists = new Query("
			SELECT *
			FROM ".Monthalize('stock_storeroom_product')."
			WHERE (product_id=".$int_product_id.")
				AND (storeroom_id=".$_SESSION["int_current_storeroom"].")
		");
		if ($qry_exists->RowCount() > 0) {
			
			$bool_success = apply_cancellation(
				$int_product_id,
				$flt_quantity,
				$int_batch_id,
				$int_bill_number,
				$bill_id);
			
			//***
			// make sure is_active is false when positive stock batches are available
			//***
			$arr_result = check_active_batches($int_product_id);
			
			$result_set = new Query("SELECT * FROM stock_product LIMIT 1");
			
			if (count($arr_result) > 0) {
				for ($i=0;$i<count($arr_result);$i++) {
					$str_update = "
						UPDATE ".Monthalize('stock_storeroom_batch')."
						SET is_active = 'N'
						WHERE stock_storeroom_batch_id = ".$arr_result[$i]."
							AND storeroom_id = ".$_SESSION['int_current_storeroom']."
						LIMIT 1
					";
					$result_set->Query($str_update);
				}
			}
			
			//***
			// now check the possible adjusted quantities
			//***
			
			if ($flt_adjusted > 0) {
				$qry_stock = new Query("
					SELECT stock_adjusted
					FROM ".Monthalize('stock_storeroom_product')."
					WHERE product_id = $int_product_id
						AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
				");
				
				if ($qry_stock->FieldByName('stock_adjusted') > 0) {
					//***
					// CASE 1
					//***
					if ($flt_adjusted > $qry_stock->FieldByName('stock_adjusted')) {
						
						$flt_remainder = $flt_adjusted - $qry_stock->FieldByName('stock_adjusted');
						$flt_update_adjusted = $qry_stock->FieldByName('stock_adjusted');
						
						$result_set = new Query("
							UPDATE ".Monthalize('stock_storeroom_product')."
							SET stock_adjusted = ROUND((stock_adjusted - $flt_update_adjusted),3)
							WHERE (product_id = $int_product_id)
								AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
						");
						if ($result_set->b_error == true) {
							$bool_success = "false|Error updating stock_product";
						}
						
						$result_set->Query("
							UPDATE ".Yearalize('stock_balance')."
							SET stock_sold = ROUND(stock_sold - ".$flt_update_adjusted.", 3),
								stock_cancelled = stock_cancelled + ".$flt_update_adjusted."
							WHERE (product_id = ".$int_product_id.")
								AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
								AND (balance_month = ".$_SESSION["int_month_loaded"].")
								AND (balance_year = ".$_SESSION["int_year_loaded"].")
						");
						if ($result_set->b_error == true) {
							$bool_success = "false|Error updating stock_balance";
						}
						
						$bool_success = apply_cancellation(
							$int_product_id,
							$flt_remainder,
							$int_batch_id,
							$int_bill_number,
							$bill_id);
					}
					//***
					// CASE 2
					//***
					else {
						$result_set = new Query("
							UPDATE ".Monthalize('stock_storeroom_product')."
							SET stock_adjusted = ROUND((stock_adjusted - $flt_adjusted),3)
							WHERE (product_id = $int_product_id)
								AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
						");
						if ($result_set->b_error == true) {
							$bool_success = "false|Error updating stock_product";
						}
						
						$result_set->Query("
							UPDATE ".Yearalize('stock_balance')."
							SET stock_sold = ROUND(stock_sold - ".$flt_adjusted.", 3),
								stock_cancelled = stock_cancelled + ".$flt_adjusted."
							WHERE (product_id = ".$int_product_id.")
								AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
								AND (balance_month = ".$_SESSION["int_month_loaded"].")
								AND (balance_year = ".$_SESSION["int_year_loaded"].")
						");
						if ($result_set->b_error == true) {
							$bool_success = "false|Error updating stock_balance";
						}
					}
				}
				//***
				// CASE 3
				//***
				else {
					$bool_success = apply_cancellation(
						$int_product_id,
						$flt_adjusted,
						$int_batch_id,
						$int_bill_number,
						$bill_id);
				}
			}
		}
		return $bool_success;
	}
	
	
	
	function apply_cancellation($int_product_id, $flt_quantity, $int_batch_id, $int_bill_number, $bill_id) {
		$bool_success = "ok|ok";
		
		/*
			update the adjusted stock, if any
		*/
		$actual_stock_cancelled = $flt_quantity;
		$stock_received = $flt_quantity;
		$str_adjusted = "";
		
		$result_set = new Query("
			SELECT stock_adjusted
			FROM ".Monthalize('stock_storeroom_product')."
			WHERE (product_id = ".$int_product_id.")
				AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
		");
		if ($result_set->RowCount() > 0) {
			if ($result_set->FieldByName('stock_adjusted') > 0) {
				if ($result_set->FieldByName('stock_adjusted') > $flt_quantity) {
					/*
						update the stock_adjusted in stock_storeroom_product
					*/
					$qry_adjust = new Query("
						UPDATE ".Monthalize('stock_storeroom_product')."
						SET stock_adjusted = ROUND(stock_adjusted - ".$flt_quantity.", 3)
						WHERE (product_id = ".$int_product_id.")
							AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
					");
					
					$str_adjusted = ", adjusted: ".$flt_quantity;
					$stock_adjusted = $flt_quantity;
					$stock_received = 0;
				}
				else {
					/*
						update the stock_adjusted in stock_storeroom_product
					*/
					$qry_adjust = new Query("
						UPDATE ".Monthalize('stock_storeroom_product')."
						SET stock_adjusted = 0
						WHERE (product_id = ".$int_product_id.")
							AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
					");
					
					$str_adjusted = ", adjusted: ".$result_set->FieldByName('stock_adjusted');
					$stock_adjusted = number_format($result_set->FieldByName('stock_adjusted'), 3, '.', '');
					$stock_received = $stock_received - $result_set->FieldByName('stock_adjusted');
				}
			}
		}
		
		$flt_quantity = $stock_received;
		
		//***
		// TABLE stock_storeroom_product
		// the current stock gets incremented
		//***
		$qry_string = "
			UPDATE ".Monthalize('stock_storeroom_product')."
			SET stock_current = stock_current + ".$flt_quantity."
			WHERE (product_id=".$int_product_id.") 
				AND (storeroom_id=".$_SESSION["int_current_storeroom"].")";
		
		$result_set->Query($qry_string);
		if ($result_set->b_error == true) {
			$bool_success = "false|Error updating ".Monthalize('stock_storeroom_product');
		}
		
		//***
		// TABLE stock_storeroom_batch
		// the available stock gets incremented for the batch
		//***
		$qry_string = "
			UPDATE ".Monthalize('stock_storeroom_batch')."
			SET stock_available = stock_available + ".$flt_quantity.",
				is_active = 'Y'
			WHERE (batch_id = ".$int_batch_id.")
				AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
				AND (product_id = ".$int_product_id.")";
		$result_set->Query($qry_string);
		if ($result_set->b_error == true) {
			$bool_success = "false|Error updating ".Monthalize('stock_storeroom_batch');
		}
		
		//***
		// TABLE stock_balance
		// increment the cancelled stock
		// deduct the sold stock
		// increment the closing balance
		//***
		$qry_string = "
			UPDATE ".Yearalize('stock_balance')."
			SET stock_cancelled = stock_cancelled + ".$actual_stock_cancelled.",
				stock_sold = ROUND(stock_sold - ".$actual_stock_cancelled.", 3),
				stock_closing_balance = stock_closing_balance + ".$flt_quantity."
			WHERE (product_id = ".$int_product_id.")
				AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
				AND (balance_month = ".$_SESSION["int_month_loaded"].")
				AND (balance_year = ".$_SESSION["int_year_loaded"].")";
		$result_set->Query($qry_string);
		if ($result_set->b_error == true) {
			$bool_success = "false|Error updating ".Yearalize('stock_balance');
		}
		
		//***
		// TABLE stock_transfer
		//***
		$qry_string = "
			INSERT INTO  ".Monthalize('stock_transfer')."
				(transfer_quantity,
				transfer_description,
				date_created,
				module_id,
				user_id,
				storeroom_id_from,
				storeroom_id_to,
				product_id,
				batch_id,
				module_record_id,
				transfer_type,
				transfer_status,
				user_id_dispatched,
				user_id_received,
				is_deleted)
			VALUES(".
				$actual_stock_cancelled.", '".
				"CANCELLATION OF BILL NUMBER ".$int_bill_number.$str_adjusted."', '".
				date("Y-m-d H:i:s")."', ".
				"2, ".
				$_SESSION["int_user_id"].", ".
				"0, ".
				$_SESSION["int_current_storeroom"].", ".
				$int_product_id.", ".
				$int_batch_id.", ".
				$bill_id.", ".
				TYPE_CANCELLED.", ".
				STATUS_COMPLETED.", ".
				$_SESSION["int_user_id"].", ".
				"0, ".
				"'N')";
		$result_set->Query($qry_string);
		if ($result_set->b_error == true) {
			$bool_success = "false|Error inserting into ".Monthalize('stock_transfer');
		}
		
		return $bool_success;
	}
?>