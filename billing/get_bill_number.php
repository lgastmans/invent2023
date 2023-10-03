<?

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

	function get_bill_number($bill_type, $increment='Y') {
		if ($increment == 'Y') {
			//=================================================
			// check whether there are bills in the recycle bin
			//-------------------------------------------------
			$result_set = new Query("
				SELECT *
				FROM recycled_bill_numbers
				WHERE bill_type = $bill_type
				ORDER BY bill_number
			");
			if ($result_set->RowCount() > 0) {
				$int_bill_number = $result_set->FieldByName('bill_number');
				
				$result_set->Query("
					DELETE FROM recycled_bill_numbers
					WHERE bill_number = ".$int_bill_number."
						AND bill_type = ".$bill_type."
					LIMIT 1
				");
				
				return $int_bill_number;
			}
		}
		else
			$result_set = new Query("SELECT bill_cash_bill_number FROM user_settings LIMIT 1");
		
		//=========================
		// get the last bill number
		//-------------------------
		if (BILL_USE_GLOBAL == 'Y') {

			$str_query = "SELECT bill_global_bill_number FROM user_settings WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
			$result_set->Query($str_query);
			
			if ($increment == 'Y') {
				if ($result_set->RowCount() > 0)
					$int_bill_number = $result_set->FieldByName('bill_global_bill_number') +1;
				else
					$int_bill_number = 1;
			}
			else {
				$int_bill_number = 0;
				if ($result_set->RowCount() > 0)
					if ($result_set->FieldByName('bill_global_bill_number') > 0)
						$int_bill_number = $result_set->FieldByName('bill_global_bill_number') -1;
			}
			
			$result_set->Query("UPDATE user_settings SET bill_global_bill_number = ".$int_bill_number." WHERE storeroom_id = ".$_SESSION['int_current_storeroom']);
		}
		elseif ($bill_type == BILL_CASH) {
			$str_query = "SELECT bill_cash_bill_number FROM user_settings WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
			$result_set->Query($str_query);
			
			if ($increment == 'Y') {
				if ($result_set->RowCount() > 0)
					$int_bill_number = $result_set->FieldByName('bill_cash_bill_number') +1;
				else
					$int_bill_number = 1;
			}
			else {
				$int_bill_number = 0;
				if ($result_set->RowCount() > 0)
					if ($result_set->FieldByName('bill_cash_bill_number') > 0)
						$int_bill_number = $result_set->FieldByName('bill_cash_bill_number') -1;
			}
			
			$result_set->Query("UPDATE user_settings SET bill_cash_bill_number = ".$int_bill_number." WHERE storeroom_id = ".$_SESSION['int_current_storeroom']);
		}
		else if ($bill_type == BILL_ACCOUNT) {
			$str_query = "SELECT bill_fs_bill_number FROM user_settings WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
			$result_set->Query($str_query);

			if ($increment == 'Y') {
				if ($result_set->RowCount() > 0)
					$int_bill_number = $result_set->FieldByName('bill_fs_bill_number') +1;
				else
					$int_bill_number = 1;
			}
			else {
				$int_bill_number = 0;
				if ($result_set->RowCount() > 0)
					if ($result_set->FieldByName('bill_fs_bill_number') > 0)
						$int_bill_number = $result_set->FieldByName('bill_fs_bill_number') -1;
			}

			$result_set->Query("UPDATE user_settings SET bill_fs_bill_number = ".$int_bill_number." WHERE storeroom_id = ".$_SESSION['int_current_storeroom']);
		}
		else if ($bill_type == BILL_PT_ACCOUNT) {
			$str_query = "SELECT bill_pt_bill_number FROM user_settings WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
			$result_set->Query($str_query);

			if ($increment == 'Y') {
				if ($result_set->RowCount() > 0)
					$int_bill_number = $result_set->FieldByName('bill_pt_bill_number') +1;
				else
					$int_bill_number = 1;
			}
			else {
				$int_bill_number = 0;
				if ($result_set->RowCount() > 0)
					if ($result_set->FieldByName('bill_pt_bill_number') > 0)
						$int_bill_number = $result_set->FieldByName('bill_pt_bill_number') -1;
			}

			$result_set->Query("UPDATE user_settings SET bill_pt_bill_number = ".$int_bill_number." WHERE storeroom_id = ".$_SESSION['int_current_storeroom']);
		}
		else if ($bill_type == BILL_CREDIT_CARD) {
			$str_query = "SELECT bill_creditcard_bill_number FROM user_settings WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
			$result_set->Query($str_query);

			if ($increment == 'Y') {
				if ($result_set->RowCount() > 0)
					$int_bill_number = $result_set->FieldByName('bill_creditcard_bill_number') +1;
				else
					$int_bill_number = 1;
			}
			else {
				$int_bill_number = 0;
				if ($result_set->RowCount() > 0)
					if ($result_set->FieldByName('bill_creditcard_bill_number') > 0)
						$int_bill_number = $result_set->FieldByName('bill_creditcard_bill_number') -1;
			}

			$result_set->Query("UPDATE user_settings SET bill_creditcard_bill_number = ".$int_bill_number." WHERE storeroom_id = ".$_SESSION['int_current_storeroom']);
		}
		else if ($bill_type == BILL_CHEQUE) {
			$str_query = "SELECT bill_cheque_bill_number FROM user_settings WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
			$result_set->Query($str_query);

			if ($increment == 'Y') {
				if ($result_set->RowCount() > 0)
					$int_bill_number = $result_set->FieldByName('bill_cheque_bill_number') +1;
				else
					$int_bill_number = 1;
			}
			else {
				$int_bill_number = 0;
				if ($result_set->RowCount() > 0)
					if ($result_set->FieldByName('bill_cheque_bill_number') > 0)
						$int_bill_number = $result_set->FieldByName('bill_cheque_bill_number') -1;
			}

			$result_set->Query("UPDATE user_settings SET bill_cheque_bill_number = ".$int_bill_number." WHERE storeroom_id = ".$_SESSION['int_current_storeroom']);
		}
		else if ($bill_type == BILL_TRANSFER_GOOD) {
			$str_query = "SELECT bill_transfer_bill_number FROM user_settings WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
			$result_set->Query($str_query);

			if ($increment == 'Y') {
				if ($result_set->RowCount() > 0)
					$int_bill_number = $result_set->FieldByName('bill_transfer_bill_number') +1;
				else
					$int_bill_number = 1;
			}
			else {
				$int_bill_number = 0;
				if ($result_set->RowCount() > 0)
					if ($result_set->FieldByName('bill_transfer_bill_number') > 0)
						$int_bill_number = $result_set->FieldByName('bill_transfer_bill_number') -1;
			}

			$result_set->Query("UPDATE user_settings SET bill_transfer_bill_number = ".$int_bill_number." WHERE storeroom_id = ".$_SESSION['int_current_storeroom']);
		}
		else if ($bill_type == BILL_AUROCARD) {
			$str_query = "SELECT bill_aurocard_bill_number FROM user_settings WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
			$result_set->Query($str_query);

			if ($increment == 'Y') {
				if ($result_set->RowCount() > 0)
					$int_bill_number = $result_set->FieldByName('bill_aurocard_bill_number') +1;
				else
					$int_bill_number = 1;
			}
			else {
				$int_bill_number = 0;
				if ($result_set->RowCount() > 0)
					if ($result_set->FieldByName('bill_aurocard_bill_number') > 0)
						$int_bill_number = $result_set->FieldByName('bill_aurocard_bill_number') -1;
			}

			$result_set->Query("UPDATE user_settings SET bill_aurocard_bill_number = ".$int_bill_number." WHERE storeroom_id = ".$_SESSION['int_current_storeroom']);
		}
		else if ($bill_type == BILL_UPI) {
			$str_query = "SELECT bill_upi_bill_number FROM user_settings WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
			$result_set->Query($str_query);

			if ($increment == 'Y') {
				if ($result_set->RowCount() > 0)
					$int_bill_number = $result_set->FieldByName('bill_upi_bill_number') +1;
				else
					$int_bill_number = 1;
			}
			else {
				$int_bill_number = 0;
				if ($result_set->RowCount() > 0)
					if ($result_set->FieldByName('bill_upi_bill_number') > 0)
						$int_bill_number = $result_set->FieldByName('bill_upi_bill_number') -1;
			}

			$result_set->Query("UPDATE user_settings SET bill_upi_bill_number = ".$int_bill_number." WHERE storeroom_id = ".$_SESSION['int_current_storeroom']);
		}		
		return $int_bill_number;
	}
	
	function get_bill_number_no_update($bill_type) {
		//=================================================
		// check whether there are bills in the recycle bin
		//-------------------------------------------------

		$bt = $bill_type;

		$result_set = new Query("
			SELECT *
			FROM recycled_bill_numbers
			WHERE bill_type = $bt
			ORDER BY bill_number
		");
		if ($result_set->RowCount() > 0) {
			$int_bill_number = $result_set->FieldByName('bill_number');
		}
		else {
			//=========================
			// get the last bill number
            //-------------------------
            if (BILL_USE_GLOBAL == 'Y') {
				$str_query = "SELECT bill_global_bill_number FROM user_settings WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
				$result_set->Query($str_query);

				if ($result_set->RowCount() > 0)
					$int_bill_number = $result_set->FieldByName('bill_global_bill_number') +1;
				else
					$int_bill_number = 1;
            }
			elseif ($bill_type == BILL_CASH) {
				$str_query = "SELECT bill_cash_bill_number FROM user_settings WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
				$result_set->Query($str_query);

				if ($result_set->RowCount() > 0)
					$int_bill_number = $result_set->FieldByName('bill_cash_bill_number') +1;
				else
					$int_bill_number = 1;
			}
			else if ($bill_type == BILL_ACCOUNT) {
				$str_query = "SELECT bill_fs_bill_number FROM user_settings WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
				$result_set->Query($str_query);

				if ($result_set->RowCount() > 0)
					$int_bill_number = $result_set->FieldByName('bill_fs_bill_number') +1;
				else
					$int_bill_number = 1;
			}
			else if ($bill_type == BILL_PT_ACCOUNT) {
				$str_query = "SELECT bill_pt_bill_number FROM user_settings WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
				$result_set->Query($str_query);

				if ($result_set->RowCount() > 0)
					$int_bill_number = $result_set->FieldByName('bill_pt_bill_number') +1;
				else
					$int_bill_number = 1;
			}
			else if ($bill_type == BILL_CREDIT_CARD) {
				$str_query = "SELECT bill_creditcard_bill_number FROM user_settings WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
				$result_set->Query($str_query);

				if ($result_set->RowCount() > 0)
					$int_bill_number = $result_set->FieldByName('bill_creditcard_bill_number') +1;
				else
					$int_bill_number = 1;
			}
			else if ($bill_type == BILL_CHEQUE) {
				$str_query = "SELECT bill_cheque_bill_number FROM user_settings WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
				$result_set->Query($str_query);

				if ($result_set->RowCount() > 0)
					$int_bill_number = $result_set->FieldByName('bill_cheque_bill_number') +1;
				else
					$int_bill_number = 1;
			}
			else if ($bill_type == BILL_TRANSFER_GOOD) {
				$str_query = "SELECT bill_transfer_bill_number FROM user_settings WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
				$result_set->Query($str_query);

				if ($result_set->RowCount() > 0)
					$int_bill_number = $result_set->FieldByName('bill_transfer_bill_number') +1;
				else
					$int_bill_number = 1;
			}
			else if ($bill_type == BILL_AUROCARD) {
				$str_query = "SELECT bill_aurocard_bill_number FROM user_settings WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
				$result_set->Query($str_query);

				if ($result_set->RowCount() > 0)
					$int_bill_number = $result_set->FieldByName('bill_aurocard_bill_number') +1;
				else
					$int_bill_number = 1;
			}
			else if ($bill_type == BILL_UPI) {
				$str_query = "SELECT bill_upi_bill_number FROM user_settings WHERE storeroom_id = ".$_SESSION['int_current_storeroom'];
				$result_set->Query($str_query);

				if ($result_set->RowCount() > 0)
					$int_bill_number = $result_set->FieldByName('bill_upi_bill_number') +1;
				else
					$int_bill_number = 1;
			}
		}
		
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
?>