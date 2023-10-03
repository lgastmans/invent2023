<?php
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	
	$ret = array();

	if (isset($_POST['name'])) {

		$name = $_POST['name'];

		if ($name == 'current_bill_type')
			$_SESSION['current_bill_type'] = $_POST['value'];

		elseif ($name == 'connect_mode')
			$_SESSION['connect_mode'] = $_POST['value'];

		elseif ($name == 'bill_salesperson')
			$_SESSION['bill_salesperson'] = $_POST['value'];

		elseif ($name == 'current_bill_day')
			$_SESSION['current_bill_day'] = $_POST['value'];

		elseif ($name == 'sales_promotion')
			$_SESSION['sales_promotion'] = $_POST['value'];

		elseif ($name == 'bill_card_name')
			$_SESSION['bill_card_name'] = $_POST['value'];

		elseif ($name == 'bill_card_number')
			$_SESSION['bill_card_number'] = $_POST['value'];
		
		elseif ($name == 'bill_card_date')
			$_SESSION['bill_card_date'] = $_POST['value'];

		elseif ($name == 'bill_account')
			$_SESSION['current_account_number'] = $_POST['value'];

		elseif ($name == 'aurocard_number')
			$_SESSION['aurocard_number'] = $_POST['value'];

		elseif ($name == 'aurocard_transaction_id')
			$_SESSION['aurocard_transaction_id'] = $_POST['value'];

		elseif ($name == 'upi_transaction_id')
			$_SESSION['upi_transaction_id'] = $_POST['value'];

		elseif ($name == 'upi_utr_number')
			$_SESSION['upi_utr_number'] = $_POST['value'];

		elseif ($name == 'client_id')
			$_SESSION['client_id'] = $_POST['value'];

		elseif ($name == 'bill_table_ref')
			$_SESSION['bill_table_ref'] = $_POST['value'];
		
		elseif ($name == 'storeroom')
			$_SESSION['int_current_storeroom'] = $_POST['value'];

		$ret['msg'] = 'Ok';
		$ret['session_var'] = $name;
		$ret['session_val'] = $_POST['value'];

	}

	echo json_encode($ret);
?>