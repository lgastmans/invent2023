<?
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");

	function set_order_sessions($order_client_id, $order_reference, $order_date, $order_handling, $order_advance, $order_note, $order_payment_type, $order_discount, $order_invoice_is_debit, $order_status, $order_handling_percentage, $order_courier, $order_courier_percentage, $order_supply_date_time, $order_supply_place) {
		$_SESSION['order_client_id'] = $order_client_id;
		$_SESSION['order_reference'] = $order_reference;
		$_SESSION['order_date'] = $order_date;
		$_SESSION['order_handling'] = $order_handling;
		$_SESSION['order_advance'] = $order_advance;
		$_SESSION['order_note'] = $order_note;
		$_SESSION['order_payment_type'] = $order_payment_type;
		$_SESSION['order_discount'] = $order_discount;
		$_SESSION['order_invoice_is_debit'] = $order_invoice_is_debit;
		$_SESSION['order_status'] = $order_status;
		$_SESSION['order_handling_percentage'] = $order_handling_percentage;
		$_SESSION['order_courier'] = $order_courier;
		$_SESSION['order_courier_percentage'] = $order_courier_percentage;
		$_SESSION['order_supply_date_time'] = $order_supply_date_time;
		$_SESSION['order_supply_place'] = $order_supply_place;
		
		return 'saved';
	}
	
	if (!empty($_GET['live'])) {
		if ($_GET['live'] == 1) {
			echo set_order_sessions(
				$_GET['order_client_id'],
				$_GET['order_reference'],
				$_GET['order_date'],
				$_GET['order_handling'],
				$_GET['order_advance'],
				$_GET['order_note'],
				$_GET['order_payment_type'],
				$_GET['order_discount'],
				$_GET['order_invoice_is_debit'],
				$_GET['order_status'],
				$_GET['order_handling_percentage'],
				$_GET['order_courier'],
				$_GET['order_courier_percentage'],
				$_GET['order_supply_date_time'],
				$_GET['order_supply_place']
			);
			die();
		}
		else
			die('error saving order sessions');
	}
?>