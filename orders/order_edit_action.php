<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/account.php");
//	require_once("order_functions.inc.php");

	error_reporting(E_ERROR);

	if (IsSet($_GET['action'])) {
		if ($_GET['action'] == 'save') {

//================================
//===================== EDIT ORDER
//================================
			if ($_SESSION['order_bill_id'] > -1) {
				// edit an existing order
				// IF ACCOUNT ORDER, CHECK THE ACCOUNT NUMBER
				$err_status = 1;
				$int_current_CCID = -1;
				
				if ($_SESSION['order_bill_type'] == 2) {
					if (IsSet($_POST['cb_check_online'])) {
						$str_retval = getAccountName($_SESSION['order_account_number'], ACCOUNT_METHOD);
						$arr_retval = explode("|", $str_retval);
						if ($arr_retval[0] != 'OK') {
							$str_message = $arr_retval[0];
							$err_status = 0; //false
						}
						else {
							$int_current_CCID = getAccountCCID($_SESSION['order_account_number'], ACCOUNT_METHOD);
							
							if ($int_current_CCID == -1) {
								$str_message = 'Cannot retrieve the account id';
								$err_status = 0;
							}
						}
					}
					else {
						$qry_account = new Query("
							SELECT cc_id
							FROM account_cc
							WHERE account_number = '".$_SESSION['order_account_number']."'
						");
						if ($qry_account->RowCount() > 0)
							$int_current_CCID = $qry_account->FieldByName('cc_id');
						else {
							$str_message = 'Cannot retrieve the account id offline';
							$err_status = 0;
						}
					}
				}
				
				if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
					$str_message = '';
				}
				else {
					$str_message = 'Cannot edit orders in previous months. \\n Select the current month/year and continue.';
					$err_status = 0;
				}
				
				if ($err_status != 0) {
					$result_set = new Query("START TRANSACTION");
					$bool_success = true;
					
					//=====================
					// update order details
					//---------------------
					$result_set->Query("
						SELECT *
						FROM ".Monthalize('bill')."
						WHERE bill_id = ".$_SESSION['order_bill_id']
					);
					$int_order_id = $result_set->FieldByName('module_record_id');
					
					$str_order = "
						UPDATE ".Monthalize('orders')."
						SET	CC_id = ".$int_current_CCID.",
							community_id = '".$_SESSION['order_community_id']."',
							note = '".addslashes($_SESSION['order_note'])."',
							order_type = ".$_SESSION['order_type'].",
							day_of_week = ".$_SESSION['order_day'].",
							order_month = ".$_SESSION['order_month'].",
							order_week = ".$_SESSION['order_week'].",
							total_amount = ".$_SESSION['order_total_amount'].",
							payment_type = ".$_SESSION['order_bill_type'].",
							is_billable = '".$_SESSION['order_bill_order']."',
							storeroom_id = ".$_SESSION['int_current_storeroom'].",
							user_id = ".$_SESSION['int_user_id']."
						WHERE order_id=".$int_order_id."
						LIMIT 1";
						
					$result_set->Query($str_order);
					
					if ($result_set->b_error == true) {
						$bool_success = false;
						$str_message = "Error updating orders".$str_order;
					}
					
					$qry_product = new Query("SELECT * FROM stock_product LIMIT 1");
					
					//======================================================================
					// remove items from the table that may have been removed from the order
					//----------------------------------------------------------------------
					$result_set->Query("
						SELECT *
						FROM ".Monthalize('bill_items')."
						WHERE (bill_id = ".$_SESSION['order_bill_id'].")
					");
					
					$arr_existing = array();
					for ($i=0; $i<$result_set->RowCount(); $i++) {
						$arr_existing[] = $result_set->FieldByName('product_id');
						$result_set->Next();
					}
					
					$arr_updated = array();
					for ($i=0; $i<count($_SESSION['order_arr_items']); $i++) {
						$arr_updated[] = $_SESSION['order_arr_items'][$i][3];
					}
					
					$arr_deleted = array_diff($arr_existing, $arr_updated);
					
					foreach ($arr_deleted as $key=>$value) {
						$result_set->Query("
							SELECT *
							FROM ".Monthalize('bill_items')."
							WHERE (product_id = ".$value.")
								AND (bill_id = ".$_SESSION['order_bill_id'].")
						");
						if ($result_set->RowCount() > 0) {
							$flt_ordered = $result_set->FieldByName('quantity');
							
							$result_set->Query("
								UPDATE ".Monthalize('stock_storeroom_product')."
								SET stock_reserved = ROUND(stock_reserved - ".$flt_ordered.", 3)
								WHERE product_id = ".$value."
									AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
							");
						}
						
						$result_set->Query("
							DELETE FROM ".Monthalize('bill_items')."
							WHERE (product_id = ".$value.")
								AND (bill_id = ".$_SESSION['order_bill_id'].")
						");
						if ($result_set->b_error == true) {
							$bool_success = false;
							$str_message = 'error deleting removed product';
						}
					}
					
					//=============================
					//update the ordered bill items
					//-----------------------------
					for ($i=0; $i<count($_SESSION['order_arr_items']); $i++) {
						
						$result_set->Query("
							SELECT *
							FROM ".Monthalize('bill_items')."
							WHERE (bill_id = ".$_SESSION['order_bill_id'].")
								AND (product_id = ".$_SESSION['order_arr_items'][$i][3].")
						");
						if ($result_set->b_error == true) {
							$bool_success = false;
							$str_message = 'error selecting bill_items '.mysql_error();
						}
						$flt_ordered = $result_set->FieldByName('quantity');
						
						$qry_product->Query("
							SELECT *
							FROM stock_product
							WHERE (product_id = ".$_SESSION['order_arr_items'][$i][3].")
						");
						if ($qry_product->b_error == true) {
							$str_message = 'error selecting stock_product';
							$bool_success = false;
						}
						
						if ($result_set->RowCount() > 0) {
							if ($flt_ordered >= $_SESSION['order_arr_items'][$i][1]) {
								$flt_quantity_updated = $flt_ordered - $_SESSION['order_arr_items'][$i][1];
								
								$result_set->Query("
									UPDATE ".Monthalize('stock_storeroom_product')."
									SET stock_reserved = ROUND(stock_reserved - ".$flt_quantity_updated.", 3)
									WHERE product_id = ".$_SESSION['order_arr_items'][$i][3]."
										AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
								");
							}
							else {
								$flt_quantity_updated = $_SESSION['order_arr_items'][$i][1] - $flt_ordered;
								
								$result_set->Query("
									UPDATE ".Monthalize('stock_storeroom_product')."
									SET stock_reserved = ROUND(stock_reserved + ".$flt_quantity_updated.", 3)
									WHERE product_id = ".$_SESSION['order_arr_items'][$i][3]."
										AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
								");
							}
							
							$result_set->Query("
								UPDATE ".Monthalize('bill_items')."
								SET quantity = ".$_SESSION['order_arr_items'][$i][1]."
								WHERE (bill_id = ".$_SESSION['order_bill_id'].")
									AND (product_id = ".$_SESSION['order_arr_items'][$i][3].")
							");
							if ($result_set->b_error == true) {
								$str_message = 'error updating bill_items';
								$bool_success = false;
							}
						}
						else {
							$result_set->Query("
								UPDATE ".Monthalize('stock_storeroom_product')."
								SET stock_reserved = stock_reserved + ".$_SESSION['order_arr_items'][$i][1]."
								WHERE product_id = ".$_SESSION['order_arr_items'][$i][3]."
									AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
							");
							
							$result_set->Query("
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
									0,
									0,
									".$_SESSION['order_arr_items'][$i][3].",
									".$_SESSION['order_bill_id'].",
									0,
									0,
									'".addslashes($qry_product->FieldByName('product_description'))."'
								)
							");
							
							if ($result_set->b_error == true) {
								$bool_success = false;
								$str_message = 'error inserting bill_items';
							}
						}
					}
					
					if ($bool_success) {
						$result_set->Query("COMMIT");
						
						echo "<script language=\"javascript\">\n";
						echo "alert('Order saved successfully.');\n";
						echo "top.document.location = 'edit_order_bill_frameset.php';\n";
//						echo "top.document.location = 'order_edit_frameset.php?action=clear_order&order_bill_type=".$_SESSION['order_bill_type']."';\n";
						echo "</script>\n";
						
					}
					else {
						$result_set->Query("ROLLBACK");
						
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
		var oList = parent.frames["frame_list"].document.order_list.item_list;
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
	<link rel="stylesheet" type="text/css" href="../include/<?echo $str_css_filename;?>" />
</head>
<body leftmargin=5 topmargin=5 marginwidth=5 marginheight=5>
<form name='order_action' method='POST'>
	<input type="button" name='action' value='save' class='v3button' onclick='javascript:SaveOrder()'>
	<input type='button' name='action' value='cancel' class='v3button' onclick='javascript:CancelOrder(<?echo $_SESSION['order_bill_type']?>)'>
	<input type="button" name="action" value="close" class="v3button" onclick="javascript:CloseWindow();">
	&nbsp;&nbsp;
	<input type='checkbox' name='cb_check_online'>Verify account number online when saving.
</form>
</body>
</html>