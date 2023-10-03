<?php

	header("Content-Type: application/json;charset=utf-8");

	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db_mysqli.php");

	$sql = "
		SELECT id, company, city, zip
		FROM customer
		WHERE id = ".$_GET['id'];

	$qry = $conn->query($sql);
	$obj = $qry->fetch_object();

		
	$data["id"] = $obj->id;
	$data["company"] = $obj->company;
	$data["city"] = $obj->city." ".$obj->zip;


	echo json_encode($data);
?>