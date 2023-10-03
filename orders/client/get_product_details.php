<?
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");

	error_reporting(E_ERROR);

	function productDescription($strProductCode) {
		$str_retval = "__NOT_FOUND";

		if ($strProductCode != 'nil') {

			$flt_current_quantity = 0;
			$is_ordered_product = 'N';

			// check whether the code has already been entered
			for ($i=0; $i<count($_SESSION['arr_order_items']); $i++) {
				if ($_SESSION['arr_order_items'][$i][1] == $strProductCode) {
					$flt_current_quantity = $_SESSION['arr_order_items'][$i][2];
					// remove the item from the array in case it is not an ordered item
					if ($_SESSION['arr_order_items'][$i][4] <> 'Y') {
						$is_ordered_product = 'N';
						$_SESSION['arr_order_items'] = array_delete($_SESSION['arr_order_items'], $i);
					}
					else
						$is_ordered_product = 'Y';
					break;
				}
			}
			
			// check whether the code exists
			$result_search = new Query("
				SELECT sp.product_id, sp.product_code, sp.product_description, sp.is_available,
					smu.measurement_unit, smu.is_decimal,
					sup.supplier_abbreviation
				FROM stock_product sp
					INNER JOIN stock_measurement_unit smu ON (sp.measurement_unit_id = smu.measurement_unit_id)
					LEFT JOIN stock_supplier sup ON (sp.supplier_id = sup.supplier_id)
				WHERE (product_code = '".$strProductCode."')
					AND (sp.deleted = 'N')
				");

			if ($result_search->GetErrorMessage() <> "") 
				die ($result_search->GetErrorMessage());

			if ($result_search->RowCount() > 0) {

				if ($result_search->FieldByName('is_available') == 'N')
					$str_retval = "__NOT_AVAILABLE|".
						$result_search->FieldByName('measurement_unit')."|".
						$result_search->FieldByName('is_decimal')."|".
						$result_search->FieldByName('supplier_abbreviation');
				else
					$str_retval = $result_search->FieldByName('product_description')."|".
						$result_search->FieldByName('measurement_unit')."|".
						$result_search->FieldByName('is_decimal')."|".
						$result_search->FieldByName('supplier_abbreviation');

				$str_retval .= "|".$flt_current_quantity."|".$is_ordered_product;
			}
		}

		// and add the session array
		$str_retval .= '[]';
		for ($i=0; $i<count($_SESSION['arr_order_items']); $i++) {
			$str_retval .= $_SESSION['arr_order_items'][$i][5]."|";
		}
		$str_retval = substr($str_retval, 0, strlen($str_retval)-1);

		// return the description of the item
		return $str_retval;
	}

	if (!empty($_GET['live'])) {
		if (!empty($_GET['product_code'])) {
			echo productDescription($_GET['product_code']);
			die();
		}
	}
?>