<?
/**
*
* @version 		$Id: purchase.php,v 1.3 2006/02/20 03:58:37 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author		Luk Gastmans
* @date			12 Oct 2005
* @module 		Purchase View Grid
* @name  		purchase.php
*
* This file uses the Grid component to generate the purchase order grid
*/

	$str_cur_module='Purchase';

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once "../include/grid.inc.php";

	if (IsSet($_GET["cur_selected"]))
		$_SESSION['int_purchase_menu_selected'] = $_GET["cur_selected"];

	if (IsSet($_GET["status"])) {
		$status = $_GET["status"];
		$_SESSION["status"] = $status;
	}

	$int_access_level = (getModuleAccessLevel('Purchase'));
	if ($_SESSION["int_user_type"]>1) {
		$int_access_level = ACCESS_ADMIN;
	}

	$module_purchase = getModule('Purchase');
	if (!($module_purchase->yearExists($_SESSION["int_month_loaded"], $_SESSION['int_year_loaded']))) {
		dieWithError("This module has no information for the selected year.");
	}



$grid = new DBGrid('purchase_main');

$grid->addColumn("Order Number", "purchase_order_ref", "string", true,100);
$grid->addColumn("Created On", "date_created", "date", true);
$grid->addColumn("Expected", "date_expected_delivery"	, "date", true);
$grid->addColumn("Received", "date_received", "date", true);
$grid->addColumn("Supplier", "supplier_name", "string", true);
$grid->addColumn("Assigned to", "assigned_user", "string", true);
$grid->addColumn("User", "username", "string", true);

$grid->loadView();

$grid->setQuery("
SELECT
	po.supplier_id,
	po.purchase_order_id,
	po.purchase_status,
	po.date_created,
	po.date_expected_delivery,
	po.date_received,
	po.purchase_order_ref,
	po.user_id,
	po.assigned_to_user_id,
	po.storeroom_id,
	user.username,
	u2.username AS assigned_user,
	sp.supplier_name
FROM
	".Yearalize('purchase_order')." po
INNER JOIN
	user ON (user.user_id = po.user_id)
LEFT JOIN
	user u2 ON (u2.user_id = po.assigned_to_user_id)
LEFT JOIN
	stock_supplier sp ON (sp.supplier_id = po.supplier_id)");

$grid->processParameters($_GET);
$grid->addUniqueFilter('po.purchase_status', 'equals', $_SESSION["status"], 'number');
$grid->addUniqueFilter('po.storeroom_id', 'equals', $_SESSION["int_current_storeroom"], 'number');

$grid->setOnClick('gridClick','purchase_order_id');
$grid->setSubmitURL('purchase.php');

if (!empty($_GET["action"]))
	if ($_GET["action"]=="del") {
		require("purchase_delete.php");

		$_SESSION['str_purchase_message'] = deleteRecord($_GET["delid"], $_SESSION["status"]);

		header("Location:purchase.php?cur_selected=".$_SESSION["int_purchase_menu_selected"]."&status=".$_SESSION["status"]."&".$grid->buildQueryString());
		exit;
	}

$str_purchase_selected=1;
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
  parent.frames["purchase_content_detail"].frames["items_content"].document.location="purchase_items.php?id="+id;
}

function doResize(win) {

	if (parent.frames["purchase_content_detail"].innerWidth)
	{
		frameWidth = parent.frames["purchase_content_detail"].innerWidth;
		frameHeight = parent.frames["purchase_content_detail"].innerHeight;
	}
	else if (parent.frames["purchase_content_detail"].document.documentElement && parent.frames["purchase_content_detail"].document.documentElement.clientWidth)
	{
		frameWidth = parent.frames["purchase_content_detail"].document.documentElement.clientWidth;
		frameHeight = parent.frames["purchase_content_detail"].document.documentElement.clientHeight;
	}
	else if (parent.frames["purchase_content_detail"].document.body)
	{
		frameWidth = parent.frames["purchase_content_detail"].document.body.clientWidth;
		frameHeight = parent.frames["purchase_content_detail"].document.body.clientHeight;
	}

if (win==1) {

  if (frameHeight != "1") {
    parent.document.body.rows="30,*,1";
  } else parent.document.body.rows="30,*,150";

 } else {
  if (frameHeight <= 150) {
    parent.document.body.rows="1,1,*";
  } else parent.document.body.rows="30,*,150";

 }
}

function forceResize(win,asize) {

	if (parent.frames["purchase_content_detail"].innerWidth)
	{
		frameWidth = parent.frames["purchase_content_detail"].innerWidth;
		frameHeight = parent.frames["purchase_content_detail"].innerHeight;
	}
	else if (parent.frames["purchase_content_detail"].document.documentElement && parent.frames["purchase_content_detail"].document.documentElement.clientWidth)
	{
		frameWidth = parent.frames["purchase_content_detail"].document.documentElement.clientWidth;
		frameHeight = parent.frames["purchase_content_detail"].document.documentElement.clientHeight;
	}
	else if (parent.frames["purchase_content_detail"].document.body)
	{
		frameWidth = parent.frames["purchase_content_detail"].document.body.clientWidth;
		frameHeight = parent.frames["purchase_content_detail"].document.body.clientHeight;
	}

if (win==1) {

  if (asize == "1") {
    parent.document.body.rows="30,*,1";
  } else parent.document.body.rows="30,*,150";

}
}


function newRecord() {
	myWin = window.open("po_view_header_new.php",'purchase_order','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=900,height=550,top=0');
	myWin.focus();
}

function newPurchaseOrder() {
	myWin = window.open("purchase_order_frameset.php?action=clear",'purchase_order_frameset','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=900,height=550,top=0');
	myWin.moveTo((screen.availWidth/2 - 900/2), (screen.availHeight/2 - 550/2));
	myWin.focus();
}

function openRecord() {
	sNo = getSelectedSerialNumber();
	if (sNo>0) {
  		myWin = window.open("purchase_order_frameset.php?id="+intSerialNumber,'purchase_order','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=900,height=550,top=0');
		myWin.moveTo((screen.availWidth/2 - 900/2), (screen.availHeight/2 - 550/2));
  		myWin.focus();
	}
	else
		alert("Select a purchase order to modify");
}

function receiveRecord() {
	sNo = getSelectedSerialNumber();
	if (sNo>0) {
		w = 800;
		h = 600;
		if (window.screen) {
			w = window.screen.availWidth;
			h = window.screen.availHeight;
		}
  		myWin = window.open("purchase_receive.php?id="+intSerialNumber,'purchase_order','fullscreen=yes,toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,top=0,width='+w+',height='+h);
  		myWin.focus();
	}
	else
		alert("Select a purchase order to modify");
}

function receive_order() {
	sNo = getSelectedSerialNumber();
	if (sNo>0) {
		w = 800;
		h = 600;
		if (window.screen) {
			w = window.screen.availWidth;
			h = window.screen.availHeight;
		}
  		myWin = window.open("receive_purchase_order.php?id="+intSerialNumber,'purchase_order','fullscreen=yes,toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,top=0,width='+w+',height='+h);
  		myWin.focus();
	}
	else
		alert("Select a purchase order to modify");}

function deleteRecord() {
	sNo = getSelectedSerialNumber();
	if (sNo>0) {
		if (confirm("Are you sure you want to delete purchase order "+sNo+"?")) {
			if (document.location.href.indexOf("?")<0) {
				document.location = document.location.href+"?action=del&delid="+sNo;
			} else {
				document.location = document.location.href+"&action=del&delid="+sNo;
			}
		}
	}
	else
		alert("Select a record to delete!");
}

function printPurchaseOrder() {
	sNo = getSelectedSerialNumber();
	if (sNo>0) {
		window.open("po_list_print.php?po_id="+sNo, "purchase_order_print", 'fullscreen=yes,toolbar=no,location=no,directories=no,status=no,menubar=yes,scrollbars=yes,resizable=no');
	}
	else
		alert('Select a purchase order to print');
}

function exportOrder() {
	sNo = getSelectedSerialNumber();
	if (sNo > 0) {
		myWin = window.open("export_order.php?id="+sNo, 'genOrderPDF', 'width=800,height=500,resizable=yes,menubar=yes'); 
	}
	else
		alert('Select a purchase order');
}

function exportCSV() {
	sNo = getSelectedSerialNumber();
	if (sNo > 0) {
		myWin = window.open("export_po_csv.php?id="+sNo, 'export_po_csv', 'width=800,height=500,resizable=yes,menubar=yes'); 
	}
	else
		alert('Select a purchase order');
}

function cancelRecord() {
	sNo = getSelectedSerialNumber();
	if (sNo>0) {
		if (confirm("Are you sure you want to cancel purchase order "+sNo+"?")) {
			if (document.location.href.indexOf("?")<0) {
				document.location = document.location.href+"?action=del&delid="+sNo;
			} else {
				document.location = document.location.href+"&action=del&delid="+sNo;
			}
		}
	}
	else
		alert("Select a record to delete!");
}

</script>


</head>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>

<?
$grid_form = new GridForm();
$grid->prepareQuery();
$grid_form->setGrid($grid);
if ($_SESSION["status"]==PURCHASE_DRAFT) {
	if ($int_access_level > ACCESS_READ) {
//		$grid_form->addButton('New Order','../images/new.gif','newRecord','left');
		$grid_form->addButton('New Order','../images/new.gif','newPurchaseOrder','left');
	}
}

if ($_SESSION["status"]==PURCHASE_DRAFT) {
	if ($int_access_level > ACCESS_READ) {
		$grid_form->addButton('Edit Order','../images/modify.gif','openRecord','left');
	}
}
else if ($_SESSION["status"]==PURCHASE_SENT) {
	if ($int_access_level > ACCESS_READ) {
		if ($module_purchase->yearExists($_SESSION["int_month_loaded"], $_SESSION['int_year_loaded'])) {
//			$grid_form->addButton('Receive Order', '../images/modify.gif','receiveRecord','left');
			$grid_form->addButton('Receive Order', '../images/modify.gif','receive_order','left');
		}
	}
}

if ($_SESSION["status"]==PURCHASE_DRAFT) {
	if ($int_access_level > ACCESS_READ) {
		$grid_form->addButton('Delete Order','../images/delete.gif','deleteRecord','left');
	}
}
else if ($_SESSION["status"]==PURCHASE_SENT) {
	if ($int_access_level > ACCESS_READ) {
		$grid_form->addButton('Cancel Order','../images/delete.gif','cancelRecord','left');
	}
}

$grid_form->addButton('Print Purchase Order', '../images/print.gif', 'printPurchaseOrder()', 'left');
$grid_form->addButton('PDF', "../images/pdf-icon.png", 'exportOrder()', 'left');
$grid_form->addButton('CSV', "../images/csv_export.png", 'exportCSV()', 'left');


$grid_form->addControl('advfilter1','center');
$grid_form->addControl('nav','right');
$grid_form->addControl('refresh','right');
$grid_form->addControl('view','right');
$grid_form->addButton('Resize','../images/resize.gif','doResize(1)','right');
$grid_form->setFrames('purchase_menu','purchase_content');
$grid_form->draw();

if (!empty($_SESSION["str_purchase_message"])) {?>
	<script language="JavaScript">
		alert("<? echo $_SESSION["str_purchase_message"]; ?>");
	</script>
<?
	$_SESSION["str_purchase_message"]="";
 }
?>


</body>
</html>