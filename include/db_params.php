<?
//	require_once("MDB2.php");
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
	
/*

	$dsn = "mysqli://$db_login:$db_password@$db_server/$db_db";
	
	$conn =& MDB2::connect($dsn);
	if (MDB2::isError($conn))
		die("Cannot connect: ".$conn->getMessage()."<br>".$conn->getDebugInfo()."\n");
	$conn->setFetchMode(MDB2_FETCHMODE_OBJECT);
*/
	$conn = mysqli_connect("$db_server", $db_login, $db_password, $db_db);



	$db_db = $arr_invent_config['database']['invent_help_database'];

/*	
	$dsn = "mysqli://$db_login:$db_password@$db_server/$db_db";
	
	$conn_help =& MDB2::connect($dsn);
	if (MDB2::isError($conn_help))
		die("Cannot connect: ".$conn_help->getMessage()."<br>".$conn_help->getDebugInfo()."\n");
	$conn_help->setFetchMode(MDB2_FETCHMODE_OBJECT);
*/	

	$conn_help = mysqli_connect("$db_server", $db_login, $db_password, $db_db);

?>