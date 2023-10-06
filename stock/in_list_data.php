<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/tax.php");

	$_SESSION["int_stock_selected"] = 8;
	
	//==================
	// get user settings
	//------------------
	$code_sorting = $arr_invent_config['settings']['code_sorting'];
	
	$qry_settings = new Query("
		SELECT stock_show_available, bill_decimal_places
		FROM user_settings
		WHERE (storeroom_id = ".$_SESSION['int_current_storeroom'].")
	");
	$str_show_available = 'Y';
	$int_decimal_places = 2;
	if ($qry_settings->RowCount() > 0) {
		$str_show_available = $qry_settings->FieldByName('stock_show_available');
		$int_decimal_places = $qry_settings->FieldByName('bill_decimal_places');
	}

	$int_day = date('d', time());
	if (IsSet($_GET['selected_day']))
		$int_day = $_GET['selected_day'];
	
	$int_in_type = 'ALL';
	if (IsSet($_GET['in_type']))
		$int_in_type = $_GET['in_type'];
	
	if ($int_in_type == 'ALL')
		$str_transfer_type_filter = "
			AND (
				((st.transfer_type IN (1,5,7)) AND (st.storeroom_id_to = ".$_SESSION['int_current_storeroom']."))
				OR
				((st.transfer_type = 2) AND (st.storeroom_id_from = ".$_SESSION['int_current_storeroom']."))
				OR
				(st.transfer_type = 6)
			)";
	else if ($int_in_type == TYPE_RETURNED)
		$str_transfer_type_filter = "AND ((st.transfer_type = $int_in_type) AND (st.storeroom_id_from = ".$_SESSION['int_current_storeroom']."))";
	else if (($int_in_type == TYPE_CORRECTED) || ($int_in_type == TYPE_ADJUSTMENT))
		$str_transfer_type_filter = "
			AND (
				(st.transfer_type = $int_in_type) AND
				((st.storeroom_id_from = ".$_SESSION['int_current_storeroom'].") OR (st.storeroom_id_to = ".$_SESSION['int_current_storeroom']."))
			)";
	else
		$str_transfer_type_filter = "AND ((st.transfer_type = $int_in_type) AND (st.storeroom_id_to = ".$_SESSION['int_current_storeroom']."))";
	
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
		if ($code_sorting == 'ALPHA_NUM')
			$str_order .= "+0 ASC";

	if ($int_type == 'ALL') {
		if ($int_category_id == 'ALL') {
			$str_query = "
				SELECT sp.product_code, sp.product_description, sp.tax_id, st.transfer_quantity AS transfer_quantity, st.transfer_description,
					smu.measurement_unit, smu.is_decimal,
					sb.selling_price,
					u.username
				FROM ".Monthalize('stock_transfer')." st
				LEFT JOIN stock_product sp ON (sp.product_id = st.product_id)
				LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				LEFT JOIN ".Yearalize('stock_batch')." sb ON (sb.batch_id = st.batch_id)
				LEFT JOIN user u ON (u.user_id = st.user_id)
				WHERE (DAY(st.date_created) = $int_day)
					AND (sp.deleted = 'N') AND (sp.is_available='Y')
					$str_transfer_type_filter
				ORDER BY ".$str_order;
		}
		else {
				$str_query = "
					SELECT sp.product_code, sp.product_description, sp.tax_id, st.transfer_quantity AS transfer_quantity, st.transfer_description,
						smu.measurement_unit, smu.is_decimal,
						sb.selling_price,
						u.username
					FROM ".Monthalize('stock_transfer')." st
					LEFT JOIN stock_product sp ON (sp.product_id = st.product_id)
					LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
					LEFT JOIN ".Yearalize('stock_batch')." sb ON (sb.batch_id = st.batch_id)
					LEFT JOIN user u ON (u.user_id = st.user_id)
					WHERE (DAY(st.date_created) = $int_day)
						AND (sp.deleted = 'N')
						$str_transfer_type_filter
						AND (sp.category_id = $int_category_id)
					ORDER BY ".$str_order;
		}
	}
	else if ($int_type == '1') {
		if ($int_category_id == 'ALL') {
			$str_query = "
				SELECT sp.product_code, sp.product_description, sp.tax_id, st.transfer_quantity AS transfer_quantity, st.transfer_description,
					smu.measurement_unit, smu.is_decimal,
					sb.selling_price,
					u.username
				FROM ".Monthalize('stock_transfer')." st
				LEFT JOIN stock_product sp ON (sp.product_id = st.product_id)
				LEFT JOIN stock_category sc ON (sc.is_perishable = 'Y')
				LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				LEFT JOIN ".Yearalize('stock_batch')." sb ON (sb.batch_id = st.batch_id)
				LEFT JOIN user u ON (u.user_id = st.user_id)
				WHERE (DAY(st.date_created) = $int_day)
					AND (sp.deleted = 'N')
					$str_transfer_type_filter
					AND (sp.category_id = sc.category_id)
				ORDER BY ".$str_order;
		}
		else {
			$str_query = "
				SELECT sp.product_code, sp.product_description, sp.tax_id, st.transfer_quantity AS transfer_quantity, st.transfer_description,
					smu.measurement_unit, smu.is_decimal,
					sb.selling_price,
					u.username
				FROM ".Monthalize('stock_transfer')." st
				LEFT JOIN stock_product sp ON (sp.product_id = st.product_id)
				LEFT JOIN stock_category sc ON (sc.is_perishable = 'Y')
				LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				LEFT JOIN ".Yearalize('stock_batch')." sb ON (sb.batch_id = st.batch_id)
				LEFT JOIN user u ON (u.user_id = st.user_id)
				WHERE (DAY(st.date_created) = $int_day)
					AND (sp.deleted = 'N')
					$str_transfer_type_filter
					AND (sp.category_id = sc.category_id)
					AND (sp.category_id = $int_category_id)
				ORDER BY ".$str_order;
		}
    }
	else if ($int_type == '2') {
		if ($int_category_id == 'ALL')
			$str_query = "
				SELECT sp.product_code, sp.product_description, sp.tax_id, st.transfer_quantity AS transfer_quantity, st.transfer_description,
					smu.measurement_unit, smu.is_decimal,
					sb.selling_price,
					u.username
				FROM ".Monthalize('stock_transfer')." st
				LEFT JOIN stock_product sp ON (sp.product_id = st.product_id)
				LEFT JOIN stock_category sc ON (sc.is_perishable = 'N')
				LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				LEFT JOIN ".Yearalize('stock_batch')." sb ON (sb.batch_id = st.batch_id)
				LEFT JOIN user u ON (u.user_id = st.user_id)
				WHERE (DAY(st.date_created) = $int_day)
					AND (sp.deleted = 'N')
					$str_transfer_type_filter
					AND (sp.category_id = sc.category_id)
				ORDER BY ".$str_order;
		else
		    $str_query = "
				SELECT sp.product_code, sp.product_description, sp.tax_id, st.transfer_quantity AS transfer_quantity, st.transfer_description,
					smu.measurement_unit, smu.is_decimal,
					sb.selling_price,
					u.username
				FROM ".Monthalize('stock_transfer')." st
				LEFT JOIN stock_product sp ON (sp.product_id = st.product_id)
				LEFT JOIN stock_category sc ON (sc.is_perishable = 'N')
				LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				LEFT JOIN ".Yearalize('stock_batch')." sb ON (sb.batch_id = st.batch_id)
				LEFT JOIN user u ON (u.user_id = st.user_id)
				WHERE (DAY(st.date_created) = $int_day)
					AND (sp.deleted = 'N')
					$str_transfer_type_filter
					AND (sp.category_id = sc.category_id)
					AND (sp.category_id = $int_category_id)
				ORDER BY ".$str_order;
	}
//echo $str_query;
    $qry = new Query($str_query);
    
?>
<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	</head>

<body id='body_bgcolor' leftmargin=0 topmargin=0 marginwidth=7 marginheight=7>

    <table width='100%' border='0'>
    <tr><td align='left'>
    
    <table border='1' cellpadding='7' cellspacing='0'>
        <?
            for ($i=0;$i<$qry->RowCount();$i++) {
                if ($i % 2 == 0)
                    $str_color="#eff7ff";
                else
                    $str_color="#deecfb";

		$tax_amount = calculateTax($qry->FieldByName('selling_price'), $qry->FieldByName('tax_id'));
		$flt_price = number_format($qry->FieldByName('selling_price') + $tax_amount, 2, '.', '');

		echo "<tr bgcolor='$str_color'>";
                echo "<td width='100px' align='right' class='normaltext'>".$qry->FieldByName('product_code')."</td>";
                echo "<td width='300px' class='normaltext'>".$qry->FieldByName('product_description')."</td>";
		if ($qry->FieldByName('is_decimal') == 'Y')
		    echo "<td width='102px' align='right' class='normaltext'>".number_format($qry->FieldByName('transfer_quantity'), $int_decimal_places, '.', '')."</td>";
		else
		    echo "<td width='102px' align='right' class='normaltext'>".number_format($qry->FieldByName('transfer_quantity'), 0, '.', '')."</td>";
		echo "<td width='32px' class='normaltext'>".$qry->FieldByName('measurement_unit')."</td>";
		echo "<td width='100px' align='right' class='normaltext'>".$flt_price."</td>";
		echo "<td width='300px' class='normaltext'>".$qry->FieldByName('transfer_description')."</td>";
		echo "<td width='80px' class='normaltext'>".$qry->FieldByName('username')."</td>";
                echo "</tr>\n";
                $qry->Next();
            }
        ?>
    </table>
    
    </td></tr>
    </table>
</body>
</html>