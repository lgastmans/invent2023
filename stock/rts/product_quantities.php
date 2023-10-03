<?
	error_reporting(E_ERROR);

	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");


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


		/*
			check whether the item should use the batch price or the storeroom price
				in case the storeroom price is to be used, then 
			!	the tax should still be	taken from the batch
		*/
		$result_set->Query("
			SELECT buying_price, sale_price, point_price, use_batch_price, discount_qty, discount_percent
			FROM ".Monthalize('stock_storeroom_product')."
			WHERE (product_id = ".$_SESSION['current_product_id'].") AND
				(storeroom_id = ".$_SESSION["int_current_storeroom"].")"
		);

		$buying_price = 0;
		$sale_price = 0;
		$point_price = 0;
		$use_batch_price = 'Y';
		$discount_qty = 0;
		$discount_percent = 0;

		if ($result_set->RowCount() > 0) {
			$buying_price = $result_set->FieldByName('buying_price');
			$sale_price = $result_set->FieldByName('sale_price');
			$point_price = $result_set->FieldByName('point_price');
			$use_batch_price = $result_set->FieldByName('use_batch_price');
			$discount_qty = $result_set->FieldByName('discount_qty');
			$discount_percent = $result_set->FieldByName('discount_percent');
		}


		/*
			get the batch price and tax_id
		*/
		$sql2 = "
			SELECT sb.buying_price, sb.selling_price, sb.tax_id, sb.batch_id, po.invoice_number, po.invoice_date, po.date_received
			FROM ".Yearalize('stock_batch')." sb
			LEFT JOIN ".Monthalize('stock_transfer')." str ON (str.batch_id = sb.batch_id) AND (transfer_type = 5)
			LEFT JOIN ".Yearalize('purchase_items')." pi ON (pi.batch_id = sb.batch_id)
			LEFT JOIN ".Yearalize('purchase_order')." po ON (po.purchase_order_id = pi.purchase_order_id)
			WHERE (sb.product_id = ".$_SESSION['current_product_id'].") AND
				(sb.storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
				(batch_code = '".$aBatchCode."') AND 
				(sb.is_active = 'Y') AND
				(sb.status = ".STATUS_COMPLETED.") AND
				(sb.deleted = 'N')
			ORDER BY sb.date_created";
		
		$result_set->Query($sql2);

		$batch_buying_price = 0;
		$selling_price = 0;
		$tax_id = 0;
		$invno = '';
		$invdt = '';
		$batch_id = 0;

		if ($result_set->RowCount() > 0) {
			$batch_buying_price = $result_set->FieldByName('buying_price');
			$selling_price = $result_set->FieldByName('selling_price');
			$tax_id = $result_set->FieldByName('tax_id');
			$batch_id = $result_set->FieldByName('batch_id');
			$invno = $result_set->FieldByName('invoice_number');
			$invdt = $result_set->FieldByName('invoice_date');
			$batch_id = $result_set->FieldByName('batch_id');
		}


		/*
			get the category of the product
			check whether to apply the tax rule
		*/
		$sql = "
			SELECT sc.apply_tax_rule
			FROM `stock_product` sp 
			INNER JOIN stock_category sc ON (sc.category_id = sp.category_id)
			WHERE sp.product_id = ".$_SESSION['current_product_id'];

		$result_set->Query($sql);

		$apply_tax_rule = false;

		if ($result_set->RowCount()>0) {

			if ($result_set->FieldByName('apply_tax_rule')) {

				/*
					compare the buying and selling price
				*/
				if ($use_batch_price == 'Y') {
					
					if (($batch_buying_price < 1000) && ($selling_price >= 1000))

						$apply_tax_rule = true;

				}
				else {

					if (($buying_price < 1000) && ($sale_price >= 1000))

						$apply_tax_rule = true;

				}
			}
		}


		/*
			set the corresponding tax id according to tax rule
		*/
		if ($apply_tax_rule) {

			$sql = "
				SELECT stl.tax_id
			 	FROM ".Monthalize('stock_tax_links')." stl
			 	INNER JOIN ".Monthalize('stock_tax_definition')." std ON (std.rule_lower_limit=true)
			 	WHERE stl.tax_definition_id = std.definition_id";

			$result_set->Query($sql);

			if ($result_set->RowCount() > 0)
				$tax_id = $result_set->FieldByName('tax_id');

		}


		/*
			get the tax description to display on screen and print
		*/
		$sql = "
			SELECT *
		 	FROM ".Monthalize('stock_tax_links')." stl
		 	INNER JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = stl.tax_id) AND (st.tax_id = ".$tax_id.")
		 	INNER JOIN ".Monthalize('stock_tax_definition')." std ON (std.definition_id = stl.tax_definition_id)
		 	WHERE std.definition_type <> 2";

		$result_set->Query($sql);

		$tax_description = '';
		if ($result_set->RowCount() > 0)
			$tax_description = $result_set->FieldByName('tax_description');



		if ($use_batch_price == 'Y') {
			$_SESSION["arr_total_qty"][$atIndex]['buying_price'] = round($batch_buying_price,2);
			$_SESSION["arr_total_qty"][$atIndex][6] = round($selling_price,2);
		}
		else {
			$_SESSION["arr_total_qty"][$atIndex]['buying_price'] = round($buying_price,2);
			$_SESSION["arr_total_qty"][$atIndex][6] = round($sale_price,2);
		}

		$_SESSION["arr_total_qty"][$atIndex][7] = $tax_id;
		$_SESSION["arr_total_qty"][$atIndex][8] = $tax_description;
		$_SESSION["arr_total_qty"][$atIndex][9] = $is_taxed;
		$_SESSION["arr_total_qty"][$atIndex]['invno'] = $invno;
		$_SESSION["arr_total_qty"][$atIndex]['invdt'] = $invdt;
		$_SESSION["arr_total_qty"][$atIndex]['batch_id'] = $batch_id;
	}

	function productQuantities($strProductCode, $strBatchCode, $fltBilledQty) {
		// default return string
		// this string gets returned to a javascript function in "billing_enter.php"
		$strDiscount = "nil";

		if ($strProductCode != 'nil') {

			// first check whether the given code + batch is in the list
			// this is a double check, as this gets verified in the onblur of 
			// the batch list box already. This double check is necessary, however,
			// as the user might skip the batch list.
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


			// get the product's id and description
			$result_set = new Query("
				SELECT product_id, product_description
				FROM stock_product
				WHERE (product_code = '".$strProductCode."')
					AND (deleted = 'N')
			");
			if ($result_set->RowCount() > 0) {
				$int_ProductID = $result_set->FieldByName('product_id');
				$str_ProductDescription = $result_set->FieldByName('product_description');
			}
			else
				$str_ProductDescription = 'Not Found';

			// get the quantity currently in the batch that has been selected, 
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
				$_SESSION["arr_total_qty"][$intLength][4] = 0;
				$_SESSION["arr_total_qty"][$intLength][5] = 0;
				$_SESSION["arr_total_qty"][$intLength][12] = $str_ProductDescription;
				$_SESSION["arr_total_qty"][$intLength][13] = $int_ProductID;

				setDetails($strProductCode, $strBatchCode, $intLength);
			}
			else {
				// save what is available in the currently selected batch
				$_SESSION["arr_total_qty"][$intLength][0] = $strProductCode;
				$_SESSION["arr_total_qty"][$intLength][1] = $strBatchCode;
				$_SESSION["arr_total_qty"][$intLength][2] = number_format($flt_BatchQty, 3,'.','');
				$_SESSION["arr_total_qty"][$intLength][3] = 0;
				$_SESSION["arr_total_qty"][$intLength][4] = 0;
				$_SESSION["arr_total_qty"][$intLength][5] = 0;
				$_SESSION["arr_total_qty"][$intLength][12] = $str_ProductDescription;
				$_SESSION["arr_total_qty"][$intLength][13] = $int_ProductID;
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
								$_SESSION["arr_total_qty"][$intLength][4] = 0;
								$_SESSION["arr_total_qty"][$intLength][5] = 0;
								$_SESSION["arr_total_qty"][$intLength][12] = $str_ProductDescription;
								$_SESSION["arr_total_qty"][$intLength][13] = $int_ProductID;
								setDetails($strProductCode, $_SESSION["arr_item_batches"][$i][0], $intLength);

								$flt_BilledSoFar = $flt_BilledSoFar + $_SESSION["arr_item_batches"][$i][1];
							}
							else {
								$_SESSION["arr_total_qty"][$intLength][0] = $strProductCode;
								$_SESSION["arr_total_qty"][$intLength][1] = $_SESSION["arr_item_batches"][$i][0];
								$_SESSION["arr_total_qty"][$intLength][2] = number_format(($fltBilledQty - $flt_BilledSoFar), 3,'.','');
								$_SESSION["arr_total_qty"][$intLength][3] = 0;
								$_SESSION["arr_total_qty"][$intLength][4] = 0;
								$_SESSION["arr_total_qty"][$intLength][5] = 0;
								$_SESSION["arr_total_qty"][$intLength][12] = $str_ProductDescription;
								$_SESSION["arr_total_qty"][$intLength][13] = $int_ProductID;
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
								$_SESSION["arr_total_qty"][$int_CheckBatch][4] = 0;
								$_SESSION["arr_total_qty"][$int_CheckBatch][5] = 0;
								$_SESSION["arr_total_qty"][$int_CheckBatch][12] = $str_ProductDescription;
								$_SESSION["arr_total_qty"][$int_CheckBatch][13] = $int_ProductID;
								setDetails($strProductCode, $_SESSION["arr_item_batches"][$i][0], $int_CheckBatch);

							}
							else {
								$_SESSION["arr_total_qty"][$int_CheckBatch][0] = $strProductCode;
								$_SESSION["arr_total_qty"][$int_CheckBatch][1] = $_SESSION["arr_item_batches"][$i][0];
								$_SESSION["arr_total_qty"][$int_CheckBatch][2] = number_format(($_SESSION["arr_total_qty"][$int_CheckBatch][2] + ($fltBilledQty - $flt_BilledSoFar)), 3,'.','');
								$_SESSION["arr_total_qty"][$int_CheckBatch][3] = 0;
								$_SESSION["arr_total_qty"][$int_CheckBatch][4] = 0;
								$_SESSION["arr_total_qty"][$int_CheckBatch][5] = 0;
								$_SESSION["arr_total_qty"][$int_CheckBatch][12] = $str_ProductDescription;
								$_SESSION["arr_total_qty"][$int_CheckBatch][13] = $int_ProductID;
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
								$_SESSION["arr_total_qty"][$intLength][4] = 0;
								$_SESSION["arr_total_qty"][$intLength][5] = 0;
								$_SESSION["arr_total_qty"][$intLength][12] = $str_ProductDescription;
								$_SESSION["arr_total_qty"][$intLength][13] = $int_ProductID;
								setDetails($strProductCode, $_SESSION["arr_item_batches"][$i][0], $intLength);

								$flt_BilledSoFar = $flt_BilledSoFar + $_SESSION["arr_item_batches"][$i][1];
							}
							else {
								$_SESSION["arr_total_qty"][$intLength][0] = $strProductCode;
								$_SESSION["arr_total_qty"][$intLength][1] = $_SESSION["arr_item_batches"][$i][0];
								$_SESSION["arr_total_qty"][$intLength][2] = number_format(($fltBilledQty - $flt_BilledSoFar), 3,'.','');
								$_SESSION["arr_total_qty"][$intLength][3] = 0;
								$_SESSION["arr_total_qty"][$intLength][4] = 0;
								$_SESSION["arr_total_qty"][$intLength][5] = 0;
								$_SESSION["arr_total_qty"][$intLength][12] = $str_ProductDescription;
								$_SESSION["arr_total_qty"][$intLength][13] = $int_ProductID;
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
								$_SESSION["arr_total_qty"][$int_CheckBatch][4] = 0;
								$_SESSION["arr_total_qty"][$int_CheckBatch][5] = 0;
								$_SESSION["arr_total_qty"][$int_CheckBatch][12] = $str_ProductDescription;
								$_SESSION["arr_total_qty"][$int_CheckBatch][13] = $int_ProductID;
								setDetails($strProductCode, $_SESSION["arr_item_batches"][$i][0], $int_CheckBatch);

								$flt_BilledSoFar = $flt_BilledSoFar + ($_SESSION["arr_item_batches"][$i][1] - $_SESSION["arr_total_qty"][$int_CheckBatch][2]);
							}
							else {
								$_SESSION["arr_total_qty"][$int_CheckBatch][0] = $strProductCode;
								$_SESSION["arr_total_qty"][$int_CheckBatch][1] = $_SESSION["arr_item_batches"][$i][0];
								$_SESSION["arr_total_qty"][$int_CheckBatch][2] = number_format(($fltBilledQty - $flt_BilledSoFar), 3,'.','');
								$_SESSION["arr_total_qty"][$int_CheckBatch][3] = 0;
								$_SESSION["arr_total_qty"][$int_CheckBatch][4] = 0;
								$_SESSION["arr_total_qty"][$int_CheckBatch][5] = 0;
								$_SESSION["arr_total_qty"][$int_CheckBatch][12] = $str_ProductDescription;
								$_SESSION["arr_total_qty"][$int_CheckBatch][13] = $int_ProductID;
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
						$_SESSION["arr_total_qty"][$intLength][4] = 0;
						$_SESSION["arr_total_qty"][$intLength][5] = number_format($flt_Remainder, 3,'.','');
						$_SESSION["arr_total_qty"][$intLength][12] = $str_ProductDescription;
						$_SESSION["arr_total_qty"][$intLength][13] = $int_ProductID;
						setDetails($strProductCode, $_SESSION["arr_item_batches"][$i][0], $intLength);
					} 
					else {
						$_SESSION["arr_total_qty"][$int_CheckBatch][0] = $strProductCode;
						$_SESSION["arr_total_qty"][$int_CheckBatch][1] = $_SESSION["arr_item_batches"][$i][0];
						$_SESSION["arr_total_qty"][$int_CheckBatch][2] = number_format($_SESSION["arr_item_batches"][$i][1], 3,'.','');
						$_SESSION["arr_total_qty"][$int_CheckBatch][3] = 0;
						$_SESSION["arr_total_qty"][$int_CheckBatch][4] = 0;
						$_SESSION["arr_total_qty"][$int_CheckBatch][5] = number_format($flt_Remainder, 3,'.','');
						$_SESSION["arr_total_qty"][$int_CheckBatch][12] = $str_ProductDescription;
						$_SESSION["arr_total_qty"][$int_CheckBatch][13] = $int_ProductID;
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