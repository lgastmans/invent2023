<?
  error_reporting(E_ALL);
	if (file_exists("../include/const.inc.php")) {
		require_once("../include/const.inc.php");
		require_once("../include/session.inc.php");
		require_once("../include/db.inc.php");
		require_once('../include/cast128.php');
	}
	else if (file_exists("../../include/const.inc.php")) {
		require_once("../../include/const.inc.php");
		require_once("../../include/session.inc.php");
		require_once("../../include/db.inc.php");
		require_once('../../include/cast128.php');
	}

function getMaxTransferAmount($f_cc_id) {
	$maxAmount=0;

	if (BILL_DISABLED_ACCOUNTS === 1)
		return -1;

	$str_query = "SELECT account_type, account_enabled, linked_cc_id, account_balance, account_credit_line, account_may_go_below From account_cc where account_active='Y' and cc_id=".$f_cc_id;
	$ccQuery = new Query($str_query);

	if ($ccQuery->FieldByName("account_enabled")=='Y') {
		if ($_SESSION['connect_mode'] == CONNECT_OFFLINE_FULL_ACCESS) {
            
			if (($ccQuery->FieldByName("account_type") < 3) || ($ccQuery->FieldByName("account_may_go_below")=='Y')){
				$maxAmount = -1;
			} else {
				$maxAmount = $ccQuery->FieldByName("account_balance") - $ccQuery->FieldByName("account_credit_line");
				
				if ($f_cc_id <> $ccQuery->FieldByName("linked_cc_id")) {
					$linkedCCID = $ccQuery->FieldByName("linked_cc_id");
					$str_query = "select account_active, account_enabled, account_type, linked_cc_id, account_balance, account_credit_line, account_may_go_below from account_cc where account_active='Y' and cc_id=".$linkedCCID;
					$ccQuery->Query($str_query);
					if ($ccQuery->FieldByName("account_enabled")=='Y') {
						if ($ccQuery->FieldByName("account_may_go_below")=='Y'){
							$maxAmount = -1;
						} else {
							$maxAmount = $maxAmount + $ccQuery->FieldByName("account_balance")-$ccQuery->FieldByName("account_credit_line");
							if ($maxAmount<0) $maxAmount=0;
						}
					}
				} else if ($maxAmount<0) $maxAmount=0;
			}
		} else if ($_SESSION['connect_mode'] == CONNECT_OFFLINE_LIMITED_ACCESS) {
			$maxAmount = -1;
		}
	}		
//	$ccQuery->Free();
	return $maxAmount;
  }

      function testTransfer($f_account_from, $f_account_to, $f_description, $f_amount) {
            $b_result = 0;
            
            if (file_exists('../admin/nusoap.php'))
                  require_once('../admin/nusoap.php');
            else if (file_exists('../../admin/nusoap.php'))
                  require_once('../../admin/nusoap.php');
            
            $loginInfo = array('PID'=>$_SESSION['int_application_pid'],'password'=>$_SESSION['int_application_pin']);
            
            // define path to server application
            $serverpath = ACCOUNT_SOAP_SERVER_URL;
            
            // create client object
            $client = new soapclient($serverpath);
            
            // login to SOAP server
            $res = $client->call('login',$loginInfo);
            
            if ($res['Result'] == "OK") {
                  $param = array(
                        'strAccountNumberFrom'=>$f_account_from,
                        'strAccountNumberTo'=>$f_account_to,
                        'fAmount'=>$f_amount,
                        'strDescription'=>$f_description
                  );
                  $res = $client->call('testTransfer',$param);
                  if ($res['Result']=="OK") {
                        $b_result = 1;
                  }
                  else if (substr($res['Result'],0,7) == 'ERR 002') {
                        $b_result = 2;
                  }
            }
            
            return $b_result;
      }

  function createTransfer($f_account_from, $f_account_to, $f_description, $f_amount, $f_module_id, $f_module_record_id, $f_strict=true) {
	$b_result = -1;
	switch ($_SESSION['connect_mode']) {
		case CONNECT_OFFLINE_FULL_ACCESS:
			$cc_id1 = getAccountCCID($f_account_from,ACCOUNT_METHOD);
			$cc_id2 = getAccountCCID($f_account_to,ACCOUNT_METHOD);
			if (($cc_id1 > 0) && ($cc_id1 > 0)) {
				
				$str_insert = "INSERT INTO ".Monthalize('account_transfers')." (
					cc_id_from,
					cc_id_to,
					account_from,
					account_to,
					amount,
					description,
					module_id,
					module_record_id,
					date_created,
					user_id,
					transfer_status)
					VALUES (
						$cc_id1,
						$cc_id2,
						'$f_account_from',
						'$f_account_to',
						$f_amount,
						\"".addslashes($f_description)."\",
						$f_module_id,
						$f_module_record_id,
						'".date('Y-m-d H:i:s',time())."',
						".$_SESSION['int_user_id'].",
						".ACCOUNT_TRANSFER_PENDING."
					)
				";
				
				if ($f_strict) {
					$f_max = getMaxTransferAmount($cc_id1);
//					echo $f_max;
					if (($f_max > $f_amount) || ($f_max==-1)) {
						$qry = new Query($str_insert);
						if (!$qry->b_error) {
							$b_result = $qry->getInsertedID();
						}
					} else $b_result=-$f_max;
				} else {
					$qry = new Query($str_insert);
					if (!$qry->b_error) {
						$b_result = $qry->getInsertedID();
					}

				}
			}
			break;
		case CONNECT_OFFLINE_LIMITED_ACCESS:
			$cc_id1 = getAccountCCID($f_account_from);
			$cc_id2 = getAccountCCID($f_account_to);
			if (($cc_id1 > 0) && ($cc_id1 > 0)) {
				$str_insert = "INSERT INTO ".Monthalize('account_transfers')." (
					cc_id_from,
					cc_id_to,
					account_from,
					account_to,
					amount,
					description,
					module_id,
					module_record_id,
					date_created,
					user_id,
					transfer_status)
					VALUES (
						$cc_id1,
						$cc_id2,
						'$f_account_from',
						'$f_account_to',
						$f_amount,
						\"".addslashes($f_description)."\",
						$f_module_id,
						$f_module_record_id,
						'".date('Y-m-d H:i:s',time())."',
						".$_SESSION['int_user_id'].",
						".ACCOUNT_TRANSFER_PENDING."
					)
				";
				
				if ($f_strict) {
					if (BILL_DISABLED_ACCOUNTS <> 1) {
						// ===== inserted by akash - 25 aug 2006 -
//						$qry_active = new Query("select * from account_cc where cc_id='".$cc_id1."' and account_enabled='Y' and account_active='Y'");
						$qry_active = new Query("select * from account_cc where cc_id='".$cc_id1."' and account_active='Y'");
						if ($qry_active->RowCount() == 0) {
							return 0;
						}
						// ======
					}
					$f_max = getMaxTransferAmount($cc_id1);
//					die($str_insert);
//					echo $f_max;
					if (($f_max > $f_amount) || ($f_max == -1)) {
						$qry = new Query($str_insert);
						if (!$qry->b_error) {
							$b_result = $qry->getInsertedID();
		
						}
					} else $b_result=-$f_max;
				} else {
					$qry = new Query($str_insert);
//					die($str_insert);
					if (!$qry->b_error) {
						$b_result = $qry->getInsertedID();
	
					}

				}
			}
			else
				return 0;
			break;
/*
*	Online mode where the transfer is made live
*/			
		case CONNECT_ONLINE:
		
			if (file_exists('../admin/nusoap.php'))
			      require_once('../admin/nusoap.php');
			else if (file_exists('../../admin/nusoap.php'))
			      require_once('../../admin/nusoap.php');
			
			
			$loginInfo = array('PID'=>$_SESSION['int_application_pid'],'password'=>$_SESSION['int_application_pin']);

			// define path to server application
			$serverpath = ACCOUNT_SOAP_SERVER_URL;

			// create client object
			$client = new soapclient($serverpath);

			// login to SOAP server
			$res = $client->call('login',$loginInfo);


			if ($res['Result']=="OK") {
				$token = 0;

				// Get the token (integer s/n)
				$param = array(0=>"");
				$tokenres = $client->call('requestTransferToken',$param);
				//print_r($tokenres);

				$key = "fstockencryptkey";
				// Get Data from SOAP
				$encrypted = urldecode($tokenres['Result']);
				// Split encrypted payload from IV
				$d = explode(";", $encrypted);
				// Decode token
				$token = $decrypted = intval(mcrypt_decrypt(
					// Cypher
					MCRYPT_RIJNDAEL_128,
					// Key
					$key,
					// Data
					base64_decode($d[0]),
					// mode
					MCRYPT_MODE_CBC,
					// IV
					base64_decode($d[1])
				));
				// print_r($token);
				// Use the token in a transfer
				// $check='Yes' to confirm the transfer
				$check='No';

				if ($token != 0) {

					$param = array(
						'strAccountNumberFrom'=>$f_account_from,
						'strAccountNumberTo'=>$f_account_to,
						'fAmount'=>$f_amount,
						'strDescription'=>$f_description,
						'check'=>$check,
						'token'=>$token
					);
					$res = $client->call('addTransfer',$param);
//					$res = $client->call('testTransfer',$param);

					if ($res['Result']=="OK") {
						$cc_id1 = getAccountCCID($f_account_from);
						$cc_id2 = getAccountCCID($f_account_to);
						
						if (($cc_id1 > 0) && ($cc_id1 > 0)) {
							$str_insert = "INSERT INTO ".Monthalize('account_transfers')." (
								cc_id_from,
								cc_id_to,
								account_from,
								account_to,
								amount,
								description,
								module_id,
								module_record_id,
								date_created,
								user_id,
								transfer_status,
								date_completed)
								VALUES (
									$cc_id1,
									$cc_id2,
									'$f_account_from',
									'$f_account_to',
									$f_amount,
									\"".addslashes($f_description)."\",
									$f_module_id,
									$f_module_record_id,
									'".date('Y-m-d H:i:s',time())."',
									".$_SESSION['int_user_id'].",
									".ACCOUNT_TRANSFER_COMPLETE.",
									'".Date("Y-m-d h:i:s",time())."'
								)
							";
							$qry = new Query($str_insert);
						}
						else
							return 0;
					
					} else {
						if (substr($res['Result'],0,7) == 'ERR 002') {
							$cc_id1 = getAccountCCID($f_account_from);
							$cc_id2 = getAccountCCID($f_account_to);
							
							if (($cc_id1 > 0) && ($cc_id1 > 0)) {
								$str_insert = "INSERT INTO ".Monthalize('account_transfers')." (
									cc_id_from,
									cc_id_to,
									account_from,
									account_to,
									amount,
									description,
									module_id,
									module_record_id,
									date_created,
									user_id,
									transfer_status,
									date_completed)
									VALUES (
										$cc_id1,
										$cc_id2,
										'$f_account_from',
										'$f_account_to',
										$f_amount,
										\"".addslashes($f_description)."\",
										$f_module_id,
										$f_module_record_id,
										'".date('Y-m-d H:i:s',time())."',
										".$_SESSION['int_user_id'].",
										".ACCOUNT_TRANSFER_INSUFFICIENT_FUNDS.",
										'".Date("Y-m-d h:i:s",time())."'
									)
								";
								$qry = new Query($str_insert);
							}
							
							$b_result = -2;
						}
						else {
							$cc_id1 = getAccountCCID($f_account_from);
							$cc_id2 = getAccountCCID($f_account_to);
							
							if (($cc_id1 > 0) && ($cc_id1 > 0)) {
								$str_insert = "INSERT INTO ".Monthalize('account_transfers')." (
									cc_id_from,
									cc_id_to,
									account_from,
									account_to,
									amount,
									description,
									module_id,
									module_record_id,
									date_created,
									user_id,
									transfer_status,
									date_completed)
									VALUES (
										$cc_id1,
										$cc_id2,
										'$f_account_from',
										'$f_account_to',
										$f_amount,
										\"".addslashes($f_description)."\",
										$f_module_id,
										$f_module_record_id,
										'".date('Y-m-d H:i:s',time())."',
										".$_SESSION['int_user_id'].",
										".ACCOUNT_TRANSFER_ERROR.",
										'".Date("Y-m-d h:i:s",time())."'
									)
								";
								$qry = new Query($str_insert);
							}
							$b_result=0;
						}
					}
				
				}
				else 
					$b_result=0;
			}
			
			break;
		}
		return $b_result;
  }

  function createPTTransfer($f_account_from, $f_account_to, $f_description, $f_amount, $f_module_id, $f_module_record_id) {
	$b_result = -1;
	
	$cc_id_from = getPTAccountID($f_account_from);
	if ($cc_id_from <= 0)
		$cc_id_from = getPTAccountID($f_account_to);
	
	if ($cc_id_from > 0) {
		$str_query = "
			INSERT INTO ".Monthalize('account_pt_transfers')."
			(
				id_from,
				id_to,
				account_from,
				account_to,
				amount,
				description,
				module_id,
				module_record_id,
				user_id,
				date_created,
				date_completed,
				transfer_status
			)
			VALUES (".
				$cc_id_from.", ".
				"0, '".
				$f_account_from."', '".
				$f_account_to."', ".
				$f_amount.", '".
				$f_description."', ".
				$f_module_id.", ".
				$f_module_record_id.", ".
				$_SESSION['int_user_id'].", '".
				date('Y-m-d H:i:s',time())."', '".
				date('Y-m-d H:i:s',time())."', ".
				ACCOUNT_TRANSFER_COMPLETE.")";
		
		$qry = new Query($str_query);
		if (!$qry->b_error) {
			$b_result = $qry->getInsertedID();
		}
	
		// update the closing balance
		// first check whether a corresponding entry exists
		$str_query="
			SELECT * 
			FROM ".Monthalize('account_pt_balances')." 
			WHERE (account_id = $cc_id_from)";
		$qry->Query($str_query);
		
		if ($qry->RowCount() > 0) {
			$str_query = "
			UPDATE ".Monthalize('account_pt_balances')."
			SET closing_balance = closing_balance - ".$f_amount."
			WHERE (account_id = $cc_id_from)";
			$qry->Query($str_query);
		}
	}
	
	return $b_result;
  }
  
  function get_account_status($f_account_number) {
      $str_retval = '';

      $qry_account = new Query("
            SELECT *
            FROM account_cc
            WHERE account_number='".$f_account_number."'
      ");
      if ($qry_account->RowCount() > 0) {
      
	    $flt_balance = 0;
	    $str_active = 'N';

            if ($qry_account->RowCount() > 1) {
		  // =================================
		  // both account numbers are the same
                  // ---------------------------------
		  for ($i=0; $i<$qry_account->RowCount(); $i++) {
			$flt_balance += $qry_account->FieldByName('account_balance');
			$qry_account->Next();
		  }
		  
		  $qry_account->First();
		  for ($i=0; $i<$qry_account->RowCount(); $i++) {
			if ($qry_account->FieldByName('account_active') == 'Y') {
			      $str_active = 'Y';
			      break;
			}
			$qry_account->Next();
		  }
	    }
	    else {
		  $flt_balance = $qry_account->FieldByName('account_balance');
		  $str_active = $qry_account->FieldByName('account_active');
		  
		  if ($qry_account->FieldByName('cc_id') <> $qry_account->FieldByName('linked_cc_id')) {
			// ====================================================
			// different account numbers, so get the linked account
			// ----------------------------------------------------
			$qry_linked = new Query("SELECT *
			      FROM account_cc
			      WHERE cc_id = ".$qry_account->FieldByName('linked_cc_id')
			);
			
			for ($i=0; $i<$qry_linked->RowCount(); $i++) {
			      $flt_balance += $qry_linked->FieldByName('account_balance');
			      $qry_linked->Next();
			}
			
			if ($str_active == 'N') {
			      for ($i=0; $i<$qry_linked->RowCount(); $i++) {
				    if ($qry_linked->FieldByName('account_active') == 'Y') {
					  $str_active = 'Y';
					  break;
				    }
				    $qry_linked->Next();
			      }
			}
			
		  }
	    }
	    
	    $flt_balance = number_format($flt_balance, 2, '.', ',');
            $str_retval = "OK|".$str_active."|".$flt_balance;
      }
      else
            $str_retval = "FALSE|This account was not found. Load All the accounts and try again";
            
      return  $str_retval;
  }
  
  function getAccountCCID($f_account_number, $f_account_type=ACCOUNT_ANY) {
	switch ($_SESSION['connect_mode']) {
		case CONNECT_OFFLINE_FULL_ACCESS:
		case CONNECT_OFFLINE_LIMITED_ACCESS:
			if (BILL_DISABLED_ACCOUNTS == 1) {
				$qry_account = new Query("select * from account_cc where account_number='".$f_account_number."' order by account_type");
				$str_res = $qry_account->FieldByName('cc_id');
			}
			else {
				if ($f_account_type!=ACCOUNT_ANY) {
					$qry_account = new Query("select * from account_cc where account_number='".$f_account_number."' and account_type=$f_account_type and account_active='Y'");
				} else {
					$qry_account = new Query("select * from account_cc where account_number='".$f_account_number."' and account_active='Y' order by account_type");
				}
				if ($qry_account->RowCount()>0) {
					if (BILL_DISABLED_ACCOUNTS==1) {
						$str_res = $qry_account->FieldByName('cc_id');
					}
					else {
						if ($qry_account->FieldByName('account_enabled')=='Y') {
							$str_res = $qry_account->FieldByName('cc_id');
						} else {
							if ($qry_account->RowCount()>1) {
								$qry_account->Next();
								if ($qry_account->FieldByName('account_enabled')=='Y')
									$str_res = $qry_account->FieldByName('cc_id');
								else
								$str_res = "-1";
							}
							else
								$str_res = "-1";
						}
					}
				} else {
					$str_res = "-1";
				}
			}
			break;
		case CONNECT_ONLINE:
			if ($f_account_type!=ACCOUNT_ANY) {
                              $qry_account = new Query("select * from account_cc where account_number='".$f_account_number."' and account_type=$f_account_type");
			} else {
                              $qry_account = new Query("select * from account_cc where account_number='".$f_account_number."' order by account_type");
			}
			
			if ($qry_account->RowCount()>0) 
			{	
			      $str_res = $qry_account->FieldByName('cc_id');
			} else {
			      $str_res = "-1";
			}
			break;
            }
	
	return $str_res;
  }
  
function getPTAccountID($f_account_number) {
	$qry_account = new Query("
		SELECT account_id
		FROM account_pt
		WHERE (account_number = '".$f_account_number."')
	");
	
	if ($qry_account->RowCount() > 0) {
		$int_result = $qry_account->FieldByName('account_id');
	}
	else
		$int_result = -1;
	
	return $int_result;
}

  function getAccountName($f_account_number, $f_account_type) {
  	$str_res='|';
	switch ($_SESSION['connect_mode']) {
		case CONNECT_OFFLINE_FULL_ACCESS:
		case CONNECT_OFFLINE_LIMITED_ACCESS:
			if (BILL_DISABLED_ACCOUNTS == 1) {
				$qry_account = new Query("select * from account_cc where account_number='".$f_account_number."' and account_active='Y' order by account_type");
				if ($qry_account->RowCount() > 0) {
					if ($qry_account->FieldByName("account_active") == "N")
						$str_res = "ERROR:Account not active";
					else
						$str_res = "OK|".$qry_account->FieldByName('account_name');
				}
				else
					$str_res = "ERROR:Account not found";
				
			}
			else {
				if ($f_account_type!=ACCOUNT_ANY) {
					$qry_account = new Query("select * from account_cc where account_number='".$f_account_number."' and account_type=$f_account_type and account_active='Y'");
				} else {
					$str = "select * from account_cc where account_number='".$f_account_number."' and account_active='Y' order by account_type";
					$qry_account = new Query($str);
				}
				
				if ($qry_account->RowCount()>0) {
					$str_res = $qry_account->FieldByName('account_name');
					if ($qry_account->FieldByName('account_enabled')=='Y') {
						$str_res = "OK|".$str_res;
					} else {
					if ($qry_account->FieldByName('account_type') == 3) {
						$qry_linked = new Query("select * from account_cc where cc_id = ".$qry_account->FieldByName('linked_cc_id'));
						
						if ($qry_linked->RowCount() > 0) {
							if ($qry_linked->FieldByName('account_enabled')=='Y')
								$str_res = "OK|".$str_res;
							else
								$str_res = "Account ".$qry_account->FieldByName('account_name')." is disabled (No funds or locked)|".$str_res;
						}
						else
							$str_res = "Account ".$qry_account->FieldByName('account_name')." is disabled (No funds or locked)|".$str_res;
					}
					else
						$str_res = "Account ".$qry_account->FieldByName('account_name')." is disabled (No funds or locked)|".$str_res;
					}
				} else {
					$str_res = "ERROR:Account Not Found|";
				}
			}
			break;
		case CONNECT_ONLINE:
			if (file_exists("../admin/nusoap.php"))
			      include "../admin/nusoap.php";
			else if (file_exists("../../admin/nusoap.php"))
			      include "../../admin/nusoap.php";

			$loginInfo = array('PID'=>$_SESSION['int_application_pid'],'password'=>$_SESSION['int_application_pin']);

			$serverpath = ACCOUNT_SOAP_SERVER_URL;

			$client = new soapclient($serverpath);
			
			// login to SOAP server
			$res = $client->call('login',$loginInfo);

//return json_encode($res);
			
			if ($res['Result']=="OK") {
			
				// make a transfer
				$param = array('AccountNumber'=>$f_account_number);
				
				$res = $client->call('getAccountName',$param);
//return json_encode($res);
				
				if ($res['Result']=="OK") {
					$str_res = "OK|".$res['AccountName'];
				} else {
					$str_res=$res['Result']."($f_account_number)|";
				}
			} else 
				$str_res="ERROR: No-connection|";
			break;
			default: $str_res="ERROR: ".$_SESSION['connect_mode']." is not a mode!|";
	}
	return $str_res."|".$_SESSION['connect_mode'];
  }

  if (!empty($_GET['live'])) {
	    if ($_GET['live'] == 1) {
		  if (!empty($_GET['account_number'])) {
			echo getAccountName( ($_GET['account_number']), 0 ); 
			die();
		  } else {
			die(0);
		  }
	    }
	    else if ($_GET['live'] == 2) {
		  if (!empty($_GET['account_number'])) {
			echo get_account_status( ($_GET['account_number']), 0 ); 
			die();
		  } else {
			die(0);
		  }
	    }
  }

?>