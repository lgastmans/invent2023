<?
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db_params.php");
	require_once("db_funcs.inc.php");

	$module_pt_accounts = getModule('PT Accounts');
	
	/*
		get the list of years and months
	*/
	$arr_months = getFSMonths();
?>


<html>
<head>
	<script language="javascript">
		function loadContent() {
			var oFrom = document.getElementById('select_from');
			var oTo = document.getElementById('select_to');
			
			parent.frames["content"].document.location = "totals_frameset.php?"+
				"filter_from="+oFrom.value+
				"&filter_to="+oTo.value;
		}
	</script>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
</head>
<body leftmargin=5 topmargin=5 marginwidth=5 marginheight=5 bgcolor="#DADADA">
<form name="TotalsStatementMenu">

	<font style="font-family:Verdana,sans-serif;font-weight:bold;font-size:10pt;">
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
	</font>
	<input type='button' name='load' value='Load' onclick='loadContent()' class='settings_button'>
</form>

<script language="javascript">
	var oSelectStart = document.TotalsStatementMenu.start_year;
	var oSelectEnd = document.TotalsStatementMenu.end_year;

	parent.frames["content"].document.location = "totals_frameset.php?int_start="+oSelectStart.value+"&int_end="+oSelectEnd.value;
</script>

</body>
</html>