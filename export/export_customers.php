<?php
	header("Content-Type: application/text; name=customers.csv");
	header("Content-Transfer-Encoding: binary");
	header("Content-Disposition: attachment; filename=customers.csv");
	header("Expires: 0");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../common/product_funcs.inc.php");
	require_once("../include/db_mysqli.php");	
	
	$sql = "
		SELECT *
		FROM customer
		ORDER BY company
	";	
	
	$qry = $conn->query($sql);

	while ($obj = $qry->fetch_object()) {

		echo "\"".$obj->company."\"\t".
			"\"Company\"\t".
			"\"Commercial\"\t".
			"\"India\"\t".
			"\"".$obj->address."\"\t".
			"\"".$obj->address2."\"\t".
			"\"".$obj->city."\"\t".
			"\"".$obj->state."\"\t".
			"\"".$obj->zip."\"\t".
			"\"India\"\t".
			"\"".$obj->contact_person."\"\t".
			"\"".$obj->cell."\"\t".
			"\"".$obj->phone."\"\t".
			"\"".$obj->email."\"\t".
			"\"".$obj->gstin."\"\t".
			"\"1\"\t".
			"\"1\"\t".
			"\r\n";
	}



?>
