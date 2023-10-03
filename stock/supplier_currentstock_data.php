<?
	require_once("../include/db_mysqli.php");


	$str_where = '';


	if (IsSet($_GET["supplier_id"]))
		$int_supplier_id = $_GET["supplier_id"];
	else
		$int_supplier_id = 0;
	$_SESSION['global_current_supplier_id'] = $int_supplier_id;


	$str_order = "product_code";
	if (IsSet($_GET["order_by"]))
		$str_order = $_GET["order_by"];
	
	if ($str_order == 'product_code')
		if ($code_sorting == 'ALPHA_NUM')
			$str_order .= "+0 ASC";


	$str_is_filtered = 'N';
	if (IsSet($_GET['is_filtered']))
		$str_is_filtered = $_GET['is_filtered'];
		
	if ($str_is_filtered == 'Y') {
		$str_filter_field = $_GET['filter_field'];
		$str_filter_text = $_GET['filter_text'];
		$str_where = '';
		if ($str_filter_field == 'code')
			$str_where = "AND (sp.product_code = '".$str_filter_text."')";
		else if ($str_filter_field == 'description')
			$str_where = "AND (sp.product_description LIKE '".$str_filter_text."%')";
	}
	else {
		$str_filter_field = '';
		$str_filter_text = '';
		$str_where = '';
	}


	if (IsSet($_GET['display_stock']))
		$str_display_stock = $_GET['display_stock'];
	else
		$str_display_stock = 'All';

	if ($str_display_stock == 'Below Minimum') {
		$str_where .= " AND (ssp.stock_current <= sp.minimum_qty)";
	}
	else if ($str_display_stock == 'Zero') {
		$str_where .= " AND (ssp.stock_current = 0)";
	}		
	else if ($str_display_stock == 'Non-zero') {
		$str_where .= " AND (ssp.stock_current <> 0)";
	}



	$sql = "

		SELECT sp.product_id, sp.product_code, sp.product_description, sp.mrp, sp.minimum_qty,

			sb.batch_code, sb.tax_id,

			IF (ssp.use_batch_price = 'Y',
				sb.buying_price,
				ssp.buying_price
			) AS buying_price,

			IF (ssp.use_batch_price = 'Y',
				sb.selling_price,
				ssp.sale_price
			) AS selling_price,


			ssb.stock_storeroom_batch_id, ssb.stock_available, 

			ssp.stock_current, ssp.use_batch_price, ssp.stock_adjusted, 

			st.*,

			smu.measurement_unit, smu.is_decimal,

			sc.category_description

		FROM stock_product sp

		INNER JOIN ".Monthalize('stock_storeroom_product')." ssp ON (ssp.product_id = sp.product_id)
			AND (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")

		LEFT JOIN ".Yearalize('stock_batch')." sb ON (sb.product_id = sp.product_id)
			AND (sb.status = ".STATUS_COMPLETED.")
			AND (sb.deleted = 'N')
			AND (sb.is_active = 'Y')
			AND (sb.storeroom_id = ".$_SESSION['int_current_storeroom'].")

		INNER JOIN ".Monthalize('stock_storeroom_batch')." ssb ON (ssb.batch_id = sb.batch_id)
			AND (ssb.storeroom_id = ".$_SESSION['int_current_storeroom'].")
			AND (ssb.is_active = 'Y')

		LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)

		LEFT JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = sb.tax_id)

		LEFT JOIN stock_category sc ON (sc.category_id = sp.category_id)

		WHERE (sp.supplier_id = ".$int_supplier_id.") AND (sp.deleted = 'N')

			".$str_where."

		ORDER BY ".$str_order."
	";


	$qry = $conn->Query($sql);


	$data = array();

	$i=0;

	$current_id = 0;

	$total_stock = 0;
	$total_adjusted = 0;
	$total_b_value = 0;
	$total_s_value = 0;

	while ($obj = $qry->fetch_object()) {

		$add = false;

		if ($current_id <> $obj->product_id)
			$add = true;
		
		else if (($current_id == $obj->product_id) && ($obj->use_batch_price == 'Y'))
			$add = true;


		if ($add===true) {

			$data['data'][$i]["id"] = $obj->product_id;
			$data['data'][$i]["code"] = utf8_encode($obj->product_code);
			$data['data'][$i]["description"] = utf8_encode($obj->product_description);
			$data['data'][$i]["mrp"] = number_format($obj->mrp, 2, '.', ',');

			$data['data'][$i]["batch_code"] = $obj->batch_code;

			$data['data'][$i]["buying_price"] = $obj->buying_price;
			$data['data'][$i]["selling_price"] = $obj->selling_price;

			$tax_amount = calculateTax($obj->selling_price, $obj->tax_id);
			$data['data'][$i]["price_tax"] = RoundUp(($obj->selling_price + $tax_amount));

			$data['data'][$i]['tax_description'] = $obj->tax_description;

			if ($obj->use_batch_price == 'Y') {
				$data['data'][$i]["buying_value"] = $obj->buying_price * $obj->stock_available;
				$data['data'][$i]["selling_value"] = $obj->selling_price * $obj->stock_available;
			}
			else {
				$data['data'][$i]["buying_value"] = $obj->buying_price * $obj->stock_current;
				$data['data'][$i]["selling_value"] = $obj->selling_price * $obj->stock_current;
			}

			$total_b_value += $data['data'][$i]["buying_value"];
			$total_s_value += $data['data'][$i]["selling_value"];

			$data['data'][$i]["stock_minimum"] = $obj->minimum_qty;
			
			if ($obj->use_batch_price == 'Y') {

				$data['data'][$i]["stock_current"] = "(".$obj->stock_available.") ".number_format($obj->stock_current, $int_decimal_places, '.', ',');

				$total_stock += $obj->stock_available;

			}
			else {

				$data['data'][$i]["stock_current"] = number_format($obj->stock_current, $int_decimal_places, '.', ',');

				$total_stock += $obj->stock_current;

			}

			$data['data'][$i]["stock_adjusted"] = $obj->stock_adjusted;
			$total_adjusted += $obj->stock_adjusted;

			$data['data'][$i]["use_batch_price"] = $obj->use_batch_price;

			$data['data'][$i]["is_decimal"] = $obj->is_decimal;
			$data['data'][$i]["measurement_unit"] = $obj->measurement_unit;

			$data['data'][$i]["category_description"] = $obj->category_description;

			$i++;

		}

		$current_id = $obj->product_id;

	}

	$data['total_adjusted'] += $total_adjusted;
	$data['total_stock'] += $total_stock;
	$data['total_b_value'] += $total_b_value;
	$data['total_s_value'] += $total_s_value;


?>