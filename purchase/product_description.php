<?
	error_reporting(E_ERROR);

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	require_once("../common/product_funcs.inc.php");

	function productDescription($strProductCode) {
		// default return string
		// this string gets returned to a javascript function in "billing_enter.php"
		$strDescription = "__NOT_FOUND";

		if ($strProductCode != 'nil') {

			
			// check whether the code exists
			if ($_SESSION['purchase_single_supplier']=='Y') {

				$sql = "
					SELECT sp.product_id, sp.product_code, sp.product_description,
						smu.measurement_unit, smu.is_decimal,
						std.definition_description, std.definition_percent
					FROM stock_product sp
					INNER JOIN stock_measurement_unit smu ON (sp.measurement_unit_id = smu.measurement_unit_id)
					INNER JOIN ".Monthalize('stock_tax_links')." stl ON (stl.tax_id = sp.tax_id)
					INNER JOIN ".Monthalize('stock_tax_definition')." std ON (std.definition_id = stl.tax_definition_id)
					WHERE (product_code = '".$strProductCode."')
						AND (sp.deleted = 'N')
						AND (sp.supplier_id = ".$_SESSION['purchase_supplier_id'].")
				";
			}
			else {

				$sql = "
					SELECT sp.product_id, sp.product_code, sp.product_description,
						smu.measurement_unit, smu.is_decimal,
						std.definition_description, std.definition_percent
					FROM stock_product sp
					INNER JOIN stock_measurement_unit smu ON (sp.measurement_unit_id = smu.measurement_unit_id)
					INNER JOIN ".Monthalize('stock_tax_links')." stl ON (stl.tax_id = sp.tax_id)
					INNER JOIN ".Monthalize('stock_tax_definition')." std ON (std.definition_id = stl.tax_definition_id)
					WHERE (product_code = '".$strProductCode."')
						AND (sp.deleted = 'N')
				";
			}

			$result_search = new Query($sql);

			if ($result_search->b_error == true)
				$strDescription = '_ERROR|_ERROR';

			$flt_buying_price = 0;
			$flt_selling_price = 0;
			
			if ($result_search->RowCount() > 0) {
				/*
					set the price
				*/
				$int_product_id = $result_search->FieldByName('product_id');
				$flt_buying_price = getBuyingPrice($int_product_id);
				$flt_selling_price = getSellingPrice($int_product_id);
				
				/*
					check whether the given code is already in the list
					if yes, return
						- the buying price
						- the selling price
						- the quantity
				*/
				$int_found = -1;
				$qty_found = 0;
				for ($i=0; $i<count($_SESSION['purchase_order_arr_items']); $i++) {
					if ($_SESSION['purchase_order_arr_items'][$i][0] == $strProductCode) {
						$int_found = $i;
						if ($_SESSION['purchase_order_arr_items'][$int_found]['is_decimal'] == 'Y')
							$qty_found = number_format($_SESSION['purchase_order_arr_items'][$int_found][1],2);
						else
							$qty_found = number_format($_SESSION['purchase_order_arr_items'][$int_found][1],0);
						$flt_buying_price = $_SESSION['purchase_order_arr_items'][$int_found]['buying_price'];
						$flt_selling_price = $_SESSION['purchase_order_arr_items'][$int_found][4];
						break;
					}
				}
				// if found, remove before proceeding
				if ($int_found > -1) {
					$_SESSION["purchase_order_arr_items"] = array_delete($_SESSION["purchase_order_arr_items"], $int_found);
				}
				
				$strDescription =
					$result_search->FieldByName('product_description')."|".
					$result_search->FieldByName('measurement_unit')."|".
					$result_search->FieldByName('is_decimal')."|".
					$int_found."|".
					$qty_found."|".
					$flt_buying_price."|".
					$flt_selling_price."|".
					$result_search->FieldByName('definition_description')."|".
					$result_search->FieldByName('definition_percent');
			}
			else
				$strDescription = '__NOT_FOUND|__NOT_FOUND';
		}
		
		// return the description of the item
		return $strDescription."|".$_SESSION['purchase_single_supplier'];
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