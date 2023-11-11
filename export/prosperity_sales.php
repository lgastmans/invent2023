<?php
	require_once("../include/const.inc.php");
	require_once("../include/db_mysqli.php");
	require_once("../include/session.inc.php");
	require_once("../common/product_funcs.inc.php");	

	$period = "2022_4";
	$filename = "ptps_sales_".$period.".csv";


	header("Content-Type: application/text; name=".$filename);
	header("Content-Transfer-Encoding: binary");
	header("Content-Disposition: attachment; filename=".$filename);
	header("Expires: 0");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");

	$sql = '
		SELECT CASE 
			WHEN payment_type = 1 THEN "Cash"
		    WHEN payment_type = 2 THEN "Account"
		    WHEN payment_type = 3 THEN "PTDC Account"
		    WHEN payment_type = 4 THEN "Credit Card"
		    WHEN payment_type = 5 THEN "Cheque"
		    WHEN payment_type = 6 THEN "Transfer Goods"
		    WHEN payment_type = 7 THEN "Aurocard"
		    ELSE "Unknown"
		   END AS payment, b.account_number,
		bi.product_id, bi.product_description,
		ROUND(SUM(bi.quantity + bi.adjusted_quantity),3) AS quantity, bi.price, b.storeroom_id, payment_type
		FROM bill_items_'.$period.' bi
		INNER JOIN bill_'.$period.' b ON (b.bill_id = bi.bill_id)
		GROUP BY b.payment_type, bi.product_id, b.storeroom_id
		ORDER BY bi.product_id
	';

	$qry = $conn->Query($sql);

	$str_current = '';

  	if (!$qry) {
		$error = $conn->error;
		die($error);
	}
	else {

		echo "\"Sales\"\n";

		echo "\"payment\",\"product_id\",\"description\",\"Quantity\",\"Price\",\"Storeroom\"\n";

		while( $obj = $qry->fetch_object() ) {

			$str_current .= 
				"\"".$obj->payment_type."\";".
				"\"".$obj->product_id."\";".
				"\"".$obj->product_description."\";".
				"\"".$obj->quantity."\";".
				"\"".$obj->price."\";".
				"\"".$obj->storeroom_id."\";".
				"\n";

		}		

		echo $str_current;
		
	}
?>