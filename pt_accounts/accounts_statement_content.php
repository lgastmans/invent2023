<?
	require_once("../include/db.inc.php");
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	
	if (isSet($_GET["int_start"]))
		$int_start = $_GET["int_start"];
	else
		$int_start = date('Y');

	if (isSet($_GET["month_start"]))
		$month_start = $_GET["month_start"];
	else
		$month_start = date('Y');
	
	if (IsSet($_GET["int_end"]))
		$int_end = $_GET["int_end"];
	else
		$int_end = date('Y');

	if (isSet($_GET["month_end"]))
		$month_end = $_GET["month_end"];
	else
		$month_end = date('Y');
	
	$str_filter_account = '';
	if (IsSet($_GET['filter_account'])) {
		if (!empty($_GET['filter_account']))
			$str_filter_account = " AND (account_number LIKE '%".$_GET['filter_account']."%')";
	}
	
	
	$str_order_by = "ORDER BY account_name";
	if (IsSet($_GET['order_by'])) {
		if (!empty($_GET['order_by']))
			$str_order_by = "ORDER BY ".$_GET['order_by'];
	}
	
	if ($int_end == $int_start) {
		$int_columns = ($month_end - $month_start) + 1;
	}
	else {
		$int_columns = (12 - $month_start) + (($int_end - $int_start) * 12) + $month_end;
	}
	
	$arr_accounts = array();
	$qry = new Query("
		SELECT *
		FROM account_pt
		WHERE enabled = 'Y'
		$str_filter_account
		$str_order_by
	");
	for ($i=0;$i<$qry->RowCount();$i++) {
		$arr_accounts[$i][0] = $qry->FieldByName('account_id');
		$arr_accounts[$i][1] = $qry->FieldByName('account_number');
		$arr_accounts[$i][2] = $qry->FieldByName('account_name');
		$arr_accounts[$i]['cb_total'] = 0;
		
		$qry->Next();
	}

	$int_start_month = $month_start;
	$int_start_year = $int_start;
	$counter = 0;
	while (true) {
		for ($i=0;$i<count($arr_accounts);$i++) {
			$qry->Query("
				SELECT opening_balance, closing_balance
				FROM account_pt_balances_".$int_start_year."_".$int_start_month."
				WHERE account_id = ".$arr_accounts[$i][0]."
			");
			if ($qry->RowCount() > 0) {
				$arr_accounts[$i][$counter+3] = $qry->FieldByName('opening_balance');
				$arr_accounts[$i][$counter+4] = $qry->FieldByName('closing_balance');
				$arr_accounts[$i]['cb_total'] += $qry->FieldByName('closing_balance');
			}
			else {
				$arr_accounts[$i][$counter+3] =	'&nbsp;';
				$arr_accounts[$i][$counter+4] = '&nbsp;';
			}
		}
		
		$counter = $counter + 3;
		
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
?>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	</head>
<body id='body_bgcolor' leftmargin=15 topmargin=5 marginwidth=5 marginheight=5>

	<table border=1 cellpadding=5 cellspacing=0>
		<?
		for ($i=0;$i<count($arr_accounts);$i++) {
			if ($i % 2 == 1)
				$bgcolor = "#dfdfdf";
			else 
				$bgcolor = "#ffffff"; 

			echo "<tr bgcolor=".$bgcolor.">";
			echo "<td class='normaltext' width='80px' align=right>".$arr_accounts[$i][1]."</td>";
			echo "<td class='normaltext' width='150px'>".$arr_accounts[$i][2]."</td>";
			
			$int_start_month = $month_start;
			$int_start_year = $int_start;
			$counter = 0;
			while (true) {
				echo "<td class='normaltext' align='right' width='70px'>".number_format($arr_accounts[$i][$counter+3],2,'.',',')."</td>\n";
				echo "<td class='normaltext' align='right' width='70px'>".number_format($arr_accounts[$i][$counter+4],2,'.',',')."</td>\n";
				$counter = $counter + 3;
				
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
			echo "<td class='normaltext' align='right' width='100px'>".number_format($arr_accounts[$i]['cb_total'],2,'.',',')."</td>\n";
			echo "</tr>";
		}
		?>
	</table>

</body>
</html>