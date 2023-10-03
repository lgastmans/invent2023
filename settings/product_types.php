<?
$str_cur_module='Settings';

require_once("../include/const.inc.php");
require_once("../include/session.inc.php");
require_once("../include/db.inc.php");
require_once "../include/grid.inc.php";

$int_access_level = (getModuleAccessLevel('Admin'));

if ($_SESSION["int_user_type"]>1) {
	$int_access_level = ACCESS_ADMIN;
}

$grid = new Grid();

$grid->addColumn("Product Type", "product_type", "string", true);

$grid->setQuery("
	SELECT *
	FROM stock_type
");

$grid->setOnClick('gridClick','stock_type_id');
$grid->setSubmitURL('product_types.php');

$grid->processParameters($_GET);

if (!empty($_GET["action"])) {
	if ($_GET["action"]=="del") {
		require_once('product_type_delete.php');
		
		$str_retval = delete_product_type($_GET["delid"]);
		$arr_retval = explode("|", $str_retval);
		
		$_SESSION['str_settings_message'] = $arr_retval[1];
		
		header("Location:product_types.php?".$grid->buildQueryString());
		exit;
	}
}

$_SESSION['int_settings_menu_selected'] = 4;

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
	parent.frames["product_types_detail"].frames["detailscontent"].document.location="product_type_details.php?id="+id;
}

function doResize(win) {
	if (parent.frames["product_types_detail"].innerWidth) {
		frameWidth = parent.frames["product_types_detail"].innerWidth;
		frameHeight = parent.frames["product_types_detail"].innerHeight;
	}
	else if (parent.frames["product_types_detail"].document.documentElement && parent.frames["product_types_detail"].document.documentElement.clientWidth) {
		frameWidth = parent.frames["product_types_detail"].document.documentElement.clientWidth;
		frameHeight = parent.frames["product_types_detail"].document.documentElement.clientHeight;
	}
	else if (parent.frames["product_types_detail"].document.body) {
		frameWidth = parent.frames["product_types_detail"].document.body.clientWidth;
		frameHeight = parent.frames["product_types_detail"].document.body.clientHeight;
	}

	if (win==1) {
		if (frameHeight != "1") {
			parent.document.body.rows="30,*,1";
		}
		else
			parent.document.body.rows="30,*,250";
		
	}
	else {
		if (frameHeight <= 150) {
			parent.document.body.rows="1,1,*";
		}
		else
			parent.document.body.rows="30,*,250";
	}
}

function forceResize(win,asize) {

	if (parent.frames["product_types_detail"].innerWidth) {
		frameWidth = parent.frames["product_types_detail"].innerWidth;
		frameHeight = parent.frames["product_types_detail"].innerHeight;
	}
	else if (parent.frames["product_types_detail"].document.documentElement && parent.frames["product_types_detail"].document.documentElement.clientWidth) {
		frameWidth = parent.frames["product_types_detail"].document.documentElement.clientWidth;
		frameHeight = parent.frames["product_types_detail"].document.documentElement.clientHeight;
	}
	else if (parent.frames["product_types_detail"].document.body) {
		frameWidth = parent.frames["product_types_detail"].document.body.clientWidth;
		frameHeight = parent.frames["product_types_detail"].document.body.clientHeight;
	}

	if (win==1) {
		if (asize == "1") {
			parent.document.body.rows="30,*,1";
		}
		else
			parent.document.body.rows="30,*,250";
	}
}

function newRecord() {
	myWin = window.open("product_type_edit.php",'product_type','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
	myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 550/2));
	myWin.focus();
}

function modifyRecord() {
	sNo = getSelectedSerialNumber();
	if (sNo>0) {
		myWin = window.open("product_type_edit.php?id="+intSerialNumber,'product_type','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
		myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 550/2));
		myWin.focus();
	}
	else
		alert("Select a record to modify!");
}

function deleteRecord() {
	sNo = getSelectedSerialNumber();
	if (sNo>0) {
		if (confirm("Are you sure you want to delete this product type ?")) {
			if (document.location.href.indexOf("?")<0) {
				document.location = document.location.href+"?action=del&delid="+sNo;
			}
			else {
				document.location = document.location.href+"&action=del&delid="+sNo;
			}
		}
	}
	else
		alert("Select a record to delete!");
}


</script>
</head>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>
<?

$grid_form = new GridForm();

$grid_form->setGrid($grid);

if ($int_access_level > ACCESS_READ) {
	if ($int_access_level==ACCESS_ADMIN) {
		$grid_form->addButton('Add a new product type','../images/page.png','newRecord','left');
		$grid_form->addHTML('&nbsp;', 'left');
	}
	
	$grid_form->addButton('Edit the selected product type','../images/page_edit.png','modifyRecord','left');
	
	if ($int_access_level==ACCESS_ADMIN) {
		$grid_form->addHTML('&nbsp;', 'left');
		$grid_form->addButton('Delete the selected product type','../images/cross.png','deleteRecord','left');
	}
}
$grid_form->addControl('advfilter0','center');
$grid_form->addControl('nav','right');
$grid_form->addControl('refresh','right');
$grid_form->addButton('Resize','../images/resize.gif','doResize(1);','right');
$grid_form->setFrames('product_types_menu','product_types_content');
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