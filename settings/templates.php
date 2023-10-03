<?
/**
*
* @version 		$Id: orders.php,v 1.6 2006/02/25 06:29:23 cvs Exp $
* @copyright 		Cynergy Software 2005
* @author		Luk Gastmans
* @date			12 Oct 2005
* @module 		Bills Grid
* @name  		bills.php
*
* This file uses the Grid component to generate the bills grid
*/

	$str_cur_module='Settings';

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../include/grid.inc.php");

	$int_access_level = (getModuleAccessLevel('Settings'));
	$module_billing = getModule('Settings');

	$_SESSION["int_settings_menu_selected"] = 1;

	if ($_SESSION["int_user_type"] > 1) {
		$int_access_level = ACCESS_ADMIN;
	}

	function get_template_type($f_field, $f_qry) {
		if ($f_qry->FieldByName($f_field) == TEMPLATE_BILL)
			echo "Bill";
		else if ($f_qry->FieldByName($f_field) == TEMPLATE_ORDER_INVOICE)
			echo "Order invoice";
		else if ($f_qry->FieldByName($f_field) == TEMPLATE_ORDER_PROFORMA)
			echo "Order proforma invoice";
		else
			echo "unknown";
	}

	$grid = new DBGrid('settings_templates');
	
	$grid->addColumn("Name", "name", "string", true, 250);
	$grid->addColumn("Type", "template_type", "custom", false, 250, 'get_template_type');
	$grid->addColumn("Default", "is_default", "boolean", false, 100);
	
	$grid->loadView();

	$grid->setQuery("
		SELECT id, name, is_default, template_type
		FROM templates
		ORDER BY name
	");

	$grid->processParameters($_GET);
	$grid->setOnClick('gridClick','id');
	$grid->setSubmitURL('templates.php');

if (!empty($_GET["action"]))
	if ($_GET["action"]=="del") {
		require("template_delete.php");
		
		$str_retval = deleteTemplate($_GET["delid"]);
		
		$arr_retval = explode('|', $str_retval);
		$_SESSION['str_template_delete_message'] = $arr_retval[1];
		
		header("Location:templates.php?".$grid->buildQueryString());
		exit;
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

		function newTemplate() {
			myWin = window.open("template_new.php",'create_template','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=no,resizable=no,width=800,height=700,top=0');
			myWin.moveTo((screen.availWidth/2 - 800/2), (screen.availHeight/2 - 700/2));
			myWin.focus();
		}

		function modifyTemplate() {
			sNo = getSelectedSerialNumber();

			if (sNo > 0) {
				myWin = window.open("template_edit.php?id="+sNo,'modify_template','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=no,resizable=no,width=800,height=700,top=0');
				myWin.moveTo((screen.availWidth/2 - 800/2), (screen.availHeight/2 - 700/2));
				myWin.focus();
			}
			else
				alert("Select an order to modify");
		}
		
		function deleteTemplate() {
			sNo = getSelectedSerialNumber();
			if (sNo>0) {
				if (confirm('Are you sure?')) {
					if (document.location.href.indexOf("?")<0) {
						document.location = document.location.href+"?action=del&delid="+sNo;
					} else {
						document.location = document.location.href+"&action=del&delid="+sNo;
					}
				}
			}
			else
				alert("Select an order to delete");
		}

	</script>

</head>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0>

<?
	$grid_form = new GridForm();
	$grid->prepareQuery();
	$grid_form->setGrid($grid);

	$grid_form->addButton('New Template','../images/page.png','newTemplate','left');
	$grid_form->addHTML('&nbsp;', 'left');
	$grid_form->addButton('Edit Template','../images/page_edit.png','modifyTemplate','left');
	$grid_form->addHTML('&nbsp;', 'left');
	$grid_form->addButton('Delete Template','../images/cross.png','deleteTemplate','left');
	$grid_form->addHTML('&nbsp;', 'left');


	$grid_form->addControl('filter0','center');

	$grid_form->addControl('nav','right');
	$grid_form->addControl('refresh','right');
	$grid_form->addControl('view','right');
	$grid_form->setFrames('templates_menu','templates_content');
	$grid_form->draw();

	if (!empty($_SESSION["str_template_delete_message"])) {
		echo "<script language=\"javaScript\">";
		echo "alert('".$_SESSION["str_template_delete_message"]."');";
		echo "</script>";
	
		$_SESSION["str_template_delete_message"] = "";
	}

?>

</body>
</html>