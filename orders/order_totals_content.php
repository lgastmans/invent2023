<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
	$_SESSION["int_orders_menu_selected"] = 3;
	
	$str_totals_date = date('d-m-Y');
	if (IsSet($_GET['totals_date']))
		$str_totals_date = $_GET['totals_date'];
	
	$str_include_delivered = 'N';
	if (IsSet($_GET['include_delivered']))
		$str_include_delivered = $_GET['include_delivered'];

	function getMySQLDate($str_date) {
		if ($str_date == '')
			$str_date = date('d-m-Y');
		$arr_date = explode('-', $str_date);
		return sprintf("%04d-%02d-%02d", $arr_date[2], $arr_date[1], $arr_date[0]);
	}

	if ($str_include_delivered == 'Y')
		$str_totals = "
			SELECT
				sp.product_code, sp.product_description, SUM(bi.quantity_ordered) AS total,
				smu.measurement_unit
			FROM ".Monthalize('bill')." b,
				".Monthalize('bill_items')." bi,
				stock_product sp
			LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			WHERE (bi.product_id = sp.product_id)
				AND (bi.bill_id = b.bill_id)
				AND (b.module_id = 7)
				AND (DATE(b.date_created) = '".getMySQLDate($str_totals_date)."')
			GROUP BY bi.product_id
			ORDER BY sp.product_description";
	else
		$str_totals = "
			SELECT
				sp.product_code, sp.product_description, SUM(bi.quantity_ordered) AS total,
				smu.measurement_unit
			FROM ".Monthalize('bill')." b,
				".Monthalize('bill_items')." bi,
				stock_product sp
			LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			WHERE (bi.product_id = sp.product_id)
				AND (bi.bill_id = b.bill_id)
				AND (b.module_id = 7)
				AND (DATE(b.date_created) = '".getMySQLDate($str_totals_date)."')
				AND (b.is_pending = 'Y')
				AND (b.bill_status = ".BILL_STATUS_UNRESOLVED.")
			GROUP BY bi.product_id
			ORDER BY sp.product_description";
	$qry_totals = new Query($str_totals);
?>

<html>
<body>

	<font style="font-family:Verdana,sans-serif;">
	<table border='1' cellpadding='7' cellspacin='0'>
		<tr>
		<td align="right"><b>Code</b></td>
		<td><b>Description</b></td>
		<td><b>Ordered</b></td>
		<td>&nbsp;</td>
		</tr>
		<?
		for ($i=0; $i<$qry_totals->RowCount(); $i++) {
			echo "<tr>";
			echo "<td align='right'>".$qry_totals->FieldByName('product_code')."</td>";
			echo "<td>".$qry_totals->FieldByName('product_description')."</td>";
			echo "<td align='right'>".number_format($qry_totals->FieldByName('total'), 3, '.', '')."</td>";
			echo "<td>".$qry_totals->FieldByName('measurement_unit')."</td>";
			echo "</tr>";
	
			$qry_totals->Next();
		}
		?>
	</table>
	</font>
    
</body>
</html>