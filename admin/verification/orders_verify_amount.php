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
		get orders that have zero in the 'amount' field
	*/
	$qry_orders = new Query("
			SELECT *
			FROM ".Monthalize('bill')."
			WHERE (total_amount = 0) AND (module_id = 7)
		");

	/*
		recalculate the amount and update in FS transfer, if exists, resetting flag to pending
	*/
	if (IsSet($_POST['action'])) {
		if ($_POST['action'] == 'mark_pending') {

			/*
				get the tax details of the current storeroom
			*/
			$qry_storeroom = new Query("
				SELECT *
				FROM stock_storeroom
				WHERE storeroom_id = ".$_SESSION['int_current_storeroom']."
			");
			$is_cash_taxed = 'Y';
			$is_account_taxed = 'Y';
			if ($qry_storeroom->RowCount() > 0) {
				$is_cash_taxed = $qry_storeroom->FieldByName('is_cash_taxed');
				$is_account_taxed = $qry_storeroom->FieldByName('is_account_taxed');
			}
			if ($is_account_taxed == 'Y')
				$calculate_tax = 'Y';

			/*
				iterate through the bills
				recalc amount and save
			*/
			$fn = "order_verify_amount_log_".time().".txt";
			$file = fopen($fn, "w");
			fwrite($file, "Verification run on ".date('d-m-Y',time())."\n\n");

			for ($i=0;$i<=$qry_orders->RowCount();$i++) {

				$anId = $qry_orders->FieldBYName('bill_id');

				$qry_bill_items = new Query("
					SELECT *
					FROM ".Monthalize('bill_items')."
					WHERE (bill_id = ".$anId.")
				");

				$total_amount = 0;

				if ($qry_bill_items->RowCount() > 0) {

					for ($j=0; $j<$qry_bill_items->RowCount(); $j++) {

						$flt_temp_price = $qry_bill_items->FieldByName('price');
						$flt_temp_qty = round($qry_bill_items->FieldByName('quantity') + $qry_bill_items->FieldByName('adjusted_quantity'), 3);
						$int_temp_tax_id = $qry_bill_items->FieldByName('tax_id');
						
						if ($calculate_tax == 'Y') {
							$tax_amount = calculateTax($flt_temp_price * $flt_temp_qty, $int_temp_tax_id);
							$flt_price_total = number_format(($flt_temp_qty * $flt_temp_price + $tax_amount), 3, '.', '');
						}
						else {
							$tax_amount = 0;
							$flt_price_total = number_format(($flt_temp_qty * $flt_temp_price), 3, '.', '');
						}
						
						$total_amount += number_format($flt_price_total, 3, '.', '');

						$qry_bill_items->Next();
					}

					$qry_update = new Query("
						UPDATE ".Monthalize('bill')."
						SET total_amount = '".number_format($total_amount, 2, '.', '')."'
						WHERE bill_id = ".$anId."
					");

					$str = "bill ".$qry_orders->FieldByName('bill_number')." :".number_format($total_amount, 2, '.', '')."\n";
				}
				else
					$str = "bill ".$qry_orders->FieldByName('bill_number')." : not items found for this bill \n";

				
				/*
					update web transfer if necessary
				*/
				$qry_transfer = new Query("
					SELECT *
					FROM ".Monthalize('account_transfers')."
					WHERE (module_id = 7) AND (module_record_id = ".$anId.")
				");
				if ($qry_transfer->RowCount()>0) {
					$qry_transfer = new Query("
						UPDATE ".Monthalize('account_transfers')."
						SET amount = '".number_format($total_amount, 2, '.', '')."', 
							transfer_status = '".ACCOUNT_TRANSFER_PENDING."'
						WHERE (module_id = 7) AND (module_record_id = ".$anId.")
					");

					$str .= "FS transfer updated \n";
				}
				else 
					$str .= "FS transfer NOT found - manual transfer required \n";

				$str .= "\n";

				fwrite($file, $str);

				$qry_orders->Next();
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
			if (confirm('Are you sure?'))
				document.orders_verify_amount.submit(); // location = 'fs_verify_web_transfers.php?day=int_day&action=mark_pending';
		}
    </script>
</head>

<body leftmargin='40px' rightmargin='20px' topmargin='40px' bottommargin='20px'>
<form name='orders_verify_amount' method="POST">

<?php
	boundingBoxStart("800", "../../images/blank.gif");

	if ($qry_orders->RowCount() > 0) {
		echo "There are ".$qry_orders->RowCount()." orders with total amount zero.<br>";
	
		for ($i=0;$i<$qry_orders->RowCount();$i++){
			echo "account ".$qry_orders->FieldByName("account_number")." - ".$qry_orders->FieldByName('account_name')."<br>";
		}

?>
	<br><br>
	<input type='button' name='action' value='Recalc amount and reset transfers as "Pending"' onclick='setPending()'>
	<input type='hidden' name='action' value='mark_pending'>

<?php
	}
	else {
		echo "There are no orders with zero.";
	}
?>

<?php
	boundingBoxEnd("800", "../../images/blank.gif");
?>

</form>
</body>
</html>