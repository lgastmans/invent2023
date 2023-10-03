<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/tax.php");
	
	function getColumn($arr_dest, $int_definition_id) {
		$int_retval = -1;
		for ($i=0; $i<count($arr_dest); $i++) {
			if ($arr_dest[$i][0] === $int_definition_id) {
				$int_retval = $i;
				break;
			}
		}
		return $int_retval;
	}

	//======================================
	// get the completed order bills 
	//--------------------------------------
	$str_query ="
		SELECT b.*, c.company, c.discount AS customer_discount, c.tax_id AS customer_tax_id, o.handling_charge
		FROM ".Monthalize('bill')." b
		INNER JOIN ".Monthalize('orders')." o ON (o.order_id = b.module_record_id)
		LEFT JOIN customer c ON (c.id = b.CC_id)
		WHERE (module_id = 7)
			AND (b.storeroom_id = ".$_SESSION['int_current_storeroom'].")
			AND ((b.bill_status = ".BILL_STATUS_RESOLVED.") OR (b.bill_status = ".BILL_STATUS_DELIVERED."))
			AND (b.is_pending = 'N')";
	
	$qry_bills = new Query($str_query);
	
	//======================================
	// load the taxes into an array
	//--------------------------------------
	$qry_tax_headers = new Query("
		SELECT *
		FROM ".Monthalize('stock_tax_definition')."
		ORDER BY definition_type, definition_percent
	");
	$array_header = array();
	$array_header[] = array(0=>"",1=>"Sl.",2=>0);
	$array_header[] = array(0=>"",1=>"Date",2=>0);
	$array_header[] = array(0=>"",1=>"Customer",2=>0);
	$array_header[] = array(0=>"",1=>"Amount",2=>0);
	if ($qry_tax_headers->RowCount() > 0) {
		for ($i=0; $i<$qry_tax_headers->RowCount(); $i++) {
			// sales column
			unset($arr_tmp);
			$arr_tmp[] = $qry_tax_headers->FieldByName('definition_id');
			$arr_tmp[] = "Sales<br>".$qry_tax_headers->FieldByName('definition_description');
			$arr_tmp[] = $qry_tax_headers->FieldByName('definition_type');
			$array_header[] = $arr_tmp;
			if ($qry_tax_headers->FieldByName('definition_percent') > 0) {
				// % column
				unset($arr_tmp);
				$arr_tmp[] = $qry_tax_headers->FieldByName('definition_id');
				$arr_tmp[] = "Tax<br>".$qry_tax_headers->FieldByName('definition_description');
				$arr_tmp[] = $qry_tax_headers->FieldByName('definition_type');
				$array_header[] = $arr_tmp;
			}
			$qry_tax_headers->Next();
		}
	}
	$array_header[] = array(0=>"T",1=>"Total",2=>0);
	
	$array_content = array();
	$qry_items = new Query("SELECT * FROM stock_product LIMIT 1");
	
	//======================================
	// iterate through the bills
	// calculating the totals for each 
	//--------------------------------------
	for ($i=0; $i<$qry_bills->RowCount(); $i++) {
		
		$qry_items->Query("
			SELECT *
			FROM ".Monthalize('bill_items')." bi
			LEFT JOIN stock_product sp ON (sp.product_id = bi.product_id)
			WHERE bi.bill_id = ".$qry_bills->FieldByName('bill_id')."
		");
		
		$item_total = 0;
		$flt_total = 0;
		for ($j=0; $j<$qry_items->RowCount(); $j++) {
			$total_quantity = $qry_items->FieldByName('quantity') + $qry_items->FieldByName('adjusted_quantity');
			$flt_price = $qry_items->FieldByName('price');
			$item_total = $total_quantity * $flt_price;
			
			$flt_total += $item_total;
			
			$qry_items->Next();
		}
		
		$flt_discount = $flt_total * $qry_bills->FieldByName('customer_discount') / 100;
		$flt_tax = calculateTax(($flt_total - $flt_discount), $qry_bills->FieldByName('customer_tax_id'));
		
		$array_content[$i][0] = ($i+1);
		$array_content[$i][1] = date('d-m-Y', strtotime($qry_bills->FieldByName('date_created')));
		$array_content[$i][2] = $qry_bills->FieldByName('company');
		$array_content[$i][3] = $flt_total - $flt_discount + $qry_bills->FieldByName('handling_charge') + $flt_tax;
		
		$int_col = getColumn($array_header, $qry_bills->FieldByName('customer_tax_id'));
		$array_content[$i][$int_col] = ($flt_total - $flt_discount);
		$array_content[$i][$int_col+1] = $flt_tax;
		
		$int_col = getColumn($array_header, "T");
		if ($qry_bills->FieldByName('is_debit_bill') == 'Y') {
			$array_content[$i][$int_col] = (($flt_total - $flt_discount) + $flt_tax) * -1;
//			$array_content[$i][$int_col] = (($flt_total - $flt_discount) + $qry_bills->FieldByName('handling_charge') + $flt_tax) * -1;
		}
		else {
			$array_content[$i][$int_col] = ($flt_total - $flt_discount) + $flt_tax;
//			$array_content[$i][$int_col] = ($flt_total - $flt_discount) + $qry_bills->FieldByName('handling_charge') + $flt_tax;
		}
		
		$qry_bills->Next();
	}

?>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	</head>
<body id='body_bgcolor'>

<table border='1' cellpadding='5' cellspacing='0'>
	<tr bgcolor='lightgrey'>
	<?
		for ($i=0; $i<count($array_header); $i++) {
			echo "<td align='center' valign='center' class='normaltext_bold' width='80px'>".$array_header[$i][1]."</td>\n";
		}
	?>
	</tr>
	<?
		$arr_totals = array();
		$total = 0;
		
		for ($i=0;$i<count($array_content);$i++) {
			if ($i % 2 == 0)
				$str_color="#eff7ff";
			else
				$str_color="#deecfb";
			
			echo "<tr bgcolor='$str_color'>";
			echo "<td align='right' class='normaltext'>".$array_content[$i][0]."</td>";
			echo "<td class='normaltext'>".$array_content[$i][1]."</td>";
			echo "<td class='normaltext'>".$array_content[$i][2]."</td>";
			echo "<td align='right' class='normaltext'>".number_format($array_content[$i][3],2,'.','')."</td>";
			$total += number_format($array_content[$i][3],2,'.','');
			for ($j=4;$j<count($array_header);$j++) {
				if (IsSet($array_content[$i][$j])) {
					echo "<td align='right' class='normaltext'>".number_format($array_content[$i][$j],2,'.',',')."</td>\n";
					if (IsSet($arr_totals[$j]))
						$arr_totals[$j] += number_format($array_content[$i][$j],2,'.','');
					else
						$arr_totals[$j] = number_format($array_content[$i][$j],2,'.','');
				}
				else {
					if (!IsSet($arr_totals[$j]))
						$arr_totals[$j] = 0;
					echo "<td class='normaltext'>&nbsp;</td>\n";
				}
			}
			echo "</tr>\n";
		} 
    ?>
	<tr>
		<td align='right' colspan='3' class='normaltext_bold'>Totals</td>
		<td align='right' class='normaltext_bold'><?echo number_format($total,2,'.',',');?></td>
		<?
			foreach ($arr_totals as $value)
				echo "<td align='right' class='normaltext_bold'>".number_format($value,2,'.',',')."</td>";
		?>
	</tr>
</table>

</body>
</html>