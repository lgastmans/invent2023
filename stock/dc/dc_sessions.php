<?
	require_once("../../include/const.inc.php");
	require_once("../../include/session.inc.php");

	function set_sessions($client_id, $dc_day) {
		$_SESSION['dc_client_id'] = $client_id;
		$_SESSION['current_dc_day'] = $dc_day;
		
		return 'saved:'.$_SESSION['dc_client_id'].":".$_SESSION['current_dc_day'];
	}
	
	if (!empty($_GET['live'])) {
		if ($_GET['live'] == 1) {
			echo set_sessions(
				$_GET['client_id'],
				$_GET['dc_day']
			);
		}
		else
			die('error saving order sessions');
	}
?>