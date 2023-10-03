<?php
	require_once('include/const.inc.php');
	require_once("session.inc.php");
	require_once("JSON.php");
	
	/*
		session_start should be called
		but commented out here
		as session.inc.php calls it
	*/
	//session_start();
	session_unset();
	session_destroy();
	
	$arr_retval['replyCode'] = 200;
	$arr_retval['replyStatus'] = "Ok";
	$arr_retval['replyText'] = "Ok";
	
	echo (json_encode($arr_retval));

?>