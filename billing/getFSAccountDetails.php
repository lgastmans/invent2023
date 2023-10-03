<?php
	require_once('../admin/nusoap.php');
	require_once('../include/const.inc.php');
	require_once('../include/session.inc.php');

	if ($_SESSION['connect_mode'] != CONNECT_ONLINE) {
		$res=array();
		echo json_encode($res);
		die();
	}

	$loginInfo = array('PID'=>$_SESSION['int_application_pid'],'password'=>$_SESSION['int_application_pin']);

	$serverpath = ACCOUNT_SOAP_SERVER_URL;

	$client = new soapclient($serverpath);

	$res = $client->call('login',$loginInfo); 


	$res = $client->call("getAccountMaxAmount", array(
		"aNumber" => $_POST['fsAccount'],
	));

/*
	if not logged in:
		array('Result'=>'ERR099: No Permission');
	if not allowed to access account:
		array('Result'=>"ERR090: No Permission For Account $strAccountNumber");
	if number is not found
		array('Result'=>'ERR100: Account not found');
*/

	if($res['Result'] == "OK") {
		$_SESSION['fs_account_balance'] = $res['maxAmount'];
	}
	else
		$_SESSION['fs_account_balance'] = 0;

	echo json_encode($res);

	unset($client);
	die();

?>