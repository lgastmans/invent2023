<?
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");

	if (IsSet($_GET['del'])) {
		if ($_GET['del'] == 'Y') {
			$int_bill_id = $_GET['bill_id'];
			$int_order_id = $_GET['order_id'];
			
			//***
			// cancel the order bill
			//***
			require_once("../../billing/get_bill_number.php");
			require_once("../../common/product_cancel.php");
			require("../order_bill_cancel.php");
			
			$str_query = "
				SELECT *
				FROM ".Monthalize('bill')."
				WHERE bill_id = $int_bill_id
			";
			$qry_bill = new Query($str_query);
			$int_bill_status = $qry_bill->FieldByName('bill_status');
			
			//***
			// if the status is "Dispatched" then the stock needs to be updated
			//***
			if ($int_bill_status == BILL_STATUS_PROCESSING)
				$str_retval = cancelOrderBill($int_bill_id);
			else if ($int_bill_status == BILL_STATUS_DISPATCHED)
				$str_retval = cancelOrderBill($int_bill_id, 'Y');
			
			$arr_retval = explode('|', $str_retval);
			
			if ($arr_retval[0] == 'OK') {
				$bool_success = true;
				$str_message = '';
				
				$qry = new Query("START TRANSACTION");
				
				//***
				// delete the order bill
				//***
				$str_query = "
					DELETE FROM ".Monthalize('bill_items')."
					WHERE bill_id = $int_bill_id
				";
				$qry->Query($str_query);
				if ($qry->b_error == true) {
					$bool_success = false;
					$str_message = 'Error deleting bill items';
				}
				
				$str_query = "
					DELETE FROM ".Monthalize('bill')."
					WHERE bill_id = $int_bill_id
				";
				$qry->Query($str_query);
				if ($qry->b_error == true) {
					$bool_success = false;
					$str_message = 'Error deleting bill';
				}
				
				//***
				// set the order status back to pending
				//***
				$str_query = "
					UPDATE ".Monthalize('orders')."
					SET order_status = ".ORDER_STATUS_PENDING."
					WHERE order_id = $int_order_id
				";
				$qry->Query($str_query);
				if ($qry->b_error == true) {
					$bool_success = false;
					$str_message = 'Error deleting bill items';
				}
				
				if ($bool_success) {
					if ($int_bill_status == BILL_STATUS_DISPATCHED) {
						//***
						// decrement the bill number
						//***
						$str_query = "
							SELECT payment_type
							FROM ".Monthalize('orders')."
							WHERE order_id = $int_order_id
						";
						$qry->Query($str_query);
						
						get_bill_number($qry->FieldByName('payment_type'), 'N');
					}
					
					$qry->Query("COMMIT");
					header("Location:order_frameset.php?order_id=".$int_order_id);
				}
				else {
					$qry->Query("ROLLBACK");
					die($str_message);
				}
			}
			else {
				die($arr_retval[1]);
			}
		}
	}
	
	// this array holds the total quantities of items billed
//	('order_arr_items');

	// the string version of the array to pass to javascript
//	('order_str_items');


	if (IsSet($_GET['action'])) {
		if ($_GET['action'] == 'clear_order') {
			// clear the session variables related to the billing
			unset($_SESSION["order_arr_items"]);
			$_SESSION['order_str_items'] = "";
		        $_SESSION["order_id"] = -1;
			$_SESSION['order_client_id'] = 0;
			$_SESSION['order_reference'] = '';
			$_SESSION['order_date'] = date('d-m-Y', time());
			$_SESSION['order_handling'] =  0;
			$_SESSION['order_handling_percentage'] = 'Y';
			$_SESSION['order_courier'] = 0;
			$_SESSION['order_courier_percentage'] = 'Y';
			$_SESSION['order_advance'] = 0;
			$_SESSION['order_note'] = '';
			$_SESSION['order_payment_type'] = BILL_CASH;
			$_SESSION['order_discount'] = 0;
			$_SESSION['order_invoice_is_debit'] = 'N';
			$_SESSION['order_status'] = 0;
			
			// now refresh this page without the GET variable
			header('location:order_frameset.php');
			exit;
		}
	}

	if (IsSet($_GET['order_id'])) {
		
		$int_order_id = $_GET['order_id'];
		
		$qry_order = new Query("
			SELECT *
			FROM ".Monthalize('bill')."
			WHERE module_record_id = $int_order_id
		");
		if ($qry_order->RowCount() > 0) {
		
			$str_pending = 'N';
			if ($qry_order->FieldByName('is_pending') == 'Y')
				$str_pending = 'Y';
				
			?>
			<html>
				<head>
					<script language="javascript">
						function deleteOrderBill(intBillID,intOrderID) {
							if (confirm('Are you sure?')) {
								document.location = 'order_frameset.php?del=Y&bill_id='+intBillID+'&order_id='+intOrderID;
							}
						}
					</script>
				</head>
				<body>
				<table>
					<tr>
						<td>This order has a bill associated with it.</td>
					</tr>
					<tr>
						<TD>
						<?
							if ($str_pending == 'N') {
								echo "The bill has been completed and no further changes can be made";
							}
							else {
								echo "<input type='button' name='action' value='Delete bill and continue' onclick='javascript:deleteOrderBill(".$qry_order->FieldByName('bill_id').", ".$int_order_id.")'>";
							}
						?>
						</TD>
					</tr>
				</table>
				</body>
			</html>
			<?
		}
		else {
			$qry_order->Query("
				SELECT *
				FROM ".Monthalize('orders')."
				WHERE order_id=$int_order_id
			");
			
			$_SESSION['order_id'] = $int_order_id;
			$_SESSION['order_client_id'] = $qry_order->FieldByName('CC_id');
			$_SESSION['order_reference'] = $qry_order->FieldByName('order_reference');
			$_SESSION['order_date'] = set_formatted_date($qry_order->FieldByName('order_date'), '-');
			$_SESSION['order_handling'] = $qry_order->FieldByName('handling_charge');
			$_SESSION['order_handling_percentage'] = $qry_order->FieldByName('handling_is_percentage');
			$_SESSION['order_courier'] = $qry_order->FieldByName('courier_charge');
			$_SESSION['order_courier_percentage'] = $qry_order->FieldByName('courier_is_percentage');
			$_SESSION['order_advance'] = $qry_order->FieldByName('advance_paid');
			$_SESSION['order_note'] = $qry_order->FieldByName('note');
			$_SESSION['order_payment_type'] = $qry_order->FieldByName('payment_type');
			$_SESSION['order_discount'] = $qry_order->FieldByName('discount');
			$_SESSION['order_invoice_is_debit'] = $qry_order->FieldByName('is_debit_invoice');
			$_SESSION['order_status'] = $qry_order->FieldByName('order_status');
			
			$qry_order_items = new Query("
				SELECT *
				FROM ".Monthalize('order_items')." oi, stock_product sp
				INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				WHERE oi.order_id=$int_order_id
					AND (oi.product_id = sp.product_id)
				ORDER BY sp.product_code
			");
			unset($_SESSION["order_arr_items"]);
			for ($i=0;$i<$qry_order_items->RowCount();$i++) {
				$_SESSION["order_arr_items"][$i][0] = $qry_order_items->FieldByName('product_code'); //  product code
				if ($qry_order_items->FieldByName('is_decimal') == 'Y')
					$_SESSION["order_arr_items"][$i][1] = number_format($qry_order_items->FieldByName('quantity_ordered'),2,'.',''); // quantity
				else
					$_SESSION["order_arr_items"][$i][1] = number_format($qry_order_items->FieldByName('quantity_ordered'),0,'.',''); // quantity
				$_SESSION["order_arr_items"][$i][2] = $qry_order_items->FieldByName('product_description'); // product description
				$_SESSION["order_arr_items"][$i][3] = $qry_order_items->FieldByName('product_id'); // product_id
				$_SESSION["order_arr_items"][$i][4] = number_format($qry_order_items->FieldByName('price'),2,'.',''); // price
				
				$qry_order_items->Next();
			}
			?>
				<html>
				<frameset rows='180,70,*,70' border="0" scrolling=no>
					<frame name='frame_details' src="order_details.php" scrolling=no>
					<frame name='frame_enter' src="order_enter.php" scrolling=no>
					<frame name='frame_list' src="order_list.php" scrolling=no>
					<frame name='frame_action' src="order_action.php" scrolling=no>
				</frameset>
				</html>
			<?
		}
	}
	else {
		?>
			<html>
			<frameset rows='180,70,*,70' border="0" scrolling=no>
				<frame name='frame_details' src="order_details.php" scrolling=no>
				<frame name='frame_enter' src="order_enter.php" scrolling=no>
				<frame name='frame_list' src="order_list.php" scrolling=no>
				<frame name='frame_action' src="order_action.php" scrolling=no>
			</frameset>
			</html>
		<?
	}
?>

