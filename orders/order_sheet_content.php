<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
	$_SESSION["int_orders_menu_selected"] = 4;

	$str_display_quantity = $_SESSION['order_sheet_display_quantity'];
	$str_sheet_date = $_SESSION['order_sheet_date'];
	$str_sheet_date_to = $_SESSION['order_sheet_date_to'];
	$str_include_delivered = $_SESSION['order_sheet_include_delivered'];

	function getMySQLDate($str_date) {
		if ($str_date == '')
			$str_date = date('d-m-Y');
		$arr_date = explode('-', $str_date);
		return sprintf("%04d-%02d-%02d", $arr_date[2], $arr_date[1], $arr_date[0]);
	}

	function get_product_index($arr_search, $int_product_id) {
		$int_retval = -1;
		for ($i=0; $i<count($arr_search); $i++) {
			if ($arr_search[$i][0] == $int_product_id) {
				$int_retval = $i;
				break;
			}
		}
		return $int_retval;
	}
	
	//======================================================================
	// generate mysql string for the products to be included
	//----------------------------------------------------------------------
	$str_products_clause = '';
	$str_tmp_clause = '';
//	if (count($arr_selected) > 0)
		$str_products_clause = ' AND (bi.product_id IN (';
	for ($i=0; $i<count($_SESSION['arr_order_sheet_products']); $i++) {
		if ($_SESSION['arr_order_sheet_products'][$i][5] == 'Y')
			$str_tmp_clause .= $_SESSION['arr_order_sheet_products'][$i][0].", ";
	}
	$str_products_clause .= substr($str_tmp_clause, 0, strlen($str_tmp_clause)-2);
//	if (count($arr_selected) > 0)
		$str_products_clause .= "))";

	//======================================================================
	// get all the order bills for the given date
	//----------------------------------------------------------------------
	if ($str_include_delivered == 'Y')
		$str_query = "
			SELECT
				*
			FROM ".Monthalize('bill')." b, ".Monthalize('orders')." o
			LEFT JOIN communities c ON (c.community_id = o.community_id)
			WHERE (b.module_id = 7)
				AND (
					DATE(b.date_created) BETWEEN '".getMySQLDate($str_sheet_date)."' AND '".getMySQLDate($str_sheet_date_to)."'
				)
				AND (b.module_record_id = o.order_id)
				AND (c.is_individual = 'Y')
				AND (b.bill_status <> ".BILL_STATUS_CANCELLED.")
			ORDER BY account_name";
	else
		$str_query = "
			SELECT
				*
			FROM ".Monthalize('bill')." b, ".Monthalize('orders')." o
			LEFT JOIN communities c ON (c.community_id = o.community_id)
			WHERE (b.module_id = 7)
				AND (
					DATE(b.date_created) BETWEEN '".getMySQLDate($str_sheet_date)."' AND '".getMySQLDate($str_sheet_date_to)."'
				)
				AND (b.is_pending = 'Y')
				AND ((b.bill_status = ".BILL_STATUS_UNRESOLVED.") || (b.bill_status <> ".BILL_STATUS_CANCELLED."))
				AND (b.module_record_id = o.order_id)
				AND (c.is_individual = 'Y')
			ORDER BY account_name";

	$qry_bills = new Query($str_query);

	//======================================================================
	// for each bill, load the quantities into an array
	//----------------------------------------------------------------------
	$arr_data = array();
	$qry_products = new Query("SELECT * FROM ".Monthalize('bill_items')." LIMIT 1");

	for ($i=0; $i<$qry_bills->RowCount(); $i++) {
		$arr_data[$i][0] = $qry_bills->FieldByName('account_number');
		if ($qry_bills->FieldByName('payment_type') == BILL_CASH)
			$arr_data[$i][1] = substr($qry_bills->FieldByName('note'), 0, 15);
		else
			$arr_data[$i][1] = $qry_bills->FieldByName('account_name');

		$qry_products->Query("
			SELECT *
			FROM ".Monthalize('bill_items')." bi
			WHERE (bi.bill_id = ".$qry_bills->FieldByName('bill_id').")
				".$str_products_clause."
		");
		
		for ($j=0; $j<$qry_products->RowCount(); $j++) {
			$int_index = get_product_index($_SESSION['arr_order_sheet_products'], $qry_products->FieldByName('product_id'));
			if ($int_index > -1) {
				if ($str_display_quantity == 'delivered')
					$arr_data[$i][$int_index+2] = $qry_products->FieldByName('quantity') + $qry_products->FieldByName('adjusted_quantity');
				else
					$arr_data[$i][$int_index+2] = $qry_products->FieldByName('quantity_ordered');
			}
			$qry_products->Next();
		}
		
		$qry_bills->Next();
	}

	//======================================================================
	// calculate the totals for each column
	//----------------------------------------------------------------------
	$int_last_row = count($arr_data);
	$arr_data[$int_last_row][0] = '';
	$arr_data[$int_last_row][1] = '';
	for ($j=0; $j<count($_SESSION['arr_order_sheet_products']); $j++)
		$arr_data[$int_last_row][$j+2] = 0;

	for ($i=0; $i<count($arr_data)-1; $i++) {
		for ($j=0; $j<count($_SESSION['arr_order_sheet_products']); $j++) {
			if (IsSet($arr_data[$i][$j+2])) 
				$arr_data[$int_last_row][$j+2] += number_format($arr_data[$i][$j+2],2,'.','');
		}
	}

	//======================================================================
	// check whether the decimal part of a number is greater than zero
	//----------------------------------------------------------------------
        function hasDecimalPart($aNumber) {
		$decimal_part = $aNumber - intval($aNumber);
		$decimal_part = $decimal_part * 1000;
		$decimal_part = intval($decimal_part);
		return ($decimal_part > 0);
	}

	//======================================================================
	// get the totals per product per community
	//----------------------------------------------------------------------
	if ($str_include_delivered == 'Y')
		$str_query = "
			SELECT sp.product_id, sp.product_code, sp.product_description,
				c.community_name,
				sp.product_abbreviation,
				smu.is_decimal,
				SUM(bi.quantity + bi.adjusted_quantity) AS quantity,
				SUM(bi.quantity_ordered) AS quantity_ordered
			FROM ".Monthalize('orders')." o
			INNER JOIN ".Monthalize('bill')." b ON (b.module_id = 7)
			INNER JOIN ".Monthalize('bill_items')." bi ON (bi.bill_id = b.bill_id)
			INNER JOIN stock_product sp ON (bi.product_id = sp.product_id)
			LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			LEFT JOIN communities c ON (c.community_id = o.community_id)
			WHERE (DATE(b.date_created) BETWEEN '".getMySQLDate($str_sheet_date)."' AND '".getMySQLDate($str_sheet_date_to)."')
				AND (b.module_record_id = o.order_id)
				AND (b.bill_status <> ".BILL_STATUS_CANCELLED.")
				AND (c.is_individual = 'N')
			GROUP BY bi.product_id, c.community_id
			ORDER BY c.community_name, sp.product_code";
	else
		$str_query = "
			SELECT sp.product_id, sp.product_code, sp.product_description,
				c.community_name,
				sp.product_abbreviation,
				smu.is_decimal,
				SUM(bi.quantity + bi.adjusted_quantity) AS quantity,
				SUM(bi.quantity_ordered) AS quantity_ordered
			FROM ".Monthalize('orders')." o
			INNER JOIN ".Monthalize('bill')." b ON (b.module_id = 7)
			INNER JOIN ".Monthalize('bill_items')." bi ON (bi.bill_id = b.bill_id)
			INNER JOIN stock_product sp ON (bi.product_id = sp.product_id)
			LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
			LEFT JOIN communities c ON (c.community_id = o.community_id)
			WHERE (DATE(b.date_created) BETWEEN '".getMySQLDate($str_sheet_date)."' AND '".getMySQLDate($str_sheet_date_to)."')
				AND (b.is_pending = 'Y') 
				AND ((b.bill_status = 1) || (b.bill_status <> ".BILL_STATUS_CANCELLED."))
				AND (b.module_record_id = o.order_id)
				AND (c.is_individual = 'N')
			GROUP BY bi.product_id, c.community_id
			ORDER BY c.community_name, sp.product_code";

	$qry_orders = new Query($str_query);

	$arr_communities = array();
	$str_community = '';
	$int_row = -1;
	for ($i=0; $i<$qry_orders->RowCount(); $i++) {

		if ($str_community <> $qry_orders->FieldByName('community_name')) {
			$int_row++;
			$arr_communities[$int_row][0] = $qry_orders->FieldByName('community_name');
		}

		$int_index = get_product_index($_SESSION['arr_order_sheet_products'], $qry_orders->FieldByName('product_id'));
		if ($int_index > -1) {
			if ($str_display_quantity == 'delivered')
				$arr_communities[$int_row][$int_index+1] = $qry_orders->FieldByName('quantity');
			else
				$arr_communities[$int_row][$int_index+1] = $qry_orders->FieldByName('quantity_ordered');
		}

		$str_community = $qry_orders->FieldByName('community_name');
		$qry_orders->Next();
	}

	//======================================================================
	// calculate the grand totals
	//----------------------------------------------------------------------
	$int_last = count($arr_communities);
	$arr_communities[$int_last][0] = '';
	for ($j=0; $j<count($_SESSION['arr_order_sheet_products']); $j++)
		$arr_communities[$int_last][$j+1] = $arr_data[$int_last_row][$j+2];

	for ($i=0; $i<count($arr_communities)-1; $i++) {
		for ($j=0; $j<count($_SESSION['arr_order_sheet_products']); $j++) {
			if (IsSet($arr_communities[$i][$j+1])) 
				$arr_communities[$int_last][$j+1] += number_format($arr_communities[$i][$j+1],2,'.','');
		}
	}
?>


<html>
<body>

	<font style="font-family:Verdana,sans-serif;">
	<table border='1' cellpadding='7' cellspacin='0'>
		<tr>
			<td><b>Number</b></td>
			<td><b>Name</b></td>
			<?
			//====================
			// display the products as the table header
			//====================
			for ($i=0; $i<count($_SESSION['arr_order_sheet_products']); $i++) {
				if ($_SESSION['arr_order_sheet_products'][$i][5] == 'Y') {
					echo "<td>";
					if ($_SESSION['arr_order_sheet_products'][$i][2] == '')
						echo "<b>".$_SESSION['arr_order_sheet_products'][$i][1]."</b>";
					else
						echo "<b>".$_SESSION['arr_order_sheet_products'][$i][2]."</b>";
					echo "</td>";
				}
			}
			?>
		</tr>
		<?
			//====================
			// display the accounts
			//====================
			for ($i=0; $i<count($arr_data)-1; $i++) {
				echo "<tr>";
				echo "<td align='right'>".$arr_data[$i][0]."</td>";
				echo "<td>".$arr_data[$i][1]."</td>";
				
				for ($j=0; $j<count($_SESSION['arr_order_sheet_products']); $j++) {
					if ($_SESSION['arr_order_sheet_products'][$j][5] == 'Y') {
						if (IsSet($arr_data[$i][$j+2])) {
							if ($_SESSION['arr_order_sheet_products'][$j][4] == 'Y')
								if (hasDecimalPart($arr_data[$i][$j+2]))
									echo "<td align='right'>".number_format($arr_data[$i][$j+2], 2, '.', '')."</td>";
								else
									echo "<td align='right'>".number_format($arr_data[$i][$j+2], 0, '.', '')."</td>";
							else
								echo "<td align='right'>".number_format($arr_data[$i][$j+2], 0, '.', '')."</td>";
							}
						else
							echo "<td>&nbsp;</td>";
					}
				}
				echo "</tr>";
			}
		?>
		<tr>
			<td align='right' colspan='2'><b>Totals</b></td>
			<?
			//====================
			// display the totals
			//====================
			for ($i=0; $i<count($_SESSION['arr_order_sheet_products']); $i++) {
				if ($_SESSION['arr_order_sheet_products'][$i][5] == 'Y') {
					echo "<td align='right'>";
					if (hasDecimalPart($arr_data[$int_last_row][$i+2]))
						echo "<b>".number_format($arr_data[$int_last_row][$i+2],2,'.','')."</b>";
					else
						echo "<b>".number_format($arr_data[$int_last_row][$i+2],0,'.','')."</b>";
					echo "</td>";
				}
			}
			?>
		</tr>
		
		<tr>
			<td colspan='<?echo 2+count($_SESSION['arr_order_sheet_products']);?>'><b>Community</b></td>
		</tr>
		
		<?
			for ($i=0; $i<count($arr_communities)-1; $i++) {
				echo "<tr>";
				echo "<td colspan='2'>".$arr_communities[$i][0]."</td>";
				for ($j=0; $j<count($_SESSION['arr_order_sheet_products']); $j++) {
					if ($_SESSION['arr_order_sheet_products'][$j][5] == 'Y') {
						echo "<td align='right'>";
						if (IsSet($arr_communities[$i][$j+1])) {
							if (hasDecimalPart($arr_communities[$i][$j+1]))
								echo "<b>".number_format($arr_communities[$i][$j+1],2,'.','')."</b>";
							else
								echo "<b>".number_format($arr_communities[$i][$j+1],0,'.','')."</b>";
						}
						else
							echo "&nbsp;";
						echo "</td>";
					}
				}
				echo "</tr>";
			}
		?>

		<tr>
			<td align='right' colspan='2'><b>Grand Totals</b></td>
			<?
			//====================
			// display the grand totals
			//====================
			for ($i=0; $i<count($_SESSION['arr_order_sheet_products']); $i++) {
				if ($_SESSION['arr_order_sheet_products'][$i][5] == 'Y') {
					echo "<td align='right'>";
					if (hasDecimalPart($arr_communities[$int_last][$i+1]))
						echo "<b>".number_format($arr_communities[$int_last][$i+1],2,'.','')."</b>";
					else
						echo "<b>".number_format($arr_communities[$int_last][$i+1],0,'.','')."</b>";
					echo "</td>";
				}
			}
			?>
		</tr>

	</table>

	</font>

</body>
</html>