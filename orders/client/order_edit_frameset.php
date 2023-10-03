<?
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");

	// this array holds the total quantities of items billed
//	('order_arr_items');

	// the string version of the array to pass to javascript
//	('order_str_items');


	if (IsSet($_GET['action'])) {
		if ($_GET['action'] == 'clear_order') {
			// clear the session variables related to the billing
			unset($_SESSION["order_arr_items"]);
			$_SESSION['order_str_items'] = "";
		        $_SESSION["order_bill_id"] = -1;
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
			$_SESSION['order_status'] = 4;
			$_SESSION['order_supply_date_time'] = null;
			$_SESSION['order_supply_place'] = '';

			
			// now refresh this page without the GET variable
			header('location:order_edit_frameset.php');
			exit;
		}
	}

	if (IsSet($_GET['id'])) {
		$int_id = $_GET['id'];
		
		$qry_bill = new Query("
			SELECT *
			FROM ".Monthalize('bill')."
			WHERE bill_id = $int_id"
		);
		if (($qry_bill->FieldByName('bill_status') == BILL_STATUS_RESOLVED) ||
			($qry_bill->FieldByName('bill_status') == BILL_STATUS_CANCELLED) ||
			($qry_bill->FieldByName('bill_status') == BILL_STATUS_DELIVERED))
			die('This order cannot be editted');
		
		$qry_order = new Query("
			SELECT *
			FROM ".Monthalize('orders')."
			WHERE order_id = ".$qry_bill->FieldByName('module_record_id')."
		");
		
		$_SESSION['order_bill_id'] = $int_id;
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
		$_SESSION['order_discount'] = $qry_bill->FieldByName('discount');
		$_SESSION['order_invoice_is_debit'] = $qry_bill->FieldByName('is_debit_bill');
		$_SESSION['order_status'] = $qry_bill->FieldByName('bill_status');
		$_SESSION['order_supply_date_time'] = date("d-m-Y H:i:s", strtotime($qry_bill->FieldByName('supply_date_time')));
		$_SESSION['order_supply_place'] = $qry_bill->FieldByName('supply_place');
		
		$qry_items = new Query("
			SELECT *
			FROM ".Monthalize('bill_items')." bi, stock_product sp
			INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			WHERE bi.bill_id = $int_id
				AND (bi.product_id = sp.product_id)
			ORDER BY sp.product_code
		");
		
		unset($_SESSION["order_arr_items"]);
		for ($i=0;$i<$qry_items->RowCount();$i++) {
			$_SESSION["order_arr_items"][$i][0] = $qry_items->FieldByName('product_code'); //  product code
			if ($qry_items->FieldByName('is_decimal') == 'Y')
				$_SESSION["order_arr_items"][$i][1] = number_format($qry_items->FieldByName('quantity'),3,'.',''); // quantity
                        else
				$_SESSION["order_arr_items"][$i][1] = number_format($qry_items->FieldByName('quantity'),0,'.',''); // quantity
			$_SESSION["order_arr_items"][$i][2] = $qry_items->FieldByName('product_description'); // product description
			$_SESSION["order_arr_items"][$i][3] = $qry_items->FieldByName('product_id'); // product_id
                        $_SESSION["order_arr_items"][$i][4] = number_format($qry_items->FieldByName('price'),2,'.',''); // price
			if ($qry_items->FieldByName('is_decimal') == 'Y')
				$_SESSION["order_arr_items"][$i][5] = number_format($qry_items->FieldByName('quantity_ordered'),3,'.',''); // quantity
			else
				$_SESSION["order_arr_items"][$i][5] = number_format($qry_items->FieldByName('quantity_ordered'),0,'.',''); // quantity
			$qry_items->Next();
		}
	}

?>

<html>
<frameset rows="200,70,*,70" border="0" scrolling="no">
	<frame name="frame_details" src="order_edit_details.php" scrolling="no">
	<frame name="frame_enter" src="order_edit_enter.php" scrolling="no">
	<frame name="frame_list" src="order_edit_list.php" scrolling="no">
	<frame name="frame_action" src="order_edit_action.php" scrolling="no">
</frameset>

</html>