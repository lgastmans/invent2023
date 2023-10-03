<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");
    require_once("../include/purchase_funcs.inc.php");

	$code_sorting = $arr_invent_config['settings']['code_sorting'];
    
    $int_type = 0;
    if (IsSet($_GET['category_type']))
        $int_type = $_GET['category_type'];
        
    $int_category_id = 0;
    if (IsSet($_GET['category_id']))
        $int_category_id = $_GET['category_id'];

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
//echo $int_sold_storeroom;

	if ($str_order == 'product_code')
		if ($code_sorting == 'ALPHA_NUM')
			$str_order = " ORDER BY category_description, ".$str_order."+0 ASC";
		else
			$str_order = " ORDER BY category_description, ".$str_order;
	else
		$str_order = " ORDER BY ".$str_order;

    function getSPrice($flt_price, $int_margin) {
            if ($int_margin > 0)
                    return RoundUp($flt_price * (1 + ($int_margin/100)));
            else
                    return $flt_price;
    }

    $arr_prev_month=getPreviousMonth();

    if ($int_type == 'ALL') {
        if ($int_category_id == 'ALL') {
            $str_category_filter = "";
        }
	else {
            $str_category_filter = " AND (sc.category_id = $int_category_id) ";
	}
    }
    else if ($int_type == '1') {
        if ($int_category_id == 'ALL')
            $str_category_filter = " AND (sc.is_perishable = 'Y') ";
	else
            $str_category_filter = " AND (sc.category_id = $int_category_id) AND (sc.is_perishable = 'Y') ";
    }
    else if ($int_type == '2') {
        if ($int_category_id == 'ALL')
            $str_category_filter = " AND (sc.is_perishable = 'N') ";
	else
            $str_category_filter = " AND (sc.category_id = $int_category_id) AND (sc.is_perishable = 'N') ";
    }

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


//==============================================================================
// Create new purchase order based on result
//------------------------------------------------------------------------------
    
    if (!empty($_GET["action"])) {
        if ($_GET["action"]=="new") {
            $qry->First();
            
            // query object initialization
            $qry_item = new Query("SELECT * FROM ".Yearalize('purchase_items'));
            
            // create purchase order
            $int_purchase_order_id = new_purchase_order($int_supplier_id);
/*
echo "<script language='javascript'>";
echo "alert('id ".$int_purchase_order_id."');";
echo "</script>";
*/
            // create purchase order items
            for ($i=0;$i<$qry->RowCount();$i++) {
                
                // only if the "to order" quantity is greater than zero add to the purchase order
                if ($qry->FieldByName('to_buy') > 0) {
                    
                    // check whether the buying_price is defined
                    if (is_null($qry->FieldByName('buying_price')))
                        $flt_buying_price = 0;
                    else
                        $flt_buying_price = $qry->FieldByName('buying_price');
                    
                    $qry_item->Query("
                        INSERT INTO ".Yearalize('purchase_items')."
                                (purchase_order_id,
                                product_id,
                                supplier_id,
                                quantity_ordered,
                                buying_price,
                                selling_price)
                        VALUES (".$int_purchase_order_id.", ".
                                $qry->FieldByName('product_id').", ".
                                $qry->FieldByName('supplier_id').", ".
                                $qry->FieldByName('to_buy').", ".
                                $flt_buying_price.", ".
                                getSPrice($flt_buying_price, $qry->FieldByName('margin_percent')).")
                    ");
                }
                $qry->Next();
            }
            echo "<script language=\"javascript\">\n";
            echo "alert('Created purchase order');\n";
            echo "</script>\n";
        }
    }
?>

<html>
<head>
    <link rel="stylesheet" type="text/css" href="../include/styles.css" />
    <script language='javascript'>
	function processCheckbox() {
	    for (i=0;i<document.purchase_category_list_data.length;i++) {
		if (document.purchase_category_list_data.elements[i].name.indexOf('cb_')>=0) {
		    document.purchase_category_list_data.elements[i].checked = true;
		}
	    }
	}

	function processCheckboxFalse() {
	    for (i=0;i<document.purchase_category_list_data.length;i++) {
		if (document.purchase_category_list_data.elements[i].name.indexOf('cb_')>=0) {
		    document.purchase_category_list_data.elements[i].checked = false;
		}
	    }
	}
    </script>
</head>

<body id='body_bgcolor' leftmargin=5 topmargin=5 marginwidth=5 marginheight=5>

<form name='purchase_category_list_data' method='POST'>

    <table width='100%' cellpadding='0' cellspacing='0'>
    <tr><td align='center'>
        <table border=1 cellpadding=7 cellspacing=0>
            <tr class='headertext' bgcolor='#808080'>
				<td><a href="javascript:processCheckbox()"><img src='../images/tick_true.png' title='Select All' alt='Select All' border='0'></a>&nbsp;<a href="javascript:processCheckboxFalse()"><img src='../images/tick_false.png' title='Unselect All' alt='Unselect All' border='0'></a></td>
                <td align='right'>Code</td>
                <td>Description</td>
                <td align='right'>S. Price</td>
                <td align='right'>Min.</td>
                <td align='right'>Ordered</td>
                <td align='right'>Current</td>
                <? if (($int_method == PO_PREDICT_PREVIOUS_CURRENT) || ($int_method == PO_PREDICT_PREVIOUS)) { ?>
                    <td align='right'>Sold Last Month</td>
                <? } ?>
                <td align='right'>Sold This Month</td>
                <td align='right'>To Buy</td>
            </tr>
            <?
                for ($i=0;$i<$qry->RowCount();$i++) {
                    
                    if ($i % 2 == 0)
                        $str_color="#eff7ff";
                    else
                        $str_color="#deecfb";
                        
                    echo "<tr bgcolor='$str_color'>";
					echo "<td><input type='checkbox' name='cb_".$qry->FieldByName('product_id')."' checked class='normaltext'></td>";
                    echo "<td align='right' class='normaltext'>".$qry->FieldByName('product_code')."</td>";
                    echo "<td class='normaltext'>".$qry->FieldByName('product_description')."</td>";
                    echo "<td align='right' class='normaltext'>".$qry->FieldByName('selling_price')."</td>";
                    echo "<td align='right' class='normaltext'>".number_format($qry->FieldByName('stock_minimum'), 2, '.', '')."</td>";
                    echo "<td align='right' class='normaltext'>".number_format($qry->FieldByName('stock_ordered'), 2, '.', '')."</td>";
                    echo "<td align='right' class='normaltext'>".number_format($qry->FieldByName('stock_current'), 2, '.', '')."</td>";
                    if (($int_method == PO_PREDICT_PREVIOUS_CURRENT) || ($int_method == PO_PREDICT_PREVIOUS)) {
                        echo "<td align='right' class='normaltext'>".number_format($qry->FieldByName('prev_stock_sold'), 2, '.', '')."</td>";
                    }
                    echo "<td align='right' class='normaltext'>".number_format($qry->FieldByName('stock_sold'), 2, '.', '')."</td>";
                    if ($qry->FieldByName('to_buy') < $qry->FieldByName('stock_minimum'))
                        echo "<td align='right' class='normaltext'><font color='red'>".number_format($qry->FieldByName('to_buy'), 2, '.', '')." ".$qry->FieldByName('measurement_unit')."</font></td>";
                    else
                        echo "<td align='right' class='normaltext'>".number_format($qry->FieldByName('to_buy'), 2, '.', '')." ".$qry->FieldByName('measurement_unit')."</td>";
                    
                    $qry->Next();
                }
                
            ?>
        </table>
    </td></tr>
    </table>
</body>
</html>
