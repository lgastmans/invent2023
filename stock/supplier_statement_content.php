<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");

	$_SESSION["int_stock_selected"] = 6;

	if (IsSet($_GET["supplier_id"]))
		$int_supplier_id = $_GET["supplier_id"];
	else
		$int_supplier_id = 0;

	$_SESSION['global_current_supplier_id'] = $int_supplier_id;
	
	$qry = new Query("
		SELECT stock_show_returned, bill_decimal_places
		FROM user_settings
		WHERE storeroom_id = ".$_SESSION['int_current_storeroom']."
	");
	$str_show_returned = 'Y';
	$int_decimal_places = 2;
	if ($qry->RowCount() > 0) {
		$str_show_returned = $qry->FieldByName('stock_show_returned');
		$int_decimal_places = $qry->FieldByName('bill_decimal_places');
	}


	$stmnt = 'All';
	if ((IsSet($_GET["stmnt"])))
		$stmnt = $_GET["stmnt"];


	$str_product_code = '';
	if (IsSet($_GET["product_code"]))
		$str_product_code = $_GET["product_code"];
		

	if (IsSet($_GET["display_price"])) {
		if ($_GET["display_price"] == "B")
			$str_price = "sb.buying_price";
		else
			$str_price = "sb.selling_price";
	}
	else
		$str_price = "sb.buying_price";



	$str_type_clause = '';
	if ($str_show_returned == 'Y')
		$str_type_clause = "
				AND (
					(st.transfer_type = ".TYPE_RECEIVED.") AND (st.storeroom_id_to = ".$_SESSION["int_current_storeroom"].")
					OR
					(st.transfer_type = ".TYPE_RETURNED.") AND (st.storeroom_id_from = ".$_SESSION["int_current_storeroom"].")
				)";
	else
		$str_type_clause = "
				AND (st.transfer_type = ".TYPE_RECEIVED.") AND (st.storeroom_id_to = ".$_SESSION["int_current_storeroom"].")";


	$where = '';
	if ($stmnt=='Direct') {
		$where = " AND (st.module_id = 1) ";
	}	



	if ($str_product_code <> '')
		$str_qry = "
			SELECT
				DAYOFMONTH(st.date_created) as date_created,
				sp.product_code,
				sp.product_description,
				".$str_price.",
				st.transfer_quantity,
				st.transfer_type,
				ROUND(st.transfer_quantity * ".$str_price.", 2) AS amount,
				smu.is_decimal,
				u.username
			FROM 
				".Monthalize("stock_transfer")." st
			INNER JOIN user u ON (u.user_id = st.user_id)
			INNER JOIN ".Yearalize('stock_batch')." sb ON (sb.supplier_id = ".$int_supplier_id.")
			INNER JOIN stock_product sp ON (sp.product_id = sb.product_id)
			INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			WHERE (sb.batch_id = st.batch_id) 
				AND (sp.product_code = '".$str_product_code."')
				AND (sp.deleted = 'N')
				$where
				$str_type_clause
			ORDER BY date_created, st.transfer_id
		";
	else
		$str_qry = "
			SELECT
				DAYOFMONTH(st.date_created) as date_created,
				sp.product_code,
				sp.product_description,
				".$str_price.",
				st.transfer_quantity,
				st.transfer_type,
				st.module_id,
				ROUND(st.transfer_quantity * ".$str_price.", 2) AS amount,
				smu.is_decimal,
				u.username
			FROM 
				".Monthalize("stock_transfer")." st
			INNER JOIN user u ON (u.user_id = st.user_id)
			INNER JOIN ".Yearalize('stock_batch')." sb ON (sb.supplier_id = ".$int_supplier_id.")
			INNER JOIN stock_product sp ON (sp.product_id = sb.product_id)
			INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			WHERE (sb.batch_id = st.batch_id) 
				AND (sp.deleted = 'N')
				$where
				$str_type_clause
			ORDER BY date_created, st.transfer_id
		";
//echo $str_qry;
	$qry->Query($str_qry);
?>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../include/styles.css" />
    </head>
<body id='body_bgcolor' topmargin='0'>
	<font class='normaltext'>
	
	<table width='100%' cellpadding='0' cellspacing='0'>
	<tr><td align='left'>
	
		<table border=1 cellpadding=7 cellspacing=0>
			<?
				$date_current = 0;
				$total = 0;
				$total_qty = 0;
	
				for ($i=0;$i<$qry->RowCount();$i++) {
					if ($i % 2 == 0)
					    $str_color="#eff7ff";
					else
					    $str_color="#deecfb";
	
					echo "<tr bgcolor=".$str_color." class='normaltext'>";
	
					if ($date_current < $qry->FieldByName('date_created')) {
						echo "<td width='120px'>".$qry->FieldByName('date_created')."</td>";
						$date_current = $qry->FieldByName('date_created');
					}
					else
						echo "<td>&nbsp;</td>";
					echo "<td width='100px' align=right>".$qry->FieldByName('product_code')."</td>";
					echo "<td width='300px'>".$qry->FieldByName('product_description')."</td>";
					if ($str_price == "sb.buying_price")
						echo "<td width='100px' align=right>".sprintf("%01.2f", $qry->FieldByName('buying_price'))."</td>";
					else
						echo "<td width='100px' align=right>".sprintf("%01.2f", $qry->FieldByName('selling_price'))."</td>";
					if ($qry->FieldByName('is_decimal') == 'Y')
						if ($qry->FieldByname('transfer_type') == TYPE_RETURNED)
							echo "<td width='100px' align=right><font color='red'>".number_format($qry->FieldByName('transfer_quantity'), $int_decimal_places, '.', ',')."</font></td>";
						else
							echo "<td width='100px' align=right>".number_format($qry->FieldByName('transfer_quantity'), $int_decimal_places, '.', ',')."</td>";
					else
						if ($qry->FieldByname('transfer_type') == TYPE_RETURNED)
							echo "<td width='100px' align=right><font color='red'>".number_format($qry->FieldByName('transfer_quantity'),0,'.','')."</font></td>";
						else
							echo "<td width='100px' align=right>".number_format($qry->FieldByName('transfer_quantity'),0,'.','')."</td>";
					echo "<td width='100px' align=right>".$qry->FieldByName('amount')."</td>";
					echo "<td width='100px'>".$qry->FieldByName('username').$qry->FieldByname('module_id')."</td>";
					echo "</tr>";
	
					if ($qry->FieldByname('transfer_type') == TYPE_RETURNED) {
						$total -= $qry->FieldByName('amount');
						$total_qty -= $qry->FieldByName('transfer_quantity');
					}
					else {
						$total += $qry->FieldByName('amount');
						$total_qty += $qry->FieldByName('transfer_quantity');
					}	
					$qry->Next();
				}
	
			?>
		</table>
		<table border='0' cellpadding='0' cellspacing='0'>
			<tr><td>
				<?
					echo "<br>";
					echo "Total : ".sprintf("%01.2f", $total_qty)."<br>";
					echo "Value : Rs. ".sprintf("%01.2f", $total)."<br>";
					echo "<br>";
			//		echo $total_qty;
				?>
			</td></tr>
		</table>
		
	</td></tr>
	</table>
	</font>
</body>
</html>