<?php
	require_once("../../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db_mysqli.php");


	$data['id'] = 0;

	
	if (!empty($_POST['payment_id'])) {

		$sql = "
			DELETE FROM ".Yearalize('bill_payments')."
			WHERE id = ".$_POST['payment_id'];

		$qry = $conn->query($sql);

		$data['sql'] = $sql;
		$data['id'] = $_POST['payment_id'];
	}


	$ret = array("data"=>$data);

	echo json_encode($ret);

?>