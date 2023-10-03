<?
    require_once("../include/const.inc.php");
    require_once("../include/session.inc.php");
	
    $str_id_list = '';
    if (IsSet($_GET['id_list']))
            $str_id_list = $_GET['id_list'];

?>

<html>
<head>
	<script language="javascript" src="../include/calendar1.js"></script>
	<link rel="stylesheet" type="text/css" href="../include/<?echo $str_css_filename;?>" />

	<script language='javascript'>
		function deliver() {
			var oTextDate = document.deliver_order_date.order_delivery_date;

			if ((oTextDate.value.length == 0) || (oTextDate.value == "")) {
				alert('The date cannot be blank');
			}
			else {
				window.resizeTo(400, 400);
				window.location = 'deliver_selected_orders.php?id_list=<?echo $str_id_list;?>&delivery_date='+oTextDate.value;
				window.moveTo((screen.availWidth/2 - 400/2), (screen.availHeight/2 - 150/2));
			}
		}
	</script>
</head>
<body>
<form name='deliver_order_date' method='POST'>
	<table width='100%'>
		<tr>
			<td align='right' class='<?echo $str_class_header?>'>Date dispatched: </td>
			<td>
				<input type="text" name="order_delivery_date" value="<?echo date('d-m-Y');?>" class='<?echo $str_class_input200?>'>
				<a href="javascript:cal1.popup();"><img src="../images/calendar/cal.gif" width="16" height="16" border="0" alt="Click here to select a date"></a>
			</td>
		</tr>
		<tr>
			<td></td>
			<td>
				<br><input type='button' name='action' value='Dispatch' onclick='deliver()'>&nbsp;<input type='button' name='action' value='Close' onclick='window.close()'>
			</td>
		</tr>
	</table>
</form>

<script language="javascript">
	var oTextDate = document.deliver_order_date.order_delivery_date;
	var cal1 = new calendar1(oTextDate);
	cal1.year_scroll = true;
	cal1.time_comp = false;
</script>

</body>
</html>