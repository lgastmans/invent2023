<?
/**
* 
* @version 	$Id: batch.php,v 1.2 2006/02/20 03:58:37 cvs Exp $
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
  $_SESSION['str_context_help']='stock/help/batch.html';


  $int_access_level = (getModuleAccessLevel('Stock'));

  if ($_SESSION["int_user_type"]>1) {	
	$int_access_level = ACCESS_ADMIN;
  } 

  $module_stock = getModule('Stock');
  if (!($module_stock->monthExists($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']))) {
	dieWithError("This module has no information for the selected month.");	
  }


      //==================
      // get user settings
      //------------------
      $qry_settings = new Query("
	    SELECT stock_show_available, bill_decimal_places
	    FROM user_settings
	    WHERE (storeroom_id = ".$_SESSION['int_current_storeroom'].")
      ");
      $str_show_available = 'Y';
      $int_decimal_places = 2;
      if ($qry_settings->RowCount() > 0) {
	    $str_show_available = $qry_settings->FieldByName('stock_show_available');
	    $int_decimal_places = $qry_settings->FieldByName('bill_decimal_places');
      }

function drawQty($f_field, $f_qry) {
      global $int_decimal_places;
      if ($f_qry->FieldByName('is_decimal') == 'Y') {
	    if ($int_decimal_places == 2)
		  echo sprintf("%0.2f",$f_qry->FieldByName($f_field));
	    else
		  echo sprintf("%0.3f",$f_qry->FieldByName($f_field));
      }
      else
	    echo sprintf("%0.0f",$f_qry->FieldByName($f_field));
}

function drawProd($f_field, $f_qry) {
	echo "<b>".$f_qry->FieldByName($f_field)."</b>";
}

$grid = new DBGrid('stock_batch');
$grid->addColumn("Product Code", "sp.product_code", "string", true, 100); 
$grid->addColumn("Product Name", "sp.product_description", "custom", true,150,"drawProd"); 
$grid->addColumn("Category", "sc.category_description", "string", true); 
$grid->addColumn("Batch Code", "sb.batch_code", "string", true,100); 
$grid->addColumn("B. Price", "sb.buying_price", "currency", true); 
$grid->addColumn("S. Price", "sb.selling_price", "currency", true); 
$grid->addColumn("Date", "sb.date_created", "date", true); 
$grid->addColumn("Available", "ssb.stock_available", "custom", false, 50, "drawQty"); 
$grid->addColumn("Unit", "mu.measurement_unit", "string", false, 50); 
$grid->addColumn("Reserved", "ssb.stock_reserved", "custom", false, 50, "drawQty"); 
$grid->addColumn("Ordered", "ssb.stock_ordered", "custom", false, 50, "drawQty"); 
$grid->addColumn("Shelf", "ssb.shelf_id", "number", false); 
$grid->addColumn("Tax", "st.tax_description", "string", false);
$grid->addColumn("Active", "ssb.is_active", "boolean", false);
$grid->addColumn("Supplier", "ss.supplier_name", "string", true);
//$grid->b_debug=true;
$grid->loadView();

$grid->setQuery("SELECT
	sp.product_id as `sp.product_id`,
	sp.product_code as `sp.product_code`,
	sp.product_description as `sp.product_description`,
	sc.category_description as `sc.category_description`,
	sb.batch_code as `sb.batch_code`,
	sb.batch_id as `sb.batch_id`,
	sb.date_created as `sb.date_created`,
	sb.deleted as `sb.deleted`,
	ss.supplier_name as `ss.supplier_name`,
	sb.buying_price as `sb.buying_price`,
	sb.selling_price as `sb.selling_price`,
	sb.tax_id as `sb.tax_id`,
	st.tax_description as `st.tax_description`,
	ssb.shelf_id as `ssb.shelf_id`,
	ssb.stock_available as `ssb.stock_available`,
	ssb.stock_reserved as `ssb.stock_reserved`,
	ssb.stock_ordered as `ssb.stock_ordered`,
	ssb.storeroom_id as `ssb.storeroom_id`,
	ssb.is_active as `ssb.is_active`,
	mu.is_decimal,
	mu.measurement_unit as `mu.measurement_unit`
FROM 
	".Yearalize("stock_batch")." sb
INNER JOIN ".Monthalize('stock_storeroom_batch')." ssb 
ON	
	ssb.batch_id = sb.batch_id
INNER JOIN
	stock_product sp
ON
	sp.product_id = sb.product_id
INNER JOIN 
	stock_category sc
ON 	sc.category_id = sp.category_id
LEFT JOIN 
	stock_supplier ss
ON	
	ss.supplier_id = sb.supplier_id
LEFT JOIN 
	".Monthalize('stock_tax')." st
ON 
	st.tax_id=sb.tax_id
INNER JOIN 
	stock_measurement_unit mu
ON 
	sp.measurement_unit_id=mu.measurement_unit_id
");
//die ($grid->str_query_string);
$grid->setDeletedField('sb.deleted');
//$grid->setShowDeleted(true);

$grid->setOnClick('gridClick','sb.batch_id');
$grid->setSubmitURL('batch.php');

$grid->processParameters($_GET);
$grid->addUniqueFilter('sb.storeroom_id','equals',$_SESSION['int_current_storeroom'],'number');
$grid->addUniqueFilter('sp.deleted', 'equals', 'N', 'string');
//$grid->addUniqueFilter('product_description','start with','','string');
//$grid->addUniqueFilter('sp.product_code','equals','','');

if (!empty($_GET["action"])) 
  if ($_GET["action"]=="del") {
    require("batchdelete.php");
    
    $_SESSION['str_stock_message'] = deleteRecord($_GET["delid"]);
    
    header("Location:batch.php?".$grid->buildQueryString());
    exit;
  }
  $_SESSION['int_stock_selected'] = 2;
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
//  parent.frames["stockcontentdetail"].frames["batchescontent"].document.location="stockbatches.php?id="+id;
//  alert(id);
}



function transferRecord() {
  sNo = getSelectedSerialNumber();
  if (sNo>0) {
  myWin = window.open("transferbatch.php?id="+intSerialNumber,'stock','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
  myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 550/2));
  myWin.focus();
  } else alert("Select a batch to transfer!");

//  return false;
}

function returnRecord() {
  sNo = getSelectedSerialNumber();
  if (sNo>0) {
  myWin = window.open("returnbatch.php?id="+intSerialNumber,'stock','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
  myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 550/2));
  myWin.focus();
  } else alert("Select a batch to return!");

//  return false;
}



function openRecord() {
  myWin = window.open("modifybatch.php?id="+intSerialNumber,'stock','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
  myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 550/2));
  myWin.focus();
//  return false;
}

function modifyRecord() {
  sNo = getSelectedSerialNumber();
  if (sNo>0) {
    openRecord();
  } else alert("Select a batch to edit!");
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
if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
  if ($int_access_level > ACCESS_READ) {
//  	$grid_form->addButton('Transfer from the current batch to a different storeroom','../../images/transfer.gif','transferRecord','left');
  
  	if ($int_access_level==ACCESS_ADMIN) {
//    	$grid_form->addButton('Return waste goods','../../images/transfer_return.gif','returnRecord','left');
  		$grid_form->addButton('Edit batch details','../../images/modify.gif','modifyRecord','left');
  		$grid_form->addControl('showdeleted','left');
  
  	}
  }
}
//$grid_form->addSelectionControl('filter1','sp.product_code',array('One'=>1200,'Two'=>100),'center');
$grid_form->addControl('advfilter2','center');
$grid_form->addControl('print','left');
//$grid_form->addControl('filter1','center');
$grid_form->addControl('nav','right');
$grid_form->addControl('view','right');
//$grid_form->addControl('pagesize','right');
$grid_form->addControl('refresh','right');
$grid_form->setFrames('batchmenu','batchcontent');
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