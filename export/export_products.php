<?php
/**
	Shradhanjali SQL to run in PhpMyAdmin

	This SQL takes the 'mrp' price from the products table
		SELECT sp.product_description, sp.product_code, sp.product_bar_code, smu.measurement_unit, sc.hsn, sp.mrp, std.definition_percent
		FROM stock_product sp
		INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
		INNER JOIN stock_category sc ON (sc.category_id = sp.category_id)
		INNER JOIN stock_tax_links_2022_9 stl ON (stl.tax_id = sp.tax_id)
		INNER JOIN stock_tax_definition_2022_9 std ON (std.definition_id = stl.tax_definition_id)
		LEFT JOIN stock_balance_2022 sb ON (sb.product_id = sp.product_id) AND (balance_month = 9) AND (balance_year = 2022) AND (sb.storeroom_id = 1)
		WHERE sp.deleted = 'N'
		ORDER BY sc.category_description, sp.product_description  

	This SQL takes the price from the stock_batch table (but showing multiple rows)
		SELECT sp.product_description, sp.product_code, sp.product_bar_code, smu.measurement_unit, sc.hsn, sb.selling_price, std.definition_percent
		FROM stock_product sp
		INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
		INNER JOIN stock_category sc ON (sc.category_id = sp.category_id)
		INNER JOIN stock_tax_links_2022_9 stl ON (stl.tax_id = sp.tax_id)
		INNER JOIN stock_tax_definition_2022_9 std ON (std.definition_id = stl.tax_definition_id)
		LEFT JOIN stock_batch_2022 sb ON (sb.product_id = sp.product_id)
		LEFT JOIN stock_balance_2022 sb ON (sb.product_id = sp.product_id) AND (balance_month = 9) AND (balance_year = 2022) AND (storeroom_id = 1)
		WHERE deleted = 'N'
		ORDER BY sc.category_description, sp.product_description;
*/

	header("Content-Type: application/text; name=products.csv");
	header("Content-Transfer-Encoding: binary");
	header("Content-Disposition: attachment; filename=products.csv");
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
		SELECT sp.product_id, sp.product_code, sp.product_description, sp.product_bar_code, std.definition_percent, smu.measurement_unit, sc.category_description, sc.hsn, sb.stock_closing_balance
		FROM stock_product sp
		INNER JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
		INNER JOIN stock_category sc ON (sc.category_id = sp.category_id)
		INNER JOIN ".Monthalize('stock_tax_links')." stl ON (stl.tax_id = sp.tax_id)
		INNER JOIN ".Monthalize('stock_tax_definition')." std ON (std.definition_id = stl.tax_definition_id)
		LEFT JOIN ".Yearalize('stock_balance')." sb ON (sb.product_id = sp.product_id) AND (balance_month = $cur_month) AND (balance_year = $cur_year) AND (storeroom_id = 1)
		WHERE deleted = 'N'
		ORDER BY sc.category_description, sp.product_description
	";	
	
	$qry = $conn->query($sql);

	while ($obj = $qry->fetch_object()) {

		$price = getSellingPrice($obj->product_id);
		$bprice = getBuyingPrice($obj->product_id);

		echo "\"".$obj->product_code."\"\t".
			"\"".$obj->category_description."\"\t".
			"\"".$obj->measurement_unit."\"\t".
			"\"".$obj->product_description."\"\t".
			"\"".$obj->hsn."\"\t".
			"\"0\"\t".
			"\"".$price."\"\t".
			"\"".$bprice."\"\t".
			"\"Stores\"\t".
			"\"".$obj->definition_percent."\"\t".
			"\"No\"\t".
			"\"\"\t".
			"\r\n";

		// 	"\"".$obj->category_description."\"\t".
		// 	"\"".$obj->product_code."\"\t".
		// 	"\"yes\"\t".
		// 	"\"".$obj->definition_percent."\"\t".
		// 	"\"exclusive\"\t".
		// 	"\"exclusive\"\t".
		// 	"\"0\"\t".
		// 	"\"".$obj->stock_closing_balance."\"\t".
		// 	"\"0\"\r\n";

		// echo "\"".$obj->product_description."\"\t".
		// 	"\"".$obj->category_description."\"\t".
		// 	"\"".$obj->stock_closing_balance."\"\t".
		// 	"\"".$price."\"\t".
		// 	"\"Without tax\"\t".
		// 	"\"".$bprice."\"\t".
		// 	"\"Without tax\"\t".
		// 	"\"".$obj->hsn."\"\t".
		// 	"\"".$obj->definition_percent."\"\t".
		// 	"\r\n";
	}



?>
