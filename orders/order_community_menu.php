<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");

	//====================
	// get the list of communities
	//====================
	$qry_communities = new Query("
		SELECT *
		FROM communities
		WHERE is_individual = 'N'
		ORDER BY community_name
	");
?>

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
			var oSelectCommunity = document.order_community_menu.select_community;
			var oSelectQty = document.order_community_menu.select_quantity;
			var oTextDate = document.order_community_menu.order_totals_date;
			var oCheckBoxDelivered = document.order_community_menu.include_delivered;
			var str_include_delivered;
			
			if (oCheckBoxDelivered.checked)
			    str_include_delivered = 'Y';
			else
			    str_include_delivered = 'N';

			parent.frames['order_community_content'].document.location = 'order_community_content.php?sheet_date='+oTextDate.value+
				'&include_delivered='+str_include_delivered+
				'&display_quantity='+oSelectQty.value+
				'&community='+oSelectCommunity.value;
		}
		
		function printStatement() {
			var oSelectCommunity = document.order_community_menu.select_community;
			var oSelectQty = document.order_community_menu.select_quantity;
			var oTextDate = document.order_community_menu.order_totals_date;
			var oCheckBoxDelivered = document.order_community_menu.include_delivered;
			var oCheckBoxCondensed = document.order_community_menu.print_condensed;
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
			
			var str_dest = 'order_community_print.php?sheet_date='+oTextDate.value+
				'&include_delivered='+str_include_delivered+
				'&display_quantity='+oSelectQty.value+
				'&print_condensed='+str_print_condensed+
				'&community='+oSelectCommunity.value;
			
			window.open(str_dest, "print_window");
		}

	</script>
</head>

<body id='body_bgcolor' leftmargin=5 topmargin=5>

<form name='order_community_menu'>

<font class='normaltext'>

	&nbsp;
	Community
	<select name="select_community" class='select_400'>
		<option value='ALL'>&lt; All &gt;
		<?
			for ($i=0; $i<$qry_communities->RowCount(); $i++) {
				echo "<option value='".$qry_communities->FieldByName('community_id')."'>".$qry_communities->FieldByName('community_name')."\n";
				$qry_communities->Next();
			}
		?>
	</select>
	&nbsp;
	Display quantity
	<select name='select_quantity' class='select_100'>
		<option value='delivered'>Delivered</option>
		<option value='ordered'>Ordered</option>
	</select>
	&nbsp;
	<input type="text" name="order_totals_date" value="<?echo date('d-m-Y');?>" class='input_100'>
	<a href="javascript:cal1.popup();"><img src="../images/calendar/cal.gif" width="16" height="16" border="0" alt="Click here to select a date"></a>
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
	var oTextDate = document.order_community_menu.order_totals_date;

        var cal1 = new calendar1(oTextDate);
        cal1.year_scroll = true;
        cal1.time_comp = false;

</script>

</body>
</html>