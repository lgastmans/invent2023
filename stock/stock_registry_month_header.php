<?
	require_once("../include/const.inc.php");
	
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
?>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	</head>
<body id='body_bgcolor' leftmargin=15 topmargin=5 marginwidth=5 marginheight=5>

	<table border=1 cellpadding=5 cellspacing=0 style='table-layout:fixed' width="100%">
		<tr class='normaltext_bold' bgcolor='lightgrey'>
			<td width='150px' align="center">Balances</td>
			<?
				if ($int_columns > 0) {
					$int_start_month = $month_start;
					$int_start_year = $int_start;
					
					while (true) {
						echo "<td width='150px' align='center' colspan='3'>".getMonthName($int_start_month)."<br>".$int_start_year."</td>\n";
					
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
				}
			?>
			<td width="100%">&nbsp;</td>
		</tr>
	</table>

</body>
</html>