<?
// include the SOAP classes
require_once('nusoap.php');

require_once('../include/const.inc.php');
require_once('../include/session.inc.php');
require_once('../include/db.inc.php');
?>
<html><title>Financial Service Export</title>
<body>
<code><pre>
<?php

$loginInfo = array('PID'=>$_SESSION['int_application_pid'],'password'=>$_SESSION['int_application_pin']);

// define path to server application
$serverpath = ACCOUNT_SOAP_SERVER_URL;

// create client object
$client = new soapclient($serverpath);

// login to SOAP server
$res = $client->call('login',$loginInfo);

if (empty($_GET['start'])) {
	$start=0;
} else {
	$start=$_GET['start'];
}

$str_transfer_type = 'pending';
if (IsSet($_GET['transfer_type']))
	$str_transfer_type = $_GET['transfer_type'];

if ($str_transfer_type == 'pending') {
	$qry = new Query(
		"SELECT *
		FROM ".Monthalize('account_transfers')." 
		WHERE transfer_status=".ACCOUNT_TRANSFER_PENDING."
		LIMIT 0,10
	");
}
else {
	$qry = new Query(
		"SELECT *
		FROM ".Monthalize('account_transfers')." 
		WHERE transfer_status=".ACCOUNT_TRANSFER_INSUFFICIENT_FUNDS."
		LIMIT $start,10
	");
}


ob_start();
echo "Starting export of ".$qry->RowCount()." transfers...\n";

if ($res['Result']=="OK") {
	$qry2 = new Query("select * from ".Monthalize('account_transfers')." LIMIT 0,1");

	for ($i=0; $i < $qry->RowCount();$i++) {

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
				'strAccountNumberFrom'=>$qry->FieldByName('account_from'),
				'strAccountNumberTo'=>$qry->FieldByName('account_to'),
				'fAmount'=>$qry->FieldByName('amount'),
				'strDescription'=>$qry->FieldByName('description'),
				'check'=>$check,
				'token'=>$token
			);
			$res = $client->call('addTransfer',$param);

			if ($res['Result']=="OK") {
				// ... TODO: Insert code on successful transaction
				echo "Completed tranfer of Rs. ".number_format($qry->FieldByName('amount'),2,'.',',')." from #".$qry->FieldByName('account_from')." to ".$qry->FieldByName('account_to')."\n";
				
				$str_update = "UPDATE ".Monthalize('account_transfers')." SET
						transfer_status=".ACCOUNT_TRANSFER_COMPLETE.",date_completed='".Date("Y-m-d h:i:s",time())."' WHERE
						transfer_id=".$qry->FieldByName('transfer_id');
		
				
				$qry2->Query($str_update);
				
				
			} else {
				echo "Failed on #".$qry->FieldByName('account_from').": ".$res['Result']."\n";
				if (substr($res['Result'],0,7)=='ERR 002') {
					$int_status = ACCOUNT_TRANSFER_INSUFFICIENT_FUNDS;
				} else $int_status = ACCOUNT_TRANSFER_ERROR;

				$str_update = "UPDATE ".Monthalize('account_transfers')." SET
						transfer_status=".$int_status." WHERE
						transfer_id=".$qry->FieldByName('transfer_id')."\n";
		
				
				$qry2->Query($str_update);
			
			}
		}
		else {
			echo "Token Error on #".$qry->FieldByName('account_from').": ".$res['Result']."\n";
		}

		$qry->Next();
 	}

} 
else 
	die($res['Result']);


// kill object
unset($client);

?></pre></code>
<br>
<?
	if ($qry->RowCount() == 10) {
		$time_delay = 5000;
		$start = $start + $qry->RowCount();
		echo "Waiting 5 seconds...";
	} else {
		$time_delay = 60000;
		echo "Waiting 1 minute...";
	}
?>

<script language="javascript">
	setTimeout("document.location = 'accounttransfer.php?start=<?echo $start;?>&transfer_type=<?echo $str_transfer_type?>';",<? echo $time_delay;?>); 
</script>

</body></html>