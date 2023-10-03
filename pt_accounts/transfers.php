<?
/**
* 
* @version 	$Id: accounts.php,v 1.1.1.1 2006/02/14 05:03:58 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* @date		09 Dec 2005
* @module 	Accounts Grid
* @name  	accounts.php
* 
* This file uses the Grid component to generate the stock grid
*/
  $str_cur_module='PT Accounts';
  require_once("../include/const.inc.php");
  require_once("../include/session.inc.php");
  require_once("../include/db.inc.php");
  require_once "../include/grid.inc.php";

  // context sensitive help
//  $_SESSION['str_context_help']='pt_accounts/help/index.php';

  $int_access_level = (getModuleAccessLevel('PT Accounts'));

  if ($_SESSION["int_user_type"]>1) {	
	 $int_access_level = ACCESS_ADMIN;
  } 

  $module_stock = getModule('PT Accounts');
  if (!($module_stock->monthExists($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']))) {
	 dieWithError("This module has no information for the selected month.");	
  }
  
  function getTransferStatus($f_field, $f_qry) {
		switch ($f_qry->FieldByName($f_field)) {
			case ACCOUNT_TRANSFER_PENDING:
				echo "Pending";
				break;
			case ACCOUNT_TRANSFER_INSUFFICIENT_FUNDS:
				echo "Insufficient Funds";
				break;
			case ACCOUNT_TRANSFER_ERROR:
				echo "Error";
				break;
			case ACCOUNT_TRANSFER_CANCELLED:
				echo "<font color='red'>Cancelled</font>";
				break;
			case ACCOUNT_TRANSFER_HOLD:
				echo "On Hold";
				break;
			case ACCOUNT_TRANSFER_COMPLETE:
				echo "Complete";
				break;
			default:
				echo "Unknown";
				break;
		}
	}

$grid = new DBGrid('pt_accounts_transfers');

$grid->addColumn("Account", "account_number", "string", true); 
$grid->addColumn("Name", "account_name", "string", true); 
$grid->addColumn("Amount", "amount", "number", true); 
$grid->addColumn("Description", "description", "string", true); 
$grid->addColumn("Status", "transfer_status", "custom", true, 100, 'getTransferStatus');
$grid->addColumn("Date", "date_created", "date", true); 
$grid->addColumn("User", "username", "string", true); 

$grid->loadView();

$grid->setQuery("SELECT
	ap.account_name,
	ap.account_number,
	apt.amount,
	apt.description,
	apt.date_created,
	apt.transfer_status,
	u.username
FROM 
	".Monthalize('account_pt_transfers')." apt
LEFT JOIN
  account_pt ap
ON (apt.id_from = ap.account_id)
LEFT JOIN
  user u
ON (apt.user_id = u.user_id)
");

//  $grid->setOnClick('gridClick','c.community_id');
  $grid->setSubmitURL('transfers.php');
  $grid->processParameters($_GET);
  
/*  if (!empty($_GET["action"]))
  	if ($_GET["action"]=="del") {
  		require("community_delete.php");
  
  		$_SESSION['str_message'] = deleteCommunity($_GET["delid"]);
  
  		header("Location:communities.php?".$grid->buildQueryString());
  		exit;
  	}
*/
  $_SESSION["int_pt_accounts_selected"]=2;
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
  forceResize(1,0);
  parent.frames["accountscontentdetail"].frames["transferscontent"].document.location="accounts_transfers.php?id="+id;
}

function doResize(win) {

	if (parent.frames["accountscontentdetail"].innerWidth)
	{
		frameWidth = parent.frames["accountscontentdetail"].innerWidth;
		frameHeight = parent.frames["accountscontentdetail"].innerHeight;
	}
	else if (parent.frames["accountscontentdetail"].document.documentElement && parent.frames["accountscontentdetail"].document.documentElement.clientWidth)
	{
		frameWidth = parent.frames["accountscontentdetail"].document.documentElement.clientWidth;
		frameHeight = parent.frames["accountscontentdetail"].document.documentElement.clientHeight;
	}
	else if (parent.frames["accountscontentdetail"].document.body)
	{
		frameWidth = parent.frames["accountscontentdetail"].document.body.clientWidth;
		frameHeight = parent.frames["accountscontentdetail"].document.body.clientHeight;
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

	if (parent.frames["accountscontentdetail"].innerWidth)
	{
		frameWidth = parent.frames["accountscontentdetail"].innerWidth;
		frameHeight = parent.frames["accountscontentdetail"].innerHeight;
	}
	else if (parent.frames["accountscontentdetail"].document.documentElement && parent.frames["accountscontentdetail"].document.documentElement.clientWidth)
	{
		frameWidth = parent.frames["accountscontentdetail"].document.documentElement.clientWidth;
		frameHeight = parent.frames["accountscontentdetail"].document.documentElement.clientHeight;
	}
	else if (parent.frames["accountscontentdetail"].document.body)
	{
		frameWidth = parent.frames["accountscontentdetail"].document.body.clientWidth;
		frameHeight = parent.frames["accountscontentdetail"].document.body.clientHeight;
	}

  if (win==1) {
    
    if (asize == "1") {
      parent.document.body.rows="30,*,1";
    } 
    else {
      parent.document.body.rows="30,*,150";
    }
  }
}

function deleteRecord() {
	sNo = getSelectedSerialNumber();
	if (sNo>0) {
		if (confirm("Are you sure you want to delete community with id "+sNo+"?")) {
			if (document.location.href.indexOf("?")<0) {
				document.location = document.location.href+"?action=del&delid="+sNo;
			} else {
				document.location = document.location.href+"&action=del&delid="+sNo;
			}
		}
	}
	else
		alert("Select a community to delete");
}
		
function newRecord() {
  myWin = window.open("community_new.php",'community_new','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
  myWin.focus();
}

function modifyRecord() {
  sNo = getSelectedSerialNumber();
  if (sNo>0) {
    myWin = window.open("community_edit.php?id="+intSerialNumber,'account_edit','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
    myWin.focus();
  }
  else {
    alert("Select a record to modify!");
  }
}


</script>
</head>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>
<?

$grid_form = new GridForm();

$grid_form->setGrid($grid);
$grid_form->addControl('advfilter0','center');
$grid_form->addControl('nav','right');
$grid_form->addControl('view','right');
$grid_form->addControl('refresh','right');
$grid_form->addButton('Resize','../images/resize.gif','doResize(1);','right');
$grid_form->setFrames('transfersmenu','transferscontent');
$grid_form->draw();

if (!empty($_SESSION["str_message"])) {
	echo "<script language=\"javaScript\">";
	echo "alert('".$_SESSION["str_message"]."');";
	echo "</script>";

	$_SESSION["str_message"]="";
 }

?>
<script language="JavaScript">
  forceResize(1,1);
</script>
</body>
</html>