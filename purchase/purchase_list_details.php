<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../include/purchase_funcs.inc.php");
	require_once("../common/product_funcs.inc.php");

	$code_sorting = $arr_invent_config['settings']['code_sorting'];
	
	$str_city="";
	if (IsSet($_GET['city'])) {
		$str_city = $_GET['city'];
	}

	$int_supplier_id = 0;
	if (IsSet($_GET['supplier_id']))
		$int_supplier_id = $_GET['supplier_id'];

	/*
		current number of days
	*/
	$int_days = date('j', time());
	
	/*
		predict for this number of days
	*/
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
	
	/*
		show rows
	*/
	$str_show = "ALL";
	$str_having = "";
	if (IsSet($_GET['show']))
		$str_show = $_GET['show'];
	if ($str_show == "ALL")
		$str_having = "";
	else if ($str_show == "NON_ZERO")
		$str_having = "HAVING (to_buy > 0.0)";
	else if ($str_show == "BELOW_MINIMUM")
		$str_having = "HAVING (to_buy < sp.minimum_qty)";

	$int_method = '';
	if (IsSet($_GET['method']))
		$int_method = $_GET['method'];

	$str_order_field = 'product_code';
	if (IsSet($_GET['order']))
		$str_order_field = $_GET['order'];

	/*
		filter on category if set
	*/
	$str_filter_category = '';
	if ((IsSet($_GET['category'])) && ($_GET['category'] != 'ALL')) {
		$str_categories = $_GET['category'];
		$arr_ids = explode('|', $str_categories);
		
		$str_filter_category = "AND ( ";
		for ($i=0;$i<count($arr_ids);$i++) {
			$str_filter_category .= " (sc.category_id = ".$arr_ids[$i].") OR ";
		}
		$str_filter_category = substr($str_filter_category, 0, strlen($str_filter_category)-4);
		$str_filter_category .= ")";
	}
	
	/*
		the stock sold should be retrieved from this storeroom
	*/
	$int_sold_storeroom = $_SESSION['int_current_storeroom'];
	if (IsSet($_GET['storeroom_id']))
		$int_sold_storeroom = $_GET['storeroom_id'];

	if ($str_order_field == 'product_code')
		if ($code_sorting == 'ALPHA_NUM')
			$str_order_field = $str_order_field."+0 ASC";

	$str_filter = '';
	$str_order = '';
	if ($int_supplier_id == 'ALL') {
		$str_filter = "AND (ss.supplier_city = '".$str_city."')";
		$str_order = "ORDER BY ss.supplier_name, sc.category_description, ".$str_order_field;
	}
	else {
		$str_filter = "AND (ss.supplier_id = ".$int_supplier_id.")";
		$str_order = "ORDER BY sc.category_description, ".$str_order_field;
	}

	/*
		array holds
			1 => month
			2 => year
	*/
	$arr_prev_month = getPreviousMonth();
	
	/*
		table name of stock_balance for previous month
	*/
	if ($_SESSION['int_month_loaded'] == 4) {
		$int_prev_year = date('Y',time()) - 1;
		$str_prev_stock_balance_table = "stock_balance_".$int_prev_year;
	}
	else
		$str_prev_stock_balance_table = Yearalize('stock_balance');

	/*
		selling price base on buying price * margin %
	*/
	function getSPrice($flt_price, $int_margin) {
		if ($int_margin > 0)
			return RoundUp($flt_price * (1 + ($int_margin/100)));
		else
			return $flt_price;
	}
	
	
	/*
	SELECT sp.product_id, sp.product_code, sp.product_description, sp.purchase_round, sb_sold.stock_sold, 
		SUM(ssp.stock_current) AS stock_current, ssp.stock_ordered, ssp.stock_minimum, 
		@qty_value := ( (sb_sold.stock_sold/20 * 1) - ssp.stock_current - ssp.stock_ordered ) AS to_buy, 
		IF (smu.is_decimal='Y', (ROUND(@qty_value, 2) * sp.purchase_round), 
		IF (ROUND(@qty_value) * sp.purchase_round > 0, (ROUND(@qty_value) * sp.purchase_round) , 0 ) ) AS rounded_value, 
		smu.is_decimal, sc.category_description, ss.supplier_name 
	FROM stock_product sp 
	LEFT JOIN stock_balance_2008 sb_sold ON (sb_sold.product_id = sp.product_id) AND (sb_sold.storeroom_id = 2) AND (sb_sold.balance_month = 10) AND (sb_sold.balance_year = 2008) 
	INNER JOIN stock_storeroom_product_2008_10 ssp ON (ssp.product_id = sp.product_id)
	INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id) 
	INNER JOIN stock_supplier ss ON (ss.supplier_id = sp.supplier_id) 
	INNER JOIN stock_category sc ON (sc.category_id = sp.category_id) 
	WHERE (sp.list_in_purchase = 'Y') 
		AND (sp.deleted = 'N')
		AND (ss.supplier_id = 793)
	GROUP BY sp.product_id
	ORDER BY sc.category_description, product_code+0 ASC	
	*/
	
	
	if ($int_method == PO_PREDICT_NONE) {
		$str_query = "
			SELECT sp.product_id, sp.product_code, sp.product_description, sp.purchase_round, sp.minimum_qty,
				sb_sold.stock_sold AS prev_stock_sold,
				$str_select_stock, ssp.stock_ordered, ssp.stock_minimum,
				@qty_value := (sp.minimum_qty - ssp.stock_current) AS to_buy,
				IF (smu.is_decimal='Y',
						(ROUND(@qty_value, 2) * sp.purchase_round),
						IF (ROUND(@qty_value) * sp.purchase_round > 0,
								(ROUND(@qty_value) * sp.purchase_round)
								, 0
						)
				)
				AS rounded_value,
				smu.is_decimal,
				sc.category_description,
				ss.supplier_name
			FROM stock_product sp
			LEFT JOIN $str_prev_stock_balance_table sb_sold ON
				(sb_sold.product_id = sp.product_id)
				AND (sb_sold.storeroom_id = ".$int_sold_storeroom.")
				AND (sb_sold.balance_month = ".$arr_prev_month[1].")
				AND (sb_sold.balance_year = ".$arr_prev_month[2].")
			$str_join_stock
			INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			INNER JOIN stock_supplier ss ON (ss.supplier_id = sp.supplier_id)
			INNER JOIN stock_category sc ON (sc.category_id = sp.category_id) $str_filter_category
			WHERE (sp.list_in_purchase = 'Y')
				AND (sp.deleted = 'N')
				$str_filter
			$str_having
			$str_group_stock
			$str_order
		";
	}
	else if ($int_method == PO_PREDICT_PREVIOUS) {
		$str_query = "
			SELECT sp.product_id, sp.product_code, sp.product_description, sp.purchase_round, sp.minimum_qty,
				sb_sold.stock_sold AS prev_stock_sold,
				$str_select_stock, ssp.stock_ordered, ssp.stock_minimum,
				@qty_value := (
					(sb_sold.stock_sold/$int_days * $int_num_days) - ssp.stock_current - ssp.stock_ordered
				) AS to_buy,
				IF (smu.is_decimal='Y',
						(ROUND(@qty_value, 2) * sp.purchase_round),
						IF (ROUND(@qty_value) * sp.purchase_round > 0,
								(ROUND(@qty_value) * sp.purchase_round)
								, 0
						)
				)
				AS rounded_value,
				smu.is_decimal,
				sc.category_description,
				ss.supplier_name
			FROM stock_product sp
			LEFT JOIN $str_prev_stock_balance_table sb_sold ON
				(sb_sold.product_id = sp.product_id)
				AND (sb_sold.storeroom_id = ".$int_sold_storeroom.")
				AND (sb_sold.balance_month = ".$arr_prev_month[1].")
				AND (sb_sold.balance_year = ".$arr_prev_month[2].")
			$str_join_stock
			INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			INNER JOIN stock_supplier ss ON (ss.supplier_id = sp.supplier_id)
			INNER JOIN stock_category sc ON (sc.category_id = sp.category_id) $str_filter_category
			WHERE (sp.list_in_purchase = 'Y')
				AND (sp.deleted = 'N')
				$str_filter
			$str_having
			$str_group_stock
			$str_order
		";
	}
	else if ($int_method == PO_PREDICT_PREVIOUS_CURRENT) {
		$int_days = 26 + $int_days;
		$str_query = "
			SELECT sp.product_id, sp.product_code, sp.product_description, sp.purchase_round, sp.minimum_qty,
				sb_sold.stock_sold,
				sb_prev.stock_sold AS prev_stock_sold,
				$str_select_stock, ssp.stock_ordered, ssp.stock_minimum,
				@qty_value := (
					(
						(
							SELECT SUM(stock_sold)
							FROM ".Yearalize('stock_balance')."
							WHERE (
									((balance_month = ".$_SESSION["int_month_loaded"].") AND (balance_year = ".$_SESSION["int_year_loaded"]."))
									OR ((balance_month = ".$arr_prev_month[1].") AND (balance_year = ".$arr_prev_month[2]."))
								)
								AND (product_id = sp.product_id)
								AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
							
						)
						/$int_days * $int_num_days) - ssp.stock_current - ssp.stock_ordered
				) AS to_buy,
				IF (smu.is_decimal='Y',
						(ROUND(@qty_value, 2) * sp.purchase_round),
						IF (ROUND(@qty_value) * sp.purchase_round > 0,
								(ROUND(@qty_value) * sp.purchase_round)
								, 0
						)
				)
				AS rounded_value,
				smu.is_decimal,
				sc.category_description,
				ss.supplier_name
			FROM stock_product sp
			LEFT JOIN ".Yearalize('stock_balance')." sb_sold ON
				(sb_sold.product_id = sp.product_id)
				AND (sb_sold.storeroom_id = ".$int_sold_storeroom.")
				AND (sb_sold.balance_month = ".$_SESSION["int_month_loaded"].")
				AND (sb_sold.balance_year = ".$_SESSION["int_year_loaded"].")
			LEFT JOIN $str_prev_stock_balance_table sb_prev ON
				(sb_prev.product_id = sp.product_id)
				AND (sb_prev.storeroom_id = ".$int_sold_storeroom.")
				AND (sb_prev.balance_month = ".$arr_prev_month[1].")
				AND (sb_prev.balance_year = ".$arr_prev_month[2].")
			$str_join_stock
			INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			INNER JOIN stock_supplier ss ON (ss.supplier_id = sp.supplier_id)
			INNER JOIN stock_category sc ON (sc.category_id = sp.category_id) $str_filter_category
			WHERE (sp.list_in_purchase = 'Y')
				AND (sp.deleted = 'N')
				$str_filter
			$str_having
			$str_group_stock
			$str_order
		";
	}
	else if ($int_method == PO_PREDICT_CURRENT) {
		$str_query = "
			SELECT sp.product_id, sp.product_code, sp.product_description, sp.purchase_round, sp.minimum_qty,
				sb_sold.stock_sold,
				$str_select_stock, ssp.stock_ordered, ssp.stock_minimum,

				@qty_value := (
					(sb_sold.stock_sold/$int_days * $int_num_days) - ssp.stock_current - ssp.stock_ordered
				) AS to_buy,

				IF (smu.is_decimal='Y',

						(ROUND(@qty_value, 2) * sp.purchase_round),

						IF (ROUND(@qty_value) * sp.purchase_round > 0,
								(ROUND(@qty_value) * sp.purchase_round)
								, 0
						)
				)
				AS rounded_value,

				smu.is_decimal,
				sc.category_description,
				ss.supplier_name
			FROM stock_product sp
			LEFT JOIN ".Yearalize('stock_balance')." sb_sold ON
				(sb_sold.product_id = sp.product_id)
				AND (sb_sold.storeroom_id = ".$int_sold_storeroom.")
				AND (sb_sold.balance_month = ".$_SESSION["int_month_loaded"].")
				AND (sb_sold.balance_year = ".$_SESSION["int_year_loaded"].")
			$str_join_stock
			INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			INNER JOIN stock_supplier ss ON (ss.supplier_id = sp.supplier_id)
			INNER JOIN stock_category sc ON (sc.category_id = sp.category_id) $str_filter_category
			WHERE (sp.list_in_purchase = 'Y')
				AND (sp.deleted = 'N')
				$str_filter
			$str_having
			$str_group_stock
			$str_order
		";
	}
//echo $str_query;

	$qry = new Query($str_query);
	if ($qry->b_error == true)
		echo mysql_error();
	
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
				

				$sql = "
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
				";
				
				$qry_item->Query($sql);

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
	
	function processCheckPositive() {
		var oDoc = document.purchase_list_details;
		
		for (i=0;i<oDoc.length;i++) {
			if (oDoc.elements[i].name.indexOf('input_') >= 0) {
				var arrID = oDoc.elements[i].name.split('_');
				oCheck = document.getElementById('cb_'+arrID[1]);
				if (oCheck) {
					oCheck.checked = parseFloat(oDoc.elements[i].value) > 0;
				}
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
				<td>
					<a href="javascript:processCheckbox()"><img src='../images/tick_true.png' title='Select All' alt='Select All' border='0'></a>&nbsp;
					<a href="javascript:processCheckboxFalse()"><img src='../images/tick_false.png' title='Unselect All' alt='Unselect All' border='0'></a><br>
					<a href="javascript:processCheckPositive()"><img src='../images/wand.png' title='Select all positive values' alt='Select all positive values' border='0'></a>
				</td>
				<td>Code</td>
				<td>Description</td>
				<td>Category</td>
				<? if ($int_supplier_id == 'ALL') { ?>
					<td>Supplier</td>
				<? } ?>
				<td>B. Price</td>
				<td>S. Price</td>
				<td>Min.</td>

				<?php if ($int_method != PO_PREDICT_NONE) { ?>
					<td>Ordered</td>
				<?php } ?>

				<td>Current</td>
				<? if (($int_method == PO_PREDICT_PREVIOUS_CURRENT) || ($int_method == PO_PREDICT_PREVIOUS)) { ?>
					<td>Sold Last Month</td>
				<? } ?>
				<? if (($int_method == PO_PREDICT_PREVIOUS_CURRENT) || ($int_method == PO_PREDICT_CURRENT)) { ?>
				<td>Sold This Month</td>
				<? } ?>
				<td>To Buy</td>
			</tr>
			<?
				for ($i=0;$i<$qry->RowCount();$i++) {
					
					if ($i % 2 == 0)
						$str_color="#eff7ff";
					else
						$str_color="#deecfb";
					
					$flt_buying_price = getBuyingPrice($qry->FieldByName('product_id'));
					$flt_selling_price = getSellingPrice($qry->FieldByName('product_id'));
					
					echo "<tr bgcolor='$str_color'>";
					echo "<td><input type='checkbox' name='cb_".$qry->FieldByName('product_id')."' id='cb_".$qry->FieldByName('product_id')."' checked class='normaltext'></td>";
					echo "<td align='right' class='normaltext'>".$qry->FieldByName('product_code')."</td>";
					echo "<td class='normaltext'>".$qry->FieldByName('product_description')."</td>";
					echo "<td class='normaltext'>".$qry->FieldByName('category_description')."</td>";
					if ($int_supplier_id == 'ALL')
						echo "<td class='normaltext'>".$qry->FieldByName('supplier_name')."</td>";
					echo "<td align='right' class='normaltext'>".$flt_buying_price."</td>";
					echo "<td align='right' class='normaltext'>".$flt_selling_price."</td>";
					echo "<td align='right' class='normaltext'>".$qry->FieldByName('minimum_qty')."</td>";

					if ($int_method != PO_PREDICT_NONE)
						echo "<td align='right' class='normaltext'>".$qry->FieldByName('stock_ordered')."</td>";

					echo "<td align='right' class='normaltext'>".$qry->FieldByName('stock_current')."</td>";

					if (($int_method == PO_PREDICT_PREVIOUS_CURRENT) || ($int_method == PO_PREDICT_PREVIOUS)) {
						echo "<td align='right' class='normaltext'>".$qry->FieldByName('prev_stock_sold')."</td>";
					}

					if (($int_method == PO_PREDICT_PREVIOUS_CURRENT) || ($int_method == PO_PREDICT_CURRENT)) {
						echo "<td align='right' class='normaltext'>".$qry->FieldByName('stock_sold')."</td>";
					}

					$flt_to_buy = $qry->FieldByName('rounded_value');

					if ($flt_to_buy < 0)
						$flt_to_buy = 0;

					//if ($flt_to_buy < $qry->FieldByName('stock_minimum'))
					//	$flt_to_buy = $qry->FieldByName('stock_minimum');

					if ($qry->FieldByName('is_decimal') == 'N')
						$flt_to_buy = number_format($flt_to_buy, 0);

					echo "<td><input type='text' name='input_".$qry->FieldByName('product_id')."' id='input_".$qry->FieldByName('product_id')."' value='".$flt_to_buy."' class='input_100' autocomplete='off'></td>\n";
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