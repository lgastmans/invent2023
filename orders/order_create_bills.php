<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");

	$str_retval = '';

	if (IsSet($_GET['action'])) {
		if ($_GET['action'] == 'create') {
			
			require_once("order_functions.inc.php");
			
			$arr_date = explode('-', $_GET['create_for']);
			$str_date = $arr_date[2]."-".$arr_date[1]."-".$arr_date[0];
			
			$str_retval = create_order_bills(strtotime($str_date));
			
			$arr_retval = explode('|', $str_retval);
		}
	}

?>

<html>
<head>
	<link rel="stylesheet" type="text/css" href="../include/<?echo $str_css_filename;?>" />
	<script language="JavaScript" src="../include/calendar1.js"></script>
	<script language='javascript'>

		function createOrderBills() {
			var oTextFrom = document.order_create_bills.cancel_from;

			document.location = 'order_create_bills.php?action=create'+
				'&create_for='+oTextFrom.value;
		}

	</script>
</head>


<body marginheight="10px" marginwidth="10px">

<form name='order_create_bills'>

	<table width='100%' height='100%' border='0' cellpadding='5' cellspacing='0'>
		<tr>
			<td width='15px'></td>
			<td class='<?echo $str_class_header?>'>
				Create bills based on orders <br>for the given date below:<br>
				<span id='message_text'></span>
			</td>
		</tr>
		<tr>
			<td align='right'></td>
			<td>
				<input type="Text" name="cancel_from" value="<?echo date('d-m-Y');?>">
				<a href="javascript:cal1.popup();"><img src="../images/calendar/cal.gif" width="16" height="16" border="0" alt="Click Here to Pick up the date"></a>
			</td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type='button' name='action' value='Create' onclick='createOrderBills()'>
				<input type='button' name='action' value='Close' onclick='window.close()'>
			</td>
		</tr>
	</table>

</form>

<script language="javascript">
	var oTextFrom = document.order_create_bills.cancel_from;

	if (oTextFrom) {
		var cal1 = new calendar1(oTextFrom);
		cal1.year_scroll = true;
		cal1.time_comp = false;
	}
</script>

<? if ($str_retval <> '') { ?>
	<script language='javascript'>
		var oSpan = document.getElementById('message_text');
		oSpan.innerHTML = '<? echo $arr_retval[1]." bills were created for ".$_GET['create_for']; ?>';
		setTimeout("oSpan.innerHTML = ''", 5000);
	</script>
<? } ?>

</body>
</html>