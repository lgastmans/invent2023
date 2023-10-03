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
		
	/*
	$int_start_year = YEAR_INSTALLED;
	$int_end_year = $int_start_year + 15;
	$int_cur_year = date('Y');
	$int_cur_month = date('m');
	
	for ($i=$int_start_year; $i<$int_end_year; $i++) {
		if ($i==YEAR_INSTALLED) {
			$bool_result = $module_pt_accounts->monthExists(MONTH_INSTALLED, $i);
		}
		else {
			$bool_result = $module_pt_accounts->monthExists(1, $i);
		}

		if ($bool_result)
			$arr_years[] = $i;
		else
			break;
	}
	*/
?>

<script language="javascript">
	function openSearch(aBillType) {
		myWin = window.open("../common/account_search.php?"+
			"bill_type="+aBillType+
			"&formname=AccountsStatementMenu&"+
			"fieldname=filter_account",'searchProduct','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=500,height=600,top=0');
		myWin.focus();
	}
	
	function loadContent() {
		var oTextFilter = document.AccountsStatementMenu.filter_account;
		var oSelectOrder = document.AccountsStatementMenu.order_by;
		
		var oFrom = document.getElementById('select_from');
		var oTo = document.getElementById('select_to');
		
		parent.frames["content"].document.location = "accounts_statement_frameset.php?"+
			"filter_from="+oFrom.value+
			"&filter_to="+oTo.value+
			"&filter_account="+oTextFilter.value+
			"&order_by="+oSelectOrder.value;
	}
</script>

<html>
<head>
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
</head>
<body leftmargin=5 topmargin=5 marginwidth=5 marginheight=5 bgcolor="#DADADA">
<form name="AccountsStatementMenu">

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
	
	&nbsp;Filter on account:
	<input type='text' name='filter_account' value='' class='input_100'>
	<a href="javascript:openSearch(<?echo BILL_PT_ACCOUNT;?>)"><img src="../images/find.png" border="0" title="Search" alt="Search"></a>
	
	&nbsp;Order by:
	<select name='order_by' class='select_100'>
		<option value='account_name'>Name</option>
		<option value='account_number'>Number</option>
	</select>
	</font>
	<input type='button' name='load' value='Load' onclick='loadContent()' class='settings_button'>

</form>

</body>
</html>