<?
	error_reporting(E_ALL);

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
//session_start();


	function updateBatch($strProductCode, $strBatchCode) {
		// default return string
		// this string gets returned to a javascript function in "billing_enter.php"
		$strRetVal = "nil|nil";

		// check whether the given code + batch is already entered in the array
		$int_foundAt = -1;
		for ($i=0; $i<count($_SESSION['arr_total_qty']); $i++) {
			if (($_SESSION['arr_total_qty'][$i][0] == $strProductCode) && 
				($_SESSION['arr_total_qty'][$i][1] == $strBatchCode)) {
				$flt_quantity = $_SESSION['arr_total_qty'][$i][2] + $_SESSION['arr_total_qty'][$i][5];
				$int_foundAt = $i;
				break;
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
			echo updateBatch($_GET['product_code'], $_GET['batch_code']);
			die();
		}
		else {
			die();
		}
	}
?>