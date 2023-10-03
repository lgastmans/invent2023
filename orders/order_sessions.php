<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");

	function set_order_sessions($order_bill_type, $order_type, $order_day, $order_week, $order_month, $order_bill_order, $order_account_number, $order_community_id, $order_note) {
		$_SESSION['order_bill_type'] = $order_bill_type;
		$_SESSION['order_type'] = $order_type;
		$_SESSION['order_day'] = $order_day;
		$_SESSION['order_week'] = $order_week;
		$_SESSION['order_month'] = $order_month;
		$_SESSION['order_bill_order'] = $order_bill_order;
		$_SESSION['order_account_number'] = $order_account_number;
		$_SESSION['order_account_name'] = "";
		$_SESSION['order_community_id'] = $order_community_id;
		$_SESSION['order_note'] = $order_note;
			
		return 'saved';
	}
	
	if (!empty($_GET['live'])) {
		if ($_GET['live'] == 1) {
			echo set_order_sessions(
				$_GET['order_bill_type'],
				$_GET['order_type'],
				$_GET['order_day'],
				$_GET['order_week'],
				$_GET['order_month'],
				$_GET['order_bill_order'],
				$_GET['order_account_number'],
				$_GET['order_community_id'],
				$_GET['order_note']
			);
		}
		else
			die('error saving order sessions');
	}
?>