<?
	require_once("../common/product_quantities.php");
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");

	error_reporting(E_ERROR);

	// this array holds the total quantities of items billed, as one item can be billed across multiple batches
	// it serves the purpose of checking whether the total quantity bought is equal to, or greater than, the
	// discount_qty in the stock_storeroom_product table, in which case the discount_percent should be suggested
///	('arr_total_qty');

	// this array holds the batch code and quantity for the product that is currently being billed
//	('arr_item_batches');
	// the string version of the array to pass to javascript
//	('str_total_qty');

	// list of items billed
//	('arr_billed_items');
	

	if ($_SESSION['save_counter'] > 0)
		die('This bill has been saved.');
	
	// get which types that can be billed
	$qry = new Query("
		SELECT can_bill_cash, can_bill_fs_account, can_bill_pt_account, can_bill_aurocard
		FROM stock_storeroom
		WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")
	");
	$bool_cash = false;
	$bool_fs = false;
	$bool_pt = false;
	$bool_transfer = false;
	$bool_aurocard = false;
	if ($qry->FieldByName('can_bill_cash') == 'Y')
		$bool_cash = true;
	if ($qry->FieldByName('can_bill_fs_account') == 'Y')
		$bool_fs = true;
	if ($qry->FieldByName('can_bill_pt_account') == 'Y')
		$bool_pt = true;
	if ($qry->FieldByName('can_bill_aurocard') == 'Y')
		$bool_aurocard = true;
		
	if (CAN_BILL_TRANSFER_GOOD === 1)
		$bool_transfer = true;

	if (IsSet($_GET['action'])) {
		if ($_GET['action'] == 'clear_bill') {
			// clear the session variables related to the billing
			$_SESSION['bill_id'] = -1;
			$_SESSION['bill_number'] = '';
			unset($_SESSION["arr_total_qty"]);
			unset($_SESSION["arr_item_batches"]);
			unset($_SESSION['arr_billed_items']);
			if (IsSet($_GET["bill_type"]))
				$_SESSION['current_bill_type'] = $_GET["bill_type"];
			else {
				if ($bool_cash)
					$_SESSION['current_bill_type'] = BILL_CASH;
				else if ($bool_fs)
					$_SESSION['current_bill_type'] = BILL_ACCOUNT;
				else if ($bool_pt)
					$_SESSION['current_bill_type'] = BILL_PT_ACCOUNT;
				else if ($bool_transfer)
					$_SESSION['current_bill_type'] = BILL_TRANSFER_GOOD;
				else if ($bool_aurocard)
					$_SESSION['current_bill_type'] = BILL_AUROCARD;
			}
			$_SESSION['current_bill_day'] = date('j');
			$_SESSION['current_account_number'] = "";
			$_SESSION['current_account_name'] = "";
			$_SESSION['bill_total'] = 0;
			$_SESSION['sales_promotion'] = 0;
			$_SESSION['bill_card_name'] = '';
			$_SESSION['bill_card_number'] = '';
			$_SESSION['bill_card_date'] = '';
			$_SESSION['aurocard_number'] = 0;
			$_SESSION['aurocard_transaction_id'] = 0;
			$_SESSION['fs_account_balance'] = 0;
			$_SESSION['arr_total_qty'] = array();

			// now refresh this page without the GET variable
			header('location:billing_frameset.php');
			exit;
		}
	}
	
	if (IsSet($_GET['id'])) {
		$str_retval = load_bill_details($_GET['id']);
		$arr_retval = explode('|', $str_retval);
		
		if ($arr_retval[0] == 'false') {
			header('location:billing_frameset.php?action=clear_bill');
		}
	}
?>

<html>
<?
if ($bool_pt == true) {
	$_SESSION['current_bill_type'] = BILL_PT_ACCOUNT; ?>
	<frameset rows='65,80,0,0,80,*,75,40' border=0 scrolling=no>
<? } else if ($_SESSION['current_bill_type'] == BILL_CASH) { ?>
	<frameset rows='65,0,0,0,80,*,75,40' border=0 scrolling=no>
<? } else if ($_SESSION['current_bill_type'] == BILL_CREDIT_CARD) { ?>
	<frameset rows='65,0,80,0,80,*,75,40' border=0 scrolling=no>
<? } else if (($_SESSION['current_bill_type'] == BILL_ACCOUNT) || ($_SESSION['current_bill_type'] == BILL_TRANSFER_GOOD)) { ?>
	<frameset rows='65,80,0,0,80,*,75,40' border=0 scrolling=no>
<? } else if ($_SESSION['current_bill_type'] == BILL_AUROCARD) { ?>
	<frameset rows='65,0,0,80,80,*,75,40' border=0 scrolling=no>
<? } ?>
	<frame name='frame_type' src="billing_type.php" scrolling=no style="z-index: 10000;">
	<frame name='frame_account' src="billing_account.php" scrolling=no style="z-index: 1;">
	<frame name='frame_creditcard' src="billing_creditcard.php" scrolling=no style="z-index: 1;">
	<frame name='frame_aurocard' src='billing_aurocard.php' scrolling='no' style="z-index: 1;">
	<frame name='frame_enter' src="billing_enter.php" scrolling=no style="z-index: 1;">
	<frame name='frame_list' src="billing_list.php" scrolling=auto style="z-index: 1;">
	<frame name='frame_total' src="billing_total.php" scrolling=no style="z-index: 1;">
	<frame name='frame_action' src="billing_action.php" scrolling=no style="z-index: 1;">
</frameset>

</html>