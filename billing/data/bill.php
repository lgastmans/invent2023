<?php

	header("Content-Type: application/json;charset=utf-8");


	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db_mysqli.php");


	$qry = $conn->query("
		SELECT product_id, product_code, product_description
		FROM stock_product
		ORDER BY product_description
	");

	$data = array();
	$i=0;
	while ($obj = $qry->fetch_object()) {

		//$data[] = $qry->FieldByName('product_description');
		
		$data[$i]["Id"] = $obj->product_id;
		$data[$i]["Code"] = $obj->product_code;
		$data[$i]["Description"] = $obj->product_description;

		$i++;
	}

	$ret = array("data"=>$data);
	

	echo json_encode($ret);
?>