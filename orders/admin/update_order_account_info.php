<?php
	require_once("../../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db_params.php");
	require_once("db.inc.php");
	require_once("db_funcs.inc.php");
	
	/*
	$db_server = $arr_invent_config['database']['invent_server'];
	$db = $arr_invent_config['database']['invent_database'];
	$db_login = $arr_invent_config['database']['invent_login'];
	$db_password = $arr_invent_config['database']['invent_password'];
	*/

	/*
	 * get all the orders
	 */
	$sql = "SELECT CC_id FROM ".Monthalize('orders');
	$orders = $conn->query($sql);
	
	$arr_not_found = array();
	
	while ($row = $orders->fetchRow()) {
		/*
		 * update the account_number and name from table account_cc
		 */
		
		$sql = "SELECT * FROM account_cc WHERE (cc_id = ".$row->cc_id.") AND account_active = 'Y'";
		$account = $conn->query($sql);
		
		
		if ($account->numRows()>0) {
			$info = $account->fetchRow();
			$update = $conn->query("
				UPDATE orders_2013_4 
				SET account_number = '".$info->account_number."',
					account_name = '".$info->account_name."'
				WHERE cc_id = ".$row->cc_id
			);
		}
		else {
			$arr_not_found[] = $sql;
		}
		
	}
	
	print_r($arr_not_found);
?>
