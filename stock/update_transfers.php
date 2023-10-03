<?
	require_once('../include/const.inc.php');
	require_once('../include/db.inc.php');
	require_once('../include/session.inc.php');

	if (IsSet($_GET['select_month']))
		$int_month_selected = $_GET['select_month'];
	else
		$int_month_selected = date('n');

	$int_year = date('Y');

	if (IsSet($_GET['action'])) {
		if ($_GET['action'] == 'submit') {
			$str_transfers = "
				SELECT *
				FROM stock_transfer_".$int_year."_".$int_month_selected."
				WHERE transfer_type = 5";
			$qry_transfers = new Query($str_transfers);
			
			$qry_update = new Query("SELECT * FROM stock_transfer_".$int_year."_".$int_month_selected." LIMIT 1");
			
			echo "updating...";
			for ($i=0;$i<$qry_transfers->RowCount();$i++) {
				
				$str_string = $qry_transfers->FieldByName('transfer_description');
				
				$int_pos = strpos($str_string, 'adjusted:');
				
				if ($int_pos !== false) {
					$str_quantity = substr($str_string, $int_pos+10, strlen($str_string));
					$flt_quantity = floatval($str_quantity);
					
					$str_update = "
						UPDATE stock_transfer_".$int_year."_".$int_month_selected."
						SET transfer_quantity = transfer_quantity + ".number_format($flt_quantity, 3, '.', '')."
						WHERE transfer_id = ".$qry_transfers->FieldByName('transfer_id');
					
					$qry_update->Query($str_update);
				}
				
				$qry_transfers->Next();
			}
			echo "completed updated.";
		}
	}
?>

<html>
<body>

<form name='update_transfers' method='get'>

    Month:
    <select name='select_month'>
        <option value=1 <? if ($int_month_selected == 1) echo "selected"?>>January
        <option value=2 <? if ($int_month_selected == 2) echo "selected"?>>February
        <option value=3 <? if ($int_month_selected == 3) echo "selected"?>>March
        <option value=4 <? if ($int_month_selected == 4) echo "selected"?>>April
        <option value=5 <? if ($int_month_selected == 5) echo "selected"?>>May
        <option value=6 <? if ($int_month_selected == 6) echo "selected"?>>June
        <option value=7 <? if ($int_month_selected == 7) echo "selected"?>>July
        <option value=8 <? if ($int_month_selected == 8) echo "selected"?>>August
        <option value=9 <? if ($int_month_selected == 9) echo "selected"?>>September
        <option value=10 <? if ($int_month_selected == 10) echo "selected"?>>October
        <option value=11 <? if ($int_month_selected == 11) echo "selected"?>>November
        <option value=12 <? if ($int_month_selected == 12) echo "selected"?>>December
    </select>
    <input type='submit' name='action' value='submit'>
    <br>
    <br>
</form>

</body>
</html>