<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
    require_once("../include/db.inc.php");

	$int_num_days = 1;
	$int_days = date('j', time());

	$str_query = "
		SELECT sp.product_id, sp.product_code, sp.product_description, sp.purchase_round,
			sb.stock_sold, ssp.stock_current,
			@qty_value := (
				(sb.stock_sold/$int_days * $int_num_days) - ssp.stock_current - ssp.stock_ordered
			) AS to_buy,
			CAST(
					(
						IF (smu.is_decimal='Y',
								(ROUND(@qty_value, 2) * sp.purchase_round),
								IF (ROUND(@qty_value) * sp.purchase_round > 0,
										(ROUND(@qty_value) * sp.purchase_round)
										, 0
								)
						)
					) AS DECIMAL(10,2)
				) AS rounded_value,
			smu.is_decimal
		FROM stock_product sp
		INNER JOIN ".Yearalize('stock_balance')." sb ON
			(sb.product_id = sp.product_id)
			AND (sb.storeroom_id = 1)
			AND (sb.balance_month = 5)
			AND (sb.balance_year = 2008)
		INNER JOIN ".Monthalize('stock_storeroom_product')." ssp ON
			(ssp.product_id = sp.product_id)
			AND (ssp.storeroom_id = 1)
		INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
		WHERE (sp.list_in_purchase = 'Y')
			AND (sp.deleted = 'N')
		HAVING (to_buy > 0.0)
	";
	
	$qry = new Query($str_query);
	if ($qry->b_error == true)
		echo mysql_error();
	
	echo $qry->RowCount();
?>
<html>
<body>

<table border='1'>
<?
	for ($i=0;$i<$qry->RowCount();$i++) {
		echo "<tr>";
		echo "<td>".$qry->FieldByName('product_code')."</td>";
		echo "<td>".$qry->FieldByName('product_description')."</td>";
		echo "<td>".$qry->FieldByName('stock_sold')."</td>";
		echo "<td>".$qry->FieldByName('stock_current')."</td>";
		echo "<td>".$qry->FieldByName('to_buy')."</td>";
		echo "<td>".$qry->FieldByName('rounded_value')."</td>";
		echo "</tr>";
		$qry->Next();
	}
?>
</table>

</body>
</html>