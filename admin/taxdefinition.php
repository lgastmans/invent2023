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
$grid->addColumn("Tax Definition", "definition_description", "string", true); 
$grid->addColumn("Percent", "definition_percent", "number", true); 
$grid->addColumn("Type", "definition_type", "custom", true,100,"drawType"); 
$grid->addColumn("Details", "definition_explanation", "string", true); 



//$grid->setDeletedField('deleted');

$grid->setQuery("SELECT
	td.definition_description,
	td.definition_percent,
	td.definition_type,
	td.definition_id,
	td.definition_explanation
FROM 
	".Monthalize('stock_tax_definition')." td
");


$grid->setOnClick('gridClick','definition_id');

$grid->setSubmitURL('taxdefinition.php');
 
//$grid->addCustomParameter('id');
 
$grid->processParameters($_GET);

if (!empty($_GET["action"])) 
  if ($_GET["action"]=="del") {
    require("taxdefinitiondelete.php");
    $_SESSION['str_tax_message'] = deleteRecord($_GET["delid"]);
  }

$_SESSION['int_admin_selected']=5;

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
  myWin = window.open("viewtaxdefinition.php",'tax','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
  myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 550/2));
  myWin.focus();
//  return false;
}

function openRecord() {

  myWin = window.open("viewtaxdefinition.php?id="+intSerialNumber,'tax','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
  myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 550/2));
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
if ($_GET['action']=="del") {
 ?><script language="JavaScript">
 alert("<? echo $_SESSION['str_tax_message']; unset($_SESSION['str_tax_message']);?>");
 </script>
 <?
} ?>
</body>
</html>