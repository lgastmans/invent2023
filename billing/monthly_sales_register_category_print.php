<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/tax.php");
	require_once("../include/browser_detection.php");
	require_once("../common/printer.inc.php");
	
	$sql_settings = new Query("
		SELECT *
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION["int_current_storeroom"]."
	");
	$int_eject_lines = 12;
	if ($sql_settings->RowCount() > 0) {
		$int_eject_lines = $sql_settings->FieldByName('bill_print_lines_to_eject');
		$str_print_address = $sql_settings->FieldByName('bill_print_address');
		$str_print_phone = $sql_settings->FieldByName('bill_print_phone');
		$str_calc_tax_first = $sql_settings->FieldByName('calculate_tax_before_discount');
		$int_transfer_tax = $sql_settings->FieldByName('bill_transfer_tax');
	}

	$sql_settings = new Query("
		SELECT *
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION['int_current_storeroom']."
	");
	if ($sql_settings->RowCount() > 0) {
		$str_calc_tax_first = $sql_settings->FieldByName('calculate_tax_before_discount');
		$int_transfer_tax = $sql_settings->FieldByName('bill_transfer_tax');
	}

	$str_day_clause = '';
	if (IsSet($_GET["selected_day"])) {
		if ($_GET["selected_day"] == 'ALL') {
			$int_start_day = 1;
			$int_num_days = DaysInMonth2($_SESSION['int_month_loaded'], $_SESSION['int_year_loaded']);
		}
		else {
			$int_start_day = $_GET["selected_day"];
			$int_num_days = $_GET["selected_day"];
			$str_day_clause = "AND (DATE(date_created) = '".sprintf("%04d-%02d-%02d", $_SESSION["int_year_loaded"], $_SESSION["int_month_loaded"], $int_start_day)."')";
		}
	}
	
		
	if (IsSet($_GET["selected_type"]))
		$int_cur_type = $_GET["selected_type"];
	else
		$int_cur_type = 1;
		
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
	
	// get all the categories
	$qry_categories = new Query("
		SELECT *
		FROM stock_category
		WHERE parent_category_id = 0
		ORDER BY category_description
	");

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
		$array_header[] = array(0=>"D",1=>"Category ",2=>0,3=>0,4=>COLUMN_ANY); //$arr_tmp;
		for ($i=0; $i<$qry_tax_headers->RowCount(); $i++) {
			unset($arr_tmp);
			$arr_tmp[] = $qry_tax_headers->FieldByName('definition_id');
			$arr_tmp[] = "Sales ".$qry_tax_headers->FieldByName('definition_description');
			$arr_tmp[] = $qry_tax_headers->FieldByName('definition_type');
			$arr_tmp[] = $qry_tax_headers->FieldByName('definition_percent');
			$arr_tmp[] = COLUMN_SALES;
			$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_id');
			$array_taxes[$i][] = "Sales ".$qry_tax_headers->FieldByName('definition_description');
			$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_type');
			$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_percent');
			$array_taxes[$i][] = COLUMN_SALES;
			$array_header[] = $arr_tmp;
			
			if ($qry_tax_headers->FieldByName('definition_percent') > 0) {
				unset($arr_tmp);
				$arr_tmp[] = $qry_tax_headers->FieldByName('definition_id');
				$arr_tmp[] = "Tax ".$qry_tax_headers->FieldByName('definition_description');
				$arr_tmp[] = $qry_tax_headers->FieldByName('definition_type');
				$arr_tmp[] = $qry_tax_headers->FieldByName('definition_percent');
				$arr_tmp[] = COLUMN_TAX;
				$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_id');
				$array_taxes[$i][] = "Tax ".$qry_tax_headers->FieldByName('definition_description');
				$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_type');
				$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_percent');
				$array_taxes[$i][] = COLUMN_TAX;
				$array_header[] = $arr_tmp;
			}
			$qry_tax_headers->Next();
		}
	}
	$int_tax_count = $qry_tax_headers->RowCount();
	
	$array_header[] = array(0=>"S",1=>"Sales",2=>0,3=>0,4=>COLUMN_ANY);
	$array_header[] = array(0=>"T",1=>"Taxes",2=>0,3=>0,4=>COLUMN_ANY);

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
	
	$array_header[] = array(0=>"GT",1=>"Total",2=>0,3=>0,4=>COLUMN_ANY);
	
	// dummy queries
	$qry_bills = new Query("SELECT * FROM ".Monthalize('bill'));
//	$qry_bills = new Query("SELECT * FROM ".Monthalize('bill_items'));
 
	$arr_bill_numbers = array();
	$arr_taxes = array();
	
	// in the following loop, cur_day does NOT refer to the date
	// this code was copied from monthly_sales_registry.php
	// cur_day was kept as it is used in many places in the loop
	$qry_categories->First();
	for ($cur_day=0; $cur_day<$qry_categories->RowCount(); $cur_day++) {
		if ($int_cur_type == "ALL") {
			$str_bills = "
				SELECT *, bi.discount AS discount, bi.tax_id AS tax_id, sp.tax_id AS stock_tax_id
				FROM ".Monthalize('bill_items')." bi
				INNER JOIN ".Monthalize('bill')." b ON (b.bill_id = bi.bill_id)
					AND (
						(bill_status = ".BILL_STATUS_RESOLVED.")
						OR (bill_status = ".BILL_STATUS_DISPATCHED.")
						OR (bill_status = ".BILL_STATUS_DELIVERED.")
					)
					AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
					$str_day_clause
				INNER JOIN stock_product sp ON (sp.product_id = bi.product_id)
				WHERE (sp.category_id = ".$qry_categories->FieldByName('category_id').")
				ORDER BY date_created";
		}
		else {
			$str_bills = "
				SELECT *, bi.discount AS discount, bi.tax_id AS tax_id, sp.tax_id AS stock_tax_id
				FROM ".Monthalize('bill_items')." bi
				INNER JOIN ".Monthalize('bill')." b ON (b.bill_id = bi.bill_id)
					AND (
						(bill_status = ".BILL_STATUS_RESOLVED.")
						OR (bill_status = ".BILL_STATUS_DISPATCHED.")
						OR (bill_status = ".BILL_STATUS_DELIVERED.")
					)
					AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
					AND (payment_type = ".$int_cur_type.")
					$str_day_clause
				INNER JOIN stock_product sp ON (sp.product_id = bi.product_id)
				WHERE (sp.category_id = ".$qry_categories->FieldByName('category_id').")
				ORDER BY date_created";
		}
		
		$qry_bills->Query($str_bills);
		
		$arr_taxes[$cur_day][0] = $qry_categories->FieldByName('category_description');
		
		$tax_total = 0;
		for ($i=0; $i<$qry_bills->RowCount(); $i++) {
			$cur_bill_total = 0;
			$cur_tax_total = 0;
			
//			for ($j=0; $j<$qry_bills->RowCount(); $j++) {
				
				$flt_quantity = number_format($qry_bills->FieldByName('quantity') + $qry_bills->FieldByName('adjusted_quantity'), 3, '.', '');
				$tmp_price = $qry_bills->FieldByName('price');
				$tmp_discount = $qry_bills->FieldByName('discount');
				$flt_discount = 0;
				$flt_transfer_tax = 0;
				
				if ($tmp_discount > 0) {
					if ($str_calc_tax_first == 'Y') {
						$tmp_taxes = getTaxBreakdown($tmp_price * $flt_quantity, $qry_bills->FieldByName('tax_id'));
						$calc_price = round($tmp_price + calculateTax($tmp_price, $qry_bills->FieldByName('tax_id')),3);
						$flt_discount = round(($flt_quantity * $calc_price) * ($tmp_discount/100),3);
						$calc_price = $tmp_price;
					}
					else {
						$calc_price = round(($tmp_price * (1 - ($tmp_discount/100))), 3);
						$tmp_taxes = getTaxBreakdown($calc_price * $flt_quantity, $qry_bills->FieldByName('tax_id'));
					}
				}
				else {
					$calc_price = $tmp_price;
					$tmp_taxes = getTaxBreakdown($calc_price * $flt_quantity, $qry_bills->FieldByName('tax_id'));
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
							if (($qry_bills->FieldByName('payment_type') == BILL_CASH) || ($qry_bills->FieldByName('payment_type') == BILL_CREDIT_CARD)) {
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
								if ((($qry_bills->FieldByName('payment_type') == BILL_CASH) || ($qry_bills->FieldByName('payment_type') == BILL_CREDIT_CARD)) && ($is_cash_taxed == 'Y'))
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
					
				if (!empty($arr_taxes[$cur_day][$int_col_num]))
					$arr_taxes[$cur_day][$int_col_num] += $flt_temp_sales;
				else
					$arr_taxes[$cur_day][$int_col_num] = $flt_temp_sales;
				$cur_bill_total += number_format(($calc_price * $flt_quantity), 2,'.','');
						
				//====================
				// update the Discount column
				//====================
				$int_col_num = getColumn($array_header, "DS");
				if (!empty($arr_taxes[$cur_day][$int_col_num]))
					$arr_taxes[$cur_day][$int_col_num] += round($flt_discount, 2);
				else
					$arr_taxes[$cur_day][$int_col_num] = round($flt_discount, 2);
				
//				$qry_bills->Next();
//			}
			
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
		
		$qry_categories->Next();
	}
	
	for ($cur_day=0; $cur_day<$qry_categories->RowCount();$cur_day++) {
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
<head><TITLE>Printing Statement</TITLE>
<link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>

<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0">

<?
//
// GET THE TOTALS
//

$arr_totals = array();
for ($i=1;$i<count($array_header);$i++)
	$arr_totals[$i] = 0;

//for ($i=1;$i<=count($arr_taxes);$i++) {
for ($i=0; $i<$qry_categories->RowCount(); $i++) {
	for ($j=1;$j<count($array_header);$j++) {
		if (!empty($arr_taxes[$i][$j]))
			$arr_totals[$j] += round($arr_taxes[$i][$j],2);
	}
}

/*
	The HSN column need not be totalled
*/
$arr_totals[1] = '';

//==============================================================================
// TOTALS STATEMENT
//------------------------------------------------------------------------------

if ($int_cur_type == 'ALL')
	$str_type = "All";
else if ($int_cur_type == BILL_CASH)
	$str_type = "Cash";
else if ($int_cur_type == BILL_ACCOUNT)
	$str_type = "FS Account";
else if ($int_cur_type == BILL_PT_ACCOUNT)
	$str_type = "PT Account";
else if ($int_cur_type == BILL_TRANSFER_GOOD)
	$str_type = "Transfer of Goods";

$str_title = "Monthly Sales Register of ".$str_type." bills for ".getMonthName($_SESSION["int_month_loaded"])." ".$_SESSION["int_year_loaded"];

$str_top = "";
$str_top = PadWithCharacter($str_top, '=', count($array_header)*11);

$str_bottom = "";
$str_bottom = PadWithCharacter($str_bottom, '-', count($array_header)*11);


$str_header = "";
for ($i=0; $i<count($array_header); $i++) {
  $str_header .= StuffWithCharacter($array_header[$i][1], ' ', 11);
}

// print the rows
$str_data = "";
//for ($i=1; $i<=count($arr_taxes); $i++) {
for ($i=0; $i<$qry_categories->RowCount(); $i++) {
  for ($j=0; $j<count($array_header); $j++) {
    if (!empty($arr_taxes[$i][$j]))
      if ($j==0)
        $str_data .= StuffWithCharacter($arr_taxes[$i][$j], ' ', 11);
      else
        $str_data .= StuffWithCharacter(sprintf("%01.2f", $arr_taxes[$i][$j]), ' ', 11);
    else
      $str_data .= StuffWithCharacter("0.00", ' ', 11);
  }
  $str_data .= "\n";
}

$str_totals = "";
$str_totals = PadWithCharacter($str_totals, ' ', 11);
for ($i=1;$i<=count($arr_totals);$i++) {
  $str_totals .= StuffWithCharacter(sprintf("%01.2f", $arr_totals[$i]), ' ', 11);
}

$str_eject_lines = "";
for ($i=0;$i<$int_eject_lines;$i++) {
  $str_eject_lines .= "\n"; 
}

$str_statement = "%c
".$str_application_title."
".$str_print_address."
".$str_print_phone."

".$str_title."
".$str_top."
".$str_header."
".$str_bottom."
".$str_data."
".$str_bottom."
".$str_totals."
".$str_top.$str_eject_lines."%n";

$str_statement = replaceSpecialCharacters($str_statement);
?>

<PRE>
<?
	echo $str_statement;
?>
</PRE>


<form name="printerForm" method="POST" action="http://localhost/print.php">

<table width="100%" bgcolor="#E0E0E0">
  <tr>
    <td height=45 class="headerText" bgcolor="#808080">
      &nbsp;<font class='title'>Printing</font>
    </td>
  </tr>
  <tr>
    <td>
      <br>
      <input type="hidden" name="data" value="<? echo ($str_statement); ?>"><br>
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

<script language="JavaScript">
	printerForm.submit();
</script>

</body>
</html>
