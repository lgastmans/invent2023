<?php
	include("../../include/const.inc.php");
	include("../../include/session.inc.php");
	include("../../include/db.inc.php");
	include('../../admin/nusoap.php');
	require_once("../../common/tax.php");

	
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
	
	/*
		get duplicate transfers
		http://stackoverflow.com/questions/11694761/select-and-display-only-duplicate-records-in-mysql
	*/
	$qry_duplicates = new Query("
		SELECT * 
		FROM ".Monthalize('bill')."
		WHERE bill_id NOT IN ( 
			SELECT bill_id
			FROM ".Monthalize('bill')." 
			GROUP BY bill_number, payment_type, MONTH(date_created)
			HAVING ( COUNT(*) = 1)
		)
		ORDER BY bill_number
	");

	/*
		recalculate the amount and update in FS transfer, if exists, resetting flag to pending
	*/
	if (IsSet($_POST['action'])) {

		if ($_POST['action'] == 'mark_pending') {

			/*
				iterate through the transfers
				remove the transfer where amount is zero
				and transfer status ERROR
			*/
			$fn = "order_duplicate_transfers_log_".time().".txt";
			$file = fopen($fn, "w");
			fwrite($file, "Verification run on ".date('d-m-Y',time())."\n\n");

			$str = '';

			for ($i=0;$i<=$qry_duplicates->RowCount();$i++) {

				$qry_transfer = new Query("
					SELECT *
					FROM ".Monthalize('account_transfers')."
					WHERE (transfer_id = ".$qry_duplicates->FieldByName('transfer_id').")
				");
				if ($qry_transfer->RowCount()>0) {

					if (($qry_transfer->FieldByName('amount')==0) && ($qry_transfer->FieldBYName('transfer_status')==ACCOUNT_TRANSFER_ERROR)) {

						$qry_transfer = new Query("
							DELETE FROM ".Monthalize('account_transfers')."
							WHERE (transfer_id = ".$qry_duplicates->FieldByName('transfer_id').")
						");

						$str .= "FS transfer deleted: amount ".$qry_transfer->FieldByName('amount').", number ".$qry_transfer->FieldByName('account_from')."\n";
					}
					else $str .= "FS transfer not deleted: ".$qry_transfer->FieldBYName('transfer_id')."\n";
				}
				else 
					$str .= "FS transfer NOT found: ".$qry_transfer->FieldBYName('transfer_id')." \n";

				$str .= "\n";

				fwrite($file, $str);

				$qry_duplicates->Next();
			}
			
			fclose($file);

			die("DONE - SEE LOG FILE ".$fn." FOR DETAILS");
		}
	}
?>


<html>
<head>
    <link href="../../include/styles.css" rel="stylesheet" type="text/css">
    <script language='javascript'>
		function goBack() {
			document.location = '../index_verification_tools.php';
		}
		function setPending() {
			/*
			if (confirm('Are you sure?'))
				document.orders_duplicate_transfers.submit();
			*/
		}
    </script>
</head>

<body leftmargin='40px' rightmargin='20px' topmargin='40px' bottommargin='20px'>
<form name='orders_duplicate_transfers' method="POST">

<?php
	boundingBoxStart("800", "../../images/blank.gif");

	if ($qry_duplicates->RowCount() > 0) {
		echo "There are ".$qry_duplicates->RowCount()." duplicate transfers.<br>";
		
		echo "<table>";
		for ($i=0;$i<$qry_duplicates->RowCount();$i++){
			echo "<tr>";
				echo "<td>".$qry_duplicates->FieldByName("bill_number")."</td><td>".
					$qry_duplicates->FieldByName('date_created')."</td><td>".
					$qry_duplicates->FieldByName('total_amount')."</td>";
			echo "</tr>";
			$qry_duplicates->next();
		}
		echo "</table>";

?>
	<br><br>
	<input type='button' disabled name='action' value='Remove duplicate transfers' onclick='setPending()'>
	<input type='hidden' name='action' value='mark_pending'>

<?php
	}
	else {
		echo "There are no duplicate bills.";
	}
?>

<?php
	boundingBoxEnd("800", "../../images/blank.gif");
?>

</form>
</body>
</html>