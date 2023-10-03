<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	

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

	if (IsSet($_GET['action'])) {
		if ($_GET['action'] == 'load') {
			$str_display_quantity = 'delivered';
			if (IsSet($_GET['display_quantity']))
				$str_display_quantity = $_GET['display_quantity'];
			$_SESSION['order_sheet_display_quantity'] = $str_display_quantity;
		
			$str_sheet_date = date('d-m-Y');
			if (IsSet($_GET['sheet_date']))
				$str_sheet_date = $_GET['sheet_date'];
			$_SESSION['order_sheet_date'] = $str_sheet_date;
		
			$str_sheet_date_to = date('d-m-Y');
			if (IsSet($_GET['sheet_date_to']))
				$str_sheet_date_to = $_GET['sheet_date_to'];
			$_SESSION['order_sheet_date_to'] = $str_sheet_date_to;
		
			$str_include_delivered = 'N';
			if (IsSet($_GET['include_delivered']))
				$str_include_delivered = $_GET['include_delivered'];
			$_SESSION['order_sheet_include_delivered'] = $str_include_delivered;

			function getMySQLDate($str_date) {
				if ($str_date == '')
					$str_date = date('d-m-Y');
				$arr_date = explode('-', $str_date);
				return sprintf("%04d-%02d-%02d", $arr_date[2], $arr_date[1], $arr_date[0]);
			}
		
			//====================
			// load all the products for the given date
			// into an array
			//====================
			$arr_products = array();
		
			if ($str_include_delivered == 'Y')
				$str_query = "
					SELECT
						sp.product_id, sp.product_code, sp.product_description, sp.product_abbreviation,
						smu.is_decimal
					FROM ".Monthalize('bill')." b,
						".Monthalize('bill_items')." bi
					INNER JOIN stock_product sp ON (bi.product_id = sp.product_id)
					LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
					WHERE  (bi.bill_id = b.bill_id)
						AND (b.module_id = 7)
						AND (
							DATE(b.date_created) BETWEEN '".getMySQLDate($str_sheet_date)."' AND '".getMySQLDate($str_sheet_date_to)."'
						)
					GROUP BY bi.product_id
					ORDER BY sp.product_code";
			else
				$str_query = "
					SELECT
						sp.product_id, sp.product_code, sp.product_description, sp.product_abbreviation,
						smu.is_decimal
					FROM ".Monthalize('bill')." b,
						".Monthalize('bill_items')." bi
					INNER JOIN stock_product sp ON (bi.product_id = sp.product_id)
					LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
					WHERE (bi.bill_id = b.bill_id)
						AND (b.module_id = 7)
						AND (
							DATE(b.date_created) BETWEEN '".getMySQLDate($str_sheet_date)."' AND '".getMySQLDate($str_sheet_date_to)."'
						)
						AND (b.is_pending = 'Y')
						AND (b.bill_status = ".BILL_STATUS_UNRESOLVED.")
					GROUP BY bi.product_id
					ORDER BY sp.product_code";
		
			$qry_sheet = new Query($str_query);
		
			
			for ($i=0; $i<$qry_sheet->RowCount(); $i++) {
				$_SESSION['arr_order_sheet_products'][$i][0] = $qry_sheet->FieldByName('product_id');
				$_SESSION['arr_order_sheet_products'][$i][1] = $qry_sheet->FieldByName('product_code');
				$_SESSION['arr_order_sheet_products'][$i][2] = $qry_sheet->FieldByName('product_abbreviation');
				$_SESSION['arr_order_sheet_products'][$i][3] = $qry_sheet->FieldByName('product_description');
				$_SESSION['arr_order_sheet_products'][$i][4] = $qry_sheet->FieldByName('is_decimal');
				$_SESSION['arr_order_sheet_products'][$i][5] = 'Y';
				$qry_sheet->Next();
			}
		}
		else if ($_GET['action'] == 'update') {
			//====================
			// get the selected status of the products
			//====================
			$str_selected_products = '';
			if (IsSet($_GET['selected_products']))
				$str_selected_products = $_GET['selected_products'];
			$arr_result = explode(',', $str_selected_products);

			$arr_selected = array();
			for ($i=0; $i<count($arr_result); $i++) {
				$arr_temp = explode('|', $arr_result[$i]);
				$arr_selected[$i][0] = $arr_temp[0];
				$arr_selected[$i][1] = $arr_temp[1];
			}
			
			for ($i=0; $i<count($arr_selected); $i++) {
				$int_pos = get_product_index($_SESSION['arr_order_sheet_products'], $arr_selected[$i][0]);
				if ($int_pos > -1)
					$_SESSION['arr_order_sheet_products'][$int_pos][5] = $arr_selected[$i][1];
			}
			
		}
	}

	if (IsSet($_GET['toggle_select'])) {
		for ($i=0; $i<count($_SESSION['arr_order_sheet_products']); $i++) {
			$_SESSION['arr_order_sheet_products'][$i][5] = $_GET['toggle_select'];
		}
	}
?>

<script language='javascript'>
	parent.frames['order_sheet_content'].document.location = 'order_sheet_frameset.php';
</script>
