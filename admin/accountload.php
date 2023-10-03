<html>
<head><meta http-equiv="refresh" content="30"></head>
<body>
<code><pre>
<?php
error_reporting(E_ERROR);
// include the SOAP classes
require_once('nusoap.php');
require_once('../include/const.inc.php');
require_once('../include/session.inc.php');
require_once('../include/db.inc.php');
$loginInfo = array('PID'=>$_SESSION['int_application_pid'],'password'=>$_SESSION['int_application_pin']);

// define path to server application
$serverpath = ACCOUNT_SOAP_SERVER_URL;

// create client object
$client = new soapclientw($serverpath);

// login to SOAP server
$res = $client->call('login',$loginInfo);

$qry = new Query("select * from account_cc LIMIT 1,1");
if ($res['Result']=="OK") {

	// make a transfer
	$param = array();
	
	$res = $client->call('getNumModifiedAccounts',$param);
	
	if ($res['Result']=="OK") {
		// ... TODO: Insert code on successful transaction
		echo $res['RecordCount']." rows in the database\n";
		
		$param = array('intFrom'=>'0','intCount'=>$res['RecordCount']);
		
		$res2 = $client->call('getModifiedAccountRange',$param);
		for ($i=0;$i < $res['RecordCount'];$i++) {
			$qry->Query("delete from account_cc where cc_id=".$res2['R'.$i."_CCID"]);
			if (($_SESSION['connect_mode']==CONNECT_OFFLINE_FULL_ACCESS) || (DOWNLOAD_ALL == 1)){
					$str_update = "INSERT INTO account_cc (cc_id,
						linked_cc_id
						account_name,
						account_number,
						account_type,
						account_balance,
						account_enabled,
						account_active,
						account_may_go_below,
						account_credit_line,
						community)
						VALUES (
						" . $res2['R'.$i."_CCID"].",
						" . $res2['R'.$i."_LinkedCCID"].",
						'" . addslashes($res2['R'.$i."_Name"])."',
						'" . $res2['R'.$i."_Number"]."',
						" . $res2['R'.$i."_Type"].",
						" . $res2['R'.$i."_CurrentBalance"].",
						'" . ($res2['R'.$i."_Disable"]==0?'Y':'N')."',
						'".$res2['R'.$i."_Active"]."',
						'" . ($res2['R'.$i."_MayGoBelow"]==1?'Y':'N')."',
						" . $res2['R'.$i."_CreditLine"].",
						'" . addslashes($res2['R'.$i."_Community"])."')";
			} else {
					$str_update = "INSERT INTO account_cc (cc_id,
						account_name,
						account_number,
						account_type,
						account_enabled,
						account_active,
						community,
						linked_CC_id)
						VALUES (
						" . $res2['R'.$i."_CCID"].",
						'" . addslashes($res2['R'.$i."_Name"])."',
						'" . $res2['R'.$i."_Number"]."',
						" . $res2['R'.$i."_Type"].",
						'" . ($res2['R'.$i."_Disable"]=='0'?'Y':'N')."',
						'".$res2['R'.$i."_Active"]."',
						'" . addslashes($res2['R'.$i."_Community"])."',
						" . $res2['R'.$i."_LinkedCCID"].")";

			}
//			print_r($res2);
			$qry->Query($str_update);
			echo $res2['R'.$i."_CCID"]." ". $res2['R'.$i."_Number"]." - ".$res2['R'.$i."_Name"]."\n";
		
		}
 		$res = $client->call('clearModifiedAccounts',$param);		
		echo "Result of clear accounts - ".$res['Result'];
	} else {
	  echo "failed";
	  die($res['Result']);
	}
} else {
print_r($res);
die("Error Logging in: ");
}

// kill object
unset($client);

?></pre></code>
<br>
Waiting 1 minute...
<script language="javascript">
//setTimeout("document.location = 'accountload.php';",60000); 
</script>

</body></html>