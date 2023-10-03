<?php
	require_once("ftpclass.php");
	require_once("../../include/const.inc.php");
		
	// ***
	// receive xml files from server
	//                  folder on server     local folder
	//***
	$ftp = new FTPClass(
		$arr_invent_config['reseller']['server_orders_folder'],
		$arr_invent_config['reseller']['local_orders_folder'],
		$arr_invent_config['reseller']['server_address'],
		$arr_invent_config['reseller']['ftp_user'],
		$arr_invent_config['reseller']['ftp_password']);
	
	$ftp->setDebug(true);
	
	$ftp->FTP_receive();
	
	$ftp->FTP_close();
?>