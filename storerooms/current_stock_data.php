<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/tax.php");

	$int_access_level = (getModuleAccessLevel('Storerooms'));
	
	$code_sorting = $arr_invent_config['settings']['code_sorting'];
	
	$int_type = 0;
	if (IsSet($_GET['category_type']))
		$int_type = $_GET['category_type'];
		
	$int_category_id = 0;
	if (IsSet($_GET['category_id']))
		$int_category_id = $_GET['category_id'];

	$str_order = 'product_code';
	if (IsSet($_GET['order']))
		$str_order = $_GET['order'];

	if ($str_order == 'product_code')
	{
		if ($code_sorting == 'ALPHA_NUM')
			$str_order .= "+0 ASC";
	}
	else if ($str_order == 'category_description')
	{
		$str_order .= ', product_code +0';
	}
	
	$str_global_stock = 'Y';
	if (IsSet($_GET['global_stock']))
		$str_global_stock = $_GET['global_stock'];
	
	$str_show = 'ALL';
	if (IsSet($_GET['show']))
		$str_show = $_GET['show'];
	
	if ($str_global_stock == 'Y') {
		$str_select_global = 'SUM(ssp.stock_current) AS current_stock ';
	
		$str_group_clause = ' GROUP BY sp.product_id ';
		
		if ($str_show == 'ZERO') {
			$str_group_clause .= 'HAVING (SUM(ssp.stock_current) <= 0)';
		}
		elseif ($str_show == 'NONZERO') {
			$str_group_clause .= 'HAVING (SUM(ssp.stock_current) > 0)';
		}
	}
	else {
		$str_select_global = 'ssp.stock_current AS current_stock ';
		if (($int_type == 'ALL') && ($int_category_id == 'ALL')) {
			if ($str_show == 'ALL')
			$str_group_clause = "WHERE (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")";
			
			elseif ($str_show == 'ZERO')
			$str_group_clause = "WHERE (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")
				AND (ssp.stock_current <= 0)";
				
			elseif ($str_show == 'NONZERO')
			$str_group_clause = "WHERE (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")
				AND (ssp.stock_current > 0)";
		}
		else {
			if ($str_show == 'ALL')
			$str_group_clause = "AND (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")";
			
			elseif ($str_show == 'ZERO')
			$str_group_clause = "AND (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")
				AND (ssp.stock_current <= 0)";
				
			elseif ($str_show == 'NONZERO')
			$str_group_clause = "AND (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")
				AND (ssp.stock_current > 0)";
		}
		$str_group_clause .= " AND (sp.is_available = 'Y')";
	}
    
	if ($int_type == 'ALL') {
		if ($int_category_id == 'ALL') {
			$str_query = "
				SELECT sp.product_id, sp.product_code, sp.product_description, sp.mrp, ".$str_select_global.", smu.measurement_unit, st.tax_id,
					ssp.use_batch_price, ssp.sale_price, ssp.buying_price,
					sc.category_description
				FROM ".Monthalize('stock_storeroom_product')." ssp
				INNER JOIN stock_product sp ON (sp.product_id = ssp.product_id)
				INNER JOIN stock_category sc ON (sc.category_id = sp.category_id)
				INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				INNER JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = sp.tax_id)
				WHERE (sp.is_available = 'Y')
				".$str_group_clause."
				ORDER BY ".$str_order;
		}
		else {
			$str_query = "
				SELECT sp.product_id, sp.product_code, sp.product_description, sp.mrp, ".$str_select_global.", smu.measurement_unit, st.tax_id,
					ssp.use_batch_price, ssp.sale_price, ssp.buying_price,
					sc.category_description
				FROM stock_category sc
				INNER JOIN stock_product sp ON (sp.category_id = sc.category_id)
				INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				INNER JOIN ".Monthalize('stock_storeroom_product')." ssp ON (sp.product_id = ssp.product_id)
				INNER JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = sp.tax_id)
				WHERE (sc.category_id = $int_category_id) AND (sp.is_available = 'Y')
				".$str_group_clause."
				ORDER BY ".$str_order;
		}
	}
	else if ($int_type == '1') { // PERISHABLE
		if ($int_category_id == 'ALL') {
			$str_query = "
				SELECT sp.product_id, sp.product_code, sp.product_description, sp.mrp, ".$str_select_global.", smu.measurement_unit, st.tax_id,
					ssp.use_batch_price, ssp.sale_price, ssp.buying_price,
					sc.category_description
				FROM stock_category sc
				INNER JOIN stock_product sp ON (sp.category_id = sc.category_id)
				INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				INNER JOIN ".Monthalize('stock_storeroom_product')." ssp ON (sp.product_id = ssp.product_id)
				INNER JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = sp.tax_id)
				WHERE (sp.is_perishable = 'Y') AND (sp.is_available = 'Y')
				".$str_group_clause."
				ORDER BY ".$str_order;
		}
		else {
			$str_query = "
				SELECT sp.product_id, sp.product_code, sp.product_description, sp.mrp, ".$str_select_global.", smu.measurement_unit, st.tax_id,
					ssp.use_batch_price, ssp.sale_price, ssp.buying_price,
					sc.category_description
				FROM stock_category sc
				INNER JOIN stock_product sp ON (sp.category_id = sc.category_id)
				INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				INNER JOIN ".Monthalize('stock_storeroom_product')." ssp ON (sp.product_id = ssp.product_id)
				INNER JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = sp.tax_id)
				WHERE (sc.category_id = $int_category_id) AND (sp.is_perishable = 'Y') AND (sp.is_available = 'Y')
				".$str_group_clause."
				ORDER BY ".$str_order;
		}
	}
	else if ($int_type == '2') {
		if ($int_category_id == 'ALL')
			$str_query = "
				SELECT sp.product_id, sp.product_code, sp.product_description, sp.mrp, ".$str_select_global.", smu.measurement_unit, st.tax_id,
					ssp.use_batch_price, ssp.sale_price, ssp.buying_price,
					sc.category_description
				FROM stock_category sc
				INNER JOIN stock_product sp ON (sp.category_id = sc.category_id)
				INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				INNER JOIN ".Monthalize('stock_storeroom_product')." ssp ON (sp.product_id = ssp.product_id)
				INNER JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = sp.tax_id)
				WHERE (sp.is_perishable = 'N') AND (sp.is_available = 'Y')
				".$str_group_clause."
				ORDER BY ".$str_order;
		else
			$str_query = "
				SELECT sp.product_id, sp.product_code, sp.product_description, sp.mrp, ".$str_select_global.", smu.measurement_unit, st.tax_id,
					ssp.use_batch_price, ssp.sale_price, ssp.buying_price,
					sc.category_description
				FROM stock_category sc
				INNER JOIN stock_product sp ON (sp.category_id = sc.category_id)
				INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				INNER JOIN ".Monthalize('stock_storeroom_product')." ssp ON (sp.product_id = ssp.product_id)
				INNER JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = sp.tax_id)
				WHERE (sc.category_id = $int_category_id) AND (sp.is_perishable = 'N') AND (sp.is_available = 'Y')
				".$str_group_clause."
				ORDER BY ".$str_order;
	}
//echo $str_query;
	$qry = new Query($str_query);
	
	$qry_batch = new Query("SELECT * FROM stock_product LIMIT 1");
	
?>
<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	</head>

<body id='body_bgcolor' leftmargin=15 topmargin=0>

<table width='100%' border='0'>
	<tr><td align='left'>
		
		<table border='1' cellpadding='7' cellspacing='0'>
		<?
			$flt_total_buying_value = 0;
			$flt_total_selling_value = 0;
			
			for ($i=0;$i<$qry->RowCount();$i++) {
				$flt_buying_price = 0;
				$flt_selling_price = 0;
				
				if ($qry->FieldByName('use_batch_price') == 'Y') {
					$qry_batch->Query("
						SELECT sb.batch_id, sb.batch_code, sb.buying_price, sb.selling_price, sb.tax_id, ssb.stock_available, ssb.is_active
						FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb
						WHERE (sb.product_id = ".$qry->FieldByName('product_id').") AND
							(sb.storeroom_id = ".$_SESSION["int_current_storeroom"].") AND
							(sb.status = ".STATUS_COMPLETED.") AND
							(sb.deleted = 'N') AND
							(ssb.product_id = sb.product_id) AND
							(ssb.batch_id = sb.batch_id) AND
							(ssb.storeroom_id = sb.storeroom_id) AND 
							(ssb.is_active = 'Y')
						ORDER BY date_created
					");
					if ($qry_batch->RowCount() > 0) {
						$flt_buying_price = number_format($qry_batch->FieldByName('buying_price'), 2, '.', '');
						$flt_selling_price = number_format($qry_batch->FieldByName('selling_price'), 2, '.', '');
					}
				}
				else {
					$flt_buying_price = number_format($qry->FieldByName('buying_price'), 2, '.', '');
					$flt_selling_price = number_format($qry->FieldByName('sale_price'), 2, '.', '');
				}
				
				$tax_amount = calculateTax($flt_selling_price, $qry->FieldByName('tax_id'));
				$flt_buying_value = $flt_buying_price * $qry->FieldByName('current_stock');
				$flt_buying_value = number_format($flt_buying_value, 2, '.', '');
				$flt_price_total = number_format(RoundUp($flt_selling_price + $tax_amount),2,'.','');
				$flt_selling_value = $flt_price_total * $qry->FieldByName('current_stock');
				$flt_selling_value = number_format($flt_selling_value, 2, '.', '');
				
				$flt_total_buying_value += $flt_buying_value;
				$flt_total_selling_value += $flt_selling_value;
				
				if ($i % 2 == 0)
					$str_color="#eff7ff";
				else
					$str_color="#deecfb";
				
				echo "<tr bgcolor='$str_color'>";
				echo "<td width='60px' align='right' class='normaltext'>".$qry->FieldByName('product_code')."</td>";
				echo "<td width='250px' class='normaltext'>".$qry->FieldByName('product_description')."</td>";
				echo "<td width='275px' class='normaltext'>".$qry->FieldByName('category_description')."</td>";
				echo "<td width='100px' align='right' class='normaltext'>".number_format($qry->FieldByName('current_stock'), 2, '.', '')."</td>";
				echo "<td width='35px' class='normaltext'>".$qry->FieldByName('measurement_unit')."</td>";
				echo "<td width='100px' align='right' class='normaltext'>".$flt_buying_price."</td>";
				echo "<td width='100px' align='right' class='normaltext'>".$flt_price_total."</td>";
				echo "<td width='100px' align='right' class='normaltext'>".number_format($qry->FieldByName('mrp'),2,'.','')."</td>";
				echo "<td width='100px' align='right' class='normaltext'>".number_format($flt_buying_value, 2, '.', ',')."</td>";
				echo "<td width='100px' align='right' class='normaltext'>".number_format($flt_selling_value, 2, '.', ',')."</td>";
				echo "</tr>\n";
				
				$qry->Next();
			}
		?>
	</table>
</td></tr>
</table>
</body>
</html>

<script language='javascript'>
parent.frames['footer'].document.location = 'current_stock_data_footer.php?total_value=<?echo number_format($flt_total_selling_value,2,'.','')?>&total_buying=<?echo number_format($flt_total_buying_value,2,'.','')?>';
</script>