<?php 
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
			if ($_SESSION['order_id'] > -1) {
				
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
								$str_message = 'Cannot retrieve the account id online';
								$err_status = 0;
							}
						}
					}
					else {
						$qry_account = new Query("
							SELECT cc_id, account_number, account_name
							FROM account_cc
							WHERE account_number = '".$_SESSION['order_account_number']."'
								AND account_active = 'Y'
						");
						if ($qry_account->RowCount() > 0) {
							$int_current_CCID = $qry_account->FieldByName('cc_id');
							$current_account_name = $qry_account->FieldByName('account_name');
							$current_account_number = $qry_account->FieldByName('account_number');
						}
						else {
							$str_message = 'Cannot retrieve the account id offline';
							$err_status = 0;
						}
					}
				}
				/*
				if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
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
					
					/*
					 *		CC_id = ".$int_current_CCID.",
					 * 		account_name = '".$current_account_name."',
					 *		account_number = '".$current_account_number."',
					*/
					$str_order = "
						UPDATE ".Monthalize('orders')."
						SET	community_id = '".$_SESSION['order_community_id']."',
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
						WHERE order_id=".$_SESSION['order_id']."
						LIMIT 1";
					$result_set->Query($str_order);
					if ($result_set->b_error == true) {
						$bool_success = false;
						$str_message = "Error updating orders".$str_order;
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
						echo "top.document.location = 'order_frameset.php?action=clear_order&order_bill_type=".$_SESSION['order_bill_type']."';\n";
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
//================================
//===================== NEW ORDER
//================================
			else {
				// add a new order
				// IF ACCOUNT ORDER, CHECK THE ACCOUNT NUMBER
                                $err_status = 1;
				$int_current_CCID = -1;
				
				if ($_SESSION['order_bill_type'] == 2) {
					if (IsSet($_POST['cb_check_online'])) {
						$str_retval = getAccountName($_SESSION['order_account_number'], ACCOUNT_METHOD);
						$arr_retval = explode("|", $str_retval);
						if ($arr_retval[0] != 'OK') {
							$str_message = "Account ".$arr_retval[0];
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
							SELECT cc_id, account_name, account_number
							FROM account_cc
							WHERE account_number = '".$_SESSION['order_account_number']."'
								AND account_active = 'Y'
						");
						if ($qry_account->RowCount() > 0) {
							$int_current_CCID = $qry_account->FieldByName('cc_id');
							$current_account_name = $qry_account->FieldByName('account_name');
							$current_account_number = $qry_account->FieldByName('account_number');
						}
						else {
							$str_message = 'Cannot retrieve the account id offline';
							$err_status = 0;
						}
					}
				}
				/*
				if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
					$str_message .= '';
				}
				else {
					$str_message .= 'Cannot edit orders in previous months. \\n Select the current month/year and continue.';
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
							account_name,
							account_number,
							community_id,
							note,
							order_type,
							day_of_week,
							order_month,
							order_week,
							total_amount,
							payment_type,
							is_billable,
							order_status,
							storeroom_id,
							user_id
						)
						VALUES (".
							$int_current_CCID.", '".
							$current_account_name."', '".
							$current_account_number."', '".
							$_SESSION['order_community_id']."', '".
							addslashes($_SESSION['order_note'])."', ".
							$_SESSION['order_type'].", ".
							$_SESSION['order_day'].", ".
							$_SESSION['order_month'].", ".
							$_SESSION['order_week'].", ".
							$_SESSION['order_total_amount'].", ".
							$_SESSION['order_bill_type'].", '".
							$_SESSION['order_bill_order']."', ".
							ORDER_STATUS_ACTIVE.", ".
							$_SESSION['int_current_storeroom'].", ".
							$_SESSION['int_user_id']."
						)
					";
					$result_set->Query($str_order);
					if ($result_set->b_error == true) {
						$bool_success = false;
						$str_message = "Error inserting into orders";
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
						echo "top.document.location = 'order_frameset.php?action=clear_order&order_bill_type=".$_SESSION['order_bill_type']."';\n";
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
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
</head>
<body id='body_bgcolor' leftmargin=35 topmargin=5>
<form name='order_action' method='POST'>
	<input type="button" name='action' value='save' class='settings_button' onclick='javascript:SaveOrder()'>
	<input type='button' name='action' value='cancel' class='settings_button' onclick='javascript:CancelOrder(<?echo $_SESSION['order_bill_type']?>)'>
	<input type="button" name="action" value="close" class="settings_button" onclick="javascript:CloseWindow();">
	&nbsp;&nbsp;
	<input type='checkbox' name='cb_check_online'><font class='normaltext'>Verify account number online when saving.</font>
</form>
</body>
</html>