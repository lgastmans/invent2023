<?php

	header("Content-Type: application/json;charset=utf-8");


	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db_mysqli.php");

	$qry = $conn->query("
		SELECT id, company, city, zip
		FROM customer
		ORDER BY company
	");

	$data = array();

	$i=0;

	while ($obj = $qry->fetch_object()) {

		//$data[] = $qry->FieldByName('product_description');
		
		$data[$i]["id"] = $obj->id;
		$data[$i]["company"] = $obj->company;
		$data[$i]["city"] = $obj->city." ".$obj->zip;

		$i++;
	}

	echo json_encode($data);
?>