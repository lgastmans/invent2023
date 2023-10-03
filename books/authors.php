<?
/**
* 
* @version 	$Id: accounts.php,v 1.1.1.1 2006/02/14 05:03:58 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* @date		09 Dec 2005
* @module 	Currencies Grid
* @name  	authors.php
* 
* This file uses the Grid component to generate the stock grid
*/

$str_cur_module='Books';

require_once("../include/const.inc.php");
require_once("../include/session.inc.php");
require_once("../include/db.inc.php");
require_once "../include/grid.inc.php";

$int_access_level = (getModuleAccessLevel('Books'));

if ($_SESSION["int_user_type"] > 1) {
	$int_access_level = ACCESS_ADMIN;
}

$module_stock = getModule('Books');
if (!($module_stock->monthExists($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']))) {
	dieWithError("This module has no information for the selected month.");
}

$grid = new DBGrid('books_authors');

$grid->addColumn("author", "author", "string", true);

$grid->loadView();

$grid->setQuery("
	SELECT *
	FROM stock_author
");

$grid->setOnClick('gridClick','author_id');
$grid->setSubmitURL('index_authors.php');
$grid->processParameters($_GET);

if (!empty($_GET["action"]))
	if ($_GET["action"]=="del") {
		require("author_delete.php");
	
		$_SESSION['str_message'] = deleteAuthor($_GET["delid"]);
	
		header("Location:authors.php?".$grid->buildQueryString());
		exit;
	}

$_SESSION["int_books_menu_selected"] = 12;
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
			if (confirm("Are you sure you want to delete this author ?")) {
				if (document.location.href.indexOf("?")<0) {
					document.location = document.location.href+"?action=del&delid="+sNo;
				} else {
					document.location = document.location.href+"&action=del&delid="+sNo;
				}
			}
		}
		else
			alert("Select an author to delete");
	}
	
	function newRecord() {
		myWin = window.open("author_edit.php",'author','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=200,top=0');
		myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 200/2));
		myWin.focus();
	}
	
	function modifyRecord() {
		sNo = getSelectedSerialNumber();
		if (sNo>0) {
			myWin = window.open("author_edit.php?id="+intSerialNumber,'author','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=200,top=0');
			myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 200/2));
			myWin.focus();
		}
		else {
			alert("Select an author to modify!");
		}
	}
</script>
</head>

<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>
<?
$grid_form = new GridForm();

$grid_form->setGrid($grid);
if ($int_access_level > ACCESS_READ) {
	$grid_form->addButton('Add new author','../images/new.gif','newRecord','left');
	$grid_form->addButton('Edit author','../images/modify.gif','modifyRecord','left');
	$grid_form->addButton('Delete selected author','../images/delete.gif','deleteRecord','left');
}
$grid_form->addControl('advfilter0','center');
$grid_form->addControl('nav','right');
$grid_form->addControl('view','right');
$grid_form->addControl('refresh','right');
$grid_form->addButton('Resize','../images/resize.gif','doResize(1);','right');
$grid_form->setFrames('author_menu','author_content');
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