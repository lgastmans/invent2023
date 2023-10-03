<?php
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");
	
	$ret = array();

	if (isset($_POST['name'])) {

		$name = $_POST['name'];

		if ($name == 'invoice_number')
			$_SESSION['current_invoice_number'] = $_POST['value'];

		elseif ($name == 'invoice_date')
			$_SESSION['current_invoice_date'] = $_POST['value'];

		elseif ($name == 'list_day')
			$_SESSION['current_bill_day'] = $_POST['value'];

		elseif ($name == 'list_supplier')
			$_SESSION['current_supplier_id'] = $_POST['value'];

		elseif ($name == 'note')
			$_SESSION['current_note'] = $_POST['value'];

		elseif ($name == 'bill_number')
			$_SESSION['current_bill_number'];


		$ret['msg'] = 'Ok';
		$ret['session_var'] = $name;
		$ret['session_val'] = $_POST['value'];

	}

	echo json_encode($ret);
?>