<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");

	function getMySQLDate($str_date) {
		if ($str_date == '')
			$str_date = date('d-m-Y');
		$arr_date = explode('-', $str_date);
		return sprintf("%04d-%02d-%02d", $arr_date[2], $arr_date[1], $arr_date[0]);
	}

	function set_order_sessions($purchase_order_ref, $purchase_date_expected, $purchase_supplier_id, $purchase_assigned_to, $single_supplier, $invoice_number, $invoice_date) {
		$_SESSION['purchase_order_ref'] = $purchase_order_ref;
		$_SESSION['purchase_date_expected'] = getMySQLDate($purchase_date_expected);
		$_SESSION['purchase_supplier_id'] = $purchase_supplier_id;
		$_SESSION['purchase_assigned_to'] = $purchase_assigned_to;
		$_SESSION['purchase_single_supplier'] = $single_supplier;
		$_SESSION['purchase_invoice_number'] = $invoice_number;
		$_SESSION['purchase_invoice_date'] = getMySQLDate($invoice_date);
			
		return 'saved';
	}
	
	if (!empty($_GET['live'])) {

		if ($_GET['live'] == 1) {

			echo set_order_sessions(
				$_GET['order_reference'],
				$_GET['order_date_expected'],
				$_GET['order_supplier'],
				$_GET['order_assigned_to'],
				$_GET['order_single_supplier'],
				$_GET['order_invoice_number'],
				$_GET['order_invoice_date']
			);

			die();
		}
		else
			die('error saving purchase order sessions');
	}
?>