<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/tax.php");

	require_once("supplier_currentstock_data.php");

print_r($data);die();


	$_SESSION["int_stock_selected"] = 7;

	//==================
	// get user settings
	//------------------
	$code_sorting = $arr_invent_config['settings']['code_sorting'];
	
	$qry_settings = new Query("
		SELECT stock_show_available, bill_decimal_places
		FROM user_settings
		WHERE (storeroom_id = ".$_SESSION['int_current_storeroom'].")
	");
	$str_show_available = 'Y';
	$int_decimal_places = 2;
	if ($qry_settings->RowCount() > 0) {
		$str_show_available = $qry_settings->FieldByName('stock_show_available');
		$int_decimal_places = $qry_settings->FieldByName('bill_decimal_places');
	}

	if (IsSet($_GET["supplier_id"]))
		$int_supplier_id = $_GET["supplier_id"];
	else
		$int_supplier_id = 0;
		
	$_SESSION['global_current_supplier_id'] = $int_supplier_id;
	
	if (IsSet($_GET["include_tax"]))
		$str_include_tax = $_GET["include_tax"];
	else
		$str_include_tax = 'Y';
	
	if (IsSet($_GET["include_value"]))
		$str_include_value = $_GET["include_value"];
	else
		$str_include_value = 'Y';
		
	$str_order = "product_code";
	if (IsSet($_GET["order_by"]))
		$str_order = $_GET["order_by"];
	
	if ($str_order == 'product_code')
		if ($code_sorting == 'ALPHA_NUM')
			$str_order .= "+0 ASC";
		
	if (IsSet($_GET['display_stock']))
		$str_display_stock = $_GET['display_stock'];
	else
		$str_display_stock = 'All';
	
	if (IsSet($_GET['include_bprice']))
		$str_include_bprice = $_GET['include_bprice'];
	else
		$str_include_bprice = 'N';
		
	$str_is_filtered = 'N';
	if (IsSet($_GET['is_filtered']))
		$str_is_filtered = $_GET['is_filtered'];
		
	if ($str_is_filtered == 'Y') {
		$str_filter_field = $_GET['filter_field'];
		$str_filter_text = $_GET['filter_text'];
		$str_where = '';
		if ($str_filter_field == 'code')
			$str_where = "AND (sp.product_code = '".$str_filter_text."')";
		else if ($str_filter_field == 'description')
			$str_where = "AND (sp.product_description LIKE '".$str_filter_text."%')";
	}
	else {
		$str_filter_field = '';
		$str_filter_text = '';
		$str_where = '';
	}

	$qry_supplier = new Query("
		SELECT *
		FROM stock_supplier
		WHERE supplier_id = $int_supplier_id
	");

	if ($int_supplier_id === "__ALL") {
		$str_products = "
			SELECT sp.product_id, sp.product_code, sp.product_description, sp.mrp,
				ssp.use_batch_price, ssp.stock_adjusted, ssp.stock_minimum,
				SUM(ssb.stock_available) AS total_stock,
				st.*,
				smu.measurement_unit, smu.is_decimal,
				IF (ssp.use_batch_price='Y',
					SUM(sb.buying_price * ssb.stock_available),
					SUM(ssp.buying_price * ssb.stock_available)
				) AS buying_value,
				IF (ssp.use_batch_price='Y',
					SUM(sb.selling_price * ssb.stock_available),
					SUM(ssp.sale_price * ssb.stock_available)
				) AS selling_value,
				IF (ssp.use_batch_price='Y',
					sb.buying_price,
					ssp.buying_price
				) AS buying_price,
				IF (ssp.use_batch_price='Y',
					sb.selling_price,
					ssp.sale_price
				) AS selling_price,
				sc.category_description
			FROM stock_product sp
			LEFT JOIN ".Yearalize('stock_batch')." sb ON (sb.product_id = sp.product_id)
				AND (sb.product_id = sp.product_id)
				AND (sb.status = ".STATUS_COMPLETED.")
				AND (sb.deleted = 'N')
				AND (sb.is_active ='Y')
				AND (sb.storeroom_id = ".$_SESSION['int_current_storeroom'].")
			INNER JOIN ".Monthalize('stock_storeroom_batch')." ssb ON (ssb.batch_id = sb.batch_id)
				AND (ssb.storeroom_id = ".$_SESSION['int_current_storeroom'].")
				AND (ssb.is_active = 'Y')
			LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			LEFT JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = sp.tax_id)
			LEFT JOIN ".Monthalize('stock_storeroom_product')." ssp ON (ssp.product_id = sp.product_id)
				AND (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")
			LEFT JOIN stock_category sc ON (sc.category_id = sp.category_id)
			WHERE (sp.deleted = 'N')
				".$str_where."
			GROUP BY sp.product_id, sb.selling_price
			ORDER BY ".$str_order."
		";
	}
	else {

		$str_products = "

			SELECT sp.product_id, sp.product_code, sp.product_description, sp.mrp,
				sb.batch_code, ssb.stock_storeroom_batch_id,
				ssp.use_batch_price, ssp.stock_adjusted, ssp.stock_minimum,
				SUM(ssb.stock_available) AS total_stock,
				st.*,
				smu.measurement_unit, smu.is_decimal,
				IF (ssp.use_batch_price='Y',
					SUM(sb.buying_price * ssb.stock_available),
					SUM(ssp.buying_price * ssb.stock_available)
				) AS buying_value,
				IF (ssp.use_batch_price='Y',
					SUM(sb.selling_price * ssb.stock_available),
					SUM(ssp.sale_price * ssb.stock_available)
				) AS selling_value,
				IF (ssp.use_batch_price='Y',
					sb.buying_price,
					ssp.buying_price
				) AS buying_price,
				IF (ssp.use_batch_price='Y',
					sb.selling_price,
					ssp.sale_price
				) AS selling_price,
				sc.category_description

			FROM stock_product sp

			LEFT JOIN ".Yearalize('stock_batch')." sb ON (sb.product_id = sp.product_id)
				AND (sb.status = ".STATUS_COMPLETED.")
				AND (sb.deleted = 'N')
				AND (sb.is_active = 'Y')
				AND (sb.storeroom_id = ".$_SESSION['int_current_storeroom'].")

			INNER JOIN ".Monthalize('stock_storeroom_batch')." ssb ON (ssb.batch_id = sb.batch_id)
				AND (ssb.storeroom_id = ".$_SESSION['int_current_storeroom'].")
				AND (ssb.is_active = 'Y')

			LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)

			LEFT JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = sp.tax_id)

			LEFT JOIN ".Monthalize('stock_storeroom_product')." ssp ON (ssp.product_id = sp.product_id)
				AND (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")

			LEFT JOIN stock_category sc ON (sc.category_id = sp.category_id)

			WHERE (sp.supplier_id = ".$int_supplier_id.")
				AND (sp.deleted = 'N')

				".$str_where."

			GROUP BY sp.product_id, sb.selling_price

			ORDER BY ".$str_order."
		";


	}

//echo $str_products;die();
	
	$qry_products = new Query($str_products);

	$qry_batch = new Query("
		SELECT *
		FROM ".Yearalize('stock_batch')."
		LIMIT 1
	");
	if ($qry_batch->b_error == true)
		echo "stock_batch error ".mysql_error();

	$qry_tax = new Query("
		SELECT *
		FROM ".Monthalize('stock_tax')."
		LIMIT 1
	");
	if ($qry_tax->b_error == true)
		echo "stock tax error ".mysql_error();

	$arr_result = array();
	
	for ($i=0;$i<$qry_products->RowCount();$i++) {
		
		$arr_result[$i][0] = $qry_products->FieldByName('product_code');
		$arr_result[$i][1] = $qry_products->FieldByName('product_description');
		$arr_result[$i][2] = $qry_products->FieldByName('buying_price'); // buying price
		$arr_result[$i][3] = $qry_products->FieldByName('selling_price'); // selling price
		$flt_current_stock = number_format($qry_products->FieldByName('total_stock'), 3, '.', '');
		$arr_result[$i][5] = $flt_current_stock;  // stock
		$flt_adjusted_stock = number_format($qry_products->FieldByName('stock_adjusted'), 3, '.', '');
		$arr_result[$i][6] = $flt_adjusted_stock;
		$arr_result[$i][7] = number_format($qry_products->FieldByName('buying_value'), 3, '.', ''); // buying value
		$arr_result[$i][8] = number_format($qry_products->FieldByName('selling_value'), 3, '.', ''); // selling value
		$arr_result[$i][10] = $qry_products->FieldByName('is_decimal');
		$arr_result[$i][11] = number_format($qry_products->FieldByName('mrp'), 2, '.', ',');
		$arr_result[$i][12] = $qry_products->FieldByName('stock_minimum');
		$arr_result[$i]['category'] = $qry_products->FieldByName('category_description');
		

		if ($qry_products->FieldByName('use_batch_price') == 'Y') {
			
			
			$qry_batch->Query("
				SELECT sb.tax_id
				FROM ".Yearalize('stock_batch')." sb
				WHERE (sb.product_id = ".$qry_products->FieldByName('product_id').")
					AND (sb.storeroom_id = ".$_SESSION["int_current_storeroom"].")
					AND (sb.status = ".STATUS_COMPLETED.")
					AND (sb.deleted = 'N')
				ORDER BY date_created
			");
			
			
			$arr_result[$i][4] = 0; // tax_description
			$arr_result[$i][9] = 0; // price_total
			
			$has_multiple_prices = false;
			

			if ($qry_batch->RowCount() > 0) {

				$str_current_price = $qry_products->FieldByName('selling_price');
				
				for ($j=0;$j<$qry_batch->RowCount();$j++) {

					if ($str_current_price <> $qry_products->FieldByName('selling_price'))
						$has_multiple_prices = true;
					
					
					$qry_tax = new Query("
						SELECT *
						FROM ".Monthalize('stock_tax')."
						WHERE (tax_id = ".$qry_batch->FieldByName('tax_id').")
					");
					$arr_result[$i][4] = $qry_tax->FieldByName('tax_description');
					
					$int_tax_id = 0;
					if ($qry_batch->FieldByName('tax_id') != '')
						$int_tax_id = $qry_batch->FieldByName('tax_id');
						
					$tax_amount = calculateTax($arr_result[$i][3], $int_tax_id);
					$flt_price_total = RoundUp(($arr_result[$i][3] + $tax_amount));
					$arr_result[$i][9] = $flt_price_total;
					
					
					$str_current_price = $qry_products->FieldByName('selling_price');
					
					$qry_batch->Next();
				}
			}

			if ($has_multiple_prices == true)
				$arr_result[$i][0] = $arr_result[$i][0]."<font color='red'>*</font>";

		}
		else {

			$arr_result[$i][2] = $qry_products->FieldByName('buying_price');
			$arr_result[$i][3] = $qry_products->FieldByName('selling_price');
			$arr_result[$i][4] = $qry_products->FieldByName('tax_description');
			
			$int_tax_id = 0;
			if ($qry_products->FieldByName('tax_id') != '')
				$int_tax_id = $qry_products->FieldByName('tax_id');
				
			$tax_amount = calculateTax($arr_result[$i][3], $int_tax_id);
			$flt_price_total = RoundUp(($arr_result[$i][3] + $tax_amount));
			$arr_result[$i][9] = $flt_price_total;
			
			$arr_result[$i][7] = $arr_result[$i][2] * ($flt_current_stock - $flt_adjusted_stock);
			$arr_result[$i][8] = $arr_result[$i][3] * ($flt_current_stock - $flt_adjusted_stock);
			
		}
		
		$qry_products->Next();
	}
?>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../include/styles.css" />
    </head>
<body id='body_bgcolor'>
	<font class='normaltext'>
	
	<table width='100%' border='0' cellpadding='0' cellspacing='0'>
	<tr><td align='left'>
	
		<table border=1 cellpadding=7 cellspacing=0 class='normaltext'>
			<?
				$int_counter = 0;
				$total_stock = 0;
				$total_adjusted = 0;
				$total_b_value = 0;
				$total_s_value = 0;
				
				for ($i=0; $i<count($arr_result); $i++) {
						
					if ($str_display_stock == 'All') {
						$bool_display = 'Y';
						if ($str_is_filtered == 'Y') {
							if ($str_filter_field == 'price') {
								if ($arr_result[$i][3] == $str_filter_text)
									$bool_display = 'Y';
								else
									$bool_display = 'N';
							}
						}
						
						if ($bool_display == 'Y') {
							if ($int_counter % 2 == 0)
							    $str_color="#eff7ff";
							else
							    $str_color="#deecfb";
							
							echo "<tr bgcolor=".$str_color.">";
								
							echo "<td width='50px' align=right>".$arr_result[$i][0]."</td>";
							echo "<td width='250px'>".$arr_result[$i][1]."</td>";
							echo "<td width='100px'>".$arr_result[$i]['category']."</td>";
							if ($str_include_bprice == 'Y')
								echo "<td width='100px' align=right>".sprintf("%01.2f",$arr_result[$i][2],3)."</td>";
							echo "<td width='100px' align=right>".sprintf("%01.2f",$arr_result[$i][3],3)."</td>";
							if ($str_include_tax == 'Y') {
								echo "<td width='100px' align=right>".sprintf("%01.2f",$arr_result[$i][9],3)."</td>";
								echo "<td width='100px' align='right'>".$arr_result[$i][11]."</td>";
								echo "<td width='50px' align=right>".$arr_result[$i][4]."</td>";
							}
							else
								echo "<td width='100px' align='right'>".$arr_result[$i][11]."</td>";
							if ($arr_result[$i][10] == 'Y') {
								if ($arr_result[$i][6] > 0)
									echo "<td width='100px' align=right>".number_format($arr_result[$i][5],$int_decimal_places,'.',',')."(-".number_format($arr_result[$i][6],$int_decimal_places,'.',',').")</td>";
								else
									echo "<td width='100px' align=right>".number_format($arr_result[$i][5],$int_decimal_places,'.',',')."</td>";
							} else {
								if ($arr_result[$i][6] > 0)
									echo "<td width='100px' align=right>".number_format($arr_result[$i][5],0,'.','')."(-".number_format($arr_result[$i][6],0,'.','').")</td>";
								else
									echo "<td width='100px' align=right>".number_format($arr_result[$i][5],0,'.','')."</td>";
							}
							if ($str_include_value == 'Y') {
								if ($str_include_bprice == 'Y')
									echo "<td width='100px' align=right>".sprintf("%01.2f",$arr_result[$i][7],3)."</td>";
								echo "<td width='100px' align=right>".sprintf("%01.2f",$arr_result[$i][8],3)."</td>";
							}
							echo "</tr>\n";
							
							$int_counter++;
							$total_stock += $arr_result[$i][5];
							$total_adjusted += $arr_result[$i][6];
							$total_b_value += $arr_result[$i][7];
							$total_s_value += $arr_result[$i][8];
						}
					}
					else if ($str_display_stock == 'Below Minimum') {
						$bool_display = 'Y';
						if ($str_is_filtered == 'Y') {
							if ($str_filter_field == 'price') {
								if ($arr_result[$i][3] == $str_filter_text)
									$bool_display = 'Y';
								else
									$bool_display = 'N';
							}
						}
						
						if (($bool_display == 'Y') && ($arr_result[$i][5] < $arr_result[$i][12])) {
							if ($int_counter % 2 == 0)
								$str_color="#eff7ff";
							else
								$str_color="#deecfb";
							
							echo "<tr bgcolor=".$str_color.">";
							
							echo "<td width='50px' align=right>".$arr_result[$i][0]."</td>";
							echo "<td width='250px'>".$arr_result[$i][1]."</td>";
							if ($str_include_bprice == 'Y')
								echo "<td width='100px' align=right>".sprintf("%01.2f",$arr_result[$i][2],3)."</td>";
							echo "<td width='100px' align=right>".sprintf("%01.2f",$arr_result[$i][3],3)."</td>";
							if ($str_include_tax == 'Y') {
								echo "<td width='100px' align=right>".sprintf("%01.2f",$arr_result[$i][9],3)."</td>";
								echo "<td width='100px' align='right'>".$arr_result[$i][11]."</td>";
								echo "<td width='50px' align=right>".$arr_result[$i][4]."</td>";
							}
							else
								echo "<td width='100px' align='right'>".$arr_result[$i][11]."</td>";
							if ($arr_result[$i][10] == 'Y') {
								if ($arr_result[$i][6] > 0)
									echo "<td width='100px' align=right>".number_format($arr_result[$i][5],$int_decimal_places,'.',',')."(-".number_format($arr_result[$i][6],$int_decimal_places,'.',',').")</td>";
								else
									echo "<td width='100px' align=right>".number_format($arr_result[$i][5],$int_decimal_places,'.',',')."</td>";
							} else {
								if ($arr_result[$i][6] > 0)
									echo "<td width='100px' align=right>".number_format($arr_result[$i][5],0,'.','')."(-".number_format($arr_result[$i][6],0,'.','').")</td>";
								else
									echo "<td width='100px' align=right>".number_format($arr_result[$i][5],0,'.','')."</td>";
							}
							if ($str_include_value == 'Y') {
								if ($str_include_bprice == 'Y')
									echo "<td width='100px' align=right>".sprintf("%01.2f",$arr_result[$i][7],3)."</td>";
								echo "<td width='100px' align=right>".sprintf("%01.2f",$arr_result[$i][8],3)."</td>";
							}
							echo "</tr>";
			
							$int_counter++;
							$total_stock += $arr_result[$i][5];
							$total_adjusted += $arr_result[$i][6];
							$total_b_value += $arr_result[$i][7];
							$total_s_value += $arr_result[$i][8];
						}
					}
					else if ($str_display_stock == 'Zero') {
						$bool_display = 'Y';
						if ($str_is_filtered == 'Y') {
							if ($str_filter_field == 'price') {
								if ($arr_result[$i][3] == $str_filter_text)
									$bool_display = 'Y';
								else
									$bool_display = 'N';
							}
						}
		
						if (($bool_display == 'Y') && ($arr_result[$i][5] == 0)) {
							if ($int_counter % 2 == 0)
							    $str_color="#eff7ff";
							else
							    $str_color="#deecfb";
		
							echo "<tr bgcolor=".$str_color.">";
			
							echo "<td width='50px' align=right>".$arr_result[$i][0]."</td>";
							echo "<td width='250px'>".$arr_result[$i][1]."</td>";
							if ($str_include_bprice == 'Y')
								echo "<td width='100px' align=right>".sprintf("%01.2f",$arr_result[$i][2],3)."</td>";
							echo "<td width='100px' align=right>".sprintf("%01.2f",$arr_result[$i][3],3)."</td>";
							if ($str_include_tax == 'Y') {
								echo "<td width='100px' align=right>".sprintf("%01.2f",$arr_result[$i][9],3)."</td>";
								echo "<td width='100px' align='right'>".$arr_result[$i][11]."</td>";
								echo "<td width='50px' align=right>".$arr_result[$i][4]."</td>";
							}
							else
								echo "<td width='100px' align='right'>".$arr_result[$i][11]."</td>";
							if ($arr_result[$i][10] == 'Y') {	// is_decimal
								if ($arr_result[$i][6] > 0)
									echo "<td width='100px' align=right>".number_format($arr_result[$i][5],$int_decimal_places,'.',',')."(-".number_format($arr_result[$i][6],$int_decimal_places,'.',',').")</td>";
								else
									echo "<td width='100px' align=right>".number_format($arr_result[$i][5],$int_decimal_places,'.',',')."</td>";
							} else {
								if ($arr_result[$i][6] > 0)
									echo "<td width='100px' align=right>".number_format($arr_result[$i][5],0,'.','')."(-".number_format($arr_result[$i][6],0,'.','').")</td>";
								else
									echo "<td width='100px' align=right>".number_format($arr_result[$i][5],0,'.','')."</td>";
							}
							if ($str_include_value == 'Y') {
								if ($str_include_bprice == 'Y')
									echo "<td width='100px' align=right>".sprintf("%01.2f",$arr_result[$i][7],3)."</td>";
								echo "<td width='100px' align=right>".sprintf("%01.2f",$arr_result[$i][8],3)."</td>";
							}
							echo "</tr>";
			
							$int_counter++;
							$total_stock += $arr_result[$i][5];
							$total_adjusted += $arr_result[$i][6];
							$total_b_value += $arr_result[$i][7];
							$total_s_value += $arr_result[$i][8];
						}
					}
					else if ($str_display_stock == 'Non-zero') {
						$bool_display = 'Y';
						if ($str_is_filtered == 'Y') {
							if ($str_filter_field == 'price') {
								if ($arr_result[$i][3] == $str_filter_text)
									$bool_display = 'Y';
								else
									$bool_display = 'N';
							}
						}
		
						if (($bool_display == 'Y') && (($arr_result[$i][5] <> 0) || ($arr_result[$i][6] <> 0))) {
							if ($int_counter % 2 == 0)
							    $str_color="#eff7ff";
							else
							    $str_color="#deecfb";
		
							echo "<tr bgcolor=".$str_color.">";
			
							echo "<td width='50px' align=right>".$arr_result[$i][0]."</td>";
							echo "<td width='250px'>".$arr_result[$i][1]."</td>";
							if ($str_include_bprice == 'Y')
								echo "<td width='100px' align=right>".sprintf("%01.2f",$arr_result[$i][2],3)."</td>";
							echo "<td width='100px' align=right>".sprintf("%01.2f",$arr_result[$i][3],3)."</td>";
							if ($str_include_tax == 'Y') {
								echo "<td width='100px' align=right>".sprintf("%01.2f",$arr_result[$i][9],3)."</td>";
								echo "<td width='100px' align='right'>".$arr_result[$i][11]."</td>";
								echo "<td width='50px' align=right>".$arr_result[$i][4]."</td>";
							}
							else
								echo "<td width='100px' align='right'>".$arr_result[$i][11]."</td>";
							if ($arr_result[$i][10] == 'Y') {
								if ($arr_result[$i][6] > 0)
									echo "<td width='100px' align=right>".number_format($arr_result[$i][5],$int_decimal_places,'.',',')."(-".number_format($arr_result[$i][6],$int_decimal_places,'.',',').")</td>";
								else
									echo "<td width='100px' align=right>".number_format($arr_result[$i][5],$int_decimal_places,'.',',')."</td>";
							} else {
								if ($arr_result[$i][6] > 0)
									echo "<td width='100px' align=right>".number_format($arr_result[$i][5],0,'.','')."(-".number_format($arr_result[$i][6],0,'.','').")</td>";
								else
									echo "<td width='100px' align=right>".number_format($arr_result[$i][5],0,'.','')."</td>";
							}
							if ($str_include_value == 'Y') {
								if ($str_include_bprice == 'Y')
									echo "<td width='100px' align=right>".sprintf("%01.2f",$arr_result[$i][7],3)."</td>";
								echo "<td width='100px' align=right>".sprintf("%01.2f",$arr_result[$i][8],3)."</td>";
							}
							echo "</tr>";
			
							$int_counter++;
							$total_stock += $arr_result[$i][5];
							$total_adjusted += $arr_result[$i][6];
							$total_b_value += $arr_result[$i][7];
							$total_s_value += $arr_result[$i][8];
						}
					}
				}
	
			?>
		</table>
		
	</td></tr>
	</table>
	</font>
	<script language='javascript'>
	
		parent.frames["footer"].document.location = 'supplier_currentstock_footer.php?'+
			'total_stock=<?echo number_format($total_stock,2,'.','')?>'+
			'&total_adjusted=<?echo number_format($total_adjusted,2,'.','')?>'+
			'&buying_value=<?echo number_format($total_b_value,2,'.','')?>'+
			'&selling_value=<?echo number_format($total_s_value,2,'.','')?>';
	</script>

</body>
</html>