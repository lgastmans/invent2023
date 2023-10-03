<?
error_reporting(E_ERROR);

if (file_exists("const.inc.php")) {
	require_once("const.inc.php");
}
else if (file_exists("include/const.inc.php")) {
	require_once("include/const.inc.php");
}
else if (file_exists("../include/const.inc.php")) {
	require_once("../include/const.inc.php");
}
else if (file_exists("../../include/const.inc.php")) {
	require_once("../../include/const.inc.php");
}
else if (file_exists("../../../include/const.inc.php")) {
	require_once("../../../include/const.inc.php");
}

require_once($str_application_path."include/session.inc.php");
require_once($str_application_path."include/db.inc.php");
require_once($str_application_path."common/product_funcs.inc.php");


	function findBatch($aProductCode, $aBatchCode) {
		$int_Pos = -1;
		for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {
			if (($_SESSION['arr_total_qty'][$i][0] === $aProductCode) && ($_SESSION['arr_total_qty'][$i][1] == $aBatchCode)) {
				$int_Pos = $i;
				break;
			}
		}
		return $int_Pos;
	}


	function productBatches($strProductCode, $is_bar_code='N') {
		// default return string
		// this string gets returned to a javascript function in "billing_enter.php"
		$strBatches = "nil";

		if ($strProductCode != 'nil') {
			$int_product_id = 0;
		
			// locate the product for the given code and save the id
			/*
			if ($is_bar_code == 'Y') {
				$result_set = new Query("
					SELECT product_id
					FROM stock_product
					WHERE (product_bar_code = '".$strProductCode."')
						AND (deleted = 'N')
					");
			} else {
				$result_set = new Query("
					SELECT product_id
					FROM stock_product
					WHERE (product_code = '".$strProductCode."')
						AND (deleted = 'N')
					");
			}
			*/
			// NEW 
			$result_set = new Query("
				SELECT product_id
				FROM stock_product
				WHERE ((product_code = '".$strProductCode."') OR (product_bar_code = '".$strProductCode."'))
					AND (deleted = 'N')
				");
			$int_product_id = $result_set->FieldByName('product_id');

			if ($int_product_id > 0) {
				//=============================================
				// make sure there is at least one active batch
                //---------------------------------------------
				$result_set->Query("
					SELECT *
					FROM ".Monthalize('stock_storeroom_batch')."
					WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].") 
						AND (product_id = ".$int_product_id.")
						AND (is_active = 'Y')
				");
				$strBatches = $result_set->RowCount();
				if ($result_set->RowCount() == 0) {
					// make the most recent batch active
					$result_set->Query("
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
					if ($result_set->RowCount() > 0) {
						$result_set->First();
						$int_ssb_batch_id = $result_set->FieldByName('stock_storeroom_batch_id');
						$result_set->Query("
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
 				$arr_result = check_active_batches($int_product_id);
				
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
			}
			
			// get the batches and quantities available for the given product
            // if the bill is being editted, then return also inactive batches
			if ($_SESSION['bill_id'] > -1) {
				$result_set->Query("
					SELECT sb.batch_id, sb.batch_code, sb.buying_price, sb.selling_price, sb.tax_id,
						ssb.stock_available, ssb.bill_reserved, ssb.is_active
					FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb
					WHERE (sb.product_id = ".$int_product_id.") AND
						(sb.storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
						(sb.status = ".STATUS_COMPLETED.") AND
						(sb.deleted = 'N') AND
						(ssb.product_id = sb.product_id) AND
						(ssb.batch_id = sb.batch_id) AND
						(ssb.storeroom_id = sb.storeroom_id)
					ORDER BY date_created
				");
			}
			else {
				$result_set->Query("
					SELECT sb.batch_id, sb.batch_code, sb.buying_price, sb.selling_price, sb.tax_id,
						ssb.stock_available, ssb.bill_reserved, ssb.is_active
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
			}
			
			if ($result_set->RowCount() > 0) {
				// save the batch code and quantity in the session array
				// in the case of inactive batches, only include those that
				// were part of the bill
				unset($_SESSION["arr_item_batches"]);
				
				$int_counter = 0;
				for ($i=0; $i<$result_set->RowCount(); $i++) {
					
//					$flt_actual_stock = $result_set->FieldByName('stock_available') - $result_set->FieldByName('bill_reserved');
					$flt_actual_stock = $result_set->FieldByName('stock_available');
					$flt_actual_stock = number_format($flt_actual_stock, 3, '.', '');
					
					if ($_SESSION['bill_id'] > -1) {
						if ($result_set->FieldByName('is_active') == 'N') {
							$int_pos = findBatch($strProductCode, $result_set->FieldByName('batch_code'));
							if ($int_pos > -1) {
								$flt_total_quantity = number_format(($_SESSION['arr_total_qty'][$int_pos][2]),3,'.',''); 
								$_SESSION["arr_item_batches"][$int_counter][0] = $result_set->FieldByName('batch_code');
								$_SESSION["arr_item_batches"][$int_counter][1] = $flt_total_quantity;
								$_SESSION["arr_item_batches"][$int_counter][2] = $result_set->FieldByName('batch_id');
								$int_counter++;
							}
							
						}
						else {
							if ($flt_actual_stock > 0) {
								$int_pos = findBatch($strProductCode, $result_set->FieldByName('batch_code'));
								if ($int_pos > -1)
									$flt_total_quantity = number_format(($_SESSION['arr_total_qty'][$int_pos][2]),3,'.','') + $flt_actual_stock; // number_format($result_set->FieldByName('stock_available'),3,'.','');
								else
									$flt_total_quantity = $flt_actual_stock; // number_format($result_set->FieldByName('stock_available'),3,'.','');
								
								$_SESSION["arr_item_batches"][$int_counter][0] = $result_set->FieldByName('batch_code');
								$_SESSION["arr_item_batches"][$int_counter][1] = $flt_total_quantity;
								$_SESSION["arr_item_batches"][$int_counter][2] = $result_set->FieldByName('batch_id');
								$int_counter++;
							}
						}
					}
					else {
/*
						if ($result_set->FieldByName('bill_reserved') > 0) {
							if ($flt_actual_stock > 0) {
								$_SESSION["arr_item_batches"][$int_counter][0] = $result_set->FieldByName('batch_code');
								$_SESSION["arr_item_batches"][$int_counter][1] = $flt_actual_stock; //number_format($result_set->FieldByName('stock_available'),3,'.','');
								$_SESSION["arr_item_batches"][$int_counter][2] = $result_set->FieldByName('batch_id');
								$int_counter++;
							}
						}
						else {
*/
							$_SESSION["arr_item_batches"][$int_counter][0] = $result_set->FieldByName('batch_code');
							$_SESSION["arr_item_batches"][$int_counter][1] = $flt_actual_stock; //number_format($result_set->FieldByName('stock_available'),3,'.',''); //
							$_SESSION["arr_item_batches"][$int_counter][2] = $result_set->FieldByName('batch_id');
							$int_counter++;
//						}
					}
					
					$result_set->Next();
				}
				
				// this string is for javascript, in order to populate the list of batches
				$result_set->First();
				$strBatches = "";
				for ($i=0; $i<$result_set->RowCount(); $i++) {
					if ($_SESSION['bill_id'] > -1) {
						if ($result_set->FieldByName('is_active') == 'N') {
							$int_pos = findBatch($strProductCode, $result_set->FieldByName('batch_code'));
							if ($int_pos > -1) {
								$flt_total_quantity = number_format(($_SESSION['arr_total_qty'][$int_pos][2]),3,'.',''); 
								if ($i == $result_set->RowCount()-1)
									$strBatches .=
										$result_set->FieldByName('batch_code').
										"&".$flt_total_quantity.
										"&".number_format(getSellingPrice($int_product_id, $result_set->FieldByName('batch_id')),2,'.',',').
										"&".$result_set->FieldByName('batch_id');
								else
									$strBatches .=
										$result_set->FieldByName('batch_code').
										"&".$flt_total_quantity.
										"&".number_format(getSellingPrice($int_product_id, $result_set->FieldByName('batch_id')),2,'.',',').
										"&".$result_set->FieldByName('batch_id').
										"|";
							}
						}
						else {
//							if ($result_set->FieldByName('bill_reserved') > 0) {
								if ($flt_actual_stock > 0) {
									$int_pos = findBatch($strProductCode, $result_set->FieldByName('batch_code'));
									if ($int_pos > -1)
										$flt_total_quantity = number_format(($_SESSION['arr_total_qty'][$int_pos][2]),3,'.','') + number_format($result_set->FieldByName('stock_available'),3,'.','');
									else
										$flt_total_quantity = number_format($result_set->FieldByName('stock_available'),3,'.','');
									
									if ($i == $result_set->RowCount()-1)
										$strBatches .=
											$result_set->FieldByName('batch_code').
											"&".$flt_total_quantity.
											"&".number_format(getSellingPrice($int_product_id, $result_set->FieldByName('batch_id')),2,'.',',').
											"&".$result_set->FieldByName('batch_id');
									else
										$strBatches .=
											$result_set->FieldByName('batch_code').
											"&".$flt_total_quantity.
											"&".number_format(getSellingPrice($int_product_id, $result_set->FieldByName('batch_id')),2,'.',',').
											"&".$result_set->FieldByName('batch_id').
											"|";
								}
//							}
							else {
								$int_pos = findBatch($strProductCode, $result_set->FieldByName('batch_code'));
								if ($int_pos > -1)
									$flt_total_quantity = number_format(($_SESSION['arr_total_qty'][$int_pos][2]),3,'.','') + number_format($result_set->FieldByName('stock_available'),3,'.','');
								else
									$flt_total_quantity = number_format($result_set->FieldByName('stock_available'),3,'.','');
								
								if ($i == $result_set->RowCount()-1)
									$strBatches .=
										$result_set->FieldByName('batch_code').
										"&".$flt_total_quantity.
										"&".number_format(getSellingPrice($int_product_id, $result_set->FieldByName('batch_id')),2,'.',',').
										"&".$result_set->FieldByName('batch_id');
								else
									$strBatches .=
										$result_set->FieldByName('batch_code').
										"&".$flt_total_quantity.
										"&".number_format(getSellingPrice($int_product_id, $result_set->FieldByName('batch_id')),2,'.',',').
										"&".$result_set->FieldByName('batch_id').
										"|";
							}
						}
					}
					else {
/*						if ($result_set->FieldByName('bill_reserved') > 0) {
							if ($flt_actual_stock > 0) {
								if ($i == $result_set->RowCount()-1)
									$strBatches .= $result_set->FieldByName('batch_code')."&".number_format($result_set->FieldByName('stock_available'),3,'.','')."&".number_format(getSellingPrice($int_product_id),2,'.',',');
								else
									$strBatches .= $result_set->FieldByName('batch_code')."&".number_format($result_set->FieldByName('stock_available'),3,'.','')."&".number_format(getSellingPrice($int_product_id),2,'.',',')."|";
							}
						}
						else {
*/
							if ($i == $result_set->RowCount()-1)
								$strBatches .=
									$result_set->FieldByName('batch_code').
									"&".number_format($result_set->FieldByName('stock_available'),3,'.','').
									"&".number_format(getSellingPrice($int_product_id, $result_set->FieldByName('batch_id')),2,'.',',').
									"&".$result_set->FieldByName('batch_id');
							else
								$strBatches .=
									$result_set->FieldByName('batch_code').
									"&".number_format($result_set->FieldByName('stock_available'),3,'.','').
									"&".number_format(getSellingPrice($int_product_id, $result_set->FieldByName('batch_id')),2,'.',',').
									"&".$result_set->FieldByName('batch_id').
									"|";
						}
//					}
					
					$result_set->Next();
				}
			}
			else {
				unset($_SESSION["arr_item_batches"]);
			}
		}

		// return
		return $strBatches;
	}

	if (!empty($_GET['live'])) {
		if (!empty($_GET['product_code'])) {
			$str_is_bar_code = 'N';
			if (IsSet($_GET['is_bar_code']))
				$str_is_bar_code = $_GET['is_bar_code'];

			echo productBatches($_GET['product_code'], $str_is_bar_code);
			die();
		}
		else {
			die("nil");
		}
	}
?>
