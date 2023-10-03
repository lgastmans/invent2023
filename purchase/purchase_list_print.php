<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../include/browser_detection.php");
	require_once("../common/printer.inc.php");
	require_once("../common/print_funcs.inc.php");

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

	if (IsSet($_GET['id_list'])) {
		$arr_temp = explode('^', $_GET['id_list']);
		$arr_products = array();
		for ($i=0;$i<count($arr_temp);$i++){
			$arr_products[$i] = explode('>', $arr_temp[$i]);
			$arr_products[$i][0] = substr($arr_products[$i][0], 3, strlen($arr_products[$i][0]));
		}
	}
	
	function get_array_pos($int_product_id) {
		$ret_val = -1;
		global $arr_products;
		for ($i=0;$i<count($arr_products);$i++) {
			if ($arr_products[$i][0] == $int_product_id) {
				$ret_val = $i;
				break;
			}
		}
		return $ret_val;
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

    if (IsSet($_GET['city'])) {
        $str_city = $_GET['city'];
    }
        
    $int_supplier_id = 0;
    if (IsSet($_GET['supplier_id']))
        $int_supplier_id = $_GET['supplier_id'];

    $int_num_days = 1;
    if (IsSet($_GET['days'])) {
        $int_num_days = $_GET['days'];
    }

	/*
		calculate the total current stock across storerooms
		according to user selection
	*/
	$str_stock_total = 'CURRENT';
	if (IsSet($_GET['stock_total']))
		$str_stock_total = $_GET['stock_total'];
	
	if ($str_stock_total == 'ALL') {
		$str_select_stock = "SUM(ssp.stock_current) AS stock_current ";
		$str_join_stock = "INNER JOIN ".Monthalize('stock_storeroom_product')." ssp ON (ssp.product_id = sp.product_id)";
		$str_group_stock = "GROUP BY sp.product_id";
	}
	else {
		$str_select_stock = "ssp.stock_current";
		$str_join_stock = "INNER JOIN ".Monthalize('stock_storeroom_product')." ssp ON (ssp.product_id = sp.product_id)
				AND (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")";
		$str_group_stock = "";
	}
    
    $int_method = '';
    if (IsSet($_GET['method']))
        $int_method = $_GET['method'];
        
    $str_order_field = 'product_code';
    if (IsSet($_GET['order']))
	$str_order_field = $_GET['order'];
	
    $int_sold_storeroom = $_SESSION['int_current_storeroom'];
    if (IsSet($_GET['storeroom_id']))
        $int_sold_storeroom = $_GET['storeroom_id'];

	if ($str_order_field == 'product_code')
		if ($code_sorting == 'ALPHA_NUM')
			$str_order_field = $str_order_field."+0 ASC";

	$qry_supplier = new Query("SELECT supplier_name FROM stock_supplier WHERE supplier_id = $int_supplier_id");
	
    $str_filter = '';
    $str_order = '';
    if ($int_supplier_id == 'ALL') {
        $str_filter = "AND (ss.supplier_city = '".$str_city."')";
        $str_order = "ORDER BY ss.supplier_name, ".$str_order_field;
	$str_info = "Supplier purchase list for ".$str_city;
    }
    else {
        $str_filter = "AND (ss.supplier_id = ".$int_supplier_id.")";
        $str_order = "ORDER BY ".$str_order_field;
	$str_info = "Supplier purchase list for ".$qry_supplier->FieldByName('supplier_name');
    }

	$str_filter .= " AND ( ";
	for ($i=0;$i<count($arr_products);$i++) {
		$str_filter .= "(sp.product_id = ".$arr_products[$i][0].") OR ";
	}
	$str_filter = substr($str_filter, 0, strlen($str_filter)-3);
	$str_filter .= ")";
	
	if ($int_method == PO_PREDICT_PREVIOUS)
		$str_info .= "\nForecast for next ".$int_num_days." day(s) based on previous month";
	else if ($int_method == PO_PREDICT_PREVIOUS_CURRENT)
		$str_info .= "\nForecast for next ".$int_num_days." day(s) based on previous and current month";
	else
		$str_info .= "\nForecast for next ".$int_num_days." day(s) based on current month";

	$qry_storeroom = new Query("
		SELECT *
		FROM stock_storeroom
		WHERE storeroom_id = ".$_SESSION['int_current_storeroom']."
	");
	$str_storeroom_name = $qry_storeroom->FieldByName('description');
	$str_info .= "\nStoreroom: ".$str_storeroom_name;

	$qry_storeroom = new Query("
		SELECT *
		FROM stock_storeroom
		WHERE storeroom_id = ".$int_sold_storeroom."
	");
	$str_storeroom_name = $qry_storeroom->FieldByName('description');
	$str_info .= ", Sales storeroom: ".$str_storeroom_name;

    function getSPrice($flt_price, $int_margin) {
            if ($int_margin > 0)
                    return RoundUp($flt_price * (1 + ($int_margin/100)));
            else
                    return $flt_price;
    }

    $arr_prev_month=getPreviousMonth();

    $str_query = "SELECT
        sp.product_id,
        sp.product_code,
        sp.product_description,
        sp.margin_percent,
        sp.quantity_per_box,
        sp.is_perishable,
        sp.list_in_purchase,
        sp.is_av_product,
        ssp.stock_minimum,
        ssp.stock_ordered,
        $str_select_stock,
        sb.buying_price,
        sb.selling_price,
        sby.stock_sold,
        p_sby.stock_sold AS prev_stock_sold,
        smu.measurement_unit,
        smu.is_decimal,
        ss.supplier_id,
        ss.supplier_name,
        sc.category_description
    FROM
        stock_product sp
        LEFT JOIN ".Yearalize('stock_batch')." sb ON ((sb.batch_id=(SELECT MAX(batch_id) FROM ".Yearalize('stock_batch')." sb2 WHERE (sb2.product_id=sp.product_id) LIMIT 1)))
        $str_join_stock
        LEFT JOIN ".Yearalize('stock_balance')." p_sby ON (p_sby.product_id=sp.product_id) AND (p_sby.storeroom_id=".$int_sold_storeroom.") AND (p_sby.balance_month=".$arr_prev_month[1].")
        LEFT JOIN ".Yearalize('stock_balance')." sby ON (sby.product_id=sp.product_id) AND (sby.storeroom_id=".$int_sold_storeroom.") AND (sby.balance_month=".$_SESSION["int_month_loaded"].")
        LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id=sp.measurement_unit_id)
        LEFT JOIN stock_supplier ss ON (ss.supplier_id=sp.supplier_id)
        LEFT JOIN stock_category sc ON (sc.category_id = sp.category_id)
    WHERE (sp.list_in_purchase = 'Y')
	AND (sp.deleted = 'N')
        $str_filter
        $str_group_stock
        $str_order";
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
//$str_info .= "%n";
/*
    $print = new print_page;
    $print->query = $qry;
    
	if ($int_method == PO_PREDICT_PREVIOUS) 
		$print->arr_columns = array(
		    0 => array('product_code', 'Code', 6, 'right', 'string'),
		    1 => array('product_description', 'Description', 25, 'left', 'string'),
		    2 => array('measurement_unit', '', 4, 'left', 'string'),
		    3 => array('buying_price', 'B. Price', 8, 'right', 'number'),
		    4 => array('selling_price', 'S. Price', 8, 'right', 'number'),
		    5 => array('stock_minimum', 'Minimum', 8, 'right', 'number'),
		    6 => array('stock_current', 'Stock', 8, 'right', 'number'),
		    7 => array('prev_stock_sold', 'Sold L.M.', 8, 'right', 'number'),
		    8 => array('to_buy', 'To Buy', 8, 'right', 'number'),
		    9 => array('', 'Bought', 8, 'left', 'dotted')
		);
	else if ($int_method == PO_PREDICT_PREVIOUS_CURRENT)
		$print->arr_columns = array(
		    0 => array('product_code', 'Code', 6, 'right', 'string'),
		    1 => array('product_description', 'Description', 25, 'left', 'string'),
		    2 => array('measurement_unit', '', 4, 'left', 'string'),
		    3 => array('buying_price', 'B. Price', 8, 'right', 'number'),
		    4 => array('selling_price', 'S. Price', 8, 'right', 'number'),
		    5 => array('stock_minimum', 'Minimum', 8, 'right', 'number'),
		    6 => array('stock_current', 'Stock', 8, 'right', 'number'),
		    7 => array('prev_stock_sold', 'Sold L.M.', 8, 'right', 'number'),
		    8 => array('stock_sold', 'Sold', 8, 'right', 'number'),
		    9 => array('to_buy', 'To Buy', 8, 'right', 'number'),
		    10 => array('', 'Bought', 8, 'left', 'dotted')
		);
	else
		$print->arr_columns = array(
		    0 => array('product_code', 'Code', 6, 'right', 'string'),
		    1 => array('product_description', 'Description', 25, 'left', 'string'),
		    2 => array('measurement_unit', '', 4, 'left', 'string'),
		    3 => array('buying_price', 'B. Price', 8, 'right', 'number'),
		    4 => array('selling_price', 'S. Price', 8, 'right', 'number'),
		    5 => array('stock_minimum', 'Minimum', 8, 'right', 'number'),
		    6 => array('stock_current', 'Stock', 8, 'right', 'number'),
		    7 => array('stock_sold', 'Sold', 8, 'right', 'number'),
		    8 => array('to_buy', 'To Buy', 8, 'right', 'number'),
		    9 => array('', 'Bought', 8, 'left', 'dotted')
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
	$print->int_page_width = 120;
	$print->int_linecounter_start = 11;
	if (($str_print_range == 'RANGE') && ($int_range_from > 1))
		$print->int_linecounter_start = 1;
    
    $str_header = $print->get_header();
    $str_data = $print->get_data_sorted('supplier_name');
*/
$str_double = '';
$str_double = StuffWithCharacter($str_double, '=', 100);

$str_single = '';
$str_single = StuffWithCharacter($str_single, '-', 100);

/*
$str_header = $str_double."\n".
	StuffWithCharacter('Code', ' ', 6)." ".
	PadWithCharacter('Description', ' ', 25)." ".
	StuffWithCharacter('B. Price', ' ', 8)." ".
	StuffWithCharacter('S. Price', ' ', 8)." ".
	StuffWithCharacter('Minimum', ' ', 8)." ".
	StuffWithCharacter('Stock', ' ', 8)." ".
	StuffWithCharacter('Sold', ' ', 8)." ".
	StuffWithCharacter('To Buy', ' ', 8)." ".
	StuffWithCharacter('', ' ', 4)." ".
	StuffWithCharacter('Bought', ' ', 8)."\n".
	$str_single."\n";
*/


if ($int_method == PO_PREDICT_PREVIOUS)
	$str_header = $str_double."\n".
		StuffWithCharacter('Code', ' ', 6)." ".
		PadWithCharacter('Description', ' ', 25)." ".
		StuffWithCharacter('Stock', ' ', 8)." ".
		StuffWithCharacter('Sold LM', ' ', 8)." ".
		StuffWithCharacter('To Buy', ' ', 8)." ".
		StuffWithCharacter('', ' ', 4)." ".
		StuffWithCharacter('Bought', ' ', 8)."\n".
		$str_single."\n";
elseif ($int_method == PO_PREDICT_PREVIOUS_CURRENT)
	$str_header = $str_double."\n".
		StuffWithCharacter('Code', ' ', 6)." ".
		PadWithCharacter('Description', ' ', 25)." ".
		StuffWithCharacter('Stock', ' ', 8)." ".
		StuffWithCharacter('Sold LM', ' ', 8)." ".
		StuffWithCharacter('Sold TM', ' ', 8)." ".
		StuffWithCharacter('To Buy', ' ', 8)." ".
		StuffWithCharacter('', ' ', 4)." ".
		StuffWithCharacter('Bought', ' ', 8)."\n".
		$str_single."\n";
elseif ($int_method == PO_PREDICT_CURRENT)
	$str_header = $str_double."\n".
		StuffWithCharacter('Code', ' ', 6)." ".
		PadWithCharacter('Description', ' ', 25)." ".
		StuffWithCharacter('Stock', ' ', 8)." ".
		StuffWithCharacter('Sold', ' ', 8)." ".
		StuffWithCharacter('To Buy', ' ', 8)." ".
		StuffWithCharacter('', ' ', 4)." ".
		StuffWithCharacter('Bought', ' ', 8)."\n".
		$str_single."\n";

$str_data = '';
$cur_supplier_id = 0;
for ($i=0;$i<$qry->RowCount();$i++) {
	$flt_quantity = 0;
	$int_pos = get_array_pos($qry->FieldByName('product_id'));
	if ($int_pos > -1)
		$flt_quantity = $arr_products[$int_pos][1];
		
	if ($cur_supplier_id != $qry->FieldByName('supplier_id'))
		$str_data .= $qry->FieldByName('supplier_name')."\n";
		
	/*
	$str_data .=
		StuffWithCharacter($qry->FieldByName('product_code'), ' ', 6)." ".
		PadWithCharacter($qry->FieldByName('product_description'), ' ', 25)." ".
		StuffWithCharacter($qry->FieldByName('buying_price'), ' ', 8)." ".
		StuffWithCharacter($qry->FieldByName('selling_price'), ' ', 8)." ".
		StuffWithCharacter($qry->FieldByName('stock_minimum'), ' ', 8)." ".
		StuffWithCharacter($qry->FieldByName('stock_current'), ' ', 8)." ".
		StuffWithCharacter($qry->FieldByName('stock_sold'), ' ', 8)." ".
		StuffWithCharacter($flt_quantity, ' ', 8)." ".
		StuffWithCharacter($qry->FieldByName('measurement_unit'), ' ', 4)." ".
		StuffWithCharacter('........', ' ', 8)."\n";
	*/
	if ($int_method == PO_PREDICT_PREVIOUS)
		$str_data .=
			StuffWithCharacter($qry->FieldByName('product_code'), ' ', 6)." ".
			PadWithCharacter($qry->FieldByName('product_description'), ' ', 25)." ".
			StuffWithCharacter($qry->FieldByName('stock_current'), ' ', 8)." ".
			StuffWithCharacter($qry->FieldByName('prev_stock_sold'), ' ', 8)." ".
			StuffWithCharacter($flt_quantity, ' ', 8)." ".
			StuffWithCharacter($qry->FieldByName('measurement_unit'), ' ', 4)." ".
			StuffWithCharacter('........', ' ', 8)."\n";
	elseif ($int_method == PO_PREDICT_PREVIOUS_CURRENT)
		$str_data .=
			StuffWithCharacter($qry->FieldByName('product_code'), ' ', 6)." ".
			PadWithCharacter($qry->FieldByName('product_description'), ' ', 25)." ".
			StuffWithCharacter($qry->FieldByName('stock_current'), ' ', 8)." ".
			StuffWithCharacter($qry->FieldByName('stock_sold'), ' ', 8)." ".
			StuffWithCharacter($qry->FieldByName('prev_stock_sold'), ' ', 8)." ".
			StuffWithCharacter($flt_quantity, ' ', 8)." ".
			StuffWithCharacter($qry->FieldByName('measurement_unit'), ' ', 4)." ".
			StuffWithCharacter('........', ' ', 8)."\n";
	elseif ($int_method == PO_PREDICT_CURRENT)
		$str_data .=
			StuffWithCharacter($qry->FieldByName('product_code'), ' ', 6)." ".
			PadWithCharacter($qry->FieldByName('product_description'), ' ', 25)." ".
			StuffWithCharacter($qry->FieldByName('stock_current'), ' ', 8)." ".
			StuffWithCharacter($qry->FieldByName('stock_sold'), ' ', 8)." ".
			StuffWithCharacter($flt_quantity, ' ', 8)." ".
			StuffWithCharacter($qry->FieldByName('measurement_unit'), ' ', 4)." ".
			StuffWithCharacter('........', ' ', 8)."\n";
	
	$cur_supplier_id = $qry->FieldByName('supplier_id');
	$qry->Next();
}

$str_todays_date = "\nPrinted on: ".date('j M Y', time());

$str_eject_lines = "";
for ($i=0;$i<$int_eject_lines;$i++) {
  $str_eject_lines .= "\n"; 
}

$str_statement =
"%c".$str_application_title."
".$str_application_title2."
".$str_print_address."
".$str_print_phone."

".$str_info."
".$str_header.$str_data.$str_double.$str_todays_date.$str_eject_lines."%n";

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
      <input type="hidden" name="data" value="<? echo htmlentities($str_statement); ?>"><br>
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
