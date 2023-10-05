<?
	require_once("session.inc.php");
	
	/*
		the following array gets initialized in the const.inc.php file
		which is assumed to be included
	*/
	if (!IsSet($_SESSION['invent_database_loaded'])) {
		$_SESSION['invent_database_loaded'] = $arr_invent_config['database']['invent_database'];
	}
	
	$db_db = $_SESSION['invent_database_loaded'];
	$db_server =  $arr_invent_config['database']['invent_server'];
	$db_login = $arr_invent_config['database']['invent_login'];
	$db_password = $arr_invent_config['database']['invent_password'];
	

	$conn = new mysqli($db_server, $db_login, $db_password, $db_db);

	if ($conn->connect_errno) {
	    printf("Connect failed: %s\n", $conn->connect_error);
	    exit();
	}
?>