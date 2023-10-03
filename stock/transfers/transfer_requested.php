<?
/**
* 
* @version 	$Id: transfer_requested.php,v 1.1.1.1 2006/02/14 05:03:59 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* @date		12 Oct 2005
* @module 	Product View Grid
* @name  	viewstock.php
* 
* This file uses the Grid component to generate the stock grid
*/
  $str_cur_module='Stock';
  require_once("../../include/const.inc.php");

  require_once("../../include/session.inc.php");

 
  require_once("../../include/db.inc.php");
  require_once "../../include/grid.inc.php";

  // context sensitive help
  $_SESSION['str_context_help']='stock/help/transfer_requested.html';


  $int_access_level = (getModuleAccessLevel('Stock'));

  if ($_SESSION["int_user_type"]>1) {	
	$int_access_level = ACCESS_ADMIN;
  } 

  $module_stock = getModule('Stock');
  if (!($module_stock->monthExists($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']))) {
	dieWithError("This module has no information for the selected month.");	
  }


function drawQty($f_field, $f_qry) {
	if ($f_qry->FieldByName("stock_current")+$f_qry->FieldByName("stock_reserved")-$f_qry->FieldByName("stock_ordered") < $f_qry->FieldByName("stock_minimum")) {
 		echo "<font color='red'>".$f_qry->FieldByName($f_field)."</font>";
	} else 
	echo $f_qry->FieldByName($f_field);	
}

function drawProd($f_field, $f_qry) {
	echo "<b>".$f_qry->FieldByName($f_field)."</b>";
}

function drawDir($f_field, $f_qry) {
	if ($f_qry->FieldByName('ssb.storeroom_id_from')==$_SESSION['int_current_storeroom']) {
		echo "<img src='../../images/transfer_outbound.gif' border=0 alt='Outbound Transfer'>";
	} else
		echo "<img src='../../images/transfer_inbound.gif' border=0 alt='Inbound Transfer'>";
}

$grid = new DBGrid('stock_requested');
$grid->addColumn("Dir", "sp.product_id", "custom", false, 20,"drawDir"); 
$grid->addColumn("Product Name", "sp.product_description", "custom", true,150,"drawProd"); 
//$grid->addColumn("Batch Code", "sb.batch_code", "string", true,100); 
$grid->addColumn("Date", "ssb.date_created", "date", true,150); 
$grid->addColumn("Qty", "ssb.transfer_quantity", "number", true,50); 
$grid->addColumn("From", "s1.description", "string", true); 
$grid->addColumn("To", "s2.description", "string", true); 
$grid->addColumn("Description", "ssb.transfer_description", "string", true); 
$grid->addColumn("Type", "st.transfer_type_description", "string", true); 
$grid->addColumn("By", "u.username", "string", true); 
$grid->loadView();

$grid->setQuery("SELECT
	sp.product_id as `sp.product_id`,
	sp.product_code as `sp.product_code`,
	sp.product_description as `sp.product_description`,
	ssb.date_created as `ssb.date_created`,
	ssb.storeroom_id_from as `ssb.storeroom_id_from`,
	ssb.storeroom_id_to as `ssb.storeroom_id_to`,
	ssb.transfer_type as `ssb.trasnfer_type`,
	ssb.transfer_quantity as `ssb.transfer_quantity`,
	ssb.transfer_description as `ssb.transfer_description`,
	ssb.transfer_id as `ssb.transfer_id`,
	ssb.transfer_status as `ssb.transfer_status`,
	u.username as `u.username`,
	s1.description as `s1.description`,
	s2.description as `s2.description`,
	st.transfer_type_description as `st.transfer_type_description`,
	ud.username as `ud.username`,
	ssb.is_deleted as `ssb.is_deleted`
FROM 
	".Monthalize("stock_transfer")." ssb
INNER JOIN
	stock_product sp
ON
	sp.product_id = ssb.product_id
INNER JOIN stock_transfer_type st 
ON	
	st.transfer_type = ssb.transfer_type
LEFT JOIN 
	stock_storeroom s1
ON	
	s1.storeroom_id = ssb.storeroom_id_from
LEFT JOIN 
	stock_storeroom s2
ON	
	s2.storeroom_id = ssb.storeroom_id_to
INNER JOIN
	user u on u.user_id = ssb.user_id
LEFT JOIN
	user ud on ud.user_id = ssb.user_id_dispatched
");
//die ($grid->str_query_string);
$grid->setDeletedField('ssb.is_deleted');
//$grid->setShowDeleted(true);

$grid->setOnClick('gridClick','ssb.transfer_id');
$grid->setSubmitURL('transfer_requested.php');
$grid->addUniqueFilter('ssb.storeroom_id_from','equals',$_SESSION['int_current_storeroom'],'number');
$grid->addUniqueFilter('ssb.storeroom_id_to','equals',$_SESSION['int_current_storeroom'],'number','or');
$grid->addUniqueFilter('ssb.transfer_status','equals',STATUS_REQUESTED,'number');
$grid->addUniqueFilter('sp.deleted','equals','N','string');
$grid->processParameters($_GET);
if ($grid->bool_show_deleted) 
	$grid->addColumn("Deleted By", "ud.username", "string", true); 

//$grid->addUniqueFilter('product_description','start with','','string');


if (!empty($_REQUEST["action"])) {
  if ($_REQUEST["action"]=="del") {
    require("stockrequest.inc.php");
    
    $_SESSION['str_stock_message'] = deleteRecord($_GET["delid"]);
    
    header("Location:transfer_requested.php?".$grid->buildQueryString());
    exit;
  }
/*  if ($_GET["action"]=="dispatch") {
    require("stockrequest.inc.php");
    
    $_SESSION['str_stock_message'] = dispatchRecord($_GET["delid"]);
    
    header("Location:transfer_requested.php?".$grid->buildQueryString());
    exit;
  } */
} 
  $_SESSION['int_stock_selected'] = 5;
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
//  alert(id);
  intSerialNumber = id;
//  forceResize(1,0);
//  parent.frames["stockcontentdetail"].frames["transferescontent"].document.location="stocktransferes.php?id="+id;
//  alert(id);
}



function transferRecord() {
  sNo = getSelectedSerialNumber();
  if (sNo>0) {
  myWin = window.open("transfertransfer_requested.php",'stock','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
  myWin.focus();
  } else alert("Select a transfer to transfer!");

//  return false;
}

function dispatchTransfer() {
  sNo = getSelectedSerialNumber();
  if (sNo>0) {
	myWin = 	window.open("../batchgrid/dispatchbatch.php?id="+sNo,'stock','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
  	myWin.focus();

  } else alert("Select a request to dispatch!");

}

function deleteTransfer() {
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

//  return false;
}

</script>
</head>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>
<?


//$grid->addOrder("category_description","");


//$grid->addFilter("product_code","starts with","3","string");
//$grid->addFilter("category_description","contains","cleaning","string");

$grid_form = new GridForm();

$grid_form->setGrid($grid);
$grid_form->str_stylesheet='../../include/styles.css';
$grid->str_image_path='../../images/';
$grid_form->addControl('advfilter4','center');
if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
  if ($int_access_level > ACCESS_READ) {
  	$grid_form->addButton('Dispatch Selected Request','../../images/export.gif','dispatchTransfer()','left');
  	$grid_form->addButton('Delete Selected Request','../../images/delete.gif','deleteTransfer()','left');
  	if ($int_access_level == ACCESS_ADMIN) {
  		$grid_form->addControl('showdeleted','left');
  	}
  }
}

$grid_form->addControl('print','left');
//$grid_form->addControl('filter1','center');
$grid_form->addControl('nav','right');
$grid_form->addControl('view','right');
$grid_form->addControl('pagesize','righ');
$grid_form->addControl('refresh','right');
$grid_form->setFrames('transfermenu','transfercontent');
$grid_form->draw();

// if there are any stock messages, display them and clear the session message

if (!empty($_SESSION["str_stock_message"])) {
 ?><script language="JavaScript">
 alert("<? echo $_SESSION["str_stock_message"]; ?>");
 </script>
 <?
  $_SESSION["str_stock_message"]="";
 }
?>
<script language="JavaScript">
  forceResize(1,1);

 </script>
</body>
</html>