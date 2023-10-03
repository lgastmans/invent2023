<?
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");
	require_once("../../orders/order_functions.inc.php");

	error_reporting(E_ERROR);

	if (IsSet($_GET['action'])) {
		if ($_GET['action'] == 'save') {

			//================================
			//===================== EDIT ORDER
			//================================
			if ($_SESSION['order_id'] > -1) {
                                $err_status = 1;
				
/*				if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
					$str_message = '';
				}
				else {
					$str_message = 'Cannot edit orders in previous months. \\n Select the current month/year and continue.';
					$err_status = 0;
				}
*/				
				if ($err_status != 0) {
					$result_set = new Query("BEGIN");
					$bool_success = true;
					
					$str_order = "
						UPDATE ".Monthalize('orders')."
						SET	CC_id = ".$_SESSION['order_client_id'].",
							order_type = ".ORDER_TYPE_DAILY.",
							total_amount = ".$_SESSION['order_total_amount'].",
							payment_type = ".$_SESSION['order_payment_type'].",
							discount = ".intval($_SESSION['order_discount']).",
							storeroom_id = ".$_SESSION['int_current_storeroom'].",
							user_id = ".$_SESSION['int_user_id'].",
							order_reference = '".$_SESSION['order_reference']."',
							order_date = '".set_mysql_date($_SESSION['order_date'], '-')."',
							handling_charge = ".$_SESSION['order_handling'].",
							handling_is_percentage = '".$_SESSION['order_handling_percentage']."',
							courier_charge = ".$_SESSION['order_courier'].",
							courier_is_percentage = '".$_SESSION['order_courier_percentage']."',
							advance_paid = ".$_SESSION['order_advance'].",
							is_debit_invoice = '".$_SESSION['order_invoice_is_debit']."',
							order_status = ".$_SESSION['order_status'].",
							note = '".addslashes($_SESSION['order_note'])."',
							is_modified = 'Y'
						WHERE order_id=".$_SESSION['order_id']."
						LIMIT 1";
					
					$result_set->Query($str_order);
					if ($result_set->b_error == true) {
						$bool_success = false;
						$str_message = "Error updating orders ";
					}
					
					// clear the items related to this order
					$str_clear_items = "
						DELETE FROM ".Monthalize('order_items')."
						WHERE order_id = ".$_SESSION['order_id'];
					$result_set->Query($str_clear_items);
					if ($result_set->b_error == true) {
						$bool_success = false;
						$str_message = "Error clearing the order items";
					}
					
					// save the items
					for ($i=0;$i<count($_SESSION['order_arr_items']);$i++) {
						$str_item = "
							INSERT INTO ".Monthalize('order_items')."
							(
								order_id,
								quantity_ordered,
								quantity_delivered,
								price,
								is_temporary,
								adjusted,
								product_id
							)
							VALUES (
								".$_SESSION['order_id'].", ".
								$_SESSION['order_arr_items'][$i][1].", ".
								$_SESSION['order_arr_items'][$i][1].", ".
								$_SESSION['order_arr_items'][$i][4].", ".
								"'N',
								0,".
								$_SESSION['order_arr_items'][$i][3]."
							)
						";
						$result_set->Query($str_item);
						if ($result_set->b_error == true) {
							$bool_success = false;
							$str_message = "Error inserting into order_items";
						}
					}
					
					if ($bool_success) {
						$result_set->Query("COMMIT");
			
						echo "<script language=\"javascript\">\n";
						echo "alert('Order saved successfully.');\n";
						echo "top.window.close();\n";
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
			else {
				//================================
				//===================== NEW ORDER
				//================================
				$err_status = 1;
				
/*				if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
					$str_message = '';
				}
				else {
					$str_message = 'Cannot edit orders in previous months. \\n Select the current month/year and continue.';
					$err_status = 0;
				}
*/				
				if ($err_status != 0) {
					
					$result_set = new Query("BEGIN");
					$bool_success = true;
					
					$str_order = "
						INSERT INTO ".Monthalize('orders')."
						(
							CC_id,
							order_type,
							total_amount,
							payment_type,
							discount,
							order_status,
							storeroom_id,
							user_id,
							order_reference,
							order_date,
							handling_charge,
							handling_is_percentage,
							courier_charge,
							courier_is_percentage,
							advance_paid,
							is_debit_invoice,
							note
						)
						VALUES (".
							$_SESSION['order_client_id'].", ".
							ORDER_TYPE_DAILY.", ".
							$_SESSION['order_total_amount'].", ".
							$_SESSION['order_payment_type'].", ".
							intval($_SESSION['order_discount']).", ".
							$_SESSION['order_status'].", ".
							$_SESSION['int_current_storeroom'].", ".
							$_SESSION['int_user_id'].", ".
							"'".$_SESSION['order_reference']."', ".
							"'".set_mysql_date($_SESSION['order_date'], '-')."', ".
							$_SESSION['order_handling'].", ".
							"'".$_SESSION['order_handling_percentage']."', ".
							$_SESSION['order_courier'].", ".
							"'".$_SESSION['order_courier_percentage']."', ".
							$_SESSION['order_advance'].", ".
							"'".$_SESSION['order_invoice_is_debit']."', ".
							"'".addslashes($_SESSION['order_note'])."'
						)
					";
					
					$result_set->Query($str_order);
					if ($result_set->b_error == true) {
						$bool_success = false;
						$str_message = "Error inserting into orders".$str_order;
					}
					
					$int_order_id = $result_set->getInsertedID();
					
					for ($i=0;$i<count($_SESSION['order_arr_items']);$i++) {
						$str_item = "
							INSERT INTO ".Monthalize('order_items')."
							(
								order_id,
								quantity_ordered,
								quantity_delivered,
								price,
								is_temporary,
								adjusted,
								product_id
							)
							VALUES (
								".$int_order_id.", ".
								$_SESSION['order_arr_items'][$i][1].", ".
								$_SESSION['order_arr_items'][$i][1].", ".
								$_SESSION['order_arr_items'][$i][4].", ".
								"'N',
								0,".
								$_SESSION['order_arr_items'][$i][3]."
							)
						";
						$result_set->Query($str_item);
						if ($result_set->b_error == true) {
							$bool_success = false;
							$str_message = "Error inserting into order_items";
						}
					}
					
					if ($bool_success) {
						$result_set->Query("COMMIT");
						
						echo "<script language=\"javascript\">\n";
						echo "alert('Order saved successfully.');\n";
						echo "top.window.close();\n";
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
		parent.frames['frame_action'].document.location = "order_action.php?action=save";
	}
	
	function CancelOrder(intOrderType) {
		if (confirm("Are you sure?"))
			top.document.location = "order_frameset.php?action=clear_order&order_bill_type="+intOrderType;
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
	<link rel="stylesheet" type="text/css" href="../../include/styles.css" />
</head>
<body bgcolor="lightgrey" leftmargin="10" topmargin="10" rightmargin="1" bottommargin="1">

	<input type="button" name='action' value='save' class='settings_button' onclick='javascript:SaveOrder()'>
	<input type='button' name='action' value='cancel' class='settings_button' onclick='javascript:CancelOrder(<?echo $_SESSION['order_bill_type']?>)'>
	<input type="button" name="action" value="close" class="settings_button" onclick="javascript:CloseWindow();">
</body>
</html>