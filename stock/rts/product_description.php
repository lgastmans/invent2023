<?
	error_reporting(E_ERROR);

	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");


	function productDescription($strProductCode) {
		// default return string
		// this string gets returned to a javascript function in "billing_enter.php"
		$strDescription = "__NOT_FOUND";
		$_SESSION["current_product_id"] = "";
		$_SESSION["current_code"] = "";
		$_SESSION["current_description"] = "__NOT_FOUND";

		if ($strProductCode != 'nil') {

			// check whether the code exists
			$result_search = new Query("
				SELECT sp.product_id, sp.product_code, sp.product_description, sp.is_available,
					smu.measurement_unit, smu.is_decimal
				FROM stock_product sp
					INNER JOIN stock_measurement_unit smu ON (sp.measurement_unit_id = smu.measurement_unit_id)
				WHERE (product_code = '".$strProductCode."')
					AND (sp.deleted = 'N')
					AND ((supplier_id = ".$_SESSION["current_supplier_id"].") 
					OR (supplier2_id = ".$_SESSION["current_supplier_id"].")
					OR (supplier3_id = ".$_SESSION["current_supplier_id"]."))
			");

			if ($result_search->GetErrorMessage()<>"") die ($result_search->GetErrorMessage());

			if ($result_search->RowCount() > 0) {
				$int_product_id = $result_search->FieldByName('product_id');

				if ($result_search->FieldByName('is_available') == 'N')
					$strDescription = "__NOT_AVAILABLE|". $result_search->FieldByName('measurement_unit')."|".$result_search->FieldByName('is_decimal');
				else
					$strDescription = $result_search->FieldByName('product_description')."|". $result_search->FieldByName('measurement_unit')."|".$result_search->FieldByName('is_decimal');

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
			echo productDescription($_GET['product_code']);
			die();
		}
		else {
			die("__NOT_FOUND");
		}
	}
?>