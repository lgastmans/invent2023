<?
/**
*
* @version 		$Id: bills.php,v 1.6 2006/02/25 06:29:23 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author		Luk Gastmans
* @date			12 Oct 2005
* @module 		Return to section Grid
* @name  		rts.php
*
* This file uses the Grid component to generate the return to section grid
*/

	$str_cur_module='Stock';

	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");
	require_once("../../include/grid.inc.php");

	$int_access_level = (getModuleAccessLevel('Stock'));

	$_SESSION["int_stock_selected"] = 5;

	if ($_SESSION["int_user_type"] > 1) {
		$int_access_level = ACCESS_ADMIN;
	}

	$module_billing = getModule('Stock');
	if (!($module_billing->monthExists($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']))) {
		dieWithError("This module has no information for the selected year.");
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
			default:
				echo "Unknown";
				break;
		}
	}

$grid = new DBGrid('rts_main');

$grid->addColumn("Receipt", "bill_number", "string", true, 50);
$grid->addColumn("Date", "date_created", "date", false, 100);
$grid->addColumn("Amount", "total_amount", "number", false, 100);
$grid->addColumn("Discount", "discount", "number", false, 100);
$grid->addColumn("Description", "description", "string", false, 100);
$grid->addColumn("Status", "bill_status", "custom", true, 50, 'getBillStatus');
$grid->addColumn("User", "username", "string", true, 150);
$grid->addColumn("Supplier", "supplier_name", "string", true, 150);

$grid->loadView();

$grid->setQuery("SELECT
	sr.stock_rts_id,
	sr.storeroom_id,
	sr.bill_number,
	sr.date_created,
	sr.total_amount,
	sr.discount,
	sr.description,
	sr.bill_status,
	sr.user_id,
	sr.supplier_id,
	user.username,
	ss.supplier_name
FROM
	".Monthalize('stock_rts')." sr
INNER JOIN user u ON (user.user_id = sr.user_id)
INNER JOIN stock_supplier ss ON (sr.supplier_id = ss.supplier_id)
");

$grid->processParameters($_GET);
$grid->addUniqueFilter('sr.storeroom_id', 'equals', $_SESSION["int_current_storeroom"], 'number');

$grid->setOnClick('gridClick','stock_rts_id');
$grid->setSubmitURL('rts.php');

if (!empty($_GET["action"]))
	if ($_GET["action"]=="del") {
		require("rts_cancel.php");

		$_SESSION['str_message'] = cancelReceipt($_GET["delid"]);

		header("Location:rts.php?".$grid->buildQueryString());
		exit;
	}
?>

<html>
<head><TITLE></TITLE>
<link href="../../include/styles.css" rel="stylesheet" type="text/css">

	<script language='javascript'>

		var intSerialNumber=0;

		function getSelectedSerialNumber() {
			return intSerialNumber;
		}

		function gridClick(id) {
			intSerialNumber = id;
			forceResize(1,0);
			parent.frames["rts_content_detail"].frames["items_content"].document.location="rts_details.php?id="+id;
		}

		function doResize(win) {

			if (parent.frames["rts_content_detail"].innerWidth)
			{
				frameWidth = parent.frames["rts_content_detail"].innerWidth;
				frameHeight = parent.frames["rts_content_detail"].innerHeight;
			}
			else if (parent.frames["rts_content_detail"].document.documentElement && parent.frames["rts_content_detail"].document.documentElement.clientWidth)
			{
				frameWidth = parent.frames["rts_content_detail"].document.documentElement.clientWidth;
				frameHeight = parent.frames["rts_content_detail"].document.documentElement.clientHeight;
			}
			else if (parent.frames["rts_content_detail"].document.body)
			{
				frameWidth = parent.frames["rts_content_detail"].document.body.clientWidth;
				frameHeight = parent.frames["rts_content_detail"].document.body.clientHeight;
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

			if (parent.frames["rts_content_detail"].innerWidth)
			{
				frameWidth = parent.frames["rts_content_detail"].innerWidth;
				frameHeight = parent.frames["rts_content_detail"].innerHeight;
			}
			else if (parent.frames["rts_content_detail"].document.documentElement && parent.frames["rts_content_detail"].document.documentElement.clientWidth)
			{
				frameWidth = parent.frames["rts_content_detail"].document.documentElement.clientWidth;
				frameHeight = parent.frames["rts_content_detail"].document.documentElement.clientHeight;
			}
			else if (parent.frames["rts_content_detail"].document.body)
			{
				frameWidth = parent.frames["rts_content_detail"].document.body.clientWidth;
				frameHeight = parent.frames["rts_content_detail"].document.body.clientHeight;
			}

			if (win==1) {
				if (asize == "1") {
					parent.document.body.rows="30,*,1";
				}
				else
					parent.document.body.rows="30,*,150";

			}
		}

		function cancelReceipt() {
			sNo = getSelectedSerialNumber();
			if (sNo>0) {
				if (confirm("Are you sure you want to cancel receipt number with id "+sNo+"?")) {
					if (document.location.href.indexOf("?")<0) {
						document.location = document.location.href+"?action=del&delid="+sNo;
					} else {
						document.location = document.location.href+"&action=del&delid="+sNo;
					}
				}
			}
			else
				alert("Select a receipt to cancel");
		}

		function newReceipt() {
			myWin = window.open("receipt_frameset.php?action=clear_receipt",'create_receipt','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=800,height=600,top=0');
			myWin.moveTo((screen.availWidth/2 - 800/2), (screen.availHeight/2 - 600/2));
			myWin.focus();
		}

		function printReceipt() {
			sNo = getSelectedSerialNumber();
			if (sNo > 0) {
				myWin = window.open("print_receipt.php?id="+sNo, 'printwin', 'width=800,height=500,resizable=yes');
			}
			else
				alert('Select a receipt to print');
		}

	</script>

</head>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>

<?
	$grid_form = new GridForm();
	$grid->prepareQuery();
	$grid_form->setGrid($grid);
	$grid_form->str_stylesheet='../../include/styles.css';
	$grid->str_image_path='../../images/';
	if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
		$grid_form->addButton('New Receipt','../../images/new.gif','newReceipt','left');
		$grid_form->addButton('Cancel Receipt','../../images/delete.gif','cancelReceipt','left');
	}
	$grid_form->addButton('Print Receipt', '../../images/print.gif', 'printReceipt', 'left');
	$grid_form->addControl('advfilter2','center');

	$grid_form->addControl('nav','right');
	$grid_form->addControl('refresh','right');
	$grid_form->addControl('view','right');
	$grid_form->addButton('Resize','../../images/resize.gif','doResize(1)','right');
	$grid_form->setFrames('rts_menu','rts_content');
	$grid_form->draw();

if (!empty($_SESSION["str_message"])) {
	echo "<script language=\"javaScript\">";
	echo "alert('".$_SESSION["str_message"]."');";
	echo "</script>";

	$_SESSION["str_message"]="";
 }
?>

</body>
</html>