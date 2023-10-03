<?
	error_reporting(E_ERROR);

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");

	function setSalesPromotion($fltAmount) {
		if ($fltAmount == "del") {
			$_SESSION['sales_promotion'] = 0;
			$strReturn = "nil|nil";
		}
		else {
			$_SESSION['sales_promotion'] = $fltAmount;
			$fltGrandTotal = $_SESSION['bill_total'] - $fltAmount;
			$strReturn = number_format($fltAmount,2,'.',',')."|".number_format($fltGrandTotal,2,'.',',');
		}

		return $strReturn;
	}

	if (!empty($_GET['live'])) {
		if (!empty($_GET['salesprom_amount'])) {
			echo setSalesPromotion($_GET['salesprom_amount']);
			die();
		}
		else {
			die("nil|nil");
		}
	}
?>