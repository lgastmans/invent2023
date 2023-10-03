<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/tax.php");

	$_SESSION["int_bills_menu_selected"] = 10;

	$sql_settings = new Query("
		SELECT *
		FROM user_settings
	");
	if ($sql_settings->RowCount() > 0) {
		$str_calc_tax_first = $sql_settings->FieldByName('calculate_tax_before_discount');
	}

	if (IsSet($_GET["supplier_id"]))
		$int_supplier_id = $_GET["supplier_id"];
	else
		$int_supplier_id = 0;
	
	$_SESSION['global_current_supplier_id'] = $int_supplier_id;
	
	$str_include_tax = 'Y';
	if (IsSet($_GET['include_tax']))
		$str_include_tax = $_GET['include_tax'];


	$qry_supplier = new Query("
		SELECT commission_percent, commission_percent_2, commission_percent_3
		FROM stock_supplier
		WHERE (supplier_id = ".$int_supplier_id.")
	");
	$flt_percent = 0;
	$flt_percent_2 = 0;
	$flt_percent_3 = 0;
	if ($qry_supplier->RowCount() > 0)
		$flt_percent = $qry_supplier->FieldByName('commission_percent');
		$flt_percent_2 = $qry_supplier->FieldByName('commission_percent_2');
		$flt_percent_3 = $qry_supplier->FieldByName('commission_percent_3');

	$str_query = "
		SELECT DAYOFMONTH(b.date_created) AS date_created, b.bill_number, b.is_debit_bill,
			sp.product_code,
			bi.product_description,
			bi.price,
			bi.tax_id,
			st.tax_description,
			ROUND(bi.quantity + bi.adjusted_quantity, 3) AS quantity,
			bi.discount,
			IF(bi.discount > 0, 
					ROUND((bi.price * (1 - (bi.discount/100)) * (bi.quantity + bi.adjusted_quantity)), 2), 
					ROUND((bi.price * (bi.quantity + bi.adjusted_quantity)), 2)) 
			AS amount,
			smu.is_decimal
		FROM ".Monthalize('bill')." b, 
			".Monthalize('bill_items')." bi, 
			stock_product sp, 
			".Yearalize('stock_batch')." sb,
			stock_measurement_unit smu,
			".Monthalize('stock_tax')." st
		WHERE (bi.bill_id = b.bill_id)
			AND (b.bill_status = ".BILL_STATUS_RESOLVED.")
			AND (sp.product_id = bi.product_id)
			AND (sb.product_id = bi.product_id)
			AND (sb.supplier_id = ".$int_supplier_id.")
			AND (sb.batch_id = bi.batch_id)
			AND (sp.measurement_unit_id = smu.measurement_unit_id)
			AND (bi.tax_id = st.tax_id)
		ORDER BY b.date_created";
	$qry = new Query($str_query);
?>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../include/styles.css" />
    </head>
<body id='body_bgcolor' leftmargin=15 topmargin=5 marginwidth=5 marginheight=5>

	<font class='normaltext'>
	<table border=1 cellpadding=7 cellspacing=0>
		<tr class='normaltext_bold' bgcolor='lightgrey'>
			<td>Date</td>
			<td>Bill</td>
			<td>Code</td>
			<td>Description</td>
			<td>Price</td>
			<? if ($str_include_tax == 'Y') { ?>
			<td>Tax</td>
			<? } ?>
			<td>Qty</td>
			<td>Discount %</td>
			<td>Amount</td>
		</tr>
		<?
			$date_current = 0;
			$total = 0;
			$total_qty = 0;

			for ($i=0;$i<$qry->RowCount();$i++) {
				if ($i % 2 == 0)
					$str_color="#eff7ff";
				else
					$str_color="#deecfb";

				echo "<tr class='normaltext' bgcolor=".$str_color.">";
				
				$tmp_discount = $qry->FieldByName('discount');

				if ($str_include_tax == 'Y') {
					
					if ($qry->FieldByName('is_debit_bill') == 'Y')
						$tmp_qty = ($qry->FieldByName('quantity') * -1);
					else
						$tmp_qty = $qry->FieldByName('quantity');
					$flt_price = number_format($qry->FieldByName('price'), 2, '.', '');
					$tmp_tax_id = $qry->FieldByName('tax_id');
					
					if ($tmp_discount > 0) {
						if ($str_calc_tax_first == 'Y') {
							$tax_price = round($flt_price + calculateTax($flt_price, $tmp_tax_id),3);
							$tax_amount = calculateTax(($flt_price * $tmp_qty), $tmp_tax_id);
							$flt_discount = round(($tmp_qty * $tax_price) * ($tmp_discount/100),3);
							$flt_amount = round(($tmp_qty * $tax_price - $flt_discount), 3);
						}
						else {
							$discount_price = round(($flt_price * (1 - ($tmp_discount/100))), 3);
							$tax_amount = calculateTax($tmp_qty * $discount_price, $tmp_tax_id);
							$flt_amount = round(($tmp_qty * $discount_price + $tax_amount), 3);
						}
					}
					else {
						$tax_amount = calculateTax($flt_price * $tmp_qty, $tmp_tax_id);
						$flt_amount = round(($tmp_qty * $flt_price + $tax_amount), 3);
					}
					$flt_amount = number_format($flt_amount, 2, '.', '');
				}
				else {
					$flt_price = number_format($qry->FieldByName('price'), 2,'.','');
					$flt_amount = number_format($qry->FieldByName('amount'), 2, '.', '');
				}

				if ($date_current < $qry->FieldByName('date_created')) {
					echo "<td>".$qry->FieldByName('date_created')."</td>";
					$date_current = $qry->FieldByName('date_created');
				}
				else
					echo "<td>&nbsp;</td>";
				echo "<td align=right>".$qry->FieldByName('bill_number')."</td>";
				echo "<td align=right>".$qry->FieldByName('product_code')."</td>";
				echo "<td>".$qry->FieldByName('product_description')."</td>";
				echo "<td align=right>".$flt_price."</td>";
				if ($str_include_tax == 'Y') {
					echo "<td align=right>".$qry->FieldByName('tax_description')."</td>";
				}
				
				if ($qry->FieldByName('is_debit_bill') == 'Y')
					$tmp_qty = ($qry->FieldByName('quantity') * -1);
				else
					$tmp_qty = $qry->FieldByName('quantity');
				
				if ($qry->FieldByName('is_decimal') == 'Y')
					echo "<td align=right>".number_format($tmp_qty, 2, '.', '')."</td>";
				else
					echo "<td align=right>".number_format($tmp_qty, 0, '.', '')."</td>";
				echo "<td align=right>".$qry->FieldByName('discount')."</td>";
				
				if ($qry->FieldByName('is_debit_bill') == 'Y')
					$flt_amount = number_format(($flt_amount * -1), 2, '.', '');
				echo "<td align=right>".$flt_amount."</td>";
				echo "</tr>";

				$total += $flt_amount;
				$total_qty += $qry->FieldByName('quantity');

				$qry->Next();
			}

			$commission = $total * ($flt_percent/100);
			
			$commission_2 = 0;
			if ($flt_percent_2 > 0)
				$commission_2 = $total * ($flt_percent_2/100);
				
			$commission_3 = 0;
			if ($flt_percent_3 > 0)
				$commission_3 = $total * ($flt_percent_3/100);
				
			$given = $total - $commission - $commission_2 - $commission_3;
		?>
	</table>
	<br>
	<table width='30%' border='0' cellpadding=0 cellspacing=0>
		<tr>
			<td width='75%' align='right'>Total :&nbsp;</td>
			<td align='right'><? echo number_format($total, 2, '.', ','); ?></td>
		</tr>
		<tr>
			<td width='75%' align='right'><? echo "Commission ".$flt_percent."% :";?>&nbsp;</td>
			<td align='right'><? echo number_format($commission, 2, '.', ','); ?></td>
		</tr>
		<? if ($flt_percent_2 > 0) { ?>
		<tr>
			<td width='75%' align='right'><? echo "Commission ".$flt_percent_2."% :";?>&nbsp;</td>
			<td align='right'><? echo number_format($commission_2, 2, '.', ','); ?></td>
		</tr>
		<? } ?>
		<? if ($flt_percent_3 > 0) { ?>
		<tr>
			<td width='75%' align='right'><? echo "Commission ".$flt_percent_3."% :";?>&nbsp;</td>
			<td align='right'><? echo number_format($commission_3, 2, '.', ','); ?></td>
		</tr>
		<? } ?>
		<tr>
			<td width='75%' align='right'>Given :&nbsp;</td>
			<td align='right'><? echo number_format($given, 2, '.', ','); ?></td>
		</tr>
	</table>
	</font>
</body>
</html>