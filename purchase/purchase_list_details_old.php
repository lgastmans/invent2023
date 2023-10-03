<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");
    require_once("../include/purchase_funcs.inc.php");
    require_once("../common/product_funcs.inc.php");

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
	$str_order_field = $str_order_field."+0 ASC";

    $str_filter = '';
    $str_order = '';
    if ($int_supplier_id == 'ALL') {
        $str_filter = "AND (ss.supplier_city = '".$str_city."')";
        $str_order = "ORDER BY ss.supplier_name, ".$str_order_field;
    }
    else {
        $str_filter = "AND (ss.supplier_id = ".$int_supplier_id.")";
        $str_order = "ORDER BY ".$str_order_field;
    }
    
    function getSPrice($flt_price, $int_margin) {
            if ($int_margin > 0)
                    return RoundUp($flt_price * (1 + ($int_margin/100)));
            else
                    return $flt_price;
    }

    $arr_prev_month = getPreviousMonth();

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
	else if ($int_method == PO_PREDICT_CURRENT) {
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

	if ($_SESSION['int_month_loaded'] == 4) {
		$int_prev_year = date('Y',time()) - 1;
		$str_prev_stock_balance_table = "stock_balance_".$int_prev_year;
	}
	else
		$str_prev_stock_balance_table = Yearalize('stock_balance');
		

    $str_query = "
    SELECT
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
        ss.supplier_id,
        ss.supplier_name,
        sc.category_description
    FROM
        stock_product sp
        LEFT JOIN ".Yearalize('stock_batch')." sb ON ((sb.batch_id=(SELECT MAX(batch_id) FROM ".Yearalize('stock_batch')." sb2 WHERE (sb2.product_id=sp.product_id) LIMIT 1)))
        INNER JOIN ".Monthalize('stock_storeroom_product')." ssp ON (ssp.product_id=sp.product_id) AND (ssp.storeroom_id=".$_SESSION["int_current_storeroom"].")
        LEFT JOIN $str_prev_stock_balance_table p_sby ON (p_sby.product_id=sp.product_id) AND (p_sby.storeroom_id=".$int_sold_storeroom.") AND (p_sby.balance_month=".$arr_prev_month[1].")
        INNER JOIN ".Yearalize('stock_balance')." sby ON (sby.product_id=sp.product_id) AND (sby.storeroom_id=".$int_sold_storeroom.") AND (sby.balance_month=".$_SESSION["int_month_loaded"].")
        INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id=sp.measurement_unit_id)
        INNER JOIN stock_supplier ss ON (ss.supplier_id=sp.supplier_id)
        INNER JOIN stock_category sc ON (sc.category_id = sp.category_id)
    WHERE (sp.list_in_purchase = 'Y')
	AND (sp.deleted = 'N')
        ".$str_filter."
        ".$str_order;
 
echo $str_query;

    $qry = new Query($str_query);

    if (!empty($_POST["action"])) {
        if ($_POST["action"] == "new") {
	    
			// get all checked rows
            $arr_items = array();
			$int_counter = 0;
			$bool_insert = false;
			foreach($_POST as $key=>$value) {
			
				$int_pos = strpos($key, 'cb_');
				if ($int_pos !== false) {
					$int_counter++;
					$bool_insert = true;
					$int_product_id = intval(substr($key, 3, strlen($key)));
					$arr_items[$int_counter][0] = $int_product_id;
				}
			
				$int_pos = strpos($key, 'input_');
				if ($int_pos !== false) {
					if (is_numeric($value))
						$flt_quantity = number_format($value,2,'.','');
					else
						$flt_quantity = 0;
					
					if ($bool_insert)
						$arr_items[$int_counter][1] = $flt_quantity;
					$bool_insert = false;
				}
			}
			
            // query object initialization
            $qry_item = new Query("SELECT * FROM stock_product");
            
            // create purchase order
            $int_purchase_order_id = new_purchase_order($int_supplier_id);
			
			for ($i=1;$i<=count($arr_items);$i++) {
			
				$flt_buying_price = getBuyingPrice($arr_items[$i][0]);
				$flt_selling_price = getSellingPrice($arr_items[$i][0]);
				
				$qry_item->Query("
					INSERT INTO ".Yearalize('purchase_items')."
						(purchase_order_id,
						product_id,
						supplier_id,
						quantity_ordered,
						buying_price,
						selling_price)
					VALUES (".$int_purchase_order_id.", ".
						$arr_items[$i][0].", ".
						$_POST['supplier_id'].", ".
						$arr_items[$i][1].", ".
						$flt_buying_price.", ".
						$flt_selling_price.")
				");
			}
			
			echo "<script language='javascript'>";
			echo "alert('Purchase order created successfully');";
			echo "</script>";
        }
    }
?>

<html>
<head>
    <link rel="stylesheet" type="text/css" href="../include/styles.css" />
    <script language='javascript'>
	function processCheckbox() {
	    for (i=0;i<document.purchase_list_details.length;i++) {
		if (document.purchase_list_details.elements[i].name.indexOf('cb_')>=0) {
		    document.purchase_list_details.elements[i].checked = true;
		}
	    }
	}

	function processCheckboxFalse() {
	    for (i=0;i<document.purchase_list_details.length;i++) {
		if (document.purchase_list_details.elements[i].name.indexOf('cb_')>=0) {
		    document.purchase_list_details.elements[i].checked = false;
		}
	    }
	}
    </script>
</head>

<body id='body_bgcolor' leftmargin=5 topmargin=5 marginwidth=5 marginheight=5>

<form name='purchase_list_details' method='POST'>

<input type='hidden' name='action' value='new'>
<input type='hidden' name='supplier_id' value='<?echo $int_supplier_id ;?>'>

    <table width='100%' cellpadding='0' cellspacing='0'>
    <tr><td align='center'>
        <table border=1 cellpadding=7 cellspacing=0>
            <tr class='normaltext_bold' bgcolor='lightgrey'>
		<td><a href="javascript:processCheckbox()"><img src='../images/tick_true.png' title='Select All' alt='Select All' border='0'></a>&nbsp;<a href="javascript:processCheckboxFalse()"><img src='../images/tick_false.png' title='Unselect All' alt='Unselect All' border='0'></a></td>
                <td>Code</td>
                <td>Description</td>
                <td>B. Price</td>
                <td>S. Price</td>
                <td>Min.</td>
                <td>Ordered</td>
                <td>Current</td>
                <? if (($int_method == PO_PREDICT_PREVIOUS_CURRENT) || ($int_method == PO_PREDICT_PREVIOUS)) { ?>
                    <td>Sold Last Month</td>
                <? } ?>
                <td>Sold This Month</td>
                <td>To Buy</td>
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
                    echo "<td align='right' class='normaltext'>".$qry->FieldByName('buying_price')."</td>";
                    echo "<td align='right' class='normaltext'>".$qry->FieldByName('selling_price')."</td>";
                    echo "<td align='right' class='normaltext'>".$qry->FieldByName('stock_minimum')."</td>";
                    echo "<td align='right' class='normaltext'>".$qry->FieldByName('stock_ordered')."</td>";
                    echo "<td align='right' class='normaltext'>".$qry->FieldByName('stock_current')."</td>";
                    if (($int_method == PO_PREDICT_PREVIOUS_CURRENT) || ($int_method == PO_PREDICT_PREVIOUS)) {
                        echo "<td align='right' class='normaltext'>".$qry->FieldByName('prev_stock_sold')."</td>";
                    }
                    echo "<td align='right' class='normaltext'>".$qry->FieldByName('stock_sold')."</td>";
					echo "<td><input type='text' name='input_".$qry->FieldByName('product_id')."' value='".$qry->FieldByName('to_buy')."' class='input_100' autocomplete='off'></td>\n";
/*
                    if ($qry->FieldByName('to_buy') < $qry->FieldByName('stock_minimum'))
                        echo "<td align='right' class='normaltext'><font color='red'>".$qry->FieldByName('to_buy')." ".$qry->FieldByName('measurement_unit')."</font></td>\n";
                    else
                        echo "<td align='right' class='normaltext'>".$qry->FieldByName('to_buy')." ".$qry->FieldByName('measurement_unit')."</td>\n";
*/                    
                    $qry->Next();
                }
                
            ?>
        </table>
    </td></tr>
    </table>
    
</form>
</body>
</html>