<?php
	if (file_exists('const.inc.php'))
		require_once('const.inc.php');

	else if (file_exists('include/const.inc.php'))
		require_once('include/const.inc.php');

	else if (file_exists('../include/const.inc.php'))
		require_once('../include/const.inc.php');

	else if (file_exists('../../include/const.inc.php'))
		require_once('../../include/const.inc.php');

	else if (file_exists('../../../include/const.inc.php'))
		require_once('../../../include/const.inc.php');

	$os = php_uname('s');

	/**
	 * get the status of the repo
	 * 
	 */
	if ($os == "Linux") {

	}
	else {

		$gitCommand = "git pull";
		$res = exec($gitCommand);
		
		print_r($res);
	}

?>