<?php
	include("../../include/const.inc.php");
	include("../../include/session.inc.php");
	include("../../include/db.inc.php");
	include('../../admin/nusoap.php');
	require_once("../../common/tax.php");
	require_once("../../include/db_mysqli.php");

/*
	ALTER TABLE `ptps`.`bill_2024_2` ADD UNIQUE  (`storeroom_id`, `payment_type`, `bill_number`);
*/


	/*
		get duplicate transfers
		http://stackoverflow.com/questions/11694761/select-and-display-only-duplicate-records-in-mysql


		SELECT * 
		FROM bill_2024_2
		WHERE bill_id NOT IN ( 
			SELECT bill_id
			FROM bill_2024_2 
			GROUP BY bill_number, payment_type, storeroom_id
			HAVING ( COUNT(*) = 1)
		)
		ORDER BY bill_number

				
	*/
	$sql = "
		SELECT * 
		FROM ".Monthalize('bill')."
		WHERE bill_id NOT IN ( 
			SELECT bill_id
			FROM ".Monthalize('bill')." 
			GROUP BY bill_number, payment_type, storeroom_id
			HAVING ( COUNT(*) = 1)
		)
		ORDER BY bill_number
	";
	$qry = $conn->Query($sql);


	$filename = "duplicate_bills_".date('Y-m-d').".csv";

	header("Content-Type: application/text; name=".$filename);
	header("Content-Transfer-Encoding: binary");
	header("Content-Disposition: attachment; filename=".$filename);
	header("Expires: 0");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");

	$delimiter = "|";
	//$delimiter = "\t";

	$str_header = 
		'Bill'.$delimiter.
	  	'Amount'.$delimiter.
	  	'FS Account'.$delimiter.
	  	'Name'.$delimiter."\n";

	$str_data = '';

	if ($qry->num_rows > 0) {

		while ($obj = $qry->fetch_object()) {

		$str_data .= 
			$obj->bill_number.$delimiter.
			sprintf("%01.2f",$obj->total_amount,3).$delimiter.
			$obj->account_number.$delimiter.
			$obj->account_name."\n";
		}
	}
	else {
		$str_data = "There are no duplicate bills.";
	}

	echo "sep=|"."\n".$str_header.$str_data;
?>