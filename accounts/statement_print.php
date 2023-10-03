<?php
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db_params.php");
	require_once("db.inc.php");
	require_once("db_funcs.inc.php");
	require_once("../include/browser_detection.php");
	require_once("../common/printer.inc.php");	

	$db_server = $arr_invent_config['database']['invent_server'];
	$db = $arr_invent_config['database']['invent_database'];
	$db_login = $arr_invent_config['database']['invent_login'];
	$db_password = $arr_invent_config['database']['invent_password'];
	
	$account = '';
	if (isSet($_GET['account'])) {
		$account = $_GET['account'];
	}
	
	if (isSet($_GET["filter_from"])) {
		$arr_period = explode("_", $_GET["filter_from"]);
		$int_start = intval($arr_period[1]);
		$month_start = intval($arr_period[0]);
	}
	else {
		$int_start = date('Y');
		$month_start = date('n');
	}

	if (isSet($_GET["filter_to"])) {
		$arr_period = explode('_', $_GET['filter_to']);
		$int_end = intval($arr_period[1]);
		$month_end = intval($arr_period[0]);
	}
	else {
		$int_end = date('Y');
		$month_end = date('n');
	}
	
	if ($int_end == $int_start) {
		$int_columns = ($month_end - $month_start) + 1;
	}
	else {
		$int_columns = (12 - $month_start) + (($int_end - $int_start -1) * 12) + $month_end;
	}
	
	$sql = "SELECT * FROM account_cc ac WHERE ac.account_number = '$account'";
	$qry =& $conn->query($sql);
	if (MDB2::isError($sql))
		echo $sql->getDebugInfo();
	
	$ac_name = "";
	if ($qry->numRows()>0) {
		$obj = $qry->fetchRow();
		$ac_name = $obj->account_name;
	}
	

	function getStatus($int) {
		switch ($int) {
			case 0:
				return "PENDING";
				break;
			case 1:
				return "INSUFFICIENT FUNDS";
				break;
			case 2:
				return "ERROR";
				break;
			case 3:
				return "CANCELLED";
				break;
			case 4:
				return "HOLD";
				break;
			case 5:
				return "COMPLETE";
				break;
			case 6:
				return "REVIEW";
				break;
		}
	}	
?>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="../../include/styles.css" />
	<style>
		.normalnumber {
			font-family:Verdana,sans-serif;
			font-size:11px;
			color:black;
			text-align:right;
		}
		
		td {
			border:.5px;
			border-style:groove;
			border-color:grey;
		}
		
		.blank {
			border-bottom-width:0px;
			border-bottom-style:none;
			border-top-width:0px;
			border-top-style:none;
			border-right-width:0px;
			border-right-style:none;
			background-color:#E1E1E1;
		}
	</style>
</head>
<body id='body_bgcolor' leftmargin=5 topmargin=5 marginwidth=5 marginheight=5>

<?php
	$str_data = "Account details for $account - $ac_name \n";
	
	$int_start_month = $month_start;
	$int_start_year = $int_start;
	$total = 0;
	
	while (true) {

		$str_data .= getMonthName($int_start_month)." ".$int_start_year."\n";

		$str_query = "SELECT date_created, tr.amount, tr.description, tr.transfer_status, u.username, ac.account_name 
			FROM  account_transfers_".$int_start_year."_".$int_start_month." tr 
			INNER JOIN user u ON u.user_id = tr.user_id 
			INNER JOIN account_cc ac ON (ac.cc_id = tr.cc_id_from) AND (tr.transfer_status IN (0,1,2,3,4))
			WHERE ac.account_number = '$account'";
					
		$qry =& $conn->query($str_query);
		if (MDB2::isError($qry))
			echo $qry->getDebugInfo();
		
		if ($qry->numRows() > 0) {

			$date = new DateTime();

			while ($obj = $qry->fetchRow()) {

				$date = date_create($obj->date_created);

				$str_data .= 
					PadWithCharacter(date_format($date, 'd M, Y'), ' ', 15)." ".
					StuffWithCharacter(number_format($obj->amount,2,'.',','), ' ', 10)." ".
					PadWithCharacter($obj->description, ' ', 30)." ".
					PadWithCharacter(getStatus($obj->transfer_status), ' ', 20)."\n";

				$total += $obj->amount;
			}
		}
		
		if (($int_start_month == 12) && ($int_end > $int_start_year)) {
			$int_start_year++;
			$int_start_month = 1;
		}
		else 
			$int_start_month++;
			
		if ($int_end == $int_start_year)
			if ($int_start_month > $month_end)
				break;
	}

	//$str_data .= "::".number_format($total,2)."\n";

	$str_eject_lines = "";
	for ($i=0;$i<$int_eject_lines;$i++) {
	  $str_eject_lines .= "\n"; 
	}

	$str_statement = 
		$str_data." ".
		$str_eject_lines."%n";

	$str_statement = replaceSpecialCharacters($str_statement);

?>

<PRE>
<?
echo $str_statement;
?>
</PRE>


<form name="printerForm" method="POST" action="http://localhost/print.php">

<table width="100%" bgcolor="#E0E0E0">
  <tr>
    <td height=45 class="headerText" bgcolor="#808080">
      &nbsp;<font class='title'>Printing</font>
    </td>
  </tr>
  <tr>
    <td>
      <br>
      <input type="hidden" name="data" value="<? echo ($str_statement); ?>"><br>
    </td>
  </tr>
  <tr>
    <td class='normaltext'>
      <textarea name='printerStatus' height=5 rows=5 cols=40 class='editbox'></textarea>
    </td>
  </tr>
  <tr>
    <td align='center'>
      <br><input type='submit' name='doaction' value="Print">
      <input type='button' onclick="window.close();" name='doaction' value="Close">
    </td>
  </tr>
</table>

</form>

<script language="JavaScript">
 printerForm.submit();
</script>

</body>
</html>
