<?
	error_reporting(E_ALL);

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");

	function updateBatch($strProductCode, $strBatchCode, $is_bar_code='N') {
		// default return string
		// this string gets returned to a javascript function in "billing_enter.php"
		$strRetVal = "nil|nil";
		$flt_quantity = 0;
		
		$str_product_code = $strProductCode;

/*
		if ($is_bar_code == 'Y') {
			$qry_product = new Query("
				SELECT product_code
				FROM stock_product
				WHERE product_bar_code = '".$strProductCode."'");
			$str_product_code = $qry_product->FieldByName('product_code');
		}
*/		
		$qry_product = new Query("
			SELECT product_code
			FROM stock_product
			WHERE (product_bar_code = '".$strProductCode."') OR (product_code = '".$strProductCode."')
		");
		$str_product_code = $qry_product->FieldByName('product_code');

		// check whether the given code is already entered in the array
		$int_foundAt = -1;
		for ($i=0; $i<count($_SESSION['arr_total_qty']); $i++) {
			if ($_SESSION['arr_total_qty'][$i][0] === $str_product_code) {
				$flt_quantity += $_SESSION['arr_total_qty'][$i][2] + $_SESSION['arr_total_qty'][$i][5];
				$int_foundAt = $i;
			}
		}

		if ($int_foundAt > -1) {
			$strRetVal = $int_foundAt."|".$flt_quantity;
		}

		// return
		//		the index where the item was found
		//		the quantity of the found item
		return trim($strRetVal);
	}

	if (!empty($_GET['live'])) {
		if (!empty($_GET['product_code'])) {
			$str_is_bar_code = 'N';
			if (IsSet($_GET['is_bar_code']))
				$str_is_bar_code = $_GET['is_bar_code'];
			
			echo updateBatch($_GET['product_code'], $_GET['batch_code'], $str_is_bar_code);
			die();
		}
		else {
			die();
		}
	}
?>