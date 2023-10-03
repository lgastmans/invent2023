<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");

	// this array holds the total quantities of items billed
	('order_arr_items');

	// the string version of the array to pass to javascript
	('order_str_items');


	if (IsSet($_GET['action'])) {
		if ($_GET['action'] == 'clear_order') {
			// clear the session variables related to the billing
			unset($_SESSION["order_arr_items"]);
			$_SESSION['order_str_items'] = "";
		        $_SESSION["order_bill_id"] = -1;
			if (IsSet($_GET["order_bill_type"]))
				$_SESSION['order_bill_type'] = $_GET["order_bill_type"];
			else
				$_SESSION['order_bill_type'] = BILL_ACCOUNT;
			$_SESSION['order_type'] = 1;
			$_SESSION['order_account_number'] = "";
			$_SESSION['order_account_name'] = "";
			$_SESSION['order_day'] == ORDER_DAY_MONDAY;
			$_SESSION['order_week'] == ORDER_WEEK1;
			$_SESSION['order_month'] == 1;
			$_SESSION['order_bill_order'] = 'Y';
			$_SESSION['order_total_amount'] = 0;
			$_SESSION['order_community_id'] = -1;
			$_SESSION['order_note'] = '';
			
			// now refresh this page without the GET variable
			header('location:order_frameset.php');
			exit;
		}
	}

	if (IsSet($_GET['id'])) {
		$int_bill_id = $_GET['id'];
		
		$qry_bill = new Query("
			SELECT *
			FROM ".Monthalize('bill')."
			WHERE bill_id = $int_bill_id"
		);
		if (($qry_bill->FieldByName('bill_status') == BILL_STATUS_RESOLVED) || ($qry_bill->FieldByName('bill_status') == BILL_STATUS_CANCELLED))
			die('This order cannot be editted');
		
		$qry_order = new Query("
			SELECT *
			FROM ".Monthalize('orders')."
			WHERE order_id = ".$qry_bill->FieldByName('module_record_id')."
		");
		
		$qry_account = new Query("
			SELECT *
			FROM account_cc
			WHERE cc_id=".$qry_order->FieldByName('CC_id')."
		");
		
		$_SESSION['order_bill_id'] = $int_bill_id;
		$_SESSION['order_bill_type'] = $qry_order->FieldByName('payment_type');
		$_SESSION['order_type'] = $qry_order->FieldByName('order_type');
		$_SESSION['order_account_number'] = $qry_account->FieldByName('account_number');
		$_SESSION['order_account_name'] = $qry_account->FieldByName('account_name');
		$_SESSION['order_total_amount'] = $qry_order->FieldByName('total_amount');
		$_SESSION['order_day'] = $qry_order->FieldByName('day_of_week');
		$_SESSION['order_week'] = $qry_order->FieldByName('order_week');
		$_SESSION['order_month'] = $qry_order->FieldByName('order_month');
		$_SESSION['order_bill_order'] = $qry_order->FieldByName('is_billable');
		$_SESSION['order_community_id'] = $qry_order->FieldByName('community_id');
		$_SESSION['order_note'] = $qry_order->FieldByName('note');
		
		$qry_items = new Query("
			SELECT *
			FROM ".Monthalize('bill_items')." bi, stock_product sp
			INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			WHERE bi.bill_id = $int_bill_id
				AND (bi.product_id = sp.product_id)
			ORDER BY sp.product_description
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
                        $_SESSION["order_arr_items"][$i][4] = number_format($qry_items->FieldByName('price'),3,'.',''); // price
			if ($qry_items->FieldByName('is_decimal') == 'Y')
				$_SESSION["order_arr_items"][$i][5] = number_format($qry_items->FieldByName('quantity_ordered'),3,'.',''); // quantity
			else
				$_SESSION["order_arr_items"][$i][5] = number_format($qry_items->FieldByName('quantity_ordered'),0,'.',''); // quantity
			
			$qry_items->Next();
		}
	}

?>

<html>
<frameset rows='165,70,*,70' border=1 scrolling=no>
	<frame name='frame_details' src="order_edit_details.php" scrolling=no>
	<frame name='frame_enter' src="order_edit_enter.php" scrolling=auto>
	<frame name='frame_list' src="order_edit_list.php" scrolling=auto>
	<frame name='frame_action' src="order_edit_action.php" scrolling=auto>
</frameset>

</html>