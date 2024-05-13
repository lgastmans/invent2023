<?php

	require_once('nusoap.php');

	require_once('../include/const.inc.php');
	require_once('../include/session.inc.php');
	require_once('../include/db_mysqli.php');

	//echo "Response: ".$_POST['transfer_id']. " > ".$_POST['strAccountNumberFrom'];


/*
	login to SOAP server
*/
	$loginInfo = array('PID'=>$_SESSION['int_application_pid'],'password'=>$_SESSION['int_application_pin']);
	$serverpath = ACCOUNT_SOAP_SERVER_URL;
	$client = new soapclient($serverpath);
	$res = $client->call('login',$loginInfo);

	
	if ($res['Result']=="OK") {

		if (isset($_POST['transfer_id'])) {

			$qry = $conn->query("
				SELECT *
				FROM ".Monthalize('account_transfers')." 
				WHERE transfer_id = ".$_POST['transfer_id']."
				LIMIT 1
			");

			$obj = $qry->fetch_object();

			$token = 0;

			// Get the token (integer s/n)
			$param = array(0=>"");
			$tokenres = $client->call('requestTransferToken',$param);

			$key = "fstockencryptkey";
			$encrypted = urldecode($tokenres['Result']);
			$d = explode(";", $encrypted);
			$token = $decrypted = intval(mcrypt_decrypt(
				MCRYPT_RIJNDAEL_128,
				$key,
				base64_decode($d[0]),
				MCRYPT_MODE_CBC,
				base64_decode($d[1])
			));
			$check='No';

			if ($token != 0) {

				$param = array(
					'strAccountNumberFrom'=>$obj->account_from,
					'strAccountNumberTo'=>$obj->account_to,
					'fAmount'=>$obj->amount,
					'strDescription'=>addslashes($obj->module_record_id."/".$obj->description),
					'check'=>$check,
					'token'=>$token
				);

				$res = $client->call('addTransfer',$param);
				//$res = $client->call('testTransfer', $param);

				$res_str = implode('|', $res);

				$sql_log = "INSERT INTO `".Yearalize('transfers_log')."`
					(`cc_id_from`,
					`cc_id_to`,
					`account_from`,
					`account_to`,
					`amount`,
					`description`,
					`called_on`,
					`result_string`,
					`result`)
					VALUES (
						'".$obj->cc_id_from."',
						'".$obj->cc_id_to."',
						'".$obj->account_from."',
						'".$obj->account_to."',
						'".$obj->amount."',
						'".$obj->description."|".$obj->module_record_id."',
						'".Date("Y-m-d h:i:s",time())."',
						'".addslashes($res_str)."',
						'".addslashes($res['Result'])."'
					)
				";
				$qry = $conn->query($sql_log);

				if ($res['Result']=="OK") {

					echo "Completed tranfer of Rs. ".number_format($obj->amount,2,'.',',').
						" from #".$obj->account_from.
						" to ".$obj->account_to."\n";
					
					$qry = $conn->query("
						UPDATE ".Monthalize('account_transfers')." 
						SET	transfer_status=".ACCOUNT_TRANSFER_COMPLETE.",
						date_completed='".Date("Y-m-d h:i:s",time())."' 
						WHERE transfer_id=".$_POST['transfer_id']
					);

				} else {

					echo "Transfer failed from #".$obj->account_from." to #".$obj->account_to.": ".$res['Result']."\n";

					if (substr($res['Result'],0,7)=='ERR 002')
						$int_status = ACCOUNT_TRANSFER_INSUFFICIENT_FUNDS;
					else
						$int_status = ACCOUNT_TRANSFER_ERROR;

					$qry = $conn->query("
						UPDATE ".Monthalize('account_transfers')." 
						SET transfer_status=".$int_status." 
						WHERE transfer_id=".$_POST['transfer_id']."\n"
					);
				}
			}
	 	}
	} 



?>