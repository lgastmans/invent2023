<?
//==================================================
// this array holds the information of the batches
// that have been affected. The batches transferred
// should keep their corresponding prices
//--------------------------------------------------

$arr_batch_details = array();

function deduct_stock($int_product_id, $flt_quantity, $int_destination_storeroom, $int_day, $str_reference='') {
	
	//====================
	// Check the current stock against the specified quantity
	//====================
	$qry_transaction = new Query("
	    SELECT *
	    FROM ".Monthalize('stock_storeroom_product')."
	    WHERE (product_id=".$int_product_id.") AND
		    (storeroom_id=".$_SESSION["int_current_storeroom"].")
	");
	
	if ($qry_transaction->FieldByName('stock_current') < $flt_quantity) {
	    $bool_success = false;
	    $str_retval = "ERROR|Quantity specified cannot be greater than available stock";
	    return $str_retval;
	}
	
	//====================
	// query initialization
	//====================
	$qry_transaction->Query("
	    SELECT storeroom_code
	    FROM stock_storeroom
	    WHERE storeroom_id = ".$int_destination_storeroom
	);
	$str_storeroom = $qry_transaction->FieldByName('storeroom_code');
	
	//====================
	// get the batch breakdown for the product
	//====================
	$arr_retval = array();
	$arr_retval = get_product_batch_details(
		$int_product_id,
		$flt_quantity
	);
	
	global $arr_batch_details;
	$arr_batch_details = $arr_retval;
	
	$bool_success = true;
	$str_retval = "OK|true";
	
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
				0,
				0,
				$arr_retval[$j][2],
				$arr_retval[$j][4],
				$int_day);
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
				SET stock_out = stock_out + ".$arr_retval[$j][3].",
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
				transfer_reference,
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
				"DISPATCHED TO ".$str_storeroom."', '".
				$str_reference."', '".
				get_date($int_day)."', ".
				"1, ".
				$_SESSION["int_user_id"].", ".
				$_SESSION["int_current_storeroom"].", ".
				"0, ".
				$arr_retval[$j][2].", ".
				$arr_retval[$j][1].", ".
				"0, ".
				TYPE_INTERNAL.", ".
				STATUS_COMPLETED.", ".
				$_SESSION["int_user_id"].", ".
				"0, ".
				"'N')");
		if ($qry_transaction->b_error == true) {
			$bool_success = false;
			$str_retval = "ERROR|Error updating stock_transfer";
		}
	} // end of $j loop = count($arr_retval)
	
	return $str_retval;
}

    function get_date($int_day) {
		$str_retval = date('Y', time())."-".date('m', time())."-".$int_day." ".date('H:i:s', time());
		return $str_retval;
    }
    
//==============================================================================
// function get_product_batch_details
//------------------------------------------------------------------------------

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
			$arr_retval[0][5] = $arr_batches[0][3];		// buying price
			$arr_retval[0][6] = $arr_batches[0][4];		// selling price
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
						$arr_retval[$intLength][5] = $arr_batches[$i][3];
						$arr_retval[$intLength][6] = $arr_batches[$i][4];
						
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
						$arr_retval[$intLength][5] = $arr_batches[$i][3];
						$arr_retval[$intLength][6] = $arr_batches[$i][4];
						
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
						$arr_retval[$intLength][5] = $arr_batches[$i][3];
						$arr_retval[$intLength][6] = $arr_batches[$i][4];
						
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
						$arr_retval[$intLength][5] = $arr_batches[$i][3];
						$arr_retval[$intLength][6] = $arr_batches[$i][4];
						
						break;
					}
				}
				
				$intLength = count($arr_retval);
				
				$arr_retval[$intLength][0] = $arr_batches[$i][0];
				$arr_retval[$intLength][1] = $arr_batches[$i][2];
				$arr_retval[$intLength][2] = $intProductId;
				$arr_retval[$intLength][3] = number_format($arr_batches[$i][1], 3,'.','');
				$arr_retval[$intLength][4] = number_format($flt_Remainder, 3,'.','');;
				$arr_retval[$intLength][5] = $arr_batches[$i][3];
				$arr_retval[$intLength][6] = $arr_batches[$i][4];
			}
		}
		return $arr_retval;
	} // end of function

//==============================================================================
// function get_active_batches
//------------------------------------------------------------------------------

	function check_active_batches($int_id, $int_current_storeroom) {
		$str_batches = "
			SELECT stock_storeroom_batch_id, batch_id, @cur_id := product_id AS product_id,
				(SELECT 
					COUNT(batch_id) 
					FROM ".Monthalize('stock_storeroom_batch')."
					WHERE stock_available  > 0 
						AND is_active = 'Y' 
						AND product_id = @cur_id
						AND storeroom_id = ".$int_current_storeroom."
				) AS counter
			FROM ".Monthalize('stock_storeroom_batch')."
			WHERE is_active = 'Y'
				AND stock_available = 0
				AND product_id = $int_id
				AND storeroom_id = ".$int_current_storeroom;
		$qry_batches = new Query($str_batches);
		
		$arr_batches = array();
		
		for ($i=0; $i < $qry_batches->RowCount(); $i++) {
			if ($qry_batches->FieldByName('counter') > 0)
				$arr_batches[] = $qry_batches->FieldByName('stock_storeroom_batch_id');
			
			$qry_batches->Next();
		}
		
		return $arr_batches;
	}

//==============================================================================
// function get_product_batches
//------------------------------------------------------------------------------

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
		$arr_result = check_active_batches($intProductId, $_SESSION['int_current_storeroom']);
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
			$arr_retval[$i][3] = $qry_batches->FieldByName('buying_price');
			$arr_retval[$i][4] = $qry_batches->FieldByName('selling_price');

			$qry_batches->Next();
		}

		$qry_batches->Free();

		return $arr_retval;
	}

//==============================================================================
// function create_manual_transfer
//------------------------------------------------------------------------------

	function create_manual_transfer($int_batch_id, $int_bill_number, $int_bill_id, $int_product_id, $flt_adjusted_qty, $int_day) {

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
				"INTERNAL TRANSFER', '".
				get_date($int_day)."', ".
				"1, ".
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
	


//==============================================================================
// ADD STOCK
//------------------------------------------------------------------------------
// function add_stock
//------------------------------------------------------------------------------

function add_stock($int_product_id, $int_destination_storeroom, $flt_quantity, $int_day, $str_reference='') {
	
	$bool_success = true;
	$str_message = 'OK|true';
	
	$stock_received = number_format($flt_quantity, 3, '.', '');
	$actual_stock_received = number_format($flt_quantity, 3, '.', '');
	$str_adjusted = "";
	$stock_adjusted = 0;
	
	$qry = new Query("SELECT storeroom_code FROM stock_storeroom WHERE storeroom_id = ".$_SESSION['int_current_storeroom']);
	$str_storeroom = $qry->FieldByName('storeroom_code');
	
	//==============================================================
	// check whether the product exists in the destination storeroom
	//--------------------------------------------------------------
	$qry->Query("
		SELECT *
		FROM ".Monthalize('stock_storeroom_product')."
		WHERE (product_id = ".$int_product_id.")
			AND (storeroom_id = ".$int_destination_storeroom.")
	");
    if ($qry->RowCount() == 0) {
		// the product was not found in the destination storeroom
		// create it
		$qry->Query("
			SELECT *
			FROM ".Monthalize('stock_storeroom_product')."
			WHERE (product_id = ".$int_product_id.")
				AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
		");
		$qry_insert = new Query("
			INSERT INTO ".Monthalize('stock_storeroom_product')."
			(
				product_id,
				storeroom_id,
				stock_current,
				stock_reserved,
				stock_ordered,
				stock_adjusted,
				stock_minimum,
				buying_price,
				sale_price,
				point_price,
				use_batch_price,
				discount_qty,
				discount_percent
			)
			VALUES (
				".$int_product_id.",
				".$int_destination_storeroom.",
				0,
				0,
				0,
				0,
				".$qry->FieldByName('stock_minimum').",
				".$qry->FieldByName('buying_price').",
				".$qry->FieldByName('sale_price').",
				".$qry->FieldByName('point_price').",
				'".$qry->FieldByName('use_batch_price')."',
				".$qry->FieldByName('discount_qty').",
				".$qry->FieldByName('discount_percent')."
			)
		");
		if ($qry_insert->b_error == true) {
			$str_message = "ERROR|Product not found in destination storeroom and could not be created ";
			$bool_success = false;
			return $str_message;
		}
		$qry->Query("
			SELECT *
			FROM ".Monthalize('stock_storeroom_product')."
			WHERE (product_id = ".$int_product_id.")
			AND (storeroom_id = ".$int_destination_storeroom.")
		");
	}

	//=====================================
	// update the adjusted stock, if any
	//-------------------------------------
	$qry->Query("
		SELECT stock_adjusted
		FROM ".Monthalize('stock_storeroom_product')."
		WHERE (product_id = ".$int_product_id.")
			AND (storeroom_id = ".$int_destination_storeroom.")
	");
	if ($qry->RowCount() > 0) {
		if ($qry->FieldByName('stock_adjusted') > 0) {
			if ($qry->FieldByName('stock_adjusted') > $stock_received) {
				// update the stock_adjusted in stock_storeroom_product
				$qry_adjust = new Query("
					UPDATE ".Monthalize('stock_storeroom_product')."
					SET stock_adjusted = stock_adjusted - ROUND(".$stock_received.", 3)
					WHERE (product_id = ".$int_product_id.")
					AND (storeroom_id = ".$int_destination_storeroom.")
				");
				
				$str_adjusted = ", adjusted: ".$stock_received;
				$stock_adjusted = $stock_received;
				$stock_received = 0;
			}
			else {
				// update the stock_adjusted in stock_storeroom_product
				$qry_adjust = new Query("
					UPDATE ".Monthalize('stock_storeroom_product')."
					SET stock_adjusted = 0
					WHERE (product_id = ".$int_product_id.")
						AND (storeroom_id = ".$int_destination_storeroom.")
			    ");
				$str_adjusted = ", adjusted: ".$qry->FieldByName('stock_adjusted');
				$stock_adjusted = number_format($qry->FieldByName('stock_adjusted'), 3, '.', '');
				$stock_received = $stock_received - $qry->FieldByName('stock_adjusted');
			}
		}
	}

	//=============================================
	// make sure there is at least one active batch
	//---------------------------------------------
	$qry_batches = new Query("
		SELECT *
		FROM ".Monthalize('stock_storeroom_batch')."
		WHERE (storeroom_id = ".$int_destination_storeroom.") 
			AND (product_id = ".$int_product_id.")
			AND (is_active = 'Y')
	");
	$strBatches = $qry_batches->RowCount();
	if ($qry_batches->RowCount() == 0) {
		// make the most recent batch active
		$qry_batches->Query("
			SELECT ssb.stock_storeroom_batch_id
			FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb
			WHERE (sb.product_id = ".$int_product_id.") AND
				(sb.storeroom_id = ".$int_destination_storeroom.") AND
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
					AND (storeroom_id = ".$int_destination_storeroom.")
			");
		}
	}
	
	//=============================================
	// make sure is_active is false when positive stock batches are available
	//---------------------------------------------
	$arr_result = check_active_batches($int_product_id, $int_destination_storeroom);
	if (count($arr_result) > 0) {
		for ($i=0;$i<count($arr_result);$i++) {
			$str_update = "
				UPDATE ".Monthalize('stock_storeroom_batch')."
				SET is_active = 'N'
				WHERE stock_storeroom_batch_id = ".$arr_result[$i]."
					AND storeroom_id = ".$int_destination_storeroom."
				LIMIT 1
			";
			$qry_batches->Query($str_update);
		}
	}

	//=========================================
	// get the details of the most recent batch
	//-----------------------------------------
	$qry->Query("
		SELECT * 
		FROM ".Monthalize('stock_storeroom_product')." ssp, stock_product sp
		WHERE (sp.product_id = ".$int_product_id.")
		AND (ssp.product_id = sp.product_id)
		AND (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")
	");
	
	if ($qry->FieldByName('use_batch_price') == 'Y') {
		$qry_prices = new Query("
			SELECT * 
			FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb
			WHERE (sb.product_id = ".$int_product_id.") 
				AND (sb.storeroom_id = ".$_SESSION['int_current_storeroom'].") 
				AND (sb.status = ".STATUS_COMPLETED.") 
				AND (sb.deleted = 'N') 
				AND (ssb.product_id = sb.product_id) 
				AND (ssb.batch_id = sb.batch_id) 
				AND (ssb.storeroom_id = sb.storeroom_id) 
				AND (ssb.is_active = 'Y')
			ORDER BY date_created DESC 
			LIMIT 1
		");
		if ($qry_prices->RowCount() > 0) {
			$flt_bprice = $qry_prices->FieldByName('buying_price');
			$flt_sprice = $qry_prices->FieldByName('selling_price');
			$int_tax = $qry_prices->FieldByName('tax_id');
			$int_supplier_id = $qry_prices->FieldByName('supplier_id');
		}
		
		$qry_prices->Query("SELECT minimum_qty FROM stock_product WHERE product_id = ".$int_product_id);
		$int_min_qty = $qry_prices->FieldByName('minimum_qty');
	}
	else {
		$flt_bprice = $qry->FieldByName('buying_price');
		$flt_sprice = $qry->FieldByName('sale_price');
		$int_tax = $qry->FieldByName('tax_id');
		$int_supplier_id = $qry->FieldByName('supplier_id');
		$int_min_qty = $qry->FieldByName('minimum_qty');
	}

	global $arr_batch_details;
	
	//===================================
	// create batches and save batch code
	//-----------------------------------
	$flt_remaining_adjusted = $stock_adjusted;
	
	for ($i=0;$i<count($arr_batch_details);$i++) {
		
		$int_stock_received = $arr_batch_details[$i][3];
		$str_description = "RECEIVED FROM ".$str_storeroom;
		if ($flt_remaining_adjusted > 0) {
			if ($int_stock_received > $flt_remaining_adjusted) {
				$int_stock_received = $int_stock_received - $flt_remaining_adjusted;
				$str_description .= " (adjusted ".$flt_remaining_adjusted.")";
				$flt_remaining_adjusted = 0;
			}
			else {
				$str_description .= " (adjusted ".$int_stock_received.")";
				$flt_remaining_adjusted = $flt_remaining_adjusted - $int_stock_received;
				$int_stock_received = 0;
			}
		}
		
		if ($int_stock_received > 0) {
			$str_query = "
				INSERT INTO ".Yearalize('stock_batch')."
					(buying_price,
					selling_price,
					date_created,
					opening_balance,
					date_manufacture,
					date_expiry,
					is_active,
					status,
					user_id,
					buyer_id,
					supplier_id,
					product_id,
					storeroom_id,
					tax_id)
				VALUES(".
					$arr_batch_details[$i][5].", ".
					$arr_batch_details[$i][6].", '".
					get_date($int_day)."', ".
					$int_stock_received.", '".
					date('Y-m-d', time())."', '".
					date('Y-m-d', time())."', ".
					"'Y', '".
					STATUS_COMPLETED."', ".
					$_SESSION["int_user_id"].", ".
					$_SESSION["int_user_id"].", ".
					$int_supplier_id.", ".
					$int_product_id.", ".
					$int_destination_storeroom.", ".
					$int_tax."
			)";
			$qry->Query($str_query);
			if ($qry->b_error == true) {
				$str_message = "ERROR|error inserting into ".Yearalize('stock_batch');
				$bool_success = false;
			}
			$int_batch_id = $qry->getInsertedID();
			
			//============================================================
			// set the batch code to the autoincremental value of batch_id
			//------------------------------------------------------------
			$qry->Query("
				UPDATE ".Yearalize('stock_batch')."
				SET batch_code = '".$int_batch_id."'
				WHERE (batch_id=".$int_batch_id.")
					AND (storeroom_id = ".$int_destination_storeroom.")
			");
			
			//============================================================
			// update stock_storeroom_product
			// check whether an entry exists already
			//------------------------------------------------------------
			$qry->Query("
				SELECT *
				FROM ".Monthalize('stock_storeroom_product')."
				WHERE (product_id = ".$int_product_id.")
					AND (storeroom_id = ".$int_destination_storeroom.")
			");
			if ($qry->RowCount() > 0) {
				$str_query = "
					UPDATE ".Monthalize('stock_storeroom_product')."
					SET stock_current = stock_current + ".$int_stock_received."
					WHERE (product_id=".$int_product_id.")
						AND (storeroom_id=".$int_destination_storeroom.")";
				$qry->Query($str_query);
				if ($qry->b_error == true) {
					echo $str_query;
					$str_message = "ERROR|error (adding) updating ".Monthalize('stock_storeroom_product')." ".mysql_error();
					$bool_success = false;
				}
			}
			else {
				$qry->Query("
					INSERT INTO ".Monthalize('stock_storeroom_product')."
					(	product_id,
						storeroom_id,
						stock_current,
						stock_minimum,
						buying_price,
						sale_price)
					VALUES(".
						$int_product_id.", ".
						$int_destination_storeroom.", ".
						$int_stock_received.", ".
						$int_min_qty.", ".
						$arr_batch_details[$i][5].", ".
						$arr_batch_details[$i][6].")
				");
				if ($qry->b_error == true) {
					$str_message = "ERROR|error inserting into ".Monthalize('stock_storeroom_product');
					$bool_success = false;
				}
			}
			
			//===============================================================
			// flag is_active to false where stock_available is zero or below
			//---------------------------------------------------------------
			$qry->Query("
				UPDATE ".Monthalize('stock_storeroom_batch')."
				SET is_active = 'N',
					debug = 'receive'
				WHERE (storeroom_id = ".$int_destination_storeroom.")
					AND (product_id = ".$int_product_id.")
					AND (stock_available <= 0)
			");
			
			//=============================
			// insert stock_storeroom_batch
			//-----------------------------
			$qry->Query("
				INSERT INTO ".Monthalize('stock_storeroom_batch')."
					(stock_available,
					shelf_id,
					batch_id,
					storeroom_id,
					product_id)
				VALUES (".$int_stock_received.",
					0, ".
					$int_batch_id.", ".
					$int_destination_storeroom.", ".
					$int_product_id.")");
			if ($qry->b_error == true) {
				$str_message = "ERROR|error updating ".Monthalize('stock_storeroom_batch');
				$bool_success = false;
			}
			
			//=====================
			// update stock_balance
			//---------------------
			$qry->Query("
				SELECT *
				FROM ".Yearalize('stock_balance')."
				WHERE (product_id = ".$int_product_id.")
					AND (storeroom_id = ".$int_destination_storeroom.")
					AND (balance_month = ".$_SESSION["int_month_loaded"].")
					AND (balance_year = ".$_SESSION["int_year_loaded"].")
					
			");
			if ($qry->RowCount() > 0) {
				$qry->Query("
					UPDATE ".Yearalize('stock_balance')."
					SET stock_in = stock_in + ".($arr_batch_details[$i][3]).",
						stock_closing_balance = stock_closing_balance + ".($int_stock_received)."
					WHERE (product_id = ".$int_product_id.")
						AND (storeroom_id = ".$int_destination_storeroom.")
						AND (balance_month = ".$_SESSION["int_month_loaded"].")
						AND (balance_year = ".$_SESSION["int_year_loaded"].")
						
				");
				if ($qry->b_error == true) {
					$str_message = "ERROR|error updating ".Yearalize('stock_balance');
					$bool_success = false;
				}
			}
			else {
				$qry->Query("
				INSERT INTO ".Yearalize('stock_balance')."
					(stock_closing_balance,
					balance_month,
					balance_year,
					stock_in,
					product_id,
					storeroom_id)
				VALUES (".
					$int_stock_received.", ".
					$_SESSION["int_month_loaded"].", ".
					$_SESSION["int_year_loaded"].", ".
					$arr_batch_details[$i][3].", ".
					$int_product_id.", ".
					$int_destination_storeroom."
				)
				");
				if ($qry->b_error == true) {
					$str_message = "ERROR|error inserting into ".Yearalize('stock_balance');
					$bool_success = false;
				}
			}
			
			$str_insert = "
				INSERT INTO ".Monthalize('stock_transfer')."
					(transfer_quantity,
					transfer_description,
					transfer_reference,
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
					$arr_batch_details[$i][3].", '".
					$str_description."', '".
					$str_reference."', '".
					get_date($int_day)."', ".
					"1, ".
					$_SESSION["int_user_id"].", ".
					"0, ".
					$int_destination_storeroom.", ".
					$int_product_id.", ".
					$int_batch_id.", ".
					"0, ".
					TYPE_INTERNAL.", ".
					STATUS_COMPLETED.", ".
					$_SESSION["int_user_id"].", ".
					$_SESSION["int_user_id"].", ".
				"'N')
			";
			$qry->Query($str_insert);
			
			if ($qry->b_error == true) {
				$str_message = "ERROR|error inserting into ".Monthalize('stock_transfer')."- ".$str_insert;
				$bool_success = false;
			}
		} // end of if ($int_stock_received > 0)
		else {
			$qry->Query("
				UPDATE ".Yearalize('stock_balance')."
				SET stock_in = stock_in + ".$arr_batch_details[$i][3]."
				WHERE (product_id = ".$int_product_id.")
					AND (storeroom_id = ".$int_destination_storeroom.")
					AND (balance_month = ".$_SESSION["int_month_loaded"].")
					AND (balance_year = ".$_SESSION["int_year_loaded"].")
					
			");
			if ($qry->b_error == true) {
				$str_message = "ERROR|error updating adjusted ".Yearalize('stock_balance');
				$bool_success = false;
			}
			
			$str_insert = "
				INSERT INTO ".Monthalize('stock_transfer')."
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
					$arr_batch_details[$i][3].", '".
					$str_description."', '".
					get_date($int_day)."', ".
					"1, ".
					$_SESSION["int_user_id"].", ".
					"0, ".
					$int_destination_storeroom.", ".
					$int_product_id.", ".
					"0, ".
					"0, ".
					TYPE_INTERNAL.", ".
					STATUS_COMPLETED.", ".
					$_SESSION["int_user_id"].", ".
					$_SESSION["int_user_id"].", ".
				"'N')
			";
			$qry->Query($str_insert);
			
			if ($qry->b_error == true) {
				$str_message = "ERROR|error inserting into ".Monthalize('stock_transfer')."- ".$str_insert;
				$bool_success = false;
			}
		}
	} // end of $arr_batch_details loop
	
	return $str_message;
}

?>