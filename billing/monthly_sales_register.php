<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/tax.php");
	
	$sql_settings = new Query("
		SELECT *
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION['int_current_storeroom']."
	");
	if ($sql_settings->RowCount() > 0) {
		$str_calc_tax_first = $sql_settings->FieldByName('calculate_tax_before_discount');
		$int_transfer_tax = $sql_settings->FieldByName('bill_transfer_tax');
	}

	if (IsSet($_GET["selected_day"])) {
		if ($_GET["selected_day"] == 'ALL') {
			$int_start_day = 1;
			$int_num_days = DaysInMonth2($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']);
		}
		else {
			$int_start_day = $_GET["selected_day"];
			$int_num_days = $_GET["selected_day"];
		}
	}
	
		
	$_SESSION["int_bills_menu_selected"] = 5;

	if (IsSet($_GET["selected_type"]))
		$int_cur_type = $_GET["selected_type"];
	else
		$int_cur_type = 1;
		
	if (IsSet($_GET['selected_view']))
		$str_selected_view = $_GET['selected_view'];
	else
		$str_selected_view = 'totals';


	function getColumn($arr_dest, $int_definition_id) {
	  $int_retval = -1;
	  for ($i=0; $i<count($arr_dest); $i++) {
	    if ($arr_dest[$i][0] === $int_definition_id) {
		$int_retval = $i;
		break;
	    }
	  }
	  return $int_retval;
	}

	function getDefinitionPercent($arr_dest, $int_pos) {
		$int_retval = -1;
		if (IsSet($arr_dest[$int_pos][3]))
			$int_retval = $arr_dest[$int_pos][3];
		return $int_retval;
	}
	
	function getColumnByType($arr_dest, $int_type) {
	  $int_retval = -1;
	  for ($i=0; $i<count($arr_dest); $i++) {
	    if ($arr_dest[$i][2] == $int_type) {
	      $int_retval = $i;
	      break;
	    }
	  }
	  return $int_retval;
	}

	$qry_storeroom = new Query("
		SELECT is_cash_taxed, is_account_taxed
		FROM stock_storeroom
		WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")
	");
	$is_cash_taxed = 'Y';
	$is_account_taxed = 'Y';
	if ($qry_storeroom->RowCount() > 0) {
		$is_cash_taxed = $qry_storeroom->FieldByName('is_cash_taxed');
		$is_account_taxed = $qry_storeroom->FieldByName('is_account_taxed');
	}

	// get all taxes that are not "surcharge"
	$sql = "
		SELECT std.*
		FROM ".Monthalize('stock_tax_links')." stl
		INNER JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = stl.tax_id)
		INNER JOIN ".Monthalize('stock_tax_definition')." std ON (std.definition_id = stl.tax_definition_id)
		WHERE (std.definition_type <> 2) AND (st.is_active='Y')
		ORDER BY definition_type, definition_percent
	";	
	$qry_tax_headers = new Query($sql);
	$array_header = array();
	$array_taxes = array();
	
	//==========================================================================
	// the last entry in the array_header array, is for the "Salestax" statement
    // the only the "sales" totals should appear, not the "tax" totals
    //--------------------------------------------------------------------------
    define('COLUMN_ANY', 0);
	define('COLUMN_SALES', 1);
	define('COLUMN_TAX', 2);

	if ($qry_tax_headers->RowCount() > 0) {

//		$arr_tmp[] = 0;
//		$arr_tmp[] = 'Date';
//		$array_header[] = $arr_tmp;

		$array_header[] = array(0=>"D",1=>"Date ",2=>0,3=>0,4=>COLUMN_ANY); //$arr_tmp;

		for ($i=0; $i<$qry_tax_headers->RowCount(); $i++) {
			unset($arr_tmp);
			$arr_tmp[] = $qry_tax_headers->FieldByName('definition_id');
			$arr_tmp[] = "Sales<br>".$qry_tax_headers->FieldByName('definition_description');
			$arr_tmp[] = $qry_tax_headers->FieldByName('definition_type');
			$arr_tmp[] = $qry_tax_headers->FieldByName('definition_percent');
			$arr_tmp[] = COLUMN_SALES;
			$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_id');
			$array_taxes[$i][] = "Sales<br>".$qry_tax_headers->FieldByName('definition_description');
			$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_type');
			$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_percent');
			$array_taxes[$i][] = COLUMN_SALES;
			$array_header[] = $arr_tmp;
			
			if ($qry_tax_headers->FieldByName('definition_percent') > 0) {
				unset($arr_tmp);
				$arr_tmp[] = $qry_tax_headers->FieldByName('definition_id');
				$arr_tmp[] = "Tax<br>".$qry_tax_headers->FieldByName('definition_description');
				$arr_tmp[] = $qry_tax_headers->FieldByName('definition_type');
				$arr_tmp[] = $qry_tax_headers->FieldByName('definition_percent');
				$arr_tmp[] = COLUMN_TAX;
				$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_id');
				$array_taxes[$i][] = "Tax<br>".$qry_tax_headers->FieldByName('definition_description');
				$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_type');
				$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_percent');
				$array_taxes[$i][] = COLUMN_TAX;
				$array_header[] = $arr_tmp;
			}
			$qry_tax_headers->Next();
		}
	}
	$int_tax_count = $qry_tax_headers->RowCount();
	
	$array_header[] = array(0=>"S",1=>"Total<br>Sales",2=>0,3=>0,4=>COLUMN_ANY);
	$array_header[] = array(0=>"T",1=>"Total<br>Taxes",2=>0,3=>0,4=>COLUMN_ANY);



	//=======================
	// Surcharge was scrapped when VAT was introduced
	//=======================
	if ($str_calc_tax_first == 'Y') {
		$array_header[] = array(0=>"DS",1=>"Discount",2=>0,3=>0,4=>COLUMN_ANY);
	}
	else {
		// get the "surcharge" taxes
		$qry_tax_headers = new Query("
			SELECT definition_id, definition_description, definition_type
			FROM ".Monthalize('stock_tax_definition')."
			WHERE definition_type = 2
			ORDER BY definition_type, definition_percent
		");
	
		if ($qry_tax_headers->RowCount() > 0) {
			for ($i=0; $i<$qry_tax_headers->RowCount(); $i++) {
				unset($arr_tmp);
				$arr_tmp[] = $qry_tax_headers->FieldByName('definition_id');
				$arr_tmp[] = $qry_tax_headers->FieldByName('definition_description');
				$arr_tmp[] = $qry_tax_headers->FieldByName('definition_type');
				$arr_tmp[] = $qry_tax_headers->FieldByName('definition_percent');
				$arr_tmp[] = COLUMN_ANY;
				$array_header[] = $arr_tmp;
				$qry_tax_headers->Next();
			}
		}
	}
	
	$array_header[] = array(0=>"GT",1=>"Grand<br>Total",2=>0,3=>0,4=>COLUMN_ANY);


//print_r($array_header);



	// dummy queries
	$qry_bills = new Query("SELECT * FROM ".Monthalize('bill'));
	$qry_items = new Query("SELECT * FROM ".Monthalize('bill_items'));
 
	$arr_bill_numbers = array();
	$arr_taxes = array();
	

	for ($cur_day=$int_start_day; $cur_day<=$int_num_days; $cur_day++) {
		
		if ($int_cur_type == "ALL") {
			$str_bills = "
			SELECT *
			FROM ".Monthalize('bill')." b
			WHERE (DATE(date_created) = '".sprintf("%04d-%02d-%02d", $_SESSION["int_year_loaded"], $_SESSION["int_month_loaded"], $cur_day)."')
				AND (
					(bill_status = ".BILL_STATUS_RESOLVED.")
					OR (bill_status = ".BILL_STATUS_DISPATCHED.")
					OR (bill_status = ".BILL_STATUS_DELIVERED.")
				)
				AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
			ORDER BY date_created";
		}
		else if ($int_cur_type == 'ORDERS') {
			$str_bills = "
				SELECT *
				FROM ".Monthalize('bill')."
				WHERE (DATE(date_created) = '".sprintf("%04d-%02d-%02d", $_SESSION["int_year_loaded"], $_SESSION["int_month_loaded"], $cur_day)."')
					AND (module_id = 7)
				AND (
					(bill_status = ".BILL_STATUS_RESOLVED.")
					OR (bill_status = ".BILL_STATUS_DISPATCHED.")
					OR (bill_status = ".BILL_STATUS_DELIVERED.")
				)
					AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
				ORDER BY bill_number";
		}
		else {
			$str_bills = "
			SELECT *
			FROM ".Monthalize('bill')."
			WHERE (payment_type = ".$int_cur_type.")
				AND (DATE(date_created) = '".sprintf("%04d-%02d-%02d", $_SESSION["int_year_loaded"], $_SESSION["int_month_loaded"], $cur_day)."')
				AND (
					(bill_status = ".BILL_STATUS_RESOLVED.")
					OR (bill_status = ".BILL_STATUS_DISPATCHED.")
					OR (bill_status = ".BILL_STATUS_DELIVERED.")
				)
				AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
			ORDER BY date_created";
		}
		$qry_bills->Query($str_bills);

		$arr_taxes[$cur_day][0] = sprintf("%02d-%02d-%04d", $cur_day, $_SESSION["int_month_loaded"], $_SESSION["int_year_loaded"]);
		$tax_total = 0;

		for ($i=0; $i<$qry_bills->RowCount(); $i++) {

			$qry_items->Query("
				SELECT * 
				FROM ".Monthalize('bill_items')."
				WHERE (bill_id = ".$qry_bills->FieldByName('bill_id').")
			");

			$sql = "
				SELECT o.is_billable, b.bill_number
				FROM ".Monthalize('bill')." b 
				INNER JOIN ".Monthalize('orders')." o ON (b.module_record_id = o.order_id)
				WHERE b.bill_id = ".$qry_bills->FieldByName('bill_id')."
					AND (b.payment_type = ".$int_cur_type.")
					AND (b.storeroom_id = ".$_SESSION["int_current_storeroom"].")
			"; 
			$qry_order = new Query($sql);
			$order_is_billable = 'Y';
			if ($qry_order) {			
				if (($qry_order->FieldByName('is_billable')!==null) || (!empty($qry_order->FieldByName('is_billable'))))
					$order_is_billable = $qry_order->FieldByName('is_billable');
			}
/*
			if ($int_cur_type == 2)  // ACCOUNT
			{
				$sql = "
					SELECT o.is_billable
					FROM ".Monthalize('bill')." b 
					INNER JOIN ".Monthalize('orders')." o ON (b.module_record_id = o.order_id)
					WHERE b.bill_id = ".$qry_bills->FieldByName('bill_id')."
				";
				$qry_billable = new Query($sql);

				if ($qry_billable)
					echo $qry_billable->FieldByName('is_billable')."<br>";
				else
					echo "not found<br>";

			}
*/
			
			$cur_bill_total = 0;
			$cur_tax_total = 0;
			
			for ($j=0; $j<$qry_items->RowCount(); $j++) {
				
				$flt_quantity = number_format($qry_items->FieldByName('quantity') + $qry_items->FieldByName('adjusted_quantity'), 3, '.', '');
				$tmp_price = $qry_items->FieldByName('price');
				$tmp_discount = $qry_items->FieldByName('discount');
				$flt_discount = 0;
				$flt_transfer_tax = 0;

				if ($tmp_discount > 0) {
					if ($str_calc_tax_first == 'Y') {
						$tmp_taxes = getTaxBreakdown($tmp_price * $flt_quantity, $qry_items->FieldByName('tax_id'));
						$calc_price = round($tmp_price + calculateTax($tmp_price, $qry_items->FieldByName('tax_id')),3);
						$flt_discount = round(($flt_quantity * $calc_price) * ($tmp_discount/100),3);
						$calc_price = $tmp_price;
					}
					else {
						$calc_price = round(($tmp_price * (1 - ($tmp_discount/100))), 3);
						$tmp_taxes = getTaxBreakdown($calc_price * $flt_quantity, $qry_items->FieldByName('tax_id'));
					}
				}
				else {
					$calc_price = $tmp_price;
					$tmp_taxes = getTaxBreakdown($calc_price * $flt_quantity, $qry_items->FieldByName('tax_id'));
				}
				
				if ($qry_bills->FieldByName('is_debit_bill') == 'Y')
					$tmp_amount = ($calc_price * $flt_quantity) * -1;
				else
					$tmp_amount = ($calc_price * $flt_quantity);
				
				
					// add quantity under the right column for each returned value and tax id
					for ($k=0; $k<count($tmp_taxes); $k+=2) {
						$int_col_num = getColumn($array_header, $tmp_taxes[$k]);
						if ($qry_bills->FieldByName('is_debit_bill') == 'Y')
							$tmp_taxes[$k+1] = $tmp_taxes[$k+1] * -1;

						if ($int_col_num > -1) {
						if (($qry_bills->FieldByName('payment_type') == BILL_CASH) 
							|| ($qry_bills->FieldByName('payment_type') == BILL_CREDIT_CARD)
							|| ($qry_bills->FieldByName('payment_type') == BILL_UPI)
						) {
								if ($qry_storeroom->FieldByName('is_cash_taxed') == 'Y')
								    if ($array_header[$int_col_num][2] == 2)
									$flt_temp = round($tmp_taxes[$k+1], 2);
								    else
									$flt_temp = $tmp_amount;
								else
									$flt_temp = 0;
							}
							else if (($qry_bills->FieldByName('payment_type') == BILL_ACCOUNT) 
								|| ($qry_bills->FieldByName('payment_type') == BILL_PT_ACCOUNT) || ($qry_bills->FieldByName('payment_type') == BILL_AUROCARD)
								|| ($qry_bills->FieldByName('payment_type') == BILL_TRANSFER_GOOD)
							) {
								if ($qry_storeroom->FieldByName('is_account_taxed') == 'Y')
								    if ($array_header[$int_col_num][2] == 2)
									$flt_temp = round($tmp_taxes[$k+1],2);
								    else
									$flt_temp = $tmp_amount;
								else
									$flt_temp = 0;
							}
	
							if (!empty($arr_taxes[$cur_day][$int_col_num])) {
								$arr_taxes[$cur_day][$int_col_num] = $arr_taxes[$cur_day][$int_col_num] + $flt_temp;
								$int_type = getDefinitionPercent($array_header, $int_col_num);
								if ($int_type > 0)
									$arr_taxes[$cur_day][$int_col_num+1] = $arr_taxes[$cur_day][$int_col_num+1] + round($tmp_taxes[$k+1],2);
							}
							else {
								$arr_taxes[$cur_day][$int_col_num] = $flt_temp;
								$int_type = getDefinitionPercent($array_header, $int_col_num);
								if ($int_type > 0)
									$arr_taxes[$cur_day][$int_col_num+1] = round($tmp_taxes[$k+1],2);
							}
							
							if ($array_header[$int_col_num][2] <> 2) {
								if ((($qry_bills->FieldByName('payment_type') == BILL_CASH) || ($qry_bills->FieldByName('payment_type') == BILL_CREDIT_CARD) || ($qry_bills->FieldByName('payment_type') == BILL_UPI)) && ($is_cash_taxed == 'Y'))
									$tax_total += round($tmp_taxes[$k+1], 2);
								else if (($qry_bills->FieldByName('payment_type') == BILL_ACCOUNT) && ($is_account_taxed == 'Y'))
									$tax_total += round($tmp_taxes[$k+1], 2);
								else if (($qry_bills->FieldByName('payment_type') == BILL_AUROCARD) && ($is_account_taxed == 'Y'))
									$tax_total += round($tmp_taxes[$k+1], 2);
								else
									$tax_total += 0;
							}
						}
					}
				
				//====================
				// update the Sales column
				//====================
				$int_col_num = getColumn($array_header, "S");
				$flt_temp_sales = number_format(($calc_price * $flt_quantity), 2,'.','');
				if ($qry_bills->FieldByName('is_debit_bill') == 'Y')
					$flt_temp_sales = $flt_temp_sales * -1;
					
				if (($order_is_billable=='Y') && (count($tmp_taxes)>0))
				{
					if (!empty($arr_taxes[$cur_day][$int_col_num]))
						$arr_taxes[$cur_day][$int_col_num] += $flt_temp_sales;
					else
						$arr_taxes[$cur_day][$int_col_num] = $flt_temp_sales;
					$cur_bill_total += number_format(($calc_price * $flt_quantity), 2,'.','');
				}
						
				//====================
				// update the Discount column
				//====================
				$int_col_num = getColumn($array_header, "DS");
				if (!empty($arr_taxes[$cur_day][$int_col_num]))
					$arr_taxes[$cur_day][$int_col_num] += round($flt_discount, 2);
				else
					$arr_taxes[$cur_day][$int_col_num] = round($flt_discount, 2);

				$qry_items->Next();
			}
			
			$qry_bills->Next();
		}
		
		// update the Taxes column
		$int_col_num = getColumn($array_header, "T");
		if (!empty($arr_taxes[$cur_day][$int_col_num])) {
			$arr_taxes[$cur_day][$int_col_num] += $tax_total;
		}
		else {
			$arr_taxes[$cur_day][$int_col_num] = $tax_total;
		}
	}


      for ($cur_day=$int_start_day; $cur_day<=$int_num_days;$cur_day++) {
	// update the Taxes column
	$int_col_num = getColumn($array_header, "T");
	$flt_tax_total = $arr_taxes[$cur_day][$int_col_num];
	$cur_bill_total = $flt_tax_total;

	$int_col_num = getColumnByType($array_header, 2);
	if ($int_col_num > -1)
		if (!empty($arr_taxes[$cur_day][$int_col_num]))
			$cur_bill_total = $cur_bill_total + $arr_taxes[$cur_day][$int_col_num];
	
	$int_col_num = getColumn($array_header, "S");
	if (!empty($arr_taxes[$cur_day][$int_col_num]))
		$cur_bill_total += $arr_taxes[$cur_day][$int_col_num];

	$int_col_num = getColumn($array_header, "DS");
	if (!empty($arr_taxes[$cur_day][$int_col_num]))
		$cur_bill_total -= $arr_taxes[$cur_day][$int_col_num];
	
	// update the Total column
	$int_col_num = getColumn($array_header, "GT");
	$arr_taxes[$cur_day][$int_col_num] = ($cur_bill_total); // RoundUp($cur_bill_total);
      }
?>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../include/styles.css" />
    </head>
<body id='body_bgcolor'>
<form name="MonthlySalesRegister" method="GET">
  <font style="font-family:Verdana,sans-serif;">

	<?

	// get the totals and add them to the array
	$arr_totals = array();
	for ($i=1;$i<count($array_header);$i++)
		$arr_totals[$i] = 0;

	for ($i=$int_start_day;$i<=$int_num_days;$i++) {
		for ($j=1;$j<count($array_header);$j++) {
			if (!empty($arr_taxes[$i][$j]))
				$arr_totals[$j] += number_format($arr_taxes[$i][$j],2,'.','');
		}
	}
	
	if ($str_selected_view == 'totals') { ?>
	
		<table border=1 cellpadding=7 cellspacing=0>
			<tr bgcolor='lightgrey'>
			<? 
				// print the header row
				for ($i=0; $i<count($array_header); $i++) {
					echo "<td align='center' valign='center' width='80px' class='normaltext'><b>".$array_header[$i][1]."</b></td>";
				}
			?>
			</tr>
			<?
				// print the rows
//				for ($i=1; $i<=count($arr_taxes); $i++) {
				for ($i=$int_start_day; $i<=$int_num_days; $i++) {
					echo "<tr class='normaltext'>";
					for ($j=0; $j<count($array_header); $j++) {
						if (!empty($arr_taxes[$i][$j]))
						 if ($j==0)
							echo "<td align=\"right\">".$arr_taxes[$i][$j]."</td>";
						 else
							echo "<td align=\"right\">".number_format($arr_taxes[$i][$j],2,'.',',')."</td>";
						else
							echo "<td align=\"right\"> 0.00 </td>";
					}
					echo "</tr>";
				}
				
				// print the totals
				echo "<tr bgcolor='lightgrey' class='normaltext'><td align='right'><b>TOTALS</b></td>";
				for ($i=1;$i<=count($arr_totals);$i++) {
					echo "<td align=\"right\"><b>".number_format($arr_totals[$i],2,'.',',')."</b></td>";
				}
				echo "</tr>";
			?>
	
		</table>
	
	<? } else { ?>

		<table border=1 cellpadding=7 cellspacing=0>
			<tr bgcolor='lightgrey' class='normaltext'>
				<td>&nbsp;</td>
				<td><b>Tax Collected</b></td>
				<td><b>Turnover</b></td>
			</tr>
			<?
				$flt_collected_total = 0;
				$flt_tax_total = 0;

				for ($i=1; $i<count($array_header); $i++) {

					if (($array_header[$i][2] <> 0) && ($array_header[$i][4] <> COLUMN_TAX)) {

						echo "<tr class='normaltext'>";
						echo "<td><b>".$array_header[$i][1]."</b></td>";
						
						if ($array_header[$i][2] == 2) { // surcharge
						  $flt_tax_collected = $arr_totals[$i];
						  echo "<td align='right'>".number_format($flt_tax_collected, 2, '.',',')."</td>";
						  echo "<td align='right'>0.00</td>";
						}
						else {
						  $flt_tax_collected = RoundUp($arr_totals[$i] * $array_header[$i][3] / 100);
						  echo "<td align='right'>".number_format($flt_tax_collected, 2, '.',',')."</td>";
						  echo "<td align='right'>".number_format($arr_totals[$i], 2, '.', ',')."</td>";
						}
						echo "</tr>";
						
						$flt_collected_total += $flt_tax_collected;
						if ($array_header[$i][2] <> 2)
						  $flt_tax_total += $arr_totals[$i];

					}

				}

				echo "<tr bgcolor='lightgrey' class='normaltext'>";
				echo "<td><b>Totals</b></td>";
				echo "<td align='right'>".number_format($flt_collected_total, 2, '.', ',')."</td>";
				echo "<td align='right'>".number_format($flt_tax_total, 2, '.', ',')."</td>";
				echo "</tr>";
			?>
		</table>
	
	<? } ?>
	</font>
</form>
</body>
</html>
