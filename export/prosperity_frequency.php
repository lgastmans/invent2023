<?php
	require_once("../include/const.inc.php");
	require_once("../include/db_mysqli.php");
	require_once("../include/session.inc.php");
	require_once("../common/product_funcs.inc.php");	

	$period = "2021_4";
	$filename = "ptdc_frequency_".$period.".csv";


	header("Content-Type: application/text; name=".$filename);
	header("Content-Transfer-Encoding: binary");
	header("Content-Disposition: attachment; filename=".$filename);
	header("Expires: 0");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");




/*
	define('BILL_USE_GLOBAL', 'N');
	define('BILL_CASH', 1);
	define('BILL_ACCOUNT', 2);
	define('BILL_PT_ACCOUNT', 3);
	define('BILL_CREDIT_CARD', 4);
	define('BILL_CHEQUE', 5);
	define('BILL_TRANSFER_GOOD', 6);
	define('BILL_AUROCARD', 7);
*/


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
		   END AS payment, b.account_number, COUNT(bill_id) AS frequency, b.storeroom_id, b.aurocard_number, payment_type
		FROM bill_'.$period.' b
		GROUP BY b.payment_type, b.account_number, b.aurocard_number, b.storeroom_id
		ORDER BY b.payment_type, b.account_number
	';

	$qry = $conn->Query($sql);

	$str_current = '';

  	if (!$qry) {
		$error = $conn->error;
		die($error);
	}
	else {

		echo "\"frequency\"\n";

		echo "\"payment\",\"account\",\"frequency\"\n";

		while( $obj = $qry->fetch_object() ) {

			$account = '';
			if ($obj->payment_type==2)
				$account = $obj->account_number;
			elseif ($obj->payment_type==7)
				$account = $obj->aurocard_number;

			$str_current .= 
				"\"".$obj->payment_type."\";".
				"\"".$account."\";".
				"\"".$obj->frequency."\";".
				"\"".$obj->storeroom_id."\";".
				"\n";

		}		

		echo $str_current;
		
	}
?>