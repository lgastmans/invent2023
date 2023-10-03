<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../include/browser_detection.php");
	require_once("../common/printer.inc.php");
	require_once("../common/print_funcs.inc.php");
	require_once("../common/tax.php");

	$copies = $arr_invent_config['billing']['print_copies'];
	$print_name = $arr_invent_config['billing']['print_name'];
	$print_mode = $arr_invent_config['billing']['print_mode'];
	$print_os = browser_detection("os");

	$code_sorting = $arr_invent_config['settings']['code_sorting'];
	
	$sql_settings = new Query("
		SELECT *
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION["int_current_storeroom"]."
	");
	if ($sql_settings->RowCount() > 0) {
		$int_eject_lines = $sql_settings->FieldByName('bill_print_lines_to_eject');
		$str_print_address = $sql_settings->FieldByName('bill_print_address');
		$str_print_phone = $sql_settings->FieldByName('bill_print_phone');
	}

	$qry_storeroom = new Query("
		SELECT *
		FROM stock_storeroom
		WHERE storeroom_id = ".$_SESSION['int_current_storeroom']."
	");
	$str_storeroom_name = $qry_storeroom->FieldByName('description');
	$str_storeroom_info = "Storeroom: ".$str_storeroom_name;
	
	$int_day = date('d', time());
	if (IsSet($_GET['selected_day']))
	    $int_day = $_GET['selected_day'];
	    
	$int_in_type = 'ALL';
	if (IsSet($_GET['in_type']))
	    $int_in_type = $_GET['in_type'];
    
	if ($int_in_type == 'ALL')
	    $str_transfer_type_filter = "
		AND (
		    ((st.transfer_type IN (1,5,7)) AND (st.storeroom_id_to = ".$_SESSION['int_current_storeroom']."))
		    OR
		    ((st.transfer_type = 2) AND (st.storeroom_id_from = ".$_SESSION['int_current_storeroom']."))
		    OR
		    (st.transfer_type = 6)
		)";
	else if ($int_in_type == TYPE_RETURNED)
	    $str_transfer_type_filter = "AND ((st.transfer_type = $int_in_type) AND (st.storeroom_id_from = ".$_SESSION['int_current_storeroom']."))";
	else if (($int_in_type == TYPE_CORRECTED) || ($int_in_type == TYPE_ADJUSTMENT))
	    $str_transfer_type_filter = "
		AND (
		    (st.transfer_type = $int_in_type) AND
		    ((st.storeroom_id_from = ".$_SESSION['int_current_storeroom'].") OR (st.storeroom_id_to = ".$_SESSION['int_current_storeroom']."))
		)";
	else
	    $str_transfer_type_filter = "AND ((st.transfer_type = $int_in_type) AND (st.storeroom_id_to = ".$_SESSION['int_current_storeroom']."))";
	
	$str_info_type = '';
	if ($int_in_type == 'ALL')
		$str_info_type = 'ALL';
	else if ($int_in_type ==  1)
		$str_info_type = 'INTERNAL';
	else if ($int_in_type == 4)
		$str_info_type = 'ADJUSTED';
	else if ($int_in_type == 5)
		$str_info_type = 'RECEIVED';
	else if ($int_in_type == 6)
		$str_info_type = 'CORRECTED';
	else if ($int_in_type == 7)
		$str_info_type = 'CANCELLED';

	$int_type = 0;
	if (IsSet($_GET['category_type']))
	    $int_type = $_GET['category_type'];
	    
	$int_category_id = 0;
	if (IsSet($_GET['category_id'])) {
		$int_category_id = $_GET['category_id'];
		$qry_category = new Query("SELECT category_description FROM stock_category WHERE category_id=".$int_category_id);
		$str_category = $qry_category->FieldByName('category_description');
	}
        
	$str_order = 'product_code';
	if (IsSet($_GET['order']))
	    $str_order = $_GET['order'];
	    
	if ($str_order == 'product_code')
		if ($code_sorting == 'ALPHA_NUM')
			$str_order .= "+0 ASC";

	$str_info = "%cStock received on ".$int_day." ".getMonthName($_SESSION['int_month_loaded'])." ".$_SESSION['int_year_loaded']."\nType: ".$str_info_type." ".$str_storeroom_info;
	
	if ($int_type == 'ALL') {
		if ($int_category_id == 'ALL') {
			$str_query = "
				SELECT sp.product_code, sp.product_description, st.transfer_type, sp.tax_id, st.transfer_quantity AS transfer_quantity, st.transfer_description, smu.measurement_unit, sb.selling_price
				FROM ".Monthalize('stock_transfer')." st
				LEFT JOIN stock_product sp ON (sp.product_id = st.product_id)
				LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				LEFT JOIN ".Yearalize('stock_batch')." sb ON (sb.batch_id = st.batch_id)
				WHERE (DAY(st.date_created) = $int_day)
					AND (sp.deleted = 'N')
					$str_transfer_type_filter
				ORDER BY ".$str_order;
		}
		else {
				$str_query = "
					SELECT sp.product_code, sp.product_description, st.transfer_type, sp.tax_id, st.transfer_quantity AS transfer_quantity, st.transfer_description, smu.measurement_unit, sb.selling_price
					FROM ".Monthalize('stock_transfer')." st
					LEFT JOIN stock_product sp ON (sp.product_id = st.product_id)
					LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
					LEFT JOIN ".Yearalize('stock_batch')." sb ON (sb.batch_id = st.batch_id)
					WHERE (DAY(st.date_created) = $int_day)
						AND (sp.deleted = 'N')
						$str_transfer_type_filter
						AND (sp.category_id = $int_category_id)
					ORDER BY ".$str_order;
		}
	}
	else if ($int_type == '1') {
		if ($int_category_id == 'ALL') {
			$str_query = "
				SELECT sp.product_code, sp.product_description, st.transfer_type, sp.tax_id, st.transfer_quantity AS transfer_quantity, st.transfer_description, smu.measurement_unit, sb.selling_price
				FROM ".Monthalize('stock_transfer')." st
				LEFT JOIN stock_product sp ON (sp.product_id = st.product_id)
				LEFT JOIN stock_category sc ON (sc.is_perishable = 'Y')
				LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				LEFT JOIN ".Yearalize('stock_batch')." sb ON (sb.batch_id = st.batch_id)
				WHERE (DAY(st.date_created) = $int_day)
					AND (sp.deleted = 'N')
					$str_transfer_type_filter
					AND (sp.category_id = sc.category_id)
				ORDER BY ".$str_order;
		}
		else {
			$str_query = "
				SELECT sp.product_code, sp.product_description, st.transfer_type, sp.tax_id, st.transfer_quantity AS transfer_quantity, st.transfer_description, smu.measurement_unit, sb.selling_price
				FROM ".Monthalize('stock_transfer')." st
				LEFT JOIN stock_product sp ON (sp.product_id = st.product_id)
				LEFT JOIN stock_category sc ON (sc.is_perishable = 'Y')
				LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				LEFT JOIN ".Yearalize('stock_batch')." sb ON (sb.batch_id = st.batch_id)
				WHERE (DAY(st.date_created) = $int_day)
					AND (sp.deleted = 'N')
					$str_transfer_type_filter
					AND (sp.category_id = sc.category_id)
					AND (sp.category_id = $int_category_id)
				ORDER BY ".$str_order;
		}
	}
	else if ($int_type == '2') {
		if ($int_category_id == 'ALL')
			$str_query = "
				SELECT sp.product_code, sp.product_description, st.transfer_type, sp.tax_id, st.transfer_quantity AS transfer_quantity, st.transfer_description, smu.measurement_unit, sb.selling_price
				FROM ".Monthalize('stock_transfer')." st
				LEFT JOIN stock_product sp ON (sp.product_id = st.product_id)
				LEFT JOIN stock_category sc ON (sc.is_perishable = 'N')
				LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				LEFT JOIN ".Yearalize('stock_batch')." sb ON (sb.batch_id = st.batch_id)
				WHERE (DAY(st.date_created) = $int_day)
					AND (sp.deleted = 'N')
					$str_transfer_type_filter
					AND (sp.category_id = sc.category_id)
				ORDER BY ".$str_order;
		else
			$str_query = "
				SELECT sp.product_code, sp.product_description, st.transfer_type, sp.tax_id, st.transfer_quantity AS transfer_quantity, st.transfer_description, smu.measurement_unit, sb.selling_price
				FROM ".Monthalize('stock_transfer')." st
				LEFT JOIN stock_product sp ON (sp.product_id = st.product_id)
				LEFT JOIN stock_category sc ON (sc.is_perishable = 'N')
				LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				LEFT JOIN ".Yearalize('stock_batch')." sb ON (sb.batch_id = st.batch_id)
				WHERE (DAY(st.date_created) = $int_day)
					AND (sp.deleted = 'N')
					$str_transfer_type_filter
					AND (sp.category_id = sc.category_id)
					AND (sp.category_id = $int_category_id)
				ORDER BY ".$str_order;
	}

    $qry = new Query($str_query);
  
?>

<html>
<head><TITLE>Printing Statement</TITLE>
<link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>

<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0">

<?

	function get_price($qry) {
		$tax_amount = calculateTax($qry->FieldByName('selling_price'), $qry->FieldByName('tax_id'));
		$flt_price = number_format($qry->FieldByName('selling_price') + $tax_amount, 2, '.', '');

		return $flt_price;
	}
	
	function get_description($qry) {
		if ($qry->FieldByName('transfer_type') == TYPE_INTERNAL)
			return substr($qry->FieldByName('transfer_description'), 10, strlen($qry->FieldByName('transfer_description')));
		else if ($qry->FieldByName('transfer_type') == TYPE_RECEIVED)
			if ($qry->FieldByName('module_id') == 1)
				return "DR ".substr($qry->FieldByName('transfer_description'), 14, strlen($qry->FieldByName('transfer_description')));
			else
				return "PO ".substr($qry->FieldByName('transfer_description'), 14, strlen($qry->FieldByName('transfer_description')));
		else if ($qry->FieldByName('transfer_type') == TYPE_CORRECTED)
			return substr($qry->FieldByName('transfer_description'), 12, strlen($qry->FieldByName('transfer_description')));
		else if ($qry->FieldByName('transfer_type') == TYPE_CANCELLED)
			return "BN: ".substr($qry->FieldByName('transfer_description'), 27, strlen($qry->FieldByName('transfer_description')));
	}
	
	function get_type($qry) {
		if ($qry->FieldByName('transfer_type') == TYPE_INTERNAL)
			return 'IN';
		else if ($qry->FieldByName('transfer_type') == TYPE_RETURNED)
			return 'RT';
		else if ($qry->FieldByName('transfer_type') == TYPE_RECEIVED)
			return 'RC';
		else if ($qry->FieldByName('transfer_type') == TYPE_CORRECTED)
			return 'CR';
		else if ($qry->FieldByName('transfer_type') == TYPE_CANCELLED)
			return 'CC';
	}
	
//$str_info .= "%n";

    $print = new print_page;
    $print->query = $qry;
    $print->arr_columns = array(
		0 => array('product_code', 'Code', 6, 'right', 'string'),
		1 => array('product_description', 'Description', 25, 'left', 'string'),
		2 => array('transfer_quantity', 'Stock', 10, 'right', 'number'),
		3 => array('measurement_unit', '', 4, 'left', 'string'),
		4 => array('selling_price', 'Price/Tax', 10, 'right', 'custom', 'get_price'),
		5 => array('transfer_type', '', 3, 'left', 'custom', 'get_type')
//	4 => array('transfer_description', '', 30, 'left', 'custom', 'get_description')
    );
    $print->int_space_between = 1;
    $print->int_total_lines = 65;
    $print->int_total_columns = 2;
    $print->int_page_width = 120;
    $print->int_linecounter_start = 10;
    
    $str_header = $print->get_header();
    $str_data = $print->get_data();

$str_eject_lines = "";
for ($i=0;$i<$int_eject_lines;$i++) {
  $str_eject_lines .= "\n"; 
}

$str_statement = "
".$str_application_title."
".$str_application_title2."
".$str_print_address."
".$str_print_phone."

".$str_info."
".$str_header.$str_data.$str_eject_lines;

$str_statement = replaceSpecialCharacters($str_statement);

?>

<PRE>
<?
 echo $str_statement;
?>
</PRE>


<form name="printerForm" method="POST" action="http://localhost/print.php">


<table width="100%" bgcolor="#E0E0E0">
  <tr>
    <td height=45 class="headerText" bgcolor="#808080">
      &nbsp;<font class='title'>Printing Statement</font>
    </td>
  </tr>
  <tr>
    <td>
      <br>
      <input type="hidden" name="data" value="<? echo ($str_statement); ?>"><br>

	  <input type="hidden" name="os" value="<? echo $os;?>"><br>
	  <input type="hidden" name="print_name" value="<? echo $print_name?>"><br>
	  <input type="hidden" name="print_mode" value="<? echo $print_mode?>"><br>

    </td>
  </tr>
  <tr>
    <td class='normaltext'>
      <textarea name='printerStatus' height=5 rows=5 cols=40 class='editbox'></textarea>
    </td>
  </tr>
  <tr>
    <td align='center'>
      <br><input type='submit' name='doaction' value="Print">
      <input type='button' onclick="window.close();" name='doaction' value="Close">
    </td>
  </tr>
</table>

</form>


<script language="JavaScript">
	printerForm.submit();
</script>


</body>
</html>
