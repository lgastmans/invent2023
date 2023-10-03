<?
/**
* 
* @version 	$Id: stock.php,v 1.1.1.1 2006/02/14 05:03:59 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* @date		12 Oct 2005
* @module 	Product View Grid
* @name  	viewstock.php
* 
* This file uses the Grid component to generate the stock grid
*/
  $str_cur_module='Stock';
  require_once("../include/const.inc.php");
  require_once("../include/session.inc.php");
  require_once("../include/db.inc.php");
  require_once "../include/grid.inc.php";

  // context sensitive help
  $_SESSION['str_context_help']='stock/help/index.php';

  $int_access_level = (getModuleAccessLevel('Stock'));

  if ($_SESSION["int_user_type"]>1) {	
	$int_access_level = ACCESS_ADMIN;
  } 

  $module_stock = getModule('Stock');
  if (!($module_stock->monthExists($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']))) {
	dieWithError("This module has no information for the selected month.");	
  }

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
      if ($f_qry->FieldByName("stock_current")+$f_qry->FieldByName("stock_reserved")-$f_qry->FieldByName("stock_ordered") < $f_qry->FieldByName("stock_minimum")) {
	    if ($f_qry->FieldByName('is_decimal') == 'Y') {
		  if ($int_decimal_places == 2)
			echo "<font color='red'>".sprintf("%0.2f",$f_qry->FieldByName($f_field))."</font>";
		  else
			echo "<font color='red'>".sprintf("%0.3f",$f_qry->FieldByName($f_field))."</font>";
	    }
	    else
		  echo "<font color='red'>".sprintf("%0.0f",$f_qry->FieldByName($f_field))."</font>";
      }
      else {
	    if ($f_qry->FieldByName('is_decimal') == 'Y') {
		  if ($int_decimal_places == 2)
			echo sprintf("%0.2f",$f_qry->FieldByName($f_field));
		  else
			echo sprintf("%0.3f",$f_qry->FieldByName($f_field));
	    }
	    else
		  echo sprintf("%0.0f",$f_qry->FieldByName($f_field));
      }
}

function drawAdjusted($f_field, $f_qry) {
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

$grid = new DBGrid('stock_main');

$grid->addColumn("Code", "product_code", "string", true, 50); 
$grid->addColumn("Product Name", "product_description", "custom", true,200,"drawProd"); 
$grid->addColumn("Category", "category_description", "string", true); 
$grid->addColumn("Qty", "stock_current", "custom", true, 50, "drawQty");
$grid->addColumn("Adjusted", "stock_adjusted", "custom", false, 50, "drawAdjusted");
$grid->addColumn("Reserved", "stock_reserved", "custom", false, 50, "drawAdjusted"); 
$grid->addColumn("Ordered", "stock_ordered", "custom", false, 50, "drawAdjusted"); 
$grid->addColumn("Minimum", "stock_minimum", "custom", false, 50, "drawAdjusted"); 
$grid->addColumn("Tax", "tax_description", "string", true); 
$grid->addColumn("Unit", "measurement_unit", "string", true); 
$grid->addColumn("Point Price", "point_price", "number", true); 
$grid->addColumn("Sale Price", "sale_price", "number", true); 
$grid->addColumn("Use B.Price", "use_batch_price", "boolean", true);
$grid->addColumn("Supplier 1", "supplier_name", "string", true, 150);
//$grid->b_debug=true;
$grid->loadView();


$grid->setQuery("SELECT
	sp.product_id,
	sp.product_code,
	sp.product_description,
	sp.is_available,
	sp.is_av_product,
	sp.minimum_qty,
	sp.is_minimum_consolidated,
	sp.tax_id,
	sp.is_perishable,
	sp.measurement_unit_id,
	sp.category_id,
	mu.measurement_unit,
	mu.is_decimal,
	sc.category_description,
	sp.deleted,
	st.tax_description,
	ssp.stock_current,
	ssp.stock_reserved,
	ssp.stock_ordered,
	ssp.stock_adjusted,
	ssp.stock_minimum,
	ssp.sale_price,
	ssp.point_price,
	ssp.use_batch_price,
	ss.supplier_name
FROM 
	stock_product sp
INNER JOIN 
	stock_measurement_unit mu
ON 
	sp.measurement_unit_id=mu.measurement_unit_id
INNER JOIN ".Monthalize('stock_storeroom_product')." ssp
ON 
	ssp.product_id = sp.product_id
LEFT JOIN 
	".Monthalize('stock_tax')." st
ON 
	st.tax_id=sp.tax_id
	
INNER JOIN 
	stock_category sc
ON 	sc.category_id = sp.category_id
LEFT JOIN
      stock_supplier ss
ON ss.supplier_id = sp.supplier_id");
//die ($grid->str_query_string);
//$grid->setDeletedField('deleted');
//$grid->setShowDeleted(true);

$grid->setOnClick('gridClick','product_id');
$grid->setSubmitURL('stock.php');

$grid->processParameters($_GET);
$grid->addUniqueFilter('storeroom_id','equals',$_SESSION['int_current_storeroom'],'number');
$grid->addUniqueFilter('deleted','equals','N','string');
if ($str_show_available == 'N')
      $grid->addUniqueFilter('is_available','equals','Y','string');
      
//$grid->addUniqueFilter('product_description','start with','','string');

if (!empty($_GET["action"])) 
  if ($_GET["action"]=="del") {
    require("stockdelete.php");
    
    $_SESSION['str_stock_message'] = deleteRecord($_GET["delid"]);
    
    header("Location:stock.php?".$grid->buildQueryString());
    exit;
  }
  
      $_SESSION["int_stock_selected"] = 1;

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
  parent.frames["stockcontentdetail"].frames["batchescontent"].document.location="stockbatches.php?id="+id;
//  alert(id);
}

function doResize(win) {

	if (parent.frames["stockcontentdetail"].innerWidth)
	{
		frameWidth = parent.frames["stockcontentdetail"].innerWidth;
		frameHeight = parent.frames["stockcontentdetail"].innerHeight;
	}
	else if (parent.frames["stockcontentdetail"].document.documentElement && parent.frames["stockcontentdetail"].document.documentElement.clientWidth)
	{
		frameWidth = parent.frames["stockcontentdetail"].document.documentElement.clientWidth;
		frameHeight = parent.frames["stockcontentdetail"].document.documentElement.clientHeight;
	}
	else if (parent.frames["stockcontentdetail"].document.body)
	{
		frameWidth = parent.frames["stockcontentdetail"].document.body.clientWidth;
		frameHeight = parent.frames["stockcontentdetail"].document.body.clientHeight;
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

	if (parent.frames["stockcontentdetail"].innerWidth)
	{
		frameWidth = parent.frames["stockcontentdetail"].innerWidth;
		frameHeight = parent.frames["stockcontentdetail"].innerHeight;
	}
	else if (parent.frames["stockcontentdetail"].document.documentElement && parent.frames["stockcontentdetail"].document.documentElement.clientWidth)
	{
		frameWidth = parent.frames["stockcontentdetail"].document.documentElement.clientWidth;
		frameHeight = parent.frames["stockcontentdetail"].document.documentElement.clientHeight;
	}
	else if (parent.frames["stockcontentdetail"].document.body)
	{
		frameWidth = parent.frames["stockcontentdetail"].document.body.clientWidth;
		frameHeight = parent.frames["stockcontentdetail"].document.body.clientHeight;
	}

if (win==1) {
  
  if (asize == "1") {
    parent.document.body.rows="30,*,1";
  } else parent.document.body.rows="30,*,150";
 
}
}


function newRecord() {
  myWin = window.open("viewstock.php",'stock','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=250,top=0');
      myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 250/2));
  myWin.focus();
//  return false;
}

function openRecord() {
  myWin = window.open("viewstock2.php?id="+intSerialNumber,'stock','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=250,top=0');
      myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 250/2));
  myWin.focus();
//  return false;
}

function modifyRecord() {
  sNo = getSelectedSerialNumber();
  if (sNo>0) {
    openRecord();
  } else alert("Select a record to modify!");
}

function requestRecord() {
  sNo = getSelectedSerialNumber();
  if (sNo>0) {
      myWin = window.open("batchgrid/requestbatch.php?id="+intSerialNumber,'stock','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
      myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 550/2));
      myWin.focus();
  } else
      alert("Select a product to request!");
}

function requestMultipleRecord() {
  myWin = window.open("batchgrid/requestbatchmultiple.php",'stockmultiple','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
  myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 550/2));
  myWin.focus();
}

function updateGlobalPrice() {
  myWin = window.open("update_global_price.php",'update_global_price','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=300,top=0');
  myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 300/2));
  myWin.focus();
}
//  return false;

function stockCorrect() {
  myWin = window.open('transfers/stock_correct.php','stock_correct','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=300,top=0');
  myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 300/2));
  myWin.focus();
}

function direct_transfer() {
  myWin = window.open('transfers/direct_transfer.php','direct_transfer','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=300,top=0');
  myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 300/2));
  myWin.focus();
}

function bulkPackage() {
  myWin = window.open('transfers/bulkPackage.php','bulkPackage','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=500,height=400,top=0');
  myWin.moveTo((screen.availWidth/2 - 500/2), (screen.availHeight/2 - 400/2));
  myWin.focus();
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


//$grid->addOrder("category_description","");


//$grid->addFilter("product_code","starts with","3","string");
//$grid->addFilter("category_description","contains","cleaning","string");

$grid_form = new GridForm();

$grid_form->setGrid($grid);
if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
	if ($int_access_level > ACCESS_READ) {
		$grid_form->addHTML('&nbsp;', 'left');
//		$grid_form->addButton('Add item to this storeroom','../images/new.gif','newRecord','left');
		$grid_form->addButton('Edit product details for this storeroom','../images/book_edit.png','modifyRecord','left');	
		$grid_form->addHTML('&nbsp;', 'left');
		
		if ($int_access_level==ACCESS_ADMIN) {
			$grid_form->addButton('Remove product from this storeroom','../images/cross.png','deleteRecord','left');
			$grid_form->addHTML('&nbsp;', 'left');
			$grid_form->addButton('Correct the stock of this product', '../images/lock_edit.png', 'stockCorrect', 'left');
			$grid_form->addHTML('&nbsp;', 'left');
		}
	//  	$grid_form->addButton('Request stock from other storeroom','../images/new.gif','requestRecord','left');
	//   	$grid_form->addButton('Request all low stock from other storeroom','../images/new.gif','requestMultipleRecord','left');
		$grid_form->addButton('Update the global price','../images/money_add.png','updateGlobalPrice','left');
		$grid_form->addHTML('&nbsp;', 'left');
		$grid_form->addButton('Direct transfer to storeroom', '../images/transmit_go.png', 'direct_transfer', 'left');
		$grid_form->addHTML('&nbsp;', 'left');
		$grid_form->addButton('Bulk package transfer', '../images/cart_remove.png', 'bulkPackage', 'left');
	}
}
$grid_form->addControl('print', 'left');
if ($str_show_available == 'Y')
      $grid_form->addControl('advfilter2','center');
else
      $grid_form->addControl('advfilter3','center');
//$grid_form->addControl('advfilter2','center');
//$grid_form->addControl('filter1','center');
$grid_form->addControl('nav','right');
$grid_form->addControl('view','right');
$grid_form->addControl('pagesize','right');
$grid_form->addControl('refresh','right');
$grid_form->addButton('Resize','../images/resize.gif','doResize(1);','right');
$grid_form->setFrames('stockmenu','stockcontent');
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