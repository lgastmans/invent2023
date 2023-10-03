<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
	$str_code = "";
	if (IsSet($_GET["code"]))
		$str_code = $_GET["code"];
	
	$str_filter = "N";
	if (IsSet($_GET["filter"])) {
		$str_filter = $_GET["filter"];
		$str_from = $_GET["from"];
		$str_to = $_GET["to"];
	}
	
	$str_filter_type = "N";
	if (IsSet($_GET["filter_type"])) {
		$str_filter_type = $_GET["filter_type"];
		$str_filter_type_value = $_GET["filter_type_value"];
	}
	
	$qry_product = new Query("
		SELECT *
		FROM stock_product sp, stock_measurement_unit mu
		WHERE product_code = '".$str_code."'
		AND (sp.deleted = 'N')
			AND (sp.measurement_unit_id = mu.measurement_unit_id)
	");
	
	if ($qry_product->RowCount() > 0) {
		$str_unit = $qry_product->FieldByName('measurement_unit');
		$int_decimals = 3;
		if ($qry_product->FieldByName('is_decimal') == 'N')
			$int_decimals = 0;
		
		$str_where = "";
		if ($str_filter == 'Y') {
			$str_where .= "
				AND (DATE(st.date_created)
						BETWEEN '".sprintf("%04d-%02d-%02d", $_SESSION["int_year_loaded"], $_SESSION["int_month_loaded"], $str_from)."'
						AND '".sprintf("%04d-%02d-%02d", $_SESSION["int_year_loaded"], $_SESSION["int_month_loaded"], $str_to)."'
					)
			";
		}
		
		if ($str_filter_type == "Y") {
			$str_where .= "
				AND (stt.transfer_type = $str_filter_type_value)
			";
		}
		
		$str_query = "
			SELECT *
			FROM ".Monthalize('stock_transfer')." st, stock_transfer_type stt, user
			WHERE (product_id = ".$qry_product->FieldByName('product_id').")
				AND (st.transfer_type = stt.transfer_type)
				AND ((st.storeroom_id_from = ".$_SESSION['int_current_storeroom'].") OR (st.storeroom_id_to = ".$_SESSION['int_current_storeroom']."))
				AND (st.user_id = user.user_id)".$str_where."
			ORDER BY date_created
		";
	
		$qry_details = new Query($str_query);
	}
?>

<html>
<head><TITLE></TITLE>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
</head>
<body id='body_bgcolor' leftmargin=5 topmargin=5>
<?
	$qry_bill = new Query("SELECT * FROM ".Monthalize('bill')." LIMIT 1");

	if ($qry_product->RowCount() > 0) {

		echo "<table border=1 cellpadding=7 cellspacing=0>";

		$flt_total_quantity = 0;

		for ($i=0;$i<$qry_details->RowCount();$i++) {

			if ($i % 2 == 0)
				$str_color="#eff7ff";
			else
				$str_color="#deecfb";

			echo "<tr bgcolor='$str_color'>";
				echo "<td width='140px' class='normaltext'>".makeHumanTime($qry_details->FieldByName('date_created'))."</td>";
				
				$str_color = 'black';
				if ($qry_details->FieldByName('module_id') == 2) {
					$qry_bill->Query("SELECT is_debit_bill FROM ".Monthalize('bill')." WHERE bill_id = ".$qry_details->FieldByName('module_record_id'));
					if ($qry_bill->RowCount() > 0)
						if ($qry_bill->FieldByName('is_debit_bill') == 'Y')
							$str_color = 'red';
				}
				echo "<td width='140px' class='normaltext'><font color='$str_color'>".$qry_details->FieldByName('transfer_type_description')."</font></td>";
				echo "<td width='100px' class='normaltext'>".number_format($qry_details->FieldByName('transfer_quantity'),$int_decimals,'.',',')." (".$str_unit.")</td>";
				echo "<td width='350px' class='normaltext'>".$qry_details->FieldByName('transfer_description')."</td>";
				$str_ref = $qry_details->FieldByName('transfer_reference');
				if (empty($str_ref))
					echo "<td width='100px' class='normaltext'>&nbsp;</td>";
				else
					echo "<td width='100px' class='normaltext'>".$str_ref."</td>";
				echo "<td width='80px' class='normaltext'>".$qry_details->FieldByName('username')."</td>";
				if ($qry_details->FieldByName('transfer_status') == 1)
					echo "<td width='100px' class='normaltext'>Requested</td>";
				else if ($qry_details->FieldByName('transfer_status') == 2)
					echo "<td width='100px' class='normaltext'>Dispatched</td>";
				else if ($qry_details->FieldByName('transfer_status') == 3)
					echo "<td width='100px' class='normaltext'>Completed</td>";
				else if ($qry_details->FieldByName('transfer_status') == 4)
					echo "<td width='100px' class='normaltext'>Cancelled</td>";
			echo "</tr>\n";
			
			$flt_total_quantity = $flt_total_quantity + number_format($qry_details->FieldByName('transfer_quantity'),3,'.','');
			
			$qry_details->Next();
		}
		
		echo "</table>";
		echo "<br>";
		echo "<font class='normaltext_bold'>Total Quantity: ".number_format($flt_total_quantity, 2, '.', ',')."</font>";
	}
?>
</body>
</html>