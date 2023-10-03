<?
	error_reporting(E_ERROR);

	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");


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

	function productBatches($strProductCode, $is_bar_code='N') {
		// default return string
		// this string gets returned to a javascript function in "billing_enter.php"
		$strBatches = "nil";
		
		if ($strProductCode != 'nil') {
			$int_product_id = 0;
		
			// locate the product for the given code and save the id
			if ($is_bar_code == 'Y')
				$result_set = new Query("
					SELECT product_id
					FROM stock_product
					WHERE (product_bar_code = '".$strProductCode."')"
				);
			else
				$result_set = new Query("
					SELECT product_id
					FROM stock_product
					WHERE (product_code = '".$strProductCode."')"
				);
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
				
				//=======================================================================
				// make sure is_active is false when positive stock batches are available
				//-----------------------------------------------------------------------
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
			$result_set->Query("
				SELECT sb.batch_id, sb.batch_code, sb.buying_price, sb.selling_price, sb.tax_id, ssb.stock_available, ssb.is_active
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
			
			if ($result_set->RowCount() > 0) {
				// save the batch code and quantity in the session array
				// in the case of inactive batches, only include those that
				// were part of the bill
				unset($_SESSION["arr_item_batches"]);
				
				$int_counter = 0;
				for ($i=0; $i<$result_set->RowCount(); $i++) {
					
					$_SESSION["arr_item_batches"][$int_counter][0] = $result_set->FieldByName('batch_code');
					$_SESSION["arr_item_batches"][$int_counter][1] = number_format($result_set->FieldByName('stock_available'),3,'.','');
					$_SESSION["arr_item_batches"][$int_counter][2] = $result_set->FieldByName('batch_id');
					$int_counter++;
					
					$result_set->Next();
				}

				// this string is for javascript, in order to populate the list of batches
				$result_set->First();
				$strBatches = "";
				for ($i=0; $i<$result_set->RowCount(); $i++) {
					
					if ($i == $result_set->RowCount()-1)
						$strBatches .= $result_set->FieldByName('batch_code')."&".number_format($result_set->FieldByName('stock_available'),3,'.','')."&".number_format($result_set->FieldByName('selling_price'),2,'.',',');
					else
						$strBatches .= $result_set->FieldByName('batch_code')."&".number_format($result_set->FieldByName('stock_available'),3,'.','')."&".number_format($result_set->FieldByName('selling_price'),2,'.',',')."|";
					
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