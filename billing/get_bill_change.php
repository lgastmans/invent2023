<?
	error_reporting(E_ERROR);

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");


	function getBillChange($fltReceived) {
		$fltChange = "0";

		if ($_SESSION['sales_promotion'] > 0)
			$fltChange = $fltReceived - ($_SESSION['bill_total'] - $_SESSION['sales_promotion']);
		else
			$fltChange = $fltReceived - $_SESSION['bill_total'];

		return number_format($fltChange, 2, '.', '');
	}

	if (!empty($_GET['live'])) {
		if (!empty($_GET['amount_received'])) {
			echo getBillChange($_GET['amount_received']);
			die();
		}
		else {
			die("0");
		}
	}
?>