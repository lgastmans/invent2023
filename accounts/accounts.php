<?
/**
* 
* @version 	$Id: accounts.php,v 1.2 2006/02/25 06:29:23 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* @date		09 Dec 2005
* @module 	Accounts Grid
* @name  	accounts.php
* 
* This file uses the Grid component to generate the stock grid
*/

      $str_cur_module='FS Accounts';
      
      require_once("../include/const.inc.php");
      require_once("../include/session.inc.php");
      require_once("../include/db.inc.php");
      require_once "../include/grid.inc.php";

      // context sensitive help
      $_SESSION['str_context_help']='accounts/help/index.php';

      $int_access_level = (getModuleAccessLevel('FS Accounts'));

      if ($_SESSION["int_user_type"]>1) {	
            $int_access_level = ACCESS_ADMIN;
      } 

      $module_stock = getModule('FS Accounts');
      if (!($module_stock->monthExists($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']))) {
            dieWithError("This module has no information for the selected month.");	
      }

      $_SESSION["int_accounts_selected"] = 1;


function drawType($f_field, $f_qry) {
	switch ($f_qry->FieldByName($f_field)) {
		case 3: echo "KIND";break;
		case 4: echo "CASH";break;
		default:echo "OTHER";
	} 
//	echo "<b>".$f_qry->FieldByName($f_field)."</b>";
}

$grid = new DBGrid('accounts_main');

$grid->addColumn("CC ID", "ac.cc_id", "string", true, 50); 
$grid->addColumn("Account", "ac.account_number", "string", true,60); 
$grid->addColumn("Type", "ac.account_type", "custom", true,50,'drawType'); 
$grid->addColumn("Account Name", "ac.account_name", "string", true); 
$grid->addColumn("Community", "ac.community", "string", true); 
$grid->addColumn("Profile", "ap.profile_name", "string", true); 
$grid->addColumn("Comments", "ar.account_comments", "string", true); 
$grid->addColumn("Debit", "ar.debit_balance", "currency", true); 
$grid->addColumn("Credit", "ar.credit_balance", "currency", true); 
$grid->addColumn("Enabled", "ac.account_enabled", "boolean", true); 

$grid->loadView();


$grid->setQuery("SELECT
	ac.account_number as `ac.account_number`,
	ac.cc_id as `ac.cc_id`,
	ac.account_name as `ac.account_name`,
	ac.community as `ac.community`,
	ac.account_enabled as `ac.account_enabled`,
	ac.account_type as `ac.account_type`,
	ar.debit_balance as `ar.debit_balance`,
	ar.credit_balance as `ar.credit_balance`,
	ar.account_comments as `ar.account_comments`,
	ap.profile_name as `ap.profile_name`,
	ap.profile_id as `ap.profile_id`
FROM 
	account_cc ac
LEFT JOIN 
	".Monthalize('account_record')." ar
ON 
	ar.cc_id = ac.cc_id
LEFT JOIN
	account_profile ap
ON
	ap.profile_id = ar.profile_id");

//die ($grid->str_query_string);
//$grid->setDeletedField('deleted');
//$grid->setShowDeleted(true);

$grid->setOnClick('gridClick','ac.cc_id');
$grid->setSubmitURL('accounts.php');

$grid->processParameters($_GET);
$grid->addUniqueFilter('account_active','equals','Y','string');
//$grid->addUniqueFilter('product_description','start with','','string');

  $int_accounts_selected=1;
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
  parent.frames["accountscontentdetail"].frames["transferscontent"].document.location="accountstransfers.php?id="+id;
//  alert(id);
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
  } else parent.document.body.rows="30,*,150";
 
}
}


function openRecord() {

  myWin = window.open("viewaccount.php?id="+intSerialNumber,'stock','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
  myWin.focus();
//  return false;
}

function modifyRecord() {
  sNo = getSelectedSerialNumber();
  if (sNo>0) {
    openRecord();
  } else alert("Select a record to modify!");
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
	$grid_form->addButton('Edit Profile For Account','../images/modify.gif','modifyRecord','left');	
}
$grid_form->addControl('advfilter0','center');
//$grid_form->addControl('filter1','center');
$grid_form->addControl('nav','right');
$grid_form->addControl('view','right');
//$grid_form->addControl('pagesize','right');
$grid_form->addControl('refresh','right');
$grid_form->addButton('Resize','../images/resize.gif','doResize(1);','right');
$grid_form->setFrames('accountsmenu','accountscontent');
$grid_form->draw();

// if there are any stock messages, display them and clear the session message

?>
<script language="JavaScript">
  forceResize(1,1);

 </script>
</body>
</html>