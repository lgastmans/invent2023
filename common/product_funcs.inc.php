<?
if (file_exists('../include/const.inc.php')) {
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
}
else if (file_exists('../../include/const.inc.php')) {
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");
}
else if (file_exists('../../../include/const.inc.php')) {
	require_once("../../../include/const.inc.php");
	require_once("../../../include/session.inc.php");
	require_once("../../../include/db.inc.php");
}



/*
	return in an array the months across which
	to retrieve the quantity sold per item
*/
function get_current_months() {

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

	return $period;
}


//=================================================
// return a products buying price
// in case the product's use_batch_price is enabled
// the price of the most recent batch is retured,
// or, if the batch is given, the price of the batch
// else
// the global price is returned
//-------------------------------------------------
function getBuyingPrice($int_product_id, $batch_id=0 ) {
	$flt_buying_price = 0;
	
	if ($batch_id > 0 ) {
		$sql = "
			SELECT IF(ssp.use_batch_price = 'Y', sb.buying_price, ssp.buying_price) AS buying_price
			FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb, ".Monthalize('stock_storeroom_product')." ssp
			WHERE (sb.product_id = ".$int_product_id.")
				AND (sb.storeroom_id = ".$_SESSION["int_current_storeroom"].")
				AND (ssp.product_id = ".$int_product_id.")
				AND (ssp.storeroom_id =".$_SESSION["int_current_storeroom"].")
				AND (sb.status = ".STATUS_COMPLETED.")
				AND (sb.deleted = 'N')
				AND (ssb.product_id = sb.product_id)
				AND (sb.batch_id = $batch_id)
				AND (ssb.batch_id = sb.batch_id)
				AND (ssb.storeroom_id = sb.storeroom_id)
			ORDER BY date_created DESC
		";
		//echo $sql."<br><br>";
		$qry = new Query($sql);
	}
	else
		$qry = new Query("
			SELECT IF(ssp.use_batch_price = 'Y', sb.buying_price, ssp.buying_price) AS buying_price
			FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb, ".Monthalize('stock_storeroom_product')." ssp
			WHERE (sb.product_id = ".$int_product_id.")
				AND (sb.storeroom_id = ".$_SESSION["int_current_storeroom"].")
				AND (ssp.product_id = ".$int_product_id.")
				AND (ssp.storeroom_id =".$_SESSION["int_current_storeroom"].")
				AND (sb.status = ".STATUS_COMPLETED.")
				AND (sb.deleted = 'N')
				AND (ssb.product_id = sb.product_id)
				AND (ssb.batch_id = sb.batch_id)
				AND (ssb.storeroom_id = sb.storeroom_id)
			ORDER BY date_created DESC
		");
	
	if ($qry->RowCount() > 0)
		$flt_buying_price = $qry->FieldByName('buying_price');
	
	return number_format($flt_buying_price,2,'.','');
}

/*
	return a product's selling price
	--------------------------------
	if the product's use_batch_price is enabled
			the price of the most recent batch is retured
		OR
			the price of the specified batch id is returned
	else
		the global price is returned
*/ 
function getSellingPrice($int_product_id, $int_batch_id=0) {
	$flt_selling_price = 0;
	
	if ($int_batch_id > 0) {
		$sql = "
			SELECT IF(ssp.use_batch_price = 'Y', sb.selling_price, ssp.sale_price) AS selling_price
			FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb, ".Monthalize('stock_storeroom_product')." ssp
			WHERE (sb.product_id = ".$int_product_id.")
				AND (sb.storeroom_id = ".$_SESSION["int_current_storeroom"].")
				AND (ssp.product_id = ".$int_product_id.")
				AND (ssp.storeroom_id =".$_SESSION["int_current_storeroom"].")
				AND (sb.status = ".STATUS_COMPLETED.")
				AND (sb.deleted = 'N')
				AND (ssb.product_id = sb.product_id)
				AND (ssb.batch_id = sb.batch_id)
				AND (ssb.storeroom_id = sb.storeroom_id)
				
				AND (sb.batch_id = $int_batch_id)
			ORDER BY date_created DESC
		";
		//echo $sql."<br>";
		$qry = new Query($sql);
	} else
		$qry = new Query("
			SELECT IF(ssp.use_batch_price = 'Y', sb.selling_price, ssp.sale_price) AS selling_price
			FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb, ".Monthalize('stock_storeroom_product')." ssp
			WHERE (sb.product_id = ".$int_product_id.")
				AND (sb.storeroom_id = ".$_SESSION["int_current_storeroom"].")
				AND (ssp.product_id = ".$int_product_id.")
				AND (ssp.storeroom_id =".$_SESSION["int_current_storeroom"].")
				AND (sb.status = ".STATUS_COMPLETED.")
				AND (sb.deleted = 'N')
				AND (ssb.product_id = sb.product_id)
				AND (ssb.batch_id = sb.batch_id)
				AND (ssb.storeroom_id = sb.storeroom_id)
				AND (ssb.is_active = 'Y')
			ORDER BY date_created DESC
		");
	if ($qry->RowCount() > 0)
		$flt_selling_price = $qry->FieldByName('selling_price');
	
	return number_format($flt_selling_price,2,'.','');
}

//===================================================================
// returns an array containing the active batches for a given product
// 		0 - batch id
// 		1 - batch code
// 		2 - quantity
//-------------------------------------------------------------------
function get_active_batches($int_product_id=0, $str_product_code='', $is_bar_code='N') {
	$arr_batches = array();
	
	// query initialization
	$qry = new Query("SELECT * FROM stock_product LIMIT 1");
	
	if ($int_product_id == 0) {
		//***
		// locate the product for the given code and save the id
		//***
		if ($is_bar_code == 'Y')
			$qry->Query("
				SELECT product_id
				FROM stock_product
				WHERE (product_bar_code = '".$str_product_code."')"
			);
		else
			$qry->Query("
				SELECT product_id
				FROM stock_product
				WHERE (product_code = '".$str_product_code."')"
			);
		
		$int_product_id = $qry->FieldByName('product_id');
	}
	

	if ($str_product_code != 'nil') {
		if ($int_product_id > 0) {
			//***
			// make sure there is at least one active batch
			//***
			$qry->Query("
				SELECT *
				FROM ".Monthalize('stock_storeroom_batch')."
				WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].") 
					AND (product_id = ".$int_product_id.")
					AND (is_active = 'Y')
			");
			
			if ($qry->RowCount() == 0) {
				//***
				// make the most recent batch active
				//***
				$qry->Query("
					SELECT ssb.stock_storeroom_batch_id
					FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb
					WHERE (sb.product_id = ".$int_product_id.") AND
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
				if ($qry->RowCount() > 0) {
					$qry->First();
					$int_ssb_batch_id = $qry->FieldByName('stock_storeroom_batch_id');
					
					$qry->Query("
						UPDATE ".Monthalize('stock_storeroom_batch')."
						SET is_active = 'Y'
						WHERE (stock_storeroom_batch_id = ".$int_ssb_batch_id.")
							AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
					");
				}
			}
			
			//***
			// double check:
			// make sure is_active is false when positive stock batches are available
			//***
			$arr_result = check_active_batches($int_product_id);
			
			if (count($arr_result) > 0) {
				for ($i=0;$i<count($arr_result);$i++) {
					$str_update = "
						UPDATE ".Monthalize('stock_storeroom_batch')."
						SET is_active = 'N'
						WHERE (stock_storeroom_batch_id = ".$arr_result[$i].")
							AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
						LIMIT 1
					";
					$qry->Query($str_update);
				}
			}
		
			//***
			// get the batches and quantities available for the given product
			//***
			$qry->Query("
				SELECT sb.batch_id, sb.batch_code, ssb.stock_available
				FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb
				WHERE (sb.product_id = ".$int_product_id.") AND
					(sb.storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
					(sb.status = ".STATUS_COMPLETED.") AND
					(sb.deleted = 'N') AND
					(ssb.product_id = sb.product_id) AND
					(ssb.batch_id = sb.batch_id) AND
					(ssb.storeroom_id = sb.storeroom_id) AND 
					(ssb.is_active = 'Y')
				ORDER BY date_created
			");
		
			if ($qry->RowCount() > 0) {
				//***
				// save the batch id, code and quantity
				//***
				for ($i=0; $i<$qry->RowCount(); $i++) {
					$arr_batches[$i][0] = $qry->FieldByName('batch_code');
					$arr_batches[$i][1] = $qry->FieldByName('batch_id');
					$arr_batches[$i][2] = $qry->FieldByName('stock_available');
					
					$qry->Next();
				}
			}
		}
	}
	
	return $arr_batches;
}

//====================================
// function used by get_active_batches
//------------------------------------
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


/**
 * 
 * Correct the stock of a product
 * This code was in transfers/stock_correct.php
 * but made into a function
 * 
 * $_POST["code"] 		was changed to $product_code
 * $_POST["correct"]	was changed to $corrected_stock
 * $_POST["note"]		was changed to $note
 * 
 */

function stock_correct($product_id, $corrected_stock, $note='') {

	// verify product code
	$can_save = true;
	/**
	 * this assignment is because this code first came from /transfers/stock_correct.php
	 * where the product_code was used to find the id.
	 */
	$int_product_id = $product_id;

	// dummy initialisation of variable $qry
	$qry = new Query("SELECT product_id FROM stock_product LIMIT 1");
		
	if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
		$str_message = '';
	}
	else {
		$str_message = 'Cannot correct in previous months. \\n Select the current month/year and continue.';
		$can_save = false;
	}
	
	if (empty($corrected_stock)) {
		$flt_correct = 0;
	}
	else {
		$flt_correct = number_format(floatval($corrected_stock), 3, '.', '');
	}
	
	// negative not allowed
	if ($flt_correct < 0) {
		$str_message = "Corrected stock cannot be negative";
		$can_save = false;
	}
	
	if ($can_save) {
		
		$qry->Query("START TRANSACTION");
		
		$bool_success = true;
		
		//=======================
		// double check: get the quantity of stock across active batches for
		// this product, and set the stock_storeroom_product.current_stock
		// to this quantity in case they don't match
		//=======================
		$qry_check_stock = new Query("
			SELECT SUM(stock_available) AS stock_available
			FROM ".Monthalize('stock_storeroom_batch')." ssb
			WHERE (ssb.product_id = ".$int_product_id.")
				AND (ssb.storeroom_id = ".$_SESSION["int_current_storeroom"].")
				AND (ssb.is_active = 'Y')
		");
		if ($qry_check_stock->RowCount() > 0) {
			$qry_current_stock = new Query("SELECT stock_current
				FROM ".Monthalize('stock_storeroom_product')."
				WHERE (product_id = ".$int_product_id.")
					AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
			");
			$flt_check_stock = number_format($qry_check_stock->FieldByName('stock_available'), 3, '.', '');
				$flt_current_stock = number_format($qry_current_stock->FieldByName('stock_current'), 3, '.', '');
				if ($flt_check_stock != $flt_current_stock) {
					$qry_current_stock->Query("
						UPDATE ".Monthalize('stock_storeroom_product')."
						SET stock_current = ".$flt_check_stock."
						WHERE (product_id = ".$int_product_id.")
							AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
					");
				}
		}

		//=======================
		// nullify any adjusted stock
		//=======================
		$qry_adjust = new Query("
			SELECT stock_adjusted
			FROM ".Monthalize('stock_storeroom_product')."
			WHERE (product_id = $int_product_id)
				AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
		");
		$flt_adjusted_stock = number_format($qry_adjust->FieldByName('stock_adjusted'),3,'.','');
		$qry_adjust->Query("
			UPDATE ".Monthalize('stock_storeroom_product')."
			SET stock_adjusted = 0
			WHERE (product_id = ".$int_product_id.")
				AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
		");
		
		//==================================================================
		// update in table stock_balance the field 'stock_mismatch_addition'
		//------------------------------------------------------------------
		$qry_adjust->Query("
			UPDATE ".Yearalize('stock_balance')."
			SET stock_mismatch_addition = stock_mismatch_addition + ".$flt_adjusted_stock."
			WHERE (product_id = ".$int_product_id.")
				AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
				AND (balance_month = ".$_SESSION["int_month_loaded"].")
				AND (balance_year = ".$_SESSION["int_year_loaded"].")
		");
		
		//=======================
		// and create a transfer for the nullified amount
		//=======================
		if ($flt_adjusted_stock > 0) {
			$qry->Query("INSERT INTO ".Monthalize('stock_transfer')."
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
					$flt_adjusted_stock.", '".
					"CORRECTION, nullified ".$flt_adjusted_stock."', '".
					date('Y-m-d H:i:s')."', ".
					"3, ".
					$_SESSION["int_user_id"].", ".
					"0, ".
					$_SESSION["int_current_storeroom"].", ".
					$int_product_id.", ".
					"0, ".
					"0, ".
					TYPE_CORRECTED.", ".
					STATUS_COMPLETED.", ".
					$_SESSION["int_user_id"].", ".
					$_SESSION["int_user_id"].", ".
					"'N')");
			if ($qry->b_error == true) {
					$str_message = "error inserting into ".Monthalize('stock_transfer');
					$bool_success = false;
			}
		}
		
		$qry->Query("SELECT stock_current
			FROM ".Monthalize('stock_storeroom_product')."
			WHERE product_id = ".$int_product_id."
			AND storeroom_id = ".$_SESSION['int_current_storeroom']."
		");
		$flt_current_stock = number_format($qry->FieldByName('stock_current'), 3, '.', '');
		$flt_corrected_by = number_format(($flt_current_stock - $flt_correct), 3, '.', '');
		$flt_corrected_by = number_format(($flt_corrected_by * -1),3,'.','');
		
		#echo "<script language=\"javascript\">";
		#echo "alert('".$flt_current_stock."');";
		#echo "alert('".$flt_correct."');";
		#echo "alert('".$flt_corrected_by."');";
		#echo "</script>";

		/**
		 * 
		 * ADDING STOCK
		 * 
		 * if adding stock, add a batch without setting a supplier
		 * and update the stock
		 * 
		 */
		if ($flt_corrected_by > 0) {
			if (!empty($note))
				$str_description = "CORRECTION, added ".$flt_corrected_by.", ".$note;
			else
				$str_description = "CORRECTION, added ".$flt_corrected_by;
		
			// get the details of the most recent batch for this product
			$str_query = "
				SELECT *
				FROM ".Yearalize('stock_batch')."
				WHERE (product_id = $int_product_id)
					AND (is_active = 'Y')
					AND (status = ".STATUS_COMPLETED.")
					AND (deleted = 'N') 
					AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
				ORDER BY date_created DESC
				LIMIT 1
			";
			$qry->Query($str_query);
			if ($qry->RowCount() > 0) {
				$flt_buying_price = $qry->FieldByName('buying_price');
				$flt_selling_price = $qry->FieldByName('selling_price');
				$int_tax_id = $qry->FieldByName('tax_id');
				$int_supplier_id = $qry->FieldByName('supplier_id');
			}
			else {
				$str_message = "Could not get batch details ".$int_product_id;
				$bool_success = false;
			}
			
			// insert another batch without specifying a supplier
			$str_query = "INSERT INTO ".Yearalize('stock_batch')."
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
						$flt_buying_price.", ".
						$flt_selling_price.", '".
						date('Y-m-d H:i:s')."', ".
						$flt_correct.", '".
						date('Y-m-d H:i:s')."', '".
						date('Y-m-d H:i:s')."', ".
						"'Y', '".
						STATUS_COMPLETED."', ".
						$_SESSION["int_user_id"].", ".
						$_SESSION["int_user_id"].", ".
						$int_supplier_id.", ".
						$int_product_id.", ".
						$_SESSION["int_current_storeroom"].", ".
						$int_tax_id."
						)";
			$qry->Query($str_query);
			if ($qry->b_error == true) {
				$str_error_message = "error inserting into ".Yearalize('stock_batch');
				$bool_success = false;
			}
			$int_batch_id = $qry->getInsertedID();
			// set the batch code to the autoincremental value of batch_id 
			$qry->Query("UPDATE ".Yearalize('stock_batch')."
				SET batch_code = '".$int_batch_id."'
				WHERE (batch_id=".$int_batch_id.")
					AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
			");
						
			// update the stock
			// STOCK_STOREROOM_PRODUCT
			$qry->Query("UPDATE ".Monthalize('stock_storeroom_product')."
				SET stock_current = stock_current + ".$flt_corrected_by."
				WHERE (product_id=".$int_product_id.") AND
					(storeroom_id=".$_SESSION["int_current_storeroom"].")");
			if ($qry->b_error == true) {
				$str_message = "error updating ".Monthalize('stock_storeroom_product');
				$bool_success = false;
			}
				
			// set is_active to false where batches have zero stock
			$qry->Query("
				UPDATE ".Monthalize('stock_storeroom_batch')."
				SET is_active = 'N'
				WHERE (stock_available <= 0) AND
					(storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
					(product_id = ".$int_product_id.")
			");
			
			// STOCK_STOREROOM_BATCH
			$qry->Query("INSERT INTO ".Monthalize('stock_storeroom_batch')."
					(stock_available,
					shelf_id,
					batch_id,
					storeroom_id,
					product_id)
				VALUES (".$flt_corrected_by.",
					0, ".
					$int_batch_id.", ".
					$_SESSION["int_current_storeroom"].", ".
					$int_product_id.")");
			if ($qry->b_error == true) {
				$str_message = "error updating ".Monthalize('stock_storeroom_batch');
				$bool_success = false;
			}
			
			// STOCK_BALANCE
			$qry->Query("
				SELECT *
				FROM ".Yearalize('stock_balance')."
				WHERE (product_id = ".$int_product_id.")
					AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
					AND (balance_month = ".$_SESSION["int_month_loaded"].")
					AND (balance_year = ".$_SESSION["int_year_loaded"].")
			");
			if ($qry->RowCount() > 0) {
				$qry->Query("
					UPDATE ".Yearalize('stock_balance')."
					SET stock_mismatch_addition = stock_mismatch_addition + ".$flt_corrected_by.",
						stock_closing_balance = ".$flt_correct."
					WHERE (product_id = ".$int_product_id.")
						AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
						AND (balance_month = ".$_SESSION["int_month_loaded"].")
						AND (balance_year = ".$_SESSION["int_year_loaded"].")
				");
				if ($qry->b_error == true) {
					$str_message = "error updating ".Yearalize('stock_balance');
					$bool_success = false;
				}
			}
			else {
				$qry->Query("
					INSERT INTO ".Yearalize('stock_balance')."
					(stock_mismatch_addition,
						stock_closing_balance,
						product_id,
						storeroom_id,
						balance_month,
						balance_year)
					VALUES (
						".$flt_corrected_by.",
						".$flt_correct.",
						".$int_product_id.",
						".$_SESSION["int_current_storeroom"].",
						".$_SESSION["int_month_loaded"].",
						".$_SESSION["int_year_loaded"]."
					)
				");
				if ($qry->b_error == true) {
					$str_message = "error inserting into ".Yearalize('stock_balance');
					$bool_success = false;
				}
			}
				
			// STOCK_TRANSFER
			$qry->Query("INSERT INTO ".Monthalize('stock_transfer')."
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
					$flt_corrected_by.", '".
					$str_description."', '".
					date('Y-m-d')."', ".
					"3, ".
					$_SESSION["int_user_id"].", ".
					"0, ".
					$_SESSION["int_current_storeroom"].", ".
					$int_product_id.", ".
					$int_batch_id.", ".
					"0, ".
					TYPE_CORRECTED.", ".
					STATUS_COMPLETED.", ".
					$_SESSION["int_user_id"].", ".
					$_SESSION["int_user_id"].", ".
					"'N')");
			if ($qry->b_error == true) {
					$str_message = "error inserting into ".Monthalize('stock_transfer');
					$bool_success = false;
			}
		}
		/**
		 *
		 * DEDUCTING STOCK
		 * 
		 * if deducting stock, remove from batches
		 * 
		 */
		else {
			
			if (!empty($note))
				$str_description = "CORRECTION, deducted (total ".$flt_corrected_by."), ".$note;
			else
				$str_description = "CORRECTION, deducted (total ".$flt_corrected_by.")";
			
			// get the batches and quantities available for the given product
			$qry_batches = new Query("
				SELECT sb.batch_id, ssb.stock_available
				FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb
				WHERE (sb.product_id = ".$int_product_id.") AND
					(sb.storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
					(sb.is_active = 'Y') AND
					(sb.status = ".STATUS_COMPLETED.") AND
					(sb.deleted = 'N') AND
					(ssb.product_id = sb.product_id) AND
					(ssb.batch_id = sb.batch_id) AND
					(ssb.storeroom_id = sb.storeroom_id) AND 
					(ssb.is_active = 'Y')
				ORDER BY date_created
			");
			
			// dummy query initialisation
			$result_set = new Query("SELECT * FROM stock_batch LIMIT 1");
			
			// deduct from batches
			$flt_corrected_by = $flt_corrected_by * -1;
			$flt_corrected = 0;
			$qry_batches->First();
			
			while ($flt_corrected < $flt_corrected_by) {
				$flt_stock_available = number_format($qry_batches->FieldByName('stock_available'),3,'.','');
				$current_batch_id = $qry_batches->FieldByName('batch_id');
				
				if (($flt_corrected_by - $flt_corrected) < $flt_stock_available)
					$qty_to_deduct = number_format(($flt_corrected_by - $flt_corrected),3,'.','');
				else
					$qty_to_deduct = number_format($flt_stock_available,3,'.','');
				
				// TABLE stock_storeroom_product
				$result_set->Query("
					UPDATE ".Monthalize('stock_storeroom_product')."
					SET stock_current = ABS(ROUND((stock_current - ".$qty_to_deduct."),3))
					WHERE (product_id=".$int_product_id.") AND
						(storeroom_id=".$_SESSION["int_current_storeroom"].")");
				if ($result_set->b_error == true) {
					$bool_success = false;
					$str_message = "Error updating stock_storeroom_product";
				}
	
				// TABLE stock_storeroom_batch
				$result_set->Query("
					UPDATE ".Monthalize('stock_storeroom_batch')."
					SET stock_available = ABS(ROUND((stock_available - ".$qty_to_deduct."),3))
					WHERE (batch_id = ".$current_batch_id.")
						AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
						AND (product_id = ".$int_product_id.")
				");
				if ($result_set->b_error == true) {
					$bool_success = false;
					$str_message = "Error updating stock_storeroom_batch";
				}
				
				// TABLE stock_balance
				$result_set->Query("
					UPDATE ".Yearalize('stock_balance')."
					SET stock_mismatch_deduction = stock_mismatch_deduction + ".$qty_to_deduct."
					WHERE (product_id = ".$int_product_id.") AND
						(storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
						(balance_month = ".$_SESSION["int_month_loaded"].") AND
						(balance_year = ".$_SESSION["int_year_loaded"].")");
				if ($result_set->b_error == true) {
					$bool_success = false;
					$str_message = "Error updating stock_balance.";
				}
				
				// STOCK_TRANSFER
				$result_set->Query("INSERT INTO ".Monthalize('stock_transfer')."
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
						$qty_to_deduct.", '".
						$str_description."', '".
						date('Y-m-d H:i:s')."', ".
						"3, ".
						$_SESSION["int_user_id"].", ".
						$_SESSION["int_current_storeroom"].", ".
						"0, ".
						$int_product_id.", ".
						$current_batch_id.", ".
						"0, ".
						TYPE_CORRECTED.", ".
						STATUS_COMPLETED.", ".
						$_SESSION["int_user_id"].", ".
						$_SESSION["int_user_id"].", ".
						"'N')");
				if ($result_set->b_error == true) {
					$str_message = "error inserting into ".Monthalize('stock_transfer');
					$bool_success = false;
				}
				$flt_corrected =  number_format($flt_corrected + $qty_to_deduct,3,'.','');
				$qry_batches->Next();
			} // END OF: while
			
			// set the closing balance
			$result_set->Query("
				UPDATE ".Yearalize('stock_balance')."
				SET stock_closing_balance = ".$flt_correct."
				WHERE (product_id = ".$int_product_id.") AND
					(storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
					(balance_month = ".$_SESSION["int_month_loaded"].") AND
					(balance_year = ".$_SESSION["int_year_loaded"].")");
			if ($result_set->b_error == true) {
				$bool_success = false;
				$str_message = "Error updating stock_balance.";
			}
			
			// set is_active to false where batches have zero stock
			$qry_batches->Query("
				UPDATE ".Monthalize('stock_storeroom_batch')."
				SET is_active = 'N'
				WHERE (stock_available <= 0) AND
					(storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
					(product_id = ".$int_product_id.")
			");
			
			// make sure there is at least one active batch
			$qry_batches->Query("
				SELECT *
				FROM ".Monthalize('stock_storeroom_batch')."
				WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].") 
					AND (product_id = ".$int_product_id.")
					AND (is_active = 'Y')
			");
			if ($qry_batches->RowCount() == 0) {
				// make the most recent batch active
				$qry_batches->Query("
					SELECT ssb.stock_storeroom_batch_id
					FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb
					WHERE (sb.product_id = ".$int_product_id.") AND
						(sb.storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
						(sb.is_active = 'Y') AND
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
					");
				}
			}
		} // END OF: ADDING / DEDUCTING STOCK 
		
		if ($bool_success == true)
			$qry->Query("COMMIT");
		else
			$qry->Query("ROLLBACK");

	} // end of if (can_save)

	return $bool_success;

} // end function stock_correct()



?>
