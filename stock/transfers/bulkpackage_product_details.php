<?
	error_reporting(E_ERROR);

	require_once("../../include/db.inc.php");
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	
	function productDetails($strProductCode) {
	
		// default return string
		$strDescription = "__NOT_FOUND";

		if ($strProductCode != 'nil') {

			//==============================
			// check whether the code exists
			//------------------------------
			$result_search = new Query("
				SELECT sp.product_id, sp.product_code, sp.product_description, sp.tax_id, sp.is_available, 
					sp.margin_percent, sp.product_weight, smu.measurement_unit, smu.is_decimal,
					smu2.measurement_unit AS bulk_unit
				FROM stock_product sp
					INNER JOIN stock_measurement_unit smu ON (sp.measurement_unit_id = smu.measurement_unit_id)
					LEFT JOIN stock_measurement_unit smu2 ON (sp.bulk_unit_id = smu2.measurement_unit_id)
				WHERE (product_code = '".$strProductCode."')
					AND (deleted = 'N')");
			
			if ($result_search->GetErrorMessage() <> "") 
				die ($result_search->GetErrorMessage());
			
			$description = '__NOT_FOUND';
			$current_stock = 0;
			$adjusted_stock = 0;
			$flt_product_weight = 0;
			
			if ($result_search->RowCount() > 0) {
				//==================
				// get stock details
				//------------------
				$qry_stock = new Query("
					SELECT stock_current, stock_adjusted
					FROM ".Monthalize('stock_storeroom_product')."
					WHERE (product_id = ".$result_search->FieldByName('product_id').")
						AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
				");
				if ($qry_stock->RowCount() > 0) {
					$adjusted_stock = $qry_stock->FieldByName('stock_adjusted');
					$current_stock = $qry_stock->FieldByName('stock_current');
				}
				
				$description = $result_search->FieldByName('product_description');
				$flt_product_weight = $result_search->FieldByName('product_weight');
			}
			
			$tax_id = $result_search->FieldByName('tax_id');
			if ($result_search->FieldByName('is_available') == 'N')
				$is_available = "__NOT_AVAILABLE";
			else
				$is_available = "__AVAILABLE";
			
			$strDescription = 
				$description."|".
				$tax_id."|".
				$is_available."|".
				$current_stock."|".
				$adjusted_stock."|".
				$result_search->FieldByName('measurement_unit')."|".
				$flt_product_weight."|".
				$result_search->FieldByName('bulk_unit')."|".
				$result_search->FieldByName('is_decimal');
		}
		
		return $strDescription;
	}

	if (!empty($_GET['live'])) {
		if (!empty($_GET['product_code'])) {
			echo productDetails($_GET['product_code']);
			die();
		}
		else {
			die("__NOT_FOUND");
		}
	}

?>