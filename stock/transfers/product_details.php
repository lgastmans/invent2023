<?
	error_reporting(E_ERROR);

	require_once("../../include/db.inc.php");
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	
	function product_details($strProductCode) {
	
		// default return string
		$strDescription = "__NOT_FOUND";

		if ($strProductCode != 'nil') {

			// check whether the code exists
			$result_search = new Query("
				SELECT sp.product_id, sp.product_code, sp.product_description, sp.tax_id, sp.is_available, 
					sp.margin_percent, smu.measurement_unit, smu.is_decimal
				FROM stock_product sp
					INNER JOIN stock_measurement_unit smu ON (sp.measurement_unit_id = smu.measurement_unit_id)
				WHERE (product_code = '".$strProductCode."')
					AND (deleted = 'N')");

			if ($result_search->GetErrorMessage()<>"") die ($result_search->GetErrorMessage());

			$description = '__NOT_FOUND';
			$buying_price = 0;
			$selling_price = 0;
			$int_margin = 0;
			$flt_stock = 0;
			$str_measurement_unit = $result_search->FieldByName('measurement_unit');
			$str_decimal = $result_search->FieldByName('is_decimal');

			if ($result_search->RowCount() > 0) {

				$qry = new Query("
					SELECT * 
					FROM ".Monthalize('stock_storeroom_product')."
					WHERE (product_id = ".$result_search->FieldByName('product_id').") 
						AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
				");
				$flt_stock = number_format($qry->FieldByName('stock_current'), 2, '.', '');
				
				if ($qry->FieldByName('use_batch_price') == 'Y') {
					$qry_prices = new Query("
						SELECT * 
						FROM ".Yearalize('stock_batch')." sb, ".Monthalize('stock_storeroom_batch')." ssb
						WHERE (sb.product_id = ".$result_search->FieldByName('product_id').") 
							AND (sb.storeroom_id = ".$_SESSION["int_current_storeroom"].") 
							AND (sb.is_active = 'Y') 
							AND (sb.status = ".STATUS_COMPLETED.") 
							AND (sb.deleted = 'N') 
							AND (ssb.product_id = sb.product_id) 
							AND (ssb.batch_id = sb.batch_id) 
							AND (ssb.storeroom_id = sb.storeroom_id) 
							AND (ssb.is_active = 'Y')
						ORDER BY date_created DESC 
						LIMIT 1
					");
					if ($qry_prices->b_error == false) {
						$buying_price = $qry_prices->FieldByName('buying_price');
						$selling_price = $qry_prices->FieldByName('selling_price');
					}
				}
				else {
					$buying_price = $qry->FieldByName('buying_price');
					$selling_price = $qry->FieldByName('sale_price');
				}
				
				$description = $result_search->FieldByName('product_description');
				$int_margin = $result_search->FieldByName('margin_percent');
			}
			$strDescription = $description."|".$buying_price."|".$selling_price."|".$int_margin."|".$flt_stock."|".$str_measurement_unit."|".$str_decimal;
		}

		// return the description of the item
		return $strDescription;
	}

	if (!empty($_GET['live'])) {
		if (!empty($_GET['product_code'])) {
			echo product_details($_GET['product_code']);
			die();
		}
		else {
			die("__NOT_FOUND");
		}
	}

?>