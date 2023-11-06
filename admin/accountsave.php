<?php
	require_once('../include/const.inc.php');
	require_once("db_params.php");

	$RName 		= $_POST['RName']; 
	$RNumber 	= $_POST['RNumber'];
	$RType 		= $_POST['RType'];
	$RDisable 	= $_POST['RDisable'];
	$RCCID 		= $_POST['RCCID'];

	if (isset($_POST['RCCID'])) {

		// $str_query = "
		// 	DELETE FROM account_cc 
		// 	WHERE cc_id=".$RCCID;
		// $qry = new Query($str_query);
		
		$str_query = "
			SELECT cc_id
			FROM account_cc 
			WHERE cc_id=".$RCCID;
		$qry = new Query($str_query);

		/**
		 * if found, update
		 */
		if ($qry->RowCount() > 0) {

			$str_query = "
				UPDATE account_cc 
				SET account_active='N' 
				WHERE account_number='".$RNumber."' 
					AND account_type=".$Type;

			$qry->Query($str_query);

			$result = "FS account with $RCCID updated<br>";
		}
		/**
		 * else insert
		 */
		else {
		
			$str_query = "
				INSERT INTO account_cc (
					cc_id,
					account_name,
					account_number,
					account_type,
					account_enabled
				)
				VALUES (
					'" . $RCCID . "',
					'" . addslashes($RName) . "',
					'" . $RNumber . "',
					'" . $RType . "',
					'" . (($RDisable==0)?'Y':'N') . "'
				)
			";
			$qry->Query($str_query);

			$result = "FS account with $RCCID inserted<br>";
		}

		$qry->Free();
		
	}
	else
		$result = "FS account invalid, or empty, id $RCCID <br>";
	
	echo json_encode($result);

	die();
?>
