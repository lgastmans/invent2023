<?
  require_once("../include/const.inc.php");
  require_once("../include/session.inc.php");
  require_once("../include/db.inc.php");
  require_once "../include/grid.inc.php";

$int_access_level = (getModuleAccessLevel('PT Accounts'));

if ($_SESSION["int_user_type"]>1) {	
	$int_access_level = ACCESS_ADMIN;
} 

function drawStatus($f_field, $f_qry) {
	switch ($f_qry->FieldByName($f_field)) {
		case ACCOUNT_TRANSFER_PENDING: echo "Pending";break;
		case ACCOUNT_TRANSFER_INSUFFICIENT_FUNDS: echo "No Funds";break;
		case ACCOUNT_TRANSFER_ERROR: echo "Error";break;
		case ACCOUNT_TRANSFER_CANCELLED: echo "<font color=\"red\">Cancelled</font>";break;
		case ACCOUNT_TRANSFER_HOLD: echo "Holding";break;
		case ACCOUNT_TRANSFER_COMPLETE: echo "Complete";break;
	} 
//	echo "<b>".$f_qry->FieldByName($f_field)."</b>";
}

$grid = new Grid();
$grid->addColumn("ID", "transfer_id", "string", true,100); 
$grid->addColumn("Date", "date_created", "date", true); 
$grid->addColumn("From", "account_from", "string", true); 
$grid->addColumn("To", "account_to", "string", true); 
$grid->addColumn("Date", "date_created", "date", true); 
$grid->addColumn("Description", "description", "string", true); 
$grid->addColumn("Amount", "amount", "number", true); 
$grid->addColumn("Status", "transfer_status", "custom", true,100,'drawStatus'); 
$grid->addColumn("By", "username", "string", true); 

//$grid->setDeletedField('deleted');

$grid->setQuery("SELECT
	tr.transfer_id,
	tr.date_created,
	tr.account_from,
	tr.id_from,
	tr.id_to,
	tr.account_to,
	tr.description,
	tr.amount,
	tr.transfer_status,
	u.username
FROM 
	".Monthalize('account_pt_transfers')." tr
INNER JOIN user u
ON u.user_id = tr.user_id
");

$grid->setOnClick('gridClick','transfer_id');
$grid->setSubmitURL('accounts_transfers.php');

if (!empty($_GET['id'])) {
  $grid->addFilter('id_from', 'equals',$_GET['id'],'number');
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
  intSerialNumber = id;
}

function doResize() {
	parent.parent.frames["accountscontent"].doResize(2);
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
$grid_form->addControl('filter1','center');
//$grid_form->addControl('filter2','center');
$grid_form->addControl('nav','right');
$grid_form->addControl('refresh','right');
$grid_form->addButton('Resize','../images/resize.gif','doResize','right');
$grid_form->setFrames('transfersmenu','transferscontent');
$grid_form->draw();


if (!empty($_GET['action'])) 
if ($action=="del") {
?>
 <script language="JavaScript">
 alert("deleted record <? echo $delid; ?>");
 </script>
<?
} 
?>

</body>
</html>