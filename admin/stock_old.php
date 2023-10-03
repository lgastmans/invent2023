<?
/**
* 
* @version 	$Id: stock.php,v 1.2 2006/02/22 10:16:19 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* @date		12 Oct 2005
* @module 	Product View Grid
* @name  	viewstock.php
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

$grid = new DBGrid('admin_stock');

$grid->addColumn("Code", "product_code", "string", true, 100);
$grid->addColumn("Barcode", "product_bar_code", "string", false, 150);
$grid->addColumn("Description", "product_description", "string", true); 
$grid->addColumn("Category", "category_description", "string", true);
$grid->addColumn("M.R.P.", "mrp", "number", true);
$grid->addColumn("Available", "is_available", "boolean", true); 
$grid->addColumn("AV Product", "is_av_product", "boolean", true); 
$grid->addColumn("Perishable", "is_perishable", "boolean", true);
$grid->addColumn("List", "list_in_purchase", "boolean", true);
$grid->addColumn("Minimum", "minimum_qty", "number", true); 
$grid->addColumn("Margin %", "margin_percent", "string", true); 
$grid->addColumn("Default Tax", "tax_description", "string", true); 
$grid->addColumn("Unit", "measurement_unit", "string", true);  
$grid->addColumn("Supplier", "sp1.supplier_name", "string", true); 
$grid->addColumn("Supplier 2", "sp2.supplier_name", "string", true); 
$grid->addColumn("Supplier 3", "sp3.supplier_name", "string", true); 
$grid->loadView();

$grid->setQuery("SELECT
	sp.product_id,
	sp.product_code,
	sp.product_bar_code,
	sp.product_description,
        sp.mrp,
	sp.is_available,
	sp.is_av_product,
	sp.minimum_qty,
	sp.tax_id,
	sp.is_perishable,
	sp.measurement_unit_id,
	sp.category_id,
	sp.supplier_id,
	sp.supplier2_id,
	sp.supplier3_id,
	sp.list_in_purchase,
	sp1.supplier_name as `sp1.supplier_name`,
	sp2.supplier_name as `sp2.supplier_name`,
	sp3.supplier_name as `sp3.supplier_name`,
	sp.margin_percent,
	sp.quantity_per_box,
	mu.measurement_unit,
	sc.category_description,
	sp.deleted,
	st.tax_description
	
FROM 
	stock_product sp
INNER JOIN 
	stock_measurement_unit mu
ON 
	sp.measurement_unit_id=mu.measurement_unit_id
LEFT JOIN 
	".Monthalize('stock_tax')." st
ON 
	st.tax_id=sp.tax_id
	
INNER JOIN 
	stock_category sc
ON 	sc.category_id = sp.category_id

LEFT JOIN
	stock_supplier sp1
ON
	sp1.supplier_id = sp.supplier_id
LEFT JOIN
	stock_supplier sp2
ON
	sp2.supplier_id = sp.supplier2_id
LEFT JOIN
	stock_supplier sp3
ON
	sp3.supplier_id = sp.supplier3_id

");
//die ($grid->str_query_string);
$grid->setDeletedField('deleted');
//$grid->setShowDeleted(true);

$grid->setOnClick('gridClick','product_id');
$grid->setSubmitURL('stock.php');

$grid->processParameters($_GET);

if (!empty($_GET["action"])) 
  if ($_GET["action"]=="del") {
    require("stockdelete.php");
    
    $_SESSION['str_stock_message'] = deleteRecord($_GET["delid"]);
    
    header("Location:stock.php?".$grid->buildQueryString());
    exit;
  }
  else if ($_GET['action'] == 'perm_del') {
    require("stockdelete_permanent.php");
    
    $_SESSION['str_stock_message'] = delete_product_permanently($_GET["delid"]);
    
    header("Location:stock.php?".$grid->buildQueryString());
    exit;
  }
  
  $_SESSION['int_admin_selected']=4;
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
//  alert(id);
}



function newRecord() {
  myWin = window.open("product_edit.php",'stock','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=1000,height=600,top=0');
  myWin.moveTo((screen.availWidth/2 - 1000/2), (screen.availHeight/2 - 600/2));
  myWin.focus();
//  return false;
}

function openRecord() {
  myWin = window.open("product_edit.php?id="+intSerialNumber,'stock','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=1000,height=600,top=0');
  myWin.moveTo((screen.availWidth/2 - 1000/2), (screen.availHeight/2 - 600/2));
  myWin.focus();
//  return false;
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
      if (confirm("Are you sure you want to flag this product deleted?")) {

        if (document.location.href.indexOf("?")<0) {
          document.location = document.location.href+"?action=del&delid="+sNo;
	} else {
	  document.location = document.location.href+"&action=del&delid="+sNo;
	}
      }
  } else alert("Select a record to delete!");
}

function permanentlydeleteRecord() {
  sNo = getSelectedSerialNumber();
  if (sNo>0) {
      if (confirm("Are you sure you want to PERMANENTLY delete this product?\nTHIS OPERATION CANNOT BE UNDONE!")) {

        if (document.location.href.indexOf("?")<0) {
          document.location = document.location.href+"?action=perm_del&delid="+sNo;
	} else {
	  document.location = document.location.href+"&action=perm_del&delid="+sNo;
	}
      }
  }
  else alert("Select a record to delete!");
}

function replaceTax() {
      myWin = window.open("taxes_replace.php",'taxes_replace','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,width=500,height=250,top=0');
      myWin.moveTo((screen.availWidth/2 - 500/2), (screen.availHeight/2 - 250/2));
      myWin.focus();
}

function productDuplicate() {
	sNo = getSelectedSerialNumber();
	if (sNo>0) {
		myWin = window.open("product_duplicate.php?id="+sNo,'product_duplicate','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,width=500,height=250,top=0');
		myWin.moveTo((screen.availWidth/2 - 500/2), (screen.availHeight/2 - 250/2));
		myWin.focus();
	}
	else
		alert("Select a product to duplicate");
}

function print_barcode() {
	sNo = getSelectedSerialNumber();
	if (sNo>0) {
		myWin = window.open("print_barcode_dialog.php?id="+sNo,'print_barcode','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,width=600,height=250,top=0');
		myWin.moveTo((screen.availWidth/2 - 600/2), (screen.availHeight/2 - 250/2));
		myWin.focus();
	}
	else
		alert("Select a product.");
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
if ($int_access_level > ACCESS_READ) {
	$grid_form->addButton('Add a new product','../images/new.gif','newRecord','left');
	$grid_form->addButton('Edit the details of an existing product','../images/modify.gif','modifyRecord','left');
	
	if ($int_access_level==ACCESS_ADMIN) {
		$grid_form->addButton('mark a product as deleted','../images/delete.gif','deleteRecord','left');
		$grid_form->addHTML('&nbsp;', 'left');
		$grid_form->addButton('Delete a product permanently','../images/dustbin.png','permanentlydeleteRecord','left');
		$grid_form->addControl('showdeleted','left');
		$grid_form->addHTML('&nbsp;', 'left');
		$grid_form->addButton('Replace taxes', '../images/table_relationship.png','replaceTax','left');
		$grid_form->addHTML('&nbsp;', 'left');
		$grid_form->addButton('Create a duplicate entry for this product', '../images/arrow_divide.png', 'productDuplicate', 'left');
		$grid_form->addHTML('&nbsp;', 'left');
	}
}
$grid_form->addButton('Print barcode','../images/barcode.png','print_barcode','left');
$grid_form->addControl('print','left');
$grid_form->addControl('advfilter0','center');
//$grid_form->addControl('filter1','center');
$grid_form->addControl('nav','right');
$grid_form->addControl('pagesize','right');
$grid_form->addControl('view','right');
$grid_form->addControl('refresh','right');
$grid_form->addButton('Resize','../images/resize.gif','doResize(1);','right');
$grid_form->setFrames('adminmenu','admincontent');
$grid_form->draw();

// if there are any stock messages, display them and clear the session message

if (!empty($_SESSION["str_stock_message"])) {
 ?><script language ="JavaScript">
 alert("<? echo $_SESSION["str_stock_message"]; ?>");
 </script>
 <?
  $_SESSION["str_stock_message"]="";
 }
?>

</body>
</html>