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
			$arr_tmp[] = "Sales ".$qry_tax_headers->FieldByName('definition_description');
			$arr_tmp[] = $qry_tax_headers->FieldByName('definition_type');
			$arr_tmp[] = $qry_tax_headers->FieldByName('definition_percent');
			$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_id');
			$array_taxes[$i][] = "Sales ".$qry_tax_headers->FieldByName('definition_description');
			$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_type');
			$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_percent');
			$array_header[] = $arr_tmp;
			
			if ($qry_tax_headers->FieldByName('definition_percent') > 0) {
				unset($arr_tmp);
				$arr_tmp[] = $qry_tax_headers->FieldByName('definition_id');
				$arr_tmp[] = "Tax ".$qry_tax_headers->FieldByName('definition_description');
				$arr_tmp[] = $qry_tax_headers->FieldByName('definition_type');
				$arr_tmp[] = $qry_tax_headers->FieldByName('definition_percent');
				$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_id');
				$array_taxes[$i][] = "Tax ".$qry_tax_headers->FieldByName('definition_description');
				$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_type');
				$array_taxes[$i][] = $qry_tax_headers->FieldByName('definition_percent');
				$array_header[] = $arr_tmp;
			}
			$qry_tax_headers->Next();
		}
	}
	$int_tax_count = $qry_tax_headers->RowCount();
	
	$array_header[] = array(0=>"S",1=>"Sales",2=>0);
	$array_header[] = array(0=>"T",1=>"Taxes",2=>0);

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
	
	$array_header[] = array(0=>"GT",1=>"Total",2=>0);

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
<head><TITLE>Printing Statement</TITLE>
<link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>

<? if (browser_detection( 'os' ) === 'lin') { ?>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0">
<? } else { ?>
<body leftmargin=0 topmargin=0 marginwidth=0 marginheight=0 bgcolor="#E0E0E0" onload="CheckTC()">
<? } ?>

<?
//
// GET THE TOTALS
//

$arr_totals = array();
for ($i=1;$i<count($array_header);$i++)
	$arr_totals[$i] = 0;

//for ($i=1;$i<=count($arr_taxes);$i++) {
for ($i=$int_start_day; $i<=$int_num_days; $i++) {
	for ($j=1;$j<count($array_header);$j++) {
		if (!empty($arr_taxes[$i][$j]))
			$arr_totals[$j] += round($arr_taxes[$i][$j],2);
	}
}


$str_title = "Monthly Purchase Sales Register for ".getMonthName($_SESSION["int_month_loaded"])." ".$_SESSION["int_year_loaded"];

$str_top = "";
$str_top = PadWithCharacter($str_top, '=', count($array_header)*10);

$str_bottom = "";
$str_bottom = PadWithCharacter($str_bottom, '-', count($array_header)*10);


$str_header = "";
for ($i=0; $i<count($array_header); $i++) {
  $str_header .= StuffWithCharacter($array_header[$i][1], ' ', 10);
}

// print the rows
$str_data = "";
//for ($i=1; $i<=count($arr_taxes); $i++) {
for ($i=$int_start_day; $i<=$int_num_days; $i++) {
  for ($j=0; $j<count($array_header); $j++) {
    if (!empty($arr_taxes[$i][$j]))
      if ($j==0)
        $str_data .= StuffWithCharacter($arr_taxes[$i][$j], ' ', 10);
      else
        $str_data .= StuffWithCharacter(sprintf("%01.2f", $arr_taxes[$i][$j]), ' ', 10);
    else
      $str_data .= StuffWithCharacter("0.00", ' ', 10);
  }
  $str_data .= "\n";
}

$str_totals = "";
$str_totals = PadWithCharacter($str_totals, ' ', 10);
for ($i=1;$i<=count($arr_totals);$i++) {
  $str_totals .= StuffWithCharacter(sprintf("%01.2f", $arr_totals[$i]), ' ', 10);
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


<? if (browser_detection("os") === "lin") { ?>
<form name="printerForm" method="POST" action="http://localhost/print.php">
<? } else { ?>
<form name="printerForm" onsubmit="return false;">
<? } ?>

<table width="100%" bgcolor="#E0E0E0">
  <tr>
    <td height=45 class="headerText" bgcolor="#808080">
      &nbsp;<font class='title'>Printing</font>
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
