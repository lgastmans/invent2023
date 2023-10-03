<?
	require_once("include/const.inc.php");
	require_once("session.inc.php");

	$int_module_id = 1;
	if (IsSet($_GET['module_id'])) {
		$int_module_id = $_GET['module_id'];
		$_SESSION['int_module_selected'] = $int_module_id;
	}
		
	for ($i=0; $i<count($_SESSION['arr_modules']);$i++) {
		if ($int_module_id == $_SESSION['arr_modules'][$i]->int_module_id)
			echo $_SESSION['arr_modules'][$i]->buildMenu(1);
	}
?>