<? 
require_once('../include/const.inc.php');
require_once('../include/session.inc.php');
require_once('../include/db.inc.php');

?>
<html>
<head><TITLE></TITLE>
	<link href="../include/styles.css" rel="stylesheet" type="text/css">
</head>

<body>

<font class='title'>Updating FS Accounts</font><br><br>

<font class='normaltext'>
<?php

		/*
		 * get orders where the cc_id is linked to an inactive account
		 */
		$qry = new Query("
			SELECT DISTINCT ac.cc_id, ac.account_number, ac.account_name 
			FROM account_cc ac, ".Monthalize('orders')." o
			WHERE ac.account_active =  'N'
			AND o.cc_id = ac.cc_id
		");
	
		if ($qry->RowCount()>0) {
			
			echo "The following ".$qry->RowCount()." accounts have orders that have been updated:<br>";
			
			for ($i=0;$i<$qry->RowCount();$i++) {
				// get the account number
				$ac_num = $qry->FieldByName('account_number');
				
				$orders = new Query("
					SELECT cc_id
					FROM account_cc ac
					WHERE account_number = '$ac_num' AND account_active = 'Y' 
				");
				
				if ($orders->RowCount()>0) {
					$cc_id_old = $qry->FieldByName('cc_id');
					$cc_id_new = $orders->FieldByName('cc_id');
					
					$orders->Query("
						UPDATE ".Monthalize('orders')." 
						SET cc_id = $cc_id_new
						WHERE cc_id = $cc_id_old
					");
					echo $ac_num." - ".$qry->FieldByName('account_name')." has not been updated <br>";
				}
				
				$qry->Next();
			}
		}
		else
			echo "0 accounts have been updated in the orders<br><br>";
			
			
		/*
		 * get transfers where the cc_id is linked to an inactive account
		 */
		$qry->Query("
			SELECT DISTINCT ac.cc_id, ac.account_number, ac.account_name 
			FROM account_cc ac, ".Monthalize('account_transfers')." at
			WHERE ac.account_active =  'N'
			AND at.cc_id_from = ac.cc_id
		");
	
		if ($qry->RowCount()>0) {		
			echo $qry->RowCount()." transfers have been updated<br>";
			
			for ($i=0;$i<$qry->RowCount();$i++) {
				// get the account number
				$ac_num = $qry->FieldByName('account_number');
				
				$transfers = new Query("
					SELECT cc_id
					FROM account_cc ac
					WHERE account_number = '$ac_num' AND account_active = 'Y' 
				");
				
				if ($transfers->RowCount()>0) {
					$cc_id_old = $qry->FieldByName('cc_id');
					$cc_id_new = $transfers->FieldByName('cc_id');
					
					$transfers->Query("
						UPDATE ".Monthalize('account_transfers')." 
						SET cc_id_from = $cc_id_new
						WHERE cc_id_from = $cc_id_old
					");
				}
				
				$qry->Next();
			}
			
		}
		else {
			echo $qry->RowCount()." transfers have been updated<br>";
		}	
	?>


<br>


</font>
</body>
</html>