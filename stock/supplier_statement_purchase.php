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



/*
	if ($str_product_code <> '') {

		$sql = "
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
			ORDER BY date_created, sp.product_code
		";
	}
	else {
*/

		$sql = "

			SELECT po.purchase_order_id, po.invoice_number, po.invoice_date, po.purchase_order_ref, po.date_received, po.date_created,
				DAYOFMONTH(po.date_created) as date_created,
				sp.product_code,
				sp.product_description,
				".$str_price.",
				pi.quantity_received AS quantity,
				ROUND(pi.quantity_received * ".$str_price.", 2) AS amount,
				smu.is_decimal,
				u.username


			FROM ".Yearalize('purchase_items')." pi

			INNER JOIN ".Yearalize('purchase_order')." po ON ( po.purchase_order_id = pi.purchase_order_id )
			INNER JOIN ".Yearalize('stock_batch')." sb ON (sb.supplier_id = ".$int_supplier_id.")
			INNER JOIN stock_product sp ON (sp.product_id = sb.product_id)
			INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			INNER JOIN user u ON (u.user_id = po.user_id)

			WHERE (sb.batch_id = pi.batch_id)

				AND ( MONTH(po.date_received) = ".$_SESSION["int_month_loaded"]." )

				AND ( po.storeroom_id =". $_SESSION["int_current_storeroom"]." )

			ORDER BY po.date_received, po.purchase_order_id, pi.purchase_item_id
		";


//	}
//echo $sql;

	$qry->Query($sql);


	
	$po_id = 0;
	$total = 0;
	$total_qty = 0;

	$data = array();

	for ($i=0;$i<$qry->RowCount();$i++) {

		// header
		if ($po_id <> $qry->FieldByName('purchase_order_id')) {

			$data[ $qry->FieldByName('purchase_order_id') ] = array();

			$data[ $qry->FieldByName('purchase_order_id') ]['header']['invoice_number'] = $qry->FieldByName('invoice_number');
			$data[ $qry->FieldByName('purchase_order_id') ]['header']['invoice_date'] = $qry->FieldByName('invoice_date');
			$data[ $qry->FieldByName('purchase_order_id') ]['header']['purchase_order_ref'] = $qry->FieldByName('purchase_order_ref');
			$data[ $qry->FieldByName('purchase_order_id') ]['header']['date_received'] = $qry->FieldByName('date_received');
			$data[ $qry->FieldByName('purchase_order_id') ]['header']['date_created'] = $qry->FieldByName('date_created');
			
			$po_id = $qry->FieldByName('purchase_order_id');
		}

		
		// rows
		$data[ $qry->FieldByName('purchase_order_id') ]['products'][$i]['product_code'] = $qry->FieldByName('product_code');
		$data[ $qry->FieldByName('purchase_order_id') ]['products'][$i]['product_description'] = $qry->FieldByName('product_description');

		if ($str_price == "sb.buying_price")
			$data[ $qry->FieldByName('purchase_order_id') ]['products'][$i]['price'] = sprintf("%01.2f", $qry->FieldByName('buying_price'));
		else
			$data[ $qry->FieldByName('purchase_order_id') ]['products'][$i]['price'] = sprintf("%01.2f", $qry->FieldByName('selling_price'));

		if ($qry->FieldByName('is_decimal') == 'Y')
			$data[ $qry->FieldByName('purchase_order_id') ]['products'][$i]['quantity'] = number_format($qry->FieldByName('quantity'), $int_decimal_places, '.', ',');
		else
			$data[ $qry->FieldByName('purchase_order_id') ]['products'][$i]['quantity'] = number_format($qry->FieldByName('quantity'),0,'.','');

		$data[ $qry->FieldByName('purchase_order_id') ]['products'][$i]['amount'] = round( floatval( $qry->FieldByName('amount') ), 3);
		$data[ $qry->FieldByName('purchase_order_id') ]['products'][$i]['username'] = $qry->FieldByName('username');			

		
		// footer
		$data[ $qry->FieldByName('purchase_order_id') ]['footer']['total'] += round( $qry->FieldByName('amount'), 3);
		$data[ $qry->FieldByName('purchase_order_id') ]['footer']['qty'] += $qry->FieldByName('quantity');


		// grand totals
		$total += $qry->FieldByName('amount');
		$total_qty += $qry->FieldByName('quantity');


		$qry->Next();

	}

//print_r($data);

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
	
				foreach ( $data as $invoice ) {


					// header

					echo "<tr bgcolor='lightgrey' class='normaltext'>";
						echo "<td width='120px'>Invoice No.:</td><td>".$invoice['header']['invoice_number']."</td>";
						echo "<td width='120px'>P.O. No.:</td><td>".$invoice['header']['purchase_order_ref']."</td>";
						echo "<td colspan='2'></td>";
					echo "</tr>";
					echo "<tr bgcolor='lightgrey' class='normaltext'>";
						echo "<td width='120px'>Invoice Dt.:</td><td>".$invoice['header']['invoice_date']."</td>";
						echo "<td width='120px'>Rcvd. Dt.:</td><td>".$invoice['header']['date_received']."</td>";
						echo "<td colspan='2'></td>";
					echo "</tr>";
					


					// products
					$i=0;
					foreach ($invoice['products'] as $row) {

						if ($i % 2 == 0)
						    $str_color="#eff7ff";
						else
						    $str_color="#deecfb";
		
						echo "<tr bgcolor=".$str_color." class='normaltext'>";
		
							echo "<td width='100px' align=right>".$row['product_code']."</td>";

							echo "<td width='300px'>".$row['product_description']."</td>";

							echo "<td width='100px' align=right>".sprintf("%01.2f", $row['price'])."</td>";

							if ($qry->FieldByName('is_decimal') == 'Y')
								echo "<td width='100px' align=right>".number_format($row['quantity'], $int_decimal_places, '.', ',')."</td>";
							else
								echo "<td width='100px' align=right>".number_format($row['quantity'],0,'.','')."</td>";

							echo "<td width='100px' align=right>".number_format($row['amount'], 2, '.', ',')."</td>";

							echo "<td width='100px'>".$row['username']."</td>";

						echo "</tr>";

						$i++;
					}
	

					// footer

					echo "<tr bgcolor='lightgrey' class='normaltext'>";
						echo "<td colspan='3'></td>";
						echo "<td align='right'>".$invoice['footer']['qty']."</td>";
						echo "<td align='right'>".number_format($invoice['footer']['total'], 2, '.', ',')."</td>";
						echo "<td></td>";
					echo "</tr>";
					echo "<tr bgcolor='grey' class='normaltext'>";
						echo "<td colspan='6'></td>";
					echo "</tr>";

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
				?>
			</td></tr>
		</table>
		
	</td></tr>
	</table>
	</font>
</body>
</html>