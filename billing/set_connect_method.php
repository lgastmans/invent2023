<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");

	function set_connect_method($int_connect_method) {
		$_SESSION['connect_method'] = $int_connect_method;

		return $int_connect_method;
	}

	if (!empty($_GET['live'])) {

		if (!empty($_GET['connect_method'])) {

			echo set_connect_method($_GET['connect_method']);
			die();

		}
		else {
			die("connect method not set");
		}
	}

?>