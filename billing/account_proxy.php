<?php
	require_once('../include/const.inc.php');
	require_once('session.inc.php');
	require_once('db_params.php');
	require_once('JSON.php');
	
	$str_search = '';
	if (IsSet($_GET['query']))
		$str_search = $_GET['query'];
	
	if (strlen($str_search) > 2) {
		$str_query = "
			SELECT account_number, account_name
			FROM account_cc
			WHERE (account_number LIKE '%$str_search%')
				OR (account_name LIKE '%$str_search%')
			ORDER BY account_name";
		$qry = $conn->query($str_query);
		
		if ($qry->numRows() > 0) {
			$i = 0;
			while ($obj =& $qry->fetchRow()) {
				$arr_accounts[$i]['Number'] = $obj->account_number;
				$arr_accounts[$i]['Account'] = $obj->account_number." ".$obj->account_name;
				$i++;
			}
			
			$arr_retval['replyCode'] = 201;
			$arr_retval['replyText'] = "Ok";
			$arr_retval['Result'] = $arr_accounts;
		}
		else {
			$arr_retval['replyCode'] = 501;
			$arr_retval['replyText'] = "None found";
			$arr_retval['Result'] = "";
		}
	}
	else {
			$arr_retval['replyCode'] = 501;
			$arr_retval['replyText'] = "None found";
			$arr_retval['Result'] = "";
	}
	
	$json = new Services_JSON();
	echo ($json->encode($arr_retval));
?>