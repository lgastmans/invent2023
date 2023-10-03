<?php
	require_once("../../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db_mysqli.php");


	$id = 0;
	if (isset($_POST['id']))
		$id = $_POST['id'];



	$amount = 0;
	if (isset($_POST['amount']))
		$amount = $_POST['amount'];

	$payment_reference = '';
	if (isset($_POST['reference']))
		$payment_reference = $_POST['reference'];

	$payment_type = 'Other';
	if (isset($_POST['payment_type']))
		$payment_type = $_POST['payment_type'];

	$payment_date = '';
	if (isset($_POST['payment_date']))
		$payment_date = $_POST['payment_date'];



	if (!empty($_POST['payment_id'])) {

		$sql = "
			UPDATE ".Yearalize('bill_payments')."
			SET 
				amount = '".$amount."',
				payment_reference = '".$payment_reference."',
				payment_type = '".$payment_type."',
				payment_date = '".$payment_date."'
			WHERE id = ".$_POST['payment_id'];

		$qry = $conn->query($sql);

		$data['sql'] = $sql;

	}
	else {

		$sql = "
			INSERT INTO ".Yearalize('bill_payments')."
			(
				bill_id,
				amount,
				payment_reference,
				payment_type,
				payment_date
			)
			VALUES
			(
				'".$id."',
				'".$amount."',
				'".$payment_reference."',
				'".$payment_type."',
				'".$payment_date."'
			)
		";

		$qry = $conn->query($sql);
		
		$data['sql'] = $sql;
	}


	$data['amount'] = $amount;

	$ret = array("data"=>$data);

	echo json_encode($ret);

?>