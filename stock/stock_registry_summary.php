<?
	require_once("../include/const.inc.php");
	require_once("../include/session.inc.php");
	require_once("../include/db.inc.php");
	
	$str_code = "";
	if (IsSet($_GET["code"]))
		$str_code = $_GET["code"];

	$qry_product = new Query("
		SELECT *
		FROM stock_product sp, stock_measurement_unit mu, ".Monthalize('stock_storeroom_product')." ssp
		WHERE product_code = '".$str_code."'
			AND (sp.measurement_unit_id = mu.measurement_unit_id)
			AND (sp.deleted = 'N')
			AND (sp.is_available = 'Y')
			AND (ssp.product_id = sp.product_id)
			AND (ssp.storeroom_id = ".$_SESSION['int_current_storeroom'].")
	");
	$flt_adjusted_stock = $qry_product->FieldByName('stock_adjusted');

	if ($qry_product->RowCount() > 0) {
		$str_unit = $qry_product->FieldByName('measurement_unit');
		
		$int_decimals = 3;
		if ($qry_product->FieldByName('is_decimal') == 'N')
			$int_decimals = 0;
		
		$str_query = "
			SELECT *
			FROM ".Yearalize('stock_balance')."
			WHERE (product_id = ".$qry_product->FieldByName('product_id').")
				AND (storeroom_id = ".$_SESSION["int_current_storeroom"].")
				AND (balance_month = ".$_SESSION["int_month_loaded"].")
				AND (balance_year = ".$_SESSION["int_year_loaded"].")
		";
		$qry_summary = new Query($str_query);
	}
?>

<html>
<head><title></title>
	<link rel="stylesheet" type="text/css" href="../include/sweetTitles.css" />
	<link rel="stylesheet" type="text/css" href="../include/styles.css" />
	<script type="text/javascript" src="../include/addEvent.js"></script>
	<script type="text/javascript" src="../include/sweetTitles.js"></script>
	
	<script language="javascript">
		function mouseGoesOver(element, aSource) {
			element.src = aSource;
		}
		
		function mouseGoesOut(element, aSource) {
			element.src = aSource;
		}
		
		function printStatement(strCode) {
			var myWin = window.open("stock_registry_print_sold.php?code="+strCode, "print_window");
			myWin.focus();
		}
	</script>
</head>

<body id=body_bgcolor leftmargin=5 topmargin=5>
<?
#echo $str_query.", ".$qry_product->RowCount();
?>
<? if ($qry_product->RowCount() > 0) { ?>

	<table width='800px' border='0' cellpadding='4' cellspacing='0'>
		<tr>
			<td class='normaltext_bold' colspan='4'><?echo $qry_product->FieldByName('product_description')." (".$str_unit.")";?></td>
		</tr>
		<tr valign='top'>
			<td width='230px'>
				<table width='100%' border='0' cellpadding='2' cellspacing='0'>
					<tr>
						<td class='normaltext_bold' align='right'>Opening Balance:</td>
						<td class='normaltext_bold'><b><?echo number_format($qry_summary->FieldByName('stock_opening_balance'),$int_decimals,'.',',');?></b></td>
					</tr>
					<tr>
						<td class='normaltext_bold' align='right'><a class='normaltext' href='#' title='closing balance'>Closing Balance:</a></td>
						<td class='normaltext_bold'><b><?echo number_format($qry_summary->FieldByName('stock_closing_balance'),$int_decimals,'.',',');?></b></td>        
					</tr>
					<tr>
						<td class='normaltext_bold' align='right'>Stock Mismatch Additions:</td>
						<td class='normaltext_bold'><b><?echo number_format($qry_summary->FieldByName('stock_mismatch_addition'),$int_decimals,'.',',');?></b></td>
					</tr>
					<tr>
						<td class='normaltext_bold' align='right'>Stock Mismatch Deductions:</td>
						<td class='normaltext_bold'><b><?echo number_format($qry_summary->FieldByName('stock_mismatch_deduction'),$int_decimals,'.',',');?></b></td>
					</tr>
				</table>
			</td>
			
			<td width='230px'>
				<table width='100%' border='0' cellpadding='2' cellspacing='0'>
					<tr>
						<td class='normaltext_bold' align='right'>
							<a class='normaltext' href="javascript:printStatement('<?echo $str_code;?>')" title='Print the total sold for the current month statement'><img src="../images/printer.png" border="0" onmouseover="javascript:mouseGoesOver(this, '../images/printer_active.png')" onmouseout="javascript:mouseGoesOut(this, '../images/printer.png')"></a>&nbsp;Stock Sold:
						</td>
						<td class='normaltext_bold'><b><?echo number_format($qry_summary->FieldByName('stock_sold'),$int_decimals,'.',',');?></b></td>
					</tr>
					<tr>
						<td class='normaltext_bold' align='right'>Stock Returned:</td>
						<td class='normaltext_bold'><b><?echo number_format($qry_summary->FieldByName('stock_returned'),$int_decimals,'.',',');?></b></td>
					</tr>
					<tr>
						<td class='normaltext_bold' align='right'>Stock Received:</td>
						<td class='normaltext_bold'><b><?echo number_format($qry_summary->FieldByName('stock_received'),$int_decimals,'.',',');?></b></td>
					</tr>
					<tr>
						<td class='normaltext_bold' align='right'>Stock Adjusted:</td>
						<td class='normaltext_bold'><b><?echo number_format($flt_adjusted_stock,$int_decimals,'.',',');?></b></td>        
					</tr>
					<tr>
						<td class='normaltext_bold' align='right'>Stock Cancelled:</td>
						<td class='normaltext_bold'><b><?echo number_format($qry_summary->FieldByName('stock_cancelled'),$int_decimals,'.',',');?></b></td>        
					</tr>
				</table>
			</td>
			
			<td width='240px'>
				<table width='100%' border='0' cellpadding='2' cellspacing='0'>
					<tr>
						<td class='normaltext_bold' align='right'>Storeroom Received:</td>
						<td class='normaltext_bold'><b><?echo number_format($qry_summary->FieldByName('stock_in'),$int_decimals,'.',',');?></b></td>
					</tr>
					<tr>
						<td class='normaltext_bold' align='right'>Storeroom Dispatched:</td>
						<td class='normaltext_bold'><b><?echo number_format($qry_summary->FieldByName('stock_out'),$int_decimals,'.',',');?></b></td>        
					</tr>
				</table>
			</td>
		</tr>
	</table>
	<br>
	<table border=1 cellpadding=7 cellspacing=0>
		<tr class='normaltext_bold' bgcolor='lightgrey'>
			<td width='140px'>Date</td>
			<td width='140px'>Type</td>
			<td width='100px'>Quantity</td>
			<td width='350px'>Description</td>
			<td width='100px'>Reference</td>
			<td width='80px'>User</td>
			<td width='100px'>Status</td>
		</tr>
	</table>
<? } ?>

</body>
</html>