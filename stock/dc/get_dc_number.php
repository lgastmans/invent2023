<?php

	if (file_exists("const.inc.php")) {
		require_once("const.inc.php");
	}
	else if (file_exists("include/const.inc.php")) {
		require_once("include/const.inc.php");
	}
	else if (file_exists("../include/const.inc.php")) {
		require_once("../include/const.inc.php");
	}
	else if (file_exists("../../include/const.inc.php")) {
		require_once("../../include/const.inc.php");
	}
	else if (file_exists("../../../include/const.inc.php")) {
		require_once("../../../include/const.inc.php");
	}

	require_once($str_application_path."include/session.inc.php");
	require_once($str_application_path."include/db.inc.php");

	function get_dc_number() {
		$str_query = "
			SELECT stock_dc_number
			FROM user_settings
			WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
		$result_set = new Query($str_query);

		if ($result_set->RowCount() > 0)
			$int_bill_number = $result_set->FieldByName('stock_dc_number') +1;
		else
			$int_bill_number = 1;

		$result_set->Query("
			UPDATE user_settings
			SET stock_dc_number = ".$int_bill_number."
			WHERE storeroom_id = ".$_SESSION['int_current_storeroom']);
		
		return $int_bill_number;
	}
	
	function get_dc_number_no_update() {
		$str_query = "
			SELECT stock_dc_number
			FROM user_settings
			WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
		$result_set = new Query($str_query);
		
		if ($result_set->RowCount() > 0)
			$int_bill_number = $result_set->FieldByName('stock_dc_number') +1;
		else
			$int_bill_number = 1;
			
		return $int_bill_number;
	}

	function recycle_bill_number($int_bill_number, $bill_type) {
		$qry = new Query("
			INSERT INTO recycled_bill_numbers
			(
				bill_type,
				bill_number
			)
			VALUES (
				".$bill_type.",
				".$int_bill_number."
			)
		");
	}
	
	/*
	if (!empty($_GET['live'])) {
		if (!empty($_GET['bill_type'])) {
			if ($_GET['live'] == 1)
				echo get_bill_number($_GET['bill_type']);
			else
				echo get_bill_number_no_update($_GET['bill_type']);
			die();
		}
		else {
			die("nil");
		}
	}
	*/
?>