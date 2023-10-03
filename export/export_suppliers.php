<?php
	header("Content-Type: application/text; name=suppliers.csv");
	header("Content-Transfer-Encoding: binary");
	header("Content-Disposition: attachment; filename=suppliers.csv");
	header("Expires: 0");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");

	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../common/product_funcs.inc.php");
	require_once("../include/db_mysqli.php");	
	
	$cur_month = $_SESSION['int_month_loaded']; //date("n");
	$cur_year = $_SESSION['int_year_loaded']; //date("Y");

	$sql = "
		SELECT *
		FROM stock_supplier
		ORDER BY supplier_name
	";	
	
	$qry = $conn->query($sql);

	while ($obj = $qry->fetch_object()) {

		echo "\"".$obj->supplier_name."\"\t".
			"\"Company\"\t".
			"\"\"\t".
			"\"India\"\t".
			"\"".$obj->supplier_address."\"\t".
			"\"\"\t".
			"\"".$obj->supplier_city."\"\t".
			"\"".$obj->supplier_state."\"\t".
			"\"".$obj->supplier_zip."\"\t".
			"\"India\"\t".
			"\"".$obj->contact_person."\"\t".
			"\"".$obj->supplier_cell."\"\t".
			"\"".$obj->supplier_phone."\"\t".
			"\"".$obj->supplier_email."\"\t".
			"\"".$obj->gstin."\"\t".
			"\"1\"\t".
			"\"1\"\t".
			"\r\n";
	}



?>
