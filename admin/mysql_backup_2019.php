<?
	require_once("../include/const.inc.php");
	require_once("browser_detection.php");




	$db_db = $arr_invent_config['database']['invent_database'];
	$db_login = $arr_invent_config['database']['invent_login'];
	$db_password = $arr_invent_config['database']['invent_password'];
	$db_folder = $arr_invent_config['database']['backup_folder'];


	/*
		check if the backup folder exists
	*/
	if (!file_exists($db_folder)) {
		if (!mkdir($db_folder)) {
			die("Could not create folder $db_folder - please create the folder manually and try again");
		}
	}


	$strFolder = dirname($_SERVER['SCRIPT_FILENAME']);
	$strFilename = "mysqlexport.sql";
	$strZipFilename = "mysqlexport.sql.gz";

	if (browser_detection( 'os' ) === 'lin') {
		$strDisplayDownloadFilename = "db_backup_".date('Y')."_".date('m')."_".date('j').".sql.gz";

		$strDownloadFilename = tempnam("/tmp", "db_backup");
		
		$strExportCommand = "/usr/bin/mysqldump -a -u ".$db_login." --password=".$db_password." ".$db_db." > \"/tmp/".$strFilename."\"";
		exec($strExportCommand);
		
		$data = implode("", file("/tmp/mysqlexport.sql"));
		$gzdata = gzencode($data);
	}


	else {
		$strDisplayDownloadFilename = "db_backup_".date('Y')."_".date('m')."_".date('j').".sql.gz";
		$strDownloadFilename = "db_backup_".date('Y')."_".date('m')."_".date('j').".sql.gz";
		
		$strExportCommand = "mysqldump -a -u ".$db_login." --password=".$db_password." ".$db_db." > \"".$strFolder."/".$strFilename."\"";
		
		exec($strExportCommand);
		
		$data = implode("", file("mysqlexport.sql"));
		$gzdata = gzencode($data);
	}


    $fp = fopen($strZipFilename, "w");
    fwrite($fp, $gzdata);
    fclose($fp);

    header("Content-Type: application/x-gzip; name=\"".$strDisplayDownloadFilename."\"");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: ".filesize($db_folder."/".$strZipFilename));
    header("Content-Disposition: attachment; filename=\"".$strDisplayDownloadFilename."\"");
    header("Expires: 0");
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");
    $fp = fopen($db_folder."/".$strZipFilename, "r");
    fpassthru($fp);

?>
