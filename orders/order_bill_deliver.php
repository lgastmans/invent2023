<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/account.php");
	require_once("../common/tax.php");
	require_once("../common/product_debit.php");
	if (file_exists("get_bill_number.php"))
		require_once("get_bill_number.php");
	else if (file_exists("billing/get_bill_number.php"))
		require_once("billing/get_bill_number.php");
	else if (file_exists("../billing/get_bill_number.php"))
		require_once("../billing/get_bill_number.php");
	else if (file_exists("../../billing/get_bill_number.php"))
		require_once("../../billing/get_bill_number.php");
		
	require_once("Config.php");
	
	$config = new Config();
	$arrConfig =& $config->parseConfig($str_root."include/config.ini", "IniFile");
	$update_prices = 'N';
	
	$templateSection = $arrConfig->getItem("section", 'billing');
	/*
		if the "billing" section does not exist in the config.ini file
		set to default values
	*/
	if ($templateSection === false) {
		$update_prices = 'N';
	}
	else {
		/*
			if the section exists, but the directive does not,
			create it
		*/
		$update_prices_directive =& $templateSection->getItem("directive", "update_order_prices");
		if ($update_prices_directive === false) {
			$templateSection->createDirective("update_order_prices", 'N');
			$update_prices_directive =& $templateSection->getItem("directive", "update_order_prices");
			$config->writeConfig($str_root."include/config.ini", "IniFile");
		}
		$update_prices = $update_prices_directive->getContent();
	}
	
	function validate_date($str_date) {
		$bool_retval = false;
		if (!empty($str_date)) {
			$arr_date = explode('-', $str_date);
			
			if (count($arr_date) == 3) {
				if (strlen($arr_date[2] < 4))
					return $bool_retval;
				
				$bool_retval = checkdate($arr_date[1], $arr_date[0], $arr_date[2]);
			}
		}
		
		return $bool_retval;
	}

	function deliver_order_bill($anId, $delivery_date='') {
		global $update_prices;
		
		$str_retval = 'OK|OK';
		
		$bool_success = true;
		
		if ($delivery_date == '')
			$str_delivery_date = date('Y-m-d H:i:s', time());
		else
			$str_delivery_date = set_mysql_date($delivery_date, '-')." ".date('H:i:s', time());

		//====================
		// get user settings
		//====================
		$sql_settings = new Query("
			SELECT *
			FROM user_settings
		");
		if ($sql_settings->RowCount() > 0) {
			$str_calc_tax_first = $sql_settings->FieldByName('calculate_tax_before_discount');
		}

		//====================
		// start transaction
		//====================
		$qry_transaction = new Query("START TRANSACTION");

		//====================
		// get the tax details of the current storeroom
		//====================
		$qry_storeroom = new Query("
			SELECT *
			FROM stock_storeroom
			WHERE storeroom_id = ".$_SESSION['int_current_storeroom']."
		");
		$is_cash_taxed = 'Y';
		$is_account_taxed = 'Y';
		if ($qry_storeroom->RowCount() > 0) {
			$is_cash_taxed = $qry_storeroom->FieldByName('is_cash_taxed');
			$is_account_taxed = $qry_storeroom->FieldByName('is_account_taxed');
		}
		
		//====================================================
		// load the details of each ordered item into an array
		//----------------------------------------------------
		/**
		 * October 2023
		 * bills marked as is_billable = N were previously marked as completed without deducting stock
		 * This update resolves this issue: stock gets deducted, but the FS transfer is not done
		 * see also order_functions.inc.php line 216
		 * this file: search for createTransfer
		 */ 
		$qry_bill = new Query("
			SELECT o.is_billable, b.*
			FROM ".Monthalize('bill')." b
			LEFT JOIN ".Monthalize("orders")." o ON (o.order_id = b.module_record_id)
			WHERE (bill_id = ".$anId.")
		");
		if (($qry_bill->FieldByName('is_pending') == 'N') || 
			($qry_bill->FieldByName('bill_status') == BILL_STATUS_CANCELLED) || 
			($qry_bill->FieldByName('bill_status') == BILL_STATUS_RESOLVED) ||
			($qry_bill->FieldByName('bill_status') == BILL_STATUS_DISPATCHED) ||
			($qry_bill->FieldByName('bill_status') == BILL_STATUS_DELIVERED)
			) {
			return "ERROR|ERROR<br>Operation cannot be completed for ".$qry_bill->FieldByName('bill_number');
		}
		if (getModuleByID(9) !== null) {


			/*
				MANTRA HACK
				use the BILL_TRANSFER_GOOD for the bill number
				in case the supplier is mantra (use_mrp = Y)
			*/
            $qry_mantra = new Query("
                SELECT *
                FROM customer
                WHERE use_mrp = 'Y'
            ");

            $int_customer_id = $qry_mantra->FieldByName('id');

			if ($qry_bill->FieldByName('CC_id')==$int_customer_id)
				$int_next_billnumber = get_bill_number(BILL_TRANSFER_GOOD);
			else
				$int_next_billnumber = get_bill_number($qry_bill->FieldByName('payment_type'));



			$qry_bill->Query("
				UPDATE ".Monthalize('bill')."
				SET bill_number = $int_next_billnumber,
					date_created = '".$str_delivery_date."'
				WHERE (bill_id = $anId)
			");
			$qry_bill->Query("
				SELECT *
				FROM ".Monthalize('bill')."
				WHERE (bill_id = ".$anId.")
			");
		}
		else {
			$int_next_billnumber = $qry_bill->FieldByName('bill_number');
		}
		
		$qry_bill_items = new Query("
			SELECT *
			FROM ".Monthalize('bill_items')."
			WHERE (bill_id = ".$anId.")
		");
		
		$flt_bill_total = 0;
		
		//===========================================
		// first check whether all items in the order
		// have stock
		//-------------------------------------------
		$qry_exists = new Query("SELECT * FROM stock_product LIMIT 1");

		for ($i=0; $i<$qry_bill_items->RowCount(); $i++) {

			$qry_exists->Query("
				SELECT *
				FROM ".Monthalize('stock_storeroom_product')."
				WHERE (product_id=".$qry_bill_items->FieldByName('product_id').")
					AND (storeroom_id=".$_SESSION["int_current_storeroom"].")
			");

			if ($qry_exists->RowCount() == 0) {

				$qry_transaction->Query("ROLLBACK");

				$qry_exists->Query("
					SELECT *
					FROM stock_product
					WHERE product_id = ".$qry_bill_items->FieldByName('product_id')."
				");
				
				return "ERROR|Product ".$qry_exists->FieldByName('product_code')." not in stock - order NOT delivered";
			}
			
			$qry_bill_items->Next();
		}
		
		$qry_bill_items->First();
		
		for ($i=0; $i<$qry_bill_items->RowCount(); $i++) {
			
			if ($qry_bill->FieldByName('is_debit_bill') == 'Y') {
				$str_retval = debit_receive(
					$qry_bill_items->FieldByName('product_id'),
					$qry_bill_items->FieldByName('quantity'),
					$int_next_billnumber,
					date('j', time()),
					7,
					$qry_bill->FieldByName('bill_id'));
			}
			else {
				if ($qry_bill_items->FieldByName('quantity') > 0) {
				
					//====================
					// get the batch breakdown for the product
					//====================
					$arr_retval = array();
					$arr_retval = get_product_batch_details(
						$qry_bill_items->FieldByName('product_id'),
						$qry_bill_items->FieldByName('quantity')
					);
					$bool_success = true;
					
					for ($j=0; $j<count($arr_retval); $j++) {
						//====================
						// update the stock tables for each item in the order bill
						//====================
						// check whether a manual transfer should be made
						//====================
						$arr_success[0] = 'OK';
						if ($arr_retval[$j][4] > 0) {
							$str_success = create_manual_transfer(
								$arr_retval[$j][1],
								$qry_bill->FieldByName('bill_number'),
								$qry_bill->FieldByName('bill_id'),
								$arr_retval[$j][2],
								$arr_retval[$j][4]);
							$arr_success = explode("|", $str_success);
						}
						if ($arr_success[0] == 'ERROR') {
							$bool_success = false;
							$str_retval = "ERROR|".$arr_success[1];
						}
						
						$flt_quantity_to_bill = number_format(($arr_retval[$j][3] + $arr_retval[$j][4]), 3,'.','');
						
						//====================
						// TABLE stock_storeroom_product
						//====================
						$qry_transaction->Query("UPDATE ".Monthalize('stock_storeroom_product')."
							SET stock_current = ABS(ROUND((stock_current - ".$flt_quantity_to_bill."),3))
							WHERE (product_id=".$arr_retval[$j][2].") AND
								(storeroom_id=".$_SESSION["int_current_storeroom"].")");
						if ($qry_transaction->b_error == true) {
							$bool_success = false;
							$str_retval = "ERROR|Error updating stock_storeroom_product";
						}
	
						//====================
						// TABLE stock_storeroom_batch
						// There was some strange behaviour subtracting here, 
						// hence the ROUND function call
						// very small amounts were generated, as -7.12548e-9
						//====================
						$qry_transaction->Query("UPDATE ".Monthalize('stock_storeroom_batch')."
							SET stock_available = ROUND((stock_available - ".$flt_quantity_to_bill."),3)
							WHERE (batch_id = ".$arr_retval[$j][1].") AND
								(storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
								(product_id = ".$arr_retval[$j][2].")
						");
						if ($qry_transaction->b_error == true) {
							$bool_success = false;
							$str_retval = "ERROR|Error updating stock_storeroom_batch";
						}
						
						//====================
						// if the current stock becomes zero, then set the batch's is_active flag to false
						// if there is more than one active batch available. There should always be one active
						// batch regardless of the available stock
						//====================
						$qry_transaction->Query("SELECT stock_available 
							FROM ".Monthalize('stock_storeroom_batch')."
							WHERE (batch_id = ".$arr_retval[$j][1].") AND
								(storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
								(product_id = ".$arr_retval[$j][2].")
						");
						
						$flt_stock_available = number_format($qry_transaction->FieldByName('stock_available'),3,'.','');
						
						if ($flt_stock_available <= 0) {
							// check number of available active batches, and if it is greater than one
							// set the current batch's is_active flag to false
							$qry_check = new Query("SELECT * 
								FROM ".Monthalize('stock_storeroom_batch')." 
								WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
									(product_id = ".$arr_retval[$j][2].") AND 
									(is_active = 'Y')
							");
							if ($qry_check->RowCount() > 1) {
								$qry_transaction->Query("
									UPDATE ".Monthalize('stock_storeroom_batch')."
									SET is_active = 'N',
										debug = 'orders'
									WHERE (batch_id = ".$arr_retval[$j][1].")
										AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
										AND (product_id = ".$arr_retval[$j][2].")
								");
							}
						}
						
						//====================
						// TABLE stock_balance
						//====================
						$qry_transaction->Query("UPDATE ".Yearalize('stock_balance')."
								SET stock_sold = stock_sold + ".$arr_retval[$j][3].",
									stock_closing_balance = ROUND((stock_closing_balance - ".$arr_retval[$j][3]."),3)
								WHERE (product_id = ".$arr_retval[$j][2].") AND
									(storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
									(balance_month = ".$_SESSION["int_month_loaded"].") AND
									(balance_year = ".$_SESSION["int_year_loaded"].")");
						if ($qry_transaction->b_error == true) {
							$bool_success = false;
							$str_retval = "ERROR|Error updating stock_balance";
						}
						
						//====================
						// TABLE stock_transfer
						//====================
						$qry_transaction->Query("INSERT INTO  ".Monthalize('stock_transfer')."
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
								$arr_retval[$j][3].", '". // here, only index 2 is saved, as a manual transfer for the remaining amount is already created
								"BILL NUMBER ".$qry_bill->FieldByName('bill_number')."', '".
								get_bill_date(date('d'))."', ".
								"2, ".
								$_SESSION["int_user_id"].", ".
								$_SESSION["int_current_storeroom"].", ".
								"0, ".
								$arr_retval[$j][2].", ".
								$arr_retval[$j][1].", ".
								$qry_bill->FieldByName('bill_id').", ".
								TYPE_BILL.", ".
								STATUS_COMPLETED.", ".
								$_SESSION["int_user_id"].", ".
								"0, ".
								"'N')");
						if ($qry_transaction->b_error == true) {
							$bool_success = false;
							$str_retval = "ERROR|Error updating stock_transfer";
						}
						
						//====================
						// update the bill_items table for each item in the order bill
						//====================
						// calculate the tax and the total cost per item billed
						//====================
						if ($qry_bill->FieldByName('payment_type') == BILL_CASH) {
							if ($is_cash_taxed == 'Y')
								$calculate_tax = 'Y';
							else
								$calculate_tax = 'N';
						}
						else if (($qry_bill->FieldByName('payment_type') == BILL_ACCOUNT) || ($qry_bill->FieldByName('payment_type') == BILL_PT_ACCOUNT)) {
							if ($is_account_taxed == 'Y')
								$calculate_tax = 'Y';
							else
								$calculate_tax = 'N';
						}
						
						if ($update_prices == 'Y')
							$flt_temp_price = $arr_retval[$j][5];
						else
							$flt_temp_price = $qry_bill_items->FieldByName('price');
						$flt_temp_qty = round($arr_retval[$j][3] + $arr_retval[$j][4], 3);
						$int_temp_tax_id = $arr_retval[$j][6];
						
						if ($calculate_tax == 'Y') {
							$tax_amount = calculateTax($flt_temp_price * $flt_temp_qty, $int_temp_tax_id);
							$flt_price_total = number_format(($flt_temp_qty * $flt_temp_price + $tax_amount), 3, '.', '');
						}
						else {
							$tax_amount = 0;
							$flt_price_total = number_format(($flt_temp_qty * $flt_temp_price), 3, '.', '');
						}
						
						$flt_bill_total += number_format($flt_price_total, 3, '.', '');
						
						$flt_bill_total = number_format($flt_bill_total, 2, '.', '');
						
						if ($update_prices == 'Y')
							$cur_price = $arr_retval[$j][5];
						else
							$cur_price = $qry_bill_items->FieldByName('price');
						
						if ($j > 0) {
							$str_query = "
								INSERT INTO ".Monthalize('bill_items')."
								(
									quantity,
									discount,
									price,
									tax_id,
									tax_amount,
									product_id,
									bill_id,
									batch_id,
									adjusted_quantity,
									product_description
								)
								VALUES (
									".$arr_retval[$j][3].",
									0,
									".$cur_price.", 
									".$arr_retval[$j][6].",
									".$tax_amount.",
									".$arr_retval[$j][2].",
									".$qry_bill->FieldByName('bill_id').",
									".$arr_retval[$j][1].",
									".$arr_retval[$j][4].",
									'".addslashes($qry_bill_items->FieldByName('product_description'))."'
								)";
							$qry_transaction->Query($str_query);
							if ($qry_transaction->b_error == true) {
								$bool_success = false;
								$str_retval = "ERROR|Error inserting into bill_items".$str_query;
							}
						}
						else {
							$qry_transaction->Query("
								UPDATE ".Monthalize('bill_items')."
								SET 
									quantity = ".$arr_retval[$j][3].",
									adjusted_quantity = ".$arr_retval[$j][4].",
									tax_id = ".$arr_retval[$j][6].",
									price = ".$cur_price.",
									tax_amount = ".$tax_amount.",
									batch_id = ".$arr_retval[$j][1]."
								WHERE bill_item_id = ".$qry_bill_items->FieldByName('bill_item_id')."
								LIMIT 1
							");
							if ($qry_transaction->b_error == true) {
								$bool_success = false;
								$str_retval = "ERROR|Error updating bill_items";
							}
						}
						
						
					} // end of $j loop = count($arr_retval)
					
					//====================
					// update the 'stock_ordered' field in the stock_storeroom_product table
					//====================
					$qry_transaction->Query("
						UPDATE ".Monthalize('stock_storeroom_product')."
						SET stock_reserved = ROUND(stock_reserved - ".$qry_bill_items->FieldByName('quantity').", 3)
						WHERE storeroom_id = ".$_SESSION['int_current_storeroom']."
							AND product_id = ".$qry_bill_items->FieldByName('product_id')."
					");
					if ($qry_transaction->b_error == true) {
						$bool_success = false;
						$str_retval = "ERROR|Error updating the stock ordered quantity";
					}
				} // end of if (quantity > 0)
			} // end of if ($qry_bill->FieldByName('is_debit_bill') == 'Y'
			
			$qry_bill_items->Next();
			
		} // end of $i loop = $qry_bill_items

		//=========================================================================================
		// if the CLIENTS module is NOT active
		// update the is_pending and bill_status flags, and the total_amount and resolved_on fields
		//-----------------------------------------------------------------------------------------
		if (getModuleByID(9) === null) {
			$qry_transaction->Query("
				UPDATE ".Monthalize('bill')."
				SET
					is_pending = 'N',
					bill_status = ".BILL_STATUS_RESOLVED.",
					total_amount = ".$flt_bill_total.",
					resolved_on = '".get_bill_date(date('d'))."'
				WHERE (bill_id = ".$anId.")
					AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
			");
			if ($qry_transaction->b_error == true) {
				$bool_success = false;
				$str_retval = "ERROR|Error updating the bill status";
			}
		}
		else {
			$qry_transaction->Query("
				UPDATE ".Monthalize('bill')."
				SET
					is_pending = 'Y',
					bill_status = ".BILL_STATUS_DISPATCHED.",
					total_amount = ".$flt_bill_total."
				WHERE (bill_id = ".$anId.")
					AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
			");
			if ($qry_transaction->b_error == true) {
				$bool_success = false;
				$str_retval = "ERROR|Error updating the bill status";
			}
		}
		
		//=====================
		// complete transaction
		//---------------------
		if ($bool_success) {
			$qry_transaction->Query("COMMIT");
			
			//==================
			// create a transfer
			//----------------------------------------
			// get the account to make the transfer to
			//----------------------------------------
			if ($qry_bill->FieldByName('payment_type') == BILL_ACCOUNT) {
				
				$qry_transaction->Query("SELECT * FROM module WHERE module_id = 9");
				
				if ($qry_transaction->RowCount() == 0) {
					$qry_transaction->Query("
						SELECT bill_credit_account, bill_order_description
						FROM stock_storeroom
						WHERE (storeroom_id = ".$_SESSION['int_current_storeroom'].")
					");
					$credit_acount = $qry_transaction->FieldByName('bill_credit_account');
					$bill_description = str_replace("%s", $qry_bill->FieldByName('bill_number'), $qry_transaction->FieldByName('bill_order_description'));
					$bill_description = str_replace("%d", substr(get_bill_date(date('d')),0,10), $bill_description);
					
					if ($qry_bill->FieldByName('is_billable')=='Y') {
						$int_result = createTransfer(
							$qry_bill->FieldByName('account_number'),
							$credit_acount,
							$bill_description,
							$flt_bill_total,
							7,
							$anId);
					}
					
					if (($int_result > 0) || ($int_result == -1)) {
						$can_save = 1;
					}
					else {
						$bool_success = false;
						$str_retval = "ERROR|Error creating transfer for account ".$qry_bill->FieldByName('account_number');
					}
				}
			}
		}
		else
			$qry_transaction->Query("ROLLBACK");
		
		return $str_retval;
	}

	function get_product_batch_details($intProductId, $fltBilledQty) {
		// array to return
		$arr_retval = array();

		// format the quantity
		$fltBilledQty = number_format($fltBilledQty, 3, '.', '');

		// get the available batches for the given product
		$arr_batches = get_product_batches($intProductId);
		
		// get the total quantity in the batches listed
		$flt_TotalQty = 0;
		for ($i=0; $i<count($arr_batches); $i++) {
			$flt_TotalQty = $flt_TotalQty + $arr_batches[$i][1];
		}
		$flt_TotalQty = number_format($flt_TotalQty, 3, '.', '');

		// format the quantity in the first entry of the batches array
		$flt_BatchQty = number_format($arr_batches[0][1], 3, '.', '');

		// if the billed quantity is less than the quantity in the
		// first batch, create a single entry
		if ($fltBilledQty <= $flt_BatchQty) {
			
			$arr_retval[0][0] = $arr_batches[0][0];		// batch code
			$arr_retval[0][1] = $arr_batches[0][2];		// batch id
			$arr_retval[0][2] = $intProductId;		// product id
			$arr_retval[0][3] = $fltBilledQty;		// quantity
			$arr_retval[0][4] = 0;				// adjusted quantity
			
			$arr_tax_details = get_product_tax_details($intProductId, $arr_batches[0][0]);
			$arr_retval[0][5] = $arr_tax_details[0];	// price
			$arr_retval[0][6] = $arr_tax_details[1];	// tax id
		}
		else {
			if ($fltBilledQty <= $flt_TotalQty) {
				
				// create as many entries as needed to meet the currently billed quantity
				$flt_TempQty = 0;
				$flt_BilledSoFar = 0;
				
				for ($i=0; $i<count($arr_batches); $i++) {
					
					$intLength = count($arr_retval);
					
					$flt_TempQty = $flt_TempQty + $arr_batches[$i][1];
					$flt_TempQty = number_format($flt_TempQty, 3, '.', '');
					
					if ($flt_TempQty < $fltBilledQty) {
						
						$arr_retval[$intLength][0] = $arr_batches[$i][0];
						$arr_retval[$intLength][1] = $arr_batches[$i][2];
						$arr_retval[$intLength][2] = $intProductId;
						$arr_retval[$intLength][3] = number_format($arr_batches[$i][1],3,'.','');
						$arr_retval[$intLength][4] = 0;
						
						$arr_tax_details = get_product_tax_details($intProductId, $arr_batches[$i][0]);
						$arr_retval[$intLength][5] = $arr_tax_details[0];
						$arr_retval[$intLength][6] = $arr_tax_details[1];
						
						$flt_BilledSoFar = $flt_BilledSoFar + $arr_batches[$i][1];
						$flt_BilledSoFar = number_format($flt_BilledSoFar, 3, '.', '');
					}
					else {
						$flt_qty = ($fltBilledQty - $flt_BilledSoFar);
						
						$arr_retval[$intLength][0] = $arr_batches[$i][0];
						$arr_retval[$intLength][1] = $arr_batches[$i][2];
						$arr_retval[$intLength][2] = $intProductId;
						$arr_retval[$intLength][3] = number_format($flt_qty, 3,'.','');
						$arr_retval[$intLength][4] = 0;

						$arr_tax_details = get_product_tax_details($intProductId, $arr_batches[$i][0]);
						$arr_retval[$intLength][5] = $arr_tax_details[0];
						$arr_retval[$intLength][6] = $arr_tax_details[1];

						break;
					}
				}
			}
			else {
				// the billed amount is greater than the total available across batches
				// enter all the available batches in the session array, 
				// plus the extra quantity to be added to the last entry
				$flt_Remainder = $fltBilledQty - $flt_TotalQty;
				$flt_Remainder = number_format($flt_Remainder, 3, '.', '');
				$flt_TempQty = $flt_BatchQty;
				$flt_BilledSoFar = $flt_BatchQty;
				
				// HERE we loop through the batches except the last
				for ($i=0; $i<count($arr_batches)-1; $i++) {
					
					$intLength = count($arr_retval);
					
					$flt_TempQty = $flt_TempQty + $arr_batches[$i][1];
					$flt_TempQty = number_format($flt_TempQty, 3, '.', '');
					
					if ($flt_TempQty < $fltBilledQty) {
						
						$arr_retval[$intLength][0] = $arr_batches[$i][0];
						$arr_retval[$intLength][1] = $arr_batches[$i][2];
						$arr_retval[$intLength][2] = $intProductId;
						$arr_retval[$intLength][3] = number_format($arr_batches[$i][1], 3,'.','');
						$arr_retval[$intLength][4] = 0;

						$arr_tax_details = get_product_tax_details($intProductId, $arr_batches[$i][0]);
						$arr_retval[$intLength][5] = $arr_tax_details[0];
						$arr_retval[$intLength][6] = $arr_tax_details[1];
						
						$flt_BilledSoFar = $flt_BilledSoFar + $arr_batches[$i][1];
						$flt_BilledSoFar = number_format($flt_BilledSoFar, 3, '.', '');
					}
					else {

						$flt_qty = ($fltBilledQty - $flt_BilledSoFar);
						
						$arr_retval[$intLength][0] = $arr_batches[$i][0];
						$arr_retval[$intLength][1] = $arr_batches[$i][2];
						$arr_retval[$intLength][2] = $intProductId;
						$arr_retval[$intLength][3] = number_format($flt_qty, 3,'.','');
						$arr_retval[$intLength][4] = 0;
						
						$arr_tax_details = get_product_tax_details($intProductId, $arr_batches[$i][0]);
						$arr_retval[$intLength][5] = $arr_tax_details[0];
						$arr_retval[$intLength][6] = $arr_tax_details[1];
						
						break;
					}
				}
				
				$intLength = count($arr_retval);
				
				$arr_retval[$intLength][0] = $arr_batches[$i][0];
				$arr_retval[$intLength][1] = $arr_batches[$i][2];
				$arr_retval[$intLength][2] = $intProductId;
				$arr_retval[$intLength][3] = number_format($arr_batches[$i][1], 3,'.','');
				$arr_retval[$intLength][4] = number_format($flt_Remainder, 3,'.','');;
				
				$arr_tax_details = get_product_tax_details($intProductId, $arr_batches[$i][0]);
				$arr_retval[$intLength][5] = $arr_tax_details[0];
				$arr_retval[$intLength][6] = $arr_tax_details[1];
			}
		}
		return $arr_retval;
	} // end of function

/*
function check_active_batches($int_id) {
	$str_batches = "
		SELECT stock_storeroom_batch_id, batch_id, @cur_id := product_id AS product_id,
			(SELECT 
				COUNT(batch_id) 
				FROM ".Monthalize('stock_storeroom_batch')."
				WHERE stock_available  > 0 
					AND is_active = 'Y' 
					AND product_id = @cur_id
					AND storeroom_id = ".$_SESSION['int_current_storeroom']."
			) AS counter
		FROM ".Monthalize('stock_storeroom_batch')."
		WHERE is_active = 'Y'
			AND stock_available = 0
			AND product_id = $int_id
			AND storeroom_id = ".$_SESSION['int_current_storeroom'];
	$qry_batches = new Query($str_batches);
	
	$arr_batches = array();
	
	for ($i=0; $i < $qry_batches->RowCount(); $i++) {
		if ($qry_batches->FieldByName('counter') > 0)
			$arr_batches[] = $qry_batches->FieldByName('stock_storeroom_batch_id');
		
		$qry_batches->Next();
	}
	
	return $arr_batches;
}
*/

	function get_product_batches($intProductId) {

		$arr_retval = array();

		//=============================================
		// make sure there is at least one active batch
		//---------------------------------------------
		$qry_batches = new Query("
			SELECT *
			FROM ".Monthalize('stock_storeroom_batch')."
			WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].") 
				AND (product_id = ".$intProductId.")
				AND (is_active = 'Y')
		");
		$strBatches = $qry_batches->RowCount();
		if ($qry_batches->RowCount() == 0) {
			// make the most recent batch active
			$qry_batches->Query("
				SELECT ssb.stock_storeroom_batch_id
				FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb
				WHERE (sb.product_id = ".$intProductId.") AND
					(sb.storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
					(sb.status = ".STATUS_COMPLETED.") AND
					(sb.deleted = 'N') AND
					(ssb.product_id = sb.product_id) AND
					(ssb.batch_id = sb.batch_id) AND
					(ssb.storeroom_id = sb.storeroom_id) AND
					(ssb.stock_available <= 0)
				ORDER BY date_created DESC
				LIMIT 1
			");
			if ($qry_batches->RowCount() > 0) {
				$qry_batches->First();
				$int_ssb_batch_id = $qry_batches->FieldByName('stock_storeroom_batch_id');								
				$qry_batches->Query("
					UPDATE ".Monthalize('stock_storeroom_batch')."
					SET is_active = 'Y'
					WHERE stock_storeroom_batch_id = ".$int_ssb_batch_id."
						AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
				");
			}
		}
		
		//=============================================
		// make sure is_active is false when positive stock batches are available
		//---------------------------------------------
		$arr_result = check_active_batches($intProductId);
		if (count($arr_result) > 0) {
			for ($i=0;$i<count($arr_result);$i++) {
				$str_update = "
					UPDATE ".Monthalize('stock_storeroom_batch')."
					SET is_active = 'N'
					WHERE stock_storeroom_batch_id = ".$arr_result[$i]."
						AND storeroom_id = ".$_SESSION['int_current_storeroom']."
					LIMIT 1
				";
				$qry_batches->Query($str_update);
			}
		}

		$qry_batches->Query("
			SELECT sb.batch_id, sb.batch_code, sb.buying_price, sb.selling_price, sb.tax_id, ssb.stock_available, ssb.is_active
			FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb
			WHERE (sb.product_id = ".$intProductId.") AND
				(sb.storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
				(sb.status = ".STATUS_COMPLETED.") AND
				(sb.deleted = 'N') AND
				(ssb.product_id = sb.product_id) AND
				(ssb.batch_id = sb.batch_id) AND
				(ssb.storeroom_id = sb.storeroom_id) AND 
				(ssb.is_active = 'Y')
			ORDER BY date_created
		");

		for ($i=0; $i<$qry_batches->RowCount(); $i++) {

			$arr_retval[$i][0] = $qry_batches->FieldByName('batch_code');
			$arr_retval[$i][1] = number_format($qry_batches->FieldByName('stock_available'),3,'.','');
			$arr_retval[$i][2] = $qry_batches->FieldByName('batch_id');

			$qry_batches->Next();
		}

		$qry_batches->Free();

		return $arr_retval;
	}

	function get_product_tax_details($intProductId, $aBatchCode) {

		$arr_retval = array();

		// check whether the item should use the batch price or the storeroom price
		// in case the storeroom price is to be used, then the tax should still be
		// taken from the batch
		$result_set = new Query("
			SELECT sale_price, point_price, use_batch_price, discount_qty, discount_percent
			FROM ".Monthalize('stock_storeroom_product')."
			WHERE (product_id = ".$intProductId.") AND
				(storeroom_id = ".$_SESSION["int_current_storeroom"].")"
		);
		$sale_price = 0;
		$point_price = 0;
		$use_batch_price = 'Y';
		if ($result_set->RowCount() > 0) {
			$sale_price = $result_set->FieldByName('sale_price');
			$point_price = $result_set->FieldByName('point_price');
			$use_batch_price = $result_set->FieldByName('use_batch_price');
		}

		// get the batch price and tax_id
		$result_set->Query("
			SELECT sb.selling_price, sb.tax_id, sb.batch_id
			FROM ".Yearalize('stock_batch')." sb
			WHERE (sb.product_id = ".$intProductId.") AND
				(sb.storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
				(batch_code = '".$aBatchCode."') AND 
				(sb.status = ".STATUS_COMPLETED.") AND
				(sb.deleted = 'N')
			ORDER BY date_created
			");
		$selling_price = 0;
		$tax_id = 0;
		if ($result_set->RowCount() > 0) {
			$selling_price = $result_set->FieldByName('selling_price');
			$tax_id = $result_set->FieldByName('tax_id');
			$batch_id = $result_set->FieldByName('batch_id');
		}

		if ($use_batch_price == 'Y') {
			$arr_retval[0] = number_format(round($selling_price,3),3,'.','');
			$arr_retval[1] = $tax_id;
		}
		else {
			$arr_retval[0] = round($sale_price,3);
			$result_set->Query("SELECT tax_id FROM stock_product WHERE (product_id = ".$intProductId.")");
			$arr_retval[1] = $result_set->FieldByName('tax_id');
		}
		
		$result_set->Free();

		return $arr_retval;
	}

	function create_manual_transfer($int_batch_id, $int_bill_number, $int_bill_id, $int_product_id, $flt_adjusted_qty) {

		$str_retval = 'OK|OK';

		$qry_string = "UPDATE ".Monthalize('stock_storeroom_product')."
			SET stock_current = stock_current + ".$flt_adjusted_qty."
			WHERE (product_id=".$int_product_id.") AND
				(storeroom_id=".$_SESSION["int_current_storeroom"].")";

		// TABLE stock_storeroom_product
		$result_set = new Query($qry_string);
		if ($result_set->b_error == true) {
			$str_retval = "ERROR|Manual transfer - Error updating stock_storeroom_product";
		}

		// TABLE stock_storeroom_batch
		$result_set->Query("UPDATE ".Monthalize('stock_storeroom_batch')."
			SET stock_available = stock_available + ".$flt_adjusted_qty."
			WHERE (batch_id = ".$int_batch_id.") AND
				(storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
				(product_id = ".$int_product_id.")
		");
		if ($result_set->b_error == true) {
			$str_retval = "ERROR|Manual transfer - Error updating stock_storeroom_batch";
		}

		// TABLE stock_balance
		$result_set->Query("UPDATE ".Yearalize('stock_balance')."
				SET stock_sold = stock_sold + ".$flt_adjusted_qty."
				WHERE (product_id = ".$int_product_id.") AND
					(storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
					(balance_month = ".$_SESSION["int_month_loaded"].") AND
					(balance_year = ".$_SESSION["int_year_loaded"].")");
		if ($result_set->b_error == true) {
			$str_retval = "ERROR|Manual transfer - Error updating stock_balance";
		}

		// TABLE stock_transfer
		$result_set->Query("INSERT INTO  ".Monthalize('stock_transfer')."
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
				$flt_adjusted_qty.", '".
				"BILL NUMBER ".$int_bill_number."', '".
				get_bill_date(date('d'))."', ".
				"2, ".
				$_SESSION["int_user_id"].", ".
				$_SESSION["int_current_storeroom"].", ".
				"0, ".
				$int_product_id.", ".
				$int_batch_id.", ".
				$int_bill_id .", ".
				TYPE_ADJUSTMENT.", ".
				STATUS_COMPLETED.", ".
				$_SESSION["int_user_id"].", ".
				"0, ".
				"'N')");
		if ($result_set->b_error == true) {
			$str_retval = "ERROR|Manual transfer - Error updating stock_transfer";
		}

		// TABLE stock_product
		$result_set->Query("
			UPDATE ".Monthalize('stock_storeroom_product')."
			SET stock_adjusted = stock_adjusted + ".$flt_adjusted_qty."
			WHERE (product_id = ".$int_product_id.")
				AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
		");
		if ($result_set->b_error == true) {
			$str_retval = "ERROR|Manual transfer - Error updating stock_storeroom_product";
		}

		return $str_retval;
	}

	function get_bill_date($int_day) {
		$str_date = $_SESSION["int_year_loaded"]."-".sprintf("%02d", $_SESSION["int_month_loaded"])."-".$int_day." ".date("H:i:s");
		return $str_date;
	}

?>