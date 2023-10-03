<?
	include("../../include/const.inc.php");
	include("../../include/session.inc.php");
	include("../../include/db.inc.php");
	include('../../admin/nusoap.php');
	
	function get_day($str_date) {
		$str_temp = substr($str_date, 0, 10);
		$str_temp = substr($str_date, 0, 2);
		return intval($str_temp);
	}
	
	function get_transfer_status($int_status) {
		if ($int_status == ACCOUNT_TRANSFER_PENDING)
			return "Pending";
		else if ($int_status == ACCOUNT_TRANSFER_INSUFFICIENT_FUNDS)
			return "Insufficient Funds";
		else if ($int_status == ACCOUNT_TRANSFER_ERROR)
			return "Error";
		else if ($int_status == ACCOUNT_TRANSFER_CANCELLED)
			return "Cancelled";
		else if ($int_status == ACCOUNT_TRANSFER_HOLD)
			return "Hold";
		else if ($int_status == ACCOUNT_TRANSFER_COMPLETE)
			return "Completed";
		else if ($int_status == ACCOUNT_TRANSFER_REVIEW)
			return "Review";
	}
	
	$int_day = 1;
	if (IsSet($_GET['day']))
		$int_day = $_GET['day'];
		
	if (IsSet($_POST['day']))
		$int_day = $_POST['day'];
		
	//===================
	// get the FS account
	//-------------------
	$qry_account = new Query("
		SELECT bill_credit_account
		FROM stock_storeroom
		WHERE (storeroom_id = ".$_SESSION['int_current_storeroom'].")
	");
	$credit_account = $qry_account->FieldByName('bill_credit_account');
	
	//=======================
	// SOAP server connection
	//-----------------------
	$loginInfo = array(
		'PID'=>$_SESSION['int_application_pid'],
		'password'=>$_SESSION['int_application_pin']
	);
	
	// define path to server application
	$serverpath = ACCOUNT_SOAP_SERVER_URL;
	
	// create client object
	$client = new soapclientw($serverpath);
	
	// login to SOAP server
	$res = $client->call('login', $loginInfo);
	
	if ($res['Result']=="OK") {
		$param = array(
			'int_month'				=> $_SESSION['int_month_loaded'],
			'int_year'				=> $_SESSION['int_year_loaded'],
			'int_day'				=> $_GET['day'],
			'str_account_number'    => $credit_account
		);
		
		//================================================
		// the array returned has structure:
		//      ["R_".$i."_from"] = from account
		//      ["R_".$i."_date"] = transfer date
		//      ["R_".$i."_amount"] = amount
		//      ["R_".$i."_description"] = transfer description
		//      ["R_".$i."_status"] = status of transfer;
		//------------------------------------------------
		
		$arr_result = $client->call('getWebTransfers', $param);
//print_r($arr_result);
		$arr_discrepancies = array();
		$int_counter = 0;
		
		if ($arr_result['Result'] == "OK") {
			//=========================================================================
			// create temporary tables that will hold all the transfers that match
			// or don't match with the local copy
			//-------------------------------------------------------------------------
			$qry = new Query("
				CREATE TEMPORARY TABLE tmp_matching_transfers
					LIKE ".Monthalize('account_transfers')."
			");
			$qry->Query("
				CREATE TEMPORARY TABLE tmp_mismatching_transfers
					LIKE ".Monthalize('account_transfers')."
			");
			
			$qry_insert = new Query("SELECT * FROM stock_product LIMIT 1");
			
			//=========================================================================
			// iterate through the returned array that holds all the transfers from
			// the server
			//-------------------------------------------------------------------------
			for ($i=0;$i<$arr_result['RecordCount'];$i++) {
				
				// only check descriptions that contain "BN:"
				$str_description = substr($arr_result["R_".$i."_description"],0,3);
				if ($str_description == 'BN:') {
					$str_timestamp = $arr_result["R_".$i."_date"];
					$str_date = set_formatted_date(date('Y-m-d H:i:s',$str_timestamp),'-');
					$int_day = get_day($str_date);
					
					if ($arr_result["R_".$i."_status"] == 'C')
						$str_status = ACCOUNT_TRANSFER_COMPLETE;
					else if ($arr_result["R_".$i."_status"] == 'P')
						$str_status = ACCOUNT_TRANSFER_PENDING;
					else if ($arr_result["R_".$i."_status"] == 'M')
						$str_status = ACCOUNT_TRANSFER_REVIEW;
					else if ($arr_result["R_".$i."_status"] == 'X')
						$str_status = ACCOUNT_TRANSFER_CANCELLED;
					
					$str_query = "
						SELECT *
						FROM ".Monthalize('account_transfers')."
						WHERE (account_from = '".$arr_result["R_".$i."_from"]."')
							AND (description = '".$arr_result["R_".$i."_description"]."')
							AND (
								(DAY(date_completed) = $int_day)
								AND (MONTH(date_completed) = ".$_SESSION['int_month_loaded'].")
								AND (YEAR(date_completed) = ".$_SESSION['int_year_loaded'].")
							)
						";
					$qry->Query($str_query);
					
					//=========================================================================
					// if a matching entry was found, enter it in the temporary table
					//-------------------------------------------------------------------------
					if ($qry->RowCount() > 0) {
						$str_query = "
							INSERT INTO tmp_matching_transfers
							(
								account_from,
								amount,
								date_completed,
								transfer_status,
								description
							)
							VALUES (
								'".$arr_result["R_".$i."_from"]."',
								".$arr_result["R_".$i."_amount"].",
								'".set_mysql_date($str_date, '-')."',
								".$str_status.",
								'".$arr_result["R_".$i."_description"]."'
							)";
						$qry_insert->Query($str_query);
					}
					//=========================================================================
					// if not, enter it in the temporary table
					//-------------------------------------------------------------------------
					else {
						$qry_insert->Query("
							INSERT INTO tmp_mismatching_transfers
							(
								account_from,
								amount,
								date_completed,
								transfer_status,
								description
							)
							VALUES (
								'".$arr_result["R_".$i."_from"]."',
								".$arr_result["R_".$i."_amount"].",
								'".set_mysql_date($str_date,'-')."',
								".$str_status.",
								'".$arr_result["R_".$i."_description"]."'
							)
						");
					}
				}
			}
			
			$qry_not_found = new Query("
				SELECT *
				FROM ".Monthalize('account_transfers')." at
				WHERE (
					DAY(date_completed) = $int_day
					AND (MONTH(date_completed) = ".$_SESSION['int_month_loaded'].")
					AND (YEAR(date_completed) = ".$_SESSION['int_year_loaded'].")
				)
				AND NOT	EXISTS (
					SELECT *
					FROM tmp_matching_transfers tmt
					WHERE at.account_from = tmt.account_from
						AND at.description = tmt.description
				)
			");
			$int_not_found = $qry_not_found->RowCount();
			
			if (IsSet($_POST['action'])) {
				if ($_POST['action'] == 'mark_pending') {
					foreach ($_POST as $key=>$value) {
						$str_temp = substr($key, 0, 6);
						if ($str_temp =='check_') {
							$int_transfer_id = substr($key,6,strlen($key));
							
							$qry->Query("
								UPDATE ".Monthalize('account_transfers')."
								SET transfer_status = ".ACCOUNT_TRANSFER_PENDING."
								WHERE transfer_id = ".$int_transfer_id."
								LIMIT 1
							");
						}
					}
					
					$qry_not_found->Query("
						SELECT *
						FROM ".Monthalize('account_transfers')." at
						WHERE (
							DAY(date_completed) = $int_day
							AND (MONTH(date_completed) = ".$_SESSION['int_month_loaded'].")
							AND (YEAR(date_completed) = ".$_SESSION['int_year_loaded'].")
						)
						AND NOT	EXISTS (
							SELECT *
							FROM tmp_matching_transfers tmt
							WHERE at.account_from = tmt.account_from
								AND at.description = tmt.description
						)
					");
				}
			}
			
			$qry->Query("SELECT * FROM tmp_matching_transfers");
			$int_matching = $qry->RowCount();
			
			$qry->Query("SELECT * FROM tmp_mismatching_transfers");
			$int_mismatching = $qry->RowCount();
			
			$qry->Query("DROP TABLE tmp_matching_transfers");
			$qry->Query("DROP TABLE tmp_mismatching_transfers");
		}
	}
	else
		echo "Could not login to the server";
	
?>
<html>
<head>
    <link href="../../include/styles.css" rel="stylesheet" type="text/css">
    <script language='javascript'>
		function goBack() {
			document.location = '../index_verification_tools.php';
		}
		function setPending() {
			if (confirm('Are you sure?'))
				document.fs_verify_web_transfers.submit(); // location = 'fs_verify_web_transfers.php?day=int_day&action=mark_pending';
		}
    </script>
</head>

<body leftmargin='40px' rightmargin='20px' topmargin='40px' bottommargin='20px'>
<form name='fs_verify_web_transfers' method="POST">
<?
	boundingBoxStart("800", "../../images/blank.gif");
		
		if ($res['Result'] == "OK") {
			
			echo "There are ".$arr_result['RecordCount']." transfers for ".$int_day."/".$_SESSION['int_month_loaded']."/".$_SESSION['int_year_loaded']." for FS account ".$credit_account;
?>
			<br>
			<table>
				<tr>
					<td class='normaltext'><?echo $int_not_found?> transfers were not found on the server
						<input type='button' name='action' value='Mark these transfers as Pending' onclick='setPending()'>
						<input type='hidden' name='action' value='mark_pending'>
						<input type='hidden' name='int_day' value='<?echo $int_day?>'>
						<table border='0' cellpadding='4'>
							<?
								for ($i=0;$i<$qry_not_found->RowCount();$i++) {
									if ($i % 2 == 0)
										$str_color="#eff7ff";
									else
										$str_color="#deecfb";
										
									echo "<tr bgcolor='$str_color' class='normaltext'>";
									if ($qry_not_found->FieldByName('transfer_status') == ACCOUNT_TRANSFER_PENDING)
										echo "<td><input type='checkbox' name='check_".$qry_not_found->FieldByName('transfer_id')."'></td>";
									else
										echo "<td><input type='checkbox' name='check_".$qry_not_found->FieldByName('transfer_id')."' checked></td>";
									echo "<td>".$qry_not_found->FieldByName('transfer_id')."</td>";
									echo "<td>".$qry_not_found->FieldByName('account_from')."</td>";
									echo "<td>".$qry_not_found->FieldByName('amount')."</td>";
									echo "<td>".$qry_not_found->FieldByName('description')."</td>";
									echo "<td>".set_formatted_date($qry_not_found->FieldByName('date_completed'),'-')."</td>";
									echo "<td>".get_transfer_status($qry_not_found->FieldByName('transfer_status'))."</td>";
									echo "</tr>";
									
									$qry_not_found->Next();
								}
							?>
						</table>
						
					</td>
				</tr>
				<tr>
					<td class='normaltext'><?echo $int_matching?> transfers matched with the local database</td>
				</tr>
				<tr>
					<td class='normaltext'><?echo $int_mismatching?> transfers did not match with the local database</td>
				</tr>
			</table>
			<br>
			<input type='button' name='action' value='Back' class='settings_button' onclick='javascript:goBack()'>
<?
		}
	
	boundingBoxEnd("800", "../../images/blank.gif");
?>

</form>
</body>
</html>