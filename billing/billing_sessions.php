<?php
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");

	function set_sessions($aurocard_number, $aurocard_transaction_id) {
		$_SESSION['aurocard_number'] = $aurocard_number;
		$_SESSION['aurocard_transaction_id'] = $aurocard_transaction_id;
		
		return 'saved:'.$_SESSION['aurocard_number'].":".$_SESSION['aurocard_transaction_id'];
	}
	
	if (!empty($_GET['live'])) {
		if ($_GET['live'] == 1) {
			echo set_sessions(
				$_GET['aurocard_number'],
				$_GET['aurocard_transaction_id']
			);
		}
		else
			die('error saving order sessions');
	}
?>