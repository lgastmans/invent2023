<?php

	header("Content-Type: application/json;charset=utf-8");


	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	require_once("../../include/db_mysqli.php");

	$qry = $conn->query("
		SELECT sp.product_id, sp.product_code, sp.product_description
		FROM ".Monthalize('stock_storeroom_product')." ssp
		INNER JOIN stock_product sp ON (sp.product_id = ssp.product_id)
		WHERE ssp.storeroom_id = ".$_SESSION['int_current_storeroom']."
		ORDER BY product_description
	");

	$data = array();

	$i=0;

	while ($obj = $qry->fetch_object()) {

		//$data[] = $qry->FieldByName('product_description');
		
		$data[$i]["id"] = $obj->product_id;
		$data[$i]["code"] = utf8_encode($obj->product_code);
		$data[$i]["description"] = utf8_encode($obj->product_description);

		$i++;
	}

/*
	function utf8ize($d) {
	    if (is_array($d)) {
	        foreach ($d as $k => $v) {
	            $d[$k] = utf8ize($v);
	        }
	    } else if (is_string ($d)) {
	        return utf8_encode($d);
	    }
	    return $d;
	}
*/

	echo json_encode($data);
?>