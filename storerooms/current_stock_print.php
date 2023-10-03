<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../include/browser_detection.php");
	require_once("../common/printer.inc.php");
	require_once("../common/print_funcs.inc.php");
	require_once("../common/tax.php");

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

	$str_print_range = 'ALL';
	if (IsSet($_GET['print_range']))
		$str_print_range = $_GET['print_range'];
	
	$int_range_from = 1;
	if (IsSet($_GET['range_from']))
		$int_range_from = $_GET['range_from'];
	
	$int_range_to = 1;
	if (IsSet($_GET['range_to']))
		$int_range_to = $_GET['range_to'];

	$int_type = 0;
	if (IsSet($_GET['category_type']))
	    $int_type = $_GET['category_type'];
	
	$str_print_category = 'N';
	if (IsSet($_GET['print_category']))
		$str_print_category = $_GET['print_category'];
	
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

	$str_show = 'ALL';
	if (IsSet($_GET['show']))
	    $str_show = $_GET['show'];
// echo "::".$str_show;    
	$str_global_stock = 'Y';
	if (IsSet($_GET['global_stock']))
	    $str_global_stock = $_GET['global_stock'];
	    
	if ($str_global_stock == 'Y') {
		$str_select_global = 'SUM(ssp.stock_current) AS current_stock ';
		$str_group_clause = 'GROUP BY sp.product_id ';
		if ($str_show == 'ZERO')
		    $str_group_clause .= 'HAVING (SUM(ssp.stock_current) <= 0)';
		elseif ($str_show == 'NONZERO')
		    $str_group_clause .= 'HAVING (SUM(ssp.stock_current) > 0)';
		$str_storeroom_info = "All storerooms";
	}
	else {
		$str_select_global = 'ssp.stock_current AS current_stock ';
		if (($int_type == 'ALL') && ($int_category_id == 'ALL')) {
			if ($str_show == 'ALL')
			    $str_group_clause = "WHERE (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")";
			    
			elseif ($str_show == 'ZERO')
			    $str_group_clause = "WHERE (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")
				AND (ssp.stock_current <= 0)";
				
			elseif ($str_show == 'NONZERO')
			    $str_group_clause = "WHERE (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")
				AND (ssp.stock_current > 0)";
		}
		else {
			if ($str_show == 'ALL')
			    $str_group_clause = "AND (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")";
			    
			elseif ($str_show == 'ZERO')
			    $str_group_clause = "AND (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")
				AND (ssp.stock_current <= 0)";
				
			elseif ($str_show == 'NONZERO')
			    $str_group_clause = "AND (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")
				AND (ssp.stock_current > 0)";
		}
		
		$qry_storeroom = new Query("
			SELECT *
			FROM stock_storeroom
			WHERE storeroom_id = ".$_SESSION['int_current_storeroom']."
		");
		$str_storeroom_name = $qry_storeroom->FieldByName('description');
		$str_storeroom_info = "Storeroom: ".$str_storeroom_name;
	}


	$str_info = "%b%wCurrent stock as on ".date('d', time())." ".getMonthName($_SESSION['int_month_loaded'])." ".$_SESSION['int_year_loaded']."\n";

	if ($int_type == 'ALL') {
		if ($int_category_id == 'ALL') {
			$str_info .= "Type: All, Category: All";
			$str_query = "
				SELECT sp.product_id, sp.product_code, sp.product_description, sp.mrp, ".$str_select_global.", smu.measurement_unit, st.tax_id,
					ssp.use_batch_price, ssp.sale_price, ssp.stock_minimum,
					sc.category_description
				FROM ".Monthalize('stock_storeroom_product')." ssp
				INNER JOIN stock_product sp ON (sp.product_id = ssp.product_id)
				INNER JOIN stock_category sc ON (sc.category_id = sp.category_id)
				INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				INNER JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = sp.tax_id)
				".$str_group_clause."
				ORDER BY ".$str_order;
		}
		else {
			$str_info .= "Type: All, Category: ".$str_category;
			$str_query = "
				SELECT sp.product_id, sp.product_code, sp.product_description, sp.mrp, ".$str_select_global.", smu.measurement_unit, st.tax_id,
					ssp.use_batch_price, ssp.sale_price, ssp.stock_minimum,
					sc.category_description
				FROM stock_category sc
				INNER JOIN stock_product sp ON (sp.category_id = sc.category_id)
				INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				INNER JOIN ".Monthalize('stock_storeroom_product')." ssp ON (sp.product_id = ssp.product_id)
				INNER JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = sp.tax_id)
				WHERE (sc.category_id = $int_category_id)
				".$str_group_clause."
				ORDER BY ".$str_order;
		}
	}
	else if ($int_type == '1') {
		if ($int_category_id == 'ALL')  {
			$str_info .= "Type: Perishable, Category: All";
			$str_query = "
				SELECT sp.product_id, sp.product_code, sp.product_description, sp.mrp, ".$str_select_global.", smu.measurement_unit, st.tax_id,
					ssp.use_batch_price, ssp.sale_price, ssp.stock_minimum,
					sc.category_description
				FROM stock_category sc
				INNER JOIN stock_product sp ON (sp.category_id = sc.category_id)
				INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				INNER JOIN ".Monthalize('stock_storeroom_product')." ssp ON (sp.product_id = ssp.product_id)
				INNER JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = sp.tax_id)
				WHERE (sc.is_perishable = 'Y')
				".$str_group_clause."
				ORDER BY ".$str_order;
		}
		else {
			$str_info .= "Type: Perishable, Category: ".$str_category;
			$str_query = "
				SELECT sp.product_id, sp.product_code, sp.product_description, sp.mrp, ".$str_select_global.", smu.measurement_unit, st.tax_id,
					ssp.use_batch_price, ssp.sale_price, ssp.stock_minimum,
					sc.category_description
				FROM stock_category sc
				INNER JOIN stock_product sp ON (sp.category_id = sc.category_id)
				INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				INNER JOIN ".Monthalize('stock_storeroom_product')." ssp ON (sp.product_id = ssp.product_id)
				INNER JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = sp.tax_id)
				WHERE (sc.category_id = $int_category_id) AND (sp.is_perishable = 'Y')
				".$str_group_clause."
				ORDER BY ".$str_order;
		}
	}
	else if ($int_type == '2') {
		if ($int_category_id == 'ALL') {
			$str_info .= "Type: Non-Perishable, Category: All";
			$str_query = "
				SELECT sp.product_id, sp.product_code, sp.product_description, sp.mrp, ".$str_select_global.", smu.measurement_unit, st.tax_id,
					ssp.use_batch_price, ssp.sale_price, ssp.stock_minimum,
					sc.category_description
				FROM stock_category sc
				INNER JOIN stock_product sp ON (sp.category_id = sc.category_id)
				INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				INNER JOIN ".Monthalize('stock_storeroom_product')." ssp ON (sp.product_id = ssp.product_id)
				INNER JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = sp.tax_id)
				WHERE (sc.is_perishable = 'N')
				".$str_group_clause."
				ORDER BY ".$str_order;
		}
		else {
			$str_info .= "Type: Non-Perishable, Category: ".$str_category;
			$str_query = "
				SELECT sp.product_id, sp.product_code, sp.product_description, sp.mrp, ".$str_select_global.", smu.measurement_unit, st.tax_id,
					ssp.use_batch_price, ssp.sale_price, ssp.stock_minimum,
					sc.category_description
				FROM stock_category sc
				INNER JOIN stock_product sp ON (sp.category_id = sc.category_id)
				INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				INNER JOIN ".Monthalize('stock_storeroom_product')." ssp ON (sp.product_id = ssp.product_id)
				INNER JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = sp.tax_id)
				WHERE (sc.category_id = $int_category_id) AND (sp.is_perishable = 'N')
				".$str_group_clause."
				ORDER BY ".$str_order;
		}
    }
//echo $str_query;
	$qry = new Query($str_query);
?>

<html>
<head><TITLE>Printing Statement</TITLE>
<link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>

<? if (browser_detection( 'os' ) === 'lin') { ?>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0">
<? } else { ?>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0" onload="CheckTC()">
<? } ?>

<?
	function get_price($f_qry) {
		$flt_selling_price = 0;
		
		if ($f_qry->FieldByName('use_batch_price') == 'Y') {
		    $qry_batch = new Query("
			    SELECT sb.batch_id, sb.batch_code, sb.buying_price, sb.selling_price, sb.tax_id, ssb.stock_available, ssb.is_active
			    FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb
			    WHERE (sb.product_id = ".$f_qry->FieldByName('product_id').") AND
				    (sb.storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
				    (sb.status = ".STATUS_COMPLETED.") AND
				    (sb.deleted = 'N') AND
				    (ssb.product_id = sb.product_id) AND
				    (ssb.batch_id = sb.batch_id) AND
				    (ssb.storeroom_id = sb.storeroom_id) AND 
				    (ssb.is_active = 'Y')
			    ORDER BY date_created
		    ");
		    if ($qry_batch->RowCount() > 0)
			$flt_selling_price = number_format($qry_batch->FieldByName('selling_price'), 2, '.', '');
		}
		else {
		    $flt_selling_price = number_format($f_qry->FieldByName('sale_price'), 2, '.', '');
		}
		
		$tax_amount = calculateTax($flt_selling_price, $f_qry->FieldByName('tax_id'));
		$flt_price_total = number_format(RoundUp($flt_selling_price + $tax_amount),2,'.','');
		
		if (IsSet($qry_batch))
			$qry_batch->Free();
		
		return $flt_price_total;
	}

	$print = new print_page;
	$print->query = $qry;
	if ($str_print_category == 'Y')
		$print->arr_columns = array(
			0 => array('product_code', 'Code', 6, 'right', 'string'),
			1 => array('product_description', 'Description', 20, 'left', 'string'),
			2 => array('category_description', 'Category', 15, 'left', 'string'),
			3 => array('stock_minimum', 'Minimum', 8, 'right', 'number'),
			4 => array('current_stock', 'Stock', 10, 'right', 'number'),
			5 => array('measurement_unit', '', 4, 'left', 'string'),
			6 => array('', 'S.P./Tax', 8, 'right', 'custom', 'get_price'),
			7 => array('mrp', 'M.R.P.', 8, 'right', 'number')
		);
	else
		$print->arr_columns = array(
			0 => array('product_code', 'Code', 6, 'right', 'string'),
			1 => array('product_description', 'Description', 20, 'left', 'string'),
			2 => array('stock_minimum', 'Minimum', 8, 'right', 'number'),
			3 => array('current_stock', 'Stock', 10, 'right', 'number'),
			4 => array('measurement_unit', '', 4, 'left', 'string'),
			5 => array('', 'S.P./Tax', 8, 'right', 'custom', 'get_price'),
			6 => array('mrp', 'M.R.P.', 8, 'right', 'number')
		);
	
	if ($str_print_range == 'ALL')
		$print->str_print_all = 'Y';
	else
		$print->str_print_all = 'N';
	$print->int_page_from = $int_range_from;
	$print->int_page_to = $int_range_to;
	$print->int_space_between = 1;
	$print->int_total_lines = 62;
	$print->int_total_columns = 1;
	$print->int_linecounter_start = 10;
	if (($str_print_range == 'RANGE') && ($int_range_from > 1))
		$print->int_linecounter_start = 1;
	
    
    $str_header = $print->get_header();
    $str_data = $print->get_data();

	$str_eject_lines = "";
	for ($i=0;$i<$int_eject_lines;$i++) {
	  $str_eject_lines .= "\n"; 
	}

if (($str_print_range == 'RANGE') && ($int_range_from > 1))
$str_statement = $str_data;
else
$str_statement = "
".$str_application_title."
".$str_application_title2."
".$str_print_address."
".$str_print_phone."

".$str_info."
".$str_storeroom_info."%n
".$str_header.$str_data;

$str_statement = replaceSpecialCharacters($str_statement);
?>

<PRE>
<?
 echo $str_statement;
?>
</PRE>


<? if (browser_detection("os") === "lin") { ?>
<form name="printerForm" method="POST" action="http://localhost/print.php">
<? } else { ?>
<form name="printerForm" onsubmit="return false;">
<? } ?>


<table width="100%" bgcolor="#E0E0E0">
  <tr>
    <td height=45 class="headerText" bgcolor="#808080">
      &nbsp;<font class='title'>Printing Statement</font>
    </td>
  </tr>
  <tr>
    <td>
      <br>
      <? if (browser_detection("os") === "lin") { ?>
      <input type="hidden" name="data" value="<? echo ($str_statement); ?>"><br>
      <? } else { ?>
      <input type="hidden" name="output" value="<? echo htmlentities($str_statement); ?>">
      <? } ?>
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

<? if (browser_detection( 'os' ) === 'lin') { ?>

<script language="JavaScript">
	printerForm.submit();
</script>

<? } else { ?>

<script language="JavaScript">
	writedata();
</script>

<? } ?>

</body>
</html>
