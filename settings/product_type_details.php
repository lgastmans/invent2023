<?
require_once("../include/const.inc.php");
require_once("../include/session.inc.php");
require_once("../include/db.inc.php");
require_once "../include/grid.inc.php";

$int_access_level = (getModuleAccessLevel('Settings'));

if ($_SESSION["int_user_type"] > 1) {
	$int_access_level = ACCESS_ADMIN;
}


$grid = new Grid();

$grid->addColumn("Description", "description", "string", true);

$grid->setQuery("
	SELECT *
	FROM stock_type_description
");

$grid->setOnClick('gridClick','stock_type_description_id');
$grid->setSubmitURL('product_type_details.php');

if (!empty($_GET['id'])) {
	$grid->addUniqueFilter('stock_type_id', 'equals', $_GET['id'], 'number');
}
else {
	$grid->processParameters($_GET);
}

if (!empty($_GET["action"])) {
	if ($_GET["action"]=="del") {
		require_once('product_type_description_delete.php');
		
		$str_retval = delete_product_type_description($_GET["delid"]);
		$arr_retval = explode("|", $str_retval);
		
		$_SESSION['str_settings_message'] = $arr_retval[1];
		
		header("Location:product_type_details.php?id=".$_GET['id']."&".$grid->buildQueryString());
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
	intSerialNumber = id;
}

function newRecord() {
	myWin = window.open("product_type_descr_edit.php?product_type_id=<? echo $_GET['id']; ?>",'product_type_description','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550');
	myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 550/2));
	myWin.focus();
}

function modifyRecord() {
	sNo = getSelectedSerialNumber();
	if (sNo>0) {
		myWin = window.open("product_type_descr_edit.php?id="+intSerialNumber,'product_type_description','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550');
		myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 550/2));
		myWin.focus();
	}
	else
		alert("Select a record to modify!");
}

function deleteRecord() {
	sNo = getSelectedSerialNumber();
	if (sNo>0) {
		if (confirm("Are you sure you want to delete this description?")) {
			if (document.location.href.indexOf("?")<0) {
				document.location = document.location.href+"?action=del&delid="+sNo;
			} else {
				document.location = document.location.href+"&action=del&delid="+sNo;
			}
		}
	}
	else 
		alert("Select a record to delete!");
}

function doResize() {
	parent.parent.frames["admincontent"].doResize(2);
}

</script>
</head>

<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>
<? 

$grid_form = new GridForm();
$grid_form->setGrid($grid);

if ($int_access_level == ACCESS_ADMIN) {
	$grid_form->addButton('Add a new description for the selected product type','../images/page.png','newRecord','left');
	$grid_form->addHTML('&nbsp;', 'left');
	$grid_form->addButton('Edit the selected description','../images/page_edit.png','modifyRecord','left');
	$grid_form->addHTML('&nbsp;', 'left');
	$grid_form->addButton('Delete the selected description','../images/cross.png','deleteRecord','left');
}

$grid_form->addControl('nav','right');
$grid_form->addControl('refresh','right');
$grid_form->addButton('Resize','../images/resize.gif','doResize','right');
$grid_form->setFrames('detailsmenu','detailscontent');
$grid_form->draw();


if (!empty($_SESSION["str_settings_message"])) {
	?><script language="JavaScript">
	alert("<? echo $_SESSION["str_settings_message"]; ?>");
	</script>
	<?
	$_SESSION["str_settings_message"]="";
}
?>
</body>
</html>