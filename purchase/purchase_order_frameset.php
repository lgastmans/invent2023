<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../include/purchase_funcs.inc.php");

//	error_reporting(E_ERROR);

	// this array holds the total quantities of items billed, as one item can be billed across multiple batches
	// it serves the purpose of checking whether the total quantity bought is equal to, or greater than, the
	// discount_qty in the stock_storeroom_product table, in which case the discount_percent should be suggested
//	('purchase_order_arr_items');


	if (IsSet($_GET['action'])) {

		if ($_GET['action'] == 'clear') {

			// clear the session variables related to the billing
			unset($_SESSION['purchase_order_arr_items']);
			$_SESSION['purchase_order_id'] = -1;
			$_SESSION['purchase_comment'] = '';
			$_SESSION['purchase_status'] = PURCHASE_DRAFT;
			$_SESSION['purchase_date_created'] = '';
			$_SESSION['purchase_date_received'] = '';
			$_SESSION['purchase_order_ref'] = '';
			$_SESSION['purchase_user_id'] = $_SESSION['int_user_id'];
			$_SESSION['purchase_date_expected'] = '';
			$_SESSION['purchase_assigned_to'] = $_SESSION['int_user_id'];
			$_SESSION['purchase_discount'] = 0;
			$_SESSION['invoice_number'] = 0;
			$_SESSION['invoice_date'] = '';

			if (!isset($_SESSION['purchase_supplier_id']))
				$_SESSION['purchase_supplier_id'] = '';
			
			if (!isset($_SESSION['purchase_single_supplier']))
				$_SESSION['purchase_single_supplier'] = 'Y';
			
			// now refresh this page without the GET variable
			header('location:purchase_order_frameset.php');
			exit;
		}
	}
	
	if (IsSet($_GET['id'])) {
		$str_retval = load_purchase_order_details($_GET['id']);
//		$arr_retval = explode('|', $str_retval);
		
		if ($str_retval[0] == 'FALSE') {
			header('location:purchase_order_frameset.php?action=clear_bill');
		}
	}
?>

<html>
	<frameset rows="110,*,70,70" border="0" scrolling="no">

	<frame name='frame_header' src="purchase_order_header.php" scrolling="no"></frame>
	<frame name='frame_enter' src="purchase_order_enter.php" scrolling="no"></frame>
	<frame name='frame_total' src="purchase_order_total.php" scrolling="no"></frame>
	<frame name='frame_action' src="purchase_order_action.php" scrolling="no"></frame>
</frameset>

</html>