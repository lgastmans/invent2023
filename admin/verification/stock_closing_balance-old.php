<?php
	include("../../include/const.inc.php");
	include("session.inc.php");
	include("include/db.inc.php");

	$sql_batch = "
		SELECT ROUND(SUM(stock_available),3) AS stock_available
		FROM ".Monthalize('stock_storeroom_batch')." ssb
		WHERE (ssb.product_id = sp.product_id)
			AND (ssb.storeroom_id = ".$_SESSION["int_current_storeroom"].")
			AND (ssb.is_active = 'Y')
	";
	
	$sql_balance = "
		SELECT stock_closing_balance
		FROM ".Yearalize('stock_balance')." sb
		WHERE (sb.storeroom_id = ".$_SESSION['int_current_storeroom'].")
			AND (sb.balance_month = ".$_SESSION['int_month_loaded'].")
			AND (sb.balance_year = ".$_SESSION['int_year_loaded'].")
	";


	$sql_product = "
		ssp.stock_adjusted,
		ssp.stock_current,
	";


	$sql = "
		SELECT
			sp.product_id,
			sp.product_code,
			ssp.stock_adjusted,
			ssp.stock_current,
			stock_closing_balance,
			ROUND((
				SELECT ROUND(SUM(stock_available),2) AS stock_available
				FROM ".Monthalize('stock_storeroom_batch')." ssb
				WHERE (ssb.product_id = sp.product_id)
					AND (ssb.storeroom_id = ".$_SESSION["int_current_storeroom"].")
					AND (ssb.is_active = 'Y')
			),2)
			AS total_batch_stock

		FROM ".Yearalize('stock_balance')." sb

		INNER JOIN stock_product sp ON (sp.product_id = sb.product_id)

		LEFT JOIN ".Monthalize('stock_storeroom_product')." ssp ON (ssp.product_id = sb.product_id)
			AND (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")

		WHERE (sb.storeroom_id = ".$_SESSION['int_current_storeroom'].")
			AND (sb.balance_month = ".$_SESSION['int_month_loaded'].")
			AND (sb.balance_year = ".$_SESSION['int_year_loaded'].")

		ORDER BY sp.product_code+0
	";
	$qry_verify = new Query($sql);


/*
	$str_query = "
		SELECT
			sp.product_id,
			sp.product_code,
			ssp.stock_adjusted,
			ssp.stock_current,
			stock_closing_balance,
			ROUND((
				SELECT ROUND(SUM(stock_available),2) AS stock_available
				FROM ".Monthalize('stock_storeroom_batch')." ssb
				WHERE (ssb.product_id = sp.product_id)
					AND (ssb.storeroom_id = ".$_SESSION["int_current_storeroom"].")
					AND (ssb.is_active = 'Y')
			),2)
			AS total_batch_stock
		FROM ".Yearalize('stock_balance')." sb
		INNER JOIN stock_product sp ON (sp.product_id = sb.product_id)
		LEFT JOIN ".Monthalize('stock_storeroom_product')." ssp ON (ssp.product_id = sb.product_id)
			AND (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")
		WHERE (sb.storeroom_id = ".$_SESSION['int_current_storeroom'].")
			AND (sb.balance_month = ".$_SESSION['int_month_loaded'].")
			AND (sb.balance_year = ".$_SESSION['int_year_loaded'].")
		HAVING (ROUND(ssp.stock_current,2) <> ROUND(stock_closing_balance,2))
			|| (stock_closing_balance < 0)
			|| (ssp.stock_current < 0)
			|| ((ssp.stock_current > 0) AND (ssp.stock_adjusted > 0))
			|| (ROUND(total_batch_stock,2) <> ROUND(ssp.stock_current,2))
		ORDER BY sp.product_code+0
	";
	$qry_verify = new Query($str_query);
*/


	if ($qry_verify->b_error)
		echo "error ".mysql_error();
	$int_discrepancies = $qry_verify->RowCount();
//echo $int_discrepancies."::".$str_query;
?>
<html>
<head>
	<link href="../../include/styles.css" rel="stylesheet" type="text/css">
	<script language='javascript'>
		function goBack() {
			document.location = '../index_verification_tools.php';
		}
		function applyCorrections() {
			if (confirm('Are you sure?'))
				document.location = 'stock_closing_balance.php?action=apply_corrections';
		}
	</script>
</head>

<body leftmargin='20px' rightmargin='20px' topmargin='20px' bottommargin='20px'>

<?
	if (IsSet($_GET['action'])) {
		if ($_GET['action'] == 'apply_corrections') {
			/*
			for ($i=0;$i<count($arr_found);$i++) {

				if ($arr_found[$i][3] < 0) {
					$qry_verify->Query("
						UPDATE ".Yearalize('stock_balance')."
						SET stock_closing_balance = 0
						WHERE (product_id = ".$arr_found[$i][0].")
							AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
							AND (balance_month = ".$_SESSION['int_month_loaded'].")
							AND (balance_year = ".$_SESSION['int_year_loaded'].")
					");

					$qry_verify->Query("
						UPDATE ".Monthalize('stock_storeroom_product')."
						SET stock_adjusted = ".$arr_found[$i][3]."
						WHERE (product_id = ".$arr_found[$i][0].")
							AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
					");
				}
				else {
					$qry_verify->Query("
						UPDATE ".Yearalize('stock_balance')."
						SET stock_closing_balance = ".$arr_found[$i][5]."
						WHERE (product_id = ".$arr_found[$i][0].")
							AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
							AND (balance_month = ".$_SESSION['int_month_loaded'].")
							AND (balance_year = ".$_SESSION['int_year_loaded'].")
					");
				}

				if ($arr_found[$i][5] <> $arr_found[$i][6]) {
					$qry_verify->Query("
						UPDATE ".Monthalize('stock_storeroom_product')."
						SET stock_current = ".$arr_found[$i][5]."
						WHERE (product_id = ".$arr_found[$i][0].")
							AND (storeroom_id = ".$_SESSION['int_current_storeroom'].")
					");
				}
			}
		*/
		boundingBoxStart("800", "../../images/blank.gif");
?>
		<br>
		<div class='normaltext'>This function is temporarily not available.</div>
		<br>
		<input type='button' name='action' value='Back' class='settings_button' onclick='javascript:goBack()'>
		<br><br>
<?
		boundingBoxEnd("800", "../../images/blank.gif");

		}
	}
	else {
		
		boundingBoxStart("800", "../../images/blank.gif");
?>
		<br>
		<div class='normaltext'><? echo $int_discrepancies; ?> discrepancies found.</div>
		<br>
		<table border='0' cellspacing='0' cellpadding='2'>
			<tr valign='bottom'>
				<td width='50px' align='right' class='normaltext'><b>Code</b></td>
				<td width='100px' align='right' class='normaltext'><b>Closing<br>Balance</b></td>
				<td width='100px' align='right' class='normaltext'><b>Current<br>Stock</b></td>
				<td width='100px' align='right' class='normaltext'><b>Batch<br>Total</b></td>
				<td width='100px' align='right' class='normaltext'><b>Adjusted<br>Stock</b></td>
			</tr>
<?

			for ($i=0; $i<$qry_verify->RowCount(); $i++) {

				echo "<tr>";
				echo "<td class='normaltext' align='right'>".$qry_verify->FieldByName('product_code')."</td>";
				echo "<td class='normaltext' align='right'>".$qry_verify->FieldByName('stock_closing_balance')."</td>";
				echo "<td class='normaltext' align='right'>".$qry_verify->FieldByName('stock_current')."</td>";
				echo "<td class='normaltext' align='right'>".$qry_verify->FieldByName('total_batch_stock')."</td>";
				echo "<td class='normaltext' align='right'>".$qry_verify->FieldByName('stock_adjusted')."</td>";
				echo "</tr>";
				
				$qry_verify->Next();

			}

?>
		</table>
		<br>
		<input type='button' name='action' value='Correct Discrepancies' <? if ($int_discrepancies == 0) echo "disabled"?> class='settings_button' onclick='javascript:applyCorrections()'>
		<input type='button' name='action' value='Back' class='settings_button' onclick='javascript:goBack()'>
		<br><br>
		<font class='normaltext'>The adjusted stock will be set to the value in the 'Calculated Balance' in case it is negative.</font>
		<br>
<?
		boundingBoxEnd("800", "../../images/blank.gif");
	}
?>

</body>
</html>