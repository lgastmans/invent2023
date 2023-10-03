<?
	require_once("../../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db.inc.php");
	require_once("module.inc.php");
	require_once("db_params.php");
	require_once("db_funcs.inc.php");
	
	/*
		get the list of years and months
	*/
	$arr_months = getFSMonths();
?>
<html>
<head>
	<script language="javascript" src="../../include/calendar1.js"></script>
	<link rel="stylesheet" type="text/css" href="../../include/styles.css" />
	<script language="javascript">
		function setValue() {
			var oType = document.getElementById('select_type');
			var oValue = document.getElementById('select_value');
			var oCode = document.getElementById('product_code');
			
			strURL = 'statistics_details.php?stat_type='+oType.value;
			parent.frames['details'].document.location = strURL;
		}

		function loadData() {
			var oType = document.getElementById('select_type');
			var oValue = document.getElementById('select_value');
			var oCode = document.getElementById('product_code');
			var oFrom = document.getElementById('select_from');
			var oTo = document.getElementById('select_to');
			var oOrder = document.getElementById('select_order');
			var oSelDate = document.getElementById('sel_date');
			var oTypeValue = parent.frames['details'].document.getElementById('select_value');
			
			for (var i=0; i < document.statistics_menu.period.length; i++) {
			   if (document.statistics_menu.period[i].checked) {
			      var rad_val = document.statistics_menu.period[i].value;
			   }
			}
					
			
			if (oType.value == 'SALESPERSON')
				strURL = 'statistics_salesperson.php?'+
					'filter_from='+oFrom.value+
					'&filter_to='+oTo.value+
					'&salesperson_id='+oTypeValue.value;
			else
				strURL = 'statistics_content.php?stat_type='+oType.value+
					'&type_value='+oTypeValue.value+
					'&filter_from='+oFrom.value+
					'&filter_to='+oTo.value+
					'&order_by='+oOrder.value+
					'&period='+rad_val+
					'&period_date='+oSelDate.value;
			
			parent.frames['content'].document.location = strURL;
		}
		
		function loadChart() {
			var oType = document.getElementById('select_type');
			var oValue = document.getElementById('select_value');
			var oCode = document.getElementById('product_code');
			var oFrom = document.getElementById('select_from');
			var oTo = document.getElementById('select_to');
			var oOrder = document.getElementById('select_order');
			var oRange = document.forms['statistics_menu'].period;
			var oTypeValue = parent.frames['details'].document.getElementById('select_value');
			
			if (oType.value == 'SALESPERSON')
				strURL = 'statistics_salesperson_chart.php?'+
					'filter_from='+oFrom.value+
					'&filter_to='+oTo.value;
			else
				strURL = 'statistics_content.php?stat_type='+oType.value+
					'&type_value='+oTypeValue.value+
					'&filter_from='+oFrom.value+
					'&filter_to='+oTo.value+
					'&order_by='+oOrder.value+
					'&range='+oRange.value;
			//alert(strURL);
			parent.frames['content'].document.location = strURL;
		}
		
		function mouseGoesOver(element, aSource) {
			element.src = aSource;
		}
		
		function mouseGoesOut(element, aSource) {
			element.src = aSource;
		}
	</script>
</head>
<body id='body_bgcolor' leftmargin=5 topmargin=5 marginwidth=5 marginheight=5>

<form name="statistics_menu" onsubmit="return false">

	<font class='normaltext'>
		<select name="select_type" id="select_type" onchange="setValue()">
 			<option value="SALESPERSON">salesperson</option>
			<option value="CATEGORY">category</option>
			<option value="SUPPLIER">supplier</option>
		</select>
		&nbsp;
		
		<input type="radio" name="period" value="month" checked>
		
		From:
		<select id='select_from' name="select_from" class='select_200'>
		<?
			foreach ($arr_months as $key=>$value) {
				echo "<option value=$key>$value";
			}
		?>
		</select>
		
		To:
		<select id='select_to' name="select_to" class='select_200'>
		<?
			foreach ($arr_months as $key=>$value) {
				$arr = explode('_', $key);
				if (intval($arr[0]) == date('n'))
					echo "<option value=$key selected>$value</option>\n";
				else
					echo "<option value=$key>$value</option>\n";
			}
		?>
		</select>
		&nbsp;
		
		Order by:
		<select id="select_order" name="select_order" class="select_100">
			<option value="TOTAL">Total</option>
			<option value="CODE">Code</option>
		</select>
	</font>
	&nbsp;
	
	<a href="javascript:printStatement()"><img src="../../images/printer.png" border="0" onmouseover="javascript:mouseGoesOver(this, '../../images/printer_active.png')" onmouseout="javascript:mouseGoesOut(this, '../../images/printer.png')"></a>
	&nbsp;
	
	<input type='button' name='action' value='load' class='settings_button' onclick='javascript:loadData()'>
<!-- 	<input type='button' name='action' value='chart' class='settings_button' onclick='javascript:loadChart()'> -->

	<br />
	
	<input type="radio" name="period" value="day" style="margin-left:129px;"> 
	<input type="text" name="sel_date" id="sel_date" value="<?echo date('d-m-Y');?>" class='input_100'>
	<a href="javascript:cal1.popup();"><img src="../../images/calendar/cal.gif" width="16" height="16" border="0" alt="Click here to select a date"></a>
	
</form>

<script language="JavaScript">
	var oTextDate = document.statistics_menu.sel_date;

        var cal1 = new calendar1(oTextDate,1);
        cal1.year_scroll = true;
        cal1.time_comp = false;

</script>


</body>
</html>