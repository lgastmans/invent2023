<?
  require_once("../include/const.inc.php");
  require_once("../include/session.inc.php");
  
  require_once("../include/db.inc.php");

  require_once "../include/grid.inc.php";


$int_access_level = (getModuleAccessLevel('Stock'));

if ($_SESSION["int_user_type"]>1) {	
	$int_access_level = ACCESS_ADMIN;
} 

$grid = new Grid();
//$grid->addColumn("ID", "sb.product_id", "number", true,100); 
$grid->addColumn("Batch Code", "sb.batch_code", "string", true,100); 
$grid->addColumn("B. Price", "sb.buying_price", "currency", true); 
$grid->addColumn("S. Price", "sb.selling_price", "currency", true); 
$grid->addColumn("Date", "sb.date_created", "date", true); 
$grid->addColumn("Available", "ssb.stock_available", "number", true); 
$grid->addColumn("Reserved", "ssb.stock_reserved", "number", true); 
$grid->addColumn("Ordered", "ssb.stock_ordered", "number", true); 
$grid->addColumn("Tax", "st.tax_description", "string", false); 
$grid->addColumn("Shelf", "ssb.shelf_id", "number", false); 



//$grid->setDeletedField('deleted');

$grid->setQuery("SELECT
sb.batch_id as `sb.batch_id`,
sb.product_id as `sb.product_id`,
sb.batch_code as `sb.batch_code`,
sb.buying_price as `sb.buying_price`,
sb.selling_price as `sb.selling_price`,
sb.date_created as `sb.date_created`,
ssb.stock_available as `ssb.stock_available`,
ssb.stock_reserved as `ssb.stock_reserved`,
ssb.stock_ordered as `ssb.stock_ordered`,
ssb.shelf_id as `ssb.shelf_id`,
sb.tax_id as `ssb.tax_id`,
st.tax_description as `st.tax_description`
FROM 
	".Monthalize('stock_storeroom_batch')." ssb
INNER JOIN ".Yearalize('stock_batch')." sb
ON sb.batch_id = ssb.batch_id
LEFT JOIN 
	".Monthalize('stock_tax')." st
ON 
	st.tax_id=sb.tax_id

");


$grid->setOnClick('gridClick','sb.batch_id');

$grid->setSubmitURL('stockbatches.php');

//$grid->addCustomParameter('id');

if (!empty($_GET['id'])) {
  $grid->addFilter('sb.product_id', 'equals',$_GET['id'],'number');
  $grid->addFilter('ssb.storeroom_id', 'equals',$_SESSION['int_current_storeroom'],'number');
  $grid->addFilter('sb.batch_code', 'starts with','','string');
} else {
  $grid->processParameters($_GET);
}


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

function transferRecord() {
  sNo = getSelectedSerialNumber();
  if (sNo>0) {
  myWin = window.open("batchgrid/transferbatch.php?id="+intSerialNumber,'stock','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
  myWin.focus();
  } else alert("Select a batch to transfer!");

//  return false;
}


function doResize() {
	parent.parent.frames["stockcontent"].doResize(2);
}
</script>
</head>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>
<? 
$grid_form = new GridForm();
//$grid->b_debug=true;
$grid_form->setGrid($grid);
if ($int_access_level > ACCESS_READ) {
	$grid_form->addButton('Transfer part or all of selected batch to other storeroom','../images/transfer.gif','transferRecord','left');
}
//$grid_form->addControl('showdeleted','left');
//$grid_form->addControl('filter0','center');
$grid_form->addControl('filter2','center');
//$grid_form->addControl('filter2','center');
$grid_form->addControl('nav','right');
$grid_form->addControl('refresh','right');
$grid_form->addButton('Resize','../images/resize.gif','doResize','right');
$grid_form->setFrames('batchesmenu','batchescontent');
$grid_form->draw();


if (!empty($_GET['action'])) 
if ($action=="del") {
 ?><script language="JavaScript">
 alert("deleted record <? echo $delid; ?>");
 </script>
 <?
} ?>
</body>
</html>