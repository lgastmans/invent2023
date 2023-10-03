<?
    $strUsername = "root";
    $strPassword = "nine11";
    $strDatabase = "pourtous";
    $strFolder = dirname($_SERVER['SCRIPT_FILENAME']);
    $strFilename = "mysqlexport.sql";
    $strTarFilename = "mysqlexport.tgz";
    $strDownloadFilename = "db_backup_".date('Y')."_".date('m')."_".date('j').".tar.gz";

    $strExportCommand = "mysqldump -a -u ".$strUsername." --password=".$strPassword." ".$strDatabase." > ".$strFolder."/".$strFilename;
    exec($strExportCommand);
    $strCompressCommand = "tar -czf ".$strFolder."/".$strTarFilename." ".$strFilename;
    
    header("Content-Type: application/x-gzip; name=\"".$strDownloadFilename."\"");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: ".filesize($strFolder."/".$strTarFilename));
    header("Content-Disposition: attachment; filename=\"".$strDownloadFilename."\"");
    header("Expires: 0");
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");
    $fp = fopen($strFolder."/".$strTarFilename, "r");
    fpassthru($fp);
?>