<?php
	header("Content-Type: application/json;charset=utf-8");

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db_mysqli.php");

	$sql = "
		SELECT code
		FROM state_codes
		WHERE id = ".$_POST['value'];

	$qry = $conn->query($sql);

	$data = array();

	$obj = $qry->fetch_object();

	$data["code"] = $obj->code;

	echo json_encode($data);
?>