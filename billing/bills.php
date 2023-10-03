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

	$str_cur_module='Billing';

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once "../include/grid.inc.php";

	$int_access_level = (getModuleAccessLevel('Billing'));

	$_SESSION["int_bills_menu_selected"] = 1;

	if ($_SESSION["int_user_type"] > 1) {
		$int_access_level = ACCESS_ADMIN;
	}

	$module_billing = getModule('Billing');
	if (!($module_billing->monthExists($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']))) {
		dieWithError("This module has no information for the selected year.");
	}

	// get which types that can be billed
	$qry = new Query("
		SELECT can_bill_cash, can_bill_fs_account, can_bill_pt_account
		FROM stock_storeroom
		WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")
	");
	$bool_cash = false;
	$bool_fs = false;
	$bool_pt = false;
	$bool_transfer = false;
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
	if (CAN_BILL_TRANSFER_GOOD === 1)
		$arr_filter_type['Transfer of Goods'] = 6;
	
	$qry->Query("SELECT order_show_bills FROM user_settings WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")");
	$str_show_bills = 'N';
	if ($qry->RowCount() > 0)
		$str_show_bills = $qry->FieldByName('order_show_bills');

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
		switch ($f_qry->FieldByName('module_id')) {
			case 2:
				$str_type = 'Bill';
				break;
			case 7:
				$str_type = 'Order';
				break;
			default:
				$str_type = 'unknown';
				break;
		}

		switch ($f_qry->FieldByName($f_field)) {
			case BILL_CASH:
				echo "Cash / ".$str_type;
				break;
			case BILL_ACCOUNT:
				echo "Account / ".$str_type;
				break;
			case BILL_PT_ACCOUNT:
				echo "PT Account / ".$str_type;
				break;
			case BILL_CREDIT_CARD:
				echo "Credit Card / ".$str_type;
				break;
			case BILL_CHEQUE:
				echo "Cheque / ".$str_type;
				break;
			case BILL_TRANSFER_GOOD:
				echo "Transfer / ".$str_type;
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
				$str_username = $f_qry->FieldByName('cancelled_username');
				$str_reason = '';
				if ($str_username <> "")
					$str_reason .= $str_username;
				else
					$str_reason .= "&lt;unknown&gt;";
	
				if ($f_qry->FieldByName('cancelled_reason') <> "")
					$str_reason .= " for ".$f_qry->FieldByName('cancelled_reason');
				else
					$str_reason .= " reason not specified";
				echo "<font color='red'>Cancelled by ".$str_reason."</font>";
				break;
			default:
				echo "Unknown";
				break;
		}
	}

	function getTotalAmount($f_field, $f_qry) {
		echo sprintf("%01.2f", $f_qry->FieldByName($f_field));
	}

	function drawDate($f_field, $f_qry) {
		echo makeHumanTime($f_qry->FieldByName($f_field));
	}
	
	function drawBillNumber($f_field, $f_qry) {
		if ($f_qry->FieldByName('is_debit_bill') == 'Y')
			echo "<font color='red'>".$f_qry->FieldByName($f_field)."</font>";
		else
			echo $f_qry->FieldByName($f_field);
	}

$grid = new DBGrid('billing_main');

$grid->addColumn("Bill", "bill_number", "custom", true, 50, 'drawBillNumber');
$grid->addColumn("Date", "date_created", "custom", true, 100, 'drawDate');
$grid->addColumn("FS Account", "account_number", "string", true, 70);
$grid->addColumn("FS Name", "account_name", "string", true, 200);
$grid->addColumn("Aurocard", "aurocard_number", "string", true, 70);
$grid->addColumn("Amount", "total_amount", "custom", true, 100, 'getTotalAmount');
$grid->addColumn("Type", "payment_type", "custom", true, 100, 'getPaymentType');
$grid->addColumn("Ref", "payment_type_number", "string", true, 100);
$grid->addColumn("Promotion", "bill_promotion", "string", true, 100);
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
	b.module_id,
	b.cancelled_reason,
	b.account_number,
	b.account_name,
	b.is_debit_bill,
	b.aurocard_number,
	user.username,
	u2.username AS cancelled_username
FROM
	".Monthalize('bill')." b
INNER JOIN user ON (user.user_id = b.user_id)
	LEFT JOIN user u2 ON (u2.user_id = b.cancelled_user_id)
");

$grid->addUniqueFilter('storeroom_id', 'equals', $_SESSION["int_current_storeroom"], 'number');

if ($str_show_bills == 'N')
	$grid->addUniqueFilter('module_id', 'equals', 2, 'number');
	
$grid->addOrder('date_created', 'DESC');
$grid->setOnClick('gridClick','bill_id');
$grid->setSubmitURL('bills.php');

if (!empty($_GET["action"]))
	if ($_GET["action"]=="del") {
		require("bill_cancel.php");
		
		$str_retval = cancelBill($_GET["delid"], $_GET['reason'], 2); // 2 = bill module id
		
		$arr_retval = explode('|', $str_retval);
		$_SESSION['str_bill_cancel_message'] = $arr_retval[1];
		
		header("Location:bills.php?".$grid->buildQueryString());
		exit;
	}
?>
<? if (empty($_GET['export'])) { ?><html>
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
			parent.frames["billing_content_detail"].frames["items_content"].document.location="bill_items.php?id="+id;
		}

		function doResize(win) {

			if (parent.frames["billing_content_detail"].innerWidth)
			{
				frameWidth = parent.frames["billing_content_detail"].innerWidth;
				frameHeight = parent.frames["billing_content_detail"].innerHeight;
			}
			else if (parent.frames["billing_content_detail"].document.documentElement && parent.frames["billing_content_detail"].document.documentElement.clientWidth)
			{
				frameWidth = parent.frames["billing_content_detail"].document.documentElement.clientWidth;
				frameHeight = parent.frames["billing_content_detail"].document.documentElement.clientHeight;
			}
			else if (parent.frames["billing_content_detail"].document.body)
			{
				frameWidth = parent.frames["billing_content_detail"].document.body.clientWidth;
				frameHeight = parent.frames["billing_content_detail"].document.body.clientHeight;
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

			if (parent.frames["billing_content_detail"].innerWidth)
			{
				frameWidth = parent.frames["billing_content_detail"].innerWidth;
				frameHeight = parent.frames["billing_content_detail"].innerHeight;
			}
			else if (parent.frames["billing_content_detail"].document.documentElement && parent.frames["billing_content_detail"].document.documentElement.clientWidth)
			{
				frameWidth = parent.frames["billing_content_detail"].document.documentElement.clientWidth;
				frameHeight = parent.frames["billing_content_detail"].document.documentElement.clientHeight;
			}
			else if (parent.frames["billing_content_detail"].document.body)
			{
				frameWidth = parent.frames["billing_content_detail"].document.body.clientWidth;
				frameHeight = parent.frames["billing_content_detail"].document.body.clientHeight;
			}

			if (win==1) {
				if (asize == "1") {
					parent.document.body.rows="30,*,1";
				}
				else
					parent.document.body.rows="30,*,150";

			}
		}

		function cancelBill() {
			sNo = getSelectedSerialNumber();
			if (sNo>0) {
				if (confirm("Are you sure you want to cancel this bill?")) {
					
					var str_reply = prompt("Please give a reason for cancelling the bill", "");
					
					if (document.location.href.indexOf("?")<0) {
						document.location = document.location.href+"?action=del&delid="+sNo+"&reason="+str_reply;
					} else {
						document.location = document.location.href+"&action=del&delid="+sNo+"&reason="+str_reply;
					}
				}
			}
			else
				alert("Select a bill to cancel");
		}

/*		function editBill() {
			sNo = getSelectedSerialNumber();
			if (sNo>0) {
				str = "billing_frameset.php?id=" + sNo;
				myWin = window.open(str,'create_bill','toolbar=no,location=no,directories=no,status=yes,fullscreen=no,menubar=no,scrollbars=yes,resizable=yes,width=' + screen.availWidth + ',height=' + screen.availHeight + ',top=0,left=0');
				myWin.MoveTo(0,0);
				myWin.focus();
			}
			else
				alert("Select a bill to modify");
		}
*/		

		function debitBill() {
			myWin = window.open("debit/billing_frameset.php?action=clear_bill",'reverse_bill','toolbar=no,location=no,directories=no,status=yes,fullscreen=no,menubar=no,scrollbars=yes,resizable=yes');
			myWin.MoveTo(0,0);
			myWin.focus();
		}

		function newBill() {
			myWin = window.open("billing_frameset.php?action=clear_bill",'create_bill','toolbar=no,fullscreen=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes');
			myWin.moveTo(0,0);
			myWin.focus();
		}

		function printBill() {
			sNo = getSelectedSerialNumber();
			if (sNo > 0) {
				myWin = window.open("print_bill.php?id="+sNo, 'printwin', 'width=800,height=500,resizable=yes'); //'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,width=450,height=250');
			}
			else
				alert('Select a bill to print');
		}

		function exportInvoice() {
			//aFilename = '<?echo $str_invoice_filename;?>';
			sNo = getSelectedSerialNumber();
			if (sNo > 0) {
				myWin = window.open("export_invoice.php?id="+sNo, 'printwin', 'width=800,height=500,resizable=yes,menubar=yes'); 
			}
			else
				alert('Select a bill to print');
		}

	</script>

</head>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>

<? } ?>
<?

  $grid->processParameters($_GET);
  
  $grid->addUniqueFilter('date_created', 'equals', '', 'string');
  $grid->addUniqueFilter('payment_type', 'equals', '', 'string');

	$grid_form = new GridForm();
	$grid->prepareQuery();
	$grid_form->setGrid($grid);

	// cancelling and creating new bills only in current month/year
	if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
		if ($int_access_level > ACCESS_READ) {
			$grid_form->addButton('New Bill','../images/new.gif','newBill','left');
			$grid_form->addButton('Debit (Reverse) Bill', '../images/modify.gif', 'debitBill', 'left');
			$grid_form->addButton('Cancel Bill','../images/delete.gif','cancelBill','left');
		}
	}
	$grid_form->addButton('Print Bill', '../images/print.gif', 'printBill', 'left');
	$grid_form->addControl('export', 'left');

	$grid_form->addButton('Export Invoice to PDF', '../images/pdf-icon.png', 'exportInvoice', 'left');

	
	if ($str_show_bills == 'N') {
		$grid_form->addHTML('&nbsp;<font style=\"font-size:12px\">Day:&nbsp;</font>', 'center');
		$grid_form->addSelectionControl('filter2','date_created', $arr_days, 'center');
		$grid_form->addHTML('&nbsp;<font style=\"font-size:12px\">Type:&nbsp;</font> ', 'center');
		$grid_form->addSelectionControl('filter3','payment_type', $arr_filter_type, 'center');
		$grid_form->addHTML('&nbsp;', 'center');
		$grid_form->addControl('advfilter4','center');
	}
	else {
		$grid_form->addHTML('&nbsp;<font style=\"font-size:12px\">Day:&nbsp;</font>', 'center');
		$grid_form->addSelectionControl('filter1','date_created', $arr_days, 'center');
		$grid_form->addHTML('&nbsp;<font style=\"font-size:12px\">Type:&nbsp;</font> ', 'center');
		$grid_form->addSelectionControl('filter2','payment_type', $arr_filter_type, 'center');
		$grid_form->addHTML('&nbsp;', 'center');
		$grid_form->addControl('advfilter3','center');
	}
//	print_r($_GET);

	$grid_form->addControl('nav','right');
	$grid_form->addControl('refresh','right');
	$grid_form->addControl('view','right');
	$grid_form->addButton('Resize','../images/resize.gif','doResize(1)','right');
	$grid_form->setFrames('billing_menu','billing_content');
	$grid_form->draw();

if (!empty($_SESSION["str_bill_cancel_message"])) {
	echo "<script language=\"javaScript\">";
	echo "alert('".$_SESSION["str_bill_cancel_message"]."');";
	echo "</script>";

	$_SESSION["str_bill_cancel_message"]="";
}
?>

<? if (empty($_GET['export'])) { ?>

</body>
</html>
<? } ?>