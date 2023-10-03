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
        
    $int_category_id = 0;
    if (IsSet($_GET['category_id']))
        $int_category_id = $_GET['category_id'];

$str_category = '';
$qry_category = new Query("SELECT category_description FROM stock_category WHERE category_id = ".$int_category_id);
if ($qry_category->RowCount() > 0)
	$str_category = $qry_category->FieldByName('category_description');

    $int_num_days = 1;
    if (IsSet($_GET['days'])) {
        $int_num_days = $_GET['days'];
    }

    $int_method = '';
    if (IsSet($_GET['method']))
        $int_method = $_GET['method'];
        
    $str_order = 'product_code';
    if (IsSet($_GET['order']))
	$str_order = $_GET['order'];
	
    $int_sold_storeroom = $_SESSION['int_current_storeroom'];
    if (IsSet($_GET['storeroom_id']))
        $int_sold_storeroom = $_GET['storeroom_id'];

	if ($str_order == 'product_code')
		if ($code_sorting == 'ALPHA_NUM')
			$str_order = " ORDER BY category_description, ".$str_order."+0 ASC";
		else
			$str_order = " ORDER BY category_description, ".$str_order;

    function getSPrice($flt_price, $int_margin) {
            if ($int_margin > 0)
                    return RoundUp($flt_price * (1 + ($int_margin/100)));
            else
                    return $flt_price;
    }

    $arr_prev_month=getPreviousMonth();

    $str_info = 'Purchase list ';
    
    if ($int_type == 'ALL') {
        if ($int_category_id == 'ALL') {
            $str_category_filter = "";
        }
	else {
		$str_info .= 'category '.$str_category.' ';
            $str_category_filter = " AND (sc.category_id = $int_category_id) ";
	}
    }
    else if ($int_type == '1') {
        if ($int_category_id == 'ALL') {
		$str_info .= 'of perishable products';
            $str_category_filter = " AND (sc.is_perishable = 'Y') ";
	}
	else {
		$str_info .= 'of perishable products, category '.$str_category;
            $str_category_filter = " AND (sc.category_id = $int_category_id) AND (sc.is_perishable = 'Y') ";
	}
    }
    else if ($int_type == '2') {
        if ($int_category_id == 'ALL') {
		$str_info .= 'of non-perishable products ';
            $str_category_filter = " AND (sc.is_perishable = 'N') ";
	}
	else {
		$str_info .= 'of non-perishable products, category '.$str_category;
            $str_category_filter = " AND (sc.category_id = $int_category_id) AND (sc.is_perishable = 'N') ";
	}
    }

	if ($int_method == PO_PREDICT_PREVIOUS)
		$str_info .= "Forecast for next ".$int_num_days." day(s) based on previous month";
	else if ($int_method == PO_PREDICT_PREVIOUS_CURRENT)
		$str_info .= "Forecast for next ".$int_num_days." day(s) based on previous and current month";
	else
		$str_info .= "Forecast for next ".$int_num_days." day(s) based on current month";

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

    if ($int_method == PO_PREDICT_PREVIOUS) {
            $str_value = "
            (
                    @qty_value :=
                            ROUND(
                                    ((SELECT SUM(stock_sold)
                                            FROM ".Yearalize('stock_balance')."
                                            WHERE (balance_month = ".$arr_prev_month[1].") AND
                                                    (balance_year = ".$_SESSION["int_year_loaded"].") AND
                                                    (product_id = sp.product_id) AND
						    (storeroom_id = ".$_SESSION['int_current_storeroom'].")
                                    ) / 26 * ".$int_num_days.") - ssp.stock_ordered - ssp.stock_current
                            , 2) / sp.quantity_per_box
            )";

            $str_prediction_method = "
            (
                    @qty_calc :=
                            IF (smu.is_decimal='Y',
                                    (ROUND(@qty_value, 2) * sp.quantity_per_box),
                                    IF (ROUND(@qty_value) * sp.quantity_per_box > 0,
                                            (ROUND(@qty_value) * sp.quantity_per_box)
                                            , 0
                                    )
                            )
            )";

            $str_rounded = "
                    IF (sp.purchase_round > 0,
                            @qty_calc + (CEILING(@qty_calc/sp.purchase_round) * sp.purchase_round - @qty_calc),
                            @qty_calc
                    )
            ";
    }	
    else
    if ($int_method == PO_PREDICT_PREVIOUS_CURRENT) {

            $str_value = "
            (
                    @qty_value :=
                            ROUND(
                                    ((SELECT SUM(stock_sold)
                                            FROM ".Yearalize('stock_balance')."
                                            WHERE ((balance_month = ".$arr_prev_month[1].") OR (balance_month = ".$_SESSION["int_month_loaded"].")) AND
                                                    (balance_year = ".$_SESSION["int_year_loaded"].") AND
                                                    (product_id = sp.product_id) AND
						    (storeroom_id = ".$_SESSION['int_current_storeroom'].")
                                    ) / 26 * ".$int_num_days.") - ssp.stock_ordered - ssp.stock_current
                            , 2) / sp.quantity_per_box
            )";

            $str_prediction_method = "
            (
                    @qty_calc :=
                            IF (smu.is_decimal='Y',
                                    (ROUND(@qty_value, 2) * sp.quantity_per_box),
                                    IF (ROUND(@qty_value) * sp.quantity_per_box > 0,
                                            (ROUND(@qty_value) * sp.quantity_per_box)
                                            , 0
                                    )
                            )
            )";

            $str_rounded = "
                    IF (sp.purchase_round > 0,
                            @qty_calc + (CEILING(@qty_calc/sp.purchase_round) * sp.purchase_round - @qty_calc),
                            @qty_calc
                    )
            ";
    }
    else
    if ($int_method == PO_PREDICT_CURRENT) {
            $str_value = "
            (
                    @qty_value :=
                            ROUND(
                                    ((SELECT SUM(stock_sold)
                                            FROM ".Yearalize('stock_balance')."
                                            WHERE (balance_month = ".$_SESSION["int_month_loaded"].") AND
                                                    (balance_year = ".$_SESSION["int_year_loaded"].") AND
                                                    (product_id = sp.product_id) AND
						    (storeroom_id = ".$_SESSION['int_current_storeroom'].")
                                    ) / 26 * ".$int_num_days.") - ssp.stock_ordered - ssp.stock_current
                            , 2) / sp.quantity_per_box
            )";

            $str_prediction_method = "
            (
                    @qty_calc :=
                            IF (smu.is_decimal='Y',
                                    (ROUND(@qty_value, 2) * sp.quantity_per_box),
                                    IF (ROUND(@qty_value) * sp.quantity_per_box > 0,
                                            (ROUND(@qty_value) * sp.quantity_per_box)
                                            , 0
                                    )
                            )
            )";

            $str_rounded = "
                    IF (sp.purchase_round > 0,
                            @qty_calc + (CEILING(@qty_calc/sp.purchase_round) * sp.purchase_round - @qty_calc),
                            @qty_calc
                    )
            ";
    }

    if ($int_method == PO_PREDICT_PREVIOUS)
            $str_filter_temp = "ROUND(ROUND(((SELECT SUM(stock_sold) FROM ".Yearalize('stock_balance')." WHERE (balance_month = ".$arr_prev_month[1].") AND (balance_year = ".$_SESSION["int_year_loaded"].") AND (product_id = sp.product_id) AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")) / 26 * ".$int_num_days.") - ssp.stock_ordered - ssp.stock_current, 2) / sp.quantity_per_box) * sp.quantity_per_box";
    else
    if ($int_method == PO_PREDICT_PREVIOUS_CURRENT)
            $str_filter_temp = "ROUND(ROUND(((SELECT SUM(stock_sold) FROM ".Yearalize('stock_balance')." WHERE ((balance_month = ".$arr_prev_month[1].") OR (balance_month = ".$_SESSION["int_month_loaded"].")) AND (balance_year = ".$_SESSION["int_year_loaded"].") AND (product_id = sp.product_id) AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")) / 26 * ".$int_num_days.") - ssp.stock_ordered - ssp.stock_current, 2) / sp.quantity_per_box) * sp.quantity_per_box";
    else
    if ($int_method == PO_PREDICT_CURRENT)
            $str_filter_temp = "(ROUND(ROUND(((SELECT SUM(stock_sold) FROM ".Yearalize('stock_balance')." WHERE (balance_month = ".$_SESSION["int_month_loaded"].") AND (balance_year = ".$_SESSION["int_year_loaded"].") AND (product_id = sp.product_id) AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")) / 26 * ".$int_num_days.") - ssp.stock_ordered - ssp.stock_current, 2) / sp.quantity_per_box) * sp.quantity_per_box)";

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
        ssp.stock_current,
        sb.buying_price,
        sb.selling_price,
        sby.stock_sold,
        p_sby.stock_sold AS prev_stock_sold,
        ".$str_value." AS retrieved_quantity,
        CAST(".$str_prediction_method." AS CHAR) AS calculated_quantity,
        CAST(".$str_rounded." AS CHAR) AS to_buy,
        smu.measurement_unit,
        smu.is_decimal,
        sc.category_description
    FROM
        stock_product sp
        LEFT JOIN ".Yearalize('stock_batch')." sb ON ((sb.batch_id=(SELECT MAX(batch_id) FROM ".Yearalize('stock_batch')." sb2 WHERE (sb2.product_id=sp.product_id) LIMIT 1)))
        INNER JOIN ".Monthalize('stock_storeroom_product')." ssp ON (ssp.product_id=sp.product_id) AND (ssp.storeroom_id=".$_SESSION["int_current_storeroom"].")
        LEFT JOIN ".Yearalize('stock_balance')." p_sby ON (p_sby.product_id=sp.product_id) AND (p_sby.storeroom_id=".$int_sold_storeroom.") AND (p_sby.balance_month=".$arr_prev_month[1].")
        INNER JOIN ".Yearalize('stock_balance')." sby ON (sby.product_id=sp.product_id) AND (sby.storeroom_id=".$int_sold_storeroom.") AND (sby.balance_month=".$_SESSION["int_month_loaded"].")
        INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
        INNER JOIN stock_category sc ON (sc.category_id = sp.category_id)
    WHERE (sp.list_in_purchase = 'Y')
	AND (sp.deleted = 'N')
        $str_category_filter
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

	$print = new print_page;
	$print->query = $qry;

	if ($int_method == PO_PREDICT_PREVIOUS) 
		$print->arr_columns = array(
		    0 => array('product_code', 'Code', 6, 'right', 'string'),
		    1 => array('product_description', 'Description', 25, 'left', 'string'),
		    2 => array('measurement_unit', '', 4, 'left', 'string'),
		    3 => array('buying_price', 'B.Price', 8, 'right', 'number'),
		    4 => array('selling_price', 'S.Price', 8, 'right', 'number'),
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
		    3 => array('buying_price', 'B.Price', 8, 'right', 'number'),
		    4 => array('selling_price', 'S.Price', 8, 'right', 'number'),
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
		    3 => array('buying_price', 'B.Price', 8, 'right', 'number'),
		    4 => array('selling_price', 'S.Price', 8, 'right', 'number'),
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
	$print->int_linecounter_start = 10;
	if (($str_print_range == 'RANGE') && ($int_range_from > 1))
		$print->int_linecounter_start = 1;
    
	$str_header = $print->get_header();
	$str_data = $print->get_data_sorted('category_description');

$str_eject_lines = "";
for ($i=0;$i<$int_eject_lines;$i++) {
  $str_eject_lines .= "\n"; 
}

if (($str_print_range == 'RANGE') && ($int_range_from > 1))
$str_statement = "%c".$str_data."%n";
else
$str_statement = "%c
".$str_application_title."
".$str_application_title2."
".$str_print_address."
".$str_print_phone."

".$str_info."
".$str_header.$str_data."%n";

$str_statement = replaceSpecialCharacters($str_statement);
?>

<PRE>
<?
 echo $str_statement;
?>
</PRE>


<? if (browser_detection("os") === "lin") { ?>
<form name="printerForm" method="POST" action="http://localhost/pourtous/print.php">
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
