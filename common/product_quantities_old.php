<?
	error_reporting(E_ERROR);

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");


	function checkBatch($aProductCode, $aBatchCode, $aAvailableQuantity) {
		$int_Pos = -1;
		for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {
			if (($_SESSION['arr_total_qty'][$i][0] == $aProductCode) && ($_SESSION['arr_total_qty'][$i][1] == $aBatchCode)) {
				// billed already, but not the total quantity available in the batch
				if ($_SESSION["arr_total_qty"][$i][2] < $aAvailableQuantity) {
					$int_Pos = $i;
					break;
				}
				else {
				// the total available quantity of the batch has been billed already
					$int_Pos = -2;
					break;
				}
			}
		}
		return $int_Pos;
	}

	function findBatch($aProductCode, $aBatchCode) {
		$int_Pos = -1;
		for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {
			if (($_SESSION['arr_total_qty'][$i][0] == $aProductCode) && ($_SESSION['arr_total_qty'][$i][1] == $aBatchCode)) {
				$int_Pos = $i;
				break;
			}
		}
		return $int_Pos;
	}

//------------------------------------------------
//
//	This function loads the price and tax 
//	details for a given code.
//
//------------------------------------------------

	function setDetails($aProductCode, $aBatchCode, $atIndex) {
		$result_set = new Query("
			SELECT is_taxed, is_cash_taxed, is_account_taxed
			FROM stock_storeroom
			WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")"
		);
		$is_taxed = 'Y';
		$is_cash_taxed = 'Y';
		$is_account_taxed = 'Y';
		if ($result_set->RowCount() > 0) {
			$is_taxed = $result_set->FieldByName('is_taxed');
			$is_cash_taxed = $result_set->FieldByName('is_cash_taxed');
			$is_account_taxed = $result_set->FieldByName('is_account_taxed');
		}

		// check whether the item should use the batch price or the storeroom price
		// in case the storeroom price is to be used, then the tax should still be
		// taken from the batch
		$result_set->Query("
			SELECT sale_price, point_price, use_batch_price, discount_qty, discount_percent
			FROM ".Monthalize('stock_storeroom_product')."
			WHERE (product_id = ".$_SESSION['current_product_id'].") AND
				(storeroom_id = ".$_SESSION["int_current_storeroom"].")"
		);
		$sale_price = 0;
		$point_price = 0;
		$use_batch_price = 'Y';
		$discount_qty = 0;
		$discount_percent = 0;
		if ($result_set->RowCount() > 0) {
			$sale_price = $result_set->FieldByName('sale_price');
			$point_price = $result_set->FieldByName('point_price');
			$use_batch_price = $result_set->FieldByName('use_batch_price');
			$discount_qty = $result_set->FieldByName('discount_qty');
			$discount_percent = $result_set->FieldByName('discount_percent');
		}

		// get the batch price and tax_id
		$result_set->Query("
			SELECT sb.selling_price, sb.tax_id, sb.batch_id
			FROM ".Yearalize('stock_batch')." sb
			WHERE (sb.product_id = ".$_SESSION['current_product_id'].") AND
				(sb.storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
				(batch_code = '".$aBatchCode."') AND 
				(sb.is_active = 'Y') AND
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

		// get the tax description to display on screen and print
		$result_set->Query("
			SELECT tax_description
			FROM ".Monthalize('stock_tax')."
			WHERE (tax_id = ".$tax_id.")"
		);
		$tax_description = '';
		if ($result_set->RowCount() > 0) {
			if ($is_taxed == 'Y')
				$tax_description = $result_set->FieldByName('tax_description');
		}

		if ($use_batch_price == 'Y')
			$_SESSION["arr_total_qty"][$atIndex][6] = number_format(round($selling_price,3),3,'.','');
		else
			$_SESSION["arr_total_qty"][$atIndex][6] = round($sale_price,3);
		$_SESSION["arr_total_qty"][$atIndex][7] = $tax_id;
		$_SESSION["arr_total_qty"][$atIndex][8] = $tax_description;
		$_SESSION["arr_total_qty"][$atIndex][9] = $is_taxed;
	}


	function remove_product($str_code) {
		for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {
			if ($_SESSION["arr_total_qty"][$i][0] == $str_code) {
				$_SESSION["arr_total_qty"] = array_delete($_SESSION["arr_total_qty"], $i);
				remove_product();
			}
		}
	}
	
//------------------------------------------------
//
//	This function loads all the details of a 
//	bill based on the product code, batch code
//	and billed quantity
//
//------------------------------------------------

	function productQuantities($strProductCode, $strBatchCode, $fltBilledQty) {
		// default return string
		// this string gets returned to a javascript function in "billing_enter.php"
		$strDiscount = "nil";

		if ($strProductCode != 'nil') {

			// first check whether the given code + batch is in the list
			// this is a double check, as this gets verified in the onblur of 
			// the batch list box already. This double check is necessary, however,
			// as the user might skip the batch list.
                        remove_product($strProductCode);
			
/*
 			$int_batch_found = -1;
			for ($i=0; $i<count($_SESSION['arr_total_qty']); $i++) {
				if (($_SESSION['arr_total_qty'][$i][0] == $strProductCode) && 
					($_SESSION['arr_total_qty'][$i][1] == $strBatchCode)) {
					$int_batch_found = $i;
					break;
				}
			}
			// if found, remove before proceeding
			if ($int_batch_found > -1) {
				$_SESSION["arr_total_qty"] = array_delete($_SESSION["arr_total_qty"], $int_batch_found);
			}
*/
			// get the product's id and description, measurement unit and first default supplier
			$result_set = new Query("
				SELECT sp.product_id, sp.product_description, sup.supplier_abbreviation, smu.is_decimal
				FROM stock_product sp
				LEFT JOIN stock_supplier sup ON (sp.supplier_id = sup.supplier_id)
				LEFT JOIN stock_measurement_unit smu ON (sp.measurement_unit_id = smu.measurement_unit_id)
				WHERE (product_code = '".$strProductCode."')"
			);
			if ($result_set->RowCount() > 0) {
				$int_ProductID = $result_set->FieldByName('product_id');
				$str_ProductDescription = $result_set->FieldByName('product_description');
				$str_abbreviation = $result_set->FieldByName('supplier_abbreviation');
				$str_is_decimal = $result_set->FieldByName('is_decimal');
			}
			else
				$str_ProductDescription = 'Not Found';

			// get the quantity in the batch that has been selected, 
			// and the total quantity in all the batches
			$int_BatchPos = -1;
			$flt_BatchQty = 0;
			$flt_TotalQty = 0;
			for ($i=0; $i<count($_SESSION["arr_item_batches"]); $i++) {
				if ($_SESSION["arr_item_batches"][$i][0] == $strBatchCode) {
					$int_BatchPos = $i;
					$flt_BatchQty = $_SESSION["arr_item_batches"][$i][1];
				}
				$flt_TotalQty = $flt_TotalQty + $_SESSION["arr_item_batches"][$i][1];
			}

			// check whether the given product code has already been billed in other batch(es)
			// and get that total
			$flt_AlreadyBilled = 0;
			for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {
				if ($_SESSION["arr_total_qty"][$i][0] == $strProductCode) {
					$flt_AlreadyBilled = $flt_AlreadyBilled + $_SESSION["arr_total_qty"][$i][2] + $_SESSION["arr_total_qty"][$i][5];
				}
			}

			$intLength = count($_SESSION["arr_total_qty"]);

			// if the billed quantity is less than the quantity in the selected batch, create a single entry
			if ($fltBilledQty <= $flt_BatchQty) {
				$_SESSION["arr_total_qty"][$intLength][0] = $strProductCode;
				$_SESSION["arr_total_qty"][$intLength][1] = $strBatchCode;
				$_SESSION["arr_total_qty"][$intLength][2] = number_format($fltBilledQty, 3,'.','');
				$_SESSION["arr_total_qty"][$intLength][3] = 0;
				if (empty($_SESSION["arr_total_qty"][$intLength][4]))
					$_SESSION["arr_total_qty"][$intLength][4] = 0;
				$_SESSION["arr_total_qty"][$intLength][5] = 0;
				$_SESSION["arr_total_qty"][$intLength][12] = $str_ProductDescription;
				$_SESSION["arr_total_qty"][$intLength][13] = $int_ProductID;
				$_SESSION["arr_total_qty"][$intLength][14] = $str_abbreviation;
				$_SESSION["arr_total_qty"][$intLength][15] = $str_is_decimal;
				$_SESSION["arr_total_qty"][$intLength][20] = "1";

				setDetails($strProductCode, $strBatchCode, $intLength);
			}
			else {
				// save what is available in the currently selected batch
				$_SESSION["arr_total_qty"][$intLength][0] = $strProductCode;
				$_SESSION["arr_total_qty"][$intLength][1] = $strBatchCode;
				$_SESSION["arr_total_qty"][$intLength][2] = number_format($flt_BatchQty, 3,'.','');
				$_SESSION["arr_total_qty"][$intLength][3] = 0;
				if (empty($_SESSION["arr_total_qty"][$intLength][4]))
					$_SESSION["arr_total_qty"][$intLength][4] = 0;
				$_SESSION["arr_total_qty"][$intLength][5] = 0;
				$_SESSION["arr_total_qty"][$intLength][12] = $str_ProductDescription;
				$_SESSION["arr_total_qty"][$intLength][13] = $int_ProductID;
				$_SESSION["arr_total_qty"][$intLength][14] = $str_abbreviation;
				$_SESSION["arr_total_qty"][$intLength][15] = $str_is_decimal;
				$_SESSION["arr_total_qty"][$intLength][20] = "2";

				setDetails($strProductCode, $strBatchCode, $intLength);

				// if the billed amount is less than the total amount across the batches
				if (($fltBilledQty + $flt_AlreadyBilled) <= $flt_TotalQty) {

					// create as many entries as needed to meet the currently billed quantity
					$flt_TempQty = $flt_BatchQty;
					$flt_BilledSoFar = $flt_BatchQty;
					
					for ($i=0; $i<count($_SESSION["arr_item_batches"]); $i++) {

						$int_CheckBatch = checkBatch($strProductCode, $_SESSION["arr_item_batches"][$i][0], $_SESSION["arr_item_batches"][$i][1]);

						if ($int_CheckBatch == -2) {
							// do nothing - batch already billed for available quantity
						}
						else if ($int_CheckBatch == -1) {
							$intLength = count($_SESSION["arr_total_qty"]);

							// batch not billed yet
							$flt_TempQty = $flt_TempQty + $_SESSION["arr_item_batches"][$i][1];
							if ($flt_TempQty < $fltBilledQty) {
								$_SESSION["arr_total_qty"][$intLength][0] = $strProductCode;
								$_SESSION["arr_total_qty"][$intLength][1] = $_SESSION["arr_item_batches"][$i][0];
								$_SESSION["arr_total_qty"][$intLength][2] = number_format($_SESSION["arr_item_batches"][$i][1], 3,'.','');
								$_SESSION["arr_total_qty"][$intLength][3] = 0;
								if (empty($_SESSION["arr_total_qty"][$intLength][4]))
									$_SESSION["arr_total_qty"][$intLength][4] = 0;
								$_SESSION["arr_total_qty"][$intLength][5] = 0;
								$_SESSION["arr_total_qty"][$intLength][12] = $str_ProductDescription;
								$_SESSION["arr_total_qty"][$intLength][13] = $int_ProductID;
								$_SESSION["arr_total_qty"][$intLength][14] = $str_abbreviation;
								$_SESSION["arr_total_qty"][$intLength][15] = $str_is_decimal;
								$_SESSION["arr_total_qty"][$intLength][20] = "3";
								setDetails($strProductCode, $_SESSION["arr_item_batches"][$i][0], $intLength);

								$flt_BilledSoFar = $flt_BilledSoFar + $_SESSION["arr_item_batches"][$i][1];
							}
							else {
								$_SESSION["arr_total_qty"][$intLength][0] = $strProductCode;
								$_SESSION["arr_total_qty"][$intLength][1] = $_SESSION["arr_item_batches"][$i][0];
								$_SESSION["arr_total_qty"][$intLength][2] = number_format(($fltBilledQty - $flt_BilledSoFar), 3,'.','');
								$_SESSION["arr_total_qty"][$intLength][3] = 0;
								if (empty($_SESSION["arr_total_qty"][$intLength][4]))
									$_SESSION["arr_total_qty"][$intLength][4] = 0;
								$_SESSION["arr_total_qty"][$intLength][5] = 0;
								$_SESSION["arr_total_qty"][$intLength][12] = $str_ProductDescription;
								$_SESSION["arr_total_qty"][$intLength][13] = $int_ProductID;
								$_SESSION["arr_total_qty"][$intLength][14] = $str_abbreviation;
								$_SESSION["arr_total_qty"][$intLength][15] = $str_is_decimal;
								$_SESSION["arr_total_qty"][$intLength][20] = "4";
								setDetails($strProductCode, $_SESSION["arr_item_batches"][$i][0], $intLength);

								break;
							}
						}
						else {
							$flt_TempQty = $flt_TempQty + ($_SESSION["arr_item_batches"][$i][1] - $_SESSION["arr_total_qty"][$int_CheckBatch][2]);

							if ($flt_TempQty < $fltBilledQty) {
								$flt_BilledSoFar = $flt_BilledSoFar + ($_SESSION["arr_item_batches"][$i][1] - $_SESSION["arr_total_qty"][$int_CheckBatch][2]);

								$_SESSION["arr_total_qty"][$int_CheckBatch][0] = $strProductCode;
								$_SESSION["arr_total_qty"][$int_CheckBatch][1] = $_SESSION["arr_item_batches"][$i][0];
								$_SESSION["arr_total_qty"][$int_CheckBatch][2] = number_format($_SESSION["arr_item_batches"][$i][1], 3,'.','');
 								$_SESSION["arr_total_qty"][$int_CheckBatch][3] = 0;
								if (empty($_SESSION["arr_total_qty"][$intLength][4]))
									$_SESSION["arr_total_qty"][$int_CheckBatch][4] = 0;
								$_SESSION["arr_total_qty"][$int_CheckBatch][5] = 0;
								$_SESSION["arr_total_qty"][$int_CheckBatch][12] = $str_ProductDescription;
								$_SESSION["arr_total_qty"][$int_CheckBatch][13] = $int_ProductID;
								$_SESSION["arr_total_qty"][$int_CheckBatch][14] = $str_abbreviation;
								$_SESSION["arr_total_qty"][$int_CheckBatch][15] = $str_is_decimal;
								$_SESSION["arr_total_qty"][$int_CheckBatch][20] = "5";
								setDetails($strProductCode, $_SESSION["arr_item_batches"][$i][0], $int_CheckBatch);

							}
							else {
								$_SESSION["arr_total_qty"][$int_CheckBatch][0] = $strProductCode;
								$_SESSION["arr_total_qty"][$int_CheckBatch][1] = $_SESSION["arr_item_batches"][$i][0];
								$_SESSION["arr_total_qty"][$int_CheckBatch][2] = number_format(($_SESSION["arr_total_qty"][$int_CheckBatch][2] + ($fltBilledQty - $flt_BilledSoFar)), 3,'.','');
								$_SESSION["arr_total_qty"][$int_CheckBatch][3] = 0;
								if (empty($_SESSION["arr_total_qty"][$intLength][4]))
									$_SESSION["arr_total_qty"][$int_CheckBatch][4] = 0;
								$_SESSION["arr_total_qty"][$int_CheckBatch][5] = 0;
								$_SESSION["arr_total_qty"][$int_CheckBatch][12] = $str_ProductDescription;
								$_SESSION["arr_total_qty"][$int_CheckBatch][13] = $int_ProductID;
								$_SESSION["arr_total_qty"][$int_CheckBatch][14] = $str_abbreviation;
								$_SESSION["arr_total_qty"][$int_CheckBatch][15] = $str_is_decimal;
								$_SESSION["arr_total_qty"][$int_CheckBatch][20] = "6";
								setDetails($strProductCode, $_SESSION["arr_item_batches"][$i][0], $int_CheckBatch);
								
								break;
							}
						}
					}
				}
				else {
					// the billed amount is greater than the total available across batches
					// enter all the available batches in the session array, 
					// plus the extra quantity to be added to the last entry
					$flt_Remainder = $fltBilledQty - ($flt_TotalQty - $flt_AlreadyBilled);
					$flt_TempQty = $flt_BatchQty;
					$flt_BilledSoFar = $flt_BatchQty;
					// HERE we loop through the batches except the last
					for ($i=0; $i<count($_SESSION["arr_item_batches"])-1; $i++) {
						$int_CheckBatch = checkBatch($strProductCode, $_SESSION["arr_item_batches"][$i][0], $_SESSION["arr_item_batches"][$i][1]);
						
						if ($int_CheckBatch == -2) {
							// do nothing - batch already billed for available quantity
						}
						else if ($int_CheckBatch == -1) {
							// batch not billed yet
							$intLength = count($_SESSION["arr_total_qty"]);
							
							$flt_TempQty = $flt_TempQty + $_SESSION["arr_item_batches"][$i][1];
							
							if ($flt_TempQty < $fltBilledQty) {
								$_SESSION["arr_total_qty"][$intLength][0] = $strProductCode;
								$_SESSION["arr_total_qty"][$intLength][1] = $_SESSION["arr_item_batches"][$i][0];
								$_SESSION["arr_total_qty"][$intLength][2] = number_format($_SESSION["arr_item_batches"][$i][1], 3,'.','');
								$_SESSION["arr_total_qty"][$intLength][3] = 0;
								if (empty($_SESSION["arr_total_qty"][$intLength][4]))
									$_SESSION["arr_total_qty"][$intLength][4] = 0;
								$_SESSION["arr_total_qty"][$intLength][5] = 0;
								$_SESSION["arr_total_qty"][$intLength][12] = $str_ProductDescription;
								$_SESSION["arr_total_qty"][$intLength][13] = $int_ProductID;
								$_SESSION["arr_total_qty"][$intLength][14] = $str_abbreviation;
								$_SESSION["arr_total_qty"][$intLength][15] = $str_is_decimal;
								$_SESSION["arr_total_qty"][$intLength][20] = "7";
								setDetails($strProductCode, $_SESSION["arr_item_batches"][$i][0], $intLength);

								$flt_BilledSoFar = $flt_BilledSoFar + $_SESSION["arr_item_batches"][$i][1];
							}
							else {
								$_SESSION["arr_total_qty"][$intLength][0] = $strProductCode;
								$_SESSION["arr_total_qty"][$intLength][1] = $_SESSION["arr_item_batches"][$i][0];
								$_SESSION["arr_total_qty"][$intLength][2] = number_format(($fltBilledQty - $flt_BilledSoFar), 3,'.','');
								$_SESSION["arr_total_qty"][$intLength][3] = 0;
								if (empty($_SESSION["arr_total_qty"][$intLength][4]))
									$_SESSION["arr_total_qty"][$intLength][4] = 0;
								$_SESSION["arr_total_qty"][$intLength][5] = 0;
								$_SESSION["arr_total_qty"][$intLength][12] = $str_ProductDescription;
								$_SESSION["arr_total_qty"][$intLength][13] = $int_ProductID;
								$_SESSION["arr_total_qty"][$intLength][14] = $str_abbreviation;
								$_SESSION["arr_total_qty"][$intLength][15] = $str_is_decimal;
								$_SESSION["arr_total_qty"][$intLength][20] = "8";
								setDetails($strProductCode, $_SESSION["arr_item_batches"][$i][0], $intLength);

								break;
							}
						}
						else {
							$flt_TempQty = $flt_TempQty + ($_SESSION["arr_item_batches"][$i][1] - $_SESSION["arr_total_qty"][$int_CheckBatch][2]);

							if ($flt_TempQty < $fltBilledQty) {
								$_SESSION["arr_total_qty"][$int_CheckBatch][0] = $strProductCode;
								$_SESSION["arr_total_qty"][$int_CheckBatch][1] = $_SESSION["arr_item_batches"][$i][0];
								$_SESSION["arr_total_qty"][$int_CheckBatch][2] = number_format($_SESSION["arr_item_batches"][$i][1], 3,'.','');
								$_SESSION["arr_total_qty"][$int_CheckBatch][3] = 0;
								if (empty($_SESSION["arr_total_qty"][$intLength][4]))
									$_SESSION["arr_total_qty"][$int_CheckBatch][4] = 0;
								$_SESSION["arr_total_qty"][$int_CheckBatch][5] = 0;
								$_SESSION["arr_total_qty"][$int_CheckBatch][12] = $str_ProductDescription;
								$_SESSION["arr_total_qty"][$int_CheckBatch][13] = $int_ProductID;
								$_SESSION["arr_total_qty"][$int_CheckBatch][14] = $str_abbreviation;
								$_SESSION["arr_total_qty"][$int_CheckBatch][15] = $str_is_decimal;
								$_SESSION["arr_total_qty"][$int_CheckBatch][20] = "9";
								setDetails($strProductCode, $_SESSION["arr_item_batches"][$i][0], $int_CheckBatch);

								$flt_BilledSoFar = $flt_BilledSoFar + ($_SESSION["arr_item_batches"][$i][1] - $_SESSION["arr_total_qty"][$int_CheckBatch][2]);
							}
							else {
								$_SESSION["arr_total_qty"][$int_CheckBatch][0] = $strProductCode;
								$_SESSION["arr_total_qty"][$int_CheckBatch][1] = $_SESSION["arr_item_batches"][$i][0];
								$_SESSION["arr_total_qty"][$int_CheckBatch][2] = number_format(($fltBilledQty - $flt_BilledSoFar), 3,'.','');
								$_SESSION["arr_total_qty"][$int_CheckBatch][3] = 0;
								if (empty($_SESSION["arr_total_qty"][$intLength][4]))
									$_SESSION["arr_total_qty"][$int_CheckBatch][4] = 0;
								$_SESSION["arr_total_qty"][$int_CheckBatch][5] = 0;
								$_SESSION["arr_total_qty"][$int_CheckBatch][12] = $str_ProductDescription;
								$_SESSION["arr_total_qty"][$int_CheckBatch][13] = $int_ProductID;
								$_SESSION["arr_total_qty"][$int_CheckBatch][14] = $str_abbreviation;
								$_SESSION["arr_total_qty"][$int_CheckBatch][15] = $str_is_decimal;
								$_SESSION["arr_total_qty"][$int_CheckBatch][20] = "10";
								setDetails($strProductCode, $_SESSION["arr_item_batches"][$i][0], $int_CheckBatch);

								break;
							}
						}
					}

					// if the last batch is also taken, then update the extra quantities
					$int_CheckBatch = findBatch($strProductCode, $_SESSION["arr_item_batches"][$i][0]);
					if ($int_CheckBatch == -1) {
						$intLength = count($_SESSION["arr_total_qty"]);

						$_SESSION["arr_total_qty"][$intLength][0] = $strProductCode;
						$_SESSION["arr_total_qty"][$intLength][1] = $_SESSION["arr_item_batches"][$i][0];
						$_SESSION["arr_total_qty"][$intLength][2] = number_format($_SESSION["arr_item_batches"][$i][1], 3,'.','');
						$_SESSION["arr_total_qty"][$intLength][3] = 0;
						if (empty($_SESSION["arr_total_qty"][$intLength][4]))
							$_SESSION["arr_total_qty"][$intLength][4] = 0;
						$_SESSION["arr_total_qty"][$intLength][5] = number_format($flt_Remainder, 3,'.','');
						$_SESSION["arr_total_qty"][$intLength][12] = $str_ProductDescription;
						$_SESSION["arr_total_qty"][$intLength][13] = $int_ProductID;
						$_SESSION["arr_total_qty"][$intLength][14] = $str_abbreviation;
						$_SESSION["arr_total_qty"][$intLength][15] = $str_is_decimal;
						$_SESSION["arr_total_qty"][$intLength][20] = "11";
						setDetails($strProductCode, $_SESSION["arr_item_batches"][$i][0], $intLength);
					} 
					else {
						$_SESSION["arr_total_qty"][$int_CheckBatch][0] = $strProductCode;
						$_SESSION["arr_total_qty"][$int_CheckBatch][1] = $_SESSION["arr_item_batches"][$i][0];
						$_SESSION["arr_total_qty"][$int_CheckBatch][2] = number_format($_SESSION["arr_item_batches"][$i][1], 3,'.','');
						$_SESSION["arr_total_qty"][$int_CheckBatch][3] = 0;
						if (empty($_SESSION["arr_total_qty"][$intLength][4]))
							$_SESSION["arr_total_qty"][$int_CheckBatch][4] = 0;
						$_SESSION["arr_total_qty"][$int_CheckBatch][5] = number_format($flt_Remainder, 3,'.','');
						$_SESSION["arr_total_qty"][$int_CheckBatch][12] = $str_ProductDescription;
						$_SESSION["arr_total_qty"][$int_CheckBatch][13] = $int_ProductID;
						$_SESSION["arr_total_qty"][$int_CheckBatch][14] = $str_abbreviation;
						$_SESSION["arr_total_qty"][$int_CheckBatch][15] = $str_is_decimal;
						$_SESSION["arr_total_qty"][$int_CheckBatch][20] = "12";
						setDetails($strProductCode, $_SESSION["arr_item_batches"][$i][0], $int_CheckBatch);
					}
				}
			}

			// check whether a discount should be given based on the discount_qty
			// if so, return the discount_percent
/*
			$result_set = new Query("
				SELECT discount_qty, discount_percent
				FROM ".Monthalize('stock_storeroom_product')."
				WHERE (product_id = ".$_SESSION['current_product_id'].") AND
					(storeroom_id = ".$_SESSION["int_current_storeroom"].")"
			);
			$strDiscount = '0';
			if ($result_set->RowCount() > 0) {
				if (($flt_AlreadyBilled + $fltBilledQty) >= $result_set->FieldByName('discount_qty')) {
					for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {
						if ($_SESSION["arr_total_qty"][$i][0] == $strProductCode) {
							$_SESSION["arr_total_qty"][$i][3] = round($result_set->FieldByName('discount_qty'));
							$_SESSION["arr_total_qty"][$i][4] = round($result_set->FieldByName('discount_percent'));
							$strDiscount = round($result_set->FieldByName('discount_percent'));
						}
					}
				}
				else {
					for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {
						if ($_SESSION["arr_total_qty"][$i][0] == $strProductCode) {
							$_SESSION["arr_total_qty"][$i][3] = 0;
							$_SESSION["arr_total_qty"][$i][4] = 0;
							$strDiscount = 0;
						}
					}
				}
			}

			*/ $strDiscount = 0;

		} // if ($strProductCode != 'nil')
		return $strDiscount;
	} // end of function



//------------------------------------------------
//
//	This function loads all the details of a 
//	bill given the bill id
//
//------------------------------------------------

	function load_bill_details($int_bill_id) {
        
		$str_retval = 'OK|';
		
		//-----
		// check if the given bill is present
		//-----
		$qry_result = new Query("
			SELECT *
			FROM ".Monthalize('bill')."
			WHERE (bill_id = $int_bill_id)
		");
		if ($qry_result->b_error == true) {
			$str_retval = "false|Error querying bill_id";
			$_SESSION['bill_error'] = "Error querying bill_id";
			return $str_retval;
		}
		if ($qry_result->RowCount() == 0) {
			$str_retval = "false|Invalid bill_id";
			$_SESSION['bill_error'] = "Invalid bill_id";
			return $str_retval;
		}
		if ($qry_result->FieldByName('bill_status') == BILL_STATUS_CANCELLED) {
			$str_retval = "false|Cannot edit a cancelled bill";
			$_SESSION['bill_error'] = "Cannot edit a cancelled bill";
			return $str_retval;
		}

		//-----
		// if an account bill, get the details
		//-----
                $str_account_number = '';
		$str_account_name = '';
		if ($qry_result->FieldByName('payment_type') == BILL_PT_ACCOUNT) {
			$qry_account = new Query("
				SELECT *
				FROM account_pt
				WHERE account_id = ".$qry_result->FieldByName('CC_id')
			);
			if ($qry_account->RowCount() == 0) {
				$str_retval = 'false|PT Account not found';
				return $str_retval;
			}
			$str_account_number = $qry_account->FieldByName('account_number');
			$str_account_name = $qry_account->FieldByName('account_name');
		}
		else if ($qry_result->FieldByName('payment_type') == BILL_ACCOUNT) {
			$qry_account = new Query("
				SELECT *
				FROM account_cc
				WHERE cc_id = ".$qry_result->FieldByName('CC_id')
			);
			if ($qry_account->RowCount() == 0) {
				$str_retval = 'false|FS Account not found';
				return $str_retval;
			}
			$str_account_number = $qry_account->FieldByName('account_number');
			$str_account_name = $qry_account->FieldByName('account_name');
		}
		
		$_SESSION['bill_id'] = $int_bill_id;
		$_SESSION['bill_number'] = $qry_result->FieldByName('bill_number');
		$_SESSION['current_bill_type'] = $qry_result->FieldByName('payment_type');
		$_SESSION['current_bill_day'] = date('j'); //, mySQLTimeToLinuxTime($qry_result->FieldByName('date_created')));
		$_SESSION['current_account_number'] = $str_account_number;
		$_SESSION['current_account_name'] = $str_account_name;
		$_SESSION['bill_total'] = $qry_result->FieldByName('total_amount');
		$_SESSION['sales_promotion'] = $qry_result->FieldByName('bill_promotion');
		
		
		//-----
		// get the items of the bill and load in session arrays
		//-----
		unset($_SESSION["arr_total_qty"]);
		unset($_SESSION["arr_item_batches"]);
		
		$qry_result->Query("
			SELECT *
			FROM ".Monthalize('bill_items')."
			WHERE bill_id = $int_bill_id
		");
		if ($qry_result->b_error == true) {
			$str_retval = 'false|Error querying items';
			return $str_retval;
		}
		
		// dummy initialization
		$qry_product = new Query("SELECT * FROM stock_product LIMIT 1");
		$qry_batch = new Query("SELECT * FROM ".Yearalize('stock_batch')." LIMIT 1");
		
		for ($i = 0; $i < $qry_result->RowCount(); $i++) {
		    
			$qry_product->Query("
				SElECT sp.*, sup.*, smu.is_decimal
				FROM stock_product sp
				LEFT JOIN stock_supplier sup ON (sp.supplier_id = sup.supplier_id)
				LEFT JOIN stock_measurement_unit smu ON (sp.measurement_unit_id = smu.measurement_unit_id)
				WHERE product_id = ".$qry_result->FieldByName('product_id')
			);
			$qry_batch->Query("
				SELECT *
				FROM ".Yearalize('stock_batch')."
				WHERE batch_id = ".$qry_result->FieldByName('batch_id')
			);
			
			$_SESSION["arr_total_qty"][$i][0] = $qry_product->FieldByName('product_code');
			$_SESSION["arr_total_qty"][$i][1] = $qry_batch->FieldByName('batch_code');
			$_SESSION["arr_total_qty"][$i][2] = number_format($qry_result->FieldByName('quantity'), 3,'.','');
			// the two following fields are not set
			// as this functionality has not been coded yet
			$_SESSION["arr_total_qty"][$i][3] = 0; // discount qty
			$_SESSION["arr_total_qty"][$i][4] = $qry_result->FieldByName('discount'); // discount percent
			$_SESSION["arr_total_qty"][$i][5] = number_format($qry_result->FieldByName('adjusted_quantity'), 3,'.','');;
			// the following two fields are calculated in
                        // billing_list.php
			$_SESSION["arr_total_qty"][$i][10] = 0; // total of item * price (incl. discount and tax)
			$_SESSION["arr_total_qty"][$i][11] = $qry_result->FieldByName('tax_amount');
			$_SESSION["arr_total_qty"][$i][12] = $qry_product->FieldByName('product_description');
			$_SESSION["arr_total_qty"][$i][13] = $qry_result->FieldByName('product_id');
			$_SESSION["arr_total_qty"][$i][14] = $qry_product->FieldByName('supplier_abbreviation');
			$_SESSION["arr_total_qty"][$i][15] = $qry_product->FieldByName('is_decimal');
			
			$_SESSION["current_product_id"] = $qry_result->FieldByName('product_id');
			
			setDetails($qry_product->FieldByName('product_code'),
				$qry_batch->FieldByName('batch_code'),
				$i
			);
			
			$qry_result->Next();
		}
		
		return $str_retval;
	}


//------------------------------------------------
//
//	in case the 'live' variable is set
//	call the 'productQuantities' function
//
//------------------------------------------------

	if (!empty($_GET['live'])) {
		if (!empty($_GET['product_code'])) {
			echo productQuantities($_GET['product_code'], $_GET["batch_code"], $_GET['qty']);
			die();
		}
		else {
			die("nil");
		}
	}
	
?>