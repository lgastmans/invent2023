<?
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");
//	require_once("../../common/account.php");
//	require_once("order_functions.inc.php");

	error_reporting(E_ERROR);

	if (IsSet($_GET['action'])) {

		if ($_GET['action'] == 'save') {

			if ($_SESSION['order_bill_id'] > -1) {
				
				$err_status = 1;
				if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
					$str_message = '';
					$err_status = 1;
				}
				else {
					$str_message = 'Cannot edit orders in previous months. \\n Select the current month/year and continue.';
					$err_status = 0;
				}
				
				$bool_success = true;
				$bool_changed = false;
				
				if ($err_status != 0) {
					$qry_bill_item = new Query("START TRANSACTION");
					
					//=====================
					// get the bill details
					//---------------------
					$qry_bill_item->Query("
						SELECT *
						FROM ".Monthalize('bill')."
						WHERE bill_id = ".$_SESSION['order_bill_id']
					);
					$bool_changed = $qry_bill_item->FieldByName('is_debit_bill') <> $_SESSION['order_invoice_is_debit'];
					
					//=====================
					// update bill details
					//---------------------
					$str="
						UPDATE ".Monthalize('bill')."
						SET 
							payment_type = '".$_SESSION['order_payment_type']."',
							discount = '".$_SESSION['order_discount']."',
							is_debit_bill = '".$_SESSION['order_invoice_is_debit']."',
							bill_status = '".$_SESSION['order_status']."',
							supply_date_time = '".date("Y-m-d H:i:s", strtotime($_SESSION['order_supply_date_time']))."',
							supply_place = '".$_SESSION['order_supply_place']."',
							customer_id = '".$_SESSION['order_client_id']."',
							CC_id = '".$_SESSION['order_client_id']."'
						WHERE bill_id = ".$_SESSION['order_bill_id'];
					$qry_bill_item->Query($str);

					//echo $str;


					//=====================
					// update order details
					//------------------------------
					// get the bill_id for the order
					//------------------------------
					$qry_bill_item->Query("
						SELECT *
						FROM ".Monthalize('bill')."
						WHERE bill_id = ".$_SESSION['order_bill_id']
					);
					$int_order_id = $qry_bill_item->FieldByName('module_record_id');
					
					$qry_bill_item->Query("
						UPDATE ".Monthalize('orders')."
						SET CC_id = ".$_SESSION['order_client_id'].",
							handling_charge = '".$_SESSION['order_handling']."',
							handling_is_percentage = '".$_SESSION['order_handling_percentage']."',
							courier_charge = '".$_SESSION['order_courier']."',
							courier_is_percentage = '".$_SESSION['order_courier_percentage']."',
							order_reference = '".addslashes($_SESSION['order_reference'])."',
							order_date = '".set_mysql_date($_SESSION['order_date'], '-')."',
							advance_paid = '".$_SESSION['order_advance']."',
							note = '".addslashes($_SESSION['order_note'])."',
							payment_type = ".$_SESSION['order_payment_type'].",
							is_debit_invoice = '".$_SESSION['order_invoice_is_debit']."'
						WHERE order_id = ".$int_order_id
					);
					
					if ($qry_bill_item->b_error == true) {
						$str_message = "error updating order";
						$bool_success = false;
					}
					
					$qry_product = new Query("SELECT * FROM stock_product LIMIT 1");
					
					//======================================================================
					// remove items from the table that may have been removed from the order
					//----------------------------------------------------------------------
					$qry_bill_item->Query("
						SELECT *
						FROM ".Monthalize('bill_items')."
						WHERE (bill_id = ".$_SESSION['order_bill_id'].")
					");
					
					$arr_existing = array();
					for ($i=0; $i<$qry_bill_item->RowCount(); $i++) {
						$arr_existing[] = $qry_bill_item->FieldByName('product_id');
						$qry_bill_item->Next();
					}
					
					$arr_updated = array();
					for ($i=0; $i<count($_SESSION['order_arr_items']); $i++) {
						$arr_updated[] = $_SESSION['order_arr_items'][$i][3];
					}
					
					$arr_deleted = array_diff($arr_existing, $arr_updated);
					
					foreach ($arr_deleted as $key=>$value) {
						$qry_bill_item->Query("
							SELECT *
							FROM ".Monthalize('bill_items')."
							WHERE (product_id = ".$value.")
								AND (bill_id = ".$_SESSION['order_bill_id'].")
						");
						if ($qry_bill_item->RowCount() > 0) {
							$flt_ordered = $qry_bill_item->FieldByName('quantity');
							
							$qry_bill_item->Query("
								UPDATE ".Monthalize('stock_storeroom_product')."
								SET stock_reserved = ROUND(stock_reserved - ".$flt_ordered.", 3)
								WHERE product_id = ".$value."
									AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
							");
						}
						
						$qry_bill_item->Query("
							DELETE FROM ".Monthalize('bill_items')."
							WHERE (product_id = ".$value.")
								AND (bill_id = ".$_SESSION['order_bill_id'].")
						");
						if ($qry_bill_item->b_error == true) {
							$bool_success = false;
							$str_message = 'error deleting removed product';
						}
					}
					
					//=============================
					//update the ordered bill items
					//-----------------------------
					for ($i=0; $i<count($_SESSION['order_arr_items']); $i++) {
						
						$qry_bill_item->Query("
							SELECT *
							FROM ".Monthalize('bill_items')."
							WHERE (bill_id = ".$_SESSION['order_bill_id'].")
								AND (product_id = ".$_SESSION['order_arr_items'][$i][3].")
						");
						if ($qry_bill_item->b_error == true) {
							$bool_success = false;
							$str_message = 'error selecting bill_items '.mysql_error();
						}
						//=================================================
						// this table field, ie "quantity", reflects the 
						// "delivered" quantity, not the "ordered" quantity
						//-------------------------------------------------
						$flt_ordered = $qry_bill_item->FieldByName('quantity');
						
						$qry_product->Query("
							SELECT *
							FROM stock_product
							WHERE (product_id = ".$_SESSION['order_arr_items'][$i][3].")
						");
						if ($qry_product->b_error == true) {
							$str_message = 'error selecting stock_product';
							$bool_success = false;
						}
						
						//========================================================
						// if the product was updated, update the quantity ordered
						//--------------------------------------------------------
						if ($qry_bill_item->RowCount() > 0) {

							if ($bool_changed) {

								if ($_SESSION['order_invoice_is_debit'] == 'N') {
									$qry_bill_item->Query("
										UPDATE ".Monthalize('stock_storeroom_product')."
										SET stock_reserved = ROUND(stock_reserved + ".$_SESSION['order_arr_items'][$i][1].", 3)
										WHERE product_id = ".$_SESSION['order_arr_items'][$i][3]."
											AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
									");
								}
								else {
									$qry_bill_item->Query("
										UPDATE ".Monthalize('stock_storeroom_product')."
										SET stock_reserved = ROUND(stock_reserved - ".$flt_ordered.", 3)
										WHERE product_id = ".$_SESSION['order_arr_items'][$i][3]."
											AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
									");
								}

							}
							else {

								if ($_SESSION['order_invoice_is_debit'] == 'N') {
									if ($flt_ordered >= $_SESSION['order_arr_items'][$i][1]) {
										$flt_quantity_updated = $flt_ordered - $_SESSION['order_arr_items'][$i][1];
										
										$qry_bill_item->Query("
											UPDATE ".Monthalize('stock_storeroom_product')."
											SET stock_reserved = ROUND(stock_reserved - ".$flt_quantity_updated.", 3)
											WHERE product_id = ".$_SESSION['order_arr_items'][$i][3]."
												AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
										");
									}
									else {
										$flt_quantity_updated = $_SESSION['order_arr_items'][$i][1] - $flt_ordered;
										
										$qry_bill_item->Query("
											UPDATE ".Monthalize('stock_storeroom_product')."
											SET stock_reserved = ROUND(stock_reserved + ".$flt_quantity_updated.", 3)
											WHERE product_id = ".$_SESSION['order_arr_items'][$i][3]."
												AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
										");
									}
								}
							}
							
							$qry_bill_item->Query("
								UPDATE ".Monthalize('order_items')."
								SET 
									quantity_delivered = ".$_SESSION['order_arr_items'][$i][1].", 
									price = '".$_SESSION['order_arr_items'][$i][4]."'
								WHERE (order_id = ".$int_order_id.")
									AND (product_id = ".$_SESSION['order_arr_items'][$i][3].")
								");
							if ($qry_bill_item->b_error == true) {
								$str_message = 'error updating order_items';
								$bool_success = false;
							}

							$qry_bill_item->Query("
								UPDATE ".Monthalize('bill_items')."
								SET 
									quantity = ".$_SESSION['order_arr_items'][$i][1].",
									price = '".$_SESSION['order_arr_items'][$i][4]."'
								WHERE (bill_id = ".$_SESSION['order_bill_id'].")
									AND (product_id = ".$_SESSION['order_arr_items'][$i][3].")
							");
							if ($qry_bill_item->b_error == true) {
								$str_message = 'error updating bill_items';
								$bool_success = false;
							}
						}
						//======================================================
						// if the product was added, insert the quantity ordered
						//------------------------------------------------------
						else {
							$qry_bill_item->Query("
								UPDATE ".Monthalize('stock_storeroom_product')."
								SET stock_reserved = stock_reserved + ".$_SESSION['order_arr_items'][$i][1]."
								WHERE product_id = ".$_SESSION['order_arr_items'][$i][3]."
									AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
							");
							
							$qry_bill_item->Query("
								INSERT INTO ".Monthalize('bill_items')."
								(
									quantity,
									quantity_ordered,
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
									".$_SESSION['order_arr_items'][$i][1].",
									".$_SESSION['order_arr_items'][$i][5].",
									0,
									".$_SESSION['order_arr_items'][$i][4].",
									".$qry_product->FieldByName('tax_id').",
									0,
									".$_SESSION['order_arr_items'][$i][3].",
									".$_SESSION['order_bill_id'].",
									0,
									0,
									'".addslashes($qry_product->FieldByName('product_description'))."'
								)
							");
							
							if ($qry_bill_item->b_error == true) {
								$bool_success = false;
								$str_message = 'error inserting bill_items';
							}
						}
					}
					
					if ($bool_success) {
						$qry_bill_item->Query("COMMIT");
						
						echo "<script language=\"javascript\">\n";
						echo "alert('Order saved successfully.');\n";
						//echo "top.document.location = 'order_edit_frameset.php?action=clear_order&order_bill_type=".$_SESSION['order_bill_type']."';\n";
						echo "setTimeout('top.window.close();',1000);\n";
						echo "</script>\n";
						
					}
					else {
						$qry_bill_item->Query("ROLLBACK");
						
						echo "<script language=\"javascript\">\n";
						echo "alert('".$str_message."');\n";
						echo "</script>\n";
					}
				}
				else {
					echo "<script language=\"javascript\">\n";
					echo "alert('".$str_message."');\n";
					echo "</script>\n";
				}
			}
		}
	}
?>

<script language="javascript">

	function SaveOrder() {
		parent.frames['frame_action'].document.location = "order_edit_action.php?action=save";
	}
	
	function CancelOrder(intOrderType) {
		if (confirm("Are you sure?"))
			top.document.location = "order_edit_frameset.php?action=clear_order&order_bill_type="+intOrderType;
	}
	
	function CloseWindow() {
		var oList = parent.frames["frame_list"].document.order_edit_list.item_list;
		if (oList.options.length > 0) {
			if (confirm('Items have been entered. \n Exit anyway?')) {
				top.window.opener.document.location=top.window.opener.document.location.href;
				top.window.close();
			}
		}
		else {
			top.window.opener.document.location=top.window.opener.document.location.href;
			top.window.close();
		}
	}

</script>

<html>
<head><TITLE></TITLE>
<head>
	<link rel="stylesheet" type="text/css" href="../../include/styles.css" />
</head>
<body bgcolor="lightgrey" leftmargin="10" topmargin="10" rightmargin="1" bottommargin="1">

	<input type="button" name='action' value='save' class='settings_button' onclick='javascript:SaveOrder()'>
	<input type='button' name='action' value='cancel' class='settings_button' onclick='javascript:CancelOrder(<?echo $_SESSION['order_bill_type']?>)'>
	<input type="button" name="action" value="close" class="settings_button" onclick="javascript:CloseWindow();">
</body>
</html>