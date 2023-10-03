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

$grid = new Grid();
//$grid->addColumn("ID", "sb.product_id", "number", true,100); 
$grid->addColumn("Code", "storeroom_code", "string", true); 
$grid->addColumn("Storeroom", "description", "string", true); 
$grid->addColumn("Location", "location", "string", true); 
$grid->addColumn("Account", "bill_credit_account", "string", true); 
$grid->addColumn("Tax Accounts", "is_account_taxed", "boolean", true,70); 
$grid->addColumn("Tax Cash", "is_cash_taxed", "boolean", true,70); 
$grid->addColumn("Bill Cash", "can_bill_cash", "boolean", true,70); 
$grid->addColumn("Bill PT", "can_bill_pt_account", "boolean", true,70); 
$grid->addColumn("Bill FS", "can_bill_fs_account", "boolean", true,70); 
$grid->addColumn("Default Supplier", "ss  supplier_name", "string", true, 100);
$grid->addColumn("Default Tax", "tax tax_description", "string", false, 50);


//$grid->setDeletedField('deleted');

$grid->setQuery("SELECT
	st.*, ss.supplier_name, tax.tax_description
FROM 
	stock_storeroom  st
LEFT JOIN
  stock_supplier ss
ON
  (st.default_supplier_id = ss.supplier_id)
LEFT JOIN
  ".Monthalize('stock_tax')." tax
ON
  (st.default_tax_id = tax.tax_id)
");


$grid->setOnClick('gridClick','storeroom_id');

$grid->setSubmitURL('storeroom.php');
 
//$grid->addCustomParameter('id');
 
$grid->processParameters($_GET);

if (IsSet($_GET["action"]))
	if ($_GET["action"] == "del") {

	function deleteRecord($f_record_id) {
		$delQuery = new Query("
			SELECT *
			FROM stock_storeroom
			WHERE storeroom_id = $f_record_id
		");
		if ($delQuery->RowCount() == 0)
			return "Cannot find record.";
			
		//***
		// check to see if there is something with a balance
		//***
		$delQuery -> Query("
			SELECT *
			FROM ".Monthalize('stock_storeroom_product')."
			WHERE storeroom_id = $f_record_id
		");
		if ($delQuery->RowCount() > 0) {
			return "There are products in this storeroom, please remove them if you wish to delete it.";
		}
		
		$bool_success=true;
		$delQuery->Query("START TRANSACTION");
		
		$delQuery->Query("
			DELETE FROM stock_storeroom
			WHERE storeroom_id = $f_record_id
		");
		if ($delQuery->b_error==true) {
			$bool_success = false;
		}
		
		$delQuery->Query("
			DELETE FROM user_settings
			WHERE storeroom_id = $f_record_id
		");
		if ($delQuery->b_error==true) {
			$bool_success = false;
		}
		
		if ($bool_success) {
			$delQuery->Query("COMMIT");
			return "Storeroom deleted";
		}
		else {
			$delQuery->Query("ROLLBACK");
			return "Error deleting storeroom";
		}
		
	}

	$_SESSION['str_tax_message'] = deleteRecord($_GET["delid"]);
	
	header("Location:storeroom.php?".$grid->buildQueryString());
	exit;
}

$_SESSION['int_admin_selected']=7;

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
  myWin = window.open("storeroom_edit.php",'tax','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=650,height=600,top=0');
  myWin.moveTo((screen.availWidth/2 - 650/2), (screen.availHeight/2 - 600/2));
  myWin.focus();
}

function openRecord() {

//  myWin = window.open("viewstoreroom.php?id="+intSerialNumber,'tax','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=650,height=550,top=0');
  myWin = window.open("storeroom_edit.php?id="+intSerialNumber,'tax','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=650,height=600,top=0');
  myWin.moveTo((screen.availWidth/2 - 650/2), (screen.availHeight/2 - 600/2));
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


if (IsSet($_SESSION['str_tax_message'])) {
	if ($_SESSION['str_tax_message'] <> '') { ?>
		<script language="JavaScript">
			alert("<? echo $_SESSION['str_tax_message'];?>");
		</script>
<?
		$_SESSION["str_tax_message"]="";
	}
}
?>
</body>
</html>