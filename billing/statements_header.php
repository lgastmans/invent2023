<?
	$str_include_tax = 'N';
	if (IsSet($_GET['include_tax']))
		$str_include_tax = $_GET['include_tax'];
	
	$str_format = "DATE_BILL";
	if (IsSet($_GET['format']))
		$str_format = $_GET['format'];
?>
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../include/styles.css" />
    </head>
<body id='body_bgcolor' leftmargin=15 topmargin=5 marginwidth=5 marginheight=5>


	<table border=1 cellpadding=7 cellspacing=0>

	<?php
		if ($str_format == "DATE_BILL") {
	?>

		<tr class='normaltext_bold' bgcolor='lightgrey'>
			<td width='50px'>Date</td>
			<td width='60px'>Bill</td>
			<td width='60px'>Batch</td>
			<td width='120px'>Code</td>
			<td width='250px'>Description</td>
			<td width='80px'>Qty</td>
			<td width='80px'>Price</td>
			<td width='80px'>Discount %</td>
			<td width='80px' align="right">Taxable Value</td>
			<? if ($str_include_tax == 'Y') { ?>
				<td width='60px' align="right">Tax<br>Rate</td>
				<td width='60px' align="right">Tax<br> Amt</td>
			<? } ?>
			
			<td width='120px' align="right">Amount</td>
		</tr>

	<?php } else { ?>
	
		<tr class='normaltext_bold' bgcolor='lightgrey'>
			<td width='120px'>Code</td>
			<td width='250px'>Description</td>
			<!-- <td width='120px'>Batches</td> -->
			<td width='80px'>Qty</td>
			<td width='80px'>Price</td>
			<td width='80px'>Discount %</td>
			<td width='80px' align="right">Taxable Value</td>
			<td width='120px' align="right">Amount</td>
		</tr>
	<?php } ?>
	</table>
    
</body>
</html>