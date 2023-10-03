<?php
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	
	$ret = array();

	if (isset($_POST['bill_number'])) {

		$_SESSION['current_bill_number'] = $_POST['bill_number'];
		$ret['bill_number'] = $_POST['bill_number'];
	}

	if (isset($_POST['list_supplier'])) {

		$_SESSION['current_supplier_id'] = $_POST['list_supplier'];
		$ret['list_supplier'] = $_POST['list_supplier'];
	}

	if (isset($_POST['list_day'])) {

		$_SESSION['current_bill_day'] = $_POST['list_day'];
		$ret['list_day'] = $_POST['list_day'];
	}

	if (isset($_POST['note'])) {

		$_SESSION['current_note'] = $_POST['note'];
		$ret['note'] = $_POST['note'];
	}


	if (isset($_POST['clear_list'])) {

		unset($_SESSION["arr_item_batches"]);	
		unset($_SESSION["arr_total_qty"]);
		$_SESSION['bill_total'] = 0;

		$ret['clear'] = true;
	}


	echo json_encode($ret);
?>