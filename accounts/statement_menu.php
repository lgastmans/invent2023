<?
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db.inc.php");
	require_once("module.inc.php");
	require_once("db_params.php");
	require_once("db_funcs.inc.php");

	
 	$_SESSION['int_accounts_selected'] = 4;
			
	/*
		get the list of years and months
	*/
	$arr_months = getFSMonths();
?>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	<script language="javascript">
		function setValue() {
			var oType = document.getElementById('select_type');
			var oValue = document.getElementById('select_value');
			var oCode = document.getElementById('product_code');
			
			strURL = 'statistics_details.php?stat_type='+oType.value;
			parent.frames['details'].document.location = strURL;
		}

		function loadData() {
			var oAccount = document.getElementById('fs_account');
			var oFrom = document.getElementById('select_from');
			var oTo = document.getElementById('select_to');
			
			strURL = 'statement_content.php?account='+oAccount.value+
				'&filter_from='+oFrom.value+
				'&filter_to='+oTo.value;
			
			parent.frames['content'].document.location = strURL;
		}
		
		function mouseGoesOver(element, aSource) {
			element.src = aSource;
		}
		
		function mouseGoesOut(element, aSource) {
			element.src = aSource;
		}

		function printStatement() {
 
	        var oTextAccountNumber = document.statement_menu.fs_account;
	        var oFilterFrom = document.statement_menu.select_from;
	        var oFilterTo = document.statement_menu.select_to;

		    str_url = "statement_print.php?"+
				"account="+oTextAccountNumber.value+
				"&filter_from="+oFilterFrom.value+
				"&fitler_to="+oFilterTo.value;

		    window.open(str_url, "print_window");
		}

	</script>
</head>
<body id='body_bgcolor' leftmargin=5 topmargin=5 marginwidth=5 marginheight=5>

<form name="statement_menu" onsubmit="return false">

	<font class='normaltext'>
		Account:
		<input type="text" name="fs_acount" id="fs_account">
		
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
		
	</font>
	&nbsp;
	
	<a href="javascript:printStatement()"><img src="../images/printer.png" border="0" onmouseover="javascript:mouseGoesOver(this, '../images/printer_active.png')" onmouseout="javascript:mouseGoesOut(this, '../images/printer.png')"></a>
	&nbsp;
	
	<input type='button' name='action' value='load' class='settings_button' onclick='javascript:loadData()'>
<!-- 	<input type='button' name='action' value='chart' class='settings_button' onclick='javascript:loadChart()'> -->

	<br />
	
</form>


</body>
</html>