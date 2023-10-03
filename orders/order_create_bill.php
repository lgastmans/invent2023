<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");

	$bool_success = false;
	$str_message = 'An error occurred creating the bill for this order';
	
	if (IsSet($_GET['order_id'])) {
		require_once("order_functions.inc.php");

		$qry = new Query("SELECT * FROM ".Monthalize('bill')." WHERE module_record_id = ".$_GET['order_id']);
		if ($qry->RowCount() > 0) {
			$str_message = "A bill already exists for this order";
		}
		else {
			$qry->Query("SELECT * FROM ".Monthalize('orders')." WHERE order_id = ".$_GET['order_id']);

			if ($qry->RowCount() > 0) {
				$bool_success = create_an_order_bill($_GET['order_id'], $qry->FieldByName('order_date'));

				if ($bool_success == 'OK') {
					// set the order status flag to "billed"
					$qry->Query("
						UPDATE ".Monthalize('orders')." 
						SET order_status = ".ORDER_STATUS_ACTIVE.",
							is_modified = 'Y'
						WHERE order_id = ".$_GET['order_id']
					);
				}
			}
		}
	}
?>
<html>
<head><TITLE></TITLE>
	<script language='javascript'>
		function closeWindow() {
			if (top.window.opener)
				top.window.opener.document.location=top.window.opener.document.location.href;
			top.window.close();
		}
	</script>
</head>
<body>
	<table width='100%' height='100%' border='0'>
		<tr>
			<td align='center' height='50px'>
			<?
				
				if ($bool_success == 'OK')
					echo "Order bill created successfully";
				else
					echo $str_message;
			?>
			</td>
		</tr>
		<tr>
			<td align='center' valign='bottom'>
				<input type='button' name='action' value='Close' onclick='closeWindow()'>
			</td>
		</tr>
	</table>
</body>
</html>