<?
/**
* 
* @version 	$Id: tax.php,v 1.1.1.1 2006/02/14 05:03:58 cvs Exp $
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

  function deleteRecord($f_record_id) {
  
  	$delQuery = new Query("select * from ".Monthalize('stock_tax')." where tax_id=$f_record_id");
	if ($delQuery->RowCount()==0) 
		return "Cannot find record.";
	//
	// check to see if there is something with a balance
	//
  	$delQuery -> Query("select * from stock_product where tax_id=$f_record_id");
	if ($delQuery -> RowCount()>0) {
		return "There are products that use this tax.  Please disassociate them before deleting the tax.";
	}
  	$delQuery -> Query("select * from stock_batch where is_active='Y' and tax_id=$f_record_id");
	if ($delQuery -> RowCount()>0) {
		return "There are active batches that use this tax.  Please deactivate batches first.";
	}

	$delQuery->ExecuteQuery("DELETE from ".Monthalize('stock_tax')." where tax_id=$f_record_id");
	$delQuery->ExecuteQuery("DELETE from ".Monthalize('stock_tax_links')." where tax_id=$f_record_id");
	$delQuery->Free();
	
	return "Deleted record $f_record_id";
  } 

function drawTax($f_field, $f_qry) {
	echo "<b>".$f_qry->FieldByName($f_field)."</b>";
}
$grid = new Grid();

$grid->addColumn("Tax Category", "tax_description", "string", true); 

$grid->setQuery("SELECT * FROM ".Monthalize('stock_tax')." st");

$grid->setDeletedField('');

$grid->setOnClick('gridClick','tax_id');
$grid->setSubmitURL('tax.php');
//$grid->addUniqueFilter('parent_tax_id','equals',0,'number');

$grid->processParameters($_GET);

if (!empty($_GET["action"])) 
  if ($_GET["action"]=="del") {
   
    $_SESSION['str_admin_message'] = deleteRecord($_GET["delid"]);
    
    header("Location:tax.php?".$grid->buildQueryString());
    exit;
  }
  $_SESSION['int_admin_selected']=3;

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
//  alert(id);
  intSerialNumber = id;
  forceResize(1,0);
  parent.frames["admincontentdetail"].frames["detailscontent"].document.location="taxdetails.php?id="+id;
//  alert(id);
}

function doResize(win) {

	if (parent.frames["admincontentdetail"].innerWidth)
	{
		frameWidth = parent.frames["admincontentdetail"].innerWidth;
		frameHeight = parent.frames["admincontentdetail"].innerHeight;
	}
	else if (parent.frames["admincontentdetail"].document.documentElement && parent.frames["admincontentdetail"].document.documentElement.clientWidth)
	{
		frameWidth = parent.frames["admincontentdetail"].document.documentElement.clientWidth;
		frameHeight = parent.frames["admincontentdetail"].document.documentElement.clientHeight;
	}
	else if (parent.frames["admincontentdetail"].document.body)
	{
		frameWidth = parent.frames["admincontentdetail"].document.body.clientWidth;
		frameHeight = parent.frames["admincontentdetail"].document.body.clientHeight;
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

	if (parent.frames["admincontentdetail"].innerWidth)
	{
		frameWidth = parent.frames["admincontentdetail"].innerWidth;
		frameHeight = parent.frames["admincontentdetail"].innerHeight;
	}
	else if (parent.frames["admincontentdetail"].document.documentElement && parent.frames["admincontentdetail"].document.documentElement.clientWidth)
	{
		frameWidth = parent.frames["admincontentdetail"].document.documentElement.clientWidth;
		frameHeight = parent.frames["admincontentdetail"].document.documentElement.clientHeight;
	}
	else if (parent.frames["admincontentdetail"].document.body)
	{
		frameWidth = parent.frames["admincontentdetail"].document.body.clientWidth;
		frameHeight = parent.frames["admincontentdetail"].document.body.clientHeight;
	}

if (win==1) {
  
  if (asize == "1") {
    parent.document.body.rows="30,*,1";
  } else parent.document.body.rows="30,*,150";
 
}
}

function newRecord() {
  myWin = window.open("viewtax.php",'tax','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
  myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 550/2));
  myWin.focus();
//  return false;
}

function openRecord() {

  myWin = window.open("viewtax.php?id="+intSerialNumber,'tax','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
  myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 550/2));
  myWin.focus();
//  return false;
}

function modifyRecord() {
  sNo = getSelectedSerialNumber();
  if (sNo>0) {
    openRecord(sNo);
  } else alert("Select a record to modify!"+sNo);
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
$grid_form->addControl('filter1','center');
//$grid_form->addControl('advfilter1','center');
$grid_form->addControl('nav','right');
$grid_form->addControl('refresh','right');
$grid_form->addButton('Resize','../images/resize.gif','doResize(1);','right');
$grid_form->setFrames('adminmenu','admincontent');
$grid_form->draw();

// if there are any stock messages, display them and clear the session message

if (!empty($_SESSION["str_admin_message"])) { ?>
	<script language="JavaScript">
		alert("<? echo $_SESSION["str_admin_message"]; ?>");
	</script>
<?
	$_SESSION["str_admin_message"]="";
}
?>
</body>
</html>