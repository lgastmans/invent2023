<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/tax.php");
	require_once("../common/product_funcs.inc.php");	

	$_SESSION["int_bills_menu_selected"] = 11;

	if (IsSet($_GET["supplier_type"]))
		$str_supplier_type = $_GET["supplier_type"];
	else
		$str_supplier_type = 'Y';

	$str_include_tax = 'Y';

	$sql = "
		SELECT ss.supplier_id, ss.supplier_name, ss.account_number, ss.trust, ss.is_active,
			SUM(IF(bi.discount > 0, 
					ROUND((bi.price * (1 - (bi.discount/100)) * (bi.quantity + bi.adjusted_quantity)), 2), 
					ROUND((bi.price * (bi.quantity + bi.adjusted_quantity)), 2))) AS amount,
					
			ROUND((SUM(IF(bi.discount > 0, 
					ROUND((bi.price * (1 - (bi.discount/100)) * (bi.quantity + bi.adjusted_quantity)), 2), 
					ROUND((bi.price * (bi.quantity + bi.adjusted_quantity)), 2))) * ss.commission_percent / 100), 2) AS commission,
			
			ROUND((SUM(IF(bi.discount > 0, 
					ROUND((bi.price * (1 - (bi.discount/100)) * (bi.quantity + bi.adjusted_quantity)), 2), 
					ROUND((bi.price * (bi.quantity + bi.adjusted_quantity)), 2))) * ss.commission_percent_2 / 100), 2) AS commission2,
			
			ROUND((SUM(IF(bi.discount > 0, 
					ROUND((bi.price * (1 - (bi.discount/100)) * (bi.quantity + bi.adjusted_quantity)), 2), 
					ROUND((bi.price * (bi.quantity + bi.adjusted_quantity)), 2))) * ss.commission_percent_3 / 100), 2) AS commission3,
			
			(SUM(IF(bi.discount > 0, 
					ROUND((bi.price * (1 - (bi.discount/100)) * (bi.quantity + bi.adjusted_quantity)), 2), 
					ROUND((bi.price * (bi.quantity + bi.adjusted_quantity)), 2))) -
				(
					ROUND((SUM(IF(bi.discount > 0, 
							ROUND((bi.price * (1 - (bi.discount/100)) * (bi.quantity + bi.adjusted_quantity)), 2), 
							ROUND((bi.price * (bi.quantity + bi.adjusted_quantity)), 2))) * ss.commission_percent / 100), 2)) -
					ROUND((SUM(IF(bi.discount > 0, 
							ROUND((bi.price * (1 - (bi.discount/100)) * (bi.quantity + bi.adjusted_quantity)), 2), 
							ROUND((bi.price * (bi.quantity + bi.adjusted_quantity)), 2))) * ss.commission_percent_2 / 100), 2) -
					ROUND((SUM(IF(bi.discount > 0, 
							ROUND((bi.price * (1 - (bi.discount/100)) * (bi.quantity + bi.adjusted_quantity)), 2), 
							ROUND((bi.price * (bi.quantity + bi.adjusted_quantity)), 2))) * ss.commission_percent_3 / 100), 2)
				)
				AS total
		FROM ".Monthalize('bill')." b, 
			".Monthalize('bill_items')." bi, 
			stock_product sp, 
			".Yearalize('stock_batch')." sb,
			stock_supplier ss
		WHERE (bi.bill_id = b.bill_id)
			AND (b.bill_status = ".BILL_STATUS_RESOLVED.")
			AND (sp.product_id = bi.product_id)
			AND (sb.product_id = bi.product_id)
			AND (sb.supplier_id = ss.supplier_id)
			AND (sb.batch_id = bi.batch_id)
			AND (ss.is_supplier_delivering = '$str_supplier_type')
		GROUP BY sb.supplier_id
		ORDER BY supplier_name
	";

	$qry_supplier = new Query($sql);

//echo $sql;

	$company = new Query("SELECT * FROM company WHERE 1");	

?>

<html>
<body>
	<font style="font-family:Verdana,sans-serif;">
	<table border=1 cellpadding=7 cellspacing=0>
		<tr  bgcolor=#dfdfdf>
			<td><b>Supplier</b></td>
			<td><b>Account No.</b></td>
			<td><b>Amount</b></td>
			<td><b>Commission</b></td>
			<td><b>Commission 2</b></td>
			<td><b>Commission 3</b></td>
			<td><b>Given</b></td>
			<td><b>Total Tax on<br>Buying Price</b></td>
			<td><b>Given + Tax</b></td>
    </tr>
    <?
			$flt_total_amount = 0;
			$flt_total_commission = 0;
			$flt_grand_total = 0;

			$amount = 0;
			$comm1 = 0;
			$comm2 = 0;
			$comm3 = 0;
			$given = 0;
			$tax = 0;
			$total = 0;

			for ($i=0;$i<$qry_supplier->RowCount();$i++) {

				if ($i % 2 == 1) 
					$bgcolor = "#dfdfdf";
				else 
					$bgcolor = "#ffffff"; 

				echo "<tr bgcolor=".$bgcolor.">";


				/*
					get the total tax to be paid to the supplier,
					based on the buying price
				*/
				$sql = "
					SELECT 
						sp.product_id,
						bi.price,
						bi.bprice,
						bi.batch_id,
						bi.tax_id,
						bi.discount,
						ROUND(bi.quantity + bi.adjusted_quantity, 3) AS quantity
					FROM ".Monthalize('bill')." b,
						".Monthalize('bill_items')." bi,
						stock_product sp,
						".Yearalize('stock_batch')." sb,
						".Monthalize('stock_tax')." st
					WHERE (bi.bill_id = b.bill_id)
						AND (
							(b.bill_status = ".BILL_STATUS_RESOLVED.")
							OR (b.bill_status = ".BILL_STATUS_DELIVERED.")
						)
						AND (sp.product_id = bi.product_id)
						AND (sb.product_id = bi.product_id)
						AND (sb.supplier_id = ".$qry_supplier->FieldByName('supplier_id').")
						AND (sb.batch_id = bi.batch_id)
						AND (bi.tax_id = st.tax_id)
				";

				$qry_items = new Query($sql);

				$total_tax = 0;

				for ($j=0;$j<$qry_items->RowCount();$j++) {
					
					$quantity = $qry_items->FieldByName('quantity');

					$flt_price = number_format($qry_items->FieldByName('bprice'), 2,'.','');
					if ($flt_price == 0) {
						$flt_price = getBuyingPrice($qry_items->FieldByName('product_id'), $qry_items->FieldByName('batch_id'));
						$flt_price = number_format($flt_price, 2,'.','');
					}
						
					$discount = $qry_items->FieldByName('discount');

					if ($str_include_tax == 'Y') {

						$tax_id = $qry_items->FieldByName('tax_id');
						
						if ($discount > 0) {
							$discount_price = round(($flt_price * (1 - ($discount/100))), 3);
							$tax_amount = calculateTax($quantity * $discount_price, $tax_id);
							$flt_amount = round(($quantity * $discount_price + $tax_amount), 3);
						}
						else {
							$discount_price = $flt_price;
							$tax_amount = calculateTax($flt_price * $quantity, $tax_id);
							$flt_amount = round(($quantity * $flt_price + $tax_amount), 3);
						}
						$flt_amount = number_format($flt_amount, 2, '.', '');
					}
					else {
						if ($discount > 0) {
							$flt_amount = number_format(($flt_price * (1 - ($discount/100)) * $quantity), 2, '.','');
						}
						else {
							$flt_amount = number_format(($flt_price * $quantity), 2, '.','');
						}
					}

					$total_tax += $tax_amount;

					$qry_items->Next();
				}

				if ($qry_supplier->FieldByName('is_active') == 'Y')
					echo "<td>".$qry_supplier->FieldByName('supplier_name')."</td>";
				else
					echo "<td><font color='red'>".$qry_supplier->FieldByName('supplier_name')."</font></td>";
				echo "<td align=right>".$qry_supplier->FieldByName('account_number')."</td>";
				echo "<td align=right>".$qry_supplier->FieldByName('amount')."</td>";
				echo "<td align=right>".$qry_supplier->FieldByName('commission')."</td>";
				echo "<td align=right>".$qry_supplier->FieldByName('commission2')."</td>";
				echo "<td align=right>".$qry_supplier->FieldByName('commission3')."</td>";
				echo "<td align=right>".$qry_supplier->FieldByName('total')."</td>";

				/*
					if the supplier is registed under the same trust as the company
					the tax is not applicable
				*/
				if ($qry_supplier->FieldByName('trust') == $company->FieldByName('trust')) {
					echo "<td align=right>N/A</td>";
					echo "<td align=right>".number_format( ($qry_supplier->FieldByName('total')) ,2,'.',',')."</td>";
				}
				else {
					echo "<td align=right>".number_format($total_tax,2,'.',',')."</td>";
					echo "<td align=right>".number_format( ($total_tax + $qry_supplier->FieldByName('total')) ,2,'.',',')."</td>";
				}

				echo "</tr>";

				$flt_total_amount = $flt_total_amount + $qry_supplier->FieldByName('amount');

				$flt_total_commission = $flt_total_commission +
					$qry_supplier->FieldByName('commission') +
					$qry_supplier->FieldByName('commission2') +
					$qry_supplier->FieldByName('commission3');
				$flt_grand_total = $flt_grand_total + $qry_supplier->FieldByName('total');



				$amount += $qry_supplier->FieldByName('amount');
				$comm1 += $qry_supplier->FieldByName('commission');
				$comm2 += $qry_supplier->FieldByName('commission2');
				$comm3 += $qry_supplier->FieldByName('commission3');
				$given += $qry_supplier->FieldByName('total');

				if ($qry_supplier->FieldByName('trust') == $company->FieldByName('trust')) {
					$tax += 0;
					$total += $qry_supplier->FieldByName('total');
				}
				else {
					$tax += $total_tax;
					$total += ($qry_supplier->FieldByName('total') + $total_tax);
				}


				$qry_supplier->Next();
			}

			echo "<tr>";
				echo "<td colspan=2></td>";
				echo "<td align=right>".number_format($amount, 2, '.', ',')."</td>";
				echo "<td align=right>".number_format($comm1, 2, '.', ',')."</td>";
				echo "<td align=right>".number_format($comm2, 2, '.', ',')."</td>";
				echo "<td align=right>".number_format($comm3, 2, '.', ',')."</td>";
				echo "<td align=right>".number_format($given, 2, '.', ',')."</td>";
				echo "<td align=right>".number_format($tax,2,'.',',')."</td>";
				echo "<td align=right>".number_format($total ,2,'.',',')."</td>";
			echo "</tr>";
    
    ?>

  </table>
  <br>
  </font>
</body>
</html>