<?
	error_reporting(E_ERROR);

	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");

	function productDescription($strProductCode) {
		// default return string
		// this string gets returned to a javascript function in "billing_enter.php"
		$strDescription = "__NOT_FOUND";

		if ($strProductCode != 'nil') {

			// check whether the code exists
			$result_search = new Query("
				SELECT sp.product_id, sp.product_code, sp.product_description, sp.is_available,
					smu.measurement_unit, smu.is_decimal
				FROM stock_product sp
					INNER JOIN stock_measurement_unit smu ON (sp.measurement_unit_id = smu.measurement_unit_id)
				WHERE (product_code LIKE BINARY '".$strProductCode."')
					AND (sp.deleted = 'N')
				");

			if ($result_search->GetErrorMessage()<>"") die ($result_search->GetErrorMessage());

			if ($result_search->RowCount() > 0) {
				if ($result_search->FieldByName('is_available') == 'N')
					$strDescription = "__NOT_AVAILABLE|".$result_search->FieldByName('measurement_unit')."|".$result_search->FieldByName('is_decimal');
				else {
					// check whether the given code is already in the list
					$int_found = -1;
					$qty_found = 0;
					for ($i=0; $i<count($_SESSION['order_arr_items']); $i++) {
						if ($_SESSION['order_arr_items'][$i][0] == $strProductCode) {
							$int_found = $i;
							$qty_found = $_SESSION['order_arr_items'][$int_found][1];
							break;
						}
					}
					// if found, remove before proceeding
					if ($int_found > -1) {
						$_SESSION["order_arr_items"] = array_delete($_SESSION["order_arr_items"], $int_found);
					}
					
					$strDescription = $result_search->FieldByName('product_description')."|". $result_search->FieldByName('measurement_unit')."|".$result_search->FieldByName('is_decimal')."|".$int_found."|".$qty_found;
				}
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