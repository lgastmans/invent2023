<?php
	require_once('../include/const.inc.php');
	require_once('session.inc.php');
	require_once("db_mysqli.php");

	$str_search = '';
	if (IsSet($_GET['search']))
		$str_search = $_GET['search'];
		
	/*
		2 => FS Account
		3 => PT Account
	*/
	$int_account_type = 2;
	if (IsSet($_GET['type']))
		$int_account_type = intval($_GET['type']);
	
	if ($int_account_type == 2) {
		$str_query = "
			SELECT account_number, account_name
			FROM account_cc
			WHERE (account_number LIKE '%$str_search%')
				OR (account_name LIKE '%$str_search%')
			ORDER BY account_name";
	}
	else {
		$str_query = "
			SELECT account_number, account_name, enabled
			FROM account_pt
			WHERE (account_number LIKE '%$str_search%')
				OR (account_name LIKE '%$str_search%')
			ORDER BY account_name";
	}
	$qry = $conn->query($str_query);
	
	if ($qry->num_rows > 0) {

		$i = 0;
		while ($obj =& $qry->fetch_object()) {
			$arr_accounts[$i][0] = $obj->account_number;
			$arr_accounts[$i][1] = utf8_encode($obj->account_name);
			$i++;
		}
		
		$arr_retval['replyCode'] = 201;
		$arr_retval['replyText'] = "Ok";
		$arr_retval['replySet'] = $arr_accounts;
	}
	else {
		$arr_retval['replyCode'] = 501;
		$arr_retval['replyText'] = "None found";
	}
	
	echo (json_encode($arr_retval));
?>