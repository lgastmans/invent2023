<?
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");
	require_once("../../common/tax.php");

	$int_decimals = 2;
	$str_batches_enabled = 'N';
	
	$sql_settings = new Query("
		SELECT *
		FROM user_settings
	");
	if ($sql_settings->RowCount() > 0) {
		$int_decimals = $sql_settings->FieldByName('bill_decimal_places');
		$str_batches_enabled = $sql_settings->FieldByName('bill_enable_batches');
		$str_calc_tax_first = $sql_settings->FieldByName('calculate_tax_before_discount');
	}
	

	if (IsSet($_GET['action'])) {
		if ($_GET['action'] == "set_type") {
			$_SESSION['current_bill_type'] = $_GET['bill_type'];
			$_SESSION['current_bill_day'] = $_GET['bill_day'];
		}
	}
	
	function remove_product() {
		for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {
			if ($_SESSION["arr_total_qty"][$i][0] == $_SESSION['current_code']) {
				$_SESSION["arr_total_qty"] = array_delete($_SESSION["arr_total_qty"], $i);
				remove_product();
			}
		}
	}
	
?>

<html>
<head>
	<link rel="stylesheet" type="text/css" href="../../include/<?echo $str_css_filename;?>" />
</head>

<body leftmargin=0 topmargin=0 marginwidth=7 marginheight=7> 

<form name="billing_list" method="GET">
	<font class="<?echo $str_class_list_header?>">
	<?
		echo "&nbsp;";
		echo StuffWithBlank('Code', 6)." ";
		if ($str_batches_enabled == 'Y')
			echo StuffWithBlank('Batch', 6)." ";
		echo PadWithBlank('Description', 20)." ";
		echo StuffWithBlank('Qty', 6)." ";
		echo StuffWithBlank('Dsc', 3)." ";
		echo StuffWithBlank('Price', 10)." ";
		echo StuffWithBlank('Tax',7)." ";
		echo StuffWithBlank('Total', 10);
	?>
	</font><br>
	<select name="item_list" size="<?if ($_SESSION['current_bill_type'] == 1) echo "35"; else echo "30";?>" class="<?echo $str_class_list_box?>">

	<?
		if (IsSet($_GET["code"])) {
			// set the discount, if one was entered
			if ($_GET["set_discount"] == "Y") {
				for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {
					if ($_SESSION["arr_total_qty"][$i][0] === $_SESSION['current_code']) //== $_GET["code"])
						$_SESSION["arr_total_qty"][$i][4] = $_GET["discount"];
				}
			}
			else {
				$int_discount = 0;
				// check whether a discount was given before
				for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {
					if ($_SESSION["arr_total_qty"][$i][0] === $_SESSION['current_code']) //== $_GET["code"])
						if ($_SESSION["arr_total_qty"][$i][4] > 0) {
							$int_discount = $_SESSION["arr_total_qty"][$i][4];
							break;
						}
				}
				if ($int_discount > 0) {
					for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {
//						if (($_SESSION["arr_total_qty"][$i][0] == $_GET["code"]) && ($_SESSION["arr_total_qty"][$i][1] == $_GET["batch_code"]))
						if (($_SESSION["arr_total_qty"][$i][0] === $_SESSION['current_code']) && ($_SESSION["arr_total_qty"][$i][1] == $_GET["batch_code"]))
							$_SESSION["arr_total_qty"][$i][4] = $int_discount;
					}
				}
			}
		}
		else
		if (IsSet($_GET["del"])) {
			// remove entries found in the session array arr_total_qty for the given product code
			remove_product();
		}

		//====================
		// get the tax details for the storeroom
		//====================
		$result_set = new Query("
			SELECT is_taxed, is_cash_taxed, is_account_taxed
			FROM stock_storeroom
			WHERE (storeroom_id = ".$_SESSION["int_current_storeroom"].")"
		);
		$is_taxed = 'Y';
		$is_cash_taxed = 'Y';
		$is_account_taxed = 'Y';
		if ($result_set->RowCount() > 0) {
			$is_taxed = $result_set->FieldByName('is_taxed');
			$is_cash_taxed = $result_set->FieldByName('is_cash_taxed');
			$is_account_taxed = $result_set->FieldByName('is_account_taxed');
		}


		$flt_total = 0;
		for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {
			
			$tmp_qty = round($_SESSION["arr_total_qty"][$i][2] + $_SESSION["arr_total_qty"][$i][5], 3);
			$tmp_discount = $_SESSION["arr_total_qty"][$i][4];
			$tmp_price = $_SESSION["arr_total_qty"][$i][6];
			$tmp_tax_id = $_SESSION["arr_total_qty"][$i][7];
			$tmp_is_taxed = $_SESSION["arr_total_qty"][$i][9];
			$tmp_is_decimal = $_SESSION["arr_total_qty"][$i][15];
			$calculate_tax = $tmp_is_taxed;
			
			// calculate the tax and the total cost per item billed
			if ($tmp_is_taxed == 'Y') {
				if ($_SESSION['current_bill_type'] == BILL_CASH) {
					if ($is_cash_taxed == 'Y')
						$calculate_tax = 'Y';
					else
						$calculate_tax = 'N';
				}
				else if (($_SESSION['current_bill_type'] == BILL_ACCOUNT) || ($_SESSION['current_bill_type'] == BILL_PT_ACCOUNT)) {
					if ($is_account_taxed == 'Y')
						$calculate_tax = 'Y';
					else
						$calculate_tax = 'N';
				}
			}
			else
				$calculate_tax = 'N';
				
			if ($_SESSION['current_bill_type'] == BILL_TRANSFER_GOOD)
				$calculate_tax = 'N';
			
			if ($calculate_tax == 'Y') {
				if ($tmp_discount > 0) {
					
					if ($str_calc_tax_first == 'Y') {
						$tax_price = round($tmp_price + calculateTax($tmp_price, $tmp_tax_id),3);
						$tax_amount = calculateTax(($tmp_price * $tmp_qty), $tmp_tax_id);
						$flt_discount = round(($tmp_qty * $tax_price) * ($tmp_discount/100),3);
						$flt_price_total = round(($tmp_qty * $tax_price - $flt_discount), 3);
					}
					else {
						$discount_price = round(($tmp_price * (1 - ($tmp_discount/100))), 3);
						$tax_amount = calculateTax($tmp_qty * $discount_price, $tmp_tax_id);
						$flt_price_total = round(($tmp_qty * $discount_price + $tax_amount), 3);
					}
				}
				else {
					$tax_amount = calculateTax($tmp_price * $tmp_qty, $tmp_tax_id);
					$flt_price_total = round(($tmp_qty * $tmp_price + $tax_amount), 3);
				}
//echo "<script language='javascript'>";
//echo "alert('".$tax_amount."');";
//echo "</script>";
			}
			else {
				$tax_amount = 0;
				if ($tmp_discount > 0) {
					$discount_price = round(($tmp_price * (1 - ($tmp_discount/100))), 3);
					$flt_price_total = round(($tmp_qty * $discount_price), 3);
				}
				else {
					$flt_price_total = round(($tmp_qty * $tmp_price), 3);
				}
			}
			
			// save the total per item 
			$_SESSION["arr_total_qty"][$i][10] = $flt_price_total;
			// and the amount taxed per item
			$_SESSION["arr_total_qty"][$i][11] = $tax_amount;
			
			
			// calculate the bill total
			$flt_total += floatval($_SESSION["arr_total_qty"][$i][10]);
		}
		
		if ($str_batches_enabled == 'Y') {
			$int_item_counter = 0;
			for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {
				$int_discount_amount = intval($_SESSION["arr_total_qty"][$i][4]);
				$tmp_is_decimal = $_SESSION['arr_total_qty'][$i][15];
				if ($int_discount_amount == 0)
					$str_discount = '';
				else
					$str_discount = $int_discount_amount."%";
				
				$flt_total_quantity = $_SESSION["arr_total_qty"][$i][2] + $_SESSION["arr_total_qty"][$i][5];
				if ($tmp_is_decimal == 'Y')
					$str_total_quantity = StuffWithBlank(number_format($flt_total_quantity, $int_decimals), 6);
				else
					$str_total_quantity = StuffWithBlank(number_format($flt_total_quantity, 0), 6);
				
				$strList = StuffWithBlank($_SESSION["arr_total_qty"][$i][0], 6)." ".			// product code
					StuffWithBlank($_SESSION["arr_total_qty"][$i][1], 6)." ".									// batch code
					PadWithBlank($_SESSION["arr_total_qty"][$i][12], 15)." ".			// product description
					StuffWithBlank($_SESSION["arr_total_qty"][$i][14], 5)." ".			// supplier abbreviation
					$str_total_quantity." ".							// quantity billed
					StuffWithBlank($str_discount, 3)." ".						// discount percent
					StuffWithBlank(number_format($_SESSION["arr_total_qty"][$i][6], $int_decimals), 10)." ".			// price
					StuffWithBlank($_SESSION["arr_total_qty"][$i][8], 7)." ".			// tax description
					StuffWithBlank(number_format($_SESSION["arr_total_qty"][$i][10], $int_decimals), 10);	// total
				
				if ($i == count($_SESSION["arr_total_qty"])-1)
					echo "<option value=\"".$i."\" selected>".$strList;
				else
					echo "<option value=\"".$i."\">".$strList;
			}
		}
		else {
			$arr_item_totals = array();
			$int_counter = 0;
			$int_item_counter = 0;
			$str_current_code = '';
			
			for ($i=0; $i<count($_SESSION["arr_total_qty"]); $i++) {
				
				if ($_SESSION["arr_total_qty"][$i][0] == $str_current_code) {
					// if the price is different, create another entry
					if (($arr_item_totals[$int_counter-1][6] <> $_SESSION["arr_total_qty"][$i][6]) ||
					    ($arr_item_totals[$int_counter-1][7] <> $_SESSION["arr_total_qty"][$i][7])) {
						$arr_item_totals[$int_counter][0] = $_SESSION["arr_total_qty"][$i][0];
						$arr_item_totals[$int_counter][1] = $_SESSION["arr_total_qty"][$i][1];
						$arr_item_totals[$int_counter][2] = $_SESSION["arr_total_qty"][$i][2];
						$arr_item_totals[$int_counter][4] = $_SESSION["arr_total_qty"][$i][4];
						$arr_item_totals[$int_counter][5] = $_SESSION["arr_total_qty"][$i][5];
						$arr_item_totals[$int_counter][6] = $_SESSION["arr_total_qty"][$i][6];
						$arr_item_totals[$int_counter][7] = $_SESSION["arr_total_qty"][$i][7];
						$arr_item_totals[$int_counter][8] = $_SESSION["arr_total_qty"][$i][8];
						$arr_item_totals[$int_counter][10] = $_SESSION["arr_total_qty"][$i][10];
						$arr_item_totals[$int_counter][12] = $_SESSION["arr_total_qty"][$i][12];
						$arr_item_totals[$int_counter][14] = $_SESSION["arr_total_qty"][$i][14];
						$arr_item_totals[$int_counter][15] = $_SESSION["arr_total_qty"][$i][15];
						
						$int_counter++;
					}
					else {
						$arr_item_totals[$int_counter-1][2] += $_SESSION["arr_total_qty"][$i][2];
						$arr_item_totals[$int_counter-1][5] += $_SESSION["arr_total_qty"][$i][5];
						$arr_item_totals[$int_counter-1][10] += $_SESSION["arr_total_qty"][$i][10];
					}
				}
				else {
					$arr_item_totals[$int_counter][0] = $_SESSION["arr_total_qty"][$i][0];
					$arr_item_totals[$int_counter][1] = $_SESSION["arr_total_qty"][$i][1];
					$arr_item_totals[$int_counter][2] = $_SESSION["arr_total_qty"][$i][2];
					$arr_item_totals[$int_counter][4] = $_SESSION["arr_total_qty"][$i][4];
					$arr_item_totals[$int_counter][5] = $_SESSION["arr_total_qty"][$i][5];
					$arr_item_totals[$int_counter][6] = $_SESSION["arr_total_qty"][$i][6];
					$arr_item_totals[$int_counter][7] = $_SESSION["arr_total_qty"][$i][7];
					$arr_item_totals[$int_counter][8] = $_SESSION["arr_total_qty"][$i][8];
					$arr_item_totals[$int_counter][10] = $_SESSION["arr_total_qty"][$i][10];
					$arr_item_totals[$int_counter][12] = $_SESSION["arr_total_qty"][$i][12];
					$arr_item_totals[$int_counter][14] = $_SESSION["arr_total_qty"][$i][14];
					$arr_item_totals[$int_counter][15] = $_SESSION["arr_total_qty"][$i][15];
					
					$int_item_counter++;
					$int_counter++;
				}
				
				$str_current_code = $_SESSION["arr_total_qty"][$i][0];
			}
			
			for ($i=0;$i<count($arr_item_totals);$i++) {
				$int_discount_amount = intval($arr_item_totals[$i][4]);
				$tmp_is_decimal = $arr_item_totals[$i][15];
				if ($int_discount_amount == 0)
					$str_discount = '';
				else
					$str_discount = $int_discount_amount."%";
				
				$flt_total_quantity = $arr_item_totals[$i][2] + $arr_item_totals[$i][5];
				if ($tmp_is_decimal == 'Y')
					$str_total_quantity = StuffWithBlank(number_format($flt_total_quantity, $int_decimals), 6);
				else
					$str_total_quantity = StuffWithBlank(number_format($flt_total_quantity, 0), 6);
				
				
				$strList = StuffWithBlank($arr_item_totals[$i][0], 6)." ".				// product code
					PadWithBlank($arr_item_totals[$i][12], 15)." ".					// product description
					StuffWithBlank($arr_item_totals[$i][14], 5)." ".				// supplier abbreviation
					$str_total_quantity." ".							// quantity billed
					StuffWithBlank($str_discount, 3)." ".						// discount percent
					StuffWithBlank(number_format($arr_item_totals[$i][6], $int_decimals), 10)." ".	// price
					StuffWithBlank($arr_item_totals[$i][8], 7)." ".					// tax description
					StuffWithBlank(number_format($arr_item_totals[$i][10], $int_decimals), 10);	// total
				
				if ($i == count($_SESSION["arr_total_qty"])-1)
					echo "<option value=\"".$i."\" selected>".$strList;
				else
					echo "<option value=\"".$i."\">".$strList;
			}
		}

	?>
	</select>
	
	<?
		$flt_total = RoundUp($flt_total);
		$_SESSION['bill_total'] = number_format($flt_total,2,'.','');
	?>
	
	<script language='javascript'>
		str = "billing_total.php?total=<?echo number_format($flt_total,2,'.','');?>&promotion=<?echo number_format($_SESSION['sales_promotion'],2,'.','')?>&items_billed=<?echo $int_item_counter?>";
		parent.frames["frame_total"].document.location=str;
	</script>

</form>
</body>
</html>