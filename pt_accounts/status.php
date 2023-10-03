<?
	$str_cur_module='PT Accounts';
	
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once "../include/grid.inc.php";
	
	$int_access_level = (getModuleAccessLevel('PT Accounts'));

	if ($_SESSION["int_user_type"] > 1) {
		$int_access_level = ACCESS_ADMIN;
	}

	$module_stock = getModule('PT Accounts');
	if (!($module_stock->monthExists($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']))) {
		dieWithError("This module has no information for the selected month.");
	}

	$grid = new DBGrid('pt_accounts_status');
	
	$grid->addColumn("Status", "status", "string", true, 200);
	
	$grid->loadView();
	
	$grid->setQuery("
		SELECT *
		FROM account_pt_status aps
	");
	
	$grid->setOnClick('gridClick','status_id');
	$grid->setSubmitURL('status.php');
	$grid->processParameters($_GET);
	$grid->addOrder('status', 'ASC');
	
	if (!empty($_GET["action"])) {
		if ($_GET["action"]=="del") {
			require("account_delete.php");
		
			$_SESSION['str_message'] = deleteAccount($_GET["delid"]);
		
			header("Location:accounts.php?".$grid->buildQueryString());
			exit;
		}
	}
	
	$_SESSION["int_pt_accounts_selected"] = 7;

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

function newRecord() {
	myWin = window.open("status_edit.php",'account_new','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
	myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 550/2));
	myWin.focus();
}

function modifyRecord() {
  sNo = getSelectedSerialNumber();
  if (sNo>0) {
    myWin = window.open("status_edit.php?id="+intSerialNumber,'account_edit','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
	myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 550/2));
    myWin.focus();
  }
  else {
    alert("Select a record to modify!");
  }
}

function deleteRecord() {
	sNo = getSelectedSerialNumber();
	if (sNo>0) {
		if (confirm("Are you sure you want to delete account with id "+sNo+"? \n WARNING: All corresponding transfers will also be deleted.")) {
			if (document.location.href.indexOf("?")<0) {
				document.location = document.location.href+"?action=del&delid="+sNo;
			} else {
				document.location = document.location.href+"&action=del&delid="+sNo;
			}
		}
	}
	else
		alert("Select an account to delete");
}
</script>
</head>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>
<?

$grid_form = new GridForm();

$grid_form->setGrid($grid);
if ($int_access_level > ACCESS_READ) {
	$grid_form->addButton('Add new account','../images/new.gif','newRecord','left');	
	$grid_form->addButton('Edit account','../images/modify.gif','modifyRecord','left');	
	$grid_form->addButton('Delete selected account','../images/delete.gif','deleteRecord','left');	
}
$grid_form->addControl('export', 'left');
$grid_form->addControl('advfilter0','center');
$grid_form->addControl('nav','right');
$grid_form->addControl('view','right');
$grid_form->addControl('refresh','right');
$grid_form->addButton('Resize','../images/resize.gif','doResize(1);','right');
$grid_form->setFrames('menu','content');
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