<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
	$str_code = "";
	if (IsSet($_GET["product_code"]))
		$str_code = $_GET["product_code"];
	
	if (isSet($_GET["filter_from"])) {
		$arr_period = explode("_", $_GET["filter_from"]);
		$int_year_from = intval($arr_period[1]);
		$int_month_from = intval($arr_period[0]);
		
		$int_start = intval($arr_period[1]);
		$month_start = intval($arr_period[0]);
	}
	else {
		$int_year_from = date('Y');
		$int_month_from = date('n');
		
		$int_start = date('Y');
		$month_start = date('n');
	}

	if (isSet($_GET["filter_to"])) {
		$arr_period = explode('_', $_GET['filter_to']);
		$int_year_to = intval($arr_period[1]);
		$int_month_to = intval($arr_period[0]);
		
		$int_end = intval($arr_period[1]);
		$month_end = intval($arr_period[0]);
	}
	else {
		$int_year_to = date('Y');
		$int_month_to = date('n');
		
		$int_end = date('Y');
		$month_end = date('n');
	}
	
	if ($int_end == $int_start) {
		$int_columns = ($month_end - $month_start) + 1;
	}
	else {
		$int_columns = (12 - $month_start) + (($int_end - $int_start -1) * 12) + $month_end;
	}
	
	$arr_months = array(
		1 => 'January',
		2 => 'February',
		3 => 'March',
		4 => 'April',
		5 => 'May',
		6 => 'June',
		7 => 'July',
		8 => 'August',
		9 => 'September',
		10 => 'October',
		11 => 'November',
		12 => 'December'
	);
	
	$qry_data = new Query("
		SELECT *
		FROM stock_product sp, stock_measurement_unit mu, ".Monthalize('stock_storeroom_product')." ssp
		WHERE product_code = '".$str_code."'
			AND (sp.deleted = 'N')
			AND (sp.is_available = 'Y')
			AND (sp.measurement_unit_id = mu.measurement_unit_id)
			AND (ssp.product_id = sp.product_id)
			AND (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")
	");
	$int_product_id = $qry_data->FieldByName('product_id');
	$str_unit = $qry_data->FieldByName('measurement_unit');
	
	$arr_data = array();

	if ($qry_data->RowCount() > 0) {
		
		$int_cur_month = $int_month_from;
		$int_cur_year = $int_year_from;
		$int_counter = 0;
		
		while (true) {
			
			$qry_data->Query("
					SELECT *
					FROM stock_storeroom_product_".$int_cur_year."_".$int_cur_month." ssp
					WHERE (ssp.product_id = $int_product_id)
							AND (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")
			");
			$arr_data[$int_counter][9] = number_format($qry_data->FieldByName('stock_adjusted'),3,'.',',');
			
			if ($int_cur_month <= 3) 
				$str_query = "
						SELECT *
						FROM stock_balance_".($int_cur_year-1)."
						WHERE (product_id = $int_product_id)
								AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
								AND (balance_month = ".$int_cur_month.")
								AND (balance_year = ".$int_cur_year.")
				";
			else
				$str_query = "
						SELECT *
						FROM stock_balance_".$int_cur_year."
						WHERE (product_id = $int_product_id)
								AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
								AND (balance_month = ".$int_cur_month.")
								AND (balance_year = ".$int_cur_year.")
				";
			
			$qry_data->Query($str_query);
			
			$arr_data[$int_counter][0] = $int_cur_year;
			$arr_data[$int_counter][1] = $int_cur_month;
			if ($qry_data->RowCount() > 0) {
				$arr_data[$int_counter][2] = number_format($qry_data->FieldByName('stock_opening_balance'),3,'.',',');
				$arr_data[$int_counter][3] = number_format($qry_data->FieldByName('stock_closing_balance'),3,'.',',');
				$arr_data[$int_counter][4] = number_format($qry_data->FieldByName('stock_mismatch_addition'),3,'.',',');
				$arr_data[$int_counter][5] = number_format($qry_data->FieldByName('stock_mismatch_deduction'),3,'.',',');
				$arr_data[$int_counter][6] = number_format($qry_data->FieldByName('stock_sold'),3,'.',',');
				$arr_data[$int_counter][7] = number_format($qry_data->FieldByName('stock_returned'),3,'.',',');
				$arr_data[$int_counter][8] = number_format($qry_data->FieldByName('stock_received'),3,'.',',');
				$arr_data[$int_counter][10] = number_format($qry_data->FieldByName('stock_cancelled'),3,'.',',');
				$arr_data[$int_counter][11] = number_format($qry_data->FieldByName('stock_in'),3,'.',',');
				$arr_data[$int_counter][12] = number_format($qry_data->FieldByName('stock_out'),3,'.',',');
			}
			else {
				$arr_data[$int_counter][2] = '&nbsp;';
				$arr_data[$int_counter][3] = '&nbsp;';
				$arr_data[$int_counter][4] = '&nbsp;';
				$arr_data[$int_counter][5] = '&nbsp;';
				$arr_data[$int_counter][6] = '&nbsp;';
				$arr_data[$int_counter][7] = '&nbsp;';
				$arr_data[$int_counter][8] = '&nbsp;';
				$arr_data[$int_counter][10] = '&nbsp;';
				$arr_data[$int_counter][11] = '&nbsp;';
				$arr_data[$int_counter][12] = '&nbsp;';
			}
		
			if ($int_cur_year > $int_year_to)
				break;
			else if ($int_year_to == $int_cur_year)
				if ($int_cur_month >= $int_month_to)
					break;
			
			if ($int_cur_month == 12) {
				$int_cur_year++;
				$int_cur_month = 1;
			}
			else
				$int_cur_month++;
			
			$int_counter++;
		}
	}
	
	$total_opening_balance = 0;
	$total_closing_balance = 0;
	$total_mismatch_addition = 0;
	$total_mismatch_deduction = 0;
	$total_sold = 0;
	$total_returned = 0;
	$total_received = 0;
	$total_cancelled = 0;
	$total_storeroom_in = 0;
	$total_storeroom_out = 0;
	$total_adjusted = 0;
?>

<html>
<head>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
</head>
<body id='body_bgcolor' leftmargin=15 topmargin=5 marginwidth=5 marginheight=5>
	<table border=1 cellpadding=5 cellspacing=0 style='table-layout:fixed' width="100%">
		<tr class='normaltext_bold' bgcolor='lightgrey'>
			<td width='150px' align="center">Balances</td>
			<?
				if ($int_columns > 0) {
					$int_start_month = $month_start;
					$int_start_year = $int_start;
					
					while (true) {
						echo "<td width='120px' align='center'>".getMonthName($int_start_month)."<br>".$int_start_year."</td>\n";
					
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
			?>
			<td width='150px' class='normaltext_bold' align="center">Total</td>
		</tr>
		<tr bgcolor='#eff7ff'>
			<td width='150px' class='normaltext_bold'>Opening Balance</td>
			<?
				for ($i=0;$i<count($arr_data);$i++) {
					echo "<td width='150px' align='right' class='normaltext'>".$arr_data[$i][2]."</td>";
					$total_opening_balance += $arr_data[$i][2];
				}
			?>
			<td class="normaltext" align="right"><?echo number_format($total_opening_balance,3,'.',',');?></td>
		</tr>
		<tr bgcolor='#deecfb'>
			<td class='normaltext_bold'>Closing Balance</td>
			<?
				for ($i=0;$i<count($arr_data);$i++) {
					echo "<td align='right' class='normaltext'>".$arr_data[$i][3]."</td>";
					$total_closing_balance += $arr_data[$i][3];
				}
			?>
			<td class="normaltext" align="right"><?echo number_format($total_closing_balance,3,'.',',');?></td>
		</tr>
		<tr bgcolor='#eff7ff'>
			<td class='normaltext_bold'>Mismatch Addition</td>
			<?
				for ($i=0;$i<count($arr_data);$i++) {
					echo "<td align='right' class='normaltext'>".$arr_data[$i][4]."</td>";
					$total_mismatch_addition += $arr_data[$i][4];
				}
			?>
			<td class="normaltext" align="right"><?echo number_format($total_mismatch_addition,3,'.',',');?></td>
		</tr>
		<tr bgcolor='#deecfb'>
			<td class='normaltext_bold'>Mismatch Deduction</td>
			<?
				for ($i=0;$i<count($arr_data);$i++) {
					echo "<td align='right' class='normaltext'>".$arr_data[$i][5]."</td>";
					$total_mismatch_deduction += $arr_data[$i][5];
				}
			?>
			<td class="normaltext" align="right"><?echo number_format($total_mismatch_deduction,3,'.',',');?></td>
		</tr>
		<tr bgcolor='#eff7ff'>
			<td class='normaltext_bold'>Sold</td>
			<?
				for ($i=0;$i<count($arr_data);$i++) {
					echo "<td align='right' class='normaltext'>".$arr_data[$i][6]."</td>";
					$total_sold += $arr_data[$i][6];
				}
			?>
			<td class="normaltext" align="right"><?echo number_format($total_sold,3,'.',',');?></td>
		</tr>
		<tr bgcolor='#deecfb'>
			<td class='normaltext_bold'>Returned</td>
			<?
				for ($i=0;$i<count($arr_data);$i++) {
					echo "<td align='right' class='normaltext'>".$arr_data[$i][7]."</td>";
					$total_returned += $arr_data[$i][7];
				}
			?>
			<td class="normaltext" align="right"><?echo number_format($total_returned,3,'.',',');?></td>
		</tr>
		<tr bgcolor='#eff7ff'>
			<td class='normaltext_bold'>Received</td>
			<?
				for ($i=0;$i<count($arr_data);$i++) {
					echo "<td align='right' class='normaltext'>".$arr_data[$i][8]."</td>";
					$total_received += $arr_data[$i][8];
				}
			?>
			<td class="normaltext" align="right"><?echo number_format($total_received,3,'.',',');?></td>
		</tr>
		<tr bgcolor='#deecfb'>
			<td class='normaltext_bold'>Cancelled</td>
			<?
				for ($i=0;$i<count($arr_data);$i++) {
					echo "<td align='right' class='normaltext'>".$arr_data[$i][10]."</td>";
					$total_cancelled += $arr_data[$i][10];
				}
			?>
			<td class="normaltext" align="right"><?echo number_format($total_cancelled,3,'.',',');?></td>
		</tr>
		<tr bgcolor='#eff7ff'>
			<td class='normaltext_bold'>Storeroom In</td>
			<?
				for ($i=0;$i<count($arr_data);$i++) {
					echo "<td align='right' class='normaltext'>".$arr_data[$i][11]."</td>";
					$total_storeroom_in += $arr_data[$i][11];
				}
			?>
			<td class="normaltext" align="right"><?echo number_format($total_storeroom_in,3,'.',',');?></td>
		</tr>
		<tr bgcolor='#deecfb'>
			<td class='normaltext_bold'>Storeroom Out</td>
			<?
				for ($i=0;$i<count($arr_data);$i++) {
					echo "<td align='right' class='normaltext'>".$arr_data[$i][12]."</td>";
					$total_storeroom_out += $arr_data[$i][12];
				}
			?>
			<td class="normaltext" align="right"><?echo number_format($total_storeroom_out,3,'.',',');?></td>
		</tr>
		<tr bgcolor='#eff7ff'>
			<td class='normaltext_bold'>Adjusted</td>
			<?
				for ($i=0;$i<count($arr_data);$i++) {
					echo "<td align='right' class='normaltext'>".$arr_data[$i][9]."</td>";
					$total_adjusted += $arr_data[$i][9];
				}
			?>
			<td class="normaltext" align="right"><?echo number_format($total_adjusted,3,'.',',');?></td>
		</tr>
    </table>
</body>
</html>