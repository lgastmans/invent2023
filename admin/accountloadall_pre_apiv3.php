<?php

require_once('nusoap.php');
require_once('../include/const.inc.php');
require_once('../include/session.inc.php');
require_once('../include/db.inc.php');

?>
<html>
<head><TITLE></TITLE>
	<link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>
<body><br><br>
<? boundingBoxStart("750",'../images/blank.gif', '200px'); ?>
<font class='normaltext'><font class='title'>Importing All Accounts</font><br><br>
<?php

$loginInfo = array('PID'=>$_SESSION['int_application_pid'],'password'=>$_SESSION['int_application_pin']);
//echo $_SESSION['int_application_pid']."::".$_SESSION['int_application_pin'];
// define path to server application
$serverpath = ACCOUNT_SOAP_SERVER_URL;
//echo "0";
// create client object
$client = new soapclientw($serverpath);
//echo $serverpath;

// login to SOAP server
// UNDO
$res = $client->call('login',$loginInfo); 


//print_r($loginInfo);
//echo "2::";
//print_r($res);
//echo $res['Result'];
$qry = new Query("select * from account_cc LIMIT 1,1");
ob_start();

// UNDO
//$res['Result']="FALSE";

if ($res['Result']=="OK") {
	// make a transfer
	$param = array(0=>"");

	if (empty($_GET['start'])) {
		$res = $client->call('getNumAccounts',$param);
        print_r($res);
        die('first here');
		$start = 0;
		$size = 100;
		$count = $res['RecordCount'];
	} else {
		$start = $_GET['start'];
		$size = $_GET['size'];
		$count = $_GET['count'];
	}
	echo "<img src='../images/graydot.gif' height=15 width=".round($start*600/$count)."><br> ".round($start*100/$count + 1)."% complete";
	echo "<code><pre>";
	
//	if ($res['Result']=="OK") 
	
		// ... TODO: Insert code on successful transaction
//		echo $res['RecordCount']." rows in the database\n";
		
		$param = array('intFrom'=>$start,'intCount'=>$size);
		
		$res2 = $client->call('getAccountRange',$param);
		
        print_r($res2);
		die('here');

		for ($i=0;$i < $res2['RecordCount'];$i++) {
			

			
/*			$qry->Query("
				DELETE FROM account_cc 
				WHERE account_number=".$res2['R'.$i."_Number"]."
					AND cc_id=".$res2['R'.$i."_CCID"]."
				");*/
				
			$qry->Query("
				DELETE FROM account_cc 
				WHERE cc_id=".$res2['R'.$i."_CCID"]."
			");
				
			/*
				if there is an account in the system that has been flagged 
				inactive in the online db, this will flag it as inactive,
				and the new account is re-imported
			*/
			$qry->Query("
				UPDATE account_cc 
				SET account_active='N' 
				WHERE account_number='".$res2['R'.$i."_Number"]."' 
					AND account_type=".$res2['R'.$i."_Type"]
			);
			
			if (($_SESSION['connect_mode']==CONNECT_OFFLINE_FULL_ACCESS) || (DOWNLOAD_ALL == 1)) {
					$str_update = "INSERT INTO account_cc (cc_id,
						linked_cc_id,
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
						" . (($res2['R'.$i."_CurrentBalance"])?$res2['R'.$i."_CurrentBalance"]:0).",
						'" . (($res2['R'.$i."_Disable"]==0)?'Y':'N')."',
						'".$res2['R'.$i."_Active"]."',
						'" . (($res2['R'.$i."_MayGoBelow"]==1)?'Y':'N')."',
						" . (($res2['R'.$i."_CreditLine"])?$res2['R'.$i."_CreditLine"]:0).",
						'" . addslashes($res2['R'.$i."_Community"])."')";
			} else {
					$str_update = "INSERT INTO account_cc (cc_id,
						account_name,
						account_number,
						account_type,
						account_enabled,
						account_active,
						community,
						linked_cc_id)
						VALUES (
						" . $res2['R'.$i."_CCID"].",
						'" . addslashes($res2['R'.$i."_Name"])."',
						'" . $res2['R'.$i."_Number"]."',
						" . $res2['R'.$i."_Type"].",
						'" . (($res2['R'.$i."_Disable"]==0)?'Y':'N')."',
						'".$res2['R'.$i."_Active"]."',
						'" . addslashes($res2['R'.$i."_Community"])."',
						" . $res2['R'.$i."_LinkedCCID"].")";

			}
			$qry->Query($str_update);
//			echo $res2['R'.$i."_CCID"]." ". $res2['R'.$i."_Number"]." - ".$res2['R'.$i."_Name"]."\n";
			ob_flush();
		
		}
// 		$res = $client->call('clearModifiedAccounts',$param);		
	 /*else {
	  echo "failed";
	  die($res['Result']);
	}*/

	$qry->Query("
		UPDATE user_settings
		SET admin_last_loadall = '".date('Y-m-d H:i:s', time())."'
	");
	
} 
else {
	//UNDO
	die($res['Result']);
	
}
// kill object
unset($client);



?>

<font class='normaltext'>
	
<? boundingBoxEnd("650"); 

?>
</font>
<br>
<script language="javascript">
<? 
	$start += $size;
	if ($start<$count-1) {
	?>	
setTimeout("document.location = 'accountloadall.php?size=<? echo $size."&count=$count&start=$start"; ?>';",1000); 
	<? } ?>
</script>


<br>

</body>