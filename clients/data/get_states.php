<?php

	header("Content-Type: application/json;charset=utf-8");


	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db_mysqli.php");

	$qry = $conn->query("
		SELECT id, state, code
		FROM state_codes
		ORDER BY state
	");

	$data = array();

	$i=0;

	while ($obj = $qry->fetch_object()) {

		//$data[] = $qry->FieldByName('product_description');
		
		$data[$i]["id"] = $obj->id;
		$data[$i]["state"] = $obj->state;
		$data[$i]["code"] = $obj->code;

		$i++;
	}

	echo json_encode($data);
?>