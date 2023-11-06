<?php
// echo '{"R0_Name":"NAGAPPAN & SUGANYA","R0_Number":"102080","R0_Type":"3","R0_Disable":"0","R0_CCID":"691","R1_Name":"LASZLO&EVA","R1_Number":"103220","R1_Type":"4","R1_Disable":"0","R1_CCID":"4094","R2_Name":"NEEDAM WORKERS FUND\/AMRIT ","R2_Number":"251858","R2_Type":"4","R2_Disable":"0","R2_CCID":"14441"}';
// die();

	require_once('nusoap.php');
	require_once('../include/const.inc.php');
	require_once('../include/session.inc.php');
//	require_once("DB.php");
	require_once("db_params.php");


	$ret = 'ERROR';


	$loginInfo = array('PID'=>$_SESSION['int_application_pid'],'password'=>$_SESSION['int_application_pin']);
	
	// define path to server application
	$serverpath = ACCOUNT_SOAP_SERVER_URL;

	// create client object
	$client = new soapclient($serverpath);

	// login to SOAP server
	$res = $client->call('login',$loginInfo); 
	

// print_r($serverpath);
// print_r($loginInfo);
// print_r($res);
// die('test');


	if ($res['Result'] == 'OK') {

//		$qry = new Query("TRUNCATE TABLE account_cc");

		$arr = $client->call(
			"getAccountRange", 
			array(""=>0)
		);

		$arr['Accounts'] = unserialize($arr['Accounts']);

		echo json_encode($arr['Accounts']);

/*
		$i = 0;
		while (true) {

			if (isset($arr['Accounts']['R'.$i."_CCID"])) {

				$str_query = "DELETE FROM account_cc WHERE cc_id=".$arr['Accounts']['R'.$i."_CCID"];
				$qry->Query($str_query);
				
				$str_query = "
					UPDATE account_cc 
					SET account_active='N' 
					WHERE account_number='".$arr['Accounts']['R'.$i."_Number"]."' 
						AND account_type=".$arr['Accounts']['R'.$i."_Type"];
				$qry->Query($str_query);
				
				$str_query = "
					INSERT INTO account_cc (cc_id,
						account_name,
						account_number,
						account_type,
						account_enabled
					)
					VALUES (
						" . $arr['Accounts']['R'.$i."_CCID"].",
						'" . addslashes($arr['Accounts']['R'.$i."_Name"])."',
						'" . $arr['Accounts']['R'.$i."_Number"]."',
						" . $arr['Accounts']['R'.$i."_Type"].",
						'" . (($arr['Accounts']['R'.$i."_Disable"]==0)?'Y':'N')."'
					)
				";
				$qry->Query($str_query);
				
			}
			else
				break;

			$i++;
		}
*/
	}
	else
		echo json_encode($ret);

	// kill object
	unset($client);

	die();
?>
