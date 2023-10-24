<?
	include("../../include/const.inc.php");
	include("../../include/session.inc.php");
	include("../../include/db.inc.php");
	include("../../common/account.php");
	
	function getBillDate($int_day) {
		$str_date = $_SESSION["int_year_loaded"]."-".sprintf("%02d", $_SESSION["int_month_loaded"])."-".$int_day." ".date("H:i:s");
		return $str_date;
	}
	
	
	$sql = "
		SELECT b.bill_id, b.bill_number, b.date_created, b.total_amount,
			m.description,
			ac.account_number, ac.account_name
		FROM ".Monthalize('bill')." b, account_cc ac, module m
		WHERE (payment_type = ".BILL_ACCOUNT.")
			AND (bill_status = ".BILL_STATUS_RESOLVED.")
			AND (b.cc_id = ac.cc_id)
			AND (b.module_id = m.module_id)
			AND (b.module_id=7)
			AND NOT EXISTS (
				SELECT *
				FROM ".Monthalize('account_transfers')." at
				WHERE (at.module_record_id = b.bill_id) AND (at.module_id = 7)
			)
		ORDER BY b.module_id
	";


/*
	$str_bills = "
		SELECT b.bill_id, b.bill_number, b.date_created, b.total_amount, ac.account_number, ac.account_name, o.is_billable
		FROM (`".Monthalize('bill')."` b, `account_cc` ac)
		LEFT JOIN (".Monthalize('orders')." o) ON (o.order_id = b.module_record_id)
		WHERE (
			b.bill_status = 2
		)
		AND (
			b.payment_type = 2
		)
		AND (
			b.cc_id = ac.cc_id
		)
		AND NOT EXISTS (
			SELECT *
			FROM ".Monthalize('account_transfers')." at
			WHERE (
				at.module_record_id = b.bill_id
			)
		)
		ORDER BY `b`.`bill_number`, `ac`.`account_number` ASC
	";
*/


	$qry_bills = new Query($sql);

	$int_discrepancies = $qry_bills->RowCount();
/*
	$int_discrepancies = $qry_bills->RowCount();
	$grand_total = 0;
	
	$str_orders = "
		SELECT b.bill_id, b.bill_number, b.date_created, b.total_amount,
			ac.account_number, ac.account_name
		FROM ".Monthalize('bill')." b, account_cc ac, ".Monthalize('orders')." o
		WHERE (b.payment_type = ".BILL_ACCOUNT.")
			AND (bill_status = ".BILL_STATUS_RESOLVED.")
			AND (b.cc_id = ac.cc_id)
			AND (b.module_id = 7)
			AND (b.module_record_id = o.order_id)
			AND (o.is_billable = 'Y')
		ORDER BY b.cc_id, b.date_created
	";
	
	$qry_orders = new Query($str_orders);
	
	
	$arr_found = array();
	
	for ($i=0;$i<$qry_bills->RowCount();$i++) {
		
		$arr_found[$i]['account_number'] = $qry_bills->FieldByName('account_number');
		$arr_found[$i]['account_name'] = $qry_bills->FieldByName('account_name');
		$arr_found[$i]['bill_id'] = $qry_bills->FieldByName('bill_id');
		$arr_found[$i]['bill_number'] = $qry_bills->FieldByName('bill_number');
		$arr_found[$i]['total_amount'] = $qry_bills->FieldByName('total_amount');
		$arr_found[$i]['module'] = 'bills';
		$arr_found[$i]['date_created'] = $qry_bills->FieldByName('date_created');//makeHumanDate($qry_bills->FieldByName('date_created'));
		
		$grand_total += $qry_bills->FieldByName('total_amount');
		
		$qry_bills->Next();
	}
	
	for ($i=0;$i<$qry_orders->RowCount();$i++) {
		$str = "SELECT * FROM ".Monthalize('account_transfers')." at WHERE (at.module_record_id = ".$qry_orders->FieldByName('bill_id').")";
		
		$check = new Query($str);
		if ($check->RowCount() == 0) {
			$arr_found[$i]['account_number'] = $qry_orders->FieldByName('account_number');
			$arr_found[$i]['account_name'] = $qry_orders->FieldByName('account_name');
			$arr_found[$i]['bill_id'] = $qry_orders->FieldByName('bill_id');
			$arr_found[$i]['bill_number'] = $qry_orders->FieldByName('bill_number');
			$arr_found[$i]['total_amount'] = $qry_orders->FieldByName('total_amount');
			$arr_found[$i]['module'] = 'orders';
			$arr_found[$i]['date_created'] = $qry_orders->FieldByName('date_created');
			
			$grand_total += $qry_orders->FieldByName('total_amount');
			
			$int_discrepancies++;
		}
		$qry_orders->Next();
	}
*/
	if (IsSet($_POST['action'])) {

		if ($_POST['action'] == 'createTransfers') {

			$qry_account = new Query("
				SELECT bill_credit_account, bill_description, bill_order_description
				FROM stock_storeroom
				WHERE (storeroom_id = ".$_SESSION['int_current_storeroom'].")
			");
			$credit_acount = $qry_account->FieldByName('bill_credit_account');
			
			$arr_bill_ids = $_POST['billIDs'];

			foreach ($arr_bill_ids as $bill_id) {

				$bill_id = explode('-',$bill_id);

				if (is_numeric($bill_id[1])) {
					$bill_description = str_replace("%s", $qry_bills->FieldByName('bill_number'), $qry_account->FieldByName('bill_order_description'));
					$bill_description = str_replace("%d", substr($qry_bills->FieldByName('date_created'),0,10), $bill_description);
					
					$sql_bill = "SELECT bill_id, account_number, total_amount FROM ".Monthalize('bill')." WHERE (bill_id = $bill_id[1])";
					$qry_bill = new Query($sql_bill);

					$cc_id1 = getAccountCCID($qry_bill->FieldByName('account_number'));
					$cc_id2 = getAccountCCID($credit_acount);

					$str_insert = "
						INSERT INTO ".Monthalize('account_transfers')." 
						(
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
							date_completed
						)
						VALUES (
							$cc_id1,
							$cc_id2,
							'".$qry_bill->FieldByName('account_number')."',
							'$credit_acount',
							'".$qry_bill->FieldByName('total_amount')."',
							\"".addslashes($bill_description)."\",
							7,
							'".$qry_bill->FieldByName('bill_id')."',
							'".date('Y-m-d H:i:s',time())."',
							".$_SESSION['int_user_id'].",
							".ACCOUNT_TRANSFER_PENDING.",
							'".Date("Y-m-d h:i:s",time())."'
						)
					";

					$qry = new Query($str_insert);
					//echo $str_insert."<br>";
				}
			}
			
			$int_discrepancies = 0;
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
    </script>
</head>

<body leftmargin='40px' rightmargin='20px' topmargin='40px' bottommargin='20px'>

<?
    boundingBoxStart("800", "../../images/blank.gif");
?>
	<br>
	<div class='normaltext'><b><? echo $int_discrepancies; ?></b> possible discrepancies found</div>
	<br>
	<form name='verify_transactions' method='POST'>
		<input type='button' name='action' value='Back' class='settings_button' onclick='javascript:goBack()'>
		<input type='button' name='action' id="btn-create-transfers" value='Create Transfers' class='settings_button'>
	</form>
	<br>
	<table border='0' cellspacing='0' cellpadding='5'>
		<tr valign='bottom'>
			<td><input type="checkbox" id="btn-check-toggle"></td>
			<td width='50px' align='right' class='normaltext'><b>Account<br>Number</b></td>
			<td width='200px' class='normaltext'><b>Account<br>Name</b></td>
			<td width='150px' align='right' class='normaltext'><b>Bill<br>Number</b></td>
			<td width='150px' class='normaltext'><b>Date</b></td>
			<td width='100px' class='normaltext' align="right"><b>Amount</b></td>
			<td width='100px' class='normaltext'><b>Donation</b></td>
		</tr>
<?
	//for ($i=0;$i<count($arr_found);$i++) {
	for ($i=0;$i<$qry_bills->RowCount();$i++) {
		echo "<tr>";
		echo "<td><input type='checkbox' id='transfer-".$qry_bills->FieldByName('bill_id')."'></td>";
		echo "<td class='normaltext' align='right'>".$qry_bills->FieldByName('account_number')."</td>";
		echo "<td class='normaltext'>".$qry_bills->FieldByName('account_name')."</td>";
		echo "<td class='normaltext' align='right'>".$qry_bills->FieldByName('bill_number')."</td>";
		echo "<td class='normaltext'>".set_formatted_date($qry_bills->FieldByName('date_created'),'-')."</td>";
		echo "<td class='normaltext' align='right'>".number_format($qry_bills->FieldByName('total_amount'),2,'.',',')."</td>";
		echo "<td class='normaltext'>".($qry_bills->FieldByName('is_billable')=='N'?'Yes':'No')."</td>";
		echo "</tr>";

		$qry_bills->Next();
	}
?>
	</table>
	<br>
<?
    boundingBoxEnd("800", "../../images/blank.gif");
?>


</body>
</html>
<script src="../../include/js/jquery-3.2.1.min.js"></script>
<script>
$(document).ready(function () {

	$("#btn-create-transfers").on('click', function() {
		let IDs = new Array();
		IDs = $("table input[type=checkbox]:checked").map(function () {
		    return $(this).attr("id");
		}).get();

		$.post( "fs_verify_transactions.php", { billIDs: IDs, action: 'createTransfers' })
		 	.done(function( data ) {
		 		alert('FS transactions created');
		 		document.location = '../index_verification_tools.php';
		 	});
	})


	$("#btn-check-toggle").on('click', function() {
		let IDs = new Array();
		IDs = $("table input[type=checkbox]");
        IDs.attr("checked", !IDs.attr("checked"));
	})
});
</script>