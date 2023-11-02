<?
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



	function backup_db($purge='N', $purge_pattern='', $filename='') {

		global $arr_invent_config;

		$db_db = $arr_invent_config['database']['invent_database'];
		$db_login = $arr_invent_config['database']['invent_login'];
		$db_password = $arr_invent_config['database']['invent_password'];
		$db_folder = $arr_invent_config['database']['backup_folder'];
		$mysql_folder = $arr_invent_config['database']['mysql_folder'];

		$os = php_uname('s');

		/*
			check if the backup folder exists
		*/
		if (!file_exists($db_folder)) {
			if (!mkdir($db_folder)) {
				return "Could not create folder '$db_folder' - please create the folder manually and try again";
			}
		}


		$temp_filename = "mysqlexport.sql";


		if (empty($filename))
			$filename = "db_backup_".date('Y')."_".date('m')."_".date('j').".sql.gz";

		/*
			check if the file was already created
		*/
		if (file_exists($db_folder."/".$filename))
			return "Backup file $filename already exists";


		/*
			purge previous backup files 
		*/
		if ($purge=='Y') {

			if (!empty($purge_pattern)) {

				$arr = glob($db_folder."/".$purge_pattern."*");

				if (count($arr) > 0) {
					foreach ($arr as $file) {
						unlink($file);
					}
				}

			}

		}



		/*
			create the temporary database backup file
		*/
		if ($os == "Linux") {

			$strExportCommand = "/usr/bin/mysqldump -a -u ".$db_login." --password=".$db_password." ".$db_db." > \"".$db_folder."/".$temp_filename."\"";

			exec($strExportCommand);
			
			$data = implode("", file($db_folder."/".$temp_filename));
			$gzdata = gzencode($data);

		}
		else {

			$strExportCommand = "\"".$mysql_folder."mysqldump.exe\" -a -u ".$db_login." --password=".$db_password." ".$db_db." > \"".$db_folder."/".$temp_filename."\"";
			exec($strExportCommand);
			
			$data = implode("", file($db_folder."/".$temp_filename));
			$gzdata = gzencode($data);

		}


		/*
			write the zipped data to file
		*/
	    $fp = fopen($db_folder."/".$filename, "w");

		if (!$fp)
			return 'error creating zip file';

	    fwrite($fp, $gzdata);
	    fclose($fp);


	    unlink($db_folder."/".$temp_filename);


	    return "Database backup completed successfully to folder '$db_folder'.";

	}




	if ((isset($_GET['action'])) && ($_GET['action']=='backup')) {
		echo backup_db('Y', "db_backup_");
	}

?>
