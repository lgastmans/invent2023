<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");

	$int_id = 0;
	$str_message='';
	
	if (IsSet($_GET['id'])) {
		$int_id = $_GET['id'];
		
		$str_message = "Successfully marked delivered";
		$bool_success = true;
		$qry = new Query("START TRANSACTION");
		
		// update bill details
		$str_query = "
			UPDATE ".Monthalize('bill')."
			SET bill_status = ".BILL_STATUS_DELIVERED.",
				is_pending = 'N',
				resolved_on = '".set_mysql_date($_GET['delivery_date'], "-")."'
			WHERE bill_id = $int_id
		";
		$qry->Query($str_query);
		$int_order_id = 0;
		if ($qry->b_error == true) {
			$bool_success = false;
			$str_message = "Error updating bill information";
		}
		else
			$int_order_id = $qry->FieldByName('module_record_id');

		// update order details
		$str_query = "
			UPDATE ".Monthalize('orders')."
			SET order_status = ".ORDER_STATUS_BILLED."
			WHERE order_id = $int_order_id
		";
		if ($qry->b_error == true) {
			$bool_success = false;
			$str_message = "Error updating order information";
		}

		if ($bool_success)
			$qry->Query("COMMIT");
		else
			$qry->Query("ROLLBACK");
	}
?>

<html>
<head>
	<script language="javascript" src="../include/calendar1.js"></script>
	<link rel="stylesheet" type="text/css" href="../include/<?echo $str_css_filename;?>" />
		<script language='javascript'>
			function closeWindow() {
				if (top.window.opener)
					top.window.opener.document.location=top.window.opener.document.location.href;
				top.window.close();
			}
		</script>
</head>
<body>
<form name='order_mark_delivered' method='POST'>
	<table width='100%'>
		<tr>
			<td class='<?echo $str_class_header?>'><?echo $str_message;?></td>
		</tr>
		<tr>
			<td>
				<br><input type='button' name='action' value='Ok' onclick='closeWindow()'>
			</td>
		</tr>
	</table>
</form>

</body>
</html>