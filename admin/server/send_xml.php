<?php
	require_once("ftpclass.php");
	require_once("../../include/const.inc.php");
	require_once("DB.php");
	require_once("db_params.php");
	
	// ***
	// get list of xml files in folder
	//***
	$arr_files = glob("xml_files/*.xml");
	
	// ***
	// send files to server
	//***
	$ftp = new FTPClass($arr_invent_config['reseller']['server_folder'],
		$arr_invent_config['reseller']['local_folder'],
		$arr_invent_config['reseller']['server_address'],
		$arr_invent_config['reseller']['ftp_user'],
		$arr_invent_config['reseller']['ftp_password']);
	
	$ftp->setDebug(true);
	
	for ($i=0;$i<count($arr_files);$i++) {
		if ($ftp->FTP_send($arr_files[$i])) {
			//**
			// remove the local copy
			//**
			unlink($arr_files[$i]);
		}
		
	}
	$ftp->FTP_close();
	
	/*
		send modified images to the server
	*/
	$ftp = new FTPClass($arr_invent_config['reseller']['server_images_folder'],
		$arr_invent_config['reseller']['local_images_folder'],
		$arr_invent_config['reseller']['server_address'],
		$arr_invent_config['reseller']['ftp_user'],
		$arr_invent_config['reseller']['ftp_password']);
	
	$ftp->setDebug(true);
	
	$str_query = "
		SELECT *
		FROM stock_product
		WHERE is_image_modified = 'Y'
			AND deleted = 'N'
	";
	$qry =& $conn->query($str_query);
	
	if ($qry->numRows() > 0) {
		while ($obj =& $qry->fetchRow()) {
			if ($ftp->FTP_send($obj->image_filename, FTP_BINARY)) {
				/*
					update the is_image_modified status
				*/
				$str_query = "
					UPDATE stock_product
					SET is_image_modified = 'N'
					WHERE product_id = ".$obj->product_id;
				
				$qry_update = $conn->query($str_query);
				
				echo "Updated image ".$obj->image_filename."<br>";
			}
		}
	}
	
	$ftp->FTP_close();
?>