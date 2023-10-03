<?
  require_once("../include/const.inc.php");
  require_once("../include/session.inc.php");
  require_once("../include/db.inc.php");
  require_once "../include/grid.inc.php";

$int_access_level = (getModuleAccessLevel('Admin'));

if ($_SESSION["int_user_type"]>1) {	
	$int_access_level = ACCESS_ADMIN;
} 

function drawType($f_field, $f_qry) {
	echo getTaxType($f_qry->FieldByName($f_field));
}

function drawSupplier($f_field, $f_qry) {
		if ($f_qry->FieldByName('is_active') == 'Y')
				echo $f_qry->FieldByName('supplier_name');
		else
				echo "<font color='red'>".$f_qry->FieldByName('supplier_name')."</font>";
}

function setDelivers($f_field, $f_qry) {
		if ($f_qry->FieldByName('is_supplier_delivering') == 'Y')
				echo "Consignment";
		else
				echo "Direct";
}

$grid = new DBGrid('admin_supplier');
//$grid->addColumn("ID", "sb.product_id", "number", true,100); 
$grid->addColumn("Code", "supplier_code", "string", true);
$grid->addColumn("Name", "supplier_name", "custom", true, 250, 'drawSupplier');
$grid->addColumn("Abbr.", "supplier_abbreviation", "string", false);
$grid->addColumn("Contact Person", "contact_person", "string", true); 
$grid->addColumn("Address", "supplier_address", "string", true); 
$grid->addColumn("City", "supplier_city", "string", true); 
$grid->addColumn("State", "supplier_state", "string", true); 
$grid->addColumn("Phone", "supplier_phone", "string", true); 
$grid->addColumn("Cell", "supplier_cell", "string", true); 
$grid->addColumn("Delivers", "is_supplier_delivering", "custom", false, 100, 'setDelivers');
$grid->addColumn("Commisision %", "commission_percent", "string", true);
$grid->addColumn("Price Discount %", "supplier_discount", "string", true);
//$grid->addColumn("Supplier Type", "supplier_type", "string", true);
$grid->addColumn("Trust", "trust", "string", true);
$grid->addColumn("TIN", "supplier_TIN", "string", true);
$grid->addColumn("CST", "supplier_CST", "string", true);
//$grid->addColumn("Supplier Type", "supplier_abbreviation", "string", true); 


//$grid->setDeletedField('deleted');

$grid->setQuery("SELECT
	*
FROM 
	stock_supplier ss
");


$grid->setOnClick('gridClick','supplier_id');

$grid->setSubmitURL('supplier.php');
 
//$grid->addCustomParameter('id');
 
$grid->processParameters($_GET);

if (!empty($_GET["action"])) 
  if ($_GET["action"]=="del") {

  function deleteRecord($f_record_id) {
  
  	$delQuery = new Query("select * from stock_supplier where supplier_id=$f_record_id");
	if ($delQuery->RowCount()==0) 
		return "Cannot find record.";
	//
	// check to see if there is something with a balance
	//
  	$delQuery -> Query("select * from ".Monthalize('stock_storeroom_product')." where supplier_id=$f_record_id");

	if ($delQuery -> RowCount()>0) {
		return "There are products using this supplier, please remove them if you wish to delete it.";
	}

	$delQuery->ExecuteQuery("DELETE from stock_supplier where supplier_id=$f_record_id");

	$delQuery->Free();
	
	return "Deleted supplier $f_record_id";
  } 

    $_SESSION['str_tax_message'] = deleteRecord($_GET["delid"]);
  }

$_SESSION['int_admin_selected']=8;

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
//  forceResize(1,0);
//  parent.frames["stockcontentdetail"].frames["batchescontent"].document.location="stockbatches.php?id="+id;
//  alert(id);
}

function newRecord() {
  myWin = window.open("supplier_edit.php",'tax','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=630,height=750,top=0');
  myWin.moveTo((screen.availWidth/2 - 630/2), (screen.availHeight/2 - 750/2));
  myWin.focus();
//  return false;
}

function openRecord() {
  myWin = window.open("supplier_edit.php?id="+intSerialNumber,'tax','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=630,height=750,top=0');
  myWin.moveTo((screen.availWidth/2 - 630/2), (screen.availHeight/2 - 750/2));
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
//$grid->b_debug=true;
$grid_form->setGrid($grid);
/*if ($int_access_level > ACCESS_READ) {
	$grid_form->addButton('Transfer part or all of selected batch to other storeroom','../images/transfer.gif','transferRecord','left');
}*/
//$grid_form->addControl('showdeleted','left');
//$grid_form->addControl('filter0','center');
if ($int_access_level > ACCESS_READ) {
	$grid_form->addButton('New Record','../images/new.gif','newRecord','left');
	$grid_form->addButton('Edit Record','../images/modify.gif','modifyRecord','left');
	
	if ($int_access_level==ACCESS_ADMIN) {
		$grid_form->addButton('Delete Record','../images/delete.gif','deleteRecord','left');

	}
}

$grid_form->addControl('filter0','center');
//$grid_form->addControl('filter2','center');
$grid_form->addControl('nav','right');
$grid_form->addControl('refresh','right');
//$grid_form->addButton('Resize','../images/resize.gif','doResize','right');
$grid_form->setFrames('adminmenu','admincontent');
$grid_form->draw();


if (!empty($_GET['action'])) 
if ($action=="del") {
 ?><script language="JavaScript">
 alert("<? echo $_SESSION['str_tax_message']; unset($_SESSION['str_tax_message']);?>");
 </script>
 <?
} ?>
</body>
</html>