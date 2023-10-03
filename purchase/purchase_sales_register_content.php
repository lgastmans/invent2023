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
	
	$_SESSION["int_purchase_menu_selected"] = 8;

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

	function getDefinitionType($arr_dest, $int_pos) {
		$int_retval = -1;
		if (IsSet($arr_dest[$int_pos][2]))
			$int_retval = $arr_dest[$int_pos][2];
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
	$qry_tax_headers = new Query("
		SELECT *
		FROM ".Monthalize('stock_tax_definition')."
		WHERE definition_type <> 2
		ORDER BY definition_type, definition_percent
	");
	$array_header = array();
	$array_taxes = array();
	if ($qry_tax_headers->RowCount() > 0) {
//		$arr_tmp[] = 0;
//		$arr_tmp[] = 'Date';
//		$array_header[] = $arr_tmp;
		$array_header[] = array(0=>"D",1=>"Date ",2=>0); //$arr_tmp;
		for ($i=0; $i<$qry_tax_headers->RowCount(); $i++) {
			unset($arr_tmp);
			$arr_tmp[] = $qry_tax_headers->FieldByName('definition_id');
			$arr_tmp[] = "Sales<br>".$qry_tax_headers->FieldByName('definition_description');
			$arr_tmp[] = $qry_tax_headers->FieldByName('definition_type');
			$arr_tmp[] = $qry_tax_headers->FieldByName('definition_percent');
			$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_id');
			$array_taxes[$i][] = "Sales<br>".$qry_tax_headers->FieldByName('definition_description');
			$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_type');
			$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_percent');
			$array_header[] = $arr_tmp;
			
			if ($qry_tax_headers->FieldByName('definition_percent') > 0) {
				unset($arr_tmp);
				$arr_tmp[] = $qry_tax_headers->FieldByName('definition_id');
				$arr_tmp[] = "Tax<br>".$qry_tax_headers->FieldByName('definition_description');
				$arr_tmp[] = $qry_tax_headers->FieldByName('definition_type');
				$arr_tmp[] = $qry_tax_headers->FieldByName('definition_percent');
				$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_id');
				$array_taxes[$i][] = "Tax<br>".$qry_tax_headers->FieldByName('definition_description');
				$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_type');
				$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_percent');
				$array_header[] = $arr_tmp;
			}
			$qry_tax_headers->Next();
		}
	}
	$int_tax_count = $qry_tax_headers->RowCount();
	
	$array_header[] = array(0=>"S",1=>"Total<br>Sales",2=>0);
	$array_header[] = array(0=>"T",1=>"Total<br>Taxes",2=>0);

	//=======================
	// Surcharge was scrapped when VAT was introduced
	//=======================
	if ($str_calc_tax_first == 'Y') {
//		$array_header[] = array(0=>"DS",1=>"Discount",2=>0);
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
				$array_header[] = $arr_tmp;
				$qry_tax_headers->Next();
			}
		}
	}
	
	$array_header[] = array(0=>"GT",1=>"Grand<br>Total",2=>0);

	// dummy queries
	$qry_bills = new Query("SELECT * FROM stock_product");
	$qry_items = new Query("SELECT * FROM stock_product");
 
	$arr_bill_numbers = array();
	$arr_taxes = array();
	
	for ($cur_day=$int_start_day; $cur_day<=$int_num_days; $cur_day++) {
		
		$str_bills = "
		      SELECT *
		      FROM ".Yearalize('purchase_order')."
		      WHERE (DATE(date_received) = '".sprintf("%04d-%02d-%02d", $_SESSION["int_year_loaded"], $_SESSION["int_month_loaded"], $cur_day)."')
			      AND ((purchase_status = ".PURCHASE_RECEIVED.") OR (purchase_status = ".PURCHASE_SENT."))
			      AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
		      ORDER BY date_received";
		$qry_bills->Query($str_bills);

		$arr_taxes[$cur_day][0] = sprintf("%02d-%02d-%04d", $cur_day, $_SESSION["int_month_loaded"], $_SESSION["int_year_loaded"]);
		$tax_total = 0;
		for ($i=0; $i<$qry_bills->RowCount(); $i++) {
			$qry_items->Query("
				SELECT * 
				FROM ".Yearalize('purchase_items')."
				WHERE (purchase_order_id = ".$qry_bills->FieldByName('purchase_order_id').")
					AND (is_received = 'Y')
			");
			
			$cur_bill_total = 0;
			$cur_tax_total = 0;
			
			for ($j=0; $j<$qry_items->RowCount(); $j++) {
				
				$flt_quantity = number_format($qry_items->FieldByName('quantity_received') + $qry_items->FieldByName('quantity_bonus'), 3, '.', '');
				$tmp_price = $qry_items->FieldByName('buying_price');
				
				$calc_price = $tmp_price;
				$tmp_taxes = getTaxBreakdown($calc_price * $flt_quantity, $qry_items->FieldByName('tax_id'));
				$tmp_amount = ($calc_price * $flt_quantity);
				
				// add quantity under the right column for each returned value and tax id
				for ($k=0; $k<count($tmp_taxes); $k+=2) {
					$int_col_num = getColumn($array_header, $tmp_taxes[$k]);
					if ($int_col_num > -1) {
						if ($array_header[$int_col_num][2] == 2)
						    $flt_temp = round($tmp_taxes[$k+1], 2);
						else
						    $flt_temp = $tmp_amount;

						if (!empty($arr_taxes[$cur_day][$int_col_num])) {
							$arr_taxes[$cur_day][$int_col_num] = $arr_taxes[$cur_day][$int_col_num] + $flt_temp;
							$int_type = getDefinitionType($array_header, $int_col_num+1);
							if ($int_type > 0)
								$arr_taxes[$cur_day][$int_col_num+1] = $arr_taxes[$cur_day][$int_col_num+1] + round($tmp_taxes[$k+1],2);
						}
						else {
							$arr_taxes[$cur_day][$int_col_num] = $flt_temp;
							$int_type = getDefinitionType($array_header, $int_col_num+1);
							if ($int_type > 0)
								$arr_taxes[$cur_day][$int_col_num+1] = round($tmp_taxes[$k+1],2);
						}
						
						if ($array_header[$int_col_num][2] <> 2) {
							$tax_total += round($tmp_taxes[$k+1], 2);
						}
					}
				}
				
				//====================
				// update the Sales column
				//====================
				$int_col_num = getColumn($array_header, "S");
				$flt_temp_sales = number_format(($calc_price * $flt_quantity), 2,'.','');
				
				if (!empty($arr_taxes[$cur_day][$int_col_num]))
					$arr_taxes[$cur_day][$int_col_num] += $flt_temp_sales;
				else
					$arr_taxes[$cur_day][$int_col_num] = $flt_temp_sales;
				$cur_bill_total += number_format(($calc_price * $flt_quantity), 2,'.','');
				
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
?>	

	<table border=1 cellpadding=7 cellspacing=0>
		<tr bgcolor='lightgrey' class='normaltext'>
		<? 
			for ($i=0; $i<count($array_header); $i++) {
				echo "<td align='center' valign='center' width='80px'><b>".$array_header[$i][1]."</b></td>";
			}
		?>
		</tr>
		<?
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
	
	</font>
</form>
</body>
</html>