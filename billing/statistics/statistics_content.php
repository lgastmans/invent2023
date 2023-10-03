<?php
	require_once("../../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db_mysqli.php");
	
	$display_value = "SOLD";
	if (IsSet($_GET['display_value']))
		$display_value = $_GET['display_value'];
	
	$str_type = "CATEGORY";
	if (IsSet($_GET['stat_type']))
		$str_type = $_GET['stat_type'];
		
	$value = '';
	if (IsSet($_GET['type_value']))
		$value = $_GET['type_value'];
	else
		die();

	$order = 'TOTAL';
	if (IsSet($_GET['order_by']))
		$order = $_GET['order_by'];
	
	if (isSet($_GET["filter_from"])) {
		$arr_period = explode("_", $_GET["filter_from"]);
		$int_start = intval($arr_period[1]);
		$month_start = intval($arr_period[0]);
	}
	else {
		$int_start = date('Y');
		$month_start = date('n');
	}

	if (isSet($_GET["filter_to"])) {
		$arr_period = explode('_', $_GET['filter_to']);
		$int_end = intval($arr_period[1]);
		$month_end = intval($arr_period[0]);
	}
	else {
		$int_end = date('Y');
		$month_end = date('n');
	}
	
	$period = $_GET['period'];
	$period_date = set_mysql_date($_GET['period_date'], "-");

	if ($str_type == 'CATEGORY') {
		$str_query = "
			SELECT *
			FROM stock_product sp, stock_measurement_unit mu, ".Monthalize('stock_storeroom_product')." ssp, stock_category sc
			WHERE sc.category_id = $value
				AND (sp.category_id = sc.category_id)
				AND (sp.deleted = 'N')
				AND (sp.measurement_unit_id = mu.measurement_unit_id)
				AND (ssp.product_id = sp.product_id)
				AND (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")
			ORDER BY sp.product_code
		";
	}
	else if ($str_type == 'SUPPLIER') {
		$str_query = "
			SELECT *
			FROM stock_product sp, stock_measurement_unit mu, ".Monthalize('stock_storeroom_product')." ssp, stock_supplier ss
			WHERE ss.supplier_id = $value
				AND (sp.supplier_id = ss.supplier_id)
				AND (sp.deleted = 'N')
				AND (sp.measurement_unit_id = mu.measurement_unit_id)
				AND (ssp.product_id = sp.product_id)
				AND (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")
			ORDER BY sp.product_code
		";
	}
	$qry_data =& $conn->query($str_query);
	if (!$qry_data) {
		$str_message = "Error deleting from table stock_product ".mysqli_error($conn);
	}

	$arr_data = array();
	$i = 0;
	while ($obj = $qry_data->fetch_object()) {
		$arr_data[$obj->product_id]['product_id'] = $obj->product_id;
		$arr_data[$obj->product_id]['description'] = "<b>".$obj->product_code."</b> ".$obj->product_description;
//		$arr_data[$i]['unit'] = $obj->measurement_unit;
		
		$i++;
	}
	
	if ($period=='month') {
		if ($int_end == $int_start) {
			$int_columns = ($month_end - $month_start) + 1;
		}
		else {
			$int_columns = (12 - $month_start) + (($int_end - $int_start -1) * 12) + $month_end;
		}
	}
	else
		$int_columns = 1;

?>

<html>
<head>
	<link rel="stylesheet" type="text/css" href="../../include/styles.css" />
	
	<script language="javascript">
		function setDisplayValue() {
			document.statistics_content.submit();
		}
	</script>
	<style>
		.normalnumber {
			font-family:Verdana,sans-serif;
			font-size:11px;
			color:black;
			text-align:right;
		}
		
		td {
			border:.5px;
			border-style:groove;
			border-color:grey;
		}
		
		.blank {
			border-bottom-width:0px;
			border-bottom-style:none;
			border-top-width:0px;
			border-top-style:none;
			border-right-width:0px;
			border-right-style:none;
			background-color:#E1E1E1;
		}
	</style>
</head>
<body id='body_bgcolor' leftmargin=5 topmargin=5 marginwidth=5 marginheight=5>

<form name="statistics_content" method="GET" action="statistics_content.php">
	<input type="hidden" name="type_value" value="<?php echo $value?>">
	<input type="hidden" name="filter_from" value="<?php echo $_GET['filter_from']?>">
	<input type="hidden" name="filter_to" value="<?php echo $_GET['filter_to']?>">
	<input type="hidden" name="stat_type" value="<?php echo $str_type;?>">
	<input type="hidden" name="order_by" value="<?php echo $order;?>">
	
	<table border=0 cellpadding=5 cellspacing=0 style='table-layout:fixed' width="100%">
		<tr class='normaltext_bold' bgcolor='lightgrey'>
			<td width='250px' align="center">
				Show:&nbsp;
				<select name="display_value" onchange="javascript:setDisplayValue()">
					<option value="SOLD" <?php if ($display_value == 'SOLD') echo "selected";?>>Sold</option>
					<option value="RECEIVED" <?php if ($display_value == 'RECEIVED') echo "selected";?>>Received</option>
				</select>
			</td>
			<?
				if ($int_columns > 0) {
					if ($period=='month') {
						$int_start_month = $month_start;
						$int_start_year = $int_start;
						
						while (true) {
							echo "<td width='150px' align='center'>".getMonthName($int_start_month)."<br>".$int_start_year."</td>\n";
						
							if (($int_start_month == 12) && ($int_end > $int_start_year)) {
								$int_start_year++;
								$int_start_month = 1;
							}
							else 
								$int_start_month++;
								
							if ($int_end == $int_start_year)
								if ($int_start_month > $month_end)
									break;
						}
					}
					else {
						echo "<td width='150px' align='center' colspan='3'>".$period_date."<br>qty | price | total</td>\n";
					}
				}
			?>
			<? if ($period=='month') { ?>
			<td width="150px" align="right">Total</td>
			<? } ?>
			<td width="100%" class="blank">&nbsp;</td>
		</tr>
		<?
			$int_len = count($arr_data);
			foreach ($arr_data as $key=>$value) {
				//echo "<tr>";
				//echo "<td class='normaltext'>".$arr_data[$i]['description']."</td>";
				$int_product_id = $key; //$arr_data[$i]['product_id'];
				
				if ($int_columns > 0) {
					if ($period=='month') {
						$int_start_month = $month_start;
						$int_start_year = $int_start;
						$total = 0;
						
						while (true) {
							$str_query = "
									SELECT *
									FROM ".Yearalize('stock_balance')."
									WHERE (product_id = $int_product_id)
											AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
											AND (balance_month = ".$int_start_month.")
											AND (balance_year = ".$int_start_year.")
							";
							$qry =& $conn->query($str_query);
							if ($qry->num_rows > 0) {
								$obj = $qry->fetch_object();
								
								if ($display_value == 'SOLD') {
									//echo "<td class='normalnumber'>".$obj->stock_sold."</td>";
									$total += $obj->stock_sold;
									$arr_data[$key][$int_start_month."_".$int_start_year] = $obj->stock_sold;
								}
								else {
									//echo "<td class='normalnumber'>".$obj->stock_received."</td>";
									$total += $obj->stock_received;
									$arr_data[$key][$int_start_month."_".$int_start_year] = $obj->stock_received;
								}
							}
							else
								$arr_data[$key][$int_start_month."_".$int_start_year] = 0;
							
							if (($int_start_month == 12) && ($int_end > $int_start_year)) {
								$int_start_year++;
								$int_start_month = 1;
							}
							else 
								$int_start_month++;
								
							if ($int_end == $int_start_year)
								if ($int_start_month > $month_end)
									break;
						}
						$arr_data[$key]['total'] = $total;
						//echo "<td class='normalnumber'>$total</td>";
						//echo "<td>&nbsp;</td>";
					}
					else { // $period == 'day'
						$str_query = "
								SELECT SUM(bi.quantity + bi.adjusted_quantity) AS quantity, bi.price, 
									(SUM((bi.quantity + bi.adjusted_quantity)) * bi.price) AS stock_sold
								FROM ".Monthalize('bill')." b, ".Monthalize('bill_items')." bi
								WHERE (bi.bill_id = b.bill_id) 
									AND (bi.product_id = $int_product_id)
									AND (b.storeroom_id = ".$_SESSION["int_current_storeroom"].")
									AND (DAYOFMONTH(b.date_created) = DAYOFMONTH('".$period_date."'))
								GROUP BY bi.product_id
						";
						
						$qry =& $conn->query($str_query);
						if ($qry->num_rows > 0) {
							$obj = $qry->fetch_object();
							$total += $obj->stock_sold;
							$arr_data[$int_product_id]['quantity'] = $obj->quantity;
							$arr_data[$int_product_id]['price'] = $obj->price;
							$arr_data[$int_product_id]['sold'] = $obj->stock_sold;
						}
						else {
							$arr_data[$int_product_id]['quantity'] = 0;
							$arr_data[$int_product_id]['price'] = 0;
							$arr_data[$int_product_id]['sold'] = '0';
						}
					}
				}
				//echo "</tr>";
			}

			
			if ($order == 'TOTAL') {
				/*
					sort the array per total
					ref: http://phpbuilder.com/manual/en/function.array-multisort.php
						array_multisort — Sort multiple or multi-dimensional arrays
				*/
				if ($period=='month') {
					foreach ($arr_data as $key => $row) {
						$arr_total[$key] = $row['total'];
					}
				}
				else {
					foreach ($arr_data as $key => $row) {
						$arr_total[$key] = $row['sold'];
					}
				}
				// Add $arr_data as the last parameter, to sort by the common key
				array_multisort($arr_total, SORT_DESC, $arr_data);
			}
			
			/*
				display the data
			*/
			$int_len = count($arr_data);
			for ($i=0;$i<$int_len;$i++) {
				echo "<tr>";
				if ($int_columns > 0) {
					foreach ($arr_data[$i] as $key=>$value) {
						if ($key != 'product_id')
							echo "<td class='normalnumber'>".$value."</td>";
					}
				}
				echo "<td class=\"blank\">&nbsp;</td></tr>";
			}

		?>
	</table>
</form>
</body>
</html>