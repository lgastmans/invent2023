<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/tax.php");

	$int_purchase_order_id = 0;
	if (IsSet($_GET["id"]))
		$int_purchase_order_id = $_GET['id'];
	else if (IsSet($_POST["id"]))
		$int_purchase_order_id = $_POST['id'];
	
	$qry_purchase_order = new Query("
		SELECT *
		FROM ".Yearalize('purchase_order')."
		WHERE purchase_order_id = $int_purchase_order_id
	");
	
	//==============
	// list of taxes
	//--------------
	$qry_tax = new Query("
		SELECT tax_id, tax_description
		FROM ".Monthalize('stock_tax')
	);
	
	//==================
	// list of suppliers
	//------------------
        $qry_suppliers = new Query("
		SELECT supplier_id, supplier_name
		FROM stock_supplier
		WHERE is_active = 'Y'
		ORDER BY supplier_name
	");

	function loadArray($int_purchase_order_id) {
		// result set of items to receive, listing only those where the is_received
		// flag is still false
		$str_receive = "SELECT *, pi.supplier_id AS cur_supplier
			FROM ".Yearalize('purchase_items')." pi,
				stock_product sp
			WHERE (purchase_order_id = ".$int_purchase_order_id.") AND
				(pi.product_id = sp.product_id) AND
				(pi.is_received = 'N')";
				
		$qry_receive = new Query($str_receive);
		
		// load the result set in an array
		for ($i = 0; $i < $qry_receive->RowCount(); $i++) {
			$_SESSION["arr_split_batches"][$i] = array(
				'code'			=> $qry_receive->FieldByName('product_code'),
				'description'		=> $qry_receive->FieldByName('product_description'),
				'batch_id'		=> '',
				'product_id'		=> $qry_receive->FieldByName('product_id'),
				'batch'			=> "",
				'ordered'		=> $qry_receive->FieldByName('quantity_ordered'),
				'received'		=> $qry_receive->FieldByName('quantity_ordered'),
				'bonus'			=> 0,
				'buying_price'		=> number_format($qry_receive->FieldByName('buying_price'), 2, '.', ''),
				'selling_price'		=> number_format($qry_receive->FieldByName('selling_price'), 2, '.', ''),
				'tax_id'		=> $qry_receive->FieldByName('tax_id'),
				'date_manufacture'	=> time(),
				'shelf_life'		=> $qry_receive->FieldByName('shelf_life'),
				'active'		=> 'Y',
				'supplier_id'		=> $qry_receive->FieldByName('cur_supplier'),
				'receive'		=> 'Y',
				'split_batch'		=> $i,
				'num_split_batches'	=> 0,
				'is_split_batch'	=> 'N',
				'margin_percent'	=> $qry_receive->FieldByName('margin_percent')
			);
			$qry_receive->Next();
		}
	}

	//=============================================================================
	// returns the number of items selected to process based on the "receive" field
	//-----------------------------------------------------------------------------
	function getSelectedRows() {
		$int_result = 0;
		for ($i=0; $i<count($_SESSION["arr_split_batches"]); $i++) {
			if ($_SESSION["arr_split_batches"][$i]["receive"] == 'Y')
				$int_result = $int_result + 1;
		}
		return $int_result;
	}

	function save_to_array() {
		for ($i = 0; $i < count($_SESSION["arr_split_batches"]); $i++) {
			$_SESSION["arr_split_batches"][$i]['batch'] = $_POST["product_id_".$i];
			$_SESSION["arr_split_batches"][$i]['received'] = $_POST["received_".$i];
			$_SESSION["arr_split_batches"][$i]['bonus'] = $_POST["bonus_".$i];
			$_SESSION["arr_split_batches"][$i]['buying_price'] = $_POST["buying_price_".$i];
			$_SESSION["arr_split_batches"][$i]['selling_price'] = $_POST["selling_price_".$i];
			$_SESSION["arr_split_batches"][$i]['tax_id'] = $_POST["select_tax_".$i];
//			$_SESSION["arr_split_batches"][$i]['date_manufacture'] = $_POST["date_manufacture_".$i];
			$_SESSION["arr_split_batches"][$i]['active'] = 'Y';
			$_SESSION["arr_split_batches"][$i]['supplier_id'] = $_POST["select_supplier_".$i];
			if (IsSet($_POST["cb_receive_".$i]))
				$_SESSION["arr_split_batches"][$i]['receive'] = 'Y';
			else
				$_SESSION["arr_split_batches"][$i]['receive'] = 'N';
		}
	}

	function removeBatch($aBatchID, $anIndexID) {
		// locate the array row for the given intID
		for ($i = 0; $i < count($_SESSION["arr_split_batches"]); $i++) {
			if ($_SESSION["arr_split_batches"][$i]['batch_id'] == $aBatchID) {
				// get the number of split batches for the given batch
				$tmp_num = $_SESSION["arr_split_batches"][$i]['num_split_batches'];
				
				// remove the row from the array
				$_SESSION["arr_split_batches"] = array_delete($_SESSION["arr_split_batches"], $anIndexID);
				
				// decrement the 'num_split_batches' in the original entry
				$tmp_num = $tmp_num - 1;
				$_SESSION["arr_split_batches"][$i]['num_split_batches'] = $tmp_num;
				
				break;
			}
		}
	}

	function splitBatch($intID) {
		// locate the array row for the given intID
		for ($i = 0; $i < count($_SESSION["arr_split_batches"]); $i++) {
			if ($_SESSION["arr_split_batches"][$i]['batch_id'] == $intID) {
				
				// get the index of the last split batch to copy the data from
				$int_last_pos = $i + $_SESSION["arr_split_batches"][$i]['num_split_batches'];
				// get the number of split batches for the given batch
				$tmp_num = $_SESSION["arr_split_batches"][$i]['num_split_batches'];
				
				// fill new elements with the original entry
				$arr_new = array(
					'code'			=> $_SESSION["arr_split_batches"][$int_last_pos]['code'],
					'description'		=> $_SESSION["arr_split_batches"][$int_last_pos]['description'],
					'batch_id'		=> $_SESSION["arr_split_batches"][$int_last_pos]['batch_id'],
					'product_id'		=> $_SESSION["arr_split_batches"][$int_last_pos]['product_id'],
					'batch'			=> $_SESSION["arr_split_batches"][$int_last_pos]['batch'],
					'ordered'		=> $_SESSION["arr_split_batches"][$int_last_pos]['ordered'],
					'received'		=> $_SESSION["arr_split_batches"][$int_last_pos]['ordered'] - $_SESSION["arr_split_batches"][$int_last_pos]['received'],
					'bonus'			=> $_SESSION["arr_split_batches"][$int_last_pos]['bonus'],
					'buying_price'		=> $_SESSION["arr_split_batches"][$int_last_pos]['buying_price'],
					'selling_price'		=> $_SESSION["arr_split_batches"][$int_last_pos]['selling_price'],
					'tax_id'		=> $_SESSION["arr_split_batches"][$int_last_pos]['tax_id'],
					'date_manufacture'	=> $_SESSION["arr_split_batches"][$int_last_pos]['date_manufacture'],
					'shelf_life'		=> $_SESSION["arr_split_batches"][$int_last_pos]['shelf_life'],
					'active'		=> $_SESSION["arr_split_batches"][$int_last_pos]['active'],
					'supplier_id'		=> $_SESSION["arr_split_batches"][$int_last_pos]['supplier_id'],
					'receive'		=> $_SESSION["arr_split_batches"][$int_last_pos]['receive'],
					'split_batch'		=> $_SESSION["arr_split_batches"][$int_last_pos]['split_batch'],
					'num_split_batches'	=> $_SESSION["arr_split_batches"][$int_last_pos]['num_split_batches'],
					'is_split_batch'	=> 'Y',
					'margin_percent'	=> $_SESSION["arr_split_batches"][$int_last_pos]['margin_percent']
				);
				
				// insert a new entry at the end of the current list of split batches
				$_SESSION["arr_split_batches"] = array_insert($_SESSION["arr_split_batches"], $i+$tmp_num+1, array($arr_new));
				
				// increment the 'num_split_batches' in the original entry
				$tmp_num = $tmp_num + 1;
				$_SESSION["arr_split_batches"][$i]['num_split_batches'] = $tmp_num;
				
				break;
			}
		}
	}

	//=============================================================
	// initialize the array that holds the rows and the row counter
        //-------------------------------------------------------------
	if (!IsSet($_POST["action"]) && (!IsSet($_POST['batchaction']))) {
		$_SESSION["arr_split_batches"] = array();
		loadArray($int_purchase_order_id);
	}
	
	//======================================================
	// check whether a batch has been selected for splitting
        //------------------------------------------------------
	if (IsSet($_POST["batchaction"])) {
		if ($_POST["batchaction"] == "batch_split") {
			// save all data entered so far
			save_to_array();
			
			//add to array
			splitBatch($_POST["batch_id"]);
		}
		// or for removing
		else if ($_POST["batchaction"] == "batch_remove") {
			// save all data entered so far
			save_to_array();
			echo "here";
			// remove from array
			removeBatch($_POST["batch_id"], $_POST["del_index"]);
		}
	}
	
	if (IsSet($_POST['action'])) {
		if ($_POST['action'] == 'Save') {
			
			if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
				
				//===============================================
				// get the number of rows that have been selected
				//-----------------------------------------------
				save_to_array();
				
				$int_num_rows_selected = getSelectedRows();
				
				if ($int_num_rows_selected > 0) {
					$qry_update = new Query("START TRANSACTION");
					$bool_success = true;
					$str_message = "";
					
					for ($i = 0; $i < count($_SESSION["arr_split_batches"]); $i++) {
						
						if ($_SESSION["arr_split_batches"][$i]['receive'] == 'Y') {
							
							$total_received = number_format(($_POST["received_".$i] + $_POST["bonus_".$i]), 3, '.', '');
							$actual_stock_received = number_format(($_POST["received_".$i] + $_POST["bonus_".$i]), 3, '.', '');
							$str_adjusted = '';
							
							//==================================
							// update the adjusted stock, if any
							//----------------------------------
							$qry_update->Query("
								SELECT stock_adjusted
								FROM ".Monthalize('stock_storeroom_product')."
								WHERE (product_id = ".$_POST["product_id_".$i].")
									AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
							");
							if ($qry_update->RowCount() > 0) {
								if ($qry_update->FieldByName('stock_adjusted') > 0) {
									if ($qry_update->FieldByName('stock_adjusted') > $total_received) {
										// update the stock_adjusted in stock_storeroom_product
										$qry_adjust = new Query("
											UPDATE ".Monthalize('stock_storeroom_product')."
											SET stock_adjusted = ROUND(stock_adjusted - ".$total_received.", 3)
											WHERE (product_id = ".$_POST["product_id_".$i].")
												AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
										");
										
										$str_adjusted = ", adjusted: ".$total_received;
										$stock_adjusted = $total_received;
										$total_received = 0;
									}
									else {
										// update the stock_adjusted in stock_storeroom_product
										$qry_adjust = new Query("
											UPDATE ".Monthalize('stock_storeroom_product')."
											SET stock_adjusted = 0
											WHERE (product_id = ".$_POST["product_id_".$i].")
												AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
										");
										
										$str_adjusted = ", adjusted: ".$qry_update->FieldByName('stock_adjusted');
										$stock_adjusted = number_format($qry_update->FieldByName('stock_adjusted'), 3, '.', '');
										$total_received = $total_received - $qry_update->FieldByName('stock_adjusted');
									}
								}
							}
							
							
							//========================
							// insert into STOCK_BATCH
							//------------------------
							if (!empty($_POST["batch_".$i])) {
								$qry_update->Query("
									INSERT INTO ".Yearalize('stock_batch')."
										(batch_code,
										buying_price,
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
									VALUES('".
										$_POST["batch_".$i]."', ".
										$_POST["buying_price_".$i].", ".
										$_POST["selling_price_".$i].", '".
										set_mysql_date($_POST['date_received'],'-')."', ".
										$total_received.", '".
										date('Y-m-d', time())."', '".
										date('Y-m-d', time())."', ".
										"'Y', '".
										STATUS_COMPLETED."', ".
										$_SESSION["int_user_id"].", ".
										$qry_purchase_order->FieldByName('assigned_to_user_id').", ".
										$_POST["select_supplier_".$i].", ".
										$_POST["product_id_".$i].", ".
										$_SESSION["int_current_storeroom"].", ".
										$_POST["select_tax_".$i]."
									)
								");
							}
							else {
								// don't save the batch code, and set it later to the autoincremental value of batch_id
								$qry_update->Query("INSERT INTO ".Yearalize('stock_batch')."
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
										$_POST["buying_price_".$i].", ".
										$_POST["selling_price_".$i].", '".
										set_mysql_date($_POST['date_received'],'-')."', ".
										$total_received.", '".
										date('Y-m-d', time())."', '".
										date('Y-m-d', time())."', ".
										"'Y', '".
										STATUS_COMPLETED."', ".
										$_SESSION["int_user_id"].", ".
										$qry_purchase_order->FieldByName('assigned_to_user_id').", ".
										$_POST["select_supplier_".$i].", ".
										$_POST["product_id_".$i].", ".
										$_SESSION["int_current_storeroom"].", ".
										$_POST["select_tax_".$i]."
										)");
							}
							$int_batch_id = $qry_update->getInsertedID();
							
							if (empty($_POST["batch_".$i])) {
								//============================================================
								// set the batch code to the autoincremental value of batch_id
								//------------------------------------------------------------
								$qry_update->Query("
									UPDATE ".Yearalize('stock_batch')."
									SET batch_code = '".$int_batch_id."'
									WHERE (batch_id=".$int_batch_id.")
										AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
								");
							}
							if ($qry_update->b_error == true) {
								$bool_success = false;
								$str_message = "error updating stock batch";
							}
							
							//===============================================================
							// flag is_active to false where stock_available is zero or below
							//---------------------------------------------------------------
							$qry_update->Query("
								UPDATE ".Monthalize('stock_storeroom_batch')."
								SET is_active = 'N',
									debug = 'po receive'
								WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")
									AND (product_id = ".$_POST["product_id_".$i].")
									AND (stock_available <= 0)
							");
							
							//================================================================================
							// update stock_storeroom_product, updating fields stock_ordered and stock_current
							//--------------------------------------------------------------------------------
							$qry_update->Query("
								SELECT *
								FROM ".Monthalize('stock_storeroom_product')."
								WHERE (product_id = ".$_POST["product_id_".$i].")
									AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
							");
							
							if ($qry_update->RowCount() > 0) {
								if ($qry_update->FieldByName('use_batch_price') == 'Y') {
									$qry_update->Query("
										UPDATE ".Monthalize('stock_storeroom_product')."
										SET stock_ordered = ROUND(stock_ordered - ".$_POST["ordered_".$i].", 3),
											stock_current = stock_current + ".$total_received."
										WHERE (product_id = ".$_POST["product_id_".$i].") AND
											(storeroom_id = ".$_SESSION["int_current_storeroom"].")
									");
									if ($qry_update->b_error == true) {
										$bool_success = false;
										$str_message = "error updating stock storeroom product";
									}
								}
								else {
									$qry_update->Query("
										UPDATE ".Monthalize('stock_storeroom_product')."
										SET stock_ordered = ROUND(stock_ordered - ".$_POST["ordered_".$i].", 3),
											stock_current = stock_current + ".$total_received.",
											buying_price = ".$_POST["buying_price_".$i].",
											sale_price = ".$_POST["selling_price_".$i]."
										WHERE (product_id = ".$_POST["product_id_".$i].") AND
											(storeroom_id = ".$_SESSION["int_current_storeroom"].")
									");
									if ($qry_update->b_error == true) {
										$bool_success = false;
										$str_message = "error updating stock storeroom product";
									}
								}
							}
							else {
								$qry_update->Query("
									INSERT INTO ".Monthalize('stock_storeroom_product')."
									(
										stock_ordered,
										stock_current,
										product_id,
										storeroom_id)
									VALUES (
										".$_POST["ordered_".$i].",
										".$total_received.",
										".$_POST["product_id_".$i].",
										".$_SESSION["int_current_storeroom"].")
								");
								if ($qry_update->b_error == true) {
									$bool_success = false;
									$str_message = "error inserting into stock storeroom product";
								}
							}
							
							//======================================
							// add an entry in stock_storeroom_batch
							//--------------------------------------
							$qry_update->Query("
								INSERT INTO ".Monthalize('stock_storeroom_batch')."
									(stock_available,
									shelf_id,
									batch_id,
									storeroom_id,
									product_id)
								VALUES (".$total_received.",
									0, ".
									$int_batch_id.", ".
									$_SESSION["int_current_storeroom"].", ".
									$_POST["product_id_".$i].")");
							if ($qry_update->b_error == true) {
								$bool_success = false;
								$str_message = "error updating stock storeroom batch";
							}
							
							//===============================================
							// check whether an entry exists in stock_balance
							//-----------------------------------------------
							$qry_update->Query("
								SELECT * 
								FROM ".Yearalize('stock_balance')." 
								WHERE (product_id=".$_POST["product_id_".$i].")
									AND (storeroom_id=".$_SESSION["int_current_storeroom"].")
									AND (balance_month=".$_SESSION["int_month_loaded"].")
									AND (balance_year=".$_SESSION["int_year_loaded"].")
							");
							
							//=======================================
							// update entry in stock_balance if found
							//---------------------------------------
							if ($qry_update->RowCount() > 0) {
								$qry_update->Query("
									UPDATE ".Yearalize('stock_balance')."
									SET stock_received = stock_received + ".$actual_stock_received.",
										stock_closing_balance = stock_closing_balance + ".$total_received."
									WHERE (product_id=".$_POST["product_id_".$i].") 
										AND (storeroom_id=".$_SESSION["int_current_storeroom"].")
										AND (balance_month=".$_SESSION["int_month_loaded"].")
										AND (balance_year=".$_SESSION["int_year_loaded"].")
								");
								if ($qry_update->b_error == true) {
									$bool_success = false;
									$str_message = "error updating stock balances";
								}
								// else create an entry
							} else {
								$qry_update->Query("
									INSERT INTO ".Yearalize('stock_balance')."
									(stock_closing_balance,
										stock_received,
										product_id,
										storeroom_id,
										balance_month,
										balance_year)
									VALUES (".$total_received.", ".
										$total_received.", ".
										$_POST["product_id_".$i].", ".
										$_SESSION["int_current_storeroom"].", ".
										$_SESSION["int_month_loaded"].", ".
										$_SESSION["int_year_loaded"].")
								");
								if ($qry_update->b_error == true) {
									$bool_success = false;
									$str_message = "error updating stock balances";
								}
							}
							
							//===============================
							// add an entry in stock_transfer
							//-------------------------------
							$str_update = "
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
									$actual_stock_received.", '".
									"Purchase order ".$str_adjusted."', '".
									$_POST['reference']."', '".
									set_mysql_date($_POST['date_received'],'-')."', ".
									"3, ".
									$_SESSION["int_user_id"].", ".
									"0, ".
									$_SESSION["int_current_storeroom"].", ".
									$_POST["product_id_".$i].", ".
									$int_batch_id.", ".
									$int_purchase_order_id.", ".
									TYPE_RECEIVED.", ".
									STATUS_COMPLETED.", ".
									$qry_purchase_order->FieldByName('assigned_to_user_id').", ".
									$_SESSION["int_user_id"].", ".
									"'N')"; 
							$qry_update->Query($str_update);
							if ($qry_update->b_error == true) {
								$bool_success = false;
								$str_message = "error updating stock transfer";
							}
							
							//==================================================================================
							// update purchase_items, setting the quantity_received, quantity_bonus and batch_id field
							//----------------------------------------------------------------------------------
							$qry_update->Query("
								UPDATE ".Yearalize('purchase_items')."
								SET quantity_received=".$_POST["received_".$i].",
									quantity_bonus=".$_POST["bonus_".$i].",
									buying_price=".$_POST["buying_price_".$i].",
									selling_price=".$_POST["selling_price_".$i].",
									tax_id=".$_POST["select_tax_".$i].",
									supplier_id=".$_POST["select_supplier_".$i].",
									is_received='Y',
									batch_id=".$int_batch_id."
								WHERE (purchase_order_id=".$int_purchase_order_id.") AND
									(product_id=".$_POST["product_id_".$i].")
							");
							if ($qry_update->b_error == true) {
								$bool_success = false;
								$str_message = "error updating purchase items";
							}
						} // end of "if selected"
					}	// end of loop that iterates through received items
					
					//======================================================================
					// set the purchase order to received only if selected rows = total rows
					//----------------------------------------------------------------------
					if ($int_num_rows_selected == count($_SESSION["arr_split_batches"])) {
						// update purchase_order_year, setting purchase_status field
						$str_update = "
							UPDATE ".Yearalize('purchase_order')."
							SET purchase_status=".PURCHASE_RECEIVED.",
								date_received='".set_mysql_date($_POST['date_received'],'-')."',
								purchase_order_ref = '".$_POST['reference']."',
								invoice_number = '".$_POST['invoice_number']."',
								invoice_date = '".set_mysql_date($_POST['invoice_date'],'-')."'
							WHERE (purchase_order_id=".$int_purchase_order_id.")";
						$qry_update->Query($str_update);
						
						if ($qry_update->b_error == true) {
							$bool_success = false;
							$str_message = "error updating purchase order";
						}
					}
					
					if ($bool_success) {
						$qry_update->Query("COMMIT");
						echo "<script language=\"javascript\">\n";
						echo "alert('Purchase Order saved successfully');\n";
						//echo "YAHOO.example.container.hidePanel();\n";
						echo "window.opener.document.location=window.opener.document.location.href;\n";
						echo "window.close();\n";
						echo "</script>\n";
					}
					else {
						$qry_update->Query("ROLLBACK");
						echo "<script language=\"javascript\">\n";
						echo "YAHOO.example.container.hidePanel();\n";
						echo "alert('".$str_message."');\n";
						echo "</script>\n";
					}
					
					$qry_update->Free();
					
				} // end of "if selected > 0" statement
			}
			else {
				echo "<script language=\"javascript\">\n";
				//echo "YAHOO.example.container.hidePanel();\n";
				echo "alert('Select current month before saving.');\n";
				echo "window.opener.document.location=window.opener.document.location.href;\n";
				echo "window.close();\n";
				echo "</script>\n";
			}
		} //if ($_GET['action'] == 'Save')
	} // if (IsSet($_GET['action']))
?>

<html>
	<head>
		<!-- for the modal panel -->
		<link rel="stylesheet" type="text/css" href="../yui2.7.0/build/fonts/fonts-min.css" />
		<link rel="stylesheet" type="text/css" href="../yui2.7.0/build/container/assets/skins/sam/container.css" />
		<script type="text/javascript" src="../yui2.7.0/build/yahoo-dom-event/yahoo-dom-event.js"></script>
		
		<script type="text/javascript" src="../yui2.7.0/build/connection/connection-min.js"></script>
		<script type="text/javascript" src="../yui2.7.0/build/animation/animation-min.js"></script>
		<script type="text/javascript" src="../yui2.7.0/build/dragdrop/dragdrop-min.js"></script>
		<script type="text/javascript" src="../yui2.7.0/build/container/container-min.js"></script>	
		<!-- end modal panel -->
		
		<script language="javascript" src="../include/calendar1.js"></script>
		<link rel="stylesheet" type="text/css" href="../include/styles.css" />
		<script language="javaScript">
			function setBatchSplit(intID) {
//				alert('batch_id '+intID);
				document.receive_purchase_order.batchaction.value = 'batch_split';
				document.receive_purchase_order.batch_id.value = intID;
				document.receive_purchase_order.submit();
			}
		
			function removeBatchSplit(aBatchID, atIndex) {
//				alert('batch_id '+aBatchID+', index '+atIndex);
				document.receive_purchase_order.batchaction.value = 'batch_remove';
				document.receive_purchase_order.batch_id.value = aBatchID;
				document.receive_purchase_order.del_index.value = atIndex;
				document.receive_purchase_order.submit();
			}
		</script>
	</head>

<body class="yui-skin-sam" id='body_bgcolor' leftmargin=0 topmargin=0 marginwidth=7 marginheight=7>

<div id="content"></div>

<form name='receive_purchase_order' method='POST'>
	
	<input type='hidden' name='id' value='<?echo $int_purchase_order_id;?>'>
	<input type='hidden' name='batchaction' value='none'>
	<input type='hidden' name='batch_id' value=0>
	<input type='hidden' name='del_index' value=-1>
	

	<table width="100%" border="0" cellpadding="0" cellspacing="4">
		<tr>
			<td width="220px" class='normaltext'>
				Date received :
				<input type='text' name='date_received' readonly value="<?echo set_formatted_date($qry_purchase_order->FieldByName('date_expected_delivery'),'-');?>" class='input_100'>
				<a href="javascript:cal1.popup();"><img src="../images/calendar/cal.gif" width="16" height="16" border="0" alt="Click here to select a date"></a>
			</td>
			<td width="220px" class="normaltext">
				Reference :
				<input type="text" name="reference" value="<?echo $qry_purchase_order->FieldByName('purchase_order_ref');?>" class="input_100">
			</td>
			<td width="220px" class="normaltext">
				Invoice No.:
				<input type="text" name="invoice_number" value="<?echo $qry_purchase_order->FieldByName('invoice_number');?>" class="input_100">
			</td>
			<td width="220px" class="normaltext">
				Invoice Dt.:
				<input type="text" name="invoice_date" readonly value="<?echo set_formatted_date($qry_purchase_order->FieldByName('invoice_date'),'-');?>" class="input_100">
				<a href="javascript:cal2.popup();"><img src="../images/calendar/cal.gif" width="16" height="16" border="0" alt="Click here to select a date"></a>
			</td>
			<td>&nbsp;</td>
		</tr>
	</table>
	
		<table border='1' cellpadding='5' cellspacing='0'>
			<tr bgcolor='lightgrey' >
				<td width='80px' class='normaltext_bold'>Code
				<td width='250px' class='normaltext_bold'>Description</td>
				<td width='65px' class='normaltext_bold'>Ordered</td>
				<td width='65px' class='normaltext_bold'>Batch</td>
				<td width='65px' class='normaltext_bold'>Received</td>
				<td width='65px' class='normaltext_bold'>Bonus</td>
				<td width='65px' class='normaltext_bold'>B.Price</td>
				<td width='65px' class='normaltext_bold'>S.Price</td>
				<td class='normaltext_bold'>Tax</td>
				<td width='150px' class='normaltext_bold'>Supplier</td>
				<td width='50px' align='center' class='normaltext_bold'>Receive</td>
				<td>&nbsp;</td>
			</tr>
			<?
				for ($i = 0; $i < count($_SESSION["arr_split_batches"]); $i++) {
				?>
				<tr class='normaltext'>
					<td>
						<input type='hidden' name='product_id_<?echo $i?>' value='<?echo $_SESSION["arr_split_batches"][$i]['product_id']?>'>
						<input type='hidden' name='ordered_<?echo $i?>' value='<?echo $_SESSION["arr_split_batches"][$i]['ordered']?>'>
						<?echo $_SESSION["arr_split_batches"][$i]['code'];?>
					</td>
					<td><?echo $_SESSION["arr_split_batches"][$i]['description'];?></td>
					<td><?echo $_SESSION["arr_split_batches"][$i]['ordered'];?></td>
					<td>
						<input type='text' class='input_100' name='batch_<?echo $i?>' value='<?echo $_SESSION["arr_split_batches"][$i]['batch_id'];?>'>
					</td>
					<td>
						<input type='text' class='input_50' name='received_<?echo $i?>' value='<?echo $_SESSION["arr_split_batches"][$i]['received'];?>'>
					</td>
					<td>
						<input type='text' class='input_50' name='bonus_<?echo $i?>' value='<?echo $_SESSION["arr_split_batches"][$i]['bonus'];?>'>
					</td>
					<td>
						<input type='text' class='input_50' name='buying_price_<?echo $i?>' value='<?echo $_SESSION["arr_split_batches"][$i]['buying_price'];?>'>
					</td>
					<td>
						<input type='text' class='input_50' name='selling_price_<?echo $i?>' value='<?echo $_SESSION["arr_split_batches"][$i]['selling_price'];?>'>
					</td>
					<td>
						<select name='select_tax_<?echo $i?>' class='select_50'>
						<?
							$qry_tax->First();
							for ($j=0; $j<$qry_tax->RowCount(); $j++) {
								if ($qry_tax->FieldByName('tax_id') == $_SESSION["arr_split_batches"][$i]['tax_id'])
									echo "<option value='".$qry_tax->FieldByName('tax_id')."' selected>".$qry_tax->FieldByName('tax_description')."\n";
								else
									echo "<option value='".$qry_tax->FieldByName('tax_id')."'>".$qry_tax->FieldByName('tax_description')."\n";
								$qry_tax->Next();
							}
						?>
						</select>
					</td>
					<td>
						<select name='select_supplier_<?echo $i?>' class='select_200'>
						<?
							$qry_suppliers->First();
							for ($j=0; $j<$qry_suppliers->RowCount(); $j++) {
								if ($qry_suppliers->FieldByName('supplier_id') == $_SESSION["arr_split_batches"][$i]['supplier_id'])
									echo "<option value='".$qry_suppliers->FieldByName('supplier_id')."' selected>".$qry_suppliers->FieldByName('supplier_name')."\n";
								else
									echo "<option value='".$qry_suppliers->FieldByName('supplier_id')."'>".$qry_suppliers->FieldByName('supplier_name')."\n";
								$qry_suppliers->Next();
							}
						?>
						</select>
					</td>
					<td align='center'>
						<input type='checkbox' name='cb_receive_<?echo $i?>' class='normaltext' <?if ($_SESSION["arr_split_batches"][$i]['receive'] == 'Y') echo 'checked'?>>
					</td>
					<td>
					<?
						if ($_SESSION["arr_split_batches"][$i]['is_split_batch'] == 'N')
							echo "<input type='button' name='action' value='split' class='small_button' onclick='setBatchSplit(".$_SESSION["arr_split_batches"][$i]['batch_id'].")'>";
						else
							echo "<input type='button' name='action' value='remove' class='small_button' onclick='removeBatchSplit(".$_SESSION["arr_split_batches"][$i]['batch_id'].", ".$i.")'>";
					?>
					</td>
				</tr>
				<?
				}
			?>
			<tr bgcolor='lightgrey'>
				<td colspan='13'>
					<input type='submit' name='action' id="save" value='Save' class='settings_button'>&nbsp;
					<input type='button' name='action' value='Close' class='settings_button' onclick='window.close()'>
				</td>
			</tr>
		</table>

</form>

<script type="text/javascript">

	YAHOO.namespace("example.container");
	
	function init() {
	
		var content = document.getElementById("content");
		var oSave = document.getElementById("save");
		oSave.enabled = false;
		
		content.innerHTML = "";
	
		if (!YAHOO.example.container.wait) {
			// Initialize the temporary Panel to display while waiting for external content to load
			YAHOO.example.container.wait = 
				new YAHOO.widget.Panel("wait",  
								{ width: "240px", 
								fixedcenter: true, 
								close: false, 
								draggable: false, 
								zindex:4,
								modal: true,
								visible: false
								} 
								);
		
			YAHOO.example.container.wait.setHeader("Saving, please wait...");
			YAHOO.example.container.wait.setBody("<img src=\"http://l.yimg.com/a/i/us/per/gr/gp/rel_interstitial_loading.gif\"/>");
			YAHOO.example.container.wait.render(document.body);
		}
		
		//setTimeout("YAHOO.example.container.hidePanel();", 2000 );
		
		YAHOO.example.container.hidePanel = function() {
			content.innerHTML = '';
			content.style.visibility = "visible";
			YAHOO.example.container.wait.hide();
		}
		
		// Show the Panel
		YAHOO.example.container.wait.show();
	}
	
	YAHOO.util.Event.on("save", "click", init);
		
</script>

<script language="javascript">

		var oTextDate = document.receive_purchase_order.date_received;
		var oTextDate2 = document.receive_purchase_order.invoice_date;

        var cal1 = new calendar1(oTextDate);
        cal1.year_scroll = true;
        cal1.time_comp = false;

        var cal2 = new calendar1(oTextDate2);
        cal2.year_scroll = true;
        cal2.time_comp = false;
		

		set_session_variables();

</script>

</body>
</html>
