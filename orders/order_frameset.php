<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");

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

	if (IsSet($_GET['order_id'])) {
		$int_order_id = $_GET['order_id'];
		
		$qry_order = new Query("
			SELECT *
			FROM ".Monthalize('orders')."
			WHERE order_id=$int_order_id
		");
		$qry_account = new Query("
			SELECT *
			FROM account_cc
			WHERE cc_id=".$qry_order->FieldByName('CC_id')."
		");
		
		$_SESSION['order_id'] = $int_order_id;
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
		
		$qry_order_items = new Query("
			SELECT *
			FROM ".Monthalize('order_items')." oi, stock_product sp
			WHERE oi.order_id=$int_order_id
				AND (oi.product_id = sp.product_id)
			ORDER BY sp.product_description
		");
		unset($_SESSION["order_arr_items"]);
		for ($i=0;$i<$qry_order_items->RowCount();$i++) {
			$qry_product = new Query("SELECT * FROM stock_product WHERE product_id=".$qry_order_items->FieldByName('product_id'));
			
			$_SESSION["order_arr_items"][$i][0] = $qry_product->FieldByName('product_code'); //  product code
			$_SESSION["order_arr_items"][$i][1] = number_format($qry_order_items->FieldByName('quantity_ordered'),3,'.',''); // quantity
			$_SESSION["order_arr_items"][$i][2] = $qry_product->FieldByName('product_description'); // product description
			$_SESSION["order_arr_items"][$i][3] = $qry_order_items->FieldByName('product_id'); // product_id
                        $_SESSION["order_arr_items"][$i][4] = number_format($qry_order_items->FieldByName('price'),3,'.',''); // price
			
			$qry_order_items->Next();
		}
	}

?>

<html>
<frameset rows='165,75,*,70' border=0 scrolling=no>
	<frame name='frame_details' src="order_details.php" scrolling=no>
	<frame name='frame_enter' src="order_enter.php" scrolling=no>
	<frame name='frame_list' src="order_list.php" scrolling=no>
	<frame name='frame_action' src="order_action.php" scrolling=no>
</frameset>

</html>