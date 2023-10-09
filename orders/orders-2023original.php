<?
/**
*
* @version 		$Id: orders.php,v 1.6 2006/02/25 06:29:23 cvs Exp $
* @copyright 		Cynergy Software 2005
* @author		Luk Gastmans
* @date			12 Oct 2005
* @module 		Bills Grid
* @name  		bills.php
*
* This file uses the Grid component to generate the bills grid
*/

	$str_cur_module='Orders';

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once   "../include/grid.inc.php";

	$int_access_level = (getModuleAccessLevel('Orders'));

	$_SESSION["int_orders_menu_selected"] = 1;

	if ($_SESSION["int_user_type"] > 1) {
		$int_access_level = ACCESS_ADMIN;
	}

	$module_billing = getModule('Orders');
	if (!($module_billing->monthExists($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']))) {
		dieWithError("This module has no information for the selected year.");
	}

	//====================================
	// get the file names for the printing
        //------------------------------------
    $qry = new Query("SELECT print_to_filename FROM templates WHERE template_type = ".TEMPLATE_ORDER_INVOICE." AND is_default = 'Y'");
	$str_invoice_filename = $qry->FieldByName('print_to_filename');
	$qry->Query("SELECT print_to_filename FROM templates WHERE template_type = ".TEMPLATE_ORDER_PROFORMA." AND is_default = 'Y'");
	$str_proforma_filename = $qry->FieldByName('print_to_filename');

	function getOrderType($f_field, $f_qry) {
		switch ($f_qry->FieldByName($f_field)) {
			case ORDER_TYPE_DAILY:
				echo "Daily";
				break;
			case ORDER_TYPE_WEEKLY:
				echo "Weekly";
				break;
			case ORDER_TYPE_MONTHLY:
				echo "Monthly";
				break;
			case ORDER_TYPE_ONCE:
				echo "Once";
				break;
			default:
				echo "Unknown";
				break;
		}
	}

	function getDayOfWeek($f_field, $f_qry) {
		if ($f_qry->FieldByName('order_type') == 0)
			echo "";
		else {
			switch ($f_qry->FieldByName($f_field)) {
				case ORDER_DAY_SUNDAY:
					echo "Sunday";
					break;
				case ORDER_DAY_MONDAY:
					echo "Monday";
					break;
				case ORDER_DAY_TUESDAY:
					echo "Tuesday";
					break;
				case ORDER_DAY_WEDNESDAY:
					echo "Wednesday";
					break;
				case ORDER_DAY_THURSDAY:
					echo "Thursday";
					break;
				case ORDER_DAY_FRIDAY:
					echo "Friday";
					break;
				case ORDER_DAY_SATURDAY:
					echo "Saturday";
					break;
			}
		}
	}

	function getWeek($f_field, $f_qry) {
		if (($f_qry->FieldByName('order_type') == 0) || ($f_qry->FieldByName('order_type') == 1))
			echo "";
		else {
			switch ($f_qry->FieldByName($f_field)) {
				case ORDER_WEEK1:
					echo "Week 1";
					break;
				case ORDER_WEEK2:
					echo "Week 2";
					break;
				case ORDER_WEEK3:
					echo "Week 3";
					break;
				case ORDER_WEEK4:
					echo "Week 4";
					break;
			}
		}
	}

	function getMonth($f_field, $f_qry) {
		if ($f_qry->FieldByName('order_type') == 3) {
			switch ($f_qry->FieldByName($f_field)) {
				case 1:
					echo "January";
					break;
				case 2:
					echo "February";
					break;
				case 3:
					echo "March";
					break;
				case 4:
					echo "April";
					break;
				case 5:
					echo "May";
					break;
				case 6:
					echo "June";
					break;
				case 7:
					echo "July";
					break;
				case 8:
					echo "August";
					break;
				case 9:
					echo "September";
					break;
				case 10:
					echo "October";
					break;
				case 11:
					echo "November";
					break;
				case 12:
					echo "December";
					break;
			}
		}
		else
			echo "";
	}

	function getPaymentType($f_field, $f_qry) {
		switch ($f_qry->FieldByName($f_field)) {
			case BILL_CASH:
				echo "Cash";
				break;
			case BILL_ACCOUNT:
				echo "Account";
				break;
			case BILL_PT_ACCOUNT:
				echo "PT Account";
				break;
			case BILL_CREDIT_CARD:
				echo "Credit Card";
				break;
			case BILL_CHEQUE:
				echo "Cheque";
				break;
		}
	}

	function getOrderStatus($f_field, $f_qry) {
		switch ($f_qry->FieldByName($f_field)) {
			case ORDER_STATUS_PENDING:
				echo "Pending";
				break;
			case ORDER_STATUS_BILLED:
				echo "<font color='green'>Billed</font>";
				break;
			case ORDER_STATUS_CANCELLED:
				echo "<font color='red'>Cancelled</font>";
				break;
			case ORDER_STATUS_NO_BILL:
				echo "<font color='green'>No Bill</font>";
				break;
			case ORDER_STATUS_ACTIVE:
				echo "<font color='blue'>Active</font>";
				break;
			case ORDER_STATUS_DELIVERED:
				echo "Delivered";
				break;
			case ORDER_STATUS_RECEIVED:
				echo "<font color='orange'>Received</font>";
				break;
			case ORDER_STATUS_PROCESSING:
				echo "Processing";
				break;
			default:
				echo "Unknown";
				break;
		}
	}

	function drawFSAccount($f_field, $f_qry) {
		if ($f_qry->FieldByName('payment_type') == 2) {
			echo $f_qry->FieldByName($f_field);
		}
		else	{
			echo "";
		}
	}

	function drawPTAccount($f_field, $f_qry) {
		if ($f_qry->FieldByName('payment_type') == 3) {
			echo $f_qry->FieldByName($f_field);
		}
		else	{
			echo "";
		}
	}
	
	function getOrderCancelledDate($f_field, $f_qry) {
		if ($f_qry->FieldByName('order_status') == ORDER_STATUS_CANCELLED) {
			echo MakeHumanDate($f_qry->FieldByName($f_field));
		}
		else
			echo '';
	}

$grid = new DBGrid('orders_main');

$grid->addColumn("Customer", "company", "string", true, 250);
$grid->addColumn("FS Account", "ac.account_number", "custom", true, 70, 'drawFSAccount');
$grid->addColumn("FS Name", "ac.account_name", "custom", true, 200, 'drawFSAccount');
$grid->addColumn("PT Account", "pt.account_number", "custom", true, 70, 'drawPTAccount');
$grid->addColumn("PT Name", "pt.account_name", "custom", true, 200, 'drawPTAccount');
$grid->addColumn("Community", "community_name", "string", true, 200);
$grid->addColumn("Type", "order_type", "custom", true, 100, 'getOrderType');
$grid->addColumn("Day", "day_of_week", "custom", true, 70, 'getDayOfWeek');
$grid->addColumn("Week", "order_week", "custom", true, 100, 'getWeek');
$grid->addColumn("Month", "order_month", "custom", true, 200, 'getMonth');
$grid->addColumn("Amount", "total_amount", "number", true, 100);
$grid->addColumn("Payment", "payment_type", "custom", true, 100, 'getPaymentType');
$grid->addColumn("Bill", "is_billable", "boolean", false, 50);
$grid->addColumn("Status", "order_status", "custom", true, 50, 'getOrderStatus');
$grid->addColumn("From", "date_cancel_from", "custom", true, 100, 'getOrderCancelledDate');
$grid->addColumn("To", "date_cancel_till", "custom", true, 100, 'getOrderCancelledDate');
$grid->addColumn("User", "username", "string", true, 150);

$grid->loadView();

$grid->setQuery("SELECT
	o.order_id,
	o.order_type,
	o.day_of_week,
	o.order_month,
	o.order_week,
	o.total_amount,
	o.payment_type,
	o.is_billable,
	o.order_status,
	o.date_cancel_from,
	o.date_cancel_till,
	com.community_name,
	user.username,
	ac.account_number AS `ac.account_number`,
	pt.account_number AS `pt.account_number`,
	ac.account_name  AS `ac.account_name`,
	pt.account_name AS `pt.account_name`,
	c.company
FROM
	".Monthalize('orders')." o
INNER JOIN user ON (user.user_id = o.user_id)
LEFT JOIN account_cc ac ON (ac.cc_id = o.CC_id)
LEFT JOIN account_pt pt ON (pt.account_id = o.CC_id)
LEFT JOIN customer c ON (c.id = o.CC_id)
LEFT JOIN communities com ON (com.community_id = o.community_id)
");

//$grid->b_debug = true;
$grid->processParameters($_GET);
$grid->addUniqueFilter('o.storeroom_id', 'equals', $_SESSION["int_current_storeroom"], 'number');

$grid->setOnClick('gridClick','order_id');
$grid->setSubmitURL('orders.php');

if (!empty($_GET["action"]))
	if ($_GET["action"]=="del") {
		require("order_delete.php");
		
		$str_retval = deleteOrder($_GET["delid"]);
		
		$arr_retval = explode('|', $str_retval);
		$_SESSION['str_order_delete_message'] = $arr_retval[1];
		
		header("Location:orders.php?".$grid->buildQueryString());
		exit;
	}
?>

<html>
<head><TITLE></TITLE>
<link href="../include/styles.css" rel="stylesheet" type="text/css">

	<script language='javascript'>

		var intSerialNumber=0;

		function getSelectedSerialNumber() {
			return intSerialNumber;
		}

		function gridClick(id) {
			intSerialNumber = id;
			forceResize(1,0);
			parent.frames["orders_content_detail"].frames["items_content"].document.location="order_items.php?id="+id;
		}

		function doResize(win) {

			if (parent.frames["orders_content_detail"].innerWidth)
			{
				frameWidth = parent.frames["orders_content_detail"].innerWidth;
				frameHeight = parent.frames["orders_content_detail"].innerHeight;
			}
			else if (parent.frames["orders_content_detail"].document.documentElement && parent.frames["orders_content_detail"].document.documentElement.clientWidth)
			{
				frameWidth = parent.frames["orders_content_detail"].document.documentElement.clientWidth;
				frameHeight = parent.frames["orders_content_detail"].document.documentElement.clientHeight;
			}
			else if (parent.frames["orders_content_detail"].document.body)
			{
				frameWidth = parent.frames["orders_content_detail"].document.body.clientWidth;
				frameHeight = parent.frames["orders_content_detail"].document.body.clientHeight;
			}

			if (win==1) {

			if (frameHeight != "1") {
				parent.document.body.rows="30,*,1";
			} else parent.document.body.rows="30,*,150";

			} else {
				if (frameHeight <= 150) {
					parent.document.body.rows="1,1,*";
				}
				else
					parent.document.body.rows="30,*,150";
			}
		}

		function forceResize(win,asize) {

			if (parent.frames["orders_content_detail"].innerWidth)
			{
				frameWidth = parent.frames["orders_content_detail"].innerWidth;
				frameHeight = parent.frames["orders_content_detail"].innerHeight;
			}
			else if (parent.frames["orders_content_detail"].document.documentElement && parent.frames["orders_content_detail"].document.documentElement.clientWidth)
			{
				frameWidth = parent.frames["orders_content_detail"].document.documentElement.clientWidth;
				frameHeight = parent.frames["orders_content_detail"].document.documentElement.clientHeight;
			}
			else if (parent.frames["orders_content_detail"].document.body)
			{
				frameWidth = parent.frames["orders_content_detail"].document.body.clientWidth;
				frameHeight = parent.frames["orders_content_detail"].document.body.clientHeight;
			}

			if (win==1) {
				if (asize == "1") {
					parent.document.body.rows="30,*,1";
				}
				else
					parent.document.body.rows="30,*,150";

			}
		}

		function newOrder() {
			<?
				if (getModuleById(9) === null)
					$str_value = 0;
				else
					$str_value = 1;
			?>
			var order_client_enabled = <?echo $str_value;?>;
			if (order_client_enabled == 0)
				myWin = window.open("order_frameset.php?action=clear_order",'create_order','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=no,resizable=no,width=800,height=700,top=0');
			else
				myWin = window.open("client/order_frameset.php?action=clear_order",'create_order','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=no,resizable=no,width=800,height=700,top=0');
			myWin.moveTo((screen.availWidth/2 - 800/2), (screen.availHeight/2 - 700/2));
			myWin.focus();
		}

		function modifyOrder() {
			sNo = getSelectedSerialNumber();
			<?
				if (getModuleById(9) === null)
					$str_value = 0;
				else
					$str_value = 1;
			?>
			var order_client_enabled = <?echo $str_value;?>;
			
			if (sNo > 0) {
				if (order_client_enabled == 0)
					myWin = window.open("order_frameset.php?order_id="+sNo,'modify_order','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=no,resizable=no,width=800,height=700,top=0');
				else
					myWin = window.open("client/order_frameset.php?order_id="+sNo,'modify_order','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=no,resizable=no,width=800,height=700,top=0');
				myWin.moveTo((screen.availWidth/2 - 800/2), (screen.availHeight/2 - 700/2));
				myWin.focus();
			}
			else
				alert("Select an order to modify");
		}
		
		function cancelOrder() {
			sNo = getSelectedSerialNumber();
			if (sNo>0) {
				myWin = window.open("order_cancel.php?order_id="+sNo,'cancel_order','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=no,resizable=yes,width=500,height=300,top=0,left=0');
				myWin.moveTo((screen.availWidth/2 - 500/2), (screen.availHeight/2 - 300/2));
				myWin.focus();
			}
			else
				alert("Select an order to cancel");
		}

		function deleteOrder() {
			sNo = getSelectedSerialNumber();
			if (sNo>0) {
				if (confirm('Are you sure?')) {
					if (document.location.href.indexOf("?")<0) {
						document.location = document.location.href+"?action=del&delid="+sNo;
					} else {
						document.location = document.location.href+"&action=del&delid="+sNo;
					}
				}
			}
			else
				alert("Select an order to delete");
		}

		function createOrderBill() {
			sNo = getSelectedSerialNumber();
			if (sNo > 0) {
				myWin = window.open("order_create_bill.php?order_id="+sNo,'order_create_bill','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=no,resizable=yes,width=500,height=300,top=0,left=0');
				myWin.moveTo((screen.availWidth/2 - 500/2), (screen.availHeight/2 - 300/2));
				myWin.focus();
			}
			else
				alert('Select an order to print');
		}

		function createOrderBills() {
			myWin = window.open("order_create_bills.php",'order_create_bills','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=no,resizable=yes,width=500,height=300,top=0,left=0');
			myWin.moveTo((screen.availWidth/2 - 500/2), (screen.availHeight/2 - 300/2));
			myWin.focus();
		}

		function printOrder() {
			sNo = getSelectedSerialNumber();
			if (sNo > 0) {
				myWin = window.open("print_order.php?id="+sNo, 'printwin', 'width=800,height=500,resizable=yes');
			}
			else
				alert('Select an order to print');
		}
		
		function importMantra() {
			myWin = window.open("import_mantra_po.php", 'importmantra', 'width=800,height=500,resizable=yes,menubar=yes'); 
		}

		function importOrder() {
			myWin = window.open("order_import.php",'order_import','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=no,resizable=yes,width=500,height=300,top=0,left=0');
			myWin.moveTo((screen.availWidth/2 - 500/2), (screen.availHeight/2 - 300/2));
			myWin.focus();
		}
		
		function printDeliveryNote() {
			sNo = getSelectedSerialNumber();
			if (sNo > 0) {
				myWin = window.open('print_delivery_note.php?order_id='+sNo,'print_delivery_notes','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=no,resizable=yes,width=300,height=100,top=0,left=0');
				myWin.moveTo((screen.availWidth/2 - 300/2), (screen.availHeight/2 - 100/2));
				myWin.focus();
			}
			else
				alert('Select an order to print');
		}
		
		function printProformaInvoice() {
			aFilename = '<?echo $str_proforma_filename;?>';
			sNo = getSelectedSerialNumber();
			if (sNo > 0) {
				myWin = window.open(aFilename+'?order_id='+sNo,'print_proforma_invoice','width=800,height=500,resizable=yes,menubar=yes'); 
			}
			else
				alert('Select an order to print');
		}

		function exportProforma() {
			aFilename = '<?echo $str_invoice_filename;?>';
			sNo = getSelectedSerialNumber();
			if (sNo > 0) {
				myWin = window.open("export_invoice.php?id="+sNo+"&proforma=Y", 'printwin', 'width=800,height=500,resizable=yes,menubar=yes'); 
			}
			else
				alert('Select a bill to print');
		}
		
	</script>

</head>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>

<?
	$grid_form = new GridForm();
	$grid->prepareQuery();
	$grid_form->setGrid($grid);

	// cancelling and creating new bills only in current month/year
	if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
		$grid_form->addButton('New Order','../images/page.png','newOrder','left');
		$grid_form->addHTML('&nbsp;', 'left');
		$grid_form->addButton('Edit Order','../images/page_edit.png','modifyOrder','left');
		$grid_form->addHTML('&nbsp;', 'left');
		if (getModuleByID(9) === null) { // ORDER_CLIENT_ENABLED == 0) {
			$grid_form->addButton('Cancel Order','../images/page_delete.png','cancelOrder','left');
			$grid_form->addHTML('&nbsp;', 'left');
		}
		$grid_form->addButton('Delete Order','../images/cross.png','deleteOrder','left');
		$grid_form->addHTML('&nbsp;', 'left');
		if (getModuleByID(9) === null) {
			$grid_form->addButton('Create Order Bills for a given date', '../images/table_multiple.png', 'createOrderBills', 'left');
			$grid_form->addHTML('&nbsp;', 'left');
			$grid_form->addButton('Print delivery note', '../images/printer.png', 'printDeliveryNote', 'left');
		}
		else {
			$grid_form->addButton('Create Order Bill for selected order', '../images/table_multiple.png', 'createOrderBill', 'left');
			$grid_form->addHTML('&nbsp;', 'left');
			$grid_form->addButton('Import Mantra Purchase Order', '../images/icon_download.gif', 'importMantra', 'left');
			$grid_form->addHTML('&nbsp;', 'left');
			$grid_form->addButton('Import an order from a tab delimited text file', '../images/application_get.png', 'importOrder', 'left');
			$grid_form->addHTML('&nbsp;', 'left');
			$grid_form->addButton('Print proforma invoice for the selected order', '../images/printer.png', 'printProformaInvoice', 'left');
			$grid_form->addHTML('&nbsp;', 'left');
			$grid_form->addButton('Export Proforma to PDF', '../images/pdf-icon.png', 'exportProforma', 'left');
		}
	}
//	$grid_form->addButton('Print Order', '../images/print.gif', 'printOrder', 'left');

	$arr_order_type['Daily'] = ORDER_TYPE_DAILY;
	$arr_order_type['Weekly'] = ORDER_TYPE_WEEKLY;
	$arr_order_type['Monthly'] = ORDER_TYPE_MONTHLY;
	$arr_order_type['Once'] = ORDER_TYPE_ONCE;
	
	$arr_order_day['Monday'] = ORDER_DAY_MONDAY;
	$arr_order_day['Tuesday'] = ORDER_DAY_TUESDAY;
	$arr_order_day['Wednesday'] = ORDER_DAY_WEDNESDAY;
	$arr_order_day['Thursday'] = ORDER_DAY_THURSDAY;
	$arr_order_day['Friday'] = ORDER_DAY_FRIDAY;
	$arr_order_day['Saturday'] = ORDER_DAY_SATURDAY;
	
	$qry_communities = new Query("SELECT * FROM communities ORDER BY community_name");
	$arr_order_community = array();
	for ($i=0; $i<$qry_communities->RowCount();$i++) {
		$arr_order_community[$qry_communities->FieldByName('community_name')] = $qry_communities->FieldByName('community_id');
		$qry_communities->Next();
	}
	
	if (getModuleByID(9) === null) {
		$grid->addUniqueFilter('order_type', 'equals', '', 'string');
		$grid->addUniqueFilter('day_of_week', 'equals', '', 'string');
		$grid->addUniqueFilter('community_id', 'equals', '', 'string');
	
		$grid_form->addHTML('&nbsp;<font style=\"font-size:12px\">Type:</font> ', 'center');
		$grid_form->addSelectionControl('filter1','order_type', $arr_order_type, 'center');
		$grid_form->addHTML('&nbsp;', 'center');
		
		$grid_form->addHTML('&nbsp;<font style=\"font-size:12px\">Day:</font> ', 'center');
		$grid_form->addSelectionControl('filter2','day_of_week', $arr_order_day, 'center');
		$grid_form->addHTML('&nbsp;', 'center');
		
		$grid_form->addHTML('&nbsp;<font style=\"font-size:12px\">Community:</font> ', 'center');
		$grid_form->addSelectionControl('filter3','com.community_id', $arr_order_community, 'center');
		$grid_form->addHTML('&nbsp;', 'center');
		
		$grid_form->addControl('filter4','center');
	}
	else {
		$grid_form->addControl('filter1','center');
	}

	$grid_form->addControl('nav','right');
	$grid_form->addControl('refresh','right');
	$grid_form->addControl('view','right');
	$grid_form->addButton('Resize','../images/resize.gif','doResize(1)','right');
	$grid_form->setFrames('orders_menu','orders_content');
	$grid_form->draw();

	if (!empty($_SESSION["str_order_delete_message"])) {
		echo "<script language=\"javaScript\">";
		echo "alert('".$_SESSION["str_order_delete_message"]."');";
		echo "</script>";
	
		$_SESSION["str_order_delete_message"]="";
	}

?>

</body>
</html>