<?
/**
* 
* @version 	$Id: accounts.php,v 1.1.1.1 2006/02/14 05:03:58 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* @date		09 Dec 2005
* @module 	Currencies Grid
* @name  	currencies.php
* 
* This file uses the Grid component to generate the stock grid
*/

$str_cur_module='Admin';

require_once("../include/const.inc.php");
require_once("../include/session.inc.php");
require_once("../include/db.inc.php");
require_once "../include/grid.inc.php";

$int_access_level = (getModuleAccessLevel('Admin'));

if ($_SESSION["int_user_type"]>1) {	
	$int_access_level = ACCESS_ADMIN;
}

$module_stock = getModule('Admin');
if (!($module_stock->monthExists($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']))) {
	dieWithError("This module has no information for the selected month.");	
}

$grid = new DBGrid('admin_currencies');

$grid->addColumn("currency", "currency_name", "string", true, 150);
$grid->addColumn("rate", "currency_rate", "number", true);

$grid->loadView();

$grid->setQuery("
	SELECT *
	FROM stock_currency
");

$grid->setOnClick('gridClick','currency_id');
$grid->setSubmitURL('currencies.php');
$grid->processParameters($_GET);

if (!empty($_GET["action"]))
	if ($_GET["action"]=="del") {
		require("currency_delete.php");
	
		$_SESSION['str_message'] = deleteCurrency($_GET["delid"]);
	
		header("Location:currencies.php?".$grid->buildQueryString());
		exit;
	}

$_SESSION["int_admin_selected"] = 12;
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
	}

	function deleteRecord() {
		sNo = getSelectedSerialNumber();
		if (sNo>0) {
			if (confirm("Are you sure you want to delete this currency?")) {
				if (document.location.href.indexOf("?")<0) {
					document.location = document.location.href+"?action=del&delid="+sNo;
				} else {
					document.location = document.location.href+"&action=del&delid="+sNo;
				}
			}
		}
		else
			alert("Select a currency to delete");
	}
	
	function newRecord() {
		myWin = window.open("currency_edit.php",'currency','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=200,top=0');
		myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 200/2));
		myWin.focus();
	}
	
	function modifyRecord() {
		sNo = getSelectedSerialNumber();
		if (sNo>0) {
			myWin = window.open("currency_edit.php?id="+intSerialNumber,'currency','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=200,top=0');
			myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 200/2));
			myWin.focus();
		}
		else {
			alert("Select a currency to modify!");
		}
	}
</script>
</head>

<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>
<?
$grid_form = new GridForm();

$grid_form->setGrid($grid);
if ($int_access_level > ACCESS_READ) {
	$grid_form->addButton('Add new currency','../images/new.gif','newRecord','left');
	$grid_form->addButton('Edit currency','../images/modify.gif','modifyRecord','left');
	$grid_form->addButton('Delete selected currency','../images/delete.gif','deleteRecord','left');
}
$grid_form->addControl('advfilter0','center');
$grid_form->addControl('nav','right');
$grid_form->addControl('view','right');
$grid_form->addControl('refresh','right');
$grid_form->addButton('Resize','../images/resize.gif','doResize(1);','right');
$grid_form->setFrames('currency_menu','currency_content');
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