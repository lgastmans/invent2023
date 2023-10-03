<?
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db.inc.php");

	function update_order_quantity($str_product_code, $flt_quantity) {
		$str_retval = 'ERROR';

		// get the product details
		$qry_product = new Query("
			SELECT * 
			FROM stock_product sp
			WHERE (product_code = '".$str_product_code."')
		");

		if ($qry_product->RowCount() > 0) {
			
			// check whether the product is in the array
			// and ordered
			$int_found = -1;
			for ($i=0; $i<count($_SESSION['arr_order_items']); $i++) {
				if (($_SESSION['arr_order_items'][$i][1] == $str_product_code) && ($_SESSION['arr_order_items'][$i][4] == 'Y')){
					$int_found = $i;
					break;
				}
			}

			// if the product is an ordered product
			if ($int_found > -1) {
				// edit the entry
				$_SESSION['arr_order_items'][$int_found][0] = $qry_product->FieldByName('product_id');
				$_SESSION['arr_order_items'][$int_found][1] = $qry_product->FieldByName('product_code');
				$_SESSION['arr_order_items'][$int_found][2] = $flt_quantity;
				$_SESSION['arr_order_items'][$int_found][4] = 'Y';
				$_SESSION['arr_order_items'][$int_found][5] = 
					StuffWithBlank($qry_product->FieldByName('product_code'), 10)." ".
					PadWithBlank($qry_product->FieldByName('product_description'), 30)." ".
					StuffWithBlank($_SESSION['arr_order_items'][$int_found][3], 10)." ".
					StuffWithBlank($flt_quantity, 10);
			}
			else {
				// else add a new entry
				$int_found = count($_SESSION['arr_order_items']);

				$_SESSION['arr_order_items'][$int_found][0] = $qry_product->FieldByName('product_id');
				$_SESSION['arr_order_items'][$int_found][1] = $qry_product->FieldByName('product_code');
				$_SESSION['arr_order_items'][$int_found][2] = $flt_quantity;
				$_SESSION['arr_order_items'][$int_found][3] = 0;
				$_SESSION['arr_order_items'][$int_found][4] = 'N';
				$_SESSION['arr_order_items'][$int_found][5] = 
					StuffWithBlank($qry_product->FieldByName('product_code'), 10)." ".
					PadWithBlank($qry_product->FieldByName('product_description'), 30)." ".
					StuffWithBlank('0', 10)." ".
					StuffWithBlank($flt_quantity, 10);
			}
			
			$str_retval = '';
			for ($i=0; $i<count($_SESSION['arr_order_items']); $i++) {
				$str_retval .= $_SESSION['arr_order_items'][$i][5]."|";
			}
			$str_retval = substr($str_retval, 0, strlen($str_retval)-1);

		}

		return $str_retval;
	}


	if (!empty($_GET['live'])) {
		if (!empty($_GET['product_code'])) {
			echo update_order_quantity($_GET['product_code'], $_GET['qty']);
			die();
		}
	}
?>