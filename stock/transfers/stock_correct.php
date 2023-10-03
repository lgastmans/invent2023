<?
	require_once("../../include/const.inc.php");
	require_once("../../include/db.inc.php");
	require_once("../../include/session.inc.php");

	$int_access_level = (getModuleAccessLevel('Stock'));
	if ($_SESSION["int_user_type"]>1) {	
		$int_access_level = ACCESS_ADMIN;
	}

	$int_id = 0;
	if (IsSet($_GET['id']))
		$int_id = $_GET['id'];

	$qry = new Query("
		SELECT product_code
		FROM stock_product
		WHERE (product_id = $int_id)
			AND (deleted = 'N')
	");
	$str_code='';
	if ($qry->RowCount() > 0) {
		$str_code = $qry->FieldByName('product_code');
	}

?>

<script language="javascript">

	var can_save = false;
	var bool_is_decimal = false;
	var current_stock = 0;

	function CloseWindow() {
		if (window.opener) 
		  window.opener.document.location=window.opener.document.location.href;
		window.close();
	}

	function createRequest() {
		try {
			var requester = new XMLHttpRequest();
		}
		catch (error) {
			try {
				requester = new ActiveXObject("Microsoft.XMLHTTP");
			}
			catch (error) {
				return false;
			}
		}
		return requester;
	}

	var requester = createRequest();

	// RETURNS THE DESCRIPTION OF THE CODE ENTERED
	function stateHandler() {
		if (requester.readyState == 4) {
			if (requester.status == 200)  {
				var oTextBoxCode = document.stock_correct.code;
				var oTextBoxDescription = document.getElementById('description');
				var oTextBoxStock = document.getElementById('current_stock');

				str_retval = requester.responseText;

				if (str_retval == '__NOT_FOUND') {
				  can_save = false;
					oTextBoxCode.value = "";
					oTextBoxDescription.innerHTML = '';
				}
				else {
					arr_details = str_retval.split('|');

				if (arr_details[10] == "__NOT_AVAILABLE") {
					can_save = false;
						oTextBoxDescription.innerHTML = '';
						alert('This product cannot be received.\n It has been disabled');
						oTextBoxCode.focus();
				}
				else {
				
					can_save = true;

					oTextBoxDescription.innerHTML = arr_details[0];
					oTextBoxStock.innerHTML = arr_details[11]+" "+arr_details[12];
					current_stock = arr_details[11];

					if (arr_details[13] == 'Y')
						bool_is_decimal = true;
					else
						bool_is_decimal = false;
				}
			}
			}
			else {
				alert("failed to get description... please try again.");
			}
		requester = null;
		requester = createRequest();
		}
	}
	
	function getDescription(strProductCode) {
		requester.onreadystatechange = stateHandler;
		var strPassValue = '';
		if (strProductCode.value == '')
			strPassValue = 'nil'
		else
			strPassValue = strProductCode.value;
		requester.open("GET", "productDetails.php?live=1&product_code="+strPassValue);
		requester.send(null);
	}

	function receiveStock() {
		if (can_save == true) {
			document.stock_correct.Save.onclick = '';
			stock_correct.submit();
		}
  	}
	
	function setCorrected(aField) {
		var oTextBoxCorrected = document.getElementById('corrected_stock');
		
		flt_corrected = ((Number(current_stock) - Number(aField.value)) * -1);
		oTextBoxCorrected.innerHTML = "Corrected by: "+flt_corrected.toFixed(3);
	}
	
	function focusNext(aField, focusElem, evt) {
		evt = (evt) ? evt : event;
		var charCode = (evt.charCode) ? evt.charCode : ((evt.which) ? evt.which : evt.keyCode);

		var oTextBoxCode = document.stock_correct.code;
		var oTextBoxCorrect = document.stock_correct.correct;
		var oTextBoxNote = document.stock_correct.note;
		var oButtonSave = document.stock_correct.Save;
	
		if (charCode == 113) { // F2 Save
			oButtonSave.click();
                }
		else if (charCode == 13 || charCode == 3) {
			if (focusElem == 'correct') {
				oTextBoxCorrect.select();
			}
			else if (focusElem == 'note') {
			 oTextBoxNote.focus();
			}
			else if (focusElem == 'button_save') {
			 oButtonSave.focus();
			}
		} 
		else if (charCode == 27) {
			oTextBoxCode.select();
			clearValues;
		}	
/*    else if (charCode == 8) {
			if (focusElem == 'correct') {
				oTextBoxCorrect.select();
			}
			else if (focusElem == 'note') {
			 oTextBoxNote.focus();
			}
			else if (focusElem == 'button_save') {
			 oTextBoxMnfrYear.focus();
			}
		}
*/  	
		else if (charCode == 46) {
			if (focusElem == 'correct') {
				if (bool_is_decimal == false)
					return false;
			}
  		}
  	
		return true;
	}  
	
	function clearValues() {
		var oTextBoxCode = document.stock_correct.code;
		var oTextBoxDescription = document.getElementById('description');
		var oTextBoxStock = document.getElementById('current_stock');
		var oTextBoxCorrect = document.stock_correct.correct;
		var oTextBoxNote = document.stock_correct.note;

		oTextBoxCode.value = '';
		oTextBoxDescription.innerHTML = '';
		oTextBoxStock.innerHTML = '';
		oTextBoxCorrect.value = '';
		oTextBoxNote.value = '';
	}
	
	function openSearch() {
		myWin = window.open("../../common/product_search.php?formname=stock_correct&fieldname=code",'searchProduct','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=400,height=600,top=0');
		myWin.focus();
	}
</script>

<?	
	$str_message = '';

	if (IsSet($_POST["action"])) {
		
		if ($_POST["action"] == "save") {
			// verify product code
			$can_save = true;
			$int_product_id = -1;
			
			$qry = new Query("
				SELECT *
				FROM stock_product
				WHERE (product_code = '".$_POST["code"]."')
					AND (deleted = 'N')
			");
			if ($qry->RowCount() == 0) {
				$str_message = "Product code not found";
				$can_save = false;
			}
			else {
				$int_product_id = $qry->FieldByName('product_id');
			}
			
			if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
				$str_message = '';
			}
			else {
				$str_message = 'Cannot correct in previous months. \\n Select the current month/year and continue.';
				$can_save = false;
			}
			
			if (empty($_POST["correct"])) {
				$flt_correct = 0;
			}
			else {
				$flt_correct = number_format(floatval($_POST["correct"]), 3, '.', '');
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

//=======================
// ADDING STOCK
//=======================
				//========================================================
				// if adding stock, add a batch without setting a supplier
				// and update the stock
				//--------------------------------------------------------
				if ($flt_corrected_by > 0) {
					if (!empty($_POST["note"]))
						$str_description = "CORRECTION, added ".$flt_corrected_by.", ".$_POST["note"];
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
//=======================
// DEDUCTING STOCK
//=======================
				//=======================
				// if deducting stock, remove from batches
				//=======================
				else {
					
					if (IsSet($_POST["note"]))
						$str_description = "CORRECTION, deducted (total ".$flt_corrected_by."), ".$_POST["note"];
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
		} // end of action == save
	} // end of IsSet($_GET["action"]))
		
?>

<html>
<head>
	<link rel="stylesheet" type="text/css" href="../../include/styles.css" />
</head>
<body id='body_bgcolor'>
<form name="stock_correct" method="POST" onsubmit="return false">


<table width='100%' height='90%' border='0' >
<tr>
	<td align='center' valign='center'>
	
<?
	boundingBoxStart("400", "../../images/blank.gif");

	if ($str_message != '')  { ?>
		<script language='javascript'>
		alert('<?echo $str_message?>');
		</script>
<?
	}
?>

	<table border="0" cellpadding="2" cellspacing="0">
		<tr>
			<td align="right" class="normaltext"><b>Code</b></td>
			<td><input type="text" name="code" value="<?php echo $str_code;?>" class='input_100' autocomplete="OFF" onblur="javascript:getDescription(this)" onkeypress="focusNext(this, 'correct', event)">&nbsp;<a href="javascript:openSearch()"><img src="../../images/find.png" border="0" title="Search" alt="Search"></a></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td id="description" class="spantext">&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td id="current_stock" class="spantext">&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td id="corrected_stock" class="spantext">&nbsp;</td>
		</tr>
		<tr>
			<td align="right" class="normaltext"><b>Correct Stock</b></td>
			<td><input type="text" name="correct" value="" class='input_100' autocomplete="OFF" onkeyup="setCorrected(this)" onkeypress="return focusNext(this, 'note', event)"></td>
		</tr>
		<tr>
			<td align="right" class="normaltext"><b>Note</b></td>
			<td><input type="text" name="note" value="" class='input_settings' autocomplete="OFF" onkeypress="return focusNext(this, 'button_save', event)"></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="right">
				<input type="hidden" name="action" value="save">
				<? if ($int_access_level > ACCESS_READ) { ?>
				<input type="button" class="mainmenu_button" name="Save" value="Save" onclick="receiveStock()">
				<? } else { ?>
				&nbsp;
				<? } ?>
			</td>
			<td>
				<input type="button" class="mainmenu_button" name="Close" value="Close" onclick="CloseWindow()">
			</td>
		</tr>
		<tr>
		    <td colspan="2">
			    <i><br><font class="normaltext">Correcting stock nullifies all manually adjusted stock</font></i>
		    </td>
		</tr>
	</table>
<?
    boundingBoxEnd("400", "../../images/blank.gif");
?>

</td></tr>
</table>

</form>

<script language="javascript">
  document.stock_correct.code.focus();
</script>

</body>
</html>