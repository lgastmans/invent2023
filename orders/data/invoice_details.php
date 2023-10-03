<?php
	require_once("../../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db_mysqli.php");

//	header("Content-Type: application/json;charset=utf-8");

	$int_decimals = 3;


	$invoice_id = 0;
	if (isset($_POST['invoice_id']))
		$invoice_id = $_POST['invoice_id'];

	/*
		retrieve all the product details for the selected purchase order.
	*/
	$qry = $conn->query("
		SELECT *
		FROM ".Yearalize('bill_payments')." bp
		WHERE bp.bill_id = ".$invoice_id);


	$data = array();

	$i=0;
	while ($obj = $qry->fetch_object() ) {

		$data[$i]['id'] = $obj->id;
		$data[$i]['amount'] = $obj->amount;
		$data[$i]['payment_reference'] = $obj->payment_reference;
		$data[$i]['payment_type'] = $obj->payment_type;
		$data[$i]['payment_date'] = $obj->payment_date;

		$i++;

	}


	$ret = array("data"=>$data);

	echo json_encode($ret);

?>