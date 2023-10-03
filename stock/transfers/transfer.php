<?
/**
* 
* @version 	$Id: transfer.php,v 1.2 2006/02/20 03:58:37 cvs Exp $
* @copyright 	Cynergy Software 2005
* @author	Luk Gastmans
* @date		12 Oct 2005
* @module 	Product View Grid
* @name  	viewstock.php
* 
* This file uses the Grid component to generate the stock grid
*/
  $str_cur_module='Stock';
  require_once("../../include/const.inc.php");
  require_once("../../include/session.inc.php");
  require_once("../../include/db.inc.php");
  require_once "../../include/grid.inc.php";

  // context sensitive help
  $_SESSION['str_context_help']='stock/help/transfer.html';

  $int_access_level = (getModuleAccessLevel('Stock'));

      if ($_SESSION["int_user_type"]>1) {	
            $int_access_level = ACCESS_ADMIN;
      } 
      
      $module_stock = getModule('Stock');
      if (!($module_stock->monthExists($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']))) {
            dieWithError("This module has no information for the selected month.");	
      }

      //==================
      // get user settings
      //------------------
      $qry_settings = new Query("
	    SELECT stock_show_available, bill_decimal_places
	    FROM user_settings
	    WHERE (storeroom_id = ".$_SESSION['int_current_storeroom'].")
      ");
      $str_show_available = 'Y';
      $int_decimal_places = 2;
      if ($qry_settings->RowCount() > 0) {
	    $str_show_available = $qry_settings->FieldByName('stock_show_available');
	    $int_decimal_places = $qry_settings->FieldByName('bill_decimal_places');
      }

      function get_mysql_date($int_day, $int_month, $int_year) {
	      $str_retval = $int_year."-".sprintf("%02d", $int_month)."-".sprintf("%02d", $int_day);
	      return $str_retval;
      }

      $int_num_days = DaysInMonth2($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']);
      $arr_days = array();
      for ($i=1;$i<=$int_num_days;$i++) {
	      $arr_days[$i] = get_mysql_date($i, $_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']);
      }



function drawQty($f_field, $f_qry) {
      global $int_decimal_places;

      if ($f_qry->FieldByName('is_decimal') == 'Y') {
	    if ($int_decimal_places == 2)
		  echo sprintf("%0.2f",$f_qry->FieldByName($f_field));
	    else
		  echo sprintf("%0.3f",$f_qry->FieldByName($f_field));
      }
      else
	    echo sprintf("%0.0f",$f_qry->FieldByName($f_field));
}

function drawProd($f_field, $f_qry) {
	echo "<b>".$f_qry->FieldByName($f_field)."</b>";
}

$grid = new DBGrid('stock_transfer');

$grid->addColumn("Code", "sp.product_code", "string", true, 50); 
$grid->addColumn("Description", "sp.product_description", "custom", true,300,"drawProd");
$grid->addColumn("Reference", "ssb.transfer_reference", "string", true, 100);
$grid->addColumn("Batch", "sb.batch_code", "string", true,100); 
$grid->addColumn("Date", "ssb.date_created", "date", true,100); 
$grid->addColumn("Qty", "ssb.transfer_quantity", "custom", false, 50, "drawQty"); 
$grid->addColumn("Unit", "mu.measurement_unit", "string", false, 35);
$grid->addColumn("S Price", "sb.selling_price", "number", true,60);
$grid->addColumn("To", "s2.description", "string", true); 
$grid->addColumn("Description", "ssb.transfer_description", "string", true); 
$grid->addColumn("Type", "st.transfer_type_description", "string", true);
$grid->addColumn("Supplier", "sup.supplier_name", "string", true);
$grid->addColumn("User", "u.username", "string", true, 100);
$grid->loadView();

$grid->setQuery("SELECT
	sp.product_id as `sp.product_id`,
	sp.product_code as `sp.product_code`,
	sp.product_description as `sp.product_description`,
	sb.batch_code as `sb.batch_code`,
	sb.date_created as `sb.date_created`,
	sb.selling_price as `sb.selling_price`,
	mu.measurement_unit as `mu.measurement_unit`,
	ssb.storeroom_id_from as `ssb.storeroom_id_from`,
	ssb.storeroom_id_to as `ssb.storeroom_id_to`,
	ssb.transfer_type as `ssb.transfer_type`,
	ssb.transfer_quantity as `ssb.transfer_quantity`,
	ssb.transfer_description as `ssb.transfer_description`,
	ssb.transfer_reference as `ssb.transfer_reference`,
	ssb.transfer_id as `ssb.transfer_id`,
	ssb.transfer_status as `ssb.transfer_status`,
	s2.description as `s2.description`,
	st.transfer_type_description as `st.transfer_type_description`,
	ssb.date_created as `ssb.date_created`,
	sup.supplier_name as `sup.supplier_name`,
      u.username as `u.username`,
      mu.is_decimal
	
FROM 
	".Monthalize("stock_transfer")." ssb
INNER JOIN
      ".Yearalize("stock_batch")." sb 
ON	
      sb.batch_id = ssb.batch_id
INNER JOIN
      stock_product sp
ON
      sp.product_id = sb.product_id
INNER JOIN 
      stock_measurement_unit mu
ON 
      sp.measurement_unit_id=mu.measurement_unit_id
INNER JOIN
      stock_transfer_type st 
ON	
      st.transfer_type = ssb.transfer_type
LEFT JOIN 
      stock_storeroom s2
ON	
      s2.storeroom_id = ssb.storeroom_id_to
LEFT JOIN
      stock_supplier sup
ON
      sup.supplier_id = sb.supplier_id      
INNER JOIN user u ON (u.user_id = ssb.user_id)
");
//die ($grid->str_query_string);
//$grid->setDeletedField('sb.deleted');
//$grid->setShowDeleted(true);

$grid->setOnClick('gridClick','ssb.transfer_id');
$grid->setSubmitURL('transfer.php');
$grid->addUniqueFilter('ssb.storeroom_id_from','equals',$_SESSION['int_current_storeroom'],'number');
$grid->addUniqueFilter('ssb.transfer_status','equals',STATUS_COMPLETED,'number');
$grid->addUniqueFilter('sp.deleted','equals','N','string');
$grid->processParameters($_GET);

//$grid->addUniqueFilter('product_description','start with','','string');

if (!empty($_GET["action"])) 
  if ($_GET["action"]=="del") {
    require("transferdelete.php");
    
    $_SESSION['str_stock_message'] = deleteRecord($_GET["delid"]);
    
    header("Location:transfer.php?".$grid->buildQueryString());
    exit;
  }
  $_SESSION['int_stock_selected'] = 4;
?>
<html>
<head><TITLE></TITLE>
<link href="../../include/styles.css" rel="stylesheet" type="text/css">

<script language='javascript'>

	var intSerialNumber=0;

	function getSelectedSerialNumber() {
		return intSerialNumber;
	}
	
	function gridClick(id) {
		intSerialNumber = id;
	}

	function transferRecord() {
		sNo = getSelectedSerialNumber();
		if (sNo>0) {
			myWin = window.open("transfertransfer.php",'stock','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=550,top=0');
			myWin.focus();
		}
		else
			alert("Select a transfer to transfer!");
	}

	function modifyRecord() {
		sNo = getSelectedSerialNumber();
		if (sNo>0) {
			myWin = window.open("transfer_edit.php?id="+sNo,'transfer_edit','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=450,height=300,top=0');
			myWin.moveTo((screen.availWidth/2 - 450/2), (screen.availHeight/2 - 300/2));
			myWin.focus();
		}
		else
			alert("Select a transfer to modify.");
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
$grid_form->str_stylesheet='../../include/styles.css';
$grid->str_image_path='../../images/';

	$arr_filter_type = array();
	$arr_filter_type['Internal Transfer'] = 1;
	$arr_filter_type['Returned Goods'] = 2;
	$arr_filter_type['Bill'] = 3;
	$arr_filter_type['Adjustment'] = 4;
	$arr_filter_type['Received Goods'] = 5;
	$arr_filter_type['Correction'] = 6;
	$arr_filter_type['Cancelled'] = 7;

	$grid->addUniqueFilter('ssb.transfer_type', 'equals', '', 'string');
	$grid->addUniqueFilter('sb.date_created', 'equals', '', 'string');

	if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
		if ($int_access_level > ACCESS_READ) {
			$grid_form->addHTML('&nbsp;', 'left');
			$grid_form->addButton('Edit details for selected entry','../../images/book_edit.png','modifyRecord','left');
		}
	}
	$grid_form->addHTML('&nbsp;', 'left');
	$grid_form->addControl('print','left');
	
	$grid_form->addHTML('&nbsp;<font style=\"font-size:12px\">Type:&nbsp;</font> ', 'center');
	$grid_form->addSelectionControl('filter3','ssb.transfer_type', $arr_filter_type, 'center');
	$grid_form->addHTML('&nbsp;<font style=\"font-size:12px\">Day:&nbsp;</font>', 'center');
	$grid_form->addSelectionControl('filter4','sb.date_created', $arr_days, 'center');
	$grid_form->addHTML('&nbsp;', 'center');
	$grid_form->addControl('filter5','center');

$grid_form->addControl('nav','right');
$grid_form->addControl('view','right');
$grid_form->addControl('pagesize','right');
$grid_form->addControl('refresh','right');
$grid_form->setFrames('transfermenu','transfercontent');
$grid_form->draw();

// if there are any stock messages, display them and clear the session message

if (!empty($_SESSION["str_stock_message"])) {
 ?><script language="JavaScript">
 alert("<? echo $_SESSION["str_stock_message"]; ?>");
 </script>
 <?
  $_SESSION["str_stock_message"]="";
 }
?>
<script language="JavaScript">
  forceResize(1,1);

 </script>
</body>
</html>