<?
/**
* 
* @version 	$Id
* @copyright 	Cynergy Software 2006
* @author	Luk Gastmans
* @date		8 April 2006
* @module 	Customer Master
* @name  	customer.php
* 
* This file uses the Grid component
*/
	error_reporting(E_ERROR);

	$str_cur_module='Admin';
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once "../include/grid.inc.php";
	
	$int_access_level = (getModuleAccessLevel('Admin'));

	if ($_SESSION["int_user_type"]>1) {	
		$int_access_level = ACCESS_ADMIN;
	} 

	$_SESSION['int_clients_menu_selected'] = 1;

	function deleteRecord($f_record_id) {
	
		$delQuery = new Query("SELECT * FROM customer WHERE Id = $f_record_id");
		if ($delQuery->RowCount() == 0) 
			return "Cannot find record.";
		else {
			$str_customer = $delQuery->FieldByName('company');
			$delQuery->Query("DELETE FROM customer WHERE id = $f_record_id");
			$delQuery->Free();
			return "Deleted $str_customer";
		}
	} 

	$grid = new DBGrid('admin_customer');
	
	$grid->addColumn("Cust Id", "customer_id", "string", true);
	$grid->addColumn("Company", "company", "string", true);
	$grid->addColumn("Address", "address", "string", true);
	$grid->addColumn("City", "city", "string", true);
	$grid->addColumn("Phone", "phone1", "string", true);
	$grid->addColumn("Contact", "contact_person", "string", true, 100);
	$grid->addColumn("Tax Type", "sales_tax_type", "string", false, 50);
	$grid->addColumn("Tax", "tax_description", "string", false, 75);
	$grid->addColumn("Terms", "payment_terms", "string", false, 100);
	
	$grid->loadView();

	$grid->setQuery("
		SELECT
			c.*, t.tax_description
		FROM 
			customer c
		LEFT JOIN ".Monthalize('stock_tax')." t ON (t.tax_id = c.tax_id)
	");

	$grid->processParameters($_GET);
	$grid->addOrder('company', 'DESC');
	$grid->setOnClick('gridClick','id');
	$grid->setSubmitURL('client.php');
//	$grid->b_debug=true;

	if (!empty($_GET["action"]))  {
		if ($_GET["action"] == "del") {
			$_SESSION['str_admin_message'] = deleteRecord($_GET["delid"]);
			
			header("Location:client.php?".$grid->buildQueryString());
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
//		forceResize(1,0);
//		parent.frames["billing_content_detail"].frames["items_content"].document.location="bill_items.php?id="+id;
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
			} else 
				parent.document.body.rows="30,*,150";
			
		} else {
			if (frameHeight <= 150) {
				parent.document.body.rows="1,1,*";
			} else 
				parent.document.body.rows="30,*,150";
	
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
			} 
			else 
				parent.document.body.rows="30,*,150";	
		}
	}

	function newRecord() {
		myWin = window.open("viewcustomer.php",'user','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,width=750,height=850,top=0');
		myWin.moveTo((screen.availWidth/2 - 750/2), (screen.availHeight/2 - 850/2));
		myWin.focus();
	}

	function openRecord() {
		myWin = window.open("viewcustomer.php?id="+intSerialNumber,'user','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,width=750,height=850,top=0');
		myWin.moveTo((screen.availWidth/2 - 750/2), (screen.availHeight/2 - 850/2));
		myWin.focus();
	}

	function modifyRecord() {
		sNo = getSelectedSerialNumber();
		if (sNo>0) {
			openRecord(sNo);
		} else 
			alert("Select a record to modify!");
	}


	function deleteRecord() {
		sNo = getSelectedSerialNumber();
		if (sNo>0) {
			if (confirm("Are you sure ?")) {
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


</script>
</head>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>
<?

	$grid_form = new GridForm();
	$grid->prepareQuery();

	$grid_form->setGrid($grid);

	if ($int_access_level > ACCESS_READ) {
		if ($int_access_level==ACCESS_ADMIN) {
			$grid_form->addButton('New Customer','../images/user_add.png','newRecord','left');
			$grid_form->addHTML('&nbsp;', 'left');
		}
		$grid_form->addButton('Edit Customer','../images/user_edit.png','modifyRecord','left');
		
		if ($int_access_level==ACCESS_ADMIN) {
			$grid_form->addHTML('&nbsp;', 'left');
			$grid_form->addButton('Delete Customer','../images/user_delete.png','deleteRecord','left');
	
		}
	}
	$grid_form->addControl('print', 'left');
	$grid_form->addControl('filter0','center');
	$grid_form->addControl('nav','right');
	$grid_form->addControl('refresh','right');
	$grid_form->addButton('Resize','../images/resize.gif','doResize(1);','right');
	$grid_form->setFrames('clientmenu','clientcontent');
	$grid_form->draw();


if (!empty($_SESSION["str_admin_message"])) { ?>
	<script language="JavaScript">
 		alert("<? echo $_SESSION["str_admin_message"]; ?>");
 	</script>
<?
	$_SESSION["str_admin_message"]="";
} 
?>

</body>
</html>