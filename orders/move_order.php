<?
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db.inc.php");
	
	$int_id = 0;
	if (IsSet($_GET['bill_id']))
		$int_id = $_GET['bill_id'];
	
	$str_error_message = '';
	
	/*
		orders can only be moved within the current
		financial year
		
		THE STOCK DOES NOT GET UDPATED - TO DO
	*/
	$int_month = date('n', time());
	$int_year = date('Y', time());
	$arr_months = Array();
	if ($int_month >= 4) {
		for ($i=4;$i<=$int_month;$i++) {
			$arr_months[$i."_".$int_year] = date("M Y", mktime(0, 0, 0, $i, 1, $int_year));
		}
	}
	else {
		for ($i=4;$i<=12;$i++) {
			$arr_months[$i."_".($int_year-1)] = date("M Y", mktime(0, 0, 0, $i, 1, ($int_year-1)));
		}
		for ($i=1;$i<=$int_month;$i++) {
			$arr_months[$i."_".$int_year] = date("M Y", mktime(0, 0, 0, $i, 1, $int_year));
		}
	}

	if (IsSet($_POST['select_date'])) {
		$str_move_to = $_POST['select_date'];
		$int_id = $_POST['bill_id'];
		
		/*
			only invoices that are delivered can be moved
		*/
		$str_query = "
			SELECT *
			FROM ".Monthalize('bill')."
			WHERE bill_id = $int_id
		";
		$qry = new Query($str_query);
		
		if ($qry->RowCount() > 0) {
		
			$int_order_id = $qry->FieldByName('module_record_id');
			
			if ($qry->FieldByName('bill_status') == BILL_STATUS_DELIVERED) {
			
				$arr_move_to = explode("_", $str_move_to);
				$int_month = $arr_move_to[0];
				$int_year = $arr_move_to[1];
				
				$bool_success = true;
				$qry->Query("START TRANSACTION");
				
				/*
					copy the bill and remove from current month
				*/
				$str_query = "
					INSERT INTO bill_".$int_year."_".$int_month."
						SELECT *
						FROM ".Monthalize('bill')."
						WHERE bill_id = $int_id
				";
				$qry->Query($str_query);
				if ($qry->b_error === true) {
					$bool_success = false;
					$str_error_message = 'Error moving bill';
				}
				
				$str_query = "
					DELETE FROM ".Monthalize('bill')."
					WHERE bill_id = $int_id
				";
				$qry->Query($str_query);
				if ($qry->b_error === true) {
					$bool_success = false;
					$str_error_message = 'Error deleting bill';
				}
				
				/*
					copy the bill items and remove from current month
				*/
				$str_query = "
					INSERT INTO bill_items_".$int_year."_".$int_month."
						SELECT *
						FROM ".Monthalize('bill_items')."
						WHERE bill_id = $int_id
				";
				$qry->Query($str_query);
				if ($qry->b_error === true) {
					$bool_success = false;
					$str_error_message = 'Error moving bill items';
				}
				
				$str_query = "
					DELETE FROM ".Monthalize('bill_items')."
					WHERE bill_id = $int_id
				";
				$qry->Query($str_query);
				if ($qry->b_error === true) {
					$bool_success = false;
					$str_error_message = 'Error deleting bill items';
				}
				
				/*
					copy the order and remove from current month
				*/
				$str_query = "
					INSERT INTO orders_".$int_year."_".$int_month."
						SELECT *
						FROM ".Monthalize('orders')."
						WHERE order_id = $int_order_id
				";
				$qry->Query($str_query);
				if ($qry->b_error === true) {
					$bool_success = false;
					$str_error_message = 'Error moving orders';
				}
				
				$str_query = "
					DELETE FROM ".Monthalize('orders')."
					WHERE order_id = $int_order_id
				";
				$qry->Query($str_query);
				if ($qry->b_error === true) {
					$bool_success = false;
					$str_error_message = 'Error deleting order';
				}
				
				/*
					copy the order items
				*/
				$str_query = "
					INSERT INTO order_items_".$int_year."_".$int_month."
						SELECT *
						FROM ".Monthalize('order_items')."
						WHERE order_id = $int_order_id
				";
				$qry->Query($str_query);
				if ($qry->b_error === true) {
					$bool_success = false;
					$str_error_message = 'Error moving order items ';
				}
				
				$str_query = "
					DELETE FROM ".Monthalize('order_items')."
					WHERE order_id = $int_order_id
				";
				$qry->Query($str_query);
				if ($qry->b_error === true) {
					$bool_success = false;
					$str_error_message = 'Error deleting order items';
				}
				
				if ($bool_success) {
					$qry->Query("COMMIT");
					
					echo "<script language='javascript'>";
					echo "window.close()";
					echo "</script>";
				}
				else
					$qry->Query("ROLLBACK");
			}
			else
				$str_error_message = "Only delivered orders can be moved";
		}
		else
			$str_error_message = "Selected order not found";
	}
?>
<html>
<head><TITLE>Move Invoice</TITLE>
	<link href="../include/styles.css" rel="stylesheet" type="text/css">
	<script language="javascript">
		function moveOrder() {
			document.move_order.submit();
		}
	</script>
</head>

<body id="body_bgcolor">
<form name="move_order" method="POST">
	<input type="hidden" name="bill_id" value="<?echo $int_id;?>">
<?
	if ($str_error_message <> "") {
		die($str_error_message);
	}
?>
	<table width="100%" border="0" cellpadding="2" cellspacing="0">
		<tr>
			<td align="right" class="normaltext">Move to</td>
			<td>
				<select name="select_date" class="select_100">
					<?
						foreach($arr_months as $key => $value) {
							echo "<option value=\"$key\">".$value."\n";
						}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<TD>&nbsp;</TD>
		</tr>
		<tr>
			<td colspan="2" align="center">
				<input type="button" name="action" value="Move" onclick="javascript:moveOrder()">
			</td>
		</tr>
	</table>
	
</form>
</body>
</html>