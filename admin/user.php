<?
/**
* 
* @version 	$Id
* @copyright 	Cynergy Software 2006
* @author	Luk Gastmans
* @date		8 April 2006
* @module 	User Master
* @name  	user.php
* 
* This file uses the Grid component to generate the measurement unit grid
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

  function deleteRecord($f_record_id) {
  
  	$delQuery = new Query("select * from user where user_id=$f_record_id");
	if ($delQuery->RowCount()==0) 
		return "Cannot find record.";
	$delQuery->ExecuteQuery("UPDATE user set deleted='Y' where user_id=$f_record_id");
	$delQuery->Free();
	
	return "Removed successfully.";
  } 

function drawUserType($f_field, $f_qry) {
	switch($f_qry->FieldByName($f_field)) {
		case "1": echo "Normal User"; break;
		case "2": echo "Administrator"; break;
	}
}
function drawPrediction($f_field, $f_qry) {
	switch($f_qry->FieldByName($f_field)) {
		case PO_PREDICT_NONE: echo "None";break;
		case PO_PREDICT_PREVIOUS: echo "Prev. Month";break;
		case PO_PREDICT_PREVIOUS_CURRENT: echo "Prev and Current Month";break;
		case PO_PREDICT_CURRENT: echo "Current Month";break;
	}
}
$grid = new Grid();

$grid->addColumn("User Id", "user_id", "number", true); 
$grid->addColumn("User Name", "username", "string", true); 
$grid->addColumn("Last Login", "last_login", "date", true); 
$grid->addColumn("User Type", "user_type", "custom", false,100,"drawUserType"); 
$grid->addColumn("Prediction Method", "po_prediction_method", "custom", false,150,"drawPrediction"); 

$grid->setQuery("
SELECT
      *
FROM 
      user
");

$grid->setDeletedField('deleted');

$grid->setOnClick('gridClick','user_id');
$grid->setSubmitURL('user.php');

if (($int_access_level < 3) || ($_SESSION["int_user_type"] < 2))
      $grid->addUniqueFilter('user_id','equals',$_SESSION['int_user_id'],'number');

$grid->processParameters($_GET);

if (!empty($_GET["action"])) 
  if ($_GET["action"]=="del") {
   
    $_SESSION['str_admin_message'] = deleteRecord($_GET["delid"]);
    
    header("Location:user.php?".$grid->buildQueryString());
    exit;
  }
  $_SESSION['int_admin_selected']=9;

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
  parent.frames["admincontentdetail"].frames["detailscontent"].document.location="userdetails.php?id="+id;
}

function doResize(win) {

	if (parent.frames["admincontentdetail"].innerWidth)
	{
		frameWidth = parent.frames["admincontentdetail"].innerWidth;
		frameHeight = parent.frames["admincontentdetail"].innerHeight;
	}
	else if (parent.frames["admincontentdetail"].document.documentElement && parent.frames["admincontentdetail"].document.documentElement.clientWidth)
	{
		frameWidth = parent.frames["admincontentdetail"].document.documentElement.clientWidth;
		frameHeight = parent.frames["admincontentdetail"].document.documentElement.clientHeight;
	}
	else if (parent.frames["admincontentdetail"].document.body)
	{
		frameWidth = parent.frames["admincontentdetail"].document.body.clientWidth;
		frameHeight = parent.frames["admincontentdetail"].document.body.clientHeight;
	}

if (win==1) {
  
  if (frameHeight != "1") {
    parent.document.body.rows="30,*,1";
  } else parent.document.body.rows="30,*,250";
  
 } else {
  if (frameHeight <= 150) {
    parent.document.body.rows="1,1,*";
  } else parent.document.body.rows="30,*,250";
  
 }
}

function forceResize(win,asize) {

	if (parent.frames["admincontentdetail"].innerWidth)
	{
		frameWidth = parent.frames["admincontentdetail"].innerWidth;
		frameHeight = parent.frames["admincontentdetail"].innerHeight;
	}
	else if (parent.frames["admincontentdetail"].document.documentElement && parent.frames["admincontentdetail"].document.documentElement.clientWidth)
	{
		frameWidth = parent.frames["admincontentdetail"].document.documentElement.clientWidth;
		frameHeight = parent.frames["admincontentdetail"].document.documentElement.clientHeight;
	}
	else if (parent.frames["admincontentdetail"].document.body)
	{
		frameWidth = parent.frames["admincontentdetail"].document.body.clientWidth;
		frameHeight = parent.frames["admincontentdetail"].document.body.clientHeight;
	}

if (win==1) {
  if (asize == "1") {
    parent.document.body.rows="30,*,1";
  } else parent.document.body.rows="30,*,250";
 
}
}

function newRecord() {
	myWin = window.open("user_edit.php",'user','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
	myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 550/2));
	myWin.focus();
}

function openRecord() {
	myWin = window.open("user_edit.php?id="+intSerialNumber,'user','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
	myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 550/2));
	myWin.focus();
}

function modifyRecord() {
	sNo = getSelectedSerialNumber();
	if (sNo>0) {
		openRecord(sNo);
	}
	else
		alert("Select a record to modify!");
}

function deleteRecord() {
  sNo = getSelectedSerialNumber();
  if (sNo>0) {
      if (confirm("Are you sure you want to remove this user?")) {

        if (document.location.href.indexOf("?")<0) {
          document.location = document.location.href+"?action=del&delid="+sNo;
	} else {
	  document.location = document.location.href+"&action=del&delid="+sNo;
	}
      }
  } else alert("Select a record to delete!");
}

function changePassword() {
	sNo = getSelectedSerialNumber();
	if (sNo>0) {
		myWin = window.open("user_password.php?id="+intSerialNumber,'user','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=250,top=0');
		myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 250/2));
		myWin.focus();
	}
	else
		alert('Select a user');
	
}


</script>
</head>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>
<?

$grid_form = new GridForm();

$grid_form->setGrid($grid);

if ($int_access_level > ACCESS_READ) {
	if ($int_access_level==ACCESS_ADMIN) {
			$grid_form->addButton('New User','../images/user_add.png','newRecord','left');
			$grid_form->addHTML('&nbsp;', 'left');
		}
	$grid_form->addButton('Edit User','../images/user_edit.png','modifyRecord','left');
	
	if ($int_access_level==ACCESS_ADMIN) {
		$grid_form->addHTML('&nbsp;', 'left');
		$grid_form->addButton('Delete User','../images/user_delete.png','deleteRecord','left');
		$grid_form->addHTML('&nbsp;', 'left');
		$grid_form->addButton('Change Password','../images/textfield_key.png','changePassword','left');
	}
}
$grid_form->addControl('filter0','center');
$grid_form->addControl('showdeleted','left');
//$grid_form->addControl('advfilter1','center');
$grid_form->addControl('nav','right');
$grid_form->addControl('refresh','right');
$grid_form->addButton('Resize','../images/resize.gif','doResize(1);','right');
$grid_form->setFrames('adminmenu','admincontent');
$grid_form->draw();

// if there are any stock messages, display them and clear the session message

if (!empty($_SESSION["str_admin_message"])) {
 ?><script language="JavaScript">
 alert("<? echo $_SESSION["str_admin_message"]; ?>");
 </script>
 <?
  $_SESSION["str_admin_message"]="";
 }
?>
</body>
</html>