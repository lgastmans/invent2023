<?php
	require_once("../include/const.inc.php");
	require_once("../include/db_mysqli.php");
	require_once("../include/session.inc.php");
	require_once("../common/product_funcs.inc.php");	

	$period = "2021_1";
	$filename = "ptdc_movement".$period.".csv";


	header("Content-Type: application/text; name=".$filename);
	header("Content-Transfer-Encoding: binary");
	header("Content-Disposition: attachment; filename=".$filename);
	header("Expires: 0");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");




	$sql = '
		SELECT CASE 
			WHEN transfer_type = 1 THEN "Internal"
		    WHEN transfer_type = 2 THEN "Returned"
		    WHEN transfer_type = 3 THEN "Bill"
		    WHEN transfer_type = 4 THEN "Adjustment"
		    WHEN transfer_type = 5 THEN "Received"
		    WHEN transfer_type = 6 THEN "Corrected"
		    WHEN transfer_type = 7 THEN "Cancelled"
		    WHEN transfer_type = 8 THEN "Debit Bill"
		    WHEN transfer_type = 9 THEN "DC"
		    ELSE "Unknown"
		   END AS movement,
		ROUND(SUM(transfer_quantity),3) AS quantity, sp.product_id, st.storeroom_id_from, st.storeroom_id_to, transfer_type
		FROM stock_transfer_'.$period.' st
		INNER JOIN stock_product sp ON (sp.product_id = st.product_id)
		GROUP BY transfer_type, product_id
		ORDER BY product_id
	';


	$qry = $conn->Query($sql);

	$str_current = '';

  	if (!$qry) {
		$error = $conn->error;
		die($error);
	}
	else {

		echo "\"Stock Movement\"\n";

		echo "\"Type\",\"Quantity\",\"product id\",\"Buying Price\",\"Selling Price\",\"Storeroom from\",\"Storeroom to\"\n";

		while( $obj = $qry->fetch_object() ) {

			$buying_price = getBuyingPrice($obj->product_id);
			$selling_price = getSellingPrice($obj->product_id);

			$str_current .= 
				"\"".$obj->transfer_type."\";".
				"\"".$obj->quantity."\";".
				"\"".$obj->product_id."\";".
				"\"".$buying_price."\";".
				"\"".$selling_price."\";".
				"\"".$obj->storeroom_id_from."\";".
				"\"".$obj->storeroom_id_to."\";".
				"\n";

		}		

		echo $str_current;
		
	}
?>