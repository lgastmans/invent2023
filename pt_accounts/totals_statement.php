<?
	require_once("../include/const.inc.php");
	require_once("session.inc.php");
	require_once("db.inc.php");
	
	$_SESSION["int_pt_accounts_selected"]=4;
?>
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../include/styles.css" />
    </head>
<body id='body_bgcolor' leftmargin=15 topmargin=5 marginwidth=5 marginheight=5>
<?
	if (isSet($_GET["int_start"])){
		$int_start = $_GET["int_start"];
	}
	else
		$int_start = date('Y');

	if (IsSet($_GET["int_end"])) {
		$int_end = $_GET["int_end"];
	}
	else
		$int_end = date('Y');
	
	$int_cur_year = date('Y');
	$int_cur_month = date('n');

	$qry = new Query("SELECT * FROM account_pt");

	$arr_totals = array();
	$counter = 0;
	for ($i=$int_start;$i<=$int_end;$i++) {

		if ($i == YEAR_INSTALLED) {
			for ($j=MONTH_INSTALLED; $j<=12; $j++) {
				$qry->Query("
					SELECT SUM(opening_balance) AS ob, SUM(closing_balance) AS cb
					FROM account_pt_balances_".$i."_".$j
				);
				if ($qry->RowCount() > 0) {
					$arr_totals[$counter][0] = $i;
					$arr_totals[$counter][1] = $j;
					$arr_totals[$counter][2] = ($qry->FieldByName('ob'));
					$arr_totals[$counter][3] = ($qry->FieldByName('cb'));
					$counter++;
				}
			}
		}
		else if ($i == $int_cur_year) {
			for ($j=1; $j<=$int_cur_month; $j++) {
				$qry->Query("
					SELECT SUM(opening_balance) AS ob, SUM(closing_balance) AS cb
					FROM account_pt_balances_".$i."_".$j
				);
				if ($qry->RowCount() > 0) {
					$arr_totals[$counter][0] = $i;
					$arr_totals[$counter][1] = $j;
					$arr_totals[$counter][2] = ($qry->FieldByName('ob'));
					$arr_totals[$counter][3] = ($qry->FieldByName('cb'));
					$counter++;
				}
			}
		}
		else {
			for ($j=1;$j<=12;$j++) {
				$qry->Query("
					SELECT SUM(opening_balance) AS ob, SUM(closing_balance) AS cb
					FROM account_pt_balances_".$i."_".$j
				);
				if ($qry->RowCount() > 0) {
					$arr_totals[$counter][0] = $i;
					$arr_totals[$counter][1] = $j;
					$arr_totals[$counter][2] = ($qry->FieldByName('ob'));
					$arr_totals[$counter][3] = ($qry->FieldByName('cb'));
					$counter++;
				}
			}
		}
	}
?>

<form name="DailySalesRegister" method="GET">
	<font style="font-family:Verdana,sans-serif;">
	<table border=1 cellpadding=7 cellspacing=0>
		<?
			$int_cur_year = 0;
			$flt_ob_total = 0;
			$flt_cb_total = 0;
			
			for ($i=0;$i<count($arr_totals);$i++) {
				if ($i % 2 == 1) 
					$bgcolor = "#dfdfdf";
				else 
					$bgcolor = "#ffffff"; 

				echo "<tr bgcolor=".$bgcolor.">";
				if ($int_cur_year < $arr_totals[$i][0]) {
					echo "<td class='normaltext' width='80px'>".$arr_totals[$i][0]."</td>";
					$int_cur_year = $arr_totals[$i][0];
				}
				else
					echo "<td class='normaltext' width='80px'>&nbsp;</td>";
				echo "<td class='normaltext' width='80px' align=right>".$arr_totals[$i][1]."</td>";
				echo "<td class='normaltext' width='250px' align=right>".number_format($arr_totals[$i][2],2,'.',',')."</td>";
				echo "<td class='normaltext' width='250px' align=right>".number_format($arr_totals[$i][3],2,'.',',')."</td>";
				echo "</tr>";
				
				$flt_ob_total += number_format($arr_totals[$i][2],2,'.','');
				$flt_cb_total += number_format($arr_totals[$i][3],2,'.','');
			}
    ?>
	</table>
	</font>
	<br>

</form>
<script language="javascript">
	parent.frames['footer'].document.location = "totals_footer.php?ob_total=<?echo number_format($flt_ob_total,2,'.','');?>&cb_total=<?echo number_format($flt_cb_total,2,'.','');?>";
</script>
</body>
</html>