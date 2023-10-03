<?
  require_once("../include/const.inc.php");
  require_once("../include/session.inc.php");
  require_once("../include/db.inc.php");
  require_once "../include/grid.inc.php";

	if ($_SESSION['str_user_color_scheme'] == 'standard')
		$str_css_filename = 'bill_styles.css';
	else if ($_SESSION['str_user_color_scheme'] == 'blue')
		$str_css_filename = 'bill_styles_blue.css';
	else if ($_SESSION['str_user_color_scheme'] == 'purple')
		$str_css_filename = 'bill_styles_purple.css';
	else if ($_SESSION['str_user_color_scheme'] == 'green')
		$str_css_filename = 'bill_styles_green.css';
	else
		$str_css_filename = 'bill_styles.css';


$int_access_level = (getModuleAccessLevel('Accounts'));

if ($_SESSION["int_user_type"]>1) {	
	$int_access_level = ACCESS_ADMIN;
} 

      $_SESSION["int_accounts_selected"] = 2;

	$arr_filter_type['Pending'] = 0;
	$arr_filter_type['No Funds'] = 1;
	$arr_filter_type['Error'] = 2;
	$arr_filter_type['Cancelled'] = 3;
	$arr_filter_type['Hold'] = 4;
	$arr_filter_type['Complete'] = 5;
	$arr_filter_type['Review'] = 6;

function drawStatus($f_field, $f_qry) {
	switch ($f_qry->FieldByName($f_field)) {
		case ACCOUNT_TRANSFER_PENDING: echo "Pending";break;
		case ACCOUNT_TRANSFER_INSUFFICIENT_FUNDS: echo "No Funds";break;
		case ACCOUNT_TRANSFER_ERROR: echo "Error";break;
		case ACCOUNT_TRANSFER_CANCELLED: echo "<font color=\"red\">Cancelled</font>";break;
		case ACCOUNT_TRANSFER_HOLD: echo "Holding";break;
		case ACCOUNT_TRANSFER_COMPLETE: echo "Complete";break;
		case ACCOUNT_TRANSFER_REVIEW: echo "Review";break;
	} 
//	echo "<b>".$f_qry->FieldByName($f_field)."</b>";
}

function drawDate($f_field, $f_qry) {
	echo makeHumanTime($f_qry->FieldByName($f_field));
}


$grid = new DBGrid('accounts_transfers');
//$grid->addColumn("ID", "sb.product_id", "number", true,100); 
$grid->addColumn("ID", "transfer_id", "string", true,100);
$grid->addColumn("Date", "date_created", "custom", true, 80, 'drawDate');
$grid->addColumn("From", "account_from", "string", true);
$grid->addColumn("Name", "account_name", "string", true);
$grid->addColumn("To", "account_to", "string", true);
$grid->addColumn("Date", "date_completed", "custom", true, 80, 'drawDate');
$grid->addColumn("Description", "description", "string", true);
$grid->addColumn("Amount", "amount", "number", true);
$grid->addColumn("Status", "transfer_status", "custom", true,100,'drawStatus');
$grid->addColumn("By", "username", "string", true);

$grid->loadView();
//$grid->setDeletedField('deleted');

$grid->setQuery("SELECT
	tr.transfer_id,
	tr.date_created,
	tr.account_from,
	tr.cc_id_from,
	tr.cc_id_to,
	tr.account_to,
	tr.description,
	tr.date_completed,
	tr.amount,
	tr.transfer_status,
	u.username,
	ac.account_name
FROM 
	".Monthalize('account_transfers')." tr
INNER JOIN user u ON u.user_id = tr.user_id
INNER JOIN account_cc ac ON ac.cc_id = tr.cc_id_from
");

$grid->processParameters($_GET);
//$grid->addUniqueFilter('transfer_id', 'equals', '', 'number');
$grid->addOrder('date_created', 'DESC');

$grid->setOnClick('gridClick','transfer_id');
$grid->setSubmitURL('transfers.php');

	if (!empty($_GET['action'])) {
		if ($_GET['action'] == 'flag_no_funds') {
			$qry_update = new Query("
				SELECT *
				FROM ".Monthalize('account_transfers')."
				WHERE transfer_status = ".ACCOUNT_TRANSFER_INSUFFICIENT_FUNDS);
			$int_records = $qry_update->RowCount();
			
			$qry_update->Query("
				UPDATE ".Monthalize('account_transfers')."
				SET transfer_status = ".ACCOUNT_TRANSFER_PENDING."
				WHERE transfer_status = ".ACCOUNT_TRANSFER_INSUFFICIENT_FUNDS);
			if ($qry_update->b_error == true) {
				$_SESSION['str_transfer_message'] = "Error updating the status of ".$int_records." transfers";
			}
			else {
				$_SESSION['str_transfer_message'] = "Updated the status of ".$int_records." transfers successfully";
			}
			
			header("Location:transfers.php?".$grid->buildQueryString());
			exit;
		}
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
	parent.parent.frames["accountscontent"].doResize(2);
}

function changeStatus() {
	sNo = getSelectedSerialNumber();
	if (sNo>0) {
		myWin = window.open("transfer_status.php?id="+sNo,'transfer_status','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=no,width=500,height=400,top=0,left=0');
		myWin.moveTo((screen.availWidth/2 - 500/2), (screen.availHeight/2 - 400/2));
		myWin.focus();
	}
	else
		alert("Select a transfer");
}

function changeNoFunds() {
	if (confirm("Are you sure ?")) {
		if (document.location.href.indexOf("?")<0) {
			document.location = document.location.href+"?action=flag_no_funds";
		} else {
			document.location = document.location.href+"&action=flag_no_funds";
		}
	}
}

</script>
</head>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>

<? 

//$grid->addUniqueFilter('transfer_status', 'equals', '', 'string');

$grid_form = new GridForm();
//$grid->b_debug=true;
$grid->prepareQuery();
$grid_form->setGrid($grid);
//$grid_form->str_stylesheet = $str_css_filename;
/*if ($int_access_level > ACCESS_READ) {

	$grid_form->addButton('Transfer part or all of selected batch to other storeroom','../images/transfer.gif','transferRecord','left');

}*/
//$grid_form->addControl('showdeleted','left');
//$grid_form->addControl('filter0','center');


$grid_form->addButton('Change the status of the transfer','../images/transmit_edit.png','changeStatus','left');
$grid_form->addHTML('&nbsp;', 'left');
$grid_form->addButton('Flag all No Funds transfers as Pending','../images/transmit_go.png','changeNoFunds','left');
$grid_form->addHTML('&nbsp;<font style=\"font-size:12px\">Status:&nbsp;</font> ', 'center');
$grid_form->addSelectionControl('filter0','transfer_status', $arr_filter_type, 'center');
$grid_form->addHTML('&nbsp;', 'center');
$grid_form->addControl('advfilter1','center');
$grid_form->addControl('nav','right');
$grid_form->addControl('refresh','right');
$grid_form->addControl('view','right');
$grid_form->addButton('Resize','../images/resize.gif','doResize','right');
$grid_form->setFrames('transfersmenu','transferscontent');
$grid_form->draw();

	if (!empty($_SESSION['str_transfer_message'])) { ?>
		<script language="javascript">
		alert('<? echo $_SESSION['str_transfer_message']; ?>');
		</script>
<?
		$_SESSION['str_transfer_message'] = '';
	}
?>
</body>
</html>