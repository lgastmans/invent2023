<?
	require_once("../../common/product_quantities.php");
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");

	error_reporting(E_ERROR);

	// this array holds the total quantities of items billed, as one item can be billed across multiple batches
	// it serves the purpose of checking whether the total quantity bought is equal to, or greater than, the
	// discount_qty in the stock_storeroom_product table, in which case the discount_percent should be suggested
	//('arr_total_qty');
	// this array holds the batch code and quantity for the product that is currently being billed
	//('arr_item_batches');
	// the string version of the array to pass to javascript
	//('str_total_qty');

	// list of items billed
	//('arr_billed_items');

	
	if ($_SESSION['save_counter'] > 0)
		die('This dc has been saved.');
	
	if (IsSet($_GET['action'])) {
		if ($_GET['action'] == 'clear_dc') {
			// clear the session variables related to the billing
			$_SESSION['dc_id'] = -1;
			$_SESSION['dc_client_id'] = 0;
			$_SESSION['dc_number'] = '';
			$_SESSION['sales_promotion'] = 0;
			unset($_SESSION["arr_total_qty"]);
			unset($_SESSION["arr_item_batches"]);
			unset($_SESSION['arr_billed_items']);
			
			$_SESSION['current_dc_day'] = date('j');
			$_SESSION['dc_total'] = 0;

			// now refresh this page without the GET variable
			header('location:dc_frameset.php');
			exit;
		}
	}
	
	if (IsSet($_GET['id'])) {
		/*
			if it is an edit, make sure the status
			is set to "unresolved"
		*/
		if ($_GET['id'] > 0) {
			$qry = new Query("SELECT * FROM ".Monthalize('dc')." WHERE dc_id = ".$_GET['id']);
			if ($qry->FieldByName('dc_status') > DC_STATUS_UNRESOLVED) {
				die('This DC cannot be editted');
			}
		}
			
		$str_retval = load_dc_details($_GET['id']);
		$arr_retval = explode('|', $str_retval);
		
		if ($arr_retval[0] == 'false') {
			header('location:dc_frameset.php?action=clear_dc');
		}
	}
?>

<html>
	<frameset rows='45,80,*,75,40' border=0 scrolling=no>
	<frame name='frame_type' src="dc_type.php" scrolling=no>
	<frame name='frame_enter' src="dc_enter.php" scrolling=no>
	<frame name='frame_list' src="dc_list.php" scrolling=auto>
	<frame name='frame_total' src="dc_total.php" scrolling=no>
	<frame name='frame_action' src="dc_action.php" scrolling=no>
</frameset>

</html>