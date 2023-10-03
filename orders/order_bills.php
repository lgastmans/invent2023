<?
/**
*
* @version 		$Id: bills.php,v 1.6 2006/02/25 06:29:23 cvs Exp $
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
	require_once "../include/grid.inc.php";

	$int_access_level = (getModuleAccessLevel('Orders'));

	$_SESSION["int_orders_menu_selected"] = 2;

	if ($_SESSION["int_user_type"] > 1) {
		$int_access_level = ACCESS_ADMIN;
	}

	$module_billing = getModule('Orders');
	if (!($module_billing->monthExists($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']))) {
		dieWithError("This module has no information for the selected year.");
	}

	//===================================
	// get which types that can be billed
        //-----------------------------------
	$qry = new Query("
		SELECT can_bill_cash, can_bill_fs_account, can_bill_pt_account
		FROM stock_storeroom
		WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")
	");
	$bool_cash = false;
	$bool_fs = false;
	$bool_pt = false;
	if ($qry->FieldByName('can_bill_cash') == 'Y') {
		$bool_cash = true;
	}
	if ($qry->FieldByName('can_bill_fs_account') == 'Y') {
		$bool_fs = true;
	}
	if ($qry->FieldByName('can_bill_pt_account') == 'Y') {
		$bool_pt = true;
	}
	$arr_filter_type = array();
	if ($bool_cash == true)
		$arr_filter_type['Cash'] = 1;
	if ($bool_fs == true)
		$arr_filter_type['FS Account'] = 2;
	if ($bool_pt == true)
		$arr_filter_type['PT Account'] = 3;

	//====================================
	// get the file names for the printing
	//------------------------------------
	$qry->Query("SELECT print_to_filename FROM templates WHERE template_type = ".TEMPLATE_ORDER_INVOICE." AND is_default = 'Y'");
	$str_invoice_filename = $qry->FieldByName('print_to_filename');
	$qry->Query("SELECT print_to_filename FROM templates WHERE template_type = ".TEMPLATE_ORDER_PROFORMA." AND is_default = 'Y'");
	$str_proforma_filename = $qry->FieldByName('print_to_filename');
	
	$arr_pending['Pending'] = 'Y';
	$arr_pending['Delivered'] = 'N';

	$arr_payments['Zero'] = '0';

	function get_mysql_date($int_day, $int_month, $int_year) {
		$str_retval = $int_year."-".sprintf("%02d", $int_month)."-".sprintf("%02d", $int_day);
		return $str_retval;
	}
	
	$int_num_days = DaysInMonth2($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']);
	$arr_days = array();
	for ($i=1;$i<=$int_num_days;$i++) {
		$arr_days[$i] = get_mysql_date($i, $_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']);
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
	
	function getBillStatus($f_field, $f_qry) {
		switch ($f_qry->FieldByName($f_field)) {
			case BILL_STATUS_UNRESOLVED:
				echo "Unresolved";
				break;
			case BILL_STATUS_RESOLVED:
				echo "Resolved";
				break;
			case BILL_STATUS_CANCELLED:
				echo "<font color='red'>Cancelled</font>";
				break;
			case BILL_STATUS_PROCESSING:
				echo "processing";
				break;
			case BILL_STATUS_DISPATCHED:
				echo "<font color='orange'>dispatched</font>";
				break;
			case BILL_STATUS_DELIVERED:
				echo "<font color='green'>delivered</font>";
				break;
			default:
				echo "Unknown";
				break;
		}
	}
	
	function getTotalAmount($f_field, $f_qry) {
		echo sprintf("%01.2f", $f_qry->FieldByName($f_field));
	}
	
	function drawPayments($f_field, $f_qry) {
		$qry = new Query("SELECT COUNT(id) AS total_payments FROM ".Yearalize('bill_payments')." WHERE bill_id = ".$f_qry->FieldByName($f_field));

		echo $qry->FieldByName('total_payments');
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
	
	function drawDate($f_field, $f_qry) {
		echo makeHumanTime($f_qry->FieldByName($f_field));
	}

	function getPending($f_field, $f_qry) {
		if ($f_qry->FieldByName($f_field) == 'Y')
			echo "<font color='red'>Pending</font>";
		else
			echo "<font color='green'>Completed</font>";
	}

	function drawBillNumber($f_field, $f_qry) {
		if ($f_qry->FieldByName('is_debit_bill') == 'Y')
			echo "<font color='red'>".$f_qry->FieldByName($f_field)."</font>";
		else
			echo $f_qry->FieldByName($f_field);
	}
	
	global $int_cur_row;
	$int_cur_row = 0;

	function drawCheck($f_field, $f_qry) {
		global $int_cur_row;
		echo "<input type='checkbox' name='check_".$int_cur_row."' value='".$f_qry->FieldByName('bill_id')."'>";
		$int_cur_row++;	
	}

	$grid = new DBGrid('order_bills');
	$grid->addColumn("", "bill_id", "custom", false, 30, "drawCheck"); 
	$grid->addColumn("Bill", "bill_number", "custom", true, 50, "drawBillNumber");
	$grid->addColumn("Date", "date_created", "custom", false, 100, 'drawDate');
	$grid->addColumn("Payments", "bill_id", "custom", false, 100, 'drawPayments');
	$grid->addColumn("FS Account", "ac.account_number", "custom", true, 70, 'drawFSAccount');
	$grid->addColumn("FS Name", "ac.account_name", "custom", true, 200, 'drawFSAccount');
	$grid->addColumn("PT Account", "pt.account_number", "custom", true, 70, 'drawPTAccount');
	$grid->addColumn("PT Name", "pt.account_name", "custom", true, 200, 'drawPTAccount');
	$grid->addColumn("Customer", "company", "string", true, 250);
	$grid->addColumn("Amount", "total_amount", "custom", true, 100, 'getTotalAmount');
	$grid->addColumn("Type", "payment_type", "custom", true, 100, 'getPaymentType');
	$grid->addColumn("Ref", "payment_type_number", "string", true, 100);
	$grid->addColumn("Promotion", "bill_promotion", "string", true, 100);
	$grid->addColumn("Status", "is_pending", "custom", true, 50, 'getPending');
	$grid->addColumn("Status", "bill_status", "custom", true, 50, 'getBillStatus');
	$grid->addColumn("On", "resolved_on", "date", true, 100);
	$grid->addColumn("User", "username", "string", true, 150);
	
	$grid->loadView();
	
	$grid->setQuery("SELECT
		b.bill_id,
		b.storeroom_id,
		b.bill_number,
		b.date_created,
		b.total_amount,
		b.payment_type,
		b.payment_type_number,
		b.bill_promotion,
		b.bill_status,
		b.is_pending,
		b.resolved_on,
		b.user_id,
		b.is_debit_bill,
		user.username,
		ac.account_number AS `ac.account_number`,
		pt.account_number AS `pt.account_number`,
		ac.account_name  AS `ac.account_name`,
		pt.account_name AS `pt.account_name`,
		c.company
	FROM
		".Monthalize('bill')." b
	INNER JOIN user ON (user.user_id = b.user_id)
		LEFT JOIN account_cc ac ON (ac.cc_id = b.CC_id)
		LEFT JOIN account_pt pt ON (pt.account_id = b.CC_id)
		LEFT JOIN customer c ON (c.id = b.CC_id)
	");
	
	$grid->processParameters($_GET);
	$grid->addUniqueFilter('storeroom_id', 'equals', $_SESSION["int_current_storeroom"], 'number');
	$grid->addUniqueFilter('module_id', 'equals', '7', 'number');
	$grid->addOrder('date_created', 'DESC');
	
	$grid->setOnClick('gridClick','bill_id');
	
	$grid->setSubmitURL('order_bills.php');
	
	if (IsSet($_GET["action"])) {
		if ($_GET["action"]=="del") {
			require_once("../common/product_cancel.php");
			require("order_bill_cancel.php");
			
			$str_force = 'N';
			if (IsSet($_GET['force']))
				$str_force = $_GET['force'];
			
			$str_retval = cancelOrderBill($_GET["delid"], $str_force);
			
			$arr_retval = explode('|', $str_retval);
			
			$_SESSION['str_order_cancel_message'] = $arr_retval[1];
			$_SESSION['str_order_cancel_error'] = $arr_retval[0];
			$_SESSION['str_order_cancel_id'] = $_GET['delid'];
			
			header("Location:order_bills.php?".$grid->buildQueryString());
			exit;
		}
		else if ($_GET['action'] == 'deliver') {
			require("order_bill_deliver.php");
			$str_retval = deliver_order_bill($_GET["delid"]);
			$arr_retval = explode("|", $str_retval);
			if ($arr_retval[1] == 'OK')
				$_SESSION['str_order_cancel_message'] = 'Successfully delivered order.';
			else
				$_SESSION['str_order_cancel_message'] = $arr_retval[1];
			
			header("Location:order_bills.php?".$grid->buildQueryString());
			exit;
		}
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
			parent.frames["order_bills_content_detail"].frames["items_content"].document.location="order_bill_items.php?id="+id;
		}

		function doResize(win) {

			if (parent.frames["order_bills_content_detail"].innerWidth)
			{
				frameWidth = parent.frames["order_bills_content_detail"].innerWidth;
				frameHeight = parent.frames["order_bills_content_detail"].innerHeight;
			}
			else if (parent.frames["order_bills_content_detail"].document.documentElement && parent.frames["order_bills_content_detail"].document.documentElement.clientWidth)
			{
				frameWidth = parent.frames["order_bills_content_detail"].document.documentElement.clientWidth;
				frameHeight = parent.frames["order_bills_content_detail"].document.documentElement.clientHeight;
			}
			else if (parent.frames["order_bills_content_detail"].document.body)
			{
				frameWidth = parent.frames["order_bills_content_detail"].document.body.clientWidth;
				frameHeight = parent.frames["order_bills_content_detail"].document.body.clientHeight;
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

			if (parent.frames["order_bills_content_detail"].innerWidth)
			{
				frameWidth = parent.frames["order_bills_content_detail"].innerWidth;
				frameHeight = parent.frames["order_bills_content_detail"].innerHeight;
			}
			else if (parent.frames["order_bills_content_detail"].document.documentElement && parent.frames["order_bills_content_detail"].document.documentElement.clientWidth)
			{
				frameWidth = parent.frames["order_bills_content_detail"].document.documentElement.clientWidth;
				frameHeight = parent.frames["order_bills_content_detail"].document.documentElement.clientHeight;
			}
			else if (parent.frames["order_bills_content_detail"].document.body)
			{
				frameWidth = parent.frames["order_bills_content_detail"].document.body.clientWidth;
				frameHeight = parent.frames["order_bills_content_detail"].document.body.clientHeight;
			}

			if (win==1) {
				if (asize == "1") {
					parent.document.body.rows="30,*,1";
				}
				else
					parent.document.body.rows="30,*,150";

			}
		}

		function processCheckbox() {
			for (i=0;i<document.order_bills.length;i++) {
				if (document.order_bills.elements[i].name.indexOf('check')>=0) {
					document.order_bills.elements[i].checked = true;
				}
			}
		}

		function processCheckboxFalse() {
			for (i=0;i<document.order_bills.length;i++) {
				if (document.order_bills.elements[i].name.indexOf('check')>=0) {
					document.order_bills.elements[i].checked = false;
				}
			}
		}

		function cancelOrderBill() {
			sNo = getSelectedSerialNumber();
			if (sNo>0) {
				if (confirm("Are you sure you want to cancel this order bill?")) {
					if (document.location.href.indexOf("?")<0) {
						document.location = document.location.href+"?action=del&delid="+sNo;
					} else {
						document.location = document.location.href+"&action=del&delid="+sNo;
					}
				}
			}
			else
				alert("Select an order to cancel");
		}

		function dispatchOrderBill() {
			str_id_list = '';
			for (i=0;i<document.order_bills.length;i++) {
				if (document.order_bills.elements[i].name.indexOf('check') >= 0) {
					if (document.order_bills.elements[i].checked == true)
						str_id_list += document.order_bills.elements[i].getAttribute('value') + '|';
				}
			}
			if (str_id_list.length > 0) {
				if (confirm('Are you sure?')) {
					str_id_list = str_id_list.substring(0, str_id_list.length - 1)
					myWin = window.open('deliver_orders_date.php?id_list='+str_id_list,'deliver_selected_orders','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=no,resizable=yes,width=400,height=150,top=0,left=0');
//					myWin = window.open('deliver_selected_orders.php?id_list='+str_id_list,'deliver_selected_orders','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=no,resizable=yes,width=400,height=400,top=0,left=0');
					myWin.moveTo((screen.availWidth/2 - 400/2), (screen.availHeight/2 - 150/2));
					myWin.focus();
				}
			}
			else
				alert('No bills selected to deliver');
		}

		function deliverOrderBill() {
			sNo = getSelectedSerialNumber();
			if (sNo > 0) {
				myWin = window.open('order_mark_delivered_date.php?id='+sNo,'order_mark_delivered','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=no,resizable=yes,width=400,height=150,top=0,left=0');
				myWin.moveTo((screen.availWidth/2 - 400/2), (screen.availHeight/2 - 150/2));
				myWin.focus();
			}
		}

		function invoicePayments() {
			sNo = getSelectedSerialNumber();
			if (sNo > 0) {
				myWin = window.open('invoice_payment.php?id='+sNo, 'invoice_payment','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=no,resizable=yes,width=800,height=450,top=0,left=0');
				myWin.moveTo((screen.availWidth/2 - 800/2), (screen.availHeight/2 - 450/2));
				myWin.focus();
			}
		}

		function printDeliveryNote() {
			str_id_list = '';
			for (i=0;i<document.order_bills.length;i++) {
				if (document.order_bills.elements[i].name.indexOf('check')>=0) {
					if (document.order_bills.elements[i].checked == true)
						str_id_list += document.order_bills.elements[i].getAttribute('value') + '|';
				}
			}
			if (str_id_list.length > 0) {
				str_id_list = str_id_list.substring(0, str_id_list.length - 1)
				myWin = window.open('print_delivery_notes.php?id_list='+str_id_list,'print_delivery_notes','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=no,resizable=yes,width=300,height=100,top=0,left=0');
				myWin.moveTo((screen.availWidth/2 - 300/2), (screen.availHeight/2 - 100/2));
				myWin.focus();
			}
			else
				alert('No orders selected to print');
		}

		function gridDblClick() {
			editOrderBill();
		}

		function editOrderBill() {
			<?
				if (getModuleById(9) === null)
					$str_value = 0;
				else
					$str_value = 1;
			?>
			var order_client_enabled = <?echo $str_value;?>;
			
			if (order_client_enabled == 0) {
				str_id_list = '';
				for (i=0;i<document.order_bills.length;i++) {
					if (document.order_bills.elements[i].name.indexOf('check')>=0) {
						if (document.order_bills.elements[i].checked == true)
							str_id_list += document.order_bills.elements[i].getAttribute('value') + '|';
					}
				}
				if (str_id_list.length > 0) {
					str = "load_edit_order_bills.php?id_list=" + str_id_list;
					myWin = window.open(str,'load_edit_order_bills','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=no,resizable=yes,width=300,height=300,top=0,left=0');
					myWin.moveTo((screen.availWidth/2 - 300/2), (screen.availHeight/2 - 300/2));
					myWin.focus();
				}
				else
					alert("Select an order bill to modify");
			}
			else {
				sNo = getSelectedSerialNumber();
				if (sNo > 0) {
					myWin = window.open('client/order_edit_frameset.php?id='+sNo,'edit_order_bill','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=no,resizable=yes,width=800,height=700,top=0,left=0');
					myWin.moveTo((screen.availWidth/2 - 800/2), (screen.availHeight/2 - 700/2));
					myWin.focus();
				}
				else
					alert("Select an order bill to modify");
			}
		}
		
		function newBill() {
			myWin = window.open("billing_frameset.php?action=clear_bill",'create_bill','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=no,resizable=yes,width=' + screen.availWidth + ',height=' + screen.availHeight + ',top=0,left=0');
			myWin.moveTo(0,0);
			myWin.focus();
		}

		function printOrderBill() {
			sNo = getSelectedSerialNumber();
			if (sNo > 0) {
				myWin = window.open('print_order_bill.php?id='+sNo, 'printwin', 'width=800,height=500,resizable=yes,menubar=no');
			}
			else
				alert('Select an order to print the bill for');
		}

		function moveOrderBill() {
			sNo = getSelectedSerialNumber();
			if (sNo > 0) {
				myWin = window.open('move_order.php?bill_id='+sNo, 'moveOrder', 'width=400,height=200,resizable=yes,menubar=no');
				myWin.moveTo((screen.availWidth/2 - 400/2), (screen.availHeight/2 - 200/2));
				myWin.focus();
			}
			else
				alert('Select an order to move');
		}
		
		function printInvoice() {
			aFilename = '<?echo $str_invoice_filename;?>';
			sNo = getSelectedSerialNumber();
			if (sNo > 0) {
				myWin = window.open(aFilename+"?id="+sNo, 'printwin', 'width=800,height=500,resizable=yes,menubar=yes'); 
			}
			else
				alert('Select a bill to print');
		}

		function exportInvoice() {
			aFilename = '<?echo $str_invoice_filename;?>';
			sNo = getSelectedSerialNumber();
			if (sNo > 0) {
				myWin = window.open("export_invoice.php?id="+sNo, 'printwin', 'width=800,height=500,resizable=yes,menubar=yes'); 
			}
			else
				alert('Select a bill to print');
		}

		function exportMantra() {
			aFilename = '<?echo $str_invoice_filename;?>';
			sNo = getSelectedSerialNumber();
			if (sNo > 0) {
				myWin = window.open("export_invoice_mantra.php?id="+sNo, 'printmantra', 'width=800,height=500,resizable=yes,menubar=yes'); 
			}
			else
				alert('Select a bill to print');
		}

		function printInternalOrder() {
			sNo = getSelectedSerialNumber();
			if (sNo > 0) {
				myWin = window.open("print_internal_order.php?id="+sNo, 'printwin', 'width=800,height=500,resizable=yes,menubar=yes'); //'toolbar=no,location=no,directories=no,status=no,scrollbars=no,resizable=no,width=450,height=250');
			}
			else
				alert('Select a bill to print');
		}
		
		function printProformaInvoice() {
			aFilename = '<?echo $str_proforma_filename;?>';
			sNo = getSelectedSerialNumber();
			if (sNo > 0) {
				myWin = window.open(aFilename+'?order_id='+sNo+'&is_bill_id=Y','print_proforma_invoice','width=800,height=500,resizable=yes,menubar=yes'); 
			}
			else
				alert('Select an order to print');
		}

		function exportProforma() {
			aFilename = '<?echo $str_invoice_filename;?>';
			sNo = getSelectedSerialNumber();
			if (sNo > 0) {
				myWin = window.open("export_proforma.php?id="+sNo, 'printwin', 'width=800,height=500,resizable=yes,menubar=yes'); 
			}
			else
				alert('Select a bill to print');
		}

	</script>

</head>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 onDblClick="gridDblClick()">
<form name='order_bills' method='get' action='test.php'>
<?
	$grid_form = new GridForm();
	$grid->prepareQuery();
	$grid_form->setGrid($grid);

	if (getModuleByID(9) === null) {
		$grid_form->addHTML('&nbsp;', 'left');
		$grid_form->addButton('Select All','../images/flag_green.png','processCheckbox','left');
		$grid_form->addHTML('&nbsp;', 'left');
		$grid_form->addButton('Select None','../images/flag_red.png','processCheckboxFalse','left');
		$grid_form->addHTML('&nbsp;&nbsp;', 'left');
	}
	
	// cancelling and creating new bills only in current month/year
	if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
		if ($int_access_level > ACCESS_READ) {
//			$grid_form->addButton('New Bill','../images/new.gif','newBill','left');
			$grid_form->addButton('Edit the selected order bill', '../images/page_edit.png', 'editOrderBill', 'left');
			$grid_form->addHTML('&nbsp;', 'left');
			$grid_form->addButton('Cancel Order Bill','../images/cross.png','cancelOrderBill','left');
			$grid_form->addHTML('&nbsp;', 'left');
			$grid_form->addButton('Dispatch the currently selected order bills','../images/lorry_go.png','dispatchOrderBill','left');
			$grid_form->addHTML('&nbsp;', 'left');
			$grid_form->addButton('Mark Delivered the currently selected order bills','../images/lorry_flatbed.png','deliverOrderBill','left');
			$grid_form->addHTML('&nbsp;', 'left');
			$grid_form->addButton('Move selected order bill to another month/year','../images/cart_remove.png','moveOrderBill','left');
			$grid_form->addHTML('&nbsp;', 'left');
		}
	}
	if (getModuleByID(9) === null) {
		$grid_form->addButton('Print order bill', '../images/printer.png', 'printOrderBill', 'left');
		$grid_form->addHTML('&nbsp;', 'left');
		$grid_form->addButton('Print delivery notes for currently selected orders', '../images/printer.png', 'printDeliveryNote', 'left');
		
		$grid->addUniqueFilter('payment_type', 'equals', '', 'string');
		$grid->addUniqueFilter('is_pending', 'equals', '', 'string');
		$grid->addUniqueFilter('date_created', 'equals', '', 'string');
		
		$grid_form->addHTML('&nbsp;<font style=\"font-size:12px\">Type:&nbsp;</font> ', 'center');
		$grid_form->addSelectionControl('filter2','b.payment_type', $arr_filter_type, 'center');
		$grid_form->addHTML('&nbsp;<font style=\"font-size:12px\">Status:&nbsp;</font>', 'center');
		$grid_form->addSelectionControl('filter3','is_pending', $arr_pending, 'center');
		$grid_form->addHTML('&nbsp;<font style=\"font-size:12px\">Day:&nbsp;</font>', 'center');
		$grid_form->addSelectionControl('filter4','date_created', $arr_days, 'center');
		$grid_form->addHTML('&nbsp;', 'center');
		$grid_form->addControl('filter5','center');
	}
	else {
		$grid_form->addButton('Invoice Payments', '../images/money_add.png', 'invoicePayments', 'left');
		$grid_form->addHTML('&nbsp;', 'left');
		$grid_form->addButton('Print Invoice', '../images/printer.png', 'printInvoice', 'left');
		$grid_form->addHTML('&nbsp;', 'left');
		$grid_form->addButton('Export Invoice to PDF', '../images/pdf-icon.png', 'exportInvoice', 'left');
		$grid_form->addHTML('&nbsp;', 'left');
		$grid_form->addButton('Export Mantra Invoice to PDF', '../images/pdf-icon.png', 'exportMantra', 'left');
		$grid_form->addHTML('&nbsp;', 'left');
		$grid_form->addButton('Print proforma invoice for the selected order', '../images/printer.png', "printProformaInvoice()", 'left');
		$grid_form->addHTML('&nbsp;', 'left');
		$grid_form->addButton('Export Proforma to PDF', '../images/pdf-icon.png', 'exportProforma', 'left');
		$grid_form->addHTML('&nbsp;', 'left');
		$grid_form->addButton('Print Internal Order', '../images/printer.png', 'printInternalOrder', 'left');
		$grid->addUniqueFilter('is_pending', 'equals', '', 'string');
		
		$grid_form->addHTML('&nbsp;<font style=\"font-size:12px\">Status:&nbsp;</font>', 'center');
		$grid_form->addSelectionControl('filter2','is_pending', $arr_pending, 'center');

		//$grid_form->addHTML('&nbsp;<font style=\"font-size:12px\">Payments:&nbsp;</font>', 'center');
		//$grid_form->addSelectionControl('filter3','total_payments', $arr_payments, 'center');

		$grid_form->addHTML('&nbsp;', 'center');
		$grid_form->addControl('filter3','center');
	}


	$grid_form->addControl('nav','right');
	$grid_form->addControl('refresh','right');
	$grid_form->addControl('view','right');
	$grid_form->addButton('Resize','../images/resize.gif','doResize(1)','right');
	$grid_form->setFrames('order_bills_menu','order_bills_content');
	$grid_form->draw();

	if (!empty($_SESSION["str_order_cancel_message"])) {
		echo "<script language=\"javaScript\">";
		if ($_SESSION['str_order_cancel_error'] === 'ERROR_001') {
			echo "if (confirm('".$_SESSION['str_order_cancel_message'].". Cancel order anyway?')) ";
			echo "document.location = document.location.href+'&action=del&force=Y&delid=".$_SESSION['str_order_cancel_id']."';";
		}
		else
			echo "alert('".$_SESSION["str_order_cancel_message"]."');";
		echo "</script>";
	
		$_SESSION["str_order_cancel_message"] = "";
	}
?>
</form>
</body>
</html>
