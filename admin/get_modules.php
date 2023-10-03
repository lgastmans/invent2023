<?
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db.inc.php");

	$int_user_id = 0;
	if (IsSet($_GET["user_id"]))
		$int_user_id = $_GET['user_id'];
		
	$int_permission_id = 0;
	if (IsSet($_GET['permission_id']))
		$int_permission_id = $_GET['permission_id'];

	$int_current_storeroom = 0;
	if (IsSet($_GET['storeroom_id']))
		$int_current_storeroom = $_GET['storeroom_id'];
	
	function get_modules($int_user_id, $int_permission_id, $int_current_storeroom) {
		if ($int_user_id > 0) {
			/*
				new
			*/
			$qry_modules = new Query("
				SELECT *
				FROM module
				WHERE module_id NOT IN (
					SELECT module_id
					FROM user_permissions
					WHERE (user_id = $int_user_id)
						AND (storeroom_id = ".$int_current_storeroom.")
				)
				AND active='Y'
				ORDER BY module_id
			");
		}
		else {
			/*
				edit
			*/
			$qry_modules = new Query("
				SELECT *
				FROM module
				WHERE module_id IN (
					SELECT module_id
					FROM user_permissions
					WHERE permission_id = $int_permission_id
				)
				AND active='Y'
				ORDER BY module_id
			");
		}
		
		$str_retval = "";
		
		for ($i=0;$i<$qry_modules->RowCount();$i++) {
			$str_retval .= $qry_modules->FieldByName('module_id')."_".$qry_modules->FieldByName('module_name')."|";
			$qry_modules->Next();
		}
		
		$str_retval = substr($str_retval, 0, strlen($str_retval)-1);

		return $str_retval;
	}

	if (!empty($_GET['live'])) {
		echo get_modules($int_user_id, $int_permission_id, $int_current_storeroom);
		die();
	}

?>