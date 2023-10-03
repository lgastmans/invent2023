<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");

	$int_id = 0;
	if (IsSet($_GET['id']))
		$int_id = $_GET['id'];

	$str_query = "
		SELECT *
		FROM ".Monthalize('bill')."
		WHERE bill_id = $int_id
	";
	$qry = new Query($str_query);
	if ($qry->b_error == true)
		die('Error retrieving the status');
		
	$int_bill_status = $qry->FieldByName('bill_status');

	//***
	// the status HAS TO be "Dispatched"
	//***
	if ($int_bill_status <> BILL_STATUS_DISPATCHED)
		die('Only dispatched orders can be delivered');
?>

<html>
<head>
	<script language="javascript" src="../include/calendar1.js"></script>
	<link rel="stylesheet" type="text/css" href="../include/<?echo $str_css_filename;?>" />
		<script language='javascript'>
			function deliver() {
				var oTextDate = document.order_mark_delivered_date.order_delivery_date;
				
				if ((oTextDate.value.length == 0) || (oTextDate.value == "")) {
					alert('The date cannot be blank');
				}
				else {
					window.resizeTo(400, 400);
					window.location = 'order_mark_delivered.php?id=<?echo $int_id;?>&delivery_date='+oTextDate.value;
					window.moveTo((screen.availWidth/2 - 400/2), (screen.availHeight/2 - 150/2));
				}
			}
		</script>
</head>

<body>
<form name='order_mark_delivered_date' method='POST'>
	<table width='100%'>
		<tr>
			<td align='right' class='<?echo $str_class_header?>'>Delivery date: </td>
			<td>
				<input type="text" name="order_delivery_date" value="<?echo date('d-m-Y');?>" class='<?echo $str_class_input200?>'>
				<a href="javascript:cal1.popup();"><img src="../images/calendar/cal.gif" width="16" height="16" border="0" alt="Click here to select a date"></a>
			</td>
		</tr>
		<tr>
			<td></td>
			<td>
				<br><input type='button' name='action' value='Deliver' onclick='deliver()'>&nbsp;<input type='button' name='action' value='Close' onclick='window.close()'>
			</td>
		</tr>
	</table>
</form>

<script language="javascript">
	var oTextDate = document.order_mark_delivered_date.order_delivery_date;
	var cal1 = new calendar1(oTextDate);
	cal1.year_scroll = true;
	cal1.time_comp = false;
</script>

</body>
</html>