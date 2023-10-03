<html>
<head>
	<script language="javascript" src="../include/calendar1.js"></script>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	<script language='javascript'>
		function mouseGoesOver(element, aSource)
		{
			element.src = aSource;
		}
		
		function mouseGoesOut(element, aSource)
		{
			element.src = aSource;
		}

		function loadSheet() {
			var oSelectQty = document.order_sheet_menu.select_quantity;
			var oTextDate = document.order_sheet_menu.order_totals_date;
			var oTextDateTo = document.order_sheet_menu.order_totals_date_to;
			var oCheckBoxDelivered = document.order_sheet_menu.include_delivered;
			var str_include_delivered;
			
			if (oCheckBoxDelivered.checked)
			    str_include_delivered = 'Y';
			else
			    str_include_delivered = 'N';

			parent.frames['order_sheet_content'].document.location = 'order_sheet_update_array.php?'+
				'action=load'+
				'&sheet_date='+oTextDate.value+
				'&sheet_date_to='+oTextDateTo.value+
				'&include_delivered='+str_include_delivered+
				'&display_quantity='+oSelectQty.value;
		}
		
		function printStatement() {
			var oSelectQty = document.order_sheet_menu.select_quantity;
			var oTextDate = document.order_sheet_menu.order_totals_date;
			var oTextDateTo = document.order_sheet_menu.order_totals_date_to;
			var oCheckBoxDelivered = document.order_sheet_menu.include_delivered;
			var oCheckBoxCondensed = document.order_sheet_menu.print_condensed;
			var str_include_delivered;
			var str_print_condensed;
			
			if (oCheckBoxDelivered.checked)
				str_include_delivered = 'Y';
			else
				str_include_delivered = 'N';

			if (oCheckBoxCondensed.checked)
				str_print_condensed = 'Y';
			else
				str_print_condensed = 'N';

			var oArrSelects = parent.frames['order_sheet_content'].document.getElementsByName('select_print');
			var str_selected = '&selected_products=';
			for (i=0; i<oArrSelects.length; i++) {
				if (oArrSelects[i].checked)
					str_checked = 'Y';
				else
					str_checked = 'N';
				str_selected += oArrSelects[i].getAttribute('id') +'|' + str_checked +',';
			}
			str_selected = str_selected.substring(0, str_selected.length - 1)

			var str_dest = 'order_sheet_print.php?'+
				'sheet_date='+oTextDate.value+
				'&sheet_date_to='+oTextDateTo.value+
				'&include_delivered='+str_include_delivered+
				'&display_quantity='+oSelectQty.value+
				'&print_condensed='+str_print_condensed+
				str_selected;

			window.open(str_dest, "print_window");
		}

	</script>
</head>

<body id='body_bgcolor' leftmargin=5 topmargin=5>

<form name='order_sheet_menu'>

<font class='normaltext'>

	&nbsp;
	Display quantity
	<select name='select_quantity' class='select_100'>
		<option value='delivered'>Delivered</option>
		<option value='ordered'>Ordered</option>
	</select>
	&nbsp;
	from
	<input type="text" name="order_totals_date" value="<?echo date('d-m-Y');?>" class='input_100'>
	<a href="javascript:cal1.popup();"><img src="../images/calendar/cal.gif" width="16" height="16" border="0" alt="Click here to select a date"></a>
	&nbsp;
	to
	<input type="text" name="order_totals_date_to" value="<?echo date('d-m-Y');?>" class='input_100'>
	<a href="javascript:cal2.popup();"><img src="../images/calendar/cal.gif" width="16" height="16" border="0" alt="Click here to select a date"></a>
	&nbsp;
	<a href="javascript:printStatement()"><img src="../images/printer.png" border="0" onmouseover="javascript:mouseGoesOver(this, '../images/printer_active.png')" onmouseout="javascript:mouseGoesOut(this, '../images/printer.png')"></a>
	&nbsp;&nbsp;
	<input type='button' name='action' value='Load' class='settings_button' onclick='loadSheet()'>
	<br>
	<input type='checkbox' name='include_delivered'>Include delivered orders
	&nbsp;
	<input type='checkbox' name='print_condensed' checked>Print condensed
</font>

</form>

<script language="JavaScript">
	var oTextDate = document.order_sheet_menu.order_totals_date;
	var oTextDateTo = document.order_sheet_menu.order_totals_date_to;

        var cal1 = new calendar1(oTextDate);
        cal1.year_scroll = true;
        cal1.time_comp = false;

        var cal2 = new calendar1(oTextDateTo);
        cal2.year_scroll = true;
        cal2.time_comp = false;
</script>

</body>
</html>