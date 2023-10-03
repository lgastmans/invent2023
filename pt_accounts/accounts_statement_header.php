<?
	require_once("../include/const.inc.php");
	
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
	
	if ($int_end == $int_start) {
		$int_columns = ($month_end - $month_start) + 1;
	}
	else {
		$int_columns = (12 - $month_start) + (($int_end - $int_start) * 12) + $month_end;
	}
?>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	</head>
<body id='body_bgcolor' leftmargin=15 topmargin=5 marginwidth=5 marginheight=5>

	<table border=1 cellpadding=5 cellspacing=0>
		<tr class='normaltext_bold' bgcolor='lightgrey'>
			<td width='80px' rowspan='2'>Account</td>
			<td width='150px' rowspan='2'>Name</td>
			<?
				$int_start_month = intval($month_start);
				$int_start_year = intval($int_start);
				
				while (true) {
					echo "<td width='100px' align='center' colspan='2'>".getMonthName($int_start_month)." ".$int_start_year."</td>\n";
				
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
			<td width="100px" rowspan="2" align="right">C.B.<br>Total</td>
		</tr>
		<tr class='normaltext_bold' bgcolor='lightgrey'>
		<?
			$int_start_month = $month_start;
			$int_start_year = $int_start;
			
			while (true) {
				echo "<td width='70px' align='right'>O.B.</td><td width='70px' align='right'>C.B.</td>\n";
			
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
		</tr>
	</table>

</body>
</html>