<?
	error_reporting(E_ERROR);

	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");
	include("../../common/product_funcs.inc.php");
	

	function find_code($str_code) {
		$int_pos = -1;
		for ($i=0; $i<count($_SESSION['order_arr_items']); $i++) {
			if ($_SESSION['order_arr_items'][$i][0] == $str_code) {
				$int_pos = $i;
				break;
			}
		}
		return $int_pos;
	}
	
	function productQuantities($strProductCode, $fltBilledQty, $fltPrice) {
		// default return string
		// this string gets returned to a javascript function in "billing_enter.php"
		$strDiscount = "nil";
		
		if ($strProductCode != 'nil') {
			
			// get the product's id and description
			$result_set = new Query("
				SELECT sp.product_id, sp.product_description, smu.is_decimal
				FROM stock_product sp
				INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
				WHERE (sp.product_code = '".$strProductCode."')
					AND (sp.deleted = 'N')
				");
			if ($result_set->RowCount() > 0) {
				$int_product_id = $result_set->FieldByName('product_id');
				$str_ProductDescription = $result_set->FieldByName('product_description');
			}
			else
				$str_ProductDescription = 'Not Found';
			
			$int_pos = find_code($strProductCode);
			if ($int_pos > -1)
				$int_length = $int_pos;
			else
				$int_length = count($_SESSION["order_arr_items"]);
			
			$_SESSION["order_arr_items"][$int_length][0] = $strProductCode;
			if ($result_set->FieldByName('is_decimal') == 'Y')
				$_SESSION["order_arr_items"][$int_length][1] = number_format($fltBilledQty, 2,'.','');
			else
				$_SESSION["order_arr_items"][$int_length][1] = $fltBilledQty;
			
			$_SESSION["order_arr_items"][$int_length][2] = $str_ProductDescription;
			$_SESSION["order_arr_items"][$int_length][3] = $int_product_id;
			$_SESSION["order_arr_items"][$int_length][4] = $fltPrice; //getSellingPrice($int_product_id);

			if ($int_pos == -1)
				$_SESSION["order_arr_items"][$int_length][5] = 0;
			
			$strDiscount = 0;
			
		} // if ($strProductCode != 'nil')
		return $fltBilledQty;
	} // end of function


	if (!empty($_GET['live'])) {
		if (!empty($_GET['product_code'])) {
			echo productQuantities($_GET['product_code'], $_GET['qty'], $_GET['price']);
			die();
		}
		else {
			die("nil");
		}
	}
?>