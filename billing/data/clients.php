<?php

	header("Content-Type: application/json;charset=utf-8");


	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db_mysqli.php");

	$qry = $conn->query("
		SELECT c.id, customer_id, company, address, city, zip, gstin, contact_person, sc.state AS state
		FROM customer c
		LEFT JOIN state_codes sc ON (sc.id = c.state)
		ORDER BY company
	");

	$data = array();

	$i=0;

	while ($obj = $qry->fetch_object()) {

		//$data[] = $qry->FieldByName('product_description');
		
		$data[$i]["id"] = $obj->id;
		$data[$i]["customer_id"] = $obj->customer_id;
		$data[$i]["address"] = $obj->address;
		$data[$i]["city"] = $obj->city;
		$data[$i]["zip"] = $obj->zip;
		$data[$i]["state"] = $obj->state;
		$data[$i]["company"] = $obj->company;
		$data[$i]["gstin"] = $obj->gstin;
		$data[$i]["contact_person"] = $obj->contact_person;

		$i++;
	}

	echo json_encode($data);
?>