<?
/**
* 
* @version 	$Id: measurement.php,v 1.1.1.1 2006/02/14 05:03:58 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* @date		14 Oct 2005
* @module 	Measurement Unit Master
* @name  	measurement.php
* 
* This file uses the Grid component to generate the measurement unit grid
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

$grid = new Grid();

$grid->addColumn("Measurement Unit", "measurement_unit", "string", true); 
$grid->addColumn("Is Decimal", "is_decimal", "boolean", true); 

$grid->setQuery("SELECT
		*
FROM 
	stock_measurement_unit
");

$grid->setDeletedField('');

$grid->setOnClick('gridClick','measurement_unit_id');
$grid->setSubmitURL('measurement.php');

$grid->processParameters($_GET);

if (!empty($_GET["action"])) 
  if ($_GET["action"]=="del") {
    require("measurementdelete.php");
    
    $_SESSION['str_admin_message'] = deleteRecord($_GET["delid"]);
    
    header("Location:measurement.php?".$grid->buildQueryString());
    exit;
  }
  $_SESSION['int_admin_selected']=1;

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

function newRecord() {
  myWin = window.open("viewmeasurement.php",'stock','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
  myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 550/2));
  myWin.focus();
}

function openRecord() {
  myWin = window.open("viewmeasurement.php?id="+intSerialNumber,'stock','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
  myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 550/2));
  myWin.focus();
}

function modifyRecord() {
  sNo = getSelectedSerialNumber();
  if (sNo>0) {
    openRecord(sNo);
  } else alert("Select a record to modify!");
}


function deleteRecord() {
  sNo = getSelectedSerialNumber();
  if (sNo>0) {
      if (confirm("Are you sure you want to delete record "+sNo+"?")) {

        if (document.location.href.indexOf("?")<0) {
          document.location = document.location.href+"?action=del&delid="+sNo;
	} else {
	  document.location = document.location.href+"&action=del&delid="+sNo;
	}
      }
  } else alert("Select a record to delete!");
}


</script>
</head>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>
<?

$grid_form = new GridForm();

$grid_form->setGrid($grid);

if ($int_access_level > ACCESS_READ) {
	$grid_form->addButton('New Record','../images/new.gif','newRecord','left');
	$grid_form->addButton('Edit Record','../images/modify.gif','modifyRecord','left');
	
	if ($int_access_level==ACCESS_ADMIN) {
		$grid_form->addButton('Delete Record','../images/delete.gif','deleteRecord','left');

	}
}
$grid_form->addControl('filter0','center');
//$grid_form->addControl('advfilter1','center');
$grid_form->addControl('nav','right');
$grid_form->addControl('refresh','right');
$grid_form->setFrames('adminmenu','admincontent');
$grid_form->draw();

// if there are any stock messages, display them and clear the session message

if (!empty($_SESSION["str_admin_message"])) {
 ?><script language="JavaScript">
 alert("<? echo $_SESSION["str_admin_message"]; ?>");
 </script>
 <?
  $_SESSION["str_admin_message"]="";
 }
?>
</body>
</html>