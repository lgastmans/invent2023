<?php

	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");

	$ret = array( "reset"=>false );

	if (IsSet($_POST['action'])) {

		if ($_POST['action'] == 'type') {

			$_SESSION['current_account_number'] = "";
			$_SESSION['current_account_name'] = "";
			$_SESSION['fs_account_balance'] = 0;

			$_SESSION['bill_card_name'] = '';
			$_SESSION['bill_card_number'] = '';
			$_SESSION['bill_card_date'] = '';

			$_SESSION['aurocard_number'] = 0;
			$_SESSION['aurocard_transaction_id'] = 0;

			$_SESSION['upi_transaction_id'] = '';
			$_SESSION['upi_utr_number'] = '';

			$ret["types cleared"] = true;
		}
		elseif ($_POST['action'] == 'clear') {

			// clear the session variables related to the billing
			$_SESSION['bill_id'] = -1;
			$_SESSION['bill_number'] = '';
			unset($_SESSION["arr_total_qty"]);
			unset($_SESSION["arr_item_batches"]);
			unset($_SESSION['arr_billed_items']);

			/*
			if (IsSet($_POST["bill_type"]))
				$_SESSION['current_bill_type'] = $_POST["bill_type"];
			else {
				if ($bool_cash)
					$_SESSION['current_bill_type'] = BILL_CASH;
				else if ($bool_fs)
					$_SESSION['current_bill_type'] = BILL_ACCOUNT;
				else if ($bool_pt)
					$_SESSION['current_bill_type'] = BILL_PT_ACCOUNT;
				else if ($bool_transfer)
					$_SESSION['current_bill_type'] = BILL_TRANSFER_GOOD;
				else if ($bool_aurocard)
					$_SESSION['current_bill_type'] = BILL_AUROCARD;
			}
			*/
			
			$_SESSION['current_bill_day'] = date('j');
			$_SESSION['current_account_number'] = "";
			$_SESSION['current_account_name'] = "";
			$_SESSION['bill_total'] = 0;
			$_SESSION['sales_promotion'] = 0;
			$_SESSION['bill_card_name'] = '';
			$_SESSION['bill_card_number'] = '';
			$_SESSION['bill_card_date'] = '';
			$_SESSION['aurocard_number'] = 0;
			$_SESSION['aurocard_transaction_id'] = 0;
			$_SESSION['upi_transaction_id'] = '';
			$_SESSION['upi_utr_number'] = '';			
			$_SESSION['fs_account_balance'] = 0;
			$_SESSION['client_id'] = 0;
			$_SESSION['bill_table_ref'] = '';

			$ret["reset"] = true;
		}
	}

	echo json_encode($ret);
?>