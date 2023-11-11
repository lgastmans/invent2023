<?php
	require_once("../include/const.inc.php");
	require_once("../include/db_mysqli.php");
	require_once("../include/session.inc.php");
	require_once("../common/product_funcs.inc.php");	


	$filename = "ptps_products_2021_2022.csv";


	header("Content-Type: application/text; name=".$filename);
	header("Content-Transfer-Encoding: binary");
	header("Content-Disposition: attachment; filename=".$filename);
	header("Expires: 0");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");


	$sql = '
		SELECT
			sp.product_id, sp.product_description, sp.is_av_product,
			smu.measurement_unit_id,
			sc.category_id, sc.category_description,
			ss.supplier_id, ss.supplier_name
		FROM stock_product sp
		LEFT JOIN stock_category sc ON (sc.category_id = sp.category_id)
		LEFT JOIN stock_supplier ss ON (ss.supplier_id = sp.supplier_id)
		LEFT JOIN stock_measurement_unit smu ON (smu.measurement_unit_id = sp.measurement_unit_id)
		ORDER BY product_id
	';


	$qry = $conn->Query($sql);

	$str_current = '';

  	if (!$qry) {
		$error = $conn->error;
		die($error);
	}
	else {

		echo "\"Products\"\n";

		echo "\"product id\",\"Description\",\"AV Product\",\"Unit\",\"Category_id\",\"Category\",\"supplier_id\",\"Supplier\"\n";

		while( $obj = $qry->fetch_object() ) {

			$str_current .= "\"".$obj->product_id."\";".
				"\"".$obj->product_description."\";".
				"\"".$obj->is_av_product."\";".
				"\"".$obj->measurement_unit_id."\";".
				"\"".$obj->category_id."\";".
				"\"".$obj->category_description."\";".
				"\"".$obj->supplier_id."\";".
				"\"".$obj->supplier_name."\";".
				"\"".$obj->storeroom_id."\";".
				"\n";

		}		

		echo $str_current;
		
	}
?>