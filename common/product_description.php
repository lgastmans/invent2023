<?

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");


	function productDescription($strProductCode, $is_bar_code='N') {
		// default return string
		// this string gets returned to a javascript function in "billing_enter.php"
		$strDescription = "__NOT_FOUND";
		$_SESSION["current_product_id"] = "";
		$_SESSION["current_code"] = "";
		$_SESSION["current_description"] = "__NOT_FOUND";

		if ($strProductCode != 'nil') {

			// check whether the code exists
			/*
            if ($is_bar_code == 'Y')
				$result_search = new Query("
					SELECT sp.product_id, sp.product_code, sp.product_description, sp.is_available,
						smu.measurement_unit, smu.is_decimal,
						sup.supplier_abbreviation
					FROM stock_product sp
						INNER JOIN stock_measurement_unit smu ON (sp.measurement_unit_id = smu.measurement_unit_id)
						LEFT JOIN stock_supplier sup ON (sp.supplier_id = sup.supplier_id)
					WHERE (product_bar_code = '".$strProductCode."')
						AND (deleted = 'N')
					");
			else
				$result_search = new Query("
					SELECT sp.product_id, sp.product_code, sp.product_description, sp.is_available,
						smu.measurement_unit, smu.is_decimal,
						sup.supplier_abbreviation
					FROM stock_product sp
						INNER JOIN stock_measurement_unit smu ON (sp.measurement_unit_id = smu.measurement_unit_id)
						LEFT JOIN stock_supplier sup ON (sp.supplier_id = sup.supplier_id)
					WHERE (product_code LIKE BINARY '".$strProductCode."')
						AND (deleted = 'N')
					");
			*/
			// NEW
				$result_search = new Query("
					SELECT sp.product_id, sp.product_code, sp.product_description, sp.is_available,
						smu.measurement_unit, smu.is_decimal,
						sup.supplier_abbreviation
					FROM stock_product sp
						INNER JOIN stock_measurement_unit smu ON (sp.measurement_unit_id = smu.measurement_unit_id)
						LEFT JOIN stock_supplier sup ON (sp.supplier_id = sup.supplier_id)
					WHERE ((product_code LIKE BINARY '".$strProductCode."') OR (product_bar_code = '".$strProductCode."'))
						AND (deleted = 'N')
					");
			
			if ($result_search->GetErrorMessage() <> "")
				die ($result_search->GetErrorMessage());

			if ($result_search->RowCount() > 0) {
				$int_product_id = $result_search->FieldByName('product_id');

				if ($result_search->FieldByName('is_available') == 'N')
					$strDescription = "__NOT_AVAILABLE|".
						$result_search->FieldByName('measurement_unit')."|".
						$result_search->FieldByName('is_decimal')."|".
						$result_search->FieldByName('supplier_abbreviation');
				else
					$strDescription = $result_search->FieldByName('product_description')."|".
						$result_search->FieldByName('measurement_unit')."|".
						$result_search->FieldByName('is_decimal')."|".
						$result_search->FieldByName('supplier_abbreviation');

				$_SESSION["current_product_id"] = $result_search->FieldByName('product_id');
				$_SESSION["current_code"] = $result_search->FieldByName('product_code');
				$_SESSION["current_description"] = $result_search->FieldByName('product_description');
			}
		}

		// return the description of the item
		return $strDescription;
	}

	if (!empty($_GET['live'])) {
		if (!empty($_GET['product_code'])) {
			$str_is_bar_code = 'N';
			if (IsSet($_GET['is_bar_code']))
				$str_is_bar_code = $_GET['is_bar_code'];
				
			echo productDescription($_GET['product_code'], $str_is_bar_code);
			die();
		}
		else {
			die("__NOT_FOUND");
		}
	}
?>
