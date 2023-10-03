<?php

	header("Content-Type: application/json;charset=utf-8");


	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db_mysqli.php");

	$qry = $conn->query("
		SELECT cc_id, account_number, account_name
		FROM account_cc
		WHERE account_active = 'Y' AND account_type IN (3,4)
		ORDER BY account_name
	");

	$data = array();

	$i=0;

	while ($obj = $qry->fetch_object()) {

		//$data[] = $qry->FieldByName('product_description');
		
		$data[$i]["id"] = $obj->cc_id;
		$data[$i]["account_number"] = utf8_encode($obj->account_number);
		$data[$i]["account_name"] = utf8_encode($obj->account_name);

		$i++;
	}

	echo json_encode($data);
?>