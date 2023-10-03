<html>
<head>
	<script language="JavaScript" src="../include/calendar1.js"></script>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	<script language='javascript'>

		function loadTotals() {
                	var oTextDate = document.order_totals_menu.order_totals_date;
			var oCheckBoxDelivered = document.order_totals_menu.include_delivered;
			var str_include_delivered;
			
			if (oCheckBoxDelivered.checked)
			    str_include_delivered = 'Y';
			else
			    str_include_delivered = 'N';
			
			parent.frames['order_totals_content'].document.location = 'order_totals_content.php?totals_date='+oTextDate.value+
				'&include_delivered='+str_include_delivered;
		}
		
		function printStatement() {
                	var oTextDate = document.order_totals_menu.order_totals_date;
			var oCheckBoxDelivered = document.order_totals_menu.include_delivered;
			var str_include_delivered;
			
			if (oCheckBoxDelivered.checked)
			    str_include_delivered = 'Y';
			else
			    str_include_delivered = 'N';

			var str_dest = 'order_totals_print.php?totals_date='+oTextDate.value+
				'&include_delivered='+str_include_delivered;
			window.open(str_dest, "print_window");
		}

	</script>
</head>

<body id='body_bgcolor' leftmargin=5 topmargin=5>

<form name='order_totals_menu'>

<font class='normaltext'>

	&nbsp;
	<input type="text" name="order_totals_date" class='input_100' value="<?echo date('d-m-Y');?>">
	<a href="javascript:cal1.popup();"><img src="../images/calendar/cal.gif" width="16" height="16" border="0" alt="Click here to select a date"></a>
	&nbsp;
	<input type='checkbox' name='include_delivered'>Include delivered orders
	&nbsp;
	<a href="javascript:printStatement()"><img src="../images/printer.png" border="0" onmouseover="javascript:mouseGoesOver(this, '../images/printer_active.png')" onmouseout="javascript:mouseGoesOut(this, '../images/printer.png')"></a>
	&nbsp;&nbsp;
	<input type='button' name='action' value='Load' class='settings_button' onclick='loadTotals()'>

</font>

</form>

<script language="JavaScript">
	var oTextDate = document.order_totals_menu.order_totals_date;

        var cal1 = new calendar1(oTextDate);
        cal1.year_scroll = true;
        cal1.time_comp = false;

</script>

</body>
</html>