<?
	error_reporting(E_ERROR);

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	include("../common/product_funcs.inc.php");


	function productQuantities($strProductCode, $fltBilledQty, $flt_bprice, $flt_sprice) {
		// default return string
		// this string gets returned to a javascript function in "billing_enter.php"
		$strDiscount = "nil";
		
		if ($strProductCode != 'nil') {
			
			// get the product's id and description
			$result_set = new Query("
				SELECT product_id, product_description, supplier_id, tax_id,
					smu.measurement_unit, smu.is_decimal
				FROM stock_product sp
				INNER JOIN stock_measurement_unit smu ON (sp.measurement_unit_id = smu.measurement_unit_id)
				WHERE (sp.product_code = '".$strProductCode."')
					AND (sp.deleted = 'N')
				");
			if ($result_set->RowCount() > 0) {
				$int_product_id = $result_set->FieldByName('product_id');
				$str_ProductDescription = $result_set->FieldByName('product_description');
				$int_supplier_id = $result_set->FieldByName('supplier_id');
			}
			else {
				$int_product_id = 0;
				$str_ProductDescription = 'Not Found';
				$int_supplier_id = 0;
			}
			
			$int_length = count($_SESSION["purchase_order_arr_items"]);
			
			$_SESSION["purchase_order_arr_items"][$int_length][0] = $strProductCode;
			$_SESSION["purchase_order_arr_items"][$int_length][1] = number_format($fltBilledQty, 3,'.','');
			$_SESSION["purchase_order_arr_items"][$int_length][2] = $str_ProductDescription;
			$_SESSION["purchase_order_arr_items"][$int_length][3] = $int_product_id;
			$_SESSION["purchase_order_arr_items"][$int_length][4] = $flt_sprice; //getSellingPrice($int_product_id);
			$_SESSION["purchase_order_arr_items"][$int_length][5] = $int_supplier_id;
			$_SESSION["purchase_order_arr_items"][$int_length]['is_decimal'] = $result_set->FieldByName('is_decimal');
			$_SESSION["purchase_order_arr_items"][$int_length]['buying_price'] = $flt_bprice;
			$_SESSION["purchase_order_arr_items"][$int_length]['tax_id'] = $result_set->FieldByName('tax_id');
			
			$strDiscount = 0;
		} // if ($strProductCode != 'nil')
		
		return $fltBilledQty."|".$int_product_id;
	} // end of function

	if (!empty($_GET['live'])) {
		if (!empty($_GET['product_code'])) {
			echo productQuantities(
				$_GET['product_code'],
				$_GET['qty'],
				$_GET['bprice'],
				$_GET['sprice']
			);
			die();
		}
		else {
			die("nil");
		}
	}
?>