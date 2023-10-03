<?
	if (file_exists("../include/const.inc.php"))
		require_once("../include/const.inc.php");
	else if (file_exists("../../include/const.inc.php"))
		require_once("../../include/const.inc.php");
	require_once("db_params.php");
//	require_once("session.inc.php");

	$user_id = 0;
	if (IsSet($_SESSION['int_user_id']))
		$user_id = $_SESSION['int_user_id'];

	$conn->setFetchMode(MDB2_FETCHMODE_ORDERED);
	
	/*
		check whether the log table exists
	*/
	$str_query = "SHOW TABLES";
	$qry =& $conn->query($str_query);
	$i = 0;
	$found = false;
	while ($obj = $qry->fetchRow()) {
		if ($obj[0] == 'update_log') {
			$found = true;
			break;
		}
		$i++;
	}
	
	/*
		if the log table does not exist
		create before proceeding
	*/
	if (!$found) {
		if (file_exists($str_application_path.'updatemanager/invent/insert_log.sql')) {
			$found = true;
			$filename = $str_application_path."updatemanager/invent/insert_log.sql";
			$handle = fopen($filename, "r");
			$contents = fread($handle, filesize($filename));
			$qry =& $conn->query($contents);
			if (MDB2::isError($qry)) {
				$found = false;
				echo $qry->getDebugInfo();
			}
			else
				echo "log table created<br>";
		}
		else
			$found = false;
	}
	
	$conn->setFetchMode(MDB2_FETCHMODE_OBJECT);
	
	if (!$found)
		die('update log table not found - no updates installed');

	/*
		update the invent database - 1 - $conn
		update the help database - 2 - $conn_help
	*/
	echo "<b>Updating Invent Database</b><br>";
	update_database(1, $user_id);
	
	echo "<br><br><b>Updating Invent Help Database</b><br>";
	update_database(2, $user_id);

	function update_database($int_type_id, $user_id) {
		global $conn;
		global $conn_help;
		global $str_application_path;
		
		/*
			get list of sql files in the invent folder
		*/
		if ($int_type_id == 1)
			$arr_files = glob($str_application_path."updatemanager/invent/update_*.sql");
		else if ($int_type_id == 2)
			$arr_files = glob($str_application_path."updatemanager/help/update_*.sql");
		
		$int_num_files = count($arr_files);
		
		if ($int_num_files <= 0)
			return true;
		
		/*
			check these files against the logs
			type_id 1 for invent updates
		*/
		$str_query = "
			SELECT *
			FROM update_log
			WHERE type_id = $int_type_id
		";
		$qry =& $conn->query($str_query);
		if (MDB2::isError($qry))
			echo $qry->getDebugInfo()."<br>";
			
		if ($qry->numRows() > 0) {
			for ($i=0;$i<$int_num_files;$i++) {
				$filename = $arr_files[$i];
				
				$qry->seek();
				while ($obj = $qry->fetchRow()) {
					$bool_found = false;
					if ($obj->filename == $filename) {
						$bool_found = true;
						break;
					}
				}
				
				if (!$bool_found) {
					$handle = fopen($filename, "r");
					$contents = fread($handle, filesize($filename));
					if ($int_type_id == 1)
						$qry_update =& $conn->query($contents);
					else if ($int_type_id == 2)
						$qry_update =& $conn_help->query($contents);
					if (MDB2::isError($qry_update)) {
						echo $qry_update->getDebugInfo()."<br>";
					}
					else {
						$qry_update =& $conn->query("
							INSERT INTO update_log
							(
								type_id,
								updated_on,
								filename,
								user_id
							)
							VALUES (
								$int_type_id,
								'".date('Y-m-d', time())."',
								'$filename',
								$user_id
							)
						");
						if (MDB2::isError($qry_update))
							echo $qry_update->getDebugInfo()."<br>";
						
						echo "update $filename applied<br>";
						
						unlink($filename);
					}
				}
			}
		}
		else {
			/*
				log file empty - run updates, if any
			*/
			for ($i=0;$i<$int_num_files;$i++) {
				$filename = $arr_files[$i];
				$handle = fopen($filename, "r");
				$contents = fread($handle, filesize($filename));
				if ($int_type_id == 1)
					$qry_update =& $conn->query($contents);
				else if ($int_type_id == 2)
					$qry_update =& $conn_help->query($contents);
				if (MDB2::isError($qry_update)) {
					echo $qry_update->getDebugInfo()."<br>";
				}
				else {
					$qry_update =& $conn->query("
						INSERT INTO update_log
						(
							type_id,
							updated_on,
							filename,
							user_id
						)
						VALUES (
							$int_type_id,
							'".date('Y-m-d', time())."',
							'$filename',
							$user_id
						)
					");
					if (MDB2::isError($qry_update))
						echo $qry_update->getDebugInfo()."<br>";
					else
						echo "update $filename applied <br>";
					
					unlink($filename);
				}
			}
		}
		
		return true;
	}

?>